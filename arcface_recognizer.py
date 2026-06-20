import cv2
import numpy as np
import onnxruntime as ort
from typing import Optional


class ArcFaceRecognizer:
    def __init__(self, model_path: str = "models/arcface_w600k_r50.onnx", providers=None):
        if providers is None:
            providers = ["CUDAExecutionProvider", "CPUExecutionProvider"]

        self.model_path = model_path
        self.session = ort.InferenceSession(self.model_path, providers=providers)
        self.input_name = self.session.get_inputs()[0].name
        self.output_name = self.session.get_outputs()[0].name

    def _preprocess(self, face_bgr: np.ndarray) -> np.ndarray:
        face = cv2.resize(face_bgr, (112, 112), interpolation=cv2.INTER_LINEAR)
        face = cv2.cvtColor(face, cv2.COLOR_BGR2RGB)
        face = face.astype(np.float32) / 255.0
        face = (face - 0.5) / 0.5
        face = np.transpose(face, (2, 0, 1))  # HWC -> CHW
        face = np.expand_dims(face, axis=0).astype(np.float32)
        return face

    def get_embedding(self, face_bgr: np.ndarray) -> Optional[np.ndarray]:
        try:
            if face_bgr is None or face_bgr.size == 0:
                return None

            inp = self._preprocess(face_bgr)
            emb = self.session.run([self.output_name], {self.input_name: inp})[0][0]
            emb = emb.astype(np.float32)

            norm = np.linalg.norm(emb)
            if norm > 0:
                emb = emb / norm

            return emb
        except Exception as e:
            print(f"[ArcFaceONNX] Error extrayendo embedding: {e}")
            return None

    @staticmethod
    def cosine_similarity(a: np.ndarray, b: np.ndarray) -> float:
        if a is None or b is None:
            return 0.0
        denom = np.linalg.norm(a) * np.linalg.norm(b)
        if denom == 0:
            return 0.0
        return float(np.dot(a, b) / denom)