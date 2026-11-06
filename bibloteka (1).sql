-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2026 at 09:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bibloteka`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `titulli` varchar(150) NOT NULL,
  `autori` varchar(100) NOT NULL,
  `kategoria` varchar(100) DEFAULT NULL,
  `sasia` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `titulli`, `autori`, `kategoria`, `sasia`) VALUES
(1, 'Harry Potter dhe Guri Filozofal', 'J.K. Rowling', 'Fantazi', 5),
(2, 'Princi i Vogël', 'Antoine de Saint-Exupery', 'Aventurë', 4),
(3, 'Alkimisti', 'Paulo Coelho', 'Roman', 6),
(4, '1984', 'George Orwell', 'Distopi', 3),
(5, 'Oliver Twist', 'Charles Dickens', 'Klasik', 4),
(6, 'Don Kishoti', 'Miguel de Cervantes', 'Klasik', 2),
(7, 'I Huaji', 'Albert Camus', 'Filozofi', 3),
(8, 'Kronikë në gur', 'Ismail Kadare', 'Letërsi shqipe', 5),
(9, 'Gjenerali i ushtrisë së vdekur', 'Ismail Kadare', 'Roman', 4),
(10, 'Lahuta e Malcis', 'Gjergj Fishta', 'Letërsi shqipe', 3),
(11, 'Metamorfoza', 'Franz Kafka', 'Klasik', 4),
(12, 'Romeo dhe Zhuljeta', 'William Shakespeare', 'Tragjedi', 3);

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `data_huazimit` date NOT NULL,
  `data_kthimit` date DEFAULT NULL,
  `statusi` enum('huazuar','kthyer') NOT NULL DEFAULT 'huazuar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `emri` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `roli` enum('nxenes','punetor','administrator') NOT NULL DEFAULT 'nxenes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `emri`, `username`, `email`, `password`, `roli`) VALUES
(1, 'Erdrin Ajvazi', 'Dini', 'erdrinajvazi@gmail.com', '$2y$10$PsaJZHGDvcIAlhE3N8ADlefrBCaiBLERNgRxOC.VpJ/uSmfhghXSO', 'administrator'),
(2, 'fiolon', 'fiolon', 'fiolon026@gmail.com', '$2y$10$6k/X1LCxbFLnR/aMPC5nSu.6X.wFvgpKkdJLePvo1Phtv0LZr6QUW', 'administrator'),
(3, 'eris', 'eris', 'eris@gmail.com', '$2y$10$kj7x/mAfzATexeOQYlsxieKSzQ3Civfk/QuMNd86FDEXaokXZXXQC', 'nxenes'),
(4, 'perparim haliti', 'perparim', 'perparimhalii106@gmail.com', '$2y$10$H9.nhaa2A88BZSsvDlbweesh6jpDPWdVjVzK061/jKEWtjs4kmjji', 'nxenes'),
(5, 'rilind', 'rilindsahiti', 'lindisahiti98@gmail.com', '$2y$10$1GTZiujYXz4x4gxsqD9QYeDYg.ypYdICzxl5C/sXUWz40CLpGZgNa', 'nxenes'),
(6, 'afrim', 'afrim', 'filani@gmail.com', '$2y$10$DvMNYl7dKVL23wkRjUstXOPbBRaX5zNJQF0W2szop/FJDRgHk91Ny', 'nxenes');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
