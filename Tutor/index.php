<?php
// Incluir la navegación y la sesión.
include 'Includes/Nav.php'; 
// Incluimos la conexión a la DB (PDO)
include '../Includes/db.php'; 

// VERIFICACIÓN DE SESIÓN (Tutor) y obtención del ID
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Dashboard - Tutor</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">    
            <main>
                <div class="container-fluid px-4">
                    <?php if (isset($_SESSION['nombre'])): ?>
                        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h1>
                    <?php endif; ?>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>

                    <?php
                        // --- Lógica de la Base de Datos para Estadísticas ---

                        // Inicializar variables
                        $total_tutorias_confirmadas = 0;
                        $total_horas_impartidas = 0;
                        $tutorias_pendientes_revision = 0;
                        $promedio_general = 0;

                        try {
                            // 1. Total de Tutorías Confirmadas
                            $sql_confirmadas = "SELECT COUNT(id) AS total FROM solicitudes_tutorias WHERE id_tutor = :id_tutor AND estado = 'CONFIRMADA'";
                            $stmt_confirmadas = $conn->prepare($sql_confirmadas);
                            $stmt_confirmadas->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
                            $stmt_confirmadas->execute();
                            $total_tutorias_confirmadas = $stmt_confirmadas->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

                            // 2. Total de Horas Impartidas (Tutorías Completadas)
                            $sql_horas = "SELECT SUM(duracion) AS total_horas FROM solicitudes_tutorias WHERE id_tutor = :id_tutor AND estado = 'COMPLETADA'";
                            $stmt_horas = $conn->prepare($sql_horas);
                            $stmt_horas->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
                            $stmt_horas->execute();
                            $total_horas_impartidas = $stmt_horas->fetch(PDO::FETCH_ASSOC)['total_horas'] ?? 0;

                            // 3. Solicitudes Pendientes de Revisión
                            $sql_pendientes = "SELECT COUNT(id) AS total FROM solicitudes_tutorias WHERE id_tutor = :id_tutor AND estado = 'PENDIENTE'";
                            $stmt_pendientes = $conn->prepare($sql_pendientes);
                            $stmt_pendientes->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
                            $stmt_pendientes->execute();
                            $tutorias_pendientes_revision = $stmt_pendientes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

                            // 4. Calificación Promedio General
                            $sql_promedio = "
                                SELECT AVG(calificacion) AS promedio_general 
                                FROM calificaciones_tutorias
                                WHERE id_tutor = :id_tutor
                            ";
                            $stmt_promedio = $conn->prepare($sql_promedio);
                            $stmt_promedio->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
                            $stmt_promedio->execute();
                            $promedio_general = $stmt_promedio->fetch(PDO::FETCH_ASSOC)['promedio_general'] ?? 0;
                            // Formateamos la calificación a un decimal
                            $promedio_formateado = number_format($promedio_general, 1);

                            // 5. Dinero del tutor: saldo y lo ganado este mes (ya descontadas las comisiones)
                            $stmt_dinero = $conn->prepare("
                                SELECT
                                    COALESCE(b.saldo, 0) AS saldo,
                                    COALESCE(SUM(CASE WHEN m.tipo = 'INGRESO' AND m.referencia LIKE 'Ingreso Solicitud%'
                                        AND m.fecha_movimiento >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN m.monto END), 0) AS ingresos_mes,
                                    COALESCE(SUM(CASE WHEN m.tipo = 'COMISION'
                                        AND m.fecha_movimiento >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN m.monto END), 0) AS comisiones_mes
                                FROM billeteras b
                                LEFT JOIN movimientos_billetera m ON m.id_billetera = b.id
                                WHERE b.id_usuario = :id_tutor
                                GROUP BY b.id, b.saldo
                            ");
                            $stmt_dinero->execute([':id_tutor' => $id_tutor]);
                            $dinero = $stmt_dinero->fetch(PDO::FETCH_ASSOC) ?: ['saldo' => 0, 'ingresos_mes' => 0, 'comisiones_mes' => 0];

                        } catch (PDOException $e) {
                            error_log("Error de estadísticas: " . $e->getMessage());
                            echo '<div class="alert alert-danger">Error al cargar estadísticas.</div>';
                        }
                    ?>

                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">Tutorías Confirmadas (Próximas)</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <h1 class="mb-0"><?php echo $total_tutorias_confirmadas; ?></h1>
                                    <div class="small text-white"><i class="fas fa-calendar-check fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">Total de Horas Impartidas</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <h1 class="mb-0"><?php echo number_format($total_horas_impartidas, 1); ?> h</h1>
                                    <div class="small text-white"><i class="fas fa-clock fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-dark mb-4">
                                <div class="card-body">Calificación Promedio</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <h1 class="mb-0"><?php echo $promedio_formateado; ?> / 5.0</h1>
                                    <div class="small text-dark"><i class="fas fa-star fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">Solicitudes Pendientes de Revisión</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <h1 class="mb-0"><?php echo $tutorias_pendientes_revision; ?></h1>
                                    <div class="small text-white"><i class="fas fa-hourglass-half fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-success shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="small text-muted">Saldo disponible</div>
                                    <h3 class="text-success mb-0">$<?= number_format($dinero['saldo'] ?? 0, 2) ?></h3>
                                </div>
                                <div class="card-footer small"><a href="Billetera.php">Ver billetera y retirar</a></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="small text-muted">Ingresos netos de este mes</div>
                                    <h3 class="text-primary mb-0">$<?= number_format($dinero['ingresos_mes'] ?? 0, 2) ?></h3>
                                </div>
                                <div class="card-footer small text-muted">Ya descontadas las comisiones</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="small text-muted">Comisiones de este mes</div>
                                    <h3 class="text-warning mb-0">$<?= number_format($dinero['comisiones_mes'] ?? 0, 2) ?></h3>
                                </div>
                                <div class="card-footer small text-muted">
                                    Tarifa de servicio (<?= porcentaje_comision(COMISION_ESTUDIANTE) ?>%) + comisión de tutor (<?= porcentaje_comision(COMISION_TUTOR) ?>%)
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-area me-1"></i>
                                    Horas de Tutoría por Mes
                                </div>
                                <div class="card-body"><canvas id="myAreaChart" width="100%" height="40"></canvas></div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Tutorías por Materia (Top 5)
                                </div>
                                <div class="card-body"><canvas id="myBarChart" width="100%" height="40"></canvas></div>
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    
    <script src="js/chart-area-tutor.js"></script>
    <script src="js/chart-bar-tutor.js"></script>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === "exitoso") {
            Swal.fire({
                icon: 'success',
                title: '¡Bienvenido!',
                text: 'Has iniciado sesión exitosamente.',
                showConfirmButton: false,
                timer: 2500
            });
        }
    </script>
</body>

</html>