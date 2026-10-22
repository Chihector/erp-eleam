<?php

namespace App\Models;

use Database\Factories\LiquidacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Liquidacion extends Model implements Auditable
{
    /** @use HasFactory<LiquidacionFactory> */
    use HasFactory;

    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'trabajador_id',
        'contrato_id',
        'parametro_legal_id',
        'valor_uf_usado',
        'valor_utm_usado',
        'mes',
        'anio',
        'dias_trabajados',
        'origen_dias',
        'sueldo_base',
        'gratificacion',
        'total_imponible',
        'renta_liquida_imponible',
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
        'descuento_anticipo',
        'descuento_sindicato',
        'sis_porcentaje',
        'monto_sis',
        'impuesto_segunda_categoria',
        'total_descuentos_legales',
        'otros_descuentos',
        'total_descuentos',
        'sueldo_liquido',
        'cerrada',
        'cerrada_por_id',
        'cerrada_en',
        'reabierta_por_id',
        'reabierta_en',
        'motivo_reapertura',
    ];

    protected function casts(): array
    {
        return [
            'valor_uf_usado' => 'float',
            'valor_utm_usado' => 'integer',
            'renta_liquida_imponible' => 'integer',
            'impuesto_segunda_categoria' => 'integer',
            'descuento_anticipo' => 'integer',
            'descuento_sindicato' => 'integer',
            'cerrada' => 'boolean',
            'cerrada_en' => 'datetime',
            'reabierta_en' => 'datetime',
        ];
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

    public function parametroLegal()
    {
        return $this->belongsTo(ParametroLegal::class);
    }

    public function cerradaPor()
    {
        return $this->belongsTo(User::class, 'cerrada_por_id');
    }

    public function reabiertaPor()
    {
        return $this->belongsTo(User::class, 'reabierta_por_id');
    }
}
