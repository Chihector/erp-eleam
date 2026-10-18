<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de rendimiento para las consultas más frecuentes del sistema:
 *
 *  - Turnos: validación de choques horarios y calendario mensual.
 *  - Asistencias: listado y filtros por fecha.
 *  - Liquidaciones: generación/consulta por período (mes, año).
 *  - Vacaciones: cálculo del saldo legal por trabajador.
 *  - Gastos / Ingresos: totales mensuales del dashboard financiero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Consulta anti-choques: where trabajador_id = ? and fecha_inicio/fin solapan
            $table->index(
                ['trabajador_id', 'fecha_inicio', 'fecha_fin'],
                'turnos_trabajador_fechas_index'
            );

            // Calendario del dashboard: turnos dentro de un rango de fechas
            $table->index('fecha_inicio', 'turnos_fecha_inicio_index');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            // Reloj control: marcas por rango de fecha
            $table->index('fecha_hora_entrada', 'asistencias_entrada_index');
        });

        Schema::table('liquidacions', function (Blueprint $table) {
            // Listado y generación masiva por período
            $table->index(['mes', 'anio'], 'liquidacions_periodo_index');
        });

        Schema::table('vacaciones', function (Blueprint $table) {
            // Saldo legal: where trabajador_id = ? and estado = 'Aprobada'
            $table->index(['trabajador_id', 'estado'], 'vacaciones_trabajador_estado_index');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->index('fecha', 'gastos_fecha_index');
        });

        Schema::table('ingresos', function (Blueprint $table) {
            $table->index('fecha', 'ingresos_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropIndex('turnos_trabajador_fechas_index');
            $table->dropIndex('turnos_fecha_inicio_index');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('asistencias_entrada_index');
        });

        Schema::table('liquidacions', function (Blueprint $table) {
            $table->dropIndex('liquidacions_periodo_index');
        });

        Schema::table('vacaciones', function (Blueprint $table) {
            $table->dropIndex('vacaciones_trabajador_estado_index');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropIndex('gastos_fecha_index');
        });

        Schema::table('ingresos', function (Blueprint $table) {
            $table->dropIndex('ingresos_fecha_index');
        });
    }
};
