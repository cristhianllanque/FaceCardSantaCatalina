@extends('layouts.app')
@section('title', 'Personas')
@section('page-title', 'Gestión de Personas - Inventario General')

@push('styles')
<style>
/* ─── Dashboard Tabs ─── */
.persona-grado-btn {
    padding: 10px 18px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-bottom: 3px solid transparent;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    border-radius: 8px 8px 0 0;
    margin-right: 2px;
    transition: var(--transition);
    white-space: nowrap;
}
.persona-grado-btn:hover {
    background: var(--bg-input);
    color: var(--text-primary);
}
.persona-grado-btn.active {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
    border-bottom-color: var(--accent);
}

.persona-side-btn {
    padding: 10px 14px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
    border-radius: 6px;
    text-align: left;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}
.persona-side-btn:hover {
    background: var(--bg-input);
    color: var(--text-primary);
}
.persona-side-btn.active {
    background: var(--bg-input);
    color: var(--accent);
    border-color: var(--accent);
    box-shadow: inset 3px 0 0 var(--accent);
}

.persona-side-btn .side-count {
    margin-left: auto;
    background: rgba(255,255,255,0.08);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    color: var(--text-muted);
    font-weight: 500;
}
.persona-side-btn.active .side-count {
    background: rgba(99,102,241,0.15);
    color: var(--accent);
}

/* ─── Main Tabs (Estudiantes / Docentes) ─── */
.persona-main-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--border);
}
.persona-main-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
}
.persona-main-tab:hover {
    color: var(--text-primary);
}
.persona-main-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
}
.persona-main-tab .tab-count {
    background: var(--bg-input);
    padding: 2px 10px;
    border-radius: 10px;
    font-size: 0.75rem;
}
.persona-main-tab.active .tab-count {
    background: rgba(99,102,241,0.15);
    color: var(--accent);
}

/* ─── Table ─── */
.persona-table {
    width: 100%;
    border-collapse: collapse;
}
.persona-table th {
    text-align: left;
    padding: 10px 14px;
    color: var(--text-muted);
    font-size: 0.75rem;
    text-transform: uppercase;
    background: var(--bg-input);
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}
.persona-table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    font-size: 0.88rem;
}
.persona-table tbody tr {
    transition: background 0.15s ease;
}
.persona-table tbody tr:hover {
    background: rgba(255,255,255,0.02);
}

/* ─── Search Bar ─── */
.persona-search-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}
.persona-search-bar input {
    flex: 1;
    padding: 10px 14px 10px 38px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 0.88rem;
    transition: var(--transition);
}
.persona-search-bar input:focus {
    border-color: var(--accent);
    outline: none;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}
.persona-search-icon {
    position: absolute;
    left: 12px;
    color: var(--text-muted);
    font-size: 0.85rem;
    pointer-events: none;
}

/* ─── Dashboard Container ─── */
.persona-dashboard {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.persona-dashboard-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 130px;
}
.persona-dashboard-center {
    flex: 1;
    background: var(--bg-card);
    border-radius: 8px;
    border: 1px solid var(--border);
    overflow: hidden;
    min-width: 0;
}
.persona-dashboard-right {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 140px;
}
.persona-dashboard-header {
    padding: 12px 16px;
    background: var(--bg-input);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.persona-dashboard-header h4 {
    margin: 0;
    font-size: 0.88rem;
    color: var(--text-primary);
    font-weight: 600;
}
.persona-dashboard-header .result-count {
    font-size: 0.78rem;
    color: var(--text-muted);
}

/* ─── Tab Content Show/Hide ─── */
.persona-tab-content { display: none; }
.persona-tab-content.active { display: block; animation: fadeIn 0.25s ease; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ─── Empty State ─── */
.persona-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}
.persona-empty i {
    font-size: 2.5rem;
    margin-bottom: 12px;
    display: block;
    opacity: 0.4;
}
.persona-empty p {
    font-size: 0.9rem;
    margin: 4px 0;
}
</style>
@endpush

@section('content')

<div class="flex items-center justify-between mb-4">
    <p style="color:var(--text-secondary);font-size:0.9rem;">
        Administración completa del personal y alumnado. Organizado por grado, sección, área y turno.
    </p>
    <a href="{{ route('personas.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Nuevo Registro</a>
</div>

{{-- ═══ Pestañas Principales ═══ --}}
<div class="persona-main-tabs">
    <button class="persona-main-tab active" onclick="switchPersonaMainTab('est', this)">
        <i class="fas fa-user-graduate"></i> Estudiantes
        <span class="tab-count">{{ $estudiantes->count() }}</span>
    </button>
    <button class="persona-main-tab" onclick="switchPersonaMainTab('doc', this)">
        <i class="fas fa-chalkboard-teacher"></i> Docentes
        <span class="tab-count">{{ $docentes->count() }}</span>
    </button>
</div>

{{-- ═══════════════════════════════════════════════════════ --}}
{{--                  PESTAÑA ESTUDIANTES                   --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<div id="persona-tab-est" class="persona-tab-content active">

    {{-- Barra de Búsqueda --}}
    <form method="GET" action="{{ route('personas.index') }}" class="persona-search-bar" style="position:relative;">
        <i class="fas fa-search persona-search-icon"></i>
        <input type="text" name="buscar_est" placeholder="Buscar estudiante por nombre o código..." value="{{ request('buscar_est') }}">
        @if(request('buscar_est'))
            <a href="{{ route('personas.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Limpiar</a>
        @endif
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Buscar</button>
    </form>

    {{-- Pestañas de Grado (Superiores) --}}
    <div style="display:flex; border-bottom: 2px solid var(--border); overflow-x:auto; margin-bottom:16px;">
        @for($i=1; $i<=6; $i++)
            <button class="persona-grado-btn {{ $i == 1 ? 'active' : '' }}" data-grado="{{ $i }}" onclick="filterPersonaEst(this, 'grado')">
                {{ $i }}° Grado
            </button>
        @endfor
    </div>

    {{-- Dashboard Layout --}}
    <div class="persona-dashboard">
        {{-- Secciones (Izquierda) --}}
        <div class="persona-dashboard-left">
            @foreach(['A','B','C','D','E'] as $index => $sec)
                <button class="persona-side-btn {{ $index == 0 ? 'active' : '' }}" data-seccion="{{ $sec }}" onclick="filterPersonaEst(this, 'seccion')">
                    <i class="fas fa-users text-accent"></i> Sección {{ $sec }}
                    <span class="side-count est-count-{{ $sec }}">0</span>
                </button>
            @endforeach
        </div>

        {{-- Tabla Central --}}
        <div class="persona-dashboard-center">
            <div class="persona-dashboard-header">
                <h4><i class="fas fa-user-graduate" style="margin-right:6px; opacity:0.6;"></i> INVENTARIO DE ESTUDIANTES</h4>
                <span class="result-count" id="estVisibleCount">0 registrados</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="persona-table" id="tablaEstudiantes">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Grado/Sección</th>
                            <th>IA Facial</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estudiantes as $est)
                        <tr class="est-row" data-grado="{{ $est->grado ?? '1' }}" data-seccion="{{ $est->seccion ?? 'A' }}" data-turno="{{ strtolower($est->turno ?? 'mañana') }}">
                            <td>
                                <img src="{{ $est->foto_url }}" style="width:40px;height:40px;border-radius:8px;object-fit:cover;" onerror="this.src='/images/default-avatar.svg'">
                            </td>
                            <td style="font-family:monospace;font-weight:bold;color:var(--accent);">{{ $est->codigo }}</td>
                            <td>{{ $est->nombre }}</td>
                            <td>{{ $est->grado }}° {{ $est->seccion }}</td>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            <tr id="estEmptyRow" style="display:none;">
                <td colspan="6">
                    <div class="persona-empty">
                        <i class="fas fa-user-slash"></i>
                        <p>No hay estudiantes registrados con estos filtros.</p>
                        <p style="font-size:0.78rem;">Prueba cambiando de grado, sección o turno.</p>
                    </div>
                </td>
            </tr>
        </div>

        {{-- Turnos (Derecha) --}}
        <div class="persona-dashboard-right">
            <button class="persona-side-btn active" data-turno="mañana" onclick="filterPersonaEst(this, 'turno')">
                <i class="fas fa-sun text-warning"></i> Turno Mañana
            </button>
            <button class="persona-side-btn" data-turno="tarde" onclick="filterPersonaEst(this, 'turno')">
                <i class="fas fa-moon text-info"></i> Turno Tarde
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════ --}}
{{--                   PESTAÑA DOCENTES                     --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<div id="persona-tab-doc" class="persona-tab-content">

    {{-- Barra de Búsqueda --}}
    <form method="GET" action="{{ route('personas.index') }}" class="persona-search-bar" style="position:relative;">
        <i class="fas fa-search persona-search-icon"></i>
        <input type="text" name="buscar_doc" placeholder="Buscar docente por nombre o código..." value="{{ request('buscar_doc') }}">
        @if(request('buscar_doc'))
            <a href="{{ route('personas.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Limpiar</a>
        @endif
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Buscar</button>
    </form>

    {{-- Dashboard Layout --}}
    <div class="persona-dashboard">
        {{-- Áreas (Izquierda) --}}
        <div class="persona-dashboard-left" style="min-width:170px;">
            @foreach($areas as $index => $area)
                <button class="persona-side-btn {{ $index == 0 ? 'active' : '' }}" data-area="{{ $area }}" onclick="filterPersonaDoc(this, 'area')">
                    <i class="fas fa-briefcase text-info"></i> {{ $area }}
                    <span class="side-count doc-count-{{ Str::slug($area) }}">0</span>
                </button>
            @endforeach
            @if($areas->isEmpty())
                <div style="padding:12px; color:var(--text-muted); font-size:0.82rem; text-align:center;">
                    <i class="fas fa-info-circle"></i> Sin áreas registradas
                </div>
            @endif
        </div>

        {{-- Tabla Central --}}
        <div class="persona-dashboard-center">
            <div class="persona-dashboard-header">
                <h4><i class="fas fa-chalkboard-teacher" style="margin-right:6px; opacity:0.6;"></i> INVENTARIO DE DOCENTES</h4>
                <span class="result-count" id="docVisibleCount">0 registrados</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="persona-table" id="tablaDocentes">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Área</th>
                            <th>IA Facial</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($docentes as $doc)
                        <tr class="doc-row" data-area="{{ $doc->area ?? 'Sin Área' }}" data-turno="{{ strtolower($doc->turno ?? 'mañana') }}">
                            <td>
                                <img src="{{ $doc->foto_url }}" style="width:40px;height:40px;border-radius:8px;object-fit:cover;" onerror="this.src='/images/default-avatar.svg'">
                            </td>
                            <td style="font-family:monospace;font-weight:bold;color:var(--accent);">{{ $doc->codigo }}</td>
                            <td>{{ $doc->nombre }}</td>
                            <td><span class="badge badge-info">{{ $doc->area ?? 'Sin Área' }}</span></td>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Turnos (Derecha) --}}
        <div class="persona-dashboard-right">
            <button class="persona-side-btn active" data-turno="mañana" onclick="filterPersonaDoc(this, 'turno')">
                <i class="fas fa-sun text-warning"></i> Turno Mañana
            </button>
            <button class="persona-side-btn" data-turno="tarde" onclick="filterPersonaDoc(this, 'turno')">
                <i class="fas fa-moon text-info"></i> Turno Tarde
            </button>
        </div>
    </div>
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
// ═══ Estado de los filtros ═══
let estGrado = '1';
let estSeccion = 'A';
let estTurno = 'mañana';

let docArea = '';
let docTurno = 'mañana';

// ═══ Inicialización ═══
document.addEventListener('DOMContentLoaded', () => {
    // Auto-seleccionar primera área de docentes
    const firstAreaBtn = document.querySelector('#persona-tab-doc .persona-side-btn[data-area]');
    if (firstAreaBtn) {
        docArea = firstAreaBtn.dataset.area;
    }

    updateEstFilter();
    updateDocFilter();
    updateSectionCounts();
    updateAreaCounts();
});

// ═══ Cambio de pestaña principal ═══
function switchPersonaMainTab(tab, btn) {
    document.querySelectorAll('.persona-main-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.persona-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('persona-tab-' + tab).classList.add('active');
}

// ═══ Filtro Estudiantes ═══
function filterPersonaEst(btn, type) {
    if (type === 'grado') {
        document.querySelectorAll('.persona-grado-btn').forEach(b => b.classList.remove('active'));
        estGrado = btn.dataset.grado;
    } else if (type === 'seccion') {
        document.querySelectorAll('#persona-tab-est .persona-side-btn[data-seccion]').forEach(b => b.classList.remove('active'));
        estSeccion = btn.dataset.seccion;
    } else if (type === 'turno') {
        document.querySelectorAll('#persona-tab-est .persona-side-btn[data-turno]').forEach(b => b.classList.remove('active'));
        estTurno = btn.dataset.turno;
    }
    btn.classList.add('active');
    updateEstFilter();
    updateSectionCounts();
}

function updateEstFilter() {
    let count = 0;
    document.querySelectorAll('.est-row').forEach(row => {
        const match = row.dataset.grado === estGrado &&
                      row.dataset.seccion === estSeccion &&
                      row.dataset.turno === estTurno;
        row.style.display = match ? '' : 'none';
        if (match) count++;
    });

    document.getElementById('estVisibleCount').textContent = count + ' registrado' + (count !== 1 ? 's' : '');

    const emptyRow = document.getElementById('estEmptyRow');
    if (emptyRow) {
        emptyRow.style.display = count === 0 ? '' : 'none';
    }
}

function updateSectionCounts() {
    ['A','B','C','D','E'].forEach(sec => {
        let count = 0;
        document.querySelectorAll('.est-row').forEach(row => {
            if (row.dataset.grado === estGrado && row.dataset.seccion === sec && row.dataset.turno === estTurno) {
                count++;
            }
        });
        const badge = document.querySelector('.est-count-' + sec);
        if (badge) badge.textContent = count;
    });
}

// ═══ Filtro Docentes ═══
function filterPersonaDoc(btn, type) {
    if (type === 'area') {
        document.querySelectorAll('#persona-tab-doc .persona-side-btn[data-area]').forEach(b => b.classList.remove('active'));
        docArea = btn.dataset.area;
    } else if (type === 'turno') {
        document.querySelectorAll('#persona-tab-doc .persona-side-btn[data-turno]').forEach(b => b.classList.remove('active'));
        docTurno = btn.dataset.turno;
    }
    btn.classList.add('active');
    updateDocFilter();
    updateAreaCounts();
}

function updateDocFilter() {
    if (!docArea) return;
    let count = 0;
    document.querySelectorAll('.doc-row').forEach(row => {
        const match = row.dataset.area === docArea && row.dataset.turno === docTurno;
        row.style.display = match ? '' : 'none';
        if (match) count++;
    });

    document.getElementById('docVisibleCount').textContent = count + ' registrado' + (count !== 1 ? 's' : '');
}

function updateAreaCounts() {
    document.querySelectorAll('#persona-tab-doc .persona-side-btn[data-area]').forEach(btn => {
        const area = btn.dataset.area;
        let count = 0;
        document.querySelectorAll('.doc-row').forEach(row => {
            if (row.dataset.area === area && row.dataset.turno === docTurno) {
                count++;
            }
        });
        const badge = btn.querySelector('.side-count');
        if (badge) badge.textContent = count;
    });
}

// ═══ Modal Rostros IA ═══
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
