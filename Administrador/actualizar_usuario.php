<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

include "../Includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'] ?? null;
    $apellido = $_POST['apellido'] ?? null;
    $correo = $_POST['correo'] ?? null;
    $rol = $_POST['rol'] ?? null;
    $contrasena = $_POST['contrasena'] ?? null; // CAmbiar 'password' a 'contrasena'

    if ($id && $nombre && $apellido && $correo && $rol) {
        try {
            // Iniciar la consulta SQL con los campos obligatorios
            $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, rol = :rol";
            $parametros = [
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':correo' => $correo,
                ':rol' => $rol,
                ':id' => $id,
            ];

            // Si se proporcionó una nueva contraseña, la ciframos y la añadimos a la consulta
            if (!empty($contrasena)) { // CAmbiar 'password' a 'contrasena'
                $hashed_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
                $sql .= ", contrasena = :contrasena"; // CAmbiar 'password' a 'contrasena'
                $parametros[':contrasena'] = $hashed_contrasena; // CAmbiar 'password' a 'contrasena'
            }

            $sql .= " WHERE id = :id";

            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute($parametros)) {
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido.']);
}
?>