@extends('layouts.app')
@section('title', 'Historial de Asistencia')
@section('page-title', 'Historial de Asistencia')

@section('content')
<div class="flex items-center justify-between mb-2 flex-wrap gap-2">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="{{ $fecha }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Ver</button>
    </form>
    <a href="{{ route('asistencia.envivo') }}" class="btn btn-success"><i class="fas fa-video"></i> Asistencia en Vivo</a>
</div>

@if(auth()->user()->isAdmin() && $horario)
<div class="card mb-2">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock"></i> Configurar Horario</h3>
    </div>
    <form method="POST" action="{{ route('asistencia.guardarHorario') }}" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        @csrf
        <div class="form-group" style="margin:0;">
            <label class="form-label">Hora Entrada (Puntual)</label>
            <input type="time" name="hora_entrada" class="form-control" value="{{ $horario->hora_entrada }}">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Hora Tardanza</label>
            <input type="time" name="hora_tardanza" class="form-control" value="{{ $horario->hora_tardanza }}">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Hora Falta</label>
            <input type="time" name="hora_falta" class="form-control" value="{{ $horario->hora_falta }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Guardar</button>
    </form>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Asistencia del {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h3>
        <span class="badge badge-accent">{{ $asistencias->count() }} registros</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Foto</th><th>Código</th><th>Nombre</th><th>Hora</th><th>Estado</th><th>Confianza</th>
                    @if(auth()->user()->isAdmin())<th>Acción</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($asistencias as $a)
                <tr>
                    <td><img src="{{ $a->persona->foto_url }}" style="width:36px;height:36px;border-radius:6px;object-fit:cover;" onerror="this.src='/images/default-avatar.svg'"></td>
                    <td style="color:var(--accent);font-weight:500;">{{ $a->persona->codigo }}</td>
                    <td>{{ $a->persona->nombre }}</td>
                    <td>{{ $a->hora_ingreso }}</td>
                    <td>
                        <span class="badge badge-{{ $a->estado === 'puntual' ? 'success' : ($a->estado === 'tardanza' ? 'warning' : 'danger') }}">
                            {{ ucfirst($a->estado) }}
                        </span>
                    </td>
                    <td>{{ round($a->confianza * 100, 1) }}%</td>
                    @if(auth()->user()->isAdmin())
                    <td>
                        <select class="form-control" style="width:120px;padding:4px 8px;font-size:0.8rem;" onchange="cambiarEstado({{ $a->id }}, this.value)">
                            <option value="puntual" {{ $a->estado === 'puntual' ? 'selected' : '' }}>Puntual</option>
                            <option value="tardanza" {{ $a->estado === 'tardanza' ? 'selected' : '' }}>Tardanza</option>
                            <option value="falta" {{ $a->estado === 'falta' ? 'selected' : '' }}>Falta</option>
                        </select>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px;">No hay registros para esta fecha</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
async function cambiarEstado(id, estado) {
    const res = await fetch(`/api/asistencia/${id}/estado`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
        body: JSON.stringify({ estado })
    });
    const data = await res.json();
    if (data.success) {
        // Visual feedback
        const badge = event.target.closest('tr').querySelector('.badge');
        if (badge) {
            badge.className = 'badge badge-' + (estado === 'puntual' ? 'success' : estado === 'tardanza' ? 'warning' : 'danger');
            badge.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
        }
    }
}
</script>
@endpush
@endsection
