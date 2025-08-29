<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'school_management_dbV2');
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function validate_csrf_token($token)
{
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}
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
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
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
    teacher_id INT, -- Class Teacher
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
    parent_id INT, -- user_id of parent
    medical_history TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    document_path VARCHAR(255), -- e.g., birth certificate scan
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
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
    teacher_id INT, -- teacher who created/manages the exam
    exam_date DATE NOT NULL,
    max_marks INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
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
CREATE TABLE IF NOT EXISTS fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('Tuition', 'Transport', 'Lab', 'Exam', 'Other') NOT NULL DEFAULT 'Tuition',
    class_id INT,
    description TEXT,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_structure_id INT, -- Link to fee structure if applicable
    amount DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE,
    status ENUM('Paid', 'Unpaid', 'Partially Paid') NOT NULL,
    description VARCHAR(255),
    concession DECIMAL(10, 2) DEFAULT 0.00,
    fine DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS fee_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    file_path VARCHAR(255), -- Added missing column
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
CREATE TABLE IF NOT EXISTS study_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
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
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    read_status BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS teacher_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
-- Dummy Data Insertion
INSERT IGNORE INTO users (username, password, role, email) VALUES
('admin', '$2y$10$tJ9fF2vL1xW5B0f8M1G2O.u.n.y.V.S.h.A.N.i.N', 'admin', 'admin@example.com'), -- adminpass
('teacher1', '$2y$10$wKzN1eJ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'teacher', 'teacher1@example.com'), -- teacherpass
('student1', '$2y$10$oKjN2eY0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'student', 'student1@example.com'), -- studentpass
('parent1', '$2y$10$lJ9fF2vL1xW5B0f8M1G2O.u.n.y.V.S.h.A.N.i.N', 'parent', 'parent1@example.com'), -- parentpass
('student2', '$2y$10$qKzN3eZ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'student', 'student2@example.com'), -- studentpass
('parent2', '$2y$10$pKjN4eA0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'parent', 'parent2@example.com'); -- parentpass
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
INSERT IGNORE INTO students (user_id, name, class_id, roll_no, dob, address, phone, parent_id, medical_history, emergency_contact_name, emergency_contact_phone, document_path) VALUES
((SELECT id FROM users WHERE username = 'student1'), 'Fatima Khan', (SELECT id FROM classes WHERE name = 'Class 10A'), '10A-001', '2008-05-15', 'House 1, Street 2, Lahore', '03219876543', (SELECT id FROM users WHERE username = 'parent1'), 'Allergy to peanuts', 'Mr. Asif Khan', '03211234567', 'uploads/documents/birth_cert_fatima.pdf'),
((SELECT id FROM users WHERE username = 'student2'), 'Ahmed Raza', (SELECT id FROM classes WHERE name = 'Class 10A'), '10A-002', '2008-06-20', 'Flat 5, Block B, Islamabad', '03331234567', (SELECT id FROM users WHERE username = 'parent1'), NULL, 'Ms. Zara Raza', '03337654321', 'uploads/documents/birth_cert_ahmed.pdf');
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
INSERT IGNORE INTO fee_structures (name, amount, type, class_id, description) VALUES
('Monthly Tuition Fee', 5000.00, 'Tuition', (SELECT id FROM classes WHERE name = 'Class 10A'), 'Standard monthly tuition'),
('Monthly Tuition Fee', 4500.00, 'Tuition', (SELECT id FROM classes WHERE name = 'Class 9B'), 'Standard monthly tuition');
INSERT IGNORE INTO fees (student_id, fee_structure_id, amount, due_date, status, description) VALUES
((SELECT id FROM students WHERE name = 'Fatima Khan'), (SELECT id FROM fee_structures WHERE name = 'Monthly Tuition Fee' AND class_id = (SELECT id FROM classes WHERE name = 'Class 10A')), 5000.00, '2023-10-01', 'Unpaid', 'Tuition Fee - October'),
((SELECT id FROM students WHERE name = 'Ahmed Raza'), (SELECT id FROM fee_structures WHERE name = 'Monthly Tuition Fee' AND class_id = (SELECT id FROM classes WHERE name = 'Class 10A')), 5000.00, '2023-10-01', 'Paid', 'Tuition Fee - October');
INSERT IGNORE INTO exams (name, class_id, subject_id, teacher_id, exam_date, max_marks) VALUES
('Mid-Term Exam', (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Mathematics'), (SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), '2023-11-15', 100),
('Mid-Term Exam', (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Physics'), (SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), '2023-11-16', 100);
INSERT IGNORE INTO marks (exam_id, student_id, marks_obtained) VALUES
((SELECT id FROM exams WHERE name = 'Mid-Term Exam' AND class_id = (SELECT id FROM classes WHERE name = 'Class 10A') AND subject_id = (SELECT id FROM subjects WHERE name = 'Mathematics')), (SELECT id FROM students WHERE name = 'Fatima Khan'), 85),
((SELECT id FROM exams WHERE name = 'Mid-Term Exam' AND class_id = (SELECT id FROM classes WHERE name = 'Class 10A') AND subject_id = (SELECT id FROM subjects WHERE name = 'Mathematics')), (SELECT id FROM students WHERE name = 'Ahmed Raza'), 78);
INSERT IGNORE INTO assignments (teacher_id, class_id, subject_id, title, description, due_date) VALUES
((SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Mathematics'), 'Algebra Homework', 'Complete exercises on quadratic equations.', '2023-10-25'),
((SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Physics'), 'Newton Laws Worksheet', 'Solve problems related to Newton\'s Laws of Motion.', '2023-11-01');
INSERT IGNORE INTO study_materials (teacher_id, class_id, subject_id, title, description, file_path) VALUES
((SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Mathematics'), 'Algebra Formulas', 'Key formulas for algebra.', 'uploads/study_materials/algebra_formulas.pdf'),
((SELECT id FROM teachers WHERE name = 'Mr. Ali Ahmed'), (SELECT id FROM classes WHERE name = 'Class 10A'), (SELECT id FROM subjects WHERE name = 'Physics'), 'Physics Concepts', 'Basic concepts of mechanics.', 'uploads/study_materials/physics_concepts.pdf');
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
function send_email($to, $subject, $message)
{
    $log_path = 'emails.log';
    file_put_contents($log_path, "To: $to\nSubject: $subject\nMessage: $message\n\n", FILE_APPEND);
    return true;
}
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
function upload_file_securely($file_input_name, $upload_dir, $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'], $max_size = 5 * 1024 * 1024)
{
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error.'];
    }
    $file = $_FILES[$file_input_name];
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds limit. Max 5MB allowed.'];
    }
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions)];
    }
    $new_file_name = uniqid() . '.' . $file_extension;
    $target_file = $upload_dir . $new_file_name;
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'path' => $target_file];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}
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
if (!is_dir('uploads/study_materials')) {
    mkdir('uploads/study_materials', 0777, true);
}
if (!is_dir('uploads/documents')) {
    mkdir('uploads/documents', 0777, true);
}
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
        'upload_assignments' => 'Upload Assignments',
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
        'assigned_teacher' => 'Assigned Class Teacher',
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
        'teacher_classes' => 'My Classes',
        'teacher_students' => 'My Students',
        'teacher_notes' => 'Teacher Notes',
        'send_message' => 'Send Message',
        'internal_messaging' => 'Internal Messaging',
        'my_messages' => 'My Messages',
        'compose_message' => 'Compose Message',
        'receiver' => 'Receiver',
        'unread_messages' => 'Unread Messages',
        'read_message' => 'Read Message',
        'sent_messages' => 'Sent Messages',
        'compose_new_message' => 'Compose New Message',
        'select_receiver' => 'Select Receiver',
        'send' => 'Send',
        'add_note' => 'Add Note for Student',
        'manage_fees_structures' => 'Manage Fee Structures',
        'add_fee_structure' => 'Add Fee Structure',
        'fee_structure_name' => 'Fee Name',
        'fee_type' => 'Fee Type',
        'concession' => 'Concession',
        'fine' => 'Fine',
        'view_all_students_in_class' => 'View All Students in Class',
        'upload_study_materials' => 'Upload Study Materials',
        'study_materials_list' => 'Study Materials List',
        'add_study_material' => 'Add Study Material',
        'promote_students' => 'Promote Students',
        'promote_class' => 'Promote Class',
        'to_class' => 'To Class',
        'promote' => 'Promote',
        'next_academic_year' => 'Next Academic Year',
        'medical_history' => 'Medical History',
        'emergency_contact' => 'Emergency Contact',
        'document_upload' => 'Document Upload',
        'current_file' => 'Current File',
        'guardian_name' => 'Guardian Name',
        'guardian_phone' => 'Guardian Phone',
        'select_fee_structure' => 'Select Fee Structure',
        'fee_structure_description' => 'Fee Description',
        'fee_structure_details' => 'Fee Structure Details',
        'student_document' => 'Student Document',
        'note_title' => 'Note Title',
        'note_content' => 'Note Content',
        'student_performance_overview' => 'Student Performance Overview',
        'view_report_card' => 'View Report Card',
        'all_subjects' => 'All Subjects',
        'academic_year' => 'Academic Year',
        'current_status' => 'Current Status',
        'class_teacher_duties' => 'Class Teacher Duties',
        'manage_class_students' => 'Manage Class Students',
        'class_announcements' => 'Class Announcements',
        'send_parent_notes' => 'Send Parent Notes',
        'add_exam_for_my_class' => 'Add Exam for My Class',
        'manage_my_exams' => 'Manage My Exams',
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
        'upload_assignments' => 'اسائنمنٹس اپ لوڈ کریں',
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
        'assigned_teacher' => 'مقرر کردہ کلاس استاد',
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
        'teacher_classes' => 'میری کلاسز',
        'teacher_students' => 'میرے طلباء',
        'teacher_notes' => 'استاد کے نوٹس',
        'send_message' => 'پیغام بھیجیں',
        'internal_messaging' => 'اندرونی پیغام رسانی',
        'my_messages' => 'میرے پیغامات',
        'compose_message' => 'پیغام لکھیں',
        'receiver' => 'وصول کنندہ',
        'unread_messages' => 'نہ پڑھے گئے پیغامات',
        'read_message' => 'پیغام پڑھیں',
        'sent_messages' => 'بھیجے گئے پیغامات',
        'compose_new_message' => 'نیا پیغام لکھیں',
        'select_receiver' => 'وصول کنندہ منتخب کریں',
        'send' => 'بھیجیں',
        'add_note' => 'طالب علم کے لیے نوٹ شامل کریں',
        'manage_fees_structures' => 'فیس کے ڈھانچے کا انتظام کریں',
        'add_fee_structure' => 'فیس کا ڈھانچہ شامل کریں',
        'fee_structure_name' => 'فیس کا نام',
        'fee_type' => 'فیس کی قسم',
        'concession' => 'رعایت',
        'fine' => 'جرمانہ',
        'view_all_students_in_class' => 'کلاس کے تمام طلباء دیکھیں',
        'upload_study_materials' => 'مطالعہ کا مواد اپ لوڈ کریں',
        'study_materials_list' => 'مطالعہ کا مواد کی فہرست',
        'add_study_material' => 'مطالعہ کا مواد شامل کریں',
        'promote_students' => 'طلباء کو پروموٹ کریں',
        'promote_class' => 'کلاس پروموٹ کریں',
        'to_class' => 'کلاس تک',
        'promote' => 'پروموٹ کریں',
        'next_academic_year' => 'اگلا تعلیمی سال',
        'medical_history' => 'طبی تاریخ',
        'emergency_contact' => 'ہنگامی رابطہ',
        'document_upload' => 'دستاویز اپ لوڈ',
        'current_file' => 'موجودہ فائل',
        'guardian_name' => 'سرپرست کا نام',
        'guardian_phone' => 'سرپرست کا فون',
        'select_fee_structure' => 'فیس کا ڈھانچہ منتخب کریں',
        'fee_structure_description' => 'فیس کی تفصیل',
        'fee_structure_details' => 'فیس کے ڈھانچے کی تفصیلات',
        'student_document' => 'طالب علم کی دستاویز',
        'note_title' => 'نوٹ کا عنوان',
        'note_content' => 'نوٹ کا مواد',
        'student_performance_overview' => 'طالب علم کی کارکردگی کا جائزہ',
        'view_report_card' => 'رپورٹ کارڈ دیکھیں',
        'all_subjects' => 'تمام مضامین',
        'academic_year' => 'تعلیمی سال',
        'current_status' => 'موجودہ حیثیت',
        'class_teacher_duties' => 'کلاس ٹیچر کے فرائض',
        'manage_class_students' => 'کلاس کے طلباء کا انتظام کریں',
        'class_announcements' => 'کلاس اعلانات',
        'send_parent_notes' => 'والدین کو نوٹس بھیجیں',
        'add_exam_for_my_class' => 'میری کلاس کے لیے امتحان شامل کریں',
        'manage_my_exams' => 'میرے امتحانات کا انتظام کریں',
    ]
];
$t = $phrases[$lang];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = 'CSRF token validation failed. Please try again.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    unset($_SESSION['csrf_token']);
    generate_csrf_token();
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
                    $medical_history = trim($_POST['medical_history']);
                    $emergency_contact_name = trim($_POST['emergency_contact_name']);
                    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
                    $document_path = null;
                    if (isset($_FILES['student_document']) && $_FILES['student_document']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('student_document', 'uploads/documents/', ['pdf', 'jpg', 'jpeg', 'png']);
                        if ($upload_result['success']) {
                            $document_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "Document upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=students&lang=$lang");
                            exit;
                        }
                    }
                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                        $stmt->execute([$username, $password]);
                        $user_id = $pdo->lastInsertId();
                        $stmt = $pdo->prepare("INSERT INTO students (user_id, name, class_id, roll_no, dob, address, phone, parent_id, medical_history, emergency_contact_name, emergency_contact_phone, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $name, $class_id, $roll_no, $dob, $address, $phone, $parent_id, $medical_history, $emergency_contact_name, $emergency_contact_phone, $document_path]);
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
                    $medical_history = trim($_POST['medical_history']);
                    $emergency_contact_name = trim($_POST['emergency_contact_name']);
                    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
                    $document_path = $_POST['current_document_path'] ?? null;
                    if (isset($_FILES['student_document']) && $_FILES['student_document']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('student_document', 'uploads/documents/', ['pdf', 'jpg', 'jpeg', 'png']);
                        if ($upload_result['success']) {
                            if ($document_path && file_exists($document_path)) {
                                unlink($document_path);
                            }
                            $document_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "Document upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=students&lang=$lang");
                            exit;
                        }
                    }
                    $stmt = $pdo->prepare("UPDATE students SET name = ?, class_id = ?, roll_no = ?, dob = ?, address = ?, phone = ?, parent_id = ?, medical_history = ?, emergency_contact_name = ?, emergency_contact_phone = ?, document_path = ? WHERE id = ?");
                    $stmt->execute([$name, $class_id, $roll_no, $dob, $address, $phone, $parent_id, $medical_history, $emergency_contact_name, $emergency_contact_phone, $document_path, $id]);
                    $_SESSION['message'] = "Student updated successfully!";
                    header("Location: ?page=dashboard&section=students&lang=$lang");
                    exit;
                }
                break;
            case 'delete_student':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("SELECT user_id, document_path FROM students WHERE id = ?");
                    $stmt->execute([$id]);
                    $student = $stmt->fetch();
                    if ($student) {
                        $pdo->beginTransaction();
                        try {
                            if ($student['document_path'] && file_exists($student['document_path'])) {
                                unlink($student['document_path']);
                            }
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
                    $teacher_id = trim($_POST['teacher_id']) ?? null;
                    $exam_date = trim($_POST['exam_date']);
                    $max_marks = trim($_POST['max_marks']);
                    $stmt = $pdo->prepare("INSERT INTO exams (name, class_id, subject_id, teacher_id, exam_date, max_marks) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $class_id, $subject_id, $teacher_id, $exam_date, $max_marks]);
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
                    $teacher_id = trim($_POST['teacher_id']) ?? null;
                    $exam_date = trim($_POST['exam_date']);
                    $max_marks = trim($_POST['max_marks']);
                    $stmt = $pdo->prepare("UPDATE exams SET name = ?, class_id = ?, subject_id = ?, teacher_id = ?, exam_date = ?, max_marks = ? WHERE id = ?");
                    $stmt->execute([$name, $class_id, $subject_id, $teacher_id, $exam_date, $max_marks, $id]);
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
            case 'teacher_add_exam':
                if (isTeacher()) {
                    $name = trim($_POST['name']);
                    $class_id = trim($_POST['class_id']);
                    $subject_id = trim($_POST['subject_id']);
                    $teacher_db_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_db_id_stmt->execute([$_SESSION['id']]);
                    $teacher_db_id = $teacher_db_id_stmt->fetchColumn();
                    $exam_date = trim($_POST['exam_date']);
                    $max_marks = trim($_POST['max_marks']);
                    $stmt = $pdo->prepare("INSERT INTO exams (name, class_id, subject_id, teacher_id, exam_date, max_marks) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $class_id, $subject_id, $teacher_db_id, $exam_date, $max_marks]);
                    $_SESSION['message'] = "Exam added successfully!";
                    header("Location: ?page=dashboard&section=exams_marks&lang=$lang");
                    exit;
                }
                break;
            case 'teacher_edit_exam':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $class_id = trim($_POST['class_id']);
                    $subject_id = trim($_POST['subject_id']);
                    $exam_date = trim($_POST['exam_date']);
                    $max_marks = trim($_POST['max_marks']);
                    $teacher_db_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_db_id_stmt->execute([$_SESSION['id']]);
                    $teacher_db_id = $teacher_db_id_stmt->fetchColumn();
                    $stmt = $pdo->prepare("UPDATE exams SET name = ?, class_id = ?, subject_id = ?, exam_date = ?, max_marks = ? WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$name, $class_id, $subject_id, $exam_date, $max_marks, $id, $teacher_db_id]);
                    $_SESSION['message'] = "Exam updated successfully!";
                    header("Location: ?page=dashboard&section=exams_marks&lang=$lang");
                    exit;
                }
                break;
            case 'teacher_delete_exam':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $teacher_db_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_db_id_stmt->execute([$_SESSION['id']]);
                    $teacher_db_id = $teacher_db_id_stmt->fetchColumn();
                    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $teacher_db_id]);
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
            case 'add_fee_structure':
                if (isAdmin()) {
                    $name = trim($_POST['name']);
                    $amount = trim($_POST['amount']);
                    $type = trim($_POST['type']);
                    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                    $description = trim($_POST['description']);
                    $stmt = $pdo->prepare("INSERT INTO fee_structures (name, amount, type, class_id, description) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $amount, $type, $class_id, $description]);
                    $_SESSION['message'] = "Fee structure added successfully!";
                    header("Location: ?page=dashboard&section=fees_structures&lang=$lang");
                    exit;
                }
                break;
            case 'edit_fee_structure':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $amount = trim($_POST['amount']);
                    $type = trim($_POST['type']);
                    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                    $description = trim($_POST['description']);
                    $stmt = $pdo->prepare("UPDATE fee_structures SET name = ?, amount = ?, type = ?, class_id = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $amount, $type, $class_id, $description, $id]);
                    $_SESSION['message'] = "Fee structure updated successfully!";
                    header("Location: ?page=dashboard&section=fees_structures&lang=$lang");
                    exit;
                }
                break;
            case 'delete_fee_structure':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM fee_structures WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Fee structure deleted successfully!";
                    header("Location: ?page=dashboard&section=fees_structures&lang=$lang");
                    exit;
                }
                break;
            case 'add_fee_invoice':
                if (isAdmin()) {
                    $student_id = trim($_POST['student_id']);
                    $fee_structure_id = !empty($_POST['fee_structure_id']) ? $_POST['fee_structure_id'] : null;
                    $amount = trim($_POST['amount']);
                    $due_date = trim($_POST['due_date']);
                    $status = trim($_POST['status']);
                    $description = trim($_POST['description']);
                    $concession = trim($_POST['concession']) ?? 0.00;
                    $fine = trim($_POST['fine']) ?? 0.00;
                    $paid_date = ($status == 'Paid') ? date('Y-m-d') : null;
                    $stmt = $pdo->prepare("INSERT INTO fees (student_id, fee_structure_id, amount, due_date, paid_date, status, description, concession, fine) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$student_id, $fee_structure_id, $amount, $due_date, $paid_date, $status, $description, $concession, $fine]);
                    $_SESSION['message'] = "Fee invoice added successfully!";
                    header("Location: ?page=dashboard&section=fees&lang=$lang");
                    exit;
                }
                break;
            case 'edit_fee_invoice':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $student_id = trim($_POST['student_id']);
                    $fee_structure_id = !empty($_POST['fee_structure_id']) ? $_POST['fee_structure_id'] : null;
                    $amount = trim($_POST['amount']);
                    $due_date = trim($_POST['due_date']);
                    $status = trim($_POST['status']);
                    $description = trim($_POST['description']);
                    $concession = trim($_POST['concession']) ?? 0.00;
                    $fine = trim($_POST['fine']) ?? 0.00;
                    $paid_date = ($status == 'Paid') ? (isset($_POST['paid_date']) && !empty($_POST['paid_date']) ? $_POST['paid_date'] : date('Y-m-d')) : null;
                    $stmt = $pdo->prepare("UPDATE fees SET student_id = ?, fee_structure_id = ?, amount = ?, due_date = ?, paid_date = ?, status = ?, description = ?, concession = ?, fine = ? WHERE id = ?");
                    $stmt->execute([$student_id, $fee_structure_id, $amount, $due_date, $paid_date, $status, $description, $concession, $fine, $id]);
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
            case 'bulk_add_fee_invoice':
                if (isAdmin()) {
                    $class_id = $_POST['class_id'];
                    $fee_structure_id = !empty($_POST['fee_structure_id']) ? $_POST['fee_structure_id'] : null;
                    $amount = trim($_POST['amount']);
                    $due_date = trim($_POST['due_date']);
                    $status = 'Unpaid';
                    $description = trim($_POST['description']);

                    $stmt_students = $pdo->prepare("SELECT id FROM students WHERE class_id = ?");
                    $stmt_students->execute([$class_id]);
                    $student_ids = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($student_ids)) {
                        $pdo->beginTransaction();
                        try {
                            $stmt_insert = $pdo->prepare("INSERT INTO fees (student_id, fee_structure_id, amount, due_date, status, description) VALUES (?, ?, ?, ?, ?, ?)");
                            foreach ($student_ids as $student_id) {
                                $stmt_insert->execute([$student_id, $fee_structure_id, $amount, $due_date, $status, $description]);
                            }
                            $pdo->commit();
                            $_SESSION['message'] = "Bulk fee invoices created successfully for " . count($student_ids) . " students.";
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            $_SESSION['error'] = "Error creating bulk invoices: " . $e->getMessage();
                        }
                    } else {
                        $_SESSION['error'] = "No students found in the selected class.";
                    }
                    header("Location: ?page=dashboard&section=fees&lang=$lang");
                    exit;
                }
                break;
            case 'mark_fee_paid':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $paid_amount = floatval($_POST['paid_amount']);
                    $fee_stmt = $pdo->prepare("SELECT amount, paid_date, status FROM fees WHERE id = ?");
                    $fee_stmt->execute([$id]);
                    $fee_record = $fee_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($fee_record) {
                        $current_paid_amount_sum_stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM fee_transactions WHERE fee_id = ?");
                        $current_paid_amount_sum_stmt->execute([$id]);
                        $current_paid_amount_sum = $current_paid_amount_sum_stmt->fetchColumn() ?: 0;
                        $remaining_amount = $fee_record['amount'] - $current_paid_amount_sum;
                        if ($paid_amount > $remaining_amount) {
                            $_SESSION['error'] = "Paid amount cannot exceed remaining amount.";
                        } else {
                            $pdo->beginTransaction();
                            try {
                                $stmt_transaction = $pdo->prepare("INSERT INTO fee_transactions (fee_id, amount_paid, payment_method) VALUES (?, ?, 'Cash')");
                                $stmt_transaction->execute([$id, $paid_amount]);
                                $new_total_paid = $current_paid_amount_sum + $paid_amount;
                                $new_status = 'Unpaid';
                                if ($new_total_paid >= $fee_record['amount']) {
                                    $new_status = 'Paid';
                                } elseif ($new_total_paid > 0) {
                                    $new_status = 'Partially Paid';
                                }
                                $stmt_update_fee = $pdo->prepare("UPDATE fees SET status = ?, paid_date = ? WHERE id = ?");
                                $stmt_update_fee->execute([$new_status, ($new_status == 'Paid' ? date('Y-m-d') : $fee_record['paid_date']), $id]);
                                $pdo->commit();
                                $_SESSION['message'] = "Fee marked as Paid (partial/full)! Transaction recorded.";
                            } catch (PDOException $e) {
                                $pdo->rollBack();
                                $_SESSION['error'] = "Error marking fee as paid: " . $e->getMessage();
                            }
                        }
                    }
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
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $due_date = trim($_POST['due_date']);
                    $file_path = null;
                    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('assignment_file', 'uploads/assignments/');
                        if ($upload_result['success']) {
                            $file_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "File upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=assignments&lang=$lang");
                            exit;
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
                    $file_path = $_POST['file_path_old'] ?? null;
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('assignment_file', 'uploads/assignments/');
                        if ($upload_result['success']) {
                            if ($file_path && file_exists($file_path)) {
                                unlink($file_path);
                            }
                            $file_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "File upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=assignments&lang=$lang");
                            exit;
                        }
                    }
                    $stmt = $pdo->prepare("UPDATE assignments SET class_id = ?, subject_id = ?, title = ?, description = ?, due_date = ?, file_path = ? WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$class_id, $subject_id, $title, $description, $due_date, $file_path, $id, $teacher_id]);
                    $_SESSION['message'] = "Assignment updated successfully!";
                    header("Location: ?page=dashboard&section=assignments&lang=$lang");
                    exit;
                }
                break;
            case 'delete_assignment':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT file_path FROM assignments WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $teacher_id]);
                    $assignment = $stmt->fetch();
                    if ($assignment && $assignment['file_path'] && file_exists($assignment['file_path'])) {
                        unlink($assignment['file_path']);
                    }
                    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $teacher_id]);
                    $_SESSION['message'] = "Assignment deleted successfully!";
                    header("Location: ?page=dashboard&section=assignments&lang=$lang");
                    exit;
                }
                break;
            case 'add_study_material':
                if (isTeacher()) {
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $file_path = null;
                    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('material_file', 'uploads/study_materials/');
                        if ($upload_result['success']) {
                            $file_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "File upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=study_materials&lang=$lang");
                            exit;
                        }
                    } else {
                        $_SESSION['error'] = "No file uploaded or upload error.";
                        header("Location: ?page=dashboard&section=study_materials&lang=$lang");
                        exit;
                    }
                    $stmt = $pdo->prepare("INSERT INTO study_materials (teacher_id, class_id, subject_id, title, description, file_path) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$teacher_id, $class_id, $subject_id, $title, $description, $file_path]);
                    $_SESSION['message'] = "Study material added successfully!";
                    header("Location: ?page=dashboard&section=study_materials&lang=$lang");
                    exit;
                }
                break;
            case 'edit_study_material':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $class_id = $_POST['class_id'];
                    $subject_id = $_POST['subject_id'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $file_path = $_POST['current_material_path'] ?? null;
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('material_file', 'uploads/study_materials/');
                        if ($upload_result['success']) {
                            if ($file_path && file_exists($file_path)) {
                                unlink($file_path);
                            }
                            $file_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "File upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=study_materials&lang=$lang");
                            exit;
                        }
                    }
                    $stmt = $pdo->prepare("UPDATE study_materials SET class_id = ?, subject_id = ?, title = ?, description = ?, file_path = ? WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$class_id, $subject_id, $title, $description, $file_path, $id, $teacher_id]);
                    $_SESSION['message'] = "Study material updated successfully!";
                    header("Location: ?page=dashboard&section=study_materials&lang=$lang");
                    exit;
                }
                break;
            case 'delete_study_material':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT file_path FROM study_materials WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $teacher_id]);
                    $material = $stmt->fetch();
                    if ($material && $material['file_path'] && file_exists($material['file_path'])) {
                        unlink($material['file_path']);
                    }
                    $stmt = $pdo->prepare("DELETE FROM study_materials WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $teacher_id]);
                    $_SESSION['message'] = "Study material deleted successfully!";
                    header("Location: ?page=dashboard&section=study_materials&lang=$lang");
                    exit;
                }
                break;
            case 'add_gallery_image':
                if (isAdmin()) {
                    $title = trim($_POST['title']);
                    $image_path = null;
                    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('image_file', 'uploads/gallery/', ['jpg', 'jpeg', 'png', 'gif']);
                        if ($upload_result['success']) {
                            $image_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "Image upload failed: " . $upload_result['message'];
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
                        $upload_result = upload_file_securely('announcement_file', 'uploads/announcements/');
                        if ($upload_result['success']) {
                            $file_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "File upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=announcements&lang=$lang");
                            exit;
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
                    $file_path = $_POST['file_path_old'] ?? null;
                    if (isset($_FILES['announcement_file']) && $_FILES['announcement_file']['error'] == UPLOAD_ERR_OK) {
                        $upload_result = upload_file_securely('announcement_file', 'uploads/announcements/');
                        if ($upload_result['success']) {
                            if ($file_path && file_exists($file_path)) {
                                unlink($file_path);
                            }
                            $file_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "File upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=announcements&lang=$lang");
                            exit;
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
                    header("Location: ?page=dashboard&section=events_admin&lang=$lang");
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
                    header("Location: ?page=dashboard&section=events_admin&lang=$lang");
                    exit;
                }
                break;
            case 'delete_event':
                if (isAdmin()) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "Event deleted successfully!";
                    header("Location: ?page=dashboard&section=events_admin&lang=$lang");
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
                        $upload_result = upload_file_securely('submission_file', 'uploads/student_assignments/');
                        if ($upload_result['success']) {
                            $file_path = $upload_result['path'];
                        } else {
                            $_SESSION['error'] = "File upload failed: " . $upload_result['message'];
                            header("Location: ?page=dashboard&section=assignments&lang=$lang");
                            exit;
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
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $stmt_insert_token = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                    $stmt_insert_token->execute([$email, $token, $expires_at]);
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
                $token = $_POST['token'];
                $stmt_check_token = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
                $stmt_check_token->execute([$email, $token]);
                $reset_entry = $stmt_check_token->fetch();
                if (!$reset_entry) {
                    $_SESSION['error'] = $t['invalid_reset_link'];
                    header("Location: ?page=login&lang=$lang");
                    exit;
                }
                if ($new_password !== $confirm_new_password) {
                    $_SESSION['error'] = $t['password_mismatch'];
                    header("Location: ?page=reset_password&token=$token&email=" . urlencode($email) . "&lang=$lang");
                    exit;
                }
                $hashed_password = hashPassword($new_password);
                $pdo->beginTransaction();
                try {
                    $stmt_update_password = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmt_update_password->execute([$hashed_password, $email]);
                    $stmt_delete_token = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt_delete_token->execute([$email]);
                    $pdo->commit();
                    $_SESSION['message'] = $t['password_updated_success'];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Error resetting password: " . $e->getMessage();
                }
                header("Location: ?page=login&lang=$lang");
                exit;
                break;
            case 'send_internal_message':
                if (isAuthenticated()) {
                    $sender_id = $_SESSION['id'];
                    $receiver_id = $_POST['receiver_id'];
                    $subject = trim($_POST['subject']);
                    $message_content = trim($_POST['message_content']);
                    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$sender_id, $receiver_id, $subject, $message_content]);
                    $_SESSION['message'] = $t['message_sent'];
                    header("Location: ?page=dashboard&section=messages&lang=$lang");
                    exit;
                }
                break;
            case 'add_teacher_note':
                if (isTeacher()) {
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    $student_id = $_POST['student_id'];
                    $note_title = trim($_POST['note_title']);
                    $note_content = trim($_POST['note_content']);
                    $stmt = $pdo->prepare("INSERT INTO teacher_notes (teacher_id, student_id, title, note) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$teacher_id, $student_id, $note_title, $note_content]);
                    $_SESSION['message'] = "Note added successfully!";
                    header("Location: ?page=dashboard&section=teacher_notes&lang=$lang");
                    exit;
                }
                break;
            case 'edit_teacher_note':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $note_title = trim($_POST['note_title']);
                    $note_content = trim($_POST['note_content']);
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    $stmt = $pdo->prepare("UPDATE teacher_notes SET title = ?, note = ? WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$note_title, $note_content, $id, $teacher_id]);
                    $_SESSION['message'] = "Note updated successfully!";
                    header("Location: ?page=dashboard&section=teacher_notes&lang=$lang");
                    exit;
                }
                break;
            case 'delete_teacher_note':
                if (isTeacher()) {
                    $id = $_POST['id'];
                    $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                    $teacher_id_stmt->execute([$_SESSION['id']]);
                    $teacher_id = $teacher_id_stmt->fetchColumn();
                    $stmt = $pdo->prepare("DELETE FROM teacher_notes WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$id, $teacher_id]);
                    $_SESSION['message'] = "Note deleted successfully!";
                    header("Location: ?page=dashboard&section=teacher_notes&lang=$lang");
                    exit;
                }
                break;
            case 'promote_students':
                if (isAdmin()) {
                    $current_class_id = $_POST['current_class_id'];
                    $next_class_id = $_POST['next_class_id'];
                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("UPDATE students SET class_id = ? WHERE class_id = ?");
                        $stmt->execute([$next_class_id, $current_class_id]);
                        $pdo->commit();
                        $_SESSION['message'] = "Students promoted successfully from " . $_POST['current_class_name'] . " to " . $_POST['next_class_name'] . "!";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Error promoting students: " . $e->getMessage();
                    }
                    header("Location: ?page=dashboard&section=students&lang=$lang");
                    exit;
                }
                break;
            case 'export_data':
                if (isAdmin()) {
                    $tables = [
                        'users',
                        'password_resets',
                        'students',
                        'teachers',
                        'classes',
                        'subjects',
                        'class_subjects',
                        'timetables',
                        'attendance',
                        'exams',
                        'marks',
                        'fee_structures',
                        'fees',
                        'fee_transactions',
                        'announcements',
                        'events',
                        'admissions',
                        'contacts',
                        'assignments',
                        'study_materials',
                        'gallery',
                        'student_assignments',
                        'messages',
                        'teacher_notes'
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
                            'password_resets',
                            'teachers',
                            'classes',
                            'subjects',
                            'fee_structures',
                            'students',
                            'class_subjects',
                            'timetables',
                            'exams',
                            'assignments',
                            'study_materials',
                            'fees',
                            'fee_transactions',
                            'announcements',
                            'events',
                            'admissions',
                            'contacts',
                            'gallery',
                            'attendance',
                            'marks',
                            'student_assignments',
                            'messages',
                            'teacher_notes'
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
                $options = '';
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
                $stmt_students = $pdo->prepare("SELECT id, name FROM students WHERE class_id = ? ORDER BY name");
                $stmt_students->execute([$class_id]);
                $students_in_class = $stmt_students->fetchAll();
                $stmt_existing_attendance = $pdo->prepare("
                    SELECT student_id, status 
                    FROM attendance 
                    WHERE class_id = ? AND attendance_date = ? AND (subject_id = ? OR (subject_id IS NULL AND ? IS NULL))
                ");
                $stmt_existing_attendance->execute([$class_id, $attendance_date, $subject_id, $subject_id]);
                $existing_attendance = $stmt_existing_attendance->fetchAll(PDO::FETCH_KEY_PAIR);
                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered table-striped">';
                echo '<thead><tr><th>' . $t['student_name'] . '</th><th>' . $t['status'] . '</th></tr></thead>';
                echo '<tbody>';
                if ($students_in_class) {
                    foreach ($students_in_class as $student) {
                        $current_status = $existing_attendance[$student['id']] ?? 'Present';
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
        case 'fee_structure_details':
            $fee_structure_id = $_GET['fee_structure_id'] ?? 0;
            if ($fee_structure_id) {
                $stmt = $pdo->prepare("SELECT amount, description FROM fee_structures WHERE id = ?");
                $stmt->execute([$fee_structure_id]);
                $structure = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($structure) {
                    echo json_encode(['amount' => $structure['amount'], 'description' => $structure['description']]);
                } else {
                    echo json_encode(['amount' => 0, 'description' => '']);
                }
            } else {
                echo json_encode(['amount' => 0, 'description' => '']);
            }
            exit;
        case 'student_full_details':
            if (!isAdmin()) exit;
            $student_id = $_GET['student_id'] ?? 0;
            if (!$student_id) exit('<p>Invalid student ID.</p>');

            // Personal Details
            $stmt_personal = $pdo->prepare("SELECT s.*, c.name AS class_name, p.username AS parent_username FROM students s LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN users p ON s.parent_id = p.id WHERE s.id = ?");
            $stmt_personal->execute([$student_id]);
            $personal = $stmt_personal->fetch(PDO::FETCH_ASSOC);
            if (!$personal) exit('<p>Student not found.</p>');

            echo '<h4>Personal Details: ' . htmlspecialchars($personal['name']) . '</h4>';
            echo '<table class="table table-sm table-bordered"><tbody>';
            echo '<tr><th>Roll No</th><td>' . htmlspecialchars($personal['roll_no']) . '</td><th>Class</th><td>' . htmlspecialchars($personal['class_name']) . '</td></tr>';
            echo '<tr><th>Date of Birth</th><td>' . htmlspecialchars($personal['dob']) . '</td><th>Phone</th><td>' . htmlspecialchars($personal['phone']) . '</td></tr>';
            echo '<tr><th>Address</th><td colspan="3">' . htmlspecialchars($personal['address']) . '</td></tr>';
            echo '<tr><th>Parent</th><td>' . htmlspecialchars($personal['parent_username'] ?? 'N/A') . '</td><th>Emergency Contact</th><td>' . htmlspecialchars($personal['emergency_contact_name']) . ' (' . htmlspecialchars($personal['emergency_contact_phone']) . ')</td></tr>';
            echo '<tr><th>Medical History</th><td colspan="3">' . nl2br(htmlspecialchars($personal['medical_history'] ?? 'None')) . '</td></tr>';
            echo '</tbody></table>';

            // Fee Details
            $stmt_fees = $pdo->prepare("SELECT SUM(f.amount - f.concession + f.fine) as total_due, SUM((SELECT SUM(amount_paid) FROM fee_transactions WHERE fee_id = f.id)) as total_paid FROM fees f WHERE f.student_id = ?");
            $stmt_fees->execute([$student_id]);
            $fee_summary = $stmt_fees->fetch(PDO::FETCH_ASSOC);
            echo '<h4 class="mt-4">Fee Summary</h4>';
            echo '<table class="table table-sm table-bordered"><tbody>';
            echo '<tr><th>Total Due</th><td>' . number_format($fee_summary['total_due'] ?? 0, 2) . '</td></tr>';
            echo '<tr><th>Total Paid</th><td>' . number_format($fee_summary['total_paid'] ?? 0, 2) . '</td></tr>';
            echo '<tr><th>Balance</th><td class="fw-bold">' . number_format(($fee_summary['total_due'] ?? 0) - ($fee_summary['total_paid'] ?? 0), 2) . '</td></tr>';
            echo '</tbody></table>';

            // Attendance Summary
            $stmt_att = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status");
            $stmt_att->execute([$student_id]);
            $att_summary_raw = $stmt_att->fetchAll(PDO::FETCH_KEY_PAIR);
            $total_days = array_sum($att_summary_raw);
            echo '<h4 class="mt-4">Attendance Summary</h4>';
            echo '<table class="table table-sm table-bordered"><tbody>';
            echo '<tr><th>Present</th><td>' . ($att_summary_raw['Present'] ?? 0) . ' (' . ($total_days > 0 ? round((($att_summary_raw['Present'] ?? 0) / $total_days) * 100) : 0) . '%)</td></tr>';
            echo '<tr><th>Absent</th><td>' . ($att_summary_raw['Absent'] ?? 0) . ' (' . ($total_days > 0 ? round((($att_summary_raw['Absent'] ?? 0) / $total_days) * 100) : 0) . '%)</td></tr>';
            echo '<tr><th>Late</th><td>' . ($att_summary_raw['Late'] ?? 0) . ' (' . ($total_days > 0 ? round((($att_summary_raw['Late'] ?? 0) / $total_days) * 100) : 0) . '%)</td></tr>';
            echo '</tbody></table>';

            // Exam Results
            echo '<h4 class="mt-4">Recent Exam Results</h4>';
            $stmt_exams = $pdo->prepare("SELECT e.name AS exam_name, e.max_marks, s.name AS subject_name, m.marks_obtained FROM marks m JOIN exams e ON m.exam_id = e.id JOIN subjects s ON e.subject_id = s.id WHERE m.student_id = ? ORDER BY e.exam_date DESC LIMIT 10");
            $stmt_exams->execute([$student_id]);
            $exam_results = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
            if ($exam_results) {
                echo '<div class="table-responsive" style="max-height: 200px; overflow-y: auto;"><table class="table table-sm table-bordered"><thead><tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Max Marks</th></tr></thead><tbody>';
                foreach ($exam_results as $result) {
                    echo '<tr><td>' . htmlspecialchars($result['exam_name']) . '</td><td>' . htmlspecialchars($result['subject_name']) . '</td><td>' . htmlspecialchars($result['marks_obtained'] ?? 'N/A') . '</td><td>' . htmlspecialchars($result['max_marks']) . '</td></tr>';
                }
                echo '</tbody></table></div>';
            } else {
                echo '<p>No exam records found.</p>';
            }
            exit;
            break;
        case 'student_full_report_card':
            $student_id = $_GET['student_id'] ?? 0;
            $academic_year = $_GET['academic_year'] ?? date('Y');
            if ($student_id) {
                $stmt_student = $pdo->prepare("SELECT s.name as student_name, c.name as class_name, s.roll_no FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
                $stmt_student->execute([$student_id]);
                $student_data = $stmt_student->fetch(PDO::FETCH_ASSOC);
                if (!$student_data) {
                    echo '<p>Student not found.</p>';
                    break;
                }
                echo '<h5>Report Card for ' . htmlspecialchars($student_data['student_name']) . ' (Roll No: ' . htmlspecialchars($student_data['roll_no']) . ') - Class: ' . htmlspecialchars($student_data['class_name'] ?? 'N/A') . ' - Academic Year: ' . htmlspecialchars($academic_year) . '</h5>';
                $stmt_exams = $pdo->prepare("
                    SELECT e.id AS exam_id, e.name AS exam_name, e.exam_date, e.max_marks, s.name AS subject_name, m.marks_obtained
                    FROM exams e
                    JOIN subjects s ON e.subject_id = s.id
                    LEFT JOIN marks m ON e.id = m.exam_id AND m.student_id = ?
                    WHERE e.class_id = (SELECT class_id FROM students WHERE id = ?) AND YEAR(e.exam_date) = ?
                    ORDER BY e.exam_date ASC, s.name ASC
                ");
                $stmt_exams->execute([$student_id, $student_id, $academic_year]);
                $exam_results = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
                $grouped_results = [];
                foreach ($exam_results as $row) {
                    $grouped_results[$row['exam_name'] . ' (' . $row['exam_date'] . ')'][] = $row;
                }
                if ($grouped_results) {
                    foreach ($grouped_results as $exam_label => $results) {
                        echo '<h6 class="mt-3">' . htmlspecialchars($exam_label) . '</h6>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-sm">';
                        echo '<thead><tr><th>Subject</th><th>Marks Obtained</th><th>Max Marks</th><th>Percentage</th></tr></thead>';
                        echo '<tbody>';
                        $total_obtained = 0;
                        $total_max = 0;
                        foreach ($results as $result) {
                            $percentage = ($result['max_marks'] > 0) ? round(($result['marks_obtained'] / $result['max_marks']) * 100, 2) : 0;
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($result['subject_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($result['marks_obtained'] ?? 'N/A') . '</td>';
                            echo '<td>' . htmlspecialchars($result['max_marks']) . '</td>';
                            echo '<td>' . htmlspecialchars($percentage) . '%</td>';
                            echo '</tr>';
                            $total_obtained += $result['marks_obtained'] ?? 0;
                            $total_max += $result['max_marks'];
                        }
                        echo '<tr><td colspan="2" class="text-end"><strong>Total:</strong></td><td><strong>' . $total_max . '</strong></td><td><strong>' . ($total_max > 0 ? round(($total_obtained / $total_max) * 100, 2) : 0) . '%</strong></td></tr>';
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>' . $t['no_records'] . '</p>';
                }
            } else {
                echo '<p>Select a student to view report card.</p>';
            }
            break;
        default:
            echo '';
            break;
    }
    exit;
}
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
generate_csrf_token();
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
            height: 100%;
            width: 4px;
            background: #e9ecef;
        }

        .timeline-item {
            margin-bottom: 20px;
            position: relative;
            padding-left: 25px;
        }

        .timeline-item:after {
            content: '';
            position: absolute;
            left: 10px;
            top: 43%;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 4px solid #28a745;
            z-index: 1;
        }

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

        .dashboard-sidebar .nav-link i {
            margin-left: 10px;
            margin-right: 7px;
        }

        .py-5.bg-light .col-md-6 {
            background: #fff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            text-align: center;
        }

        .py-5.bg-light .col-md-6:hover {
            transform: translateY(-8px);
        }

        .py-5.bg-light .col-md-6 h2::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 2.5rem;
            color: #28a745;
            display: block;
            margin-bottom: 1rem;
        }

        .py-5.bg-light .row .col-md-6:first-child h2::before {
            content: '\f140';
            /* Mission icon */
        }

        .py-5.bg-light .row .col-md-6:last-child h2::before {
            content: '\f06e';
            /* Vision icon */
        }

        @media (max-width: 767px) {
            .py-5.bg-light .col-md-6:first-child {
                margin-bottom: 2rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
                // --- START: New Statistics Code ---
                try {
                    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
                    $total_teachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
                    $total_classes = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
                    $upcoming_events_count = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
                } catch (PDOException $e) {
                    // Silently fail or log error, so the public page doesn't break
                    $total_students = 0;
                    $total_teachers = 0;
                    $total_classes = 0;
                    $upcoming_events_count = 0;
                }
                // --- END: New Statistics Code ---
                // --- START: New Leaderboard Code ---
                $classes = $pdo->query("SELECT id, name FROM classes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

                // Get filter values from URL, with defaults
                $filter_class_id = $_GET['filter_class_id'] ?? '';
                $filter_year = $_GET['filter_year'] ?? date('Y');
                $filter_month = $_GET['filter_month'] ?? date('m');

                // --- Top Attendance Logic ---
                $top_attendance_sql = "
    SELECT s.roll_no, c.name as class_name, COUNT(a.id) as present_days
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE a.status = 'Present' AND YEAR(a.attendance_date) = :year AND MONTH(a.attendance_date) = :month
";
                $attendance_params = [':year' => $filter_year, ':month' => $filter_month];
                if (!empty($filter_class_id)) {
                    $top_attendance_sql .= " AND s.class_id = :class_id";
                    $attendance_params[':class_id'] = $filter_class_id;
                }
                $top_attendance_sql .= " GROUP BY s.id ORDER BY present_days DESC LIMIT 5";
                $stmt_att = $pdo->prepare($top_attendance_sql);
                $stmt_att->execute($attendance_params);
                $top_attendance = $stmt_att->fetchAll(PDO::FETCH_ASSOC);

                // --- Top Exam Performers Logic ---
                $top_performers_sql = "
    SELECT s.roll_no, c.name as class_name, AVG((m.marks_obtained / e.max_marks) * 100) as avg_percentage
    FROM marks m
    JOIN students s ON m.student_id = s.id
    JOIN exams e ON m.exam_id = e.id
    JOIN classes c ON s.class_id = c.id
    WHERE e.max_marks > 0 AND YEAR(e.exam_date) = :year AND MONTH(e.exam_date) = :month
";
                $performers_params = [':year' => $filter_year, ':month' => $filter_month];
                if (!empty($filter_class_id)) {
                    $top_performers_sql .= " AND s.class_id = :class_id";
                    $performers_params[':class_id'] = $filter_class_id;
                }
                $top_performers_sql .= " GROUP BY s.id ORDER BY avg_percentage DESC LIMIT 5";
                $stmt_perf = $pdo->prepare($top_performers_sql);
                $stmt_perf->execute($performers_params);
                $top_performers = $stmt_perf->fetchAll(PDO::FETCH_ASSOC);
                // --- END: New Leaderboard Code ---
        ?>
                <section class="hero-section">
                    <div class="container">
                        <h1><?php echo $t['school_name']; ?></h1>
                        <p class="lead"><?php echo $t['mission_text']; ?></p>
                        <a href="?page=admissions&lang=<?php echo $lang; ?>" class="btn btn-lg btn-success"><?php echo $t['apply_now']; ?></a>
                    </div>
                </section>
                <section class="py-5 bg-light text-center">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card shadow-sm h-100 justify-content-center p-3">
                                    <h2 class="display-4 text-success fw-bold"><?php echo htmlspecialchars($total_students); ?>+</h2>
                                    <p class="text-muted mb-0">Enrolled Students</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card shadow-sm h-100 justify-content-center p-3">
                                    <h2 class="display-4 text-success fw-bold"><?php echo htmlspecialchars($total_teachers); ?>+</h2>
                                    <p class="text-muted mb-0">Qualified Teachers</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card shadow-sm h-100 justify-content-center p-3">
                                    <h2 class="display-4 text-success fw-bold"><?php echo htmlspecialchars($total_classes); ?>+</h2>
                                    <p class="text-muted mb-0">Total Classes</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card shadow-sm h-100 justify-content-center p-3">
                                    <h2 class="display-4 text-success fw-bold"><?php echo htmlspecialchars($upcoming_events_count); ?>+</h2>
                                    <p class="text-muted mb-0">Upcoming Events</p>
                                </div>
                            </div>
                        </div>
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
                                    $event_datetime = $event['event_date'] . 'T' . ($event['event_time'] ?? '00:00:00');
                                    echo '<div class="accordion-item">';
                                    echo '  <h2 class="accordion-header" id="heading' . $count . '">';
                                    echo '    <button class="accordion-button' . ($count > 1 ? ' collapsed' : '') . '" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $count . '" aria-expanded="' . ($count === 1 ? 'true' : 'false') . '" aria-controls="collapse' . $count . '">';
                                    echo htmlspecialchars($event['title']) . ' - <span class="ms-2 text-muted">' . date("F j, Y", strtotime($event['event_date'])) . '</span>';
                                    echo '    </button>';
                                    echo '  </h2>';
                                    echo '  <div id="collapse' . $count . '" class="accordion-collapse collapse' . ($count === 1 ? ' show' : '') . '" aria-labelledby="heading' . $count . '" data-bs-parent="#eventsAccordion">';
                                    echo '    <div class="accordion-body">';
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
                <section class="py-5">
                    <div class="container">
                        <h2 class="text-center mb-4">Student Leaderboard</h2>

                        <form action="?page=home&lang=<?php echo $lang; ?>#leaderboard" method="GET" class="card p-3 mb-5 shadow-sm">
                            <input type="hidden" name="page" value="home">
                            <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label for="filter_class_id" class="form-label">Filter by Class</label>
                                    <select name="filter_class_id" id="filter_class_id" class="form-select">
                                        <option value="">All Classes</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" <?php echo ($filter_class_id == $class['id'] ? 'selected' : ''); ?>>
                                                <?php echo htmlspecialchars($class['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter_month" class="form-label">Month</label>
                                    <select name="filter_month" id="filter_month" class="form-select">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($filter_month == $m ? 'selected' : ''); ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter_year" class="form-label">Year</label>
                                    <input type="number" name="filter_year" id="filter_year" class="form-control" value="<?php echo htmlspecialchars($filter_year); ?>" min="2020" max="<?php echo date('Y') + 1; ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success w-100">Apply Filter</button>
                                </div>
                            </div>
                        </form>

                        <a id="leaderboard"></a>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-dark text-white">
                                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Top Attendance</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Rank</th>
                                                    <th scope="col">Roll No.</th>
                                                    <th scope="col">Class</th>
                                                    <th scope="col">Days Present</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($top_attendance)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No data available for this period.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($top_attendance as $index => $student): ?>
                                                        <tr>
                                                            <td><strong>#<?php echo $index + 1; ?></strong></td>
                                                            <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['present_days']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-dark text-white">
                                        <h5 class="mb-0"><i class="fas fa-award me-2"></i>Top Exam Performers</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Rank</th>
                                                    <th scope="col">Roll No.</th>
                                                    <th scope="col">Class</th>
                                                    <th scope="col">Average Score</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($top_performers)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No data available for this period.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($top_performers as $index => $student): ?>
                                                        <tr>
                                                            <td><strong>#<?php echo $index + 1; ?></strong></td>
                                                            <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                                            <td><?php echo round($student['avg_percentage'], 2); ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d105943.4!2d70.530!3d32.985!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38d87f0b4a4b2b1f%3A0x6b772422071a9a3!2sBannu%2C%20Khyber%20Pakhtunkhwa%2C%20Pakistan!5e0!3m2!1sen!2s" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
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
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                $stmt_check_token = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
                $stmt_check_token->execute([$email, $token]);
                $reset_entry_valid = $stmt_check_token->fetch();
                if (!$reset_entry_valid) {
                    echo '<section class="py-5"><div class="container"><div class="alert alert-danger text-center">' . $t['invalid_reset_link'] . '</div></div></section>';
                    break;
                }
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
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'fees_structures' ? 'active' : ''); ?>" href="?page=dashboard&section=fees_structures&lang=<?php echo $lang; ?>"><i class="fas fa-money-check"></i> <?php echo $t['manage_fees_structures']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'fees' ? 'active' : ''); ?>" href="?page=dashboard&section=fees&lang=<?php echo $lang; ?>"><i class="fas fa-money-bill-wave"></i> <?php echo $t['manage_fees']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'reports' ? 'active' : ''); ?>" href="?page=dashboard&section=reports&lang=<?php echo $lang; ?>"><i class="fas fa-chart-line"></i> <?php echo $t['reports']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'user_management' ? 'active' : ''); ?>" href="?page=dashboard&section=user_management&lang=<?php echo $lang; ?>"><i class="fas fa-users-cog"></i> <?php echo $t['user_management']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'admissions_list' ? 'active' : ''); ?>" href="?page=dashboard&section=admissions_list&lang=<?php echo $lang; ?>"><i class="fas fa-file-invoice"></i> Admission Applications</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'contact_messages' ? 'active' : ''); ?>" href="?page=dashboard&section=contact_messages&lang=<?php echo $lang; ?>"><i class="fas fa-envelope"></i> Contact Messages</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'announcements' ? 'active' : ''); ?>" href="?page=dashboard&section=announcements&lang=<?php echo $lang; ?>"><i class="fas fa-bullhorn"></i> Manage Announcements</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'events_admin' ? 'active' : ''); ?>" href="?page=dashboard&section=events_admin&lang=<?php echo $lang; ?>"><i class="fas fa-calendar-check"></i> Manage Events</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'gallery' ? 'active' : ''); ?>" href="?page=dashboard&section=gallery&lang=<?php echo $lang; ?>"><i class="fas fa-images"></i> Gallery Management</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'messages' ? 'active' : ''); ?>" href="?page=dashboard&section=messages&lang=<?php echo $lang; ?>"><i class="fas fa-comments"></i> <?php echo $t['internal_messaging']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'backup_restore' ? 'active' : ''); ?>" href="?page=dashboard&section=backup_restore&lang=<?php echo $lang; ?>"><i class="fas fa-database"></i> <?php echo $t['backup_restore']; ?></a></li>
                            <?php endif; ?>
                            <?php if (isTeacher()) : ?>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'teacher_classes' ? 'active' : ''); ?>" href="?page=dashboard&section=teacher_classes&lang=<?php echo $lang; ?>"><i class="fas fa-school"></i> <?php echo $t['teacher_classes']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'attendance' ? 'active' : ''); ?>" href="?page=dashboard&section=attendance&lang=<?php echo $lang; ?>"><i class="fas fa-check-circle"></i> <?php echo $t['take_class_attendance']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'assignments' ? 'active' : ''); ?>" href="?page=dashboard&section=assignments&lang=<?php echo $lang; ?>"><i class="fas fa-book"></i> <?php echo $t['upload_assignments']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'study_materials' ? 'active' : ''); ?>" href="?page=dashboard&section=study_materials&lang=<?php echo $lang; ?>"><i class="fas fa-book-reader"></i> <?php echo $t['upload_study_materials']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'exams_marks' ? 'active' : ''); ?>" href="?page=dashboard&section=exams_marks&lang=<?php echo $lang; ?>"><i class="fas fa-clipboard-list"></i> <?php echo $t['enter_exam_marks']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'timetable' ? 'active' : ''); ?>" href="?page=dashboard&section=timetable&lang=<?php echo $lang; ?>"><i class="fas fa-calendar-alt"></i> <?php echo $t['view_timetable']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'teacher_notes' ? 'active' : ''); ?>" href="?page=dashboard&section=teacher_notes&lang=<?php echo $lang; ?>"><i class="fas fa-comment-dots"></i> <?php echo $t['teacher_notes']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'messages' ? 'active' : ''); ?>" href="?page=dashboard&section=messages&lang=<?php echo $lang; ?>"><i class="fas fa-comments"></i> <?php echo $t['internal_messaging']; ?></a></li>
                            <?php endif; ?>
                            <?php if (isStudent()) : ?>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'attendance' ? 'active' : ''); ?>" href="?page=dashboard&section=attendance&lang=<?php echo $lang; ?>"><i class="fas fa-check-circle"></i> <?php echo $t['view_attendance']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'timetable' ? 'active' : ''); ?>" href="?page=dashboard&section=timetable&lang=<?php echo $lang; ?>"><i class="fas fa-calendar-alt"></i> <?php echo $t['view_timetable']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'marks' ? 'active' : ''); ?>" href="?page=dashboard&section=marks&lang=<?php echo $lang; ?>"><i class="fas fa-chart-bar"></i> <?php echo $t['view_marks']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'assignments' ? 'active' : ''); ?>" href="?page=dashboard&section=assignments&lang=<?php echo $lang; ?>"><i class="fas fa-download"></i> <?php echo $t['download_assignments']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'study_materials' ? 'active' : ''); ?>" href="?page=dashboard&section=study_materials&lang=<?php echo $lang; ?>"><i class="fas fa-book-open"></i> Study Materials</a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'messages' ? 'active' : ''); ?>" href="?page=dashboard&section=messages&lang=<?php echo $lang; ?>"><i class="fas fa-comments"></i> <?php echo $t['internal_messaging']; ?></a></li>
                            <?php endif; ?>
                            <?php if (isParent()) : ?>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'performance' ? 'active' : ''); ?>" href="?page=dashboard&section=performance&lang=<?php echo $lang; ?>"><i class="fas fa-chart-pie"></i> <?php echo $t['view_performance']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'fees' ? 'active' : ''); ?>" href="?page=dashboard&section=fees&lang=<?php echo $lang; ?>"><i class="fas fa-receipt"></i> <?php echo $t['view_student_fees']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'messages' ? 'active' : ''); ?>" href="?page=dashboard&section=messages&lang=<?php echo $lang; ?>"><i class="fas fa-comments"></i> <?php echo $t['internal_messaging']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo ($section == 'teacher_notes' ? 'active' : ''); ?>" href="?page=dashboard&section=teacher_notes&lang=<?php echo $lang; ?>"><i class="fas fa-clipboard"></i> View Teacher Notes</a></li>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Manually handle the bulk invoice modal to fix the error
            const bulkInvoiceButton = document.getElementById('open-bulk-modal-btn');

            if (bulkInvoiceButton) {
                const bulkModalEl = document.getElementById('addBulkFeeInvoiceModal');

                if (bulkModalEl) {
                    const bulkModal = new bootstrap.Modal(bulkModalEl);
                    bulkInvoiceButton.addEventListener('click', function() {
                        bulkModal.show();
                    });
                } else {
                    // If the button is found but the modal isn't, log an error
                    console.error('Bulk Invoice Modal Error: The button was found, but the modal HTML with id="addBulkFeeInvoiceModal" is missing from the page.');
                }
            }
        });
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
            $('#addAssignmentModal #add_assignment_class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('addAssignmentModal', 'add_assignment_subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });
            $('#editStudyMaterialModal #class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('editStudyMaterialModal', 'subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });
            $('#addStudyMaterialModal #add_study_material_class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('addStudyMaterialModal', 'add_study_material_subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });
            $('#addExamModal #add_exam_class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('addExamModal', 'add_exam_subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });
            $('#addTeacherExamModal #add_teacher_exam_class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('addTeacherExamModal', 'add_teacher_exam_subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });
            $('#editTeacherExamModal #class_id').on('change', function(e, subjectId) {
                updateModalSelectOptions('editTeacherExamModal', 'subject_id', '?page=ajax_data&type=subjects_by_class&lang=<?php echo $lang; ?>', subjectId);
            });
            $('#addFeeInvoiceModal #fee_structure_id').on('change', function() {
                var feeStructureId = $(this).val();
                if (feeStructureId) {
                    $.ajax({
                        url: '?page=ajax_data&type=fee_structure_details&lang=<?php echo $lang; ?>',
                        type: 'GET',
                        data: {
                            fee_structure_id: feeStructureId
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            $('#addFeeInvoiceModal #add_fee_amount').val(data.amount);
                            $('#addFeeInvoiceModal #add_fee_description').val(data.description);
                        }
                    });
                } else {
                    $('#addFeeInvoiceModal #add_fee_amount').val('');
                    $('#addFeeInvoiceModal #add_fee_description').val('');
                }
            });
            $(document).on('click', '.mark-paid-btn', function(e) {
                e.preventDefault();
                var feeId = $(this).data('id');
                var totalAmount = parseFloat($(this).data('amount'));
                var currentPaid = parseFloat($(this).data('paid-amount'));
                var remainingAmount = totalAmount - currentPaid;
                $('#markFeePaidModal #fee_id').val(feeId);
                $('#markFeePaidModal #remaining_amount_display').text(remainingAmount.toFixed(2));
                $('#markFeePaidModal #paid_amount').attr('max', remainingAmount.toFixed(2));
                $('#markFeePaidModal #paid_amount').val(remainingAmount.toFixed(2));
                $('#markFeePaidModal').modal('show');
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
                        } else if (input.is('select') && (key === 'subject_id' || key === 'class_id' || key === 'teacher_id' || key === 'parent_id' || key === 'fee_structure_id')) {
                            input.val(value);
                            if (key === 'class_id' && (type === 'timetable_entry' || type === 'exam' || type === 'assignment' || type === 'study_material')) {
                                input.trigger('change', [data.subject_id]);
                            }
                        } else {
                            input.val(value);
                        }
                    }
                });
                if (type === 'assignment' && data.file_path) {
                    $(modalId + ' #file_path_old').val(data.file_path);
                }
                if (type === 'study_material' && data.file_path) {
                    $(modalId + ' #current_material_path').val(data.file_path);
                }
                if (type === 'student' && data.document_path) {
                    $(modalId + ' #current_document_path').val(data.document_path);
                    $(modalId + ' #current_document_link').attr('href', data.document_path).removeClass('d-none');
                } else if (type === 'student') {
                    $(modalId + ' #current_document_link').addClass('d-none');
                }
                $(modalId + ' #action').val('edit_' + type);
                $(modalId + ' #id').val(data.id);
                $(modalId).modal('show');
            });
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this record?')) {
                    var id = $(this).data('id');
                    var type = $(this).data('type');
                    var csrfToken = $('input[name="csrf_token"]').val();
                    var form = $('<form action="" method="post">' +
                        '<input type="hidden" name="action" value="delete_' + type + '">' +
                        '<input type="hidden" name="id" value="' + id + '">' +
                        '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
                        '</form>');
                    $('body').append(form);
                    form.submit();
                }
            });
            $('#timetable_class_id, #add_assignment_class_id, #add_exam_class_id, #add_study_material_class_id, #add_teacher_exam_class_id').change(function() {
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
            $('#createUserForm, #editUserForm').submit(function() {
                var password = $('#' + $(this).attr('id') + ' #password').val();
                var confirmPassword = $('#' + $(this).attr('id') + ' #confirm_password').val();
                if (password !== confirmPassword) {
                    alert('<?php echo $t['password_mismatch']; ?>');
                    return false;
                }
                return true;
            });
            $(document).on('click', '.view-message-btn', function() {
                var data = $(this).data('json');
                $('#messageModalLabel').text(data.subject);
                $('#messageModalSender').text(data.sender_username + ' (' + data.sender_role + ')');
                $('#messageModalReceiver').text(data.receiver_username + ' (' + data.receiver_role + ')');
                $('#messageModalTimestamp').text(new Date(data.sent_at).toLocaleString());
                $('#messageModalBody').html(data.message.replace(/\n/g, '<br>'));
                // Mark as read
                if (!data.read_status) {
                    $.post('?page=dashboard&section=messages&lang=<?php echo $lang; ?>', {
                        action: 'mark_message_read',
                        message_id: data.id,
                        csrf_token: $('input[name="csrf_token"]').val()
                    }, function(response) {
                        // Optionally update UI for read status
                        location.reload();
                    });
                }
                $('#messageModal').modal('show');
            });
            $(document).on('click', '.reply-message-btn', function() {
                var senderId = $(this).data('sender-id');
                var subject = $(this).data('subject');
                $('#composeMessageModal #receiver_id').val(senderId);
                $('#composeMessageModal #message_subject').val('RE: ' + subject);
                $('#composeMessageModal').modal('show');
            });
            $(document).on('click', '.view-note-btn', function() {
                var data = $(this).data('json');
                $('#teacherNoteModalLabel').text(data.title);
                $('#teacherNoteModalStudent').text(data.student_name);
                $('#teacherNoteModalTeacher').text(data.teacher_name);
                $('#teacherNoteModalDate').text(new Date(data.created_at).toLocaleDateString());
                $('#teacherNoteModalContent').html(data.note.replace(/\n/g, '<br>'));
                $('#teacherNoteModal').modal('show');
            });
            $('#promote_current_class_id').change(function() {
                var selectedClass = $(this).find('option:selected');
                $('#promote_current_class_name').val(selectedClass.text());
            });
            $('#promote_next_class_id').change(function() {
                var selectedClass = $(this).find('option:selected');
                $('#promote_next_class_name').val(selectedClass.text());
            });
            $('#report_card_student_id, #report_card_academic_year').change(function() {
                var studentId = $('#report_card_student_id').val();
                var academicYear = $('#report_card_academic_year').val();
                if (studentId && academicYear) {
                    $.ajax({
                        url: '?page=ajax_data&type=student_full_report_card&lang=<?php echo $lang; ?>',
                        type: 'GET',
                        data: {
                            student_id: studentId,
                            academic_year: academicYear
                        },
                        success: function(response) {
                            $('#report_card_display_area').html(response);
                        },
                        error: function() {
                            $('#report_card_display_area').html('<p>Error loading report card.</p>');
                        }
                    });
                } else {
                    $('#report_card_display_area').html('<p>Select a student and academic year.</p>');
                }
            }).trigger('change');
        });
        $(document).on('click', '.view-details-btn', function() {
            var studentId = $(this).data('student-id');
            var modalContent = $('#studentDetailsContent');
            modalContent.html('<p class="text-center">Loading...</p>');
            $.ajax({
                url: '?page=ajax_data&type=student_full_details&lang=<?php echo $lang; ?>',
                type: 'GET',
                data: {
                    student_id: studentId
                },
                dataType: 'html',
                success: function(response) {
                    modalContent.html(response);
                },
                error: function() {
                    modalContent.html('<p class="alert alert-danger">Error loading student details.</p>');
                }
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
function displayAdminPanel($pdo, $t, $lang)
{
    global $section;
    $csrf = generate_csrf_token();
    switch ($section) {
        case 'students':
            echo '<h3>' . $t['student_list'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3 me-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">' . $t['add_new_student'] . '</button>';
            echo '<button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#promoteStudentsModal">' . $t['promote_students'] . '</button>';
            $stmt = $pdo->query("SELECT s.*, c.name AS class_name, p.username AS parent_username FROM students s LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN users p ON s.parent_id = p.id");
            $students = $stmt->fetchAll();
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['name'] . '</th><th>' . $t['roll_no'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['phone_number'] . '</th><th>Parent</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            if ($students) {
                foreach ($students as $student) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($student['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['roll_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['class_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['phone']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['parent_username'] ?? 'N/A') . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-primary view-details-btn me-1" data-bs-toggle="modal" data-bs-target="#studentDetailsModal" data-student-id="' . $student['id'] . '"><i class="fas fa-eye"></i> ' . $t['view_details'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editStudentModal" data-json=\'' . json_encode($student) . '\' data-form-id="editStudentForm" data-type="student"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $student['id'] . '" data-type="student"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            echo '<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addStudentForm" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addStudentModalLabel">' . $t['add_new_student'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_student">';
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<div class="mb-3"><label for="add_student_medical_history" class="form-label">' . $t['medical_history'] . '</label><textarea class="form-control" id="add_student_medical_history" name="medical_history" rows="3"></textarea></div>';
            echo '<div class="mb-3"><label for="add_student_emergency_contact_name" class="form-label">' . $t['emergency_contact'] . ' ' . $t['guardian_name'] . '</label><input type="text" class="form-control" id="add_student_emergency_contact_name" name="emergency_contact_name"></div>';
            echo '<div class="mb-3"><label for="add_student_emergency_contact_phone" class="form-label">' . $t['emergency_contact'] . ' ' . $t['guardian_phone'] . '</label><input type="text" class="form-control" id="add_student_emergency_contact_phone" name="emergency_contact_phone"></div>';
            echo '<div class="mb-3"><label for="student_document" class="form-label">' . $t['student_document'] . ' (PDF, JPG, PNG)</label><input type="file" class="form-control" id="student_document" name="student_document" accept=".pdf,.jpg,.jpeg,.png"></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_student'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editStudentForm" enctype="multipart/form-data">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editStudentModalLabel">' . $t['edit_student_details'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_student">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="current_document_path" id="current_document_path">';
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
            echo '<div class="mb-3"><label for="medical_history" class="form-label">' . $t['medical_history'] . '</label><textarea class="form-control" id="medical_history" name="medical_history" rows="3"></textarea></div>';
            echo '<div class="mb-3"><label for="emergency_contact_name" class="form-label">' . $t['emergency_contact'] . ' ' . $t['guardian_name'] . '</label><input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"></div>';
            echo '<div class="mb-3"><label for="emergency_contact_phone" class="form-label">' . $t['emergency_contact'] . ' ' . $t['guardian_phone'] . '</label><input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone"></div>';
            echo '<div class="mb-3"><label for="student_document" class="form-label">New ' . $t['student_document'] . ' (PDF, JPG, PNG)</label><input type="file" class="form-control" id="student_document" name="student_document" accept=".pdf,.jpg,.jpeg,.png"></div>';
            echo '<div class="mb-3"><label class="form-label">Current Document:</label> <a href="#" id="current_document_link" target="_blank" class="d-none">View Current Document</a></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="modal fade" id="promoteStudentsModal" tabindex="-1" aria-labelledby="promoteStudentsModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="promoteStudentsModalLabel">' . $t['promote_students'] . ' ' . $t['next_academic_year'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="promote_students">';
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<div class="mb-3"><label for="promote_current_class_id" class="form-label">Promote From ' . $t['class'] . '</label><select class="form-select" id="promote_current_class_id" name="current_class_id" required>';
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select><input type="hidden" name="current_class_name" id="promote_current_class_name" value=""></div>';
            echo '<div class="mb-3"><label for="promote_next_class_id" class="form-label">' . $t['promote_class'] . ' ' . $t['to_class'] . '</label><select class="form-select" id="promote_next_class_id" name="next_class_id" required>';
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select><input type="hidden" name="next_class_name" id="promote_next_class_name" value=""></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['promote'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog modal-xl">';
            echo '<div class="modal-content">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="studentDetailsModalLabel">Student Details</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<div id="studentDetailsContent"><p class="text-center">Loading...</p></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '</div>';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
            foreach ($classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
            echo '<option value="">Select Class First</option>';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<thead><tr><th>ID</th><th>' . $t['exam_name'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['subject'] . '</th><th>Teacher</th><th>' . $t['exam_date'] . '</th><th>' . $t['max_marks'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT e.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name FROM exams e JOIN classes c ON e.class_id = c.id JOIN subjects s ON e.subject_id = s.id LEFT JOIN teachers t ON e.teacher_id = t.id ORDER BY exam_date DESC");
            $exams = $stmt->fetchAll();
            $all_classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
            $all_subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
            $all_teachers = $pdo->query("SELECT id, name FROM teachers")->fetchAll();
            foreach ($exams as $exam) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($exam['id']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['name']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['class_name']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['subject_name']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['teacher_name'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($exam['exam_date']) . '</td>';
                echo '<td>' . htmlspecialchars($exam['max_marks']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editExamModal" data-json=\'' . json_encode($exam) . '\' data-form-id="editExamForm" data-type="exam"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $exam['id'] . '" data-type="exam"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$exams) {
                echo '<tr><td colspan="8" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
            echo '<div class="mb-3"><label for="add_exam_teacher_id" class="form-label">Teacher</label><select class="form-select" id="add_exam_teacher_id" name="teacher_id"><option value="">None</option>';
            foreach ($all_teachers as $teacher) {
                echo '<option value="' . $teacher['id'] . '">' . htmlspecialchars($teacher['name']) . '</option>';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<div class="mb-3"><label for="name" class="form-label">' . $t['exam_name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
            echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
            foreach ($all_classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
            echo '<option value="">Select Class First</option>';
            echo '</select></div>';
            echo '<div class="mb-3"><label for="teacher_id" class="form-label">Teacher</label><select class="form-select" id="teacher_id" name="teacher_id"><option value="">None</option>';
            foreach ($all_teachers as $teacher) {
                echo '<option value="' . $teacher['id'] . '">' . htmlspecialchars($teacher['name']) . '</option>';
            }
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
        case 'fees_structures':
            echo '<h3>' . $t['manage_fees_structures'] . '</h3>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addFeeStructureModal">' . $t['add_fee_structure'] . '</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>' . $t['fee_structure_name'] . '</th><th>' . $t['amount'] . '</th><th>' . $t['fee_type'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['description'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT fs.*, c.name AS class_name FROM fee_structures fs LEFT JOIN classes c ON fs.class_id = c.id ORDER BY fs.name");
            $fee_structures = $stmt->fetchAll();
            $all_classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
            if ($fee_structures) {
                foreach ($fee_structures as $structure) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($structure['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($structure['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($structure['amount']) . '</td>';
                    echo '<td>' . htmlspecialchars($structure['type']) . '</td>';
                    echo '<td>' . htmlspecialchars($structure['class_name'] ?? 'All Classes') . '</td>';
                    echo '<td>' . htmlspecialchars($structure['description'] ?? 'N/A') . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editFeeStructureModal" data-json=\'' . json_encode($structure) . '\' data-form-id="editFeeStructureForm" data-type="fee_structure"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $structure['id'] . '" data-type="fee_structure"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            echo '<div class="modal fade" id="addFeeStructureModal" tabindex="-1" aria-labelledby="addFeeStructureModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="addFeeStructureForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="addFeeStructureModalLabel">' . $t['add_fee_structure'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="add_fee_structure">';
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<div class="mb-3"><label for="add_fee_structure_name" class="form-label">' . $t['fee_structure_name'] . '</label><input type="text" class="form-control" id="add_fee_structure_name" name="name" required></div>';
            echo '<div class="mb-3"><label for="add_fee_structure_amount" class="form-label">' . $t['amount'] . '</label><input type="number" step="0.01" class="form-control" id="add_fee_structure_amount" name="amount" required></div>';
            echo '<div class="mb-3"><label for="add_fee_structure_type" class="form-label">' . $t['fee_type'] . '</label><select class="form-select" id="add_fee_structure_type" name="type" required><option value="Tuition">Tuition</option><option value="Transport">Transport</option><option value="Lab">Lab</option><option value="Exam">Exam</option><option value="Other">Other</option></select></div>';
            echo '<div class="mb-3"><label for="add_fee_structure_class_id" class="form-label">' . $t['class_name'] . ' (Optional)</label><select class="form-select" id="add_fee_structure_class_id" name="class_id"><option value="">All Classes</option>';
            foreach ($all_classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_fee_structure_description" class="form-label">' . $t['fee_structure_description'] . '</label><textarea class="form-control" id="add_fee_structure_description" name="description" rows="3"></textarea></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-primary">' . $t['add_fee_structure'] . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="modal fade" id="editFeeStructureModal" tabindex="-1" aria-labelledby="editFeeStructureModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST" id="editFeeStructureForm">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="editFeeStructureModalLabel">' . $t['edit'] . ' ' . $t['fee_structure_details'] . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" id="action" value="edit_fee_structure">';
            echo '<input type="hidden" name="id" id="id">';
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<div class="mb-3"><label for="name" class="form-label">' . $t['fee_structure_name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
            echo '<div class="mb-3"><label for="amount" class="form-label">' . $t['amount'] . '</label><input type="number" step="0.01" class="form-control" id="amount" name="amount" required></div>';
            echo '<div class="mb-3"><label for="type" class="form-label">' . $t['fee_type'] . '</label><select class="form-select" id="type" name="type" required><option value="Tuition">Tuition</option><option value="Transport">Transport</option><option value="Lab">Lab</option><option value="Exam">Exam</option><option value="Other">Other</option></select></div>';
            echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['class_name'] . ' (Optional)</label><select class="form-select" id="class_id" name="class_id"><option value="">All Classes</option>';
            foreach ($all_classes as $class) {
                echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="description" class="form-label">' . $t['fee_structure_description'] . '</label><textarea class="form-control" id="description" name="description" rows="3"></textarea></div>';
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
        case 'fees':
            echo '<h3>' . $t['manage_fees'] . '</h3>';
            echo '<h4>' . $t['fee_invoices'] . '</h4>';
            echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addFeeInvoiceModal">' . $t['add_new_invoice'] . '</button>';
            echo '<button type="button" class="btn btn-info mb-3 ms-2" id="open-bulk-modal-btn">Generate Bulk Invoice</button>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>ID</th><th>Student</th><th>Fee Item</th><th>' . $t['amount'] . '</th><th>' . $t['concession'] . '</th><th>' . $t['fine'] . '</th><th>Net Amount</th><th>Paid Amount</th><th>' . $t['due_date_invoice'] . '</th><th>' . $t['paid_date'] . '</th><th>' . $t['invoice_status'] . '</th><th>Description</th><th>' . $t['actions'] . '</th></tr></thead>';
            echo '<tbody>';
            $stmt = $pdo->query("SELECT f.*, s.name AS student_name, fs.name AS fee_structure_name, (f.amount - f.concession + f.fine) AS net_amount, (SELECT SUM(amount_paid) FROM fee_transactions WHERE fee_id = f.id) AS total_paid FROM fees f JOIN students s ON f.student_id = s.id LEFT JOIN fee_structures fs ON f.fee_structure_id = fs.id ORDER BY due_date ASC");
            $fees = $stmt->fetchAll();
            $all_students = $pdo->query("SELECT id, name FROM students")->fetchAll();
            $fee_structures_list = $pdo->query("SELECT id, name, amount, description FROM fee_structures")->fetchAll();
            if ($fees) {
                foreach ($fees as $fee) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($fee['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['student_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['fee_structure_name'] ?? 'Custom Fee') . '</td>';
                    echo '<td>' . htmlspecialchars($fee['amount']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['concession']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['fine']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['net_amount']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['total_paid'] ?? 0) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['due_date']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['paid_date'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($fee['status']) . '</td>';
                    echo '<td>' . htmlspecialchars($fee['description']) . '</td>';
                    echo '<td>';
                    if ($fee['status'] != 'Paid' && ($fee['net_amount'] - ($fee['total_paid'] ?? 0)) > 0) {
                        echo '<button type="button" class="btn btn-sm btn-success mark-paid-btn me-1" data-id="' . $fee['id'] . '" data-amount="' . $fee['net_amount'] . '" data-paid-amount="' . ($fee['total_paid'] ?? 0) . '"><i class="fas fa-money-check-alt"></i>Collect</button>';
                    }
                    echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editFeeInvoiceModal" data-json=\'' . json_encode($fee) . '\' data-form-id="editFeeInvoiceForm" data-type="fee_invoice"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                    echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $fee['id'] . '" data-type="fee_invoice"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="13" class="text-center">' . $t['no_records'] . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<div class="mb-3"><label for="add_fee_student_id" class="form-label">Student</label><select class="form-select" id="add_fee_student_id" name="student_id" required>';
            foreach ($all_students as $student) {
                echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="fee_structure_id" class="form-label">' . $t['select_fee_structure'] . ' (Optional)</label><select class="form-select" id="fee_structure_id" name="fee_structure_id"><option value="">Custom Fee</option>';
            foreach ($fee_structures_list as $fs) {
                echo '<option value="' . $fs['id'] . '">' . htmlspecialchars($fs['name']) . ' (' . htmlspecialchars($fs['amount']) . ')</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="add_fee_amount" class="form-label">' . $t['amount'] . '</label><input type="number" step="0.01" class="form-control" id="add_fee_amount" name="amount" required></div>';
            echo '<div class="mb-3"><label for="add_fee_concession" class="form-label">' . $t['concession'] . '</label><input type="number" step="0.01" class="form-control" id="add_fee_concession" name="concession" value="0.00"></div>';
            echo '<div class="mb-3"><label for="add_fee_fine" class="form-label">' . $t['fine'] . '</label><input type="number" step="0.01" class="form-control" id="add_fee_fine" name="fine" value="0.00"></div>';
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
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<div class="mb-3"><label for="student_id" class="form-label">Student</label><select class="form-select" id="student_id" name="student_id" required>';
            foreach ($all_students as $student) {
                echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="fee_structure_id" class="form-label">' . $t['select_fee_structure'] . ' (Optional)</label><select class="form-select" id="fee_structure_id" name="fee_structure_id"><option value="">Custom Fee</option>';
            foreach ($fee_structures_list as $fs) {
                echo '<option value="' . $fs['id'] . '">' . htmlspecialchars($fs['name']) . ' (' . htmlspecialchars($fs['amount']) . ')</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="amount" class="form-label">' . $t['amount'] . '</label><input type="number" step="0.01" class="form-control" id="amount" name="amount" required></div>';
            echo '<div class="mb-3"><label for="concession" class="form-label">' . $t['concession'] . '</label><input type="number" step="0.01" class="form-control" id="concession" name="concession"></div>';
            echo '<div class="mb-3"><label for="fine" class="form-label">' . $t['fine'] . '</label><input type="number" step="0.01" class="form-control" id="fine" name="fine"></div>';
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
            echo '<div class="modal fade" id="markFeePaidModal" tabindex="-1" aria-labelledby="markFeePaidModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog">';
            echo '<div class="modal-content">';
            echo '<form action="" method="POST">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="markFeePaidModalLabel">Collect Fee Payment</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="action" value="mark_fee_paid">';
            echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="id" id="fee_id">';
            echo '<div class="mb-3"><strong>Remaining Amount:</strong> <span id="remaining_amount_display"></span></div>';
            echo '<div class="mb-3"><label for="paid_amount" class="form-label">Amount to Collect</label><input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" min="0" required></div>';
            echo '</div>';
            echo '<div class="modal-footer">';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            echo '<button type="submit" class="btn btn-success">Record Payment</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            $fee_structures_list = $pdo->query("SELECT id, name, amount, description FROM fee_structures ORDER BY name")->fetchAll();
            $all_classes_for_modal = $pdo->query("SELECT id, name FROM classes")->fetchAll();
?>
            <div class="modal fade" id="addBulkFeeInvoiceModal" tabindex="-1" aria-labelledby="addBulkFeeInvoiceModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="" method="POST" id="addBulkFeeInvoiceForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addBulkFeeInvoiceModalLabel">Generate Bulk Fee Invoices</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="bulk_add_fee_invoice">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <div class="mb-3">
                                    <label for="bulk_class_id" class="form-label">For Class</label>
                                    <select class="form-select" id="bulk_class_id" name="class_id" required>
                                        <?php foreach ($all_classes_for_modal as $class) : ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="bulk_fee_structure_id" class="form-label"><?php echo $t['select_fee_structure']; ?></label>
                                    <select class="form-select" id="bulk_fee_structure_id" name="fee_structure_id">
                                        <option value="">Custom Fee</option>
                                        <?php foreach ($fee_structures_list as $fs) : ?>
                                            <option value="<?php echo $fs['id']; ?>" data-amount="<?php echo $fs['amount']; ?>" data-description="<?php echo htmlspecialchars($fs['description'] ?? $fs['name']); ?>"><?php echo htmlspecialchars($fs['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="bulk_amount" class="form-label"><?php echo $t['amount']; ?></label>
                                    <input type="number" step="0.01" class="form-control" id="bulk_amount" name="amount" required>
                                </div>
                                <div class="mb-3">
                                    <label for="bulk_description" class="form-label">Description (e.g., Tuition Fee for Oct 2025)</label>
                                    <input type="text" class="form-control" id="bulk_description" name="description" required>
                                </div>
                                <div class="mb-3">
                                    <label for="bulk_due_date" class="form-label"><?php echo $t['due_date_invoice']; ?></label>
                                    <input type="date" class="form-control" id="bulk_due_date" name="due_date" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Generate Invoices</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script>
                // Helper script for the bulk invoice modal
                $('#bulk_fee_structure_id').on('change', function() {
                    var selectedOption = $(this).find('option:selected');
                    var amount = selectedOption.data('amount');
                    var description = selectedOption.data('description');
                    if (amount) {
                        $('#bulk_amount').val(amount);
                    }
                    if (description) {
                        $('#bulk_description').val(description);
                    }
                });
            </script><?php
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
                            $sql = "SELECT f.*, s.name AS student_name, fs.name AS fee_structure_name, (f.amount - f.concession + f.fine) AS net_amount, (SELECT SUM(amount_paid) FROM fee_transactions WHERE fee_id = f.id) AS total_paid FROM fees f JOIN students s ON f.student_id = s.id LEFT JOIN fee_structures fs ON f.fee_structure_id = fs.id WHERE 1=1";
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
                            echo '<thead><tr><th>Student</th><th>Fee Item</th><th>' . $t['amount'] . '</th><th>' . $t['concession'] . '</th><th>' . $t['fine'] . '</th><th>Net Amount</th><th>Paid Amount</th><th>' . $t['due_date_invoice'] . '</th><th>' . $t['paid_date'] . '</th><th>' . $t['invoice_status'] . '</th><th>Description</th></tr></thead>';
                            echo '<tbody>';
                            if ($report_data_fees) {
                                foreach ($report_data_fees as $row) {
                                    echo '<tr><td>' . htmlspecialchars($row['student_name']) . '</td><td>' . htmlspecialchars($row['fee_structure_name'] ?? 'Custom Fee') . '</td><td>' . htmlspecialchars($row['amount']) . '</td><td>' . htmlspecialchars($row['concession']) . '</td><td>' . htmlspecialchars($row['fine']) . '</td><td>' . htmlspecialchars($row['net_amount']) . '</td><td>' . htmlspecialchars($row['total_paid'] ?? 0) . '</td><td>' . htmlspecialchars($row['due_date']) . '</td><td>' . htmlspecialchars($row['paid_date'] ?? 'N/A') . '</td><td>' . htmlspecialchars($row['status']) . '</td><td>' . htmlspecialchars($row['description']) . '</td></tr>';
                                }
                            } else {
                                echo '<tr><td colspan="11" class="text-center">' . $t['no_records'] . '</td></tr>';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                            echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="add_announcement_title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="add_announcement_title" name="title" required></div>';
                        echo '<div class="mb-3"><label for="add_announcement_content" class="form-label">Content</label><textarea class="form-control" id="add_announcement_content" name="content" rows="5" required></textarea></div>';
                        echo '<div class="mb-3"><label for="add_announcement_file" class="form-label">Attachment File (PDF, DOCX, JPG, PNG)</label><input type="file" class="form-control" id="add_announcement_file" name="announcement_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['add_news'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="title" name="title" required></div>';
                        echo '<div class="mb-3"><label for="content" class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="5" required></textarea></div>';
                        echo '<input type="hidden" name="file_path_old" id="file_path_old">';
                        echo '<div class="mb-3"><label for="announcement_file" class="form-label">New Attachment (optional, PDF, DOCX, JPG, PNG)</label><input type="file" class="form-control" id="announcement_file" name="announcement_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"></div>';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="add_image_title" class="form-label">' . $t['image_title'] . '</label><input type="text" class="form-control" id="add_image_title" name="title" required></div>';
                        echo '<div class="mb-3"><label for="image_file" class="form-label">' . $t['image'] . ' (JPG, PNG, GIF)</label><input type="file" class="form-control" id="image_file" name="image_file" accept="image/*" required></div>';
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
                    case 'messages':
                        echo '<h3>' . $t['internal_messaging'] . '</h3>';
                        echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#composeMessageModal">' . $t['compose_new_message'] . '</button>';
                        echo '<h4>' . $t['my_messages'] . '</h4>';
                        echo '<ul class="nav nav-tabs mb-3" id="messageTabs" role="tablist">';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab" aria-controls="inbox" aria-selected="true">Inbox</button></li>';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab" aria-controls="sent" aria-selected="false">' . $t['sent_messages'] . '</button></li>';
                        echo '</ul>';
                        echo '<div class="tab-content" id="messageTabContent">';
                        echo '<div class="tab-pane fade show active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">';
                        echo '<h5>Unread Messages</h5>';
                        $stmt_inbox_unread = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = FALSE ORDER BY m.sent_at DESC");
                        $stmt_inbox_unread->execute([$_SESSION['id']]);
                        $inbox_unread_messages = $stmt_inbox_unread->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive mb-3">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_unread_messages) {
                            foreach ($inbox_unread_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-primary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No unread messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<h5>Read Messages</h5>';
                        $stmt_inbox_read = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = TRUE ORDER BY m.sent_at DESC");
                        $stmt_inbox_read->execute([$_SESSION['id']]);
                        $inbox_read_messages = $stmt_inbox_read->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_read_messages) {
                            foreach ($inbox_read_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No read messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="tab-pane fade" id="sent" role="tabpanel" aria-labelledby="sent-tab">';
                        echo '<h5>Sent Messages</h5>';
                        $stmt_sent = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.sender_id = ? ORDER BY m.sent_at DESC");
                        $stmt_sent->execute([$_SESSION['id']]);
                        $sent_messages = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Receiver</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($sent_messages) {
                            foreach ($sent_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['receiver_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td>' . ($message_data['read_status'] ? 'Read' : 'Unread') . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['view'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No sent messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="composeMessageModal" tabindex="-1" aria-labelledby="composeMessageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="composeMessageModalLabel">' . $t['compose_new_message'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="send_internal_message">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="receiver_id" class="form-label">' . $t['receiver'] . '</label><select class="form-select" id="receiver_id" name="receiver_id" required>';
                        echo '<option value="">' . $t['select_receiver'] . '</option>';
                        $all_users = $pdo->prepare("SELECT id, username, role FROM users WHERE id != ? ORDER BY role, username");
                        $all_users->execute([$_SESSION['id']]);
                        $users_for_message = $all_users->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($users_for_message as $user_msg) {
                            echo '<option value="' . $user_msg['id'] . '">' . htmlspecialchars($user_msg['username']) . ' (' . htmlspecialchars($user_msg['role']) . ')</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="message_subject" class="form-label">' . $t['subject'] . '</label><input type="text" class="form-control" id="message_subject" name="subject" required></div>';
                        echo '<div class="mb-3"><label for="message_content" class="form-label">' . $t['message'] . '</label><textarea class="form-control" id="message_content" name="message_content" rows="5" required></textarea></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['send'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog modal-lg">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="messageModalLabel">Message Subject</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<p><strong>From:</strong> <span id="messageModalSender"></span></p>';
                        echo '<p><strong>To:</strong> <span id="messageModalReceiver"></span></p>';
                        echo '<p><strong>Date:</strong> <span id="messageModalTimestamp"></span></p>';
                        echo '<hr>';
                        echo '<div id="messageModalBody"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '</div>';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<button type="submit" class="btn btn-success"><i class="fas fa-download"></i> ' . $t['export_data'] . '</button>';
                        echo '</form>';
                        echo '</div>';
                        echo '<div class="card p-4">';
                        echo '<h4>' . $t['import_data'] . '</h4>';
                        echo '<p>Upload a previously exported JSON file to restore or replace current school data. <strong class="text-danger">Warning: This will overwrite ALL existing data!</strong></p>';
                        echo '<form action="" method="POST" enctype="multipart/form-data">';
                        echo '<input type="hidden" name="action" value="import_data">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                $csrf = generate_csrf_token();
                $teacher_id_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
                $teacher_id_stmt->execute([$_SESSION['id']]);
                $teacher_db_id = $teacher_id_stmt->fetchColumn();
                if (!$teacher_db_id) {
                    echo '<p class="alert alert-danger">Teacher record not found. Please contact administrator.</p>';
                    return;
                }
                $classes_taught_stmt = $pdo->prepare("SELECT id, name FROM classes WHERE teacher_id = ? ORDER BY name");
                $classes_taught_stmt->execute([$teacher_db_id]);
                $classes_taught_list = $classes_taught_stmt->fetchAll();
                switch ($section) {
                    case 'teacher_classes':
                        echo '<h3>' . $t['teacher_classes'] . '</h3>';
                        echo '<div class="accordion" id="teacherClassesAccordion">';
                        if ($classes_taught_list) {
                            $counter = 0;
                            foreach ($classes_taught_list as $class) {
                                $counter++;
                                $stmt_students_in_class = $pdo->prepare("SELECT id, name, roll_no FROM students WHERE class_id = ? ORDER BY name");
                                $stmt_students_in_class->execute([$class['id']]);
                                $students_in_class = $stmt_students_in_class->fetchAll();
                                echo '<div class="accordion-item">';
                                echo '  <h2 class="accordion-header" id="heading' . $class['id'] . '">';
                                echo '    <button class="accordion-button' . ($counter > 1 ? ' collapsed' : '') . '" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $class['id'] . '" aria-expanded="' . ($counter === 1 ? 'true' : 'false') . '" aria-controls="collapse' . $class['id'] . '">';
                                echo htmlspecialchars($class['name']) . ' - ' . count($students_in_class) . ' Students';
                                echo '    </button>';
                                echo '  </h2>';
                                echo '  <div id="collapse' . $class['id'] . '" class="accordion-collapse collapse' . ($counter === 1 ? ' show' : '') . '" aria-labelledby="heading' . $class['id'] . '" data-bs-parent="#teacherClassesAccordion">';
                                echo '    <div class="accordion-body">';
                                if ($students_in_class) {
                                    echo '<h5>' . $t['view_all_students_in_class'] . '</h5>';
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-bordered table-striped table-sm">';
                                    echo '<thead><tr><th>' . $t['student_name'] . '</th><th>' . $t['roll_no'] . '</th></tr></thead>';
                                    echo '<tbody>';
                                    foreach ($students_in_class as $student) {
                                        echo '<tr><td>' . htmlspecialchars($student['name']) . '</td><td>' . htmlspecialchars($student['roll_no']) . '</td></tr>';
                                    }
                                    echo '</tbody></table>';
                                    echo '</div>';
                                } else {
                                    echo '<p>No students assigned to this class.</p>';
                                }
                                echo '    </div>';
                                echo '  </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>' . $t['no_records'] . '</p>';
                        }
                        echo '</div>';
                        break;
                    case 'attendance':
                        echo '<h3>' . $t['take_class_attendance'] . '</h3>';
                        echo '<form action="" method="POST" class="mb-4">';
                        echo '<input type="hidden" name="action" value="save_attendance">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="row mb-3">';
                        echo '<div class="col-md-4"><label for="attendance_class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="attendance_class_id" name="class_id" required>';
                        echo '<option value="">' . $t['select_class'] . '</option>';
                        foreach ($classes_taught_list as $class) {
                            echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="col-md-4"><label for="attendance_subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="attendance_subject_id" name="subject_id">';
                        echo '<option value="">' . $t['select_subject'] . '</option>';
                        $all_subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
                        foreach ($all_subjects as $subject) {
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
                        $stmt = $pdo->prepare("SELECT a.*, c.name AS class_name, s.name AS subject_name FROM assignments a JOIN classes c ON a.class_id = c.id JOIN subjects s ON a.subject_id = s.id WHERE a.teacher_id = ? ORDER BY due_date DESC");
                        $stmt->execute([$teacher_db_id]);
                        $assignments = $stmt->fetchAll();
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
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
                        echo '<div class="mb-3"><label for="assignment_file" class="form-label">' . $t['upload_file'] . ' (PDF, DOCX, JPG, PNG)</label><input type="file" class="form-control" id="assignment_file" name="assignment_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['upload_assignment'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<input type="hidden" name="file_path_old" id="file_path_old">';
                        echo '<div class="mb-3"><label for="title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="title" name="title" required></div>';
                        echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
                        foreach ($classes_taught_list as $class) {
                            echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
                        echo '<option value="">Select Class First</option>';
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="description" class="form-label">' . $t['description'] . '</label><textarea class="form-control" id="description" name="description"></textarea></div>';
                        echo '<div class="mb-3"><label for="due_date" class="form-label">' . $t['due_date'] . '</label><input type="date" class="form-control" id="due_date" name="due_date" required></div>';
                        echo '<div class="mb-3"><label for="assignment_file" class="form-label">' . $t['upload_file'] . ' (Leave blank to keep current file)</label><input type="file" class="form-control" id="assignment_file" name="assignment_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
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
                    case 'study_materials':
                        echo '<h3>' . $t['study_materials_list'] . '</h3>';
                        echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addStudyMaterialModal">' . $t['add_study_material'] . '</button>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>ID</th><th>' . $t['title'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['subject'] . '</th><th>Description</th><th>File</th><th>' . $t['actions'] . '</th></tr></thead>';
                        echo '<tbody>';
                        $stmt = $pdo->prepare("SELECT sm.*, c.name AS class_name, s.name AS subject_name FROM study_materials sm JOIN classes c ON sm.class_id = c.id JOIN subjects s ON sm.subject_id = s.id WHERE sm.teacher_id = ? ORDER BY uploaded_at DESC");
                        $stmt->execute([$teacher_db_id]);
                        $materials = $stmt->fetchAll();
                        $all_subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
                        if ($materials) {
                            foreach ($materials as $material) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($material['id']) . '</td>';
                                echo '<td>' . htmlspecialchars($material['title']) . '</td>';
                                echo '<td>' . htmlspecialchars($material['class_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($material['subject_name']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($material['description']), 0, 50) . '...</td>';
                                echo '<td><a href="' . htmlspecialchars($material['file_path']) . '" target="_blank">Download</a></td>';
                                echo '<td>';
                                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editStudyMaterialModal" data-json=\'' . json_encode($material) . '\' data-form-id="editStudyMaterialForm" data-type="study_material"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $material['id'] . '" data-type="study_material"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">' . $t['no_records'] . '</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<div class="modal fade" id="addStudyMaterialModal" tabindex="-1" aria-labelledby="addStudyMaterialModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST" id="addStudyMaterialForm" enctype="multipart/form-data">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="addStudyMaterialModalLabel">' . $t['add_study_material'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="add_study_material">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="add_study_material_title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="add_study_material_title" name="title" required></div>';
                        echo '<div class="mb-3"><label for="add_study_material_class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="add_study_material_class_id" name="class_id" required>';
                        foreach ($classes_taught_list as $class) {
                            echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="add_study_material_subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="add_study_material_subject_id" name="subject_id" required>';
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
                        echo '<div class="mb-3"><label for="add_study_material_description" class="form-label">' . $t['description'] . '</label><textarea class="form-control" id="add_study_material_description" name="description"></textarea></div>';
                        echo '<div class="mb-3"><label for="material_file" class="form-label">' . $t['upload_file'] . ' (PDF, DOCX, JPG, PNG)</label><input type="file" class="form-control" id="material_file" name="material_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" required></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['add_study_material'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="editStudyMaterialModal" tabindex="-1" aria-labelledby="editStudyMaterialModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST" id="editStudyMaterialForm" enctype="multipart/form-data">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="editStudyMaterialModalLabel">' . $t['edit'] . ' Study Material</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" id="action" value="edit_study_material">';
                        echo '<input type="hidden" name="id" id="id">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<input type="hidden" name="current_material_path" id="current_material_path">';
                        echo '<div class="mb-3"><label for="title" class="form-label">' . $t['title'] . '</label><input type="text" class="form-control" id="title" name="title" required></div>';
                        echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['select_class'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
                        foreach ($classes_taught_list as $class) {
                            echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['select_subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
                        echo '<option value="">Select Class First</option>';
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="description" class="form-label">' . $t['description'] . '</label><textarea class="form-control" id="description" name="description"></textarea></div>';
                        echo '<div class="mb-3"><label for="material_file" class="form-label">' . $t['upload_file'] . ' (Leave blank to keep current file)</label><input type="file" class="form-control" id="material_file" name="material_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"></div>';
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
                    case 'exams_marks':
                        echo '<h3>' . $t['manage_my_exams'] . '</h3>';
                        echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addTeacherExamModal">' . $t['add_exam_for_my_class'] . '</button>';
                        echo '<div class="table-responsive mb-4">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>ID</th><th>' . $t['exam_name'] . '</th><th>' . $t['class_name'] . '</th><th>' . $t['subject'] . '</th><th>' . $t['exam_date'] . '</th><th>' . $t['max_marks'] . '</th><th>' . $t['actions'] . '</th></tr></thead>';
                        echo '<tbody>';
                        $stmt = $pdo->prepare("SELECT e.*, c.name AS class_name, s.name AS subject_name FROM exams e JOIN classes c ON e.class_id = c.id JOIN subjects s ON e.subject_id = s.id WHERE e.teacher_id = ? ORDER BY exam_date DESC");
                        $stmt->execute([$teacher_db_id]);
                        $exams_by_me = $stmt->fetchAll();
                        $all_subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();
                        if ($exams_by_me) {
                            foreach ($exams_by_me as $exam) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($exam['id']) . '</td>';
                                echo '<td>' . htmlspecialchars($exam['name']) . '</td>';
                                echo '<td>' . htmlspecialchars($exam['class_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($exam['subject_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($exam['exam_date']) . '</td>';
                                echo '<td>' . htmlspecialchars($exam['max_marks']) . '</td>';
                                echo '<td>';
                                echo '<button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#enterMarksModal" data-exam-id="' . $exam['id'] . '">' . $t['enter_marks'] . '</button>';
                                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editTeacherExamModal" data-json=\'' . json_encode($exam) . '\' data-form-id="editTeacherExamForm" data-type="teacher_exam"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $exam['id'] . '" data-type="teacher_exam"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">' . $t['no_records'] . '</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<div class="modal fade" id="addTeacherExamModal" tabindex="-1" aria-labelledby="addTeacherExamModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST" id="addTeacherExamForm">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="addTeacherExamModalLabel">' . $t['add_exam_for_my_class'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="teacher_add_exam">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="add_teacher_exam_name" class="form-label">' . $t['exam_name'] . '</label><input type="text" class="form-control" id="add_teacher_exam_name" name="name" required></div>';
                        echo '<div class="mb-3"><label for="add_teacher_exam_class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="add_teacher_exam_class_id" name="class_id" required>';
                        foreach ($classes_taught_list as $class) {
                            echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="add_teacher_exam_subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="add_teacher_exam_subject_id" name="subject_id" required>';
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
                        echo '<div class="mb-3"><label for="add_teacher_exam_date" class="form-label">' . $t['exam_date'] . '</label><input type="date" class="form-control" id="add_teacher_exam_date" name="exam_date" required></div>';
                        echo '<div class="mb-3"><label for="add_teacher_exam_max_marks" class="form-label">' . $t['max_marks'] . '</label><input type="number" class="form-control" id="add_teacher_exam_max_marks" name="max_marks" required></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['add_exam'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="editTeacherExamModal" tabindex="-1" aria-labelledby="editTeacherExamModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST" id="editTeacherExamForm">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="editTeacherExamModalLabel">' . $t['edit'] . ' ' . $t['exam_name'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" id="action" value="teacher_edit_exam">';
                        echo '<input type="hidden" name="id" id="id">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="name" class="form-label">' . $t['exam_name'] . '</label><input type="text" class="form-control" id="name" name="name" required></div>';
                        echo '<div class="mb-3"><label for="class_id" class="form-label">' . $t['class_name'] . '</label><select class="form-select" id="class_id" name="class_id" required>';
                        foreach ($classes_taught_list as $class) {
                            echo '<option value="' . $class['id'] . '">' . htmlspecialchars($class['name']) . '</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="subject_id" class="form-label">' . $t['subject'] . '</label><select class="form-select" id="subject_id" name="subject_id" required>';
                        echo '<option value="">Select Class First</option>';
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
                        echo '<div class="modal fade" id="enterMarksModal" tabindex="-1" aria-labelledby="enterMarksModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST" id="enterMarksForm">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="enterMarksModalLabel">' . $t['enter_exam_marks'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="save_marks">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<input type="hidden" name="exam_id" id="marks_exam_id_modal">';
                        echo '<div id="student_marks_list_modal">';
                        echo '<p>Loading students...</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['save_marks'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<script>';
                        echo '$(document).on("click", "[data-bs-target=\"#enterMarksModal\"]", function() {';
                        echo '    var examId = $(this).data("exam-id");';
                        echo '    $("#marks_exam_id_modal").val(examId);';
                        echo '    $.ajax({';
                        echo '        url: "?page=ajax_data&type=marks_students&lang=' . $lang . '",';
                        echo '        type: "GET",';
                        echo '        data: { exam_id: examId },';
                        echo '        success: function(response) {';
                        echo '            $("#student_marks_list_modal").html(response);';
                        echo '        }';
                        echo '    });';
                        echo '});';
                        echo '</script>';
                        break;
                    case 'timetable':
                        echo '<h3>' . $t['view_timetable'] . '</h3>';
                        $stmt = $pdo->prepare("SELECT tt.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name FROM timetables tt JOIN classes c ON tt.class_id = c.id JOIN subjects s ON tt.subject_id = s.id JOIN teachers t ON tt.teacher_id = t.id WHERE tt.teacher_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
                        $stmt->execute([$teacher_db_id]);
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
                    case 'teacher_notes':
                        echo '<h3>' . $t['teacher_notes'] . '</h3>';
                        echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addTeacherNoteModal">' . $t['add_note'] . '</button>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>ID</th><th>Student</th><th>' . $t['note_title'] . '</th><th>Note Snippet</th><th>Date</th><th>' . $t['actions'] . '</th></tr></thead>';
                        echo '<tbody>';
                        $stmt = $pdo->prepare("SELECT tn.*, s.name AS student_name, t.name AS teacher_name FROM teacher_notes tn JOIN students s ON tn.student_id = s.id JOIN teachers t ON tn.teacher_id = t.id WHERE tn.teacher_id = ? ORDER BY tn.created_at DESC");
                        $stmt->execute([$teacher_db_id]);
                        $notes = $stmt->fetchAll();
                        $students_in_my_classes_stmt = $pdo->prepare("SELECT s.id, s.name FROM students s JOIN classes c ON s.class_id = c.id WHERE c.teacher_id = ? ORDER BY s.name");
                        $students_in_my_classes_stmt->execute([$teacher_db_id]);
                        $students_in_my_classes = $students_in_my_classes_stmt->fetchAll();
                        if ($notes) {
                            foreach ($notes as $note) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($note['id']) . '</td>';
                                echo '<td>' . htmlspecialchars($note['student_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($note['title']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($note['note']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d", strtotime($note['created_at'])) . '</td>';
                                echo '<td>';
                                echo '<button type="button" class="btn btn-sm btn-info view-note-btn me-1" data-bs-toggle="modal" data-bs-target="#teacherNoteModal" data-json=\'' . json_encode($note) . '\'>' . $t['view'] . '</button>';
                                echo '<button type="button" class="btn btn-sm btn-info edit-btn me-1" data-bs-toggle="modal" data-bs-target="#editTeacherNoteModal" data-json=\'' . json_encode($note) . '\' data-form-id="editTeacherNoteForm" data-type="teacher_note"><i class="fas fa-edit"></i> ' . $t['edit'] . '</button>';
                                echo '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $note['id'] . '" data-type="teacher_note"><i class="fas fa-trash"></i> ' . $t['delete'] . '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<div class="modal fade" id="addTeacherNoteModal" tabindex="-1" aria-labelledby="addTeacherNoteModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST" id="addTeacherNoteForm">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="addTeacherNoteModalLabel">' . $t['add_note'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="add_teacher_note">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="add_note_student_id" class="form-label">Student</label><select class="form-select" id="add_note_student_id" name="student_id" required>';
                        if ($students_in_my_classes) {
                            foreach ($students_in_my_classes as $student) {
                                echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
                            }
                        } else {
                            echo '<option value="">No students in your classes</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="add_note_title" class="form-label">' . $t['note_title'] . '</label><input type="text" class="form-control" id="add_note_title" name="note_title" required></div>';
                        echo '<div class="mb-3"><label for="add_note_content" class="form-label">' . $t['note_content'] . '</label><textarea class="form-control" id="add_note_content" name="note_content" rows="5" required></textarea></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['add_note'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="editTeacherNoteModal" tabindex="-1" aria-labelledby="editTeacherNoteModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST" id="editTeacherNoteForm">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="editTeacherNoteModalLabel">' . $t['edit'] . ' Teacher Note</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" id="action" value="edit_teacher_note">';
                        echo '<input type="hidden" name="id" id="id">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="student_id" class="form-label">Student</label><select class="form-select" id="student_id" name="student_id" required>';
                        foreach ($students_in_my_classes as $student) {
                            echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="note_title" class="form-label">' . $t['note_title'] . '</label><input type="text" class="form-control" id="note_title" name="note_title" required></div>';
                        echo '<div class="mb-3"><label for="note_content" class="form-label">' . $t['note_content'] . '</label><textarea class="form-control" id="note_content" name="note_content" rows="5" required></textarea></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['save_changes'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="teacherNoteModal" tabindex="-1" aria-labelledby="teacherNoteModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog modal-lg">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="teacherNoteModalLabel">Note Details</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<p><strong>Student:</strong> <span id="teacherNoteModalStudent"></span></p>';
                        echo '<p><strong>Teacher:</strong> <span id="teacherNoteModalTeacher"></span></p>';
                        echo '<p><strong>Date:</strong> <span id="teacherNoteModalDate"></span></p>';
                        echo '<hr>';
                        echo '<div id="teacherNoteModalContent"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        break;
                    case 'messages':
                        echo '<h3>' . $t['internal_messaging'] . '</h3>';
                        echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#composeMessageModal">' . $t['compose_new_message'] . '</button>';
                        echo '<h4>' . $t['my_messages'] . '</h4>';
                        echo '<ul class="nav nav-tabs mb-3" id="messageTabs" role="tablist">';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab" aria-controls="inbox" aria-selected="true">Inbox</button></li>';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab" aria-controls="sent" aria-selected="false">' . $t['sent_messages'] . '</button></li>';
                        echo '</ul>';
                        echo '<div class="tab-content" id="messageTabContent">';
                        echo '<div class="tab-pane fade show active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">';
                        echo '<h5>Unread Messages</h5>';
                        $stmt_inbox_unread = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = FALSE ORDER BY m.sent_at DESC");
                        $stmt_inbox_unread->execute([$_SESSION['id']]);
                        $inbox_unread_messages = $stmt_inbox_unread->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive mb-3">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_unread_messages) {
                            foreach ($inbox_unread_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-primary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No unread messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<h5>Read Messages</h5>';
                        $stmt_inbox_read = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = TRUE ORDER BY m.sent_at DESC");
                        $stmt_inbox_read->execute([$_SESSION['id']]);
                        $inbox_read_messages = $stmt_inbox_read->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_read_messages) {
                            foreach ($inbox_read_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No read messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="tab-pane fade" id="sent" role="tabpanel" aria-labelledby="sent-tab">';
                        echo '<h5>Sent Messages</h5>';
                        $stmt_sent = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.sender_id = ? ORDER BY m.sent_at DESC");
                        $stmt_sent->execute([$_SESSION['id']]);
                        $sent_messages = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Receiver</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($sent_messages) {
                            foreach ($sent_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['receiver_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td>' . ($message_data['read_status'] ? 'Read' : 'Unread') . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['view'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No sent messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="composeMessageModal" tabindex="-1" aria-labelledby="composeMessageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="composeMessageModalLabel">' . $t['compose_new_message'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="send_internal_message">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="receiver_id" class="form-label">' . $t['receiver'] . '</label><select class="form-select" id="receiver_id" name="receiver_id" required>';
                        echo '<option value="">' . $t['select_receiver'] . '</option>';
                        $all_users = $pdo->prepare("SELECT id, username, role FROM users WHERE id != ? ORDER BY role, username");
                        $all_users->execute([$_SESSION['id']]);
                        $users_for_message = $all_users->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($users_for_message as $user_msg) {
                            echo '<option value="' . $user_msg['id'] . '">' . htmlspecialchars($user_msg['username']) . ' (' . htmlspecialchars($user_msg['role']) . ')</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="message_subject" class="form-label">' . $t['subject'] . '</label><input type="text" class="form-control" id="message_subject" name="subject" required></div>';
                        echo '<div class="mb-3"><label for="message_content" class="form-label">' . $t['message'] . '</label><textarea class="form-control" id="message_content" name="message_content" rows="5" required></textarea></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['send'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog modal-lg">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="messageModalLabel">Message Subject</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<p><strong>From:</strong> <span id="messageModalSender"></span></p>';
                        echo '<p><strong>To:</strong> <span id="messageModalReceiver"></span></p>';
                        echo '<p><strong>Date:</strong> <span id="messageModalTimestamp"></span></p>';
                        echo '<hr>';
                        echo '<div id="messageModalBody"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
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
                $csrf = generate_csrf_token();
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
                        $stmt = $pdo->prepare("SELECT a.*, c.name AS class_name, s.name AS subject_name, tea.name AS teacher_name, sa.file_path AS submitted_file, sa.submission_date FROM assignments a JOIN classes c ON a.class_id = c.id JOIN subjects s ON a.subject_id = s.id JOIN teachers tea ON a.teacher_id = tea.id LEFT JOIN student_assignments sa ON a.id = sa.assignment_id AND sa.student_id = ? WHERE a.class_id = ? ORDER BY due_date DESC");
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
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<input type="hidden" name="assignment_id" id="submit_assignment_id">';
                        echo '<input type="hidden" name="student_id" id="submit_student_id">';
                        echo '<div class="mb-3"><label for="submission_file" class="form-label">' . $t['submission_file'] . '</label><input type="file" class="form-control" id="submission_file" name="submission_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" required></div>';
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
                    case 'study_materials':
                        echo '<h3>Study Materials</h3>';
                        echo '<h4>Available Study Materials for My Class (' . htmlspecialchars($student['class_name'] ?? 'N/A') . ')</h4>';
                        if ($class_id) {
                            $stmt = $pdo->prepare("SELECT sm.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name FROM study_materials sm JOIN classes c ON sm.class_id = c.id JOIN subjects s ON sm.subject_id = s.id JOIN teachers t ON sm.teacher_id = t.id WHERE sm.class_id = ? ORDER BY uploaded_at DESC");
                            $stmt->execute([$class_id]);
                            $materials = $stmt->fetchAll();
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead><tr><th>' . $t['title'] . '</th><th>Teacher</th><th>Subject</th><th>Description</th><th>File</th><th>Uploaded At</th></tr></thead>';
                            echo '<tbody>';
                            if ($materials) {
                                foreach ($materials as $material) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($material['title']) . '</td>';
                                    echo '<td>' . htmlspecialchars($material['teacher_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($material['subject_name']) . '</td>';
                                    echo '<td>' . substr(htmlspecialchars($material['description']), 0, 50) . '...</td>';
                                    echo '<td><a href="' . htmlspecialchars($material['file_path']) . '" target="_blank">Download</a></td>';
                                    echo '<td>' . date("Y-m-d H:i", strtotime($material['uploaded_at'])) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">' . $t['no_records'] . '</td></tr>';
                            }
                            echo '</tbody></table>';
                            echo '</div>';
                        } else {
                            echo '<p>You are not assigned to a class. Please contact administration.</p>';
                        }
                        break;
                    case 'messages':
                        echo '<h3>' . $t['internal_messaging'] . '</h3>';
                        echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#composeMessageModal">' . $t['compose_new_message'] . '</button>';
                        echo '<h4>' . $t['my_messages'] . '</h4>';
                        echo '<ul class="nav nav-tabs mb-3" id="messageTabs" role="tablist">';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab" aria-controls="inbox" aria-selected="true">Inbox</button></li>';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab" aria-controls="sent" aria-selected="false">' . $t['sent_messages'] . '</button></li>';
                        echo '</ul>';
                        echo '<div class="tab-content" id="messageTabContent">';
                        echo '<div class="tab-pane fade show active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">';
                        echo '<h5>Unread Messages</h5>';
                        $stmt_inbox_unread = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = FALSE ORDER BY m.sent_at DESC");
                        $stmt_inbox_unread->execute([$_SESSION['id']]);
                        $inbox_unread_messages = $stmt_inbox_unread->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive mb-3">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_unread_messages) {
                            foreach ($inbox_unread_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-primary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No unread messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<h5>Read Messages</h5>';
                        $stmt_inbox_read = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = TRUE ORDER BY m.sent_at DESC");
                        $stmt_inbox_read->execute([$_SESSION['id']]);
                        $inbox_read_messages = $stmt_inbox_read->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_read_messages) {
                            foreach ($inbox_read_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No read messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="tab-pane fade" id="sent" role="tabpanel" aria-labelledby="sent-tab">';
                        echo '<h5>Sent Messages</h5>';
                        $stmt_sent = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.sender_id = ? ORDER BY m.sent_at DESC");
                        $stmt_sent->execute([$_SESSION['id']]);
                        $sent_messages = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Receiver</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($sent_messages) {
                            foreach ($sent_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['receiver_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td>' . ($message_data['read_status'] ? 'Read' : 'Unread') . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['view'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No sent messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="composeMessageModal" tabindex="-1" aria-labelledby="composeMessageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="composeMessageModalLabel">' . $t['compose_new_message'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="send_internal_message">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="receiver_id" class="form-label">' . $t['receiver'] . '</label><select class="form-select" id="receiver_id" name="receiver_id" required>';
                        echo '<option value="">' . $t['select_receiver'] . '</option>';
                        $all_users = $pdo->prepare("SELECT id, username, role FROM users WHERE id != ? ORDER BY role, username");
                        $all_users->execute([$_SESSION['id']]);
                        $users_for_message = $all_users->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($users_for_message as $user_msg) {
                            echo '<option value="' . $user_msg['id'] . '">' . htmlspecialchars($user_msg['username']) . ' (' . htmlspecialchars($user_msg['role']) . ')</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="message_subject" class="form-label">' . $t['subject'] . '</label><input type="text" class="form-control" id="message_subject" name="subject" required></div>';
                        echo '<div class="mb-3"><label for="message_content" class="form-label">' . $t['message'] . '</label><textarea class="form-control" id="message_content" name="message_content" rows="5" required></textarea></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['send'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog modal-lg">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="messageModalLabel">Message Subject</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<p><strong>From:</strong> <span id="messageModalSender"></span></p>';
                        echo '<p><strong>To:</strong> <span id="messageModalReceiver"></span></p>';
                        echo '<p><strong>Date:</strong> <span id="messageModalTimestamp"></span></p>';
                        echo '<hr>';
                        echo '<div id="messageModalBody"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
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
                $csrf = generate_csrf_token();
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
                echo '<h4>Select Child:</h4>';
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
                        echo '<h4>' . $t['student_performance_overview'] . '</h4>';
                        echo '<div class="d-flex justify-content-between align-items-center mb-3">';
                        echo '<h5>' . $t['view_report_card'] . '</h5>';
                        echo '<form method="GET" class="d-inline-block">';
                        echo '<input type="hidden" name="page" value="dashboard">';
                        echo '<input type="hidden" name="section" value="performance">';
                        echo '<input type="hidden" name="lang" value="' . $lang . '">';
                        echo '<input type="hidden" name="student_id" value="' . htmlspecialchars($selected_student_id) . '">';
                        echo '<label for="report_card_academic_year" class="form-label me-2">Academic Year:</label>';
                        echo '<select class="form-select d-inline-block w-auto" id="report_card_academic_year" name="academic_year">';
                        $current_year = date('Y');
                        for ($year = $current_year; $year >= 2020; $year--) {
                            $selected = (isset($_GET['academic_year']) && $_GET['academic_year'] == $year) ? 'selected' : (($year == $current_year && !isset($_GET['academic_year'])) ? 'selected' : '');
                            echo '<option value="' . $year . '" ' . $selected . '>' . $year . '</option>';
                        }
                        echo '</select>';
                        echo '</form>';
                        echo '</div>';
                        echo '<div id="report_card_display_area">';
                        echo '<p>Loading report card...</p>';
                        echo '</div>';
                        break;
                    case 'fees':
                        echo '<h4>' . $t['view_student_fees'] . '</h4>';
                        $stmt_fees = $pdo->prepare("SELECT f.*, fs.name AS fee_structure_name, (f.amount - f.concession + f.fine) AS net_amount, (SELECT SUM(amount_paid) FROM fee_transactions WHERE fee_id = f.id) AS total_paid FROM fees f LEFT JOIN fee_structures fs ON f.fee_structure_id = fs.id WHERE student_id = ? ORDER BY due_date DESC");
                        $stmt_fees->execute([$selected_student_id]);
                        $fee_records = $stmt_fees->fetchAll();
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>ID</th><th>Fee Item</th><th>' . $t['amount'] . '</th><th>' . $t['concession'] . '</th><th>' . $t['fine'] . '</th><th>Net Amount</th><th>Paid Amount</th><th>' . $t['due_date_invoice'] . '</th><th>' . $t['paid_date'] . '</th><th>' . $t['invoice_status'] . '</th><th>Description</th></tr></thead>';
                        echo '<tbody>';
                        if ($fee_records) {
                            foreach ($fee_records as $fee) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($fee['id']) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['fee_structure_name'] ?? 'Custom Fee') . '</td>';
                                echo '<td>' . htmlspecialchars($fee['amount']) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['concession']) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['fine']) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['net_amount']) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['total_paid'] ?? 0) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['due_date']) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['paid_date'] ?? 'N/A') . '</td>';
                                echo '<td>' . htmlspecialchars($fee['status']) . '</td>';
                                echo '<td>' . htmlspecialchars($fee['description']) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="11" class="text-center">' . $t['no_records'] . '</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        break;

                    case 'messages':
                        echo '<h3>' . $t['internal_messaging'] . '</h3>';
                        echo '<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#composeMessageModal">' . $t['compose_new_message'] . '</button>';
                        echo '<h4>' . $t['my_messages'] . '</h4>';
                        echo '<ul class="nav nav-tabs mb-3" id="messageTabs" role="tablist">';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab" aria-controls="inbox" aria-selected="true">Inbox</button></li>';
                        echo '<li class="nav-item" role="presentation"><button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab" aria-controls="sent" aria-selected="false">' . $t['sent_messages'] . '</button></li>';
                        echo '</ul>';
                        echo '<div class="tab-content" id="messageTabContent">';
                        echo '<div class="tab-pane fade show active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">';
                        echo '<h5>Unread Messages</h5>';
                        $stmt_inbox_unread = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = FALSE ORDER BY m.sent_at DESC");
                        $stmt_inbox_unread->execute([$_SESSION['id']]);
                        $inbox_unread_messages = $stmt_inbox_unread->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive mb-3">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_unread_messages) {
                            foreach ($inbox_unread_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-primary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No unread messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<h5>Read Messages</h5>';
                        $stmt_inbox_read = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.receiver_id = ? AND m.read_status = TRUE ORDER BY m.sent_at DESC");
                        $stmt_inbox_read->execute([$_SESSION['id']]);
                        $inbox_read_messages = $stmt_inbox_read->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Sender</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($inbox_read_messages) {
                            foreach ($inbox_read_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['sender_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['read_message'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No read messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="tab-pane fade" id="sent" role="tabpanel" aria-labelledby="sent-tab">';
                        echo '<h5>Sent Messages</h5>';
                        $stmt_sent = $pdo->prepare("SELECT m.*, s.username AS sender_username, s.role AS sender_role, r.username AS receiver_username, r.role AS receiver_role FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id WHERE m.sender_id = ? ORDER BY m.sent_at DESC");
                        $stmt_sent->execute([$_SESSION['id']]);
                        $sent_messages = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Receiver</th><th>Subject</th><th>Message Snippet</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        if ($sent_messages) {
                            foreach ($sent_messages as $message_data) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($message_data['receiver_username']) . '</td>';
                                echo '<td>' . htmlspecialchars($message_data['subject']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($message_data['message']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d H:i", strtotime($message_data['sent_at'])) . '</td>';
                                echo '<td>' . ($message_data['read_status'] ? 'Read' : 'Unread') . '</td>';
                                echo '<td><button class="btn btn-sm btn-secondary view-message-btn" data-bs-toggle="modal" data-bs-target="#messageModal" data-json=\'' . json_encode($message_data) . '\'>' . $t['view'] . '</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No sent messages.</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="composeMessageModal" tabindex="-1" aria-labelledby="composeMessageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<form action="" method="POST">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="composeMessageModalLabel">' . $t['compose_new_message'] . '</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<input type="hidden" name="action" value="send_internal_message">';
                        echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
                        echo '<div class="mb-3"><label for="receiver_id" class="form-label">' . $t['receiver'] . '</label><select class="form-select" id="receiver_id" name="receiver_id" required>';
                        echo '<option value="">' . $t['select_receiver'] . '</option>';
                        $all_users = $pdo->prepare("SELECT id, username, role FROM users WHERE id != ? ORDER BY role, username");
                        $all_users->execute([$_SESSION['id']]);
                        $users_for_message = $all_users->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($users_for_message as $user_msg) {
                            echo '<option value="' . $user_msg['id'] . '">' . htmlspecialchars($user_msg['username']) . ' (' . htmlspecialchars($user_msg['role']) . ')</option>';
                        }
                        echo '</select></div>';
                        echo '<div class="mb-3"><label for="message_subject" class="form-label">' . $t['subject'] . '</label><input type="text" class="form-control" id="message_subject" name="subject" required></div>';
                        echo '<div class="mb-3"><label for="message_content" class="form-label">' . $t['message'] . '</label><textarea class="form-control" id="message_content" name="message_content" rows="5" required></textarea></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '<button type="submit" class="btn btn-primary">' . $t['send'] . '</button>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog modal-lg">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="messageModalLabel">Message Subject</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<p><strong>From:</strong> <span id="messageModalSender"></span></p>';
                        echo '<p><strong>To:</strong> <span id="messageModalReceiver"></span></p>';
                        echo '<p><strong>Date:</strong> <span id="messageModalTimestamp"></span></p>';
                        echo '<hr>';
                        echo '<div id="messageModalBody"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        break;
                    case 'teacher_notes':
                        echo '<h3>View Teacher Notes</h3>';
                        echo '<h4>Notes for ' . htmlspecialchars($current_student['name']) . '</h4>';
                        $stmt = $pdo->prepare("SELECT tn.*, t.name AS teacher_name FROM teacher_notes tn JOIN teachers t ON tn.teacher_id = t.id WHERE tn.student_id = ? ORDER BY tn.created_at DESC");
                        $stmt->execute([$selected_student_id]);
                        $notes = $stmt->fetchAll();
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead><tr><th>Teacher</th><th>' . $t['note_title'] . '</th><th>Note Snippet</th><th>Date</th><th>' . $t['actions'] . '</th></tr></thead>';
                        echo '<tbody>';
                        if ($notes) {
                            foreach ($notes as $note) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($note['teacher_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($note['title']) . '</td>';
                                echo '<td>' . substr(htmlspecialchars($note['note']), 0, 100) . '...</td>';
                                echo '<td>' . date("Y-m-d", strtotime($note['created_at'])) . '</td>';
                                echo '<td>';
                                echo '<button type="button" class="btn btn-sm btn-info view-note-btn me-1" data-bs-toggle="modal" data-bs-target="#teacherNoteModal" data-json=\'' . json_encode($note) . '\'>' . $t['view'] . '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">' . $t['no_records'] . '</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<div class="modal fade" id="teacherNoteModal" tabindex="-1" aria-labelledby="teacherNoteModalLabel" aria-hidden="true">';
                        echo '<div class="modal-dialog modal-lg">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="teacherNoteModalLabel">Note Details</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<p><strong>Student:</strong> ' . htmlspecialchars($current_student['name']) . '</p>';
                        echo '<p><strong>Teacher:</strong> <span id="teacherNoteModalTeacher"></span></p>';
                        echo '<p><strong>Date:</strong> <span id="teacherNoteModalDate"></span></p>';
                        echo '<hr>';
                        echo '<div id="teacherNoteModalContent"></div>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        break;
                    default:
                        echo '<h2>Welcome, Parent!</h2>';
                        echo '<p>Use the dropdown and sidebar to view your child\'s performance and fee status.</p>';
                        break;
                }
            }
                        ?>