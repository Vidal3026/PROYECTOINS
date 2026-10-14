<?php
// ¡DEBE SER LO PRIMERO EN EL ARCHIVO!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluye la conexión a la base de datos (ruta relativa)
include '../Includes/db.php';
include '../Includes/Wallet.php';

// 1. Verificación de Seguridad: Autenticación y Rol
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}

$id_tutor = (int) $_SESSION['id'];

// 2. Procesar la solicitud POST del modal
$solicitud_id = filter_input(INPUT_POST, 'solicitud_id', FILTER_VALIDATE_INT);
$motivo = trim($_POST['motivo_rechazo'] ?? '');
$largo_motivo = mb_strlen($motivo, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$solicitud_id) {
    $_SESSION['mensaje'] = "Solicitud no válida.";
    $_SESSION['tipo_mensaje'] = "danger";
} elseif ($largo_motivo < 10 || $largo_motivo > 500) {
    $_SESSION['mensaje'] = "Para cancelar una tutoría debes explicar el motivo al estudiante (entre 10 y 500 caracteres).";
    $_SESSION['tipo_mensaje'] = "warning";
} else {
    // Cambia el estado a CANCELADA y, si ya estaba pagada, reembolsa al estudiante,
    // descuenta al tutor lo recibido y revierte las comisiones (todo en una transacción).
    $resultado = cancelar_tutoria_con_reembolso($conn, $id_tutor, $solicitud_id, $motivo);

    if ($resultado['success']) {
        $_SESSION['mensaje'] = "Tutoría #{$solicitud_id} cancelada. "
            . ($resultado['reembolso'] > 0
                ? "Se reembolsaron $" . number_format($resultado['reembolso'], 2) . " al estudiante y se descontó de tu saldo lo que habías recibido."
                : "El estudiante ha sido notificado.");
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "La tutoría #{$solicitud_id} no pudo ser cancelada: " . $resultado['message'];
        $_SESSION['tipo_mensaje'] = "warning";
    }
}

// Redirige siempre a la vista principal para mostrar el mensaje
header("Location: proximas_tutorias.php");
exit();
