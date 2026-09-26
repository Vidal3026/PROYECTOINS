<?php
session_start();
// Incluye el archivo de conexión a la base de datos (que usa PDO)
include "Includes/db.php";

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] == "POST") {
  $correo = trim($_POST['email']);
  $contrasena = $_POST['password'];

  // Validaciones
  // Si los campos están vacíos
  if (empty($correo) || empty($contrasena)) {
    header("Location: Login.php?status=campos_vacios");
    exit();
  }
  // Si el correo no es válido
  elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: Login.php?status=correo_invalido");
    exit();
  }

  // Consultar usuario
  try {
    $stmt = $conn->prepare("SELECT id, contrasena, rol, nombre FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si se encontró el usuario
    if ($usuario) {
      // Verificar la contraseña encriptada
      if (password_verify($contrasena, $usuario['contrasena'])) {
        // Iniciar sesión y redirigir
        $_SESSION['id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];

        // Redireccionar con un estado de éxito
        if ($usuario['rol'] === "estudiante") {
          header("Location: Estudiante/index.php?status=exitoso");
        } elseif ($usuario['rol'] === "tutor") {
          header("Location: Tutor/index.php?status=exitoso");
        } elseif ($usuario['rol'] === "admin") {
          header("Location: Administrador/index.php?status=exitoso");
        } else {
          // Si el rol no es reconocido, redirigir a la página de login como medida de seguridad
          header("Location: Login.php?status=rol_invalido");
        }
        exit();
      } else {
        // Contraseña incorrecta
        header("Location: Login.php?status=contrasena_incorrecta");
        exit();
      }
    } else {
      // Usuario no encontrado
      header("Location: Login.php?status=usuario_no_encontrado");
      exit();
    }
  } catch (PDOException $e) {
    // Manejo de errores de la base de datos
    header("Location: Login.php?status=error_db");
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <title>TutoLink - Iniciar Sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
    href="https://unpkg.com/bs-brain@2.0.4/components/registrations/registration-3/assets/css/registration-3.css">
  <link href="assets/css/styles.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="d-flex flex-column h-100">
  <main class="flex-shrink-0">
    <?php require_once 'Includes/navbar.php'; ?>

    <section class="p-3 p-md-4 p-xl-5">
      <div class="container">
        <div class="row">
          <div class="col-12 col-md-6 bsb-tpl-bg-platinum">
            <div class="d-flex flex-column justify-content-between h-100 p-3 p-md-4 p-xl-5">
              <h3 class="m-0">Bienvenido!</h3>
              <img class="img-fluid rounded mx-auto my-4" loading="lazy" src="assets/img/logo.png" width="auto"
                height="auto" alt="Logo">
              <p class="mb-0">¿Todavía no eres miembro?
                <a href="Registro.php" class="link-secondary text-decoration-none">Regístrate Ahora</a>
              </p>
            </div>
          </div>

          <div class="col-12 col-md-6 bsb-tpl-bg-lotion">
            <div class="p-3 p-md-4 p-xl-5">
              <div class="row">
                <div class="col-12">
                  <div class="mb-5">
                    <h1>Iniciar Sesión</h1>
                  </div>
                </div>
              </div>

              <form id="loginForm" action="Login.php" method="POST">
                <div class="row gy-3 gy-md-4 overflow-hidden">
                  <div class="col-12">
                    <label for="email" class="form-label">Correo Electrónico<span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="nombre@ejemplo.com"
                      required>
                  </div>
                  <div class="col-12">
                    <label for="password" class="form-label">Contraseña<span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password" id="password" required>
                  </div>
                  <div class="col-12">
                    <div class="d-grid">
                      <button class="btn bsb-btn-xl btn-primary" type="submit">Iniciar Sesión</button>
                    </div>
                  </div>
                </div>
              </form>

              <div class="row">
                <div class="col-12">
                  <hr class="mt-5 mb-4 border-secondary-subtle">
                  <div class="text-end">
                    <a href="#" class="link-secondary text-decoration-none">Olvidé mi contraseña</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
  <?php require_once 'Includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/scripts.js"></script>

  <script>
    // Validaciones y alertas con SweetAlert
    // Este script debe ir al final del body
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status === "campos_vacios") {
      Swal.fire({
        icon: 'warning',
        title: 'Campos vacíos',
        text: 'Por favor, completa todos los campos.',
        confirmButtonText: 'OK'
      });
    } else if (status === "correo_invalido") {
      Swal.fire({
        icon: 'warning',
        title: 'Correo inválido',
        text: 'Por favor, ingresa un correo electrónico válido.',
        confirmButtonText: 'OK'
      });
    } else if (status === "contrasena_incorrecta") {
      Swal.fire({
        icon: 'error',
        title: 'Error de acceso',
        text: 'La contraseña es incorrecta. Inténtalo de nuevo.',
        confirmButtonText: 'OK'
      });
    } else if (status === "usuario_no_encontrado") {
      Swal.fire({
        icon: 'error',
        title: 'Error de acceso',
        text: 'El usuario no existe. Por favor, regístrate.',
        confirmButtonText: 'OK'
      });
    } else if (status === "error_db") {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Hubo un problema con la base de datos.',
        confirmButtonText: 'OK'
      });
    } else if (status === "logout_exitoso") {
      Swal.fire({
        icon: 'success',
        title: '¡Sesión Cerrada!',
        text: 'Has cerrado sesión exitosamente.',
        showConfirmButton: false,
        timer: 2500
      });
    }

    // Limpia el parámetro de la URL para evitar que la alerta aparezca al recargar
    if (status) {
      const newUrl = window.location.origin + window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
    }
  </script>
</body>

</html>
