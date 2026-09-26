<?php
// Admin/api_data_flujo_efectivo.php
session_start();
include '../Includes/db.php'; 
header('Content-Type: application/json');

// Recoger y validar fechas del filtro (usadas en el fetch de JS)
$fecha_inicio = $_GET['fi'] ?? date('Y-m-d', strtotime('-90 days'));
$fecha_fin = $_GET['ff'] ?? date('Y-m-d');

$datos_grafico = [
    'labels' => [], 
    'ingreso_bruto' => [], 
    'comision_neta' => []
];

try {
    $sql = "SELECT 
                DATE_FORMAT(fecha_movimiento, '%Y-%m') AS mes_orden,
                DATE_FORMAT(fecha_movimiento, '%b %Y') AS mes_etiqueta,
                SUM(CASE WHEN tipo = 'INGRESO' THEN monto ELSE 0 END) AS ingreso_bruto,
                SUM(CASE WHEN tipo = 'COMISION' THEN monto ELSE 0 END) AS comision_neta
            FROM movimientos_billetera
            WHERE fecha_movimiento BETWEEN :fi AND DATE_ADD(:ff, INTERVAL 1 DAY)
            GROUP BY mes_orden, mes_etiqueta
            ORDER BY mes_orden ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':fi' => $fecha_inicio, ':ff' => $fecha_fin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        $datos_grafico['labels'][] = $row['mes_etiqueta'];
        $datos_grafico['ingreso_bruto'][] = (float)($row['ingreso_bruto'] ?? 0);
        $datos_grafico['comision_neta'][] = (float)($row['comision_neta'] ?? 0);
    }

    echo json_encode($datos_grafico);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener flujo de efectivo.']);
}
?>