<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\AsistenciaController;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
Route::post('/registro', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (requieren autenticación)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Gestión de Personas (Admin)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        Route::resource('personas', PersonaController::class);
        Route::post('/personas/{persona}/guardar-foto', [PersonaController::class, 'guardarFoto'])->name('personas.guardarFoto');
        Route::get('/personas/{persona}/rostros', [PersonaController::class, 'getFaces'])->name('personas.getFaces');
        Route::get('/dataset/{codigo}/{filename}', [PersonaController::class, 'serveFaceImage'])->name('dataset.serve');
    });

    /*
    |----------------------------------------------------------------------
    | Asistencia (Admin + Docente)
    |----------------------------------------------------------------------
    */
    Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::get('/asistencia/exportar', [AsistenciaController::class, 'exportar'])->name('asistencia.exportar');
    Route::get('/asistencia/en-vivo', [AsistenciaController::class, 'enVivo'])->name('asistencia.envivo');

    // APIs de asistencia
    Route::post('/api/asistencia/registrar', [AsistenciaController::class, 'registrar'])->name('api.asistencia.registrar');
    Route::put('/api/asistencia/{asistencia}/estado', [AsistenciaController::class, 'actualizarEstado'])->name('api.asistencia.estado');
    Route::get('/api/asistencia/hoy', [AsistenciaController::class, 'asistenciasHoy'])->name('api.asistencia.hoy');
    Route::post('/api/personas/buscar', [PersonaController::class, 'buscarPorCodigo'])->name('api.personas.buscar');
    Route::get('/api/personas/{persona}/asistencias', [AsistenciaController::class, 'historialPersona'])->name('api.personas.asistencias');

    // Horario (solo admin)
    Route::middleware('role:admin')->group(function () {
        Route::post('/asistencia/horario', [AsistenciaController::class, 'guardarHorario'])->name('asistencia.guardarHorario');
    });
});
