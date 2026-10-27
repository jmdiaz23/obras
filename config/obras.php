<?php

return [
    'unidades' => [
        'UND' => 'Unidad (UND)',
        'M2' => 'Metro cuadrado (M2)',
        'M3' => 'Metro cúbico (M3)',
        'ML' => 'Metro lineal (ML)',
        'KG' => 'Kilogramo (KG)',
        'GAL' => 'Galón (GAL)',
        'PLIEGO' => 'Pliego (PLIEGO)',
        'GL' => 'Global (GL)',
        'BL' => 'Bulto (BL)',
        'DIA' => 'Día (DÍA)',
        'HORA' => 'Hora (HORA)',
        'MES' => 'Mes (MES)',
    ],

    /*
     * Unidades con las que se puede alquilar un equipo dentro de un APU.
     * En hora y día la tarifa sale del catálogo; en las demás el usuario
     * define el valor unitario (p. ej. formaleta prorrateada por m²).
     */
    'unidades_equipo' => [
        'hr' => 'Hora (hr)',
        'dia' => 'Día (día)',
        'm2' => 'Metro cuadrado (m²)',
        'm3' => 'Metro cúbico (m³)',
        'und' => 'Unidad (und)',
    ],
];
