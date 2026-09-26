<?php
session_start();

// Código para evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verifica si la sesión está activa, independientemente del rol
if (!isset($_SESSION['id'])) {
    // Si la sesión no existe, redirige al login
    header("Location: ../Login.php");
    exit();
}

// Incluye la conexión a la base de datos
include "../Includes/db.php";

// Obtener el ID del usuario de la sesión
$id_usuario = $_SESSION['id'];

// Consulta para obtener los datos del usuario usando el nombre de columna correcto
try {
    $stmt = $conn->prepare("SELECT id, nombre, apellido, correo, telefono, carrera, anio_ciclo, universidad_estudiante, rol, perfil_imagen FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        // Si no se encuentra el usuario, redirige con un error
        header("Location: index.php?error=Usuario no encontrado.");
        exit();
    }
} catch (PDOException $e) {
    // Muestra un error si la consulta falla
    die("Error en la base de datos: " . $e->getMessage());
}
?>