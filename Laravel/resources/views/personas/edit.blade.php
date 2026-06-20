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
        <div class="form-group {{ old('cargo', $persona->cargo) !== 'docente' ? 'hidden' : '' }}" id="areaGroup">
            <label class="form-label">Área</label>
            <select name="area" class="form-control">
                <option value="">Seleccionar área/curso...</option>
                <option value="Matemática" {{ old('area', $persona->area) === 'Matemática' ? 'selected' : '' }}>Matemática</option>
                <option value="Comunicación" {{ old('area', $persona->area) === 'Comunicación' ? 'selected' : '' }}>Comunicación</option>
                <option value="Ciencias Sociales" {{ old('area', $persona->area) === 'Ciencias Sociales' ? 'selected' : '' }}>Ciencias Sociales</option>
                <option value="Ciencia y Tecnología" {{ old('area', $persona->area) === 'Ciencia y Tecnología' ? 'selected' : '' }}>Ciencia y Tecnología</option>
                <option value="Inglés" {{ old('area', $persona->area) === 'Inglés' ? 'selected' : '' }}>Inglés</option>
                <option value="Educación Física" {{ old('area', $persona->area) === 'Educación Física' ? 'selected' : '' }}>Educación Física</option>
                <option value="Arte y Cultura" {{ old('area', $persona->area) === 'Arte y Cultura' ? 'selected' : '' }}>Arte y Cultura</option>
                <option value="EPT" {{ old('area', $persona->area) === 'EPT' ? 'selected' : '' }}>EPT (Educación para el Trabajo)</option>
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
    const cargoSelect = document.querySelector('select[name="cargo"]');
    const estudianteFields = document.getElementById('estudianteFields');
    const areaGroup = document.getElementById('areaGroup');

    function toggleFields() {
        if (estudianteFields) {
            estudianteFields.classList.toggle('hidden', cargoSelect.value !== 'estudiante');
        }
        if (areaGroup) {
            areaGroup.classList.toggle('hidden', cargoSelect.value !== 'docente');
        }
    }

    if (cargoSelect) {
        cargoSelect.addEventListener('change', toggleFields);
    }
</script>
@endpush
@endsection
