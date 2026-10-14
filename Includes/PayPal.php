<?php
/**
 * Cliente mínimo de la API REST de PayPal (Orders v2) para recargar la billetera.
 * Documentación: https://developer.paypal.com/docs/api/orders/v2/
 *
 * Flujo (todo el dinero se valida en el servidor, nunca en el navegador):
 * 1. El botón de PayPal llama a Estudiante/paypal_crear_orden.php: se registra la recarga PENDIENTE
 *    y se crea una orden en PayPal por el monto exacto (paypal_crear_orden).
 * 2. El estudiante aprueba el pago en la ventana de PayPal.
 * 3. El botón llama a Estudiante/paypal_capturar_orden.php: el servidor captura la orden
 *    (paypal_capturar_orden), comprueba estado, monto y moneda, y acredita el saldo.
 * 4. Opcional: paypal_webhook.php recibe PAYMENT.CAPTURE.COMPLETED (firma verificada con PayPal)
 *    por si el navegador se cerró antes del paso 3. confirmar_recarga_pagada() evita duplicados.
 */
require_once __DIR__ . '/config_app.php';

/** Indica si hay credenciales de PayPal configuradas (si no, se usa la recarga simulada). */
function paypal_configurado()
{
    return PAYPAL_CLIENT_ID !== '' && PAYPAL_CLIENT_SECRET !== '';
}

/** URL base de la API según el modo (sandbox o live). */
function paypal_url_api()
{
    return PAYPAL_MODO === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

if (!function_exists('paypal_http')) {
    /**
     * Ejecuta una petición HTTP y devuelve [código_http, cuerpo_decodificado].
     * (Envuelta en function_exists para poder sustituirla en pruebas automatizadas.)
     * @throws RuntimeException si la conexión falla.
     */
    function paypal_http($metodo, $url, array $cabeceras = [], $cuerpo = null)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        if ($cuerpo !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo);
        }

        $respuesta = curl_exec($ch);
        if ($respuesta === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Error de conexión con PayPal: {$error}");
        }
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$codigo, json_decode($respuesta, true)];
    }
}

/**
 * Obtiene un access token OAuth2 (client_credentials). Se reutiliza durante la misma petición.
 * @throws RuntimeException
 */
function paypal_obtener_token()
{
    static $token = null;
    if ($token !== null) {
        return $token;
    }

    [$codigo, $datos] = paypal_http('POST', paypal_url_api() . '/v1/oauth2/token', [
        'Authorization: Basic ' . base64_encode(PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ], 'grant_type=client_credentials');

    if ($codigo !== 200 || empty($datos['access_token'])) {
        throw new RuntimeException("PayPal rechazó la autenticación (HTTP {$codigo}). Revisa PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET y PAYPAL_MODO.");
    }

    $token = $datos['access_token'];
    return $token;
}

/**
 * Petición autenticada a la API de PayPal.
 * @param string|null $id_idempotencia Valor de PayPal-Request-Id: repetir la petición con el mismo valor no duplica la operación.
 */
function paypal_api($metodo, $ruta, ?array $cuerpo = null, $id_idempotencia = null)
{
    $cabeceras = [
        'Authorization: Bearer ' . paypal_obtener_token(),
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    if ($id_idempotencia !== null) {
        $cabeceras[] = 'PayPal-Request-Id: ' . $id_idempotencia;
    }
    $json = null;
    if ($cuerpo !== null) {
        $json = json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } elseif ($metodo === 'POST') {
        $json = '{}';
    }
    return paypal_http($metodo, paypal_url_api() . $ruta, $cabeceras, $json);
}

/** Formatea un monto como lo exige PayPal ("25.00"). */
function paypal_monto($monto)
{
    return number_format((float) $monto, 2, '.', '');
}

/**
 * Crea una orden de pago (intent CAPTURE) por el monto exacto de la recarga.
 *
 * @param string $referencia Identificador propio de la recarga (viaja como custom_id e invoice_id).
 * @return string ID de la orden en PayPal.
 * @throws RuntimeException
 */
function paypal_crear_orden($referencia, $monto, $descripcion)
{
    [$codigo, $datos] = paypal_api('POST', '/v2/checkout/orders', [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $referencia,
            'custom_id' => $referencia,
            'invoice_id' => $referencia,
            'description' => $descripcion,
            'amount' => ['currency_code' => PAYPAL_MONEDA, 'value' => paypal_monto($monto)],
        ]],
        'application_context' => [
            'brand_name' => 'TutoLink',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'PAY_NOW',
        ],
    ], 'crear-' . $referencia);

    if (($codigo !== 200 && $codigo !== 201) || empty($datos['id'])) {
        throw new RuntimeException("PayPal no pudo crear la orden (HTTP {$codigo}): " . paypal_resumen_error($datos));
    }
    return $datos['id'];
}

/**
 * Captura (cobra) una orden aprobada por el comprador.
 * Si la orden ya había sido capturada, devuelve la captura existente (operación idempotente).
 *
 * @return array ['estado' => 'COMPLETADO'|'PENDIENTE'|'RECHAZADO'|'NO_APROBADO', 'capture_id', 'monto', 'moneda', 'referencia', 'reintentar' => bool]
 * @throws RuntimeException
 */
function paypal_capturar_orden($order_id)
{
    [$codigo, $datos] = paypal_api('POST', '/v2/checkout/orders/' . rawurlencode($order_id) . '/capture', null, 'capturar-' . $order_id);

    $problema = $datos['details'][0]['issue'] ?? '';

    if ($codigo === 422 && $problema === 'ORDER_ALREADY_CAPTURED') {
        // Otro intento (o el webhook) ya la capturó: consultamos la orden para obtener la captura.
        [$codigo, $datos] = paypal_api('GET', '/v2/checkout/orders/' . rawurlencode($order_id));
    } elseif ($codigo === 422 && $problema === 'INSTRUMENT_DECLINED') {
        // La tarjeta o fuente de pago fue rechazada: el comprador puede elegir otra.
        return ['estado' => 'RECHAZADO', 'reintentar' => true, 'capture_id' => null, 'monto' => 0, 'moneda' => '', 'referencia' => ''];
    } elseif ($codigo === 422 && $problema === 'ORDER_NOT_APPROVED') {
        // El comprador todavía no aprobó el pago en PayPal: no se cobra ni se marca como fallida.
        return ['estado' => 'NO_APROBADO', 'reintentar' => false, 'capture_id' => null, 'monto' => 0, 'moneda' => '', 'referencia' => ''];
    }

    if ($codigo < 200 || $codigo >= 300 || !is_array($datos)) {
        throw new RuntimeException("PayPal no pudo capturar la orden {$order_id} (HTTP {$codigo}): " . paypal_resumen_error($datos));
    }

    $captura = $datos['purchase_units'][0]['payments']['captures'][0] ?? null;
    if (!$captura) {
        return ['estado' => 'RECHAZADO', 'reintentar' => false, 'capture_id' => null, 'monto' => 0, 'moneda' => '', 'referencia' => ''];
    }

    $estados = ['COMPLETED' => 'COMPLETADO', 'PENDING' => 'PENDIENTE'];
    return [
        'estado' => $estados[$captura['status'] ?? ''] ?? 'RECHAZADO',
        'reintentar' => false,
        'capture_id' => $captura['id'] ?? null,
        'monto' => (float) ($captura['amount']['value'] ?? 0),
        'moneda' => $captura['amount']['currency_code'] ?? '',
        'referencia' => $captura['custom_id'] ?? ($datos['purchase_units'][0]['custom_id'] ?? ''),
    ];
}

/**
 * Verifica con PayPal que un webhook es auténtico (POST /v1/notifications/verify-webhook-signature).
 * @param array $cabeceras Cabeceras HTTP recibidas (nombres en cualquier formato de mayúsculas).
 * @param string $cuerpo Cuerpo crudo del webhook (se reenvía sin modificar).
 */
function paypal_webhook_valido(array $cabeceras, $cuerpo)
{
    if (PAYPAL_WEBHOOK_ID === '') {
        return false;
    }
    $h = array_change_key_case($cabeceras, CASE_LOWER);
    $campos = [
        'auth_algo' => $h['paypal-auth-algo'] ?? '',
        'cert_url' => $h['paypal-cert-url'] ?? '',
        'transmission_id' => $h['paypal-transmission-id'] ?? '',
        'transmission_sig' => $h['paypal-transmission-sig'] ?? '',
        'transmission_time' => $h['paypal-transmission-time'] ?? '',
        'webhook_id' => PAYPAL_WEBHOOK_ID,
    ];
    if (in_array('', $campos, true) || json_decode($cuerpo) === null) {
        return false;
    }

    // El evento se inserta tal cual llegó: re-codificarlo podría alterar la firma.
    $json = substr(json_encode($campos, JSON_UNESCAPED_SLASHES), 0, -1) . ',"webhook_event":' . $cuerpo . '}';

    [$codigo, $datos] = paypal_http('POST', paypal_url_api() . '/v1/notifications/verify-webhook-signature', [
        'Authorization: Bearer ' . paypal_obtener_token(),
        'Content-Type: application/json',
    ], $json);

    return $codigo === 200 && ($datos['verification_status'] ?? '') === 'SUCCESS';
}

/** Texto corto con el error devuelto por PayPal, para los logs. */
function paypal_resumen_error($datos)
{
    if (!is_array($datos)) {
        return 'sin respuesta';
    }
    $partes = [$datos['name'] ?? '', $datos['message'] ?? '', $datos['details'][0]['issue'] ?? '', $datos['debug_id'] ?? ''];
    return implode(' | ', array_filter($partes));
}
