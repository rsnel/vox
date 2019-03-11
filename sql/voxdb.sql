-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 11, 2019 at 07:01 AM
-- Server version: 10.1.37-MariaDB-0+deb9u1
-- PHP Version: 5.6.33-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `voxdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `avail`
--

CREATE TABLE `avail` (
  `avail_id` int(11) NOT NULL,
  `time_id` int(11) NOT NULL,
  `ppl_id` int(11) NOT NULL,
  `subj_id` int(11) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT '25'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `avails`
-- (See below for the actual view)
--
CREATE TABLE `avails` (
`time_id` int(11)
,`ppl_id` int(11)
,`capacity` int(11)
,`subj_abbrevs` text
,`avail_ids` text
);

-- --------------------------------------------------------

--
-- Table structure for table `claim`
--

CREATE TABLE `claim` (
  `claim_id` int(11) NOT NULL,
  `ppl_id` int(11) NOT NULL,
  `avail_id` int(11) NOT NULL,
  `claim_locked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `claims`
-- (See below for the actual view)
--
CREATE TABLE `claims` (
`ppl_id` int(11)
,`locked` bigint(21) unsigned
,`ll_ppl_id` int(11)
,`time_id` int(11)
,`capacity` int(11)
,`subj_abbrevs` text
);

-- --------------------------------------------------------

--
-- Table structure for table `lok`
--

CREATE TABLE `lok` (
  `lok_id` int(11) NOT NULL,
  `lok_afk` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lok_active` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ppl`
--

CREATE TABLE `ppl` (
  `ppl_id` int(11) NOT NULL,
  `ppl_login` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ppl_forename` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ppl_prefix` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ppl_surname` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ppl_type` enum('leerling','personeel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ppl_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ppl2tag`
--

CREATE TABLE `ppl2tag` (
  `ppl2tag_id` int(11) NOT NULL,
  `ppl_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ppl2time2lok`
--

CREATE TABLE `ppl2time2lok` (
  `ppl2time2lok_id` int(11) NOT NULL,
  `ppl_id` int(11) NOT NULL,
  `time_id` int(11) NOT NULL,
  `lok_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subj`
--

CREATE TABLE `subj` (
  `subj_id` int(11) NOT NULL,
  `subj_abbrev` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subj_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE `tag` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag_type` enum('ROOSTERLLN','LEERJAAR','NIVEAU','TEMP') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ROOSTERLLN',
  `tag_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time`
--

CREATE TABLE `time` (
  `time_id` int(11) NOT NULL,
  `time_year` int(11) NOT NULL,
  `time_week` int(11) NOT NULL,
  `time_day` enum('ma','di','wo','do','vr','za','zo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_hour` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weken`
--

CREATE TABLE `weken` (
  `week_id` int(11) NOT NULL,
  `time_year` int(11) NOT NULL,
  `time_week` int(11) NOT NULL,
  `status_doc` int(11) NOT NULL DEFAULT '0',
  `status_lln` int(11) NOT NULL DEFAULT '0',
  `rooster_zichtbaar` int(11) NOT NULL DEFAULT '0',
  `week_titel` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `avails`
--
DROP TABLE IF EXISTS `avails`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `avails`  AS  select `avail`.`time_id` AS `time_id`,`avail`.`ppl_id` AS `ppl_id`,min(`avail`.`capacity`) AS `capacity`,group_concat(`subj`.`subj_abbrev` order by `subj`.`subj_abbrev` ASC separator ',') AS `subj_abbrevs`,group_concat(`avail`.`avail_id` separator ',') AS `avail_ids` from (`avail` join `subj` on((`avail`.`subj_id` = `subj`.`subj_id`))) where 1 group by `avail`.`time_id`,`avail`.`ppl_id` ;

-- --------------------------------------------------------

--
-- Structure for view `claims`
--
DROP TABLE IF EXISTS `claims`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `claims`  AS  select `avails`.`ppl_id` AS `ppl_id`,bit_or(`claim`.`claim_locked`) AS `locked`,`claim`.`ppl_id` AS `ll_ppl_id`,`avails`.`time_id` AS `time_id`,`avails`.`capacity` AS `capacity`,`avails`.`subj_abbrevs` AS `subj_abbrevs` from (`avails` left join `claim` on(find_in_set(`claim`.`avail_id`,`avails`.`avail_ids`))) group by `avails`.`time_id`,`avails`.`ppl_id`,`claim`.`ppl_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `avail`
--
ALTER TABLE `avail`
  ADD PRIMARY KEY (`avail_id`),
  ADD UNIQUE KEY `time_id` (`time_id`,`ppl_id`,`subj_id`),
  ADD KEY `subj_id` (`subj_id`),
  ADD KEY `avail_ibfk_2` (`ppl_id`);

--
-- Indexes for table `claim`
--
ALTER TABLE `claim`
  ADD PRIMARY KEY (`claim_id`),
  ADD UNIQUE KEY `ppl_id` (`ppl_id`,`avail_id`),
  ADD KEY `avail_id` (`avail_id`);

--
-- Indexes for table `lok`
--
ALTER TABLE `lok`
  ADD PRIMARY KEY (`lok_id`),
  ADD UNIQUE KEY `lok_afk` (`lok_afk`);

--
-- Indexes for table `ppl`
--
ALTER TABLE `ppl`
  ADD PRIMARY KEY (`ppl_id`),
  ADD UNIQUE KEY `ppl_login` (`ppl_login`);

--
-- Indexes for table `ppl2tag`
--
ALTER TABLE `ppl2tag`
  ADD PRIMARY KEY (`ppl2tag_id`),
  ADD KEY `ppl_id` (`ppl_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `ppl2time2lok`
--
ALTER TABLE `ppl2time2lok`
  ADD PRIMARY KEY (`ppl2time2lok_id`),
  ADD UNIQUE KEY `ppl_id_2` (`ppl_id`,`time_id`),
  ADD KEY `ppl_id` (`ppl_id`),
  ADD KEY `time_id` (`time_id`),
  ADD KEY `lok_id` (`lok_id`);

--
-- Indexes for table `subj`
--
ALTER TABLE `subj`
  ADD PRIMARY KEY (`subj_id`),
  ADD UNIQUE KEY `subj_abbrev` (`subj_abbrev`),
  ADD UNIQUE KEY `subj_name` (`subj_name`);

--
-- Indexes for table `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`tag_id`);

--
-- Indexes for table `time`
--
ALTER TABLE `time`
  ADD PRIMARY KEY (`time_id`),
  ADD UNIQUE KEY `time_year` (`time_year`,`time_week`,`time_day`,`time_hour`);

--
-- Indexes for table `weken`
--
ALTER TABLE `weken`
  ADD PRIMARY KEY (`week_id`),
  ADD UNIQUE KEY `year` (`time_year`,`time_week`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avail`
--
ALTER TABLE `avail`
  MODIFY `avail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3511;
--
-- AUTO_INCREMENT for table `claim`
--
ALTER TABLE `claim`
  MODIFY `claim_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14293;
--
-- AUTO_INCREMENT for table `lok`
--
ALTER TABLE `lok`
  MODIFY `lok_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `ppl`
--
ALTER TABLE `ppl`
  MODIFY `ppl_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;
--
-- AUTO_INCREMENT for table `ppl2tag`
--
ALTER TABLE `ppl2tag`
  MODIFY `ppl2tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1260;
--
-- AUTO_INCREMENT for table `ppl2time2lok`
--
ALTER TABLE `ppl2time2lok`
  MODIFY `ppl2time2lok_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `subj`
--
ALTER TABLE `subj`
  MODIFY `subj_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `tag`
--
ALTER TABLE `tag`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;
--
-- AUTO_INCREMENT for table `time`
--
ALTER TABLE `time`
  MODIFY `time_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;
--
-- AUTO_INCREMENT for table `weken`
--
ALTER TABLE `weken`
  MODIFY `week_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `avail`
--
ALTER TABLE `avail`
  ADD CONSTRAINT `avail_ibfk_1` FOREIGN KEY (`time_id`) REFERENCES `time` (`time_id`),
  ADD CONSTRAINT `avail_ibfk_2` FOREIGN KEY (`ppl_id`) REFERENCES `ppl` (`ppl_id`) ON UPDATE NO ACTION,
  ADD CONSTRAINT `avail_ibfk_3` FOREIGN KEY (`subj_id`) REFERENCES `subj` (`subj_id`);

--
-- Constraints for table `claim`
--
ALTER TABLE `claim`
  ADD CONSTRAINT `claim_ibfk_1` FOREIGN KEY (`ppl_id`) REFERENCES `ppl` (`ppl_id`) ON UPDATE NO ACTION,
  ADD CONSTRAINT `claim_ibfk_2` FOREIGN KEY (`avail_id`) REFERENCES `avail` (`avail_id`);

--
-- Constraints for table `ppl2tag`
--
ALTER TABLE `ppl2tag`
  ADD CONSTRAINT `ppl2tag_ibfk_1` FOREIGN KEY (`ppl_id`) REFERENCES `ppl` (`ppl_id`),
  ADD CONSTRAINT `ppl2tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`tag_id`);

--
-- Constraints for table `ppl2time2lok`
--
ALTER TABLE `ppl2time2lok`
  ADD CONSTRAINT `ppl2time2lok_ibfk_1` FOREIGN KEY (`ppl_id`) REFERENCES `ppl` (`ppl_id`),
  ADD CONSTRAINT `ppl2time2lok_ibfk_2` FOREIGN KEY (`time_id`) REFERENCES `time` (`time_id`),
  ADD CONSTRAINT `ppl2time2lok_ibfk_3` FOREIGN KEY (`lok_id`) REFERENCES `lok` (`lok_id`);

--
-- Constraints for table `time`
--
ALTER TABLE `time`
  ADD CONSTRAINT `time_ibfk_1` FOREIGN KEY (`time_year`,`time_week`) REFERENCES `weken` (`time_year`, `time_week`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
