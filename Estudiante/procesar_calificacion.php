<?php
// procesar_calificacion.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../Includes/db.php'; // Asegúrate de que esta ruta sea correcta para tu conexión

// 1. Verificación de Seguridad y Método
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Login.php");
    exit();
}

$id_estudiante = $_SESSION['id'];

// 2. Recolección y Sanitización de Datos
$solicitud_id = filter_input(INPUT_POST, 'solicitud_id', FILTER_VALIDATE_INT);
$id_tutor = filter_input(INPUT_POST, 'id_tutor', FILTER_VALIDATE_INT);
// El campo del select del modal se llama 'calificacion'
$calificacion = filter_input(INPUT_POST, 'calificacion', FILTER_VALIDATE_FLOAT); 
$comentario = trim($_POST['comentario'] ?? '');

// 3. Validación
if (!$solicitud_id || !$id_tutor || $calificacion < 1 || $calificacion > 5) {
    $_SESSION['mensaje'] = "Error de validación: Faltan datos necesarios para la calificación.";
    $_SESSION['tipo_mensaje'] = 'danger';
    header("Location: Historial.php");
    exit();
}

// 4. Procesamiento en la Base de Datos
try {
    // A. Verificar si ya fue calificada 
    $sql_check = "SELECT id FROM calificaciones_tutorias WHERE id_solicitud = :solicitud_id";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
    $stmt_check->execute();
    
    if (!$stmt_check->fetch()) {
        // B. Insertar la nueva calificación
        $sql_insert = "
            INSERT INTO calificaciones_tutorias 
            (id_solicitud, id_estudiante, id_tutor, calificacion, comentario, fecha_calificacion)
            VALUES 
            (:id_solicitud, :id_estudiante, :id_tutor, :calificacion, :comentario, NOW())
        ";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':id_solicitud', $solicitud_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':id_estudiante', $id_estudiante, PDO::PARAM_INT);
        $stmt_insert->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
        $stmt_insert->bindParam(':calificacion', $calificacion);
        $stmt_insert->bindParam(':comentario', $comentario);
        
        $stmt_insert->execute();

        $_SESSION['mensaje'] = "¡Gracias! Tu calificación ha sido enviada con éxito.";
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
         $_SESSION['mensaje'] = "Error: Esta tutoría ya fue calificada.";
         $_SESSION['tipo_mensaje'] = 'danger';
    }

} catch (PDOException $e) {
    error_log("Error al insertar calificación (Archivo procesar): " . $e->getMessage());
    $_SESSION['mensaje'] = "Error interno al guardar la calificación. Intenta de nuevo.";
    $_SESSION['tipo_mensaje'] = 'danger';
}

// 5. Redirigir al historial
header("Location: Historial.php");
exit();
?>