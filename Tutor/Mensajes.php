<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Mensajes</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Historial de Mensajes del Chat</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Mensajes</li>
                    </ol>

                    <?php if (empty($grouped_messages)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i> No hay mensajes registrados en el sistema de chat
                            (`chat_data.json`).
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="messagesAccordion">

                            <?php foreach ($grouped_messages as $solicitud_id => $messages): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading_<?= $solicitud_id ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse_<?= $solicitud_id ?>" aria-expanded="false"
                                            aria-controls="collapse_<?= $solicitud_id ?>">
                                            <i class="fas fa-comments me-2"></i> **Tutoría ID
                                            #<?= htmlspecialchars($solicitud_id) ?>** <span
                                                class="badge bg-primary ms-3"><?= count($messages) ?> mensajes</span>
                                        </button>
                                    </h2>
                                    <div id="collapse_<?= $solicitud_id ?>" class="accordion-collapse collapse"
                                        aria-labelledby="heading_<?= $solicitud_id ?>" data-bs-parent="#messagesAccordion">
                                        <div class="accordion-body">

                                            <div class="chat-history p-3 border rounded"
                                                style="max-height: 400px; overflow-y: auto;">
                                                <?php foreach ($messages as $msg):
                                                    // Asume que el ID del Estudiante (1) y Tutor (2) son roles para diferenciar
                                                    $is_student = ($msg['id_usuario'] == 1); // Ajusta esta lógica si tienes IDs reales
                                                    $alignment = $is_student ? 'end' : 'start';
                                                    $color = $is_student ? 'bg-light text-dark' : 'bg-success text-white';
                                                    $name_display = htmlspecialchars($msg['usuario']);
                                                    ?>
                                                    <div class="d-flex justify-content-<?= $alignment ?> mb-2">
                                                        <div class="p-2 rounded <?= $color ?>" style="max-width: 80%;">
                                                            <p class="mb-0 fw-bold small"><?= $name_display ?></p>
                                                            <p class="mb-0"><?= htmlspecialchars($msg['mensaje']) ?></p>
                                                            <span class="text-muted small d-block text-end"
                                                                style="font-size: 0.7rem;">
                                                                <i class="far fa-clock"></i> <?= htmlspecialchars($msg['hora']) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    <?php endif; ?>

                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
</body>

</html>