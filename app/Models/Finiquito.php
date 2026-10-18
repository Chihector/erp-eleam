<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Finiquito extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'finiquitos';

    protected $fillable = [
        'trabajador_id',
        'fecha_termino',
        'causal',
        'anios_servicio',
        'meses_servicio',
        'sueldo_base_calculo',
        'indemnizacion_anios',
        'indemnizacion_aviso_previo',
        'vacaciones_pendientes',
        'gratificacion_pendiente',
        'total_haberes',
        'otros_descuentos',
        'liquido_pagar',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_termino' => 'date',
            'anios_servicio' => 'integer',
            'meses_servicio' => 'integer',
            'sueldo_base_calculo' => 'integer',
            'indemnizacion_anios' => 'integer',
            'indemnizacion_aviso_previo' => 'integer',
            'vacaciones_pendientes' => 'integer',
            'gratificacion_pendiente' => 'integer',
            'total_haberes' => 'integer',
            'otros_descuentos' => 'integer',
            'liquido_pagar' => 'integer',
        ];
    }

    /**
     * Al guardar, recalcula el líquido a pagar según los descuentos.
     */
    protected static function booted(): void
    {
        static::saving(function (Finiquito $finiquito): void {
            $finiquito->liquido_pagar = max(
                0,
                (int) $finiquito->total_haberes - (int) $finiquito->otros_descuentos,
            );
        });
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }
}
