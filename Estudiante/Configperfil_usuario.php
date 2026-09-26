<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Perfil</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <h1 class="mt-4">Perfil de Usuario</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Configurar Perfil</li>
                    </ol>
                    <!--Contenido-->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-edit me-1"></i>
                            Datos Personales
                        </div>
                        <div class="card-body">
                            <form id="formularioPerfil" enctype="multipart/form-data">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">

                                <div class="mb-3 text-center">
                                    <label for="foto_perfil" class="form-label">Foto de Perfil</label>
                                    <div>
                                        <img src="<?php echo !empty($usuario['perfil_imagen']) ? '../' . htmlspecialchars($usuario['perfil_imagen']) : '../Assets/perfil_default.png'; ?>"
                                            alt="Foto de Perfil" class="rounded-circle mb-2"
                                            style="width: 150px; height: 150px; object-fit: cover;">
                                        <input class="form-control" type="file" id="foto_perfil" name="foto_perfil">
                                        <input type="hidden" name="perfil_imagen_existente"
                                            value="<?php echo htmlspecialchars($usuario['perfil_imagen']); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="apellido" class="form-label">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido"
                                            value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="correo" class="form-label">Correo</label>
                                        <input type="email" class="form-control" id="correo" name="correo"
                                            value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" id="telefono" name="telefono"
                                            value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="carrera" class="form-label">Carrera</label>
                                        <input type="text" class="form-control" id="carrera" name="carrera"
                                            value="<?php echo htmlspecialchars($usuario['carrera']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="anio_ciclo" class="form-label">Año</label>
                                        <input type="text" class="form-control" id="anio_ciclo" name="anio_ciclo"
                                            value="<?php echo htmlspecialchars($usuario['anio_ciclo']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="universidad_estudiante" class="form-label">Universidad</label>
                                        <input type="text" class="form-control" id="universidad_estudiante"
                                            name="universidad_estudiante"
                                            value="<?php echo htmlspecialchars($usuario['universidad_estudiante']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="rol" class="form-label">Rol</label>
                                        <input type="text" class="form-control" id="rol" name="rol"
                                            value="<?php echo htmlspecialchars($usuario['rol']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="nueva_contrasena" class="form-label">Nueva Contraseña (opcional)</label>
                                    <input type="password" class="form-control" id="nueva_contrasena"
                                        name="nueva_contrasena">
                                </div>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </form>
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
    <script>
        // Script para manejar el envío del formulario
        document.getElementById('formularioPerfil').addEventListener('submit', function (e) {
            e.preventDefault();

            fetch('actualizar_perfil.php', {
                method: 'POST',
                body: new FormData(this)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Éxito!', data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Hubo un problema al guardar los cambios.', 'error');
                });
        });
    </script>
</body>

</html>