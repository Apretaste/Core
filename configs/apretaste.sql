-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 04, 2015 at 04:26 PM
-- Server version: 5.5.40
-- PHP Version: 5.4.36-1+deb.sury.org~precise+2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `apretaste`
--

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

DROP TABLE IF EXISTS `ads`;
CREATE TABLE IF NOT EXISTS `ads` (
  `ads_id` int(11) NOT NULL,
  `time_inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL,
  `impresions` int(11) NOT NULL,
  `owner` varchar(50) NOT NULL,
  `title` varchar(20) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  `expiration_date` datetime NOT NULL,
  `paid_date` datetime NOT NULL,
  PRIMARY KEY (`ads_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `invitations`
--

DROP TABLE IF EXISTS `invitations`;
CREATE TABLE IF NOT EXISTS `invitations` (
  `invitation_id` int(11) NOT NULL AUTO_INCREMENT,
  `invitation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `email_inviter` varchar(50) NOT NULL,
  `email_invited` varchar(50) NOT NULL,
  `used` tinyint(1) NOT NULL,
  `used_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`invitation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `jumper`
--

DROP TABLE IF EXISTS `jumper`;
CREATE TABLE IF NOT EXISTS `jumper` (
  `email` varchar(50) NOT NULL,
  `sent_count` int(11) NOT NULL,
  `blocked_domains` varchar(1000) NOT NULL,
  `error` tinyint(1) NOT NULL,
  `error_count` int(11) NOT NULL,
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `provider` varchar(20) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

DROP TABLE IF EXISTS `person`;
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
  `eyes` enum('BLACK','BROWN','GREEN','BLUE','HAZEL','OTHER') DEFAULT NULL,
  `skin` enum('BLACK','WHITE','MIX','OTHER') DEFAULT NULL,
  `body_type` enum('OTHER','THIN','OVERWEIGHT','AVERAGE','FIT','JACKED','LITTLE_EXTRA','CURVY','FULL_FIGURE','USED_UP') DEFAULT NULL,
  `hair` enum('BRUNETTE','BLOND','BLACK','RED','OTHER') DEFAULT NULL,
  `province` enum('PINAR_DEL_RIO','HAVANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DA_LA_JUVENTUD') DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `about_me` varchar(1000) DEFAULT NULL,
  `credit` float DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `last_update_date` datetime DEFAULT NULL,
  `updated_by_user` tinyint(1) DEFAULT '0',
  `picture` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `raffle`
--

DROP TABLE IF EXISTS `raffle`;
CREATE TABLE IF NOT EXISTS `raffle` (
  `raffle_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_desc` varchar(50) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `winner_1` varchar(50) NOT NULL,
  `winner_2` varchar(50) NOT NULL,
  `winner_3` varchar(50) NOT NULL,
  PRIMARY KEY (`raffle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

DROP TABLE IF EXISTS `service`;
CREATE TABLE IF NOT EXISTS `service` (
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `usage_text` text NOT NULL,
  `creator_email` varchar(50) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `category` enum('negocios','ocio','academico','social','comunicaciones','informativo','adulto','otros') NOT NULL,
  `subservices` varchar(250) NOT NULL,
  `deploy_key` varchar(32) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`name`, `description`, `usage_text`, `creator_email`, `insertion_date`, `category`, `subservices`, `deploy_key`) VALUES
('ayuda', 'Muestra la ayuda de Apretaste', 'Escriba un correo a apretaste@gmail.com y en el asunto ponga la palabra AYUDA\n			Por ejemplo:\n\n			Para: apretaste@gmail.com\n			Asunto: AYUDA\n\n			Debera recibir un email explicando como usar Apretaste y una lista de los servicios mas usados.', 'salvi.pascual@gmail.com', '2015-08-02 22:14:41', 'academico', '', '88388a73e7959ba3ac4862f4309dded4'),
('letra', 'Este servicio devuelve letras de canciones basado en su titulo', 'Escriba un correo a apretaste@mail.com y en el asunto ponga la palabra LETRA seguida del titulo de una cancion.\n			Por ejemplo:\n\n			Para: apretaste@mail.com\n			Asunto: LETRA before I forgot\n\n			Espere tres minutos y debera recibir un correo de respeuesta con su cancion.', 'salvi.pascual@pragres.com', '2015-08-02 16:47:31', '', '', '2344a5ba680d3452dd29ab48a6090286');

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
CREATE TABLE IF NOT EXISTS `ticket` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `raffle_id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `paid` tinyint(1) NOT NULL,
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `utilization`
--

DROP TABLE IF EXISTS `utilization`;
CREATE TABLE IF NOT EXISTS `utilization` (
  `usage_id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(50) NOT NULL,
  `subservice` varchar(50) DEFAULT NULL,
  `query` varchar(100) DEFAULT NULL,
  `requestor` varchar(50) NOT NULL,
  `request_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `response_time` time NOT NULL DEFAULT '00:00:00',
  `domain` varchar(30) NOT NULL,
  `ad_top` int(11) DEFAULT NULL,
  `ad_botton` int(11) DEFAULT NULL,
  PRIMARY KEY (`usage_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `utilization`
--

INSERT INTO `utilization` (`usage_id`, `service`, `subservice`, `query`, `requestor`, `request_time`, `response_time`, `domain`, `ad_top`, `ad_botton`) VALUES
(1, 'letra', '', 'before I forgot', 'salvi.pascual@zgmail.com', '2015-08-02 16:47:55', '00:00:00', 'zgmail.com', 0, 0),
(2, 'ayuda', '', 'is an example webhook message', 'example.sender@mandrillapp.com', '2015-08-02 22:18:33', '00:00:00', 'mandrillapp.com', 0, 0),
(3, 'letra', '', 'before I forgot', 'salvi.pascual@gmail.com', '2015-08-02 22:19:30', '00:00:00', 'gmail.com', 0, 0),
(4, 'letra', '', 'before I forgot', 'salvi.pascual@gmail.com', '2015-08-02 22:22:12', '00:00:01', 'gmail.com', 0, 0),
(5, 'letra', '', 'before o forgot', 'salvi.pascual@gmail.com', '2015-08-02 22:36:10', '00:00:01', 'gmail.com', 0, 0),
(6, 'letra', '', 'como los peces', 'salvi.pascual@gmail.com', '2015-08-02 22:39:15', '00:00:00', 'gmail.com', 0, 0),
(7, 'ayuda', '', '', 'salvi.pascual@gmail.com', '2015-08-02 22:43:14', '00:00:01', 'gmail.com', 0, 0),
(8, 'letra', '', 'before I forgot', 'Ibis@techyibis.com', '2015-08-02 23:39:24', '00:00:01', 'techyibis.com', 0, 0),
(9, 'letra', '', 'no one', 'salvi.pascual@gmail.com', '2015-08-03 04:36:48', '00:00:00', 'gmail.com', 0, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
