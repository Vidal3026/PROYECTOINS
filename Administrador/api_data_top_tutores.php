<?php
// Admin/api_data_top_tutores.php (CORREGIDO: ASOCIA COMISION CON EL TUTOR VIA SOLICITUD)
session_start();
include '../Includes/db.php'; 
header('Content-Type: application/json');

// Recoger y validar fechas del filtro
$fecha_inicio = $_GET['fi'] ?? date('Y-m-d', strtotime('-90 days'));
$fecha_fin = $_GET['ff'] ?? date('Y-m-d');

$datos_grafico = ['labels' => [], 'data' => []];

try {
    // 1. EXTRAER EL ID DE LA SOLICITUD DE LA COLUMNA 'referencia'
    // 2. UNIR CON 'solicitudes_tutorias' para obtener el id_tutor
    // 3. UNIR CON 'usuarios' para obtener el nombre del tutor
    $sql = "
    SELECT 
        u.nombre,
        SUM(m.monto) AS total_comision
    FROM movimientos_billetera m
    
    -- Expresión regular o función para obtener el ID de solicitud de la referencia:
    -- Esto ASUME que la referencia SIEMPRE es como 'Comisión Plataforma Solicitud #ID'
    JOIN solicitudes_tutorias st ON st.id = CAST(SUBSTRING_INDEX(m.referencia, '#', -1) AS UNSIGNED)
    
    -- Ahora unimos a usuarios (tutores)
    JOIN usuarios u ON st.id_tutor = u.id
    
    WHERE m.tipo = 'COMISION' 
      AND m.fecha_movimiento BETWEEN :fi AND DATE_ADD(:ff, INTERVAL 1 DAY)
    GROUP BY u.nombre
    ORDER BY total_comision DESC
    LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':fi' => $fecha_inicio, ':ff' => $fecha_fin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        $datos_grafico['labels'][] = htmlspecialchars($row['nombre']);
        $datos_grafico['data'][] = (float)($row['total_comision'] ?? 0);
    }

    echo json_encode($datos_grafico);

} catch (PDOException $e) {
    // Mostrar error si la consulta SQL es inválida o falla la conversión de datos
    http_response_code(500);
    error_log("Error de BD en Top Tutores: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener ranking de tutores.']);
}
?>