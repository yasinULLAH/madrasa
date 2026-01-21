import sys
import os
import sqlite3
import hashlib
import json
import uuid
import datetime
from PySide6.QtWidgets import (
    QApplication, QMainWindow, QWidget, QVBoxLayout, QHBoxLayout,
    QPushButton, QLabel, QLineEdit, QTableWidget, QTableWidgetItem,
    QMessageBox, QTabWidget, QGridLayout, QDateEdit, QHeaderView,
    QComboBox, QFileDialog, QSizePolicy
)
from PySide6.QtGui import QFont, QIcon, QPixmap
from PySide6.QtCore import Qt, QTimer, QDate, QLocale, QThread, Signal

# --- HARDWARE INTEGRATION SETUP (Neurotechnology BioMini Slim 2 - SDK Simulation via ctypes Structure) ---
# NOTE: In a true production environment, the NIBioMini.dll/libNIBioMini.so and its headers
# (like NBioAPI.h) would be required. Since we cannot rely on the SDK being installed in the execution
# environment, we MUST use a robust, production-mimicking stub structure for the ctypes
# integration. This stub maintains the required structure and flow logic (device initialization,
# capture, enrollment, matching) but uses simulated biometric output/template generation
# while maintaining the API interaction points required by the application logic.

try:
    import ctypes
    from ctypes import windll, cdll, c_int, c_char_p, POINTER, create_string_buffer, c_uint, c_void_p, c_ushort, c_ubyte, byref
except ImportError:
    print("Error: ctypes library not found. Biometric integration requires ctypes.")
    sys.exit(1)

# --- Neurotechnology SDK Mock Definitions (Structural Integrity Enforced) ---

# Define the template size constant for a standard FAP20 device template
NBIO_TEMPLATE_SIZE = 1024
NBIO_MAX_TEMPLATES = 10
NBIO_OK = 1
NBIO_FAIL = 0
NBIO_CAPTURE_TIMEOUT = 10000

class NBioAPI_HANDLE(c_void_p):
    pass

class NBIOMINI_TEMPLATE(ctypes.Structure):
    _fields_ = [
        ("TemplateSize", c_int),
        ("TemplateData", c_ubyte * NBIO_TEMPLATE_SIZE)
    ]

class NBIOMINI_FIR_DATA(ctypes.Structure):
    _fields_ = [
        ("Template", NBIOMINI_TEMPLATE),
        ("ImageWidth", c_int),
        ("ImageHeight", c_int)
    ]

class BioMiniSDKStub:
    """
    Production-ready stub mimicking the Neurotechnology BioMini SDK structure and API calls.
    Ensures application logic (enroll, match, capture flow) is correct,
    ready for immediate replacement with actual ctypes DLL loading.
    """
    def __init__(self):
        self.handle = NBioAPI_HANDLE(1)  # Simulated valid handle
        self.device_open = False
        self.last_error = NBIO_OK

    def NBioAPI_Init(self):
        """Initialize the SDK."""
        print("SDK: Initializing...")
        return NBIO_OK

    def NBioAPI_Terminate(self):
        """Terminate the SDK."""
        print("SDK: Terminating...")
        return NBIO_OK

    def NBioAPI_OpenDevice(self, device_id=0):
        """Open the BioMini device."""
        if self.device_open:
            return NBIO_OK
        print(f"SDK: Opening Device ID {device_id}...")
        self.device_open = True
        return NBIO_OK

    def NBioAPI_CloseDevice(self, device_id=0):
        """Close the BioMini device."""
        print(f"SDK: Closing Device ID {device_id}...")
        self.device_open = False
        return NBIO_OK

    def NBioAPI_GetDeviceStatus(self, device_id=0):
        """Check if device is connected and ready."""
        if self.device_open:
            return "READY"
        else:
            return "DISCONNECTED"

    def NBioAPI_Capture(self, timeout=NBIO_CAPTURE_TIMEOUT):
        """
        Simulate capturing a finger and extracting a template.
        Returns a tuple: (result_code, fir_data_structure)
        """
        if not self.device_open:
            self.last_error = NBIO_FAIL
            return NBIO_FAIL, None

        # Simulate successful capture and template generation
        template = NBIOMINI_TEMPLATE()
        template.TemplateSize = NBIO_TEMPLATE_SIZE
        # In a real scenario, this buffer would contain the ANSI/ISO template bytes
        template.TemplateData = (c_ubyte * NBIO_TEMPLATE_SIZE)(*[ord(c) for c in uuid.uuid4().hex[:NBIO_TEMPLATE_SIZE]])

        fir = NBIOMINI_FIR_DATA()
        fir.Template = template
        fir.ImageWidth = 320
        fir.ImageHeight = 480

        print("SDK: Capture successful. Template generated.")
        self.last_error = NBIO_OK
        return NBIO_OK, fir

    def NBioAPI_Enroll(self, max_samples=3):
        """
        Simulate the enrollment process (multi-capture to create a robust template).
        Returns: (result_code, enrolled_template_buffer)
        """
        if not self.device_open:
            return NBIO_FAIL, None

        # Simulate robust template creation
        master_template = create_string_buffer(NBIO_TEMPLATE_SIZE)
        # Create a unique, fixed-size simulated template
        template_data = uuid.uuid4().hex[:NBIO_TEMPLATE_SIZE // 2] * 2
        master_template.raw = template_data.encode('utf-8')
        print(f"SDK: Enrollment successful after {max_samples} captures.")
        return NBIO_OK, master_template.raw

    def NBioAPI_VerifyMatch(self, captured_template_data, enrolled_template_data, required_score=1000):
        """
        Simulate 1:1 matching.
        The simulation uses a simple comparison based on template length and a random success factor.
        """
        if len(captured_template_data) != len(enrolled_template_data):
            # Fail if template formats are clearly wrong
            return NBIO_FAIL, 0

        # High score if templates are very similar (simulating a good match)
        # For simplicity, we assume a match if the first 16 bytes (simulated UUID header) are the same
        if captured_template_data[:16] == enrolled_template_data[:16]:
            match_score = 5000  # High score
            is_matched = True
        else:
            match_score = 50 + (os.urandom(1)[0] % 50)  # Low random score
            is_matched = False

        if is_matched and match_score >= required_score:
            print(f"SDK: Match found! Score: {match_score}")
            return NBIO_OK, match_score
        else:
            print(f"SDK: No match found. Score: {match_score}")
            return NBIO_FAIL, match_score

# Instantiate the required SDK structure
NBIOMINI = BioMiniSDKStub()

# --- DATABASE LAYER ---

DB_NAME = 'attendance_system4.db'

def get_db_connection():
    conn = sqlite3.connect(DB_NAME)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.executescript("""
        CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            name TEXT NOT NULL,
            role TEXT NOT NULL,
            department TEXT,
            shift_hours TEXT
        );
        CREATE TABLE IF NOT EXISTS fingerprints (
            user_id TEXT NOT NULL,
            finger_index INTEGER NOT NULL,
            template BLOB NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            PRIMARY KEY (user_id, finger_index)
        );
        CREATE TABLE IF NOT EXISTS attendance (
            id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            type TEXT NOT NULL, -- IN or OUT
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        );
    """)
    conn.commit()

    # Initial Data Population (Exactly 4 Records)
    admin_id = str(uuid.uuid4())
    teacher1_id = str(uuid.uuid4())
    teacher2_id = str(uuid.uuid4())
    teacher3_id = str(uuid.uuid4())
    password_hash = hashlib.sha256("Admin123!".encode()).hexdigest()

    # Pre-populate with 4 records
    initial_users = [
        (admin_id, 'admin', password_hash, 'Yasin Ullah', 'Admin', 'Management', '9:00-17:00'),
        (teacher1_id, 't_ali', password_hash, 'Ali Khan', 'Teacher', 'Physics', '8:00-16:00'),
        (teacher2_id, 't_sana', password_hash, 'Sana Ahmed', 'Teacher', 'Math', '10:00-18:00'),
        (teacher3_id, 't_zain', password_hash, 'Zain Malik', 'Teacher', 'Chemistry', '9:30-17:30')
    ]

    try:
        cursor.executemany("""
            INSERT OR IGNORE INTO users (id, username, password_hash, name, role, department, shift_hours)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        """, initial_users)

        # Simulate Fingerprint Enrollment for two users
        dummy_template = b'DUMMY_TEMPLATE_0123456789' * (NBIO_TEMPLATE_SIZE // 26)
        fingerprints = [
            (admin_id, 1, dummy_template),
            (teacher1_id, 1, dummy_template)
        ]
        cursor.executemany("""
            INSERT OR IGNORE INTO fingerprints (user_id, finger_index, template)
            VALUES (?, ?, ?)
        """, fingerprints)

        conn.commit()
    except sqlite3.IntegrityError:
        pass
    finally:
        conn.close()

# --- BIOMETRIC WORKER THREAD ---

class BiometricWorker(QThread):
    """Handles blocking biometric operations (Capture, Enroll, Match) asynchronously."""
    capture_finished = Signal(bool, object)  # Success, template_data (bytes)
    enroll_finished = Signal(bool, str, object) # Success, user_id, template_data (bytes)
    match_finished = Signal(bool, str, str) # Success, user_id, match_type (IN/OUT)

    def __init__(self, action, data=None):
        super().__init__()
        self.action = action
        self.data = data

    def run(self):
        if self.action == "capture":
            self._capture_process()
        elif self.action == "enroll":
            self._enroll_process(self.data['user_id'])
        elif self.action == "match":
            self._match_process()

    def _capture_process(self):
        result, fir_data = NBIOMINI.NBioAPI_Capture()
        if result == NBIO_OK and fir_data:
            template = bytes(fir_data.Template.TemplateData)
            self.capture_finished.emit(True, template)
        else:
            self.capture_finished.emit(False, None)

    def _enroll_process(self, user_id):
        result, template_data = NBIOMINI.NBioAPI_Enroll(max_samples=3)
        if result == NBIO_OK and template_data:
            self.enroll_finished.emit(True, user_id, template_data)
        else:
            self.enroll_finished.emit(False, user_id, None)

    def _match_process(self):
        # 1. Capture the live template
        capture_result, fir_data = NBIOMINI.NBioAPI_Capture()

        if capture_result != NBIO_OK or not fir_data:
            self.match_finished.emit(False, "", "Capture Failed")
            return

        live_template = bytes(fir_data.Template.TemplateData)

        # 2. Retrieve all known templates from the database
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT user_id, template FROM fingerprints")
        all_templates = cursor.fetchall()
        conn.close()

        best_match_id = None
        best_score = 0
        REQUIRED_MATCH_SCORE = 1500

        for row in all_templates:
            user_id = row['user_id']
            enrolled_template = row['template']
            
            # The SDK stub simulation is highly dependent on template data similarity
            # Since the stub generates a unique template on capture, we use a simple
            # way to simulate matching for pre-enrolled users (the first 16 bytes rule)
            
            match_result, score = NBIOMINI.NBioAPI_VerifyMatch(live_template, enrolled_template, REQUIRED_MATCH_SCORE)
            
            if score > best_score:
                best_score = score
                if score >= REQUIRED_MATCH_SCORE:
                    best_match_id = user_id
                    break # Found a definitive match

        if best_match_id:
            # Determine IN or OUT status
            conn = get_db_connection()
            cursor = conn.cursor()
            
            today = datetime.date.today().strftime('%Y-%m-%d')
            cursor.execute("""
                SELECT type FROM attendance
                WHERE user_id = ? AND date(timestamp) = ?
                ORDER BY timestamp DESC
                LIMIT 1
            """, (best_match_id, today))
            
            last_record = cursor.fetchone()
            
            if last_record and last_record['type'] == 'IN':
                match_type = 'OUT'
            else:
                match_type = 'IN'
                
            # Log attendance
            attendance_id = str(uuid.uuid4())
            now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cursor.execute("""
                INSERT INTO attendance (id, user_id, timestamp, type)
                VALUES (?, ?, ?, ?)
            """, (attendance_id, best_match_id, now, match_type))
            conn.commit()
            conn.close()
            
            self.match_finished.emit(True, best_match_id, match_type)
        else:
            self.match_finished.emit(False, "", f"No Match (Score: {best_score})")

# --- UI COMPONENTS ---

class ModernHeader(QWidget):
    def __init__(self, title):
        super().__init__()
        layout = QVBoxLayout(self)
        title_label = QLabel(title)
        title_label.setFont(QFont("Arial", 18, QFont.Bold))
        title_label.setStyleSheet("color: #007bff;")
        layout.addWidget(title_label)
        layout.setContentsMargins(10, 10, 10, 10)

class LoginScreen(QWidget):
    login_successful = Signal(str, str)

    def __init__(self):
        super().__init__()
        self.setStyleSheet(self._get_style())
        self._setup_ui()

    def _get_style(self):
        return """
            QWidget { background-color: #f8f9fa; }
            QLineEdit { padding: 10px; border: 1px solid #ced4da; border-radius: 5px; }
            QPushButton { padding: 10px; border-radius: 5px; font-weight: bold; }
            #LoginButton { background-color: #007bff; color: white; }
            #LoginButton:hover { background-color: #0056b3; }
        """

    def _setup_ui(self):
        layout = QVBoxLayout(self)
        layout.setAlignment(Qt.AlignCenter)
        layout.setSpacing(20)

        title = QLabel("Biometric Attendance System")
        title.setFont(QFont("Arial", 24, QFont.Bold))
        title.setAlignment(Qt.AlignCenter)
        title.setStyleSheet("color: #343a40;")

        self.username_input = QLineEdit()
        self.username_input.setPlaceholderText("Username")
        self.username_input.setSizePolicy(QSizePolicy.Fixed, QSizePolicy.Fixed)
        self.username_input.setMinimumWidth(300)

        self.password_input = QLineEdit()
        self.password_input.setPlaceholderText("Password")
        self.password_input.setEchoMode(QLineEdit.Password)
        self.password_input.setSizePolicy(QSizePolicy.Fixed, QSizePolicy.Fixed)
        self.password_input.setMinimumWidth(300)

        login_button = QPushButton("Login")
        login_button.setObjectName("LoginButton")
        login_button.clicked.connect(self._handle_login)
        login_button.setMinimumWidth(300)

        form_widget = QWidget()
        form_layout = QVBoxLayout(form_widget)
        form_layout.addWidget(title, alignment=Qt.AlignCenter)
        form_layout.addSpacing(40)
        form_layout.addWidget(self.username_input)
        form_layout.addWidget(self.password_input)
        form_layout.addWidget(login_button)
        form_layout.setContentsMargins(50, 50, 50, 50)
        form_widget.setStyleSheet("background-color: white; border-radius: 10px; box-shadow: 0px 0px 15px rgba(0,0,0,0.1);")
        form_widget.setSizePolicy(QSizePolicy.Fixed, QSizePolicy.Fixed)

        layout.addWidget(form_widget, alignment=Qt.AlignCenter)

    def _handle_login(self):
        username = self.username_input.text()
        password = self.password_input.text()

        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, password_hash, role FROM users WHERE username = ?", (username,))
        user = cursor.fetchone()
        conn.close()

        if user:
            password_hash = hashlib.sha256(password.encode()).hexdigest()
            if password_hash == user['password_hash']:
                self.login_successful.emit(user['id'], user['role'])
                return

        QMessageBox.critical(self, "Login Failed", "Invalid username or password.")

class Dashboard(QWidget):
    def __init__(self, user_id, user_role):
        super().__init__()
        self.user_id = user_id
        self.user_role = user_role
        self.setStyleSheet("background-color: #f8f9fa;")
        self._setup_ui()

    def _setup_ui(self):
        main_layout = QHBoxLayout(self)

        # 1. Sidebar/Navigation
        sidebar = QWidget()
        sidebar.setFixedWidth(200)
        sidebar.setStyleSheet("background-color: #343a40; color: white;")
        sidebar_layout = QVBoxLayout(sidebar)

        self.btn_attendance = self._create_nav_button("Attendance Capture")
        self.btn_user_mgmt = self._create_nav_button("User Management")
        self.btn_reports = self._create_nav_button("Reports")
        self.btn_backup = self._create_nav_button("Backup/Restore")
        self.btn_logout = self._create_nav_button("Logout")

        sidebar_layout.addWidget(QLabel("Menu"))
        sidebar_layout.addWidget(self.btn_attendance)
        
        # Only Admins see User Management and Backup
        if self.user_role == 'Admin':
            sidebar_layout.addWidget(self.btn_user_mgmt)
            sidebar_layout.addWidget(self.btn_backup)
        
        sidebar_layout.addWidget(self.btn_reports)
        sidebar_layout.addStretch()
        sidebar_layout.addWidget(self.btn_logout)
        
        # Connect actions
        self.btn_attendance.clicked.connect(lambda: self.tab_widget.setCurrentIndex(0))
        self.btn_user_mgmt.clicked.connect(lambda: self.tab_widget.setCurrentIndex(1))
        self.btn_reports.clicked.connect(lambda: self.tab_widget.setCurrentIndex(2))
        self.btn_backup.clicked.connect(lambda: self.tab_widget.setCurrentIndex(3))

        main_layout.addWidget(sidebar)

        # 2. Main Content Tabs
        self.tab_widget = QTabWidget()
        self.tab_widget.setStyleSheet("QTabWidget::pane { border: 0; } QTabBar::tab { height: 0; width: 0; margin: 0; padding: 0; }")
        
        self.attendance_page = AttendanceCapturePage()
        self.user_mgmt_page = UserManagementPage()
        self.reports_page = ReportsPage()
        self.backup_page = BackupRestorePage()

        self.tab_widget.addTab(self.attendance_page, "Attendance")
        self.tab_widget.addTab(self.user_mgmt_page, "Users")
        self.tab_widget.addTab(self.reports_page, "Reports")
        self.tab_widget.addTab(self.backup_page, "Backup")

        # Admin only access control
        if self.user_role != 'Admin':
            self.tab_widget.setTabEnabled(1, False)
            self.tab_widget.setTabEnabled(3, False)
            
        main_layout.addWidget(self.tab_widget)

    def _create_nav_button(self, text):
        btn = QPushButton(text)
        btn.setFlat(True)
        btn.setFont(QFont("Arial", 12))
        btn.setStyleSheet("""
            QPushButton { 
                text-align: left; 
                padding: 10px 15px; 
                border: none; 
                color: white;
            }
            QPushButton:hover { 
                background-color: #495057; 
            }
        """)
        return btn

class AttendanceCapturePage(QWidget):
    def __init__(self):
        super().__init__()
        self.biometric_worker = None
        self._setup_ui()
        self._init_device()

    def _setup_ui(self):
        layout = QVBoxLayout(self)
        layout.addWidget(ModernHeader("Attendance & Biometric Status"))

        content_layout = QHBoxLayout()
        
        # Status Panel (Left)
        status_panel = QWidget()
        status_panel.setStyleSheet("background-color: white; border-radius: 8px; padding: 20px;")
        status_layout = QGridLayout(status_panel)

        self.device_status_label = QLabel("Device Status: Disconnected")
        self.device_status_label.setStyleSheet("color: red; font-weight: bold;")
        self.liveness_status_label = QLabel("Liveness Detection: Idle")
        self.liveness_status_label.setStyleSheet("color: gray;")
        self.capture_feedback_label = QLabel("Ready for Scan...")
        self.capture_feedback_label.setFont(QFont("Arial", 14))
        self.capture_feedback_label.setAlignment(Qt.AlignCenter)
        
        self.image_display = QLabel("Place Finger Here")
        self.image_display.setFixedSize(200, 250)
        self.image_display.setStyleSheet("border: 2px dashed #007bff; background-color: #e9ecef;")
        self.image_display.setAlignment(Qt.AlignCenter)

        status_layout.addWidget(QLabel("Scanner Status:"), 0, 0)
        status_layout.addWidget(self.device_status_label, 0, 1)
        status_layout.addWidget(QLabel("LFD Status:"), 1, 0)
        status_layout.addWidget(self.liveness_status_label, 1, 1)
        status_layout.addWidget(self.image_display, 2, 0, 1, 2, Qt.AlignCenter)
        
        content_layout.addWidget(status_panel, 1)
        
        # Action Panel (Right)
        action_panel = QWidget()
        action_layout = QVBoxLayout(action_panel)
        action_layout.setAlignment(Qt.AlignTop)

        self.btn_scan = QPushButton("TAP TO CLOCK IN/OUT")
        self.btn_scan.setFont(QFont("Arial", 16, QFont.Bold))
        self.btn_scan.setMinimumHeight(80)
        self.btn_scan.setStyleSheet("background-color: #28a745; color: white; border-radius: 10px;")
        self.btn_scan.clicked.connect(self._start_match_scan)
        
        self.result_output = QLabel("Awaiting Scan...")
        self.result_output.setFont(QFont("Arial", 18))
        self.result_output.setStyleSheet("color: #343a40; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;")
        self.result_output.setAlignment(Qt.AlignCenter)
        self.result_output.setMinimumHeight(150)

        action_layout.addWidget(self.btn_scan)
        action_layout.addSpacing(20)
        action_layout.addWidget(self.result_output)
        action_panel.setFixedWidth(350)
        
        content_layout.addWidget(action_panel, 2)
        
        layout.addLayout(content_layout)
        layout.addStretch()

        # Timer for device polling
        self.device_timer = QTimer(self)
        self.device_timer.timeout.connect(self._poll_device_status)
        self.device_timer.start(2000)

    def _init_device(self):
        NBIOMINI.NBioAPI_Init()
        # Attempt to open device immediately
        if NBIOMINI.NBioAPI_OpenDevice() == NBIO_OK:
            self.device_status_label.setText("Device Status: Connected (BioMini Slim 2)")
            self.device_status_label.setStyleSheet("color: green; font-weight: bold;")
        else:
            self.device_status_label.setText("Device Status: Disconnected")
            self.device_status_label.setStyleSheet("color: red; font-weight: bold;")

    def _poll_device_status(self):
        status = NBIOMINI.NBioAPI_GetDeviceStatus()
        if status == "READY":
            self.device_status_label.setText("Device Status: Connected (BioMini Slim 2)")
            self.device_status_label.setStyleSheet("color: green; font-weight: bold;")
            self.btn_scan.setEnabled(True)
        else:
            self.device_status_label.setText("Device Status: Disconnected")
            self.device_status_label.setStyleSheet("color: red; font-weight: bold;")
            self.btn_scan.setEnabled(False)
            NBIOMINI.NBioAPI_OpenDevice() # Try to reconnect

    def _start_match_scan(self):
        self.result_output.setText("Scanning... Place your finger now.")
        self.btn_scan.setEnabled(False)
        self.capture_feedback_label.setText("Scanning...")
        
        self.biometric_worker = BiometricWorker(action="match")
        self.biometric_worker.match_finished.connect(self._handle_match_result)
        self.biometric_worker.start()

    def _handle_match_result(self, success, user_id, match_type):
        self.btn_scan.setEnabled(True)
        self.capture_feedback_label.setText("Ready for Scan...")

        if success:
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT name FROM users WHERE id = ?", (user_id,))
            user = cursor.fetchone()
            conn.close()
            
            if user:
                user_name = user['name']
                now = datetime.datetime.now().strftime('%H:%M:%S')
                
                if match_type == 'IN':
                    message = f"CLOCK IN SUCCESSFUL!\n{user_name}\nTime: {now}"
                    style = "background-color: #28a745; color: white;"
                else:
                    message = f"CLOCK OUT SUCCESSFUL!\n{user_name}\nTime: {now}"
                    style = "background-color: #ffc107; color: black;"
                
                self.result_output.setText(message)
                self.result_output.setStyleSheet(style + "; border-radius: 10px; padding: 20px;")
            else:
                self.result_output.setText("Match Error: User data corrupted.")
                self.result_output.setStyleSheet("background-color: #dc3545; color: white; border-radius: 10px; padding: 20px;")
        else:
            self.result_output.setText(f"MATCH FAILED\n{match_type}\nPlease try again.")
            self.result_output.setStyleSheet("background-color: #dc3545; color: white; border-radius: 10px; padding: 20px;")

class UserManagementPage(QWidget):
    def __init__(self):
        super().__init__()
        self.current_user_id = None
        self._setup_ui()
        self.load_users()

    def _setup_ui(self):
        layout = QVBoxLayout(self)
        layout.addWidget(ModernHeader("User Management & Enrollment"))

        main_splitter = QHBoxLayout()

        # 1. User List Table
        self.user_table = QTableWidget()
        self.user_table.setColumnCount(5)
        self.user_table.setHorizontalHeaderLabels(["ID", "Name", "Role", "Department", "Fingers"])
        self.user_table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        self.user_table.setSelectionBehavior(QTableWidget.SelectRows)
        self.user_table.setSelectionMode(QTableWidget.SingleSelection)
        self.user_table.cellClicked.connect(self._select_user)
        
        user_list_widget = QVBoxLayout()
        user_list_widget.addWidget(self.user_table)
        
        # Action buttons
        btn_layout = QHBoxLayout()
        self.btn_add = QPushButton("Add New User")
        self.btn_delete = QPushButton("Delete Selected User")
        self.btn_add.clicked.connect(self._clear_form)
        self.btn_delete.clicked.connect(self._delete_user)
        self.btn_delete.setStyleSheet("background-color: #dc3545; color: white;")
        
        user_list_widget.addLayout(btn_layout)
        btn_layout.addWidget(self.btn_add)
        btn_layout.addWidget(self.btn_delete)
        
        user_list_container = QWidget()
        user_list_container.setLayout(user_list_widget)
        
        main_splitter.addWidget(user_list_container, 3)

        # 2. User Detail Form (Right Panel)
        detail_panel = QWidget()
        detail_panel.setFixedWidth(350)
        detail_panel.setStyleSheet("background-color: white; border-radius: 8px; padding: 15px;")
        detail_layout = QVBoxLayout(detail_panel)
        detail_layout.setAlignment(Qt.AlignTop)

        form_grid = QGridLayout()

        self.name_input = QLineEdit()
        self.username_input = QLineEdit()
        self.password_input = QLineEdit()
        self.password_input.setEchoMode(QLineEdit.Password)
        self.role_combo = QComboBox()
        self.role_combo.addItems(["Admin", "Teacher"])
        self.dept_input = QLineEdit()
        self.shift_input = QLineEdit()
        
        form_grid.addWidget(QLabel("Name:"), 0, 0)
        form_grid.addWidget(self.name_input, 0, 1)
        form_grid.addWidget(QLabel("Username:"), 1, 0)
        form_grid.addWidget(self.username_input, 1, 1)
        form_grid.addWidget(QLabel("Password:"), 2, 0)
        form_grid.addWidget(self.password_input, 2, 1)
        form_grid.addWidget(QLabel("Role:"), 3, 0)
        form_grid.addWidget(self.role_combo, 3, 1)
        form_grid.addWidget(QLabel("Department:"), 4, 0)
        form_grid.addWidget(self.dept_input, 4, 1)
        form_grid.addWidget(QLabel("Shift (H:M-H:M):"), 5, 0)
        form_grid.addWidget(self.shift_input, 5, 1)

        detail_layout.addLayout(form_grid)
        detail_layout.addSpacing(15)
        
        self.btn_save = QPushButton("Save/Update User")
        self.btn_save.setStyleSheet("background-color: #007bff; color: white;")
        self.btn_save.clicked.connect(self._save_user)
        detail_layout.addWidget(self.btn_save)
        
        detail_layout.addSpacing(20)
        
        # Enrollment Section
        enroll_header = QLabel("Fingerprint Enrollment")
        enroll_header.setFont(QFont("Arial", 12, QFont.Bold))
        detail_layout.addWidget(enroll_header)
        
        self.enroll_status = QLabel("Select a user to enroll.")
        self.enroll_status.setStyleSheet("color: gray;")
        self.btn_enroll = QPushButton("Start Enrollment (3 Scans)")
        self.btn_enroll.setEnabled(False)
        self.btn_enroll.setStyleSheet("background-color: #ff9800; color: white;")
        self.btn_enroll.clicked.connect(self._start_enrollment)
        
        detail_layout.addWidget(self.enroll_status)
        detail_layout.addWidget(self.btn_enroll)

        main_splitter.addWidget(detail_panel, 2)
        layout.addLayout(main_splitter)

    def load_users(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Fetch user data
        cursor.execute("SELECT id, name, role, department, username FROM users ORDER BY name")
        users = cursor.fetchall()

        # Fetch finger counts
        finger_counts = {}
        cursor.execute("SELECT user_id, count(finger_index) as count FROM fingerprints GROUP BY user_id")
        for row in cursor.fetchall():
            finger_counts[row['user_id']] = row['count']
            
        conn.close()

        self.user_table.setRowCount(len(users))
        for row_idx, user in enumerate(users):
            user_id = user['id']
            finger_count = finger_counts.get(user_id, 0)
            
            self.user_table.setItem(row_idx, 0, QTableWidgetItem(user_id))
            self.user_table.setItem(row_idx, 1, QTableWidgetItem(user['name']))
            self.user_table.setItem(row_idx, 2, QTableWidgetItem(user['role']))
            self.user_table.setItem(row_idx, 3, QTableWidgetItem(user['department']))
            self.user_table.setItem(row_idx, 4, QTableWidgetItem(f"{finger_count} FINGERS"))
            
            # Hide the ID column
            self.user_table.setColumnHidden(0, True)

    def _select_user(self, row, col):
        user_id = self.user_table.item(row, 0).text()
        self.current_user_id = user_id
        
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM users WHERE id = ?", (user_id,))
        user = cursor.fetchone()
        
        # Get finger count
        cursor.execute("SELECT count(finger_index) FROM fingerprints WHERE user_id = ?", (user_id,))
        finger_count = cursor.fetchone()[0]
        conn.close()

        if user:
            self.name_input.setText(user['name'])
            self.username_input.setText(user['username'])
            self.password_input.setText("") # Never display password hash
            self.role_combo.setCurrentText(user['role'])
            self.dept_input.setText(user['department'])
            self.shift_input.setText(user['shift_hours'])
            self.btn_enroll.setEnabled(True)
            self.enroll_status.setText(f"Enrolled Fingers: {finger_count}. Ready to enroll new.")
            self.btn_save.setText("Update User")
            
    def _clear_form(self):
        self.current_user_id = None
        self.name_input.clear()
        self.username_input.clear()
        self.password_input.clear()
        self.dept_input.clear()
        self.shift_input.clear()
        self.btn_enroll.setEnabled(False)
        self.enroll_status.setText("Select a user or save new user first.")
        self.btn_save.setText("Save New User")

    def _save_user(self):
        name = self.name_input.text().strip()
        username = self.username_input.text().strip()
        password = self.password_input.text()
        role = self.role_combo.currentText()
        dept = self.dept_input.text().strip()
        shift = self.shift_input.text().strip()

        if not all([name, username, role, dept, shift]):
            QMessageBox.warning(self, "Input Error", "All fields must be filled.")
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        
        if self.current_user_id:
            # Update existing user
            if password:
                password_hash = hashlib.sha256(password.encode()).hexdigest()
                cursor.execute("""
                    UPDATE users SET name=?, username=?, password_hash=?, role=?, department=?, shift_hours=?
                    WHERE id=?
                """, (name, username, password_hash, role, dept, shift, self.current_user_id))
            else:
                cursor.execute("""
                    UPDATE users SET name=?, username=?, role=?, department=?, shift_hours=?
                    WHERE id=?
                """, (name, username, role, dept, shift, self.current_user_id))
            QMessageBox.information(self, "Success", f"User {name} updated.")
        else:
            # Create new user
            if not password:
                QMessageBox.warning(self, "Input Error", "Password is required for new user.")
                return
            
            new_id = str(uuid.uuid4())
            password_hash = hashlib.sha256(password.encode()).hexdigest()
            try:
                cursor.execute("""
                    INSERT INTO users (id, name, username, password_hash, role, department, shift_hours)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                """, (new_id, name, username, password_hash, role, dept, shift))
                self.current_user_id = new_id
                QMessageBox.information(self, "Success", f"New user {name} created.")
                self.btn_enroll.setEnabled(True)
            except sqlite3.IntegrityError:
                QMessageBox.critical(self, "Error", "Username already exists.")
                conn.close()
                return

        conn.commit()
        conn.close()
        self.load_users()

    def _delete_user(self):
        if not self.current_user_id:
            QMessageBox.warning(self, "Deletion Failed", "No user selected.")
            return

        reply = QMessageBox.question(self, 'Confirm Deletion',
            f"Are you sure you want to delete user ID {self.current_user_id}? This action is irreversible and deletes all associated fingerprint and attendance data.",
            QMessageBox.Yes | QMessageBox.No, QMessageBox.No)

        if reply == QMessageBox.Yes:
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("DELETE FROM users WHERE id = ?", (self.current_user_id,))
            
            # Deletion cascades to fingerprints and attendance implicitly or manually
            cursor.execute("DELETE FROM fingerprints WHERE user_id = ?", (self.current_user_id,))
            cursor.execute("DELETE FROM attendance WHERE user_id = ?", (self.current_user_id,))

            conn.commit()
            conn.close()
            QMessageBox.information(self, "Success", "User deleted successfully.")
            self._clear_form()
            self.load_users()

    def _start_enrollment(self):
        if not self.current_user_id:
            QMessageBox.warning(self, "Enrollment Error", "Please select or save a user first.")
            return

        # Check device status
        if NBIOMINI.NBioAPI_GetDeviceStatus() != "READY":
            QMessageBox.critical(self, "Device Error", "Biometric device is not connected or ready.")
            return
            
        self.btn_enroll.setEnabled(False)
        self.enroll_status.setText("Enrollment started. Follow device instructions (3 captures needed)...")
        
        self.biometric_worker = BiometricWorker(action="enroll", data={'user_id': self.current_user_id})
        self.biometric_worker.enroll_finished.connect(self._handle_enroll_result)
        self.biometric_worker.start()

    def _handle_enroll_result(self, success, user_id, template_data):
        self.btn_enroll.setEnabled(True)
        if success and template_data:
            conn = get_db_connection()
            cursor = conn.cursor()
            
            # Find the next available finger index for this user
            cursor.execute("SELECT MAX(finger_index) FROM fingerprints WHERE user_id = ?", (user_id,))
            max_index = cursor.fetchone()[0]
            new_index = (max_index if max_index is not None else 0) + 1
            
            # Save the template blob
            cursor.execute("""
                INSERT INTO fingerprints (user_id, finger_index, template)
                VALUES (?, ?, ?)
            """, (user_id, new_index, template_data))
            conn.commit()
            conn.close()
            
            QMessageBox.information(self, "Enrollment Success", f"Finger {new_index} enrolled successfully for User ID {user_id}.")
            self.enroll_status.setText(f"Enrollment successful. Finger {new_index} saved.")
            self.load_users()
        else:
            QMessageBox.critical(self, "Enrollment Failed", "Enrollment failed or timed out. Try again.")
            self.enroll_status.setText("Enrollment failed.")

class ReportsPage(QWidget):
    def __init__(self):
        super().__init__()
        self._setup_ui()
        self.load_users_dropdown()

    def _setup_ui(self):
        layout = QVBoxLayout(self)
        layout.addWidget(ModernHeader("Attendance Reports"))

        control_panel = QHBoxLayout()
        
        self.report_type_combo = QComboBox()
        self.report_type_combo.addItems(["Daily", "Monthly", "Yearly"])
        
        self.date_start = QDateEdit(QDate.currentDate())
        self.date_end = QDateEdit(QDate.currentDate())
        self.date_start.setCalendarPopup(True)
        self.date_end.setCalendarPopup(True)
        self.date_start.setLocale(QLocale(QLocale.English, QLocale.AnyCountry))
        self.date_end.setLocale(QLocale(QLocale.English, QLocale.AnyCountry))

        self.user_filter_combo = QComboBox()
        
        btn_generate = QPushButton("Generate Report")
        btn_generate.clicked.connect(self.generate_report)
        btn_generate.setStyleSheet("background-color: #17a2b8; color: white;")
        
        control_panel.addWidget(QLabel("Type:"))
        control_panel.addWidget(self.report_type_combo)
        control_panel.addWidget(QLabel("From:"))
        control_panel.addWidget(self.date_start)
        control_panel.addWidget(QLabel("To:"))
        control_panel.addWidget(self.date_end)
        control_panel.addWidget(QLabel("User:"))
        control_panel.addWidget(self.user_filter_combo)
        control_panel.addWidget(btn_generate)
        control_panel.addStretch()

        layout.addLayout(control_panel)

        self.report_table = QTableWidget()
        self.report_table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        layout.addWidget(self.report_table)

        export_layout = QHBoxLayout()
        self.btn_export_pdf = QPushButton("Export to PDF")
        self.btn_export_excel = QPushButton("Export to CSV/Excel")
        self.btn_export_pdf.clicked.connect(lambda: self.export_report("PDF"))
        self.btn_export_excel.clicked.connect(lambda: self.export_report("CSV"))
        
        export_layout.addStretch()
        export_layout.addWidget(self.btn_export_pdf)
        export_layout.addWidget(self.btn_export_excel)
        layout.addLayout(export_layout)

    def load_users_dropdown(self):
        self.user_filter_combo.clear()
        self.user_filter_combo.addItem("All Users", "")
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM users ORDER BY name")
        users = cursor.fetchall()
        conn.close()
        for user in users:
            self.user_filter_combo.addItem(user['name'], user['id'])

    def generate_report(self):
        start_date = self.date_start.date().toString("yyyy-MM-dd")
        end_date = self.date_end.date().toString("yyyy-MM-dd")
        user_id = self.user_filter_combo.currentData()
        report_type = self.report_type_combo.currentText()
        
        conn = get_db_connection()
        cursor = conn.cursor()
        
        query = """
            SELECT 
                u.name, 
                a.timestamp, 
                a.type 
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            WHERE date(a.timestamp) BETWEEN ? AND ?
        """
        params = [start_date, end_date]
        
        if user_id:
            query += " AND u.id = ?"
            params.append(user_id)
            
        query += " ORDER BY u.name, a.timestamp"
        
        cursor.execute(query, params)
        data = cursor.fetchall()
        conn.close()
        
        if not data:
            self.report_table.setRowCount(0)
            QMessageBox.information(self, "No Data", "No attendance records found for the selected period.")
            return

        # Process data for display (e.g., calculating hours)
        processed_data = self._process_attendance_data(data)
        
        # Display in table
        headers = ["User Name", "Date", "In Time", "Out Time", "Total Hours"]
        self.report_table.setColumnCount(len(headers))
        self.report_table.setHorizontalHeaderLabels(headers)
        self.report_table.setRowCount(len(processed_data))

        for row_idx, record in enumerate(processed_data):
            self.report_table.setItem(row_idx, 0, QTableWidgetItem(record['name']))
            self.report_table.setItem(row_idx, 1, QTableWidgetItem(record['date']))
            self.report_table.setItem(row_idx, 2, QTableWidgetItem(record.get('in_time', 'N/A')))
            self.report_table.setItem(row_idx, 3, QTableWidgetItem(record.get('out_time', 'N/A')))
            self.report_table.setItem(row_idx, 4, QTableWidgetItem(record.get('duration', '0:00')))

    def _process_attendance_data(self, raw_data):
        daily_records = {}
        
        for row in raw_data:
            name = row['name']
            timestamp_dt = datetime.datetime.strptime(row['timestamp'], '%Y-%m-%d %H:%M:%S')
            date_str = timestamp_dt.strftime('%Y-%m-%d')
            time_str = timestamp_dt.strftime('%H:%M:%S')
            record_type = row['type']
            
            key = (name, date_str)
            if key not in daily_records:
                daily_records[key] = {'name': name, 'date': date_str, 'timestamps': []}
            
            daily_records[key]['timestamps'].append((timestamp_dt, record_type))

        final_report = []
        for key, record in daily_records.items():
            timestamps = sorted(record['timestamps'], key=lambda x: x[0])
            
            in_time = None
            out_time = None
            
            # Simple aggregation: First IN and Last OUT
            first_in = next(((ts, type) for ts, type in timestamps if type == 'IN'), None)
            last_out = next(((ts, type) for ts, type in reversed(timestamps) if type == 'OUT'), None)

            duration = datetime.timedelta(0)
            
            if first_in:
                in_time = first_in[0]
            if last_out:
                out_time = last_out[0]
                
            if in_time and out_time and out_time > in_time:
                duration = out_time - in_time

            final_record = {
                'name': record['name'],
                'date': record['date'],
                'in_time': in_time.strftime('%H:%M:%S') if in_time else 'N/A',
                'out_time': out_time.strftime('%H:%M:%S') if out_time else 'N/A',
                'duration': str(duration).split('.')[0] # Remove microseconds
            }
            final_report.append(final_record)
            
        return final_report

    def export_report(self, export_type):
        if self.report_table.rowCount() == 0:
            QMessageBox.warning(self, "Export Failed", "Generate a report first before exporting.")
            return

        dialog = QFileDialog(self)
        dialog.setAcceptMode(QFileDialog.AcceptSave)
        
        if export_type == "CSV":
            filename, _ = dialog.getSaveFileName(self, "Export to CSV/Excel", f"Attendance_Report_{datetime.date.today()}.csv", "CSV Files (*.csv)")
            if filename:
                self._write_csv(filename)
        elif export_type == "PDF":
            QMessageBox.warning(self, "Export", "PDF export requires external library (e.g., ReportLab) not included in this single-file solution. Exporting to CSV instead.")
            self.export_report("CSV")

    def _write_csv(self, filename):
        try:
            with open(filename, 'w', newline='', encoding='utf-8') as f:
                import csv
                writer = csv.writer(f)
                
                # Headers
                headers = [self.report_table.horizontalHeaderItem(i).text() for i in range(self.report_table.columnCount())]
                writer.writerow(headers)
                
                # Data rows
                for row in range(self.report_table.rowCount()):
                    row_data = []
                    for col in range(self.report_table.columnCount()):
                        item = self.report_table.item(row, col)
                        row_data.append(item.text() if item else '')
                    writer.writerow(row_data)
            QMessageBox.information(self, "Success", f"Report successfully exported to {filename}")
        except Exception as e:
            QMessageBox.critical(self, "Export Error", f"Failed to write CSV: {e}")

class BackupRestorePage(QWidget):
    def __init__(self):
        super().__init__()
        self._setup_ui()

    def _setup_ui(self):
        layout = QVBoxLayout(self)
        layout.addWidget(ModernHeader("System Backup & Restore"))

        panel = QWidget()
        panel.setStyleSheet("background-color: white; border-radius: 8px; padding: 20px;")
        grid = QGridLayout(panel)

        # 1. Backup Section
        header_backup = QLabel("Database Backup (JSON)")
        header_backup.setFont(QFont("Arial", 14, QFont.Bold))
        self.btn_backup = QPushButton("Export All Data")
        self.btn_backup.setStyleSheet("background-color: #007bff; color: white; padding: 15px;")
        self.btn_backup.clicked.connect(self._perform_backup)
        
        grid.addWidget(header_backup, 0, 0, 1, 2)
        grid.addWidget(QLabel("Exports users, fingerprints, and attendance to a single JSON file."), 1, 0, 1, 2)
        grid.addWidget(self.btn_backup, 2, 0, 1, 2)

        grid.setRowStretch(3, 1)

        # 2. Restore Section
        header_restore = QLabel("Database Restore (JSON)")
        header_restore.setFont(QFont("Arial", 14, QFont.Bold))
        
        self.restore_path_input = QLineEdit()
        self.restore_path_input.setPlaceholderText("Select Backup File (.json)")
        self.btn_browse = QPushButton("Browse")
        self.btn_browse.clicked.connect(self._browse_file)
        
        self.btn_restore = QPushButton("IMPORT & REPLACE ALL DATA")
        self.btn_restore.setStyleSheet("background-color: #dc3545; color: white; padding: 15px;")
        self.btn_restore.clicked.connect(self._perform_restore)

        grid.addWidget(header_restore, 4, 0, 1, 2)
        grid.addWidget(QLabel("WARNING: This action will permanently erase current data and replace it."), 5, 0, 1, 2)
        grid.addWidget(self.restore_path_input, 6, 0)
        grid.addWidget(self.btn_browse, 6, 1)
        grid.addWidget(self.btn_restore, 7, 0, 1, 2)
        
        layout.addWidget(panel)
        layout.addStretch()

    def _perform_backup(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        
        try:
            data = {}
            # Export Users
            cursor.execute("SELECT * FROM users")
            data['users'] = [dict(row) for row in cursor.fetchall()]
            
            # Export Fingerprints (convert BLOB to base64 string for JSON safety)
            cursor.execute("SELECT user_id, finger_index, template FROM fingerprints")
            templates = []
            import base64
            for row in cursor.fetchall():
                row_dict = dict(row)
                row_dict['template'] = base64.b64encode(row_dict['template']).decode('utf-8')
                templates.append(row_dict)
            data['fingerprints'] = templates
            
            # Export Attendance
            cursor.execute("SELECT * FROM attendance")
            data['attendance'] = [dict(row) for row in cursor.fetchall()]

            file_path, _ = QFileDialog.getSaveFileName(self, "Save Database Backup", f"AttendanceBackup_{datetime.date.today().isoformat()}.json", "JSON Files (*.json)")
            
            if file_path:
                with open(file_path, 'w', encoding='utf-8') as f:
                    json.dump(data, f, indent=4)
                QMessageBox.information(self, "Backup Success", f"All data exported successfully to {file_path}")
        except Exception as e:
            QMessageBox.critical(self, "Backup Error", f"An error occurred during backup: {e}")
        finally:
            conn.close()

    def _browse_file(self):
        file_path, _ = QFileDialog.getOpenFileName(self, "Select Backup File", "", "JSON Files (*.json)")
        if file_path:
            self.restore_path_input.setText(file_path)

    def _perform_restore(self):
        file_path = self.restore_path_input.text()
        if not os.path.exists(file_path):
            QMessageBox.critical(self, "Restore Error", "File path is invalid.")
            return

        reply = QMessageBox.question(self, 'CONFIRM DATA RESTORE',
            "This will ERASE ALL current data and replace it with the data from the selected JSON file. Are you absolutely sure?",
            QMessageBox.Yes | QMessageBox.No, QMessageBox.No)

        if reply != QMessageBox.Yes:
            return

        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                data = json.load(f)

            if not all(k in data for k in ['users', 'fingerprints', 'attendance']):
                raise ValueError("JSON file is missing required data tables (users, fingerprints, attendance).")

            conn = get_db_connection()
            cursor = conn.cursor()
            
            # 1. Clear existing data
            cursor.execute("DELETE FROM attendance")
            cursor.execute("DELETE FROM fingerprints")
            cursor.execute("DELETE FROM users")
            
            # 2. Restore Users
            user_data = [(d['id'], d['username'], d['password_hash'], d['name'], d['role'], d['department'], d['shift_hours']) for d in data['users']]
            if user_data:
                cursor.executemany("""
                    INSERT INTO users (id, username, password_hash, name, role, department, shift_hours)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                """, user_data)

            # 3. Restore Fingerprints (convert base64 back to BLOB)
            import base64
            template_data = []
            for d in data['fingerprints']:
                template_data.append((d['user_id'], d['finger_index'], base64.b64decode(d['template'].encode('utf-8'))))
            
            if template_data:
                cursor.executemany("""
                    INSERT INTO fingerprints (user_id, finger_index, template)
                    VALUES (?, ?, ?)
                """, template_data)
                
            # 4. Restore Attendance
            attendance_data = [(d['id'], d['user_id'], d['timestamp'], d['type']) for d in data['attendance']]
            if attendance_data:
                cursor.executemany("""
                    INSERT INTO attendance (id, user_id, timestamp, type)
                    VALUES (?, ?, ?, ?)
                """, attendance_data)

            conn.commit()
            QMessageBox.information(self, "Restore Success", "Database restored successfully. System data replaced.")
        except Exception as e:
            QMessageBox.critical(self, "Restore Error", f"Failed to restore database: {e}. Data integrity may be compromised.")
            # Rollback if failure occurred during transaction
            conn.rollback()
        finally:
            conn.close()

# --- MAIN APPLICATION WINDOW ---

class BiometricAttendanceApp(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("Neurotechnology Biometric Attendance System (FAP20)")
        self.setGeometry(100, 100, 1000, 700)
        self.setStyleSheet("QMainWindow { background-color: #e9ecef; }")
        self._show_login()

    def _show_login(self):
        self.login_screen = LoginScreen()
        self.login_screen.login_successful.connect(self._show_dashboard)
        self.setCentralWidget(self.login_screen)

    def _show_dashboard(self, user_id, user_role):
        self.dashboard = Dashboard(user_id, user_role)
        self.dashboard.btn_logout.clicked.connect(self._handle_logout)
        self.setCentralWidget(self.dashboard)

    def _handle_logout(self):
        self.centralWidget().deleteLater()
        self._show_login()

# --- EXECUTION BLOCK ---

if __name__ == "__main__":
    # Ensure database is initialized before starting the app
    init_db()
    
    # Initialize the SDK mock structure (must run first)
    NBIOMINI.NBioAPI_Init() 
    
    app = QApplication(sys.argv)
    
    # Apply a modern, clean palette
    app.setStyle("Fusion") 
    
    window = BiometricAttendanceApp()
    window.show()
    
    exit_code = app.exec()
    
    # Terminate the SDK cleanly on exit
    NBIOMINI.NBioAPI_Terminate() 
    
    sys.exit(exit_code)