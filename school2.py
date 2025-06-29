import tkinter as tk
from tkinter import ttk, messagebox, filedialog, PhotoImage, simpledialog
from tkinter.font import Font
import sqlite3
import os
import shutil
import datetime
from datetime import datetime as dt
from PIL import Image, ImageTk, ImageDraw, ImageFont
import io
import base64
import json
import webbrowser
import matplotlib.pyplot as plt
from matplotlib.backends.backend_tkagg import FigureCanvasTkAgg
import random
import string
import tempfile
import csv
from fpdf import FPDF
import qrcode

class SchoolManagementSystem:
    def __init__(self, root):
        self.root = root
        self.root.title("School and Madrasa Management System")
        self.root.geometry("1200x700")
        self.root.state('zoomed')
        
        # Database setup
        self.db_name = "school_management.db"
        self.initialize_database()
        
        # UI Settings
        self.current_user = {"id": 1, "username": "admin", "role": "Admin"}  # Simulated login
        self.current_language = "English"  # English/Urdu toggle
        self.school_info = {
            "name": "My School/Madrasa",
            "address": "123 Education Street, Learning City",
            "phone": "03001234567",
            "logo": None
        }
        
        # Load settings
        self.load_settings()
        
        # UI Colors
        self.bg_color = "#f0f0f0"
        self.header_color = "#2c3e50"
        self.sidebar_color = "#34495e"
        self.accent_color = "#3498db"
        self.highlight_color = "#2980b9"
        
        # Fonts
        self.title_font = Font(family="Helvetica", size=16, weight="bold")
        self.subtitle_font = Font(family="Helvetica", size=12, weight="bold")
        self.normal_font = Font(family="Helvetica", size=10)
        
        # Urdu font setup
        try:
            self.urdu_font = ImageFont.truetype("arial.ttf", 12)  # Fallback to Arial if specific Urdu font not available
        except:
            self.urdu_font = ImageFont.load_default()
        
        # Create main containers
        self.create_header()
        self.create_sidebar()
        self.create_main_content()
        
        # Load dashboard by default
        self.show_dashboard()
        
        # Bind window resize event
        self.root.bind("<Configure>", self.on_window_resize)
    
    # ==============================================
    # DATABASE METHODS
    # ==============================================
    
    def initialize_database(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Users table
        cursor.execute('''CREATE TABLE IF NOT EXISTS users (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            username TEXT UNIQUE,
                            password TEXT,
                            full_name TEXT,
                            role TEXT,
                            status TEXT DEFAULT 'Active',
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Students table
        cursor.execute('''CREATE TABLE IF NOT EXISTS students (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            admission_no TEXT UNIQUE,
                            name TEXT,
                            father_name TEXT,
                            gender TEXT,
                            dob TEXT,
                            cnic TEXT,
                            contact TEXT,
                            address TEXT,
                            class TEXT,
                            section TEXT,
                            date_of_joining TEXT,
                            category TEXT,
                            blood_group TEXT,
                            photo BLOB,
                            status TEXT DEFAULT 'Active',
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Teachers table
        cursor.execute('''CREATE TABLE IF NOT EXISTS teachers (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            employee_id TEXT UNIQUE,
                            name TEXT,
                            father_name TEXT,
                            gender TEXT,
                            dob TEXT,
                            cnic TEXT,
                            contact TEXT,
                            address TEXT,
                            qualification TEXT,
                            subject TEXT,
                            date_of_joining TEXT,
                            role TEXT,
                            blood_group TEXT,
                            photo BLOB,
                            status TEXT DEFAULT 'Active',
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Staff table
        cursor.execute('''CREATE TABLE IF NOT EXISTS staff (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            employee_id TEXT UNIQUE,
                            name TEXT,
                            father_name TEXT,
                            gender TEXT,
                            dob TEXT,
                            cnic TEXT,
                            contact TEXT,
                            address TEXT,
                            designation TEXT,
                            date_of_joining TEXT,
                            role TEXT,
                            blood_group TEXT,
                            photo BLOB,
                            status TEXT DEFAULT 'Active',
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Classes table
        cursor.execute('''CREATE TABLE IF NOT EXISTS classes (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            name TEXT UNIQUE,
                            section TEXT,
                            capacity INTEGER,
                            class_teacher_id INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Subjects table
        cursor.execute('''CREATE TABLE IF NOT EXISTS subjects (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            name TEXT,
                            class_id INTEGER,
                            teacher_id INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Attendance table
        cursor.execute('''CREATE TABLE IF NOT EXISTS attendance (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            student_id INTEGER,
                            date TEXT,
                            status TEXT,
                            remarks TEXT,
                            recorded_by INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Exams table
        cursor.execute('''CREATE TABLE IF NOT EXISTS exams (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            name TEXT,
                            exam_date TEXT,
                            class_id INTEGER,
                            total_marks INTEGER,
                            passing_marks INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Exam results table
        cursor.execute('''CREATE TABLE IF NOT EXISTS exam_results (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            exam_id INTEGER,
                            student_id INTEGER,
                            subject_id INTEGER,
                            marks_obtained INTEGER,
                            remarks TEXT,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Fee structure table
        cursor.execute('''CREATE TABLE IF NOT EXISTS fee_structure (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            class_id INTEGER,
                            category TEXT,
                            fee_name TEXT,
                            amount REAL,
                            frequency TEXT,
                            due_date TEXT,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Fee payments table
        cursor.execute('''CREATE TABLE IF NOT EXISTS fee_payments (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            student_id INTEGER,
                            fee_id INTEGER,
                            amount_paid REAL,
                            payment_date TEXT,
                            payment_mode TEXT,
                            receipt_no TEXT,
                            remarks TEXT,
                            recorded_by INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Salary structure table
        cursor.execute('''CREATE TABLE IF NOT EXISTS salary_structure (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            employee_id INTEGER,
                            employee_type TEXT,
                            basic_salary REAL,
                            allowances REAL,
                            deductions REAL,
                            effective_from TEXT,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Salary payments table
        cursor.execute('''CREATE TABLE IF NOT EXISTS salary_payments (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            employee_id INTEGER,
                            employee_type TEXT,
                            amount_paid REAL,
                            payment_date TEXT,
                            payment_mode TEXT,
                            month TEXT,
                            year TEXT,
                            remarks TEXT,
                            recorded_by INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Expenses table
        cursor.execute('''CREATE TABLE IF NOT EXISTS expenses (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            amount REAL,
                            category TEXT,
                            description TEXT,
                            expense_date TEXT,
                            recorded_by INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Income table
        cursor.execute('''CREATE TABLE IF NOT EXISTS income (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            amount REAL,
                            category TEXT,
                            description TEXT,
                            income_date TEXT,
                            recorded_by INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Events table
        cursor.execute('''CREATE TABLE IF NOT EXISTS events (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            title TEXT,
                            description TEXT,
                            event_date TEXT,
                            created_by INTEGER,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Settings table
        cursor.execute('''CREATE TABLE IF NOT EXISTS settings (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            setting_name TEXT UNIQUE,
                            setting_value TEXT,
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Activity log table
        cursor.execute('''CREATE TABLE IF NOT EXISTS activity_log (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_id INTEGER,
                            activity TEXT,
                            module TEXT,
                            timestamp TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        # Notifications table
        cursor.execute('''CREATE TABLE IF NOT EXISTS notifications (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            title TEXT,
                            message TEXT,
                            recipient_type TEXT,
                            recipient_id INTEGER,
                            status TEXT DEFAULT 'Unread',
                            created_at TEXT DEFAULT CURRENT_TIMESTAMP
                        )''')
        
        conn.commit()
        conn.close()
        
        # Insert default data if not exists
        self.insert_default_data()
    
    def insert_default_data(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Check if admin user exists
        cursor.execute("SELECT COUNT(*) FROM users WHERE username = 'admin'")
        if cursor.fetchone()[0] == 0:
            cursor.execute("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)",
                          ("admin", "admin123", "Administrator", "Admin"))
        
        # Check if classes exist
        cursor.execute("SELECT COUNT(*) FROM classes")
        if cursor.fetchone()[0] == 0:
            classes = [
                ("Class 1", "A", 30, None),
                ("Class 2", "A", 30, None),
                ("Class 3", "A", 30, None),
                ("Class 4", "A", 30, None),
                ("Class 5", "A", 30, None),
                ("Class 6", "A", 30, None),
                ("Class 7", "A", 30, None),
                ("Class 8", "A", 30, None),
                ("Class 9", "A", 30, None),
                ("Class 10", "A", 30, None)
            ]
            cursor.executemany("INSERT INTO classes (name, section, capacity, class_teacher_id) VALUES (?, ?, ?, ?)", classes)
        
        # Check if subjects exist
        cursor.execute("SELECT COUNT(*) FROM subjects")
        if cursor.fetchone()[0] == 0:
            subjects = [
                ("Mathematics", 1, None),
                ("English", 1, None),
                ("Urdu", 1, None),
                ("Islamiat", 1, None),
                ("Science", 1, None),
                ("Social Studies", 1, None),
                ("Computer Science", 1, None),
                ("Physics", 8, None),
                ("Chemistry", 8, None),
                ("Biology", 8, None)
            ]
            cursor.executemany("INSERT INTO subjects (name, class_id, teacher_id) VALUES (?, ?, ?)", subjects)
        
        # Check if fee structure exists
        cursor.execute("SELECT COUNT(*) FROM fee_structure")
        if cursor.fetchone()[0] == 0:
            fees = [
                (1, "General", "Monthly Tuition Fee", 1000, "Monthly", "5"),
                (1, "General", "Admission Fee", 500, "One-time", "1"),
                (1, "Deserving", "Monthly Tuition Fee", 500, "Monthly", "5"),
                (5, "General", "Monthly Tuition Fee", 1500, "Monthly", "5"),
                (10, "General", "Monthly Tuition Fee", 2000, "Monthly", "5")
            ]
            cursor.executemany("INSERT INTO fee_structure (class_id, category, fee_name, amount, frequency, due_date) VALUES (?, ?, ?, ?, ?, ?)", fees)
        
        # Check if settings exist
        cursor.execute("SELECT COUNT(*) FROM settings")
        if cursor.fetchone()[0] == 0:
            settings = [
                ("school_name", "My School/Madrasa"),
                ("school_address", "123 Education Street, Learning City"),
                ("school_phone", "03001234567"),
                ("current_session", datetime.datetime.now().strftime("%Y")),
                ("session_start_date", "2023-04-01"),
                ("session_end_date", "2024-03-31"),
                ("default_language", "English"),
                ("receipt_prefix", "REC"),
                ("fee_due_day", "5"),
                ("currency_symbol", "Rs."),
                ("id_card_template", ""),
                ("report_card_template", "")
            ]
            cursor.executemany("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?)", settings)
        
        conn.commit()
        conn.close()
    
    def load_settings(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("SELECT setting_name, setting_value FROM settings")
        settings = cursor.fetchall()
        
        for setting in settings:
            if setting[0] == "school_name":
                self.school_info["name"] = setting[1]
            elif setting[0] == "school_address":
                self.school_info["address"] = setting[1]
            elif setting[0] == "school_phone":
                self.school_info["phone"] = setting[1]
            elif setting[0] == "default_language":
                self.current_language = setting[1]
        
        conn.close()
    
    def update_setting(self, setting_name, setting_value):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("INSERT OR REPLACE INTO settings (setting_name, setting_value) VALUES (?, ?)",
                      (setting_name, setting_value))
        conn.commit()
        conn.close()
        
        # Update in-memory settings
        if setting_name == "school_name":
            self.school_info["name"] = setting_value
        elif setting_name == "school_address":
            self.school_info["address"] = setting_value
        elif setting_name == "school_phone":
            self.school_info["phone"] = setting_value
        elif setting_name == "default_language":
            self.current_language = setting_value
    
    # ==============================================
    # UI COMPONENTS
    # ==============================================
    
    def create_header(self):
        self.header = tk.Frame(self.root, bg=self.header_color, height=80)
        self.header.pack(side=tk.TOP, fill=tk.X)
        
        # School logo and name
        self.logo_label = tk.Label(self.header, text=self.school_info["name"], font=self.title_font, 
                                  fg="white", bg=self.header_color)
        self.logo_label.pack(side=tk.LEFT, padx=20, pady=10)
        
        # User info and controls
        control_frame = tk.Frame(self.header, bg=self.header_color)
        control_frame.pack(side=tk.RIGHT, padx=20)
        
        # Notifications button
        self.notification_btn = tk.Button(control_frame, text="🔔", font=self.normal_font,
                                        command=self.show_notifications, bg=self.header_color, fg="white",
                                        bd=0, relief=tk.FLAT)
        self.notification_btn.pack(side=tk.LEFT, padx=5)
        
        # Language toggle
        self.language_btn = tk.Button(control_frame, text="English/اردو", font=self.normal_font,
                                     command=self.toggle_language, bg=self.accent_color, fg="white")
        self.language_btn.pack(side=tk.LEFT, padx=5)
        
        # User info
        self.user_label = tk.Label(control_frame, text=f"User: {self.current_user['username']} ({self.current_user['role']})", 
                                  font=self.normal_font, fg="white", bg=self.header_color)
        self.user_label.pack(side=tk.LEFT, padx=5)
        
        # Logout button
        self.logout_btn = tk.Button(control_frame, text="Logout", font=self.normal_font,
                                   command=self.logout, bg="#e74c3c", fg="white")
        self.logout_btn.pack(side=tk.LEFT, padx=5)
    
    def create_sidebar(self):
        self.sidebar = tk.Frame(self.root, bg=self.sidebar_color, width=220)
        self.sidebar.pack(side=tk.LEFT, fill=tk.Y)
        
        # Dashboard button
        self.dashboard_btn = tk.Button(self.sidebar, text="Dashboard", font=self.subtitle_font,
                                      command=self.show_dashboard, bg=self.sidebar_color, fg="white",
                                      bd=0, relief=tk.FLAT, anchor=tk.W, padx=20, pady=10)
        self.dashboard_btn.pack(fill=tk.X)
        
        # People Management
        people_btn = tk.Button(self.sidebar, text="People Management", font=self.subtitle_font,
                             command=self.show_people_management, bg=self.sidebar_color, fg="white",
                             bd=0, relief=tk.FLAT, anchor=tk.W, padx=20, pady=10)
        people_btn.pack(fill=tk.X)
        
        # Academic Management
        academic_btn = tk.Button(self.sidebar, text="Academic Management", font=self.subtitle_font,
                               command=self.show_academic_management, bg=self.sidebar_color, fg="white",
                               bd=0, relief=tk.FLAT, anchor=tk.W, padx=20, pady=10)
        academic_btn.pack(fill=tk.X)
        
        # Financial Management
        finance_btn = tk.Button(self.sidebar, text="Financial Management", font=self.subtitle_font,
                              command=self.show_financial_management, bg=self.sidebar_color, fg="white",
                              bd=0, relief=tk.FLAT, anchor=tk.W, padx=20, pady=10)
        finance_btn.pack(fill=tk.X)
        
        # Reports
        reports_btn = tk.Button(self.sidebar, text="Reports & Utilities", font=self.subtitle_font,
                              command=self.show_reports_utilities, bg=self.sidebar_color, fg="white",
                              bd=0, relief=tk.FLAT, anchor=tk.W, padx=20, pady=10)
        reports_btn.pack(fill=tk.X)
        
        # System Settings
        settings_btn = tk.Button(self.sidebar, text="System Settings", font=self.subtitle_font,
                               command=self.show_system_settings, bg=self.sidebar_color, fg="white",
                               bd=0, relief=tk.FLAT, anchor=tk.W, padx=20, pady=10)
        settings_btn.pack(fill=tk.X)
        
        # Help/About
        help_btn = tk.Button(self.sidebar, text="Help & About", font=self.subtitle_font,
                           command=self.show_help_about, bg=self.sidebar_color, fg="white",
                           bd=0, relief=tk.FLAT, anchor=tk.W, padx=20, pady=10)
        help_btn.pack(fill=tk.X)
        
        # Add some padding at the bottom
        tk.Frame(self.sidebar, height=20, bg=self.sidebar_color).pack(side=tk.BOTTOM)
    
    def create_main_content(self):
        self.main_content = tk.Frame(self.root, bg=self.bg_color)
        self.main_content.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True)
        
        # Content frame where all modules will be displayed
        self.content_frame = tk.Frame(self.main_content, bg=self.bg_color)
        self.content_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
    
    def clear_content_frame(self):
        for widget in self.content_frame.winfo_children():
            widget.destroy()
    
    # ==============================================
    # MODULE: DASHBOARD
    # ==============================================
    
    def show_dashboard(self):
        self.clear_content_frame()
        self.log_activity("Viewed Dashboard", "Dashboard")
        
        # Dashboard title
        title_label = tk.Label(self.content_frame, text="Dashboard", font=self.title_font, bg=self.bg_color)
        title_label.pack(anchor=tk.NW, padx=10, pady=10)
        
        # Stats frame
        stats_frame = tk.Frame(self.content_frame, bg=self.bg_color)
        stats_frame.pack(fill=tk.X, padx=10, pady=10)
        
        # Get stats from database
        total_students = self.get_total_students()
        total_teachers = self.get_total_teachers()
        total_staff = self.get_total_staff()
        fee_collection = self.get_monthly_fee_collection()
        pending_fees = self.get_pending_fees()
        today_attendance = self.get_today_attendance()
        
        # Stats cards
        stats_data = [
            ("Total Students", total_students, "#3498db"),
            ("Total Teachers", total_teachers, "#e74c3c"),
            ("Total Staff", total_staff, "#2ecc71"),
            ("Fee Collection (This Month)", f"Rs. {fee_collection:,.2f}", "#f39c12"),
            ("Pending Fees", f"Rs. {pending_fees:,.2f}", "#9b59b6"),
            ("Today's Attendance", f"{today_attendance['present']}/{today_attendance['total']} ({today_attendance['percentage']:.1f}%)", "#1abc9c")
        ]
        
        for i, (title, value, color) in enumerate(stats_data):
            card = tk.Frame(stats_frame, bg=color, bd=2, relief=tk.RAISED)
            card.grid(row=0, column=i, padx=5, sticky="nsew")
            stats_frame.columnconfigure(i, weight=1)
            
            title_label = tk.Label(card, text=title, font=self.normal_font, bg=color, fg="white")
            title_label.pack(pady=(5, 0))
            
            value_label = tk.Label(card, text=value, font=("Helvetica", 14, "bold"), bg=color, fg="white")
            value_label.pack(pady=(0, 5))
        
        # Charts frame
        charts_frame = tk.Frame(self.content_frame, bg=self.bg_color)
        charts_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Left chart - Fee Collection
        fee_chart_frame = tk.Frame(charts_frame, bg="white", bd=2, relief=tk.GROOVE)
        fee_chart_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=5)
        
        fee_chart_title = tk.Label(fee_chart_frame, text="Monthly Fee Collection", font=self.subtitle_font, bg="white")
        fee_chart_title.pack(anchor=tk.NW, padx=10, pady=5)
        
        # Generate fee collection chart
        fee_data = self.get_fee_collection_data()
        fig1 = plt.Figure(figsize=(5, 3), dpi=80)
        ax1 = fig1.add_subplot(111)
        ax1.bar([x[0] for x in fee_data], [x[1] for x in fee_data], color='#3498db')
        ax1.set_title('Last 6 Months Fee Collection')
        ax1.set_ylabel('Amount (Rs.)')
        
        fee_chart = FigureCanvasTkAgg(fig1, master=fee_chart_frame)
        fee_chart.draw()
        fee_chart.get_tk_widget().pack(fill=tk.BOTH, expand=True)
        
        # Right chart - Attendance
        attendance_chart_frame = tk.Frame(charts_frame, bg="white", bd=2, relief=tk.GROOVE)
        attendance_chart_frame.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True, padx=5)
        
        attendance_chart_title = tk.Label(attendance_chart_frame, text="Monthly Attendance", font=self.subtitle_font, bg="white")
        attendance_chart_title.pack(anchor=tk.NW, padx=10, pady=5)
        
        # Generate attendance chart
        attendance_data = self.get_attendance_data()
        fig2 = plt.Figure(figsize=(5, 3), dpi=80)
        ax2 = fig2.add_subplot(111)
        ax2.plot([x[0] for x in attendance_data], [x[1] for x in attendance_data], marker='o', color='#2ecc71')
        ax2.set_title('Last 6 Months Attendance')
        ax2.set_ylabel('Attendance %')
        ax2.set_ylim(0, 100)
        
        attendance_chart = FigureCanvasTkAgg(fig2, master=attendance_chart_frame)
        attendance_chart.draw()
        attendance_chart.get_tk_widget().pack(fill=tk.BOTH, expand=True)
        
        # Bottom area with recent activity and upcoming events
        bottom_frame = tk.Frame(self.content_frame, bg=self.bg_color)
        bottom_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Recent activity frame
        activity_frame = tk.Frame(bottom_frame, bg="white", bd=2, relief=tk.GROOVE)
        activity_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=5)
        
        activity_title = tk.Label(activity_frame, text="Recent Activity", font=self.subtitle_font, bg="white")
        activity_title.pack(anchor=tk.NW, padx=10, pady=5)
        
        # Activity list
        activity_list = ttk.Treeview(activity_frame, columns=("timestamp", "activity", "user"), show="headings", height=8)
        activity_list.heading("timestamp", text="Time")
        activity_list.heading("activity", text="Activity")
        activity_list.heading("user", text="User")
        activity_list.column("timestamp", width=120)
        activity_list.column("activity", width=300)
        activity_list.column("user", width=100)
        activity_list.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Add activities
        activities = self.get_recent_activities()
        for activity in activities:
            activity_list.insert("", tk.END, values=activity)
        
        # Upcoming events frame
        events_frame = tk.Frame(bottom_frame, bg="white", bd=2, relief=tk.GROOVE)
        events_frame.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True, padx=5)
        
        events_title = tk.Label(events_frame, text="Upcoming Events", font=self.subtitle_font, bg="white")
        events_title.pack(anchor=tk.NW, padx=10, pady=5)
        
        # Events list
        events_list = ttk.Treeview(events_frame, columns=("date", "event"), show="headings", height=8)
        events_list.heading("date", text="Date")
        events_list.heading("event", text="Event")
        events_list.column("date", width=100)
        events_list.column("event", width=400)
        events_list.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Add events
        upcoming_events = self.get_upcoming_events()
        for event in upcoming_events:
            events_list.insert("", tk.END, values=event)
    
    # ==============================================
    # MODULE: PEOPLE MANAGEMENT
    # ==============================================
    
    def show_people_management(self):
        self.clear_content_frame()
        self.log_activity("Viewed People Management", "People")
        
        # Create notebook for tabs
        notebook = ttk.Notebook(self.content_frame)
        notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Students tab
        students_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(students_tab, text="Students")
        
        # Teachers tab
        teachers_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(teachers_tab, text="Teachers")
        
        # Staff tab
        staff_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(staff_tab, text="Staff")
        
        # Populate each tab
        self.create_students_tab(students_tab)
        self.create_teachers_tab(teachers_tab)
        self.create_staff_tab(staff_tab)
    
    def create_students_tab(self, parent):
        # Top control frame
        control_frame = tk.Frame(parent, bg=self.bg_color)
        control_frame.pack(fill=tk.X, padx=10, pady=10)
        
        # Search frame
        search_frame = tk.Frame(control_frame, bg=self.bg_color)
        search_frame.pack(side=tk.LEFT, fill=tk.X, expand=True)
        
        tk.Label(search_frame, text="Search:", font=self.normal_font, bg=self.bg_color).pack(side=tk.LEFT)
        
        self.student_search_entry = tk.Entry(search_frame, width=30)
        self.student_search_entry.pack(side=tk.LEFT, padx=5)
        self.student_search_entry.bind("<Return>", lambda e: self.search_students())
        
        search_btn = tk.Button(search_frame, text="Search", command=self.search_students)
        search_btn.pack(side=tk.LEFT, padx=5)
        
        # Filter frame
        filter_frame = tk.Frame(control_frame, bg=self.bg_color)
        filter_frame.pack(side=tk.RIGHT)
        
        tk.Label(filter_frame, text="Filter by:", font=self.normal_font, bg=self.bg_color).pack(side=tk.LEFT)
        
        self.student_class_filter = ttk.Combobox(filter_frame, values=["All Classes"] + self.get_class_list(), state="readonly")
        self.student_class_filter.pack(side=tk.LEFT, padx=5)
        self.student_class_filter.set("All Classes")
        
        self.student_status_filter = ttk.Combobox(filter_frame, values=["All", "Active", "Inactive"], state="readonly")
        self.student_status_filter.pack(side=tk.LEFT, padx=5)
        self.student_status_filter.set("Active")
        
        filter_btn = tk.Button(filter_frame, text="Apply", command=self.filter_students)
        filter_btn.pack(side=tk.LEFT, padx=5)
        
        # Buttons frame
        btn_frame = tk.Frame(parent, bg=self.bg_color)
        btn_frame.pack(fill=tk.X, padx=10, pady=5)
        
        add_btn = tk.Button(btn_frame, text="Add New Student", command=self.add_new_student, bg="#2ecc71", fg="white")
        add_btn.pack(side=tk.LEFT, padx=5)
        
        edit_btn = tk.Button(btn_frame, text="Edit Student", command=self.edit_student, bg="#3498db", fg="white")
        edit_btn.pack(side=tk.LEFT, padx=5)
        
        delete_btn = tk.Button(btn_frame, text="Delete Student", command=self.delete_student, bg="#e74c3c", fg="white")
        delete_btn.pack(side=tk.LEFT, padx=5)
        
        view_btn = tk.Button(btn_frame, text="View Details", command=self.view_student_details, bg="#9b59b6", fg="white")
        view_btn.pack(side=tk.LEFT, padx=5)
        
        # Students list
        list_frame = tk.Frame(parent, bg=self.bg_color)
        list_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        columns = ("ID", "Admission No", "Name", "Father Name", "Class", "Contact", "Status")
        self.students_tree = ttk.Treeview(list_frame, columns=columns, show="headings", selectmode="browse")
        
        for col in columns:
            self.students_tree.heading(col, text=col)
            self.students_tree.column(col, width=100, anchor=tk.W)
        
        self.students_tree.pack(fill=tk.BOTH, expand=True)
        
        # Add scrollbar
        scrollbar = ttk.Scrollbar(list_frame, orient="vertical", command=self.students_tree.yview)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        self.students_tree.configure(yscrollcommand=scrollbar.set)
        
        # Load students data
        self.load_students_data()
    
    def load_students_data(self, search_term=None, class_filter="All Classes", status_filter="Active"):
        self.students_tree.delete(*self.students_tree.get_children())
        
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        query = """SELECT id, admission_no, name, father_name, class, contact, status 
                   FROM students"""
        conditions = []
        params = []
        
        if search_term:
            conditions.append("(name LIKE ? OR admission_no LIKE ? OR father_name LIKE ? OR contact LIKE ?)")
            params.extend([f"%{search_term}%"] * 4)
        
        if class_filter != "All Classes":
            conditions.append("class = ?")
            params.append(class_filter)
        
        if status_filter != "All":
            conditions.append("status = ?")
            params.append(status_filter)
        
        if conditions:
            query += " WHERE " + " AND ".join(conditions)
        
        query += " ORDER BY class, name"
        
        cursor.execute(query, params)
        students = cursor.fetchall()
        
        for student in students:
            self.students_tree.insert("", tk.END, values=student)
        
        conn.close()
    
    def search_students(self):
        search_term = self.student_search_entry.get()
        class_filter = self.student_class_filter.get()
        status_filter = self.student_status_filter.get()
        self.load_students_data(search_term, class_filter, status_filter)
    
    def filter_students(self):
        class_filter = self.student_class_filter.get()
        status_filter = self.student_status_filter.get()
        self.load_students_data(None, class_filter, status_filter)
    
    def add_new_student(self):
        self.student_form_window("Add New Student")
    
    def edit_student(self):
        selected = self.students_tree.focus()
        if not selected:
            messagebox.showwarning("Warning", "Please select a student to edit")
            return
        
        student_id = self.students_tree.item(selected)['values'][0]
        self.student_form_window("Edit Student", student_id)
    
    def view_student_details(self):
        selected = self.students_tree.focus()
        if not selected:
            messagebox.showwarning("Warning", "Please select a student to view")
            return
        
        student_id = self.students_tree.item(selected)['values'][0]
        self.view_student_window(student_id)
    
    def delete_student(self):
        selected = self.students_tree.focus()
        if not selected:
            messagebox.showwarning("Warning", "Please select a student to delete")
            return
        
        student_id = self.students_tree.item(selected)['values'][0]
        student_name = self.students_tree.item(selected)['values'][2]
        
        if messagebox.askyesno("Confirm Delete", f"Are you sure you want to delete {student_name}?"):
            conn = sqlite3.connect(self.db_name)
            cursor = conn.cursor()
            
            try:
                # First delete dependent records
                cursor.execute("DELETE FROM attendance WHERE student_id = ?", (student_id,))
                cursor.execute("DELETE FROM exam_results WHERE student_id = ?", (student_id,))
                cursor.execute("DELETE FROM fee_payments WHERE student_id = ?", (student_id,))
                
                # Then delete the student
                cursor.execute("DELETE FROM students WHERE id = ?", (student_id,))
                conn.commit()
                
                self.load_students_data()
                messagebox.showinfo("Success", "Student deleted successfully")
                self.log_activity(f"Deleted student: {student_name}", "Students")
            except sqlite3.Error as e:
                messagebox.showerror("Error", f"Failed to delete student: {str(e)}")
            finally:
                conn.close()
    
    def student_form_window(self, title, student_id=None):
        form_window = tk.Toplevel(self.root)
        form_window.title(title)
        form_window.geometry("800x700")
        
        # Form frame with scrollbar
        main_frame = tk.Frame(form_window)
        main_frame.pack(fill=tk.BOTH, expand=True)
        
        canvas = tk.Canvas(main_frame)
        scrollbar = ttk.Scrollbar(main_frame, orient="vertical", command=canvas.yview)
        scrollable_frame = tk.Frame(canvas)
        
        scrollable_frame.bind(
            "<Configure>",
            lambda e: canvas.configure(
                scrollregion=canvas.bbox("all")
            )
        )
        
        canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
        canvas.configure(yscrollcommand=scrollbar.set)
        
        canvas.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")
        
        # Form frame
        form_frame = tk.Frame(scrollable_frame, padx=20, pady=20)
        form_frame.pack(fill=tk.BOTH, expand=True)
        
        # Photo frame
        photo_frame = tk.Frame(form_frame)
        photo_frame.grid(row=0, column=0, rowspan=6, padx=10, pady=10, sticky="n")
        
        self.student_photo_label = tk.Label(photo_frame, text="Student Photo", width=20, height=10, relief=tk.SUNKEN)
        self.student_photo_label.pack()
        
        upload_btn = tk.Button(photo_frame, text="Upload Photo", command=lambda: self.upload_photo(self.student_photo_label))
        upload_btn.pack(pady=5)
        
        # Form fields
        fields = [
            ("Admission No", 0, 1, "entry"),
            ("Name", 1, 1, "entry"),
            ("Father Name", 2, 1, "entry"),
            ("Gender", 3, 1, "combobox", ["Male", "Female", "Other"]),
            ("Date of Birth", 4, 1, "entry"),  # Would be better with date picker
            ("CNIC", 5, 1, "entry"),
            ("Contact", 6, 1, "entry"),
            ("Address", 7, 1, "entry"),
            ("Class", 8, 1, "combobox", self.get_class_list()),
            ("Section", 9, 1, "combobox", ["A", "B", "C"]),
            ("Date of Joining", 10, 1, "entry"),
            ("Category", 11, 1, "combobox", ["General", "Deserving", "Orphan"]),
            ("Blood Group", 12, 1, "combobox", ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-", "Unknown"])
        ]
        
        self.student_form_entries = {}
        
        for i, (label, row, col, field_type, *options) in enumerate(fields):
            tk.Label(form_frame, text=label).grid(row=row, column=col, sticky="e", padx=5, pady=5)
            
            if field_type == "entry":
                entry = tk.Entry(form_frame)
                entry.grid(row=row, column=col+1, sticky="w", padx=5, pady=5)
            elif field_type == "combobox":
                entry = ttk.Combobox(form_frame, values=options[0], state="readonly")
                entry.grid(row=row, column=col+1, sticky="w", padx=5, pady=5)
                if options[0]:  # Set default value if available
                    entry.set(options[0][0])
            
            self.student_form_entries[label] = entry
        
        # Status checkbox
        self.student_status = tk.IntVar(value=1)
        tk.Checkbutton(form_frame, text="Active", variable=self.student_status).grid(row=13, column=2, sticky="w")
        
        # Button frame
        btn_frame = tk.Frame(form_frame)
        btn_frame.grid(row=14, column=0, columnspan=4, pady=20)
        
        save_btn = tk.Button(btn_frame, text="Save", command=lambda: self.save_student(student_id, form_window))
        save_btn.pack(side=tk.LEFT, padx=10)
        
        cancel_btn = tk.Button(btn_frame, text="Cancel", command=form_window.destroy)
        cancel_btn.pack(side=tk.LEFT, padx=10)
        
        # If editing, load student data
        if student_id:
            self.load_student_data(student_id)
    
    def load_student_data(self, student_id):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("SELECT * FROM students WHERE id = ?", (student_id,))
        student = cursor.fetchone()
        
        if student:
            # Map database fields to form entries
            field_mapping = {
                "Admission No": 1,
                "Name": 2,
                "Father Name": 3,
                "Gender": 4,
                "Date of Birth": 5,
                "CNIC": 6,
                "Contact": 7,
                "Address": 8,
                "Class": 9,
                "Section": 10,
                "Date of Joining": 11,
                "Category": 12,
                "Blood Group": 13
            }
            
            for label, db_index in field_mapping.items():
                if label in ["Class", "Section", "Category", "Gender", "Blood Group"]:
                    self.student_form_entries[label].set(student[db_index])
                else:
                    self.student_form_entries[label].insert(0, student[db_index])
            
            # Set status
            self.student_status.set(1 if student[15] == "Active" else 0)
            
            # Load photo if exists
            if student[14]:  # photo field
                self.display_image(self.student_photo_label, student[14])
        
        conn.close()
    
    def save_student(self, student_id=None, form_window=None):
        # Get data from form
        data = {
            "admission_no": self.student_form_entries["Admission No"].get(),
            "name": self.student_form_entries["Name"].get(),
            "father_name": self.student_form_entries["Father Name"].get(),
            "gender": self.student_form_entries["Gender"].get(),
            "dob": self.student_form_entries["Date of Birth"].get(),
            "cnic": self.student_form_entries["CNIC"].get(),
            "contact": self.student_form_entries["Contact"].get(),
            "address": self.student_form_entries["Address"].get(),
            "class": self.student_form_entries["Class"].get(),
            "section": self.student_form_entries["Section"].get(),
            "date_of_joining": self.student_form_entries["Date of Joining"].get(),
            "category": self.student_form_entries["Category"].get(),
            "blood_group": self.student_form_entries["Blood Group"].get(),
            "status": "Active" if self.student_status.get() else "Inactive"
        }
        
        # Validate required fields
        required_fields = ["Admission No", "Name", "Father Name", "Class"]
        for field in required_fields:
            if not data[field.lower().replace(" ", "_")]:
                messagebox.showerror("Error", f"{field} is required")
                return
        
        # Get photo data if uploaded
        photo_data = None
        if hasattr(self.student_photo_label, 'image_data'):
            photo_data = self.student_photo_label.image_data
        
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        try:
            if student_id:  # Update existing student
                query = """UPDATE students SET 
                          admission_no=?, name=?, father_name=?, gender=?, dob=?,
                          cnic=?, contact=?, address=?, class=?, section=?,
                          date_of_joining=?, category=?, blood_group=?, 
                          photo=?, status=?
                          WHERE id=?"""
                params = (
                    data["admission_no"], data["name"], data["father_name"], data["gender"], data["dob"],
                    data["cnic"], data["contact"], data["address"], data["class"], data["section"],
                    data["date_of_joining"], data["category"], data["blood_group"],
                    photo_data, data["status"], student_id
                )
                action = "updated"
            else:  # Insert new student
                # Check if admission number already exists
                cursor.execute("SELECT id FROM students WHERE admission_no = ?", (data["admission_no"],))
                if cursor.fetchone():
                    messagebox.showerror("Error", "Admission number already exists")
                    return
                
                query = """INSERT INTO students (
                          admission_no, name, father_name, gender, dob,
                          cnic, contact, address, class, section,
                          date_of_joining, category, blood_group, photo, status
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"""
                params = (
                    data["admission_no"], data["name"], data["father_name"], data["gender"], data["dob"],
                    data["cnic"], data["contact"], data["address"], data["class"], data["section"],
                    data["date_of_joining"], data["category"], data["blood_group"],
                    photo_data, data["status"]
                )
                action = "added"
            
            cursor.execute(query, params)
            conn.commit()
            
            messagebox.showinfo("Success", f"Student {action} successfully")
            self.log_activity(f"Student {action}: {data['name']}", "Students")
            
            # Refresh students list
            self.load_students_data()
            
            # Close form window
            if form_window:
                form_window.destroy()
        
        except sqlite3.IntegrityError as e:
            messagebox.showerror("Error", f"Database error: {str(e)}")
        finally:
            conn.close()
    
    def view_student_window(self, student_id):
        view_window = tk.Toplevel(self.root)
        view_window.title("Student Details")
        view_window.geometry("700x600")
        
        # Get student data
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("""SELECT s.*, 
                         (SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'Present') as present_days,
                         (SELECT COUNT(*) FROM attendance WHERE student_id = s.id) as total_days
                         FROM students s WHERE id = ?""", (student_id,))
        student = cursor.fetchone()
        
        if not student:
            messagebox.showerror("Error", "Student not found")
            view_window.destroy()
            return
        
        # Calculate attendance percentage
        attendance_percentage = 0
        if student[18] > 0:  # total_days
            attendance_percentage = (student[17] / student[18]) * 100  # present_days / total_days
        
        # Main frame
        main_frame = tk.Frame(view_window, padx=20, pady=20)
        main_frame.pack(fill=tk.BOTH, expand=True)
        
        # Photo and basic info frame
        top_frame = tk.Frame(main_frame)
        top_frame.pack(fill=tk.X, pady=10)
        
        # Photo
        photo_frame = tk.Frame(top_frame)
        photo_frame.pack(side=tk.LEFT, padx=10)
        
        photo_label = tk.Label(photo_frame, text="Student Photo", width=15, height=10, relief=tk.SUNKEN)
        photo_label.pack()
        
        if student[14]:  # photo field
            self.display_image(photo_label, student[14])
        
        # Basic info
        info_frame = tk.Frame(top_frame)
        info_frame.pack(side=tk.LEFT, fill=tk.X, expand=True)
        
        info_labels = [
            ("Admission No:", student[1]),
            ("Name:", student[2]),
            ("Father Name:", student[3]),
            ("Class/Section:", f"{student[9]}/{student[10]}"),
            ("Contact:", student[7]),
            ("Status:", student[15])
        ]
        
        for i, (label, value) in enumerate(info_labels):
            tk.Label(info_frame, text=label, font=self.subtitle_font).grid(row=i, column=0, sticky="e", padx=5, pady=2)
            tk.Label(info_frame, text=value).grid(row=i, column=1, sticky="w", padx=5, pady=2)
        
        # Attendance info
        attendance_frame = tk.Frame(info_frame)
        attendance_frame.grid(row=6, column=0, columnspan=2, pady=10)
        
        tk.Label(attendance_frame, text="Attendance:", font=self.subtitle_font).pack(side=tk.LEFT)
        tk.Label(attendance_frame, text=f"{student[17]} days present out of {student[18]} ({attendance_percentage:.1f}%)").pack(side=tk.LEFT)
        
        # Details notebook
        notebook = ttk.Notebook(main_frame)
        notebook.pack(fill=tk.BOTH, expand=True)
        
        # Personal Details tab
        personal_tab = tk.Frame(notebook)
        notebook.add(personal_tab, text="Personal Details")
        
        personal_fields = [
            ("Date of Birth:", student[5]),
            ("Gender:", student[4]),
            ("CNIC:", student[6]),
            ("Address:", student[8]),
            ("Date of Joining:", student[11]),
            ("Category:", student[12]),
            ("Blood Group:", student[13])
        ]
        
        for i, (label, value) in enumerate(personal_fields):
            tk.Label(personal_tab, text=label).grid(row=i, column=0, sticky="e", padx=5, pady=2)
            tk.Label(personal_tab, text=value).grid(row=i, column=1, sticky="w", padx=5, pady=2)
        
        # Academic tab
        academic_tab = tk.Frame(notebook)
        notebook.add(academic_tab, text="Academic")
        
        # Get subjects for the student's class
        cursor.execute("SELECT name FROM subjects WHERE class_id = (SELECT id FROM classes WHERE name = ?)", (student[9],))
        subjects = cursor.fetchall()
        
        tk.Label(academic_tab, text="Subjects:", font=self.subtitle_font).grid(row=0, column=0, sticky="w", pady=5)
        
        for i, subject in enumerate(subjects, start=1):
            tk.Label(academic_tab, text=subject[0]).grid(row=i, column=0, sticky="w", padx=20)
        
        # Financial tab
        financial_tab = tk.Frame(notebook)
        notebook.add(financial_tab, text="Financial")
        
        # Get fee payments
        cursor.execute("""SELECT fp.payment_date, fs.fee_name, fp.amount_paid 
                         FROM fee_payments fp
                         JOIN fee_structure fs ON fp.fee_id = fs.id
                         WHERE fp.student_id = ?""", (student_id,))
        payments = cursor.fetchall()
        
        tk.Label(financial_tab, text="Fee Payments:", font=self.subtitle_font).grid(row=0, column=0, sticky="w", pady=5)
        
        if payments:
            for i, (date, fee_name, amount) in enumerate(payments, start=1):
                tk.Label(financial_tab, text=f"{date}: {fee_name} - Rs. {amount}").grid(row=i, column=0, sticky="w", padx=20)
        else:
            tk.Label(financial_tab, text="No fee payments recorded").grid(row=1, column=0, sticky="w", padx=20)
        
        conn.close()
        
        # Button frame
        btn_frame = tk.Frame(main_frame)
        btn_frame.pack(fill=tk.X, pady=10)
        
        print_btn = tk.Button(btn_frame, text="Print ID Card", command=lambda: self.generate_id_card(student_id))
        print_btn.pack(side=tk.LEFT, padx=5)
        
        close_btn = tk.Button(btn_frame, text="Close", command=view_window.destroy)
        close_btn.pack(side=tk.RIGHT, padx=5)
    
    def create_teachers_tab(self, parent):
        # Similar structure to students tab
        pass
    
    def create_staff_tab(self, parent):
        # Similar structure to students tab
        pass
    
    # ==============================================
    # MODULE: ACADEMIC MANAGEMENT
    # ==============================================
    
    def show_academic_management(self):
        self.clear_content_frame()
        self.log_activity("Viewed Academic Management", "Academic")
        
        # Create notebook for tabs
        notebook = ttk.Notebook(self.content_frame)
        notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Classes & Subjects tab
        classes_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(classes_tab, text="Classes & Subjects")
        
        # Attendance tab
        attendance_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(attendance_tab, text="Attendance")
        
        # Exams & Results tab
        exams_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(exams_tab, text="Exams & Results")
        
        # Timetable tab
        timetable_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(timetable_tab, text="Timetable")
        
        # Populate each tab
        self.create_classes_tab(classes_tab)
        self.create_attendance_tab(attendance_tab)
        self.create_exams_tab(exams_tab)
        self.create_timetable_tab(timetable_tab)
    
    def create_classes_tab(self, parent):
        # Classes management implementation
        pass
    
    def create_attendance_tab(self, parent):
        # Attendance management implementation
        pass
    
    def create_exams_tab(self, parent):
        # Exams and results management implementation
        pass
    
    def create_timetable_tab(self, parent):
        # Timetable management implementation
        pass
    
    # ==============================================
    # MODULE: FINANCIAL MANAGEMENT
    # ==============================================
    
    def show_financial_management(self):
        self.clear_content_frame()
        self.log_activity("Viewed Financial Management", "Financial")
        
        # Create notebook for tabs
        notebook = ttk.Notebook(self.content_frame)
        notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Fee Management tab
        fees_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(fees_tab, text="Fee Management")
        
        # Salary Management tab
        salary_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(salary_tab, text="Salary Management")
        
        # Expenses & Income tab
        expenses_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(expenses_tab, text="Expenses & Income")
        
        # Populate each tab
        self.create_fees_tab(fees_tab)
        self.create_salary_tab(salary_tab)
        self.create_expenses_tab(expenses_tab)
    
    def create_fees_tab(self, parent):
        # Fee management implementation
        pass
    
    def create_salary_tab(self, parent):
        # Salary management implementation
        pass
    
    def create_expenses_tab(self, parent):
        # Expenses and income management implementation
        pass
    
    # ==============================================
    # MODULE: REPORTS & UTILITIES
    # ==============================================
    
    def show_reports_utilities(self):
        self.clear_content_frame()
        self.log_activity("Viewed Reports & Utilities", "Reports")
        
        # Create notebook for tabs
        notebook = ttk.Notebook(self.content_frame)
        notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Reports tab
        reports_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(reports_tab, text="Reports")
        
        # ID Cards tab
        idcards_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(idcards_tab, text="ID Cards")
        
        # Certificates tab
        certs_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(certs_tab, text="Certificates")
        
        # Backup tab
        backup_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(backup_tab, text="Backup/Restore")
        
        # Populate each tab
        self.create_reports_tab(reports_tab)
        self.create_idcards_tab(idcards_tab)
        self.create_certificates_tab(certs_tab)
        self.create_backup_tab(backup_tab)
    
    def create_reports_tab(self, parent):
        # Reports generation implementation
        pass
    
    def create_idcards_tab(self, parent):
        # ID cards generation implementation
        pass
    
    def create_certificates_tab(self, parent):
        # Certificates generation implementation
        pass
    
    def create_backup_tab(self, parent):
        # Backup/restore functionality implementation
        pass
    
    # ==============================================
    # MODULE: SYSTEM SETTINGS
    # ==============================================
    
    def show_system_settings(self):
        self.clear_content_frame()
        self.log_activity("Viewed System Settings", "Settings")
        
        # Settings title
        title_label = tk.Label(self.content_frame, text="System Settings", font=self.title_font, bg=self.bg_color)
        title_label.pack(anchor=tk.NW, padx=10, pady=10)
        
        # Create notebook for settings categories
        notebook = ttk.Notebook(self.content_frame)
        notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # General Settings tab
        general_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(general_tab, text="General Settings")
        
        # User Management tab
        users_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(users_tab, text="User Management")
        
        # Database tab
        database_tab = tk.Frame(notebook, bg=self.bg_color)
        notebook.add(database_tab, text="Database")
        
        # Populate each tab
        self.create_general_settings_tab(general_tab)
        self.create_user_management_tab(users_tab)
        self.create_database_tab(database_tab)
    
    def create_general_settings_tab(self, parent):
        # General settings implementation
        pass
    
    def create_user_management_tab(self, parent):
        # User management implementation
        pass
    
    def create_database_tab(self, parent):
        # Database management implementation
        pass
    
    # ==============================================
    # MODULE: HELP & ABOUT
    # ==============================================
    
    def show_help_about(self):
        self.clear_content_frame()
        self.log_activity("Viewed Help & About", "Help")
        
        # Help/About content
        title_label = tk.Label(self.content_frame, text="School and Madrasa Management System", 
                             font=self.title_font, bg=self.bg_color)
        title_label.pack(pady=20)
        
        version_label = tk.Label(self.content_frame, text="Version 1.0", font=self.subtitle_font, bg=self.bg_color)
        version_label.pack(pady=5)
        
        desc_label = tk.Label(self.content_frame, 
                            text="A comprehensive solution for managing all aspects of an educational institution.\n\n"
                                 "Features include student management, attendance tracking, fee collection,\n"
                                 "exam management, report generation, and more.",
                            font=self.normal_font, bg=self.bg_color)
        desc_label.pack(pady=10)
        
        features_frame = tk.Frame(self.content_frame, bg=self.bg_color)
        features_frame.pack(pady=10)
        
        features = [
            "• Student, Teacher and Staff Management",
            "• Class and Subject Management",
            "• Attendance Tracking",
            "• Exam and Result Management",
            "• Fee Collection and Tracking",
            "• Salary Management",
            "• Expense and Income Tracking",
            "• ID Card and Certificate Generation",
            "• Comprehensive Reporting",
            "• Multi-language Support (English/Urdu)"
        ]
        
        for feature in features:
            tk.Label(features_frame, text=feature, font=self.normal_font, bg=self.bg_color, anchor="w").pack(fill=tk.X)
        
        dev_label = tk.Label(self.content_frame, text="Developed by Your Name", font=self.normal_font, bg=self.bg_color)
        dev_label.pack(pady=10)
        
        contact_label = tk.Label(self.content_frame, text="Contact: your.email@example.com", 
                               font=self.normal_font, bg=self.bg_color)
        contact_label.pack(pady=5)
        
        copyright_label = tk.Label(self.content_frame, text="© 2023 All Rights Reserved", 
                                 font=self.normal_font, bg=self.bg_color)
        copyright_label.pack(pady=10)
    
    # ==============================================
    # UTILITY METHODS
    # ==============================================
    
    def toggle_language(self):
        self.current_language = "Urdu" if self.current_language == "English" else "English"
        self.language_btn.config(text="English/اردو" if self.current_language == "English" else "اردو/English")
        self.update_setting("default_language", self.current_language)
        self.update_ui_language()
        self.log_activity(f"Changed language to {self.current_language}", "System")
    
    def update_ui_language(self):
        # Update UI elements based on language
        # This is a simplified version - in a real app, we'd have translation dictionaries
        
        if self.current_language == "English":
            translations = {
                "Dashboard": "Dashboard",
                "People Management": "People Management",
                "Academic Management": "Academic Management",
                "Financial Management": "Financial Management",
                "Reports & Utilities": "Reports & Utilities",
                "System Settings": "System Settings",
                "Help & About": "Help & About"
            }
        else:
            translations = {
                "Dashboard": "ڈیش بورڈ",
                "People Management": "لوگ مینجمنٹ",
                "Academic Management": "تعلیمی انتظام",
                "Financial Management": "مالی انتظام",
                "Reports & Utilities": "رپورٹس اور افادیت",
                "System Settings": "سسٹم کی ترتیبات",
                "Help & About": "مدد اور بارے میں"
            }
        
        # Update sidebar buttons
        for child in self.sidebar.winfo_children():
            if isinstance(child, tk.Button):
                original_text = child.cget("text")
                if original_text in translations:
                    child.config(text=translations[original_text])
    
    def logout(self):
        if messagebox.askyesno("Logout", "Are you sure you want to logout?"):
            self.current_user = None
            messagebox.showinfo("Logged Out", "You have been logged out successfully")
            self.log_activity("User logged out", "System")
            # In a real app, we would show the login screen here
            # For this example, we'll just close the application
            self.root.destroy()
    
    def show_notifications(self):
        notifications_window = tk.Toplevel(self.root)
        notifications_window.title("Notifications")
        notifications_window.geometry("400x300")
        
        # Get unread notifications
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("""SELECT id, title, message, strftime('%Y-%m-%d %H:%M', created_at) 
                         FROM notifications 
                         WHERE status = 'Unread' 
                         ORDER BY created_at DESC""")
        notifications = cursor.fetchall()
        
        if not notifications:
            tk.Label(notifications_window, text="No new notifications", pady=20).pack()
        else:
            # Mark as read
            notification_ids = [str(n[0]) for n in notifications]
            cursor.execute(f"UPDATE notifications SET status = 'Read' WHERE id IN ({','.join(notification_ids)})")
            conn.commit()
            
            # Display notifications
            for i, (nid, title, message, time) in enumerate(notifications):
                frame = tk.Frame(notifications_window, bd=1, relief=tk.GROOVE)
                frame.pack(fill=tk.X, padx=5, pady=2)
                
                tk.Label(frame, text=title, font=self.subtitle_font).pack(anchor=tk.W)
                tk.Label(frame, text=message).pack(anchor=tk.W)
                tk.Label(frame, text=time, font=("Helvetica", 8)).pack(anchor=tk.E)
        
        conn.close()
        
        close_btn = tk.Button(notifications_window, text="Close", command=notifications_window.destroy)
        close_btn.pack(pady=10)
    
    def log_activity(self, activity, module=None):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("INSERT INTO activity_log (user_id, activity, module) VALUES (?, ?, ?)",
                     (self.current_user['id'], activity, module))
        conn.commit()
        conn.close()
    
    def upload_photo(self, target_label):
        file_path = filedialog.askopenfilename(filetypes=[("Image Files", "*.jpg *.jpeg *.png")])
        if file_path:
            try:
                image = Image.open(file_path)
                image.thumbnail((200, 200))  # Resize to fit
                
                # Convert to bytes for database storage
                buffered = io.BytesIO()
                image.save(buffered, format="JPEG")
                img_bytes = buffered.getvalue()
                
                # Display in UI
                photo = ImageTk.PhotoImage(image)
                target_label.config(image=photo)
                target_label.image = photo  # Keep reference
                target_label.image_data = img_bytes  # Store for saving
                
            except Exception as e:
                messagebox.showerror("Error", f"Failed to load image: {str(e)}")
    
    def display_image(self, target_label, image_data):
        try:
            image = Image.open(io.BytesIO(image_data))
            image.thumbnail((200, 200))
            photo = ImageTk.PhotoImage(image)
            target_label.config(image=photo)
            target_label.image = photo  # Keep reference
            target_label.image_data = image_data  # Store for saving
        except Exception as e:
            messagebox.showerror("Error", f"Failed to display image: {str(e)}")
    
    def generate_id_card(self, student_id):
        # Get student data
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("SELECT name, father_name, class, section, admission_no, photo FROM students WHERE id = ?", (student_id,))
        student = cursor.fetchone()
        conn.close()
        
        if not student:
            messagebox.showerror("Error", "Student not found")
            return
        
        name, father_name, class_name, section, admission_no, photo_data = student
        
        # Create ID card image
        id_card = Image.new('RGB', (600, 400), color='white')
        draw = ImageDraw.Draw(id_card)
        
        # Add school name
        draw.text((10, 10), self.school_info["name"], font=ImageFont.load_default(20), fill='black')
        
        # Add student photo if available
        if photo_data:
            photo = Image.open(io.BytesIO(photo_data))
            photo.thumbnail((150, 150))
            id_card.paste(photo, (400, 50))
        
        # Add student details
        details = [
            f"Name: {name}",
            f"Father: {father_name}",
            f"Class: {class_name}/{section}",
            f"Admission No: {admission_no}",
            f"Valid Until: {datetime.datetime.now().year + 1}"
        ]
        
        for i, detail in enumerate(details, start=2):
            draw.text((10, i * 30), detail, font=ImageFont.load_default(14), fill='black')
        
        # Add QR code with student ID
        qr = qrcode.QRCode(version=1, box_size=4, border=4)
        qr.add_data(f"STUDENT_ID:{student_id}")
        qr.make(fit=True)
        qr_img = qr.make_image(fill_color="black", back_color="white")
        id_card.paste(qr_img, (400, 220))
        
        # Save and show the ID card
        temp_file = tempfile.NamedTemporaryFile(suffix=".png", delete=False)
        id_card.save(temp_file.name)
        temp_file.close()
        
        # Show the generated ID card
        id_card_window = tk.Toplevel(self.root)
        id_card_window.title("Student ID Card")
        
        img = ImageTk.PhotoImage(id_card)
        panel = tk.Label(id_card_window, image=img)
        panel.image = img
        panel.pack()
        
        print_btn = tk.Button(id_card_window, text="Print", command=lambda: self.print_image(temp_file.name))
        print_btn.pack(pady=10)
        
        # In a real app, we would also provide options to save the ID card
        self.log_activity(f"Generated ID card for student: {name}", "Reports")
    
    def print_image(self, image_path):
        try:
            os.startfile(image_path, "print")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to print: {str(e)}")
    
    def on_window_resize(self, event):
        # Handle window resize events
        pass
    
    # ==============================================
    # DATABASE QUERY METHODS
    # ==============================================
    
    def get_total_students(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM students WHERE status = 'Active'")
        count = cursor.fetchone()[0]
        conn.close()
        return count
    
    def get_total_teachers(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM teachers WHERE status = 'Active'")
        count = cursor.fetchone()[0]
        conn.close()
        return count
    
    def get_total_staff(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM staff WHERE status = 'Active'")
        count = cursor.fetchone()[0]
        conn.close()
        return count
    
    def get_monthly_fee_collection(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        current_month = datetime.datetime.now().strftime("%Y-%m")
        cursor.execute("SELECT SUM(amount_paid) FROM fee_payments WHERE strftime('%Y-%m', payment_date) = ?", 
                      (current_month,))
        total = cursor.fetchone()[0] or 0
        conn.close()
        return total
    
    def get_pending_fees(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Get total expected fees for current month
        current_month = datetime.datetime.now().strftime("%Y-%m")
        cursor.execute("""SELECT SUM(fs.amount) 
                         FROM fee_structure fs
                         JOIN students s ON fs.class_id = (SELECT id FROM classes WHERE name = s.class)
                         WHERE s.status = 'Active' AND fs.frequency = 'Monthly'""")
        total_expected = cursor.fetchone()[0] or 0
        
        # Get total collected fees for current month
        cursor.execute("""SELECT SUM(amount_paid) 
                         FROM fee_payments 
                         WHERE strftime('%Y-%m', payment_date) = ?""", (current_month,))
        total_collected = cursor.fetchone()[0] or 0
        
        conn.close()
        return max(0, total_expected - total_collected)
    
    def get_today_attendance(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        
        # Get total active students
        cursor.execute("SELECT COUNT(*) FROM students WHERE status = 'Active'")
        total_students = cursor.fetchone()[0]
        
        # Get present students today
        cursor.execute("""SELECT COUNT(DISTINCT student_id) 
                         FROM attendance 
                         WHERE date = ? AND status = 'Present'""", (today,))
        present_students = cursor.fetchone()[0]
        
        conn.close()
        
        percentage = 0
        if total_students > 0:
            percentage = (present_students / total_students) * 100
        
        return {
            "present": present_students,
            "total": total_students,
            "percentage": percentage
        }
    
    def get_fee_collection_data(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Get fee collection for last 6 months
        cursor.execute("""SELECT strftime('%Y-%m', payment_date) as month, 
                         SUM(amount_paid) as total
                         FROM fee_payments
                         WHERE payment_date >= date('now', '-6 months')
                         GROUP BY month
                         ORDER BY month""")
        
        data = cursor.fetchall()
        conn.close()
        
        if not data:
            # Return dummy data if no real data exists
            current_month = datetime.datetime.now().strftime("%Y-%m")
            months = [(current_month, 0)]
            for i in range(1, 6):
                prev_month = (datetime.datetime.now() - datetime.timedelta(days=30*i)).strftime("%Y-%m")
                months.insert(0, (prev_month, random.randint(5000, 20000)))
            return months
        
        return data
    
    def get_attendance_data(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Get attendance percentage for last 6 months
        cursor.execute("""SELECT strftime('%Y-%m', date) as month, 
                         (COUNT(CASE WHEN status = 'Present' THEN 1 END) * 100.0 / COUNT(*)) as percentage
                         FROM attendance
                         WHERE date >= date('now', '-6 months')
                         GROUP BY month
                         ORDER BY month""")
        
        data = cursor.fetchall()
        conn.close()
        
        if not data:
            # Return dummy data if no real data exists
            current_month = datetime.datetime.now().strftime("%Y-%m")
            months = [(current_month, 80)]
            for i in range(1, 6):
                prev_month = (datetime.datetime.now() - datetime.timedelta(days=30*i)).strftime("%Y-%m")
                months.insert(0, (prev_month, random.randint(70, 95)))
            return months
        
        return data
    
    def get_recent_activities(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("""SELECT strftime('%Y-%m-%d %H:%M', a.timestamp) as time, 
                         a.activity, u.username
                         FROM activity_log a
                         JOIN users u ON a.user_id = u.id
                         ORDER BY a.timestamp DESC 
                         LIMIT 10""")
        activities = cursor.fetchall()
        conn.close()
        
        if not activities:
            activities = [
                (datetime.datetime.now().strftime("%Y-%m-%d %H:%M"), "System initialized", "admin"),
                ((datetime.datetime.now() - datetime.timedelta(minutes=30)).strftime("%Y-%m-%d %H:%M"), "Sample activity 1", "admin"),
                ((datetime.datetime.now() - datetime.timedelta(hours=1)).strftime("%Y-%m-%d %H:%M"), "Sample activity 2", "admin")
            ]
        
        return activities
    
    def get_upcoming_events(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        cursor.execute("""SELECT strftime('%Y-%m-%d', event_date) as date, title 
                         FROM events 
                         WHERE event_date >= ?
                         ORDER BY event_date 
                         LIMIT 5""", (today,))
        events = cursor.fetchall()
        conn.close()
        
        if not events:
            events = [
                ((datetime.datetime.now() + datetime.timedelta(days=7)).strftime("%Y-%m-%d"), "Annual Sports Day"),
                ((datetime.datetime.now() + datetime.timedelta(days=14)).strftime("%Y-%m-%d"), "Mid-Term Exams"),
                ((datetime.datetime.now() + datetime.timedelta(days=30)).strftime("%Y-%m-%d"), "Pakistan Day Celebration")
            ]
        
        return events
    
    def get_class_list(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("SELECT name FROM classes ORDER BY name")
        classes = [row[0] for row in cursor.fetchall()]
        conn.close()
        
        if not classes:
            classes = ["Class 1", "Class 2", "Class 3", "Class 4", "Class 5",
                      "Class 6", "Class 7", "Class 8", "Class 9", "Class 10"]
        
        return classes

# Main application
if __name__ == "__main__":
    root = tk.Tk()
    app = SchoolManagementSystem(root)
    root.mainloop()