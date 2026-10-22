<?php

namespace App\Models;

use Database\Factories\LiquidacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    /** @use HasFactory<LiquidacionFactory> */
    use HasFactory;

    protected $fillable = [
        'trabajador_id',
        'contrato_id',
        'mes',
        'anio',
        'dias_trabajados',
        'sueldo_base',
        'gratificacion',
        'total_imponible',
        'monto_colacion',
        'monto_movilizacion',
        'cargas_familiares',
        'total_no_imponible',
        'total_haberes',
        'afp_nombre',
        'afp_porcentaje',
        'monto_afp',
        'salud_nombre',
        'salud_porcentaje',
        'monto_salud',
        'monto_cesantia',
        'sis_porcentaje',
        'monto_sis',
        'total_descuentos_legales',
        'otros_descuentos',
        'total_descuentos',
        'sueldo_liquido',
        'cerrada',
    ];

    protected $casts = [
        'cerrada' => 'boolean',
    ];

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }
}
