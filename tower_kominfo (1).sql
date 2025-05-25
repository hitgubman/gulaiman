-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 12:59 PM
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
-- Database: `tower_kominfo`
--

-- --------------------------------------------------------

--
-- Table structure for table `akun`
--

CREATE TABLE `akun` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun`
--

INSERT INTO `akun` (`id`, `username`, `password`, `role`, `profile_image`) VALUES
(1, 'ADMIN', '$2y$10$W23vkvldgxj6YnFbN4cZieIpIPg2UQK0cvcXgqbnlECT/I5R6bneC', 'superadmin', NULL),
(8, 'Kakak', '$2y$10$Q/N9KCZfkmygVMLnCb5K4uTlrXT3sEhDndEgKdQx9C5cVCkwqfyS6', 'superadmin', NULL),
(9, 'kaka', '$2y$10$jiq9JRtFo6/o9rjvgxFTmOWRVa7Ei9MNY2fdWN8aeQ8xL8ZvdxVx2', 'admin', 'DtD Besar 10 Mei 2025.png'),
(10, '22210028', '123', 'admin', '0d9c148725f2a367fa2a6447b0a308e5.jpg'),
(11, 'CACA', '123', 'admin', 'e461399641e8bbe1892d150afb27e23c.png'),
(12, 'CACAH', '$2y$10$N4zpXrYpN0pFrJNwaS8aCOHbkKn/BZirqZeGlclBgBsqn3u0QuaB6', 'admin', 'c46501201ac63cf82dc7dea8a88d40a3.jpg'),
(13, 'KORORO', '$2y$10$a6MX.8NOAs3.j8WKC9n5g.Dmb3frfS/9L0e15P4fpFslaw1oGbWWK', 'admin', '7907aa3e3bec61d8d22ab2df585419fd.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tower`
--

CREATE TABLE `tower` (
  `id_tower` int(11) NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `lokasi` varchar(150) NOT NULL,
  `jml_kaki` int(1) NOT NULL,
  `lintang` float NOT NULL,
  `bujur` float(20,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tower`
--

INSERT INTO `tower` (`id_tower`, `gambar`, `status`, `provider`, `lokasi`, `jml_kaki`, `lintang`, `bujur`) VALUES
(31, 'tower1.jpg', 'Aktif', 'Telkomsel', 'Liningaan, Tondano Timur', 3, 1.23457, 125),
(32, 'Screenshot (1008).png', 'Aktif', 'XL', 'Tataaran, Tondano Utara', 4, 1.24568, 125),
(34, 'Screenshot (98).png', 'Non-Aktif', 'Tri', 'Tataaran Dua, Tondano Utara', 4, 1.26789, 125),
(52, 'isi.jpg', 'Non-Aktif', 'Axis', 'Liningaan, Tondano Timur', 3, 1.2895, 125),
(54, 'WhatsApp Image 2025-03-11 at 20.48.51_d02296bc.jpg', 'Aktif', 'Smartfren', 'Wengkol, Tondano Timur', 1, 0.911827, 125),
(55, 'pic makalah 2.jpg', 'Non-aktif', 'Smartfren', 'Kendis, Tondano Timur', 4, 1.25234, 125);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tower`
--
ALTER TABLE `tower`
  ADD PRIMARY KEY (`id_tower`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun`
--
ALTER TABLE `akun`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tower`
--
ALTER TABLE `tower`
  MODIFY `id_tower` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
