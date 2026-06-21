@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ═══ Tarjetas de Estadísticas Principales ═══ --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-users"></i></div>
        <div><div class="stat-value">{{ $totalPersonas }}</div><div class="stat-label">Total Personas</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-user-graduate"></i></div>
        <div><div class="stat-value">{{ $totalEstudiantes }}</div><div class="stat-label">Estudiantes</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-chalkboard-teacher"></i></div>
        <div><div class="stat-value">{{ $totalDocentes }}</div><div class="stat-label">Docentes</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-brain"></i></div>
        <div><div class="stat-value">{{ $totalConEmbedding }}</div><div class="stat-label">Con Reconocimiento</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-clipboard-check"></i></div>
        <div><div class="stat-value">{{ $asistenciasHoy }}</div><div class="stat-label">Asistencias Hoy</div></div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
    
    {{-- ═══ Horarios por Turno ═══ --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock"></i> Horarios por Turno</h3>
        </div>

        {{-- Turno Mañana --}}
        <div style="margin-bottom:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <i class="fas fa-sun" style="color:var(--warning); font-size:1.1rem;"></i>
                <span style="font-weight:700; font-size:0.9rem;">Turno Mañana</span>
                <span class="badge badge-secondary" style="font-size:0.7rem; margin-left:auto;">{{ $estManana }} est. · {{ $docManana }} doc.</span>
            </div>
            @if($horarioManana)
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                <div style="text-align:center; padding:12px; background:var(--success-bg); border-radius:var(--radius-sm);">
                    <div style="font-size:1.2rem; font-weight:700; color:var(--success);">{{ $horarioManana->hora_entrada }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary); margin-top:4px;">Puntual</div>
                </div>
                <div style="text-align:center; padding:12px; background:var(--warning-bg); border-radius:var(--radius-sm);">
                    <div style="font-size:1.2rem; font-weight:700; color:var(--warning);">{{ $horarioManana->hora_tardanza }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary); margin-top:4px;">Tardanza</div>
                </div>
                <div style="text-align:center; padding:12px; background:var(--danger-bg); border-radius:var(--radius-sm);">
                    <div style="font-size:1.2rem; font-weight:700; color:var(--danger);">{{ $horarioManana->hora_falta }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary); margin-top:4px;">Falta</div>
                </div>
            </div>
            @else
            <p style="color:var(--text-muted); font-size:0.85rem;">No configurado.</p>
            @endif
        </div>

        {{-- Turno Tarde --}}
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <i class="fas fa-moon" style="color:var(--info); font-size:1.1rem;"></i>
                <span style="font-weight:700; font-size:0.9rem;">Turno Tarde</span>
                <span class="badge badge-secondary" style="font-size:0.7rem; margin-left:auto;">{{ $estTarde }} est. · {{ $docTarde }} doc.</span>
            </div>
            @if($horarioTarde)
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                <div style="text-align:center; padding:12px; background:var(--success-bg); border-radius:var(--radius-sm);">
                    <div style="font-size:1.2rem; font-weight:700; color:var(--success);">{{ $horarioTarde->hora_entrada }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary); margin-top:4px;">Puntual</div>
                </div>
                <div style="text-align:center; padding:12px; background:var(--warning-bg); border-radius:var(--radius-sm);">
                    <div style="font-size:1.2rem; font-weight:700; color:var(--warning);">{{ $horarioTarde->hora_tardanza }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary); margin-top:4px;">Tardanza</div>
                </div>
                <div style="text-align:center; padding:12px; background:var(--danger-bg); border-radius:var(--radius-sm);">
                    <div style="font-size:1.2rem; font-weight:700; color:var(--danger);">{{ $horarioTarde->hora_falta }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary); margin-top:4px;">Falta</div>
                </div>
            </div>
            @else
            <p style="color:var(--text-muted); font-size:0.85rem;">No configurado.</p>
            @endif
        </div>
    </div>

    {{-- ═══ Resumen de Asistencia Hoy ═══ --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Resumen de Asistencia Hoy</h3>
        </div>

        {{-- Por Estado --}}
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:16px;">
            <div style="text-align:center; padding:16px; background:var(--success-bg); border-radius:var(--radius-sm);">
                <div style="font-size:1.8rem; font-weight:700; color:var(--success);">{{ $puntualesHoy }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-check-circle"></i> Puntuales</div>
            </div>
            <div style="text-align:center; padding:16px; background:var(--warning-bg); border-radius:var(--radius-sm);">
                <div style="font-size:1.8rem; font-weight:700; color:var(--warning);">{{ $tardanzasHoy }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-exclamation-circle"></i> Tardanzas</div>
            </div>
            <div style="text-align:center; padding:16px; background:var(--danger-bg); border-radius:var(--radius-sm);">
                <div style="font-size:1.8rem; font-weight:700; color:var(--danger);">{{ $faltasHoy }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-times-circle"></i> Faltas</div>
            </div>
        </div>

        {{-- Por Turno --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:var(--bg-input); border-radius:var(--radius-sm); border-left:3px solid var(--warning);">
                <i class="fas fa-sun" style="font-size:1.3rem; color:var(--warning);"></i>
                <div>
                    <div style="font-size:1.2rem; font-weight:700;">{{ $asistHoyManana }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary);">Asistencias Turno Mañana</div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:var(--bg-input); border-radius:var(--radius-sm); border-left:3px solid var(--info);">
                <i class="fas fa-moon" style="font-size:1.3rem; color:var(--info);"></i>
                <div>
                    <div style="font-size:1.2rem; font-weight:700;">{{ $asistHoyTarde }}</div>
                    <div style="font-size:0.72rem; color:var(--text-secondary);">Asistencias Turno Tarde</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Últimas Asistencias ═══ --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> Últimas Asistencias</h3>
    </div>
    @if($ultimasAsistencias->count() > 0)
    <div style="display:flex;flex-direction:column;gap:8px;max-height:300px;overflow-y:auto;">
        @foreach($ultimasAsistencias as $a)
        <div class="attendance-item">
            <img src="{{ $a->persona->foto_url }}" alt="{{ $a->persona->nombre }}" onerror="this.src='/images/default-avatar.svg'">
            <div class="attendance-item-info">
                <div class="attendance-item-name">{{ $a->persona->nombre }}</div>
                <div class="attendance-item-time">{{ $a->hora_ingreso }} — {{ $a->fecha->format('d/m/Y') }}</div>
            </div>
            <span class="badge badge-{{ $a->estado === 'puntual' ? 'success' : ($a->estado === 'tardanza' ? 'warning' : 'danger') }}">
                {{ ucfirst($a->estado) }}
            </span>
        </div>
        @endforeach
    </div>
    @else
    <p style="color:var(--text-muted);text-align:center;padding:20px;">No hay asistencias hoy.</p>
    @endif
</div>
@endsection
