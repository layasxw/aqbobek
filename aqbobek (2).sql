-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Мар 30 2026 г., 19:51
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `aqbobek`
--

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `achievements`
--

INSERT INTO `achievements` (`id`, `student_id`, `title`, `description`, `recorded_by`, `date`) VALUES
(1, 2, '1 место — Олимпиада по информатике', NULL, 5, '2025-02-15'),
(2, 2, 'Участник — Республиканский конкурс проектов', NULL, 5, '2025-01-20'),
(3, 3, '3 место — Олимпиада по математике', NULL, 5, '2025-03-01'),
(4, 4, 'Хакатон 1 место', NULL, 5, '2026-03-29');

-- --------------------------------------------------------

--
-- Структура таблицы `ai_advice`
--

CREATE TABLE `ai_advice` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `advice` text NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `classes`
--

INSERT INTO `classes` (`id`, `name`, `teacher_id`) VALUES
(1, '7А', 8),
(2, '7Б', 5),
(3, '7В', 9),
(4, '8А', NULL),
(5, '8Б', NULL),
(6, '8В', NULL),
(7, '9А', NULL),
(8, '9Б', NULL),
(9, '9В', NULL),
(10, '10А', NULL),
(11, '10Б', NULL),
(12, '10В', NULL),
(13, '11А', NULL),
(14, '11Б', NULL),
(15, '11В', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `author_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `author_id`) VALUES
(1, 'СОЧ по Физике', 'Итоговая контрольная за 3 четверть', '2026-04-05 10:00:00', 7),
(2, 'День открытых дверей', 'Приглашаем родителей в школу', '2026-04-10 14:00:00', 7),
(3, 'Олимпиада по математике', 'Школьный этап', '2026-04-15 09:00:00', 7),
(4, 'Выпускной вечер', 'Торжественная церемония', '2026-06-20 18:00:00', 7);

-- --------------------------------------------------------

--
-- Структура таблицы `goals`
--

CREATE TABLE `goals` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `target_score` int(11) NOT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('active','done','failed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `grade_type` varchar(50) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `topic` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `score`, `grade_type`, `recorded_by`, `date`, `topic`) VALUES
(1, 2, 1, 85, 'СОР', 5, '2025-01-10', 'Квадратные уравнения'),
(2, 2, 1, 72, 'СОЧ', 5, '2025-01-25', 'Квадратные уравнения'),
(3, 2, 1, 90, 'СОР', 5, '2025-02-10', 'Тригонометрия'),
(4, 2, 1, 65, 'СОЧ', 5, '2025-02-28', 'Тригонометрия'),
(5, 2, 1, 55, 'СОР', 5, '2025-03-15', 'Производная'),
(6, 2, 2, 45, 'СОР', 5, '2025-01-12', 'Термодинамика'),
(7, 2, 2, 50, 'СОЧ', 5, '2025-01-30', 'Термодинамика'),
(8, 2, 2, 42, 'СОР', 5, '2025-02-15', 'Идеальный газ'),
(9, 2, 2, 38, 'СОЧ', 5, '2025-03-01', 'Идеальный газ'),
(10, 2, 2, 35, 'СОР', 5, '2025-03-20', 'Молекулярная физика'),
(11, 2, 3, 95, 'СОР', 5, '2025-01-15', 'Reading'),
(12, 2, 3, 88, 'СОЧ', 5, '2025-02-01', 'Writing'),
(13, 2, 3, 92, 'СОР', 5, '2025-02-20', 'Grammar'),
(14, 2, 4, 98, 'СОР', 5, '2025-01-20', 'Алгоритмы'),
(15, 2, 4, 95, 'СОЧ', 5, '2025-02-10', 'Базы данных'),
(16, 2, 4, 100, 'СОР', 5, '2025-03-05', 'ООП'),
(17, 3, 1, 78, 'СОР', 5, '2025-01-10', 'Квадратные уравнения'),
(18, 3, 1, 82, 'СОЧ', 5, '2025-02-28', 'Тригонометрия'),
(19, 3, 2, 80, 'СОР', 5, '2025-01-12', 'Термодинамика'),
(20, 3, 3, 88, 'СОР', 5, '2025-01-15', 'Reading'),
(21, 3, 4, 91, 'СОР', 5, '2025-01-20', 'Алгоритмы'),
(22, 4, 1, 55, 'СОР', 5, '2025-01-10', 'Квадратные уравнения'),
(23, 4, 1, 48, 'СОЧ', 5, '2025-02-28', 'Тригонометрия'),
(24, 4, 2, 40, 'СОР', 5, '2025-01-12', 'Термодинамика'),
(25, 4, 3, 60, 'СОР', 5, '2025-01-15', 'Reading'),
(26, 4, 4, 52, 'СОР', 5, '2025-01-20', 'Алгоритмы');

-- --------------------------------------------------------

--
-- Структура таблицы `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `news`
--

INSERT INTO `news` (`id`, `title`, `body`, `author_id`, `created_at`) VALUES
(1, 'Итоги олимпиады по информатике', 'Поздравляем Армана Даулетова с победой!', 7, '2026-03-29 11:30:39'),
(2, 'Расписание СОЧ за 3 четверть', 'Итоговые контрольные с 1 по 10 апреля.', 7, '2026-03-29 11:30:39'),
(3, 'Запись на элективные курсы', 'Открыта запись на курсы по робототехнике.', 7, '2026-03-29 11:30:39');

-- --------------------------------------------------------

--
-- Структура таблицы `parent_student`
--

CREATE TABLE `parent_student` (
  `parent_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `parent_student`
--

INSERT INTO `parent_student` (`parent_id`, `student_id`) VALUES
(6, 2),
(13, 3),
(14, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `day` enum('Mon','Tue','Wed','Thu','Fri') NOT NULL,
  `period` int(11) NOT NULL,
  `room` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `subjects`
--

INSERT INTO `subjects` (`id`, `name`) VALUES
(1, 'Математика'),
(2, 'Физика'),
(3, 'Английский'),
(4, 'Информатика'),
(5, 'Химия'),
(6, 'История');

-- --------------------------------------------------------

--
-- Структура таблицы `teacher_classes`
--

CREATE TABLE `teacher_classes` (
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `teacher_classes`
--

INSERT INTO `teacher_classes` (`teacher_id`, `class_id`) VALUES
(5, 1),
(5, 2),
(5, 3),
(8, 1),
(8, 2),
(8, 3),
(9, 1),
(9, 2),
(9, 3),
(10, 1),
(10, 2),
(10, 3),
(11, 1),
(11, 2),
(11, 3),
(12, 1),
(12, 2),
(12, 3);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','parent','admin') NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `class_id`, `created_at`, `subject_id`) VALUES
(2, 'Арман Даулетов', 'student@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-03-29 11:28:30', NULL),
(3, 'Айгерим Сейткали', 'student2@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-03-29 11:28:30', NULL),
(4, 'Данияр Мұқанов', 'student3@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-03-29 11:28:30', NULL),
(5, 'Нурлан Асқаров', 'teacher@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, '2026-03-29 11:28:30', 2),
(6, 'Сауле Жақсыбекова', 'parent@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', NULL, '2026-03-29 11:28:30', NULL),
(7, 'Админ Школы', 'admin@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '2026-03-29 11:28:30', NULL),
(8, 'Айгуль Бекова', 'teacher2@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, '2026-03-30 09:41:40', 1),
(9, 'Дамир Сейтов', 'teacher3@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, '2026-03-30 09:41:40', 3),
(10, 'Жанна Омарова', 'teacher4@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, '2026-03-30 09:41:40', 4),
(11, 'Болат Нұрмағамбетов', 'teacher5@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, '2026-03-30 09:41:40', 5),
(12, 'Гүлнар Қасымова', 'teacher6@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, '2026-03-30 09:41:40', 6),
(13, 'Қанат Сейткали', 'parent2@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', NULL, '2026-03-30 09:41:40', NULL),
(14, 'Зауреш Мұқанова', 'parent3@school.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', NULL, '2026-03-30 09:41:40', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Индексы таблицы `ai_advice`
--
ALTER TABLE `ai_advice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Индексы таблицы `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Индексы таблицы `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Индексы таблицы `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Индексы таблицы `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Индексы таблицы `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Индексы таблицы `parent_student`
--
ALTER TABLE `parent_student`
  ADD PRIMARY KEY (`parent_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD PRIMARY KEY (`teacher_id`,`class_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `ai_advice`
--
ALTER TABLE `ai_advice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `goals`
--
ALTER TABLE `goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `achievements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `ai_advice`
--
ALTER TABLE `ai_advice`
  ADD CONSTRAINT `ai_advice_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `goals_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Ограничения внешнего ключа таблицы `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Ограничения внешнего ключа таблицы `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `parent_student`
--
ALTER TABLE `parent_student`
  ADD CONSTRAINT `parent_student_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `parent_student_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
