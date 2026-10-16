<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $fillable = ['trabajador_id', 'turno_id', 'fecha_hora_entrada', 'fecha_hora_salida', 'observaciones'];

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
