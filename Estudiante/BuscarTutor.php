<?php
// Incluir la navegación y la conexión a la base de datos
include 'Includes/Nav.php';

// 🛑 1. OBTENER DATOS PARA EL FILTRO DE MATERIAS
$materias_todas = [];
try {
    $sql_materias = "SELECT id, nombre_materia FROM materias ORDER BY nombre_materia";
    $stmt_materias = $conn->prepare($sql_materias);
    $stmt_materias->execute();
    $materias_todas = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar materias para el filtro: " . $e->getMessage());
}

// 🛑 2. CAPTURAR Y SANEAR LOS PARÁMETROS DE BÚSQUEDA
$filtro_materia_id = $_GET['materia'] ?? '';
$filtro_modalidad = $_GET['modalidad'] ?? '';
$filtro_precio_max = $_GET['precio_max'] ?? '';

// 🛑 3. CONSTRUIR LA CONSULTA SQL DINÁMICA
$where_clauses = ["ot.activo = 1"]; // Solo ofertas activas
$join_clauses = [];
$params = [];

// Filtro por Materia
if (!empty($filtro_materia_id) && is_numeric($filtro_materia_id)) {
    // Para buscar tutores que ofrezcan esta materia, ya sea como principal (ot.id_materia) o secundaria (tutor_materias)
    $where_clauses[] = "(ot.id_materia = :materia_id OR EXISTS (SELECT 1 FROM tutor_materias tm WHERE tm.id_tutor = u.id AND tm.id_materia = :materia_id))";
    $params[':materia_id'] = $filtro_materia_id;
}

// Filtro por Modalidad
if (!empty($filtro_modalidad)) {
    // Si la modalidad es "Ambas", el tutor debe tener "Presencial" o "Virtual" o "Ambas"
    if ($filtro_modalidad === 'Ambas') {
        $where_clauses[] = "u.modalidad_tutor IN ('Presencial', 'Virtual', 'Ambas')";
    } else {
        // Busca tutores que ofrezcan la modalidad exacta o "Ambas"
        $where_clauses[] = "(u.modalidad_tutor = :modalidad OR u.modalidad_tutor = 'Ambas')";
        $params[':modalidad'] = $filtro_modalidad;
    }
}

// Filtro por Precio Máximo
if (!empty($filtro_precio_max) && is_numeric($filtro_precio_max)) {
    $where_clauses[] = "ot.precio_hora <= :precio_max";
    $params[':precio_max'] = $filtro_precio_max;
}

// Juntar todas las cláusulas WHERE con AND
$where_sql = "WHERE " . implode(" AND ", $where_clauses);
$join_sql = implode(" ", array_unique($join_clauses)); // No se necesitan JOINs adicionales, las cláusulas EXISTS/OR lo manejan

// Consulta Base
$sql_ofertas = "
    SELECT DISTINCT
        ot.id AS oferta_id,
        ot.precio_hora,
        m.nombre_materia,
        u.id AS tutor_id,
        u.nombre,
        u.apellido,
        u.perfil_imagen,
        u.universidad_tutor,
        u.modalidad_tutor
    FROM 
        ofertas_tutorias ot
    JOIN 
        usuarios u ON ot.id_tutor = u.id 
    JOIN 
        materias m ON ot.id_materia = m.id 
    {$where_sql} 
    ORDER BY 
        ot.precio_hora ASC, u.nombre";

try {
    $stmt_ofertas = $conn->prepare($sql_ofertas);
    // Enlazar los parámetros
    foreach ($params as $key => $value) {
        $type = (strpos($key, 'id') !== false || strpos($key, 'precio') !== false) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt_ofertas->bindValue($key, $value, $type);
    }

    $stmt_ofertas->execute();
    $ofertas = $stmt_ofertas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en consulta de búsqueda: " . $e->getMessage());
    die("Error al cargar los tutores disponibles: " . $e->getMessage());
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
    <title>Tutores</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">


    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <!-- Panel Izquierdo -->
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Buscar Tutores</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tutores Disponibles</li>
                    </ol>
                    <!--Contendo-->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-filter me-1"></i> Filtros de Búsqueda</h5>
                                    <form action="BuscarTutor.php" method="GET">
                                        <div class="row g-3">

                                            <div class="col-md-4">
                                                <label for="materia" class="form-label">Materia</label>
                                                <select class="form-select" id="materia" name="materia">
                                                    <option value="">Todas las Materias</option>
                                                    <?php foreach ($materias_todas as $materia): ?>
                                                        <option value="<?= $materia['id'] ?>"
                                                            <?= ($filtro_materia_id == $materia['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($materia['nombre_materia']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="modalidad" class="form-label">Modalidad</label>
                                                <select class="form-select" id="modalidad" name="modalidad">
                                                    <option value="">Todas las Modalidades</option>
                                                    <option value="Presencial" <?= ($filtro_modalidad == 'Presencial') ? 'selected' : '' ?>>Presencial</option>
                                                    <option value="Virtual" <?= ($filtro_modalidad == 'Virtual') ? 'selected' : '' ?>>Virtual</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="precio_max" class="form-label">Precio Máximo ($)</label>
                                                <input type="number" step="1" min="1" class="form-control"
                                                    id="precio_max" name="precio_max" placeholder="Ej: 20"
                                                    value="<?= htmlspecialchars($filtro_precio_max) ?>">
                                            </div>

                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100 me-2">Buscar</button>
                                                <a href="BuscarTutor.php"
                                                    class="btn btn-outline-secondary w-100">Limpiar</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-search me-1"></i>
                                Mostrando <?= count($ofertas) ?> Tutores Encontrados
                            </div>
                            <div class="card-body">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mt-3">
                                    <?php if (count($ofertas) > 0): ?>
                                        <?php foreach ($ofertas as $oferta): // 🛑 Cambiamos $tutores a $ofertas ?>
                                            <?php
                                            // Determinar la ruta de la imagen del tutor
                                            $ruta_imagen = !empty($oferta['perfil_imagen']) ?
                                                '../' . htmlspecialchars($oferta['perfil_imagen']) :
                                                '../Assets/perfil_default.png';
                                            ?>

                                            <div class="col">
                                                <div class="card h-100 shadow-sm">
                                                    <div class="card-body d-flex flex-column">

                                                        <div class="d-flex align-items-center mb-3">
                                                            <img src="<?= $ruta_imagen ?>"
                                                                alt="Foto de <?= htmlspecialchars($oferta['nombre']) ?>"
                                                                class="rounded-circle me-3"
                                                                style="width: 60px; height: 60px; object-fit: cover;">
                                                            <div>
                                                                <h5 class="card-title mb-0">
                                                                    <?= htmlspecialchars($oferta['nombre'] . ' ' . $oferta['apellido']) ?>
                                                                </h5>
                                                                <small class="text-muted">Tutor</small>
                                                            </div>
                                                        </div>

                                                        <p class="card-text">
                                                            <strong>Materia:</strong>
                                                            <?= htmlspecialchars($oferta['nombre_materia']) ?>
                                                        </p>

                                                        <p class="card-text">
                                                            <strong>Precio por Hora:</strong>
                                                            $<?= number_format($oferta['precio_hora'], 2) ?>
                                                        </p>

                                                        <p class="card-text">
                                                            <strong>Modalidad:</strong>
                                                            <?= htmlspecialchars($oferta['modalidad_tutor']) ?>
                                                        </p>
                                                        <p class="card-text">
                                                            <strong>Universidad:</strong>
                                                            <?= htmlspecialchars($oferta['universidad_tutor']) ?>
                                                        </p>


                                                        <a href="perfil_tutor.php?tutor_id=<?= $oferta['tutor_id'] ?>"
                                                            class="btn btn-primary mt-auto">
                                                            Ver Detalles y Agendar Tutoría
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info" role="alert">
                                            No se encontraron ofertas de tutoría disponibles en este momento.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
</body>

</html>