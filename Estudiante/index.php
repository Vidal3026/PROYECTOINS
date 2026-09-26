<?php
// Asegúrate de que 'Includes/Nav.php' establece la conexión PDO en la variable $conn
include 'Includes/Nav.php';

// 1. VERIFICACIÓN DE SESIÓN
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}
$id_estudiante = $_SESSION['id'];

// 2. CONSULTAS PHP PARA EL DASHBOARD

// ----------------------------------------------------
// A. Total de Tutorías Confirmadas
// ----------------------------------------------------
$sql_confirmadas = "SELECT COUNT(id) FROM solicitudes_tutorias WHERE id_estudiante = :id AND estado = 'CONFIRMADA'";
$stmt = $conn->prepare($sql_confirmadas);
$stmt->bindParam(':id', $id_estudiante, PDO::PARAM_INT);
$stmt->execute();
$tutorias_confirmadas = $stmt->fetchColumn();

// ----------------------------------------------------
// B. Solicitudes Pendientes
// ----------------------------------------------------
$sql_pendientes = "SELECT COUNT(id) FROM solicitudes_tutorias WHERE id_estudiante = :id AND estado = 'PENDIENTE'";
$stmt = $conn->prepare($sql_pendientes);
$stmt->bindParam(':id', $id_estudiante, PDO::PARAM_INT);
$stmt->execute();
$solicitudes_pendientes = $stmt->fetchColumn();

// ----------------------------------------------------
// C. Total de Tutorías Completadas (Realizadas)
// ----------------------------------------------------
$sql_completadas = "SELECT COUNT(id) FROM solicitudes_tutorias WHERE id_estudiante = :id AND estado = 'COMPLETADA'";
$stmt = $conn->prepare($sql_completadas);
$stmt->bindParam(':id', $id_estudiante, PDO::PARAM_INT);
$stmt->execute();
$tutorias_completadas = $stmt->fetchColumn();

// ----------------------------------------------------
// D. Calificación Promedio (ELIMINADO el bloque de consulta SQL)
// ----------------------------------------------------
// Las variables $promedio y $calificacion_promedio ya no son necesarias.

// ----------------------------------------------------
// E. Próximas Tutorías (máximo 5)
// ----------------------------------------------------
$sql_proximas = "
    SELECT 
        s.fecha, 
        s.hora_inicio, 
        s.hora_fin,  
        m.nombre_materia, 
        CONCAT(t.nombre, ' ', t.apellido) AS tutor 
    FROM solicitudes_tutorias s
    JOIN usuarios t ON s.id_tutor = t.id
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN materias m ON o.id_materia = m.id
    WHERE s.id_estudiante = :id 
    AND s.estado = 'CONFIRMADA'
    AND s.fecha >= CURDATE()
    ORDER BY s.fecha ASC, s.hora_inicio ASC
    LIMIT 5
";
$stmt = $conn->prepare($sql_proximas);
$stmt->bindParam(':id', $id_estudiante, PDO::PARAM_INT);
$stmt->execute();
$proximas_tutorias_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear los datos para la tabla
$proximas_tutorias = array_map(function ($t) {
    return [
        'materia' => $t['nombre_materia'],
        'tutor' => $t['tutor'],
        'fecha' => (new DateTime($t['fecha']))->format('d/m/Y'),
        'hora_inicio' => (new DateTime($t['hora_inicio']))->format('h:i A'),
        'hora_fin' => (new DateTime($t['hora_fin']))->format('h:i A')
    ];
}, $proximas_tutorias_db);

// ----------------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Dashboard - Estudiante</title>
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
                <div class="container-fluid px-4"><br>
                    <?php if (isset($_SESSION['nombre'])): ?>
                        <h1>Bienvenido/a, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h1>
                    <?php endif; ?>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                    <!--Contenido-->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-md-4 col-sm-6 mb-4">
                                    <div class="card bg-primary text-white h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="small text-white-50">Tutorías Confirmadas</div>
                                                    <div class="h3 font-weight-bold">
                                                        <?php echo $tutorias_confirmadas; ?></div>
                                                </div>
                                                <i class="fas fa-book-open fa-2x"></i>
                                            </div>
                                        </div>
                                        <div class="card-footer d-flex align-items-center justify-content-between">
                                            <a class="small text-white stretched-link" href="VerTutorias.php">Ver
                                                detalles</a>
                                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-6 mb-4">
                                    <div class="card bg-warning text-white h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="small text-white-50">Solicitudes Pendientes</div>
                                                    <div class="h3 font-weight-bold">
                                                        <?php echo $solicitudes_pendientes; ?></div>
                                                </div>
                                                <i class="fas fa-envelope-open-text fa-2x"></i>
                                            </div>
                                        </div>
                                        <div class="card-footer d-flex align-items-center justify-content-between">
                                            <a class="small text-white stretched-link" href="MisSolicitudes.php">Revisar
                                                solicitudes</a>
                                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-12 mb-4">
                                    <div class="card bg-success text-white h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="small text-white-50">Tutorías Realizadas</div>
                                                    <div class="h3 font-weight-bold">
                                                        <?php echo $tutorias_completadas; ?></div>
                                                </div>
                                                <i class="fas fa-check-circle fa-2x"></i>
                                            </div>
                                        </div>
                                        <div class="card-footer d-flex align-items-center justify-content-between">
                                            <a class="small text-white stretched-link" href="Historial.php">Ver
                                                historial</a>
                                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-4 mb-4">
                            <div class="card bg-info text-white shadow h-100 p-3">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-search fa-4x mb-3"></i>
                                    <h5 class="text-center">¿Necesitas ayuda con algo más?</h5>
                                    <p class="text-center small">Encuentra tutores disponibles y agenda tu próxima
                                        sesión.</p>
                                    <a href="BuscarTutor.php" class="btn btn-light btn-lg mt-2">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        BUSCAR Y AGENDAR
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Próximas Tutorías Agendadas
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Materia</th>
                                                    <th>Tutor</th>
                                                    <th>Fecha</th>
                                                    <th>Hora de Inicio</th>
                                                    <th>Hora de Fin</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($proximas_tutorias)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">No tienes tutorías
                                                            confirmadas próximamente.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($proximas_tutorias as $t): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($t['materia']); ?></td>
                                                            <td><?php echo htmlspecialchars($t['tutor']); ?></td>
                                                            <td><?php echo htmlspecialchars($t['fecha']); ?></td>
                                                            <td><?php echo htmlspecialchars($t['hora_inicio']); ?></td>
                                                            <td><?php echo htmlspecialchars($t['hora_fin']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>

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