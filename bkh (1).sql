-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2025 at 06:07 PM
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
(2, 'Akash', '$2y$10$58tfpeIpQ8wUmdWNViLIKep9tgvIohoKze5TPyoHuv22.ZWNsNnd2', 'ashikur31169@gmail.com', 'Ashikur Rahaman', '2025-06-22 04:50:19', '2025-06-22 15:57:29');

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
(1, 'The Pearl of Love', 'H.G. Wells', 'Romantic', 'Love', 'English', 'assets/audiobooks/The_Pearl_of_Love_1750607677.mp3', '/../assets/audiobook_covers/The_Pearl_of_Love_1750607677.jpg', 'In the radiant valleys beneath snow-capped peaks, a young prince’s love blooms with breathtaking intensity—only to be shattered by a sudden loss. Devastated but resolute, he vows to immortalize his beloved in a monument of unparalleled beauty, a creation so transcendent it might rival the heavens themselves.', '10:52:00', 'visible', '2025-06-22 21:54:37', '2025-06-22 21:54:37');

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

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `book_id`, `quantity`, `added_at`) VALUES
(17, 4, 6, 5, '2025-06-21 15:12:06'),
(18, 4, 7, 1, '2025-06-22 22:06:31');

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
(1, 'Writers Meet Readers', 'Dhaka Convention Center', '2025-06-21 18:13:53', 'A meet-up event for writers and readers', 'assets\\event_banners\\wmeetr.png', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:46:19'),
(2, 'Workshop on Digital Marketing', 'Bangladesh Tech Hub', '2025-06-22 10:00:00', 'Workshop focused on digital marketing strategies', 'assets\\event_banners\\workshop1.webp', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:46:54'),
(3, 'Motivational Speech', 'City Hall, Dhaka', '2025-07-14 14:00:00', 'Motivational speech by renowned speakers', 'assets\\event_banners\\motive.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 23:26:13'),
(4, 'Book Launch Event', 'National Library', '2025-06-24 18:00:00', 'Launch event for a new fiction book', 'assets\\event_banners\\wmeetr.png', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:48:40'),
(5, 'AI and Automation Conference', 'International Conference Center', '2025-06-25 09:00:00', 'Conference discussing AI and automation trends', 'assets\\event_banners\\Inovstion.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:49:51'),
(6, 'Art Exhibition', 'Dhaka Art Gallery', '2025-06-26 11:00:00', 'An exhibition showcasing contemporary art', 'assets\\event_banners\\art.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:50:14'),
(7, 'Health and Wellness Seminar', 'University of Dhaka', '2025-06-27 15:00:00', 'Seminar on health and wellness topics', 'assets\\event_banners\\health.webp', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:50:36'),
(8, 'Tech Innovation Expo', 'Bangabandhu International Conference Center', '2025-06-28 16:00:00', 'Exhibition on the latest in tech innovations', 'assets\\event_banners\\Ai.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:51:21'),
(10, 'Entrepreneurship Meetup', 'Startup Hub Dhaka', '2025-06-30 13:00:00', 'Networking event for entrepreneurs and startups', 'assets\\event_banners\\digital.jpg', 'upcoming', '2025-06-21 22:25:59', '2025-06-21 22:51:59'),
(14, 's', 'sxxss', '2025-06-27 01:58:00', 'ssss', 'assets/event_banners/s_1750564483.jpg', 'upcoming', '2025-06-22 09:54:43', '2025-06-22 09:59:05');

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
  `shipping_address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `status`, `total_amount`, `payment_method`, `shipping_address`) VALUES
(1, 4, '2025-06-18 22:42:35', 'shipped', 2860.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.'),
(3, 4, '2025-06-18 23:28:44', 'cancelled', 1260.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.'),
(4, 4, '2025-06-19 00:42:53', 'pending', 1660.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.'),
(5, 4, '2025-06-19 01:33:24', 'delivered', 1060.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.'),
(6, 4, '2025-06-19 01:48:33', 'delivered', 5260.00, 'online', 'Raghunathpur, Narail, Dhaka , Bangladesh.'),
(7, 4, '2025-06-21 10:46:22', 'pending', 780.00, 'cod', 'Raghunathpur, Narail, Dhaka , Bangladesh.');

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
(10, 7, 5, 1, 520.00);

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `partner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','suspended') DEFAULT 'pending',
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`partner_id`, `user_id`, `status`, `joined_at`) VALUES
(2, 4, 'approved', '2025-06-22 11:34:10');

-- --------------------------------------------------------

--
-- Table structure for table `partner_books`
--

CREATE TABLE `partner_books` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `rent_book_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 4, 4, 'xasssax', '2025-06-18 15:52:52', '2025-06-18 15:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `rent_books`
--

CREATE TABLE `rent_books` (
  `rent_book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `writer` varchar(255) NOT NULL,
  `genre` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `language` varchar(100) DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('visible','hidden','pending','on_transportaion') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Premium', 1200.00, 60, 40, 10, 'You get 27/7 full support, free shipping.', 'active', '2025-06-21 10:33:11', '2025-06-21 10:33:11'),
(2, 'Akash', 1200.00, 60, 10, 10, 'ajnckjiubcj ksjcu', 'active', '2025-06-22 09:39:14', '2025-06-22 09:39:14');

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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `userimageurl` text DEFAULT NULL,
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

INSERT INTO `users` (`user_id`, `username`, `email`, `pass`, `userimageurl`, `create_time`, `update_time`, `email_verified`, `two_step_verification`, `last_login`, `login_count`) VALUES
(4, 'Akash', 'ashikur31169@gmail.com', '$2y$10$SSXmEH8pRsI9GM.9hzFmo.kg13ioDPR6XPFNZEoGQmWvdeCxHBdm.', 'assets\\user_profile\\person.jpg', '2025-06-17 16:06:42', '2025-06-22 21:43:08', 0, 0, '2025-06-22 21:43:08', 20);

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
(22, 4, '::1', '2025-06-22 21:43:08', '2025-06-22 21:43:08', 'active');

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
(4, '2025-02-12', '01777895889', 'Raghunathpur, Narail, Dhaka , Bangladesh.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_otp`
--

CREATE TABLE `user_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_time` datetime DEFAULT current_timestamp(),
  `purpose` enum('payment','two-factor','password_reset','verify_email') NOT NULL,
  `otp_attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_otp`
--

INSERT INTO `user_otp` (`id`, `user_id`, `otp_code`, `otp_time`, `purpose`, `otp_attempts`) VALUES
(3, 4, '140327', '2025-06-17 16:23:25', 'payment', 0),
(4, 4, '959334', '2025-06-17 16:28:27', 'password_reset', 0),
(5, 4, '914637', '2025-06-17 16:30:53', 'password_reset', 0),
(7, 4, '226963', '2025-06-17 17:54:03', 'two-factor', 0),
(8, 4, '114695', '2025-06-17 17:59:35', 'two-factor', 1),
(9, 4, '634445', '2025-06-22 00:38:45', 'password_reset', 1);

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
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `auto_renew` tinyint(1) DEFAULT 0,
  `available_audio` int(11) DEFAULT NULL,
  `available_rent_book` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`user_subscription_id`, `user_id`, `subscription_plan_id`, `start_date`, `end_date`, `status`, `auto_renew`, `available_audio`, `available_rent_book`) VALUES
(1, 4, 1, '2025-06-21 10:50:23', '2025-06-21 06:50:41', 'active', 0, 10, 10);

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
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 4, 6, '2025-06-18 21:41:09'),
(2, 4, 4, '2025-06-18 21:43:13'),
(3, 4, 5, '2025-06-19 01:35:07'),
(4, 4, 7, '2025-06-19 02:11:36');

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
(1, 'J.K. Rowling', 'jkrowling@example.com', 'British author, best known for the Harry Potter series.', 'Edinburgh, UK', 'https://example.com/images/jkrowling.jpg', '2025-06-05 13:00:51'),
(2, 'George R.R. Martin', 'grrmartin@example.com', 'American novelist and creator of \"A Song of Ice and Fire\".', 'Bayonne, NJ, USA', 'https://example.com/images/grrmartin.jpg', '2025-06-05 13:00:51'),
(3, 'Rabindranath Tagore', 'tagore@example.com', 'Nobel laureate poet, musician, and artist from Bengal.', 'Kolkata, India', 'https://example.com/images/tagore.jpg', '2025-06-05 13:00:51'),
(4, 'Humayun Ahmed', 'humayunahmed@example.com', 'Popular Bangladeshi author, dramatist, and filmmaker.', 'Dhaka, Bangladesh', 'https://example.com/images/humayunahmed.jpg', '2025-06-05 13:00:51'),
(5, 'Agatha Christie', 'agathachristie@example.com', 'Renowned British mystery writer and creator of Hercule Poirot.', 'Wallingford, UK', 'https://example.com/images/agathachristie.jpg', '2025-06-05 13:00:51'),
(6, 'Paulo Coelho', 'paulocoelho@example.com', 'Brazilian author of \"The Alchemist\" and other spiritual novels.', 'Rio de Janeiro, Brazil', 'https://example.com/images/paulocoelho.jpg', '2025-06-05 13:00:51'),
(21, 'Kazi Nazrul Islam', 'nazrul@example.com', 'National poet of Bangladesh, known for his revolutionary and spiritual writings.', 'Churulia, West Bengal, India', 'https://example.com/images/nazrul.jpg', '2025-06-05 13:02:08'),
(22, 'Humayun Ahmed', 'humayun@example.com', 'Celebrated Bangladeshi author and filmmaker. Known for Himu and Misir Ali series.', 'Dhanmondi, Dhaka, Bangladesh', 'https://example.com/images/humayun.jpg', '2025-06-05 13:02:08'),
(23, 'Sarat Chandra Chattopadhyay', 'sarat@example.com', 'Famous Bengali novelist known for \"Devdas\", \"Parineeta\", and more.', 'Debanandapur, West Bengal, India', 'https://example.com/images/sarat.jpg', '2025-06-05 13:02:08'),
(24, 'Sunil Gangopadhyay', 'sunil@example.com', 'Modern Bengali poet and novelist, known for \"Sei Somoy\" and \"Nikhilesh\" series.', 'Kolkata, India', 'https://example.com/images/sunil.jpg', '2025-06-05 13:02:08'),
(25, 'Syed Shamsul Haq', 'syedshamsulhaq@example.com', 'Bangladeshi writer, poet, and playwright. Noted for his contributions to Bengali literature.', 'Kurigram, Bangladesh', 'https://example.com/images/syed.jpg', '2025-06-05 13:02:08'),
(26, 'Begum Rokeya', 'rokeya@example.com', 'Pioneer of women’s education and rights in Bengal. Known for \"Sultana’s Dream\".', 'Rangpur, Bangladesh', 'https://example.com/images/rokeya.jpg', '2025-06-05 13:02:08');

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
  ADD KEY `fk_subs_order_subs_id` (`plan_id`);

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
  ADD KEY `fk_sub_audio_access_audiobook` (`audiobook_id`);

--
-- Indexes for table `user_subscription_rent_book_access`
--
ALTER TABLE `user_subscription_rent_book_access`
  ADD PRIMARY KEY (`access_id`),
  ADD KEY `fk_sub_rent_access_subscription` (`user_subscription_id`),
  ADD KEY `fk_sub_rent_access_rent_book` (`rent_book_id`);

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
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `book_categories`
--
ALTER TABLE `book_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `book_genres`
--
ALTER TABLE `book_genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `book_languages`
--
ALTER TABLE `book_languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `book_writers`
--
ALTER TABLE `book_writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `partner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `partner_books`
--
ALTER TABLE `partner_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rent_books`
--
ALTER TABLE `rent_books`
  MODIFY `rent_book_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscription_orders`
--
ALTER TABLE `subscription_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `auth_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_otp`
--
ALTER TABLE `user_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `user_subscription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_subscription_audiobook_access`
--
ALTER TABLE `user_subscription_audiobook_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_subscription_rent_book_access`
--
ALTER TABLE `user_subscription_rent_book_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `writer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
  ADD CONSTRAINT `fk_subs_order_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_sub_audio_access_subscription` FOREIGN KEY (`user_subscription_id`) REFERENCES `user_subscriptions` (`user_subscription_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_subscription_rent_book_access`
--
ALTER TABLE `user_subscription_rent_book_access`
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
