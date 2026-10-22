<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametro_legals', function (Blueprint $table) {
            $table->string('origen', 80)->nullable()->after('porcentaje_sis');
            $table->string('fuente_url')->nullable()->after('origen');
            $table->timestamp('actualizado_en')->nullable()->after('fuente_url');
            $table->foreignId('verificado_por_id')->nullable()->after('actualizado_en')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verificado_en')->nullable()->after('verificado_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('parametro_legals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verificado_por_id');
            $table->dropColumn(['origen', 'fuente_url', 'actualizado_en', 'verificado_en']);
        });
    }
};
