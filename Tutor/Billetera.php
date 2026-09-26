<?php
// Incluir la configuración de la sesión y la navegación
include 'Includes/Nav.php';
include '../Includes/Wallet.php';

// 1. VERIFICACIÓN DE SESIÓN (Tutor)
// Este bloque ha sido limpiado para evitar el error 'T_STRING' en la línea 9
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];

// Obtener el saldo y el ID de la billetera
$saldo_actual = obtener_saldo($conn, $id_tutor);
$billetera_id = obtener_o_crear_billetera_id($conn, $id_tutor);
$movimientos = obtener_historial_movimientos($conn, $billetera_id);

// ==============================================
// MANEJO DE ALERTAS (Éxito y Error)
// ==============================================
$alert_message = '';
$alert_type = '';

if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    $message = isset($_GET['msg']) ? urldecode($_GET['msg']) : '';

    switch ($error_code) {
        case 'saldo_insuficiente':
            $alert_type = 'danger';
            $alert_message = "❌ Error de Retiro: {$message}. No puedes solicitar un monto mayor a tu saldo disponible.";
            break;
        case 'campos_incompletos':
            $alert_type = 'warning';
            $alert_message = "Por favor, completa todos los campos del formulario de retiro (Monto, Método y Detalles).";
            break;
        case 'db_retiro':
            $alert_type = 'danger';
            $alert_message = "Error en la base de datos al registrar la solicitud de retiro. Inténtalo más tarde.";
            break;
        default:
            $alert_type = 'danger';
            $alert_message = "Ocurrió un error inesperado al procesar la solicitud.";
            break;
    }
} elseif (isset($_GET['success'])) {
    $success_code = $_GET['success'];

    switch ($success_code) {
        case 'retiro_pendiente':
            $alert_type = 'info';
            $alert_message = "✅ Solicitud Enviada. Tu solicitud de retiro está en estado PENDIENTE y será revisada por el administrador en las próximas 48 horas.";
            break;
    }
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
                                    <h5 class="card-title">Saldo Disponible para Retiro</h5>
                                    <h1 class="display-4">$ <?php echo number_format($saldo_actual, 2); ?></h1>

                                    <button class="btn btn-light btn-sm mt-3" data-bs-toggle="modal"
                                        data-bs-target="#modalRetiro">
                                        Solicitar Retiro
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
                                        <th class="text-right">Monto (USD)</th>
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
                                                <td class="text-right">$ <?php echo number_format($mov['monto'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="modal fade" id="modalRetiro" tabindex="-1" aria-labelledby="modalRetiroLabel"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalRetiroLabel">Solicitar Retiro de Fondos</h5>

                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <form action="procesar_retiro.php" method="POST">
                                <div class="modal-body">
                                    <p class="alert alert-info">Tu saldo actual disponible es:
                                        <strong>$<?php echo number_format($saldo_actual, 2); ?></strong>
                                    </p>

                                    <div class="form-group mb-3">
                                        <label for="monto_retiro">Monto a Retirar (Mín. $10.00)</label>
                                        <input type="number" step="0.01" min="10.00" class="form-control"
                                            id="monto_retiro" name="monto_retiro" required>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="metodo_pago">Método de Pago</label>
                                        <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PayPal">PayPal</option>
                                            <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="datos_pago">Detalles del Pago (Ej. Email de PayPal, o
                                            Cuenta/Banco)</label>
                                        <textarea class="form-control" id="datos_pago" name="datos_pago" rows="3"
                                            required></textarea>
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success">Confirmar Solicitud</button>
                                </div>
                            </form>
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

</body>

</html>