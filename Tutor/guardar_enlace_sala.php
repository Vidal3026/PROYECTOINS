<?php
// Inicia la sesión para acceder al ID del tutor
session_start();

// Verifica que la sesión del tutor esté activa
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}

// Incluir la conexión a la base de datos (ruta relativa desde Tutor/ a Includes/)
include "../Includes/db.php"; 

$solicitud_id = 0; // Inicializamos para evitar errores si no se reciben datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recibir y sanitizar datos
    $solicitud_id = $_POST['solicitud_id'] ?? 0;
    $link_sala = trim($_POST['link_sala'] ?? ''); // Elimina espacios en blanco

    // 2. Validar datos
    if (!is_numeric($solicitud_id) || empty($link_sala)) {
        $_SESSION['mensaje'] = "Datos incompletos o inválidos. Asegúrate de proporcionar un enlace.";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: sala_virtual.php?id=" . $solicitud_id);
        exit();
    }
    
    $id_tutor = $_SESSION['id'];

    try {
        // 3. Consulta para actualizar el enlace en la solicitud
        // La condición WHERE asegura que solo el tutor dueño pueda modificarla.
        $sql = "UPDATE solicitudes_tutorias 
                SET link_sala_virtual = :link, estado = 'CONFIRMADA' 
                WHERE id = :id AND id_tutor = :tutor_id";
                
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':link', $link_sala);
        $stmt->bindParam(':id', $solicitud_id, PDO::PARAM_INT);
        $stmt->bindParam(':tutor_id', $id_tutor, PDO::PARAM_INT);
        $stmt->execute();
        
        // Verifica si la actualización fue exitosa
        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje'] = "El enlace de la sala virtual ha sido guardado exitosamente. Ya puedes iniciar la sesión.";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "No se pudo actualizar el enlace. Verifica que la tutoría exista y te pertenezca.";
            $_SESSION['tipo_mensaje'] = "warning";
        }
        
    } catch (PDOException $e) {
        // 4. Manejo de errores de base de datos
        error_log("Error de DB al guardar enlace: " . $e->getMessage());
        $_SESSION['mensaje'] = "Error de sistema al guardar el enlace. Por favor, contacta a soporte.";
        $_SESSION['tipo_mensaje'] = "danger";
    }
} else {
    // Si no se accedió por POST, se redirige.
    $_SESSION['mensaje'] = "Acceso denegado.";
    $_SESSION['tipo_mensaje'] = "danger";
}

// 5. Redirección final a la página de la sala virtual
header("Location: sala_virtual.php?id=" . $solicitud_id);
exit();
?>