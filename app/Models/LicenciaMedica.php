<?php

namespace App\Models;

use App\Services\SubsidioLicenciaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Throwable;

class LicenciaMedica extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'licencias_medicas';

    protected $fillable = [
        'trabajador_id',
        'folio',
        'tipo',
        'entidad_emisora',
        'fecha_inicio',
        'fecha_fin',
        'dias_reposo',
        'dias_pagados',
        'porcentaje_subsidio',
        'monto_subsidio',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'dias_reposo' => 'integer',
            'dias_pagados' => 'integer',
            'porcentaje_subsidio' => 'float',
            'monto_subsidio' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Al guardar: calcula los días de reposo y el subsidio automáticamente.
        static::saving(function (LicenciaMedica $licencia): void {
            if ($licencia->fecha_inicio && $licencia->fecha_fin) {
                $licencia->dias_reposo = max(
                    0,
                    $licencia->fecha_inicio->diffInDays($licencia->fecha_fin) + 1,
                );
            }

            try {
                $calculo = app(SubsidioLicenciaService::class)->calcular($licencia);

                $licencia->dias_pagados = $calculo['dias_pagados'];
                $licencia->porcentaje_subsidio = round($calculo['porcentaje'] * 100, 2);
                $licencia->monto_subsidio = $calculo['subsidio_total'];
            } catch (Throwable) {
                // Sin contrato o parámetros legales: se guardan valores en cero.
            }
        });
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

    /**
     * Subsidio diario (monto total dividido en los días pagados).
     */
    public function getSubsidioDiarioAttribute(): int
    {
        if ($this->dias_pagados <= 0) {
            return 0;
        }

        return (int) round($this->monto_subsidio / $this->dias_pagados);
    }
}
