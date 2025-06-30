import tkinter as tk
from tkinter import ttk, messagebox, filedialog, PhotoImage
import sqlite3
import os
from datetime import datetime
from PIL import Image, ImageTk, ImageDraw, ImageFont # Required for image manipulation
import qrcode # Required for QR code generation

# --- Database Setup ---
DB_NAME = "school_management.db"

def initialize_db():
    conn = sqlite3.connect(DB_NAME)
    cursor = conn.cursor()

    # People
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            cnic TEXT UNIQUE,
            father_name TEXT,
            contact TEXT,
            class_id INTEGER,
            address TEXT,
            photo_path TEXT,
            admission_date DATE DEFAULT CURRENT_DATE,
            FOREIGN KEY (class_id) REFERENCES classes(id)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS teachers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            cnic TEXT UNIQUE,
            contact TEXT,
            address TEXT,
            joining_date DATE DEFAULT CURRENT_DATE,
            subjects TEXT, -- Comma-separated subject IDs
            role TEXT, -- e.g., 'Teacher', 'Admin', 'Accountant'
            photo_path TEXT
        )
    ''')

    # Academics
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS classes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS sections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            class_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            FOREIGN KEY (class_id) REFERENCES classes(id),
            UNIQUE(class_id, name)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS subjects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS class_subject_teacher (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            class_id INTEGER NOT NULL,
            section_id INTEGER NOT NULL,
            subject_id INTEGER NOT NULL,
            teacher_id INTEGER NOT NULL,
            FOREIGN KEY (class_id) REFERENCES classes(id),
            FOREIGN KEY (section_id) REFERENCES sections(id),
            FOREIGN KEY (subject_id) REFERENCES subjects(id),
            FOREIGN KEY (teacher_id) REFERENCES teachers(id)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            attendance_date DATE NOT NULL,
            status TEXT NOT NULL, -- 'Present', 'Absent', 'Leave', 'Late'
            FOREIGN KEY (student_id) REFERENCES students(id),
            UNIQUE(student_id, attendance_date)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS exams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL, -- e.g., 'Midterm', 'Final'
            date DATE
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS marks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            exam_id INTEGER NOT NULL,
            subject_id INTEGER NOT NULL,
            marks_obtained REAL,
            total_marks REAL,
            FOREIGN KEY (student_id) REFERENCES students(id),
            FOREIGN KEY (exam_id) REFERENCES exams(id),
            FOREIGN KEY (subject_id) REFERENCES subjects(id),
            UNIQUE(student_id, exam_id, subject_id)
        )
    ''')

    # Financial
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS fee_structures (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            class_id INTEGER,
            category TEXT, -- e.g., 'General', 'Deserving'
            fee_type TEXT NOT NULL, -- e.g., 'Tuition Fee', 'Admission Fee'
            amount REAL NOT NULL,
            FOREIGN KEY (class_id) REFERENCES classes(id)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS fee_collections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            fee_type TEXT NOT NULL,
            amount_paid REAL NOT NULL,
            payment_date DATE DEFAULT CURRENT_DATE,
            receipt_number TEXT UNIQUE,
            fine_paid REAL DEFAULT 0,
            discount_applied REAL DEFAULT 0,
            collected_by TEXT, -- Teacher/Accountant name
            FOREIGN KEY (student_id) REFERENCES students(id)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS salary_structures (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER NOT NULL,
            base_salary REAL NOT NULL,
            allowances TEXT, -- JSON string for allowances like '{"HRA": 1000, "Conveyance": 500}'
            deductions TEXT, -- JSON string for deductions like '{"Provident Fund": 200}'
            FOREIGN KEY (teacher_id) REFERENCES teachers(id)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS payslips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER NOT NULL,
            month TEXT NOT NULL,
            year TEXT NOT NULL,
            gross_pay REAL NOT NULL,
            net_pay REAL NOT NULL,
            generated_date DATE DEFAULT CURRENT_DATE,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id)
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_date DATE DEFAULT CURRENT_DATE,
            description TEXT NOT NULL,
            type TEXT NOT NULL, -- 'Income' or 'Expense'
            category TEXT,
            amount REAL NOT NULL
        )
    ''')

    # Utilities
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )
    ''')
    # Insert default settings if they don't exist
    cursor.execute("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)", ('school_name', 'My School'))
    cursor.execute("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)", ('logo_path', ''))
    cursor.execute("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)", ('session_start', datetime.now().strftime('%Y-%m-%d')))
    cursor.execute("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)", ('session_end', (datetime.now().replace(month=datetime.now().month + 6)).strftime('%Y-%m-%d'))) # Example: 6-month session

    conn.commit()
    conn.close()

# --- Utility Functions ---
def get_db_connection():
    return sqlite3.connect(DB_NAME)

def get_setting(key):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT value FROM settings WHERE key = ?", (key,))
    result = cursor.fetchone()
    conn.close()
    return result[0] if result else None

def update_setting(key, value):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE settings SET value = ? WHERE key = ?", (value, key))
    conn.commit()
    conn.close()

def get_logo_image():
    logo_path = get_setting('logo_path')
    if logo_path and os.path.exists(logo_path):
        try:
            img = Image.open(logo_path)
            img.thumbnail((100, 100)) # Resize for display
            return ImageTk.PhotoImage(img)
        except Exception as e:
            print(f"Error loading logo: {e}")
            return None
    return None

def get_qr_code_image(data):
    qr = qrcode.QRCode(version=1, error_correction=qrcode.constants.ERROR_CORRECT_L, box_size=3, border=2)
    qr.add_data(data)
    qr.make(fit=True)
    img = qr.make_image(fill_color="black", back_color="white")
    img.thumbnail((80, 80))
    return ImageTk.PhotoImage(img)

def get_student_photo(student_id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT photo_path FROM students WHERE id = ?", (student_id,))
    result = cursor.fetchone()
    conn.close()
    if result and result[0] and os.path.exists(result[0]):
        try:
            img = Image.open(result[0])
            img.thumbnail((75, 75))
            return ImageTk.PhotoImage(img)
        except Exception as e:
            print(f"Error loading student photo: {e}")
            return None
    return None

def get_teacher_photo(teacher_id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT photo_path FROM teachers WHERE id = ?", (teacher_id,))
    result = cursor.fetchone()
    conn.close()
    if result and result[0] and os.path.exists(result[0]):
        try:
            img = Image.open(result[0])
            img.thumbnail((75, 75))
            return ImageTk.PhotoImage(img)
        except Exception as e:
            print(f"Error loading teacher photo: {e}")
            return None
    return None

def select_photo(callback):
    filepath = filedialog.askopenfilename(title="Select Photo", filetypes=[("Image Files", "*.png *.jpg *.jpeg")])
    if filepath:
        callback(filepath)

def create_receipt_number():
    now = datetime.now()
    return f"REC-{now.strftime('%Y%m%d%H%M%S')}"

def format_currency(amount):
    return f"{amount:,.2f}" # Basic formatting

def calculate_grade(percentage):
    if percentage >= 90: return "A+"
    if percentage >= 80: return "A"
    if percentage >= 70: return "B"
    if percentage >= 60: return "C"
    if percentage >= 50: return "D"
    return "F"

# --- Backup and Restore ---
def backup_database():
    source_file = DB_NAME
    backup_path = filedialog.asksaveasfilename(defaultextension=".sqlite", filetypes=[("SQLite Database", "*.sqlite"), ("JSON Backup", "*.json")], title="Backup Database")
    if not backup_path:
        return

    if backup_path.endswith(".json"):
        try:
            conn = get_db_connection()
            cursor = conn.cursor()
            # Export tables to JSON (simplified)
            import json
            backup_data = {}
            for table in ["students", "teachers", "classes", "sections", "subjects", "fee_structures", "fee_collections", "transactions", "settings", "attendance", "exams", "marks", "salary_structures", "payslips", "class_subject_teacher"]:
                try:
                    cursor.execute(f"SELECT * FROM {table}")
                    columns = [description[0] for description in cursor.description]
                    data = [dict(zip(columns, row)) for row in cursor.fetchall()]
                    backup_data[table] = data
                except sqlite3.OperationalError: # Table might not exist in some setups
                    pass
            with open(backup_path, 'w', encoding='utf-8') as f:
                json.dump(backup_data, f, indent=4, ensure_ascii=False)
            messagebox.showinfo("Backup Success", f"Database backed up successfully to {backup_path}")
        except Exception as e:
            messagebox.showerror("Backup Error", f"Failed to backup database: {e}")
        finally:
            if conn:
                conn.close()
    elif backup_path.endswith(".sqlite"):
        try:
            shutil.copyfile(source_file, backup_path)
            messagebox.showinfo("Backup Success", f"Database backed up successfully to {backup_path}")
        except Exception as e:
            messagebox.showerror("Backup Error", f"Failed to backup database: {e}")
    else:
        messagebox.showerror("Backup Error", "Invalid backup file format selected.")


def restore_database():
    restore_path = filedialog.askopenfilename(title="Restore Database", filetypes=[("SQLite Database", "*.sqlite"), ("JSON Backup", "*.json")])
    if not restore_path:
        return

    if not messagebox.askyesno("Confirm Restore", "Restoring will overwrite the current database. Are you sure?"):
        return

    try:
        if restore_path.endswith(".json"):
            import json
            with open(restore_path, 'r', encoding='utf-8') as f:
                backup_data = json.load(f)

            conn = get_db_connection()
            cursor = conn.cursor()

            # Clear existing data (carefully!)
            tables_to_clear = ["students", "teachers", "classes", "sections", "subjects", "fee_structures", "fee_collections", "transactions", "settings", "attendance", "exams", "marks", "salary_structures", "payslips", "class_subject_teacher"]
            for table in tables_to_clear:
                try:
                    cursor.execute(f"DELETE FROM {table}")
                except sqlite3.OperationalError:
                    pass # Table might not exist

            # Insert data from JSON
            for table_name, rows in backup_data.items():
                if not rows: continue
                columns = rows[0].keys()
                placeholders = ', '.join('?' * len(columns))
                sql = f"INSERT INTO {table_name} ({', '.join(columns)}) VALUES ({placeholders})"
                values = [[row.get(col) for col in columns] for row in rows]
                cursor.executemany(sql, values)

            conn.commit()
            conn.close()
            messagebox.showinfo("Restore Success", "Database restored successfully from JSON.")
        elif restore_path.endswith(".sqlite"):
            import shutil
            shutil.copyfile(restore_path, DB_NAME)
            messagebox.showinfo("Restore Success", "Database restored successfully from SQLite file.")
        else:
            messagebox.showerror("Restore Error", "Invalid backup file format selected.")

        # Re-initialize the app after restore
        messagebox.showinfo("Restart Required", "The application needs to restart for changes to take full effect.")
        root.quit()
        os.execv(__file__, sys.argv) # Restart the script

    except Exception as e:
        messagebox.showerror("Restore Error", f"Failed to restore database: {e}")

# --- UI Components ---

class Sidebar(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="#f0f0f0", width=200, relief=tk.RIDGE, borderwidth=2)
        self.controller = controller
        self.logo_label = None
        self.school_name_label = None
        self.create_widgets()

    def create_widgets(self):
        logo_path = get_setting('logo_path')
        school_name = get_setting('school_name')

        if logo_path and os.path.exists(logo_path):
            try:
                img = Image.open(logo_path)
                img.thumbnail((100, 100))
                self.logo_photo = ImageTk.PhotoImage(img)
                self.logo_label = tk.Label(self, image=self.logo_photo, bg="#f0f0f0")
                self.logo_label.pack(pady=10)
            except Exception as e:
                print(f"Error loading logo in sidebar: {e}")

        if school_name:
            self.school_name_label = tk.Label(self, text=school_name, font=("Arial", 14, "bold"), bg="#f0f0f0")
            self.school_name_label.pack(pady=5)

        self.nav_frame = tk.Frame(self, bg="#f0f0f0")
        self.nav_frame.pack(pady=20, fill=tk.X)

        nav_options = {
            "Dashboard": "show_dashboard",
            "Students": "show_students",
            "Teachers & Staff": "show_teachers",
            "Classes & Subjects": "show_academics",
            "Attendance": "show_attendance",
            "Exams & Marks": "show_exams",
            "Fees": "show_fees",
            "Salaries": "show_salaries",
            "Transactions": "show_transactions",
            "Reports": "show_reports",
            "Utilities": "show_utilities",
            "Settings": "show_settings",
        }

        self.buttons = {}
        for text, command in nav_options.items():
            btn = tk.Button(self.nav_frame, text=text, command=lambda c=command: self.controller.show_frame(c),
                            bg="#e0e0e0", relief=tk.FLAT, font=("Arial", 10), anchor="w", width=25, padx=10)
            btn.pack(pady=2, fill=tk.X)
            self.buttons[command] = btn

    def set_user_role(self, role):
        # Basic role-based access control simulation
        if role == 'Admin':
            self.buttons["show_settings"].pack(pady=2, fill=tk.X)
        elif role == 'Teacher':
            for key, btn in self.buttons.items():
                if key not in ["show_attendance", "show_dashboard", "show_reports", "show_students", "show_academics", "show_exams", "show_fees"]:
                    btn.pack_forget()
        elif role == 'Accountant':
            for key, btn in self.buttons.items():
                if key not in ["show_fees", "show_transactions", "show_reports", "show_dashboard"]:
                    btn.pack_forget()
        else: # Default or Guest
            pass # Show all for now, refine later if needed


class MainArea(tk.Frame):
    def __init__(self, master):
        super().__init__(master, bg="white")
        self.label = tk.Label(self, text="Welcome!", font=("Arial", 24, "bold"), bg="white")
        self.label.pack(pady=50)

# --- Controllers/Views ---

class DashboardView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.logo_photo = None # To hold PhotoImage object

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        self.logo_label = None
        self.school_name_label = None
        self.recent_activity_label = None
        self.upcoming_events_label = None

        self.create_widgets()
        self.load_data()

    def create_widgets(self):
        top_bar = tk.Frame(self.main_frame, bg="#e0e0e0", height=60)
        top_bar.pack(fill=tk.X, pady=10)
        tk.Label(top_bar, text="Dashboard", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        # School Info Section
        school_info_frame = tk.Frame(self.main_frame, bg="white")
        school_info_frame.pack(fill=tk.X, pady=10)

        logo_path = get_setting('logo_path')
        school_name = get_setting('school_name') or "School Name"

        if logo_path and os.path.exists(logo_path):
            try:
                img = Image.open(logo_path)
                img.thumbnail((80, 80))
                self.logo_photo = ImageTk.PhotoImage(img)
                self.logo_label = tk.Label(school_info_frame, image=self.logo_photo, bg="white")
                self.logo_label.pack(side=tk.LEFT, padx=20)
            except Exception as e:
                print(f"Error loading logo in Dashboard: {e}")
                self.logo_label = tk.Label(school_info_frame, text="[Logo]", font=("Arial", 10), bg="white")
                self.logo_label.pack(side=tk.LEFT, padx=20)
        else:
            self.logo_label = tk.Label(school_info_frame, text="[Logo]", font=("Arial", 10), bg="white")
            self.logo_label.pack(side=tk.LEFT, padx=20)

        self.school_name_label = tk.Label(school_info_frame, text=school_name, font=("Arial", 20, "bold"), bg="white")
        self.school_name_label.pack(side=tk.LEFT, padx=10)

        # Quick Stats Section
        stats_frame = tk.Frame(self.main_frame, bg="#f9f9f9", bd=2, relief=tk.GROOVE)
        stats_frame.pack(fill=tk.X, pady=20, padx=10)
        stats_frame.grid_columnconfigure((0, 1, 2, 3, 4), weight=1)

        self.stat_total_students = tk.Label(stats_frame, text="0", font=("Arial", 16, "bold"), bg="#f9f9f9")
        self.stat_total_students.grid(row=0, column=0, padx=5, pady=5)
        tk.Label(stats_frame, text="Total Students", font=("Arial", 10), bg="#f9f9f9").grid(row=1, column=0, padx=5, pady=0)

        self.stat_total_staff = tk.Label(stats_frame, text="0", font=("Arial", 16, "bold"), bg="#f9f9f9")
        self.stat_total_staff.grid(row=0, column=1, padx=5, pady=5)
        tk.Label(stats_frame, text="Total Staff", font=("Arial", 10), bg="#f9f9f9").grid(row=1, column=1, padx=5, pady=0)

        self.stat_total_fees = tk.Label(stats_frame, text="0.00", font=("Arial", 16, "bold"), bg="#f9f9f9")
        self.stat_total_fees.grid(row=0, column=2, padx=5, pady=5)
        tk.Label(stats_frame, text="Fees Collected", font=("Arial", 10), bg="#f9f9f9").grid(row=1, column=2, padx=5, pady=0)

        self.stat_pending_fees = tk.Label(stats_frame, text="0.00", font=("Arial", 16, "bold"), bg="#f9f9f9")
        self.stat_pending_fees.grid(row=0, column=3, padx=5, pady=5)
        tk.Label(stats_frame, text="Pending Fees", font=("Arial", 10), bg="#f9f9f9").grid(row=1, column=3, padx=5, pady=0)

        self.stat_today_attendance = tk.Label(stats_frame, text="N/A", font=("Arial", 16, "bold"), bg="#f9f9f9")
        self.stat_today_attendance.grid(row=0, column=4, padx=5, pady=5)
        tk.Label(stats_frame, text="Today's Attendance", font=("Arial", 10), bg="#f9f9f9").grid(row=1, column=4, padx=5, pady=0)

        # Activity Log & Upcoming Events
        log_events_frame = tk.Frame(self.main_frame, bg="white")
        log_events_frame.pack(fill=tk.BOTH, expand=True, pady=10, padx=10)

        log_frame = tk.LabelFrame(log_events_frame, text="Recent Activity Log", padx=10, pady=10, bg="white", font=("Arial", 12))
        log_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=5)
        self.recent_activity_label = tk.Label(log_frame, text="No recent activity.", justify=tk.LEFT, anchor="nw", bg="white", font=("Arial", 10))
        self.recent_activity_label.pack(fill=tk.BOTH, expand=True)

        events_frame = tk.LabelFrame(log_events_frame, text="Upcoming Events", padx=10, pady=10, bg="white", font=("Arial", 12))
        events_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=5)
        self.upcoming_events_label = tk.Label(events_frame, text="No upcoming events.", justify=tk.LEFT, anchor="nw", bg="white", font=("Arial", 10))
        self.upcoming_events_label.pack(fill=tk.BOTH, expand=True)

        # Quick Access Buttons
        quick_access_frame = tk.Frame(self.main_frame, bg="#f0f0f0")
        quick_access_frame.pack(fill=tk.X, pady=20)
        tk.Label(quick_access_frame, text="Quick Access", font=("Arial", 14, "bold"), bg="#f0f0f0").pack(pady=5)

        button_cmds = {
            "Add Student": lambda: self.controller.show_frame("show_students", action="add"),
            "Mark Attendance": lambda: self.controller.show_frame("show_attendance", action="mark"),
            "Collect Fees": lambda: self.controller.show_frame("show_fees", action="collect"),
            "View Reports": lambda: self.controller.show_frame("show_reports"),
        }
        quick_btn_row = tk.Frame(quick_access_frame, bg="#f0f0f0")
        quick_btn_row.pack()
        for text, cmd in button_cmds.items():
            tk.Button(quick_btn_row, text=text, command=cmd, width=15, height=2, font=("Arial", 10)).pack(side=tk.LEFT, padx=10, pady=10)


    def load_data(self):
        conn = get_db_connection()
        cursor = conn.cursor()

        # Fetch stats
        cursor.execute("SELECT COUNT(*) FROM students")
        total_students = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM teachers WHERE role != 'Admin'") # Exclude admin from staff count for clarity
        total_staff = cursor.fetchone()[0]

        today_str = datetime.now().strftime('%Y-%m-%d')
        cursor.execute("SELECT COUNT(DISTINCT student_id) FROM attendance WHERE attendance_date = ? AND status = 'Present'", (today_str,))
        today_present = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM students") # Total students for attendance metric
        total_students_for_attendance = cursor.fetchone()[0]
        if total_students_for_attendance > 0:
            attendance_percentage = (today_present / total_students_for_attendance) * 100
            today_attendance_str = f"{attendance_percentage:.1f}% ({today_present}/{total_students_for_attendance})"
        else:
            today_attendance_str = "N/A"


        # Calculate total fees collected and pending fees (simplified)
        cursor.execute("SELECT SUM(amount_paid + fine_paid - discount_applied) FROM fee_collections")
        total_fees_collected = cursor.fetchone()[0] or 0.0

        # Pending fees calculation is complex, requires comparing fee structures with collections.
        # This is a placeholder. A real system would need more logic.
        pending_fees = 0.0 # Placeholder

        self.stat_total_students.config(text=str(total_students))
        self.stat_total_staff.config(text=str(total_staff))
        self.stat_total_fees.config(text=format_currency(total_fees_collected))
        self.stat_pending_fees.config(text=format_currency(pending_fees))
        self.stat_today_attendance.config(text=today_attendance_str)

        # Load recent activity and upcoming events (placeholder data)
        self.recent_activity_label.config(text=" - New student admitted: Ali Khan\n - Fee paid: Fatima Zahra\n - Teacher profile updated: Mr. Ahmed")
        self.upcoming_events_label.config(text=" - Midterm Exams start next week\n - Parent-Teacher Meeting on Friday")

        conn.close()

class PeopleView(tk.Frame):
    def __init__(self, master, controller, person_type):
        super().__init__(master, bg="white")
        self.controller = controller
        self.person_type = person_type # "student" or "teacher"
        self.current_action = "view"
        self.selected_id = None
        self.photo_path = tk.StringVar()
        self.photo_label_img = None # To hold PhotoImage object

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        self.create_widgets()
        self.load_data()

    def show_add_form(self):
        self.current_action = "add"
        self.header_label.config(text=f"Add New {self.person_type.capitalize()}")
        self.search_entry.pack_forget()
        # FIX: Added a check to ensure filter_combo exists and is mapped before trying to pack_forget it.
        # This handles cases where filter_combo might only be created for students.
        if hasattr(self, 'filter_combo') and self.filter_combo.winfo_ismapped():
            self.filter_combo.pack_forget()
        self.add_button.pack_forget()
        self.refresh_button.pack_forget()
        self.tree.pack_forget()
        self.form_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        self.create_form_fields()
        self.clear_form()
        self.show_save_cancel_buttons("Add")

# In PeopleView class, create_widgets method:

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        self.header_label = tk.Label(header_frame, text=f"{self.person_type.capitalize()}s", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20)
        self.header_label.pack(side=tk.LEFT)

        # Search and Action Frame
        search_action_frame = tk.Frame(self.main_frame, bg="white")
        search_action_frame.pack(fill=tk.X, pady=10)

        self.search_entry = tk.Entry(search_action_frame, width=30, font=("Arial", 11))
        self.search_entry.pack(side=tk.LEFT, padx=5)
        self.search_entry.bind("<KeyRelease>", self.filter_data)

        # Only create and pack filter_combo if the person_type is 'student'
        if self.person_type == "student":
            self.filter_combo_var = tk.StringVar()
            # Ensure values are loaded before setting them
            self.update_filter_options() # Load classes first
            self.filter_combo = ttk.Combobox(search_action_frame, textvariable=self.filter_combo_var, values=self.get_class_filter_values(), state="readonly")
            self.filter_combo.set("All Classes")
            self.filter_combo.pack(side=tk.LEFT, padx=5)
            self.filter_combo.bind("<<ComboboxSelected>>", self.filter_data)

        self.add_button = tk.Button(search_action_frame, text="Add New", command=self.show_add_form, width=10, font=("Arial", 10, "bold"))
        self.add_button.pack(side=tk.RIGHT, padx=5)
        self.refresh_button = tk.Button(search_action_frame, text="Refresh", command=self.load_data, width=10)
        self.refresh_button.pack(side=tk.RIGHT, padx=5)


        # Treeview Frame
        tree_frame = tk.Frame(self.main_frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True, pady=10)

        self.tree_scroll = tk.Scrollbar(tree_frame)
        self.tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)

        columns = []
        if self.person_type == "student":
            columns = ('id', 'name', 'cnic', 'contact', 'class_name', 'actions')
            self.tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=self.tree_scroll.set, selectmode="browse")
            self.tree.heading('id', text='ID')
            self.tree.heading('name', text='Name')
            self.tree.heading('cnic', text='CNIC')
            self.tree.heading('contact', text='Contact')
            self.tree.heading('class_name', text='Class')
            self.tree.heading('actions', text='Actions')
            self.tree.column('id', width=50, anchor=tk.CENTER)
            self.tree.column('name', width=150)
            self.tree.column('cnic', width=120)
            self.tree.column('contact', width=100)
            self.tree.column('class_name', width=80)
            self.tree.column('actions', width=100, anchor=tk.CENTER)
        else: # teacher
            columns = ('id', 'name', 'cnic', 'contact', 'subjects', 'role', 'actions')
            self.tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=self.tree_scroll.set, selectmode="browse")
            self.tree.heading('id', text='ID')
            self.tree.heading('name', text='Name')
            self.tree.heading('cnic', text='CNIC')
            self.tree.heading('contact', text='Contact')
            self.tree.heading('subjects', text='Subjects')
            self.tree.heading('role', text='Role')
            self.tree.heading('actions', text='Actions')
            self.tree.column('id', width=50, anchor=tk.CENTER)
            self.tree.column('name', width=150)
            self.tree.column('cnic', width=120)
            self.tree.column('contact', width=100)
            self.tree.column('subjects', width=150)
            self.tree.column('role', width=80)
            self.tree.column('actions', width=100, anchor=tk.CENTER)

        self.tree_scroll.config(command=self.tree.yview)
        self.tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.tree.bind('<<TreeviewSelect>>', self.on_item_select)

        # Form Frame (initially hidden)
        self.form_frame = tk.Frame(self.main_frame, bg="white", bd=2, relief=tk.GROOVE)
        
    def get_class_filter_values(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT name FROM classes ORDER BY name")
        classes = [row[0] for row in cursor.fetchall()]
        conn.close()
        return ["All Classes"] + classes
    
    def update_filter_options(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT name FROM classes ORDER BY name")
        classes = [row[0] for row in cursor.fetchall()]
        conn.close()
        # FIX: Correctly reference 'filter_combo' to set its values.
        # It was previously trying to use 'classes' directly as the attribute name.
        if hasattr(self, 'filter_combo') and self.filter_combo: # Check if filter_combo exists
            self.filter_combo['values'] = ["All Classes"] + classes
            self.filter_combo.set("All Classes") # Reset to default

    def load_data(self):
        for item in self.tree.get_children():
            self.tree.delete(item)

        conn = get_db_connection()
        cursor = conn.cursor()

        query = ""
        params = ()
        if self.person_type == "student":
            query = """
                SELECT s.id, s.name, s.cnic, s.contact, c.name
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                ORDER BY s.name
            """
            cursor.execute(query)
        else: # teacher
            query = """
                SELECT t.id, t.name, t.cnic, t.contact, t.subjects, t.role
                FROM teachers t
                ORDER BY t.name
            """
            cursor.execute(query)

        rows = cursor.fetchall()
        conn.close()

        for row in rows:
            item_id = row[0]
            data = list(row[1:])

            # Process subjects for teachers
            if self.person_type == "teacher" and row[4]:
                subject_ids = [int(sid.strip()) for sid in row[4].split(',') if sid.strip().isdigit()]
                if subject_ids:
                    conn = get_db_connection()
                    sub_cursor = conn.cursor()
                    sub_cursor.execute("SELECT name FROM subjects WHERE id IN ({})".format(','.join('?' * len(subject_ids))), subject_ids)
                    subject_names = [sub[0] for sub in sub_cursor.fetchall()]
                    conn.close()
                    data[3] = ", ".join(subject_names) # Replace subject IDs with names
                else:
                    data[3] = "None" # No subjects assigned

            # Add action buttons
            btn_frame = tk.Frame(self.tree, bg="white")
            btn_edit = tk.Button(btn_frame, text="Edit", width=5, command=lambda i=item_id: self.show_edit_form(i), font=("Arial", 8))
            btn_edit.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=5, command=lambda i=item_id: self.delete_item(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            btn_view = tk.Button(btn_frame, text="View", width=5, command=lambda i=item_id: self.show_detail_view(i), font=("Arial", 8))
            btn_view.pack(side=tk.LEFT, padx=2)

            self.tree.insert('', tk.END, values=data + [btn_frame])
            self.tree.custom_widgets = getattr(self.tree, 'custom_widgets', {})
            self.tree.custom_widgets[item_id] = btn_frame # Keep track for potential cleanup


    def filter_data(self, event=None):
        search_term = self.search_entry.get().lower()
        filter_value = self.filter_combo_var.get() if self.person_type == "student" else None

        for item in self.tree.get_children():
            self.tree.delete(item)

        conn = get_db_connection()
        cursor = conn.cursor()

        query = ""
        params = ()
        base_query = ""

        if self.person_type == "student":
            base_query = """
                SELECT s.id, s.name, s.cnic, s.contact, c.name
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
            """
            if filter_value and filter_value != "All Classes":
                cursor.execute("SELECT id FROM classes WHERE name = ?", (filter_value,))
                class_id = cursor.fetchone()
                if class_id:
                    query = base_query + " WHERE s.class_id = ? AND lower(s.name) LIKE ? ORDER BY s.name"
                    params = (class_id[0], f"%{search_term}%")
                else: # Class not found, show nothing matching filter
                    query = base_query + " WHERE 1 = 0" # Impossible condition
            else:
                query = base_query + " WHERE lower(s.name) LIKE ? ORDER BY s.name"
                params = (f"%{search_term}%",)

        else: # teacher
            base_query = """
                SELECT t.id, t.name, t.cnic, t.contact, t.subjects, t.role
                FROM teachers t
            """
            query = base_query + " WHERE lower(t.name) LIKE ? ORDER BY t.name"
            params = (f"%{search_term}%",)

        try:
            cursor.execute(query, params)
            rows = cursor.fetchall()
        except Exception as e:
            print(f"Error during filtering: {e}")
            rows = []
        finally:
            conn.close()

        for row in rows:
            item_id = row[0]
            data = list(row[1:])
            if self.person_type == "teacher" and row[4]:
                subject_ids = [int(sid.strip()) for sid in row[4].split(',') if sid.strip().isdigit()]
                if subject_ids:
                    conn = get_db_connection()
                    sub_cursor = conn.cursor()
                    sub_cursor.execute("SELECT name FROM subjects WHERE id IN ({})".format(','.join('?' * len(subject_ids))), subject_ids)
                    subject_names = [sub[0] for sub in sub_cursor.fetchall()]
                    conn.close()
                    data[3] = ", ".join(subject_names)
                else:
                    data[3] = "None"

            btn_frame = tk.Frame(self.tree, bg="white")
            btn_edit = tk.Button(btn_frame, text="Edit", width=5, command=lambda i=item_id: self.show_edit_form(i), font=("Arial", 8))
            btn_edit.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=5, command=lambda i=item_id: self.delete_item(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            btn_view = tk.Button(btn_frame, text="View", width=5, command=lambda i=item_id: self.show_detail_view(i), font=("Arial", 8))
            btn_view.pack(side=tk.LEFT, padx=2)

            self.tree.insert('', tk.END, values=data + [btn_frame])
            self.tree.custom_widgets = getattr(self.tree, 'custom_widgets', {})
            self.tree.custom_widgets[item_id] = btn_frame


    def on_item_select(self, event):
        selected_items = self.tree.selection()
        if selected_items:
            item = self.tree.item(selected_items[0])
            self.selected_id = item['values'][0] # Assuming ID is the first column

    def show_add_form(self):
        self.current_action = "add"
        self.header_label.config(text=f"Add New {self.person_type.capitalize()}")
        self.search_entry.pack_forget()
        # FIX: Added a check to ensure filter_combo exists and is mapped before trying to pack_forget it.
        # This handles cases where filter_combo might only be created for students.
        if hasattr(self, 'filter_combo') and self.filter_combo.winfo_ismapped():
            self.filter_combo.pack_forget()
        self.add_button.pack_forget()
        self.refresh_button.pack_forget()
        self.tree.pack_forget()
        self.form_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        self.create_form_fields()
        self.clear_form()
        self.show_save_cancel_buttons("Add")

    def show_edit_form(self, item_id):
        self.current_action = "edit"
        self.selected_id = item_id
        self.header_label.config(text=f"Edit {self.person_type.capitalize()}")
        self.search_entry.pack_forget()
        self.filter_combo.pack_forget()
        self.add_button.pack_forget()
        self.refresh_button.pack_forget()
        self.tree.pack_forget()
        self.form_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        self.create_form_fields()
        self.load_item_data(item_id)
        self.show_save_cancel_buttons("Update")

    def show_detail_view(self, item_id):
        self.selected_id = item_id
        self.header_label.config(text=f"View {self.person_type.capitalize()} Details")
        self.search_entry.pack_forget()
        self.filter_combo.pack_forget()
        self.add_button.pack_forget()
        self.refresh_button.pack_forget()
        self.tree.pack_forget()
        self.form_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        self.create_detail_view_fields()
        self.load_item_data(item_id)
        # Hide save/cancel buttons and show back button
        for widget in self.form_frame.winfo_children():
            if isinstance(widget, tk.Button) and widget.cget("text") in ["Save", "Cancel"]:
                widget.pack_forget()
        tk.Button(self.form_frame, text="Back to List", command=self.show_list_view, font=("Arial", 10)).pack(pady=10)


    def create_form_fields(self):
        for widget in self.form_frame.winfo_children():
            widget.destroy()

        self.field_vars = {}
        row_num = 0

        if self.person_type == "student":
            fields = ["Name", "CNIC", "Father Name", "Contact", "Address", "Class", "Photo"]
            db_fields = ["name", "cnic", "father_name", "contact", "address", "class_id", "photo_path"]
            is_dropdown = [False, False, False, False, False, True, False]
            dropdown_sources = {
                "class_id": ("classes", "name", "id") # table, display_col, value_col
            }
        else: # teacher
            fields = ["Name", "CNIC", "Contact", "Address", "Subjects", "Role", "Photo"]
            db_fields = ["name", "cnic", "contact", "address", "subjects", "role", "photo_path"]
            is_dropdown = [False, False, False, False, True, True, False]
            dropdown_sources = {
                "subjects": ("subjects", "name", "id"),
                "role": (None, ["Teacher", "Admin", "Accountant"], None)
            }

        for i, field in enumerate(fields):
            lbl = tk.Label(self.form_frame, text=field + ":", font=("Arial", 11), bg="white")
            lbl.grid(row=row_num, column=0, padx=5, pady=5, sticky=tk.W)

            if is_dropdown[i]:
                var = tk.StringVar()
                self.field_vars[db_fields[i]] = var
                if dropdown_sources[db_fields[i]][0]: # Fetch from DB
                    source_table, display_col, value_col = dropdown_sources[db_fields[i]]
                    conn = get_db_connection()
                    cursor = conn.cursor()
                    cursor.execute(f"SELECT {value_col}, {display_col} FROM {source_table} ORDER BY {display_col}")
                    options = [(row[1], row[0]) for row in cursor.fetchall()] # (Display, Value)
                    conn.close()
                    options.insert(0, ("", "")) # Placeholder for empty selection
                    combo = ttk.Combobox(self.form_frame, textvariable=var, values=[opt[0] for opt in options], state="readonly", width=40)
                    combo.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.EW)
                    combo.set("") # Default empty
                    # Store actual values for lookup
                    combo.option_map = {opt[0]: opt[1] for opt in options}
                else: # Predefined list
                    values_list = dropdown_sources[db_fields[i]][1]
                    combo = ttk.Combobox(self.form_frame, textvariable=var, values=values_list, state="readonly", width=40)
                    combo.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.EW)
                    combo.set("")
            elif field == "Photo":
                self.photo_path.set("")
                photo_frame = tk.Frame(self.form_frame, bg="white")
                photo_frame.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.W)
                self.photo_label_img = tk.Label(photo_frame, text="[No Image]", bg="white", width=10, height=4, relief=tk.SOLID)
                self.photo_label_img.pack(side=tk.LEFT)
                btn_select_photo = tk.Button(photo_frame, text="Select", command=self.select_photo, font=("Arial", 9))
                btn_select_photo.pack(side=tk.LEFT, padx=5)
                self.field_vars[db_fields[i]] = self.photo_path # Use the StringVar directly
            else:
                var = tk.StringVar()
                self.field_vars[db_fields[i]] = var
                entry = tk.Entry(self.form_frame, textvariable=var, width=45, font=("Arial", 11))
                entry.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.EW)

            row_num += 1

        self.form_frame.grid_columnconfigure(1, weight=1)

    def create_detail_view_fields(self):
        for widget in self.form_frame.winfo_children():
            widget.destroy()

        row_num = 0
        if self.person_type == "student":
            fields = ["ID", "Name", "CNIC", "Father Name", "Contact", "Address", "Class", "Photo"]
            db_fields = ["id", "name", "cnic", "father_name", "contact", "address", "class_name", "photo_path"]
        else: # teacher
            fields = ["ID", "Name", "CNIC", "Contact", "Address", "Subjects", "Role", "Photo"]
            db_fields = ["id", "name", "cnic", "contact", "address", "subjects", "role", "photo_path"]

        for i, field in enumerate(fields):
            lbl = tk.Label(self.form_frame, text=field + ":", font=("Arial", 11, "bold"), bg="white")
            lbl.grid(row=row_num, column=0, padx=5, pady=5, sticky=tk.W)

            if field == "Photo":
                photo_frame = tk.Frame(self.form_frame, bg="white")
                photo_frame.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.W)
                self.photo_label_img = tk.Label(photo_frame, text="[No Image]", bg="white", width=10, height=4, relief=tk.SOLID)
                self.photo_label_img.pack(side=tk.LEFT)
            elif field == "Class": # Student specific
                lbl_val = tk.Label(self.form_frame, text="", font=("Arial", 11), bg="white")
                lbl_val.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.W)
                self.field_vars[db_fields[i]] = lbl_val
            elif field == "Subjects": # Teacher specific
                lbl_val = tk.Label(self.form_frame, text="", font=("Arial", 11), bg="white", wraplength=400, justify=tk.LEFT)
                lbl_val.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.W)
                self.field_vars[db_fields[i]] = lbl_val
            else:
                lbl_val = tk.Label(self.form_frame, text="", font=("Arial", 11), bg="white")
                lbl_val.grid(row=row_num, column=1, padx=5, pady=5, sticky=tk.W)
                self.field_vars[db_fields[i]] = lbl_val
            row_num += 1
        self.form_frame.grid_columnconfigure(1, weight=1)


    def load_item_data(self, item_id):
        conn = get_db_connection()
        cursor = conn.cursor()
        if self.person_type == "student":
            cursor.execute("SELECT s.*, c.name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?", (item_id,))
            row = cursor.fetchone()
            if row:
                self.field_vars['id'].set(row[0])
                self.field_vars['name'].set(row[1])
                self.field_vars['cnic'].set(row[2])
                self.field_vars['father_name'].set(row[3])
                self.field_vars['contact'].set(row[4])
                self.field_vars['address'].set(row[5])
                self.field_vars['class_id'].set(row[7]) # Class name for display in detail view
                self.field_vars['photo_path'].set(row[6])
                self.update_photo_display(row[6])
        else: # teacher
            cursor.execute("SELECT * FROM teachers WHERE id = ?", (item_id,))
            row = cursor.fetchone()
            if row:
                self.field_vars['id'].set(row[0])
                self.field_vars['name'].set(row[1])
                self.field_vars['cnic'].set(row[2])
                self.field_vars['contact'].set(row[3])
                self.field_vars['address'].set(row[4])
                # Process subjects
                subject_names = "None"
                if row[5]:
                    subject_ids = [int(sid.strip()) for sid in row[5].split(',') if sid.strip().isdigit()]
                    if subject_ids:
                        sub_cursor = conn.cursor()
                        sub_cursor.execute("SELECT name FROM subjects WHERE id IN ({})".format(','.join('?' * len(subject_ids))), subject_ids)
                        subject_names = ", ".join([sub[0] for sub in sub_cursor.fetchall()])
                self.field_vars['subjects'].set(subject_names)
                self.field_vars['role'].set(row[6])
                self.field_vars['photo_path'].set(row[7])
                self.update_photo_display(row[7])
        conn.close()


    def update_photo_display(self, photo_path):
        if photo_path and os.path.exists(photo_path):
            try:
                img = Image.open(photo_path)
                img.thumbnail((80, 80))
                self.photo_label_img_tk = ImageTk.PhotoImage(img)
                self.photo_label_img.config(image=self.photo_label_img_tk, text="")
            except Exception as e:
                print(f"Error loading photo: {e}")
                self.photo_label_img.config(image='', text="[Load Error]")
        else:
            self.photo_label_img.config(image='', text="[No Image]")


    def select_photo(self):
        filepath = filedialog.askopenfilename(title="Select Photo", filetypes=[("Image Files", "*.png *.jpg *.jpeg")])
        if filepath:
            self.photo_path.set(filepath)
            self.update_photo_display(filepath)

    def save_item(self):
        values = {}
        for field, var in self.field_vars.items():
            values[field] = var.get()

        if not values.get("name"):
            messagebox.showerror("Input Error", "Name is required.")
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Handle photo copying if a new one is selected
        final_photo_path = values.get("photo_path")
        if final_photo_path and self.current_action == "add":
             # Generate a unique filename based on name and timestamp
            base, ext = os.path.splitext(os.path.basename(final_photo_path))
            timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
            new_filename = f"{base}_{timestamp}{ext}"
            dest_path = os.path.join("photos", new_filename) # Store in a 'photos' subdirectory
            os.makedirs("photos", exist_ok=True)
            try:
                import shutil
                shutil.copyfile(final_photo_path, dest_path)
                final_photo_path = dest_path
            except Exception as e:
                messagebox.showerror("File Error", f"Could not copy photo: {e}")
                final_photo_path = "" # Clear if copy failed

        elif final_photo_path and self.current_action == "edit":
            # Check if the photo is the same as the currently stored one
            conn_check = get_db_connection()
            cursor_check = conn_check.cursor()
            cursor_check.execute(f"SELECT photo_path FROM {self.person_type}s WHERE id = ?", (self.selected_id,))
            current_photo_path = cursor_check.fetchone()[0]
            conn_check.close()
            if final_photo_path != current_photo_path:
                 # A new photo was selected, copy it
                base, ext = os.path.splitext(os.path.basename(final_photo_path))
                timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
                new_filename = f"{base}_{timestamp}{ext}"
                dest_path = os.path.join("photos", new_filename)
                os.makedirs("photos", exist_ok=True)
                try:
                    import shutil
                    shutil.copyfile(final_photo_path, dest_path)
                    final_photo_path = dest_path
                     # Optionally remove the old photo file if it's not the default placeholder
                    if current_photo_path and current_photo_path.startswith("photos/") and os.path.exists(current_photo_path):
                       try:
                           os.remove(current_photo_path)
                       except OSError as e:
                           print(f"Warning: Could not remove old photo {current_photo_path}: {e}")
                except Exception as e:
                    messagebox.showerror("File Error", f"Could not copy photo: {e}")
                    final_photo_path = current_photo_path # Revert to old path if copy fails

        elif not final_photo_path and self.current_action == "edit":
             # Photo was cleared, ensure db reflects this
            conn_check = get_db_connection()
            cursor_check = conn_check.cursor()
            cursor_check.execute(f"SELECT photo_path FROM {self.person_type}s WHERE id = ?", (self.selected_id,))
            current_photo_path = cursor_check.fetchone()[0]
            conn_check.close()
            if current_photo_path and current_photo_path.startswith("photos/") and os.path.exists(current_photo_path):
                 try:
                     os.remove(current_photo_path)
                 except OSError as e:
                     print(f"Warning: Could not remove old photo {current_photo_path}: {e}")
            final_photo_path = "" # Set to empty string for DB update


        if self.person_type == "student":
            table = "students"
            field_map = {
                "name": "name", "cnic": "cnic", "father_name": "father_name",
                "contact": "contact", "address": "address", "class_id": "class_id",
                "photo_path": "photo_path"
            }
            if self.current_action == "add":
                class_display_name = values.get("class_id")
                class_id_to_insert = ""
                if class_display_name:
                    conn_class = get_db_connection()
                    cursor_class = conn_class.cursor()
                    cursor_class.execute("SELECT id FROM classes WHERE name = ?", (class_display_name,))
                    class_result = cursor_class.fetchone()
                    conn_class.close()
                    if class_result:
                        class_id_to_insert = class_result[0]

                sql = f"INSERT INTO {table} (name, cnic, father_name, contact, address, class_id, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?)"
                params = (values.get("name"), values.get("cnic"), values.get("father_name"),
                          values.get("contact"), values.get("address"), class_id_to_insert, final_photo_path)
                try:
                    cursor.execute(sql, params)
                    messagebox.showinfo("Success", f"{self.person_type.capitalize()} added successfully.")
                    self.show_list_view()
                except sqlite3.IntegrityError as e:
                    messagebox.showerror("Error", f"Database error: {e}. CNIC might be duplicated.")
                except Exception as e:
                    messagebox.showerror("Error", f"An error occurred: {e}")
            else: # edit
                class_display_name = values.get("class_id")
                class_id_to_update = ""
                if class_display_name:
                    conn_class = get_db_connection()
                    cursor_class = conn_class.cursor()
                    cursor_class.execute("SELECT id FROM classes WHERE name = ?", (class_display_name,))
                    class_result = cursor_class.fetchone()
                    conn_class.close()
                    if class_result:
                        class_id_to_update = class_result[0]

                sql = f"UPDATE {table} SET name=?, cnic=?, father_name=?, contact=?, address=?, class_id=?, photo_path=? WHERE id=?"
                params = (values.get("name"), values.get("cnic"), values.get("father_name"),
                          values.get("contact"), values.get("address"), class_id_to_update, final_photo_path, self.selected_id)
                try:
                    cursor.execute(sql, params)
                    messagebox.showinfo("Success", f"{self.person_type.capitalize()} updated successfully.")
                    self.show_list_view()
                except sqlite3.IntegrityError as e:
                    messagebox.showerror("Error", f"Database error: {e}. CNIC might be duplicated.")
                except Exception as e:
                    messagebox.showerror("Error", f"An error occurred: {e}")

        else: # teacher
            table = "teachers"
            field_map = {
                "name": "name", "cnic": "cnic", "contact": "contact",
                "address": "address", "subjects": "subjects", "role": "role",
                "photo_path": "photo_path"
            }

            # Process selected subjects
            selected_subject_names = values.get("subjects")
            subject_ids_to_store = []
            if selected_subject_names:
                conn_sub = get_db_connection()
                cursor_sub = conn_sub.cursor()
                # Fetch IDs for all subjects initially to handle potential mismatches
                cursor_sub.execute("SELECT id, name FROM subjects")
                all_subjects = {name: id for id, name in cursor_sub.fetchall()}
                conn_sub.close()

                for subject_name in selected_subject_names.split(','):
                    subject_name = subject_name.strip()
                    if subject_name in all_subjects:
                        subject_ids_to_store.append(str(all_subjects[subject_name]))
            subjects_str = ",".join(subject_ids_to_store)

            if self.current_action == "add":
                sql = f"INSERT INTO {table} (name, cnic, contact, address, subjects, role, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?)"
                params = (values.get("name"), values.get("cnic"), values.get("contact"),
                          values.get("address"), subjects_str, values.get("role"), final_photo_path)
                try:
                    cursor.execute(sql, params)
                    messagebox.showinfo("Success", f"{self.person_type.capitalize()} added successfully.")
                    self.show_list_view()
                except sqlite3.IntegrityError as e:
                    messagebox.showerror("Error", f"Database error: {e}. CNIC might be duplicated.")
                except Exception as e:
                    messagebox.showerror("Error", f"An error occurred: {e}")

            else: # edit
                sql = f"UPDATE {table} SET name=?, cnic=?, contact=?, address=?, subjects=?, role=?, photo_path=? WHERE id=?"
                params = (values.get("name"), values.get("cnic"), values.get("contact"),
                          values.get("address"), subjects_str, values.get("role"), final_photo_path, self.selected_id)
                try:
                    cursor.execute(sql, params)
                    messagebox.showinfo("Success", f"{self.person_type.capitalize()} updated successfully.")
                    self.show_list_view()
                except sqlite3.IntegrityError as e:
                    messagebox.showerror("Error", f"Database error: {e}. CNIC might be duplicated.")
                except Exception as e:
                    messagebox.showerror("Error", f"An error occurred: {e}")

        conn.commit()
        conn.close()
        self.load_data() # Refresh the list

    def delete_item(self, item_id):
        if not messagebox.askyesno("Confirm Delete", f"Are you sure you want to delete this {self.person_type}? This action cannot be undone."):
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Get photo path before deleting
        photo_path_to_delete = None
        cursor.execute(f"SELECT photo_path FROM {self.person_type}s WHERE id = ?", (item_id,))
        result = cursor.fetchone()
        if result and result[0]:
            photo_path_to_delete = result[0]

        try:
            cursor.execute(f"DELETE FROM {self.person_type}s WHERE id = ?", (item_id,))
            conn.commit()
            messagebox.showinfo("Success", f"{self.person_type.capitalize()} deleted successfully.")
            self.load_data() # Refresh the list

            # Attempt to delete the photo file if it exists and is in the 'photos' directory
            if photo_path_to_delete and photo_path_to_delete.startswith("photos/") and os.path.exists(photo_path_to_delete):
                try:
                    os.remove(photo_path_to_delete)
                except OSError as e:
                    print(f"Warning: Could not delete photo file {photo_path_to_delete}: {e}")

        except Exception as e:
            messagebox.showerror("Error", f"An error occurred during deletion: {e}")
        finally:
            conn.close()

    def show_list_view(self):
        self.form_frame.pack_forget()
        self.header_label.config(text=f"{self.person_type.capitalize()}s")
        self.search_entry.pack(side=tk.LEFT, padx=5)
        if self.person_type == "student":
            self.filter_combo.pack(side=tk.LEFT, padx=5)
        self.add_button.pack(side=tk.RIGHT, padx=5)
        self.refresh_button.pack(side=tk.RIGHT, padx=5)
        self.tree.pack(fill=tk.BOTH, expand=True, pady=10)
        self.load_data() # Reload data to ensure consistency


    def show_save_cancel_buttons(self, action_text):
        button_frame = tk.Frame(self.form_frame)
        button_frame.grid(row=len(self.field_vars) + 1, columnspan=2, pady=10)
        save_btn = tk.Button(button_frame, text=action_text, command=self.save_item, width=12, font=("Arial", 10, "bold"))
        save_btn.pack(side=tk.LEFT, padx=10)
        cancel_btn = tk.Button(button_frame, text="Cancel", command=self.show_list_view, width=12, font=("Arial", 10))
        cancel_btn.pack(side=tk.LEFT, padx=10)

    def clear_form(self):
        for var in self.field_vars.values():
            if isinstance(var, tk.StringVar):
                var.set("")
        if self.photo_label_img:
            self.photo_label_img.config(image='')
        self.photo_path.set("")


class AcademicsView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.current_tab = None
        self.create_widgets()
        self.load_data()

    def create_widgets(self):
        header_frame = tk.Frame(self, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="Academic Management", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        self.notebook = ttk.Notebook(self)
        self.notebook.pack(fill=tk.BOTH, expand=True, padx=20, pady=10)

        # Classes Tab
        self.classes_frame = tk.Frame(self.notebook, bg="white")
        self.notebook.add(self.classes_frame, text="Classes & Sections")
        self.create_classes_widgets()

        # Subjects Tab
        self.subjects_frame = tk.Frame(self.notebook, bg="white")
        self.notebook.add(self.subjects_frame, text="Subjects")
        self.create_subjects_widgets()

        # Class Subject Teacher Assignment Tab
        self.assignment_frame = tk.Frame(self.notebook, bg="white")
        self.notebook.add(self.assignment_frame, text="Assign Teachers")
        self.create_assignment_widgets()

        self.notebook.bind("<<NotebookTabChanged>>", self.on_tab_change)
        self.on_tab_change() # Trigger initial load for the first tab

    def create_classes_widgets(self):
        # Add Class Frame
        add_class_frame = tk.Frame(self.classes_frame, bg="white")
        add_class_frame.pack(fill=tk.X, pady=10)
        tk.Label(add_class_frame, text="Class Name:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        self.class_name_entry = tk.Entry(add_class_frame, width=30, font=("Arial", 11))
        self.class_name_entry.pack(side=tk.LEFT, padx=5)
        tk.Button(add_class_frame, text="Add Class", command=self.add_class, font=("Arial", 10)).pack(side=tk.LEFT, padx=5)
        tk.Button(add_class_frame, text="Refresh", command=self.load_classes, font=("Arial", 10)).pack(side=tk.LEFT, padx=5)

        # Class List Frame
        class_list_frame = tk.Frame(self.classes_frame, bg="white", bd=2, relief=tk.GROOVE)
        class_list_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        class_scroll = tk.Scrollbar(class_list_frame)
        class_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('class_id', 'class_name', 'actions')
        self.classes_tree = ttk.Treeview(class_list_frame, columns=columns, show='headings', yscrollcommand=class_scroll.set, selectmode="browse")
        self.classes_tree.heading('class_id', text='ID')
        self.classes_tree.heading('class_name', text='Class Name')
        self.classes_tree.heading('actions', text='Actions')
        self.classes_tree.column('class_id', width=50, anchor=tk.CENTER)
        self.classes_tree.column('class_name', width=200)
        self.classes_tree.column('actions', width=100, anchor=tk.CENTER)
        class_scroll.config(command=self.classes_tree.yview)
        self.classes_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.classes_tree.bind('<<TreeviewSelect>>', self.on_class_select)

        # Section Frame (associated with selected class)
        self.selected_class_id = None
        section_manage_frame = tk.LabelFrame(self.classes_frame, text="Sections Management", padx=10, pady=10, bg="white")
        section_manage_frame.pack(fill=tk.X, pady=10)
        tk.Label(section_manage_frame, text="Section Name:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        self.section_name_entry = tk.Entry(section_manage_frame, width=20, font=("Arial", 11))
        self.section_name_entry.pack(side=tk.LEFT, padx=5)
        self.add_section_button = tk.Button(section_manage_frame, text="Add Section", command=self.add_section, state=tk.DISABLED, font=("Arial", 10))
        self.add_section_button.pack(side=tk.LEFT, padx=5)
        self.delete_section_button = tk.Button(section_manage_frame, text="Delete Section", command=self.delete_section, state=tk.DISABLED, font=("Arial", 10), fg="red")
        self.delete_section_button.pack(side=tk.LEFT, padx=5)

        section_list_frame = tk.Frame(self.classes_frame, bg="white", bd=2, relief=tk.GROOVE)
        section_list_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        section_scroll = tk.Scrollbar(section_list_frame)
        section_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('section_id', 'section_name')
        self.sections_tree = ttk.Treeview(section_list_frame, columns=columns, show='headings', yscrollcommand=section_scroll.set, selectmode="browse")
        self.sections_tree.heading('section_id', text='ID')
        self.sections_tree.heading('section_name', text='Section Name')
        self.sections_tree.column('section_id', width=50, anchor=tk.CENTER)
        self.sections_tree.column('section_name', width=250)
        section_scroll.config(command=self.sections_tree.yview)
        self.sections_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

    def create_subjects_widgets(self):
        add_subject_frame = tk.Frame(self.subjects_frame, bg="white")
        add_subject_frame.pack(fill=tk.X, pady=10)
        tk.Label(add_subject_frame, text="Subject Name:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        self.subject_name_entry = tk.Entry(add_subject_frame, width=30, font=("Arial", 11))
        self.subject_name_entry.pack(side=tk.LEFT, padx=5)
        tk.Button(add_subject_frame, text="Add Subject", command=self.add_subject, font=("Arial", 10)).pack(side=tk.LEFT, padx=5)
        tk.Button(add_subject_frame, text="Refresh", command=self.load_subjects, font=("Arial", 10)).pack(side=tk.LEFT, padx=5)

        subject_list_frame = tk.Frame(self.subjects_frame, bg="white", bd=2, relief=tk.GROOVE)
        subject_list_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        subject_scroll = tk.Scrollbar(subject_list_frame)
        subject_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('subject_id', 'subject_name', 'actions')
        self.subjects_tree = ttk.Treeview(subject_list_frame, columns=columns, show='headings', yscrollcommand=subject_scroll.set, selectmode="browse")
        self.subjects_tree.heading('subject_id', text='ID')
        self.subjects_tree.heading('subject_name', text='Subject Name')
        self.subjects_tree.heading('actions', text='Actions')
        self.subjects_tree.column('subject_id', width=50, anchor=tk.CENTER)
        self.subjects_tree.column('subject_name', width=250)
        self.subjects_tree.column('actions', width=100, anchor=tk.CENTER)
        subject_scroll.config(command=self.subjects_tree.yview)
        self.subjects_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

    def create_assignment_widgets(self):
        self.assignment_vars = {}
        form_frame = tk.Frame(self.assignment_frame, bg="white")
        form_frame.pack(pady=10)

        tk.Label(form_frame, text="Class:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.class_assign_combo_var = tk.StringVar()
        self.class_assign_combo = ttk.Combobox(form_frame, textvariable=self.class_assign_combo_var, state="readonly", width=30)
        self.class_assign_combo.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)
        self.class_assign_combo.bind("<<ComboboxSelected>>", self.on_class_assign_select)

        tk.Label(form_frame, text="Section:", font=("Arial", 11)).grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        self.section_assign_combo_var = tk.StringVar()
        self.section_assign_combo = ttk.Combobox(form_frame, textvariable=self.section_assign_combo_var, state="readonly", width=30)
        self.section_assign_combo.grid(row=1, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(form_frame, text="Subject:", font=("Arial", 11)).grid(row=2, column=0, padx=5, pady=5, sticky=tk.W)
        self.subject_assign_combo_var = tk.StringVar()
        self.subject_assign_combo = ttk.Combobox(form_frame, textvariable=self.subject_assign_combo_var, state="readonly", width=30)
        self.subject_assign_combo.grid(row=2, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(form_frame, text="Teacher:", font=("Arial", 11)).grid(row=3, column=0, padx=5, pady=5, sticky=tk.W)
        self.teacher_assign_combo_var = tk.StringVar()
        self.teacher_assign_combo = ttk.Combobox(form_frame, textvariable=self.teacher_assign_combo_var, state="readonly", width=30)
        self.teacher_assign_combo.grid(row=3, column=1, padx=5, pady=5, sticky=tk.EW)

        form_frame.grid_columnconfigure(1, weight=1)

        tk.Button(self.assignment_frame, text="Assign Subject", command=self.assign_teacher_subject, font=("Arial", 10, "bold")).pack(pady=10)
        tk.Button(self.assignment_frame, text="Refresh Assignments", command=self.load_assignments, font=("Arial", 10)).pack(pady=5)

        # Assignment List
        assignment_list_frame = tk.Frame(self.assignment_frame, bg="white", bd=2, relief=tk.GROOVE)
        assignment_list_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        assignment_scroll = tk.Scrollbar(assignment_list_frame)
        assignment_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('assignment_id', 'class_name', 'section_name', 'subject_name', 'teacher_name', 'actions')
        self.assignments_tree = ttk.Treeview(assignment_list_frame, columns=columns, show='headings', yscrollcommand=assignment_scroll.set, selectmode="browse")
        self.assignments_tree.heading('assignment_id', text='ID')
        self.assignments_tree.heading('class_name', text='Class')
        self.assignments_tree.heading('section_name', text='Section')
        self.assignments_tree.heading('subject_name', text='Subject')
        self.assignments_tree.heading('teacher_name', text='Teacher')
        self.assignments_tree.heading('actions', text='Actions')
        self.assignments_tree.column('assignment_id', width=50, anchor=tk.CENTER)
        self.assignments_tree.column('class_name', width=100)
        self.assignments_tree.column('section_name', width=80)
        self.assignments_tree.column('subject_name', width=120)
        self.assignments_tree.column('teacher_name', width=150)
        self.assignments_tree.column('actions', width=80, anchor=tk.CENTER)
        assignment_scroll.config(command=self.assignments_tree.yview)
        self.assignments_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)


    def on_tab_change(self, event=None):
        selected_tab_index = self.notebook.index(self.notebook.select())
        self.current_tab = self.notebook.tabs()[selected_tab_index]
        if self.current_tab.endswith("Classes & Sections"):
            self.load_classes()
            self.load_sections() # Load sections for the initially selected class if any
        elif self.current_tab.endswith("Subjects"):
            self.load_subjects()
        elif self.current_tab.endswith("Assign Teachers"):
            self.load_assignment_combobox_data()
            self.load_assignments()

    def load_data(self):
        self.load_classes()
        self.load_subjects()
        self.load_assignment_combobox_data()
        self.load_assignments()

    def load_classes(self):
        for item in self.classes_tree.get_children():
            self.classes_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM classes ORDER BY name")
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            class_id, class_name = row
            btn_frame = tk.Frame(self.classes_tree, bg="white")
            btn_edit = tk.Button(btn_frame, text="Edit", width=4, command=lambda i=class_id, n=class_name: self.edit_class(i, n), font=("Arial", 8))
            btn_edit.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=4, command=lambda i=class_id: self.delete_class(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            self.classes_tree.insert('', tk.END, values=(class_id, class_name, btn_frame))
            self.classes_tree.custom_widgets = getattr(self.classes_tree, 'custom_widgets', {})
            self.classes_tree.custom_widgets[class_id] = btn_frame

    def load_subjects(self):
        for item in self.subjects_tree.get_children():
            self.subjects_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM subjects ORDER BY name")
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            subject_id, subject_name = row
            btn_frame = tk.Frame(self.subjects_tree, bg="white")
            btn_edit = tk.Button(btn_frame, text="Edit", width=4, command=lambda i=subject_id, n=subject_name: self.edit_subject(i, n), font=("Arial", 8))
            btn_edit.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=4, command=lambda i=subject_id: self.delete_subject(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            self.subjects_tree.insert('', tk.END, values=(subject_id, subject_name, btn_frame))
            self.subjects_tree.custom_widgets = getattr(self.subjects_tree, 'custom_widgets', {})
            self.subjects_tree.custom_widgets[subject_id] = btn_frame

    def load_sections(self):
        for item in self.sections_tree.get_children():
            self.sections_tree.delete(item)
        if self.selected_class_id:
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT id, name FROM sections WHERE class_id = ? ORDER BY name", (self.selected_class_id,))
            rows = cursor.fetchall()
            conn.close()
            for row in rows:
                self.sections_tree.insert('', tk.END, values=row)

    def load_assignment_combobox_data(self):
        conn = get_db_connection()
        cursor = conn.cursor()

        # Load classes
        cursor.execute("SELECT id, name FROM classes ORDER BY name")
        classes = [(row[1], row[0]) for row in cursor.fetchall()] # (Name, ID)
        self.class_assign_combo['values'] = [c[0] for c in classes]
        self.class_assign_combo.option_map = {c[0]: c[1] for c in classes}

        # Load subjects
        cursor.execute("SELECT id, name FROM subjects ORDER BY name")
        subjects = [(row[1], row[0]) for row in cursor.fetchall()]
        self.subject_assign_combo['values'] = [s[0] for s in subjects]
        self.subject_assign_combo.option_map = {s[0]: s[1] for s in subjects}

        # Load teachers
        cursor.execute("SELECT id, name FROM teachers ORDER BY name")
        teachers = [(row[1], row[0]) for row in cursor.fetchall()]
        self.teacher_assign_combo['values'] = [t[0] for t in teachers]
        self.teacher_assign_combo.option_map = {t[0]: t[1] for t in teachers}

        conn.close()

    def load_assignments(self):
        for item in self.assignments_tree.get_children():
            self.assignments_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        query = """
            SELECT cst.id, cl.name, s.name, sub.name, t.name
            FROM class_subject_teacher cst
            JOIN classes cl ON cst.class_id = cl.id
            JOIN sections s ON cst.section_id = s.id
            JOIN subjects sub ON cst.subject_id = sub.id
            JOIN teachers t ON cst.teacher_id = t.id
            ORDER BY cl.name, s.name, sub.name
        """
        cursor.execute(query)
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            assignment_id, class_name, section_name, subject_name, teacher_name = row
            btn_frame = tk.Frame(self.assignments_tree, bg="white")
            btn_delete = tk.Button(btn_frame, text="Del", width=5, command=lambda i=assignment_id: self.delete_assignment(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            self.assignments_tree.insert('', tk.END, values=(assignment_id, class_name, section_name, subject_name, teacher_name, btn_frame))
            self.assignments_tree.custom_widgets = getattr(self.assignments_tree, 'custom_widgets', {})
            self.assignments_tree.custom_widgets[assignment_id] = btn_frame


    def add_class(self):
        class_name = self.class_name_entry.get().strip()
        if not class_name:
            messagebox.showwarning("Input Error", "Class name cannot be empty.")
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("INSERT INTO classes (name) VALUES (?)", (class_name,))
            conn.commit()
            messagebox.showinfo("Success", f"Class '{class_name}' added successfully.")
            self.class_name_entry.delete(0, tk.END)
            self.load_classes()
            self.load_assignment_combobox_data() # Update comboboxes
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", f"Class '{class_name}' already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def edit_class(self, class_id, old_name):
        new_name = tk.simpledialog.askstring("Edit Class", "Enter new class name:", initialvalue=old_name)
        if new_name and new_name.strip() != old_name:
            new_name = new_name.strip()
            conn = get_db_connection()
            cursor = conn.cursor()
            try:
                cursor.execute("UPDATE classes SET name = ? WHERE id = ?", (new_name, class_id))
                conn.commit()
                messagebox.showinfo("Success", f"Class renamed to '{new_name}' successfully.")
                self.load_classes()
                self.load_assignment_combobox_data()
            except sqlite3.IntegrityError:
                messagebox.showerror("Error", f"Class name '{new_name}' already exists.")
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")
            finally:
                conn.close()

    def delete_class(self, class_id):
        if not messagebox.askyesno("Confirm Delete", "Deleting a class will also delete associated sections and assignments. Are you sure?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            # Delete associated sections first
            cursor.execute("SELECT id FROM sections WHERE class_id = ?", (class_id,))
            section_ids = [row[0] for row in cursor.fetchall()]
            if section_ids:
                placeholders = ','.join('?' * len(section_ids))
                cursor.execute(f"DELETE FROM class_subject_teacher WHERE section_id IN ({placeholders})", section_ids)
                cursor.execute(f"DELETE FROM sections WHERE class_id = ?", (class_id,))

            cursor.execute("DELETE FROM students WHERE class_id = ?", (class_id,)) # Students in this class
            cursor.execute("DELETE FROM class_subject_teacher WHERE class_id = ?", (class_id,)) # Assignments for this class
            cursor.execute("DELETE FROM classes WHERE id = ?", (class_id,))
            conn.commit()
            messagebox.showinfo("Success", "Class and its associated data deleted successfully.")
            self.load_classes()
            self.load_sections() # Clear sections tree if the current class was deleted
            self.selected_class_id = None
            self.add_section_button.config(state=tk.DISABLED)
            self.delete_section_button.config(state=tk.DISABLED)
            self.load_assignment_combobox_data()
            self.load_assignments()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred during deletion: {e}")
        finally:
            conn.close()

    def on_class_select(self, event):
        selected_items = self.classes_tree.selection()
        if selected_items:
            item = self.classes_tree.item(selected_items[0])
            self.selected_class_id = item['values'][0]
            self.add_section_button.config(state=tk.NORMAL)
            self.delete_section_button.config(state=tk.NORMAL)
            self.load_sections()
        else:
            self.selected_class_id = None
            self.add_section_button.config(state=tk.DISABLED)
            self.delete_section_button.config(state=tk.DISABLED)

    def add_section(self):
        if not self.selected_class_id:
            messagebox.showwarning("Selection Error", "Please select a class first.")
            return
        section_name = self.section_name_entry.get().strip()
        if not section_name:
            messagebox.showwarning("Input Error", "Section name cannot be empty.")
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("INSERT INTO sections (class_id, name) VALUES (?, ?)", (self.selected_class_id, section_name))
            conn.commit()
            messagebox.showinfo("Success", f"Section '{section_name}' added successfully to class.")
            self.section_name_entry.delete(0, tk.END)
            self.load_sections()
            self.load_assignment_combobox_data() # Update comboboxes for assignments
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", f"Section '{section_name}' already exists for this class.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def delete_section(self):
        selected_items = self.sections_tree.selection()
        if not selected_items:
            messagebox.showwarning("Selection Error", "Please select a section to delete.")
            return
        section_id, section_name = self.sections_tree.item(selected_items[0])['values']

        if not messagebox.askyesno("Confirm Delete", f"Deleting section '{section_name}' will also delete associated assignments. Are you sure?"):
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM class_subject_teacher WHERE section_id = ?", (section_id,))
            cursor.execute("DELETE FROM sections WHERE id = ?", (section_id,))
            conn.commit()
            messagebox.showinfo("Success", f"Section '{section_name}' and its assignments deleted successfully.")
            self.load_sections()
            self.load_assignment_combobox_data()
            self.load_assignments()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred during deletion: {e}")
        finally:
            conn.close()


    def add_subject(self):
        subject_name = self.subject_name_entry.get().strip()
        if not subject_name:
            messagebox.showwarning("Input Error", "Subject name cannot be empty.")
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("INSERT INTO subjects (name) VALUES (?)", (subject_name,))
            conn.commit()
            messagebox.showinfo("Success", f"Subject '{subject_name}' added successfully.")
            self.subject_name_entry.delete(0, tk.END)
            self.load_subjects()
            self.load_assignment_combobox_data()
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", f"Subject '{subject_name}' already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def edit_subject(self, subject_id, old_name):
        new_name = tk.simpledialog.askstring("Edit Subject", "Enter new subject name:", initialvalue=old_name)
        if new_name and new_name.strip() != old_name:
            new_name = new_name.strip()
            conn = get_db_connection()
            cursor = conn.cursor()
            try:
                cursor.execute("UPDATE subjects SET name = ? WHERE id = ?", (new_name, subject_id))
                conn.commit()
                messagebox.showinfo("Success", f"Subject renamed to '{new_name}' successfully.")
                self.load_subjects()
                self.load_assignment_combobox_data()
            except sqlite3.IntegrityError:
                messagebox.showerror("Error", f"Subject name '{new_name}' already exists.")
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")
            finally:
                conn.close()

    def delete_subject(self, subject_id):
        if not messagebox.askyesno("Confirm Delete", "Deleting a subject will also remove it from all assignments. Are you sure?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM class_subject_teacher WHERE subject_id = ?", (subject_id,))
            cursor.execute("DELETE FROM subjects WHERE id = ?", (subject_id,))
            conn.commit()
            messagebox.showinfo("Success", "Subject and its assignments deleted successfully.")
            self.load_subjects()
            self.load_assignment_combobox_data()
            self.load_assignments()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred during deletion: {e}")
        finally:
            conn.close()

    def assign_teacher_subject(self):
        class_name = self.class_assign_combo_var.get()
        section_name = self.section_assign_combo_var.get()
        subject_name = self.subject_assign_combo_var.get()
        teacher_name = self.teacher_assign_combo_var.get()

        if not all([class_name, section_name, subject_name, teacher_name]):
            messagebox.showwarning("Input Error", "Please select Class, Section, Subject, and Teacher.")
            return

        class_id = self.class_assign_combo.option_map.get(class_name)
        section_id = None
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id FROM sections WHERE class_id = ? AND name = ?", (class_id, section_name))
        section_result = cursor.fetchone()
        if section_result:
            section_id = section_result[0]

        subject_id = self.subject_assign_combo.option_map.get(subject_name)
        teacher_id = self.teacher_assign_combo.option_map.get(teacher_name)

        if not all([class_id, section_id, subject_id, teacher_id]):
             messagebox.showerror("Error", "Could not resolve IDs for selected items. Please check your data.")
             conn.close()
             return

        try:
            cursor.execute("INSERT INTO class_subject_teacher (class_id, section_id, subject_id, teacher_id) VALUES (?, ?, ?, ?)",
                           (class_id, section_id, subject_id, teacher_id))
            conn.commit()
            messagebox.showinfo("Success", "Teacher assigned to subject and class successfully.")
            self.load_assignments()
            self.clear_assignment_form()
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "This assignment already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def on_class_assign_select(self, event=None):
        selected_class_name = self.class_assign_combo_var.get()
        if not selected_class_name:
            self.section_assign_combo['values'] = []
            self.section_assign_combo_var.set("")
            return

        class_id = self.class_assign_combo.option_map.get(selected_class_name)
        if not class_id: return

        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM sections WHERE class_id = ? ORDER BY name", (class_id,))
        sections = [(row[1], row[0]) for row in cursor.fetchall()] # (Name, ID)
        conn.close()

        self.section_assign_combo['values'] = [s[0] for s in sections]
        self.section_assign_combo.option_map = {s[0]: s[1] for s in sections}
        self.section_assign_combo_var.set("") # Reset section selection

    def delete_assignment(self, assignment_id):
        if not messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this assignment?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM class_subject_teacher WHERE id = ?", (assignment_id,))
            conn.commit()
            messagebox.showinfo("Success", "Assignment deleted successfully.")
            self.load_assignments()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def clear_assignment_form(self):
        self.class_assign_combo_var.set("")
        self.section_assign_combo_var.set("")
        self.subject_assign_combo_var.set("")
        self.teacher_assign_combo_var.set("")
        self.section_assign_combo['values'] = []

class AttendanceView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.selected_date = tk.StringVar(value=datetime.now().strftime("%Y-%m-%d"))
        self.current_view = "select_date" # "select_date" or "mark_attendance"
        self.class_data = {} # Cache for class IDs and names

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        self.create_widgets()
        self.load_classes_for_selection()

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        self.header_label = tk.Label(header_frame, text="Attendance Management", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20)
        self.header_label.pack(side=tk.LEFT)

        # Date Selection Frame (Initial View)
        self.date_selection_frame = tk.Frame(self.main_frame, bg="white")
        self.date_selection_frame.pack(pady=20)

        tk.Label(self.date_selection_frame, text="Select Date:", font=("Arial", 12)).grid(row=0, column=0, padx=5, pady=5)
        self.date_entry = tk.Entry(self.date_selection_frame, textvariable=self.selected_date, width=15, font=("Arial", 11))
        self.date_entry.grid(row=0, column=1, padx=5, pady=5)
        # Simple Calendar Picker (using ttkcalendar if available, otherwise basic entry)
        try:
            from tkcalendar import Calendar
            self.cal_button = tk.Button(self.date_selection_frame, text="📅", command=self.pick_date, font=("Arial", 10))
            self.cal_button.grid(row=0, column=2, padx=5)
        except ImportError:
            self.cal_button = None # Button won't be shown if tkcalendar is not installed

        tk.Label(self.date_selection_frame, text="Select Class:", font=("Arial", 12)).grid(row=1, column=0, padx=5, pady=5)
        self.class_combo_var = tk.StringVar()
        self.class_combo = ttk.Combobox(self.date_selection_frame, textvariable=self.class_combo_var, state="readonly", width=25)
        self.class_combo.grid(row=1, column=1, columnspan=2, padx=5, pady=5, sticky=tk.EW)

        tk.Button(self.date_selection_frame, text="View Attendance Sheet", command=self.show_attendance_sheet, font=("Arial", 11, "bold")).grid(row=2, column=0, columnspan=3, pady=15)

        # Attendance Sheet Frame (will be shown after date/class selection)
        self.attendance_sheet_frame = tk.Frame(self.main_frame, bg="white")
        # Treeview for attendance will be created dynamically

    def pick_date(self):
        if self.cal_button:
            from tkcalendar import DateEntry
            top = tk.Toplevel(self)
            cal = DateEntry(top, width=12, background='darkblue', foreground='white', borderwidth=2, date_pattern='yyyy-mm-dd')
            cal.pack(padx=10, pady=10)
            def set_date():
                self.selected_date.set(cal.get_date())
                top.destroy()
            tk.Button(top, text="Select", command=set_date).pack(pady=5)
            top.transient(self)
            top.grab_set()
            top.mainloop()

    def load_classes_for_selection(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM classes ORDER BY name")
        rows = cursor.fetchall()
        conn.close()
        self.class_data = {row[1]: row[0] for row in rows} # Name: ID mapping
        self.class_combo['values'] = list(self.class_data.keys())
        if rows:
            self.class_combo.set(rows[0][1]) # Select the first class by default

    def show_attendance_sheet(self):
        self.current_view = "mark_attendance"
        self.header_label.config(text="Mark Attendance")
        self.date_selection_frame.pack_forget()

        selected_class_name = self.class_combo_var.get()
        if not selected_class_name or not self.class_data.get(selected_class_name):
            messagebox.showwarning("Selection Error", "Please select a valid class.")
            self.show_date_selection() # Go back
            return

        self.selected_class_id = self.class_data[selected_class_name]
        self.attendance_date_str = self.selected_date.get()

        # Create the attendance sheet frame and treeview
        self.attendance_sheet_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        sheet_header = tk.Frame(self.attendance_sheet_frame, bg="#e0e0e0")
        sheet_header.pack(fill=tk.X, pady=5)
        tk.Label(sheet_header, text=f"Attendance for {selected_class_name} on {self.attendance_date_str}", font=("Arial", 14, "bold"), bg="#e0e0e0").pack(side=tk.LEFT, padx=10)
        tk.Button(sheet_header, text="Save Attendance", command=self.save_attendance, font=("Arial", 10, "bold")).pack(side=tk.RIGHT, padx=10)
        tk.Button(sheet_header, text="Back to Selection", command=self.show_date_selection, font=("Arial", 10)).pack(side=tk.RIGHT, padx=5)

        tree_frame = tk.Frame(self.attendance_sheet_frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)

        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('student_id', 'name', 'status')
        self.attendance_tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set, selectmode="browse")
        self.attendance_tree.heading('student_id', text='ID')
        self.attendance_tree.heading('name', text='Student Name')
        self.attendance_tree.heading('status', text='Status')
        self.attendance_tree.column('student_id', width=60, anchor=tk.CENTER)
        self.attendance_tree.column('name', width=200)
        self.attendance_tree.column('status', width=120)
        tree_scroll.config(command=self.attendance_tree.yview)
        self.attendance_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        self.load_students_for_attendance(self.selected_class_id, self.attendance_date_str)

    def load_students_for_attendance(self, class_id, attendance_date):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT s.id, s.name FROM students s WHERE s.class_id = ? ORDER BY s.name", (class_id,))
        students = cursor.fetchall()

        # Get existing attendance for the date
        cursor.execute("SELECT student_id, status FROM attendance WHERE attendance_date = ? AND student_id IN ({})".format(
            ','.join('?' for _ in [s[0] for s in students])
        ), [attendance_date] + [s[0] for s in students])
        existing_attendance = {row[0]: row[1] for row in cursor.fetchall()}

        conn.close()

        statuses = ["Present", "Absent", "Late", "Leave"]
        status_vars = {}

        for student_id, name in students:
            current_status = existing_attendance.get(student_id, "Present") # Default to Present if not marked
            status_vars[student_id] = tk.StringVar(value=current_status)

            # Create radio buttons for status
            radio_frame = tk.Frame(self.attendance_tree, bg="white")
            for i, status in enumerate(statuses):
                rb = tk.Radiobutton(radio_frame, text=status, variable=status_vars[student_id], value=status, bg="white")
                rb.pack(side=tk.LEFT, padx=2)
            
            self.attendance_tree.insert('', tk.END, values=(student_id, name, radio_frame))
            # Store the StringVar associated with each student
            self.attendance_tree.custom_widgets = getattr(self.attendance_tree, 'custom_widgets', {})
            self.attendance_tree.custom_widgets[student_id] = status_vars[student_id]


    def save_attendance(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        saved_count = 0
        error_count = 0

        for item_id in self.attendance_tree.get_children():
            student_id = self.attendance_tree.item(item_id, 'values')[0]
            status_var = self.attendance_tree.custom_widgets.get(int(student_id))
            if status_var:
                status = status_var.get()
                try:
                    # Check if attendance already exists for this date and student
                    cursor.execute("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ?", (student_id, self.attendance_date_str))
                    existing_record = cursor.fetchone()

                    if existing_record:
                        cursor.execute("UPDATE attendance SET status = ? WHERE id = ?", (status, existing_record[0]))
                    else:
                        cursor.execute("INSERT INTO attendance (student_id, attendance_date, status) VALUES (?, ?, ?)",
                                       (student_id, self.attendance_date_str, status))
                    saved_count += 1
                except Exception as e:
                    print(f"Error saving attendance for student {student_id}: {e}")
                    error_count += 1

        conn.commit()
        conn.close()

        if error_count == 0:
            messagebox.showinfo("Success", f"Attendance for {self.attendance_date_str} saved successfully. ({saved_count} records updated/inserted)")
        else:
            messagebox.showwarning("Partial Success", f"Attendance saved with errors. {saved_count} records updated/inserted, {error_count} errors.")

    def show_date_selection(self):
        self.current_view = "select_date"
        self.header_label.config(text="Attendance Management")
        self.attendance_sheet_frame.pack_forget()
        self.date_selection_frame.pack(pady=20)
        self.load_classes_for_selection() # Reload in case classes were added/deleted


class ExamView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.selected_exam_id = None
        self.selected_student_id = None

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        self.create_widgets()
        self.load_exams()

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="Exams & Marks Management", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        # Exam Setup Frame
        exam_setup_frame = tk.LabelFrame(self.main_frame, text="Exam Setup", padx=10, pady=10, bg="white", font=("Arial", 12))
        exam_setup_frame.pack(fill=tk.X, pady=10)
        tk.Label(exam_setup_frame, text="Exam Name:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        self.exam_name_entry = tk.Entry(exam_setup_frame, width=30, font=("Arial", 11))
        self.exam_name_entry.pack(side=tk.LEFT, padx=5)
        tk.Label(exam_setup_frame, text="Date:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        self.exam_date_entry = tk.Entry(exam_setup_frame, width=15, font=("Arial", 11))
        self.exam_date_entry.pack(side=tk.LEFT, padx=5)
        try:
            from tkcalendar import DateEntry
            self.cal_button_exam = tk.Button(exam_setup_frame, text="📅", command=lambda: self.pick_date(self.exam_date_entry), font=("Arial", 10))
            self.cal_button_exam.pack(side=tk.LEFT)
        except ImportError:
            self.cal_button_exam = None

        tk.Button(exam_setup_frame, text="Add Exam", command=self.add_exam, font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(exam_setup_frame, text="Refresh Exams", command=self.load_exams, font=("Arial", 10)).pack(side=tk.LEFT, padx=5)

        # Exam List Frame
        exam_list_frame = tk.Frame(self.main_frame, bg="white", bd=2, relief=tk.GROOVE)
        exam_list_frame.pack(fill=tk.X, pady=10)
        exam_scroll = tk.Scrollbar(exam_list_frame)
        exam_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('exam_id', 'exam_name', 'exam_date', 'actions')
        self.exams_tree = ttk.Treeview(exam_list_frame, columns=columns, show='headings', yscrollcommand=exam_scroll.set, selectmode="browse")
        self.exams_tree.heading('exam_id', text='ID')
        self.exams_tree.heading('exam_name', text='Exam Name')
        self.exams_tree.heading('exam_date', text='Date')
        self.exams_tree.heading('actions', text='Actions')
        self.exams_tree.column('exam_id', width=50, anchor=tk.CENTER)
        self.exams_tree.column('exam_name', width=200)
        self.exams_tree.column('exam_date', width=100)
        self.exams_tree.column('actions', width=100, anchor=tk.CENTER)
        exam_scroll.config(command=self.exams_tree.yview)
        self.exams_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.exams_tree.bind('<<TreeviewSelect>>', self.on_exam_select)

        # Marks Entry Frame
        self.marks_entry_frame = tk.LabelFrame(self.main_frame, text="Enter Marks", padx=10, pady=10, bg="white", font=("Arial", 12))
        self.marks_entry_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        self.create_marks_entry_widgets()

    def pick_date(self, entry_widget):
         if self.cal_button_exam:
            from tkcalendar import DateEntry
            top = tk.Toplevel(self)
            cal = DateEntry(top, width=12, background='darkblue', foreground='white', borderwidth=2, date_pattern='yyyy-mm-dd')
            cal.pack(padx=10, pady=10)
            def set_date():
                entry_widget.delete(0, tk.END)
                entry_widget.insert(0, cal.get_date())
                top.destroy()
            tk.Button(top, text="Select", command=set_date).pack(pady=5)
            top.transient(self)
            top.grab_set()
            top.mainloop()

    def load_exams(self):
        for item in self.exams_tree.get_children():
            self.exams_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name, date FROM exams ORDER BY date DESC")
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            exam_id, exam_name, exam_date = row
            btn_frame = tk.Frame(self.exams_tree, bg="white")
            btn_edit = tk.Button(btn_frame, text="Edit", width=4, command=lambda i=exam_id, n=exam_name, d=exam_date: self.edit_exam(i, n, d), font=("Arial", 8))
            btn_edit.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=4, command=lambda i=exam_id: self.delete_exam(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            btn_marks = tk.Button(btn_frame, text="Marks", width=5, command=lambda i=exam_id: self.load_marks_for_exam(i), font=("Arial", 8))
            btn_marks.pack(side=tk.LEFT, padx=2)
            self.exams_tree.insert('', tk.END, values=(exam_id, exam_name, exam_date, btn_frame))
            self.exams_tree.custom_widgets = getattr(self.exams_tree, 'custom_widgets', {})
            self.exams_tree.custom_widgets[exam_id] = btn_frame


    def add_exam(self):
        exam_name = self.exam_name_entry.get().strip()
        exam_date = self.exam_date.get().strip()
        if not exam_name or not exam_date:
            messagebox.showwarning("Input Error", "Exam name and date are required.")
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("INSERT INTO exams (name, date) VALUES (?, ?)", (exam_name, exam_date))
            conn.commit()
            messagebox.showinfo("Success", f"Exam '{exam_name}' added successfully.")
            self.exam_name_entry.delete(0, tk.END)
            self.exam_date.delete(0, tk.END)
            self.load_exams()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def edit_exam(self, exam_id, old_name, old_date):
        # Simple dialog for editing
        edit_win = tk.Toplevel(self)
        edit_win.title("Edit Exam")
        edit_win.geometry("350x150")
        edit_win.transient(self)
        edit_win.grab_set()

        tk.Label(edit_win, text="Exam Name:", font=("Arial", 11)).pack(pady=5)
        name_entry = tk.Entry(edit_win, font=("Arial", 11))
        name_entry.insert(0, old_name)
        name_entry.pack(pady=2)

        tk.Label(edit_win, text="Date:", font=("Arial", 11)).pack(pady=5)
        date_entry = tk.Entry(edit_win, font=("Arial", 11))
        date_entry.insert(0, old_date)
        date_entry.pack(pady=2)
        try:
            from tkcalendar import DateEntry
            cal_btn = tk.Button(edit_win, text="📅", command=lambda: self.pick_date(date_entry), font=("Arial", 10))
            cal_btn.pack(pady=2)
        except ImportError:
            pass

        def save_changes():
            new_name = name_entry.get().strip()
            new_date = date_entry.get().strip()
            if not new_name or not new_date:
                messagebox.showwarning("Input Error", "Exam name and date are required.", parent=edit_win)
                return
            
            conn = get_db_connection()
            cursor = conn.cursor()
            try:
                cursor.execute("UPDATE exams SET name = ?, date = ? WHERE id = ?", (new_name, new_date, exam_id))
                conn.commit()
                messagebox.showinfo("Success", "Exam updated successfully.", parent=edit_win)
                edit_win.destroy()
                self.load_exams()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}", parent=edit_win)
            finally:
                conn.close()

        tk.Button(edit_win, text="Save Changes", command=save_changes, font=("Arial", 10, "bold")).pack(pady=10)


    def delete_exam(self, exam_id):
        if not messagebox.askyesno("Confirm Delete", "Deleting an exam will also delete all associated marks. Are you sure?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM marks WHERE exam_id = ?", (exam_id,))
            cursor.execute("DELETE FROM exams WHERE id = ?", (exam_id,))
            conn.commit()
            messagebox.showinfo("Success", "Exam and its marks deleted successfully.")
            self.load_exams()
            # Clear marks entry if the deleted exam was selected
            if self.selected_exam_id == exam_id:
                self.clear_marks_entry()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def on_exam_select(self, event=None):
        selected_items = self.exams_tree.selection()
        if selected_items:
            item = self.exams_tree.item(selected_items[0])
            self.selected_exam_id = item['values'][0]
            self.load_marks_for_exam(self.selected_exam_id)
        else:
            self.selected_exam_id = None
            self.clear_marks_entry()

    def create_marks_entry_widgets(self):
        self.marks_tree_frame = tk.Frame(self.marks_entry_frame, bg="white", bd=2, relief=tk.GROOVE)
        self.marks_tree_frame.pack(fill=tk.BOTH, expand=True, pady=10)

        marks_scroll = tk.Scrollbar(self.marks_tree_frame)
        marks_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('student_id', 'student_name', 'subject', 'marks_obtained', 'total_marks')
        self.marks_tree = ttk.Treeview(self.marks_tree_frame, columns=columns, show='headings', yscrollcommand=marks_scroll.set, selectmode="browse")
        self.marks_tree.heading('student_id', text='ID')
        self.marks_tree.heading('student_name', text='Student Name')
        self.marks_tree.heading('subject', text='Subject')
        self.marks_tree.heading('marks_obtained', text='Marks Obtained')
        self.marks_tree.heading('total_marks', text='Total Marks')
        self.marks_tree.column('student_id', width=60, anchor=tk.CENTER)
        self.marks_tree.column('student_name', width=180)
        self.marks_tree.column('subject', width=150)
        self.marks_tree.column('marks_obtained', width=100, anchor=tk.CENTER)
        self.marks_tree.column('total_marks', width=100, anchor=tk.CENTER)
        marks_scroll.config(command=self.marks_tree.yview)
        self.marks_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        # Controls for saving marks
        save_frame = tk.Frame(self.marks_entry_frame, bg="white")
        save_frame.pack(fill=tk.X, pady=5)
        tk.Button(save_frame, text="Save Marks", command=self.save_marks, font=("Arial", 10, "bold")).pack(side=tk.RIGHT)
        tk.Button(save_frame, text="Calculate Grades", command=self.calculate_grades, font=("Arial", 10)).pack(side=tk.RIGHT, padx=5)

    def load_marks_for_exam(self, exam_id):
        self.selected_exam_id = exam_id
        self.clear_marks_entry() # Clear previous marks
        if not exam_id: return

        conn = get_db_connection()
        cursor = conn.cursor()

        # Get exam details
        cursor.execute("SELECT name FROM exams WHERE id = ?", (exam_id,))
        exam_name = cursor.fetchone()[0] if cursor.rowcount > 0 else "Unknown Exam"
        self.marks_entry_frame.config(text=f"Enter Marks for: {exam_name}")

        # Get subjects for this exam (assuming subjects are consistent across exams for now, or need linking)
        # Let's get all subjects available
        cursor.execute("SELECT id, name FROM subjects ORDER BY name")
        subjects = {row[0]: row[1] for row in cursor.fetchall()}

        # Get all students
        cursor.execute("SELECT id, name, class_id FROM students ORDER BY name")
        students = {row[0]: {'name': row[1], 'class_id': row[2]} for row in cursor.fetchall()}

        # Get existing marks for this exam
        cursor.execute("""
            SELECT m.student_id, m.subject_id, m.marks_obtained, m.total_marks
            FROM marks m
            WHERE m.exam_id = ?
        """, (exam_id,))
        existing_marks = {}
        for row in cursor.fetchall():
            existing_marks[row[0]] = {'subject_id': row[1], 'obtained': row[2], 'total': row[3]}

        student_vars = {} # To hold Entry widgets for marks

        for student_id, student_info in students.items():
            student_name = student_info['name']
            marks_data = existing_marks.get(student_id, {})
            subject_id = marks_data.get('subject_id')
            obtained = marks_data.get('obtained')
            total = marks_data.get('total')

            # Get the subject name, handle cases where subject might be missing or changed
            subject_name = subjects.get(subject_id, "N/A") if subject_id else "Select Subject"

            # Create input fields
            student_vars[student_id] = {}

            # Subject Selection
            subject_combo_var = tk.StringVar()
            if subject_id: subject_combo_var.set(subject_name)
            subject_combo = ttk.Combobox(self.marks_tree, textvariable=subject_combo_var, values=list(subjects.values()), state="readonly", width=15)
            subject_combo.grid(row=self.marks_tree.index(self.marks_tree.insert('', tk.END)), column=2, padx=5, pady=2, sticky=tk.EW)
            student_vars[student_id]['subject_combo'] = subject_combo
            student_vars[student_id]['subject_id'] = subject_id # Store current subject ID

            # Marks Obtained Entry
            obtained_var = tk.StringVar()
            if obtained is not None: obtained_var.set(str(obtained))
            obtained_entry = tk.Entry(self.marks_tree, textvariable=obtained_var, width=8, font=("Arial", 10))
            obtained_entry.grid(row=self.marks_tree.index(self.marks_tree.insert('', tk.END)), column=3, padx=5, pady=2)
            student_vars[student_id]['obtained_entry'] = obtained_entry

            # Total Marks Entry
            total_var = tk.StringVar()
            if total is not None: total_var.set(str(total))
            total_entry = tk.Entry(self.marks_tree, textvariable=total_var, width=8, font=("Arial", 10))
            total_entry.grid(row=self.marks_tree.index(self.marks_tree.insert('', tk.END)), column=4, padx=5, pady=2)
            student_vars[student_id]['total_entry'] = total_entry

            # Insert student row
            self.marks_tree.insert('', tk.END, values=(student_id, student_name, subject_combo, obtained_entry, total_entry))


        self.student_vars = student_vars # Store for saving
        conn.close()

    def clear_marks_entry(self):
        for item in self.marks_tree.get_children():
            self.marks_tree.delete(item)
        self.student_vars = {}
        self.marks_entry_frame.config(text="Enter Marks")


    def save_marks(self):
        if not self.selected_exam_id:
            messagebox.showwarning("Selection Error", "Please select an exam first.")
            return
        if not self.student_vars:
            messagebox.showwarning("No Data", "No students found or loaded for this exam.")
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        updated_count = 0

        # Get mapping of subject names to IDs again to handle changes
        conn_sub = get_db_connection()
        cursor_sub = conn_sub.cursor()
        cursor_sub.execute("SELECT name, id FROM subjects")
        subject_name_to_id = {name: id for name, id in cursor_sub.fetchall()}
        conn_sub.close()

        for student_id, widgets in self.student_vars.items():
            subject_combo = widgets['subject_combo']
            obtained_entry = widgets['obtained_entry']
            total_entry = widgets['total_entry']

            subject_name = subject_combo.get()
            marks_obtained_str = obtained_entry.get().strip()
            total_marks_str = total_entry.get().strip()

            subject_id = subject_name_to_id.get(subject_name)

            # Validate inputs
            if not subject_id:
                continue # Skip if subject is not selected or invalid
            
            marks_obtained = None
            if marks_obtained_str:
                try:
                    marks_obtained = float(marks_obtained_str)
                except ValueError:
                    messagebox.showwarning("Input Error", f"Invalid marks format for student {student_id}.")
                    continue # Skip this student

            total_marks = None
            if total_marks_str:
                try:
                    total_marks = float(total_marks_str)
                except ValueError:
                     messagebox.showwarning("Input Error", f"Invalid total marks format for student {student_id}.")
                     continue # Skip this student

            # Check if marks already exist for this student and exam
            cursor.execute("SELECT id FROM marks WHERE exam_id = ? AND student_id = ? AND subject_id = ?",
                           (self.selected_exam_id, student_id, subject_id))
            existing_mark_record = cursor.fetchone()

            try:
                if existing_mark_record:
                    # Update existing record
                    cursor.execute("""
                        UPDATE marks SET marks_obtained = ?, total_marks = ? WHERE id = ?
                    """, (marks_obtained, total_marks, existing_mark_record[0]))
                else:
                    # Insert new record
                    cursor.execute("""
                        INSERT INTO marks (student_id, exam_id, subject_id, marks_obtained, total_marks)
                        VALUES (?, ?, ?, ?, ?)
                    """, (student_id, self.selected_exam_id, subject_id, marks_obtained, total_marks))
                updated_count += 1
            except Exception as e:
                print(f"Error saving marks for student {student_id}, subject {subject_id}: {e}")

        conn.commit()
        conn.close()
        messagebox.showinfo("Success", f"{updated_count} mark entries saved/updated.")


    def calculate_grades(self):
        if not self.selected_exam_id:
            messagebox.showwarning("Selection Error", "Please select an exam first.")
            return
        if not self.student_vars:
            messagebox.showwarning("No Data", "No students found or loaded for this exam.")
            return

        # Update marks_obtained and total_marks fields in the UI based on calculations
        # (e.g., if subjects were added/removed, recalculate totals)
        # For now, assume the UI fields are the source of truth for saving.
        # This function is more for demonstrating potential grade calculation based on marks.
        
        # Example: Calculate Percentage and Grade and display them (could add columns to treeview)
        
        messagebox.showinfo("Info", "Grade calculation logic can be added here based on the entered marks and predefined grading scales.")
        # For now, just save the marks if they are entered correctly.

class FeesView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.selected_student_for_fee = None
        self.selected_fee_collection_id = None

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        self.create_widgets()
        self.load_fee_structures()
        self.load_fee_collections()
        self.load_students_for_fee_collection()


    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="Financial Management - Fees", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        # Fee Structure Management
        structure_frame = tk.LabelFrame(self.main_frame, text="Fee Structures", padx=10, pady=10, bg="white", font=("Arial", 12))
        structure_frame.pack(fill=tk.X, pady=10)

        tk.Label(structure_frame, text="Class:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.structure_class_combo_var = tk.StringVar()
        self.structure_class_combo = ttk.Combobox(structure_frame, textvariable=self.structure_class_combo_var, state="readonly", width=25)
        self.structure_class_combo.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)
        self.structure_class_combo.bind("<<ComboboxSelected>>", self.on_structure_class_select)

        tk.Label(structure_frame, text="Fee Type:", font=("Arial", 11)).grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        self.structure_fee_type_entry = tk.Entry(structure_frame, width=30, font=("Arial", 11))
        self.structure_fee_type_entry.grid(row=1, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(structure_frame, text="Amount:", font=("Arial", 11)).grid(row=2, column=0, padx=5, pady=5, sticky=tk.W)
        self.structure_amount_entry = tk.Entry(structure_frame, width=30, font=("Arial", 11))
        self.structure_amount_entry.grid(row=2, column=1, padx=5, pady=5, sticky=tk.EW)
        
        tk.Label(structure_frame, text="Category:", font=("Arial", 11)).grid(row=3, column=0, padx=5, pady=5, sticky=tk.W)
        self.structure_category_entry = tk.Entry(structure_frame, width=30, font=("Arial", 11))
        self.structure_category_entry.grid(row=3, column=1, padx=5, pady=5, sticky=tk.EW)

        structure_frame.grid_columnconfigure(1, weight=1)

        tk.Button(structure_frame, text="Add/Update Fee", command=self.save_fee_structure, font=("Arial", 10)).grid(row=4, column=0, columnspan=2, pady=10)
        tk.Button(structure_frame, text="Refresh", command=self.load_fee_structures, font=("Arial", 10)).grid(row=5, column=0, columnspan=2, pady=5)

        structure_list_frame = tk.Frame(structure_frame, bg="white", bd=2, relief=tk.GROOVE)
        structure_list_frame.grid(row=0, column=2, rowspan=6, padx=10, pady=5, sticky=tk.NSEW)
        structure_frame.grid_columnconfigure(2, weight=1)
        structure_frame.grid_rowconfigure(0, weight=1)

        struct_scroll = tk.Scrollbar(structure_list_frame)
        struct_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('structure_id', 'class_name', 'category', 'fee_type', 'amount', 'actions')
        self.structure_tree = ttk.Treeview(structure_list_frame, columns=columns, show='headings', yscrollcommand=struct_scroll.set, selectmode="browse")
        self.structure_tree.heading('structure_id', text='ID')
        self.structure_tree.heading('class_name', text='Class')
        self.structure_tree.heading('category', text='Category')
        self.structure_tree.heading('fee_type', text='Fee Type')
        self.structure_tree.heading('amount', text='Amount')
        self.structure_tree.heading('actions', text='Actions')
        self.structure_tree.column('structure_id', width=50, anchor=tk.CENTER)
        self.structure_tree.column('class_name', width=100)
        self.structure_tree.column('category', width=100)
        self.structure_tree.column('fee_type', width=150)
        self.structure_tree.column('amount', width=80, anchor=tk.CENTER)
        self.structure_tree.column('actions', width=80, anchor=tk.CENTER)
        struct_scroll.config(command=self.structure_tree.yview)
        self.structure_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.structure_tree.bind('<<TreeviewSelect>>', self.on_structure_select)


        # Fee Collection Section
        collection_frame = tk.LabelFrame(self.main_frame, text="Fee Collection", padx=10, pady=10, bg="white", font=("Arial", 12))
        collection_frame.pack(fill=tk.BOTH, expand=True, pady=10)

        # Student Search for Collection
        student_search_frame = tk.Frame(collection_frame, bg="white")
        student_search_frame.pack(fill=tk.X)
        tk.Label(student_search_frame, text="Select Student:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        self.student_combo_var = tk.StringVar()
        self.student_combo = ttk.Combobox(student_search_frame, textvariable=self.student_combo_var, state="readonly", width=40)
        self.student_combo.pack(side=tk.LEFT, padx=5, expand=True, fill=tk.X)
        tk.Button(student_search_frame, text="Load Details", command=self.load_student_fee_details, font=("Arial", 10)).pack(side=tk.LEFT, padx=5)

        # Fee Details Frame
        self.fee_details_frame = tk.Frame(collection_frame, bg="white")
        self.fee_details_frame.pack(fill=tk.X, pady=10)

        # Fee Collection Form
        collection_form_frame = tk.Frame(self.fee_details_frame, bg="white")
        collection_form_frame.pack(fill=tk.X)

        tk.Label(collection_form_frame, text="Fee Type:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.collection_fee_type_combo_var = tk.StringVar()
        self.collection_fee_type_combo = ttk.Combobox(collection_form_frame, textvariable=self.collection_fee_type_combo_var, state="readonly", width=25)
        self.collection_fee_type_combo.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(collection_form_frame, text="Amount Paid:", font=("Arial", 11)).grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        self.collection_amount_paid_entry = tk.Entry(collection_form_frame, width=25, font=("Arial", 11))
        self.collection_amount_paid_entry.grid(row=1, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(collection_form_frame, text="Fine Paid:", font=("Arial", 11)).grid(row=2, column=0, padx=5, pady=5, sticky=tk.W)
        self.collection_fine_paid_entry = tk.Entry(collection_form_frame, width=25, font=("Arial", 11))
        self.collection_fine_paid_entry.grid(row=2, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(collection_form_frame, text="Discount:", font=("Arial", 11)).grid(row=3, column=0, padx=5, pady=5, sticky=tk.W)
        self.collection_discount_entry = tk.Entry(collection_form_frame, width=25, font=("Arial", 11))
        self.collection_discount_entry.grid(row=3, column=1, padx=5, pady=5, sticky=tk.EW)

        collection_form_frame.grid_columnconfigure(1, weight=1)

        tk.Button(collection_form_frame, text="Collect Fee", command=self.collect_fee, font=("Arial", 10, "bold")).grid(row=4, column=0, columnspan=2, pady=15)

        # Pending Fees List
        pending_frame = tk.LabelFrame(self.fee_details_frame, text="Student's Fee Dues", padx=10, pady=10, bg="white", font=("Arial", 11))
        pending_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        pending_scroll = tk.Scrollbar(pending_frame)
        pending_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('due_id', 'fee_type', 'amount_due', 'actions')
        self.pending_tree = ttk.Treeview(pending_frame, columns=columns, show='headings', yscrollcommand=pending_scroll.set, selectmode="browse")
        self.pending_tree.heading('due_id', text='ID')
        self.pending_tree.heading('fee_type', text='Fee Type')
        self.pending_tree.heading('amount_due', text='Amount Due')
        self.pending_tree.heading('actions', text='Actions')
        self.pending_tree.column('due_id', width=50, anchor=tk.CENTER)
        self.pending_tree.column('fee_type', width=200)
        self.pending_tree.column('amount_due', width=100, anchor=tk.CENTER)
        self.pending_tree.column('actions', width=80, anchor=tk.CENTER)
        pending_scroll.config(command=self.pending_tree.yview)
        self.pending_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        # Recent Fee Collections List
        collections_list_frame = tk.LabelFrame(self.main_frame, text="Recent Fee Collections", padx=10, pady=10, bg="white", font=("Arial", 12))
        collections_list_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        
        collections_scroll = tk.Scrollbar(collections_list_frame)
        collections_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('collection_id', 'student_name', 'fee_type', 'amount_paid', 'fine', 'discount', 'date', 'receipt', 'actions')
        self.collections_tree = ttk.Treeview(collections_list_frame, columns=columns, show='headings', yscrollcommand=collections_scroll.set, selectmode="browse")
        self.collections_tree.heading('collection_id', text='ID')
        self.collections_tree.heading('student_name', text='Student')
        self.collections_tree.heading('fee_type', text='Fee Type')
        self.collections_tree.heading('amount_paid', text='Paid')
        self.collections_tree.heading('fine', text='Fine')
        self.collections_tree.heading('discount', text='Disc.')
        self.collections_tree.heading('date', text='Date')
        self.collections_tree.heading('receipt', text='Receipt #')
        self.collections_tree.heading('actions', text='Actions')
        self.collections_tree.column('collection_id', width=50, anchor=tk.CENTER)
        self.collections_tree.column('student_name', width=150)
        self.collections_tree.column('fee_type', width=120)
        self.collections_tree.column('amount_paid', width=70, anchor=tk.CENTER)
        self.collections_tree.column('fine', width=60, anchor=tk.CENTER)
        self.collections_tree.column('discount', width=60, anchor=tk.CENTER)
        self.collections_tree.column('date', width=90)
        self.collections_tree.column('receipt', width=100)
        self.collections_tree.column('actions', width=80, anchor=tk.CENTER)
        collections_scroll.config(command=self.collections_tree.yview)
        self.collections_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.collections_tree.bind('<<TreeviewSelect>>', self.on_collection_select)

        tk.Button(collections_list_frame, text="Refresh Collections", command=self.load_fee_collections, font=("Arial", 10)).pack(pady=5)


    def load_classes_for_structure(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM classes ORDER BY name")
        rows = cursor.fetchall()
        conn.close()
        self.class_data = {row[1]: row[0] for row in rows} # Name: ID mapping
        self.structure_class_combo['values'] = ["All Classes"] + list(self.class_data.keys())
        if rows:
            self.structure_class_combo.set("All Classes")
        else:
             self.structure_class_combo.set("")


    def load_fee_structures(self):
        for item in self.structure_tree.get_children():
            self.structure_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        query = """
            SELECT fs.id, COALESCE(c.name, 'N/A'), fs.category, fs.fee_type, fs.amount
            FROM fee_structures fs
            LEFT JOIN classes c ON fs.class_id = c.id
            ORDER BY c.name, fs.fee_type
        """
        cursor.execute(query)
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            struct_id, class_name, category, fee_type, amount = row
            btn_frame = tk.Frame(self.structure_tree, bg="white")
            btn_edit = tk.Button(btn_frame, text="Edit", width=4, command=lambda i=struct_id, cn=class_name, cat=category, ft=fee_type, am=amount: self.edit_fee_structure(i, cn, cat, ft, am), font=("Arial", 8))
            btn_edit.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=4, command=lambda i=struct_id: self.delete_fee_structure(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            self.structure_tree.insert('', tk.END, values=(struct_id, class_name, category, fee_type, format_currency(amount), btn_frame))
            self.structure_tree.custom_widgets = getattr(self.structure_tree, 'custom_widgets', {})
            self.structure_tree.custom_widgets[struct_id] = btn_frame
        self.load_classes_for_structure() # Reload class dropdown


    def load_students_for_fee_collection(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM students ORDER BY name")
        students = cursor.fetchall()
        conn.close()
        self.student_data = {row[1]: row[0] for row in students} # Name: ID mapping
        self.student_combo['values'] = list(self.student_data.keys())
        if students:
            self.student_combo.set(students[0][1])


    def load_fee_collections(self):
        for item in self.collections_tree.get_children():
            self.collections_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        query = """
            SELECT fc.id, s.name, fc.fee_type, fc.amount_paid, fc.fine_paid, fc.discount_applied, fc.payment_date, fc.receipt_number
            FROM fee_collections fc
            JOIN students s ON fc.student_id = s.id
            ORDER BY fc.payment_date DESC
        """
        cursor.execute(query)
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            coll_id, student_name, fee_type, amount_paid, fine, discount, date, receipt = row
            btn_frame = tk.Frame(self.collections_tree, bg="white")
            btn_view = tk.Button(btn_frame, text="View", width=4, command=lambda i=coll_id: self.view_receipt(i), font=("Arial", 8))
            btn_view.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=4, command=lambda i=coll_id: self.delete_fee_collection(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            self.collections_tree.insert('', tk.END, values=(coll_id, student_name, fee_type, format_currency(amount_paid), format_currency(fine), format_currency(discount), date, receipt, btn_frame))


    def save_fee_structure(self):
        class_name = self.structure_class_combo_var.get()
        fee_type = self.structure_fee_type_entry.get().strip()
        amount_str = self.structure_amount_entry.get().strip()
        category = self.structure_category_entry.get().strip()

        if not class_name or class_name == "All Classes": class_id = None
        else: class_id = self.class_data.get(class_name)

        if not fee_type or not amount_str or not class_id and class_name != "All Classes":
            messagebox.showwarning("Input Error", "Class (if not 'All'), Fee Type, and Amount are required.")
            return

        try:
            amount = float(amount_str)
        except ValueError:
            messagebox.showwarning("Input Error", "Amount must be a valid number.")
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            # Check if structure already exists for this class/fee_type/category to update it
            cursor.execute("""
                SELECT id FROM fee_structures WHERE class_id <=> ? AND fee_type = ? AND category = ?
            """, (class_id, fee_type, category))
            existing_id = cursor.fetchone()

            if existing_id:
                cursor.execute("UPDATE fee_structures SET amount = ? WHERE id = ?", (amount, existing_id[0]))
                messagebox.showinfo("Success", "Fee structure updated successfully.")
            else:
                cursor.execute("INSERT INTO fee_structures (class_id, category, fee_type, amount) VALUES (?, ?, ?, ?)",
                               (class_id, category, fee_type, amount))
                messagebox.showinfo("Success", "Fee structure added successfully.")

            conn.commit()
            self.load_fee_structures()
            self.clear_structure_form()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def edit_fee_structure(self, struct_id, class_name, category, fee_type, amount):
        self.structure_class_combo_var.set(class_name if class_name else "All Classes")
        self.structure_fee_type_entry.delete(0, tk.END)
        self.structure_fee_type_entry.insert(0, fee_type)
        self.structure_amount_entry.delete(0, tk.END)
        self.structure_amount_entry.insert(0, str(amount))
        self.structure_category_entry.delete(0, tk.END)
        self.structure_category_entry.insert(0, category)
        # Store the ID to know we are editing
        self.editing_structure_id = struct_id

    def delete_fee_structure(self, struct_id):
        if not messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this fee structure?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM fee_structures WHERE id = ?", (struct_id,))
            conn.commit()
            messagebox.showinfo("Success", "Fee structure deleted successfully.")
            self.load_fee_structures()
            self.clear_structure_form()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def clear_structure_form(self):
        self.structure_class_combo_var.set("All Classes")
        self.structure_fee_type_entry.delete(0, tk.END)
        self.structure_amount_entry.delete(0, tk.END)
        self.structure_category_entry.delete(0, tk.END)
        self.editing_structure_id = None


    def on_structure_class_select(self, event=None):
        # Could be used to filter the structure tree, but currently not implemented
        pass

    def on_structure_select(self, event=None):
        selected_items = self.structure_tree.selection()
        if selected_items:
            item = self.structure_tree.item(selected_items[0])
            self.selected_fee_structure_id = item['values'][0]
            # Optionally load data into the edit form here
        else:
            self.selected_fee_structure_id = None


    def load_student_fee_details(self):
        student_name = self.student_combo_var.get()
        if not student_name or not self.student_data.get(student_name):
            messagebox.showwarning("Selection Error", "Please select a valid student.")
            return
        
        self.selected_student_id = self.student_data[student_name]
        student_class_id = None
        student_category = "" # Default category

        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT class_id, name FROM students WHERE id = ?", (self.selected_student_id,))
        student_info = cursor.fetchone()
        if student_info:
            student_class_id = student_info[0]
            # Assume student's category can be derived or is stored elsewhere; for now, empty.
            # If student_info contained category, use it here.

        # Load available fee types based on student's class or 'All Classes'
        cursor.execute("SELECT fs.id, fs.fee_type, fs.amount, c.name FROM fee_structures fs LEFT JOIN classes c ON fs.class_id = c.id WHERE fs.class_id = ? OR fs.class_id IS NULL ORDER BY fs.class_id DESC, fs.fee_type", (student_class_id,))
        fee_structures = cursor.fetchall()
        conn.close()

        self.collection_fee_type_combo['values'] = [f"{row[1]} ({row[3] if row[3] else 'All'}) - {format_currency(row[2])}" for row in fee_structures]
        self.collection_fee_type_combo.option_map = {f"{row[1]} ({row[3] if row[3] else 'All'}) - {format_currency(row[2])}": {'id': row[0], 'type': row[1], 'amount': row[2], 'class_name': row[3]}}

        # Load pending fees for the student
        self.load_pending_fees(self.selected_student_id)


    def load_pending_fees(self, student_id):
        for item in self.pending_tree.get_children():
            self.pending_tree.delete(item)
        
        conn = get_db_connection()
        cursor = conn.cursor()

        # Get student class and category
        cursor.execute("SELECT class_id FROM students WHERE id = ?", (student_id,))
        student_class_info = cursor.fetchone()
        student_class_id = student_class_info[0] if student_class_info else None
        student_category = "" # Assume empty or get from student record if available

        # Calculate total expected fees for the student
        expected_fees = {}
        cursor.execute("SELECT fee_type, amount FROM fee_structures WHERE class_id = ? OR class_id IS NULL ORDER BY class_id DESC", (student_class_id,))
        for row in cursor.fetchall():
            expected_fees[row[0]] = row[1]

        # Get total fees already collected for each fee type
        collected_fees = {}
        cursor.execute("""
            SELECT fee_type, SUM(amount_paid + fine_paid - discount_applied)
            FROM fee_collections
            WHERE student_id = ?
            GROUP BY fee_type
        """, (student_id,))
        for row in cursor.fetchall():
            collected_fees[row[0]] = row[1] or 0.0

        # Calculate dues
        dues = []
        for fee_type, expected_amount in expected_fees.items():
            collected_amount = collected_fees.get(fee_type, 0.0)
            due_amount = expected_amount - collected_amount
            if due_amount > 0.1: # Consider small floating point differences
                dues.append({'fee_type': fee_type, 'amount': due_amount})

        for i, due in enumerate(dues):
             btn_frame = tk.Frame(self.pending_tree, bg="white")
             # Allow paying a specific due amount
             btn_pay = tk.Button(btn_frame, text="Pay", width=4, command=lambda ft=due['fee_type'], am=due['amount'], sd=student_id: self.prefill_collection_form(ft, am, sd), font=("Arial", 8))
             btn_pay.pack(side=tk.LEFT, padx=2)
             self.pending_tree.insert('', tk.END, values=(i+1, due['fee_type'], format_currency(due['amount']), btn_frame))
        
        if not dues:
             self.pending_tree.insert('', tk.END, values=('N/A', 'No pending fees', '', ''))

        conn.close()

    def prefill_collection_form(self, fee_type, amount_due, student_id):
         self.load_student_fee_details() # Ensure student and fee types are loaded
         
         # Find the correct entry in the combobox for the fee type
         combo_value = ""
         for value, mapped_data in self.collection_fee_type_combo.option_map.items():
             if mapped_data['type'] == fee_type:
                 combo_value = value
                 break
         if combo_value:
            self.collection_fee_type_combo_var.set(combo_value)
            self.collection_amount_paid_entry.delete(0, tk.END)
            self.collection_amount_paid_entry.insert(0, str(round(amount_due, 2))) # Prefill with due amount
            self.collection_fine_paid_entry.delete(0, tk.END)
            self.collection_discount_entry.delete(0, tk.END)
         else:
            messagebox.showwarning("Fee Type Not Found", f"Could not find fee type '{fee_type}' in the available structures.")


    def collect_fee(self):
        student_name = self.student_combo_var.get()
        selected_fee_combo_value = self.collection_fee_type_combo_var.get()
        amount_paid_str = self.collection_amount_paid_entry.get().strip()
        fine_paid_str = self.collection_fine_paid_entry.get().strip()
        discount_str = self.collection_discount_entry.get().strip()

        if not student_name or not selected_fee_combo_value:
            messagebox.showwarning("Input Error", "Please select a student and a fee type.")
            return

        student_id = self.student_data.get(student_name)
        fee_data = self.collection_fee_type_combo.option_map.get(selected_fee_combo_value)

        if not student_id or not fee_data:
            messagebox.showerror("Error", "Invalid student or fee type selected.")
            return

        try:
            amount_paid = float(amount_paid_str) if amount_paid_str else 0.0
            fine_paid = float(fine_paid_str) if fine_paid_str else 0.0
            discount = float(discount_str) if discount_str else 0.0
        except ValueError:
            messagebox.showwarning("Input Error", "Amount paid, fine, and discount must be valid numbers.")
            return
        
        # Basic validation: amount paid should not exceed expected + fine - discount
        # expected_amount = fee_data['amount']
        # if amount_paid + discount > expected_amount + fine_paid + 0.01 : # Allow small tolerance
        #     messagebox.showwarning("Validation Error", "Amount paid exceeds the expected amount plus fines minus discounts.")
        #     return


        receipt_number = create_receipt_number()
        payment_date = datetime.now().strftime('%Y-%m-%d')

        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("""
                INSERT INTO fee_collections (student_id, fee_type, amount_paid, fine_paid, discount_applied, receipt_number, payment_date, collected_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            """, (student_id, fee_data['type'], amount_paid, fine_paid, discount, receipt_number, payment_date, get_setting('current_user') or "Admin")) # Store who collected it
            conn.commit()
            messagebox.showinfo("Success", f"Fee collected successfully. Receipt #{receipt_number}")
            
            self.load_fee_collections()
            self.load_pending_fees(student_id) # Refresh pending fees
            self.clear_collection_form()

        except Exception as e:
            messagebox.showerror("Error", f"An error occurred during fee collection: {e}")
        finally:
            conn.close()


    def delete_fee_collection(self, collection_id):
        if not messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this fee collection record?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            # Get student ID before deleting to refresh their pending fees
            cursor.execute("SELECT student_id FROM fee_collections WHERE id = ?", (collection_id,))
            student_id_result = cursor.fetchone()
            student_id_to_refresh = student_id_result[0] if student_id_result else None

            cursor.execute("DELETE FROM fee_collections WHERE id = ?", (collection_id,))
            conn.commit()
            messagebox.showinfo("Success", "Fee collection record deleted successfully.")
            self.load_fee_collections()
            if student_id_to_refresh:
                self.load_pending_fees(student_id_to_refresh)
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()


    def view_receipt(self, collection_id):
        # Placeholder for generating a printable receipt
        messagebox.showinfo("View Receipt", f"Displaying receipt details for Collection ID: {collection_id}")
        # In a real app, this would open a new window or dialog with formatted receipt data.


    def clear_collection_form(self):
        self.student_combo_var.set("")
        self.collection_fee_type_combo_var.set("")
        self.collection_amount_paid_entry.delete(0, tk.END)
        self.collection_fine_paid_entry.delete(0, tk.END)
        self.collection_discount_entry.delete(0, tk.END)
        # Clear pending fees list
        for item in self.pending_tree.get_children():
            self.pending_tree.delete(item)


    def on_collection_select(self, event=None):
        selected_items = self.collections_tree.selection()
        if selected_items:
            item = self.collections_tree.item(selected_items[0])
            self.selected_fee_collection_id = item['values'][0]
        else:
            self.selected_fee_collection_id = None


class SalaryView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.selected_teacher_id = None

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        self.create_widgets()
        self.load_teachers_for_salary()
        self.load_payslips()

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="Financial Management - Salaries", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        # Salary Structure Management
        structure_frame = tk.LabelFrame(self.main_frame, text="Salary Structure Setup", padx=10, pady=10, bg="white", font=("Arial", 12))
        structure_frame.pack(fill=tk.X, pady=10)

        tk.Label(structure_frame, text="Teacher/Staff:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.salary_teacher_combo_var = tk.StringVar()
        self.salary_teacher_combo = ttk.Combobox(structure_frame, textvariable=self.salary_teacher_combo_var, state="readonly", width=30)
        self.salary_teacher_combo.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)
        self.salary_teacher_combo.bind("<<ComboboxSelected>>", self.on_salary_teacher_select)

        tk.Label(structure_frame, text="Base Salary:", font=("Arial", 11)).grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        self.salary_base_entry = tk.Entry(structure_frame, width=30, font=("Arial", 11))
        self.salary_base_entry.grid(row=1, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(structure_frame, text="Allowances (JSON):", font=("Arial", 11)).grid(row=2, column=0, padx=5, pady=5, sticky=tk.W)
        self.salary_allowances_entry = tk.Entry(structure_frame, width=30, font=("Arial", 11))
        self.salary_allowances_entry.grid(row=2, column=1, padx=5, pady=5, sticky=tk.EW)
        tk.Label(structure_frame, text='(e.g., {"HRA": 1000, "Conveyance": 500})', font=("Arial", 8, "italic"), bg="white").grid(row=2, column=2, sticky=tk.W)

        tk.Label(structure_frame, text="Deductions (JSON):", font=("Arial", 11)).grid(row=3, column=0, padx=5, pady=5, sticky=tk.W)
        self.salary_deductions_entry = tk.Entry(structure_frame, width=30, font=("Arial", 11))
        self.salary_deductions_entry.grid(row=3, column=1, padx=5, pady=5, sticky=tk.EW)
        tk.Label(structure_frame, text='(e.g., {"PF": 200, "Tax": 150})', font=("Arial", 8, "italic"), bg="white").grid(row=3, column=2, sticky=tk.W)

        structure_frame.grid_columnconfigure(1, weight=1)
        tk.Button(structure_frame, text="Save/Update Salary Structure", command=self.save_salary_structure, font=("Arial", 10)).grid(row=4, column=0, columnspan=3, pady=10)
        tk.Button(structure_frame, text="Refresh Structures", command=self.load_teachers_for_salary, font=("Arial", 10)).grid(row=5, column=0, columnspan=3, pady=5)

        # Payslip Generation
        payslip_frame = tk.LabelFrame(self.main_frame, text="Payslip Generation", padx=10, pady=10, bg="white", font=("Arial", 12))
        payslip_frame.pack(fill=tk.X, pady=10)

        tk.Label(payslip_frame, text="Select Month:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5)
        self.payslip_month_var = tk.StringVar()
        self.payslip_month_combo = ttk.Combobox(payslip_frame, textvariable=self.payslip_month_var, values=[datetime(2000, m, 1).strftime('%B') for m in range(1, 13)], state="readonly", width=15)
        self.payslip_month_combo.grid(row=0, column=1, padx=5)
        
        tk.Label(payslip_frame, text="Select Year:", font=("Arial", 11)).grid(row=0, column=2, padx=5)
        current_year = datetime.now().year
        year_values = [str(y) for y in range(current_year - 5, current_year + 6)] # Year range
        self.payslip_year_var = tk.StringVar()
        self.payslip_year_combo = ttk.Combobox(payslip_frame, textvariable=self.payslip_year_var, values=year_values, state="readonly", width=10)
        self.payslip_year_combo.grid(row=0, column=3, padx=5)
        self.payslip_year_var.set(str(current_year))

        tk.Button(payslip_frame, text="Generate Payslip", command=self.generate_payslip, font=("Arial", 10, "bold")).grid(row=1, column=0, columnspan=4, pady=10)

        # Payslip List
        payslip_list_frame = tk.LabelFrame(self.main_frame, text="Generated Payslips", padx=10, pady=10, bg="white", font=("Arial", 12))
        payslip_list_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        
        payslip_scroll = tk.Scrollbar(payslip_list_frame)
        payslip_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('payslip_id', 'teacher_name', 'month', 'year', 'gross', 'net', 'date_gen', 'actions')
        self.payslip_tree = ttk.Treeview(payslip_list_frame, columns=columns, show='headings', yscrollcommand=payslip_scroll.set, selectmode="browse")
        self.payslip_tree.heading('payslip_id', text='ID')
        self.payslip_tree.heading('teacher_name', text='Teacher')
        self.payslip_tree.heading('month', text='Month')
        self.payslip_tree.heading('year', text='Year')
        self.payslip_tree.heading('gross', text='Gross')
        self.payslip_tree.heading('net', text='Net')
        self.payslip_tree.heading('date_gen', text='Generated')
        self.payslip_tree.heading('actions', text='Actions')
        self.payslip_tree.column('payslip_id', width=50, anchor=tk.CENTER)
        self.payslip_tree.column('teacher_name', width=150)
        self.payslip_tree.column('month', width=80)
        self.payslip_tree.column('year', width=60, anchor=tk.CENTER)
        self.payslip_tree.column('gross', width=80, anchor=tk.CENTER)
        self.payslip_tree.column('net', width=80, anchor=tk.CENTER)
        self.payslip_tree.column('date_gen', width=100)
        self.payslip_tree.column('actions', width=80, anchor=tk.CENTER)
        payslip_scroll.config(command=self.payslip_tree.yview)
        self.payslip_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.payslip_tree.bind('<<TreeviewSelect>>', self.on_payslip_select)
        
        tk.Button(payslip_list_frame, text="Refresh Payslips", command=self.load_payslips, font=("Arial", 10)).pack(pady=5)


    def load_teachers_for_salary(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM teachers WHERE role != 'Admin' ORDER BY name") # Exclude admin from salary mgmt
        teachers = cursor.fetchall()
        conn.close()
        self.teacher_data = {row[1]: row[0] for row in teachers} # Name: ID mapping
        self.salary_teacher_combo['values'] = list(self.teacher_data.keys())
        if teachers:
            self.salary_teacher_combo.set(teachers[0][1]) # Select first teacher by default


    def save_salary_structure(self):
        teacher_name = self.salary_teacher_combo_var.get()
        base_salary_str = self.salary_base_entry.get().strip()
        allowances_str = self.salary_allowances_entry.get().strip()
        deductions_str = self.salary_deductions_entry.get().strip()

        if not teacher_name or not base_salary_str:
            messagebox.showwarning("Input Error", "Teacher/Staff and Base Salary are required.")
            return

        teacher_id = self.teacher_data.get(teacher_name)
        if not teacher_id:
            messagebox.showerror("Error", "Invalid teacher selected.")
            return

        try:
            base_salary = float(base_salary_str)
        except ValueError:
            messagebox.showwarning("Input Error", "Base Salary must be a valid number.")
            return

        # Validate JSON strings for allowances and deductions
        try:
            import json
            allowances = json.loads(allowances_str) if allowances_str else {}
            deductions = json.loads(deductions_str) if deductions_str else {}
            # Further validation could check if values are numeric, etc.
        except json.JSONDecodeError:
            messagebox.showwarning("Input Error", "Allowances and Deductions must be valid JSON format.")
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("SELECT id FROM salary_structures WHERE teacher_id = ?", (teacher_id,))
            existing_id = cursor.fetchone()

            if existing_id:
                cursor.execute("""
                    UPDATE salary_structures
                    SET base_salary = ?, allowances = ?, deductions = ?
                    WHERE id = ?
                """, (base_salary, json.dumps(allowances), json.dumps(deductions), existing_id[0]))
            else:
                cursor.execute("""
                    INSERT INTO salary_structures (teacher_id, base_salary, allowances, deductions)
                    VALUES (?, ?, ?, ?)
                """, (teacher_id, base_salary, json.dumps(allowances), json.dumps(deductions)))
            
            conn.commit()
            messagebox.showinfo("Success", "Salary structure saved/updated successfully.")
            self.clear_salary_structure_form()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()


    def on_salary_teacher_select(self, event=None):
        teacher_name = self.salary_teacher_combo_var.get()
        if not teacher_name: return
        teacher_id = self.teacher_data.get(teacher_name)
        if not teacher_id: return

        self.selected_teacher_id = teacher_id
        self.load_teacher_salary_structure(teacher_id)

    def load_teacher_salary_structure(self, teacher_id):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT base_salary, allowances, deductions FROM salary_structures WHERE teacher_id = ?", (teacher_id,))
        row = cursor.fetchone()
        conn.close()

        self.clear_salary_structure_form()
        if row:
            import json
            base_salary, allowances_json, deductions_json = row
            self.salary_base_entry.insert(0, str(base_salary))
            self.salary_allowances_entry.insert(0, json.dumps(json.loads(allowances_json), indent=4))
            self.salary_deductions_entry.insert(0, json.dumps(json.loads(deductions_json), indent=4))


    def clear_salary_structure_form(self):
        self.salary_base_entry.delete(0, tk.END)
        self.salary_allowances_entry.delete(0, tk.END)
        self.salary_deductions_entry.delete(0, tk.END)


    def generate_payslip(self):
        teacher_name = self.salary_teacher_combo_var.get()
        month_name = self.payslip_month_var.get()
        year_str = self.payslip_year_var.get()

        if not teacher_name or not month_name or not year_str:
            messagebox.showwarning("Input Error", "Please select Teacher, Month, and Year.")
            return

        teacher_id = self.teacher_data.get(teacher_name)
        if not teacher_id:
            messagebox.showerror("Error", "Invalid teacher selected.")
            return

        conn = get_db_connection()
        cursor = conn.cursor()

        # Check if payslip already generated for this month/year/teacher
        cursor.execute("SELECT id FROM payslips WHERE teacher_id = ? AND month = ? AND year = ?", (teacher_id, month_name, year_str))
        if cursor.fetchone():
            if not messagebox.askyesno("Duplicate", f"Payslip for {teacher_name} - {month_name} {year_str} already exists. Regenerate?"):
                conn.close()
                return

        # Get salary structure
        cursor.execute("SELECT base_salary, allowances, deductions FROM salary_structures WHERE teacher_id = ?", (teacher_id,))
        structure_row = cursor.fetchone()
        if not structure_row:
            messagebox.showwarning("Salary Structure Missing", f"Salary structure not found for {teacher_name}. Cannot generate payslip.")
            conn.close()
            return
        
        base_salary, allowances_json, deductions_json = structure_row
        import json
        allowances = json.loads(allowances_json) if allowances_json else {}
        deductions = json.loads(deductions_json) if deductions_json else {}

        # Calculate gross pay
        gross_pay = base_salary
        for allowance_name, allowance_amount in allowances.items():
            gross_pay += allowance_amount

        # Calculate total deductions
        total_deductions = 0
        for deduction_name, deduction_amount in deductions.items():
            total_deductions += deduction_amount
        
        # Calculate net pay
        net_pay = gross_pay - total_deductions

        # Save payslip
        try:
            cursor.execute("REPLACE INTO payslips (teacher_id, month, year, gross_pay, net_pay, generated_date) VALUES (?, ?, ?, ?, ?, ?)",
                           (teacher_id, month_name, year_str, gross_pay, net_pay, datetime.now().strftime('%Y-%m-%d')))
            conn.commit()
            messagebox.showinfo("Success", f"Payslip generated successfully for {teacher_name} ({month_name} {year_str}). Gross: {format_currency(gross_pay)}, Net: {format_currency(net_pay)}")
            self.load_payslips()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred while saving payslip: {e}")
        finally:
            conn.close()


    def load_payslips(self):
        for item in self.payslip_tree.get_children():
            self.payslip_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        query = """
            SELECT ps.id, t.name, ps.month, ps.year, ps.gross_pay, ps.net_pay, ps.generated_date
            FROM payslips ps
            JOIN teachers t ON ps.teacher_id = t.id
            ORDER BY ps.year DESC, ps.month DESC, t.name
        """
        cursor.execute(query)
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            ps_id, teacher_name, month, year, gross, net, gen_date = row
            btn_frame = tk.Frame(self.payslip_tree, bg="white")
            btn_print = tk.Button(btn_frame, text="Print", width=4, command=lambda i=ps_id: self.print_payslip(i), font=("Arial", 8))
            btn_print.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=4, command=lambda i=ps_id: self.delete_payslip(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            self.payslip_tree.insert('', tk.END, values=(ps_id, teacher_name, month, year, format_currency(gross), format_currency(net), gen_date, btn_frame))


    def delete_payslip(self, payslip_id):
        if not messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this payslip record?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM payslips WHERE id = ?", (payslip_id,))
            conn.commit()
            messagebox.showinfo("Success", "Payslip deleted successfully.")
            self.load_payslips()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def print_payslip(self, payslip_id):
        # Placeholder for actual printing functionality
        messagebox.showinfo("Print Payslip", f"Printing payslip ID: {payslip_id}")


    def on_payslip_select(self, event=None):
        selected_items = self.payslip_tree.selection()
        if selected_items:
            item = self.payslip_tree.item(selected_items[0])
            self.selected_payslip_id = item['values'][0]
        else:
            self.selected_payslip_id = None


class TransactionView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.selected_transaction_id = None

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        self.create_widgets()
        self.load_transactions()

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="Financial Management - Transactions", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        # Transaction Entry Form
        form_frame = tk.LabelFrame(self.main_frame, text="Record Transaction", padx=10, pady=10, bg="white", font=("Arial", 12))
        form_frame.pack(fill=tk.X, pady=10)

        tk.Label(form_frame, text="Date:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.trans_date_entry = tk.Entry(form_frame, width=15, font=("Arial", 11))
        self.trans_date_entry.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.trans_date_entry.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)
        try:
            from tkcalendar import DateEntry
            self.cal_button_trans = tk.Button(form_frame, text="📅", command=lambda: self.pick_date(self.trans_date_entry), font=("Arial", 10))
            self.cal_button_trans.grid(row=0, column=2)
        except ImportError:
            self.cal_button_trans = None

        tk.Label(form_frame, text="Description:", font=("Arial", 11)).grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        self.trans_desc_entry = tk.Entry(form_frame, width=40, font=("Arial", 11))
        self.trans_desc_entry.grid(row=1, column=1, columnspan=2, padx=5, pady=5, sticky=tk.EW)

        tk.Label(form_frame, text="Type:", font=("Arial", 11)).grid(row=2, column=0, padx=5, pady=5, sticky=tk.W)
        self.trans_type_var = tk.StringVar()
        self.trans_type_combo = ttk.Combobox(form_frame, textvariable=self.trans_type_var, values=["Income", "Expense"], state="readonly", width=15)
        self.trans_type_combo.grid(row=2, column=1, padx=5)
        self.trans_type_var.set("Income")

        tk.Label(form_frame, text="Category:", font=("Arial", 11)).grid(row=3, column=0, padx=5, pady=5, sticky=tk.W)
        self.trans_category_entry = tk.Entry(form_frame, width=40, font=("Arial", 11))
        self.trans_category_entry.grid(row=3, column=1, columnspan=2, padx=5, pady=5, sticky=tk.EW)

        tk.Label(form_frame, text="Amount:", font=("Arial", 11)).grid(row=4, column=0, padx=5, pady=5, sticky=tk.W)
        self.trans_amount_entry = tk.Entry(form_frame, width=20, font=("Arial", 11))
        self.trans_amount_entry.grid(row=4, column=1, padx=5, pady=5, sticky=tk.EW)

        form_frame.grid_columnconfigure(1, weight=1)
        form_frame.grid_columnconfigure(2, weight=0) # Make category/description span more if needed

        tk.Button(form_frame, text="Save Transaction", command=self.save_transaction, font=("Arial", 10, "bold")).grid(row=5, column=0, columnspan=3, pady=15)
        tk.Button(form_frame, text="Refresh List", command=self.load_transactions, font=("Arial", 10)).grid(row=6, column=0, columnspan=3, pady=5)

        # Transaction List
        list_frame = tk.LabelFrame(self.main_frame, text="Transaction History", padx=10, pady=10, bg="white", font=("Arial", 12))
        list_frame.pack(fill=tk.BOTH, expand=True, pady=10)
        
        list_scroll = tk.Scrollbar(list_frame)
        list_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('trans_id', 'date', 'description', 'type', 'category', 'amount', 'actions')
        self.trans_tree = ttk.Treeview(list_frame, columns=columns, show='headings', yscrollcommand=list_scroll.set, selectmode="browse")
        self.trans_tree.heading('trans_id', text='ID')
        self.trans_tree.heading('date', text='Date')
        self.trans_tree.heading('description', text='Description')
        self.trans_tree.heading('type', text='Type')
        self.trans_tree.heading('category', text='Category')
        self.trans_tree.heading('amount', text='Amount')
        self.trans_tree.heading('actions', text='Actions')
        self.trans_tree.column('trans_id', width=50, anchor=tk.CENTER)
        self.trans_tree.column('date', width=100)
        self.trans_tree.column('description', width=250)
        self.trans_tree.column('type', width=80)
        self.trans_tree.column('category', width=150)
        self.trans_tree.column('amount', width=90, anchor=tk.CENTER)
        self.trans_tree.column('actions', width=80, anchor=tk.CENTER)
        list_scroll.config(command=self.trans_tree.yview)
        self.trans_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.trans_tree.bind('<<TreeviewSelect>>', self.on_transaction_select)


    def pick_date(self, entry_widget):
         if self.cal_button_trans:
            from tkcalendar import DateEntry
            top = tk.Toplevel(self)
            cal = DateEntry(top, width=12, background='darkblue', foreground='white', borderwidth=2, date_pattern='yyyy-mm-dd')
            cal.pack(padx=10, pady=10)
            def set_date():
                entry_widget.delete(0, tk.END)
                entry_widget.insert(0, cal.get_date())
                top.destroy()
            tk.Button(top, text="Select", command=set_date).pack(pady=5)
            top.transient(self)
            top.grab_set()
            top.mainloop()

    def save_transaction(self):
        trans_date = self.trans_date_entry.get().strip()
        description = self.trans_desc_entry.get().strip()
        trans_type = self.trans_type_var.get()
        category = self.trans_category_entry.get().strip()
        amount_str = self.trans_amount_entry.get().strip()

        if not trans_date or not description or not trans_type or not amount_str:
            messagebox.showwarning("Input Error", "Date, Description, Type, and Amount are required.")
            return
        
        try:
            amount = float(amount_str)
            if trans_type == "Expense" and amount > 0:
                amount = -amount # Store expenses as negative
        except ValueError:
            messagebox.showwarning("Input Error", "Amount must be a valid number.")
            return

        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("INSERT INTO transactions (transaction_date, description, type, category, amount) VALUES (?, ?, ?, ?, ?)",
                           (trans_date, description, trans_type, category, amount))
            conn.commit()
            messagebox.showinfo("Success", "Transaction saved successfully.")
            self.load_transactions()
            self.clear_transaction_form()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def load_transactions(self):
        for item in self.trans_tree.get_children():
            self.trans_tree.delete(item)
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, transaction_date, description, type, category, amount FROM transactions ORDER BY transaction_date DESC, id DESC")
        rows = cursor.fetchall()
        conn.close()
        for row in rows:
            trans_id, date, desc, type, cat, amount = row
            btn_frame = tk.Frame(self.trans_tree, bg="white")
            btn_edit = tk.Button(btn_frame, text="Edit", width=4, command=lambda i=row: self.edit_transaction(i), font=("Arial", 8))
            btn_edit.pack(side=tk.LEFT, padx=2)
            btn_delete = tk.Button(btn_frame, text="Del", width=4, command=lambda i=trans_id: self.delete_transaction(i), font=("Arial", 8), fg="red")
            btn_delete.pack(side=tk.LEFT, padx=2)
            self.trans_tree.insert('', tk.END, values=(trans_id, date, desc, type, cat, format_currency(amount), btn_frame))
            self.trans_tree.custom_widgets = getattr(self.trans_tree, 'custom_widgets', {})
            self.trans_tree.custom_widgets[trans_id] = btn_frame


    def edit_transaction(self, transaction_data):
        trans_id, date, desc, type, cat, amount_str = transaction_data
        
        self.trans_date_entry.delete(0, tk.END)
        self.trans_date_entry.insert(0, date)
        self.trans_desc_entry.delete(0, tk.END)
        self.trans_desc_entry.insert(0, desc)
        self.trans_type_var.set(type)
        self.trans_category_entry.delete(0, tk.END)
        self.trans_category_entry.insert(0, cat)
        self.trans_amount_entry.delete(0, tk.END)
        self.trans_amount_entry.insert(0, str(abs(float(amount_str)))) # Display amount as positive
        
        # Store the ID for updating
        self.selected_transaction_id = trans_id


    def delete_transaction(self, trans_id):
        if not messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this transaction?"):
            return
        conn = get_db_connection()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM transactions WHERE id = ?", (trans_id,))
            conn.commit()
            messagebox.showinfo("Success", "Transaction deleted successfully.")
            self.load_transactions()
            self.clear_transaction_form()
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")
        finally:
            conn.close()

    def clear_transaction_form(self):
        self.trans_date_entry.delete(0, tk.END)
        self.trans_date_entry.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.trans_desc_entry.delete(0, tk.END)
        self.trans_type_var.set("Income")
        self.trans_category_entry.delete(0, tk.END)
        self.trans_amount_entry.delete(0, tk.END)
        self.selected_transaction_id = None


    def on_transaction_select(self, event=None):
        selected_items = self.trans_tree.selection()
        if selected_items:
            item = self.trans_tree.item(selected_items[0])
            # Find the transaction data from the item values
            transaction_data = item['values']
            if transaction_data:
                 self.edit_transaction(transaction_data[:-1]) # Exclude the button frame
        else:
            self.selected_transaction_id = None


class ReportsView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.matplotlib_loaded = False

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)
        self.create_widgets()

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="Reports & Analytics", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        # Report Options Frame
        options_frame = tk.Frame(self.main_frame, bg="white", padx=10, pady=10, relief=tk.GROOVE, bd=2)
        options_frame.pack(fill=tk.X, pady=10)

        tk.Label(options_frame, text="Select Report Type:", font=("Arial", 12)).pack(side=tk.LEFT, padx=5)
        self.report_type_var = tk.StringVar()
        report_options = [
            "Student List", "Teacher List", "Fee Collection Summary",
            "Pending Fees Report", "Attendance Summary", "Exam Results Summary",
            "Income/Expense Report", "Financial Summary"
        ]
        self.report_combo = ttk.Combobox(options_frame, textvariable=self.report_type_var, values=report_options, state="readonly", width=25)
        self.report_combo.pack(side=tk.LEFT, padx=5)
        self.report_combo.bind("<<ComboboxSelected>>", self.on_report_type_select)

        tk.Button(options_frame, text="Generate Report", command=self.generate_report, font=("Arial", 10, "bold")).pack(side=tk.LEFT, padx=10)
        tk.Button(options_frame, text="Print Report", command=self.print_report, font=("Arial", 10)).pack(side=tk.LEFT, padx=5) # Placeholder

        # Report Display Area (Notebook for different report types)
        self.report_notebook = ttk.Notebook(self.main_frame)
        self.report_notebook.pack(fill=tk.BOTH, expand=True, pady=10)

        # Initialize placeholder frames
        self.student_report_frame = tk.Frame(self.report_notebook, bg="white")
        self.report_notebook.add(self.student_report_frame, text="Student List")
        self.teacher_report_frame = tk.Frame(self.report_notebook, bg="white")
        self.report_notebook.add(self.teacher_report_frame, text="Teacher List")
        self.fee_summary_frame = tk.Frame(self.report_notebook, bg="white")
        self.report_notebook.add(self.fee_summary_frame, text="Fee Summary")
        self.attendance_summary_frame = tk.Frame(self.report_notebook, bg="white")
        self.report_notebook.add(self.attendance_summary_frame, text="Attendance Summary")
        self.exam_results_frame = tk.Frame(self.report_notebook, bg="white")
        self.report_notebook.add(self.exam_results_frame, text="Exam Results")
        self.financial_report_frame = tk.Frame(self.report_notebook, bg="white")
        self.report_notebook.add(self.financial_report_frame, text="Financials")
        
        # Try to load matplotlib for charts
        try:
            import matplotlib
            matplotlib.use('TkAgg')
            from matplotlib.backends.backend_tkagg import FigureCanvasTkAgg
            from matplotlib.figure import Figure
            import numpy as np # For sample data
            self.matplotlib_loaded = True
        except ImportError:
            tk.Label(self.main_frame, text="Matplotlib not found. Charts will not be available.", fg="red").pack(pady=10)
            self.matplotlib_loaded = False


    def on_report_type_select(self, event=None):
        report_type = self.report_type_var.get()
        if report_type:
            # Show only the selected tab
            for tab_text in ["Student List", "Teacher List", "Fee Summary", "Attendance Summary", "Exam Results", "Financials"]:
                frame = getattr(self, f"{tab_text.lower().replace(' ', '_').replace('&', '').replace('/', '')}_frame")
                if tab_text == report_type:
                    self.report_notebook.add(frame, text=tab_text) # Re-add to bring to front if needed
                else:
                    self.report_notebook.forget(frame)
            # Handle dynamic creation/population of report widgets based on type


    def generate_report(self):
        report_type = self.report_type_var.get()
        if not report_type:
            messagebox.showwarning("Selection Error", "Please select a report type.")
            return

        # Clear previous report content
        for frame in [self.student_report_frame, self.teacher_report_frame, self.fee_summary_frame, self.attendance_summary_frame, self.exam_results_frame, self.financial_report_frame]:
             for widget in frame.winfo_children():
                 widget.destroy()

        if report_type == "Student List":
            self.generate_student_list_report()
        elif report_type == "Teacher List":
            self.generate_teacher_list_report()
        elif report_type == "Fee Collection Summary":
            self.generate_fee_summary_report()
        elif report_type == "Pending Fees Report":
            self.generate_pending_fees_report()
        elif report_type == "Attendance Summary":
            self.generate_attendance_summary_report()
        elif report_type == "Exam Results Summary":
            self.generate_exam_results_summary_report()
        elif report_type == "Income/Expense Report":
            self.generate_income_expense_report()
        elif report_type == "Financial Summary":
            self.generate_financial_summary_report()


    def generate_student_list_report(self):
        frame = self.student_report_frame
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT s.id, s.name, s.cnic, s.contact, c.name FROM students s LEFT JOIN classes c ON s.class_id = c.id ORDER BY s.name")
        students = cursor.fetchall()
        conn.close()

        if not students:
            tk.Label(frame, text="No students found.", font=("Arial", 12)).pack(pady=20)
            return

        # Treeview for student list
        tree_frame = tk.Frame(frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)
        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('id', 'name', 'cnic', 'contact', 'class_name')
        tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set)
        tree.heading('id', text='ID')
        tree.heading('name', text='Name')
        tree.heading('cnic', text='CNIC')
        tree.heading('contact', text='Contact')
        tree.heading('class_name', text='Class')
        tree.column('id', width=50, anchor=tk.CENTER)
        tree.column('name', width=180)
        tree.column('cnic', width=120)
        tree.column('contact', width=100)
        tree.column('class_name', width=100)
        tree_scroll.config(command=tree.yview)
        tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        for student in students:
            tree.insert('', tk.END, values=student)

        # Add a chart if matplotlib is available
        if self.matplotlib_loaded:
            try:
                classes = {}
                for s in students:
                    class_name = s[4] or "Unknown Class"
                    classes[class_name] = classes.get(class_name, 0) + 1
                
                labels = list(classes.keys())
                sizes = list(classes.values())
                
                fig = Figure(figsize=(5, 3))
                ax = fig.add_subplot(111)
                ax.pie(sizes, labels=labels, autopct='%1.1f%%', startangle=90)
                ax.axis('equal') # Equal aspect ratio ensures that pie is drawn as a circle.
                ax.set_title("Student Distribution by Class")
                
                canvas = FigureCanvasTkAgg(fig, master=frame)
                canvas.get_tk_widget().pack(pady=10)
                canvas.draw()
            except Exception as e:
                tk.Label(frame, text=f"Error generating chart: {e}", fg="red").pack(pady=10)


    def generate_teacher_list_report(self):
        frame = self.teacher_report_frame
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name, contact, subjects, role FROM teachers ORDER BY name")
        teachers = cursor.fetchall()
        conn.close()

        if not teachers:
            tk.Label(frame, text="No teachers found.", font=("Arial", 12)).pack(pady=20)
            return

        # Treeview for teacher list
        tree_frame = tk.Frame(frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)
        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('id', 'name', 'contact', 'subjects', 'role')
        tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set)
        tree.heading('id', text='ID')
        tree.heading('name', text='Name')
        tree.heading('contact', text='Contact')
        tree.heading('subjects', text='Subjects')
        tree.heading('role', text='Role')
        tree.column('id', width=50, anchor=tk.CENTER)
        tree.column('name', width=180)
        tree.column('contact', width=100)
        tree.column('subjects', width=200)
        tree.column('role', width=100)
        tree_scroll.config(command=tree.yview)
        tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        for teacher in teachers:
            subject_names = "N/A"
            if teacher[3]: # Process subject IDs
                subject_ids = [int(sid.strip()) for sid in teacher[3].split(',') if sid.strip().isdigit()]
                if subject_ids:
                    conn_sub = get_db_connection()
                    sub_cursor = conn_sub.cursor()
                    sub_cursor.execute("SELECT name FROM subjects WHERE id IN ({})".format(','.join('?' * len(subject_ids))), subject_ids)
                    subject_names = ", ".join([sub[0] for sub in sub_cursor.fetchall()])
                    conn_sub.close()
            
            tree.insert('', tk.END, values=(teacher[0], teacher[1], teacher[2], subject_names, teacher[4]))

    def generate_fee_summary_report(self):
        frame = self.fee_summary_frame
        
        # Filter options frame
        filters_frame = tk.Frame(frame, bg="white")
        filters_frame.pack(fill=tk.X, pady=10)

        tk.Label(filters_frame, text="Select Month:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        month_var = tk.StringVar()
        month_combo = ttk.Combobox(filters_frame, textvariable=month_var, values=["All Months"] + [datetime(2000, m, 1).strftime('%B') for m in range(1, 13)], state="readonly", width=12)
        month_combo.pack(side=tk.LEFT, padx=5)
        month_var.set("All Months")

        tk.Label(filters_frame, text="Select Year:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        year_var = tk.StringVar()
        current_year = datetime.now().year
        year_values = ["All Years"] + [str(y) for y in range(current_year - 5, current_year + 6)]
        year_combo = ttk.Combobox(filters_frame, textvariable=year_var, values=year_values, state="readonly", width=10)
        year_combo.pack(side=tk.LEFT, padx=5)
        year_var.set("All Years")
        
        tk.Button(filters_frame, text="Filter", command=lambda: self.display_fee_summary(month_var.get(), year_var.get()), font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(filters_frame, text="Refresh", command=lambda: self.display_fee_summary("All Months", "All Years"), font=("Arial", 10)).pack(side=tk.LEFT)

        # Display area for the summary
        self.fee_summary_display_frame = tk.Frame(frame, bg="white")
        self.fee_summary_display_frame.pack(fill=tk.BOTH, expand=True)

        self.display_fee_summary("All Months", "All Years") # Initial load

    def display_fee_summary(self, selected_month, selected_year):
        for widget in self.fee_summary_display_frame.winfo_children():
            widget.destroy()

        conn = get_db_connection()
        cursor = conn.cursor()

        query = """
            SELECT s.name, fc.fee_type, fc.amount_paid, fc.fine_paid, fc.discount_applied, fc.payment_date, fc.receipt_number
            FROM fee_collections fc
            JOIN students s ON fc.student_id = s.id
            WHERE 1=1
        """
        params = []
        if selected_month and selected_month != "All Months":
            query += " AND STRFTIME('%m', fc.payment_date) = ?"
            params.append(f"{datetime.strptime(selected_month, '%B').month:02d}")
        if selected_year and selected_year != "All Years":
            query += " AND STRFTIME('%Y', fc.payment_date) = ?"
            params.append(selected_year)
        
        query += " ORDER BY fc.payment_date DESC"

        cursor.execute(query, params)
        collections = cursor.fetchall()
        conn.close()

        if not collections:
            tk.Label(self.fee_summary_display_frame, text="No fee collections found for the selected criteria.", font=("Arial", 12)).pack(pady=20)
            return

        # Treeview for fee collections
        tree_frame = tk.Frame(self.fee_summary_display_frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)
        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('student_name', 'fee_type', 'paid', 'fine', 'discount', 'total', 'date', 'receipt')
        tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set)
        tree.heading('student_name', text='Student')
        tree.heading('fee_type', text='Fee Type')
        tree.heading('paid', text='Amount Paid')
        tree.heading('fine', text='Fine')
        tree.heading('discount', text='Discount')
        tree.heading('total', text='Total')
        tree.heading('date', text='Date')
        tree.heading('receipt', text='Receipt #')
        tree.column('student_name', width=150)
        tree.column('fee_type', width=150)
        tree.column('paid', width=80, anchor=tk.CENTER)
        tree.column('fine', width=70, anchor=tk.CENTER)
        tree.column('discount', width=70, anchor=tk.CENTER)
        tree.column('total', width=80, anchor=tk.CENTER)
        tree.column('date', width=90)
        tree.column('receipt', width=100)
        tree_scroll.config(command=tree.yview)
        tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        total_collections = 0.0
        for coll in collections:
            student_name, fee_type, paid, fine, discount, date, receipt = coll
            total = paid + fine - discount
            total_collections += total
            tree.insert('', tk.END, values=(student_name, fee_type, format_currency(paid), format_currency(fine), format_currency(discount), format_currency(total), date, receipt))
        
        # Summary labels
        summary_frame = tk.Frame(self.fee_summary_display_frame, bg="white")
        summary_frame.pack(fill=tk.X, pady=10)
        tk.Label(summary_frame, text=f"Total Collections (filtered): {format_currency(total_collections)}", font=("Arial", 12, "bold")).pack(side=tk.RIGHT, padx=10)

        # Add chart if available
        if self.matplotlib_loaded:
            try:
                fee_type_totals = {}
                for coll in collections:
                    fee_type = coll[1]
                    total = coll[5] # Using the calculated total from the treeview values
                    fee_type_totals[fee_type] = fee_type_totals.get(fee_type, 0) + total
                
                labels = list(fee_type_totals.keys())
                sizes = list(fee_type_totals.values())
                
                fig = Figure(figsize=(6, 3.5))
                ax = fig.add_subplot(111)
                ax.pie(sizes, labels=labels, autopct='%1.1f%%', startangle=90, pctdistance=0.85)
                ax.axis('equal') 
                ax.set_title("Fee Collection Breakdown by Type")
                
                canvas = FigureCanvasTkAgg(fig, master=self.fee_summary_display_frame)
                canvas.get_tk_widget().pack(pady=10)
                canvas.draw()
            except Exception as e:
                 tk.Label(self.fee_summary_display_frame, text=f"Error generating chart: {e}", fg="red").pack(pady=10)

    def generate_pending_fees_report(self):
        frame = self.fee_summary_frame # Reuse the frame for simplicity
        
        # Filters for pending fees report (Class, potentially Category)
        filters_frame = tk.Frame(frame, bg="white")
        filters_frame.pack(fill=tk.X, pady=10)
        
        tk.Label(filters_frame, text="Filter by Class:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        class_filter_var = tk.StringVar()
        class_filter_combo = ttk.Combobox(filters_frame, textvariable=class_filter_var, values=["All Classes"] + list(self.controller.people_view.class_data.keys()), state="readonly", width=20) # Assuming class_data is accessible
        class_filter_combo.pack(side=tk.LEFT, padx=5)
        class_filter_var.set("All Classes")
        
        tk.Button(filters_frame, text="Generate", command=lambda: self.display_pending_fees_report(class_filter_var.get()), font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(filters_frame, text="Refresh", command=lambda: self.display_pending_fees_report("All Classes"), font=("Arial", 10)).pack(side=tk.LEFT)

        self.pending_fees_display_frame = tk.Frame(frame, bg="white")
        self.pending_fees_display_frame.pack(fill=tk.BOTH, expand=True)

        self.display_pending_fees_report("All Classes")

    def display_pending_fees_report(self, filter_class_name):
        for widget in self.pending_fees_display_frame.winfo_children():
            widget.destroy()

        conn = get_db_connection()
        cursor = conn.cursor()

        # Get all students and their classes
        query_students = "SELECT s.id, s.name, s.class_id FROM students s"
        if filter_class_name and filter_class_name != "All Classes":
            query_students += " JOIN classes c ON s.class_id = c.id WHERE c.name = ?"
            params_students = (filter_class_name,)
        else:
            params_students = ()
        cursor.execute(query_students, params_students)
        students_data = cursor.fetchall() # List of (student_id, student_name, class_id)

        if not students_data:
            tk.Label(self.pending_fees_display_frame, text="No students found matching the filter.", font=("Arial", 12)).pack(pady=20)
            conn.close()
            return

        student_ids = [s[0] for s in students_data]

        # Get fee structures
        query_structures = "SELECT fs.class_id, fs.category, fs.fee_type, fs.amount FROM fee_structures fs WHERE fs.class_id IS NULL"
        if filter_class_name and filter_class_name != "All Classes":
             query_structures += " OR fs.class_id = ?"
        query_structures += " ORDER BY fs.class_id DESC" # Prioritize specific class structures
        
        params_structures = ()
        if filter_class_name and filter_class_name != "All Classes":
            class_id_map = self.controller.people_view.class_data # Assuming this map is available
            filter_class_id = class_id_map.get(filter_class_name)
            if filter_class_id:
                 params_structures = (filter_class_id,)
            else: # Class not found, should not happen if sync'd
                 params_structures = ('INVALID_ID',) # Ensure no match

        cursor.execute(query_structures, params_structures)
        structures_data = cursor.fetchall()

        # Get collected fees for these students
        query_collections = """
            SELECT student_id, fee_type, SUM(amount_paid + fine_paid - discount_applied)
            FROM fee_collections
            WHERE student_id IN ({})
            GROUP BY student_id, fee_type
        """.format(','.join('?' * len(student_ids)))
        
        params_collections = student_ids + [str(sid) for sid in student_ids] # Need to pass student_ids twice for the IN clause
        cursor.execute(query_collections, params_collections)
        collected_fees_data = cursor.fetchall()
        
        collected_fees_map = {} # {student_id: {fee_type: total_collected}}
        for sid, ftype, total in collected_fees_data:
            if sid not in collected_fees_map: collected_fees_map[sid] = {}
            collected_fees_map[sid][ftype] = total

        conn.close()

        # Calculate dues and display
        pending_fees_report = [] # List of (student_name, fee_type, amount_due)

        for student_id, student_name, student_class_id in students_data:
            student_collections = collected_fees_map.get(student_id, {})
            
            # Check fees applicable to the student's class
            for struct_class_id, category, fee_type, expected_amount in structures_data:
                is_applicable_class = (struct_class_id is None) or (struct_class_id == student_class_id)
                
                # Category matching would be needed here if implemented
                # is_applicable_category = (not category or category == student_category) 

                if is_applicable_class: # and is_applicable_category:
                    collected = student_collections.get(fee_type, 0.0)
                    due = expected_amount - collected
                    if due > 0.1: # Use threshold for float comparison
                        pending_fees_report.append((student_name, fee_type, format_currency(due)))

        if not pending_fees_report:
            tk.Label(self.pending_fees_display_frame, text="No pending fees found.", font=("Arial", 12)).pack(pady=20)
            return
        
        # Display in Treeview
        tree_frame = tk.Frame(self.pending_fees_display_frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)
        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('student', 'fee_type', 'amount_due')
        tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set)
        tree.heading('student', text='Student')
        tree.heading('fee_type', text='Fee Type')
        tree.heading('amount_due', text='Amount Due')
        tree.column('student', width=200)
        tree.column('fee_type', width=200)
        tree.column('amount_due', width=100, anchor=tk.CENTER)
        tree_scroll.config(command=tree.yview)
        tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        total_pending = 0.0
        for student, ftype, due_str in pending_fees_report:
            due_val = float(due_str.replace(',', '')) # Convert back for summing
            total_pending += due_val
            tree.insert('', tk.END, values=(student, ftype, due_str))
        
        summary_frame = tk.Frame(self.pending_fees_display_frame, bg="white")
        summary_frame.pack(fill=tk.X, pady=10)
        tk.Label(summary_frame, text=f"Total Pending Fees (filtered): {format_currency(total_pending)}", font=("Arial", 12, "bold")).pack(side=tk.RIGHT, padx=10)

    def generate_attendance_summary_report(self):
        frame = self.attendance_summary_frame
        
        # Filters: Date range, Class
        filters_frame = tk.Frame(frame, bg="white")
        filters_frame.pack(fill=tk.X, pady=10)

        tk.Label(filters_frame, text="Start Date:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        start_date_var = tk.StringVar(value=datetime.now().strftime('%Y-%m-%d'))
        start_date_entry = tk.Entry(filters_frame, textvariable=start_date_var, width=12, font=("Arial", 10))
        start_date_entry.pack(side=tk.LEFT, padx=5)
        try:
             from tkcalendar import DateEntry
             tk.Button(filters_frame, text="📅", command=lambda: self.pick_date(start_date_entry), font=("Arial", 9)).pack(side=tk.LEFT)
        except ImportError: pass

        tk.Label(filters_frame, text="End Date:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        end_date_var = tk.StringVar(value=datetime.now().strftime('%Y-%m-%d'))
        end_date_entry = tk.Entry(filters_frame, textvariable=end_date_var, width=12, font=("Arial", 10))
        end_date_entry.pack(side=tk.LEFT, padx=5)
        try:
             from tkcalendar import DateEntry
             tk.Button(filters_frame, text="📅", command=lambda: self.pick_date(end_date_entry), font=("Arial", 9)).pack(side=tk.LEFT)
        except ImportError: pass

        tk.Label(filters_frame, text="Class:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        class_filter_var = tk.StringVar()
        # Use controller.people_view.class_data assuming it's populated
        class_list_for_filter = ["All Classes"] + sorted(list(self.controller.people_view.class_data.keys())) if hasattr(self.controller, 'people_view') and hasattr(self.controller.people_view, 'class_data') else ["All Classes"]
        class_filter_combo = ttk.Combobox(filters_frame, textvariable=class_filter_var, values=class_list_for_filter, state="readonly", width=15)
        class_filter_combo.pack(side=tk.LEFT, padx=5)
        class_filter_var.set("All Classes")
        
        tk.Button(filters_frame, text="Generate", command=lambda: self.display_attendance_summary(start_date_var.get(), end_date_var.get(), class_filter_var.get()), font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(filters_frame, text="Refresh", command=lambda: self.display_attendance_summary(datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%Y-%m-%d'), "All Classes"), font=("Arial", 10)).pack(side=tk.LEFT)

        self.attendance_summary_display_frame = tk.Frame(frame, bg="white")
        self.attendance_summary_display_frame.pack(fill=tk.BOTH, expand=True)

        self.display_attendance_summary(datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%Y-%m-%d'), "All Classes")

    def display_attendance_summary(self, start_date, end_date, filter_class_name):
        for widget in self.attendance_summary_display_frame.winfo_children():
            widget.destroy()

        conn = get_db_connection()
        cursor = conn.cursor()

        # Get students based on class filter
        student_query = "SELECT s.id, s.name, s.class_id FROM students s"
        if filter_class_name and filter_class_name != "All Classes":
            student_query += " JOIN classes c ON s.class_id = c.id WHERE c.name = ?"
            params_students = (filter_class_name,)
        else:
            params_students = ()
        cursor.execute(student_query, params_students)
        students_data = cursor.fetchall()

        if not students_data:
            tk.Label(self.attendance_summary_display_frame, text="No students found for the selected class.", font=("Arial", 12)).pack(pady=20)
            conn.close()
            return

        student_ids = [s[0] for s in students_data]
        
        # Get attendance records within the date range for these students
        attendance_query = """
            SELECT attendance_date, student_id, status
            FROM attendance
            WHERE student_id IN ({}) AND attendance_date BETWEEN ? AND ?
            ORDER BY attendance_date
        """.format(','.join('?' * len(student_ids)))
        
        params_attendance = student_ids + [start_date, end_date] + student_ids # Date range + student IDs again for the IN clause
        cursor.execute(attendance_query, params_attendance)
        attendance_records = cursor.fetchall()
        conn.close()

        # Process attendance data for summary
        attendance_summary = {} # {student_id: {'Present': count, 'Absent': count, ...}}
        total_days = 0
        try:
             start_dt = datetime.strptime(start_date, '%Y-%m-%d')
             end_dt = datetime.strptime(end_date, '%Y-%m-%d')
             total_days = (end_dt - start_dt).days + 1
        except ValueError:
            pass # Invalid dates, handle gracefully

        for student_id, _, _ in students_data: # Initialize all students
             attendance_summary[student_id] = {'Present': 0, 'Absent': 0, 'Late': 0, 'Leave': 0, 'Total Days': total_days}

        for date, student_id, status in attendance_records:
             if student_id in attendance_summary:
                 if status in attendance_summary[student_id]:
                      attendance_summary[student_id][status] += 1
                 # Handle cases where status might not be one of the predefined ones
        
        # Display in Treeview
        tree_frame = tk.Frame(self.attendance_summary_display_frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)
        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('student', 'present', 'absent', 'late', 'leave', 'total_days')
        tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set)
        tree.heading('student', text='Student')
        tree.heading('present', text='Present')
        tree.heading('absent', text='Absent')
        tree.heading('late', text='Late')
        tree.heading('leave', text='Leave')
        tree.heading('total_days', text='Days Covered')
        tree.column('student', width=200)
        tree.column('present', width=70, anchor=tk.CENTER)
        tree.column('absent', width=70, anchor=tk.CENTER)
        tree.column('late', width=70, anchor=tk.CENTER)
        tree.column('leave', width=70, anchor=tk.CENTER)
        tree.column('total_days', width=100, anchor=tk.CENTER)
        tree_scroll.config(command=tree.yview)
        tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        total_present_all = 0
        total_absent_all = 0
        total_late_all = 0
        total_leave_all = 0

        # Add student names back
        student_id_to_name = {s[0]: s[1] for s in students_data}
        
        for student_id, summary in attendance_summary.items():
            student_name = student_id_to_name.get(student_id, f"ID: {student_id}")
            p = summary.get('Present', 0)
            a = summary.get('Absent', 0)
            l = summary.get('Late', 0)
            lv = summary.get('Leave', 0)
            td = summary.get('Total Days', 0)
            
            total_present_all += p
            total_absent_all += a
            total_late_all += l
            total_leave_all += lv

            tree.insert('', tk.END, values=(student_name, p, a, l, lv, td))

        # Summary totals
        summary_frame = tk.Frame(self.attendance_summary_display_frame, bg="white")
        summary_frame.pack(fill=tk.X, pady=10)
        tk.Label(summary_frame, text=f"Summary ({start_date} to {end_date}): ", font=("Arial", 11, "bold")).pack(side=tk.LEFT, padx=10)
        tk.Label(summary_frame, text=f"Total Present: {total_present_all}", font=("Arial", 11)).pack(side=tk.LEFT, padx=10)
        tk.Label(summary_frame, text=f"Total Absent: {total_absent_all}", font=("Arial", 11)).pack(side=tk.LEFT, padx=10)
        tk.Label(summary_frame, text=f"Total Late: {total_late_all}", font=("Arial", 11)).pack(side=tk.LEFT, padx=10)
        tk.Label(summary_frame, text=f"Total Leave: {total_leave_all}", font=("Arial", 11)).pack(side=tk.LEFT, padx=10)

        # Chart (optional)
        if self.matplotlib_loaded and total_days > 0:
             try:
                 fig = Figure(figsize=(6, 3.5))
                 ax = fig.add_subplot(111)
                 labels = ['Present', 'Absent', 'Late', 'Leave']
                 sizes = [total_present_all, total_absent_all, total_late_all, total_leave_all]
                 # Filter out zero values for cleaner pie chart
                 filtered_labels, filtered_sizes = zip(*[(l, s) for l, s in zip(labels, sizes) if s > 0])

                 if filtered_sizes: # Only plot if there's data
                     ax.pie(filtered_sizes, labels=filtered_labels, autopct='%1.1f%%', startangle=90)
                     ax.axis('equal') 
                     ax.set_title("Overall Attendance Distribution")
                     
                     canvas = FigureCanvasTkAgg(fig, master=self.attendance_summary_display_frame)
                     canvas.get_tk_widget().pack(pady=10)
                     canvas.draw()
             except Exception as e:
                  tk.Label(self.attendance_summary_display_frame, text=f"Error generating chart: {e}", fg="red").pack(pady=10)


    def generate_exam_results_summary_report(self):
        frame = self.exam_results_frame
        
        # Filters: Exam, Class, Subject (optional)
        filters_frame = tk.Frame(frame, bg="white")
        filters_frame.pack(fill=tk.X, pady=10)

        tk.Label(filters_frame, text="Select Exam:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        exam_var = tk.StringVar()
        # Fetch exams dynamically
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM exams ORDER BY name")
        exams_data = {row[1]: row[0] for row in cursor.fetchall()}
        conn.close()
        exam_options = ["All Exams"] + sorted(list(exams_data.keys()))
        exam_combo = ttk.Combobox(filters_frame, textvariable=exam_var, values=exam_options, state="readonly", width=20)
        exam_combo.pack(side=tk.LEFT, padx=5)
        exam_var.set("All Exams")

        tk.Label(filters_frame, text="Select Class:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        class_var = tk.StringVar()
        class_options = ["All Classes"] + sorted(list(self.controller.people_view.class_data.keys())) if hasattr(self.controller, 'people_view') and hasattr(self.controller.people_view, 'class_data') else ["All Classes"]
        class_combo = ttk.Combobox(filters_frame, textvariable=class_var, values=class_options, state="readonly", width=15)
        class_combo.pack(side=tk.LEFT, padx=5)
        class_var.set("All Classes")
        
        tk.Button(filters_frame, text="Generate", command=lambda: self.display_exam_results_summary(exam_var.get(), class_var.get()), font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(filters_frame, text="Refresh", command=lambda: self.display_exam_results_summary("All Exams", "All Classes"), font=("Arial", 10)).pack(side=tk.LEFT)

        self.exam_results_display_frame = tk.Frame(frame, bg="white")
        self.exam_results_display_frame.pack(fill=tk.BOTH, expand=True)

        self.display_exam_results_summary("All Exams", "All Classes")

    def display_exam_results_summary(self, filter_exam_name, filter_class_name):
        for widget in self.exam_results_display_frame.winfo_children():
            widget.destroy()

        conn = get_db_connection()
        cursor = conn.cursor()

        # Get Exam ID if specific exam is selected
        exam_id = None
        if filter_exam_name and filter_exam_name != "All Exams":
             cursor.execute("SELECT id FROM exams WHERE name = ?", (filter_exam_name,))
             exam_row = cursor.fetchone()
             if exam_row: exam_id = exam_row[0]

        # Get Class ID if specific class is selected
        class_id = None
        if filter_class_name and filter_class_name != "All Classes":
            cursor.execute("SELECT id FROM classes WHERE name = ?", (filter_class_name,))
            class_row = cursor.fetchone()
            if class_row: class_id = class_row[0]

        # Build query
        query = """
            SELECT s.name, c.name, sub.name, m.marks_obtained, m.total_marks, e.name
            FROM marks m
            JOIN students s ON m.student_id = s.id
            JOIN subjects sub ON m.subject_id = sub.id
            JOIN exams e ON m.exam_id = e.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE 1=1
        """
        params = []
        if exam_id:
            query += " AND m.exam_id = ?"
            params.append(exam_id)
        if class_id:
            query += " AND s.class_id = ?"
            params.append(class_id)
        
        query += " ORDER BY s.name, e.name, sub.name"

        cursor.execute(query, params)
        results = cursor.fetchall()
        conn.close()

        if not results:
            tk.Label(self.exam_results_display_frame, text="No exam results found for the selected criteria.", font=("Arial", 12)).pack(pady=20)
            return

        # Display in Treeview
        tree_frame = tk.Frame(self.exam_results_display_frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)
        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('student', 'class', 'subject', 'obtained', 'total', 'exam', 'percentage', 'grade')
        tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set)
        tree.heading('student', text='Student')
        tree.heading('class', text='Class')
        tree.heading('subject', text='Subject')
        tree.heading('obtained', text='Obtained')
        tree.heading('total', text='Total')
        tree.heading('exam', text='Exam')
        tree.heading('percentage', text='%')
        tree.heading('grade', text='Grade')
        tree.column('student', width=150)
        tree.column('class', width=80)
        tree.column('subject', width=150)
        tree.column('obtained', width=70, anchor=tk.CENTER)
        tree.column('total', width=70, anchor=tk.CENTER)
        tree.column('exam', width=120)
        tree.column('percentage', width=60, anchor=tk.CENTER)
        tree.column('grade', width=60, anchor=tk.CENTER)
        tree_scroll.config(command=tree.yview)
        tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        total_percentage_sum = 0
        record_count = 0

        for student, cls, subject, obtained, total, exam, _, _ in results: # Using dummy placeholders for percentage/grade initially
            percentage = 0
            grade = "N/A"
            if total and total > 0:
                percentage = (obtained / total) * 100 if obtained is not None else 0
                grade = calculate_grade(percentage)
                total_percentage_sum += percentage
                record_count += 1
            
            tree.insert('', tk.END, values=(student, cls, subject, obtained if obtained is not None else '-', total if total is not None else '-', exam, f"{percentage:.1f}" if percentage > 0 else '-', grade))

        # Summary
        summary_frame = tk.Frame(self.exam_results_display_frame, bg="white")
        summary_frame.pack(fill=tk.X, pady=10)
        avg_percentage = (total_percentage_sum / record_count) if record_count > 0 else 0
        tk.Label(summary_frame, text=f"Average Percentage ({filter_exam_name} / {filter_class_name}): {avg_percentage:.1f}%", font=("Arial", 12, "bold")).pack(side=tk.RIGHT, padx=10)

        # Chart (optional) - Distribution of Grades
        if self.matplotlib_loaded:
             try:
                 grades_count = {'A+': 0, 'A': 0, 'B': 0, 'C': 0, 'D': 0, 'F': 0}
                 for item_id in tree.get_children():
                     grade = tree.item(item_id, 'values')[7]
                     if grade in grades_count:
                         grades_count[grade] += 1
                 
                 labels = [g for g in grades_count if grades_count[g] > 0]
                 sizes = [grades_count[g] for g in labels]
                 
                 if sizes:
                     fig = Figure(figsize=(6, 3.5))
                     ax = fig.add_subplot(111)
                     ax.pie(sizes, labels=labels, autopct='%1.1f%%', startangle=90)
                     ax.axis('equal') 
                     ax.set_title("Grade Distribution")
                     
                     canvas = FigureCanvasTkAgg(fig, master=self.exam_results_display_frame)
                     canvas.get_tk_widget().pack(pady=10)
                     canvas.draw()
             except Exception as e:
                 tk.Label(self.exam_results_display_frame, text=f"Error generating chart: {e}", fg="red").pack(pady=10)


    def generate_income_expense_report(self):
        frame = self.financial_report_frame
        
        # Filters: Date range
        filters_frame = tk.Frame(frame, bg="white")
        filters_frame.pack(fill=tk.X, pady=10)

        tk.Label(filters_frame, text="Start Date:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        start_date_var = tk.StringVar(value=datetime.now().strftime('%Y-%m-%d'))
        start_date_entry = tk.Entry(filters_frame, textvariable=start_date_var, width=12, font=("Arial", 10))
        start_date_entry.pack(side=tk.LEFT, padx=5)
        try:
             from tkcalendar import DateEntry
             tk.Button(filters_frame, text="📅", command=lambda: self.pick_date(start_date_entry), font=("Arial", 9)).pack(side=tk.LEFT)
        except ImportError: pass

        tk.Label(filters_frame, text="End Date:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        end_date_var = tk.StringVar(value=datetime.now().strftime('%Y-%m-%d'))
        end_date_entry = tk.Entry(filters_frame, textvariable=end_date_var, width=12, font=("Arial", 10))
        end_date_entry.pack(side=tk.LEFT, padx=5)
        try:
             from tkcalendar import DateEntry
             tk.Button(filters_frame, text="📅", command=lambda: self.pick_date(end_date_entry), font=("Arial", 9)).pack(side=tk.LEFT)
        except ImportError: pass
        
        tk.Button(filters_frame, text="Generate", command=lambda: self.display_income_expense_report(start_date_var.get(), end_date_var.get()), font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(filters_frame, text="Refresh", command=lambda: self.display_income_expense_report(datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%Y-%m-%d')), font=("Arial", 10)).pack(side=tk.LEFT)

        self.income_expense_display_frame = tk.Frame(frame, bg="white")
        self.income_expense_display_frame.pack(fill=tk.BOTH, expand=True)

        self.display_income_expense_report(datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%Y-%m-%d'))

    def display_income_expense_report(self, start_date, end_date):
        for widget in self.income_expense_display_frame.winfo_children():
            widget.destroy()

        conn = get_db_connection()
        cursor = conn.cursor()
        
        query = """
            SELECT transaction_date, description, type, category, amount
            FROM transactions
            WHERE transaction_date BETWEEN ? AND ?
            ORDER BY transaction_date, type DESC
        """
        cursor.execute(query, (start_date, end_date))
        transactions = cursor.fetchall()
        conn.close()

        if not transactions:
            tk.Label(self.income_expense_display_frame, text="No transactions found for the selected date range.", font=("Arial", 12)).pack(pady=20)
            return

        # Display in Treeview
        tree_frame = tk.Frame(self.income_expense_display_frame, bg="white", bd=2, relief=tk.GROOVE)
        tree_frame.pack(fill=tk.BOTH, expand=True)
        tree_scroll = tk.Scrollbar(tree_frame)
        tree_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        columns = ('date', 'description', 'type', 'category', 'amount')
        tree = ttk.Treeview(tree_frame, columns=columns, show='headings', yscrollcommand=tree_scroll.set)
        tree.heading('date', text='Date')
        tree.heading('description', text='Description')
        tree.heading('type', text='Type')
        tree.heading('category', text='Category')
        tree.heading('amount', text='Amount')
        tree.column('date', width=100)
        tree.column('description', width=250)
        tree.column('type', width=80)
        tree.column('category', width=150)
        tree.column('amount', width=100, anchor=tk.CENTER)
        tree_scroll.config(command=tree.yview)
        tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        total_income = 0
        total_expense = 0

        for date, desc, type, cat, amount in transactions:
            if type == "Income":
                total_income += amount
            else:
                total_expense += amount
            tree.insert('', tk.END, values=(date, desc, type, cat, format_currency(amount)))
        
        # Summary
        summary_frame = tk.Frame(self.income_expense_display_frame, bg="white")
        summary_frame.pack(fill=tk.X, pady=10)
        net_amount = total_income + total_expense # Expense is negative
        tk.Label(summary_frame, text=f"Total Income: {format_currency(total_income)}", font=("Arial", 11, "bold"), fg="green").pack(side=tk.LEFT, padx=10)
        tk.Label(summary_frame, text=f"Total Expenses: {format_currency(total_expense)}", font=("Arial", 11, "bold"), fg="red").pack(side=tk.LEFT, padx=10)
        tk.Label(summary_frame, text=f"Net Amount: {format_currency(net_amount)}", font=("Arial", 11, "bold"), fg="blue").pack(side=tk.LEFT, padx=10)

        # Chart (optional)
        if self.matplotlib_loaded:
            try:
                fig = Figure(figsize=(6, 3.5))
                ax = fig.add_subplot(111)
                labels = ['Income', 'Expenses']
                sizes = [total_income, abs(total_expense)] # Use absolute for pie chart
                colors = ['green', 'red']
                
                if sizes[0] > 0 or sizes[1] > 0: # Only plot if there is data
                    ax.pie(sizes, labels=labels, colors=colors, autopct='%1.1f%%', startangle=90)
                    ax.axis('equal') 
                    ax.set_title(f"Income vs. Expenses ({start_date} to {end_date})")
                    
                    canvas = FigureCanvasTkAgg(fig, master=self.income_expense_display_frame)
                    canvas.get_tk_widget().pack(pady=10)
                    canvas.draw()
            except Exception as e:
                tk.Label(self.income_expense_display_frame, text=f"Error generating chart: {e}", fg="red").pack(pady=10)


    def generate_financial_summary_report(self):
        frame = self.financial_report_frame
        
        # Filters: Date range for income/expense, potentially month for fees/salaries
        filters_frame = tk.Frame(frame, bg="white")
        filters_frame.pack(fill=tk.X, pady=10)

        tk.Label(filters_frame, text="Date Range:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        start_date_var = tk.StringVar(value=datetime.now().strftime('%Y-%m-%d'))
        start_date_entry = tk.Entry(filters_frame, textvariable=start_date_var, width=12, font=("Arial", 10))
        start_date_entry.pack(side=tk.LEFT, padx=5)
        try:
             from tkcalendar import DateEntry
             tk.Button(filters_frame, text="📅", command=lambda: self.pick_date(start_date_entry), font=("Arial", 9)).pack(side=tk.LEFT)
        except ImportError: pass

        end_date_var = tk.StringVar(value=datetime.now().strftime('%Y-%m-%d'))
        end_date_entry = tk.Entry(filters_frame, textvariable=end_date_var, width=12, font=("Arial", 10))
        end_date_entry.pack(side=tk.LEFT, padx=5)
        try:
             from tkcalendar import DateEntry
             tk.Button(filters_frame, text="📅", command=lambda: self.pick_date(end_date_entry), font=("Arial", 9)).pack(side=tk.LEFT)
        except ImportError: pass
        
        tk.Button(filters_frame, text="Generate", command=lambda: self.display_financial_summary(start_date_var.get(), end_date_var.get()), font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(filters_frame, text="Refresh", command=lambda: self.display_financial_summary(datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%Y-%m-%d')), font=("Arial", 10)).pack(side=tk.LEFT)

        self.financial_summary_display_frame = tk.Frame(frame, bg="white")
        self.financial_summary_display_frame.pack(fill=tk.BOTH, expand=True)

        self.display_financial_summary(datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%Y-%m-%d'))

    def display_financial_summary(self, start_date, end_date):
        for widget in self.financial_summary_display_frame.winfo_children():
            widget.destroy()

        conn = get_db_connection()
        cursor = conn.cursor()

        # 1. Total Fees Collected
        cursor.execute("""
            SELECT SUM(amount_paid + fine_paid - discount_applied)
            FROM fee_collections
            WHERE payment_date BETWEEN ? AND ?
        """, (start_date, end_date))
        total_fees_collected = cursor.fetchone()[0] or 0.0

        # 2. Total Salary Paid (Simplified - requires fetching payslips)
        cursor.execute("""
            SELECT SUM(net_pay)
            FROM payslips
            WHERE generated_date BETWEEN ? AND ? 
        """, (start_date, end_date)) # Assuming generated_date is close enough to payment/processing date
        total_salary_paid = cursor.fetchone()[0] or 0.0
        
        # 3. Total Transactions (Income and Expense)
        cursor.execute("""
            SELECT type, SUM(amount)
            FROM transactions
            WHERE transaction_date BETWEEN ? AND ?
            GROUP BY type
        """, (start_date, end_date))
        transaction_totals = cursor.fetchall()
        
        total_income_trans = 0
        total_expense_trans = 0
        for type, amount in transaction_totals:
            if type == "Income": total_income_trans = amount
            else: total_expense_trans = amount # Already negative if stored as such

        # Calculate overall net amount
        grand_total = total_fees_collected + total_salary_paid + total_income_trans + total_expense_trans

        # Display summary
        summary_text = f"Financial Summary ({start_date} to {end_date}):\n\n"
        summary_text += f"- Total Fees Collected: {format_currency(total_fees_collected)}\n"
        summary_text += f"- Total Salary Paid: {format_currency(total_salary_paid)}\n"
        summary_text += f"- Total Other Income: {format_currency(total_income_trans)}\n"
        summary_text += f"- Total Other Expenses: {format_currency(total_expense_trans)}\n\n"
        summary_text += f"----------------------------------------\n"
        summary_text += f"Net Financial Position: {format_currency(grand_total)}"

        tk.Label(self.financial_summary_display_frame, text=summary_text, justify=tk.LEFT, font=("Arial", 12), padx=20, pady=20).pack()

        # Chart (optional) - Breakdown of income sources and expense categories
        if self.matplotlib_loaded:
            try:
                # For simplicity, let's just show Fee Collection vs Other Income vs Expenses
                fig = Figure(figsize=(6, 3.5))
                ax = fig.add_subplot(111)
                labels = ['Fees Collected', 'Other Income', 'Expenses']
                sizes = [total_fees_collected, total_income_trans, abs(total_expense_trans)]
                colors = ['blue', 'green', 'red']
                
                filtered_labels, filtered_sizes = zip(*[(l, s) for l, s in zip(labels, sizes) if s > 0])

                if filtered_sizes:
                    ax.pie(filtered_sizes, labels=filtered_labels, colors=[colors[i] for i, s in enumerate(sizes) if s > 0], autopct='%1.1f%%', startangle=90)
                    ax.axis('equal') 
                    ax.set_title("Financial Breakdown")
                    
                    canvas = FigureCanvasTkAgg(fig, master=self.financial_summary_display_frame)
                    canvas.get_tk_widget().pack(pady=10)
                    canvas.draw()
            except Exception as e:
                 tk.Label(self.financial_summary_display_frame, text=f"Error generating chart: {e}", fg="red").pack(pady=10)

        conn.close()

    def print_report(self):
        messagebox.showinfo("Print Report", "Printing functionality not fully implemented.")

# --- Utilities View Continued ---
class UtilitiesView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)
        self.create_widgets()
        self.load_people_for_idcard_cert() # Load data for dropdowns

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="Utilities", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        # ID Card Generation
        id_card_frame = tk.LabelFrame(self.main_frame, text="ID Card Generation", padx=10, pady=10, bg="white", font=("Arial", 12))
        id_card_frame.pack(fill=tk.X, pady=10)
        
        tk.Label(id_card_frame, text="Select Student:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.idcard_student_combo_var = tk.StringVar()
        self.idcard_student_combo = ttk.Combobox(id_card_frame, textvariable=self.idcard_student_combo_var, state="readonly", width=40)
        self.idcard_student_combo.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)
        tk.Button(id_card_frame, text="Generate Student ID Card", command=self.generate_student_id_card, font=("Arial", 10, "bold")).grid(row=1, column=0, columnspan=2, pady=10)
        
        id_card_frame.grid_columnconfigure(1, weight=1)

        # Certificate Generation
        cert_frame = tk.LabelFrame(self.main_frame, text="Certificate Generation", padx=10, pady=10, bg="white", font=("Arial", 12))
        cert_frame.pack(fill=tk.X, pady=10)

        tk.Label(cert_frame, text="Select Person:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.cert_person_combo_var = tk.StringVar()
        self.cert_person_combo = ttk.Combobox(cert_frame, textvariable=self.cert_person_combo_var, state="readonly", width=40)
        self.cert_person_combo.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)
        
        tk.Label(cert_frame, text="Certificate Type:", font=("Arial", 11)).grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        self.cert_type_var = tk.StringVar()
        cert_types = ["Character Certificate", "Bonafide Certificate", "Promotion Certificate"]
        cert_type_combo = ttk.Combobox(cert_frame, textvariable=self.cert_type_var, values=cert_types, state="readonly", width=40)
        cert_type_combo.grid(row=1, column=1, padx=5, pady=5, sticky=tk.EW)
        tk.Button(cert_frame, text="Generate Certificate", command=self.generate_certificate, font=("Arial", 10, "bold")).grid(row=2, column=0, columnspan=2, pady=10)
        
        cert_frame.grid_columnconfigure(1, weight=1)
        
        # Backup/Restore (Can be placed here or in Settings)
        backup_restore_frame = tk.LabelFrame(self.main_frame, text="Database Management", padx=10, pady=10, bg="white", font=("Arial", 12))
        backup_restore_frame.pack(fill=tk.X, pady=10)
        tk.Button(backup_restore_frame, text="Backup Database", command=backup_database, font=("Arial", 10)).pack(side=tk.LEFT, padx=10)
        tk.Button(backup_restore_frame, text="Restore Database", command=restore_database, font=("Arial", 10)).pack(side=tk.LEFT, padx=10)


# In UtilitiesView class, method load_people_for_idcard_cert:

    def load_people_for_idcard_cert(self):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM students ORDER BY name")
        students = cursor.fetchall()
        cursor.execute("SELECT id, name FROM teachers ORDER BY name")
        teachers = cursor.fetchall()
        conn.close()

        people_data = {}
        for sid, sname in students:
            people_data[f"{sname} (Student, ID:{sid})"] = {'id': sid, 'type': 'student'}
        for tid, tname in teachers:
            people_data[f"{tname} (Teacher, ID:{tid})"] = {'id': tid, 'type': 'teacher'}
        
        # FIX: Correctly assign the populated dictionary to self.cert_people_data
        self.cert_people_data = people_data 
        self.idcard_people_data = people_data # Assuming it should be the same data for both

        sorted_keys = sorted(list(people_data.keys())) # Sort once

        self.idcard_student_combo['values'] = sorted_keys
        self.cert_person_combo['values'] = sorted_keys

        if sorted_keys: # Check if the list is not empty
            self.idcard_student_combo.set(sorted_keys[0])
            # FIX: Correctly use cert_people_data to get the keys for the cert_person_combo
            self.cert_person_combo.set(sorted_keys[0]) 


    def generate_student_id_card(self):
        person_key = self.idcard_student_combo_var.get()
        if not person_key:
            messagebox.showwarning("Selection Error", "Please select a student.")
            return
        
        person_info = self.idcard_people_data.get(person_key)
        if not person_info or person_info['type'] != 'student':
            messagebox.showerror("Error", "Invalid selection. Please select a student.")
            return
        
        student_id = person_info['id']
        
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT name, cnic, contact, class_id FROM students WHERE id = ?", (student_id,))
        student_data = cursor.fetchone()
        
        cursor.execute("SELECT name FROM classes WHERE id = ?", (student_data[3],))
        class_name = cursor.fetchone()[0] if cursor.rowcount > 0 else "N/A"
        conn.close()

        if not student_data:
            messagebox.showerror("Error", "Student data not found.")
            return

        name, cnic, contact, _ = student_data
        qr_data = f"ID:{student_id} Name:{name} CNIC:{cnic}"
        qr_img_tk = get_qr_code_image(qr_data)

        # --- ID Card Generation Logic ---
        # This is a basic template. Actual ID card design might need more advanced image manipulation.
        card_width, card_height = 300, 450 # Standard ID card size in pixels (approx)
        img = Image.new('RGB', (card_width, card_height), color = (255, 255, 255))
        draw = ImageDraw.Draw(img)

        # School Logo
        logo_path = get_setting('logo_path')
        logo_img = None
        if logo_path and os.path.exists(logo_path):
             try:
                logo_img = Image.open(logo_path)
                logo_img.thumbnail((80, 80))
                img.paste(logo_img, (card_width // 2 - logo_img.width // 2, 10), logo_img if logo_img.mode == 'RGBA' else None)
             except Exception as e: print(f"Error pasting logo: {e}")

        # School Name
        school_name = get_setting('school_name') or "My School"
        try:
            font_school = ImageFont.truetype("arialbd.ttf", 16) # Arial Bold
        except IOError:
            font_school = ImageFont.load_default()
        
        text_width, text_height = draw.textbbox((0,0), school_name, font=font_school)[2:]
        draw.text((card_width // 2 - text_width // 2, 90), school_name, fill="black", font=font_school)

        # ID Card Title
        try:
            font_title = ImageFont.truetype("arial.ttf", 14)
        except IOError:
            font_title = ImageFont.load_default()
        draw.text((card_width // 2 - draw.textbbox((0,0), "Student ID Card", font=font_title)[2]//2, 120), "Student ID Card", fill="black", font=font_title)

        # Student Photo Placeholder or Actual Photo
        student_photo_path = self.get_student_photo_path(student_id) # Fetch actual path from DB
        photo_area_x, photo_area_y = 50, 150
        photo_w, photo_h = 100, 120
        if student_photo_path and os.path.exists(student_photo_path):
            try:
                student_img = Image.open(student_photo_path)
                student_img = student_img.resize((photo_w, photo_h))
                img.paste(student_img, (card_width // 2 - photo_w // 2, photo_area_y), student_img if student_img.mode == 'RGBA' else None)
            except Exception as e:
                print(f"Error loading student photo for ID card: {e}")
                draw.rectangle([card_width // 2 - photo_w // 2, photo_area_y, card_width // 2 + photo_w // 2, photo_area_y + photo_h], outline="gray")
                try: font_ph = ImageFont.truetype("arial.ttf", 10)
                except IOError: font_ph = ImageFont.load_default()
                draw.text((card_width // 2 - draw.textbbox((0,0), "[No Photo]", font=font_ph)[2]//2, photo_area_y + photo_h // 2 - 10), "[No Photo]", fill="gray", font=font_ph)
        else:
             draw.rectangle([card_width // 2 - photo_w // 2, photo_area_y, card_width // 2 + photo_w // 2, photo_area_y + photo_h], outline="gray")
             try: font_ph = ImageFont.truetype("arial.ttf", 10)
             except IOError: font_ph = ImageFont.load_default()
             draw.text((card_width // 2 - draw.textbbox((0,0), "[No Photo]", font=font_ph)[2]//2, photo_area_y + photo_h // 2 - 10), "[No Photo]", fill="gray", font=font_ph)


        # Student Details
        details_y_start = photo_area_y + photo_w + 15 # Start below photo
        try:
            font_detail = ImageFont.truetype("arial.ttf", 11)
        except IOError:
            font_detail = ImageFont.load_default()

        draw.text((30, details_y_start), f"Name: {name}", fill="black", font=font_detail)
        draw.text((30, details_y_start + 25), f"Class: {class_name}", fill="black", font=font_detail)
        draw.text((30, details_y_start + 50), f"CNIC: {cnic if cnic else 'N/A'}", fill="black", font=font_detail)
        draw.text((30, details_y_start + 75), f"Contact: {contact if contact else 'N/A'}", fill="black", font=font_detail)
        
        # QR Code
        img.paste(qr_img_tk, (card_width - qr_img_tk.width - 15, details_y_start + 50), qr_img_tk) # Paste QR on the right

        # Save or display the card image
        save_path = filedialog.asksaveasfilename(defaultextension=".png", filetypes=[("PNG files", "*.png"), ("JPEG files", "*.jpg")])
        if save_path:
            try:
                img.save(save_path)
                messagebox.showinfo("Success", f"ID Card saved to {save_path}")
            except Exception as e:
                messagebox.showerror("Error", f"Failed to save ID Card: {e}")
        else:
             # Optionally display in a Tkinter window if not saving
             pass 

    def get_student_photo_path(self, student_id):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT photo_path FROM students WHERE id = ?", (student_id,))
        result = cursor.fetchone()
        conn.close()
        return result[0] if result else None

    def generate_certificate(self):
        person_key = self.cert_person_combo_var.get()
        cert_type = self.cert_type_var.get()

        if not person_key or not cert_type:
            messagebox.showwarning("Input Error", "Please select a person and a certificate type.")
            return

        person_info = self.cert_person_data.get(person_key)
        if not person_info:
            messagebox.showerror("Error", "Invalid person selection.")
            return

        person_id = person_info['id']
        person_type = person_info['type']

        # Fetch data based on person type
        data = None
        photo_path = None
        if person_type == 'student':
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT s.name, s.father_name, s.class_id, s.admission_date, s.photo_path FROM students s WHERE s.id = ?", (person_id,))
            student_data = cursor.fetchone()
            if student_data:
                 name, father_name, class_id, admission_date, photo_path = student_data
                 cursor.execute("SELECT name FROM classes WHERE id = ?", (class_id,))
                 class_name = cursor.fetchone()[0] if cursor.rowcount > 0 else "N/A"
                 data = {'name': name, 'father_name': father_name, 'class': class_name, 'admission_date': admission_date, 'photo': photo_path}
            conn.close()

        elif person_type == 'teacher':
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT name, contact, role, joining_date FROM teachers WHERE id = ?", (person_id,))
            teacher_data = cursor.fetchone()
            if teacher_data:
                 name, contact, role, joining_date = teacher_data
                 data = {'name': name, 'contact': contact, 'role': role, 'joining_date': joining_date, 'photo': self.get_teacher_photo_path(person_id)}
            conn.close()

        if not data:
            messagebox.showerror("Error", f"{person_type.capitalize()} data not found.")
            return

        # --- Certificate Generation Logic ---
        # This requires more specific templates based on certificate type.
        # Using placeholders for now.
        
        messagebox.showinfo("Generate Certificate", f"Generating '{cert_type}' for {person_key}...\nData: {data}")
        # Here you would create a PDF or an image file based on the certificate type and fetched data.


    def get_teacher_photo_path(self, teacher_id):
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT photo_path FROM teachers WHERE id = ?", (teacher_id,))
        result = cursor.fetchone()
        conn.close()
        return result[0] if result else None


class SettingsView(tk.Frame):
    def __init__(self, master, controller):
        super().__init__(master, bg="white")
        self.controller = controller
        self.logo_photo = None # To hold PhotoImage object for display

        self.main_frame = tk.Frame(self, bg="white")
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)
        self.create_widgets()
        self.load_settings()

    def create_widgets(self):
        header_frame = tk.Frame(self.main_frame, bg="#e0e0e0", height=50)
        header_frame.pack(fill=tk.X, pady=10)
        tk.Label(header_frame, text="System Settings", font=("Arial", 18, "bold"), bg="#e0e0e0", padx=20).pack(side=tk.LEFT)

        settings_frame = tk.LabelFrame(self.main_frame, text="General Settings", padx=10, pady=10, bg="white", font=("Arial", 12))
        settings_frame.pack(fill=tk.X, pady=10)

        tk.Label(settings_frame, text="School/Madrasa Name:", font=("Arial", 11)).grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        self.school_name_entry = tk.Entry(settings_frame, width=40, font=("Arial", 11))
        self.school_name_entry.grid(row=0, column=1, padx=5, pady=5, sticky=tk.EW)

        tk.Label(settings_frame, text="Logo:", font=("Arial", 11)).grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        self.logo_path_var = tk.StringVar()
        self.logo_path_entry = tk.Entry(settings_frame, textvariable=self.logo_path_var, width=30, font=("Arial", 11), state="readonly")
        self.logo_path_entry.grid(row=1, column=1, padx=5, pady=5, sticky=tk.EW)
        tk.Button(settings_frame, text="Select Logo", command=self.select_logo, font=("Arial", 10)).grid(row=1, column=2, padx=5)
        self.logo_display_label = tk.Label(settings_frame, bg="white", width=10, height=3, relief=tk.SOLID)
        self.logo_display_label.grid(row=1, column=3, padx=5)

        tk.Label(settings_frame, text="Session Start Date:", font=("Arial", 11)).grid(row=2, column=0, padx=5, pady=5, sticky=tk.W)
        self.session_start_var = tk.StringVar()
        self.session_start_entry = tk.Entry(settings_frame, textvariable=self.session_start_var, width=15, font=("Arial", 11))
        self.session_start_entry.grid(row=2, column=1, padx=5, pady=5, sticky=tk.W)
        try:
            from tkcalendar import DateEntry
            tk.Button(settings_frame, text="📅", command=lambda: self.pick_date(self.session_start_entry), font=("Arial", 10)).grid(row=2, column=2)
        except ImportError: pass

        tk.Label(settings_frame, text="Session End Date:", font=("Arial", 11)).grid(row=3, column=0, padx=5, pady=5, sticky=tk.W)
        self.session_end_var = tk.StringVar()
        self.session_end_entry = tk.Entry(settings_frame, textvariable=self.session_end_var, width=15, font=("Arial", 11))
        self.session_end_entry.grid(row=3, column=1, padx=5, pady=5, sticky=tk.W)
        try:
            from tkcalendar import DateEntry
            tk.Button(settings_frame, text="📅", command=lambda: self.pick_date(self.session_end_entry), font=("Arial", 10)).grid(row=3, column=2)
        except ImportError: pass
        
        settings_frame.grid_columnconfigure(1, weight=1)

        tk.Button(settings_frame, text="Save Settings", command=self.save_settings, font=("Arial", 10, "bold")).grid(row=4, column=0, columnspan=4, pady=15)

        # Multi-language (Placeholder)
        lang_frame = tk.LabelFrame(self.main_frame, text="Language", padx=10, pady=10, bg="white", font=("Arial", 12))
        lang_frame.pack(fill=tk.X, pady=10)
        tk.Label(lang_frame, text="Current Language:", font=("Arial", 11)).pack(side=tk.LEFT, padx=5)
        self.lang_var = tk.StringVar(value="English")
        lang_options = ["English", "Urdu"]
        lang_combo = ttk.Combobox(lang_frame, textvariable=self.lang_var, values=lang_options, state="readonly", width=15)
        lang_combo.pack(side=tk.LEFT, padx=5)
        lang_combo.bind("<<ComboboxSelected>>", self.change_language)
        tk.Button(lang_frame, text="Apply Language", command=self.apply_language_change, font=("Arial", 10)).pack(side=tk.LEFT, padx=10)


    def pick_date(self, entry_widget):
         if 'DateEntry' in globals(): # Check if tkcalendar is available
            from tkcalendar import DateEntry
            top = tk.Toplevel(self)
            cal = DateEntry(top, width=12, background='darkblue', foreground='white', borderwidth=2, date_pattern='yyyy-mm-dd')
            cal.pack(padx=10, pady=10)
            def set_date():
                entry_widget.delete(0, tk.END)
                entry_widget.insert(0, cal.get_date())
                top.destroy()
            tk.Button(top, text="Select", command=set_date).pack(pady=5)
            top.transient(self)
            top.grab_set()
            top.mainloop()

    def load_settings(self):
        school_name = get_setting('school_name')
        logo_path = get_setting('logo_path')
        session_start = get_setting('session_start')
        session_end = get_setting('session_end')

        self.school_name_entry.delete(0, tk.END)
        self.school_name_entry.insert(0, school_name if school_name else "")
        
        self.logo_path_var.set(logo_path if logo_path else "")
        self.update_logo_display(logo_path)

        self.session_start_var.set(session_start if session_start else "")
        self.session_end_var.set(session_end if session_end else "")
        
        # Set language based on system or a stored preference (placeholder)
        self.lang_var.set("English") 

    def select_logo(self):
        filepath = filedialog.askopenfilename(title="Select School Logo", filetypes=[("Image Files", "*.png *.jpg *.jpeg")])
        if filepath:
            self.logo_path_var.set(filepath)
            self.update_logo_display(filepath)

    def update_logo_display(self, logo_path):
        if logo_path and os.path.exists(logo_path):
            try:
                img = Image.open(logo_path)
                img.thumbnail((80, 80))
                self.logo_photo = ImageTk.PhotoImage(img)
                self.logo_display_label.config(image=self.logo_photo, text="")
            except Exception as e:
                print(f"Error loading logo preview: {e}")
                self.logo_display_label.config(image='', text="[Load Error]")
        else:
            self.logo_display_label.config(image='', text="[No Logo]")

    def save_settings(self):
        school_name = self.school_name_entry.get().strip()
        logo_path = self.logo_path_var.get()
        session_start = self.session_start_var.get()
        session_end = self.session_end_var.get()

        if not school_name:
            messagebox.showwarning("Input Error", "School/Madrasa Name is required.")
            return
        if not session_start or not session_end:
             messagebox.showwarning("Input Error", "Session dates are required.")
             return

        try:
            datetime.strptime(session_start, '%Y-%m-%d')
            datetime.strptime(session_end, '%Y-%m-%d')
        except ValueError:
            messagebox.showwarning("Input Error", "Session dates must be in YYYY-MM-DD format.")
            return

        update_setting('school_name', school_name)
        update_setting('logo_path', logo_path)
        update_setting('session_start', session_start)
        update_setting('session_end', session_end)
        
        messagebox.showinfo("Success", "Settings saved successfully.")
        # Update sidebar and potentially other parts of the UI that depend on settings
        self.controller.update_sidebar_info()


    def change_language(self, event=None):
        # This is a placeholder. Actual implementation requires translating all labels and UI elements.
        pass

    def apply_language_change(self):
        messagebox.showinfo("Language Applied", f"Language set to {self.lang_var.get()}. (Note: Full language support requires UI element translation.)")
        # Apply language changes to all relevant widgets if implemented


class MainApplicationController:
    def __init__(self, root):
        self.root = root
        self.root.title("School Management System")
        self.root.geometry("1200x700") # Initial size

        # Create frames/views
        self.sidebar = Sidebar(root, self)
        self.main_area = MainArea(root) # Placeholder for currently displayed content
        self.dashboard_view = DashboardView(self.main_area, self)
        self.students_view = PeopleView(self.main_area, self, "student")
        self.teachers_view = PeopleView(self.main_area, self, "teacher")
        self.academics_view = AcademicsView(self.main_area, self)
        self.attendance_view = AttendanceView(self.main_area, self)
        self.exam_view = ExamView(self.main_area, self)
        self.fees_view = FeesView(self.main_area, self)
        self.salary_view = SalaryView(self.main_area, self)
        self.transaction_view = TransactionView(self.main_area, self)
        self.reports_view = ReportsView(self.main_area, self)
        self.utilities_view = UtilitiesView(self.main_area, self)
        self.settings_view = SettingsView(self.main_area, self)

        self.current_view = None
        self.frames = {} # Dictionary to hold view instances

        self.setup_gui()
        self.show_frame("show_dashboard") # Show dashboard by default

    def setup_gui(self):
        self.sidebar.pack(side=tk.LEFT, fill=tk.Y, padx=5, pady=5)
        self.main_area.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True, padx=5, pady=5)

        self.frames = {
            "show_dashboard": self.dashboard_view,
            "show_students": self.students_view,
            "show_teachers": self.teachers_view,
            "show_academics": self.academics_view,
            "show_attendance": self.attendance_view,
            "show_exams": self.exam_view,
            "show_fees": self.fees_view,
            "show_salaries": self.salary_view,
            "show_transactions": self.transaction_view,
            "show_reports": self.reports_view,
            "show_utilities": self.utilities_view,
            "show_settings": self.settings_view,
        }
        
        # Add placeholder frames to the controller's frames dict for easier management
        # This ensures all views are initialized and accessible
        for key, view in self.frames.items():
            pass # Views are already created above

        # Set user role (e.g., from login, currently hardcoded for demo)
        # self.sidebar.set_user_role('Admin')

    def show_frame(self, view_name, **kwargs):
        if self.current_view:
            self.current_view.pack_forget()
        
        new_view = self.frames.get(view_name)
        if new_view:
            self.current_view = new_view
            self.current_view.pack(fill=tk.BOTH, expand=True)
            # Handle specific actions passed as kwargs (e.g., when clicking 'Add Student' from dashboard)
            if view_name == "show_students" and kwargs.get("action") == "add":
                 self.students_view.show_add_form()
            elif view_name == "show_attendance" and kwargs.get("action") == "mark":
                 self.attendance_view.show_attendance_sheet()
            elif view_name == "show_fees" and kwargs.get("action") == "collect":
                 # Needs to select the correct student first, or open the form directly
                 # For now, just show the fees view. Further interaction needed to select student.
                 pass # Fees view handles its own flow upon loading

            # Refresh data if necessary (e.g., after adding a class, the class combo in Attendance might need updating)
            if hasattr(new_view, 'load_data'):
                 new_view.load_data()
            if hasattr(new_view, 'load_classes_for_selection'):
                 new_view.load_classes_for_selection()
            if hasattr(new_view, 'load_students_for_fee_collection'):
                 new_view.load_students_for_fee_collection()
            if hasattr(new_view, 'load_teachers_for_salary'):
                 new_view.load_teachers_for_salary()

        else:
            print(f"Error: View '{view_name}' not found.")

    def update_sidebar_info(self):
         # Reload logo and school name in the sidebar
         self.sidebar.destroy() # Remove old sidebar
         self.sidebar = Sidebar(self.root, self) # Recreate sidebar
         self.sidebar.pack(side=tk.LEFT, fill=tk.Y, padx=5, pady=5)
         # Potentially update user role if needed
         # self.sidebar.set_user_role(get_setting('current_user_role') or 'Admin')


def main():
    # Ensure database exists
    initialize_db()
    
    root = tk.Tk()
    app = MainApplicationController(root)
    
    # Add graceful exit handling
    def on_closing():
        if messagebox.askokcancel("Quit", "Do you want to quit?"):
            root.destroy()

    root.protocol("WM_DELETE_WINDOW", on_closing)
    root.mainloop()

if __name__ == "__main__":
    import sys
    import shutil
    import json # for backup/restore
    
    # Check for necessary libraries and provide guidance if missing
    try:
        from PIL import Image, ImageTk, ImageDraw, ImageFont
        import qrcode
        import matplotlib
        matplotlib.use('TkAgg')
        from matplotlib.backends.backend_tkagg import FigureCanvasTkAgg
        from matplotlib.figure import Figure
        import numpy as np
        # Optional: tkcalendar for date pickers
        try:
            from tkcalendar import DateEntry, Calendar 
        except ImportError:
             print("Note: 'tkcalendar' library not found. Date entry will be basic.")
             print("Install it using: pip install tkcalendar")

    except ImportError as e:
        missing_module = str(e).split("'")[1]
        messagebox.showerror("Missing Library", f"The application requires the '{missing_module}' library.\nPlease install it using:\n\npip install {missing_module}\n\n(For charts, also install:\npip install matplotlib numpy qrcode)")
        sys.exit(1)

    main()