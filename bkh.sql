-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 09:43 PM
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
-- Database: `bkh`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `email`, `full_name`, `created_at`, `updated_at`) VALUES
(2, 'Akash', '$2y$10$58tfpeIpQ8wUmdWNViLIKep9tgvIohoKze5TPyoHuv22.ZWNsNnd2', 'ashikur31169@gmail.com', 'Ashikur Rahaman', '2025-06-22 04:50:19', '2025-06-24 15:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audiobooks`
--

CREATE TABLE `audiobooks` (
  `audiobook_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `writer` varchar(255) NOT NULL,
  `genre` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `language` varchar(100) DEFAULT NULL,
  `audio_url` varchar(255) NOT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` time DEFAULT NULL,
  `status` enum('visible','hidden','pending') DEFAULT 'visible',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audiobooks`
--

INSERT INTO `audiobooks` (`audiobook_id`, `title`, `writer`, `genre`, `category`, `language`, `audio_url`, `poster_url`, `description`, `duration`, `status`, `created_at`, `updated_at`) VALUES
(1, 'The Pearl of Love', 'H.G. Wells', 'Romantic', 'Love', 'English', '\\BookHeaven2.0\\assets\\audiobooks\\The_Pearl_of_Love_1750607677.mp3', '\\BookHeaven2.0\\assets/audiobook_covers/The_Pearl_of_Love_1750607677.jpg', 'In the radiant valleys beneath snow-capped peaks, a young prince’s love blooms with breathtaking intensity—only to be shattered by a sudden loss. Devastated but resolute, he vows to immortalize his beloved in a monument of unparalleled beauty, a creation so transcendent it might rival the heavens themselves.', '10:52:00', 'visible', '2025-06-22 21:54:37', '2025-06-25 23:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `published` date DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `details` text DEFAULT NULL,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rating` float DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `published`, `price`, `quantity`, `details`, `cover_image_url`, `created_at`, `updated_at`, `rating`) VALUES
(4, 'Glass Requiem', '2021-08-12', 200, 0, 'Glass Requiem is a psychological thriller about a detective who, while solving a simple case, uncovers a web of deception that challenges his career and perception of reality. With twists and a haunting atmosphere, it explores themes of guilt, obsession, and the boundary between sanity and madness.', 'assets/book_covers/Glass_Requiem_1750233338.jpg', '2025-06-18 13:55:38', '2025-06-22 14:28:12', 4),
(5, 'SAIFA', '2025-05-24', 520, 12, 'If you loved The Matrix, Arrival, or Children of Men, this story will haunt you—in the best way.\r\nSaifa is stunning. Not just because it’s beautifully written—which it is. Not just because it’s visionary and cinematic—which it absolutely is. But because Saifa remembers the one thing most science fiction forgets: the soul.', 'assets/book_covers/SAIFA_1750233759.jpg', '2025-06-18 14:02:39', '2025-06-18 14:38:38', 5),
(6, 'ZEROPOINT', '2021-08-21', 400, 6, 'Alix Principio was born with a number. A bloody number 1 etched on his chest the moment he was pulled from his mother’s body.\r\n\r\nWithin the city of Nox, formerly known as New York City, an authoritarian state called One has sprung from the ashes of the deadliest plague humanity has ever seen. Every human is now born with a number etched on their chest. It increases painfully every day, the direct consequence of a rushed vaccine that saved humanity from extinction.', 'assets/book_covers/ZEROPOINT_1750233924.jpg', '2025-06-18 14:05:24', '2025-06-18 14:05:24', NULL),
(7, 'Harry Potter', '2017-07-25', 1200, 8, 'The Eighth Story. Nineteen Years Later. Based on an original story by J.K. Rowling, John Tiffany, and Jack Thorne, a play by Jack Thorne.\r\nIt was always difficult being Harry Potter and it isn’t much easier now that he is an overworked employee of the Ministry of Magic, a husband, and father of three school-age children.\r\nWhile Harry grapples with a past that refuses to stay where it belongs, his youngest son, Albus, must struggle with the weight of a family legacy he never wanted. As past and present fuse ominously, both father and son learn the uncomfortable truth: Sometimes, darkness comes from unexpected places.', 'assets/book_covers/Harry_Potter_and_the_Cursed_Child_1750235162.jpg', '2025-06-18 14:26:02', '2025-06-18 19:15:31', 4),
(8, 'Atmosphere', '2025-06-03', 300, 10, 'Husbands of Evelyn Hugo and Daisy Jones & The Six comes an epic new novel set against the backdrop of the 1980s space shuttle program about the extraordinary lengths we go to live and love beyond our limits.\r\n\r\nThe stunning hardcover of Atmosphere features beautiful endpapers and a premium dust jacket!', 'assets/book_covers/Atmosphere_1750607310.jpg', '2025-06-22 21:48:30', '2025-06-22 21:48:30', 4);

-- --------------------------------------------------------

--
-- Table structure for table `book_categories`
--

CREATE TABLE `book_categories` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_categories`
--

INSERT INTO `book_categories` (`id`, `book_id`, `category_id`) VALUES
(4, 4, 21),
(5, 5, 14),
(6, 6, 9),
(8, 7, 2),
(9, 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `book_genres`
--

CREATE TABLE `book_genres` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_genres`
--

INSERT INTO `book_genres` (`id`, `book_id`, `genre_id`) VALUES
(4, 4, 3),
(5, 5, 18),
(6, 6, 5),
(8, 7, 3),
(9, 7, 4),
(10, 8, 4);

-- --------------------------------------------------------

--
-- Table structure for table `book_languages`
--

CREATE TABLE `book_languages` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_languages`
--

INSERT INTO `book_languages` (`id`, `book_id`, `language_id`, `is_primary`) VALUES
(4, 4, 1, 1),
(5, 5, 1, 1),
(6, 6, 1, 1),
(8, 7, 1, 1),
(9, 8, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `book_writers`
--

CREATE TABLE `book_writers` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `writer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_writers`
--

INSERT INTO `book_writers` (`id`, `book_id`, `writer_id`) VALUES
(14, 5, 34),
(12, 6, 6),
(10, 7, 1),
(11, 8, 24);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'New Arrivals', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(2, 'Bestsellers', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(3, 'Award Winners', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(4, 'Classic Literature', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(5, 'Contemporary', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(6, 'Children', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(7, 'Teen', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(8, 'Adult', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(9, 'Educational', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(10, 'Comics & Graphic Novels', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(11, 'Religious & Spiritual', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(12, 'Health & Wellness', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(13, 'Business & Finance', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(14, 'Technology', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(15, 'Travel', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(16, 'Cooking', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(17, 'Art & Photography', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(18, 'Science & Nature', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(19, 'History & Culture', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(20, 'Parenting & Family', '2025-06-05 12:59:39', '2025-06-05 12:59:39'),
(21, 'Fiction', '2025-06-18 13:54:39', '2025-06-18 13:54:39'),
(22, 'muata', '2025-06-22 10:20:12', '2025-06-22 10:20:12');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `description` text DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `name`, `venue`, `event_date`, `description`, `banner_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Writers Meet Readers', 'Dhaka Convention Center', '2025-06-21 18:13:53', 'A meet-up event for writers and readers', 'assets\\event_banners\\wmeetr.png', 'completed', '2025-06-21 22:25:59', '2025-06-23 01:00:04'),
(2, 'Workshop on Digital Marketing', 'Bangladesh Tech Hub', '2025-06-22 10:00:00', 'Workshop focused on digital marketing strategies', 'assets\\event_banners\\workshop1.webp', 'completed', '2025-06-21 22:25:59', '2025-06-23 00:59:50'),
(3, 'Motivational Speech', 'City Hall, Khulna', '2025-07-14 14:00:00', 'Motivational speech by renowned speakers', 'assets\\event_banners\\motive.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-23 00:57:36'),
(4, 'Book Launch Event', 'National Library', '2025-06-24 18:00:00', 'Launch event for a new fiction book', 'assets\\event_banners\\wmeetr.png', 'cancelled', '2025-06-21 22:25:59', '2025-06-23 01:00:19'),
(5, 'AI and Automation Conference', 'International Conference Center', '2025-06-25 09:00:00', 'Conference discussing AI and automation trends', 'assets\\event_banners\\Inovstion.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:49:51'),
(6, 'Art Exhibition', 'Dhaka Art Gallery', '2025-06-26 11:00:00', 'An exhibition showcasing contemporary art', 'assets\\event_banners\\art.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:50:14'),
(7, 'Health and Wellness Seminar', 'University of Dhaka', '2025-06-27 15:00:00', 'Seminar on health and wellness topics', 'assets\\event_banners\\health.webp', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:50:36'),
(8, 'Tech Innovation Expo', 'Bangabandhu International Conference Center', '2025-06-28 16:00:00', 'Exhibition on the latest in tech innovations', 'assets\\event_banners\\Ai.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:51:21'),
(10, 'Entrepreneurship Meetup', 'Startup Hub Dhaka', '2025-06-30 13:00:00', 'Networking event for entrepreneurs and startups', 'assets\\event_banners\\digital.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:51:59'),
(14, 'Sarathee', 'sxxss', '2025-06-27 01:58:00', 'ssss', 'assets/event_banners/s_1750564483.jpg', 'upcoming', '2025-06-22 09:54:43', '2025-06-23 02:39:15');

-- --------------------------------------------------------

--
-- Table structure for table `event_participants`
--

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `ticket_id` varchar(50) DEFAULT NULL,
  `joined_at` datetime DEFAULT current_timestamp(),
  `status` enum('registered','attended','cancelled') DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_participants`
--

INSERT INTO `event_participants` (`id`, `user_id`, `event_id`, `ticket_id`, `joined_at`, `status`) VALUES
(7, 4, 8, 'TICKET-68570F4E00A87', '2025-06-22 02:00:17', 'registered'),
(9, 4, 5, 'TICKET-68570CB745DBD', '2025-06-22 01:49:15', 'registered');

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `genre_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`genre_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Fiction', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(2, 'Non-Fiction', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(3, 'Mystery', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(4, 'Romance', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(5, 'Science Fiction', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(6, 'Fantasy', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(7, 'Historical', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(8, 'Thriller', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(9, 'Biography', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(10, 'Self-Help', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(11, 'Horror', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(12, 'Adventure', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(13, 'Poetry', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(14, 'Philosophy', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(15, 'Young Adult', '2025-06-05 12:58:54', '2025-06-05 12:58:54'),
(17, 'Islami', '2025-06-06 00:03:22', '2025-06-06 00:03:22'),
(18, 'Sci-Fi', '2025-06-18 14:02:05', '2025-06-18 14:02:05');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `language_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `iso_code` varchar(10) DEFAULT NULL,
  `native_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`language_id`, `name`, `iso_code`, `native_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'English', 'en', 'English', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(2, 'Bengali', 'bn', 'বাংলা', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(3, 'Hindi', 'hi', 'हिन्दी', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(4, 'Urdu', 'ur', 'اُردُو', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(5, 'Arabic', 'ar', 'العربية', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(6, 'Spanish', 'es', 'Español', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(7, 'French', 'fr', 'Français', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(8, 'Chinese', 'zh', '中文', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(9, 'Tamil', 'ta', 'தமிழ்', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(10, 'Telugu', 'te', 'తెలుగు', 'active', '2025-06-05 13:05:17', '2025-06-05 13:05:17'),
(11, 'Akash', NULL, NULL, 'active', '2025-06-05 23:33:17', '2025-06-05 23:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','online') NOT NULL,
  `shipping_address` text NOT NULL,
  `payment_status` enum('pending','confirm','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `status`, `total_amount`, `payment_method`, `shipping_address`, `payment_status`) VALUES
(1, 4, '2025-04-08 22:42:35', 'shipped', 2860.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'pending'),
(3, 4, '2025-04-09 23:28:44', 'cancelled', 1260.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'pending'),
(4, 4, '2025-05-06 00:42:53', 'pending', 1660.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'pending'),
(5, 4, '2025-06-19 01:33:24', 'delivered', 1060.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'pending'),
(6, 4, '2025-03-19 01:48:33', 'delivered', 5260.00, 'online', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'pending'),
(7, 4, '2025-06-21 10:46:22', 'pending', 780.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'pending'),
(8, 4, '2025-06-24 19:29:50', 'pending', 1960.00, 'online', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'pending'),
(16, 4, '2025-06-24 21:41:13', 'confirmed', 1260.00, 'online', 'Raghunathpur, Narail, Dhaka , Bangladesh.', 'confirm');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `book_id`, `quantity`, `price`) VALUES
(1, 1, 6, 3, 400.00),
(2, 1, 4, 2, 200.00),
(3, 1, 7, 1, 1200.00),
(5, 3, 4, 6, 200.00),
(6, 4, 6, 4, 400.00),
(7, 5, 4, 5, 200.00),
(8, 6, 6, 13, 400.00),
(9, 7, 4, 1, 200.00),
(10, 7, 5, 1, 520.00),
(11, 8, 6, 4, 400.00),
(12, 8, 8, 1, 300.00),
(20, 16, 7, 1, 1200.00);

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `partner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','suspended') DEFAULT 'pending',
  `income` int(11) DEFAULT NULL,
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`partner_id`, `user_id`, `status`, `income`, `joined_at`) VALUES
(3, 4, 'approved', NULL, '2025-06-25 23:05:36');

-- --------------------------------------------------------

--
-- Table structure for table `partner_books`
--

CREATE TABLE `partner_books` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `rent_book_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp(),
  `revenue` int(11) DEFAULT NULL,
  `status` enum('pending','visible','on rent','return apply','return') NOT NULL DEFAULT 'pending',
  `retuen_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partner_books`
--

INSERT INTO `partner_books` (`id`, `partner_id`, `rent_book_id`, `added_at`, `revenue`, `status`, `retuen_date`) VALUES
(2, 3, 1, '2025-06-25 23:06:55', NULL, 'visible', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `user_id`, `book_id`, `question_text`, `created_at`, `updated_at`) VALUES
(1, 4, 4, 'Is this available?', '2025-06-18 15:49:18', '2025-06-18 15:49:18'),
(2, 4, 4, 'xasssax', '2025-06-18 15:52:52', '2025-06-18 15:52:52'),
(3, 4, 5, 'Hi', '2025-06-23 03:37:44', '2025-06-23 03:37:44');

-- --------------------------------------------------------

--
-- Table structure for table `rent_books`
--

CREATE TABLE `rent_books` (
  `rent_book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `writer` varchar(255) NOT NULL,
  `genre` varchar(255) NOT NULL,
  `language` varchar(100) DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rent_books`
--

INSERT INTO `rent_books` (`rent_book_id`, `title`, `writer`, `genre`, `language`, `poster_url`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Filthy Promises', 'Nicole Fox', 'Romantic', 'English', 'assets\\rent_book_covers\\book1.jpg', 'My name is Rowan St. Clair.\r\nFor five years, I’ve tried to pretend I’m not head over heels for a man I can never have:\r\nVincent Akopov—gorgeous, ruthless, and completely out of my league.\r\nBut one fateful errand changes everything.', '2025-06-25 00:02:44', '2025-06-25 10:23:59');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `review_text` text NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `book_id`, `review_text`, `rating`, `created_at`, `updated_at`) VALUES
(1, 4, 4, 'This is good book', 4, '2025-06-18 15:47:20', '2025-06-18 15:47:20'),
(2, 4, 5, 'This is very good book.', 5, '2025-06-20 13:59:09', '2025-06-20 13:59:09');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_orders`
--

CREATE TABLE `subscription_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `status` enum('active','expired','cancelled','failed','hold') DEFAULT 'active',
  `payment_status` enum('unpaid','paid','failed') DEFAULT 'unpaid',
  `issue_date` date NOT NULL,
  `expire_date` date NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `user_subscription_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_orders`
--

INSERT INTO `subscription_orders` (`id`, `user_id`, `plan_id`, `amount`, `invoice_number`, `status`, `payment_status`, `issue_date`, `expire_date`, `payment_method`, `updated_at`, `user_subscription_id`) VALUES
(1, 4, 4, 780.00, 'INVC122343', 'active', 'paid', '2025-05-07', '2025-09-11', 'Bkash', NULL, NULL),
(3, 57, 2, 24.99, 'INV-20250622-544F10', 'active', 'paid', '2025-06-22', '2025-09-22', 'bank_transfer', NULL, NULL),
(4, 58, 2, 24.99, 'INV-20250622-445CBB', 'active', 'paid', '2025-06-22', '2025-09-22', 'paypal', NULL, NULL),
(5, 59, 2, 24.99, 'INV-20250622-AE9D11', 'active', 'paid', '2025-06-22', '2025-09-22', 'bank_transfer', NULL, NULL),
(9, 63, 2, 24.99, 'INV-20250622-47B672', 'active', 'paid', '2025-06-22', '2025-09-22', 'credit_card', NULL, NULL),
(11, 66, 2, 24.99, 'INV-20250622-48B135', 'active', 'paid', '2025-06-22', '2025-09-22', 'credit_card', NULL, NULL),
(12, 67, 1, 9.99, 'INV-20250622-DA1230', 'active', 'paid', '2025-06-22', '2025-07-22', 'credit_card', NULL, NULL),
(15, 70, 2, 24.99, 'INV-20250622-E65D5B', 'active', 'paid', '2025-02-21', '2025-09-22', 'paypal', NULL, NULL),
(16, 71, 2, 24.99, 'INV-20250622-6A9420', 'active', 'paid', '2025-06-22', '2025-09-22', 'paypal', NULL, NULL),
(17, 72, 2, 24.99, 'INV-20250622-AC1FAF', 'active', 'unpaid', '2025-03-05', '2025-09-22', NULL, NULL, NULL),
(18, 73, 4, 24.99, 'INV-20250622-59D6F5', 'active', 'paid', '2025-06-22', '2025-09-22', 'credit_card', NULL, NULL),
(20, 75, 1, 9.99, 'INV-20250622-C04F26', 'active', 'paid', '2025-06-22', '2025-07-22', 'credit_card', NULL, NULL),
(25, 81, 2, 24.99, 'INV-20250622-64FE21', 'active', 'paid', '2025-06-22', '2025-09-22', 'bank_transfer', NULL, NULL),
(26, 82, 1, 9.99, 'INV-20250622-2DC61B', 'active', 'paid', '2025-06-22', '2025-07-22', 'credit_card', NULL, NULL),
(27, 83, 2, 24.99, 'INV-20250622-DB8B45', 'active', 'paid', '2025-06-22', '2025-09-22', 'bank_transfer', NULL, NULL),
(28, 84, 2, 24.99, 'INV-20250622-E23053', 'active', 'paid', '2025-04-06', '2025-09-22', 'paypal', NULL, NULL),
(29, 85, 2, 24.99, 'INV-20250622-F03219', 'active', 'unpaid', '2025-06-22', '2025-09-22', NULL, NULL, NULL),
(31, 87, 2, 24.99, 'INV-20250622-388D73', 'active', 'paid', '2025-06-22', '2025-09-22', 'credit_card', NULL, NULL),
(32, 88, 4, 24.99, 'INV-20250622-E63CCA', 'active', 'paid', '2025-06-22', '2025-09-22', 'bank_transfer', NULL, NULL),
(33, 89, 1, 9.99, 'INV-20250622-16AD12', 'active', 'unpaid', '2025-06-22', '2025-07-22', NULL, NULL, NULL),
(34, 90, 4, 24.99, 'INV-20250622-B95CA5', 'active', 'paid', '2025-05-16', '2025-09-22', 'bank_transfer', NULL, NULL),
(35, 91, 2, 24.99, 'INV-20250622-AD9344', 'active', 'paid', '2025-06-22', '2025-09-22', 'paypal', NULL, NULL),
(36, 92, 1, 9.99, 'INV-20250622-B6B8FD', 'active', 'paid', '2025-06-22', '2025-07-22', 'credit_card', NULL, NULL),
(37, 93, 1, 9.99, 'INV-20250622-834265', 'active', 'paid', '2025-06-22', '2025-07-22', 'bank_transfer', NULL, NULL),
(38, 94, 1, 9.99, 'INV-20250622-0C0752', 'active', 'paid', '2025-06-22', '2025-07-22', 'credit_card', NULL, NULL),
(39, 95, 1, 9.99, 'INV-20250622-304CFC', 'active', 'paid', '2025-06-22', '2025-07-22', 'paypal', NULL, NULL),
(40, 96, 2, 24.99, 'INV-20250622-DA0750', 'active', 'paid', '2025-06-22', '2025-09-22', 'paypal', NULL, NULL),
(42, 98, 1, 9.99, 'INV-20250622-BE1DDD', 'active', 'paid', '2025-06-22', '2025-07-22', 'bank_transfer', NULL, NULL),
(44, 100, 1, 9.99, 'INV-20250622-333A23', 'active', 'paid', '2025-06-22', '2025-07-22', 'credit_card', NULL, NULL),
(45, 101, 2, 24.99, 'INV-20250622-16B06C', 'active', 'paid', '2025-06-22', '2025-09-22', 'paypal', NULL, NULL),
(47, 103, 2, 24.99, 'INV-20250622-DDC1BF', 'active', 'paid', '2025-06-22', '2025-09-22', 'bank_transfer', NULL, NULL),
(48, 104, 2, 24.99, 'INV-20250622-DC7D87', 'active', 'paid', '2025-06-22', '2025-09-22', 'paypal', NULL, NULL),
(49, 105, 2, 24.99, 'INV-20250622-008852', 'active', 'paid', '2025-06-22', '2025-09-22', 'credit_card', NULL, NULL),
(100, 4, 1, 1200.00, 'INV-20250624-28CA21', '', 'unpaid', '2025-06-24', '2025-08-23', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `plan_id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `validity_days` int(11) NOT NULL,
  `book_quantity` int(11) DEFAULT 0,
  `audiobook_quantity` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`plan_id`, `plan_name`, `price`, `validity_days`, `book_quantity`, `audiobook_quantity`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Premium', 1200.00, 60, 45, 10, 'You get 27/7 full support', 'active', '2025-06-21 10:33:11', '2025-06-23 00:31:19'),
(2, 'Gold', 1200.00, 60, 10, 10, 'ajnckjiubcj ksjcu', 'active', '2025-06-22 09:39:14', '2025-06-22 23:29:08'),
(4, 'Basic', 780.00, 60, 40, 30, 'This is for premium user.', 'active', '2025-06-22 23:28:49', '2025-06-22 23:54:53');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_transactions`
--

CREATE TABLE `subscription_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_subscription_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','online') NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_code` varchar(255) DEFAULT NULL,
  `payment_provider` varchar(100) DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_transactions`
--

INSERT INTO `subscription_transactions` (`transaction_id`, `user_subscription_id`, `amount`, `payment_method`, `payment_status`, `transaction_code`, `payment_provider`, `transaction_date`) VALUES
(3, 6, 780.00, '', 'paid', 'BKH1750768296278', 'bKash', '2025-06-24 18:31:36'),
(4, 7, 1200.00, '', 'paid', 'CARD1750768336990', 'Stripe', '2025-06-24 18:32:16');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cod','online') NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_date` datetime DEFAULT current_timestamp(),
  `payment_reference` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `order_id`, `payment_method`, `payment_status`, `transaction_date`, `payment_reference`) VALUES
(2, 16, '', 'paid', '2025-06-24 21:41:54', 'BKH1750779714103');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `user_profile` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT current_timestamp(),
  `update_time` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `two_step_verification` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `pass`, `user_profile`, `create_time`, `update_time`, `email_verified`, `two_step_verification`, `last_login`, `login_count`) VALUES
(4, 'Akash', 'ashikur31169@gmail.com', '$2y$10$mj4OSI2BglfRPndjz58dbOKQS8tTDL6T.KLCYg01p.66wmawUkNI2', 'assets\\user_profile\\akash.jpg', '2025-06-17 16:06:42', '2025-06-25 14:34:41', 0, 0, '2025-06-25 14:34:41', 36),
(6, 'clopez37', 'charlotte.lopez91@example.com', '$2y$10$W0U0XjwQVobR82g80Gqr7Ok29IkVino/oYycSZOApfOMtjE677tdG', NULL, '2025-03-02 23:52:05', '2025-06-23 00:14:13', 0, 0, NULL, 0),
(7, 'ewilliams96', 'evelyn.williams86@example.com', '$2y$10$T1kFDYbkrT4pFiZcw7wVdeITrPSfu9jeeCNvWmImO9lpak3UxLwye', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(8, 'rlopez84', 'robert.lopez84@example.com', '$2y$10$kFzOU5yrYv/bfS9nSO4Bl.I.SOhUvzrDKcoBTYv1zvRbaW2s/YYZ.', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(9, 'mwilson20', 'michael.wilson58@example.com', '$2y$10$7SH8KtFxAjpvY0vj6Kfy0OAwBQomiFTIc5/mD4t3d0T3ycOIU3v8y', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(10, 'mdavis94', 'michael.davis62@example.com', '$2y$10$4hJTJ62yTvTbiMtfJmX3Ne61GskZLo8NvnkpH3RCxA74uVVK23oSi', NULL, '2025-04-15 22:52:05', '2025-06-23 00:14:52', 0, 0, NULL, 0),
(11, 'srodriguez71', 'sophia.rodriguez19@example.com', '$2y$10$4PF7/C0a5nhdO9bSxSxkEO.g3eI531Z22l.hPJ1B6wvCvmckdv1YC', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(12, 'hdavis19', 'harper.davis16@example.com', '$2y$10$MYWqCzhpFhoAmTr0s9dNTOZxnfVSGAwST1HtApHn3ZLZHO8Rjkx0q', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(13, 'adavis83', 'ava.davis62@example.com', '$2y$10$THAsHNyJUtDs1jepakpA3eiZhZIiFMMCcakroM13oY.7JVUqY/1y.', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(14, 'rgarcia2', 'robert.garcia54@example.com', '$2y$10$KQTfNkSTtKFZlcXlTLvKm.l.g12bE/5FpHy.mOgTjEsYp8ZFnGGyO', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(15, 'dmartin86', 'daniel.martin22@example.com', '$2y$10$hGHcEZHK6NK.n5vTCqAiXurYccLfCgH96T8kszeMc2xMXRTfhP8aS', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(16, 'sthomas28', 'sophia.thomas97@example.com', '$2y$10$qDWGTPVrrhGyZKX5PM8oiuWsHONqb27Q9c6TRBjWDJ0r0ACJnTE6i', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(17, 'wjones12', 'william.jones59@example.com', '$2y$10$lRWVQRkLRawRJ9EGy00L6.V.OJGI6jg5LySZQAteYWhCCHsn6OK.i', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(18, 'mmartinez47', 'mia.martinez72@example.com', '$2y$10$cZG7vbw/uatoSsnQogKLSuK.1GBfiYOBVsv2ri0DT/nzeaqRIvowO', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(19, 'hdavis12', 'harper.davis89@example.com', '$2y$10$GbtF3KODX8BLJ8eg4hw1VO70iIe9xJb.IGSQ3MwOjE5XkTM2Lpq8e', NULL, '2025-06-22 23:52:05', '2025-06-22 23:52:05', 0, 0, NULL, 0),
(20, 'mdavis52', 'michael.davis48@example.com', '$2y$10$bhxlWTe2d5SFC53xwgYpUuhwqU0hCD5kPYW0xFHoug2I4eVppU.2m', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(21, 'jgonzalez92', 'john.gonzalez41@example.com', '$2y$10$XR8inxeWme8Q/YvyL2kmPelNaGcd5kSlfs.N0THKQWe9kfFlB1fzG', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(22, 'djackson46', 'daniel.jackson39@example.com', '$2y$10$kb/D0b8QRzpEl3rEcA7H/eRksOSqYnW0cV/1vt9BhwY.JxiwWJQbG', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(23, 'sgarcia17', 'sophia.garcia57@example.com', '$2y$10$Z8mLoS6.O.6rBZDQp4DzfORQIysjmdTwBW737ufyypchx9W5EihX2', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(24, 'dthomas17', 'daniel.thomas91@example.com', '$2y$10$XeXaT.RRxNoVg1cEqfe.zuqw4VRFnPAXJofyd/52W2UqormVj52Ou', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(25, 'rlopez35', 'richard.lopez54@example.com', '$2y$10$69y6MEBo46IsCSXn8z8mEuxKZeSKxW715whIFSWoFLVifuJoBaNX2', NULL, '2025-01-10 23:52:06', '2025-06-23 00:21:58', 0, 0, NULL, 0),
(26, 'ewilliams75', 'evelyn.williams94@example.com', '$2y$10$PwmXDcIET4gtRAUTxilwxuFFdPCV5Z1ZPk6UMokckNWMD.aQTbPQq', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(27, 'rwilson22', 'richard.wilson47@example.com', '$2y$10$j6Mk9wjDCm5w/gOh8Kbtkeg3lr5eRB2z2cKKTs1HwnoGLJtXAQ3vC', NULL, '2025-01-16 23:52:06', '2025-06-23 00:21:33', 0, 0, NULL, 0),
(28, 'ddavis56', 'daniel.davis50@example.com', '$2y$10$v7l7wTFlX8GRe/q1I6dIT.nOhx6Sy0IlHzil1GD01lW7/MkH.aCbu', NULL, '2025-01-08 23:52:06', '2025-06-23 00:21:21', 0, 0, NULL, 0),
(29, 'edavis90', 'evelyn.davis81@example.com', '$2y$10$31yOX226oueuAKn9tEHv7uiDfDcylcEPkrACimpNBLM5b1JZSQOju', NULL, '2025-01-15 23:52:06', '2025-06-23 00:21:10', 0, 0, NULL, 0),
(30, 'agarcia1', 'amelia.garcia81@example.com', '$2y$10$1BAOFVF1qj/oGdH9VfW/pOD2LSez7zcL3QgEXq0dvT6NUTpNwls4G', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(31, 'jsmith10', 'john.smith84@example.com', '$2y$10$YyOnDPKoZACwVQFZ1SZJzeKNTQWlV.tvAsckaFdWS9V0ZIgzPi1Qe', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(32, 'dmoore33', 'daniel.moore29@example.com', '$2y$10$EHgNMgTu27gAuzVAAgzVcub3xKg.9x31ryGsrroydjk2x9bLaNoFm', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(33, 'manderson4', 'michael.anderson55@example.com', '$2y$10$hGXeM9TYGW3L8d9f5xEnHull0swLoMz.J6YFCePQz3lq3OnOKcWDu', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(34, 'mlopez8', 'mia.lopez24@example.com', '$2y$10$gWnqrcRvmpEAX7kG/QQtZuRxvhbwDzgpd8/.rfkWcvcsG6GJbE1fC', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(35, 'wmoore80', 'william.moore94@example.com', '$2y$10$VTLi3rHc0NLlimojdJ.35.6HEE6pyrENa9aWMu131dGISNawBpKlO', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(36, 'mjohnson17', 'mia.johnson13@example.com', '$2y$10$OqRDPbvXan4tQ0gkbrLU7eYOIt6mz133oS4Rtbt6KL0oH.DjCkGPy', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(37, 'ddavis11', 'david.davis2@example.com', '$2y$10$gxTNEoa.F1ipTgmSwY7C4uE4ijXTGem1xUEisHNAN/Zv4wmeFvHm2', NULL, '2025-06-22 23:52:06', '2025-06-22 23:52:06', 0, 0, NULL, 0),
(38, 'jdavis88', 'joseph.davis96@example.com', '$2y$10$4MAbpjbTTSmQ5.ZoHZmbputq5fdOkyV5FMj0GZAaQtwR2GCl/8Ti2', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(39, 'rmartin94', 'robert.martin17@example.com', '$2y$10$RFU7EszxK/j2f7xd4Fkm9eZcDv0a565dKNKZs2.Au7sw2UOUVnBUu', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(40, 'mlopez71', 'mia.lopez85@example.com', '$2y$10$grBLKSekleSlqWMawqPPR.BnSzuJmRDss3bS45qzWXnxJ5AhTnwQ6', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(41, 'otaylor14', 'olivia.taylor18@example.com', '$2y$10$K4hWodnspqvn9TXEDKlrRuyWLc8NO6ia1htBNaCeiyCnop93h/3X.', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(42, 'rdavis72', 'richard.davis63@example.com', '$2y$10$udY5ZnC9qKhBF3CrACs3Bea23fKZXfZEh09BTQxoAXjRiqn7e2C6u', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(43, 'jjackson55', 'john.jackson8@example.com', '$2y$10$6MiPxNTBgVzjvn0p4n93zuouRebHGH6OCkFHfuELhyCmusl9wUrPW', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(44, 'ejackson65', 'evelyn.jackson56@example.com', '$2y$10$kUlwZ9AcySInpkuQ5exYb.KcB5jfwYCIeGh4J51dKLgsabG9V7u36', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(45, 'shernandez2', 'sophia.hernandez41@example.com', '$2y$10$h2RXP684yT1jxxFy2ZjwpOVhbS.U15v/oQSYKQo6i/fqxmyOwzO5a', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(46, 'tdavis67', 'thomas.davis72@example.com', '$2y$10$KllYpp/t0ALMkenWaO2R7uhtlQijwRQ3xRkXHaSysKHeqL9wtBoXC', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(47, 'arodriguez91', 'amelia.rodriguez14@example.com', '$2y$10$TX4lH1oN.TzeJYv2hZ.41.8Cv6wZh5dKu.HCVh3wrVBSfP2HHBj8e', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(48, 'jjackson95', 'james.jackson27@example.com', '$2y$10$uuPBaQHiqg8hb4gbRSCGu.3tWXa0NqD9eNtLH0q1HLg6/rV4jvMPC', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(49, 'tlopez52', 'thomas.lopez48@example.com', '$2y$10$bdU8dXNky6KB9v8O/2M.beamM0U3GU6.kcwhcFwKZXZGqePRKI0le', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(50, 'mgonzalez89', 'michael.gonzalez88@example.com', '$2y$10$iyIhJDiqX9JoY.M.LR1YiOXcrPDEA43qkiXAcmRSLCKI7F8AXjsQe', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(51, 'hmoore50', 'harper.moore95@example.com', '$2y$10$diWK7I6Ab.WWherJRYPx7.a8CipHfCior.WyowWqzqOfaSpVdewai', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(52, 'jsmith16', 'james.smith31@example.com', '$2y$10$glTilCPuc1H92bMxwO./RuhCbQlUN/OYV4tQhcxSB6h7N57xAVDSC', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(53, 'rtaylor33', 'robert.taylor54@example.com', '$2y$10$uCerxrkSzt32ubDZSazv5uZzp7LEVUkPFwMWdHBEmh0vPaqCHaIsO', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(54, 'shernandez29', 'sophia.hernandez30@example.com', '$2y$10$DZ4iiSktHYNcQuxSrpRk6ONLxVf9OIlBwJISaP6WGhA3oOT4WyQMq', NULL, '2025-06-22 23:52:07', '2025-06-22 23:52:07', 0, 0, NULL, 0),
(55, 'imartinez85', 'isabella.martinez67@example.com', '$2y$10$l0NG6Ne3.CYVv.AILP/ZmOZ3gHXmYaibBO5iTzE7.1saLbOeKtUU6', NULL, '2025-06-22 23:52:08', '2025-06-22 23:52:08', 0, 0, NULL, 0),
(56, 'hthomas69', 'harper.thomas35@example.com', '$2y$10$gAFa0a15pmm.3Ljwmg36T.DzDm2YhdppBxn1Im6Tdq7qYHytJyGWS', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(57, 'jbrown52', 'joseph.brown37@example.com', '$2y$10$TxmIN.2yGmhTCGotEgCLD.IK5OYAcvLci5E/Ouo4pJSjnniA38QnC', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(58, 'mgonzalez25', 'michael.gonzalez36@example.com', '$2y$10$RTcIaUPHcobS6jLYErFI.u9uI3r8MrXNB.mbKjBu3.XcJvX7zFV8y', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(59, 'wbrown12', 'william.brown72@example.com', '$2y$10$rFFEMgrW.xtXr.gHbKauXemy4kHm9W8uP.8IjXUpVAkS9wuBXxesW', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(60, 'dthomas1', 'david.thomas22@example.com', '$2y$10$pvyfh0CcnKyGVNRN6N8zwO9xsEqOYwul6A3jOAxN6LhUoyS8xMHru', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(61, 'dhernandez13', 'daniel.hernandez87@example.com', '$2y$10$SxAFMlzOgEpcvFBS6PfD4.9BvxuuIfpDJUmakcc1/meB7rHZxK/CG', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(62, 'jhernandez18', 'james.hernandez26@example.com', '$2y$10$rHC86lr75/W22Of/.d6KneAqaakDmFMRahzKD59J1LK7iXBPWa1wy', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(63, 'hgonzalez87', 'harper.gonzalez19@example.com', '$2y$10$yS2rzXnHEc9ZeXdd2Hxw1O41tH0AwNmRxSVJ7KtMbHdScuCupGl/u', NULL, '2025-06-23 00:09:11', '2025-06-23 00:09:11', 0, 0, NULL, 0),
(65, 'rjones27', 'robert.jones39@example.com', '$2y$10$5nruO/By99ZwcoGdYlXJ0eb5LZ/kExOG1EnPkJStqmQFzv2Me2Jva', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(66, 'imartin12', 'isabella.martin84@example.com', '$2y$10$1Zhc3PEXYjkuMl0bwHQPe.aGAPQDaUw1W8DmSNzVrLt4Ije3t7Ei2', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(67, 'jmartinez49', 'joseph.martinez62@example.com', '$2y$10$v7KdAcuS2gTjtgpMBXwM8O09pGq9A6As/k/VDP3vgqyDH1E8zGp.W', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(68, 'tjohnson27', 'thomas.johnson2@example.com', '$2y$10$qnD1vqEMl9ehOQYv0s0SluPmk0WLoalbM4Nx7EWGLyfDtWJ2yFIZG', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(69, 'cgonzalez36', 'charlotte.gonzalez51@example.com', '$2y$10$miCE4ZW2zYhScRwHbpD4OemAY9bxbA.UVgJSFWddSnE5rSUqN9ay.', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(70, 'owilliams88', 'olivia.williams63@example.com', '$2y$10$gktNudgXcWPGsr9C7HslGO6HEBSQNbLjAJvxlci49xDZoQ0HTN89S', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(71, 'trodriguez17', 'thomas.rodriguez31@example.com', '$2y$10$rgYsLOqzsiORGIhviH.uSeg6z8TI/IBpTKhvcUWbYQL7swJ.H81Ja', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(72, 'jdavis54', 'james.davis4@example.com', '$2y$10$g2UM9yWlJMgXsLJGwTFy3O.PyLmUYfqgZBRB54dPozqcDvSMJZYOa', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(73, 'awilson75', 'ava.wilson23@example.com', '$2y$10$.mwl78f7qHyBfgoJ06TdmenCf9XslPFvBm.4xho/7O2YjBBXMC9mW', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(74, 'ehernandez96', 'evelyn.hernandez44@example.com', '$2y$10$WhyWa3RGWNsRQt8ucdAzq.zz8rH7nhG/VOvhwgyHWxPQXZ6PRyUdW', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(75, 'amartinez44', 'ava.martinez17@example.com', '$2y$10$SFLuOOoSKMTJ6ZwJ8CYw2uhDFm.FhxSFAD9AYuMOmt.hz6YTzg5qS', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(76, 'jmoore40', 'james.moore30@example.com', '$2y$10$3y35.WckS8fKcNd3nIQ8DOCuMBu/ktV.BKbPEz81HNOt7XgHAavMS', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(78, 'rhernandez75', 'richard.hernandez15@example.com', '$2y$10$p70j2hiMjPDz/oHMk214guWCUyEgfPqApWkhG9x5KHAV47JEGDJtW', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(79, 'tgonzalez22', 'thomas.gonzalez17@example.com', '$2y$10$tApCJ2.Rp4DxYz540A9.M.6CQajr/QxhChiM9K.6qNaNgFSbi5Y/6', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(80, 'tsmith7', 'thomas.smith19@example.com', '$2y$10$8mVMgpsc4x8rpOL/B1cW4OYZDXjfXp./zp7g.Cvr7HP7z1EI84IxO', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(81, 'djones97', 'david.jones28@example.com', '$2y$10$8NpmtZfMgrJRjio1vMcSZ.AA7woyjDmuKQO1AFrCBPe.Wh/d0noYG', NULL, '2025-06-23 00:09:12', '2025-06-23 00:09:12', 0, 0, NULL, 0),
(82, 'rjackson41', 'richard.jackson73@example.com', '$2y$10$aKUPa1nkCDcULcXsQ1LxAuDFAUtEyrDlcf1BejHBcWwVpDFyOUTXa', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(83, 'mwilson16', 'mia.wilson88@example.com', '$2y$10$n0jOlSNd47bvBqcOi0uem.Y8uHXUp49jzK2E9o6HbMC7FQ.Hgk5ta', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(84, 'smoore15', 'sophia.moore78@example.com', '$2y$10$MzhYcH.2EglbSzzTrpXFXOa8SMjVmN/Lic2dghacMO3ZFxr6rmB6G', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(85, 'ejackson43', 'evelyn.jackson44@example.com', '$2y$10$cjAXwIazbJG0kM9RlIjGS.S7GcwpCzLmP8oQvuq1JiBSGyb1baGGi', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(86, 'adavis23', 'ava.davis51@example.com', '$2y$10$Wrfh5F95ebuHxmpO20qZOuH5L/R7Y2RCsgDlQqDKh1HuGjRa8ZfWS', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(87, 'tjackson78', 'thomas.jackson41@example.com', '$2y$10$PJv1Ky41PuK8JIVIo9cAPu1WzQw1bEctTqUfV2bQsBujCHccF2/3y', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(88, 'dwilliams98', 'david.williams78@example.com', '$2y$10$m00rqWw1jwkXAiq52V4WNOl/q4zCqn6YT9tRQXxECxaCroPUet5Te', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(89, 'smoore86', 'sophia.moore50@example.com', '$2y$10$W349PR786mwf3eqa92L2f.lBR/S7LtSh11pG3PwiTMEuuZQ9M4oty', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(90, 'rrodriguez46', 'richard.rodriguez62@example.com', '$2y$10$cRKYN.5maf1W3eWtV82ksOe.bYKHMJSWEgrG5ng2FnECQximLUq4q', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(91, 'hmiller58', 'harper.miller65@example.com', '$2y$10$eLLTCbabPsRMaS7nI8pGuOyj3TBjS0wHxmebTwwqsm3IVpl6wUHpK', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(92, 'mdavis96', 'mia.davis30@example.com', '$2y$10$ijvDPLGXNx8gpbtA4TYeOOu4atxoNESmJYwBTcEwt1NzfwIFj808y', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(93, 'rhernandez70', 'richard.hernandez44@example.com', '$2y$10$S7mTq0flaqU1YLX9WMvaWOr.75y62mJHlMSNj39JJXKHEniPe8vTG', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(94, 'wsmith95', 'william.smith87@example.com', '$2y$10$9JbgToB.yE9bvJPdvbtXgOCjHiv95StdaMJr906r2wKej9DUL05vO', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(95, 'mmiller38', 'mia.miller54@example.com', '$2y$10$46av.KEc9Le7K7HCfv44uOzuSSuiqeoZi48KzNO56Rd8cAeTk8zU2', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(96, 'edavis85', 'emma.davis93@example.com', '$2y$10$V/mnHm.BVQnkRMXnE2svgulwtuovtkkJWEUss1EIiycst9OsvKgWq', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(97, 'ethomas99', 'evelyn.thomas94@example.com', '$2y$10$Ia2l0xDbH2z3KLBzUgd4t./VBlk1.KOty8lTj28yhhwOdf1DpTK..', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(98, 'hmartinez22', 'harper.martinez2@example.com', '$2y$10$tjECBEZFZgpKRIwwpmufVeKM.zr/aUHnLRc.lSersJNSMtORJlwpm', NULL, '2025-06-23 00:09:13', '2025-06-23 00:09:13', 0, 0, NULL, 0),
(99, 'jwilliams56', 'james.williams72@example.com', '$2y$10$AgR0dd/l8fdW5MNhBn.bDuVChAobwFxOjOhS7ckM78bb2qU/SI/ka', NULL, '2025-06-23 00:09:14', '2025-06-23 00:09:14', 0, 0, NULL, 0),
(100, 'rhernandez60', 'richard.hernandez33@example.com', '$2y$10$WVnM2s5ZRhVMh5jWSaSpPuJmZCcnBHEtSharZjY2IQM7.Lkwlk3Ky', NULL, '2025-06-23 00:09:14', '2025-06-23 00:09:14', 0, 0, NULL, 0),
(101, 'jsmith31', 'joseph.smith49@example.com', '$2y$10$4aO9POdm6gRymOEFHmkBE.nqmBHllL/8Y.QgW.MCwgPSTj4hPlcle', NULL, '2025-06-23 00:09:14', '2025-06-23 00:09:14', 0, 0, NULL, 0),
(102, 'hgarcia13', 'harper.garcia49@example.com', '$2y$10$aXVDfk7pg7M5FWXOyj07u.vFgzZaPzWbPrWGJC3johizmZO5m9O3a', NULL, '2025-06-23 00:09:14', '2025-06-23 00:09:14', 0, 0, NULL, 0),
(103, 'rmartinez43', 'robert.martinez81@example.com', '$2y$10$WuGW5O7bjrC3NhKuD7URY.IbJ1os7WH.b31xqPyKnlWX1dX4uB74K', NULL, '2025-06-23 00:09:14', '2025-06-23 00:09:14', 0, 0, NULL, 0),
(104, 'ijackson71', 'isabella.jackson86@example.com', '$2y$10$xz2SRc.N6qSGMYKUOAlYJOGHMQ3iQcSdIL974t0j4BTs5SJKfzLda', NULL, '2025-05-12 00:09:14', '2025-06-23 00:22:58', 0, 0, NULL, 0),
(105, 'amartinez72', 'amelia.martinez7@example.com', '$2y$10$Vis1iUtg1AHmbL1P/0ykGeL0f1O3P1Gy7KvnLSJMyExj0LIko3EOC', NULL, '2025-02-06 00:09:14', '2025-06-23 00:22:42', 0, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `auth_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_ip` varchar(45) DEFAULT NULL,
  `login_timestamp` datetime DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `status` enum('active','logged_out') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`auth_id`, `user_id`, `login_ip`, `login_timestamp`, `logout_time`, `status`) VALUES
(5, 4, '::1', '2025-06-17 16:06:52', NULL, 'active'),
(6, 4, '::1', '2025-06-17 16:35:39', NULL, 'active'),
(7, 4, '::1', '2025-06-17 16:36:48', NULL, 'active'),
(8, 4, '::1', '2025-06-17 17:08:46', NULL, 'active'),
(9, 4, '::1', '2025-06-17 17:12:01', NULL, 'active'),
(10, 4, '::1', '2025-06-17 17:59:53', '2025-06-17 17:59:53', 'active'),
(11, 4, '::1', '2025-06-18 10:28:45', '2025-06-18 10:28:45', 'active'),
(12, 4, '::1', '2025-06-18 18:44:34', '2025-06-18 18:44:34', 'active'),
(13, 4, '::1', '2025-06-18 22:21:25', '2025-06-18 22:21:25', 'active'),
(14, 4, '::1', '2025-06-20 19:58:02', '2025-06-20 19:58:02', 'active'),
(15, 4, '::1', '2025-06-21 18:46:35', '2025-06-21 18:46:35', 'active'),
(16, 4, '::1', '2025-06-21 23:07:03', '2025-06-21 23:07:03', 'active'),
(17, 4, '::1', '2025-06-21 23:54:59', '2025-06-21 23:54:59', 'active'),
(18, 4, '::1', '2025-06-21 23:57:16', '2025-06-21 23:57:16', 'active'),
(19, 4, '::1', '2025-06-21 23:58:49', '2025-06-21 23:58:49', 'active'),
(20, 4, '::1', '2025-06-22 00:40:03', '2025-06-22 00:40:03', 'active'),
(21, 4, '::1', '2025-06-22 09:44:39', '2025-06-22 09:44:39', 'active'),
(22, 4, '::1', '2025-06-22 21:43:08', '2025-06-22 21:43:08', 'active'),
(23, 4, '::1', '2025-06-23 09:37:33', '2025-06-23 09:37:33', 'active'),
(24, 4, '::1', '2025-06-23 12:36:46', '2025-06-23 12:36:46', 'active'),
(25, 4, '::1', '2025-06-23 13:59:50', '2025-06-23 13:59:50', 'active'),
(26, 4, '::1', '2025-06-23 14:21:17', '2025-06-23 14:21:17', 'active'),
(27, 4, '::1', '2025-06-23 20:56:00', '2025-06-23 20:56:00', 'active'),
(28, 4, '::1', '2025-06-23 22:43:19', '2025-06-23 22:43:19', 'active'),
(29, 4, '::1', '2025-06-23 23:23:49', '2025-06-23 23:23:49', 'active'),
(30, 4, '::1', '2025-06-24 02:23:45', '2025-06-24 02:23:45', 'active'),
(31, 4, '::1', '2025-06-24 02:44:09', '2025-06-24 02:44:09', 'active'),
(32, 4, '::1', '2025-06-24 08:20:01', '2025-06-24 08:20:01', 'active'),
(33, 4, '::1', '2025-06-24 09:01:17', '2025-06-24 09:01:17', 'active'),
(34, 4, '::1', '2025-06-24 18:54:55', '2025-06-24 18:54:55', 'active'),
(35, 4, '::1', '2025-06-24 22:34:28', '2025-06-24 22:34:28', 'active'),
(36, 4, '::1', '2025-06-24 23:48:54', '2025-06-24 23:48:54', 'active'),
(37, 4, '::1', '2025-06-25 09:55:17', '2025-06-25 09:55:17', 'active'),
(38, 4, '::1', '2025-06-25 14:34:41', '2025-06-25 14:34:41', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_billing_address`
--

CREATE TABLE `user_billing_address` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `street_address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `division` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Bangladesh',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_billing_address`
--

INSERT INTO `user_billing_address` (`id`, `user_id`, `street_address`, `city`, `division`, `zip_code`, `country`, `created_at`, `updated_at`) VALUES
(1, 4, 'Satarkul, Alinagar Gate', 'Dhaka', 'Dhaka', '1201', 'Bangladesh', '2025-06-23 22:35:58', '2025-06-23 22:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `user_id` int(11) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `userimageurl` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_info`
--

INSERT INTO `user_info` (`user_id`, `birthday`, `phone`, `address`, `userimageurl`) VALUES
(4, '2025-02-12', '0199988430', 'Raghunathpur, Narail, Dhaka , Bangladesh.', NULL),
(6, '1963-01-22', '393-513-2335', '6324 Main St, New York, NW 63156', NULL),
(7, '1993-08-23', '808-657-1324', '2850 Willow Blvd, Dallas, OA 75420', NULL),
(8, '2001-09-27', '437-450-2342', '4365 Willow Blvd, Houston, VL 65814', NULL),
(9, '1994-03-07', '774-690-2572', '705 Cedar Ln, San Antonio, UL 87086', NULL),
(10, '1999-11-13', '484-702-8505', '6475 Oak Ave, Dallas, IW 30897', NULL),
(11, '1995-01-10', '505-977-8899', '3299 Magnolia Ave, Chicago, CG 95927', NULL),
(12, '1976-01-07', '752-415-3617', '1613 Pine Rd, New York, ZM 87409', NULL),
(13, '1959-08-22', '295-401-1695', '6143 Birch Way, New York, OE 69195', NULL),
(14, '1988-07-30', '215-809-6750', '280 Magnolia Ave, New York, DE 53918', NULL),
(15, '2005-10-30', '586-449-9939', '9612 Pine Rd, New York, TC 86313', NULL),
(16, '1988-01-12', '298-283-2437', '570 Oak Ave, Dallas, AX 30099', NULL),
(17, '2001-08-02', '393-527-2121', '4839 Cedar Ln, San Jose, TK 26332', NULL),
(18, '1956-07-04', '605-489-1000', '4671 Main St, San Antonio, ED 18292', NULL),
(19, '1992-12-23', '962-607-6375', '3587 Oak Ave, San Antonio, CA 36838', NULL),
(20, '1953-11-17', '458-463-4929', '9683 Maple Dr, Chicago, FC 22305', NULL),
(21, '1959-09-09', '760-495-1567', '9206 Birch Way, New York, GY 96901', NULL),
(22, '1994-03-04', '559-818-6175', '9414 Maple Dr, Phoenix, IZ 40368', NULL),
(23, '1945-07-19', '742-327-8911', '434 Birch Way, New York, UX 73461', NULL),
(24, '1994-03-31', '840-579-3820', '3401 Main St, New York, YM 16980', NULL),
(25, '1960-02-27', '647-447-4642', '5549 Elm St, San Antonio, QI 56680', NULL),
(26, '1988-03-09', '261-854-2732', '2668 Elm St, San Diego, UF 46852', NULL),
(27, '1991-11-29', '230-248-9791', '9042 Maple Dr, New York, BT 17907', NULL),
(28, '1971-04-20', '845-640-4438', '1873 Willow Blvd, Chicago, RU 25371', NULL),
(29, '1989-09-06', '567-537-7290', '9390 Main St, Dallas, GY 31847', NULL),
(30, '1961-12-23', '737-955-3665', '2340 Spruce Ct, Chicago, WG 99598', NULL),
(31, '1987-12-25', '270-241-4901', '5732 Maple Dr, Chicago, IZ 88997', NULL),
(32, '1974-11-20', '330-394-7163', '9236 Willow Blvd, Los Angeles, FN 53458', NULL),
(33, '1979-04-28', '706-557-1511', '8506 Cedar Ln, Chicago, YD 58957', NULL),
(34, '1978-01-29', '549-860-7065', '7758 Elm St, Los Angeles, PD 90673', NULL),
(35, '1982-01-12', '476-499-5115', '1768 Cedar Ln, Chicago, CX 44184', NULL),
(36, '1966-06-10', '899-230-3117', '871 Willow Blvd, San Diego, BD 20530', NULL),
(37, '1966-02-05', '536-462-5257', '1533 Pine Rd, Dallas, WN 29276', NULL),
(38, '2007-04-21', '446-253-3894', '513 Elm St, Houston, BO 81343', NULL),
(39, '1963-02-17', '257-899-7200', '5285 Cedar Ln, San Jose, DY 61113', NULL),
(40, '1953-08-04', '660-696-1356', '6596 Spruce Ct, San Diego, NQ 12569', NULL),
(41, '1992-12-11', '682-235-2807', '6795 Pine Rd, Houston, QU 27293', NULL),
(42, '1989-04-09', '593-848-1220', '1703 Birch Way, San Jose, QP 23810', NULL),
(43, '1965-07-16', '387-805-5371', '6927 Pine Rd, Phoenix, WI 83921', NULL),
(44, '1995-05-31', '721-269-3654', '9177 Cedar Ln, New York, WP 49160', NULL),
(45, '1949-01-01', '883-976-1042', '3057 Main St, Phoenix, YL 70444', NULL),
(46, '1981-03-14', '695-698-3904', '6085 Willow Blvd, Dallas, TM 75510', NULL),
(47, '1980-11-12', '543-760-9879', '2081 Pine Rd, San Diego, YH 87602', NULL),
(48, '1972-11-14', '697-430-3948', '679 Oak Ave, Philadelphia, EL 97076', NULL),
(49, '1946-12-25', '776-433-6162', '7891 Willow Blvd, Los Angeles, JS 40054', NULL),
(50, '1963-11-16', '933-627-1821', '5031 Main St, Phoenix, QD 39996', NULL),
(51, '1982-03-26', '553-905-4117', '8418 Maple Dr, San Jose, YZ 96898', NULL),
(52, '1948-01-22', '755-735-6549', '6993 Willow Blvd, Los Angeles, GA 13641', NULL),
(53, '2000-05-11', '545-625-4129', '4756 Magnolia Ave, San Jose, DN 57750', NULL),
(54, '2000-07-31', '278-328-1695', '5731 Oak Ave, Dallas, NH 33541', NULL),
(55, '1964-07-04', '230-776-9313', '348 Birch Way, Philadelphia, IT 56975', NULL),
(56, '1948-09-13', '788-623-8923', '539 Birch Way, San Diego, PT 62537', NULL),
(57, '1980-06-05', '765-968-6221', '2894 Main St, Chicago, BM 26728', NULL),
(58, '2002-06-10', '851-851-4572', '9965 Spruce Ct, Houston, LS 16299', NULL),
(59, '1949-07-14', '304-592-9519', '6475 Oak Ave, Dallas, YX 54697', NULL),
(60, '1962-10-31', '731-338-9041', '7603 Pine Rd, Chicago, EW 34865', NULL),
(61, '1999-02-10', '789-588-2003', '9121 Oak Ave, Houston, PO 32678', NULL),
(62, '1965-08-26', '959-308-2059', '8566 Maple Dr, Houston, YV 17122', NULL),
(63, '1968-12-02', '336-943-9994', '5719 Elm St, Chicago, YH 73036', NULL),
(65, '1999-12-22', '777-283-8895', '4284 Pine Rd, San Diego, TH 14394', NULL),
(66, '1985-08-04', '241-291-8023', '9243 Spruce Ct, San Diego, UB 99239', NULL),
(67, '1958-03-06', '932-282-8057', '8811 Elm St, Los Angeles, TN 26658', NULL),
(68, '1997-06-05', '396-915-8989', '103 Maple Dr, Dallas, KW 27164', NULL),
(69, '2003-12-11', '604-562-8029', '1009 Oak Ave, Philadelphia, ZI 44044', NULL),
(70, '1946-04-25', '416-951-3315', '1953 Oak Ave, San Antonio, FX 91561', NULL),
(71, '1961-03-31', '349-497-9164', '5111 Willow Blvd, San Jose, VK 21618', NULL),
(72, '2004-06-13', '539-985-2604', '9166 Cedar Ln, Philadelphia, NR 81974', NULL),
(73, '1965-04-28', '526-909-3241', '7351 Pine Rd, San Antonio, KE 49539', NULL),
(74, '1952-11-28', '400-740-8729', '4845 Oak Ave, Houston, QG 28826', NULL),
(75, '1946-10-21', '880-337-7223', '432 Oak Ave, Houston, MQ 73529', NULL),
(76, '1947-11-05', '608-397-8225', '7168 Maple Dr, New York, DB 11228', NULL),
(78, '2006-04-22', '567-299-9600', '3556 Main St, San Diego, YF 26749', NULL),
(79, '1989-04-08', '521-979-6632', '9743 Main St, New York, BG 29065', NULL),
(80, '1998-04-24', '501-487-7674', '5037 Willow Blvd, Dallas, QY 10846', NULL),
(81, '1973-12-20', '646-979-9923', '7842 Main St, Los Angeles, XI 21203', NULL),
(82, '1994-05-04', '977-854-7350', '8905 Willow Blvd, Los Angeles, GM 12646', NULL),
(83, '1956-09-01', '907-471-8389', '6009 Maple Dr, Houston, FL 15524', NULL),
(84, '1965-11-02', '880-258-5959', '5046 Magnolia Ave, Chicago, UH 66178', NULL),
(85, '1957-03-24', '400-767-5140', '670 Birch Way, Phoenix, OB 43631', NULL),
(86, '1957-10-10', '489-634-4137', '282 Magnolia Ave, Phoenix, KH 20440', NULL),
(87, '2000-07-28', '985-285-8328', '8003 Spruce Ct, Chicago, XB 69131', NULL),
(88, '1988-03-16', '590-666-3002', '5506 Willow Blvd, Houston, QR 67677', NULL),
(89, '1951-08-23', '296-442-8949', '5825 Maple Dr, Houston, UM 58536', NULL),
(90, '1999-12-10', '456-209-8229', '1421 Main St, San Antonio, GQ 54834', NULL),
(91, '1945-06-27', '632-462-7474', '3993 Birch Way, Los Angeles, PB 99937', NULL),
(92, '1952-11-04', '413-262-9051', '4575 Oak Ave, Phoenix, BJ 48880', NULL),
(93, '1982-12-26', '870-554-6694', '9804 Oak Ave, Houston, ID 38565', NULL),
(94, '1968-06-24', '340-715-7809', '4080 Spruce Ct, Phoenix, AD 39874', NULL),
(95, '1970-09-29', '399-967-5402', '3579 Elm St, Phoenix, MT 71835', NULL),
(96, '1982-01-13', '508-527-2841', '3373 Pine Rd, Chicago, KG 42373', NULL),
(97, '1988-07-14', '393-466-9629', '4062 Pine Rd, Philadelphia, TF 41991', NULL),
(98, '1959-11-06', '435-368-9663', '2006 Willow Blvd, Chicago, WO 99141', NULL),
(99, '1954-07-26', '298-407-3611', '7596 Elm St, Los Angeles, BZ 78304', NULL),
(100, '1968-01-30', '280-882-9617', '5999 Oak Ave, Dallas, IU 89814', NULL),
(101, '1995-06-25', '761-815-1035', '2992 Willow Blvd, Dallas, PG 58695', NULL),
(102, '2000-12-24', '900-345-6912', '1610 Magnolia Ave, San Diego, TU 29699', NULL),
(103, '1977-01-12', '330-716-2463', '4584 Oak Ave, Dallas, FY 61384', NULL),
(104, '1958-07-25', '382-325-8980', '5275 Main St, Los Angeles, RU 29255', NULL),
(105, '1961-12-07', '216-303-3019', '5437 Elm St, Phoenix, TR 35148', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_otp`
--

CREATE TABLE `user_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_time` datetime DEFAULT current_timestamp(),
  `purpose` enum('bkash_payment','two-factor','password_reset','verify_email','card_payment') NOT NULL,
  `otp_attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_otp`
--

INSERT INTO `user_otp` (`id`, `user_id`, `otp_code`, `otp_time`, `purpose`, `otp_attempts`) VALUES
(3, 4, '140327', '2025-06-17 16:23:25', '', 0),
(4, 4, '959334', '2025-06-17 16:28:27', 'password_reset', 0),
(5, 4, '914637', '2025-06-17 16:30:53', 'password_reset', 0),
(7, 4, '226963', '2025-06-17 17:54:03', 'two-factor', 0),
(8, 4, '114695', '2025-06-17 17:59:35', 'two-factor', 1),
(9, 4, '634445', '2025-06-22 00:38:45', 'password_reset', 1),
(10, 4, '345331', '2025-06-24 01:09:37', '', 0),
(11, 4, '289175', '2025-06-24 01:10:02', '', 0),
(15, 4, '532781', '2025-06-24 02:39:15', 'card_payment', 0),
(16, 4, '763795', '2025-06-24 02:39:20', 'card_payment', 0),
(18, 4, '838527', '2025-06-24 03:01:52', 'two-factor', 1),
(19, 4, '699575', '2025-06-24 08:17:04', 'two-factor', 1),
(20, 4, '608032', '2025-06-24 08:19:36', 'two-factor', 1),
(23, 4, '531146', '2025-06-24 09:00:32', 'two-factor', 1),
(24, 4, '710109', '2025-06-24 09:00:47', 'two-factor', 1),
(25, 4, '553313', '2025-06-24 09:00:49', 'two-factor', 1),
(30, 4, '623086', '2025-06-24 15:06:40', 'card_payment', 0),
(31, 4, '197352', '2025-06-24 16:19:58', 'card_payment', 0),
(32, 4, '645140', '2025-06-24 16:56:10', 'card_payment', 0),
(34, 4, '856657', '2025-06-24 18:53:10', 'two-factor', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_payment_methods`
--

CREATE TABLE `user_payment_methods` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `card_type` enum('visa','mastercard','amex','discover') NOT NULL DEFAULT 'visa',
  `card_number` varchar(20) NOT NULL,
  `card_name` varchar(100) NOT NULL,
  `expiry_date` varchar(10) NOT NULL,
  `cvv` varchar(4) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_payment_methods`
--

INSERT INTO `user_payment_methods` (`id`, `user_id`, `card_type`, `card_number`, `card_name`, `expiry_date`, `cvv`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 4, 'visa', '1122 4554 5225 5332', 'Ashikur', '6/2028', '367', 0, '2025-06-23 22:42:47', '2025-06-24 17:52:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `user_subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subscription_plan_id` int(11) NOT NULL,
  `start_date` datetime DEFAULT current_timestamp(),
  `end_date` datetime NOT NULL,
  `status` enum('active','expired','cancelled','pending') DEFAULT 'pending',
  `renew` int(11) NOT NULL DEFAULT 0,
  `auto_renew` tinyint(1) DEFAULT 0,
  `available_audio` int(11) NOT NULL,
  `used_audio_book` int(11) DEFAULT NULL,
  `available_rent_book` int(11) NOT NULL,
  `used_rent_book` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`user_subscription_id`, `user_id`, `subscription_plan_id`, `start_date`, `end_date`, `status`, `renew`, `auto_renew`, `available_audio`, `used_audio_book`, `available_rent_book`, `used_rent_book`) VALUES
(6, 4, 4, '2025-06-24 18:31:36', '2025-08-23 18:31:36', 'active', 0, 0, 30, NULL, 40, 5),
(7, 4, 2, '2025-06-24 18:32:16', '2025-08-23 14:32:16', 'active', 0, 0, 9, NULL, 9, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_subscription_audiobook_access`
--

CREATE TABLE `user_subscription_audiobook_access` (
  `access_id` int(11) NOT NULL,
  `user_subscription_id` int(11) NOT NULL,
  `audiobook_id` int(11) NOT NULL,
  `access_date` datetime DEFAULT current_timestamp(),
  `return_date` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_subscription_audiobook_access`
--

INSERT INTO `user_subscription_audiobook_access` (`access_id`, `user_subscription_id`, `audiobook_id`, `access_date`, `return_date`, `status`, `user_id`) VALUES
(1, 6, 1, '2025-06-25 11:25:06', '2025-08-23 22:41:37', 'borrowed', 4),
(2, 7, 1, '2025-06-25 11:53:48', '2025-08-23 22:42:44', 'borrowed', 4);

-- --------------------------------------------------------

--
-- Table structure for table `user_subscription_rent_book_access`
--

CREATE TABLE `user_subscription_rent_book_access` (
  `access_id` int(11) NOT NULL,
  `user_subscription_id` int(11) NOT NULL,
  `rent_book_id` int(11) NOT NULL,
  `access_date` datetime DEFAULT current_timestamp(),
  `return_date` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_subscription_rent_book_access`
--

INSERT INTO `user_subscription_rent_book_access` (`access_id`, `user_subscription_id`, `rent_book_id`, `access_date`, `return_date`, `status`, `user_id`) VALUES
(2, 6, 1, '2025-06-25 01:30:10', NULL, 'borrowed', 4),
(3, 7, 1, '2025-06-25 11:42:14', NULL, 'borrowed', 4);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `book_id`, `added_at`) VALUES
(6, 4, 8, '2025-06-23 17:24:08'),
(7, 4, 6, '2025-06-23 17:24:17');

-- --------------------------------------------------------

--
-- Table structure for table `writers`
--

CREATE TABLE `writers` (
  `writer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `writers`
--

INSERT INTO `writers` (`writer_id`, `name`, `email`, `bio`, `address`, `image_url`, `created_at`) VALUES
(1, 'J.K. Rowling', 'jkrowling@example.com', 'British author, best known for the Harry Potter series.', 'Edinburgh, UK', 'assets\\writers_profile\\J.K.Rowling.jpg', '2025-06-05 13:00:51'),
(2, 'George R.R. Martin', 'grrmartin@example.com', 'American novelist and creator of \"A Song of Ice and Fire\".', 'Bayonne, NJ, USA', 'assets\\writers_profile\\George R.R. Martin.jpg', '2025-06-05 13:00:51'),
(3, 'Rabindranath Tagore', 'tagore@example.com', 'Nobel laureate poet, musician, and artist from Bengal.', 'Kolkata, India', 'assets\\writers_profile\\Rabindranath Tagore.jpg', '2025-06-05 13:00:51'),
(4, 'Humayun Ahmed', 'humayunahmed@example.com', 'Popular Bangladeshi author, dramatist, and filmmaker.', 'Dhaka, Bangladesh', 'assets\\writers_profile\\Humayun Ahmed.jpg', '2025-06-05 13:00:51'),
(5, 'Agatha Christie', 'agathachristie@example.com', 'Renowned British mystery writer and creator of Hercule Poirot.', 'Wallingford, UK', 'assets\\writers_profile\\Agatha Christie.jpg', '2025-06-05 13:00:51'),
(6, 'Paulo Coelho', 'paulocoelho@example.com', 'Brazilian author of \"The Alchemist\" and other spiritual novels.', 'Rio de Janeiro, Brazil', 'assets\\writers_profile\\Paulo Coelho.jpg', '2025-06-05 13:00:51'),
(21, 'Kazi Nazrul Islam', 'nazrul@example.com', 'National poet of Bangladesh, known for his revolutionary and spiritual writings.', 'Churulia, West Bengal, India', 'assets\\writers_profile\\IslamKaziNazrul.jpg', '2025-06-05 13:02:08'),
(23, 'Sarat Chandra Chattopadhyay', 'sarat@example.com', 'Famous Bengali novelist known for \"Devdas\", \"Parineeta\", and more.', 'Debanandapur, West Bengal, India', 'assets\\writers_profile\\Sarat Chandra Chattopadhyay.jpg', '2025-06-05 13:02:08'),
(24, 'Sunil Gangopadhyay', 'sunil@example.com', 'Modern Bengali poet and novelist, known for \"Sei Somoy\" and \"Nikhilesh\" series.', 'Kolkata, India', 'assets\\writers_profile\\Sunil Gangopadhyay.jpg', '2025-06-05 13:02:08'),
(26, 'Begum Rokeya', 'rokeya@example.com', 'Pioneer of women’s education and rights in Bengal. Known for \"Sultana’s Dream\".', 'Rangpur, Bangladesh', 'assets\\writers_profile\\Begum Rokeya.jpg', '2025-06-05 13:02:08'),
(34, 'Ian Lumsden', 'ianlumsden@gmail.com', 'Ian Lumsden is a Canadian author and academic known for his scholarly works on art history, Canadian culture, and LGBTQ+ studies. He has contributed significantly to the understanding of Canadian art and the experiences of homosexual communities in Latin America.', 'New Brunswick,America', 'assets\\writers_profile\\th.jpg', '2025-06-23 09:27:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `answer_fk_question` (`question_id`),
  ADD KEY `fk_admin_id` (`admin_id`);

--
-- Indexes for table `audiobooks`
--
ALTER TABLE `audiobooks`
  ADD PRIMARY KEY (`audiobook_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `book_categories`
--
ALTER TABLE `book_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_book_category` (`book_id`,`category_id`),
  ADD KEY `fk_book_categories_category` (`category_id`);

--
-- Indexes for table `book_genres`
--
ALTER TABLE `book_genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_book_genre` (`book_id`,`genre_id`),
  ADD KEY `fk_book_genres_genre` (`genre_id`);

--
-- Indexes for table `book_languages`
--
ALTER TABLE `book_languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_book_language` (`book_id`,`language_id`),
  ADD KEY `fk_book_languages_language` (`language_id`);

--
-- Indexes for table `book_writers`
--
ALTER TABLE `book_writers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `book_id` (`book_id`,`writer_id`),
  ADD KEY `fk_writer` (`writer_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user_book` (`user_id`,`book_id`),
  ADD KEY `fk_cart_book` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_user` (`user_id`,`event_id`),
  ADD KEY `fk_event_participant_event` (`event_id`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`genre_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`language_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `iso_code` (`iso_code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_book` (`book_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`partner_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `partner_books`
--
ALTER TABLE `partner_books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_partner_rentr_book` (`partner_id`,`rent_book_id`),
  ADD KEY `fk_partner_rent_books_book` (`rent_book_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `question_fk_user` (`user_id`),
  ADD KEY `question_fk_books` (`book_id`);

--
-- Indexes for table `rent_books`
--
ALTER TABLE `rent_books`
  ADD PRIMARY KEY (`rent_book_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `review_fk_user` (`user_id`),
  ADD KEY `review_fk_books` (`book_id`);

--
-- Indexes for table `subscription_orders`
--
ALTER TABLE `subscription_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subs_order_user_id` (`user_id`),
  ADD KEY `fk_subs_order_subs_id` (`plan_id`),
  ADD KEY `user_subscription_id` (`user_subscription_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD UNIQUE KEY `plan_name` (`plan_name`);

--
-- Indexes for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_subtxn_subscription` (`user_subscription_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_transactions_order` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`auth_id`),
  ADD KEY `userauthentication_fk` (`user_id`);

--
-- Indexes for table `user_billing_address`
--
ALTER TABLE `user_billing_address`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
  ADD KEY `userinfo_fk` (`user_id`);

--
-- Indexes for table `user_otp`
--
ALTER TABLE `user_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_otp_user` (`user_id`);

--
-- Indexes for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_method_user` (`user_id`);

--
-- Indexes for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`user_subscription_id`),
  ADD UNIQUE KEY `uq_user_active_subscription` (`user_id`,`subscription_plan_id`,`start_date`),
  ADD KEY `fk_user_subscription_plan` (`subscription_plan_id`);

--
-- Indexes for table `user_subscription_audiobook_access`
--
ALTER TABLE `user_subscription_audiobook_access`
  ADD PRIMARY KEY (`access_id`),
  ADD KEY `fk_sub_audio_access_subscription` (`user_subscription_id`),
  ADD KEY `fk_sub_audio_access_audiobook` (`audiobook_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `user_subscription_rent_book_access`
--
ALTER TABLE `user_subscription_rent_book_access`
  ADD PRIMARY KEY (`access_id`),
  ADD KEY `fk_sub_rent_access_subscription` (`user_subscription_id`),
  ADD KEY `fk_sub_rent_access_rent_book` (`rent_book_id`),
  ADD KEY `fk1_user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlist_user_book` (`user_id`,`book_id`),
  ADD KEY `fk_wishlist_book` (`book_id`);

--
-- Indexes for table `writers`
--
ALTER TABLE `writers`
  ADD PRIMARY KEY (`writer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audiobooks`
--
ALTER TABLE `audiobooks`
  MODIFY `audiobook_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `book_categories`
--
ALTER TABLE `book_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `book_genres`
--
ALTER TABLE `book_genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `book_languages`
--
ALTER TABLE `book_languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `book_writers`
--
ALTER TABLE `book_writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `genre_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `partner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `partner_books`
--
ALTER TABLE `partner_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rent_books`
--
ALTER TABLE `rent_books`
  MODIFY `rent_book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscription_orders`
--
ALTER TABLE `subscription_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `auth_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `user_billing_address`
--
ALTER TABLE `user_billing_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_otp`
--
ALTER TABLE `user_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `user_subscription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_subscription_audiobook_access`
--
ALTER TABLE `user_subscription_audiobook_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_subscription_rent_book_access`
--
ALTER TABLE `user_subscription_rent_book_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `writer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answer_fk_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `book_categories`
--
ALTER TABLE `book_categories`
  ADD CONSTRAINT `fk_book_categories_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_book_categories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_genres`
--
ALTER TABLE `book_genres`
  ADD CONSTRAINT `fk_book_genres_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_book_genres_genre` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`genre_id`) ON DELETE CASCADE;

--
-- Constraints for table `book_languages`
--
ALTER TABLE `book_languages`
  ADD CONSTRAINT `fk_book_languages_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_book_languages_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`) ON DELETE CASCADE;

--
-- Constraints for table `book_writers`
--
ALTER TABLE `book_writers`
  ADD CONSTRAINT `fk_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_writer` FOREIGN KEY (`writer_id`) REFERENCES `writers` (`writer_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `fk_event_participant_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_participant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `partners`
--
ALTER TABLE `partners`
  ADD CONSTRAINT `fk_partner_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_books`
--
ALTER TABLE `partner_books`
  ADD CONSTRAINT `fk_partner_rent_books_book` FOREIGN KEY (`rent_book_id`) REFERENCES `rent_books` (`rent_book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_partner_rent_books_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `question_fk_books` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `question_fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `review_fk_books` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_orders`
--
ALTER TABLE `subscription_orders`
  ADD CONSTRAINT `fk_subs_order_subs_id` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subs_order_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_orders_ibfk_1` FOREIGN KEY (`user_subscription_id`) REFERENCES `user_subscriptions` (`user_subscription_id`);

--
-- Constraints for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  ADD CONSTRAINT `fk_subtxn_subscription` FOREIGN KEY (`user_subscription_id`) REFERENCES `user_subscriptions` (`user_subscription_id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `userauthentication_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_billing_address`
--
ALTER TABLE `user_billing_address`
  ADD CONSTRAINT `fk_billing_address_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_info`
--
ALTER TABLE `user_info`
  ADD CONSTRAINT `userinfo_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_otp`
--
ALTER TABLE `user_otp`
  ADD CONSTRAINT `fk_user_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  ADD CONSTRAINT `fk_payment_method_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `fk_user_subscription_plan` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_subscription_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_subscription_audiobook_access`
--
ALTER TABLE `user_subscription_audiobook_access`
  ADD CONSTRAINT `fk_sub_audio_access_audiobook` FOREIGN KEY (`audiobook_id`) REFERENCES `audiobooks` (`audiobook_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_audio_access_subscription` FOREIGN KEY (`user_subscription_id`) REFERENCES `user_subscriptions` (`user_subscription_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_subscription_rent_book_access`
--
ALTER TABLE `user_subscription_rent_book_access`
  ADD CONSTRAINT `fk1_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_rent_access_rent_book` FOREIGN KEY (`rent_book_id`) REFERENCES `rent_books` (`rent_book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_rent_access_subscription` FOREIGN KEY (`user_subscription_id`) REFERENCES `user_subscriptions` (`user_subscription_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
