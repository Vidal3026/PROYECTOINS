<?php
session_start();
include '../Includes/db.php';
include '../Includes/Wallet.php'; // Para usar obtener_saldo

// 1. VERIFICACIÓN DE SESIÓN Y ROL
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$tutor_id = $_SESSION['id'];

// 2. OBTENER Y VALIDAR DATOS DEL POST
$monto = filter_input(INPUT_POST, 'monto_retiro', FILTER_VALIDATE_FLOAT);
$metodo = trim($_POST['metodo_pago'] ?? '');
$datos = trim($_POST['datos_pago'] ?? '');

if (!$monto || $monto < 10.00 || empty($metodo) || empty($datos)) {
    header('Location: Billetera.php?error=campos_incompletos');
    exit();
}

try {
    // 3. VERIFICAR SALDO SUFICIENTE
    $saldo_actual = obtener_saldo($conn, $tutor_id);

    if ($monto > $saldo_actual) {
        // Redirigir con mensaje de error específico
        $msg = urlencode("El monto solicitado ($" . number_format($monto, 2) . ") excede tu saldo disponible ($" . number_format($saldo_actual, 2) . ")");
        header("Location: Billetera.php?error=saldo_insuficiente&msg={$msg}");
        exit();
    }

    // 4. CREAR LA SOLICITUD DE RETIRO (Estado PENDIENTE)
    $sql = "INSERT INTO solicitudes_retiro (id_tutor, monto, metodo_pago, datos_pago, estado) 
            VALUES (:id_tutor, :monto, :metodo, :datos, 'PENDIENTE')";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_tutor', $tutor_id, PDO::PARAM_INT);
    $stmt->bindParam(':monto', $monto);
    $stmt->bindParam(':metodo', $metodo);
    $stmt->bindParam(':datos', $datos);
    $stmt->execute();

    // 5. ÉXITO
    header('Location: Billetera.php?success=retiro_pendiente');
    exit();

} catch (PDOException $e) {
    error_log("Error al crear solicitud de retiro: " . $e->getMessage());
    header('Location: Billetera.php?error=db_retiro');
    exit();
}
?>