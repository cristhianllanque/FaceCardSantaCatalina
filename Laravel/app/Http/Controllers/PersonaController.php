<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        $query = Persona::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('cargo')) {
            $query->where('cargo', $request->cargo);
        }

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        // Si docente, filtrar solo su área
        $user = auth()->user();
        if ($user->role === 'docente') {
            $query->where('area', $user->area);
        }

        $personas = $query->orderByDesc('created_at')->paginate(12);
        $areas = Persona::distinct()->pluck('area')->filter()->values();

        return view('personas.index', compact('personas', 'areas'));
    }

    public function create()
    {
        return view('personas.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'codigo' => 'required|string|max:20|unique:personas,codigo',
            'nombre' => 'required|string|max:255',
            'celular' => 'nullable|string|max:20',
            'cargo' => 'required|in:docente,estudiante',
            'area' => 'nullable|string|max:255',
            'grado' => 'nullable|string|max:20',
            'seccion' => 'nullable|string|max:10',
            'foto' => 'nullable|image|max:5120',
        ];

        $validated = $request->validate($rules);

        $persona = new Persona();
        $persona->codigo = $validated['codigo'];
        $persona->nombre = $validated['nombre'];
        $persona->celular = $validated['celular'] ?? null;
        $persona->cargo = $validated['cargo'];
        $persona->area = $validated['area'] ?? null;
        $persona->grado = $validated['grado'] ?? null;
        $persona->seccion = $validated['seccion'] ?? null;

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('personas/fotos', 'public');
            $persona->foto_path = $path;
        } elseif ($request->filled('foto_base64')) {
            $imageData = $request->foto_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]);
                $imageData = base64_decode($imageData);
                $filename = "personas/fotos/{$persona->codigo}_" . time() . ".{$type}";
                Storage::disk('public')->put($filename, $imageData);
                $persona->foto_path = $filename;
            }
        }

        $persona->save();

        return redirect()->route('personas.index')
            ->with('success', "Persona {$persona->nombre} registrada correctamente.");
    }

    public function show(Persona $persona)
    {
        $persona->load('asistencias');
        return view('personas.show', compact('persona'));
    }

    public function edit(Persona $persona)
    {
        return view('personas.edit', compact('persona'));
    }

    public function update(Request $request, Persona $persona)
    {
        $rules = [
            'codigo' => 'required|string|max:20|unique:personas,codigo,' . $persona->id,
            'nombre' => 'required|string|max:255',
            'celular' => 'nullable|string|max:20',
            'cargo' => 'required|in:docente,estudiante',
            'area' => 'nullable|string|max:255',
            'grado' => 'nullable|string|max:20',
            'seccion' => 'nullable|string|max:10',
            'foto' => 'nullable|image|max:5120',
        ];

        $validated = $request->validate($rules);

        $persona->codigo = $validated['codigo'];
        $persona->nombre = $validated['nombre'];
        $persona->celular = $validated['celular'] ?? null;
        $persona->cargo = $validated['cargo'];
        $persona->area = $validated['area'] ?? null;
        $persona->grado = $validated['grado'] ?? null;
        $persona->seccion = $validated['seccion'] ?? null;

        if ($request->hasFile('foto')) {
            // Eliminar foto anterior
            if ($persona->foto_path) {
                Storage::disk('public')->delete($persona->foto_path);
            }
            $path = $request->file('foto')->store('personas/fotos', 'public');
            $persona->foto_path = $path;
        }

        $persona->save();

        return redirect()->route('personas.index')
            ->with('success', "Persona {$persona->nombre} actualizada correctamente.");
    }

    public function destroy(Persona $persona)
    {
        if ($persona->foto_path) {
            Storage::disk('public')->delete($persona->foto_path);
        }

        $nombre = $persona->nombre;
        $persona->delete();

        return redirect()->route('personas.index')
            ->with('success', "Persona {$nombre} eliminada correctamente.");
    }

    /**
     * Guardar foto capturada desde la cámara web (enrollment facial)
     */
    public function guardarFoto(Request $request, Persona $persona)
    {
        $request->validate([
            'foto' => 'required|string', // Base64 image
        ]);

        $imageData = $request->foto;

        // Decodificar base64
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                return response()->json(['error' => 'Error decodificando imagen'], 400);
            }
        } else {
            return response()->json(['error' => 'Formato de imagen inválido'], 400);
        }

        $filename = "personas/fotos/{$persona->codigo}_" . time() . ".{$type}";
        Storage::disk('public')->put($filename, $imageData);

        $persona->foto_path = $filename;
        $persona->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto guardada correctamente',
            'foto_url' => asset('storage/' . $filename),
        ]);
    }

    /**
     * API: Buscar persona por código (para asistencia)
     */
    public function buscarPorCodigo(Request $request)
    {
        $codigo = $request->input('codigo');
        $persona = Persona::where('codigo', $codigo)->first();

        if (!$persona) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'persona' => [
                'id' => $persona->id,
                'codigo' => $persona->codigo,
                'nombre' => $persona->nombre,
                'cargo' => $persona->cargo,
                'area' => $persona->area,
                'foto_url' => $persona->foto_url,
            ],
        ]);
    }
}
