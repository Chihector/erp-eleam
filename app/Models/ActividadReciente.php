<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de solo lectura para la actividad reciente del dashboard.
 *
 * No representa una tabla real: se alimenta con una subconsulta UNION
 * (liquidaciones, licencias, finiquitos, vacaciones, gastos e ingresos).
 */
class ActividadReciente extends Model
{
    protected $table = 'actividad';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
