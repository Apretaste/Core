-- phpMyAdmin SQL Dump
-- version 4.2.12deb2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 24, 2016 at 12:28 AM
-- Server version: 5.6.25-0ubuntu0.15.04.1
-- PHP Version: 5.6.4-4ubuntu6.4

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

CREATE DEFINER=`root`@`localhost` FUNCTION `SPLIT_STR`(
  x VARCHAR(255),
  delim VARCHAR(12),
  pos INT
) RETURNS varchar(255) CHARSET latin1
RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(x, delim, pos),
       LENGTH(SUBSTRING_INDEX(x, delim, pos -1)) + 1),
       delim, '')$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE IF NOT EXISTS `ads` (
`id` int(11) NOT NULL,
  `time_inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `impresions` int(11) NOT NULL DEFAULT '0',
  `clicks` int(11) NOT NULL DEFAULT '0',
  `owner` char(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `expiration_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `paid_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `coverage_area` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD','CUBA','WEST','EAST','CENTER') DEFAULT 'CUBA',
  `from_age` varchar(3) DEFAULT 'ALL',
  `to_age` varchar(3) DEFAULT 'ALL',
  `gender` enum('M','F','ALL') DEFAULT 'ALL',
  `eyes` enum('NEGRO','CARMELITA','VERDE','AZUL','AVELLANA','OTRO','ALL') DEFAULT 'ALL',
  `skin` enum('NEGRO','BLANCO','MESTIZO','OTRO','ALL') DEFAULT 'ALL',
  `body_type` enum('DELGADO','MEDIO','EXTRA','ATLETICO','ALL') DEFAULT 'ALL',
  `hair` enum('TRIGUENO','CASTANO','RUBIO','NEGRO','ROJO','BLANCO','OTRO','ALL') DEFAULT 'ALL',
  `province` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANCTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD','ALL') DEFAULT 'ALL',
  `highest_school_level` enum('PRIMARIO','SECUNDARIO','TECNICO','UNIVERSITARIO','POSTGRADUADO','DOCTORADO','OTRO','ALL') DEFAULT 'ALL',
  `marital_status` enum('SOLTERO','SALIENDO','COMPROMETIDO','CASADO','ALL') DEFAULT 'ALL',
  `sexual_orientation` enum('BI','HETERO','HOMO','ALL') DEFAULT 'ALL',
  `religion` enum('ATEISMO','SECULARISMO','AGNOSTICISMO','ISLAM','JUDAISTA','ABAKUA','SANTERO','YORUBA','BUDISMO','CATOLICISMO','OTRA','CRISTIANISMO','ALL') DEFAULT 'ALL',
  `last_usage` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `autoinvitations`
--

CREATE TABLE IF NOT EXISTS `autoinvitations` (
  `email` char(100) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `error` tinyint(1) NOT NULL DEFAULT '0',
  `processed` timestamp NULL DEFAULT NULL,
  `source` varchar(10) NOT NULL DEFAULT 'MANUAL'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='To add emails that will be invited automatically by our cron task';

-- --------------------------------------------------------

--
-- Table structure for table `delivery_checked`
--

CREATE TABLE IF NOT EXISTS `delivery_checked` (
`id` int(11) NOT NULL,
  `email` char(100) NOT NULL,
  `reason` varchar(15) NOT NULL,
  `code` int(3) NOT NULL COMMENT 'Status returned by the email validator',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=55497 DEFAULT CHARSET=latin1 COMMENT='To store all emails checked by the email validator service';

-- --------------------------------------------------------

--
-- Table structure for table `delivery_dropped`
--

CREATE TABLE IF NOT EXISTS `delivery_dropped` (
`id` int(11) NOT NULL,
  `email` char(100) NOT NULL,
  `sender` varchar(50) NOT NULL,
  `reason` varchar(15) NOT NULL,
  `code` varchar(5) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=34116 DEFAULT CHARSET=latin1 COMMENT='Save dropped emails in Mandrill';

-- --------------------------------------------------------

--
-- Table structure for table `delivery_received`
--

CREATE TABLE IF NOT EXISTS `delivery_received` (
`id` int(11) NOT NULL,
  `user` char(100) NOT NULL,
  `mailbox` char(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `attachments_count` int(2) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `webhook` varchar(15) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=100674 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_sent`
--

CREATE TABLE IF NOT EXISTS `delivery_sent` (
`id` int(11) NOT NULL,
  `mailbox` char(100) NOT NULL,
  `user` char(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `images` tinyint(1) NOT NULL DEFAULT '0',
  `attachments` tinyint(1) NOT NULL DEFAULT '0',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `domain` varchar(30) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=413129 DEFAULT CHARSET=latin1 COMMENT='List of emails successfully sent';

-- --------------------------------------------------------

--
-- Table structure for table `first_timers`
--

CREATE TABLE IF NOT EXISTS `first_timers` (
  `email` char(100) NOT NULL DEFAULT '',
  `source` varchar(255) NOT NULL DEFAULT '',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE IF NOT EXISTS `inventory` (
  `code` varchar(20) NOT NULL,
  `price` float NOT NULL,
  `name` varchar(250) NOT NULL,
  `seller` char(100) NOT NULL,
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
  `invitation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email_inviter` char(100) NOT NULL,
  `email_invited` char(100) NOT NULL,
  `used` tinyint(1) NOT NULL,
  `used_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB AUTO_INCREMENT=34606 DEFAULT CHARSET=latin1;

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
  `email` char(100) NOT NULL,
  `username` varchar(15) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_access` timestamp NULL DEFAULT NULL,
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
  `province` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANCTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD') DEFAULT NULL,
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
  `picture` tinyint(1) NOT NULL DEFAULT '0',
  `cupido` tinyint(1) NOT NULL DEFAULT '1',
  `sexual_orientation` enum('BI','HETERO','HOMO') NOT NULL DEFAULT 'HETERO',
  `religion` enum('ATEISMO','SECULARISMO','AGNOSTICISMO','ISLAM','JUDAISTA','ABAKUA','SANTERO','YORUBA','BUDISMO','CATOLICISMO','OTRA','CRISTIANISMO') DEFAULT NULL,
  `source` enum('email','api','manual') NOT NULL DEFAULT 'email'
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `remarketing`
--

CREATE TABLE IF NOT EXISTS `remarketing` (
`id` int(11) NOT NULL,
  `email` char(100) NOT NULL,
  `type` varchar(10) NOT NULL,
  `sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opened` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11384 DEFAULT CHARSET=latin1 COMMENT='Emails remarketed to attract our users back';

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE IF NOT EXISTS `service` (
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `usage_text` text NOT NULL,
  `creator_email` char(100) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `category` enum('negocios','ocio','academico','social','comunicaciones','informativo','adulto','otros') NOT NULL,
  `listed` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 if the service will be listed on the list of services',
  `ads` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'service should show ads or not'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `task_status`
--

CREATE TABLE IF NOT EXISTS `task_status` (
  `task` varchar(20) NOT NULL,
  `executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delay` int(11) NOT NULL COMMENT 'Time to finish, in seconds',
  `values` text NOT NULL COMMENT 'Extra values returned by the task',
  `frequency` int(11) NOT NULL DEFAULT '1' COMMENT 'Days to run. If passed this number should be a problem'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Report the status of cron tasks running';

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE IF NOT EXISTS `ticket` (
`ticket_id` int(11) NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raffle_id` int(11) DEFAULT NULL COMMENT 'NULL when the ticket belong to the current Raffle or ID of the Raffle where it was used',
  `email` char(100) NOT NULL,
  `paid` tinyint(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11129 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `transfer`
--

CREATE TABLE IF NOT EXISTS `transfer` (
`id` int(11) NOT NULL,
  `sender` char(100) NOT NULL,
  `receiver` char(100) NOT NULL,
  `amount` float NOT NULL,
  `transfer_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmation_hash` varchar(32) NOT NULL,
  `transfered` tinyint(1) NOT NULL DEFAULT '0',
  `inventory_code` varchar(20) DEFAULT NULL COMMENT 'Code from the inventory table, if it was a purchase'
) ENGINE=InnoDB AUTO_INCREMENT=1367 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `utilization`
--

CREATE TABLE IF NOT EXISTS `utilization` (
`usage_id` int(11) NOT NULL,
  `service` varchar(50) NOT NULL,
  `subservice` varchar(50) DEFAULT NULL,
  `query` varchar(1000) DEFAULT NULL,
  `requestor` char(100) NOT NULL,
  `request_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `response_time` time NOT NULL DEFAULT '00:00:00',
  `domain` varchar(30) NOT NULL,
  `ad_top` int(11) DEFAULT NULL,
  `ad_bottom` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=436426 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_bitcoin_accounts`
--

CREATE TABLE IF NOT EXISTS `_bitcoin_accounts` (
  `email` char(100) NOT NULL,
  `private_key` varchar(50) DEFAULT NULL,
  `public_key` varchar(50) DEFAULT NULL,
  `active` bit(1) NOT NULL DEFAULT b'1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_cupido_ignores`
--

CREATE TABLE IF NOT EXISTS `_cupido_ignores` (
  `email1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ignore_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_cupido_likes`
--

CREATE TABLE IF NOT EXISTS `_cupido_likes` (
  `email1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `like_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_navegar_searchs`
--

CREATE TABLE IF NOT EXISTS `_navegar_searchs` (
  `search_source` varchar(10) NOT NULL DEFAULT '',
  `search_query` varchar(255) NOT NULL DEFAULT '',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usage_count` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_navegar_visits`
--

CREATE TABLE IF NOT EXISTS `_navegar_visits` (
  `site` varchar(255) NOT NULL,
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usage_count` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_note`
--

CREATE TABLE IF NOT EXISTS `_note` (
`id` int(11) NOT NULL,
  `from_user` char(100) DEFAULT NULL,
  `to_user` char(100) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `send_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=9708 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_pizarra_block`
--

CREATE TABLE IF NOT EXISTS `_pizarra_block` (
  `email` char(100) NOT NULL,
  `blocked` char(100) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_pizarra_follow`
--

CREATE TABLE IF NOT EXISTS `_pizarra_follow` (
  `email` char(100) NOT NULL,
  `followed` char(100) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_pizarra_notes`
--

CREATE TABLE IF NOT EXISTS `_pizarra_notes` (
`id` int(11) NOT NULL,
  `email` char(100) NOT NULL,
  `text` varchar(140) NOT NULL,
  `likes` int(5) NOT NULL DEFAULT '0',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Autopost from ourside, 0=User insertion'
) ENGINE=InnoDB AUTO_INCREMENT=13377 DEFAULT CHARSET=latin1;

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
-- Table structure for table `_survey`
--

CREATE TABLE IF NOT EXISTS `_survey` (
`id` bigint(20) NOT NULL,
  `customer` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `details` varchar(1000) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deadline` date DEFAULT NULL,
  `value` float NOT NULL COMMENT 'Amount added to the credit when completed',
  `answers` int(11) NOT NULL DEFAULT '0' COMMENT 'Times fully answered'
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_survey_answer`
--

CREATE TABLE IF NOT EXISTS `_survey_answer` (
`id` bigint(20) NOT NULL,
  `question` bigint(20) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_survey_answer_choosen`
--

CREATE TABLE IF NOT EXISTS `_survey_answer_choosen` (
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `survey` int(11) NOT NULL,
  `question` int(11) NOT NULL,
  `answer` bigint(20) NOT NULL,
  `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `_survey_question`
--

CREATE TABLE IF NOT EXISTS `_survey_question` (
`id` bigint(20) NOT NULL,
  `survey` bigint(20) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=latin1;

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
) ENGINE=MyISAM AUTO_INCREMENT=951559 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `__sms_messages`
--

CREATE TABLE IF NOT EXISTS `__sms_messages` (
  `id` varchar(255) DEFAULT NULL,
  `sent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` char(100) DEFAULT NULL,
  `cellphone` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `discount` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `autoinvitations`
--
ALTER TABLE `autoinvitations`
 ADD PRIMARY KEY (`email`), ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `delivery_checked`
--
ALTER TABLE `delivery_checked`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_dropped`
--
ALTER TABLE `delivery_dropped`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_received`
--
ALTER TABLE `delivery_received`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_sent`
--
ALTER TABLE `delivery_sent`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `first_timers`
--
ALTER TABLE `first_timers`
 ADD PRIMARY KEY (`email`,`source`);

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
 ADD PRIMARY KEY (`email`), ADD UNIQUE KEY `username` (`username`), ADD KEY `username_2` (`username`);

--
-- Indexes for table `raffle`
--
ALTER TABLE `raffle`
 ADD PRIMARY KEY (`raffle_id`);

--
-- Indexes for table `remarketing`
--
ALTER TABLE `remarketing`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`), ADD KEY `email` (`email`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
 ADD PRIMARY KEY (`name`);

--
-- Indexes for table `task_status`
--
ALTER TABLE `task_status`
 ADD PRIMARY KEY (`task`);

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
-- Indexes for table `_bitcoin_accounts`
--
ALTER TABLE `_bitcoin_accounts`
 ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `_cupido_ignores`
--
ALTER TABLE `_cupido_ignores`
 ADD PRIMARY KEY (`email1`,`email2`);

--
-- Indexes for table `_cupido_likes`
--
ALTER TABLE `_cupido_likes`
 ADD PRIMARY KEY (`email1`,`email2`);

--
-- Indexes for table `_navegar_searchs`
--
ALTER TABLE `_navegar_searchs`
 ADD PRIMARY KEY (`search_source`,`search_query`);

--
-- Indexes for table `_navegar_visits`
--
ALTER TABLE `_navegar_visits`
 ADD PRIMARY KEY (`site`);

--
-- Indexes for table `_note`
--
ALTER TABLE `_note`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `_pizarra_block`
--
ALTER TABLE `_pizarra_block`
 ADD PRIMARY KEY (`email`,`blocked`);

--
-- Indexes for table `_pizarra_follow`
--
ALTER TABLE `_pizarra_follow`
 ADD PRIMARY KEY (`email`,`followed`);

--
-- Indexes for table `_pizarra_notes`
--
ALTER TABLE `_pizarra_notes`
 ADD PRIMARY KEY (`id`);

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
-- Indexes for table `_survey`
--
ALTER TABLE `_survey`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `_survey_answer`
--
ALTER TABLE `_survey_answer`
 ADD PRIMARY KEY (`id`), ADD KEY `answer_question` (`question`);

--
-- Indexes for table `_survey_answer_choosen`
--
ALTER TABLE `_survey_answer_choosen`
 ADD PRIMARY KEY (`email`,`answer`), ADD KEY `answer_choosen` (`answer`);

--
-- Indexes for table `_survey_question`
--
ALTER TABLE `_survey_question`
 ADD PRIMARY KEY (`id`), ADD KEY `question_survay` (`survey`);

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
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;
--
-- AUTO_INCREMENT for table `delivery_checked`
--
ALTER TABLE `delivery_checked`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=55497;
--
-- AUTO_INCREMENT for table `delivery_dropped`
--
ALTER TABLE `delivery_dropped`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=34116;
--
-- AUTO_INCREMENT for table `delivery_received`
--
ALTER TABLE `delivery_received`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=100674;
--
-- AUTO_INCREMENT for table `delivery_sent`
--
ALTER TABLE `delivery_sent`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=413129;
--
-- AUTO_INCREMENT for table `invitations`
--
ALTER TABLE `invitations`
MODIFY `invitation_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=34606;
--
-- AUTO_INCREMENT for table `raffle`
--
ALTER TABLE `raffle`
MODIFY `raffle_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `remarketing`
--
ALTER TABLE `remarketing`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11384;
--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11129;
--
-- AUTO_INCREMENT for table `transfer`
--
ALTER TABLE `transfer`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1367;
--
-- AUTO_INCREMENT for table `utilization`
--
ALTER TABLE `utilization`
MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=436426;
--
-- AUTO_INCREMENT for table `_note`
--
ALTER TABLE `_note`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9708;
--
-- AUTO_INCREMENT for table `_pizarra_notes`
--
ALTER TABLE `_pizarra_notes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13377;
--
-- AUTO_INCREMENT for table `_survey`
--
ALTER TABLE `_survey`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `_survey_answer`
--
ALTER TABLE `_survey_answer`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=164;
--
-- AUTO_INCREMENT for table `_survey_question`
--
ALTER TABLE `_survey_question`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `_tienda_post`
--
ALTER TABLE `_tienda_post`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=951559;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `_survey_answer`
--
ALTER TABLE `_survey_answer`
ADD CONSTRAINT `answer_question` FOREIGN KEY (`question`) REFERENCES `_survey_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `_survey_answer_choosen`
--
ALTER TABLE `_survey_answer_choosen`
ADD CONSTRAINT `answer_choosen` FOREIGN KEY (`answer`) REFERENCES `_survey_answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `_survey_question`
--
ALTER TABLE `_survey_question`
ADD CONSTRAINT `question_survay` FOREIGN KEY (`survey`) REFERENCES `_survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
