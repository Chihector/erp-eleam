<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            // Reproducibilidad completa: se guarda una copia de todos los
            // parámetros legales que afectan el cálculo, no solo UF y UTM.
            $table->integer('sueldo_minimo_usado')->nullable()->after('valor_utm_usado');
            $table->decimal('tope_afp_usado', 8, 2)->nullable()->after('sueldo_minimo_usado');
            $table->decimal('tope_ips_usado', 8, 2)->nullable()->after('tope_afp_usado');
            $table->decimal('tope_cesantia_usado', 8, 2)->nullable()->after('tope_ips_usado');
        });
    }

    public function down(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            $table->dropColumn([
                'sueldo_minimo_usado',
                'tope_afp_usado',
                'tope_ips_usado',
                'tope_cesantia_usado',
            ]);
        });
    }
};
