<?php
/**
 * Protección CSRF: un token aleatorio por sesión que los formularios y peticiones fetch
 * deben enviar de vuelta. Evita que otra página haga acciones en nombre del usuario.
 * Requiere que la sesión ya esté iniciada (session_start()).
 */

/** Devuelve (y crea si no existe) el token CSRF de la sesión actual. */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Compara en tiempo constante el token recibido con el de la sesión. */
function csrf_valido($token)
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Token enviado en la cabecera X-CSRF-Token (peticiones fetch) o en el campo csrf_token (formularios). */
function csrf_recibido()
{
    return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
}

/** Responde JSON con el código HTTP indicado y termina la ejecución. */
function responder_json($codigo_http, array $datos)
{
    http_response_code($codigo_http);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}
