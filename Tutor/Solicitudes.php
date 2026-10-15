<?php
include 'Includes/Nav.php';

include "../Includes/db.php";
$tutor_id = $_SESSION['id'];

// 2. CONSULTA DE SOLICITUDES DIRIGIDAS AL TUTOR
// Solo mostramos las solicitudes PENDIENTES, ya que son las que requieren acción.
// cupo_ocupado = estudiantes ya aceptados/confirmados en la misma tutoría (misma oferta, fecha y horario).
$sql = "
    SELECT
        s.id AS solicitud_id,
        s.fecha,
        COALESCE(d.hora_inicio, s.hora_inicio) AS hora_inicio,
        COALESCE(d.hora_fin, s.hora_fin) AS hora_fin,
        s.duracion, s.precio_total, s.estado,
        m.nombre_materia AS materia,
        e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante,
        o.cupo_maximo,
        (
            SELECT COUNT(*)
            FROM solicitudes_tutorias s2
            WHERE s2.id_tutor = s.id_tutor
              AND s2.id_oferta = s.id_oferta
              AND s2.fecha = s.fecha
              AND s2.hora_inicio = s.hora_inicio
              AND s2.hora_fin = s.hora_fin
              AND s2.estado IN ('ACEPTADA', 'CONFIRMADA')
        ) AS cupo_ocupado
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN usuarios e ON s.id_estudiante = e.id -- Unimos con el estudiante que hizo la solicitud
    JOIN materias m ON o.id_materia = m.id -- Obtenemos el nombre de la materia
    LEFT JOIN disponibilidad d ON d.id = (
        SELECT d2.id
        FROM disponibilidad d2
        WHERE d2.id_tutor = s.id_tutor
          AND d2.dia_semana = CASE WEEKDAY(s.fecha)
              WHEN 0 THEN 'LUNES'
              WHEN 1 THEN 'MARTES'
              WHEN 2 THEN 'MIÉRCOLES'
              WHEN 3 THEN 'JUEVES'
              WHEN 4 THEN 'VIERNES'
              WHEN 5 THEN 'SÁBADO'
              WHEN 6 THEN 'DOMINGO'
          END
        ORDER BY ABS(TIME_TO_SEC(d2.hora_inicio) - TIME_TO_SEC(s.hora_inicio)),
                 d2.hora_inicio
        LIMIT 1
    )
    WHERE s.id_tutor = :tutor_id AND s.estado = 'PENDIENTE'
    ORDER BY s.fecha ASC, s.hora_inicio ASC;
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errores_accion = [
    'datos_invalidos' => 'La acción enviada no es válida.',
    'motivo_requerido' => 'Para rechazar una solicitud debes escribir el motivo (entre 10 y 500 caracteres).',
    'no_pendiente' => 'La solicitud ya no está pendiente o no te pertenece.',
    'cupo_lleno' => 'El cupo de esa tutoría ya está lleno. Rechaza la solicitud indicando el motivo o aumenta el cupo en tu perfil.',
    'db_fail' => 'Error de base de datos al procesar la solicitud.',
];
$error_accion = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Solicitudes</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <!-- Panel Izquierdo -->
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Gestionar Solicitudes</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Solicitudes Recibidas</li>
                    </ol>
                    <!--Contendo-->

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-bell me-1"></i>
                            Solicitudes de Reserva (PENDIENTES)
                        </div>
                        <div class="card-body">

                            <?php if (isset($_GET['success']) && $_GET['success'] == 'accion_exitosa'): ?>
                                <div class="alert alert-success">
                                    La solicitud fue procesada exitosamente.
                                </div>
                            <?php elseif (isset($errores_accion[$error_accion])): ?>
                                <div class="alert alert-warning">
                                    <?= $errores_accion[$error_accion] ?>
                                </div>
                            <?php endif; ?>

                            <?php if (count($solicitudes) > 0): ?>
                                <p class="small text-muted">
                                    <i class="fas fa-info-circle"></i> "Recibirás" es lo que se acreditará a tu saldo cuando el
                                    estudiante pague: el precio menos la tarifa de servicio (<?= porcentaje_comision(COMISION_ESTUDIANTE) ?>%)
                                    y la comisión de tutor (<?= porcentaje_comision(COMISION_TUTOR) ?>%).
                                    <a href="Billetera.php">Ver detalle de comisiones</a>.
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Materia</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Duración</th>
                                                <th>Precio</th>
                                                <th title="Lo que se acreditará a tu saldo después de las comisiones de la plataforma">Recibirás</th>
                                                <th>Cupo</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($solicitudes as $solicitud): ?>
                                                <?php
                                                $cupo_maximo = max(1, (int) $solicitud['cupo_maximo']);
                                                $cupo_ocupado = (int) $solicitud['cupo_ocupado'];
                                                $cupo_lleno = $cupo_ocupado >= $cupo_maximo;
                                                $nombre_estudiante = $solicitud['nombre_estudiante'] . ' ' . $solicitud['apellido_estudiante'];
                                                $desglose = calcular_desglose_pago($solicitud['precio_total']);
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($nombre_estudiante) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($solicitud['materia']) ?></td>
                                                    <td><?= date('d/M/Y', strtotime($solicitud['fecha'])) ?></td>
                                                    <td><?= date('H:i', strtotime($solicitud['hora_inicio'])) . ' - ' . date('H:i', strtotime($solicitud['hora_fin'])) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($solicitud['duracion']) ?> hrs.</td>
                                                    <td>$<?= number_format($solicitud['precio_total'], 2) ?></td>
                                                    <td class="text-success fw-bold"
                                                        title="Tarifa de servicio: $<?= number_format($desglose['comision_estudiante'], 2) ?> · Comisión de tutor: $<?= number_format($desglose['comision_tutor'], 2) ?>">
                                                        $<?= number_format($desglose['neto_tutor'], 2) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $cupo_lleno ? 'bg-danger' : 'bg-secondary' ?>">
                                                            <i class="fas fa-users"></i> <?= $cupo_ocupado ?>/<?= $cupo_maximo ?>
                                                        </span>
                                                        <?php if ($cupo_lleno): ?>
                                                            <div class="small text-danger">Cupo lleno</div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" action="procesar_tutor_accion.php"
                                                            style="display:inline;">
                                                            <input type="hidden" name="solicitud_id"
                                                                value="<?= (int) $solicitud['solicitud_id'] ?>">
                                                            <input type="hidden" name="accion" value="ACEPTAR">
                                                            <button type="submit" class="btn btn-sm btn-success"
                                                                title="<?= $cupo_lleno ? 'El cupo de esta tutoría está lleno' : 'Aceptar Reserva' ?>"
                                                                <?= $cupo_lleno ? 'disabled' : '' ?>>
                                                                <i class="fas fa-check"></i> Aceptar
                                                            </button>
                                                        </form>

                                                        <button type="button" class="btn btn-sm btn-danger btn-rechazar"
                                                            title="Rechazar Reserva" data-bs-toggle="modal"
                                                            data-bs-target="#modalRechazo"
                                                            data-solicitud-id="<?= (int) $solicitud['solicitud_id'] ?>"
                                                            data-estudiante="<?= htmlspecialchars($nombre_estudiante) ?>"
                                                            data-cupo-lleno="<?= $cupo_lleno ? '1' : '0' ?>">
                                                            <i class="fas fa-times"></i> Rechazar
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    No tienes solicitudes de tutoría PENDIENTES en este momento.
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </main>
            <!--Footer-->
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>

    <!-- Modal: motivo del rechazo -->
    <div class="modal fade" id="modalRechazo" tabindex="-1" aria-labelledby="modalRechazoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="procesar_tutor_accion.php">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalRechazoLabel"><i class="fas fa-times-circle me-1"></i>
                            Rechazar solicitud</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Vas a rechazar la solicitud de <strong id="rechazo_estudiante"></strong>.</p>
                        <input type="hidden" name="solicitud_id" id="rechazo_solicitud_id">
                        <input type="hidden" name="accion" value="CANCELAR">
                        <label for="motivo_rechazo" class="form-label fw-bold">Motivo del rechazo <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" id="motivo_rechazo" name="motivo_rechazo" rows="4"
                            minlength="10" maxlength="500" required
                            placeholder="Ej.: El cupo de esta tutoría ya está completo. Te sugiero el jueves a las 15:00."></textarea>
                        <div class="form-text">El estudiante verá este mensaje en "Mis Solicitudes". Mínimo 10 caracteres.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                        <button type="submit" class="btn btn-danger">Rechazar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        // Pasa los datos de la fila al modal de rechazo
        document.getElementById('modalRechazo').addEventListener('show.bs.modal', function (event) {
            const boton = event.relatedTarget;
            const motivo = document.getElementById('motivo_rechazo');
            document.getElementById('rechazo_solicitud_id').value = boton.getAttribute('data-solicitud-id');
            document.getElementById('rechazo_estudiante').textContent = boton.getAttribute('data-estudiante');
            motivo.value = boton.getAttribute('data-cupo-lleno') === '1'
                ? 'El cupo de esta tutoría ya está completo. '
                : '';
        });
    </script>
</body>

</html>
