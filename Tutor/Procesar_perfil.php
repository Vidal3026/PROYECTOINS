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
// 6. **PROCESAR SECCIÓN DE DISPONIBILIDAD Y NOTIFICAR INSCRIPCIONES**
// ======================================================================
elseif ($seccion === 'disponibilidad') {
    $horarios_recibidos = $_POST['horarios'] ?? [];
    $dias_validos = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'];
    $horarios = [];

    foreach ($horarios_recibidos as $bloque) {
        $dia = strtoupper(trim($bloque['dia'] ?? ''));
        $inicio = trim($bloque['inicio'] ?? '');
        $fin = trim($bloque['fin'] ?? '');

        if (!in_array($dia, $dias_validos, true) || empty($inicio) || empty($fin)) {
            continue;
        }

        if (strtotime($inicio) >= strtotime($fin)) {
            redirigir('error', 'horario_invalido');
        }

        $horarios[] = [
            'dia' => $dia,
            'inicio' => date('H:i:s', strtotime($inicio)),
            'fin' => date('H:i:s', strtotime($fin))
        ];
    }

    usort($horarios, function ($a, $b) {
        return strcmp(
            $a['dia'] . $a['inicio'] . $a['fin'],
            $b['dia'] . $b['inicio'] . $b['fin']
        );
    });

    try {
        asegurarTablaNotificacionesHorario($conn);

        // Guardamos el horario anterior para reconocer un cambio real.
        $stmt_horario_anterior = $conn->prepare("
            SELECT dia_semana AS dia, hora_inicio AS inicio, hora_fin AS fin
            FROM disponibilidad
            WHERE id_tutor = :tutor_id
            ORDER BY dia_semana, hora_inicio, hora_fin
        ");
        $stmt_horario_anterior->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_horario_anterior->execute();
        $horarios_anteriores = $stmt_horario_anterior->fetchAll(PDO::FETCH_ASSOC);

        usort($horarios_anteriores, function ($a, $b) {
            return strcmp(
                $a['dia'] . $a['inicio'] . $a['fin'],
                $b['dia'] . $b['inicio'] . $b['fin']
            );
        });

        $hubo_cambio = json_encode($horarios_anteriores, JSON_UNESCAPED_UNICODE)
            !== json_encode($horarios, JSON_UNESCAPED_UNICODE);

        $conn->beginTransaction();

        $stmt_del_dispo = $conn->prepare(
            "DELETE FROM disponibilidad WHERE id_tutor = :tutor_id"
        );
        $stmt_del_dispo->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_del_dispo->execute();

        $stmt_ins_dispo = $conn->prepare("
            INSERT INTO disponibilidad (id_tutor, dia_semana, hora_inicio, hora_fin)
            VALUES (:tutor_id, :dia, :inicio, :fin)
        ");

        foreach ($horarios as $bloque) {
            $stmt_ins_dispo->execute([
                ':tutor_id' => $tutor_id,
                ':dia' => $bloque['dia'],
                ':inicio' => $bloque['inicio'],
                ':fin' => $bloque['fin']
            ]);
        }

        if ($hubo_cambio || !empty($horarios)) {
            // La solicitud se vincula con su oferta y, por medio de ella, con la materia.
            $stmt_inscripciones = $conn->prepare("
                SELECT
                    s.id AS id_solicitud,
                    s.id_estudiante,
                    s.fecha,
                    s.hora_inicio,
                    s.hora_fin,
                    o.id_materia,
                    m.nombre_materia AS materia
                FROM solicitudes_tutorias s
                INNER JOIN ofertas_tutorias o
                    ON o.id = s.id_oferta
                   AND o.id_tutor = s.id_tutor
                INNER JOIN materias m ON m.id = o.id_materia
                WHERE s.id_tutor = :tutor_id
                  AND s.estado = 'CONFIRMADA'
                  AND TIMESTAMP(s.fecha, s.hora_fin) >= NOW()
            ");
            $stmt_inscripciones->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
            $stmt_inscripciones->execute();
            $inscripciones = $stmt_inscripciones->fetchAll(PDO::FETCH_ASSOC);

            $descripcion_horario_nuevo = empty($horarios)
                ? 'Sin disponibilidad publicada'
                : implode(', ', array_map(function ($bloque) {
                    return $bloque['dia'] . ' '
                        . substr($bloque['inicio'], 0, 5) . '-'
                        . substr($bloque['fin'], 0, 5);
                }, $horarios));

            $stmt_notificacion = $conn->prepare("
                INSERT IGNORE INTO notificaciones_horario (
                    id_solicitud,
                    id_estudiante,
                    id_tutor,
                    id_materia,
                    fecha_tutoria,
                    hora_anterior_inicio,
                    hora_anterior_fin,
                    horario_nuevo,
                    mensaje,
                    cambio_hash
                ) VALUES (
                    :id_solicitud,
                    :id_estudiante,
                    :id_tutor,
                    :id_materia,
                    :fecha_tutoria,
                    :hora_anterior_inicio,
                    :hora_anterior_fin,
                    :horario_nuevo,
                    :mensaje,
                    :cambio_hash
                )
            ");

            $stmt_cerrar_notificaciones = $conn->prepare("
                UPDATE notificaciones_horario
                SET leida = 1
                WHERE id_solicitud = :id_solicitud
                  AND leida = 0
            ");

            usort($inscripciones, function ($a, $b) {
                return strcmp(
                    $a['fecha'] . $a['hora_inicio'] . str_pad((string) $a['id_solicitud'], 10, '0', STR_PAD_LEFT),
                    $b['fecha'] . $b['hora_inicio'] . str_pad((string) $b['id_solicitud'], 10, '0', STR_PAD_LEFT)
                );
            });

            $stmt_actualizar_horario = $conn->prepare("
                UPDATE solicitudes_tutorias
                SET hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin
                WHERE id = :id_solicitud
            ");

            $slots_ocupados = [];

            foreach ($inscripciones as $inscripcion) {
                $numero_dia = (int) date('N', strtotime($inscripcion['fecha']));
                $dia_tutoria = $dias_validos[$numero_dia - 1];
                $inicio_original_ts = strtotime($inscripcion['fecha'] . ' ' . $inscripcion['hora_inicio']);
                $fin_original_ts = strtotime($inscripcion['fecha'] . ' ' . $inscripcion['hora_fin']);
                $duracion_segundos = max(60, $fin_original_ts - $inicio_original_ts);
                $mejor_slot = null;
                $menor_distancia = PHP_INT_MAX;

                foreach ($horarios as $bloque) {
                    if ($bloque['dia'] !== $dia_tutoria) {
                        continue;
                    }

                    $inicio_bloque_ts = strtotime($inscripcion['fecha'] . ' ' . $bloque['inicio']);
                    $fin_bloque_ts = strtotime($inscripcion['fecha'] . ' ' . $bloque['fin']);

                    for (
                        $candidato_inicio = $inicio_bloque_ts;
                        $candidato_inicio + $duracion_segundos <= $fin_bloque_ts;
                        $candidato_inicio += $duracion_segundos
                    ) {
                        $candidato_fin = $candidato_inicio + $duracion_segundos;
                        $clave_slot = $inscripcion['fecha'] . '|'
                            . date('H:i:s', $candidato_inicio) . '|'
                            . date('H:i:s', $candidato_fin);

                        if (isset($slots_ocupados[$clave_slot])) {
                            continue;
                        }

                        $distancia = abs($candidato_inicio - $inicio_original_ts);
                        if ($distancia < $menor_distancia) {
                            $menor_distancia = $distancia;
                            $mejor_slot = [
                                'inicio' => date('H:i:s', $candidato_inicio),
                                'fin' => date('H:i:s', $candidato_fin),
                                'clave' => $clave_slot
                            ];
                        }
                    }
                }

                $stmt_cerrar_notificaciones->execute([
                    ':id_solicitud' => $inscripcion['id_solicitud']
                ]);

                $fecha_legible = date('d/m/Y', strtotime($inscripcion['fecha']));
                $hora_inicio_anterior = substr($inscripcion['hora_inicio'], 0, 5);
                $hora_fin_anterior = substr($inscripcion['hora_fin'], 0, 5);

                if ($mejor_slot === null) {
                    $mensaje = "El tutor cambió el horario de {$inscripcion['materia']}. "
                        . "Tu inscripción del {$fecha_legible}, de {$hora_inicio_anterior} "
                        . "a {$hora_fin_anterior}, no pudo reasignarse porque no hay "
                        . "un bloque disponible con la duración necesaria.";
                    $horario_nuevo_inscripcion = $descripcion_horario_nuevo;
                } else {
                    $slots_ocupados[$mejor_slot['clave']] = true;
                    $nuevo_inicio = $mejor_slot['inicio'];
                    $nuevo_fin = $mejor_slot['fin'];

                    if (
                        $nuevo_inicio === $inscripcion['hora_inicio']
                        && $nuevo_fin === $inscripcion['hora_fin']
                    ) {
                        continue;
                    }

                    $stmt_actualizar_horario->execute([
                        ':hora_inicio' => $nuevo_inicio,
                        ':hora_fin' => $nuevo_fin,
                        ':id_solicitud' => $inscripcion['id_solicitud']
                    ]);

                    $nuevo_inicio_legible = substr($nuevo_inicio, 0, 5);
                    $nuevo_fin_legible = substr($nuevo_fin, 0, 5);
                    $horario_nuevo_inscripcion = $dia_tutoria . ' '
                        . $nuevo_inicio_legible . '-' . $nuevo_fin_legible;
                    $mensaje = "El tutor cambió el horario de {$inscripcion['materia']}. "
                        . "Tu tutoría del {$fecha_legible} fue actualizada de "
                        . "{$hora_inicio_anterior}-{$hora_fin_anterior} a "
                        . "{$nuevo_inicio_legible}-{$nuevo_fin_legible}.";
                }

                $cambio_hash = hash('sha256', json_encode([
                    'solicitud' => (int) $inscripcion['id_solicitud'],
                    'hora_anterior' => [
                        $inscripcion['hora_inicio'],
                        $inscripcion['hora_fin']
                    ],
                    'horario_nuevo' => $horario_nuevo_inscripcion,
                    'registrado_en' => microtime(true)
                ], JSON_UNESCAPED_UNICODE));

                $stmt_notificacion->execute([
                    ':id_solicitud' => $inscripcion['id_solicitud'],
                    ':id_estudiante' => $inscripcion['id_estudiante'],
                    ':id_tutor' => $tutor_id,
                    ':id_materia' => $inscripcion['id_materia'],
                    ':fecha_tutoria' => $inscripcion['fecha'],
                    ':hora_anterior_inicio' => $inscripcion['hora_inicio'],
                    ':hora_anterior_fin' => $inscripcion['hora_fin'],
                    ':horario_nuevo' => $horario_nuevo_inscripcion,
                    ':mensaje' => $mensaje,
                    ':cambio_hash' => $cambio_hash
                ]);
            }
        }

        $conn->commit();
        redirigir('exito', 'disponibilidad');

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error al actualizar disponibilidad, sincronizar tutorías y notificar cambios: " . $e->getMessage());
        redirigir('error', 'db_disponibilidad');
    }
}

// 7. **REDIRECCIÓN POR DEFECTO** (Si no se reconoce la sección)
redirigir('error', 'seccion_invalida');

?>
