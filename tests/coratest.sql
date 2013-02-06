-- MySQL dump 10.13  Distrib 5.5.29, for Linux (x86_64)
--
-- Host: localhost    Database: cora
-- ------------------------------------------------------
-- Server version	5.5.29

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `col`
--

LOCK TABLES `col` WRITE;
/*!40000 ALTER TABLE `col` DISABLE KEYS */;
INSERT INTO `col` VALUES (1,1,0,NULL),(2,2,0,NULL),(3,2,0,NULL);
/*!40000 ALTER TABLE `col` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`id`),
  KEY `tok_id` (`tok_id`),
  CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`tok_id`) REFERENCES `dipl` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment`
--

LOCK TABLES `comment` WRITE;
/*!40000 ALTER TABLE `comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `comment` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dipl`
--

LOCK TABLES `dipl` WRITE;
/*!40000 ALTER TABLE `dipl` DISABLE KEYS */;
INSERT INTO `dipl` VALUES (1,1,1,'Anshelm\'','*{A*4}n$helm%9'),(2,2,2,'pistus','pi$t||u||s'),(3,3,3,'aller','aller#'),(4,3,3,'liebstev','lieb$tev'),(5,4,3,'vunf=','vunf='),(6,4,4,'tusent','tusent#'),(7,4,4,'vnd','vnd#'),(8,4,4,'vierhundert','vierhundert#'),(9,4,4,'vn','vn-(=)'),(10,4,5,'sechzig','sechzig'),(11,5,5,'kunnen.','kunnen.(.)');
/*!40000 ALTER TABLE `dipl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_types`
--

DROP TABLE IF EXISTS `error_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_types`
--

LOCK TABLES `error_types` WRITE;
/*!40000 ALTER TABLE `error_types` DISABLE KEYS */;
INSERT INTO `error_types` VALUES (1,'general error'),(2,'inflection');
/*!40000 ALTER TABLE `error_types` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `line`
--

LOCK TABLES `line` WRITE;
/*!40000 ALTER TABLE `line` DISABLE KEYS */;
INSERT INTO `line` VALUES (1,1,1,NULL),(2,1,2,NULL),(3,1,3,NULL),(4,1,4,NULL),(5,1,5,NULL),(6,2,1,NULL),(7,2,2,NULL),(8,3,1,NULL),(9,3,2,NULL);
/*!40000 ALTER TABLE `line` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locks`
--

DROP TABLE IF EXISTS `locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `text_id` int(11) NOT NULL,
  `lockdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `text_id` (`text_id`),
  CONSTRAINT `locks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `locks_ibfk_2` FOREIGN KEY (`text_id`) REFERENCES `text` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locks`
--

LOCK TABLES `locks` WRITE;
/*!40000 ALTER TABLE `locks` DISABLE KEYS */;
INSERT INTO `locks` VALUES (183,3,3,'2013-02-05 13:00:40');
/*!40000 ALTER TABLE `locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mod2error`
--

DROP TABLE IF EXISTS `mod2error`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mod2error` (
  `mod_id` bigint(20) NOT NULL,
  `error_id` int(11) NOT NULL,
  KEY `m2e_ibfk_1` (`mod_id`),
  KEY `m2e_ibfk_2` (`error_id`),
  CONSTRAINT `m2e_ibfk_1` FOREIGN KEY (`mod_id`) REFERENCES `modern` (`id`) ON DELETE CASCADE,
  CONSTRAINT `m2e_ibfk_2` FOREIGN KEY (`error_id`) REFERENCES `error_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mod2error`
--

LOCK TABLES `mod2error` WRITE;
/*!40000 ALTER TABLE `mod2error` DISABLE KEYS */;
INSERT INTO `mod2error` VALUES (7,1),(3,1),(3,2);
/*!40000 ALTER TABLE `mod2error` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modern`
--

LOCK TABLES `modern` WRITE;
/*!40000 ALTER TABLE `modern` DISABLE KEYS */;
INSERT INTO `modern` VALUES (1,1,'*{A*4}n$helm%9','Anshelmus','Anshelm\''),(2,2,'pi$t||','pist','pist'),(3,2,'u||','u','u'),(4,2,'s','s','s'),(5,3,'aller#lieb$tev','allerliebstev','allerliebstev'),(6,4,'vunf=tusent#vnd#vierhundert#vn-(=)sechzig','vunftusentvndvierhundertvndsechzig','vunftusentvndvierhundertvnsechzig'),(7,5,'kunnen','kunnen','kunnen'),(8,5,'.','.','.'),(9,5,'(.)','.','.'),(13,6,'test','test','test'),(14,6,'foo','foo','foo');
/*!40000 ALTER TABLE `modern` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL,
  `side` char(1) DEFAULT NULL,
  `text_id` int(11) NOT NULL,
  `num` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `text_id` (`text_id`),
  CONSTRAINT `page_ibfk_1` FOREIGN KEY (`text_id`) REFERENCES `text` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page`
--

LOCK TABLES `page` WRITE;
/*!40000 ALTER TABLE `page` DISABLE KEYS */;
INSERT INTO `page` VALUES (1,'1','r',3,0),(2,'1','v',3,0);
/*!40000 ALTER TABLE `page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
INSERT INTO `project` VALUES (1,'Default-Gruppe');
/*!40000 ALTER TABLE `project` ENABLE KEYS */;
UNLOCK TABLES;

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
  CONSTRAINT `shifttags_ibfk_1` FOREIGN KEY (`tok_from`) REFERENCES `token` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifttags_ibfk_2` FOREIGN KEY (`tok_to`) REFERENCES `token` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifttags`
--

LOCK TABLES `shifttags` WRITE;
/*!40000 ALTER TABLE `shifttags` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifttags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `needs_revision` tinyint(1) NOT NULL DEFAULT '0',
  `tagset_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tagset_id` (`tagset_id`),
  CONSTRAINT `tag_ibfk_1` FOREIGN KEY (`tagset_id`) REFERENCES `tagset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=515 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag`
--

LOCK TABLES `tag` WRITE;
/*!40000 ALTER TABLE `tag` DISABLE KEYS */;
INSERT INTO `tag` VALUES (1,'$,',0,1),(2,'$.',0,1),(3,'$(',0,1),(4,'ADJA.*.*.*.*',0,1),(5,'ADJA.*.*.Akk.Pl',0,1),(6,'ADJA.Comp.*.Akk.Pl',0,1),(7,'ADJA.Comp.*.Dat.Pl',0,1),(8,'ADJA.Comp.Fem.Akk.Sg',0,1),(9,'ADJA.Comp.Fem.Dat.Sg',0,1),(10,'ADJA.Comp.Fem.Gen.Sg',0,1),(11,'ADJA.Comp.Fem.Nom.Sg',0,1),(12,'ADJA.Comp.*.Gen.Pl',0,1),(13,'ADJA.Comp.Masc.Akk.Sg',0,1),(14,'ADJA.Comp.Masc.Dat.Sg',0,1),(15,'ADJA.Comp.Masc.Gen.Sg',0,1),(16,'ADJA.Comp.Masc.Nom.Sg',0,1),(17,'ADJA.Comp.Neut.Akk.Sg',0,1),(18,'ADJA.Comp.Neut.Dat.Sg',0,1),(19,'ADJA.Comp.Neut.Gen.Sg',0,1),(20,'ADJA.Comp.Neut.Nom.Sg',0,1),(21,'ADJA.Comp.*.Nom.Pl',0,1),(22,'ADJA.*.*.Dat.Pl',0,1),(23,'ADJA.*.Fem.Akk.Sg',0,1),(24,'ADJA.*.Fem.Dat.Sg',0,1),(25,'ADJA.*.Fem.Gen.Sg',0,1),(26,'ADJA.*.Fem.Nom.Sg',0,1),(27,'ADJA.*.*.Gen.Pl',0,1),(28,'ADJA.*.Masc.Akk.Sg',0,1),(29,'ADJA.*.Masc.Dat.Sg',0,1),(30,'ADJA.*.Masc.Gen.Sg',0,1),(31,'ADJA.*.Masc.Nom.Sg',0,1),(32,'ADJA.*.Neut.Akk.Sg',0,1),(33,'ADJA.*.Neut.Dat.Sg',0,1),(34,'ADJA.*.Neut.Gen.Sg',0,1),(35,'ADJA.*.Neut.Nom.Sg',0,1),(36,'ADJA.*.*.Nom.Pl',0,1),(37,'ADJA.Pos.*.Akk.Pl',0,1),(38,'ADJA.Pos.*.Dat.Pl',0,1),(39,'ADJA.Pos.Fem.Akk.Sg',0,1),(40,'ADJA.Pos.Fem.Dat.Sg',0,1),(41,'ADJA.Pos.Fem.Gen.Sg',0,1),(42,'ADJA.Pos.Fem.Nom.Sg',0,1),(43,'ADJA.Pos.*.Gen.Pl',0,1),(44,'ADJA.Pos.Masc.Akk.Sg',0,1),(45,'ADJA.Pos.Masc.Dat.Sg',0,1),(46,'ADJA.Pos.Masc.Gen.Sg',0,1),(47,'ADJA.Pos.Masc.Nom.Sg',0,1),(48,'ADJA.Pos.Neut.Akk.Sg',0,1),(49,'ADJA.Pos.Neut.Dat.Sg',0,1),(50,'ADJA.Pos.Neut.Gen.Sg',0,1),(51,'ADJA.Pos.Neut.Nom.Sg',0,1),(52,'ADJA.Pos.*.Nom.Pl',0,1),(53,'ADJA.Sup.*.Akk.Pl',0,1),(54,'ADJA.Sup.*.Dat.Pl',0,1),(55,'ADJA.Sup.Fem.Akk.Sg',0,1),(56,'ADJA.Sup.Fem.Dat.Sg',0,1),(57,'ADJA.Sup.Fem.Gen.Sg',0,1),(58,'ADJA.Sup.Fem.Nom.Sg',0,1),(59,'ADJA.Sup.*.Gen.Pl',0,1),(60,'ADJA.Sup.Masc.Akk.Sg',0,1),(61,'ADJA.Sup.Masc.Dat.Sg',0,1),(62,'ADJA.Sup.Masc.Gen.Sg',0,1),(63,'ADJA.Sup.Masc.Nom.Sg',0,1),(64,'ADJA.Sup.Neut.Akk.Sg',0,1),(65,'ADJA.Sup.Neut.Dat.Sg',0,1),(66,'ADJA.Sup.Neut.Gen.Sg',0,1),(67,'ADJA.Sup.Neut.Nom.Sg',0,1),(68,'ADJA.Sup.*.Nom.Pl',0,1),(69,'ADJD.*',0,1),(70,'ADJD.Comp',0,1),(71,'ADJD.Pos',0,1),(72,'ADJD.Sup',0,1),(73,'ADV',0,1),(74,'APPO.Akk',0,1),(75,'APPO.Dat',0,1),(76,'APPO.Gen',0,1),(77,'APPO.Nom',0,1),(78,'APPR._',0,1),(79,'APPR.Akk',0,1),(80,'APPRART.Fem.Akk',0,1),(81,'APPRART.Fem.Dat',0,1),(82,'APPRART.Fem.Gen',0,1),(83,'APPRART.Fem.Nom',0,1),(84,'APPRART.Masc.Akk',0,1),(85,'APPRART.Masc.Dat',0,1),(86,'APPRART.Masc.Gen',0,1),(87,'APPRART.Masc.Nom',0,1),(88,'APPRART.Neut.Akk',0,1),(89,'APPRART.Neut.Dat',0,1),(90,'APPRART.Neut.Gen',0,1),(91,'APPRART.Neut.Nom',0,1),(92,'APPR.Dat',0,1),(93,'APPR.Gen',0,1),(94,'APPR.Nom',0,1),(95,'APZR',0,1),(96,'ART.Def.*.Akk.Pl',0,1),(97,'ART.Def.*.Dat.Pl',0,1),(98,'ART.Def.Fem.Akk.Sg',0,1),(99,'ART.Def.Fem.Dat.Sg',0,1),(100,'ART.Def.Fem.Gen.Sg',0,1),(101,'ART.Def.Fem.Nom.Sg',0,1),(102,'ART.Def.*.Gen.Pl',0,1),(103,'ART.Def.Masc.Akk.Sg',0,1),(104,'ART.Def.Masc.Dat.Sg',0,1),(105,'ART.Def.Masc.Gen.Sg',0,1),(106,'ART.Def.Masc.Nom.Sg',0,1),(107,'ART.Def.Neut.Akk.Sg',0,1),(108,'ART.Def.Neut.Dat.Sg',0,1),(109,'ART.Def.Neut.Gen.Sg',0,1),(110,'ART.Def.Neut.Nom.Sg',0,1),(111,'ART.Def.*.Nom.Pl',0,1),(112,'ART.Indef.*.Akk.Pl',0,1),(113,'ART.Indef.*.Dat.Pl',0,1),(114,'ART.Indef.Fem.Akk.Sg',0,1),(115,'ART.Indef.Fem.Dat.Sg',0,1),(116,'ART.Indef.Fem.Gen.Sg',0,1),(117,'ART.Indef.Fem.Nom.Sg',0,1),(118,'ART.Indef.*.Gen.Pl',0,1),(119,'ART.Indef.Masc.Akk.Sg',0,1),(120,'ART.Indef.Masc.Dat.Sg',0,1),(121,'ART.Indef.Masc.Gen.Sg',0,1),(122,'ART.Indef.Masc.Nom.Sg',0,1),(123,'ART.Indef.Neut.Akk.Sg',0,1),(124,'ART.Indef.Neut.Dat.Sg',0,1),(125,'ART.Indef.Neut.Gen.Sg',0,1),(126,'ART.Indef.Neut.Nom.Sg',0,1),(127,'ART.Indef.*.Nom.Pl',0,1),(128,'CARD',0,1),(129,'FM',0,1),(130,'ITJ',0,1),(131,'KOKOM',0,1),(132,'KON',0,1),(133,'KOUI',0,1),(134,'KOUS',0,1),(135,'NE._._._',0,1),(136,'NE.*.Akk.Pl',0,1),(137,'NE.*.Akk.Sg',0,1),(138,'NE.*.Dat.Pl',0,1),(139,'NE.*.Dat.Sg',0,1),(140,'NE.Fem.Akk.Pl',0,1),(141,'NE.Fem.Akk.Sg',0,1),(142,'NE.Fem.Dat.Pl',0,1),(143,'NE.Fem.Dat.Sg',0,1),(144,'NE.Fem.Gen.Pl',0,1),(145,'NE.Fem.Gen.Sg',0,1),(146,'NE.Fem.Nom.Pl',0,1),(147,'NE.Fem.Nom.Sg',0,1),(148,'NE.*.Gen.Pl',0,1),(149,'NE.*.Gen.Sg',0,1),(150,'NE.Masc.Akk.Pl',0,1),(151,'NE.Masc.Akk.Sg',0,1),(152,'NE.Masc.Dat.Pl',0,1),(153,'NE.Masc.Dat.Sg',0,1),(154,'NE.Masc.Gen.Pl',0,1),(155,'NE.Masc.Gen.Sg',0,1),(156,'NE.Masc.Nom.Pl',0,1),(157,'NE.Masc.Nom.Sg',0,1),(158,'NE.Neut.Akk.Pl',0,1),(159,'NE.Neut.Akk.Sg',0,1),(160,'NE.Neut.Dat.Pl',0,1),(161,'NE.Neut.Dat.Sg',0,1),(162,'NE.Neut.Gen.Pl',0,1),(163,'NE.Neut.Gen.Sg',0,1),(164,'NE.Neut.Nom.Pl',0,1),(165,'NE.Neut.Nom.Sg',0,1),(166,'NE.*.Nom.Pl',0,1),(167,'NE.*.Nom.Sg',0,1),(168,'NN.*.Akk.Pl',0,1),(169,'NN.*.Dat.Pl',0,1),(170,'NN.Fem.Akk.Pl',0,1),(171,'NN.Fem.Akk.Sg',0,1),(172,'NN.Fem.Dat.Pl',0,1),(173,'NN.Fem.Dat.Sg',0,1),(174,'NN.Fem.Gen.Pl',0,1),(175,'NN.Fem.Gen.Sg',0,1),(176,'NN.Fem.Nom.Pl',0,1),(177,'NN.Fem.Nom.Sg',0,1),(178,'NN.*.Gen.Pl',0,1),(179,'NN.Masc.Akk.Pl',0,1),(180,'NN.Masc.Akk.Sg',0,1),(181,'NN.Masc.Dat.Pl',0,1),(182,'NN.Masc.Dat.Sg',0,1),(183,'NN.Masc.Gen.Pl',0,1),(184,'NN.Masc.Gen.Sg',0,1),(185,'NN.Masc.Nom.Pl',0,1),(186,'NN.Masc.Nom.Sg',0,1),(187,'NN.Neut.Akk.Pl',0,1),(188,'NN.Neut.Akk.Sg',0,1),(189,'NN.Neut.Dat.Pl',0,1),(190,'NN.Neut.Dat.Sg',0,1),(191,'NN.Neut.Gen.Pl',0,1),(192,'NN.Neut.Gen.Sg',0,1),(193,'NN.Neut.Nom.Pl',0,1),(194,'NN.Neut.Nom.Sg',0,1),(195,'NN.*.Nom.Pl',0,1),(196,'PAV',0,1),(197,'PDAT.*.Akk.Pl',0,1),(198,'PDAT.*.Dat.Pl',0,1),(199,'PDAT.Fem.Akk.Sg',0,1),(200,'PDAT.Fem.Dat.Sg',0,1),(201,'PDAT.Fem.Gen.Sg',0,1),(202,'PDAT.Fem.Nom.Sg',0,1),(203,'PDAT.*.Gen.Pl',0,1),(204,'PDAT.Masc.Akk.Sg',0,1),(205,'PDAT.Masc.Dat.Sg',0,1),(206,'PDAT.Masc.Gen.Sg',0,1),(207,'PDAT.Masc.Nom.Sg',0,1),(208,'PDAT.Neut.Akk.Sg',0,1),(209,'PDAT.Neut.Dat.Sg',0,1),(210,'PDAT.Neut.Gen.Sg',0,1),(211,'PDAT.Neut.Nom.Sg',0,1),(212,'PDAT.*.Nom.Pl',0,1),(213,'PDS.*.Akk.Pl',0,1),(214,'PDS.*.Dat.Pl',0,1),(215,'PDS.Fem.Akk.Sg',0,1),(216,'PDS.Fem.Dat.Sg',0,1),(217,'PDS.Fem.Gen.Sg',0,1),(218,'PDS.Fem.Nom.Sg',0,1),(219,'PDS.*.Gen.Pl',0,1),(220,'PDS.Masc.Akk.Sg',0,1),(221,'PDS.Masc.Dat.Sg',0,1),(222,'PDS.Masc.Gen.Sg',0,1),(223,'PDS.Masc.Nom.Sg',0,1),(224,'PDS.Neut.Akk.Sg',0,1),(225,'PDS.Neut.Dat.Sg',0,1),(226,'PDS.Neut.Gen.Sg',0,1),(227,'PDS.Neut.Nom.Sg',0,1),(228,'PDS.*.Nom.Pl',0,1),(229,'PIAT.*.*.*',0,1),(230,'PIAT.*.Akk.Pl',0,1),(231,'PIAT.*.Dat.Pl',0,1),(232,'PIAT.Fem.Akk.Sg',0,1),(233,'PIAT.Fem.Dat.Sg',0,1),(234,'PIAT.Fem.Gen.Sg',0,1),(235,'PIAT.Fem.Nom.Sg',0,1),(236,'PIAT.*.Gen.Pl',0,1),(237,'PIAT.Masc.Akk.Sg',0,1),(238,'PIAT.Masc.Dat.Sg',0,1),(239,'PIAT.Masc.Gen.Sg',0,1),(240,'PIAT.Masc.Nom.Sg',0,1),(241,'PIAT.Neut.Akk.Sg',0,1),(242,'PIAT.Neut.Dat.Sg',0,1),(243,'PIAT.Neut.Gen.Sg',0,1),(244,'PIAT.Neut.Nom.Sg',0,1),(245,'PIAT.*.Nom.Pl',0,1),(246,'PIS.*.*.*',0,1),(247,'PIS.*.Akk.Pl',0,1),(248,'PIS.*.Dat.Pl',0,1),(249,'PIS.Fem.Akk.Sg',0,1),(250,'PIS.Fem.Dat.Sg',0,1),(251,'PIS.Fem.Gen.Sg',0,1),(252,'PIS.Fem.Nom.Sg',0,1),(253,'PIS.*.Gen.Pl',0,1),(254,'PIS.Masc.Akk.Sg',0,1),(255,'PIS.Masc.Dat.Sg',0,1),(256,'PIS.Masc.Gen.Sg',0,1),(257,'PIS.Masc.Nom.Sg',0,1),(258,'PIS.Neut.Akk.Sg',0,1),(259,'PIS.Neut.Dat.Sg',0,1),(260,'PIS.Neut.Gen.Sg',0,1),(261,'PIS.Neut.Nom.Sg',0,1),(262,'PIS.*.Nom.Pl',0,1),(263,'PPER.*.*.*.*',0,1),(264,'PPER.1.Pl.*.Akk',0,1),(265,'PPER.1.Pl.*.Dat',0,1),(266,'PPER.1.Pl.*.Gen',0,1),(267,'PPER.1.Pl.*.Nom',0,1),(268,'PPER.1.Sg.*.Akk',0,1),(269,'PPER.1.Sg.*.Dat',0,1),(270,'PPER.1.Sg.*.Gen',0,1),(271,'PPER.1.Sg.*.Nom',0,1),(272,'PPER.2.Pl.*.Akk',0,1),(273,'PPER.2.Pl.*.Dat',0,1),(274,'PPER.2.Pl.*.Gen',0,1),(275,'PPER.2.Pl.*.Nom',0,1),(276,'PPER.2.Sg.*.Akk',0,1),(277,'PPER.2.Sg.*.Dat',0,1),(278,'PPER.2.Sg.*.Gen',0,1),(279,'PPER.2.Sg.*.Nom',0,1),(280,'PPER.3.Pl.*.Akk',0,1),(281,'PPER.3.Pl.*.Dat',0,1),(282,'PPER.3.Pl.*.Gen',0,1),(283,'PPER.3.Pl.*.Nom',0,1),(284,'PPER.3.Sg.Fem.Akk',0,1),(285,'PPER.3.Sg.Fem.Dat',0,1),(286,'PPER.3.Sg.Fem.Gen',0,1),(287,'PPER.3.Sg.Fem.Nom',0,1),(288,'PPER.3.Sg.Masc.Akk',0,1),(289,'PPER.3.Sg.Masc.Dat',0,1),(290,'PPER.3.Sg.Masc.Gen',0,1),(291,'PPER.3.Sg.Masc.Nom',0,1),(292,'PPER.3.Sg.Neut.Akk',0,1),(293,'PPER.3.Sg.Neut.Dat',0,1),(294,'PPER.3.Sg.Neut.Gen',0,1),(295,'PPER.3.Sg.Neut.Nom',0,1),(296,'PPOSAT.*.Akk.Pl',0,1),(297,'PPOSAT.*.Dat.Pl',0,1),(298,'PPOSAT.Fem.Akk.Sg',0,1),(299,'PPOSAT.Fem.Dat.Sg',0,1),(300,'PPOSAT.Fem.Gen.Sg',0,1),(301,'PPOSAT.Fem.Nom.Sg',0,1),(302,'PPOSAT.*.Gen.Pl',0,1),(303,'PPOSAT.Masc.Akk.Sg',0,1),(304,'PPOSAT.Masc.Dat.Sg',0,1),(305,'PPOSAT.Masc.Gen.Sg',0,1),(306,'PPOSAT.Masc.Nom.Sg',0,1),(307,'PPOSAT.Neut.Akk.Sg',0,1),(308,'PPOSAT.Neut.Dat.Sg',0,1),(309,'PPOSAT.Neut.Gen.Sg',0,1),(310,'PPOSAT.Neut.Nom.Sg',0,1),(311,'PPOSAT.*.Nom.Pl',0,1),(312,'PPOSS.*.Akk.Pl',0,1),(313,'PPOSS.*.Dat.Pl',0,1),(314,'PPOSS.Fem.Akk.Sg',0,1),(315,'PPOSS.Fem.Dat.Sg',0,1),(316,'PPOSS.Fem.Gen.Sg',0,1),(317,'PPOSS.Fem.Nom.Sg',0,1),(318,'PPOSS.*.Gen.Pl',0,1),(319,'PPOSS.Masc.Akk.Sg',0,1),(320,'PPOSS.Masc.Dat.Sg',0,1),(321,'PPOSS.Masc.Gen.Sg',0,1),(322,'PPOSS.Masc.Nom.Sg',0,1),(323,'PPOSS.Neut.Akk.Sg',0,1),(324,'PPOSS.Neut.Dat.Sg',0,1),(325,'PPOSS.Neut.Gen.Sg',0,1),(326,'PPOSS.Neut.Nom.Sg',0,1),(327,'PPOSS.*.Nom.Pl',0,1),(328,'PRELAT',0,1),(329,'PRELS.*.Akk.Pl',0,1),(330,'PRELS.*.Dat.Pl',0,1),(331,'PRELS.Fem.Akk.Sg',0,1),(332,'PRELS.Fem.Dat.Sg',0,1),(333,'PRELS.Fem.Gen.Sg',0,1),(334,'PRELS.Fem.Nom.Sg',0,1),(335,'PRELS.*.Gen.Pl',0,1),(336,'PRELS.Masc.Akk.Sg',0,1),(337,'PRELS.Masc.Dat.Sg',0,1),(338,'PRELS.Masc.Gen.Sg',0,1),(339,'PRELS.Masc.Nom.Sg',0,1),(340,'PRELS.Neut.Akk.Sg',0,1),(341,'PRELS.Neut.Dat.Sg',0,1),(342,'PRELS.Neut.Gen.Sg',0,1),(343,'PRELS.Neut.Nom.Sg',0,1),(344,'PRELS.*.Nom.Pl',0,1),(345,'PRF.*.*.*',0,1),(346,'PRF.1.Pl.Akk',0,1),(347,'PRF.1.Pl.Dat',0,1),(348,'PRF.1.Pl.Gen',0,1),(349,'PRF.1.Pl.Nom',0,1),(350,'PRF.1.Sg.Akk',0,1),(351,'PRF.1.Sg.Dat',0,1),(352,'PRF.1.Sg.Gen',0,1),(353,'PRF.1.Sg.Nom',0,1),(354,'PRF.2.Pl.Akk',0,1),(355,'PRF.2.Pl.Dat',0,1),(356,'PRF.2.Pl.Gen',0,1),(357,'PRF.2.Pl.Nom',0,1),(358,'PRF.2.Sg.Akk',0,1),(359,'PRF.2.Sg.Dat',0,1),(360,'PRF.2.Sg.Gen',0,1),(361,'PRF.2.Sg.Nom',0,1),(362,'PRF.3.Pl.Akk',0,1),(363,'PRF.3.Pl.Dat',0,1),(364,'PRF.3.Pl.Gen',0,1),(365,'PRF.3.Pl.Nom',0,1),(366,'PRF.3.Sg.Akk',0,1),(367,'PRF.3.Sg.Dat',0,1),(368,'PRF.3.Sg.Gen',0,1),(369,'PRF.3.Sg.Nom',0,1),(370,'PTKA',0,1),(371,'PTKANT',0,1),(372,'PTKNEG',0,1),(373,'PTKVZ',0,1),(374,'PTKZU',0,1),(375,'PWAT.*.*.*',0,1),(376,'PWAT.*.Akk.Pl',0,1),(377,'PWAT.*.Dat.Pl',0,1),(378,'PWAT.Fem.Akk.Sg',0,1),(379,'PWAT.Fem.Dat.Sg',0,1),(380,'PWAT.Fem.Gen.Sg',0,1),(381,'PWAT.Fem.Nom.Sg',0,1),(382,'PWAT.*.Gen.Pl',0,1),(383,'PWAT.Masc.Akk.Sg',0,1),(384,'PWAT.Masc.Dat.Sg',0,1),(385,'PWAT.Masc.Gen.Sg',0,1),(386,'PWAT.Masc.Nom.Sg',0,1),(387,'PWAT.Neut.Akk.Sg',0,1),(388,'PWAT.Neut.Dat.Sg',0,1),(389,'PWAT.Neut.Gen.Sg',0,1),(390,'PWAT.Neut.Nom.Sg',0,1),(391,'PWAT.*.Nom.Pl',0,1),(392,'PWAV',0,1),(393,'PWS.*.Akk.Pl',0,1),(394,'PWS.*.Akk.Sg',0,1),(395,'PWS.*.Dat.Pl',0,1),(396,'PWS.*.Dat.Sg',0,1),(397,'PWS.Fem.Akk.Pl',0,1),(398,'PWS.Fem.Akk.Sg',0,1),(399,'PWS.Fem.Dat.Pl',0,1),(400,'PWS.Fem.Dat.Sg',0,1),(401,'PWS.Fem.Gen.Pl',0,1),(402,'PWS.Fem.Gen.Sg',0,1),(403,'PWS.Fem.Nom.Pl',0,1),(404,'PWS.Fem.Nom.Sg',0,1),(405,'PWS.*.Gen.Pl',0,1),(406,'PWS.*.Gen.Sg',0,1),(407,'PWS.Masc.Akk.Pl',0,1),(408,'PWS.Masc.Akk.Sg',0,1),(409,'PWS.Masc.Dat.Pl',0,1),(410,'PWS.Masc.Dat.Sg',0,1),(411,'PWS.Masc.Gen.Pl',0,1),(412,'PWS.Masc.Gen.Sg',0,1),(413,'PWS.Masc.Nom.Pl',0,1),(414,'PWS.Masc.Nom.Sg',0,1),(415,'PWS.Neut.Akk.Pl',0,1),(416,'PWS.Neut.Akk.Sg',0,1),(417,'PWS.Neut.Dat.Pl',0,1),(418,'PWS.Neut.Dat.Sg',0,1),(419,'PWS.Neut.Gen.Pl',0,1),(420,'PWS.Neut.Gen.Sg',0,1),(421,'PWS.Neut.Nom.Pl',0,1),(422,'PWS.Neut.Nom.Sg',0,1),(423,'PWS.*.Nom.Pl',0,1),(424,'PWS.*.Nom.Sg',0,1),(425,'TRUNC',0,1),(426,'VAFIN.1.Pl.Past.Ind',0,1),(427,'VAFIN.1.Pl.Past.Konj',0,1),(428,'VAFIN.1.Pl.Pres.Ind',0,1),(429,'VAFIN.1.Pl.Pres.Konj',0,1),(430,'VAFIN.1.Sg.Past.Ind',0,1),(431,'VAFIN.1.Sg.Past.Konj',0,1),(432,'VAFIN.1.Sg.Pres.Ind',0,1),(433,'VAFIN.1.Sg.Pres.Konj',0,1),(434,'VAFIN.2.Pl.Past.Ind',0,1),(435,'VAFIN.2.Pl.Past.Konj',0,1),(436,'VAFIN.2.Pl.Pres.Ind',0,1),(437,'VAFIN.2.Pl.Pres.Konj',0,1),(438,'VAFIN.2.Sg.Past.Ind',0,1),(439,'VAFIN.2.Sg.Past.Konj',0,1),(440,'VAFIN.2.Sg.Pres.Ind',0,1),(441,'VAFIN.2.Sg.Pres.Konj',0,1),(442,'VAFIN.3.Pl.Past.Ind',0,1),(443,'VAFIN.3.Pl.Past.Konj',0,1),(444,'VAFIN.3.Pl.Pres.Ind',0,1),(445,'VAFIN.3.Pl.Pres.Konj',0,1),(446,'VAFIN.3.Sg.Past.Ind',0,1),(447,'VAFIN.3.Sg.Past.Konj',0,1),(448,'VAFIN.3.Sg.Pres.Ind',0,1),(449,'VAFIN.3.Sg.Pres.Konj',0,1),(450,'VAIMP.Pl',0,1),(451,'VAIMP.Sg',0,1),(452,'VAINF',0,1),(453,'VAPP',0,1),(454,'VMFIN.1.Pl.Past.Ind',0,1),(455,'VMFIN.1.Pl.Past.Konj',0,1),(456,'VMFIN.1.Pl.Pres.Ind',0,1),(457,'VMFIN.1.Pl.Pres.Konj',0,1),(458,'VMFIN.1.Sg.Past.Ind',0,1),(459,'VMFIN.1.Sg.Past.Konj',0,1),(460,'VMFIN.1.Sg.Pres.Ind',0,1),(461,'VMFIN.1.Sg.Pres.Konj',0,1),(462,'VMFIN.2.Pl.Past.Ind',0,1),(463,'VMFIN.2.Pl.Past.Konj',0,1),(464,'VMFIN.2.Pl.Pres.Ind',0,1),(465,'VMFIN.2.Pl.Pres.Konj',0,1),(466,'VMFIN.2.Sg.Past.Ind',0,1),(467,'VMFIN.2.Sg.Past.Konj',0,1),(468,'VMFIN.2.Sg.Pres.Ind',0,1),(469,'VMFIN.2.Sg.Pres.Konj',0,1),(470,'VMFIN.3.Pl.Past.Ind',0,1),(471,'VMFIN.3.Pl.Past.Konj',0,1),(472,'VMFIN.3.Pl.Pres.Ind',0,1),(473,'VMFIN.3.Pl.Pres.Konj',0,1),(474,'VMFIN.3.Sg.Past.Ind',0,1),(475,'VMFIN.3.Sg.Past.Konj',0,1),(476,'VMFIN.3.Sg.Pres.Ind',0,1),(477,'VMFIN.3.Sg.Pres.Konj',0,1),(478,'VMINF',0,1),(479,'VMPP',0,1),(480,'VVFIN.1.Pl.Past.Ind',1,1),(481,'VVFIN.1.Pl.Past.Konj',1,1),(482,'VVFIN.1.Pl.Pres.Ind',1,1),(483,'VVFIN.1.Pl.Pres.Konj',1,1),(484,'VVFIN.1.Sg.Past.Ind',1,1),(485,'VVFIN.1.Sg.Past.Konj',1,1),(486,'VVFIN.1.Sg.Pres.Ind',1,1),(487,'VVFIN.1.Sg.Pres.Konj',1,1),(488,'VVFIN.2.Pl.Past.Ind',0,1),(489,'VVFIN.2.Pl.Past.Konj',0,1),(490,'VVFIN.2.Pl.Pres.Ind',0,1),(491,'VVFIN.2.Pl.Pres.Konj',0,1),(492,'VVFIN.2.Sg.Past.Ind',0,1),(493,'VVFIN.2.Sg.Past.Konj',0,1),(494,'VVFIN.2.Sg.Pres.Ind',0,1),(495,'VVFIN.2.Sg.Pres.Konj',0,1),(496,'VVFIN.3.Pl.Past.Ind',0,1),(497,'VVFIN.3.Pl.Past.Konj',0,1),(498,'VVFIN.3.Pl.Pres.Ind',0,1),(499,'VVFIN.3.Pl.Pres.Konj',0,1),(500,'VVFIN.3.Sg.Past.Ind',0,1),(501,'VVFIN.3.Sg.Past.Konj',0,1),(502,'VVFIN.3.Sg.Pres.Ind',0,1),(503,'VVFIN.3.Sg.Pres.Konj',0,1),(504,'VVIMP.Pl',0,1),(505,'VVIMP.Sg',0,1),(506,'VVINF',0,1),(507,'VVIZU',0,1),(508,'VVPP',0,1),(509,'XY',0,1);
/*!40000 ALTER TABLE `tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag_suggestion`
--

DROP TABLE IF EXISTS `tag_suggestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_suggestion` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `score` float DEFAULT NULL,
  `selected` tinyint(1) NOT NULL DEFAULT '0',
  `source` enum('user','auto') NOT NULL,
  `tag_id` bigint(20) NOT NULL,
  `mod_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_id_2` (`tag_id`,`mod_id`),
  KEY `tag_id` (`tag_id`),
  KEY `mod_id` (`mod_id`),
  CONSTRAINT `tagsuggestion_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`),
  CONSTRAINT `tag_suggestion_ibfk_1` FOREIGN KEY (`mod_id`) REFERENCES `modern` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag_suggestion`
--

LOCK TABLES `tag_suggestion` WRITE;
/*!40000 ALTER TABLE `tag_suggestion` DISABLE KEYS */;
INSERT INTO `tag_suggestion` VALUES (3,NULL,1,'user',499,4),(4,NULL,1,'user',219,5),(5,0.97,1,'auto',497,1);
/*!40000 ALTER TABLE `tag_suggestion` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tagset`
--

LOCK TABLES `tagset` WRITE;
/*!40000 ALTER TABLE `tagset` DISABLE KEYS */;
INSERT INTO `tagset` VALUES (1,'ImportTest','closed','POS');
/*!40000 ALTER TABLE `tagset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `text`
--

DROP TABLE IF EXISTS `text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sigle` varchar(255) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creator_id` int(11) NOT NULL,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changer_id` int(11) NOT NULL,
  `currentmod_id` bigint(20) DEFAULT NULL,
  `header` text,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `changer_id` (`changer_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `text_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `text`
--

LOCK TABLES `text` WRITE;
/*!40000 ALTER TABLE `text` DISABLE KEYS */;
INSERT INTO `text` VALUES (3,'t1','test-dummy',1,'2013-01-22 14:30:30',1,'0000-00-00 00:00:00',3,NULL,NULL),(4,'t2','yet another dummy',1,'2013-01-31 13:13:20',1,'0000-00-00 00:00:00',1,NULL,NULL);
/*!40000 ALTER TABLE `text` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `text2tagset`
--

DROP TABLE IF EXISTS `text2tagset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `text2tagset` (
  `text_id` int(11) NOT NULL,
  `tagset_id` int(11) NOT NULL,
  `complete` tinyint(1) NOT NULL DEFAULT '0',
  KEY `text_id` (`text_id`),
  KEY `tagset_id` (`tagset_id`),
  CONSTRAINT `text2tagset_ibfk_1` FOREIGN KEY (`text_id`) REFERENCES `text` (`id`) ON DELETE CASCADE,
  CONSTRAINT `text2tagset_ibfk_2` FOREIGN KEY (`tagset_id`) REFERENCES `tagset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `text2tagset`
--

LOCK TABLES `text2tagset` WRITE;
/*!40000 ALTER TABLE `text2tagset` DISABLE KEYS */;
INSERT INTO `text2tagset` VALUES (3,1,0),(4,1,0);
/*!40000 ALTER TABLE `text2tagset` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `token`
--

LOCK TABLES `token` WRITE;
/*!40000 ALTER TABLE `token` DISABLE KEYS */;
INSERT INTO `token` VALUES (1,3,'*{A*4}n$helm%9',1),(2,3,'pi$t||u||s',2),(3,3,'aller#lieb$tev',3),(4,3,'vunf=tusent#vnd#vierhundert#vn-(=)sechzig',4),(5,3,'kunnen.(.)',5),(6,4,'test',1);
/*!40000 ALTER TABLE `token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user2project`
--

DROP TABLE IF EXISTS `user2project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user2project` (
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  KEY `user_id` (`user_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `user2project_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user2project_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user2project`
--

LOCK TABLES `user2project` WRITE;
/*!40000 ALTER TABLE `user2project` DISABLE KEYS */;
INSERT INTO `user2project` VALUES (3,1);
/*!40000 ALTER TABLE `user2project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `lines_per_page` int(11) NOT NULL DEFAULT '30',
  `lines_context` int(11) NOT NULL DEFAULT '5',
  `columns_order` varchar(255) DEFAULT NULL,
  `columns_hidden` varchar(255) DEFAULT NULL,
  `show_error` tinyint(1) NOT NULL DEFAULT '1',
  `lastactive` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'system','',1,30,5,NULL,NULL,1,'2013-01-16 14:22:57'),(3,'bollmann','4d8902ba9a9130dbf55947e88393bd47',1,30,5,'7/6,6/7','',1,'2013-02-04 11:29:04'),(5,'test','68358d5d9cbbf39fe571ba41f26524b6',0,30,5,NULL,NULL,1,'2013-01-22 15:38:32');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-06  4:25:47
