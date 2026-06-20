"""
CLI principal del sistema de enrollment facial.

Uso:
    python main.py --mode webcam --codigo EST-2024001 --nombre "Juan Pérez"
    python main.py --mode folder --input ./fotos/ --codigo EST-2024001 --nombre "Juan Pérez"
    python main.py --mode image --input foto.jpg --codigo EST-2024001 --nombre "Juan Pérez"
    python main.py --mode list
    python main.py --mode verify --codigo EST-2024001
    python main.py --mode summary
"""
import argparse
import json
import sys
from pathlib import Path

import config
from enrollment import EnrollmentPipeline
from dataset_manager import DatasetManager


def main():
    parser = argparse.ArgumentParser(
        description="Sistema de Enrollment Facial - Control de Asistencia",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Ejemplos:
  python main.py --mode webcam --codigo EST-2024001 --nombre "Juan Pérez"
  python main.py --mode folder --input ./fotos_juan/ --codigo EST-2024001 --nombre "Juan Pérez"
  python main.py --mode image --input foto.jpg --codigo EST-2024001 --nombre "Juan Pérez"
  python main.py --mode gui
  python main.py --mode gui --codigo EST-2024001 --nombre "Juan Pérez"
  python main.py --mode list
  python main.py --mode verify --codigo EST-2024001
  python main.py --mode summary
        """,
    )

    parser.add_argument(
        "--mode",
        type=str,
        default="gui",
        choices=["gui", "webcam", "folder", "image", "list", "verify", "summary", "delete"],
        help="Modo de operación (default: gui)",
    )
    parser.add_argument("--codigo", type=str, help="Código del estudiante (ej: EST-2024001)")
    parser.add_argument("--nombre", type=str, help="Nombre completo del estudiante")
    parser.add_argument("--input", type=str, help="Ruta a imagen o carpeta de entrada")
    parser.add_argument("--camera", type=int, default=0, help="Índice de la cámara (default: 0)")
    parser.add_argument("--device", type=str, default=config.DEVICE, help="Device: cpu, cuda, cuda:0 (auto-detectado)")
    parser.add_argument("--model", type=str, default=None, help="Ruta al modelo YOLO .pt")

    args = parser.parse_args()

    # ---- Modos que no requieren el pipeline ----

    if args.mode == "list":
        dm = DatasetManager()
        students = dm.list_students()
        if not students:
            print("\n[Dataset] No hay estudiantes registrados aún.")
            return

        print(f"\n{'='*70}")
        print(f"  ESTUDIANTES REGISTRADOS ({len(students)})")
        print(f"{'='*70}")
        print(f"  {'Código':<16} {'Nombre':<25} {'Rostros':<10} {'Estado':<12}")
        print(f"  {'-'*14:<16} {'-'*23:<25} {'-'*8:<10} {'-'*10:<12}")

        for s in students:
            status = "Completo" if s.get("enrollment_complete") else "Pendiente"
            faces = s.get("total_faces", 0)
            print(f"  {s['codigo']:<16} {s['nombre']:<25} {faces:<10} {status:<12}")

        print()
        return

    if args.mode == "summary":
        dm = DatasetManager()
        summary = dm.get_dataset_summary()
        print(f"\n{'='*50}")
        print(f"  RESUMEN DEL DATASET")
        print(f"{'='*50}")
        for key, value in summary.items():
            label = key.replace("_", " ").title()
            print(f"  {label:<30} {value}")
        print()
        return

    if args.mode == "verify":
        if not args.codigo:
            print("ERROR: --codigo es requerido para modo verify")
            sys.exit(1)
        dm = DatasetManager()
        result = dm.verify_student(args.codigo)
        print(f"\n[Verificación] {json.dumps(result, indent=2, ensure_ascii=False)}")
        return

    if args.mode == "delete":
        if not args.codigo:
            print("ERROR: --codigo es requerido para modo delete")
            sys.exit(1)
        dm = DatasetManager()
        confirm = input(f"¿Eliminar todos los datos de {args.codigo}? (s/n): ")
        if confirm.lower() == "s":
            dm.delete_student(args.codigo)
        return

    # ---- Modos que requieren el pipeline ----

    # GUI mode: código y nombre son opcionales (se ingresan en la interfaz)
    if args.mode == "gui":
        pipeline = EnrollmentPipeline(model_path=args.model, device=args.device)
        from gui_enrollment import EnrollmentGUI
        gui = EnrollmentGUI(pipeline)
        result = gui.run(
            camera_index=1,
            codigo=args.codigo or "",
            nombre=args.nombre or "",
        )
    else:
        # Otros modos requieren código y nombre
        if not args.codigo or not args.nombre:
            print("ERROR: --codigo y --nombre son requeridos para este modo")
            sys.exit(1)

        pipeline = EnrollmentPipeline(model_path=args.model, device=args.device)

        if args.mode == "webcam":
            result = pipeline.enroll_from_webcam(
                codigo=args.codigo,
                nombre=args.nombre,
                camera_index=1,
            )

        elif args.mode == "folder":
            if not args.input:
                print("ERROR: --input es requerido para modo folder")
                sys.exit(1)
            result = pipeline.enroll_from_folder(
                codigo=args.codigo,
                nombre=args.nombre,
                folder_path=Path(args.input),
            )

        elif args.mode == "image":
            if not args.input:
                print("ERROR: --input es requerido para modo image")
                sys.exit(1)

            import cv2
            image = cv2.imread(args.input)
            if image is None:
                print(f"ERROR: No se pudo leer la imagen: {args.input}")
                sys.exit(1)

            result = pipeline.enroll_image(
                codigo=args.codigo,
                nombre=args.nombre,
                image=image,
            )

    # Mostrar resultado
    print(f"\n{'='*50}")
    print(f"  RESULTADO FINAL")
    print(f"{'='*50}")
    # Filtrar campos no serializables
    printable = {k: v for k, v in result.items() if k != "details" and k != "quality"}
    print(json.dumps(printable, indent=2, ensure_ascii=False, default=str))


if __name__ == "__main__":
    main()
