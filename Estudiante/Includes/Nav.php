<?php
include "config.php";

$notificaciones_horario = [];

if (($usuario['rol'] ?? '') === 'estudiante') {
    try {
        asegurarTablaNotificacionesHorario($conn);

        $sql_notificaciones = "
            SELECT
                n.id AS notificacion_id,
                n.id_solicitud AS solicitud_id,
                n.fecha_tutoria AS fecha,
                n.hora_anterior_inicio AS hora_inicio,
                n.hora_anterior_fin AS hora_fin,
                n.horario_nuevo,
                n.mensaje,
                n.creada_en,
                m.nombre_materia AS materia,
                CONCAT(t.nombre, ' ', t.apellido) AS tutor
            FROM notificaciones_horario n
            INNER JOIN solicitudes_tutorias s
                ON s.id = n.id_solicitud
               AND s.id_estudiante = n.id_estudiante
            INNER JOIN materias m ON m.id = n.id_materia
            INNER JOIN usuarios t ON t.id = n.id_tutor
            WHERE n.id_estudiante = :estudiante_id
              AND n.leida = 0
              AND s.estado = 'CONFIRMADA'
              AND TIMESTAMP(n.fecha_tutoria, n.hora_anterior_fin) >= NOW()
            ORDER BY n.creada_en DESC
            LIMIT 20
        ";

        $stmt_notificaciones = $conn->prepare($sql_notificaciones);
        $stmt_notificaciones->bindParam(':estudiante_id', $id_usuario, PDO::PARAM_INT);
        $stmt_notificaciones->execute();
        $notificaciones_horario = $stmt_notificaciones->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error al cargar notificaciones de horario: ' . $e->getMessage());
    }
}

$total_notificaciones_horario = count($notificaciones_horario);
?>
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="index.php">TutoLink</a>

    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Navbar Search-->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <div class="input-group">
            <input class="form-control" type="text" placeholder="Buscar" aria-label="Search for..."
                aria-describedby="btnNavbarSearch" />
            <button class="btn btn-primary" id="btnNavbarSearch" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <!-- Notificaciones persistentes de cambios de horario -->
    <ul class="navbar-nav ms-auto ms-md-0">
        <li class="nav-item dropdown me-2">
            <a class="nav-link position-relative" id="notificacionesDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones de horario">
                <i class="fas fa-bell"></i>
                <?php if ($total_notificaciones_horario > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $total_notificaciones_horario ?>
                        <span class="visually-hidden">cambios de horario pendientes</span>
                    </span>
                <?php endif; ?>
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notificacionesDropdown"
                style="min-width: 380px; max-width: 460px;">
                <li class="dropdown-header fw-bold">Cambios en tus materias inscritas</li>

                <?php if ($total_notificaciones_horario === 0): ?>
                    <li>
                        <span class="dropdown-item-text text-muted small">
                            No tienes cambios de horario pendientes.
                        </span>
                    </li>
                <?php else: ?>
                    <?php foreach ($notificaciones_horario as $notificacion): ?>
                        <li>
                            <a class="dropdown-item text-wrap py-2"
                                href="VerTutorias.php#tutoria-<?= (int) $notificacion['solicitud_id'] ?>">
                                <div class="fw-bold text-warning">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= htmlspecialchars($notificacion['materia']) ?>
                                </div>
                                <div class="small">
                                    <?= htmlspecialchars($notificacion['mensaje']) ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    Nuevo horario publicado:
                                    <?= htmlspecialchars($notificacion['horario_nuevo']) ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>

                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-center small fw-bold" href="VerTutorias.php">
                            Ver tutorías inscritas
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </li>
    </ul>

    <!-- Perfil -->
    <ul class="navbar-nav me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo !empty($usuario['perfil_imagen']) ? '../' . htmlspecialchars($usuario['perfil_imagen']) : '../Assets/perfil_default.png'; ?>"
                    alt="Foto de Perfil" class="rounded-circle"
                    style="width: 35px; height: 35px; object-fit: cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="Configperfil_usuario.php">Perfil</a></li>
                <li><a class="dropdown-item" href="404.html">Pagina de prueba</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="cerrarSesion.php">Cerrar Sesión</a></li>
            </ul>
        </li>
    </ul>
</nav>
