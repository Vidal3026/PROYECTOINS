<?php
// Configuración de la cabecera para devolver JSON
header('Content-Type: application/json');

// Incluir la conexión a la base de datos y la sesión
session_start();
include '../Includes/db.php'; // Asegúrate de que esta ruta sea correcta

// 1. VERIFICACIÓN DE SESIÓN (Tutor)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    echo json_encode([]);
    exit();
}

$id_tutor = $_SESSION['id'];
$year_actual = date("Y");

// 2. CONSULTA SQL: Horas completadas por mes para el año actual
$sql = "
    SELECT 
        MONTH(fecha) AS mes, 
        SUM(duracion) AS total_horas
    FROM solicitudes_tutorias 
    WHERE id_tutor = :id_tutor
    AND estado = 'COMPLETADA'
    AND YEAR(fecha) = :year_actual
    GROUP BY MONTH(fecha)
    ORDER BY mes ASC
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
    $stmt->bindParam(':year_actual', $year_actual, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar datos del gráfico mensual: " . $e->getMessage());
    echo json_encode(['error' => 'Error en la base de datos']);
    exit();
}

// 3. PROCESAR RESULTADOS AL FORMATO REQUERIDO
// Inicializar un array con 12 meses (0 horas) para garantizar que los meses sin datos aparezcan como cero
$horas_por_mes = array_fill(1, 12, 0);

foreach ($resultados as $row) {
    // Asignar el total de horas a su respectivo mes
    $horas_por_mes[(int)$row['mes']] = (float)$row['total_horas'];
}

// Los meses en español para las etiquetas del gráfico
$etiquetas_meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

// Preparamos el array final para el JSON
$datos_grafico = [
    'labels' => $etiquetas_meses,
    'data' => array_values($horas_por_mes) // Solo los valores de las horas
];

// 4. DEVOLVER JSON
echo json_encode($datos_grafico);

?>