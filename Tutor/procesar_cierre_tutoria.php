<?php
// ¡DEBE SER LO PRIMERO EN EL ARCHIVO!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegura que la ruta de inclusión sea correcta
include '../Includes/db.php'; 

// 1. Verificación de Seguridad
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}

$id_tutor = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitud_id'])) {
    $solicitud_id = $_POST['solicitud_id'];
    
    // Consulta de actualización: Incluye fecha_cierre (asumiendo que ya creaste la columna)
    $sql_update = "
        UPDATE solicitudes_tutorias 
        SET estado = 'COMPLETADA', fecha_cierre = NOW()
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
            $_SESSION['mensaje'] = "✅ Tutoría #$solicitud_id marcada como **COMPLETADA**. El estudiante puede ahora calificar.";
            $_SESSION['tipo_mensaje'] = "success"; 
        } else {
            // Advertencia (ej. ya estaba cerrada o el ID no coincide)
            $_SESSION['mensaje'] = "⚠️ Advertencia: La tutoría #$solicitud_id no pudo ser finalizada. (Verifique el estado o los IDs).";
            $_SESSION['tipo_mensaje'] = "warning";
        }
    } catch (PDOException $e) {
        // Error de la base de datos
        error_log("Error al cerrar tutoría: " . $e->getMessage());
        $_SESSION['mensaje'] = "❌ Error de base de datos al intentar finalizar la tutoría.";
        $_SESSION['tipo_mensaje'] = "danger";
    }

} else {
    $_SESSION['mensaje'] = "❌ Acceso no autorizado o solicitud no válida.";
    $_SESSION['tipo_mensaje'] = "danger";
}

// Redirige
header("Location: proximas_tutorias.php"); 
exit();
?>