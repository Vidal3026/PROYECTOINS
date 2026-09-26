<?php
// Incluir la configuración de la sesión y la navegación
include 'Includes/Nav.php';
include '../Includes/Wallet.php';

// 1. VERIFICACIÓN DE SESIÓN (Tutor)
// Este bloque ha sido limpiado para evitar el error 'T_STRING' en la línea 9
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}
$id_estudiante = $_SESSION['id'];

// Asegúrate de que estas funciones existen en Wallet.php y usan la variable $id_estudiante
$saldo_actual = obtener_saldo($conn, $id_estudiante);
$billetera_id = obtener_o_crear_billetera_id($conn, $id_estudiante);
$movimientos = obtener_historial_movimientos($conn, $billetera_id);

// Manejo de alertas de recarga
$alert_message = '';
$alert_type = '';

if (isset($_GET['success'])) {
    $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Acción realizada con éxito.';
    $alert_type = 'success';
    $alert_message = "✅ Éxito. {$msg}";
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
    <title>Billetera</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body>
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

                    <?php if (!empty($alert_message)): ?>
                        <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $alert_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card bg-success text-white shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Saldo Disponible</h5>
                                    <h1 class="display-4">$ <?php echo number_format($saldo_actual, 2); ?></h1>

                                    <button class="btn btn-light btn-sm mt-3" data-bs-toggle="modal"
                                        data-bs-target="#modalRecargarFondos">
                                        <i class="fas fa-plus-circle"></i> Recargar Fondo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Historial de Transacciones</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Referencia</th>
                                        <th class="text-end">Monto (USD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movimientos)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Aún no tienes movimientos registrados.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($movimientos as $mov): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $mov['tipo'] === 'INGRESO' ? 'success' : 'danger'; ?> text-white">
                                                        <?php echo htmlspecialchars($mov['tipo']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($mov['referencia'] ?? 'N/A'); ?></td>
                                                <td class="text-end">$ <?php echo number_format($mov['monto'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
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

    <div class="modal fade" id="modalRecargarFondos" tabindex="-1" aria-labelledby="modalRecargarFondosLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="procesar_recarga.php" method="POST">
                    <div class="modal-body">
                        <p>Ingresa el monto que deseas recargar. El saldo se acreditará inmediatamente (simulación de
                            pago exitoso).</p>

                        <div class="form-group mb-3">
                            <label for="monto_recarga">Monto a Recargar (USD)</label>
                            <input type="number" step="0.01" min="5.00" class="form-control" id="monto_recarga"
                                name="monto" required placeholder="Mínimo $5.00">
                        </div>

                        <div class="form-group mb-3">
                            <label for="metodo_pago">Método de Pago Simulador</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                <option value="Pago Automático">Pago Automático (Simulación)</option>
                                <option value="Tarjeta de Credito">Tarjeta de Crédito</option>
                                <option value="PayPal">PayPal</option>
                            </select>
                        </div>

                        <div class="alert alert-success mt-3">
                            ✔️ Acreditación Automática: Al presionar "Pagar", el monto se sumará a tu saldo de
                            inmediato.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Pagar y Recargar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>

</html>