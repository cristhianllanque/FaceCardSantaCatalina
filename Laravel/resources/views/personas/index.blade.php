@extends('layouts.app')
@section('title', 'Personas')
@section('page-title', 'Gestión de Personas')

@section('content')
{{-- Filtros --}}
<div class="card mb-3">
    <form method="GET" action="{{ route('personas.index') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label class="form-label">Buscar</label>
            <input type="text" name="buscar" class="form-control" placeholder="Nombre o código..." value="{{ request('buscar') }}">
        </div>
        <div class="form-group" style="margin:0;min-width:150px;">
            <label class="form-label">Cargo</label>
            <select name="cargo" class="form-control">
                <option value="">Todos</option>
                <option value="estudiante" {{ request('cargo') === 'estudiante' ? 'selected' : '' }}>Estudiante</option>
                <option value="docente" {{ request('cargo') === 'docente' ? 'selected' : '' }}>Docente</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:150px;">
            <label class="form-label">Área</label>
            <select name="area" class="form-control">
                <option value="">Todas</option>
                @foreach($areas as $area)
                <option value="{{ $area }}" {{ request('area') === $area ? 'selected' : '' }}>{{ $area }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
        <a href="{{ route('personas.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i></a>
    </form>
</div>

{{-- Header --}}
<div class="flex items-center justify-between mb-2">
    <p style="color:var(--text-secondary);font-size:0.88rem;">{{ $personas->total() }} personas registradas</p>
    <a href="{{ route('personas.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Nuevo Registro</a>
</div>

{{-- Grid de personas --}}
<div class="persons-grid">
    @forelse($personas as $persona)
    <div class="person-card">
        <div class="person-card-img">
            @if($persona->foto_path)
                <img src="{{ asset('storage/' . $persona->foto_path) }}" alt="{{ $persona->nombre }}" onerror="this.parentElement.innerHTML='<div class=placeholder-icon><i class=fas fa-user></i></div>'">
            @else
                <div class="placeholder-icon"><i class="fas fa-user"></i></div>
            @endif
        </div>
        <div class="person-card-body">
            <div class="person-card-name">{{ $persona->nombre }}</div>
            <div class="person-card-code">{{ $persona->codigo }}</div>
            <div class="person-card-meta">
                <span class="badge badge-{{ $persona->cargo === 'docente' ? 'info' : 'accent' }}">
                    {{ ucfirst($persona->cargo) }}
                </span>
                @if($persona->area)
                <span class="badge badge-secondary">{{ $persona->area }}</span>
                @endif
                @if($persona->grado)
                <span class="badge badge-secondary">{{ $persona->grado }}° {{ $persona->seccion }}</span>
                @endif
                @if($persona->tiene_embedding)
                <span class="badge badge-success"><i class="fas fa-check"></i> Facial</span>
                @endif
            </div>
            <div class="person-card-actions">
                <a href="{{ route('personas.edit', $persona) }}" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Editar</a>
                <form action="{{ route('personas.destroy', $persona) }}" method="POST" onsubmit="return confirm('¿Eliminar a {{ $persona->nombre }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted);">
        <i class="fas fa-users" style="font-size:3rem;margin-bottom:16px;display:block;"></i>
        <p>No hay personas registradas</p>
        <a href="{{ route('personas.create') }}" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> Registrar primera persona</a>
    </div>
    @endforelse
</div>

<div class="pagination mt-3">{{ $personas->withQueryString()->links('pagination.simple') }}</div>
@endsection
