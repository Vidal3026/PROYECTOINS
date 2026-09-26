<?php
session_start();

// Validar si el usuario tiene permiso de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    header("Location: ../Login.php");
    exit();
}

// Incluir el archivo de conexión a la base de datos
include "../Includes/db.php";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // 1. INICIAR TRANSACCIÓN para asegurar la integridad de la base de datos
        $conn->beginTransaction();

        // 2. ELIMINAR REGISTROS DEPENDIENTES

        // a. BILLETERAS: (La tabla que causó el error 1451)
        $sql_billeteras = "DELETE FROM billeteras WHERE id_usuario = :id";
        $stmt_billeteras = $conn->prepare($sql_billeteras);
        $stmt_billeteras->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_billeteras->execute();

        // b. SOLICITUDES DE RETIRO: (Se asume dependencia del usuario/tutor)
        // Es necesario borrar las solicitudes de retiro del usuario antes de borrar el usuario.
        // Asumiendo que esta tabla depende de 'usuarios' (columna 'id_tutor' o similar).
        $sql_retiros = "DELETE FROM solicitudes_retiro WHERE id_tutor = :id"; 
        $stmt_retiros = $conn->prepare($sql_retiros);
        $stmt_retiros->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_retiros->execute();

        // c. OTROS REGISTROS DEPENDIENTES (Ej: Solicitudes de Tutorias, Mensajes, etc.)
        // Si tu tabla 'solicitudes_tutorias' tiene id_tutor o id_estudiante, añádelo aquí:
        /*
        $sql_tutorias = "DELETE FROM solicitudes_tutorias WHERE id_tutor = :id OR id_estudiante = :id";
        $stmt_tutorias = $conn->prepare($sql_tutorias);
        $stmt_tutorias->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_tutorias->execute();
        */


        // 3. ELIMINAR EL USUARIO DE LA TABLA PRINCIPAL
        $sql_usuario = "DELETE FROM usuarios WHERE id = :id";
        $stmt_usuario = $conn->prepare($sql_usuario);
        $stmt_usuario->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_usuario->execute();
        
        // 4. CONFIRMAR LA TRANSACCIÓN
        $conn->commit();

        // 5. REDIRECCIÓN
        if ($stmt_usuario->rowCount() > 0) {
            header("Location: GestionarUsuarios.php?status=eliminado_exitoso");
            exit();
        } else {
            header("Location: GestionarUsuarios.php?status=no_encontrado");
            exit();
        }

    } catch (PDOException $e) {
        // Si algo falla, deshacer todos los cambios de la transacción
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // Mostrar el error real para el administrador
        die("Error en la base de datos: " . $e->getMessage()); 
    }
} else {
    // Si no se recibió un ID válido
    header("Location: GestionarUsuarios.php?status=no_id");
    exit();
}
?>