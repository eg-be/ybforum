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
-- Dumping data for table `log_type_table`
--

LOCK TABLES `log_type_table` WRITE;
/*!40000 ALTER TABLE `log_type_table` DISABLE KEYS */;
INSERT INTO `log_type_table` VALUES (1,'AuthFailedNoSuchUser','Authentication failed because no matching nickname was found'),(2,'AuthFailedUserIsDummy','Authentication failed because requested user is a dummy (no password, no old password, no email)'),(3,'AuthFailedPassInvalid','Authentication failed because passed password does not match'),(4,'AuthFailedUserInactive','Authentication failed because user is not set to active'),(5,'AuthFailedOldPassInvalid','Authentication failed because old password does not match'),(6,'AuthUsingOldPassword','Authentication using old password succeeded'),(7,'OperationFailedMigrationRequired','Operation failed because user needs to migrate first (has field old_passwd set)'),(8,'OperationFailedUserIsDummy','Operation failed because user context is a dummy (no password, no old passsword, no email)'),(9,'OperationFailedAlreadyMigrated','Operation failed because user has already been migrated'),(10,'OperationFailedAlreadyConfirmed','Operation failed because user has already confirmed his account'),(11,'OperationFailedEmailNotUnique','Operation failed because passed email is used within some other account'),(12,'OperationFailedNickNotUnique','Operation failed because passed nick is used within some other account'),(13,'OperationFailedNoMatchingNickOrEmail','Operation failed because no user matches the passed nick or email'),(14,'OperationFailedUserHasNoEmail','Operation failed because user context has no email'),(15,'OperationFailedUserIsInactive','Operation failed because user context is not set to active'),(16,'ConfirmMigrationCodeCreated','A confirmation code to confirm migration was created'),(17,'ConfirmRegistrationCodeCreated','A confirmation code to confirm registration was created'),(18,'ConfirmResetPasswordCodeCreated','A confirmation code to reset the password was created'),(19,'ConfirmEmailCodeCreated','A confirmatoin code to confirm an updated email address was created'),(20,'ConfirmFailedCodeInvalid','The passed confirmation code was not found in the database for the active context'),(21,'ConfirmFailedNoMatchingUser','The passed confirmaton code does not fit any user'),(22,'UserPasswordUpdated','User has updated his password'),(23,'UserEmailUpdated','User has updated his email address'),(24,'UserActived','User has been activated'),(25,'UserMigrationConfirmed','User has completed migration'),(26,'UserRegistrationConfirmed','User has confirmed registration'),(27,'StammposterLogin','Stammposter logged in'),(28,'AdminLogin','Admin logged in'),(29,'AdminLoginFailedUserIsNoAdmin','User authenticated successfully, but user is not an admin'),(30,'UserDeactivated','User has been deactivated'),(31,'UserAdminSet','User has been propagated to Admin'),(32,'UserAdminRemoved','User has been downgraded and is no longer an Admin'),(33,'UserAccepted','User registration has been accepted by an Admin'),(34,'UserDeleted','User has been deleted entirely from user_table'),(35,'NotifiedUserAccepted','Notification mail has been sent that user has been accepted'),(36,'NotifiedUserDenied','Notification mail has been sent that user has been denied'),(37,'NotifiedAdminUserConfiremdRegistration','Notification mail has been sent to an admin that a user has confirmed his email address'),(38,'ErrorExceptionThrown','Error occured due to unhandled Exception'),(39,'UserTurnedIntoDummy','User has been turned into a dummy (set all fields to null, except nick)'),(40,'PostHidden','A Post has been marked as hidden'),(41,'PostShow','A Post has been marked for being shown'),(42,'UserCreated','A new user has been added');
/*!40000 ALTER TABLE `log_type_table` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-12-31 15:12:02
