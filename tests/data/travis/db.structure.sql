-- phpMyAdmin SQL Dump
-- version 4.0.6deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 28, 2015 at 08:07 AM
-- Server version: 5.5.39-MariaDB-1~saucy-log
-- PHP Version: 5.5.3-1ubuntu2.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `sparta`
--
CREATE DATABASE IF NOT EXISTS `sparta` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `sparta`;

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `FixLastPostID`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `FixLastPostID`()
BEGIN
DECLARE done INT DEFAULT 0;
DECLARE temp_post_id INT;
DECLARE temp_topic_id INT;
DECLARE result varchar(4000);
DECLARE cur1 CURSOR FOR SELECT post_id, topic_id FROM nuke_bbposts WHERE post_time IN (SELECT MAX(post_time) FROM nuke_bbposts GROUP BY topic_id) ORDER BY topic_id DESC;

DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

OPEN cur1;

REPEAT
FETCH cur1 INTO temp_post_id, temp_topic_id;
IF NOT done THEN
UPDATE nuke_bbtopics SET topic_last_post_id = temp_post_id WHERE topic_id = temp_topic_id;
END IF;
UNTIL done END REPEAT;

CLOSE cur1;
END$$

DROP PROCEDURE IF EXISTS `geolocation`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `geolocation`(IN location_lat double, IN location_lon double, IN dist int, IN max int)
BEGIN
DECLARE lon1 float;
DECLARE lon2 float;
DECLARE lat1 float;
DECLARE lat2 float;

SET SQL_SELECT_LIMIT = max;


SET lon1 = location_lon-dist/abs(cos(radians(location_lat))*69);
SET lon2 = location_lon+dist/abs(cos(radians(location_lat))*69);
SET lat1 = location_lat-(dist/69); 
SET lat2 = location_lat+(dist/69); 



SELECT location.*, 3956 * 2 * ASIN(SQRT(POWER(SIN((location_lat - location.lat) * pi() / 180 / 2), 2) + COS(location_lat * pi() / 180) * COS(location.lat * pi() / 180) * POWER(SIN((location_lon - location.long) * pi() / 180 / 2), 2))) AS distance 
FROM location 
WHERE location.long BETWEEN lon1 AND lon2
AND location.lat BETWEEN lat1 AND lat2
HAVING distance < dist
ORDER BY distance;

SET SQL_SELECT_LIMIT = default;

END$$

DROP PROCEDURE IF EXISTS `geophotos`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `geophotos`(IN location_id int, IN dist int, IN max int)
BEGIN
DECLARE location_lon double;
DECLARE location_lat double;
DECLARE lon1 float;
DECLARE lon2 float;
DECLARE lat1 float;
DECLARE lat2 float;

SET SQL_SELECT_LIMIT = max;


SELECT location.lat, location.long INTO location_lat, location_lon FROM location WHERE location.id = location_id LIMIT 1;


SET lon1 = location_lon-dist/abs(cos(radians(location_lat))*69);
SET lon2 = location_lon+dist/abs(cos(radians(location_lat))*69);
SET lat1 = location_lat-(dist/69); 
SET lat2 = location_lat+(dist/69); 



SELECT flickr_geodata.*, 3956 * 2 * ASIN(SQRT(POWER(SIN((location.lat - flickr_geodata.lat) * pi() / 180 / 2), 2) + COS(location.lat * pi() / 180) * COS(flickr_geodata.lat * pi() / 180) * POWER(SIN((location.long - flickr_geodata.lon) * pi() / 180 / 2), 2))) AS distance 
FROM flickr_geodata, location 
WHERE location.id = location_id
AND flickr_geodata.lon BETWEEN lon1 AND lon2
AND flickr_geodata.lat BETWEEN lat1 AND lat2
HAVING distance < dist
ORDER BY distance;

SET SQL_SELECT_LIMIT = default;

END$$

DROP PROCEDURE IF EXISTS `latlngphotos`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `latlngphotos`(IN lat double, IN lon double, IN dist int, IN max int)
BEGIN

DECLARE lon1 float;
DECLARE lon2 float;
DECLARE lat1 float;
DECLARE lat2 float;

SET SQL_SELECT_LIMIT = max;

SET lon1 = lon-dist/abs(cos(radians(lat))*69);
SET lon2 = lon+dist/abs(cos(radians(lat))*69);
SET lat1 = lat-(dist/69); 
SET lat2 = lat+(dist/69); 

SELECT flickr_geodata.*, 3956 * 2 * ASIN(SQRT(POWER(SIN((lat - flickr_geodata.lat) * pi() / 180 / 2), 2) + COS(lat * pi() / 180) * COS(flickr_geodata.lat * pi() / 180) * POWER(SIN((lon - flickr_geodata.lon) * pi() / 180 / 2), 2))) AS distance 
FROM flickr_geodata
WHERE flickr_geodata.lon BETWEEN lon1 AND lon2
AND flickr_geodata.lat BETWEEN lat1 AND lat2
HAVING distance < dist
ORDER BY distance;

SET SQL_SELECT_LIMIT = default;

END$$

DROP PROCEDURE IF EXISTS `newscounters`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `newscounters`()
BEGIN
UPDATE nuke_stories SET weeklycounter = 0;
END$$

DROP PROCEDURE IF EXISTS `PopulateLocoClass`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `PopulateLocoClass`(IN loco_number VARCHAR(8), IN loco_number_last VARCHAR(8), IN loco_class_id INT, IN loco_gauge_id INT, IN loco_status_id INT, IN loco_manufacturer_id INT, IN prefix TEXT)
BEGIN

simple_loop: LOOP

SET @num_length := CHAR_LENGTH(loco_number);

INSERT INTO `sparta`.`loco_unit` (`loco_id`, `loco_num`, `loco_name`, `loco_gauge`, `loco_gauge_id`, `loco_status_id`, `class_id`, `owner_id`, `operator_id`, `date_added`, `date_modified`, `entered_service`, `withdrawn`, `builders_number`, `photo_id`, `manufacturer_id`) VALUES (NULL, CONCAT(prefix, loco_number), '', '', loco_gauge_id, loco_status_id, loco_class_id, '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '0', '0', '', '0', loco_manufacturer_id);

SET loco_number = lpad(loco_number + 1, @num_length, 0);

IF loco_number > loco_number_last THEN
LEAVE simple_loop;
END IF;

END LOOP simple_loop;
END$$

DROP PROCEDURE IF EXISTS `PopulateLocoOrgs`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `PopulateLocoOrgs`(IN LOCO_CLASS_ID INT, IN LOCO_OPERATOR_ID INT, IN LOCO_LINK_WEIGHT INT, IN LOCO_LINK_TYPE INT)
BEGIN

INSERT INTO `sparta`.`loco_org_link` (`loco_id`, `operator_id`, `link_type`, `link_weight`) SELECT `loco_id`, LOCO_OPERATOR_ID, LOCO_LINK_TYPE, LOCO_LINK_WEIGHT FROM `sparta`.`loco_unit` WHERE `class_id` = LOCO_CLASS_ID AND `loco_id` NOT IN (SELECT `loco_id` FROM `sparta`.`loco_org_link` WHERE `operator_id` = LOCO_OPERATOR_ID AND `link_type` = LOCO_LINK_TYPE);

END$$

DROP PROCEDURE IF EXISTS `update_viewed_thread`$$
CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `update_viewed_thread`(IN `val_topic_id` INT, IN `val_user_id` INT)
BEGIN
SELECT SQL_CALC_FOUND_ROWS topic_id, user_id FROM viewed_threads WHERE topic_id = val_topic_id AND user_id = val_user_id;

IF FOUND_ROWS() = 0 THEN
INSERT INTO viewed_threads (topic_id, user_id, time) VALUES (val_topic_id, val_user_id, CURRENT_TIMESTAMP());
ELSE
UPDATE viewed_threads SET time = CURRENT_TIMESTAMP() WHERE topic_id = val_topic_id AND user_id = val_user_id;
END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `api`
--

DROP TABLE IF EXISTS `api`;
CREATE TABLE IF NOT EXISTS `api` (
  `api_key` varchar(128) NOT NULL,
  `api_secret` varchar(128) NOT NULL,
  `api_name` varchar(128) NOT NULL,
  `api_date` int(10) NOT NULL,
  `api_active` tinyint(1) NOT NULL,
  `user_id` int(10) NOT NULL,
  UNIQUE KEY `api_key` (`api_key`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `asset`
--

DROP TABLE IF EXISTS `asset`;
CREATE TABLE IF NOT EXISTS `asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL,
  `type_id` int(11) NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `asset_bak`
--

DROP TABLE IF EXISTS `asset_bak`;
CREATE TABLE IF NOT EXISTS `asset_bak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) DEFAULT NULL,
  `namespace` varchar(256) NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`,`date`,`user_id`),
  KEY `loco_id` (`namespace_key`),
  KEY `namespace` (`namespace`(255)),
  KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `asset_link`
--

DROP TABLE IF EXISTS `asset_link`;
CREATE TABLE IF NOT EXISTS `asset_link` (
  `asset_link_id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `namespace` varchar(256) NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`asset_link_id`),
  KEY `type_id` (`date`,`user_id`),
  KEY `loco_id` (`namespace_key`),
  KEY `namespace` (`namespace`(255)),
  KEY `asset_id` (`asset_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `asset_type`
--

DROP TABLE IF EXISTS `asset_type`;
CREATE TABLE IF NOT EXISTS `asset_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `type` enum('video','photo','website','document','diagram') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `bancontrol`
--

DROP TABLE IF EXISTS `bancontrol`;
CREATE TABLE IF NOT EXISTS `bancontrol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `ban_active` tinyint(1) NOT NULL,
  `ban_time` int(12) NOT NULL,
  `ban_expire` int(12) NOT NULL,
  `ban_reason` text NOT NULL,
  `banned_by` int(12) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ban_active` (`ban_active`),
  KEY `banned_by` (`banned_by`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ban_domains`
--

DROP TABLE IF EXISTS `ban_domains`;
CREATE TABLE IF NOT EXISTS `ban_domains` (
  `domain_id` int(12) NOT NULL AUTO_INCREMENT,
  `domain_name` varchar(256) NOT NULL,
  `ban_date` int(12) NOT NULL,
  PRIMARY KEY (`domain_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `chronicle_item`
--

DROP TABLE IF EXISTS `chronicle_item`;
CREATE TABLE IF NOT EXISTS `chronicle_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  `type_id` int(11) NOT NULL,
  `blurb` text NOT NULL,
  `text` text NOT NULL,
  `point` point NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '45',
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`type_id`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `chronicle_link`
--

DROP TABLE IF EXISTS `chronicle_link`;
CREATE TABLE IF NOT EXISTS `chronicle_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `module` varchar(12) NOT NULL,
  `object` varchar(64) NOT NULL,
  `object_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`,`module`,`object`,`object_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `chronicle_type`
--

DROP TABLE IF EXISTS `chronicle_type`;
CREATE TABLE IF NOT EXISTS `chronicle_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grouping` enum('Locos','Locations','Other') NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `grouping` (`grouping`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL,
  `key` varchar(256) NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` varchar(2048) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `download_categories`
--

DROP TABLE IF EXISTS `download_categories`;
CREATE TABLE IF NOT EXISTS `download_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_title` varchar(50) NOT NULL DEFAULT '',
  `category_description` mediumtext NOT NULL,
  `parentid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `download_hits`
--

DROP TABLE IF EXISTS `download_hits`;
CREATE TABLE IF NOT EXISTS `download_hits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `download_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `remote_addr` text,
  PRIMARY KEY (`id`),
  KEY `download_id` (`download_id`,`date`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `download_items`
--

DROP TABLE IF EXISTS `download_items`;
CREATE TABLE IF NOT EXISTS `download_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '',
  `filepath` varchar(2048) NOT NULL,
  `filename` varchar(512) DEFAULT NULL,
  `mime` varchar(512) NOT NULL,
  `description` text NOT NULL,
  `date` datetime DEFAULT NULL,
  `hits` int(11) NOT NULL DEFAULT '0',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `object_id` varchar(128) NOT NULL,
  `extra_data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `meta` longtext NOT NULL,
  `lat` double(16,13) NOT NULL,
  `lon` double(16,13) NOT NULL,
  `category_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `user_id` int(11) NOT NULL DEFAULT '45',
  PRIMARY KEY (`id`),
  KEY `organisation_id` (`organisation_id`),
  KEY `lat` (`lat`,`lon`),
  KEY `category_id` (`category_id`),
  KEY `slug` (`slug`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

DROP TABLE IF EXISTS `event_categories`;
CREATE TABLE IF NOT EXISTS `event_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `slug` varchar(16) NOT NULL,
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `event_dates`
--

DROP TABLE IF EXISTS `event_dates`;
CREATE TABLE IF NOT EXISTS `event_dates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  `meta` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL DEFAULT '45',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`,`date`,`start`),
  KEY `approved` (`status`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE IF NOT EXISTS `feedback` (
  `assigned_to` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(256) NOT NULL,
  `email` varchar(256) NOT NULL,
  `area` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`area`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_area`
--

DROP TABLE IF EXISTS `feedback_area`;
CREATE TABLE IF NOT EXISTS `feedback_area` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback_title` varchar(256) NOT NULL,
  PRIMARY KEY (`feedback_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_status`
--

DROP TABLE IF EXISTS `feedback_status`;
CREATE TABLE IF NOT EXISTS `feedback_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_cache`
--

DROP TABLE IF EXISTS `flickr_cache`;
CREATE TABLE IF NOT EXISTS `flickr_cache` (
  `request` char(35) NOT NULL,
  `response` longtext NOT NULL,
  `expiration` datetime NOT NULL,
  KEY `request` (`request`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=0;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_favourites`
--

DROP TABLE IF EXISTS `flickr_favourites`;
CREATE TABLE IF NOT EXISTS `flickr_favourites` (
  `photo_id` varchar(24) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  KEY `photo_id` (`photo_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_geodata`
--

DROP TABLE IF EXISTS `flickr_geodata`;
CREATE TABLE IF NOT EXISTS `flickr_geodata` (
  `photo_id` varchar(20) NOT NULL,
  `lat` decimal(11,8) NOT NULL,
  `lon` decimal(11,8) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `ownername` varchar(128) NOT NULL,
  `size0` varchar(512) NOT NULL,
  `size0_w` int(11) NOT NULL,
  `size0_h` int(11) NOT NULL,
  `size1` varchar(512) NOT NULL,
  `size1_w` int(11) NOT NULL,
  `size1_h` int(11) NOT NULL,
  `size2` varchar(512) NOT NULL,
  `size2_w` int(11) NOT NULL,
  `size2_h` int(11) NOT NULL,
  `size3` varchar(512) NOT NULL,
  `size3_w` int(11) NOT NULL,
  `size3_h` int(11) NOT NULL,
  `size4` varchar(512) NOT NULL,
  `size4_w` int(11) NOT NULL,
  `size4_h` int(11) NOT NULL,
  `size5` varchar(512) NOT NULL,
  `size5_w` int(11) NOT NULL,
  `size5_h` int(11) NOT NULL,
  `size6` varchar(512) DEFAULT NULL,
  `size6_w` int(11) NOT NULL DEFAULT '0',
  `size6_h` int(11) NOT NULL DEFAULT '0',
  `title` varchar(2048) DEFAULT NULL,
  `tags` mediumtext,
  `dateadded` int(11) DEFAULT NULL,
  `dateupload` int(11) DEFAULT NULL,
  `datetaken` int(11) DEFAULT NULL,
  `size7` varchar(512) DEFAULT NULL,
  `size7_w` int(11) NOT NULL DEFAULT '0',
  `size7_h` int(11) NOT NULL DEFAULT '0',
  `size8` varchar(512) DEFAULT NULL,
  `size8_w` int(11) NOT NULL DEFAULT '0',
  `size8_h` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`photo_id`),
  KEY `lat` (`lat`),
  KEY `lon` (`lon`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=0;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_rating`
--

DROP TABLE IF EXISTS `flickr_rating`;
CREATE TABLE IF NOT EXISTS `flickr_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `photo_id` (`photo_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `fwlink`
--

DROP TABLE IF EXISTS `fwlink`;
CREATE TABLE IF NOT EXISTS `fwlink` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL,
  `title` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_mig_album`
--

DROP TABLE IF EXISTS `gallery_mig_album`;
CREATE TABLE IF NOT EXISTS `gallery_mig_album` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `title` text NOT NULL,
  `parent` varchar(128) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `meta` longtext NOT NULL,
  `owner` varchar(32) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `featured_photo` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `name` (`name`,`parent_id`),
  KEY `parent_id` (`parent_id`),
  KEY `owner_2` (`owner`,`owner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_mig_image`
--

DROP TABLE IF EXISTS `gallery_mig_image`;
CREATE TABLE IF NOT EXISTS `gallery_mig_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `album_id` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  `meta` longtext NOT NULL,
  `date_taken` datetime NOT NULL,
  `date_uploaded` datetime NOT NULL,
  `path` text NOT NULL,
  `title` text NOT NULL,
  `hidden` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `album_id` (`album_id`,`owner`),
  KEY `date_taken` (`date_taken`,`date_uploaded`),
  KEY `hidden` (`hidden`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `geoplace`
--

DROP TABLE IF EXISTS `geoplace`;
CREATE TABLE IF NOT EXISTS `geoplace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_code` varchar(4) NOT NULL,
  `country_name` text NOT NULL,
  `region_code` varchar(10) DEFAULT NULL,
  `region_name` text,
  `neighbourhood` varchar(32) DEFAULT NULL,
  `point` point NOT NULL,
  `timezone` text NOT NULL,
  `bb_southwest` point NOT NULL,
  `bb_northeast` point NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_code` (`country_code`,`region_code`),
  SPATIAL KEY `point` (`point`)
) ENGINE=Aria  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=0 TRANSACTIONAL=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `geoplace_forecast`
--

DROP TABLE IF EXISTS `geoplace_forecast`;
CREATE TABLE IF NOT EXISTS `geoplace_forecast` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `geoplace` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `date` date NOT NULL,
  `min` int(11) NOT NULL,
  `max` int(11) NOT NULL,
  `weather` text NOT NULL,
  `icon` text,
  PRIMARY KEY (`id`),
  KEY `geoplace` (`geoplace`,`expires`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `glossary`
--

DROP TABLE IF EXISTS `glossary`;
CREATE TABLE IF NOT EXISTS `glossary` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `type` enum('acronym','term','code','station','slang') NOT NULL DEFAULT 'term',
  `short` varchar(32) DEFAULT NULL,
  `full` text,
  `example` text NOT NULL,
  `date` datetime NOT NULL,
  `author` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `slug` varchar(22) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `date` (`date`),
  KEY `author` (`author`),
  KEY `status` (`status`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `idea_categories`
--

DROP TABLE IF EXISTS `idea_categories`;
CREATE TABLE IF NOT EXISTS `idea_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL,
  `slug` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `idea_ideas`
--

DROP TABLE IF EXISTS `idea_ideas`;
CREATE TABLE IF NOT EXISTS `idea_ideas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  `slug` varchar(32) NOT NULL,
  `description` text NOT NULL,
  `votes` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `status` tinyint(2) NOT NULL DEFAULT '1',
  `forum_thread_id` int(11) NOT NULL DEFAULT '0',
  `redmine_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`,`votes`),
  KEY `author` (`author`),
  KEY `category_id` (`category_id`),
  KEY `date` (`date`),
  KEY `status` (`status`),
  KEY `forum_thread_id` (`forum_thread_id`),
  KEY `redmine_id` (`redmine_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `idea_votes`
--

DROP TABLE IF EXISTS `idea_votes`;
CREATE TABLE IF NOT EXISTS `idea_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idea_id` (`idea_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
CREATE TABLE IF NOT EXISTS `image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flags` int(4) NOT NULL,
  `provider` enum('flickr','westonlangford','rpoldgallery','picasaweb','vicsig') NOT NULL,
  `photo_id` bigint(11) NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `captured` datetime DEFAULT NULL,
  `lat` double(16,13) NOT NULL,
  `lon` double(16,13) NOT NULL,
  `meta` longtext NOT NULL,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `hits_today` int(11) NOT NULL,
  `hits_weekly` int(11) NOT NULL,
  `hits_overall` int(11) NOT NULL,
  `geoplace` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `image_source` (`provider`,`photo_id`,`modified`),
  KEY `lat` (`lat`,`lon`),
  KEY `hits_today` (`hits_today`,`hits_weekly`,`hits_overall`),
  KEY `geoplace` (`geoplace`),
  KEY `user_id` (`user_id`),
  KEY `hidden` (`hidden`),
  KEY `captured` (`captured`),
  KEY `flags` (`flags`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_camera`
--

DROP TABLE IF EXISTS `image_camera`;
CREATE TABLE IF NOT EXISTS `image_camera` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `make` varchar(32) NOT NULL,
  `model` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `make` (`make`,`model`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_collection`
--

DROP TABLE IF EXISTS `image_collection`;
CREATE TABLE IF NOT EXISTS `image_collection` (

  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `slug` varchar(16) NOT NULL,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`,`created`,`modified`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_competition`
--

DROP TABLE IF EXISTS `image_competition`;
CREATE TABLE IF NOT EXISTS `image_competition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `theme` text NOT NULL,
  `description` text NOT NULL,
  `slug` varchar(64) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `voting_date_open` datetime NOT NULL,
  `voting_date_close` datetime NOT NULL,
  `submissions_date_open` datetime NOT NULL,
  `submissions_date_close` datetime NOT NULL,
  `author` int(11) NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`,`status`,`voting_date_open`,`voting_date_close`,`author`),
  KEY `submissions_date_open` (`submissions_date_open`,`submissions_date_close`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_competition_submissions`
--

DROP TABLE IF EXISTS `image_competition_submissions`;
CREATE TABLE IF NOT EXISTS `image_competition_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competition_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `meta` text NOT NULL,
  `date_added` datetime NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `competition_id` (`competition_id`,`user_id`,`image_id`,`date_added`,`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_competition_votes`
--

DROP TABLE IF EXISTS `image_competition_votes`;
CREATE TABLE IF NOT EXISTS `image_competition_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competition_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `competition_id` (`competition_id`,`user_id`,`image_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_exif`
--

DROP TABLE IF EXISTS `image_exif`;
CREATE TABLE IF NOT EXISTS `image_exif` (
  `image_id` int(11) NOT NULL,
  `camera_id` int(11) NOT NULL DEFAULT '0',
  `lens_id` int(11) NOT NULL DEFAULT '0',
  `lens_sn_id` int(11) NOT NULL DEFAULT '0',
  `aperture` double NOT NULL DEFAULT '0',
  `exposure_id` int(11) NOT NULL DEFAULT '0',
  `exposure_program_id` int(11) NOT NULL DEFAULT '0',
  `focal_length` int(11) NOT NULL DEFAULT '0',
  `iso` int(11) NOT NULL DEFAULT '0',
  `white_balance_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`image_id`),
  KEY `camera_id` (`camera_id`,`lens_id`,`aperture`,`exposure_id`,`exposure_program_id`,`focal_length`,`iso`,`white_balance_id`),
  KEY `lens_sn_id` (`lens_sn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_exposure`
--

DROP TABLE IF EXISTS `image_exposure`;
CREATE TABLE IF NOT EXISTS `image_exposure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exposure` varchar(12) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `exposure` (`exposure`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_exposure_program`
--

DROP TABLE IF EXISTS `image_exposure_program`;
CREATE TABLE IF NOT EXISTS `image_exposure_program` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `program` (`program`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_flags`
--

DROP TABLE IF EXISTS `image_flags`;
CREATE TABLE IF NOT EXISTS `image_flags` (
  `image_id` int(11) NOT NULL,
  `published` bit(1) NOT NULL DEFAULT b'0',
  `screened` bit(1) NOT NULL DEFAULT b'0',
  `screened_by` int(11) NOT NULL DEFAULT '0',
  `screened_on` datetime DEFAULT NULL,
  `screened_pick` bit(1) NOT NULL DEFAULT b'0',
  `rejected` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`image_id`),
  KEY `published` (`published`,`screened`,`screened_by`,`screened_on`,`rejected`),
  KEY `screened_pick` (`screened_pick`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_flags_skip`
--

DROP TABLE IF EXISTS `image_flags_skip`;
CREATE TABLE IF NOT EXISTS `image_flags_skip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_id` (`image_id`,`user_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_lens`
--

DROP TABLE IF EXISTS `image_lens`;
CREATE TABLE IF NOT EXISTS `image_lens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `model` (`model`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_lens_sn`
--

DROP TABLE IF EXISTS `image_lens_sn`;
CREATE TABLE IF NOT EXISTS `image_lens_sn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sn` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sn` (`sn`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_link`
--

DROP TABLE IF EXISTS `image_link`;
CREATE TABLE IF NOT EXISTS `image_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` int(11) NOT NULL,
  `namespace` enum('railpage.locos.loco','railpage.locos.class','railpage.locations.location','railpage.locos.liveries.livery','railpage.images.collection') NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `ignored` tinyint(1) NOT NULL DEFAULT '0',
  `added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `namespace` (`namespace`,`namespace_key`,`ignored`),
  KEY `image_id` (`image_id`),
  KEY `added` (`added`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_position`
--

DROP TABLE IF EXISTS `image_position`;
CREATE TABLE IF NOT EXISTS `image_position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(20) NOT NULL,
  `image_type` enum('flickr','asset') NOT NULL,
  `namespace` varchar(128) DEFAULT NULL,
  `namespace_key` varchar(32) DEFAULT NULL,
  `position_x` varchar(5) NOT NULL,
  `position_y` varchar(8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `image_id` (`image_id`,`image_type`,`namespace`,`namespace_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Define an offset position for an image. Useful for loco cover photos.' ;

-- --------------------------------------------------------

--
-- Table structure for table `image_software`
--

DROP TABLE IF EXISTS `image_software`;
CREATE TABLE IF NOT EXISTS `image_software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `version` double NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `version` (`version`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_weekly`
--

DROP TABLE IF EXISTS `image_weekly`;
CREATE TABLE IF NOT EXISTS `image_weekly` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` int(11) NOT NULL,
  `datefrom` date NOT NULL,
  `added_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `image_id` (`image_id`,`datefrom`,`added_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_whitebalance`
--

DROP TABLE IF EXISTS `image_whitebalance`;
CREATE TABLE IF NOT EXISTS `image_whitebalance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `whitebalance` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `whitebalance` (`whitebalance`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `jn_applications`
--

DROP TABLE IF EXISTS `jn_applications`;
CREATE TABLE IF NOT EXISTS `jn_applications` (
  `jn_application_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for this job application',
  `jn_job_id` int(11) NOT NULL COMMENT 'Job ID',
  `user_id` int(11) NOT NULL COMMENT 'User ID',
  `jn_application_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of application',
  PRIMARY KEY (`jn_application_id`),
  KEY `jn_job_id` (`jn_job_id`,`user_id`,`jn_application_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Railpage JobNet - Applications to advertised positions' ;

-- --------------------------------------------------------

--
-- Table structure for table `jn_classifications`
--

DROP TABLE IF EXISTS `jn_classifications`;
CREATE TABLE IF NOT EXISTS `jn_classifications` (
  `jn_classification_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of the job classification',
  `jn_classification_name` varchar(128) NOT NULL COMMENT 'Regular name for the classification',
  `jn_parent_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Parent jn_classification_id number',
  PRIMARY KEY (`jn_classification_id`),
  KEY `jn_parent_id` (`jn_parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `jn_jobs`
--

DROP TABLE IF EXISTS `jn_jobs`;
CREATE TABLE IF NOT EXISTS `jn_jobs` (
  `job_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique job ID',
  `reference_id` int(11) NOT NULL COMMENT 'The reference ID from the job poster',
  `job_title` varchar(1024) NOT NULL COMMENT 'Job title or name of position, eg "Trainee driver"',
  `organisation_id` int(11) NOT NULL COMMENT 'Link to the Organisations module',
  `job_location_id` int(11) NOT NULL COMMENT 'Location ID, eg Melbourne > South East',
  `job_description` text NOT NULL COMMENT 'Description of advertised position',
  `job_added` timestamp NULL DEFAULT NULL,
  `job_expiry` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date at which the job will be deleted',
  `job_classification_id` int(11) NOT NULL,
  `job_salary` double(14,2) NOT NULL,
  `job_special_cond` text NOT NULL COMMENT 'Special conditions; eg manual drivers license required',
  `job_duration` varchar(256) NOT NULL COMMENT 'Length of the advertised position if not ongoing',
  `job_thread_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Discussion thread ID',
  `job_urls` text NOT NULL,
  `conversions` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`job_id`),
  KEY `organisation_id` (`organisation_id`,`job_location_id`,`job_expiry`,`job_classification_id`,`job_salary`),
  KEY `job_thread_id` (`job_thread_id`),
  KEY `reference_id` (`reference_id`),
  KEY `job_added` (`job_added`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Railpage JobNet - Advertised positions' ;

-- --------------------------------------------------------

--
-- Table structure for table `jn_locations`
--

DROP TABLE IF EXISTS `jn_locations`;
CREATE TABLE IF NOT EXISTS `jn_locations` (
  `jn_location_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique job location ID',
  `jn_location_name` varchar(128) NOT NULL COMMENT 'Name of this location, eg: Melbourne > South East',
  `jn_parent_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Parent ID, eg Melbourne is the parent of Melbourne > South East',
  PRIMARY KEY (`jn_location_id`),
  KEY `jn_parent_id` (`jn_parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Railpage JobNet - Job locations' ;

-- --------------------------------------------------------

--
-- Table structure for table `loadstats`
--

DROP TABLE IF EXISTS `loadstats`;
CREATE TABLE IF NOT EXISTS `loadstats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `url` varchar(512) NOT NULL,
  `referrer` mediumtext NOT NULL,
  `ip_address` varchar(128) NOT NULL,
  `stat_loadtime` decimal(10,4) NOT NULL,
  `stat_dbqueries` int(11) NOT NULL,
  `stat_ram` decimal(10,2) NOT NULL,
  `stat_webload` decimal(3,2) NOT NULL,
  `stat_dbload` decimal(3,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
CREATE TABLE IF NOT EXISTS `location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lat` decimal(11,8) NOT NULL,
  `long` decimal(11,8) NOT NULL,
  `country` varchar(10) CHARACTER SET latin1 NOT NULL,
  `country_slug` varchar(8) NOT NULL,
  `region` varchar(20) CHARACTER SET latin1 NOT NULL,
  `region_slug` varchar(128) NOT NULL,
  `locality` varchar(128) CHARACTER SET latin1 NOT NULL,
  `neighbourhood` varchar(128) CHARACTER SET latin1 NOT NULL,
  `name` text CHARACTER SET latin1 NOT NULL,
  `desc` text CHARACTER SET latin1 NOT NULL,
  `topicid` int(11) NOT NULL,
  `zoom` tinyint(4) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `date_added` int(12) NOT NULL,
  `date_modified` int(12) NOT NULL,
  `user_id` int(10) DEFAULT NULL,
  `camera_id` int(11) NOT NULL,
  `slug` varchar(128) NOT NULL,
  `traffic` text NOT NULL,
  `environment` text NOT NULL,
  `directions_driving` text NOT NULL,
  `directions_parking` text NOT NULL,
  `directions_pt` text NOT NULL,
  `amenities` text NOT NULL,
  `geoplace` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country` (`country`,`region`),
  KEY `locality` (`locality`,`neighbourhood`),
  KEY `region` (`region`),
  KEY `camera_id` (`camera_id`),
  KEY `active` (`active`),
  KEY `date_modified` (`date_modified`),
  KEY `date_added` (`date_added`),
  KEY `topicid` (`topicid`),
  KEY `lat` (`lat`),
  KEY `long` (`long`),
  KEY `country_slug` (`country_slug`),
  KEY `region_slug` (`region_slug`),
  KEY `geoplace` (`geoplace`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations_like`
--

DROP TABLE IF EXISTS `locations_like`;
CREATE TABLE IF NOT EXISTS `locations_like` (
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  KEY `location_id` (`location_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `location_corrections`
--

DROP TABLE IF EXISTS `location_corrections`;
CREATE TABLE IF NOT EXISTS `location_corrections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comments` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_closed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `location_id` (`location_id`,`user_id`,`status`),
  KEY `date_added` (`date_added`,`date_closed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `location_date`
--

DROP TABLE IF EXISTS `location_date`;
CREATE TABLE IF NOT EXISTS `location_date` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `location_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`type_id`),
  KEY `location_id` (`location_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `location_datetypes`
--

DROP TABLE IF EXISTS `location_datetypes`;
CREATE TABLE IF NOT EXISTS `location_datetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_class`
--

DROP TABLE IF EXISTS `loco_class`;
CREATE TABLE IF NOT EXISTS `loco_class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL DEFAULT '0',
  `source_id` int(11) NOT NULL,
  `download_id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `desc` text NOT NULL,
  `introduced` varchar(128) NOT NULL,
  `length` varchar(256) NOT NULL,
  `axle_load` varchar(256) NOT NULL,
  `weight` varchar(256) NOT NULL,
  `tractive_effort` varchar(256) NOT NULL,
  `Model` varchar(256) NOT NULL,
  `wheel_arrangement_id` int(11) NOT NULL,
  `loco_type_id` int(11) NOT NULL,
  `manufacturer_id` int(11) NOT NULL,
  `flickr_tag` varchar(128) NOT NULL,
  `flickr_image_id` varchar(256) NOT NULL,
  `date_added` int(11) NOT NULL,
  `date_modified` int(11) NOT NULL,
  `slug` varchar(128) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'AU',
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `loco_type_id` (`loco_type_id`),
  KEY `manufacturer_id` (`manufacturer_id`),
  KEY `Model` (`Model`(255)),
  KEY `asset_id` (`asset_id`),
  KEY `country` (`country`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_date_type`
--

DROP TABLE IF EXISTS `loco_date_type`;
CREATE TABLE IF NOT EXISTS `loco_date_type` (
  `loco_date_id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_date_text` text NOT NULL,
  PRIMARY KEY (`loco_date_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_gauge`
--

DROP TABLE IF EXISTS `loco_gauge`;
CREATE TABLE IF NOT EXISTS `loco_gauge` (
  `gauge_id` int(11) NOT NULL AUTO_INCREMENT,
  `gauge_name` varchar(64) CHARACTER SET latin1 NOT NULL,
  `gauge_imperial` varchar(64) CHARACTER SET latin1 NOT NULL,
  `gauge_metric` varchar(64) CHARACTER SET latin1 NOT NULL,
  `slug` varchar(12) NOT NULL,
  PRIMARY KEY (`gauge_id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_groups`
--

DROP TABLE IF EXISTS `loco_groups`;
CREATE TABLE IF NOT EXISTS `loco_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(256) CHARACTER SET latin1 NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `active` (`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_groups_members`
--

DROP TABLE IF EXISTS `loco_groups_members`;
CREATE TABLE IF NOT EXISTS `loco_groups_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `loco_unit_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `loco_unit_id` (`loco_unit_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_hits`
--

DROP TABLE IF EXISTS `loco_hits`;
CREATE TABLE IF NOT EXISTS `loco_hits` (
  `loco_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(128) NOT NULL,
  KEY `loco_id` (`loco_id`,`class_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_link`
--

DROP TABLE IF EXISTS `loco_link`;
CREATE TABLE IF NOT EXISTS `loco_link` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_id_a` int(11) NOT NULL,
  `loco_id_b` int(11) NOT NULL,
  `link_type_id` int(11) NOT NULL,
  PRIMARY KEY (`link_id`),
  KEY `loco_id_a` (`loco_id_a`),
  KEY `loco_id_b` (`loco_id_b`),
  KEY `link_type_id` (`link_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_link_type`
--

DROP TABLE IF EXISTS `loco_link_type`;
CREATE TABLE IF NOT EXISTS `loco_link_type` (
  `link_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `link_type_name` varchar(128) NOT NULL,
  PRIMARY KEY (`link_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_livery`
--

DROP TABLE IF EXISTS `loco_livery`;
CREATE TABLE IF NOT EXISTS `loco_livery` (
  `livery_id` int(11) NOT NULL AUTO_INCREMENT,
  `livery` varchar(1024) NOT NULL,
  `introduced` varchar(256) NOT NULL,
  `withdrawn` varchar(256) NOT NULL,
  `superseded_by` int(11) NOT NULL DEFAULT '0',
  `supersedes` int(11) NOT NULL DEFAULT '0',
  `photo_id` varchar(2048) NOT NULL,
  `region` varchar(12) NOT NULL,
  `country` varchar(12) NOT NULL DEFAULT 'AU',
  PRIMARY KEY (`livery_id`),
  KEY `superseded_by` (`superseded_by`),
  KEY `supersedes` (`supersedes`),
  KEY `region` (`region`),
  KEY `country` (`country`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_manufacturer`
--

DROP TABLE IF EXISTS `loco_manufacturer`;
CREATE TABLE IF NOT EXISTS `loco_manufacturer` (
  `manufacturer_id` int(11) NOT NULL AUTO_INCREMENT,
  `manufacturer_name` varchar(256) NOT NULL,
  `manufacturer_desc` text NOT NULL,
  `slug` varchar(32) NOT NULL,
  PRIMARY KEY (`manufacturer_id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_notes`
--

DROP TABLE IF EXISTS `loco_notes`;
CREATE TABLE IF NOT EXISTS `loco_notes` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_date` int(11) NOT NULL,
  `note_text` text NOT NULL,
  PRIMARY KEY (`note_id`),
  KEY `loco_id` (`loco_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_org_link`
--

DROP TABLE IF EXISTS `loco_org_link`;
CREATE TABLE IF NOT EXISTS `loco_org_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_id` int(11) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `link_type` int(11) NOT NULL,
  `link_weight` int(11) NOT NULL,
  `link_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `loco_id` (`loco_id`),
  KEY `operator_id` (`operator_id`),
  KEY `link_type` (`link_type`),
  KEY `link_weight` (`link_weight`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_org_link_type`
--

DROP TABLE IF EXISTS `loco_org_link_type`;
CREATE TABLE IF NOT EXISTS `loco_org_link_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_status`
--

DROP TABLE IF EXISTS `loco_status`;
CREATE TABLE IF NOT EXISTS `loco_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_type`
--

DROP TABLE IF EXISTS `loco_type`;
CREATE TABLE IF NOT EXISTS `loco_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `slug` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit`
--

DROP TABLE IF EXISTS `loco_unit`;
CREATE TABLE IF NOT EXISTS `loco_unit` (
  `loco_id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_num` varchar(12) NOT NULL,
  `loco_name` varchar(512) NOT NULL,
  `loco_gauge` varchar(128) NOT NULL,
  `loco_gauge_id` int(11) NOT NULL,
  `loco_status_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `date_added` int(11) NOT NULL,
  `date_modified` int(11) NOT NULL,
  `entered_service` int(11) NOT NULL,
  `withdrawn` int(11) NOT NULL,
  `builders_number` varchar(128) NOT NULL,
  `photo_id` varchar(512) NOT NULL,
  `asset_id` int(10) NOT NULL DEFAULT '0',
  `manufacturer_id` int(11) NOT NULL,
  `meta` longtext,
  PRIMARY KEY (`loco_id`),
  KEY `loco_gauge_id` (`loco_gauge_id`),
  KEY `loco_status_id` (`loco_status_id`),
  KEY `loco_num` (`loco_num`),
  KEY `manufacturer_id` (`manufacturer_id`),
  KEY `class_id` (`class_id`),
  KEY `asset_id` (`asset_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_corrections`
--

DROP TABLE IF EXISTS `loco_unit_corrections`;
CREATE TABLE IF NOT EXISTS `loco_unit_corrections` (
  `correction_id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_by` int(11) NOT NULL,
  `resolved_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `text` varchar(2048) NOT NULL,
  PRIMARY KEY (`correction_id`),
  KEY `loco_id` (`loco_id`),
  KEY `user_id` (`user_id`),
  KEY `class_id` (`class_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_date`
--

DROP TABLE IF EXISTS `loco_unit_date`;
CREATE TABLE IF NOT EXISTS `loco_unit_date` (
  `date_id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_unit_id` int(11) NOT NULL,
  `loco_date_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `timestamp` date NOT NULL,
  `date_end` date DEFAULT NULL,
  `text` mediumtext NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`date_id`),
  KEY `loco_unit_id` (`loco_unit_id`),
  KEY `date_id` (`loco_date_id`),
  KEY `timestamp` (`timestamp`),
  KEY `date_end` (`date_end`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_livery`
--

DROP TABLE IF EXISTS `loco_unit_livery`;
CREATE TABLE IF NOT EXISTS `loco_unit_livery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` enum('flickr') NOT NULL,
  `photo_id` bigint(20) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `livery_id` int(11) NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ignored` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `provider` (`provider`,`photo_id`,`loco_id`,`livery_id`),
  KEY `added` (`added`),
  KEY `ignored` (`ignored`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_source`
--

DROP TABLE IF EXISTS `loco_unit_source`;
CREATE TABLE IF NOT EXISTS `loco_unit_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_id` int(11) NOT NULL,
  `source_id` int(11) NOT NULL,
  `desc` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `loco_id` (`loco_id`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_api`
--

DROP TABLE IF EXISTS `log_api`;
CREATE TABLE IF NOT EXISTS `log_api` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `version` enum('1','2') NOT NULL,
  `resource` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `version` (`version`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_downloads`
--

DROP TABLE IF EXISTS `log_downloads`;
CREATE TABLE IF NOT EXISTS `log_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `download_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `download_id` (`download_id`,`date`,`ip`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_errors`
--

DROP TABLE IF EXISTS `log_errors`;
CREATE TABLE IF NOT EXISTS `log_errors` (
  `error_id` int(11) NOT NULL AUTO_INCREMENT,
  `error_text` mediumtext NOT NULL,
  `error_time` int(11) NOT NULL,
  `error_file` mediumtext NOT NULL,
  `error_line` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `error_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `trace` mediumtext NOT NULL,
  PRIMARY KEY (`error_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_general`
--

DROP TABLE IF EXISTS `log_general`;
CREATE TABLE IF NOT EXISTS `log_general` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(128) NOT NULL,
  `args` varchar(2048) NOT NULL,
  `key` varchar(12) NOT NULL,
  `value` varchar(12) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`timestamp`),
  KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_herrings`
--

DROP TABLE IF EXISTS `log_herrings`;
CREATE TABLE IF NOT EXISTS `log_herrings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `poster_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_locos`
--

DROP TABLE IF EXISTS `log_locos`;
CREATE TABLE IF NOT EXISTS `log_locos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `title` varchar(128) NOT NULL,
  `args` varchar(2048) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`timestamp`),
  KEY `loco_id` (`loco_id`,`class_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_logins`
--

DROP TABLE IF EXISTS `log_logins`;
CREATE TABLE IF NOT EXISTS `log_logins` (
  `login_id` int(11) NOT NULL AUTO_INCREMENT,
  `login_time` int(11) NOT NULL,
  `login_ip` varchar(256) NOT NULL,
  `login_hostname` varchar(512) NOT NULL,
  `user_id` int(11) NOT NULL,
  `server` varchar(32) NOT NULL,
  `device_hash` varchar(128) NOT NULL,
  PRIMARY KEY (`login_id`),
  KEY `user_id` (`user_id`),
  KEY `login_time` (`login_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_pageactivity`
--

DROP TABLE IF EXISTS `log_pageactivity`;
CREATE TABLE IF NOT EXISTS `log_pageactivity` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url` varchar(2048) NOT NULL,
  `pagetitle` varchar(2048) NOT NULL,
  `module` varchar(1024) NOT NULL,
  `hits` int(11) NOT NULL DEFAULT '0',
  `loggedin` int(11) NOT NULL DEFAULT '0',
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_staff`
--

DROP TABLE IF EXISTS `log_staff`;
CREATE TABLE IF NOT EXISTS `log_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(32) NOT NULL,
  `key_val` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(128) NOT NULL,
  `args` varchar(2048) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `timestamp` (`timestamp`),
  KEY `title` (`title`),
  KEY `key` (`key`,`key_val`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_useractivity`
--

DROP TABLE IF EXISTS `log_useractivity`;
CREATE TABLE IF NOT EXISTS `log_useractivity` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip` varchar(64) CHARACTER SET latin1 NOT NULL,
  `module_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `pagetitle` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `ip` (`ip`),
  KEY `module_id` (`module_id`,`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(10) NOT NULL AUTO_INCREMENT,
  `message_active` tinyint(1) NOT NULL DEFAULT '1',
  `message_text` varchar(512) NOT NULL,
  `message_title` varchar(64) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `object_ns` varchar(64) NOT NULL,
  `object_id` int(10) NOT NULL,
  `target_user` int(11) NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `message_active` (`message_active`),
  KEY `message_title` (`message_title`),
  KEY `date_start` (`date_start`,`date_end`),
  KEY `object_ns` (`object_ns`,`object_id`),
  KEY `target_user` (`target_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_viewed`
--

DROP TABLE IF EXISTS `messages_viewed`;
CREATE TABLE IF NOT EXISTS `messages_viewed` (
  `row_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  PRIMARY KEY (`row_id`),
  KEY `message_id` (`message_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter`
--

DROP TABLE IF EXISTS `newsletter`;
CREATE TABLE IF NOT EXISTS `newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` text NOT NULL,
  `publishdate` date NOT NULL,
  `status` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `publishdate` (`publishdate`,`status`),
  KEY `template_id` (`template_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_templates`
--

DROP TABLE IF EXISTS `newsletter_templates`;
CREATE TABLE IF NOT EXISTS `newsletter_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `html` text NOT NULL,
  `contenturl` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `news_feed`
--

DROP TABLE IF EXISTS `news_feed`;
CREATE TABLE IF NOT EXISTS `news_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `topics` text NOT NULL,
  `keywords` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` int(11) NOT NULL,
  `transport` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_sent` datetime NOT NULL,
  `subject` text,
  `body` longtext NOT NULL,
  `response` longtext NOT NULL COMMENT 'Response from the transport. Used for errors/debugging',
  `meta` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recipient` (`author`,`transport`,`status`,`date_queued`,`date_sent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Notifications queue, to prevent page blocking when sending emails etc' ;

-- --------------------------------------------------------

--
-- Table structure for table `notifications_recipients`
--

DROP TABLE IF EXISTS `notifications_recipients`;
CREATE TABLE IF NOT EXISTS `notifications_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `destination` text NOT NULL,
  `date_sent` datetime NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`,`user_id`,`date_sent`,`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_prefs`
--

DROP TABLE IF EXISTS `notification_prefs`;
CREATE TABLE IF NOT EXISTS `notification_prefs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'User ID that this preference belongs to',
  `notify_off` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Turn off notifications completely',
  `notify_topic_reply` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notify on topic reply',
  `notify_pm` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notify on new PM',
  `notify_job_apply` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notify when someone applies for an advertised job',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `notify_off` (`notify_off`),
  KEY `notify_topic_reply` (`notify_topic_reply`),
  KEY `notify_pm` (`notify_pm`),
  KEY `notify_job_apply` (`notify_job_apply`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='Notification email preferences' ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_rules`
--

DROP TABLE IF EXISTS `notification_rules`;
CREATE TABLE IF NOT EXISTS `notification_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namespace` varchar(256) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rule` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `namespace` (`namespace`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='Custom per-user notification rules' ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_sent`
--

DROP TABLE IF EXISTS `notification_sent`;
CREATE TABLE IF NOT EXISTS `notification_sent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `namespace` varchar(256) NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `namespace_value` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`,`user_id`,`namespace`(255),`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='Previously sent notifications' ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namespace` varchar(256) NOT NULL,
  `template` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `namespace` (`namespace`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='BBCode notification templates' ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_alliance`
--

DROP TABLE IF EXISTS `nuke_alliance`;
CREATE TABLE IF NOT EXISTS `nuke_alliance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrip` mediumtext COLLATE utf8_unicode_ci,
  `joined` int(11) NOT NULL DEFAULT '0',
  `uniquetoken` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `indexed` tinyint(1) NOT NULL DEFAULT '0',
  `imgsrc` mediumtext COLLATE utf8_unicode_ci,
  `url` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_authors`
--

DROP TABLE IF EXISTS `nuke_authors`;
CREATE TABLE IF NOT EXISTS `nuke_authors` (
  `aid` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pwd` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `counter` int(11) NOT NULL DEFAULT '0',
  `radminarticle` tinyint(2) NOT NULL DEFAULT '0',
  `radmintopic` tinyint(2) NOT NULL DEFAULT '0',
  `radminuser` tinyint(2) NOT NULL DEFAULT '0',
  `radminsurvey` tinyint(2) NOT NULL DEFAULT '0',
  `radminsection` tinyint(2) NOT NULL DEFAULT '0',
  `radminlink` tinyint(2) NOT NULL DEFAULT '0',
  `radminephem` tinyint(2) NOT NULL DEFAULT '0',
  `radminfaq` tinyint(2) NOT NULL DEFAULT '0',
  `radmindownload` tinyint(2) NOT NULL DEFAULT '0',
  `radminreviews` tinyint(2) NOT NULL DEFAULT '0',
  `radminnewsletter` tinyint(2) NOT NULL DEFAULT '0',
  `radminforum` tinyint(2) NOT NULL DEFAULT '0',
  `radmincontent` tinyint(2) NOT NULL DEFAULT '0',
  `radminency` tinyint(2) NOT NULL DEFAULT '0',
  `radmingroup` tinyint(2) NOT NULL DEFAULT '0',
  `radminsuper` tinyint(2) NOT NULL DEFAULT '1',
  `admlanguage` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`aid`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade`
--

DROP TABLE IF EXISTS `nuke_bbarcade`;
CREATE TABLE IF NOT EXISTS `nuke_bbarcade` (
  `arcade_name` varchar(255) NOT NULL DEFAULT '',
  `arcade_value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`arcade_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade_categories`
--

DROP TABLE IF EXISTS `nuke_bbarcade_categories`;
CREATE TABLE IF NOT EXISTS `nuke_bbarcade_categories` (
  `arcade_catid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `arcade_cattitle` varchar(100) NOT NULL DEFAULT '',
  `arcade_nbelmt` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `arcade_catorder` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `arcade_catauth` tinyint(2) NOT NULL DEFAULT '0',
  KEY `arcade_catid` (`arcade_catid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade_comments`
--

DROP TABLE IF EXISTS `nuke_bbarcade_comments`;
CREATE TABLE IF NOT EXISTS `nuke_bbarcade_comments` (
  `game_id` mediumint(8) NOT NULL DEFAULT '0',
  `comments_value` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade_fav`
--

DROP TABLE IF EXISTS `nuke_bbarcade_fav`;
CREATE TABLE IF NOT EXISTS `nuke_bbarcade_fav` (
  `order` mediumint(8) NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `game_id` mediumint(8) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbauth_access`
--

DROP TABLE IF EXISTS `nuke_bbauth_access`;
CREATE TABLE IF NOT EXISTS `nuke_bbauth_access` (
  `group_id` mediumint(8) NOT NULL DEFAULT '0',
  `forum_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `auth_view` tinyint(1) NOT NULL DEFAULT '0',
  `auth_read` tinyint(1) NOT NULL DEFAULT '0',
  `auth_post` tinyint(1) NOT NULL DEFAULT '0',
  `auth_reply` tinyint(1) NOT NULL DEFAULT '0',
  `auth_edit` tinyint(1) NOT NULL DEFAULT '0',
  `auth_delete` tinyint(1) NOT NULL DEFAULT '0',
  `auth_sticky` tinyint(1) NOT NULL DEFAULT '0',
  `auth_announce` tinyint(1) NOT NULL DEFAULT '0',
  `auth_vote` tinyint(1) NOT NULL DEFAULT '0',
  `auth_pollcreate` tinyint(1) NOT NULL DEFAULT '0',
  `auth_attachments` tinyint(1) NOT NULL DEFAULT '0',
  `auth_mod` tinyint(1) NOT NULL DEFAULT '0',
  KEY `group_id` (`group_id`),
  KEY `forum_id` (`forum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbauth_arcade_access`
--

DROP TABLE IF EXISTS `nuke_bbauth_arcade_access`;
CREATE TABLE IF NOT EXISTS `nuke_bbauth_arcade_access` (
  `group_id` mediumint(8) NOT NULL DEFAULT '0',
  `arcade_catid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  KEY `group_id` (`group_id`),
  KEY `arcade_catid` (`arcade_catid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbbanlist`
--

DROP TABLE IF EXISTS `nuke_bbbanlist`;
CREATE TABLE IF NOT EXISTS `nuke_bbbanlist` (
  `ban_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ban_userid` mediumint(8) NOT NULL DEFAULT '0',
  `ban_ip` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ban_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ban_id`),
  KEY `ban_ip_user_id` (`ban_ip`,`ban_userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbcategories`
--

DROP TABLE IF EXISTS `nuke_bbcategories`;
CREATE TABLE IF NOT EXISTS `nuke_bbcategories` (
  `cat_id` int(8) NOT NULL AUTO_INCREMENT,
  `cat_title` varchar(100) DEFAULT NULL,
  `cat_order` int(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`),
  KEY `cat_order` (`cat_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbconfig`
--

DROP TABLE IF EXISTS `nuke_bbconfig`;
CREATE TABLE IF NOT EXISTS `nuke_bbconfig` (
  `config_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `config_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`config_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbconfirm`
--

DROP TABLE IF EXISTS `nuke_bbconfirm`;
CREATE TABLE IF NOT EXISTS `nuke_bbconfirm` (
  `confirm_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `session_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `code` char(6) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`session_id`,`confirm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbdisallow`
--

DROP TABLE IF EXISTS `nuke_bbdisallow`;
CREATE TABLE IF NOT EXISTS `nuke_bbdisallow` (
  `disallow_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `disallow_username` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`disallow_id`)
) ENGINE=Aria  DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbforums`
--

DROP TABLE IF EXISTS `nuke_bbforums`;
CREATE TABLE IF NOT EXISTS `nuke_bbforums` (
  `forum_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `forum_name` varchar(150) DEFAULT NULL,
  `forum_desc` text,
  `forum_status` tinyint(4) NOT NULL DEFAULT '0',
  `forum_order` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `forum_posts` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `forum_topics` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `forum_last_post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `prune_next` int(11) DEFAULT NULL,
  `prune_enable` tinyint(1) NOT NULL DEFAULT '0',
  `auth_view` tinyint(2) NOT NULL DEFAULT '0',
  `auth_read` tinyint(2) NOT NULL DEFAULT '0',
  `auth_post` tinyint(2) NOT NULL DEFAULT '0',
  `auth_reply` tinyint(2) NOT NULL DEFAULT '0',
  `auth_edit` tinyint(2) NOT NULL DEFAULT '0',
  `auth_delete` tinyint(2) NOT NULL DEFAULT '0',
  `auth_sticky` tinyint(2) NOT NULL DEFAULT '0',
  `auth_announce` tinyint(2) NOT NULL DEFAULT '0',
  `auth_vote` tinyint(2) NOT NULL DEFAULT '0',
  `auth_pollcreate` tinyint(2) NOT NULL DEFAULT '0',
  `auth_attachments` tinyint(2) NOT NULL DEFAULT '0',
  `forum_parent` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`forum_id`),
  KEY `cat_id` (`cat_id`),
  KEY `forum_order` (`forum_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbforum_prune`
--

DROP TABLE IF EXISTS `nuke_bbforum_prune`;
CREATE TABLE IF NOT EXISTS `nuke_bbforum_prune` (
  `prune_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `prune_days` smallint(5) unsigned NOT NULL DEFAULT '0',
  `prune_freq` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`prune_id`),
  KEY `forum_id` (`forum_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbgamehash`
--

DROP TABLE IF EXISTS `nuke_bbgamehash`;
CREATE TABLE IF NOT EXISTS `nuke_bbgamehash` (
  `gamehash_id` char(32) NOT NULL DEFAULT '',
  `game_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `hash_date` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbgames`
--

DROP TABLE IF EXISTS `nuke_bbgames`;
CREATE TABLE IF NOT EXISTS `nuke_bbgames` (
  `game_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `game_pic` varchar(50) NOT NULL DEFAULT '',
  `game_desc` varchar(255) NOT NULL DEFAULT '',
  `game_highscore` int(11) NOT NULL DEFAULT '0',
  `game_highdate` int(11) NOT NULL DEFAULT '0',
  `game_highuser` mediumint(8) NOT NULL DEFAULT '0',
  `game_name` varchar(50) NOT NULL DEFAULT '',
  `game_swf` varchar(50) NOT NULL DEFAULT '',
  `game_scorevar` varchar(20) NOT NULL DEFAULT '',
  `game_type` tinyint(4) NOT NULL DEFAULT '0',
  `game_width` mediumint(5) NOT NULL DEFAULT '550',
  `game_height` varchar(5) NOT NULL DEFAULT '380',
  `game_order` mediumint(8) NOT NULL DEFAULT '0',
  `game_set` mediumint(8) NOT NULL DEFAULT '0',
  `arcade_catid` mediumint(8) NOT NULL DEFAULT '1',
  KEY `game_id` (`game_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbgroups`
--

DROP TABLE IF EXISTS `nuke_bbgroups`;
CREATE TABLE IF NOT EXISTS `nuke_bbgroups` (
  `group_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `group_type` tinyint(4) NOT NULL DEFAULT '1',
  `group_name` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `group_description` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `group_moderator` mediumint(8) NOT NULL DEFAULT '0',
  `group_single_user` tinyint(1) NOT NULL DEFAULT '1',
  `organisation_id` int(11) NOT NULL DEFAULT '0',
  `group_attrs` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `group_single_user` (`group_single_user`),
  KEY `organisation_id` (`organisation_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbhackgame`
--

DROP TABLE IF EXISTS `nuke_bbhackgame`;
CREATE TABLE IF NOT EXISTS `nuke_bbhackgame` (
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `game_id` mediumint(8) NOT NULL DEFAULT '0',
  `date_hack` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts`
--

DROP TABLE IF EXISTS `nuke_bbposts`;
CREATE TABLE IF NOT EXISTS `nuke_bbposts` (
  `post_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `forum_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `poster_id` mediumint(8) NOT NULL DEFAULT '0',
  `post_time` int(11) NOT NULL DEFAULT '0',
  `poster_ip` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `post_username` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enable_bbcode` tinyint(1) NOT NULL DEFAULT '1',
  `enable_html` tinyint(1) NOT NULL DEFAULT '0',
  `enable_smilies` tinyint(1) NOT NULL DEFAULT '1',
  `enable_sig` tinyint(1) NOT NULL DEFAULT '1',
  `post_edit_time` int(11) DEFAULT NULL,
  `post_edit_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  `post_reported` tinyint(1) NOT NULL DEFAULT '0',
  `post_herring_count` int(10) NOT NULL,
  `post_rating` int(11) NOT NULL DEFAULT '0',
  `lat` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lon` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zoom` int(10) NOT NULL DEFAULT '14',
  `pinned` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`post_id`),
  KEY `forum_id` (`forum_id`),
  KEY `topic_id` (`topic_id`),
  KEY `poster_id` (`poster_id`),
  KEY `post_time` (`post_time`),
  KEY `pinned` (`pinned`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts_edit`
--

DROP TABLE IF EXISTS `nuke_bbposts_edit`;
CREATE TABLE IF NOT EXISTS `nuke_bbposts_edit` (
  `edit_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(32) NOT NULL DEFAULT '0',
  `thread_id` int(11) NOT NULL DEFAULT '0',
  `poster_id` int(11) NOT NULL DEFAULT '0',
  `editor_id` int(11) NOT NULL,
  `edit_time` varchar(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `edit_body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `bbcode_uid` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`edit_id`),
  KEY `post_id` (`post_id`),
  KEY `thread_id` (`thread_id`),
  KEY `poster_id` (`poster_id`),
  KEY `editor_id` (`editor_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts_reputation`
--

DROP TABLE IF EXISTS `nuke_bbposts_reputation`;
CREATE TABLE IF NOT EXISTS `nuke_bbposts_reputation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `type` tinyint(2) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`,`type`,`date`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts_text`
--

DROP TABLE IF EXISTS `nuke_bbposts_text`;
CREATE TABLE IF NOT EXISTS `nuke_bbposts_text` (
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `bbcode_uid` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `post_subject` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_text` mediumtext COLLATE utf8_unicode_ci,
  `url_slug` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `editor_version` int(10) NOT NULL DEFAULT '1',
  PRIMARY KEY (`post_id`),
  KEY `url_slug` (`url_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbprivmsgs`
--

DROP TABLE IF EXISTS `nuke_bbprivmsgs`;
CREATE TABLE IF NOT EXISTS `nuke_bbprivmsgs` (
  `privmsgs_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `privmsgs_type` tinyint(4) NOT NULL DEFAULT '0',
  `privmsgs_subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `privmsgs_from_userid` mediumint(8) NOT NULL DEFAULT '0',
  `privmsgs_to_userid` mediumint(8) NOT NULL DEFAULT '0',
  `privmsgs_date` int(11) NOT NULL DEFAULT '0',
  `privmsgs_ip` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `privmsgs_enable_bbcode` tinyint(1) NOT NULL DEFAULT '1',
  `privmsgs_enable_html` tinyint(1) NOT NULL DEFAULT '0',
  `privmsgs_enable_smilies` tinyint(1) NOT NULL DEFAULT '1',
  `privmsgs_attach_sig` tinyint(1) NOT NULL DEFAULT '1',
  `object_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `hide_from` tinyint(1) NOT NULL DEFAULT '0',
  `hide_to` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`privmsgs_id`),
  KEY `idx_from` (`privmsgs_from_userid`),
  KEY `idx_to` (`privmsgs_to_userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbprivmsgs_archive`
--

DROP TABLE IF EXISTS `nuke_bbprivmsgs_archive`;
CREATE TABLE IF NOT EXISTS `nuke_bbprivmsgs_archive` (
  `privmsgs_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `privmsgs_type` tinyint(4) NOT NULL DEFAULT '0',
  `privmsgs_subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `privmsgs_from_userid` mediumint(8) NOT NULL DEFAULT '0',
  `privmsgs_to_userid` mediumint(8) NOT NULL DEFAULT '0',
  `privmsgs_date` int(11) NOT NULL DEFAULT '0',
  `privmsgs_ip` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `privmsgs_enable_bbcode` tinyint(1) NOT NULL DEFAULT '1',
  `privmsgs_enable_html` tinyint(1) NOT NULL DEFAULT '0',
  `privmsgs_enable_smilies` tinyint(1) NOT NULL DEFAULT '1',
  `privmsgs_attach_sig` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`privmsgs_id`),
  KEY `privmsgs_from_userid` (`privmsgs_from_userid`),
  KEY `privmsgs_to_userid` (`privmsgs_to_userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbprivmsgs_text`
--

DROP TABLE IF EXISTS `nuke_bbprivmsgs_text`;
CREATE TABLE IF NOT EXISTS `nuke_bbprivmsgs_text` (
  `privmsgs_text_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `privmsgs_bbcode_uid` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `privmsgs_text` mediumtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`privmsgs_text_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbranks`
--

DROP TABLE IF EXISTS `nuke_bbranks`;
CREATE TABLE IF NOT EXISTS `nuke_bbranks` (
  `rank_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `rank_title` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rank_min` mediumint(8) NOT NULL DEFAULT '0',
  `rank_special` tinyint(1) DEFAULT NULL,
  `rank_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`rank_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbscores`
--

DROP TABLE IF EXISTS `nuke_bbscores`;
CREATE TABLE IF NOT EXISTS `nuke_bbscores` (
  `game_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `score_game` int(11) NOT NULL DEFAULT '0',
  `score_date` int(11) NOT NULL DEFAULT '0',
  `score_time` int(11) NOT NULL DEFAULT '0',
  `score_set` mediumint(8) NOT NULL DEFAULT '0',
  KEY `game_id` (`game_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_pending`
--

DROP TABLE IF EXISTS `nuke_bbsearch_pending`;
CREATE TABLE IF NOT EXISTS `nuke_bbsearch_pending` (
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `mode` varchar(20) COLLATE utf8_unicode_ci DEFAULT 'single',
  `post_subject` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_text` mediumtext COLLATE utf8_unicode_ci,
  KEY `post_id` (`post_id`),
  FULLTEXT KEY `mode` (`mode`,`post_subject`,`post_text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_results`
--

DROP TABLE IF EXISTS `nuke_bbsearch_results`;
CREATE TABLE IF NOT EXISTS `nuke_bbsearch_results` (
  `search_id` int(11) unsigned NOT NULL DEFAULT '0',
  `session_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `search_array` longtext COLLATE utf8_unicode_ci NOT NULL,
  `search_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`search_id`),
  KEY `session_id` (`session_id`),
  KEY `search_time` (`search_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_wordlist`
--

DROP TABLE IF EXISTS `nuke_bbsearch_wordlist`;
CREATE TABLE IF NOT EXISTS `nuke_bbsearch_wordlist` (
  `word_text` varchar(50) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
  `word_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `word_common` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`word_text`),
  KEY `word_id` (`word_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_wordmatch`
--

DROP TABLE IF EXISTS `nuke_bbsearch_wordmatch`;
CREATE TABLE IF NOT EXISTS `nuke_bbsearch_wordmatch` (
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `word_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `title_match` tinyint(1) NOT NULL DEFAULT '0',
  KEY `word_id` (`word_id`),
  KEY `post_id` (`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsessions`
--

DROP TABLE IF EXISTS `nuke_bbsessions`;
CREATE TABLE IF NOT EXISTS `nuke_bbsessions` (
  `session_id` char(32) NOT NULL DEFAULT '',
  `session_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `session_start` int(11) NOT NULL DEFAULT '0',
  `session_time` int(11) NOT NULL DEFAULT '0',
  `session_ip` char(8) NOT NULL DEFAULT '',
  `session_page` int(11) NOT NULL DEFAULT '0',
  `session_logged_in` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `session_user_id` (`session_user_id`),
  KEY `session_id_ip_user_id` (`session_id`,`session_ip`,`session_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsmilies`
--

DROP TABLE IF EXISTS `nuke_bbsmilies`;
CREATE TABLE IF NOT EXISTS `nuke_bbsmilies` (
  `smilies_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `smile_url` varchar(100) DEFAULT NULL,
  `emoticon` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`smilies_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbthemes`
--

DROP TABLE IF EXISTS `nuke_bbthemes`;
CREATE TABLE IF NOT EXISTS `nuke_bbthemes` (
  `themes_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `template_name` varchar(30) NOT NULL DEFAULT '',
  `style_name` varchar(30) NOT NULL DEFAULT '',
  `head_stylesheet` varchar(100) DEFAULT NULL,
  `body_background` varchar(100) DEFAULT NULL,
  `body_bgcolor` varchar(6) DEFAULT NULL,
  `body_text` varchar(6) DEFAULT NULL,
  `body_link` varchar(6) DEFAULT NULL,
  `body_vlink` varchar(6) DEFAULT NULL,
  `body_alink` varchar(6) DEFAULT NULL,
  `body_hlink` varchar(6) DEFAULT NULL,
  `tr_color1` varchar(6) DEFAULT NULL,
  `tr_color2` varchar(6) DEFAULT NULL,
  `tr_color3` varchar(6) DEFAULT NULL,
  `tr_class1` varchar(25) DEFAULT NULL,
  `tr_class2` varchar(25) DEFAULT NULL,
  `tr_class3` varchar(25) DEFAULT NULL,
  `th_color1` varchar(6) DEFAULT NULL,
  `th_color2` varchar(6) DEFAULT NULL,
  `th_color3` varchar(6) DEFAULT NULL,
  `th_class1` varchar(25) DEFAULT NULL,
  `th_class2` varchar(25) DEFAULT NULL,
  `th_class3` varchar(25) DEFAULT NULL,
  `td_color1` varchar(6) DEFAULT NULL,
  `td_color2` varchar(6) DEFAULT NULL,
  `td_color3` varchar(6) DEFAULT NULL,
  `td_class1` varchar(25) DEFAULT NULL,
  `td_class2` varchar(25) DEFAULT NULL,
  `td_class3` varchar(25) DEFAULT NULL,
  `fontface1` varchar(50) DEFAULT NULL,
  `fontface2` varchar(50) DEFAULT NULL,
  `fontface3` varchar(50) DEFAULT NULL,
  `fontsize1` tinyint(4) DEFAULT NULL,
  `fontsize2` tinyint(4) DEFAULT NULL,
  `fontsize3` tinyint(4) DEFAULT NULL,
  `fontcolor1` varchar(6) DEFAULT NULL,
  `fontcolor2` varchar(6) DEFAULT NULL,
  `fontcolor3` varchar(6) DEFAULT NULL,
  `span_class1` varchar(25) DEFAULT NULL,
  `span_class2` varchar(25) DEFAULT NULL,
  `span_class3` varchar(25) DEFAULT NULL,
  `img_size_poll` smallint(5) unsigned DEFAULT NULL,
  `img_size_privmsg` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`themes_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbthemes_name`
--

DROP TABLE IF EXISTS `nuke_bbthemes_name`;
CREATE TABLE IF NOT EXISTS `nuke_bbthemes_name` (
  `themes_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `tr_color1_name` char(50) DEFAULT NULL,
  `tr_color2_name` char(50) DEFAULT NULL,
  `tr_color3_name` char(50) DEFAULT NULL,
  `tr_class1_name` char(50) DEFAULT NULL,
  `tr_class2_name` char(50) DEFAULT NULL,
  `tr_class3_name` char(50) DEFAULT NULL,
  `th_color1_name` char(50) DEFAULT NULL,
  `th_color2_name` char(50) DEFAULT NULL,
  `th_color3_name` char(50) DEFAULT NULL,
  `th_class1_name` char(50) DEFAULT NULL,
  `th_class2_name` char(50) DEFAULT NULL,
  `th_class3_name` char(50) DEFAULT NULL,
  `td_color1_name` char(50) DEFAULT NULL,
  `td_color2_name` char(50) DEFAULT NULL,
  `td_color3_name` char(50) DEFAULT NULL,
  `td_class1_name` char(50) DEFAULT NULL,
  `td_class2_name` char(50) DEFAULT NULL,
  `td_class3_name` char(50) DEFAULT NULL,
  `fontface1_name` char(50) DEFAULT NULL,
  `fontface2_name` char(50) DEFAULT NULL,
  `fontface3_name` char(50) DEFAULT NULL,
  `fontsize1_name` char(50) DEFAULT NULL,
  `fontsize2_name` char(50) DEFAULT NULL,
  `fontsize3_name` char(50) DEFAULT NULL,
  `fontcolor1_name` char(50) DEFAULT NULL,
  `fontcolor2_name` char(50) DEFAULT NULL,
  `fontcolor3_name` char(50) DEFAULT NULL,
  `span_class1_name` char(50) DEFAULT NULL,
  `span_class2_name` char(50) DEFAULT NULL,
  `span_class3_name` char(50) DEFAULT NULL,
  PRIMARY KEY (`themes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbtopics`
--

DROP TABLE IF EXISTS `nuke_bbtopics`;
CREATE TABLE IF NOT EXISTS `nuke_bbtopics` (
  `topic_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` smallint(8) unsigned NOT NULL DEFAULT '0',
  `topic_title` char(150) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `topic_poster` mediumint(8) NOT NULL DEFAULT '0',
  `topic_time` int(11) NOT NULL DEFAULT '0',
  `topic_views` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `topic_replies` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `topic_status` tinyint(3) NOT NULL DEFAULT '0',
  `topic_vote` tinyint(1) NOT NULL DEFAULT '0',
  `topic_type` tinyint(3) NOT NULL DEFAULT '0',
  `topic_first_post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `topic_last_post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `topic_moved_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `url_slug` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `topic_meta` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`topic_id`),
  KEY `forum_id` (`forum_id`),
  KEY `topic_moved_id` (`topic_moved_id`),
  KEY `topic_status` (`topic_status`),
  KEY `topic_type` (`topic_type`),
  KEY `topic_poster` (`topic_poster`),
  KEY `url_slug` (`url_slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbtopics_view`
--

DROP TABLE IF EXISTS `nuke_bbtopics_view`;
CREATE TABLE IF NOT EXISTS `nuke_bbtopics_view` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `topic_id` (`topic_id`,`user_id`),
  KEY `viewed` (`viewed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbtopics_watch`
--

DROP TABLE IF EXISTS `nuke_bbtopics_watch`;
CREATE TABLE IF NOT EXISTS `nuke_bbtopics_watch` (
  `topic_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `notify_status` tinyint(1) NOT NULL DEFAULT '0',
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`),
  KEY `notify_status` (`notify_status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbuser_group`
--

DROP TABLE IF EXISTS `nuke_bbuser_group`;
CREATE TABLE IF NOT EXISTS `nuke_bbuser_group` (
  `group_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_pending` tinyint(1) DEFAULT '0',
  `organisation_role` varchar(128) NOT NULL,
  `organisation_contact` varchar(256) NOT NULL,
  `organisation_privileges` int(11) NOT NULL DEFAULT '0',
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`),
  KEY `user_pending` (`user_pending`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbvote_desc`
--

DROP TABLE IF EXISTS `nuke_bbvote_desc`;
CREATE TABLE IF NOT EXISTS `nuke_bbvote_desc` (
  `vote_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `vote_text` text NOT NULL,
  `vote_start` int(11) NOT NULL DEFAULT '0',
  `vote_length` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`),
  KEY `topic_id` (`topic_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbvote_results`
--

DROP TABLE IF EXISTS `nuke_bbvote_results`;
CREATE TABLE IF NOT EXISTS `nuke_bbvote_results` (
  `vote_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `vote_option_id` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `vote_option_text` varchar(255) NOT NULL DEFAULT '',
  `vote_result` int(11) NOT NULL DEFAULT '0',
  KEY `vote_option_id` (`vote_option_id`),
  KEY `vote_id` (`vote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbvote_voters`
--

DROP TABLE IF EXISTS `nuke_bbvote_voters`;
CREATE TABLE IF NOT EXISTS `nuke_bbvote_voters` (
  `vote_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `vote_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `vote_user_ip` char(8) NOT NULL DEFAULT '',
  KEY `vote_id` (`vote_id`),
  KEY `vote_user_id` (`vote_user_id`),
  KEY `vote_user_ip` (`vote_user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbwords`
--

DROP TABLE IF EXISTS `nuke_bbwords`;
CREATE TABLE IF NOT EXISTS `nuke_bbwords` (
  `word_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `word` char(100) NOT NULL DEFAULT '',
  `replacement` char(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`word_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_blocks`
--

DROP TABLE IF EXISTS `nuke_blocks`;
CREATE TABLE IF NOT EXISTS `nuke_blocks` (
  `bid` int(10) NOT NULL AUTO_INCREMENT,
  `bkey` varchar(15) NOT NULL DEFAULT '',
  `title` varchar(60) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `url` varchar(200) NOT NULL DEFAULT '',
  `bposition` char(1) NOT NULL DEFAULT '',
  `weight` int(10) NOT NULL DEFAULT '1',
  `active` int(1) NOT NULL DEFAULT '1',
  `refresh` int(10) NOT NULL DEFAULT '0',
  `time` varchar(14) NOT NULL DEFAULT '0',
  `blanguage` varchar(30) NOT NULL DEFAULT '',
  `blockfile` varchar(255) NOT NULL DEFAULT '',
  `view` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bid`),
  KEY `bid` (`bid`),
  KEY `title` (`title`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_comments`
--

DROP TABLE IF EXISTS `nuke_comments`;
CREATE TABLE IF NOT EXISTS `nuke_comments` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `name` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) DEFAULT NULL,
  `url` varchar(60) DEFAULT NULL,
  `host_name` varchar(60) DEFAULT NULL,
  `subject` varchar(85) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `score` tinyint(4) NOT NULL DEFAULT '0',
  `reason` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `sid` (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_config`
--

DROP TABLE IF EXISTS `nuke_config`;
CREATE TABLE IF NOT EXISTS `nuke_config` (
  `sitename` varchar(255) NOT NULL DEFAULT '',
  `nukeurl` varchar(255) NOT NULL DEFAULT '',
  `site_logo` varchar(255) NOT NULL DEFAULT '',
  `slogan` varchar(255) NOT NULL DEFAULT '',
  `startdate` varchar(50) NOT NULL DEFAULT '',
  `adminmail` varchar(255) NOT NULL DEFAULT '',
  `anonpost` tinyint(1) NOT NULL DEFAULT '0',
  `Default_Theme` varchar(255) NOT NULL DEFAULT '',
  `foot1` text NOT NULL,
  `foot2` text NOT NULL,
  `foot3` text NOT NULL,
  `commentlimit` int(9) NOT NULL DEFAULT '4096',
  `anonymous` varchar(255) NOT NULL DEFAULT '',
  `minpass` tinyint(1) NOT NULL DEFAULT '5',
  `pollcomm` tinyint(1) NOT NULL DEFAULT '1',
  `articlecomm` tinyint(1) NOT NULL DEFAULT '1',
  `broadcast_msg` tinyint(1) NOT NULL DEFAULT '1',
  `my_headlines` tinyint(1) NOT NULL DEFAULT '1',
  `top` int(3) NOT NULL DEFAULT '10',
  `storyhome` int(2) NOT NULL DEFAULT '10',
  `user_news` tinyint(1) NOT NULL DEFAULT '1',
  `oldnum` int(2) NOT NULL DEFAULT '30',
  `ultramode` tinyint(1) NOT NULL DEFAULT '0',
  `banners` tinyint(1) NOT NULL DEFAULT '1',
  `backend_title` varchar(255) NOT NULL DEFAULT '',
  `backend_language` varchar(10) NOT NULL DEFAULT '',
  `language` varchar(100) NOT NULL DEFAULT '',
  `locale` varchar(10) NOT NULL DEFAULT '',
  `multilingual` tinyint(1) NOT NULL DEFAULT '0',
  `useflags` tinyint(1) NOT NULL DEFAULT '0',
  `notify` tinyint(1) NOT NULL DEFAULT '0',
  `notify_email` varchar(255) NOT NULL DEFAULT '',
  `notify_subject` varchar(255) NOT NULL DEFAULT '',
  `notify_message` varchar(255) NOT NULL DEFAULT '',
  `notify_from` varchar(255) NOT NULL DEFAULT '',
  `footermsgtxt` text NOT NULL,
  `email_send` tinyint(1) NOT NULL DEFAULT '1',
  `attachmentdir` varchar(255) NOT NULL DEFAULT '',
  `attachments` tinyint(1) NOT NULL DEFAULT '0',
  `attachments_view` tinyint(1) NOT NULL DEFAULT '0',
  `download_dir` varchar(255) NOT NULL DEFAULT '',
  `defaultpopserver` varchar(255) NOT NULL DEFAULT '',
  `singleaccount` tinyint(1) NOT NULL DEFAULT '0',
  `singleaccountname` varchar(255) NOT NULL DEFAULT '',
  `numaccounts` tinyint(2) NOT NULL DEFAULT '-1',
  `imgpath` varchar(255) NOT NULL DEFAULT '',
  `filter_forward` tinyint(1) NOT NULL DEFAULT '1',
  `moderate` tinyint(1) NOT NULL DEFAULT '0',
  `admingraphic` tinyint(1) NOT NULL DEFAULT '1',
  `httpref` tinyint(1) NOT NULL DEFAULT '1',
  `httprefmax` int(5) NOT NULL DEFAULT '1000',
  `CensorMode` tinyint(1) NOT NULL DEFAULT '3',
  `CensorReplace` varchar(10) NOT NULL DEFAULT '',
  `copyright` text NOT NULL,
  `Version_Num` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_contactbook`
--

DROP TABLE IF EXISTS `nuke_contactbook`;
CREATE TABLE IF NOT EXISTS `nuke_contactbook` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `contactid` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `homeaddress` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `homephone` varchar(255) DEFAULT NULL,
  `workphone` varchar(255) DEFAULT NULL,
  `homepage` varchar(255) DEFAULT NULL,
  `IM` varchar(255) DEFAULT NULL,
  `events` text,
  `reminders` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`contactid`),
  KEY `uid` (`uid`),
  KEY `contactid` (`contactid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_counter`
--

DROP TABLE IF EXISTS `nuke_counter`;
CREATE TABLE IF NOT EXISTS `nuke_counter` (
  `type` varchar(80) NOT NULL DEFAULT '',
  `var` varchar(80) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_categories`
--

DROP TABLE IF EXISTS `nuke_downloads_categories`;
CREATE TABLE IF NOT EXISTS `nuke_downloads_categories` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT '',
  `cdescription` text NOT NULL,
  `parentid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `cid` (`cid`),
  KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_downloads`
--

DROP TABLE IF EXISTS `nuke_downloads_downloads`;
CREATE TABLE IF NOT EXISTS `nuke_downloads_downloads` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '',
  `filename` varchar(512) DEFAULT NULL,
  `mime` varchar(512) NOT NULL,
  `description` text NOT NULL,
  `date` datetime DEFAULT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `hits` int(11) NOT NULL DEFAULT '0',
  `submitter` varchar(60) NOT NULL DEFAULT '',
  `downloadratingsummary` double(6,4) NOT NULL DEFAULT '0.0000',
  `totalvotes` int(11) NOT NULL DEFAULT '0',
  `totalcomments` int(11) NOT NULL DEFAULT '0',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `version` varchar(10) NOT NULL DEFAULT '',
  `homepage` varchar(200) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lid`),
  KEY `lid` (`lid`),
  KEY `cid` (`cid`),
  KEY `sid` (`sid`),
  KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_editorials`
--

DROP TABLE IF EXISTS `nuke_downloads_editorials`;
CREATE TABLE IF NOT EXISTS `nuke_downloads_editorials` (
  `downloadid` int(11) NOT NULL DEFAULT '0',
  `adminid` varchar(60) NOT NULL DEFAULT '',
  `editorialtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `editorialtext` text NOT NULL,
  `editorialtitle` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`downloadid`),
  KEY `downloadid` (`downloadid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_modrequest`
--

DROP TABLE IF EXISTS `nuke_downloads_modrequest`;
CREATE TABLE IF NOT EXISTS `nuke_downloads_modrequest` (
  `requestid` int(11) NOT NULL AUTO_INCREMENT,
  `lid` int(11) NOT NULL DEFAULT '0',
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `modifysubmitter` varchar(60) NOT NULL DEFAULT '',
  `brokendownload` int(3) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `version` varchar(10) NOT NULL DEFAULT '',
  `homepage` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`requestid`),
  UNIQUE KEY `requestid` (`requestid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_newdownload`
--

DROP TABLE IF EXISTS `nuke_downloads_newdownload`;
CREATE TABLE IF NOT EXISTS `nuke_downloads_newdownload` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '',
  `filename` varchar(512) DEFAULT NULL,
  `description` text NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `submitter` varchar(60) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `version` varchar(10) NOT NULL DEFAULT '',
  `homepage` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`lid`),
  KEY `lid` (`lid`),
  KEY `cid` (`cid`),
  KEY `sid` (`sid`),
  KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_votedata`
--

DROP TABLE IF EXISTS `nuke_downloads_votedata`;
CREATE TABLE IF NOT EXISTS `nuke_downloads_votedata` (
  `ratingdbid` int(11) NOT NULL AUTO_INCREMENT,
  `ratinglid` int(11) NOT NULL DEFAULT '0',
  `ratinguser` varchar(60) NOT NULL DEFAULT '',
  `rating` int(11) NOT NULL DEFAULT '0',
  `ratinghostname` varchar(60) NOT NULL DEFAULT '',
  `ratingcomments` text NOT NULL,
  `ratingtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ratingdbid`),
  KEY `ratingdbid` (`ratingdbid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_encyclopedia`
--

DROP TABLE IF EXISTS `nuke_encyclopedia`;
CREATE TABLE IF NOT EXISTS `nuke_encyclopedia` (
  `eid` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `elanguage` varchar(30) NOT NULL DEFAULT '',
  `active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`eid`),
  KEY `eid` (`eid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_encyclopedia_text`
--

DROP TABLE IF EXISTS `nuke_encyclopedia_text`;
CREATE TABLE IF NOT EXISTS `nuke_encyclopedia_text` (
  `tid` int(10) NOT NULL AUTO_INCREMENT,
  `eid` int(10) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `counter` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `tid` (`tid`),
  KEY `eid` (`eid`),
  KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_ephem`
--

DROP TABLE IF EXISTS `nuke_ephem`;
CREATE TABLE IF NOT EXISTS `nuke_ephem` (
  `eid` int(11) NOT NULL AUTO_INCREMENT,
  `did` int(2) NOT NULL DEFAULT '0',
  `mid` int(2) NOT NULL DEFAULT '0',
  `yid` int(4) NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `elanguage` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`eid`),
  KEY `eid` (`eid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_externalsearch`
--

DROP TABLE IF EXISTS `nuke_externalsearch`;
CREATE TABLE IF NOT EXISTS `nuke_externalsearch` (
  `linkid` int(13) NOT NULL AUTO_INCREMENT,
  `rphosted` int(1) NOT NULL DEFAULT '0',
  `linktitle` text NOT NULL,
  `linktext` text NOT NULL,
  `linkurl` text NOT NULL,
  KEY `linkid` (`linkid`),
  FULLTEXT KEY `linktitle` (`linktitle`,`linktext`,`linkurl`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_faqAnswer`
--

DROP TABLE IF EXISTS `nuke_faqAnswer`;
CREATE TABLE IF NOT EXISTS `nuke_faqAnswer` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `id_cat` tinyint(4) NOT NULL DEFAULT '0',
  `question` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `answer` mediumtext COLLATE utf8_unicode_ci,
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `url_slug` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_cat` (`id_cat`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_faqCategories`
--

DROP TABLE IF EXISTS `nuke_faqCategories`;
CREATE TABLE IF NOT EXISTS `nuke_faqCategories` (
  `id_cat` tinyint(3) NOT NULL AUTO_INCREMENT,
  `url_slug` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `categories` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flanguage` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id_cat`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_g2config`
--

DROP TABLE IF EXISTS `nuke_g2config`;
CREATE TABLE IF NOT EXISTS `nuke_g2config` (
  `embedUri` varchar(255) DEFAULT NULL,
  `g2Uri` varchar(255) DEFAULT NULL,
  `activeUserId` int(10) DEFAULT NULL,
  `cookiepath` varchar(255) DEFAULT NULL,
  `showSidebar` tinyint(1) DEFAULT NULL,
  `g2configurationDone` tinyint(1) DEFAULT NULL,
  `embedVersion` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_gallery`
--

DROP TABLE IF EXISTS `nuke_gallery`;
CREATE TABLE IF NOT EXISTS `nuke_gallery` (
  `album_name` varchar(255) NOT NULL DEFAULT '',
  `album_title` varchar(255) NOT NULL DEFAULT '',
  `parent_album_name` varchar(255) DEFAULT NULL,
  `num_children` int(11) NOT NULL DEFAULT '0',
  `cached_photos` int(11) NOT NULL DEFAULT '0',
  `mod_date` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `album_name` (`album_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_hallfame_queue`
--

DROP TABLE IF EXISTS `nuke_hallfame_queue`;
CREATE TABLE IF NOT EXISTS `nuke_hallfame_queue` (
  `qid` int(11) NOT NULL AUTO_INCREMENT,
  `qdate` varchar(255) NOT NULL DEFAULT '',
  `qnomid` int(20) NOT NULL DEFAULT '0',
  `qanon` int(1) NOT NULL DEFAULT '0',
  `hofuid` int(20) NOT NULL DEFAULT '0',
  `hofreason` text,
  `qvotesfor` int(20) NOT NULL DEFAULT '0',
  `qstate` int(1) NOT NULL DEFAULT '0',
  `qaccept` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`qid`),
  FULLTEXT KEY `hofreason` (`hofreason`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_headlines`
--

DROP TABLE IF EXISTS `nuke_headlines`;
CREATE TABLE IF NOT EXISTS `nuke_headlines` (
  `hid` int(11) NOT NULL AUTO_INCREMENT,
  `sitename` varchar(30) NOT NULL DEFAULT '',
  `headlinesurl` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`hid`),
  KEY `hid` (`hid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_journal`
--

DROP TABLE IF EXISTS `nuke_journal`;
CREATE TABLE IF NOT EXISTS `nuke_journal` (
  `jid` int(11) NOT NULL AUTO_INCREMENT,
  `aid` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(80) DEFAULT NULL,
  `bodytext` text NOT NULL,
  `mood` varchar(48) NOT NULL DEFAULT '',
  `pdate` varchar(48) NOT NULL DEFAULT '',
  `ptime` varchar(48) NOT NULL DEFAULT '',
  `status` varchar(48) NOT NULL DEFAULT '',
  `mtime` varchar(48) NOT NULL DEFAULT '',
  `mdate` varchar(48) NOT NULL DEFAULT '',
  PRIMARY KEY (`jid`),
  KEY `jid` (`jid`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_journal_comments`
--

DROP TABLE IF EXISTS `nuke_journal_comments`;
CREATE TABLE IF NOT EXISTS `nuke_journal_comments` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `rid` varchar(48) NOT NULL DEFAULT '',
  `aid` varchar(30) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `pdate` varchar(48) NOT NULL DEFAULT '',
  `ptime` varchar(48) NOT NULL DEFAULT '',
  PRIMARY KEY (`cid`),
  KEY `cid` (`cid`),
  KEY `rid` (`rid`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_journal_stats`
--

DROP TABLE IF EXISTS `nuke_journal_stats`;
CREATE TABLE IF NOT EXISTS `nuke_journal_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `joid` varchar(48) NOT NULL DEFAULT '',
  `nop` varchar(48) NOT NULL DEFAULT '',
  `ldp` varchar(24) NOT NULL DEFAULT '',
  `ltp` varchar(24) NOT NULL DEFAULT '',
  `micro` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_categories`
--

DROP TABLE IF EXISTS `nuke_links_categories`;
CREATE TABLE IF NOT EXISTS `nuke_links_categories` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `cdescription` text CHARACTER SET latin1 NOT NULL,
  `parentid` int(11) NOT NULL DEFAULT '0',
  `slug` varchar(128) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_editorials`
--

DROP TABLE IF EXISTS `nuke_links_editorials`;
CREATE TABLE IF NOT EXISTS `nuke_links_editorials` (
  `linkid` int(11) NOT NULL DEFAULT '0',
  `adminid` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `editorialtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `editorialtext` text CHARACTER SET latin1 NOT NULL,
  `editorialtitle` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  PRIMARY KEY (`linkid`),
  KEY `linkid` (`linkid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_links`
--

DROP TABLE IF EXISTS `nuke_links_links`;
CREATE TABLE IF NOT EXISTS `nuke_links_links` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `image` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `url` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `description` text CHARACTER SET latin1 NOT NULL,
  `date` datetime DEFAULT NULL,
  `name` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `email` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `hits` int(11) NOT NULL DEFAULT '0',
  `submitter` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `linkratingsummary` double(6,4) NOT NULL DEFAULT '0.0000',
  `totalvotes` int(11) NOT NULL DEFAULT '0',
  `totalcomments` int(11) NOT NULL DEFAULT '0',
  `slug` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `link_broken` tinyint(1) NOT NULL DEFAULT '0',
  `link_approved` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lid`),
  KEY `cid` (`cid`),
  KEY `sid` (`sid`),
  KEY `user_id` (`user_id`,`link_broken`,`link_approved`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_modrequest`
--

DROP TABLE IF EXISTS `nuke_links_modrequest`;
CREATE TABLE IF NOT EXISTS `nuke_links_modrequest` (
  `requestid` int(11) NOT NULL AUTO_INCREMENT,
  `lid` int(11) NOT NULL DEFAULT '0',
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `image` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `url` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `description` text CHARACTER SET latin1 NOT NULL,
  `modifysubmitter` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `brokenlink` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`requestid`),
  UNIQUE KEY `requestid` (`requestid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_newlink`
--

DROP TABLE IF EXISTS `nuke_links_newlink`;
CREATE TABLE IF NOT EXISTS `nuke_links_newlink` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `image` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `url` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `description` text CHARACTER SET latin1 NOT NULL,
  `name` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `email` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `submitter` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  PRIMARY KEY (`lid`),
  KEY `lid` (`lid`),
  KEY `cid` (`cid`),
  KEY `sid` (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_settings`
--

DROP TABLE IF EXISTS `nuke_links_settings`;
CREATE TABLE IF NOT EXISTS `nuke_links_settings` (
  `num_links` smallint(2) NOT NULL DEFAULT '0',
  `scroll` tinyint(1) NOT NULL DEFAULT '0',
  `scroll_amt` tinyint(1) NOT NULL DEFAULT '0',
  `scroll_direction` tinyint(1) NOT NULL DEFAULT '0',
  `scroll_height` int(4) NOT NULL DEFAULT '0',
  `num_line_breaks` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_votedata`
--

DROP TABLE IF EXISTS `nuke_links_votedata`;
CREATE TABLE IF NOT EXISTS `nuke_links_votedata` (
  `ratingdbid` int(11) NOT NULL AUTO_INCREMENT,
  `ratinglid` int(11) NOT NULL DEFAULT '0',
  `ratinguser` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `rating` int(11) NOT NULL DEFAULT '0',
  `ratinghostname` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `ratingcomments` text CHARACTER SET latin1 NOT NULL,
  `ratingtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ratingdbid`),
  KEY `ratingdbid` (`ratingdbid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_main`
--

DROP TABLE IF EXISTS `nuke_main`;
CREATE TABLE IF NOT EXISTS `nuke_main` (
  `main_module` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_message`
--

DROP TABLE IF EXISTS `nuke_message`;
CREATE TABLE IF NOT EXISTS `nuke_message` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `date` varchar(14) NOT NULL DEFAULT '',
  `expire` int(7) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '1',
  `view` int(1) NOT NULL DEFAULT '1',
  `mlanguage` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`mid`),
  UNIQUE KEY `mid` (`mid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_modules`
--

DROP TABLE IF EXISTS `nuke_modules`;
CREATE TABLE IF NOT EXISTS `nuke_modules` (
  `mid` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `custom_title` varchar(255) NOT NULL DEFAULT '',
  `url` text NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  `view` int(1) NOT NULL DEFAULT '0',
  `inmenu` tinyint(1) NOT NULL DEFAULT '1',
  `mcid` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`mid`),
  KEY `mid` (`mid`),
  KEY `title` (`title`),
  KEY `custom_title` (`custom_title`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_modules_categories`
--

DROP TABLE IF EXISTS `nuke_modules_categories`;
CREATE TABLE IF NOT EXISTS `nuke_modules_categories` (
  `mcid` int(11) NOT NULL AUTO_INCREMENT,
  `mcname` varchar(60) NOT NULL DEFAULT '',
  `visible` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`mcid`),
  KEY `mcid` (`mcid`),
  KEY `mcname` (`mcname`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_admin`
--

DROP TABLE IF EXISTS `nuke_msanalysis_admin`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `max_items` int(10) NOT NULL DEFAULT '10',
  `max_view` int(10) NOT NULL DEFAULT '25',
  `max_online` int(10) NOT NULL DEFAULT '50',
  `max_browse` int(10) NOT NULL DEFAULT '1000',
  `max_inactive` int(5) NOT NULL DEFAULT '25',
  `search_store` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `overview` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `screen` int(2) unsigned NOT NULL DEFAULT '1',
  `xdate` date NOT NULL DEFAULT '2003-08-10',
  `curdate` date NOT NULL DEFAULT '2004-06-22',
  `lastupdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `staticupdate` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `blocked` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `GMT_offset` varchar(5) NOT NULL DEFAULT '+2',
  `msaurl` varchar(255) NOT NULL DEFAULT 'http://www.railpage.com.au',
  `allow_pruning` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `nbrdays` int(3) unsigned NOT NULL DEFAULT '30',
  `begindate` date NOT NULL DEFAULT '2003-08-10',
  `tcountries` int(5) unsigned NOT NULL DEFAULT '200',
  `treferrals` int(5) unsigned NOT NULL DEFAULT '1000',
  `tsearcheng` int(5) unsigned NOT NULL DEFAULT '250',
  `tqueries` int(5) unsigned NOT NULL DEFAULT '1000',
  `tbrowsers` int(5) unsigned NOT NULL DEFAULT '150',
  `tcrawlers` int(5) unsigned NOT NULL DEFAULT '150',
  `tos` int(5) unsigned NOT NULL DEFAULT '50',
  `tmodules` int(5) unsigned NOT NULL DEFAULT '100',
  `tusers` int(5) unsigned NOT NULL DEFAULT '5000',
  `tresolution` int(5) unsigned NOT NULL DEFAULT '100',
  `copyright` varchar(25) NOT NULL DEFAULT 'Maty Scripts',
  `version` varchar(25) NOT NULL DEFAULT 'MS-Analysis v1.1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_browsers`
--

DROP TABLE IF EXISTS `nuke_msanalysis_browsers`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_browsers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ibrowser` varchar(255) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '0000-00-00',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `ibrowser` (`ibrowser`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_countries`
--

DROP TABLE IF EXISTS `nuke_msanalysis_countries`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` char(20) NOT NULL DEFAULT '',
  `description` char(50) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '0000-00-00',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `domain` (`domain`),
  KEY `description` (`description`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_domains`
--

DROP TABLE IF EXISTS `nuke_msanalysis_domains`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` char(20) NOT NULL DEFAULT '',
  `description` char(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `domain` (`domain`),
  KEY `description` (`description`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_modules`
--

DROP TABLE IF EXISTS `nuke_msanalysis_modules`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulename` varchar(50) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `modulename` (`modulename`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_online`
--

DROP TABLE IF EXISTS `nuke_msanalysis_online`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_online` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `uname` varchar(25) NOT NULL DEFAULT '',
  `agent` varchar(255) NOT NULL DEFAULT '',
  `ip_addr` varchar(20) NOT NULL DEFAULT '',
  `host` varchar(100) NOT NULL DEFAULT '',
  `domain` varchar(20) NOT NULL DEFAULT '',
  `modulename` varchar(50) NOT NULL DEFAULT '',
  `scr_res` varchar(25) NOT NULL DEFAULT '',
  `referral` varchar(255) NOT NULL DEFAULT '',
  `ref_query` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_os`
--

DROP TABLE IF EXISTS `nuke_msanalysis_os`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_os` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ios` varchar(25) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `ios` (`ios`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_referrals`
--

DROP TABLE IF EXISTS `nuke_msanalysis_referrals`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referral` varchar(255) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `referral` (`referral`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_scr`
--

DROP TABLE IF EXISTS `nuke_msanalysis_scr`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_scr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scr_res` varchar(25) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `scr_res` (`scr_res`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_search`
--

DROP TABLE IF EXISTS `nuke_msanalysis_search`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `words` varchar(255) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '0000-00-00',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `words` (`words`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_users`
--

DROP TABLE IF EXISTS `nuke_msanalysis_users`;
CREATE TABLE IF NOT EXISTS `nuke_msanalysis_users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `uname` varchar(25) NOT NULL DEFAULT '',
  `browser` varchar(50) NOT NULL DEFAULT '',
  `os` varchar(25) NOT NULL DEFAULT '',
  `ip_addr` varchar(20) NOT NULL DEFAULT '',
  `domain` varchar(20) NOT NULL DEFAULT '',
  `host` varchar(100) NOT NULL DEFAULT '',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `uid` (`uid`),
  KEY `uname` (`uname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_newscomau`
--

DROP TABLE IF EXISTS `nuke_newscomau`;
CREATE TABLE IF NOT EXISTS `nuke_newscomau` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(80) DEFAULT NULL,
  `xtime` int(11) DEFAULT NULL,
  `desctext` text,
  `bodytext` text NOT NULL,
  PRIMARY KEY (`sid`),
  KEY `sid` (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nsndownloads_config`
--

DROP TABLE IF EXISTS `nuke_nsndownloads_config`;
CREATE TABLE IF NOT EXISTS `nuke_nsndownloads_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(40) NOT NULL DEFAULT '',
  `module2_name` varchar(40) NOT NULL DEFAULT '',
  `ipp` int(4) NOT NULL DEFAULT '10',
  `blk1h` int(4) NOT NULL DEFAULT '10',
  `blk1w` int(4) NOT NULL DEFAULT '15',
  `blk2h` int(4) NOT NULL DEFAULT '10',
  `blk2w` int(4) NOT NULL DEFAULT '15',
  `popular` int(5) NOT NULL DEFAULT '500',
  `form_date` varchar(40) NOT NULL DEFAULT '',
  `show_hits` int(1) NOT NULL DEFAULT '1',
  `show_date` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_attendees`
--

DROP TABLE IF EXISTS `nuke_nucal_attendees`;
CREATE TABLE IF NOT EXISTS `nuke_nucal_attendees` (
  `event_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_categories`
--

DROP TABLE IF EXISTS `nuke_nucal_categories`;
CREATE TABLE IF NOT EXISTS `nuke_nucal_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `showinblock` tinyint(1) NOT NULL DEFAULT '1',
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_events`
--

DROP TABLE IF EXISTS `nuke_nucal_events`;
CREATE TABLE IF NOT EXISTS `nuke_nucal_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL DEFAULT '',
  `location` varchar(128) NOT NULL DEFAULT '',
  `starttime` time NOT NULL DEFAULT '00:00:00',
  `duration` time NOT NULL DEFAULT '00:00:00',
  `fulldesc` text NOT NULL,
  `isactive` tinyint(1) NOT NULL DEFAULT '1',
  `isrecurring` tinyint(1) NOT NULL DEFAULT '0',
  `categoryid` int(11) NOT NULL DEFAULT '1',
  `isapproved` tinyint(1) NOT NULL DEFAULT '0',
  `onetime_date` date NOT NULL DEFAULT '0000-00-00',
  `recur_weekday` tinyint(4) NOT NULL DEFAULT '0',
  `recur_schedule` enum('weekly','monthly','yearly') NOT NULL DEFAULT 'weekly',
  `recur_period` tinyint(4) DEFAULT '0',
  `recur_month` tinyint(4) DEFAULT '0',
  `uid` int(13) DEFAULT NULL,
  `lat` varchar(20) DEFAULT NULL,
  `lon` varchar(20) DEFAULT NULL,
  `website` text NOT NULL,
  `flagged` tinyint(1) NOT NULL DEFAULT '0',
  `flag_comments` text NOT NULL,
  `organisation_id` int(10) NOT NULL,
  `ticket_url` text NOT NULL,
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`),
  KEY `starttime` (`starttime`),
  KEY `duration` (`duration`),
  KEY `isactive` (`isactive`),
  KEY `categoryid` (`categoryid`),
  KEY `isapproved` (`isapproved`),
  KEY `onetime_date` (`onetime_date`),
  KEY `uid` (`uid`),
  KEY `lat` (`lat`),
  KEY `lon` (`lon`),
  KEY `flagged` (`flagged`),
  KEY `organisation_id` (`organisation_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_options`
--

DROP TABLE IF EXISTS `nuke_nucal_options`;
CREATE TABLE IF NOT EXISTS `nuke_nucal_options` (
  `allow_user_submitted_events` tinyint(1) NOT NULL DEFAULT '0',
  `user_submitted_events_need_admin_aproval` tinyint(1) NOT NULL DEFAULT '1',
  `calendar_title` varchar(128) NOT NULL DEFAULT 'Calendar of Events',
  `calendar_title_image` varchar(255) NOT NULL DEFAULT '',
  `show_n_events` tinyint(6) unsigned NOT NULL DEFAULT '5',
  `in_n_days` int(11) unsigned NOT NULL DEFAULT '90',
  `show_bydate_in_block` tinyint(1) NOT NULL DEFAULT '1',
  `show_yearly_in_block` tinyint(1) NOT NULL DEFAULT '1',
  `show_yearly_recurring_in_block` tinyint(1) NOT NULL DEFAULT '1',
  `show_monthly_in_block` tinyint(1) NOT NULL DEFAULT '1',
  `show_monthly_recurring_in_block` tinyint(1) NOT NULL DEFAULT '1',
  `show_weekly_in_block` tinyint(1) NOT NULL DEFAULT '1',
  `month_day_color` varchar(6) NOT NULL DEFAULT 'ECECEC',
  `month_today_color` varchar(6) NOT NULL DEFAULT 'FFFFFF',
  `month_hover_color` varchar(6) NOT NULL DEFAULT 'C0C0C0',
  `show_mdy` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_optimize_gain`
--

DROP TABLE IF EXISTS `nuke_optimize_gain`;
CREATE TABLE IF NOT EXISTS `nuke_optimize_gain` (
  `gain` decimal(10,3) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_pages`
--

DROP TABLE IF EXISTS `nuke_pages`;
CREATE TABLE IF NOT EXISTS `nuke_pages` (
  `pid` int(10) NOT NULL AUTO_INCREMENT,
  `cid` int(10) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `subtitle` varchar(255) NOT NULL DEFAULT '',
  `active` int(1) NOT NULL DEFAULT '0',
  `page_header` text NOT NULL,
  `text` longtext NOT NULL,
  `page_footer` text NOT NULL,
  `signature` text NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `counter` int(10) NOT NULL DEFAULT '0',
  `clanguage` varchar(30) NOT NULL DEFAULT '',
  `shortname` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`pid`),
  KEY `pid` (`pid`),
  KEY `cid` (`cid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_pages_categories`
--

DROP TABLE IF EXISTS `nuke_pages_categories`;
CREATE TABLE IF NOT EXISTS `nuke_pages_categories` (
  `cid` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`cid`),
  KEY `cid` (`cid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_pollcomments`
--

DROP TABLE IF EXISTS `nuke_pollcomments`;
CREATE TABLE IF NOT EXISTS `nuke_pollcomments` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `pollID` int(11) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `name` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) DEFAULT NULL,
  `url` varchar(60) DEFAULT NULL,
  `host_name` varchar(60) DEFAULT NULL,
  `subject` varchar(60) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `score` tinyint(4) NOT NULL DEFAULT '0',
  `reason` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `pollID` (`pollID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_poll_check`
--

DROP TABLE IF EXISTS `nuke_poll_check`;
CREATE TABLE IF NOT EXISTS `nuke_poll_check` (
  `ip` varchar(20) NOT NULL DEFAULT '',
  `time` varchar(14) NOT NULL DEFAULT '',
  `pollID` int(10) NOT NULL DEFAULT '0',
  KEY `ip` (`ip`),
  KEY `pollID` (`pollID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_poll_data`
--

DROP TABLE IF EXISTS `nuke_poll_data`;
CREATE TABLE IF NOT EXISTS `nuke_poll_data` (
  `pollID` int(11) NOT NULL DEFAULT '0',
  `optionText` varchar(512) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `optionCount` int(11) NOT NULL DEFAULT '0',
  `voteID` int(11) NOT NULL DEFAULT '0',
  KEY `pollID` (`pollID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_poll_desc`
--

DROP TABLE IF EXISTS `nuke_poll_desc`;
CREATE TABLE IF NOT EXISTS `nuke_poll_desc` (
  `pollID` int(11) NOT NULL AUTO_INCREMENT,
  `pollTitle` varchar(100) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `planguage` varchar(30) NOT NULL DEFAULT '',
  `artid` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pollID`),
  KEY `artid` (`artid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_popsettings`
--

DROP TABLE IF EXISTS `nuke_popsettings`;
CREATE TABLE IF NOT EXISTS `nuke_popsettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `account` varchar(50) DEFAULT '',
  `popserver` varchar(255) DEFAULT '',
  `port` int(5) DEFAULT '0',
  `uname` varchar(100) DEFAULT '',
  `passwd` varchar(20) DEFAULT '',
  `numshow` int(11) DEFAULT '0',
  `deletefromserver` char(1) DEFAULT '',
  `refresh` int(11) DEFAULT '0',
  `timeout` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_priv_msgs`
--

DROP TABLE IF EXISTS `nuke_priv_msgs`;
CREATE TABLE IF NOT EXISTS `nuke_priv_msgs` (
  `msg_id` int(10) NOT NULL AUTO_INCREMENT,
  `msg_image` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `from_userid` int(10) NOT NULL DEFAULT '0',
  `to_userid` int(10) NOT NULL DEFAULT '0',
  `msg_time` varchar(20) DEFAULT NULL,
  `msg_text` text,
  `read_msg` tinyint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`msg_id`),
  KEY `msg_id` (`msg_id`),
  KEY `to_userid` (`to_userid`),
  KEY `from_userid` (`from_userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_public_messages`
--

DROP TABLE IF EXISTS `nuke_public_messages`;
CREATE TABLE IF NOT EXISTS `nuke_public_messages` (
  `mid` int(10) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) NOT NULL DEFAULT '',
  `date` varchar(14) DEFAULT NULL,
  `who` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`mid`),
  KEY `mid` (`mid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_queue`
--

DROP TABLE IF EXISTS `nuke_queue`;
CREATE TABLE IF NOT EXISTS `nuke_queue` (
  `qid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(9) NOT NULL DEFAULT '0',
  `uname` varchar(40) NOT NULL DEFAULT '',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `story` text,
  `storyext` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `topic` varchar(20) NOT NULL DEFAULT '',
  `alanguage` varchar(30) NOT NULL DEFAULT '',
  `source` varchar(1024) NOT NULL,
  `geo_lat` decimal(16,13) NOT NULL,
  `geo_lon` decimal(16,13) NOT NULL,
  PRIMARY KEY (`qid`),
  KEY `uid` (`uid`),
  KEY `uname` (`uname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_admin`
--

DROP TABLE IF EXISTS `nuke_quizz_admin`;
CREATE TABLE IF NOT EXISTS `nuke_quizz_admin` (
  `quizzID` int(11) NOT NULL AUTO_INCREMENT,
  `quizzTitle` varchar(100) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `nbscore` tinyint(9) NOT NULL DEFAULT '10',
  `displayscore` tinyint(1) NOT NULL DEFAULT '0',
  `displayresults` tinyint(1) NOT NULL DEFAULT '0',
  `emailadmin` tinyint(1) NOT NULL DEFAULT '1',
  `comment` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `restrict_user` tinyint(1) NOT NULL DEFAULT '1',
  `log_user` tinyint(1) NOT NULL DEFAULT '1',
  `image` varchar(50) DEFAULT NULL,
  `cid` int(11) NOT NULL DEFAULT '1',
  `contrib` tinyint(1) NOT NULL DEFAULT '1',
  `expire` varchar(16) NOT NULL DEFAULT 'xx-xx-xxxx xx:xx',
  `admemail` varchar(50) DEFAULT NULL,
  `administrator` varchar(50) DEFAULT NULL,
  `conditions` text,
  PRIMARY KEY (`quizzID`),
  KEY `quizzID` (`quizzID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_categories`
--

DROP TABLE IF EXISTS `nuke_quizz_categories`;
CREATE TABLE IF NOT EXISTS `nuke_quizz_categories` (
  `cid` int(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `comment` varchar(255) DEFAULT NULL,
  `image` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_check`
--

DROP TABLE IF EXISTS `nuke_quizz_check`;
CREATE TABLE IF NOT EXISTS `nuke_quizz_check` (
  `ip` varchar(20) DEFAULT NULL,
  `time` varchar(14) NOT NULL DEFAULT '',
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `qid` int(11) NOT NULL DEFAULT '0',
  `score` tinyint(2) NOT NULL DEFAULT '0',
  `answers` varchar(255) NOT NULL DEFAULT '',
  KEY `qid` (`qid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_data`
--

DROP TABLE IF EXISTS `nuke_quizz_data`;
CREATE TABLE IF NOT EXISTS `nuke_quizz_data` (
  `pollID` int(11) NOT NULL DEFAULT '0',
  `optionText` char(50) NOT NULL DEFAULT '',
  `optionCount` int(11) NOT NULL DEFAULT '0',
  `voteID` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_datacontrib`
--

DROP TABLE IF EXISTS `nuke_quizz_datacontrib`;
CREATE TABLE IF NOT EXISTS `nuke_quizz_datacontrib` (
  `pollID` int(11) NOT NULL DEFAULT '0',
  `optionText` char(50) NOT NULL DEFAULT '',
  `optionCount` int(11) NOT NULL DEFAULT '0',
  `voteID` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_desc`
--

DROP TABLE IF EXISTS `nuke_quizz_desc`;
CREATE TABLE IF NOT EXISTS `nuke_quizz_desc` (
  `pollID` int(11) NOT NULL AUTO_INCREMENT,
  `pollTitle` varchar(100) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `qid` tinyint(9) NOT NULL DEFAULT '0',
  `answer` varchar(30) NOT NULL DEFAULT '0',
  `coef` tinyint(3) NOT NULL DEFAULT '1',
  `good` text,
  `bad` text,
  `comment` text,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`pollID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_descontrib`
--

DROP TABLE IF EXISTS `nuke_quizz_descontrib`;
CREATE TABLE IF NOT EXISTS `nuke_quizz_descontrib` (
  `pollID` int(11) NOT NULL AUTO_INCREMENT,
  `pollTitle` varchar(100) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `qid` tinyint(9) NOT NULL DEFAULT '0',
  `answer` varchar(30) NOT NULL DEFAULT '0',
  `coef` tinyint(3) NOT NULL DEFAULT '1',
  `good` text,
  `bad` text,
  `comment` text,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`pollID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_admin`
--

DROP TABLE IF EXISTS `nuke_quiz_admin`;
CREATE TABLE IF NOT EXISTS `nuke_quiz_admin` (
  `quizID` int(11) NOT NULL AUTO_INCREMENT,
  `quizTitle` varchar(150) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `comment` text,
  `image` varchar(50) DEFAULT NULL,
  `cid` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`quizID`),
  KEY `quizzID` (`quizID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_categories`
--

DROP TABLE IF EXISTS `nuke_quiz_categories`;
CREATE TABLE IF NOT EXISTS `nuke_quiz_categories` (
  `cid` int(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `comment` varchar(255) DEFAULT NULL,
  `image` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_check`
--

DROP TABLE IF EXISTS `nuke_quiz_check`;
CREATE TABLE IF NOT EXISTS `nuke_quiz_check` (
  `ip` varchar(20) DEFAULT NULL,
  `time` varchar(14) NOT NULL DEFAULT '',
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `qid` int(11) NOT NULL DEFAULT '0',
  `score` tinyint(2) NOT NULL DEFAULT '0',
  `answers` varchar(255) NOT NULL DEFAULT '',
  KEY `qid` (`qid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_data`
--

DROP TABLE IF EXISTS `nuke_quiz_data`;
CREATE TABLE IF NOT EXISTS `nuke_quiz_data` (
  `pollID` int(11) NOT NULL DEFAULT '0',
  `optionText` char(150) NOT NULL DEFAULT '',
  `optionCount` int(11) NOT NULL DEFAULT '0',
  `voteID` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_desc`
--

DROP TABLE IF EXISTS `nuke_quiz_desc`;
CREATE TABLE IF NOT EXISTS `nuke_quiz_desc` (
  `pollID` int(11) NOT NULL AUTO_INCREMENT,
  `pollTitle` blob NOT NULL,
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `qid` tinyint(9) NOT NULL DEFAULT '0',
  `answer` varchar(30) NOT NULL DEFAULT '0',
  `coef` tinyint(3) NOT NULL DEFAULT '1',
  `good` text,
  `bad` text,
  `comment` text,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`pollID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_index`
--

DROP TABLE IF EXISTS `nuke_quiz_index`;
CREATE TABLE IF NOT EXISTS `nuke_quiz_index` (
  `quizid` int(11) NOT NULL AUTO_INCREMENT,
  `quiztitle` varchar(255) NOT NULL DEFAULT '',
  `quizdesc` text NOT NULL,
  `quizactive` int(1) NOT NULL DEFAULT '0',
  `quizhidden` int(1) NOT NULL DEFAULT '1',
  `quizowner` int(18) NOT NULL DEFAULT '0',
  `quizstatus` int(1) NOT NULL DEFAULT '0',
  `currentuser` int(18) NOT NULL DEFAULT '0',
  `turnexpires` int(30) NOT NULL DEFAULT '0',
  `currquestion` longtext NOT NULL,
  `quizcat` int(5) NOT NULL DEFAULT '1',
  KEY `quizid` (`quizid`),
  FULLTEXT KEY `quiztitle` (`quiztitle`,`quizdesc`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quotes`
--

DROP TABLE IF EXISTS `nuke_quotes`;
CREATE TABLE IF NOT EXISTS `nuke_quotes` (
  `qid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quote` text,
  PRIMARY KEY (`qid`),
  KEY `qid` (`qid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_referer`
--

DROP TABLE IF EXISTS `nuke_referer`;
CREATE TABLE IF NOT EXISTS `nuke_referer` (
  `rid` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`rid`),
  KEY `rid` (`rid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_related`
--

DROP TABLE IF EXISTS `nuke_related`;
CREATE TABLE IF NOT EXISTS `nuke_related` (
  `rid` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(30) NOT NULL DEFAULT '',
  `url` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`rid`),
  KEY `rid` (`rid`),
  KEY `tid` (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews`
--

DROP TABLE IF EXISTS `nuke_reviews`;
CREATE TABLE IF NOT EXISTS `nuke_reviews` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL DEFAULT '0000-00-00',
  `title` varchar(150) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `reviewer` varchar(20) DEFAULT NULL,
  `email` varchar(60) DEFAULT NULL,
  `score` int(10) NOT NULL DEFAULT '0',
  `cover` varchar(100) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '',
  `url_title` varchar(50) NOT NULL DEFAULT '',
  `hits` int(10) NOT NULL DEFAULT '0',
  `rlanguage` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews_add`
--

DROP TABLE IF EXISTS `nuke_reviews_add`;
CREATE TABLE IF NOT EXISTS `nuke_reviews_add` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `reviewer` varchar(20) NOT NULL DEFAULT '',
  `email` varchar(60) DEFAULT NULL,
  `score` int(10) NOT NULL DEFAULT '0',
  `url` varchar(100) NOT NULL DEFAULT '',
  `url_title` varchar(50) NOT NULL DEFAULT '',
  `rlanguage` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews_comments`
--

DROP TABLE IF EXISTS `nuke_reviews_comments`;
CREATE TABLE IF NOT EXISTS `nuke_reviews_comments` (
  `cid` int(10) NOT NULL AUTO_INCREMENT,
  `rid` int(10) NOT NULL DEFAULT '0',
  `userid` varchar(25) NOT NULL DEFAULT '',
  `date` datetime DEFAULT NULL,
  `comments` text,
  `score` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `cid` (`cid`),
  KEY `rid` (`rid`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews_main`
--

DROP TABLE IF EXISTS `nuke_reviews_main`;
CREATE TABLE IF NOT EXISTS `nuke_reviews_main` (
  `title` varchar(100) DEFAULT NULL,
  `description` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_seccont`
--

DROP TABLE IF EXISTS `nuke_seccont`;
CREATE TABLE IF NOT EXISTS `nuke_seccont` (
  `artid` int(11) NOT NULL AUTO_INCREMENT,
  `secid` int(11) NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `content` text NOT NULL,
  `counter` int(11) NOT NULL DEFAULT '0',
  `slanguage` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`artid`),
  KEY `artid` (`artid`),
  KEY `secid` (`secid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_sections`
--

DROP TABLE IF EXISTS `nuke_sections`;
CREATE TABLE IF NOT EXISTS `nuke_sections` (
  `secid` int(11) NOT NULL AUTO_INCREMENT,
  `secname` varchar(40) NOT NULL DEFAULT '',
  `image` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`secid`),
  KEY `secid` (`secid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_session`
--

DROP TABLE IF EXISTS `nuke_session`;
CREATE TABLE IF NOT EXISTS `nuke_session` (
  `uname` varchar(25) NOT NULL DEFAULT '',
  `time` varchar(14) NOT NULL DEFAULT '',
  `host_addr` varchar(48) NOT NULL DEFAULT '',
  `guest` int(1) NOT NULL DEFAULT '0',
  KEY `time` (`time`),
  KEY `guest` (`guest`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_sommaire`
--

DROP TABLE IF EXISTS `nuke_sommaire`;
CREATE TABLE IF NOT EXISTS `nuke_sommaire` (
  `groupmenu` int(2) NOT NULL DEFAULT '0',
  `name` varchar(200) DEFAULT NULL,
  `image` varchar(99) DEFAULT NULL,
  `lien` text,
  `hr` char(2) DEFAULT NULL,
  `center` char(2) DEFAULT NULL,
  `bgcolor` tinytext,
  `invisible` int(1) DEFAULT NULL,
  `class` tinytext,
  PRIMARY KEY (`groupmenu`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_sommaire_categories`
--

DROP TABLE IF EXISTS `nuke_sommaire_categories`;
CREATE TABLE IF NOT EXISTS `nuke_sommaire_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupmenu` int(2) NOT NULL DEFAULT '0',
  `module` varchar(50) NOT NULL DEFAULT '',
  `url` text NOT NULL,
  `url_text` text NOT NULL,
  `image` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_spelling_words`
--

DROP TABLE IF EXISTS `nuke_spelling_words`;
CREATE TABLE IF NOT EXISTS `nuke_spelling_words` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `word` varchar(30) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
  `sound` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`),
  KEY `sound` (`sound`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_staff`
--

DROP TABLE IF EXISTS `nuke_staff`;
CREATE TABLE IF NOT EXISTS `nuke_staff` (
  `id` int(3) NOT NULL DEFAULT '0',
  `sid` int(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `des` mediumtext NOT NULL,
  `rank` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `photo` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`sid`),
  UNIQUE KEY `sid` (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_staff_cat`
--

DROP TABLE IF EXISTS `nuke_staff_cat`;
CREATE TABLE IF NOT EXISTS `nuke_staff_cat` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stats_date`
--

DROP TABLE IF EXISTS `nuke_stats_date`;
CREATE TABLE IF NOT EXISTS `nuke_stats_date` (
  `year` smallint(6) NOT NULL DEFAULT '0',
  `month` tinyint(4) NOT NULL DEFAULT '0',
  `date` tinyint(4) NOT NULL DEFAULT '0',
  `hits` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stats_hour`
--

DROP TABLE IF EXISTS `nuke_stats_hour`;
CREATE TABLE IF NOT EXISTS `nuke_stats_hour` (
  `year` smallint(6) NOT NULL DEFAULT '0',
  `month` tinyint(4) NOT NULL DEFAULT '0',
  `date` tinyint(4) NOT NULL DEFAULT '0',
  `hour` tinyint(4) NOT NULL DEFAULT '0',
  `hits` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stats_month`
--

DROP TABLE IF EXISTS `nuke_stats_month`;
CREATE TABLE IF NOT EXISTS `nuke_stats_month` (
  `year` smallint(6) NOT NULL DEFAULT '0',
  `month` tinyint(4) NOT NULL DEFAULT '0',
  `hits` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stats_year`
--

DROP TABLE IF EXISTS `nuke_stats_year`;
CREATE TABLE IF NOT EXISTS `nuke_stats_year` (
  `year` smallint(6) NOT NULL DEFAULT '0',
  `hits` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stories`
--

DROP TABLE IF EXISTS `nuke_stories`;
CREATE TABLE IF NOT EXISTS `nuke_stories` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `approved` tinyint(1) NOT NULL DEFAULT '1',
  `catid` int(11) NOT NULL DEFAULT '0',
  `aid` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `hometext` mediumtext COLLATE utf8_unicode_ci,
  `bodytext` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `lead` text COLLATE utf8_unicode_ci,
  `paragraphs` longtext COLLATE utf8_unicode_ci,
  `comments` int(11) DEFAULT '0',
  `counter` mediumint(8) unsigned DEFAULT NULL,
  `weeklycounter` int(11) NOT NULL DEFAULT '0',
  `topic` int(3) NOT NULL DEFAULT '1',
  `informant` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `notes` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `ihome` int(1) NOT NULL DEFAULT '0',
  `alanguage` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `acomm` int(1) NOT NULL DEFAULT '0',
  `haspoll` int(1) NOT NULL DEFAULT '0',
  `pollID` int(10) NOT NULL DEFAULT '0',
  `score` int(10) NOT NULL DEFAULT '0',
  `ratings` int(10) NOT NULL DEFAULT '0',
  `associated` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `ForumThreadID` int(11) DEFAULT NULL,
  `source` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `story_time` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `geo_lat` decimal(16,13) NOT NULL,
  `geo_lon` decimal(16,13) NOT NULL,
  `sent_to_fb` tinyint(1) NOT NULL DEFAULT '0',
  `slug` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `featured_image` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`sid`),
  KEY `catid` (`catid`),
  KEY `counter` (`counter`),
  KEY `topic` (`topic`),
  KEY `approved` (`approved`),
  KEY `aid` (`aid`),
  KEY `ForumThreadID` (`ForumThreadID`),
  KEY `user_id` (`user_id`),
  KEY `staff_id` (`staff_id`),
  KEY `time` (`time`),
  KEY `weeklycounter` (`weeklycounter`),
  KEY `informant` (`informant`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stories_cat`
--

DROP TABLE IF EXISTS `nuke_stories_cat`;
CREATE TABLE IF NOT EXISTS `nuke_stories_cat` (
  `catid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL DEFAULT '',
  `counter` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`catid`),
  KEY `catid` (`catid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_topics`
--

DROP TABLE IF EXISTS `nuke_topics`;
CREATE TABLE IF NOT EXISTS `nuke_topics` (
  `topicid` int(3) NOT NULL AUTO_INCREMENT,
  `topicname` varchar(20) DEFAULT NULL,
  `topicimage` varchar(20) DEFAULT NULL,
  `topictext` varchar(40) DEFAULT NULL,
  `counter` int(11) NOT NULL DEFAULT '0',
  `desc` mediumtext NOT NULL,
  PRIMARY KEY (`topicid`),
  KEY `topicname` (`topicname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_upermissions`
--

DROP TABLE IF EXISTS `nuke_upermissions`;
CREATE TABLE IF NOT EXISTS `nuke_upermissions` (
  `pid` int(16) NOT NULL AUTO_INCREMENT,
  `uid` int(16) NOT NULL DEFAULT '0',
  `pmodule` varchar(255) NOT NULL DEFAULT '',
  KEY `pid` (`pid`),
  FULLTEXT KEY `pmodule` (`pmodule`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users`
--

DROP TABLE IF EXISTS `nuke_users`;
CREATE TABLE IF NOT EXISTS `nuke_users` (
  `user_id` int(10) NOT NULL AUTO_INCREMENT,
  `provider` enum('railpage','facebook','twitter','google') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'railpage',
  `user_active` tinyint(1) DEFAULT '1',
  `username` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_password_bcrypt` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_session_time` int(11) NOT NULL DEFAULT '0',
  `user_session_page` smallint(5) NOT NULL DEFAULT '0',
  `user_lastvisit` int(11) NOT NULL DEFAULT '0',
  `user_regdate` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `user_regdate_nice` date NOT NULL,
  `user_level` tinyint(4) DEFAULT '0',
  `user_posts` int(8) NOT NULL DEFAULT '0',
  `user_timezone` decimal(5,2) NOT NULL DEFAULT '0.00',
  `user_style` tinyint(4) DEFAULT NULL,
  `user_lang` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_dateformat` varchar(14) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'd M Y H:i',
  `user_new_privmsg` smallint(5) unsigned NOT NULL DEFAULT '0',
  `user_unread_privmsg` smallint(5) unsigned NOT NULL DEFAULT '0',
  `user_last_privmsg` int(11) NOT NULL DEFAULT '0',
  `user_emailtime` int(11) DEFAULT NULL,

  `user_viewemail` tinyint(1) DEFAULT NULL,
  `user_attachsig` tinyint(1) DEFAULT '0',
  `user_showsigs` tinyint(1) NOT NULL DEFAULT '0',
  `user_allowhtml` tinyint(1) DEFAULT '0',
  `user_allowbbcode` tinyint(1) DEFAULT '1',
  `user_allowsmile` tinyint(1) DEFAULT '1',
  `user_allowavatar` tinyint(1) NOT NULL DEFAULT '1',
  `user_allow_pm` tinyint(1) NOT NULL DEFAULT '1',
  `user_allow_viewonline` tinyint(1) NOT NULL DEFAULT '1',
  `user_notify` tinyint(1) NOT NULL DEFAULT '0',
  `user_notify_pm` tinyint(1) NOT NULL DEFAULT '1',
  `user_popup_pm` tinyint(1) NOT NULL DEFAULT '0',
  `user_rank` int(11) DEFAULT NULL,
  `user_avatar` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_avatar_width` int(11) DEFAULT NULL,
  `user_avatar_height` int(11) DEFAULT NULL,
  `user_avatar_type` tinyint(4) NOT NULL DEFAULT '0',
  `user_avatar_gravatar` tinyint(1) DEFAULT '1',
  `user_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_icq` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_website` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_from` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_sig` mediumtext COLLATE utf8_unicode_ci,
  `user_sig_bbcode_uid` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_aim` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_yim` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_msnm` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_occ` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_interests` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_actkey` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_newpasswd` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `femail` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `storynum` tinyint(4) NOT NULL DEFAULT '10',
  `umode` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `uorder` tinyint(1) NOT NULL DEFAULT '0',
  `thold` tinyint(1) NOT NULL DEFAULT '0',
  `noscore` tinyint(1) NOT NULL DEFAULT '0',
  `bio` text COLLATE utf8_unicode_ci NOT NULL,
  `ublockon` tinyint(1) NOT NULL DEFAULT '0',
  `ublock` text COLLATE utf8_unicode_ci NOT NULL,
  `theme` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `commentmax` int(11) NOT NULL DEFAULT '4096',
  `counter` int(11) NOT NULL DEFAULT '0',
  `newsletter` int(1) NOT NULL DEFAULT '0',
  `broadcast` tinyint(1) NOT NULL DEFAULT '1',
  `popmeson` tinyint(1) NOT NULL DEFAULT '0',
  `user_warnlevel` int(3) NOT NULL DEFAULT '0',
  `user_group_cp` int(11) NOT NULL DEFAULT '2',
  `user_group_list_cp` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '2',
  `user_active_cp` enum('YES','NO') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'YES',
  `user_lastvisit_cp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_regdate_cp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `disallow_mod_warn` tinyint(1) DEFAULT '0',
  `user_current_visit` int(11) NOT NULL DEFAULT '0',
  `user_last_visit` int(11) NOT NULL DEFAULT '0',
  `user_gallery` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_allow_arcadepm` tinyint(4) NOT NULL DEFAULT '1',
  `last_session_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_session_cslh` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_session_ignore` tinyint(1) DEFAULT '0',
  `user_timestate` varchar(10) COLLATE utf8_unicode_ci DEFAULT 'MAN',
  `user_report_optout` tinyint(1) NOT NULL DEFAULT '0',
  `uWheat` int(11) NOT NULL DEFAULT '0',
  `uChaff` int(11) NOT NULL DEFAULT '0',
  `user_forum_postsperpage` smallint(3) NOT NULL DEFAULT '25',
  `api_key` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_secret` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flickr_oauth_token` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flickr_oauth_token_secret` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flickr_nsid` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flickr_username` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timezone` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_enablerte` tinyint(1) NOT NULL DEFAULT '1',
  `user_enableglossary` tinyint(1) NOT NULL DEFAULT '0',
  `user_enableautologin` tinyint(1) NOT NULL DEFAULT '1',
  `user_enablessl` tinyint(1) NOT NULL DEFAULT '0',
  `oauth_consumer_id` int(11) NOT NULL,
  `sidebar_type` smallint(6) NOT NULL DEFAULT '2',
  `facebook_user_id` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `reported_to_sfs` tinyint(1) NOT NULL DEFAULT '0',
  `user_opts` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `meta` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `user_session_time` (`user_session_time`),
  KEY `username` (`username`),
  KEY `user_enablerte` (`user_enablerte`),
  KEY `api_secret` (`api_secret`(255)),
  KEY `user_active` (`user_active`),
  KEY `user_lastvisit` (`user_lastvisit`),
  KEY `oauth_consumer_id` (`oauth_consumer_id`),
  KEY `reported_to_sfs` (`reported_to_sfs`),
  KEY `user_regdate_nice` (`user_regdate_nice`),
  KEY `provider` (`provider`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci PACK_KEYS=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_autologin`
--

DROP TABLE IF EXISTS `nuke_users_autologin`;
CREATE TABLE IF NOT EXISTS `nuke_users_autologin` (
  `autologin_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `autologin_token` varchar(128) NOT NULL,
  `autologin_time` int(11) NOT NULL,
  `autologin_expire` int(11) NOT NULL,
  `autologin_last` int(11) NOT NULL,
  `autologin_ip` varchar(128) NOT NULL,
  `autologin_hostname` varchar(256) NOT NULL,
  PRIMARY KEY (`autologin_id`),
  KEY `autologin_last` (`autologin_last`),
  KEY `user_id` (`user_id`),
  KEY `autologin_expire` (`autologin_expire`),
  KEY `autologin_time` (`autologin_time`),
  KEY `autologin_token` (`autologin_token`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_groups`
--

DROP TABLE IF EXISTS `nuke_users_groups`;
CREATE TABLE IF NOT EXISTS `nuke_users_groups` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `gname` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`gid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_groups_users`
--

DROP TABLE IF EXISTS `nuke_users_groups_users`;
CREATE TABLE IF NOT EXISTS `nuke_users_groups_users` (
  `gid` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `mname` varchar(25) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `sdate` date NOT NULL DEFAULT '0000-00-00',
  `edate` date NOT NULL DEFAULT '0000-00-00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_hash`
--

DROP TABLE IF EXISTS `nuke_users_hash`;
CREATE TABLE IF NOT EXISTS `nuke_users_hash` (
  `user_id` int(11) NOT NULL,
  `hash` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) NOT NULL,
  `ip` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  KEY `user_id` (`user_id`,`hash`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_notes`
--

DROP TABLE IF EXISTS `nuke_users_notes`;
CREATE TABLE IF NOT EXISTS `nuke_users_notes` (
  `nid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `aid` int(11) NOT NULL DEFAULT '0',
  `datetime` int(11) NOT NULL DEFAULT '0',
  `data` mediumtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`nid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_temp`
--

DROP TABLE IF EXISTS `nuke_users_temp`;
CREATE TABLE IF NOT EXISTS `nuke_users_temp` (
  `user_id` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(25) NOT NULL DEFAULT '',
  `user_email` varchar(255) NOT NULL DEFAULT '',
  `user_password` varchar(40) NOT NULL DEFAULT '',
  `user_regdate` varchar(20) NOT NULL DEFAULT '',
  `check_num` varchar(50) NOT NULL DEFAULT '',
  `time` varchar(14) NOT NULL DEFAULT '',
  `email_sent` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer`
--

DROP TABLE IF EXISTS `oauth_consumer`;
CREATE TABLE IF NOT EXISTS `oauth_consumer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumer_key` varchar(250) NOT NULL,
  `consumer_secret` varchar(250) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `dateadded` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `consumer_key` (`consumer_key`),
  KEY `consumer_secret` (`consumer_secret`),
  KEY `active` (`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_nonce`
--

DROP TABLE IF EXISTS `oauth_consumer_nonce`;
CREATE TABLE IF NOT EXISTS `oauth_consumer_nonce` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumer_id` int(11) NOT NULL,
  `timestamp` bigint(20) NOT NULL,
  `nonce` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_token`
--

DROP TABLE IF EXISTS `oauth_token`;
CREATE TABLE IF NOT EXISTS `oauth_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `token` varchar(250) NOT NULL,
  `token_secret` varchar(250) NOT NULL,
  `callback_url` varchar(250) NOT NULL,
  `verifier` varchar(250) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_token_type`
--

DROP TABLE IF EXISTS `oauth_token_type`;
CREATE TABLE IF NOT EXISTS `oauth_token_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `operators`
--

DROP TABLE IF EXISTS `operators`;
CREATE TABLE IF NOT EXISTS `operators` (
  `operator_id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_name` varchar(128) NOT NULL,
  `operator_desc` text NOT NULL,
  `organisation_id` int(11) NOT NULL,
  PRIMARY KEY (`operator_id`),
  KEY `organisation_id` (`organisation_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `organisation`
--

DROP TABLE IF EXISTS `organisation`;
CREATE TABLE IF NOT EXISTS `organisation` (
  `organisation_id` int(10) NOT NULL AUTO_INCREMENT,
  `organisation_name` text CHARACTER SET latin1 NOT NULL,
  `organisation_desc` text CHARACTER SET latin1,
  `organisation_dateadded` int(12) NOT NULL,
  `organisation_owner` int(10) DEFAULT NULL,
  `organisation_website` text CHARACTER SET latin1,
  `organisation_phone` text CHARACTER SET latin1,
  `organisation_fax` text CHARACTER SET latin1,
  `organisation_email` text CHARACTER SET latin1,
  `organisation_logo` varchar(2048) NOT NULL,
  `flickr_photo_id` varchar(512) NOT NULL,
  `organisation_slug` varchar(128) NOT NULL,
  PRIMARY KEY (`organisation_id`),
  KEY `organisation_owner` (`organisation_owner`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `organisation_member`
--

DROP TABLE IF EXISTS `organisation_member`;
CREATE TABLE IF NOT EXISTS `organisation_member` (
  `organisation_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  KEY `organisation_id` (`organisation_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `organisation_roles`
--

DROP TABLE IF EXISTS `organisation_roles`;
CREATE TABLE IF NOT EXISTS `organisation_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` text NOT NULL,
  `organisation_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`),
  KEY `organisation_id` (`organisation_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_actions`
--

DROP TABLE IF EXISTS `phpbb_reports_actions`;
CREATE TABLE IF NOT EXISTS `phpbb_reports_actions` (
  `action_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `action_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `action_time` int(11) NOT NULL DEFAULT '0',
  `action` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `action_comments` text CHARACTER SET latin1,
  `action_status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`action_id`),
  KEY `report_id` (`report_id`),
  KEY `action_user_id` (`action_user_id`),
  KEY `action_status` (`action_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_config`
--

DROP TABLE IF EXISTS `phpbb_reports_config`;
CREATE TABLE IF NOT EXISTS `phpbb_reports_config` (
  `config_name` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `config_value` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  PRIMARY KEY (`config_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_data`
--

DROP TABLE IF EXISTS `phpbb_reports_data`;
CREATE TABLE IF NOT EXISTS `phpbb_reports_data` (
  `data_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `data_name` varchar(30) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `data_desc` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `data_comments` tinyint(1) NOT NULL DEFAULT '0',
  `data_order` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `data_code` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`data_id`),
  KEY `data_code` (`data_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_posts`
--

DROP TABLE IF EXISTS `phpbb_reports_posts`;
CREATE TABLE IF NOT EXISTS `phpbb_reports_posts` (
  `report_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `poster_id` mediumint(8) NOT NULL DEFAULT '0',
  `report_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `report_time` int(11) NOT NULL DEFAULT '0',
  `report_reason` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `report_comments` text CHARACTER SET latin1,
  `report_status` tinyint(1) NOT NULL DEFAULT '0',
  `report_action_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`report_id`),
  KEY `report_user_id` (`report_user_id`),
  KEY `report_status` (`report_status`),
  KEY `post_id` (`post_id`),
  KEY `poster_id` (`poster_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_warnings`
--

DROP TABLE IF EXISTS `phpbb_warnings`;
CREATE TABLE IF NOT EXISTS `phpbb_warnings` (
  `warn_id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30) NOT NULL DEFAULT '0',
  `warned_by` int(30) NOT NULL DEFAULT '0',
  `warn_reason` text CHARACTER SET latin1,
  `mod_comments` text CHARACTER SET latin1,
  `actiontaken` text CHARACTER SET latin1,
  `warn_date` int(30) NOT NULL DEFAULT '0',
  `old_warning_level` int(11) NOT NULL,
  `new_warning_level` int(11) NOT NULL,
  PRIMARY KEY (`warn_id`),
  KEY `warn_id` (`warn_id`),
  KEY `user_id` (`user_id`),
  KEY `warned_by` (`warned_by`),
  KEY `warn_date` (`warn_date`),
  KEY `old_warning_level` (`old_warning_level`),
  KEY `new_warning_level` (`new_warning_level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

DROP TABLE IF EXISTS `polls`;
CREATE TABLE IF NOT EXISTS `polls` (
  `poll_id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_name` varchar(32) NOT NULL,
  `poll_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `poll_votes` int(11) NOT NULL,
  `poll_options` text NOT NULL,
  PRIMARY KEY (`poll_id`),
  KEY `poll_votes` (`poll_votes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `popover_viewed`
--

DROP TABLE IF EXISTS `popover_viewed`;
CREATE TABLE IF NOT EXISTS `popover_viewed` (
  `popover_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  KEY `popover_id` (`popover_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `privmsgs_hidelist`
--

DROP TABLE IF EXISTS `privmsgs_hidelist`;
CREATE TABLE IF NOT EXISTS `privmsgs_hidelist` (
  `privmsgs_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `privmsgs_id` (`privmsgs_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `railcams`
--

DROP TABLE IF EXISTS `railcams`;
CREATE TABLE IF NOT EXISTS `railcams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `permalink` varchar(128) NOT NULL,
  `lat` decimal(16,13) NOT NULL,
  `lon` decimal(16,13) NOT NULL,
  `name` varchar(512) NOT NULL,
  `desc` text NOT NULL,
  `nsid` varchar(32) NOT NULL,
  `route_id` int(11) NOT NULL,
  `timezone` varchar(128) NOT NULL DEFAULT 'Australia/Melbourne',
  `flickr_oauth_token` varchar(256) NOT NULL,
  `flickr_oauth_secret` varchar(256) NOT NULL,
  `video_store_url` varchar(1024) NOT NULL,
  `live_image_url` varchar(1024) NOT NULL,
  `live_video_url` varchar(1024) NOT NULL,
  `left` varchar(128) NOT NULL COMMENT 'What is to the left of camera',
  `right` varchar(128) NOT NULL COMMENT 'What is to the right of camera',
  `provider` enum('Flickr') NOT NULL DEFAULT 'Flickr',
  PRIMARY KEY (`id`),
  KEY `permalink` (`permalink`),
  KEY `lat` (`lat`),
  KEY `lon` (`lon`),
  KEY `route_id` (`route_id`),
  KEY `nsid` (`nsid`),
  KEY `type_id` (`type_id`),
  KEY `provider` (`provider`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `railcams_type`
--

DROP TABLE IF EXISTS `railcams_type`;
CREATE TABLE IF NOT EXISTS `railcams_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `slug` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `rating_loco`
--

DROP TABLE IF EXISTS `rating_loco`;
CREATE TABLE IF NOT EXISTS `rating_loco` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `loco_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` float NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rating_id`),
  KEY `rating_id` (`rating_id`,`loco_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(16) NOT NULL,
  `namespace` varchar(32) NOT NULL,
  `object` varchar(32) NOT NULL,
  `object_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reminder` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `dispatched` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `title` text NOT NULL,
  `text` text NOT NULL,
  `sent` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `module` (`module`,`object`,`object_id`,`user_id`,`reminder`),
  KEY `sent` (`sent`),
  KEY `dispatched` (`dispatched`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `route`
--

DROP TABLE IF EXISTS `route`;
CREATE TABLE IF NOT EXISTS `route` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(12) NOT NULL DEFAULT 'AU',
  `region` varchar(12) NOT NULL DEFAULT 'QLD',
  `slug` varchar(128) NOT NULL,
  `name` varchar(512) NOT NULL,
  `orig_name` varchar(128) NOT NULL,
  `desc` text NOT NULL,
  `hexcolour` varchar(7) NOT NULL DEFAULT '#000000',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `source` varchar(32) NOT NULL DEFAULT 'gtfs',
  `gtfs_route_id` varchar(32) NOT NULL,
  `download_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`),
  KEY `active` (`active`),
  KEY `gtfs_route_id` (`gtfs_route_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `route_markers`
--

DROP TABLE IF EXISTS `route_markers`;
CREATE TABLE IF NOT EXISTS `route_markers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) NOT NULL,
  `lat` varchar(256) NOT NULL,
  `lon` varchar(256) NOT NULL,
  `name` varchar(1024) NOT NULL,
  `timing` tinyint(1) NOT NULL DEFAULT '0',
  `route_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `route_id` (`route_id`),
  KEY `path_id` (`path_id`),
  KEY `weight` (`weight`),
  KEY `lat` (`lat`(255)),
  KEY `lon` (`lon`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `route_markers_tmp`
--

DROP TABLE IF EXISTS `route_markers_tmp`;
CREATE TABLE IF NOT EXISTS `route_markers_tmp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) NOT NULL,
  `lat` varchar(256) NOT NULL,
  `lon` varchar(256) NOT NULL,
  `name` varchar(1024) NOT NULL,
  `timing` tinyint(1) NOT NULL DEFAULT '0',
  `route_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `sighting`
--

DROP TABLE IF EXISTS `sighting`;
CREATE TABLE IF NOT EXISTS `sighting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timezone` varchar(64) NOT NULL DEFAULT 'Australia/Melbourne',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_added` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lat` decimal(11,8) NOT NULL,
  `lon` decimal(11,8) NOT NULL,
  `text` varchar(2048) NOT NULL,
  `traincode` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


-- --------------------------------------------------------

--
-- Table structure for table `sighting_locos`
--

DROP TABLE IF EXISTS `sighting_locos`;
CREATE TABLE IF NOT EXISTS `sighting_locos` (
  `sighting_id` int(11) NOT NULL,
  `loco_id` int(11) NOT NULL,
  KEY `loco_id` (`loco_id`),
  KEY `sighting_id` (`sighting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `source`
--

DROP TABLE IF EXISTS `source`;
CREATE TABLE IF NOT EXISTS `source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `desc` text NOT NULL,
  `url` varchar(512) NOT NULL,
  `image` varchar(512) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `sph_counter`
--

DROP TABLE IF EXISTS `sph_counter`;
CREATE TABLE IF NOT EXISTS `sph_counter` (
  `counter_id` int(11) NOT NULL,
  `max_doc_id` int(11) NOT NULL,
  PRIMARY KEY (`counter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
CREATE TABLE IF NOT EXISTS `tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(128) NOT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_link`
--

DROP TABLE IF EXISTS `tag_link`;
CREATE TABLE IF NOT EXISTS `tag_link` (
  `tag_link_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  KEY `tag_link_id` (`tag_link_id`),
  KEY `tag_id` (`tag_id`),
  KEY `story_id` (`story_id`),
  KEY `topic_id` (`topic_id`),
  KEY `post_id` (`post_id`),
  KEY `photo_id` (`photo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_entries`
--

DROP TABLE IF EXISTS `timetable_entries`;
CREATE TABLE IF NOT EXISTS `timetable_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `point_id` int(11) NOT NULL,
  `expires` date NOT NULL DEFAULT '0000-00-00',
  `starts` date NOT NULL DEFAULT '0000-00-00',
  `train_id` int(10) NOT NULL,
  `day` int(11) NOT NULL,
  `time` time NOT NULL,
  `going` enum('arr','dep') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `point_id` (`point_id`),
  KEY `train_id` (`train_id`),
  KEY `day` (`day`,`time`,`going`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_points`
--

DROP TABLE IF EXISTS `timetable_points`;
CREATE TABLE IF NOT EXISTS `timetable_points` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `lat` double(16,13) NOT NULL,
  `lon` double(16,13) NOT NULL,
  `route_id` int(10) NOT NULL DEFAULT '0',
  `slug` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `route_id` (`route_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_regions`
--

DROP TABLE IF EXISTS `timetable_regions`;
CREATE TABLE IF NOT EXISTS `timetable_regions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `state` varchar(12) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_trains`
--

DROP TABLE IF EXISTS `timetable_trains`;
CREATE TABLE IF NOT EXISTS `timetable_trains` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `provider` enum('artc','pbr') NOT NULL DEFAULT 'artc',
  `train_number` varchar(128) NOT NULL,
  `train_name` varchar(512) NOT NULL,
  `train_desc` text NOT NULL,
  `operator_id` int(10) NOT NULL,
  `gauge_id` int(10) NOT NULL,
  `meta` text NOT NULL,
  `commodity` int(11) NOT NULL,
  `slug` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `operator_id` (`operator_id`),
  KEY `provider` (`provider`),
  KEY `commodity` (`commodity`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `viewed_threads`
--

DROP TABLE IF EXISTS `viewed_threads`;
CREATE TABLE IF NOT EXISTS `viewed_threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `topic_id_index` (`topic_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `waynet`
--

DROP TABLE IF EXISTS `waynet`;
CREATE TABLE IF NOT EXISTS `waynet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainnum` varchar(12) NOT NULL,
  `loco` varchar(12) NOT NULL,
  `linekms` varchar(12) NOT NULL,
  `linename` varchar(64) NOT NULL,
  `lineid` int(11) NOT NULL,
  `lat` varchar(32) NOT NULL,
  `lon` varchar(32) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trainnum` (`trainnum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wheel_arrangements`
--

DROP TABLE IF EXISTS `wheel_arrangements`;
CREATE TABLE IF NOT EXISTS `wheel_arrangements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `arrangement` varchar(256) NOT NULL,
  `slug` varchar(32) NOT NULL,
  `image` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

