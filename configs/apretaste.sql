-- phpMyAdmin SQL Dump
-- version 4.2.12deb2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 22, 2015 at 11:56 PM
-- Server version: 5.6.25-0ubuntu0.15.04.1
-- PHP Version: 5.6.4-4ubuntu6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `apretaste`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `levenshtein`(`s1` VARCHAR(255), `s2` VARCHAR(255)) RETURNS int(11)
    DETERMINISTIC
BEGIN
    DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
    DECLARE s1_char CHAR;
    -- max strlen=255
    DECLARE cv0, cv1 VARBINARY(256);
    SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;
    IF s1 = s2 THEN
      RETURN 0;
    ELSEIF s1_len = 0 THEN
      RETURN s2_len;
    ELSEIF s2_len = 0 THEN
      RETURN s1_len;
    ELSE
      WHILE j <= s2_len DO
        SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
      END WHILE;
      WHILE i <= s1_len DO
        SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
        WHILE j <= s2_len DO
          SET c = c + 1;
          IF s1_char = SUBSTRING(s2, j, 1) THEN 
            SET cost = 0; ELSE SET cost = 1;
          END IF;
          SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
          IF c > c_temp THEN SET c = c_temp; END IF;
            SET c_temp = CONV(HEX(SUBSTRING(cv1, j+1, 1)), 16, 10) + 1;
            IF c > c_temp THEN 
              SET c = c_temp; 
            END IF;
            SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
        END WHILE;
        SET cv1 = cv0, i = i + 1;
      END WHILE;
    END IF;
    RETURN c;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE IF NOT EXISTS `ads` (
`ads_id` int(11) NOT NULL,
  `time_inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `impresions` int(11) NOT NULL DEFAULT '0',
  `owner` varchar(50) NOT NULL,
  `title` varchar(20) NOT NULL,
  `description` varchar(250) NOT NULL,
  `expiration_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `paid_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_error`
--

CREATE TABLE IF NOT EXISTS `delivery_error` (
`id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `direction` enum('in','out') NOT NULL COMMENT 'in=received, out=sent',
  `reason` enum('hard-bounce','soft-bounce','spam','no-reply','loop','unknown') NOT NULL COMMENT 'The reason for the rejection',
  `error_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE IF NOT EXISTS `inventory` (
  `code` varchar(20) NOT NULL,
  `price` float NOT NULL,
  `name` varchar(250) NOT NULL,
  `seller` varchar(50) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `service` varchar(50) NOT NULL COMMENT 'Service wich payment function will be executed when the payment is finalized',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `invitations`
--

CREATE TABLE IF NOT EXISTS `invitations` (
`invitation_id` int(11) NOT NULL,
  `invitation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `email_inviter` varchar(50) NOT NULL,
  `email_invited` varchar(50) NOT NULL,
  `used` tinyint(1) NOT NULL,
  `used_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB AUTO_INCREMENT=891 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `jumper`
--

CREATE TABLE IF NOT EXISTS `jumper` (
  `email` varchar(50) NOT NULL,
  `sent_count` int(11) NOT NULL DEFAULT '0',
  `received_count` int(11) NOT NULL DEFAULT '0',
  `blocked_domains` varchar(1000) NOT NULL COMMENT 'Comma separated list of the domains that are blocked for that email',
  `status` enum('Inactive','SendOnly','ReceiveOnly','SendReceive') NOT NULL DEFAULT 'Inactive',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE IF NOT EXISTS `person` (
  `email` varchar(50) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `mother_name` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('M','F') DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `cellphone` varchar(10) DEFAULT NULL,
  `eyes` enum('NEGRO','CARMELITA','VERDE','AZUL','AVELLANA','OTRO') DEFAULT NULL,
  `skin` enum('NEGRO','BLANCO','MESTIZO','OTRO') DEFAULT NULL,
  `body_type` enum('DELGADO','MEDIO','EXTRA','ATLETICO') DEFAULT NULL,
  `hair` enum('TRIGUENO','CASTANO','RUBIO','NEGRO','ROJO','BLANCO','OTRO') DEFAULT NULL,
  `province` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD') DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `highest_school_level` enum('PRIMARIO','SECUNDARIO','TECNICO','UNIVERSITARIO','POSTGRADUADO','DOCTORADO','OTRO') DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `marital_status` enum('SOLTERO','SALIENDO','COMPROMETIDO','CASADO') DEFAULT NULL,
  `interests` varchar(1000) NOT NULL COMMENT 'Comma separated list of interests',
  `about_me` varchar(1000) DEFAULT NULL,
  `credit` float NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `last_update_date` datetime DEFAULT NULL,
  `updated_by_user` tinyint(1) NOT NULL DEFAULT '0',
  `picture` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `raffle`
--

CREATE TABLE IF NOT EXISTS `raffle` (
`raffle_id` int(11) NOT NULL,
  `item_desc` varchar(1000) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `winner_1` varchar(50) NOT NULL,
  `winner_2` varchar(50) NOT NULL,
  `winner_3` varchar(50) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE IF NOT EXISTS `service` (
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `usage_text` text NOT NULL,
  `creator_email` varchar(50) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `category` enum('negocios','ocio','academico','social','comunicaciones','informativo','adulto','otros') NOT NULL,
  `deploy_key` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE IF NOT EXISTS `ticket` (
`ticket_id` int(11) NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `raffle_id` int(11) DEFAULT NULL COMMENT 'NULL when the ticket belong to the current Raffle or ID of the Raffle where it was used',
  `email` varchar(50) NOT NULL,
  `paid` tinyint(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `transfer`
--

CREATE TABLE IF NOT EXISTS `transfer` (
`id` int(11) NOT NULL,
  `sender` varchar(50) NOT NULL,
  `receiver` varchar(50) NOT NULL,
  `amount` float NOT NULL,
  `transfer_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `confirmation_hash` varchar(32) NOT NULL,
  `transfered` tinyint(1) NOT NULL DEFAULT '0',
  `inventory_code` varchar(20) DEFAULT NULL COMMENT 'Code from the inventory table, if it was a purchase'
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `utilization`
--

CREATE TABLE IF NOT EXISTS `utilization` (
`usage_id` int(11) NOT NULL,
  `service` varchar(50) NOT NULL,
  `subservice` varchar(50) DEFAULT NULL,
  `query` varchar(1000) DEFAULT NULL,
  `requestor` varchar(50) NOT NULL,
  `request_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `response_time` time NOT NULL DEFAULT '00:00:00',
  `domain` varchar(30) NOT NULL,
  `ad_top` int(11) DEFAULT NULL,
  `ad_botton` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4877 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_search_ignored_words`
--

CREATE TABLE IF NOT EXISTS `_search_ignored_words` (
  `word` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_search_variations`
--

CREATE TABLE IF NOT EXISTS `_search_variations` (
  `word` varchar(30) NOT NULL,
  `variation` varchar(30) NOT NULL,
  `variation_type` enum('SYNONYM','TYPO') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_search_words`
--

CREATE TABLE IF NOT EXISTS `_search_words` (
  `word` varchar(30) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of times that word was used, or a typo of that word',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Useful to remove non-used words automatically'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_tienda_categories`
--

CREATE TABLE IF NOT EXISTS `_tienda_categories` (
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_tienda_post`
--

CREATE TABLE IF NOT EXISTS `_tienda_post` (
`id` int(11) NOT NULL,
  `contact_name` varchar(50) NOT NULL,
  `contact_email_1` varchar(50) DEFAULT NULL,
  `contact_email_2` varchar(50) DEFAULT NULL,
  `contact_email_3` varchar(50) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_cellphone` varchar(20) DEFAULT NULL,
  `location_city` varchar(50) DEFAULT NULL,
  `location_province` enum('pinar_del_rio','artemisa','la_habana','mayabeque','matanzas','cienfuegos','villa_clara','sancti_spiritus','ciego_de_avila','camaguey','las_tunas','granma','holguin','santiago_de_cuba','guantanamo','isla_de_la_juventud') DEFAULT NULL,
  `ad_title` varchar(250) NOT NULL,
  `ad_body` varchar(1000) NOT NULL,
  `category` varchar(20) NOT NULL,
  `taxonony_id` int(11) DEFAULT NULL,
  `number_of_pictures` int(2) DEFAULT NULL COMMENT '0 if there are no pictures for the post',
  `price` float NOT NULL,
  `currency` enum('CUC','CUP') NOT NULL,
  `date_time_posted` timestamp NULL DEFAULT NULL COMMENT 'Time the post was made on the source. Null if it is internal',
  `date_time_inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Time the post was inserted into this database',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `source` varchar(20) NOT NULL,
  `source_url` varchar(250) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=612159 DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
 ADD PRIMARY KEY (`ads_id`);

--
-- Indexes for table `delivery_error`
--
ALTER TABLE `delivery_error`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
 ADD PRIMARY KEY (`code`), ADD UNIQUE KEY `code` (`code`), ADD KEY `code_2` (`code`);

--
-- Indexes for table `invitations`
--
ALTER TABLE `invitations`
 ADD PRIMARY KEY (`invitation_id`);

--
-- Indexes for table `jumper`
--
ALTER TABLE `jumper`
 ADD PRIMARY KEY (`email`), ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `person`
--
ALTER TABLE `person`
 ADD PRIMARY KEY (`email`);

--
-- Indexes for table `raffle`
--
ALTER TABLE `raffle`
 ADD PRIMARY KEY (`raffle_id`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
 ADD PRIMARY KEY (`name`);

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
 ADD PRIMARY KEY (`ticket_id`);

--
-- Indexes for table `transfer`
--
ALTER TABLE `transfer`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utilization`
--
ALTER TABLE `utilization`
 ADD PRIMARY KEY (`usage_id`);

--
-- Indexes for table `_search_ignored_words`
--
ALTER TABLE `_search_ignored_words`
 ADD PRIMARY KEY (`word`);

--
-- Indexes for table `_search_variations`
--
ALTER TABLE `_search_variations`
 ADD PRIMARY KEY (`word`,`variation`);

--
-- Indexes for table `_search_words`
--
ALTER TABLE `_search_words`
 ADD PRIMARY KEY (`word`);

--
-- Indexes for table `_tienda_categories`
--
ALTER TABLE `_tienda_categories`
 ADD PRIMARY KEY (`code`), ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `_tienda_post`
--
ALTER TABLE `_tienda_post`
 ADD PRIMARY KEY (`id`), ADD FULLTEXT KEY `ad_title` (`ad_title`,`ad_body`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
MODIFY `ads_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `delivery_error`
--
ALTER TABLE `delivery_error`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=15;
--
-- AUTO_INCREMENT for table `invitations`
--
ALTER TABLE `invitations`
MODIFY `invitation_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=891;
--
-- AUTO_INCREMENT for table `raffle`
--
ALTER TABLE `raffle`
MODIFY `raffle_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=158;
--
-- AUTO_INCREMENT for table `transfer`
--
ALTER TABLE `transfer`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=70;
--
-- AUTO_INCREMENT for table `utilization`
--
ALTER TABLE `utilization`
MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4877;
--
-- AUTO_INCREMENT for table `_tienda_post`
--
ALTER TABLE `_tienda_post`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=612159;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
