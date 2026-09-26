<?php
session_start();

// Código para evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. VERIFICACIÓN DE SESIÓN (ESTUDIANTE)
if (!isset($_SESSION['id'])) {
    header("Location: ../Login.php");
    exit();
}
$estudiante_id = $_SESSION['id']; // Obtenemos la ID del estudiante que hace la reserva

// Incluye la conexión a la base de datos
include "../Includes/db.php";

// 2. RECIBIR Y VALIDAR DATOS DEL FORMULARIO
if (
    !isset($_POST['tutor_id']) || !isset($_POST['oferta_id']) ||
    !isset($_POST['precio_total_calculado']) || !isset($_POST['fecha']) ||
    !isset($_POST['hora']) || !isset($_POST['duracion_horas']) ||
    !is_numeric($_POST['duracion_horas'])
) {
    $tutor_id_fail = $_POST['tutor_id'] ?? '0';
    header('Location: PerfilTutor.php?tutor_id=' . $tutor_id_fail . '&error=datos_incompletos');
    exit;
}

// Obtener los datos del formulario
$tutor_id = $_POST['tutor_id'];
$oferta_id = $_POST['oferta_id'];
$precio_total = (float) $_POST['precio_total_calculado'];
$fecha_solicitada = $_POST['fecha']; // YYYY-MM-DD
$hora_inicio = $_POST['hora'];       // HH:MM
$duracion_horas = (float) $_POST['duracion_horas'];

// 3. CALCULAR HORA DE FIN
$duracion_segundos = $duracion_horas * 3600;
$timestamp_inicio = strtotime($fecha_solicitada . ' ' . $hora_inicio);
$timestamp_fin = $timestamp_inicio + $duracion_segundos;
$hora_fin = date('H:i:s', $timestamp_fin); // Hora de fin para la DB (HH:MM:SS)

// 4. PREPARAR DATOS PARA VALIDACIÓN DE DISPONIBILIDAD
// Mapear el día de la semana (1=Lunes, 7=Domingo) al formato de texto de tu DB
$dia_reserva_num = (int) date('N', $timestamp_inicio);
$dias_map = [
    1 => 'LUNES',
    2 => 'MARTES',
    3 => 'MIÉRCOLES',
    4 => 'JUEVES',
    5 => 'VIERNES',
    6 => 'SÁBADO',
    7 => 'DOMINGO'
];
$dia_semana_db = $dias_map[$dia_reserva_num];


// =========================================================================
// 5. VALIDACIÓN CRÍTICA DE DISPONIBILIDAD
// =========================================================================

try {
    // 5.1: Verificar que el RANGO COMPLETO esté dentro de la disponibilidad del tutor.
    $sql_disponibilidad = "
        SELECT 1 
        FROM disponibilidad 
        WHERE 
            id_tutor = :tutor_id AND 
            dia_semana = :dia_semana AND 
            hora_inicio <= :hora_inicio_reserva AND 
            hora_fin >= :hora_fin_reserva
    ";
    $stmt_disp = $conn->prepare($sql_disponibilidad);
    $stmt_disp->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_disp->bindParam(':dia_semana', $dia_semana_db);
    $stmt_disp->bindParam(':hora_inicio_reserva', $hora_inicio); // Inicio de la reserva
    $stmt_disp->bindParam(':hora_fin_reserva', $hora_fin);       // Fin CALCULADO de la reserva
    $stmt_disp->execute();

    if ($stmt_disp->rowCount() === 0) {
        // La duración solicitada EXCEDió el tiempo que el tutor puso como disponible.
        header('Location: PerfilTutor.php?tutor_id=' . $tutor_id . '&error=fuera_rango_disponibilidad');
        exit;
    }

    // 5.2: Verificar que NO haya otra reserva SUPERPUESTA.
    $sql_superposicion = "
        SELECT 1 
        FROM solicitudes_tutorias 
        WHERE 
            id_tutor = :tutor_id AND 
            fecha = :fecha AND 
            estado IN ('PENDIENTE', 'APROBADA') AND 
            (
                -- Cláusula para detectar superposición: 
                -- Una cita choca si empieza antes de que la nueva termine Y termina después de que la nueva empiece
                hora_inicio < :hora_fin_reserva AND hora_fin > :hora_inicio_reserva
            )
    ";
    $stmt_super = $conn->prepare($sql_superposicion);
    $stmt_super->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_super->bindParam(':fecha', $fecha_solicitada);
    $stmt_super->bindParam(':hora_inicio_reserva', $hora_inicio);
    $stmt_super->bindParam(':hora_fin_reserva', $hora_fin);
    $stmt_super->execute();

    if ($stmt_super->rowCount() > 0) {
        // Ya existe una reserva para ese tutor en ese rango de tiempo.
        header('Location: PerfilTutor.php?tutor_id=' . $tutor_id . '&error=horario_ocupado');
        exit;
    }


    // =========================================================================
    // 6. INSERCIÓN DE LA SOLICITUD (Si las validaciones pasan)
    // =========================================================================

    $sql_insert = "
        INSERT INTO solicitudes_tutorias 
            (id_estudiante, id_tutor, id_oferta, fecha, hora_inicio, hora_fin, duracion, precio_total, estado)
        VALUES 
            (:estudiante, :tutor,  :oferta, :fecha, :h_inicio, :h_fin, :duracion, :precio, 'PENDIENTE')
    ";

    $stmt = $conn->prepare($sql_insert);

    // Bindings completados:
    $stmt->bindParam(':tutor', $tutor_id, PDO::PARAM_INT);
    $stmt->bindParam(':estudiante', $estudiante_id, PDO::PARAM_INT); // ¡Añadido!
    $stmt->bindParam(':oferta', $oferta_id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha_solicitada);
    $stmt->bindParam(':h_inicio', $hora_inicio);
    $stmt->bindParam(':h_fin', $hora_fin); // Hora de fin calculada
    $stmt->bindParam(':duracion', $duracion_horas); // Duración seleccionada
    $stmt->bindParam(':precio', $precio_total);

    $stmt->execute();

    // 7. REDIRECCIÓN DE ÉXITO
    // Asumiendo que existe una página para ver las solicitudes del estudiante
    header('Location: MisSolicitudes.php?success=reserva_enviada');
    exit;

} catch (PDOException $e) {
    // 8. MANEJO DE ERRORES DE BASE DE DATOS
    error_log("Error al procesar agendamiento: " . $e->getMessage());
    header('Location: PerfilTutor.php?tutor_id=' . $tutor_id . '&error=db_fail');
    exit;
}
?>