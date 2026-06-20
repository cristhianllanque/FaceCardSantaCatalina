"""
Data augmentation para imágenes faciales.
Genera variaciones realistas para enriquecer el dataset de enrollment.
"""
import cv2
import numpy as np
from typing import List

import config


class FaceAugmentor:
    """
    Genera variaciones de imágenes faciales para data augmentation.
    
    Transformaciones aplicadas (realistas para entorno de asistencia):
    - Variación de brillo (simula diferentes iluminaciones)
    - Ligera rotación (cabeza ligeramente inclinada)
    - Flip horizontal (simetría facial)
    - Ruido gaussiano leve (simula cámaras de menor calidad)
    - Variación de contraste
    - Ligero zoom/crop (simula diferentes distancias)
    """

    def __init__(self, augmentations_per_image: int = config.AUGMENTATIONS_PER_IMAGE):
        self.augmentations_per_image = augmentations_per_image
        self.rng = np.random.default_rng(seed=42)

    def augment(self, face_image: np.ndarray) -> List[np.ndarray]:
        """
        Genera múltiples variaciones de una imagen facial.
        
        Args:
            face_image: Imagen BGR del rostro (ya procesada).
        
        Returns:
            Lista de imágenes augmentadas.
        """
        augmented = []

        for i in range(self.augmentations_per_image):
            img = face_image.copy()

            # Seleccionar transformaciones aleatorias
            transforms = self.rng.choice(
                ["brightness", "rotation", "flip", "noise", "contrast", "zoom"],
                size=self.rng.integers(1, 4),
                replace=False,
            )

            for t in transforms:
                if t == "brightness":
                    img = self._adjust_brightness(img)
                elif t == "rotation":
                    img = self._rotate(img)
                elif t == "flip":
                    img = self._horizontal_flip(img)
                elif t == "noise":
                    img = self._add_noise(img)
                elif t == "contrast":
                    img = self._adjust_contrast(img)
                elif t == "zoom":
                    img = self._random_zoom(img)

            augmented.append(img)

        return augmented

    def _adjust_brightness(self, image: np.ndarray) -> np.ndarray:
        """Ajusta brillo aleatoriamente (-30 a +30)."""
        value = self.rng.integers(-30, 31)
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV).astype(np.int16)
        hsv[:, :, 2] = np.clip(hsv[:, :, 2] + value, 0, 255)
        return cv2.cvtColor(hsv.astype(np.uint8), cv2.COLOR_HSV2BGR)

    def _rotate(self, image: np.ndarray) -> np.ndarray:
        """Rotación leve (-10 a +10 grados)."""
        angle = self.rng.uniform(-10, 10)
        h, w = image.shape[:2]
        center = (w // 2, h // 2)
        matrix = cv2.getRotationMatrix2D(center, angle, 1.0)
        return cv2.warpAffine(image, matrix, (w, h), borderMode=cv2.BORDER_REPLICATE)

    def _horizontal_flip(self, image: np.ndarray) -> np.ndarray:
        """Flip horizontal (espejo)."""
        return cv2.flip(image, 1)

    def _add_noise(self, image: np.ndarray) -> np.ndarray:
        """Añade ruido gaussiano leve."""
        noise = self.rng.normal(0, 8, image.shape).astype(np.int16)
        noisy = np.clip(image.astype(np.int16) + noise, 0, 255)
        return noisy.astype(np.uint8)

    def _adjust_contrast(self, image: np.ndarray) -> np.ndarray:
        """Ajusta contraste aleatoriamente (0.8x a 1.2x)."""
        alpha = self.rng.uniform(0.8, 1.2)
        return np.clip(image.astype(np.float32) * alpha, 0, 255).astype(np.uint8)

    def _random_zoom(self, image: np.ndarray) -> np.ndarray:
        """Zoom aleatorio (crop central del 80-95%)."""
        h, w = image.shape[:2]
        scale = self.rng.uniform(0.80, 0.95)
        new_h, new_w = int(h * scale), int(w * scale)
        y_off = (h - new_h) // 2
        x_off = (w - new_w) // 2
        cropped = image[y_off:y_off + new_h, x_off:x_off + new_w]
        return cv2.resize(cropped, (w, h), interpolation=cv2.INTER_LANCZOS4)
