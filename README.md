# Sistema de Enrollment Facial - Control de Asistencia
## Fase 0: Registro y Construcción del Dataset

### Descripción
Sistema de captura, detección y almacenamiento de rostros de estudiantes
usando YOLO-Face (ultralytics) para la detección facial.

### Estructura del Proyecto
```
enrollment_system/
├── config.py              # Configuración global
├── face_detector.py       # Detector YOLO-Face wrapper
├── face_preprocessor.py   # Preprocesamiento y alineación
├── enrollment.py          # Lógica principal de enrollment
├── dataset_manager.py     # Gestión del dataset en disco
├── main.py                # CLI principal
├── requirements.txt       # Dependencias
└── dataset/               # Dataset generado
    └── {codigo_alumno}/
        ├── metadata.json
        ├── raw/           # Imágenes originales
        ├── faces/         # Rostros recortados y alineados
        └── augmented/     # Data augmentation
```

### Instalación
```bash
pip install -r requirements.txt
```

### Uso
```bash
# Modo 1: Captura desde webcam
python main.py --mode webcam --codigo EST-2024001 --nombre "Juan Pérez"

# Modo 2: Desde carpeta de imágenes
python main.py --mode folder --input ./fotos_juan/ --codigo EST-2024001 --nombre "Juan Pérez"

# Modo 3: Desde imagen individual
python main.py --mode image --input foto.jpg --codigo EST-2024001 --nombre "Juan Pérez"

# Listar estudiantes registrados
python main.py --mode list

# Verificar calidad del dataset
python main.py --mode verify --codigo EST-2024001
```

### Modelo YOLO-Face
Usamos `yolov8n-face.pt` del repositorio `akanametov/yolo-face` 
(pre-entrenado en WIDERFace). Se descarga automáticamente en el primer uso.

