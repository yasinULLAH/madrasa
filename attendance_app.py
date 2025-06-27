# attendance_app.py
from flask import Flask, request, jsonify
from flask_cors import CORS
import os
from datetime import datetime

# --- Configuration ---
ATTENDANCE_LOG_FILE = 'attendance_log.csv'
app = Flask(__name__)

# --- THIS IS THE FIX ---
# We now allow requests from the origin 'null' (for local files)
# AND 'http://localhost' and 'http://127.0.0.1' (for local servers).
# The port number is wildcarded with '*' to be flexible.
CORS(app, resources={r"/*": {"origins": ["null", "http://localhost:*", "http://127.0.0.1:*"]}}) 
# --- END OF FIX ---

# Keep track of IDs scanned today to prevent duplicates
todays_scans = set()

def load_todays_scans():
    """Load IDs that have already been scanned today from the log file."""
    global todays_scans
    todays_scans.clear()
    today_str = datetime.now().strftime('%Y-%m-%d')
    if not os.path.exists(ATTENDANCE_LOG_FILE):
        return
    with open(ATTENDANCE_LOG_FILE, 'r') as f:
        for line in f:
            try:
                student_id, date, _ = line.strip().split(',')
                if date == today_str:
                    todays_scans.add(student_id)
            except ValueError:
                continue
    print(f"Loaded {len(todays_scans)} scans from today's log.")

@app.route('/mark_attendance', methods=['POST'])
def mark_attendance():
    """Receives a student ID and logs it if not already scanned today."""
    data = request.get_json()
    student_id = data.get('student_id')

    if not student_id:
        return jsonify({"success": False, "message": "Student ID is missing."}), 400

    if student_id in todays_scans:
        print(f"Attendance already marked for {student_id} today.")
        return jsonify({"success": False, "message": "Already marked present today."}), 208

    now = datetime.now()
    log_entry = f"{student_id},{now.strftime('%Y-%m-%d')},{now.strftime('%H:%M:%S')}\n"

    with open(ATTENDANCE_LOG_FILE, 'a') as f:
        f.write(log_entry)
    
    todays_scans.add(student_id)
    print(f"Successfully marked attendance for {student_id}")
    return jsonify({"success": True, "message": "Attendance marked successfully."})

@app.route('/get_todays_attendance', methods=['GET'])
def get_todays_attendance():
    """Returns a list of all IDs scanned today."""
    return jsonify({"success": True, "scanned_ids": list(todays_scans)})

if __name__ == '__main__':
    load_todays_scans()
    app.run(port=8000, debug=True)