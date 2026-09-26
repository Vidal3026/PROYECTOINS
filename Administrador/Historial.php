<?php
session_start();
include '../Includes/db.php'; 

// 1. VERIFICACIÓN DE SESIÓN (Administrador)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    header("Location: ../Login.php");
    exit();
}

// 2. CONFIGURACIÓN INICIAL Y FILTROS
$fecha_fin = date('Y-m-d');
$fecha_inicio = date('Y-m-d', strtotime('-30 days')); // Rango por defecto de 30 días
$tipo_filtro = $_GET['tipo'] ?? 'TODOS';

// Procesar el rango de fechas si se envió el formulario
if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin = $_GET['fecha_fin'];
}

// 3. CONSULTA SQL DINÁMICA
// Usamos el id_billetera = 1 como la billetera central del administrador para el historial,
// aunque si el Admin quiere ver TODO el historial de TODAS las billeteras, se quita el filtro id_billetera.
// Asumiremos que el Admin quiere ver solo los movimientos que afectan a la plataforma (o a sus ganancias/comisiones).

$sql = "SELECT 
            m.id, 
            m.id_billetera, 
            m.tipo, 
            m.monto, 
            m.referencia, 
            m.fecha_movimiento,
            u.nombre AS usuario_billetera
        FROM movimientos_billetera m  -- ¡CORRECCIÓN DEL NOMBRE DE LA TABLA!
        
        -- Y usamos b.id para la unión
        LEFT JOIN billeteras b ON m.id_billetera = b.id 
        
        LEFT JOIN usuarios u ON b.id_usuario = u.id
        WHERE m.fecha_movimiento BETWEEN :fi AND DATE_ADD(:ff, INTERVAL 1 DAY)";

// Añadir filtro por tipo si no es 'TODOS'
if ($tipo_filtro !== 'TODOS') {
    $sql .= " AND m.tipo = :tipo";
}

$sql .= " ORDER BY m.id ASC";

try {
    $stmt = $conn->prepare($sql);
    $params = [
        ':fi' => $fecha_inicio,
        ':ff' => $fecha_fin
    ];

    if ($tipo_filtro !== 'TODOS') {
        $params[':tipo'] = $tipo_filtro;
    }

    $stmt->execute($params);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar el historial de movimientos: " . $e->getMessage());
}

// Tipos de movimiento (para el selector de filtro)
$tipos_movimiento = ['TODOS', 'INGRESO', 'EGRESO', 'COMISION', 'RECARGA', 'RETIRO']; // Ajusta según tus tipos
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Historial de movimientos financieros para el administrador." />
    <meta name="author" content="" />
    <title>Historial de Movimientos</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Historial de Movimientos</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Movimientos</li>
                    </ol>

                    <div class="card mb-4 shadow">
                        <div class="card-header">
                            <i class="fas fa-filter me-1"></i>
                            Filtro de Historial
                        </div>
                        <div class="card-body">
                            <form method="GET" action="HistorialMovimientos.php">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                            value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                            value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="tipo_filtro" class="form-label">Tipo de Movimiento</label>
                                        <select class="form-select" id="tipo_filtro" name="tipo">
                                            <?php foreach ($tipos_movimiento as $tipo): ?>
                                                <option value="<?php echo $tipo; ?>" 
                                                    <?php echo ($tipo_filtro === $tipo) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst(strtolower($tipo)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">Aplicar Filtro</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4 shadow">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Detalle de Movimientos (<?php echo htmlspecialchars($fecha_inicio); ?> a <?php echo htmlspecialchars($fecha_fin); ?>)
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Monto</th>
                                        <th>Referencia</th>
                                        <th>Fecha</th>
                                        <th>ID Billetera</th>
                                        <th>Usuario (Billetera)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($movimientos)): ?>
                                        <?php foreach ($movimientos as $mov): 
                                            // Asignar una clase para el color del monto
                                            $color_clase = '';
                                            if ($mov['tipo'] === 'INGRESO' || $mov['tipo'] === 'COMISION' || $mov['tipo'] === 'RECARGA') {
                                                $color_clase = 'text-success fw-bold';
                                            } elseif ($mov['tipo'] === 'EGRESO' || $mov['tipo'] === 'RETIRO') {
                                                $color_clase = 'text-danger fw-bold';
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($mov['id']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($mov['tipo']); ?></span></td>
                                            <td class="<?php echo $color_clase; ?>">$<?php echo number_format($mov['monto'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($mov['referencia']); ?></td>
                                            <td><?php echo date('d/M/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                            <td><?php echo htmlspecialchars($mov['id_billetera']); ?></td>
                                            <td><?php echo htmlspecialchars($mov['usuario_billetera']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No se encontraron movimientos para los filtros seleccionados.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div>
            </main>
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script>
        // Inicializar Simple Datatables
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });
    </script>
</body>

</html>