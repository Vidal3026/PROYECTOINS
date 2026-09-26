<?php
include 'Includes/Nav.php';

// 1. Verificación de Seguridad
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];
// Incluir la conexión a la base de datos (ajusta la ruta si es necesario)
include "../Includes/db.php";

$id_estudiante = $_SESSION['id'];

// 2. VALIDACIÓN DEL ID DE SOLICITUD
$solicitud_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;

if ($solicitud_id === 0) {
    $_SESSION['mensaje'] = "Error: ID de tutoría inválido. No se puede acceder a la sala.";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: Solicitudes.php");
    exit();
}

// 3. CONSULTA SEGURA DE LA TUTORÍA (Verificación de Propiedad del Estudiante)
// Asumimos que link_sala_virtual está en solicitudes_tutorias (como corregimos)
$sql = "
    SELECT 
        s.*, 
        s.link_sala_virtual,
        m.nombre_materia AS materia,
        CONCAT(t.nombre, ' ', t.apellido) AS tutor 
    FROM solicitudes_tutorias s
    JOIN usuarios t ON s.id_tutor = t.id
    JOIN ofertas_tutorias ot ON s.id_oferta = ot.id 
    JOIN materias m ON ot.id_materia = m.id
    WHERE s.id = :id 
    AND s.id_estudiante = :estudiante_id
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $solicitud_id, PDO::PARAM_INT);
    $stmt->bindParam(':estudiante_id', $id_estudiante, PDO::PARAM_INT);
    $stmt->execute();
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error de DB en sala_virtual (Estudiante): " . $e->getMessage());
    $_SESSION['mensaje'] = "Error del sistema al buscar la tutoría.";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: Solicitudes.php");
    exit();
}

// 4. VERIFICACIÓN DE PROPIEDAD Y ESTADO
if (!$solicitud) {
    $_SESSION['mensaje'] = "Tutoría #{$solicitud_id} no encontrada o no te pertenece.";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: Solicitudes.php");
    exit();
}

$link_sala = $solicitud['link_sala_virtual'];
$estado = $solicitud['estado'];

// Restringir el acceso si el estado no es CONFIRMADA o en_progreso
if ($estado !== 'CONFIRMADA' && $estado !== 'en_progreso') {
    if ($estado === 'COMPLETADA') {
        $_SESSION['mensaje'] = "Esta tutoría ha finalizado y fue marcada como 'COMPLETADA'.";
        $_SESSION['tipo_mensaje'] = "info";
    } else if ($estado === 'PENDIENTE') {
        $_SESSION['mensaje'] = "Esta tutoría aún está 'PENDIENTE' de ser confirmada por el tutor.";
        $_SESSION['tipo_mensaje'] = "warning";
    } else {
        $_SESSION['mensaje'] = "Acceso denegado. La tutoría tiene el estado '{$estado}'.";
        $_SESSION['tipo_mensaje'] = "warning";
    }
    header("Location: Solicitudes.php");
    exit();
}

// 5. LÓGICA DE TIEMPOS (Permitir acceso 5 minutos antes)
// CORREGIDO: Usar la zona horaria correcta para El Salvador
date_default_timezone_set('America/El_Salvador'); 
$timestamp_inicio = strtotime($solicitud['fecha'] . ' ' . $solicitud['hora_inicio']);
$timestamp_fin = strtotime($solicitud['fecha'] . ' ' . $solicitud['hora_fin']);
$hora_actual = time();

$margen_inicio = $timestamp_inicio - (5 * 60); // 5 minutos antes

$sesion_aun_no_empieza = ($hora_actual < $margen_inicio);
$sesion_ya_termino = ($hora_actual > $timestamp_fin);

// Definir clases y textos de estado para la interfaz
$estado_clase = 'bg-primary';
$estado_texto = 'CONFIRMADA';
if ($estado === 'en_progreso') {
    $estado_clase = 'bg-success';
    $estado_texto = 'EN PROGRESO';
} elseif ($estado === 'COMPLETADA') {
    $estado_clase = 'bg-secondary';
    $estado_texto = 'FINALIZADA';
} elseif ($sesion_ya_termino) {
    $estado_clase = 'bg-warning text-dark';
    $estado_texto = 'TIEMPO EXPIRADO';
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Sala Virtual #<?= $solicitud_id ?> - Estudiante</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous" />
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
                    
                </div>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Sala Virtual #<?= $solicitud_id ?></h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sala Virtual</li>
                    </ol>

                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje'] ?? 'info') ?> alert-dismissible fade show"
                            role="alert">
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
                                        Tutor: <?= htmlspecialchars($solicitud['tutor']) ?><br>
                                        Fecha: <?= date('d/m/Y', strtotime($solicitud['fecha'])) ?><br>
                                        Hora: <?= date('h:i A', $timestamp_inicio) ?> -
                                        <?= date('h:i A', $timestamp_fin) ?>
                                    </p>
                                    <span class="badge <?= $estado_clase ?> p-2"><?= $estado_texto ?></span>
                                </div>
                            </div>

                            <?php if (!$link_sala): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i> El tutor aún no ha proporcionado el
                                    enlace de la sala virtual. Por favor, espera a que lo configure.
                                </div>
                            <?php elseif ($sesion_ya_termino): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-calendar-times me-2"></i> La hora de finalización de esta sesión ha
                                    pasado (<?= date('h:i A', $timestamp_fin) ?>).
                                </div>
                            <?php elseif ($sesion_aun_no_empieza): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-clock me-2"></i> La sala estará disponible para acceso a partir de las
                                    <strong><?= date('h:i A', $margen_inicio) ?></strong> (5 minutos antes del inicio).
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-4 text-center">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-play me-1"></i> Acceso a la Sesión
                                </div>
                                <div class="card-body">

                                    <?php
                                    $puede_acceder = $link_sala && !$sesion_ya_termino && !$sesion_aun_no_empieza;

                                    if ($puede_acceder): ?>
                                        <a href="<?= htmlspecialchars($link_sala) ?>" target="_blank"
                                            class="btn btn-success btn-lg w-100 mb-3" role="button">
                                            <i class="fas fa-video me-2"></i> **ENTRAR A LA SALA VIRTUAL**
                                        </a>
                                        <p class="small text-muted">Asegúrate de tener micrófono y cámara listos.</p>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-lg w-100 mb-3" disabled>
                                            <i class="fas fa-lock me-2"></i> Acceso No Disponible
                                        </button>
                                        <?php if ($sesion_aun_no_empieza): ?>
                                            <p class="small text-muted">Vuelve a esta página cuando se acerque la hora de
                                                inicio.</p>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>

</html>