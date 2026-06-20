<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asistencia;
use App\Models\Persona;
use App\Models\ConfiguracionHorario;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $fecha = $request->input('fecha', today()->toDateString());
        $horario = ConfiguracionHorario::activo();

        $query = Asistencia::with('persona')->whereDate('fecha', $fecha);

        // Filtros de persona en la asistencia
        if ($request->filled('cargo')) {
            $query->whereHas('persona', fn($q) => $q->where('cargo', $request->cargo));
        }
        if ($request->filled('grado')) {
            $query->whereHas('persona', fn($q) => $q->where('grado', $request->grado));
        }
        if ($request->filled('seccion')) {
            $query->whereHas('persona', fn($q) => $q->where('seccion', $request->seccion));
        }
        if ($request->filled('turno')) {
            $query->whereHas('persona', fn($q) => $q->where('turno', $request->turno));
        }

        if ($user->role === 'docente') {
            $query->whereHas('persona', fn($q) => $q->where('area', $user->area));
        }

        $asistencias = $query->orderByDesc('created_at')->get();

        return view('asistencia.index', compact('asistencias', 'fecha', 'horario'));
    }

    public function enVivo()
    {
        $horario = ConfiguracionHorario::where('nombre', 'mañana')->first() ?? ConfiguracionHorario::activo();
        $fecha = today()->toDateString();
        $sesionId = 'SES-' . now()->format('YmdHis');

        // Asistencias de hoy
        $asistenciasHoy = Asistencia::with('persona')
            ->whereDate('fecha', $fecha)
            ->orderByDesc('created_at')
            ->get();

        return view('asistencia.envivo', compact('horario', 'fecha', 'sesionId', 'asistenciasHoy'));
    }

    /**
     * Registrar asistencia desde reconocimiento facial
     */
    public function registrar(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|exists:personas,id',
            'confianza' => 'required|numeric|min:0|max:1',
            'foto_captura' => 'nullable|string',
            'foto_completa' => 'nullable|string',
            'sesion_id' => 'nullable|string',
        ]);

        $persona = Persona::findOrFail($request->persona_id);
        $fecha = today()->toDateString();
        $horaActual = now()->format('H:i:s');

        // Verificar duplicados: no registrar la misma persona en el mismo día
        $existente = Asistencia::where('persona_id', $persona->id)
            ->whereDate('fecha', $fecha)
            ->first();

        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => "{$persona->nombre} ya tiene asistencia registrada hoy.",
                'duplicado' => true,
                'asistencia' => $existente,
            ]);
        }

        // Determinar estado según horario basado en el turno de la persona
        $turno_persona = $persona->turno ?: 'mañana'; // fallback
        $horario = ConfiguracionHorario::where('nombre', $turno_persona)->first();
        if (!$horario) {
            $horario = ConfiguracionHorario::activo();
        }

        $estado = 'puntual';

        if ($horario) {
            $horaEntrada = Carbon::createFromFormat('H:i', $horario->hora_entrada);
            $horaTardanza = Carbon::createFromFormat('H:i', $horario->hora_tardanza);
            $horaFalta = Carbon::createFromFormat('H:i', $horario->hora_falta);
            $ahora = Carbon::createFromFormat('H:i:s', $horaActual);

            if ($ahora->greaterThan($horaFalta)) {
                $estado = 'falta';
            } elseif ($ahora->greaterThan($horaTardanza)) {
                $estado = 'tardanza';
            } else {
                $estado = 'puntual';
            }
        }

        // Guardar foto de captura si viene en base64
        $fotoCapturaPath = null;
        if ($request->foto_captura) {
            $imageData = $request->foto_captura;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $ext = strtolower($type[1]);
                $decoded = base64_decode($imageData);
                if ($decoded) {
                    $filename = "asistencias/{$fecha}/{$persona->codigo}_crop_" . time() . ".{$ext}";
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decoded);
                    $fotoCapturaPath = $filename;
                }
            }
        }

        $fotoCompletaPath = null;
        if ($request->foto_completa) {
            $imageData = $request->foto_completa;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $ext = strtolower($type[1]);
                $decoded = base64_decode($imageData);
                if ($decoded) {
                    $filename = "asistencias/{$fecha}/{$persona->codigo}_full_" . time() . ".{$ext}";
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decoded);
                    $fotoCompletaPath = $filename;
                }
            }
        }

        $asistencia = Asistencia::create([
            'persona_id' => $persona->id,
            'fecha' => $fecha,
            'hora_ingreso' => $horaActual,
            'turno' => $persona->turno,
            'estado' => $estado,
            'confianza' => $request->confianza,
            'foto_captura' => $fotoCapturaPath,
            'foto_completa' => $fotoCompletaPath,
            'sesion_id' => $request->sesion_id,
        ]);

        $asistencia->load('persona');

        return response()->json([
            'success' => true,
            'message' => "Asistencia registrada: {$persona->nombre} - {$estado}",
            'asistencia' => [
                'id' => $asistencia->id,
                'persona_nombre' => $persona->nombre,
                'persona_codigo' => $persona->codigo,
                'persona_foto' => $persona->foto_url,
                'persona_cargo' => $persona->cargo,
                'hora_ingreso' => $asistencia->hora_ingreso,
                'estado' => $asistencia->estado,
                'confianza' => round($asistencia->confianza * 100, 1),
                'fecha' => $asistencia->fecha->format('d/m/Y'),
            ],
        ]);
    }

    /**
     * Actualizar estado de asistencia (admin puede cambiar puntual/tardanza/falta)
     */
    public function actualizarEstado(Request $request, Asistencia $asistencia)
    {
        $request->validate([
            'estado' => 'required|in:puntual,tardanza,falta',
        ]);

        $asistencia->estado = $request->estado;
        $asistencia->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
        ]);
    }

    /**
     * Guardar configuración de horarios
     */
    public function guardarHorario(Request $request)
    {
        $request->validate([
            'horarios.mañana.hora_entrada' => 'required|string',
            'horarios.mañana.hora_tardanza' => 'required|string',
            'horarios.mañana.hora_falta' => 'required|string',
            'horarios.tarde.hora_entrada' => 'required|string',
            'horarios.tarde.hora_tardanza' => 'required|string',
            'horarios.tarde.hora_falta' => 'required|string',
        ]);

        foreach (['mañana', 'tarde'] as $turno) {
            $horario = ConfiguracionHorario::where('nombre', $turno)->first();
            if (!$horario) {
                $horario = new ConfiguracionHorario();
                $horario->nombre = $turno;
                $horario->activo = true;
            }

            $horario->hora_entrada = $request->input("horarios.{$turno}.hora_entrada");
            $horario->hora_tardanza = $request->input("horarios.{$turno}.hora_tardanza");
            $horario->hora_falta = $request->input("horarios.{$turno}.hora_falta");
            $horario->save();
        }

        return back()->with('success', 'Horarios por turnos actualizados correctamente.');
    }

    /**
     * API: Obtener asistencias de hoy (para polling en vista en vivo)
     */
    public function asistenciasHoy(Request $request)
    {
        $fecha = today()->toDateString();
        $asistencias = Asistencia::with('persona')
            ->whereDate('fecha', $fecha)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'persona_nombre' => $a->persona->nombre,
                'persona_codigo' => $a->persona->codigo,
                'persona_foto' => $a->persona->foto_url,
                'persona_cargo' => $a->persona->cargo,
                'hora_ingreso' => $a->hora_ingreso,
                'estado' => $a->estado,
                'confianza' => round($a->confianza * 100, 1),
            ]);

        return response()->json($asistencias);
    }

    public function exportar(Request $request)
    {
        $fecha = $request->input('fecha', today()->toDateString());
        $formato = $request->input('formato', 'pdf');
        
        $user = auth()->user();
        $query = Asistencia::with('persona')->whereDate('fecha', $fecha);
        
        // Filtros de persona en la exportación
        if ($request->filled('cargo')) {
            $query->whereHas('persona', fn($q) => $q->where('cargo', $request->cargo));
        }
        if ($request->filled('grado')) {
            $query->whereHas('persona', fn($q) => $q->where('grado', $request->grado));
        }
        if ($request->filled('seccion')) {
            $query->whereHas('persona', fn($q) => $q->where('seccion', $request->seccion));
        }
        if ($request->filled('turno')) {
            $query->whereHas('persona', fn($q) => $q->where('turno', $request->turno));
        }

        if ($user->role === 'docente') {
            $query->whereHas('persona', fn($q) => $q->where('area', $user->area));
        }
        
        $asistencias = $query->orderByDesc('created_at')->get();
        if ($formato === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencia.export_pdf', compact('asistencias', 'fecha'));
            return $pdf->download("asistencia_{$fecha}.pdf");
        } else {
            return \Maatwebsite\Excel\Facades\Excel::download(new AsistenciaExport($asistencias, $fecha), "asistencia_{$fecha}.xlsx");
        }
    }

    /**
     * API: Obtener historial individual de un estudiante/docente
     */
    public function historialPersona(Persona $persona)
    {
        $asistencias = $persona->asistencias()->orderBy('fecha', 'desc')->get()->map(function($a) {
            return [
                'id' => $a->id,
                'fecha' => $a->fecha,
                'fecha_format' => \Carbon\Carbon::parse($a->fecha)->format('d/m/Y'),
                'hora' => $a->hora_ingreso,
                'estado' => $a->estado,
                'turno' => $a->turno
            ];
        });

        return response()->json([
            'success' => true,
            'persona' => [
                'nombre' => $persona->nombre,
                'codigo' => $persona->codigo,
                'cargo' => $persona->cargo,
                'foto' => $persona->foto_url,
                'total_asistencias' => $asistencias->count()
            ],
            'asistencias' => $asistencias
        ]);
    }
}

class AsistenciaExport implements \Maatwebsite\Excel\Concerns\FromView, \Maatwebsite\Excel\Concerns\ShouldAutoSize
{
    protected $asistencias;
    protected $fecha;

    public function __construct($asistencias, $fecha)
    {
        $this->asistencias = $asistencias;
        $this->fecha = $fecha;
    }

    public function view(): \Illuminate\Contracts\View\View
    {
        return view('asistencia.export_excel', [
            'asistencias' => $this->asistencias,
            'fecha' => $this->fecha
        ]);
    }
}
