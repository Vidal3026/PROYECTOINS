<?php
/**
 * Obtiene el ID de la billetera para un usuario. Si no existe, la crea.
 * @param PDO $conn Conexión a la base de datos.
 * @param int $id_usuario ID del usuario (tutor o estudiante).
 * @return int|false ID de la billetera si tiene éxito, o false si falla.
 */
function obtener_o_crear_billetera_id($conn, $id_usuario)
{
    // 1. Intentar obtener el ID de la billetera existente
    $sql_select = "SELECT id FROM billeteras WHERE id_usuario = :id_usuario";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $stmt_select->execute();

    if ($billetera = $stmt_select->fetch(PDO::FETCH_ASSOC)) {
        return $billetera['id']; // Ya existe
    }

    // 2. Si no existe, crear la billetera con saldo 0.00
    try {
        $sql_insert = "INSERT INTO billeteras (id_usuario, saldo) VALUES (:id_usuario, 0.00)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt_insert->execute();

        // Devolver el ID recién insertado
        return $conn->lastInsertId();

    } catch (PDOException $e) {
        error_log("Error al crear billetera para usuario {$id_usuario}: " . $e->getMessage());
        return false;
    }
}
function obtener_saldo($conn, $id_usuario)
{
    $billetera_id = obtener_o_crear_billetera_id($conn, $id_usuario);
    if (!$billetera_id) {
        return 0.00; // Asumimos 0 si no se pudo crear/obtener
    }

    $sql = "SELECT saldo FROM billeteras WHERE id = :id_billetera";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_billetera', $billetera_id, PDO::PARAM_INT);
    $stmt->execute();

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    return $resultado ? (float) $resultado['saldo'] : 0.00;
}

function obtener_historial_movimientos($conn, $id_billetera)
{
    $sql = "SELECT tipo, monto, referencia, fecha_movimiento 
            FROM movimientos_billetera 
            WHERE id_billetera = :id_billetera
            ORDER BY fecha_movimiento DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_billetera', $id_billetera, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Procesa el pago total de una tutoría, calculando la comisión de la plataforma (10%)
 * y registrando el ingreso neto al tutor (90%).
 * * @param PDO $conn Conexión a la base de datos.
 * @param int $id_tutor ID del tutor que realizó la tutoría.
 * @param float $monto_total_pagado Monto total que el estudiante pagó por la tutoría.
 * @param string $id_solicitud Referencia del ID de la solicitud de tutoría.
 * @return bool True si ambas transacciones (ingreso y comisión) fueron exitosas, false en caso contrario.
 */
function procesar_pago_tutoria($conn, $id_tutor, $monto_total_pagado, $id_solicitud)
{
    // Definición de la comisión (10%)
    $comision_porcentaje = 0.1; // 10%
    
    // 1. CÁLCULO DE MONTOS
    $comision_monto = round($monto_total_pagado * $comision_porcentaje, 2);
    $ingreso_neto_tutor = $monto_total_pagado - $comision_monto; 
    
    // 2. OBTENER ID DE BILLETERA
    // Aseguramos que la billetera del tutor exista.
    $id_billetera_tutor = obtener_o_crear_billetera_id($conn, $id_tutor);
    if (!$id_billetera_tutor) {
        error_log("No se pudo obtener/crear la billetera para el tutor ID: {$id_tutor}");
        return false;
    }
    
    try {
        $conn->beginTransaction();

        // --- TRANSACCIÓN A: REGISTRO DE INGRESO NETO AL TUTOR (98%) ---
        
        // 1. Insertar el registro de ingreso neto en movimientos_billetera
        $sql_ingreso_tutor = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia) 
                              VALUES (:id_billetera, 'INGRESO', :monto, :referencia)";
        $stmt_ingreso = $conn->prepare($sql_ingreso_tutor);
        $stmt_ingreso->bindParam(':id_billetera', $id_billetera_tutor, PDO::PARAM_INT);
        $stmt_ingreso->bindParam(':monto', $ingreso_neto_tutor);
        $stmt_ingreso->bindValue(':referencia', "Pago Solicitud #{$id_solicitud} (98% Neto)");
        $stmt_ingreso->execute();

        // 2. Actualizar el saldo del tutor
        $sql_update_saldo = "UPDATE billeteras SET saldo = saldo + :monto WHERE id = :id_billetera";
        $stmt_saldo = $conn->prepare($sql_update_saldo);
        $stmt_saldo->bindParam(':monto', $ingreso_neto_tutor);
        $stmt_saldo->bindParam(':id_billetera', $id_billetera_tutor, PDO::PARAM_INT);
        $stmt_saldo->execute();

        // --- TRANSACCIÓN B: REGISTRO DE COMISIÓN DE PLATAFORMA (10%) ---
        // Nota: Esta es una transacción de control. No afecta el saldo del tutor, 
        // pero sí registra el egreso total del pago del estudiante como una comisión.
        
        $sql_comision = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia) 
                         VALUES (:id_billetera, 'COMISION', :monto, :referencia)";
        $stmt_comision = $conn->prepare($sql_comision);
        $stmt_comision->bindParam(':id_billetera', $id_billetera_tutor, PDO::PARAM_INT);
        $stmt_comision->bindParam(':monto', $comision_monto);
        $stmt_comision->bindValue(':referencia', "Comisión Plataforma Solicitud #{$id_solicitud} (10%)");
        $stmt_comision->execute();

        $conn->commit();
        return true;

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error de BD al procesar pago de tutoría {$id_solicitud}: " . $e->getMessage());
        return false;
    }
}

/**
 * Procesa la transacción completa de una tutoría:
 * 1. Resta el monto total de la billetera del estudiante.
 * 2. Asigna el monto neto (90%) al tutor.
 * 3. Registra la comisión (10%) de la plataforma.
 * * @param PDO $conn Conexión a la base de datos.
 * @param int $id_estudiante ID del usuario que paga.
 * @param int $id_tutor ID del usuario que recibe.
 * @param float $monto_total_pagado Monto total de la tutoría.
 * @param int $id_solicitud ID de la solicitud de tutoría.
 * @return array ['success' => bool, 'message' => string]
 */
function procesar_transaccion_tutoria($conn, $id_estudiante, $id_tutor, $monto_total_pagado, $id_solicitud)
{
    $comision_porcentaje = 0.1; // 10%
    $monto_a_restar_estudiante = abs($monto_total_pagado);
    $comision_monto = round($monto_total_pagado * $comision_porcentaje, 2);
    $ingreso_neto_tutor = $monto_total_pagado - $comision_monto; 

    // --- 1. VERIFICAR FONDOS DEL ESTUDIANTE ---
    $saldo_estudiante = obtener_saldo($conn, $id_estudiante);
    if ($saldo_estudiante < $monto_a_restar_estudiante) {
        return ['success' => false, 'message' => "Saldo insuficiente. Se requieren $" . number_format($monto_a_restar_estudiante, 2) . ", saldo actual $" . number_format($saldo_estudiante, 2)];
    }

    // --- 2. OBTENER IDs DE BILLETERA ---
    $id_billetera_estudiante = obtener_o_crear_billetera_id($conn, $id_estudiante);
    $id_billetera_tutor = obtener_o_crear_billetera_id($conn, $id_tutor);

    if (!$id_billetera_estudiante || !$id_billetera_tutor) {
        return ['success' => false, 'message' => 'Error al obtener las billeteras de los usuarios.'];
    }
    
    try {
        $conn->beginTransaction();

        $referencia_pago = "Solicitud #{$id_solicitud}";

        // A. EGRESO DEL ESTUDIANTE (Monto Total)
        // 1. Registrar movimiento de egreso para el estudiante
        $sql_egreso = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia) 
                       VALUES (:id_b_estudiante, 'EGRESO', :monto, :referencia)";
        $stmt_egreso = $conn->prepare($sql_egreso);
        $stmt_egreso->bindParam(':id_b_estudiante', $id_billetera_estudiante, PDO::PARAM_INT);
        $stmt_egreso->bindParam(':monto', $monto_a_restar_estudiante);
        $stmt_egreso->bindValue(':referencia', "Pago {$referencia_pago}");
        $stmt_egreso->execute();

        // 2. Restar el saldo del estudiante
        $sql_update_estudiante = "UPDATE billeteras SET saldo = saldo - :monto WHERE id = :id_billetera";
        $stmt_update_estudiante = $conn->prepare($sql_update_estudiante);
        $stmt_update_estudiante->bindParam(':monto', $monto_a_restar_estudiante);
        $stmt_update_estudiante->bindParam(':id_billetera', $id_billetera_estudiante, PDO::PARAM_INT);
        $stmt_update_estudiante->execute();
        
        // B. INGRESO AL TUTOR (Monto Neto 98%)
        $sql_ingreso = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia) 
                        VALUES (:id_b_tutor, 'INGRESO', :monto, :referencia)";
        $stmt_ingreso = $conn->prepare($sql_ingreso);
        $stmt_ingreso->bindParam(':id_b_tutor', $id_billetera_tutor, PDO::PARAM_INT);
        $stmt_ingreso->bindParam(':monto', $ingreso_neto_tutor);
        $stmt_ingreso->bindValue(':referencia', "Ingreso {$referencia_pago} (98% Neto)");
        $stmt_ingreso->execute();
        
        // 2. Sumar el saldo al tutor
        $sql_update_tutor = "UPDATE billeteras SET saldo = saldo + :monto WHERE id = :id_billetera";
        $stmt_update_tutor = $conn->prepare($sql_update_tutor);
        $stmt_update_tutor->bindParam(':monto', $ingreso_neto_tutor);
        $stmt_update_tutor->bindParam(':id_billetera', $id_billetera_tutor, PDO::PARAM_INT);
        $stmt_update_tutor->execute();

        // C. REGISTRO DE COMISIÓN (Plataforma 10%)
        $sql_comision = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia) 
                         VALUES (:id_b_tutor, 'COMISION', :monto, :referencia)";
        $stmt_comision = $conn->prepare($sql_comision);
        // Usamos la billetera del tutor solo como referencia para la auditoría, ya que esta comisión no afecta su saldo.
        // Si tienes una billetera para la plataforma, deberías usar su ID aquí.
        $stmt_comision->bindParam(':id_b_tutor', $id_billetera_tutor, PDO::PARAM_INT); 
        $stmt_comision->bindParam(':monto', $comision_monto);
        $stmt_comision->bindValue(':referencia', "Comisión Plataforma {$referencia_pago} (10%)");
        $stmt_comision->execute();

        $conn->commit();
        return ['success' => true, 'message' => "Transacción completada con éxito. Tutor recibió $" . number_format($ingreso_neto_tutor, 2)];

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error fatal en transacción de tutoría {$id_solicitud}: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error de base de datos durante la transacción.'];
    }
}

// ... (Dentro de Includes/Wallet.php, después de las otras funciones)

/**
 * Ejecuta la transacción de retiro (EGRESO) una vez que el administrador
 * ha simulado o realizado el pago real fuera del sistema.
 * @param PDO $conn Conexión a la base de datos.
 * @param int $id_tutor ID del tutor afectado.
 * @param float $monto Cantidad a restar del saldo del tutor.
 * @param int $id_retiro ID de la solicitud de retiro a aprobar.
 * @return array ['success' => bool, 'message' => string]
 */
function ejecutar_retiro_aprobado($conn, $id_tutor, $monto, $id_retiro)
{
    $monto_abs = abs($monto); // Aseguramos que es positivo para el registro
    
    // 1. Obtener la billetera del tutor
    $id_billetera = obtener_o_crear_billetera_id($conn, $id_tutor);
    if (!$id_billetera) {
        return ['success' => false, 'message' => 'Error al obtener la billetera del tutor.'];
    }

    try {
        $conn->beginTransaction();

        // 2. Insertar el registro de EGRESO en movimientos_billetera
        $sql_mov = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia) 
                    VALUES (:id_billetera, 'EGRESO', :monto, :referencia)";
        $stmt_mov = $conn->prepare($sql_mov);
        $stmt_mov->bindParam(':id_billetera', $id_billetera, PDO::PARAM_INT);
        $stmt_mov->bindParam(':monto', $monto_abs);
        $stmt_mov->bindValue(':referencia', "Retiro Solicitud #{$id_retiro} Aprobado");
        $stmt_mov->execute();

        // 3. Restar el saldo en la tabla billeteras
        // Usamos -$monto_abs para restar
        $sql_billetera = "UPDATE billeteras SET saldo = saldo - :mmonto WHERE id = :id_billetera";
        $stmt_billetera = $conn->prepare($sql_billetera);
        $stmt_billetera->bindParam(':mmonto', $monto_abs); 
        $stmt_billetera->bindParam(':id_billetera', $id_billetera, PDO::PARAM_INT);
        $stmt_billetera->execute();
        
        // 4. Actualizar el estado de la solicitud de retiro a APROBADO
        $sql_update_retiro = "UPDATE solicitudes_retiro 
                              SET estado = 'APROBADO', fecha_procesamiento = NOW() 
                              WHERE id = :id_retiro AND estado = 'PENDIENTE'";
        $stmt_update_retiro = $conn->prepare($sql_update_retiro);
        $stmt_update_retiro->bindParam(':id_retiro', $id_retiro, PDO::PARAM_INT);
        $stmt_update_retiro->execute();
        
        // Verificación de filas afectadas (solo para mayor seguridad)
        if ($stmt_update_retiro->rowCount() === 0) {
            $conn->rollBack();
            return ['success' => false, 'message' => 'Error: Solicitud ya procesada o ID inválido.'];
        }

        $conn->commit();
        return ['success' => true, 'message' => "Retiro #{$id_retiro} de $".number_format($monto, 2)." aprobado y ejecutado."];

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error de BD al ejecutar retiro: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error de base de datos durante la ejecución.'];
    }
}

/**
 * Acredita un monto al saldo del usuario y registra el movimiento.
 * NOTA: Esta función ASUME que ya se ha iniciado una transacción (beginTransaction)
 * en el script que la llama.
 * * @param PDO $conn La conexión a la base de datos.
 * @param int $user_id El ID del estudiante.
 * @param float $monto El monto a depositar.
 * @param string $referencia La referencia del movimiento.
 */
function acreditar_saldo_y_log(PDO $conn, int $user_id, float $monto, string $referencia) {
    if ($monto <= 0) {
        throw new Exception("El monto a depositar debe ser positivo.");
    }
    
    // Obtener o crear el ID de la billetera (reutilizando tu función existente)
    // Asumo que obtener_o_crear_billetera_id() está definida en este mismo archivo.
    $billetera_id = obtener_o_crear_billetera_id($conn, $user_id);

    // ********* GESTIÓN DE TRANSACCIÓN ELIMINADA *********

    // 1. Actualizar saldo en la tabla 'billeteras'
    $sql_update = "UPDATE billeteras SET saldo = saldo + :monto WHERE id = :billetera_id";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':monto', $monto);
    $stmt_update->bindParam(':billetera_id', $billetera_id, PDO::PARAM_INT);
    $stmt_update->execute();
    
    // 2. Registrar movimiento en la tabla 'movimientos'
    $sql_log = "INSERT INTO movimientos_billetera (id_billetera, monto, tipo, referencia, fecha_movimiento)
                VALUES (:billetera_id, :monto, 'INGRESO', :referencia, NOW())";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bindParam(':billetera_id', $billetera_id, PDO::PARAM_INT);
    $stmt_log->bindParam(':monto', $monto);
    $stmt_log->bindParam(':referencia', $referencia);
    $stmt_log->execute();
    
    // ********* GESTIÓN DE TRANSACCIÓN ELIMINADA *********
    
    // Ya no hay catch aquí, cualquier error será capturado por el script principal
}
?>