<?php
// Copiar como config_local.php y poner las credenciales de PayPal Sandbox.

define('PAYPAL_CLIENT_ID', 'tu-client-id-sandbox');
define('PAYPAL_CLIENT_SECRET', 'tu-secret-sandbox');
define('PAYPAL_MODO', 'sandbox'); // 'live' solo con credenciales de producción

// Opcional: ID del webhook creado en tu app de PayPal (necesita una URL pública, p. ej. con ngrok).
// define('PAYPAL_WEBHOOK_ID', '');

// URL del proyecto en esta PC (sin "/" final).
define('APP_URL_BASE', 'http://localhost/PROYECTOINS');

// Descomenta para cambiar las comisiones sin tocar config_app.php
// define('COMISION_ESTUDIANTE', 0.05);
// define('COMISION_TUTOR', 0.10);
