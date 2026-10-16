<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Contrato extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'trabajador_id',
        'tipo_contrato',
        'sueldo_base',
        'horas_semanales',
        'jornada_excepcional',
        'fecha_inicio',
        'fecha_termino',
    ];

    protected function casts(): array
    {
        return [
            'sueldo_base' => 'integer',
            'horas_semanales' => 'integer',
            'jornada_excepcional' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_termino' => 'date',
        ];
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }
}
