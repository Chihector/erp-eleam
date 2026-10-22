<?php

namespace App\Models;

use Database\Factories\ParametroLegalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroLegal extends Model
{
    /** @use HasFactory<ParametroLegalFactory> */
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
        'origen',
        'fuente_url',
        'actualizado_en',
        'verificado_por_id',
        'verificado_en',
    ];

    protected function casts(): array
    {
        return [
            'valor_uf' => 'float',
            'valor_utm' => 'integer',
            'sueldo_minimo' => 'integer',
            'tope_imponible_afp' => 'float',
            'tope_imponible_ips' => 'float',
            'tope_imponible_cesantia' => 'float',
            'porcentaje_sis' => 'float',
            'actualizado_en' => 'datetime',
            'verificado_en' => 'datetime',
        ];
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por_id');
    }

    public function estaVerificado(): bool
    {
        return $this->verificado_en !== null;
    }
}
