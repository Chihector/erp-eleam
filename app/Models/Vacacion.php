<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Vacacion extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'vacaciones';

    protected $fillable = [
        'trabajador_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_habiles',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'dias_habiles' => 'integer',
        ];
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }
}
