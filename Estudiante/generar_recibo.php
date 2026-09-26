<?php
// Incluir archivos de conexión y navegación (aunque aquí no se usa la navegación completa)
include 'Includes/config.php';
include "../Includes/db.php"; 

// 1. VERIFICACIÓN DE SESIÓN (Estudiante)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}

$id_estudiante = $_SESSION['id'];

// 2. OBTENER ID DE LA SOLICITUD
$solicitud_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($solicitud_id === 0) {
    die("Error: ID de solicitud no proporcionado.");
}

// 3. CONSULTA DE DETALLES DEL RECIBO
$sql = "
    SELECT
        s.id, s.fecha, s.hora_inicio, s.duracion, s.precio_total, s.fecha_pago,
        s.estado,
        m.nombre_materia AS materia,
        t.nombre AS nombre_tutor, t.apellido AS apellido_tutor,
        e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN usuarios t ON s.id_tutor = t.id 
    JOIN usuarios e ON s.id_estudiante = e.id 
    JOIN materias m ON o.id_materia = m.id 
    WHERE s.id = :solicitud_id 
    AND s.id_estudiante = :id_estudiante
    AND s.estado IN ('CONFIRMADA', 'COMPLETADA');
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
    $stmt->bindParam(':id_estudiante', $id_estudiante, PDO::PARAM_INT);
    $stmt->execute();
    $recibo = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar datos del recibo: " . $e->getMessage());
    die("Error en la base de datos al generar el recibo.");
}

// 4. VERIFICAR RESULTADOS
if (!$recibo) {
    die("Error: Solicitud no encontrada o no pagada (estado debe ser CONFIRMADA o COMPLETADA).");
}

// Formatear datos
$fecha_clase = date('d/M/Y', strtotime($recibo['fecha']));
$hora_clase = date('H:i', strtotime($recibo['hora_inicio']));
$fecha_pago = date('d/M/Y H:i', strtotime($recibo['fecha_pago']));
$nombre_tutor = htmlspecialchars($recibo['nombre_tutor'] . ' ' . $recibo['apellido_tutor']);
$nombre_estudiante = htmlspecialchars($recibo['nombre_estudiante'] . ' ' . $recibo['apellido_estudiante']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago - Solicitud #<?= $recibo['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Estilos para impresión */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .recibo-box {
                box-shadow: none !important;
                border: 1px solid #ccc;
                max-width: 800px;
                margin: 20px auto;
            }
        }
        /* Estilos generales de la caja de recibo */
        .recibo-container {
            padding: 20px;
            max-width: 600px; /* Ancho típico de un recibo */
            margin: 50px auto;
        }
        .recibo-box {
            border: 1px solid #dee2e6;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .recibo-header h1 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #007bff;
        }
        .recibo-detail p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body class="bg-light">

    <div class="recibo-container">
        <div class="no-print text-center mb-3">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="fas fa-print"></i> Imprimir Recibo
            </button>
            <a href="VerTutorias.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Tutorías
            </a>
        </div>

        <div class="recibo-box">
            <div class="recibo-header text-center">
                <h2 class="mb-0">TutoLink</h2>
                <h1 class="h3">RECIBO DE PAGO</h1>
                <p class="text-muted small">Comprobante generado el: <?= date('d/M/Y H:i') ?></p>
            </div>

            <hr>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>DATOS DEL PAGO</h5>
                    <p><strong>N° Recibo/ID:</strong> #<?= htmlspecialchars($recibo['id']) ?></p>
                    <p><strong>Fecha de Pago:</strong> <?= $fecha_pago ?></p>
                    <p><strong>Estado:</strong> <span class="badge bg-primary"><?= htmlspecialchars($recibo['estado']) ?></span></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>MONTO TOTAL</h5>
                    <h1 class="text-success display-4">$<?= number_format($recibo['precio_total'], 2) ?></h1>
                </div>
            </div>

            <hr>
            
            <h5 class="mt-4 mb-3">DETALLES DEL SERVICIO</h5>
            <div class="recibo-detail">
                <p><strong>Materia:</strong> <span><?= htmlspecialchars($recibo['materia']) ?></span></p>
                <p><strong>Tutor:</strong> <?= $nombre_tutor ?></p>
                <p><strong>Estudiante:</strong> <?= $nombre_estudiante ?></p>
                <p><strong>Fecha y Hora:</strong> <?= $fecha_clase ?> @ <?= $hora_clase ?></p>
                <p><strong>Duración:</strong> <?= htmlspecialchars($recibo['duracion']) ?> horas</p>
            </div>
            
            <hr class="my-4">

            <div class="text-center small text-muted">
                Este comprobante certifica la confirmación y el pago de la tutoría en la plataforma TutoLink.
                Conserve este recibo para cualquier aclaración futura.
            </div>
        </div>
    </div>

    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</body>
</html>