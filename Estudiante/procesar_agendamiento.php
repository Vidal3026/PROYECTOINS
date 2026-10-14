<?php
session_start();

// Código para evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. VERIFICACIÓN DE SESIÓN (ESTUDIANTE)
if (!isset($_SESSION['id']) || ($_SESSION['rol'] ?? '') !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}
$estudiante_id = $_SESSION['id']; // Obtenemos la ID del estudiante que hace la reserva

// Incluye la conexión a la base de datos
include "../Includes/db.php";

// 2. RECIBIR Y VALIDAR DATOS DEL FORMULARIO
$tutor_id = filter_input(INPUT_POST, 'tutor_id', FILTER_VALIDATE_INT);
$oferta_id = filter_input(INPUT_POST, 'oferta_id', FILTER_VALIDATE_INT);
$fecha_solicitada = $_POST['fecha'] ?? '';   // YYYY-MM-DD
$hora_inicio = $_POST['hora'] ?? '';         // HH:MM
$duracion_horas = filter_input(INPUT_POST, 'duracion_horas', FILTER_VALIDATE_FLOAT);
$duraciones_validas = [1.0, 1.5, 2.0, 2.5, 3.0];

function volver_a_perfil($tutor_id, $error)
{
    header('Location: perfil_tutor.php?tutor_id=' . (int) $tutor_id . '&error=' . $error);
    exit;
}

if (
    !$tutor_id || !$oferta_id || !$duracion_horas
    || !in_array($duracion_horas, $duraciones_validas, true)
    || !DateTime::createFromFormat('Y-m-d', $fecha_solicitada)
    || !preg_match('/^\d{2}:\d{2}$/', $hora_inicio)
) {
    volver_a_perfil($tutor_id, 'datos_incompletos');
}

// 3. CALCULAR HORA DE INICIO Y FIN (formato HH:MM:SS, igual que en la BD)
$timestamp_inicio = strtotime($fecha_solicitada . ' ' . $hora_inicio);
$timestamp_fin = $timestamp_inicio + (int) ($duracion_horas * 3600);
$hora_inicio = date('H:i:s', $timestamp_inicio);
$hora_fin = date('H:i:s', $timestamp_fin);

if ($timestamp_inicio <= time()) {
    volver_a_perfil($tutor_id, 'fecha_pasada');
}

// 4. PREPARAR DATOS PARA VALIDACIÓN DE DISPONIBILIDAD
// Mapear el día de la semana (1=Lunes, 7=Domingo) al formato de texto de tu DB
$dias_map = [
    1 => 'LUNES',
    2 => 'MARTES',
    3 => 'MIÉRCOLES',
    4 => 'JUEVES',
    5 => 'VIERNES',
    6 => 'SÁBADO',
    7 => 'DOMINGO'
];
$dia_semana_db = $dias_map[(int) date('N', $timestamp_inicio)];

try {
    // =========================================================================
    // 5. OFERTA: precio y cupo se toman de la BD (nunca del formulario)
    // =========================================================================
    $stmt_oferta = $conn->prepare("
        SELECT precio_hora, cupo_maximo
        FROM ofertas_tutorias
        WHERE id = :oferta AND id_tutor = :tutor AND activo = 1
    ");
    $stmt_oferta->execute([':oferta' => $oferta_id, ':tutor' => $tutor_id]);
    $oferta = $stmt_oferta->fetch(PDO::FETCH_ASSOC);

    if (!$oferta) {
        volver_a_perfil($tutor_id, 'oferta_invalida');
    }

    $precio_total = round((float) $oferta['precio_hora'] * $duracion_horas, 2);
    $cupo_maximo = max(1, (int) $oferta['cupo_maximo']);

    // =========================================================================
    // 6. VALIDACIÓN CRÍTICA DE DISPONIBILIDAD
    // =========================================================================

    // 6.1: Verificar que el RANGO COMPLETO esté dentro de la disponibilidad del tutor.
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
        volver_a_perfil($tutor_id, 'fuera_rango_disponibilidad');
    }

    // 6.2: Revisar las reservas que se cruzan con este horario.
    // Una tutoría grupal es la misma oferta, fecha, hora de inicio y hora de fin: varios estudiantes
    // pueden unirse a ella hasta llenar el cupo. Cualquier otra reserva superpuesta bloquea el horario.
    $sql_superposicion = "
        SELECT id_estudiante, id_oferta, hora_inicio, hora_fin, estado
        FROM solicitudes_tutorias
        WHERE
            id_tutor = :tutor_id AND
            fecha = :fecha AND
            estado IN ('PENDIENTE', 'ACEPTADA', 'CONFIRMADA') AND
            hora_inicio < :hora_fin_reserva AND hora_fin > :hora_inicio_reserva
    ";
    $stmt_super = $conn->prepare($sql_superposicion);
    $stmt_super->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_super->bindParam(':fecha', $fecha_solicitada);
    $stmt_super->bindParam(':hora_inicio_reserva', $hora_inicio);
    $stmt_super->bindParam(':hora_fin_reserva', $hora_fin);
    $stmt_super->execute();

    $cupos_ocupados = 0;
    foreach ($stmt_super->fetchAll(PDO::FETCH_ASSOC) as $reserva) {
        $misma_sesion = (int) $reserva['id_oferta'] === (int) $oferta_id
            && $reserva['hora_inicio'] === $hora_inicio
            && $reserva['hora_fin'] === $hora_fin;

        if (!$misma_sesion) {
            // Ya existe otra tutoría del tutor en ese rango de tiempo.
            volver_a_perfil($tutor_id, 'horario_ocupado');
        }
        if ((int) $reserva['id_estudiante'] === (int) $estudiante_id) {
            volver_a_perfil($tutor_id, 'ya_inscrito');
        }
        if ($reserva['estado'] !== 'PENDIENTE') {
            $cupos_ocupados++;
        }
    }

    if ($cupos_ocupados >= $cupo_maximo) {
        volver_a_perfil($tutor_id, 'cupo_lleno');
    }


    // =========================================================================
    // 7. INSERCIÓN DE LA SOLICITUD (Si las validaciones pasan)
    // =========================================================================

    $sql_insert = "
        INSERT INTO solicitudes_tutorias
            (id_estudiante, id_tutor, id_oferta, fecha, hora_inicio, hora_fin, duracion, precio_total, estado)
        VALUES
            (:estudiante, :tutor,  :oferta, :fecha, :h_inicio, :h_fin, :duracion, :precio, 'PENDIENTE')
    ";

    $stmt = $conn->prepare($sql_insert);
    $stmt->bindParam(':tutor', $tutor_id, PDO::PARAM_INT);
    $stmt->bindParam(':estudiante', $estudiante_id, PDO::PARAM_INT);
    $stmt->bindParam(':oferta', $oferta_id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha_solicitada);
    $stmt->bindParam(':h_inicio', $hora_inicio);
    $stmt->bindParam(':h_fin', $hora_fin); // Hora de fin calculada
    $stmt->bindParam(':duracion', $duracion_horas); // Duración seleccionada
    $stmt->bindParam(':precio', $precio_total); // Precio calculado en el servidor

    $stmt->execute();

    // 8. REDIRECCIÓN DE ÉXITO
    header('Location: MisSolicitudes.php?success=reserva_enviada');
    exit;

} catch (PDOException $e) {
    // 9. MANEJO DE ERRORES DE BASE DE DATOS
    error_log("Error al procesar agendamiento: " . $e->getMessage());
    volver_a_perfil($tutor_id, 'db_fail');
}
?>
