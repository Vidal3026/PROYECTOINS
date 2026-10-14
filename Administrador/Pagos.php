<?php
session_start();
include '../Includes/db.php';
include '../Includes/PayPal.php';

// 1. VERIFICACIÓN DE SESIÓN (Administrador)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    header("Location: ../Login.php");
    exit();
}

$configurado = paypal_configurado();
$url_webhook = APP_URL_BASE . '/paypal_webhook.php';
$es_local = (bool) preg_match('~^https?://(localhost|127\.0\.0\.1|\[::1\])(:\d+)?/~i', APP_URL_BASE . '/');

// 2. PRUEBA DE CONEXIÓN (obtiene un token con las credenciales configuradas)
$resultado_prueba = null;
if ($configurado && isset($_GET['probar'])) {
    try {
        paypal_obtener_token();
        $resultado_prueba = ['ok' => true, 'mensaje' => 'Conexión correcta: PayPal (' . PAYPAL_MODO . ') aceptó las credenciales.'];
    } catch (Exception $e) {
        $resultado_prueba = ['ok' => false, 'mensaje' => $e->getMessage()];
    }
}

// 3. ÚLTIMAS RECARGAS Y COMISIONES ACUMULADAS
$recargas = $conn->query("
    SELECT r.referencia, r.monto, r.metodo_pago, r.estado, r.fecha_solicitud, r.fecha_confirmacion,
           r.paypal_order_id, r.paypal_capture_id, u.nombre, u.apellido
    FROM recargas_estudiante r
    JOIN usuarios u ON u.id = r.id_estudiante
    ORDER BY r.id DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$comisiones = $conn->query("
    SELECT
        COALESCE(SUM(CASE WHEN referencia LIKE 'Comisión Plataforma (tarifa de servicio%' OR referencia LIKE 'Comisión Plataforma (estudiante%' THEN monto END), 0) AS estudiante,
        COALESCE(SUM(CASE WHEN referencia LIKE 'Comisión Plataforma (comisión de tutor%' OR referencia LIKE 'Comisión Plataforma (tutor%' THEN monto END), 0) AS tutor,
        COALESCE(SUM(monto), 0) AS total
    FROM movimientos_billetera
    WHERE tipo = 'COMISION'
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Pagos y Comisiones</title>
    <link href="css/styles.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Pagos y Comisiones</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">PayPal y comisiones</li>
                    </ol>

                    <div class="row">
                        <div class="col-lg-7 mb-4">
                            <div class="card h-100">
                                <div class="card-header"><i class="fab fa-paypal me-1"></i> Estado de PayPal</div>
                                <div class="card-body">
                                    <?php if ($configurado): ?>
                                        <p>
                                            <span class="badge bg-success">Activo</span>
                                            <span class="badge bg-<?= PAYPAL_MODO === 'live' ? 'danger' : 'info text-dark' ?>">
                                                <?= PAYPAL_MODO === 'live' ? 'Producción: cobros reales' : 'Sandbox: pruebas sin dinero real' ?>
                                            </span>
                                        </p>
                                        <p class="small">Los estudiantes recargan saldo con el botón de PayPal de su Billetera.</p>
                                    <?php else: ?>
                                        <p><span class="badge bg-secondary">Modo simulación</span> No hay credenciales de PayPal:
                                            las recargas se acreditan sin cobro real.</p>
                                        <ol class="small">
                                            <li>Entra a <a href="https://developer.paypal.com" target="_blank" rel="noopener">developer.paypal.com</a>
                                                con una cuenta PayPal (gratis).</li>
                                            <li>En <strong>Apps &amp; Credentials</strong>, modo <strong>Sandbox</strong>, abre tu app
                                                (o crea una) y copia el <strong>Client ID</strong> y el <strong>Secret</strong>.</li>
                                            <li>Pégalos en <code>Includes/config_local.php</code> (<code>PAYPAL_CLIENT_ID</code> y
                                                <code>PAYPAL_CLIENT_SECRET</code>) y recarga esta página.</li>
                                        </ol>
                                    <?php endif; ?>

                                    <table class="table table-sm small">
                                        <tr><th>Modo</th><td><code><?= htmlspecialchars(PAYPAL_MODO) ?></code></td></tr>
                                        <tr><th>Moneda</th><td><?= htmlspecialchars(PAYPAL_MONEDA) ?></td></tr>
                                        <tr><th>Webhook (opcional)</th><td>
                                            <?php if (PAYPAL_WEBHOOK_ID !== ''): ?>
                                                <span class="badge bg-success">Configurado</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin configurar</span>
                                            <?php endif; ?>
                                            <code><?= htmlspecialchars($url_webhook) ?></code>
                                        </td></tr>
                                    </table>

                                    <p class="small text-muted">
                                        El saldo se acredita cuando el servidor captura el pago, así que el flujo funciona completo
                                        en <code>localhost</code>. El webhook es un respaldo para pagos que PayPal deja en revisión o
                                        cuando el estudiante cierra el navegador antes de terminar<?= $es_local ? '; necesita una URL pública (por ejemplo, con ngrok)' : '' ?>.
                                    </p>

                                    <?php if ($resultado_prueba): ?>
                                        <div class="alert alert-<?= $resultado_prueba['ok'] ? 'success' : 'danger' ?>">
                                            <?= htmlspecialchars($resultado_prueba['mensaje']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <a href="Pagos.php?probar=1" class="btn btn-primary <?= $configurado ? '' : 'disabled' ?>">
                                        <i class="fas fa-plug"></i> Probar conexión
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 mb-4">
                            <div class="card h-100">
                                <div class="card-header"><i class="fas fa-percent me-1"></i> Comisiones de la plataforma</div>
                                <div class="card-body">
                                    <p class="small text-muted">Configurables en <code>Includes/config_app.php</code>.</p>
                                    <table class="table table-sm">
                                        <tr><td>Tarifa de servicio (estudiante, <?= porcentaje_comision(COMISION_ESTUDIANTE) ?>%)</td>
                                            <td class="text-end">$<?= number_format($comisiones['estudiante'], 2) ?></td></tr>
                                        <tr><td>Comisión de tutor (<?= porcentaje_comision(COMISION_TUTOR) ?>%)</td>
                                            <td class="text-end">$<?= number_format($comisiones['tutor'], 2) ?></td></tr>
                                        <tr class="fw-bold"><td>Total acumulado (incluye pagos anteriores)</td>
                                            <td class="text-end">$<?= number_format($comisiones['total'], 2) ?></td></tr>
                                    </table>
                                    <p class="small mb-0">La plataforma retiene en total el <?= porcentaje_comision_total() ?>% de cada pago.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-list me-1"></i> Últimas recargas de estudiantes</div>
                        <div class="card-body table-responsive">
                            <table class="table table-striped table-sm small">
                                <thead>
                                    <tr><th>Estudiante</th><th>Referencia</th><th>Método</th><th class="text-end">Monto</th>
                                        <th>Estado</th><th>Solicitada</th><th>Confirmada</th><th>Orden PayPal</th><th>Captura PayPal</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recargas)): ?>
                                        <tr><td colspan="9" class="text-center">Aún no hay recargas.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($recargas as $r): ?>
                                        <?php $color = ['COMPLETADO' => 'success', 'PENDIENTE' => 'warning text-dark', 'FALLIDO' => 'danger'][$r['estado']] ?? 'secondary'; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                                            <td><code><?= htmlspecialchars($r['referencia'] ?? '') ?></code></td>
                                            <td><?= htmlspecialchars($r['metodo_pago'] ?? '') ?></td>
                                            <td class="text-end">$<?= number_format($r['monto'], 2) ?></td>
                                            <td><span class="badge bg-<?= $color ?>"><?= htmlspecialchars($r['estado']) ?></span></td>
                                            <td><?= htmlspecialchars($r['fecha_solicitud'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($r['fecha_confirmacion'] ?? '-') ?></td>
                                            <td><code><?= htmlspecialchars($r['paypal_order_id'] ?? '-') ?></code></td>
                                            <td><code><?= htmlspecialchars($r['paypal_capture_id'] ?? '-') ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
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
</body>

</html>
