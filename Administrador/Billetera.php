<?php
session_start();
include '../Includes/db.php';

// 1. VERIFICACIÓN DE SESIÓN (Administrador)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    header("Location: ../Login.php");
    exit();
}

// 2. ID DE LA BILLETERA CENTRAL
$admin_billetera_id = 8;
$saldo_actual = 0.00;
$ganancia_neta_total = 0.00;
$error_billetera = null;

try {
    // 3. OBTENER SALDO ACTUAL (Fondo Bruto)
    $sql_saldo = "SELECT saldo FROM billeteras WHERE id = :id";
    $stmt_saldo = $conn->prepare($sql_saldo);
    $stmt_saldo->execute([':id' => $admin_billetera_id]);
    $saldo_temp = $stmt_saldo->fetchColumn();

    if ($saldo_temp !== false) {
        $saldo_actual = (float) $saldo_temp;
    } else {
        $error_billetera = "No se encontró la billetera central (ID: $admin_billetera_id).";
    }

    // 4. OBTENER GANANCIA NETA TOTAL (Comisiones Acumuladas)
    $sql_ganancia = "SELECT SUM(monto) FROM movimientos_billetera WHERE tipo = 'COMISION'";
    $stmt_ganancia = $conn->prepare($sql_ganancia);
    $stmt_ganancia->execute();
    $ganancia_neta_total_temp = $stmt_ganancia->fetchColumn();

    if ($ganancia_neta_total_temp !== false) {
        $ganancia_neta_total = (float) $ganancia_neta_total_temp;
    }

    // 5. OBTENER ÚLTIMOS MOVIMIENTOS (Solo de esta billetera)
    $sql_movimientos = "SELECT 
                            tipo, 
                            monto, 
                            referencia, 
                            fecha_movimiento 
                        FROM movimientos_billetera 
                        WHERE id_billetera = :id 
                        ORDER BY fecha_movimiento DESC 
                        LIMIT 10";
    $stmt_movimientos = $conn->prepare($sql_movimientos);
    $stmt_movimientos->execute([':id' => $admin_billetera_id]);
    $movimientos_recientes = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar datos de la billetera: " . $e->getMessage());
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
    <title>Billetera</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body>
    <?php include 'Includes/Nav.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Billetera</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Billetera</li>
                    </ol>
                    <div class="row mb-4">
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card bg-primary text-white shadow h-100">
                                <div class="card-body">
                                    <h6 class="text-uppercase text-white-50">Saldo Bruto Disponible (Fondo Mixto)</h6>
                                    <div class="display-5 fw-bold">$<?php echo number_format($saldo_actual, 2); ?></div>
                                    <div class="small text-white-50 mt-2">Dinero total bajo control de la plataforma.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card bg-success text-white shadow h-100">
                                <div class="card-body">
                                    <h6 class="text-uppercase text-white-50">Ganancia Neta Total (Comisiones Acumuladas)
                                    </h6>
                                    <div class="display-5 fw-bold">
                                        $<?php echo number_format($ganancia_neta_total, 2); ?></div>
                                    <div class="small text-white-50 mt-2">Total de ingresos por comisiones desde el
                                        inicio.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 col-xl-4 mb-4">
                            <div class="card shadow h-100 p-3 d-flex flex-column justify-content-center">
                                <p class="text-center text-muted mb-3">Operaciones de Ajuste o Recarga</p>
                                <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal"
                                    data-bs-target="#modalRecarga">
                                    <i class="fas fa-plus me-2"></i> Recarga / Ajuste
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error_billetera)): ?>
                        <div class="alert alert-danger mt-2"><?php echo $error_billetera; ?></div>
                    <?php endif; ?>
                </div>
            </main>
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>
    <div class="modal fade" id="modalRecarga" tabindex="-1" aria-labelledby="modalRecargaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="AjustarSaldo.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRecargaLabel">Ajustar Saldo de Plataforma</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="monto_ajuste" class="form-label">Monto de Ajuste ($)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="monto_ajuste"
                                name="monto" required>
                            <input type="hidden" name="id_billetera" value="<?php echo $admin_billetera_id; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="tipo_ajuste" class="form-label">Tipo de Operación</label>
                            <select class="form-select" id="tipo_ajuste" name="tipo" required>
                                <option value="RECARGA">Recarga (Aumenta Saldo)</option>
                                <option value="AJUSTE_NEGATIVO">Ajuste Negativo (Disminuye Saldo)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="referencia_ajuste" class="form-label">Referencia / Motivo</label>
                            <input type="text" class="form-control" id="referencia_ajuste" name="referencia" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>

    <script>
        document.getElementById('btnSubmitRetiro').addEventListener('click', function (e) {
            var montoInput = document.getElementById('monto_retiro');
            var monto = parseFloat(montoInput.value);
            var maxMonto = parseFloat(montoInput.max);

            if (monto > maxMonto) {
                alert('No puedes retirar más del saldo disponible.');
                e.preventDefault();
            }
        });
    </script>
</body>

</html>