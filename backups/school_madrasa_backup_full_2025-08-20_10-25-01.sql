-- MySQL dump 10.13  Distrib 8.2.0, for Win64 (x86_64)
--
-- Host: localhost    Database: school_madrasa_dba
-- ------------------------------------------------------
-- Server version	8.2.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `date` datetime NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `userId` int DEFAULT NULL,
  `details` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES (1,'Attendance saved','2025-08-20 07:18:46','Attendance','Super Admin',1,'Attendance for Class and Section on 2025-08-20 saved','2025-08-20 07:18:46','2025-08-20 07:18:46');
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `classId` int NOT NULL,
  `sectionId` int NOT NULL,
  `studentId` int NOT NULL,
  `status` varchar(20) NOT NULL,
  `isLate` tinyint(1) DEFAULT NULL,
  `isLeave` tinyint(1) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`,`studentId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,'2025-08-20',1,1,1,'present',1,0,'new','2025-08-20 07:18:46','2025-08-20 07:18:46');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `format` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `size` int DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `isAutomatic` tinyint(1) DEFAULT '0',
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_issues`
--

DROP TABLE IF EXISTS `book_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_issues` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_issues`
--

LOCK TABLES `book_issues` WRITE;
/*!40000 ALTER TABLE `book_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `copies` int DEFAULT NULL,
  `availableCopies` int DEFAULT NULL,
  `shelf` varchar(100) DEFAULT NULL,
  `description` text,
  `image` longblob,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_subjects`
--

DROP TABLE IF EXISTS `class_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `classId` int NOT NULL,
  `subjectId` int NOT NULL,
  `teacherId` int NOT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classId` (`classId`,`subjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_subjects`
--

LOCK TABLES `class_subjects` WRITE;
/*!40000 ALTER TABLE `class_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `class_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `inChargeId` int DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `roomNumber` varchar(50) DEFAULT NULL,
  `feeCategory` varchar(50) DEFAULT NULL,
  `monthlyFee` decimal(10,2) DEFAULT NULL,
  `admissionFee` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'1st Grade',1,'Elementary','101','',500.00,1000.00,'','2025-08-20 06:55:00','2025-08-20 07:18:24'),(2,'2nd Grade',2,'Elementary','102',NULL,500.00,1000.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:06'),(3,'3rd Grade',3,'Elementary','103',NULL,600.00,1000.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:06'),(4,'4th Grade',4,'Elementary','104',NULL,600.00,1000.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:06'),(5,'5th Grade',5,'Elementary','105',NULL,700.00,1000.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:06'),(6,'6th Grade',6,'Secondary','201',NULL,800.00,1500.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:06'),(7,'7th Grade',NULL,'Secondary','202',NULL,800.00,1500.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:00'),(8,'8th Grade',NULL,'Secondary','203',NULL,900.00,1500.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:00'),(9,'9th Grade',NULL,'Senior','301',NULL,1000.00,2000.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:00'),(10,'10th Grade',NULL,'Senior','302',NULL,1000.00,2000.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:00'),(11,'Hifz-ul-Quran',NULL,'Special','401',NULL,1200.00,2500.00,NULL,'2025-08-20 06:55:00','2025-08-20 06:55:00'),(12,'Nazra Quran',NULL,'Special','402',NULL,800.00,1500.00,NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(13,'9th',1,'Elementary','2','General',2500.00,0.00,'new','2025-08-20 07:13:00','2025-08-20 07:13:00');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `classes` text,
  `passingPercent` int DEFAULT NULL,
  `details` text,
  `status` varchar(50) DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structure`
--

DROP TABLE IF EXISTS `fee_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structure` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classId` (`classId`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structure`
--

LOCK TABLES `fee_structure` WRITE;
/*!40000 ALTER TABLE `fee_structure` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fees`
--

DROP TABLE IF EXISTS `fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fees` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receiptNumber` (`receiptNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fees`
--

LOCK TABLES `fees` WRITE;
/*!40000 ALTER TABLE `fees` DISABLE KEYS */;
/*!40000 ALTER TABLE `fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marks`
--

DROP TABLE IF EXISTS `marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marks` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `examId` (`examId`,`studentId`,`subjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marks`
--

LOCK TABLES `marks` WRITE;
/*!40000 ALTER TABLE `marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `permissions` json DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','Full System Administrator Access','{\"fees\": true, \"exams\": true, \"staff\": true, \"users\": true, \"backup\": true, \"classes\": true, \"library\": true, \"reports\": true, \"id-cards\": true, \"salaries\": true, \"settings\": true, \"students\": true, \"subjects\": true, \"teachers\": true, \"dashboard\": true, \"attendance\": true}','2025-08-20 06:55:07','2025-08-20 06:55:07'),(2,'admin','Standard Administrator Access','{\"fees\": true, \"exams\": true, \"staff\": true, \"users\": false, \"backup\": true, \"classes\": true, \"library\": true, \"reports\": true, \"id-cards\": true, \"salaries\": true, \"settings\": false, \"students\": true, \"subjects\": true, \"teachers\": true, \"dashboard\": true, \"attendance\": true}','2025-08-20 06:55:07','2025-08-20 06:55:07'),(3,'teacher','Teacher','{\"fees\": false, \"exams\": true, \"staff\": false, \"users\": false, \"backup\": false, \"classes\": true, \"library\": true, \"reports\": true, \"id-cards\": false, \"salaries\": false, \"settings\": false, \"students\": true, \"subjects\": true, \"teachers\": true, \"dashboard\": true, \"attendance\": true}','2025-08-20 06:55:07','2025-08-20 06:55:07'),(4,'librarian','Librarian','{\"fees\": false, \"exams\": false, \"staff\": false, \"users\": false, \"backup\": false, \"classes\": false, \"library\": true, \"reports\": true, \"id-cards\": false, \"salaries\": false, \"settings\": false, \"students\": true, \"subjects\": false, \"teachers\": true, \"dashboard\": true, \"attendance\": false}','2025-08-20 06:55:07','2025-08-20 06:55:07'),(5,'accountant','Accountant','{\"fees\": true, \"exams\": false, \"staff\": true, \"users\": false, \"backup\": false, \"classes\": false, \"library\": false, \"reports\": true, \"id-cards\": false, \"salaries\": true, \"settings\": false, \"students\": true, \"subjects\": false, \"teachers\": true, \"dashboard\": true, \"attendance\": false}','2025-08-20 06:55:07','2025-08-20 06:55:07');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary`
--

DROP TABLE IF EXISTS `salary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucherNumber` (`voucherNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary`
--

LOCK TABLES `salary` WRITE;
/*!40000 ALTER TABLE `salary` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_structure`
--

DROP TABLE IF EXISTS `salary_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_structure` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employeeId` (`employeeId`,`employeeType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_structure`
--

LOCK TABLES `salary_structure` WRITE;
/*!40000 ALTER TABLE `salary_structure` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `classId` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `inChargeId` int DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classId` (`classId`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES (1,1,'A',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(2,1,'B',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(3,1,'C',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(4,2,'A',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(5,2,'B',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(6,2,'C',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(7,3,'A',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(8,3,'B',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(9,3,'C',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(10,4,'A',NULL,'2025-08-20 06:55:01','2025-08-20 06:55:01'),(11,4,'B',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(12,4,'C',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(13,5,'A',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(14,5,'B',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(15,5,'C',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(16,6,'A',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(17,6,'B',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(18,6,'C',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(19,7,'A',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(20,7,'B',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(21,7,'C',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(22,8,'A',NULL,'2025-08-20 06:55:02','2025-08-20 06:55:02'),(23,8,'B',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(24,8,'C',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(25,9,'A',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(26,9,'B',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(27,9,'C',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(28,10,'A',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(29,10,'B',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(30,10,'C',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(31,11,'A',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(32,11,'B',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(33,11,'C',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(34,12,'A',NULL,'2025-08-20 06:55:03','2025-08-20 06:55:03'),(35,12,'B',NULL,'2025-08-20 06:55:04','2025-08-20 06:55:04'),(36,12,'C',NULL,'2025-08-20 06:55:04','2025-08-20 06:55:04'),(37,13,'B',NULL,'2025-08-20 07:13:00','2025-08-20 07:13:00');
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('','Deeni Madrasa','Principal Name','Madrasa Road, City','0300-1234567','info@deenimadrasa.com','www.deenimadrasa.com','2025-2026','2025-08-31','2026-05-30','08:00:00','14:00:00',45,'[\"Sunday\"]',10,50.00,14,5.00,'#3498db','#2c3e50','medium','Arial, sans-serif','left','dark','en',0,'monthly',3,'02:00:00','2025-08-20 07:14:57','2025-08-20 09:24:10');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `photo` longblob,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employeeId` (`employeeId`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,'S001','Muhammad Arif','Muhammad Aslam','Male','1985-06-20','Office Clerk','Administration','B.A','Office work, admission, registration','0300-7890123','arif@example.com','Faisal Town, Lahore','35201-7890123-1','2018-01-15',18000.00,2000.00,0.00,'',NULL,'2025-08-20 06:55:06','2025-08-20 06:55:06'),(2,'S002','Muhammad Siddique','Muhammad Yusuf','Male','1970-04-10','Librarian','Library','M.A Library Science','Library management, book issue/return','0300-8901234','siddique@example.com','Township, Lahore','35201-8901234-1','2015-05-01',20000.00,3000.00,0.00,'',NULL,'2025-08-20 06:55:06','2025-08-20 06:55:06'),(3,'S003','Abdul Ghafoor','Abdul Razzaq','Male','1975-08-15','Security Guard','Security','Matric','School security, gate checking','0300-9012345',NULL,'Begum Pura, Lahore','35201-9012345-1','2017-03-01',15000.00,2000.00,0.00,'',NULL,'2025-08-20 06:55:07','2025-08-20 06:55:07');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `photo` longblob,
  `admissionDate` date NOT NULL,
  `feeCategory` varchar(50) DEFAULT NULL,
  `monthlyFee` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `status` varchar(20) DEFAULT 'active',
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rollNumber` (`rollNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'0001','yasin','Amirzada','2025-08-01','Male',1,1,'03323211123','yasincomps@gmail.com','Abdul Ghafoor','03055702909','new',_binary 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCALDAkMDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9TaRutLSFabM4h/DQvWj+GhetSzcSnU2nUxdAoooqDIKKKKr7RX2gooooYMKKKKGDCiiimMKKKKggKKKKr7RX2gooooYMKKKKGDCiiimMKKKKggKKKKr7RX2gooopjCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooEwooooMwooooK2A/dpF60tFJlXE2n0paKKYwoooqCAoooqvtFfaCiiihgwooooYMKKKKYwoooqCAoooqvtFfaCiiihgwooooYMKKKKYwoooqCAoooqvtFfaCiiimMKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKBMKKKKDMKKKKACiiigphRRRSZX2QooopjCiiioICiiiq+0V9oKKKKGDCiiihgwooopjCiiioICiiiq+0V9oKKKKGDCiiihgwooopjCiiioICiiiq+0V9oKKKKYwooooAKKKKACiiigAoooPegAopNwo3UALRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQJhRRRQZhRRSbqCWLRSbqKBC0UUUGrCiiikyvshRRRTGFFFFQQFFFFV9or7QUUUUMGFFFFDBhRRRTGFFFFQQFFFFV9or7QUUUUMGFFFFDBhRRRTGFFFFQQFFFFV9or7QUUHvRTGFFFFABRRRQQFFFIzbVzRZlXFpCtVpNQghR3LDagy7Z2gV5l44/ag+Gvw/XZqvii0e7/AOfOyP2ib/vlKq1xcyR6ttpNvvXz1H+2r4L1C2WfSLHVtUQvs2pAIzu99zcCtpv2lbVoy0WlQzsOXgi1WHzk/wCAmnysXOj2xetLXlehfH/SdUl2XelanpaY4nljV4v++kNd/oninSfEdv52m6hb3if9MpAxFLlY+ZGrRQe9FSUgooooAKKKKgAoooqwCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooICg9KKazBRQTLYdTT1o8wetIWosQLRSbqKLAPooooNWFFFFJlfZCiiimMKKKKggKKKKr7RX2goooqSQoooqmUwooopjCiiioICiiiq+0V9oKKKKGDCiiihgwooopjCiiioICiikP3eauwNg3Ws6TxFpkNy1qb2D7SvWBZF3j/gNcD8cvjJpvwn8G6heSXAOpeUfs8S8nf2zXwroHx0Twrp8uowQSaj4muy91f6nczbERj90f8AAfSrUbmDq2P0kvvFOl6XavdXd9Da26dZZ3CL+ZrzvxN+1V8LvDMLtc+MNOlePrFBLvb9K/MHxt8bNR+IF3dG51G+1ZZvlLyXDtEn+yidK4fQ1t7rUVtLaCGDaeWxuZf+BVfKT7Vn6dXf/BQD4U2sKyLd6hcMeiQWjM1PtP2/PhPeQq4v9Qhdjt8qW0ZWH19K/N6/vtH8MzMlyWunD7dkbnJN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','2025-08-20','General',0.00,'new','active','2025-08-20 07:17:40','2025-08-20 07:17:40');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `periods` int DEFAULT NULL,
  `details` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'Holy Quran','QUR','Quran',5,'Teaching of Holy Quran','2025-08-20 06:55:04','2025-08-20 06:55:04'),(2,'Nazra Quran','NAZ','Quran',5,'Recitation of Holy Quran','2025-08-20 06:55:04','2025-08-20 06:55:04'),(3,'Hifz-ul-Quran','HIF','Quran',10,'Memorization of Holy Quran','2025-08-20 06:55:04','2025-08-20 06:55:04'),(4,'Tajweed','TAJ','Quran',3,'Correct recitation of Holy Quran','2025-08-20 06:55:04','2025-08-20 06:55:04'),(5,'Tafseer-ul-Quran','TAF','Quran',3,'Exegesis of Holy Quran','2025-08-20 06:55:04','2025-08-20 06:55:04'),(6,'Hadith','HAD','Hadith',4,'Teaching of Prophetic Traditions','2025-08-20 06:55:04','2025-08-20 06:55:04'),(7,'Fiqh','FIQ','Fiqh',3,'Teaching of Islamic Jurisprudence','2025-08-20 06:55:04','2025-08-20 06:55:04'),(8,'Aqeedah','AQA','Aqeedah',2,'Teaching of Islamic Beliefs','2025-08-20 06:55:04','2025-08-20 06:55:04'),(9,'Seerat-un-Nabi','SIR','Seerah',2,'Teaching of Prophet Muhammad\'s biography','2025-08-20 06:55:04','2025-08-20 06:55:04'),(10,'Ethics','AKH','Akhlaq',2,'Teaching of Islamic Ethics','2025-08-20 06:55:04','2025-08-20 06:55:04'),(11,'Arabic','ARB','Arabic',4,'Teaching of Arabic language','2025-08-20 06:55:05','2025-08-20 06:55:05'),(12,'Urdu','URD','Urdu',4,'Teaching of Urdu language','2025-08-20 06:55:05','2025-08-20 06:55:05'),(13,'English','ENG','English',4,'Teaching of English language','2025-08-20 06:55:05','2025-08-20 06:55:05'),(14,'Mathematics','MAT','Math',5,'Teaching of Mathematics','2025-08-20 06:55:05','2025-08-20 06:55:05'),(15,'Science','SCI','Science',4,'Teaching of Science','2025-08-20 06:55:05','2025-08-20 06:55:05'),(16,'Social Studies','SOC','Social Studies',3,'Teaching of Social Studies','2025-08-20 06:55:05','2025-08-20 06:55:05'),(17,'Pakistan Studies','PAK','Social Studies',2,'History and Geography of Pakistan','2025-08-20 06:55:05','2025-08-20 06:55:05'),(18,'Islamiyat','ISL','Islamiyat',3,'Teaching of Islamic Studies','2025-08-20 06:55:05','2025-08-20 06:55:05'),(19,'Computer','COM','Computer',2,'Teaching of Computer Science','2025-08-20 06:55:05','2025-08-20 06:55:05'),(20,'Health & Physical Education','PHY','Health',2,'Health and Physical Education','2025-08-20 06:55:05','2025-08-20 06:55:05');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `photo` longblob,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employeeId` (`employeeId`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,'T001','Qari Muhammad Ahmad','Muhammad Yusuf','Male','1980-05-15','Alim Fazil, Qari','Quran & Hadith','[\"1\",\"6\",\"7\"]','[]','0300-1234567','ahmad@example.com','Hafizabad, Lahore','35201-1234567-1','2015-03-01',25000.00,5000.00,0.00,'',NULL,'2025-08-20 06:55:05','2025-08-20 06:55:05'),(2,'T002','Maulana Abdul Rahman','Abdul Ghafoor','Male','1975-09-20','Shaykh-ul-Hadith, Mufti','Fiqh & Usool','[\"1\",\"6\",\"7\"]','[]','0300-2345678','abdulrehman@example.com','Johar Town, Lahore','35201-2345678-1','2010-01-15',30000.00,7000.00,0.00,'',NULL,'2025-08-20 06:55:05','2025-08-20 06:55:05'),(3,'T003','Hafiz Muhammad Umar','Muhammad Aslam','Male','1985-03-10','Hafiz, Alim','Quran & Tajweed','[\"1\",\"2\",\"3\",\"4\"]','[]','0300-3456789','umar@example.com','Township, Lahore','35201-3456789-1','2018-08-01',22000.00,3000.00,0.00,'',NULL,'2025-08-20 06:55:06','2025-08-20 06:55:06'),(4,'T004','Ustad Muhammad Ali','Muhammad Akram','Male','1982-12-05','M.A Arabic, B.Ed','Arabic & Urdu','[\"11\",\"12\"]','[]','0300-4567890','ali@example.com','Faisal Town, Lahore','35201-4567890-1','2016-04-15',20000.00,4000.00,0.00,'',NULL,'2025-08-20 06:55:06','2025-08-20 06:55:06'),(5,'T005','Ustad Muhammad Imran','Muhammad Afzal','Male','1988-07-25','M.Sc Math, B.Ed','Mathematics & Science','[\"14\",\"15\"]','[]','0300-5678901','imran@example.com','Model Town, Lahore','35201-5678901-1','2017-09-01',23000.00,5000.00,0.00,'',NULL,'2025-08-20 06:55:06','2025-08-20 06:55:06'),(6,'T006','Muallimah Ayesha','Abdul Sattar','Female','1990-11-12','Alimah Fazilah, B.Ed','Islamiyat & Urdu','[\"12\",\"18\"]','[]','0300-6789012','ayesha@example.com','Gulshan-e-Ravi, Lahore','35201-6789012-2','2019-03-15',20000.00,3000.00,0.00,'',NULL,'2025-08-20 06:55:06','2025-08-20 06:55:06');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `address` text,
  `photo` longblob,
  `lastLogin` datetime DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'superadmin','superadmin123','Super Admin','superadmin@schoolmadrasa.com','super_admin','active','0300-0000000','Secret Location',NULL,NULL,'2025-08-20 06:55:07','2025-08-20 06:55:07'),(2,'admin','admin123','System Admin','admin@schoolmadrasa.com','admin','active','0300-1234567','Lahore',NULL,NULL,'2025-08-20 06:55:07','2025-08-20 06:55:07'),(3,'teacher1','teacher123','Qari Muhammad Ahmad','ahmad@example.com','teacher','active','0300-1234567','Hafizabad, Lahore',NULL,NULL,'2025-08-20 06:55:07','2025-08-20 06:55:07'),(4,'librarian','librarian123','Muhammad Siddique','siddique@example.com','librarian','active','0300-8901234','Township, Lahore',NULL,NULL,'2025-08-20 06:55:07','2025-08-20 06:55:07'),(5,'accountant','accountant123','Muhammad Arif','arif@example.com','accountant','active','0300-7890123','Faisal Town, Lahore',NULL,NULL,'2025-08-20 06:55:07','2025-08-20 06:55:07');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-20 14:25:01
