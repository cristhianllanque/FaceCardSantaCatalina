@extends('layouts.app')
@section('title', 'Editar Persona')
@section('page-title', 'Editar: ' . $persona->nombre)

@section('content')
<div class="card" style="max-width:700px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-edit"></i> Editar Persona</h3>
    </div>
    <form method="POST" action="{{ route('personas.update', $persona) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        @if($persona->foto_path)
        <div style="margin-bottom:20px;text-align:center;">
            <img src="{{ asset('storage/' . $persona->foto_path) }}" style="width:120px;height:120px;object-fit:cover;border-radius:12px;border:2px solid var(--border);" alt="{{ $persona->nombre }}">
        </div>
        @endif

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Código *</label>
                <input type="text" name="codigo" class="form-control" value="{{ old('codigo', $persona->codigo) }}" required>
                @error('codigo')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Celular</label>
                <input type="text" name="celular" class="form-control" value="{{ old('celular', $persona->celular) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $persona->nombre) }}" required>
        </div>
        <div class="form-group">
            <label class="form-label">Cargo *</label>
            <select name="cargo" class="form-control" id="cargoSelect" required>
                <option value="estudiante" {{ old('cargo', $persona->cargo) === 'estudiante' ? 'selected' : '' }}>Estudiante</option>
                <option value="docente" {{ old('cargo', $persona->cargo) === 'docente' ? 'selected' : '' }}>Docente</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Área</label>
            <select name="area" class="form-control">
                <option value="">Sin área</option>
                @foreach(['Ingeniería de Sistemas','Ingeniería Civil','Administración','Contabilidad','Educación','Derecho','Medicina'] as $a)
                <option value="{{ $a }}" {{ old('area', $persona->area) === $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div id="estudianteFields" class="{{ old('cargo', $persona->cargo) !== 'estudiante' ? 'hidden' : '' }}">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Grado</label>
                    <select name="grado" class="form-control">
                        <option value="">-</option>
                        @for($i=1;$i<=6;$i++)
                        <option value="{{ $i }}" {{ old('grado', $persona->grado) == $i ? 'selected' : '' }}>{{ $i }}°</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sección</label>
                    <select name="seccion" class="form-control">
                        <option value="">-</option>
                        @foreach(['A','B','C','D','E'] as $s)
                        <option value="{{ $s }}" {{ old('seccion', $persona->seccion) === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Cambiar foto</label>
            <input type="file" name="foto" class="form-control" accept="image/*">
        </div>
        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
            <a href="{{ route('personas.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('cargoSelect').addEventListener('change', function() {
    document.getElementById('estudianteFields').classList.toggle('hidden', this.value !== 'estudiante');
});
</script>
@endpush
@endsection
