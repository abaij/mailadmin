# iRedMail `vmail` schema, extracted from the production dump.
# Used to build an empty database for the test suite. iRedMail owns these
# tables in production -- this file is a reference, not a migration.

SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO,ALLOW_INVALID_DATES';
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

DELIMITER ;;
/*!50003 CREATE */ /*!50003 TRIGGER `used_quota_before_insert` BEFORE INSERT ON `used_quota` FOR EACH ROW BEGIN
        SET NEW.domain = SUBSTRING_INDEX(NEW.username, '@', -1);
    END */;;
DELIMITER ;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;