create database `ff`;
use `ff`;
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
(1, 8, 'Admin User', 'admin@freefire.com', '$2y$10$lJ9ulmDSMrLM8hfm2oAEUu8Of9UQh843yNfippHV/FzuY3gxIMnBa', '9999999999', 'super_admin', 1, '2025-10-30 05:46:22', '2025-10-23 08:05:17', '2025-10-30 05:46:22');

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
(1, 7, 'Test Creator', '7777777777', 'creator@test.com', 'FF123456789', 'TestCreatorYT', NULL, 'src/uploads/creators/profile_7_1761302187.jpg', 'src/uploads/creators/game_profile_7_1761302187.png', '2025-10-23 07:54:43', '2025-10-24 10:36:27');

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
  `audience` enum('all','players','creators','user') NOT NULL DEFAULT 'players',
  `audience_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `title`, `message`, `tournament_id`, `audience`, `audience_user_id`, `created_at`) VALUES
(1, 'tournament_created', 'New Tournament: bjhcvjgy', 'Creator posted a new tournament on 2025-10-29 03:51 | Entry ₹10.00 | Prize ₹10.00', 28, 'players', NULL, '2025-10-28 05:21:12'),
(2, 'tournament_created', 'New Tournament: phone test', 'Creator posted a new tournament on 2025-10-09 18:20 | Entry ₹0.00 | Prize ₹0.00', 29, 'players', NULL, '2025-10-28 08:50:17'),
(3, 'tournament_created', 'New Tournament: dzlmfnhg', 'Creator posted a new tournament on 2025-10-28 15:05 | Entry ₹0.00 | Prize ₹0.00', 30, 'players', NULL, '2025-10-28 09:31:52'),
(4, 'tournament_created', 'New Tournament: dzlmfnhg', 'Creator posted a new tournament on 2025-10-28 15:05 | Entry ₹0.00 | Prize ₹0.00', 31, 'players', NULL, '2025-10-28 09:33:06'),
(5, 'tournament_created', 'New Tournament: dfhbfgb', 'Creator posted a new tournament on 2025-10-22 20:03 | Entry ₹0.00 | Prize ₹0.00', 32, 'players', NULL, '2025-10-28 09:36:59'),
(6, 'tournament_created', 'New Tournament: dfhf', 'Creator posted a new tournament on 2025-10-31 20:10 | Entry ₹0.00 | Prize ₹0.00', 33, 'players', NULL, '2025-10-28 09:40:03'),
(7, 'tournament_created', 'New Tournament: dvef', 'Creator posted a new tournament on 2025-10-14 20:11 | Entry ₹0.00 | Prize ₹0.00', 34, 'players', NULL, '2025-10-28 09:41:45'),
(8, 'tournament_created', 'New Tournament: j', 'Creator posted a new tournament on 2025-10-22 19:21 | Entry ₹0.00 | Prize ₹0.00', 35, 'players', NULL, '2025-10-28 09:51:27'),
(9, 'tournament_created', 'New Tournament: dvd', 'Creator posted a new tournament on 2025-10-29 20:35 | Entry ₹0.00 | Prize ₹0.00', 36, 'players', NULL, '2025-10-28 10:05:54'),
(10, 'tournament_created', 'New Tournament: test', 'Creator posted a new tournament on 2025-11-07 17:31 | Entry ₹0.00 | Prize ₹0.00', 37, 'players', NULL, '2025-10-29 08:01:08'),
(11, 'tournament_created', 'New Tournament: solo', 'Creator posted a new tournament on 2025-11-01 02:18 | Entry ₹0.00 | Prize ₹0.00', 38, 'players', NULL, '2025-10-30 04:48:33'),
(12, 'tournament_created', 'New Tournament: duo', 'Creator posted a new tournament on 2025-11-05 03:18 | Entry ₹0.00 | Prize ₹0.00', 39, 'players', NULL, '2025-10-30 04:49:00'),
(13, 'tournament_created', 'New Tournament: squard', 'Creator posted a new tournament on 2025-11-04 02:19 | Entry ₹0.00 | Prize ₹0.00', 40, 'players', NULL, '2025-10-30 04:49:25'),
(14, 'tournament_created', 'New Tournament: solo paid', 'Creator posted a new tournament on 2025-11-03 03:19 | Entry ₹10.00 | Prize ₹10.00', 41, 'players', NULL, '2025-10-30 04:49:58'),
(15, 'tournament_created', 'New Tournament: duo paid', 'Creator posted a new tournament on 2025-11-03 10:25 | Entry ₹20.00 | Prize ₹20.00', 42, 'players', NULL, '2025-10-30 04:50:27'),
(16, 'tournament_created', 'New Tournament: squard paid', 'Creator posted a new tournament on 2025-11-04 03:20 | Entry ₹50.00 | Prize ₹50.00', 43, 'players', NULL, '2025-10-30 04:50:56'),
(17, 'tournament_created', 'New Tournament: cs', 'Creator posted a new tournament on 2025-10-29 02:21 | Entry ₹0.00 | Prize ₹0.00', 44, 'players', NULL, '2025-10-30 04:51:20'),
(18, 'tournament_created', 'New Tournament: cs paid', 'Creator posted a new tournament on 2025-10-31 03:21 | Entry ₹50.00 | Prize ₹100.00', 45, 'players', NULL, '2025-10-30 04:51:51'),
(19, 'tournament_created', 'New Tournament: cs', 'Creator posted a new tournament on 2025-11-04 04:37 | Entry ₹0.00 | Prize ₹50.00', 46, 'players', NULL, '2025-10-30 05:02:14'),
(20, 'tournament_created', 'New Tournament: cs test', 'Creator posted a new tournament on 2025-11-04 03:36 | Entry ₹0.00 | Prize ₹0.00', 47, 'players', NULL, '2025-10-30 05:06:29');

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
(1, '2025-10-29 13:35:52', '2025-10-29 08:05:52'),
(15, '2025-10-28 15:36:20', '2025-10-28 10:06:20');

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
(3, 14, 'DS rohan !!', 'src/uploads/players/avatar_14_1761559462.jpg', 'src/uploads/players/screenshot_14_1761559462.png', '1789721228', NULL, '9907260511', '2025-10-27 09:56:04', '2025-10-27 10:04:22');

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
(25, 37, 1, 1, 'DS ESP', '', NULL, 50.00, '2025-10-29 08:05:49', NULL, NULL, NULL, NULL, 'Not Won');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_registrations`
--

INSERT INTO `team_registrations` (`id`, `registration_id`, `user_id`, `role`, `position_index`, `invited_by`, `invitation_status`, `created_at`, `updated_at`) VALUES
(1, 20, 1, 'captain', 1, NULL, 'accepted', '2025-10-27 10:21:10', '2025-10-27 10:21:10'),
(2, 20, 14, 'member', 2, NULL, 'accepted', '2025-10-27 10:21:10', '2025-10-27 10:21:10'),
(3, 21, 1, 'captain', 1, NULL, 'accepted', '2025-10-27 10:35:37', '2025-10-27 10:35:37'),
(4, 21, 14, 'member', 2, NULL, 'accepted', '2025-10-27 10:35:37', '2025-10-27 10:35:37'),
(5, 24, 1, 'captain', 1, NULL, 'accepted', '2025-10-27 14:26:20', '2025-10-27 14:26:20'),
(6, 24, 14, 'member', 2, NULL, 'accepted', '2025-10-27 14:26:20', '2025-10-27 14:26:20'),
(7, 25, 1, 'captain', 1, NULL, 'accepted', '2025-10-29 08:05:49', '2025-10-29 08:05:49'),
(8, 25, 14, 'member', 2, NULL, 'accepted', '2025-10-29 08:05:49', '2025-10-29 08:05:49');

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
  `match_type` enum('solo','duo','squad') DEFAULT 'squad',
  `map_name` varchar(100) DEFAULT 'Bermuda',
  `date` date NOT NULL,
  `time` time NOT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `room_password` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `banner` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `title`, `description`, `entry_fee`, `prize_pool`, `max_players`, `match_type`, `map_name`, `date`, `time`, `room_id`, `room_password`, `created_by`, `status`, `created_at`, `banner`) VALUES
(37, 'test', 'rules', 0.00, 0.00, 48, 'duo', 'Alpine', '2025-11-07', '17:31:00', 'room_6901c9c4e3510', 'c4ffb206', 7, 'cancelled', '2025-10-29 08:01:08', 'src/uploads/tournament_banners/banner_6901c9c4e35a1.png'),
(38, 'solo', 'g', 0.00, 0.00, 48, 'solo', 'Bermuda', '2025-11-01', '02:18:00', 'room_6902ee21615cd', 'f4293975', 7, 'upcoming', '2025-10-30 04:48:33', 'src/uploads/tournament_banners/banner_6902ee2161673.png'),
(39, 'duo', 'sdvf', 0.00, 0.00, 48, 'duo', 'Alpine', '2025-11-05', '03:18:00', 'room_6902ee3cde690', '93e24074', 7, 'upcoming', '2025-10-30 04:49:00', 'src/uploads/tournament_banners/banner_6902ee3cde725.png'),
(40, 'squard', 'gvrtgvf', 0.00, 0.00, 48, 'squad', 'Alpine', '2025-11-04', '02:19:00', 'room_6902ee556001b', '4581cee6', 7, 'upcoming', '2025-10-30 04:49:25', 'src/uploads/tournament_banners/banner_6902ee556006b.png'),
(41, 'solo paid', 'wger', 10.00, 10.00, 48, 'solo', 'Nexterra', '2025-11-03', '03:19:00', 'room_6902ee76e3154', '3e34bb9b', 7, 'upcoming', '2025-10-30 04:49:58', NULL),
(42, 'duo paid', 'cghjmgh', 20.00, 20.00, 48, 'duo', 'Alpine', '2025-11-03', '10:25:00', 'room_6902ee9345b7d', '48ea668d', 7, 'upcoming', '2025-10-30 04:50:27', 'src/uploads/tournament_banners/banner_6902ee9345baa.png'),
(43, 'squard paid', 'fgjty', 50.00, 50.00, 48, 'squad', 'Alpine', '2025-11-04', '03:20:00', 'room_6902eeb0de575', '57675f67', 7, 'upcoming', '2025-10-30 04:50:56', 'src/uploads/tournament_banners/banner_6902eeb0de59f.png'),
(44, 'cs', 'wgr', 0.00, 0.00, 48, '', 'Alpine', '2025-10-29', '02:21:00', 'room_6902eec85d89a', '25a0a937', 7, 'upcoming', '2025-10-30 04:51:20', 'src/uploads/tournament_banners/banner_6902eec85d8ff.png'),
(45, 'cs paid', 'fgngf', 50.00, 100.00, 48, '', 'Purgatory', '2025-10-31', '03:21:00', 'room_6902f4469659c', 'fddc3276', 7, 'upcoming', '2025-10-30 04:51:51', 'src/uploads/tournament_banners/banner_6902eee714169.png'),
(46, 'cs', 'efre', 0.00, 50.00, 48, '', 'Alpine', '2025-11-04', '04:37:00', 'room_6902f1566abed', '38ff105f', 7, 'upcoming', '2025-10-30 05:02:14', 'src/uploads/tournament_banners/banner_6902f1566ac6d.png'),
(47, 'cs test', 'cfbfg', 0.00, 0.00, 48, '', 'Purgatory', '2025-11-04', '03:36:00', 'room_6902f2b7ef32b', '5c138c62', 7, 'upcoming', '2025-10-30 05:06:29', 'src/uploads/tournament_banners/banner_6902f25507e04.png');

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
(47, 0.00, 0.00, 0.00, 'open', '2025-10-30 05:06:29', '2025-10-30 05:06:29');

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
  `fcm_token` varchar(255) DEFAULT NULL,
  `fcm_token_updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `role`, `password`, `joined_at`, `wallet_balance`, `profile_verified`, `fcm_token`, `fcm_token_updated_at`) VALUES
(1, 'skynoxx', 'kushwahasanjiv01@gmial.com', '9981474023', 'player', '$2y$10$hxkutGmdfGMDPcEJNj/X/uY4eh6dOJOyPEqE4fwbokv55I42Hs1JK', '2025-10-23 06:20:30', 515.00, 1, NULL, NULL),
(7, 'Test Creator', 'creator@test.com', '7777777777', 'creator', '$2y$10$TWc843DaZ1L7QDG7Tsw0RulKvP5X8ZEiDJyGkF.zwIcXYDCO8C30y', '2025-10-23 07:54:39', 1310.00, 0, NULL, NULL),
(8, 'Admin User', 'admin@freefire.com', '9999999999', 'admin', '$2y$10$lJ9ulmDSMrLM8hfm2oAEUu8Of9UQh843yNfippHV/FzuY3gxIMnBa', '2025-10-23 08:05:13', 25.00, 0, NULL, NULL),
(14, 'rohan', 'kushwahasanjiv75@gmial.com', '09981474023', 'player', '$2y$10$.YF2CyZZxZEXHkT.dqpD.uwJh/SiNErniGixfkOqhW68Kn8ZyfXYO', '2025-10-27 09:54:48', 0.00, 0, NULL, NULL),
(15, 'Sanjiv kumar ', 'kushwahasanjiv01@gmail.com', '9981474023', 'player', '$2y$10$5BXlLF2qUG6DdtRQDxnHYeXhNlzowzofHJEYSg5x5oMEKeYyFqaF.', '2025-10-28 08:48:25', 0.00, 0, 'ceji6155TDC81_yP_Cl4sR:APA91bHtONBIlWa1z5NBYaYW3OZt1unJ19gMJ5TcM8xqKfz1vfhdFmIct63OTiMe-y8VfnPASm99fH7pWLp-UOA-iSfoEZVTFzV9_36D3ykCPJV9MKIDIrA', '2025-10-28 15:34:43');

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
(35, 7, '', 500.00, NULL, NULL, 'fgngh', 'completed', '2025-10-30 05:47:34');

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
(1, 7, 100.00, '9981474023-2@ybl', 'src/uploads/withdrawal_qr/qr_creator_7_1761725013.png', 'approved', '2025-10-29 08:03:33');

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_audience` (`audience`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `registration_team_members`
--
ALTER TABLE `registration_team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `team_registrations`
--
ALTER TABLE `team_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
