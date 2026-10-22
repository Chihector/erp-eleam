<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            // Aporte SIS: costo del empleador, no se descuenta del trabajador.
            $table->decimal('sis_porcentaje', 5, 2)->default(0)->after('monto_cesantia');
            $table->integer('monto_sis')->default(0)->after('sis_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            $table->dropColumn(['sis_porcentaje', 'monto_sis']);
        });
    }
};
