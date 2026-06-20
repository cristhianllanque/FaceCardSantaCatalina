"""
Preprocesamiento facial: calidad, alineación y normalización.
Mejorado: filtro de tamaño, control de rotación y suavizado.
"""
import cv2
import numpy as np
from typing import Optional, Tuple
from dataclasses import dataclass

import config


@dataclass
class QualityReport:
    is_valid: bool
    blur_score: float
    brightness: float
    is_blurry: bool
    is_too_dark: bool
    is_too_bright: bool
    message: str

    def __str__(self) -> str:
        status = "OK" if self.is_valid else "RECHAZADA"
        return (
            f"[{status}] Blur: {self.blur_score:.1f} | "
            f"Brillo: {self.brightness:.1f} | {self.message}"
        )


class FacePreprocessor:
    def __init__(
        self,
        output_size: Tuple[int, int] = config.FACE_OUTPUT_SIZE,
        apply_clahe: bool = config.APPLY_CLAHE,
    ):
        self.output_size = output_size
        self.apply_clahe = apply_clahe

        if apply_clahe:
            self.clahe = cv2.createCLAHE(
                clipLimit=config.CLAHE_CLIP_LIMIT,
                tileGridSize=config.CLAHE_GRID_SIZE,
            )

    # ------------------------------------------------------------
    # CALIDAD
    # ------------------------------------------------------------

    def check_quality(self, face_image: np.ndarray) -> QualityReport:
        gray = cv2.cvtColor(face_image, cv2.COLOR_BGR2GRAY)

        blur_score = cv2.Laplacian(gray, cv2.CV_64F).var()
        is_blurry = blur_score < config.BLUR_THRESHOLD

        brightness = np.mean(gray)
        is_too_dark = brightness < config.BRIGHTNESS_MIN
        is_too_bright = brightness > config.BRIGHTNESS_MAX

        is_valid = not is_blurry and not is_too_dark and not is_too_bright

        issues = []
        if is_blurry:
            issues.append(f"Borrosa ({blur_score:.1f})")
        if is_too_dark:
            issues.append(f"Oscura ({brightness:.0f})")
        if is_too_bright:
            issues.append(f"Brillante ({brightness:.0f})")

        message = "Calidad OK" if is_valid else " | ".join(issues)

        return QualityReport(
            is_valid,
            blur_score,
            brightness,
            is_blurry,
            is_too_dark,
            is_too_bright,
            message,
        )

    # ------------------------------------------------------------
    # ALINEACIÓN
    # ------------------------------------------------------------

    def align_face(
        self,
        image: np.ndarray,
        landmarks: Optional[np.ndarray],
    ) -> np.ndarray:

        # Si no hay landmarks → no alinear (permite perfil)
        if landmarks is None or len(landmarks) < 2:
            return image

        left_eye = landmarks[0]
        right_eye = landmarks[1]

        dx = right_eye[0] - left_eye[0]
        dy = right_eye[1] - left_eye[1]
        angle = np.degrees(np.arctan2(dy, dx))

        # 🔥 EVITAR ROTACIONES EXTREMAS (clave para perfil)
        if abs(angle) > 25:
            return image

        eyes_center = (
            (left_eye[0] + right_eye[0]) / 2.0,
            (left_eye[1] + right_eye[1]) / 2.0,
        )

        h, w = image.shape[:2]
        M = cv2.getRotationMatrix2D(eyes_center, angle, 1.0)

        aligned = cv2.warpAffine(
            image,
            M,
            (w, h),
            flags=cv2.INTER_LANCZOS4,
            borderMode=cv2.BORDER_REPLICATE,
        )

        return aligned

    # ------------------------------------------------------------
    # NORMALIZACIÓN
    # ------------------------------------------------------------

    def normalize(self, face_image: np.ndarray) -> np.ndarray:
        result = face_image.copy()

        if self.apply_clahe:
            lab = cv2.cvtColor(result, cv2.COLOR_BGR2LAB)
            l, a, b = cv2.split(lab)
            l = self.clahe.apply(l)
            lab = cv2.merge([l, a, b])
            result = cv2.cvtColor(lab, cv2.COLOR_LAB2BGR)

        # 🔥 SUAVIZADO leve (mejor estabilidad)
        result = cv2.GaussianBlur(result, (3, 3), 0)

        result = cv2.resize(result, self.output_size, interpolation=cv2.INTER_LANCZOS4)

        return result

    # ------------------------------------------------------------
    # PIPELINE COMPLETO
    # ------------------------------------------------------------

    def process(
        self,
        face_image: np.ndarray,
        landmarks: Optional[np.ndarray] = None,
        check_quality: bool = True,
    ) -> Tuple[Optional[np.ndarray], QualityReport]:

        # 🔥 FILTRO: imagen vacía
        if face_image is None or face_image.size == 0:
            return None, QualityReport(False, 0, 0, True, True, False, "Imagen vacía")

        # 🔥 FILTRO: tamaño mínimo (CLAVE)
        h, w = face_image.shape[:2]
        if min(h, w) < 80:
            return None, QualityReport(False, 0, 0, True, True, False, "Rostro muy pequeño")

        # 1. calidad
        quality = self.check_quality(face_image)
        if check_quality and not quality.is_valid:
            return None, quality

        # 2. alineación
        aligned = self.align_face(face_image, landmarks)

        # 3. normalización
        normalized = self.normalize(aligned)

        return normalized, quality