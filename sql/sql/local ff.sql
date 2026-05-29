-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 11:47 AM
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
-- Database: `ff`
--

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
(1, 8, 'Admin User', 'admin@freefire.com', '$2y$10$lJ9ulmDSMrLM8hfm2oAEUu8Of9UQh843yNfippHV/FzuY3gxIMnBa', '9999999999', 'super_admin', 1, '2025-11-02 18:39:24', '2025-10-23 08:05:17', '2025-11-02 18:39:24');

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
(4, 1, 'sanjiv kumar', '09981474023', 'kushwahasanjiv01@gmial.com', '123', '', '', NULL, NULL, '2025-10-30 17:45:57', '2025-10-30 17:45:57');

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
  `total_points` decimal(10,2) GENERATED ALWAYS AS (`placement_points` + `kill_points` + `bonus_points`) STORED,
  `updated_by` int(11) DEFAULT NULL COMMENT 'Creator who entered stats',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores match-by-match statistics for tournaments';

--
-- Dumping data for table `match_results`
--

INSERT INTO `match_results` (`id`, `tournament_id`, `match_number`, `registration_id`, `placement`, `kills`, `damage`, `survival_time`, `placement_points`, `kill_points`, `bhooya_points`, `bonus_points`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 69, 1, 47, 1, 50, 0, 0, 12.00, 50.00, 50.00, 0.00, 1, '2025-11-05 09:44:36', '2025-11-05 10:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'tournament_created',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `invitation_id` int(11) DEFAULT NULL,
  `audience` enum('all','players','creators','user') NOT NULL DEFAULT 'players',
  `audience_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `title`, `message`, `tournament_id`, `invitation_id`, `audience`, `audience_user_id`, `created_at`) VALUES
(35, 'tournament_created', 'New Tournament: 123456789', 'Creator posted a new tournament on 2025-11-05 17:37 | Entry ₹0.00 | Prize ₹100.00', 62, NULL, 'players', NULL, '2025-10-30 18:07:54'),
(36, 'team_added', 'Added to Tournament Team', 'A player has added you to the team \'DS ESP\' for the tournament \'123456789\'. You can leave the team anytime from your dashboard.', 62, NULL, 'user', 14, '2025-10-30 18:08:16'),
(37, 'team_disbanded', 'Team Disbanded', 'A player has left the team for tournament \'123456789\'. The entire team registration has been cancelled.', 62, NULL, 'user', 17, '2025-10-30 18:09:28'),
(38, 'team_added', 'Added to Tournament Team', 'A player has added you to the team \'DS ESP\' for the tournament \'123456789\'. You can leave the team anytime from your dashboard.', 62, NULL, 'user', 17, '2025-10-30 18:31:19'),
(39, 'team_disbanded', 'Team Disbanded', 'A player has left the team for tournament \'123456789\'. The entire team registration has been cancelled.', 62, NULL, 'user', 14, '2025-10-30 18:32:09'),
(40, 'team_added', 'Added to Tournament Team', 'A player has added you to the team \'DS ESP\' for the tournament \'123456789\'. You can leave the team anytime from your dashboard.', 62, NULL, 'user', 14, '2025-10-31 05:58:48'),
(41, 'team_disbanded', 'Team Disbanded', 'A player has left the team for tournament \'123456789\'. The entire team registration has been cancelled.', 62, NULL, 'user', 14, '2025-10-31 09:02:38'),
(42, 'wallet_topup_approved', 'Wallet Top-up Approved', 'Your manual wallet top-up of ₹500.00 has been approved and credited.', NULL, NULL, 'user', 17, '2025-11-02 18:41:25'),
(43, 'team_added', 'Added to Tournament Team', 'A player has added you to the team \'DS ESP\' for the tournament \'123456789\'. You can leave the team anytime from your dashboard.', 62, NULL, 'user', 14, '2025-11-03 13:01:38'),
(44, 'tournament_created', 'New Tournament: solo', 'Creator posted a new tournament on 2025-11-25 22:35 | Entry ₹0.00 | Prize ₹0.00', 63, NULL, 'players', NULL, '2025-11-03 13:06:59'),
(45, 'tournament_created', 'New Tournament: 11', 'Creator posted a new tournament on 2025-11-18 23:40 | Entry ₹0.00 | Prize ₹0.00', 64, NULL, 'players', NULL, '2025-11-03 13:10:20'),
(46, 'tournament_created', 'New Tournament: dfbfgbtfg', 'Creator posted a new tournament on 2025-11-19 22:56 | Entry ₹0.00 | Prize ₹0.00', 65, NULL, 'players', NULL, '2025-11-03 13:26:24'),
(47, 'tournament_created', 'New Tournament: 1st', 'Creator posted a new tournament on 2025-11-17 02:44 | Entry ₹0.00 | Prize ₹0.00', 66, NULL, 'players', NULL, '2025-11-04 04:14:41'),
(48, 'tournament_created', 'New Tournament: rhntyn5yrn6', 'Creator posted a new tournament on 2025-11-09 15:09 | Entry ₹20.00 | Prize ₹50.00', 67, NULL, 'players', NULL, '2025-11-04 17:41:09'),
(49, 'tournament_created', 'New Tournament: ldfjbklhmtnnukmjmnrmui', 'Creator posted a new tournament on 2025-11-24 13:31 | Entry ₹0.00 | Prize ₹0.00', 68, NULL, 'players', NULL, '2025-11-04 18:01:09'),
(50, 'team_added', 'Added to Tournament Team', 'A player has added you to the team \'DS ESP\' for the tournament \'ldfjbklhmtnnukmjmnrmui\'. You can leave the team anytime from your dashboard.', 68, NULL, 'user', 14, '2025-11-04 18:02:20'),
(51, 'tournament_created', 'New Tournament: fh', 'Creator posted a new tournament on 2025-11-18 17:35 | Entry ₹0.00 | Prize ₹0.00', 69, NULL, 'players', NULL, '2025-11-05 09:05:20');

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
(1, '2025-10-30 22:53:48', '2025-10-30 17:23:48'),
(14, '2025-11-03 18:57:44', '2025-11-03 13:27:44'),
(17, '2025-11-05 14:35:41', '2025-11-05 09:05:41');

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
(1, 1, 'DS SKYNOXX !!', 'src/uploads/players/avatar_1_1761667196.png', 'uploads/players/screenshot_1_1761297337.png', '1789721227', NULL, '9981474023', '2025-10-24 09:15:37', '2025-10-28 15:59:56'),
(3, 14, 'DS rohan !!', 'src/uploads/players/avatar_14_1761559462.jpg', 'src/uploads/players/screenshot_14_1761559462.png', '1789721228', NULL, '9907260511', '2025-10-27 09:56:04', '2025-10-27 10:04:22'),
(8, 17, 'DS SKYNOXX !!', 'src/uploads/players/avatar_17_1761847544.jpg', NULL, '1789721227', NULL, '9981474023-2@ybl', '2025-10-30 18:05:28', '2025-10-30 18:05:44');

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
(17, 20, 1, 1, NULL, '', NULL, 0.00, '2025-10-26 18:18:53', NULL, NULL, NULL, NULL, 'Not Won'),
(18, 21, 1, 1, NULL, '', NULL, 10.00, '2025-10-26 19:12:08', NULL, NULL, NULL, NULL, 'Not Won'),
(19, 22, 1, 1, NULL, '', NULL, 10.00, '2025-10-27 04:56:43', NULL, NULL, NULL, NULL, 'Not Won'),
(20, 23, 1, 1, 'DS ESP', '', NULL, 0.00, '2025-10-27 10:21:10', NULL, NULL, NULL, NULL, 'Not Won'),
(21, 24, 1, 1, 'DS ESP', '', NULL, 0.00, '2025-10-27 10:35:37', NULL, NULL, NULL, NULL, 'Not Won'),
(22, 25, 1, 1, '', '', NULL, 20.00, '2025-10-27 10:47:51', NULL, NULL, NULL, NULL, 'Not Won'),
(23, 26, 1, 1, '', '', NULL, 100.00, '2025-10-27 14:08:44', NULL, NULL, NULL, NULL, 'Not Won'),
(24, 27, 1, 1, 'DS ESP', '', NULL, 50.00, '2025-10-27 14:26:20', NULL, NULL, NULL, NULL, 'Not Won'),
(43, 66, 17, 1, '', '', NULL, 0.00, '2025-11-04 04:15:03', NULL, NULL, NULL, NULL, 'Not Won'),
(44, 66, 14, 2, '', '', NULL, 0.00, '2025-11-04 04:15:46', NULL, NULL, NULL, NULL, 'Not Won'),
(45, 67, 17, 1, '', '', NULL, 50.00, '2025-11-04 17:53:32', NULL, NULL, NULL, NULL, 'Not Won'),
(46, 68, 17, 1, 'DS ESP', '', NULL, 0.00, '2025-11-04 18:02:20', NULL, NULL, NULL, NULL, 'Not Won'),
(47, 69, 17, 1, '', '', NULL, 0.00, '2025-11-05 09:05:37', NULL, NULL, NULL, NULL, 'Not Won');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('accepted','pending','rejected') NOT NULL DEFAULT 'pending',
  `invited_by` int(11) DEFAULT NULL,
  `invitation_status` enum('pending','accepted','declined') DEFAULT 'accepted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_registrations`
--

INSERT INTO `team_registrations` (`id`, `registration_id`, `user_id`, `role`, `position_index`, `status`, `invited_by`, `invitation_status`, `created_at`, `updated_at`) VALUES
(1, 20, 1, 'captain', 1, 'accepted', NULL, 'accepted', '2025-10-27 10:21:10', '2025-10-30 17:03:35'),
(2, 20, 14, 'member', 2, 'accepted', NULL, 'accepted', '2025-10-27 10:21:10', '2025-10-30 17:03:35'),
(3, 21, 1, 'captain', 1, 'accepted', NULL, 'accepted', '2025-10-27 10:35:37', '2025-10-30 17:03:35'),
(4, 21, 14, 'member', 2, 'accepted', NULL, 'accepted', '2025-10-27 10:35:37', '2025-10-30 17:03:35'),
(5, 24, 1, 'captain', 1, 'accepted', NULL, 'accepted', '2025-10-27 14:26:20', '2025-10-30 17:03:35'),
(6, 24, 14, 'member', 2, 'accepted', NULL, 'accepted', '2025-10-27 14:26:20', '2025-10-30 17:03:35'),
(25, 46, 17, 'captain', 1, 'accepted', NULL, 'accepted', '2025-11-04 18:02:20', '2025-11-04 18:02:20'),
(26, 46, 14, 'member', 2, 'accepted', NULL, 'accepted', '2025-11-04 18:02:20', '2025-11-04 18:02:20');

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
  `kill_points` decimal(4,2) DEFAULT 1.00 COMMENT 'Points per kill (default 1)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `title`, `description`, `entry_fee`, `prize_pool`, `max_players`, `match_type`, `map_name`, `date`, `time`, `room_id`, `room_password`, `created_by`, `status`, `created_at`, `banner`, `number_of_matches`, `current_match_number`, `points_distribution`, `kill_points`) VALUES
(66, '1st', 'aspfew', 0.00, 0.00, 48, 'solo', 'Bermuda', '2025-11-17', '02:44:00', 'room_69097db1e184b', '80d5697e', 1, 'upcoming', '2025-11-04 04:14:41', 'src/uploads/tournament_banners/banner_69097db1e18bd.jpg', 1, 1, '{\"1\":12,\"2\":10,\"3\":8,\"4\":7,\"5\":6,\"6\":5,\"7\":4,\"8\":3,\"9\":2,\"10\":1,\"11\":1,\"12\":1}', 1.00),
(67, 'rhntyn5yrn6', 'ergvetd', 20.00, 50.00, 48, 'solo', 'Bermuda', '2025-11-09', '15:09:00', 'room_690a3ab567fdf', 'd6da2e4a', 1, 'completed', '2025-11-04 17:41:09', 'src/uploads/tournament_banners/banner_690a3ab568440.png', 1, 1, '{\"1\":12,\"2\":10,\"3\":8,\"4\":7,\"5\":6,\"6\":5,\"7\":4,\"8\":3,\"9\":2,\"10\":1,\"11\":1,\"12\":1}', 1.00),
(68, 'ldfjbklhmtnnukmjmnrmui', 'ergergvt', 0.00, 0.00, 24, 'duo', 'Alpine', '2025-11-24', '13:31:00', '12345678910235', '1234567', 1, 'ongoing', '2025-11-04 18:01:09', 'src/uploads/tournament_banners/banner_690a3f65db3b6.jpg', 3, 1, '{\"1\":12,\"2\":10,\"3\":8,\"4\":7,\"5\":6,\"6\":5,\"7\":4,\"8\":3,\"9\":2,\"10\":1,\"11\":1,\"12\":1}', 1.00),
(69, 'fh', 'rfgbrt', 0.00, 0.00, 2, 'solo', 'Bermuda', '2025-11-18', '17:35:00', 'room_690b13501a28b', '4d195b5b', 1, 'upcoming', '2025-11-05 09:05:20', 'src/uploads/tournament_banners/banner_690b13501a310.png', 1, 1, '{\"1\":12,\"2\":10,\"3\":8,\"4\":7,\"5\":6,\"6\":5,\"7\":4,\"8\":3,\"9\":2,\"10\":1,\"11\":1,\"12\":1}', 1.00);

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
(20, 0.00, 100.00, 0.00, 'open', '2025-10-26 19:08:07', '2025-10-26 19:08:07'),
(21, 0.00, 10.00, 10.00, 'settled', '2025-10-26 19:11:39', '2025-10-26 19:20:41'),
(22, 0.00, 10.00, 10.00, 'settled', '2025-10-27 04:55:17', '2025-10-27 05:01:27'),
(23, 50.00, 100.00, 0.00, 'open', '2025-10-27 09:44:46', '2025-10-27 10:21:10'),
(24, 20.00, 20.00, 0.00, 'open', '2025-10-27 10:34:57', '2025-10-27 10:36:33'),
(25, 0.00, 20.00, 20.00, 'settled', '2025-10-27 10:47:30', '2025-10-27 11:02:37'),
(26, 0.00, 100.00, 100.00, 'settled', '2025-10-27 14:05:34', '2025-10-27 14:16:07'),
(27, 0.00, 20.00, 50.00, 'open', '2025-10-27 14:25:01', '2025-10-27 14:41:04'),
(28, 0.00, 10.00, 0.00, 'open', '2025-10-28 05:21:12', '2025-10-28 05:21:12'),
(29, 0.00, 0.00, 0.00, 'open', '2025-10-28 08:50:17', '2025-10-28 08:50:17'),
(30, 0.00, 0.00, 0.00, 'open', '2025-10-28 09:31:52', '2025-10-28 09:31:52'),
(31, 0.00, 0.00, 0.00, 'open', '2025-10-28 09:33:06', '2025-10-28 09:33:06'),
(32, 0.00, 0.00, 0.00, 'open', '2025-10-28 09:36:59', '2025-10-28 09:36:59'),
(33, 0.00, 0.00, 0.00, 'open', '2025-10-28 09:40:03', '2025-10-28 09:40:03'),
(34, 0.00, 0.00, 0.00, 'open', '2025-10-28 09:41:45', '2025-10-28 09:41:45'),
(35, 0.00, 0.00, 0.00, 'open', '2025-10-28 09:51:27', '2025-10-28 09:51:27'),
(36, 0.00, 0.00, 0.00, 'open', '2025-10-28 10:05:54', '2025-10-28 10:05:54'),
(37, 0.00, 0.00, 50.00, 'settled', '2025-10-29 08:01:08', '2025-10-29 08:07:35'),
(38, 0.00, 0.00, 0.00, 'open', '2025-10-30 04:48:33', '2025-10-30 04:48:33'),
(39, 0.00, 0.00, 0.00, 'open', '2025-10-30 04:49:00', '2025-10-30 04:49:00'),
(40, 0.00, 0.00, 0.00, 'open', '2025-10-30 04:49:25', '2025-10-30 04:49:25'),
(41, 0.00, 10.00, 0.00, 'open', '2025-10-30 04:49:58', '2025-10-30 04:49:58'),
(42, 0.00, 20.00, 0.00, 'open', '2025-10-30 04:50:27', '2025-10-30 04:50:27'),
(43, 0.00, 50.00, 0.00, 'open', '2025-10-30 04:50:56', '2025-10-30 04:50:56'),
(44, 0.00, 0.00, 0.00, 'open', '2025-10-30 04:51:20', '2025-10-30 04:51:20'),
(45, 0.00, 100.00, 0.00, 'open', '2025-10-30 04:51:51', '2025-10-30 04:51:51'),
(46, 0.00, 50.00, 0.00, 'open', '2025-10-30 05:02:14', '2025-10-30 05:02:14'),
(47, 0.00, 0.00, 0.00, 'open', '2025-10-30 05:06:29', '2025-10-30 05:06:29'),
(48, 0.00, 100.00, 0.00, 'open', '2025-10-30 15:35:35', '2025-10-30 15:35:35'),
(49, 0.00, 100.00, 0.00, 'open', '2025-10-30 15:41:15', '2025-10-30 15:41:15'),
(50, 0.00, 0.00, 0.00, 'open', '2025-10-30 15:43:24', '2025-10-30 15:43:24'),
(51, 0.00, 100.00, 0.00, 'open', '2025-10-30 15:49:43', '2025-10-30 15:49:43'),
(56, 0.00, 0.00, 0.00, 'open', '2025-10-30 17:17:55', '2025-10-30 17:17:55'),
(66, 0.00, 0.00, 0.00, 'open', '2025-11-04 04:14:41', '2025-11-04 04:14:41'),
(67, 0.00, 50.00, 50.00, 'settled', '2025-11-04 17:41:09', '2025-11-04 17:56:13'),
(68, 0.00, 0.00, 0.00, 'open', '2025-11-04 18:01:09', '2025-11-04 18:01:09'),
(69, 0.00, 0.00, 0.00, 'open', '2025-11-05 09:05:20', '2025-11-05 09:05:20');

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
(1, 'skynoxx', 'kushwahasanjiv00@gmial.com', '9981474023', 'creator', '$2y$10$hxkutGmdfGMDPcEJNj/X/uY4eh6dOJOyPEqE4fwbokv55I42Hs1JK', '2025-10-23 06:20:30', 471.00, 1, 1, NULL, NULL),
(7, 'Test Creator', 'creator@test.com', '7777777777', 'creator', '$2y$10$TWc843DaZ1L7QDG7Tsw0RulKvP5X8ZEiDJyGkF.zwIcXYDCO8C30y', '2025-10-23 07:54:39', 1310.00, 0, 1, NULL, NULL),
(8, 'Admin User', 'admin@freefire.com', '9999999999', 'admin', '$2y$10$lJ9ulmDSMrLM8hfm2oAEUu8Of9UQh843yNfippHV/FzuY3gxIMnBa', '2025-10-23 08:05:13', 39.00, 0, 1, NULL, NULL),
(14, 'rohan', 'kushwahasanjiv75@gmial.com', '09981474023', 'player', '$2y$10$.YF2CyZZxZEXHkT.dqpD.uwJh/SiNErniGixfkOqhW68Kn8ZyfXYO', '2025-10-27 09:54:48', 0.00, 0, 1, NULL, NULL),
(17, 'sanjiv kumar', 'kushwahasanjiv01@gmial.com', '09981474023', 'player', '$2y$10$KpvjRtg6aVk3ZRMqn1V3FelsQ6edCdHE67524kxtWR/9938Pg9ZMq', '2025-10-30 17:57:00', 530.00, 0, 1, NULL, NULL);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_topup_requests`
--

INSERT INTO `wallet_topup_requests` (`id`, `user_id`, `amount`, `upi_reference`, `screenshot_path`, `status`, `remarks`, `created_at`, `approved_at`, `admin_id`) VALUES
(1, 17, 500.00, '9981474023', '/uploads/topups/topup_user_17_1762108744.jpg', 'approved', '', '2025-11-02 18:39:04', '2025-11-03 00:11:25', 8),
(2, 17, 100.00, '9981474023', '/uploads/topups/topup_user_17_1762279212.jpg', 'pending', NULL, '2025-11-04 18:00:12', NULL, NULL);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `type`, `amount`, `related_user_id`, `tournament_id`, `description`, `status`, `created_at`) VALUES
(1, 1, '', 50.00, NULL, 20, 'Tournament Entry Fee - Tournament #20', 'completed', '2025-10-26 18:18:53'),
(2, 7, '', 50.00, NULL, 20, 'Tournament Entry Received - Tournament #20', 'completed', '2025-10-26 18:18:53'),
(3, 1, '', 20.00, NULL, 21, 'Tournament Entry Fee - Tournament #21', 'completed', '2025-10-26 19:12:08'),
(4, 1, 'prize', 10.00, NULL, 21, 'Prize won in tournament', 'completed', '2025-10-26 19:12:38'),
(5, 7, '', 8.00, NULL, 21, 'Tournament profit share', 'completed', '2025-10-26 19:20:41'),
(6, 8, '', 2.00, NULL, 21, 'Admin profit share', 'completed', '2025-10-26 19:20:41'),
(7, 1, '', 25.00, NULL, 22, 'Tournament Entry Fee - Tournament #22', 'completed', '2025-10-27 04:56:43'),
(8, 1, '', 10.00, NULL, 22, 'Prize won in tournament', 'completed', '2025-10-27 05:00:54'),
(9, 7, '', 12.00, NULL, 22, 'Tournament profit share', 'completed', '2025-10-27 05:01:27'),
(10, 8, '', 3.00, NULL, 22, 'Admin profit share', 'completed', '2025-10-27 05:01:27'),
(11, 1, '', 50.00, NULL, 23, 'Tournament Entry Fee - Tournament #23', 'completed', '2025-10-27 10:21:10'),
(12, 1, '', 10.00, NULL, 24, 'Tournament Entry Fee - Tournament #24', 'completed', '2025-10-27 10:35:37'),
(13, 7, '', 10.00, NULL, 24, 'Top-up tournament wallet', 'completed', '2025-10-27 10:36:33'),
(14, 1, '', 50.00, NULL, 25, 'Tournament Entry Fee - Tournament #25', 'completed', '2025-10-27 10:47:51'),
(15, 1, '', 20.00, NULL, 25, 'Prize won in tournament', 'completed', '2025-10-27 11:02:32'),
(16, 7, '', 24.00, NULL, 25, 'Tournament profit share', 'completed', '2025-10-27 11:02:37'),
(17, 8, '', 6.00, NULL, 25, 'Admin profit share', 'completed', '2025-10-27 11:02:37'),
(18, 1, '', 20.00, NULL, 26, 'Tournament Entry Fee - Tournament #26', 'completed', '2025-10-27 14:08:44'),
(19, 7, '', 100.00, NULL, 26, 'Top-up tournament wallet', 'completed', '2025-10-27 14:15:33'),
(20, 1, '', 100.00, NULL, 26, 'Prize won in tournament', 'completed', '2025-10-27 14:15:46'),
(21, 7, '', 16.00, NULL, 26, 'Tournament profit share', 'completed', '2025-10-27 14:16:07'),
(22, 8, '', 4.00, NULL, 26, 'Admin profit share', 'completed', '2025-10-27 14:16:07'),
(23, 1, '', 50.00, NULL, 27, 'Tournament Entry Fee - Tournament #27', 'completed', '2025-10-27 14:26:20'),
(24, 1, '', 20.00, NULL, 27, 'Prize won in tournament', 'completed', '2025-10-27 14:39:42'),
(25, 1, '', 30.00, NULL, 27, 'Prize won in tournament', 'completed', '2025-10-27 14:41:04'),
(26, 7, '', 500.00, NULL, NULL, 'bonus', 'completed', '2025-10-28 06:25:03'),
(27, 1, '', 100.00, NULL, NULL, 'bonus', 'completed', '2025-10-28 06:27:00'),
(28, 7, '', 100.00, NULL, NULL, 'Wallet Withdrawal Request (pending approval)', 'completed', '2025-10-29 08:03:33'),
(29, 7, '', 100.00, NULL, 37, 'Top-up tournament wallet', 'completed', '2025-10-29 08:06:51'),
(30, 1, '', 50.00, NULL, 37, 'Prize won in tournament', 'completed', '2025-10-29 08:07:09'),
(31, 7, '', 40.00, NULL, 37, 'Tournament profit share', 'completed', '2025-10-29 08:07:35'),
(32, 8, '', 10.00, NULL, 37, 'Admin profit share', 'completed', '2025-10-29 08:07:35'),
(33, 7, '', 100.00, NULL, NULL, 'gift', 'completed', '2025-10-29 08:10:15'),
(34, 7, 'withdraw', 100.00, NULL, NULL, 'Withdrawal approved by admin', 'completed', '2025-10-29 08:12:43'),
(35, 7, '', 500.00, NULL, NULL, 'fgngh', 'completed', '2025-10-30 05:47:34'),
(36, 17, 'deposit', 500.00, NULL, NULL, 'Manual wallet top-up approved (Request #1)', 'completed', '2025-11-02 18:41:25'),
(37, 17, '', 20.00, NULL, 67, 'Tournament Entry Fee - Tournament #67', 'completed', '2025-11-04 17:53:32'),
(38, 1, '', 100.00, NULL, 67, 'Top-up tournament wallet', 'completed', '2025-11-04 17:55:42'),
(39, 17, '', 50.00, NULL, 67, 'Prize won in tournament', 'completed', '2025-11-04 17:55:54'),
(40, 1, '', 56.00, NULL, 67, 'Tournament profit share', 'completed', '2025-11-04 17:56:13'),
(41, 8, '', 14.00, NULL, 67, 'Admin profit share', 'completed', '2025-11-04 17:56:13'),
(42, 1, '', 400.00, NULL, NULL, 'Wallet Withdrawal Request (pending approval)', 'completed', '2025-11-04 17:57:20');

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
(1, 7, 100.00, '9981474023-2@ybl', 'src/uploads/withdrawal_qr/qr_creator_7_1761725013.png', 'approved', '2025-10-29 08:03:33'),
(2, 1, 400.00, '9981474023-2@ybl', 'src/uploads/withdrawal_qr/qr_creator_1_1762279040.png', 'pending', '2025-11-04 17:57:20');

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
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_audience` (`audience`),
  ADD KEY `idx_invitation` (`invitation_id`);

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
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tournament_player` (`tournament_id`,`player_id`),
  ADD UNIQUE KEY `uniq_tournament_slot` (`tournament_id`,`slot_no`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_prize_status` (`prize_status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `creators`
--
ALTER TABLE `creators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `match_results`
--
ALTER TABLE `match_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `registration_team_members`
--
ALTER TABLE `registration_team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `team_invitations`
--
ALTER TABLE `team_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `team_registrations`
--
ALTER TABLE `team_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `wallet_topup_requests`
--
ALTER TABLE `wallet_topup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `tournament_wallets`
--
ALTER TABLE `tournament_wallets`
  ADD CONSTRAINT `fk_tw_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_topup_requests`
--
ALTER TABLE `wallet_topup_requests`
  ADD CONSTRAINT `wallet_topup_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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
