import uvicorn
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional
import cv2
import numpy as np
import base64

import config
from enrollment import EnrollmentPipeline
from registro import SimpleFaceRecognizer

app = FastAPI(title="FaceCardV2 API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize Pipelines
print("Inicializando pipelines de FaceCardV2...")
pipeline = EnrollmentPipeline(device=config.DEVICE)
recognizer = SimpleFaceRecognizer()
recognizer.build_database()
print("Pipelines listos.")

class EnrollRequest(BaseModel):
    codigo: str
    nombre: str
    images: List[str] # List of base64 images

class RecognizeRequest(BaseModel):
    image: str # Base64 image

def decode_base64_image(base64_str: str) -> np.ndarray:
    if ',' in base64_str:
        base64_str = base64_str.split(',')[1]
    img_data = base64.b64decode(base64_str)
    nparr = np.frombuffer(img_data, np.uint8)
    return cv2.imdecode(nparr, cv2.IMREAD_COLOR)

@app.post("/api/enroll")
async def enroll(req: EnrollRequest):
    if not req.images:
        raise HTTPException(status_code=400, detail="No images provided")
    
    results = {
        "processed": 0,
        "rejected": 0,
        "details": []
    }
    
    for idx, b64_img in enumerate(req.images):
        try:
            image = decode_base64_image(b64_img)
            result = pipeline.enroll_image(req.codigo, req.nombre, image, save_raw=True)
            if result["success"]:
                results["processed"] += 1
            else:
                results["rejected"] += 1
                
            # Convertir a tipos nativos para evitar error JSON
            clean_result = {
                "success": bool(result.get("success", False)),
                "message": str(result.get("message", ""))
            }
            results["details"].append(clean_result)
        except Exception as e:
            results["rejected"] += 1
            results["details"].append({"success": False, "message": str(e)})
            
    # Verify enrollment status
    verification = pipeline.dataset.verify_student(req.codigo)
    results["status"] = str(verification.get("status", ""))
    
    # Reload recognizer database to include the new person
    recognizer.build_database()
    
    return results

@app.post("/api/recognize")
async def recognize(req: RecognizeRequest):
    try:
        image = decode_base64_image(req.image)
        res = recognizer.recognize_faces(image, max_faces=10)
        
        matches = []
        if res.get("success"):
            for item in res.get("results", []):
                score = item.get("score", 0.0)
                best_student = item.get("best_student")
                if best_student:
                    det = item.get("detection")
                    if det:
                        x1, y1, x2, y2 = det.bbox
                        matches.append({
                            "codigo": str(best_student.get("codigo", "")),
                            "nombre": str(best_student.get("nombre", "")),
                            "confianza": float(score),
                            "bbox": [int(x1), int(y1), int(x2), int(y2)]
                        })
        return {"found": len(matches) > 0, "matches": matches}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8889)
