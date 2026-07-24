-- College Club Management System (CCMS) Database Schema
CREATE DATABASE IF NOT EXISTS `ccms_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ccms_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(36) PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(191) NOT NULL,
    `role` ENUM('super_admin', 'club_admin', 'student') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT 'bi-collection-fill',
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Clubs Table
CREATE TABLE IF NOT EXISTS `clubs` (
    `id` VARCHAR(36) PRIMARY KEY,
    `name` VARCHAR(191) NOT NULL,
    `short_name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
    `category_id` INT NOT NULL,
    `tagline` VARCHAR(255),
    `description` TEXT,
    `mission` TEXT,
    `vision` TEXT,
    `objectives` TEXT,
    `logo` VARCHAR(255) DEFAULT 'assets/images/default-logo.png',
    `cover_image` VARCHAR(255) DEFAULT 'assets/images/default-cover.jpg',
    `founded_year` INT DEFAULT 2024,
    `status` ENUM('active', 'recruiting', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `recruitment_open` TINYINT(1) DEFAULT 0,
    `recruitment_link` VARCHAR(255) DEFAULT '',
    `recruitment_deadline` DATE DEFAULT NULL,
    `recruitment_eligibility` TEXT,
    `email` VARCHAR(191),
    `phone` VARCHAR(50),
    `office_location` VARCHAR(191),
    `meeting_time` VARCHAR(191),
    `meeting_location` VARCHAR(191),
    `website` VARCHAR(255),
    `instagram` VARCHAR(255),
    `linkedin` VARCHAR(255),
    `github` VARCHAR(255),
    `discord` VARCHAR(255),
    `whatsapp` VARCHAR(255),
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Club Admins Association
CREATE TABLE IF NOT EXISTS `club_admins` (
    `club_id` VARCHAR(36) NOT NULL,
    `user_id` VARCHAR(36) NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`club_id`, `user_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Leadership & Roster Table
CREATE TABLE IF NOT EXISTS `leadership` (
    `id` VARCHAR(36) PRIMARY KEY,
    `club_id` VARCHAR(36) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `role_title` VARCHAR(100) NOT NULL,
    `category` ENUM('faculty_coordinator', 'president', 'vice_president', 'secretary', 'treasurer', 'core_member') NOT NULL,
    `email` VARCHAR(191),
    `phone` VARCHAR(50),
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `order_index` INT DEFAULT 0,
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Events Table
CREATE TABLE IF NOT EXISTS `events` (
    `id` VARCHAR(36) PRIMARY KEY,
    `club_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `banner` VARCHAR(255) DEFAULT 'assets/images/default-event.jpg',
    `description` TEXT,
    `venue` VARCHAR(191) NOT NULL,
    `event_date` DATETIME NOT NULL,
    `registration_link` VARCHAR(255),
    `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    `attendance_count` INT DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Activities (Club Blog / Updates)
CREATE TABLE IF NOT EXISTS `activities` (
    `id` VARCHAR(36) PRIMARY KEY,
    `club_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `content` TEXT NOT NULL,
    `image` VARCHAR(255),
    `tag` VARCHAR(100) DEFAULT 'General',
    `status` ENUM('published', 'pending_approval', 'rejected') DEFAULT 'published',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Gallery Albums & Items
CREATE TABLE IF NOT EXISTS `gallery_albums` (
    `id` VARCHAR(36) PRIMARY KEY,
    `club_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `description` TEXT,
    `cover_image` VARCHAR(255),
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gallery_items` (
    `id` VARCHAR(36) PRIMARY KEY,
    `album_id` VARCHAR(36) NOT NULL,
    `media_url` VARCHAR(255) NOT NULL,
    `media_type` ENUM('image', 'video') DEFAULT 'image',
    `caption` VARCHAR(255),
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`album_id`) REFERENCES `gallery_albums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Achievements Table
CREATE TABLE IF NOT EXISTS `achievements` (
    `id` VARCHAR(36) PRIMARY KEY,
    `club_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `achievement_date` DATE NOT NULL,
    `description` TEXT,
    `certificate_url` VARCHAR(255),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36),
    `user_name` VARCHAR(191),
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(100),
    `target_id` VARCHAR(100),
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Contact Messages
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` VARCHAR(36) PRIMARY KEY,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_read` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
