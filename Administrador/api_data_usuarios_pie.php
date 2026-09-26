<?php
// Admin/api_data_usuarios_pie.php
session_start();
include '../Includes/db.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit();
}

$datos_grafico = [
    'labels' => [], // Roles (Tutor, Estudiante)
    'data' => []    // Cantidad por rol
];

try {
    // Consulta: Contar usuarios por rol (solo tutores y estudiantes)
    $sql = "SELECT rol, COUNT(*) AS total 
            FROM usuarios 
            WHERE rol IN ('tutor', 'estudiante') 
            GROUP BY rol";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        // Capitalizamos el rol para la etiqueta
        $datos_grafico['labels'][] = ucfirst($row['rol']);
        $datos_grafico['data'][] = (int)$row['total'];
    }

    echo json_encode($datos_grafico);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en api_data_usuarios_pie: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener datos de usuarios.']);
}
?>