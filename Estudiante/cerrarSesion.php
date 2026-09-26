<?php
session_start();

// Vacía todas las variables de sesión
$_SESSION = array();

// Si se usan cookies de sesión, destrúyelas también
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruye la sesión
session_destroy();

// Redirige al usuario a la página de login con el parámetro de estado
header("Location: ../Login.php?status=logout_exitoso");
exit();
?>