<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            // Reproducibilidad: parámetros exactos usados al calcular.
            $table->foreignId('parametro_legal_id')->nullable()->after('anio')
                ->constrained('parametro_legals')->nullOnDelete();
            $table->decimal('valor_uf_usado', 12, 2)->nullable()->after('parametro_legal_id');
            $table->integer('valor_utm_usado')->nullable()->after('valor_uf_usado');

            // Origen de los días trabajados: manual, licencias o asistencia.
            $table->string('origen_dias', 20)->default('manual')->after('dias_trabajados');

            // Impuesto único de segunda categoría.
            $table->integer('renta_liquida_imponible')->default(0)->after('total_imponible');
            $table->integer('impuesto_segunda_categoria')->default(0)->after('total_descuentos_legales');

            // Detalle de otros descuentos.
            $table->integer('descuento_anticipo')->default(0)->after('monto_cesantia');
            $table->integer('descuento_sindicato')->default(0)->after('descuento_anticipo');

            // Cierre y reapertura con trazabilidad.
            $table->foreignId('cerrada_por_id')->nullable()->after('cerrada')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('cerrada_en')->nullable()->after('cerrada_por_id');
            $table->foreignId('reabierta_por_id')->nullable()->after('cerrada_en')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reabierta_en')->nullable()->after('reabierta_por_id');
            $table->text('motivo_reapertura')->nullable()->after('reabierta_en');
        });
    }

    public function down(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parametro_legal_id');
            $table->dropConstrainedForeignId('cerrada_por_id');
            $table->dropConstrainedForeignId('reabierta_por_id');
            $table->dropColumn([
                'valor_uf_usado',
                'valor_utm_usado',
                'origen_dias',
                'renta_liquida_imponible',
                'impuesto_segunda_categoria',
                'descuento_anticipo',
                'descuento_sindicato',
                'cerrada_en',
                'reabierta_en',
                'motivo_reapertura',
            ]);
        });
    }
};
