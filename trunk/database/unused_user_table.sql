CREATE TABLE `unused_user_table` (
  `iduser` int(10) unsigned NOT NULL,
  `nick` varchar(60) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `registration_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `registration_msg` text,
  `old_passwd` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`iduser`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

