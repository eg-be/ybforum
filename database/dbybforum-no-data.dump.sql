-- MySQL dump 10.15  Distrib 10.0.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: server09.hostfactory.ch    Database: dbybforum
-- ------------------------------------------------------
-- Server version	10.0.32-MariaDB-0+deb8u1

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
-- Table structure for table `blacklist_table`
--

DROP TABLE IF EXISTS `blacklist_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blacklist_table` (
  `idblacklist` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `email_regex` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci NOT NULL,
  PRIMARY KEY (`idblacklist`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `email_regex_UNIQUE` (`email_regex`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8 COLLATE=utf8_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `confirm_user_table`
--

DROP TABLE IF EXISTS `confirm_user_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `confirm_user_table` (
  `iduser` int(10) unsigned NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_german2_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_german2_ci NOT NULL,
  `confirm_code` varchar(191) COLLATE utf8mb4_german2_ci NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip_address` varchar(45) COLLATE utf8mb4_german2_ci NOT NULL,
  `confirm_source` varchar(45) COLLATE utf8mb4_german2_ci NOT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `confirm_code_UNIQUE` (`confirm_code`),
  CONSTRAINT `fk_confirm_user_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_extended_info`
--

DROP TABLE IF EXISTS `log_extended_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_extended_info` (
  `idlog_extended_info` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idlog` int(10) unsigned NOT NULL,
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci NOT NULL,
  PRIMARY KEY (`idlog_extended_info`),
  KEY `log_table_idlog` (`idlog`),
  CONSTRAINT `log_table_idlog` FOREIGN KEY (`idlog`) REFERENCES `log_table` (`idlog`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_table`
--

DROP TABLE IF EXISTS `log_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_table` (
  `idlog` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idlog_type` int(10) unsigned NOT NULL,
  `ts` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `iduser` int(10) unsigned DEFAULT NULL,
  `historic_user_context` varchar(255) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `message` varchar(255) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `request_uri` varchar(255) COLLATE utf8mb4_german2_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_german2_ci NOT NULL,
  `admin_iduser` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idlog`),
  KEY `log_table_idlog_type_idx` (`idlog_type`),
  KEY `log_table_iduser_idx` (`iduser`),
  KEY `log_table_admin_iduser_idx` (`admin_iduser`),
  CONSTRAINT `log_table_admin_iduser` FOREIGN KEY (`admin_iduser`) REFERENCES `user_table` (`iduser`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `log_table_idlog_type` FOREIGN KEY (`idlog_type`) REFERENCES `log_type_table` (`idlog_type`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `log_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11395 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_type_table`
--

DROP TABLE IF EXISTS `log_type_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_type_table` (
  `idlog_type` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8mb4_german2_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_german2_ci NOT NULL,
  PRIMARY KEY (`idlog_type`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_table`
--

DROP TABLE IF EXISTS `post_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_table` (
  `idpost` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idthread` int(10) unsigned NOT NULL,
  `parent_idpost` int(10) unsigned DEFAULT NULL,
  `iduser` int(10) unsigned NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_german2_ci NOT NULL,
  `content` text COLLATE utf8mb4_german2_ci,
  `rank` smallint(5) unsigned NOT NULL,
  `indent` smallint(5) unsigned NOT NULL,
  `creation_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(255) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `link_text` varchar(255) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `img_url` varchar(255) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_german2_ci NOT NULL,
  `old_no` int(10) unsigned DEFAULT NULL,
  `hidden` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`idpost`),
  UNIQUE KEY `idpost_UNIQUE` (`idpost`),
  UNIQUE KEY `old_nr_UNIQUE` (`old_no`),
  KEY `fk_post_table_idthread_idx` (`idthread`),
  KEY `fk_post_table_idpost_idx` (`parent_idpost`),
  KEY `fk_post_table_iduser_idx` (`iduser`),
  FULLTEXT KEY `fulltext_title_content` (`title`,`content`),
  CONSTRAINT `fk_post_table_idpost` FOREIGN KEY (`parent_idpost`) REFERENCES `post_table` (`idpost`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_table_idthread` FOREIGN KEY (`idthread`) REFERENCES `thread_table` (`idthread`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=682535 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reset_password_table`
--

DROP TABLE IF EXISTS `reset_password_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reset_password_table` (
  `iduser` int(10) unsigned NOT NULL,
  `confirm_code` varchar(191) COLLATE utf8mb4_german2_ci NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip_address` varchar(45) COLLATE utf8mb4_german2_ci NOT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `confirm_code_UNIQUE` (`confirm_code`),
  CONSTRAINT `fk_reset_password_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `thread_table`
--

DROP TABLE IF EXISTS `thread_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thread_table` (
  `idthread` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_threadno` int(10) unsigned DEFAULT NULL,
  `old_rootpostno` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idthread`),
  UNIQUE KEY `idthread_table_UNIQUE` (`idthread`),
  UNIQUE KEY `old_rootpostno_UNIQUE` (`old_rootpostno`)
) ENGINE=InnoDB AUTO_INCREMENT=80049 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unused_user_table`
--

DROP TABLE IF EXISTS `unused_user_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unused_user_table` (
  `iduser` int(10) unsigned NOT NULL,
  `nick` varchar(60) COLLATE utf8mb4_german2_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `registration_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `registration_msg` text COLLATE utf8mb4_german2_ci,
  `old_passwd` varchar(100) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `update_email_table`
--

DROP TABLE IF EXISTS `update_email_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `update_email_table` (
  `iduser` int(10) unsigned NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_german2_ci NOT NULL,
  `confirm_code` varchar(191) COLLATE utf8mb4_german2_ci NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip_address` varchar(45) COLLATE utf8mb4_german2_ci NOT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `confirm_code_UNIQUE` (`confirm_code`),
  CONSTRAINT `fk_update_email_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_deactivated_reason_table`
--

DROP TABLE IF EXISTS `user_deactivated_reason_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_deactivated_reason_table` (
  `iduser` int(10) unsigned NOT NULL,
  `deactivated_by_iduser` int(10) unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_german2_ci NOT NULL,
  `deactivated_ts` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iduser`),
  KEY `user_deactived_reason_table_deactivated_by_idx` (`deactivated_by_iduser`),
  CONSTRAINT `user_deactivated_reason_table_deactivated_by` FOREIGN KEY (`deactivated_by_iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_deactivated_reason_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_table`
--

DROP TABLE IF EXISTS `user_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_table` (
  `iduser` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(60) COLLATE utf8mb4_german2_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  `admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `registration_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_msg` text COLLATE utf8mb4_german2_ci,
  `confirmation_ts` timestamp NULL DEFAULT NULL,
  `old_passwd` varchar(100) COLLATE utf8mb4_german2_ci DEFAULT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `iduser_UNIQUE` (`iduser`),
  UNIQUE KEY `nick_UNIQUE` (`nick`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2874 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_german2_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'dbybforum'
--
/*!50003 DROP PROCEDURE IF EXISTS `insert_reply` */;
DROP PROCEDURE IF EXISTS `insert_reply`;
ALTER DATABASE `dbybforum` CHARACTER SET utf8 COLLATE utf8_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER() PROCEDURE `insert_reply`(IN `idparentpost` BIGINT, IN `iduser` INT, IN `title` VARCHAR(100) CHARSET utf8mb4, IN `content` TEXT CHARSET utf8mb4, IN `ipaddress` VARCHAR(45) CHARSET utf8mb4, IN `email` VARCHAR(255) CHARSET utf8mb4, IN `link_url` VARCHAR(255) CHARSET utf8mb4, IN `link_text` VARCHAR(255) CHARSET utf8mb4, IN `img_url` VARCHAR(255) CHARSET utf8mb4, OUT newPostId BIGINT)
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
BEGIN
 SET @idthread = NULL;
 SET @rank = NULL;
 SET @indent = NULL;
 SELECT `idthread`, `rank` + 1, `indent` + 1 INTO @idthread, @rank, @indent 
  FROM post_table WHERE idpost = idparentpost;
 IF @idthread IS NOT NULL THEN
  START TRANSACTION;
  SELECT * FROM post_table WHERE idthread = @idthread FOR UPDATE;
  UPDATE post_table SET `rank` = `rank` + 1 WHERE idthread = @idthread AND `rank` >= @rank;
  INSERT INTO post_table (idthread, parent_idpost, iduser, title, content, `rank`, indent, 
   email, link_url, link_text, img_url, ip_address)
   VALUES(@idthread, idparentpost, iduser, title, content, @rank, @indent, 
   email, link_url, link_text, img_url, ipaddress);
  SELECT LAST_INSERT_ID() INTO newPostId;
  COMMIT;
 ELSE
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = "No post with matching idparentpost found";
 END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `dbybforum` CHARACTER SET utf8 COLLATE utf8_german2_ci ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-05-05 12:34:07
