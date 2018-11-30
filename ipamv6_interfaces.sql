-- phpMyAdmin SQL Dump
-- version 4.1.14.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 22. Aug 2015 um 12:14
-- Server Version: 5.1.73-1+deb6u1
-- PHP-Version: 5.3.3-7+squeeze23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `usr_web677_1`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ipamv6_interfaces`
--

CREATE TABLE IF NOT EXISTS `ipamv6_interfaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(128) NOT NULL,
  `mask` int(3) NOT NULL,
  `name` varchar(128) NOT NULL,
  `device` int(11) NOT NULL,
  `description` varchar(512) NOT NULL,
  `eui-64` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `device` (`device`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
