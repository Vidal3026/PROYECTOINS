<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

include "../Includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_SESSION['id'];
    $nombre = $_POST['nombre'] ?? null;
    $apellido = $_POST['apellido'] ?? null;
    $correo = $_POST['correo'] ?? null;
    $telefono = $_POST['telefono'] ?? null;
    $carrera = $_POST['carrera'] ?? null;
    $anio_ciclo = $_POST['anio_ciclo'] ?? null;
    $universidad_estudiante = $_POST['universidad_estudiante'] ?? null;
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? null;
    $perfil_imagen_existente = $_POST['perfil_imagen_existente'] ?? '';

    // Inicializar la ruta de la imagen con la existente
    $ruta_imagen_db = $perfil_imagen_existente;
    $error_imagen = false;

    // ===============================================================
    // LÓGICA DE SUBIDA DE IMAGEN
    // ===============================================================
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $archivo_temporal = $_FILES['foto_perfil']['tmp_name'];
        $nombre_archivo = basename($_FILES['foto_perfil']['name']);
        $directorio_destino = '../uploads/'; 
        // La carpeta 'uploads' debe estar en el directorio raíz, fuera de 'Estudiante/'

        $tipo_archivo = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        
        // 1. Validar tipo de archivo
        if (!in_array($tipo_archivo, $extensiones_permitidas)) {
            $error_imagen = true;
            echo json_encode(['success' => false, 'message' => 'Error: Solo se permiten archivos JPG, JPEG, PNG y GIF.']);
            exit();
        }

        // 2. Definir nuevo nombre y ruta
        $nuevo_nombre = "perfil_{$id}.{$tipo_archivo}";
        $ruta_destino_servidor = $directorio_destino . $nuevo_nombre;
        // Ruta que se guardará en la base de datos (relativa a la carpeta principal)
        $ruta_imagen_db = "uploads/" . $nuevo_nombre;

        // 3. Mover el archivo subido
        if (move_uploaded_file($archivo_temporal, $ruta_destino_servidor)) {
            // Éxito: La ruta_imagen_db está lista para ser guardada en la base de datos
        } else {
            $error_imagen = true;
            echo json_encode(['success' => false, 'message' => 'Error al mover el archivo subido.']);
            exit();
        }
    }
    // ===============================================================

    if ($nombre && $apellido && $correo && !$error_imagen) {
        try {
            // Inicio de la consulta SQL
            $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, telefono = :telefono, carrera = :carrera, anio_ciclo = :anio_ciclo, universidad_estudiante = :universidad_estudiante, perfil_imagen = :perfil_imagen";
            
            // Parámetros obligatorios (incluyendo la ruta de la imagen)
            $parametros = [
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':correo' => $correo,
                ':telefono' => $telefono,
                ':carrera' => $carrera,
                ':anio_ciclo' => $anio_ciclo,
                ':universidad_estudiante' => $universidad_estudiante,
                ':perfil_imagen' => $ruta_imagen_db, // Se añade la nueva ruta de imagen
                ':id' => $id,
            ];

            // Condición para actualizar la contraseña
            if (!empty($nueva_contrasena)) {
                $hashed_contrasena = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                $sql .= ", contrasena = :contrasena";
                $parametros[':contrasena'] = $hashed_contrasena;
            }

            $sql .= " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute($parametros)) {
                echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
        }
    } else if (!$error_imagen) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido.']);
}
?>