<?php
// Tutor/configurar_perfil.php
include 'Includes/Nav.php';

// 🛑 Incluir la conexión a la base de datos
if (!isset($conn)) {
    // Asegúrate de que esta ruta sea correcta para la carpeta Tutor/
    include '../Includes/db.php';
}

// 1. **AUTENTICACIÓN Y VARIABLES**
if ($usuario['rol'] !== 'tutor') {
    header('Location: ../index.php');
    exit;
}
$tutor_id = $usuario['id'];

// 2. **CARGAR DATOS DEL PERFIL** (Tabla 'usuarios')
$perfil_tutor = [];
try {
    // Se seleccionan los campos específicos del tutor.
    $sql_perfil = "SELECT nombre, apellido, correo, telefono, universidad_tutor, modalidad_tutor, descripcion FROM usuarios WHERE id = :tutor_id";
    $stmt_perfil = $conn->prepare($sql_perfil);
    $stmt_perfil->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_perfil->execute();
    $perfil_tutor = $stmt_perfil->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg_perfil = "Error al cargar perfil: " . $e->getMessage();
}

// 3. **CARGAR DISPONIBILIDAD** (Tabla 'disponibilidad')
$dias_semana = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'];
$disponibilidad_actual = [];
try {
    $sql_dispo = "SELECT dia_semana, hora_inicio, hora_fin FROM disponibilidad WHERE id_tutor = :tutor_id ORDER BY FIELD(dia_semana, 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO')";
    $stmt_dispo = $conn->prepare($sql_dispo);
    $stmt_dispo->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_dispo->execute();

    while ($row = $stmt_dispo->fetch(PDO::FETCH_ASSOC)) {
        $disponibilidad_actual[$row['dia_semana']] = $row;
    }
} catch (PDOException $e) {
    $error_msg_dispo = "Error al cargar disponibilidad: " . $e->getMessage();
}

// 4. **CARGAR OFERTAS Y PRECIOS DEL TUTOR** $todas_materias = [];
$materias_precios_actuales = []; 

try {
    // 1. Obtener todas las materias disponibles
    $stmt_materias = $conn->query("SELECT id, nombre_materia FROM materias ORDER BY nombre_materia");
    $todas_materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener las OFERTAS y el PRECIO que imparte el tutor desde la tabla CORRECTA
    $sql_tutor_ofertas = "SELECT id_materia, precio_hora FROM ofertas_tutorias WHERE id_tutor = :tutor_id";
    $stmt_ofertas = $conn->prepare($sql_tutor_ofertas);
    $stmt_ofertas->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT); // Asegúrate que $tutor_id es el ID del tutor autenticado
    $stmt_ofertas->execute();
    
    // Almacenar los resultados en un array asociativo: [id_materia => precio_hora]
    while ($row = $stmt_ofertas->fetch(PDO::FETCH_ASSOC)) {
        // La clave del array es el ID de la materia, lo que permite la verificación rápida
        $materias_precios_actuales[$row['id_materia']] = $row['precio_hora'];
    }

} catch (PDOException $e) {
    // Manejo de error de carga
    $error_msg_materias = "Error al cargar ofertas: " . $e->getMessage();
}


// 5. **LÓGICA DE MENSAJES Y PESTAÑAS ACTIVAS**
$estado_exito = $_GET['exito'] ?? '';
$estado_error = $_GET['error'] ?? '';

// Determinamos qué pestaña debe estar activa después de guardar, usando la URL (compatible con PHP 7+)
$active_tab = 'list-perfil'; // Valor por defecto

if (isset($_GET['anchor'])) {
    switch ($_GET['anchor']) {
        case 'materias':
            $active_tab = 'list-materias';
            break;
        case 'horario':
            $active_tab = 'list-horario';
            break;
        // default es list-perfil, que ya está asignado.
    }
}

// Lógica para mostrar mensajes de éxito
$mensaje_exito = '';
if ($estado_exito === 'perfil') {
    $mensaje_exito = '¡Datos personales y de perfil actualizados correctamente! 🎉';
} elseif ($estado_exito === 'materias') {
    $mensaje_exito = '¡Materias y modalidad guardadas! 📚';
} elseif ($estado_exito === 'disponibilidad') {
    $mensaje_exito = '¡Disponibilidad y horarios guardados! ⏰';
}



?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Configuración de Perfil - Tutor</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Configuración de Perfil</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Configurar Perfil</li>
                    </ol>

                    <p class="mb-4">Gestiona tu información personal, materias de enseñanza y disponibilidad semanal.
                    </p>

                    <?php if ($mensaje_exito): ?>
                        <div class="alert alert-success"><?= $mensaje_exito ?></div>
                    <?php elseif ($estado_error): ?>
                        <div class="alert alert-danger">Hubo un error al guardar los datos. Inténtalo de nuevo. (Detalle:
                            <?= htmlspecialchars($estado_error) ?>)
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="list-group" id="list-tab" role="tablist">
                                <a class="list-group-item list-group-item-action <?= ($active_tab === 'list-perfil') ? 'active' : '' ?>"
                                    id="list-perfil-list" data-bs-toggle="list" href="#list-perfil" role="tab">
                                    Datos Personales
                                </a>
                                <a class="list-group-item list-group-item-action <?= ($active_tab === 'list-materias') ? 'active' : '' ?>"
                                    id="list-materias-list" data-bs-toggle="list" href="#list-materias" role="tab">
                                    Materias y Modalidad
                                </a>
                                <a class="list-group-item list-group-item-action <?= ($active_tab === 'list-horario') ? 'active' : '' ?>"
                                    id="list-horario-list" data-bs-toggle="list" href="#list-horario" role="tab">
                                    Horarios y Disponibilidad
                                </a>
                            </div>
                        </div>

                        <div class="col-md-9">
                            <div class="tab-content" id="nav-tabContent">

                                <div class="tab-pane fade <?= ($active_tab === 'list-perfil') ? 'show active' : '' ?>"
                                    id="list-perfil" role="tabpanel">
                                    <div class="card shadow-sm p-4">
                                        <h5 class="mb-3">Información de Usuario y Perfil</h5>
                                        <form action="Procesar_perfil.php" method="POST">
                                            <input type="hidden" name="seccion" value="perfil">
                                            <input type="hidden" name="tutor_id" value="<?= $tutor_id ?>">

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="nombre" class="form-label">Nombre</label>
                                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                                        value="<?= htmlspecialchars($perfil_tutor['nombre'] ?? '') ?>"
                                                        required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="apellido" class="form-label">Apellido</label>
                                                    <input type="text" class="form-control" id="apellido"
                                                        name="apellido"
                                                        value="<?= htmlspecialchars($perfil_tutor['apellido'] ?? '') ?>">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="email" class="form-label">Correo Electrónico</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    value="<?= htmlspecialchars($perfil_tutor['correo'] ?? '') ?>"
                                                    required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Teléfono</label>
                                                <input type="text" class="form-control" id="telefono" name="telefono"
                                                    value="<?= htmlspecialchars($perfil_tutor['telefono'] ?? '') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="universidad_tutor" class="form-label">Universidad donde
                                                    estudiaste/impartes</label>
                                                <input type="text" class="form-control" id="universidad_tutor"
                                                    name="universidad_tutor"
                                                    value="<?= htmlspecialchars($perfil_tutor['universidad_tutor'] ?? '') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="descripcion" class="form-label">Descripción / Biografía
                                                    (Cuéntanos sobre ti)</label>
                                                <textarea class="form-control" id="descripcion" name="descripcion"
                                                    rows="5"><?= htmlspecialchars($perfil_tutor['descripcion'] ?? '') ?></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Guardar Datos
                                                Personales</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="tab-pane fade <?= ($active_tab === 'list-materias') ? 'show active' : '' ?>"
                                    id="list-materias" role="tabpanel">
                                    <div class="card shadow-sm p-4">
                                        <h5 class="mb-3">Materias y Modalidad</h5>
                                        <form action="procesar_perfil.php" method="POST">
                                            <input type="hidden" name="seccion" value="materias">
                                            <input type="hidden" name="tutor_id" value="<?= $tutor_id ?>">

                                            <div class="mb-4">
                                                <label for="modalidad_tutor" class="form-label">Modalidad de
                                                    Tutoría</label>
                                                <select class="form-select" id="modalidad_tutor" name="modalidad_tutor"
                                                    required>
                                                    <option value="Virtual" <?= (($perfil_tutor['modalidad_tutor'] ?? '') === 'Virtual') ? 'selected' : '' ?>>Virtual</option>
                                                    <option value="Presencial" <?= (($perfil_tutor['modalidad_tutor'] ?? '') === 'Presencial') ? 'selected' : '' ?>>Presencial</option>
                                                    <option value="Ambas" <?= (($perfil_tutor['modalidad_tutor'] ?? '') === 'Ambas') ? 'selected' : '' ?>>Ambas (Virtual y Presencial)
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="mb-4 border p-3 rounded">
                                                <label class="form-label d-block mb-3"><strong>Selecciona las materias y define tu precio por hora (USD):</strong></label>
                                                <div class="row">
                                                    <?php if (empty($todas_materias)): ?>
                                                        <p class="text-danger">No se encontraron materias disponibles en la base de datos.</p>
                                                    <?php else: ?>
                                                        <?php foreach ($todas_materias as $materia): ?>
                                                            <?php
                                                            // Preparamos el array de materias actuales para encontrar el precio guardado.
                                                            // DEBES MODIFICAR la carga de datos en PHP (sección 4) para obtener el precio
                                                            // Asumiendo que ahora 'materias_actuales' es un array asociativo [id_materia => precio]
                                                            
                                                            // Si el ID de la materia está en el array de materias actuales (la llave existe)
                                                            $checked = isset($materias_precios_actuales[$materia['id']]) ? 'checked' : '';
                                                            // Obtener el precio guardado o 0.00 por defecto
                                                            $precio_actual = $materias_precios_actuales[$materia['id']] ?? '0.00';
                                                            ?>
                                                            <div class="col-sm-12 col-md-6 mb-3">
                                                                <div class="input-group">
                                                                    <div class="input-group-text">
                                                                        <input class="form-check-input mt-0 materia-checkbox" type="checkbox"
                                                                            name="materias_impartidas[<?= $materia['id'] ?>][id]"
                                                                            value="<?= $materia['id'] ?>"
                                                                            id="materia_<?= $materia['id'] ?>" <?= $checked ?>>
                                                                    </div>
                                                                    <label class="form-control" for="materia_<?= $materia['id'] ?>">
                                                                        <?= htmlspecialchars($materia['nombre_materia']) ?>
                                                                    </label>
                                                                    <span class="input-group-text">$</span>
                                                                    <input type="number" class="form-control precio-input"
                                                                        name="materias_impartidas[<?= $materia['id'] ?>][precio]"
                                                                        placeholder="0.00" step="0.01" min="0"
                                                                        value="<?= $precio_actual ?>"
                                                                        data-materia-id="<?= $materia['id'] ?>"
                                                                        <?= $checked ? '' : 'disabled' ?>> 
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Guardar Materias y
                                                Modalidad</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="tab-pane fade <?= ($active_tab === 'list-horario') ? 'show active' : '' ?>"
                                    id="list-horario" role="tabpanel">
                                    <div class="card shadow-sm p-4">
                                        <h5 class="mb-3">Horario Recurrente Semanal</h5>

                                        <p class="text-muted">Define los bloques de tiempo específicos en los que estás
                                            disponible cada semana. Puedes agregar múltiples bloques por día.</p>

                                        <div class="border p-3 mb-4 bg-light rounded">
                                            <h6 class="mb-3">Agregar Bloque de Disponibilidad</h6>
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-4">
                                                    <label for="dia_select" class="form-label">Día</label>
                                                    <select class="form-select" id="dia_select">
                                                        <option value="">Seleccionar Día...</option>
                                                        <?php foreach ($dias_semana as $dia): ?>
                                                            <option value="<?= $dia ?>"><?= ucfirst(strtolower($dia)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="hora_inicio" class="form-label">Hora de inicio</label>
                                                    <input type="time" class="form-control" id="hora_inicio"
                                                        value="09:00">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="hora_fin" class="form-label">Hora de fin</label>
                                                    <input type="time" class="form-control" id="hora_fin" value="17:00">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-primary w-100"
                                                        id="btn_agregar_horario">Agregar</button>
                                                </div>
                                            </div>
                                        </div>
                                        <form action="procesar_perfil.php" method="POST" id="form_horario_principal">
                                            <input type="hidden" name="seccion" value="disponibilidad">
                                            <input type="hidden" name="tutor_id" value="<?= $tutor_id ?>">

                                            <h6 class="mb-3 mt-4">Disponibilidad Actual</h6>

                                            <div id="lista_horarios_actuales">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Día</th>
                                                            <th>Rango Horario</th>
                                                            <th>Acción</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbody_horarios">
                                                    </tbody>
                                                </table>
                                                <p id="horario_vacio_msg" class="text-muted text-center"
                                                    style="display: none;">Aún no has agregado ningún bloque de
                                                    disponibilidad.</p>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-success"
                                                    id="btn_guardar_horario">Guardar Horario Semanal</button>
                                            </div>
                                        </form>

                                    </div>
                                </div><br>

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
        document.addEventListener('DOMContentLoaded', function () {
            // Variables de Elementos
            const btnAgregarHorario = document.getElementById('btn_agregar_horario');
            const tbodyHorarios = document.getElementById('tbody_horarios');
            const diaSelect = document.getElementById('dia_select');
            const horaInicio = document.getElementById('hora_inicio');
            const horaFin = document.getElementById('hora_fin');
            const horarioVacioMsg = document.getElementById('horario_vacio_msg');
            const formHorarioPrincipal = document.getElementById('form_horario_principal');
            const btnGuardarHorario = document.getElementById('btn_guardar_horario'); // Asumo que este ID existe en tu HTML

            // Variables de Estado
            let bloquesHorario = [];
            let editingIndex = null; // Índice del bloque que estamos editando

            // Cargar datos actuales desde PHP al array de JS
            // **Asegúrate de que $disponibilidad_actual es un array de objetos con todos los bloques**
            const disponibilidadActual = <?= json_encode($disponibilidad_actual ?? []) ?>;
            // La lista de días de la semana para ordenar
            const diasSemana = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'];

            // Función de ayuda para formatear el nombre del día
            function formatDia(dia) {
                return dia.charAt(0) + dia.slice(1).toLowerCase();
            }

            // 1. CARGA DE DATOS EXISTENTES (Modificado para manejar múltiples bloques si la BD los devuelve)
            function cargarHorariosExistentes() {
                // Asumiendo que $disponibilidad_actual es un array de objetos/filas desde la BD
                if (Array.isArray(disponibilidadActual)) {
                    disponibilidadActual.forEach(row => {
                        bloquesHorario.push({
                            dia: row.dia_semana,
                            inicio: row.hora_inicio.substring(0, 5), // 'HH:MM'
                            fin: row.hora_fin.substring(0, 5) // 'HH:MM'
                        });
                    });
                } else {
                    // Si la estructura es un objeto asociativo (la vista antigua), iteramos sobre los valores
                    Object.values(disponibilidadActual).forEach(row => {
                        bloquesHorario.push({
                            dia: row.dia_semana,
                            inicio: row.hora_inicio.substring(0, 5), // 'HH:MM'
                            fin: row.hora_fin.substring(0, 5) // 'HH:MM'
                        });
                    });
                }
                renderizarHorarios();
            }

            // 2. FUNCIÓN PRINCIPAL DE RENDERIZADO Y ENVÍO
            function renderizarHorarios() {
                tbodyHorarios.innerHTML = '';

                if (bloquesHorario.length === 0) {
                    horarioVacioMsg.style.display = 'block';
                    return;
                }
                horarioVacioMsg.style.display = 'none';

                // Ordenar los bloques
                bloquesHorario.sort((a, b) => {
                    const indexA = diasSemana.indexOf(a.dia);
                    const indexB = diasSemana.indexOf(b.dia);
                    if (indexA !== indexB) return indexA - indexB;
                    return a.inicio.localeCompare(b.inicio);
                });

                // Generar las filas de la tabla y los campos ocultos para el envío
                bloquesHorario.forEach((bloque, index) => {
                    const newRow = tbodyHorarios.insertRow();
                    newRow.innerHTML = `
                    <td>${formatDia(bloque.dia)}</td>
                    <td>${bloque.inicio} - ${bloque.fin}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn-editar me-2" data-index="${index}">
                            Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btn-eliminar" data-index="${index}">
                            Eliminar
                        </button>
                    </td>
                    <input type="hidden" name="horarios[${index}][dia]" value="${bloque.dia}">
                    <input type="hidden" name="horarios[${index}][inicio]" value="${bloque.inicio}:00">
                    <input type="hidden" name="horarios[${index}][fin]" value="${bloque.fin}:00">
                `;
                });

                // Re-adjuntar eventos de eliminación
                document.querySelectorAll('.btn-eliminar').forEach(button => {
                    button.addEventListener('click', function () {
                        eliminarHorario(parseInt(this.getAttribute('data-index')));
                    });
                });

                // 🛑 AÑADIR EVENTOS DE EDICIÓN
                document.querySelectorAll('.btn-editar').forEach(button => {
                    button.addEventListener('click', function () {
                        editarHorario(parseInt(this.getAttribute('data-index')));
                    });
                });
            }

            // 3. LÓGICA DE EDICIÓN (Cargar datos a los inputs)
            function editarHorario(index) {
                const bloque = bloquesHorario[index];

                // 1. Cargar los datos del bloque seleccionado a los campos de entrada
                diaSelect.value = bloque.dia;
                horaInicio.value = bloque.inicio;
                horaFin.value = bloque.fin;

                // 2. Marcar que estamos en modo edición
                editingIndex = index;

                // 3. Cambiar el texto y estilo del botón de "Agregar"
                btnAgregarHorario.textContent = 'Actualizar';
                btnAgregarHorario.classList.remove('btn-primary');
                btnAgregarHorario.classList.add('btn-success');

                // 4. Deshabilitar el botón de Guardar hasta que se complete la edición
                if (btnGuardarHorario) btnGuardarHorario.disabled = true;

                horaInicio.focus();
            }


            // 4. LÓGICA DE AGREGAR/ACTUALIZAR (Manejador principal del botón)
            btnAgregarHorario.addEventListener('click', function () {
                const dia = diaSelect.value;
                const inicio = horaInicio.value;
                const fin = horaFin.value;

                if (!dia || !inicio || !fin) {
                    alert('Debes seleccionar día, hora de inicio y hora de fin.');
                    return;
                }
                if (inicio >= fin) {
                    alert('La hora de inicio debe ser anterior a la hora de fin.');
                    return;
                }

                const nuevoBloque = { dia: dia, inicio: inicio, fin: fin };

                if (editingIndex !== null) {
                    // Modo ACTUALIZAR: Reemplazar el bloque existente
                    bloquesHorario[editingIndex] = nuevoBloque;

                    // Resetear el modo edición
                    editingIndex = null;
                    btnAgregarHorario.textContent = 'Agregar';
                    btnAgregarHorario.classList.remove('btn-success');
                    btnAgregarHorario.classList.add('btn-primary');
                    if (btnGuardarHorario) btnGuardarHorario.disabled = false; // Habilitar Guardar

                } else {
                    // Modo AGREGAR: Añadir un nuevo bloque
                    bloquesHorario.push(nuevoBloque);
                }

                // Limpiar y dibujar la tabla
                renderizarHorarios();

                // Limpiar inputs
                diaSelect.value = '';
                horaInicio.value = '09:00';
                horaFin.value = '17:00';
            });

            // 5. LÓGICA DE ELIMINAR
            function eliminarHorario(index) {
                bloquesHorario.splice(index, 1);

                // Si eliminamos el bloque que estábamos editando, salimos del modo edición
                if (index === editingIndex) {
                    editingIndex = null;
                    btnAgregarHorario.textContent = 'Agregar';
                    btnAgregarHorario.classList.remove('btn-success');
                    btnAgregarHorario.classList.add('btn-primary');
                    if (btnGuardarHorario) btnGuardarHorario.disabled = false;
                }
                renderizarHorarios();
            }

            // Iniciar la carga de horarios al cargar la página
            cargarHorariosExistentes();
        });

        // Lógica para habilitar/deshabilitar el campo de precio
document.querySelectorAll('.materia-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        // Encontrar el input de precio asociado a este checkbox (dentro del mismo grupo)
        const inputGroup = this.closest('.input-group');
        const precioInput = inputGroup.querySelector('.precio-input');
        
        if (precioInput) {
            precioInput.disabled = !this.checked;
            if (this.checked) {
                precioInput.focus();
            } else {
                // Opcional: limpiar el precio si se desmarca
                // precioInput.value = '0.00'; 
            }
        }
    });
});
    </script>
</body>

</html>