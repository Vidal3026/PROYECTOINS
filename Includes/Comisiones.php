<?php
/**
 * Comisiones de la plataforma: cálculo y textos que ven estudiantes y tutores.
 * Los porcentajes se configuran en Includes/config_app.php (COMISION_ESTUDIANTE y COMISION_TUTOR).
 */
require_once __DIR__ . '/config_app.php';

if (!function_exists('calcular_desglose_pago')) {
    /**
     *
     * @param float $monto_pagado Monto total que paga el estudiante.
     * @return array Montos redondeados a 2 decimales.
     */
    function calcular_desglose_pago($monto_pagado)
    {
        $monto_pagado = round(abs((float) $monto_pagado), 2);

        $comision_estudiante = round($monto_pagado * COMISION_ESTUDIANTE, 2);
        $base_tutor = round($monto_pagado - $comision_estudiante, 2);
        $comision_tutor = round($base_tutor * COMISION_TUTOR, 2);
        $neto_tutor = round($base_tutor - $comision_tutor, 2);

        return [
            'monto_pagado' => $monto_pagado,
            'porcentaje_estudiante' => COMISION_ESTUDIANTE * 100,
            'comision_estudiante' => $comision_estudiante,
            'base_tutor' => $base_tutor,
            'porcentaje_tutor' => COMISION_TUTOR * 100,
            'comision_tutor' => $comision_tutor,
            'neto_tutor' => $neto_tutor,
            'ganancia_plataforma' => round($comision_estudiante + $comision_tutor, 2),
        ];
    }

    /** Porcentaje legible: 0.05 → "5", 0.075 → "7.5". */
    function porcentaje_comision($fraccion)
    {
        return rtrim(rtrim(number_format($fraccion * 100, 2, '.', ''), '0'), '.');
    }

    /** Porcentaje total que la plataforma retiene del pago del estudiante (5 % + 10 % de 95 % = 14.5 %). */
    function porcentaje_comision_total()
    {
        return porcentaje_comision(1 - (1 - COMISION_ESTUDIANTE) * (1 - COMISION_TUTOR));
    }

    /**
     * Tarjeta informativa con las comisiones, adaptada al rol que la ve.
     * Cada rol ve solo lo que le corresponde:
     * - estudiante: la tarifa de servicio incluida en lo que paga (nada sobre lo que recibe el tutor).
     * - tutor: los descuentos que se aplican al precio de sus tutorías y lo que recibe.
     * @param string $rol 'estudiante' o 'tutor'
     * @param float $monto_ejemplo Monto usado en el ejemplo numérico.
     */
    function render_info_comisiones($rol, $monto_ejemplo = 20.00)
    {
        $d = calcular_desglose_pago($monto_ejemplo);
        $pe = porcentaje_comision(COMISION_ESTUDIANTE);
        $f = function ($n) { return '$' . number_format($n, 2); };
        ?>
        <div class="card shadow-sm mb-4 border-info">
            <div class="card-header bg-info bg-opacity-10">
                <i class="fas fa-percent me-1"></i>
                <strong><?= $rol === 'tutor' ? 'Comisiones sobre tus tutorías' : 'Tarifa de servicio TutoLink' ?></strong>
            </div>
            <div class="card-body">
                <?php if ($rol === 'tutor'): ?>
                    <?php $pt = porcentaje_comision(COMISION_TUTOR); ?>
                    <p class="mb-2">Por cada tutoría pagada, la plataforma descuenta del precio:</p>
                    <ul class="mb-3">
                        <li><strong>Tarifa de servicio (<?= $pe ?>%)</strong> sobre el precio de la tutoría.</li>
                        <li><strong>Comisión de tutor (<?= $pt ?>%)</strong> sobre lo que queda.</li>
                    </ul>
                    <p class="small text-muted mb-2">En total recibes el <?= porcentaje_comision((1 - COMISION_ESTUDIANTE) * (1 - COMISION_TUTOR)) ?>% del precio. Ejemplo:</p>
                    <table class="table table-sm mb-0" style="max-width: 28rem;">
                        <tbody>
                            <tr><td>Precio de tu tutoría</td><td class="text-end"><?= $f($d['monto_pagado']) ?></td></tr>
                            <tr class="text-danger"><td>− Tarifa de servicio (<?= $pe ?>%)</td><td class="text-end"><?= $f($d['comision_estudiante']) ?></td></tr>
                            <tr class="text-danger"><td>− Comisión de tutor (<?= $pt ?>% de <?= $f($d['base_tutor']) ?>)</td><td class="text-end"><?= $f($d['comision_tutor']) ?></td></tr>
                            <tr class="fw-bold text-success"><td>Recibes</td><td class="text-end"><?= $f($d['neto_tutor']) ?></td></tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="mb-2">El precio de cada tutoría <strong>ya incluye</strong> una tarifa de servicio del <?= $pe ?>%
                        que mantiene la plataforma funcionando. No se cobra nada adicional.</p>
                    <table class="table table-sm mb-0" style="max-width: 28rem;">
                        <tbody>
                            <tr><td>Precio de la tutoría</td><td class="text-end"><?= $f($d['monto_pagado']) ?></td></tr>
                            <tr class="text-muted"><td>Incluye tarifa de servicio (<?= $pe ?>%)</td><td class="text-end"><?= $f($d['comision_estudiante']) ?></td></tr>
                            <tr class="fw-bold"><td>Total que pagas</td><td class="text-end"><?= $f($d['monto_pagado']) ?></td></tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Expone a JavaScript el cálculo de comisiones con la misma fórmula del servidor
     * (solo informativo: los montos reales siempre los calcula el servidor).
     * - 'estudiante': desgloseTutolink(20) → {pagado: 20, comisionEstudiante: 1}
     * - 'tutor':      desgloseTutolink(20) → {..., comisionTutor: 1.9, netoTutor: 17.1}
     * La página del estudiante no recibe el porcentaje del tutor.
     */
    function script_comisiones($rol)
    {
        ?>
        <script>
            function desgloseTutolink(monto) {
                const r = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
                const pagado = r(Math.abs(Number(monto) || 0));
                const comisionEstudiante = r(pagado * <?= (float) COMISION_ESTUDIANTE ?>);
                <?php if ($rol === 'tutor'): ?>
                const baseTutor = r(pagado - comisionEstudiante);
                const comisionTutor = r(baseTutor * <?= (float) COMISION_TUTOR ?>);
                return { pagado, comisionEstudiante, baseTutor, comisionTutor, netoTutor: r(baseTutor - comisionTutor) };
                <?php else: ?>
                return { pagado, comisionEstudiante };
                <?php endif; ?>
            }
        </script>
        <?php
    }
}
