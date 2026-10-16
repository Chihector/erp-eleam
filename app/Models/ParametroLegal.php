<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametroLegal extends Model
{
    /** @use HasFactory<\Database\Factories\ParametroLegalFactory> */
    use HasFactory;

    protected $fillable = [
        'mes',
        'anio',
        'valor_uf',
        'valor_utm',
        'sueldo_minimo',
        'tope_imponible_afp',
        'tope_imponible_ips',
        'tope_imponible_cesantia',
        'porcentaje_sis',
    ];
}
