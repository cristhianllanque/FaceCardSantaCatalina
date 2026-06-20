import pymysql
import numpy as np
import json
import os
from pathlib import Path
from datetime import datetime

# Rutas
NPZ_FILE = "d:/faceCardV2/dataset/embeddings_db.npz"
DATASET_DIR = "d:/faceCardV2/dataset"

# Configuración MySQL
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'facecardv2',
    'port': 3306
}

def sync_db():
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    
    print("Sincronizando estudiantes desde embeddings_db.npz y dataset local a Laravel SQLite...")
    
    # 1. Cargar desde NPZ
    if os.path.exists(NPZ_FILE):
        try:
            data = np.load(NPZ_FILE, allow_pickle=True)
            codigos = []
            nombres = []
            
            # Buscar claves (adaptado de registro.py)
            code_key, name_key = None, None
            for key in data.files:
                if 'code' in key.lower() or 'cod' in key.lower():
                    code_key = key
                elif 'name' in key.lower() or 'nombre' in key.lower():
                    name_key = key
            
            if code_key and name_key:
                codigos = data[code_key]
                nombres = data[name_key]
                
                for i in range(min(len(codigos), len(nombres))):
                    codigo = str(codigos[i]).strip()
                    nombre = str(nombres[i]).strip()
                    
                    if not codigo: continue
                    if not nombre: nombre = codigo
                    
                    # Insertar en MySQL
                    cursor.execute("SELECT id FROM personas WHERE codigo = %s", (codigo,))
                    if not cursor.fetchone():
                        now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                        cursor.execute("""
                            INSERT INTO personas (codigo, nombre, cargo, tiene_embedding, created_at, updated_at)
                            VALUES (%s, %s, 'estudiante', 1, %s, %s)
                        """, (codigo, nombre, now, now))
                        print(f"Insertado NPZ: {codigo} - {nombre}")
                        
            # Si el NPZ es un diccionario con codigos
            else:
                for key in data.files:
                    content = data[key]
                    if content.dtype == object:
                        try:
                            items = content.tolist()
                            if isinstance(items, dict):
                                for k, v in items.items():
                                    codigo = str(k).strip()
                                    if not codigo: continue
                                    
                                    nombre = codigo
                                    if isinstance(v, dict):
                                        nombre = str(v.get("nombre", v.get("name", codigo))).strip()
                                        
                                    cursor.execute("SELECT id FROM personas WHERE codigo = %s", (codigo,))
                                    if not cursor.fetchone():
                                        now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                                        cursor.execute("""
                                            INSERT INTO personas (codigo, nombre, cargo, tiene_embedding, created_at, updated_at)
                                            VALUES (%s, %s, 'estudiante', 1, %s, %s)
                                        """, (codigo, nombre, now, now))
                                        print(f"Insertado NPZ (Dict): {codigo} - {nombre}")
                        except:
                            pass
        except Exception as e:
            print(f"Error cargando NPZ: {e}")

    # 2. Cargar desde dataset local
    dataset_path = Path(DATASET_DIR)
    if dataset_path.exists():
        for student_dir in dataset_path.iterdir():
            if not student_dir.is_dir(): continue
            
            meta_file = student_dir / "metadata.json"
            if meta_file.exists():
                with open(meta_file, "r", encoding="utf-8", errors="ignore") as f:
                    meta = json.load(f)
                    codigo = meta.get("codigo", student_dir.name)
                    nombre = meta.get("nombre", codigo)
                    
                    cursor.execute("SELECT id FROM personas WHERE codigo = %s", (codigo,))
                    if not cursor.fetchone():
                        now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                        cursor.execute("""
                            INSERT INTO personas (codigo, nombre, cargo, tiene_embedding, created_at, updated_at)
                            VALUES (%s, %s, 'estudiante', 1, %s, %s)
                        """, (codigo, nombre, now, now))
                        print(f"Insertado Local: {codigo} - {nombre}")

    conn.commit()
    conn.close()
    print("Sincronización completada con éxito.")

if __name__ == "__main__":
    sync_db()
