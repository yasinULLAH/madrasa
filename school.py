import tkinter as tk
from tkinter import ttk, messagebox, filedialog, PhotoImage
from tkinter.font import Font
import sqlite3
import os
import shutil
import datetime
from datetime import datetime as dt
from PIL import Image, ImageTk
import io
import base64
import json
import webbrowser
import matplotlib.pyplot as plt
from matplotlib.backends.backend_tkagg import FigureCanvasTkAgg
import random
import string
import tempfile

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
        self.current_user_role = "Admin"  # Simulated login
        self.current_language = "English"  # English/Urdu toggle
        self.school_name = "My School/Madrasa"
        self.school_logo = None
        
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
        
        # Create main containers
        self.create_header()
        self.create_sidebar()
        self.create_main_content()
        
        # Load dashboard by default
        self.show_dashboard()
        
        # Bind window resize event
        self.root.bind("<Configure>", self.on_window_resize)
    
    def initialize_database(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Students table
        cursor.execute('''CREATE TABLE IF NOT EXISTS students (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            admission_no TEXT UNIQUE,
                            name TEXT,
                            father_name TEXT,
                            cnic TEXT,
                            contact TEXT,
                            address TEXT,
                            class TEXT,
                            section TEXT,
                            date_of_joining TEXT,
                            category TEXT,
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
                            cnic TEXT,
                            contact TEXT,
                            address TEXT,
                            qualification TEXT,
                            subject TEXT,
                            date_of_joining TEXT,
                            role TEXT,
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
                            cnic TEXT,
                            contact TEXT,
                            address TEXT,
                            designation TEXT,
                            date_of_joining TEXT,
                            role TEXT,
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
        
        conn.commit()
        conn.close()
        
        # Insert default settings if not exists
        self.insert_default_settings()
    
    def insert_default_settings(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Check if settings already exist
        cursor.execute("SELECT COUNT(*) FROM settings")
        if cursor.fetchone()[0] == 0:
            default_settings = [
                ("school_name", "My School/Madrasa"),
                ("current_session", datetime.datetime.now().strftime("%Y")),
                ("session_start_date", "2023-04-01"),
                ("session_end_date", "2024-03-31"),
                ("default_language", "English"),
                ("receipt_prefix", "REC"),
                ("fee_due_day", "5"),
                ("currency_symbol", "Rs."),
                ("admin_user", "admin"),
                ("admin_password", "admin123")
            ]
            
            cursor.executemany("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?)", default_settings)
            conn.commit()
        
        conn.close()
    
    def load_settings(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("SELECT setting_name, setting_value FROM settings")
        settings = cursor.fetchall()
        
        for setting in settings:
            if setting[0] == "school_name":
                self.school_name = setting[1]
            elif setting[0] == "default_language":
                self.current_language = setting[1]
        
        conn.close()
    
    def create_header(self):
        self.header = tk.Frame(self.root, bg=self.header_color, height=80)
        self.header.pack(side=tk.TOP, fill=tk.X)
        
        # School logo and name
        self.logo_label = tk.Label(self.header, text=self.school_name, font=self.title_font, 
                                  fg="white", bg=self.header_color)
        self.logo_label.pack(side=tk.LEFT, padx=20, pady=10)
        
        # User info and controls
        control_frame = tk.Frame(self.header, bg=self.header_color)
        control_frame.pack(side=tk.RIGHT, padx=20)
        
        # Language toggle
        self.language_btn = tk.Button(control_frame, text="English/اردو", font=self.normal_font,
                                     command=self.toggle_language, bg=self.accent_color, fg="white")
        self.language_btn.pack(side=tk.LEFT, padx=5)
        
        # User info
        self.user_label = tk.Label(control_frame, text=f"User: {self.current_user_role}", 
                                  font=self.normal_font, fg="white", bg=self.header_color)
        self.user_label.pack(side=tk.LEFT, padx=5)
        
        # Logout button (simulated)
        self.logout_btn = tk.Button(control_frame, text="Logout", font=self.normal_font,
                                   command=self.simulate_logout, bg="#e74c3c", fg="white")
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
    
    def show_dashboard(self):
        self.clear_content_frame()
        self.log_activity("Viewed Dashboard")
        
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
            ("Today's Attendance", f"{today_attendance['present']}/{today_attendance['total']}", "#1abc9c")
        ]
        
        for i, (title, value, color) in enumerate(stats_data):
            card = tk.Frame(stats_frame, bg=color, bd=2, relief=tk.RAISED)
            card.grid(row=0, column=i, padx=5, sticky="nsew")
            stats_frame.columnconfigure(i, weight=1)
            
            title_label = tk.Label(card, text=title, font=self.normal_font, bg=color, fg="white")
            title_label.pack(pady=(5, 0))
            
            value_label = tk.Label(card, text=value, font=("Helvetica", 14, "bold"), bg=color, fg="white")
            value_label.pack(pady=(0, 5))
        
        # Main content area with recent activity and upcoming events
        main_area = tk.Frame(self.content_frame, bg=self.bg_color)
        main_area.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Recent activity frame
        activity_frame = tk.Frame(main_area, bg="white", bd=2, relief=tk.GROOVE)
        activity_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=5)
        
        activity_title = tk.Label(activity_frame, text="Recent Activity", font=self.subtitle_font, bg="white")
        activity_title.pack(anchor=tk.NW, padx=10, pady=5)
        
        # Activity list
        activity_list = ttk.Treeview(activity_frame, columns=("timestamp", "activity"), show="headings", height=10)
        activity_list.heading("timestamp", text="Time")
        activity_list.heading("activity", text="Activity")
        activity_list.column("timestamp", width=150)
        activity_list.column("activity", width=400)
        activity_list.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Add sample activities (in a real app, these would come from the database)
        activities = self.get_recent_activities()
        for activity in activities:
            activity_list.insert("", tk.END, values=(activity[0], activity[1]))
        
        # Upcoming events frame
        events_frame = tk.Frame(main_area, bg="white", bd=2, relief=tk.GROOVE)
        events_frame.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True, padx=5)
        
        events_title = tk.Label(events_frame, text="Upcoming Events", font=self.subtitle_font, bg="white")
        events_title.pack(anchor=tk.NW, padx=10, pady=5)
        
        # Events list
        events_list = ttk.Treeview(events_frame, columns=("date", "event"), show="headings", height=10)
        events_list.heading("date", text="Date")
        events_list.heading("event", text="Event")
        events_list.column("date", width=100)
        events_list.column("event", width=400)
        events_list.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Add sample events (in a real app, these would come from the database)
        upcoming_events = self.get_upcoming_events()
        for event in upcoming_events:
            events_list.insert("", tk.END, values=(event[0], event[1]))
    
    def show_people_management(self):
        self.clear_content_frame()
        self.log_activity("Viewed People Management")
        
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
        # Search frame
        search_frame = tk.Frame(parent, bg=self.bg_color)
        search_frame.pack(fill=tk.X, padx=10, pady=10)
        
        tk.Label(search_frame, text="Search Students:", font=self.normal_font, bg=self.bg_color).pack(side=tk.LEFT)
        
        search_entry = tk.Entry(search_frame, width=30)
        search_entry.pack(side=tk.LEFT, padx=5)
        
        search_btn = tk.Button(search_frame, text="Search", command=lambda: self.search_students(search_entry.get()))
        search_btn.pack(side=tk.LEFT, padx=5)
        
        filter_frame = tk.Frame(parent, bg=self.bg_color)
        filter_frame.pack(fill=tk.X, padx=10, pady=5)
        
        tk.Label(filter_frame, text="Filter by:", font=self.normal_font, bg=self.bg_color).pack(side=tk.LEFT)
        
        class_filter = ttk.Combobox(filter_frame, values=self.get_class_list(), state="readonly")
        class_filter.pack(side=tk.LEFT, padx=5)
        class_filter.set("All Classes")
        
        status_filter = ttk.Combobox(filter_frame, values=["All", "Active", "Inactive"], state="readonly")
        status_filter.pack(side=tk.LEFT, padx=5)
        status_filter.set("Active")
        
        filter_btn = tk.Button(filter_frame, text="Apply Filter", 
                              command=lambda: self.filter_students(class_filter.get(), status_filter.get()))
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
    
    def load_students_data(self, filter_class="All", filter_status="Active"):
        self.students_tree.delete(*self.students_tree.get_children())
        
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        query = "SELECT id, admission_no, name, father_name, class, contact, status FROM students"
        conditions = []
        params = []
        
        if filter_class != "All":
            conditions.append("class = ?")
            params.append(filter_class)
        
        if filter_status != "All":
            conditions.append("status = ?")
            params.append(filter_status)
        
        if conditions:
            query += " WHERE " + " AND ".join(conditions)
        
        cursor.execute(query, params)
        students = cursor.fetchall()
        
        for student in students:
            self.students_tree.insert("", tk.END, values=student)
        
        conn.close()
    
    def add_new_student(self):
        self.student_form_window("Add New Student")
    
    def edit_student(self):
        selected = self.students_tree.focus()
        if not selected:
            messagebox.showwarning("Warning", "Please select a student to edit")
            return
        
        student_id = self.students_tree.item(selected)['values'][0]
        self.student_form_window("Edit Student", student_id)
    
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
            
            cursor.execute("DELETE FROM students WHERE id = ?", (student_id,))
            conn.commit()
            conn.close()
            
            self.load_students_data()
            messagebox.showinfo("Success", "Student deleted successfully")
            self.log_activity(f"Deleted student: {student_name}")
    
    def student_form_window(self, title, student_id=None):
        form_window = tk.Toplevel(self.root)
        form_window.title(title)
        form_window.geometry("800x600")
        
        # Form frame
        form_frame = tk.Frame(form_window, padx=20, pady=20)
        form_frame.pack(fill=tk.BOTH, expand=True)
        
        # Photo frame
        photo_frame = tk.Frame(form_frame)
        photo_frame.grid(row=0, column=0, rowspan=6, padx=10, pady=10, sticky="n")
        
        self.student_photo = tk.Label(photo_frame, text="Student Photo", width=20, height=10, relief=tk.SUNKEN)
        self.student_photo.pack()
        
        upload_btn = tk.Button(photo_frame, text="Upload Photo", command=lambda: self.upload_photo(self.student_photo))
        upload_btn.pack(pady=5)
        
        # Form fields
        fields = [
            ("Admission No", 0, 1),
            ("Name", 1, 1),
            ("Father Name", 2, 1),
            ("CNIC", 3, 1),
            ("Contact", 4, 1),
            ("Address", 5, 1),
            ("Class", 6, 1),
            ("Section", 7, 1),
            ("Date of Joining", 8, 1),
            ("Category", 9, 1)
        ]
        
        self.student_form_entries = {}
        
        for i, (label, row, col) in enumerate(fields):
            tk.Label(form_frame, text=label).grid(row=row, column=col, sticky="e", padx=5, pady=5)
            
            if label == "Class":
                entry = ttk.Combobox(form_frame, values=self.get_class_list())
            elif label == "Category":
                entry = ttk.Combobox(form_frame, values=["General", "Deserving", "Orphan"])
            elif label == "Date of Joining":
                entry = tk.Entry(form_frame)
                # Add date picker functionality here
            else:
                entry = tk.Entry(form_frame)
            
            entry.grid(row=row, column=col+1, sticky="w", padx=5, pady=5)
            self.student_form_entries[label] = entry
        
        # Status checkbox
        self.student_status = tk.IntVar(value=1)
        tk.Checkbutton(form_frame, text="Active", variable=self.student_status).grid(row=10, column=2, sticky="w")
        
        # Button frame
        btn_frame = tk.Frame(form_frame)
        btn_frame.grid(row=11, column=0, columnspan=4, pady=20)
        
        save_btn = tk.Button(btn_frame, text="Save", command=lambda: self.save_student(student_id))
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
                "CNIC": 4,
                "Contact": 5,
                "Address": 6,
                "Class": 7,
                "Section": 8,
                "Date of Joining": 9,
                "Category": 10
            }
            
            for label, db_index in field_mapping.items():
                self.student_form_entries[label].insert(0, student[db_index])
            
            # Set status
            self.student_status.set(1 if student[12] == "Active" else 0)
            
            # Load photo if exists
            if student[11]:  # photo field
                self.display_image(self.student_photo, student[11])
        
        conn.close()
    
    def save_student(self, student_id=None):
        # Get data from form
        data = {
            "admission_no": self.student_form_entries["Admission No"].get(),
            "name": self.student_form_entries["Name"].get(),
            "father_name": self.student_form_entries["Father Name"].get(),
            "cnic": self.student_form_entries["CNIC"].get(),
            "contact": self.student_form_entries["Contact"].get(),
            "address": self.student_form_entries["Address"].get(),
            "class": self.student_form_entries["Class"].get(),
            "section": self.student_form_entries["Section"].get(),
            "date_of_joining": self.student_form_entries["Date of Joining"].get(),
            "category": self.student_form_entries["Category"].get(),
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
        if hasattr(self.student_photo, 'image_data'):
            photo_data = self.student_photo.image_data
        
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        try:
            if student_id:  # Update existing student
                query = """UPDATE students SET 
                          admission_no=?, name=?, father_name=?, cnic=?, contact=?, 
                          address=?, class=?, section=?, date_of_joining=?, category=?, 
                          photo=?, status=?
                          WHERE id=?"""
                params = (
                    data["admission_no"], data["name"], data["father_name"], data["cnic"], 
                    data["contact"], data["address"], data["class"], data["section"], 
                    data["date_of_joining"], data["category"], photo_data, data["status"], 
                    student_id
                )
                action = "updated"
            else:  # Insert new student
                query = """INSERT INTO students (
                          admission_no, name, father_name, cnic, contact, address, 
                          class, section, date_of_joining, category, photo, status
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"""
                params = (
                    data["admission_no"], data["name"], data["father_name"], data["cnic"], 
                    data["contact"], data["address"], data["class"], data["section"], 
                    data["date_of_joining"], data["category"], photo_data, data["status"]
                )
                action = "added"
            
            cursor.execute(query, params)
            conn.commit()
            
            messagebox.showinfo("Success", f"Student {action} successfully")
            self.log_activity(f"Student {action}: {data['name']}")
            
            # Refresh students list
            self.load_students_data()
            
            # Close form window
            for widget in self.root.winfo_children():
                if isinstance(widget, tk.Toplevel) and widget.title().startswith(("Add", "Edit")):
                    widget.destroy()
                    break
        
        except sqlite3.IntegrityError as e:
            messagebox.showerror("Error", f"Database error: {str(e)}")
        finally:
            conn.close()
    
    def create_teachers_tab(self, parent):
        # Similar to students tab but for teachers
        search_frame = tk.Frame(parent, bg=self.bg_color)
        search_frame.pack(fill=tk.X, padx=10, pady=10)
        
        tk.Label(search_frame, text="Search Teachers:", font=self.normal_font, bg=self.bg_color).pack(side=tk.LEFT)
        
        search_entry = tk.Entry(search_frame, width=30)
        search_entry.pack(side=tk.LEFT, padx=5)
        
        search_btn = tk.Button(search_frame, text="Search", command=lambda: self.search_teachers(search_entry.get()))
        search_btn.pack(side=tk.LEFT, padx=5)
        
        # Teachers list
        list_frame = tk.Frame(parent, bg=self.bg_color)
        list_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        columns = ("ID", "Employee ID", "Name", "Father Name", "Subject", "Contact", "Status")
        self.teachers_tree = ttk.Treeview(list_frame, columns=columns, show="headings", selectmode="browse")
        
        for col in columns:
            self.teachers_tree.heading(col, text=col)
            self.teachers_tree.column(col, width=100, anchor=tk.W)
        
        self.teachers_tree.pack(fill=tk.BOTH, expand=True)
        
        # Add scrollbar
        scrollbar = ttk.Scrollbar(list_frame, orient="vertical", command=self.teachers_tree.yview)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        self.teachers_tree.configure(yscrollcommand=scrollbar.set)
        
        # Load teachers data
        self.load_teachers_data()
    
    def load_teachers_data(self):
        # Implement similar to load_students_data
        pass
    
    def create_staff_tab(self, parent):
        # Similar to students tab but for staff
        pass
    
    def show_academic_management(self):
        self.clear_content_frame()
        self.log_activity("Viewed Academic Management")
        
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
        
        # Populate each tab
        self.create_classes_tab(classes_tab)
        self.create_attendance_tab(attendance_tab)
        self.create_exams_tab(exams_tab)
    
    def create_classes_tab(self, parent):
        # Implement classes and subjects management
        pass
    
    def create_attendance_tab(self, parent):
        # Implement attendance management
        pass
    
    def create_exams_tab(self, parent):
        # Implement exams and results management
        pass
    
    def show_financial_management(self):
        self.clear_content_frame()
        self.log_activity("Viewed Financial Management")
        
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
        # Implement fee management
        pass
    
    def create_salary_tab(self, parent):
        # Implement salary management
        pass
    
    def create_expenses_tab(self, parent):
        # Implement expenses and income management
        pass
    
    def show_reports_utilities(self):
        self.clear_content_frame()
        self.log_activity("Viewed Reports & Utilities")
        
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
        # Implement reports generation
        pass
    
    def create_idcards_tab(self, parent):
        # Implement ID cards generation
        pass
    
    def create_certificates_tab(self, parent):
        # Implement certificates generation
        pass
    
    def create_backup_tab(self, parent):
        # Implement backup/restore functionality
        pass
    
    def show_system_settings(self):
        self.clear_content_frame()
        self.log_activity("Viewed System Settings")
        
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
        
        # Populate each tab
        self.create_general_settings_tab(general_tab)
        self.create_user_management_tab(users_tab)
    
    def create_general_settings_tab(self, parent):
        # Implement general settings
        pass
    
    def create_user_management_tab(self, parent):
        # Implement user management
        pass
    
    def show_help_about(self):
        self.clear_content_frame()
        self.log_activity("Viewed Help & About")
        
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
        
        dev_label = tk.Label(self.content_frame, text="Developed by Your Name", font=self.normal_font, bg=self.bg_color)
        dev_label.pack(pady=10)
        
        contact_label = tk.Label(self.content_frame, text="Contact: your.email@example.com", 
                               font=self.normal_font, bg=self.bg_color)
        contact_label.pack(pady=5)
    
    def toggle_language(self):
        self.current_language = "Urdu" if self.current_language == "English" else "English"
        self.language_btn.config(text="English/اردو" if self.current_language == "English" else "اردو/English")
        
        # Update UI elements based on language
        self.update_ui_language()
        self.log_activity(f"Changed language to {self.current_language}")
    
    def update_ui_language(self):
        # This would update all UI elements to the selected language
        # For simplicity, we'll just update a few key elements
        
        if self.current_language == "English":
            self.dashboard_btn.config(text="Dashboard")
            self.logo_label.config(text=self.school_name)
        else:
            self.dashboard_btn.config(text="ڈیش بورڈ")
            self.logo_label.config(text="مدرسہ مینجمنٹ سسٹم")
    
    def simulate_logout(self):
        if messagebox.askyesno("Logout", "Are you sure you want to logout?"):
            # In a real app, this would clear session and show login screen
            self.current_user_role = None
            messagebox.showinfo("Logged Out", "You have been logged out successfully")
            self.log_activity("User logged out")
    
    def log_activity(self, activity, module=None):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # In a real app, we would use the actual user ID
        user_id = 1  # Simulated admin user
        
        cursor.execute("INSERT INTO activity_log (user_id, activity, module) VALUES (?, ?, ?)",
                     (user_id, activity, module))
        conn.commit()
        conn.close()
    
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
        # Simplified calculation - in a real app, this would be more complex
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        # Get total expected fees for active students
        cursor.execute("""SELECT SUM(fs.amount) 
                         FROM fee_structure fs
                         JOIN students s ON fs.class_id = (SELECT id FROM classes WHERE name = s.class)
                         WHERE s.status = 'Active'""")
        total_expected = cursor.fetchone()[0] or 0
        
        # Get total collected fees
        cursor.execute("SELECT SUM(amount_paid) FROM fee_payments")
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
        return {"present": present_students, "total": total_students}
    
    def get_recent_activities(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("""SELECT strftime('%Y-%m-%d %H:%M', timestamp) as time, activity 
                        FROM activity_log 
                        ORDER BY timestamp DESC 
                        LIMIT 10""")
        activities = cursor.fetchall()
        conn.close()
        
        if not activities:
            activities = [
                ("2023-01-01 10:00", "System initialized"),
                ("2023-01-01 09:30", "Sample activity 1"),
                ("2023-01-01 09:15", "Sample activity 2")
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
                ("2023-01-15", "Annual Sports Day"),
                ("2023-02-01", "Mid-Term Exams"),
                ("2023-03-23", "Pakistan Day Celebration")
            ]
        
        return events
    
    def get_class_list(self):
        conn = sqlite3.connect(self.db_name)
        cursor = conn.cursor()
        
        cursor.execute("SELECT name FROM classes ORDER BY name")
        classes = [row[0] for row in cursor.fetchall()]
        conn.close()
        
        if not classes:
            classes = ["Class 1", "Class 2", "Class 3", "Class 4", "Class 5"]
        
        return classes
    
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
    
    def on_window_resize(self, event):
        # Handle window resize events if needed
        pass
    
    def search_students(self, query):
        # Implement student search
        pass
    
    def filter_students(self, class_filter, status_filter):
        # Implement student filtering
        pass
    
    def search_teachers(self, query):
        # Implement teacher search
        pass

# Main application
if __name__ == "__main__":
    root = tk.Tk()
    app = SchoolManagementSystem(root)
    root.mainloop()