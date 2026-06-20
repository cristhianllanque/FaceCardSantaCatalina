@extends('layouts.app')
@section('title', 'Nuevo Registro')
@section('page-title', 'Registrar Nueva Persona')

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    {{-- Formulario --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-plus"></i> Datos Personales</h3>
        </div>
        <form method="POST" action="{{ route('personas.store') }}" enctype="multipart/form-data" id="personaForm">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Código *</label>
                    <input type="text" name="codigo" class="form-control" value="{{ old('codigo') }}" placeholder="Ej: 202123376" required>
                    @error('codigo')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" value="{{ old('celular') }}" placeholder="987654321">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre completo *</label>
                <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" placeholder="Nombre y apellidos" required>
            </div>
            <div class="form-group">
                <label class="form-label">Cargo *</label>
                <select name="cargo" class="form-control" id="cargoSelect" required>
                    <option value="">Seleccionar...</option>
                    <option value="estudiante" {{ old('cargo') === 'estudiante' ? 'selected' : '' }}>Estudiante</option>
                    <option value="docente" {{ old('cargo') === 'docente' ? 'selected' : '' }}>Docente</option>
                </select>
            </div>
            <div class="form-group" id="areaGroup">
                <label class="form-label">Área</label>
                <select name="area" class="form-control">
                    <option value="">Seleccionar área...</option>
                    <option value="Ingeniería de Sistemas" {{ old('area') === 'Ingeniería de Sistemas' ? 'selected' : '' }}>Ingeniería de Sistemas</option>
                    <option value="Ingeniería Civil" {{ old('area') === 'Ingeniería Civil' ? 'selected' : '' }}>Ingeniería Civil</option>
                    <option value="Administración" {{ old('area') === 'Administración' ? 'selected' : '' }}>Administración</option>
                    <option value="Contabilidad" {{ old('area') === 'Contabilidad' ? 'selected' : '' }}>Contabilidad</option>
                    <option value="Educación" {{ old('area') === 'Educación' ? 'selected' : '' }}>Educación</option>
                    <option value="Derecho" {{ old('area') === 'Derecho' ? 'selected' : '' }}>Derecho</option>
                    <option value="Medicina" {{ old('area') === 'Medicina' ? 'selected' : '' }}>Medicina</option>
                </select>
            </div>
            <div id="estudianteFields" class="hidden">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Grado</label>
                        <select name="grado" class="form-control">
                            <option value="">Seleccionar...</option>
                            @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ old('grado') == $i ? 'selected' : '' }}>{{ $i }}°</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sección</label>
                        <select name="seccion" class="form-control">
                            <option value="">Seleccionar...</option>
                            @foreach(['A','B','C','D','E'] as $s)
                            <option value="{{ $s }}" {{ old('seccion') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Foto (archivo)</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
                <p class="form-hint">O usa la cámara del panel derecho →</p>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar Persona</button>
                <a href="{{ route('personas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    {{-- Panel de Cámara / Enrollment Facial --}}
    <div>
        <div class="card mb-2">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-camera"></i> Captura Facial</h3>
                <button class="btn btn-sm btn-secondary" id="btnToggleCamera" onclick="toggleCamera()">
                    <i class="fas fa-video"></i> Activar Cámara
                </button>
            </div>
            <div class="camera-container" id="cameraContainer" style="aspect-ratio:4/3;">
                <video id="cameraVideo" autoplay playsinline style="display:none;"></video>
                <canvas id="cameraCanvas" style="display:none;"></canvas>
                <div class="camera-overlay" id="cameraPlaceholder">
                    <div style="text-align:center;color:var(--text-muted);">
                        <i class="fas fa-video-slash" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
                        <p>Cámara desactivada</p>
                        <p style="font-size:0.78rem;">Haz clic en "Activar Cámara"</p>
                    </div>
                </div>
                <div class="camera-overlay" id="cameraGuide" style="display:none;">
                    <div class="camera-guide"></div>
                </div>
                <div class="camera-status" id="cameraStatus" style="display:none;">
                    <div class="dot" id="cameraDot"></div>
                    <span id="cameraStatusText">Preparando...</span>
                </div>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;">
                <button class="btn btn-success w-full" id="btnCapture" onclick="capturePhoto()" disabled>
                    <i class="fas fa-camera"></i> Capturar Foto
                </button>
            </div>
            <div id="capturedPreview" style="margin-top:12px;display:none;">
                <p class="form-label">Foto capturada:</p>
                <img id="capturedImg" style="width:100%;border-radius:var(--radius-sm);border:2px solid var(--success);">
            </div>
        </div>

        {{-- Instrucciones de enrollment --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-check"></i> Instrucciones</h3>
            </div>
            <div class="enrollment-steps">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-text"><strong>Posición frontal</strong><span>Mire directamente a la cámara con el rostro centrado</span></div>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-text"><strong>Buena iluminación</strong><span>Asegúrese de estar en un ambiente bien iluminado</span></div>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-text"><strong>Sin obstrucciones</strong><span>Retire lentes de sol, gorros o mascarillas</span></div>
                </div>
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-text"><strong>Capturar foto</strong><span>Presione el botón cuando esté listo</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let cameraStream = null;
let cameraActive = false;
let capturing = false;

const cargoSelect = document.getElementById('cargoSelect');
const estudianteFields = document.getElementById('estudianteFields');

cargoSelect.addEventListener('change', function() {
    estudianteFields.classList.toggle('hidden', this.value !== 'estudiante');
});
if (cargoSelect.value === 'estudiante') estudianteFields.classList.remove('hidden');

async function toggleCamera() {
    const video = document.getElementById('cameraVideo');
    const placeholder = document.getElementById('cameraPlaceholder');
    const guide = document.getElementById('cameraGuide');
    const status = document.getElementById('cameraStatus');
    const dot = document.getElementById('cameraDot');
    const statusText = document.getElementById('cameraStatusText');
    const btn = document.getElementById('btnToggleCamera');
    const btnCapture = document.getElementById('btnCapture');

    if (cameraActive) {
        if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
        video.style.display = 'none'; placeholder.style.display = 'flex';
        guide.style.display = 'none'; status.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-video"></i> Activar Cámara';
        btnCapture.disabled = true; cameraActive = false;
        return;
    }

    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: 'user' } });
        video.srcObject = cameraStream; video.style.display = 'block';
        placeholder.style.display = 'none'; guide.style.display = 'flex';
        status.style.display = 'flex'; dot.classList.add('active');
        statusText.textContent = 'Cámara activa';
        btn.innerHTML = '<i class="fas fa-video-slash"></i> Desactivar';
        btnCapture.disabled = false; cameraActive = true;
    } catch (err) {
        alert('No se pudo acceder a la cámara: ' + err.message);
    }
}

// Sobrescribimos el envío del formulario para interceptar la captura múltiple
document.getElementById('personaForm').addEventListener('submit', async function(e) {
    if (cameraActive && !capturing) {
        e.preventDefault(); // Detenemos el envío normal
        
        const codigo = document.querySelector('input[name="codigo"]').value;
        const nombre = document.querySelector('input[name="nombre"]').value;
        
        if (!codigo || !nombre) {
            alert("El código y el nombre son obligatorios para el reconocimiento facial.");
            return;
        }

        capturing = true;
        const btnSubmit = document.querySelector('button[type="submit"]');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Capturando rostros...';
        
        const statusText = document.getElementById('cameraStatusText');
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth; 
        canvas.height = video.videoHeight;

        let frames = [];
        let totalFrames = 20;

        // Capturar N frames cada 200ms
        for (let i = 0; i < totalFrames; i++) {
            statusText.textContent = `Capturando ${i+1}/${totalFrames}...`;
            ctx.drawImage(video, 0, 0);
            frames.push(canvas.toDataURL('image/jpeg', 0.9));
            
            // Flash effect visual
            const container = document.getElementById('cameraContainer');
            container.style.boxShadow = '0 0 30px rgba(99,102,241,0.6)';
            setTimeout(() => container.style.boxShadow = '', 100);
            
            await new Promise(r => setTimeout(r, 200)); // Esperar 200ms entre capturas
        }

        statusText.textContent = 'Procesando en IA...';
        
        try {
            // Enviar al backend Python (FastAPI)
            const response = await fetch('http://127.0.0.1:8889/api/enroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    codigo: codigo,
                    nombre: nombre,
                    images: frames
                })
            });
            
            const result = await response.json();
            
            if (response.ok) {
                // Mostrar preview del último frame
                document.getElementById('capturedImg').src = frames[frames.length - 1];
                document.getElementById('capturedPreview').style.display = 'block';

                // Añadir campo oculto con una foto para que Laravel la guarde de perfil
                let hiddenInput = document.getElementById('fotoBase64');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden'; hiddenInput.name = 'foto_base64'; hiddenInput.id = 'fotoBase64';
                    this.appendChild(hiddenInput);
                }
                hiddenInput.value = frames[frames.length - 1];

                // Ya podemos enviar el formulario a Laravel
                this.submit();
            } else {
                alert("Error en el reconocimiento: " + (result.detail || 'Error desconocido'));
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Registrar Persona';
                capturing = false;
            }
        } catch (error) {
            alert("No se pudo conectar con la API de Python. ¿Está corriendo uvicorn?");
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> Registrar Persona';
            capturing = false;
        }
    }
});

function capturePhoto() {
    // Para no romper la UI, el botón "Capturar" también disparará el envío del form
    document.getElementById('personaForm').requestSubmit();
}
</script>
@endpush
@endsection
