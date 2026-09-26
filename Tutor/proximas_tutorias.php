<?php
// NOTA IMPORTANTE: Si 'Includes/Nav.php' no inicia la sesión, debes agregar session_start() aquí.
// Pero asumiremos que ya lo hace.
// Se incluye el archivo de navegación
include 'Includes/Nav.php';

// 1. Verificación de Seguridad
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];

// Asegúrate de que esta ruta sea correcta
include "../Includes/db.php";

// 1. CONSULTA DE TUTORÍAS CONFIRMADAS Y COMPLETADAS RECIENTES
// La consulta se mantiene igual, ya que es correcta y segura.
$sql_proximas = "
    SELECT 
        s.id AS solicitud_id,
        s.fecha, 
        s.hora_inicio, 
        s.hora_fin,
        s.duracion,
        s.precio_total,
        s.estado, 
        m.nombre_materia AS materia,
        CONCAT(e.nombre, ' ', e.apellido) AS estudiante 
    FROM solicitudes_tutorias s
    JOIN usuarios e ON s.id_estudiante = e.id
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN materias m ON o.id_materia = m.id
    WHERE s.id_tutor = :id_tutor 
    AND (
        s.estado = 'CONFIRMADA'
        OR (s.estado = 'COMPLETADA' AND s.fecha_cierre >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) 
    )
    ORDER BY s.fecha ASC, s.hora_inicio ASC
";
try {
    $stmt = $conn->prepare($sql_proximas);
    $stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
    $stmt->execute();
    $proximas_tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Es bueno registrar el error, pero no exponerlo al usuario final.
    error_log("Error al cargar próximas tutorías: " . $e->getMessage());
    $proximas_tutorias = [];
    $error_db = "Error al cargar la lista de tutorías. Intente más tarde.";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Tutorías - Tutor</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>

                <div class="container-fluid px-4">
                    <h1 class="mt-4">Tutorías Confirmadas y Historial Reciente</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Próximas y Pendientes</li>
                    </ol>
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje']) ?> alert-dismissible fade show position-relative"
                            style="z-index: 1100;" role="alert">
                            <?= htmlspecialchars($_SESSION['mensaje']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php
                        // Limpiar las variables de sesión
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo_mensaje']);
                        ?>
                    <?php endif; ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-calendar-check me-1"></i>
                            Gestión de Sesiones (Próximas, Pendientes de Cierre y Recientes)
                        </div>
                        <div class="card-body">
                            <?php if (isset($error_db)): ?>
                                <div class="alert alert-danger"><?= $error_db ?></div>
                            <?php endif; ?>

                            <?php if (count($proximas_tutorias) > 0): ?>
                                <div class="table-responsive">
                                    <table id="datatablesSimple" class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Materia</th>
                                                <th>Estudiante</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Costo</th>
                                                <th>Estado</th>
                                                <th data-sortable="false">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($proximas_tutorias as $t):
                                                // Lógica de Tiempos
                                                $timestamp_inicio = strtotime($t['fecha'] . ' ' . $t['hora_inicio']);
                                                $timestamp_fin = strtotime($t['fecha'] . ' ' . $t['hora_fin']);
                                                $sesion_ya_paso = ($timestamp_fin < time());

                                                // Lógica para el formato de duración (se mantiene)
                                                $duracion_horas = floatval($t['duracion']);
                                                if ($duracion_horas < 1 && $duracion_horas > 0) {
                                                    $duracion_str = ($duracion_horas * 60) . ' min.';
                                                } elseif ($duracion_horas >= 1) {
                                                    $duracion_str = rtrim(rtrim(number_format($duracion_horas, 2), '0'), '.') . ' hrs.';
                                                } else {
                                                    $duracion_str = 'N/A';
                                                }
                                                
                                                // Lógica de Estado (simplificada y centralizada para la tabla)
                                                $badge_class = 'bg-primary';
                                                $estado_texto = 'PRÓXIMA';
                                                if ($t['estado'] === 'COMPLETADA') {
                                                    $badge_class = 'bg-success';
                                                    $estado_texto = 'COMPLETADA';
                                                } elseif ($sesion_ya_paso && $t['estado'] === 'CONFIRMADA') {
                                                    $badge_class = 'bg-warning text-dark';
                                                    $estado_texto = 'PENDIENTE DE CIERRE';
                                                }
                                                ?>
                                                <tr>
                                                    <td>#<?= htmlspecialchars($t['solicitud_id']) ?></td>
                                                    <td><?= htmlspecialchars($t['materia']) ?></td>
                                                    <td><?= htmlspecialchars($t['estudiante']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                                                    <td><?= date('h:i A', $timestamp_inicio) ?></td>
                                                    <td class="text-success fw-bold">
                                                        $<?= number_format($t['precio_total'], 2) ?></td>
                                                    <td>
                                                        <span class="badge <?= $badge_class ?>"><?= $estado_texto ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($t['estado'] === 'CONFIRMADA'): ?>
                                                            <a href="sala_virtual.php?id=<?= $t['solicitud_id'] ?>"
                                                                class="btn btn-sm btn-primary me-2"
                                                                title="Ir a la Sala de Videoconferencia">
                                                                <i class="fas fa-video"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($t['estado'] === 'CONFIRMADA'): ?>
                                                            <button type="button" class="btn btn-sm btn-success me-2"
                                                                title="Finalizar Sesión Manualmente" data-bs-toggle="modal"
                                                                data-bs-target="#modalGenericoFinalizar"
                                                                data-solicitud-id="<?= htmlspecialchars($t['solicitud_id']) ?>"
                                                                data-materia="<?= htmlspecialchars($t['materia']) ?>"
                                                                data-estudiante="<?= htmlspecialchars($t['estudiante']) ?>"
                                                                data-fecha="<?= date('d/m/Y', strtotime($t['fecha'])) ?>">
                                                                <i class="fas fa-check-circle"></i> Finalizar
                                                            </button>
                                                        <?php endif; ?>

                                                        <button type="button" class="btn btn-sm btn-info me-2"
                                                            title="Ver Detalles de la Reserva" data-bs-toggle="modal"
                                                            data-bs-target="#modalGenericoDetalle"
                                                            data-solicitud-id="<?= htmlspecialchars($t['solicitud_id']) ?>"
                                                            data-materia="<?= htmlspecialchars($t['materia']) ?>"
                                                            data-estudiante="<?= htmlspecialchars($t['estudiante']) ?>"
                                                            data-fecha="<?= date('d/m/Y', strtotime($t['fecha'])) ?>"
                                                            data-hora-inicio="<?= date('h:i A', $timestamp_inicio) ?>"
                                                            data-hora-fin="<?= date('h:i A', $timestamp_fin) ?>"
                                                            data-duracion="<?= htmlspecialchars($duracion_str) ?>"
                                                            data-costo="$<?= number_format($t['precio_total'], 2) ?>"
                                                            data-estado-texto="<?= htmlspecialchars($estado_texto) ?>"
                                                            data-estado-clase="<?= htmlspecialchars($badge_class) ?>"
                                                            data-sesion-paso="<?= $sesion_ya_paso ? 'true' : 'false' ?>"
                                                            data-estado-raw="<?= htmlspecialchars($t['estado']) ?>">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>

                                                        <?php if ($t['estado'] === 'CONFIRMADA'): ?>
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                title="Cancelar Tutoría" data-bs-toggle="modal"
                                                                data-bs-target="#modalGenericoCancelar"
                                                                data-solicitud-id="<?= htmlspecialchars($t['solicitud_id']) ?>"
                                                                data-materia="<?= htmlspecialchars($t['materia']) ?>"
                                                                data-estudiante="<?= htmlspecialchars($t['estudiante']) ?>"
                                                                data-fecha="<?= date('d/m/Y', strtotime($t['fecha'])) ?>">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($t['estado'] === 'COMPLETADA'): ?>
                                                            <span class="text-success me-2" title="Tutoría Finalizada"><i
                                                                    class="fas fa-check-double fa-lg"></i></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    🗓️ No tienes tutorías confirmadas programadas ni historial reciente.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="modalGenericoFinalizar" tabindex="-1" aria-labelledby="modalGenericoFinalizarLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formFinalizar" method="POST" action="procesar_cierre_tutoria.php">
                    <input type="hidden" name="solicitud_id" id="finalizar_solicitud_id">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="modalGenericoFinalizarLabel">Finalizar Tutoría <span
                                id="finalizar_id_display"></span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirma que la tutoría de
                            <span id="finalizar_materia"></span> con
                            <span id="finalizar_estudiante"></span> ha sido completada
                            (<span id="finalizar_fecha"></span>).</p>
                        <p class="small text-danger">Al confirmar, el estado cambiará a COMPLETADA y se habilitará la
                            calificación para el estudiante.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Finalización</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGenericoCancelar" tabindex="-1" aria-labelledby="modalGenericoCancelarLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formCancelar" method="POST" action="cancelar_tutor_accion.php">
                    <input type="hidden" name="solicitud_id" id="cancelar_solicitud_id">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalGenericoCancelarLabel">Confirmar Cancelación <span
                                id="cancelar_id_display"></span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas CANCELAR la tutoría de
                            <span id="cancelar_materia"></span> con
                            <span id="cancelar_estudiante"></span>
                            (<span id="cancelar_fecha"></span>)?</p>
                        <p class="small text-danger">Esta acción no se puede deshacer y el estudiante recibirá una
                            notificación de cancelación.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Volver</button>
                        <button type="submit" class="btn btn-danger">Sí, Cancelar Tutoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modalGenericoDetalle" tabindex="-1" aria-labelledby="modalGenericoDetalleLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalGenericoDetalleLabel">Detalle de la Tutoría <span
                            id="detalle_id_display"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 class="text-primary mb-3" id="detalle_materia"></h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-user-graduate me-2 text-muted"></i>
                            <strong>Estudiante:</strong>
                            <span class="float-end" id="detalle_estudiante"></span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-calendar-day me-2 text-muted"></i>
                            <strong>Fecha:</strong>
                            <span class="float-end" id="detalle_fecha"></span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-clock me-2 text-muted"></i>
                            <strong>Hora:</strong>
                            <span class="float-end">
                                <span id="detalle_hora_inicio"></span> - <span id="detalle_hora_fin"></span>
                            </span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-hourglass-half me-2 text-muted"></i>
                            <strong>Duración:</strong>
                            <span class="float-end" id="detalle_duracion"></span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-money-bill-wave me-2 text-muted"></i>
                            <strong>Costo:</strong>
                            <span class="float-end text-success fw-bold" id="detalle_costo"></span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-info-circle me-2 text-muted"></i>
                            <strong>Estado:</strong>
                            <span class="float-end">
                                <span class="badge" id="detalle_estado_badge"></span>
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="#" id="detalle_link_sala_virtual" class="btn btn-primary" title="Unirse a la sala virtual">
                        <i class="fas fa-video"></i> Ir a Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script src="js/scripts.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Lógica para el Modal Finalizar
            var finalizarModal = document.getElementById('modalGenericoFinalizar');
            finalizarModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-solicitud-id');
                var materia = button.getAttribute('data-materia');
                var estudiante = button.getAttribute('data-estudiante');
                var fecha = button.getAttribute('data-fecha');

                var modalTitle = finalizarModal.querySelector('.modal-title #finalizar_id_display');
                var modalBodyMateria = finalizarModal.querySelector('.modal-body #finalizar_materia');
                var modalBodyEstudiante = finalizarModal.querySelector('.modal-body #finalizar_estudiante');
                var modalBodyFecha = finalizarModal.querySelector('.modal-body #finalizar_fecha');
                var modalFormInput = finalizarModal.querySelector('.modal-content form #finalizar_solicitud_id');

                modalTitle.textContent = '#' + id;
                modalBodyMateria.textContent = materia;
                modalBodyEstudiante.textContent = estudiante;
                modalBodyFecha.textContent = fecha;
                modalFormInput.value = id;
            });

            // Lógica para el Modal Cancelar
            var cancelarModal = document.getElementById('modalGenericoCancelar');
            cancelarModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-solicitud-id');
                var materia = button.getAttribute('data-materia');
                var estudiante = button.getAttribute('data-estudiante');
                var fecha = button.getAttribute('data-fecha');

                var modalTitle = cancelarModal.querySelector('.modal-title #cancelar_id_display');
                var modalBodyMateria = cancelarModal.querySelector('.modal-body #cancelar_materia');
                var modalBodyEstudiante = cancelarModal.querySelector('.modal-body #cancelar_estudiante');
                var modalBodyFecha = cancelarModal.querySelector('.modal-body #cancelar_fecha');
                var modalFormInput = cancelarModal.querySelector('.modal-content form #cancelar_solicitud_id');

                modalTitle.textContent = '#' + id;
                modalBodyMateria.textContent = materia;
                modalBodyEstudiante.textContent = estudiante;
                modalBodyFecha.textContent = fecha;
                modalFormInput.value = id;
            });

            // Lógica para el Modal Detalle
            var detalleModal = document.getElementById('modalGenericoDetalle');
            detalleModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-solicitud-id');
                var materia = button.getAttribute('data-materia');
                var estudiante = button.getAttribute('data-estudiante');
                var fecha = button.getAttribute('data-fecha');
                var horaInicio = button.getAttribute('data-hora-inicio');
                var horaFin = button.getAttribute('data-hora-fin');
                var duracion = button.getAttribute('data-duracion');
                var costo = button.getAttribute('data-costo');
                var estadoTexto = button.getAttribute('data-estado-texto');
                var estadoClase = button.getAttribute('data-estado-clase');
                var sesionPaso = button.getAttribute('data-sesion-paso') === 'true';
                var estadoRaw = button.getAttribute('data-estado-raw');

                // Inyectar datos
                document.getElementById('detalle_id_display').textContent = '#' + id;
                document.getElementById('detalle_materia').textContent = materia;
                document.getElementById('detalle_estudiante').textContent = estudiante;
                document.getElementById('detalle_fecha').textContent = fecha;
                document.getElementById('detalle_hora_inicio').textContent = horaInicio;
                document.getElementById('detalle_hora_fin').textContent = horaFin;
                document.getElementById('detalle_duracion').textContent = duracion;
                document.getElementById('detalle_costo').textContent = costo;

                // Estado (Badge)
                var estadoBadge = document.getElementById('detalle_estado_badge');
                estadoBadge.textContent = estadoTexto;
                estadoBadge.className = 'badge ' + estadoClase;

                // Botón "Ir a Sesión"
                var linkSalaVirtual = document.getElementById('detalle_link_sala_virtual');
                
                // Mostrar/Ocultar y configurar el enlace solo si es 'CONFIRMADA' y NO ha pasado.
                if (estadoRaw === 'CONFIRMADA' && !sesionPaso) {
                    linkSalaVirtual.href = 'sala_virtual.php';
                    linkSalaVirtual.style.display = 'inline-block';
                } else {
                    linkSalaVirtual.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>