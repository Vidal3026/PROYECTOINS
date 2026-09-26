<?php
session_start();
include '../Includes/db.php';
include '../Includes/Wallet.php'; 

// 1. VERIFICACIÓN DE SESIÓN (ADMIN)
// 🛑 Reemplaza 'admin' con el rol de tu administrador si es diferente
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: ../Login.php");
    exit();
}

// 2. VALIDAR ID DE RETIRO
$retiro_id = filter_input(INPUT_GET, 'retiro_id', FILTER_VALIDATE_INT);
if (!$retiro_id) {
    header('Location: SolicitudesAdministrador.php?error=no_retiro_id'); 
    exit();
}

try {
    // 3. OBTENER DETALLES DE LA SOLICITUD PENDIENTE
    $sql = "SELECT id_tutor, monto, estado 
            FROM solicitudes_retiro 
            WHERE id = :retiro_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':retiro_id', $retiro_id, PDO::PARAM_INT);
    $stmt->execute();
    $retiro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$retiro) {
        header('Location: SolicitudesAdministrador.php?error=retiro_no_existe');
        exit();
    }

    if ($retiro['estado'] !== 'PENDIENTE') {
        header('Location: SolicitudesAdministrador.php?error=retiro_no_pendiente');
        exit();
    }
    
    // 4. EJECUTAR EL RETIRO
    $resultado = ejecutar_retiro_aprobado(
        $conn, 
        (int)$retiro['id_tutor'], 
        (float)$retiro['monto'], 
        (int)$retiro_id
    );

    // 5. REDIRECCIÓN BASADA EN EL RESULTADO
    if ($resultado['success']) {
        // Éxito: Se descontó el saldo y se marcó como APROBADO
        $msg = urlencode($resultado['message']);
        header("Location: SolicitudesAdministrador.php?success=retiro_aprobado&msg={$msg}");
        exit();
    } else {
        // Fallo: Problemas de DB o concurrencia
        $msg = urlencode($resultado['message']);
        header("Location: SolicitudesAdministrador.php?error=retiro_fallido&msg={$msg}");
        exit();
    }

} catch (PDOException $e) {
    error_log("Error de Admin al aprobar retiro: " . $e->getMessage());
    header('Location: SolicitudesAdministrador.php?error=db_admin');
    exit();
}
?>