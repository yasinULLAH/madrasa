-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 19, 2025 at 11:31 AM
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
-- Database: `school_management_db`
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
(1, 'Sara Imran', 'Imran Khan', '2010-03-20', 'Female', 'Green Valley School', 'Class 7', 'Street 1, Phase 5, DHA, Karachi', '03451122334', 'sara.imran@example.com', '2025-08-19 08:53:20', 'Pending'),
(2, 'Usman Tariq', 'Tariq Mehmood', '2009-08-10', 'Male', NULL, 'Class 8', 'House 4, Lane 7, Gulberg, Lahore', '03019876543', 'usman.tariq@example.com', '2025-08-19 08:53:20', 'Approved'),
(3, 'Aliya Khan', 'Zahid Khan', '2012-05-10', 'Female', 'City School', 'Class 6', 'House 123, Sector F, Islamabad', '03112233445', 'aliya.k@example.com', '2025-08-19 10:14:33', 'Pending'),
(4, 'Omar Farooq', 'Farooq Ahmed', '2011-11-25', 'Male', 'Beaconhouse School', 'Class 7', 'Street 4, Phase 6, DHA, Lahore', '03223344556', 'omar.f@example.com', '2025-08-19 10:14:33', 'Pending'),
(5, 'Hina Riaz', 'Riaz Ali', '2010-09-01', 'Female', NULL, 'Class 8', 'Cantt Area, Karachi', '03334455667', 'hina.r@example.com', '2025-08-19 10:14:33', 'Pending'),
(6, 'Kamran Akmal', 'Akmal Khan', '2013-02-14', 'Male', 'Roots School System', 'Class 5', 'University Town, Peshawar', '03445566778', 'kamran.a@example.com', '2025-08-19 10:14:33', 'Approved'),
(7, 'Sadia Anwar', 'Anwar Malik', '2012-07-30', 'Female', 'Froebels', 'Class 6', 'Model Town, Lahore', '03009988776', 'sadia.a@example.com', '2025-08-19 10:14:33', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `published_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `published_date`, `file_path`) VALUES
(1, 'New School Year Begins', 'Welcome back students! Classes for the new academic year will commence on September 1, 2023.', '2023-08-25 05:00:00', NULL),
(2, 'Admissions Open for 2024', 'Admissions for the academic year 2024 are now open. Visit our website for more details.', '2023-09-01 04:30:00', NULL),
(3, 'Summer Break Announced', 'School will remain closed for summer break from June 15 to August 15.', '2024-06-01 07:00:00', NULL),
(4, 'Annual Science Fair', 'Our annual science fair will be held on December 5, 2023. Students are encouraged to participate.', '2023-11-10 10:00:00', NULL),
(5, 'Annual Sports Gala', 'The Annual Sports Gala will be held on the first week of November. All students are encouraged to participate.', '2025-10-15 05:00:00', NULL),
(6, 'Parent-Teacher Meeting Schedule', 'The second PTM of this term is scheduled for October 25, 2025. Please ensure your presence.', '2025-10-10 06:30:00', NULL),
(7, 'Winter Vacations Announced', 'The school will remain closed for winter vacations from December 22, 2025, to January 5, 2026.', '2025-10-05 09:00:00', NULL),
(8, 'Inter-School Art Competition', 'An art competition is being organized on November 15, 2025. Interested students should register with their art teacher.', '2025-10-20 04:00:00', NULL),
(9, 'Annual Health Checkup Camp', 'A free health checkup camp for all students will be arranged on November 5, 2025.', '2025-10-22 08:00:00', 'uploads/announcements/68a4526758aba_maulana muhammad ilyas db.pdf');

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
(1, 1, 1, 1, 'Algebra Homework', 'Complete exercises on quadratic equations.', '2023-10-25', NULL, '2025-08-19 08:53:20'),
(2, 1, 1, 2, 'Newton Laws Worksheet', 'Solve problems related to Newton\'s Laws of Motion.', '2023-11-01', NULL, '2025-08-19 08:53:20');

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
(2, 'Class 9B', 1),
(3, 'Class 8A', 2),
(4, 'Class 7C', 3);

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
(1, 'Zainab Bibi', 'zainab.bibi@example.com', 'Inquiry about Admissions', 'I would like to know more about the admission process for my child in Class 5.', '2025-08-19 08:53:20', 'New'),
(2, 'Haris Khan', 'haris.khan@example.com', 'Complaint about class timings', 'The current class timings are clashing with my child\'s extracurricular activities.', '2025-08-19 08:53:20', 'Replied'),
(3, 'Asma Zafar', 'asma.z@example.com', 'Fee Structure Inquiry', 'Could you please provide details about the fee structure for Class 5 for the next academic year?', '2025-08-19 10:14:33', 'New'),
(4, 'Tariq Jameel', 'tariq.j@example.com', 'Question about Sports Facilities', 'I would like to know more about the sports facilities available at the school.', '2025-08-19 10:14:33', 'New'),
(5, 'Nadia Hussain', 'nadia.h@example.com', 'Feedback on Online Portal', 'The new online portal is very user-friendly. Great job on the new design!', '2025-08-19 10:14:33', 'Replied'),
(6, 'Imran Abbas', 'imran.a@example.com', 'Admission for two children', 'I am looking to admit my two children to your school. Is there any sibling discount?', '2025-08-19 10:14:33', 'New'),
(7, 'Saba Qamar', 'saba.q@example.com', 'Request for School Prospectus', 'Please send me a digital copy of the school prospectus at this email address.', '2025-08-19 10:14:33', 'New');

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
(1, 'Annual Sports Day', 'Join us for a day of exciting sports activities and competitions.', '2023-11-10', '09:00:00', 'School Ground', '2025-08-19 08:53:20'),
(2, 'Parent-Teacher Meeting', 'Discuss student progress with teachers. All parents are encouraged to attend.', '2023-10-20', '14:00:00', 'School Auditorium', '2025-08-19 08:53:20'),
(3, 'Debate Competition', 'Inter-house debate competition for senior students.', '2023-12-01', '10:00:00', 'School Hall', '2025-08-19 08:53:20'),
(4, 'Art Exhibition', 'Showcasing student artworks from various classes.', '2025-09-19', '11:00:00', 'Art Room', '2025-08-19 08:53:20'),
(5, 'Book Fair', 'A book fair with a wide range of books will be held in the school library.', '2025-10-28', '10:00:00', 'School Library', '2025-08-19 10:14:33'),
(6, 'Charity Bake Sale', 'A bake sale to raise funds for a local charity. Your participation will be appreciated.', '2025-11-08', '11:00:00', 'School Courtyard', '2025-08-19 10:14:33'),
(7, 'Science Fair', 'Annual science fair showcasing innovative projects by our students.', '2025-11-20', '09:30:00', 'School Auditorium', '2025-08-19 10:14:33'),
(8, 'Poetry Recitation Competition', 'An inter-house poetry recitation competition for classes 6-8.', '2025-11-25', '10:30:00', 'School Hall', '2025-08-19 10:14:33'),
(9, 'Guest Speaker Session on IT', 'A session with a renowned IT professional on career opportunities in technology.', '2025-12-02', '11:30:00', 'School Auditorium', '2025-08-19 10:14:33');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `exam_date` date NOT NULL,
  `max_marks` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `name`, `class_id`, `subject_id`, `exam_date`, `max_marks`) VALUES
(1, 'Mid-Term Exam', 1, 1, '2023-11-15', 100),
(2, 'Mid-Term Exam', 1, 2, '2023-11-16', 100),
(3, 'Monthly Test - October', 3, 2, '2025-10-30', 50),
(4, 'Monthly Test - October', 4, 5, '2025-10-31', 50);

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('Paid','Unpaid','Partially Paid') NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `amount`, `due_date`, `paid_date`, `status`, `description`) VALUES
(1, 1, 5000.00, '2023-10-01', NULL, 'Unpaid', 'Tuition Fee - October'),
(2, 2, 5000.00, '2023-10-01', NULL, 'Paid', 'Tuition Fee - October'),
(3, 3, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(4, 4, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(5, 5, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(6, 6, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(7, 7, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(8, 8, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(9, 9, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(10, 10, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(11, 11, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(12, 12, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(13, 13, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(14, 14, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(15, 15, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(16, 16, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(17, 17, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(18, 18, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(19, 19, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(20, 20, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(21, 21, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November'),
(22, 22, 5500.00, '2025-11-01', NULL, 'Unpaid', 'Tuition Fee - November');

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
(5, 'The opening of 2025', 'uploads/gallery/68a449a03eb42_7_Tips_to_Prepare_for_the_First_Day_of_School.png', '2025-08-19 09:53:36'),
(6, 'Annual Sports Day 2022', 'uploads/gallery/68a44a632d325_16424433938_f69a62d687_o.jpg', '2025-08-19 09:56:51'),
(7, 'Science Fair 2023', 'uploads/gallery/68a44a71821b0_Pakistani-children-in-schools.jpg', '2025-08-19 09:57:05'),
(8, 'Graduation Ceremony 2023', 'uploads/gallery/68a44ab693382_484620342_1048746340607867_4018364831364722985_n.jpg', '2025-08-19 09:58:14'),
(9, 'Art Competition Winners', 'uploads/gallery/68a44af61e4fe_493937000_1249364323858819_992749151734762105_n.jpg', '2025-08-19 09:59:18'),
(10, 'Tree Plantation Drive', 'uploads/gallery/tree_plantation.jpg', '2025-08-19 10:14:33'),
(11, 'Cultural Day 2025', 'uploads/gallery/cultural_day_2025.jpg', '2025-08-19 10:14:33'),
(12, 'Charity Bake Sale Event', 'uploads/gallery/bake_sale.jpg', '2025-08-19 10:14:33'),
(13, 'School Trip to Museum', 'uploads/gallery/museum_trip.jpg', '2025-08-19 10:14:33');

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
(3, 3, 3, 45),
(4, 3, 4, 38),
(5, 3, 5, 42),
(6, 3, 6, 35),
(7, 3, 7, 48),
(8, 3, 8, 41),
(9, 3, 9, 33),
(10, 3, 10, 46),
(11, 3, 11, 39),
(12, 3, 12, 44),
(13, 4, 13, 40),
(14, 4, 14, 43),
(15, 4, 15, 37),
(16, 4, 16, 47),
(17, 4, 17, 36),
(18, 4, 18, 49),
(19, 4, 19, 34),
(20, 4, 20, 45),
(21, 4, 21, 38),
(22, 4, 22, 46);

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
  `parent_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `name`, `class_id`, `roll_no`, `dob`, `address`, `phone`, `parent_id`) VALUES
(1, 3, 'Fatima Khan', 1, '10A-001', '2008-05-15', 'House 1, Street 2, Lahore', '03219876543', 4),
(2, 5, 'Ahmed Raza', 1, '10A-002', '2008-06-20', 'Flat 5, Block B, Islamabad', '03331234567', 4),
(3, 14, 'Ayesha Iqbal', 3, '8A-001', '2010-01-10', NULL, NULL, 9),
(4, 15, 'Bilal Khan', 3, '8A-002', '2010-02-12', NULL, NULL, 9),
(5, 16, 'Fatima Ali', 3, '8A-003', '2010-03-14', NULL, NULL, 9),
(6, 17, 'Hassan Raza', 3, '8A-004', '2010-04-16', NULL, NULL, 9),
(7, 18, 'Sana Ahmed', 3, '8A-005', '2010-05-18', NULL, NULL, 10),
(8, 19, 'Zainab Mehmood', 3, '8A-006', '2010-06-20', NULL, NULL, 10),
(9, 20, 'Usman Tariq', 3, '8A-007', '2010-07-22', NULL, NULL, 10),
(10, 21, 'Maryam Imran', 3, '8A-008', '2010-08-24', NULL, NULL, 10),
(11, 22, 'Ali Hassan', 3, '8A-009', '2010-09-26', NULL, NULL, 11),
(12, 23, 'Kinza Batool', 3, '8A-010', '2010-10-28', NULL, NULL, 11),
(13, 24, 'Abdullah Nasir', 4, '7C-001', '2011-01-15', NULL, NULL, 11),
(14, 25, 'Eman Fatima', 4, '7C-002', '2011-02-17', NULL, NULL, 11),
(15, 26, 'Saad Ali', 4, '7C-003', '2011-03-19', NULL, NULL, 12),
(16, 27, 'Hira Khan', 4, '7C-004', '2011-04-21', NULL, NULL, 12),
(17, 28, 'Fahad Iqbal', 4, '7C-005', '2011-05-23', NULL, NULL, 12),
(18, 29, 'Iqra Asif', 4, '7C-006', '2011-06-25', NULL, NULL, 12),
(19, 30, 'Hamza Farooq', 4, '7C-007', '2011-07-27', NULL, NULL, 13),
(20, 31, 'Mahnoor Shah', 4, '7C-008', '2011-08-29', NULL, NULL, 13),
(21, 32, 'Arslan Zafar', 4, '7C-009', '2011-09-01', NULL, NULL, 13),
(22, 33, 'Khadija Bibi', 4, '7C-010', '2011-10-03', NULL, NULL, 13);

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
(1, 2, 'Mr. Ali Ahmed', 'Mathematics', '03001234567', 'teacher1@example.com'),
(2, 7, 'Ms. Sana Javed', 'Science', '03011122334', 'teacher2@example.com'),
(3, 8, 'Mr. Bilal Ashraf', 'English', '03022233445', 'teacher3@example.com');

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
(1, 'admin', '$2y$10$yHhI5ksDdqZisW1Hdrz9DOAWfh4B94psWMKk75NDKtfXAQbjnOnpa', 'admin', 'admin@example.com', '2025-08-19 08:53:19'),
(2, 'teacher1', '$2y$10$yHhI5ksDdqZisW1Hdrz9DOAWfh4B94psWMKk75NDKtfXAQbjnOnpa', 'teacher', 'teacher1@example.com', '2025-08-19 08:53:19'),
(3, 'student1', '$2y$10$yHhI5ksDdqZisW1Hdrz9DOAWfh4B94psWMKk75NDKtfXAQbjnOnpa', 'student', 'studentpass', '2025-08-19 08:53:19'),
(4, 'parent1', '$2y$10$yHhI5ksDdqZisW1Hdrz9DOAWfh4B94psWMKk75NDKtfXAQbjnOnpa', 'parent', 'parent1@example.com', '2025-08-19 08:53:19'),
(5, 'student2', '$2y$10$yHhI5ksDdqZisW1Hdrz9DOAWfh4B94psWMKk75NDKtfXAQbjnOnpa', 'student', 'student2@example.com', '2025-08-19 08:53:19'),
(6, 'parent2', '$2y$10$yHhI5ksDdqZisW1Hdrz9DOAWfh4B94psWMKk75NDKtfXAQbjnOnpa', 'parent', 'parent2@example.com', '2025-08-19 08:53:19'),
(7, 'teacher2', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'teacher', 'teacher2@example.com', '2025-08-19 10:14:33'),
(8, 'teacher3', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'teacher', 'teacher3@example.com', '2025-08-19 10:14:33'),
(9, 'parent3', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'parent', 'parent3@example.com', '2025-08-19 10:14:33'),
(10, 'parent4', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'parent', 'parent4@example.com', '2025-08-19 10:14:33'),
(11, 'parent5', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'parent', 'parent5@example.com', '2025-08-19 10:14:33'),
(12, 'parent6', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'parent', 'parent6@example.com', '2025-08-19 10:14:33'),
(13, 'parent7', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'parent', 'parent7@example.com', '2025-08-19 10:14:33'),
(14, 'student3', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student3@example.com', '2025-08-19 10:14:33'),
(15, 'student4', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student4@example.com', '2025-08-19 10:14:33'),
(16, 'student5', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student5@example.com', '2025-08-19 10:14:33'),
(17, 'student6', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student6@example.com', '2025-08-19 10:14:33'),
(18, 'student7', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student7@example.com', '2025-08-19 10:14:33'),
(19, 'student8', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student8@example.com', '2025-08-19 10:14:33'),
(20, 'student9', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student9@example.com', '2025-08-19 10:14:33'),
(21, 'student10', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student10@example.com', '2025-08-19 10:14:33'),
(22, 'student11', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student11@example.com', '2025-08-19 10:14:33'),
(23, 'student12', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student12@example.com', '2025-08-19 10:14:33'),
(24, 'student13', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student13@example.com', '2025-08-19 10:14:33'),
(25, 'student14', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student14@example.com', '2025-08-19 10:14:33'),
(26, 'student15', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student15@example.com', '2025-08-19 10:14:33'),
(27, 'student16', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student16@example.com', '2025-08-19 10:14:33'),
(28, 'student17', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student17@example.com', '2025-08-19 10:14:33'),
(29, 'student18', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student18@example.com', '2025-08-19 10:14:33'),
(30, 'student19', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student19@example.com', '2025-08-19 10:14:33'),
(31, 'student20', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student20@example.com', '2025-08-19 10:14:33'),
(32, 'student21', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student21@example.com', '2025-08-19 10:14:33'),
(33, 'student22', '$2y$10$2.E26/1s/f8t0.i.4JzM.uLwGk/u4i5Sjvy7g3i3e/QxWV8We9/qm', 'student', 'student22@example.com', '2025-08-19 10:14:33');

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
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

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
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `student_assignments`
--
ALTER TABLE `student_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assignment_id` (`assignment_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `student_assignments`
--
ALTER TABLE `student_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_assignments`
--
ALTER TABLE `student_assignments`
  ADD CONSTRAINT `student_assignments_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_assignments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
