<?php
session_start();

// Código para evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verifica si la sesión está activa y si el rol coincide
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    // Si la sesión no existe o el rol es incorrecto, redirige al login
    header("Location: ../Login.php");
    exit();
}

// Incluye el archivo de conexión a la base de datos
include "../Includes/db.php";

// 2. OBTENER SOLICITUDES PENDIENTES
$pendientes = [];
$historial = [];

try {
    // Consulta para obtener TODAS las solicitudes y los datos del tutor (nombre, correo)
    // Se ha corregido 'email_tutor' a 'correo_tutor' según tu feedback.
    $sql = "SELECT sr.id, sr.monto, sr.metodo_pago, sr.datos_pago, sr.estado, sr.fecha_solicitud, sr.fecha_procesamiento,
                   u.nombre AS nombre_tutor, u.correo AS correo_tutor, u.id AS id_tutor
            FROM solicitudes_retiro sr
            JOIN usuarios u ON sr.id_tutor = u.id
            ORDER BY sr.fecha_solicitud DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separar en pendientes e historial
    foreach ($solicitudes as $sol) {
        if ($sol['estado'] === 'PENDIENTE') {
            $pendientes[] = $sol;
        } else {
            $historial[] = $sol;
        }
    }

} catch (PDOException $e) {
    error_log("Error al cargar solicitudes de retiro: " . $e->getMessage());
    $error_db = "Error al cargar datos. Consulte el log.";
}

// 3. MANEJO DE ALERTAS (Éxito de Aprobación/Rechazo)
$alert_message = '';
$alert_type = '';

if (isset($_GET['success'])) {
    $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Acción realizada con éxito.';
    $alert_type = 'success';
    $alert_message = "✅ Éxito: {$msg}";
} elseif (isset($_GET['error'])) {
    $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Ocurrió un error inesperado.';
    $alert_type = 'danger';
    $alert_message = "❌ Error: {$msg}";
}

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
    <?php include 'Includes/Nav.php'; ?>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Gestión de Retiros</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Solicitudes de Retiro</li>
                    </ol>

                    <?php if (!empty($alert_message)): ?>
                        <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $alert_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header bg-warning text-white">
                            <i class="fas fa-hourglass-half me-1"></i>
                            Solicitudes Pendientes de Aprobación (<?php echo count($pendientes); ?>)
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendientes)): ?>
                                <p class="text-center">No hay solicitudes de retiro pendientes actualmente.</p>
                            <?php else: ?>
                                <table id="datatablesSimple" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#ID</th>
                                            <th>Tutor</th>
                                            <th>Monto</th>
                                            <th>Método de Pago</th>
                                            <th>Detalles de Pago</th>
                                            <th>Fecha Solicitud</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendientes as $p): ?>
                                            <tr>
                                                <td><?php echo $p['id']; ?></td>
                                                <td><?php echo htmlspecialchars($p['nombre_tutor']); ?></td>
                                                <td><strong>$<?php echo number_format($p['monto'], 2); ?></strong></td>
                                                <td><?php echo htmlspecialchars($p['metodo_pago']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($p['datos_pago'])); ?></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($p['fecha_solicitud'])); ?></td>
                                                <td>
                                                    <button class="btn btn-success btn-sm mb-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalAprobar"
                                                        data-id="<?php echo $p['id']; ?>"
                                                        data-monto="<?php echo number_format($p['monto'], 2); ?>"
                                                        data-tutor="<?php echo htmlspecialchars($p['nombre_tutor']); ?>">
                                                        Aprobar y Ejecutar
                                                    </button>

                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                        data-bs-target="#modalRechazar"
                                                        data-id="<?php echo $p['id']; ?>"
                                                        data-monto="<?php echo number_format($p['monto'], 2); ?>">
                                                        Rechazar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <i class="fas fa-history me-1"></i>
                            Historial de Retiros Procesados
                        </div>
                        <div class="card-body">
                            <?php if (empty($historial)): ?>
                                <p class="text-center">No hay retiros procesados en el historial.</p>
                            <?php else: ?>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#ID</th>
                                            <th>Tutor</th>
                                            <th>Monto</th>
                                            <th>Método</th>
                                            <th>Estado</th>
                                            <th>Fecha Procesado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historial as $h): ?>
                                            <tr>
                                                <td><?php echo $h['id']; ?></td>
                                                <td><?php echo htmlspecialchars($h['nombre_tutor']); ?></td>
                                                <td>$<?php echo number_format($h['monto'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($h['metodo_pago']); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $h['estado'] === 'APROBADO' ? 'success' : 'danger'; ?>">
                                                        <?php echo htmlspecialchars($h['estado']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($h['fecha_procesamiento'] ?? 'N/A')); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
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

    <div class="modal fade" id="modalAprobar" tabindex="-1" aria-labelledby="modalAprobarLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalAprobarLabel">Confirmar Ejecución de Retiro <span id="aprobarRetiroIdSpan"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="aprobar_retiro.php" method="GET">
                    <div class="modal-body">
                        <input type="hidden" name="retiro_id" id="aprobar_retiro_id_input">
                        <p>Estás a punto de confirmar el pago para el tutor <strong id="aprobarTutorSpan"></strong> por el monto de <strong id="aprobarMontoSpan"></strong>.</p>
                        
                        <div class="alert alert-danger">
                            🛑 **ADVERTENCIA CRÍTICA:** Al confirmar, el saldo del tutor se **descontará inmediatamente**. Asegúrate de haber realizado el pago antes de continuar.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar y Descontar Saldo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalRechazarLabel">Rechazar Solicitud de Retiro <span id="retiroIdSpan"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="rechazar_retiro.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="retiro_id" id="retiro_id_input">
                        <p>¿Estás seguro de que deseas rechazar el retiro por un monto de <strong id="montoSpan"></strong>?</p>
                        <div class="alert alert-warning">El saldo NO será descontado del tutor. El estado de la solicitud se marcará como RECHAZADO.</div>

                        <div class="form-group">
                            <label for="motivo_rechazo">Motivo del Rechazo</label>
                            <textarea class="form-control" id="motivo_rechazo" name="motivo" rows="3" required
                                placeholder="Indica la razón del rechazo (ej. Datos bancarios inválidos, Monto incorrecto)."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/chart-area-demo.js"></script>
    <script src="assets/demo/chart-bar-demo.js"></script>
    <script src="assets/demo/chart-pie-demo.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Lógica para el Modal de RECHAZO
            var modalRechazar = document.getElementById('modalRechazar');
            modalRechazar.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; 
                var retiroId = button.getAttribute('data-id');
                var monto = button.getAttribute('data-monto');

                var modalTitleSpan = modalRechazar.querySelector('#retiroIdSpan');
                var modalBodyMontoSpan = modalRechazar.querySelector('#montoSpan');
                var modalInputId = modalRechazar.querySelector('#retiro_id_input');
                
                modalTitleSpan.textContent = '(ID: ' + retiroId + ')';
                modalBodyMontoSpan.textContent = '$' + monto;
                modalInputId.value = retiroId;
                modalRechazar.querySelector('#motivo_rechazo').value = '';
            });

            // Lógica para el Modal de APROBACIÓN
            var modalAprobar = document.getElementById('modalAprobar');
            modalAprobar.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; 
                
                // Extraer la información de los atributos data-*
                var retiroId = button.getAttribute('data-id');
                var monto = button.getAttribute('data-monto');
                var tutor = button.getAttribute('data-tutor');

                // Actualizar los elementos dentro del modal
                var modalTitleSpan = modalAprobar.querySelector('#aprobarRetiroIdSpan');
                var modalTutorSpan = modalAprobar.querySelector('#aprobarTutorSpan');
                var modalMontoSpan = modalAprobar.querySelector('#aprobarMontoSpan');
                var modalInputId = modalAprobar.querySelector('#aprobar_retiro_id_input');
                
                // Rellenar los campos
                modalTitleSpan.textContent = '(ID: ' + retiroId + ')';
                modalTutorSpan.textContent = tutor;
                modalMontoSpan.textContent = '$' + monto;
                modalInputId.value = retiroId;
            });
        });
    </script>

</body>

</html>