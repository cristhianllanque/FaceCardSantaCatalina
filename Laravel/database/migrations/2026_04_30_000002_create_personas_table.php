<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('celular')->nullable();
            $table->enum('cargo', ['docente', 'estudiante']);
            $table->string('area')->nullable();
            $table->string('grado')->nullable();
            $table->string('seccion')->nullable();
            $table->enum('turno', ['mañana', 'tarde'])->nullable();
            $table->string('foto_path')->nullable();
            $table->boolean('tiene_embedding')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
