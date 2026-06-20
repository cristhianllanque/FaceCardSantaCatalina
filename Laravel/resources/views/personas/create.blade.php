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
            <div class="form-group hidden" id="areaGroup">
                <label class="form-label">Área</label>
                <select name="area" class="form-control">
                    <option value="">Seleccionar área/curso...</option>
                    <option value="Matemática" {{ old('area') === 'Matemática' ? 'selected' : '' }}>Matemática</option>
                    <option value="Comunicación" {{ old('area') === 'Comunicación' ? 'selected' : '' }}>Comunicación</option>
                    <option value="Ciencias Sociales" {{ old('area') === 'Ciencias Sociales' ? 'selected' : '' }}>Ciencias Sociales</option>
                    <option value="Ciencia y Tecnología" {{ old('area') === 'Ciencia y Tecnología' ? 'selected' : '' }}>Ciencia y Tecnología</option>
                    <option value="Inglés" {{ old('area') === 'Inglés' ? 'selected' : '' }}>Inglés</option>
                    <option value="Educación Física" {{ old('area') === 'Educación Física' ? 'selected' : '' }}>Educación Física</option>
                    <option value="Arte y Cultura" {{ old('area') === 'Arte y Cultura' ? 'selected' : '' }}>Arte y Cultura</option>
                    <option value="EPT" {{ old('area') === 'EPT' ? 'selected' : '' }}>EPT (Educación para el Trabajo)</option>
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
            
            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Turno</label>
                <select name="turno" class="form-control" required>
                    <option value="">Seleccionar...</option>
                    <option value="mañana" {{ old('turno') === 'mañana' ? 'selected' : '' }}>Mañana</option>
                    <option value="tarde" {{ old('turno') === 'tarde' ? 'selected' : '' }}>Tarde</option>
                </select>
            </div>
            <div class="form-group" id="fileUploadGroup">
                <label class="form-label">Foto de Perfil (Opcional) <small style="color:var(--text-muted);">(Solo para el perfil, no para la IA)</small></label>
                <input type="file" name="foto" id="fotoInput" class="form-control" accept="image/*">
                <p class="form-hint">Para el reconocimiento facial, use la cámara del panel derecho →</p>
            </div>
            
            <div style="margin-top:20px;display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary w-full" id="btnMainSubmit" style="opacity:0.5; cursor:not-allowed;" disabled>
                    <i class="fas fa-save"></i> Registrar Persona
                </button>
                <a href="{{ route('personas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    {{-- Panel de Cámara / Enrollment Facial --}}
    <div>
        <div class="card p-0" style="background:#1e1e2d; border-color:#2b2b40;">
            <div class="card-header" style="border-bottom:1px solid #2b2b40; display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title" style="color:#fff;"><i class="fas fa-camera text-primary"></i> Captura Facial</h3>
                <div style="display:flex; gap:10px;">
                    <select id="cameraSelect" class="form-control" style="background:#2b2b40; color:white; border:none; padding:4px 8px; border-radius:4px; max-width:200px; display:none;"></select>
                    <button type="button" class="btn btn-secondary btn-sm" id="btnToggleCamera" onclick="toggleCamera()" style="background:#323248; border:none; color:white;">
                        <i class="fas fa-video"></i> Activar Cámara
                    </button>
                </div>
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
            <div style="margin-top:12px;display:flex;gap:8px; padding: 12px;">
                <button type="button" class="btn btn-success w-full" id="btnCapture" onclick="capturePhoto()" disabled>
                    <i class="fas fa-camera"></i> Iniciar Escaneo Facial (10 fotos)
                </button>
            </div>
            <div id="capturedPreview" style="margin-top:12px;display:none; padding: 12px;">
                <p class="form-label">Foto capturada:</p>
                <img id="capturedImg" style="width:100%;border-radius:var(--radius-sm);border:2px solid var(--success);">
            </div>
        </div>

        {{-- Instrucciones de enrollment --}}
        <div class="card mt-4">
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
const areaGroup = document.getElementById('areaGroup');

function toggleFields() {
    estudianteFields.classList.toggle('hidden', cargoSelect.value !== 'estudiante');
    areaGroup.classList.toggle('hidden', cargoSelect.value !== 'docente');
}

cargoSelect.addEventListener('change', toggleFields);
toggleFields();

// Cargar lista de cámaras
async function loadCameras() {
    try {
        await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(d => d.kind === 'videoinput');
        const select = document.getElementById('cameraSelect');
        
        select.innerHTML = '';
        if (videoDevices.length > 0) {
            select.style.display = 'block';
            videoDevices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Cámara ${index + 1}`;
                select.appendChild(option);
            });
            // Si cambian de cámara mientras está activa, reiniciar
            select.addEventListener('change', () => {
                if (cameraActive) {
                    toggleCamera(); // Apagar
                    setTimeout(toggleCamera, 500); // Prender con nueva
                }
            });
        }
    } catch (e) {
        console.log("No se pudieron cargar las cámaras: ", e);
    }
}
loadCameras();

// El botón de enviar solo se habilita al completar la IA.
// Ya no habilitamos por subir archivo.

async function toggleCamera() {
    const video = document.getElementById('cameraVideo');
    const placeholder = document.getElementById('cameraPlaceholder');
    const guide = document.getElementById('cameraGuide');
    const status = document.getElementById('cameraStatus');
    const dot = document.getElementById('cameraDot');
    const statusText = document.getElementById('cameraStatusText');
    const btn = document.getElementById('btnToggleCamera');
    const btnCapture = document.getElementById('btnCapture');
    const cameraSelect = document.getElementById('cameraSelect');

    if (cameraActive) {
        if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
        video.style.display = 'none'; placeholder.style.display = 'flex';
        guide.style.display = 'none'; status.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-video"></i> Activar Cámara';
        btnCapture.disabled = true; cameraActive = false;
        return;
    }

    try {
        const constraints = { video: { width: 640, height: 480 } };
        if (cameraSelect && cameraSelect.value) {
            constraints.video.deviceId = { exact: cameraSelect.value };
        } else {
            constraints.video.facingMode = 'user';
        }
        
        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = cameraStream; video.style.display = 'block';
        placeholder.style.display = 'none'; guide.style.display = 'flex';
        status.style.display = 'flex'; dot.classList.add('active');
        statusText.textContent = 'Cámara activa';
        btn.innerHTML = '<i class="fas fa-video-slash"></i> Desactivar';
        btnCapture.disabled = false; cameraActive = true;
    } catch (err) {
        alert("Error al acceder a la cámara: " + err.message);
    }
}

document.getElementById('personaForm').addEventListener('submit', async function(e) {
    const hiddenInput = document.getElementById('fotoBase64');
    
    // Si la cámara NO está activa, y no se ha completado el escaneo (fotoBase64)
    if (!cameraActive && (!hiddenInput || !hiddenInput.value)) {
        e.preventDefault();
        alert("¡Alto! Es obligatorio Activar la Cámara y realizar el Escaneo Facial de 10 fotos antes de guardar. (La foto de archivo es opcional y solo para el perfil).");
        return;
    }

    // Si ya completamos el escaneo y solo estamos enviando el formulario final, lo dejamos pasar
    if (hiddenInput && hiddenInput.value && !capturing) {
        return; // Deja que se envíe a Laravel
    }

    // Si la cámara está activa y queremos escanear
    if (cameraActive && !capturing) {
        e.preventDefault(); // Detenemos el envío normal
        
        const codigo = document.querySelector('input[name="codigo"]').value;
        const nombre = document.querySelector('input[name="nombre"]').value;
        
        if (!codigo || !nombre) {
            alert("El código y el nombre son obligatorios para el reconocimiento facial.");
            return;
        }

        capturing = true;
        const btnCapture = document.getElementById('btnCapture');
        const btnSubmit = document.getElementById('btnMainSubmit');
        btnSubmit.disabled = true;
        btnCapture.disabled = true;
        btnCapture.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Escaneando (10 fotos)...';
        
        const statusText = document.getElementById('cameraStatusText');
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth; 
        canvas.height = video.videoHeight;

        let frames = [];
        let totalFrames = 10; // 10 fotos para mayor profesionalismo

        // Instrucciones a mostrar por frame
        const instrucciones = [
            "Frente - Mire directo",
            "Frente - Sonría levemente",
            "Izquierda - Gire levemente",
            "Izquierda - Gire un poco más",
            "Derecha - Gire levemente",
            "Derecha - Gire un poco más",
            "Arriba - Levante el mentón",
            "Arriba - Mire hacia la cámara",
            "Abajo - Baje el mentón",
            "Frente - Rostro relajado final"
        ];

        // Cambiar estilo para las instrucciones grandes
        const overlayText = document.createElement('div');
        overlayText.style.position = 'absolute';
        overlayText.style.top = '10%';
        overlayText.style.left = '50%';
        overlayText.style.transform = 'translateX(-50%)';
        overlayText.style.background = 'rgba(0,0,0,0.7)';
        overlayText.style.color = '#fff';
        overlayText.style.padding = '12px 24px';
        overlayText.style.borderRadius = '8px';
        overlayText.style.fontSize = '1.5rem';
        overlayText.style.fontWeight = 'bold';
        overlayText.style.textAlign = 'center';
        overlayText.style.zIndex = '100';
        document.getElementById('cameraContainer').appendChild(overlayText);

        // Capturar frames con pausas y cuenta regresiva
        for (let i = 0; i < totalFrames; i++) {
            const pasoBase = `PASO ${i+1}/${totalFrames}: ${instrucciones[i]}`;
            
            // Cuenta regresiva visual de 3 segundos
            for (let c = 3; c > 0; c--) {
                const stepMsg = `${pasoBase}<br><span style="font-size:2rem;color:var(--success);">Capturando en ${c}...</span>`;
                statusText.innerHTML = `<strong>${stepMsg}</strong>`;
                overlayText.innerHTML = stepMsg;
                await new Promise(r => setTimeout(r, 1000));
            }
            
            // Justo antes de la foto
            overlayText.innerHTML = `${pasoBase}<br><span style="font-size:2rem;color:white;">¡📸 FOTO!</span>`;
            await new Promise(r => setTimeout(r, 200));

            ctx.drawImage(video, 0, 0);
            frames.push(canvas.toDataURL('image/jpeg', 0.9));
            
            // Flash effect visual
            const container = document.getElementById('cameraContainer');
            container.style.boxShadow = '0 0 50px rgba(255,255,255,1)';
            setTimeout(() => container.style.boxShadow = '', 200);
            
            // Breve pausa para que vean que se tomó
            await new Promise(r => setTimeout(r, 500));
        }
        
        overlayText.remove();
        statusText.textContent = 'Procesando rostros con IA...';
        
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
                    document.getElementById('personaForm').appendChild(hiddenInput);
                }
                hiddenInput.value = frames[frames.length - 1];

                // Habilitar el botón principal para que ellos le den a Guardar
                alert("¡Escaneo facial completado! Ahora haz clic en el botón morado 'Registrar Persona' para finalizar.");
                statusText.textContent = 'IA Completada ✓';
                
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Registrar Persona';
                
                btnCapture.innerHTML = '<i class="fas fa-check"></i> Rostros guardados';
                
                // Detener cámara
                toggleCamera();
            } else {
                alert("Error en el reconocimiento: " + (result.detail || 'Error desconocido'));
                btnCapture.disabled = false;
                btnCapture.innerHTML = '<i class="fas fa-camera"></i> Reintentar Escaneo';
                capturing = false;
            }
        } catch (error) {
            alert("No se pudo conectar con la API de Python. ¿Está corriendo uvicorn?");
            btnCapture.disabled = false;
            btnCapture.innerHTML = '<i class="fas fa-camera"></i> Reintentar Escaneo';
            capturing = false;
        }
    }
});

function capturePhoto() {
    document.getElementById('personaForm').requestSubmit();
}
</script>
@endpush
@endsection
