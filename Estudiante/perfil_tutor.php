<?php
include 'Includes/Nav.php';

// 1. Validar que se ha recibido el ID del Tutor (tutor_id)
if (!isset($_GET['tutor_id']) || !is_numeric($_GET['tutor_id'])) {
    // Redirigir si no hay ID válido
    header('Location: BuscarTutor.php?error=tutor_invalido');
    exit;
}

$tutor_id = $_GET['tutor_id'];

//obtener fecha php
// --- Nueva Función PHP para obtener la fecha de la próxima ocurrencia del día ---
/**
 * Calcula la fecha real de la próxima ocurrencia de un día de la semana (LUNES, MARTES, etc.).
 * @param string $dayName El nombre del día de la semana en mayúsculas (LUNES, MARTES...).
 * @return string La fecha en formato YYYY-MM-DD.
 */
function getNextDateForDayPHP($dayName)
{
    // Definimos el mapeo de días de la semana de PHP (1 = Lunes, 7 = Domingo)
    $day_map = [
        'LUNES' => 1,
        'MARTES' => 2,
        'MIÉRCOLES' => 3,
        'JUEVES' => 4,
        'VIERNES' => 5,
        'SÁBADO' => 6,
        'DOMINGO' => 7
    ];

    if (!isset($day_map[$dayName])) {
        return '';
    }

    $targetDay = $day_map[$dayName];
    $currentDay = (int) date('N'); // Día actual (1=Lunes, 7=Domingo)

    // Calculamos la diferencia
    $diff = $targetDay - $currentDay;

    // Si el día ya pasó esta semana, sumamos 7 días para ir a la próxima semana
    if ($diff <= 0) {
        $diff += 7;
    }

    // Calculamos la fecha real sumando la diferencia de días a la fecha actual
    $nextDate = date('Y-m-d', strtotime("+$diff days"));

    return $nextDate;
}
// -----------------------------------------------------------------------------

// 2. Consulta para obtener todos los detalles del tutor
try {
    $sql_tutor = "
        SELECT 
            u.id AS tutor_id,
            u.nombre AS nombre_tutor, 
            u.apellido AS apellido_tutor, 
            u.perfil_imagen, 
            u.universidad_tutor, 
            u.modalidad_tutor,
            u.descripcion,
            
            /* Obtener la materia con el precio más bajo (la mejor oferta) */
            (
                SELECT MIN(ot.precio_hora) 
                FROM ofertas_tutorias ot 
                WHERE ot.id_tutor = u.id AND ot.activo = 1 
            ) AS precio_minimo,
            
            /* Obtener todas las materias que imparte el tutor */
            (
                SELECT GROUP_CONCAT(DISTINCT m_all.nombre_materia SEPARATOR ', ')
                FROM materias m_all
                WHERE EXISTS (
                    SELECT 1 
                    FROM ofertas_tutorias ot_all 
                    /* Incluye el filtro activo = 1 para que solo muestre materias con ofertas activas */
                    WHERE ot_all.id_materia = m_all.id AND ot_all.id_tutor = u.id AND ot_all.activo = 1 
                )
                OR EXISTS (
                    SELECT 1 
                    FROM tutor_materias tm 
                    WHERE tm.id_materia = m_all.id AND tm.id_tutor = u.id
                )
            ) AS todas_las_materias
            
        FROM 
            usuarios u
        WHERE 
            u.id = :tutor_id AND u.rol = 'Tutor'";

    $stmt_tutor = $conn->prepare($sql_tutor);
    $stmt_tutor->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_tutor->execute();
    $tutor_detalle = $stmt_tutor->fetch(PDO::FETCH_ASSOC);

    // Si el tutor no existe, redirigir
    if (!$tutor_detalle) {
        header('Location: BuscarTutor.php?error=tutor_noexiste');
        exit;
    }

    // 3. Obtener la disponibilidad horaria semanal del tutor
    $sql_disponibilidad = "
        SELECT 
            dia_semana, 
            hora_inicio, 
            hora_fin 
        FROM 
            disponibilidad 
        WHERE 
            id_tutor = :tutor_id
        ORDER BY 
            FIELD(dia_semana, 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'), hora_inicio";

    $stmt_disp = $conn->prepare($sql_disponibilidad);
    $stmt_disp->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_disp->execute();
    $disponibilidad = $stmt_disp->fetchAll(PDO::FETCH_ASSOC);

    // Formatear la disponibilidad por día de la semana
    $horario_semanal = [
        'LUNES' => [],
        'MARTES' => [],
        'MIÉRCOLES' => [],
        'JUEVES' => [],
        'VIERNES' => [],
        'SÁBADO' => [],
        'DOMINGO' => []
    ];

    foreach ($disponibilidad as $bloque) {
        $horario_semanal[$bloque['dia_semana']][] = [
            'inicio' => date('H:i', strtotime($bloque['hora_inicio'])),
            'fin' => date('H:i', strtotime($bloque['hora_fin'])),
        ];
    }

} catch (PDOException $e) {
    die("Error al cargar los detalles del tutor: " . $e->getMessage());
}

// 4. Obtener las ofertas de tutoría activas de forma individual
try {
    $sql_ofertas = "
        SELECT 
            ot.id AS oferta_id,
            ot.precio_hora,
            m.nombre_materia
        FROM 
            ofertas_tutorias ot
        JOIN 
            materias m ON ot.id_materia = m.id
        WHERE 
            ot.id_tutor = :tutor_id AND ot.activo = 1
        ORDER BY 
            m.nombre_materia";

    $stmt_ofertas = $conn->prepare($sql_ofertas);
    $stmt_ofertas->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_ofertas->execute();
    $ofertas_activas = $stmt_ofertas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar las ofertas de tutoría: " . $e->getMessage());
}

// Determinar la ruta de la imagen del tutor
$ruta_imagen = !empty($tutor_detalle['perfil_imagen']) ?
    '../' . htmlspecialchars($tutor_detalle['perfil_imagen']) :
    '../Assets/perfil_default.png';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Perfil de Tutor | Agendar Tutoria</title>
    <link href="css/styles.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Perfil de Tutor</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="BuscarTutor.php">Buscar Tutores</a></li>
                        <li class="breadcrumb-item active">
                            <?= htmlspecialchars($tutor_detalle['nombre_tutor'] . ' ' . $tutor_detalle['apellido_tutor']) ?>
                        </li>
                    </ol>

                    <div class="row">

                        <div class="col-lg-4">

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-primary text-white text-center">
                                    <h5 class="mb-0">Datos del Tutor</h5>
                                </div>
                                <div class="card-body text-center">
                                    <img src="<?= $ruta_imagen ?>" alt="Foto de perfil"
                                        class="rounded-circle mb-3 border border-3"
                                        style="width: 120px; height: 120px; object-fit: cover;">
                                    <h4 class="mb-1">
                                        <?= htmlspecialchars($tutor_detalle['nombre_tutor'] . ' ' . $tutor_detalle['apellido_tutor']) ?>
                                    </h4>
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-graduation-cap me-1"></i>
                                        <?= htmlspecialchars($tutor_detalle['universidad_tutor'] ?? 'Universidad no especificada') ?>
                                    </p>

                                    <div class="border-top pt-3">
                                        <p class="mb-1 text-start">
                                            <i class="fas fa-tags text-info me-2"></i>
                                            <strong>Precio Mínimo:</strong>
                                            <span
                                                class="fw-bold text-success">$<?= number_format($tutor_detalle['precio_minimo'] ?? 0, 2) ?>/h</span>
                                        </p>
                                        <p class="mb-1 text-start">
                                            <i class="fas fa-map-marker-alt text-warning me-2"></i>
                                            <strong>Modalidad:</strong>
                                            <?= htmlspecialchars($tutor_detalle['modalidad_tutor']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="card-footer bg-light">
                                    <h6 class="mb-2 text-primary"><i class="fas fa-user-circle me-1"></i> Sobre Mí</h6>
                                    <p class="card-text small text-start mb-0">
                                        <?= nl2br(htmlspecialchars($tutor_detalle['descripcion'] ?? 'El tutor no ha proporcionado una descripción detallada.')) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <i class="fas fa-book me-1"></i> Materias Impartidas
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($tutor_detalle['todas_las_materias'])): ?>
                                        <p class="card-text">
                                            <?= nl2br(htmlspecialchars($tutor_detalle['todas_las_materias'])) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">No hay materias activas listadas por este tutor.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-calendar-alt me-1"></i> Disponibilidad Horaria Semanal
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Día</th>
                                                <th>Horario Disponible</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Esta lógica PHP debe estar definida antes en el archivo para que funcione
                                            $dias_espanol = ['LUNES' => 'Lunes', 'MARTES' => 'Martes', 'MIÉRCOLES' => 'Miércoles', 'JUEVES' => 'Jueves', 'VIERNES' => 'Viernes', 'SÁBADO' => 'Sábado', 'DOMINGO' => 'Domingo'];
                                            foreach ($horario_semanal as $dia => $bloques):
                                                ?>
                                                <tr>
                                                    <td class="fw-bold"><?= $dias_espanol[$dia] ?></td>
                                                    <td>
                                                        <?php if (!empty($bloques)): ?>
                                                            <?php foreach ($bloques as $bloque): ?>
                                                                <span class="badge bg-success me-1">
                                                                    <?= $bloque['inicio'] ?> - <?= $bloque['fin'] ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No Disponible</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <i class="fas fa-calendar-check me-1"></i> Agendar Tutoría
                                </div>
                                <div class="card-body">

                                    <div class="mb-4">
                                        <label for="materia_seleccionada" class="form-label fw-bold">1. Selecciona la
                                            Materia:</label>
                                        <select class="form-select" id="materia_seleccionada" required>
                                            <option value="" disabled selected>Seleccione una materia</option>
                                            <?php if (!empty($ofertas_activas)): ?>
                                                <?php foreach ($ofertas_activas as $oferta): ?>
                                                    <option value="<?= htmlspecialchars($oferta['oferta_id']) ?>"
                                                        data-precio="<?= htmlspecialchars($oferta['precio_hora']) ?>"
                                                        data-nombre="<?= htmlspecialchars($oferta['nombre_materia']) ?>">
                                                        <?= htmlspecialchars($oferta['nombre_materia']) ?>
                                                        ($<?= number_format($oferta['precio_hora'], 2) ?>/h)
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="" disabled>El tutor no tiene ofertas activas.</option>
                                            <?php endif; ?>
                                        </select>
                                        <small class="form-text text-muted" id="info_precio_materia">
                                            *Selecciona la materia para ver los precios y habilitar la reserva.
                                        </small>
                                    </div>

                                    <label class="form-label fw-bold mt-3">2. Selecciona un Bloque Horario:</label>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm text-center align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Lunes</th>
                                                    <th>Martes</th>
                                                    <th>Miércoles</th>
                                                    <th>Jueves</th>
                                                    <th>Viernes</th>
                                                    <th>Sábado</th>
                                                    <th>Domingo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Definimos los días de la semana y las horas que queremos mostrar
                                                $dias_semana_num = [1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO', 7 => 'DOMINGO'];
                                                $horas_del_dia = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];

                                                foreach ($horas_del_dia as $hora_bloque): ?>
                                                    <tr>
                                                        <th class="table-light small"><?= $hora_bloque ?></th>
                                                        <?php foreach ($dias_semana_num as $dia_num => $dia_nombre): ?>
                                                            <td>
                                                                <?php
                                                                $es_disponible = false;
                                                                $hora_fin_bloque = date('H:i', strtotime($hora_bloque . ' +1 hour'));
                                                                $today = strftime('%A'); // Usaremos esto para filtrar horarios pasados en el día actual
                                                        
                                                                if (isset($horario_semanal[$dia_nombre])) {
                                                                    foreach ($horario_semanal[$dia_nombre] as $slot) {
                                                                        if ($hora_bloque >= $slot['inicio'] && $hora_fin_bloque <= $slot['fin']) {
                                                                            $es_disponible = true;
                                                                            break;
                                                                        }
                                                                    }
                                                                }

                                                                if ($es_disponible): ?>
                                                                    <button class="btn btn-success btn-sm btn-reserva"
                                                                        data-dia-num="<?= $dia_num ?>"
                                                                        data-dia-nombre="<?= $dia_nombre ?>"
                                                                        data-hora-inicio="<?= $hora_bloque ?>"
                                                                        data-hora-fin="<?= $hora_fin_bloque ?>"
                                                                        onclick="abrirModalReserva(this)" disabled>
                                                                        <i class="fas fa-clock"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span class="text-muted small">―</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div class="modal fade" id="modalReserva" tabindex="-1" aria-labelledby="modalReservaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form_solicitud" action="procesar_agendamiento.php" method="POST">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="modalReservaLabel"><i class="fas fa-clipboard-check me-1"></i>
                            Confirmar Solicitud</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Confirma los detalles de tu tutoría:</p>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item">**Tutor:**
                                <?= htmlspecialchars($tutor_detalle['nombre_tutor'] . ' ' . $tutor_detalle['apellido_tutor']) ?>
                            </li>
                            <li class="list-group-item">Materia: <strong id="modal_materia_nombre"></strong></li>
                            <li class="list-group-item">Día Solicitado: <strong id="modal_dia_solicitado"></strong>
                            </li>
                            <li class="list-group-item">Hora de Inicio: <strong id="modal_hora_inicio"></strong>
                            </li>
                        </ul>

                        <div class="mb-3 border p-3 rounded bg-light">
                            <label for="duracion_horas_input" class="form-label fw-bold mb-1">Duración (horas):</label>
                            <select class="form-select" id="duracion_horas_input" name="duracion_horas">
                                <option value="1.0">1.0 Hora</option>
                                <option value="1.5">1.5 Horas</option>
                                <option value="2.0">2.0 Horas</option>
                                <option value="2.5">2.5 Horas</option>
                                <option value="3.0">3.0 Horas</option>
                            </select>
                            <small class="text-muted mt-2 d-block">La hora de fin se recalculará
                                automáticamente.</small>
                        </div>

                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item">**Hora de Fin Estimada:** <strong id="modal_hora_fin"></strong>
                            </li>
                            <li class="list-group-item">**Precio Total:** <strong id="modal_precio_final"
                                    class="text-success"></strong></li>
                        </ul>

                        <p class="alert alert-info small mt-3">
                            Tu solicitud se registrará como **PENDIENTE** y el tutor deberá aprobarla.
                        </p>

                        <input type="hidden" name="tutor_id" value="<?= $tutor_id ?>">
                        <input type="hidden" name="oferta_id" id="input_oferta_id">
                        <input type="hidden" name="precio_total_calculado" id="input_precio_total_calculado">

                        <input type="hidden" name="fecha" id="input_fecha_solicitada">
                        <input type="hidden" name="hora" id="input_hora_inicio">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Enviar Solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Mapeo para convertir el día de la semana (LUNES, MARTES...) a texto en español para el Modal
        const dias_espanol = {
            'LUNES': 'Lunes',
            'MARTES': 'Martes',
            'MIÉRCOLES': 'Miércoles',
            'JUEVES': 'Jueves',
            'VIERNES': 'Viernes',
            'SÁBADO': 'Sábado',
            'DOMINGO': 'Domingo'
        };

        // Variables globales para mantener el estado de la reserva seleccionada
        let precio_hora_base = 0;
        let hora_inicio_seleccionada = '';

        // Función para calcular la fecha real de la próxima ocurrencia del día de la semana
        function getNextDateForDay(dayName) {
            const dias = ['DOMINGO', 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO'];
            const targetDayIndex = dias.indexOf(dayName);
            if (targetDayIndex === -1) return null;

            const today = new Date();
            const todayIndex = today.getDay(); // 0 (Dom) a 6 (Sab)

            let diff = targetDayIndex - todayIndex;
            if (diff <= 0) {
                diff += 7; // Si el día ya pasó esta semana, se va a la próxima semana
            }

            const nextDate = new Date(today);
            nextDate.setDate(today.getDate() + diff);

            // Formato YYYY-MM-DD
            const year = nextDate.getFullYear();
            const month = String(nextDate.getMonth() + 1).padStart(2, '0');
            const day = String(nextDate.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Función principal para actualizar el precio y la hora de fin en el modal
        function actualizarDetallesReserva() {
            const duracionSelect = document.getElementById('duracion_horas_input');
            const duracion = parseFloat(duracionSelect.value);

            // 1. Recálculo del Precio Total
            const precioTotal = precio_hora_base * duracion;
            document.getElementById('modal_precio_final').textContent = `$${precioTotal.toFixed(2)} USD`;
            document.getElementById('input_precio_total_calculado').value = precioTotal.toFixed(2);

            // 2. Recálculo de la Hora de Fin
            if (hora_inicio_seleccionada) {
                // Se calcula la hora de fin sumando la duración (en minutos) a la hora de inicio
                const [horas, minutos] = hora_inicio_seleccionada.split(':').map(Number);
                const duracionMinutos = duracion * 60;

                const totalMinutos = (horas * 60) + minutos + duracionMinutos;

                const finHoras = Math.floor(totalMinutos / 60) % 24; // El modulo 24 es por si pasa de medianoche
                const finMinutos = totalMinutos % 60;

                const horaFinFormateada =
                    String(finHoras).padStart(2, '0') + ':' +
                    String(finMinutos).padStart(2, '0');

                document.getElementById('modal_hora_fin').textContent = horaFinFormateada;
            }

            // TODO Opcional: Implementar una validación aquí (o en el backend) para ver si 
            // la hora de fin calculada sigue dentro de la disponibilidad del tutor. 
        }

        // -----------------------------------------------------

        function abrirModalReserva(boton) {
            const materiaSelect = document.getElementById('materia_seleccionada');

            if (!materiaSelect.value) {
                alert("¡ERROR! Primero debes seleccionar una materia para poder reservar.");
                return;
            }

            // Resetear la duración a 1.0 al abrir el modal para un nuevo slot
            document.getElementById('duracion_horas_input').value = '1.0';

            // 1. Obtener datos del botón y el selector
            const diaNombre = boton.getAttribute('data-dia-nombre');
            const horaInicio = boton.getAttribute('data-hora-inicio');

            const selectedOption = materiaSelect.options[materiaSelect.selectedIndex];
            const ofertaID = selectedOption.value;

            // Asignar precio base y hora de inicio a las variables globales
            precio_hora_base = parseFloat(selectedOption.getAttribute('data-precio'));
            hora_inicio_seleccionada = horaInicio;

            const nombreMateria = selectedOption.getAttribute('data-nombre');

            // 2. Calcular la FECHA REAL (la próxima ocurrencia de ese día)
            const fechaReal = getNextDateForDay(diaNombre);

            // 3. Rellenar el Modal con datos fijos del slot
            document.getElementById('modal_materia_nombre').textContent = nombreMateria;
            document.getElementById('modal_dia_solicitado').textContent = `${dias_espanol[diaNombre]} (${fechaReal})`;
            document.getElementById('modal_hora_inicio').textContent = horaInicio;

            // 4. Rellenar Campos Ocultos del Formulario (para el backend)
            document.getElementById('input_oferta_id').value = ofertaID;
            document.getElementById('input_fecha_solicitada').value = fechaReal;
            document.getElementById('input_hora_inicio').value = horaInicio;

            // 5. Llamar a la función de actualización inicial para rellenar precio y hora de fin
            actualizarDetallesReserva();

            // 6. Asignar el listener al selector de duración
            document.getElementById('duracion_horas_input').onchange = actualizarDetallesReserva;

            // Mostrar el modal
            const reservaModal = new bootstrap.Modal(document.getElementById('modalReserva'));
            reservaModal.show();
        }

        // ... mantener la función que se llama al cambiar la materia (addEventListener de materia_seleccionada) ...
        document.getElementById('materia_seleccionada').addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const precio = selectedOption.getAttribute('data-precio');
            const infoDiv = document.getElementById('info_precio_materia');

            if (precio) {
                infoDiv.innerHTML = `*Precio base: **$${parseFloat(precio).toFixed(2)} USD** por hora. Ahora puedes seleccionar un horario.`;
                infoDiv.classList.remove('text-muted');
                infoDiv.classList.add('text-success');

                // Habilitar todos los botones de reserva
                document.querySelectorAll('.btn-reserva').forEach(btn => {
                    btn.disabled = false;
                });

            } else {
                infoDiv.innerHTML = `*Selecciona la materia para ver los precios y habilitar la reserva.`;
                infoDiv.classList.add('text-muted');
                infoDiv.classList.remove('text-success');

                // Deshabilitar todos los botones de reserva
                document.querySelectorAll('.btn-reserva').forEach(btn => {
                    btn.disabled = true;
                });
            }
        });

        // Deshabilitar botones al cargar la página si no hay materia seleccionada
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.btn-reserva').forEach(btn => {
                btn.disabled = true;
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>

</html>