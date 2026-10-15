<?php
// Configuración general: comisiones, cupos y PayPal.
// Las credenciales van en Includes/config_local.php, que no se sube a git.

if (file_exists(__DIR__ . '/config_local.php')) {
    require_once __DIR__ . '/config_local.php';
}

// --- Comisiones por uso de plataforma (0.05 = 5 %) ---
// Se descuenta del monto que paga el estudiante; lo que queda es la base del tutor.
defined('COMISION_ESTUDIANTE') || define('COMISION_ESTUDIANTE', 0.05);
// Se descuenta de la base que le llega al tutor.
defined('COMISION_TUTOR') || define('COMISION_TUTOR', 0.10);

// --- Cupos por tutoría grupal ---
defined('CUPO_MAXIMO_PERMITIDO') || define('CUPO_MAXIMO_PERMITIDO', 20);

// --- Recargas de billetera ---
defined('RECARGA_MINIMA') || define('RECARGA_MINIMA', 5.00);
defined('RECARGA_MAXIMA') || define('RECARGA_MAXIMA', 500.00);
// Máximo de recargas sin completar que un estudiante puede abrir por hora (evita abuso del endpoint).
defined('RECARGAS_PENDIENTES_MAX_HORA') || define('RECARGAS_PENDIENTES_MAX_HORA', 10);

// --- PayPal (Orders API v2) ---
// Si PAYPAL_CLIENT_ID o PAYPAL_CLIENT_SECRET están vacíos, la billetera usa la recarga simulada.
defined('PAYPAL_CLIENT_ID') || define('PAYPAL_CLIENT_ID', '');
defined('PAYPAL_CLIENT_SECRET') || define('PAYPAL_CLIENT_SECRET', '');
// 'sandbox' = pruebas con dinero ficticio (developer.paypal.com); 'live' = cobros reales.
defined('PAYPAL_MODO') || define('PAYPAL_MODO', 'sandbox');
// ID del webhook registrado en PayPal (opcional). Vacío = el webhook está deshabilitado;
// el saldo igual se acredita al capturar el pago desde la billetera.
defined('PAYPAL_WEBHOOK_ID') || define('PAYPAL_WEBHOOK_ID', '');
defined('PAYPAL_MONEDA') || define('PAYPAL_MONEDA', 'USD');

// URL pública donde está publicado el proyecto, sin "/" final (se usa para mostrar la URL del webhook).
defined('APP_URL_BASE') || define('APP_URL_BASE', 'http://localhost/PROYECTOINS');
