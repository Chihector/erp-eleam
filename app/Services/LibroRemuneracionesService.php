<?php

namespace App\Services;

use App\Models\Liquidacion;
use Illuminate\Support\Collection;

/**
 * Libro de Remuneraciones Digital (Dirección del Trabajo) — versión
 * simplificada en CSV.
 *
 * Genera un archivo con una fila por trabajador y todas las sumas y
 * descuentos del período, listo para abrir en Excel (incluye BOM UTF-8
 * y separador punto y coma, compatible con la configuración regional
 * en español de Excel).
 *
 * Montos en pesos chilenos enteros, sin separador de miles.
 */
class LibroRemuneracionesService
{
    /** Encabezados del archivo, en el orden de las columnas. */
    public const COLUMNAS = [
        'RUT Trabajador',
        'Nombres',
        'Apellidos',
        'Cargo',
        'Tipo de Contrato',
        'Fecha de Ingreso',
        'Días Trabajados',
        'Sueldo Base',
        'Gratificación',
        'Total Imponible',
        'Movilización',
        'Colación',
        'Cargas Familiares',
        'Total No Imponible',
        'Total Haberes',
        'AFP',
        'Salud',
        'Seguro de Cesantía',
        'Total Descuentos Legales',
        'Otros Descuentos',
        'Total Descuentos',
        'Sueldo Líquido',
    ];

    /**
     * Genera el contenido CSV del libro de remuneraciones del período.
     */
    public function generarCsv(int $mes, int $anio): string
    {
        $liquidaciones = $this->liquidacionesDelPeriodo($mes, $anio);

        $lineas = [implode(';', array_map($this->texto(...), self::COLUMNAS))];

        foreach ($liquidaciones as $liquidacion) {
            $trabajador = $liquidacion->trabajador;
            $contrato = $trabajador->contratos->sortByDesc('fecha_inicio')->first();

            $lineas[] = implode(';', [
                $this->texto($trabajador->rut),
                $this->texto($trabajador->nombres),
                $this->texto($trabajador->apellidos),
                $this->texto($trabajador->cargo),
                $this->texto($contrato?->tipo_contrato),
                $this->texto($trabajador->fecha_ingreso?->format('d/m/Y')),
                (int) $liquidacion->dias_trabajados,
                $this->monto($liquidacion->sueldo_base),
                $this->monto($liquidacion->gratificacion),
                $this->monto($liquidacion->total_imponible),
                $this->monto($liquidacion->monto_movilizacion),
                $this->monto($liquidacion->monto_colacion),
                $this->monto($liquidacion->cargas_familiares),
                $this->monto($liquidacion->total_no_imponible),
                $this->monto($liquidacion->total_haberes),
                $this->monto($liquidacion->monto_afp),
                $this->monto($liquidacion->monto_salud),
                $this->monto($liquidacion->monto_cesantia),
                $this->monto($liquidacion->total_descuentos_legales),
                $this->monto($liquidacion->otros_descuentos),
                $this->monto($liquidacion->total_descuentos),
                $this->monto($liquidacion->sueldo_liquido),
            ]);
        }

        // BOM UTF-8 para que Excel reconozca los acentos; saltos CRLF estándar.
        return "\xEF\xBB\xBF".implode("\r\n", $lineas)."\r\n";
    }

    /**
     * Cantidad de liquidaciones incluidas en el período.
     */
    public function cantidadDelPeriodo(int $mes, int $anio): int
    {
        return $this->liquidacionesDelPeriodo($mes, $anio)->count();
    }

    /**
     * Liquidaciones del período, ordenadas por apellido del trabajador.
     *
     * @return Collection<int, Liquidacion>
     */
    private function liquidacionesDelPeriodo(int $mes, int $anio): Collection
    {
        return Liquidacion::query()
            ->with(['trabajador.contratos'])
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get()
            ->sortBy(fn (Liquidacion $liquidacion) => $liquidacion->trabajador->apellidos.' '.$liquidacion->trabajador->nombres)
            ->values();
    }

    /**
     * Formatea un monto como peso entero sin separadores (para CSV).
     */
    private function monto(mixed $valor): string
    {
        return (string) (int) round((float) $valor);
    }

    /**
     * Escapa un campo de texto para CSV y neutraliza fórmulas de Excel.
     *
     * Un nombre o cargo que empiece con =, +, - o @ podría ejecutarse como
     * fórmula al abrir el archivo. Se antepone un apóstrofo y, si el valor
     * contiene el separador, comillas o saltos de línea, se entrecomilla.
     */
    private function texto(?string $valor): string
    {
        $valor = (string) $valor;

        if ($valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $valor = "'".$valor;
        }

        if (preg_match('/[;"\r\n]/', $valor) === 1) {
            $valor = '"'.str_replace('"', '""', $valor).'"';
        }

        return $valor;
    }
}
