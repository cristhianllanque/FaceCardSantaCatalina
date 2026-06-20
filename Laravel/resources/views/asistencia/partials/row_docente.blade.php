<tr>
    <td>
        @if($a->foto_captura)
            @php
                $fotoCompleta = $a->foto_completa ? asset('storage/' . $a->foto_completa) : asset('storage/' . $a->foto_captura);
            @endphp
            <img src="{{ asset('storage/' . $a->foto_captura) }}" 
                 style="width:40px;height:40px;border-radius:4px;object-fit:cover;border:2px solid var(--border-light); cursor:pointer;"
                 onclick="openEvidenceModal('{{ asset('storage/' . $a->foto_captura) }}', '{{ $fotoCompleta }}', '{{ $a->persona->nombre }}')"
                 title="Ver evidencia fotográfica">
        @else
            <img src="{{ $a->persona->foto_url }}" style="width:40px;height:40px;border-radius:6px;object-fit:cover; opacity: 0.5;" onerror="this.src='/images/default-avatar.svg'">
        @endif
    </td>
    <td>
        <div style="font-weight:600; color:var(--accent); font-family:monospace;">{{ $a->persona->codigo }}</div>
        <div style="font-size:0.9rem; display:flex; align-items:center; gap:8px;">
            {{ $a->persona->nombre }}
            <button class="btn-icon" style="background:var(--bg-input); border:1px solid var(--border); color:var(--text-secondary); width:24px; height:24px; border-radius:4px; font-size:0.7rem; cursor:pointer;" onclick="openHistoryModal({{ $a->persona->id }})" title="Ver Historial Individual">
                <i class="fas fa-history"></i>
            </button>
        </div>
    </td>
    <td>
        @if($a->turno)
            <span class="badge badge-secondary" style="background:#444;"><i class="fas fa-sun"></i> {{ ucfirst($a->turno) }}</span>
        @else
            <span style="color:var(--text-muted);font-size:0.85rem;">-</span>
        @endif
    </td>
    <td><i class="far fa-clock" style="color:var(--text-muted);"></i> {{ $a->hora_ingreso }}</td>
    <td>
        <span class="badge badge-{{ $a->estado === 'puntual' ? 'success' : ($a->estado === 'tardanza' ? 'warning' : 'danger') }}">
            {{ ucfirst($a->estado) }}
        </span>
    </td>
    <td>
        @if($a->confianza)
        <span style="font-size:0.85rem; color:{{ $a->confianza > 0.6 ? 'var(--success)' : 'var(--warning)' }};">
            <i class="fas fa-brain"></i> {{ round($a->confianza * 100, 1) }}%
        </span>
        @else
        <span style="font-size:0.85rem; color:var(--text-muted);">-</span>
        @endif
    </td>
    @if(auth()->user()->isAdmin())
    <td>
        <select class="form-control" style="width:100%;padding:4px 8px;font-size:0.8rem;" onchange="cambiarEstado({{ $a->id }}, this.value)">
            <option value="puntual" {{ $a->estado === 'puntual' ? 'selected' : '' }}>Puntual</option>
            <option value="tardanza" {{ $a->estado === 'tardanza' ? 'selected' : '' }}>Tardanza</option>
            <option value="falta" {{ $a->estado === 'falta' ? 'selected' : '' }}>Falta</option>
        </select>
    </td>
    @endif
</tr>
