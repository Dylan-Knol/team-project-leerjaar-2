-- ==========================================================
-- init.sql
-- Database initialization script
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `userdata` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `userdata`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `user`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Tabel: user (inlogsysteem - al bestaand)
-- --------------------------------------------------------
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `password` varchar(100) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'gebruiker',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------
-- Tabel: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `icon` VARCHAR(50),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`name`, `icon`) VALUES
('Travel', 'plane'),
('Home', 'house'),
('Safety', 'shield'),
('Other', 'star');

-- --------------------------------------------------------
-- Tabel: goals (spaardoelen / potjes)
-- --------------------------------------------------------
CREATE TABLE `goals` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `target_amount` DECIMAL(10,2) NOT NULL,
  `target_date` DATE NOT NULL,
  `status` ENUM('active','completed') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel: transactions (stortingen per doel)
-- --------------------------------------------------------
CREATE TABLE `transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `goal_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `type` ENUM('manual','auto') DEFAULT 'manual',
  `transaction_date` DATE NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`goal_id`) REFERENCES `goals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;