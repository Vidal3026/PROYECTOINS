<?php
require_once __DIR__ . '/Comisiones.php'; // config_app.php + calcular_desglose_pago()

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
 * Procesa el pago completo de una solicitud ACEPTADA en una sola transacción:
 * 1. Bloquea la solicitud y la billetera del estudiante (evita cobros dobles por clics simultáneos).
 * 2. Resta el monto total al estudiante.
 * 3. Acredita al tutor el neto (descontadas ambas comisiones).
 * 4. Registra las dos comisiones de la plataforma (tipo COMISION).
 * 5. Marca la solicitud como CONFIRMADA y guarda el desglose.
 *
 * El monto y el tutor se leen de la base de datos, nunca del formulario.
 *
 * @param PDO $conn Conexión a la base de datos.
 * @param int $id_estudiante ID del estudiante que paga (dueño de la solicitud).
 * @param int $id_solicitud ID de la solicitud de tutoría.
 * @return array ['success' => bool, 'codigo' => string, 'message' => string]
 */
function procesar_transaccion_tutoria($conn, $id_estudiante, $id_solicitud)
{
    // Las billeteras se crean antes de abrir la transacción (obtener_o_crear_billetera_id puede insertar).
    $stmt_tutor = $conn->prepare("SELECT id_tutor FROM solicitudes_tutorias WHERE id = :id AND id_estudiante = :estudiante");
    $stmt_tutor->execute([':id' => $id_solicitud, ':estudiante' => $id_estudiante]);
    $id_tutor = $stmt_tutor->fetchColumn();

    if (!$id_tutor) {
        return ['success' => false, 'codigo' => 'acceso_denegado', 'message' => 'La solicitud no existe o no te pertenece.'];
    }

    $id_billetera_estudiante = obtener_o_crear_billetera_id($conn, $id_estudiante);
    $id_billetera_tutor = obtener_o_crear_billetera_id($conn, (int) $id_tutor);

    if (!$id_billetera_estudiante || !$id_billetera_tutor) {
        return ['success' => false, 'codigo' => 'pago_fallido_db', 'message' => 'Error al obtener las billeteras de los usuarios.'];
    }

    try {
        $conn->beginTransaction();

        // A. Bloquear la solicitud y validar su estado
        $stmt_sol = $conn->prepare("
            SELECT id, id_tutor, estado, precio_total
            FROM solicitudes_tutorias
            WHERE id = :id AND id_estudiante = :estudiante
            FOR UPDATE
        ");
        $stmt_sol->execute([':id' => $id_solicitud, ':estudiante' => $id_estudiante]);
        $solicitud = $stmt_sol->fetch(PDO::FETCH_ASSOC);

        if (!$solicitud) {
            $conn->rollBack();
            return ['success' => false, 'codigo' => 'acceso_denegado', 'message' => 'La solicitud no existe o no te pertenece.'];
        }
        if ($solicitud['estado'] === 'CONFIRMADA') {
            $conn->rollBack();
            return ['success' => false, 'codigo' => 'pago_ya_confirmado', 'message' => 'Esta solicitud ya fue pagada.'];
        }
        if ($solicitud['estado'] !== 'ACEPTADA') {
            $conn->rollBack();
            return ['success' => false, 'codigo' => 'estado_invalido', 'message' => 'La solicitud no está ACEPTADA.'];
        }

        $desglose = calcular_desglose_pago($solicitud['precio_total']);
        if ($desglose['monto_pagado'] <= 0) {
            $conn->rollBack();
            return ['success' => false, 'codigo' => 'monto_invalido', 'message' => 'El monto de la tutoría no es válido.'];
        }

        // B. Bloquear la billetera del estudiante y verificar fondos
        $stmt_saldo = $conn->prepare("SELECT saldo FROM billeteras WHERE id = :id FOR UPDATE");
        $stmt_saldo->execute([':id' => $id_billetera_estudiante]);
        $saldo_estudiante = (float) $stmt_saldo->fetchColumn();

        if ($saldo_estudiante < $desglose['monto_pagado']) {
            $conn->rollBack();
            return [
                'success' => false,
                'codigo' => 'pago_fallido',
                'message' => "Saldo insuficiente. Se requieren $" . number_format($desglose['monto_pagado'], 2)
                    . ", saldo actual $" . number_format($saldo_estudiante, 2)
            ];
        }

        $sql_movimiento = "INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia)
                           VALUES (:id_billetera, :tipo, :monto, :referencia)";
        $stmt_mov = $conn->prepare($sql_movimiento);
        $stmt_ajuste = $conn->prepare("UPDATE billeteras SET saldo = saldo + :monto WHERE id = :id_billetera");

        $referencia = "Solicitud #{$id_solicitud}";
        $pct_est = rtrim(rtrim(number_format($desglose['porcentaje_estudiante'], 2), '0'), '.');
        $pct_tut = rtrim(rtrim(number_format($desglose['porcentaje_tutor'], 2), '0'), '.');

        // C. Egreso del estudiante (monto total)
        $stmt_mov->execute([
            ':id_billetera' => $id_billetera_estudiante,
            ':tipo' => 'EGRESO',
            ':monto' => $desglose['monto_pagado'],
            ':referencia' => "Pago {$referencia} (incluye tarifa de servicio {$pct_est}%)"
        ]);
        $stmt_ajuste->execute([':monto' => -$desglose['monto_pagado'], ':id_billetera' => $id_billetera_estudiante]);

        // D. Ingreso neto al tutor
        $stmt_mov->execute([
            ':id_billetera' => $id_billetera_tutor,
            ':tipo' => 'INGRESO',
            ':monto' => $desglose['neto_tutor'],
            ':referencia' => "Ingreso {$referencia} (neto tras comisiones)"
        ]);
        $stmt_ajuste->execute([':monto' => $desglose['neto_tutor'], ':id_billetera' => $id_billetera_tutor]);

        // E. Comisiones de la plataforma. Se registran en la billetera del tutor solo como
        //    auditoría (no afectan su saldo); los reportes del admin suman los movimientos COMISION.
        $stmt_mov->execute([
            ':id_billetera' => $id_billetera_tutor,
            ':tipo' => 'COMISION',
            ':monto' => $desglose['comision_estudiante'],
            ':referencia' => "Comisión Plataforma (tarifa de servicio {$pct_est}%) {$referencia}"
        ]);
        $stmt_mov->execute([
            ':id_billetera' => $id_billetera_tutor,
            ':tipo' => 'COMISION',
            ':monto' => $desglose['comision_tutor'],
            ':referencia' => "Comisión Plataforma (comisión de tutor {$pct_tut}%) {$referencia}"
        ]);

        // F. Confirmar la solicitud y guardar el desglose
        $stmt_conf = $conn->prepare("
            UPDATE solicitudes_tutorias
            SET estado = 'CONFIRMADA',
                fecha_pago = NOW(),
                comision_estudiante = :comision_estudiante,
                comision_tutor = :comision_tutor,
                monto_neto_tutor = :neto_tutor
            WHERE id = :id
        ");
        $stmt_conf->execute([
            ':comision_estudiante' => $desglose['comision_estudiante'],
            ':comision_tutor' => $desglose['comision_tutor'],
            ':neto_tutor' => $desglose['neto_tutor'],
            ':id' => $id_solicitud
        ]);

        $conn->commit();
        return [
            'success' => true,
            'codigo' => 'pago_confirmado',
            'message' => "Transacción completada. Tutor recibió $" . number_format($desglose['neto_tutor'], 2)
        ];

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error fatal en transacción de tutoría {$id_solicitud}: " . $e->getMessage());
        return ['success' => false, 'codigo' => 'pago_fallido_db', 'message' => 'Error de base de datos durante la transacción.'];
    }
}

/**
 * Cancela una tutoría CONFIRMADA (ya pagada) por decisión del tutor y reembolsa al estudiante.
 * Todo ocurre en una sola transacción:
 * 1. Al estudiante se le devuelve el 100 % de lo que pagó.
 * 2. Al tutor se le descuenta lo que había recibido por esa tutoría (su saldo puede quedar
 *    negativo si ya lo retiró; se compensa con sus próximos ingresos).
 * 3. Las comisiones de la plataforma de esa tutoría se revierten (movimiento COMISION negativo),
 *    porque el servicio no se prestó.
 *
 * @param PDO $conn Conexión a la base de datos.
 * @param int $id_tutor Tutor que cancela (debe ser el dueño de la tutoría).
 * @param int $id_solicitud Tutoría a cancelar.
 * @param string $motivo Explicación que verá el estudiante.
 * @return array ['success' => bool, 'reembolso' => float, 'message' => string]
 */
function cancelar_tutoria_con_reembolso($conn, $id_tutor, $id_solicitud, $motivo)
{
    $stmt = $conn->prepare("SELECT id_estudiante FROM solicitudes_tutorias WHERE id = :id AND id_tutor = :tutor");
    $stmt->execute([':id' => $id_solicitud, ':tutor' => $id_tutor]);
    $id_estudiante = $stmt->fetchColumn();
    if (!$id_estudiante) {
        return ['success' => false, 'reembolso' => 0, 'message' => 'La tutoría no existe o no te pertenece.'];
    }

    // Las billeteras se crean antes de abrir la transacción (obtener_o_crear_billetera_id puede insertar).
    $id_billetera_estudiante = obtener_o_crear_billetera_id($conn, (int) $id_estudiante);
    $id_billetera_tutor = obtener_o_crear_billetera_id($conn, (int) $id_tutor);

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            SELECT precio_total, monto_neto_tutor, fecha_pago
            FROM solicitudes_tutorias
            WHERE id = :id AND id_tutor = :tutor AND estado = 'CONFIRMADA'
            FOR UPDATE
        ");
        $stmt->execute([':id' => $id_solicitud, ':tutor' => $id_tutor]);
        $tutoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tutoria) {
            $conn->rollBack();
            return ['success' => false, 'reembolso' => 0, 'message' => 'La tutoría ya estaba cerrada o cancelada.'];
        }

        $referencia = "Solicitud #{$id_solicitud}";
        $patron = '(^|[^0-9])Solicitud #' . (int) $id_solicitud . '([^0-9]|$)';
        $reembolso = 0.0;

        if ($tutoria['fecha_pago'] !== null) {
            $reembolso = round((float) $tutoria['precio_total'], 2);

            // Lo que recibió el tutor y las comisiones cobradas por esta tutoría
            // (se leen de los movimientos para cubrir también pagos hechos antes del desglose).
            $stmt = $conn->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN tipo = 'INGRESO' AND referencia LIKE 'Ingreso %' THEN monto END), 0) AS neto,
                    COALESCE(SUM(CASE WHEN tipo = 'COMISION' THEN monto END), 0) AS comisiones
                FROM movimientos_billetera
                WHERE id_billetera = :billetera AND referencia REGEXP :patron
            ");
            $stmt->execute([':billetera' => $id_billetera_tutor, ':patron' => $patron]);
            $cobros = $stmt->fetch(PDO::FETCH_ASSOC);
            $neto_tutor = round((float) ($tutoria['monto_neto_tutor'] ?? $cobros['neto']), 2);
            $comisiones = round((float) $cobros['comisiones'], 2);

            $stmt_mov = $conn->prepare("INSERT INTO movimientos_billetera (id_billetera, tipo, monto, referencia)
                                        VALUES (:billetera, :tipo, :monto, :referencia)");
            $stmt_saldo = $conn->prepare("UPDATE billeteras SET saldo = saldo + :monto WHERE id = :billetera");

            // 1. Reembolso completo al estudiante
            $stmt_mov->execute([':billetera' => $id_billetera_estudiante, ':tipo' => 'INGRESO', ':monto' => $reembolso,
                ':referencia' => "Reembolso {$referencia} (cancelada por el tutor)"]);
            $stmt_saldo->execute([':monto' => $reembolso, ':billetera' => $id_billetera_estudiante]);

            // 2. Se descuenta al tutor lo que había recibido
            if ($neto_tutor > 0) {
                $stmt_mov->execute([':billetera' => $id_billetera_tutor, ':tipo' => 'EGRESO', ':monto' => $neto_tutor,
                    ':referencia' => "Reverso {$referencia} (cancelaste la tutoría)"]);
                $stmt_saldo->execute([':monto' => -$neto_tutor, ':billetera' => $id_billetera_tutor]);
            }

            // 3. Se revierten las comisiones de la plataforma
            if ($comisiones > 0) {
                $stmt_mov->execute([':billetera' => $id_billetera_tutor, ':tipo' => 'COMISION', ':monto' => -$comisiones,
                    ':referencia' => "Reverso de comisiones {$referencia} (tutoría cancelada)"]);
            }
        }

        $stmt = $conn->prepare("
            UPDATE solicitudes_tutorias
            SET estado = 'CANCELADA', fecha_cancelacion = NOW(), motivo_rechazo = :motivo
            WHERE id = :id
        ");
        $stmt->execute([':motivo' => $motivo, ':id' => $id_solicitud]);

        $conn->commit();
        return ['success' => true, 'reembolso' => $reembolso, 'message' => 'Tutoría cancelada.'];

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error al cancelar con reembolso la tutoría {$id_solicitud}: " . $e->getMessage());
        return ['success' => false, 'reembolso' => 0, 'message' => 'Error de base de datos al cancelar la tutoría.'];
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

        // 1b. Bloquear la billetera y verificar que el saldo alcance: evita que varios retiros
        //     pendientes aprobados uno tras otro dejen el saldo en negativo.
        $stmt_saldo = $conn->prepare("SELECT saldo FROM billeteras WHERE id = :id FOR UPDATE");
        $stmt_saldo->execute([':id' => $id_billetera]);
        $saldo_tutor = (float) $stmt_saldo->fetchColumn();
        if ($saldo_tutor < $monto_abs) {
            $conn->rollBack();
            return ['success' => false, 'message' => "El tutor ya no tiene saldo suficiente para este retiro (saldo actual $"
                . number_format($saldo_tutor, 2) . "). Recházalo o espera a que tenga saldo."];
        }

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

/**
 * Confirma una recarga pagada con PayPal y acredita el saldo al estudiante.
 * Es idempotente: si la recarga ya estaba COMPLETADA (por ejemplo, llegó primero el webhook
 * y después la captura desde el navegador), no vuelve a acreditar.
 *
 * @param PDO $conn Conexión a la base de datos.
 * @param string $referencia Referencia propia de la recarga (custom_id en PayPal; columna recargas_estudiante.referencia).
 * @param string $id_captura ID de la captura en PayPal.
 * @param float $monto_pagado Monto capturado según PayPal.
 * @return array ['success' => bool, 'ya_procesada' => bool, 'message' => string]
 */
function confirmar_recarga_pagada(PDO $conn, string $referencia, string $id_captura, float $monto_pagado)
{
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            SELECT id, id_estudiante, monto, estado
            FROM recargas_estudiante
            WHERE referencia = :referencia
            FOR UPDATE
        ");
        $stmt->execute([':referencia' => $referencia]);
        $recarga = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recarga) {
            $conn->rollBack();
            return ['success' => false, 'ya_procesada' => false, 'message' => 'La recarga no existe.'];
        }

        if ($recarga['estado'] === 'COMPLETADO') {
            $conn->rollBack();
            return ['success' => true, 'ya_procesada' => true, 'message' => 'La recarga ya había sido acreditada.'];
        }

        if (abs((float) $recarga['monto'] - $monto_pagado) > 0.009) {
            $conn->rollBack();
            error_log("Recarga {$referencia}: monto PayPal ({$monto_pagado}) distinto al registrado ({$recarga['monto']}).");
            return ['success' => false, 'ya_procesada' => false, 'message' => 'El monto pagado no coincide con la recarga.'];
        }

        $stmt_upd = $conn->prepare("
            UPDATE recargas_estudiante
            SET estado = 'COMPLETADO', fecha_confirmacion = NOW(), paypal_capture_id = :captura
            WHERE id = :id
        ");
        $stmt_upd->execute([':captura' => $id_captura, ':id' => $recarga['id']]);

        acreditar_saldo_y_log($conn, (int) $recarga['id_estudiante'], (float) $recarga['monto'], "Recarga PayPal ({$referencia})");

        $conn->commit();
        return ['success' => true, 'ya_procesada' => false, 'message' => 'Recarga acreditada.'];

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error al confirmar recarga PayPal {$referencia}: " . $e->getMessage());
        return ['success' => false, 'ya_procesada' => false, 'message' => 'Error de base de datos al acreditar la recarga.'];
    }
}
?>