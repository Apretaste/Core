-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 03, 2015 at 07:23 PM
-- Server version: 5.5.44
-- PHP Version: 5.5.28-1+deb.sury.org~precise+1

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
  `ads_id` int(11) NOT NULL AUTO_INCREMENT,
  `time_inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `impresions` int(11) NOT NULL DEFAULT '0',
  `owner` varchar(50) NOT NULL,
  `title` varchar(20) NOT NULL,
  `description` varchar(250) NOT NULL,
  `expiration_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `paid_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ads_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_error`
--

DROP TABLE IF EXISTS `delivery_error`;
CREATE TABLE IF NOT EXISTS `delivery_error` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_email` varchar(50) NOT NULL COMMENT 'The email address of the recipient',
  `response_email` varchar(50) NOT NULL COMMENT 'Email that Apretaste selected to respond',
  `reason` varchar(20) NOT NULL COMMENT 'The reason for the rejection. One of "hard-bounce", "soft-bounce", "spam", "unsub", "custom", "invalid-sender", "invalid", "test-mode-limit", or "rule"',
  `mandrill_id` varchar(20) NOT NULL COMMENT 'Unique ID in Mandrill',
  `error_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `code` varchar(20) NOT NULL,
  `price` float NOT NULL,
  `name` varchar(250) NOT NULL,
  `seller` varchar(50) NOT NULL,
  `insertion_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `service` varchar(50) NOT NULL COMMENT 'Service wich payment function will be executed when the payment is finalized',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code`),
  UNIQUE KEY `code` (`code`),
  KEY `code_2` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`code`, `price`, `name`, `seller`, `insertion_date`, `service`, `active`) VALUES
('10TICKETS', 3.5, 'Diez tickets para la Rifa', 'salvi.pascual@gmail.com', '2015-09-04 19:23:44', 'RIFA', 1),
('1TICKET', 0.5, 'Un ticket para la Rifa', 'salvi.pascual@gmail.com', '2015-09-04 19:23:44', 'RIFA', 1),
('5TICKETS', 2, 'Cinco tickets para la Rifa', 'salvi.pascual@gmail.com', '2015-09-04 19:23:44', 'RIFA', 1);

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `invitations`
--

INSERT INTO `invitations` (`invitation_id`, `invitation_time`, `email_inviter`, `email_invited`, `used`, `used_time`) VALUES
(1, '2015-08-18 02:30:35', 'html@apretaste.com', 'salvi@gmail.com', 0, '0000-00-00 00:00:00'),
(2, '2015-08-18 02:31:42', 'salvi.pascual@gmail.com', 'fdiaz3000@gmail.com', 0, '0000-00-00 00:00:00'),
(3, '2015-08-18 02:31:42', 'salvi.pascual@gmail.com', 'bonilla.daniella@gmail.com', 0, '0000-00-00 00:00:00'),
(5, '2015-08-19 03:25:18', 'salvi.pascual@gmail.com', 'maura.pascual@gmail.com', 0, '0000-00-00 00:00:00'),
(7, '2015-08-23 03:57:23', 'salvi.pascual@gmail.com', 'Ibis@techyibis.com', 0, '0000-00-00 00:00:00'),
(9, '2015-09-06 18:43:35', 'salvi.pascual@gmail.com', 'jadirey@yahoo.es', 0, '0000-00-00 00:00:00'),
(10, '2015-09-06 23:01:14', 'salvi.pascual@gmail.com', 'marta.magaly@gmail.com', 1, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `jumper`
--

DROP TABLE IF EXISTS `jumper`;
CREATE TABLE IF NOT EXISTS `jumper` (
  `email` varchar(50) NOT NULL,
  `sent_count` int(11) NOT NULL DEFAULT '0',
  `blocked_domains` varchar(1000) NOT NULL COMMENT 'Comma separated list of the domains that are blocked for that email',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`),
  UNIQUE KEY `email_2` (`email`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `jumper`
--

INSERT INTO `jumper` (`email`, `sent_count`, `blocked_domains`, `active`, `last_usage`) VALUES
('apretaste@apretaste.biz', 40, '', 1, '2015-10-02 19:46:34'),
('apretaste@apretaste.org', 40, '', 1, '2015-10-02 19:47:00'),
('apretaste@gmail.com', 41, '', 1, '2015-10-02 19:47:42'),
('apretaste@yahoo.com', 40, '', 1, '2015-10-02 19:17:06');

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
  `eyes` enum('NEGRO','CARMELITA','VERDE','AZUL','AVELLANA','OTRO') DEFAULT NULL,
  `skin` enum('NEGRO','BLANCO','MESTIZO','OTRO') DEFAULT NULL,
  `body_type` enum('DELGADO','MEDIO','EXTRA','ATLETICO') DEFAULT NULL,
  `hair` enum('TRIGUENO','CASTANO','RUBIO','NEGRO','ROJO','BLANCO','OTRO') DEFAULT NULL,
  `province` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD') DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `highest_school_level` enum('PRIMARIO','SECUNDARIO','TECNICO','UNIVERSITARIO','POSTGRADUADO','DOCTORADO','OTRO') DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `marital_status` enum('SOLTERO','SALIENDO','COMPROMETIDO','CASADO') DEFAULT NULL,
  `interests` varchar(1000) NOT NULL COMMENT 'Comma separated list of interests',
  `about_me` varchar(1000) DEFAULT NULL,
  `credit` float NOT NULL DEFAULT '0',
  `active` tinyint(1) DEFAULT NULL,
  `last_update_date` datetime DEFAULT NULL,
  `updated_by_user` tinyint(1) DEFAULT '0',
  `picture` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `person`
--

INSERT INTO `person` (`email`, `insertion_date`, `first_name`, `middle_name`, `last_name`, `mother_name`, `date_of_birth`, `gender`, `phone`, `eyes`, `skin`, `body_type`, `hair`, `province`, `city`, `highest_school_level`, `occupation`, `marital_status`, `interests`, `about_me`, `credit`, `active`, `last_update_date`, `updated_by_user`, `picture`) VALUES
('html@apretaste.com', '2015-08-26 02:13:51', 'Test', 'Yo', 'Me', 'Agustin', '1985-11-23', 'M', NULL, 'AZUL', 'BLANCO', 'ATLETICO', 'CASTANO', 'LA_HABANA', 'Miami', 'POSTGRADUADO', 'CTO', 'CASADO', 'Networking,Amistad,Programacion,Apretaste,c++,developing,', '', 8, NULL, '2015-08-25 13:15:44', 1, 0),
('ibis@girldevelopit.com', '2015-10-02 19:17:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, 0, NULL, NULL, 0, NULL),
('jadierreyes@icloud.com', '2015-09-06 18:45:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, 0, NULL, NULL, 0, NULL),
('marta.magaly@gmail.com', '2015-09-06 23:01:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, 0, NULL, NULL, 0, NULL),
('salvi.pascual@gmail.com', '2015-08-18 02:13:51', 'Salvi', '', 'Pascual', '', '1985-11-23', 'M', NULL, 'VERDE', 'BLANCO', 'ATLETICO', 'TRIGUENO', 'LA_HABANA', 'el vedado', 'POSTGRADUADO', 'Profesor y Programador', 'CASADO', 'programacion,ensennar,apretaste,mary,electronic music', '', 2.5, NULL, '2015-08-25 16:36:19', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `raffle`
--

DROP TABLE IF EXISTS `raffle`;
CREATE TABLE IF NOT EXISTS `raffle` (
  `raffle_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_desc` varchar(1000) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `winner_1` varchar(50) NOT NULL,
  `winner_2` varchar(50) NOT NULL,
  `winner_3` varchar(50) NOT NULL,
  PRIMARY KEY (`raffle_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `raffle`
--

INSERT INTO `raffle` (`raffle_id`, `item_desc`, `start_date`, `end_date`, `winner_1`, `winner_2`, `winner_3`) VALUES
(1, 'El BLU Dash es un smartphone Android con una pantalla de 2.8 pulgadas, camara de 2 megapixels, Wi-Fi, Bluetooth GPS, y ranura microSD. El BLU Dash corre Android 2.3 Gingerbread potenciado por un procesador de 650MHz. Pesa 96 gramos y tiene el tamanno perfecto para ser caber en cualquier bolsiso o bolso sin hacerse notar ni molestar.', '2015-09-01 00:00:00', '2015-09-30 00:00:00', '', '', '');

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
  `deploy_key` varchar(32) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
CREATE TABLE IF NOT EXISTS `ticket` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `raffle_id` int(11) DEFAULT NULL COMMENT 'NULL when the ticket belong to the current Raffle or ID of the Raffle where it was used',
  `email` varchar(50) NOT NULL,
  `paid` tinyint(1) NOT NULL,
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `transfer`
--

DROP TABLE IF EXISTS `transfer`;
CREATE TABLE IF NOT EXISTS `transfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender` varchar(50) NOT NULL,
  `receiver` varchar(50) NOT NULL,
  `amount` float NOT NULL,
  `transfer_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `confirmation_hash` varchar(32) NOT NULL,
  `transfered` tinyint(1) NOT NULL DEFAULT '0',
  `inventory_code` varchar(20) DEFAULT NULL COMMENT 'Code from the inventory table, if it was a purchase',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=44 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `_search_ignored_words`
--

DROP TABLE IF EXISTS `_search_ignored_words`;
CREATE TABLE IF NOT EXISTS `_search_ignored_words` (
  `word` varchar(30) NOT NULL,
  PRIMARY KEY (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `_search_ignored_words`
--

INSERT INTO `_search_ignored_words` (`word`) VALUES
('a'),
('al'),
('comprar'),
('compro'),
('con'),
('de'),
('la'),
('las'),
('los'),
('mas'),
('no'),
('pa'),
('para'),
('pero'),
('si'),
('sin'),
('sino'),
('su'),
('vendo'),
('y');

-- --------------------------------------------------------

--
-- Table structure for table `_search_variations`
--

DROP TABLE IF EXISTS `_search_variations`;
CREATE TABLE IF NOT EXISTS `_search_variations` (
  `word` varchar(30) NOT NULL,
  `variation` varchar(30) NOT NULL,
  `variation_type` enum('SYNONYM','TYPO') NOT NULL,
  PRIMARY KEY (`word`,`variation`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `_search_variations`
--

INSERT INTO `_search_variations` (`word`, `variation`, `variation_type`) VALUES
('computadora', 'compu', 'SYNONYM'),
('computadora', 'conputadora', 'TYPO'),
('computadora', 'ordenador', 'SYNONYM'),
('computadora', 'PC', 'SYNONYM'),
('escritorio', 'escritorrio', 'TYPO'),
('laptop', 'portatil', 'SYNONYM');

-- --------------------------------------------------------

--
-- Table structure for table `_search_words`
--

DROP TABLE IF EXISTS `_search_words`;
CREATE TABLE IF NOT EXISTS `_search_words` (
  `word` varchar(30) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of times that word was used, or a typo of that word',
  `last_usage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Useful to remove non-used words automatically',
  PRIMARY KEY (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `_search_words`
--

INSERT INTO `_search_words` (`word`, `count`, `last_usage`) VALUES
('5', 1, '2015-09-27 21:45:57'),
('6', 6, '2015-09-27 21:51:46'),
('blanco', 3, '2015-09-27 21:51:46'),
('computadora', 1384, '2015-09-27 01:40:38'),
('escritorio', 16, '2015-09-27 21:32:29'),
('iphone', 7, '2015-09-27 21:51:46'),
('kinetic', 38, '2015-09-26 22:02:26'),
('kit', 1, '2015-09-26 22:34:32'),
('laptop', 27, '2015-09-27 21:02:23'),
('lcd', 15, '2015-09-27 21:44:21'),
('mesa', 61, '2015-09-27 21:32:29'),
('motherboard', 1, '2015-09-26 22:34:32'),
('mouse', 0, '2015-09-26 21:03:47'),
('portatil', 32, '2015-09-26 23:03:19'),
('satelite', 27, '2015-09-27 21:02:23'),
('teclado', 2, '2015-09-26 22:33:55'),
('telefono', 0, '2015-09-26 21:03:47'),
('televisor', 23, '2015-09-27 21:44:21'),
('toshiba', 27, '2015-09-27 21:02:23');

-- --------------------------------------------------------

--
-- Table structure for table `_tienda_categories`
--

DROP TABLE IF EXISTS `_tienda_categories`;
CREATE TABLE IF NOT EXISTS `_tienda_categories` (
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `_tienda_categories`
--

INSERT INTO `_tienda_categories` (`code`, `name`, `description`) VALUES
('antiques', 'Coleccionables', 'Articulos que forman parte de una coleccion o se pueden coleccionar'),
('books', 'Libros', 'Personas que venden, renten o compren libros'),
('cars', 'Carros y partes', 'Carros, motos y piezas'),
('computers', 'Computadoras y piezas', 'PC, laptops, mouse, teclados'),
('electronics', 'Electrodomesticos', 'Electronicos y utiles del hogar electricos'),
('events', 'Eventos', 'Fiestas y eventos sociales'),
('for_sale', 'A la venta', 'Cualquier articulo que no encaje en alguna categoria de venta especifica'),
('home', 'Utiles del hogar', 'Utiles del hogar (no electronicos)'),
('jobs', 'Trabajos', 'Personas que buscan o brindan trabajo'),
('music_instruments', 'Instrumentos musicales', 'Instrumentos musicales, accesorios y mas'),
('phones', 'Telefonos, Tablets y accesorios', 'Telefonos, tablets, cargadores, covers y mas'),
('places', 'Lugares', 'Restaurantes, bares, nighclubs, parques y mas'),
('real_state', 'Bienes Raices', 'Ventas, permutas, rentas y alquier de oficinas y espacios'),
('relationship', 'Interpersonal', 'Personas que buscan pareja o amigos'),
('services', 'Servicios', 'Barberos a domicilio, profesores particulares, plomeros, etc.'),
('software', 'Software', 'Personas que venden o compran software'),
('videogames', 'Juegos de video', 'Ventas de juegos, consolas y accesorios');

-- --------------------------------------------------------

--
-- Table structure for table `_tienda_post`
--

DROP TABLE IF EXISTS `_tienda_post`;
CREATE TABLE IF NOT EXISTS `_tienda_post` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=494287 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
