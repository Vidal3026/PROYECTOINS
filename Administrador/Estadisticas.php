<?php
session_start();
include '../Includes/db.php'; // Incluye la conexión a la base de datos

// 1. VERIFICACIÓN DE SESIÓN (Administrador)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    header("Location: ../Login.php");
    exit();
}

// Variables para el rango de fechas (usaremos un rango por defecto de 90 días)
$fecha_fin = date('Y-m-d');
$fecha_inicio = date('Y-m-d', strtotime('-90 days'));

// Procesar el rango de fechas si se envió el formulario
if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin = $_GET['fecha_fin'];
}

// Nota: No se requiere incluir el archivo Wallet aquí a menos que lo uses directamente
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Estadisticas</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <!-- Navbar -->
    <?php include 'Includes/Nav.php'; ?>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <!-- Panel Izquierdo -->
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Estadisticas y Reportes</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Estadisticas</li>
                    </ol>
                    <!--Contendo-->

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-header">
                                    <i class="fas fa-filter me-1"></i>
                                    Filtro de Período
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="Estadisticas.php">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4">
                                                <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                                <input type="date" class="form-control" id="fecha_inicio"
                                                    name="fecha_inicio"
                                                    value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                                    value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary w-100">Aplicar
                                                    Filtro</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card mb-4 shadow">
                                <div class="card-header">
                                    <i class="fas fa-chart-line me-1"></i>
                                    Flujo de Efectivo Mensual (Ingreso vs Comisión)
                                </div>
                                <div class="card-body" style="height: 350px;"><canvas id="chartFlujoEfectivo"
                                        width="100%"></canvas></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card mb-4 shadow">
                                <div class="card-header">
                                    <i class="fas fa-ranking-star me-1"></i>
                                    Top 5 Tutores por Comisión Generada
                                </div>
                                <div class="card-body" style="height: 350px;"><canvas id="chartTopTutores"
                                        width="100%"></canvas></div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <!--Footer-->
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>

    <script src="js/chart-flujo-efectivo.js"></script>
    <script src="js/chart-top-tutores.js"></script>

</body>

</html>