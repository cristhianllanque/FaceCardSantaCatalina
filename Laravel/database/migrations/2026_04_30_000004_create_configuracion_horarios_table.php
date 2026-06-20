<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_horarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->default('Principal');
            $table->string('hora_entrada')->default('08:00');
            $table->string('hora_tardanza')->default('08:15');
            $table->string('hora_falta')->default('08:30');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_horarios');
    }
};
