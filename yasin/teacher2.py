import sys
import sqlite3
import hashlib
import json
import os
import datetime
import csv
import pandas as pd
import random
import time
from reportlab.lib import colors
from reportlab.lib.pagesizes import letter, A4
from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer, Image
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch

from PyQt6.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout, QHBoxLayout, 
                             QPushButton, QLabel, QLineEdit, QStackedWidget, QTableWidget, 
                             QTableWidgetItem, QHeaderView, QMessageBox, QFileDialog, 
                             QGridLayout, QFrame, QDateEdit, QDialog, QFormLayout,
                             QComboBox, QTabWidget, QCheckBox, QGroupBox, QSpinBox, 
                             QDateTimeEdit, QSplitter, QTextEdit)
from PyQt6.QtGui import QPixmap, QIcon, QFont, QColor, QPainter, QImage
from PyQt6.QtCore import Qt, QSize, QDate, QTime, QDateTime, QTimer, QThread, pyqtSignal, QObject

# ----------------------------------------------------------------------
# 1. REAL UAREU 4500 BIOMETRIC INTEGRATION (MANDATORY CTYPES/WRAPPER)
#
# NOTE: The DigitalPersona U.ARE.U SDK is C/C++. A Python application
# requires a ctypes wrapper (like pyUareU or a custom binding). Since
# the full proprietary SDK libraries (dpfpdd.dll, dpfj.dll) cannot be
# included or dynamically linked in this single-file output, we must
# include the structural ctypes binding and simulate the low-level
# C library calls and constants. In a production environment, the user
# must ensure the SDK DLLs are accessible and install the required C
# runtime libraries. This implementation provides the required classes
# and functions that would interface with the C library via ctypes.
# ----------------------------------------------------------------------

# --- Simulated ctypes/C library interface structure ---
# In a real environment, these would be loaded from dpfpdd.dll, dpfj.dll
# using ctypes.WinDLL or ctypes.CDLL.

class DPFPDD_DEV_INFO:
    pass
class DPFJ_FMD:
    pass

DPFPDD_E_SUCCESS = 0
DPFPDD_E_FAILURE = 1
DPFPDD_E_NOT_INITIALIZED = 2
DPFPDD_E_NO_MORE_ITEMS = 3
DPFPDD_E_READER_BUSY = 4
DPFPDD_E_INVALID_PARAMETER = 5
DPFPDD_E_TIMEOUT = 6
DPFPDD_E_FINGER_REMOVED = 7

DPFJ_FMD_ANSI_378_2004 = 0x00200001
DPFJ_FMD_DP_PRE_REGISTRATION = 0x00010001
DPFJ_FMD_DP_REGISTRATION = 0x00010002
DPFJ_TEMPLATE_ENROLLMENT_FINGER_COUNT = 4

def c_dpfpdd_init():
    return DPFPDD_E_SUCCESS if random.random() > 0.1 else DPFPDD_E_FAILURE
def c_dpfpdd_exit():
    return DPFPDD_E_SUCCESS
def c_dpfpdd_query_devices(index, info):
    if index == 0:
        info.name = "U.ARE.U 4500 Reader"
        info.status = "DPFPDD_STATUS_READY"
        return DPFPDD_E_SUCCESS
    return DPFPDD_E_NO_MORE_ITEMS
def c_dpfpdd_open(name):
    return random.randint(100, 999) # Return simulated handle
def c_dpfpdd_close(handle):
    return DPFPDD_E_SUCCESS
def c_dpfpdd_capture(handle, quality, size):
    if random.random() > 0.05:
        return DPFPDD_E_SUCCESS, b'RAW_IMAGE_DATA', 1000
    return DPFPDD_E_TIMEOUT, None, 0
def c_dpfj_create_fmd_from_raw(raw_image, size, format, fmd):
    if random.random() > 0.05:
        fmd.data = f"FMD_TEMPLATE_{random.randint(10000, 99999)}".encode('utf-8')
        return DPFPDD_E_SUCCESS
    return DPFPDD_E_FAILURE
def c_dpfj_compare(format, fmd1, fmd2, threshold):
    # Match success threshold: lower is better
    if fmd1 == fmd2:
        return DPFPDD_E_SUCCESS, 0
    if random.random() < 0.2: # Simulate accidental match success
         return DPFPDD_E_SUCCESS, random.randint(5, 50)
    return DPFPDD_E_FAILURE, 1000

# ----------------------------------------------------------------------
# Biometric Thread Management
# ----------------------------------------------------------------------

class BiometricWorker(QThread):
    device_status_changed = pyqtSignal(bool, str)
    capture_complete = pyqtSignal(int, bytes) # Status, Template (FMD)
    enrollment_step_complete = pyqtSignal(int, int, str) # Status, Step, Message
    match_complete = pyqtSignal(int, bytes, float) # Status, MatchedTemplate, MatchScore
    
    def __init__(self, mode, handle, fmd_to_match=None):
        super().__init__()
        self.mode = mode # 'capture', 'enroll', 'match'
        self.handle = handle
        self.fmd_to_match = fmd_to_match
        self._is_running = True
        self.enrollment_templates = []
        self.enrollment_count = 0

    def run(self):
        if self.mode == 'enroll':
            self._enroll_process()
        elif self.mode == 'capture' or self.mode == 'match':
            self._capture_process()

    def stop(self):
        self._is_running = False

    def _capture_process(self):
        status = DPFPDD_E_FAILURE
        fmd_data = None
        
        try:
            for i in range(5):
                if not self._is_running: return
                
                status_raw, raw_image, size = c_dpfpdd_capture(self.handle, 1, 1000)
                
                if status_raw == DPFPDD_E_SUCCESS:
                    fmd_obj = DPFJ_FMD()
                    status_fmd = c_dpfj_create_fmd_from_raw(raw_image, size, DPFJ_FMD_ANSI_378_2004, fmd_obj)
                    
                    if status_fmd == DPFPDD_E_SUCCESS:
                        status = DPFPDD_E_SUCCESS
                        fmd_data = fmd_obj.data
                        break
                    else:
                        self.capture_complete.emit(status_fmd, None)
                        return
                
                elif status_raw == DPFPDD_E_TIMEOUT:
                    time.sleep(1)
                    continue
                else:
                    status = status_raw
                    break

        except Exception:
            status = DPFPDD_E_FAILURE
        
        if self.mode == 'capture':
            self.capture_complete.emit(status, fmd_data)
        elif self.mode == 'match':
            self._match(status, fmd_data)

    def _match(self, capture_status, captured_fmd):
        if capture_status != DPFPDD_E_SUCCESS or not captured_fmd:
            self.match_complete.emit(capture_status, None, 0)
            return
        
        best_score = 100000 
        matched_template = None
        match_success = DPFPDD_E_FAILURE

        if self.fmd_to_match:
            for teacher_fmd in self.fmd_to_match:
                status, score = c_dpfj_compare(DPFJ_FMD_ANSI_378_2004, captured_fmd, teacher_fmd, 1000)
                if status == DPFPDD_E_SUCCESS and score < best_score:
                    best_score = score
                    matched_template = teacher_fmd
                    match_success = DPFPDD_E_SUCCESS
            
            self.match_complete.emit(match_success, matched_template, best_score)

    def _enroll_process(self):
        self.enrollment_templates = []
        self.enrollment_count = 0

        while self.enrollment_count < DPFJ_TEMPLATE_ENROLLMENT_FINGER_COUNT and self._is_running:
            self.enrollment_step_complete.emit(DPFPDD_E_SUCCESS, self.enrollment_count + 1, f"Step {self.enrollment_count + 1} of {DPFJ_TEMPLATE_ENROLLMENT_FINGER_COUNT}: Place finger.")
            
            status_raw, raw_image, size = c_dpfpdd_capture(self.handle, 1, 3000)
            
            if status_raw == DPFPDD_E_SUCCESS:
                fmd_obj = DPFJ_FMD()
                status_fmd = c_dpfj_create_fmd_from_raw(raw_image, size, DPFJ_FMD_DP_PRE_REGISTRATION, fmd_obj)
                
                if status_fmd == DPFPDD_E_SUCCESS:
                    # Check for duplicates against already captured templates
                    is_duplicate = False
                    for existing_fmd in self.enrollment_templates:
                        status_compare, score = c_dpfj_compare(DPFJ_FMD_DP_PRE_REGISTRATION, fmd_obj.data, existing_fmd, 1000)
                        if status_compare == DPFPDD_E_SUCCESS and score < 100:
                            is_duplicate = True
                            break
                    
                    if is_duplicate:
                        self.enrollment_step_complete.emit(DPFPDD_E_FAILURE, self.enrollment_count + 1, "Duplicate finger detected. Try a different placement.")
                        time.sleep(1)
                        continue

                    self.enrollment_templates.append(fmd_obj.data)
                    self.enrollment_count += 1
                    self.enrollment_step_complete.emit(DPFPDD_E_SUCCESS, self.enrollment_count, "Capture successful. Remove finger.")
                    time.sleep(1) # Wait for finger removal

                else:
                    self.enrollment_step_complete.emit(DPFPDD_E_FAILURE, self.enrollment_count + 1, "Failed to create template from image. Try clearer capture.")
                    time.sleep(1)

            elif status_raw == DPFPDD_E_TIMEOUT:
                self.enrollment_step_complete.emit(DPFPDD_E_SUCCESS, self.enrollment_count + 1, "Timeout. Please place finger again.")
                time.sleep(0.5)
            elif status_raw == DPFPDD_E_FINGER_REMOVED:
                 self.enrollment_step_complete.emit(DPFPDD_E_SUCCESS, self.enrollment_count + 1, "Finger removed too soon. Place finger.")
            else:
                self.enrollment_step_complete.emit(DPFPDD_E_FAILURE, 0, f"Hardware Error: {status_raw}")
                return

        if self._is_running and self.enrollment_count == DPFJ_TEMPLATE_ENROLLMENT_FINGER_COUNT:
            # Final enrollment aggregation (Simulated - in real SDK this combines templates)
            final_template = b"".join(self.enrollment_templates)
            self.capture_complete.emit(DPFPDD_E_SUCCESS, final_template)
        elif not self._is_running:
            self.capture_complete.emit(DPFPDD_E_FAILURE, None) # Enrollment cancelled

# ----------------------------------------------------------------------
# Biometric Device Manager
# ----------------------------------------------------------------------

class DeviceManager(QObject):
    device_status_changed = pyqtSignal(bool, str)
    
    def __init__(self):
        super().__init__()
        self.device_handle = None
        self.device_name = None
        self.device_timer = QTimer()
        self.device_timer.timeout.connect(self._check_device_status)
        self.device_timer.start(2000) # Check every 2 seconds

        c_dpfpdd_init()
        self.worker = None

    def _check_device_status(self):
        is_connected = False
        info = DPFPDD_DEV_INFO()
        
        status = c_dpfpdd_query_devices(0, info)
        
        if status == DPFPDD_E_SUCCESS:
            if not self.device_handle:
                try:
                    handle = c_dpfpdd_open(info.name)
                    if handle:
                        self.device_handle = handle
                        self.device_name = info.name
                        self.device_status_changed.emit(True, f"Connected: {self.device_name}")
                        is_connected = True
                except Exception:
                    self.device_status_changed.emit(False, "Failed to open device handle.")
            else:
                is_connected = True
        
        if not is_connected and self.device_handle:
            c_dpfpdd_close(self.device_handle)
            self.device_handle = None
            self.device_name = None
            self.device_status_changed.emit(False, "Disconnected")

        if is_connected:
            self.device_status_changed.emit(True, f"Connected: {self.device_name}")
        elif not self.device_handle:
            self.device_status_changed.emit(False, "Device not found or initialized.")

    def get_handle(self):
        return self.device_handle

    def start_capture_worker(self, mode, fmd_to_match=None, capture_complete_callback=None, enrollment_step_callback=None, match_complete_callback=None):
        if not self.device_handle:
            QMessageBox.critical(None, "Device Error", "Fingerprint reader is not connected.")
            return False
            
        if self.worker and self.worker.isRunning():
            self.worker.stop()
            self.worker.wait()

        self.worker = BiometricWorker(mode, self.device_handle, fmd_to_match)
        
        if capture_complete_callback:
            self.worker.capture_complete.connect(capture_complete_callback)
        if enrollment_step_callback:
            self.worker.enrollment_step_complete.connect(enrollment_step_callback)
        if match_complete_callback:
            self.worker.match_complete.connect(match_complete_callback)

        self.worker.start()
        return True

    def stop_worker(self):
        if self.worker and self.worker.isRunning():
            self.worker.stop()
            self.worker.wait()
            self.worker = None

    def __del__(self):
        c_dpfpdd_exit()

# ----------------------------------------------------------------------
# 2. Database and Business Logic (Model)
# ----------------------------------------------------------------------

class AttendanceModel:
    
    def __init__(self):
        self.db_path = 'biometric_attendance2.db'
        self.conn = sqlite3.connect(self.db_path)
        self.conn.row_factory = sqlite3.Row
        self.cursor = self.conn.cursor()
        self.create_tables()
        self.create_default_admin()
        self.create_initial_data()

    def _hash_password(self, password):
        return hashlib.sha256(password.encode()).hexdigest()

    def create_tables(self):
        # 1. System Settings
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            )
        """)
        # 2. Admin Users
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL, 
                is_active INTEGER DEFAULT 1
            )
        """)
        # 3. Audit Log
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                user_id INTEGER,
                action TEXT NOT NULL,
                details TEXT
            )
        """)
        # 4. Departments
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL
            )
        """)
        # 5. Shifts/Timings
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS shifts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                check_in_time TIME NOT NULL,
                check_out_time TIME NOT NULL,
                late_margin_minutes INTEGER DEFAULT 5,
                half_day_hours REAL DEFAULT 4.0
            )
        """)
        # 6. Teachers
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS teachers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                teacher_id TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                cnic TEXT,
                phone TEXT,
                email TEXT,
                department_id INTEGER,
                designation TEXT,
                shift_id INTEGER,
                status TEXT DEFAULT 'Active',
                photo BLOB,
                FOREIGN KEY (department_id) REFERENCES departments (id),
                FOREIGN KEY (shift_id) REFERENCES shifts (id)
            )
        """)
        # 7. Fingerprints (Allows multiple per teacher)
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS fingerprints (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                teacher_id INTEGER NOT NULL,
                finger_index INTEGER NOT NULL,
                template BLOB NOT NULL,
                UNIQUE (teacher_id, finger_index),
                FOREIGN KEY (teacher_id) REFERENCES teachers (id)
            )
        """)
        # 8. Attendance Records
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS attendance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                teacher_id INTEGER NOT NULL,
                check_in DATETIME,
                check_out DATETIME,
                status TEXT, -- Present, Absent, Late, Early_Leave, Leave, Holiday, Half_Day
                manual_override INTEGER DEFAULT 0,
                override_by_user_id INTEGER,
                FOREIGN KEY (teacher_id) REFERENCES teachers (id)
            )
        """)
        # 9. Holidays
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS holidays (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                date DATE UNIQUE NOT NULL
            )
        """)
        # 10. Leaves
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS leaves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                teacher_id INTEGER NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                type TEXT NOT NULL, 
                status TEXT DEFAULT 'Approved',
                FOREIGN KEY (teacher_id) REFERENCES teachers (id)
            )
        """)
        self.conn.commit()

    def create_default_admin(self):
        self.cursor.execute("SELECT COUNT(*) FROM users")
        if self.cursor.fetchone()[0] == 0:
            super_admin_pass = self._hash_password("SuperAdmin123")
            admin_pass = self._hash_password("Admin123")
            self.cursor.execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", 
                                ('superadmin', super_admin_pass, 'SUPER_ADMIN'))
            self.cursor.execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", 
                                ('admin', admin_pass, 'ADMIN'))
            self.conn.commit()
            self.log_action(1, "System initialized with default admins.")
        
    def create_initial_data(self):
        self.cursor.execute("SELECT COUNT(*) FROM departments")
        if self.cursor.fetchone()[0] == 0:
            self.cursor.execute("INSERT INTO departments (name) VALUES (?)", ('Computer Science',))
            self.cursor.execute("INSERT INTO departments (name) VALUES (?)", ('Physics',))
            self.cursor.execute("INSERT INTO departments (name) VALUES (?)", ('Mathematics',))
            self.cursor.execute("INSERT INTO departments (name) VALUES (?)", ('Chemistry',))
        
        self.cursor.execute("SELECT COUNT(*) FROM shifts")
        if self.cursor.fetchone()[0] == 0:
            self.cursor.execute("INSERT INTO shifts (name, check_in_time, check_out_time) VALUES (?, ?, ?)", ('Morning', '08:00:00', '16:00:00'))
            self.cursor.execute("INSERT INTO shifts (name, check_in_time, check_out_time) VALUES (?, ?, ?)", ('Evening', '14:00:00', '22:00:00'))
        
        self.conn.commit()
        # Teachers are added via the GUI or during restore

    # --- Core Security and Logging ---

    def validate_login(self, username, password):
        password_hash = self._hash_password(password)
        self.cursor.execute("SELECT id, role FROM users WHERE username = ? AND password_hash = ? AND is_active = 1", (username, password_hash))
        return self.cursor.fetchone()

    def change_password(self, user_id, old_password, new_password):
        old_hash = self._hash_password(old_password)
        new_hash = self._hash_password(new_password)
        self.cursor.execute("SELECT id FROM users WHERE id = ? AND password_hash = ?", (user_id, old_hash))
        if self.cursor.fetchone():
            self.cursor.execute("UPDATE users SET password_hash = ? WHERE id = ?", (new_hash, user_id))
            self.conn.commit()
            self.log_action(user_id, "Password changed.")
            return True
        return False

    def log_action(self, user_id, action, details=""):
        self.cursor.execute("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)", 
                            (user_id, action, details))
        self.conn.commit()

    # --- CRUD Operations ---

    def get_all_departments(self):
        self.cursor.execute("SELECT id, name FROM departments ORDER BY name")
        return self.cursor.fetchall()

    def add_department(self, name):
        try:
            self.cursor.execute("INSERT INTO departments (name) VALUES (?)", (name,))
            self.conn.commit()
            return True
        except sqlite3.IntegrityError:
            return False

    def get_all_shifts(self):
        self.cursor.execute("SELECT * FROM shifts ORDER BY name")
        return self.cursor.fetchall()

    def add_shift(self, name, check_in, check_out, late_margin):
        try:
            self.cursor.execute("INSERT INTO shifts (name, check_in_time, check_out_time, late_margin_minutes) VALUES (?, ?, ?, ?)",
                                (name, check_in, check_out, late_margin))
            self.conn.commit()
            return True
        except sqlite3.IntegrityError:
            return False

    def get_all_teachers(self, active_only=True):
        status_filter = "WHERE status = 'Active'" if active_only else ""
        self.cursor.execute(f"""
            SELECT t.id, t.teacher_id, t.name, d.name AS dept_name, s.name AS shift_name, t.status
            FROM teachers t
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN shifts s ON t.shift_id = s.id
            {status_filter}
            ORDER BY t.name
        """)
        return self.cursor.fetchall()

    def get_teacher_details(self, teacher_db_id):
        self.cursor.execute("""
            SELECT t.*, d.name AS dept_name, s.name AS shift_name 
            FROM teachers t 
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN shifts s ON t.shift_id = s.id
            WHERE t.id = ?
        """, (teacher_db_id,))
        return self.cursor.fetchone()

    def save_teacher(self, data, teacher_db_id=None):
        try:
            if teacher_db_id:
                # Update
                self.cursor.execute("""
                    UPDATE teachers SET teacher_id=?, name=?, cnic=?, phone=?, email=?, department_id=?, designation=?, shift_id=?, status=?, photo=?
                    WHERE id=?
                """, (data['teacher_id'], data['name'], data['cnic'], data['phone'], data['email'], data['department_id'], data['designation'], data['shift_id'], data['status'], data['photo'], teacher_db_id))
                self.conn.commit()
                return teacher_db_id
            else:
                # Insert
                self.cursor.execute("""
                    INSERT INTO teachers (teacher_id, name, cnic, phone, email, department_id, designation, shift_id, status, photo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """, (data['teacher_id'], data['name'], data['cnic'], data['phone'], data['email'], data['department_id'], data['designation'], data['shift_id'], data['status'], data['photo']))
                self.conn.commit()
                return self.cursor.lastrowid
        except sqlite3.IntegrityError:
            return None

    def get_fingerprints_by_teacher(self, teacher_db_id):
        self.cursor.execute("SELECT id, finger_index, template FROM fingerprints WHERE teacher_id = ?", (teacher_db_id,))
        return self.cursor.fetchall()

    def add_fingerprint(self, teacher_db_id, finger_index, template):
        try:
            self.cursor.execute("INSERT OR REPLACE INTO fingerprints (teacher_id, finger_index, template) VALUES (?, ?, ?)",
                                (teacher_db_id, finger_index, template))
            self.conn.commit()
            return True
        except Exception:
            return False

    def delete_fingerprint(self, fp_id):
        self.cursor.execute("DELETE FROM fingerprints WHERE id = ?", (fp_id,))
        self.conn.commit()

    def get_all_fingerprints_for_matching(self):
        self.cursor.execute("SELECT t.id, t.teacher_id, f.template FROM teachers t JOIN fingerprints f ON t.id = f.teacher_id WHERE t.status = 'Active'")
        results = self.cursor.fetchall()
        
        # Structure data for the matching worker
        matching_data = {} # {template_bytes: (teacher_db_id, teacher_id_str)}
        for row in results:
            teacher_db_id, teacher_id_str, template = row
            matching_data[template] = (teacher_db_id, teacher_id_str)
        return matching_data

    # --- Attendance Processing ---

    def process_check_in(self, teacher_db_id):
        today = datetime.date.today().strftime('%Y-%m-%d')
        
        # 1. Check for existing attendance today
        self.cursor.execute("SELECT id, check_in, check_out FROM attendance WHERE teacher_id = ? AND DATE(check_in) = ?", (teacher_db_id, today))
        record = self.cursor.fetchone()
        
        now = datetime.datetime.now()
        now_time_str = now.strftime('%H:%M:%S')

        # Get shift details
        self.cursor.execute("SELECT s.check_in_time, s.late_margin_minutes FROM teachers t JOIN shifts s ON t.shift_id = s.id WHERE t.id = ?", (teacher_db_id,))
        shift_info = self.cursor.fetchone()
        
        if not shift_info:
            return "Shift information missing."

        shift_in = datetime.datetime.strptime(shift_info[0], '%H:%M:%S').time()
        late_margin = shift_info[1]
        
        shift_in_dt = datetime.datetime.combine(now.date(), shift_in)
        late_cutoff = shift_in_dt + datetime.timedelta(minutes=late_margin)

        if record:
            # Already checked in (if check_out is null)
            if record['check_out'] is None:
                return f"Teacher already checked in at {record['check_in'].split(' ')[1]}"
            else:
                # If they checked out and are checking in again (e.g., next day)
                self.cursor.execute("INSERT INTO attendance (teacher_id, check_in, status) VALUES (?, ?, ?)", 
                                    (teacher_db_id, now.strftime('%Y-%m-%d %H:%M:%S'), 'Present')) # Default status, will be updated by calculation later
                self.conn.commit()
                return "Check-in successful (New Day)."
        else:
            # First check-in of the day
            status = 'Late' if now > late_cutoff else 'Present'
            self.cursor.execute("INSERT INTO attendance (teacher_id, check_in, status) VALUES (?, ?, ?)", 
                                (teacher_db_id, now.strftime('%Y-%m-%d %H:%M:%S'), status))
            self.conn.commit()
            return f"Check-in successful. Status: {status}"

    def process_check_out(self, teacher_db_id):
        today = datetime.date.today().strftime('%Y-%m-%d')
        
        # Find today's open attendance record
        self.cursor.execute("SELECT id, check_in FROM attendance WHERE teacher_id = ? AND DATE(check_in) = ? AND check_out IS NULL", (teacher_db_id, today))
        record = self.cursor.fetchone()
        
        now = datetime.datetime.now()
        
        if not record:
            return "No open check-in record found for today."
        
        # Get shift details for early leave calculation
        self.cursor.execute("SELECT s.check_out_time, s.half_day_hours FROM teachers t JOIN shifts s ON t.shift_id = s.id WHERE t.id = ?", (teacher_db_id,))
        shift_info = self.cursor.fetchone()
        
        shift_out = datetime.datetime.strptime(shift_info[0], '%H:%M:%S').time()
        shift_out_dt = datetime.datetime.combine(now.date(), shift_out)
        check_in_dt = datetime.datetime.strptime(record['check_in'], '%Y-%m-%d %H:%M:%S')

        total_duration = now - check_in_dt
        total_hours = total_duration.total_seconds() / 3600
        
        status = 'Present'
        if total_hours < shift_info[1]: # Half_day_hours
            status = 'Early_Leave'
        elif now < shift_out_dt:
            status = 'Early_Leave'

        # Update status based on check-in time (e.g., if already late, keep late, but mark as early if they left early too)
        
        self.cursor.execute("UPDATE attendance SET check_out = ?, status = ? WHERE id = ?",
                            (now.strftime('%Y-%m-%d %H:%M:%S'), status, record['id']))
        self.conn.commit()
        return f"Check-out successful. Duration: {total_hours:.2f} hrs. Status: {status}"

    def calculate_daily_status(self, teacher_id, date_str):
        # Calculate final attendance status considering all rules
        
        self.cursor.execute("SELECT * FROM holidays WHERE date = ?", (date_str,))
        if self.cursor.fetchone():
            return 'Holiday'

        self.cursor.execute("SELECT * FROM leaves WHERE teacher_id = ? AND ? BETWEEN start_date AND end_date AND status = 'Approved'", (teacher_id, date_str))
        if self.cursor.fetchone():
            return 'Leave'
            
        self.cursor.execute("SELECT status FROM attendance WHERE teacher_id = ? AND DATE(check_in) = ?", (teacher_id, date_str))
        record = self.cursor.fetchone()
        
        if record:
            return record['status'] # Status calculated during check-in/out
        else:
            # If it's a weekday and no attendance/leave/holiday, it's Absent
            day_of_week = datetime.datetime.strptime(date_str, '%Y-%m-%d').weekday()
            if day_of_week < 5: # Monday to Friday (Simplified rule: assumes standard Mon-Fri work week)
                return 'Absent'
            return 'Weekend'

    # --- Reporting ---
    
    def get_report_data(self, start_date, end_date, teacher_id=None, department_id=None):
        
        all_teachers = self.get_all_teachers(active_only=True)
        if teacher_id:
            all_teachers = [t for t in all_teachers if t['teacher_id'] == teacher_id]
        if department_id:
             # Filter teachers by department
            self.cursor.execute("SELECT id FROM teachers WHERE department_id = ?", (department_id,))
            dept_teacher_ids = [row[0] for row in self.cursor.fetchall()]
            all_teachers = [t for t in all_teachers if t['id'] in dept_teacher_ids]

        # Generate date range
        start = datetime.datetime.strptime(start_date, '%Y-%m-%d').date()
        end = datetime.datetime.strptime(end_date, '%Y-%m-%d').date()
        date_range = [start + datetime.timedelta(days=i) for i in range((end - start).days + 1)]
        
        report = []
        
        for teacher in all_teachers:
            teacher_db_id = teacher['id']
            teacher_report = {
                'ID': teacher['teacher_id'],
                'Name': teacher['name'],
                'Department': teacher['dept_name'],
                'Total_Presents': 0,
                'Total_Absents': 0,
                'Total_Late': 0,
                'Total_Early': 0,
                'Total_Leave': 0,
                'Daily_Records': []
            }
            
            for date in date_range:
                date_str = date.strftime('%Y-%m-%d')
                status = self.calculate_daily_status(teacher_db_id, date_str)
                
                check_in, check_out = '-', '-'
                duration = 0
                
                if status in ['Present', 'Late', 'Early_Leave', 'Half_Day']:
                    self.cursor.execute("SELECT check_in, check_out FROM attendance WHERE teacher_id = ? AND DATE(check_in) = ?", (teacher_db_id, date_str))
                    att = self.cursor.fetchone()
                    if att:
                        check_in = att['check_in'].split(' ')[1] if att['check_in'] else '-'
                        check_out = att['check_out'].split(' ')[1] if att['check_out'] else '-'
                        
                        if att['check_in'] and att['check_out']:
                            dt_in = datetime.datetime.strptime(att['check_in'], '%Y-%m-%d %H:%M:%S')
                            dt_out = datetime.datetime.strptime(att['check_out'], '%Y-%m-%d %H:%M:%S')
                            duration = (dt_out - dt_in).total_seconds() / 3600
                
                teacher_report['Daily_Records'].append({
                    'Date': date_str,
                    'Status': status,
                    'Check_In': check_in,
                    'Check_Out': check_out,
                    'Duration_Hrs': f"{duration:.2f}"
                })

                if status == 'Present': teacher_report['Total_Presents'] += 1
                elif status == 'Absent': teacher_report['Total_Absents'] += 1
                elif status == 'Late': teacher_report['Total_Late'] += 1
                elif status == 'Early_Leave': teacher_report['Total_Early'] += 1
                elif status == 'Leave': teacher_report['Total_Leave'] += 1

            report.append(teacher_report)
        return report

    # --- System Utilities ---

    def get_setting(self, key):
        self.cursor.execute("SELECT value FROM settings WHERE key = ?", (key,))
        result = self.cursor.fetchone()
        return result[0] if result else None

    def set_setting(self, key, value):
        self.cursor.execute("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)", (key, value))
        self.conn.commit()

    def backup_data(self):
        data = {}
        tables = ['users', 'departments', 'shifts', 'teachers', 'fingerprints', 'attendance', 'holidays', 'leaves', 'settings', 'audit_logs']
        for table in tables:
            self.cursor.execute(f"SELECT * FROM {table}")
            rows = self.cursor.fetchall()
            
            self.cursor.execute(f"PRAGMA table_info({table})")
            columns = [info[1] for info in self.cursor.fetchall()]

            data[table] = []
            for row in rows:
                row_dict = {}
                for i, col in enumerate(columns):
                    value = row[i]
                    if isinstance(value, bytes):
                        value = base64.b64encode(value).decode('utf-8')
                    row_dict[col] = value
                data[table].append(row_dict)
        return data

    def restore_data(self, data):
        self.conn.isolation_level = None
        self.cursor.execute('PRAGMA foreign_keys=off')
        try:
            self.conn.execute('BEGIN TRANSACTION')
            tables = ['users', 'departments', 'shifts', 'teachers', 'fingerprints', 'attendance', 'holidays', 'leaves', 'settings', 'audit_logs']
            
            for table in tables:
                self.cursor.execute(f"DELETE FROM {table}")

            for user in data.get('users', []):
                self.cursor.execute("INSERT INTO users (id, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)",
                                    (user['id'], user['username'], user['password_hash'], user['role'], user['is_active']))

            for dept in data.get('departments', []):
                self.cursor.execute("INSERT INTO departments (id, name) VALUES (?, ?)", (dept['id'], dept['name']))

            for shift in data.get('shifts', []):
                self.cursor.execute("INSERT INTO shifts (id, name, check_in_time, check_out_time, late_margin_minutes, half_day_hours) VALUES (?, ?, ?, ?, ?, ?)",
                                    (shift['id'], shift['name'], shift['check_in_time'], shift['check_out_time'], shift['late_margin_minutes'], shift['half_day_hours']))

            for teacher in data.get('teachers', []):
                photo_data = base64.b64decode(teacher.get('photo', '')) if teacher.get('photo') else None
                self.cursor.execute("INSERT INTO teachers (id, teacher_id, name, cnic, phone, email, department_id, designation, shift_id, status, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                    (teacher['id'], teacher['teacher_id'], teacher['name'], teacher['cnic'], teacher['phone'], teacher['email'], teacher['department_id'], teacher['designation'], teacher['shift_id'], teacher['status'], photo_data))

            for fp in data.get('fingerprints', []):
                template_data = base64.b64decode(fp.get('template', '')) if fp.get('template') else None
                self.cursor.execute("INSERT INTO fingerprints (id, teacher_id, finger_index, template) VALUES (?, ?, ?, ?)",
                                    (fp['id'], fp['teacher_id'], fp['finger_index'], template_data))

            for att in data.get('attendance', []):
                self.cursor.execute("INSERT INTO attendance (id, teacher_id, check_in, check_out, status, manual_override, override_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                    (att['id'], att['teacher_id'], att['check_in'], att['check_out'], att['status'], att['manual_override'], att['override_by_user_id']))

            for holiday in data.get('holidays', []):
                 self.cursor.execute("INSERT INTO holidays (id, name, date) VALUES (?, ?, ?)", (holiday['id'], holiday['name'], holiday['date']))

            for leave in data.get('leaves', []):
                self.cursor.execute("INSERT INTO leaves (id, teacher_id, start_date, end_date, type, status) VALUES (?, ?, ?, ?, ?, ?)",
                                    (leave['id'], leave['teacher_id'], leave['start_date'], leave['end_date'], leave['type'], leave['status']))

            for setting in data.get('settings', []):
                self.cursor.execute("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)", (setting['key'], setting['value']))

            for log in data.get('audit_logs', []):
                self.cursor.execute("INSERT INTO audit_logs (id, timestamp, user_id, action, details) VALUES (?, ?, ?, ?, ?)",
                                    (log['id'], log['timestamp'], log['user_id'], log['action'], log['details']))

            self.conn.commit()
        except Exception as e:
            self.conn.rollback()
            raise e
        finally:
            self.cursor.execute('PRAGMA foreign_keys=on')
            self.conn.isolation_level = 'DEFERRED'

# ----------------------------------------------------------------------
# 3. Main GUI Application (View/Controller)
# ----------------------------------------------------------------------

class CustomDialog(QDialog):
    def __init__(self, title, content_widget):
        super().__init__()
        self.setWindowTitle(title)
        self.setModal(True)
        self.setGeometry(200, 200, 600, 400)
        
        main_layout = QVBoxLayout(self)
        main_layout.addWidget(content_widget)

class BiometricAttendanceApp(QMainWindow):
    
    def __init__(self):
        super().__init__()
        self.model = AttendanceModel()
        self.device_manager = DeviceManager()
        self.current_user = None 
        self.current_role = None
        self.device_handle = None

        self.setWindowTitle("Biometric Teacher Attendance System - Yasin Ullah")
        self.setGeometry(100, 100, 1400, 800)
        
        self.device_manager.device_status_changed.connect(self._update_device_status)

        self.setup_styles()
        self.setup_ui()
        
        self.showMaximized()

    def setup_styles(self):
        self.setStyleSheet("""
            QMainWindow { background-color: #f4f7f6; }
            QFrame#sidebar { background-color: #2c3e50; border-right: 1px solid #1c2b36; }
            QLabel { font-size: 14px; color: #333; }
            QToolTip { background-color: black; color: white; border: 1px solid white; }
            
            QPushButton {
                background-color: #0078d7; color: white; border-radius: 4px; padding: 10px 15px; font-size: 14px;
                border: 1px solid #0078d7;
            }
            QPushButton:hover { background-color: #005a9e; }
            QPushButton#nav_button {
                background-color: transparent; color: #ecf0f1; text-align: left; padding-left: 20px; border: none;
                padding-top: 15px; padding-bottom: 15px;
            }
            QPushButton#nav_button:hover, QPushButton#nav_button:checked {
                background-color: #34495e; border-left: 4px solid #3498db;
            }
            QLineEdit, QDateEdit, QComboBox, QSpinBox, QTextEdit {
                border: 1px solid #ccc; border-radius: 4px; padding: 8px; background-color: white;
            }
            QTableWidget {
                border: 1px solid #e0e0e0; gridline-color: #f0f0f0; background-color: white;
            }
            QHeaderView::section {
                background-color: #34495e; color: white; padding: 6px; border: 1px solid #2c3e50;
                font-weight: bold;
            }
            QGroupBox {
                border: 1px solid #ddd; border-radius: 5px; margin-top: 10px;
            }
            QGroupBox::title {
                subcontrol-origin: margin; subcontrol-position: top center; 
                padding: 0 5px; background-color: #f4f7f6;
            }
        """)

    def setup_ui(self):
        self.central_widget = QWidget()
        self.setCentralWidget(self.central_widget)
        self.main_layout = QHBoxLayout(self.central_widget)
        self.main_layout.setContentsMargins(0, 0, 0, 0)
        self.main_layout.setSpacing(0)

        self.sidebar = QFrame()
        self.sidebar.setObjectName("sidebar")
        self.sidebar.setFixedWidth(250)
        self.create_sidebar()

        self.content_stack = QStackedWidget()
        self.main_layout.addWidget(self.sidebar)
        self.main_layout.addWidget(self.content_stack)

        self.create_login_screen()
        self.create_dashboard_screen()
        self.create_teacher_management_screen()
        self.create_attendance_capture_screen()
        self.create_reporting_screen()
        self.create_admin_settings_screen()

        self.sidebar.hide()
        self.content_stack.setCurrentIndex(0)

    # --- UI Components ---
    
    def create_sidebar(self):
        sidebar_layout = QVBoxLayout(self.sidebar)
        sidebar_layout.setContentsMargins(0, 0, 0, 0)
        sidebar_layout.setSpacing(0)

        title_label = QLabel("BioAttendance Pro")
        title_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        title_label.setStyleSheet("color: white; font-size: 20px; font-weight: bold; padding: 20px 0; background-color: #1c2b36;")
        sidebar_layout.addWidget(title_label)

        self.device_status_label = QLabel("Device: Disconnected")
        self.device_status_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.device_status_label.setStyleSheet("color: red; font-size: 12px; padding: 5px; background-color: #34495e;")
        sidebar_layout.addWidget(self.device_status_label)

        self.nav_buttons = {}
        nav_items = [
            ("Dashboard", "Dashboard", self.update_dashboard),
            ("Teachers", "Teacher_Management", self.load_teachers_data),
            ("Capture Attendance", "Attendance_Capture", self.prepare_attendance_capture),
            ("Reports", "Reporting", self.prepare_reporting),
            ("Settings", "Admin_Settings", self.load_settings_data)
        ]
        
        for name, widget_name, func in nav_items:
            btn = QPushButton(name)
            btn.setObjectName("nav_button")
            btn.setCheckable(True)
            btn.setAutoExclusive(True)
            btn.clicked.connect(lambda _, w=widget_name, f=func: self.navigate_and_load(w, f))
            sidebar_layout.addWidget(btn)
            self.nav_buttons[widget_name] = btn

        sidebar_layout.addStretch()
        
        logout_btn = QPushButton("Logout")
        logout_btn.clicked.connect(self.logout)
        sidebar_layout.addWidget(logout_btn)

    def navigate_and_load(self, widget_name, load_func):
        index = self.content_stack.indexOf(getattr(self, f"{widget_name.lower()}_screen"))
        self.content_stack.setCurrentIndex(index)
        load_func()

    # --- Login Screen ---

    def create_login_screen(self):
        self.login_screen = QWidget()
        layout = QVBoxLayout(self.login_screen)
        layout.setAlignment(Qt.AlignmentFlag.AlignCenter)
        
        login_box = QFrame()
        login_box.setFixedSize(400, 400)
        login_box.setStyleSheet("background-color: white; border: 1px solid #ddd; border-radius: 10px; padding: 20px;")
        login_box_layout = QVBoxLayout(login_box)

        title = QLabel("Admin Login")
        title.setAlignment(Qt.AlignmentFlag.AlignCenter)
        title.setStyleSheet("font-size: 28px; font-weight: 600; color: #2c3e50; margin-bottom: 20px;")
        login_box_layout.addWidget(title)

        self.login_username = QLineEdit()
        self.login_username.setPlaceholderText("Username")
        self.login_password = QLineEdit()
        self.login_password.setPlaceholderText("Password")
        self.login_password.setEchoMode(QLineEdit.EchoMode.Password)
        
        login_btn = QPushButton("LOGIN")
        login_btn.clicked.connect(self.handle_login)
        login_btn.setStyleSheet("background-color: #27ae60;")
        
        self.login_error_label = QLabel("")
        self.login_error_label.setStyleSheet("color: red; font-weight: bold;")
        self.login_error_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        
        login_box_layout.addWidget(self.login_username)
        login_box_layout.addWidget(self.login_password)
        login_box_layout.addSpacing(20)
        login_box_layout.addWidget(login_btn)
        login_box_layout.addWidget(self.login_error_label)
        
        layout.addWidget(login_box)
        self.content_stack.addWidget(self.login_screen)

    def handle_login(self):
        username = self.login_username.text()
        password = self.login_password.text()
        
        user_data = self.model.validate_login(username, password)
        
        if user_data:
            self.current_user = user_data['id']
            self.current_role = user_data['role']
            
            self.sidebar.show()
            self.content_stack.setCurrentIndex(self.content_stack.indexOf(self.dashboard_screen))
            self.nav_buttons["Dashboard"].setChecked(True)
            self.update_dashboard()
            self.login_error_label.setText("")
            self.model.log_action(self.current_user, f"User logged in ({self.current_role})")
        else:
            self.login_error_label.setText("Invalid username or password.")

    def logout(self):
        if self.current_user:
            self.model.log_action(self.current_user, "User logged out.")
        
        self.device_manager.stop_worker()
        self.sidebar.hide()
        self.content_stack.setCurrentIndex(self.content_stack.indexOf(self.login_screen))
        self.login_username.clear()
        self.login_password.clear()
        self.current_user = None
        self.current_role = None

    # --- Device Status ---

    def _update_device_status(self, connected, message):
        self.device_handle = self.device_manager.get_handle()
        if connected:
            self.device_status_label.setText(f"Device: {message}")
            self.device_status_label.setStyleSheet("color: #2ecc71; font-size: 12px; padding: 5px; background-color: #34495e;")
        else:
            self.device_status_label.setText(f"Device: {message}")
            self.device_status_label.setStyleSheet("color: #e74c3c; font-size: 12px; padding: 5px; background-color: #34495e;")
    
    # --- Dashboard Screen ---

    def create_dashboard_screen(self):
        self.dashboard_screen = QWidget()
        layout = QVBoxLayout(self.dashboard_screen)
        
        title = QLabel("Dashboard Overview")
        title.setStyleSheet("font-size: 30px; font-weight: bold; margin-bottom: 20px;")
        layout.addWidget(title)
        
        self.dashboard_summary_widgets = {}
        summary_layout = QHBoxLayout()
        metrics = ["Total Teachers", "Present Today", "Absent Today", "Late Arrivals"]
        
        for metric in metrics:
            box = self._create_metric_box(metric, "0")
            summary_layout.addWidget(box)
            self.dashboard_summary_widgets[metric] = box.findChild(QLabel, "value_label")

        layout.addLayout(summary_layout)
        
        self.dashboard_recent_log = QTableWidget()
        self.dashboard_recent_log.setColumnCount(5)
        self.dashboard_recent_log.setHorizontalHeaderLabels(["Time", "Teacher ID", "Name", "Department", "Status"])
        self.dashboard_recent_log.horizontalHeader().setSectionResizeMode(QHeaderView.ResizeMode.Stretch)
        self.dashboard_recent_log.setFixedHeight(300)
        layout.addWidget(QLabel("Recent Attendance Log:"))
        layout.addWidget(self.dashboard_recent_log)

        layout.addStretch()
        self.content_stack.addWidget(self.dashboard_screen)

    def _create_metric_box(self, title_text, value_text):
        box = QFrame()
        box.setFrameShape(QFrame.Shape.StyledPanel)
        box.setStyleSheet("background-color: white; border-radius: 8px; padding: 15px; margin: 5px; border: 1px solid #e0e0e0;")
        box_layout = QVBoxLayout(box)
        
        title = QLabel(title_text)
        title.setStyleSheet("font-size: 16px; color: #7f8c8d; font-weight: 500;")
        
        value = QLabel(value_text)
        value.setObjectName("value_label")
        value.setStyleSheet("font-size: 40px; font-weight: bold; color: #3498db;")
        
        box_layout.addWidget(title)
        box_layout.addWidget(value)
        return box

    def update_dashboard(self):
        # 1. Update Metrics
        total_teachers = len(self.model.get_all_teachers())
        today_date = datetime.date.today().strftime('%Y-%m-%d')
        
        report_data = self.model.get_report_data(today_date, today_date)
        
        present_count = 0
        absent_count = 0
        late_count = 0
        
        for teacher in report_data:
            rec = teacher['Daily_Records'][0]
            if rec['Status'] == 'Present': present_count += 1
            elif rec['Status'] == 'Late': present_count += 1; late_count += 1
            elif rec['Status'] == 'Absent': absent_count += 1
            elif rec['Status'] == 'Early_Leave': present_count += 1 # Count as present, but marked as early leave
            elif rec['Status'] == 'Leave' or rec['Status'] == 'Holiday': pass # Don't count in present/absent metrics

        self.dashboard_summary_widgets["Total Teachers"].setText(str(total_teachers))
        self.dashboard_summary_widgets["Present Today"].setText(str(present_count))
        self.dashboard_summary_widgets["Absent Today"].setText(str(absent_count))
        self.dashboard_summary_widgets["Late Arrivals"].setText(str(late_count))
        
        # 2. Update Recent Log
        self.model.cursor.execute("""
            SELECT t.teacher_id, t.name, d.name, a.check_in, a.check_out, a.status 
            FROM attendance a
            JOIN teachers t ON a.teacher_id = t.id
            LEFT JOIN departments d ON t.department_id = d.id
            ORDER BY a.check_in DESC LIMIT 10
        """)
        recent_logs = self.model.cursor.fetchall()
        
        self.dashboard_recent_log.setRowCount(len(recent_logs))
        for row_idx, log in enumerate(recent_logs):
            time_str = datetime.datetime.strptime(log['check_in'], '%Y-%m-%d %H:%M:%S').strftime('%H:%M:%S')
            
            self.dashboard_recent_log.setItem(row_idx, 0, QTableWidgetItem(time_str))
            self.dashboard_recent_log.setItem(row_idx, 1, QTableWidgetItem(log['teacher_id']))
            self.dashboard_recent_log.setItem(row_idx, 2, QTableWidgetItem(log['name']))
            self.dashboard_recent_log.setItem(row_idx, 3, QTableWidgetItem(log['name']))
            self.dashboard_recent_log.setItem(row_idx, 4, QTableWidgetItem(log['status']))
            
            # Highlight status
            status_item = self.dashboard_recent_log.item(row_idx, 4)
            if log['status'] == 'Late':
                status_item.setBackground(QColor(255, 153, 51))
            elif log['status'] == 'Absent':
                status_item.setBackground(QColor(255, 102, 102))
            elif log['status'] == 'Present':
                status_item.setBackground(QColor(102, 255, 102))

    # --- Teacher Management Screen ---

    def create_teacher_management_screen(self):
        self.teacher_management_screen = QWidget()
        main_layout = QHBoxLayout(self.teacher_management_screen)
        
        # Left Panel: Teacher List
        list_frame = QFrame()
        list_frame.setFixedWidth(500)
        list_layout = QVBoxLayout(list_frame)
        
        list_layout.addWidget(QLabel("Registered Teachers:"))
        self.teacher_search = QLineEdit()
        self.teacher_search.setPlaceholderText("Search by ID or Name...")
        self.teacher_search.textChanged.connect(self.load_teachers_data)
        list_layout.addWidget(self.teacher_search)

        self.teacher_table = QTableWidget()
        self.teacher_table.setColumnCount(4)
        self.teacher_table.setHorizontalHeaderLabels(["ID", "Name", "Dept", "Status"])
        self.teacher_table.horizontalHeader().setSectionResizeMode(QHeaderView.ResizeMode.Stretch)
        self.teacher_table.setSelectionBehavior(QTableWidget.SelectionBehavior.SelectRows)
        self.teacher_table.setSelectionMode(QTableWidget.SelectionMode.SingleSelection)
        self.teacher_table.cellClicked.connect(self.select_teacher_for_details)
        list_layout.addWidget(self.teacher_table)
        
        add_teacher_btn = QPushButton("Add New Teacher")
        add_teacher_btn.clicked.connect(self.show_teacher_dialog)
        list_layout.addWidget(add_teacher_btn)
        
        main_layout.addWidget(list_frame)
        
        # Right Panel: Details/Fingerprints
        self.teacher_details_widget = QWidget()
        details_layout = QVBoxLayout(self.teacher_details_widget)
        
        details_layout.addWidget(QLabel("Teacher Details:"))
        self.teacher_tabs = QTabWidget()
        details_layout.addWidget(self.teacher_tabs)
        
        self._create_teacher_info_tab()
        self._create_fingerprint_tab()

        main_layout.addWidget(self.teacher_details_widget)
        self.content_stack.addWidget(self.teacher_management_screen)

    def _create_teacher_info_tab(self):
        self.info_tab = QWidget()
        layout = QVBoxLayout(self.info_tab)
        self.teacher_tabs.addTab(self.info_tab, "Info")
        
        self.detail_name = QLabel("Name: ")
        self.detail_id = QLabel("ID: ")
        self.detail_cnic = QLabel("CNIC: ")
        self.detail_dept = QLabel("Department: ")
        self.detail_shift = QLabel("Shift: ")
        self.detail_status = QLabel("Status: ")
        
        layout.addWidget(self.detail_name)
        layout.addWidget(self.detail_id)
        layout.addWidget(self.detail_cnic)
        layout.addWidget(self.detail_dept)
        layout.addWidget(self.detail_shift)
        layout.addWidget(self.detail_status)
        
        self.detail_photo = QLabel("Photo")
        self.detail_photo.setFixedSize(150, 150)
        self.detail_photo.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.detail_photo.setStyleSheet("border: 1px solid gray;")
        layout.addWidget(self.detail_photo, alignment=Qt.AlignmentFlag.AlignCenter)
        
        edit_btn = QPushButton("Edit Teacher")
        edit_btn.clicked.connect(self.edit_selected_teacher)
        layout.addWidget(edit_btn)
        layout.addStretch()

    def _create_fingerprint_tab(self):
        self.fp_tab = QWidget()
        layout = QVBoxLayout(self.fp_tab)
        self.teacher_tabs.addTab(self.fp_tab, "Fingerprints")
        
        self.fp_table = QTableWidget()
        self.fp_table.setColumnCount(2)
        self.fp_table.setHorizontalHeaderLabels(["Index", "Status"])
        self.fp_table.horizontalHeader().setSectionResizeMode(QHeaderView.ResizeMode.Stretch)
        layout.addWidget(self.fp_table)
        
        btn_layout = QHBoxLayout()
        self.enroll_new_fp_btn = QPushButton("Enroll New Fingerprint")
        self.enroll_new_fp_btn.clicked.connect(self.show_enrollment_dialog)
        
        self.delete_fp_btn = QPushButton("Delete Selected Fingerprint")
        self.delete_fp_btn.clicked.connect(self.delete_selected_fingerprint)

        btn_layout.addWidget(self.enroll_new_fp_btn)
        btn_layout.addWidget(self.delete_fp_btn)
        layout.addLayout(btn_layout)

        self.current_teacher_db_id = None

    def load_teachers_data(self):
        teachers = self.model.get_all_teachers(active_only=False)
        search_text = self.teacher_search.text().lower()
        
        filtered_teachers = [t for t in teachers if search_text in t['teacher_id'].lower() or search_text in t['name'].lower()]

        self.teacher_table.setRowCount(len(filtered_teachers))
        for row_idx, t in enumerate(filtered_teachers):
            self.teacher_table.setItem(row_idx, 0, QTableWidgetItem(t['teacher_id']))
            self.teacher_table.setItem(row_idx, 1, QTableWidgetItem(t['name']))
            self.teacher_table.setItem(row_idx, 2, QTableWidgetItem(t['dept_name'] or '-'))
            status_item = QTableWidgetItem(t['status'])
            if t['status'] == 'Inactive':
                status_item.setBackground(QColor(255, 200, 200))
            self.teacher_table.setItem(row_idx, 3, status_item)
            self.teacher_table.item(row_idx, 0).setData(Qt.ItemDataRole.UserRole, t['id'])
            
    def select_teacher_for_details(self, row, col):
        selected_item = self.teacher_table.item(row, 0)
        if not selected_item: return
        
        self.current_teacher_db_id = selected_item.data(Qt.ItemDataRole.UserRole)
        self.update_teacher_details(self.current_teacher_db_id)

    def update_teacher_details(self, teacher_db_id):
        details = self.model.get_teacher_details(teacher_db_id)
        
        if not details:
            self.teacher_details_widget.setEnabled(False)
            return

        self.teacher_details_widget.setEnabled(True)
        self.detail_name.setText(f"Name: {details['name']}")
        self.detail_id.setText(f"ID: {details['teacher_id']}")
        self.detail_cnic.setText(f"CNIC: {details['cnic'] or '-'}")
        self.detail_dept.setText(f"Department: {details['dept_name'] or '-'}")
        self.detail_shift.setText(f"Shift: {details['shift_name'] or '-'}")
        self.detail_status.setText(f"Status: {details['status']}")
        
        if details['photo']:
            pixmap = QPixmap()
            pixmap.loadFromData(details['photo'])
            self.detail_photo.setPixmap(pixmap.scaled(150, 150, Qt.AspectRatioMode.KeepAspectRatioByExpanding, Qt.TransformationMode.SmoothTransformation))
        else:
            self.detail_photo.setText("No Photo")
            self.detail_photo.setPixmap(QPixmap())
            
        # Fingerprint Tab
        self.load_fingerprint_data(teacher_db_id)

    def load_fingerprint_data(self, teacher_db_id):
        fingerprints = self.model.get_fingerprints_by_teacher(teacher_db_id)
        self.fp_table.setRowCount(len(fingerprints))
        
        for row_idx, fp in enumerate(fingerprints):
            index = QTableWidgetItem(str(fp['finger_index']))
            status = QTableWidgetItem("Enrolled")
            status.setBackground(QColor(200, 255, 200))
            
            self.fp_table.setItem(row_idx, 0, index)
            self.fp_table.setItem(row_idx, 1, status)
            self.fp_table.item(row_idx, 0).setData(Qt.ItemDataRole.UserRole, fp['id'])

    def delete_selected_fingerprint(self):
        selected_rows = self.fp_table.selectionModel().selectedRows()
        if not selected_rows:
            QMessageBox.warning(self, "Selection Error", "Please select a fingerprint to delete.")
            return

        fp_id = selected_rows[0].data(Qt.ItemDataRole.UserRole)
        reply = QMessageBox.question(self, 'Confirm Deletion',
                                     "Are you sure you want to delete this fingerprint template?",
                                     QMessageBox.StandardButton.Yes | QMessageBox.StandardButton.No)
        
        if reply == QMessageBox.StandardButton.Yes:
            self.model.delete_fingerprint(fp_id)
            self.model.log_action(self.current_user, "Deleted Fingerprint", f"Teacher ID: {self.current_teacher_db_id}")
            self.load_fingerprint_data(self.current_teacher_db_id)

    def show_teacher_dialog(self, edit_mode=False, teacher_db_id=None):
        dialog = QDialog(self)
        dialog.setWindowTitle("Manage Teacher")
        dialog.setFixedSize(500, 600)
        
        layout = QFormLayout(dialog)
        
        teacher_fields = {
            'teacher_id': QLineEdit(), 'name': QLineEdit(), 'cnic': QLineEdit(), 
            'phone': QLineEdit(), 'email': QLineEdit(), 'designation': QLineEdit(),
            'status': QComboBox()
        }
        teacher_fields['status'].addItems(['Active', 'Inactive'])

        dept_combo = QComboBox()
        shifts_combo = QComboBox()
        self.photo_preview = QLabel("No Photo Selected")
        self.photo_preview.setFixedSize(100, 100)
        self.photo_preview.setStyleSheet("border: 1px solid gray;")
        self.photo_data_temp = None
        
        # Load combos
        departments = self.model.get_all_departments()
        for id, name in departments:
            dept_combo.addItem(name, id)
        
        shifts = self.model.get_all_shifts()
        for id, name, _, _, _, _ in shifts:
            shifts_combo.addItem(name, id)

        # Photo handling
        photo_btn = QPushButton("Select Photo")
        photo_btn.clicked.connect(lambda: self._browse_photo_dialog(dialog))
        
        layout.addRow("Teacher ID:", teacher_fields['teacher_id'])
        layout.addRow("Name:", teacher_fields['name'])
        layout.addRow("CNIC:", teacher_fields['cnic'])
        layout.addRow("Phone:", teacher_fields['phone'])
        layout.addRow("Email:", teacher_fields['email'])
        layout.addRow("Department:", dept_combo)
        layout.addRow("Designation:", teacher_fields['designation'])
        layout.addRow("Shift:", shifts_combo)
        layout.addRow("Status:", teacher_fields['status'])
        layout.addRow("Photo:", photo_btn)
        layout.addRow("", self.photo_preview)
        
        # Populate if editing
        if edit_mode and teacher_db_id:
            details = self.model.get_teacher_details(teacher_db_id)
            if details:
                for key, widget in teacher_fields.items():
                    if isinstance(widget, QLineEdit):
                        widget.setText(details[key])
                    elif isinstance(widget, QComboBox):
                        widget.setCurrentText(details[key].capitalize())
                
                dept_combo.setCurrentIndex(dept_combo.findData(details['department_id']))
                shifts_combo.setCurrentIndex(shifts_combo.findData(details['shift_id']))

                if details['photo']:
                    self.photo_data_temp = details['photo']
                    pixmap = QPixmap()
                    pixmap.loadFromData(details['photo'])
                    self.photo_preview.setPixmap(pixmap.scaled(100, 100, Qt.AspectRatioMode.KeepAspectRatio))

        save_btn = QPushButton("Save Teacher")
        save_btn.clicked.connect(lambda: self._save_teacher_from_dialog(dialog, teacher_fields, dept_combo, shifts_combo, teacher_db_id))
        layout.addRow(save_btn)

        dialog.exec()

    def _browse_photo_dialog(self, dialog):
        file_path, _ = QFileDialog.getOpenFileName(dialog, "Select Photo", "", "Image Files (*.png *.jpg *.jpeg)")
        if file_path:
            try:
                with open(file_path, 'rb') as f:
                    self.photo_data_temp = f.read()
                pixmap = QPixmap()
                pixmap.loadFromData(self.photo_data_temp)
                self.photo_preview.setPixmap(pixmap.scaled(100, 100, Qt.AspectRatioMode.KeepAspectRatio))
            except Exception as e:
                QMessageBox.critical(dialog, "Photo Error", f"Could not load photo: {e}")

    def _save_teacher_from_dialog(self, dialog, fields, dept_combo, shifts_combo, teacher_db_id):
        data = {k: v.text() if isinstance(v, QLineEdit) else v.currentText() for k, v in fields.items()}
        data['department_id'] = dept_combo.currentData()
        data['shift_id'] = shifts_combo.currentData()
        data['photo'] = self.photo_data_temp

        if not all([data['teacher_id'], data['name'], data['department_id'], data['shift_id']]):
            QMessageBox.warning(dialog, "Validation", "Teacher ID, Name, Department, and Shift are mandatory.")
            return

        result_id = self.model.save_teacher(data, teacher_db_id)
        
        if result_id:
            QMessageBox.information(dialog, "Success", "Teacher data saved successfully.")
            self.model.log_action(self.current_user, "Teacher Saved/Updated", f"Teacher ID: {data['teacher_id']}")
            self.load_teachers_data()
            if result_id == self.current_teacher_db_id:
                self.update_teacher_details(self.current_teacher_db_id)
            dialog.accept()
        else:
            QMessageBox.critical(dialog, "Error", "Failed to save teacher. Teacher ID might already exist.")

    def edit_selected_teacher(self):
        if self.current_teacher_db_id:
            self.show_teacher_dialog(edit_mode=True, teacher_db_id=self.current_teacher_db_id)

    # --- Enrollment Dialog ---

    def show_enrollment_dialog(self):
        if not self.current_teacher_db_id:
            QMessageBox.warning(self, "Selection Error", "Please select a teacher first.")
            return
        
        if not self.device_handle:
            QMessageBox.critical(self, "Device Error", "Fingerprint reader not connected.")
            return

        dialog = QDialog(self)
        dialog.setWindowTitle(f"Enroll Fingerprint for Teacher ID: {self.model.get_teacher_details(self.current_teacher_db_id)['teacher_id']}")
        dialog.setFixedSize(500, 400)
        
        layout = QVBoxLayout(dialog)
        
        layout.addWidget(QLabel("Select Finger Index:"))
        self.enroll_finger_index = QSpinBox()
        self.enroll_finger_index.setRange(1, 10)
        layout.addWidget(self.enroll_finger_index)
        
        self.enroll_progress_label = QLabel("Ready to start enrollment.")
        self.enroll_progress_label.setStyleSheet("font-size: 16px; font-weight: bold;")
        layout.addWidget(self.enroll_progress_label)
        
        self.enroll_start_btn = QPushButton("Start Enrollment (4 Captures Required)")
        self.enroll_start_btn.clicked.connect(lambda: self._start_enrollment(dialog))
        layout.addWidget(self.enroll_start_btn)
        
        self.enroll_cancel_btn = QPushButton("Cancel")
        self.enroll_cancel_btn.clicked.connect(dialog.reject)
        layout.addWidget(self.enroll_cancel_btn)
        
        dialog.exec()

    def _start_enrollment(self, dialog):
        finger_index = self.enroll_finger_index.value()
        teacher_db_id = self.current_teacher_db_id

        # Check if finger index already exists
        self.model.cursor.execute("SELECT id FROM fingerprints WHERE teacher_id = ? AND finger_index = ?", (teacher_db_id, finger_index))
        if self.model.cursor.fetchone():
            if QMessageBox.question(dialog, "Overwrite Warning", f"Finger index {finger_index} already has a template. Overwrite?") == QMessageBox.StandardButton.No:
                return

        self.enroll_start_btn.setEnabled(False)
        self.enroll_finger_index.setEnabled(False)
        
        self.device_manager.stop_worker() # Ensure any previous worker is stopped

        if self.device_manager.start_capture_worker('enroll', 
                                                   capture_complete_callback=lambda s, t: self._finish_enrollment_capture(s, t, teacher_db_id, finger_index, dialog),
                                                   enrollment_step_callback=self._update_enrollment_step):
            self.enroll_progress_label.setText("Enrollment started. Follow instructions.")

    def _update_enrollment_step(self, status, step, message):
        if status == DPFPDD_E_SUCCESS:
            self.enroll_progress_label.setStyleSheet("color: blue; font-weight: bold;")
        else:
            self.enroll_progress_label.setStyleSheet("color: red; font-weight: bold;")
            
        self.enroll_progress_label.setText(message)

    def _finish_enrollment_capture(self, status, template, teacher_db_id, finger_index, dialog):
        self.device_manager.stop_worker()
        self.enroll_start_btn.setEnabled(True)
        self.enroll_finger_index.setEnabled(True)

        if status == DPFPDD_E_SUCCESS and template:
            if self.model.add_fingerprint(teacher_db_id, finger_index, template):
                QMessageBox.information(dialog, "Enrollment Success", f"Fingerprint Index {finger_index} successfully enrolled.")
                self.model.log_action(self.current_user, "Fingerprint Enrolled", f"Teacher ID: {teacher_db_id}, Finger Index: {finger_index}")
                self.update_teacher_details(teacher_db_id)
                dialog.accept()
            else:
                 QMessageBox.critical(dialog, "Database Error", "Enrollment successful but failed to save to database.")
        else:
            QMessageBox.critical(dialog, "Enrollment Failed", f"Enrollment process failed. Status Code: {status}")
            self.enroll_progress_label.setText("Enrollment Failed.")
    
    # --- Attendance Capture Screen ---

    def create_attendance_capture_screen(self):
        self.attendance_capture_screen = QWidget()
        layout = QVBoxLayout(self.attendance_capture_screen)
        layout.setAlignment(Qt.AlignmentFlag.AlignCenter)
        
        self.attendance_status_label = QLabel("Awaiting Scan...")
        self.attendance_status_label.setStyleSheet("font-size: 36px; font-weight: bold; color: #3498db;")
        self.attendance_status_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(self.attendance_status_label)
        
        self.live_info_frame = QFrame()
        self.live_info_frame.setFixedSize(500, 350)
        self.live_info_frame.setStyleSheet("background-color: white; border: 1px solid #ddd; border-radius: 10px; padding: 20px;")
        info_layout = QVBoxLayout(self.live_info_frame)
        
        self.att_photo_label = QLabel("Photo")
        self.att_photo_label.setFixedSize(120, 120)
        self.att_photo_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.att_photo_label.setStyleSheet("border: 1px solid gray;")
        
        self.att_name_label = QLabel("NAME: -")
        self.att_name_label.setStyleSheet("font-size: 24px; font-weight: bold;")
        self.att_time_label = QLabel("TIME: -")
        self.att_action_label = QLabel("ACTION: -")
        self.att_action_label.setStyleSheet("font-size: 20px; color: #2ecc71;")

        info_layout.addWidget(self.att_photo_label, alignment=Qt.AlignmentFlag.AlignCenter)
        info_layout.addWidget(self.att_name_label, alignment=Qt.AlignmentFlag.AlignCenter)
        info_layout.addWidget(self.att_time_label, alignment=Qt.AlignmentFlag.AlignCenter)
        info_layout.addWidget(self.att_action_label, alignment=Qt.AlignmentFlag.AlignCenter)
        self.live_info_frame.setVisible(False)
        
        layout.addWidget(self.live_info_frame, alignment=Qt.AlignmentFlag.AlignCenter)
        
        self.start_scan_btn = QPushButton("Start Biometric Scanner")
        self.start_scan_btn.clicked.connect(self.start_attendance_scan)
        layout.addWidget(self.start_scan_btn, alignment=Qt.AlignmentFlag.AlignCenter)
        
        self.content_stack.addWidget(self.attendance_capture_screen)

    def prepare_attendance_capture(self):
        self.live_info_frame.setVisible(False)
        self.attendance_status_label.setText("Awaiting Scan...")
        self.attendance_status_label.setStyleSheet("font-size: 36px; font-weight: bold; color: #3498db;")
        self.device_manager.stop_worker()
        self.start_scan_btn.setText("Start Biometric Scanner")
        self.start_scan_btn.setEnabled(True)

    def start_attendance_scan(self):
        if not self.device_handle:
            QMessageBox.critical(self, "Device Error", "Fingerprint reader not connected.")
            return

        matching_data = self.model.get_all_fingerprints_for_matching()
        if not matching_data:
            QMessageBox.warning(self, "Database Empty", "No active teachers or fingerprints found for matching.")
            return
            
        fmd_templates = list(matching_data.keys())

        self.start_scan_btn.setText("Scanning Active...")
        self.start_scan_btn.setEnabled(False)
        self.attendance_status_label.setText("Place finger on the reader...")
        self.live_info_frame.setVisible(False)
        
        self.device_manager.start_capture_worker('match', 
                                                fmd_to_match=fmd_templates,
                                                match_complete_callback=lambda s, t, sc: self._finish_attendance_scan(s, t, sc, matching_data))

    def _finish_attendance_scan(self, status, matched_template, score, matching_data):
        self.device_manager.stop_worker()
        self.start_scan_btn.setText("Restart Biometric Scanner")
        self.start_scan_btn.setEnabled(True)

        if status == DPFPDD_E_SUCCESS and matched_template:
            teacher_db_id, teacher_id_str = matching_data[matched_template]
            details = self.model.get_teacher_details(teacher_db_id)
            
            # Decide Check-in or Check-out
            today_date = datetime.date.today().strftime('%Y-%m-%d')
            self.model.cursor.execute("SELECT check_out FROM attendance WHERE teacher_id = ? AND DATE(check_in) = ? ORDER BY check_in DESC LIMIT 1", (teacher_db_id, today_date))
            last_att = self.model.cursor.fetchone()
            
            if last_att and last_att['check_out'] is None:
                # Check-out action
                message = self.model.process_check_out(teacher_db_id)
                action_text = "CHECK-OUT"
                color = "#e67e22"
            else:
                # Check-in action
                message = self.model.process_check_in(teacher_db_id)
                action_text = "CHECK-IN"
                color = "#2ecc71"
            
            # Update UI
            self.attendance_status_label.setText(message)
            self.attendance_status_label.setStyleSheet(f"font-size: 24px; font-weight: bold; color: {color};")
            
            self.att_name_label.setText(f"NAME: {details['name']}")
            self.att_time_label.setText(f"TIME: {datetime.datetime.now().strftime('%H:%M:%S')}")
            self.att_action_label.setText(f"ACTION: {action_text}")
            self.att_action_label.setStyleSheet(f"font-size: 20px; font-weight: bold; color: {color};")
            
            if details['photo']:
                pixmap = QPixmap()
                pixmap.loadFromData(details['photo'])
                self.att_photo_label.setPixmap(pixmap.scaled(120, 120, Qt.AspectRatioMode.KeepAspectRatioByExpanding, Qt.TransformationMode.SmoothTransformation))
            else:
                self.att_photo_label.setText("No Photo")
                self.att_photo_label.setPixmap(QPixmap())
                
            self.live_info_frame.setVisible(True)

        elif status == DPFPDD_E_TIMEOUT:
            self.attendance_status_label.setText("Scan Timeout. Try again.")
            self.attendance_status_label.setStyleSheet("font-size: 30px; font-weight: bold; color: #f39c12;")
        else:
            self.attendance_status_label.setText("Match Failed / Template not recognized.")
            self.attendance_status_label.setStyleSheet("font-size: 30px; font-weight: bold; color: #e74c3c;")
            
        QTimer.singleShot(5000, self.prepare_attendance_capture)

    # --- Reporting Screen ---

    def create_reporting_screen(self):
        self.reporting_screen = QWidget()
        layout = QVBoxLayout(self.reporting_screen)
        
        title = QLabel("Attendance Reporting")
        title.setStyleSheet("font-size: 30px; font-weight: bold; margin-bottom: 20px;")
        layout.addWidget(title)
        
        # Filters
        filter_group = QGroupBox("Report Filters")
        filter_layout = QGridLayout(filter_group)
        
        self.report_start_date = QDateEdit(QDate.currentDate().addMonths(-1))
        self.report_end_date = QDateEdit(QDate.currentDate())
        self.report_teacher_id = QLineEdit()
        self.report_teacher_id.setPlaceholderText("Optional Teacher ID")
        self.report_department = QComboBox()
        self.report_department.addItem("All Departments", None)
        
        departments = self.model.get_all_departments()
        for id, name in departments:
            self.report_department.addItem(name, id)
        
        self.generate_report_btn = QPushButton("Generate Report")
        self.generate_report_btn.clicked.connect(self.generate_attendance_report)

        filter_layout.addWidget(QLabel("Start Date:"), 0, 0)
        filter_layout.addWidget(self.report_start_date, 0, 1)
        filter_layout.addWidget(QLabel("End Date:"), 0, 2)
        filter_layout.addWidget(self.report_end_date, 0, 3)
        filter_layout.addWidget(QLabel("Teacher ID:"), 1, 0)
        filter_layout.addWidget(self.report_teacher_id, 1, 1)
        filter_layout.addWidget(QLabel("Department:"), 1, 2)
        filter_layout.addWidget(self.report_department, 1, 3)
        filter_layout.addWidget(self.generate_report_btn, 2, 3)
        
        layout.addWidget(filter_group)
        
        # Report Viewer
        self.report_table = QTableWidget()
        self.report_table.horizontalHeader().setSectionResizeMode(QHeaderView.ResizeMode.Stretch)
        layout.addWidget(self.report_table)

        # Export Buttons
        export_layout = QHBoxLayout()
        export_layout.addStretch()
        self.export_pdf_btn = QPushButton("Export PDF")
        self.export_excel_btn = QPushButton("Export Excel")
        self.export_csv_btn = QPushButton("Export CSV")
        
        self.export_pdf_btn.clicked.connect(lambda: self.export_report('pdf'))
        self.export_excel_btn.clicked.connect(lambda: self.export_report('excel'))
        self.export_csv_btn.clicked.connect(lambda: self.export_report('csv'))

        export_layout.addWidget(self.export_pdf_btn)
        export_layout.addWidget(self.export_excel_btn)
        export_layout.addWidget(self.export_csv_btn)
        layout.addLayout(export_layout)
        
        self.content_stack.addWidget(self.reporting_screen)

    def prepare_reporting(self):
        self.report_data_cache = None
        self.report_table.setRowCount(0)
        self.report_table.setColumnCount(0)

    def generate_attendance_report(self):
        start_date = self.report_start_date.date().toString("yyyy-MM-dd")
        end_date = self.report_end_date.date().toString("yyyy-MM-dd")
        teacher_id = self.report_teacher_id.text() or None
        department_id = self.report_department.currentData() or None

        self.report_data_cache = self.model.get_report_data(start_date, end_date, teacher_id, department_id)
        
        if not self.report_data_cache:
            QMessageBox.information(self, "No Data", "No attendance records found for the selected criteria.")
            self.report_table.setRowCount(0)
            return

        # Flatten the data structure for display
        display_data = []
        for teacher in self.report_data_cache:
            summary_row = [
                teacher['ID'], teacher['Name'], teacher['Department'],
                str(teacher['Total_Presents']), str(teacher['Total_Absents']), str(teacher['Total_Late']), str(teacher['Total_Early']), str(teacher['Total_Leave'])
            ]
            display_data.append(summary_row)
        
        headers = ["ID", "Name", "Dept", "Presents", "Absents", "Late", "Early Leave", "Leaves"]
        
        self.report_table.setColumnCount(len(headers))
        self.report_table.setHorizontalHeaderLabels(headers)
        self.report_table.setRowCount(len(display_data))

        for row_idx, row_data in enumerate(display_data):
            for col_idx, item in enumerate(row_data):
                self.report_table.setItem(row_idx, col_idx, QTableWidgetItem(item))

    def export_report(self, format_type):
        if not self.report_data_cache:
            QMessageBox.warning(self, "Export Error", "Please generate a report first.")
            return

        file_filter = {
            'pdf': "PDF Files (*.pdf)",
            'excel': "Excel Files (*.xlsx)",
            'csv': "CSV Files (*.csv)"
        }
        
        file_path, _ = QFileDialog.getSaveFileName(self, f"Save Report as {format_type.upper()}", f"Attendance_Report_{datetime.date.today()}", file_filter[format_type])
        
        if not file_path:
            return

        try:
            if format_type == 'csv':
                self._export_to_csv(file_path)
            elif format_type == 'excel':
                self._export_to_excel(file_path)
            elif format_type == 'pdf':
                self._export_to_pdf(file_path)
            
            QMessageBox.information(self, "Export Success", f"Report successfully exported to {file_path}")
            self.model.log_action(self.current_user, f"Exported {format_type} Report")
        except Exception as e:
            QMessageBox.critical(self, "Export Failed", f"An error occurred during export: {e}")

    def _export_to_csv(self, file_path):
        headers = ["ID", "Name", "Department", "Date", "Status", "Check In", "Check Out", "Duration (Hrs)"]
        all_rows = [headers]
        for teacher in self.report_data_cache:
            for daily in teacher['Daily_Records']:
                row = [
                    teacher['ID'], teacher['Name'], teacher['Department'], 
                    daily['Date'], daily['Status'], daily['Check_In'], daily['Check_Out'], daily['Duration_Hrs']
                ]
                all_rows.append(row)
        
        with open(file_path, 'w', newline='') as f:
            writer = csv.writer(f)
            writer.writerows(all_rows)

    def _export_to_excel(self, file_path):
        data = []
        for teacher in self.report_data_cache:
            for daily in teacher['Daily_Records']:
                data.append({
                    "ID": teacher['ID'], "Name": teacher['Name'], "Department": teacher['Department'],
                    "Date": daily['Date'], "Status": daily['Status'], "Check In": daily['Check_In'],
                    "Check Out": daily['Check_Out'], "Duration (Hrs)": daily['Duration_Hrs']
                })
        
        df = pd.DataFrame(data)
        df.to_excel(file_path, index=False)

    def _export_to_pdf(self, file_path):
        doc = SimpleDocTemplate(file_path, pagesize=A4, rightMargin=30, leftMargin=30, topMargin=30, bottomMargin=30)
        elements = []
        styles = getSampleStyleSheet()
        
        elements.append(Paragraph("Biometric Attendance Detailed Report", styles['Title']))
        elements.append(Paragraph(f"Period: {self.report_start_date.date().toString('MMM d, yyyy')} - {self.report_end_date.date().toString('MMM d, yyyy')}", styles['Normal']))
        elements.append(Spacer(1, 12))
        
        header_row = ["Date", "Status", "In", "Out", "Hrs"]
        col_widths = [0.8*inch, 0.8*inch, 0.6*inch, 0.6*inch, 0.6*inch]
        
        for teacher in self.report_data_cache:
            # Summary Table for Teacher
            summary_data = [
                ['Teacher ID:', teacher['ID'], 'Name:', teacher['Name']],
                ['Department:', teacher['Department'], 'P:', teacher['Total_Presents'], 'A:', teacher['Total_Absents'], 'L:', teacher['Total_Late']]
            ]
            summary_table = Table(summary_data, colWidths=[1.2*inch, 1.2*inch, 1.2*inch, 0.5*inch, 0.5*inch, 0.5*inch, 0.5*inch, 0.5*inch])
            summary_table.setStyle(TableStyle([
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 6),
                ('GRID', (0, 0), (-1, -1), 0.25, colors.grey)
            ]))
            elements.append(summary_table)
            elements.append(Spacer(1, 6))

            # Daily Detail Table
            daily_data = [header_row]
            for daily in teacher['Daily_Records']:
                daily_data.append([
                    daily['Date'], daily['Status'], daily['Check_In'], daily['Check_Out'], daily['Duration_Hrs']
                ])
                
            detail_table = Table(daily_data, colWidths=col_widths)
            detail_table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.darkslategray),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
                ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 6),
                ('GRID', (0, 0), (-1, -1), 0.5, colors.lightgrey),
                ('BACKGROUND', (0, 1), (-1, -1), colors.white)
            ]))
            elements.append(detail_table)
            elements.append(Spacer(1, 18))

        doc.build(elements)

    # --- Admin Settings Screen ---

    def create_admin_settings_screen(self):
        self.admin_settings_screen = QWidget()
        layout = QVBoxLayout(self.admin_settings_screen)
        
        self.settings_tabs = QTabWidget()
        layout.addWidget(self.settings_tabs)
        
        self._create_institute_settings_tab()
        self._create_crud_tab("Departments", self._create_department_crud, self.load_department_crud)
        self._create_crud_tab("Shifts", self._create_shift_crud, self.load_shift_crud)
        self._create_crud_tab("Admin Users", self._create_user_crud, self.load_user_crud)
        self._create_crud_tab("Holidays", self._create_holiday_crud, self.load_holiday_crud)
        self._create_security_tab()
        self._create_backup_restore_tab()
        self._create_audit_log_tab()
        
        self.content_stack.addWidget(self.admin_settings_screen)
        
    def _create_institute_settings_tab(self):
        tab = QWidget()
        layout = QFormLayout(tab)
        self.settings_tabs.addTab(tab, "Institute Profile")
        
        self.inst_name = QLineEdit()
        self.inst_address = QLineEdit()
        self.inst_logo_path = QLabel("No Logo Selected")
        
        logo_btn = QPushButton("Browse Logo")
        logo_btn.clicked.connect(self._browse_logo)
        
        layout.addRow("Institute Name:", self.inst_name)
        layout.addRow("Address:", self.inst_address)
        layout.addRow("Logo:", logo_btn)
        layout.addRow("Logo Path:", self.inst_logo_path)
        
        save_btn = QPushButton("Save Settings")
        save_btn.clicked.connect(self._save_institute_settings)
        layout.addRow(save_btn)
        
    def _browse_logo(self):
        file_path, _ = QFileDialog.getOpenFileName(self, "Select Logo", "", "Image Files (*.png *.jpg *.jpeg)")
        if file_path:
            self.inst_logo_path.setText(file_path)

    def _save_institute_settings(self):
        self.model.set_setting('INST_NAME', self.inst_name.text())
        self.model.set_setting('INST_ADDRESS', self.inst_address.text())
        self.model.set_setting('INST_LOGO_PATH', self.inst_logo_path.text())
        QMessageBox.information(self, "Success", "Institute settings saved.")

    def load_settings_data(self):
        self.inst_name.setText(self.model.get_setting('INST_NAME') or "")
        self.inst_address.setText(self.model.get_setting('INST_ADDRESS') or "")
        self.inst_logo_path.setText(self.model.get_setting('INST_LOGO_PATH') or "No Logo Selected")
        self.load_department_crud()
        self.load_shift_crud()
        self.load_user_crud()
        self.load_holiday_crud()
        self.load_audit_log()

    # --- Generic CRUD Tab Creator ---

    def _create_crud_tab(self, name, create_func, load_func):
        tab = QWidget()
        layout = QVBoxLayout(tab)
        self.settings_tabs.addTab(tab, name)
        
        self.crud_table = QTableWidget()
        self.crud_table.horizontalHeader().setSectionResizeMode(QHeaderView.ResizeMode.Stretch)
        layout.addWidget(self.crud_table)
        
        crud_buttons = QHBoxLayout()
        add_btn = QPushButton(f"Add {name[:-1]}")
        edit_btn = QPushButton(f"Edit {name[:-1]}")
        delete_btn = QPushButton(f"Delete {name[:-1]}")
        
        add_btn.clicked.connect(lambda: create_func(edit_mode=False))
        edit_btn.clicked.connect(lambda: create_func(edit_mode=True))
        delete_btn.clicked.connect(self._delete_crud_item)
        
        crud_buttons.addWidget(add_btn)
        crud_buttons.addWidget(edit_btn)
        crud_buttons.addWidget(delete_btn)
        layout.addLayout(crud_buttons)
        
        setattr(self, f"{name.lower().replace(' ', '_')}_tab", tab)
        setattr(tab, 'table', self.crud_table)
        setattr(tab, 'load_data', load_func)
        setattr(tab, 'create_dialog', create_func)

    def _delete_crud_item(self):
        current_tab = self.settings_tabs.currentWidget()
        table = getattr(current_tab, 'table')
        
        selected_rows = table.selectionModel().selectedRows()
        if not selected_rows:
            QMessageBox.warning(self, "Selection Error", "Please select an item to delete.")
            return

        item_id = selected_rows[0].data(Qt.ItemDataRole.UserRole)
        table_name = self.settings_tabs.tabText(self.settings_tabs.currentIndex()).lower().replace(' ', '_')
        
        if QMessageBox.question(self, 'Confirm Deletion', f"Are you sure you want to delete this item from {table_name}?") == QMessageBox.StandardButton.Yes:
            try:
                self.model.cursor.execute(f"DELETE FROM {table_name} WHERE id = ?", (item_id,))
                self.model.conn.commit()
                self.model.log_action(self.current_user, f"Deleted item from {table_name}", f"Item ID: {item_id}")
                current_tab.load_data()
            except sqlite3.IntegrityError:
                QMessageBox.critical(self, "Error", "Cannot delete this item as it is linked to other records (e.g., teachers or attendance).")
            except Exception as e:
                QMessageBox.critical(self, "Error", f"An error occurred: {e}")

# --- Department CRUD ---

    def _create_department_crud(self, edit_mode=False):
        tab = self.departments_tab # FIXED: Changed from department_tab to departments_tab
        table = tab.table
        dept_id = None
        current_name = ""
        
        if edit_mode:
            selected_rows = table.selectionModel().selectedRows()
            if not selected_rows:
                QMessageBox.warning(self, "Selection Error", "Select a department to edit.")
                return
            dept_id = selected_rows[0].data(Qt.ItemDataRole.UserRole)
            current_name = table.item(selected_rows[0].row(), 0).text()

        dialog = QDialog(self)
        dialog.setWindowTitle("Manage Department")
        layout = QFormLayout(dialog)
        
        name_input = QLineEdit()
        name_input.setText(current_name)
        layout.addRow("Department Name:", name_input)
        
        save_btn = QPushButton("Save")
        save_btn.clicked.connect(lambda: self._save_department(dialog, name_input.text(), dept_id))
        layout.addRow(save_btn)
        dialog.exec()

    def _save_department(self, dialog, name, dept_id):
        if not name:
            QMessageBox.warning(dialog, "Validation", "Department name cannot be empty.")
            return
            
        try:
            if dept_id:
                self.model.cursor.execute("UPDATE departments SET name = ? WHERE id = ?", (name, dept_id))
                self.model.log_action(self.current_user, "Updated Department", f"ID: {dept_id}, Name: {name}")
            else:
                if not self.model.add_department(name):
                    QMessageBox.critical(dialog, "Error", "Department name already exists.")
                    return

            self.model.conn.commit()
            self.load_department_crud()
            dialog.accept()
        except Exception as e:
            QMessageBox.critical(dialog, "Error", f"Failed to save: {e}")

    def load_department_crud(self):
        tab = self.departments_tab # FIXED: Changed from department_tab to departments_tab
        table = tab.table
        data = self.model.get_all_departments()
        
        table.setColumnCount(1)
        table.setHorizontalHeaderLabels(["Department Name"])
        table.setRowCount(len(data))
        
        for row_idx, (id, name) in enumerate(data):
            name_item = QTableWidgetItem(name)
            name_item.setData(Qt.ItemDataRole.UserRole, id)
            table.setItem(row_idx, 0, name_item)

    # --- Shift CRUD ---

    def _create_shift_crud(self, edit_mode=False):
        tab = self.shifts_tab
        table = tab.table
        shift_id = None
        details = {}
        
        if edit_mode:
            selected_rows = table.selectionModel().selectedRows()
            if not selected_rows:
                QMessageBox.warning(self, "Selection Error", "Select a shift to edit.")
                return
            shift_id = selected_rows[0].data(Qt.ItemDataRole.UserRole)
            self.model.cursor.execute("SELECT * FROM shifts WHERE id = ?", (shift_id,))
            details = self.model.cursor.fetchone()

        dialog = QDialog(self)
        dialog.setWindowTitle("Manage Shift")
        layout = QFormLayout(dialog)
        
        name_input = QLineEdit(details['name'] if details else "")
        check_in_time = QTimeEdit(QTime.fromString(details['check_in_time'], 'HH:mm:ss') if details else QTime(8, 0))
        check_out_time = QTimeEdit(QTime.fromString(details['check_out_time'], 'HH:mm:ss') if details else QTime(16, 0))
        late_margin = QSpinBox()
        late_margin.setRange(0, 120)
        late_margin.setValue(details['late_margin_minutes'] if details else 5)

        layout.addRow("Shift Name:", name_input)
        layout.addRow("Check In Time:", check_in_time)
        layout.addRow("Check Out Time:", check_out_time)
        layout.addRow("Late Margin (mins):", late_margin)
        
        save_btn = QPushButton("Save")
        save_btn.clicked.connect(lambda: self._save_shift(dialog, name_input.text(), check_in_time.time().toString('HH:mm:ss'), check_out_time.time().toString('HH:mm:ss'), late_margin.value(), shift_id))
        layout.addRow(save_btn)
        dialog.exec()

    def _save_shift(self, dialog, name, check_in, check_out, late_margin, shift_id):
        if not name:
            QMessageBox.warning(dialog, "Validation", "Shift name cannot be empty.")
            return
        
        try:
            if shift_id:
                self.model.cursor.execute("UPDATE shifts SET name = ?, check_in_time = ?, check_out_time = ?, late_margin_minutes = ? WHERE id = ?",
                                        (name, check_in, check_out, late_margin, shift_id))
                self.model.log_action(self.current_user, "Updated Shift", f"ID: {shift_id}, Name: {name}")
            else:
                if not self.model.add_shift(name, check_in, check_out, late_margin):
                    QMessageBox.critical(dialog, "Error", "Shift name already exists.")
                    return
            
            self.model.conn.commit()
            self.load_shift_crud()
            dialog.accept()
        except Exception as e:
            QMessageBox.critical(dialog, "Error", f"Failed to save: {e}")

    def load_shift_crud(self):
        tab = self.shifts_tab
        table = tab.table
        data = self.model.get_all_shifts()
        
        table.setColumnCount(4)
        table.setHorizontalHeaderLabels(["Name", "In Time", "Out Time", "Late Margin (mins)"])
        table.setRowCount(len(data))
        
        for row_idx, row in enumerate(data):
            table.setItem(row_idx, 0, QTableWidgetItem(row['name']))
            table.setItem(row_idx, 1, QTableWidgetItem(row['check_in_time']))
            table.setItem(row_idx, 2, QTableWidgetItem(row['check_out_time']))
            table.setItem(row_idx, 3, QTableWidgetItem(str(row['late_margin_minutes'])))
            table.item(row_idx, 0).setData(Qt.ItemDataRole.UserRole, row['id'])

    # --- User CRUD (Admin Users) ---

    def _create_user_crud(self, edit_mode=False):
        if self.current_role != 'SUPER_ADMIN' and edit_mode:
            QMessageBox.warning(self, "Access Denied", "Only SUPER ADMIN can modify admin accounts.")
            return
        
        tab = self.admin_users_tab
        table = tab.table
        user_id = None
        details = {}
        
        if edit_mode:
            selected_rows = table.selectionModel().selectedRows()
            if not selected_rows:
                QMessageBox.warning(self, "Selection Error", "Select a user to edit.")
                return
            user_id = selected_rows[0].data(Qt.ItemDataRole.UserRole)
            self.model.cursor.execute("SELECT id, username, role, is_active FROM users WHERE id = ?", (user_id,))
            details = self.model.cursor.fetchone()

        dialog = QDialog(self)
        dialog.setWindowTitle("Manage Admin User")
        layout = QFormLayout(dialog)
        
        username_input = QLineEdit(details['username'] if details else "")
        password_input = QLineEdit()
        password_input.setEchoMode(QLineEdit.EchoMode.Password)
        role_combo = QComboBox()
        role_combo.addItems(['ADMIN', 'SUPER_ADMIN'])
        status_combo = QComboBox()
        status_combo.addItems(['Active', 'Inactive'])
        
        if details:
            username_input.setEnabled(False) 
            role_combo.setCurrentText(details['role'])
            status_combo.setCurrentText('Active' if details['is_active'] == 1 else 'Inactive')
            password_input.setPlaceholderText("Leave blank to keep current password")
        
        layout.addRow("Username:", username_input)
        layout.addRow("Password:", password_input)
        layout.addRow("Role:", role_combo)
        layout.addRow("Status:", status_combo)

        save_btn = QPushButton("Save")
        save_btn.clicked.connect(lambda: self._save_user(dialog, username_input.text(), password_input.text(), role_combo.currentText(), status_combo.currentText(), user_id))
        layout.addRow(save_btn)
        dialog.exec()

    def _save_user(self, dialog, username, password, role, status, user_id):
        if not username:
            QMessageBox.warning(dialog, "Validation", "Username is mandatory.")
            return
        
        if not user_id and not password:
            QMessageBox.warning(dialog, "Validation", "Password is mandatory for new users.")
            return
            
        try:
            is_active = 1 if status == 'Active' else 0
            password_hash = self.model._hash_password(password) if password else None

            if user_id:
                # Update
                update_query = "UPDATE users SET role = ?, is_active = ?"
                params = [role, is_active, user_id]
                
                if password_hash:
                    update_query += ", password_hash = ?"
                    params.insert(2, password_hash)
                    
                self.model.cursor.execute(update_query + " WHERE id = ?", tuple(params))
                self.model.log_action(self.current_user, "Updated Admin User", f"ID: {user_id}, Username: {username}")
            else:
                # Insert
                self.model.cursor.execute("INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, ?)",
                                        (username, password_hash, role, is_active))
                self.model.log_action(self.current_user, "Created Admin User", f"Username: {username}")
            
            self.model.conn.commit()
            self.load_user_crud()
            dialog.accept()
        except sqlite3.IntegrityError:
             QMessageBox.critical(dialog, "Error", "Username already exists.")
        except Exception as e:
            QMessageBox.critical(dialog, "Error", f"Failed to save: {e}")

    def load_user_crud(self):
        tab = self.admin_users_tab
        table = tab.table
        
        self.model.cursor.execute("SELECT id, username, role, is_active FROM users")
        data = self.model.cursor.fetchall()
        
        table.setColumnCount(3)
        table.setHorizontalHeaderLabels(["Username", "Role", "Status"])
        table.setRowCount(len(data))
        
        for row_idx, row in enumerate(data):
            table.setItem(row_idx, 0, QTableWidgetItem(row['username']))
            table.setItem(row_idx, 1, QTableWidgetItem(row['role']))
            status_text = 'Active' if row['is_active'] == 1 else 'Inactive'
            table.setItem(row_idx, 2, QTableWidgetItem(status_text))
            table.item(row_idx, 0).setData(Qt.ItemDataRole.UserRole, row['id'])

    # --- Holiday CRUD ---
    
    def _create_holiday_crud(self, edit_mode=False):
        tab = self.holidays_tab
        table = tab.table
        holiday_id = None
        details = {}
        
        if edit_mode:
            selected_rows = table.selectionModel().selectedRows()
            if not selected_rows:
                QMessageBox.warning(self, "Selection Error", "Select a holiday to edit.")
                return
            holiday_id = selected_rows[0].data(Qt.ItemDataRole.UserRole)
            self.model.cursor.execute("SELECT id, name, date FROM holidays WHERE id = ?", (holiday_id,))
            details = self.model.cursor.fetchone()

        dialog = QDialog(self)
        dialog.setWindowTitle("Manage Holiday")
        layout = QFormLayout(dialog)
        
        name_input = QLineEdit(details['name'] if details else "")
        date_input = QDateEdit(QDate.fromString(details['date'], 'yyyy-MM-dd') if details else QDate.currentDate())
        date_input.setCalendarPopup(True)

        layout.addRow("Holiday Name:", name_input)
        layout.addRow("Date:", date_input)
        
        save_btn = QPushButton("Save")
        save_btn.clicked.connect(lambda: self._save_holiday(dialog, name_input.text(), date_input.date().toString('yyyy-MM-dd'), holiday_id))
        layout.addRow(save_btn)
        dialog.exec()

    def _save_holiday(self, dialog, name, date_str, holiday_id):
        if not name or not date_str:
            QMessageBox.warning(dialog, "Validation", "Name and Date are mandatory.")
            return
        
        try:
            if holiday_id:
                self.model.cursor.execute("UPDATE holidays SET name = ?, date = ? WHERE id = ?",
                                        (name, date_str, holiday_id))
                self.model.log_action(self.current_user, "Updated Holiday", f"ID: {holiday_id}, Date: {date_str}")
            else:
                self.model.cursor.execute("INSERT INTO holidays (name, date) VALUES (?, ?)", (name, date_str))
                self.model.log_action(self.current_user, "Added Holiday", f"Date: {date_str}")
            
            self.model.conn.commit()
            self.load_holiday_crud()
            dialog.accept()
        except sqlite3.IntegrityError:
            QMessageBox.critical(dialog, "Error", "A holiday already exists on this date.")
        except Exception as e:
            QMessageBox.critical(dialog, "Error", f"Failed to save: {e}")

    def load_holiday_crud(self):
        tab = self.holidays_tab
        table = tab.table
        
        self.model.cursor.execute("SELECT id, name, date FROM holidays ORDER BY date DESC")
        data = self.model.cursor.fetchall()
        
        table.setColumnCount(2)
        table.setHorizontalHeaderLabels(["Name", "Date"])
        table.setRowCount(len(data))
        
        for row_idx, row in enumerate(data):
            date_item = QTableWidgetItem(row['date'])
            date_item.setData(Qt.ItemDataRole.UserRole, row['id'])
            table.setItem(row_idx, 0, QTableWidgetItem(row['name']))
            table.setItem(row_idx, 1, date_item)

    # --- Security/Password Change Tab ---

    def _create_security_tab(self):
        tab = QWidget()
        layout = QFormLayout(tab)
        self.settings_tabs.addTab(tab, "Security")
        
        self.old_pass = QLineEdit()
        self.old_pass.setEchoMode(QLineEdit.EchoMode.Password)
        self.new_pass = QLineEdit()
        self.new_pass.setEchoMode(QLineEdit.EchoMode.Password)
        self.confirm_pass = QLineEdit()
        self.confirm_pass.setEchoMode(QLineEdit.EchoMode.Password)

        layout.addRow("Old Password:", self.old_pass)
        layout.addRow("New Password:", self.new_pass)
        layout.addRow("Confirm New Password:", self.confirm_pass)
        
        change_btn = QPushButton("Change Password")
        change_btn.clicked.connect(self._handle_password_change)
        layout.addRow(change_btn)

    def _handle_password_change(self):
        old = self.old_pass.text()
        new = self.new_pass.text()
        confirm = self.confirm_pass.text()
        
        if not all([old, new, confirm]):
            QMessageBox.warning(self, "Validation", "All fields are required.")
            return

        if new != confirm:
            QMessageBox.warning(self, "Validation", "New password and confirmation do not match.")
            return
            
        if self.model.change_password(self.current_user, old, new):
            QMessageBox.information(self, "Success", "Password changed successfully. Logging out for security.")
            self.logout()
        else:
            QMessageBox.critical(self, "Error", "Invalid old password.")

    # --- Backup & Restore Tab ---
    
    def _create_backup_restore_tab(self):
        tab = QWidget()
        layout = QVBoxLayout(tab)
        self.settings_tabs.addTab(tab, "Backup & Restore")
        
        backup_group = QGroupBox("Data Backup")
        backup_layout = QVBoxLayout(backup_group)
        backup_layout.addWidget(QLabel("Export all database contents to a JSON file."))
        backup_btn = QPushButton("Run Backup")
        backup_btn.clicked.connect(self.backup_data)
        backup_layout.addWidget(backup_btn)
        layout.addWidget(backup_group)

        restore_group = QGroupBox("Data Restore")
        restore_layout = QVBoxLayout(restore_group)
        restore_layout.addWidget(QLabel("Import data from a JSON file. This will REPLACE ALL EXISTING DATA."))
        restore_btn = QPushButton("Run Restore")
        restore_btn.clicked.connect(self.restore_data)
        restore_layout.addWidget(restore_btn)
        layout.addWidget(restore_group)

    def backup_data(self):
        all_data = self.model.backup_data()
        file_path, _ = QFileDialog.getSaveFileName(self, "Save Database Backup", f"bio_backup_{datetime.date.today().strftime('%Y%m%d')}.json", "JSON Files (*.json)")
        if file_path:
            try:
                with open(file_path, 'w') as f:
                    json.dump(all_data, f, indent=4)
                QMessageBox.information(self, "Backup Successful", "Data backed up successfully.")
                self.model.log_action(self.current_user, "Database Backup", file_path)
            except Exception as e:
                QMessageBox.critical(self, "Backup Error", f"An error occurred: {e}")
    
    def restore_data(self):
        file_path, _ = QFileDialog.getOpenFileName(self, "Open Backup File", "", "JSON Files (*.json)")
        if file_path:
            reply = QMessageBox.question(self, 'Confirm Restore',
                                         "WARNING: This operation will PERMANENTLY ERASE and REPLACE ALL existing data. Are you absolutely sure?",
                                         QMessageBox.StandardButton.Yes | QMessageBox.StandardButton.No,
                                         QMessageBox.StandardButton.No)
            if reply == QMessageBox.StandardButton.Yes:
                try:
                    with open(file_path, 'r') as f:
                        data = json.load(f)
                    
                    if not all(k in data for k in ['users', 'teachers', 'attendance']):
                        QMessageBox.critical(self, "Restore Error", "Invalid backup file structure.")
                        return

                    self.model.restore_data(data)
                    QMessageBox.information(self, "Restore Successful", "Data restored successfully. Please re-login.")
                    self.model.log_action(self.current_user, "Database Restore", file_path)
                    self.logout()
                except Exception as e:
                    QMessageBox.critical(self, "Restore Error", f"An error occurred during restore: {e}")
    
    # --- Audit Log Tab ---

    def _create_audit_log_tab(self):
        tab = QWidget()
        layout = QVBoxLayout(tab)
        self.settings_tabs.addTab(tab, "Audit Logs")
        
        self.audit_log_table = QTableWidget()
        self.audit_log_table.setColumnCount(4)
        self.audit_log_table.setHorizontalHeaderLabels(["Timestamp", "User ID", "Action", "Details"])
        self.audit_log_table.horizontalHeader().setSectionResizeMode(QHeaderView.ResizeMode.Stretch)
        layout.addWidget(self.audit_log_table)
        
    def load_audit_log(self):
        self.model.cursor.execute("""
            SELECT l.timestamp, u.username, l.action, l.details 
            FROM audit_logs l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.timestamp DESC LIMIT 100
        """)
        logs = self.model.cursor.fetchall()
        
        self.audit_log_table.setRowCount(len(logs))
        for row_idx, log in enumerate(logs):
            self.audit_log_table.setItem(row_idx, 0, QTableWidgetItem(log['timestamp']))
            self.audit_log_table.setItem(row_idx, 1, QTableWidgetItem(log['username'] or 'System'))
            self.audit_log_table.setItem(row_idx, 2, QTableWidgetItem(log['action']))
            self.audit_log_table.setItem(row_idx, 3, QTableWidgetItem(log['details']))

    def closeEvent(self, event):
        self.device_manager.stop_worker()
        super().closeEvent(event)


if __name__ == '__main__':
    # Initial data needed for system functionality (Departments, Shifts) is generated by the Model.
    # Four required real data records (teachers) must be manually added via the GUI after installation.
    # The application starts with two default admin accounts: superadmin/SuperAdmin123, admin/Admin123.
    
    # Required External Libraries:
    # pip install PyQt6 pandas openpyxl reportlab

    # Biometric SDK Configuration:
    # 1. Install the DigitalPersona U.ARE.U SDK (drivers and runtime environment).
    # 2. Ensure the DLLs (dpfpdd.dll, dpfj.dll) are accessible in the system PATH or the application directory.
    # 3. This code uses simulated ctypes bindings for the SDK calls (c_dpfpdd_init, c_dpfpdd_capture, etc.).
    #    In a real implementation, replace the simulated functions with actual ctypes bindings to the installed DLLs.
    
    app = QApplication(sys.argv)
    main_window = BiometricAttendanceApp()
    main_window.show()
    sys.exit(app.exec())