<?php
session_start();
include "Includes/db.php";

// Inicializamos un estado para el SweetAlert
$status = "";

// Validar cuando se envía el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nombre = trim($_POST["firstName"]);
  $apellido = trim($_POST["lastName"]);
  $correo = trim($_POST["email"]);
  $telefono = trim($_POST["telefono"]);
  $password = $_POST["password"];
  $confirmPass = $_POST["confirmPassword"];
  $rol = $_POST["rol"];

  // Campos extras
  $carrera = $_POST["carrera"] ?? null;
  $anio = $_POST["anio"] ?? null;
  $universidad = $_POST["universidad"] ?? null;
  $modalidad = $_POST["modalidad"] ?? null;
  $universidadTutor = $_POST["universidadTutor"] ?? null;
  $descripcion = $_POST["descripcion"] ?? null;

  // Validaciones
  if (empty($nombre) || empty($apellido) || empty($correo) || empty($password) || empty($rol)) {
    $status = "campos_vacios";
  } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $status = "correo_invalido";
  } elseif ($password !== $confirmPass) {
    $status = "contraseña_mismatch";
  } else {
    // Verificar si el correo ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);

    if ($stmt->rowCount() > 0) {
      $status = "correo_existente";
    } else {
      // Insertar usuario
      $hash = password_hash($password, PASSWORD_BCRYPT);

      $sql = "INSERT INTO usuarios 
          (nombre, apellido, correo, telefono, contrasena, rol, carrera, anio_ciclo, universidad_estudiante, modalidad_tutor, universidad_tutor, descripcion) 
          VALUES (:nombre, :apellido, :correo, :telefono, :contrasena, :rol, :carrera, :anio, :universidad_estudiante, :modalidad, :universidad_tutor, :descripcion)";

      $stmt = $conn->prepare($sql);
      $insertado = $stmt->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':correo' => $correo,
        ':telefono' => $telefono,
        ':contrasena' => $hash,
        ':rol' => $rol,
        ':carrera' => $carrera,
        ':anio' => $anio,
        ':universidad_estudiante' => $universidad,
        ':modalidad' => $modalidad,
        ':universidad_tutor' => $universidadTutor,
        ':descripcion' => $descripcion
      ]);

      if ($insertado) {
        $status = "ok";
      } else {
        $status = "error";
      }
    }
  }

  // Redirigir con parámetro GET para SweetAlert
  header("Location: Registro.php?status=$status");
  exit();
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <title>TutoLink</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
    href="https://unpkg.com/bs-brain@2.0.4/components/registrations/registration-3/assets/css/registration-3.css">
  <!-- Bootstrap icons-->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
  <!-- Core theme CSS (includes Bootstrap)-->
  <link href="assets/css/styles.css" rel="stylesheet" />
  <!--Alerta de javascript-->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="d-flex flex-column">
  <main class="flex-shrink-0">
    <!-- Navigation-->
    <?php require_once 'Includes/navbar.php'; ?>
    <section class="p-3 p-md-4 p-xl-5">
      <div class="container">
        <div class="row">
          <div class="col-12 col-md-6 bsb-tpl-bg-platinum">
            <div class="d-flex flex-column justify-content-between h-100 p-3 p-md-4 p-xl-5">
              <h3 class="m-0">Bienvenido!</h3>
              <img class="img-fluid rounded mx-auto my-4" loading="lazy" src="assets/img/logo.png" width="auto"
                height="auto" alt="Logo">
              <p class="mb-0">¿Ya tienes una cuenta? <a href="Login.php"
                  class="link-secondary text-decoration-none">Iniciar sesión</a></p>
            </div>
          </div>
          <div class="col-12 col-md-6 bsb-tpl-bg-lotion">
            <div class="p-3 p-md-4 p-xl-5">
              <div class="row">
                <div class="col-12">
                  <div class="mb-5">
                    <h1 class="h1">Registro</h1>
                    <h3 class="fs-6 fw-normal text-secondary m-0">Introduce tus datos para registrarte</h3>
                  </div>
                </div>
              </div>
              <form class="row g-3" action="registro.php" method="POST">
                <div class="row gy-3 gy-md-4 overflow-hidden">
                  <form class="row g-3" action="registro.php" method="POST">
                    <!-- Nombre -->
                    <div class="col-md-6">
                      <label for="firstName" class="form-label">Nombre<span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="firstName" id="firstName" placeholder="Nombre"
                        required>
                    </div>

                    <!-- Apellido -->
                    <div class="col-md-6">
                      <label for="lastName" class="form-label">Apellido<span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="lastName" id="lastName" placeholder="Apellido"
                        required>
                    </div>

                    <!-- Correo -->
                    <div class="col-12">
                      <label for="email" class="form-label">Correo Electrónico<span class="text-danger">*</span></label>
                      <input type="email" class="form-control" name="email" id="email" placeholder="nombre@ejemplo.com"
                        required>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-md-6">
                      <label for="telefono" class="form-label">Teléfono</label>
                      <input type="text" class="form-control" name="telefono" id="telefono" placeholder="7777-7777">
                    </div>

                    <!-- Contraseña -->
                    <div class="col-md-6">
                      <label for="password" class="form-label">Contraseña<span class="text-danger">*</span></label>
                      <input type="password" class="form-control" name="password" id="password" required>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="col-md-6">
                      <label for="confirmPassword" class="form-label">Confirmar Contraseña<span
                          class="text-danger">*</span></label>
                      <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" required>
                    </div>

                    <!-- Rol -->
                    <div class="col-md-6">
                      <label for="rol" class="form-label">Registrarse como<span class="text-danger">*</span></label>
                      <select class="form-select" name="rol" id="rol" required onchange="mostrarCampos()">
                        <option value="">Seleccione...</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="tutor">Tutor</option>
                      </select>
                    </div>

                    <!-- Campos extras para Estudiantes -->
                    <div id="camposEstudiante" class="row g-3" style="display:none;">
                      <div class="col-md-6">
                        <label for="carrera" class="form-label">Carrera</label>
                        <input type="text" class="form-control" name="carrera" id="carrera"
                          placeholder="Ej. Ingeniería en Sistemas">
                      </div>
                      <div class="col-md-6">
                        <label for="anio" class="form-label">Año/Ciclo</label>
                        <input type="text" class="form-control" name="anio" id="anio" placeholder="Ej. 2° Año">
                      </div>
                      <div class="col-md-12">
                        <label for="universidad" class="form-label">Universidad o Institución</label>
                        <input type="text" class="form-control" name="universidad" id="universidad"
                          placeholder="Ej. Universidad Nacional">
                      </div>
                    </div>

                    <!-- Campos extras para Tutores -->
                    <div id="camposTutor" class="row g-3" style="display:none;">
                      <div class="col-md-6">
                        <label for="modalidad" class="form-label">Modalidad</label>
                        <select class="form-select" name="modalidad" id="modalidad">
                          <option value="">Seleccione...</option>
                          <option value="Presencial">Presencial</option>
                          <option value="Virtual">Virtual</option>
                          <option value="Mixta">Mixta</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="universidadTutor" class="form-label">Universidad o Institución</label>
                        <input type="text" class="form-control" name="universidadTutor" id="universidadTutor"
                          placeholder="Ej. Universidad Nacional">
                      </div>
                      <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción / Experiencia</label>
                        <textarea class="form-control" name="descripcion" id="descripcion" rows="3"
                          placeholder="Breve presentación y experiencia"></textarea>
                      </div>
                    </div>

                    <!-- Botón -->
                    <div class="col-12">
                      <div class="d-grid">
                        <button class="btn btn-primary" type="submit">Registrarse</button>
                      </div>
                    </div>
                  </form>

                  <script>
                    function mostrarCampos() {
                      const rol = document.getElementById("rol").value;
                      document.getElementById("camposEstudiante").style.display = (rol === "estudiante") ? "flex" : "none";
                      document.getElementById("camposTutor").style.display = (rol === "tutor") ? "flex" : "none";
                    }

                    // SweetAlert2 según el status
                    const urlParams = new URLSearchParams(window.location.search);
                    const status = urlParams.get('status');

                    if (status === "ok") {
                      Swal.fire({
                        icon: 'success',
                        title: 'Registro Exitoso',
                        text: '¡Ahora puedes iniciar sesión!',
                        confirmButtonText: 'OK'
                      });
                    } else if (status === "error") {
                      Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un problema en el registro.',
                        confirmButtonText: 'OK'
                      });
                    } else if (status === "correo_existente") {
                      Swal.fire({
                        icon: 'warning',
                        title: 'Correo en uso',
                        text: 'Este correo ya está registrado.',
                        confirmButtonText: 'OK'
                      });
                    } else if (status === "contraseña_mismatch") {
                      Swal.fire({
                        icon: 'warning',
                        title: 'Contraseñas no coinciden',
                        text: 'Asegúrate de que las contraseñas sean iguales.',
                        confirmButtonText: 'OK'
                      });
                    } else if (status === "correo_invalido") {
                      Swal.fire({
                        icon: 'warning',
                        title: 'Correo inválido',
                        text: 'Por favor ingresa un correo válido.',
                        confirmButtonText: 'OK'
                      });
                    } else if (status === "campos_vacios") {
                      Swal.fire({
                        icon: 'warning',
                        title: 'Campos incompletos',
                        text: 'Por favor llena todos los campos requeridos.',
                        confirmButtonText: 'OK'
                      });
                    }
                  </script>
                  <!--Mostrar elementos segun rol-->
                  <script src="assets/js/MostrarElementosRegistro.js"></script>
                  <div class="row">
                    <div class="col-12">
                      <hr class="mt-5 mb-4 border-secondary-subtle">
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </div>
    </section>
  </main>
  <!-- Footer-->
  <?php require_once 'Includes/footer.php'; ?>

  <!-- Bootstrap core JS-->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Core theme JS-->
  <script src="assets/js/scripts.js"></script>
</body>

</html>
