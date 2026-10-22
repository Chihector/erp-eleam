<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Contracts\Auditable;

class Trabajador extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'trabajadores';

    protected $fillable = [
        'rut',
        'nombres',
        'apellidos',
        'cargo',
        'afp',
        'afp_tasa',
        'sistema_salud',
        'salud_plan_uf',
        'fecha_ingreso',
        'color_calendario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'afp_tasa' => 'float',
            'salud_plan_uf' => 'float',
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

    public function licenciasMedicas()
    {
        return $this->hasMany(LicenciaMedica::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function finiquitos()
    {
        return $this->hasMany(Finiquito::class);
    }

    public function getSaldoVacacionesAttribute(): float
    {
        return $this->diasVacacionesPendientes();
    }

    /**
     * Días de vacaciones pendientes (devengados menos los ya usados).
     *
     * Si la relación `vacaciones` está precargada (with('vacaciones')) no
     * genera consultas adicionales, lo que permite filtrar listados grandes
     * sin caer en N+1.
     */
    public function diasVacacionesPendientes(): float
    {
        if (! $this->fecha_ingreso) {
            return 0.0;
        }

        // En Chile se ganan 1.25 días hábiles por mes trabajado (15 días al año).
        $mesesTrabajados = $this->fecha_ingreso->diffInMonths(now());
        $diasGanados = $mesesTrabajados * 1.25;

        $diasUsados = $this->relationLoaded('vacaciones')
            ? $this->vacaciones->where('estado', 'Aprobada')->sum('dias_habiles')
            : $this->vacaciones()->where('estado', 'Aprobada')->sum('dias_habiles');

        return $diasGanados - $diasUsados;
    }

    /**
     * Trabajadores con más de $dias de vacaciones pendientes.
     *
     * El saldo es calculado (accessor), no una columna, por lo que se filtra
     * en PHP con la relación precargada para evitar N+1.
     *
     * @return Collection<int, self>
     */
    public static function conVacacionesPendientesMayorA(float $dias, bool $soloConMasDeUnAnio = true): Collection
    {
        return static::query()
            ->with('vacaciones')
            ->when($soloConMasDeUnAnio, fn ($query) => $query->where('fecha_ingreso', '<=', now()->subYear()->toDateString()))
            ->get()
            ->filter(fn (self $trabajador): bool => $trabajador->diasVacacionesPendientes() > $dias)
            ->sortByDesc(fn (self $trabajador): float => $trabajador->diasVacacionesPendientes())
            ->values();
    }
}
