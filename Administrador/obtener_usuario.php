<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

include "../Includes/db.php";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT id, nombre, apellido, correo, rol FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            echo json_encode($usuario);
        } else {
            echo json_encode(['error' => 'Usuario no encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'ID no proporcionado.']);
}
?>