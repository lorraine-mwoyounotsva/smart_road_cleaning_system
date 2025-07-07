from flask import Flask, request, jsonify, make_response
import pandas as pd
from datetime import datetime
import os

app = Flask(__name__)

@app.route('/api/export', methods=['POST'])
def export_data():
    try:
        # Get data from request
        data = request.json.get('data')
        if not data:
            return jsonify({"error": "No data provided"}), 400
            
        export_type = request.json.get('type', 'summary')
        
        # Convert to DataFrame
        df = pd.DataFrame(data)
        
        # Generate filename with timestamp
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{export_type}_report_{timestamp}.csv"
        
        # Convert to CSV
        csv_data = df.to_csv(index=False)
        
        # Create response
        response = make_response(csv_data)
        response.headers['Content-Disposition'] = f"attachment; filename={filename}"
        response.headers['Content-Type'] = 'text/csv'
        
        return response
        
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/')
def home():
    return "Python Export API is running!"

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=True)