-- MySQL dump 10.13  Distrib 5.6.30, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: apretaste
-- ------------------------------------------------------
-- Server version	5.6.30-0ubuntu0.15.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `__sms_messages`
--

DROP TABLE IF EXISTS `__sms_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `__sms_messages` (
  `id` varchar(255) DEFAULT NULL,
  `sent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` char(100) DEFAULT NULL,
  `cellphone` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `discount` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_bitcoin_accounts`
--

DROP TABLE IF EXISTS `_bitcoin_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_bitcoin_accounts` (
  `email` char(100) NOT NULL,
  `private_key` varchar(50) DEFAULT NULL,
  `public_key` varchar(50) DEFAULT NULL,
  `active` bit(1) NOT NULL DEFAULT b'1',
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_cupido_ignores`
--

DROP TABLE IF EXISTS `_cupido_ignores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_cupido_ignores` (
  `email1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ignore_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email1`,`email2`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_cupido_likes`
--

DROP TABLE IF EXISTS `_cupido_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_cupido_likes` (
  `email1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `like_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email1`,`email2`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_answer`
--

DROP TABLE IF EXISTS `_escuela_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `xorder` int(11) DEFAULT NULL,
  `right_choosen` tinyint(1) NOT NULL DEFAULT '0',
  `question` int(11) NOT NULL,
  `chapter` int(11) DEFAULT NULL,
  `course` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `question` (`question`),
  KEY `chapter` (`chapter`),
  KEY `course` (`course`),
  CONSTRAINT `_escuela_answer_ibfk_1` FOREIGN KEY (`question`) REFERENCES `_escuela_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_answer_ibfk_2` FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_answer_ibfk_3` FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_answer_choosen`
--

DROP TABLE IF EXISTS `_escuela_answer_choosen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_answer_choosen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `answer` int(11) NOT NULL,
  `question` int(11) NOT NULL,
  `chapter` int(11) DEFAULT NULL,
  `course` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`,`question`),
  KEY `answer` (`answer`),
  KEY `question` (`question`),
  KEY `chapter` (`chapter`),
  KEY `course` (`course`),
  CONSTRAINT `_escuela_answer_choosen_ibfk_1` FOREIGN KEY (`answer`) REFERENCES `_escuela_answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_answer_choosen_ibfk_2` FOREIGN KEY (`question`) REFERENCES `_escuela_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_answer_choosen_ibfk_3` FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_answer_choosen_ibfk_4` FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_chapter`
--

DROP TABLE IF EXISTS `_escuela_chapter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_chapter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `course` int(11) DEFAULT NULL,
  `xtype` enum('CAPITULO','PRUEBA') DEFAULT 'CAPITULO',
  `xorder` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course` (`course`),
  CONSTRAINT `_escuela_chapter_ibfk_1` FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_chapter_viewed`
--

DROP TABLE IF EXISTS `_escuela_chapter_viewed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_chapter_viewed` (
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `chapter` int(11) NOT NULL DEFAULT '0',
  `course` int(11) NOT NULL,
  `date_viewed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`,`chapter`),
  KEY `chapter` (`chapter`),
  KEY `course` (`course`),
  CONSTRAINT `_escuela_chapter_viewed_ibfk_1` FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_chapter_viewed_ibfk_2` FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_course`
--

DROP TABLE IF EXISTS `_escuela_course`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_course` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` varchar(1024) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `teacher` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `popularity` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `teacher` (`teacher`),
  CONSTRAINT `_escuela_course_ibfk_1` FOREIGN KEY (`teacher`) REFERENCES `_escuela_teacher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_feedback`
--

DROP TABLE IF EXISTS `_escuela_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(255) NOT NULL,
  `answers` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_feedback_received`
--

DROP TABLE IF EXISTS `_escuela_feedback_received`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_feedback_received` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback` int(11) NOT NULL,
  `course` int(11) NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `answer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback` (`feedback`),
  KEY `course` (`course`),
  CONSTRAINT `_escuela_feedback_received_ibfk_1` FOREIGN KEY (`feedback`) REFERENCES `_escuela_feedback` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_feedback_received_ibfk_2` FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_images`
--

DROP TABLE IF EXISTS `_escuela_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_images` (
  `id` varchar(50) NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `mime_type` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `chapter` int(11) NOT NULL,
  `course` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chapter` (`chapter`),
  KEY `course` (`course`),
  CONSTRAINT `_escuela_images_ibfk_1` FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_images_ibfk_2` FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_question`
--

DROP TABLE IF EXISTS `_escuela_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `chapter` int(11) DEFAULT NULL,
  `course` int(11) DEFAULT NULL,
  `xorder` int(11) DEFAULT NULL,
  `answer` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chapter` (`chapter`),
  KEY `course` (`course`),
  CONSTRAINT `_escuela_question_ibfk_1` FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_escuela_question_ibfk_2` FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_escuela_teacher`
--

DROP TABLE IF EXISTS `_escuela_teacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_escuela_teacher` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_idea`
--

DROP TABLE IF EXISTS `_idea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_idea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(255) NOT NULL,
  `text` varchar(1024) NOT NULL,
  `improving` int(11) DEFAULT NULL,
  `commenting` int(11) DEFAULT NULL,
  `likes` int(11) NOT NULL DEFAULT '0',
  `unlikes` int(11) NOT NULL DEFAULT '0',
  `spam` int(11) NOT NULL DEFAULT '0',
  `inserted_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `taxonomy` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_navegar_searchs`
--

DROP TABLE IF EXISTS `_navegar_searchs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_navegar_searchs` (
  `search_source` varchar(10) NOT NULL DEFAULT '',
  `search_query` varchar(255) NOT NULL DEFAULT '',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usage_count` int(11) DEFAULT '1',
  PRIMARY KEY (`search_source`,`search_query`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_navegar_visits`
--

DROP TABLE IF EXISTS `_navegar_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_navegar_visits` (
  `site` varchar(255) NOT NULL,
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usage_count` int(11) DEFAULT '1',
  PRIMARY KEY (`site`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_note`
--

DROP TABLE IF EXISTS `_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user` char(100) DEFAULT NULL,
  `to_user` char(100) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `send_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_piropazo_crowns`
--

DROP TABLE IF EXISTS `_piropazo_crowns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_piropazo_crowns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` char(100) NOT NULL,
  `crowned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_piropazo_flowers`
--

DROP TABLE IF EXISTS `_piropazo_flowers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_piropazo_flowers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender` char(100) NOT NULL,
  `receiver` char(100) NOT NULL,
  `sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_piropazo_people`
--

DROP TABLE IF EXISTS `_piropazo_people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_piropazo_people` (
  `email` char(100) NOT NULL,
  `flowers` int(5) NOT NULL DEFAULT '3',
  `crowns` int(5) NOT NULL DEFAULT '1',
  `likes` int(11) NOT NULL DEFAULT '0',
  `dislikes` int(11) NOT NULL DEFAULT '0',
  `crowned` timestamp NULL DEFAULT NULL COMMENT 'Last time the user was king/queen',
  `first_access` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_access` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_piropazo_relationships`
--

DROP TABLE IF EXISTS `_piropazo_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_piropazo_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_from` char(100) NOT NULL,
  `email_to` char(100) NOT NULL,
  `status` enum('like','dislike','match','blocked') NOT NULL,
  `expires_matched_blocked` timestamp NULL DEFAULT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_pizarra_block`
--

DROP TABLE IF EXISTS `_pizarra_block`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_pizarra_block` (
  `email` char(100) NOT NULL,
  `blocked` char(100) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`,`blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_pizarra_follow`
--

DROP TABLE IF EXISTS `_pizarra_follow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_pizarra_follow` (
  `email` char(100) NOT NULL,
  `followed` char(100) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`,`followed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_pizarra_notes`
--

DROP TABLE IF EXISTS `_pizarra_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_pizarra_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` char(100) NOT NULL,
  `text` varchar(140) NOT NULL,
  `likes` int(5) NOT NULL DEFAULT '0',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Autopost from ourside, 0=User insertion',
  `source` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13443 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_pizarra_seen_notes`
--

DROP TABLE IF EXISTS `_pizarra_seen_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_pizarra_seen_notes` (
  `note` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`note`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_search_ignored_words`
--

DROP TABLE IF EXISTS `_search_ignored_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_search_ignored_words` (
  `word` varchar(30) NOT NULL,
  PRIMARY KEY (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_search_variations`
--

DROP TABLE IF EXISTS `_search_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_search_variations` (
  `word` varchar(30) NOT NULL,
  `variation` varchar(30) NOT NULL,
  `variation_type` enum('SYNONYM','TYPO') NOT NULL,
  PRIMARY KEY (`word`,`variation`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_search_words`
--

DROP TABLE IF EXISTS `_search_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_search_words` (
  `word` varchar(30) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of times that word was used, or a typo of that word',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Useful to remove non-used words automatically',
  PRIMARY KEY (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_survey`
--

DROP TABLE IF EXISTS `_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_survey` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `details` varchar(1000) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deadline` date DEFAULT NULL,
  `value` float NOT NULL COMMENT 'Amount added to the credit when completed',
  `answers` int(11) NOT NULL DEFAULT '0' COMMENT 'Times fully answered',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_survey_answer`
--

DROP TABLE IF EXISTS `_survey_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_survey_answer` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `question` bigint(20) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `answer_question` (`question`),
  CONSTRAINT `answer_question` FOREIGN KEY (`question`) REFERENCES `_survey_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=170 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_survey_answer_choosen`
--

DROP TABLE IF EXISTS `_survey_answer_choosen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_survey_answer_choosen` (
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `survey` int(11) NOT NULL,
  `question` int(11) NOT NULL,
  `answer` bigint(20) NOT NULL,
  `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`,`answer`),
  KEY `answer_choosen` (`answer`),
  CONSTRAINT `answer_choosen` FOREIGN KEY (`answer`) REFERENCES `_survey_answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_survey_question`
--

DROP TABLE IF EXISTS `_survey_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_survey_question` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `survey` bigint(20) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question_survay` (`survey`),
  CONSTRAINT `question_survay` FOREIGN KEY (`survey`) REFERENCES `_survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_tienda_categories`
--

DROP TABLE IF EXISTS `_tienda_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_tienda_categories` (
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_tienda_orders`
--

DROP TABLE IF EXISTS `_tienda_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_tienda_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ci` varchar(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `inserted_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `address` varchar(255) DEFAULT NULL,
  `province` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANCTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `received` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_tienda_post`
--

DROP TABLE IF EXISTS `_tienda_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_tienda_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `source_url` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `ad_title` (`ad_title`,`ad_body`)
) ENGINE=MyISAM AUTO_INCREMENT=2942348 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_tienda_products`
--

DROP TABLE IF EXISTS `_tienda_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_tienda_products` (
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(1024) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `price` float NOT NULL,
  `shipping_price` float NOT NULL,
  `credits` float NOT NULL,
  `agency` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `inserted_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `_web_sites`
--

DROP TABLE IF EXISTS `_web_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_web_sites` (
  `domain` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `owner` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ads`
--

DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `last_usage` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `price` float NOT NULL DEFAULT '0.1',
  `related_service` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=319 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `authentication`
--

DROP TABLE IF EXISTS `authentication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authentication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` char(32) NOT NULL,
  `email` char(100) NOT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=latin1 COMMENT='Stores the tokens for authentication using the API';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoinvitations`
--

DROP TABLE IF EXISTS `autoinvitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoinvitations` (
  `email` char(100) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `error` tinyint(1) NOT NULL DEFAULT '0',
  `processed` timestamp NULL DEFAULT NULL,
  `source` varchar(10) NOT NULL DEFAULT 'MANUAL',
  PRIMARY KEY (`email`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='To add emails that will be invited automatically by our cron task';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campaign`
--

DROP TABLE IF EXISTS `campaign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sending_date` timestamp NULL DEFAULT NULL,
  `status` enum('WAITING','SENDING','ERROR','SENT') NOT NULL DEFAULT 'WAITING',
  `sent` int(5) NOT NULL DEFAULT '0',
  `opened` int(5) NOT NULL DEFAULT '0',
  `bounced` int(5) NOT NULL DEFAULT '0',
  `emails` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_checked`
--

DROP TABLE IF EXISTS `delivery_checked`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_checked` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` char(100) NOT NULL,
  `reason` varchar(15) NOT NULL,
  `code` int(3) NOT NULL COMMENT 'Status returned by the email validator',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55522 DEFAULT CHARSET=latin1 COMMENT='To store all emails checked by the email validator service';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_dropped`
--

DROP TABLE IF EXISTS `delivery_dropped`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_dropped` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` char(100) NOT NULL,
  `sender` varchar(50) NOT NULL,
  `reason` varchar(15) NOT NULL,
  `code` varchar(5) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Save dropped emails in Mandrill';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_received`
--

DROP TABLE IF EXISTS `delivery_received`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_received` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` char(100) NOT NULL,
  `mailbox` char(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `attachments_count` int(2) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `webhook` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=102126 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_sent`
--

DROP TABLE IF EXISTS `delivery_sent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_sent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mailbox` char(100) NOT NULL,
  `user` char(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `images` tinyint(1) NOT NULL DEFAULT '0',
  `attachments` tinyint(1) NOT NULL DEFAULT '0',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `domain` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=414452 DEFAULT CHARSET=latin1 COMMENT='List of emails successfully sent';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `domain`
--

DROP TABLE IF EXISTS `domain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `domain` (
  `domain` varchar(50) NOT NULL,
  `sent_count` int(11) NOT NULL DEFAULT '0',
  `default_service` varchar(50) NOT NULL DEFAULT 'ayuda',
  `group` varchar(20) NOT NULL DEFAULT 'apretaste',
  `blacklist` varchar(1000) NOT NULL COMMENT 'Comma separated list of the domains that are blocked for that email',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `last_usage` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`domain`),
  UNIQUE KEY `email` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `event_type` varchar(255) DEFAULT NULL,
  `event_data` varchar(2048) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `first_timers`
--

DROP TABLE IF EXISTS `first_timers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `first_timers` (
  `email` char(100) NOT NULL DEFAULT '',
  `source` varchar(255) NOT NULL DEFAULT '',
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`email`,`source`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `code` varchar(20) NOT NULL,
  `price` float NOT NULL,
  `name` varchar(250) NOT NULL,
  `seller` char(100) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `service` varchar(50) NOT NULL COMMENT 'Service wich payment function will be executed when the payment is finalized',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code`),
  UNIQUE KEY `code` (`code`),
  KEY `code_2` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invitations`
--

DROP TABLE IF EXISTS `invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invitation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email_inviter` char(100) NOT NULL,
  `email_invited` char(100) NOT NULL,
  `source` enum('promoter','internal','abroad','campaign','manual') NOT NULL DEFAULT 'manual',
  `used` tinyint(1) NOT NULL,
  `used_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jumper`
--

DROP TABLE IF EXISTS `jumper`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jumper` (
  `email` varchar(50) NOT NULL,
  `sent_count` int(11) NOT NULL DEFAULT '0',
  `received_count` int(11) NOT NULL DEFAULT '0',
  `default_service` varchar(50) NOT NULL DEFAULT 'ayuda',
  `group` varchar(20) NOT NULL DEFAULT 'apretaste',
  `blocked_domains` varchar(1000) NOT NULL COMMENT 'Comma separated list of the domains that are blocked for that email',
  `status` enum('Inactive','SendOnly','ReceiveOnly','SendReceive') NOT NULL DEFAULT 'Inactive',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `promoter` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 if the email is from a promoter',
  PRIMARY KEY (`email`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `keys`
--

DROP TABLE IF EXISTS `keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `keys` (
  `email` char(100) NOT NULL,
  `privatekey` varchar(1024) NOT NULL,
  `publickey` varchar(512) NOT NULL,
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `manage_users`
--

DROP TABLE IF EXISTS `manage_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manage_users` (
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `permissions` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `origin` varchar(50) DEFAULT NULL,
  `inserted_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `text` varchar(255) NOT NULL,
  `viewed` tinyint(1) DEFAULT '0',
  `viewed_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link` varchar(255) DEFAULT NULL,
  `tag` enum('URGENT','IMPORTANT','WARNING','INFO') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `person`
--

DROP TABLE IF EXISTS `person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `person` (
  `email` char(100) NOT NULL,
  `pin` int(4) DEFAULT NULL,
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
  `lang` char(2) NOT NULL DEFAULT 'es' COMMENT 'Language the user prefer to receive the content',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `mail_list` tinyint(1) NOT NULL DEFAULT '1',
  `last_update_date` datetime DEFAULT NULL,
  `updated_by_user` tinyint(1) NOT NULL DEFAULT '0',
  `picture` tinyint(1) NOT NULL DEFAULT '0',
  `cupido` tinyint(1) NOT NULL DEFAULT '1',
  `sexual_orientation` enum('BI','HETERO','HOMO') NOT NULL DEFAULT 'HETERO',
  `religion` enum('ATEISMO','SECULARISMO','AGNOSTICISMO','ISLAM','JUDAISTA','ABAKUA','SANTERO','YORUBA','BUDISMO','CATOLICISMO','OTRA','CRISTIANISMO') DEFAULT NULL,
  `source` enum('api','manual','promoter','internal','abroad','campaign','alone') NOT NULL DEFAULT 'manual',
  `blocked` tinyint(1) NOT NULL DEFAULT '0',
  `notifications` int(11) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `usstate` enum('AL','AK','AS','AZ','AR','CA','CO','CT','DE','DC','FL','GA','GU','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MH','MA','MI','FM','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','MP','OH','OK','OR','PW','PA','PR','RI','SC','SD','TN','TX','UT','VT','VA','VI','WA','WV','WI','WY') NOT NULL,
  PRIMARY KEY (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `username_2` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promoters`
--

DROP TABLE IF EXISTS `promoters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promoters` (
  `email` char(100) NOT NULL,
  `usage` int(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `last_usage` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `raffle`
--

DROP TABLE IF EXISTS `raffle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raffle` (
  `raffle_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_desc` varchar(1000) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `winner_1` varchar(50) NOT NULL,
  `winner_2` varchar(50) NOT NULL,
  `winner_3` varchar(50) NOT NULL,
  PRIMARY KEY (`raffle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relations`
--

DROP TABLE IF EXISTS `relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `user2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'friend',
  `confirmed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user1` (`user1`,`user2`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `remarketing`
--

DROP TABLE IF EXISTS `remarketing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remarketing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` char(100) NOT NULL,
  `type` varchar(10) NOT NULL,
  `sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opened` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11407 DEFAULT CHARSET=latin1 COMMENT='Emails remarketed to attract our users back';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `service`
--

DROP TABLE IF EXISTS `service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service` (
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `usage_text` text NOT NULL,
  `creator_email` char(100) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `category` enum('negocios','ocio','academico','social','comunicaciones','informativo','adulto','otros') NOT NULL,
  `listed` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 if the service will be listed on the list of services',
  `ads` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'service should show ads or not',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `service_alias`
--

DROP TABLE IF EXISTS `service_alias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_alias` (
  `service` varchar(50) NOT NULL,
  `alias` varchar(50) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task_status`
--

DROP TABLE IF EXISTS `task_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_status` (
  `task` varchar(20) NOT NULL,
  `executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delay` int(11) NOT NULL COMMENT 'Time to finish, in seconds',
  `values` text NOT NULL COMMENT 'Extra values returned by the task',
  `frequency` int(11) NOT NULL DEFAULT '1' COMMENT 'Days to run. If passed this number should be a problem',
  PRIMARY KEY (`task`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Report the status of cron tasks running';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raffle_id` int(11) DEFAULT NULL COMMENT 'NULL when the ticket belong to the current Raffle or ID of the Raffle where it was used',
  `email` char(100) NOT NULL,
  `origin` enum('RAFFLE','PURCHASE','PROMOTER','GAME','UNKNOWN','OTHER') NOT NULL DEFAULT 'OTHER',
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transfer`
--

DROP TABLE IF EXISTS `transfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender` char(100) NOT NULL,
  `receiver` char(100) NOT NULL,
  `amount` float NOT NULL,
  `transfer_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmation_hash` varchar(32) NOT NULL,
  `transfered` tinyint(1) NOT NULL DEFAULT '0',
  `inventory_code` varchar(20) DEFAULT NULL COMMENT 'Code from the inventory table, if it was a purchase',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `utilization`
--

DROP TABLE IF EXISTS `utilization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilization` (
  `usage_id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(50) NOT NULL,
  `subservice` varchar(50) DEFAULT NULL,
  `query` varchar(1000) DEFAULT NULL,
  `requestor` char(100) NOT NULL,
  `request_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `response_time` time NOT NULL DEFAULT '00:00:00',
  `domain` varchar(30) NOT NULL,
  `ad_top` int(11) DEFAULT NULL,
  `ad_bottom` int(11) DEFAULT NULL,
  PRIMARY KEY (`usage_id`)
) ENGINE=InnoDB AUTO_INCREMENT=437424 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-20  5:56:49
