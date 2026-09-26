<?php
include 'Includes/Nav.php';

include "../Includes/db.php"; // Incluye tu conexión a la BD
$tutor_id = $_SESSION['id'];

// 2. CONSULTA DE SOLICITUDES DIRIGIDAS AL TUTOR
// Solo mostramos las solicitudes PENDIENTES, ya que son las que requieren acción.
$sql = "
    SELECT
        s.id AS solicitud_id, 
        s.fecha, s.hora_inicio, s.hora_fin, s.duracion, s.precio_total, s.estado,
        m.nombre_materia AS materia,
        e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN usuarios e ON s.id_estudiante = e.id -- Unimos con el estudiante que hizo la solicitud
    JOIN materias m ON o.id_materia = m.id -- Obtenemos el nombre de la materia
    WHERE s.id_tutor = :tutor_id AND s.estado = 'PENDIENTE'
    ORDER BY s.fecha ASC, s.hora_inicio ASC;
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <?php ; ?>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <!-- Panel Izquierdo -->
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Gestionar Solicitudes</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active">Solicitudes Recibidas</li>
                    </ol>
                    <!--Contendo-->

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-bell me-1"></i>
                            Solicitudes de Reserva (PENDIENTES)
                        </div>
                        <div class="card-body">

                            <?php if (isset($_GET['success']) && $_GET['success'] == 'accion_exitosa'): ?>
                                <div class="alert alert-success">
                                    ✅ La solicitud fue procesada exitosamente.
                                </div>
                            <?php endif; ?>

                            <?php if (count($solicitudes) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Materia</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Duración</th>
                                                <th>Precio</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($solicitudes as $solicitud): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($solicitud['nombre_estudiante'] . ' ' . $solicitud['apellido_estudiante']) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($solicitud['materia']) ?></td>
                                                    <td><?= date('d/M/Y', strtotime($solicitud['fecha'])) ?></td>
                                                    <td><?= date('H:i', strtotime($solicitud['hora_inicio'])) . ' - ' . date('H:i', strtotime($solicitud['hora_fin'])) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($solicitud['duracion']) ?> hrs.</td>
                                                    <td>$<?= number_format($solicitud['precio_total'], 2) ?></td>
                                                    <td>
                                                        <form method="POST" action="procesar_tutor_accion.php"
                                                            style="display:inline;">
                                                            <input type="hidden" name="solicitud_id"
                                                                value="<?= $solicitud['solicitud_id'] ?>">
                                                            <input type="hidden" name="accion" value="ACEPTAR">
                                                            <button type="submit" class="btn btn-sm btn-success"
                                                                title="Aceptar Reserva">
                                                                <i class="fas fa-check"></i> Aceptar
                                                            </button>
                                                        </form>

                                                        <form method="POST" action="procesar_tutor_accion.php"
                                                            style="display:inline;">
                                                            <input type="hidden" name="solicitud_id"
                                                                value="<?= $solicitud['solicitud_id'] ?>">
                                                            <input type="hidden" name="accion" value="CANCELAR">
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                title="Rechazar Reserva">
                                                                <i class="fas fa-times"></i> Rechazar
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    No tienes solicitudes de tutoría PENDIENTES en este momento.
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </main>
            <!--Footer-->
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>

</html>