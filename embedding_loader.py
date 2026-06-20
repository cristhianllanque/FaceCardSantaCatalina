import os
import numpy as np


class ExternalEmbeddingLoader:
    def __init__(self, npz_path="dataset/embeddings_db.npz"):
        self.npz_path = npz_path

    def inspect(self):
        if not os.path.exists(self.npz_path):
            print(f"[ERROR] No existe: {self.npz_path}")
            return

        data = np.load(self.npz_path, allow_pickle=True)
        print("[INFO] Claves encontradas en el NPZ:")
        for key in data.files:
            value = data[key]
            print(f" - {key}: shape={getattr(value, 'shape', None)}, dtype={value.dtype}")

    def load_students_db(self):
        if not os.path.exists(self.npz_path):
            print(f"[WARN] No se encontró embeddings externo: {self.npz_path}")
            return []

        data = np.load(self.npz_path, allow_pickle=True)

        students_db = []

        # Caso común: el NPZ guarda un array/lista de diccionarios
        for key in data.files:
            content = data[key]

            if content.dtype == object:
                items = content.tolist()

                if isinstance(items, dict):
                    items = list(items.values())

                if isinstance(items, list):
                    for item in items:
                        if not isinstance(item, dict):
                            continue

                        codigo = str(item.get("codigo", item.get("code", item.get("id", "")))).strip()
                        nombre = str(item.get("nombre", item.get("name", codigo))).strip()
                        vector = item.get("vector", item.get("embedding", item.get("embeddings", None)))

                        if vector is None or codigo == "":
                            continue

                        vector = np.array(vector, dtype=np.float32)

                        if vector.ndim > 1:
                            vector = np.mean(vector, axis=0)

                        norm = np.linalg.norm(vector)
                        if norm > 0:
                            vector = vector / norm

                        students_db.append({
                            "codigo": codigo,
                            "nombre": nombre,
                            "vector": vector,
                            "samples": 1,
                            "source": "external_npz"
                        })

        print(f"[INFO] Embeddings externos cargados: {len(students_db)}")
        return students_db