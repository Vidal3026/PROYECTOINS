<?php
session_start();
include '../Includes/db.php'; // Incluye la conexión a la base de datos

// Código para evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. VERIFICACIÓN DE SESIÓN (Administrador)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    // Si la sesión no existe o el rol es incorrecto, redirige al login
    header("Location: ../Login.php");
    exit();
}

$retiros_pendientes = 0;
// $recargas_pendientes = 0; <--- Eliminada
$total_tutores = 0;
$total_estudiantes = 0;
$error_db = null;

try {
    // A. Contar Retiros Pendientes
    $sql_retiros = "SELECT COUNT(*) AS total FROM solicitudes_retiro WHERE estado = 'PENDIENTE'";
    $stmt_retiros = $conn->query($sql_retiros);
    $retiros_pendientes = $stmt_retiros->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // B. Contar Usuarios (Tutores y Estudiantes)
    $sql_usuarios = "SELECT rol, COUNT(*) AS total FROM usuarios WHERE rol IN ('tutor', 'estudiante') GROUP BY rol";
    $stmt_usuarios = $conn->query($sql_usuarios);
    $usuarios_data = $stmt_usuarios->fetchAll(PDO::FETCH_KEY_PAIR);

    // C. Contar Tutorías Finalizadas (Total de producción)
    $sql_tutorias = "SELECT COUNT(*) AS total FROM solicitudes_tutorias WHERE estado = 'COMPLETADA'";
    $stmt_tutorias = $conn->query($sql_tutorias);
    $total_tutorias = $stmt_tutorias->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $total_tutores = $usuarios_data['tutor'] ?? 0;
    $total_estudiantes = $usuarios_data['estudiante'] ?? 0;

} catch (PDOException $e) {
    error_log("Error de BD en Dashboard: " . $e->getMessage());
    $error_db = "Error al cargar métricas de la base de datos.";
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
    <title>Dashboard - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <?php if (isset($_SESSION['nombre'])): ?>
                        <h1 class="mt-4">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h1>
                    <?php endif; ?>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>

                    <?php if (isset($error_db)): ?>
                        <div class="alert alert-danger"><?php echo $error_db; ?></div>
                    <?php endif; ?>

                    <h2 class="h5 mb-3">Tareas de Gestión</h2>
                    <div class="row">

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-danger text-white shadow h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-uppercase mb-1">
                                                Solicitudes de Retiro
                                            </div>
                                            <div class="h5 mb-0 fw-bold"><?php echo $retiros_pendientes; ?> PENDIENTES
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-hand-holding-dollar fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link"
                                        href="SolicitudesAdministrador.php">Gestionar</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-primary text-white shadow h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-uppercase mb-1">
                                                Total de Tutores
                                            </div>
                                            <div class="h5 mb-0 fw-bold"><?php echo $total_tutores; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chalkboard-user fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="GestionarUsuarios.php">Ver
                                        Listado</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-info text-white shadow h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-uppercase mb-1">
                                                Total de Estudiantes
                                            </div>
                                            <div class="h5 mb-0 fw-bold"><?php echo $total_estudiantes; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-graduate fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="GestionarUsuarios.php">Ver
                                        Listado</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-success text-white shadow h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-uppercase mb-1">
                                                Tutorías Completadas
                                            </div>
                                            <div class="h5 mb-0 fw-bold"><?php echo $total_tutorias; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="GestionTutorias.php">Ver
                                        Historial</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Ingresos por Mes
                                </div>
                                <div class="card-body" style="height: 300px;"><canvas id="myBarChart"
                                        width="100%"></canvas></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-pie me-1"></i>
                                    Distribución de Usuarios (Tutores vs Estudiantes)
                                </div>
                                <div class="card-body" style="height: 300px;"><canvas id="myPieChart"
                                        width="100%"></canvas></div>
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

    <script src="js/chart-bar-demo.js"></script>
    <script src="js/chart-pie-demo.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
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
            }).then(() => {
                // Opcional: limpiar la URL para que la alerta no aparezca si se recarga
                if (history.replaceState) {
                    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
                }
            });
        }
    </script>
</body>

</html>