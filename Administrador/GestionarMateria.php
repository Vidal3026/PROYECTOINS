<?php
session_start();
include '../Includes/db.php';

// Verificación de sesión (Administrador)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    header("Location: ../Login.php");
    exit();
}

$mensaje = '';

// --- A. Procesar Solicitudes de Gestión (Crear/Editar/Borrar) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Agregar/Editar Materia
    if (isset($_POST['nombre_materia'])) { // Usamos el nombre del campo del formulario
        $nombre_materia = trim($_POST['nombre_materia']);
        $id_materia = $_POST['id_materia'] ?? null;
        // La descripción es opcional en tu DB, por eso no la requerimos en el CRUD básico.

        if (!empty($nombre_materia)) {
            try {
                if ($id_materia) {
                    // EDITAR MATERIA
                    // La columna se llama nombre_materia
                    $sql = "UPDATE materias SET nombre_materia = :nombre_materia WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':nombre_materia' => $nombre_materia, ':id' => $id_materia]);
                    $mensaje = '<div class="alert alert-success">Materia actualizada con éxito.</div>';
                } else {
                    // CREAR NUEVA MATERIA
                    // La columna se llama nombre_materia
                    $sql = "INSERT INTO materias (nombre_materia) VALUES (:nombre_materia)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':nombre_materia' => $nombre_materia]);
                    $mensaje = '<div class="alert alert-success">Nueva materia creada con éxito.</div>';
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $mensaje = '<div class="alert alert-warning">Error: La materia ya existe (nombre duplicado).</div>';
                } else {
                    $mensaje = '<div class="alert alert-danger">Error de base de datos: ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // 2. Borrar Materia
    $id_materia = $_GET['id'];
    try {
        $sql = "DELETE FROM materias WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id_materia]);

        if ($stmt->rowCount() > 0) {
            $mensaje = '<div class="alert alert-success">Materia eliminada con éxito.</div>';
        } else {
            $mensaje = '<div class="alert alert-warning">Materia no encontrada o no se pudo eliminar.</div>';
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $mensaje = '<div class="alert alert-danger">No se puede eliminar: Esta materia está asociada a tutorías o usuarios.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error de base de datos: ' . $e->getMessage() . '</div>';
        }
    }
}

// --- B. Obtener Listado de Materias ---
try {
    // La columna se llama nombre_materia
    $sql_list = "SELECT id, nombre_materia FROM materias ORDER BY nombre_materia ASC";
    $stmt_list = $conn->query($sql_list);
    $materias = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar las materias: " . $e->getMessage());
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
    <title>Meterias</title>
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
                    <h1 class="mt-4">Gestionar Materias</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Gestionar Materias</li>
                    </ol>
                    <!--Contendo-->
                    <?php echo $mensaje; ?>

                    <div class="card mb-4 shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-table me-1"></i> Listado de Materias</div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#materiaModal"
                                onclick="limpiarModal()">
                                <i class="fas fa-plus me-2"></i> Agregar Nueva Materia
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre de la Materia</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materias as $materia): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($materia['id']); ?></td>
                                            <td><?php echo htmlspecialchars($materia['nombre_materia']); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#materiaModal"
                                                    onclick="cargarDatos(<?php echo $materia['id']; ?>, '<?php echo htmlspecialchars($materia['nombre_materia']); ?>')">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>

                                                <a href="GestionarMateria.php?action=delete&id=<?php echo $materia['id']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('¿Estás seguro de que quieres eliminar esta materia? Esto podría afectar tutorías existentes.')">
                                                    <i class="fas fa-trash"></i> Borrar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
            <!--Footer-->
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="materiaModal" tabindex="-1" aria-labelledby="materiaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="GestionarMateria.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="materiaModalLabel">Agregar Nueva Materia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_materia" id="id_materia">
                        <div class="mb-3">
                            <label for="nombre_materia" class="form-label">Nombre de la Materia</label>
                            <input type="text" class="form-control" id="nombre_materia_input" name="nombre_materia" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Materia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });

        // Función para limpiar el modal y prepararlo para "Agregar"
        function limpiarModal() {
            document.getElementById('materiaModalLabel').innerText = 'Agregar Nueva Materia';
            document.getElementById('id_materia').value = '';
            document.getElementById('nombre_materia_input').value = ''; // Usamos el ID del input
        }

        // Función para cargar datos al modal y prepararlo para "Editar"
        function cargarDatos(id, nombre_materia) {
            document.getElementById('materiaModalLabel').innerText = 'Editar Materia';
            document.getElementById('id_materia').value = id;
            document.getElementById('nombre_materia_input').value = nombre_materia; // Usamos el ID del input
        }
    </script>
</body>

</html>