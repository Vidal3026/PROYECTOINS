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
$solicitud_id = filter_input(INPUT_POST, 'solicitud_id', FILTER_VALIDATE_INT);
$accion = strtoupper($_POST['accion'] ?? '');
$motivo = trim($_POST['motivo_rechazo'] ?? '');

if (!$solicitud_id || !in_array($accion, ['ACEPTAR', 'CANCELAR'], true)) {
    header('Location: Solicitudes.php?error=datos_invalidos');
    exit;
}

// Al rechazar es obligatorio explicarle el motivo al estudiante.
if ($accion === 'CANCELAR') {
    $largo_motivo = mb_strlen($motivo, 'UTF-8');
    if ($largo_motivo < 10 || $largo_motivo > 500) {
        header('Location: Solicitudes.php?error=motivo_requerido');
        exit;
    }
}

try {
    $conn->beginTransaction();

    // 3. Bloquear la solicitud (solo las propias y PENDIENTES)
    $stmt_sol = $conn->prepare("
        SELECT s.id, s.id_oferta, s.fecha, s.hora_inicio, s.hora_fin, o.cupo_maximo
        FROM solicitudes_tutorias s
        JOIN ofertas_tutorias o ON o.id = s.id_oferta
        WHERE s.id = :solicitud_id AND s.id_tutor = :tutor_id AND s.estado = 'PENDIENTE'
        FOR UPDATE
    ");
    $stmt_sol->execute([':solicitud_id' => $solicitud_id, ':tutor_id' => $tutor_id]);
    $solicitud = $stmt_sol->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        $conn->rollBack();
        header('Location: Solicitudes.php?error=no_pendiente');
        exit;
    }

    if ($accion === 'ACEPTAR') {
        // 4a. Verificar cupo: cuenta los estudiantes ya aceptados/confirmados en la misma tutoría.
        //     FOR UPDATE evita que dos aceptaciones simultáneas sobrepasen el cupo.
        $stmt_cupo = $conn->prepare("
            SELECT id
            FROM solicitudes_tutorias
            WHERE id_tutor = :tutor_id
              AND id_oferta = :oferta
              AND fecha = :fecha
              AND hora_inicio = :inicio
              AND hora_fin = :fin
              AND estado IN ('ACEPTADA', 'CONFIRMADA')
            FOR UPDATE
        ");
        $stmt_cupo->execute([
            ':tutor_id' => $tutor_id,
            ':oferta' => $solicitud['id_oferta'],
            ':fecha' => $solicitud['fecha'],
            ':inicio' => $solicitud['hora_inicio'],
            ':fin' => $solicitud['hora_fin'],
        ]);
        $ocupados = count($stmt_cupo->fetchAll());

        if ($ocupados >= max(1, (int) $solicitud['cupo_maximo'])) {
            $conn->rollBack();
            header('Location: Solicitudes.php?error=cupo_lleno');
            exit;
        }

        $stmt_upd = $conn->prepare("UPDATE solicitudes_tutorias SET estado = 'ACEPTADA' WHERE id = :id");
        $stmt_upd->execute([':id' => $solicitud_id]);
        $nuevo_estado = 'ACEPTADA';
    } else {
        // 4b. Rechazar guardando el motivo que verá el estudiante
        $stmt_upd = $conn->prepare("
            UPDATE solicitudes_tutorias
            SET estado = 'CANCELADA', motivo_rechazo = :motivo
            WHERE id = :id
        ");
        $stmt_upd->execute([':motivo' => $motivo, ':id' => $solicitud_id]);
        $nuevo_estado = 'CANCELADA';
    }

    $conn->commit();

    // 5. Redirección de Éxito
    // El 'ACEPTADA' ahora requiere que el estudiante pague.
    // El 'CANCELADA' notifica al estudiante con el motivo.
    header('Location: Solicitudes.php?success=accion_exitosa&estado=' . $nuevo_estado);
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error al procesar acción del tutor: " . $e->getMessage());
    header('Location: Solicitudes.php?error=db_fail');
    exit;
}
?>
