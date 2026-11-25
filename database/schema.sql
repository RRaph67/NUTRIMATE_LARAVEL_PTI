-- Skema database NutriMate (MariaDB / MySQL)
--
-- CATATAN: sumber kebenaran skema ada pada Laravel migrations
-- (database/migrations). Berkas ini hanya referensi manual/DDL untuk
-- keperluan impor cepat atau pembuatan database baru tanpa artisan.
--
-- Cara pakai:
--   mysql -u root -p < database/schema.sql
--   php artisan db:seed            (mengisi 36 menu awal)

CREATE DATABASE IF NOT EXISTS `nutrimate_pemweb_kelas`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nutrimate_pemweb_kelas`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `profiles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `full_name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `bio` TEXT NULL,
    `calorie_target` INT UNSIGNED NOT NULL DEFAULT 2000,
    `daily_budget` DECIMAL(12, 2) NOT NULL DEFAULT 30000.00,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `foods` (
    `id` VARCHAR(36) PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `price` DECIMAL(12, 2) NOT NULL,
    `portion_description` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `calories` DECIMAL(8, 2) NOT NULL,
    `protein` DECIMAL(6, 2) NOT NULL,
    `carbs` DECIMAL(6, 2) NOT NULL,
    `fat` DECIMAL(6, 2) NOT NULL,
    `image_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_foods_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nilai gizi & harga disimpan langsung (denormalisasi) agar riwayat rencana
-- makan tetap stabil walaupun data makanan berubah.
CREATE TABLE IF NOT EXISTS `meal_plans` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `entry_date` DATE NOT NULL,
    `meal_slot` VARCHAR(20) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `food_id` VARCHAR(36) NULL,
    `calories` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `protein` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `carbs` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `fat` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_meal_plans_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_meal_plans_user_date` (`user_id`, `entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `nutrition_calculations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `gender` ENUM('male', 'female') NOT NULL DEFAULT 'male',
    `age` TINYINT UNSIGNED NOT NULL,
    `weight` DECIMAL(5, 2) NOT NULL,
    `height` DECIMAL(5, 2) NOT NULL,
    `activity_factor` DECIMAL(4, 3) NOT NULL DEFAULT 1.200,
    `activity_label` VARCHAR(60) NOT NULL DEFAULT 'Ringan',
    `bmr` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `tdee` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `protein` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `carbs` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `fat` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    `bmi` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_nutrition_calculations_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_nutrition_calculations_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
