@extends('layouts.app')
@section('title', 'Historial de Asistencia')
@section('page-title', 'Historial de Asistencia')

@push('styles')
<style>
/* Estilos para Calendario Interactivo */
.date-nav-container {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 10px 16px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.date-nav-main {
    display: flex;
    align-items: center;
    gap: 16px;
}
.date-selector-btn {
    background: var(--bg-input);
    border: 1px solid var(--border);
    color: var(--text-primary);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}
.date-selector-btn:hover {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}
.date-display {
    text-align: center;
}
.date-display-day {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--accent);
    line-height: 1.1;
}
.date-display-full {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: capitalize;
}
.date-picker-hidden {
    position: absolute;
    opacity: 0;
    pointer-events: none;
    width: 0; height: 0;
}

/* Estilos para Pestañas (Tabs) */
.custom-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 2px solid var(--border);
    padding-bottom: 4px;
}
.custom-tab {
    padding: 8px 16px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-secondary);
    background: transparent;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 6px;
    position: relative;
}
.custom-tab:hover {
    color: var(--text-primary);
    background: var(--bg-input);
}
.custom-tab.active {
    color: var(--accent);
    background: rgba(139, 92, 246, 0.1);
}
.custom-tab.active::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 0;
    width: 100%;
    height: 2px;
    background: var(--accent);
    border-radius: 2px 2px 0 0;
}
.tab-content {
    display: none;
    animation: fadeIn 0.2s ease;
}
.tab-content.active {
    display: block;
}

/* Estilos para Acordeones (Submenús compactos) */
.accordion-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
}
.accordion-item {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
}
.accordion-header {
    width: 100%;
    padding: 10px 16px; /* COMPACTO */
    background: var(--bg-card);
    border: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    color: var(--text-primary);
    font-weight: 600;
    font-size: 0.9rem;
    transition: var(--transition);
}
.accordion-header:hover {
    background: var(--bg-input);
}
.accordion-header i.fa-chevron-down {
    transition: transform 0.3s ease;
    color: var(--text-muted);
}
.accordion-item.active > .accordion-header i.fa-chevron-down {
    transform: rotate(180deg);
}
.accordion-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: transparent; /* No agregar fondo extra para no parecer caja dentro de caja */
}
.accordion-item.active > .accordion-body {
    max-height: 5000px;
}
.accordion-content-inner {
    padding: 4px 12px 12px 12px;
}

/* Tablas ultra compactas y no expandidas */
.dash-grado-btn {
    padding: 10px 16px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-bottom: none;
    color: var(--text-secondary);
    font-weight: 600;
    cursor: pointer;
    border-radius: 8px 8px 0 0;
    margin-right: 4px;
    transition: var(--transition);
}
.dash-grado-btn.active {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}
.dash-seccion-btn, .dash-turno-btn, .dash-area-btn, .dash-turno-doc-btn {
    padding: 10px 16px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    color: var(--text-secondary);
    font-weight: 600;
    cursor: pointer;
    border-radius: 6px;
    text-align: left;
    transition: var(--transition);
}
.dash-seccion-btn.active, .dash-turno-btn.active, .dash-area-btn.active, .dash-turno-doc-btn.active {
    background: var(--bg-input);
    color: var(--accent);
    border-color: var(--accent);
    box-shadow: inset 3px 0 0 var(--accent);
}
.dashboard-estudiantes-container {
    animation: fadeIn 0.3s ease;
}

.nested-table {
    margin: 0;
    border-radius: var(--radius-sm);
    overflow: hidden;
}
.nested-table table {
    width: auto; /* No forzar al 100% de la pantalla */
    min-width: 600px; /* Ancho mínimo para que no se vea apretado */
}
.nested-table th {
    background: var(--bg-input);
    padding: 6px 16px; /* MUY COMPACTO */
    font-size: 0.75rem;
    white-space: nowrap;
}
.nested-table td {
    padding: 6px 16px; /* MUY COMPACTO */
    background: var(--bg-card);
    font-size: 0.85rem;
}
.nested-table tr:hover td {
    background: var(--bg-input);
}

/* Modal Historial */
.history-row {
    display: grid;
    grid-template-columns: 100px 1fr 100px 100px;
    padding: 8px 12px;
    border-bottom: 1px solid var(--border-light);
    align-items: center;
    font-size: 0.85rem;
}
.history-row:last-child {
    border-bottom: none;
}
.history-row.header {
    background: var(--bg-input);
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    font-size: 0.7rem;
}
</style>
@endpush

@section('content')

{{-- Panel Superior de Configuración (Horarios y Filtros Ocultables) --}}
@if(auth()->user()->isAdmin())
@php
    $horarioManana = \App\Models\ConfiguracionHorario::where('nombre', 'mañana')->first();
    $horarioTarde = \App\Models\ConfiguracionHorario::where('nombre', 'tarde')->first();
@endphp
<div class="card" style="padding:12px 16px; margin-bottom:12px; background:var(--bg-secondary);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-cog text-accent"></i> Panel de Administración Avanzado
        </h3>
        <button onclick="document.getElementById('adminPanel').classList.toggle('hidden')" class="btn btn-secondary btn-sm" style="background:transparent; border:1px solid var(--border);">
            <i class="fas fa-sliders-h"></i> Mostrar / Ocultar Controles
        </button>
    </div>

    <div id="adminPanel" class="hidden" style="margin-top:16px; border-top:1px solid var(--border); padding-top:16px;">
        {{-- Formularios de Horarios --}}
        <form method="POST" action="{{ route('asistencia.guardarHorario') }}" style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
            @csrf
            <h4 style="font-size:0.85rem; color:var(--text-secondary); margin:0;">1. Configurar Horarios Globales por Turno</h4>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div style="display:flex; gap:8px; align-items:center; background:var(--bg-input); padding:8px 12px; border-radius:6px;">
                    <strong style="color:var(--accent); font-size:0.85rem; width:80px;">Mañana:</strong>
                    <input type="time" name="horarios[mañana][hora_entrada]" class="form-control" value="{{ $horarioManana->hora_entrada ?? '08:00' }}" style="padding:4px 8px; font-size:0.8rem; width:auto;">
                    <input type="time" name="horarios[mañana][hora_tardanza]" class="form-control" value="{{ $horarioManana->hora_tardanza ?? '08:15' }}" style="padding:4px 8px; font-size:0.8rem; width:auto;">
                    <input type="time" name="horarios[mañana][hora_falta]" class="form-control" value="{{ $horarioManana->hora_falta ?? '08:30' }}" style="padding:4px 8px; font-size:0.8rem; width:auto;">
                </div>
                <div style="display:flex; gap:8px; align-items:center; background:var(--bg-input); padding:8px 12px; border-radius:6px;">
                    <strong style="color:var(--warning); font-size:0.85rem; width:80px;">Tarde:</strong>
                    <input type="time" name="horarios[tarde][hora_entrada]" class="form-control" value="{{ $horarioTarde->hora_entrada ?? '13:00' }}" style="padding:4px 8px; font-size:0.8rem; width:auto;">
                    <input type="time" name="horarios[tarde][hora_tardanza]" class="form-control" value="{{ $horarioTarde->hora_tardanza ?? '13:15' }}" style="padding:4px 8px; font-size:0.8rem; width:auto;">
                    <input type="time" name="horarios[tarde][hora_falta]" class="form-control" value="{{ $horarioTarde->hora_falta ?? '13:30' }}" style="padding:4px 8px; font-size:0.8rem; width:auto;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>


</div>
@endif

<div class="flex items-center justify-between mb-3 flex-wrap gap-2">
    <div>
        <h2 class="page-title" style="margin:0;">Asistencia Diaria</h2>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('asistencia.envivo') }}" class="btn btn-success btn-sm"><i class="fas fa-video"></i> Modo En Vivo</a>
        <a href="{{ route('asistencia.exportar', ['formato' => 'pdf', 'fecha' => $fecha]) }}" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> Exportar Todo PDF</a>
        <a href="{{ route('asistencia.exportar', ['formato' => 'excel', 'fecha' => $fecha]) }}" class="btn btn-success btn-sm" style="background:#107c41;border-color:#107c41;"><i class="fas fa-file-excel"></i> Exportar Todo Excel</a>
    </div>
</div>

{{-- Calendario Interactivo Compacto --}}
<div class="date-nav-container">
    <div class="date-nav-main">
        <button class="date-selector-btn" onclick="changeDate(-1)" title="Día Anterior">
            <i class="fas fa-chevron-left" style="font-size:0.8rem;"></i>
        </button>
        
        <div class="date-display" style="cursor:pointer;" onclick="document.getElementById('fecha_picker').showPicker()">
            @php
                $carbonDate = \Carbon\Carbon::parse($fecha)->locale('es');
            @endphp
            <div class="date-display-day">{{ $carbonDate->format('d') }}</div>
            <div class="date-display-full">{{ $carbonDate->translatedFormat('M Y') }} • {{ ucfirst($carbonDate->translatedFormat('l')) }}</div>
        </div>
        
        <button class="date-selector-btn" onclick="changeDate(1)" title="Día Siguiente">
            <i class="fas fa-chevron-right" style="font-size:0.8rem;"></i>
        </button>
    </div>
    
    <form id="dateForm" method="GET" style="margin:0;">
        <input type="date" name="fecha" id="fecha_picker" class="date-picker-hidden" value="{{ $fecha }}" onchange="document.getElementById('dateForm').submit()">
        <!-- Mantener filtros al cambiar de fecha -->
        @foreach(request()->except('fecha') as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
    
    <div style="text-align:right;">
        <span class="badge badge-accent" style="font-size:0.8rem; padding:6px 12px;">
            <i class="fas fa-users"></i> Total Registros: {{ $asistencias->count() }}
        </span>
    </div>
</div>

@php
    $asistenciasDocentes = $asistencias->filter(fn($a) => $a->persona->cargo === 'docente');
    $asistenciasEstudiantes = $asistencias->filter(fn($a) => $a->persona->cargo === 'estudiante');

    $docentesAgrupados = $asistenciasDocentes->groupBy(function($a) {
        return $a->persona->area ?: 'Sin Área';
    })->sortKeys();

    $estudiantesAgrupados = []; // Ya no se usa para la vista nueva, pero lo dejamos vacío por si acaso
@endphp

{{-- Pestañas Compactas --}}
<div class="custom-tabs">
    <button class="custom-tab active" onclick="switchTab('tab-estudiantes', this)">
        <i class="fas fa-user-graduate"></i> Estudiantes ({{ $asistenciasEstudiantes->count() }})
    </button>
    <button class="custom-tab" onclick="switchTab('tab-docentes', this)">
        <i class="fas fa-chalkboard-teacher"></i> Docentes ({{ $asistenciasDocentes->count() }})
    </button>
</div>

{{-- Pestaña: Estudiantes --}}
<div id="tab-estudiantes" class="tab-content active">
    @if($asistenciasEstudiantes->isEmpty())
        <div class="card" style="text-align:center; padding:30px; color:var(--text-muted);">
            <i class="fas fa-user-graduate" style="font-size:2rem; margin-bottom:12px; opacity:0.5;"></i>
            <h3 style="font-size:1rem;">No hay estudiantes</h3>
        </div>
    @else
        <div class="dashboard-estudiantes-container" style="display:flex; flex-direction:column; gap:16px;">
            <!-- Grados Top Tabs -->
            <div class="dashboard-grados-tabs" style="display:flex; border-bottom: 2px solid var(--border); overflow-x:auto;">
                @for($i=1; $i<=6; $i++)
                    <button class="dash-grado-btn {{ $i == 1 ? 'active' : '' }}" data-grado="{{ $i }}" onclick="filterDashboard(this, 'grado')">{{ $i }}° Grado</button>
                @endfor
            </div>
            
            <div style="display:flex; gap:16px; flex-wrap:wrap;">
                <!-- Secciones Left Tabs -->
                <div class="dashboard-secciones-tabs" style="display:flex; flex-direction:column; gap:4px; min-width:120px;">
                    @foreach(['A','B','C','D','E'] as $index => $sec)
                        <button class="dash-seccion-btn {{ $index == 0 ? 'active' : '' }}" data-seccion="{{ $sec }}" onclick="filterDashboard(this, 'seccion')">
                            <i class="fas fa-users text-accent" style="margin-right:8px;"></i> Sección {{ $sec }}
                        </button>
                    @endforeach
                </div>
                
                <!-- Table Area -->
                <div style="flex:1; background:var(--bg-card); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
                    <div style="padding:12px 16px; background:var(--bg-input); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                        <h4 style="margin:0; font-size:0.9rem; color:var(--text-primary);">LISTA DE LOS ALUMNOS QUE ASISTIERON</h4>
                        <div style="display:flex; gap:8px;">
                            <button onclick="exportCurrentView('estudiante', 'pdf')" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:0.75rem;"><i class="fas fa-file-pdf"></i> PDF de la lista</button>
                            <button onclick="exportCurrentView('estudiante', 'excel')" class="btn btn-success btn-sm" style="background:#107c41;border-color:#107c41; padding:4px 8px; font-size:0.75rem;"><i class="fas fa-file-excel"></i> Excel de la lista</button>
                        </div>
                    </div>
                    <div class="table-wrapper nested-table">
                        <table id="estudiantesDashboardTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th style="width:40px;">Foto</th>
                                    <th>Código / Nombre</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                    <th>Confianza</th>
                                    @if(auth()->user()->isAdmin())<th style="width:100px;">Acción</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asistenciasEstudiantes as $a)
                                    @include('asistencia.partials.row', ['a' => $a])
                                @endforeach
                                <tr id="emptyDashboardRow" style="display:none;">
                                    <td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" style="text-align:center; padding:30px; color:var(--text-muted);">
                                        <i class="fas fa-folder-open" style="font-size:2rem; margin-bottom:12px; opacity:0.5; display:block;"></i>
                                        No hay alumnos para los filtros seleccionados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Turnos Right Tabs -->
                <div class="dashboard-turnos-tabs" style="display:flex; flex-direction:column; gap:8px; min-width:140px;">
                    <button class="dash-turno-btn active" data-turno="mañana" onclick="filterDashboard(this, 'turno')">
                        <i class="fas fa-sun text-warning" style="margin-right:8px;"></i> Turno Mañana
                    </button>
                    <button class="dash-turno-btn" data-turno="tarde" onclick="filterDashboard(this, 'turno')">
                        <i class="fas fa-moon text-info" style="margin-right:8px;"></i> Turno Tarde
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Pestaña: Docentes --}}
<div id="tab-docentes" class="tab-content">
    @if($asistenciasDocentes->isEmpty())
        <div class="card" style="text-align:center; padding:30px; color:var(--text-muted);">
            <i class="fas fa-chalkboard-teacher" style="font-size:2rem; margin-bottom:12px; opacity:0.5;"></i>
            <h3 style="font-size:1rem;">No hay docentes</h3>
        </div>
    @else
        <div class="dashboard-docentes-container" style="display:flex; gap:16px; flex-wrap:wrap;">
            <!-- Areas Left Tabs -->
            <div class="dashboard-areas-tabs" style="display:flex; flex-direction:column; gap:4px; min-width:160px;">
                @foreach($docentesAgrupados->keys() as $index => $areaNombre)
                    <button class="dash-area-btn {{ $index == 0 ? 'active' : '' }}" data-area="{{ $areaNombre }}" onclick="filterDocentesDashboard(this, 'area')">
                        <i class="fas fa-briefcase text-info" style="margin-right:8px;"></i> {{ $areaNombre }}
                    </button>
                @endforeach
            </div>
            
            <!-- Table Area -->
            <div style="flex:1; background:var(--bg-card); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
                <div style="padding:12px 16px; background:var(--bg-input); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                    <h4 style="margin:0; font-size:0.9rem; color:var(--text-primary);">LISTA DE DOCENTES QUE ASISTIERON</h4>
                    <div style="display:flex; gap:8px;">
                        <button onclick="exportCurrentView('docente', 'pdf')" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:0.75rem;"><i class="fas fa-file-pdf"></i> PDF de la lista</button>
                        <button onclick="exportCurrentView('docente', 'excel')" class="btn btn-success btn-sm" style="background:#107c41;border-color:#107c41; padding:4px 8px; font-size:0.75rem;"><i class="fas fa-file-excel"></i> Excel de la lista</button>
                    </div>
                </div>
                <div class="table-wrapper nested-table">
                    <table id="docentesDashboardTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width:40px;">Foto</th>
                                <th>Código / Nombre</th>
                                <th>Turno</th>
                                <th>Hora</th>
                                <th>Estado</th>
                                <th>Confianza</th>
                                @if(auth()->user()->isAdmin())<th style="width:100px;">Acción</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($asistenciasDocentes as $a)
                                @include('asistencia.partials.row_docente', ['a' => $a])
                            @endforeach
                            <tr id="emptyDocentesDashboardRow" style="display:none;">
                                <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" style="text-align:center; padding:30px; color:var(--text-muted);">
                                    <i class="fas fa-folder-open" style="font-size:2rem; margin-bottom:12px; opacity:0.5; display:block;"></i>
                                    No hay docentes para los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Turnos Right Tabs -->
            <div class="dashboard-docentes-turnos-tabs" style="display:flex; flex-direction:column; gap:8px; min-width:140px;">
                <button class="dash-turno-doc-btn active" data-turno="mañana" onclick="filterDocentesDashboard(this, 'turno')">
                    <i class="fas fa-sun text-warning" style="margin-right:8px;"></i> Turno Mañana
                </button>
                <button class="dash-turno-doc-btn" data-turno="tarde" onclick="filterDocentesDashboard(this, 'turno')">
                    <i class="fas fa-moon text-info" style="margin-right:8px;"></i> Turno Tarde
                </button>
            </div>
        </div>
    @endif
</div>

{{-- Modal de Historial Individual --}}
<div id="historyModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(5px);">
    <div style="background:var(--bg-card);padding:24px;border-radius:12px;max-width:700px;width:95%;box-shadow:0 10px 40px rgba(0,0,0,0.6);border:1px solid var(--border-light); max-height:85vh; display:flex; flex-direction:column;">
        
        <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">
            <div style="display:flex; gap:16px; align-items:center;">
                <img id="histFoto" src="" style="width:60px; height:60px; border-radius:12px; object-fit:cover; border:2px solid var(--border);">
                <div>
                    <h3 id="histNombre" style="margin:0;font-size:1.1rem;color:var(--text-primary);"></h3>
                    <div style="font-size:0.85rem; color:var(--accent); font-family:monospace;" id="histCodigo"></div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;" id="histCargo"></div>
                </div>
            </div>
            <button onclick="document.getElementById('historyModal').style.display='none'" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;transition:color 0.2s;">&times;</button>
        </div>

        <div style="overflow-y:auto; flex:1; padding-right:8px;" id="histContainer">
            <div style="text-align:center; padding:30px; color:var(--text-muted);" id="histLoader">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; margin-bottom:12px;"></i>
                <p>Cargando historial de asistencias...</p>
            </div>
            
            <div id="histContent" style="display:none;">
                <div class="card p-0" style="background:transparent;">
                    <div class="history-row header">
                        <div>Fecha</div>
                        <div>Turno</div>
                        <div>Hora</div>
                        <div>Estado</div>
                    </div>
                    <div id="histRows"></div>
                </div>
            </div>
        </div>
        
        <div style="border-top:1px solid var(--border); padding-top:16px; margin-top:16px; text-align:right;">
            <span class="badge badge-accent" id="histTotal" style="font-size:0.85rem; padding:6px 12px;">Total: 0</span>
        </div>
    </div>
</div>

{{-- Modal Evidencia --}}
<div id="evidenceModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(5px);">
    <div style="background:var(--bg-card);padding:24px;border-radius:12px;max-width:900px;width:95%;box-shadow:0 10px 40px rgba(0,0,0,0.6);border:1px solid var(--border-light);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px;">
            <h3 style="margin:0;font-size:1.2rem;color:var(--text-primary);"><i class="fas fa-camera text-primary"></i> Evidencia: <span id="evName" style="color:var(--accent);"></span></h3>
            <button onclick="document.getElementById('evidenceModal').style.display='none'" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;">&times;</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;">
            <div style="background:var(--bg-input);padding:16px;border-radius:8px;">
                <strong style="display:flex;align-items:center;gap:6px;margin-bottom:12px;color:var(--text-secondary);font-size:0.9rem;"><i class="fas fa-user-circle"></i> Rostro Capturado</strong>
                <img id="evCrop" src="" style="width:100%;border-radius:8px;border:2px solid var(--border);object-fit:cover;aspect-ratio:1/1;">
            </div>
            <div style="background:var(--bg-input);padding:16px;border-radius:8px;">
                <strong style="display:flex;align-items:center;gap:6px;margin-bottom:12px;color:var(--text-secondary);font-size:0.9rem;"><i class="fas fa-panorama"></i> Toma Panorámica</strong>
                <img id="evFull" src="" style="width:100%;border-radius:8px;border:2px solid var(--border);object-fit:cover;aspect-ratio:16/9;">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentGrado = '1';
let currentSeccion = 'A';
let currentTurno = 'mañana';

let currentDocArea = '';
let currentDocTurno = 'mañana';

document.addEventListener('DOMContentLoaded', () => {
    updateDashboardFilter();
    
    const firstAreaBtn = document.querySelector('.dash-area-btn');
    if (firstAreaBtn) {
        currentDocArea = firstAreaBtn.dataset.area;
    }
    updateDocentesDashboardFilter();
});

function filterDashboard(btn, type) {
    if (type === 'grado') {
        document.querySelectorAll('.dash-grado-btn').forEach(b => b.classList.remove('active'));
        currentGrado = btn.dataset.grado;
    } else if (type === 'seccion') {
        document.querySelectorAll('.dash-seccion-btn').forEach(b => b.classList.remove('active'));
        currentSeccion = btn.dataset.seccion;
    } else if (type === 'turno') {
        document.querySelectorAll('.dash-turno-btn').forEach(b => b.classList.remove('active'));
        currentTurno = btn.dataset.turno;
    }
    btn.classList.add('active');
    updateDashboardFilter();
}

function updateDashboardFilter() {
    let visibleCount = 0;
    document.querySelectorAll('.estudiante-row').forEach(row => {
        if (row.dataset.grado === currentGrado && 
            row.dataset.seccion === currentSeccion && 
            row.dataset.turno === currentTurno) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const emptyRow = document.getElementById('emptyDashboardRow');
    if (emptyRow) {
        emptyRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}

function exportCurrentView(cargo, formato) {
    const params = new URLSearchParams({
        fecha: document.getElementById('fecha_picker').value,
        formato: formato,
        cargo: cargo
    });
    
    if (cargo === 'estudiante') {
        params.append('grado', currentGrado);
        params.append('seccion', currentSeccion);
        params.append('turno', currentTurno);
    } else {
        params.append('area', currentDocArea);
        params.append('turno', currentDocTurno);
    }
    
    window.location.href = '{{ route("asistencia.exportar") }}?' + params.toString();
}

function filterDocentesDashboard(btn, type) {
    if (type === 'area') {
        document.querySelectorAll('.dash-area-btn').forEach(b => b.classList.remove('active'));
        currentDocArea = btn.dataset.area;
    } else if (type === 'turno') {
        document.querySelectorAll('.dash-turno-doc-btn').forEach(b => b.classList.remove('active'));
        currentDocTurno = btn.dataset.turno;
    }
    btn.classList.add('active');
    updateDocentesDashboardFilter();
}

function updateDocentesDashboardFilter() {
    if (!currentDocArea) return;
    let visibleCount = 0;
    document.querySelectorAll('.docente-row').forEach(row => {
        if (row.dataset.area === currentDocArea && 
            row.dataset.turno === currentDocTurno) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const emptyRow = document.getElementById('emptyDocentesDashboardRow');
    if (emptyRow) {
        emptyRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}

function changeDate(offset) {
    const picker = document.getElementById('fecha_picker');
    const currentDate = new Date(picker.value);
    currentDate.setDate(currentDate.getDate() + offset);
    const yyyy = currentDate.getFullYear();
    const mm = String(currentDate.getMonth() + 1).padStart(2, '0');
    const dd = String(currentDate.getDate()).padStart(2, '0');
    picker.value = `${yyyy}-${mm}-${dd}`;
    document.getElementById('dateForm').submit();
}

function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.custom-tab').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

function toggleAccordion(button) {
    const item = button.closest('.accordion-item');
    item.classList.toggle('active');
}

function openEvidenceModal(cropSrc, fullSrc, name) {
    document.getElementById('evName').textContent = name;
    document.getElementById('evCrop').src = cropSrc;
    document.getElementById('evFull').src = fullSrc;
    document.getElementById('evidenceModal').style.display = 'flex';
}

async function cambiarEstado(id, estado) {
    const res = await fetch(`/api/asistencia/${id}/estado`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
        body: JSON.stringify({ estado })
    });
    const data = await res.json();
    if (data.success) {
        const badge = event.target.closest('tr').querySelector('.badge');
        if (badge) {
            badge.className = 'badge badge-' + (estado === 'puntual' ? 'success' : estado === 'tardanza' ? 'warning' : 'danger');
            badge.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
        }
    }
}

async function openHistoryModal(personaId) {
    // Show modal and loader
    document.getElementById('historyModal').style.display = 'flex';
    document.getElementById('histLoader').style.display = 'block';
    document.getElementById('histContent').style.display = 'none';
    document.getElementById('histNombre').textContent = '';
    document.getElementById('histCodigo').textContent = '';
    document.getElementById('histCargo').textContent = '';
    document.getElementById('histTotal').textContent = 'Total: 0';
    document.getElementById('histFoto').src = '/images/default-avatar.svg';
    document.getElementById('histRows').innerHTML = '';

    try {
        const res = await fetch(`/api/personas/${personaId}/asistencias`);
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('histNombre').textContent = data.persona.nombre;
            document.getElementById('histCodigo').textContent = data.persona.codigo;
            document.getElementById('histCargo').textContent = data.persona.cargo;
            document.getElementById('histTotal').textContent = 'Total Registros: ' + data.persona.total_asistencias;
            if(data.persona.foto) document.getElementById('histFoto').src = data.persona.foto;

            const rowsHtml = data.asistencias.map(a => {
                const color = a.estado === 'puntual' ? 'var(--success)' : (a.estado === 'tardanza' ? 'var(--warning)' : 'var(--danger)');
                const turnoStr = a.turno ? a.turno.charAt(0).toUpperCase() + a.turno.slice(1) : '-';
                const estadoStr = a.estado.charAt(0).toUpperCase() + a.estado.slice(1);
                
                return `
                    <div class="history-row" style="background:var(--bg-card);">
                        <div style="font-weight:600; color:var(--text-primary);">${a.fecha_format}</div>
                        <div style="color:var(--text-secondary);"><i class="fas fa-sun text-warning" style="font-size:0.7rem;"></i> ${turnoStr}</div>
                        <div style="color:var(--text-secondary); font-family:monospace;">${a.hora}</div>
                        <div><span class="badge" style="background:transparent; border:1px solid ${color}; color:${color}; font-size:0.7rem; padding:2px 6px;">${estadoStr}</span></div>
                    </div>
                `;
            }).join('');

            document.getElementById('histRows').innerHTML = rowsHtml || '<div style="padding:20px; text-align:center; color:var(--text-muted);">Sin registros históricos</div>';
            
            document.getElementById('histLoader').style.display = 'none';
            document.getElementById('histContent').style.display = 'block';
        }
    } catch (e) {
        document.getElementById('histLoader').innerHTML = '<div style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Error al cargar datos.</div>';
    }
}
</script>
@endpush
@endsection
