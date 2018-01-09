-- MySQL dump 10.16  Distrib 10.1.26-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: ybforum
-- ------------------------------------------------------
-- Server version	10.1.26-MariaDB-0+deb9u1

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
-- Table structure for table `confirm_user_table`
--

DROP TABLE IF EXISTS `confirm_user_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `confirm_user_table` (
  `iduser` int(10) unsigned NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `confirm_code` varchar(191) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip_address` varchar(45) NOT NULL,
  `confirm_source` varchar(45) NOT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `confirm_code_UNIQUE` (`confirm_code`),
  CONSTRAINT `fk_migrate_user_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `deactivated_user_view`
--

DROP TABLE IF EXISTS `deactivated_user_view`;
/*!50001 DROP VIEW IF EXISTS `deactivated_user_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `deactivated_user_view` (
  `deactivated_id` tinyint NOT NULL,
  `deactivated_nick` tinyint NOT NULL,
  `deactivated_email` tinyint NOT NULL,
  `deactivated_byid` tinyint NOT NULL,
  `deactivated_bynick` tinyint NOT NULL,
  `reason` tinyint NOT NULL,
  `deactivated_ts` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `hidden_post_view`
--

DROP TABLE IF EXISTS `hidden_post_view`;
/*!50001 DROP VIEW IF EXISTS `hidden_post_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `hidden_post_view` (
  `idpost` tinyint NOT NULL,
  `idthread` tinyint NOT NULL,
  `parent_idpost` tinyint NOT NULL,
  `iduser` tinyint NOT NULL,
  `nick` tinyint NOT NULL,
  `title` tinyint NOT NULL,
  `content` tinyint NOT NULL,
  `creation_ts` tinyint NOT NULL,
  `email` tinyint NOT NULL,
  `link_url` tinyint NOT NULL,
  `link_text` tinyint NOT NULL,
  `img_url` tinyint NOT NULL,
  `ip_address` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

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
  `message` varchar(255) DEFAULT NULL,
  `request_uri` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `admin_iduser` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idlog`),
  KEY `log_table_idlog_type_idx` (`idlog_type`),
  KEY `log_table_iduser_idx` (`iduser`),
  KEY `log_table_admin_iduser_idx` (`admin_iduser`),
  CONSTRAINT `log_table_admin_iduser` FOREIGN KEY (`admin_iduser`) REFERENCES `user_table` (`iduser`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `log_table_idlog_type` FOREIGN KEY (`idlog_type`) REFERENCES `log_type_table` (`idlog_type`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `log_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_type_table`
--

DROP TABLE IF EXISTS `log_type_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_type_table` (
  `idlog_type` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`idlog_type`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `log_view`
--

DROP TABLE IF EXISTS `log_view`;
/*!50001 DROP VIEW IF EXISTS `log_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `log_view` (
  `idlog` tinyint NOT NULL,
  `ts` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `iduser` tinyint NOT NULL,
  `nick` tinyint NOT NULL,
  `message` tinyint NOT NULL,
  `request_uri` tinyint NOT NULL,
  `ip_address` tinyint NOT NULL,
  `admin_iduser` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `pending_admin_approval_view`
--

DROP TABLE IF EXISTS `pending_admin_approval_view`;
/*!50001 DROP VIEW IF EXISTS `pending_admin_approval_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `pending_admin_approval_view` (
  `iduser` tinyint NOT NULL,
  `nick` tinyint NOT NULL,
  `email` tinyint NOT NULL,
  `registration_ts` tinyint NOT NULL,
  `registration_msg` tinyint NOT NULL,
  `confirmation_ts` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

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
  `title` varchar(100) NOT NULL,
  `content` text,
  `rank` smallint(5) unsigned NOT NULL,
  `indent` smallint(5) unsigned NOT NULL,
  `creation_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `link_text` varchar(255) DEFAULT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `old_no` int(10) unsigned DEFAULT NULL,
  `hidden` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`idpost`),
  UNIQUE KEY `idpost_UNIQUE` (`idpost`),
  UNIQUE KEY `old_nr_UNIQUE` (`old_no`),
  KEY `fk_post_table_idthread_idx` (`idthread`),
  KEY `fk_post_table_idpost_idx` (`parent_idpost`),
  KEY `fk_post_table_iduser_idx` (`iduser`),
  CONSTRAINT `fk_post_table_idpost` FOREIGN KEY (`parent_idpost`) REFERENCES `post_table` (`idpost`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_table_idthread` FOREIGN KEY (`idthread`) REFERENCES `thread_table` (`idthread`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reset_password_table`
--

DROP TABLE IF EXISTS `reset_password_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reset_password_table` (
  `iduser` int(10) unsigned NOT NULL,
  `confirm_code` varchar(191) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip_address` varchar(45) NOT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `confirm_code_UNIQUE` (`confirm_code`),
  CONSTRAINT `fk_reset_password_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `update_email_table`
--

DROP TABLE IF EXISTS `update_email_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `update_email_table` (
  `iduser` int(10) unsigned NOT NULL,
  `email` varchar(191) NOT NULL,
  `confirm_code` varchar(191) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip_address` varchar(45) NOT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `confirm_code_UNIQUE` (`confirm_code`),
  CONSTRAINT `fk_update_email_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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
  `reason` varchar(255) NOT NULL,
  `deactivated_ts` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iduser`),
  KEY `user_deactived_reason_table_deactivated_by_idx` (`deactivated_by_iduser`),
  CONSTRAINT `user_deactivated_reason_table_deactivated_by` FOREIGN KEY (`deactivated_by_iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_deactivated_reason_table_iduser` FOREIGN KEY (`iduser`) REFERENCES `user_table` (`iduser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_table`
--

DROP TABLE IF EXISTS `user_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_table` (
  `iduser` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `registration_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_msg` text,
  `confirmation_ts` timestamp NULL DEFAULT NULL,
  `old_passwd` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `iduser_UNIQUE` (`iduser`),
  UNIQUE KEY `nick_UNIQUE` (`nick`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `userlist_view`
--

DROP TABLE IF EXISTS `userlist_view`;
/*!50001 DROP VIEW IF EXISTS `userlist_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `userlist_view` (
  `iduser` tinyint NOT NULL,
  `nick` tinyint NOT NULL,
  `email` tinyint NOT NULL,
  `admin` tinyint NOT NULL,
  `active` tinyint NOT NULL,
  `registration_ts` tinyint NOT NULL,
  `registration_msg` tinyint NOT NULL,
  `confirmation_ts` tinyint NOT NULL,
  `has_password` tinyint NOT NULL,
  `has_old_passwd` tinyint NOT NULL,
  `is_dummy` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `deactivated_user_view`
--

/*!50001 DROP TABLE IF EXISTS `deactivated_user_view`*/;
/*!50001 DROP VIEW IF EXISTS `deactivated_user_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `deactivated_user_view` AS select `d`.`iduser` AS `deactivated_id`,`u1`.`nick` AS `deactivated_nick`,`u1`.`email` AS `deactivated_email`,`u2`.`iduser` AS `deactivated_byid`,`u2`.`nick` AS `deactivated_bynick`,`d`.`reason` AS `reason`,`d`.`deactivated_ts` AS `deactivated_ts` from ((`user_deactivated_reason_table` `d` join `user_table` `u1` on((`u1`.`iduser` = `d`.`iduser`))) join `user_table` `u2` on((`u2`.`iduser` = `d`.`deactivated_by_iduser`))) where (`u1`.`active` = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `hidden_post_view`
--

/*!50001 DROP TABLE IF EXISTS `hidden_post_view`*/;
/*!50001 DROP VIEW IF EXISTS `hidden_post_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `hidden_post_view` AS select `post_table`.`idpost` AS `idpost`,`post_table`.`idthread` AS `idthread`,`post_table`.`parent_idpost` AS `parent_idpost`,`post_table`.`iduser` AS `iduser`,`user_table`.`nick` AS `nick`,`post_table`.`title` AS `title`,`post_table`.`content` AS `content`,`post_table`.`creation_ts` AS `creation_ts`,`post_table`.`email` AS `email`,`post_table`.`link_url` AS `link_url`,`post_table`.`link_text` AS `link_text`,`post_table`.`img_url` AS `img_url`,`post_table`.`ip_address` AS `ip_address` from (`post_table` left join `user_table` on((`post_table`.`iduser` = `user_table`.`iduser`))) where (`post_table`.`hidden` > 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `log_view`
--

/*!50001 DROP TABLE IF EXISTS `log_view`*/;
/*!50001 DROP VIEW IF EXISTS `log_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `log_view` AS select `l`.`idlog` AS `idlog`,`l`.`ts` AS `ts`,`lt`.`description` AS `description`,`l`.`iduser` AS `iduser`,`u`.`nick` AS `nick`,`l`.`message` AS `message`,`l`.`request_uri` AS `request_uri`,`l`.`ip_address` AS `ip_address`,`l`.`admin_iduser` AS `admin_iduser` from ((`log_table` `l` left join `user_table` `u` on((`l`.`iduser` = `u`.`iduser`))) left join `log_type_table` `lt` on((`lt`.`idlog_type` = `l`.`idlog_type`))) order by `l`.`idlog` desc limit 100 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `pending_admin_approval_view`
--

/*!50001 DROP TABLE IF EXISTS `pending_admin_approval_view`*/;
/*!50001 DROP VIEW IF EXISTS `pending_admin_approval_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `pending_admin_approval_view` AS select `user_table`.`iduser` AS `iduser`,`user_table`.`nick` AS `nick`,`user_table`.`email` AS `email`,`user_table`.`registration_ts` AS `registration_ts`,`user_table`.`registration_msg` AS `registration_msg`,`user_table`.`confirmation_ts` AS `confirmation_ts` from `user_table` where ((`user_table`.`confirmation_ts` is not null) and (`user_table`.`active` = 0) and (not(`user_table`.`iduser` in (select `user_deactivated_reason_table`.`iduser` from `user_deactivated_reason_table`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `userlist_view`
--

/*!50001 DROP TABLE IF EXISTS `userlist_view`*/;
/*!50001 DROP VIEW IF EXISTS `userlist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `userlist_view` AS select `user_table`.`iduser` AS `iduser`,`user_table`.`nick` AS `nick`,`user_table`.`email` AS `email`,`user_table`.`admin` AS `admin`,`user_table`.`active` AS `active`,`user_table`.`registration_ts` AS `registration_ts`,`user_table`.`registration_msg` AS `registration_msg`,`user_table`.`confirmation_ts` AS `confirmation_ts`,(`user_table`.`password` is not null) AS `has_password`,(`user_table`.`old_passwd` is not null) AS `has_old_passwd`,(isnull(`user_table`.`email`) and isnull(`user_table`.`password`) and isnull(`user_table`.`old_passwd`)) AS `is_dummy` from `user_table` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-12-31 15:09:43
