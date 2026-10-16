<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Trabajador extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'trabajadores';

    protected $fillable = [
        'rut',
        'nombres',
        'apellidos',
        'cargo',
        'afp',
        'sistema_salud',
        'fecha_ingreso',
        'color_calendario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
        ];
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    public function turnos()
    {
        return $this->hasMany(Turno::class);
    }

    public function vacaciones()
    {
        return $this->hasMany(Vacacion::class);
    }

    public function getSaldoVacacionesAttribute(): float
    {
        if (!$this->fecha_ingreso) {
            return 0;
        }

        // Calcula los meses trabajados desde la fecha de ingreso
        $mesesTrabajados = $this->fecha_ingreso->diffInMonths(now());
        
        // En Chile se ganan 1.25 días hábiles por mes trabajado (15 días al año)
        $diasGanados = $mesesTrabajados * 1.25;

        // Sumar todos los días de vacaciones aprobados
        $diasUsados = $this->vacaciones()->where('estado', 'Aprobada')->sum('dias_habiles');

        return $diasGanados - $diasUsados;
    }
}
