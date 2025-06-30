import tkinter as tk
from tkinter import ttk, messagebox, filedialog
import sqlite3
import os
from datetime import datetime
from PIL import Image, ImageTk
import qrcode
import json
import shutil # Ensure shutil is imported for file operations

class SchoolMadrasaApp:
    def __init__(self, root):
        self.root = root
        self.root.title("School & Madrasa Management System")
        self.root.geometry("1200x800")
        self.root.state('zoomed') # Start maximized

        self.db_name = "school_madrasa.db"
        self.conn = None
        self.cursor = None
        self.connect_db()
        self.create_tables()

        self.current_user_role = "Admin" # Simulate login: Admin, Teacher, Accountant
        self.language = "English" # English or Urdu

        self.apply_styles() # Apply modern styles
        self.create_widgets()
        self.load_dashboard_data()

    def connect_db(self):
        try:
            self.conn = sqlite3.connect(self.db_name)
            self.cursor = self.conn.cursor()
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to connect to database: {e}")
            self.root.destroy()

    def create_tables(self):
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY,
                school_name TEXT,
                logo_path TEXT,
                current_session TEXT
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY,
                username TEXT UNIQUE,
                password TEXT,
                role TEXT
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY,
                name TEXT,
                cnic TEXT UNIQUE,
                father_name TEXT,
                contact TEXT,
                class TEXT,
                address TEXT,
                dob TEXT,
                photo_path TEXT,
                category TEXT
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS staff (
                id INTEGER PRIMARY KEY,
                name TEXT,
                cnic TEXT UNIQUE,
                contact TEXT,
                role TEXT,
                subject TEXT,
                address TEXT,
                joining_date TEXT,
                photo_path TEXT,
                salary REAL
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS classes (
                id INTEGER PRIMARY KEY,
                name TEXT UNIQUE,
                section TEXT
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS subjects (
                id INTEGER PRIMARY KEY,
                name TEXT UNIQUE
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS class_subject_teacher (
                id INTEGER PRIMARY KEY,
                class_id INTEGER,
                subject_id INTEGER,
                teacher_id INTEGER,
                FOREIGN KEY (class_id) REFERENCES classes(id),
                FOREIGN KEY (subject_id) REFERENCES subjects(id),
                FOREIGN KEY (teacher_id) REFERENCES staff(id)
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS attendance (
                id INTEGER PRIMARY KEY,
                student_id INTEGER,
                date TEXT,
                status TEXT, -- Present, Absent, Leave, Late
                FOREIGN KEY (student_id) REFERENCES students(id)
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS exams (
                id INTEGER PRIMARY KEY,
                name TEXT,
                exam_date TEXT,
                class_id INTEGER,
                FOREIGN KEY (class_id) REFERENCES classes(id)
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS marks (
                id INTEGER PRIMARY KEY,
                exam_id INTEGER,
                student_id INTEGER,
                subject_id INTEGER,
                marks REAL,
                FOREIGN KEY (exam_id) REFERENCES exams(id),
                FOREIGN KEY (student_id) REFERENCES students(id),
                FOREIGN KEY (subject_id) REFERENCES subjects(id)
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS fee_structures (
                id INTEGER PRIMARY KEY,
                class_id INTEGER,
                category TEXT,
                amount REAL,
                FOREIGN KEY (class_id) REFERENCES classes(id)
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS fee_collections (
                id INTEGER PRIMARY KEY,
                student_id INTEGER,
                amount REAL,
                collection_date TEXT,
                month TEXT,
                year TEXT,
                discount REAL,
                fine REAL,
                receipt_no TEXT UNIQUE,
                FOREIGN KEY (student_id) REFERENCES students(id)
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS expenses (
                id INTEGER PRIMARY KEY,
                category TEXT,
                amount REAL,
                expense_date TEXT,
                description TEXT
            )
        ''')
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS income (
                id INTEGER PRIMARY KEY,
                category TEXT,
                amount REAL,
                income_date TEXT,
                description TEXT
            )
        ''')
        self.conn.commit()

        self.cursor.execute("SELECT * FROM users WHERE username='admin'")
        if not self.cursor.fetchone():
            self.cursor.execute("INSERT INTO users (username, password, role) VALUES (?, ?, ?)", ('admin', 'admin123', 'Admin'))
            self.conn.commit()

    def apply_styles(self):
        style = ttk.Style()
        style.theme_use("clam") # Or "alt", "default", "vista", "xpnative"

        # General Frame/LabelFrame styling
        style.configure("TFrame", background="#ecf0f1")
        style.configure("TLabelframe", background="#ecf0f1", bordercolor="#bdc3c7", relief="solid")
        style.configure("TLabelframe.Label", background="#ecf0f1", foreground="#2c3e50", font=("Arial", 11, "bold"))

        # Button styling
        style.configure("TButton",
                        font=("Arial", 10, "bold"),
                        background="#3498db",
                        foreground="white",
                        relief="flat",
                        padding=8)
        style.map("TButton",
                  background=[('active', '#2980b9')],
                  foreground=[('active', 'white')])

        # Sidebar Button styling
        style.configure("Sidebar.TButton",
                        font=("Arial", 12, "bold"),
                        background="#34495e",
                        foreground="white",
                        relief="flat",
                        padding=10)
        style.map("Sidebar.TButton",
                  background=[('active', '#1abc9c')],
                  foreground=[('active', 'white')])

        # Entry and Combobox styling
        style.configure("TEntry", fieldbackground="white", bordercolor="#bdc3c7", relief="solid")
        style.configure("TCombobox", fieldbackground="white", bordercolor="#bdc3c7", relief="solid")

        # Treeview styling
        style.configure("Treeview",
                        background="#ffffff",
                        foreground="#34495e",
                        rowheight=25,
                        fieldbackground="#ffffff")
        style.map("Treeview",
                  background=[('selected', '#3498db')])
        style.configure("Treeview.Heading",
                        font=("Arial", 10, "bold"),
                        background="#2c3e50",
                        foreground="white",
                        relief="flat")
        style.map("Treeview.Heading",
                  background=[('active', '#34495e')])

        # Notebook (Tab) styling
        style.configure("TNotebook", background="#ecf0f1", borderwidth=0)
        style.configure("TNotebook.Tab",
                        background="#bdc3c7",
                        foreground="#2c3e50",
                        padding=[10, 5],
                        font=("Arial", 10, "bold"))
        style.map("TNotebook.Tab",
                  background=[('selected', '#3498db')],
                  foreground=[('selected', 'white')])

        # Label styling
        style.configure("TLabel", background="#ecf0f1", foreground="#2c3e50")
        style.configure("Header.TLabel", font=("Arial", 24, "bold"), foreground="#2c3e50")
        style.configure("SubHeader.TLabel", font=("Arial", 16, "bold"), foreground="#2c3e50")
        style.configure("Stat.TLabel", font=("Arial", 12, "bold"), foreground="white")
        style.configure("StatValue.TLabel", font=("Arial", 20, "bold"), foreground="white")

    def create_widgets(self):
        self.sidebar = tk.Frame(self.root, bg="#2c3e50", width=200)
        self.sidebar.pack(side="left", fill="y")

        self.main_content = tk.Frame(self.root, bg="#ecf0f1")
        self.main_content.pack(side="right", fill="both", expand=True)

        self.buttons = [
            ("Dashboard", self.show_dashboard, "Admin", "Teacher", "Accountant"),
            ("People Management", self.show_people_management, "Admin"),
            ("Academic Management", self.show_academic_management, "Admin", "Teacher"),
            ("Financial Management", self.show_financial_management, "Admin", "Accountant"),
            ("Utilities & Reports", self.show_utilities_reports, "Admin"),
            ("Settings", self.show_settings, "Admin"),
            ("Logout", self.logout, "Admin", "Teacher", "Accountant")
        ]

        self.nav_buttons = []
        for text, command, *roles in self.buttons:
            btn = ttk.Button(self.sidebar, text=text, command=command, style="Sidebar.TButton")
            btn.pack(fill="x", pady=5, padx=10)
            self.nav_buttons.append((btn, roles))

        self.update_sidebar_visibility()

        self.current_frame = None
        self.show_dashboard()

    def update_sidebar_visibility(self):
        for btn, roles in self.nav_buttons:
            if self.current_user_role in roles:
                btn.pack(fill="x", pady=5, padx=10)
            else:
                btn.pack_forget()

    def switch_frame(self, new_frame_func):
        if self.current_frame:
            self.current_frame.destroy()
        new_frame_func()

    def logout(self):
        if messagebox.askyesno("Logout", "Are you sure you want to logout?"):
            self.current_user_role = None # Or show login screen
            messagebox.showinfo("Logged Out", "You have been logged out.")
            self.root.destroy() # For simplicity, close app. In real app, show login.

    def show_dashboard(self):
        self.switch_frame(self._create_dashboard_frame)

    def _create_dashboard_frame(self):
        self.current_frame = ttk.Frame(self.main_content, style="TFrame")
        self.current_frame.pack(fill="both", expand=True, padx=20, pady=20)

        ttk.Label(self.current_frame, text="Dashboard", style="Header.TLabel").pack(pady=10)

        stats_frame = ttk.Frame(self.current_frame, style="TFrame")
        stats_frame.pack(pady=20, fill="x")

        self.stats_labels = {}
        stats_data = {
            "Total Students": 0, "Total Staff": 0, "Fee Collections (Today)": 0,
            "Pending Fees": 0, "Today's Attendance": "N/A"
        }

        for i, (text, value) in enumerate(stats_data.items()):
            frame = tk.Frame(stats_frame, bg="#3498db", bd=2, relief="groove") # Use raw Tkinter Frame for custom background
            frame.grid(row=0, column=i, padx=10, pady=10, ipadx=10, ipady=10, sticky="nsew")
            ttk.Label(frame, text=text, style="Stat.TLabel", background="#3498db").pack(pady=5)
            self.stats_labels[text] = ttk.Label(frame, text=str(value), style="StatValue.TLabel", background="#3498db")
            self.stats_labels[text].pack(pady=5)
            stats_frame.grid_columnconfigure(i, weight=1)

        self.load_dashboard_data()

        activity_frame = ttk.LabelFrame(self.current_frame, text="Recent Activity & Upcoming Events", style="TLabelframe")
        activity_frame.pack(pady=20, fill="both", expand=True)

        ttk.Label(activity_frame, text="No recent activities or upcoming events to display.", style="TLabel").pack(anchor="w", padx=10, pady=5)

    def load_dashboard_data(self):
        if not hasattr(self, 'stats_labels'): return

        self.cursor.execute("SELECT COUNT(*) FROM students")
        total_students = self.cursor.fetchone()[0]

        self.cursor.execute("SELECT COUNT(*) FROM staff")
        total_staff = self.cursor.fetchone()[0]

        today = datetime.now().strftime("%Y-%m-%d")
        self.cursor.execute("SELECT SUM(amount) FROM fee_collections WHERE collection_date = ?", (today,))
        today_fees = self.cursor.fetchone()[0] or 0

        current_month = datetime.now().strftime("%B")
        current_year = datetime.now().strftime("%Y")
        self.cursor.execute("""
            SELECT COUNT(s.id) FROM students s
            LEFT JOIN fee_collections fc ON s.id = fc.student_id AND fc.month = ? AND fc.year = ?
            WHERE fc.id IS NULL
        """, (current_month, current_year))
        pending_fees_count = self.cursor.fetchone()[0]

        self.cursor.execute("SELECT COUNT(*) FROM attendance WHERE date = ? AND status = 'Present'", (today,))
        today_present = self.cursor.fetchone()[0]
        self.cursor.execute("SELECT COUNT(*) FROM students")
        total_students_for_attendance = self.cursor.fetchone()[0]
        today_attendance_str = f"{today_present}/{total_students_for_attendance} Present" if total_students_for_attendance > 0 else "N/A"

        self.stats_labels["Total Students"].config(text=str(total_students))
        self.stats_labels["Total Staff"].config(text=str(total_staff))
        self.stats_labels["Fee Collections (Today)"].config(text=f"PKR {today_fees:,.2f}")
        self.stats_labels["Pending Fees"].config(text=f"{pending_fees_count} Students")
        self.stats_labels["Today's Attendance"].config(text=today_attendance_str)

    def show_people_management(self):
        self.switch_frame(self._create_people_management_frame)

    def _create_people_management_frame(self):
        self.current_frame = ttk.Frame(self.main_content, style="TFrame")
        self.current_frame.pack(fill="both", expand=True, padx=20, pady=20)

        ttk.Label(self.current_frame, text="People Management", style="Header.TLabel").pack(pady=10)

        notebook = ttk.Notebook(self.current_frame)
        notebook.pack(fill="both", expand=True)

        self.student_tab = ttk.Frame(notebook, style="TFrame")
        self.teacher_staff_tab = ttk.Frame(notebook, style="TFrame")

        notebook.add(self.student_tab, text="Students")
        notebook.add(self.teacher_staff_tab, text="Teachers & Staff")

        self._setup_student_tab()
        self._setup_teacher_staff_tab()

    def _setup_student_tab(self):
        search_frame = ttk.LabelFrame(self.student_tab, text="Search Students", style="TLabelframe")
        search_frame.pack(fill="x", pady=10, padx=10)
        search_frame.columnconfigure(1, weight=1)
        search_frame.columnconfigure(3, weight=1)
        search_frame.columnconfigure(5, weight=1)

        ttk.Label(search_frame, text="Name:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.student_search_name_entry = ttk.Entry(search_frame)
        self.student_search_name_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(search_frame, text="Class:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.student_search_class_combo = ttk.Combobox(search_frame, values=self._get_class_names())
        self.student_search_class_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Label(search_frame, text="Category:").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.student_search_category_combo = ttk.Combobox(search_frame, values=["General", "Deserving"])
        self.student_search_category_combo.grid(row=0, column=5, padx=5, pady=2, sticky="ew")

        ttk.Button(search_frame, text="Search", command=self._load_students).grid(row=0, column=6, padx=10, pady=2)
        ttk.Button(search_frame, text="Clear", command=lambda: [self.student_search_name_entry.delete(0, tk.END), self.student_search_class_combo.set(''), self.student_search_category_combo.set(''), self._load_students()]).grid(row=0, column=7, padx=10, pady=2)

        self.student_tree = ttk.Treeview(self.student_tab, columns=("ID", "Name", "CNIC", "Father Name", "Contact", "Class", "Address", "DOB", "Category"), show="headings")
        self.student_tree.pack(fill="both", expand=True, pady=10, padx=10)

        for col in self.student_tree["columns"]:
            self.student_tree.heading(col, text=col)
            self.student_tree.column(col, width=100, anchor="center")

        self.student_tree.bind("<Double-1>", self._edit_student_dialog)

        btn_frame = ttk.Frame(self.student_tab, style="TFrame")
        btn_frame.pack(pady=10)
        ttk.Button(btn_frame, text="Add Student", command=self._add_student_dialog).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="Edit Student", command=self._edit_student_dialog).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="Delete Student", command=self._delete_student).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="View Profile", command=self._view_student_profile).pack(side="left", padx=5)

        self._load_students()

    def _load_students(self):
        for i in self.student_tree.get_children():
            self.student_tree.delete(i)

        name_filter = self.student_search_name_entry.get()
        class_filter = self.student_search_class_combo.get()
        category_filter = self.student_search_category_combo.get()

        query = "SELECT * FROM students WHERE 1=1"
        params = []
        if name_filter:
            query += " AND name LIKE ?"
            params.append(f"%{name_filter}%")
        if class_filter:
            query += " AND class = ?"
            params.append(class_filter)
        if category_filter:
            query += " AND category = ?"
            params.append(category_filter)

        self.cursor.execute(query, params)
        for row in self.cursor.fetchall():
            self.student_tree.insert("", "end", values=row)

    def _add_student_dialog(self):
        self._student_form_dialog("Add Student")

    def _edit_student_dialog(self, event=None):
        selected_item = self.student_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a student to edit.")
            return
        student_id = self.student_tree.item(selected_item, "values")[0]
        self._student_form_dialog("Edit Student", student_id)

    def _student_form_dialog(self, title, student_id=None):
        dialog = tk.Toplevel(self.root)
        dialog.title(title)
        dialog.transient(self.root)
        dialog.grab_set()
        dialog.geometry("500x600")
        dialog.resizable(False, False)

        form_frame = ttk.Frame(dialog, style="TFrame", padding=20)
        form_frame.pack(fill="both", expand=True)
        form_frame.columnconfigure(1, weight=1)

        labels = ["Name:", "CNIC:", "Father Name:", "Contact:", "Class:", "Address:", "Date of Birth (YYYY-MM-DD):", "Category:"]
        entries = {}
        photo_path_var = tk.StringVar()
        photo_label = None
        current_photo_path = ""

        for i, text in enumerate(labels):
            ttk.Label(form_frame, text=text).grid(row=i, column=0, sticky="w", pady=5, padx=5)
            if text == "Class:":
                entry = ttk.Combobox(form_frame, values=self._get_class_names())
            elif text == "Category:":
                entry = ttk.Combobox(form_frame, values=["General", "Deserving"])
            else:
                entry = ttk.Entry(form_frame)
            entry.grid(row=i, column=1, sticky="ew", pady=5, padx=5)
            entries[text.replace(":", "").strip()] = entry

        def select_photo():
            file_path = filedialog.askopenfilename(filetypes=[("Image Files", "*.png *.jpg *.jpeg *.gif")])
            if file_path:
                photo_path_var.set(file_path)
                self._display_photo_preview(photo_path_var.get(), photo_label)

        ttk.Label(form_frame, text="Photo:").grid(row=len(labels), column=0, sticky="w", pady=5, padx=5)
        ttk.Button(form_frame, text="Browse", command=select_photo).grid(row=len(labels), column=1, sticky="ew", pady=5, padx=5)
        photo_label = ttk.Label(form_frame, text="No Photo Selected", relief="groove", anchor="center")
        photo_label.grid(row=len(labels) + 1, column=0, columnspan=2, pady=5, padx=5, sticky="nsew")
        form_frame.rowconfigure(len(labels) + 1, weight=1) # Make photo label expand vertically

        if student_id:
            self.cursor.execute("SELECT * FROM students WHERE id=?", (student_id,))
            student_data = self.cursor.fetchone()
            if student_data:
                entries["Name"].insert(0, student_data[1])
                entries["CNIC"].insert(0, student_data[2])
                entries["Father Name"].insert(0, student_data[3])
                entries["Contact"].insert(0, student_data[4])
                entries["Class"].set(student_data[5])
                entries["Address"].insert(0, student_data[6])
                entries["Date of Birth (YYYY-MM-DD)"].insert(0, student_data[7])
                current_photo_path = student_data[8]
                photo_path_var.set(current_photo_path)
                entries["Category"].set(student_data[9])
                self._display_photo_preview(current_photo_path, photo_label)

        def save_student():
            name = entries["Name"].get()
            cnic = entries["CNIC"].get()
            father_name = entries["Father Name"].get()
            contact = entries["Contact"].get()
            s_class = entries["Class"].get()
            address = entries["Address"].get()
            dob = entries["Date of Birth (YYYY-MM-DD)"].get()
            category = entries["Category"].get()
            new_photo_path = photo_path_var.get()

            if not all([name, cnic, father_name, contact, s_class, address, dob, category]):
                messagebox.showerror("Input Error", "All fields are required.")
                return

            saved_photo_path = current_photo_path
            if new_photo_path and new_photo_path != current_photo_path:
                try:
                    photo_dir = "student_photos"
                    os.makedirs(photo_dir, exist_ok=True)
                    filename = os.path.basename(new_photo_path)
                    saved_photo_path = os.path.join(photo_dir, filename)
                    shutil.copy(new_photo_path, saved_photo_path)
                except Exception as e:
                    messagebox.showwarning("Photo Save Error", f"Could not save photo: {e}. Proceeding without photo.")
                    saved_photo_path = ""

            try:
                if student_id:
                    self.cursor.execute("UPDATE students SET name=?, cnic=?, father_name=?, contact=?, class=?, address=?, dob=?, photo_path=?, category=? WHERE id=?",
                                        (name, cnic, father_name, contact, s_class, address, dob, saved_photo_path, category, student_id))
                else:
                    self.cursor.execute("INSERT INTO students (name, cnic, father_name, contact, class, address, dob, photo_path, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                        (name, cnic, father_name, contact, s_class, address, dob, saved_photo_path, category))
                self.conn.commit()
                messagebox.showinfo("Success", f"Student {title.lower()}ed successfully!")
                self._load_students()
                self.load_dashboard_data()
                dialog.destroy()
            except sqlite3.IntegrityError:
                messagebox.showerror("Database Error", "CNIC must be unique.")
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

        ttk.Button(dialog, text="Save", command=save_student).pack(pady=10)

    def _delete_student(self):
        selected_item = self.student_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a student to delete.")
            return
        student_id = self.student_tree.item(selected_item, "values")[0]
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this student?"):
            try:
                self.cursor.execute("DELETE FROM students WHERE id=?", (student_id,))
                self.conn.commit()
                messagebox.showinfo("Success", "Student deleted successfully!")
                self._load_students()
                self.load_dashboard_data()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

    def _view_student_profile(self):
        selected_item = self.student_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a student to view profile.")
            return
        student_id = self.student_tree.item(selected_item, "values")[0]

        dialog = tk.Toplevel(self.root)
        dialog.title("Student Profile")
        dialog.transient(self.root)
        dialog.grab_set()
        dialog.geometry("600x700")
        dialog.resizable(False, False)

        profile_frame = ttk.Frame(dialog, style="TFrame", padding=20)
        profile_frame.pack(fill="both", expand=True)
        profile_frame.columnconfigure(1, weight=1)

        self.cursor.execute("SELECT * FROM students WHERE id=?", (student_id,))
        student_data = self.cursor.fetchone()

        if student_data:
            labels_data = {
                "ID": student_data[0],
                "Name": student_data[1],
                "CNIC": student_data[2],
                "Father Name": student_data[3],
                "Contact": student_data[4],
                "Class": student_data[5],
                "Address": student_data[6],
                "Date of Birth": student_data[7],
                "Category": student_data[9]
            }

            row = 0
            photo_label = ttk.Label(profile_frame, text="No Photo", relief="groove", anchor="center")
            photo_label.grid(row=row, column=0, columnspan=2, pady=10, sticky="nsew")
            self._display_photo_preview(student_data[8], photo_label, size=(150, 150))
            profile_frame.rowconfigure(row, weight=1) # Make photo label expand vertically
            row += 1

            for label_text, value in labels_data.items():
                ttk.Label(profile_frame, text=f"{label_text}:", font=("Arial", 10, "bold")).grid(row=row, column=0, sticky="w", pady=2, padx=5)
                ttk.Label(profile_frame, text=value, font=("Arial", 10)).grid(row=row, column=1, sticky="w", pady=2, padx=5)
                row += 1
        else:
            ttk.Label(profile_frame, text="Student not found.").pack()

    def _setup_teacher_staff_tab(self):
        search_frame = ttk.LabelFrame(self.teacher_staff_tab, text="Search Teachers/Staff", style="TLabelframe")
        search_frame.pack(fill="x", pady=10, padx=10)
        search_frame.columnconfigure(1, weight=1)
        search_frame.columnconfigure(3, weight=1)

        ttk.Label(search_frame, text="Name:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.staff_search_name_entry = ttk.Entry(search_frame)
        self.staff_search_name_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(search_frame, text="Role:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.staff_search_role_combo = ttk.Combobox(search_frame, values=["Teacher", "Admin", "Accountant", "Other Staff"])
        self.staff_search_role_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Button(search_frame, text="Search", command=self._load_staff).grid(row=0, column=4, padx=10, pady=2)
        ttk.Button(search_frame, text="Clear", command=lambda: [self.staff_search_name_entry.delete(0, tk.END), self.staff_search_role_combo.set(''), self._load_staff()]).grid(row=0, column=5, padx=10, pady=2)

        self.staff_tree = ttk.Treeview(self.teacher_staff_tab, columns=("ID", "Name", "CNIC", "Contact", "Role", "Subject", "Address", "Joining Date", "Salary"), show="headings")
        self.staff_tree.pack(fill="both", expand=True, pady=10, padx=10)

        for col in self.staff_tree["columns"]:
            self.staff_tree.heading(col, text=col)
            self.staff_tree.column(col, width=100, anchor="center")

        self.staff_tree.bind("<Double-1>", self._edit_staff_dialog)

        btn_frame = ttk.Frame(self.teacher_staff_tab, style="TFrame")
        btn_frame.pack(pady=10)
        ttk.Button(btn_frame, text="Add Staff", command=self._add_staff_dialog).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="Edit Staff", command=self._edit_staff_dialog).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="Delete Staff", command=self._delete_staff).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="View Profile", command=self._view_staff_profile).pack(side="left", padx=5)

        self._load_staff()

    def _load_staff(self):
        for i in self.staff_tree.get_children():
            self.staff_tree.delete(i)

        name_filter = self.staff_search_name_entry.get()
        role_filter = self.staff_search_role_combo.get()

        query = "SELECT id, name, cnic, contact, role, subject, address, joining_date, salary FROM staff WHERE 1=1"
        params = []
        if name_filter:
            query += " AND name LIKE ?"
            params.append(f"%{name_filter}%")
        if role_filter:
            query += " AND role = ?"
            params.append(role_filter)

        self.cursor.execute(query, params)
        for row in self.cursor.fetchall():
            self.staff_tree.insert("", "end", values=row)

    def _add_staff_dialog(self):
        self._staff_form_dialog("Add Staff")

    def _edit_staff_dialog(self, event=None):
        selected_item = self.staff_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a staff member to edit.")
            return
        staff_id = self.staff_tree.item(selected_item, "values")[0]
        self._staff_form_dialog("Edit Staff", staff_id)

    def _staff_form_dialog(self, title, staff_id=None):
        dialog = tk.Toplevel(self.root)
        dialog.title(title)
        dialog.transient(self.root)
        dialog.grab_set()
        dialog.geometry("500x600")
        dialog.resizable(False, False)

        form_frame = ttk.Frame(dialog, style="TFrame", padding=20)
        form_frame.pack(fill="both", expand=True)
        form_frame.columnconfigure(1, weight=1)

        labels = ["Name:", "CNIC:", "Contact:", "Role:", "Subject (if Teacher):", "Address:", "Joining Date (YYYY-MM-DD):", "Salary:"]
        entries = {}
        photo_path_var = tk.StringVar()
        photo_label = None
        current_photo_path = ""

        for i, text in enumerate(labels):
            ttk.Label(form_frame, text=text).grid(row=i, column=0, sticky="w", pady=5, padx=5)
            if text == "Role:":
                entry = ttk.Combobox(form_frame, values=["Teacher", "Admin", "Accountant", "Other Staff"])
            else:
                entry = ttk.Entry(form_frame)
            entry.grid(row=i, column=1, sticky="ew", pady=5, padx=5)
            entries[text.replace(":", "").strip()] = entry

        def select_photo():
            file_path = filedialog.askopenfilename(filetypes=[("Image Files", "*.png *.jpg *.jpeg *.gif")])
            if file_path:
                photo_path_var.set(file_path)
                self._display_photo_preview(photo_path_var.get(), photo_label)

        ttk.Label(form_frame, text="Photo:").grid(row=len(labels), column=0, sticky="w", pady=5, padx=5)
        ttk.Button(form_frame, text="Browse", command=select_photo).grid(row=len(labels), column=1, sticky="ew", pady=5, padx=5)
        photo_label = ttk.Label(form_frame, text="No Photo Selected", relief="groove", anchor="center")
        photo_label.grid(row=len(labels) + 1, column=0, columnspan=2, pady=5, padx=5, sticky="nsew")
        form_frame.rowconfigure(len(labels) + 1, weight=1)

        if staff_id:
            self.cursor.execute("SELECT * FROM staff WHERE id=?", (staff_id,))
            staff_data = self.cursor.fetchone()
            if staff_data:
                entries["Name"].insert(0, staff_data[1])
                entries["CNIC"].insert(0, staff_data[2])
                entries["Contact"].insert(0, staff_data[3])
                entries["Role"].set(staff_data[4])
                entries["Subject (if Teacher)"].insert(0, staff_data[5])
                entries["Address"].insert(0, staff_data[6])
                entries["Joining Date (YYYY-MM-DD)"].insert(0, staff_data[7])
                current_photo_path = staff_data[8]
                photo_path_var.set(current_photo_path)
                entries["Salary"].insert(0, staff_data[9])
                self._display_photo_preview(current_photo_path, photo_label)

        def save_staff():
            name = entries["Name"].get()
            cnic = entries["CNIC"].get()
            contact = entries["Contact"].get()
            role = entries["Role"].get()
            subject = entries["Subject (if Teacher)"].get() if role == "Teacher" else ""
            address = entries["Address"].get()
            joining_date = entries["Joining Date (YYYY-MM-DD)"].get()
            salary = entries["Salary"].get()
            new_photo_path = photo_path_var.get()

            if not all([name, cnic, contact, role, address, joining_date, salary]):
                messagebox.showerror("Input Error", "All fields (except Subject for non-teachers) are required.")
                return
            try:
                salary = float(salary)
            except ValueError:
                messagebox.showerror("Input Error", "Salary must be a number.")
                return

            saved_photo_path = current_photo_path
            if new_photo_path and new_photo_path != current_photo_path:
                try:
                    photo_dir = "staff_photos"
                    os.makedirs(photo_dir, exist_ok=True)
                    filename = os.path.basename(new_photo_path)
                    saved_photo_path = os.path.join(photo_dir, filename)
                    shutil.copy(new_photo_path, saved_photo_path)
                except Exception as e:
                    messagebox.showwarning("Photo Save Error", f"Could not save photo: {e}. Proceeding without photo.")
                    saved_photo_path = ""

            try:
                if staff_id:
                    self.cursor.execute("UPDATE staff SET name=?, cnic=?, contact=?, role=?, subject=?, address=?, joining_date=?, photo_path=?, salary=? WHERE id=?",
                                        (name, cnic, contact, role, subject, address, joining_date, saved_photo_path, salary, staff_id))
                else:
                    self.cursor.execute("INSERT INTO staff (name, cnic, contact, role, subject, address, joining_date, photo_path, salary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                        (name, cnic, contact, role, subject, address, joining_date, saved_photo_path, salary))
                self.conn.commit()
                messagebox.showinfo("Success", f"Staff {title.lower()}ed successfully!")
                self._load_staff()
                self.load_dashboard_data()
                dialog.destroy()
            except sqlite3.IntegrityError:
                messagebox.showerror("Database Error", "CNIC must be unique.")
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

        ttk.Button(dialog, text="Save", command=save_staff).pack(pady=10)

    def _delete_staff(self):
        selected_item = self.staff_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a staff member to delete.")
            return
        staff_id = self.staff_tree.item(selected_item, "values")[0]
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this staff member?"):
            try:
                self.cursor.execute("DELETE FROM staff WHERE id=?", (staff_id,))
                self.conn.commit()
                messagebox.showinfo("Success", "Staff member deleted successfully!")
                self._load_staff()
                self.load_dashboard_data()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

    def _view_staff_profile(self):
        selected_item = self.staff_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a staff member to view profile.")
            return
        staff_id = self.staff_tree.item(selected_item, "values")[0]

        dialog = tk.Toplevel(self.root)
        dialog.title("Staff Profile")
        dialog.transient(self.root)
        dialog.grab_set()
        dialog.geometry("600x700")
        dialog.resizable(False, False)

        profile_frame = ttk.Frame(dialog, style="TFrame", padding=20)
        profile_frame.pack(fill="both", expand=True)
        profile_frame.columnconfigure(1, weight=1)

        self.cursor.execute("SELECT * FROM staff WHERE id=?", (staff_id,))
        staff_data = self.cursor.fetchone()

        if staff_data:
            labels_data = {
                "ID": staff_data[0],
                "Name": staff_data[1],
                "CNIC": staff_data[2],
                "Contact": staff_data[3],
                "Role": staff_data[4],
                "Subject": staff_data[5],
                "Address": staff_data[6],
                "Joining Date": staff_data[7],
                "Salary": f"PKR {staff_data[9]:,.2f}"
            }

            row = 0
            photo_label = ttk.Label(profile_frame, text="No Photo", relief="groove", anchor="center")
            photo_label.grid(row=row, column=0, columnspan=2, pady=10, sticky="nsew")
            self._display_photo_preview(staff_data[8], photo_label, size=(150, 150))
            profile_frame.rowconfigure(row, weight=1)
            row += 1

            for label_text, value in labels_data.items():
                ttk.Label(profile_frame, text=f"{label_text}:", font=("Arial", 10, "bold")).grid(row=row, column=0, sticky="w", pady=2, padx=5)
                ttk.Label(profile_frame, text=value, font=("Arial", 10)).grid(row=row, column=1, sticky="w", pady=2, padx=5)
                row += 1
        else:
            ttk.Label(profile_frame, text="Staff not found.").pack()

    def _display_photo_preview(self, photo_path, label_widget, size=(100, 100)):
        if photo_path and os.path.exists(photo_path):
            try:
                img = Image.open(photo_path)
                img = img.resize(size, Image.LANCZOS)
                img_tk = ImageTk.PhotoImage(img)
                label_widget.config(image=img_tk, text="")
                label_widget.image = img_tk
            except Exception as e:
                label_widget.config(image="", text=f"Error loading photo: {e}")
                label_widget.image = None
        else:
            label_widget.config(image="", text="No Photo Selected")
            label_widget.image = None

    def _get_class_names(self):
        self.cursor.execute("SELECT name FROM classes")
        return [row[0] for row in self.cursor.fetchall()]

    def _get_subject_names(self):
        self.cursor.execute("SELECT name FROM subjects")
        return [row[0] for row in self.cursor.fetchall()]

    def _get_teacher_names(self):
        self.cursor.execute("SELECT name FROM staff WHERE role='Teacher'")
        return [row[0] for row in self.cursor.fetchall()]

    def show_academic_management(self):
        self.switch_frame(self._create_academic_management_frame)

    def _create_academic_management_frame(self):
        self.current_frame = ttk.Frame(self.main_content, style="TFrame")
        self.current_frame.pack(fill="both", expand=True, padx=20, pady=20)

        ttk.Label(self.current_frame, text="Academic Management", style="Header.TLabel").pack(pady=10)

        notebook = ttk.Notebook(self.current_frame)
        notebook.pack(fill="both", expand=True)

        self.class_subject_tab = ttk.Frame(notebook, style="TFrame")
        self.attendance_tab = ttk.Frame(notebook, style="TFrame")
        self.exams_marks_tab = ttk.Frame(notebook, style="TFrame")

        notebook.add(self.class_subject_tab, text="Classes & Subjects")
        notebook.add(self.attendance_tab, text="Attendance")
        notebook.add(self.exams_marks_tab, text="Exams & Marks")

        self._setup_class_subject_tab()
        self._setup_attendance_tab()
        self._setup_exams_marks_tab()

    def _setup_class_subject_tab(self):
        class_frame = ttk.LabelFrame(self.class_subject_tab, text="Classes Management", style="TLabelframe")
        class_frame.pack(fill="x", pady=10, padx=10)
        class_frame.columnconfigure(1, weight=1)
        class_frame.columnconfigure(3, weight=1)

        ttk.Label(class_frame, text="Class Name:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.class_name_entry = ttk.Entry(class_frame)
        self.class_name_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        ttk.Label(class_frame, text="Section:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.class_section_entry = ttk.Entry(class_frame)
        self.class_section_entry.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Button(class_frame, text="Add Class", command=self._add_class).grid(row=0, column=4, padx=5, pady=2)
        ttk.Button(class_frame, text="Update Class", command=self._update_class).grid(row=0, column=5, padx=5, pady=2)
        ttk.Button(class_frame, text="Delete Class", command=self._delete_class).grid(row=0, column=6, padx=5, pady=2)

        self.class_tree = ttk.Treeview(self.class_subject_tab, columns=("ID", "Name", "Section"), show="headings")
        self.class_tree.pack(fill="x", pady=10, padx=10)
        self.class_tree.heading("ID", text="ID")
        self.class_tree.heading("Name", text="Class Name")
        self.class_tree.heading("Section", text="Section")
        self.class_tree.column("ID", width=50, anchor="center")
        self.class_tree.column("Name", width=150, anchor="center")
        self.class_tree.column("Section", width=100, anchor="center")
        self.class_tree.bind("<<TreeviewSelect>>", self._load_class_selection)
        self._load_classes() # Initial load

        subject_frame = ttk.LabelFrame(self.class_subject_tab, text="Subjects Management", style="TLabelframe")
        subject_frame.pack(fill="x", pady=10, padx=10)
        subject_frame.columnconfigure(1, weight=1)

        ttk.Label(subject_frame, text="Subject Name:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.subject_name_entry = ttk.Entry(subject_frame)
        self.subject_name_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Button(subject_frame, text="Add Subject", command=self._add_subject).grid(row=0, column=2, padx=5, pady=2)
        ttk.Button(subject_frame, text="Update Subject", command=self._update_subject).grid(row=0, column=3, padx=5, pady=2)
        ttk.Button(subject_frame, text="Delete Subject", command=self._delete_subject).grid(row=0, column=4, padx=5, pady=2)

        self.subject_tree = ttk.Treeview(self.class_subject_tab, columns=("ID", "Name"), show="headings")
        self.subject_tree.pack(fill="x", pady=10, padx=10)
        self.subject_tree.heading("ID", text="ID")
        self.subject_tree.heading("Name", text="Subject Name")
        self.subject_tree.column("ID", width=50, anchor="center")
        self.subject_tree.column("Name", width=200, anchor="center")
        self.subject_tree.bind("<<TreeviewSelect>>", self._load_subject_selection)
        self._load_subjects() # Initial load

        assign_frame = ttk.LabelFrame(self.class_subject_tab, text="Assign Teachers to Subjects", style="TLabelframe")
        assign_frame.pack(fill="x", pady=10, padx=10)
        assign_frame.columnconfigure(1, weight=1)
        assign_frame.columnconfigure(3, weight=1)
        assign_frame.columnconfigure(5, weight=1)

        ttk.Label(assign_frame, text="Class:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.assign_class_combo = ttk.Combobox(assign_frame, values=self._get_class_names())
        self.assign_class_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(assign_frame, text="Subject:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.assign_subject_combo = ttk.Combobox(assign_frame, values=self._get_subject_names())
        self.assign_subject_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Label(assign_frame, text="Teacher:").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.assign_teacher_combo = ttk.Combobox(assign_frame, values=self._get_teacher_names())
        self.assign_teacher_combo.grid(row=0, column=5, padx=5, pady=2, sticky="ew")

        ttk.Button(assign_frame, text="Assign", command=self._assign_teacher_to_subject).grid(row=0, column=6, padx=5, pady=2)
        ttk.Button(assign_frame, text="Remove Assignment", command=self._remove_teacher_assignment).grid(row=0, column=7, padx=5, pady=2)

        self.assignment_tree = ttk.Treeview(self.class_subject_tab, columns=("ID", "Class", "Subject", "Teacher"), show="headings")
        self.assignment_tree.pack(fill="both", expand=True, pady=10, padx=10)
        self.assignment_tree.heading("ID", text="ID")
        self.assignment_tree.heading("Class", text="Class")
        self.assignment_tree.heading("Subject", text="Subject")
        self.assignment_tree.heading("Teacher", text="Teacher")
        self.assignment_tree.column("ID", width=50, anchor="center")
        self.assignment_tree.column("Class", width=150, anchor="center")
        self.assignment_tree.column("Subject", width=150, anchor="center")
        self.assignment_tree.column("Teacher", width=150, anchor="center")
        self._load_assignments() # Initial load

    def _load_classes(self):
        for i in self.class_tree.get_children():
            self.class_tree.delete(i)
        self.cursor.execute("SELECT * FROM classes")
        for row in self.cursor.fetchall():
            self.class_tree.insert("", "end", values=row)
        # Update combobox values after loading classes
        if hasattr(self, 'assign_class_combo'): # Check if widget exists before updating
            self.assign_class_combo['values'] = self._get_class_names()
        if hasattr(self, 'attendance_class_combo'):
            self.attendance_class_combo['values'] = self._get_class_names()
        if hasattr(self, 'exam_class_combo'):
            self.exam_class_combo['values'] = self._get_class_names()
        if hasattr(self, 'report_class_combo'):
            self.report_class_combo['values'] = self._get_class_names()
        if hasattr(self, 'fee_struct_class_combo'):
            self.fee_struct_class_combo['values'] = self._get_class_names()
        if hasattr(self, 'report_card_class_combo'):
            self.report_card_class_combo['values'] = self._get_class_names()


    def _load_class_selection(self, event):
        selected_item = self.class_tree.focus()
        if selected_item:
            values = self.class_tree.item(selected_item, "values")
            self.class_name_entry.delete(0, tk.END)
            self.class_name_entry.insert(0, values[1])
            self.class_section_entry.delete(0, tk.END)
            self.class_section_entry.insert(0, values[2])

    def _add_class(self):
        name = self.class_name_entry.get()
        section = self.class_section_entry.get()
        if not name:
            messagebox.showerror("Input Error", "Class name is required.")
            return
        try:
            self.cursor.execute("INSERT INTO classes (name, section) VALUES (?, ?)", (name, section))
            self.conn.commit()
            messagebox.showinfo("Success", "Class added successfully!")
            self._load_classes()
            self.class_name_entry.delete(0, tk.END)
            self.class_section_entry.delete(0, tk.END)
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "Class with this name already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")

    def _update_class(self):
        selected_item = self.class_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a class to update.")
            return
        class_id = self.class_tree.item(selected_item, "values")[0]
        name = self.class_name_entry.get()
        section = self.class_section_entry.get()
        if not name:
            messagebox.showerror("Input Error", "Class name is required.")
            return
        try:
            self.cursor.execute("UPDATE classes SET name=?, section=? WHERE id=?", (name, section, class_id))
            self.conn.commit()
            messagebox.showinfo("Success", "Class updated successfully!")
            self._load_classes()
            self.class_name_entry.delete(0, tk.END)
            self.class_section_entry.delete(0, tk.END)
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "Class with this name already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")

    def _delete_class(self):
        selected_item = self.class_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a class to delete.")
            return
        class_id = self.class_tree.item(selected_item, "values")[0]
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this class? This will also delete related assignments, fees, and exams."):
            try:
                self.cursor.execute("DELETE FROM classes WHERE id=?", (class_id,))
                self.cursor.execute("DELETE FROM class_subject_teacher WHERE class_id=?", (class_id,))
                self.cursor.execute("DELETE FROM fee_structures WHERE class_id=?", (class_id,))
                self.cursor.execute("DELETE FROM exams WHERE class_id=?", (class_id,))
                self.conn.commit()
                messagebox.showinfo("Success", "Class deleted successfully!")
                self._load_classes()
                self._load_assignments()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

    def _load_subjects(self):
        for i in self.subject_tree.get_children():
            self.subject_tree.delete(i)
        self.cursor.execute("SELECT * FROM subjects")
        for row in self.cursor.fetchall():
            self.subject_tree.insert("", "end", values=row)
        if hasattr(self, 'assign_subject_combo'):
            self.assign_subject_combo['values'] = self._get_subject_names()
        if hasattr(self, 'marks_subject_combo'):
            self.marks_subject_combo['values'] = self._get_subject_names()

    def _load_subject_selection(self, event):
        selected_item = self.subject_tree.focus()
        if selected_item:
            values = self.subject_tree.item(selected_item, "values")
            self.subject_name_entry.delete(0, tk.END)
            self.subject_name_entry.insert(0, values[1])

    def _add_subject(self):
        name = self.subject_name_entry.get()
        if not name:
            messagebox.showerror("Input Error", "Subject name is required.")
            return
        try:
            self.cursor.execute("INSERT INTO subjects (name) VALUES (?)", (name,))
            self.conn.commit()
            messagebox.showinfo("Success", "Subject added successfully!")
            self._load_subjects()
            self.subject_name_entry.delete(0, tk.END)
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "Subject with this name already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")

    def _update_subject(self):
        selected_item = self.subject_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a subject to update.")
            return
        subject_id = self.subject_tree.item(selected_item, "values")[0]
        name = self.subject_name_entry.get()
        if not name:
            messagebox.showerror("Input Error", "Subject name is required.")
            return
        try:
            self.cursor.execute("UPDATE subjects SET name=? WHERE id=?", (name, subject_id))
            self.conn.commit()
            messagebox.showinfo("Success", "Subject updated successfully!")
            self._load_subjects()
            self.subject_name_entry.delete(0, tk.END)
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "Subject with this name already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")

    def _delete_subject(self):
        selected_item = self.subject_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a subject to delete.")
            return
        subject_id = self.subject_tree.item(selected_item, "values")[0]
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this subject? This will also delete related assignments and marks."):
            try:
                self.cursor.execute("DELETE FROM subjects WHERE id=?", (subject_id,))
                self.cursor.execute("DELETE FROM class_subject_teacher WHERE subject_id=?", (subject_id,))
                self.cursor.execute("DELETE FROM marks WHERE subject_id=?", (subject_id,))
                self.conn.commit()
                messagebox.showinfo("Success", "Subject deleted successfully!")
                self._load_subjects()
                self._load_assignments()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

    def _load_assignments(self):
        for i in self.assignment_tree.get_children():
            self.assignment_tree.delete(i)
        self.cursor.execute("""
            SELECT cst.id, cl.name, s.name, t.name
            FROM class_subject_teacher cst
            JOIN classes cl ON cst.class_id = cl.id
            JOIN subjects s ON cst.subject_id = s.id
            JOIN staff t ON cst.teacher_id = t.id
        """)
        for row in self.cursor.fetchall():
            self.assignment_tree.insert("", "end", values=row)

    def _assign_teacher_to_subject(self):
        class_name = self.assign_class_combo.get()
        subject_name = self.assign_subject_combo.get()
        teacher_name = self.assign_teacher_combo.get()

        if not all([class_name, subject_name, teacher_name]):
            messagebox.showerror("Input Error", "All fields are required for assignment.")
            return

        self.cursor.execute("SELECT id FROM classes WHERE name=?", (class_name,))
        class_id = self.cursor.fetchone()
        self.cursor.execute("SELECT id FROM subjects WHERE name=?", (subject_name,))
        subject_id = self.cursor.fetchone()
        self.cursor.execute("SELECT id FROM staff WHERE name=? AND role='Teacher'", (teacher_name,))
        teacher_id = self.cursor.fetchone()

        if not (class_id and subject_id and teacher_id):
            messagebox.showerror("Error", "Invalid Class, Subject, or Teacher selected.")
            return

        class_id = class_id[0]
        subject_id = subject_id[0]
        teacher_id = teacher_id[0]

        try:
            self.cursor.execute("INSERT INTO class_subject_teacher (class_id, subject_id, teacher_id) VALUES (?, ?, ?)",
                                (class_id, subject_id, teacher_id))
            self.conn.commit()
            messagebox.showinfo("Success", "Teacher assigned to subject successfully!")
            self._load_assignments()
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "This assignment already exists.")
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")

    def _remove_teacher_assignment(self):
        selected_item = self.assignment_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select an assignment to remove.")
            return
        assignment_id = self.assignment_tree.item(selected_item, "values")[0]
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to remove this assignment?"):
            try:
                self.cursor.execute("DELETE FROM class_subject_teacher WHERE id=?", (assignment_id,))
                self.conn.commit()
                messagebox.showinfo("Success", "Assignment removed successfully!")
                self._load_assignments()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

    def _setup_attendance_tab(self):
        mark_frame = ttk.LabelFrame(self.attendance_tab, text="Mark Daily Attendance", style="TLabelframe")
        mark_frame.pack(fill="x", pady=10, padx=10)
        mark_frame.columnconfigure(1, weight=1)
        mark_frame.columnconfigure(3, weight=1)

        ttk.Label(mark_frame, text="Select Class:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.attendance_class_combo = ttk.Combobox(mark_frame, values=self._get_class_names())
        self.attendance_class_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        self.attendance_class_combo.bind("<<ComboboxSelected>>", self._load_students_for_attendance)

        ttk.Label(mark_frame, text="Date (YYYY-MM-DD):").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.attendance_date_entry = ttk.Entry(mark_frame)
        self.attendance_date_entry.grid(row=0, column=3, padx=5, pady=2, sticky="ew")
        self.attendance_date_entry.insert(0, datetime.now().strftime("%Y-%m-%d"))

        ttk.Button(mark_frame, text="Load Students", command=self._load_students_for_attendance).grid(row=0, column=4, padx=5, pady=2)

        self.attendance_tree = ttk.Treeview(self.attendance_tab, columns=("ID", "Name", "Class", "Status"), show="headings")
        self.attendance_tree.pack(fill="both", expand=True, pady=10, padx=10)
        self.attendance_tree.heading("ID", text="Student ID")
        self.attendance_tree.heading("Name", text="Student Name")
        self.attendance_tree.heading("Class", text="Class")
        self.attendance_tree.heading("Status", text="Status")
        self.attendance_tree.column("ID", width=80, anchor="center")
        self.attendance_tree.column("Name", width=200, anchor="w")
        self.attendance_tree.column("Class", width=100, anchor="center")
        self.attendance_tree.column("Status", width=100, anchor="center")

        self.attendance_status_vars = {}
        self.attendance_tree.bind("<ButtonRelease-1>", self._on_attendance_tree_click)

        ttk.Button(self.attendance_tab, text="Save Attendance", command=self._save_attendance).pack(pady=10)

        report_frame = ttk.LabelFrame(self.attendance_tab, text="Attendance Reports", style="TLabelframe")
        report_frame.pack(fill="x", pady=10, padx=10)
        report_frame.columnconfigure(1, weight=1)
        report_frame.columnconfigure(3, weight=1)
        report_frame.columnconfigure(5, weight=1)

        ttk.Label(report_frame, text="Class:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.report_class_combo = ttk.Combobox(report_frame, values=self._get_class_names())
        self.report_class_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(report_frame, text="From Date (YYYY-MM-DD):").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.report_from_date_entry = ttk.Entry(report_frame)
        self.report_from_date_entry.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Label(report_frame, text="To Date (YYYY-MM-DD):").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.report_to_date_entry = ttk.Entry(report_frame)
        self.report_to_date_entry.grid(row=0, column=5, padx=5, pady=2, sticky="ew")

        ttk.Button(report_frame, text="Generate Report", command=self._generate_attendance_report).grid(row=0, column=6, padx=5, pady=2)

    def _load_students_for_attendance(self, event=None):
        for i in self.attendance_tree.get_children():
            self.attendance_tree.delete(i)
        self.attendance_status_vars.clear()

        selected_class = self.attendance_class_combo.get()
        attendance_date = self.attendance_date_entry.get()

        if not selected_class:
            messagebox.showwarning("Selection Error", "Please select a class.")
            return

        self.cursor.execute("SELECT id, name, class FROM students WHERE class=?", (selected_class,))
        students = self.cursor.fetchall()

        for student_id, name, s_class in students:
            status_var = tk.StringVar(value="Absent")
            self.attendance_status_vars[student_id] = status_var

            self.cursor.execute("SELECT status FROM attendance WHERE student_id=? AND date=?", (student_id, attendance_date))
            existing_status = self.cursor.fetchone()
            if existing_status:
                status_var.set(existing_status[0])

            self.attendance_tree.insert("", "end", iid=student_id, values=(student_id, name, s_class, status_var.get()))

    def _on_attendance_tree_click(self, event):
        region = self.attendance_tree.identify("region", event.x, event.y)
        if region == "cell":
            column = self.attendance_tree.identify_column(event.x)
            if column == "#4":
                item_id = self.attendance_tree.identify_row(event.y)
                if item_id:
                    student_id = int(item_id)
                    current_status = self.attendance_status_vars[student_id].get()
                    statuses = ["Present", "Absent", "Leave", "Late"]
                    next_index = (statuses.index(current_status) + 1) % len(statuses)
                    new_status = statuses[next_index]
                    self.attendance_status_vars[student_id].set(new_status)
                    self.attendance_tree.item(item_id, values=(self.attendance_tree.item(item_id, "values")[0],
                                                                self.attendance_tree.item(item_id, "values")[1],
                                                                self.attendance_tree.item(item_id, "values")[2],
                                                                new_status))

    def _save_attendance(self):
        attendance_date = self.attendance_date_entry.get()
        if not attendance_date:
            messagebox.showerror("Input Error", "Please enter a date for attendance.")
            return

        if not self.attendance_status_vars:
            messagebox.showwarning("No Students", "No students loaded to save attendance for.")
            return

        try:
            for student_id, status_var in self.attendance_status_vars.items():
                status = status_var.get()
                self.cursor.execute("INSERT OR REPLACE INTO attendance (student_id, date, status) VALUES (?, ?, ?)",
                                    (student_id, attendance_date, status))
            self.conn.commit()
            messagebox.showinfo("Success", "Attendance saved successfully!")
            self.load_dashboard_data()
        except Exception as e:
            messagebox.showerror("Error", f"Failed to save attendance: {e}")

    def _generate_attendance_report(self):
        selected_class = self.report_class_combo.get()
        from_date = self.report_from_date_entry.get()
        to_date = self.report_to_date_entry.get()

        if not selected_class or not from_date or not to_date:
            messagebox.showwarning("Input Error", "Please select a class and date range for the report.")
            return

        report_dialog = tk.Toplevel(self.root)
        report_dialog.title(f"Attendance Report for {selected_class}")
        report_dialog.transient(self.root)
        report_dialog.grab_set()
        report_dialog.geometry("800x600")

        report_tree = ttk.Treeview(report_dialog, columns=("Student Name", "Total Present", "Total Absent", "Total Leave", "Total Late"), show="headings")
        report_tree.pack(fill="both", expand=True, padx=10, pady=10)

        for col in report_tree["columns"]:
            report_tree.heading(col, text=col)
            report_tree.column(col, width=150, anchor="center")

        self.cursor.execute("SELECT id, name FROM students WHERE class=?", (selected_class,))
        students_in_class = self.cursor.fetchall()

        for student_id, student_name in students_in_class:
            self.cursor.execute("""
                SELECT status, COUNT(*) FROM attendance
                WHERE student_id=? AND date BETWEEN ? AND ?
                GROUP BY status
            """, (student_id, from_date, to_date))
            attendance_summary = self.cursor.fetchall()

            present = 0
            absent = 0
            leave = 0
            late = 0
            for status, count in attendance_summary:
                if status == "Present":
                    present = count
                elif status == "Absent":
                    absent = count
                elif status == "Leave":
                    leave = count
                elif status == "Late":
                    late = count
            report_tree.insert("", "end", values=(student_name, present, absent, leave, late))

    def _setup_exams_marks_tab(self):
        exam_setup_frame = ttk.LabelFrame(self.exams_marks_tab, text="Exam Setup", style="TLabelframe")
        exam_setup_frame.pack(fill="x", pady=10, padx=10)
        exam_setup_frame.columnconfigure(1, weight=1)
        exam_setup_frame.columnconfigure(3, weight=1)
        exam_setup_frame.columnconfigure(5, weight=1)

        ttk.Label(exam_setup_frame, text="Exam Name:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.exam_name_entry = ttk.Entry(exam_setup_frame)
        self.exam_name_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(exam_setup_frame, text="Exam Date (YYYY-MM-DD):").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.exam_date_entry = ttk.Entry(exam_setup_frame)
        self.exam_date_entry.grid(row=0, column=3, padx=5, pady=2, sticky="ew")
        self.exam_date_entry.insert(0, datetime.now().strftime("%Y-%m-%d"))

        ttk.Label(exam_setup_frame, text="Class:").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.exam_class_combo = ttk.Combobox(exam_setup_frame, values=self._get_class_names())
        self.exam_class_combo.grid(row=0, column=5, padx=5, pady=2, sticky="ew")

        ttk.Button(exam_setup_frame, text="Add Exam", command=self._add_exam).grid(row=0, column=6, padx=5, pady=2)
        ttk.Button(exam_setup_frame, text="Delete Exam", command=self._delete_exam).grid(row=0, column=7, padx=5, pady=2)

        self.exam_tree = ttk.Treeview(self.exams_marks_tab, columns=("ID", "Name", "Date", "Class"), show="headings")
        self.exam_tree.pack(fill="x", pady=10, padx=10)
        self.exam_tree.heading("ID", text="ID")
        self.exam_tree.heading("Name", text="Exam Name")
        self.exam_tree.heading("Date", text="Date")
        self.exam_tree.heading("Class", text="Class")
        self.exam_tree.column("ID", width=50, anchor="center")
        self.exam_tree.column("Name", width=150, anchor="center")
        self.exam_tree.column("Date", width=100, anchor="center")
        self.exam_tree.column("Class", width=100, anchor="center")
        self._load_exams()

        marks_entry_frame = ttk.LabelFrame(self.exams_marks_tab, text="Enter Marks", style="TLabelframe")
        marks_entry_frame.pack(fill="x", pady=10, padx=10)
        marks_entry_frame.columnconfigure(1, weight=1)
        marks_entry_frame.columnconfigure(3, weight=1)

        ttk.Label(marks_entry_frame, text="Select Exam:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.marks_exam_combo = ttk.Combobox(marks_entry_frame, values=self._get_exam_names())
        self.marks_exam_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        self.marks_exam_combo.bind("<<ComboboxSelected>>", self._load_students_for_marks_entry)

        ttk.Label(marks_entry_frame, text="Select Subject:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.marks_subject_combo = ttk.Combobox(marks_entry_frame, values=self._get_subject_names())
        self.marks_subject_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")
        self.marks_subject_combo.bind("<<ComboboxSelected>>", self._load_students_for_marks_entry)

        ttk.Button(marks_entry_frame, text="Load Students", command=self._load_students_for_marks_entry).grid(row=0, column=4, padx=5, pady=2)

        self.marks_tree = ttk.Treeview(self.exams_marks_tab, columns=("ID", "Name", "Marks"), show="headings")
        self.marks_tree.pack(fill="both", expand=True, pady=10, padx=10)
        self.marks_tree.heading("ID", text="Student ID")
        self.marks_tree.heading("Name", text="Student Name")
        self.marks_tree.heading("Marks", text="Marks")
        self.marks_tree.column("ID", width=80, anchor="center")
        self.marks_tree.column("Name", width=200, anchor="w")
        self.marks_tree.column("Marks", width=100, anchor="center")

        self.marks_entry_widgets = {}
        self.marks_tree.bind("<Double-1>", self._on_marks_tree_double_click)

        ttk.Button(self.exams_marks_tab, text="Save Marks", command=self._save_marks).pack(pady=10)

        report_card_frame = ttk.LabelFrame(self.exams_marks_tab, text="Generate Report Cards", style="TLabelframe")
        report_card_frame.pack(fill="x", pady=10, padx=10)
        report_card_frame.columnconfigure(1, weight=1)
        report_card_frame.columnconfigure(3, weight=1)

        ttk.Label(report_card_frame, text="Select Exam:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.report_card_exam_combo = ttk.Combobox(report_card_frame, values=self._get_exam_names())
        self.report_card_exam_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(report_card_frame, text="Select Class:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.report_card_class_combo = ttk.Combobox(report_card_frame, values=self._get_class_names())
        self.report_card_class_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Button(report_card_frame, text="Generate Report Cards", command=self._generate_report_cards).grid(row=0, column=4, padx=5, pady=2)

    def _get_exam_names(self):
        self.cursor.execute("SELECT name FROM exams")
        return [row[0] for row in self.cursor.fetchall()]

    def _load_exams(self):
        for i in self.exam_tree.get_children():
            self.exam_tree.delete(i)
        self.cursor.execute("""
            SELECT e.id, e.name, e.exam_date, c.name
            FROM exams e JOIN classes c ON e.class_id = c.id
        """)
        for row in self.cursor.fetchall():
            self.exam_tree.insert("", "end", values=row)
        if hasattr(self, 'marks_exam_combo'):
            self.marks_exam_combo['values'] = self._get_exam_names()
        if hasattr(self, 'report_card_exam_combo'):
            self.report_card_exam_combo['values'] = self._get_exam_names()

    def _add_exam(self):
        name = self.exam_name_entry.get()
        exam_date = self.exam_date_entry.get()
        class_name = self.exam_class_combo.get()

        if not all([name, exam_date, class_name]):
            messagebox.showerror("Input Error", "All exam fields are required.")
            return

        self.cursor.execute("SELECT id FROM classes WHERE name=?", (class_name,))
        class_id = self.cursor.fetchone()
        if not class_id:
            messagebox.showerror("Error", "Invalid Class selected.")
            return
        class_id = class_id[0]

        try:
            self.cursor.execute("INSERT INTO exams (name, exam_date, class_id) VALUES (?, ?, ?)", (name, exam_date, class_id))
            self.conn.commit()
            messagebox.showinfo("Success", "Exam added successfully!")
            self._load_exams()
            self.exam_name_entry.delete(0, tk.END)
            self.exam_date_entry.delete(0, tk.END)
            self.exam_class_combo.set('')
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")

    def _delete_exam(self):
        selected_item = self.exam_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select an exam to delete.")
            return
        exam_id = self.exam_tree.item(selected_item, "values")[0]
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this exam? This will also delete all associated marks."):
            try:
                self.cursor.execute("DELETE FROM exams WHERE id=?", (exam_id,))
                self.cursor.execute("DELETE FROM marks WHERE exam_id=?", (exam_id,))
                self.conn.commit()
                messagebox.showinfo("Success", "Exam deleted successfully!")
                self._load_exams()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

    def _load_students_for_marks_entry(self, event=None):
        for i in self.marks_tree.get_children():
            self.marks_tree.delete(i)
        self.marks_entry_widgets.clear()

        exam_name = self.marks_exam_combo.get()
        subject_name = self.marks_subject_combo.get()

        if not exam_name or not subject_name:
            return

        self.cursor.execute("SELECT id, class_id FROM exams WHERE name=?", (exam_name,))
        exam_data = self.cursor.fetchone()
        if not exam_data: return
        exam_id, class_id = exam_data

        self.cursor.execute("SELECT id FROM subjects WHERE name=?", (subject_name,))
        subject_id = self.cursor.fetchone()
        if not subject_id: return
        subject_id = subject_id[0]

        self.cursor.execute("SELECT id, name FROM students WHERE class=(SELECT name FROM classes WHERE id=?)", (class_id,))
        students = self.cursor.fetchall()

        for student_id, student_name in students:
            self.cursor.execute("SELECT marks FROM marks WHERE exam_id=? AND student_id=? AND subject_id=?",
                                (exam_id, student_id, subject_id))
            existing_marks = self.cursor.fetchone()
            marks_value = existing_marks[0] if existing_marks and existing_marks[0] is not None else ""

            self.marks_tree.insert("", "end", iid=student_id, values=(student_id, student_name, marks_value))

    def _on_marks_tree_double_click(self, event):
        item_id = self.marks_tree.identify_row(event.y)
        if not item_id: return

        col = self.marks_tree.identify_column(event.x)
        if col == "#3":
            x, y, width, height = self.marks_tree.bbox(item_id, col)
            
            student_id = int(item_id)
            current_marks = self.marks_tree.item(item_id, "values")[2]

            entry = ttk.Entry(self.marks_tree, width=width // 8)
            entry.place(x=x, y=y, width=width, height=height)
            entry.insert(0, current_marks)
            entry.focus_set()

            def on_enter(e):
                new_marks = entry.get()
                try:
                    new_marks = float(new_marks)
                    self.marks_tree.item(item_id, values=(self.marks_tree.item(item_id, "values")[0],
                                                          self.marks_tree.item(item_id, "values")[1],
                                                          new_marks))
                    self.marks_entry_widgets[student_id] = new_marks
                except ValueError:
                    messagebox.showerror("Input Error", "Marks must be a number.")
                entry.destroy()

            entry.bind("<Return>", on_enter)
            entry.bind("<FocusOut>", on_enter)

    def _save_marks(self):
        exam_name = self.marks_exam_combo.get()
        subject_name = self.marks_subject_combo.get()

        if not exam_name or not subject_name:
            messagebox.showerror("Input Error", "Please select an exam and subject.")
            return

        self.cursor.execute("SELECT id FROM exams WHERE name=?", (exam_name,))
        exam_id = self.cursor.fetchone()
        self.cursor.execute("SELECT id FROM subjects WHERE name=?", (subject_name,))
        subject_id = self.cursor.fetchone()

        if not (exam_id and subject_id):
            messagebox.showerror("Error", "Invalid Exam or Subject selected.")
            return
        exam_id = exam_id[0]
        subject_id = subject_id[0]

        try:
            for item_id in self.marks_tree.get_children():
                student_id, _, marks_value = self.marks_tree.item(item_id, "values")
                
                if marks_value == "":
                    marks_value = None
                else:
                    try:
                        marks_value = float(marks_value)
                    except ValueError:
                        messagebox.showwarning("Invalid Marks", f"Invalid marks for student ID {student_id}. Skipping.")
                        continue

                self.cursor.execute("INSERT OR REPLACE INTO marks (exam_id, student_id, subject_id, marks) VALUES (?, ?, ?, ?)",
                                    (exam_id, student_id, subject_id, marks_value))
            self.conn.commit()
            messagebox.showinfo("Success", "Marks saved successfully!")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to save marks: {e}")

    def _generate_report_cards(self):
        exam_name = self.report_card_exam_combo.get()
        class_name = self.report_card_class_combo.get()

        if not exam_name or not class_name:
            messagebox.showwarning("Input Error", "Please select an exam and class to generate report cards.")
            return

        self.cursor.execute("SELECT id FROM exams WHERE name=?", (exam_name,))
        exam_id = self.cursor.fetchone()
        self.cursor.execute("SELECT id FROM classes WHERE name=?", (class_name,))
        class_id = self.cursor.fetchone()

        if not (exam_id and class_id):
            messagebox.showerror("Error", "Invalid Exam or Class selected.")
            return
        exam_id = exam_id[0]
        class_id = class_id[0]

        report_dialog = tk.Toplevel(self.root)
        report_dialog.title(f"Report Cards for {class_name} - {exam_name}")
        report_dialog.transient(self.root)
        report_dialog.grab_set()
        report_dialog.geometry("900x700")

        canvas = tk.Canvas(report_dialog, bg="white")
        canvas.pack(side="left", fill="both", expand=True)
        scrollbar = ttk.Scrollbar(report_dialog, orient="vertical", command=canvas.yview)
        scrollbar.pack(side="right", fill="y")
        canvas.configure(yscrollcommand=scrollbar.set)
        canvas.bind('<Configure>', lambda e: canvas.configure(scrollregion = canvas.bbox("all")))

        report_frame = ttk.Frame(canvas, style="TFrame")
        canvas.create_window((0, 0), window=report_frame, anchor="nw")

        self.cursor.execute("SELECT id, name, father_name, class FROM students WHERE class=?", (class_name,))
        students = self.cursor.fetchall()

        self.cursor.execute("""
            SELECT s.name, cst.subject_id
            FROM class_subject_teacher cst
            JOIN subjects s ON cst.subject_id = s.id
            WHERE cst.class_id = ?
        """, (class_id,))
        subjects_in_class = self.cursor.fetchall()
        subject_names = [s[0] for s in subjects_in_class]
        subject_ids = {s[0]: s[1] for s in subjects_in_class}

        for student_id, student_name, father_name, student_class in students:
            card_frame = ttk.LabelFrame(report_frame, text=f"Report Card: {student_name}", style="TLabelframe")
            card_frame.pack(fill="x", padx=10, pady=10)

            ttk.Label(card_frame, text=f"Student Name: {student_name}", font=("Arial", 12, "bold")).pack(anchor="w", pady=2, padx=5)
            ttk.Label(card_frame, text=f"Father Name: {father_name}", font=("Arial", 10)).pack(anchor="w", pady=2, padx=5)
            ttk.Label(card_frame, text=f"Class: {student_class}", font=("Arial", 10)).pack(anchor="w", pady=2, padx=5)
            ttk.Label(card_frame, text=f"Exam: {exam_name}", font=("Arial", 10)).pack(anchor="w", pady=2, padx=5)
            ttk.Label(card_frame, text="--- Marks ---", font=("Arial", 10, "underline")).pack(anchor="w", pady=5, padx=5)

            marks_data = []
            total_marks_obtained = 0
            total_max_marks = 0
            
            for sub_name in subject_names:
                sub_id = subject_ids[sub_name]
                self.cursor.execute("SELECT marks FROM marks WHERE exam_id=? AND student_id=? AND subject_id=?",
                                    (exam_id, student_id, sub_id))
                marks = self.cursor.fetchone()
                marks_val = marks[0] if marks and marks[0] is not None else "N/A"
                marks_data.append((sub_name, marks_val))
                
                if isinstance(marks_val, (int, float)):
                    total_marks_obtained += marks_val
                    total_max_marks += 100

            for sub, mark in marks_data:
                ttk.Label(card_frame, text=f"{sub}: {mark}").pack(anchor="w", padx=15)

            percentage = (total_marks_obtained / total_max_marks * 100) if total_max_marks > 0 else 0
            grade = self._calculate_grade(percentage)

            ttk.Label(card_frame, text="--- Summary ---", font=("Arial", 10, "underline")).pack(anchor="w", pady=5, padx=5)
            ttk.Label(card_frame, text=f"Total Marks Obtained: {total_marks_obtained}", font=("Arial", 10)).pack(anchor="w", padx=15)
            ttk.Label(card_frame, text=f"Percentage: {percentage:.2f}%", font=("Arial", 10)).pack(anchor="w", padx=15)
            ttk.Label(card_frame, text=f"Grade: {grade}", font=("Arial", 12, "bold")).pack(anchor="w", padx=15)

            ttk.Label(card_frame, text="\n").pack()

        report_frame.update_idletasks()
        canvas.config(scrollregion=canvas.bbox("all"))

    def _calculate_grade(self, percentage):
        if percentage >= 90: return "A+"
        elif percentage >= 80: return "A"
        elif percentage >= 70: return "B"
        elif percentage >= 60: return "C"
        elif percentage >= 50: return "D"
        else: return "F"

    def show_financial_management(self):
        self.switch_frame(self._create_financial_management_frame)

    def _create_financial_management_frame(self):
        self.current_frame = ttk.Frame(self.main_content, style="TFrame")
        self.current_frame.pack(fill="both", expand=True, padx=20, pady=20)

        ttk.Label(self.current_frame, text="Financial Management", style="Header.TLabel").pack(pady=10)

        notebook = ttk.Notebook(self.current_frame)
        notebook.pack(fill="both", expand=True)

        self.fees_tab = ttk.Frame(notebook, style="TFrame")
        self.salary_tab = ttk.Frame(notebook, style="TFrame")
        self.expense_income_tab = ttk.Frame(notebook, style="TFrame")

        notebook.add(self.fees_tab, text="Fees Management")
        notebook.add(self.salary_tab, text="Salary Management")
        notebook.add(self.expense_income_tab, text="Expense/Income Tracking")

        self._setup_fees_tab()
        self._setup_salary_tab()
        self._setup_expense_income_tab()

    def _setup_fees_tab(self):
        fee_structure_frame = ttk.LabelFrame(self.fees_tab, text="Fee Structure Setup", style="TLabelframe")
        fee_structure_frame.pack(fill="x", pady=10, padx=10)
        fee_structure_frame.columnconfigure(1, weight=1)
        fee_structure_frame.columnconfigure(3, weight=1)
        fee_structure_frame.columnconfigure(5, weight=1)

        ttk.Label(fee_structure_frame, text="Class:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.fee_struct_class_combo = ttk.Combobox(fee_structure_frame, values=self._get_class_names())
        self.fee_struct_class_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(fee_structure_frame, text="Category:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.fee_struct_category_combo = ttk.Combobox(fee_structure_frame, values=["General", "Deserving"])
        self.fee_struct_category_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Label(fee_structure_frame, text="Amount:").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.fee_struct_amount_entry = ttk.Entry(fee_structure_frame)
        self.fee_struct_amount_entry.grid(row=0, column=5, padx=5, pady=2, sticky="ew")

        ttk.Button(fee_structure_frame, text="Add/Update Structure", command=self._add_update_fee_structure).grid(row=0, column=6, padx=5, pady=2)
        ttk.Button(fee_structure_frame, text="Delete Structure", command=self._delete_fee_structure).grid(row=0, column=7, padx=5, pady=2)

        self.fee_structure_tree = ttk.Treeview(self.fees_tab, columns=("ID", "Class", "Category", "Amount"), show="headings")
        self.fee_structure_tree.pack(fill="x", pady=10, padx=10)
        self.fee_structure_tree.heading("ID", text="ID")
        self.fee_structure_tree.heading("Class", text="Class")
        self.fee_structure_tree.heading("Category", text="Category")
        self.fee_structure_tree.heading("Amount", text="Amount")
        self.fee_structure_tree.column("ID", width=50, anchor="center")
        self.fee_structure_tree.column("Class", width=150, anchor="center")
        self.fee_structure_tree.column("Category", width=100, anchor="center")
        self.fee_structure_tree.column("Amount", width=100, anchor="center")
        self.fee_structure_tree.bind("<<TreeviewSelect>>", self._load_fee_structure_selection)
        self._load_fee_structures()

        fee_collection_frame = ttk.LabelFrame(self.fees_tab, text="Fee Collection", style="TLabelframe")
        fee_collection_frame.pack(fill="x", pady=10, padx=10)
        fee_collection_frame.columnconfigure(1, weight=1)
        fee_collection_frame.columnconfigure(3, weight=1)

        ttk.Label(fee_collection_frame, text="Student Name/ID:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.fee_student_search_entry = ttk.Entry(fee_collection_frame)
        self.fee_student_search_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        ttk.Button(fee_collection_frame, text="Search Student", command=self._search_student_for_fee).grid(row=0, column=2, padx=5, pady=2)

        ttk.Label(fee_collection_frame, text="Selected Student:").grid(row=1, column=0, padx=5, pady=2, sticky="w")
        self.selected_fee_student_label = ttk.Label(fee_collection_frame, text="", foreground="blue")
        self.selected_fee_student_label.grid(row=1, column=1, columnspan=2, sticky="w", padx=5, pady=2)
        self.selected_fee_student_id = None

        ttk.Label(fee_collection_frame, text="Amount Due:").grid(row=2, column=0, padx=5, pady=2, sticky="w")
        self.fee_amount_due_label = ttk.Label(fee_collection_frame, text="0.00", foreground="red")
        self.fee_amount_due_label.grid(row=2, column=1, sticky="w", padx=5, pady=2)

        ttk.Label(fee_collection_frame, text="Amount Paid:").grid(row=2, column=2, padx=5, pady=2, sticky="w")
        self.fee_amount_paid_entry = ttk.Entry(fee_collection_frame)
        self.fee_amount_paid_entry.grid(row=2, column=3, padx=5, pady=2, sticky="ew")

        ttk.Label(fee_collection_frame, text="Discount:").grid(row=3, column=0, padx=5, pady=2, sticky="w")
        self.fee_discount_entry = ttk.Entry(fee_collection_frame)
        self.fee_discount_entry.grid(row=3, column=1, padx=5, pady=2, sticky="ew")
        self.fee_discount_entry.insert(0, "0")

        ttk.Label(fee_collection_frame, text="Fine:").grid(row=3, column=2, padx=5, pady=2, sticky="w")
        self.fee_fine_entry = ttk.Entry(fee_collection_frame)
        self.fee_fine_entry.grid(row=3, column=3, padx=5, pady=2, sticky="ew")
        self.fee_fine_entry.insert(0, "0")

        ttk.Label(fee_collection_frame, text="Month:").grid(row=4, column=0, padx=5, pady=2, sticky="w")
        self.fee_month_combo = ttk.Combobox(fee_collection_frame, values=[datetime.strftime(datetime(2000, i, 1), '%B') for i in range(1, 13)])
        self.fee_month_combo.grid(row=4, column=1, padx=5, pady=2, sticky="ew")
        self.fee_month_combo.set(datetime.now().strftime("%B"))

        ttk.Label(fee_collection_frame, text="Year:").grid(row=4, column=2, padx=5, pady=2, sticky="w")
        self.fee_year_entry = ttk.Entry(fee_collection_frame)
        self.fee_year_entry.grid(row=4, column=3, padx=5, pady=2, sticky="ew")
        self.fee_year_entry.insert(0, datetime.now().year)

        ttk.Button(fee_collection_frame, text="Collect Fee", command=self._collect_fee).grid(row=5, column=0, columnspan=2, pady=10)
        ttk.Button(fee_collection_frame, text="Generate Receipt", command=self._generate_receipt).grid(row=5, column=2, columnspan=2, pady=10)
        ttk.Button(fee_collection_frame, text="Defaulter List", command=self._show_defaulter_list).grid(row=5, column=4, columnspan=2, pady=10)

        self.fee_collection_tree = ttk.Treeview(self.fees_tab, columns=("ID", "Student", "Amount", "Date", "Month", "Year", "Discount", "Fine", "Receipt No"), show="headings")
        self.fee_collection_tree.pack(fill="both", expand=True, pady=10, padx=10)
        for col in self.fee_collection_tree["columns"]:
            self.fee_collection_tree.heading(col, text=col)
            self.fee_collection_tree.column(col, width=80, anchor="center")
        self._load_fee_collections()

    def _load_fee_structures(self):
        for i in self.fee_structure_tree.get_children():
            self.fee_structure_tree.delete(i)
        self.cursor.execute("""
            SELECT fs.id, c.name, fs.category, fs.amount
            FROM fee_structures fs JOIN classes c ON fs.class_id = c.id
        """)
        for row in self.cursor.fetchall():
            self.fee_structure_tree.insert("", "end", values=row)

    def _load_fee_structure_selection(self, event):
        selected_item = self.fee_structure_tree.focus()
        if selected_item:
            values = self.fee_structure_tree.item(selected_item, "values")
            self.fee_struct_class_combo.set(values[1])
            self.fee_struct_category_combo.set(values[2])
            self.fee_struct_amount_entry.delete(0, tk.END)
            self.fee_struct_amount_entry.insert(0, values[3])

    def _add_update_fee_structure(self):
        class_name = self.fee_struct_class_combo.get()
        category = self.fee_struct_category_combo.get()
        amount = self.fee_struct_amount_entry.get()

        if not all([class_name, category, amount]):
            messagebox.showerror("Input Error", "All fee structure fields are required.")
            return
        try:
            amount = float(amount)
        except ValueError:
            messagebox.showerror("Input Error", "Amount must be a number.")
            return

        self.cursor.execute("SELECT id FROM classes WHERE name=?", (class_name,))
        class_id = self.cursor.fetchone()
        if not class_id:
            messagebox.showerror("Error", "Invalid Class selected.")
            return
        class_id = class_id[0]

        try:
            self.cursor.execute("SELECT id FROM fee_structures WHERE class_id=? AND category=?", (class_id, category))
            existing_id = self.cursor.fetchone()
            if existing_id:
                self.cursor.execute("UPDATE fee_structures SET amount=? WHERE id=?", (amount, existing_id[0]))
                messagebox.showinfo("Success", "Fee structure updated successfully!")
            else:
                self.cursor.execute("INSERT INTO fee_structures (class_id, category, amount) VALUES (?, ?, ?)", (class_id, category, amount))
                messagebox.showinfo("Success", "Fee structure added successfully!")
            self.conn.commit()
            self._load_fee_structures()
            self.fee_struct_class_combo.set('')
            self.fee_struct_category_combo.set('')
            self.fee_struct_amount_entry.delete(0, tk.END)
        except Exception as e:
            messagebox.showerror("Error", f"An error occurred: {e}")

    def _delete_fee_structure(self):
        selected_item = self.fee_structure_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a fee structure to delete.")
            return
        fee_struct_id = self.fee_structure_tree.item(selected_item, "values")[0]
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this fee structure?"):
            try:
                self.cursor.execute("DELETE FROM fee_structures WHERE id=?", (fee_struct_id,))
                self.conn.commit()
                messagebox.showinfo("Success", "Fee structure deleted successfully!")
                self._load_fee_structures()
            except Exception as e:
                messagebox.showerror("Error", f"An error occurred: {e}")

    def _search_student_for_fee(self):
        search_term = self.fee_student_search_entry.get()
        if not search_term:
            messagebox.showwarning("Input Error", "Please enter student name or ID.")
            return

        self.cursor.execute("SELECT id, name, class, category FROM students WHERE id=? OR name LIKE ?", (search_term, f"%{search_term}%"))
        student_data = self.cursor.fetchone()

        if student_data:
            student_id, name, s_class, category = student_data
            self.selected_fee_student_id = student_id
            self.selected_fee_student_label.config(text=f"{name} (Class: {s_class}, Category: {category})")

            self.cursor.execute("SELECT fs.amount FROM fee_structures fs JOIN classes c ON fs.class_id = c.id WHERE c.name=? AND fs.category=?", (s_class, category))
            fee_amount = self.cursor.fetchone()
            if fee_amount:
                self.fee_amount_due_label.config(text=f"{fee_amount[0]:,.2f}")
                self.fee_amount_paid_entry.delete(0, tk.END)
                self.fee_amount_paid_entry.insert(0, str(fee_amount[0]))
            else:
                self.fee_amount_due_label.config(text="N/A (Fee structure not found)")
                self.fee_amount_paid_entry.delete(0, tk.END)
                messagebox.showwarning("Fee Structure Missing", f"No fee structure found for Class: {s_class}, Category: {category}.")
        else:
            self.selected_fee_student_id = None
            self.selected_fee_student_label.config(text="Student Not Found")
            self.fee_amount_due_label.config(text="0.00")
            self.fee_amount_paid_entry.delete(0, tk.END)
            messagebox.showinfo("Not Found", "No student found with that name or ID.")

    def _collect_fee(self):
        if not self.selected_fee_student_id:
            messagebox.showerror("Error", "Please select a student first.")
            return

        student_id = self.selected_fee_student_id
        amount_paid = self.fee_amount_paid_entry.get()
        discount = self.fee_discount_entry.get()
        fine = self.fee_fine_entry.get()
        month = self.fee_month_combo.get()
        year = self.fee_year_entry.get()
        collection_date = datetime.now().strftime("%Y-%m-%d")
        receipt_no = f"REC-{datetime.now().strftime('%Y%m%d%H%M%S')}-{student_id}"

        try:
            amount_paid = float(amount_paid)
            discount = float(discount)
            fine = float(fine)
        except ValueError:
            messagebox.showerror("Input Error", "Amount, Discount, and Fine must be numbers.")
            return

        if amount_paid <= 0:
            messagebox.showerror("Input Error", "Amount paid must be greater than zero.")
            return

        try:
            self.cursor.execute("INSERT INTO fee_collections (student_id, amount, collection_date, month, year, discount, fine, receipt_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                                (student_id, amount_paid, collection_date, month, year, discount, fine, receipt_no))
            self.conn.commit()
            messagebox.showinfo("Success", f"Fee collected successfully! Receipt No: {receipt_no}")
            self._load_fee_collections()
            self.load_dashboard_data()
            self.fee_student_search_entry.delete(0, tk.END)
            self.selected_fee_student_label.config(text="")
            self.fee_amount_due_label.config(text="0.00")
            self.fee_amount_paid_entry.delete(0, tk.END)
            self.fee_discount_entry.delete(0, tk.END)
            self.fee_discount_entry.insert(0, "0")
            self.fee_fine_entry.delete(0, tk.END)
            self.fee_fine_entry.insert(0, "0")
            self.selected_fee_student_id = None
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "Receipt number already exists. Please try again.")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to collect fee: {e}")

    def _load_fee_collections(self):
        for i in self.fee_collection_tree.get_children():
            self.fee_collection_tree.delete(i)
        self.cursor.execute("""
            SELECT fc.id, s.name, fc.amount, fc.collection_date, fc.month, fc.year, fc.discount, fc.fine, fc.receipt_no
            FROM fee_collections fc JOIN students s ON fc.student_id = s.id
            ORDER BY fc.collection_date DESC LIMIT 50
        """)
        for row in self.cursor.fetchall():
            self.fee_collection_tree.insert("", "end", values=row)

    def _generate_receipt(self):
        selected_item = self.fee_collection_tree.focus()
        if not selected_item:
            messagebox.showwarning("No Selection", "Please select a fee collection to generate receipt.")
            return
        
        values = self.fee_collection_tree.item(selected_item, "values")
        receipt_data = {
            "Receipt No": values[8],
            "Student Name": values[1],
            "Amount Paid": values[2],
            "Collection Date": values[3],
            "Month": values[4],
            "Year": values[5],
            "Discount": values[6],
            "Fine": values[7]
        }

        receipt_dialog = tk.Toplevel(self.root)
        receipt_dialog.title("Fee Receipt")
        receipt_dialog.transient(self.root)
        receipt_dialog.grab_set()
        receipt_dialog.geometry("400x400")
        receipt_dialog.resizable(False, False)

        ttk.Label(receipt_dialog, text="--- Fee Receipt ---", font=("Arial", 14, "bold")).pack(pady=10)
        for key, value in receipt_data.items():
            ttk.Label(receipt_dialog, text=f"{key}: {value}", font=("Arial", 10)).pack(anchor="w", padx=20)
        ttk.Label(receipt_dialog, text="\nThank you for your payment!", font=("Arial", 10, "italic")).pack(pady=10)
        ttk.Button(receipt_dialog, text="Print (Simulated)", command=lambda: messagebox.showinfo("Print", "Printing receipt...")).pack(pady=10)

    def _show_defaulter_list(self):
        defaulter_dialog = tk.Toplevel(self.root)
        defaulter_dialog.title("Defaulter List")
        defaulter_dialog.transient(self.root)
        defaulter_dialog.grab_set()
        defaulter_dialog.geometry("700x500")

        ttk.Label(defaulter_dialog, text="Defaulter List (Current Month)", style="SubHeader.TLabel").pack(pady=10)

        defaulter_tree = ttk.Treeview(defaulter_dialog, columns=("ID", "Name", "Class", "Contact", "Category"), show="headings")
        defaulter_tree.pack(fill="both", expand=True, padx=10, pady=10)

        for col in defaulter_tree["columns"]:
            defaulter_tree.heading(col, text=col)
            defaulter_tree.column(col, width=100, anchor="center")

        current_month = datetime.now().strftime("%B")
        current_year = datetime.now().strftime("%Y")

        self.cursor.execute("""
            SELECT s.id, s.name, s.class, s.contact, s.category
            FROM students s
            LEFT JOIN fee_collections fc ON s.id = fc.student_id AND fc.month = ? AND fc.year = ?
            WHERE fc.id IS NULL
        """, (current_month, current_year))
        defaulters = self.cursor.fetchall()

        for row in defaulters:
            defaulter_tree.insert("", "end", values=row)

        ttk.Button(defaulter_dialog, text="Send Reminder (Simulated)", command=lambda: messagebox.showinfo("Reminder", "Simulating SMS/WhatsApp reminders to defaulters...")).pack(pady=10)

    def _setup_salary_tab(self):
        salary_manage_frame = ttk.LabelFrame(self.salary_tab, text="Manage Staff Salaries", style="TLabelframe")
        salary_manage_frame.pack(fill="x", pady=10, padx=10)
        salary_manage_frame.columnconfigure(1, weight=1)
        salary_manage_frame.columnconfigure(3, weight=1)

        ttk.Label(salary_manage_frame, text="Select Staff:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.salary_staff_combo = ttk.Combobox(salary_manage_frame, values=self._get_staff_names_with_id())
        self.salary_staff_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        self.salary_staff_combo.bind("<<ComboboxSelected>>", self._load_staff_salary)

        ttk.Label(salary_manage_frame, text="Basic Salary:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.basic_salary_entry = ttk.Entry(salary_manage_frame)
        self.basic_salary_entry.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Button(salary_manage_frame, text="Update Salary", command=self._update_staff_salary).grid(row=0, column=4, padx=5, pady=2)

        payslip_frame = ttk.LabelFrame(self.salary_tab, text="Generate Payslips", style="TLabelframe")
        payslip_frame.pack(fill="x", pady=10, padx=10)
        payslip_frame.columnconfigure(1, weight=1)
        payslip_frame.columnconfigure(3, weight=1)
        payslip_frame.columnconfigure(5, weight=1)

        ttk.Label(payslip_frame, text="Select Staff:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.payslip_staff_combo = ttk.Combobox(payslip_frame, values=self._get_staff_names_with_id())
        self.payslip_staff_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(payslip_frame, text="Month:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.payslip_month_combo = ttk.Combobox(payslip_frame, values=[datetime.strftime(datetime(2000, i, 1), '%B') for i in range(1, 13)])
        self.payslip_month_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")
        self.payslip_month_combo.set(datetime.now().strftime("%B"))

        ttk.Label(payslip_frame, text="Year:").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.payslip_year_entry = ttk.Entry(payslip_frame)
        self.payslip_year_entry.grid(row=0, column=5, padx=5, pady=2, sticky="ew")
        self.payslip_year_entry.insert(0, datetime.now().year)

        ttk.Button(payslip_frame, text="Generate Payslip", command=self._generate_payslip).grid(row=0, column=6, padx=5, pady=2)

    def _get_staff_names_with_id(self):
        self.cursor.execute("SELECT id, name FROM staff")
        return [f"{row[1]} (ID: {row[0]})" for row in self.cursor.fetchall()]

    def _load_staff_salary(self, event=None):
        selected_staff_str = self.salary_staff_combo.get()
        if not selected_staff_str: return

        staff_id = int(selected_staff_str.split('(ID: ')[1][:-1])
        self.cursor.execute("SELECT salary FROM staff WHERE id=?", (staff_id,))
        salary = self.cursor.fetchone()
        self.basic_salary_entry.delete(0, tk.END)
        if salary:
            self.basic_salary_entry.insert(0, str(salary[0]))

    def _update_staff_salary(self):
        selected_staff_str = self.salary_staff_combo.get()
        new_salary = self.basic_salary_entry.get()

        if not selected_staff_str or not new_salary:
            messagebox.showerror("Input Error", "Please select staff and enter salary.")
            return
        try:
            staff_id = int(selected_staff_str.split('(ID: ')[1][:-1])
            new_salary = float(new_salary)
        except ValueError:
            messagebox.showerror("Input Error", "Salary must be a number.")
            return

        try:
            self.cursor.execute("UPDATE staff SET salary=? WHERE id=?", (new_salary, staff_id))
            self.conn.commit()
            messagebox.showinfo("Success", "Staff salary updated successfully!")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to update salary: {e}")

    def _generate_payslip(self):
        selected_staff_str = self.payslip_staff_combo.get()
        month = self.payslip_month_combo.get()
        year = self.payslip_year_entry.get()

        if not selected_staff_str or not month or not year:
            messagebox.showwarning("Input Error", "Please select staff, month, and year.")
            return

        staff_id = int(selected_staff_str.split('(ID: ')[1][:-1])
        self.cursor.execute("SELECT name, role, salary FROM staff WHERE id=?", (staff_id,))
        staff_data = self.cursor.fetchone()

        if not staff_data:
            messagebox.showerror("Error", "Staff not found.")
            return

        name, role, basic_salary = staff_data
        
        allowances = basic_salary * 0.10
        deductions = basic_salary * 0.05
        net_salary = basic_salary + allowances - deductions

        payslip_dialog = tk.Toplevel(self.root)
        payslip_dialog.title(f"Payslip for {name} - {month} {year}")
        payslip_dialog.transient(self.root)
        payslip_dialog.grab_set()
        payslip_dialog.geometry("500x500")
        payslip_dialog.resizable(False, False)

        ttk.Label(payslip_dialog, text="--- Payslip ---", font=("Arial", 16, "bold")).pack(pady=10)
        ttk.Label(payslip_dialog, text=f"Employee Name: {name}", font=("Arial", 12)).pack(anchor="w", padx=20)
        ttk.Label(payslip_dialog, text=f"Role: {role}", font=("Arial", 12)).pack(anchor="w", padx=20)
        ttk.Label(payslip_dialog, text=f"Month/Year: {month} {year}", font=("Arial", 12)).pack(anchor="w", padx=20)
        ttk.Label(payslip_dialog, text="------------------------------------", font=("Arial", 12)).pack(pady=5)
        ttk.Label(payslip_dialog, text=f"Basic Salary: PKR {basic_salary:,.2f}", font=("Arial", 12)).pack(anchor="w", padx=20)
        ttk.Label(payslip_dialog, text=f"Allowances: PKR {allowances:,.2f}", font=("Arial", 12)).pack(anchor="w", padx=20)
        ttk.Label(payslip_dialog, text=f"Deductions: PKR {deductions:,.2f}", font=("Arial", 12)).pack(anchor="w", padx=20)
        ttk.Label(payslip_dialog, text="------------------------------------", font=("Arial", 12)).pack(pady=5)
        ttk.Label(payslip_dialog, text=f"Net Salary: PKR {net_salary:,.2f}", font=("Arial", 14, "bold")).pack(anchor="w", padx=20)

        ttk.Button(payslip_dialog, text="Print Payslip (Simulated)", command=lambda: messagebox.showinfo("Print", "Printing payslip...")).pack(pady=20)

    def _setup_expense_income_tab(self):
        expense_frame = ttk.LabelFrame(self.expense_income_tab, text="Record Expenses", style="TLabelframe")
        expense_frame.pack(fill="x", pady=10, padx=10)
        expense_frame.columnconfigure(1, weight=1)
        expense_frame.columnconfigure(3, weight=1)
        expense_frame.columnconfigure(5, weight=1)
        expense_frame.columnconfigure(6, weight=1)

        ttk.Label(expense_frame, text="Category:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.expense_category_entry = ttk.Entry(expense_frame)
        self.expense_category_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(expense_frame, text="Amount:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.expense_amount_entry = ttk.Entry(expense_frame)
        self.expense_amount_entry.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Label(expense_frame, text="Date (YYYY-MM-DD):").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.expense_date_entry = ttk.Entry(expense_frame)
        self.expense_date_entry.grid(row=0, column=5, padx=5, pady=2, sticky="ew")
        self.expense_date_entry.insert(0, datetime.now().strftime("%Y-%m-%d"))

        ttk.Label(expense_frame, text="Description:").grid(row=1, column=0, sticky="w", padx=5, pady=2)
        self.expense_description_entry = ttk.Entry(expense_frame)
        self.expense_description_entry.grid(row=1, column=1, columnspan=5, sticky="ew", padx=5, pady=2)

        ttk.Button(expense_frame, text="Add Expense", command=self._add_expense).grid(row=2, column=0, columnspan=6, pady=10)

        self.expense_tree = ttk.Treeview(self.expense_income_tab, columns=("ID", "Category", "Amount", "Date", "Description"), show="headings")
        self.expense_tree.pack(fill="x", pady=10, padx=10)
        for col in self.expense_tree["columns"]:
            self.expense_tree.heading(col, text=col)
            self.expense_tree.column(col, width=100, anchor="center")
        self._load_expenses()

        income_frame = ttk.LabelFrame(self.expense_income_tab, text="Record Income", style="TLabelframe")
        income_frame.pack(fill="x", pady=10, padx=10)
        income_frame.columnconfigure(1, weight=1)
        income_frame.columnconfigure(3, weight=1)
        income_frame.columnconfigure(5, weight=1)
        income_frame.columnconfigure(6, weight=1)

        ttk.Label(income_frame, text="Category:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.income_category_entry = ttk.Entry(income_frame)
        self.income_category_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(income_frame, text="Amount:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.income_amount_entry = ttk.Entry(income_frame)
        self.income_amount_entry.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Label(income_frame, text="Date (YYYY-MM-DD):").grid(row=0, column=4, padx=5, pady=2, sticky="w")
        self.income_date_entry = ttk.Entry(income_frame)
        self.income_date_entry.grid(row=0, column=5, padx=5, pady=2, sticky="ew")
        self.income_date_entry.insert(0, datetime.now().strftime("%Y-%m-%d"))

        ttk.Label(income_frame, text="Description:").grid(row=1, column=0, sticky="w", padx=5, pady=2)
        self.income_description_entry = ttk.Entry(income_frame)
        self.income_description_entry.grid(row=1, column=1, columnspan=5, sticky="ew", padx=5, pady=2)

        ttk.Button(income_frame, text="Add Income", command=self._add_income).grid(row=2, column=0, columnspan=6, pady=10)

        self.income_tree = ttk.Treeview(self.expense_income_tab, columns=("ID", "Category", "Amount", "Date", "Description"), show="headings")
        self.income_tree.pack(fill="x", pady=10, padx=10)
        for col in self.income_tree["columns"]:
            self.income_tree.heading(col, text=col)
            self.income_tree.column(col, width=100, anchor="center")
        self._load_income()

    def _add_expense(self):
        category = self.expense_category_entry.get()
        amount = self.expense_amount_entry.get()
        expense_date = self.expense_date_entry.get()
        description = self.expense_description_entry.get()

        if not all([category, amount, expense_date]):
            messagebox.showerror("Input Error", "Category, Amount, and Date are required for expense.")
            return
        try:
            amount = float(amount)
        except ValueError:
            messagebox.showerror("Input Error", "Amount must be a number.")
            return

        try:
            self.cursor.execute("INSERT INTO expenses (category, amount, expense_date, description) VALUES (?, ?, ?, ?)",
                                (category, amount, expense_date, description))
            self.conn.commit()
            messagebox.showinfo("Success", "Expense recorded successfully!")
            self._load_expenses()
            self.expense_category_entry.delete(0, tk.END)
            self.expense_amount_entry.delete(0, tk.END)
            self.expense_description_entry.delete(0, tk.END)
        except Exception as e:
            messagebox.showerror("Error", f"Failed to record expense: {e}")

    def _load_expenses(self):
        for i in self.expense_tree.get_children():
            self.expense_tree.delete(i)
        self.cursor.execute("SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 50")
        for row in self.cursor.fetchall():
            self.expense_tree.insert("", "end", values=row)

    def _add_income(self):
        category = self.income_category_entry.get()
        amount = self.income_amount_entry.get()
        income_date = self.income_date_entry.get()
        description = self.income_description_entry.get()

        if not all([category, amount, income_date]):
            messagebox.showerror("Input Error", "Category, Amount, and Date are required for income.")
            return
        try:
            amount = float(amount)
        except ValueError:
            messagebox.showerror("Input Error", "Amount must be a number.")
            return

        try:
            self.cursor.execute("INSERT INTO income (category, amount, income_date, description) VALUES (?, ?, ?, ?)",
                                (category, amount, income_date, description))
            self.conn.commit()
            messagebox.showinfo("Success", "Income recorded successfully!")
            self._load_income()
            self.income_category_entry.delete(0, tk.END)
            self.income_amount_entry.delete(0, tk.END)
            self.income_description_entry.delete(0, tk.END)
        except Exception as e:
            messagebox.showerror("Error", f"Failed to record income: {e}")

    def _load_income(self):
        for i in self.income_tree.get_children():
            self.income_tree.delete(i)
        self.cursor.execute("SELECT * FROM income ORDER BY income_date DESC LIMIT 50")
        for row in self.cursor.fetchall():
            self.income_tree.insert("", "end", values=row)

    def show_utilities_reports(self):
        self.switch_frame(self._create_utilities_reports_frame)

    def _create_utilities_reports_frame(self):
        self.current_frame = ttk.Frame(self.main_content, style="TFrame")
        self.current_frame.pack(fill="both", expand=True, padx=20, pady=20)

        ttk.Label(self.current_frame, text="Utilities & Reports", style="Header.TLabel").pack(pady=10)

        notebook = ttk.Notebook(self.current_frame)
        notebook.pack(fill="both", expand=True)

        self.id_cards_certs_tab = ttk.Frame(notebook, style="TFrame")
        self.charts_reports_tab = ttk.Frame(notebook, style="TFrame")
        self.backup_restore_tab = ttk.Frame(notebook, style="TFrame")

        notebook.add(self.id_cards_certs_tab, text="ID Cards & Certificates")
        notebook.add(self.charts_reports_tab, text="Charts & Reports")
        notebook.add(self.backup_restore_tab, text="Backup/Restore")

        self._setup_id_cards_certs_tab()
        self._setup_charts_reports_tab()
        self._setup_backup_restore_tab()

    def _setup_id_cards_certs_tab(self):
        id_card_frame = ttk.LabelFrame(self.id_cards_certs_tab, text="Generate ID Cards", style="TLabelframe")
        id_card_frame.pack(fill="x", pady=10, padx=10)
        id_card_frame.columnconfigure(1, weight=1)
        id_card_frame.columnconfigure(3, weight=1)

        ttk.Label(id_card_frame, text="Select Person Type:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.id_card_type_combo = ttk.Combobox(id_card_frame, values=["Student", "Staff"])
        self.id_card_type_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        self.id_card_type_combo.bind("<<ComboboxSelected>>", self._load_people_for_id_card)

        ttk.Label(id_card_frame, text="Select Person:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.id_card_person_combo = ttk.Combobox(id_card_frame)
        self.id_card_person_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Button(id_card_frame, text="Generate ID Card", command=self._generate_id_card).grid(row=0, column=4, padx=5, pady=2)

        cert_frame = ttk.LabelFrame(self.id_cards_certs_tab, text="Generate Certificates", style="TLabelframe")
        cert_frame.pack(fill="x", pady=10, padx=10)
        cert_frame.columnconfigure(1, weight=1)
        cert_frame.columnconfigure(3, weight=1)

        ttk.Label(cert_frame, text="Select Student:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.cert_student_combo = ttk.Combobox(cert_frame, values=[f"{s[1]} (ID: {s[0]})" for s in self.cursor.execute("SELECT id, name FROM students").fetchall()])
        self.cert_student_combo.grid(row=0, column=1, padx=5, pady=2, sticky="ew")

        ttk.Label(cert_frame, text="Certificate Type:").grid(row=0, column=2, padx=5, pady=2, sticky="w")
        self.cert_type_combo = ttk.Combobox(cert_frame, values=["Promotion Certificate", "Character Certificate", "Completion Certificate"])
        self.cert_type_combo.grid(row=0, column=3, padx=5, pady=2, sticky="ew")

        ttk.Button(cert_frame, text="Generate Certificate", command=self._generate_certificate).grid(row=0, column=4, padx=5, pady=2)

    def _load_people_for_id_card(self, event=None):
        person_type = self.id_card_type_combo.get()
        if person_type == "Student":
            self.cursor.execute("SELECT id, name FROM students")
            people = [f"{row[1]} (ID: {row[0]})" for row in self.cursor.fetchall()]
        elif person_type == "Staff":
            self.cursor.execute("SELECT id, name FROM staff")
            people = [f"{row[1]} (ID: {row[0]})" for row in self.cursor.fetchall()]
        else:
            people = []
        self.id_card_person_combo['values'] = people
        self.id_card_person_combo.set('')

    def _generate_id_card(self):
        person_type = self.id_card_type_combo.get()
        selected_person_str = self.id_card_person_combo.get()

        if not person_type or not selected_person_str:
            messagebox.showwarning("Input Error", "Please select person type and a person.")
            return

        person_id = int(selected_person_str.split('(ID: ')[1][:-1])

        if person_type == "Student":
            self.cursor.execute("SELECT name, father_name, class, contact, photo_path FROM students WHERE id=?", (person_id,))
            data = self.cursor.fetchone()
            if not data:
                messagebox.showerror("Error", "Student not found.")
                return
            name, father_name, s_class, contact, photo_path = data
            card_details = {
                "Type": "Student ID Card",
                "Name": name,
                "Father Name": father_name,
                "Class": s_class,
                "Contact": contact,
                "ID": person_id
            }
        elif person_type == "Staff":
            self.cursor.execute("SELECT name, role, contact, joining_date, photo_path FROM staff WHERE id=?", (person_id,))
            data = self.cursor.fetchone()
            if not data:
                messagebox.showerror("Error", "Staff not found.")
                return
            name, role, contact, joining_date, photo_path = data
            card_details = {
                "Type": "Staff ID Card",
                "Name": name,
                "Role": role,
                "Contact": contact,
                "Joining Date": joining_date,
                "ID": person_id
            }
        else:
            return

        id_card_dialog = tk.Toplevel(self.root)
        id_card_dialog.title(f"{person_type} ID Card")
        id_card_dialog.transient(self.root)
        id_card_dialog.grab_set()
        id_card_dialog.geometry("400x550")
        id_card_dialog.resizable(False, False)

        card_frame = ttk.Frame(id_card_dialog, style="TFrame", padding=20, relief="solid", borderwidth=2)
        card_frame.pack(fill="both", expand=True, padx=10, pady=10)

        ttk.Label(card_frame, text="School/Madrasa Name", font=("Arial", 16, "bold")).pack(pady=5)
        ttk.Label(card_frame, text=card_details["Type"], font=("Arial", 14, "bold")).pack(pady=5)

        photo_label = ttk.Label(card_frame, text="Photo", relief="groove", anchor="center")
        photo_label.pack(pady=10)
        self._display_photo_preview(photo_path, photo_label, size=(120, 120))

        for key, value in card_details.items():
            if key not in ["Type", "ID"]:
                ttk.Label(card_frame, text=f"{key}: {value}", font=("Arial", 10)).pack(anchor="w", padx=10)

        qr_data = f"{person_type} ID: {card_details['ID']}\nName: {card_details['Name']}"
        qr_img = qrcode.make(qr_data)
        qr_img = qr_img.resize((100, 100), Image.LANCZOS)
        qr_tk = ImageTk.PhotoImage(qr_img)
        qr_label = ttk.Label(card_frame, image=qr_tk)
        qr_label.image = qr_tk
        qr_label.pack(pady=10)

        ttk.Label(card_frame, text="Signature", font=("Arial", 10, "italic")).pack(anchor="e", padx=20, pady=10)
        ttk.Button(id_card_dialog, text="Print (Simulated)", command=lambda: messagebox.showinfo("Print", "Printing ID Card...")).pack(pady=10)

    def _generate_certificate(self):
        selected_student_str = self.cert_student_combo.get()
        cert_type = self.cert_type_combo.get()

        if not selected_student_str or not cert_type:
            messagebox.showwarning("Input Error", "Please select a student and certificate type.")
            return

        student_id = int(selected_student_str.split('(ID: ')[1][:-1])
        self.cursor.execute("SELECT name, father_name, class FROM students WHERE id=?", (student_id,))
        student_data = self.cursor.fetchone()

        if not student_data:
            messagebox.showerror("Error", "Student not found.")
            return
        name, father_name, s_class = student_data

        certificate_dialog = tk.Toplevel(self.root)
        certificate_dialog.title(f"{cert_type}")
        certificate_dialog.transient(self.root)
        certificate_dialog.grab_set()
        certificate_dialog.geometry("600x500")
        certificate_dialog.resizable(False, False)

        cert_frame = ttk.Frame(certificate_dialog, style="TFrame", padding=30, relief="solid", borderwidth=2)
        cert_frame.pack(fill="both", expand=True, padx=20, pady=20)

        ttk.Label(cert_frame, text="School/Madrasa Name", font=("Arial", 20, "bold")).pack(pady=10)
        ttk.Label(cert_frame, text=cert_type, font=("Arial", 18, "underline")).pack(pady=10)

        if cert_type == "Promotion Certificate":
            text = f"This is to certify that {name}, son/daughter of {father_name}, a student of Class {s_class}, has been successfully promoted to the next class for the academic session {datetime.now().year}-{datetime.now().year+1}."
        elif cert_type == "Character Certificate":
            text = f"This is to certify that {name}, son/daughter of {father_name}, a student of Class {s_class}, has been a student of this institution from [Start Date] to [End Date]. During this period, his/her conduct and character have been found to be excellent."
        elif cert_type == "Completion Certificate":
            text = f"This is to certify that {name}, son/daughter of {father_name}, has successfully completed his/her studies in Class {s_class} at this institution on {datetime.now().strftime('%Y-%m-%d')}."
        else:
            text = "Certificate content not defined."

        ttk.Label(cert_frame, text=text, font=("Arial", 12), wraplength=500, justify="center").pack(pady=20)
        ttk.Label(cert_frame, text=f"Date: {datetime.now().strftime('%Y-%m-%d')}", font=("Arial", 10)).pack(anchor="w", padx=20)
        ttk.Label(cert_frame, text="\n\nPrincipal's Signature", font=("Arial", 12, "bold")).pack(anchor="e", padx=20)

        ttk.Button(certificate_dialog, text="Print (Simulated)", command=lambda: messagebox.showinfo("Print", "Printing Certificate...")).pack(pady=10)

    def _setup_charts_reports_tab(self):
        ttk.Label(self.charts_reports_tab, text="Charts & Reports (Placeholder)", style="SubHeader.TLabel").pack(pady=20)
        ttk.Label(self.charts_reports_tab, text="Integration with Matplotlib or other charting libraries would go here.", style="TLabel").pack()
        ttk.Label(self.charts_reports_tab, text="Example: Fee Collection Trends, Attendance Summary Charts, Exam Performance Graphs.", style="TLabel").pack()

    def _setup_backup_restore_tab(self):
        backup_frame = ttk.LabelFrame(self.backup_restore_tab, text="Database Backup", style="TLabelframe")
        backup_frame.pack(fill="x", pady=10, padx=10)
        backup_frame.columnconfigure(1, weight=1)

        ttk.Label(backup_frame, text="Backup Location:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.backup_path_entry = ttk.Entry(backup_frame, width=50)
        self.backup_path_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        ttk.Button(backup_frame, text="Browse", command=self._browse_backup_location).grid(row=0, column=2, padx=5, pady=2)
        ttk.Button(backup_frame, text="Create Backup", command=self._create_backup).grid(row=0, column=3, padx=5, pady=2)

        restore_frame = ttk.LabelFrame(self.backup_restore_tab, text="Database Restore", style="TLabelframe")
        restore_frame.pack(fill="x", pady=10, padx=10)
        restore_frame.columnconfigure(1, weight=1)

        ttk.Label(restore_frame, text="Restore File:").grid(row=0, column=0, padx=5, pady=2, sticky="w")
        self.restore_path_entry = ttk.Entry(restore_frame, width=50)
        self.restore_path_entry.grid(row=0, column=1, padx=5, pady=2, sticky="ew")
        ttk.Button(restore_frame, text="Browse", command=self._browse_restore_file).grid(row=0, column=2, padx=5, pady=2)
        ttk.Button(restore_frame, text="Restore Database", command=self._restore_database).grid(row=0, column=3, padx=5, pady=2)
        ttk.Label(restore_frame, text="Warning: Restoring will overwrite current data!", foreground="red").grid(row=1, column=0, columnspan=4, pady=5, sticky="w")

    def _browse_backup_location(self):
        folder_path = filedialog.askdirectory()
        if folder_path:
            self.backup_path_entry.delete(0, tk.END)
            self.backup_path_entry.insert(0, folder_path)

    def _create_backup(self):
        backup_dir = self.backup_path_entry.get()
        if not backup_dir:
            messagebox.showerror("Error", "Please select a backup directory.")
            return

        backup_filename = f"school_madrasa_backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}.db"
        backup_path = os.path.join(backup_dir, backup_filename)

        try:
            self.conn.close()
            shutil.copyfile(self.db_name, backup_path)
            messagebox.showinfo("Backup Success", f"Database backed up to:\n{backup_path}")
        except Exception as e:
            messagebox.showerror("Backup Error", f"Failed to create backup: {e}")
        finally:
            self.connect_db()

    def _browse_restore_file(self):
        file_path = filedialog.askopenfilename(filetypes=[("SQLite Database", "*.db"), ("All Files", "*.*")])
        if file_path:
            self.restore_path_entry.delete(0, tk.END)
            self.restore_path_entry.insert(0, file_path)

    def _restore_database(self):
        restore_file = self.restore_path_entry.get()
        if not restore_file:
            messagebox.showerror("Error", "Please select a backup file to restore.")
            return

        if not messagebox.askyesno("Confirm Restore", "WARNING: This will overwrite your current database with the selected backup. All unsaved data will be lost. Are you sure?"):
            return

        try:
            self.conn.close()
            shutil.copyfile(restore_file, self.db_name)
            messagebox.showinfo("Restore Success", "Database restored successfully! Please restart the application for changes to take full effect.")
            self.root.destroy()
        except Exception as e:
            messagebox.showerror("Restore Error", f"Failed to restore database: {e}")
        finally:
            self.connect_db()

    def show_settings(self):
        self.switch_frame(self._create_settings_frame)

    def _create_settings_frame(self):
        self.current_frame = ttk.Frame(self.main_content, style="TFrame")
        self.current_frame.pack(fill="both", expand=True, padx=20, pady=20)

        ttk.Label(self.current_frame, text="System Settings", style="Header.TLabel").pack(pady=10)

        settings_frame = ttk.LabelFrame(self.current_frame, text="General Settings", style="TLabelframe")
        settings_frame.pack(fill="x", pady=10, padx=10)
        settings_frame.columnconfigure(1, weight=1)

        ttk.Label(settings_frame, text="School/Madrasa Name:").grid(row=0, column=0, sticky="w", padx=5, pady=2)
        self.school_name_entry = ttk.Entry(settings_frame)
        self.school_name_entry.grid(row=0, column=1, sticky="ew", padx=5, pady=2)

        ttk.Label(settings_frame, text="Current Session:").grid(row=1, column=0, sticky="w", padx=5, pady=2)
        self.current_session_entry = ttk.Entry(settings_frame)
        self.current_session_entry.grid(row=1, column=1, sticky="ew", padx=5, pady=2)

        ttk.Label(settings_frame, text="Logo Path:").grid(row=2, column=0, sticky="w", padx=5, pady=2)
        self.logo_path_entry = ttk.Entry(settings_frame)
        self.logo_path_entry.grid(row=2, column=1, sticky="ew", padx=5, pady=2)
        ttk.Button(settings_frame, text="Browse", command=self._browse_logo).grid(row=2, column=2, padx=5, pady=2)

        ttk.Button(settings_frame, text="Save Settings", command=self._save_settings).grid(row=3, column=0, columnspan=3, pady=10)

        self._load_settings()

        user_frame = ttk.LabelFrame(self.current_frame, text="User Management (Simulated)", style="TLabelframe")
        user_frame.pack(fill="x", pady=10, padx=10)
        user_frame.columnconfigure(1, weight=1)

        ttk.Label(user_frame, text="Current User Role:").grid(row=0, column=0, sticky="w", padx=5, pady=2)
        self.current_role_label = ttk.Label(user_frame, text=self.current_user_role, foreground="blue")
        self.current_role_label.grid(row=0, column=1, sticky="w", padx=5, pady=2)

        ttk.Label(user_frame, text="Change Role To:").grid(row=1, column=0, sticky="w", padx=5, pady=2)
        self.change_role_combo = ttk.Combobox(user_frame, values=["Admin", "Teacher", "Accountant"])
        self.change_role_combo.grid(row=1, column=1, sticky="ew", padx=5, pady=2)
        ttk.Button(user_frame, text="Apply Role", command=self._apply_user_role).grid(row=1, column=2, padx=5, pady=2)
        ttk.Label(user_frame, text="Note: This is a simulation. Full user management with login/password would be more complex.", foreground="gray", font=("Arial", 8, "italic")).grid(row=2, column=0, columnspan=3, sticky="w", padx=5, pady=2)

        lang_frame = ttk.LabelFrame(self.current_frame, text="Language Settings", style="TLabelframe")
        lang_frame.pack(fill="x", pady=10, padx=10)
        lang_frame.columnconfigure(1, weight=1)

        ttk.Label(lang_frame, text="Select Language:").grid(row=0, column=0, sticky="w", padx=5, pady=2)
        self.language_combo = ttk.Combobox(lang_frame, values=["English", "Urdu"])
        self.language_combo.grid(row=0, column=1, sticky="ew", padx=5, pady=2)
        self.language_combo.set(self.language)
        ttk.Button(lang_frame, text="Apply Language", command=self._apply_language).grid(row=0, column=2, padx=5, pady=2)
        ttk.Label(lang_frame, text="Note: Full multi-language support requires extensive text mapping.", foreground="gray", font=("Arial", 8, "italic")).grid(row=1, column=0, columnspan=3, sticky="w", padx=5, pady=2)

    def _load_settings(self):
        self.cursor.execute("SELECT school_name, logo_path, current_session FROM settings WHERE id=1")
        settings = self.cursor.fetchone()
        if settings:
            self.school_name_entry.insert(0, settings[0] or "")
            self.logo_path_entry.insert(0, settings[1] or "")
            self.current_session_entry.insert(0, settings[2] or "")
        else:
            self.cursor.execute("INSERT INTO settings (id, school_name, logo_path, current_session) VALUES (1, ?, ?, ?)",
                                ("My School/Madrasa", "", f"{datetime.now().year}-{datetime.now().year+1}"))
            self.conn.commit()
            self.school_name_entry.insert(0, "My School/Madrasa")
            self.current_session_entry.insert(0, f"{datetime.now().year}-{datetime.now().year+1}")

    def _browse_logo(self):
        file_path = filedialog.askopenfilename(filetypes=[("Image Files", "*.png *.jpg *.jpeg *.gif")])
        if file_path:
            self.logo_path_entry.delete(0, tk.END)
            self.logo_path_entry.insert(0, file_path)

    def _save_settings(self):
        school_name = self.school_name_entry.get()
        logo_path = self.logo_path_entry.get()
        current_session = self.current_session_entry.get()

        try:
            self.cursor.execute("UPDATE settings SET school_name=?, logo_path=?, current_session=? WHERE id=1",
                                (school_name, logo_path, current_session))
            self.conn.commit()
            messagebox.showinfo("Success", "Settings saved successfully!")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to save settings: {e}")

    def _apply_user_role(self):
        new_role = self.change_role_combo.get()
        if new_role and new_role in ["Admin", "Teacher", "Accountant"]:
            self.current_user_role = new_role
            self.current_role_label.config(text=self.current_user_role)
            self.update_sidebar_visibility()
            messagebox.showinfo("Role Changed", f"User role changed to {new_role}. Sidebar updated.")
        else:
            messagebox.showwarning("Invalid Role", "Please select a valid role.")

    def _apply_language(self):
        new_lang = self.language_combo.get()
        if new_lang in ["English", "Urdu"]:
            self.language = new_lang
            messagebox.showinfo("Language Changed", f"Language set to {new_lang}. (Note: Full UI translation requires re-initializing widgets with translated texts.)")

    def on_closing(self):
        if self.conn:
            self.conn.close()
        self.root.destroy()

if __name__ == "__main__":
    root = tk.Tk()
    app = SchoolMadrasaApp(root)
    root.protocol("WM_DELETE_WINDOW", app.on_closing)
    root.mainloop()