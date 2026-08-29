# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 212.85.27.138 (MySQL 5.5.5-10.11.14-MariaDB-0ubuntu0.24.04.1)
# Database: vmail
# Generation Time: 2026-08-29 03:55:46 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table admin
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `language` varchar(5) NOT NULL DEFAULT '',
  `passwordlastchange` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `settings` text DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`username`),
  KEY `passwordlastchange` (`passwordlastchange`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table alias
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alias`;

CREATE TABLE `alias` (
  `address` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `accesspolicy` varchar(30) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`address`),
  KEY `domain` (`domain`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table alias_domain
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alias_domain`;

CREATE TABLE `alias_domain` (
  `alias_domain` varchar(255) NOT NULL,
  `target_domain` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`alias_domain`),
  KEY `target_domain` (`target_domain`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table anyone_shares
# ------------------------------------------------------------

DROP TABLE IF EXISTS `anyone_shares`;

CREATE TABLE `anyone_shares` (
  `from_user` varchar(255) NOT NULL,
  `dummy` char(1) DEFAULT '1',
  PRIMARY KEY (`from_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table deleted_mailboxes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `deleted_mailboxes`;

CREATE TABLE `deleted_mailboxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `maildir` varchar(255) NOT NULL DEFAULT '',
  `bytes` bigint(20) NOT NULL DEFAULT 0,
  `messages` bigint(20) NOT NULL DEFAULT 0,
  `admin` varchar(255) NOT NULL DEFAULT '',
  `delete_date` date DEFAULT NULL,
  KEY `id` (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `username` (`username`),
  KEY `domain` (`domain`),
  KEY `admin` (`admin`),
  KEY `delete_date` (`delete_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table domain
# ------------------------------------------------------------

DROP TABLE IF EXISTS `domain`;

CREATE TABLE `domain` (
  `domain` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `disclaimer` text DEFAULT NULL,
  `aliases` int(10) NOT NULL DEFAULT 0,
  `mailboxes` int(10) NOT NULL DEFAULT 0,
  `maillists` int(10) NOT NULL DEFAULT 0,
  `maxquota` bigint(20) NOT NULL DEFAULT 0,
  `quota` bigint(20) NOT NULL DEFAULT 0,
  `transport` varchar(255) NOT NULL DEFAULT 'dovecot',
  `backupmx` tinyint(1) NOT NULL DEFAULT 0,
  `settings` text DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`domain`),
  KEY `backupmx` (`backupmx`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `domain` WRITE;
/*!40000 ALTER TABLE `domain` DISABLE KEYS */;

INSERT INTO `domain` (`domain`, `description`, `disclaimer`, `aliases`, `mailboxes`, `maillists`, `maxquota`, `quota`, `transport`, `backupmx`, `settings`, `created`, `modified`, `expired`, `active`)
VALUES
	('arenaloka.com','Arenaloka',NULL,0,0,0,0,0,'dovecot',0,NULL,'2025-12-26 02:42:38','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('digitrainfitech.com','Digitra Infitech Primadata',NULL,0,0,0,0,0,'dovecot',0,NULL,'2025-02-25 15:30:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('fatchcraft.com','Fatchcraft',NULL,0,0,0,0,0,'dovecot',0,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('kandaga.com',NULL,NULL,0,0,0,0,0,'dovecot',0,'default_user_quota:1024;','2025-12-26 01:28:53','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('kriyasi.com','Kriyasi',NULL,0,0,0,0,0,'dovecot',0,NULL,'2025-02-25 16:34:09','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('sellusi.com','Sellusi',NULL,0,0,0,0,0,'dovecot',0,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('sikampus.com','Sikampus',NULL,0,0,0,0,0,'dovecot',0,NULL,'2025-02-25 16:34:09','1970-01-01 01:01:01','9999-12-31 00:00:00',1);

/*!40000 ALTER TABLE `domain` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table domain_admins
# ------------------------------------------------------------

DROP TABLE IF EXISTS `domain_admins`;

CREATE TABLE `domain_admins` (
  `username` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `domain` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`username`,`domain`),
  KEY `username` (`username`),
  KEY `domain` (`domain`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `domain_admins` WRITE;
/*!40000 ALTER TABLE `domain_admins` DISABLE KEYS */;

INSERT INTO `domain_admins` (`username`, `domain`, `created`, `modified`, `expired`, `active`)
VALUES
	('postmaster@kandaga.com','ALL','2025-12-26 01:28:53','1970-01-01 01:01:01','9999-12-31 00:00:00',1);

/*!40000 ALTER TABLE `domain_admins` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table forwardings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `forwardings`;

CREATE TABLE `forwardings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL DEFAULT '',
  `forwarding` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `dest_domain` varchar(255) NOT NULL DEFAULT '',
  `is_maillist` tinyint(1) NOT NULL DEFAULT 0,
  `is_list` tinyint(1) NOT NULL DEFAULT 0,
  `is_forwarding` tinyint(1) NOT NULL DEFAULT 0,
  `is_alias` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`,`forwarding`),
  KEY `forwarding` (`forwarding`),
  KEY `domain` (`domain`),
  KEY `dest_domain` (`dest_domain`),
  KEY `is_maillist` (`is_maillist`),
  KEY `is_list` (`is_list`),
  KEY `is_alias` (`is_alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `forwardings` WRITE;
/*!40000 ALTER TABLE `forwardings` DISABLE KEYS */;

INSERT INTO `forwardings` (`id`, `address`, `forwarding`, `domain`, `dest_domain`, `is_maillist`, `is_list`, `is_forwarding`, `is_alias`, `active`)
VALUES
	(1,'postmaster@kandaga.com','postmaster@kandaga.com','kandaga.com','kandaga.com',0,0,1,0,1),
	(2,'a.bayu@arenaloka.com','a.bayu@arenaloka.com','arenaloka.com','arenaloka.com',0,0,1,0,1),
	(3,'a.bayu@kandaga.com','a.bayu@kandaga.com','kandaga.com','kandaga.com',0,0,1,0,1),
	(4,'cs@arenaloka.com','cs@arenaloka.com','arenaloka.com','arenaloka.com',0,0,1,0,1);

/*!40000 ALTER TABLE `forwardings` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table last_login
# ------------------------------------------------------------

DROP TABLE IF EXISTS `last_login`;

CREATE TABLE `last_login` (
  `username` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `imap` int(11) DEFAULT NULL,
  `pop3` int(11) DEFAULT NULL,
  `lda` int(11) DEFAULT NULL,
  PRIMARY KEY (`username`),
  KEY `domain` (`domain`),
  KEY `imap` (`imap`),
  KEY `pop3` (`pop3`),
  KEY `lda` (`lda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `last_login` WRITE;
/*!40000 ALTER TABLE `last_login` DISABLE KEYS */;

INSERT INTO `last_login` (`username`, `domain`, `imap`, `pop3`, `lda`)
VALUES
	('a.bayu@arenaloka.com','arenaloka.com',1787975060,NULL,1776045997),
	('a.bayu@fatchcraft.com','fatchcraft.com',1774432620,NULL,1783837902),
	('a.bayu@kandaga.com','kandaga.com',1776648714,NULL,1776619202),
	('a.bayu@sikampus.com','sikampus.com',1787975386,NULL,1787749314),
	('bayu@kandaga.com','kandaga.com',1784454699,NULL,NULL),
	('cs@arenaloka.com','arenaloka.com',1785556692,NULL,1786075614),
	('direktur@kriyasi.com','kriyasi.com',1783995020,NULL,NULL),
	('fatchurohman@fatchcraft.com','fatchcraft.com',1774432920,NULL,1774667684),
	('helpdesk@sikampus.com','sikampus.com',1775533598,NULL,1787782702),
	('latifahmahbub@fatchcraft.com','fatchcraft.com',1774445655,NULL,1784083706),
	('marketing@digitrainfitech.com','digitrainfitech.com',1787657803,NULL,1772025249),
	('marketing@sikampus.com','sikampus.com',1787900012,NULL,1784678569),
	('midtrans@kriyasi.com','kriyasi.com',1786541029,NULL,1786614227),
	('postmaster@kandaga.com','kandaga.com',NULL,NULL,1787974207),
	('sales@kandaga.com','kandaga.com',1785570869,NULL,1786656341),
	('sales@sellusi.com','sellusi.com',1783653209,NULL,1787935486),
	('siak@digitrainfitech.com','digitrainfitech.com',1772027136,NULL,1772025252),
	('siak@sikampus.com','sikampus.com',1787822306,NULL,1785918219);

/*!40000 ALTER TABLE `last_login` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table mailbox
# ------------------------------------------------------------

DROP TABLE IF EXISTS `mailbox`;

CREATE TABLE `mailbox` (
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `language` varchar(5) NOT NULL DEFAULT '',
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `mobile` varchar(255) NOT NULL DEFAULT '',
  `telephone` varchar(255) NOT NULL DEFAULT '',
  `recovery_email` varchar(255) NOT NULL DEFAULT '',
  `birthday` date NOT NULL DEFAULT '0001-01-01',
  `mailboxformat` varchar(50) NOT NULL DEFAULT 'maildir',
  `mailboxfolder` varchar(50) NOT NULL DEFAULT 'Maildir',
  `storagebasedirectory` varchar(255) NOT NULL DEFAULT '/var/vmail',
  `storagenode` varchar(255) NOT NULL DEFAULT 'vmail1',
  `maildir` varchar(255) NOT NULL DEFAULT '',
  `quota` bigint(20) NOT NULL DEFAULT 0,
  `domain` varchar(255) NOT NULL DEFAULT '',
  `transport` varchar(255) NOT NULL DEFAULT '',
  `department` varchar(255) NOT NULL DEFAULT '',
  `rank` varchar(255) NOT NULL DEFAULT 'normal',
  `employeeid` varchar(255) NOT NULL DEFAULT '',
  `isadmin` tinyint(1) NOT NULL DEFAULT 0,
  `isglobaladmin` tinyint(1) NOT NULL DEFAULT 0,
  `enablesmtp` tinyint(1) NOT NULL DEFAULT 1,
  `enablesmtpsecured` tinyint(1) NOT NULL DEFAULT 1,
  `enablepop3` tinyint(1) NOT NULL DEFAULT 1,
  `enablepop3secured` tinyint(1) NOT NULL DEFAULT 1,
  `enablepop3tls` tinyint(1) NOT NULL DEFAULT 1,
  `enableimap` tinyint(1) NOT NULL DEFAULT 1,
  `enableimapsecured` tinyint(1) NOT NULL DEFAULT 1,
  `enableimaptls` tinyint(1) NOT NULL DEFAULT 1,
  `enabledeliver` tinyint(1) NOT NULL DEFAULT 1,
  `enablelda` tinyint(1) NOT NULL DEFAULT 1,
  `enablemanagesieve` tinyint(1) NOT NULL DEFAULT 1,
  `enablemanagesievesecured` tinyint(1) NOT NULL DEFAULT 1,
  `enablesieve` tinyint(1) NOT NULL DEFAULT 1,
  `enablesievesecured` tinyint(1) NOT NULL DEFAULT 1,
  `enablesievetls` tinyint(1) NOT NULL DEFAULT 1,
  `enableinternal` tinyint(1) NOT NULL DEFAULT 1,
  `enabledoveadm` tinyint(1) NOT NULL DEFAULT 1,
  `enablelib-storage` tinyint(1) NOT NULL DEFAULT 1,
  `enablequota-status` tinyint(1) NOT NULL DEFAULT 1,
  `enableindexer-worker` tinyint(1) NOT NULL DEFAULT 1,
  `enablelmtp` tinyint(1) NOT NULL DEFAULT 1,
  `enabledsync` tinyint(1) NOT NULL DEFAULT 1,
  `enablesogo` tinyint(1) NOT NULL DEFAULT 1,
  `enablesogowebmail` char(1) NOT NULL DEFAULT 'y',
  `enablesogocalendar` char(1) NOT NULL DEFAULT 'y',
  `enablesogoactivesync` char(1) NOT NULL DEFAULT 'y',
  `allow_nets` text DEFAULT NULL,
  `disclaimer` text DEFAULT NULL,
  `settings` text DEFAULT NULL,
  `passwordlastchange` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`username`),
  KEY `domain` (`domain`),
  KEY `department` (`department`),
  KEY `employeeid` (`employeeid`),
  KEY `isadmin` (`isadmin`),
  KEY `isglobaladmin` (`isglobaladmin`),
  KEY `enablesmtp` (`enablesmtp`),
  KEY `enablesmtpsecured` (`enablesmtpsecured`),
  KEY `enablepop3` (`enablepop3`),
  KEY `enablepop3secured` (`enablepop3secured`),
  KEY `enablepop3tls` (`enablepop3tls`),
  KEY `enableimap` (`enableimap`),
  KEY `enableimapsecured` (`enableimapsecured`),
  KEY `enableimaptls` (`enableimaptls`),
  KEY `enabledeliver` (`enabledeliver`),
  KEY `enablelda` (`enablelda`),
  KEY `enablemanagesieve` (`enablemanagesieve`),
  KEY `enablemanagesievesecured` (`enablemanagesievesecured`),
  KEY `enablesieve` (`enablesieve`),
  KEY `enablesievesecured` (`enablesievesecured`),
  KEY `enablesievetls` (`enablesievetls`),
  KEY `enablelmtp` (`enablelmtp`),
  KEY `enableinternal` (`enableinternal`),
  KEY `enabledoveadm` (`enabledoveadm`),
  KEY `enablelib-storage` (`enablelib-storage`),
  KEY `enablequota-status` (`enablequota-status`),
  KEY `enableindexer-worker` (`enableindexer-worker`),
  KEY `enabledsync` (`enabledsync`),
  KEY `enablesogo` (`enablesogo`),
  KEY `passwordlastchange` (`passwordlastchange`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `mailbox` WRITE;
/*!40000 ALTER TABLE `mailbox` DISABLE KEYS */;

INSERT INTO `mailbox` (`username`, `password`, `name`, `language`, `first_name`, `last_name`, `mobile`, `telephone`, `recovery_email`, `birthday`, `mailboxformat`, `mailboxfolder`, `storagebasedirectory`, `storagenode`, `maildir`, `quota`, `domain`, `transport`, `department`, `rank`, `employeeid`, `isadmin`, `isglobaladmin`, `enablesmtp`, `enablesmtpsecured`, `enablepop3`, `enablepop3secured`, `enablepop3tls`, `enableimap`, `enableimapsecured`, `enableimaptls`, `enabledeliver`, `enablelda`, `enablemanagesieve`, `enablemanagesievesecured`, `enablesieve`, `enablesievesecured`, `enablesievetls`, `enableinternal`, `enabledoveadm`, `enablelib-storage`, `enablequota-status`, `enableindexer-worker`, `enablelmtp`, `enabledsync`, `enablesogo`, `enablesogowebmail`, `enablesogocalendar`, `enablesogoactivesync`, `allow_nets`, `disclaimer`, `settings`, `passwordlastchange`, `created`, `modified`, `expired`, `active`)
VALUES
	('a.bayu@arenaloka.com','{SSHA512}PxSrKpD2vWWcjpoDAv4vLC0IdYoctog32bUMiLpOtMIwC3azLMdtsHeHh6NL/aVleWXrxRtz0BjtFxs5GwksCEV8ciLN/WBE','A. Bayu Ekawijaya','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','arenaloka.com/a/_/b/a.bayu-2025.12.26.02.43.37/',1000,'arenaloka.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'2025-12-26 02:43:37','2025-12-26 02:43:37','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('a.bayu@fatchcraft.com','{SHA512-CRYPT}$6$w08MXDbCurLTtwWs$UAW4Yayf5CmQE831cy8WMofjGYKqzdIGXBPbEq6jeZmkp2G2pCuyYdNUOdTDc0O9r.sWODwjO9R19gkad1RkC0','A. Bayu','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','fatchcraft.com/a/b/a.bayu/',2048,'fatchcraft.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('a.bayu@sikampus.com','{SSHA512}fdXlCmL13f5YNaindQOv1QwS2Om4U8+E+LlI0oLe7Vq8OlXeiyH6PNVBFnen2EcH7nLIg5i+1HT9T2BsLqUtBqNDABA=','A. Bayu Ekawijaya','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','sikampus.com/a/b/a.bayu/',5000,'sikampus.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'2026-06-25 06:49:56','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('bayu@kandaga.com','{SSHA512}MhJEc71RnqtgTdnZR0I6k0WunOBCCWR2RNP2ikzWcR0VaR83FWsLlaZnsbWcvfzIZnq95SCDFhE5NQVOhmn/8B6GGys=','A. Bayu Ekawijaya','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','kandaga.com/a/_/b/a.bayu-2025.12.26.02.44.14/',1024,'kandaga.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'2026-07-19 09:49:28','2025-12-26 02:44:14','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('cs@arenaloka.com','{SSHA512}YRz2n3aJoRqwihnCaUepjRtd45LGioUBEmUUrYKrmoJCqK93TbL9BS4HxkKBjUaPaSrTezPYDRbamrn6Ag2qVsTU5uKriele','Customer Service Arenaloka','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','arenaloka.com/c/s/_/cs-2026.01.01.05.03.12/',1024,'arenaloka.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'2026-01-01 05:03:12','2026-01-01 05:03:12','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('direktur@kriyasi.com','{SHA512-CRYPT}$6$tV5TQ2IYi6gRVKSO$PamG/CGYsqk/VAnfeQ50eKAWBDMZ1uqE7UUr8WQg6Iuor0w93/8J4IYS4K323Wwc2lqpp.umPf/rH.abNBCIH1','Direktur SCK','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','kriyasi.com/d/direktur/',2048,'kriyasi.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('fatchurohman@fatchcraft.com','{SHA512-CRYPT}$6$PbwFDMR/Ya7shs2X$o6yCeH2D9yxOBPYD2z/pgr2QQJJoDdZrRaQ2YsAM.0K07FZRe6bf.zfoS2fD7Mm3frUrORSb3a9yvgMBBkHcG1','Fatchurohman','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','fatchcraft.com/f/fatchurohman',5000,'fatchcraft.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('helpdesk@sikampus.com','{SHA512-CRYPT}$6$tV5TQ2IYi6gRVKSO$PamG/CGYsqk/VAnfeQ50eKAWBDMZ1uqE7UUr8WQg6Iuor0w93/8J4IYS4K323Wwc2lqpp.umPf/rH.abNBCIH1','Helpdesk Sikampus','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','sikampus.com/h/d/helpdesk/',2048,'sikampus.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('latifahmahbub@fatchcraft.com','{SHA512-CRYPT}$6$GBEgIuRv2MSu6X7b$vNsuujOMqcfCQLUWTr81FFfJTTQxE5wQxkqAB17AHxd/4nmjngQb6qSCgEv5gilKoLRwxKIwUWv2JZr5V77QW.','Latifah','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','fatchcraft.com/f/latifah-2026.03.20.02.43.37/',5000,'fatchcraft.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('marketing@digitrainfitech.com','{SHA512-CRYPT}$6$BSawkuW5.hlet4XG$XPc6MdXSZgqwHn7bWxmrWRp4AdUv/eulE.Kmxap1hWdI1UoCdzID5aSIrnu89VJ9VM.5cIga3bAzCVIkm7Frb/','Marketing Digitra Infitech','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','digitrainfitech.com/m/marketing/',2048,'digitrainfitech.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('marketing@sikampus.com','{SHA512-CRYPT}$6$yFaDYNlZn6c8efz8$Sw/qh62483dEr1PA7el4RXObgff19iLFBWEFXz/1U/t5pW2f4zt2EeXD7bbxxNRI79JqJXhVPLKz.me4nxSud0','Marketing Sikampus','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','sikampus.com/m/marketing-2025.12.26.02.43.37/',5000,'sikampus.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('midtrans@kriyasi.com','{SHA512-CRYPT}$6$2r8upyagpCL7Df4b$9gSWvAqnAfskH7R0zcyT5JvJ/kky030Bhbd.rMj78OLN.evPGlZMK0osxYewJOC86d1/np5H5r9J6vzWp2kaD1','Midtrans Kriyasi','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','kriyasi.com/m/midtrans',1000,'kriyasi.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('postmaster@kandaga.com','{SSHA512}Cl/UzafTtp1GNro8lxUpcLxCK0tHqXi6FoZWXu5v314c6u7IRS+7mIg/53wJc1IcHFQGZBcXsDZGTnviVwAcqzVnKz75fkhU','postmaster','','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','kandaga.com/p/o/s/postmaster/',1024,'kandaga.com','','','normal','',1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','2025-12-26 01:28:53','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('sales@kandaga.com','{SSHA512}BJV3OhUB4phkjaYDozSh9kW0H4kVNE9Roy7bj404kdWisrCQBTJZ4Ohsg3vbvZnESwXuYOFWnvNu+dZAsvI/BUHUs1Y=','Sales Kandaga','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','kandaga.com/a/_/b/sales/',1024,'kandaga.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'2026-04-19 13:26:20','2025-12-26 02:44:14','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('sales@sellusi.com','{SHA512-CRYPT}$6$ELBk3zRk0huCU8bI$a9HV1IuzJtOx6NyZCZumX3yA3wNHEU/bFIGIT1KeKX/cHZuvzwXulbkrY4ItU0xsAnj8Vs66gQLpwuIx0raTW.','Sellusi Sales Team','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','sellusi.com/s/sales',5000,'sellusi.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1),
	('siak@sikampus.com','{SHA512-CRYPT}$6$tV5TQ2IYi6gRVKSO$PamG/CGYsqk/VAnfeQ50eKAWBDMZ1uqE7UUr8WQg6Iuor0w93/8J4IYS4K323Wwc2lqpp.umPf/rH.abNBCIH1','SIAK','en_US','','','','','','0001-01-01','maildir','Maildir','/var/vmail','vmail1','sikampus.com/s/siak/',2048,'sikampus.com','','','normal','',0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'y','y','y',NULL,NULL,NULL,'1970-01-01 01:01:01','1970-01-01 01:01:01','1970-01-01 01:01:01','9999-12-31 00:00:00',1);

/*!40000 ALTER TABLE `mailbox` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table maillist_owners
# ------------------------------------------------------------

DROP TABLE IF EXISTS `maillist_owners`;

CREATE TABLE `maillist_owners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL DEFAULT '',
  `owner` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `dest_domain` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`,`owner`),
  KEY `owner` (`owner`),
  KEY `domain` (`domain`),
  KEY `dest_domain` (`dest_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table maillists
# ------------------------------------------------------------

DROP TABLE IF EXISTS `maillists`;

CREATE TABLE `maillists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `transport` varchar(255) NOT NULL DEFAULT '',
  `accesspolicy` varchar(30) NOT NULL DEFAULT '',
  `maxmsgsize` bigint(20) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `mlid` varchar(36) NOT NULL DEFAULT '',
  `is_newsletter` tinyint(1) NOT NULL DEFAULT 0,
  `settings` text DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`),
  UNIQUE KEY `mlid` (`mlid`),
  KEY `is_newsletter` (`is_newsletter`),
  KEY `domain` (`domain`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table moderators
# ------------------------------------------------------------

DROP TABLE IF EXISTS `moderators`;

CREATE TABLE `moderators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL DEFAULT '',
  `moderator` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `dest_domain` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`,`moderator`),
  KEY `domain` (`domain`),
  KEY `dest_domain` (`dest_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table recipient_bcc_domain
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recipient_bcc_domain`;

CREATE TABLE `recipient_bcc_domain` (
  `domain` varchar(255) NOT NULL DEFAULT '',
  `bcc_address` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`domain`),
  KEY `bcc_address` (`bcc_address`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table recipient_bcc_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recipient_bcc_user`;

CREATE TABLE `recipient_bcc_user` (
  `username` varchar(255) NOT NULL DEFAULT '',
  `bcc_address` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`username`),
  KEY `bcc_address` (`bcc_address`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table sender_bcc_domain
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sender_bcc_domain`;

CREATE TABLE `sender_bcc_domain` (
  `domain` varchar(255) NOT NULL DEFAULT '',
  `bcc_address` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`domain`),
  KEY `bcc_address` (`bcc_address`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table sender_bcc_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sender_bcc_user`;

CREATE TABLE `sender_bcc_user` (
  `username` varchar(255) NOT NULL DEFAULT '',
  `bcc_address` varchar(255) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `modified` datetime NOT NULL DEFAULT '1970-01-01 01:01:01',
  `expired` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`username`),
  KEY `bcc_address` (`bcc_address`),
  KEY `domain` (`domain`),
  KEY `expired` (`expired`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table sender_relayhost
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sender_relayhost`;

CREATE TABLE `sender_relayhost` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(255) NOT NULL DEFAULT '',
  `relayhost` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table share_folder
# ------------------------------------------------------------

DROP TABLE IF EXISTS `share_folder`;

CREATE TABLE `share_folder` (
  `from_user` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `to_user` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `dummy` char(1) DEFAULT NULL,
  PRIMARY KEY (`from_user`,`to_user`),
  KEY `from_user` (`from_user`),
  KEY `to_user` (`to_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table used_quota
# ------------------------------------------------------------

DROP TABLE IF EXISTS `used_quota`;

CREATE TABLE `used_quota` (
  `username` varchar(255) NOT NULL,
  `bytes` bigint(20) NOT NULL DEFAULT 0,
  `messages` bigint(20) NOT NULL DEFAULT 0,
  `domain` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`username`),
  KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `used_quota` WRITE;
/*!40000 ALTER TABLE `used_quota` DISABLE KEYS */;

INSERT INTO `used_quota` (`username`, `bytes`, `messages`, `domain`)
VALUES
	('a.bayu@arenaloka.com',33654,7,'arenaloka.com'),
	('a.bayu@fatchcraft.com',126394,30,'fatchcraft.com'),
	('a.bayu@kandaga.com',256319085,12472,'kandaga.com'),
	('a.bayu@sikampus.com',3883553,191,'sikampus.com'),
	('bayu@kandaga.com',256319085,12472,'kandaga.com'),
	('cs@arenaloka.com',191484,13,'arenaloka.com'),
	('direktur@kriyasi.com',0,0,'kriyasi.com'),
	('fatchurohman@fatchcraft.com',15088,3,'fatchcraft.com'),
	('helpdesk@sikampus.com',379411,33,'sikampus.com'),
	('latifahmahbub@fatchcraft.com',572762,21,'fatchcraft.com'),
	('marketing@digitrainfitech.com',2593,1,'digitrainfitech.com'),
	('marketing@sikampus.com',672462,48,'sikampus.com'),
	('midtrans@kriyasi.com',375897,14,'kriyasi.com'),
	('postmaster@kandaga.com',7126965,725,'kandaga.com'),
	('sales@kandaga.com',31578,6,'kandaga.com'),
	('sales@sellusi.com',494799,11,'sellusi.com'),
	('siak@digitrainfitech.com',7231,1,'digitrainfitech.com'),
	('siak@sikampus.com',1470786,169,'sikampus.com');

/*!40000 ALTER TABLE `used_quota` ENABLE KEYS */;
UNLOCK TABLES;

DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`root`@`localhost` */ /*!50003 TRIGGER `used_quota_before_insert` BEFORE INSERT ON `used_quota` FOR EACH ROW BEGIN
        SET NEW.domain = SUBSTRING_INDEX(NEW.username, '@', -1);
    END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
