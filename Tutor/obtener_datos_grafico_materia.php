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

// 2. CONSULTA SQL: Contar tutorías completadas por materia y limitar al TOP 5
$sql = "
    SELECT 
        m.nombre_materia AS materia_nombre, 
        COUNT(s.id) AS total_tutorias
    FROM solicitudes_tutorias s 
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN materias m ON o.id_materia = m.id
    WHERE s.id_tutor = :id_tutor
    AND s.estado = 'COMPLETADA'
    GROUP BY m.nombre_materia
    ORDER BY total_tutorias DESC
    LIMIT 5
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar datos del gráfico por materia: " . $e->getMessage());
    echo json_encode(['error' => 'Error en la base de datos']);
    exit();
}

// 3. PROCESAR RESULTADOS AL FORMATO REQUERIDO
$labels = []; // Nombres de las materias
$data = [];   // Número de tutorías

foreach ($resultados as $row) {
    $labels[] = $row['materia_nombre'];
    $data[] = (int)$row['total_tutorias'];
}

// Preparamos el array final para el JSON
$datos_grafico = [
    'labels' => $labels,
    'data' => $data 
];

// 4. DEVOLVER JSON
echo json_encode($datos_grafico);

?>