<?php
include 'Includes/Nav.php';

// 1. Verificación de Seguridad
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];
// Incluir la conexión a la base de datos (ajusta la ruta si es necesario)
include "../Includes/db.php"; 


// 2. VALIDACIÓN DEL ID DE SOLICITUD
$solicitud_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($solicitud_id === 0) {
    $_SESSION['mensaje'] = "Error: ID de tutoría inválido. No se puede acceder a la sala.";
    $_SESSION['tipo_mensaje'] = "danger";
    // *** PUNTO DE REDIRECCIÓN (Falla 1) ***
    header("Location: proximas_tutorias.php");
    exit();
}

// 3. CONSULTA SEGURA DE LA TUTORÍA (Verificación de Propiedad y Existencia)
// Se trae el enlace de la sala (link_sala_virtual), si existe.
$sql = "
    SELECT 
        s.*, 
        s.link_sala_virtual, -- AHORA SELECCIONAMOS EL CAMPO DESDE LA TABLA SOLICITUDES
        m.nombre_materia AS materia,
        CONCAT(e.nombre, ' ', e.apellido) AS estudiante 
    FROM solicitudes_tutorias s
    JOIN usuarios e ON s.id_estudiante = e.id
    JOIN ofertas_tutorias ot ON s.id_oferta = ot.id -- La oferta asociada a la solicitud
    JOIN materias m ON ot.id_materia = m.id
    WHERE s.id = :id 
    AND s.id_tutor = :tutor_id
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $solicitud_id, PDO::PARAM_INT);
    $stmt->bindParam(':tutor_id', $id_tutor, PDO::PARAM_INT);
    $stmt->execute();
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
        //die("Error crítico de DB: " . $e->getMessage() . " | SQLSTATE: " . $e->getCode());

    error_log("Error de DB en sala_virtual: " . $e->getMessage());
    $_SESSION['mensaje'] = "Error del sistema al buscar la tutoría.";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: proximas_tutorias.php");
    exit();
}

// 4. VERIFICACIÓN DE PROPIEDAD
if (!$solicitud) { 
    $_SESSION['mensaje'] = "Tutoría #{$solicitud_id} no encontrada o no te pertenece.";
    $_SESSION['tipo_mensaje'] = "danger";
    // *** PUNTO DE REDIRECCIÓN (Falla 2) ***
    header("Location: proximas_tutorias.php");
    exit();
}

// 5. LÓGICA DE ESTADO Y TIEMPOS

// Define qué estados permiten la operación (modificar enlace, iniciar/finalizar)
$puede_operar = $solicitud['estado'] === 'CONFIRMADA' || $solicitud['estado'] === 'en_progreso';

// Bloquea el acceso si el estado es PENDIENTE, CANCELADA, etc.
if (!$puede_operar && $solicitud['estado'] !== 'COMPLETADA') {
    $_SESSION['mensaje'] = "Acceso denegado. La tutoría #{$solicitud_id} tiene el estado '{$solicitud['estado']}'.";
    $_SESSION['tipo_mensaje'] = "warning";
    // *** PUNTO DE REDIRECCIÓN (Falla 3: El problema más probable que enfrentas si ya pasó Falla 2) ***
    header("Location: Solicitudes.php");
    exit();
}

// Cálculo de tiempos para el botón de acceso (da 5 minutos de margen)
date_default_timezone_set('America/El_Salvador'); 
$timestamp_inicio = strtotime($solicitud['fecha'] . ' ' . $solicitud['hora_inicio']);
$timestamp_fin = strtotime($solicitud['fecha'] . ' ' . $solicitud['hora_fin']);
$hora_actual = time();

$margen_inicio = $timestamp_inicio - (5 * 60); // 5 minutos antes

$sesion_aun_no_empieza = ($hora_actual < $margen_inicio);
$sesion_ya_termino = ($hora_actual > $timestamp_fin);

// Determinar el enlace actual (se asume que el enlace se guarda en la tabla 'ofertas_tutorias'
// pero debe ser el tutor quien lo provea. Usaremos el campo 'link_sala_virtual'
// si la BD lo devuelve, de lo contrario un valor vacío.
$link_actual = $solicitud['link_sala_virtual'] ?? '';

// Definir clases y textos de estado para la interfaz
$estado_clase = 'bg-primary';
$estado_texto = 'CONFIRMADA';
if ($solicitud['estado'] === 'en_progreso') {
    $estado_clase = 'bg-success';
    $estado_texto = 'EN PROGRESO';
} elseif ($solicitud['estado'] === 'COMPLETADA') {
    $estado_clase = 'bg-secondary';
    $estado_texto = 'FINALIZADA';
} elseif ($sesion_ya_termino && $solicitud['estado'] === 'CONFIRMADA') {
    // Si ya terminó pero no se ha cerrado manualmente
    $estado_clase = 'bg-warning text-dark';
    $estado_texto = 'PENDIENTE DE CIERRE';
}

// Lógica de deshabilitar botones
$disabled_btn = $puede_operar ? '' : 'disabled';
$disabled_enlace = ($solicitud['estado'] === 'COMPLETADA' || $solicitud['estado'] === 'CANCELADA') ? 'disabled' : '';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Sala Virtual</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
</head>
<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Sala Virtual #<?= $solicitud_id ?></h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sala Virtual</li>
                    </ol>

                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje'] ?? 'info') ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['mensaje']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo_mensaje']);
                        ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Detalles de la Sesión
                                </div>
                                <div class="card-body">
                                    <h4 class="card-title"><?= htmlspecialchars($solicitud['materia']) ?></h4>
                                    <p class="card-text">
                                        Estudiante: <?= htmlspecialchars($solicitud['estudiante']) ?><br>
                                        Fecha: <?= date('d/m/Y', strtotime($solicitud['fecha'])) ?><br>
                                        Hora: <?= date('h:i A', $timestamp_inicio) ?> - <?= date('h:i A', $timestamp_fin) ?>
                                    </p>
                                    <span class="badge <?= $estado_clase ?> p-2"><?= $estado_texto ?></span>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <i class="fas fa-link me-1"></i>
                                    Configuración del Enlace
                                </div>
                                <div class="card-body">
                                    <?php if ($solicitud['estado'] === 'COMPLETADA' || $solicitud['estado'] === 'CANCELADA'): ?>
                                        <div class="alert alert-info">Esta sesión ha sido **<?= $solicitud['estado'] ?>**. El enlace no se puede modificar.</div>
                                    <?php else: ?>
                                        <form action="guardar_enlace_sala.php" method="POST">
                                            <input type="hidden" name="solicitud_id" value="<?= $solicitud_id ?>">
                                            <div class="mb-3">
                                                <label for="link_sala" class="form-label">Enlace de Videoconferencia (Google Meet, Zoom, etc.)</label>
                                                <input type="url" class="form-control" id="link_sala" name="link_sala" 
                                                       value="<?= htmlspecialchars($link_actual) ?>" 
                                                       placeholder="Ej: https://meet.google.com/abc-xyz" required <?= $disabled_enlace ?>>
                                            </div>
                                            <button type="submit" class="btn btn-success" <?= $disabled_enlace ?>>
                                                <i class="fas fa-save me-1"></i> Guardar Enlace
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-4 text-center">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-play me-1"></i> Acciones
                                </div>
                                <div class="card-body">
                                    <?php if ($link_actual && $solicitud['estado'] !== 'COMPLETADA' && $solicitud['estado'] !== 'CANCELADA'): ?>
                                        
                                        <?php if ($sesion_aun_no_empieza): ?>
                                            <div class="alert alert-warning">La sesión inicia a las <?= date('h:i A', $timestamp_inicio) ?>. Podrás entrar en <?= date('i', $timestamp_inicio - $hora_actual) ?> minutos.</div>
                                            <button type="button" class="btn btn-primary btn-lg w-100" disabled>
                                                <i class="fas fa-clock me-2"></i> Abrir Sala Virtual
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($link_actual) ?>" target="_blank" class="btn btn-primary btn-lg w-100 mb-3" role="button">
                                                <i class="fas fa-video me-2"></i> Abrir Sala Virtual
                                            </a>
                                            <p class="small text-muted">Asegúrate de que la sesión se inicie a la hora acordada.</p>
                                        <?php endif; ?>
                                        
                                    <?php elseif ($link_actual && ($solicitud['estado'] === 'COMPLETADA' || $solicitud['estado'] === 'CANCELADA')): ?>
                                        <button type="button" class="btn btn-primary btn-lg w-100" disabled>
                                            Sesión Finalizada/Cancelada
                                        </button>
                                    <?php else: ?>
                                        <div class="alert alert-danger">Debes guardar el enlace de la sala primero.</div>
                                        <button type="button" class="btn btn-primary btn-lg w-100" disabled>
                                            <i class="fas fa-link me-2"></i> Falta Enlace
                                        </button>
                                    <?php endif; ?>

                                    <hr>

                                    <?php if ($puede_operar): ?>
                                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#confirmarFinalizar">
                                            <i class="fas fa-check-circle me-2"></i> Finalizar
                                        </button>
                                        <p class="small text-muted mt-2">Solo finaliza cuando la tutoría haya concluido.</p>
                                    <?php elseif ($solicitud['estado'] === 'COMPLETADA'): ?>
                                        <button type="button" class="btn btn-secondary w-100" disabled>Sesión ya procesada</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary w-100" disabled>Acción no disponible</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="confirmarFinalizar" tabindex="-1" aria-labelledby="confirmarFinalizarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="procesar_cierre_tutoria.php">
                    <input type="hidden" name="solicitud_id" value="<?= $solicitud_id ?>">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="confirmarFinalizarLabel">Confirmar Finalización de Tutoría #<?= $solicitud_id ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas finalizar la tutoría de **<?= htmlspecialchars($solicitud['materia']) ?>** con **<?= htmlspecialchars($solicitud['estudiante']) ?>**?</p>
                        <p class="small text-danger">Al confirmar, el estado cambiará a **COMPLETADA** y se procederá con el procesamiento del pago.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Sí, Finalizar Tutoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>
</html>