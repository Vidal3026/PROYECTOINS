<?php
// Incluir la configuración de la sesión y la navegación
include 'Includes/Nav.php';
include '../Includes/Wallet.php';
include '../Includes/PayPal.php';
include '../Includes/Seguridad.php';

// 1. VERIFICACIÓN DE SESIÓN (Estudiante)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}
$id_estudiante = $_SESSION['id'];

$saldo_actual = obtener_saldo($conn, $id_estudiante);
$billetera_id = obtener_o_crear_billetera_id($conn, $id_estudiante);
$movimientos = obtener_historial_movimientos($conn, $billetera_id);
$paypal_activo = paypal_configurado();

// Manejo de alertas de recarga
$alert_message = '';
$alert_type = '';

if (isset($_GET['success'])) {
    $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Acción realizada con éxito.';
    $alert_type = 'success';
    $alert_message = "Éxito. {$msg}";
} elseif (isset($_GET['error'])) {
    $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Ocurrió un error inesperado.';
    $alert_type = 'danger';
    $alert_message = "Error: {$msg}";
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
                            <?php echo htmlspecialchars($alert_message); ?>
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

                    <?php render_info_comisiones('estudiante'); ?>

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
                <?php if ($paypal_activo): ?>
                    <!-- Recarga real con PayPal: el servidor crea la orden, la captura y recién entonces acredita -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRecargarFondosLabel">Recargar saldo con PayPal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (PAYPAL_MODO !== 'live'): ?>
                            <div class="alert alert-secondary small py-2">
                                Modo de pruebas (PayPal Sandbox): usa una cuenta de comprador de prueba; no se cobra dinero real.
                            </div>
                        <?php endif; ?>

                        <div class="form-group mb-3">
                            <label for="monto_recarga">Monto a Recargar (USD)</label>
                            <input type="number" step="0.01" min="<?= RECARGA_MINIMA ?>" max="<?= RECARGA_MAXIMA ?>"
                                class="form-control" id="monto_recarga" required
                                placeholder="Entre $<?= number_format(RECARGA_MINIMA, 2) ?> y $<?= number_format(RECARGA_MAXIMA, 2) ?>">
                        </div>

                        <div id="paypal_mensaje" class="alert d-none small" role="alert"></div>
                        <div id="paypal_botones"></div>

                        <p class="small text-muted mb-0 mt-2">
                            Paga con tu cuenta PayPal o con tarjeta. El saldo se acredita en cuanto PayPal confirma el cobro.
                        </p>
                    </div>
                <?php else: ?>
                <form action="procesar_recarga.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <div class="modal-body">
                        <div class="alert alert-warning small">
                            Modo simulación: PayPal no está configurado (ver Includes/config_local.example.php).
                        </div>
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
                            Acreditación Automática: Al presionar "Pagar", el monto se sumará a tu saldo de
                            inmediato.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Pagar y Recargar</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>

    <?php if ($paypal_activo): ?>
        <script src="https://www.paypal.com/sdk/js?client-id=<?= urlencode(PAYPAL_CLIENT_ID) ?>&currency=<?= urlencode(PAYPAL_MONEDA) ?>&intent=capture&components=buttons&disable-funding=paylater,venmo"></script>
        <script>
            (function () {
                const CSRF = <?= json_encode(csrf_token()) ?>;
                const MIN = <?= json_encode((float) RECARGA_MINIMA) ?>;
                const MAX = <?= json_encode((float) RECARGA_MAXIMA) ?>;
                const inputMonto = document.getElementById('monto_recarga');
                const caja = document.getElementById('paypal_mensaje');

                function mostrar(texto, tipo) {
                    caja.className = 'alert small alert-' + tipo;
                    caja.textContent = texto;
                }
                function ocultar() {
                    caja.className = 'alert d-none small';
                }
                async function llamar(url, datos) {
                    const respuesta = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                        body: JSON.stringify(datos)
                    });
                    let json = {};
                    try { json = await respuesta.json(); } catch (e) { /* respuesta vacía */ }
                    return { estado: respuesta.status, json };
                }

                paypal.Buttons({
                    style: { layout: 'vertical', label: 'pay' },

                    // El botón solo se habilita con un monto válido
                    onInit: function (data, actions) {
                        const validar = () => {
                            const monto = parseFloat(inputMonto.value);
                            (monto >= MIN && monto <= MAX) ? actions.enable() : actions.disable();
                        };
                        validar();
                        inputMonto.addEventListener('input', validar);
                    },
                    onClick: function () {
                        const monto = parseFloat(inputMonto.value);
                        if (!(monto >= MIN && monto <= MAX)) {
                            mostrar('Ingresa un monto entre $' + MIN.toFixed(2) + ' y $' + MAX.toFixed(2) + '.', 'warning');
                        } else {
                            ocultar();
                        }
                    },

                    // 1. El servidor registra la recarga y crea la orden en PayPal
                    createOrder: async function () {
                        const r = await llamar('paypal_crear_orden.php', { monto: parseFloat(inputMonto.value) });
                        if (!r.json.id) {
                            mostrar(r.json.error || 'No se pudo iniciar el pago.', 'danger');
                            throw new Error(r.json.error || 'crear_orden');
                        }
                        return r.json.id;
                    },

                    // 2. El estudiante aprobó: el servidor captura el cobro y acredita el saldo
                    onApprove: async function (data, actions) {
                        mostrar('Confirmando el pago con PayPal...', 'info');
                        const r = await llamar('paypal_capturar_orden.php', { orderID: data.orderID });
                        if (r.json.reintentar) {
                            mostrar(r.json.error, 'warning');
                            return actions.restart();
                        }
                        if (r.json.ok) {
                            window.location.href = r.json.redirect;
                            return;
                        }
                        mostrar(r.json.error || 'No se pudo confirmar el pago.', r.estado === 202 ? 'info' : 'danger');
                    },

                    onCancel: function () {
                        mostrar('Cancelaste el pago. No se realizó ningún cargo.', 'secondary');
                    },
                    onError: function (err) {
                        console.error(err);
                        if (caja.classList.contains('d-none')) {
                            mostrar('Ocurrió un error con PayPal. Inténtalo de nuevo.', 'danger');
                        }
                    }
                }).render('#paypal_botones');
            })();
        </script>
    <?php endif; ?>
</body>

</html>
