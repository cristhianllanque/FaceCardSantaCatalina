"""
Reconocimiento facial múltiple usando ArcFace sobre los rostros ya enrolados en dataset/.

No modifica tu sistema actual.
Solo reutiliza:
- config.py
- dataset_manager.py
- face_detector.py
- face_preprocessor.py
- arcface_recognizer.py

Cambios principales:
- Soporta varios rostros al mismo tiempo
- Usa ArcFace para embeddings faciales
- Dibuja una caja y etiqueta por cada rostro detectado
- Usa max_faces configurable
- Usa similitud coseno sobre embeddings reales
- Carga embeddings externos desde dataset/embeddings_db.npz
- Mejora precisión con Top1 vs Top2
- Confirma reconocimiento por varios frames
- Mantiene reconocimiento bloqueado visualmente
- Agrega tracking real para seguir el rostro reconocido
- Evita duplicar detección + tracking del mismo rostro
"""

import argparse
import os
import time
from typing import Dict, List, Optional

import cv2
import numpy as np

import config
from dataset_manager import DatasetManager
from face_detector import YOLOFaceDetector
from face_preprocessor import FacePreprocessor
from arcface_recognizer import ArcFaceRecognizer


def create_opencv_tracker():
    """
    Crea un tracker compatible con diferentes versiones de OpenCV.
    CSRT es más preciso. KCF es más rápido.
    """
    if hasattr(cv2, "legacy"):
        if hasattr(cv2.legacy, "TrackerCSRT_create"):
            return cv2.legacy.TrackerCSRT_create()
        if hasattr(cv2.legacy, "TrackerKCF_create"):
            return cv2.legacy.TrackerKCF_create()
        if hasattr(cv2.legacy, "TrackerMOSSE_create"):
            return cv2.legacy.TrackerMOSSE_create()

    if hasattr(cv2, "TrackerCSRT_create"):
        return cv2.TrackerCSRT_create()
    if hasattr(cv2, "TrackerKCF_create"):
        return cv2.TrackerKCF_create()
    if hasattr(cv2, "TrackerMOSSE_create"):
        return cv2.TrackerMOSSE_create()

    return None


class ExternalEmbeddingLoader:
    """
    Cargador de embeddings externos desde dataset/embeddings_db.npz.
    Sirve para usar embeddings compartidos sin necesitar las fotos originales.
    """

    def __init__(self, npz_path="dataset/embeddings_db.npz"):
        self.npz_path = npz_path

    def inspect(self):
        if not os.path.exists(self.npz_path):
            print(f"[WARN] No existe archivo externo: {self.npz_path}")
            return

        data = np.load(self.npz_path, allow_pickle=True)
        print("[INFO] Claves encontradas en el NPZ:")
        for key in data.files:
            value = data[key]
            print(f" - {key}: shape={getattr(value, 'shape', None)}, dtype={value.dtype}")

    @staticmethod
    def _normalize_vector(vector: np.ndarray) -> np.ndarray:
        vector = np.array(vector, dtype=np.float32)

        if vector.ndim > 1:
            vector = np.mean(vector, axis=0)

        norm = np.linalg.norm(vector)
        if norm > 0:
            vector = vector / norm

        return vector

    @staticmethod
    def _normalize_vectors(vectors) -> List[np.ndarray]:
        valid_vectors = []

        arr = np.array(vectors, dtype=np.float32)

        if arr.ndim == 1:
            vec = ExternalEmbeddingLoader._normalize_vector(arr)
            valid_vectors.append(vec)

        elif arr.ndim == 2:
            for row in arr:
                vec = ExternalEmbeddingLoader._normalize_vector(row)
                valid_vectors.append(vec)

        elif arr.ndim > 2:
            flat = arr.reshape(-1, arr.shape[-1])
            for row in flat:
                vec = ExternalEmbeddingLoader._normalize_vector(row)
                valid_vectors.append(vec)

        return valid_vectors

    def load_students_db(self) -> List[Dict]:
        if not os.path.exists(self.npz_path):
            print(f"[INFO] No se encontró embeddings externo: {self.npz_path}")
            return []

        try:
            data = np.load(self.npz_path, allow_pickle=True)
        except Exception as e:
            print(f"[ERROR] No se pudo leer {self.npz_path}: {e}")
            return []

        students_db: List[Dict] = []

        print("[INFO] Leyendo embeddings externos desde NPZ...")
        print("[INFO] Claves encontradas:")
        for key in data.files:
            value = data[key]
            print(f" - {key}: shape={getattr(value, 'shape', None)}, dtype={value.dtype}")

        possible_code_keys = ["codigos", "codes", "ids", "student_codes", "codigo"]
        possible_name_keys = ["nombres", "names", "student_names", "nombre"]
        possible_embedding_keys = ["embeddings", "vectors", "features", "X", "embs"]

        code_key = next((k for k in possible_code_keys if k in data.files), None)
        name_key = next((k for k in possible_name_keys if k in data.files), None)
        emb_key = next((k for k in possible_embedding_keys if k in data.files), None)

        if emb_key is not None:
            embeddings = data[emb_key]

            if embeddings.ndim == 1 and embeddings.dtype == object:
                embeddings = np.array(embeddings.tolist(), dtype=object)

            if code_key is not None:
                codigos = data[code_key]
            else:
                codigos = np.array([f"EXT-{i+1:04d}" for i in range(len(embeddings))])

            if name_key is not None:
                nombres = data[name_key]
            else:
                nombres = np.array([str(c) for c in codigos])

            total = min(len(embeddings), len(codigos), len(nombres))

            for i in range(total):
                codigo = str(codigos[i]).strip()
                nombre = str(nombres[i]).strip()

                if not codigo:
                    continue

                raw_vector = embeddings[i]

                try:
                    vectors = self._normalize_vectors(raw_vector)
                except Exception:
                    try:
                        vectors = [self._normalize_vector(raw_vector)]
                    except Exception:
                        continue

                if not vectors:
                    continue

                mean_vector = np.mean(np.stack(vectors, axis=0), axis=0)
                mean_vector = self._normalize_vector(mean_vector)

                students_db.append(
                    {
                        "codigo": codigo,
                        "nombre": nombre if nombre else codigo,
                        "vector": mean_vector,
                        "vectors": vectors,
                        "samples": len(vectors),
                        "source": "external_npz",
                    }
                )

        if not students_db:
            for key in data.files:
                content = data[key]

                if content.dtype != object:
                    continue

                try:
                    items = content.tolist()
                except Exception:
                    continue

                if isinstance(items, dict):
                    for k, v in items.items():
                        codigo = str(k).strip()
                        nombre = codigo
                        raw_vector = None

                        if isinstance(v, dict):
                            nombre = str(
                                v.get("nombre", v.get("name", v.get("student_name", codigo)))
                            ).strip()
                            raw_vector = v.get("vector", v.get("embedding", v.get("embeddings", None)))
                        else:
                            raw_vector = v

                        if raw_vector is None or codigo == "":
                            continue

                        try:
                            vectors = self._normalize_vectors(raw_vector)
                        except Exception:
                            try:
                                vectors = [self._normalize_vector(raw_vector)]
                            except Exception:
                                continue

                        if not vectors:
                            continue

                        mean_vector = np.mean(np.stack(vectors, axis=0), axis=0)
                        mean_vector = self._normalize_vector(mean_vector)

                        students_db.append(
                            {
                                "codigo": codigo,
                                "nombre": nombre if nombre else codigo,
                                "vector": mean_vector,
                                "vectors": vectors,
                                "samples": len(vectors),
                                "source": "external_npz",
                            }
                        )

                elif isinstance(items, list):
                    for i, item in enumerate(items):
                        if not isinstance(item, dict):
                            continue

                        codigo = str(
                            item.get("codigo", item.get("code", item.get("id", f"EXT-{i+1:04d}")))
                        ).strip()
                        nombre = str(
                            item.get("nombre", item.get("name", item.get("student_name", codigo)))
                        ).strip()

                        raw_vector = item.get(
                            "vector",
                            item.get("embedding", item.get("embeddings", item.get("features", None))),
                        )

                        if raw_vector is None or codigo == "":
                            continue

                        try:
                            vectors = self._normalize_vectors(raw_vector)
                        except Exception:
                            try:
                                vectors = [self._normalize_vector(raw_vector)]
                            except Exception:
                                continue

                        if not vectors:
                            continue

                        mean_vector = np.mean(np.stack(vectors, axis=0), axis=0)
                        mean_vector = self._normalize_vector(mean_vector)

                        students_db.append(
                            {
                                "codigo": codigo,
                                "nombre": nombre if nombre else codigo,
                                "vector": mean_vector,
                                "vectors": vectors,
                                "samples": len(vectors),
                                "source": "external_npz",
                            }
                        )

        if not students_db:
            for key in data.files:
                arr = data[key]

                if not isinstance(arr, np.ndarray):
                    continue

                if arr.dtype == object:
                    continue

                if arr.ndim == 2:
                    for i, vector in enumerate(arr):
                        vector = self._normalize_vector(vector)
                        codigo = f"EXT-{i+1:04d}"

                        students_db.append(
                            {
                                "codigo": codigo,
                                "nombre": codigo,
                                "vector": vector,
                                "vectors": [vector],
                                "samples": 1,
                                "source": "external_npz",
                            }
                        )

                    break

        print(f"[INFO] Embeddings externos cargados: {len(students_db)}")
        return students_db


class SimpleFaceRecognizer:
    """
    Reconocedor facial basado en ArcFace.
    """

    def __init__(self):
        self.dataset = DatasetManager()
        self.detector = YOLOFaceDetector(device=config.DEVICE)
        self.preprocessor = FacePreprocessor()
        self.students_db: List[Dict] = []

        self.arcface = ArcFaceRecognizer(
            model_path="models/arcface_w600k_r50.onnx",
            providers=["CUDAExecutionProvider", "CPUExecutionProvider"]
        )

    def _face_to_vector(self, face_bgr: np.ndarray) -> Optional[np.ndarray]:
        return self.arcface.get_embedding(face_bgr)

    @staticmethod
    def _normalize_vector(vector: np.ndarray) -> np.ndarray:
        vector = np.array(vector, dtype=np.float32)
        norm = np.linalg.norm(vector)
        if norm > 0:
            vector = vector / norm
        return vector

    def build_database(self):
        self.students_db.clear()

        print("=" * 60)
        print("  CONSTRUYENDO BASE DE RECONOCIMIENTO CON ARCFACE")
        print("=" * 60)

        external_loader = ExternalEmbeddingLoader("dataset/embeddings_db.npz")
        external_students = external_loader.load_students_db()

        if external_students:
            self.students_db.extend(external_students)
            print(f"[OK] Base externa cargada desde embeddings_db.npz: {len(external_students)} registros")
        else:
            print("[INFO] No se cargaron embeddings externos.")

        students = self.dataset.list_students()

        if not students:
            print("[INFO] No hay estudiantes locales en el dataset.")
            print("-" * 60)
            print(f"[INFO] Total estudiantes cargados: {len(self.students_db)}")
            print("=" * 60)
            return

        existing_codes = {str(s["codigo"]).strip() for s in self.students_db}

        for student in students:
            codigo = student.get("codigo", "").strip()
            nombre = student.get("nombre", "").strip()

            if not codigo:
                continue

            if codigo in existing_codes:
                print(f"[SKIP] {codigo} - {nombre} | ya existe desde embeddings externos")
                continue

            face_images = self.dataset.get_face_images(codigo)
            if not face_images:
                print(f"[SKIP] {codigo} - {nombre} | sin rostros")
                continue

            vectors = []
            for img in face_images:
                try:
                    vec = self._face_to_vector(img)
                    if vec is not None:
                        vec = self._normalize_vector(vec)
                        vectors.append(vec)
                except Exception as e:
                    print(f"[WARN] {codigo} | error procesando muestra: {e}")
                    continue

            if not vectors:
                print(f"[SKIP] {codigo} - {nombre} | sin embeddings válidos")
                continue

            mean_vector = np.mean(np.stack(vectors, axis=0), axis=0)
            mean_vector = self._normalize_vector(mean_vector)

            self.students_db.append(
                {
                    "codigo": codigo,
                    "nombre": nombre,
                    "vector": mean_vector,
                    "vectors": vectors,
                    "samples": len(vectors),
                    "source": "local_dataset",
                }
            )

            print(f"[OK] {codigo} - {nombre} | muestras válidas: {len(vectors)}")

        print("-" * 60)
        print(f"[INFO] Total estudiantes cargados: {len(self.students_db)}")
        print("=" * 60)

    @staticmethod
    def cosine_similarity(a: np.ndarray, b: np.ndarray) -> float:
        denom = np.linalg.norm(a) * np.linalg.norm(b)
        if denom == 0:
            return 0.0
        return float(np.dot(a, b) / denom)

    def _student_best_score(self, query_vector: np.ndarray, student: Dict) -> float:
        vectors = student.get("vectors", None)

        if vectors:
            scores = []
            for vec in vectors:
                try:
                    score = self.cosine_similarity(query_vector, vec)
                    scores.append(score)
                except Exception:
                    continue

            if scores:
                return float(max(scores))

        return self.cosine_similarity(query_vector, student["vector"])

    def _find_best_match(self, query_vector: np.ndarray) -> Dict:
        ranked = []

        for student in self.students_db:
            score = self._student_best_score(query_vector, student)
            ranked.append((score, student))

        ranked.sort(key=lambda x: x[0], reverse=True)

        best_score = ranked[0][0] if len(ranked) >= 1 else 0.0
        best_student = ranked[0][1] if len(ranked) >= 1 else None

        second_score = ranked[1][0] if len(ranked) >= 2 else 0.0
        second_student = ranked[1][1] if len(ranked) >= 2 else None

        margin = best_score - second_score

        return {
            "best_student": best_student,
            "best_score": best_score,
            "second_student": second_student,
            "second_score": second_score,
            "margin": margin,
        }

    def _process_single_detection(self, frame: np.ndarray, det) -> Optional[Dict]:
        h, w = frame.shape[:2]
        x1, y1, x2, y2 = det.bbox

        x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)

        fw, fh = x2 - x1, y2 - y1
        if fw <= 0 or fh <= 0:
            return None

        pad_x = int(fw * config.FACE_PADDING)
        pad_y = int(fh * config.FACE_PADDING)

        cx1 = max(0, x1 - pad_x)
        cy1 = max(0, y1 - pad_y)
        cx2 = min(w, x2 + pad_x)
        cy2 = min(h, y2 + pad_y)

        face_crop = frame[cy1:cy2, cx1:cx2]
        if face_crop.size == 0:
            return None

        crop_landmarks = None
        if det.landmarks is not None:
            crop_landmarks = det.landmarks.copy()
            crop_landmarks[:, 0] -= cx1
            crop_landmarks[:, 1] -= cy1

        processed, quality = self.preprocessor.process(
            face_crop,
            landmarks=crop_landmarks,
            check_quality=False,
        )

        if processed is None:
            return None

        query_vector = self._face_to_vector(processed)
        if query_vector is None:
            return None

        query_vector = self._normalize_vector(query_vector)

        match = self._find_best_match(query_vector)

        return {
            "detection": det,
            "quality": quality,
            "best_student": match["best_student"],
            "score": match["best_score"],
            "second_student": match["second_student"],
            "second_score": match["second_score"],
            "margin": match["margin"],
            "processed_face": processed,
        }

    def recognize_faces(self, frame: np.ndarray, max_faces: int = 5) -> Dict:
        if not self.students_db:
            return {
                "success": False,
                "reason": "EMPTY_DB",
                "message": "No hay base cargada",
                "results": [],
            }

        detections = self.detector.detect(frame, max_faces=max_faces)
        if not detections:
            return {
                "success": False,
                "reason": "NO_FACE",
                "message": "No se detectó ningún rostro",
                "results": [],
            }

        results = []
        for det in detections:
            item = self._process_single_detection(frame, det)
            if item is not None:
                results.append(item)

        if not results:
            return {
                "success": False,
                "reason": "PREPROCESS_FAIL",
                "message": "No se pudo procesar ningún rostro",
                "results": [],
            }

        return {
            "success": True,
            "message": f"Rostros detectados: {len(results)}",
            "results": results,
        }


def draw_result_multi(frame: np.ndarray, result: Dict, threshold: float) -> np.ndarray:
    output = frame.copy()

    if not result.get("success"):
        msg = result.get("message", "Sin resultado")
        cv2.putText(
            output,
            msg,
            (20, 40),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.8,
            (0, 0, 255),
            2,
            cv2.LINE_AA,
        )
        return output

    results = result.get("results", [])

    for item in results:
        det = item["detection"]
        score = item["score"]
        student = item["best_student"]
        margin = item.get("margin", 0.0)
        second_score = item.get("second_score", 0.0)
        locked = item.get("locked", False)
        confirmed = item.get("confirmed", False)
        tracked = item.get("tracked", False)

        x1, y1, x2, y2 = det.bbox
        x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)

        recognized = locked or confirmed or score >= threshold
        color = (0, 255, 0) if recognized else (0, 165, 255)

        if tracked:
            label1 = "SIGUIENDO"
        elif locked:
            label1 = "BLOQUEADO"
        elif confirmed:
            label1 = "RECONOCIDO"
        elif recognized:
            label1 = "RECONOCIDO"
        else:
            label1 = "NO SEGURO"

        label2 = (
            f"{student['codigo']} - {student['nombre']}"
            if student is not None
            else "Desconocido"
        )

        label3 = f"Sim:{score:.3f} Top2:{second_score:.3f} Dif:{margin:.3f}"

        cv2.rectangle(output, (x1, y1), (x2, y2), color, 2)

        box_w = min(420, max(230, len(label2) * 7))
        box_y1 = max(25, y1 - 68)
        box_y2 = y1

        cv2.rectangle(
            output,
            (x1, box_y1),
            (min(x1 + box_w, output.shape[1] - 10), box_y2),
            color,
            -1,
        )

        cv2.putText(
            output,
            label1,
            (x1 + 8, box_y1 + 18),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.50,
            (255, 255, 255),
            2,
            cv2.LINE_AA,
        )
        cv2.putText(
            output,
            label2,
            (x1 + 8, box_y1 + 39),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.43,
            (255, 255, 255),
            1,
            cv2.LINE_AA,
        )
        cv2.putText(
            output,
            label3,
            (x1 + 8, box_y1 + 59),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.36,
            (255, 255, 255),
            1,
            cv2.LINE_AA,
        )

        if det.landmarks is not None:
            for point in det.landmarks:
                px, py = int(point[0]), int(point[1])
                if px > 0 and py > 0:
                    cv2.circle(output, (px, py), 2, color, -1, cv2.LINE_AA)

    return output


def make_fake_detection_from_box(box):
    x, y, w, h = [int(v) for v in box]

    fake_det = type("Detection", (), {})()
    fake_det.bbox = (x, y, x + w, y + h)
    fake_det.landmarks = None

    return fake_det


def main():
    parser = argparse.ArgumentParser(
        description="Reconocimiento facial múltiple desde dataset existente usando ArcFace"
    )
    parser.add_argument(
        "--camera",
        type=int,
        default=1,
        help="Índice de cámara",
    )
    parser.add_argument(
        "--threshold",
        type=float,
        default=0.48,
        help="Umbral de reconocimiento",
    )
    parser.add_argument(
        "--max-faces",
        type=int,
        default=10,
        help="Máximo de rostros a detectar por frame",
    )
    args = parser.parse_args()

    recognizer = SimpleFaceRecognizer()
    recognizer.build_database()

    if not recognizer.students_db:
        print("[ERROR] No hay estudiantes cargados para reconocer.")
        return

    cap = cv2.VideoCapture(args.camera)
    if not cap.isOpened():
        print("[ERROR] No se pudo abrir la cámara.")
        return

    print("\n[INFO] Reconocimiento múltiple iniciado con ArcFace")
    print("[INFO] Teclas: Q = salir | R = recargar base\n")

    recognition_memory: Dict[str, Dict] = {}
    candidate_memory: Dict[str, Dict] = {}
    trackers: Dict[str, Dict] = {}

    HOLD_TIME = 8.0
    TRACKER_LOST_TIME = 2.0

    LOCK_THRESHOLD = args.threshold
    MIN_MARGIN = 0.045
    CONFIRM_FRAMES = 4

    while True:
        ret, frame = cap.read()
        if not ret:
            print("[ERROR] No se pudo leer frame de la cámara.")
            break

        frame = cv2.resize(frame, (960, 540))

        result = recognizer.recognize_faces(
            frame,
            max_faces=args.max_faces,
        )

        current_time = time.time()

        if result.get("success"):
            for item in result.get("results", []):
                student = item.get("best_student")
                score = item.get("score", 0.0)
                margin = item.get("margin", 0.0)

                if student is None:
                    continue

                codigo = student["codigo"]
                is_confident = score >= LOCK_THRESHOLD and margin >= MIN_MARGIN

                if is_confident:
                    if codigo not in candidate_memory:
                        candidate_memory[codigo] = {
                            "count": 1,
                            "first_time": current_time,
                            "last_time": current_time,
                            "best_score": score,
                            "item": item,
                        }
                    else:
                        candidate_memory[codigo]["count"] += 1
                        candidate_memory[codigo]["last_time"] = current_time

                        if score >= candidate_memory[codigo].get("best_score", 0.0):
                            candidate_memory[codigo]["best_score"] = score
                            candidate_memory[codigo]["item"] = item

                    if candidate_memory[codigo]["count"] >= CONFIRM_FRAMES:
                        locked_item = candidate_memory[codigo]["item"]
                        locked_item["locked"] = True
                        locked_item["confirmed"] = True
                        locked_item["tracked"] = False

                        recognition_memory[codigo] = {
                            "item": locked_item,
                            "time": current_time,
                            "locked": True,
                            "best_score": candidate_memory[codigo]["best_score"],
                        }

                        if codigo not in trackers:
                            x1, y1, x2, y2 = locked_item["detection"].bbox
                            x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)
                            bbox = (x1, y1, max(1, x2 - x1), max(1, y2 - y1))

                            tracker = create_opencv_tracker()

                            if tracker is not None:
                                try:
                                    tracker.init(frame, bbox)
                                    trackers[codigo] = {
                                        "tracker": tracker,
                                        "student": locked_item["best_student"],
                                        "last_seen": current_time,
                                        "last_item": locked_item,
                                        "last_box": bbox,
                                    }
                                except Exception as e:
                                    print(f"[WARN] No se pudo iniciar tracker para {codigo}: {e}")
                            else:
                                print("[WARN] Tu OpenCV no tiene trackers CSRT/KCF/MOSSE disponibles.")

                else:
                    if codigo in candidate_memory:
                        if (current_time - candidate_memory[codigo]["last_time"]) > 1.0:
                            del candidate_memory[codigo]

        expired_candidates = []
        for codigo, data in candidate_memory.items():
            if (current_time - data["last_time"]) > 1.5:
                expired_candidates.append(codigo)

        for codigo in expired_candidates:
            del candidate_memory[codigo]

        expired_codes = []
        for codigo, data in recognition_memory.items():
            if (current_time - data["time"]) > HOLD_TIME:
                expired_codes.append(codigo)

        for codigo in expired_codes:
            del recognition_memory[codigo]
            if codigo in trackers:
                del trackers[codigo]

        tracked_results = []
        lost_trackers = []

        for codigo, tracker_data in trackers.items():
            tracker = tracker_data["tracker"]

            try:
                ok, box = tracker.update(frame)
            except Exception:
                ok = False
                box = None

            if ok and box is not None:
                x, y, w, h = [int(v) for v in box]

                if w > 10 and h > 10:
                    tracker_data["last_seen"] = current_time
                    tracker_data["last_box"] = (x, y, w, h)

                    fake_det = make_fake_detection_from_box((x, y, w, h))
                    student = tracker_data.get("student", {"codigo": codigo, "nombre": codigo})

                    tracked_item = {
                        "detection": fake_det,
                        "quality": None,
                        "best_student": student,
                        "score": recognition_memory.get(codigo, {}).get("best_score", 1.0),
                        "second_student": None,
                        "second_score": 0.0,
                        "margin": 1.0,
                        "processed_face": None,
                        "locked": True,
                        "confirmed": True,
                        "tracked": True,
                    }

                    tracker_data["last_item"] = tracked_item
                    tracked_results.append(tracked_item)
                else:
                    if (current_time - tracker_data["last_seen"]) > TRACKER_LOST_TIME:
                        lost_trackers.append(codigo)
            else:
                if (current_time - tracker_data["last_seen"]) > TRACKER_LOST_TIME:
                    lost_trackers.append(codigo)

        for codigo in lost_trackers:
            if codigo in trackers:
                del trackers[codigo]
            if codigo in recognition_memory:
                del recognition_memory[codigo]

        merged_results = []
        seen_codes = set()

        for tracked_item in tracked_results:
            student = tracked_item.get("best_student")
            if student is not None:
                seen_codes.add(student["codigo"])
            merged_results.append(tracked_item)

        if result.get("success"):
            for item in result.get("results", []):
                student = item.get("best_student")
                score = item.get("score", 0.0)
                margin = item.get("margin", 0.0)

                if student is not None:
                    codigo = student["codigo"]

                    # CLAVE: si ya está siendo seguido por tracker,
                    # no dibujar otra caja naranja/verde encima del mismo rostro.
                    if codigo in trackers:
                        continue

                    if codigo in seen_codes:
                        continue

                    if codigo in recognition_memory:
                        item["locked"] = True
                        item["confirmed"] = True
                        item["tracked"] = False
                        recognition_memory[codigo]["item"] = item
                        recognition_memory[codigo]["time"] = current_time

                        if codigo not in trackers:
                            x1, y1, x2, y2 = item["detection"].bbox
                            x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)
                            bbox = (x1, y1, max(1, x2 - x1), max(1, y2 - y1))

                            tracker = create_opencv_tracker()
                            if tracker is not None:
                                try:
                                    tracker.init(frame, bbox)
                                    trackers[codigo] = {
                                        "tracker": tracker,
                                        "student": item["best_student"],
                                        "last_seen": current_time,
                                        "last_item": item,
                                        "last_box": bbox,
                                    }
                                except Exception:
                                    pass

                    elif score >= LOCK_THRESHOLD and margin >= MIN_MARGIN:
                        item["confirmed"] = candidate_memory.get(codigo, {}).get("count", 0) >= CONFIRM_FRAMES
                        item["locked"] = False
                        item["tracked"] = False

                    else:
                        item["confirmed"] = False
                        item["locked"] = False
                        item["tracked"] = False

                    seen_codes.add(codigo)

                merged_results.append(item)

        for codigo, data in recognition_memory.items():
            if codigo not in seen_codes and codigo not in trackers:
                locked_item = data["item"]
                locked_item["locked"] = True
                locked_item["confirmed"] = True
                locked_item["tracked"] = False
                merged_results.append(locked_item)

        display_result = {
            "success": len(merged_results) > 0,
            "message": result.get("message", ""),
            "results": merged_results,
        }

        if not display_result["success"] and not result.get("success"):
            display_result = result

        display = draw_result_multi(frame, display_result, args.threshold)

        cv2.putText(
            display,
            f"Threshold: {args.threshold:.2f} | Margin: {MIN_MARGIN:.3f} | Confirm: {CONFIRM_FRAMES} | Trackers: {len(trackers)} | Max faces: {args.max_faces}",
            (20, display.shape[0] - 20),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.52,
            (255, 255, 255),
            1,
            cv2.LINE_AA,
        )

        if display_result.get("success"):
            cv2.putText(
                display,
                f"Detectados: {len(display_result.get('results', []))}",
                (20, 35),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.75,
                (0, 255, 255),
                2,
                cv2.LINE_AA,
            )

        cv2.imshow("Registro / Reconocimiento Facial Multiple", display)

        key = cv2.waitKey(1) & 0xFF
        if key in (ord("q"), ord("Q")):
            break
        elif key in (ord("r"), ord("R")):
            print("[INFO] Recargando base...")
            recognizer.build_database()
            recognition_memory.clear()
            candidate_memory.clear()
            trackers.clear()

    cap.release()
    cv2.destroyAllWindows()


if __name__ == "__main__":
    main()