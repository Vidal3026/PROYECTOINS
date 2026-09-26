<?php
session_start();

// Código para evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verifica si la sesión está activa y si el rol coincide
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    // Si la sesión no existe o el rol es incorrecto, redirige al login
    header("Location: ../Login.php");
    exit();
}

// Incluye el archivo de conexión a la base de datos
include "../Includes/db.php";

// Lógica de filtro: verifica si se ha pasado un rol por la URL
$rol_filtrado = $_GET['rol_filtrado'] ?? '';
$sql = "SELECT id, nombre, apellido, correo, rol FROM usuarios";
$parametros = [];

if ($rol_filtrado && ($rol_filtrado === 'estudiante' || $rol_filtrado === 'tutor')) {
    $sql .= " WHERE rol = :rol_filtrado";
    $parametros[':rol_filtrado'] = $rol_filtrado;
}
$sql .= " ORDER BY id ASC";

// Consulta para obtener los usuarios
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($parametros);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
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
    <title>Gestionar Estudiante</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <h1 class="mt-4">Gestionar Estudiante</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Gestionar Usuarios</li>
                    </ol>

                    <!--Contenido-->
                    <div class="mb-3">
                        <a href="?rol_filtrado=estudiante" class="btn btn-primary">Ver Estudiantes</a>
                        <a href="?rol_filtrado=tutor" class="btn btn-primary">Ver Tutores</a>
                        <a href="GestionarUsuarios.php" class="btn btn-secondary">Ver Todos</a>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Lista de Usuarios
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Apellido</th>
                                            <th>Correo</th>
                                            <th>Rol</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                                                <td>
                                                    <a href="#" class="btn btn-warning btn-sm editar-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editarUsuarioModal"
                                                        data-id="<?php echo $usuario['id']; ?>">Editar</a>
                                                    <a href="eliminar_usuario.php?id=<?php echo $usuario['id']; ?>"
                                                        class="btn btn-danger btn-sm eliminar-btn">Eliminar</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!--Modal-->
                    <div class="modal fade" id="editarUsuarioModal" tabindex="-1"
                        aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Usuario</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="formularioEditarUsuario">
                                        <input type="hidden" id="edit-id" name="id">
                                        <div class="mb-3">
                                            <label for="edit-nombre" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="edit-nombre" name="nombre"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-apellido" class="form-label">Apellido</label>
                                            <input type="text" class="form-control" id="edit-apellido" name="apellido"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-correo" class="form-label">Correo</label>
                                            <input type="email" class="form-control" id="edit-correo" name="correo"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-rol" class="form-label">Rol</label>
                                            <select class="form-select" id="edit-rol" name="rol" required>
                                                <option value="estudiante">Estudiante</option>
                                                <option value="tutor">Tutor</option>
                                                <option value="admin">Administrador</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-password" class="form-label">Nueva Contraseña (opcional)</label>
                                            <input type="password" class="form-control" id="edit-password" name="contrasena">
                                        </div>
                                        <center>
                                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                        </center>
                                    </form>
                                </div>
                            </div>
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

    <script>
        //Alerta de seguridad
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === "eliminado_exitoso") {
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: 'El usuario ha sido eliminado correctamente.',
                    showConfirmButton: false,
                    timer: 2500
                });
            } else if (status === "no_encontrado") {
                Swal.fire({
                    icon: 'info',
                    title: 'Aviso',
                    text: 'El usuario no fue encontrado.',
                });
            } else if (status === "error") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al intentar eliminar el usuario.',
                });
            } else if (status === "no_id") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se proporcionó un ID de usuario válido.',
                });
            }
        });

        // Código para la confirmación antes de eliminar
        const botonesEliminar = document.querySelectorAll('.eliminar-btn');
        botonesEliminar.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.href;

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>

    <script>
        //Modal
        document.addEventListener('DOMContentLoaded', function () {
            // Código para el modal de edición
            const editarUsuarioModal = document.getElementById('editarUsuarioModal');
            editarUsuarioModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-id');

                // Aquí haremos una llamada a un script PHP para obtener los datos del usuario
                fetch(`obtener_usuario.php?id=${userId}`)
                    .then(response => response.json())
                    .then(user => {
                        if (user.id) {
                            document.getElementById('edit-id').value = user.id;
                            document.getElementById('edit-nombre').value = user.nombre;
                            document.getElementById('edit-apellido').value = user.apellido;
                            document.getElementById('edit-correo').value = user.correo;
                            document.getElementById('edit-rol').value = user.rol;
                        } else {
                            Swal.fire('Error', 'No se encontraron los datos del usuario.', 'error');
                            const modal = bootstrap.Modal.getInstance(editarUsuarioModal);
                            modal.hide();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'No se pudo cargar la información del usuario.', 'error');
                        const modal = bootstrap.Modal.getInstance(editarUsuarioModal);
                        modal.hide();
                    });
            });

            // Código para manejar el envío del formulario del modal
            const formulario = document.getElementById('formularioEditarUsuario');
            formulario.addEventListener('submit', function (e) {
                e.preventDefault();

                fetch('actualizar_usuario.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Éxito!', data.message, 'success')
                                .then(() => {
                                    window.location.reload(); // Recarga la página para mostrar los cambios
                                });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Hubo un problema al guardar los cambios.', 'error');
                    });
            });
        });
    </script>
</body>

</html>