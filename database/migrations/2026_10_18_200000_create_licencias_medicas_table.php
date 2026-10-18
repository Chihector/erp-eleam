<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licencias médicas del personal (Fonasa / Isapre / Mutual).
 *
 * El subsidio se calcula automáticamente al guardar (SubsidioLicenciaService):
 *  - Común: 75% del sueldo diario, tope 60 UF y 3 días de carencia.
 *  - Maternal / Accidente del Trabajo: 100% del sueldo, sin carencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencias_medicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();

            $table->string('folio')->nullable();          // Número de folio de la licencia
            $table->string('tipo')->default('Común');      // Común, Maternal, Accidente del Trabajo, Enfermedad Profesional
            $table->string('entidad_emisora')->default('Fonasa'); // Fonasa, Isapre, Mutual de Seguridad

            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias_reposo');                // Días corridos de reposo (se calcula solo)

            // Subsidio calculado automáticamente
            $table->integer('dias_pagados')->default(0);
            $table->decimal('porcentaje_subsidio', 5, 2)->default(0);
            $table->integer('monto_subsidio')->default(0);

            $table->string('estado')->default('Pendiente'); // Pendiente, Validada, Rechazada
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['trabajador_id', 'estado'], 'licencias_trabajador_estado_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias_medicas');
    }
};
