<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;
use App\Models\Asistencia;
use App\Models\ConfiguracionHorario;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $totalPersonas = Persona::count();
        $totalEstudiantes = Persona::where('cargo', 'estudiante')->count();
        $totalDocentes = Persona::where('cargo', 'docente')->count();
        $totalConEmbedding = Persona::where('tiene_embedding', true)->count();
        $asistenciasHoy = Asistencia::whereDate('fecha', today())->count();
        $horario = ConfiguracionHorario::activo();

        // Si es docente, filtrar por su área
        if ($user->role === 'docente') {
            $totalPersonas = Persona::where('area', $user->area)->count();
            $totalEstudiantes = Persona::where('cargo', 'estudiante')->where('area', $user->area)->count();
            $asistenciasHoy = Asistencia::whereDate('fecha', today())
                ->whereHas('persona', fn($q) => $q->where('area', $user->area))
                ->count();
        }

        $ultimasAsistencias = Asistencia::with('persona')
            ->when($user->role === 'docente', fn($q) =>
                $q->whereHas('persona', fn($q2) => $q2->where('area', $user->area))
            )
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalPersonas',
            'totalEstudiantes',
            'totalDocentes',
            'totalConEmbedding',
            'asistenciasHoy',
            'horario',
            'ultimasAsistencias'
        ));
    }
}
