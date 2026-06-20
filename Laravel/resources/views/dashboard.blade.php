@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    {{-- Horario activo --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock"></i> Horario Activo</h3>
        </div>
        @if($horario)
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
            <div style="text-align:center;padding:16px;background:var(--success-bg);border-radius:var(--radius-sm);">
                <div style="font-size:1.3rem;font-weight:700;color:var(--success);">{{ $horario->hora_entrada }}</div>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px;">Puntual</div>
            </div>
            <div style="text-align:center;padding:16px;background:var(--warning-bg);border-radius:var(--radius-sm);">
                <div style="font-size:1.3rem;font-weight:700;color:var(--warning);">{{ $horario->hora_tardanza }}</div>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px;">Tardanza</div>
            </div>
            <div style="text-align:center;padding:16px;background:var(--danger-bg);border-radius:var(--radius-sm);">
                <div style="font-size:1.3rem;font-weight:700;color:var(--danger);">{{ $horario->hora_falta }}</div>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px;">Falta</div>
            </div>
        </div>
        @else
        <p style="color:var(--text-muted);">No hay horario configurado.</p>
        @endif
    </div>

    {{-- Últimas asistencias --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history"></i> Últimas Asistencias</h3>
        </div>
        @if($ultimasAsistencias->count() > 0)
        <div style="display:flex;flex-direction:column;gap:8px;max-height:260px;overflow-y:auto;">
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
</div>
@endsection
