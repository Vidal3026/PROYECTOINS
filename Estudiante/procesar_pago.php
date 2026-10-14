<?php
session_start();

// Código para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. VERIFICACIÓN DE SESIÓN (Estudiante)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}

include "../Includes/db.php";
include "../Includes/Wallet.php";
$estudiante_id = $_SESSION['id'];

// 2. VALIDAR ID DE LA SOLICITUD
$solicitud_id = filter_input(INPUT_GET, 'solicitud_id', FILTER_VALIDATE_INT);
if (!$solicitud_id) {
    header('Location: MisSolicitudes.php?error=no_solicitud_id');
    exit();
}

// 3. EJECUTAR EL PAGO
// procesar_transaccion_tutoria valida pertenencia, estado ACEPTADA y saldo, cobra al estudiante,
// descuenta las comisiones de la plataforma, acredita el neto al tutor y confirma la solicitud,
// todo dentro de una sola transacción.
$resultado = procesar_transaccion_tutoria($conn, $estudiante_id, $solicitud_id);

if ($resultado['success']) {
    header('Location: MisSolicitudes.php?success=pago_confirmado');
    exit();
}

switch ($resultado['codigo']) {
    case 'pago_ya_confirmado':
        header('Location: MisSolicitudes.php?success=pago_ya_confirmado');
        break;
    case 'pago_fallido':
        header('Location: MisSolicitudes.php?error=pago_fallido&msg=' . urlencode($resultado['message']));
        break;
    default:
        error_log("FALLO DE PAGO: Solicitud ID {$solicitud_id}. " . $resultado['message']);
        header('Location: MisSolicitudes.php?error=' . urlencode($resultado['codigo']));
        break;
}
exit();
