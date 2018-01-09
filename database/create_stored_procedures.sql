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
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping routines for database 'ybforum'
--
/*!50003 DROP PROCEDURE IF EXISTS `insert_reply` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `insert_reply`(IN idparentpost BIGINT, 
  IN iduser INT, IN title VARCHAR(100) CHARACTER SET utf8mb4, IN content TEXT CHARACTER SET utf8mb4, 
  IN ipaddress VARCHAR(45) CHARACTER SET utf8mb4, IN email VARCHAR(255) CHARACTER SET utf8mb4,
  IN link_url VARCHAR(255) CHARACTER SET utf8mb4, link_text VARCHAR(255) CHARACTER SET utf8mb4,
  IN img_url VARCHAR(255) CHARACTER SET utf8mb4)
BEGIN
 SET @idthread = NULL;
 SET @rank = NULL;
 SET @indent = NULL;
 SELECT `idthread`, `rank` + 1, `indent` + 1 INTO @idthread, @rank, @indent 
  FROM post_table WHERE idpost = idparentpost;
 IF @idthread IS NOT NULL THEN
  -- shift existing child-posts and insert
  START TRANSACTION;
  UPDATE post_table SET rank = rank + 1 WHERE idthread = @idthread AND rank >= @rank;
  INSERT INTO post_table (idthread, parent_idpost, iduser, title, content, rank, indent, 
   email, link_url, link_text, img_url, ip_address)
   VALUES(@idthread, idparentpost, iduser, title, content, @rank, @indent, 
   email, link_url, link_text, img_url, ipaddress);
  COMMIT;
  SELECT LAST_INSERT_ID();
 END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-12-31 15:16:35
