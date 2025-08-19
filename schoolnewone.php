<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'school_management_db');

try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Database Schema and Sample Data (moved outside PHP for clarity, assumes this is run manually once)
/*
CREATE DATABASE IF NOT EXISTS school_management_db;

USE school_management_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    name VARCHAR(100) NOT NULL,
    subject_specialty VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    teacher_id INT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS class_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE(class_id, subject_id)
);

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    name VARCHAR(100) NOT NULL,
    class_id INT,
    roll_no VARCHAR(20) UNIQUE,
    dob DATE,
    address VARCHAR(255),
    phone VARCHAR(20),
    parent_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late') NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    UNIQUE(student_id, class_id, subject_id, attendance_date)
);

CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_date DATE NOT NULL,
    max_marks INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained INT,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE(exam_id, student_id)
);

CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE,
    status ENUM('Paid', 'Unpaid', 'Partially Paid') NOT NULL,
    description VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    published_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    previous_school VARCHAR(255),
    applying_for_class VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending'
);

CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('New', 'Replied') DEFAULT 'New'
);

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE(assignment_id, student_id)
);


-- Dummy Data Insertion
INSERT IGNORE INTO users (username, password, role, email) VALUES
('admin', '$2y$10$wKzN1eJ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'admin', 'admin@example.com'), -- password is 'adminpass'
('teacher1', '$2y$10$wKzN1eJ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'teacher', 'teacher1@example.com'), -- password is 'teacherpass'
('student1', '$2y$10$wKzN1eJ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'student', 'studentpass'), -- password is 'studentpass'
('parent1', '$2y$10$wKzN1eJ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'parent', 'parent1@example.com'), -- password is 'parentpass'
('student2', '$2y$10$wKzN1eJ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'student', 'student2@example.com'), -- password is 'studentpass'
('parent2', '$2y$10$wKzN1eJ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'parent', 'parent2@example.com'); -- password is 'parentpass'


INSERT IGNORE INTO teachers (user_id, name, subject_specialty, phone, email) VALUES
((SELECT id FROM users WHERE username = 'teacher1'), 'Mr. Ali Ahmed', 'Mathematics', '03001234567', 'teacher1@example.com');

INSERT IGNORE INTO classes (name, teacher_id) VALUES
('Class 10A', (SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed')),
('Class 9B', (SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'));

INSERT IGNORE INTO subjects (name) VALUES
('Mathematics'), ('Physics'), ('Chemistry'), ('Biology'), ('English'), ('Urdu'), ('Computer Science');

INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES
((SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Mathematics')),
((SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Physics')),
((SELECT id FROM classes WHERE name = 'Class 9B'), (SELECT id FROM subjects WHERE name = 'English')),
((SELECT id FROM classes WHERE name = 'Class 9B'), (SELECT id FROM subjects WHERE name = 'Urdu'));

INSERT IGNORE INTO students (user_id, name, class_id, roll_no, dob, address, phone, parent_id) VALUES
((SELECT id FROM users WHERE username = 'student1'), 'Fatima Khan', (SELECT id FROM classes WHERE name = 'Class 10A'), '10A-001', '2008-05-15', 'House 1, Street 2, Lahore', '03219876543', (SELECT id FROM users WHERE username = 'parent1')),
((SELECT id FROM users WHERE username = 'student2'), 'Ahmed Raza', (SELECT id FROM classes WHERE name = 'Class 10A'), '10A-002', '2008-06-20', 'Flat 5, Block B, Islamabad', '03331234567', (SELECT id FROM users WHERE username = 'parent1'));

INSERT IGNORE INTO timetables (class_id, subject_id, teacher_id, day_of_week, start_time, end_time) VALUES
((SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Mathematics'), (SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), 'Monday', '09:00:00', '10:00:00'),
((SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Physics'), (SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), 'Tuesday', '10:00:00', '11:00:00'),
((SELECT id FROM classes WHERE name = 'Class 9B'), (SELECT id FROM subjects WHERE name = 'English'), (SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), 'Wednesday', '11:00:00', '12:00:00');

INSERT IGNORE INTO announcements (title, content, published_date) VALUES
('New School Year Begins', 'Welcome back students! Classes for the new academic year will commence on September 1, 2023.', '2023-08-25 10:00:00'),
('Admissions Open for 2024', 'Admissions for the academic year 2024 are now open. Visit our website for more details.', '2023-09-01 09:30:00'),
('Summer Break Announced', 'School will remain closed for summer break from June 15 to August 15.', '2024-06-01 12:00:00'),
('Annual Science Fair', 'Our annual science fair will be held on December 5, 2023. Students are encouraged to participate.', '2023-11-10 15:00:00');

INSERT IGNORE INTO events (title, description, event_date, event_time, location) VALUES
('Annual Sports Day', 'Join us for a day of exciting sports activities and competitions.', '2023-11-10', '09:00:00', 'School Ground'),
('Parent-Teacher Meeting', 'Discuss student progress with teachers. All parents are encouraged to attend.', '2023-10-20', '14:00:00', 'School Auditorium'),
('Debate Competition', 'Inter-house debate competition for senior students.', '2023-12-01', '10:00:00', 'School Hall'),
('Art Exhibition', 'Showcasing student artworks from various classes.', '2024-01-25', '11:00:00', 'Art Room');

INSERT IGNORE INTO fees (student_id, amount, due_date, status, description) VALUES
((SELECT id FROM students WHERE name = 'Fatima Khan'), 5000.00, '2023-10-01', 'Unpaid', 'Tuition Fee - October'),
((SELECT id FROM students WHERE name = 'Ahmed Raza'), 5000.00, '2023-10-01', 'Paid', 'Tuition Fee - October');

INSERT IGNORE INTO exams (name, class_id, subject_id, exam_date, max_marks) VALUES
('Mid-Term Exam', (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Mathematics'), '2023-11-15', 100),
('Mid-Term Exam', (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Physics'), '2023-11-16', 100);

INSERT IGNORE INTO marks (exam_id, student_id, marks_obtained) VALUES
((SELECT id FROM exams WHERE name = 'Mid-Term Exam' AND class_id = (SELECT id FROM classes WHERE name = 'Class 10A') AND subject_id = (SELECT id FROM subjects WHERE name = 'Mathematics')), (SELECT id FROM students WHERE name = 'Fatima Khan'), 85),
((SELECT id FROM exams WHERE name = 'Mid-Term Exam' AND class_id = (SELECT id FROM classes WHERE name = 'Class 10A') AND subject_id = (SELECT id FROM subjects WHERE name = 'Mathematics')), (SELECT id FROM students WHERE name = 'Ahmed Raza'), 78);


INSERT IGNORE INTO assignments (teacher_id, class_id, subject_id, title, description, due_date) VALUES
((SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Mathematics'), 'Algebra Homework', 'Complete exercises on quadratic equations.', '2023-10-25'),
((SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Physics'), 'Newton Laws Worksheet', 'Solve problems related to Newton\'s Laws of Motion.', '2023-11-01');

INSERT IGNORE INTO gallery (title, image_path) VALUES
('Annual Sports Day 2022', 'uploads/gallery/sports_day_2022.jpg'),
('Science Fair 2023', 'uploads/gallery/science_fair_2023.jpg'),
('Graduation Ceremony 2023', 'uploads/gallery/graduation_2023.jpg'),
('Art Competition Winners', 'uploads/gallery/art_winners.jpg');

INSERT IGNORE INTO admissions (student_name, father_name, dob, gender, previous_school, applying_for_class, address, phone, email, status) VALUES
('Sara Imran', 'Imran Khan', '2010-03-20', 'Female', 'Green Valley School', 'Class 7', 'Street 1, Phase 5, DHA, Karachi', '03451122334', 'sara.imran@example.com', 'Pending'),
('Usman Tariq', 'Tariq Mehmood', '2009-08-10', 'Male', NULL, 'Class 8', 'House 4, Lane 7, Gulberg, Lahore', '03019876543', 'usman.tariq@example.com', 'Approved');

INSERT IGNORE INTO contacts (name, email, subject, message, status) VALUES
('Zainab Bibi', 'zainab.bibi@example.com', 'Inquiry about Admissions', 'I would like to know more about the admission process for my child in Class 5.', 'New'),
('Haris Khan', 'haris.khan@example.com', 'Complaint about class timings', 'The current class timings are clashing with my child\'s extracurricular activities.', 'Replied');
*/

// Function to send email (placeholder, real implementation would use PHPMailer or similar)
function send_email($to, $subject, $message)
{
    // For local development, we'll log emails to a file.
    // In a production environment, integrate with a real SMTP service (e.g., Mailgun, SendGrid, or PHPMailer).
    $log_path = 'emails.log';
    file_put_contents($log_path, "To: $to\nSubject: $subject\nMessage: $message\n\n", FILE_APPEND);
    return true;
}

// Helper functions for role-based access control
function isAdmin()
{
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['role'] === 'admin';
}

function isTeacher()
{
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['role'] === 'teacher';
}

function isStudent()
{
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['role'] === 'student';
}

function isParent()
{
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['role'] === 'parent';
}

function isAuthenticated()
{
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

// Password hashing
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

// Ensure upload directories exist
if (!is_dir('uploads/gallery')) {
    mkdir('uploads/gallery', 0777, true);
}
if (!is_dir('uploads/assignments')) {
    mkdir('uploads/assignments', 0777, true);
}
if (!is_dir('uploads/student_assignments')) {
    mkdir('uploads/student_assignments', 0777, true);
}
if (!is_dir('uploads/announcements')) {
    mkdir('uploads/announcements', 0777, true);
}
// Localization phrases
$lang = isset($_GET['lang']) && $_GET['lang'] == 'ur' ? 'ur' : 'en';

$phrases = [
    'en' => [
        'school_name' => 'Future Leaders School',
        'home' => 'Home',
        'news' => 'News & Announcements',
        'events' => 'Events',
        'admissions' => 'Admissions',
        'contact' => 'Contact Us',
        'gallery' => 'Gallery',
        'login' => 'Login',
        'logout' => 'Logout',
        'dashboard' => 'Dashboard',
        'admin_panel' => 'Admin Panel',
        'teacher_panel' => 'Teacher Panel',
        'student_panel' => 'Student Panel',
        'parent_panel' => 'Parent Panel',
        'our_mission' => 'Our Mission',
        'mission_text' => 'To provide a nurturing and challenging learning environment that empowers students to achieve their full potential and become responsible citizens.',
        'our_vision' => 'Our Vision',
        'vision_text' => 'To be a leading educational institution recognized for academic excellence, innovation, and holistic development.',
        'latest_news' => 'Latest News & Announcements',
        'upcoming_events' => 'Upcoming Events',
        'apply_now' => 'Apply Now',
        'get_in_touch' => 'Get in Touch',
        'full_name' => 'Full Name',
        'email_address' => 'Email Address',
        'subject' => 'Subject',
        'message' => 'Message',
        'send_message' => 'Send Message',
        'admission_form' => 'Admission Form',
        'student_name' => 'Student Name',
        'father_name' => 'Father\'s Name',
        'date_of_birth' => 'Date of Birth',
        'gender' => 'Gender',
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'previous_school' => 'Previous School (if any)',
        'applying_for_class' => 'Applying for Class',
        'address' => 'Address',
        'phone_number' => 'Phone Number',
        'submit_application' => 'Submit Application',
        'login_to_dashboard' => 'Login to Dashboard',
        'username' => 'Username',
        'password' => 'Password',
        'access_dashboard' => 'Access Dashboard',
        'manage_students' => 'Manage Students',
        'manage_teachers' => 'Manage Teachers',
        'manage_classes_subjects' => 'Manage Classes & Subjects',
        'manage_timetables' => 'Manage Timetables',
        'manage_attendance' => 'Manage Attendance',
        'manage_exams_marks' => 'Manage Exams & Marks',
        'manage_fees' => 'Manage Fees',
        'reports' => 'Reports',
        'user_management' => 'User Management',
        'add_new_student' => 'Add New Student',
        'student_list' => 'Student List',
        'edit_student' => 'Edit Student',
        'delete_student' => 'Delete Student',
        'add_new_teacher' => 'Add New Teacher',
        'teacher_list' => 'Teacher List',
        'edit_teacher' => 'Edit Teacher',
        'delete_teacher' => 'Delete Teacher',
        'class_list' => 'Class List',
        'add_new_class' => 'Add New Class',
        'edit_class' => 'Edit Class',
        'delete_class' => 'Delete Class',
        'subject_list' => 'Subject List',
        'add_new_subject' => 'Add New Subject',
        'edit_subject' => 'Edit Subject',
        'delete_subject' => 'Delete Subject',
        'attendance_register' => 'Attendance Register',
        'take_attendance' => 'Take Attendance',
        'view_attendance' => 'View Attendance',
        'exam_list' => 'Exam List',
        'add_new_exam' => 'Add New Exam',
        'enter_marks' => 'Enter Marks',
        'view_marks' => 'View Marks',
        'fee_invoices' => 'Fee Invoices',
        'add_new_invoice' => 'Add New Invoice',
        'view_fee_status' => 'View Fee Status',
        'mark_as_paid' => 'Mark as Paid',
        'attendance_report' => 'Attendance Report',
        'marks_report' => 'Marks Report',
        'fee_report' => 'Fee Report',
        'create_user_account' => 'Create User Account',
        'role' => 'Role',
        'confirm_password' => 'Confirm Password',
        'take_class_attendance' => 'Take Class Attendance',
        'upload_assignments' => 'Upload Assignments & Study Material',
        'enter_exam_marks' => 'Enter Exam Marks',
        'view_timetable' => 'View Timetable',
        'download_assignments' => 'Download Assignments',
        'view_performance' => 'View Performance',
        'view_student_fees' => 'View Student Fees',
        'select_class' => 'Select Class',
        'select_subject' => 'Select Subject',
        'select_date' => 'Select Date',
        'status' => 'Status',
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'save_attendance' => 'Save Attendance',
        'upload_file' => 'Upload File',
        'choose_file' => 'Choose File',
        'add_assignment' => 'Add Assignment',
        'title' => 'Title',
        'description' => 'Description',
        'due_date' => 'Due Date',
        'upload_assignment' => 'Upload Assignment',
        'select_exam' => 'Select Exam',
        'marks_obtained' => 'Marks Obtained',
        'max_marks' => 'Max Marks',
        'save_marks' => 'Save Marks',
        'roll_no' => 'Roll No.',
        'class_name' => 'Class Name',
        'view_details' => 'View Details',
        'add_student' => 'Add Student',
        'edit_student_details' => 'Edit Student Details',
        'save_changes' => 'Save Changes',
        'add_teacher' => 'Add Teacher',
        'edit_teacher_details' => 'Edit Teacher Details',
        'subject_specialty' => 'Subject Specialty',
        'add_class' => 'Add Class',
        'edit_class_details' => 'Edit Class Details',
        'assigned_teacher' => 'Assigned Teacher',
        'add_subject' => 'Add Subject',
        'edit_subject_details' => 'Edit Subject Details',
        'add_timetable_entry' => 'Add Timetable Entry',
        'day_of_week' => 'Day of Week',
        'start_time' => 'Start Time',
        'end_time' => 'End Time',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
        'add_exam' => 'Add Exam',
        'exam_name' => 'Exam Name',
        'exam_date' => 'Exam Date',
        'add_invoice' => 'Add Invoice',
        'amount' => 'Amount',
        'due_date_invoice' => 'Due Date',
        'paid_date' => 'Paid Date',
        'invoice_status' => 'Status',
        'generate_report' => 'Generate Report',
        'select_report_type' => 'Select Report Type',
        'export_csv' => 'Export CSV',
        'export_pdf' => 'Export PDF',
        'no_records' => 'No records found.',
        'gallery_images' => 'Gallery Images',
        'upload_image' => 'Upload Image',
        'add_new_image' => 'Add New Image',
        'image_title' => 'Image Title',
        'image' => 'Image',
        'add_image' => 'Add Image',
        'backup_restore' => 'Backup & Restore',
        'export_data' => 'Export Data',
        'import_data' => 'Import Data',
        'choose_json_file' => 'Choose JSON File',
        'upload_json_file' => 'Upload JSON File',
        'news_announcements_heading' => 'News & Announcements',
        'events_calendar_heading' => 'Events Calendar',
        'application_submitted' => 'Your application has been submitted successfully!',
        'message_sent' => 'Your message has been sent successfully!',
        'invalid_credentials' => 'Invalid username or password.',
        'not_authorized' => 'You are not authorized to access this page.',
        'data_exported_success' => 'Data exported successfully!',
        'data_imported_success' => 'Data imported successfully!',
        'data_import_fail' => 'Error importing data. Please check the file format.',
        'add_news' => 'Add News/Announcement',
        'edit_news' => 'Edit News/Announcement',
        'delete_news' => 'Delete News/Announcement',
        'add_event' => 'Add Event',
        'edit_event' => 'Edit Event',
        'delete_event' => 'Delete Event',
        'published_date' => 'Published Date',
        'event_date' => 'Event Date',
        'event_time' => 'Event Time',
        'location' => 'Location',
        'actions' => 'Actions',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'mark_replied' => 'Mark as Replied',
        'select_student' => 'Select Student',
        'upload_submission' => 'Upload Submission',
        'submit_assignment' => 'Submit Assignment',
        'submission_file' => 'Submission File',
        'update_status' => 'Update Status',
        'all_students' => 'All Students',
        'select_student_first' => 'Select a student first',
        'no_student_linked_parent' => 'No students are currently linked to your account. Please contact school administration.',
        'selected_student_not_found' => 'Selected student not found or not linked to your account.',
        'attendance_saved' => 'Attendance saved successfully!',
        'assignment_submitted' => 'Assignment submitted successfully!',
        'admission_status_updated' => 'Admission status updated successfully!',
        'contact_status_updated' => 'Contact message status updated successfully!',
        'password_reset_link_sent' => 'If an account with that email exists, a password reset link has been sent.',
        'password_reset' => 'Password Reset',
        'new_password' => 'New Password',
        'reset_password' => 'Reset Password',
        'invalid_reset_link' => 'Invalid or expired password reset link.',
        'password_updated_success' => 'Your password has been updated successfully. Please log in.',
        'password_mismatch' => 'New password and confirm password do not match.',
    ],
    'ur' => [
        'school_name' => 'فیوچر لیڈرز سکول',
        'home' => 'ہوم',
        'news' => 'خبریں اور اعلانات',
        'events' => 'تقریبات',
        'admissions' => 'داخلے',
        'contact' => 'ہم سے رابطہ کریں',
        'gallery' => 'گیلری',
        'login' => 'لاگ ان',
        'logout' => 'لاگ آؤٹ',
        'dashboard' => 'ڈیش بورڈ',
        'admin_panel' => 'ایڈمن پینل',
        'teacher_panel' => 'استاد پینل',
        'student_panel' => 'طالب علم پینل',
        'parent_panel' => 'والدین کا پینل',
        'our_mission' => 'ہمارا مشن',
        'mission_text' => 'ایسا پرورش اور چیلنجنگ سیکھنے کا ماحول فراہم کرنا جو طلباء کو اپنی پوری صلاحیت کو حاصل کرنے اور ذمہ دار شہری بننے کے لیے بااختیار بنائے۔',
        'our_vision' => 'ہمارا وژن',
        'vision_text' => 'تعلیمی عمدگی، جدت اور جامع ترقی کے لیے تسلیم شدہ ایک سرکردہ تعلیمی ادارہ بننا۔',
        'latest_news' => 'تازہ ترین خبریں اور اعلانات',
        'upcoming_events' => 'آنے والے واقعات',
        'apply_now' => 'ابھی اپلائی کریں',
        'get_in_touch' => 'رابطہ میں رہیں',
        'full_name' => 'پورا نام',
        'email_address' => 'ای میل ایڈریس',
        'subject' => 'موضوع',
        'message' => 'پیغام',
        'send_message' => 'پیغام بھیجیں',
        'admission_form' => 'داخلہ فارم',
        'student_name' => 'طالب علم کا نام',
        'father_name' => 'والد کا نام',
        'date_of_birth' => 'تاریخ پیدائش',
        'gender' => 'جنس',
        'male' => 'مرد',
        'female' => 'عورت',
        'other' => 'دیگر',
        'previous_school' => 'پچھلا سکول (اگر کوئی ہے)',
        'applying_for_class' => 'کس کلاس کے لیے درخواست دے رہے ہیں',
        'address' => 'پتہ',
        'phone_number' => 'فون نمبر',
        'submit_application' => 'درخواست جمع کروائیں',
        'login_to_dashboard' => 'ڈیش بورڈ میں لاگ ان کریں',
        'username' => 'صارف کا نام',
        'password' => 'پاس ورڈ',
        'access_dashboard' => 'ڈیش بورڈ تک رسائی',
        'manage_students' => 'طلباء کا انتظام کریں',
        'manage_teachers' => 'اساتذہ کا انتظام کریں',
        'manage_classes_subjects' => 'کلاسز اور مضامین کا انتظام کریں',
        'manage_timetables' => 'ٹائم ٹیبل کا انتظام کریں',
        'manage_attendance' => 'حاضری کا انتظام کریں',
        'manage_exams_marks' => 'امتحانات اور نمبروں کا انتظام کریں',
        'manage_fees' => 'فیس کا انتظام کریں',
        'reports' => 'رپورٹس',
        'user_management' => 'صارف کا انتظام',
        'add_new_student' => 'نیا طالب علم شامل کریں',
        'student_list' => 'طلباء کی فہرست',
        'edit_student' => 'طالب علم میں ترمیم کریں',
        'delete_student' => 'طالب علم کو حذف کریں',
        'add_new_teacher' => 'نیا استاد شامل کریں',
        'teacher_list' => 'اساتذہ کی فہرست',
        'edit_teacher' => 'استاد میں ترمیم کریں',
        'delete_teacher' => 'استاد کو حذف کریں',
        'class_list' => 'کلاس کی فہرست',
        'add_new_class' => 'نئی کلاس شامل کریں',
        'edit_class' => 'کلاس میں ترمیم کریں',
        'delete_class' => 'کلاس کو حذف کریں',
        'subject_list' => 'مضامین کی فہرست',
        'add_new_subject' => 'نیا مضمون شامل کریں',
        'edit_subject' => 'مضمون میں ترمیم کریں',
        'delete_subject' => 'مضمون کو حذف کریں',
        'attendance_register' => 'حاضری رجسٹر',
        'take_attendance' => 'حاضری لگائیں',
        'view_attendance' => 'حاضری دیکھیں',
        'exam_list' => 'امتحانات کی فہرست',
        'add_new_exam' => 'نیا امتحان شامل کریں',
        'enter_marks' => 'نمبر درج کریں',
        'view_marks' => 'نمبر دیکھیں',
        'fee_invoices' => 'فیس کے بل',
        'add_new_invoice' => 'نیا بل شامل کریں',
        'view_fee_status' => 'فیس کی حیثیت دیکھیں',
        'mark_as_paid' => 'ادا شدہ کے طور پر نشان زد کریں',
        'attendance_report' => 'حاضری کی رپورٹ',
        'marks_report' => 'نمبروں کی رپورٹ',
        'fee_report' => 'فیس کی رپورٹ',
        'create_user_account' => 'صارف اکاؤنٹ بنائیں',
        'role' => 'کردار',
        'confirm_password' => 'پاس ورڈ کی تصدیق کریں',
        'take_class_attendance' => 'کلاس کی حاضری لگائیں',
        'upload_assignments' => 'اسائنمنٹس اور مطالعہ کا مواد اپ لوڈ کریں',
        'enter_exam_marks' => 'امتحانی نمبر درج کریں',
        'view_timetable' => 'ٹائم ٹیبل دیکھیں',
        'download_assignments' => 'اسائنمنٹس ڈاؤن لوڈ کریں',
        'view_performance' => 'کارکردگی دیکھیں',
        'view_student_fees' => 'طالب علم کی فیس دیکھیں',
        'select_class' => 'کلاس منتخب کریں',
        'select_subject' => 'مضمون منتخب کریں',
        'select_date' => 'تاریخ منتخب کریں',
        'status' => 'حیثیت',
        'present' => 'حاضر',
        'absent' => 'غیر حاضر',
        'late' => 'دیر سے',
        'save_attendance' => 'حاضری محفوظ کریں',
        'upload_file' => 'فائل اپ لوڈ کریں',
        'choose_file' => 'فائل منتخب کریں',
        'add_assignment' => 'اسائنمنٹ شامل کریں',
        'title' => 'عنوان',
        'description' => 'تفصیل',
        'due_date' => 'آخری تاریخ',
        'upload_assignment' => 'اسائنمنٹ اپ لوڈ کریں',
        'select_exam' => 'امتحان منتخب کریں',
        'marks_obtained' => 'حاصل کردہ نمبر',
        'max_marks' => 'زیادہ سے زیادہ نمبر',
        'save_marks' => 'نمبر محفوظ کریں',
        'roll_no' => 'رول نمبر',
        'class_name' => 'کلاس کا نام',
        'view_details' => 'تفصیلات دیکھیں',
        'add_student' => 'طالب علم شامل کریں',
        'edit_student_details' => 'طالب علم کی تفصیلات میں ترمیم کریں',
        'save_changes' => 'تبدیلیاں محفوظ کریں',
        'add_teacher' => 'استاد شامل کریں',
        'edit_teacher_details' => 'استاد کی تفصیلات میں ترمیم کریں',
        'subject_specialty' => 'موضوع کی مہارت',
        'add_class' => 'کلاس شامل کریں',
        'edit_class_details' => 'کلاس کی تفصیلات میں ترمیم کریں',
        'assigned_teacher' => 'مقرر کردہ استاد',
        'add_subject' => 'مضمون شامل کریں',
        'edit_subject_details' => 'مضمون کی تفصیلات میں ترمیم کریں',
        'add_timetable_entry' => 'ٹائم ٹیبل اندراج شامل کریں',
        'day_of_week' => 'ہفتے کا دن',
        'start_time' => 'شروع کا وقت',
        'end_time' => 'اختتامی وقت',
        'monday' => 'پیر',
        'tuesday' => 'منگل',
        'wednesday' => 'بدھ',
        'thursday' => 'جمعرات',
        'friday' => 'جمعہ',
        'saturday' => 'ہفتہ',
        'sunday' => 'اتوار',
        'add_exam' => 'امتحان شامل کریں',
        'exam_name' => 'امتحان کا نام',
        'exam_date' => 'امتحان کی تاریخ',
        'add_invoice' => 'بل شامل کریں',
        'amount' => 'رقم',
        'due_date_invoice' => 'آخری تاریخ',
        'paid_date' => 'ادا شدہ تاریخ',
        'invoice_status' => 'حیثیت',
        'generate_report' => 'رپورٹ تیار کریں',
        'select_report_type' => 'رپورٹ کی قسم منتخب کریں',
        'export_csv' => 'CSV برآمد کریں',
        'export_pdf' => 'PDF برآمد کریں',
        'no_records' => 'کوئی ریکارڈ نہیں ملا۔',
        'gallery_images' => 'گیلری کی تصاویر',
        'upload_image' => 'تصویر اپ لوڈ کریں',
        'add_new_image' => 'نئی تصویر شامل کریں',
        'image_title' => 'تصویر کا عنوان',
        'image' => 'تصویر',
        'add_image' => 'تصویر شامل کریں',
        'backup_restore' => 'بیک اپ اور بحالی',
        'export_data' => 'ڈیٹا ایکسپورٹ کریں',
        'import_data' => 'ڈیٹا امپورٹ کریں',
        'choose_json_file' => 'JSON فائل منتخب کریں',
        'upload_json_file' => 'JSON فائل اپ لوڈ کریں',
        'news_announcements_heading' => 'خبریں اور اعلانات',
        'events_calendar_heading' => 'تقریبات کا کیلنڈر',
        'application_submitted' => 'آپ کی درخواست کامیابی سے جمع ہو گئی ہے!',
        'message_sent' => 'آپ کا پیغام کامیابی سے بھیجا گیا ہے!',
        'invalid_credentials' => 'غلط صارف نام یا پاس ورڈ۔',
        'not_authorized' => 'آپ کو اس صفحے تک رسائی کی اجازت نہیں ہے۔',
        'data_exported_success' => 'ڈیٹا کامیابی سے ایکسپورٹ ہو گیا۔',
        'data_imported_success' => 'ڈیٹا کامیابی سے امپورٹ ہو گیا۔',
        'data_import_fail' => 'ڈیٹا امپورٹ کرنے میں خرابی۔ براہ کرم فائل فارمیٹ چیک کریں۔',
        'add_news' => 'خبر/اعلان شامل کریں',
        'edit_news' => 'خبر/اعلان میں ترمیم کریں',
        'delete_news' => 'خبر/اعلان حذف کریں',
        'add_event' => 'تقریب شامل کریں',
        'edit_event' => 'تقریب میں ترمیم کریں',
        'delete_event' => 'تقریب حذف کریں',
        'published_date' => 'تاریخ اشاعت',
        'event_date' => 'تقریب کی تاریخ',
        'event_time' => 'تقریب کا وقت',
        'location' => 'مقام',
        'actions' => 'عمل',
        'edit' => 'ترمیم کریں',
        'delete' => 'حذف کریں',
        'view' => 'دیکھیں',
        'approve' => 'منظور کریں',
        'reject' => 'مسترد کریں',
        'mark_replied' => 'جواب دیا گیا کے طور پر نشان زد کریں',
        'select_student' => 'طالب علم منتخب کریں',
        'upload_submission' => 'جمع کرائی گئی فائل اپ لوڈ کریں',
        'submit_assignment' => 'اسائنمنٹ جمع کروائیں',
        'submission_file' => 'جمع کرائی گئی فائل',
        'update_status' => 'حیثیت کو اپ ڈیٹ کریں',
        'all_students' => 'تمام طلباء',
        'select_student_first' => 'پہلے طالب علم منتخب کریں',
        'no_student_linked_parent' => 'آپ کے اکاؤنٹ سے کوئی طالب علم منسلک نہیں ہے۔ براہ کرم اسکول انتظامیہ سے رابطہ کریں۔',
        'selected_student_not_found' => 'منتخب کردہ طالب علم نہیں ملا یا آپ کے اکاؤنٹ سے منسلک نہیں ہے۔',
        'attendance_saved' => 'حاضری کامیابی سے محفوظ ہو گئی!',
        'assignment_submitted' => 'اسائنمنٹ کامیابی سے جمع ہو گئی!',
        'admission_status_updated' => 'داخلہ کی حیثیت کامیابی سے اپ ڈیٹ ہو گئی!',
        'contact_status_updated' => 'رابطہ پیغام کی حیثیت کامیابی سے اپ ڈیٹ ہو گئی!',
        'password_reset_link_sent' => 'اگر اس ای میل کے ساتھ کوئی اکاؤنٹ موجود ہے تو پاس ورڈ ری سیٹ لنک بھیج دیا گیا ہے۔',
        'password_reset' => 'پاس ورڈ ری سیٹ',
        'new_password' => 'نیا پاس ورڈ',
        'reset_password' => 'پاس ورڈ ری سیٹ کریں',
        'invalid_reset_link' => 'غلط یا میعاد ختم شدہ پاس ورڈ ری سیٹ لنک۔',
        'password_updated_success' => 'آپ کا پاس ورڈ کامیابی سے اپ ڈیٹ ہو گیا ہے۔ براہ کرم لاگ ان کریں۔',
        'password_mismatch' => 'نیا پاس ورڈ اور تصدیقی پاس ورڈ مماثل نہیں ہیں۔',
    ]
];

$t = $phrases[$lang];

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $username = trim($_POST['username']);
                $password = trim($_POST['password']);

                $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: ?page=dashboard&lang=$lang");
                    exit;
                } else {
                    $login_error = $t['invalid_credentials'];
                }
                break;

            case 'submit_admission':
                $student_name = trim($_POST['student_name']);
                $father_name = trim($_POST['father_name']);
                $dob = trim($_POST['dob']);
                $gender = trim($_POST['gender']);
                $previous_school = trim($_POST['previous_school']);
                $applying_for_class = trim($_POST['applying_for_class']);
                $address = trim($_POST['address']);
                $phone = trim($_POST['phone']);
                $email = trim($_POST['email']);

                $stmt = $pdo->prepare("INSERT INTO admissions (student_name, father_name, dob, gender, previous_school, applying_for_class, address, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$student_name, $father_name, $dob, $gender, $previous_school, $applying_for_class, $address, $phone, $email]);
                $_SESSION['message'] = $t['application_submitted'];
                header("Location: ?page=admissions&lang=$lang");
                exit;
                break;

            case 'submit_contact':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $subject = trim($_POST['subject']);
                $message = trim($_POST['message']);

                $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $subject, $message]);

                send_email('admin@example.com', 'New Contact Form Submission: ' . $subject, "From: $name ($email)\n\nMessage:\n$message");

                $_SESSION['message'] = $t['message_sent'];
                header("Location: ?page=contact&lang=$lang");
                exit;
                break;

            case 'add_student':
                if (isAdmin()) {
                    $name = trim($_POST['name']);
                    $username = trim($_POST['username']);
                    $password = hashPassword($_POST['password']);
                    $class_id = trim($_POST['class_id']);
                    $roll_no = trim($_POST['roll_no']);
                    $dob = trim($_POST['dob']);
                    $address = trim($_POST['address']);
                    $phone = trim($_POST['phone']);
                    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                        $stmt->execute([$username, $password]);
                        $user_id = $pdo->lastInsertId();

                        $stmt = $pdo->prepare("INSERT INTO students (user_id, name, class_id, roll_no, dob, address, phone, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $name, $class_id, $roll_no, $dob, $address, $phone, $parent_id]);
                        $pdo->commit();
                        $_SESSION['message'] = "Student added successfully!";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Error adding student: " . $e->getMessage();
                    }
                    header("Location: ?page=dashboard&section=students&lang=$lang");
                    exit;
                }
                break;

            case 'edit_student':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $class_id = trim($_POST['class_id']);
                    $roll_no = trim($_POST['roll_no']);
                    $dob = trim($_POST['dob']);
                    $address = trim($_POST['address']);
                    $phone = trim($_POST['phone']);
                    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

                    $stmt = $pdo->prepare("UPDATE students SET name = ?, class_id = ?, roll_no = ?, dob = ?, address = ?, phone = ?, parent_id = ? WHERE id = ?");
                    $stmt->execute([$name, $class_id, $roll_no, $dob, $address, $phone, $parent_id, $id]);
                    $_SESSION['message'] = "Student updated successfully!";
                    header("Location: ?page=dashboard&section=students&lang=$lang");
                    exit;
                }
                break;

            case 'delete_student':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                    $stmt->execute([$id]);
                    $student = $stmt->fetch();
                    if ($student) {
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                            $stmt->execute([$id]);
                            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->execute([$student['user_id']]);
                            $pdo->commit();
                            $_SESSION['message'] = "Student deleted successfully!";
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
                        }
                    }
                    header("Location: ?page=dashboard&section=students&lang=$lang");
                    exit;
                }
                break;

            case 'add_teacher':
                if (isAdmin()) {
                    $name = trim($_POST['name']);
                    $username = trim($_POST['username']);
                    $password = hashPassword($_POST['password']);
                    $subject_specialty = trim($_POST['subject_specialty']);
                    $phone = trim($_POST['phone']);
                    $email = trim($_POST['email']);

                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'teacher', ?)");
                        $stmt->execute([$username, $password, $email]);
                        $user_id = $pdo->lastInsertId();

                        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name, subject_specialty, phone, email) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $name, $subject_specialty, $phone, $email]);
                        $pdo->commit();
                        $_SESSION['message'] = "Teacher added successfully!";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Error adding teacher: " . $e->getMessage();
                    }
                    header("Location: ?page=dashboard&section=teachers&lang=$lang");
                    exit;
                }
                break;

            case 'edit_teacher':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $subject_specialty = trim($_POST['subject_specialty']);
                    $phone = trim($_POST['phone']);
                    $email = trim($_POST['email']);

                    $stmt = $pdo->prepare("UPDATE teachers SET name = ?, subject_specialty = ?, phone = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $subject_specialty, $phone, $email, $id]);
                    $_SESSION['message'] = "Teacher updated successfully!";
                    header("Location: ?page=dashboard&section=teachers&lang=$lang");
                    exit;
                }
                break;

            case 'delete_teacher':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
                    $stmt->execute([$id]);
                    $teacher = $stmt->fetch();
                    if ($teacher) {
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
                            $stmt->execute([$id]);
                            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->execute([$teacher['user_id']]);
                            $pdo->commit();
                            $_SESSION['message'] = "Teacher deleted successfully!";
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            $_SESSION['error'] = "Error deleting teacher: " . $e->getMessage();
                        }
                    }
                    header("Location: ?page=dashboard&section=teachers&lang=$lang");
                    exit;
                }
                break;

            case 'add_class':
                if (isAdmin()) {
                    $name = trim($_POST['name']);
                    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;

                    $stmt = $pdo->prepare("INSERT INTO classes (name, teacher_id) VALUES (?, ?)");
                    $stmt->execute([$name, $teacher_id]);
                    $_SESSION['message'] = "Class added successfully!";
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'edit_class':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;

                    $stmt = $pdo->prepare("UPDATE classes SET name = ?, teacher_id = ? WHERE id = ?");
                    $stmt->execute([$name, $teacher_id, $id]);
                    $_SESSION['message'] = "Class updated successfully!";
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'delete_class':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Class deleted successfully!";
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'add_subject':
                if (isAdmin()) {
                    $name = trim($_POST['name']);
                    $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
                    $stmt->execute([$name]);
                    $_SESSION['message'] = "Subject added successfully!";
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'edit_subject':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $stmt = $pdo->prepare("UPDATE subjects SET name = ? WHERE id = ?");
                    $stmt->execute([$name, $id]);
                    $_SESSION['message'] = "Subject updated successfully!";
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'delete_subject':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Subject deleted successfully!";
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'add_class_subject':
                if (isAdmin()) {
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $stmt = $pdo->prepare("INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)");
                    try {
                        $stmt->execute([$class_id, $subject_id]);
                        $_SESSION['message'] = "Class-Subject linkage added successfully!";
                    } catch (PDOException $e) {
                        $_SESSION['error'] = "Error: Class already linked to this subject or invalid IDs.";
                    }
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'delete_class_subject':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM class_subjects WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Class-Subject linkage deleted successfully!";
                    header("Location: ?page=dashboard&section=classes_subjects&lang=$lang");
                    exit;
                }
                break;

            case 'add_timetable_entry':
                if (isAdmin()) {
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $teacher_id = $_POST['teacher_id'];
                    $day_of_week = $_POST['day_of_week'];
                    $start_time = $_POST['start_time'];
                    $end_time = $_POST['end_time'];

                    $stmt = $pdo->prepare("INSERT INTO timetables (class_id, subject_id, teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time]);
                    $_SESSION['message'] = "Timetable entry added successfully!";
                    header("Location: ?page=dashboard&section=timetables&lang=$lang");
                    exit;
                }
                break;

            case 'edit_timetable_entry':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $teacher_id = $_POST['teacher_id'];
                    $day_of_week = $_POST['day_of_week'];
                    $start_time = $_POST['start_time'];
                    $end_time = $_POST['end_time'];

                    $stmt = $pdo->prepare("UPDATE timetables SET class_id = ?, subject_id = ?, teacher_id = ?, day_of_week = ?, start_time = ?, end_time = ? WHERE id = ?");
                    $stmt->execute([$class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $id]);
                    $_SESSION['message'] = "Timetable entry updated successfully!";
                    header("Location: ?page=dashboard&section=timetables&lang=$lang");
                    exit;
                }
                break;

            case 'delete_timetable_entry':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM timetables WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Timetable entry deleted successfully!";
                    header("Location: ?page=dashboard&section=timetables&lang=$lang");
                    exit;
                }
                break;

            case 'save_attendance':
                if (isTeacher() || isAdmin()) {
                    $class_id = $_POST['class_id'];
                    $attendance_date = $_POST['attendance_date'];
                    $subject_id = isset($_POST['subject_id']) && $_POST['subject_id'] !== '' ? $_POST['subject_id'] : null;

                    foreach ($_POST['attendance'] as $student_id => $status) {
                        $stmt = $pdo->prepare("
                            INSERT INTO attendance (student_id, class_id, subject_id, attendance_date, status) 
                            VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE status = ?
                        ");
                        $stmt->execute([$student_id, $class_id, $subject_id, $attendance_date, $status, $status]);
                    }
                    $_SESSION['message'] = $t['attendance_saved'];
                    $redirect_page = isAdmin() ? 'dashboard' : 'dashboard';
                    $redirect_section = isAdmin() ? 'attendance' : 'attendance';
                    header("Location: ?page=$redirect_page&section=$redirect_section&lang=$lang");
                    exit;
                }
                break;

            case 'add_exam':
                if (isAdmin()) {
                    $name = trim($_POST['name']);
                    $class_id = trim($_POST['class_id']);
                    $subject_id = trim($_POST['subject_id']);
                    $exam_date = trim($_POST['exam_date']);
                    $max_marks = trim($_POST['max_marks']);

                    $stmt = $pdo->prepare("INSERT INTO exams (name, class_id, subject_id, exam_date, max_marks) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $class_id, $subject_id, $exam_date, $max_marks]);
                    $_SESSION['message'] = "Exam added successfully!";
                    header("Location: ?page=dashboard&section=exams_marks&lang=$lang");
                    exit;
                }
                break;

            case 'edit_exam':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $class_id = trim($_POST['class_id']);
                    $subject_id = trim($_POST['subject_id']);
                    $exam_date = trim($_POST['exam_date']);
                    $max_marks = trim($_POST['max_marks']);

                    $stmt = $pdo->prepare("UPDATE exams SET name = ?, class_id = ?, subject_id = ?, exam_date = ?, max_marks = ? WHERE id = ?");
                    $stmt->execute([$name, $class_id, $subject_id, $exam_date, $max_marks, $id]);
                    $_SESSION['message'] = "Exam updated successfully!";
                    header("Location: ?page=dashboard&section=exams_marks&lang=$lang");
                    exit;
                }
                break;

            case 'delete_exam':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Exam deleted successfully!";
                    header("Location: ?page=dashboard&section=exams_marks&lang=$lang");
                    exit;
                }
                break;

            case 'save_marks':
                if (isTeacher() || isAdmin()) {
                    $exam_id = $_POST['exam_id'];
                    foreach ($_POST['marks'] as $student_id => $marks_obtained) {
                        $marks_obtained = intval($marks_obtained);
                        $stmt = $pdo->prepare("INSERT INTO marks (exam_id, student_id, marks_obtained) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE marks_obtained = ?");
                        $stmt->execute([$exam_id, $student_id, $marks_obtained, $marks_obtained]);
                    }
                    $_SESSION['message'] = "Marks saved successfully!";
                    $redirect_page = isAdmin() ? 'dashboard' : 'dashboard';
                    $redirect_section = isAdmin() ? 'exams_marks' : 'exams_marks';
                    header("Location: ?page=$redirect_page&section=$redirect_section&lang=$lang");
                    exit;
                }
                break;

            case 'add_fee_invoice':
                if (isAdmin()) {
                    $student_id = trim($_POST['student_id']);
                    $amount = trim($_POST['amount']);
                    $due_date = trim($_POST['due_date']);
                    $status = trim($_POST['status']);
                    $description = trim($_POST['description']);
                    $paid_date = ($status == 'Paid') ? date('Y-m-d') : null;

                    $stmt = $pdo->prepare("INSERT INTO fees (student_id, amount, due_date, paid_date, status, description) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$student_id, $amount, $due_date, $paid_date, $status, $description]);
                    $_SESSION['message'] = "Fee invoice added successfully!";
                    header("Location: ?page=dashboard&section=fees&lang=$lang");
                    exit;
                }
                break;

            case 'edit_fee_invoice':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $student_id = trim($_POST['student_id']);
                    $amount = trim($_POST['amount']);
                    $due_date = trim($_POST['due_date']);
                    $status = trim($_POST['status']);
                    $description = trim($_POST['description']);
                    $paid_date = ($status == 'Paid') ? (isset($_POST['paid_date']) && !empty($_POST['paid_date']) ? $_POST['paid_date'] : date('Y-m-d')) : null;

                    $stmt = $pdo->prepare("UPDATE fees SET student_id = ?, amount = ?, due_date = ?, paid_date = ?, status = ?, description = ? WHERE id = ?");
                    $stmt->execute([$student_id, $amount, $due_date, $paid_date, $status, $description, $id]);
                    $_SESSION['message'] = "Fee invoice updated successfully!";
                    header("Location: ?page=dashboard&section=fees&lang=$lang");
                    exit;
                }
                break;

            case 'delete_fee_invoice':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM fees WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Fee invoice deleted successfully!";
                    header("Location: ?page=dashboard&section=fees&lang=$lang");
                    exit;
                }
                break;

            case 'mark_fee_paid':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("UPDATE fees SET status = 'Paid', paid_date = CURDATE() WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Fee marked as Paid!";
                    header("Location: ?page=dashboard&section=fees&lang=$lang");
                    exit;
                }
                break;

            case 'create_user_account':
                if (isAdmin()) {
                    $username = trim($_POST['username']);
                    $password = $_POST['password'];
                    $confirm_password = $_POST['confirm_password'];
                    $role = trim($_POST['role']);
                    $email = trim($_POST['email']);

                    if ($password !== $confirm_password) {
                        $_SESSION['error'] = $t['password_mismatch'];
                        header("Location: ?page=dashboard&section=user_management&lang=$lang");
                        exit;
                    }

                    $hashed_password = hashPassword($password);

                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
                    try {
                        $stmt->execute([$username, $hashed_password, $role, $email]);
                        $_SESSION['message'] = "User account created successfully!";
                    } catch (PDOException $e) {
                        $_SESSION['error'] = "Error creating user: Username might already exist.";
                    }
                    header("Location: ?page=dashboard&section=user_management&lang=$lang");
                    exit;
                }
                break;

            case 'edit_user_account':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $username = trim($_POST['username']);
                    $role = trim($_POST['role']);
                    $email = trim($_POST['email']);
                    $password = !empty($_POST['password']) ? $_POST['password'] : null;
                    $confirm_password = !empty($_POST['confirm_password']) ? $_POST['confirm_password'] : null;

                    if ($password !== null && $password !== $confirm_password) {
                        $_SESSION['error'] = $t['password_mismatch'];
                        header("Location: ?page=dashboard&section=user_management&lang=$lang");
                        exit;
                    }

                    if ($password) {
                        $hashed_password = hashPassword($password);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ?, email = ? WHERE id = ?");
                        $stmt->execute([$username, $hashed_password, $role, $email, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, email = ? WHERE id = ?");
                        $stmt->execute([$username, $role, $email, $id]);
                    }
                    $_SESSION['message'] = "User account updated successfully!";
                    header("Location: ?page=dashboard&section=user_management&lang=$lang");
                    exit;
                }
                break;

            case 'delete_user_account':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "User account deleted successfully!";
                    header("Location: ?page=dashboard&section=user_management&lang=$lang");
                    exit;
                }
                break;

            case 'add_assignment':
                if (isTeacher()) {
                    $teacher_id = $_SESSION['id'];
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $due_date = trim($_POST['due_date']);
                    $file_path = null;

                    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/assignments/';
                        $file_name = uniqid() . '_' . basename($_FILES['assignment_file']['name']);
                        $target_file = $upload_dir . $file_name;
                        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
                            $file_path = $target_file;
                        } else {
                            $_SESSION['error'] = "Error uploading file.";
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO assignments (teacher_id, class_id, subject_id, title, description, due_date, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$teacher_id, $class_id, $subject_id, $title, $description, $due_date, $file_path]);
                    $_SESSION['message'] = "Assignment added successfully!";
                    header("Location: ?page=dashboard&section=assignments&lang=$lang");
                    exit;
                }
                break;

            case 'edit_assignment':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $due_date = trim($_POST['due_date']);
                    $file_path_old = $_POST['file_path_old'] ?? null;
                    $file_path = $file_path_old;

                    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/assignments/';
                        $file_name = uniqid() . '_' . basename($_FILES['assignment_file']['name']);
                        $target_file = $upload_dir . $file_name;
                        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
                            if ($file_path_old && file_exists($file_path_old)) {
                                unlink($file_path_old);
                            }
                            $file_path = $target_file;
                        } else {
                            $_SESSION['error'] = "Error uploading new file.";
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE assignments SET class_id = ?, subject_id = ?, title = ?, description = ?, due_date = ?, file_path = ? WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$class_id, $subject_id, $title, $description, $due_date, $file_path, $id, $_SESSION['id']]);
                    $_SESSION['message'] = "Assignment updated successfully!";
                    header("Location: ?page=dashboard&section=assignments&lang=$lang");
                    exit;
                }
                break;

            case 'delete_assignment':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("SELECT file_path FROM assignments WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $_SESSION['id']]);
                    $assignment = $stmt->fetch();
                    if ($assignment && $assignment['file_path'] && file_exists($assignment['file_path'])) {
                        unlink($assignment['file_path']);
                    }
                    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $_SESSION['id']]);
                    $_SESSION['message'] = "Assignment deleted successfully!";
                    header("Location: ?page=dashboard&section=assignments&lang=$lang");
                    exit;
                }
                break;

            case 'add_gallery_image':
                if (isAdmin()) {
                    $title = trim($_POST['title']);
                    $image_path = null;

                    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/gallery/';
                        $file_name = uniqid() . '_' . basename($_FILES['image_file']['name']);
                        $target_file = $upload_dir . $file_name;
                        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        if (!in_array($imageFileType, $allowed_types)) {
                            $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                            header("Location: ?page=dashboard&section=gallery&lang=$lang");
                            exit;
                        }

                        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                            $image_path = $target_file;
                        } else {
                            $_SESSION['error'] = "Sorry, there was an error uploading your file.";
                            header("Location: ?page=dashboard&section=gallery&lang=$lang");
                            exit;
                        }
                    } else {
                        $_SESSION['error'] = "No file uploaded or upload error.";
                        header("Location: ?page=dashboard&section=gallery&lang=$lang");
                        exit;
                    }

                    if ($image_path) {
                        $stmt = $pdo->prepare("INSERT INTO gallery (title, image_path) VALUES (?, ?)");
                        $stmt->execute([$title, $image_path]);
                        $_SESSION['message'] = "Image added to gallery successfully!";
                    }
                    header("Location: ?page=dashboard&section=gallery&lang=$lang");
                    exit;
                }
                break;

            case 'delete_gallery_image':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
                    $stmt->execute([$id]);
                    $image = $stmt->fetch();
                    if ($image && file_exists($image['image_path'])) {
                        unlink($image['image_path']);
                    }
                    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Image deleted from gallery successfully!";
                    header("Location: ?page=dashboard&section=gallery&lang=$lang");
                    exit;
                }
                break;

            case 'add_announcement':
                if (isAdmin()) {
                    $title = trim($_POST['title']);
                    $content = trim($_POST['content']);
                    $file_path = null;
                    if (isset($_FILES['announcement_file']) && $_FILES['announcement_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/announcements/';
                        $file_name = uniqid() . '_' . basename($_FILES['announcement_file']['name']);
                        $target_file = $upload_dir . $file_name;
                        if (move_uploaded_file($_FILES['announcement_file']['tmp_name'], $target_file)) {
                            $file_path = $target_file;
                        }
                    }
                    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, file_path) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $content, $file_path]);
                    $_SESSION['message'] = "Announcement added successfully!";
                    header("Location: ?page=dashboard&section=announcements&lang=$lang");
                    exit;
                }
                break;

            case 'edit_announcement':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $title = trim($_POST['title']);
                    $content = trim($_POST['content']);
                    $file_path_old = $_POST['file_path_old'] ?? null;
                    $file_path = $file_path_old;

                    if (isset($_FILES['announcement_file']) && $_FILES['announcement_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/announcements/';
                        $file_name = uniqid() . '_' . basename($_FILES['announcement_file']['name']);
                        $target_file = $upload_dir . $file_name;
                        if (move_uploaded_file($_FILES['announcement_file']['tmp_name'], $target_file)) {
                            if ($file_path_old && file_exists($file_path_old)) {
                                unlink($file_path_old);
                            }
                            $file_path = $target_file;
                        }
                    }
                    $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, file_path = ? WHERE id = ?");
                    $stmt->execute([$title, $content, $file_path, $id]);
                    $_SESSION['message'] = "Announcement updated successfully!";
                    header("Location: ?page=dashboard&section=announcements&lang=$lang");
                    exit;
                }
                break;

            case 'delete_announcement':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt_select = $pdo->prepare("SELECT file_path FROM announcements WHERE id = ?");
                    $stmt_select->execute([$id]);
                    $announcement = $stmt_select->fetch();
                    if ($announcement && $announcement['file_path'] && file_exists($announcement['file_path'])) {
                        unlink($announcement['file_path']);
                    }
                    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Announcement deleted successfully!";
                    header("Location: ?page=dashboard&section=announcements&lang=$lang");
                    exit;
                }
                break;

            case 'add_event':
                if (isAdmin()) {
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $event_date = trim($_POST['event_date']);
                    $event_time = trim($_POST['event_time']);
                    $location = trim($_POST['location']);
                    $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $event_date, $event_time, $location]);
                    $_SESSION['message'] = "Event added successfully!";
                    header("Location: ?page=dashboard&section=events&lang=$lang");
                    exit;
                }
                break;

            case 'edit_event':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $event_date = trim($_POST['event_date']);
                    $event_time = trim($_POST['event_time']);
                    $location = trim($_POST['location']);
                    $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $event_date, $event_time, $location, $id]);
                    $_SESSION['message'] = "Event updated successfully!";
                    header("Location: ?page=dashboard&section=events&lang=$lang");
                    exit;
                }
                break;

            case 'delete_event':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Event deleted successfully!";
                    header("Location: ?page=dashboard&section=events&lang=$lang");
                    exit;
                }
                break;

            case 'update_admission_status':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $status = $_POST['status'];
                    $stmt = $pdo->prepare("UPDATE admissions SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $_SESSION['message'] = $t['admission_status_updated'];
                    header("Location: ?page=dashboard&section=admissions_list&lang=$lang");
                    exit;
                }
                break;

            case 'update_contact_status':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $status = $_POST['status'];
                    $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $_SESSION['message'] = $t['contact_status_updated'];
                    header("Location: ?page=dashboard&section=contact_messages&lang=$lang");
                    exit;
                }
                break;

            case 'submit_student_assignment':
                if (isStudent()) {
                    $student_id = $_POST['student_id'];
                    $assignment_id = $_POST['assignment_id'];
                    $file_path = null;

                    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/student_assignments/';
                        $file_name = uniqid() . '_' . basename($_FILES['submission_file']['name']);
                        $target_file = $upload_dir . $file_name;
                        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_file)) {
                            $file_path = $target_file;
                        } else {
                            $_SESSION['error'] = "Error uploading file.";
                        }
                    } else {
                        $_SESSION['error'] = "No file uploaded or upload error.";
                    }

                    if ($file_path) {
                        $stmt = $pdo->prepare("INSERT INTO student_assignments (assignment_id, student_id, file_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE file_path = ?, submission_date = CURRENT_TIMESTAMP");
                        $stmt->execute([$assignment_id, $student_id, $file_path, $file_path]);
                        $_SESSION['message'] = $t['assignment_submitted'];
                    }
                    header("Location: ?page=dashboard&section=assignments&lang=$lang");
                    exit;
                }
                break;

            case 'request_password_reset':
                $email = trim($_POST['email']);
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    // Store token with user ID and expiration in a real application.
                    // For this single-file app, we'll simplify and just send the link.
                    // In production, you'd have a password_resets table.
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?page=reset_password&token=$token&email=" . urlencode($email);
                    send_email($email, "Password Reset for " . $t['school_name'], "Click here to reset your password: $reset_link\n\nThis link is valid for 1 hour.");
                }
                $_SESSION['message'] = $t['password_reset_link_sent'];
                header("Location: ?page=login&lang=$lang");
                exit;
                break;

            case 'reset_password':
                $email = trim($_POST['email']);
                $new_password = $_POST['new_password'];
                $confirm_new_password = $_POST['confirm_new_password'];
                $token = $_POST['token']; // In a real app, validate this token

                if ($new_password !== $confirm_new_password) {
                    $_SESSION['error'] = $t['password_mismatch'];
                    header("Location: ?page=reset_password&token=$token&email=" . urlencode($email) . "&lang=$lang");
                    exit;
                }

                $hashed_password = hashPassword($new_password);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $email]);

                $_SESSION['message'] = $t['password_updated_success'];
                header("Location: ?page=login&lang=$lang");
                exit;
                break;

            case 'export_data':
                if (isAdmin()) {
                    $tables = [
                        'users',
                        'students',
                        'teachers',
                        'classes',
                        'subjects',
                        'class_subjects',
                        'timetables',
                        'attendance',
                        'exams',
                        'marks',
                        'fees',
                        'announcements',
                        'events',
                        'admissions',
                        'contacts',
                        'assignments',
                        'gallery',
                        'student_assignments'
                    ];
                    $export_data = [];
                    foreach ($tables as $table) {
                        $stmt = $pdo->query("SELECT * FROM $table");
                        $export_data[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="school_data_backup_' . date('Ymd_His') . '.json"');
                    echo json_encode($export_data, JSON_PRETTY_PRINT);
                    exit;
                }
                break;

            case 'import_data':
                if (isAdmin() && isset($_FILES['json_file']) && $_FILES['json_file']['error'] == UPLOAD_ERR_OK) {
                    $file_content = file_get_contents($_FILES['json_file']['tmp_name']);
                    $import_data = json_decode($file_content, true);

                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($import_data)) {
                        $_SESSION['error'] = $t['data_import_fail'];
                        header("Location: ?page=dashboard&section=backup_restore&lang=$lang");
                        exit;
                    }

                    $pdo->beginTransaction();
                    try {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

                        $tables_ordered = [
                            'users',
                            'teachers',
                            'classes',
                            'subjects',
                            'students',
                            'class_subjects',
                            'timetables',
                            'exams',
                            'assignments',
                            'fees',
                            'announcements',
                            'events',
                            'admissions',
                            'contacts',
                            'gallery',
                            'attendance',
                            'marks',
                            'student_assignments'
                        ];

                        foreach (array_reverse($tables_ordered) as $table) {
                            $pdo->exec("TRUNCATE TABLE $table;");
                        }

                        foreach ($tables_ordered as $table) {
                            if (isset($import_data[$table]) && is_array($import_data[$table])) {
                                foreach ($import_data[$table] as $row) {
                                    $columns = implode(", ", array_keys($row));
                                    $placeholders = implode(", ", array_fill(0, count($row), "?"));
                                    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute(array_values($row));
                                }
                            }
                        }

                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                        $pdo->commit();
                        $_SESSION['message'] = $t['data_imported_success'];
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                        $_SESSION['error'] = $t['data_import_fail'] . " Error: " . $e->getMessage();
                    }
                    header("Location: ?page=dashboard&section=backup_restore&lang=$lang");
                    exit;
                }
                break;
        }
    }
}
if (isset($_GET['page']) && $_GET['page'] == 'ajax_data') {
    header('Content-Type: text/html');
    $type = $_GET['type'] ?? '';
    switch ($type) {
        case 'students_by_class':
            $class_id = $_GET['class_id'] ?? 0;
            if ($class_id) {
                $stmt = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? ORDER BY name");
                $stmt->execute([$class_id]);
                $students = $stmt->fetchAll();
                $options = ''; // Removed default 'Select Student' as it might be 'All Students' in some contexts
                foreach ($students as $student) {
                    $options .= '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
                }
                echo $options;
            } else {
                echo '';
            }
            break;

        case 'subjects_by_class':
            $class_id = $_GET['class_id'] ?? 0;
            if ($class_id) {
                $stmt = $pdo->prepare("SELECT s.id, s.name FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.class_id = ? ORDER BY s.name");
                $stmt->execute([$class_id]);
                $subjects = $stmt->fetchAll();
                $options = '<option value="">' . $t['select_subject'] . '</option>';
                foreach ($subjects as $subject) {
                    $options .= '<option value="' . $subject['id'] . '">' . htmlspecialchars($subject['name']) . '</option>';
                }
                echo $options;
            } else {
                echo '<option value="">' . $t['select_class'] . ' first</option>';
            }
            break;

        case 'attendance_students':
            $class_id = $_GET['class_id'] ?? 0;
            $attendance_date = $_GET['attendance_date'] ?? date('Y-m-d');
            $subject_id = $_GET['subject_id'] ?? null;

            if ($class_id) {
                // Fetch students in the class
                $stmt_students = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? ORDER BY name");
                $stmt_students->execute([$class_id]);
                $students_in_class = $stmt_students->fetchAll();

                // Fetch existing attendance for the specific date, class, and subject (if any)
                $stmt_existing_attendance = $pdo->prepare("
                    SELECT student_id, status 
                    FROM attendance 
                    WHERE class_id = ? AND attendance_date = ? AND (subject_id = ? OR (subject_id IS NULL AND ? IS NULL))
                ");
                $stmt_existing_attendance->execute([$class_id, $attendance_date, $subject_id, $subject_id]);
                $existing_attendance = $stmt_existing_attendance->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as [student_id => status]

                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered table-striped">';
                echo '<thead><tr><th>' . $t['student_name'] . '</th><th>' . $t['status'] . '</th></tr></thead>';
                echo '<tbody>';
                if ($students_in_class) {
                    foreach ($students_in_class as $student) {
                        $current_status = $existing_attendance[$student['id']] ?? 'Present'; // Default to Present if no record
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($student['name']) . '</td>';
                        echo '<td>';
                        echo '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="attendance[' . $student['id'] . ']" value="Present" ' . (($current_status == 'Present') ? 'checked' : '') . '> <label class="form-check-label">' . $t['present'] . '</label></div>';
                        echo '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="attendance[' . $student['id'] . ']" value="Absent" ' . (($current_status == 'Absent') ? 'checked' : '') . '> <label class="form-check-label">' . $t['absent'] . '</label></div>';
                        echo '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="attendance[' . $student['id'] . ']" value="Late" ' . (($current_status == 'Late') ? 'checked' : '') . '> <label class="form-check-label">' . $t['late'] . '</label></div>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="2" class="text-center">' . $t['no_records'] . '</td></tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p>Select a class to load students for attendance.</p>';
            }
            break;

        case 'marks_students':
            $exam_id = $_GET['exam_id'] ?? 0;
            if ($exam_id) {
                $stmt_exam = $pdo->prepare("SELECT class_id, max_marks FROM exams WHERE id = ?");
                $stmt_exam->execute([$exam_id]);
                $exam_info = $stmt_exam->fetch();
                $class_id = $exam_info['class_id'] ?? 0;
                $max_marks = $exam_info['max_marks'] ?? 0;

                if ($class_id) {
                    $stmt = $pdo->prepare("
                        SELECT s.id, s.name, 
                               (SELECT marks_obtained FROM marks WHERE student_id = s.id AND exam_id = ?) AS marks_obtained 
                        FROM students s 
                        WHERE s.class_id = ? ORDER BY s.name
                    ");
                    $stmt->execute([$exam_id, $class_id]);
                    $students = $stmt->fetchAll();

                    echo '<div class="table-responsive">';
                    echo '<table class="table table-bordered table-striped">';
                    echo '<thead><tr><th>' . $t['student_name'] . '</th><th>' . $t['marks_obtained'] . ' (' . $t['max_marks'] . ': ' . $max_marks . ')</th></tr></thead>';
                    echo '<tbody>';
                    if ($students) {
                        foreach ($students as $student) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($student['name']) . '</td>';
                            echo '<td><input type="number" class="form-control" name="marks[' . $student['id'] . ']" min="0" max="' . $max_marks . '" value="' . htmlspecialchars($student['marks_obtained'] ?? '') . '"></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="2" class="text-center">' . $t['no_records'] . '</td></tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>';
                } else {
                    echo '<p>Select an exam to load students for marks entry.</p>';
                }
            } else {
                echo '<p>Select an exam to load students for marks entry.</p>';
            }
            break;

        default:
            echo '';
            break;
    }
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: ?page=home&lang=$lang");
    exit;
}

$page = $_GET['page'] ?? 'home';
$section = $_GET['section'] ?? '';

$message = '';
$error = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo ($lang == 'ur' ? 'rtl' : 'ltr'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Yasin Ullah, Pakistan">
    <meta name="description" content="<?php echo $t['school_name']; ?> - A complete school management system and public website.">
    <meta name="keywords" content="school management system, school website, education, Pakistan, students, teachers, parents, admissions, timetable, fees, exams">
    <title><?php echo $t['school_name']; ?> - <?php echo $t[$page] ?? $t['home']; ?></title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if ($lang == 'ur') : ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap">
    <?php endif; ?>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }

        <?php if ($lang == 'ur') : ?>body {
            font-family: 'Noto Nastaliq Urdu', 'Arial', sans-serif;
            direction: rtl;
        }

        .navbar-nav .nav-link,
        .btn,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        label,
        th,
        td {
            text-align: right;
        }

        .form-control,
        .form-select {
            text-align: right;
        }

        .input-group>.form-control,
        .input-group>.form-select {
            border-top-left-radius: var(--bs-border-radius);
            border-bottom-left-radius: var(--bs-border-radius);
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group:not(.has-validation)>.dropdown-toggle:nth-last-child(n+3),
        .input-group:not(.has-validation)>:not(:last-child):not(.dropdown-toggle):not(.dropdown-menu):not(.form-floating) {
            border-top-left-radius: var(--bs-border-radius);
            border-bottom-left-radius: var(--bs-border-radius);
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group>.form-floating:not(:last-child)>.form-control,
        .input-group>.form-floating:not(:last-child)>.form-select {
            border-top-left-radius: var(--bs-border-radius);
            border-bottom-left-radius: var(--bs-border-radius);
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group>.form-floating:not(:first-child)>.form-control,
        .input-group>.form-floating:not(:first-child)>.form-select {
            border-top-right-radius: var(--bs-border-radius);
            border-bottom-right-radius: var(--bs-border-radius);
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        <?php endif; ?>.navbar-brand {
            font-weight: bold;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('school.png') no-repeat center center/cover;
            color: white;
            padding: 100px 0;
            text-align: center;
        }

        .feature-box {
            padding: 30px;
            text-align: center;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .feature-box i {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .dashboard-sidebar {
            background-color: #343a40;
            color: white;
            padding: 20px;
            min-height: 100vh;
        }

        .dashboard-sidebar .nav-link {
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .dashboard-sidebar .nav-link i {
            margin-<?php echo ($lang == 'ur' ? 'right' : 'left'); ?>: 10px;
            margin-<?php echo ($lang == 'ur' ? 'left' : 'right'); ?>: 0;
        }

        .dashboard-sidebar .nav-link.active,
        .dashboard-sidebar .nav-link:hover {
            background-color: #28a745;
            color: white;
        }

        .dashboard-content {
            padding: 20px;
        }

        .table img {
            max-width: 100px;
            height: auto;
        }

        .modal-footer .btn {
            margin-top: 0;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
            list-style: none;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            left: 18px;
            /* For LTR */
            height: 100%;
            width: 4px;
            background: #e9ecef;
        }

        .timeline-item {
            margin-bottom: 20px;
            position: relative;
            padding-left: 25px;
            /* For LTR */
        }

        .timeline-item:after {
            content: '';
            position: absolute;
            left: 10px;
            /* For LTR */
            top: 43%;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 4px solid #28a745;
            /* Theme color */
            z-index: 1;
        }

        /* RTL support for Urdu */
        [dir="rtl"] .timeline:before {
            left: auto;
            right: 18px;
        }

        [dir="rtl"] .timeline-item {
            padding-left: 0;
            padding-right: 25px;
        }

        [dir="rtl"] .timeline-item:after {
            left: auto;
            right: 10px;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="?page=home&lang=<?php echo $lang; ?>"><?php echo $t['school_name']; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'home' ? 'active' : ''); ?>" href="?page=home&lang=<?php echo $lang; ?>"><?php echo $t['home']; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'news' ? 'active' : ''); ?>" href="?page=news&lang=<?php echo $lang; ?>"><?php echo $t['news']; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'events' ? 'active' : ''); ?>" href="?page=events&lang=<?php echo $lang; ?>"><?php echo $t['events']; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'admissions' ? 'active' : ''); ?>" href="?page=admissions&lang=<?php echo $lang; ?>"><?php echo $t['admissions']; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'contact' ? 'active' : ''); ?>" href="?page=contact&lang=<?php echo $lang; ?>"><?php echo $t['contact']; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'gallery' ? 'active' : ''); ?>" href="?page=gallery&lang=<?php echo $lang; ?>"><?php echo $t['gallery']; ?></a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo ($lang == 'en' ? 'English' : 'اردو'); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="?page=<?php echo $page; ?>&section=<?php echo $section; ?>&lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?page=<?php echo $page; ?>&section=<?php echo $section; ?>&lang=ur">اردو</a></li>
                        </ul>
                    </li>
                    <?php if (isAuthenticated()) : ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-success text-white mx-2" href="?page=dashboard&lang=<?php echo $lang; ?>"><?php echo $t['dashboard']; ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light" href="?action=logout&lang=<?php echo $lang; ?>"><?php echo $t['logout']; ?></a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white" href="?page=login&lang=<?php echo $lang; ?>"><?php echo $t['login']; ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($message) : ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error) : ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <main class="container-fluid">
        <?php
        switch ($page) {
            case 'home':
        ?>
                <section class="hero-section">
                    <div class="container">
                        <h1><?php echo $t['school_name']; ?></h1>
                        <p class="lead"><?php echo $t['mission_text']; ?></p>
                        <a href="?page=admissions&lang=<?php echo $lang; ?>" class="btn btn-lg btn-success"><?php echo $t['apply_now']; ?></a>
                    </div>
                </section>

                <section class="py-5 bg-light">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-6">
                                <h2><?php echo $t['our_mission']; ?></h2>
                                <p><?php echo $t['mission_text']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h2><?php echo $t['our_vision']; ?></h2>
                                <p><?php echo $t['vision_text']; ?></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="py-5">
                    <div class="container">
                        <h2 class="text-center mb-4"><?php echo $t['latest_news']; ?></h2>
                        <div class="row">
                            <?php
                            $stmt = $pdo->query("SELECT * FROM announcements ORDER BY published_date DESC LIMIT 3");
                            $announcements = $stmt->fetchAll();
                            if ($announcements) {
                                foreach ($announcements as $announcement) {
                                    echo '<div class="col-md-4">';
                                    echo '<div class="feature-box">';
                                    echo '<h4>' . htmlspecialchars($announcement['title']) . '</h4>';
                                    echo '<p>' . nl2br(htmlspecialchars($announcement['content'])) . '</p>';
                                    echo '<small class="text-muted">' . date("F j, Y", strtotime($announcement['published_date'])) . '</small>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-center">' . $t['no_records'] . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </section>

                <section class="py-5 bg-light">
                    <div class="container">
                        <h2 class="text-center mb-4"><?php echo $t['upcoming_events']; ?></h2>
                        <div class="accordion" id="eventsAccordion">
                            <?php
                            $stmt = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC");
                            $events = $stmt->fetchAll();
                            if ($events) {
                                $count = 0;
                                foreach ($events as $event) {
                                    $count++;
                                    // Combine date and time for the countdown attribute
                                    $event_datetime = $event['event_date'] . 'T' . ($event['event_time'] ?? '00:00:00');

                                    echo '<div class="accordion-item">';
                                    echo '  <h2 class="accordion-header" id="heading' . $count . '">';
                                    echo '    <button class="accordion-button' . ($count > 1 ? ' collapsed' : '') . '" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $count . '" aria-expanded="' . ($count === 1 ? 'true' : 'false') . '" aria-controls="collapse' . $count . '">';
                                    echo htmlspecialchars($event['title']) . ' - <span class="ms-2 text-muted">' . date("F j, Y", strtotime($event['event_date'])) . '</span>';
                                    echo '    </button>';
                                    echo '  </h2>';
                                    echo '  <div id="collapse' . $count . '" class="accordion-collapse collapse' . ($count === 1 ? ' show' : '') . '" aria-labelledby="heading' . $count . '" data-bs-parent="#eventsAccordion">';
                                    echo '    <div class="accordion-body">';
                                    // This is the new placeholder for the countdown timer
                                    echo '      <div class="countdown-timer alert alert-info text-center p-2" data-datetime="' . $event_datetime . '"></div>';
                                    echo '      <p class="mt-3">' . nl2br(htmlspecialchars($event['description'])) . '</p>';
                                    echo '      <small class="text-muted"><i class="fas fa-clock"></i> ' . date("h:i A", strtotime($event['event_time'])) . ' at <i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($event['location']) . '</small>';
                                    echo '    </div>';
                                    echo '  </div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-center">' . $t['no_records'] . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </section>
            <?php
                break;

            case 'news':
            ?>
                <section class="py-5">
                    <div class="container">
                        <h2 class="mb-5 text-center"><?php echo $t['news_announcements_heading']; ?></h2>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM announcements ORDER BY published_date DESC");
                        $announcements = $stmt->fetchAll();
                        if ($announcements) {
                            echo '<ul class="timeline">';
                            foreach ($announcements as $announcement) {
                                echo '<li class="timeline-item">';
                                echo '  <div class="card shadow-sm">';
                                echo '    <div class="card-body">';
                                echo '      <h5 class="card-title">' . htmlspecialchars($announcement['title']) . '</h5>';
                                echo '      <p class="card-text text-muted mb-2"><small><i class="fas fa-calendar-alt me-2"></i>Published on: ' . date("F j, Y", strtotime($announcement['published_date'])) . '</small></p>';
                                echo '      <p class="card-text">' . nl2br(htmlspecialchars($announcement['content'])) . '</p>';
                                if (!empty($announcement['file_path'])) {
                                    echo '<p class="card-text mt-2"><a href="' . htmlspecialchars($announcement['file_path']) . '" class="btn btn-sm btn-outline-success" download>Download Attachment</a></p>';
                                }
                                echo '    </div>';
                                echo '  </div>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-center">' . $t['no_records'] . '</p>';
                        }
                        ?>
                    </div>
                </section>
            <?php
                break;
            case 'events':
            ?>
                <section class="py-5">
                    <div class="container">
                        <h2 class="mb-5 text-center"><?php echo $t['events_calendar_heading']; ?></h2>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
                        $events = $stmt->fetchAll();
                        if ($events) {
                            echo '<ul class="timeline">';
                            foreach ($events as $event) {
                                echo '<li class="timeline-item">';
                                echo '  <div class="card shadow-sm">';
                                echo '    <div class="card-body">';
                                echo '      <div class="d-flex align-items-start">';
                                echo '        <div class="text-center me-4">';
                                echo '          <div class="bg-success text-white rounded px-3 py-2 shadow-sm">';
                                echo '            <span class="d-block fs-4 fw-bold">' . date("d", strtotime($event['event_date'])) . '</span>';
                                echo '            <small class="d-block">' . date("M", strtotime($event['event_date'])) . '</small>';
                                echo '            <small class="d-block" style="font-size: 0.75em;">' . date("Y", strtotime($event['event_date'])) . '</small>';
                                echo '          </div>';
                                echo '        </div>';
                                echo '        <div>';
                                echo '          <h5 class="card-title mb-1">' . htmlspecialchars($event['title']) . '</h5>';
                                echo '          <p class="card-text mb-2">' . nl2br(htmlspecialchars($event['description'])) . '</p>';
                                echo '          <ul class="list-unstyled text-muted small">';
                                if ($event['event_time']) {
                                    echo '<li class="mb-1"><i class="fas fa-clock fa-fw me-2"></i>' . date("h:i A", strtotime($event['event_time'])) . '</li>';
                                }
                                if ($event['location']) {
                                    echo '<li><i class="fas fa-map-marker-alt fa-fw me-2"></i>' . htmlspecialchars($event['location']) . '</li>';
                                }
                                echo '          </ul>';
                                echo '        </div>';
                                echo '      </div>';
                                echo '    </div>';
                                echo '  </div>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-center">' . $t['no_records'] . '</p>';
                        }
                        ?>
                    </div>
                </section>
            <?php
                break;

            case 'admissions':
            ?>
                <section class="py-5">
                    <div class="container">
                        <h2 class="mb-4"><?php echo $t['admission_form']; ?></h2>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="submit_admission">
                            <div class="mb-3">
                                <label for="student_name" class="form-label"><?php echo $t['student_name']; ?></label>
                                <input type="text" class="form-control" id="student_name" name="student_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="father_name" class="form-label"><?php echo $t['father_name']; ?></label>
                                <input type="text" class="form-control" id="father_name" name="father_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="dob" class="form-label"><?php echo $t['date_of_birth']; ?></label>
                                <input type="date" class="form-control" id="dob" name="dob" required>
                            </div>
                            <div class="mb-3">
                                <label for="gender" class="form-label"><?php echo $t['gender']; ?></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="Male"><?php echo $t['male']; ?></option>
                                    <option value="Female"><?php echo $t['female']; ?></option>
                                    <option value="Other"><?php echo $t['other']; ?></option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="previous_school" class="form-label"><?php echo $t['previous_school']; ?></label>
                                <input type="text" class="form-control" id="previous_school" name="previous_school">
                            </div>
                            <div class="mb-3">
                                <label for="applying_for_class" class="form-label"><?php echo $t['applying_for_class']; ?></label>
                                <input type="text" class="form-control" id="applying_for_class" name="applying_for_class" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label"><?php echo $t['address']; ?></label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label"><?php echo $t['phone_number']; ?></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo $t['email_address']; ?></label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <button type="submit" class="btn btn-success"><?php echo $t['submit_application']; ?></button>
                        </form>
                    </div>
                </section>
            <?php
                break;

            case 'contact':
            ?>
                <section class="py-5">
                    <div class="container">
                        <h2 class="mb-4"><?php echo $t['get_in_touch']; ?></h2>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="submit_contact">
                            <div class="mb-3">
                                <label for="name" class="form-label"><?php echo $t['full_name']; ?></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo $t['email_address']; ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label"><?php echo $t['subject']; ?></label>
                                <input type="text" class="form-control" id="subject" name="subject">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label"><?php echo $t['message']; ?></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success"><?php echo $t['send_message']; ?></button>
                        </form>
                        <div class="row mt-5 pt-5 border-top">
                            <div class="col-lg-5 mb-4 mb-lg-0">
                                <h3 class="mb-3">Contact Information</h3>
                                <p>We are here to help. Please don't hesitate to get in touch with us using the details below.</p>
                                <ul class="list-unstyled" style="font-size: 1.1rem;">
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fas fa-phone fa-fw me-3 text-success"></i>
                                        <a href="tel:+923001234567">+92 300 1234567</a>
                                    </li>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fas fa-envelope fa-fw me-3 text-success"></i>
                                        <a href="mailto:info@futureleaders.edu.pk">info@futureleaders.edu.pk</a>
                                    </li>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fab fa-whatsapp fa-fw me-3 text-success"></i>
                                        <a href="https://wa.me/923001234567" target="_blank">Chat on WhatsApp</a>
                                    </li>
                                </ul>
                                <h4 class="mt-4 mb-3">Follow Our Socials</h4>
                                <div>
                                    <a href="#" class="btn btn-outline-primary m-1" title="Facebook" style="width: 40px; height: 40px; line-height: 1;"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" class="btn btn-outline-info m-1" title="Twitter" style="width: 40px; height: 40px; line-height: 1;"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="btn btn-outline-danger m-1" title="Instagram" style="width: 40px; height: 40px; line-height: 1;"><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <h3 class="mb-3">Our Location in Bannu</h3>
                                <div class="ratio ratio-16x9 rounded overflow-hidden">
                                    <iframe src="https://maps.google.com/maps?q=Bannu%2C%20Khyber%20Pakhtunkhwa%2CPakistan&t=&z=13&ie=UTF8&iwloc=&output=embed" allowfullscreen="" loading="lazy"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php
                break;

            case 'gallery':
            ?>
                <section class="py-5">
                    <div class="container">
                        <h2 class="mb-4"><?php echo $t['gallery_images']; ?></h2>
                        <div class="row row-cols-1 row-cols-md-3 g-4">
                            <?php
                            $stmt = $pdo->query("SELECT * FROM gallery ORDER BY uploaded_at DESC");
                            $images = $stmt->fetchAll();
                            if ($images) {
                                foreach ($images as $image) {
                                    echo '<div class="col">';
                                    echo '  <a href="' . htmlspecialchars($image['image_path']) . '" class="glightbox" data-gallery="school-gallery" title="' . htmlspecialchars($image['title']) . '">';
                                    echo '    <div class="card h-100 text-decoration-none">';
                                    echo '      <img src="' . htmlspecialchars($image['image_path']) . '" class="card-img-top" alt="' . htmlspecialchars($image['title']) . '" style="height: 200px; object-fit: cover;">';
                                    echo '      <div class="card-body">';
                                    echo '        <h5 class="card-title text-dark">' . htmlspecialchars($image['title']) . '</h5>';
                                    echo '      </div>';
                                    echo '    </div>';
                                    echo '  </a>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-center">' . $t['no_records'] . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </section>
            <?php
                break;

            case 'login':
            ?>
                <section class="py-5">
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header text-center">
                                        <h3><?php echo $t['login_to_dashboard']; ?></h3>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="POST">
                                            <input type="hidden" name="action" value="login">
                                            <div class="mb-3">
                                                <label for="username" class="form-label"><?php echo $t['username']; ?></label>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="password" class="form-label"><?php echo $t['password']; ?></label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                            <?php if (isset($login_error)) : ?>
                                                <div class="alert alert-danger"><?php echo $login_error; ?></div>
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn-primary w-100"><?php echo $t['access_dashboard']; ?></button>
                                            <div class="mt-3 text-center">
                                                <a href="?page=forgot_password&lang=<?php echo $lang; ?>">Forgot Password?</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php
                break;

            case 'forgot_password':
            ?>
                <section class="py-5">
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header text-center">
                                        <h3><?php echo $t['password_reset']; ?></h3>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="POST">
                                            <input type="hidden" name="action" value="request_password_reset">
                                            <div class="mb-3">
                                                <label for="email" class="form-label"><?php echo $t['email_address']; ?></label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100"><?php echo $t['reset_password']; ?></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php
                break;

            case 'reset_password':
                $email = $_GET['email'] ?? '';
                $token = $_GET['token'] ?? '';
                // In a real application, you would validate the token from the database here
                // For this app, we assume the token is valid if present in the URL
            ?>
                <section class="py-5">
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header text-center">
                                        <h3><?php echo $t['reset_password']; ?></h3>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="POST">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label"><?php echo $t['new_password']; ?></label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="confirm_new_password" class="form-label"><?php echo $t['confirm_password']; ?></label>
                                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100"><?php echo $t['reset_password']; ?></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php
                break;

            case 'dashboard':
                if (!isAuthenticated()) {
                    header("Location: ?page=login&lang=$lang");
                    exit;
                }
            ?>
                <div class="row">
                    <div class="col-md-3 dashboard-sidebar">
                        <h4><?php echo $t['dashboard']; ?></h4>
                        <ul class="nav flex-column">
                            <?php if (isAdmin()) : ?>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'students' ? 'active' : ''); ?>" href="?page=dashboard&section=students&lang=<?php echo $lang; ?>"><i class="fas fa-user-graduate"></i> <?php echo $t['manage_students']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'teachers' ? 'active' : ''); ?>" href="?page=dashboard&section=teachers&lang=<?php echo $lang; ?>"><i class="fas fa-chalkboard-teacher"></i> <?php echo $t['manage_teachers']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'classes_subjects' ? 'active' : ''); ?>" href="?page=dashboard&section=classes_subjects&lang=<?php echo $lang; ?>"><i class="fas fa-school"></i> <?php echo $t['manage_classes_subjects']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'timetables' ? 'active' : ''); ?>" href="?page=dashboard&section=timetables&lang=<?php echo $lang; ?>"><i class="fas fa-calendar-alt"></i> <?php echo $t['manage_timetables']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'attendance' ? 'active' : ''); ?>" href="?page=dashboard&section=attendance&lang=<?php echo $lang; ?>"><i class="fas fa-check-circle"></i> <?php echo $t['manage_attendance']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'exams_marks' ? 'active' : ''); ?>" href="?page=dashboard&section=exams_marks&lang=<?php echo $lang; ?>"><i class="fas fa-clipboard-list"></i> <?php echo $t['manage_exams_marks']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'fees' ? 'active' : ''); ?>" href="?page=dashboard&section=fees&lang=<?php echo $lang; ?>"><i class="fas fa-money-bill-wave"></i> <?php echo $t['manage_fees']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'reports' ? 'active' : ''); ?>" href="?page=dashboard&section=reports&lang=<?php echo $lang; ?>"><i class="fas fa-chart-line"></i> <?php echo $t['reports']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'user_management' ? 'active' : ''); ?>" href="?page=dashboard&section=user_management&lang=<?php echo $lang; ?>"><i class="fas fa-users-cog"></i> <?php echo $t['user_management']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'admissions_list' ? 'active' : ''); ?>" href="?page=dashboard&section=admissions_list&lang=<?php echo $lang; ?>"><i class="fas fa-file-invoice"></i> Admission Applications</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'contact_messages' ? 'active' : ''); ?>" href="?page=dashboard&section=contact_messages&lang=<?php echo $lang; ?>"><i class="fas fa-envelope"></i> Contact Messages</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'announcements' ? 'active' : ''); ?>" href="?page=dashboard&section=announcements&lang=<?php echo $lang; ?>"><i class="fas fa-bullhorn"></i> Manage Announcements</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'events_admin' ? 'active' : ''); ?>" href="?page=dashboard&section=events_admin&lang=<?php echo $lang; ?>"><i class="fas fa-calendar-check"></i> Manage Events</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'gallery' ? 'active' : ''); ?>" href="?page=dashboard&section=gallery&lang=<?php echo $lang; ?>"><i class="fas fa-images"></i> Gallery Management</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'backup_restore' ? 'active' : ''); ?>" href="?page=dashboard&section=backup_restore&lang=<?php echo $lang; ?>"><i class="fas fa-database"></i> <?php echo $t['backup_restore']; ?></a></li>
                            <?php endif; ?>

                            <?php if (isTeacher()) : ?>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'attendance' ? 'active' : ''); ?>" href="?page=dashboard&section=attendance&lang=<?php echo $lang; ?>"><i class="fas fa-check-circle"></i> <?php echo $t['take_class_attendance']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'assignments' ? 'active' : ''); ?>" href="?page=dashboard&section=assignments&lang=<?php echo $lang; ?>"><i class="fas fa-book"></i> <?php echo $t['upload_assignments']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'exams_marks' ? 'active' : ''); ?>" href="?page=dashboard&section=exams_marks&lang=<?php echo $lang; ?>"><i class="fas fa-clipboard-list"></i> <?php echo $t['enter_exam_marks']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'timetable' ? 'active' : ''); ?>" href="?page=dashboard&section=timetable&lang=<?php echo $lang; ?>"><i class="fas fa-calendar-alt"></i> <?php echo $t['view_timetable']; ?></a></li>
                            <?php endif; ?>

                            <?php if (isStudent()) : ?>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'attendance' ? 'active' : ''); ?>" href="?page=dashboard&section=attendance&lang=<?php echo $lang; ?>"><i class="fas fa-check-circle"></i> <?php echo $t['view_attendance']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'timetable' ? 'active' : ''); ?>" href="?page=dashboard&section=timetable&lang=<?php echo $lang; ?>"><i class="fas fa-calendar-alt"></i> <?php echo $t['view_timetable']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'marks' ? 'active' : ''); ?>" href="?page=dashboard&section=marks&lang=<?php echo $lang; ?>"><i class="fas fa-chart-bar"></i> <?php echo $t['view_marks']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'assignments' ? 'active' : ''); ?>" href="?page=dashboard&section=assignments&lang=<?php echo $lang; ?>"><i class="fas fa-download"></i> <?php echo $t['download_assignments']; ?></a></li>
                            <?php endif; ?>

                            <?php if (isParent()) : ?>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'performance' ? 'active' : ''); ?>" href="?page=dashboard&section=performance&lang=<?php echo $lang; ?>"><i class="fas fa-chart-pie"></i> <?php echo $t['view_performance']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'fees' ? 'active' : ''); ?>" href="?page=dashboard&section=fees&lang=<?php echo $lang; ?>"><i class="fas fa-receipt"></i> <?php echo $t['view_student_fees']; ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-md-9 dashboard-content">
                        <?php
                        if (isAdmin()) {
                            displayAdminPanel($pdo, $t, $lang);
                        } elseif (isTeacher()) {
                            displayTeacherPanel($pdo, $t, $lang);
                        } elseif (isStudent()) {
                            displayStudentPanel($pdo, $t, $lang);
                        } elseif (isParent()) {
                            displayParentPanel($pdo, $t, $lang);
                        }
                        ?>
                    </div>
                </div>
        <?php
                break;
        }
        ?>
    </main>

    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <p>&copy; <?php echo date('Y'); ?> <?php echo $t['school_name']; ?>. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script>
        const lightbox = GLightbox({
            selector: '.glightbox'
        });
        $(document).ready(function() {
            function updateModalSelectOptions(modalId, selectId, dataUrl, initialValue) {
                var currentClassId = $('#' + modalId + ' #class_id').val();
                if (currentClassId) {
                    $.ajax({
                        url: dataUrl,
                        type: 'GET',
                        data: {
                            class_id: currentClassId
                        },
                        success: function(response) {
                            $('#' + modalId + ' #' + selectId).html(response);
                            if (initialValue) {
                                $('#' + modalId + ' #' + selectId).val(initialValue);
                            }
                        }
                    });
                } else {
                    $('#' + modalId + ' #' + selectId).html('<option value=""><?php echo $t['select_class_first']; ?></option>');
                }
            }


            $('#editTimetableEntryModal #class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('editTimetableEntryModal', 'subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });

            $('#editExamModal #class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('editExamModal', 'subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });

            $('#editAssignmentModal #class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('editAssignmentModal', 'subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });


            $(document).on('click', '.mark-paid-btn', function(e) {
                e.preventDefault();
                var feeId = $(this).data('id');
                if (confirm('Are you sure you want to mark this invoice as Paid?')) {
                    var form = $('<form action="" method="post">' +
                        '<input type="hidden" name="action" value="mark_fee_paid">' +
                        '<input type="hidden" name="id" value="' + feeId + '">' +
                        '</form>');
                    $('body').append(form);
                    form.submit();
                }
            });

            $(document).on('click', '.edit-btn', function() {
                var data = $(this).data('json');
                var formId = $(this).data('form-id');
                var modalId = $(this).data('bs-target');
                var type = $(this).data('type');

                $.each(data, function(key, value) {
                    var input = $(modalId + ' #' + key);
                    if (input.length > 0) {
                        if (input.is(':checkbox')) {
                            input.prop('checked', value == 1);
                        } else if (input.is(':radio')) {
                            input.filter('[value="' + value + '"]').prop('checked', true);
                        } else if (input.is('select') && (key === 'subject_id' || key === 'class_id' || key === 'teacher_id' || key === 'parent_id')) {
                            input.val(value);
                        } else {
                            input.val(value);
                        }
                    }
                });

                if (type === 'timetable_entry' || type === 'exam' || type === 'assignment') {
                    $(modalId + ' #class_id').val(data.class_id).trigger('change', [data.subject_id]);
                }

                $(modalId + ' #action').val('edit_' + type);
                $(modalId + ' #id').val(data.id);
                if (data.file_path) {
                    $(modalId + ' #file_path_old').val(data.file_path);
                }
                if (type === 'assignment' && data.file_path) {
                    $(modalId + ' #file_path_old').val(data.file_path);
                }

                $(modalId).modal('show');
            });


            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this record?')) {
                    var id = $(this).data('id');
                    var type = $(this).data('type');
                    var form = $('<form action="" method="post">' +
                        '<input type="hidden" name="action" value="delete_' + type + '">' +
                        '<input type="hidden" name="id" value="' + id + '">' +
                        '</form>');
                    $('body').append(form);
                    form.submit();
                }
            });


            $('#timetable_class_id, #add_assignment_class_id, #add_exam_class_id').change(function() {
                var classId = $(this).val();
                var targetSelectId = $(this).attr('id').replace('_class_id', '_subject_id');
                if (classId) {
                    $.ajax({
                        url: '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>',
                        type: 'GET',
                        data: {
                            class_id: classId
                        },
                        success: function(response) {
                            $('#' + targetSelectId).html(response);
                        }
                    });
                } else {
                    $('#' + targetSelectId).html('<option value=""><?php echo $t['select_subject']; ?></option>');
                }
            });


            $('#view_marks_class_id, #report_class_id_marks').change(function() {
                var classId = $(this).val();
                var targetSelectId = $(this).attr('id').replace('_class_id', '_student_id');
                if (classId) {
                    $.ajax({
                        url: '?page=ajax_data&type=students_by_class&lang=<?php echo $lang; ?>',
                        type: 'GET',
                        data: {
                            class_id: classId
                        },
                        success: function(response) {
                            $('#' + targetSelectId).html('<option value="">' + (targetSelectId.includes('report') ? 'All Students' : 'Select Student') + '</option>' + response);
                        }
                    });
                } else {
                    $('#' + targetSelectId).html('<option value="">' + (targetSelectId.includes('report') ? 'All Students' : 'Select Student') + '</option>');
                }
            });

            // Admission Details Modal (for view button)
            $(document).on("click", "[data-bs-target=\"#admissionDetailsModal\"]", function() {
                var data = $(this).data("json");
                $("#modal_admission_id").text(data.id);
                $("#modal_student_name").text(data.student_name);
                $("#modal_father_name").text(data.father_name);
                $("#modal_dob").text(data.dob);
                $("#modal_gender").text(data.gender);
                $("#modal_previous_school").text(data.previous_school);
                $("#modal_applying_for_class").text(data.applying_for_class);
                $("#modal_address").text(data.address);
                $("#modal_phone").text(data.phone);
                $("#modal_email").text(data.email);
                $("#modal_submission_date").text(new Date(data.submission_date).toLocaleDateString());
                $("#modal_status").text(data.status);
                $("#update_admission_id").val(data.id);
                $("#update_admission_status_select").val(data.status);
            });

            // Contact Details Modal (for view button)
            $(document).on("click", ".view-contact-btn", function() {
                var data = $(this).data("json");
                $("#contact_modal_id").text(data.id);
                $("#contact_modal_name").text(data.name);
                $("#contact_modal_email").text(data.email);
                $("#contact_modal_subject").text(data.subject);
                $("#contact_modal_message").text(data.message);
                $("#contact_modal_date").text(new Date(data.submission_date).toLocaleString());
                $("#update_contact_id").val(data.id);
                $("#update_contact_status_select").val(data.status);
            });
            // Countdown Timer Logic
            function initializeCountdown() {
                const timers = document.querySelectorAll('.countdown-timer');
                timers.forEach(timer => {
                    const eventDateTime = new Date(timer.dataset.datetime).getTime();
                    if (isNaN(eventDateTime)) {
                        timer.innerHTML = "Event time is not specified.";
                        return;
                    }

                    const interval = setInterval(function() {
                        const now = new Date().getTime();
                        const distance = eventDateTime - now;

                        if (distance < 0) {
                            clearInterval(interval);
                            timer.innerHTML = "<strong>This event has started!</strong>";
                            timer.classList.replace('alert-info', 'alert-success');
                            return;
                        }

                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        timer.innerHTML = `<strong>Time Remaining:</strong> ${days}d ${hours}h ${minutes}m ${seconds}s`;
                    }, 1000);
                });
            }
            initializeCountdown();
            // Password reset confirmation
            $('#createUserForm, #editUserForm').submit(function() {
                var password = $('#' + $(this).attr('id') + ' #password').val();
                var confirmPassword = $('#' + $(this).attr('id') + ' #confirm_password').val();
                if (password !== confirmPassword) {
                    alert('<?php echo $t['password_mismatch']; ?>');
                    return false;
                }
                return true;
            });
        });

        function printReport(reportId) {
            var printContent = document.getElementById(reportId).innerHTML;
            var originalContent = document.body.innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }
    </script>
</body>

</html>

<?php

// Function to display Admin Panel sections
function displayAdminPanel($pdo, $t, $lang)
{
    global $section; // Access the global $section variable
    switch ($section) {
        case 'students':
            echo '<h3>' . $t['student_list'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">' . $t['add_new_student'] . '</button>';
            $stmt = $pdo->query("SELECT s.*, c.name AS class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id");
            $students = $stmt->fetchAll();
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['name'] . '</th><th>' . $t['roll_no'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['phone_number'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            if ($students) {
                foreach ($students as $student) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($student['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['roll_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['class_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['phone']) . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editStudentModal" data-json=\'' . json_encode($student) . '\' data-form-id="editStudentForm" data-type="student"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $student['id'] . '" data-type="student"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Student Modal
            echo '<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addStudentForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addStudentModalLabel">' . $t['add_new_student'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_student">';
            echo '<div class="mb-3"><label for="add_student_name" class="form-label">' . $t['student_name'] . '</label><input type="text" class="form-control" id="add_student_name" name="name" required></div>';
            echo '<div class="mb-3"><label for="add_student_username" class="form-label">' . $t['username'] . '</label><input type="text" class="form-control" id="add_student_username" name="username" required></div>';
            echo '<div class="mb-3"><label for="add_student_password" class="form-label">' . $t['password'] . '</label><input type="password" class="form-control" id="add_student_password" name="password" required></div>';
            echo '<div class="mb-3"><label for="add_student_class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="add_student_class_id" name="class_id" required>';
            $classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_student_roll_no" class="form-label">' . $t['roll_no'] . '</label><input type="text" class="form-control" id="add_student_roll_no" name="roll_no" required></div>';
            echo '<div class="mb-3"><label for="add_student_dob" class="form-label">' . $t['date_of_birth'] . '</label><input type="date" class="form-control" id="add_student_dob" name="dob"></div>';
            echo '<div class="mb-3"><label for="add_student_address" class="form-label">' . $t['address'] . '</label><input type="text" class="form-control" id="add_student_address" name="address"></div>';
            echo '<div class="mb-3"><label for="add_student_phone" class="form-label">' . $t['phone_number'] . '</label><input type="text" class="form-control" id="add_student_phone" name="phone"></div>';
            echo '<div class="mb-3"><label for="add_student_parent_id" class="form-label">Parent (User ID)</label><select class="form-select" id="add_student_parent_id" name="parent_id"><option value="">None</option>';
            $parents = $pdo->query("SELECT id, username FROM users WHERE role = 'parent'")->fetchAll();
            foreach ($parents as $parent) {
                echo '<option value="' . $parent['id'] . '">' . htmlspecialchars($parent['username']) . '</option>';
            }
            echo '</select></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_student'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Student Modal
            echo '<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editStudentForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editStudentModalLabel">' . $t['edit_student_details'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_student">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="name" class="form-label">' . $t['student_name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
            echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="roll_no" class="form-label">' . $t['roll_no'] . '</label><input type="text" class="form-control" id="roll_no" name="roll_no" required></div>';
            echo '<div class="mb-3"><label for="dob" class="form-label">' . $t['date_of_birth'] . '</label><input type="date" class="form-control" id="dob" name="dob"></div>';
            echo '<div class="mb-3"><label for="address" class="form-label">' . $t['address'] . '</label><input type="text" class="form-control" id="address" name="address"></div>';
            echo '<div class="mb-3"><label for="phone" class="form-label">' . $t['phone_number'] . '</label><input type="text" class="form-control" id="phone" name="phone"></div>';
            echo '<div class="mb-3"><label for="parent_id" class="form-label">Parent (User ID)</label><select class="form-select" id="parent_id" name="parent_id"><option value="">None</option>';
            foreach ($parents as $parent) {
                echo '<option value="' . $parent['id'] . '">' . htmlspecialchars($parent['username']) . '</option>';
            }
            echo '</select></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'teachers':
            echo '<h3>' . $t['teacher_list'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addTeacherModal">' . $t['add_new_teacher'] . '</button>';
            $stmt = $pdo->query("SELECT * FROM teachers");
            $teachers = $stmt->fetchAll();
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['name'] . '</th><th>' . $t['subject_specialty'] . '</th><th>' . $t['phone_number'] . '</th><th>' . $t['email_address'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            if ($teachers) {
                foreach ($teachers as $teacher) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($teacher['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($teacher['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($teacher['subject_specialty']) . '</td>';
                    echo '<td>' . htmlspecialchars($teacher['phone']) . '</td>';
                    echo '<td>' . htmlspecialchars($teacher['email']) . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editTeacherModal" data-json=\'' . json_encode($teacher) . '\' data-form-id="editTeacherForm" data-type="teacher"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $teacher['id'] . '" data-type="teacher"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Teacher Modal
            echo '<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addTeacherForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addTeacherModalLabel">' . $t['add_new_teacher'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_teacher">';
            echo '<div class="mb-3"><label for="add_teacher_name" class="form-label">' . $t['name'] . '</label><input type="text" class="form-control" id="add_teacher_name" name="name" required></div>';
            echo '<div class="mb-3"><label for="add_teacher_username" class="form-label">' . $t['username'] . '</label><input type="text" class="form-control" id="add_teacher_username" name="username" required></div>';
            echo '<div class="mb-3"><label for="add_teacher_password" class="form-label">' . $t['password'] . '</label><input type="password" class="form-control" id="add_teacher_password" name="password" required></div>';
            echo '<div class="mb-3"><label for="add_teacher_subject_specialty" class="form-label">' . $t['subject_specialty'] . '</label><input type="text" class="form-control" id="add_teacher_subject_specialty" name="subject_specialty"></div>';
            echo '<div class="mb-3"><label for="add_teacher_phone" class="form-label">' . $t['phone_number'] . '</label><input type="text" class="form-control" id="add_teacher_phone" name="phone"></div>';
            echo '<div class="mb-3"><label for="add_teacher_email" class="form-label">' . $t['email_address'] . '</label><input type="email" class="form-control" id="add_teacher_email" name="email"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_teacher'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Teacher Modal
            echo '<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editTeacherForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editTeacherModalLabel">' . $t['edit_teacher_details'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_teacher">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="name" class="form-label">' . $t['name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
            echo '<div class="mb-3"><label for="subject_specialty" class="form-label">' . $t['subject_specialty'] . '</label><input type="text" class="form-control" id="subject_specialty" name="subject_specialty"></div>';
            echo '<div class="mb-3"><label for="phone" class="form-label">' . $t['phone_number'] . '</label><input type="text" class="form-control" id="phone" name="phone"></div>';
            echo '<div class="mb-3"><label for="email" class="form-label">' . $t['email_address'] . '</label><input type="email" class="form-control" id="email" name="email"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'classes_subjects':
            echo '<h3>' . $t['class_list'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3 me-2" data-bs-toggle="modal" data-bs-target="#addClassModal">' . $t['add_new_class'] . '</button>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">' . $t['add_new_subject'] . '</button>';
            echo '<div class="table-responsive mb-4">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['class_name'] . '</th><th>' . $t['assigned_teacher'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT c.*, t.name AS teacher_name FROM classes c LEFT JOIN teachers t ON c.teacher_id = t.id");
            $classes = $stmt->fetchAll();
            $all_teachers = $pdo->query("SELECT id, name FROM teachers")->fetchAll();
            foreach ($classes as $class) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($class['id']) . '</td>';
                echo '<td>' . htmlspecialchars($class['name']) . '</td>';
                echo '<td>' . htmlspecialchars($class['teacher_name'] ?? 'N/A') . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editClassModal" data-json=\'' . json_encode($class) . '\' data-form-id="editClassForm" data-type="class"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $class['id'] . '" data-type="class"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$classes) {
                echo '<tr><td colspan="4" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Class Modal
            echo '<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addClassForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addClassModalLabel">' . $t['add_new_class'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_class">';
            echo '<div class="mb-3"><label for="add_class_name" class="form-label">' . $t['class_name'] . '</label><input type="text" class="form-control" id="add_class_name" name="name" required></div>';
            echo '<div class="mb-3"><label for="add_class_teacher_id" class="form-label">' . $t['assigned_teacher'] . '</label><select class="form-select" id="add_class_teacher_id" name="teacher_id"><option value="">None</option>';
            foreach ($all_teachers as $teacher) {
                echo '<option value="' . $teacher['id'] . '">' . htmlspecialchars($teacher['name']) . '</option>';
            }
            echo '</select></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_class'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Class Modal
            echo '<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editClassForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editClassModalLabel">' . $t['edit_class_details'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_class">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="name" class="form-label">' . $t['class_name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
            echo '<div class="mb-3"><label for="teacher_id" class="form-label">' . $t['assigned_teacher'] . '</label><select class="form-select" id="teacher_id" name="teacher_id"><option value="">None</option>';
            foreach ($all_teachers as $teacher) {
                echo '<option value="' . $teacher['id'] . '">' . htmlspecialchars($teacher['name']) . '</option>';
            }
            echo '</select></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<h3>' . $t['subject_list'] . '</h3>';
            echo '<div class="table-responsive mb-4">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['name'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT * FROM subjects");
            $subjects = $stmt->fetchAll();
            foreach ($subjects as $subject) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($subject['id']) . '</td>';
                echo '<td>' . htmlspecialchars($subject['name']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editSubjectModal" data-json=\'' . json_encode($subject) . '\' data-form-id="editSubjectForm" data-type="subject"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $subject['id'] . '" data-type="subject"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$subjects) {
                echo '<tr><td colspan="3" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Subject Modal
            echo '<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addSubjectForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addSubjectModalLabel">' . $t['add_new_subject'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_subject">';
            echo '<div class="mb-3"><label for="add_subject_name" class="form-label">' . $t['name'] . '</label><input type="text" class="form-control" id="add_subject_name" name="name" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_subject'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Subject Modal
            echo '<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editSubjectForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editSubjectModalLabel">' . $t['edit_subject_details'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_subject">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="name" class="form-label">' . $t['name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<h3>Class-Subject Linkage</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addClassSubjectModal">Link Class & Subject</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>Class</th><th>Subject</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT cs.id, c.name AS class_name, s.name AS subject_name FROM class_subjects cs JOIN classes c ON cs.class_id = c.id JOIN subjects s ON cs.subject_id = s.id");
            $class_subjects = $stmt->fetchAll();
            foreach ($class_subjects as $link) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($link['id']) . '</td>';
                echo '<td>' . htmlspecialchars($link['class_name']) . '</td>';
                echo '<td>' . htmlspecialchars($link['subject_name']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $link['id'] . '" data-type="class_subject"><i class="fas fa-trash"></i> Delete</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$class_subjects) {
                echo '<tr><td colspan="4" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Class-Subject Linkage Modal
            echo '<div class="modal fade" id="addClassSubjectModal" tabindex="-1" aria-labelledby="addClassSubjectModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addClassSubjectForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addClassSubjectModalLabel">Link Class & Subject</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_class_subject">';
            echo '<div class="mb-3"><label for="add_class_subject_class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="add_class_subject_class_id" name="class_id" required>';
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_class_subject_subject_id" class="form-label">' . $t['subject_list'] . '</label><select class="form-select" id="add_class_subject_subject_id" name="subject_id" required>';
            foreach ($subjects as $subject) {
                echo '<option value="' . $subject['id'] . '">' . htmlspecialchars($subject['name']) . '</option>';
            }
            echo '</select></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">Link</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'timetables':
            echo '<h3>' . $t['manage_timetables'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addTimetableEntryModal">' . $t['add_timetable_entry'] . '</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['class_name'] . '</th><th>' . $t['subject'] . '</th><th>' . $t['assigned_teacher'] . '</th><th>' . $t['day_of_week'] . '</th><th>' . $t['start_time'] . '</th><th>' . $t['end_time'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT tt.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name FROM timetables tt JOIN classes c ON tt.class_id = c.id JOIN subjects s ON tt.subject_id = s.id JOIN teachers t ON tt.teacher_id = t.id ORDER BY day_of_week, start_time");
            $timetables = $stmt->fetchAll();
            $classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
            $all_teachers = $pdo->query("SELECT id, name FROM teachers")->fetchAll();
            $all_subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
            foreach ($timetables as $entry) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($entry['id']) . '</td>';
                echo '<td>' . htmlspecialchars($entry['class_name']) . '</td>';
                echo '<td>' . htmlspecialchars($entry['subject_name']) . '</td>';
                echo '<td>' . htmlspecialchars($entry['teacher_name']) . '</td>';
                echo '<td>' . htmlspecialchars($entry['day_of_week']) . '</td>';
                echo '<td>' . htmlspecialchars($entry['start_time']) . '</td>';
                echo '<td>' . htmlspecialchars($entry['end_time']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editTimetableEntryModal" data-json=\'' . json_encode($entry) . '\' data-form-id="editTimetableEntryForm" data-type="timetable_entry"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $entry['id'] . '" data-type="timetable_entry"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$timetables) {
                echo '<tr><td colspan="8" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Timetable Entry Modal
            echo '<div class="modal fade" id="addTimetableEntryModal" tabindex="-1" aria-labelledby="addTimetableEntryModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addTimetableEntryForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addTimetableEntryModalLabel">' . $t['add_timetable_entry'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_timetable_entry">';
            echo '<div class="mb-3"><label for="add_timetable_class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="add_timetable_class_id" name="class_id" required>';
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_timetable_subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="add_timetable_subject_id" name="subject_id" required>';
            if (!empty($classes)) {
                $default_class_id = $classes[0]['id'];
                $stmt_sub = $pdo->prepare("SELECT s.id, s.name FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.class_id = ?");
                $stmt_sub->execute([$default_class_id]);
                $class_subjects_options = $stmt_sub->fetchAll();
                foreach ($class_subjects_options as $sub) {
                    echo '<option value="' . $sub['id'] . '">' . htmlspecialchars($sub['name']) . '</option>';
                }
            } else {
                echo '<option value="">' . $t['select_class'] . ' first</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_timetable_teacher_id" class="form-label">' . $t['assigned_teacher'] . '</label><select class="form-select" id="add_timetable_teacher_id" name="teacher_id" required>';
            foreach ($all_teachers as $teacher) {
                echo '<option value="' . $teacher['id'] . '">' . htmlspecialchars($teacher['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_timetable_day_of_week" class="form-label">' . $t['day_of_week'] . '</label><select class="form-select" id="add_timetable_day_of_week" name="day_of_week" required>';
            $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days_of_week as $day) {
                echo '<option value="' . $day . '">' . $t[strtolower($day)] . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_timetable_start_time" class="form-label">' . $t['start_time'] . '</label><input type="time" class="form-control" id="add_timetable_start_time" name="start_time" required></div>';
            echo '<div class="mb-3"><label for="add_timetable_end_time" class="form-label">' . $t['end_time'] . '</label><input type="time" class="form-control" id="add_timetable_end_time" name="end_time" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_timetable_entry'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Timetable Entry Modal
            echo '<div class="modal fade" id="editTimetableEntryModal" tabindex="-1" aria-labelledby="editTimetableEntryModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editTimetableEntryForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editTimetableEntryModalLabel">' . $t['edit'] . ' ' . $t['timetable_entry'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_timetable_entry">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
            echo '<option value="">Select Class First</option>'; // Populated by JS
            echo '</select></div>';
            echo '<div class="mb-3"><label for="teacher_id" class="form-label">' . $t['assigned_teacher'] . '</label><select class="form-select" id="teacher_id" name="teacher_id" required>';
            foreach ($all_teachers as $teacher) {
                echo '<option value="' . $teacher['id'] . '">' . htmlspecialchars($teacher['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="day_of_week" class="form-label">' . $t['day_of_week'] . '</label><select class="form-select" id="day_of_week" name="day_of_week" required>';
            foreach ($days_of_week as $day) {
                echo '<option value="' . $day . '">' . $t[strtolower($day)] . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="start_time" class="form-label">' . $t['start_time'] . '</label><input type="time" class="form-control" id="start_time" name="start_time" required></div>';
            echo '<div class="mb-3"><label for="end_time" class="form-label">' . $t['end_time'] . '</label><input type="time" class="form-control" id="end_time" name="end_time" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'attendance':
            echo '<h3>' . $t['manage_attendance'] . '</h3>';
            echo '<h4>' . $t['take_attendance'] . '</h4>';
            echo '<form action="" method="POST" class="mb-4">';
            echo '<input type="hidden" name="action" value="save_attendance">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-4"><label for="attendance_class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="attendance_class_id" name="class_id" required>';
            echo '<option value="">' . $t['select_class'] . '</option>';
            $classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-4"><label for="attendance_subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="attendance_subject_id" name="subject_id">';
            echo '<option value="">' . $t['select_subject'] . '</option>';
            $subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
            foreach ($subjects as $subject) {
                echo '<option value="' . $subject['id'] . '">' . htmlspecialchars($subject['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-4"><label for="attendance_date" class="form-label">' . $t['select_date'] . '</label><input type="date" class="form-control" id="attendance_date" name="attendance_date" value="' . date('Y-m-d') . '" required></div>';
            echo '</div>';
            echo '<div id="student_attendance_list">';
            echo '<p>Select a class and date to take attendance.</p>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-primary d-none" id="save_attendance_btn">' . $t['save_attendance'] . '</button>';
            echo '</form>';


            echo '<script>';
            echo '$(document).ready(function() {';
            echo '    function loadStudentsForAttendance() {';
            echo '        var classId = $("#attendance_class_id").val();';
            echo '        var attendanceDate = $("#attendance_date").val();';
            echo '        var subjectId = $("#attendance_subject_id").val();';
            echo '        if (classId && attendanceDate) {';
            echo '            $.ajax({';
            echo '                url: "?page=ajax_data&type=attendance_students&lang=' . $lang . '",';
            echo '                type: "GET",';
            echo '                data: { class_id: classId, attendance_date: attendanceDate, subject_id: subjectId },';
            echo '                success: function(response) {';
            echo '                    $("#student_attendance_list").html(response);';
            echo '                    $("#save_attendance_btn").removeClass("d-none");';
            echo '                }';
            echo '            });';
            echo '        } else {';
            echo '            $("#student_attendance_list").html("<p>Select a class and date to take attendance.</p>");';
            echo '            $("#save_attendance_btn").addClass("d-none");';
            echo '        }';
            echo '    }';
            echo '    $("#attendance_class_id, #attendance_date, #attendance_subject_id").change(loadStudentsForAttendance);';
            echo '    loadStudentsForAttendance();';
            echo '});';
            echo '</script>';


            echo '<h4>' . $t['view_attendance'] . '</h4>';
            echo '<form method="GET" class="mb-4">';
            echo '<input type="hidden" name="page" value="dashboard">';
            echo '<input type="hidden" name="section" value="attendance">';
            echo '<input type="hidden" name="lang" value="' . $lang . '">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-4"><label for="view_attendance_class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="view_attendance_class_id" name="class_id">';
            echo '<option value="">All Classes</option>';
            foreach ($classes as $class) {
                $selected = (isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) ? 'selected' : '';
                echo '<option value="' . $class['id'] . '" ' . $selected . '>' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-4"><label for="view_attendance_start_date" class="form-label">From Date</label><input type="date" class="form-control" id="view_attendance_start_date" name="start_date" value="' . ($_GET['start_date'] ?? '') . '"></div>';
            echo '<div class="col-md-4"><label for="view_attendance_end_date" class="form-label">To Date</label><input type="date" class="form-control" id="view_attendance_end_date" name="end_date" value="' . ($_GET['end_date'] ?? '') . '"></div>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-info">' . $t['view_details'] . '</button>';
            echo '</form>';

            if (isset($_GET['class_id']) || (isset($_GET['start_date']) && isset($_GET['end_date']))) {
                $sql = "SELECT att.*, s.name AS student_name, c.name AS class_name, sub.name AS subject_name FROM attendance att JOIN students s ON att.student_id = s.id JOIN classes c ON att.class_id = c.id LEFT JOIN subjects sub ON att.subject_id = sub.id WHERE 1=1";
                $params = [];
                if (!empty($_GET['class_id'])) {
                    $sql .= " AND att.class_id = ?";
                    $params[] = $_GET['class_id'];
                }
                if (!empty($_GET['start_date'])) {
                    $sql .= " AND att.attendance_date >= ?";
                    $params[] = $_GET['start_date'];
                }
                if (!empty($_GET['end_date'])) {
                    $sql .= " AND att.attendance_date <= ?";
                    $params[] = $_GET['end_date'];
                }
                $sql .= " ORDER BY att.attendance_date DESC, student_name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $attendance_records = $stmt->fetchAll();

                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered table-striped mt-4">';
                echo '<thead><tr><th>Student</th><th>Class</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                if ($attendance_records) {
                    foreach ($attendance_records as $record) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($record['student_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['class_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['subject_name'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($record['attendance_date']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['status']) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center">' . $t['no_records'] . '</td></tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            }
            break;

        case 'exams_marks':
            echo '<h3>' . $t['manage_exams_marks'] . '</h3>';
            echo '<h4>Exam List</h4>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addExamModal">' . $t['add_new_exam'] . '</button>';
            echo '<div class="table-responsive mb-4">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['exam_name'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['subject'] . '</th><th>' . $t['exam_date'] . '</th><th>' . $t['max_marks'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT e.*, c.name AS class_name, s.name AS subject_name FROM exams e JOIN classes c ON e.class_id = c.id JOIN subjects s ON e.subject_id = s.id ORDER BY exam_date DESC");
            $exams = $stmt->fetchAll();
            $all_classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
            $all_subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
            foreach ($exams as $exam) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($exam['id']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['name']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['class_name']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['subject_name']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['exam_date']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['max_marks']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editExamModal" data-json=\'' . json_encode($exam) . '\' data-form-id="editExamForm" data-type="exam"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $exam['id'] . '" data-type="exam"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$exams) {
                echo '<tr><td colspan="7" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Exam Modal
            echo '<div class="modal fade" id="addExamModal" tabindex="-1" aria-labelledby="addExamModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addExamForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addExamModalLabel">' . $t['add_new_exam'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_exam">';
            echo '<div class="mb-3"><label for="add_exam_name" class="form-label">' . $t['exam_name'] . '</label><input type="text" class="form-control" id="add_exam_name" name="name" required></div>';
            echo '<div class="mb-3"><label for="add_exam_class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="add_exam_class_id" name="class_id" required>';
            foreach ($all_classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_exam_subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="add_exam_subject_id" name="subject_id" required>';
            if (!empty($all_classes)) {
                $default_class_id = $all_classes[0]['id'];
                $stmt_sub = $pdo->prepare("SELECT s.id, s.name FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.class_id = ?");
                $stmt_sub->execute([$default_class_id]);
                $class_subjects_options = $stmt_sub->fetchAll();
                foreach ($class_subjects_options as $sub) {
                    echo '<option value="' . $sub['id'] . '">' . htmlspecialchars($sub['name']) . '</option>';
                }
            } else {
                echo '<option value="">' . $t['select_class'] . ' first</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_exam_date" class="form-label">' . $t['exam_date'] . '</label><input type="date" class="form-control" id="add_exam_date" name="exam_date" required></div>';
            echo '<div class="mb-3"><label for="add_exam_max_marks" class="form-label">' . $t['max_marks'] . '</label><input type="number" class="form-control" id="add_exam_max_marks" name="max_marks" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_exam'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Exam Modal
            echo '<div class="modal fade" id="editExamModal" tabindex="-1" aria-labelledby="editExamModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editExamForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editExamModalLabel">' . $t['edit'] . ' ' . $t['exam_name'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_exam">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="name" class="form-label">' . $t['exam_name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
            echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
            foreach ($all_classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
            echo '<option value="">Select Class First</option>'; // Populated by JS
            echo '</select></div>';
            echo '<div class="mb-3"><label for="exam_date" class="form-label">' . $t['exam_date'] . '</label><input type="date" class="form-control" id="exam_date" name="exam_date" required></div>';
            echo '<div class="mb-3"><label for="max_marks" class="form-label">' . $t['max_marks'] . '</label><input type="number" class="form-control" id="max_marks" name="max_marks" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<h4>' . $t['enter_marks'] . '</h4>';
            echo '<form action="" method="POST" class="mb-4">';
            echo '<input type="hidden" name="action" value="save_marks">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><label for="marks_exam_id" class="form-label">' . $t['select_exam'] . '</label><select class="form-select" id="marks_exam_id" name="exam_id" required>';
            echo '<option value="">' . $t['select_exam'] . '</option>';
            foreach ($exams as $exam) {
                echo '<option value="' . $exam['id'] . '">' . htmlspecialchars($exam['name'] . ' - ' . $exam['class_name'] . ' - ' . $exam['subject_name']) . '</option>';
            }
            echo '</select></div>';
            echo '</div>';
            echo '<div id="student_marks_list">';
            echo '<p>Select an exam to enter marks.</p>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-primary d-none" id="save_marks_btn">' . $t['save_marks'] . '</button>';
            echo '</form>';


            echo '<script>';
            echo '$(document).ready(function() {';
            echo '    function loadStudentsForMarks() {';
            echo '        var examId = $("#marks_exam_id").val();';
            echo '        if (examId) {';
            echo '            $.ajax({';
            echo '                url: "?page=ajax_data&type=marks_students&lang=' . $lang . '",';
            echo '                type: "GET",';
            echo '                data: { exam_id: examId },';
            echo '                success: function(response) {';
            echo '                    $("#student_marks_list").html(response);';
            echo '                    $("#save_marks_btn").removeClass("d-none");';
            echo '                }';
            echo '            });';
            echo '        } else {';
            echo '            $("#student_marks_list").html("<p>Select an exam to enter marks.</p>");';
            echo '            $("#save_marks_btn").addClass("d-none");';
            echo '        }';
            echo '    }';
            echo '    $("#marks_exam_id").change(loadStudentsForMarks);';
            echo '    loadStudentsForMarks();';
            echo '});';
            echo '</script>';


            echo '<h4>' . $t['view_marks'] . '</h4>';
            echo '<form method="GET" class="mb-4">';
            echo '<input type="hidden" name="page" value="dashboard">';
            echo '<input type="hidden" name="section" value="exams_marks">';
            echo '<input type="hidden" name="lang" value="' . $lang . '">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><label for="view_marks_class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="view_marks_class_id" name="class_id">';
            echo '<option value="">All Classes</option>';
            foreach ($all_classes as $class) {
                $selected = (isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) ? 'selected' : '';
                echo '<option value="' . $class['id'] . '" ' . $selected . '>' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-6"><label for="view_marks_student_id" class="form-label">Select Student</label><select class="form-select" id="view_marks_student_id" name="student_id">';
            echo '<option value="">All Students</option>';
            if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
                $stmt_s = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? ORDER BY name");
                $stmt_s->execute([$_GET['class_id']]);
                $students_in_class = $stmt_s->fetchAll();
                foreach ($students_in_class as $s) {
                    $selected = (isset($_GET['student_id']) && $_GET['student_id'] == $s['id']) ? 'selected' : '';
                    echo '<option value="' . $s['id'] . '" ' . $selected . '>' . htmlspecialchars($s['name']) . '</option>';
                }
            }
            echo '</select></div>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-info">' . $t['view_details'] . '</button>';
            echo '</form>';

            if (isset($_GET['class_id']) || isset($_GET['student_id'])) {
                $sql = "SELECT m.*, s.name AS student_name, e.name AS exam_name, e.max_marks, c.name AS class_name, sub.name AS subject_name FROM marks m JOIN students s ON m.student_id = s.id JOIN exams e ON m.exam_id = e.id JOIN classes c ON e.class_id = c.id JOIN subjects sub ON e.subject_id = sub.id WHERE 1=1";
                $params = [];
                if (!empty($_GET['class_id'])) {
                    $sql .= " AND e.class_id = ?";
                    $params[] = $_GET['class_id'];
                }
                if (!empty($_GET['student_id'])) {
                    $sql .= " AND m.student_id = ?";
                    $params[] = $_GET['student_id'];
                }
                $sql .= " ORDER BY student_name, exam_name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $marks_records = $stmt->fetchAll();

                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered table-striped mt-4">';
                echo '<thead><tr><th>Student</th><th>Class</th><th>Exam</th><th>Subject</th><th>' . $t['marks_obtained'] . '</th><th>' . $t['max_marks'] . '</th></tr></thead>';
                echo '<tbody>';
                if ($marks_records) {
                    foreach ($marks_records as $record) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($record['student_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['class_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['exam_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['subject_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['marks_obtained']) . '</td>';
                        echo '<td>' . htmlspecialchars($record['max_marks']) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            }
            break;

        case 'fees':
            echo '<h3>' . $t['manage_fees'] . '</h3>';
            echo '<h4>' . $t['fee_invoices'] . '</h4>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addFeeInvoiceModal">' . $t['add_new_invoice'] . '</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>Student</th><th>' . $t['amount'] . '</th><th>' . $t['due_date_invoice'] . '</th><th>' . $t['paid_date'] . '</th><th>' . $t['invoice_status'] . '</th><th>Description</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT f.*, s.name AS student_name FROM fees f JOIN students s ON f.student_id = s.id ORDER BY due_date ASC");
            $fees = $stmt->fetchAll();
            $all_students = $pdo->query("SELECT id, name FROM students")->fetchAll();
            foreach ($fees as $fee) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($fee['id']) . '</td>';
                echo '<td>' . htmlspecialchars($fee['student_name']) . '</td>';
                echo '<td>' . htmlspecialchars($fee['amount']) . '</td>';
                echo '<td>' . htmlspecialchars($fee['due_date']) . '</td>';
                echo '<td>' . htmlspecialchars($fee['paid_date'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($fee['status']) . '</td>';
                echo '<td>' . htmlspecialchars($fee['description']) . '</td>';
                echo '<td>';
                if ($fee['status'] != 'Paid') {
                    echo '<button type="button" class="btn btn-sm btn-success mark-paid-btn me-1" data-id="' . $fee['id'] . '"><i class="fas fa-money-check-alt"></i> ' . $t['mark_as_paid'] . '</button>';
                }
                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editFeeInvoiceModal" data-json=\'' . json_encode($fee) . '\' data-form-id="editFeeInvoiceForm" data-type="fee_invoice"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $fee['id'] . '" data-type="fee_invoice"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$fees) {
                echo '<tr><td colspan="8" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Fee Invoice Modal
            echo '<div class="modal fade" id="addFeeInvoiceModal" tabindex="-1" aria-labelledby="addFeeInvoiceModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addFeeInvoiceForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addFeeInvoiceModalLabel">' . $t['add_new_invoice'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_fee_invoice">';
            echo '<div class="mb-3"><label for="add_fee_student_id" class="form-label">Student</label><select class="form-select" id="add_fee_student_id" name="student_id" required>';
            foreach ($all_students as $student) {
                echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_fee_amount" class="form-label">' . $t['amount'] . '</label><input type="number" step="0.01" class="form-control" id="add_fee_amount" name="amount" required></div>';
            echo '<div class="mb-3"><label for="add_fee_due_date" class="form-label">' . $t['due_date_invoice'] . '</label><input type="date" class="form-control" id="add_fee_due_date" name="due_date" required></div>';
            echo '<div class="mb-3"><label for="add_fee_status" class="form-label">' . $t['invoice_status'] . '</label><select class="form-select" id="add_fee_status" name="status" required><option value="Unpaid">Unpaid</option><option value="Paid">Paid</option><option value="Partially Paid">Partially Paid</option></select></div>';
            echo '<div class="mb-3"><label for="add_fee_description" class="form-label">Description</label><textarea class="form-control" id="add_fee_description" name="description"></textarea></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_invoice'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Fee Invoice Modal
            echo '<div class="modal fade" id="editFeeInvoiceModal" tabindex="-1" aria-labelledby="editFeeInvoiceModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editFeeInvoiceForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editFeeInvoiceModalLabel">' . $t['edit'] . ' ' . $t['fee_invoices'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_fee_invoice">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="student_id" class="form-label">Student</label><select class="form-select" id="student_id" name="student_id" required>';
            foreach ($all_students as $student) {
                echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="amount" class="form-label">' . $t['amount'] . '</label><input type="number" step="0.01" class="form-control" id="amount" name="amount" required></div>';
            echo '<div class="mb-3"><label for="due_date" class="form-label">' . $t['due_date_invoice'] . '</label><input type="date" class="form-control" id="due_date" name="due_date" required></div>';
            echo '<div class="mb-3"><label for="status" class="form-label">' . $t['invoice_status'] . '</label><select class="form-select" id="status" name="status" required><option value="Unpaid">Unpaid</option><option value="Paid">Paid</option><option value="Partially Paid">Partially Paid</option></select></div>';
            echo '<div class="mb-3"><label for="paid_date" class="form-label">' . $t['paid_date'] . '</label><input type="date" class="form-control" id="paid_date" name="paid_date"></div>';
            echo '<div class="mb-3"><label for="description" class="form-label">Description</label><textarea class="form-control" id="description" name="description"></textarea></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'reports':
            echo '<h3>' . $t['reports'] . '</h3>';

            echo '<h4>' . $t['attendance_report'] . '</h4>';
            echo '<form method="GET" class="mb-3" action="">';
            echo '<input type="hidden" name="page" value="dashboard">';
            echo '<input type="hidden" name="section" value="reports">';
            echo '<input type="hidden" name="report_type" value="attendance">';
            echo '<input type="hidden" name="lang" value="' . $lang . '">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-4"><label for="report_class_id_att" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="report_class_id_att" name="class_id">';
            echo '<option value="">All Classes</option>';
            $classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
            foreach ($classes as $class) {
                $selected = (isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) ? 'selected' : '';
                echo '<option value="' . $class['id'] . '" ' . $selected . '>' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-4"><label for="report_start_date_att" class="form-label">From Date</label><input type="date" class="form-control" id="report_start_date_att" name="start_date" value="' . ($_GET['start_date'] ?? '') . '"></div>';
            echo '<div class="col-md-4"><label for="report_end_date_att" class="form-label">To Date</label><input type="date" class="form-control" id="report_end_date_att" name="end_date" value="' . ($_GET['end_date'] ?? '') . '"></div>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-info">' . $t['generate_report'] . '</button>';
            if (isset($_GET['report_type']) && $_GET['report_type'] == 'attendance' && (isset($_GET['class_id']) || (isset($_GET['start_date']) && isset($_GET['end_date'])))) {
                $sql = "SELECT att.attendance_date, s.name AS student_name, att.status, c.name AS class_name, sub.name AS subject_name FROM attendance att JOIN students s ON att.student_id = s.id JOIN classes c ON att.class_id = c.id LEFT JOIN subjects sub ON att.subject_id = sub.id WHERE 1=1";
                $params = [];
                if (!empty($_GET['class_id'])) {
                    $sql .= " AND att.class_id = ?";
                    $params[] = $_GET['class_id'];
                }
                if (!empty($_GET['start_date'])) {
                    $sql .= " AND att.attendance_date >= ?";
                    $params[] = $_GET['start_date'];
                }
                if (!empty($_GET['end_date'])) {
                    $sql .= " AND att.attendance_date <= ?";
                    $params[] = $_GET['end_date'];
                }
                $sql .= " ORDER BY att.attendance_date DESC, student_name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $report_data_att = $stmt->fetchAll();

                echo '<div id="attendanceReportTable" class="mt-4">';
                echo '<h5>Attendance Report for ' . ($report_data_att[0]['class_name'] ?? 'All Classes') . ' (' . ($_GET['start_date'] ?? 'N/A') . ' to ' . ($_GET['end_date'] ?? 'N/A') . ')</h5>';
                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered table-striped">';
                echo '<thead><tr><th>Student</th><th>Class</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                if ($report_data_att) {
                    foreach ($report_data_att as $row) {
                        echo '<tr><td>' . htmlspecialchars($row['student_name']) . '</td><td>' . htmlspecialchars($row['class_name']) . '</td><td>' . htmlspecialchars($row['subject_name'] ?? 'N/A') . '</td><td>' . htmlspecialchars($row['attendance_date']) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center">' . $t['no_records'] . '</td></tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
                echo '<button onclick="printReport(\'attendanceReportTable\')" class="btn btn-secondary mt-3"><i class="fas fa-print"></i> Print Report</button>';
                echo '</div>';
            }

            echo '<h4 class="mt-5">' . $t['marks_report'] . '</h4>';
            echo '<form method="GET" class="mb-3" action="">';
            echo '<input type="hidden" name="page" value="dashboard">';
            echo '<input type="hidden" name="section" value="reports">';
            echo '<input type="hidden" name="report_type" value="marks">';
            echo '<input type="hidden" name="lang" value="' . $lang . '">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><label for="report_class_id_marks" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="report_class_id_marks" name="class_id">';
            echo '<option value="">All Classes</option>';
            foreach ($classes as $class) {
                $selected = (isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) ? 'selected' : '';
                echo '<option value="' . $class['id'] . '" ' . $selected . '>' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-6"><label for="report_student_id_marks" class="form-label">Select Student</label><select class="form-select" id="report_student_id_marks" name="student_id">';
            echo '<option value="">All Students</option>'; // Populated by JS
            if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
                $stmt_s = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? ORDER BY name");
                $stmt_s->execute([$_GET['class_id']]);
                $students_in_class = $stmt_s->fetchAll();
                foreach ($students_in_class as $s) {
                    $selected = (isset($_GET['student_id']) && $_GET['student_id'] == $s['id']) ? 'selected' : '';
                    echo '<option value="' . $s['id'] . '" ' . $selected . '>' . htmlspecialchars($s['name']) . '</option>';
                }
            }
            echo '</select></div>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-info">' . $t['generate_report'] . '</button>';
            if (isset($_GET['report_type']) && $_GET['report_type'] == 'marks' && (isset($_GET['class_id']) || isset($_GET['student_id']))) {
                $sql = "SELECT m.*, s.name AS student_name, e.name AS exam_name, e.max_marks, c.name AS class_name, sub.name AS subject_name FROM marks m JOIN students s ON m.student_id = s.id JOIN exams e ON m.exam_id = e.id JOIN classes c ON e.class_id = c.id JOIN subjects sub ON e.subject_id = sub.id WHERE 1=1";
                $params = [];
                if (!empty($_GET['class_id'])) {
                    $sql .= " AND e.class_id = ?";
                    $params[] = $_GET['class_id'];
                }
                if (!empty($_GET['student_id'])) {
                    $sql .= " AND m.student_id = ?";
                    $params[] = $_GET['student_id'];
                }
                $sql .= " ORDER BY student_name, exam_name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $report_data_marks = $stmt->fetchAll();

                echo '<div id="marksReportTable" class="mt-4">';
                echo '<h5>Marks Report for ' . ($report_data_marks[0]['student_name'] ?? ($report_data_marks[0]['class_name'] ?? 'All Students/Classes')) . '</h5>';
                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered table-striped">';
                echo '<thead><tr><th>Student</th><th>Class</th><th>Exam</th><th>Subject</th><th>' . $t['marks_obtained'] . '</th><th>' . $t['max_marks'] . '</th></tr></thead>';
                echo '<tbody>';
                if ($report_data_marks) {
                    foreach ($report_data_marks as $row) {
                        echo '<tr><td>' . htmlspecialchars($row['student_name']) . '</td><td>' . htmlspecialchars($row['class_name']) . '</td><td>' . htmlspecialchars($row['exam_name']) . '</td><td>' . htmlspecialchars($row['subject_name']) . '</td><td>' . htmlspecialchars($row['marks_obtained']) . '</td><td>' . htmlspecialchars($row['max_marks']) . '</td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
                echo '<button onclick="printReport(\'marksReportTable\')" class="btn btn-secondary mt-3"><i class="fas fa-print"></i> Print Report</button>';
                echo '</div>';
            }

            echo '<h4 class="mt-5">' . $t['fee_report'] . '</h4>';
            echo '<form method="GET" class="mb-3" action="">';
            echo '<input type="hidden" name="page" value="dashboard">';
            echo '<input type="hidden" name="section" value="reports">';
            echo '<input type="hidden" name="report_type" value="fees">';
            echo '<input type="hidden" name="lang" value="' . $lang . '">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-4"><label for="report_student_id_fees" class="form-label">Student</label><select class="form-select" id="report_student_id_fees" name="student_id">';
            echo '<option value="">All Students</option>';
            $all_students = $pdo->query("SELECT id, name FROM students")->fetchAll();
            foreach ($all_students as $student) {
                $selected = (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : '';
                echo '<option value="' . $student['id'] . '" ' . $selected . '>' . htmlspecialchars($student['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-4"><label for="report_fee_status" class="form-label">' . $t['invoice_status'] . '</label><select class="form-select" id="report_fee_status" name="status">';
            echo '<option value="">All Statuses</option>';
            echo '<option value="Paid" ' . ((isset($_GET['status']) && $_GET['status'] == 'Paid') ? 'selected' : '') . '>Paid</option>';
            echo '<option value="Unpaid" ' . ((isset($_GET['status']) && $_GET['status'] == 'Unpaid') ? 'selected' : '') . '>Unpaid</option>';
            echo '<option value="Partially Paid" ' . ((isset($_GET['status']) && $_GET['status'] == 'Partially Paid') ? 'selected' : '') . '>Partially Paid</option>';
            echo '</select></div>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-info">' . $t['generate_report'] . '</button>';
            if (isset($_GET['report_type']) && $_GET['report_type'] == 'fees' && (isset($_GET['student_id']) || isset($_GET['status']))) {
                $sql = "SELECT f.*, s.name AS student_name FROM fees f JOIN students s ON f.student_id = s.id WHERE 1=1";
                $params = [];
                if (!empty($_GET['student_id'])) {
                    $sql .= " AND f.student_id = ?";
                    $params[] = $_GET['student_id'];
                }
                if (!empty($_GET['status'])) {
                    $sql .= " AND f.status = ?";
                    $params[] = $_GET['status'];
                }
                $sql .= " ORDER BY f.due_date ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $report_data_fees = $stmt->fetchAll();

                echo '<div id="feeReportTable" class="mt-4">';
                echo '<h5>Fee Report ' . (!empty($_GET['student_id']) ? 'for ' . ($report_data_fees[0]['student_name'] ?? '') : '') . ' (' . (!empty($_GET['status']) ? $_GET['status'] : 'All Statuses') . ')</h5>';
                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered table-striped">';
                echo '<thead><tr><th>Student</th><th>' . $t['amount'] . '</th><th>' . $t['due_date_invoice'] . '</th><th>' . $t['paid_date'] . '</th><th>' . $t['invoice_status'] . '</th><th>Description</th></tr></thead>';
                echo '<tbody>';
                if ($report_data_fees) {
                    foreach ($report_data_fees as $row) {
                        echo '<tr><td>' . htmlspecialchars($row['student_name']) . '</td><td>' . htmlspecialchars($row['amount']) . '</td><td>' . htmlspecialchars($row['due_date']) . '</td><td>' . htmlspecialchars($row['paid_date'] ?? 'N/A') . '</td><td>' . htmlspecialchars($row['status']) . '</td><td>' . htmlspecialchars($row['description']) . '</td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
                echo '<button onclick="printReport(\'feeReportTable\')" class="btn btn-secondary mt-3"><i class="fas fa-print"></i> Print Report</button>';
                echo '</div>';
            }
            break;

        case 'user_management':
            echo '<h3>' . $t['user_management'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createUserModal">' . $t['create_user_account'] . '</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['username'] . '</th><th>' . $t['role'] . '</th><th>' . $t['email_address'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT id, username, role, email FROM users ORDER BY role, username");
            $users = $stmt->fetchAll();
            foreach ($users as $user) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editUserModal" data-json=\'' . json_encode($user) . '\' data-form-id="editUserForm" data-type="user_account"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $user['id'] . '" data-type="user_account"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$users) {
                echo '<tr><td colspan="5" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Create User Modal
            echo '<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="createUserForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="createUserModalLabel">' . $t['create_user_account'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="create_user_account">';
            echo '<div class="mb-3"><label for="create_username" class="form-label">' . $t['username'] . '</label><input type="text" class="form-control" id="create_username" name="username" required></div>';
            echo '<div class="mb-3"><label for="create_password" class="form-label">' . $t['password'] . '</label><input type="password" class="form-control" id="create_password" name="password" required></div>';
            echo '<div class="mb-3"><label for="create_confirm_password" class="form-label">' . $t['confirm_password'] . '</label><input type="password" class="form-control" id="create_confirm_password" name="confirm_password" required></div>';
            echo '<div class="mb-3"><label for="create_role" class="form-label">' . $t['role'] . '</label><select class="form-select" id="create_role" name="role" required><option value="admin">Admin</option><option value="teacher">Teacher</option><option value="student">Student</option><option value="parent">Parent</option></select></div>';
            echo '<div class="mb-3"><label for="create_email" class="form-label">' . $t['email_address'] . '</label><input type="email" class="form-control" id="create_email" name="email"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['create_user_account'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit User Modal
            echo '<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editUserForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editUserModalLabel">' . $t['edit'] . ' User Account</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_user_account">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="username" class="form-label">' . $t['username'] . '</label><input type="text" class="form-control" id="username" name="username" required></div>';
            echo '<div class="mb-3"><label for="password" class="form-label">New Password (Leave blank to keep current)</label><input type="password" class="form-control" id="password" name="password"></div>';
            echo '<div class="mb-3"><label for="confirm_password" class="form-label">Confirm New Password</label><input type="password" class="form-control" id="confirm_password" name="confirm_password"></div>';
            echo '<div class="mb-3"><label for="role" class="form-label">' . $t['role'] . '</label><select class="form-select" id="role" name="role" required><option value="admin">Admin</option><option value="teacher">Teacher</option><option value="student">Student</option><option value="parent">Parent</option></select></div>';
            echo '<div class="mb-3"><label for="email" class="form-label">' . $t['email_address'] . '</label><input type="email" class="form-control" id="email" name="email"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'admissions_list':
            echo '<h3>Admission Applications</h3>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>Student Name</th><th>Father Name</th><th>Class</th><th>Phone</th><th>Email</th><th>Status</th><th>Submission Date</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT * FROM admissions ORDER BY submission_date DESC");
            $admissions = $stmt->fetchAll();
            if ($admissions) {
                foreach ($admissions as $admission) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($admission['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($admission['student_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($admission['father_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($admission['applying_for_class']) . '</td>';
                    echo '<td>' . htmlspecialchars($admission['phone']) . '</td>';
                    echo '<td>' . htmlspecialchars($admission['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($admission['status']) . '</td>';
                    echo '<td>' . date("Y-m-d", strtotime($admission['submission_date'])) . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info me-1" data-bs-toggle="modal" data-bs-target="#admissionDetailsModal" data-json=\'' . json_encode($admission) . '\'>' . $t['view'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="9" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Admission Details & Update Status Modal
            echo '<div class="modal fade" id="admissionDetailsModal" tabindex="-1" aria-labelledby="admissionDetailsModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="admissionDetailsModalLabel">Admission Application Details</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<strong>ID:</strong> <span id="modal_admission_id"></span><br>';
            echo '<strong>Student Name:</strong> <span id="modal_student_name"></span><br>';
            echo '<strong>Father\'s Name:</strong> <span id="modal_father_name"></span><br>';
            echo '<strong>DOB:</strong> <span id="modal_dob"></span><br>';
            echo '<strong>Gender:</strong> <span id="modal_gender"></span><br>';
            echo '<strong>Previous School:</strong> <span id="modal_previous_school"></span><br>';
            echo '<strong>Applying for Class:</strong> <span id="modal_applying_for_class"></span><br>';
            echo '<strong>Address:</strong> <span id="modal_address"></span><br>';
            echo '<strong>Phone:</strong> <span id="modal_phone"></span><br>';
            echo '<strong>Email:</strong> <span id="modal_email"></span><br>';
            echo '<strong>Submission Date:</strong> <span id="modal_submission_date"></span><br>';
            echo '<strong>Current Status:</strong> <span id="modal_status"></span><br>';

            echo '<form action="" method="POST" class="mt-3">';
            echo '<input type="hidden" name="action" value="update_admission_status">';
            echo '<input type="hidden" name="id" id="update_admission_id">';
            echo '<div class="mb-3">';
            echo '<label for="update_admission_status_select" class="form-label">' . $t['update_status'] . '</label>';
            echo '<select class="form-select" id="update_admission_status_select" name="status">';
            echo '<option value="Pending">Pending</option>';
            echo '<option value="Approved">Approved</option>';
            echo '<option value="Rejected">Rejected</option>';
            echo '</select>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</form>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'contact_messages':
            echo '<h3>Contact Messages</h3>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Submission Date</th><th>Status</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT * FROM contacts ORDER BY submission_date DESC");
            $contacts = $stmt->fetchAll();
            if ($contacts) {
                foreach ($contacts as $contact) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($contact['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($contact['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($contact['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($contact['subject']) . '</td>';
                    echo '<td>' . substr(htmlspecialchars($contact['message']), 0, 50) . '...</td>';
                    echo '<td>' . date("Y-m-d H:i", strtotime($contact['submission_date'])) . '</td>';
                    echo '<td>' . htmlspecialchars($contact['status']) . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info view-contact-btn me-1" data-bs-toggle="modal" data-bs-target="#viewContactModal" data-json=\'' . json_encode($contact) . '\'>' . $t['view'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // View Contact Modal
            echo '<div class="modal fade" id="viewContactModal" tabindex="-1" aria-labelledby="viewContactModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="viewContactModalLabel">Contact Message Details</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<strong>ID:</strong> <span id="contact_modal_id"></span><br>';
            echo '<strong>Name:</strong> <span id="contact_modal_name"></span><br>';
            echo '<strong>Email:</strong> <span id="contact_modal_email"></span><br>';
            echo '<strong>Subject:</strong> <span id="contact_modal_subject"></span><br>';
            echo '<strong>Message:</strong> <p id="contact_modal_message" class="border p-2 mt-2"></p>';
            echo '<strong>Submission Date:</strong> <span id="contact_modal_date"></span><br>';
            echo '<strong>Current Status:</strong> <span id="contact_modal_status"></span><br>';

            echo '<form action="" method="POST" class="mt-3">';
            echo '<input type="hidden" name="action" value="update_contact_status">';
            echo '<input type="hidden" name="id" id="update_contact_id">';
            echo '<div class="mb-3">';
            echo '<label for="update_contact_status_select" class="form-label">' . $t['update_status'] . '</label>';
            echo '<select class="form-select" id="update_contact_status_select" name="status">';
            echo '<option value="New">New</option>';
            echo '<option value="Replied">Replied</option>';
            echo '</select>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</form>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'announcements':
            echo '<h3>Manage Announcements</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">' . $t['add_news'] . '</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['title'] . '</th><th>Content</th><th>File</th><th>' . $t['published_date'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT * FROM announcements ORDER BY published_date DESC");
            $announcements = $stmt->fetchAll();
            if ($announcements) {
                foreach ($announcements as $announcement) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($announcement['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($announcement['title']) . '</td>';
                    echo '<td>' . substr(htmlspecialchars($announcement['content']), 0, 100) . '...</td>';
                    echo '<td>';
                    if (!empty($announcement['file_path'])) {
                        echo '<a href="' . htmlspecialchars($announcement['file_path']) . '" download>Download</a>';
                    } else {
                        echo 'N/A';
                    }
                    echo '</td>';
                    echo '<td>' . date("Y-m-d", strtotime($announcement['published_date'])) . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal" data-json=\'' . json_encode($announcement) . '\' data-form-id="editAnnouncementForm" data-type="announcement"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $announcement['id'] . '" data-type="announcement"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Announcement Modal
            echo '<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addAnnouncementForm" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addAnnouncementModalLabel">' . $t['add_news'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_announcement">';
            echo '<div class="mb-3"><label for="add_announcement_title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="add_announcement_title" name="title" required></div>';
            echo '<div class="mb-3"><label for="add_announcement_content" class="form-label">Content</label><textarea class="form-control" id="add_announcement_content" name="content" rows="5" required></textarea></div>';
            echo '<div class="mb-3"><label for="add_announcement_file" class="form-label">Attachment File</label><input type="file" class="form-control" id="add_announcement_file" name="announcement_file"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_news'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Announcement Modal
            echo '<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editAnnouncementForm" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editAnnouncementModalLabel">' . $t['edit_news'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_announcement">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="title" name="title" required></div>';
            echo '<div class="mb-3"><label for="content" class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="5" required></textarea></div>';
            echo '<input type="hidden" name="file_path_old" id="file_path_old">';
            echo '<div class="mb-3"><label for="announcement_file" class="form-label">New Attachment (optional)</label><input type="file" class="form-control" id="announcement_file" name="announcement_file"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'events_admin':
            echo '<h3>Manage Events</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addEventModal">' . $t['add_event'] . '</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['title'] . '</th><th>Description</th><th>' . $t['event_date'] . '</th><th>' . $t['event_time'] . '</th><th>' . $t['location'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
            $events = $stmt->fetchAll();
            if ($events) {
                foreach ($events as $event) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($event['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($event['title']) . '</td>';
                    echo '<td>' . substr(htmlspecialchars($event['description']), 0, 100) . '...</td>';
                    echo '<td>' . date("Y-m-d", strtotime($event['event_date'])) . '</td>';
                    echo '<td>' . date("h:i A", strtotime($event['event_time'])) . '</td>';
                    echo '<td>' . htmlspecialchars($event['location']) . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editEventModal" data-json=\'' . json_encode($event) . '\' data-form-id="editEventForm" data-type="event"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $event['id'] . '" data-type="event"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Event Modal
            echo '<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addEventForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addEventModalLabel">' . $t['add_event'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_event">';
            echo '<div class="mb-3"><label for="add_event_title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="add_event_title" name="title" required></div>';
            echo '<div class="mb-3"><label for="add_event_description" class="form-label">Description</label><textarea class="form-control" id="add_event_description" name="description" rows="3"></textarea></div>';
            echo '<div class="mb-3"><label for="add_event_date" class="form-label">' . $t['event_date'] . '</label><input type="date" class="form-control" id="add_event_date" name="event_date" required></div>';
            echo '<div class="mb-3"><label for="add_event_time" class="form-label">' . $t['event_time'] . '</label><input type="time" class="form-control" id="add_event_time" name="event_time"></div>';
            echo '<div class="mb-3"><label for="add_event_location" class="form-label">' . $t['location'] . '</label><input type="text" class="form-control" id="add_event_location" name="location"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_event'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Event Modal
            echo '<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editEventForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editEventModalLabel">' . $t['edit_event'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_event">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<div class="mb-3"><label for="title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="title" name="title" required></div>';
            echo '<div class="mb-3"><label for="description" class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="3"></textarea></div>';
            echo '<div class="mb-3"><label for="event_date" class="form-label">' . $t['event_date'] . '</label><input type="date" class="form-control" id="event_date" name="event_date" required></div>';
            echo '<div class="mb-3"><label for="event_time" class="form-label">' . $t['event_time'] . '</label><input type="time" class="form-control" id="event_time" name="event_time"></div>';
            echo '<div class="mb-3"><label for="location" class="form-label">' . $t['location'] . '</label><input type="text" class="form-control" id="location" name="location"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'gallery':
            echo '<h3>Gallery Management</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addGalleryImageModal">' . $t['add_new_image'] . '</button>';
            echo '<div class="row row-cols-1 row-cols-md-3 g-4">';
            $stmt = $pdo->query("SELECT * FROM gallery ORDER BY uploaded_at DESC");
            $images = $stmt->fetchAll();
            if ($images) {
                foreach ($images as $image) {
                    echo '<div class="col">';
                    echo '  <div class="card h-100">';
                    echo '    <a href="' . htmlspecialchars($image['image_path']) . '" class="glightbox" data-gallery="admin-gallery" title="' . htmlspecialchars($image['title']) . '">';
                    echo '      <img src="' . htmlspecialchars($image['image_path']) . '" class="card-img-top" alt="' . htmlspecialchars($image['title']) . '" style="height: 200px; object-fit: cover;">';
                    echo '    </a>';
                    echo '    <div class="card-body">';
                    echo '      <h5 class="card-title">' . htmlspecialchars($image['title']) . '</h5>';
                    echo '      <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $image['id'] . '" data-type="gallery_image"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '    </div>';
                    echo '  </div>';
                    echo '</div>';
                }
            } else {
                echo '<p class="text-center">' . $t['no_records'] . '</p>';
            }
            echo '</div>';

            // Add Gallery Image Modal
            echo '<div class="modal fade" id="addGalleryImageModal" tabindex="-1" aria-labelledby="addGalleryImageModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addGalleryImageForm" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addGalleryImageModalLabel">' . $t['add_new_image'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_gallery_image">';
            echo '<div class="mb-3"><label for="add_image_title" class="form-label">' . $t['image_title'] . '</label><input type="text" class="form-control" id="add_image_title" name="title" required></div>';
            echo '<div class="mb-3"><label for="image_file" class="form-label">' . $t['image'] . '</label><input type="file" class="form-control" id="image_file" name="image_file" accept="image/*" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_image'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;

        case 'backup_restore':
            echo '<h3>' . $t['backup_restore'] . '</h3>';
            echo '<div class="card p-4 mb-4">';
            echo '<h4>' . $t['export_data'] . '</h4>';
            echo '<p>Download a JSON file containing all school data for backup purposes.</p>';
            echo '<form action="" method="POST">';
            echo '<input type="hidden" name="action" value="export_data">';
            echo '<button type="submit" class="btn btn-success"><i class="fas fa-download"></i> ' . $t['export_data'] . '</button>';
            echo '</form>';
            echo '</div>';

            echo '<div class="card p-4">';
            echo '<h4>' . $t['import_data'] . '</h4>';
            echo '<p>Upload a previously exported JSON file to restore or replace current school data.</p>';
            echo '<form action="" method="POST" enctype="multipart/form-data">';
            echo '<input type="hidden" name="action" value="import_data">';
            echo '<div class="mb-3">';
            echo '<label for="json_file" class="form-label">' . $t['choose_json_file'] . '</label>';
            echo '<input type="file" class="form-control" id="json_file" name="json_file" accept=".json" required>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-warning"><i class="fas fa-upload"></i> ' . $t['import_data'] . '</button>';
            echo '</form>';
            echo '</div>';
            break;

        default:
            echo '<h2>Welcome, Admin!</h2>';
            echo '<p>Use the sidebar to manage your school.</p>';
            break;
    }
}

function displayTeacherPanel($pdo, $t, $lang)
{
    global $section;
    switch ($section) {
        case 'attendance':
            echo '<h3>' . $t['take_class_attendance'] . '</h3>';
            echo '<form action="" method="POST" class="mb-4">';
            echo '<input type="hidden" name="action" value="save_attendance">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-4"><label for="attendance_class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="attendance_class_id" name="class_id" required>';
            echo '<option value="">' . $t['select_class'] . '</option>';

            $stmt = $pdo->prepare("SELECT id, name FROM classes WHERE teacher_id = (SELECT id FROM teachers WHERE user_id = ?)");
            $stmt->execute([$_SESSION['id']]);
            $classes = $stmt->fetchAll();
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-4"><label for="attendance_subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="attendance_subject_id" name="subject_id">';
            echo '<option value="">' . $t['select_subject'] . '</option>';
            $subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
            foreach ($subjects as $subject) {
                echo '<option value="' . $subject['id'] . '">' . htmlspecialchars($subject['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="col-md-4"><label for="attendance_date" class="form-label">' . $t['select_date'] . '</label><input type="date" class="form-control" id="attendance_date" name="attendance_date" value="' . date('Y-m-d') . '" required></div>';
            echo '</div>';
            echo '<div id="student_attendance_list">';
            echo '<p>Select a class and date to take attendance.</p>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-primary d-none" id="save_attendance_btn">' . $t['save_attendance'] . '</button>';
            echo '</form>';

            echo '<script>';
            echo '$(document).ready(function() {';
            echo '    function loadStudentsForAttendance() {';
            echo '        var classId = $("#attendance_class_id").val();';
            echo '        var attendanceDate = $("#attendance_date").val();';
            echo '        var subjectId = $("#attendance_subject_id").val();';
            echo '        if (classId && attendanceDate) {';
            echo '            $.ajax({';
            echo '                url: "?page=ajax_data&type=attendance_students&lang=' . $lang . '",';
            echo '                type: "GET",';
            echo '                data: { class_id: classId, attendance_date: attendanceDate, subject_id: subjectId },';
            echo '                success: function(response) {';
            echo '                    $("#student_attendance_list").html(response);';
            echo '                    $("#save_attendance_btn").removeClass("d-none");';
            echo '                }';
            echo '            });';
            echo '        } else {';
            echo '            $("#student_attendance_list").html("<p>Select a class and date to take attendance.</p>");';
            echo '            $("#save_attendance_btn").addClass("d-none");';
            echo '        }';
            echo '    }';
            echo '    $("#attendance_class_id, #attendance_date, #attendance_subject_id").change(loadStudentsForAttendance);';
            echo '    loadStudentsForAttendance();';
            echo '});';
            echo '</script>';
            break;

        case 'assignments':
            echo '<h3>' . $t['upload_assignments'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">' . $t['add_assignment'] . '</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['title'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['subject'] . '</th><th>' . $t['due_date'] . '</th><th>File</th><th>Submissions</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->prepare("SELECT a.*, c.name AS class_name, s.name AS subject_name FROM assignments a JOIN classes c ON a.class_id = c.id JOIN subjects s ON a.subject_id = s.id WHERE a.teacher_id = (SELECT id FROM teachers WHERE user_id = ?) ORDER BY due_date DESC");
            $stmt->execute([$_SESSION['id']]);
            $assignments = $stmt->fetchAll();
            $classes_taught = $pdo->prepare("SELECT id, name FROM classes WHERE teacher_id = (SELECT id FROM teachers WHERE user_id = ?)");
            $classes_taught->execute([$_SESSION['id']]);
            $classes_taught_list = $classes_taught->fetchAll();
            $all_subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();

            if ($assignments) {
                foreach ($assignments as $assignment) {
                    $stmt_submissions = $pdo->prepare("SELECT sa.*, st.name AS student_name FROM student_assignments sa JOIN students st ON sa.student_id = st.id WHERE sa.assignment_id = ?");
                    $stmt_submissions->execute([$assignment['id']]);
                    $submissions = $stmt_submissions->fetchAll();
                    $submission_count = count($submissions);

                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($assignment['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($assignment['title']) . '</td>';
                    echo '<td>' . htmlspecialchars($assignment['class_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($assignment['subject_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($assignment['due_date']) . '</td>';
                    echo '<td>';
                    if ($assignment['file_path']) {
                        echo '<a href="' . htmlspecialchars($assignment['file_path']) . '" target="_blank">Download</a>';
                    } else {
                        echo 'No File';
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($submission_count > 0) {
                        echo '<button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#viewSubmissionsModal" data-submissions=\'' . json_encode($submissions) . '\'>View (' . $submission_count . ')</button>';
                    } else {
                        echo '0';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editAssignmentModal" data-json=\'' . json_encode($assignment) . '\' data-form-id="editAssignmentForm" data-type="assignment"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $assignment['id'] . '" data-type="assignment"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Add Assignment Modal
            echo '<div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-labelledby="addAssignmentModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addAssignmentForm" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addAssignmentModalLabel">' . $t['add_assignment'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_assignment">';
            echo '<div class="mb-3"><label for="add_assignment_title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="add_assignment_title" name="title" required></div>';
            echo '<div class="mb-3"><label for="add_assignment_class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="add_assignment_class_id" name="class_id" required>';
            foreach ($classes_taught_list as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_assignment_subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="add_assignment_subject_id" name="subject_id" required>';
            if (!empty($classes_taught_list)) {
                $default_class_id = $classes_taught_list[0]['id'];
                $stmt_sub = $pdo->prepare("SELECT s.id, s.name FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.class_id = ?");
                $stmt_sub->execute([$default_class_id]);
                $class_subjects_options = $stmt_sub->fetchAll();
                foreach ($class_subjects_options as $sub) {
                    echo '<option value="' . $sub['id'] . '">' . htmlspecialchars($sub['name']) . '</option>';
                }
            } else {
                echo '<option value="">' . $t['select_class'] . ' first</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_assignment_description" class="form-label">' . $t['description'] . '</label><textarea class="form-control" id="add_assignment_description" name="description"></textarea></div>';
            echo '<div class="mb-3"><label for="add_assignment_due_date" class="form-label">' . $t['due_date'] . '</label><input type="date" class="form-control" id="add_assignment_due_date" name="due_date" required></div>';
            echo '<div class="mb-3"><label for="assignment_file" class="form-label">' . $t['upload_file'] . '</label><input type="file" class="form-control" id="assignment_file" name="assignment_file"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['upload_assignment'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Edit Assignment Modal
            echo '<div class="modal fade" id="editAssignmentModal" tabindex="-1" aria-labelledby="editAssignmentModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editAssignmentForm" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editAssignmentModalLabel">' . $t['edit'] . ' ' . $t['assignment'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_assignment">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<input type="hidden" name="file_path_old" id="file_path_old">';
            echo '<div class="mb-3"><label for="title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="title" name="title" required></div>';
            echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
            foreach ($classes_taught_list as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
            echo '<option value="">Select Class First</option>'; // Populated by JS
            echo '</select></div>';
            echo '<div class="mb-3"><label for="description" class="form-label">' . $t['description'] . '</label><textarea class="form-control" id="description" name="description"></textarea></div>';
            echo '<div class="mb-3"><label for="due_date" class="form-label">' . $t['due_date'] . '</label><input type="date" class="form-control" id="due_date" name="due_date" required></div>';
            echo '<div class="mb-3"><label for="assignment_file" class="form-label">' . $t['upload_file'] . ' (Leave blank to keep current file)</label><input type="file" class="form-control" id="assignment_file" name="assignment_file"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // View Submissions Modal
            echo '<div class="modal fade" id="viewSubmissionsModal" tabindex="-1" aria-labelledby="viewSubmissionsModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog modal-lg">';
            echo '<div class="modal-content">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="viewSubmissionsModalLabel">Student Submissions</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Student Name</th><th>Submission Date</th><th>File</th></tr></thead>';
            echo '<tbody id="submissionsTableBody">';
            echo '</tbody></table>';
            echo '</div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<script>';
            echo '$(document).on("click", "[data-bs-target=\"#viewSubmissionsModal\"]", function() {';
            echo '    var submissions = $(this).data("submissions");';
            echo '    var tableBody = $("#submissionsTableBody");';
            echo '    tableBody.empty();';
            echo '    if (submissions && submissions.length > 0) {';
            echo '        $.each(submissions, function(index, submission) {';
            echo '            var row = "<tr>" +';
            echo '                "<td>" + submission.student_name + "</td>" +';
            echo '                "<td>" + new Date(submission.submission_date).toLocaleString() + "</td>" +';
            echo '                "<td><a href=\'" + submission.file_path + "\' target=\'_blank\'>Download File</a></td>" +';
            echo '                "</tr>";';
            echo '            tableBody.append(row);';
            echo '        });';
            echo '    } else {';
            echo '        tableBody.append("<tr><td colspan=\'3\' class=\'text-center\'>No submissions yet.</td></tr>");';
            echo '    }';
            echo '});';
            echo '</script>';
            break;

        case 'exams_marks':
            echo '<h3>' . $t['enter_exam_marks'] . '</h3>';
            echo '<form action="" method="POST" class="mb-4">';
            echo '<input type="hidden" name="action" value="save_marks">';
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><label for="marks_exam_id" class="form-label">' . $t['select_exam'] . '</label><select class="form-select" id="marks_exam_id" name="exam_id" required>';
            echo '<option value="">' . $t['select_exam'] . '</option>';

            $stmt = $pdo->prepare("SELECT e.id, e.name, c.name AS class_name, s.name AS subject_name, e.max_marks FROM exams e JOIN classes c ON e.class_id = c.id JOIN subjects s ON e.subject_id = s.id WHERE c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?) ORDER BY e.exam_date DESC");
            $stmt->execute([$_SESSION['id']]);
            $exams_taught = $stmt->fetchAll();
            foreach ($exams_taught as $exam) {
                echo '<option value="' . $exam['id'] . '">' . htmlspecialchars($exam['name'] . ' - ' . $exam['class_name'] . ' - ' . $exam['subject_name'] . ' (Max: ' . $exam['max_marks'] . ')') . '</option>';
            }
            echo '</select></div>';
            echo '</div>';
            echo '<div id="student_marks_list">';
            echo '<p>Select an exam to enter marks.</p>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-primary d-none" id="save_marks_btn">' . $t['save_marks'] . '</button>';
            echo '</form>';

            echo '<script>';
            echo '$(document).ready(function() {';
            echo '    function loadStudentsForMarks() {';
            echo '        var examId = $("#marks_exam_id").val();';
            echo '        if (examId) {';
            echo '            $.ajax({';
            echo '                url: "?page=ajax_data&type=marks_students&lang=' . $lang . '",';
            echo '                type: "GET",';
            echo '                data: { exam_id: examId },';
            echo '                success: function(response) {';
            echo '                    $("#student_marks_list").html(response);';
            echo '                    $("#save_marks_btn").removeClass("d-none");';
            echo '                }';
            echo '            });';
            echo '        } else {';
            echo '            $("#student_marks_list").html("<p>Select an exam to enter marks.</p>");';
            echo '            $("#save_marks_btn").addClass("d-none");';
            echo '        }';
            echo '    }';
            echo '    $("#marks_exam_id").change(loadStudentsForMarks);';
            echo '    loadStudentsForMarks();';
            echo '});';
            echo '</script>';
            break;

        case 'timetable':
            echo '<h3>' . $t['view_timetable'] . '</h3>';
            $stmt = $pdo->prepare("SELECT tt.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name FROM timetables tt JOIN classes c ON tt.class_id = c.id JOIN subjects s ON tt.subject_id = s.id JOIN teachers t ON tt.teacher_id = t.id WHERE tt.teacher_id = (SELECT id FROM teachers WHERE user_id = ?) ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
            $stmt->execute([$_SESSION['id']]);
            $timetable_entries = $stmt->fetchAll();
            $grouped_timetable = [];
            foreach ($timetable_entries as $entry) {
                $grouped_timetable[$entry['day_of_week']][] = $entry;
            }

            if ($grouped_timetable) {
                $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days_of_week as $day) {
                    if (isset($grouped_timetable[$day])) {
                        echo '<h4>' . $t[strtolower($day)] . '</h4>';
                        echo '<div class="table-responsive mb-4">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>' . $t['class_name'] . '</th><th>' . $t['subject'] . '</th><th>' . $t['start_time'] . '</th><th>' . $t['end_time'] . '</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($grouped_timetable[$day] as $entry) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($entry['class_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($entry['subject_name']) . '</td>';
                            echo '<td>' . htmlspecialchars(date('h:i A', strtotime($entry['start_time']))) . '</td>';
                            echo '<td>' . htmlspecialchars(date('h:i A', strtotime($entry['end_time']))) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<p>' . $t['no_records'] . '</p>';
            }
            break;

        default:
            echo '<h2>Welcome, Teacher!</h2>';
            echo '<p>Use the sidebar to manage your classes and students.</p>';
            break;
    }
}

function displayStudentPanel($pdo, $t, $lang)
{
    global $section;
    $student_info_stmt = $pdo->prepare("SELECT s.id, s.name, s.class_id, c.name AS class_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN classes c ON s.class_id = c.id WHERE u.id = ?");
    $student_info_stmt->execute([$_SESSION['id']]);
    $student = $student_info_stmt->fetch();

    if (!$student) {
        echo '<p>Error: Could not retrieve student information.</p>';
        return;
    }

    $student_id = $student['id'];
    $class_id = $student['class_id'];

    switch ($section) {
        case 'attendance':
            echo '<h3>' . $t['view_attendance'] . '</h3>';
            echo '<h4>My Attendance Record (' . htmlspecialchars($student['name']) . ')</h4>';
            $stmt = $pdo->prepare("SELECT att.attendance_date, att.status, c.name AS class_name, sub.name AS subject_name FROM attendance att JOIN classes c ON att.class_id = c.id LEFT JOIN subjects sub ON att.subject_id = sub.id WHERE att.student_id = ? ORDER BY attendance_date DESC");
            $stmt->execute([$student_id]);
            $attendance_records = $stmt->fetchAll();

            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Date</th><th>Class</th><th>Subject</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            if ($attendance_records) {
                foreach ($attendance_records as $record) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($record['attendance_date']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['class_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['subject_name'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($record['status']) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            break;

        case 'timetable':
            echo '<h3>' . $t['view_timetable'] . '</h3>';
            echo '<h4>My Class Timetable (' . htmlspecialchars($student['class_name'] ?? 'N/A') . ')</h4>';
            if ($class_id) {
                $stmt = $pdo->prepare("SELECT tt.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name FROM timetables tt JOIN classes c ON tt.class_id = c.id JOIN subjects s ON tt.subject_id = s.id JOIN teachers t ON tt.teacher_id = t.id WHERE tt.class_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
                $stmt->execute([$class_id]);
                $timetable_entries = $stmt->fetchAll();
                $grouped_timetable = [];
                foreach ($timetable_entries as $entry) {
                    $grouped_timetable[$entry['day_of_week']][] = $entry;
                }

                if ($grouped_timetable) {
                    $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days_of_week as $day) {
                        if (isset($grouped_timetable[$day])) {
                            echo '<h4>' . $t[strtolower($day)] . '</h4>';
                            echo '<div class="table-responsive mb-4">';
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead><tr><th>' . $t['subject'] . '</th><th>' . $t['assigned_teacher'] . '</th><th>' . $t['start_time'] . '</th><th>' . $t['end_time'] . '</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($grouped_timetable[$day] as $entry) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($entry['subject_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($entry['teacher_name']) . '</td>';
                                echo '<td>' . htmlspecialchars(date('h:i A', strtotime($entry['start_time']))) . '</td>';
                                echo '<td>' . htmlspecialchars(date('h:i A', strtotime($entry['end_time']))) . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<p>' . $t['no_records'] . '</p>';
                }
            } else {
                echo '<p>You are not assigned to a class. Please contact administration.</p>';
            }
            break;

        case 'marks':
            echo '<h3>' . $t['view_marks'] . '</h3>';
            echo '<h4>My Exam Results (' . htmlspecialchars($student['name']) . ')</h4>';
            $stmt = $pdo->prepare("SELECT m.marks_obtained, e.name AS exam_name, e.max_marks, s.name AS subject_name FROM marks m JOIN exams e ON m.exam_id = e.id JOIN subjects s ON e.subject_id = s.id WHERE m.student_id = ? ORDER BY e.exam_date DESC");
            $stmt->execute([$student_id]);
            $marks_records = $stmt->fetchAll();

            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Exam</th><th>Subject</th><th>' . $t['marks_obtained'] . '</th><th>' . $t['max_marks'] . '</th><th>Percentage</th></tr></thead>';
            echo '<tbody>';
            if ($marks_records) {
                foreach ($marks_records as $record) {
                    $percentage = ($record['max_marks'] > 0) ? round(($record['marks_obtained'] / $record['max_marks']) * 100, 2) : 0;
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($record['exam_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['subject_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['marks_obtained']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['max_marks']) . '</td>';
                    echo '<td>' . $percentage . '%</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            break;

        case 'assignments':
            echo '<h3>' . $t['download_assignments'] . '</h3>';
            echo '<h4>My Assignments (' . htmlspecialchars($student['name']) . ')</h4>';
            $stmt = $pdo->prepare("SELECT a.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name, sa.file_path AS submitted_file, sa.submission_date FROM assignments a JOIN classes c ON a.class_id = c.id JOIN subjects s ON a.subject_id = s.id JOIN teachers tea ON a.teacher_id = tea.id LEFT JOIN student_assignments sa ON a.id = sa.assignment_id AND sa.student_id = ? WHERE a.class_id = ? ORDER BY due_date DESC");
            $stmt->execute([$student_id, $class_id]);
            $assignments = $stmt->fetchAll();

            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>' . $t['title'] . '</th><th>Teacher</th><th>Subject</th><th>' . $t['due_date'] . '</th><th>Assignment File</th><th>Submitted File</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            if ($assignments) {
                foreach ($assignments as $assignment) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($assignment['title']) . '</td>';
                    echo '<td>' . htmlspecialchars($assignment['teacher_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($assignment['subject_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($assignment['due_date']) . '</td>';
                    echo '<td>';
                    if ($assignment['file_path']) {
                        echo '<a href="' . htmlspecialchars($assignment['file_path']) . '" target="_blank">Download</a>';
                    } else {
                        echo 'N/A';
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($assignment['submitted_file']) {
                        echo '<a href="' . htmlspecialchars($assignment['submitted_file']) . '" target="_blank">Download</a> (' . date("Y-m-d", strtotime($assignment['submission_date'])) . ')';
                    } else {
                        echo 'Not Submitted';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#submitAssignmentModal" data-assignment-id="' . $assignment['id'] . '" data-student-id="' . $student_id . '">' . $t['submit_assignment'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            // Submit Assignment Modal
            echo '<div class="modal fade" id="submitAssignmentModal" tabindex="-1" aria-labelledby="submitAssignmentModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="submitAssignmentModalLabel">' . $t['submit_assignment'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="submit_student_assignment">';
            echo '<input type="hidden" name="assignment_id" id="submit_assignment_id">';
            echo '<input type="hidden" name="student_id" id="submit_student_id">';
            echo '<div class="mb-3"><label for="submission_file" class="form-label">' . $t['submission_file'] . '</label><input type="file" class="form-control" id="submission_file" name="submission_file" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['submit_assignment'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<script>';
            echo '$(document).on("click", "[data-bs-target=\"#submitAssignmentModal\"]", function() {';
            echo '    var assignmentId = $(this).data("assignment-id");';
            echo '    var studentId = $(this).data("student-id");';
            echo '    $("#submitAssignmentModal #submit_assignment_id").val(assignmentId);';
            echo '    $("#submitAssignmentModal #submit_student_id").val(studentId);';
            echo '});';
            echo '</script>';
            break;

        default:
            echo '<h2>Welcome, Student!</h2>';
            echo '<p>Use the sidebar to view your academic information.</p>';
            break;
    }
}

function displayParentPanel($pdo, $t, $lang)
{
    global $section;
    $parent_user_id = $_SESSION['id'];

    $stmt_students = $pdo->prepare("SELECT id, name, class_id FROM students WHERE parent_id = ?");
    $stmt_students->execute([$parent_user_id]);
    $linked_students = $stmt_students->fetchAll();

    if (empty($linked_students)) {
        echo '<p class="alert alert-warning">' . $t['no_student_linked_parent'] . '</p>';
        return;
    }

    $selected_student_id = $_GET['student_id'] ?? ($linked_students[0]['id'] ?? null);

    echo '<div class="mb-4">';
    echo '<h4>Select Student:</h4>';
    echo '<form method="GET" class="d-inline-block">';
    echo '<input type="hidden" name="page" value="dashboard">';
    echo '<input type="hidden" name="section" value="' . $section . '">';
    echo '<input type="hidden" name="lang" value="' . $lang . '">';
    echo '<select class="form-select w-auto d-inline-block me-2" name="student_id" onchange="this.form.submit()">';
    foreach ($linked_students as $student) {
        $selected = ($student['id'] == $selected_student_id) ? 'selected' : '';
        echo '<option value="' . $student['id'] . '" ' . $selected . '>' . htmlspecialchars($student['name']) . '</option>';
    }
    echo '</select>';
    echo '</form>';
    echo '</div>';

    if (!$selected_student_id) {
        echo '<p>' . $t['select_student_first'] . '</p>';
        return;
    }

    $current_student_info_stmt = $pdo->prepare("SELECT s.id, s.name, s.class_id, c.name AS class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ? AND s.parent_id = ?");
    $current_student_info_stmt->execute([$selected_student_id, $parent_user_id]);
    $current_student = $current_student_info_stmt->fetch();

    if (!$current_student) {
        echo '<p class="alert alert-danger">' . $t['selected_student_not_found'] . '</p>';
        return;
    }

    echo '<h3>Viewing information for: ' . htmlspecialchars($current_student['name']) . ' (Class: ' . htmlspecialchars($current_student['class_name'] ?? 'N/A') . ')</h3>';

    switch ($section) {
        case 'performance':
            echo '<h4>' . $t['view_performance'] . ' (Attendance & Marks)</h4>';

            echo '<h5>Attendance Summary</h5>';
            $stmt_att = $pdo->prepare("SELECT att.attendance_date, att.status, c.name AS class_name, sub.name AS subject_name FROM attendance att JOIN classes c ON att.class_id = c.id LEFT JOIN subjects sub ON att.subject_id = sub.id WHERE student_id = ? ORDER BY attendance_date DESC LIMIT 10");
            $stmt_att->execute([$selected_student_id]);
            $attendance_records = $stmt_att->fetchAll();

            echo '<div class="table-responsive mb-4">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Date</th><th>Class</th><th>Subject</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            if ($attendance_records) {
                foreach ($attendance_records as $record) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($record['attendance_date']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['class_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['subject_name'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($record['status']) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';


            echo '<h5>Marks Summary</h5>';
            $stmt_marks = $pdo->prepare("SELECT m.marks_obtained, e.name AS exam_name, e.max_marks, s.name AS subject_name, c.name AS class_name FROM marks m JOIN exams e ON m.exam_id = e.id JOIN subjects s ON e.subject_id = s.id JOIN classes c ON e.class_id = c.id WHERE m.student_id = ? ORDER BY e.exam_date DESC");
            $stmt_marks->execute([$selected_student_id]);
            $marks_records = $stmt_marks->fetchAll();

            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Exam</th><th>Subject</th><th>' . $t['marks_obtained'] . '</th><th>' . $t['max_marks'] . '</th><th>Percentage</th></tr></thead>';
            echo '<tbody>';
            if ($marks_records) {
                foreach ($marks_records as $record) {
                    $percentage = ($record['max_marks'] > 0) ? round(($record['marks_obtained'] / $record['max_marks']) * 100, 2) : 0;
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($record['exam_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['subject_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['marks_obtained']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['max_marks']) . '</td>';
                    echo '<td>' . $percentage . '%</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            break;

        case 'fees':
            echo '<h4>' . $t['view_student_fees'] . '</h4>';
            $stmt_fees = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY due_date DESC");
            $stmt_fees->execute([$selected_student_id]);
            $fee_records = $stmt_fees->fetchAll();

            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['amount'] . '</th><th>' . $t['due_date_invoice'] . '</th><th>' . $t['paid_date'] . '</th><th>' . $t['invoice_status'] . '</th><th>Description</th></tr></thead>';
            echo '<tbody>';
            if ($fee_records) {
                foreach ($fee_records as $fee) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($fee['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['amount']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['due_date']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['paid_date'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($fee['status']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['description']) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            break;

        default:
            echo '<h2>Welcome, Parent!</h2>';
            echo '<p>Use the dropdown and sidebar to view your child\'s performance and fee status.</p>';
            break;
    }
}

?>