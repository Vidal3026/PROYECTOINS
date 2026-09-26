<?php
session_start();
// Asegúrate de que db.php contiene $conn y Wallet.php contiene las funciones sueltas
include '../Includes/db.php'; 
include '../Includes/Wallet.php'; 

// 1. VERIFICACIÓN DE SESIÓN (ESTUDIANTE)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') { 
    header("Location: ../Login.php");
    exit();
}

// 2. RECUPERAR Y VALIDAR DATOS
$estudiante_id = $_SESSION['id'];
$monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
$metodo = filter_input(INPUT_POST, 'metodo_pago', FILTER_SANITIZE_STRING);
$referencia_simulada = "REC-" . time() . rand(100, 999);

if (!$monto || $monto < 5.00 || empty($metodo)) {
    $msg = 'Monto de recarga inválido o falta el método de pago.';
    header("Location: Billetera.php?error=recarga_fallida&msg=" . urlencode($msg));
    exit();
}

try {
    // INICIAR LA TRANSACCIÓN (Punto de inicio atómico)
    $conn->beginTransaction(); 

    // A. PREPARAR Y REGISTRAR LA RECARGA COMO COMPLETA
    $sql_recarga = "INSERT INTO recargas_estudiante 
                    (id_estudiante, monto, metodo_pago, estado, fecha_solicitud, fecha_confirmacion, referencia) 
                    VALUES (:id_estudiante, :monto, :metodo, 'COMPLETADO', NOW(), NOW(), :referencia)";
    
    $stmt_recarga = $conn->prepare($sql_recarga); // Define la variable stmt_recarga
    
    $stmt_recarga->bindParam(':id_estudiante', $estudiante_id, PDO::PARAM_INT);
    $stmt_recarga->bindParam(':monto', $monto);
    $stmt_recarga->bindParam(':metodo', $metodo);
    $stmt_recarga->bindParam(':referencia', $referencia_simulada);
    $stmt_recarga->execute(); // Ejecución

    // B. ACREDITAR EL SALDO USANDO LA FUNCIÓN MODIFICADA
    $referencia_log = 'Recarga de Fondos (' . $metodo . ')';
    acreditar_saldo_y_log($conn, $estudiante_id, $monto, $referencia_log);

    // Confirmar ambas operaciones
    $conn->commit();
    
    // Cambiado de "¡Recarga exitosa! Se han añadido..." a tu preferencia:
    $msg = "Recarga exitosa. Se han añadido \${$monto} a tu saldo."; // <-- Nuevo mensaje
    header("Location: Billetera.php?success=recarga_acreditada&msg=" . urlencode($msg));
    exit();

} catch (PDOException $e) {
    // Si ocurre cualquier error de DB (sintaxis, etc.), deshacer la transacción si está activa.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error de BD al procesar recarga: " . $e->getMessage());
    $msg = "Error de base de datos. El pago falló. Detalle: " . $e->getMessage();
    header("Location: Billetera.php?error=db_error&msg=" . urlencode($msg));
    exit();
    
} catch (Exception $e) {
    // Captura cualquier otro error de lógica (como el throw de acreditacion_saldo_y_log)
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error de lógica al acreditar: " . $e->getMessage());
    $msg = "Error de lógica del sistema al acreditar el saldo. Contacta a soporte.";
    header("Location: Billetera.php?error=logic_error&msg=" . urlencode($msg));
    exit();
}