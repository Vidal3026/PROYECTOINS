<?php
// Configuración de la cabecera para devolver JSON
header('Content-Type: application/json');

// Incluir la conexión a la base de datos y la sesión
session_start();
// NOTA: Reemplaza con la ruta correcta a tu archivo de conexión si es necesario
include "../Includes/db.php"; 

// 1. VERIFICACIÓN DE SESIÓN (Estudiante)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    // Devolver un array vacío si no está autenticado o no es estudiante
    echo json_encode([]);
    exit();
}

$id_estudiante = $_SESSION['id'];

// 2. CONSULTA DE TUTORÍAS
$sql = "
    SELECT
        s.id, 
        s.fecha, 
        s.hora_inicio, 
        s.duracion,
        s.estado, 
        m.nombre_materia AS materia,
        t.nombre AS nombre_tutor, 
        t.apellido AS apellido_tutor
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN usuarios t ON s.id_tutor = t.id 
    JOIN materias m ON o.id_materia = m.id 
    WHERE s.id_estudiante = :id_estudiante
    AND s.estado IN ('CONFIRMADA', 'COMPLETADA')
    ORDER BY s.fecha, s.hora_inicio;
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_estudiante', $id_estudiante, PDO::PARAM_INT);
    $stmt->execute();
    $tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar eventos del calendario: " . $e->getMessage());
    echo json_encode([]);
    exit();
}

// 3. CONVERSIÓN DE DATOS AL FORMATO DE FULLCALENDAR
$eventos = [];
foreach ($tutorias as $t) {
    
    // 3.1. Limpieza y validación de la duración
    // Esto asegura que $duracion sea un número válido para el cálculo
    $duracion = floatval($t['duracion']);
    if ($duracion <= 0) {
        $duracion = 1; // Asegura una duración mínima si el valor es inválido o cero
    }
    
    // 3.2. Calcular la hora de fin
    $start_datetime_str = $t['fecha'] . ' ' . $t['hora_inicio'];
    
    try {
        $start_datetime = new DateTime($start_datetime_str);
        $end_datetime = clone $start_datetime;
        
        // El cálculo se hace usando la duración limpia
        $end_datetime->modify('+' . $duracion . ' hour'); 
        $end_time_iso = $end_datetime->format('Y-m-d\TH:i:s');
        
    } catch (Exception $e) {
        // En caso de fallo en el cálculo (ej. formato de fecha/hora inválido)
        error_log("Error de cálculo de DateTime: " . $e->getMessage());
        $end_time_iso = null; // Enviar NULL si falla
    }

    // 3.3. Definir el color según el estado
    $backgroundColor = ($t['estado'] === 'COMPLETADA' ? '#6c757d' : '#007bff'); 

    // 3.4. Formato de evento para FullCalendar
    $eventos[] = [
        'id' => $t['id'],
        'title' => htmlspecialchars($t['materia']),
        'start' => $start_datetime->format('Y-m-d\TH:i:s'), // Formato ISO 8601 (Inicio)
        'end' => $end_time_iso,                               // <--- ¡AQUÍ ESTÁ LA HORA DE FIN!
        'backgroundColor' => $backgroundColor, 
        'borderColor' => $backgroundColor,
        
        // Datos extendidos para el Modal
        'extendedProps' => [
            'tutor' => htmlspecialchars($t['nombre_tutor'] . ' ' . $t['apellido_tutor']),
            'duracion' => $t['duracion'],
            'estado' => $t['estado']
        ]
    ];
}

// 4. Devolver el array de eventos como JSON
echo json_encode($eventos);