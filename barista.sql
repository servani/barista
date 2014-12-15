-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 14, 2014 at 10:58 PM
-- Server version: 5.5.40-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `barista`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `image` varchar(200) DEFAULT NULL,
  `description` text,
  `slug` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `cf_type`
--

CREATE TABLE IF NOT EXISTS `cf_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `cf_type`
--

INSERT INTO `cf_type` (`id`, `name`) VALUES
(1, 'Links');

-- --------------------------------------------------------

--
-- Table structure for table `custom_field`
--

CREATE TABLE IF NOT EXISTS `custom_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(200) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `attributes` varchar(100) DEFAULT NULL,
  `post_id` int(11) NOT NULL,
  `cf_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`post_id`),
  KEY `fk_custom_field_post` (`post_id`),
  KEY `fk_custom_field_cf_type` (`cf_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Table structure for table `file`
--

CREATE TABLE IF NOT EXISTS `file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `src` varchar(200) NOT NULL,
  `title` varchar(400) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `post_id` int(11) NOT NULL,
  PRIMARY KEY (`id`,`post_id`),
  KEY `fk_file_post` (`post_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

CREATE TABLE IF NOT EXISTS `image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `src` varchar(200) NOT NULL,
  `title` varchar(400) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `post_id` int(11) NOT NULL,
  PRIMARY KEY (`id`,`post_id`),
  KEY `fk_image_post` (`post_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=103 ;

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE IF NOT EXISTS `post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `subtitle` varchar(200) DEFAULT NULL,
  `summary` text,
  `text` text,
  `creation_date` datetime NOT NULL,
  `public_date` datetime DEFAULT NULL,
  `cover` varchar(200) DEFAULT NULL,
  `video` varchar(400) DEFAULT NULL,
  `map` varchar(400) DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT '1',
  `starred` tinyint(1) DEFAULT '0',
  `category_id` int(11) DEFAULT NULL,
  `bin` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_post_category` (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=27 ;

-- --------------------------------------------------------

--
-- Table structure for table `post_has_tag`
--

CREATE TABLE IF NOT EXISTS `post_has_tag` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `fk_post_has_tag_tag` (`tag_id`),
  KEY `fk_post_has_tag_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `setting`
--

INSERT INTO `setting` (`id`, `name`, `value`) VALUES
(1, 'maintenance', '0');

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `tag_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tag_tag_type` (`tag_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=68 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_type`
--

CREATE TABLE IF NOT EXISTS `tag_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `tag_type`
--

INSERT INTO `tag_type` (`id`, `name`) VALUES
(4, 'Colores');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `username`, `password`, `email`) VALUES
(1, 'Grupo NSNC', 'nsnc', '56033133c25d5177814d93a651f99450', ''),
(20, 'Federico A. Viarnés', 'fede', '7d11810cf99c74a1f3fa22c3879ea39d', 'fede@nsnc.co');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `custom_field`
--
ALTER TABLE `custom_field`
  ADD CONSTRAINT `custom_field_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `custom_field_ibfk_4` FOREIGN KEY (`cf_type_id`) REFERENCES `cf_type` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `file`
--
ALTER TABLE `file`
  ADD CONSTRAINT `file_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `image`
--
ALTER TABLE `image`
  ADD CONSTRAINT `image_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_post_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `post_has_tag`
--
ALTER TABLE `post_has_tag`
  ADD CONSTRAINT `fk_post_has_tag_tag` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_post_has_tag_post` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `tag`
--
ALTER TABLE `tag`
  ADD CONSTRAINT `tag_ibfk_1` FOREIGN KEY (`tag_type_id`) REFERENCES `tag_type` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
