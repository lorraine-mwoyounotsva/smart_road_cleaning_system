from fastapi import FastAPI
from fastapi.responses import JSONResponse
from datetime import datetime, timedelta
import random

app = FastAPI()

# Mock data
routes = [
    {"id": 1, "name": "Windhoek West", "status": "completed"},
    {"id": 2, "name": "Independence Avenue", "status": "in-progress"},
    {"id": 3, "name": "Eros", "status": "missed"}
]

@app.get("/api/cleaned_today")
async def get_cleaned_today():
    cleaned = [r for r in routes if r['status'] == 'completed']
    return JSONResponse({"cleaned_routes": cleaned})

@app.get("/api/missed_routes")
async def get_missed_routes():
    missed = [r for r in routes if r['status'] == 'missed']
    return JSONResponse({"missed_routes": missed})

@app.get("/api/predict_alerts")
async def predict_alerts():
    alerts = []
    if random.random() > 0.7:
        alerts.append({"type": "weather", "message": "Heavy rain expected tomorrow"})
    if random.random() > 0.8:
        alerts.append({"type": "equipment", "message": "2 brooms need replacement"})
    return JSONResponse({"alerts": alerts})

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)