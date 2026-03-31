-- phpMyAdmin SQL Dump для Railway
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

USE railway;

-- Таблица achievements
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `achievements` (`id`, `student_id`, `title`, `description`, `recorded_by`, `date`) VALUES
(1, 2, '1 место — Олимпиада по информатике', NULL, 5, '2025-02-15'),
(2, 2, 'Участник — Республиканский конкурс проектов', NULL, 5, '2025-01-20'),
(3, 3, '3 место — Олимпиада по математике', NULL, 5, '2025-03-01'),
(4, 4, 'Хакатон 1 место', NULL, 5, '2026-03-29');

-- Таблица users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','parent','admin') NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subject_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `class_id`, `created_at`, `subject_id`) VALUES
(2, 'Арман Даулетов', 'student@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-03-29 11:28:30', NULL),
(3, 'Айгерим Сейткали', 'student2@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-03-29 11:28:30', NULL),
(4, 'Данияр Мұқанов', 'student3@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-03-29 11:28:30', NULL),
(5, 'Нурлан Асқаров', 'teacher@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, '2026-03-29 11:28:30', 2),
(6, 'Сауле Жақсыбекова', 'parent@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', NULL, '2026-03-29 11:28:30', NULL),
(7, 'Админ Школы', 'admin@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '2026-03-29 11:28:30', NULL);

-- Добавь остальные таблицы по аналогии
-- Для каждой таблицы: убираем CREATE DATABASE и USE, оставляем только CREATE TABLE IF NOT EXISTS и INSERT INTO
COMMIT;