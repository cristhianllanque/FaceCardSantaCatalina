@extends('layouts.app')
@section('title', 'Asistencia en Vivo')
@section('page-title', 'Asistencia en Tiempo Real')

@section('content')
<div class="live-layout">
    {{-- Panel de cámara --}}
    <div class="live-feed">
        <div class="card" style="padding:16px;">
            <div class="flex items-center justify-between mb-1">
                <h3 class="card-title" style="margin:0;"><i class="fas fa-video"></i> Cámara de Reconocimiento</h3>
                <div class="flex gap-1" style="align-items:center;">
                    <select id="liveCameraSelect" class="form-control" style="background:#2b2b40; color:white; border:none; padding:4px 8px; border-radius:4px; max-width:200px; display:none; margin-right:8px;"></select>
                    <button class="btn btn-success" id="btnStart" onclick="startAttendance()">
                        <i class="fas fa-play"></i> Comenzar Asistencia
                    </button>
                    <button class="btn btn-danger hidden" id="btnStop" onclick="stopAttendance()">
                        <i class="fas fa-stop"></i> Detener
                    </button>
                </div>
            </div>
            <div class="camera-container" id="liveCamera" style="position: relative;">
                <video id="liveVideo" autoplay playsinline style="display:none; width: 100%; border-radius: 8px;"></video>
                <canvas id="liveCanvas" style="display:none;"></canvas>
                <canvas id="overlayCanvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none; display:none;"></canvas>
                <div class="camera-overlay" id="livePlaceholder">
                    <div style="text-align:center;color:var(--text-muted);">
                        <i class="fas fa-shield-halved" style="font-size:3rem;margin-bottom:16px;display:block;opacity:0.3;"></i>
                        <p style="font-size:1.1rem;font-weight:600;">Asistencia en Tiempo Real</p>
                        <p style="font-size:0.85rem;margin-top:8px;">Presione "Comenzar Asistencia" para activar la cámara</p>
                        <p style="font-size:0.75rem;margin-top:4px;">El sistema detectará rostros automáticamente</p>
                    </div>
                </div>
                <div class="camera-overlay" id="liveGuide" style="display:none;">
                    <div class="camera-guide"></div>
                </div>
                <div class="camera-status" id="liveStatus" style="display:none;">
                    <div class="dot" id="liveDot"></div>
                    <span id="liveStatusText">Iniciando...</span>
                </div>
            </div>

            {{-- Info de sesión --}}
            <div style="margin-top:12px;display:flex;gap:16px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;font-size:0.82rem;">
                    <i class="fas fa-calendar" style="color:var(--accent);"></i>
                    <span>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.82rem;">
                    <i class="fas fa-fingerprint" style="color:var(--accent);"></i>
                    <span>Sesión: <code style="color:var(--warning);font-size:0.75rem;">{{ $sesionId }}</code></span>
                </div>
                @if($horario)
                <div style="display:flex;align-items:center;gap:6px;font-size:0.82rem;">
                    <i class="fas fa-clock" style="color:var(--success);"></i>
                    <span>Puntual: {{ $horario->hora_entrada }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.82rem;">
                    <i class="fas fa-clock" style="color:var(--warning);"></i>
                    <span>Tardanza: {{ $horario->hora_tardanza }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.82rem;">
                    <i class="fas fa-clock" style="color:var(--danger);"></i>
                    <span>Falta: {{ $horario->hora_falta }}</span>
                </div>
                @endif
            </div>

            {{-- Último reconocimiento --}}
            <div id="lastRecognition" class="hidden" style="margin-top:12px;padding:16px;background:var(--success-bg);border:1px solid rgba(34,197,94,0.3);border-radius:var(--radius-sm);animation:slideIn 0.3s ease;">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle" style="font-size:1.5rem;color:var(--success);"></i>
                    <div>
                        <div style="font-weight:600;" id="lastRecName">-</div>
                        <div style="font-size:0.8rem;color:var(--text-secondary);" id="lastRecInfo">-</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reconocimiento simulado (demo sin Python API) --}}
        <div class="card" style="padding:16px;">
            <h3 class="card-title mb-1"><i class="fas fa-keyboard"></i> Registro Manual (Demo)</h3>
            <p style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:12px;">Ingrese el código de la persona para registrar asistencia manualmente.</p>
            <div style="display:flex;gap:8px;">
                <input type="text" id="manualCode" class="form-control" placeholder="Código de persona..." style="flex:1;">
                <button class="btn btn-primary" onclick="manualRegister()"><i class="fas fa-user-check"></i> Registrar</button>
            </div>
            <div id="manualMsg" class="hidden" style="margin-top:8px;font-size:0.85rem;"></div>
        </div>
    </div>

    {{-- Lista de asistencia --}}
    <div class="live-sidebar">
        <div class="card" style="padding:16px;display:flex;flex-direction:column;height:100%;overflow:hidden;">
            <div class="flex items-center justify-between mb-1" style="flex-wrap:wrap; gap:8px;">
                <h3 class="card-title" style="margin:0;"><i class="fas fa-list-check"></i> Registrados Hoy</h3>
                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="filterList" class="form-control" style="background:#2b2b40; color:white; border:none; padding:2px 6px; border-radius:4px; font-size:0.8rem;" onchange="filterAttendance()">
                        <option value="todos">Todos</option>
                        <option value="estudiante">Estudiantes</option>
                        <option value="docente">Docentes</option>
                    </select>
                    <span class="badge badge-accent" id="attendanceCount">{{ $asistenciasHoy->count() }}</span>
                </div>
            </div>

            <div class="attendance-list" id="attendanceList">
                @foreach($asistenciasHoy as $a)
                <div class="attendance-item" data-id="{{ $a->id }}" data-cargo="{{ strtolower($a->persona->cargo) }}">
                    <img src="{{ $a->persona->foto_url }}" alt="" onerror="this.src='/images/default-avatar.svg'" style="width:44px;height:44px;border-radius:8px;object-fit:cover;">
                    <div class="attendance-item-info">
                        <div class="attendance-item-name">{{ $a->persona->nombre }}</div>
                        <div class="attendance-item-time">
                            {{ $a->persona->codigo }} · {{ $a->hora_ingreso }}
                        </div>
                        <div class="attendance-item-confidence">Confianza: {{ round($a->confianza * 100, 1) }}%</div>
                    </div>
                    <span class="badge badge-{{ $a->estado === 'puntual' ? 'success' : ($a->estado === 'tardanza' ? 'warning' : 'danger') }}">
                        {{ ucfirst($a->estado) }}
                    </span>
                </div>
                @endforeach

                @if($asistenciasHoy->count() === 0)
                <div id="emptyMsg" style="text-align:center;color:var(--text-muted);padding:40px 0;">
                    <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:8px;display:block;"></i>
                    <p>Sin registros aún</p>
                    <p style="font-size:0.75rem;">Los reconocimientos aparecerán aquí</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const sesionId = '{{ $sesionId }}';
const csrfToken = window.csrfToken;
const pythonApiUrl = 'http://127.0.0.1:8889';
let liveStream = null;
let isRunning = false;
let registeredCodes = new Set();
let recognitionInterval = null;

// Cargar códigos ya registrados
@foreach($asistenciasHoy as $a)
registeredCodes.add('{{ $a->persona->codigo }}');
@endforeach

// Cargar cámaras
async function loadLiveCameras() {
    try {
        await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(d => d.kind === 'videoinput');
        const select = document.getElementById('liveCameraSelect');
        
        select.innerHTML = '';
        if (videoDevices.length > 0) {
            select.style.display = 'block';
            videoDevices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Cámara ${index + 1}`;
                select.appendChild(option);
            });
            select.addEventListener('change', () => {
                if (isRunning) {
                    stopAttendance();
                    setTimeout(startAttendance, 500);
                }
            });
        }
    } catch (e) {
        console.log("No se pudieron cargar las cámaras: ", e);
    }
}
loadLiveCameras();

async function startAttendance() {
    const video = document.getElementById('liveVideo');
    const select = document.getElementById('liveCameraSelect');
    try {
        const constraints = { video: { width: 1280, height: 720 } };
        if (select && select.value) {
            constraints.video.deviceId = { exact: select.value };
        } else {
            constraints.video.facingMode = 'user';
        }

        liveStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = liveStream;
        video.style.display = 'block';
        document.getElementById('overlayCanvas').style.display = 'block';
        document.getElementById('livePlaceholder').style.display = 'none';
        document.getElementById('liveGuide').style.display = 'flex';
        document.getElementById('liveStatus').style.display = 'flex';
        document.getElementById('liveDot').classList.add('active');
        document.getElementById('liveStatusText').textContent = 'Reconociendo...';
        document.getElementById('btnStart').classList.add('hidden');
        document.getElementById('btnStop').classList.remove('hidden');
        isRunning = true;
        
        // Esperar a que el video tenga dimensiones
        video.onloadedmetadata = () => {
            const overlay = document.getElementById('overlayCanvas');
            overlay.width = video.videoWidth;
            overlay.height = video.videoHeight;
        };
        
        // Empezar el polling para reconocimiento
        startRecognitionLoop();
    } catch (err) {
        alert('No se pudo acceder a la cámara: ' + err.message);
    }
}

function stopAttendance() {
    if (liveStream) {
        liveStream.getTracks().forEach(t => t.stop());
        liveStream = null;
    }
    if (recognitionInterval) {
        clearInterval(recognitionInterval);
        recognitionInterval = null;
    }
    document.getElementById('liveVideo').style.display = 'none';
    document.getElementById('overlayCanvas').style.display = 'none';
    document.getElementById('livePlaceholder').style.display = 'flex';
    document.getElementById('liveGuide').style.display = 'none';
    document.getElementById('liveStatus').style.display = 'none';
    document.getElementById('btnStart').classList.remove('hidden');
    document.getElementById('btnStop').classList.add('hidden');
    isRunning = false;
}

function startRecognitionLoop() {
    // Capturar frame cada 600ms (aprox 1.5 FPS) para no saturar la API
    recognitionInterval = setInterval(async () => {
        if (!isRunning) return;
        
        const video = document.getElementById('liveVideo');
        if (!video.videoWidth) return; // Aún no ha cargado
        
        const canvas = document.getElementById('liveCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        
        const base64Image = canvas.toDataURL('image/jpeg', 0.8);
        
        try {
            const response = await fetch(`${pythonApiUrl}/api/recognize`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: base64Image })
            });
            
            const result = await response.json();
            
            // Limpiar canvas overlay
            const overlay = document.getElementById('overlayCanvas');
            const ctxOverlay = overlay.getContext('2d');
            ctxOverlay.clearRect(0, 0, overlay.width, overlay.height);
            
            if (result.found && result.matches.length > 0) {
                // Flash effect for recognition
                const container = document.getElementById('liveCamera');
                container.style.boxShadow = '0 0 30px rgba(34,197,94,0.6)';
                setTimeout(() => container.style.boxShadow = '', 400);

                // Procesar coincidencias
                for (let match of result.matches) {
                    // Dibujar rectángulo
                    const [x1, y1, x2, y2] = match.bbox;
                    const w = x2 - x1;
                    const h = y2 - y1;
                    
                    const isRecognized = match.confianza >= 0.45;
                    const color = isRecognized ? '#00FF00' : '#FFA500'; // Verde o Naranja

                    ctxOverlay.strokeStyle = color;
                    ctxOverlay.lineWidth = 3;
                    ctxOverlay.strokeRect(x1, y1, w, h);
                    
                    // Dibujar nombre
                    ctxOverlay.fillStyle = color;
                    ctxOverlay.font = 'bold 22px Arial';
                    ctxOverlay.shadowColor = 'black';
                    ctxOverlay.shadowBlur = 4;
                    const percent = Math.round(match.confianza * 100);
                    const label = isRecognized ? match.nombre : "Desconocido";
                    ctxOverlay.fillText(`${label} (${percent}%)`, x1, y1 - 10);
                    ctxOverlay.shadowBlur = 0; // reset

                    // Solo registrar si supera umbral (0.45) y no fue registrado hoy
                    if (isRecognized && !registeredCodes.has(match.codigo)) {
                        let evidenciaCrop = match.foto_crop ? 'data:image/jpeg;base64,' + match.foto_crop : null;
                        await registerAttendance(match.codigo, match.confianza, evidenciaCrop, base64Image);
                    }
                }
            }
        } catch (error) {
            console.error("Error en reconocimiento:", error);
        }
    }, 600);
}

async function registerAttendance(codigo, confianza, cropBase64, fullBase64) {
    if (registeredCodes.has(codigo)) return;
    
    // Primero, buscar la persona en la DB de Laravel para obtener su ID
    const searchRes = await fetch('/api/personas/buscar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ codigo: codigo })
    });
    const searchData = await searchRes.json();

    if (!searchData.found) return;

    // Registrar asistencia
    const res = await fetch('/api/asistencia/registrar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            persona_id: searchData.persona.id,
            confianza: confianza,
            foto_captura: cropBase64 || fullBase64,
            foto_completa: fullBase64,
            sesion_id: sesionId
        })
    });
    const data = await res.json();

    if (data.success) {
        addAttendanceItem(data.asistencia);
        registeredCodes.add(codigo);
        showLastRecognition(data.asistencia.persona_nombre, data.asistencia.hora_ingreso + ' — ' + data.asistencia.estado);
    }
}

async function manualRegister() {
    const code = document.getElementById('manualCode').value.trim();
    const msgDiv = document.getElementById('manualMsg');
    if (!code) return;

    if (registeredCodes.has(code)) {
        msgDiv.className = ''; msgDiv.classList.remove('hidden');
        msgDiv.style.color = 'var(--warning)';
        msgDiv.textContent = '⚠️ Esta persona ya tiene asistencia hoy.';
        return;
    }

    // Buscar persona y registrar (similar al flujo manual original)
    const searchRes = await fetch('/api/personas/buscar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ codigo: code })
    });
    const searchData = await searchRes.json();

    if (!searchData.found) {
        msgDiv.className = ''; msgDiv.classList.remove('hidden');
        msgDiv.style.color = 'var(--danger)';
        msgDiv.textContent = '❌ No se encontró persona con ese código.';
        return;
    }

    const res = await fetch('/api/asistencia/registrar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            persona_id: searchData.persona.id,
            confianza: 1.0,
            sesion_id: sesionId
        })
    });
    const data = await res.json();

    if (data.success) {
        addAttendanceItem(data.asistencia);
        registeredCodes.add(code);
        document.getElementById('manualCode').value = '';
        msgDiv.className = ''; msgDiv.classList.remove('hidden');
        msgDiv.style.color = 'var(--success)';
        msgDiv.textContent = '✅ ' + data.message;

        showLastRecognition(data.asistencia.persona_nombre, data.asistencia.hora_ingreso + ' — ' + data.asistencia.estado);
    } else {
        msgDiv.className = ''; msgDiv.classList.remove('hidden');
        msgDiv.style.color = 'var(--warning)';
        msgDiv.textContent = '⚠️ ' + data.message;
    }

    setTimeout(() => msgDiv.classList.add('hidden'), 4000);
}

function addAttendanceItem(a) {
    const emptyMsg = document.getElementById('emptyMsg');
    if (emptyMsg) emptyMsg.remove();

    const list = document.getElementById('attendanceList');
    const badgeClass = a.estado === 'puntual' ? 'success' : a.estado === 'tardanza' ? 'warning' : 'danger';

    const html = `
        <div class="attendance-item" data-cargo="${a.persona_cargo ? a.persona_cargo.toLowerCase() : ''}" style="animation:slideIn 0.3s ease;">
            <img src="${a.persona_foto || '/images/default-avatar.svg'}" alt="" onerror="this.src='/images/default-avatar.svg'" style="width:44px;height:44px;border-radius:8px;object-fit:cover;">
            <div class="attendance-item-info">
                <div class="attendance-item-name">${a.persona_nombre}</div>
                <div class="attendance-item-time">${a.persona_codigo} · ${a.hora_ingreso}</div>
                <div class="attendance-item-confidence">Confianza: ${a.confianza}%</div>
            </div>
            <span class="badge badge-${badgeClass}">${a.estado.charAt(0).toUpperCase() + a.estado.slice(1)}</span>
        </div>
    `;
    list.insertAdjacentHTML('afterbegin', html);

    const count = document.getElementById('attendanceCount');
    count.textContent = parseInt(count.textContent) + 1;
    filterAttendance(); // Re-aplicar filtro
}

function filterAttendance() {
    const filter = document.getElementById('filterList').value;
    const items = document.querySelectorAll('.attendance-item');
    let visibleCount = 0;
    
    items.forEach(item => {
        const cargo = item.getAttribute('data-cargo');
        if (filter === 'todos' || cargo === filter) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    document.getElementById('attendanceCount').textContent = visibleCount;
}

function showLastRecognition(name, info) {
    const el = document.getElementById('lastRecognition');
    document.getElementById('lastRecName').textContent = name;
    document.getElementById('lastRecInfo').textContent = info;
    el.classList.remove('hidden');
    el.style.animation = 'none'; el.offsetHeight; el.style.animation = 'slideIn 0.3s ease';
    setTimeout(() => el.classList.add('hidden'), 5000);
}

// Enter key for manual register
document.getElementById('manualCode').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') manualRegister();
});
</script>
@endpush
@endsection
