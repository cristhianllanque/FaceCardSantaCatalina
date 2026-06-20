<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('hora_ingreso');
            $table->enum('estado', ['puntual', 'tardanza', 'falta'])->default('puntual');
            $table->float('confianza')->default(0);
            $table->string('foto_captura')->nullable();
            $table->string('sesion_id')->nullable();
            $table->timestamps();

            $table->unique(['persona_id', 'fecha', 'sesion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
