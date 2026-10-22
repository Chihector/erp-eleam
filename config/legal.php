<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Actualización de parámetros legales
    |--------------------------------------------------------------------------
    |
    | La UF y la UTM se consultan desde mindicador.cl. Es una fuente externa
    | de referencia: antes de pagar remuneraciones, un responsable debe
    | revisar y marcar el período como verificado en el panel.
    |
    */

    'parametros_url' => env('LEGAL_PARAMETROS_URL', 'https://mindicador.cl/api'),

    /*
    |--------------------------------------------------------------------------
    | Sueldo mínimo mensual (CLP)
    |--------------------------------------------------------------------------
    |
    | Valores de referencia por fecha de vigencia. Se usan solo si el período
    | solicitado cae desde esa fecha. El comando permite reemplazarlo con
    | --sueldo-minimo cuando exista un reajuste publicado.
    |
    */

    'sueldo_minimo' => [
        '2025-07-01' => 529000,
        '2026-01-01' => 539000,
        '2026-05-01' => 553553,
    ],

    /*
    |--------------------------------------------------------------------------
    | Topes legales expresados en UF
    |--------------------------------------------------------------------------
    |
    | Se conservan al crear un período nuevo. No se descargan desde internet:
    | deben revisarse cuando cambie la normativa.
    |
    */

    'topes' => [
        'afp' => 84.3,
        'ips' => 60.0,
        'cesantia' => 126.6,
    ],

    'sis' => 1.49,

    /*
    |--------------------------------------------------------------------------
    | Tasas AFP de referencia (10% obligatorio + comisión)
    |--------------------------------------------------------------------------
    |
    | Solo se usan como respaldo cuando el trabajador no tiene registrada su
    | tasa propia. La comisión cambia y debe validarse en la Superintendencia
    | de Pensiones. La tasa usada queda guardada en cada liquidación.
    |
    */

    'afp_tasas' => [
        'capital' => 11.44,
        'cuprum' => 11.44,
        'habitat' => 11.27,
        'modelo' => 10.58,
        'planvital' => 11.16,
        'provida' => 11.45,
        'uno' => 10.46,
    ],

    /*
    |--------------------------------------------------------------------------
    | Salud
    |--------------------------------------------------------------------------
    |
    | Fonasa cotiza el 7% de la renta imponible. En Isapre se paga el plan
    | contratado, con un mínimo legal equivalente al 7% de la renta imponible.
    |
    */

    'salud_cotizacion_minima' => 7.0,

    /*
    |--------------------------------------------------------------------------
    | Impuesto único de segunda categoría (IUSC)
    |--------------------------------------------------------------------------
    |
    | Tramos mensuales expresados en UTM, según el artículo 43 N° 1 de la Ley
    | sobre Impuesto a la Renta. La tabla vigente se publica cada mes en la
    | Circular del SII; los factores y rebajas en UTM se mantienen y los
    | límites en pesos cambian con el valor de la UTM del período.
    |
    | Fuente de referencia (octubre 2026):
    | https://www.sii.cl/valores_y_fechas/impuesto_2da_categoria/impuesto2026.htm
    |
    | El cálculo es: impuesto = renta_liquida_imponible * factor
    |                            - rebaja_utm * valor_utm
    | y nunca puede ser negativo. La renta líquida imponible es el total
    | imponible menos AFP, salud y seguro de cesantía del trabajador.
    |
    */

    'impuesto_segunda_categoria' => [
        'fuente' => 'https://www.sii.cl/valores_y_fechas/impuesto_2da_categoria/impuesto2026.htm',
        'tramos' => [
            ['hasta_utm' => 13.5, 'factor' => 0.0, 'rebaja_utm' => 0.0],
            ['hasta_utm' => 30.0, 'factor' => 0.04, 'rebaja_utm' => 0.54],
            ['hasta_utm' => 50.0, 'factor' => 0.08, 'rebaja_utm' => 1.74],
            ['hasta_utm' => 70.0, 'factor' => 0.135, 'rebaja_utm' => 4.49],
            ['hasta_utm' => 90.0, 'factor' => 0.23, 'rebaja_utm' => 11.14],
            ['hasta_utm' => 120.0, 'factor' => 0.304, 'rebaja_utm' => 17.80],
            ['hasta_utm' => 310.0, 'factor' => 0.35, 'rebaja_utm' => 23.32],
            ['hasta_utm' => null, 'factor' => 0.40, 'rebaja_utm' => 38.82],
        ],
    ],

];
