<?php
// Admin/api_data_ingresos.php (Ahora calcula Ganancia Neta por COMISION)
session_start();
include '../Includes/db.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit();
}

$datos_grafico = ['labels' => [], 'data' => []];

try {
    // CORRECCIÓN: Usamos la tabla 'movimientos' y filtramos por 'tipo = COMISION'
    $sql = "SELECT 
                DATE_FORMAT(fecha_movimiento, '%Y-%m') AS mes_orden,
                DATE_FORMAT(fecha_movimiento, '%b %Y') AS mes_etiqueta,
                SUM(monto) AS total_ganancia_neta
            FROM movimientos_billetera
            WHERE tipo = 'COMISION' 
              AND fecha_movimiento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY mes_orden, mes_etiqueta
            ORDER BY mes_orden ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        $datos_grafico['labels'][] = $row['mes_etiqueta'];
        // Aseguramos que el valor sea un float, usando 0 si es NULL
        $datos_grafico['data'][] = (float)($row['total_ganancia_neta'] ?? 0);
    }

    echo json_encode($datos_grafico);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en api_data_ingresos (COMISION): " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener datos de comisión.']);
}
?>