<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las liquidaciones son registros laborales: eliminarlas debe ser
     * reversible. El soft delete conserva el cálculo, la auditoría y permite
     * restaurarlas sin recurrir a un respaldo.
     */
    public function up(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('liquidacions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
