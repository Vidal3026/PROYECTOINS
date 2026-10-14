<?php
/**
 * Paso 2 del pago con PayPal: lo llama el botón cuando el estudiante aprueba el pago.
 * Captura (cobra) la orden en PayPal y, si el cobro se completó por el monto y la moneda
 * correctos, acredita el saldo una sola vez.
 *
 * Entrada (JSON): {"orderID": "..."}   Cabecera: X-CSRF-Token
 * Salida (JSON):  {"ok": true, "mensaje": "...", "redirect": "..."}  o  {"error": "...", "reintentar": bool}
 */
session_start();
include '../Includes/db.php';
include '../Includes/Wallet.php';
include '../Includes/PayPal.php';
include '../Includes/Seguridad.php';

// 1. VALIDACIONES DE ACCESO
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(405, ['error' => 'Método no permitido.']);
}
if (!isset($_SESSION['id']) || ($_SESSION['rol'] ?? '') !== 'estudiante') {
    responder_json(401, ['error' => 'Tu sesión expiró. Si PayPal confirmó el pago, el saldo se acreditará igualmente.']);
}
if (!csrf_valido(csrf_recibido())) {
    responder_json(403, ['error' => 'La página expiró. Recárgala e inténtalo de nuevo.']);
}
if (!paypal_configurado()) {
    responder_json(503, ['error' => 'La pasarela de pago no está disponible.']);
}

$entrada = json_decode(file_get_contents('php://input'), true);
$order_id = (string) ($entrada['orderID'] ?? '');
if (!preg_match('/^[A-Z0-9]{5,40}$/', $order_id)) {
    responder_json(422, ['error' => 'Orden de pago inválida.']);
}

// 2. LA ORDEN DEBE PERTENECER A UNA RECARGA DE ESTE ESTUDIANTE
$stmt = $conn->prepare("
    SELECT id, referencia, monto, estado
    FROM recargas_estudiante
    WHERE paypal_order_id = :orden AND id_estudiante = :estudiante
");
$stmt->execute([':orden' => $order_id, ':estudiante' => (int) $_SESSION['id']]);
$recarga = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recarga) {
    responder_json(404, ['error' => 'No se encontró la recarga asociada a este pago.']);
}

$exito = function ($monto) {
    $mensaje = 'Recarga exitosa. Se añadieron $' . number_format($monto, 2) . ' a tu saldo.';
    responder_json(200, [
        'ok' => true,
        'mensaje' => $mensaje,
        'redirect' => 'Billetera.php?success=recarga_acreditada&msg=' . urlencode($mensaje),
    ]);
};

if ($recarga['estado'] === 'COMPLETADO') {
    $exito((float) $recarga['monto']);
}

// 3. CAPTURAR EL PAGO EN PAYPAL
try {
    $captura = paypal_capturar_orden($order_id);
} catch (Exception $e) {
    error_log("PayPal capturar {$recarga['referencia']}: " . $e->getMessage());
    responder_json(502, ['error' => 'No se pudo confirmar el pago con PayPal. Si se realizó el cargo, el saldo se acreditará al verificarse.']);
}

if ($captura['estado'] === 'NO_APROBADO') {
    // La recarga sigue PENDIENTE: el estudiante aún puede aprobarla en la ventana de PayPal.
    responder_json(402, ['error' => 'Todavía no apruebas el pago en PayPal. No se realizó ningún cargo.']);
}

if ($captura['estado'] === 'RECHAZADO') {
    if ($captura['reintentar']) {
        // El botón de PayPal reabre la ventana para elegir otra tarjeta o cuenta.
        responder_json(402, ['error' => 'PayPal rechazó el método de pago. Elige otro.', 'reintentar' => true]);
    }
    $conn->prepare("UPDATE recargas_estudiante SET estado = 'FALLIDO' WHERE id = :id")->execute([':id' => $recarga['id']]);
    responder_json(402, ['error' => 'El pago no fue aprobado. No se realizó ningún cargo a tu saldo.']);
}

if ($captura['estado'] === 'PENDIENTE') {
    // PayPal retiene el cobro para revisión; se acreditará cuando llegue PAYMENT.CAPTURE.COMPLETED.
    responder_json(202, ['error' => 'PayPal está revisando el pago. El saldo se acreditará cuando lo confirme.']);
}

// 4. VERIFICAR QUE LO COBRADO CORRESPONDE A ESTA RECARGA
if ($captura['moneda'] !== PAYPAL_MONEDA || ($captura['referencia'] !== '' && $captura['referencia'] !== $recarga['referencia'])) {
    error_log("PayPal capturar {$recarga['referencia']}: datos no coinciden " . json_encode($captura));
    responder_json(409, ['error' => 'El pago recibido no coincide con la recarga. Contacta a soporte.']);
}

// 5. ACREDITAR (valida el monto y evita duplicados)
$resultado = confirmar_recarga_pagada($conn, $recarga['referencia'], (string) $captura['capture_id'], $captura['monto']);

if ($resultado['success']) {
    $exito((float) $recarga['monto']);
}
responder_json(409, ['error' => $resultado['message']]);
