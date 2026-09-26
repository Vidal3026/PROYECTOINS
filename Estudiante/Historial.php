<?php
include 'Includes/Nav.php'; 
// Historial.php - Versión Final con Modal y Responsividad
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../Includes/db.php'; 

// 1. Verificación de Seguridad
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}

$id_estudiante = $_SESSION['id'];

// 2. LÓGICA DE PROCESAMIENTO DEL MODAL (Maneja el submit del formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calificar') {
    $calificacion_id = filter_input(INPUT_POST, 'solicitud_id', FILTER_VALIDATE_INT);
    $id_tutor = filter_input(INPUT_POST, 'id_tutor', FILTER_VALIDATE_INT);
    
    // El campo del select se llama 'calificacion' y su valor es un entero (1-5)
    $calificacion = filter_input(INPUT_POST, 'calificacion', FILTER_VALIDATE_FLOAT); 
    $comentario = trim($_POST['comentario'] ?? '');
    
    // Verificamos que sea un valor numérico entre 1 y 5
    if ($calificacion_id && $id_tutor && $calificacion >= 1 && $calificacion <= 5) {
        try {
            // Verificar si ya fue calificada 
            $sql_check = "SELECT id FROM calificaciones_tutorias WHERE id_solicitud = :solicitud_id";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':solicitud_id', $calificacion_id, PDO::PARAM_INT);
            $stmt_check->execute();
            
            if (!$stmt_check->fetch()) {
                $sql_insert = "
                    INSERT INTO calificaciones_tutorias 
                    (id_solicitud, id_estudiante, id_tutor, calificacion, comentario, fecha_calificacion)
                    VALUES 
                    (:id_solicitud, :id_estudiante, :id_tutor, :calificacion, :comentario, NOW())
                ";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bindParam(':id_solicitud', $calificacion_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':id_estudiante', $id_estudiante, PDO::PARAM_INT);
                $stmt_insert->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
                $stmt_insert->bindParam(':calificacion', $calificacion);
                $stmt_insert->bindParam(':comentario', $comentario);
                
                $stmt_insert->execute();

                $_SESSION['mensaje'] = "¡Gracias! Tu calificación ha sido enviada con éxito.";
                $_SESSION['tipo_mensaje'] = 'success';
            } else {
                 $_SESSION['mensaje'] = "Error: Esta tutoría ya fue calificada.";
                 $_SESSION['tipo_mensaje'] = 'danger';
            }
        } catch (PDOException $e) {
            error_log("Error al insertar calificación (Modal): " . $e->getMessage());
            $_SESSION['mensaje'] = "Error al guardar la calificación. Intenta de nuevo.";
            $_SESSION['tipo_mensaje'] = 'danger';
        }
        header("Location: Historial.php");
        exit();
    } else {
         $_SESSION['mensaje'] = "Error de validación: La calificación es requerida y debe ser un valor entre 1 y 5.";
         $_SESSION['tipo_mensaje'] = 'danger';
    }
}
// Fin de la lógica de procesamiento

// 3. Consulta SQL
$sql_historial = "
    SELECT 
        s.id AS solicitud_id, 
        s.fecha_creacion,                                        
        CONCAT(s.fecha, ' ', s.hora_inicio) AS fecha_hora_tutoria, 
        s.estado,
        s.id_tutor,  
        
        m.nombre_materia AS tema_nombre,   
        t.nombre AS nombre_tutor,
        t.apellido AS apellido_tutor,
        
        c.id AS calificacion_id,
        c.calificacion AS calificacion_actual
        
    FROM 
        solicitudes_tutorias s
    
    JOIN 
        usuarios t ON s.id_tutor = t.id           
    
    JOIN 
        ofertas_tutorias o ON s.id_oferta = o.id   
    
    JOIN 
        materias m ON o.id_materia = m.id         
    
    LEFT JOIN 
        calificaciones_tutorias c ON s.id = c.id_solicitud
        
    WHERE 
        s.id_estudiante = :id_estudiante
    ORDER BY 
        s.id ASC 
";

try {
    $stmt = $conn->prepare($sql_historial);
    $stmt->bindParam(':id_estudiante', $id_estudiante, PDO::PARAM_INT);
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar historial (SQL/DB): " . $e->getMessage());
    $error_mensaje = "Hubo un error al cargar tu historial. Inténtalo de nuevo más tarde.";
}

// Lógica para mostrar mensajes de sesión
$mensaje_sesion = $_SESSION['mensaje'] ?? null;
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? 'info';
unset($_SESSION['mensaje']); 
unset($_SESSION['tipo_mensaje']); 

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Historial de Tutorías</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    
    <style>
        .badge {
            font-size: 0.75em;
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
            color: #fff;
            font-weight: 600;
        }
        .badge-success { background-color: #198754; } 
        .badge-danger { background-color: #dc3545; }  
        .badge-primary { background-color: #0d6efd; }  
        .badge-warning { background-color: #ffc107; color: #000; } 
        .text-center-data {
            text-align: center;
            vertical-align: middle !important;
        }
        #datatablesSimple thead th {
            background-color: #f8f9fa;
            color: #343a40;
        }
        .calificacion-badge {
            background-color: #6c757d; 
        }
        .card-body {
            padding: 1rem;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    
    
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    
                    <h1 class="mt-4">Historial de Tutorías</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>
                    
                    <?php if ($mensaje_sesion): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje); ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensaje_sesion; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Historial de Tutorías Solicitadas
                        </div>
                        <div class="card-body">
                            
                            <?php if (isset($error_mensaje)): ?>
                                <div class="alert alert-danger"><?php echo $error_mensaje; ?></div>
                            <?php elseif (empty($historial)): ?>
                                <div class="alert alert-info">Aún no tienes solicitudes de tutoría en tu historial.</div>
                            <?php else: ?>
                                
                                <div class="table-responsive"> 
                                    <table id="datatablesSimple" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tutor</th>
                                                <th style="min-width: 150px;">Materia</th> 
                                                <th class="text-center-data" style="min-width: 130px;">Fecha/Hora Tutoría</th>
                                                <th class="text-center-data" style="min-width: 130px;">Fecha Solicitud</th>
                                                <th class="text-center-data">Estado</th>
                                                <th class="text-center-data" style="min-width: 100px;">Acción</th> 
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historial as $solicitud): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($solicitud['solicitud_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($solicitud['nombre_tutor'] . ' ' . $solicitud['apellido_tutor']); ?></td>
                                                    
                                                    <td><?php echo htmlspecialchars($solicitud['tema_nombre']); ?></td>
                                                    
                                                    <td class="text-center-data"><?php 
                                                         echo date('d/m/Y H:i', strtotime($solicitud['fecha_hora_tutoria'])); 
                                                    ?></td>
                                                    <td class="text-center-data">
                                                        <?php echo date('d/m/Y', strtotime($solicitud['fecha_creacion'])); ?>
                                                    </td>
                                                    <td class="text-center-data">
                                                        <?php 
                                                             $estado = htmlspecialchars($solicitud['estado']);
                                                             $clase_estado = '';
                                                             
                                                             if ($estado === 'COMPLETADA') {
                                                                  $clase_estado = 'success';
                                                             } elseif ($estado === 'CANCELADA') {
                                                                  $clase_estado = 'danger';
                                                             } elseif ($estado === 'CONFIRMADA') {
                                                                  $clase_estado = 'primary';
                                                             } elseif ($estado === 'PENDIENTE') {
                                                                  $clase_estado = 'warning';
                                                             }elseif ($estado === 'ACEPTADA') {
                                                                  $clase_estado = '" style="background-color:#808080;color:white;';
                                                             }
                                                        ?>
                                                        <span class="badge badge-<?php echo $clase_estado; ?>">
                                                            <?php echo $estado; ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <td class="text-center-data">
                                                        <?php if ($solicitud['estado'] === 'COMPLETADA'): ?>
                                                            <?php if ($solicitud['calificacion_id']): ?>
                                                                <span class="badge calificacion-badge">
                                                                    <i class="fas fa-star"></i> <?php echo number_format((float)$solicitud['calificacion_actual'], 1); ?>/5
                                                                </span>
                                                            <?php else: ?>
                                                                <button 
                                                                    type="button" 
                                                                    class="btn btn-sm btn-warning btn-calificar"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#calificarModal"
                                                                    data-solicitud-id="<?php echo htmlspecialchars($solicitud['solicitud_id']); ?>"
                                                                    data-tutor-id="<?php echo htmlspecialchars($solicitud['id_tutor']); ?>"
                                                                    data-tutor-nombre="<?php echo htmlspecialchars($solicitud['nombre_tutor'] . ' ' . $solicitud['apellido_tutor']); ?>"
                                                                >
                                                                    <i class="fas fa-star"></i> Calificar
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span>-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            
                        </div>
                    </div>
                    </div>
            </main>
            
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2025</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <div class="modal fade" id="calificarModal" tabindex="-1" aria-labelledby="calificarModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="action" value="calificar">
            <input type="hidden" id="modal_solicitud_id" name="solicitud_id">
            <input type="hidden" id="modal_id_tutor" name="id_tutor">
          
            <div class="modal-header">
              <h5 class="modal-title" id="calificarModalLabel">
                  Calificar a <span id="tutor_nombre"></span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="mb-3">
                    <label for="calificacion" class="form-label">Puntuación (1-5)</label>
                    <select class="form-select" id="calificacion" name="calificacion" required>
                        <option value="">Seleccione...</option>
                        <option value="5">5 estrellas (Excelente)</option>
                        <option value="4">4 estrellas (Muy bueno)</option>
                        <option value="3">3 estrellas (Bueno)</option>
                        <option value="2">2 estrellas (Regular)</option>
                        <option value="1">1 estrella (Malo)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="comentario" class="form-label">Comentarios (Opcional)</label>
                    <textarea class="form-control" id="comentario" name="comentario" rows="3"></textarea>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    
    <script>
        // Inicialización de DataTables
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            // Cuando se muestra el modal, inyecta los IDs
            $('#calificarModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Botón que disparó el modal
                var solicitudId = button.data('solicitud-id'); 
                var tutorId = button.data('tutor-id');
                var tutorNombre = button.data('tutor-nombre');
                
                var modal = $(this);
                
                // 1. Configurar datos en los campos ocultos
                modal.find('#tutor_nombre').text(tutorNombre);
                modal.find('#modal_solicitud_id').val(solicitudId);
                modal.find('#modal_id_tutor').val(tutorId);
                
                // 2. Limpiar los campos del formulario al abrir
                modal.find('#calificacion').val('');
                modal.find('#comentario').val('');
            });
            
            // Opcional: Limpiar el formulario al cerrarse el modal
            $('#calificarModal').on('hidden.bs.modal', function () {
                $(this).find('form').trigger('reset');
            });
        });
    </script>
</body>

</html>