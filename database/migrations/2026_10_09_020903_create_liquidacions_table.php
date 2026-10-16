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
        Schema::create('liquidacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->integer('mes');
            $table->integer('anio');
            $table->integer('dias_trabajados')->default(30);
            
            // Haberes Imponibles
            $table->integer('sueldo_base');
            $table->integer('gratificacion');
            $table->integer('total_imponible');
            
            // Haberes No Imponibles
            $table->integer('monto_colacion')->default(0);
            $table->integer('monto_movilizacion')->default(0);
            $table->integer('cargas_familiares')->default(0);
            $table->integer('total_no_imponible')->default(0);
            
            // Total Haberes
            $table->integer('total_haberes');
            
            // Descuentos Legales
            $table->string('afp_nombre');
            $table->decimal('afp_porcentaje', 5, 2);
            $table->integer('monto_afp');
            $table->string('salud_nombre');
            $table->decimal('salud_porcentaje', 5, 2);
            $table->integer('monto_salud');
            $table->integer('monto_cesantia')->default(0);
            $table->integer('total_descuentos_legales');
            
            // Otros Descuentos
            $table->integer('otros_descuentos')->default(0);
            
            // Total Descuentos
            $table->integer('total_descuentos');
            
            // Líquido
            $table->integer('sueldo_liquido');
            
            // Estado para congelar/cerrar
            $table->boolean('cerrada')->default(false);
            
            $table->timestamps();
            
            $table->unique(['trabajador_id', 'mes', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacions');
    }
};
