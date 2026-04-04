-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 09:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
(24, 1, 'UPDATE', 'candidates', 3, '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-14 08:18:08'),
(25, 1, 'UPDATE', 'candidates', 1, '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(26, 1, 'UPDATE', 'candidates', 2, '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(27, 1, 'UPDATE', 'candidates', 3, '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(28, 1, 'UPDATE', 'candidates', 4, '{\"name_en\": \"Candidate Gharoh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Gharoh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(29, 1, 'UPDATE', 'candidates', 5, '{\"name_en\": \"Candidate Kand\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Kand\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(30, 1, 'UPDATE', 'candidates', 6, '{\"name_en\": \"Candidate Bhagsu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Bhagsu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(31, 1, 'UPDATE', 'candidates', 7, '{\"name_en\": \"Candidate Rajpur\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Rajpur\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(32, 1, 'UPDATE', 'candidates', 11, '{\"name_en\": \"sahil sandhu\", \"status\": \"winner\", \"approval_status\": \"pending\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"winner\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-03-31 08:25:03'),
(33, 1, 'UPDATE', 'candidates', 12, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(34, 1, 'UPDATE', 'candidates', 13, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(35, 1, 'UPDATE', 'candidates', 14, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(36, 1, 'UPDATE', 'candidates', 15, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(37, 1, 'UPDATE', 'candidates', 16, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(38, 1, 'UPDATE', 'candidates', 17, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(39, 1, 'UPDATE', 'candidates', 18, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(40, 1, 'UPDATE', 'candidates', 19, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(41, 1, 'UPDATE', 'candidates', 20, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(42, 1, 'UPDATE', 'candidates', 21, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(43, 1, 'UPDATE', 'candidates', 22, '{\"name_en\": \"rest\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"rest\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(44, 1, 'UPDATE', 'candidates', 23, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(45, 1, 'UPDATE', 'candidates', 24, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(46, 1, 'UPDATE', 'candidates', 25, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(47, 1, 'UPDATE', 'candidates', 26, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(48, 1, 'UPDATE', 'candidates', 27, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(49, 1, 'UPDATE', 'candidates', 28, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(50, 1, 'UPDATE', 'candidates', 29, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(51, 1, 'UPDATE', 'candidates', 30, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(52, 1, 'UPDATE', 'candidates', 31, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(53, 1, 'UPDATE', 'candidates', 32, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(54, 1, 'UPDATE', 'candidates', 33, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(55, 1, 'UPDATE', 'candidates', 34, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(56, 1, 'UPDATE', 'candidates', 35, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 08:25:03'),
(57, 1, 'UPDATE', 'candidates', 35, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 12:28:52'),
(58, 1, 'UPDATE', 'candidates', 35, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"\"}', NULL, NULL, '2026-03-31 12:28:57'),
(59, 1, 'UPDATE', 'candidates', 34, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhurs\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 12:33:24'),
(60, 1, 'UPDATE', 'candidates', 1, '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(61, 1, 'UPDATE', 'candidates', 2, '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(62, 1, 'UPDATE', 'candidates', 3, '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(63, 1, 'UPDATE', 'candidates', 4, '{\"name_en\": \"Candidate Gharoh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Gharoh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(64, 1, 'UPDATE', 'candidates', 5, '{\"name_en\": \"Candidate Kand\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Kand\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(65, 1, 'UPDATE', 'candidates', 6, '{\"name_en\": \"Candidate Bhagsu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Bhagsu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(66, 1, 'UPDATE', 'candidates', 7, '{\"name_en\": \"Candidate Rajpur\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Rajpur\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(67, 1, 'UPDATE', 'candidates', 11, '{\"name_en\": \"sahil sandhu\", \"status\": \"winner\", \"approval_status\": \"pending\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"winner\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-03-31 14:49:24'),
(68, 1, 'UPDATE', 'candidates', 34, '{\"name_en\": \"sahil sandhurs\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhurs\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:49:24'),
(69, 1, 'UPDATE', 'candidates', 1, '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ramesh Kumar\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:17'),
(70, 1, 'UPDATE', 'candidates', 2, '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Seema Devi\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:17'),
(71, 1, 'UPDATE', 'candidates', 3, '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Ajay Singh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:17'),
(72, 1, 'UPDATE', 'candidates', 4, '{\"name_en\": \"Candidate Gharoh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Gharoh\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:17'),
(73, 1, 'UPDATE', 'candidates', 5, '{\"name_en\": \"Candidate Kand\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Kand\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:17'),
(74, 1, 'UPDATE', 'candidates', 6, '{\"name_en\": \"Candidate Bhagsu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Bhagsu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:17'),
(75, 1, 'UPDATE', 'candidates', 7, '{\"name_en\": \"Candidate Rajpur\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"Candidate Rajpur\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:17'),
(76, 1, 'UPDATE', 'candidates', 11, '{\"name_en\": \"sahil sandhu\", \"status\": \"winner\", \"approval_status\": \"pending\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"winner\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-03-31 14:51:18'),
(77, 1, 'UPDATE', 'candidates', 12, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(78, 1, 'UPDATE', 'candidates', 13, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(79, 1, 'UPDATE', 'candidates', 14, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(80, 1, 'UPDATE', 'candidates', 15, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(81, 1, 'UPDATE', 'candidates', 16, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(82, 1, 'UPDATE', 'candidates', 17, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(83, 1, 'UPDATE', 'candidates', 18, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(84, 1, 'UPDATE', 'candidates', 19, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(85, 1, 'UPDATE', 'candidates', 20, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(86, 1, 'UPDATE', 'candidates', 21, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(87, 1, 'UPDATE', 'candidates', 22, '{\"name_en\": \"rest\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"rest\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(88, 1, 'UPDATE', 'candidates', 23, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(89, 1, 'UPDATE', 'candidates', 24, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(90, 1, 'UPDATE', 'candidates', 25, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(91, 1, 'UPDATE', 'candidates', 26, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(92, 1, 'UPDATE', 'candidates', 27, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(93, 1, 'UPDATE', 'candidates', 28, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(94, 1, 'UPDATE', 'candidates', 29, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(95, 1, 'UPDATE', 'candidates', 30, '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"  sukhu sukhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:18'),
(96, 1, 'UPDATE', 'candidates', 31, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:19'),
(97, 1, 'UPDATE', 'candidates', 32, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:19'),
(98, 1, 'UPDATE', 'candidates', 33, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:19'),
(99, 1, 'UPDATE', 'candidates', 34, '{\"name_en\": \"sahil sandhurs\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhurs\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 14:51:19'),
(100, 1, 'UPDATE', 'candidates', 35, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"\"}', NULL, NULL, '2026-03-31 14:51:19'),
(101, 1, 'UPDATE', 'candidates', 21, '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', '{\"name_en\": \"sahil sandhu\", \"status\": \"contesting\", \"approval_status\": \"approved\"}', NULL, NULL, '2026-03-31 22:22:06'),
(102, 1, 'UPDATE', 'candidates', 41, '{\"name_en\": \"sanjeev kumar\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', '{\"name_en\": \"sanjeev kumar\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-04-01 09:33:21'),
(103, 1, 'UPDATE', 'candidates', 47, '{\"name_en\": \"aman\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', '{\"name_en\": \"aman\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-04-03 09:49:44'),
(104, 1, 'UPDATE', 'candidates', 50, '{\"name_en\": \"suresh kumar\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', '{\"name_en\": \"suresh kumar\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-04-03 10:32:07'),
(105, 1, 'UPDATE', 'candidates', 49, '{\"name_en\": \" abhay\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', '{\"name_en\": \" abhay\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-04-03 10:32:16'),
(106, 1, 'UPDATE', 'candidates', 48, '{\"name_en\": \"sanjeev kumar\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', '{\"name_en\": \"sanjeev kumar\", \"status\": \"contesting\", \"approval_status\": \"pending\"}', NULL, NULL, '2026-04-03 10:32:20');

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
-- Table structure for table `admin_user`
--

CREATE TABLE `admin_user` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `user_type` varchar(20) DEFAULT 'admin',
  `role` varchar(50) DEFAULT 'admin',
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_user`
--

INSERT INTO `admin_user` (`id`, `employee_id`, `username`, `password`, `full_name`, `email`, `user_type`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(16, 'ADMIN001', 'ajay_saklani', '$2y$10$xHFHVBahxY26UR1pfO5I6.RSy8QqxGa3FvPg1lpl3kPTRsNSxGTL6', 'Ajay Saklani', 'ajay.saklani@enoxx.id', 'admin', 'admin', 'active', NULL, '2026-04-04 06:11:27', '2026-04-04 06:11:27');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `employee_id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `last_login`, `created_at`) VALUES
(4, NULL, 'ajay_saklani', '$2y$10$YPRe.bvwUxL9.B/2f9dRM.vZ3AVQVunJ0lB5alRKrlMe47PrXCEqy', 'Ajay Saklani', 'ajay.saklani@enoxx.id', 'admin', 'active', NULL, '2026-04-04 07:31:45');

-- --------------------------------------------------------

--
-- Table structure for table `bdc_constituencies`
--

CREATE TABLE `bdc_constituencies` (
  `id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL,
  `constituency_name` varchar(100) NOT NULL,
  `constituency_name_hi` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bdc_constituencies`
--

INSERT INTO `bdc_constituencies` (`id`, `block_id`, `constituency_name`, `constituency_name_hi`, `slug`, `created_at`) VALUES
(1, 1, 'Dharamshala Rural', 'धर्मशाला ग्रामीण', 'dharamshala-rural', '2026-03-30 09:33:29'),
(2, 1, 'Kangra Valley', 'कांगड़ा घाटी', 'kangra-valley', '2026-03-30 09:33:29');

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
(25, 12, 33, 'test', 'परीक्षा', NULL, 'test-5', NULL, NULL, NULL, '2026-03-14 05:56:12', '2026-03-14 05:56:12'),
(35, 6, 6, 'jt', 'संयुक्त', NULL, 'jt', NULL, NULL, NULL, '2026-04-01 10:38:36', '2026-04-01 10:38:36'),
(38, 6, 6, 'batia no1', 'बटिया नंबर 1', NULL, 'batia-no1', NULL, NULL, NULL, '2026-04-02 07:43:53', '2026-04-02 07:43:53');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `candidate_unique_id` varchar(50) DEFAULT NULL,
  `candidate_id` varchar(50) NOT NULL,
  `district_id` int(11) NOT NULL,
  `representative_type_id` int(11) DEFAULT NULL,
  `jila_parishad_pradhan` enum('jila_parishad','pradhan') DEFAULT NULL,
  `jila_parishad_id` int(11) DEFAULT NULL,
  `block_id` int(11) DEFAULT NULL,
  `panchayat_id` int(11) DEFAULT NULL,
  `bdc_constituency_id` int(11) DEFAULT NULL,
  `zila_parishad_constituency_id` int(11) DEFAULT NULL,
  `village` varchar(100) NOT NULL,
  `village_hi` varchar(100) DEFAULT NULL,
  `candidate_name_hi` varchar(100) NOT NULL,
  `candidate_name_en` varchar(100) NOT NULL,
  `relation_type` enum('father','husband') NOT NULL,
  `relation_name` varchar(100) NOT NULL,
  `relation_name_hi` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `age` int(11) NOT NULL,
  `education` varchar(200) DEFAULT NULL,
  `education_hi` varchar(100) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `profession_hi` varchar(100) DEFAULT NULL,
  `short_notes_en` text DEFAULT NULL,
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
  `verification_expiry` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `transaction_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `candidate_unique_id`, `candidate_id`, `district_id`, `representative_type_id`, `jila_parishad_pradhan`, `jila_parishad_id`, `block_id`, `panchayat_id`, `bdc_constituency_id`, `zila_parishad_constituency_id`, `village`, `village_hi`, `candidate_name_hi`, `candidate_name_en`, `relation_type`, `relation_name`, `relation_name_hi`, `gender`, `age`, `education`, `education_hi`, `profession`, `profession_hi`, `short_notes_en`, `short_notes_hi`, `bio_hi`, `bio_en`, `slug`, `photo_url`, `video_message_url`, `interview_video_url`, `mobile_number`, `whatsapp_number`, `email`, `facebook_url`, `twitter_url`, `instagram_url`, `status`, `approval_status`, `views`, `featured`, `created_at`, `updated_at`, `created_by`, `whatsapp_verified`, `photo_hidden`, `verification_code`, `verification_expiry`, `is_active`, `transaction_id`) VALUES
(46, NULL, 'HPEL20264374', 6, 1, NULL, NULL, 35, 35, NULL, NULL, 'test', 'परीक्षा', 'संजीव कुमार', 'sanjeev kumar', 'father', 'ewe', 'एवै', 'Male', 54, 'rerererer', 'reererer', 'Netcoder Technology', 'नेटकोडर प्रौद्योगिकी', NULL, '', 'मैं संजीव कुमार, 54 साल का हूँ। ', 'I am sanjeev kumar, 54 years old. I have completed my rerererer. I work as a Netcoder Technology. I belong to test village. I am committed to serving my community and working for the development of our area.', 'sanjeev-kumar', 'uploads/candidates/1775039975_IMG_8932.JPG', '', '', '8350941126', NULL, NULL, NULL, NULL, NULL, 'contesting', 'pending', 0, 0, '2026-04-01 10:39:39', '2026-04-01 10:39:39', 6, 0, 1, NULL, NULL, 1, '232323232323'),
(47, NULL, 'HPEL20266889', 7, 1, NULL, NULL, 20, 29, NULL, NULL, 'rrer', 'rrer', 'Decoder Solutions', 'aman', 'father', 'wewewe', 'वेवेवे', 'Male', 44, 'rerererer', 'reererer', 'Netcoder Technology', 'नेटकोडर प्रौद्योगिकी', '', '', 'aman, rrer गांव के निवासी, wewewe के पुत्र हैं। पेशे से Netcoder Technology हैं और rerererer शिक्षित हैं। पंचायत चुनाव 2026 में भाग ले रहे हैं। इनका मुख्य उद्देश्य गांव का विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है।', 'aman, resident of rrer village, is the son of wewewe. By profession, a Netcoder Technology and is rerererer educated. Contesting in the Panchayat Election 2026. Their main objectives include village development, education, and health facilities.', 'aman', 'uploads/candidates/1775041170_IMG_0030.jpg', '', '', '9816132055', NULL, NULL, NULL, NULL, NULL, 'contesting', 'pending', 0, 0, '2026-04-01 10:59:33', '2026-04-03 09:49:44', 6, 0, 1, NULL, NULL, 1, '123123123'),
(48, NULL, 'HPEL20262058', 3, 1, NULL, NULL, 23, 34, NULL, NULL, 'rrer', 'rrer', 'संजीव कुमार', 'sanjeev kumar', 'father', 'ewe', 'एवै', 'Male', 43, 'rerererer', 'reererer', 'Netcoder Technology', 'नेटकोडर प्रौद्योगिकी', NULL, '', 'मैं संजीव कुमार, 43 साल का हूं। ', 'I am sanjeev kumar, 43 years old. I have completed my rerererer. I work as a Netcoder Technology. I belong to rrer village. I am committed to serving my community and working for the development of our area.', 'sanjeev-kumar-1', 'uploads/candidates/1775042646_IMG_0030.jpg', '', '', '8350941126', NULL, NULL, NULL, NULL, NULL, 'contesting', 'pending', 0, 0, '2026-04-01 11:24:10', '2026-04-03 10:32:20', 6, 1, 1, NULL, NULL, 1, 'ADMIN-1775212340-772'),
(49, NULL, 'HPEL20266216', 6, 3, NULL, NULL, 38, NULL, NULL, NULL, 'jaterh', 'jaterh', 'अभय', ' abhay', 'father', 'husband', 'husband', 'Male', 52, 'bca', 'बीसीए', 'Netcoder Technology', 'नेटकोडर प्रौद्योगिकी', NULL, '', 'मैं अभय, 52 साल का हूँ। मैंने अपना बीसीए पूरा कर लिया है. मैं नेटकोडर टेक्नोलॉजी के रूप में काम करता हूं। मैं जतेरह गांव का रहने वाला हूं. मैं अपने समुदाय की सेवा करने और अपने क्षेत्र के विकास के लिए काम करने के लिए प्रतिबद्ध हूं।', 'I am abhay, 52 years old. I have completed my bca. I work as a Netcoder Technology. I belong to jaterh village. I am committed to serving my community and working for the development of our area.', 'abhay', 'uploads/candidates/1775116288_IMG_0072.jpg', '', '', '8350941126', NULL, NULL, NULL, NULL, NULL, 'contesting', 'pending', 0, 0, '2026-04-02 07:51:31', '2026-04-03 10:32:16', 6, 1, 1, NULL, NULL, 1, 'ADMIN-1775212336-418'),
(50, NULL, 'HPEL20260300', 6, 1, NULL, NULL, 38, 36, NULL, NULL, 'jaterh', 'जतेरह', 'सुरेश कुमार', 'suresh kumar', 'father', 'husband', 'पति', 'Male', 25, 'bca ', 'बीसीए', 'Netcoder Technology', 'नेटकोडर प्रौद्योगिकी', NULL, '', 'मैं सुरेश कुमार, 25 साल का हूँ। मैंने अपना बीसीए पूरा कर लिया है. मैं नेटकोडर टेक्नोलॉजी के रूप में काम करता हूं। मैं जतेरह गांव का रहने वाला हूं. मैं अपने समुदाय की सेवा करने और अपने क्षेत्र के विकास के लिए काम करने के लिए प्रतिबद्ध हूं।', 'I am suresh kumar, 25 years old. I have completed my bca. I work as a Netcoder Technology. I belong to jaterh village. I am committed to serving my community and working for the development of our area.', 'suresh-kumar', 'uploads/candidates/1775117808_IMG_0035.jpg', '', '', '8350941126', NULL, NULL, NULL, NULL, NULL, 'contesting', 'pending', 0, 0, '2026-04-02 08:16:48', '2026-04-03 10:32:07', 6, 1, 1, NULL, NULL, 1, 'ADMIN-1775212327-489');

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
-- Table structure for table `candidate_entries`
--

CREATE TABLE `candidate_entries` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `action` enum('create','update','delete','approve','reject') DEFAULT 'create',
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(17, 'sahil', 'साहिल', 'sahil', NULL, NULL, NULL, '2026-03-13 08:05:04', '2026-03-13 08:05:04'),
(18, 'sss', 'sss', 'sss', NULL, NULL, NULL, '2026-03-31 20:58:56', '2026-03-31 20:58:56'),
(19, 'test', 'परीक्षा', 'test', NULL, NULL, NULL, '2026-04-01 10:38:19', '2026-04-01 10:38:19');

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
  `raw_password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `role` enum('data_entry','supervisor','manager') DEFAULT 'data_entry',
  `total_entries` int(11) DEFAULT 0,
  `last_entry_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `username`, `password`, `raw_password`, `full_name`, `email`, `phone`, `district_id`, `role`, `total_entries`, `last_entry_at`, `last_login`, `created_at`, `updated_at`, `is_active`) VALUES
(6, 'EMP7508', 'sahilsandhu', '$2y$10$A6Vn49XcERN52uqL5OHD0.sc2Oc4cs3vW2y38Ga3gOiTLerpqVXea', NULL, 'sahil sandhu', 'sahilsandhu39234@gmail.com', '0780767370', NULL, 'data_entry', 0, NULL, '2026-04-04 11:10:02', '2026-03-31 09:51:02', '2026-04-04 05:40:02', 1),
(7, 'EMP1503', 'ajay_saklanis', '$2y$10$0cMEz4IWe6peyByiGpj7S.x14fkyiwvW7J50UB5E.LRzQLFp2oq9e', NULL, 'sahil sandhu', 'sahilsandhu39234@gmail.com', '', NULL, '', 0, NULL, NULL, '2026-04-04 07:29:47', '2026-04-04 07:29:47', 1);

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
(34, 23, 'test', 'परीक्षा', 'test-3', 'existing', NULL, NULL, NULL, '2026-03-14 06:35:25', '2026-03-14 06:35:25'),
(35, 35, 'test', 'परीक्षा', 'test-4', 'existing', NULL, NULL, NULL, '2026-04-01 10:38:46', '2026-04-01 10:38:46'),
(36, 38, 'adhwani', 'अधवानी', 'adhwani-1', 'existing', NULL, NULL, NULL, '2026-04-02 07:44:08', '2026-04-02 07:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `representative_types`
--

CREATE TABLE `representative_types` (
  `id` int(11) NOT NULL,
  `type_key` varchar(50) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `type_name_hi` varchar(100) DEFAULT NULL,
  `has_block` tinyint(1) DEFAULT 0,
  `has_panchayat` tinyint(1) DEFAULT 0,
  `has_bdc_constituency` tinyint(1) DEFAULT 0,
  `has_zila_parishad_constituency` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `representative_types`
--

INSERT INTO `representative_types` (`id`, `type_key`, `type_name`, `type_name_hi`, `has_block`, `has_panchayat`, `has_bdc_constituency`, `has_zila_parishad_constituency`, `sort_order`, `created_at`) VALUES
(1, 'pradhan', 'Pradhan', 'प्रधान', 1, 1, 0, 0, 1, '2026-03-30 09:29:49'),
(2, 'vice_pradhan', 'Vice Pradhan', 'उप प्रधान', 1, 1, 0, 0, 2, '2026-03-30 09:29:49'),
(3, 'bdc_member', 'BDC Member', 'बीडीसी सदस्य', 1, 0, 1, 0, 3, '2026-03-30 09:29:49'),
(4, 'zila_parishad_member', 'Zila Parishad Member', 'जिला परिषद सदस्य', 0, 0, 0, 1, 4, '2026-03-30 09:29:49');

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
(10, 'ADMIN001', 'admin', '$2y$10$u0qfmzO0VDo8hB7Q/qupPu80TPrm/rJx2uz5B0cNOC6awyMsHEmNi', NULL, 'Super Administrator', 'admin', 'SUPER-ADMIN-001', 'active', '2026-03-27 22:33:31', '2026-03-27 15:39:21', '2026-03-27 17:03:31'),
(11, 'EMP20260403557', 'admin@example.com', '$2y$10$cLbxUZXk1iBwuuvLoRtQ9uQ3GFhfcFQiduQg9u23WHcPvwKNPhmH2', 'decodersolutions2023@gmail.com', 'sanjeev kumar', 'employee', 'ENOXX-2026-744', 'inactive', NULL, '2026-04-03 10:16:35', '2026-04-03 10:46:55'),
(20, '', 'ajay_saklani', '$2y$10$8febDIqh6P6rUQXh1HN/Ie.oFKN49UtRPbU/pQ3YrxWjkU0W79dPu', 'ajay.saklani@enoxx.id', 'Ajay Saklani', 'admin', 'ADMIN001', 'active', NULL, '2026-04-04 06:11:02', '2026-04-04 06:11:02');

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
-- Table structure for table `zila_parishad_constituencies`
--

CREATE TABLE `zila_parishad_constituencies` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `constituency_name` varchar(100) NOT NULL,
  `constituency_name_hi` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `ward_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zila_parishad_constituencies`
--

INSERT INTO `zila_parishad_constituencies` (`id`, `district_id`, `constituency_name`, `constituency_name_hi`, `slug`, `ward_number`, `created_at`) VALUES
(1, 1, 'Dharamshala Ward 1', 'धर्मशाला वार्ड 1', 'dharamshala-ward-1', '1', '2026-03-30 09:33:29'),
(2, 1, 'Dharamshala Ward 2', 'धर्मशाला वार्ड 2', 'dharamshala-ward-2', '2', '2026-03-30 09:33:29'),
(3, 1, 'Kangra Ward 1', 'कांगड़ा वार्ड 1', 'kangra-ward-1', '3', '2026-03-30 09:33:29');

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`),
  ADD KEY `idx_activity_logs_user_id` (`user_id`);

--
-- Indexes for table `admin_user`
--
ALTER TABLE `admin_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `bdc_constituencies`
--
ALTER TABLE `bdc_constituencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_constituency_block` (`block_id`,`constituency_name`),
  ADD UNIQUE KEY `slug` (`slug`);

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
  ADD KEY `created_by` (`created_by`),
  ADD KEY `representative_type_id` (`representative_type_id`),
  ADD KEY `idx_candidates_location` (`district_id`,`block_id`,`panchayat_id`),
  ADD KEY `idx_candidates_created_at` (`created_at`),
  ADD KEY `idx_candidates_created_by` (`created_by`),
  ADD KEY `idx_candidates_status` (`whatsapp_verified`,`approval_status`);
ALTER TABLE `candidates` ADD FULLTEXT KEY `idx_candidate_search` (`candidate_name_en`,`candidate_name_hi`,`village`,`bio_en`,`bio_hi`);

--
-- Indexes for table `candidate_entries`
--
ALTER TABLE `candidate_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `employee_id` (`employee_id`);

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
-- Indexes for table `representative_types`
--
ALTER TABLE `representative_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_key` (`type_key`);

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
-- Indexes for table `zila_parishad_constituencies`
--
ALTER TABLE `zila_parishad_constituencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_constituency_district` (`district_id`,`constituency_name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `admin_user`
--
ALTER TABLE `admin_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bdc_constituencies`
--
ALTER TABLE `bdc_constituencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `blocks`
--
ALTER TABLE `blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `candidate_entries`
--
ALTER TABLE `candidate_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidate_news`
--
ALTER TABLE `candidate_news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `election_results`
--
ALTER TABLE `election_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `representative_types`
--
ALTER TABLE `representative_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `zila_parishad_constituencies`
--
ALTER TABLE `zila_parishad_constituencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bdc_constituencies`
--
ALTER TABLE `bdc_constituencies`
  ADD CONSTRAINT `bdc_constituencies_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `candidates_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `candidates_ibfk_7` FOREIGN KEY (`representative_type_id`) REFERENCES `representative_types` (`id`);

--
-- Constraints for table `candidate_entries`
--
ALTER TABLE `candidate_entries`
  ADD CONSTRAINT `candidate_entries_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`),
  ADD CONSTRAINT `candidate_entries_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

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

--
-- Constraints for table `zila_parishad_constituencies`
--
ALTER TABLE `zila_parishad_constituencies`
  ADD CONSTRAINT `zila_parishad_constituencies_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE;

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
