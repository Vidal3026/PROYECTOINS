<?php
// Configuración de la cabecera para devolver JSON
header('Content-Type: application/json');

// Incluir la conexión a la base de datos y la sesión
session_start();
include "../Includes/db.php"; 

// 1. VERIFICACIÓN DE SESIÓN (Tutor)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    // Devolver un array vacío si no está autenticado o no es tutor
    echo json_encode([]);
    exit();
}

$id_tutor = $_SESSION['id'];

// 2. CONSULTA DE TUTORÍAS (Adaptada para el Tutor)
$sql = "
    SELECT
        s.id, 
        s.fecha, 
        CASE
            WHEN s.estado = 'CONFIRMADA' AND s.fecha >= CURDATE()
            THEN COALESCE(d.hora_inicio, s.hora_inicio)
            ELSE s.hora_inicio
        END AS hora_inicio,
        CASE
            WHEN s.estado = 'CONFIRMADA' AND s.fecha >= CURDATE()
            THEN COALESCE(d.hora_fin, s.hora_fin)
            ELSE s.hora_fin
        END AS hora_fin,
        s.duracion,
        s.estado, 
        m.nombre_materia AS materia,
        e.nombre AS nombre_estudiante, 
        e.apellido AS apellido_estudiante
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    -- CAMBIO CLAVE: Unimos a la tabla de usuarios (e) para obtener los datos del ESTUDIANTE
    JOIN usuarios e ON s.id_estudiante = e.id 
    JOIN materias m ON o.id_materia = m.id 
    LEFT JOIN disponibilidad d ON d.id = (
        SELECT d2.id
        FROM disponibilidad d2
        WHERE d2.id_tutor = s.id_tutor
          AND d2.dia_semana = CASE WEEKDAY(s.fecha)
              WHEN 0 THEN 'LUNES'
              WHEN 1 THEN 'MARTES'
              WHEN 2 THEN 'MIÉRCOLES'
              WHEN 3 THEN 'JUEVES'
              WHEN 4 THEN 'VIERNES'
              WHEN 5 THEN 'SÁBADO'
              WHEN 6 THEN 'DOMINGO'
          END
        ORDER BY ABS(TIME_TO_SEC(d2.hora_inicio) - TIME_TO_SEC(s.hora_inicio)),
                 d2.hora_inicio
        LIMIT 1
    )
    -- CAMBIO CLAVE: Filtramos por el ID del TUTOR logueado
    WHERE s.id_tutor = :id_tutor
    AND s.estado IN ('CONFIRMADA', 'COMPLETADA')
    ORDER BY s.fecha, s.hora_inicio;
";

try {
    // Asumiendo que $conn es tu objeto PDO (incluido desde db.php)
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
    $stmt->execute();
    $tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay un error, lo registramos y devolvemos un JSON vacío
    error_log("Error al cargar eventos del calendario (Tutor): " . $e->getMessage());
    echo json_encode([]);
    exit();
}

// 3. CONVERSIÓN DE DATOS AL FORMATO DE FULLCALENDAR
$eventos = [];
foreach ($tutorias as $t) {
    
    // 3.1. Limpieza y validación de la duración
    $duracion = floatval($t['duracion']);
    if ($duracion <= 0) {
        $duracion = 1; // Duración por defecto si es inválida
    }
    
    // 3.2. Calcular la hora de fin
    $start_datetime_str = $t['fecha'] . ' ' . $t['hora_inicio'];
    
    try {
        $start_datetime = new DateTime($start_datetime_str);
        $end_datetime = new DateTime($t['fecha'] . ' ' . $t['hora_fin']);
        $end_time_iso = $end_datetime->format('Y-m-d\TH:i:s');
        
    } catch (Exception $e) {
        error_log("Error de cálculo de DateTime: " . $e->getMessage());
        $end_time_iso = null;
    }

    // 3.3. Definir el color según el estado
    // Usamos colores que coinciden con los que definimos en el código HTML/JS
    $backgroundColor = ($t['estado'] === 'COMPLETADA' ? '#6c757d' : '#007bff'); 

    // 3.4. Formato de evento para FullCalendar
    $eventos[] = [
        'id' => $t['id'],
        'title' => htmlspecialchars($t['materia']),
        'start' => $start_datetime->format('Y-m-d\TH:i:s'), 
        'end' => $end_time_iso, 
        'backgroundColor' => $backgroundColor, 
        'borderColor' => $backgroundColor,
        
        // Datos extendidos para el Modal (ahora incluimos el ESTUDIANTE)
        'extendedProps' => [
            'estudiante' => htmlspecialchars($t['nombre_estudiante'] . ' ' . $t['apellido_estudiante']),
            'duracion' => $t['duracion'],
            'estado' => $t['estado']
        ]
    ];
}

// 4. Devolver el array de eventos como JSON
echo json_encode($eventos);

?>
