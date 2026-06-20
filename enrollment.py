"""
Orquestador principal del proceso de enrollment facial.
Coordina: detección → preprocesamiento → almacenamiento → augmentation.
"""
import cv2
import numpy as np
from pathlib import Path
from typing import Dict, List, Optional

import config
from face_detector import YOLOFaceDetector, FaceDetection
from face_preprocessor import FacePreprocessor, QualityReport
from dataset_manager import DatasetManager
from face_augmentor import FaceAugmentor


class EnrollmentPipeline:
    """
    Pipeline completo de enrollment facial.
    
    Flujo:
    1. Recibe imagen (webcam, archivo, carpeta)
    2. YOLO-Face detecta el rostro
    3. Verifica calidad (blur, brillo)
    4. Alinea con landmarks de los ojos
    5. Normaliza (CLAHE, resize)
    6. Guarda en dataset organizado
    7. Genera augmentaciones
    
    Uso:
        pipeline = EnrollmentPipeline()
        pipeline.enroll_from_webcam("EST-2024001", "Juan Pérez")
        pipeline.enroll_from_folder("EST-2024002", "María López", Path("./fotos"))
    """

    def __init__(
        self,
        model_path: Optional[str] = None,
        device: str = config.DEVICE,
    ):
        print("=" * 60)
        print("  SISTEMA DE ENROLLMENT FACIAL")
        print("  Detector: YOLO-Face (YOLOv8)")
        print(f"  Device: {device.upper()}")
        if config.CUDA_AVAILABLE:
            print(f"  GPU: {config.GPU_NAME} ({config.GPU_MEMORY})")
            print(f"  CUDA: {config.CUDA_VERSION} | FP16: {config.USE_HALF_PRECISION}")
        print(f"  Modelo: {config.YOLO_MODEL_NAME}")
        print("=" * 60)

        self.detector = YOLOFaceDetector(model_path=model_path, device=device)
        self.preprocessor = FacePreprocessor()
        self.dataset = DatasetManager()
        self.augmentor = FaceAugmentor()

    # ----------------------------------------------------------------
    # Enrollment desde imagen individual
    # ----------------------------------------------------------------

    def enroll_image(
        self,
        codigo: str,
        nombre: str,
        image: np.ndarray,
        save_raw: bool = True,
    ) -> Dict:
        """
        Procesa una imagen para enrollment.
        
        Args:
            codigo: Código del estudiante.
            nombre: Nombre del estudiante.
            image: Imagen BGR.
            save_raw: Si guardar también la imagen original.
        
        Returns:
            Diccionario con el resultado del procesamiento.
        """
        # Asegurar que existe el estudiante
        if not self.dataset.student_exists(codigo):
            self.dataset.create_student(codigo, nombre)

        # Guardar imagen original
        if save_raw:
            self.dataset.save_raw_image(codigo, image)

        # Detectar rostros
        detections = self.detector.detect(image, max_faces=1)

        if not detections:
            return {
                "success": False,
                "reason": "NO_FACE_DETECTED",
                "message": "No se detectó ningún rostro en la imagen.",
            }

        det = detections[0]

        # Recortar rostro con padding
        h, w = image.shape[:2]
        x1, y1, x2, y2 = det.bbox
        fw, fh = x2 - x1, y2 - y1
        pad_x = int(fw * config.FACE_PADDING)
        pad_y = int(fh * config.FACE_PADDING)
        cx1 = max(0, x1 - pad_x)
        cy1 = max(0, y1 - pad_y)
        cx2 = min(w, x2 + pad_x)
        cy2 = min(h, y2 + pad_y)
        face_crop = image[cy1:cy2, cx1:cx2]

        # Preprocesar (calidad + alineación + normalización)
        processed, quality = self.preprocessor.process(
            face_crop,
            landmarks=det.landmarks,
            check_quality=True,
        )

        if processed is None:
            return {
                "success": False,
                "reason": "LOW_QUALITY",
                "message": str(quality),
                "quality": quality,
            }

        # Guardar rostro procesado
        face_path = self.dataset.save_face_image(
            codigo, processed, quality.blur_score,
        )

        # Generar augmentaciones
        aug_count = 0
        if config.ENABLE_AUGMENTATION:
            augmented_images = self.augmentor.augment(processed)
            for i, aug_img in enumerate(augmented_images):
                self.dataset.save_augmented_image(codigo, aug_img, i)
                aug_count += 1

        return {
            "success": True,
            "face_path": str(face_path),
            "confidence": det.confidence,
            "quality": quality,
            "augmented": aug_count,
            "message": f"Rostro registrado (conf={det.confidence:.2f}, blur={quality.blur_score:.1f})",
        }

    # ----------------------------------------------------------------
    # Enrollment desde carpeta de imágenes
    # ----------------------------------------------------------------

    def enroll_from_folder(
        self,
        codigo: str,
        nombre: str,
        folder_path: Path,
        extensions: tuple = (".jpg", ".jpeg", ".png", ".bmp", ".webp"),
    ) -> Dict:
        """
        Procesa todas las imágenes de una carpeta para enrollment.
        
        Args:
            codigo: Código del estudiante.
            nombre: Nombre del estudiante.
            folder_path: Ruta a la carpeta con imágenes.
            extensions: Extensiones de archivo a procesar.
        
        Returns:
            Resumen del procesamiento batch.
        """
        folder = Path(folder_path)
        if not folder.exists():
            return {"success": False, "message": f"Carpeta no encontrada: {folder}"}

        # Buscar imágenes
        image_files = []
        for ext in extensions:
            image_files.extend(folder.glob(f"*{ext}"))
            image_files.extend(folder.glob(f"*{ext.upper()}"))

        image_files = sorted(set(image_files))

        if not image_files:
            return {"success": False, "message": "No se encontraron imágenes en la carpeta"}

        print(f"\n[Enrollment] Procesando {len(image_files)} imágenes de {nombre} ({codigo})")
        print("-" * 50)

        results = {
            "total_images": len(image_files),
            "processed": 0,
            "rejected": 0,
            "no_face": 0,
            "low_quality": 0,
            "details": [],
        }

        for i, img_path in enumerate(image_files):
            if results["processed"] >= config.MAX_SAMPLES_PER_STUDENT:
                print(f"[Enrollment] Límite alcanzado ({config.MAX_SAMPLES_PER_STUDENT} muestras)")
                break

            image = cv2.imread(str(img_path))
            if image is None:
                print(f"  [{i+1}/{len(image_files)}] ERROR: No se pudo leer {img_path.name}")
                continue

            result = self.enroll_image(codigo, nombre, image, save_raw=True)

            status_icon = "✓" if result["success"] else "✗"
            print(f"  [{i+1}/{len(image_files)}] {status_icon} {img_path.name}: {result['message']}")

            if result["success"]:
                results["processed"] += 1
            else:
                results["rejected"] += 1
                if result["reason"] == "NO_FACE_DETECTED":
                    results["no_face"] += 1
                elif result["reason"] == "LOW_QUALITY":
                    results["low_quality"] += 1

            results["details"].append({
                "file": img_path.name,
                **result,
            })

        # Verificar estado final
        verification = self.dataset.verify_student(codigo)
        results["enrollment_status"] = verification["status"]
        results["success"] = verification["status"] == "COMPLETE"

        print("-" * 50)
        print(f"[Enrollment] Resultado: {results['processed']}/{results['total_images']} procesadas")
        print(f"[Enrollment] Estado: {verification['message']}")

        return results

    # ----------------------------------------------------------------
    # Enrollment desde webcam
    # ----------------------------------------------------------------

    def enroll_from_webcam(
        self,
        codigo: str,
        nombre: str,
        camera_index: int = 1,
    ) -> Dict:
        """
        Captura rostros en tiempo real desde la webcam.
        
        Controles:
            ESPACIO: Capturar frame actual
            'a': Modo automático (captura cada N ms)
            'q': Terminar captura
        
        Args:
            codigo: Código del estudiante.
            nombre: Nombre del estudiante.
            camera_index: Índice de la cámara (0 = default).
        
        Returns:
            Resumen del enrollment.
        """
        # Crear estudiante
        if not self.dataset.student_exists(codigo):
            self.dataset.create_student(codigo, nombre)

        
        cap = cv2.VideoCapture(1)
        if not cap.isOpened():
            return {"success": False, "message": "No se pudo abrir la cámara"}

        print(f"\n[Webcam] Enrollment de {nombre} ({codigo})")
        print("[Webcam] Controles: ESPACIO=capturar | A=auto | Q=salir")
        print("-" * 50)

        captured = 0
        auto_mode = False
        last_capture_time = 0

        results = {"total_captured": 0, "total_rejected": 0, "details": []}

        while True:
            ret, frame = cap.read()
            if not ret:
                break

            # Detectar rostros para preview
            detections = self.detector.detect(frame, max_faces=1)

            # Dibujar preview
            display = self.detector.draw_detections(frame, detections)

            # Info en pantalla
            status = f"Capturadas: {captured}/{config.MAX_SAMPLES_PER_STUDENT}"
            mode_text = "AUTO" if auto_mode else "MANUAL"
            cv2.putText(display, f"{nombre} ({codigo})", (10, 30),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
            cv2.putText(display, f"{status} | Modo: {mode_text}", (10, 60),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 255), 1)

            if not detections:
                cv2.putText(display, "No se detecta rostro", (10, 90),
                           cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 1)

            cv2.imshow("Enrollment Facial", display)

            # Verificar si debemos capturar
            key = cv2.waitKey(1) & 0xFF
            current_time = cv2.getTickCount() / cv2.getTickFrequency() * 1000

            should_capture = False
            if key == ord(' '):  # Espacio = captura manual
                should_capture = True
            elif key == ord('a'):  # Toggle auto
                auto_mode = not auto_mode
                print(f"[Webcam] Modo automático: {'ON' if auto_mode else 'OFF'}")
            elif key == ord('q'):  # Salir
                break

            if auto_mode and (current_time - last_capture_time) > config.WEBCAM_CAPTURE_DELAY_MS:
                should_capture = True

            # Capturar si corresponde
            if should_capture and detections and captured < config.MAX_SAMPLES_PER_STUDENT:
                result = self.enroll_image(codigo, nombre, frame, save_raw=True)
                if result["success"]:
                    captured += 1
                    results["total_captured"] += 1
                    print(f"  [Captura {captured}] {result['message']}")
                    last_capture_time = current_time
                else:
                    results["total_rejected"] += 1
                    print(f"  [Rechazada] {result['message']}")

                results["details"].append(result)

            # Verificar límite
            if captured >= config.MAX_SAMPLES_PER_STUDENT:
                print(f"[Webcam] Límite alcanzado ({config.MAX_SAMPLES_PER_STUDENT})")
                break

        cap.release()
        cv2.destroyAllWindows()

        # Estado final
        verification = self.dataset.verify_student(codigo)
        results["enrollment_status"] = verification["status"]
        results["success"] = verification["status"] == "COMPLETE"

        print("-" * 50)
        print(f"[Webcam] Resultado: {captured} capturadas, {results['total_rejected']} rechazadas")
        print(f"[Webcam] Estado: {verification['message']}")

        return results
