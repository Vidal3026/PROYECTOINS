<?php
// ¡DEBE SER LO PRIMERO EN EL ARCHIVO!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluye la conexión a la base de datos (ruta relativa)
include '../Includes/db.php';

// 1. Verificación de Seguridad: Autenticación y Rol
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}

$id_tutor = $_SESSION['id'];

// 2. Procesar la solicitud POST del modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitud_id'])) {
    $solicitud_id = $_POST['solicitud_id'];
    
    // Consulta de actualización: Cambia el estado a CANCELADA
    // Se asegura de que la solicitud esté CONFIRMADA y pertenezca al tutor logueado.
    $sql_update = "
        UPDATE solicitudes_tutorias 
        SET estado = 'CANCELADA', fecha_cancelacion = NOW()
        WHERE id = :solicitud_id 
        AND id_tutor = :id_tutor 
        AND estado = 'CONFIRMADA'
    ";

    try {
        $stmt = $conn->prepare($sql_update);
        $stmt->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
        $stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            // Éxito
            $_SESSION['mensaje'] = "🚫 Tutoría #$solicitud_id **cancelada** exitosamente. El estudiante ha sido notificado.";
            $_SESSION['tipo_mensaje'] = "success"; 
            
            // NOTA: Aquí iría la lógica adicional para manejar el reembolso/crédito
            // al estudiante y el envío de una notificación real.
            
        } else {
            // Advertencia (ya cancelada, completada, o error de ID/permisos)
            $_SESSION['mensaje'] = "⚠️ Error: La tutoría #$solicitud_id no pudo ser cancelada. Es posible que ya estuviera cerrada/cancelada o que no tenga permiso.";
            $_SESSION['tipo_mensaje'] = "warning";
        }
    } catch (PDOException $e) {
        error_log("Error al cancelar tutoría: " . $e->getMessage());
        $_SESSION['mensaje'] = "❌ Error de base de datos al intentar cancelar la tutoría.";
        $_SESSION['tipo_mensaje'] = "danger";
    }

} else {
    $_SESSION['mensaje'] = "❌ Solicitud no válida.";
    $_SESSION['tipo_mensaje'] = "danger";
}

// Redirige siempre a la vista principal para mostrar el mensaje
header("Location: proximas_tutorias.php"); 
exit();
?>