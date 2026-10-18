<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finiquitos de término de relación laboral.
 *
 * Los montos se calculan automáticamente con FiniquitoService según la
 * normativa chilena (versión simplificada):
 *  - Indemnización por años de servicio: 30 días por año, tope 11 años,
 *    sueldo tope 90 UF (solo causal Art. 161 Necesidades de la Empresa).
 *  - Indemnización sustitutiva del aviso previo: 1 sueldo mensual.
 *  - Vacaciones pendientes y gratificación proporcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finiquitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();

            $table->date('fecha_termino');
            $table->string('causal');

            // Antigüedad
            $table->integer('anios_servicio')->default(0);
            $table->integer('meses_servicio')->default(0);
            $table->integer('sueldo_base_calculo')->default(0);

            // Haberes del finiquito
            $table->integer('indemnizacion_anios')->default(0);
            $table->integer('indemnizacion_aviso_previo')->default(0);
            $table->integer('vacaciones_pendientes')->default(0);
            $table->integer('gratificacion_pendiente')->default(0);
            $table->integer('total_haberes')->default(0);

            // Descuentos y líquido
            $table->integer('otros_descuentos')->default(0);
            $table->integer('liquido_pagar')->default(0);

            $table->string('estado')->default('Borrador'); // Borrador, Firmado
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['trabajador_id', 'estado'], 'finiquitos_trabajador_estado_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finiquitos');
    }
};
