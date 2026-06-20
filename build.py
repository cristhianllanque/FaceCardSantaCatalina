"""
Script de ayuda para construir el .exe del sistema de enrollment facial.

Uso:
    python build.py          # Construye el .exe (carpeta)
    python build.py --onefile # Construye un solo .exe
    python build.py --cpu     # Solo CPU (sin CUDA, más liviano)
    python build.py --clean   # Limpia builds anteriores
"""
import subprocess
import sys
import os
import shutil
from pathlib import Path


def check_pyinstaller():
    """Verifica que PyInstaller esté instalado."""
    try:
        import PyInstaller
        print(f"[OK] PyInstaller {PyInstaller.__version__}")
        return True
    except ImportError:
        print("[!] PyInstaller no instalado. Instalando...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "pyinstaller"])
        return True


def clean_build():
    """Limpia archivos de builds anteriores."""
    dirs_to_clean = ["build", "dist", "__pycache__"]
    for d in dirs_to_clean:
        p = Path(d)
        if p.exists():
            shutil.rmtree(p)
            print(f"[Limpiado] {d}/")

    spec_files = list(Path(".").glob("*.spec.bak"))
    for f in spec_files:
        f.unlink()
        print(f"[Limpiado] {f}")


def build(onefile=False, cpu_only=False):
    """Ejecuta PyInstaller con el .spec file."""
    check_pyinstaller()

    spec_file = "build_exe.spec"
    if not Path(spec_file).exists():
        print(f"[ERROR] No se encontró {spec_file}")
        sys.exit(1)

    # Modificar spec si es necesario
    if onefile or cpu_only:
        spec_content = Path(spec_file).read_text()

        if onefile:
            spec_content = spec_content.replace("ONE_FILE = False", "ONE_FILE = True")
        if cpu_only:
            spec_content = spec_content.replace("INCLUDE_CUDA = True", "INCLUDE_CUDA = False")

        temp_spec = "build_exe_temp.spec"
        Path(temp_spec).write_text(spec_content)
        spec_file = temp_spec

    print("=" * 60)
    print("  CONSTRUYENDO EJECUTABLE")
    print(f"  Modo: {'Un archivo' if onefile else 'Carpeta'}")
    print(f"  CUDA: {'No (solo CPU)' if cpu_only else 'Si'}")
    print("=" * 60)

    cmd = [
        sys.executable, "-m", "PyInstaller",
        spec_file,
        "--noconfirm",
        "--clean",
    ]

    print(f"\n[CMD] {' '.join(cmd)}\n")
    result = subprocess.run(cmd)

    # Limpiar spec temporal
    if spec_file == "build_exe_temp.spec":
        Path(spec_file).unlink(missing_ok=True)

    if result.returncode == 0:
        print("\n" + "=" * 60)
        print("  BUILD EXITOSO")
        print("=" * 60)

        if onefile:
            exe_path = Path("dist") / "EnrollmentFacial.exe"
        else:
            exe_path = Path("dist") / "EnrollmentFacial" / "EnrollmentFacial.exe"

        if exe_path.exists():
            size_mb = exe_path.stat().st_size / (1024 * 1024)
            print(f"  Ejecutable: {exe_path}")
            print(f"  Tamano: {size_mb:.0f} MB")

        # Copiar modelo si existe
        models_dir = Path("models")
        if models_dir.exists():
            if onefile:
                dest = Path("dist") / "models"
            else:
                dest = Path("dist") / "EnrollmentFacial" / "models"
            if not dest.exists():
                shutil.copytree(models_dir, dest)
                print(f"  Modelos copiados a: {dest}")

        print("\n  INSTRUCCIONES:")
        print("  1. Copia la carpeta 'models/' con el .pt al lado del .exe")
        print("  2. Ejecuta: EnrollmentFacial.exe --mode gui")
        print("  3. O desde CMD: EnrollmentFacial.exe --mode gui --codigo EST-001 --nombre \"Juan\"")
    else:
        print("\n[ERROR] Build falló. Revisa los errores arriba.")
        sys.exit(1)


if __name__ == "__main__":
    args = sys.argv[1:]

    if "--clean" in args:
        clean_build()
    elif "--help" in args or "-h" in args:
        print(__doc__)
    else:
        onefile = "--onefile" in args
        cpu_only = "--cpu" in args
        build(onefile=onefile, cpu_only=cpu_only)
