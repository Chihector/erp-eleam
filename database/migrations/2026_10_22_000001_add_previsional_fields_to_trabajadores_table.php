<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->decimal('afp_tasa', 5, 2)->nullable()->after('afp');
            $table->decimal('salud_plan_uf', 8, 2)->nullable()->after('sistema_salud');
        });
    }

    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropColumn(['afp_tasa', 'salud_plan_uf']);
        });
    }
};
