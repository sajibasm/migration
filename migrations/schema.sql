-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 20, 2023 at 12:07 PM
-- Server version: 8.0.31-0ubuntu0.20.04.1
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `migration`
--

-- --------------------------------------------------------

--
-- Table structure for table `migration`
--

CREATE TABLE `migration` (
                             `version` varchar(180) NOT NULL,
                             `apply_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tableCompare`
--

CREATE TABLE `tableCompare` (
                                `id` int NOT NULL,
                                `tableName` varchar(100) NOT NULL,
                                `isEngine` tinyint(1) NOT NULL DEFAULT '0',
                                `engineType` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
                                `autoIncrement` tinyint(1) NOT NULL DEFAULT '0',
                                `autoIncrementKey` varchar(20) DEFAULT NULL,
                                `isPrimary` tinyint(1) NOT NULL DEFAULT '0',
                                `primaryKeys` text,
                                `isUnique` tinyint(1) NOT NULL DEFAULT '0',
                                `uniqueKeys` text,
                                `isIndex` tinyint(1) NOT NULL DEFAULT '0',
                                `indexKeys` text,
                                `maxColType` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
                                `maxColValue` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
                                `cols` int NOT NULL,
                                `rows` int NOT NULL,
                                `columnStatics` text NOT NULL,
                                `isError` tinyint(1) NOT NULL DEFAULT '0',
                                `errorSummary` text NOT NULL,
                                `status` tinyint(1) NOT NULL DEFAULT '0',
                                `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                `processedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `migration`
--
ALTER TABLE `migration`
    ADD PRIMARY KEY (`version`);

--
-- Indexes for table `tableCompare`
--
ALTER TABLE `tableCompare`
    ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tableCompare`
--
ALTER TABLE `tableCompare`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
