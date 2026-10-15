<?php
// Webhook de PayPal: confirma la recarga si el navegador se cerró antes de terminar el pago.
include 'Includes/db.php';
include 'Includes/Wallet.php';
include 'Includes/PayPal.php';
include 'Includes/Seguridad.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(405, ['mensaje' => 'Método no permitido']);
}
if (!paypal_configurado() || PAYPAL_WEBHOOK_ID === '') {
    responder_json(503, ['mensaje' => 'Webhook deshabilitado']);
}

$cuerpo = file_get_contents('php://input');
$cabeceras = function_exists('getallheaders') ? getallheaders() : [];

try {
    $valido = paypal_webhook_valido($cabeceras, $cuerpo);
} catch (Exception $e) {
    error_log('PayPal webhook: no se pudo verificar la firma: ' . $e->getMessage());
    responder_json(500, ['mensaje' => 'Verificación no disponible']); // PayPal reintentará
}
if (!$valido) {
    error_log('PayPal webhook: firma inválida.');
    responder_json(401, ['mensaje' => 'Firma inválida']);
}

$evento = json_decode($cuerpo, true);
$tipo = $evento['event_type'] ?? '';
$captura = $evento['resource'] ?? [];
$referencia = (string) ($captura['custom_id'] ?? '');

switch ($tipo) {
    case 'PAYMENT.CAPTURE.COMPLETED':
        if ($referencia === '' || ($captura['amount']['currency_code'] ?? '') !== PAYPAL_MONEDA) {
            error_log('PayPal webhook: captura sin referencia o con otra moneda: ' . ($captura['id'] ?? '?'));
            responder_json(200, ['mensaje' => 'Ignorado']);
        }
        $resultado = confirmar_recarga_pagada($conn, $referencia, (string) ($captura['id'] ?? ''), (float) ($captura['amount']['value'] ?? 0));
        // Si falla por un error temporal de BD, 500 hace que PayPal reintente.
        responder_json($resultado['success'] ? 200 : 500, ['mensaje' => $resultado['message']]);
        // no break: responder_json termina la ejecución

    case 'PAYMENT.CAPTURE.DENIED':
        if ($referencia !== '') {
            $conn->prepare("UPDATE recargas_estudiante SET estado = 'FALLIDO' WHERE referencia = :ref AND estado = 'PENDIENTE'")
                ->execute([':ref' => $referencia]);
        }
        responder_json(200, ['mensaje' => 'Recarga marcada como fallida']);

    case 'PAYMENT.CAPTURE.REFUNDED':
    case 'PAYMENT.CAPTURE.REVERSED':
        // El saldo ya acreditado no se descuenta automáticamente: queda registrado para que el admin lo revise.
        error_log("PayPal webhook: {$tipo} para la recarga {$referencia}. Revisar y ajustar saldo manualmente.");
        responder_json(200, ['mensaje' => 'Registrado']);

    default:
        responder_json(200, ['mensaje' => 'Evento ignorado']);
}
