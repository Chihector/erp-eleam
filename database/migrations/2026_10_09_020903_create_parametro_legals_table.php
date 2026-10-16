<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parametro_legals', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('anio');
            $table->decimal('valor_uf', 10, 2);
            $table->integer('valor_utm');
            $table->integer('sueldo_minimo');
            $table->decimal('tope_imponible_afp', 8, 2);
            $table->decimal('tope_imponible_ips', 8, 2);
            $table->decimal('tope_imponible_cesantia', 8, 2);
            $table->decimal('porcentaje_sis', 5, 2)->default(1.54);
            $table->timestamps();
            
            $table->unique(['mes', 'anio']); // Only one parameter set per month/year
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametro_legals');
    }
};
