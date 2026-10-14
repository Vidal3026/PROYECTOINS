<?php
// Configuración local de esta PC (no se sube a git). Ver config_local.example.php.

// Apache de XAMPP escucha en el puerto 8080 en esta máquina.
define('APP_URL_BASE', 'http://localhost:8080/PROYECTOINS');

// Credenciales de PayPal Sandbox, app "Tutolink" (developer.paypal.com > Apps & Credentials).
// Solo pruebas con dinero ficticio. Nunca pongas aquí credenciales Live sin cambiar PAYPAL_MODO.
define('PAYPAL_CLIENT_ID', 'AZbe2r8e-Cg2UCs-0pXLDgnxnUTUw8GQnJBvE1zKKhU7yBXsrHKxqGdhOU_uzQ3zZh1CpNijkMrQaTIu');
define('PAYPAL_CLIENT_SECRET', 'EJeN39ymgkHEIsDENDKrOs9ewj2t3y2qUEXMNKUV_sa3jhkFuJvgSaG7bUU2TPQ7IRqr7DBDJu6Po44U');
define('PAYPAL_MODO', 'sandbox');
