-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 05, 2026 at 05:55 AM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tutoress_paysomeone`
--

-- --------------------------------------------------------

--
-- Table structure for table `prox_academic_level`
--

CREATE TABLE `prox_academic_level` (
  `academic_level_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_title` varchar(255) DEFAULT NULL,
  `degree` varchar(255) DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_academic_level_site`
--

CREATE TABLE `prox_academic_level_site` (
  `academic_level_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_activitylog`
--

CREATE TABLE `prox_activitylog` (
  `activitylog_id` bigint(20) UNSIGNED NOT NULL,
  `user_object` varchar(64) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_ip` varchar(64) NOT NULL,
  `object` varchar(64) NOT NULL,
  `object_id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(64) NOT NULL,
  `data` text DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_additional_service`
--

CREATE TABLE `prox_additional_service` (
  `additional_service_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `tariff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `writer_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pages` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `charts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `slides` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `writer_samples` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `used_sources` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `expert_proofreading` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `progressive_delivery` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `total` float(14,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime DEFAULT NULL,
  `is_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_application_essay`
--

CREATE TABLE `prox_application_essay` (
  `application_essay_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_configuration`
--

CREATE TABLE `prox_configuration` (
  `configuration_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_content_feel`
--

CREATE TABLE `prox_content_feel` (
  `content_feel_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_coupon`
--

CREATE TABLE `prox_coupon` (
  `coupon_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `coupon_code` varchar(255) NOT NULL,
  `coupon_type` int(10) NOT NULL DEFAULT 1,
  `coupon_value` int(10) DEFAULT NULL,
  `writer_category_id` int(10) DEFAULT NULL,
  `service_type_id` int(10) DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `one_time` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `expiry` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_credit_fine`
--

CREATE TABLE `prox_credit_fine` (
  `credit_fine_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('credit','fine') NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `description` longtext DEFAULT NULL,
  `resolved` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `requested` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `requested_at` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_currency`
--

CREATE TABLE `prox_currency` (
  `currency_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `iso_code` char(5) NOT NULL,
  `symbol` char(5) NOT NULL,
  `country_code` char(5) DEFAULT NULL,
  `conversion_rate` float(14,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `last_checked` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_customer`
--

CREATE TABLE `prox_customer` (
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `google_analytics_id` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `password` varchar(255) NOT NULL,
  `new_password` varchar(255) DEFAULT NULL,
  `reset_password_token` varchar(255) DEFAULT NULL,
  `reset_password_validity` datetime DEFAULT NULL,
  `last_passwd_gen` datetime DEFAULT NULL,
  `secure_key` varchar(255) NOT NULL DEFAULT '-1',
  `policy_accepted` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `policy_accepted_at` datetime DEFAULT NULL,
  `total_spent` float(14,2) NOT NULL DEFAULT 0.00,
  `referrer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `affiliate_used` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `affiliate_balance` float(14,2) NOT NULL DEFAULT 0.00,
  `last_academic_level` bigint(20) UNSIGNED DEFAULT NULL,
  `last_payment_method` bigint(20) UNSIGNED DEFAULT NULL,
  `is_subscribed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_test` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_banned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `push_notifications` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `reg_date` datetime DEFAULT NULL,
  `social_connected` tinyint(1) UNSIGNED DEFAULT 0,
  `social_id` varchar(255) DEFAULT NULL,
  `wallet_balance` float(14,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(10) NOT NULL DEFAULT 0,
  `employee_pool_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_anonymous` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `anonymous_id_saved` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_customer_wallet`
--

CREATE TABLE `prox_customer_wallet` (
  `wallet_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `wallet_balance` float(14,2) NOT NULL DEFAULT 0.00,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_deadline`
--

CREATE TABLE `prox_deadline` (
  `deadline_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `academic_level_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price_per_page` float(14,4) DEFAULT 0.0000,
  `hrs_customer` int(10) NOT NULL,
  `hrs_writer` int(10) NOT NULL,
  `min_pages` int(10) DEFAULT 1,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `career_level_id` bigint(20) UNSIGNED DEFAULT NULL,
  `price_modifier` float(14,2) DEFAULT 0.00,
  `is_academic_writing` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `is_calculations` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_programming` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_article_writing` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_resume` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_deliverable`
--

CREATE TABLE `prox_deliverable` (
  `deliverable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_descriptor`
--

CREATE TABLE `prox_descriptor` (
  `descriptor_id` bigint(20) UNSIGNED NOT NULL,
  `discipline_id` bigint(20) UNSIGNED NOT NULL,
  `task_size_id` bigint(20) UNSIGNED NOT NULL,
  `example` text NOT NULL,
  `is_calculations` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_programming` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_descriptor_deliverable`
--

CREATE TABLE `prox_descriptor_deliverable` (
  `descriptor_deliverable_id` bigint(20) UNSIGNED NOT NULL,
  `descriptor_id` bigint(20) UNSIGNED NOT NULL,
  `deliverable_id` bigint(20) UNSIGNED NOT NULL,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_discipline`
--

CREATE TABLE `prox_discipline` (
  `discipline_id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_group` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_complex_assignment` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `is_academic_writing` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `is_calculations` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_programming` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_article_writing` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_employee`
--

CREATE TABLE `prox_employee` (
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `employee_group` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL DEFAULT 'male',
  `phone` varchar(50) DEFAULT NULL,
  `writer_category` int(10) NOT NULL DEFAULT 0,
  `academic_level` int(10) NOT NULL DEFAULT 0,
  `employee_rating` int(10) NOT NULL DEFAULT 0,
  `employee_status` int(10) NOT NULL DEFAULT 0,
  `avatar` varchar(255) DEFAULT NULL,
  `about` longtext DEFAULT NULL,
  `facebook_profile` varchar(255) DEFAULT NULL,
  `linkedin_profile` varchar(255) DEFAULT NULL,
  `payment_method` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_method_value` varchar(255) DEFAULT NULL,
  `last_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `total_paid` float(14,2) NOT NULL DEFAULT 0.00,
  `total_orders_revised` int(10) NOT NULL DEFAULT 0,
  `total_orders_rejected` int(10) NOT NULL DEFAULT 0,
  `email_notifications` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `reset_password_token` varchar(255) DEFAULT NULL,
  `reset_password_validity` datetime DEFAULT NULL,
  `last_passwd_gen` datetime DEFAULT NULL,
  `secure_key` varchar(255) NOT NULL DEFAULT '-1',
  `policy_accepted` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `policy_accepted_at` datetime DEFAULT NULL,
  `is_started` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_banned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `push_notifications` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `live_notifications_counter` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `live_notifications_lastid` bigint(20) UNSIGNED DEFAULT NULL,
  `live_messages_counter` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `live_messages_lastid` bigint(20) UNSIGNED DEFAULT NULL,
  `number_of_takes` int(10) UNSIGNED DEFAULT 0,
  `timezone` varchar(255) DEFAULT NULL,
  `notification_sound` tinyint(1) NOT NULL DEFAULT 0,
  `cpp_type` enum('system','custom') NOT NULL DEFAULT 'system',
  `cpp_amount` float(14,2) NOT NULL DEFAULT 0.00,
  `last_login` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `reg_date` datetime DEFAULT NULL,
  `employee_dashboard` enum('academic','article','all') NOT NULL DEFAULT 'academic',
  `cpw_type` enum('system','custom') NOT NULL DEFAULT 'system',
  `cpw_amount` float(14,4) NOT NULL DEFAULT 0.0000,
  `has_academic_access` tinyint(1) UNSIGNED DEFAULT 1,
  `has_article_access` tinyint(1) UNSIGNED DEFAULT 0,
  `has_programming_access` tinyint(1) UNSIGNED DEFAULT 0,
  `has_calculations_access` tinyint(1) UNSIGNED DEFAULT 0,
  `employee_pool_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `highest_academic_degree` varchar(64) DEFAULT NULL,
  `academic_degree_major` text DEFAULT NULL,
  `software` text DEFAULT NULL,
  `proficient_disciplines` text DEFAULT NULL,
  `native_language` varchar(255) DEFAULT NULL,
  `other_languages` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `referrer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `referrer_source` varchar(255) DEFAULT NULL,
  `affiliate_used` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `affiliate_balance` float(14,2) NOT NULL DEFAULT 0.00,
  `is_application_completed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `available_247` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `available_urgent_orders` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_profile_completed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_guide_complete` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `guide_completed_at` datetime DEFAULT NULL,
  `is_writingprompt_started` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `writingprompt_started_at` datetime DEFAULT NULL,
  `writing_prompt_question` text DEFAULT NULL,
  `writing_prompt_answer` text DEFAULT NULL,
  `is_writingprompt_complete` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `writingprompt_completed_at` datetime DEFAULT NULL,
  `is_essay_complete` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `application_essays_ids` text DEFAULT NULL,
  `essay_completed_at` datetime DEFAULT NULL,
  `is_samples_complete` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `samples_completed_at` datetime DEFAULT NULL,
  `is_cv_complete` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `cv_completed_at` datetime DEFAULT NULL,
  `is_degree_cert_complete` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `degree_cert_completed_at` datetime DEFAULT NULL,
  `is_application_approved` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `application_approved_at` datetime DEFAULT NULL,
  `is_application_rejected` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `application_rejected_at` datetime DEFAULT NULL,
  `is_application_locked` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `application_locked_at` datetime DEFAULT NULL,
  `is_skip_editor` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `two_factor_type` enum('email','sms','google') DEFAULT NULL,
  `two_factor_key` varchar(64) DEFAULT NULL,
  `two_factor_gsecret` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_employee_file`
--

CREATE TABLE `prox_employee_file` (
  `employee_file_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uploader_id` bigint(20) UNSIGNED DEFAULT NULL,
  `handle` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `size` int(10) NOT NULL DEFAULT 0,
  `source` text NOT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_employee_pool`
--

CREATE TABLE `prox_employee_pool` (
  `employee_pool_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(155) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(64) DEFAULT NULL,
  `badge` text DEFAULT NULL,
  `requirements` longtext DEFAULT NULL,
  `benefits` longtext DEFAULT NULL,
  `status` enum('active','disabled','hidden') NOT NULL DEFAULT 'active',
  `rating` int(10) NOT NULL DEFAULT 0,
  `order` int(10) NOT NULL DEFAULT 0,
  `urgent_orders` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `new_customers` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `auto_delivery` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `base_price` int(10) NOT NULL DEFAULT 0,
  `price_per_page` float(14,2) NOT NULL DEFAULT 0.00,
  `price_per_word` float(14,4) NOT NULL DEFAULT 0.0000,
  `academic_level` int(10) NOT NULL,
  `writer_category` int(10) NOT NULL,
  `takes` int(10) NOT NULL DEFAULT 0,
  `bids` int(10) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_english_type`
--

CREATE TABLE `prox_english_type` (
  `english_type_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_error_log`
--

CREATE TABLE `prox_error_log` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `site_domain` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_fine_type`
--

CREATE TABLE `prox_fine_type` (
  `fine_type_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(155) NOT NULL,
  `type` enum('percentage','amount') NOT NULL,
  `value` float(14,2) NOT NULL DEFAULT 0.00,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_hook`
--

CREATE TABLE `prox_hook` (
  `hook_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `title` varchar(64) NOT NULL,
  `description` text DEFAULT NULL,
  `position` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_hook_alias`
--

CREATE TABLE `prox_hook_alias` (
  `hook_id_alias` bigint(20) UNSIGNED NOT NULL,
  `alias` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_hook_module`
--

CREATE TABLE `prox_hook_module` (
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `hook_id` bigint(20) UNSIGNED NOT NULL,
  `position` tinyint(2) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_item_pricing`
--

CREATE TABLE `prox_item_pricing` (
  `item_pricing_id` bigint(20) UNSIGNED NOT NULL,
  `discipline_id` bigint(20) UNSIGNED NOT NULL,
  `task_size_id` bigint(20) UNSIGNED NOT NULL,
  `price` float(14,2) NOT NULL DEFAULT 0.00,
  `is_calculations` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_programming` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_mail`
--

CREATE TABLE `prox_mail` (
  `mail_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `template` varchar(255) NOT NULL,
  `subject` text DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_mailing_list`
--

CREATE TABLE `prox_mailing_list` (
  `mail_id` int(10) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_mail_template`
--

CREATE TABLE `prox_mail_template` (
  `mail_template_id` bigint(20) UNSIGNED NOT NULL,
  `template` varchar(255) NOT NULL,
  `subject` text DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `plaintext` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_disabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_module`
--

CREATE TABLE `prox_module` (
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `version` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_mpesa_payout`
--

CREATE TABLE `prox_mpesa_payout` (
  `mpesa_payout_id` bigint(20) UNSIGNED NOT NULL,
  `payment_request_id` bigint(20) UNSIGNED NOT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `phone` varchar(50) NOT NULL,
  `transaction_id` text DEFAULT NULL,
  `is_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `paid_at` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_news`
--

CREATE TABLE `prox_news` (
  `news_id` bigint(20) UNSIGNED NOT NULL,
  `title` text NOT NULL,
  `content` longtext DEFAULT NULL,
  `recipient` varchar(50) NOT NULL DEFAULT 'all',
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_news_view`
--

CREATE TABLE `prox_news_view` (
  `news_view_id` bigint(20) UNSIGNED NOT NULL,
  `news_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_notification`
--

CREATE TABLE `prox_notification` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `to_department` varchar(255) NOT NULL,
  `to_user_id` bigint(20) UNSIGNED NOT NULL,
  `from_department` varchar(255) NOT NULL,
  `from_user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `node_type` varchar(255) NOT NULL,
  `node_url` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order`
--

CREATE TABLE `prox_order` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `tracking_id` varchar(255) DEFAULT NULL,
  `form_type_id` bigint(20) UNSIGNED NOT NULL,
  `form_type` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `currency_to_usd_rate` float(14,2) NOT NULL DEFAULT 0.00,
  `gmt` int(10) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `writer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `requested_writer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status_id` bigint(20) UNSIGNED DEFAULT 0,
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `service_type` bigint(20) UNSIGNED NOT NULL DEFAULT 37,
  `tariff_id` bigint(20) UNSIGNED NOT NULL,
  `title` text NOT NULL,
  `academic_level_id` bigint(20) UNSIGNED NOT NULL,
  `paper_type_id` bigint(20) UNSIGNED NOT NULL,
  `paper_type_option` text DEFAULT NULL,
  `topic_category_id` bigint(20) UNSIGNED NOT NULL,
  `topic_category_option` text DEFAULT NULL,
  `is_complex_assignment` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `paper_format_id` bigint(20) UNSIGNED NOT NULL,
  `paper_format_option` text DEFAULT NULL,
  `writer_category_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `writer_percent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `pages` decimal(5,1) DEFAULT NULL,
  `charts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `slides` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sources` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `spacing` enum('single','double') NOT NULL DEFAULT 'double',
  `paper_details` longtext DEFAULT NULL,
  `comments` longtext DEFAULT NULL,
  `writer_samples` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `used_sources` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `expert_proofreading` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `progressive_delivery` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `vip_support` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `plagiarism_report` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `draft_outline` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `raw_cost` float(14,2) NOT NULL DEFAULT 0.00,
  `total` float(14,2) NOT NULL DEFAULT 0.00,
  `tax` float(14,2) NOT NULL DEFAULT 0.00,
  `discount` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `discount_type` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `price_per_page` float(14,2) NOT NULL DEFAULT 0.00,
  `total_paid` float(14,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime DEFAULT NULL,
  `is_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `writer_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `is_writer_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `writer_paid_at` datetime DEFAULT NULL,
  `is_writer_confirmed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `editor_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `is_editor_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `editor_paid_at` datetime DEFAULT NULL,
  `is_editor_confirmed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `hrs_customer` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `customer_deadline` datetime DEFAULT NULL,
  `hrs_writer` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `writer_deadline` datetime DEFAULT NULL,
  `writer_assigned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `writer_assigned_at` datetime DEFAULT NULL,
  `editor_assigned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `editor_assigned_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `ordermanager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ordermanager_assigned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `ordermanager_assigned_at` datetime DEFAULT NULL,
  `ordermanager_pay` float(14,2) DEFAULT 0.00,
  `is_ordermanager_paid` tinyint(1) UNSIGNED DEFAULT 0,
  `ordermanager_paid_at` datetime DEFAULT NULL,
  `excel_sheets` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `order_type` enum('academic','article') NOT NULL DEFAULT 'academic',
  `coupon_id` bigint(20) UNSIGNED DEFAULT NULL,
  `price_modifier` float(14,2) DEFAULT 0.00,
  `task_size_id` int(10) UNSIGNED DEFAULT NULL,
  `task_item_price` float(14,2) DEFAULT 0.00,
  `software` varchar(255) DEFAULT NULL,
  `words` int(10) UNSIGNED DEFAULT 0,
  `keywords` varchar(255) DEFAULT NULL,
  `price_per_word` float(14,4) DEFAULT 0.0000,
  `english_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_audience_id` bigint(20) UNSIGNED DEFAULT NULL,
  `writing_tone_id` bigint(20) UNSIGNED DEFAULT NULL,
  `writing_style_id` bigint(20) UNSIGNED DEFAULT NULL,
  `content_feel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_pd_schedule_accepted` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `pd_schedule_accepted_at` datetime DEFAULT NULL,
  `is_pd_schedule_rejected` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `pd_schedule_rejected_at` datetime DEFAULT NULL,
  `is_new_customer` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `current_writer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `current_editor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `writer_revision_confirmed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_bid`
--

CREATE TABLE `prox_order_bid` (
  `bid_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `writer_id` bigint(20) UNSIGNED NOT NULL,
  `bid_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `is_assigned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_employee`
--

CREATE TABLE `prox_order_employee` (
  `order_employee_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_type` enum('writer','editor','ordermanager') NOT NULL DEFAULT 'writer',
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `hrs_employee` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `employee_deadline` datetime DEFAULT NULL,
  `employee_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `is_employee_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `employee_paid_at` datetime DEFAULT NULL,
  `is_auto_assigned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_employee_assigned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `employee_assigned_at` datetime DEFAULT NULL,
  `is_employee_confirmed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `employee_confirmed_at` datetime DEFAULT NULL,
  `is_employee_declined` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `employee_declined_at` datetime DEFAULT NULL,
  `is_completed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_delivered` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_cancelled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_revised` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_disputed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_refunded` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_late` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_file`
--

CREATE TABLE `prox_order_file` (
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uploader_id` bigint(20) UNSIGNED NOT NULL,
  `file_type_id` int(10) NOT NULL,
  `file_description` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `size` int(10) NOT NULL DEFAULT 0,
  `source` text NOT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `is_part` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_final_file` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `approved` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `approved_at` datetime DEFAULT NULL,
  `downloaded` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `downloaded_at` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_message`
--

CREATE TABLE `prox_order_message` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `from_department` bigint(20) UNSIGNED NOT NULL,
  `to_department` bigint(20) UNSIGNED NOT NULL,
  `subject` text DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `is_deleted` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `is_viewed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `viewed_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_meta`
--

CREATE TABLE `prox_order_meta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_pd_schedule`
--

CREATE TABLE `prox_order_pd_schedule` (
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `status_id` int(10) NOT NULL DEFAULT 0,
  `pages` int(10) NOT NULL DEFAULT 0,
  `slides` int(10) NOT NULL DEFAULT 0,
  `charts` int(10) NOT NULL DEFAULT 0,
  `comment` text DEFAULT NULL,
  `writer_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `is_writer_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `client_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `is_paid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `client_deadline` datetime DEFAULT NULL,
  `writer_deadline` datetime DEFAULT NULL,
  `is_completed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `is_approved` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `approved_at` datetime DEFAULT NULL,
  `is_accepted` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `accepted_at` datetime DEFAULT NULL,
  `is_rejected` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `rejected_at` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_rating`
--

CREATE TABLE `prox_order_rating` (
  `rating_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `paper_rating` int(10) DEFAULT NULL,
  `service_rating` int(10) DEFAULT NULL,
  `comment` longtext DEFAULT NULL,
  `paper_on_time` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `user_type` enum('customer','employee') NOT NULL DEFAULT 'customer',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_revision`
--

CREATE TABLE `prox_order_revision` (
  `revision_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `grammar` longtext DEFAULT NULL,
  `format` longtext DEFAULT NULL,
  `sources` longtext DEFAULT NULL,
  `customize_cont` longtext DEFAULT NULL,
  `other` longtext DEFAULT NULL,
  `instructions` longtext DEFAULT NULL,
  `client_deadline` int(10) NOT NULL,
  `request_diff_writer` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `free_revision` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_tip`
--

CREATE TABLE `prox_order_tip` (
  `order_tip_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `date_add` datetime DEFAULT NULL,
  `writer_tip_amount` float(14,2) NOT NULL DEFAULT 0.00,
  `editor_tip_amount` float(14,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_order_view`
--

CREATE TABLE `prox_order_view` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `last_viewed` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_paper_format`
--

CREATE TABLE `prox_paper_format` (
  `paper_format_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_paper_type`
--

CREATE TABLE `prox_paper_type` (
  `paper_type_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `is_academic_writing` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `is_article_writing` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_payment`
--

CREATE TABLE `prox_payment` (
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `node_id` bigint(20) UNSIGNED NOT NULL,
  `node_type` varchar(20) DEFAULT NULL,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `conversion_rate` float(14,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_payment_request`
--

CREATE TABLE `prox_payment_request` (
  `payment_request_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `total_done_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `total_completed_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `total_pay` float(14,2) NOT NULL DEFAULT 0.00,
  `total_credit` float(14,2) NOT NULL DEFAULT 0.00,
  `total_fine` float(14,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `paid_at` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_payment_request_order`
--

CREATE TABLE `prox_payment_request_order` (
  `node_id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payment_request_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_paypal_payment`
--

CREATE TABLE `prox_paypal_payment` (
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `node_type` varchar(20) DEFAULT NULL,
  `node_id` bigint(20) UNSIGNED NOT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_prepaper`
--

CREATE TABLE `prox_prepaper` (
  `prepaper_id` bigint(20) UNSIGNED NOT NULL,
  `slug` text NOT NULL,
  `title` text NOT NULL,
  `question` longtext DEFAULT NULL,
  `academic_level_id` bigint(20) UNSIGNED NOT NULL,
  `paper_type_id` bigint(20) UNSIGNED NOT NULL,
  `paper_type_option` text DEFAULT NULL,
  `topic_category_id` bigint(20) UNSIGNED NOT NULL,
  `topic_category_option` text DEFAULT NULL,
  `is_complex_assignment` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `paper_format_id` bigint(20) UNSIGNED NOT NULL,
  `paper_format_option` text DEFAULT NULL,
  `pages` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `words` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `charts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `slides` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sources` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `spacing` enum('single','double') NOT NULL DEFAULT 'double',
  `price` float(14,2) NOT NULL DEFAULT 0.00,
  `sales` float(14,2) NOT NULL DEFAULT 0.00,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `downloads` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_prepaper_file`
--

CREATE TABLE `prox_prepaper_file` (
  `prepaper_file_id` bigint(20) UNSIGNED NOT NULL,
  `prepaper_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `size` int(10) NOT NULL DEFAULT 0,
  `source` text NOT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_prepaper_payment`
--

CREATE TABLE `prox_prepaper_payment` (
  `prepaper_payment_id` bigint(20) UNSIGNED NOT NULL,
  `prepaper_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_token` varchar(255) DEFAULT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `is_paid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `paid_at` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_prepaper_preview`
--

CREATE TABLE `prox_prepaper_preview` (
  `prepaper_preview_id` bigint(20) UNSIGNED NOT NULL,
  `prepaper_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `size` int(10) NOT NULL DEFAULT 0,
  `source` text NOT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_referral`
--

CREATE TABLE `prox_referral` (
  `referral_id` bigint(20) UNSIGNED NOT NULL,
  `referrer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `referree_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `referrer_used` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `referree_used` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_sample`
--

CREATE TABLE `prox_sample` (
  `sample_id` bigint(20) UNSIGNED NOT NULL,
  `title` text NOT NULL,
  `instructions` longtext DEFAULT NULL,
  `academic_level_id` bigint(20) UNSIGNED NOT NULL,
  `paper_type_id` bigint(20) UNSIGNED NOT NULL,
  `paper_type_option` text DEFAULT NULL,
  `topic_category_id` bigint(20) UNSIGNED NOT NULL,
  `topic_category_option` text DEFAULT NULL,
  `is_complex_assignment` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `paper_format_id` bigint(20) UNSIGNED NOT NULL,
  `paper_format_option` text DEFAULT NULL,
  `pages` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `slides` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sources` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `spacing` enum('single','double') NOT NULL DEFAULT 'double',
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_sample_file`
--

CREATE TABLE `prox_sample_file` (
  `sample_file_id` bigint(20) UNSIGNED NOT NULL,
  `sample_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `size` int(10) NOT NULL DEFAULT 0,
  `source` text NOT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_site`
--

CREATE TABLE `prox_site` (
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `customers` int(10) DEFAULT 0,
  `orders` int(10) DEFAULT 0,
  `payments` float(14,2) DEFAULT 0.00,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prox_software_programming`
--

CREATE TABLE `prox_software_programming` (
  `software_programming_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_target_audience`
--

CREATE TABLE `prox_target_audience` (
  `target_audience_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_tip_option`
--

CREATE TABLE `prox_tip_option` (
  `tip_option_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('amount','percent') NOT NULL DEFAULT 'amount',
  `value` int(14) NOT NULL DEFAULT 0,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_upload_type`
--

CREATE TABLE `prox_upload_type` (
  `upload_type_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `writer` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `customer` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `editor` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_user_session`
--

CREATE TABLE `prox_user_session` (
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_browser` varchar(64) NOT NULL,
  `user_os` varchar(64) NOT NULL,
  `user_ip` varchar(64) NOT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_wallet_transaction`
--

CREATE TABLE `prox_wallet_transaction` (
  `wallet_transaction_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `source` varchar(255) NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `amount` float(14,2) NOT NULL DEFAULT 0.00,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_writing_guide`
--

CREATE TABLE `prox_writing_guide` (
  `writing_guide_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `guide` text DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_writing_prompt`
--

CREATE TABLE `prox_writing_prompt` (
  `writing_prompt_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_writing_style`
--

CREATE TABLE `prox_writing_style` (
  `writing_style_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prox_writing_tone`
--

CREATE TABLE `prox_writing_tone` (
  `writing_tone_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  `date_add` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `prox_academic_level`
--
ALTER TABLE `prox_academic_level`
  ADD PRIMARY KEY (`academic_level_id`);

--
-- Indexes for table `prox_academic_level_site`
--
ALTER TABLE `prox_academic_level_site`
  ADD PRIMARY KEY (`academic_level_id`,`site_id`),
  ADD KEY `site_id` (`site_id`);

--
-- Indexes for table `prox_activitylog`
--
ALTER TABLE `prox_activitylog`
  ADD PRIMARY KEY (`activitylog_id`);

--
-- Indexes for table `prox_additional_service`
--
ALTER TABLE `prox_additional_service`
  ADD PRIMARY KEY (`additional_service_id`);

--
-- Indexes for table `prox_application_essay`
--
ALTER TABLE `prox_application_essay`
  ADD PRIMARY KEY (`application_essay_id`);

--
-- Indexes for table `prox_configuration`
--
ALTER TABLE `prox_configuration`
  ADD PRIMARY KEY (`configuration_id`),
  ADD KEY `site_id` (`site_id`);

--
-- Indexes for table `prox_content_feel`
--
ALTER TABLE `prox_content_feel`
  ADD PRIMARY KEY (`content_feel_id`);

--
-- Indexes for table `prox_coupon`
--
ALTER TABLE `prox_coupon`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `coupon_code` (`coupon_code`);

--
-- Indexes for table `prox_credit_fine`
--
ALTER TABLE `prox_credit_fine`
  ADD PRIMARY KEY (`credit_fine_id`);

--
-- Indexes for table `prox_currency`
--
ALTER TABLE `prox_currency`
  ADD PRIMARY KEY (`currency_id`),
  ADD UNIQUE KEY `iso_code` (`iso_code`);

--
-- Indexes for table `prox_customer`
--
ALTER TABLE `prox_customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `google_analytics_id` (`google_analytics_id`);

--
-- Indexes for table `prox_customer_wallet`
--
ALTER TABLE `prox_customer_wallet`
  ADD PRIMARY KEY (`wallet_id`);

--
-- Indexes for table `prox_deadline`
--
ALTER TABLE `prox_deadline`
  ADD PRIMARY KEY (`deadline_id`);

--
-- Indexes for table `prox_deliverable`
--
ALTER TABLE `prox_deliverable`
  ADD PRIMARY KEY (`deliverable_id`);

--
-- Indexes for table `prox_descriptor`
--
ALTER TABLE `prox_descriptor`
  ADD PRIMARY KEY (`descriptor_id`);

--
-- Indexes for table `prox_descriptor_deliverable`
--
ALTER TABLE `prox_descriptor_deliverable`
  ADD PRIMARY KEY (`descriptor_deliverable_id`);

--
-- Indexes for table `prox_discipline`
--
ALTER TABLE `prox_discipline`
  ADD PRIMARY KEY (`discipline_id`);

--
-- Indexes for table `prox_employee`
--
ALTER TABLE `prox_employee`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `prox_employee_file`
--
ALTER TABLE `prox_employee_file`
  ADD PRIMARY KEY (`employee_file_id`);

--
-- Indexes for table `prox_employee_pool`
--
ALTER TABLE `prox_employee_pool`
  ADD PRIMARY KEY (`employee_pool_id`);

--
-- Indexes for table `prox_english_type`
--
ALTER TABLE `prox_english_type`
  ADD PRIMARY KEY (`english_type_id`);

--
-- Indexes for table `prox_error_log`
--
ALTER TABLE `prox_error_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `prox_fine_type`
--
ALTER TABLE `prox_fine_type`
  ADD PRIMARY KEY (`fine_type_id`);

--
-- Indexes for table `prox_hook`
--
ALTER TABLE `prox_hook`
  ADD PRIMARY KEY (`hook_id`),
  ADD UNIQUE KEY `hook_name` (`name`);

--
-- Indexes for table `prox_hook_alias`
--
ALTER TABLE `prox_hook_alias`
  ADD PRIMARY KEY (`hook_id_alias`),
  ADD UNIQUE KEY `alias` (`alias`);

--
-- Indexes for table `prox_hook_module`
--
ALTER TABLE `prox_hook_module`
  ADD PRIMARY KEY (`module_id`,`hook_id`),
  ADD KEY `hook_id` (`hook_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `position` (`position`);

--
-- Indexes for table `prox_item_pricing`
--
ALTER TABLE `prox_item_pricing`
  ADD PRIMARY KEY (`item_pricing_id`);

--
-- Indexes for table `prox_mail`
--
ALTER TABLE `prox_mail`
  ADD PRIMARY KEY (`mail_id`),
  ADD KEY `site_id` (`site_id`);

--
-- Indexes for table `prox_mailing_list`
--
ALTER TABLE `prox_mailing_list`
  ADD PRIMARY KEY (`mail_id`);

--
-- Indexes for table `prox_mail_template`
--
ALTER TABLE `prox_mail_template`
  ADD PRIMARY KEY (`mail_template_id`),
  ADD UNIQUE KEY `template` (`template`);

--
-- Indexes for table `prox_module`
--
ALTER TABLE `prox_module`
  ADD PRIMARY KEY (`module_id`),
  ADD UNIQUE KEY `name_UNIQUE` (`name`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `prox_mpesa_payout`
--
ALTER TABLE `prox_mpesa_payout`
  ADD PRIMARY KEY (`mpesa_payout_id`);

--
-- Indexes for table `prox_news`
--
ALTER TABLE `prox_news`
  ADD PRIMARY KEY (`news_id`);

--
-- Indexes for table `prox_news_view`
--
ALTER TABLE `prox_news_view`
  ADD PRIMARY KEY (`news_view_id`);

--
-- Indexes for table `prox_notification`
--
ALTER TABLE `prox_notification`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `prox_order`
--
ALTER TABLE `prox_order`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `prox_order_bid`
--
ALTER TABLE `prox_order_bid`
  ADD PRIMARY KEY (`bid_id`);

--
-- Indexes for table `prox_order_employee`
--
ALTER TABLE `prox_order_employee`
  ADD PRIMARY KEY (`order_employee_id`);

--
-- Indexes for table `prox_order_file`
--
ALTER TABLE `prox_order_file`
  ADD PRIMARY KEY (`file_id`);

--
-- Indexes for table `prox_order_message`
--
ALTER TABLE `prox_order_message`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `prox_order_meta`
--
ALTER TABLE `prox_order_meta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `prox_order_pd_schedule`
--
ALTER TABLE `prox_order_pd_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `orders` (`order_id`);

--
-- Indexes for table `prox_order_rating`
--
ALTER TABLE `prox_order_rating`
  ADD PRIMARY KEY (`rating_id`);

--
-- Indexes for table `prox_order_revision`
--
ALTER TABLE `prox_order_revision`
  ADD PRIMARY KEY (`revision_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `prox_order_tip`
--
ALTER TABLE `prox_order_tip`
  ADD PRIMARY KEY (`order_tip_id`);

--
-- Indexes for table `prox_order_view`
--
ALTER TABLE `prox_order_view`
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `prox_paper_format`
--
ALTER TABLE `prox_paper_format`
  ADD PRIMARY KEY (`paper_format_id`);

--
-- Indexes for table `prox_paper_type`
--
ALTER TABLE `prox_paper_type`
  ADD PRIMARY KEY (`paper_type_id`);

--
-- Indexes for table `prox_payment`
--
ALTER TABLE `prox_payment`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `prox_payment_request`
--
ALTER TABLE `prox_payment_request`
  ADD PRIMARY KEY (`payment_request_id`);

--
-- Indexes for table `prox_payment_request_order`
--
ALTER TABLE `prox_payment_request_order`
  ADD PRIMARY KEY (`node_id`);

--
-- Indexes for table `prox_paypal_payment`
--
ALTER TABLE `prox_paypal_payment`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `prox_prepaper`
--
ALTER TABLE `prox_prepaper`
  ADD PRIMARY KEY (`prepaper_id`);

--
-- Indexes for table `prox_prepaper_file`
--
ALTER TABLE `prox_prepaper_file`
  ADD PRIMARY KEY (`prepaper_file_id`);

--
-- Indexes for table `prox_prepaper_payment`
--
ALTER TABLE `prox_prepaper_payment`
  ADD PRIMARY KEY (`prepaper_payment_id`);

--
-- Indexes for table `prox_prepaper_preview`
--
ALTER TABLE `prox_prepaper_preview`
  ADD PRIMARY KEY (`prepaper_preview_id`);

--
-- Indexes for table `prox_referral`
--
ALTER TABLE `prox_referral`
  ADD PRIMARY KEY (`referral_id`);

--
-- Indexes for table `prox_sample`
--
ALTER TABLE `prox_sample`
  ADD PRIMARY KEY (`sample_id`);

--
-- Indexes for table `prox_sample_file`
--
ALTER TABLE `prox_sample_file`
  ADD PRIMARY KEY (`sample_file_id`);

--
-- Indexes for table `prox_site`
--
ALTER TABLE `prox_site`
  ADD PRIMARY KEY (`site_id`);

--
-- Indexes for table `prox_software_programming`
--
ALTER TABLE `prox_software_programming`
  ADD PRIMARY KEY (`software_programming_id`);

--
-- Indexes for table `prox_target_audience`
--
ALTER TABLE `prox_target_audience`
  ADD PRIMARY KEY (`target_audience_id`);

--
-- Indexes for table `prox_tip_option`
--
ALTER TABLE `prox_tip_option`
  ADD PRIMARY KEY (`tip_option_id`);

--
-- Indexes for table `prox_upload_type`
--
ALTER TABLE `prox_upload_type`
  ADD PRIMARY KEY (`upload_type_id`);

--
-- Indexes for table `prox_user_session`
--
ALTER TABLE `prox_user_session`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_ip` (`user_ip`);

--
-- Indexes for table `prox_wallet_transaction`
--
ALTER TABLE `prox_wallet_transaction`
  ADD PRIMARY KEY (`wallet_transaction_id`);

--
-- Indexes for table `prox_writing_guide`
--
ALTER TABLE `prox_writing_guide`
  ADD PRIMARY KEY (`writing_guide_id`);

--
-- Indexes for table `prox_writing_prompt`
--
ALTER TABLE `prox_writing_prompt`
  ADD PRIMARY KEY (`writing_prompt_id`);

--
-- Indexes for table `prox_writing_style`
--
ALTER TABLE `prox_writing_style`
  ADD PRIMARY KEY (`writing_style_id`);

--
-- Indexes for table `prox_writing_tone`
--
ALTER TABLE `prox_writing_tone`
  ADD PRIMARY KEY (`writing_tone_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `prox_academic_level`
--
ALTER TABLE `prox_academic_level`
  MODIFY `academic_level_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_activitylog`
--
ALTER TABLE `prox_activitylog`
  MODIFY `activitylog_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_additional_service`
--
ALTER TABLE `prox_additional_service`
  MODIFY `additional_service_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_application_essay`
--
ALTER TABLE `prox_application_essay`
  MODIFY `application_essay_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_configuration`
--
ALTER TABLE `prox_configuration`
  MODIFY `configuration_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_content_feel`
--
ALTER TABLE `prox_content_feel`
  MODIFY `content_feel_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_coupon`
--
ALTER TABLE `prox_coupon`
  MODIFY `coupon_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_credit_fine`
--
ALTER TABLE `prox_credit_fine`
  MODIFY `credit_fine_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_currency`
--
ALTER TABLE `prox_currency`
  MODIFY `currency_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_customer`
--
ALTER TABLE `prox_customer`
  MODIFY `customer_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_customer_wallet`
--
ALTER TABLE `prox_customer_wallet`
  MODIFY `wallet_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_deadline`
--
ALTER TABLE `prox_deadline`
  MODIFY `deadline_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_deliverable`
--
ALTER TABLE `prox_deliverable`
  MODIFY `deliverable_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_descriptor`
--
ALTER TABLE `prox_descriptor`
  MODIFY `descriptor_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_descriptor_deliverable`
--
ALTER TABLE `prox_descriptor_deliverable`
  MODIFY `descriptor_deliverable_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_discipline`
--
ALTER TABLE `prox_discipline`
  MODIFY `discipline_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_employee`
--
ALTER TABLE `prox_employee`
  MODIFY `employee_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_employee_file`
--
ALTER TABLE `prox_employee_file`
  MODIFY `employee_file_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_employee_pool`
--
ALTER TABLE `prox_employee_pool`
  MODIFY `employee_pool_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_english_type`
--
ALTER TABLE `prox_english_type`
  MODIFY `english_type_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_error_log`
--
ALTER TABLE `prox_error_log`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_fine_type`
--
ALTER TABLE `prox_fine_type`
  MODIFY `fine_type_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_hook`
--
ALTER TABLE `prox_hook`
  MODIFY `hook_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_hook_alias`
--
ALTER TABLE `prox_hook_alias`
  MODIFY `hook_id_alias` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_item_pricing`
--
ALTER TABLE `prox_item_pricing`
  MODIFY `item_pricing_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_mail`
--
ALTER TABLE `prox_mail`
  MODIFY `mail_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_mailing_list`
--
ALTER TABLE `prox_mailing_list`
  MODIFY `mail_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_mail_template`
--
ALTER TABLE `prox_mail_template`
  MODIFY `mail_template_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_module`
--
ALTER TABLE `prox_module`
  MODIFY `module_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_mpesa_payout`
--
ALTER TABLE `prox_mpesa_payout`
  MODIFY `mpesa_payout_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_news`
--
ALTER TABLE `prox_news`
  MODIFY `news_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_news_view`
--
ALTER TABLE `prox_news_view`
  MODIFY `news_view_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_notification`
--
ALTER TABLE `prox_notification`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order`
--
ALTER TABLE `prox_order`
  MODIFY `order_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_bid`
--
ALTER TABLE `prox_order_bid`
  MODIFY `bid_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_employee`
--
ALTER TABLE `prox_order_employee`
  MODIFY `order_employee_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_file`
--
ALTER TABLE `prox_order_file`
  MODIFY `file_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_message`
--
ALTER TABLE `prox_order_message`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_meta`
--
ALTER TABLE `prox_order_meta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_pd_schedule`
--
ALTER TABLE `prox_order_pd_schedule`
  MODIFY `schedule_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_rating`
--
ALTER TABLE `prox_order_rating`
  MODIFY `rating_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_revision`
--
ALTER TABLE `prox_order_revision`
  MODIFY `revision_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_order_tip`
--
ALTER TABLE `prox_order_tip`
  MODIFY `order_tip_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_paper_format`
--
ALTER TABLE `prox_paper_format`
  MODIFY `paper_format_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_paper_type`
--
ALTER TABLE `prox_paper_type`
  MODIFY `paper_type_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_payment`
--
ALTER TABLE `prox_payment`
  MODIFY `payment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_payment_request`
--
ALTER TABLE `prox_payment_request`
  MODIFY `payment_request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_payment_request_order`
--
ALTER TABLE `prox_payment_request_order`
  MODIFY `node_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_paypal_payment`
--
ALTER TABLE `prox_paypal_payment`
  MODIFY `payment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_prepaper`
--
ALTER TABLE `prox_prepaper`
  MODIFY `prepaper_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_prepaper_file`
--
ALTER TABLE `prox_prepaper_file`
  MODIFY `prepaper_file_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_prepaper_payment`
--
ALTER TABLE `prox_prepaper_payment`
  MODIFY `prepaper_payment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_prepaper_preview`
--
ALTER TABLE `prox_prepaper_preview`
  MODIFY `prepaper_preview_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_referral`
--
ALTER TABLE `prox_referral`
  MODIFY `referral_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_sample`
--
ALTER TABLE `prox_sample`
  MODIFY `sample_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_sample_file`
--
ALTER TABLE `prox_sample_file`
  MODIFY `sample_file_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_site`
--
ALTER TABLE `prox_site`
  MODIFY `site_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_software_programming`
--
ALTER TABLE `prox_software_programming`
  MODIFY `software_programming_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_target_audience`
--
ALTER TABLE `prox_target_audience`
  MODIFY `target_audience_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_tip_option`
--
ALTER TABLE `prox_tip_option`
  MODIFY `tip_option_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_upload_type`
--
ALTER TABLE `prox_upload_type`
  MODIFY `upload_type_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_user_session`
--
ALTER TABLE `prox_user_session`
  MODIFY `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_wallet_transaction`
--
ALTER TABLE `prox_wallet_transaction`
  MODIFY `wallet_transaction_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_writing_guide`
--
ALTER TABLE `prox_writing_guide`
  MODIFY `writing_guide_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_writing_prompt`
--
ALTER TABLE `prox_writing_prompt`
  MODIFY `writing_prompt_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_writing_style`
--
ALTER TABLE `prox_writing_style`
  MODIFY `writing_style_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prox_writing_tone`
--
ALTER TABLE `prox_writing_tone`
  MODIFY `writing_tone_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
