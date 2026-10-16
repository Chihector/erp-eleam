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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('categoria'); // Ej: Luz, Agua, Comida, Insumos Médicos, etc.
            $table->text('descripcion')->nullable();
            $table->integer('monto'); // En pesos chilenos sin decimales
            $table->string('comprobante')->nullable(); // Ruta del archivo/foto adjunto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
