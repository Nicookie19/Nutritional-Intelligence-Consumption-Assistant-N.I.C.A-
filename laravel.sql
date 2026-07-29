-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: May 26, 2026 at 09:37 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laravel`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation_requests`
--

CREATE TABLE `consultation_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `user_experience_id` bigint UNSIGNED DEFAULT NULL,
  `dietitian_id` bigint UNSIGNED DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consultation_requests`
--

INSERT INTO `consultation_requests` (`id`, `user_experience_id`, `dietitian_id`, `preferred_date`, `note`, `status`, `created_at`, `updated_at`) VALUES
(5, 2, 2, '2026-04-08', 'My Progress', 'completed', '2026-04-10 20:27:07', '2026-04-10 20:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `dietitians`
--

CREATE TABLE `dietitians` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `experience_years` tinyint UNSIGNED NOT NULL,
  `patient_count` int UNSIGNED NOT NULL DEFAULT '0',
  `rating` decimal(3,1) NOT NULL DEFAULT '4.5',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dietitians`
--

INSERT INTO `dietitians` (`id`, `name`, `email`, `specialization`, `experience_years`, `patient_count`, `rating`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Sarah Mitchell', 'sarah.mitchell@nutriassist.com', 'Weight Management', 12, 58, 4.9, 'active', '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(2, 'Dr. Joe', 'joe@nutriassist.com', 'Sports Nutrition', 6, 42, 4.8, 'active', '2026-04-02 03:22:42', '2026-04-10 20:32:06'),
(3, 'Dr. Lisa Park', 'l.park@nutriassist.com', 'Clinical Nutrition', 6, 35, 4.7, 'active', '2026-04-02 03:22:42', '2026-04-02 03:22:42');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_requests`
--

CREATE TABLE `feedback_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `user_experience_id` bigint UNSIGNED DEFAULT NULL,
  `dietitian_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `tag_tone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'slate',
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `recommendations` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `submitted_on` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback_requests`
--

INSERT INTO `feedback_requests` (`id`, `user_experience_id`, `dietitian_id`, `title`, `topic`, `tag`, `tag_tone`, `priority`, `status`, `message`, `recommendations`, `is_read`, `submitted_on`, `created_at`, `updated_at`) VALUES
(9, 1, 2, '54646', 'general', 'reply', 'slate', 'medium', 'completed', 'ytdtdtduty', '[]', 1, '2026-04-10', '2026-04-09 20:37:57', '2026-04-09 20:54:06'),
(10, 2, 2, 'Welcome to your plan', 'meal plan', 'New', 'blue', 'medium', 'completed', 'Your plan is ready. Use the meal-plan page to swap foods, add items, and make your own version.', '[\"Activate the plan you want to follow.\", \"Customize meals directly from the meal-plan builder.\", \"Use the food log each day so your stats stay current.\"]', 1, '2026-04-10', '2026-04-09 21:02:28', '2026-04-10 20:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serving_size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `calories` int UNSIGNED NOT NULL,
  `protein` decimal(6,1) NOT NULL,
  `carbs` decimal(6,1) NOT NULL,
  `fat` decimal(6,1) NOT NULL,
  `fiber` decimal(6,1) NOT NULL DEFAULT '0.0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`id`, `name`, `category`, `serving_size`, `calories`, `protein`, `carbs`, `fat`, `fiber`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Grilled Chicken Breast', 'Protein', '100g', 165, 31.0, 0.0, 3.6, 0.0, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(2, 'Brown Rice', 'Grains', '100g', 112, 2.6, 24.0, 0.9, 1.8, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(3, 'Broccoli', 'Vegetables', '100g', 34, 2.8, 7.0, 0.4, 2.6, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(4, 'Salmon Fillet', 'Protein', '100g', 208, 20.0, 0.0, 13.0, 0.0, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(5, 'Greek Yogurt', 'Dairy', '100g', 59, 10.0, 3.6, 0.4, 0.0, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(6, 'Banana', 'Fruits', '1 medium', 89, 1.1, 23.0, 0.3, 2.6, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(7, 'Almonds', 'Nuts', '100g', 579, 21.0, 22.0, 50.0, 12.5, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(8, 'Sweet Potato', 'Vegetables', '100g', 86, 1.6, 20.0, 0.1, 3.0, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(9, 'Eggs', 'Protein', '2 large', 155, 13.0, 1.1, 11.0, 0.0, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(10, 'Avocado', 'Fruits', '100g', 160, 2.0, 8.5, 15.0, 6.7, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(11, 'Oatmeal', 'Grains', '100g', 389, 17.0, 66.0, 7.0, 10.6, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(12, 'Spinach', 'Vegetables', '100g', 23, 2.9, 3.6, 0.4, 2.2, 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42');

-- --------------------------------------------------------

--
-- Table structure for table `food_log_entries`
--

CREATE TABLE `food_log_entries` (
  `id` bigint UNSIGNED NOT NULL,
  `user_experience_id` bigint UNSIGNED NOT NULL,
  `food_item_id` bigint UNSIGNED DEFAULT NULL,
  `meal_slot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `food_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serving_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `calories` int UNSIGNED NOT NULL,
  `protein` decimal(6,1) NOT NULL,
  `carbs` decimal(6,1) NOT NULL DEFAULT '0.0',
  `fat` decimal(6,1) NOT NULL DEFAULT '0.0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_plans`
--

CREATE TABLE `meal_plans` (
  `id` bigint UNSIGNED NOT NULL,
  `user_experience_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `daily_calories` int UNSIGNED NOT NULL DEFAULT '2000',
  `tags` json DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `is_template` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meal_plans`
--

INSERT INTO `meal_plans` (`id`, `user_experience_id`, `name`, `description`, `daily_calories`, `tags`, `rating`, `is_template`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'High Protein Plan', 'Perfect for muscle building and recovery.', 2100, '[\"High Protein\", \"Muscle Building\"]', 4.8, 1, 0, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(2, NULL, 'Balanced Nutrition', 'Well-rounded meals for overall health.', 2000, '[\"Balanced\", \"Healthy\"]', 4.8, 1, 0, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(3, NULL, 'Plant-Based Power', 'Nutrient-dense vegetarian meals.', 1850, '[\"Vegetarian\", \"Plant-Based\"]', 4.7, 1, 0, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(4, 1, 'High Protein Plan Copy', 'Perfect for muscle building and recovery.', 2100, '[\"High Protein\", \"Muscle Building\"]', 4.8, 0, 0, '2026-04-02 04:10:20', '2026-04-09 20:42:21'),
(5, 1, 'High Protein Plan Copy 0442', 'Perfect for muscle building and recovery.', 2100, '[\"High Protein\", \"Muscle Building\"]', 4.8, 0, 0, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(6, 2, 'High Protein Plan Copy', 'Perfect for muscle building and recovery.', 2100, '[\"High Protein\", \"Muscle Building\"]', 4.8, 0, 0, '2026-04-09 21:02:27', '2026-05-23 00:17:44'),
(7, 3, 'Plant-Based Power Copy 0647', 'Nutrient-dense vegetarian meals.', 1850, '[\"Vegetarian\", \"Plant-Based\"]', 4.7, 0, 0, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(8, 2, 'Balanced Nutrition Copy 0425', 'Well-rounded meals for overall health.', 2000, '[\"Balanced\", \"Healthy\"]', 4.8, 0, 1, '2026-04-10 20:25:30', '2026-05-23 00:17:44');

-- --------------------------------------------------------

--
-- Table structure for table `meal_plan_items`
--

CREATE TABLE `meal_plan_items` (
  `id` bigint UNSIGNED NOT NULL,
  `meal_plan_id` bigint UNSIGNED NOT NULL,
  `food_item_id` bigint UNSIGNED DEFAULT NULL,
  `meal_slot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serving_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meal_plan_items`
--

INSERT INTO `meal_plan_items` (`id`, `meal_plan_id`, `food_item_id`, `meal_slot`, `item_name`, `serving_label`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 'Breakfast', 'Eggs', '2 large', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(2, 1, 5, 'Breakfast', 'Greek Yogurt', '150g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(3, 1, 6, 'Breakfast', 'Banana', '1 medium', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(4, 1, 1, 'Lunch', 'Grilled Chicken Breast', '150g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(5, 1, 2, 'Lunch', 'Brown Rice', '150g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(6, 1, 3, 'Lunch', 'Broccoli', '100g', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(7, 1, 4, 'Dinner', 'Salmon Fillet', '150g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(8, 1, 8, 'Dinner', 'Sweet Potato', '180g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(9, 1, 12, 'Dinner', 'Spinach', '80g', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(10, 1, 7, 'Snacks', 'Almonds', '30g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(11, 2, 11, 'Breakfast', 'Oatmeal', '80g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(12, 2, 6, 'Breakfast', 'Banana', '1 medium', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(13, 2, 1, 'Lunch', 'Grilled Chicken Breast', '120g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(14, 2, 2, 'Lunch', 'Brown Rice', '120g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(15, 2, 3, 'Lunch', 'Broccoli', '120g', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(16, 2, 4, 'Dinner', 'Salmon Fillet', '120g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(17, 2, 8, 'Dinner', 'Sweet Potato', '160g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(18, 2, 12, 'Dinner', 'Spinach', '60g', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(19, 2, 5, 'Snacks', 'Greek Yogurt', '120g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(20, 2, 7, 'Snacks', 'Almonds', '20g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(21, 3, 11, 'Breakfast', 'Oatmeal', '80g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(22, 3, 6, 'Breakfast', 'Banana', '1 medium', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(23, 3, 7, 'Breakfast', 'Almonds', '20g', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(24, 3, 8, 'Lunch', 'Sweet Potato', '180g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(25, 3, 12, 'Lunch', 'Spinach', '90g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(26, 3, 10, 'Lunch', 'Avocado', '100g', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(27, 3, 2, 'Dinner', 'Brown Rice', '140g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(28, 3, 3, 'Dinner', 'Broccoli', '140g', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(29, 3, 5, 'Dinner', 'Greek Yogurt', '100g', 3, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(30, 3, 5, 'Snacks', 'Greek Yogurt', '120g', 1, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(31, 3, 6, 'Snacks', 'Banana', '1 medium', 2, '2026-04-02 03:22:42', '2026-04-02 03:22:42'),
(32, 4, 9, 'Breakfast', 'Eggs', '2 large', 1, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(33, 4, 5, 'Breakfast', 'Greek Yogurt', '150g', 2, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(34, 4, 6, 'Breakfast', 'Banana', '1 medium', 3, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(35, 4, 4, 'Dinner', 'Salmon Fillet', '150g', 1, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(36, 4, 8, 'Dinner', 'Sweet Potato', '180g', 2, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(37, 4, 12, 'Dinner', 'Spinach', '80g', 3, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(38, 4, 1, 'Lunch', 'Grilled Chicken Breast', '150g', 1, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(39, 4, 2, 'Lunch', 'Brown Rice', '150g', 2, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(40, 4, 3, 'Lunch', 'Broccoli', '100g', 3, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(41, 4, 7, 'Snacks', 'Almonds', '30g', 1, '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(42, 5, 9, 'Breakfast', 'Eggs', '2 large', 1, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(43, 5, 5, 'Breakfast', 'Greek Yogurt', '150g', 2, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(44, 5, 6, 'Breakfast', 'Banana', '1 medium', 3, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(45, 5, 4, 'Dinner', 'Salmon Fillet', '150g', 1, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(46, 5, 8, 'Dinner', 'Sweet Potato', '180g', 2, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(47, 5, 12, 'Dinner', 'Spinach', '80g', 3, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(48, 5, 1, 'Lunch', 'Grilled Chicken Breast', '150g', 1, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(49, 5, 2, 'Lunch', 'Brown Rice', '150g', 2, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(50, 5, 3, 'Lunch', 'Broccoli', '100g', 3, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(51, 5, 7, 'Snacks', 'Almonds', '30g', 1, '2026-04-09 20:42:21', '2026-04-09 20:42:21'),
(62, 7, 11, 'Breakfast', 'Oatmeal', '80g', 1, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(63, 7, 6, 'Breakfast', 'Banana', '1 medium', 2, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(64, 7, 7, 'Breakfast', 'Almonds', '20g', 3, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(65, 7, 2, 'Dinner', 'Brown Rice', '140g', 1, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(66, 7, 3, 'Dinner', 'Broccoli', '140g', 2, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(67, 7, 5, 'Dinner', 'Greek Yogurt', '100g', 3, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(68, 7, 8, 'Lunch', 'Sweet Potato', '180g', 1, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(69, 7, 12, 'Lunch', 'Spinach', '90g', 2, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(70, 7, 10, 'Lunch', 'Avocado', '100g', 3, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(71, 7, 5, 'Snacks', 'Greek Yogurt', '120g', 1, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(72, 7, 6, 'Snacks', 'Banana', '1 medium', 2, '2026-04-09 22:47:54', '2026-04-09 22:47:54'),
(73, 8, 11, 'Breakfast', 'Oatmeal', '80g', 1, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(74, 8, 6, 'Breakfast', 'Banana', '1 medium', 2, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(75, 8, 4, 'Dinner', 'Salmon Fillet', '120g', 1, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(76, 8, 8, 'Dinner', 'Sweet Potato', '160g', 2, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(77, 8, 12, 'Dinner', 'Spinach', '60g', 3, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(78, 8, 1, 'Lunch', 'Grilled Chicken Breast', '120g', 1, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(79, 8, 2, 'Lunch', 'Brown Rice', '120g', 2, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(80, 8, 3, 'Lunch', 'Broccoli', '120g', 3, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(81, 8, 5, 'Snacks', 'Greek Yogurt', '120g', 1, '2026-04-10 20:25:30', '2026-04-10 20:25:30'),
(82, 8, 7, 'Snacks', 'Almonds', '20g', 2, '2026-04-10 20:25:30', '2026-04-10 20:25:30');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2024_10_17_120000_add_is_admin_to_users_table', 1),
(5, '2026_04_01_111513_create_consultation_requests_table', 1),
(6, '2026_04_01_111513_create_dietitians_table', 1),
(7, '2026_04_01_111513_create_feedback_requests_table', 1),
(8, '2026_04_01_111513_create_food_items_table', 1),
(9, '2026_04_01_111513_create_meal_plan_items_table', 1),
(10, '2026_04_01_111513_create_meal_plans_table', 1),
(11, '2026_04_01_111513_create_user_experiences_table', 1),
(12, '2026_04_01_111617_create_food_log_entries_table', 1),
(13, '2026_04_01_124105_create_planned_meal_entries_table', 1),
(14, '2026_04_02_021721_add_profile_fields_to_users_table', 1),
(15, '2026_04_02_030533_add_user_id_to_user_experiences_table', 1),
(16, '2026_04_11_120200_add_carbs_and_fat_to_food_log_entries_table', 2),
(17, '2026_04_11_120800_add_macros_to_planned_meal_entries_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `planned_meal_entries`
--

CREATE TABLE `planned_meal_entries` (
  `id` bigint UNSIGNED NOT NULL,
  `user_experience_id` bigint UNSIGNED NOT NULL,
  `scheduled_date` date NOT NULL,
  `meal_slot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `food_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grams` int UNSIGNED NOT NULL,
  `calories` int UNSIGNED NOT NULL,
  `protein` decimal(6,1) NOT NULL DEFAULT '0.0',
  `carbs` decimal(6,1) NOT NULL DEFAULT '0.0',
  `fat` decimal(6,1) NOT NULL DEFAULT '0.0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('XQUmloVHXknbdKp4HxAlQxcjDrFbkB25Lb11Tzzi', 3, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 'eyJfdG9rZW4iOiJ4RDhNRVNua0Zybno2cVFIU0JkcTFTNVIzaFpJblJlNUFKRGljdGR4IiwicG9ydGFsX3Nlc3Npb25fa2V5IjoiZjdmZjc1OTQtZDJhNy00NzBmLTk0MDgtOTgyNzZiZTJmZTRmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hZG1pbiIsInJvdXRlIjoiYWRtaW4uZGFzaGJvYXJkIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwidXJsIjpbXSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjN9', 1779524525);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `address`, `age`, `gender`, `date_of_birth`, `email_verified_at`, `is_admin`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(3, 'Admin', 'admin@nutriassist.com', 'Fr. Selga St.,Davao City', 20, 'male', '2005-08-28', NULL, 1, '$2y$12$8dbie3ACkTB0p0XDoLmxn.S4nG9l5hxqz7vPAeG00Uvj/MMoWMbzC', '9IyOzkcDjtqucDSvTZ2zjDKK5vR8GiMDqb6sTQgWFyC3Jaze9qhf6F64Uuh4', '2026-04-02 04:10:20', '2026-04-02 04:10:20'),
(4, 'Nico Mahipus', 'nmahipus_240000001368@uic.edu.ph', 'Fr. Selga St.,Davao City', 20, 'male', '2005-08-28', NULL, 1, '$2y$12$5krml5tjVpJeDEsgKLedPeMrbNiulB/vNfj4kMQcOEDc/b8cQZHPy', 'LoqKCsSVz7wfSSu1M34FoUMXFUtFI0r3ibUKyHvMNA9MWsKCKPb0ROuPzlpk', '2026-04-02 04:51:48', '2026-04-02 04:51:48'),
(5, 'Nico Mahipus', 'nicomahipus69420@gmail.com', 'Fr. Selga St.,Davao City', 20, 'male', '2005-08-28', NULL, 0, '$2y$12$Lfu7wgVcHLxb6Ejnr3DnmubUF.0Ovd9EJE2wXG3E3EHZgeWAj9rES', 'CgugJeYuj7qkDCcX6zFN5hiuluSWp9oqwIyqrW7lK3APB6Jcv09K0kpXW6xy', '2026-04-02 05:38:05', '2026-04-02 05:38:05'),
(10, 'Glydel Mae Magdosa', 'gmagdosa@gmail.com', 'Dona Assuncion Davao City', 19, 'female', '2005-05-11', NULL, 0, '$2y$12$bQp6zpbnqNDHCEdnUKmAw.HSMRB24uoCoCLvS9D9CcrdzHLqZOUJq', 'x5HqQHe1BIs10iz6mgckvfDvRRWbKJve3d8yWkJqpoWCjtvzaa7Ge5z5mlqd', '2026-04-09 22:47:22', '2026-04-09 22:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_experiences`
--

CREATE TABLE `user_experiences` (
  `id` bigint UNSIGNED NOT NULL,
  `session_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active_meal_plan_id` bigint UNSIGNED DEFAULT NULL,
  `active_dietitian_id` bigint UNSIGNED DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `age` tinyint UNSIGNED NOT NULL DEFAULT '28',
  `gender` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Male',
  `activity_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `primary_goal` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `height_cm` smallint UNSIGNED NOT NULL DEFAULT '1',
  `current_weight_kg` decimal(5,1) NOT NULL DEFAULT '1.0',
  `target_weight_kg` decimal(5,1) NOT NULL DEFAULT '1.0',
  `starting_weight_kg` decimal(5,1) NOT NULL DEFAULT '1.0',
  `bmi_history` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_experiences`
--

INSERT INTO `user_experiences` (`id`, `session_key`, `active_meal_plan_id`, `active_dietitian_id`, `full_name`, `age`, `gender`, `activity_level`, `primary_goal`, `height_cm`, `current_weight_kg`, `target_weight_kg`, `starting_weight_kg`, `bmi_history`, `created_at`, `updated_at`, `user_id`) VALUES
(2, '5008660d-d604-460f-9c81-cd837544d660', 8, 2, 'Alex Johnson', 32, 'Male', 'Lightly Active', 'Weight Loss', 173, 75.0, 65.0, 1.0, '[{\"bmi\": \"25.1\", \"date\": \"Apr 10\", \"weight\": \"75.0 kg\"}, {\"bmi\": \"25.1\", \"date\": \"Apr 11\", \"weight\": \"75.0 kg\"}, {\"bmi\": \"25.1\", \"date\": \"Apr 11\", \"weight\": \"75.0 kg\"}]', '2026-04-09 21:02:27', '2026-04-10 20:28:45', 5),
(3, '32adc489-4cd7-46c8-8016-2f1f48a00c7f', 7, 2, '', 28, 'Male', '', '', 1, 1.0, 1.0, 1.0, NULL, '2026-04-09 22:47:22', '2026-04-09 22:47:54', 10),
(4, 'f7ff7594-d2a7-470f-9408-98276be2fe4f', NULL, 2, '', 28, 'Male', '', '', 1, 1.0, 1.0, 1.0, NULL, '2026-04-10 20:11:49', '2026-04-10 20:11:49', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dietitians`
--
ALTER TABLE `dietitians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dietitians_email_unique` (`email`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `feedback_requests`
--
ALTER TABLE `feedback_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `food_items_name_unique` (`name`);

--
-- Indexes for table `food_log_entries`
--
ALTER TABLE `food_log_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `food_log_entries_user_experience_id_foreign` (`user_experience_id`),
  ADD KEY `food_log_entries_food_item_id_foreign` (`food_item_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meal_plan_items`
--
ALTER TABLE `meal_plan_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `planned_meal_entries`
--
ALTER TABLE `planned_meal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `planned_meals_user_date_slot_unique` (`user_experience_id`,`scheduled_date`,`meal_slot`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_experiences`
--
ALTER TABLE `user_experiences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_experiences_session_key_unique` (`session_key`),
  ADD KEY `user_experiences_active_dietitian_id_foreign` (`active_dietitian_id`),
  ADD KEY `user_experiences_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dietitians`
--
ALTER TABLE `dietitians`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_requests`
--
ALTER TABLE `feedback_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `food_log_entries`
--
ALTER TABLE `food_log_entries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `meal_plan_items`
--
ALTER TABLE `meal_plan_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `planned_meal_entries`
--
ALTER TABLE `planned_meal_entries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_experiences`
--
ALTER TABLE `user_experiences`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `food_log_entries`
--
ALTER TABLE `food_log_entries`
  ADD CONSTRAINT `food_log_entries_food_item_id_foreign` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `food_log_entries_user_experience_id_foreign` FOREIGN KEY (`user_experience_id`) REFERENCES `user_experiences` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `planned_meal_entries`
--
ALTER TABLE `planned_meal_entries`
  ADD CONSTRAINT `planned_meal_entries_user_experience_id_foreign` FOREIGN KEY (`user_experience_id`) REFERENCES `user_experiences` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_experiences`
--
ALTER TABLE `user_experiences`
  ADD CONSTRAINT `user_experiences_active_dietitian_id_foreign` FOREIGN KEY (`active_dietitian_id`) REFERENCES `dietitians` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_experiences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
