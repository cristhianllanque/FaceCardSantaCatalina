"""
Detector de rostros usando YOLO-Face (ultralytics).
Wrapper que encapsula la carga del modelo y la inferencia.
"""
import cv2
import numpy as np
from pathlib import Path
from typing import List, Optional, Tuple
from dataclasses import dataclass

from ultralytics import YOLO

import config


@dataclass
class FaceDetection:
    """Resultado de una detección facial."""
    bbox: Tuple[int, int, int, int]     # (x1, y1, x2, y2)
    confidence: float                    # Score de confianza [0, 1]
    landmarks: Optional[np.ndarray]      # 5 keypoints (ojos, nariz, comisuras) o None

    @property
    def width(self) -> int:
        return self.bbox[2] - self.bbox[0]

    @property
    def height(self) -> int:
        return self.bbox[3] - self.bbox[1]

    @property
    def area(self) -> int:
        return self.width * self.height

    @property
    def center(self) -> Tuple[int, int]:
        return (
            (self.bbox[0] + self.bbox[2]) // 2,
            (self.bbox[1] + self.bbox[3]) // 2,
        )


class YOLOFaceDetector:
    """
    Detector de rostros basado en YOLO-Face.
    
    Usa modelos pre-entrenados del repositorio akanametov/yolo-face,
    entrenados sobre WIDERFace con la arquitectura YOLOv8.
    
    Uso:
        detector = YOLOFaceDetector()
        faces = detector.detect(image_bgr)
        for face in faces:
            print(face.bbox, face.confidence)
    """

    def __init__(
        self,
        model_path: Optional[str] = None,
        confidence: float = config.YOLO_CONFIDENCE,
        iou_threshold: float = config.YOLO_IOU_THRESHOLD,
        img_size: int = config.YOLO_IMG_SIZE,
        device: str = config.DEVICE,
    ):
        """
        Inicializa el detector YOLO-Face.
        
        Args:
            model_path: Ruta al archivo .pt del modelo. Si es None,
                        usa el modelo definido en config.py.
            confidence: Umbral mínimo de confianza para detecciones.
            iou_threshold: Umbral IoU para Non-Maximum Suppression.
            img_size: Tamaño de imagen de entrada para el modelo.
            device: Dispositivo ('cpu', 'cuda', 'cuda:0', etc.).
        """
        self.confidence = confidence
        self.iou_threshold = iou_threshold
        self.img_size = img_size
        self.device = device
        self.half = config.USE_HALF_PRECISION and "cuda" in device

        # Cargar modelo
        if model_path is None:
            model_path = str(config.MODELS_DIR / config.YOLO_MODEL_NAME)

        self._model = self._load_model(model_path)

        # FP16 se maneja en predict(half=True), NO en el modelo directamente
        # ultralytics hace fuse() al cargar y model.half() previo causa dtype mismatch
        if self.half:
            print(f"[YOLOFaceDetector] FP16 se aplicara en inferencia (GPU)")

        print(f"[YOLOFaceDetector] Modelo cargado: {model_path}")
        print(f"[YOLOFaceDetector] Device: {self.device} | Conf: {self.confidence} | IoU: {self.iou_threshold}")

    def _load_model(self, model_path: str) -> YOLO:
        """Carga el modelo YOLO. Si no existe localmente, intenta descargarlo."""
        path = Path(model_path)

        if not path.exists():
            print(f"[YOLOFaceDetector] Modelo no encontrado en {model_path}")
            print(f"[YOLOFaceDetector] Descargando {config.YOLO_MODEL_NAME}...")
            print(f"[YOLOFaceDetector] Repo: {config.YOLO_MODEL_REPO}")
            print(f"[YOLOFaceDetector] Coloca el archivo .pt en: {config.MODELS_DIR}/")
            # Intentar cargar directamente (ultralytics puede resolver algunos modelos)
            path.parent.mkdir(parents=True, exist_ok=True)

        model = YOLO(str(path))
        return model

    def detect(
        self,
        image: np.ndarray,
        max_faces: int = config.MAX_FACES_PER_FRAME,
    ) -> List[FaceDetection]:
        """
        Detecta rostros en una imagen.
        
        Args:
            image: Imagen en formato BGR (OpenCV).
            max_faces: Número máximo de rostros a retornar.
        
        Returns:
            Lista de FaceDetection ordenada por confianza (descendente).
        """
        # Ejecutar inferencia (FP16 en GPU para ~2x speedup)
        results = self._model.predict(
            source=image,
            conf=self.confidence,
            iou=self.iou_threshold,
            imgsz=self.img_size,
            device=self.device,
            half=self.half,
            verbose=False,
            max_det=max_faces * 2,  # Pedir un poco más para filtrar después
        )

        detections = []

        for result in results:
            boxes = result.boxes

            if boxes is None or len(boxes) == 0:
                continue

            for i in range(len(boxes)):
                # Bounding box
                xyxy = boxes.xyxy[i].cpu().numpy().astype(int)
                x1, y1, x2, y2 = xyxy

                # Confianza
                conf = float(boxes.conf[i].cpu().numpy())

                # Landmarks (si el modelo los proporciona)
                landmarks = None
                if hasattr(result, 'keypoints') and result.keypoints is not None:
                    kpts = result.keypoints
                    if kpts.xy is not None and len(kpts.xy) > i:
                        landmarks = kpts.xy[i].cpu().numpy()

                detection = FaceDetection(
                    bbox=(x1, y1, x2, y2),
                    confidence=conf,
                    landmarks=landmarks,
                )

                # Filtrar rostros muy pequeños
                if detection.width >= config.MIN_FACE_SIZE and detection.height >= config.MIN_FACE_SIZE:
                    detections.append(detection)

        # Ordenar por confianza y limitar
        detections.sort(key=lambda d: d.confidence, reverse=True)
        return detections[:max_faces]

    def detect_and_crop(
        self,
        image: np.ndarray,
        padding: float = config.FACE_PADDING,
        output_size: Tuple[int, int] = config.FACE_OUTPUT_SIZE,
    ) -> List[Tuple[FaceDetection, np.ndarray]]:
        """
        Detecta rostros y retorna las regiones recortadas.
        
        Args:
            image: Imagen BGR.
            padding: Porcentaje de padding alrededor del bbox.
            output_size: Tamaño (W, H) del recorte final.
        
        Returns:
            Lista de tuplas (FaceDetection, imagen_recortada).
        """
        h, w = image.shape[:2]
        detections = self.detect(image)
        results = []

        for det in detections:
            x1, y1, x2, y2 = det.bbox
            fw, fh = x2 - x1, y2 - y1

            # Aplicar padding
            pad_x = int(fw * padding)
            pad_y = int(fh * padding)

            cx1 = max(0, x1 - pad_x)
            cy1 = max(0, y1 - pad_y)
            cx2 = min(w, x2 + pad_x)
            cy2 = min(h, y2 + pad_y)

            # Recortar y redimensionar
            face_crop = image[cy1:cy2, cx1:cx2]
            if face_crop.size > 0:
                face_resized = cv2.resize(face_crop, output_size, interpolation=cv2.INTER_LANCZOS4)
                results.append((det, face_resized))

        return results

    def draw_detections(
        self,
        image: np.ndarray,
        detections: List[FaceDetection],
        color: Tuple[int, int, int] = (0, 255, 0),
        thickness: int = 2,
    ) -> np.ndarray:
        """
        Dibuja las detecciones sobre la imagen.
        
        Args:
            image: Imagen BGR (se crea una copia).
            detections: Lista de detecciones a dibujar.
            color: Color BGR del bbox.
            thickness: Grosor de las líneas.
        
        Returns:
            Imagen con las detecciones dibujadas.
        """
        output = image.copy()

        for det in detections:
            x1, y1, x2, y2 = det.bbox

            # Bounding box
            cv2.rectangle(output, (x1, y1), (x2, y2), color, thickness)

            # Label con confianza
            label = f"Face {det.confidence:.2f}"
            label_size, _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.6, 1)
            cv2.rectangle(
                output,
                (x1, y1 - label_size[1] - 10),
                (x1 + label_size[0], y1),
                color,
                -1,
            )
            cv2.putText(
                output, label, (x1, y1 - 5),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 0), 1,
            )

            # Landmarks
            if det.landmarks is not None:
                for point in det.landmarks:
                    px, py = int(point[0]), int(point[1])
                    if px > 0 and py > 0:
                        cv2.circle(output, (px, py), 3, (0, 0, 255), -1)

        return output
