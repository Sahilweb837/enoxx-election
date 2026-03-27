-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 07:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `himachal_panchayat_elections`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetCandidatesByLocation` (IN `p_district_id` INT, IN `p_block_id` INT, IN `p_panchayat_id` INT)   BEGIN
    SELECT * FROM vw_candidate_details
    WHERE (p_district_id IS NULL OR district_id = p_district_id)
        AND (p_block_id IS NULL OR block_id = p_block_id)
        AND (p_panchayat_id IS NULL OR panchayat_id = p_panchayat_id)
        AND approval_status = 'approved'
    ORDER BY featured DESC, views DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchCandidates` (IN `p_search_term` VARCHAR(100))   BEGIN
    SELECT * FROM vw_candidate_details
    WHERE MATCH(candidate_name_en, candidate_name_hi, village, bio_en, bio_hi) 
          AGAINST(p_search_term IN NATURAL LANGUAGE MODE)
        AND approval_status = 'approved'
    ORDER BY views DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'UPDATE', 'candidates', 12, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(2, 1, 'UPDATE', 'candidates', 13, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(3, 1, 'UPDATE', 'candidates', 14, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(4, 1, 'UPDATE', 'candidates', 15, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(5, 1, 'UPDATE', 'candidates', 16, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(6, 1, 'UPDATE', 'candidates', 17, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(7, 1, 'UPDATE', 'candidates', 18, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(8, 1, 'UPDATE', 'candidates', 19, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(9, 1, 'UPDATE', 'candidates', 20, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(10, 1, 'UPDATE', 'candidates', 21, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(11, 1, 'UPDATE', 'candidates', 22, '{\"name_en\": \"rest\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"rest\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(12, 1, 'UPDATE', 'candidates', 23, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(13, 1, 'UPDATE', 'candidates', 24, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(14, 1, 'UPDATE', 'candidates', 25, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(15, 1, 'UPDATE', 'candidates', 26, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(16, 1, 'UPDATE', 'candidates', 27, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(17, 1, 'UPDATE', 'candidates', 28, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(18, 1, 'UPDATE', 'candidates', 29, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(19, 1, 'UPDATE', 'candidates', 30, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(20, 1, 'UPDATE', 'candidates', 31, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(21, 1, 'UPDATE', 'candidates', 35, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:16:33'),
(22, 1, 'UPDATE', 'candidates', 1, '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:18:08'),
(23, 1, 'UPDATE', 'candidates', 2, '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:18:08'),
(24, 1, 'UPDATE', 'candidates', 3, '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:18:08');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(8, NULL, 'login', 'Successful login', '::1', '2026-03-27 14:46:56'),
(10, 10, 'login', 'Successful login', '::1', '2026-03-27 15:39:41'),
(11, 10, 'login', 'Successful login', '::1', '2026-03-27 15:43:57'),
(12, 10, 'login', 'Successful login', '::1', '2026-03-27 17:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','editor') DEFAULT 'editor',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `last_login`, `created_at`, `status`) VALUES
(1, 'admin', '$2y$10$oGoZtclCLH.4BqMzI8RUCuxU1/OjKMsR.fCb4xTGC00vEFy7cY5mm', 'admin@enoxxnews.com', 'Administrator', 'super_admin', '2026-03-27 17:26:14', '2026-03-13 05:49:34', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `jila_parishad_id` int(11) NOT NULL,
  `block_name` varchar(100) NOT NULL,
  `block_name_hi` varchar(100) NOT NULL,
  `jila_parishad_pradhan` enum('jila_parishad','pradhan') DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blocks`
--

INSERT INTO `blocks` (`id`, `district_id`, `jila_parishad_id`, `block_name`, `block_name_hi`, `jila_parishad_pradhan`, `slug`, `description`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Dharamshala', 'धर्मशाला', NULL, 'dharamshala', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(2, 1, 1, 'Palampur', 'पालमपुर', NULL, 'palampur', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(3, 1, 1, 'Baijnath', 'बैजनाथ', NULL, 'baijnath', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(4, 1, 1, 'Jaisinghpur', 'जयसिंहपुर', NULL, 'jaisinghpur', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(5, 1, 1, 'Kangra', 'कांगड़ा', NULL, 'kangra', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(6, 1, 1, 'Nurpur', 'नूरपुर', NULL, 'nurpur', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(7, 1, 1, 'Indora', 'इंदौरा', NULL, 'indora', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(8, 1, 1, 'Fatehpur', 'फतेहपुर', NULL, 'fatehpur', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(9, 1, 1, 'Jawali', 'जवाली', NULL, 'jawali', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(10, 1, 1, 'Jaswan', 'जसवां', NULL, 'jaswan', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(11, 1, 1, 'Rakkar', 'रक्कड़', NULL, 'rakkar', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(12, 1, 1, 'Nagrota Bagwan', 'नगरोटा बगवां', NULL, 'nagrota-bagwan', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(13, 1, 1, 'Dehra Gopipur', 'देहरा गोपीपुर', NULL, 'dehra-gopipur', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(14, 1, 1, 'Shahpur', 'शाहपुर', NULL, 'shahpur', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(15, 1, 1, 'Khundian', 'खुंडियां', NULL, 'khundian', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 11:54:43'),
(16, 6, 6, 'test', 'परीक्षा', NULL, 'test', NULL, NULL, NULL, '2026-03-13 07:33:54', '2026-03-13 11:54:43'),
(17, 14, 14, 'dhamrshala', 'धर्मशाला', NULL, 'dhamrshala', NULL, NULL, NULL, '2026-03-13 07:38:08', '2026-03-13 11:54:43'),
(18, 15, 15, 'pathankot', 'पठानकोट', NULL, 'pathankot', NULL, NULL, NULL, '2026-03-13 07:42:54', '2026-03-13 11:54:43'),
(19, 16, 16, 'test', 'परीक्षा', NULL, 'test-1', NULL, NULL, NULL, '2026-03-13 07:51:34', '2026-03-13 11:54:43'),
(20, 7, 7, 'test', 'परीक्षा', NULL, 'test-2', NULL, NULL, NULL, '2026-03-13 08:20:27', '2026-03-13 11:54:43'),
(21, 8, 8, 'test', 'परीक्षा', NULL, 'test-3', NULL, NULL, NULL, '2026-03-13 09:22:45', '2026-03-13 11:54:43'),
(22, 15, 15, 'test', 'परीक्षा', NULL, 'test-4', NULL, NULL, NULL, '2026-03-13 11:13:57', '2026-03-13 11:54:43'),
(23, 3, 32, 'dsdsd', 'डी.एस.डी.एस.डी', NULL, 'dsdsd', NULL, NULL, NULL, '2026-03-13 11:55:13', '2026-03-13 11:55:13'),
(25, 12, 33, 'test', 'परीक्षा', NULL, 'test-5', NULL, NULL, NULL, '2026-03-14 05:56:12', '2026-03-14 05:56:12');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `candidate_id` varchar(50) NOT NULL,
  `district_id` int(11) NOT NULL,
  `jila_parishad_pradhan` enum('jila_parishad','pradhan') DEFAULT NULL,
  `jila_parishad_id` int(11) DEFAULT NULL,
  `block_id` int(11) NOT NULL,
  `panchayat_id` int(11) NOT NULL,
  `village` varchar(100) NOT NULL,
  `candidate_name_hi` varchar(100) NOT NULL,
  `candidate_name_en` varchar(100) NOT NULL,
  `relation_type` enum('father','husband') NOT NULL,
  `relation_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `age` int(11) NOT NULL,
  `education` varchar(200) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `short_notes_hi` text DEFAULT NULL,
  `bio_hi` text DEFAULT NULL,
  `bio_en` text DEFAULT NULL,
  `slug` varchar(500) NOT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `video_message_url` varchar(500) DEFAULT NULL,
  `interview_video_url` varchar(500) DEFAULT NULL,
  `mobile_number` varchar(15) DEFAULT NULL,
  `whatsapp_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `facebook_url` varchar(500) DEFAULT NULL,
  `twitter_url` varchar(500) DEFAULT NULL,
  `instagram_url` varchar(500) DEFAULT NULL,
  `status` enum('contesting','leading','winner','runner_up','withdrawn') DEFAULT 'contesting',
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `views` int(11) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `whatsapp_verified` tinyint(1) DEFAULT 0,
  `photo_hidden` tinyint(1) DEFAULT 1,
  `verification_code` varchar(6) DEFAULT NULL,
  `verification_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `candidate_id`, `district_id`, `jila_parishad_pradhan`, `jila_parishad_id`, `block_id`, `panchayat_id`, `village`, `candidate_name_hi`, `candidate_name_en`, `relation_type`, `relation_name`, `gender`, `age`, `education`, `profession`, `short_notes_hi`, `bio_hi`, `bio_en`, `slug`, `photo_url`, `video_message_url`, `interview_video_url`, `mobile_number`, `whatsapp_number`, `email`, `facebook_url`, `twitter_url`, `instagram_url`, `status`, `approval_status`, `views`, `featured`, `created_at`, `updated_at`, `created_by`, `whatsapp_verified`, `photo_hidden`, `verification_code`, `verification_expiry`) VALUES
(1, 'HPEL20260001', 1, NULL, NULL, 1, 1, 'Rakkar', 'रमेश कुमार', 'Ramesh Kumar', 'father', 'Mohan Lal', 'Male', 42, 'Graduate', 'Farmer', 'स्थानीय किसान, 10 वर्षों से सामाजिक कार्य में सक्रिय', 'रमेश कुमार रक्कड़ पंचायत के स्थानीय किसान हैं और पिछले 10 वर्षों से सामाजिक कार्यों में सक्रिय रूप से भाग ले रहे हैं। उन्होंने ग्रामीण विकास और किसानों के कल्याण के लिए कई परियोजनाओं का नेतृत्व किया है।', 'Ramesh Kumar is a local farmer from Rakkar Panchayat and has been actively participating in social work for the past 10 years. He has led several projects for rural development and farmer welfare.', 'kangra/dharamshala/rakkar/ramesh-kumar', '300', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 05:49:34', '2026-03-14 08:18:08', NULL, 0, 1, NULL, NULL),
(2, 'HPEL20260002', 1, NULL, NULL, 1, 2, 'Khaniyara', 'सीमा देवी', 'Seema Devi', 'husband', 'Rajesh Kumar', 'Female', 38, 'Post Graduate', 'Teacher', 'महिला सशक्तिकरण, शिक्षा के क्षेत्र में कार्यरत', 'सीमा देवी खनियारा पंचायत में सरकारी स्कूल की शिक्षिका हैं। उन्होंने महिला सशक्तिकरण और बालिका शिक्षा के लिए कई अभियान चलाए हैं। वह पिछले 5 वर्षों से महिला मंडल की अध्यक्ष हैं।', 'Seema Devi is a government school teacher in Khaniyara Panchayat. She has led several campaigns for women empowerment and girl child education. She has been president of Mahila Mandal for the past 5 years.', 'kangra/dharamshala/khaniyara/seema-devi', '300', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 05:49:34', '2026-03-14 08:18:08', NULL, 0, 1, NULL, NULL),
(3, 'HPEL20260003', 1, NULL, NULL, 2, 3, 'Sidhpur', 'अजय सिंह', 'Ajay Singh', 'father', 'Suresh Singh', 'Male', 55, '10th', 'Business', 'व्यवसायी, युवाओं के लिए रोजगार के अवसर', 'अजय सिंह सिद्धपुर के एक प्रतिष्ठित व्यवसायी हैं। उन्होंने क्षेत्र में कई छोटे उद्योग स्थापित किए हैं और युवाओं को रोजगार के अवसर प्रदान किए हैं। वह पिछले 15 वर्षों से व्यापार मंडल के अध्यक्ष हैं।', 'Ajay Singh is a reputed businessman from Sidhpur. He has established several small industries in the region and provided employment opportunities to youth. He has been president of the Trade Council for the past 15 years.', 'kangra/palampur/sidhpur/ajay-singh', '300', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 05:49:34', '2026-03-14 08:18:08', NULL, 0, 1, NULL, NULL),
(4, 'HPEL20260004', 1, NULL, NULL, 1, 4, 'Gharoh', 'उम्मीदवार घरोह', 'Candidate Gharoh', 'husband', 'Parent Gharoh', 'Male', 29, 'Post Graduate', 'Teacher', 'स्थानीय विकास के लिए कार्यरत', 'स्थानीय विकास और सामाजिक कार्यों में सक्रिय', 'Active in local development and social work', 'kangra/dharamshala/gharoh/candidate-gharoh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 05:49:36', '2026-03-13 05:49:36', NULL, 0, 1, NULL, NULL),
(5, 'HPEL20260005', 1, NULL, NULL, 1, 6, 'Kand', 'उम्मीदवार कंड', 'Candidate Kand', 'father', 'Parent Kand', 'Male', 50, '10th', 'Farmer', 'स्थानीय विकास के लिए कार्यरत', 'स्थानीय विकास और सामाजिक कार्यों में सक्रिय', 'Active in local development and social work', 'kangra/dharamshala/kand/candidate-kand', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 05:49:36', '2026-03-13 05:49:36', NULL, 0, 1, NULL, NULL),
(6, 'HPEL20260006', 1, NULL, NULL, 1, 11, 'Bhagsu', 'उम्मीदवार भागसू', 'Candidate Bhagsu', 'father', 'Parent Bhagsu', 'Female', 52, 'Post Graduate', 'Farmer', 'स्थानीय विकास के लिए कार्यरत', 'स्थानीय विकास और सामाजिक कार्यों में सक्रिय', 'Active in local development and social work', 'kangra/dharamshala/bhagsu/candidate-bhagsu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 05:49:36', '2026-03-13 05:49:36', NULL, 0, 1, NULL, NULL),
(7, 'HPEL20260007', 1, NULL, NULL, 1, 15, 'Rajpur', 'उम्मीदवार राजपुर', 'Candidate Rajpur', 'husband', 'Parent Rajpur', 'Male', 47, '12th', 'Teacher', 'स्थानीय विकास के लिए कार्यरत', 'स्थानीय विकास और सामाजिक कार्यों में सक्रिय', 'Active in local development and social work', 'kangra/dharamshala/rajpur/candidate-rajpur', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 05:49:36', '2026-03-13 05:49:36', NULL, 0, 1, NULL, NULL),
(11, 'HPEL20261872', 1, NULL, NULL, 1, 16, 'kangra', 'sahil sandhu', 'sahil sandhu', 'husband', 'test', 'Male', 22, '', 'test', 'terter', '', '', 'kangra/dharamshala/re/sahil-sandhu', '', NULL, NULL, '0780769737', NULL, NULL, NULL, NULL, NULL, 'winner', 'pending', 0, 0, '2026-03-13 05:49:39', '2026-03-13 05:49:39', NULL, 0, 1, NULL, NULL),
(12, 'HPEL20260521', 6, NULL, NULL, 16, 26, 'kangra', 'test', 'sahil sandhu', 'husband', 'tet', 'Male', 100, 'test', 'fdrf', 'test', 'test ग्राम पंचायत kangra के निवासी हैं। वह एक fdrf हैं। test उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of kangra Panchayat. He/She is a fdrf. test Their main objective is rural development and overall progress of the panchayat.', 'hamirpur-test-test-sahil-sandhu', '69b3be99e41a1_20260313.png', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 07:36:57', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(13, 'HPEL20264446', 14, NULL, NULL, 17, 27, 'test', 'test', 'sahil sandhu', 'husband', 'test', 'Male', 100, 'test', 'test', 'rererer', 'test ग्राम पंचायत test के निवासी हैं। वह एक test हैं। rererer उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a test. rererer Their main objective is rural development and overall progress of the panchayat.', 'palampur-dhamrshala-rakkar-1-sahil-sandhu', '69b3bf05d33b4_20260313.jfif', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 07:38:45', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(14, 'HPEL20269080', 7, NULL, NULL, 20, 29, 'kangra', 'sahil sandhu', 'sahil sandhu', 'husband', 'rere', 'Male', 21, 'test', 'test', 'trt', 'sahil sandhu ग्राम पंचायत kangra के निवासी हैं। वह एक test हैं। trt उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of kangra Panchayat. He/She is a test. trt Their main objective is rural development and overall progress of the panchayat.', 'bilaspur-test-2-test-1-sahil-sandhu', '69b3c8f917a34_20260313.png', NULL, NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 08:21:13', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(15, 'HPEL20260465', 7, NULL, NULL, 20, 29, 'kangra', 'sahil sandhu', 'sahil sandhu', 'husband', 'test', 'Male', 21, 'test', 'test', 'sdsdsd', 'sahil sandhu ग्राम पंचायत kangra के निवासी हैं। वह एक test हैं। sdsdsd उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of kangra Panchayat. He/She is a test. sdsdsd Their main objective is rural development and overall progress of the panchayat.', 'bilaspur-test-2-test-1-sahil-sandhu-1', '69b3cb9a26b91_20260313.jpg', NULL, NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 08:32:26', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(16, 'HPEL20260162', 1, NULL, NULL, 1, 10, 'test', 'test', 'sahil sandhu', 'husband', 'rere', 'Male', 100, 'test', 'fdrf', 'rere', 'test ग्राम पंचायत test के निवासी हैं। वह एक fdrf हैं। rere उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a fdrf. rere Their main objective is rural development and overall progress of the panchayat.', 'kangra-dharamshala-dharamkot-sahil-sandhu', '69b3d637bc3fd_20260313.png', 'https://www.youtube.com/watch?v=NmHUtF3rceo', NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 09:17:43', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(17, 'HPEL20269042', 8, NULL, NULL, 21, 30, 'test', 'test', 'sahil sandhu', 'husband', 'test', 'Male', 21, 'resr', 'resr', 'test', 'test ग्राम पंचायत test के निवासी हैं। वह एक resr हैं। test उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a resr. test Their main objective is rural development and overall progress of the panchayat.', 'chamba-test-3-apni-sahil-sandhu', '69b3d86ceffa0_20260313.png', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 09:27:08', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(18, 'HPEL20261456', 8, NULL, NULL, 21, 30, 'test', 'sahil sandhu', 'sahil sandhu', 'husband', 'rere', 'Female', 21, 'resr', 'test', 'rererer', 'sahil sandhu ग्राम पंचायत test के निवासी हैं। वह एक test हैं। rererer उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a test. rererer Their main objective is rural development and overall progress of the panchayat.', 'chamba-test-3-apni-sahil-sandhu-1', '69b3d88f2a846_20260313.png', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 09:27:43', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(19, 'HPEL20262397', 8, NULL, NULL, 21, 30, 'test', 'test', 'sahil sandhu', 'husband', 'test', 'Male', 21, '343343', NULL, 'rererer', 'test ग्राम पंचायत test के निवासी हैं। वह एक  हैं। rererer उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a . rererer Their main objective is rural development and overall progress of the panchayat.', 'chamba-test-3-apni-sahil-sandhu-2', '69b3d8af34054_20260313.png', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 09:28:15', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(20, 'HPEL20260239', 8, NULL, NULL, 21, 30, 'test', 'test', 'sahil sandhu', 'father', 'test', 'Male', 21, 'resr', 'test', 'ewewewewe', 'test ग्राम पंचायत test के निवासी हैं। वह एक test हैं। ewewewewe उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a test. ewewewewe Their main objective is rural development and overall progress of the panchayat.', 'chamba-test-3-apni-sahil-sandhu-3', '69b3d8e0a3635_20260313.png', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 09:29:04', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(21, 'HPEL20269408', 8, NULL, NULL, 21, 30, 'test', 'test', 'sahil sandhu', 'husband', 'test', 'Male', 21, 'resr', 'fdrf', 'ewwewew', 'test ग्राम पंचायत test के निवासी हैं। वह एक fdrf हैं। ewwewew उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a fdrf. ewwewew Their main objective is rural development and overall progress of the panchayat.', 'chamba-test-3-apni-sahil-sandhu-4', '69b3d90ae5f49_20260313.png', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 09:29:46', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(22, 'HPEL20266661', 6, NULL, NULL, 16, 26, 'test', 'test', 'rest', 'husband', 'ewe', 'Female', 21, 'resr', 'test', 'wewewe', 'test, test गांव के निवासी, ewe के पत्नी हैं। पेशे से test हैं और resr शिक्षित हैं। पंचायत चुनाव 2026 में भाग ले रहे हैं और क्षेत्र के विकास के लिए प्रतिबद्ध हैं।', 'rest, resident of test village, is the wife of ewe. By profession, test and educated up to resr. Contesting in Panchayat Election 2026 and committed to the development of the area.', 'hamirpur-test-test-rest', '69b3e0f33c8f9_20260313.jfif', 'https://www.youtube.com/watch?v=NmHUtF3rceo', 'https://www.youtube.com/watch?v=NmHUtF3rceo', '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 10:03:31', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(23, 'HPEL20269261', 15, NULL, NULL, 22, 31, 'test', ' sukhu sukhu', '  sukhu sukhu', 'father', 'rere', 'Male', 21, '  sukhu sukhua', '  sukhu sukhua', ' sukhu sukhua', ' sukhu sukhu ग्राम पंचायत test के निवासी हैं। वह एक   sukhu sukhua हैं।  sukhu sukhua उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', '  sukhu sukhu is a resident of test Panchayat. He/She is a   sukhu sukhua.  sukhu sukhua Their main objective is rural development and overall progress of the panchayat.', 'delhi-test-4-rerere-sukhu-sukhu', '69b3f1e769145_20260313.jpg', NULL, NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 11:15:51', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(24, 'HPEL20268930', 6, NULL, NULL, 16, 26, 'weewe', ' sukhu sukhu', '  sukhu sukhu', 'husband', 'test', 'Female', 21, '343343', 'test', 'sdsdsds', ' sukhu sukhu ग्राम पंचायत weewe के निवासी हैं। वह एक test हैं। sdsdsds उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', '  sukhu sukhu is a resident of weewe Panchayat. He/She is a test. sdsdsds Their main objective is rural development and overall progress of the panchayat.', 'hamirpur-test-test-sukhu-sukhu', '69b3f22eca05c_20260313.jpg', NULL, NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 11:17:02', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(25, 'HPEL20265419', 6, NULL, NULL, 16, 26, 'dsdsdd', ' sukhu sukhu', '  sukhu sukhu', 'husband', 'dsdsd', 'Female', 21, '343343', 'ddsd', 'dsdsdsdddddddddddddd', ' sukhu sukhu ग्राम पंचायत dsdsdd के निवासी हैं। वह एक ddsd हैं। dsdsdsdddddddddddddd उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', '  sukhu sukhu is a resident of dsdsdd Panchayat. He/She is a ddsd. dsdsdsdddddddddddddd Their main objective is rural development and overall progress of the panchayat.', 'hamirpur-test-test-sukhu-sukhu-1', '69b3f25561500_20260313.jpg', NULL, NULL, '3434343434', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 11:17:41', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(26, 'HPEL20260291', 6, NULL, NULL, 16, 26, '4343', ' sukhu sukhu', '  sukhu sukhu', 'husband', 'test', 'Male', 100, 'resr', 'test', 'sdsdsds', ' sukhu sukhu ग्राम पंचायत 4343 के निवासी हैं। वह एक test हैं। sdsdsds उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', '  sukhu sukhu is a resident of 4343 Panchayat. He/She is a test. sdsdsds Their main objective is rural development and overall progress of the panchayat.', 'hamirpur-test-test-sukhu-sukhu-2', '69b3f279f154c_20260313.jpg', NULL, NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 11:18:17', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(27, 'HPEL20262449', 6, NULL, NULL, 16, 26, 'kangra', ' sukhu sukhu', '  sukhu sukhu', 'husband', 'tet', 'Male', 21, '343343', 'fdrf', 'sdsdsdsd', ' sukhu sukhu ग्राम पंचायत kangra के निवासी हैं। वह एक fdrf हैं। sdsdsdsd उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', '  sukhu sukhu is a resident of kangra Panchayat. He/She is a fdrf. sdsdsdsd Their main objective is rural development and overall progress of the panchayat.', 'hamirpur-test-test-sukhu-sukhu-3', '69b3f298a1549_20260313.jpg', NULL, NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 11:18:48', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(28, 'HPEL20262145', 6, NULL, NULL, 16, 26, 'kangra', ' sukhu sukhu', '  sukhu sukhu', 'husband', 'tet', 'Male', 100, '343343', 'fdrf', '3434343', ' sukhu sukhu ग्राम पंचायत kangra के निवासी हैं। वह एक fdrf हैं। 3434343 उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', '  sukhu sukhu is a resident of kangra Panchayat. He/She is a fdrf. 3434343 Their main objective is rural development and overall progress of the panchayat.', 'hamirpur-test-test-sukhu-sukhu-4', '69b3f2bab18ae_20260313.jpg', NULL, NULL, '0780769737', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 11:19:22', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(29, 'HPEL20261975', 14, NULL, NULL, 17, 27, '434343', 'साहिल संधू', 'sahil sandhu', 'husband', 'rere', 'Male', 21, '343343', 'test', ' साहिल संधू ग्राम पंचायत 434343 के निवासी हैं। वह एक test हैं और 343343 शिक्षित हैं। wewe उनका मुख्य उद्देश्य ग्रामीण विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। वह पंचायत के समग्र विकास के लिए प्रतिबद्ध हैं।\r\n', 'साहिल संधू ग्राम पंचायत 434343 के निवासी हैं। वह एक test हैं और 343343 शिक्षित हैं।  साहिल संधू ग्राम पंचायत 434343 के निवासी हैं। वह एक test हैं और 343343 शिक्षित हैं। wewe उनका मुख्य उद्देश्य ग्रामीण विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। वह पंचायत के समग्र विकास के लिए प्रतिबद्ध हैं।\r\n उनका मुख्य उद्देश्य ग्रामीण विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। वह पंचायत के समग्र विकास के लिए प्रतिबद्ध हैं।', 'Translation temporarily unavailable. Please check back later.', 'palampur-dhamrshala-rakkar-1-sahil-sandhu-1', '69b3f4bd919b3_20260313.jpg', NULL, NULL, '7845126930', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-13 11:27:58', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(30, 'HPEL20264394', 12, NULL, 33, 25, 33, 'kangra', 'सुक्खु सुक्खु', '  sukhu sukhu', 'husband', 'tet', 'Male', 21, 'resr', 'test', ' सुक्खु सुक्खु ग्राम पंचायत kangra के निवासी हैं। वह एक test हैं और resr शिक्षित हैं। test उनका मुख्य उद्देश्य ग्रामीण विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। वह पंचायत के समग्र विकास के लिए प्रतिबद्ध हैं।\r\n\r\n', 'सुक्खु सुक्खु ग्राम पंचायत kangra के निवासी हैं। वह एक test हैं और resr शिक्षित हैं।  सुक्खु सुक्खु ग्राम पंचायत kangra के निवासी हैं। वह एक test हैं और resr शिक्षित हैं। test उनका मुख्य उद्देश्य ग्रामीण विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। वह पंचायत के समग्र विकास के लिए प्रतिबद्ध हैं।\r\n\r\n उनका मुख्य उद्देश्य ग्रामीण विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। वह पंचायत के समग्र विकास के लिए प्रतिबद्ध हैं।', 'Translation temporarily unavailable. Please check back later.', 'sirmaur-res-test-test-5-test-2-sukhu-sukhu', '69b4f8aa276ec_20260314.jpg', NULL, NULL, '3434343434', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-14 05:57:00', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(31, 'HPEL20267653', 3, 'jila_parishad', NULL, 23, 34, 'test', 'साहिल संधू', 'sahil sandhu', 'father', 'test', 'Male', 21, 'resr', 'indiividual', ' साहिल संधू, test गांव के निवासी, test के पुत्र हैं। पेशे से indiividual हैं और resr शिक्षित हैं। पंचायत चुनाव 2026 में भाग ले रहे हैं और क्षेत्र के विकास के लिए प्रतिबद्ध हैं।\r\n\r\n', 'साहिल संधू ग्राम पंचायत test के निवासी हैं। वह एक indiividual हैं।  साहिल संधू, test गांव के निवासी, test के पुत्र हैं। पेशे से indiividual हैं और resr शिक्षित हैं। पंचायत चुनाव 2026 में भाग ले रहे हैं और क्षेत्र के विकास के लिए प्रतिबद्ध हैं।\r\n\r\n उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।', 'sahil sandhu is a resident of test Panchayat. He/She is a indiividual.  साहिल संधू, test गांव के निवासी, test के पुत्र हैं। पेशे से indiividual हैं और resr शिक्षित हैं। पंचायत चुनाव 2026 में भाग ले रहे हैं और क्षेत्र के विकास के लिए प्रतिबद्ध हैं।\r\n\r\n Their main objective is rural development and overall progress of the panchayat.', 'shimla-dsdsd-test-3-sahil-sandhu', '69b501e7bcabe_20260314.jpg', NULL, NULL, '7807697370', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-14 06:36:23', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL),
(32, 'HPEL20261160', 6, 'pradhan', NULL, 16, 26, 'test', 'साहिल संधू', 'sahil sandhu', 'husband', 'dsdsd', 'Male', 21, 'resr', 'indiividual', 'HY MY NAME I SAHIL SANDHU', 'साहिल संधू, test गांव के निवासी, dsdsd के पत्नी हैं। पेशे से indiividual हैं और resr शिक्षित हैं। HY MY NAME I SAHIL SANDHU ग्राम पंचायत चुनाव 2026 में भाग ले रहे हैं। इनका मुख्य उद्देश्य गांव का विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। युवाओं के लिए रोजगार के अवसर पैदा करना और महिलाओं को सशक्त बनाना इनके प्रमुख एजेंडे में शामिल हैं।', '', 'hamirpur-test-test-sahil-sandhu-1', NULL, NULL, NULL, '7807697370', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-14 06:57:56', '2026-03-14 06:57:56', NULL, 0, 1, NULL, NULL),
(33, 'HPEL20260424', 14, 'pradhan', NULL, 17, 27, 'kangra', 'साहिल संधू', 'sahil sandhu', 'husband', 'test', 'Male', 21, 'resr', 'indiividual', 'TEST', 'साहिल संधू, kangra गांव के निवासी, test के पत्नी हैं। पेशे से indiividual हैं और resr शिक्षित हैं। TEST ग्राम पंचायत चुनाव 2026 में भाग ले रहे हैं। इनका मुख्य उद्देश्य गांव का विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। युवाओं के लिए रोजगार के अवसर पैदा करना और महिलाओं को सशक्त बनाना इनके प्रमुख एजेंडे में शामिल हैं।', '', 'palampur-dhamrshala-rakkar-1-sahil-sandhu-2', NULL, NULL, NULL, '7807697370', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-14 06:58:55', '2026-03-14 06:58:55', NULL, 0, 1, NULL, NULL),
(34, 'HPEL20263194', 15, 'pradhan', NULL, 18, 28, 'RESR', 'साहिल संधू', 'sahil sandhu', 'father', 'RESR', 'Male', 21, '343343', 'indiividual', 'RESR', ' साहिल संधू, RESR गांव के निवासी, RESR के पुत्र हैं। पेशे से indiividual हैं और 343343 शिक्षित हैं। RESR ग्राम पंचायत चुनाव 2026 में भाग ले रहे हैं। इनका मुख्य उद्देश्य गांव का विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। युवाओं के लिए रोजगार के अवसर पैदा करना और महिलाओं को सशक्त बनाना इनके प्रमुख एजेंडे में शामिल हैं।\r\n\r\n', '', 'delhi-pathankot-adhwani-sahil-sandhu', NULL, NULL, NULL, '7807697370', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-14 07:00:28', '2026-03-14 07:00:28', NULL, 0, 1, NULL, NULL),
(35, 'HPEL20265548', 8, 'jila_parishad', NULL, 21, 30, 'kangra', 'साहिल संधू', 'sahil sandhu', 'husband', 'rere', 'Male', 21, '  sukhu sukhua', 'indiividual', 'my name is sahil sandhu', 'साहिल संधू, kangra गांव के निवासी, rere के पत्नी हैं। पेशे से indiividual हैं और   sukhu sukhua शिक्षित हैं। my name is sahil sandhu ग्राम पंचायत चुनाव 2026 में भाग ले रहे हैं। इनका मुख्य उद्देश्य गांव का विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है। युवाओं के लिए रोजगार के अवसर पैदा करना और महिलाओं को सशक्त बनाना इनके प्रमुख एजेंडे में शामिल हैं।', 'sahil sandhu, resident of kangra village, is the wife of rere. By profession, a indiividual and is   sukhu sukhua educated. my name is sahil sandhu Contesting in the Gram Panchayat Election 2026. Their main objectives include village development, expansion of education and health facilities, creating employment opportunities for youth, and empowering women.', 'chamba-test-3-apni-sahil-sandhu-5', '69b5126e6645f_20260314.jpg', NULL, NULL, '7807697370', NULL, NULL, NULL, NULL, NULL, 'contesting', 'approved', 0, 0, '2026-03-14 07:46:54', '2026-03-14 08:16:33', NULL, 0, 1, NULL, NULL);

--
-- Triggers `candidates`
--
DELIMITER $$
CREATE TRIGGER `log_candidate_changes` AFTER UPDATE ON `candidates` FOR EACH ROW BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, old_data, new_data)
    VALUES (1, 'UPDATE', 'candidates', NEW.id, 
        JSON_OBJECT(
            'name_en', OLD.candidate_name_en,
            'status', OLD.status,
            'approval_status', OLD.approval_status
        ),
        JSON_OBJECT(
            'name_en', NEW.candidate_name_en,
            'status', NEW.status,
            'approval_status', NEW.approval_status
        )
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_candidate_slug` BEFORE UPDATE ON `candidates` FOR EACH ROW BEGIN
    IF NEW.candidate_name_en != OLD.candidate_name_en THEN
        SET NEW.slug = CONCAT(
            (SELECT slug FROM districts WHERE id = NEW.district_id), '/',
            (SELECT slug FROM blocks WHERE id = NEW.block_id), '/',
            (SELECT slug FROM panchayats WHERE id = NEW.panchayat_id), '/',
            LOWER(REPLACE(REPLACE(REPLACE(NEW.candidate_name_en, ' ', '-'), '.', ''), ',', ''))
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_news`
--

CREATE TABLE `candidate_news` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `title_hi` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `content_hi` text DEFAULT NULL,
  `news_url` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `news_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `district_name` varchar(100) NOT NULL,
  `district_name_hi` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `district_name`, `district_name_hi`, `slug`, `description`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'Kangra', 'कांगड़ा', 'kangra', 'Kangra district is the most populous district of Himachal Pradesh', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(2, 'Mandi', 'मंडी', 'mandi', 'Mandi district is known for its ancient temples and scenic beauty', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(3, 'Shimla', 'शिमला', 'shimla', 'Shimla is the capital district of Himachal Pradesh', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(4, 'Solan', 'सोलन', 'solan', 'Solan district is known as the \"Mushroom City of India\"', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(5, 'Una', 'ऊना', 'una', 'Una district is known for its historical and religious significance', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(6, 'Hamirpur', 'हमीरपुर', 'hamirpur', 'Hamirpur is one of the smallest districts of Himachal Pradesh', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(7, 'Bilaspur', 'बिलासपुर', 'bilaspur', 'Bilaspur district is known for the Gobind Sagar Lake', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(8, 'Chamba', 'चंबा', 'chamba', 'Chamba is known for its rich cultural heritage and handicrafts', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(9, 'Kullu', 'कुल्लू', 'kullu', 'Kullu is famous for its beautiful valleys and Dussehra celebration', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(10, 'Lahaul and Spiti', 'लाहौल और स्पीति', 'lahaul-spiti', 'Lahaul and Spiti is known for its high altitude desert landscape', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(11, 'Kinnaur', 'किन्नौर', 'kinnaur', 'Kinnaur is famous for apple orchards and Buddhist monasteries', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(12, 'Sirmaur', 'सिरमौर', 'sirmaur', 'Sirmaur district is known for its wildlife sanctuary and temples', NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(13, 'rest', 'आराम', 'rest', NULL, NULL, NULL, '2026-03-13 07:37:22', '2026-03-13 07:37:22'),
(14, 'palampur', 'पालमपुर', 'palampur', NULL, NULL, NULL, '2026-03-13 07:37:52', '2026-03-13 07:37:52'),
(15, 'delhi', 'दिल्ली', 'delhi', NULL, NULL, NULL, '2026-03-13 07:42:39', '2026-03-13 07:42:39'),
(16, 'vivek', 'विवेक', 'vivek', NULL, NULL, NULL, '2026-03-13 07:51:29', '2026-03-13 07:51:29'),
(17, 'sahil', 'साहिल', 'sahil', NULL, NULL, NULL, '2026-03-13 08:05:04', '2026-03-13 08:05:04');

-- --------------------------------------------------------

--
-- Table structure for table `election_results`
--

CREATE TABLE `election_results` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `panchayat_id` int(11) NOT NULL,
  `total_votes` int(11) DEFAULT 0,
  `votes_received` int(11) DEFAULT 0,
  `vote_percentage` decimal(5,2) DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `result_status` enum('pending','declared','contested') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','blocked','inactive') DEFAULT 'active',
  `total_entries` int(11) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `username`, `password`, `full_name`, `email`, `phone`, `status`, `total_entries`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'EMP001', 'employee1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john@example.com', NULL, 'active', 0, NULL, '2026-03-27 17:35:36', '2026-03-27 17:35:36'),
(2, 'EMP002', 'employee2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane@example.com', NULL, 'active', 0, NULL, '2026-03-27 17:35:36', '2026-03-27 17:35:36'),
(3, 'EMP8186', 'admin', '$2y$10$TyjgbNT/V86MLWGuXNsyg.4JZI7LOf06jIYULWV/HcySou/OWx0Oa', 'sahil sandhu', 'sahilsandhu39234@gmail.com', '7807697370', 'active', 0, NULL, '2026-03-27 17:47:15', '2026-03-27 17:47:15');

-- --------------------------------------------------------

--
-- Table structure for table `jila_parishad`
--

CREATE TABLE `jila_parishad` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_hi` varchar(100) DEFAULT NULL,
  `constituency` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jila_parishad`
--

INSERT INTO `jila_parishad` (`id`, `district_id`, `name`, `name_hi`, `constituency`, `slug`, `created_at`) VALUES
(1, 1, 'Kangra Main', 'कांगड़ा मुख्य', 'Main Constituency', 'kangra-main', '2026-03-13 11:54:42'),
(2, 2, 'Mandi Main', 'मंडी मुख्य', 'Main Constituency', 'mandi-main', '2026-03-13 11:54:42'),
(3, 3, 'Shimla Main', 'शिमला मुख्य', 'Main Constituency', 'shimla-main', '2026-03-13 11:54:42'),
(4, 4, 'Solan Main', 'सोलन मुख्य', 'Main Constituency', 'solan-main', '2026-03-13 11:54:42'),
(5, 5, 'Una Main', 'ऊना मुख्य', 'Main Constituency', 'una-main', '2026-03-13 11:54:42'),
(6, 6, 'Hamirpur Main', 'हमीरपुर मुख्य', 'Main Constituency', 'hamirpur-main', '2026-03-13 11:54:42'),
(7, 7, 'Bilaspur Main', 'बिलासपुर मुख्य', 'Main Constituency', 'bilaspur-main', '2026-03-13 11:54:42'),
(8, 8, 'Chamba Main', 'चंबा मुख्य', 'Main Constituency', 'chamba-main', '2026-03-13 11:54:42'),
(9, 9, 'Kullu Main', 'कुल्लू मुख्य', 'Main Constituency', 'kullu-main', '2026-03-13 11:54:42'),
(10, 10, 'Lahaul and Spiti Main', 'लाहौल और स्पीति मुख्य', 'Main Constituency', 'lahaul-spiti-main', '2026-03-13 11:54:42'),
(11, 11, 'Kinnaur Main', 'किन्नौर मुख्य', 'Main Constituency', 'kinnaur-main', '2026-03-13 11:54:42'),
(12, 12, 'Sirmaur Main', 'सिरमौर मुख्य', 'Main Constituency', 'sirmaur-main', '2026-03-13 11:54:42'),
(13, 13, 'rest Main', 'आराम मुख्य', 'Main Constituency', 'rest-main', '2026-03-13 11:54:42'),
(14, 14, 'palampur Main', 'पालमपुर मुख्य', 'Main Constituency', 'palampur-main', '2026-03-13 11:54:42'),
(15, 15, 'delhi Main', 'दिल्ली मुख्य', 'Main Constituency', 'delhi-main', '2026-03-13 11:54:42'),
(16, 16, 'vivek Main', 'विवेक मुख्य', 'Main Constituency', 'vivek-main', '2026-03-13 11:54:42'),
(17, 17, 'sahil Main', 'साहिल मुख्य', 'Main Constituency', 'sahil-main', '2026-03-13 11:54:42'),
(32, 3, 'dshul', 'dshul', 'dsdsd', 'dshul-dsdsd', '2026-03-13 11:55:06'),
(33, 12, 'res', 'आर ई', 'test', 'res-test', '2026-03-14 05:56:06'),
(34, 8, 'resr', 'रेसर', 'resr', 'resr-resr', '2026-03-14 06:06:38');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panchayats`
--

CREATE TABLE `panchayats` (
  `id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL,
  `panchayat_name` varchar(100) NOT NULL,
  `panchayat_name_hi` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `status` enum('existing','proposed') DEFAULT 'existing',
  `description` text DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panchayats`
--

INSERT INTO `panchayats` (`id`, `block_id`, `panchayat_name`, `panchayat_name_hi`, `slug`, `status`, `description`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rakkar', 'रक्कड़', 'rakkar', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(2, 1, 'Khaniyara', 'खनियारा', 'khaniyara', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(3, 1, 'Sidhpur', 'सिद्धपुर', 'sidhpur', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(4, 1, 'Gharoh', 'घरोह', 'gharoh', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(5, 1, 'Yol', 'योल', 'yol', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(6, 1, 'Kand', 'कंड', 'kand', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(7, 1, 'Naddi', 'नाड्डी', 'naddi', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(8, 1, 'McLeod Ganj', 'मैक्लोडगंज', 'mcleod-ganj', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(9, 1, 'Forsyth Ganj', 'फोरसाइथगंज', 'forsyth-ganj', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(10, 1, 'Dharamkot', 'धर्मकोट', 'dharamkot', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(11, 1, 'Bhagsu', 'भागसू', 'bhagsu', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(12, 1, 'Dari', 'दारी', 'dari', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(13, 1, 'Cheelgari', 'चीलगाड़ी', 'cheelgari', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(14, 1, 'Kareri', 'करेड़ी', 'kareri', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(15, 1, 'Rajpur', 'राजपुर', 'rajpur', 'existing', NULL, NULL, NULL, '2026-03-13 05:49:34', '2026-03-13 05:49:34'),
(16, 1, 're', 'res', 're', 'proposed', NULL, NULL, NULL, '2026-03-13 05:49:37', '2026-03-13 05:49:37'),
(26, 16, 'test', 'परीक्षा', 'test', 'existing', NULL, NULL, NULL, '2026-03-13 07:34:04', '2026-03-13 07:34:04'),
(27, 17, 'rakkar', 'रक्कड़', 'rakkar-1', 'existing', NULL, NULL, NULL, '2026-03-13 07:38:15', '2026-03-13 07:38:15'),
(28, 18, 'adhwani', 'अधवानी', 'adhwani', 'existing', NULL, NULL, NULL, '2026-03-13 07:43:03', '2026-03-13 07:43:03'),
(29, 20, 'test', 'परीक्षा', 'test-1', 'existing', NULL, NULL, NULL, '2026-03-13 08:20:33', '2026-03-13 08:20:33'),
(30, 21, 'apni', 'अपना', 'apni', 'existing', NULL, NULL, NULL, '2026-03-13 09:22:54', '2026-03-13 09:22:54'),
(31, 22, 'rerere', 'rerere', 'rerere', 'existing', NULL, NULL, NULL, '2026-03-13 11:14:02', '2026-03-13 11:14:02'),
(32, 12, 'ret', 'गीला करना', 'ret', 'existing', NULL, NULL, NULL, '2026-03-13 11:35:00', '2026-03-13 11:35:00'),
(33, 25, 'test', 'परीक्षा', 'test-2', 'existing', NULL, NULL, NULL, '2026-03-14 05:56:18', '2026-03-14 05:56:18'),
(34, 23, 'test', 'परीक्षा', 'test-3', 'existing', NULL, NULL, NULL, '2026-03-14 06:35:25', '2026-03-14 06:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `user_type` enum('admin','employee') DEFAULT 'employee',
  `employee_id` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `username`, `password`, `email`, `full_name`, `user_type`, `employee_id`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(10, 'ADMIN001', 'admin', '$2y$10$u0qfmzO0VDo8hB7Q/qupPu80TPrm/rJx2uz5B0cNOC6awyMsHEmNi', NULL, 'Super Administrator', 'admin', 'SUPER-ADMIN-001', 'active', '2026-03-27 22:33:31', '2026-03-27 15:39:21', '2026-03-27 17:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(7, 10, '6c223553ebcc3c2744730978468eec8669e8973cd8dc84f36ce9cd9e699864d3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 19:03:31', '2026-03-27 17:03:31');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_candidate_details`
-- (See below for the actual view)
--
CREATE TABLE `vw_candidate_details` (
`id` int(11)
,`candidate_id` varchar(50)
,`district_id` int(11)
,`jila_parishad_pradhan` enum('jila_parishad','pradhan')
,`jila_parishad_id` int(11)
,`block_id` int(11)
,`panchayat_id` int(11)
,`village` varchar(100)
,`candidate_name_hi` varchar(100)
,`candidate_name_en` varchar(100)
,`relation_type` enum('father','husband')
,`relation_name` varchar(100)
,`gender` enum('Male','Female','Other')
,`age` int(11)
,`education` varchar(200)
,`profession` varchar(100)
,`short_notes_hi` text
,`bio_hi` text
,`bio_en` text
,`slug` varchar(500)
,`photo_url` varchar(500)
,`video_message_url` varchar(500)
,`interview_video_url` varchar(500)
,`mobile_number` varchar(15)
,`whatsapp_number` varchar(15)
,`email` varchar(100)
,`facebook_url` varchar(500)
,`twitter_url` varchar(500)
,`instagram_url` varchar(500)
,`status` enum('contesting','leading','winner','runner_up','withdrawn')
,`approval_status` enum('pending','approved','rejected')
,`views` int(11)
,`featured` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`created_by` int(11)
,`district_name` varchar(100)
,`district_name_hi` varchar(100)
,`district_slug` varchar(100)
,`block_name` varchar(100)
,`block_name_hi` varchar(100)
,`block_slug` varchar(100)
,`panchayat_name` varchar(100)
,`panchayat_name_hi` varchar(100)
,`panchayat_slug` varchar(100)
,`page_url` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_election_stats`
-- (See below for the actual view)
--
CREATE TABLE `vw_election_stats` (
`district_name` varchar(100)
,`total_candidates` bigint(21)
,`male_candidates` bigint(21)
,`female_candidates` bigint(21)
,`winners` bigint(21)
,`panchayats_covered` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_candidate_details`
--
DROP TABLE IF EXISTS `vw_candidate_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_candidate_details`  AS SELECT `c`.`id` AS `id`, `c`.`candidate_id` AS `candidate_id`, `c`.`district_id` AS `district_id`, `c`.`jila_parishad_pradhan` AS `jila_parishad_pradhan`, `c`.`jila_parishad_id` AS `jila_parishad_id`, `c`.`block_id` AS `block_id`, `c`.`panchayat_id` AS `panchayat_id`, `c`.`village` AS `village`, `c`.`candidate_name_hi` AS `candidate_name_hi`, `c`.`candidate_name_en` AS `candidate_name_en`, `c`.`relation_type` AS `relation_type`, `c`.`relation_name` AS `relation_name`, `c`.`gender` AS `gender`, `c`.`age` AS `age`, `c`.`education` AS `education`, `c`.`profession` AS `profession`, `c`.`short_notes_hi` AS `short_notes_hi`, `c`.`bio_hi` AS `bio_hi`, `c`.`bio_en` AS `bio_en`, `c`.`slug` AS `slug`, `c`.`photo_url` AS `photo_url`, `c`.`video_message_url` AS `video_message_url`, `c`.`interview_video_url` AS `interview_video_url`, `c`.`mobile_number` AS `mobile_number`, `c`.`whatsapp_number` AS `whatsapp_number`, `c`.`email` AS `email`, `c`.`facebook_url` AS `facebook_url`, `c`.`twitter_url` AS `twitter_url`, `c`.`instagram_url` AS `instagram_url`, `c`.`status` AS `status`, `c`.`approval_status` AS `approval_status`, `c`.`views` AS `views`, `c`.`featured` AS `featured`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`created_by` AS `created_by`, `d`.`district_name` AS `district_name`, `d`.`district_name_hi` AS `district_name_hi`, `d`.`slug` AS `district_slug`, `b`.`block_name` AS `block_name`, `b`.`block_name_hi` AS `block_name_hi`, `b`.`slug` AS `block_slug`, `p`.`panchayat_name` AS `panchayat_name`, `p`.`panchayat_name_hi` AS `panchayat_name_hi`, `p`.`slug` AS `panchayat_slug`, concat('/panchayat-election/',`c`.`slug`) AS `page_url` FROM (((`candidates` `c` join `districts` `d` on(`c`.`district_id` = `d`.`id`)) join `blocks` `b` on(`c`.`block_id` = `b`.`id`)) join `panchayats` `p` on(`c`.`panchayat_id` = `p`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_election_stats`
--
DROP TABLE IF EXISTS `vw_election_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_election_stats`  AS SELECT `d`.`district_name` AS `district_name`, count(distinct `c`.`id`) AS `total_candidates`, count(distinct case when `c`.`gender` = 'Male' then `c`.`id` end) AS `male_candidates`, count(distinct case when `c`.`gender` = 'Female' then `c`.`id` end) AS `female_candidates`, count(distinct case when `c`.`status` = 'winner' then `c`.`id` end) AS `winners`, count(distinct `c`.`panchayat_id`) AS `panchayats_covered` FROM (`districts` `d` left join `candidates` `c` on(`d`.`id` = `c`.`district_id`)) GROUP BY `d`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user` (`user_id`),
  ADD KEY `idx_activity_date` (`created_at`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_block_district` (`district_id`),
  ADD KEY `idx_block_slug` (`slug`),
  ADD KEY `idx_blocks_jila_parishad` (`jila_parishad_id`),
  ADD KEY `idx_blocks_jila_parishad_pradhan` (`jila_parishad_pradhan`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `candidate_id` (`candidate_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_candidate_district` (`district_id`),
  ADD KEY `idx_candidate_block` (`block_id`),
  ADD KEY `idx_candidate_panchayat` (`panchayat_id`),
  ADD KEY `idx_candidate_status` (`status`),
  ADD KEY `idx_candidate_approval` (`approval_status`),
  ADD KEY `idx_candidate_featured` (`featured`),
  ADD KEY `idx_candidate_views` (`views`),
  ADD KEY `idx_candidates_search` (`candidate_name_en`,`candidate_name_hi`,`village`),
  ADD KEY `idx_candidates_created` (`created_at`),
  ADD KEY `idx_candidates_status_approval` (`status`,`approval_status`),
  ADD KEY `idx_candidates_jila_parishad` (`jila_parishad_id`),
  ADD KEY `idx_candidates_jila_parishad_pradhan` (`jila_parishad_pradhan`),
  ADD KEY `created_by` (`created_by`);
ALTER TABLE `candidates` ADD FULLTEXT KEY `idx_candidate_search` (`candidate_name_en`,`candidate_name_hi`,`village`,`bio_en`,`bio_hi`);

--
-- Indexes for table `candidate_news`
--
ALTER TABLE `candidate_news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_news_candidate` (`candidate_id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_district_slug` (`slug`);

--
-- Indexes for table `election_results`
--
ALTER TABLE `election_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_candidate_result` (`candidate_id`,`panchayat_id`),
  ADD KEY `panchayat_id` (`panchayat_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `jila_parishad`
--
ALTER TABLE `jila_parishad`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_jila_parishad_district` (`district_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `panchayats`
--
ALTER TABLE `panchayats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_panchayat_block` (`block_id`),
  ADD KEY `idx_panchayat_slug` (`slug`),
  ADD KEY `idx_panchayat_status` (`status`),
  ADD KEY `idx_panchayats_block_status` (`block_id`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blocks`
--
ALTER TABLE `blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `candidate_news`
--
ALTER TABLE `candidate_news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `election_results`
--
ALTER TABLE `election_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jila_parishad`
--
ALTER TABLE `jila_parishad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `panchayats`
--
ALTER TABLE `panchayats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blocks`
--
ALTER TABLE `blocks`
  ADD CONSTRAINT `blocks_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocks_ibfk_2` FOREIGN KEY (`jila_parishad_id`) REFERENCES `jila_parishad` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocks_ibfk_3` FOREIGN KEY (`jila_parishad_id`) REFERENCES `jila_parishad` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  ADD CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`),
  ADD CONSTRAINT `candidates_ibfk_3` FOREIGN KEY (`panchayat_id`) REFERENCES `panchayats` (`id`),
  ADD CONSTRAINT `candidates_ibfk_4` FOREIGN KEY (`jila_parishad_id`) REFERENCES `jila_parishad` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `candidates_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `candidates_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `candidate_news`
--
ALTER TABLE `candidate_news`
  ADD CONSTRAINT `candidate_news_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `election_results`
--
ALTER TABLE `election_results`
  ADD CONSTRAINT `election_results_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `election_results_ibfk_2` FOREIGN KEY (`panchayat_id`) REFERENCES `panchayats` (`id`);

--
-- Constraints for table `jila_parishad`
--
ALTER TABLE `jila_parishad`
  ADD CONSTRAINT `jila_parishad_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `panchayats`
--
ALTER TABLE `panchayats`
  ADD CONSTRAINT `panchayats_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `reset_candidate_views` ON SCHEDULE EVERY 1 DAY STARTS '2026-03-13 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE candidates SET views = views * 0.9 WHERE views > 100$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
