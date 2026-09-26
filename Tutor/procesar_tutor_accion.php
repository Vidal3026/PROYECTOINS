<?php
session_start();
include "../Includes/db.php"; 

// 1. Verificar Sesión y Rol (Tutor)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$tutor_id = $_SESSION['id'];

// 2. Recibir y Validar Datos
if (!isset($_POST['solicitud_id']) || !isset($_POST['accion'])) {
    header('Location: TutorSolicitudes.php?error=datos_invalidos');
    exit;
}

$solicitud_id = $_POST['solicitud_id'];
$accion = strtoupper($_POST['accion']);

// Definir el nuevo estado (Usando SWITCH para compatibilidad con PHP < 8.0)
$nuevo_estado = null;
switch ($accion) {
    case 'ACEPTAR':
        $nuevo_estado = 'ACEPTADA';
        break;
    case 'CANCELAR':
        $nuevo_estado = 'CANCELADA';
        break;
    default:
        $nuevo_estado = null; // No hace falta, pero es más explícito
        break;
}

if ($nuevo_estado === null) {
    header('Location: TutorSolicitudes.php?error=accion_desconocida');
    exit;
}

try {
    // 3. ACTUALIZAR EL ESTADO (y verificar la propiedad de la solicitud por seguridad)
    $sql = "
        UPDATE solicitudes_tutorias
        SET estado = :nuevo_estado
        WHERE id = :solicitud_id
        AND id_tutor = :tutor_id  -- Seguridad: solo puede actualizar sus propias solicitudes
        AND estado = 'PENDIENTE'; -- Solo actualizar si actualmente está PENDIENTE
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nuevo_estado', $nuevo_estado);
    $stmt->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
    $stmt->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    
    $stmt->execute();

    // 4. Redirección de Éxito
    // El 'ACEPTADA' ahora requiere que el estudiante pague. 
    // El 'CANCELADA' notifica al estudiante.
    header('Location: Solicitudes.php?success=accion_exitosa&estado=' . $nuevo_estado);
    exit;

} catch (PDOException $e) {
    error_log("Error al procesar acción del tutor: " . $e->getMessage());
    header('Location: MisSolicitudes.php?error=db_fail');
    exit;
}
?>