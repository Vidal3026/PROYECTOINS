-- =====================================================================
-- TutoLink · Solo para bases que YA ejecutaron la versión anterior de la
-- migración (la que tenía columnas de Wompi). Cambia esas columnas por las de PayPal.
-- Si tu base nunca tuvo columnas wompi_*, NO ejecutes este archivo:
-- usa únicamente sql/migracion_pagos_comisiones_cupos.sql.
-- =====================================================================

ALTER TABLE recargas_estudiante
    DROP INDEX uk_recarga_wompi_transaccion,
    DROP COLUMN wompi_id_enlace,
    DROP COLUMN wompi_id_transaccion,
    ADD COLUMN paypal_order_id VARCHAR(64) NULL,
    ADD COLUMN paypal_capture_id VARCHAR(64) NULL,
    ADD UNIQUE KEY uk_recarga_paypal_orden (paypal_order_id),
    ADD UNIQUE KEY uk_recarga_paypal_captura (paypal_capture_id);
