<?php
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
$seccion = $_POST['seccion'] ?? '';

// 2. **DETERMINAR ANCLA DE REDIRECCIÓN**
// Esta variable se usará tanto para el éxito como para el error en la URL
$anchor_key = '';
if ($seccion === 'perfil') {
    $anchor_key = 'perfil';
} elseif ($seccion === 'materias') {
    $anchor_key = 'materias';
} elseif ($seccion === 'disponibilidad') {
    $anchor_key = 'horario';
} else {
    // Si la sección es inválida, usamos un error y salimos
    header("Location: Configurar_perfil.php?error=seccion_invalida");
    exit;
}

// 3. **FUNCIÓN DE REDIRECCIÓN CENTRALIZADA**
function redirigir($type, $key)
{
    // 'type' puede ser 'exito' o 'error'
    // 'key' es 'perfil', 'materias', 'horario' o un código de error
    $base_url = "Configurar_perfil.php?";

    if ($type === 'exito') {
        header("Location: {$base_url}exito={$key}");
    } else {
        // Para errores, redirigimos a la pestaña (anchor) y pasamos el código de error
        $anchor = $key === 'db_perfil' || $key === 'campos_requeridos' ? 'perfil' :
            ($key === 'db_modalidad' || $key === 'db_materias' ? 'materias' :
                ($key === 'db_disponibilidad' || $key === 'horario_invalido' ? 'horario' : 'perfil'));

        header("Location: {$base_url}anchor={$anchor}&error={$key}");
    }
    exit;
}


// ======================================================================
// 4. **PROCESAR SECCIÓN DE PERFIL (DATOS PERSONALES)**
// ======================================================================
if ($seccion === 'perfil') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $universidad = trim($_POST['universidad_tutor'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validaciones básicas
    if (empty($nombre) || empty($correo)) {
        redirigir('error', 'campos_requeridos');
    }

    try {
        // Actualizar los datos personales en la tabla 'usuarios'
        $sql = "UPDATE usuarios SET 
                    nombre = :nombre, 
                    apellido = :apellido, 
                    correo = :correo, 
                    telefono = :telefono, 
                    universidad_tutor = :universidad, 
                    descripcion = :descripcion
                WHERE id = :tutor_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':universidad', $universidad);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt->execute();

        // Éxito
        redirigir('exito', 'perfil');

    } catch (PDOException $e) {
        error_log("Error al actualizar perfil del tutor: " . $e->getMessage());
        redirigir('error', 'db_perfil');
    }
}
// ======================================================================
// 5. **PROCESAR SECCIÓN DE MATERIAS Y MODALIDAD (SOLUCIÓN DEFINITIVA Y UNIFICADA)**
// ======================================================================
elseif ($seccion === 'materias') {
    $modalidad_tutor = $_POST['modalidad_tutor'] ?? null;
    $materias_impartidas = $_POST['materias_impartidas'] ?? []; 
    $materias_insertadas = 0;

    if (empty($modalidad_tutor)) {
        redirigir('error', 'modalidad_vacia');
    }

    try {
        $conn->beginTransaction();

        // 1. Actualizar la modalidad del tutor
        $sql_update_modalidad = "UPDATE usuarios SET modalidad_tutor = :modalidad WHERE id = :tutor_id";
        $stmt_modalidad = $conn->prepare($sql_update_modalidad);
        $stmt_modalidad->bindParam(':modalidad', $modalidad_tutor, PDO::PARAM_STR);
        $stmt_modalidad->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_modalidad->execute();

        // 2. DESACTIVAR: Marcar TODAS las ofertas como INACTIVAS (USANDO 'activo')
        $sql_deactivate = "UPDATE ofertas_tutorias SET activo = 0 WHERE id_tutor = :tutor_id";
        $stmt_deactivate = $conn->prepare($sql_deactivate);
        $stmt_deactivate->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_deactivate->execute();

        // 3. UPSERT: Insertar o Reactivar
        if (!empty($materias_impartidas)) {
            
            // LA CORRECCIÓN CLAVE ESTÁ AQUÍ (activo = 1)
            $sql_upsert_oferta = "
                INSERT INTO ofertas_tutorias (id_tutor, id_materia, precio_hora, activo) 
                VALUES (:tutor_id, :id_materia, :precio_hora, 1)
                ON DUPLICATE KEY UPDATE 
                    precio_hora = VALUES(precio_hora), 
                    activo = 1; 
            ";
            $stmt_upsert_m = $conn->prepare($sql_upsert_oferta);

            foreach ($materias_impartidas as $materia_data) {
                $id_materia = filter_var($materia_data['id'] ?? 0, FILTER_VALIDATE_INT);
                $precio = filter_var($materia_data['precio'] ?? 0.00, FILTER_VALIDATE_FLOAT);

                // Solo procesar si el precio es mayor a cero.
                if ($id_materia > 0 && $precio !== false && $precio > 0.00) { 
                    $precio_sql = number_format($precio, 2, '.', ''); 
                    
                    $stmt_upsert_m->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
                    $stmt_upsert_m->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                    $stmt_upsert_m->bindParam(':precio_hora', $precio_sql); 
                    $stmt_upsert_m->execute();
                    $materias_insertadas++;
                }
            }
        }

        $conn->commit();
        redirigir('exito', 'materias');

    } catch (PDOException $e) {
        $conn->rollBack();
        
        // **SI ESTO SIGUE FALLANDO, USA ESTA LÍNEA PARA VER EL ERROR EXACTO:**
        // die("Fallo en UPSERT/Desactivación: " . $e->getMessage()); 
        
        error_log("Error de BD en procesar_perfil.php (materias): " . $e->getMessage()); 
        redirigir('error', 'db_materias');
    }
}

// ======================================================================
// 6. **PROCESAR SECCIÓN DE DISPONIBILIDAD (HORARIO) - MÚLTIPLES BLOQUES**
// ======================================================================
elseif ($seccion === 'disponibilidad') {
    // 🛑 AHORA ESPERAMOS UN ARRAY DE BLOQUES (horarios)
    $horarios = $_POST['horarios'] ?? []; // Debería ser un array de arrays: [['dia' => 'LUNES', 'inicio' => 'HH:MM:SS', 'fin' => 'HH:MM:SS'], ...]

    try {
        $conn->beginTransaction();

        // Paso 1: Eliminar toda la disponibilidad existente (DEBE HACERSE SIEMPRE)
        $sql_del_dispo = "DELETE FROM disponibilidad WHERE id_tutor = :tutor_id";
        $stmt_del_dispo = $conn->prepare($sql_del_dispo);
        $stmt_del_dispo->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_del_dispo->execute();

        // Paso 2: Insertar los nuevos bloques de disponibilidad
        $dias_validos = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'];

        $sql_ins_dispo = "INSERT INTO disponibilidad (id_tutor, dia_semana, hora_inicio, hora_fin) VALUES (:tutor_id, :dia, :inicio, :fin)";
        $stmt_ins_dispo = $conn->prepare($sql_ins_dispo);

        foreach ($horarios as $bloque) {
            $dia_upper = strtoupper($bloque['dia'] ?? '');
            $inicio = $bloque['inicio'] ?? '';
            $fin = $bloque['fin'] ?? '';

            // Solo insertamos si el día es válido y ambas horas están presentes
            if (in_array($dia_upper, $dias_validos) && !empty($inicio) && !empty($fin)) {
                // Validación de que la hora de inicio sea menor a la hora de fin (ya lo hace JS, pero es buena práctica de backend)
                if (strtotime($inicio) >= strtotime($fin)) {
                    // Si un bloque falla, deshacemos todo
                    $conn->rollBack();
                    redirigir('error', 'horario_invalido');
                }

                $stmt_ins_dispo->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
                $stmt_ins_dispo->bindParam(':dia', $dia_upper);
                $stmt_ins_dispo->bindParam(':inicio', $inicio);
                $stmt_ins_dispo->bindParam(':fin', $fin);
                $stmt_ins_dispo->execute();
            }
        }

        $conn->commit();

        // Éxito
        redirigir('exito', 'disponibilidad');

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error al actualizar disponibilidad con bloques: " . $e->getMessage());
        redirigir('error', 'db_disponibilidad');
    }
}

// 7. **REDIRECCIÓN POR DEFECTO** (Si no se reconoce la sección)
redirigir('error', 'seccion_invalida');

?>