<?php
// Ver_tutorias.php

// 1. CONFIGURACIÓN INICIAL Y VERIFICACIÓN DE SESIÓN (Estudiante)
include 'Includes/Nav.php'; 
// Asegúrate de que Nav.php ya inicia session_start()
include "../Includes/db.php"; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// *** IMPORTANTE: AÑADIR ESTA LÍNEA AQUÍ ***
// Asegura que PHP use la zona horaria correcta para todos los cálculos de fecha/hora.
// Reemplaza 'America/El_Salvador' con la zona horaria real de tu aplicación.
date_default_timezone_set('America/El_Salvador'); 
// ********************************************

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}

$id_usuario = $_SESSION['id'];

// --- LÓGICA DE PROCESAMIENTO DEL MODAL DE CALIFICACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calificar') {
    
    // Recolección y Sanitización de Datos
    $solicitud_id = filter_input(INPUT_POST, 'solicitud_id', FILTER_VALIDATE_INT);
    $id_tutor = filter_input(INPUT_POST, 'id_tutor', FILTER_VALIDATE_INT);
    $calificacion = filter_input(INPUT_POST, 'puntuacion', FILTER_VALIDATE_FLOAT); 
    $comentario = trim($_POST['comentario'] ?? '');
    
    // Validación
    if ($solicitud_id && $id_tutor && $calificacion >= 1 && $calificacion <= 5) {
        try {
            // A. Verificar si ya fue calificada
            // Se asume que la tabla de calificaciones es 'calificaciones_tutorias'
            $sql_check = "SELECT id FROM calificaciones_tutorias WHERE id_solicitud = :solicitud_id";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
            $stmt_check->execute();
            
            if (!$stmt_check->fetch()) {
                // B. Insertar la nueva calificación
                $sql_insert = "
                    INSERT INTO calificaciones_tutorias 
                    (id_solicitud, id_estudiante, id_tutor, calificacion, comentario, fecha_calificacion)
                    VALUES 
                    (:id_solicitud, :id_estudiante, :id_tutor, :calificacion, :comentario, NOW())
                ";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bindParam(':id_solicitud', $solicitud_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':id_estudiante', $id_usuario, PDO::PARAM_INT);
                $stmt_insert->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
                $stmt_insert->bindParam(':calificacion', $calificacion);
                $stmt_insert->bindParam(':comentario', $comentario);
                $stmt_insert->execute();

                $_SESSION['mensaje'] = "¡Gracias! Tu calificación ha sido enviada con éxito.";
                $_SESSION['tipo_mensaje'] = 'success';
                
                // Actualizar a COMPLETADA si era CONFIRMADA y ya ha pasado (para cerrar el ciclo)
                $sql_update_solicitud = "UPDATE solicitudes_tutorias SET estado = 'COMPLETADA' WHERE id = :solicitud_id AND estado != 'COMPLETADA'";
                $stmt_update = $conn->prepare($sql_update_solicitud);
                $stmt_update->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
                $stmt_update->execute();
                
            } else {
                 $_SESSION['mensaje'] = "Error: Esta tutoría ya fue calificada.";
                 $_SESSION['tipo_mensaje'] = 'danger';
            }

        } catch (PDOException $e) {
            error_log("Error al insertar calificación (Ver_tutorias.php): " . $e->getMessage());
            $_SESSION['mensaje'] = "Error interno al guardar la calificación. Intenta de nuevo.";
            $_SESSION['tipo_mensaje'] = 'danger';
        }
    } else {
         $_SESSION['mensaje'] = "Error de validación: La puntuación es requerida y debe ser un valor entre 1 y 5.";
         $_SESSION['tipo_mensaje'] = 'danger';
    }
    
    // Redirigir al mismo archivo para limpiar el POST y mostrar el mensaje
    header("Location: VerTutorias.php");
    exit();
}
// --- FIN LÓGICA DE PROCESAMIENTO ---


// 2. CONSULTA DE TUTORÍAS CONFIRMADAS Y COMPLETADAS (con calificación)
// Se cambió la columna de LEFT JOIN para apuntar a la tabla y columna correctas: 
// LEFT JOIN calificaciones_tutorias c ON s.id = c.id_solicitud
$sql = "
    SELECT
        s.id AS solicitud_id, 
        s.fecha, s.hora_inicio, s.hora_fin, s.duracion, s.precio_total, s.estado, s.fecha_pago, s.id_tutor,
        m.nombre_materia AS materia,
        t.nombre AS nombre_tutor, t.apellido AS apellido_tutor,
        c.id AS calificacion_existente_id,
        c.calificacion AS calificacion_valor 
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN usuarios t ON s.id_tutor = t.id 
    JOIN materias m ON o.id_materia = m.id 
    LEFT JOIN calificaciones_tutorias c ON s.id = c.id_solicitud 
    WHERE s.id_estudiante = :estudiante_id
    AND s.estado IN ('CONFIRMADA', 'COMPLETADA') 
    ORDER BY s.fecha DESC, s.hora_inicio DESC;
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':estudiante_id', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar tutorías confirmadas: " . $e->getMessage());
    $tutorias = [];
    $error_db = "Error al cargar el historial de tutorías.";
}

// Lógica para mostrar mensajes de sesión y luego borrarlos
$mensaje_sesion = $_SESSION['mensaje'] ?? null;
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? 'info';
unset($_SESSION['mensaje']); 
unset($_SESSION['tipo_mensaje']); 

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Historial y próximas tutorías pagadas" />
    <meta name="author" content="" />
    <title>Tutorías Inscritas</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Tutorías Inscritas</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Ver todas</li>
                    </ol>
                    
                    <?php if ($mensaje_sesion): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje); ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensaje_sesion; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-calendar-check me-1"></i>
                            Clases Pagadas y Agendadas
                        </div>

                        <div class="card-body">

                            <?php if (isset($error_db)): ?>
                                <div class="alert alert-danger"><?= $error_db ?></div>
                            <?php endif; ?>

                            <?php if (count($tutorias) > 0): ?>

                                <div class="row row-cols-1 row-cols-lg-3 g-4">

                                    <?php foreach ($tutorias as $tutoria):
                                        $estado = htmlspecialchars($tutoria['estado']);
                                        // Ahora es 'calificacion_existente_id' y 'calificacion_valor'
                                        $calificacion_existente_id = $tutoria['calificacion_existente_id']; 
                                        $calificacion_valor = $tutoria['calificacion_valor'];
                                        
                                        // 1. LÓGICA DE TIEMPO
                                        
                                        $timestamp_inicio = strtotime($tutoria['fecha'] . ' ' . $tutoria['hora_inicio']);
                                        $timestamp_fin = $timestamp_inicio + ($tutoria['duracion'] * 3600);
                                        $sesion_ya_paso = ($timestamp_fin < time()); 
                                        
                                        // Inicialización de variables de control
                                        $mostrar_boton_calificar = false;
                                        $clase_card = 'border-start-secondary';
                                        $clase_estado = 'bg-secondary';
                                        $label_estado = $estado;

                                        // --- LÓGICA VISUAL Y DE ACCIÓN ---
                                        if ($estado === 'COMPLETADA') {
                                            $clase_card = 'border-start-success';
                                            $clase_estado = 'bg-success';
                                            $label_estado = 'FINALIZADA';
                                            
                                            if (empty($calificacion_existente_id)) {
                                                // Si el tutor cerró, invitamos a calificar si no lo ha hecho
                                                $mostrar_boton_calificar = true;
                                            } else {
                                                $label_estado = 'FINALIZADA';
                                            }

                                        } elseif ($estado === 'CONFIRMADA') {
                                            
                                            if ($sesion_ya_paso) {
                                                // CONFIRMADA pero pasó la hora -> Pendiente de cierre
                                                $clase_card = 'border-start-warning'; 
                                                $clase_estado = 'bg-warning text-dark';
                                                $label_estado = 'PENDIENTE DE CIERRE';
                                                
                                                if (empty($calificacion_existente_id)) {
                                                    // Si pasó la hora, se puede calificar (e implícitamente cerrar la sesión)
                                                    $mostrar_boton_calificar = true;
                                                } else {
                                                    $label_estado = 'PENDIENTE (Calificada)';
                                                    $clase_estado = 'bg-success'; // Una vez calificada, es de facto FINALIZADA
                                                }
                                                
                                            } else {
                                                // CONFIRMADA y Próxima
                                                $clase_card = 'border-start-primary';
                                                $clase_estado = 'bg-primary';
                                                $label_estado = 'CONFIRMADA';
                                            }
                                        }
                                        
                                        // Lógica para el formato de duración
                                        $duracion_horas = floatval($tutoria['duracion']);
                                        if ($duracion_horas < 1 && $duracion_horas > 0) {
                                            $duracion_str = ($duracion_horas * 60) . ' min.';
                                        } elseif ($duracion_horas >= 1) {
                                            $duracion_str = rtrim(rtrim(number_format($duracion_horas, 2), '0'), '.') . ' hrs.';
                                        } else {
                                            $duracion_str = 'N/A';
                                        }
                                        ?>

                                        <div class="col">
                                            <div class="card shadow h-100 <?= $clase_card ?> border-start-5">

                                                <div
                                                    class="card-header d-flex justify-content-between align-items-center p-3 text-white <?= $clase_estado ?>">
                                                    <div class="h5 mb-0">
                                                        <i class="far fa-calendar-alt me-2"></i>
                                                        <?= date('d/M/Y', strtotime($tutoria['fecha'])) ?>
                                                    </div>
                                                    <div class="h5 mb-0">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?= date('H:i', $timestamp_inicio) ?>
                                                    </div>
                                                </div>

                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12 col-md-8">
                                                            <p class="text-muted small mb-1">Tutoría de:</p>
                                                            <h4 class="text-uppercase mb-1 text-dark">
                                                                <?= htmlspecialchars($tutoria['materia']) ?>
                                                            </h4>
                                                            <p class="mb-3">
                                                                <i class="fas fa-chalkboard-teacher me-1 text-muted"></i>
                                                                Tutor:
                                                                <?= htmlspecialchars($tutoria['nombre_tutor'] . ' ' . $tutoria['apellido_tutor']) ?>
                                                            </p>

                                                            <div class="mt-3">
                                                                <div class="small text-muted">Precio Pagado:</div>
                                                                <h5 class="text-success mb-0">
                                                                    $<?= number_format($tutoria['precio_total'], 2) ?></h5>
                                                            </div>
                                                            <?php if (!empty($calificacion_existente_id)): ?>
                                                                <div class="mt-2">
                                                                    <div class="small text-muted">Tu Calificación:</div>
                                                                    <h5 class="text-warning mb-0">
                                                                        <i class="fas fa-star me-1"></i>
                                                                        <?= number_format((float)$calificacion_valor, 1) ?>/5
                                                                    </h5>
                                                                </div>
                                                            <?php endif; ?>
                                                            </div>
                                                        <div class="col-12 col-md-4 text-md-end pt-3 pt-md-0">

                                                            <div class="small text-muted">Duración:</div>
                                                            <h6 class="text-dark mb-3">
                                                                <?= $duracion_str ?>
                                                            </h6>
                                                            <div class="small text-muted mt-2">ID:</div>
                                                            <h6 class="text-dark">
                                                                #<?= htmlspecialchars($tutoria['solicitud_id']) ?></h6>

                                                            <div class="small text-muted">Estado:</div>
                                                            <div class="mt-2">
                                                                <span class="badge rounded-pill <?= $clase_estado ?> p-2"><?= $label_estado ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer bg-light p-2 text-end">
                                                    <small class="text-muted">
                                                        <i class="fas fa-receipt me-1"></i> Pagado el:
                                                        <?= date('d/M/Y H:i', strtotime($tutoria['fecha_pago'])) ?>
                                                    </small>
                                                    
                                                    <div class="mt-2 pt-2 border-top">
                                                        <?php if ($estado === 'CONFIRMADA' && !$sesion_ya_paso): ?>
                                                            <a href="sala_virtual.php?id=<?= $tutoria['solicitud_id'] ?>" class="btn btn-primary btn-sm me-2" title="Unirse a la sala virtual">
                                                                <i class="fas fa-video"></i> Ir a Sesión
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($mostrar_boton_calificar): ?>
                                                            <button type="button" class="btn btn-warning btn-sm me-2" 
                                                                    data-bs-toggle="modal" data-bs-target="#modalCalificar-<?= $tutoria['solicitud_id'] ?>" 
                                                                    title="Calificar la experiencia con el tutor">
                                                                <i class="fas fa-star"></i> Calificar
                                                            </button>
                                                        <?php elseif ($sesion_ya_paso && !empty($calificacion_existente_id)): ?>
                                                            <span class="badge bg-success"><i class="fas fa-check"></i> Calificado</span>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="btn btn-info btn-sm"
                                                                data-bs-toggle="modal" data-bs-target="#modalDetalle-<?= $tutoria['solicitud_id'] ?>">
                                                            <i class="fas fa-info-circle"></i> Ver Detalle
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($mostrar_boton_calificar): ?>
                                            <div class="modal fade" id="modalCalificar-<?= $tutoria['solicitud_id'] ?>" tabindex="-1" aria-labelledby="modalCalificarLabel-<?= $tutoria['solicitud_id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="VerTutorias.php">
                                                            <input type="hidden" name="action" value="calificar">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="modalCalificarLabel-<?= $tutoria['solicitud_id'] ?>">
                                                                    Calificar a <?= htmlspecialchars($tutoria['nombre_tutor'] . ' ' . $tutoria['apellido_tutor']) ?>
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="solicitud_id" value="<?= $tutoria['solicitud_id'] ?>">
                                                                <input type="hidden" name="id_tutor" value="<?= $tutoria['id_tutor'] ?>">

                                                                <div class="mb-3">
                                                                    <label for="puntuacion-<?= $tutoria['solicitud_id'] ?>" class="form-label">Puntuación (1-5) *</label>
                                                                    <select class="form-select" id="puntuacion-<?= $tutoria['solicitud_id'] ?>" name="puntuacion" required>
                                                                        <option value="">Seleccione...</option>
                                                                        <option value="5">5 estrellas (Excelente)</option>
                                                                        <option value="4">4 estrellas (Muy bueno)</option>
                                                                        <option value="3">3 estrellas (Bueno)</option>
                                                                        <option value="2">2 estrellas (Regular)</option>
                                                                        <option value="1">1 estrella (Malo)</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="comentario-<?= $tutoria['solicitud_id'] ?>" class="form-label">Comentarios (Opcional)</label>
                                                                    <textarea class="form-control" id="comentario-<?= $tutoria['solicitud_id'] ?>" name="comentario" rows="3"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                <button type="submit" class="btn btn-primary">Enviar Calificación</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="modal fade" id="modalDetalle-<?= $tutoria['solicitud_id'] ?>" tabindex="-1" aria-labelledby="modalDetalleLabel-<?= $tutoria['solicitud_id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-info text-white">
                                                        <h5 class="modal-title" id="modalDetalleLabel-<?= $tutoria['solicitud_id'] ?>">
                                                            Detalle de la Tutoría #<?= $tutoria['solicitud_id'] ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h4 class="text-primary mb-3"><?= htmlspecialchars($tutoria['materia']) ?></h4>
                                                        
                                                        <ul class="list-group list-group-flush">
                                                            <li class="list-group-item">
                                                                <i class="fas fa-calendar-day me-2 text-muted"></i>
                                                                <strong>Fecha:</strong>
                                                                <span class="float-end"><?= date('d/m/Y', strtotime($tutoria['fecha'])) ?></span>
                                                            </li>
                                                            <li class="list-group-item">
                                                                <i class="fas fa-clock me-2 text-muted"></i>
                                                                <strong>Hora:</strong>
                                                                <span class="float-end"><?= date('H:i', $timestamp_inicio) ?> (<?= $duracion_str ?>)</span>
                                                            </li>
                                                            <li class="list-group-item">
                                                                <i class="fas fa-chalkboard-teacher me-2 text-muted"></i>
                                                                <strong>Tutor:</strong>
                                                                <span class="float-end"><?= htmlspecialchars($tutoria['nombre_tutor'] . ' ' . $tutoria['apellido_tutor']) ?></span>
                                                            </li>
                                                            <li class="list-group-item">
                                                                <i class="fas fa-money-bill-wave me-2 text-muted"></i>
                                                                <strong>Costo:</strong>
                                                                <span class="float-end text-success fw-bold">$<?= number_format($tutoria['precio_total'], 2) ?></span>
                                                            </li>
                                                            <li class="list-group-item">
                                                                <i class="fas fa-receipt me-2 text-muted"></i>
                                                                <strong>Pagado el:</strong>
                                                                <span class="float-end"><?= date('d/m/Y H:i', strtotime($tutoria['fecha_pago'])) ?></span>
                                                            </li>
                                                            <li class="list-group-item">
                                                                <i class="fas fa-info-circle me-2 text-muted"></i>
                                                                <strong>Estado Actual:</strong>
                                                                <span class="float-end"><span class="badge rounded-pill <?= $clase_estado ?>"><?= $label_estado ?></span></span>
                                                            </li>
                                                             <?php if (!empty($calificacion_existente_id)): ?>
                                                                <li class="list-group-item">
                                                                    <i class="fas fa-star me-2 text-muted"></i>
                                                                    <strong>Tu Calificación:</strong>
                                                                    <span class="float-end text-warning fw-bold">
                                                                        <?= number_format((float)$calificacion_valor, 1) ?>/5
                                                                    </span>
                                                                </li>
                                                             <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <?php if ($estado === 'CONFIRMADA' && !$sesion_ya_paso): ?>
                                                            <a href="sala_virtual.php?id=<?= $tutoria['solicitud_id'] ?>" class="btn btn-primary">
                                                                <i class="fas fa-video"></i> Ir a Sesión
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    Aún no tienes tutorías confirmadas o pagadas en tu historial.
                                </div>
                            <?php endif; ?>
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