@extends('layouts.app')
@section('title', 'Personas')
@section('page-title', 'Gestión de Personas - Inventario General')

@section('content')

<style>
.inventory-section {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}
.inventory-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 12px;
}
.inventory-table {
    width: 100%;
    border-collapse: collapse;
}
.inventory-table th {
    text-align: left;
    padding: 12px;
    color: var(--text-muted);
    font-size: 0.85rem;
    text-transform: uppercase;
    border-bottom: 2px solid var(--border-color);
}
.inventory-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}
.inventory-table tbody tr:hover {
    background: rgba(255,255,255,0.02);
}
</style>

<div class="flex items-center justify-between mb-4">
    <p style="color:var(--text-secondary);font-size:0.9rem;">
        Administración completa del personal y alumnado. Mostrando todos los registros organizados.
    </p>
    <a href="{{ route('personas.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Nuevo Registro</a>
</div>

{{-- SECCIÓN ESTUDIANTES --}}
<div class="inventory-section">
    <div class="inventory-header">
        <h3 style="margin:0;"><i class="fas fa-user-graduate text-primary"></i> Inventario de Estudiantes ({{ $estudiantes->total() }})</h3>
    </div>
    
    <form method="GET" action="{{ route('personas.index') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;">
        <input type="hidden" name="page_doc" value="{{ request('page_doc') }}">
        
        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label class="form-label">Buscar Estudiante</label>
            <input type="text" name="buscar_est" class="form-control" placeholder="Nombre o código..." value="{{ request('buscar_est') }}">
        </div>
        
        <div class="form-group" style="margin:0;min-width:120px;">
            <label class="form-label">Grado</label>
            <select name="grado" class="form-control">
                <option value="">Todos</option>
                <option value="1" {{ request('grado') == '1' ? 'selected' : '' }}>1ro</option>
                <option value="2" {{ request('grado') == '2' ? 'selected' : '' }}>2do</option>
                <option value="3" {{ request('grado') == '3' ? 'selected' : '' }}>3ro</option>
                <option value="4" {{ request('grado') == '4' ? 'selected' : '' }}>4to</option>
                <option value="5" {{ request('grado') == '5' ? 'selected' : '' }}>5to</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:100px;">
            <label class="form-label">Sección</label>
            <select name="seccion" class="form-control">
                <option value="">Todas</option>
                <option value="A" {{ request('seccion') == 'A' ? 'selected' : '' }}>A</option>
                <option value="B" {{ request('seccion') == 'B' ? 'selected' : '' }}>B</option>
                <option value="C" {{ request('seccion') == 'C' ? 'selected' : '' }}>C</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:120px;">
            <label class="form-label">Turno</label>
            <select name="turno_est" class="form-control">
                <option value="">Todos</option>
                <option value="mañana" {{ request('turno_est') == 'mañana' ? 'selected' : '' }}>Mañana</option>
                <option value="tarde" {{ request('turno_est') == 'tarde' ? 'selected' : '' }}>Tarde</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
        <a href="{{ route('personas.index', ['page_doc' => request('page_doc')]) }}" class="btn btn-secondary"><i class="fas fa-times"></i></a>
    </form>

    <div style="overflow-x:auto;">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Grado/Sección</th>
                    <th>Turno</th>
                    <th>IA Facial</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($estudiantes as $est)
                <tr>
                    <td>
                        <img src="{{ $est->foto_url }}" style="width:40px;height:40px;border-radius:8px;object-fit:cover;" onerror="this.src='/images/default-avatar.svg'">
                    </td>
                    <td style="font-family:monospace;font-weight:bold;">{{ $est->codigo }}</td>
                    <td>{{ $est->nombre }}</td>
                    <td>{{ $est->grado }}° {{ $est->seccion }}</td>
                    <td>
                        @if($est->turno)
                            <span class="badge badge-secondary" style="background:#444;"><i class="fas fa-sun"></i> {{ ucfirst($est->turno) }}</span>
                        @else
                            <span style="color:var(--text-muted);font-size:0.85rem;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($est->tiene_embedding)
                        <span class="badge badge-success" style="cursor:pointer;" onclick="openFacesModal('{{ $est->id }}', '{{ $est->nombre }}')"><i class="fas fa-camera"></i> Ver Rostros IA</span>
                        @else
                        <span class="badge badge-danger">Sin Escaneo</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="{{ route('personas.edit', $est) }}" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('personas.destroy', $est) }}" method="POST" onsubmit="return confirm('¿Eliminar a {{ $est->nombre }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px;">No hay estudiantes registrados o que coincidan con el filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $estudiantes->withQueryString()->links('pagination.simple') }}</div>
</div>

{{-- SECCIÓN DOCENTES --}}
<div class="inventory-section">
    <div class="inventory-header">
        <h3 style="margin:0;"><i class="fas fa-chalkboard-teacher text-info"></i> Inventario de Docentes ({{ $docentes->total() }})</h3>
    </div>
    
    <form method="GET" action="{{ route('personas.index') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;">
        <input type="hidden" name="page_est" value="{{ request('page_est') }}">
        
        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label class="form-label">Buscar Docente</label>
            <input type="text" name="buscar_doc" class="form-control" placeholder="Nombre o código..." value="{{ request('buscar_doc') }}">
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
        <div class="form-group" style="margin:0;min-width:120px;">
            <label class="form-label">Turno</label>
            <select name="turno_doc" class="form-control">
                <option value="">Todos</option>
                <option value="mañana" {{ request('turno_doc') == 'mañana' ? 'selected' : '' }}>Mañana</option>
                <option value="tarde" {{ request('turno_doc') == 'tarde' ? 'selected' : '' }}>Tarde</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
        <a href="{{ route('personas.index', ['page_est' => request('page_est')]) }}" class="btn btn-secondary"><i class="fas fa-times"></i></a>
    </form>

    <div style="overflow-x:auto;">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Turno</th>
                    <th>IA Facial</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($docentes as $doc)
                <tr>
                    <td>
                        <img src="{{ $doc->foto_url }}" style="width:40px;height:40px;border-radius:8px;object-fit:cover;" onerror="this.src='/images/default-avatar.svg'">
                    </td>
                    <td style="font-family:monospace;font-weight:bold;">{{ $doc->codigo }}</td>
                    <td>{{ $doc->nombre }}</td>
                    <td><span class="badge badge-info">{{ $doc->area ?? 'Sin Área' }}</span></td>
                    <td>
                        @if($doc->turno)
                            <span class="badge badge-secondary" style="background:#444;"><i class="fas fa-sun"></i> {{ ucfirst($doc->turno) }}</span>
                        @else
                            <span style="color:var(--text-muted);font-size:0.85rem;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($doc->tiene_embedding)
                        <span class="badge badge-success" style="cursor:pointer;" onclick="openFacesModal('{{ $doc->id }}', '{{ $doc->nombre }}')"><i class="fas fa-camera"></i> Ver Rostros IA</span>
                        @else
                        <span class="badge badge-danger">Sin Escaneo</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="{{ route('personas.edit', $doc) }}" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('personas.destroy', $doc) }}" method="POST" onsubmit="return confirm('¿Eliminar a {{ $doc->nombre }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px;">No hay docentes registrados o que coincidan con el filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $docentes->withQueryString()->links('pagination.simple') }}</div>
</div>

{{-- Modal para Ver Rostros --}}
<div id="facesModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
    <div style="background:var(--bg-card); width:90%; max-width:800px; border-radius:12px; padding:20px; border:1px solid var(--border-color); box-shadow:0 10px 30px rgba(0,0,0,0.5);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:12px; margin-bottom:16px;">
            <h3 style="margin:0; color:white;"><i class="fas fa-camera text-primary"></i> Rostros IA de: <span id="facesModalName"></span></h3>
            <button onclick="closeFacesModal()" style="background:transparent; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div id="facesModalContent" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:10px; max-height:60vh; overflow-y:auto; padding-right:8px;">
            <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted);">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; margin-bottom:10px;"></i>
                <p>Cargando rostros desde el Dataset de Inteligencia Artificial...</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function openFacesModal(personaId, personaNombre) {
    document.getElementById('facesModal').style.display = 'flex';
    document.getElementById('facesModalName').textContent = personaNombre;
    const content = document.getElementById('facesModalContent');
    content.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted);"><i class="fas fa-spinner fa-spin" style="font-size:2rem; margin-bottom:10px;"></i><p>Cargando rostros...</p></div>';

    try {
        const res = await fetch(`/personas/${personaId}/rostros`);
        const data = await res.json();
        
        if (data.success && data.faces.length > 0) {
            content.innerHTML = '';
            data.faces.forEach((url, i) => {
                const img = document.createElement('img');
                img.src = url;
                img.style.width = '100%';
                img.style.aspectRatio = '1/1';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '8px';
                img.style.border = '1px solid var(--border-color)';
                
                const wrapper = document.createElement('div');
                wrapper.style.position = 'relative';
                wrapper.appendChild(img);
                
                const badge = document.createElement('div');
                badge.textContent = '#' + (i + 1);
                badge.style.position = 'absolute';
                badge.style.bottom = '4px';
                badge.style.right = '4px';
                badge.style.background = 'rgba(0,0,0,0.7)';
                badge.style.color = 'white';
                badge.style.fontSize = '0.7rem';
                badge.style.padding = '2px 6px';
                badge.style.borderRadius = '4px';
                wrapper.appendChild(badge);
                
                content.appendChild(wrapper);
            });
        } else {
            content.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--warning);"><i class="fas fa-exclamation-triangle" style="font-size:2rem; margin-bottom:10px;"></i><p>No se encontraron fotos en el Dataset de esta persona.</p></div>';
        }
    } catch (e) {
        content.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--danger);"><i class="fas fa-times-circle" style="font-size:2rem; margin-bottom:10px;"></i><p>Error al cargar las fotos.</p></div>';
    }
}

function closeFacesModal() {
    document.getElementById('facesModal').style.display = 'none';
}
</script>
@endpush

@endsection
