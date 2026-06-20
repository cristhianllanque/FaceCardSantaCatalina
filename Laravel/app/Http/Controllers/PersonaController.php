<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        // Query base para Estudiantes
        $queryEst = Persona::where('cargo', 'estudiante');
        if ($request->filled('buscar_est')) {
            $search = $request->buscar_est;
            $queryEst->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }
        if ($request->filled('grado')) { $queryEst->where('grado', $request->grado); }
        if ($request->filled('seccion')) { $queryEst->where('seccion', $request->seccion); }
        if ($request->filled('turno_est')) { $queryEst->where('turno', $request->turno_est); }

        $estudiantes = $queryEst->latest()->paginate(10, ['*'], 'page_est');

        // Query base para Docentes
        $queryDoc = Persona::where('cargo', 'docente');
        if ($request->filled('buscar_doc')) {
            $search = $request->buscar_doc;
            $queryDoc->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }
        if ($request->filled('area')) { $queryDoc->where('area', $request->area); }
        if ($request->filled('turno_doc')) { $queryDoc->where('turno', $request->turno_doc); }

        $docentes = $queryDoc->latest()->paginate(10, ['*'], 'page_doc');

        $areas = Persona::where('cargo', 'docente')->whereNotNull('area')->distinct()->pluck('area');

        return view('personas.index', compact('estudiantes', 'docentes', 'areas'));
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
            'turno' => 'nullable|in:mañana,tarde',
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
        $persona->turno = $validated['turno'] ?? null;

        // Si sube foto de perfil (archivo opcional)
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('personas/fotos', 'public');
            $persona->foto_path = $path;
        }
        
        // Si hay foto base64 del escaneo (obligatorio para la IA)
        if ($request->filled('foto_base64')) {
            $imageData = $request->foto_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]);
                $imageData = base64_decode($imageData);
                $filename = "personas/fotos/{$persona->codigo}_" . time() . ".{$type}";
                Storage::disk('public')->put($filename, $imageData);
                
                // Si NO había subido foto de archivo, usamos esta como foto de perfil
                if (!$request->hasFile('foto')) {
                    $persona->foto_path = $filename;
                }
                
                // Siempre marcamos que tiene embedding porque se completó el escaneo
                $persona->tiene_embedding = true;
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
            'turno' => 'nullable|in:mañana,tarde',
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
        $persona->turno = $validated['turno'] ?? null;

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
        $persona->delete();

        return redirect()->route('personas.index')->with('success', 'Persona eliminada correctamente.');
    }

    /**
     * Obtiene las URLs de los 10 rostros IA de una persona.
     */
    public function getFaces(Persona $persona)
    {
        $codigo = $persona->codigo;
        $datasetPath = base_path('../dataset/' . $codigo . '/faces');
        
        if (!file_exists($datasetPath)) {
            return response()->json(['success' => false, 'message' => 'No se encontró la carpeta del dataset.']);
        }

        $files = array_diff(scandir($datasetPath), array('.', '..'));
        $faces = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'jpg' || pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                $faces[] = url('/dataset/' . $codigo . '/' . $file);
            }
        }

        sort($faces);

        return response()->json([
            'success' => true,
            'faces' => array_values($faces)
        ]);
    }

    /**
     * Sirve físicamente la imagen desde la carpeta dataset de Python.
     */
    public function serveFaceImage($codigo, $filename)
    {
        $path = base_path('../dataset/' . $codigo . '/faces/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
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
