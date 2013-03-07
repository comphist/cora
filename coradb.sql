-- MySQL dump 10.13  Distrib 5.5.28, for Linux (x86_64)
--
-- Host: localhost    Database: cora
-- ------------------------------------------------------
-- Server version	5.5.28

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

CREATE DATABASE IF NOT EXISTS `cora`;
USE cora;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `admin` boolean NOT NULL DEFAULT 0,
    `lines_per_page` int(11) NOT NULL DEFAULT 30,
    `lines_context` int(11) NOT NULL DEFAULT 5,
    `columns_order` varchar(255) DEFAULT NULL,
    `columns_hidden` varchar(255) DEFAULT NULL,
    `show_error` boolean NOT NULL DEFAULT 1,
    `lastactive` timestamp,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- insert superuser "system"
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` ( `name`, `password`, `admin` ) VALUES ( "system", "", 1 );
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `locks`;
CREATE TABLE `locks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `text_id` int(11) NOT NULL,
    `lockdate` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `text_id` (`text_id`),
    CONSTRAINT `locks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `locks_ibfk_2` FOREIGN KEY (`text_id`) REFERENCES `text` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `user2project`;
CREATE TABLE `user2project` (
    `user_id` int(11) NOT NULL,
    `project_id` int(11) NOT NULL,
    KEY `user_id` (`user_id`),
    KEY `project_id` (`project_id`),
    CONSTRAINT `user2project_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user2project_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `error_types`;
CREATE TABLE `error_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `mod2error`;
CREATE TABLE `mod2error` (
    `mod_id` bigint(20) NOT NULL,
    `error_id` int(11) NOT NULL,
    UNIQUE KEY mod2error_2 (`mod_id`, `error_id`),
    CONSTRAINT `m2e_ibfk_1` FOREIGN KEY (`mod_id`) REFERENCES `modern` (`id`) ON DELETE CASCADE,
    CONSTRAINT `m2e_ibfk_2` FOREIGN KEY (`error_id`) REFERENCES `error_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `col`
--

DROP TABLE IF EXISTS `col`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `col` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `num` int(11) NOT NULL,
  `name` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `col_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tok_id` bigint(20) NOT NULL,
  `value` varchar(255) NOT NULL,
  `comment_type` char(1) NOT NULL,
  `subtok_id` bigint(20),
  PRIMARY KEY (`id`),
  KEY `tok_id` (`tok_id`),
  CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`tok_id`) REFERENCES `token` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dipl`
--

DROP TABLE IF EXISTS `dipl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dipl` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tok_id` bigint(20) NOT NULL,
  `line_id` int(11) NOT NULL,
  `utf` varchar(255) NOT NULL,
  `trans` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tok_id` (`tok_id`),
  KEY `line_id` (`line_id`),
  CONSTRAINT `dipl_ibfk_1` FOREIGN KEY (`tok_id`) REFERENCES `token` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dipl_ibfk_2` FOREIGN KEY (`line_id`) REFERENCES `line` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line`
--

DROP TABLE IF EXISTS `line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `line` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `col_id` int(11) NOT NULL,
  `num` int(11) NOT NULL,
  `name` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `col_id` (`col_id`),
  CONSTRAINT `line_ibfk_1` FOREIGN KEY (`col_id`) REFERENCES `col` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modern`
--

DROP TABLE IF EXISTS `modern`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modern` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tok_id` bigint(20) NOT NULL,
  `trans` varchar(255) NOT NULL,
  `ascii` varchar(255) NOT NULL,
  `utf` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tok_id` (`tok_id`),
  CONSTRAINT `modern_ibfk_1` FOREIGN KEY (`tok_id`) REFERENCES `token` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num` int(11) NOT NULL,
  `name` varchar(16) NOT NULL,
  `side` char(1) DEFAULT NULL,
  `text_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `text_id` (`text_id`),
  CONSTRAINT `page_ibfk_1` FOREIGN KEY (`text_id`) REFERENCES `text` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shifttags`
--

DROP TABLE IF EXISTS `shifttags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shifttags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tok_from` bigint(20) NOT NULL,
  `tok_to` bigint(20) NOT NULL,
  `tag_type` char(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tok_from` (`tok_from`),
  KEY `tok_to` (`tok_to`),
  CONSTRAINT `shifttags_ibfk_2` FOREIGN KEY (`tok_to`) REFERENCES `token` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifttags_ibfk_1` FOREIGN KEY (`tok_from`) REFERENCES `token` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `needs_revision` boolean NOT NULL DEFAULT 0,
  `tagset_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tagset_id` (`tagset_id`),
  CONSTRAINT `tag_ibfk_1` FOREIGN KEY (`tagset_id`) REFERENCES `tagset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tag_suggestion`
--

DROP TABLE IF EXISTS `tag_suggestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_suggestion` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `score` float DEFAULT NULL,
  `selected` boolean NOT NULL DEFAULT 0,
  `source` enum('user','auto') NOT NULL,
  `tag_id` bigint(20) NOT NULL,
  `mod_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tag_id` (`tag_id`),
  KEY `mod_id` (`mod_id`),
  UNIQUE KEY `tag_id_2` (`tag_id`, `mod_id`),
  CONSTRAINT `tagsuggestion_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tagsuggestion_ibfk_2` FOREIGN KEY (`mod_id`) REFERENCES `modern` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tagset`
--

DROP TABLE IF EXISTS `tagset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tagset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `set_type` enum('open','closed') NOT NULL,
  `class` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `text`
--

DROP TABLE IF EXISTS `text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sigle` varchar(255) DEFAULT NULL,
  `header` text DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `project_id` int(11) NOT NULL,
  `created` timestamp DEFAULT CURRENT_TIMESTAMP,
  `creator_id` int(11) NOT NULL,
  `changed` timestamp,
  `changer_id` int(11) NOT NULL,
  `currentmod_id` bigint(20) DEFAULT NULL,
  `fullfile` text,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `changer_id` (`changer_id`),
  -- KEY `currentmod_id` (`currentmod_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `text_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `text2tagset`
--

DROP TABLE IF EXISTS `text2tagset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `text2tagset` (
  `text_id` int(11) NOT NULL,
  `tagset_id` int(11) NOT NULL,
  `complete` boolean NOT NULL DEFAULT 0,
  KEY `text_id` (`text_id`),
  KEY `tagset_id` (`tagset_id`),
  CONSTRAINT `text2tagset_ibfk_1` FOREIGN KEY (`text_id`) REFERENCES `text` (`id`) ON DELETE CASCADE,
  CONSTRAINT `text2tagset_ibfk_2` FOREIGN KEY (`tagset_id`) REFERENCES `tagset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `token`
--

DROP TABLE IF EXISTS `token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `token` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `text_id` int(11) NOT NULL,
  `trans` varchar(255) NOT NULL,
  `ordnr` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `text_id` (`text_id`),
  CONSTRAINT `token_ibfk_1` FOREIGN KEY (`text_id`) REFERENCES `text` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-01-14 17:08:11

GRANT SELECT,DELETE,UPDATE,INSERT ON cora.* TO 'cora'@'localhost' IDENTIFIED BY 'trustthetext';
-- GRANT SELECT ON cora.* TO 'corauser'@'localhost' IDENTIFIED BY 'mistrusttheuser';
-- GRANT DELETE ON cora.locks TO 'corauser'@'localhost';
-- GRANT INSERT ON cora.tag_suggestions TO 'corauser'@'localhost';
