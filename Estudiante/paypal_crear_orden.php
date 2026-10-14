<?php
/**
 * Paso 1 del pago con PayPal: lo llama el botón de PayPal (fetch) desde la Billetera.
 * Registra la recarga como PENDIENTE y crea la orden en PayPal por el monto exacto.
 *
 * Entrada (JSON): {"monto": 25.00}   Cabecera: X-CSRF-Token
 * Salida (JSON):  {"id": "<order id de PayPal>"}  o  {"error": "..."}
 */
session_start();
include '../Includes/db.php';
include '../Includes/PayPal.php';
include '../Includes/Seguridad.php';

// 1. VALIDACIONES DE ACCESO
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(405, ['error' => 'Método no permitido.']);
}
if (!isset($_SESSION['id']) || ($_SESSION['rol'] ?? '') !== 'estudiante') {
    responder_json(401, ['error' => 'Tu sesión expiró. Vuelve a iniciar sesión.']);
}
if (!csrf_valido(csrf_recibido())) {
    responder_json(403, ['error' => 'La página expiró. Recárgala e inténtalo de nuevo.']);
}
if (!paypal_configurado()) {
    responder_json(503, ['error' => 'La pasarela de pago no está disponible.']);
}

// 2. VALIDAR MONTO (el servidor decide el monto; el botón solo lo propone)
$entrada = json_decode(file_get_contents('php://input'), true);
$monto = filter_var($entrada['monto'] ?? null, FILTER_VALIDATE_FLOAT);

if ($monto === false || $monto < RECARGA_MINIMA || $monto > RECARGA_MAXIMA) {
    responder_json(422, ['error' => 'El monto debe estar entre $' . number_format(RECARGA_MINIMA, 2)
        . ' y $' . number_format(RECARGA_MAXIMA, 2) . '.']);
}
$monto = round($monto, 2);
$estudiante_id = (int) $_SESSION['id'];

try {
    // 3. LÍMITE DE RECARGAS ABIERTAS (evita crear órdenes en masa)
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM recargas_estudiante
        WHERE id_estudiante = :id AND estado = 'PENDIENTE'
          AND fecha_solicitud >= NOW() - INTERVAL 1 HOUR
    ");
    $stmt->execute([':id' => $estudiante_id]);
    if ((int) $stmt->fetchColumn() >= RECARGAS_PENDIENTES_MAX_HORA) {
        responder_json(429, ['error' => 'Demasiados intentos de recarga. Espera unos minutos.']);
    }

    // 4. REGISTRAR LA RECARGA COMO PENDIENTE
    $referencia = 'REC-' . $estudiante_id . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $stmt = $conn->prepare("
        INSERT INTO recargas_estudiante (id_estudiante, monto, metodo_pago, estado, fecha_solicitud, referencia)
        VALUES (:id_estudiante, :monto, 'PayPal', 'PENDIENTE', NOW(), :referencia)
    ");
    $stmt->execute([':id_estudiante' => $estudiante_id, ':monto' => $monto, ':referencia' => $referencia]);
    $id_recarga = $conn->lastInsertId();
} catch (PDOException $e) {
    error_log("PayPal crear orden (BD): " . $e->getMessage());
    responder_json(500, ['error' => 'No se pudo registrar la recarga. Inténtalo más tarde.']);
}

// 5. CREAR LA ORDEN EN PAYPAL
try {
    $order_id = paypal_crear_orden($referencia, $monto, 'Recarga de saldo TutoLink');

    $stmt = $conn->prepare("UPDATE recargas_estudiante SET paypal_order_id = :orden WHERE id = :id");
    $stmt->execute([':orden' => $order_id, ':id' => $id_recarga]);

    responder_json(200, ['id' => $order_id]);

} catch (Exception $e) {
    error_log("PayPal crear orden {$referencia}: " . $e->getMessage());
    $conn->prepare("UPDATE recargas_estudiante SET estado = 'FALLIDO' WHERE id = :id")->execute([':id' => $id_recarga]);
    responder_json(502, ['error' => 'No se pudo conectar con PayPal. Inténtalo más tarde.']);
}
