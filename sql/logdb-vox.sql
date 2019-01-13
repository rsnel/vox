-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 13, 2019 at 04:04 PM
-- Server version: 10.1.26-MariaDB-0+deb9u1
-- PHP Version: 5.6.33-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `logdb-vox`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `config`
-- (See below for the actual view)
--
CREATE TABLE `config` (
`log_id` int(11)
,`log_auth_user` varchar(64)
,`timestamp` timestamp
,`config_key` varchar(32)
,`config_value` mediumtext
);

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `log_id` int(11) NOT NULL,
  `prev_log_id` int(11) DEFAULT NULL,
  `foreign_table` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foreign_id` int(11) DEFAULT NULL,
  `session_prev_log_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_config`
--

CREATE TABLE `log_config` (
  `log_config_id` int(11) NOT NULL,
  `config_key` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_config_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_passwords`
--

CREATE TABLE `log_passwords` (
  `log_password_id` int(11) NOT NULL,
  `auth_user` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(123) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_permissions`
--

CREATE TABLE `log_permissions` (
  `log_permissions_id` int(11) NOT NULL,
  `auth_user` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `permission` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `session_config_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `passwords`
-- (See below for the actual view)
--
CREATE TABLE `passwords` (
`log_id` int(11)
,`log_auth_user` varchar(64)
,`timestamp` timestamp
,`auth_user` varchar(64)
,`password_hash` varchar(123)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `permissions`
-- (See below for the actual view)
--
CREATE TABLE `permissions` (
`log_id` int(11)
,`log_auth_user` varchar(64)
,`timestamp` timestamp
,`user` varchar(64)
,`permission` varchar(64)
);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `session_guid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_useragent_id` int(11) NOT NULL,
  `session_address` varchar(46) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_config_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_configs`
--

CREATE TABLE `session_configs` (
  `session_config_id` int(11) NOT NULL,
  `session_config` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_log`
--

CREATE TABLE `session_log` (
  `session_log_id` int(11) NOT NULL,
  `session_prev_log_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `auth_user` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ppl_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_uri` longtext COLLATE utf8mb4_unicode_ci,
  `success_msg` longtext COLLATE utf8mb4_unicode_ci,
  `error_msg` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `useragents`
--

CREATE TABLE `useragents` (
  `useragent_id` int(11) NOT NULL,
  `useragent_string` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `useragent_hash` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `config`
--
DROP TABLE IF EXISTS `config`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `config`  AS  select `log`.`log_id` AS `log_id`,`session_log`.`auth_user` AS `log_auth_user`,`session_log`.`timestamp` AS `timestamp`,`log_config`.`config_key` AS `config_key`,`log_config`.`config_value` AS `config_value` from (((`log` join `log_config` on((`log_config`.`log_config_id` = `log`.`foreign_id`))) join `session_log` on((`log`.`session_prev_log_id` = `session_log`.`session_prev_log_id`))) left join `log` `log_next` on((`log_next`.`prev_log_id` = `log`.`log_id`))) where ((`log`.`foreign_table` = 'log_config') and isnull(`log_next`.`log_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `passwords`
--
DROP TABLE IF EXISTS `passwords`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `passwords`  AS  select `log`.`log_id` AS `log_id`,`session_log`.`auth_user` AS `log_auth_user`,`session_log`.`timestamp` AS `timestamp`,`log_passwords`.`auth_user` AS `auth_user`,`log_passwords`.`password_hash` AS `password_hash` from (((`log` join `log_passwords` on((`log_passwords`.`log_password_id` = `log`.`foreign_id`))) join `session_log` on((`log`.`session_prev_log_id` = `session_log`.`session_prev_log_id`))) left join `log` `log_next` on((`log_next`.`prev_log_id` = `log`.`log_id`))) where ((`log`.`foreign_table` = 'log_passwords') and isnull(`log_next`.`log_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `permissions`
--
DROP TABLE IF EXISTS `permissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `permissions`  AS  select `log`.`log_id` AS `log_id`,`session_log`.`auth_user` AS `log_auth_user`,`session_log`.`timestamp` AS `timestamp`,`log_permissions`.`auth_user` AS `user`,`log_permissions`.`permission` AS `permission` from (((`log` join `log_permissions` on((`log_permissions`.`log_permissions_id` = `log`.`foreign_id`))) join `session_log` on((`log`.`session_prev_log_id` = `session_log`.`session_prev_log_id`))) left join `log` `log_next` on((`log_next`.`prev_log_id` = `log`.`log_id`))) where ((`log`.`foreign_table` = 'log_permissions') and isnull(`log_next`.`log_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `log_prev_id` (`prev_log_id`);

--
-- Indexes for table `log_config`
--
ALTER TABLE `log_config`
  ADD PRIMARY KEY (`log_config_id`);

--
-- Indexes for table `log_passwords`
--
ALTER TABLE `log_passwords`
  ADD PRIMARY KEY (`log_password_id`),
  ADD UNIQUE KEY `auth_user` (`auth_user`,`password_hash`);

--
-- Indexes for table `log_permissions`
--
ALTER TABLE `log_permissions`
  ADD PRIMARY KEY (`log_permissions_id`),
  ADD UNIQUE KEY `auth_user` (`auth_user`,`permission`,`session_config_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_guid` (`session_guid`,`session_useragent_id`,`session_address`,`session_config_id`);

--
-- Indexes for table `session_configs`
--
ALTER TABLE `session_configs`
  ADD PRIMARY KEY (`session_config_id`),
  ADD UNIQUE KEY `session_config` (`session_config`);

--
-- Indexes for table `session_log`
--
ALTER TABLE `session_log`
  ADD PRIMARY KEY (`session_log_id`),
  ADD UNIQUE KEY `session_prev_log_id` (`session_prev_log_id`);

--
-- Indexes for table `useragents`
--
ALTER TABLE `useragents`
  ADD PRIMARY KEY (`useragent_id`),
  ADD UNIQUE KEY `useragent_hash` (`useragent_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;
--
-- AUTO_INCREMENT for table `log_config`
--
ALTER TABLE `log_config`
  MODIFY `log_config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `log_passwords`
--
ALTER TABLE `log_passwords`
  MODIFY `log_password_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;
--
-- AUTO_INCREMENT for table `log_permissions`
--
ALTER TABLE `log_permissions`
  MODIFY `log_permissions_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;
--
-- AUTO_INCREMENT for table `session_configs`
--
ALTER TABLE `session_configs`
  MODIFY `session_config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `session_log`
--
ALTER TABLE `session_log`
  MODIFY `session_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3930;
--
-- AUTO_INCREMENT for table `useragents`
--
ALTER TABLE `useragents`
  MODIFY `useragent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
