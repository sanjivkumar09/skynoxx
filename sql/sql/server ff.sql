-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 05, 2025 at 10:47 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u938578626_ff`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`u938578626_skynoxx`@`127.0.0.1` PROCEDURE `sp_validate_team_profiles` (IN `p_registration_id` INT, OUT `p_valid` BOOLEAN, OUT `p_message` VARCHAR(500))   BEGIN
    DECLARE incomplete_count INT DEFAULT 0;
    DECLARE incomplete_names TEXT DEFAULT '';
    
    -- Check for incomplete profiles
    SELECT 
        COUNT(*),
        GROUP_CONCAT(u.name SEPARATOR ', ')
    INTO incomplete_count, incomplete_names
    FROM team_registrations tr
    JOIN users u ON tr.user_id = u.id
    LEFT JOIN players_profile pp ON tr.user_id = pp.user_id
    WHERE tr.registration_id = p_registration_id
    AND (pp.game_uid IS NULL OR pp.game_uid = '' 
         OR pp.in_game_name IS NULL OR pp.in_game_name = '');
    
    IF incomplete_count > 0 THEN
        SET p_valid = FALSE;
        SET p_message = CONCAT('Incomplete profiles for: ', incomplete_names);
    ELSE
        SET p_valid = TRUE;
        SET p_message = 'All team members have complete profiles';
    END IF;
END$$

--
-- Functions
--
CREATE DEFINER=`u938578626_skynoxx`@`127.0.0.1` FUNCTION `fn_get_team_size` (`p_registration_id` INT) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE team_size INT DEFAULT 0;
    
    SELECT COUNT(*) INTO team_size
    FROM team_registrations
    WHERE registration_id = p_registration_id;
    
    RETURN team_size;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `admin_email` varchar(100) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `mobile_no` varchar(15) DEFAULT NULL,
  `access_level` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `admin_name`, `admin_email`, `admin_password`, `mobile_no`, `access_level`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(4, 9, 'Admin User', 'skynoxx@admin', '$2y$10$g/zaQ6ytsN4tgIBgupRaEePr/1DHMnJt8p9OSGF99PevbCfkch3A2', '9981474023', 'super_admin', 1, '2025-11-03 02:49:51', '2025-10-30 08:56:45', '2025-11-03 02:49:51');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `creators`
--

CREATE TABLE `creators` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile_no` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `game_uid` varchar(50) DEFAULT NULL,
  `yt_channel_name` varchar(255) DEFAULT NULL,
  `yt_channel_link` varchar(500) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `game_profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `creators`
--

INSERT INTO `creators` (`id`, `user_id`, `name`, `mobile_no`, `email`, `game_uid`, `yt_channel_name`, `yt_channel_link`, `profile_pic`, `game_profile_pic`, `created_at`, `updated_at`) VALUES
(2, 11, 'Harshit Malik', '9258804657', 'malikharshit812@gmail.com', 'FF-2495341112', 'CN NITIN YT', 'https://youtube.com/@cnnitinyt?si=g-psv3PLecUl2DKN', NULL, NULL, '2025-10-30 09:49:22', '2025-10-30 09:49:58'),
(3, 12, 'nitin jaiswal', '9258804657', 'malikharshit674@gmail.com', 'FF-1464311210', 'CN NITIN YT', 'https://youtube.com/@cnnitinyt?si=g-psv3PLecUl2DKN', NULL, NULL, '2025-10-30 10:40:48', '2025-10-30 10:40:48'),
(4, 22, 'sanjiv Kumar', '09981474023', 'gameshear09@gmail.com', 'FF-1789721227', 'Skynoxx', 'https://youtube.com/@skynoxx-i7e?si=2WTrZYYKcEfDuGoc', 'src/uploads/creators/profile_22_1761906897.png', 'src/uploads/creators/game_profile_22_1761906897.png', '2025-10-30 14:20:08', '2025-10-31 10:34:57'),
(5, 88, 'RUPAN SARKAR', '6003546998', 'srupan431@gmail.com', 'FF-2027469222', 'RUPAN FF', 'https://www.youtube.com/@RUPANFFF', NULL, NULL, '2025-11-05 08:37:51', '2025-11-05 08:37:51');

-- --------------------------------------------------------

--
-- Table structure for table `match_results`
--

CREATE TABLE `match_results` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `match_number` tinyint(4) NOT NULL COMMENT '1, 2, or 3',
  `registration_id` int(11) NOT NULL COMMENT 'Links to registrations table (player or team captain)',
  `placement` int(11) NOT NULL COMMENT 'Final rank: 1st, 2nd, 3rd, etc.',
  `kills` int(11) DEFAULT 0 COMMENT 'Total kills in this match',
  `damage` int(11) DEFAULT 0 COMMENT 'Total damage dealt',
  `survival_time` int(11) DEFAULT 0 COMMENT 'Time survived in seconds',
  `placement_points` decimal(10,2) DEFAULT 0.00 COMMENT 'Points for placement',
  `kill_points` decimal(10,2) DEFAULT 0.00 COMMENT 'Points for kills',
  `bhooya_points` decimal(10,2) DEFAULT 0.00,
  `bonus_points` decimal(10,2) DEFAULT 0.00 COMMENT 'Any bonus points (e.g., most kills)',
  `updated_by` int(11) DEFAULT NULL COMMENT 'Creator who entered stats',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_points` decimal(10,2) GENERATED ALWAYS AS (`placement_points` + `kill_points` + `bhooya_points` + `bonus_points`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Stores match-by-match statistics for tournaments';

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` enum('tournament_created','tournament_starting_soon','tournament_started','tournament_completed','tournament_cancelled','prize_credited','withdrawal_approved','withdrawal_rejected','low_balance','payment_received','player_joined','system_announcement') NOT NULL DEFAULT 'tournament_created',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `audience` enum('all','players','creators','user') NOT NULL DEFAULT 'players',
  `audience_user_id` int(11) DEFAULT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `title`, `message`, `tournament_id`, `audience`, `audience_user_id`, `related_user_id`, `metadata`, `created_at`) VALUES
(1, 'tournament_created', 'New Tournament: Skynoxx Offical : DUO', 'Creator posted a new tournament on 2025-10-31 21:00 | Entry ₹0.00 | Prize ₹100.00', 1, 'players', NULL, NULL, NULL, '2025-10-30 16:11:12'),
(2, '', 'Added to Tournament Team', 'A player has added you to the team \'Gangster squad 💪\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 40, NULL, NULL, '2025-10-31 09:09:13'),
(3, '', 'Added to Tournament Team', 'A player has added you to the team \'\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 44, NULL, NULL, '2025-10-31 09:59:35'),
(4, '', 'Added to Tournament Team', 'A player has added you to the team \'Blizzzz Esp\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 47, NULL, NULL, '2025-10-31 10:17:58'),
(5, '', 'Added to Tournament Team', 'A player has added you to the team \'\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 49, NULL, NULL, '2025-10-31 10:45:19'),
(6, '', 'Added to Tournament Team', 'A player has added you to the team \'PungiMan\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 54, NULL, NULL, '2025-10-31 12:36:54'),
(7, '', 'Added to Tournament Team', 'A player has added you to the team \'Ds Esports\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 13, NULL, NULL, '2025-10-31 13:01:56'),
(8, '', 'Added to Tournament Team', 'A player has added you to the team \'D3VIL ESP\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 56, NULL, NULL, '2025-10-31 13:31:28'),
(9, '', 'Added to Tournament Team', 'A player has added you to the team \'BADMOSS\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 57, NULL, NULL, '2025-10-31 13:36:53'),
(10, '', 'Added to Tournament Team', 'A player has added you to the team \'Moro\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 61, NULL, NULL, '2025-10-31 15:06:12'),
(11, '', 'Added to Tournament Team', 'A player has added you to the team \'Aura Farmers\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 41, NULL, NULL, '2025-10-31 15:51:41'),
(12, '', 'Added to Tournament Team', 'A player has added you to the team \'Nexora Esports\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 59, NULL, NULL, '2025-11-02 08:01:41'),
(13, '', 'Team Disbanded', 'A player has left the team for tournament \'Skynoxx Offical : DUO\'. The entire team registration has been cancelled.', 1, 'user', 15, NULL, NULL, '2025-11-02 08:05:59'),
(14, '', 'Added to Tournament Team', 'A player has added you to the team \'DS-Esports\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 15, NULL, NULL, '2025-11-02 08:06:46'),
(15, '', 'Added to Tournament Team', 'A player has added you to the team \'Team monster\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 21, NULL, NULL, '2025-11-02 09:44:36'),
(16, '', 'Added to Tournament Team', 'A player has added you to the team \'TEAMᎪᴡᴀʀᴀ\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 72, NULL, NULL, '2025-11-02 11:06:19'),
(17, '', 'Added to Tournament Team', 'A player has added you to the team \'EGOS\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 74, NULL, NULL, '2025-11-02 12:23:32'),
(18, '', 'Added to Tournament Team', 'A player has added you to the team \'Chauhan ji\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 75, NULL, NULL, '2025-11-02 13:30:49'),
(19, '', 'Added to Tournament Team', 'A player has added you to the team \'D-IKBAL\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 77, NULL, NULL, '2025-11-02 14:18:55'),
(20, '', 'Added to Tournament Team', 'A player has added you to the team \'The Legend\'s\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 79, NULL, NULL, '2025-11-02 15:21:11'),
(21, '', 'Added to Tournament Team', 'A player has added you to the team \'Team dnzoo\' for the tournament \'Skynoxx Offical : DUO\'. You can leave the team anytime from your dashboard.', 1, 'user', 27, NULL, NULL, '2025-11-02 15:36:07'),
(22, '', 'Team Disbanded', 'A player has left the team for tournament \'Skynoxx Offical : DUO\'. The entire team registration has been cancelled.', 1, 'user', 74, NULL, NULL, '2025-11-02 15:36:35'),
(23, 'tournament_created', 'New Tournament: SKYNOX TEAM TOURNAMENT', 'Creator posted a new tournament on 2025-11-09 21:00 | Entry ₹0.00 | Prize ₹300.00', 2, 'players', NULL, NULL, NULL, '2025-11-03 10:44:49'),
(24, '', 'Added to Tournament Team', 'A player has added you to the team \'DS ESPORTS\' for the tournament \'SKYNOX TEAM TOURNAMENT\'. You can leave the team anytime from your dashboard.', 2, 'user', 15, NULL, NULL, '2025-11-03 12:58:36'),
(25, '', 'Added to Tournament Team', 'A player has added you to the team \'DS ESPORTS\' for the tournament \'SKYNOX TEAM TOURNAMENT\'. You can leave the team anytime from your dashboard.', 2, 'user', 50, NULL, NULL, '2025-11-03 12:58:36'),
(26, '', 'Added to Tournament Team', 'A player has added you to the team \'DS ESPORTS\' for the tournament \'SKYNOX TEAM TOURNAMENT\'. You can leave the team anytime from your dashboard.', 2, 'user', 13, NULL, NULL, '2025-11-03 12:58:36'),
(27, 'tournament_created', 'New Tournament: cnnitinlive', 'Creator posted a new tournament on 2025-11-05 03:00 | Entry ₹0.00 | Prize ₹0.00', 3, 'players', NULL, NULL, NULL, '2025-11-05 09:04:41'),
(28, 'tournament_created', 'New Tournament: CN NITIN LIVE', 'Creator posted a new tournament on 2025-11-08 19:12 | Entry ₹0.00 | Prize ₹0.00', 4, 'players', NULL, NULL, NULL, '2025-11-05 09:42:42');

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tournament_starting_soon` tinyint(1) DEFAULT 1,
  `tournament_results` tinyint(1) DEFAULT 1,
  `prize_credited` tinyint(1) DEFAULT 1,
  `withdrawal_updates` tinyint(1) DEFAULT 1,
  `low_balance_alert` tinyint(1) DEFAULT 1,
  `payment_updates` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `push_notifications` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`id`, `user_id`, `tournament_starting_soon`, `tournament_results`, `prize_credited`, `withdrawal_updates`, `low_balance_alert`, `payment_updates`, `email_notifications`, `push_notifications`, `created_at`, `updated_at`) VALUES
(1, 49, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(2, 85, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(3, 35, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(4, 25, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(5, 76, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(6, 27, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(7, 56, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(8, 90, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(9, 36, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(10, 89, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(11, 69, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(12, 54, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(13, 78, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(14, 86, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(15, 33, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(16, 63, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(17, 51, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(18, 14, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(19, 22, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(20, 23, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(21, 82, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(22, 91, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(23, 68, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(24, 46, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(25, 55, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(26, 48, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(27, 41, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(28, 59, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(29, 13, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(30, 83, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(31, 93, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(32, 79, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(33, 84, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(34, 53, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(35, 73, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(36, 6, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(37, 61, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(38, 15, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(39, 67, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(40, 12, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(41, 11, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(42, 50, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(43, 45, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(44, 64, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(45, 26, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(46, 70, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(47, 72, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(48, 39, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(49, 38, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(50, 47, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(51, 18, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(52, 57, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(53, 10, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(54, 65, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(55, 40, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(56, 52, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(57, 42, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(58, 43, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(59, 24, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(60, 58, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(61, 77, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(62, 75, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(63, 44, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(64, 71, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(65, 34, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(66, 9, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(67, 21, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(68, 88, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(69, 66, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(70, 30, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(71, 80, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(72, 81, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(73, 74, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(74, 92, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(75, 60, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48'),
(76, 62, 1, 1, 1, 1, 1, 1, 1, 1, '2025-11-05 10:41:48', '2025-11-05 10:41:48');

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `user_id` int(11) NOT NULL,
  `last_read_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--

INSERT INTO `notification_reads` (`user_id`, `last_read_at`, `updated_at`) VALUES
(6, '2025-10-30 16:34:00', '2025-10-30 16:34:00'),
(10, '2025-10-30 14:59:31', '2025-10-30 14:59:31'),
(13, '2025-11-05 05:28:31', '2025-11-05 05:28:31'),
(14, '2025-10-31 08:14:30', '2025-10-31 08:14:30'),
(25, '2025-10-30 16:27:22', '2025-10-30 16:27:22'),
(30, '2025-10-31 05:41:33', '2025-10-31 05:41:33'),
(33, '2025-11-02 12:45:30', '2025-11-02 12:45:30'),
(52, '2025-10-31 12:40:36', '2025-10-31 12:40:36'),
(55, '2025-11-02 02:45:25', '2025-11-02 02:45:25');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('UPI','Paytm','Razorpay','Cash') DEFAULT 'UPI',
  `txn_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `transaction_type` enum('entry_fee','prize','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `payment_gateway` varchar(50) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players_profile`
--

CREATE TABLE `players_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `in_game_name` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `game_uid` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players_profile`
--

INSERT INTO `players_profile` (`id`, `user_id`, `in_game_name`, `avatar`, `screenshot`, `game_uid`, `profile_pic`, `upi_id`, `created_at`, `updated_at`) VALUES
(1, 6, 'DS SKYNOX', 'src/uploads/players/avatar_6_1762109699.png', 'src/uploads/players/screenshot_6_1761814398.png', '1789721227', NULL, '9981474023-2@ybl', '2025-10-30 08:53:18', '2025-11-02 18:54:59'),
(2, 14, 'Deepak✓live', NULL, 'src/uploads/players/screenshot_14_1761823892.jpg', '1034847395', NULL, '', '2025-10-30 11:27:28', '2025-10-30 11:31:32'),
(4, 18, 'Priyanshu', NULL, NULL, '702553712', NULL, '8349816222@ybl', '2025-10-30 12:59:53', '2025-10-30 12:59:53'),
(5, 24, 'rs_king', 'src/uploads/players/avatar_24_1761841456.jpeg', 'src/uploads/players/screenshot_24_1761841456.png', '2149495506', NULL, 'rohansingh6227@oksbi', '2025-10-30 16:24:16', '2025-11-01 10:22:52'),
(6, 25, 'BOTAMAN', NULL, NULL, '1160592579', NULL, '9302411960123@ybl', '2025-10-30 16:26:50', '2025-10-30 16:26:50'),
(7, 13, 'DSㅤSUBASAㅤ!!', 'src/uploads/players/avatar_13_1761848493.jpg', 'src/uploads/players/screenshot_13_1761848571.jpg', '1828337928', NULL, '7407471509@fam', '2025-10-30 18:20:52', '2025-10-30 18:22:51'),
(10, 34, 'Blizzard', 'src/uploads/players/avatar_34_1761894827.jpg', 'src/uploads/players/screenshot_34_1761894827.jpg', '1525847249', NULL, 'singh24092006rohit@oksbi', '2025-10-31 07:13:41', '2025-10-31 07:13:47'),
(12, 36, 'Ꮪᴀ֟፝ɢᴀʀƦㅤ!!', NULL, NULL, '964690189', NULL, '7568807705@ybl', '2025-10-31 08:07:49', '2025-10-31 08:07:49'),
(13, 35, 'Akki', NULL, 'src/uploads/players/screenshot_35_1761898361.jpg', '4314744090', NULL, 'akshay-agrawal@ptyes', '2025-10-31 08:11:54', '2025-10-31 08:12:41'),
(16, 39, 'HE ONIK', 'src/uploads/players/avatar_39_1761914811.jpeg', 'src/uploads/players/screenshot_39_1761899319.png', '1605746957', NULL, 'onikrana-1@okhdfcbank', '2025-10-31 08:28:39', '2025-10-31 12:46:51'),
(17, 40, '*PREM ff', NULL, NULL, '923020574', NULL, '7297041772@ibl', '2025-10-31 09:08:28', '2025-10-31 09:08:28'),
(18, 44, 'Naik..ji....', NULL, NULL, '1764084446', NULL, '7987276589@fam', '2025-10-31 09:56:55', '2025-10-31 09:56:55'),
(20, 45, 'Vasu', NULL, NULL, '2194869704', NULL, '8815469712@naviaxis', '2025-10-31 09:58:30', '2025-10-31 09:58:30'),
(22, 10, '', NULL, NULL, '', NULL, '', '2025-10-31 10:07:19', '2025-10-31 10:07:19'),
(23, 47, 'devDory0702Q', 'src/uploads/players/avatar_47_1761905775.jpg', 'src/uploads/players/screenshot_47_1761905775.jpg', '2473642496', NULL, 'singh24092006rohit@oksbi', '2025-10-31 10:16:15', '2025-10-31 10:16:15'),
(24, 49, 'Aadarsh', 'src/uploads/players/avatar_49_1761907612.jpg', 'src/uploads/players/screenshot_49_1761907612.jpg', '6023245302', NULL, '9718550726@fam', '2025-10-31 10:29:36', '2025-10-31 10:46:52'),
(25, 33, 'Killer', 'src/uploads/players/avatar_33_1761906999.jpg', 'src/uploads/players/screenshot_33_1761906999.jpg', '9190385521', NULL, '9315597122@ptsbi', '2025-10-31 10:36:08', '2025-11-02 15:21:44'),
(33, 27, 'DS JAMES !!', NULL, 'src/uploads/players/screenshot_27_1761907745.jpg', '2189742446', NULL, 'aradhyasrivastava115599@oksbi', '2025-10-31 10:49:05', '2025-10-31 10:49:05'),
(34, 15, 'DS NINJA !!', NULL, NULL, '1012562212', NULL, 'shivangisnotfineaf@fam', '2025-10-31 12:30:33', '2025-10-31 12:30:33'),
(35, 52, '@SW-PARMEyt', NULL, NULL, '1238045245', NULL, 'swparmeyt@axl', '2025-10-31 12:31:35', '2025-10-31 12:31:35'),
(36, 54, 'TARGET>', NULL, NULL, '3501812237', NULL, 'archanathakur@fam', '2025-10-31 12:36:01', '2025-10-31 12:36:01'),
(39, 55, 'ZEROFYRE   78', NULL, NULL, '3167240349', NULL, 'rd1095708-2@okicici', '2025-10-31 13:19:21', '2025-10-31 13:23:14'),
(41, 56, 'D3VIL   AYUSH', NULL, NULL, '2514446660', NULL, '', '2025-10-31 13:30:57', '2025-10-31 13:30:57'),
(42, 48, 'Younes', 'src/uploads/players/avatar_48_1761917485.png', 'src/uploads/players/screenshot_48_1761917485.png', '3028037965', NULL, 'harshpatel446446@okicici', '2025-10-31 13:31:25', '2025-10-31 13:31:25'),
(43, 57, 'VISION', 'src/uploads/players/avatar_57_1761917734.png', 'src/uploads/players/screenshot_57_1761917734.png', '660747050', NULL, '6261277308@superyes', '2025-10-31 13:35:34', '2025-10-31 13:35:34'),
(44, 60, 'Dg moro', NULL, NULL, '2077647199', NULL, '8219846382@fam', '2025-10-31 14:55:04', '2025-10-31 14:55:04'),
(45, 59, 'ISHOOO.X', 'src/uploads/players/avatar_59_1761922697.jpg', NULL, '5330394936', NULL, '9300496499@fam', '2025-10-31 14:58:17', '2025-10-31 14:58:17'),
(46, 61, '모ﾠᴢⲭﾠvivek', NULL, NULL, '1714240419', NULL, '8219647621@fam', '2025-10-31 15:05:24', '2025-10-31 15:05:24'),
(47, 41, 'Harsh', 'src/uploads/players/avatar_41_1761924968.jpg', 'src/uploads/players/screenshot_41_1761924968.jpg', '2942479436', NULL, 'hc169720@okaxis', '2025-10-31 15:36:08', '2025-10-31 15:36:08'),
(48, 62, 'Dᴀʀᴋ☂︎Sᴘɪʀɪᴛ', 'src/uploads/players/avatar_62_1761925771.jpg', 'src/uploads/players/screenshot_62_1761925771.png', '5730230005', NULL, 'jyoti17403-1@okaxis', '2025-10-31 15:49:28', '2025-10-31 15:50:48'),
(51, 63, 'ᴵᴬᴹ᭄DEV࿐✓✓', NULL, 'src/uploads/players/screenshot_63_1761959895.jpg', '5232815267', NULL, 'swparmeyt@axl', '2025-11-01 01:18:15', '2025-11-01 01:18:15'),
(52, 64, 'Dnzzo 4X', 'src/uploads/players/avatar_64_1761969687.jpg', 'src/uploads/players/screenshot_64_1761969687.jpg', '7443872436', NULL, 'nabapratimbhuyan@oksbi', '2025-11-01 04:01:27', '2025-11-01 04:01:27'),
(54, 70, 'ISHOOO.X', NULL, NULL, '5330394936', NULL, '9300496499@fam', '2025-11-02 06:52:49', '2025-11-02 06:52:49'),
(55, 69, 'LionEditzzzz', NULL, NULL, '8704759347', NULL, '', '2025-11-02 08:01:16', '2025-11-02 08:01:16'),
(56, 21, '3goist speed', NULL, NULL, '4247100328', NULL, '7384617783@ybl', '2025-11-02 09:31:22', '2025-11-02 09:31:22'),
(57, 71, 'ISHIKAA !!ᥫ᭡', 'src/uploads/players/avatar_71_1762080967.webp', NULL, '4187206596', NULL, '7011050998@fam', '2025-11-02 10:56:07', '2025-11-02 10:56:07'),
(58, 72, 'Rɪsʜɪㅤᴮᴴᴬᴵ☂', 'src/uploads/players/avatar_72_1762081460.png', 'src/uploads/players/screenshot_72_1762081460.png', '3293146508', NULL, '7011050998@fam', '2025-11-02 11:03:50', '2025-11-02 11:04:20'),
(61, 46, 'LAND MARK', NULL, 'src/uploads/players/screenshot_46_1762085520.jpg', '3102795982', NULL, '8826523637@ybl', '2025-11-02 12:12:00', '2025-11-02 12:12:00'),
(63, 73, 'KUSH !!!', NULL, NULL, '576610409', NULL, '9399150941@fam', '2025-11-02 12:18:16', '2025-11-02 12:24:11'),
(64, 74, 'vanisher', NULL, NULL, '9360541272', NULL, '9399150941@fam', '2025-11-02 12:23:14', '2025-11-02 12:23:14'),
(66, 75, 'ᎢᎻㅤROHITᴳᴹᴿ™', 'src/uploads/players/avatar_75_1762086299.jpg', 'src/uploads/players/screenshot_75_1762086299.jpg', '2246952096', NULL, '7011050998@fam', '2025-11-02 12:24:59', '2025-11-02 12:24:59'),
(67, 76, 'AE-SKYRO', 'src/uploads/players/avatar_76_1762090087.jpg', 'src/uploads/players/screenshot_76_1762090087.jpg', '1035133950', NULL, '7011050998@fam', '2025-11-02 13:28:05', '2025-11-02 13:28:07'),
(70, 77, 'Drakan', NULL, NULL, '923924976', NULL, '8527630310@fam', '2025-11-02 14:09:22', '2025-11-02 14:09:22'),
(72, 50, 'D-IKBAL', NULL, NULL, '1710610516', NULL, '6203753945@ybl', '2025-11-02 14:09:51', '2025-11-02 14:16:50'),
(74, 78, 'Arjun★Bʜᴀɪ࿐', NULL, NULL, '1521451651', NULL, '7029120945', '2025-11-02 14:38:55', '2025-11-02 14:38:55'),
(75, 79, '░Ꮶ░Α░Ꮢ░Α░N░⚡', 'src/uploads/players/avatar_79_1762096776.webp', 'src/uploads/players/screenshot_79_1762096776.jpg', '3047957668', NULL, '8826523637@ybl', '2025-11-02 15:19:36', '2025-11-02 15:19:36'),
(77, 80, '➳ᴰᵃʳᵏ᭄Aᴡᴀʀᴀ࿐', NULL, 'src/uploads/players/screenshot_80_1762097443.jpg', '557972655', NULL, '9350370985-2@ybl', '2025-11-02 15:30:43', '2025-11-02 15:30:43'),
(78, 82, 'DEV4NSH_BHAI', NULL, NULL, '3303389031', NULL, '', '2025-11-02 15:38:58', '2025-11-02 15:38:58'),
(79, 81, 'GhostxㅤNexx', 'src/uploads/players/avatar_81_1762109227.jpg', 'src/uploads/players/screenshot_81_1762109227.jpg', '1301240087', NULL, '8573021427@naviaxis', '2025-11-02 18:47:07', '2025-11-02 18:47:07'),
(82, 84, 'नालायकबाबा3', 'src/uploads/players/avatar_84_1762241562.jpg', 'src/uploads/players/screenshot_84_1762241562.jpg', '4013513551', NULL, '9711377043@fam', '2025-11-04 07:32:42', '2025-11-04 07:32:42'),
(83, 86, 'devilplayer', 'src/uploads/players/avatar_86_1762255888.jpg', 'src/uploads/players/screenshot_86_1762255888.jpg', '2002548130', NULL, '', '2025-11-04 11:30:42', '2025-11-04 11:31:28'),
(85, 89, 'MW4x!NMvp?', 'src/uploads/players/avatar_89_1762320485.webp', 'src/uploads/players/screenshot_89_1762320485.jpg', '3373968942', NULL, '9205838531@fam', '2025-11-05 05:26:43', '2025-11-05 05:28:05'),
(87, 90, 'PR_PRANIT_YT', NULL, NULL, '12494414973', NULL, 'bhavnaraut508@gmail.com', '2025-11-05 09:27:54', '2025-11-05 09:27:54'),
(89, 92, 'One tap', NULL, NULL, '1551316963', NULL, '', '2025-11-05 09:57:28', '2025-11-05 09:57:28'),
(90, 93, 'BR KALYAN', NULL, NULL, '3974200716', NULL, '', '2025-11-05 10:23:27', '2025-11-05 10:23:27');

-- --------------------------------------------------------

--
-- Table structure for table `push_notification_tokens`
--

CREATE TABLE `push_notification_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(512) NOT NULL COMMENT 'FCM or device token',
  `device_type` enum('android','ios','web') NOT NULL DEFAULT 'web',
  `device_info` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `slot_no` int(11) NOT NULL DEFAULT 0,
  `team_name` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','paid','free') DEFAULT 'pending',
  `rank` int(11) DEFAULT NULL,
  `prize_won` decimal(10,2) DEFAULT 0.00,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_screenshot` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `prize_status` varchar(50) DEFAULT 'Not Won'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `tournament_id`, `player_id`, `slot_no`, `team_name`, `payment_status`, `rank`, `prize_won`, `joined_at`, `payment_method`, `transaction_id`, `payment_screenshot`, `payment_date`, `prize_status`) VALUES
(26, 1, 18, 1, 'Team Patel', '', NULL, 0.00, '2025-10-30 16:27:25', NULL, NULL, NULL, NULL, 'Not Won'),
(27, 1, 14, 2, 'Gangster squad 💪', '', NULL, 0.00, '2025-10-31 09:09:13', NULL, NULL, NULL, NULL, 'Not Won'),
(28, 1, 45, 3, '', '', NULL, 0.00, '2025-10-31 09:59:35', NULL, NULL, NULL, NULL, 'Not Won'),
(29, 1, 34, 4, 'Blizzzz Esp', '', NULL, 0.00, '2025-10-31 10:17:58', NULL, NULL, NULL, NULL, 'Not Won'),
(30, 1, 33, 5, '', '', NULL, 0.00, '2025-10-31 10:45:19', NULL, NULL, NULL, NULL, 'Not Won'),
(31, 1, 52, 6, 'PungiMan', '', NULL, 0.00, '2025-10-31 12:36:54', NULL, NULL, NULL, NULL, 'Not Won'),
(33, 1, 55, 8, 'D3VIL ESP', '', NULL, 0.00, '2025-10-31 13:31:28', NULL, NULL, NULL, NULL, 'Not Won'),
(34, 1, 48, 9, 'BADMOSS', '', NULL, 0.00, '2025-10-31 13:36:53', NULL, NULL, NULL, NULL, 'Not Won'),
(35, 1, 60, 10, 'Moro', '', NULL, 0.00, '2025-10-31 15:06:12', NULL, NULL, NULL, NULL, 'Not Won'),
(36, 1, 62, 11, 'Aura Farmers', '', NULL, 0.00, '2025-10-31 15:51:41', NULL, NULL, NULL, NULL, 'Not Won'),
(37, 1, 69, 12, 'Nexora Esports', '', NULL, 0.00, '2025-11-02 08:01:41', NULL, NULL, NULL, NULL, 'Not Won'),
(38, 1, 13, 13, 'DS-Esports', '', NULL, 0.00, '2025-11-02 08:06:46', NULL, NULL, NULL, NULL, 'Not Won'),
(39, 1, 6, 14, 'Team monster', '', NULL, 0.00, '2025-11-02 09:44:36', NULL, NULL, NULL, NULL, 'Not Won'),
(40, 1, 71, 15, 'TEAMᎪᴡᴀʀᴀ', '', NULL, 0.00, '2025-11-02 11:06:19', NULL, NULL, NULL, NULL, 'Not Won'),
(42, 1, 76, 17, 'Chauhan ji', '', NULL, 0.00, '2025-11-02 13:30:49', NULL, NULL, NULL, NULL, 'Not Won'),
(43, 1, 50, 18, 'D-IKBAL', '', NULL, 100.00, '2025-11-02 14:18:55', NULL, NULL, NULL, NULL, 'Not Won'),
(44, 1, 46, 19, 'The Legend\'s', '', NULL, 0.00, '2025-11-02 15:21:11', NULL, NULL, NULL, NULL, 'Not Won'),
(45, 1, 64, 20, 'Team dnzoo', '', NULL, 0.00, '2025-11-02 15:36:07', NULL, NULL, NULL, NULL, 'Not Won'),
(46, 2, 64, 1, 'DS ESPORTS', '', NULL, 0.00, '2025-11-03 12:58:36', NULL, NULL, NULL, NULL, 'Not Won'),
(47, 3, 91, 1, '', '', NULL, 0.00, '2025-11-05 09:23:08', NULL, NULL, NULL, NULL, 'Not Won'),
(48, 3, 33, 2, '', '', NULL, 0.00, '2025-11-05 09:23:47', NULL, NULL, NULL, NULL, 'Not Won'),
(49, 3, 82, 3, '', '', NULL, 0.00, '2025-11-05 09:39:59', NULL, NULL, NULL, NULL, 'Not Won'),
(50, 4, 90, 1, '', '', NULL, 0.00, '2025-11-05 09:46:19', NULL, NULL, NULL, NULL, 'Not Won'),
(51, 4, 82, 2, '', '', NULL, 0.00, '2025-11-05 09:47:16', NULL, NULL, NULL, NULL, 'Not Won'),
(52, 4, 92, 3, '', '', NULL, 0.00, '2025-11-05 09:57:48', NULL, NULL, NULL, NULL, 'Not Won');

-- --------------------------------------------------------

--
-- Table structure for table `registration_team_members`
--

CREATE TABLE `registration_team_members` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `member_name` varchar(100) NOT NULL,
  `member_uid` varchar(50) NOT NULL,
  `member_index` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_invitations`
--

CREATE TABLE `team_invitations` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `invited_user` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_registrations`
--

CREATE TABLE `team_registrations` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('captain','member') NOT NULL DEFAULT 'member',
  `position_index` tinyint(4) NOT NULL DEFAULT 1,
  `invited_by` int(11) DEFAULT NULL,
  `invitation_status` enum('pending','accepted','declined') DEFAULT 'accepted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('accepted','pending','rejected') DEFAULT 'accepted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_registrations`
--

INSERT INTO `team_registrations` (`id`, `registration_id`, `user_id`, `role`, `position_index`, `invited_by`, `invitation_status`, `created_at`, `updated_at`, `status`) VALUES
(1, 26, 18, 'captain', 1, NULL, 'accepted', '2025-10-30 16:27:25', '2025-10-30 16:27:25', 'accepted'),
(2, 26, 25, 'member', 2, NULL, 'accepted', '2025-10-30 16:27:25', '2025-10-30 16:27:25', 'accepted'),
(3, 27, 14, 'captain', 1, NULL, 'accepted', '2025-10-31 09:09:13', '2025-10-31 09:09:13', 'accepted'),
(4, 27, 40, 'member', 2, NULL, 'accepted', '2025-10-31 09:09:13', '2025-10-31 09:09:13', 'accepted'),
(5, 28, 45, 'captain', 1, NULL, 'accepted', '2025-10-31 09:59:35', '2025-10-31 09:59:35', 'accepted'),
(6, 28, 44, 'member', 2, NULL, 'accepted', '2025-10-31 09:59:35', '2025-10-31 09:59:35', 'accepted'),
(7, 29, 34, 'captain', 1, NULL, 'accepted', '2025-10-31 10:17:58', '2025-10-31 10:17:58', 'accepted'),
(8, 29, 47, 'member', 2, NULL, 'accepted', '2025-10-31 10:17:58', '2025-10-31 10:17:58', 'accepted'),
(9, 30, 33, 'captain', 1, NULL, 'accepted', '2025-10-31 10:45:19', '2025-10-31 10:45:19', 'accepted'),
(10, 30, 49, 'member', 2, NULL, 'accepted', '2025-10-31 10:45:19', '2025-10-31 10:45:19', 'accepted'),
(11, 31, 52, 'captain', 1, NULL, 'accepted', '2025-10-31 12:36:54', '2025-10-31 12:36:54', 'accepted'),
(12, 31, 54, 'member', 2, NULL, 'accepted', '2025-10-31 12:36:54', '2025-10-31 12:36:54', 'accepted'),
(15, 33, 55, 'captain', 1, NULL, 'accepted', '2025-10-31 13:31:28', '2025-10-31 13:31:28', 'accepted'),
(16, 33, 56, 'member', 2, NULL, 'accepted', '2025-10-31 13:31:28', '2025-10-31 13:31:28', 'accepted'),
(17, 34, 48, 'captain', 1, NULL, 'accepted', '2025-10-31 13:36:53', '2025-10-31 13:36:53', 'accepted'),
(18, 34, 57, 'member', 2, NULL, 'accepted', '2025-10-31 13:36:53', '2025-10-31 13:36:53', 'accepted'),
(19, 35, 60, 'captain', 1, NULL, 'accepted', '2025-10-31 15:06:12', '2025-10-31 15:06:12', 'accepted'),
(20, 35, 61, 'member', 2, NULL, 'accepted', '2025-10-31 15:06:12', '2025-10-31 15:06:12', 'accepted'),
(21, 36, 62, 'captain', 1, NULL, 'accepted', '2025-10-31 15:51:41', '2025-10-31 15:51:41', 'accepted'),
(22, 36, 41, 'member', 2, NULL, 'accepted', '2025-10-31 15:51:41', '2025-10-31 15:51:41', 'accepted'),
(23, 37, 69, 'captain', 1, NULL, 'accepted', '2025-11-02 08:01:41', '2025-11-02 08:01:41', 'accepted'),
(24, 37, 59, 'member', 2, NULL, 'accepted', '2025-11-02 08:01:41', '2025-11-02 08:01:41', 'accepted'),
(25, 38, 13, 'captain', 1, NULL, 'accepted', '2025-11-02 08:06:46', '2025-11-02 08:06:46', 'accepted'),
(26, 38, 15, 'member', 2, NULL, 'accepted', '2025-11-02 08:06:46', '2025-11-02 08:06:46', 'accepted'),
(27, 39, 6, 'captain', 1, NULL, 'accepted', '2025-11-02 09:44:36', '2025-11-02 09:44:36', 'accepted'),
(28, 39, 21, 'member', 2, NULL, 'accepted', '2025-11-02 09:44:36', '2025-11-02 09:44:36', 'accepted'),
(29, 40, 71, 'captain', 1, NULL, 'accepted', '2025-11-02 11:06:19', '2025-11-02 11:06:19', 'accepted'),
(30, 40, 72, 'member', 2, NULL, 'accepted', '2025-11-02 11:06:19', '2025-11-02 11:06:19', 'accepted'),
(33, 42, 76, 'captain', 1, NULL, 'accepted', '2025-11-02 13:30:49', '2025-11-02 13:30:49', 'accepted'),
(34, 42, 75, 'member', 2, NULL, 'accepted', '2025-11-02 13:30:49', '2025-11-02 13:30:49', 'accepted'),
(35, 43, 50, 'captain', 1, NULL, 'accepted', '2025-11-02 14:18:55', '2025-11-02 14:18:55', 'accepted'),
(36, 43, 77, 'member', 2, NULL, 'accepted', '2025-11-02 14:18:55', '2025-11-02 14:18:55', 'accepted'),
(37, 44, 46, 'captain', 1, NULL, 'accepted', '2025-11-02 15:21:11', '2025-11-02 15:21:11', 'accepted'),
(38, 44, 79, 'member', 2, NULL, 'accepted', '2025-11-02 15:21:11', '2025-11-02 15:21:11', 'accepted'),
(39, 45, 64, 'captain', 1, NULL, 'accepted', '2025-11-02 15:36:07', '2025-11-02 15:36:07', 'accepted'),
(40, 45, 27, 'member', 2, NULL, 'accepted', '2025-11-02 15:36:07', '2025-11-02 15:36:07', 'accepted'),
(41, 46, 64, 'captain', 1, NULL, 'accepted', '2025-11-03 12:58:36', '2025-11-03 12:58:36', 'accepted'),
(42, 46, 15, 'member', 2, NULL, 'accepted', '2025-11-03 12:58:36', '2025-11-03 12:58:36', 'accepted'),
(43, 46, 50, 'member', 3, NULL, 'accepted', '2025-11-03 12:58:36', '2025-11-03 12:58:36', 'accepted'),
(44, 46, 13, 'member', 4, NULL, 'accepted', '2025-11-03 12:58:36', '2025-11-03 12:58:36', 'accepted');

-- --------------------------------------------------------

--
-- Stand-in structure for view `team_roster_view`
-- (See below for the actual view)
--
CREATE TABLE `team_roster_view` (
`registration_id` int(11)
,`user_id` int(11)
,`role` enum('captain','member')
,`position_index` tinyint(4)
,`user_name` varchar(100)
,`email` varchar(100)
,`profile_verified` tinyint(1)
,`in_game_name` varchar(150)
,`game_uid` varchar(100)
,`avatar` varchar(255)
,`tournament_id` int(11)
,`slot_no` int(11)
,`team_name` varchar(100)
,`payment_status` enum('pending','paid','free')
,`joined_at` timestamp
,`tournament_title` varchar(150)
,`match_type` enum('solo','duo','squad','clash squad')
,`entry_fee` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `entry_fee` decimal(10,2) DEFAULT 0.00,
  `prize_pool` decimal(10,2) DEFAULT 0.00,
  `max_players` int(11) DEFAULT 100,
  `match_type` enum('solo','duo','squad','clash squad') DEFAULT 'squad',
  `map_name` varchar(100) DEFAULT 'Bermuda',
  `date` date NOT NULL,
  `time` time NOT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `room_password` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `banner` varchar(255) DEFAULT NULL,
  `number_of_matches` tinyint(4) DEFAULT 1 COMMENT '1-3 matches per tournament',
  `current_match_number` tinyint(4) DEFAULT 1 COMMENT 'Track which match is currently being played/updated',
  `points_distribution` text DEFAULT NULL COMMENT 'JSON: placement points config e.g. {1:12, 2:10, ...}',
  `kill_points` decimal(4,2) DEFAULT 1.00 COMMENT 'Points per kill (default 1)',
  `max_participants` int(11) DEFAULT NULL COMMENT 'Maximum number of players allowed',
  `current_participants` int(11) DEFAULT 0 COMMENT 'Current number of registered players',
  `auto_start` tinyint(1) DEFAULT 0 COMMENT 'Auto start when capacity reached',
  `reminder_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether 1-hour reminder was sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `title`, `description`, `entry_fee`, `prize_pool`, `max_players`, `match_type`, `map_name`, `date`, `time`, `room_id`, `room_password`, `created_by`, `status`, `created_at`, `banner`, `number_of_matches`, `current_match_number`, `points_distribution`, `kill_points`, `max_participants`, `current_participants`, `auto_start`, `reminder_sent`) VALUES
(1, 'Skynoxx Offical : DUO', '🔥 SKYNOXX Free Fire Tournament Rules\r\n\r\n*Each player must use their own Free Fire ID\r\n\r\n*Room Rules:\r\n\r\nNo hacking, teaming, or emulator use\r\n\r\nIf caught using hacks, your SKYNOXX login will be permanently banned\r\n\r\nIf any hack or disturbance is found during the match, don’t panic — the match will be re-created for everyone\r\n\r\nNo gun attributes unfair advantage\r\n\r\nNo re-entry after elimination\r\n\r\nToxic or abusive behavior leads to instant disqualification\r\n\r\n* Prize:\r\n\r\n🥇 1st Prize – ₹100\r\n\r\n* Organizer Decision:\r\n\r\nOrganizer’s decision is final in all disputes', 0.00, 100.00, 24, 'duo', 'Bermuda', '2025-11-02', '21:00:00', '70559470', '111', 22, 'cancelled', '2025-10-30 16:11:12', 'src/uploads/tournament_banners/banner_69038e2038459.png', 1, 1, NULL, 1.00, NULL, 0, 0, 0),
(2, 'SKYNOX TEAM TOURNAMENT', 'SKYNOX TEAM TOURNAMENT – RULES & POINTS\r\n🔸 MATCH DETAILS\r\n\r\nMode: Squad (4 Players per Team)\r\nTeams: 12 (48 Players Max)\r\nMaps: Bermuda • Purgatory • Kalahari\r\nEntry: Free\r\nPrize: ₹300 (1st Place)\r\n\r\n🔸 MAIN RULES\r\n\r\nRegister before match time – late entries not allowed.\r\nRoom ID & Password will be shared 15 mins before start.\r\nMobile only – no emulators or third-party apps.\r\nNo teaming, hacking, or glitch use – leads to disqualification.\r\nUse registered IGN only.\r\n\r\nOrganizer’s decision is final.\r\n\r\n🔸 POINT SYSTEM\r\nPosition	Points\r\n1st (Booyah)	20\r\n2nd	                16\r\n3rd	                14\r\n4th                	12\r\n5th	                10\r\n6th–8th           	8\r\n9th–10th     	5\r\n11th–12th	2\r\nKills	             +1 per Kill\r\n\r\n🏆 Final Rank = Placement + Kill Points\r\n\r\n🔸 PRIZE DISTRIBUTION\r\n\r\n🥇 1st Place Team: ₹300 (via UPI within 24 hrs)\r\n\r\nTie-breaker: Higher Kill Count Wins\r\n\r\n🔸 FAIR PLAY\r\n\r\nPlay fair & respect all teams.\r\n\r\nToxic or abusive behavior = instant ban.', 0.00, 300.00, 12, 'squad', 'Bermuda', '2025-11-09', '21:00:00', 'room_690887a1010a5', '59ded05e', 22, 'upcoming', '2025-11-03 10:44:49', 'src/uploads/tournament_banners/banner_690887a101164.png', 1, 1, NULL, 1.00, NULL, 0, 0, 0),
(3, 'cnnitinlive', 'custom on live - @cnnitinyt go and subscribe fast', 0.00, 0.00, 8, 'solo', 'Bermuda', '2025-11-05', '04:00:00', 'room_690b1bc7a4103', 'b1fcbc6c', 12, 'cancelled', '2025-11-05 09:04:41', NULL, 1, 1, NULL, 1.00, NULL, 0, 0, 0),
(4, 'CN NITIN LIVE', 'SUBSCRIBE THE CHANEL', 0.00, 0.00, 8, 'solo', 'Bermuda', '2025-11-08', '19:12:00', '109860178', '921', 12, 'cancelled', '2025-11-05 09:42:42', NULL, 1, 1, NULL, 1.00, NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tournament_results`
--

CREATE TABLE `tournament_results` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `position` int(11) NOT NULL COMMENT 'Final position/rank',
  `prize_amount` decimal(10,2) DEFAULT 0.00,
  `prize_distributed` tinyint(1) DEFAULT 0,
  `kills` int(11) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_status_log`
--

CREATE TABLE `tournament_status_log` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `old_status` enum('upcoming','ongoing','completed','cancelled') DEFAULT NULL,
  `new_status` enum('upcoming','ongoing','completed','cancelled') NOT NULL,
  `changed_by` int(11) DEFAULT NULL COMMENT 'User ID who made the change',
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_wallets`
--

CREATE TABLE `tournament_wallets` (
  `tournament_id` int(11) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `required_prize_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `prize_distributed_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('open','settled','cancelled') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournament_wallets`
--

INSERT INTO `tournament_wallets` (`tournament_id`, `balance`, `required_prize_total`, `prize_distributed_total`, `status`, `created_at`, `updated_at`) VALUES
(1, 0.00, 100.00, 100.00, 'open', '2025-10-30 16:11:12', '2025-11-02 16:00:03'),
(2, 300.00, 300.00, 0.00, 'open', '2025-11-03 10:44:49', '2025-11-03 12:27:20'),
(3, 0.00, 0.00, 0.00, 'open', '2025-11-05 09:04:41', '2025-11-05 09:04:41'),
(4, 0.00, 0.00, 0.00, 'open', '2025-11-05 09:42:42', '2025-11-05 09:42:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `role` enum('player','creator','admin') DEFAULT 'player',
  `password` varchar(255) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `profile_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `fcm_token` varchar(255) DEFAULT NULL,
  `fcm_token_updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `role`, `password`, `joined_at`, `wallet_balance`, `profile_verified`, `is_active`, `fcm_token`, `fcm_token_updated_at`) VALUES
(6, 'sanjiv Kumar', 'kushwahasanjiv01@gmail.com', '09981474023', 'player', '$2y$10$yAUpnAKCMD2YrmnicmHdSeBAgdDTJ29qFJNEjyY.rPTfE4EP/lpWO', '2025-10-30 08:46:30', 100.00, 1, 1, NULL, NULL),
(9, 'Admin User', 'skynoxx@admin', '9981474023', 'admin', '$2y$10$gM5maUXalUM6T/X/uyh7LeNXuYpyF/6QEZHsKyExpT2ZLWnCPJeeO', '2025-10-30 08:56:45', 0.00, 0, 1, NULL, NULL),
(10, 'Test Player', 'player@test.com', '8888888888', 'player', '$2y$10$lxpm2eMEmakaG14CkuzmwekJ4n9DRFqEE2WsLtdZWW16obkHKnpe6', '2025-10-30 09:09:14', 0.00, 0, 1, NULL, NULL),
(11, 'Harshit Malik', 'malikharshit812@gmail.com', '9258804657', 'creator', '$2y$10$tC96Xq4.cFzDQvM2HpvQ..rD7v01fL5TXU1CV5RVLbn3bTHf7C1l6', '2025-10-30 09:49:22', 0.00, 0, 1, NULL, NULL),
(12, 'nitin jaiswal', 'malikharshit674@gmail.com', '9258804657', 'creator', '$2y$10$EeRXaXIeitJq6aSFVnJlcewCbHi6QLKOvrpT5EoLJAQDGM46K9Dia', '2025-10-30 10:40:48', 0.00, 0, 1, NULL, NULL),
(13, 'Junaid', 'ja475747474@gmail.com', '7407471509', 'player', '$2y$10$/pLhB/lVV1HtVKDGen69dOD7ki6s8rbkCoON3TqdXE2gWsKGLx6Ga', '2025-10-30 10:57:10', 0.00, 1, 1, NULL, NULL),
(14, 'Deepak Mehra', 'dm7005417@gmail.com', '+917339914006', 'player', '$2y$10$yZ6siS7enlTiiOfLo/nIie9sxbzL81ysCGNKgnwICtWD6sXixO9C6', '2025-10-30 11:25:32', 0.00, 1, 1, NULL, NULL),
(15, 'Ved', 'lamehammer66@gmail.com', '6392134962', 'player', '$2y$10$02g8.VZ89ClyOk.5p3hiqu14fCHYbLAKIMcFbqh2ZteYnBmW50FYi', '2025-10-30 12:16:02', 0.00, 1, 1, NULL, NULL),
(18, 'Priyanshu Patel', 'papriyanshu3@gmail.com', '8349816222', 'player', '$2y$10$9UE6cuU5gjs3RtBdbXY.K.P7NmGGsEbuCml87yM6wGCqnBz9zG1re', '2025-10-30 12:58:31', 0.00, 1, 1, NULL, NULL),
(21, 'Soumadip Jana', 'soumodipjana0@gmail.com', '+917384617783', 'player', '$2y$10$DRGqfG4Pz1rjCHiJqGh9/.5L.RnPZHKjf8V6hcly41Em2uSAAWD4m', '2025-10-30 13:15:07', 0.00, 1, 1, NULL, NULL),
(22, 'sanjiv Kumar', 'gameshear09@gmail.com', '09981474023', 'creator', '$2y$10$7VB4DteoBJcauZEFD7jdPOnHNL5frryfgVlRIACI9KLHbGp9J2zHu', '2025-10-30 14:20:08', 100.00, 0, 1, NULL, NULL),
(23, 'Roshan ', 'gn152887@gmail.com', '9981992503', 'player', '$2y$10$06sMzdWrblianYsvVKuLSeQ2KX2fy8..uAS.cC7nuUCRwaWF.R2vC', '2025-10-30 15:01:11', 0.00, 0, 1, NULL, NULL),
(24, 'muh mei le', 'rohansingh6227@gmail.com', '09691478326', 'player', '$2y$10$0N4iEAD7AP1/V5W.nnFZE.VPqeNn8VCEMHjsXFbP4Mgdwf4eLBxha', '2025-10-30 16:12:52', 1000.00, 1, 1, NULL, NULL),
(25, 'Aman patel', 'amqnpatel2004@gmail.com', '9302411960', 'player', '$2y$10$wjxDV2P9ILSk2AhQPN/0XuafJmVilN1PsiTLkW4ieCD31EJbat30K', '2025-10-30 16:22:45', 0.00, 1, 1, NULL, NULL),
(26, 'CN NITIN YT', 'nj021366@gmail.com', '9918531637', 'player', '$2y$10$2eo2IhERbIB.jwh9zCdpYOJdmXtw9w2mC.TqQPZqigOYdbRq2UZSe', '2025-10-30 17:38:38', 0.00, 0, 1, NULL, NULL),
(27, 'DS JAMES !!', 'Aradhyasrivastava115599@gmail.com', '9506215955', 'player', '$2y$10$FWFLh61pLsaCjN79Iw61b.zly64L5u80cYlWLEWXBo7Lag0TuIsf6', '2025-10-31 04:39:47', 0.00, 1, 1, NULL, NULL),
(30, 'Sumit ', 'sumitsumitkumar95072@gmail.com', '8395077054', 'player', '$2y$10$NfhD7hocaUYDV7zXlFN8OeHby3kYPAI7cxDIdgl44Qa0qZtXotFP.', '2025-10-31 05:40:47', 0.00, 0, 1, NULL, NULL),
(33, 'Sumit', 'demono12282526@gmail.com', '8395077054', 'player', '$2y$10$eth3JDtz/bgJlBQ/KN5avueTQ7pODWSgu.qRuUGp9cZ2iolCPJ1aC', '2025-10-31 05:50:53', 0.00, 1, 1, NULL, NULL),
(34, 'Rohit singh', 'singh24092006rohit@gmail.com', '9109658367', 'player', '$2y$10$b5vDLEZPv9YLplwCYQWAxOfKEvtqptgX1OzfkCVQzBJTlD29hmRma', '2025-10-31 07:09:36', 0.00, 1, 1, NULL, NULL),
(35, 'Akshay', 'akshayagarwal8476@gmail.com', '+919829841450', 'player', '$2y$10$xAjOc3qtO8WsIOFu8k8Vge.jj0en7Q63MtskbeS9kraxcLeNhCVr6', '2025-10-31 08:00:51', 0.00, 1, 1, NULL, NULL),
(36, 'Bhav Sagar Kansotia', 'bhavsagarkansotia20003@gmail.com', '7568807705', 'player', '$2y$10$xhRe2hDkLoxAGCRo0ahp8euCLb8nFokk8PwnfgPNB9vopNruA7UOu', '2025-10-31 08:04:24', 0.00, 1, 1, NULL, NULL),
(38, 'Onik', 'onikrana10b@gmail.con', '8580842870', 'player', '$2y$10$svf93XIipQVKmwODVzZ8c.cIGXqsVCWXmnjeKf5nnylsX/WSpmaF.', '2025-10-31 08:21:22', 0.00, 0, 1, NULL, NULL),
(39, 'Onik', 'onikrana10b@gmail.com', '8580842870', 'player', '$2y$10$3d2a2rH9dmlfZQb3vfxfIO9D5aVzO4Gdc5xAspFnMghft7bp/b7sW', '2025-10-31 08:22:42', 0.00, 1, 1, NULL, NULL),
(40, 'Prem Mehra', 'premmehras005@gmail.com', '7297041772', 'player', '$2y$10$SGzCQtU.wqg5hhyixqa6Jenoi9pPHRmy4YNK.vZG/9jDt0sjM4Bpu', '2025-10-31 09:05:23', 0.00, 1, 1, NULL, NULL),
(41, 'Harsh', 'hc169720@gmail.com', '8839181938', 'player', '$2y$10$19ZRV.k6WHjToT95pNGSEeaRiunrpXgUssgkXtV2H54QIIwFBV4ka', '2025-10-31 09:28:07', 0.00, 1, 1, NULL, NULL),
(42, 'Rohit kumar', 'rkkushwaha181589@gmail.com', '8235175414', 'player', '$2y$10$1oKzey33rlp2.DaMsLlt5eIbP5oQ2aih9z/9usMDtLkwYxmXEoSVy', '2025-10-31 09:48:43', 0.00, 0, 1, NULL, NULL),
(43, 'Rohit Kumar', 'rkkuswaha181589@gmail.com', '8235175414', 'player', '$2y$10$b8oEaD1rxd4egIMJ9pKOHOLVDqWxwsM5iFiFxxkqrDhdXdOIitCHK', '2025-10-31 09:53:28', 0.00, 0, 1, NULL, NULL),
(44, 'Shivam Naik', 'shivamnaik2511@gmail.com', '7987276589', 'player', '$2y$10$86m8u2LEV7hphlMhbguWHuZHYAW1wlhTPrpCzh.FGodReENpIHhrC', '2025-10-31 09:54:46', 0.00, 1, 1, NULL, NULL),
(45, 'Pankaj Sahu', 'menusahu6666@gmail.com', '8815469712', 'player', '$2y$10$1bdQnPJgcMjf2w/PRuBYwuCXEAiCuF2QqJgZ6GP9UxxGDMo3/Uclm', '2025-10-31 09:56:20', 0.00, 1, 1, NULL, NULL),
(46, 'Rohit', 'gudiyadevi971705@gmail.com', '8826523637', 'player', '$2y$10$7ivy29xsC4Ats2gKl4S9uuAQphPcgrRRcbzxHn8y2.mLhky5Edlme', '2025-10-31 10:06:56', 0.00, 1, 1, NULL, NULL),
(47, 'Dev pandey', 'pandeyrekha206@gmail.com', '6260100504', 'player', '$2y$10$wXK19jN6MJeYpLyXOy6H4O5KrvMcl14NZSK3TzXlPmF7GY2RLQx8K', '2025-10-31 10:11:27', 0.00, 1, 1, NULL, NULL),
(48, 'Harsh Patel', 'harshpatel446446@gmail.com', '8770681731', 'player', '$2y$10$1yAhz6sdP/ij9t901O74Z.xVfJoLWQCi1fyi9E1hs7jm1xq94De3e', '2025-10-31 10:11:28', 0.00, 1, 1, NULL, NULL),
(49, 'Aadarsh Rajput', 'aadarshrajput677@gmail.com', '+919643084276', 'player', '$2y$10$q9dsXbIcjFtsTXSlQU4AYOnB6Xhm/t8DJQeLA0LIp9lvYABQPG3T.', '2025-10-31 10:25:22', 0.00, 1, 1, NULL, NULL),
(50, 'D-IKBAL', 'md4575025@gamail.com', '6203753945', 'player', '$2y$10$NZKFvVA1j1aW.XALizflPO39dmIxVuoH0xOHScExuXuxrcdgAkC3q', '2025-10-31 10:33:06', 0.00, 1, 1, NULL, NULL),
(51, 'Cg lazer', 'dklazerthakur@gmail.com', '8234044608', 'player', '$2y$10$9E4EFI/LWPNfHmIs7NmL5.m/yh8UinNhmWR6HSRzqnHnzGfDw.qKq', '2025-10-31 12:26:44', 0.00, 0, 1, NULL, NULL),
(52, 'Parme Gamer', 'pungiman18@gmail.com', '+919098299441', 'player', '$2y$10$jmKzBQ1fg9goUr8l.9qDmuByAjd4fPohG2yxsOUHkziE7HqOt4G5a', '2025-10-31 12:26:51', 0.00, 1, 1, NULL, NULL),
(53, 'UN KRUNAL', 'krunalmungra2008@gmail.com', '9428319808', 'player', '$2y$10$C5jzDPCTSmid0MDbDo.i8eXnrKzGkzW8H/Rowunq7WG4.O9f5e6WC', '2025-10-31 12:28:17', 0.00, 0, 1, NULL, NULL),
(54, 'TARGET>', 'cgketura21@gmail.com', '8234044608', 'player', '$2y$10$hbwqvYyp0PRVSlUHHS2peOWmVbFi68DWTn01o/xM5EXzsVCcB9jVy', '2025-10-31 12:34:06', 0.00, 1, 1, NULL, NULL),
(55, 'Sujal Gulhane', 'gulhanesujal712@gmail.com', '+917218205106', 'player', '$2y$10$IqZ79ZQKlPMly.VB4Tp/MO984yoXxifURA6dZVIDim6o.6BG0QuRu', '2025-10-31 13:12:03', 0.00, 1, 1, NULL, NULL),
(56, 'aayush Walke', 'ayush71611@gmail.com', '+1918830633563', 'player', '$2y$10$sHup.8FtPANxvUrPlJKTfOOsZuJQEpxoO4qfnfxcrYLvs/9JJsp0a', '2025-10-31 13:29:56', 0.00, 1, 1, NULL, NULL),
(57, 'Piyush Agrawal', 'patelji1705@gmail.com', '08770681731', 'player', '$2y$10$04EhCzFI1AoGWq7uv/rcKOM9EmClYlwP5ayZEXj5fRrRkuzhsbIcu', '2025-10-31 13:32:41', 0.00, 1, 1, NULL, NULL),
(58, 'Sahil sahani', 'sahilsahani8211@gmail.com', '7972704750', 'player', '$2y$10$ZiBwIyMKx/BfRPlJUtqXTO.PC4mPOQb3Za2XVy/Mbgi8rNOuupW2C', '2025-10-31 13:42:39', 0.00, 0, 1, NULL, NULL),
(59, 'Ishan Singh', 'ishooogaming9@gmail.com', '8770543764', 'player', '$2y$10$seZcR8YaB3KvBc9IO8nUBOfuvmOV../UjiC6smfN3zdLZX8fXtXZi', '2025-10-31 14:44:03', 0.00, 1, 1, NULL, NULL),
(60, 'Dg moro', 'vikashvlogind444@gmail.com', '8219846382', 'player', '$2y$10$4r0bnxXxrN0mjbUcdRal/OVAEOg0GXetLhEZZ2ieASHEl3bM5f4l2', '2025-10-31 14:49:11', 0.00, 1, 1, NULL, NULL),
(61, 'Vivek Kumar', 'kvivek3745@gmail.com', '8219647621', 'player', '$2y$10$dGYaH2jmQ.LaarlnzK8Pw.Oxs1eYxljkzPX318B4lt3rv9xHUgSxG', '2025-10-31 15:01:26', 0.00, 1, 1, NULL, NULL),
(62, 'Raj', 'yraj85517@gmail.com', '8349946545', 'player', '$2y$10$Ke.LUVZQV.xPIRkTkPyadurN.FurDXB0JqhFBc5delNPSNCee8VV6', '2025-10-31 15:39:02', 0.00, 1, 1, NULL, NULL),
(63, 'Devdhari', 'devdharirajwade928@gmail.com', '+7489570230', 'player', '$2y$10$ZV3E7Hkv1uNF2anU3ZwvXO/Kk9WbTkOpe8GS8tDoLbQN2hhH/Q7vK', '2025-11-01 01:13:14', 0.00, 1, 1, NULL, NULL),
(64, 'Dnzzo 4 x', 'nabapratimbhuyan@gmail.com', '06002433593', 'player', '$2y$10$nqwWuCBXsNY6JSLOIQTUzuhBP13.lIqHQ3x1e/VwRUabu5OQnBkX6', '2025-11-01 03:53:44', 0.00, 1, 1, NULL, NULL),
(65, 'Siddharth Prasad', 'prasadsiddharth34171@gmail.com', '7566124334', 'player', '$2y$10$nqyXoHCj2.eCFw5gVKIfV.9btwdoKWizjo9D5GN3G7CL3xVFc4v8a', '2025-11-01 08:22:29', 0.00, 0, 1, NULL, NULL),
(66, 'Sujit Sarkar', 'sujitsarkar741414@gmail.com', '+916295741414', 'player', '$2y$10$xTbWEwYElLbBee.OVVV2Cufv97HZ70taZedaGy.wWuCc8gp5QHVrK', '2025-11-01 09:09:16', 0.00, 0, 1, NULL, NULL),
(67, 'Rohit Kumar', 'laukhanvishnath@gmail.com', '8235175414', 'player', '$2y$10$u0eO2Qfvb0HfQxWYh6WLpeAzg/.iqR4kdEhLOD27Qm01VhN1J9OGm', '2025-11-01 13:44:41', 0.00, 0, 1, NULL, NULL),
(68, 'LionEditzzzz', 'growingytyt@gmail.com', '8101580422', 'player', '$2y$10$ndInpNrawrmcSMCsdZa91OUz7j6wXCv.Lm7pDfShebj06Ob4uTB7u', '2025-11-02 06:13:19', 0.00, 0, 1, NULL, NULL),
(69, 'LionEditzzzz', 'brazilianacc53@gmail.com', '8101580422', 'player', '$2y$10$xbDFiV/234p6QSul3R5kCe9Y70NYpJeJReuyavzpSnQ/lBSrh.dbm', '2025-11-02 06:15:57', 0.00, 1, 1, NULL, NULL),
(70, 'Ishan Singh', 'nsingh39695@gmail.com', '8770543764', 'player', '$2y$10$6a8L4qBU7IH6L7IcVuByf.cgv6OI2UGxGoHfNxBcO/gFb6/6N3aj2', '2025-11-02 06:51:28', 0.00, 1, 1, NULL, NULL),
(71, 'Shubham Kumar', 'shubhamkumaranurudhsingh@gmail.com', '7065539919', 'player', '$2y$10$i5IA2w.X35S5tsnyO0kuaOWV4A2vRF0iytiG6iySMoOSO1rwu/WYm', '2025-11-02 10:33:10', 0.00, 1, 1, NULL, NULL),
(72, 'Rishi Kushwah', 'okr6570@gmail.com', '+919669826958', 'player', '$2y$10$UPcuUbGDofxbpshIoJlRbei/MAX3Kd/gxkdRr2OMfYKwPteQ1Q8fW', '2025-11-02 10:42:40', 0.00, 1, 1, NULL, NULL),
(73, 'KUSH', 'kushsinghrajput748@gmail.com', '+916269689651', 'player', '$2y$10$8W3vIbk6FX3U0Ho.nrtNeODlkzxeBAwVoHtKFpE6Sr3X0oTiP0yA2', '2025-11-02 11:40:57', 0.00, 1, 1, NULL, NULL),
(74, 'Vanisher', 'vanisher1530@gmail.com', '9399150941', 'player', '$2y$10$ghCNkkfz2NzV.Fe8vI4HzuD/WRRPrxFtYoOczNB/YQuF6BFwv7YXW', '2025-11-02 12:13:45', 0.00, 1, 1, NULL, NULL),
(75, 'Shivam', 'shivamkumarkartikkumar@gmail.com', '7011050998', 'player', '$2y$10$TyaiSEmmx6yg56UMynaXUOOuNun7ZB40csp73FS9uAeaxFQxHapwK', '2025-11-02 12:18:11', 0.00, 1, 1, NULL, NULL),
(76, 'Thakur', 'anandsinghshr@gmail.com', '9644676561', 'player', '$2y$10$52zZ40dAq5QkM2PaHfa14uax1Z7KDYfJGEEQ/X/.esMGftE2309EC', '2025-11-02 13:16:00', 0.00, 1, 1, NULL, NULL),
(77, 'Drakan', 'shahilsikandar06@gmail.com', '8527630310', 'player', '$2y$10$a6SAseP3J6hJ8ERqpqa1CuFTxSpusPSwzpa/NecpJTcNSmZTxMIv6', '2025-11-02 14:06:42', 0.00, 1, 1, NULL, NULL),
(78, 'Arjun Chanak', 'chanakarjun61@gmail.com', '+917029120945', 'player', '$2y$10$9dy1dTKC3pC2evoKoGdpTepMDIrOUQFOZ5NXEIfiMqk2VRmr7pSI.', '2025-11-02 14:31:10', 0.00, 1, 1, NULL, NULL),
(79, 'Karan', 'karansingh20042025@gmail.com', '9266445767', 'player', '$2y$10$k4T3wRzSDiyYYAa4WZF9M.oT2aSDRbvVKhFNIg6OjH3Iu8C76QYSi', '2025-11-02 15:12:21', 0.00, 1, 1, NULL, NULL),
(80, 'Suraj', 'surajvarma625347@gmail.com', '9350370985', 'player', '$2y$10$1ys0VO.0PsjGF9Cz0XC7Gu7tmgb8PBj8nIXl1M4u7w5hpBDnFcMC.', '2025-11-02 15:28:09', 0.00, 1, 1, NULL, NULL),
(81, 'tarungamer', 'tarunkumar91199@gmail.com', '8573021427', 'player', '$2y$10$RBFstvo2.mW/kyKxKyKXFey23iskxj.3zc7JxOM1YZwU4C2a16Pj.', '2025-11-02 15:28:21', 0.00, 1, 1, NULL, NULL),
(82, 'Devansh_bhai', 'goeldevansh1234@gmail.com', '9412476197', 'player', '$2y$10$t7zyxSIYxBPTbEJNq7YdhOXa8aMatPDj0xGTQ54fGhx3IUilAsGyG', '2025-11-02 15:32:00', 0.00, 1, 1, NULL, NULL),
(83, 'Subha Jana', 'janamadhumita464@gmail.com', '9382850945', 'player', '$2y$10$hs4nxGX16WEMxgOK3WO0s.Z/rCe4R/eNZSvH/oTtaKFjhn7NAXDJa', '2025-11-02 15:41:35', 0.00, 0, 1, NULL, NULL),
(84, 'Shahid', 'khan706199@gmail.com', '9711377043', 'player', '$2y$10$HCjDvtlztAj.rOaAM2N6hebGGsSiqhP9xl8iyFU03wrbTd.iQL9CK', '2025-11-04 07:27:08', 0.00, 1, 1, NULL, NULL),
(85, 'Akash Rajput', 'akashkoli2525tf@gmail.com', '9289830743', 'player', '$2y$10$5XHgX9kp3B3OGcKaIgOlUeieFm237umpPyKeyMnlkqmDvBV9mL4j.', '2025-11-04 07:39:37', 0.00, 0, 1, NULL, NULL),
(86, 'devilplayer', 'deepsarkar2345@gmail.com', '9770642885', 'player', '$2y$10$eA2AmV5UdAFcl4yjXsE3beKIPbYun1/WmnCypYgybnRioil8lIX3q', '2025-11-04 11:23:03', 0.00, 1, 1, NULL, NULL),
(88, 'rupan ff', 'srupan431@gmail.com', '6003546998', 'creator', '$2y$10$.tFqPDvIkE2xic0rKRtdJOXNaLdg4s3aEgkmMH15hzJLkyA3/YLJu', '2025-11-04 17:50:50', 0.00, 0, 1, NULL, NULL),
(89, '_god_zx9', 'bingo03z123@gmail.com', '9205838531', 'player', '$2y$10$v1YTw6Ck1uybQTZPB3Jtj.ZanDdocawxvBwsEHHuokKjBRwCPht5q', '2025-11-05 05:20:25', 0.00, 1, 1, NULL, NULL),
(90, 'PR_YT', 'bhavnaraut508@gmail.com', '+918770341841', 'player', '$2y$10$.eF40vM9RiIOnay0hsl4/eydyzvKvaGJzB03cCF5ukw18/ZikmS0K', '2025-11-05 09:19:22', 0.00, 1, 1, NULL, NULL),
(91, 'Navraj', 'goldygeeta@gmail.com', '9634092094', 'player', '$2y$10$umMkWPBP./6VpuDOemMTYOFZdEVobZGm4mLVPkXaG1QC/U8ojwGWe', '2025-11-05 09:19:33', 0.00, 0, 1, NULL, NULL),
(92, 'Harshit Malik', 'vanshmalik5734@gmail.com', '9258804657', 'player', '$2y$10$mVa7ObhrbkDBzxjXE84GGeAkLWO5YRggYpl7PR/3ULYyMMbhuqCru', '2025-11-05 09:56:05', 0.00, 1, 1, NULL, NULL),
(93, 'Kalyan Dutta', 'kalyandatto@gmail.com', '+919007852595', 'player', '$2y$10$mdoAjTSp56PMX4Ey1E8x8eGWUtToYRV/hxyXZuZ8vHPeiuvDXaj5C', '2025-11-05 10:16:41', 0.00, 1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_topup_requests`
--

CREATE TABLE `wallet_topup_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `upi_reference` varchar(100) DEFAULT NULL,
  `screenshot_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','deduct','transfer','prize','withdraw') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional transaction data' CHECK (json_valid(`metadata`)),
  `notification_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether notification was sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `type`, `amount`, `related_user_id`, `tournament_id`, `description`, `status`, `created_at`, `metadata`, `notification_sent`) VALUES
(1, 6, '', 100.00, NULL, NULL, 'bhikh me rakh', 'completed', '2025-10-30 09:01:49', NULL, 0),
(2, 24, '', 1000.00, NULL, NULL, 'daan', 'completed', '2025-11-01 10:06:47', NULL, 0),
(3, 22, '', 500.00, NULL, NULL, '5', 'completed', '2025-11-02 15:57:02', NULL, 0),
(4, 22, '', 100.00, NULL, 1, 'Top-up tournament wallet', 'completed', '2025-11-02 15:59:53', NULL, 0),
(5, 50, '', 100.00, NULL, 1, 'Prize won in tournament', 'completed', '2025-11-02 16:00:03', NULL, 0),
(6, 50, '', 100.00, NULL, NULL, 'Wallet Withdrawal Request (pending approval)', 'completed', '2025-11-02 16:02:56', NULL, 0),
(7, 50, 'withdraw', 100.00, NULL, NULL, 'Withdrawal approved by admin', 'completed', '2025-11-02 16:06:40', NULL, 0),
(8, 22, '', 300.00, NULL, 2, 'Top-up tournament wallet', 'completed', '2025-11-03 12:27:20', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `creator_id`, `amount`, `upi_id`, `qr_code`, `status`, `requested_at`) VALUES
(1, 50, 100.00, '6203753945@ybl', NULL, 'approved', '2025-11-02 16:02:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `admin_email` (`admin_email`),
  ADD KEY `idx_admin_email` (`admin_email`),
  ADD KEY `idx_admin_user_id` (`user_id`),
  ADD KEY `idx_admin_is_active` (`is_active`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `creators`
--
ALTER TABLE `creators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_game_uid` (`game_uid`);

--
-- Indexes for table `match_results`
--
ALTER TABLE `match_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_match_registration` (`tournament_id`,`match_number`,`registration_id`),
  ADD KEY `idx_tournament_match` (`tournament_id`,`match_number`),
  ADD KEY `idx_registration` (`registration_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_match_results_total_points` (`total_points` DESC);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_audience` (`audience`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_prefs` (`user_id`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tournament_id` (`tournament_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_registration` (`registration_id`),
  ADD KEY `idx_player` (`player_id`),
  ADD KEY `idx_tournament` (`tournament_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `players_profile`
--
ALTER TABLE `players_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_game_uid` (`game_uid`),
  ADD KEY `idx_in_game_name` (`in_game_name`);

--
-- Indexes for table `push_notification_tokens`
--
ALTER TABLE `push_notification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_device` (`user_id`,`token`(255)),
  ADD KEY `idx_active_tokens` (`is_active`,`user_id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tournament_player` (`tournament_id`,`player_id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_prize_status` (`prize_status`),
  ADD KEY `idx_team_name` (`team_name`);

--
-- Indexes for table `registration_team_members`
--
ALTER TABLE `registration_team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_invitation` (`tournament_id`,`invited_user`,`registration_id`),
  ADD KEY `invited_by` (`invited_by`),
  ADD KEY `invited_user` (`invited_user`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `team_registrations`
--
ALTER TABLE `team_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration_user` (`registration_id`,`user_id`),
  ADD KEY `invited_by` (`invited_by`),
  ADD KEY `idx_registration` (`registration_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `tournament_results`
--
ALTER TABLE `tournament_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tournament_player` (`tournament_id`,`player_id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `idx_tournament_position` (`tournament_id`,`position`);

--
-- Indexes for table `tournament_status_log`
--
ALTER TABLE `tournament_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_tournament_status` (`tournament_id`,`changed_at`);

--
-- Indexes for table `tournament_wallets`
--
ALTER TABLE `tournament_wallets`
  ADD PRIMARY KEY (`tournament_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wallet_topup_requests`
--
ALTER TABLE `wallet_topup_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `related_user_id` (`related_user_id`),
  ADD KEY `tournament_id` (`tournament_id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `creators`
--
ALTER TABLE `creators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `match_results`
--
ALTER TABLE `match_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players_profile`
--
ALTER TABLE `players_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `push_notification_tokens`
--
ALTER TABLE `push_notification_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `registration_team_members`
--
ALTER TABLE `registration_team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `team_invitations`
--
ALTER TABLE `team_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_registrations`
--
ALTER TABLE `team_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tournament_results`
--
ALTER TABLE `tournament_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_status_log`
--
ALTER TABLE `tournament_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `wallet_topup_requests`
--
ALTER TABLE `wallet_topup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Structure for view `team_roster_view`
--
DROP TABLE IF EXISTS `team_roster_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u938578626_skynoxx`@`127.0.0.1` SQL SECURITY DEFINER VIEW `team_roster_view`  AS SELECT `tr`.`registration_id` AS `registration_id`, `tr`.`user_id` AS `user_id`, `tr`.`role` AS `role`, `tr`.`position_index` AS `position_index`, `u`.`name` AS `user_name`, `u`.`email` AS `email`, `u`.`profile_verified` AS `profile_verified`, `pp`.`in_game_name` AS `in_game_name`, `pp`.`game_uid` AS `game_uid`, `pp`.`avatar` AS `avatar`, `r`.`tournament_id` AS `tournament_id`, `r`.`slot_no` AS `slot_no`, `r`.`team_name` AS `team_name`, `r`.`payment_status` AS `payment_status`, `r`.`joined_at` AS `joined_at`, `t`.`title` AS `tournament_title`, `t`.`match_type` AS `match_type`, `t`.`entry_fee` AS `entry_fee` FROM ((((`team_registrations` `tr` join `users` `u` on(`tr`.`user_id` = `u`.`id`)) left join `players_profile` `pp` on(`tr`.`user_id` = `pp`.`user_id`)) join `registrations` `r` on(`tr`.`registration_id` = `r`.`id`)) join `tournaments` `t` on(`r`.`tournament_id` = `t`.`id`)) ORDER BY `tr`.`registration_id` ASC, `tr`.`role` DESC, `tr`.`position_index` ASC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `creators`
--
ALTER TABLE `creators`
  ADD CONSTRAINT `creators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `match_results`
--
ALTER TABLE `match_results`
  ADD CONSTRAINT `match_results_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_results_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_results_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD CONSTRAINT `fk_nr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `push_notification_tokens`
--
ALTER TABLE `push_notification_tokens`
  ADD CONSTRAINT `push_notification_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registration_team_members`
--
ALTER TABLE `registration_team_members`
  ADD CONSTRAINT `registration_team_members_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD CONSTRAINT `team_invitations_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_invitations_ibfk_2` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_invitations_ibfk_3` FOREIGN KEY (`invited_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_invitations_ibfk_4` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_registrations`
--
ALTER TABLE `team_registrations`
  ADD CONSTRAINT `team_registrations_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_registrations_ibfk_3` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_results`
--
ALTER TABLE `tournament_results`
  ADD CONSTRAINT `tournament_results_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_results_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_status_log`
--
ALTER TABLE `tournament_status_log`
  ADD CONSTRAINT `tournament_status_log_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_status_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tournament_wallets`
--
ALTER TABLE `tournament_wallets`
  ADD CONSTRAINT `fk_tw_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_topup_requests`
--
ALTER TABLE `wallet_topup_requests`
  ADD CONSTRAINT `fk_wtr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`related_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wallet_transactions_ibfk_3` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`);

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
