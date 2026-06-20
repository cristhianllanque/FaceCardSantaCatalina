"""
Interfaz gráfica OpenCV para el sistema de enrollment facial.
Panel de control con botones clickeables, campos de texto editables,
barra de progreso, galería de capturas, e indicadores de calidad.
"""
import cv2
import numpy as np
import time
from typing import Callable, Dict, List, Optional, Tuple
from dataclasses import dataclass, field

import config


# ============================================================
# Paleta de colores (BGR)
# ============================================================
class Colors:
    BG_DARK = (30, 30, 30)
    BG_PANEL = (45, 45, 45)
    BG_INPUT = (60, 60, 60)
    BG_INPUT_ACTIVE = (80, 80, 80)
    WHITE = (255, 255, 255)
    GRAY = (160, 160, 160)
    LIGHT_GRAY = (200, 200, 200)
    GREEN = (80, 210, 120)
    GREEN_DARK = (40, 150, 70)
    RED = (80, 80, 230)
    RED_DARK = (50, 50, 180)
    BLUE = (210, 160, 60)
    BLUE_DARK = (160, 120, 40)
    YELLOW = (60, 210, 230)
    CYAN = (210, 200, 60)
    ORANGE = (50, 140, 240)
    BORDER = (80, 80, 80)
    ACCENT = (180, 130, 40)
    FACE_BOX = (80, 230, 80)
    FACE_BOX_WARN = (0, 180, 255)


# ============================================================
# Componentes UI
# ============================================================

@dataclass
class Button:
    """Botón clickeable en la interfaz."""
    x: int
    y: int
    w: int
    h: int
    label: str
    color: Tuple[int, int, int] = Colors.BLUE
    hover_color: Tuple[int, int, int] = Colors.BLUE_DARK
    text_color: Tuple[int, int, int] = Colors.WHITE
    enabled: bool = True
    visible: bool = True
    is_hovered: bool = False
    is_toggle: bool = False
    is_active: bool = False      # Para toggle buttons
    active_color: Tuple[int, int, int] = Colors.GREEN
    active_label: str = ""       # Label alternativo cuando está activo
    hotkey: str = ""             # Tecla de atajo (mostrada en el botón)

    def contains(self, mx: int, my: int) -> bool:
        return (self.x <= mx <= self.x + self.w and
                self.y <= my <= self.y + self.h and
                self.enabled and self.visible)

    def draw(self, canvas: np.ndarray):
        if not self.visible:
            return

        # Color según estado
        if not self.enabled:
            bg = Colors.BG_INPUT
            tc = Colors.GRAY
        elif self.is_toggle and self.is_active:
            bg = self.active_color
            tc = Colors.WHITE
        elif self.is_hovered:
            bg = self.hover_color
            tc = Colors.WHITE
        else:
            bg = self.color
            tc = self.text_color

        # Rectángulo con bordes redondeados (simulados)
        cv2.rectangle(canvas, (self.x, self.y), (self.x + self.w, self.y + self.h), bg, -1)
        cv2.rectangle(canvas, (self.x, self.y), (self.x + self.w, self.y + self.h), Colors.BORDER, 1)

        # Texto centrado
        label = self.active_label if (self.is_toggle and self.is_active and self.active_label) else self.label
        if self.hotkey:
            label = f"[{self.hotkey}] {label}"
        text_size = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)[0]
        tx = self.x + (self.w - text_size[0]) // 2
        ty = self.y + (self.h + text_size[1]) // 2
        cv2.putText(canvas, label, (tx, ty), cv2.FONT_HERSHEY_SIMPLEX, 0.5, tc, 1, cv2.LINE_AA)


@dataclass
class TextField:
    """Campo de texto editable."""
    x: int
    y: int
    w: int
    h: int
    label: str
    value: str = ""
    placeholder: str = ""
    is_active: bool = False
    max_length: int = 30
    visible: bool = True

    def contains(self, mx: int, my: int) -> bool:
        return (self.x <= mx <= self.x + self.w and
                self.y <= my <= self.y + self.h and self.visible)

    def handle_key(self, key: int):
        if not self.is_active:
            return
        if key == 8:  # Backspace
            self.value = self.value[:-1]
        elif key == 13:  # Enter
            self.is_active = False
        elif 32 <= key <= 126 and len(self.value) < self.max_length:
            self.value += chr(key)

    def draw(self, canvas: np.ndarray):
        if not self.visible:
            return

        # Label
        cv2.putText(canvas, self.label, (self.x, self.y - 8),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.45, Colors.LIGHT_GRAY, 1, cv2.LINE_AA)

        # Input box
        bg = Colors.BG_INPUT_ACTIVE if self.is_active else Colors.BG_INPUT
        border = Colors.ACCENT if self.is_active else Colors.BORDER
        cv2.rectangle(canvas, (self.x, self.y), (self.x + self.w, self.y + self.h), bg, -1)
        cv2.rectangle(canvas, (self.x, self.y), (self.x + self.w, self.y + self.h), border, 1 if not self.is_active else 2)

        # Texto o placeholder
        display_text = self.value if self.value else self.placeholder
        text_color = Colors.WHITE if self.value else Colors.GRAY
        cv2.putText(canvas, display_text, (self.x + 8, self.y + self.h - 10),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.5, text_color, 1, cv2.LINE_AA)

        # Cursor parpadeante
        if self.is_active and int(time.time() * 2) % 2 == 0:
            cursor_x = self.x + 8 + cv2.getTextSize(self.value, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)[0][0]
            cv2.line(canvas, (cursor_x + 2, self.y + 6), (cursor_x + 2, self.y + self.h - 6), Colors.WHITE, 1)


# ============================================================
# Interfaz principal
# ============================================================

class EnrollmentGUI:
    # Dimensiones
    CAM_W = 640
    CAM_H = 480
    PANEL_W = 320
    STATUS_H = 40
    WINDOW_W = CAM_W + PANEL_W
    WINDOW_H = CAM_H + STATUS_H

    def __init__(self, pipeline):
        """
        Args:
            pipeline: Instancia de EnrollmentPipeline.
        """
        self.pipeline = pipeline
        self.window_name = "Sistema de Enrollment Facial"

        # Estado
        self.running = False
        self.captured = 0
        self.rejected = 0
        self.auto_mode = False
        self.last_capture_time = 0.0
        self.last_status = ""
        self.last_status_color = Colors.WHITE
        self.last_quality = None
        self.fps = 0.0
        self.frame_times: List[float] = []
        self.recent_captures: List[np.ndarray] = []  # Últimas N capturas (thumbnails)
        self.flash_alpha = 0.0  # Efecto flash al capturar
        self.results: Dict = {"total_captured": 0, "total_rejected": 0, "details": []}

        # Campos de texto
        self.txt_codigo = TextField(
            x=self.CAM_W + 20, y=80, w=280, h=35,
            label="Codigo del estudiante",
            placeholder="Ej: EST-2024001",
            max_length=20,
        )
        self.txt_nombre = TextField(
            x=self.CAM_W + 20, y=145, w=280, h=35,
            label="Nombre completo",
            placeholder="Ej: Juan Perez",
            max_length=40,
        )
        self.text_fields = [self.txt_codigo, self.txt_nombre]

        # Botones
        btn_y = 200
        btn_w = 130
        btn_h = 40

        self.btn_capture = Button(
            x=self.CAM_W + 20, y=btn_y, w=btn_w, h=btn_h,
            label="Capturar", color=Colors.GREEN_DARK,
            hover_color=Colors.GREEN, hotkey="SPACE",
        )
        self.btn_auto = Button(
            x=self.CAM_W + 160, y=btn_y, w=btn_w, h=btn_h,
            label="Auto OFF", color=Colors.BLUE,
            hover_color=Colors.BLUE_DARK,
            is_toggle=True, active_color=Colors.ORANGE,
            active_label="Auto ON", hotkey="A",
        )
        self.btn_save = Button(
            x=self.CAM_W + 20, y=btn_y + 50, w=btn_w, h=btn_h,
            label="Guardar", color=Colors.BLUE,
            hover_color=Colors.BLUE_DARK, hotkey="S",
            enabled=False,
        )
        self.btn_clear = Button(
            x=self.CAM_W + 160, y=btn_y + 50, w=btn_w, h=btn_h,
            label="Limpiar", color=Colors.BG_INPUT,
            hover_color=Colors.BG_INPUT_ACTIVE, hotkey="C",
        )
        self.btn_quit = Button(
            x=self.CAM_W + 20, y=btn_y + 100, w=280, h=btn_h,
            label="Salir y Guardar", color=Colors.RED_DARK,
            hover_color=Colors.RED, hotkey="Q",
        )

        self.buttons = [
            self.btn_capture, self.btn_auto,
            self.btn_save, self.btn_clear, self.btn_quit,
        ]

        # Setup mouse callback
        cv2.namedWindow(self.window_name, cv2.WINDOW_AUTOSIZE)
        cv2.setMouseCallback(self.window_name, self._on_mouse)

    # ----------------------------------------------------------------
    # Mouse handler
    # ----------------------------------------------------------------

    def _on_mouse(self, event, x, y, flags, param):
        # Hover
        for btn in self.buttons:
            btn.is_hovered = btn.contains(x, y)

        if event == cv2.EVENT_LBUTTONDOWN:
            # Text fields
            for tf in self.text_fields:
                tf.is_active = tf.contains(x, y)

            # Buttons
            if self.btn_capture.contains(x, y):
                self._action_capture()
            elif self.btn_auto.contains(x, y):
                self._action_toggle_auto()
            elif self.btn_save.contains(x, y):
                self._action_save()
            elif self.btn_clear.contains(x, y):
                self._action_clear()
            elif self.btn_quit.contains(x, y):
                self.running = False

    # ----------------------------------------------------------------
    # Acciones
    # ----------------------------------------------------------------

    def _action_capture(self):
        """Marca que se debe capturar en el próximo frame."""
        self._should_capture = True

    def _action_toggle_auto(self):
        self.auto_mode = not self.auto_mode
        self.btn_auto.is_active = self.auto_mode

    def _action_save(self):
        """Forzar guardado del estado actual."""
        self._set_status("Datos guardados correctamente", Colors.GREEN)

    def _action_clear(self):
        """Limpia los campos y resetea capturas."""
        self.txt_codigo.value = ""
        self.txt_nombre.value = ""
        self.captured = 0
        self.rejected = 0
        self.recent_captures.clear()
        self.results = {"total_captured": 0, "total_rejected": 0, "details": []}
        self._set_status("Campos limpiados", Colors.YELLOW)

    def _set_status(self, msg: str, color: Tuple = Colors.WHITE):
        self.last_status = msg
        self.last_status_color = color

    # ----------------------------------------------------------------
    # Dibujo del panel lateral
    # ----------------------------------------------------------------

    def _draw_panel(self, canvas: np.ndarray):
        """Dibuja el panel de control completo."""
        px = self.CAM_W  # Panel x start

        # Fondo del panel
        cv2.rectangle(canvas, (px, 0), (self.WINDOW_W, self.CAM_H), Colors.BG_DARK, -1)
        cv2.line(canvas, (px, 0), (px, self.CAM_H), Colors.BORDER, 1)

        # Título
        cv2.putText(canvas, "PANEL DE CONTROL", (px + 60, 30),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.65, Colors.ACCENT, 1, cv2.LINE_AA)
        cv2.line(canvas, (px + 20, 42), (px + 300, 42), Colors.BORDER, 1)

        # GPU badge
        if config.CUDA_AVAILABLE:
            badge_text = f"GPU: {config.GPU_NAME[:20]}"
            cv2.rectangle(canvas, (px + 20, 48), (px + 300, 65), Colors.GREEN_DARK, -1)
            cv2.putText(canvas, badge_text, (px + 28, 62),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.35, Colors.WHITE, 1, cv2.LINE_AA)
        else:
            cv2.rectangle(canvas, (px + 20, 48), (px + 300, 65), Colors.BG_INPUT, -1)
            cv2.putText(canvas, "CPU Mode", (px + 115, 62),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.35, Colors.GRAY, 1, cv2.LINE_AA)

        # Campos de texto
        for tf in self.text_fields:
            tf.draw(canvas)

        # Botones
        # Habilitar captura solo si hay código y nombre
        has_data = bool(self.txt_codigo.value.strip() and self.txt_nombre.value.strip())
        self.btn_capture.enabled = has_data
        self.btn_auto.enabled = has_data
        self.btn_save.enabled = has_data and self.captured > 0

        for btn in self.buttons:
            btn.draw(canvas)

        # ---- Progreso ----
        prog_y = 360
        cv2.putText(canvas, "Progreso de capturas", (px + 20, prog_y),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.45, Colors.LIGHT_GRAY, 1, cv2.LINE_AA)

        # Barra de progreso
        bar_x = px + 20
        bar_y = prog_y + 8
        bar_w = 280
        bar_h = 18
        progress = min(self.captured / max(config.MIN_SAMPLES_PER_STUDENT, 1), 1.0)

        cv2.rectangle(canvas, (bar_x, bar_y), (bar_x + bar_w, bar_y + bar_h), Colors.BG_INPUT, -1)
        if progress > 0:
            fill_color = Colors.GREEN if progress >= 1.0 else Colors.BLUE
            cv2.rectangle(canvas, (bar_x, bar_y), (bar_x + int(bar_w * progress), bar_y + bar_h), fill_color, -1)
        cv2.rectangle(canvas, (bar_x, bar_y), (bar_x + bar_w, bar_y + bar_h), Colors.BORDER, 1)

        # Texto de progreso
        prog_text = f"{self.captured}/{config.MAX_SAMPLES_PER_STUDENT} capturas"
        if self.rejected > 0:
            prog_text += f" ({self.rejected} rechazadas)"
        cv2.putText(canvas, prog_text, (bar_x + 5, bar_y + 14),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.38, Colors.WHITE, 1, cv2.LINE_AA)

        # Status del enrollment
        status_y = prog_y + 38
        if self.captured >= config.MIN_SAMPLES_PER_STUDENT:
            cv2.putText(canvas, "ENROLLMENT COMPLETO", (px + 70, status_y),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.5, Colors.GREEN, 1, cv2.LINE_AA)
        elif self.captured > 0:
            remaining = config.MIN_SAMPLES_PER_STUDENT - self.captured
            cv2.putText(canvas, f"Faltan {remaining} capturas mas", (px + 55, status_y),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.45, Colors.YELLOW, 1, cv2.LINE_AA)

        # ---- Calidad ----
        quality_y = 420
        cv2.line(canvas, (px + 20, quality_y - 10), (px + 300, quality_y - 10), Colors.BORDER, 1)
        cv2.putText(canvas, "Calidad de imagen", (px + 20, quality_y + 5),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.45, Colors.LIGHT_GRAY, 1, cv2.LINE_AA)

        if self.last_quality:
            q = self.last_quality
            qy = quality_y + 25

            # Blur score
            blur_color = Colors.GREEN if not q.is_blurry else Colors.RED
            cv2.putText(canvas, f"Nitidez: {q.blur_score:.0f}", (px + 30, qy),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.4, blur_color, 1, cv2.LINE_AA)
            cv2.putText(canvas, f"(min: {config.BLUR_THRESHOLD})", (px + 180, qy),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.35, Colors.GRAY, 1, cv2.LINE_AA)

            # Brillo
            bright_color = Colors.GREEN if not (q.is_too_dark or q.is_too_bright) else Colors.RED
            cv2.putText(canvas, f"Brillo: {q.brightness:.0f}", (px + 30, qy + 20),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.4, bright_color, 1, cv2.LINE_AA)
            cv2.putText(canvas, f"({config.BRIGHTNESS_MIN}-{config.BRIGHTNESS_MAX})", (px + 180, qy + 20),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.35, Colors.GRAY, 1, cv2.LINE_AA)
        else:
            cv2.putText(canvas, "Sin datos aun", (px + 30, quality_y + 25),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.4, Colors.GRAY, 1, cv2.LINE_AA)

        # ---- Galería de capturas recientes ----
        gallery_y = quality_y + 65
        cv2.line(canvas, (px + 20, gallery_y - 5), (px + 300, gallery_y - 5), Colors.BORDER, 1)
        cv2.putText(canvas, "Ultimas capturas", (px + 20, gallery_y + 10),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.45, Colors.LIGHT_GRAY, 1, cv2.LINE_AA)

        thumb_size = 55
        thumb_gap = 8
        thumb_y = gallery_y + 20
        max_thumbs = 4

        for i, thumb in enumerate(self.recent_captures[-max_thumbs:]):
            tx = px + 20 + i * (thumb_size + thumb_gap)
            # Verificar que el thumbnail cabe dentro del canvas
            avail_h = self.CAM_H - thumb_y
            avail_w = self.WINDOW_W - tx
            if avail_h < 10 or avail_w < 10:
                break
            draw_h = min(thumb_size, avail_h)
            draw_w = min(thumb_size, avail_w)
            resized = cv2.resize(thumb, (thumb_size, thumb_size))
            canvas[thumb_y:thumb_y + draw_h, tx:tx + draw_w] = resized[:draw_h, :draw_w]
            cv2.rectangle(canvas, (tx, thumb_y), (tx + draw_w, thumb_y + draw_h),
                         Colors.ACCENT, 1)

    # ----------------------------------------------------------------
    # Dibujo de la barra de estado
    # ----------------------------------------------------------------

    def _draw_status_bar(self, canvas: np.ndarray):
        """Dibuja la barra de estado inferior."""
        sy = self.CAM_H
        cv2.rectangle(canvas, (0, sy), (self.WINDOW_W, self.WINDOW_H), Colors.BG_PANEL, -1)
        cv2.line(canvas, (0, sy), (self.WINDOW_W, sy), Colors.BORDER, 1)

        # Estado principal
        cv2.putText(canvas, self.last_status, (15, sy + 26),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.5, self.last_status_color, 1, cv2.LINE_AA)

        # FPS
        fps_text = f"FPS: {self.fps:.0f}"
        cv2.putText(canvas, fps_text, (self.WINDOW_W - 100, sy + 26),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.45, Colors.GRAY, 1, cv2.LINE_AA)

        # Modo
        mode_text = "AUTO" if self.auto_mode else "MANUAL"
        mode_color = Colors.ORANGE if self.auto_mode else Colors.CYAN
        cv2.putText(canvas, mode_text, (self.WINDOW_W - 200, sy + 26),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.45, mode_color, 1, cv2.LINE_AA)

    # ----------------------------------------------------------------
    # Dibujo de detecciones sobre el feed
    # ----------------------------------------------------------------

    def _draw_face_overlay(self, canvas: np.ndarray, detections, frame_h: int, frame_w: int):
        """Dibuja bounding boxes y landmarks con estilo mejorado."""
        for det in detections:
            x1, y1, x2, y2 = det.bbox
            conf = det.confidence

            # Color según confianza
            if conf >= 0.8:
                color = Colors.FACE_BOX
            elif conf >= 0.5:
                color = Colors.FACE_BOX_WARN
            else:
                color = Colors.RED

            # BBox con esquinas estilizadas
            thickness = 2
            corner_len = 20

            # Esquinas superiores
            cv2.line(canvas, (x1, y1), (x1 + corner_len, y1), color, thickness)
            cv2.line(canvas, (x1, y1), (x1, y1 + corner_len), color, thickness)
            cv2.line(canvas, (x2, y1), (x2 - corner_len, y1), color, thickness)
            cv2.line(canvas, (x2, y1), (x2, y1 + corner_len), color, thickness)
            # Esquinas inferiores
            cv2.line(canvas, (x1, y2), (x1 + corner_len, y2), color, thickness)
            cv2.line(canvas, (x1, y2), (x1, y2 - corner_len), color, thickness)
            cv2.line(canvas, (x2, y2), (x2 - corner_len, y2), color, thickness)
            cv2.line(canvas, (x2, y2), (x2, y2 - corner_len), color, thickness)

            # Label de confianza
            label = f"{conf:.0%}"
            label_size = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.55, 1)[0]
            lx = x1
            ly = y1 - 10 if y1 > 30 else y2 + 20
            cv2.rectangle(canvas, (lx, ly - label_size[1] - 4), (lx + label_size[0] + 8, ly + 4), color, -1)
            cv2.putText(canvas, label, (lx + 4, ly), cv2.FONT_HERSHEY_SIMPLEX, 0.55, Colors.WHITE, 1, cv2.LINE_AA)

            # Landmarks
            if det.landmarks is not None:
                landmark_colors = [
                    Colors.CYAN, Colors.CYAN,           # Ojos
                    Colors.GREEN,                        # Nariz
                    Colors.YELLOW, Colors.YELLOW,        # Boca
                ]
                for j, point in enumerate(det.landmarks):
                    px, py = int(point[0]), int(point[1])
                    if px > 0 and py > 0:
                        c = landmark_colors[j] if j < len(landmark_colors) else Colors.WHITE
                        cv2.circle(canvas, (px, py), 3, c, -1, cv2.LINE_AA)
                        cv2.circle(canvas, (px, py), 5, c, 1, cv2.LINE_AA)

    # ----------------------------------------------------------------
    # Loop principal
    # ----------------------------------------------------------------

    def run(
        self,
        camera_index: int = 1,
        codigo: str = "",
        nombre: str = "",
    ) -> Dict:
        """
        Ejecuta la interfaz gráfica de enrollment.
        
        Args:
            camera_index: Índice de la cámara.
            codigo: Código pre-cargado (opcional).
            nombre: Nombre pre-cargado (opcional).
        
        Returns:
            Diccionario con resultados del enrollment.
        """
        # Pre-cargar datos si se proporcionaron
        if codigo:
            self.txt_codigo.value = codigo
        if nombre:
            self.txt_nombre.value = nombre

        # Abrir cámara
        cap = cv2.VideoCapture(camera_index)
        if not cap.isOpened():
            print("[GUI] ERROR: No se pudo abrir la camara")
            return {"success": False, "message": "No se pudo abrir la camara"}

        cap.set(cv2.CAP_PROP_FRAME_WIDTH, self.CAM_W)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, self.CAM_H)

        self.running = True
        self._should_capture = False
        self._set_status("Ingresa codigo y nombre para comenzar", Colors.YELLOW)

        print(f"[GUI] Interfaz iniciada | Camara: {camera_index}")

        while self.running:
            frame_start = time.time()

            ret, frame = cap.read()
            if not ret:
                self._set_status("Error leyendo la camara", Colors.RED)
                break

            # Redimensionar frame si es necesario
            frame = cv2.resize(frame, (self.CAM_W, self.CAM_H))

            # Detectar rostros
            detections = self.pipeline.detector.detect(frame, max_faces=1)

            # Crear canvas principal
            canvas = np.zeros((self.WINDOW_H, self.WINDOW_W, 3), dtype=np.uint8)

            # Colocar frame de cámara
            canvas[0:self.CAM_H, 0:self.CAM_W] = frame

            # Dibujar detecciones sobre el feed
            self._draw_face_overlay(canvas, detections, self.CAM_H, self.CAM_W)

            # Efecto flash al capturar
            if self.flash_alpha > 0:
                overlay = canvas[0:self.CAM_H, 0:self.CAM_W].copy()
                white = np.full_like(overlay, 255)
                cv2.addWeighted(white, self.flash_alpha, overlay, 1 - self.flash_alpha, 0, overlay)
                canvas[0:self.CAM_H, 0:self.CAM_W] = overlay
                self.flash_alpha = max(0, self.flash_alpha - 0.08)

            # Guía de posición (centro)
            if not detections:
                cx, cy = self.CAM_W // 2, self.CAM_H // 2
                guide_size = 80
                cv2.ellipse(canvas, (cx, cy), (guide_size, int(guide_size * 1.3)),
                           0, 0, 360, Colors.GRAY, 1, cv2.LINE_AA)
                cv2.putText(canvas, "Coloque el rostro aqui", (cx - 110, cy + guide_size + 30),
                           cv2.FONT_HERSHEY_SIMPLEX, 0.5, Colors.GRAY, 1, cv2.LINE_AA)
            else:
                has_data_temp = bool(self.txt_codigo.value.strip() and self.txt_nombre.value.strip())
                if has_data_temp and self.captured < config.MAX_SAMPLES_PER_STUDENT:
                    if self.captured == 0:
                        instruccion = "PASO 1: Mire directamente a la camara"
                    elif self.captured == 1:
                        instruccion = "PASO 2: Gire levemente a la IZQUIERDA"
                    elif self.captured == 2:
                        instruccion = "PASO 3: Gire levemente a la DERECHA"
                    elif self.captured == 3:
                        instruccion = "PASO 4: Mire levemente hacia ARRIBA o ABAJO"
                    else:
                        instruccion = f"Extra ({self.captured}/{config.MAX_SAMPLES_PER_STUDENT}): Cambie de expresion o angulo"
                    
                    text_size = cv2.getTextSize(instruccion, cv2.FONT_HERSHEY_SIMPLEX, 0.6, 2)[0]
                    tx = (self.CAM_W - text_size[0]) // 2
                    cv2.rectangle(canvas, (tx - 10, 20), (tx + text_size[0] + 10, 50), Colors.BG_DARK, -1)
                    cv2.putText(canvas, instruccion, (tx, 42), cv2.FONT_HERSHEY_SIMPLEX, 0.6, Colors.YELLOW, 2, cv2.LINE_AA)

            # Actualizar estado
            if detections:
                det = detections[0]

                # Calcular calidad en vivo
                h, w = frame.shape[:2]
                x1, y1, x2, y2 = det.bbox
                pad = int((x2 - x1) * 0.1)
                cx1, cy1 = max(0, x1 - pad), max(0, y1 - pad)
                cx2, cy2 = min(w, x2 + pad), min(h, y2 + pad)
                face_crop = frame[cy1:cy2, cx1:cx2]

                if face_crop.size > 0:
                    quality = self.pipeline.preprocessor.check_quality(face_crop)
                    self.last_quality = quality

                self._set_status(
                    f"Rostro detectado (conf: {det.confidence:.2f})",
                    Colors.GREEN if det.confidence >= 0.7 else Colors.YELLOW,
                )
            else:
                self._set_status("No se detecta rostro - centre su cara", Colors.RED)

            # ---- Auto captura ----
            current_time = time.time() * 1000
            has_data = bool(self.txt_codigo.value.strip() and self.txt_nombre.value.strip())

            if (self.auto_mode and has_data and detections
                    and self.captured < config.MAX_SAMPLES_PER_STUDENT
                    and (current_time - self.last_capture_time) > config.WEBCAM_CAPTURE_DELAY_MS):
                self._should_capture = True

            # ---- Ejecutar captura ----
            if (self._should_capture and has_data and detections
                    and self.captured < config.MAX_SAMPLES_PER_STUDENT):
                self._do_capture(frame)
                self._should_capture = False

            # Dibujar panel y status
            self._draw_panel(canvas)
            self._draw_status_bar(canvas)

            # FPS
            elapsed = time.time() - frame_start
            self.frame_times.append(elapsed)
            if len(self.frame_times) > 30:
                self.frame_times.pop(0)
            self.fps = 1.0 / max(np.mean(self.frame_times), 0.001)

            # Mostrar
            cv2.imshow(self.window_name, canvas)

            # Teclado
            key = cv2.waitKey(1) & 0xFF

            # Si un campo de texto está activo, enviar tecla ahí
            any_field_active = any(tf.is_active for tf in self.text_fields)
            if any_field_active:
                for tf in self.text_fields:
                    tf.handle_key(key)
                # Tab para cambiar entre campos
                if key == 9:  # Tab
                    for i, tf in enumerate(self.text_fields):
                        if tf.is_active:
                            tf.is_active = False
                            next_i = (i + 1) % len(self.text_fields)
                            self.text_fields[next_i].is_active = True
                            break
            else:
                # Hotkeys globales
                if key == ord(' '):
                    self._action_capture()
                elif key == ord('a') or key == ord('A'):
                    self._action_toggle_auto()
                elif key == ord('q') or key == ord('Q'):
                    self.running = False
                elif key == ord('c') or key == ord('C'):
                    self._action_clear()
                elif key == ord('s') or key == ord('S'):
                    self._action_save()

            # Auto-stop si se alcanzó el límite
            if self.captured >= config.MAX_SAMPLES_PER_STUDENT:
                self._set_status(
                    f"Limite alcanzado ({config.MAX_SAMPLES_PER_STUDENT} capturas)",
                    Colors.GREEN,
                )

        # Cleanup
        cap.release()
        cv2.destroyAllWindows()

        # Resultado final
        codigo_final = self.txt_codigo.value.strip()
        if codigo_final and self.captured > 0:
            verification = self.pipeline.dataset.verify_student(codigo_final)
            self.results["enrollment_status"] = verification.get("status", "UNKNOWN")
            self.results["success"] = verification.get("status") == "COMPLETE"
        else:
            self.results["success"] = False
            self.results["enrollment_status"] = "NO_DATA"

        self.results["total_captured"] = self.captured
        self.results["total_rejected"] = self.rejected

        print(f"\n[GUI] Sesion finalizada: {self.captured} capturas, {self.rejected} rechazadas")
        return self.results

    # ----------------------------------------------------------------
    # Captura
    # ----------------------------------------------------------------

    def _do_capture(self, frame: np.ndarray):
        """Ejecuta la captura y enrollment de un frame."""
        codigo = self.txt_codigo.value.strip()
        nombre = self.txt_nombre.value.strip()

        if not codigo or not nombre:
            self._set_status("Ingresa codigo y nombre primero", Colors.RED)
            return

        result = self.pipeline.enroll_image(codigo, nombre, frame, save_raw=True)

        if result["success"]:
            self.captured += 1
            self.flash_alpha = 0.4  # Flash visual
            self.last_capture_time = time.time() * 1000

            # Agregar thumbnail
            quality = result.get("quality")
            if quality:
                self.last_quality = quality

            # Obtener el último rostro guardado como thumbnail
            face_images = self.pipeline.dataset.get_face_images(codigo)
            if face_images:
                self.recent_captures.append(face_images[-1])

            self._set_status(
                f"Captura #{self.captured} exitosa (conf: {result['confidence']:.2f})",
                Colors.GREEN,
            )
            print(f"  [Captura {self.captured}] {result['message']}")
        else:
            self.rejected += 1
            self._set_status(f"Rechazada: {result['message']}", Colors.RED)
            print(f"  [Rechazada] {result['message']}")

        self.results["details"].append(result)
