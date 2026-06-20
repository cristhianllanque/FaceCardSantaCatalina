<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ConfiguracionHorario;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin por defecto
        User::firstOrCreate(
            ['email' => 'admin@facecard.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'area' => null,
            ]
        );

        // Docente de prueba
        User::firstOrCreate(
            ['email' => 'docente@facecard.com'],
            [
                'name' => 'Docente Demo',
                'password' => bcrypt('docente123'),
                'role' => 'docente',
                'area' => 'Ingeniería de Sistemas',
            ]
        );

        // Configuración de horarios por defecto
        ConfiguracionHorario::firstOrCreate(
            ['nombre' => 'Principal'],
            [
                'hora_entrada' => '08:00',
                'hora_tardanza' => '08:15',
                'hora_falta' => '08:30',
                'activo' => true,
            ]
        );
    }
}
