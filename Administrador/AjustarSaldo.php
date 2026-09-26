<?php
// Admin/AjustarSaldo.php
session_start();
include '../Includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin" || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Login.php");
    exit();
}

$id_billetera = $_POST['id_billetera'] ?? null;
$monto = floatval($_POST['monto'] ?? 0);
$tipo = $_POST['tipo'] ?? ''; // Será 'RECARGA' (para INGRESO) o 'AJUSTE_NEGATIVO' (para EGRESO)
$referencia = $_POST['referencia'] ?? 'Ajuste Manual Admin';

if ($id_billetera && $monto > 0 && in_array($tipo, ['RECARGA', 'AJUSTE_NEGATIVO'])) {
    
    try {
        $conn->beginTransaction();

        // Determinar el monto final para el UPDATE (positivo o negativo)
        $monto_final = ($tipo === 'AJUSTE_NEGATIVO') ? -$monto : $monto;
        
        // CORRECCIÓN CLAVE: Mapear el tipo de operación del formulario a los tipos de DB permitidos.
        $tipo_movimiento = ($tipo === 'AJUSTE_NEGATIVO') ? 'EGRESO' : 'INGRESO';

        // La referencia se modificará para guardar el motivo real (Recarga o Ajuste)
        $referencia_final = ($tipo === 'AJUSTE_NEGATIVO') ? "AJUSTE NEGATIVO: " . $referencia : "RECARGA MANUAL: " . $referencia;


        // 1. ACTUALIZAR SALDO DE LA BILLETERA
        $sql_update = "UPDATE billeteras SET saldo = saldo + :monto_final WHERE id = :id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([':monto_final' => $monto_final, ':id' => $id_billetera]);

        // 2. REGISTRAR EL MOVIMIENTO EN movimientos_billetera
        $sql_insert = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia, fecha_movimiento)
                       VALUES (:id_billetera, :tipo_mov, :monto_insert, :referencia_final, NOW())";
        
        $monto_insert = abs($monto_final);

        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([
            ':id_billetera' => (int)$id_billetera,
            ':tipo_mov' => $tipo_movimiento, // Usará 'INGRESO' o 'EGRESO'
            ':monto_insert' => $monto_insert,
            ':referencia_final' => $referencia_final // Referencia detallada
        ]);

        $conn->commit();
        
        header("Location: Billetera.php?status=ajuste_exitoso");
        exit();

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error al ajustar saldo: " . $e->getMessage());
        header("Location: Billetera.php?status=error&msg=db_error");
        exit();
    }
} else {
    header("Location: Billetera.php?status=error&msg=datos_invalidos");
    exit();
}
?>