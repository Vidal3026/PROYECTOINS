-- =====================================================================
-- TutoLink · Migración: pagos con PayPal, comisiones, cupos y motivo de rechazo
-- Ejecutar UNA sola vez sobre la base `tutolink` (phpMyAdmin > SQL, o
--   mysql -u root tutolink < sql/migracion_pagos_comisiones_cupos.sql)
-- =====================================================================

-- 1. Cupo máximo de estudiantes por tutoría (1 = individual)
ALTER TABLE ofertas_tutorias
    ADD COLUMN cupo_maximo INT NOT NULL DEFAULT 1 AFTER precio_hora;

-- 2. Motivo de rechazo y desglose de comisiones de cada solicitud
ALTER TABLE solicitudes_tutorias
    ADD COLUMN motivo_rechazo VARCHAR(500) NULL,
    ADD COLUMN comision_estudiante DECIMAL(10,2) NULL,
    ADD COLUMN comision_tutor DECIMAL(10,2) NULL,
    ADD COLUMN monto_neto_tutor DECIMAL(10,2) NULL;

-- Acelera la búsqueda de estudiantes inscritos en la misma tutoría grupal
ALTER TABLE solicitudes_tutorias
    ADD INDEX idx_sesion_grupal (id_tutor, id_oferta, fecha, hora_inicio, hora_fin, estado);

-- 3. Recargas con PayPal: estados PENDIENTE / COMPLETADO / FALLIDO e IDs de la orden y la captura
ALTER TABLE recargas_estudiante
    MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    MODIFY fecha_confirmacion DATETIME NULL,
    ADD COLUMN paypal_order_id VARCHAR(64) NULL,
    ADD COLUMN paypal_capture_id VARCHAR(64) NULL,
    ADD UNIQUE KEY uk_recarga_referencia (referencia),
    ADD UNIQUE KEY uk_recarga_paypal_orden (paypal_order_id),
    ADD UNIQUE KEY uk_recarga_paypal_captura (paypal_capture_id);
