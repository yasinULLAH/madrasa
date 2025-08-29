-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 29, 2025 at 11:52 AM
-- Server version: 8.2.0
-- PHP Version: 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_management_dbv2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `applying_for_class` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admissions`
--

INSERT INTO `admissions` (`id`, `student_name`, `father_name`, `dob`, `gender`, `previous_school`, `applying_for_class`, `address`, `phone`, `email`, `submission_date`, `status`) VALUES
(1, 'Sara Imran', 'Imran Khan', '2010-03-20', 'Female', 'Green Valley School', 'Class 7', 'Street 1, Phase 5, DHA, Karachi', '03451122334', 'sara.imran@example.com', '2025-08-29 06:35:03', 'Pending'),
(2, 'Usman Tariq', 'Tariq Mehmood', '2009-08-10', 'Male', NULL, 'Class 8', 'House 4, Lane 7, Gulberg, Lahore', '03019876543', 'usman.tariq@example.com', '2025-08-29 06:35:03', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `published_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `file_path`, `published_date`) VALUES
(1, 'New School Year Begins', 'Welcome back students! Classes for the new academic year will commence on September 1, 2023.', NULL, '2023-08-25 05:00:00'),
(2, 'Admissions Open for 2024', 'Admissions for the academic year 2024 are now open. Visit our website for more details.', NULL, '2023-09-01 04:30:00'),
(3, 'Summer Break Announced', 'School will remain closed for summer break from June 15 to August 15.', NULL, '2024-06-01 07:00:00'),
(4, 'Annual Science Fair', 'Our annual science fair will be held on December 5, 2023. Students are encouraged to participate.', NULL, '2023-11-10 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `due_date` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `title`, `description`, `due_date`, `file_path`, `uploaded_at`) VALUES
(1, 1, 1, 1, 'Algebra Homework', 'Complete exercises on quadratic equations.', '2023-10-25', NULL, '2025-08-29 06:35:03'),
(2, 1, 1, 2, 'Newton Laws Worksheet', 'Solve problems related to Newton\'s Laws of Motion.', '2023-11-01', NULL, '2025-08-29 06:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `class_id`, `subject_id`, `attendance_date`, `status`) VALUES
(1, 1, 1, 1, '2025-08-09', 'Absent'),
(2, 1, 1, 1, '2025-08-25', 'Absent'),
(3, 2, 1, 1, '2025-07-28', 'Late'),
(4, 1, 1, 1, '2025-07-08', 'Late'),
(5, 2, 1, 2, '2025-08-01', 'Absent'),
(6, 2, 1, 1, '2025-08-16', 'Late'),
(7, 2, 1, 2, '2025-08-28', 'Late'),
(8, 1, 1, 1, '2025-07-16', 'Present'),
(9, 2, 1, 2, '2025-07-23', 'Late'),
(10, 2, 1, 2, '2025-07-25', 'Absent'),
(11, 1, 1, 1, '2025-07-18', 'Present'),
(12, 2, 1, 2, '2025-08-08', 'Present'),
(13, 1, 1, 2, '2025-08-19', 'Present'),
(14, 2, 1, 2, '2025-07-13', 'Present'),
(15, 2, 1, 2, '2025-07-26', 'Absent'),
(16, 2, 1, 1, '2025-07-24', 'Absent'),
(17, 2, 1, 1, '2025-08-24', 'Absent'),
(18, 1, 1, 1, '2025-07-29', 'Late'),
(19, 1, 1, 2, '2025-07-06', 'Absent'),
(20, 1, 1, 2, '2025-07-31', 'Present'),
(21, 1, 1, 2, '2025-07-20', 'Late'),
(22, 1, 1, 2, '2025-08-15', 'Absent'),
(23, 1, 1, 1, '2025-08-06', 'Absent'),
(24, 2, 1, 1, '2025-08-13', 'Present'),
(25, 1, 1, 1, '2025-07-05', 'Present'),
(26, 2, 1, 1, '2025-07-17', 'Present'),
(27, 1, 1, 1, '2025-07-23', 'Present'),
(28, 1, 1, 1, '2025-07-03', 'Late'),
(29, 1, 1, 2, '2025-07-02', 'Absent'),
(30, 2, 1, 1, '2025-07-15', 'Present'),
(31, 2, 1, 2, '2025-07-20', 'Late'),
(32, 1, 1, 2, '2025-07-11', 'Present'),
(33, 1, 1, 2, '2025-07-04', 'Absent'),
(34, 2, 1, 2, '2025-07-07', 'Late'),
(35, 2, 1, 2, '2025-08-25', 'Present'),
(36, 1, 1, 2, '2025-07-13', 'Late'),
(37, 2, 1, 1, '2025-07-27', 'Absent'),
(38, 2, 1, 2, '2025-08-11', 'Absent'),
(39, 1, 1, 1, '2025-08-28', 'Late'),
(40, 2, 1, 1, '2025-07-22', 'Present'),
(41, 2, 1, 4, '2025-08-29', 'Absent'),
(42, 1, 1, 4, '2025-08-29', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `teacher_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `teacher_id`) VALUES
(1, 'Class 10A', 1),
(2, 'Class 9B', 1);

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 5),
(4, 2, 6);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `submission_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('New','Replied') DEFAULT 'New'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `submission_date`, `status`) VALUES
(1, 'Zainab Bibi', 'zainab.bibi@example.com', 'Inquiry about Admissions', 'I would like to know more about the admission process for my child in Class 5.', '2025-08-29 06:35:03', 'New'),
(2, 'Haris Khan', 'haris.khan@example.com', 'Complaint about class timings', 'The current class timings are clashing with my child\'s extracurricular activities.', '2025-08-29 06:35:03', 'Replied');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `location`, `created_at`) VALUES
(1, 'Annual Sports Day', 'Join us for a day of exciting sports activities and competitions.', '2023-11-10', '09:00:00', 'School Ground', '2025-08-29 06:35:03'),
(2, 'Parent-Teacher Meeting', 'Discuss student progress with teachers. All parents are encouraged to attend.', '2023-10-20', '14:00:00', 'School Auditorium', '2025-08-29 06:35:03'),
(3, 'Debate Competition', 'Inter-house debate competition for senior students.', '2023-12-01', '10:00:00', 'School Hall', '2025-08-29 06:35:03'),
(4, 'Art Exhibition', 'Showcasing student artworks from various classes.', '2026-01-25', '11:00:00', 'Art Room', '2025-08-29 06:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `exam_date` date NOT NULL,
  `max_marks` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `name`, `class_id`, `subject_id`, `teacher_id`, `exam_date`, `max_marks`) VALUES
(1, 'Mid-Term Exam', 1, 1, 1, '2023-11-15', 100),
(2, 'Mid-Term Exam', 1, 2, 1, '2023-11-16', 100),
(3, 'Monthly Assessment S2', 1, 2, 1, '2025-10-09', 100),
(4, 'Monthly Assessment S6', 2, 6, 1, '2025-09-08', 25),
(5, 'Mid-Term Review S1', 1, 1, 1, '2025-10-08', 25),
(6, 'Monthly Assessment S5', 2, 5, 1, '2025-09-05', 100),
(7, 'Monthly Assessment S5', 2, 5, 1, '2025-10-10', 25),
(8, 'Monthly Assessment S2', 1, 2, 1, '2025-09-02', 50),
(9, 'Pop Quiz S1', 1, 1, 1, '2025-09-29', 50),
(10, 'Pop Quiz S6', 2, 6, 1, '2025-09-08', 50),
(11, 'Monthly Assessment S5', 2, 5, 1, '2025-09-25', 100),
(12, 'Chapter Quiz S2', 1, 2, 1, '2025-09-24', 50),
(13, 'Mid-Term Review S2', 1, 2, 1, '2025-09-17', 50),
(14, 'Chapter Quiz S6', 2, 6, 1, '2025-10-01', 50),
(15, 'Chapter Quiz S2', 1, 2, 1, '2025-10-10', 50),
(16, 'Mid-Term Review S6', 2, 6, 1, '2025-10-05', 25),
(17, 'Mid-Term Review S1', 1, 1, 1, '2025-09-24', 25),
(18, 'Pop Quiz S2', 1, 2, 1, '2025-10-04', 100),
(19, 'Pop Quiz S5', 2, 5, 1, '2025-09-05', 100),
(20, 'Chapter Quiz S6', 2, 6, 1, '2025-09-07', 25),
(21, 'Mid-Term Review S2', 1, 2, 1, '2025-10-06', 25),
(22, 'Chapter Quiz S6', 2, 6, 1, '2025-09-19', 25),
(23, 'Pop Quiz S5', 2, 5, 1, '2025-09-02', 25),
(24, 'Pop Quiz S1', 1, 1, 1, '2025-09-21', 100),
(25, 'Pop Quiz S5', 2, 5, 1, '2025-09-26', 100),
(26, 'Chapter Quiz S5', 2, 5, 1, '2025-10-02', 50),
(27, 'Pop Quiz S1', 1, 1, 1, '2025-09-02', 50),
(28, 'Chapter Quiz S2', 1, 2, 1, '2025-10-07', 100),
(29, 'Mid-Term Review S2', 1, 2, 1, '2025-09-24', 100),
(30, 'Pop Quiz S2', 1, 2, 1, '2025-09-21', 25),
(31, 'Mid-Term Review S1', 1, 1, 1, '2025-10-10', 100),
(32, 'Monthly Assessment S2', 1, 2, 1, '2025-09-01', 50),
(33, 'Mid-Term Review S5', 2, 5, 1, '2025-10-06', 50),
(34, 'Mid-Term Review S2', 1, 2, 1, '2025-10-01', 100),
(35, 'Pop Quiz S6', 2, 6, 1, '2025-09-15', 50),
(36, 'Chapter Quiz S2', 1, 2, 1, '2025-10-08', 50),
(37, 'Monthly Assessment S5', 2, 5, 1, '2025-10-11', 25),
(38, 'Monthly Assessment S6', 2, 6, 1, '2025-09-26', 25),
(39, 'Pop Quiz S5', 2, 5, 1, '2025-09-10', 50),
(40, 'Chapter Quiz S5', 2, 5, 1, '2025-09-29', 25),
(41, 'Monthly Assessment S6', 2, 6, 1, '2025-09-25', 100),
(42, 'Chapter Quiz S5', 2, 5, 1, '2025-10-03', 100);

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `fee_structure_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('Paid','Unpaid','Partially Paid') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `concession` decimal(10,2) DEFAULT '0.00',
  `fine` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `fee_structure_id`, `amount`, `due_date`, `paid_date`, `status`, `description`, `concession`, `fine`) VALUES
(1, 1, 1, 5000.00, '2023-10-01', NULL, 'Unpaid', 'Tuition Fee - October', 0.00, 0.00),
(2, 2, 1, 5000.00, '2023-10-01', NULL, 'Paid', 'Tuition Fee - October', 0.00, 0.00),
(3, 1, 2, 4500.00, '2025-09-17', NULL, 'Unpaid', 'Standard monthly tuition', 0.00, 0.00),
(4, 2, 2, 4500.00, '2025-09-17', NULL, 'Unpaid', 'Standard monthly tuition', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('Tuition','Transport','Lab','Exam','Other') NOT NULL DEFAULT 'Tuition',
  `class_id` int DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`id`, `name`, `amount`, `type`, `class_id`, `description`) VALUES
(1, 'Monthly Tuition Fee', 5000.00, 'Tuition', 1, 'Standard monthly tuition'),
(2, 'Monthly Tuition Fee', 4500.00, 'Tuition', 2, 'Standard monthly tuition');

-- --------------------------------------------------------

--
-- Table structure for table `fee_transactions`
--

CREATE TABLE `fee_transactions` (
  `id` int NOT NULL,
  `fee_id` int NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `image_path`, `uploaded_at`) VALUES
(1, 'Annual Sports Day 2022', 'uploads/gallery/sports_day_2022.jpg', '2025-08-29 06:35:03'),
(2, 'Science Fair 2023', 'uploads/gallery/science_fair_2023.jpg', '2025-08-29 06:35:03'),
(3, 'Graduation Ceremony 2023', 'uploads/gallery/graduation_2023.jpg', '2025-08-29 06:35:03'),
(4, 'Art Competition Winners', 'uploads/gallery/art_winners.jpg', '2025-08-29 06:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int NOT NULL,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `marks_obtained` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `exam_id`, `student_id`, `marks_obtained`) VALUES
(1, 1, 1, 85),
(2, 1, 2, 78),
(3, 13, 2, 42),
(4, 28, 2, 85),
(5, 13, 1, 34),
(6, 21, 1, 23),
(7, 29, 1, 60),
(8, 2, 1, 60),
(9, 27, 2, 36),
(10, 17, 2, 21),
(11, 8, 1, 32),
(12, 32, 1, 40),
(13, 36, 1, 30),
(14, 32, 2, 45),
(15, 21, 2, 23),
(16, 2, 2, 75),
(17, 5, 1, 22),
(18, 12, 1, 40),
(19, 30, 2, 23),
(20, 5, 2, 21),
(21, 17, 1, 16),
(22, 27, 1, 39),
(23, 3, 1, 93),
(24, 30, 1, 23),
(25, 31, 1, 86),
(26, 34, 1, 95),
(27, 3, 2, 72),
(28, 34, 2, 67),
(29, 28, 1, 71),
(30, 18, 2, 89),
(31, 8, 2, 46),
(32, 9, 2, 38),
(33, 15, 2, 32),
(34, 15, 1, 33),
(35, 29, 2, 67),
(36, 12, 2, 48),
(37, 36, 2, 44),
(38, 9, 1, 35),
(39, 31, 2, 61),
(40, 24, 2, 68);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `read_status` tinyint(1) DEFAULT '0',
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sender_read_receipt` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `subject`, `message`, `read_status`, `sent_at`, `sender_read_receipt`) VALUES
(1, 3, 1, 'Mashallah', 'great', 1, '2025-08-29 10:34:53', 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `class_id` int DEFAULT NULL,
  `roll_no` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `medical_history` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `name`, `class_id`, `roll_no`, `dob`, `address`, `phone`, `parent_id`, `medical_history`, `emergency_contact_name`, `emergency_contact_phone`, `document_path`) VALUES
(1, 3, 'Fatima Khan', 1, '10A-001', '2008-05-15', 'House 1, Street 2, Lahore', '03219876543', 4, 'Allergy to peanuts', 'Mr. Asif Khan', '03211234567', 'uploads/documents/birth_cert_fatima.pdf'),
(2, 5, 'Ahmed Raza', 1, '10A-002', '2008-06-20', 'Flat 5, Block B, Islamabad', '03331234567', 4, NULL, 'Ms. Zara Raza', '03337654321', 'uploads/documents/birth_cert_ahmed.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `student_assignments`
--

CREATE TABLE `student_assignments` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submission_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `study_materials`
--

CREATE TABLE `study_materials` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `study_materials`
--

INSERT INTO `study_materials` (`id`, `teacher_id`, `class_id`, `subject_id`, `title`, `description`, `file_path`, `uploaded_at`) VALUES
(1, 1, 1, 1, 'Algebra Formulas', 'Key formulas for algebra.', 'uploads/study_materials/algebra_formulas.pdf', '2025-08-29 06:35:03'),
(2, 1, 1, 2, 'Physics Concepts', 'Basic concepts of mechanics.', 'uploads/study_materials/physics_concepts.pdf', '2025-08-29 06:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`) VALUES
(4, 'Biology'),
(3, 'Chemistry'),
(7, 'Computer Science'),
(5, 'English'),
(1, 'Mathematics'),
(2, 'Physics'),
(6, 'Urdu');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `subject_specialty` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `name`, `subject_specialty`, `phone`, `email`) VALUES
(1, 2, 'Mr. Ali Ahmed', 'Mathematics', '03001234567', 'teacher1@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_notes`
--

CREATE TABLE `teacher_notes` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`id`, `class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 1, 1, 1, 'Monday', '09:00:00', '10:00:00'),
(2, 1, 2, 1, 'Tuesday', '10:00:00', '11:00:00'),
(3, 2, 5, 1, 'Wednesday', '11:00:00', '12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student','parent') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$HQqSX7OlTQiYvCeZVBiTTu/KJyS3vhQyozvtPwtrA5vP17T59b9Xm', 'admin', 'admin@example.com', '2025-08-29 06:35:02'),
(2, 'teacher1', '$2y$10$hlVIE5Pn7IdAxBf8VQ4Z0etDLPWQe9oWzrb45shT8.1dQaX3GzZni', 'teacher', 'teacher1@example.com', '2025-08-29 06:35:02'),
(3, 'student1', '$2y$10$HQqSX7OlTQiYvCeZVBiTTu/KJyS3vhQyozvtPwtrA5vP17T59b9Xm', 'student', 'student1@example.com', '2025-08-29 06:35:02'),
(4, 'parent1', '$2y$10$lJ9fF2vL1xW5B0f8M1G2O.u.n.y.V.S.h.A.N.i.N', 'parent', 'parent1@example.com', '2025-08-29 06:35:02'),
(5, 'student2', '$2y$10$qKzN3eZ0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'student', 'student2@example.com', '2025-08-29 06:35:02'),
(6, 'parent2', '$2y$10$pKjN4eA0Xz2eW0F1G2X3u.b/RzW9PjC1P.u.n.y.V.S.', 'parent', 'parent2@example.com', '2025-08-29 06:35:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`class_id`,`subject_id`,`attendance_date`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_id` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `fee_transactions`
--
ALTER TABLE `fee_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_id` (`fee_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_id` (`exam_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `student_assignments`
--
ALTER TABLE `student_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assignment_id` (`assignment_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_notes`
--
ALTER TABLE `teacher_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fee_transactions`
--
ALTER TABLE `fee_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_assignments`
--
ALTER TABLE `student_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `study_materials`
--
ALTER TABLE `study_materials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_notes`
--
ALTER TABLE `teacher_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fees_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_transactions`
--
ALTER TABLE `fee_transactions`
  ADD CONSTRAINT `fee_transactions_ibfk_1` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_assignments`
--
ALTER TABLE `student_assignments`
  ADD CONSTRAINT `student_assignments_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_assignments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD CONSTRAINT `study_materials_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_materials_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_materials_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_notes`
--
ALTER TABLE `teacher_notes`
  ADD CONSTRAINT `teacher_notes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_notes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
