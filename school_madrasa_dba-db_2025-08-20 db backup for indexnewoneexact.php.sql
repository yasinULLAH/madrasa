-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 20, 2025 at 12:22 PM
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
-- Database: `school_madrasa_dba`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int NOT NULL,
  `description` text NOT NULL,
  `date` datetime NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `userId` int DEFAULT NULL,
  `details` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `description`, `date`, `type`, `user`, `userId`, `details`, `createdAt`, `updatedAt`) VALUES
(1, 'Attendance saved', '2025-08-20 07:18:46', 'Attendance', 'Super Admin', 1, 'Attendance for Class and Section on 2025-08-20 saved', '2025-08-20 07:18:46', '2025-08-20 07:18:46');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `classId` int NOT NULL,
  `sectionId` int NOT NULL,
  `studentId` int NOT NULL,
  `status` varchar(20) NOT NULL,
  `isLate` tinyint(1) DEFAULT NULL,
  `isLeave` tinyint(1) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `date`, `classId`, `sectionId`, `studentId`, `status`, `isLate`, `isLeave`, `notes`, `createdAt`, `updatedAt`) VALUES
(1, '2025-08-20', 1, 1, 1, 'present', 1, 0, 'new', '2025-08-20 07:18:46', '2025-08-20 07:18:46');

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `format` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `size` int DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `isAutomatic` tinyint(1) DEFAULT '0',
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `backups`
--

INSERT INTO `backups` (`id`, `type`, `format`, `date`, `size`, `filename`, `isAutomatic`, `createdAt`) VALUES
(1, 'full', 'sql', '2025-08-20 14:25:01', 119584, 'school_madrasa_backup_full_2025-08-20_10-25-01.sql', 0, '2025-08-20 09:25:01');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `copies` int DEFAULT NULL,
  `availableCopies` int DEFAULT NULL,
  `shelf` varchar(100) DEFAULT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book_issues`
--

CREATE TABLE `book_issues` (
  `id` int NOT NULL,
  `bookId` int NOT NULL,
  `memberId` int NOT NULL,
  `memberType` varchar(50) NOT NULL,
  `issueDate` date NOT NULL,
  `returnDueDate` date DEFAULT NULL,
  `actualReturnDate` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `fine` decimal(10,2) DEFAULT NULL,
  `condition` varchar(50) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `inChargeId` int DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `roomNumber` varchar(50) DEFAULT NULL,
  `feeCategory` varchar(50) DEFAULT NULL,
  `monthlyFee` decimal(10,2) DEFAULT NULL,
  `admissionFee` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `inChargeId`, `level`, `roomNumber`, `feeCategory`, `monthlyFee`, `admissionFee`, `notes`, `createdAt`, `updatedAt`) VALUES
(1, '1st Grade', 1, 'Elementary', '101', '', 500.00, 1000.00, '', '2025-08-20 06:55:00', '2025-08-20 07:18:24'),
(2, '2nd Grade', 2, 'Elementary', '102', NULL, 500.00, 1000.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:06'),
(3, '3rd Grade', 3, 'Elementary', '103', NULL, 600.00, 1000.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:06'),
(4, '4th Grade', 4, 'Elementary', '104', NULL, 600.00, 1000.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:06'),
(5, '5th Grade', 5, 'Elementary', '105', NULL, 700.00, 1000.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:06'),
(6, '6th Grade', 6, 'Secondary', '201', NULL, 800.00, 1500.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:06'),
(7, '7th Grade', NULL, 'Secondary', '202', NULL, 800.00, 1500.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:00'),
(8, '8th Grade', NULL, 'Secondary', '203', NULL, 900.00, 1500.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:00'),
(9, '9th Grade', NULL, 'Senior', '301', NULL, 1000.00, 2000.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:00'),
(10, '10th Grade', NULL, 'Senior', '302', NULL, 1000.00, 2000.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:00'),
(11, 'Hifz-ul-Quran', NULL, 'Special', '401', NULL, 1200.00, 2500.00, NULL, '2025-08-20 06:55:00', '2025-08-20 06:55:00'),
(12, 'Nazra Quran', NULL, 'Special', '402', NULL, 800.00, 1500.00, NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(13, '9th', 1, 'Elementary', '2', 'General', 2500.00, 0.00, 'new', '2025-08-20 07:13:00', '2025-08-20 07:13:00');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int NOT NULL,
  `classId` int NOT NULL,
  `subjectId` int NOT NULL,
  `teacherId` int NOT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `classes` text,
  `passingPercent` int DEFAULT NULL,
  `details` text,
  `status` varchar(50) DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int NOT NULL,
  `receiptNumber` varchar(50) DEFAULT NULL,
  `studentId` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fine` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `monthlyFeeAtTimeOfPayment` decimal(10,2) DEFAULT NULL,
  `date` date NOT NULL,
  `paymentMethod` varchar(50) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure`
--

CREATE TABLE `fee_structure` (
  `id` int NOT NULL,
  `classId` int NOT NULL,
  `category` varchar(50) NOT NULL,
  `monthlyFee` decimal(10,2) DEFAULT NULL,
  `admissionFee` decimal(10,2) DEFAULT NULL,
  `examFee` decimal(10,2) DEFAULT NULL,
  `computerFee` decimal(10,2) DEFAULT NULL,
  `transportFee` decimal(10,2) DEFAULT NULL,
  `otherFee` decimal(10,2) DEFAULT NULL,
  `details` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int NOT NULL,
  `examId` int NOT NULL,
  `classId` int NOT NULL,
  `sectionId` int NOT NULL,
  `subjectId` int NOT NULL,
  `studentId` int NOT NULL,
  `totalMarks` int DEFAULT NULL,
  `obtainedMarks` int DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `isPassed` tinyint(1) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `permissions` json DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `permissions`, `createdAt`, `updatedAt`) VALUES
(1, 'super_admin', 'Full System Administrator Access', '{\"fees\": true, \"exams\": true, \"staff\": true, \"users\": true, \"backup\": true, \"classes\": true, \"library\": true, \"reports\": true, \"id-cards\": true, \"salaries\": true, \"settings\": true, \"students\": true, \"subjects\": true, \"teachers\": true, \"dashboard\": true, \"attendance\": true}', '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(2, 'admin', 'Standard Administrator Access', '{\"fees\": true, \"exams\": true, \"staff\": true, \"users\": false, \"backup\": true, \"classes\": true, \"library\": true, \"reports\": true, \"id-cards\": true, \"salaries\": true, \"settings\": false, \"students\": true, \"subjects\": true, \"teachers\": true, \"dashboard\": true, \"attendance\": true}', '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(3, 'teacher', 'Teacher', '{\"fees\": false, \"exams\": true, \"staff\": false, \"users\": false, \"backup\": false, \"classes\": true, \"library\": true, \"reports\": true, \"id-cards\": false, \"salaries\": false, \"settings\": false, \"students\": true, \"subjects\": true, \"teachers\": true, \"dashboard\": true, \"attendance\": true}', '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(4, 'librarian', 'Librarian', '{\"fees\": false, \"exams\": false, \"staff\": false, \"users\": false, \"backup\": false, \"classes\": false, \"library\": true, \"reports\": true, \"id-cards\": false, \"salaries\": false, \"settings\": false, \"students\": true, \"subjects\": false, \"teachers\": true, \"dashboard\": true, \"attendance\": false}', '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(5, 'accountant', 'Accountant', '{\"fees\": true, \"exams\": false, \"staff\": true, \"users\": false, \"backup\": false, \"classes\": false, \"library\": false, \"reports\": true, \"id-cards\": false, \"salaries\": true, \"settings\": false, \"students\": true, \"subjects\": false, \"teachers\": true, \"dashboard\": true, \"attendance\": false}', '2025-08-20 06:55:07', '2025-08-20 06:55:07');

-- --------------------------------------------------------

--
-- Table structure for table `salary`
--

CREATE TABLE `salary` (
  `id` int NOT NULL,
  `voucherNumber` varchar(50) DEFAULT NULL,
  `employeeId` int NOT NULL,
  `employeeType` varchar(50) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int NOT NULL,
  `amountPaid` decimal(10,2) DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `grossSalaryAtTimeOfPayment` decimal(10,2) DEFAULT NULL,
  `date` date NOT NULL,
  `paymentMethod` varchar(50) DEFAULT NULL,
  `transactionId` varchar(255) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `salary`
--

INSERT INTO `salary` (`id`, `voucherNumber`, `employeeId`, `employeeType`, `month`, `year`, `amountPaid`, `bonus`, `deductions`, `total`, `grossSalaryAtTimeOfPayment`, `date`, `paymentMethod`, `transactionId`, `notes`, `createdAt`, `updatedAt`) VALUES
(1, 'SAL846025', 1, 'teacher', 'January', 2025, 30000.00, 0.00, 0.00, 30000.00, 30000.00, '2025-08-20', 'Cash', '', '', '2025-08-20 11:20:46', '2025-08-20 11:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `salary_structure`
--

CREATE TABLE `salary_structure` (
  `id` int NOT NULL,
  `employeeId` int NOT NULL,
  `employeeType` varchar(50) NOT NULL,
  `basicSalary` decimal(10,2) DEFAULT NULL,
  `houseRent` decimal(10,2) DEFAULT NULL,
  `transport` decimal(10,2) DEFAULT NULL,
  `medical` decimal(10,2) DEFAULT NULL,
  `otherAllowances` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `totalSalary` decimal(10,2) DEFAULT NULL,
  `details` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int NOT NULL,
  `classId` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `inChargeId` int DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `classId`, `name`, `inChargeId`, `createdAt`, `updatedAt`) VALUES
(1, 1, 'A', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(2, 1, 'B', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(3, 1, 'C', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(4, 2, 'A', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(5, 2, 'B', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(6, 2, 'C', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(7, 3, 'A', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(8, 3, 'B', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(9, 3, 'C', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(10, 4, 'A', NULL, '2025-08-20 06:55:01', '2025-08-20 06:55:01'),
(11, 4, 'B', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(12, 4, 'C', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(13, 5, 'A', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(14, 5, 'B', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(15, 5, 'C', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(16, 6, 'A', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(17, 6, 'B', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(18, 6, 'C', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(19, 7, 'A', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(20, 7, 'B', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(21, 7, 'C', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(22, 8, 'A', NULL, '2025-08-20 06:55:02', '2025-08-20 06:55:02'),
(23, 8, 'B', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(24, 8, 'C', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(25, 9, 'A', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(26, 9, 'B', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(27, 9, 'C', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(28, 10, 'A', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(29, 10, 'B', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(30, 10, 'C', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(31, 11, 'A', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(32, 11, 'B', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(33, 11, 'C', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(34, 12, 'A', NULL, '2025-08-20 06:55:03', '2025-08-20 06:55:03'),
(35, 12, 'B', NULL, '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(36, 12, 'C', NULL, '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(37, 13, 'B', NULL, '2025-08-20 07:13:00', '2025-08-20 07:13:00');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` varchar(50) NOT NULL,
  `schoolName` varchar(255) NOT NULL,
  `principalName` varchar(255) NOT NULL,
  `schoolAddress` text NOT NULL,
  `schoolPhone` varchar(50) NOT NULL,
  `schoolEmail` varchar(255) DEFAULT NULL,
  `schoolWebsite` varchar(255) DEFAULT NULL,
  `currentSession` varchar(50) DEFAULT NULL,
  `sessionStart` date DEFAULT NULL,
  `sessionEnd` date DEFAULT NULL,
  `schoolTimeStart` time DEFAULT NULL,
  `schoolTimeEnd` time DEFAULT NULL,
  `periodDuration` int DEFAULT NULL,
  `weeklyHoliday` text,
  `feeDueDay` int DEFAULT NULL,
  `feeLateFine` decimal(10,2) DEFAULT NULL,
  `libraryReturnDays` int DEFAULT NULL,
  `libraryLateFine` decimal(10,2) DEFAULT NULL,
  `primaryColor` varchar(7) DEFAULT NULL,
  `secondaryColor` varchar(7) DEFAULT NULL,
  `fontSize` varchar(20) DEFAULT NULL,
  `fontFamily` varchar(255) DEFAULT NULL,
  `sidebarPosition` varchar(10) DEFAULT NULL,
  `sidebarTheme` varchar(10) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `autoBackupEnabled` tinyint(1) DEFAULT '0',
  `autoBackupFrequency` varchar(20) DEFAULT NULL,
  `autoBackupRetention` int DEFAULT NULL,
  `autoBackupTime` time DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `schoolName`, `principalName`, `schoolAddress`, `schoolPhone`, `schoolEmail`, `schoolWebsite`, `currentSession`, `sessionStart`, `sessionEnd`, `schoolTimeStart`, `schoolTimeEnd`, `periodDuration`, `weeklyHoliday`, `feeDueDay`, `feeLateFine`, `libraryReturnDays`, `libraryLateFine`, `primaryColor`, `secondaryColor`, `fontSize`, `fontFamily`, `sidebarPosition`, `sidebarTheme`, `language`, `autoBackupEnabled`, `autoBackupFrequency`, `autoBackupRetention`, `autoBackupTime`, `createdAt`, `updatedAt`) VALUES
('general', 'Yasin school', 'Principal Name', 'School Address, City', '03001234567', 'email@example.com', 'http://example.com', '2025-2026', '2025-08-01', '2026-05-31', '08:00:00', '14:00:00', 45, '[\"Sunday\"]', 10, 100.00, 15, 5.00, '#3498db', '#2c3e50', 'medium', 'Arial, sans-serif', 'left', 'dark', 'en', 1, 'daily', 7, '02:00:00', '2025-08-20 10:37:22', '2025-08-20 10:38:48');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int NOT NULL,
  `employeeId` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fatherName` varchar(255) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `responsibilities` text,
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `cnic` varchar(50) DEFAULT NULL,
  `joiningDate` date NOT NULL,
  `basicSalary` decimal(10,2) DEFAULT NULL,
  `allowances` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `photo` varchar(255) DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `employeeId`, `name`, `fatherName`, `gender`, `dob`, `designation`, `department`, `qualification`, `responsibilities`, `contact`, `email`, `address`, `cnic`, `joiningDate`, `basicSalary`, `allowances`, `deductions`, `notes`, `photo`, `createdAt`, `updatedAt`) VALUES
(1, 'S001', 'Muhammad Arif', 'Muhammad Aslam', 'Male', '1985-06-20', 'Office Clerk', 'Administration', 'B.A', 'Office work, admission, registration', '0300-7890123', 'arif@example.com', 'Faisal Town, Lahore', '35201-7890123-1', '2018-01-15', 18000.00, 2000.00, 0.00, '', NULL, '2025-08-20 06:55:06', '2025-08-20 06:55:06'),
(2, 'S002', 'Muhammad Siddique', 'Muhammad Yusuf', 'Male', '1970-04-10', 'Librarian', 'Library', 'M.A Library Science', 'Library management, book issue/return', '0300-8901234', 'siddique@example.com', 'Township, Lahore', '35201-8901234-1', '2015-05-01', 20000.00, 3000.00, 0.00, '', NULL, '2025-08-20 06:55:06', '2025-08-20 06:55:06'),
(3, 'S003', 'Abdul Ghafoor', 'Abdul Razzaq', 'Male', '1975-08-15', 'Security Guard', 'Security', 'Matric', 'School security, gate checking', '0300-9012345', NULL, 'Begum Pura, Lahore', '35201-9012345-1', '2017-03-01', 15000.00, 2000.00, 0.00, '', NULL, '2025-08-20 06:55:07', '2025-08-20 06:55:07');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `rollNumber` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `fatherName` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `classId` int NOT NULL,
  `sectionId` int DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `guardian` varchar(255) DEFAULT NULL,
  `guardianContact` varchar(50) DEFAULT NULL,
  `address` text,
  `photo` varchar(255) DEFAULT NULL,
  `admissionDate` date NOT NULL,
  `feeCategory` varchar(50) DEFAULT NULL,
  `monthlyFee` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `status` varchar(20) DEFAULT 'active',
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `rollNumber`, `name`, `fatherName`, `dob`, `gender`, `classId`, `sectionId`, `contact`, `email`, `guardian`, `guardianContact`, `address`, `photo`, `admissionDate`, `feeCategory`, `monthlyFee`, `notes`, `status`, `createdAt`, `updatedAt`) VALUES
(1, '0001', 'yasin', 'Amirzada', '2025-08-01', 'Male', 1, 1, '03323211123', 'yasincomps@gmail.com', 'Abdul Ghafoor', '03055702909', 'new', 'client_pics/students_68a5ac16cc2d17.21712132.jpeg', '2025-08-20', 'General', 0.00, 'new', 'active', '2025-08-20 07:17:40', '2025-08-20 11:05:58');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `periods` int DEFAULT NULL,
  `details` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `type`, `periods`, `details`, `createdAt`, `updatedAt`) VALUES
(1, 'Holy Quran', 'QUR', 'Quran', 5, 'Teaching of Holy Quran', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(2, 'Nazra Quran', 'NAZ', 'Quran', 5, 'Recitation of Holy Quran', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(3, 'Hifz-ul-Quran', 'HIF', 'Quran', 10, 'Memorization of Holy Quran', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(4, 'Tajweed', 'TAJ', 'Quran', 3, 'Correct recitation of Holy Quran', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(5, 'Tafseer-ul-Quran', 'TAF', 'Quran', 3, 'Exegesis of Holy Quran', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(6, 'Hadith', 'HAD', 'Hadith', 4, 'Teaching of Prophetic Traditions', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(7, 'Fiqh', 'FIQ', 'Fiqh', 3, 'Teaching of Islamic Jurisprudence', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(8, 'Aqeedah', 'AQA', 'Aqeedah', 2, 'Teaching of Islamic Beliefs', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(9, 'Seerat-un-Nabi', 'SIR', 'Seerah', 2, 'Teaching of Prophet Muhammad\'s biography', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(10, 'Ethics', 'AKH', 'Akhlaq', 2, 'Teaching of Islamic Ethics', '2025-08-20 06:55:04', '2025-08-20 06:55:04'),
(11, 'Arabic', 'ARB', 'Arabic', 4, 'Teaching of Arabic language', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(12, 'Urdu', 'URD', 'Urdu', 4, 'Teaching of Urdu language', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(13, 'English', 'ENG', 'English', 4, 'Teaching of English language', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(14, 'Mathematics', 'MAT', 'Math', 5, 'Teaching of Mathematics', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(15, 'Science', 'SCI', 'Science', 4, 'Teaching of Science', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(16, 'Social Studies', 'SOC', 'Social Studies', 3, 'Teaching of Social Studies', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(17, 'Pakistan Studies', 'PAK', 'Social Studies', 2, 'History and Geography of Pakistan', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(18, 'Islamiyat', 'ISL', 'Islamiyat', 3, 'Teaching of Islamic Studies', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(19, 'Computer', 'COM', 'Computer', 2, 'Teaching of Computer Science', '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(20, 'Health & Physical Education', 'PHY', 'Health', 2, 'Health and Physical Education', '2025-08-20 06:55:05', '2025-08-20 06:55:05');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int NOT NULL,
  `employeeId` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fatherName` varchar(255) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `subjects` text,
  `classes` text,
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `cnic` varchar(50) DEFAULT NULL,
  `joiningDate` date NOT NULL,
  `basicSalary` decimal(10,2) DEFAULT NULL,
  `allowances` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `photo` varchar(255) DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `employeeId`, `name`, `fatherName`, `gender`, `dob`, `qualification`, `specialization`, `subjects`, `classes`, `contact`, `email`, `address`, `cnic`, `joiningDate`, `basicSalary`, `allowances`, `deductions`, `notes`, `photo`, `createdAt`, `updatedAt`) VALUES
(1, 'T001', 'Qari Muhammad Ahmad', 'Muhammad Yusuf', 'Male', '1980-05-15', 'Alim Fazil, Qari', 'Quran & Hadith', '[\"1\",\"6\",\"7\"]', '[]', '0300-1234567', 'ahmad@example.com', 'Hafizabad, Lahore', '35201-1234567-1', '2015-03-01', 25000.00, 5000.00, 0.00, '', NULL, '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(2, 'T002', 'Maulana Abdul Rahman', 'Abdul Ghafoor', 'Male', '1975-09-20', 'Shaykh-ul-Hadith, Mufti', 'Fiqh & Usool', '[\"1\",\"6\",\"7\"]', '[]', '0300-2345678', 'abdulrehman@example.com', 'Johar Town, Lahore', '35201-2345678-1', '2010-01-15', 30000.00, 7000.00, 0.00, '', NULL, '2025-08-20 06:55:05', '2025-08-20 06:55:05'),
(3, 'T003', 'Hafiz Muhammad Umar', 'Muhammad Aslam', 'Male', '1985-03-10', 'Hafiz, Alim', 'Quran & Tajweed', '[\"1\",\"2\",\"3\",\"4\"]', '[]', '0300-3456789', 'umar@example.com', 'Township, Lahore', '35201-3456789-1', '2018-08-01', 22000.00, 3000.00, 0.00, '', NULL, '2025-08-20 06:55:06', '2025-08-20 06:55:06'),
(4, 'T004', 'Ustad Muhammad Ali', 'Muhammad Akram', 'Male', '1982-12-05', 'M.A Arabic, B.Ed', 'Arabic & Urdu', '[\"11\",\"12\"]', '[]', '0300-4567890', 'ali@example.com', 'Faisal Town, Lahore', '35201-4567890-1', '2016-04-15', 20000.00, 4000.00, 0.00, '', NULL, '2025-08-20 06:55:06', '2025-08-20 06:55:06'),
(5, 'T005', 'Ustad Muhammad Imran', 'Muhammad Afzal', 'Male', '1988-07-25', 'M.Sc Math, B.Ed', 'Mathematics & Science', '[\"14\",\"15\"]', '[]', '0300-5678901', 'imran@example.com', 'Model Town, Lahore', '35201-5678901-1', '2017-09-01', 23000.00, 5000.00, 0.00, '', NULL, '2025-08-20 06:55:06', '2025-08-20 06:55:06'),
(6, 'T006', 'Muallimah Ayesha', 'Abdul Sattar', 'Female', '1990-11-12', 'Alimah Fazilah, B.Ed', 'Islamiyat & Urdu', '[\"12\",\"18\"]', '[]', '0300-6789012', 'ayesha@example.com', 'Gulshan-e-Ravi, Lahore', '35201-6789012-2', '2019-03-15', 20000.00, 3000.00, 0.00, '', NULL, '2025-08-20 06:55:06', '2025-08-20 06:55:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `address` text,
  `photo` varchar(255) DEFAULT NULL,
  `lastLogin` datetime DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `role`, `status`, `mobile`, `address`, `photo`, `lastLogin`, `createdAt`, `updatedAt`) VALUES
(1, 'superadmin', 'superadmin123', 'Super Admin', 'superadmin@schoolmadrasa.com', 'super_admin', 'active', '0300-0000000', 'Secret Location', NULL, NULL, '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(2, 'admin', 'admin123', 'System Admin', 'admin@schoolmadrasa.com', 'admin', 'active', '0300-1234567', 'Lahore', NULL, NULL, '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(3, 'teacher1', 'teacher123', 'Qari Muhammad Ahmad', 'ahmad@example.com', 'teacher', 'active', '0300-1234567', 'Hafizabad, Lahore', NULL, NULL, '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(4, 'librarian', 'librarian123', 'Muhammad Siddique', 'siddique@example.com', 'librarian', 'active', '0300-8901234', 'Township, Lahore', NULL, NULL, '2025-08-20 06:55:07', '2025-08-20 06:55:07'),
(5, 'accountant', 'accountant123', 'Muhammad Arif', 'arif@example.com', 'accountant', 'active', '0300-7890123', 'Faisal Town, Lahore', NULL, NULL, '2025-08-20 06:55:07', '2025-08-20 06:55:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`,`studentId`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `book_issues`
--
ALTER TABLE `book_issues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `classId` (`classId`,`subjectId`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receiptNumber` (`receiptNumber`);

--
-- Indexes for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `classId` (`classId`,`category`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `examId` (`examId`,`studentId`,`subjectId`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `salary`
--
ALTER TABLE `salary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucherNumber` (`voucherNumber`);

--
-- Indexes for table `salary_structure`
--
ALTER TABLE `salary_structure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employeeId` (`employeeId`,`employeeType`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `classId` (`classId`,`name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employeeId` (`employeeId`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rollNumber` (`rollNumber`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employeeId` (`employeeId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `book_issues`
--
ALTER TABLE `book_issues`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structure`
--
ALTER TABLE `fee_structure`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `salary`
--
ALTER TABLE `salary`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `salary_structure`
--
ALTER TABLE `salary_structure`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
