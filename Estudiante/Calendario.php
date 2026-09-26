<?php
// Incluir la configuración de la sesión y la navegación
include 'Includes/Nav.php';

// 1. VERIFICACIÓN DE SESIÓN (Estudiante)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}
$id_estudiante = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Mi Calendario de Tutorías</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />

</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Mi Calendario de Tutorías</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Calendario</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="far fa-calendar-alt me-1"></i>
                            Tutorías Agendadas y Confirmadas
                        </div>
                        <div class="card-body">
                            <div id='calendar'></div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <?php include 'Includes/Footer.php'; ?>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="eventModalLabel">Detalles de la Tutoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Materia:</strong> <span id="modalMateria"></span></p>
                    <p><strong>Tutor:</strong> <span id="modalTutor"></span></p>
                    <p><strong>Fecha:</strong> <span id="modalFecha"></span></p>
                    <p><strong>Hora de Inicio:</strong> <span id="modalHoraInicio"></span></p>
                    <p><strong>Hora de Fin:</strong> <span id="modalHoraFin"></span></p>
                    <p><strong>Duración:</strong> <span id="modalDuracion"></span></p>
                    <p><strong>Estado:</strong> <span id="modalEstado" class="badge bg-success"></span></p>
                </div>
                <div class="modal-footer">
                    <a href="#" id="modalLinkSolicitud" class="btn btn-info" style="display:none;">Ver Solicitud</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js'></script>

    <script>
        $(document).ready(function () {
            var calendarEl = document.getElementById('calendar');

            if (calendarEl && typeof FullCalendar !== 'undefined') {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    // ... (configuración) ...
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek'
                    },
                    editable: false,
                    selectable: true,
                    events: 'obtener_tutorias_calendario.php',

                    eventClick: function (info) {

                        var event = info.event;
                        var start = event.start;
                        var end = event.end;

                        // Función robusta para formatear la hora (Maneja el caso null de 'end')
                        var formatTime = function (dateObj) {
                            if (!dateObj) return 'N/A';
                            return dateObj.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                        };

                        var startTime = formatTime(start);
                        var endTime = formatTime(end);

                        // Inyectar datos en el Modal
                        $('#modalMateria').text(event.title);
                        $('#modalTutor').text(event.extendedProps.tutor);
                        $('#modalFecha').text(start.toLocaleDateString('es-ES'));
                        $('#modalHoraInicio').text(startTime);
                        $('#modalHoraFin').text(endTime);
                        $('#modalDuracion').text(event.extendedProps.duracion + ' horas');

                        // Manejar el estado y color del badge (CORREGIDO)
                        var estado = event.extendedProps.estado || 'CONFIRMADA';
                        // 🟢 FINAL: COMPLETADA = VERDE (bg-success); CONFIRMADA = AZUL FUERTE (bg-primary) 🟢
                        $('#modalEstado').text(estado).removeClass('bg-success bg-secondary bg-info bg-primary').addClass(
                            // Si está COMPLETADA, es verde. Si no, es azul primario.
                            estado === 'COMPLETADA' ? 'bg-success' : 'bg-primary'
                        );


                        // Enlace opcional a la solicitud
                        var linkUrl = 'MisSolicitudes.php?id=' + event.id;
                        $('#modalLinkSolicitud').attr('href', linkUrl).show();

                        // Mostrar el modal
                        var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
                        eventModal.show();
                    }
                });

                calendar.render();
            } else {
                console.error("El contenedor del calendario no se encontró o FullCalendar no cargó correctamente.");
            }
        });
    </script>
</body>

</html>