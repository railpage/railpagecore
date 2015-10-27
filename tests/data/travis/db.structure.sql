-- phpMyAdmin SQL Dump
-- version 4.2.12deb2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 27, 2015 at 10:49 AM
-- Server version: 10.0.21-MariaDB-1~jessie-log
-- PHP Version: 5.6.9-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sparta_unittest`
--
CREATE DATABASE IF NOT EXISTS `sparta_unittest` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `sparta_unittest`;

DELIMITER $$
--
-- Procedures
--
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

CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `newscounters`()
BEGIN
UPDATE nuke_stories SET weeklycounter = 0;
END$$

CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `PopulateLocoClass`(IN loco_number VARCHAR(8), IN loco_number_last VARCHAR(8), IN loco_class_id INT, IN loco_gauge_id INT, IN loco_status_id INT, IN loco_manufacturer_id INT, IN prefix TEXT)
BEGIN

simple_loop: LOOP

SET @num_length := CHAR_LENGTH(loco_number);

INSERT INTO `sparta_unittest`.`loco_unit` (`loco_id`, `loco_num`, `loco_name`, `loco_gauge`, `loco_gauge_id`, `loco_status_id`, `class_id`, `owner_id`, `operator_id`, `date_added`, `date_modified`, `entered_service`, `withdrawn`, `builders_number`, `photo_id`, `manufacturer_id`) VALUES (NULL, CONCAT(prefix, loco_number), '', '', loco_gauge_id, loco_status_id, loco_class_id, '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '0', '0', '', '0', loco_manufacturer_id);

SET loco_number = lpad(loco_number + 1, @num_length, 0);

IF loco_number > loco_number_last THEN
LEAVE simple_loop;
END IF;

END LOOP simple_loop;
END$$

CREATE DEFINER=`mgreenhill`@`%` PROCEDURE `PopulateLocoOrgs`(IN LOCO_CLASS_ID INT, IN LOCO_OPERATOR_ID INT, IN LOCO_LINK_WEIGHT INT, IN LOCO_LINK_TYPE INT)
BEGIN

INSERT INTO `sparta_unittest`.`loco_org_link` (`loco_id`, `operator_id`, `link_type`, `link_weight`) SELECT `loco_id`, LOCO_OPERATOR_ID, LOCO_LINK_TYPE, LOCO_LINK_WEIGHT FROM `sparta_unittest`.`loco_unit` WHERE `class_id` = LOCO_CLASS_ID AND `loco_id` NOT IN (SELECT `loco_id` FROM `sparta_unittest`.`loco_org_link` WHERE `operator_id` = LOCO_OPERATOR_ID AND `link_type` = LOCO_LINK_TYPE);

END$$

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

CREATE TABLE IF NOT EXISTS `api` (
  `api_key` varchar(128) NOT NULL,
  `api_secret` varchar(128) NOT NULL,
  `api_name` varchar(128) NOT NULL,
  `api_date` int(10) NOT NULL,
  `api_active` tinyint(1) NOT NULL,
  `user_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `asset`
--

CREATE TABLE IF NOT EXISTS `asset` (
`id` int(11) NOT NULL,
  `hash` varchar(32) NOT NULL,
  `type_id` int(11) NOT NULL,
  `meta` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=219 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `asset_bak`
--

CREATE TABLE IF NOT EXISTS `asset_bak` (
`id` int(11) NOT NULL,
  `hash` varchar(32) DEFAULT NULL,
  `namespace` varchar(256) NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `meta` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `asset_link`
--

CREATE TABLE IF NOT EXISTS `asset_link` (
`asset_link_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `namespace` varchar(256) NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `asset_type`
--

CREATE TABLE IF NOT EXISTS `asset_type` (
`id` int(11) NOT NULL,
  `name` text NOT NULL,
  `type` enum('video','photo','website','document','diagram') NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bancontrol`
--

CREATE TABLE IF NOT EXISTS `bancontrol` (
`id` int(11) NOT NULL,
  `user_id` int(10) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `ban_active` tinyint(1) NOT NULL,
  `ban_time` int(12) NOT NULL,
  `ban_expire` int(12) NOT NULL,
  `ban_reason` text NOT NULL,
  `banned_by` int(12) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=12208 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `ban_domains`
--

CREATE TABLE IF NOT EXISTS `ban_domains` (
`domain_id` int(12) NOT NULL,
  `domain_name` varchar(256) NOT NULL,
  `ban_date` int(12) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `cache_woe`
--

CREATE TABLE IF NOT EXISTS `cache_woe` (
`id` int(11) NOT NULL,
  `response` longtext NOT NULL,
  `expiry` date NOT NULL,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `chronicle_item`
--

CREATE TABLE IF NOT EXISTS `chronicle_item` (
`id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  `type_id` int(11) NOT NULL,
  `blurb` text NOT NULL,
  `text` text NOT NULL,
  `point` point NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '45',
  `meta` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3542 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `chronicle_link`
--

CREATE TABLE IF NOT EXISTS `chronicle_link` (
`id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `module` varchar(12) NOT NULL,
  `object` varchar(64) NOT NULL,
  `object_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3542 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `chronicle_type`
--

CREATE TABLE IF NOT EXISTS `chronicle_type` (
`id` int(11) NOT NULL,
  `grouping` enum('Locos','Locations','Other') NOT NULL,
  `text` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
`id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `key` varchar(256) NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` varchar(2048) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `download_categories`
--

CREATE TABLE IF NOT EXISTS `download_categories` (
`category_id` int(11) NOT NULL,
  `category_title` varchar(50) NOT NULL DEFAULT '',
  `category_description` mediumtext NOT NULL,
  `parentid` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `download_hits`
--

CREATE TABLE IF NOT EXISTS `download_hits` (
`id` int(11) NOT NULL,
  `download_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `remote_addr` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `download_items`
--

CREATE TABLE IF NOT EXISTS `download_items` (
`id` int(11) NOT NULL,
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
  `extra_data` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=874 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE IF NOT EXISTS `event` (
`id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `meta` longtext NOT NULL,
  `lat` double(16,13) NOT NULL,
  `lon` double(16,13) NOT NULL,
  `category_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `user_id` int(11) NOT NULL DEFAULT '45'
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

CREATE TABLE IF NOT EXISTS `event_categories` (
`id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `slug` varchar(16) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `event_dates`
--

CREATE TABLE IF NOT EXISTS `event_dates` (
`id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  `meta` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL DEFAULT '45'
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE IF NOT EXISTS `feedback` (
  `assigned_to` int(11) NOT NULL,
`id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(256) NOT NULL,
  `email` varchar(256) NOT NULL,
  `area` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_area`
--

CREATE TABLE IF NOT EXISTS `feedback_area` (
`feedback_id` int(11) NOT NULL,
  `feedback_title` varchar(256) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_status`
--

CREATE TABLE IF NOT EXISTS `feedback_status` (
`id` int(11) NOT NULL,
  `name` varchar(1024) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_cache`
--

CREATE TABLE IF NOT EXISTS `flickr_cache` (
  `request` char(35) NOT NULL,
  `response` longtext NOT NULL,
  `expiration` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=0;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_favourites`
--

CREATE TABLE IF NOT EXISTS `flickr_favourites` (
  `photo_id` varchar(24) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_geodata`
--

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
  `size8_h` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=0;

-- --------------------------------------------------------

--
-- Table structure for table `flickr_rating`
--

CREATE TABLE IF NOT EXISTS `flickr_rating` (
`id` int(11) NOT NULL,
  `photo_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `fwlink`
--

CREATE TABLE IF NOT EXISTS `fwlink` (
`id` int(11) NOT NULL,
  `url` varchar(256) NOT NULL,
  `title` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=25809 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_mig_album`
--

CREATE TABLE IF NOT EXISTS `gallery_mig_album` (
`id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `title` text NOT NULL,
  `parent` varchar(128) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `meta` longtext NOT NULL,
  `owner` varchar(32) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `featured_photo` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2833 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_mig_image`
--

CREATE TABLE IF NOT EXISTS `gallery_mig_image` (
`id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  `meta` longtext NOT NULL,
  `date_taken` datetime NOT NULL,
  `date_uploaded` datetime NOT NULL,
  `path` text NOT NULL,
  `title` text NOT NULL,
  `caption` text,
  `hidden` tinyint(1) DEFAULT '0',
  `lat` double(16,13) DEFAULT NULL,
  `lon` double(16,13) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=44521 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_mig_image_sizes`
--

CREATE TABLE IF NOT EXISTS `gallery_mig_image_sizes` (
`id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `size` enum('square','large_square','small','small_320','medium','medium_640','medium_800','original','') NOT NULL,
  `source` text NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=189020 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `geoplace`
--

CREATE TABLE IF NOT EXISTS `geoplace` (
`id` int(11) NOT NULL,
  `country_code` varchar(4) NOT NULL,
  `country_name` text NOT NULL,
  `region_code` varchar(10) DEFAULT NULL,
  `region_name` text,
  `neighbourhood` varchar(32) DEFAULT NULL,
  `point` point NOT NULL,
  `timezone` text NOT NULL,
  `bb_southwest` point NOT NULL,
  `bb_northeast` point NOT NULL
) ENGINE=Aria AUTO_INCREMENT=501373 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=0 TRANSACTIONAL=0;

-- --------------------------------------------------------

--
-- Table structure for table `geoplace_forecast`
--

CREATE TABLE IF NOT EXISTS `geoplace_forecast` (
`id` int(11) NOT NULL,
  `geoplace` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `date` date NOT NULL,
  `min` int(11) NOT NULL,
  `max` int(11) NOT NULL,
  `weather` text NOT NULL,
  `icon` text
) ENGINE=InnoDB AUTO_INCREMENT=1808 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `glossary`
--

CREATE TABLE IF NOT EXISTS `glossary` (
`id` int(12) NOT NULL,
  `type` enum('acronym','term','code','station','slang') NOT NULL DEFAULT 'term',
  `short` varchar(32) DEFAULT NULL,
  `full` text,
  `example` text NOT NULL,
  `date` datetime NOT NULL,
  `author` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `slug` varchar(22) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `idea_categories`
--

CREATE TABLE IF NOT EXISTS `idea_categories` (
`id` int(11) NOT NULL,
  `title` varchar(32) NOT NULL,
  `slug` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `idea_ideas`
--

CREATE TABLE IF NOT EXISTS `idea_ideas` (
`id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  `slug` varchar(32) NOT NULL,
  `description` text NOT NULL,
  `votes` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `status` tinyint(2) NOT NULL DEFAULT '1',
  `forum_thread_id` int(11) NOT NULL DEFAULT '0',
  `redmine_id` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `idea_votes`
--

CREATE TABLE IF NOT EXISTS `idea_votes` (
`id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=291 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

CREATE TABLE IF NOT EXISTS `image` (
`id` int(11) NOT NULL,
  `flags` int(4) NOT NULL,
  `provider` enum('flickr','westonlangford','rpoldgallery','picasaweb','vicsig','fivehundredpx','smugmug') NOT NULL,
  `photo_id` varchar(12) NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `captured` datetime DEFAULT NULL,
  `lat` double(16,13) NOT NULL,
  `lon` double(16,13) NOT NULL,
  `meta` longtext NOT NULL,
  `title` text NOT NULL,
  `description` text,
  `hits_today` int(11) NOT NULL,
  `hits_weekly` int(11) NOT NULL,
  `hits_overall` int(11) NOT NULL,
  `geoplace` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=73825 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_camera`
--

CREATE TABLE IF NOT EXISTS `image_camera` (
`id` int(11) NOT NULL,
  `make` varchar(32) NOT NULL,
  `model` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=871 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_collection`
--

CREATE TABLE IF NOT EXISTS `image_collection` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `slug` varchar(16) NOT NULL,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_competition`
--

CREATE TABLE IF NOT EXISTS `image_competition` (
`id` int(11) NOT NULL,
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
  `meta` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_competition_submissions`
--

CREATE TABLE IF NOT EXISTS `image_competition_submissions` (
`id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `meta` text NOT NULL,
  `date_added` datetime NOT NULL,
  `status` int(11) NOT NULL,
  `winner` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_competition_votes`
--

CREATE TABLE IF NOT EXISTS `image_competition_votes` (
`id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_exif`
--

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
  `white_balance_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_exposure`
--

CREATE TABLE IF NOT EXISTS `image_exposure` (
`id` int(11) NOT NULL,
  `exposure` varchar(12) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=844 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_exposure_program`
--

CREATE TABLE IF NOT EXISTS `image_exposure_program` (
`id` int(11) NOT NULL,
  `program` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_favourites`
--

CREATE TABLE IF NOT EXISTS `image_favourites` (
`id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `image_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_flags`
--

CREATE TABLE IF NOT EXISTS `image_flags` (
  `image_id` int(11) NOT NULL,
  `published` bit(1) NOT NULL DEFAULT b'0',
  `screened` bit(1) NOT NULL DEFAULT b'0',
  `screened_by` int(11) NOT NULL DEFAULT '0',
  `screened_on` datetime DEFAULT NULL,
  `screened_pick` bit(1) NOT NULL DEFAULT b'0',
  `rejected` bit(1) NOT NULL DEFAULT b'0',
  `exifqueue` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_flags_skip`
--

CREATE TABLE IF NOT EXISTS `image_flags_skip` (
`id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_lens`
--

CREATE TABLE IF NOT EXISTS `image_lens` (
`id` int(11) NOT NULL,
  `model` varchar(64) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_lens_sn`
--

CREATE TABLE IF NOT EXISTS `image_lens_sn` (
`id` int(11) NOT NULL,
  `sn` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_link`
--

CREATE TABLE IF NOT EXISTS `image_link` (
`id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `namespace` enum('railpage.locos.loco','railpage.locos.class','railpage.locations.location','railpage.locos.liveries.livery','railpage.images.collection') NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `ignored` tinyint(1) NOT NULL DEFAULT '0',
  `added` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=67104 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_position`
--

CREATE TABLE IF NOT EXISTS `image_position` (
`id` int(11) NOT NULL,
  `image_id` varchar(20) NOT NULL,
  `image_type` enum('flickr','asset') NOT NULL,
  `namespace` varchar(128) DEFAULT NULL,
  `namespace_key` varchar(32) DEFAULT NULL,
  `position_x` varchar(5) NOT NULL,
  `position_y` varchar(8) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=443 DEFAULT CHARSET=utf8 COMMENT='Define an offset position for an image. Useful for loco cover photos.';

-- --------------------------------------------------------

--
-- Table structure for table `image_software`
--

CREATE TABLE IF NOT EXISTS `image_software` (
`id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `version` double NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=772 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_weekly`
--

CREATE TABLE IF NOT EXISTS `image_weekly` (
`id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `datefrom` date NOT NULL,
  `added_by` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `image_whitebalance`
--

CREATE TABLE IF NOT EXISTS `image_whitebalance` (
`id` int(11) NOT NULL,
  `whitebalance` varchar(64) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `jn_applications`
--

CREATE TABLE IF NOT EXISTS `jn_applications` (
`jn_application_id` int(11) NOT NULL COMMENT 'Unique ID for this job application',
  `jn_job_id` int(11) NOT NULL COMMENT 'Job ID',
  `user_id` int(11) NOT NULL COMMENT 'User ID',
  `jn_application_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of application'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Railpage JobNet - Applications to advertised positions';

-- --------------------------------------------------------

--
-- Table structure for table `jn_classifications`
--

CREATE TABLE IF NOT EXISTS `jn_classifications` (
`jn_classification_id` int(11) NOT NULL COMMENT 'Unique ID of the job classification',
  `jn_classification_name` varchar(128) NOT NULL COMMENT 'Regular name for the classification',
  `jn_parent_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Parent jn_classification_id number'
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `jn_jobs`
--

CREATE TABLE IF NOT EXISTS `jn_jobs` (
`job_id` int(11) NOT NULL COMMENT 'Unique job ID',
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
  `conversions` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8 COMMENT='Railpage JobNet - Advertised positions';

-- --------------------------------------------------------

--
-- Table structure for table `jn_locations`
--

CREATE TABLE IF NOT EXISTS `jn_locations` (
`jn_location_id` int(11) NOT NULL COMMENT 'Unique job location ID',
  `jn_location_name` varchar(128) NOT NULL COMMENT 'Name of this location, eg: Melbourne > South East',
  `jn_parent_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Parent ID, eg Melbourne is the parent of Melbourne > South East'
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8 COMMENT='Railpage JobNet - Job locations';

-- --------------------------------------------------------

--
-- Table structure for table `loadstats`
--

CREATE TABLE IF NOT EXISTS `loadstats` (
`id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `url` varchar(512) NOT NULL,
  `referrer` mediumtext NOT NULL,
  `ip_address` varchar(128) NOT NULL,
  `stat_loadtime` decimal(10,4) NOT NULL,
  `stat_dbqueries` int(11) NOT NULL,
  `stat_ram` decimal(10,2) NOT NULL,
  `stat_webload` decimal(3,2) NOT NULL,
  `stat_dbload` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE IF NOT EXISTS `location` (
`id` int(11) NOT NULL,
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
  `geoplace` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=438 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `locations_like`
--

CREATE TABLE IF NOT EXISTS `locations_like` (
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `location_corrections`
--

CREATE TABLE IF NOT EXISTS `location_corrections` (
`id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comments` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_closed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location_date`
--

CREATE TABLE IF NOT EXISTS `location_date` (
`id` int(11) NOT NULL,
  `date` date NOT NULL,
  `location_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `meta` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location_datetypes`
--

CREATE TABLE IF NOT EXISTS `location_datetypes` (
`id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `loco_class`
--

CREATE TABLE IF NOT EXISTS `loco_class` (
`id` int(11) NOT NULL,
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
  `meta` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=385 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_date_type`
--

CREATE TABLE IF NOT EXISTS `loco_date_type` (
`loco_date_id` int(11) NOT NULL,
  `loco_date_text` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_gauge`
--

CREATE TABLE IF NOT EXISTS `loco_gauge` (
`gauge_id` int(11) NOT NULL,
  `gauge_name` varchar(64) CHARACTER SET latin1 NOT NULL,
  `gauge_imperial` varchar(64) CHARACTER SET latin1 NOT NULL,
  `gauge_metric` varchar(64) CHARACTER SET latin1 NOT NULL,
  `slug` varchar(12) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_groups`
--

CREATE TABLE IF NOT EXISTS `loco_groups` (
`group_id` int(11) NOT NULL,
  `group_name` varchar(256) CHARACTER SET latin1 NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `date_start` date NOT NULL,
  `date_end` date NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_groups_members`
--

CREATE TABLE IF NOT EXISTS `loco_groups_members` (
`id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `loco_unit_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_hits`
--

CREATE TABLE IF NOT EXISTS `loco_hits` (
  `loco_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_link`
--

CREATE TABLE IF NOT EXISTS `loco_link` (
`link_id` int(11) NOT NULL,
  `loco_id_a` int(11) NOT NULL,
  `loco_id_b` int(11) NOT NULL,
  `link_type_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=867 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_link_type`
--

CREATE TABLE IF NOT EXISTS `loco_link_type` (
`link_type_id` int(11) NOT NULL,
  `link_type_name` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_livery`
--

CREATE TABLE IF NOT EXISTS `loco_livery` (
`livery_id` int(11) NOT NULL,
  `livery` varchar(1024) NOT NULL,
  `introduced` varchar(256) NOT NULL,
  `withdrawn` varchar(256) NOT NULL,
  `superseded_by` int(11) NOT NULL DEFAULT '0',
  `supersedes` int(11) NOT NULL DEFAULT '0',
  `photo_id` varchar(2048) NOT NULL,
  `region` varchar(12) NOT NULL,
  `country` varchar(12) NOT NULL DEFAULT 'AU'
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_manufacturer`
--

CREATE TABLE IF NOT EXISTS `loco_manufacturer` (
`manufacturer_id` int(11) NOT NULL,
  `manufacturer_name` varchar(256) NOT NULL,
  `manufacturer_desc` text NOT NULL,
  `slug` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_notes`
--

CREATE TABLE IF NOT EXISTS `loco_notes` (
`note_id` int(11) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_date` int(11) NOT NULL,
  `note_text` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=395 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_org_link`
--

CREATE TABLE IF NOT EXISTS `loco_org_link` (
`id` int(11) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `link_type` int(11) NOT NULL,
  `link_weight` int(11) NOT NULL,
  `link_date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=26074 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_org_link_type`
--

CREATE TABLE IF NOT EXISTS `loco_org_link_type` (
`id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_status`
--

CREATE TABLE IF NOT EXISTS `loco_status` (
`id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_type`
--

CREATE TABLE IF NOT EXISTS `loco_type` (
`id` int(11) NOT NULL,
  `title` varchar(256) NOT NULL,
  `slug` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit`
--

CREATE TABLE IF NOT EXISTS `loco_unit` (
`loco_id` int(11) NOT NULL,
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
  `meta` longtext
) ENGINE=InnoDB AUTO_INCREMENT=7497 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_corrections`
--

CREATE TABLE IF NOT EXISTS `loco_unit_corrections` (
`correction_id` int(11) NOT NULL,
  `loco_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_by` int(11) NOT NULL,
  `resolved_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `text` varchar(2048) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_date`
--

CREATE TABLE IF NOT EXISTS `loco_unit_date` (
`date_id` int(11) NOT NULL,
  `loco_unit_id` int(11) NOT NULL,
  `loco_date_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `timestamp` date NOT NULL,
  `date_end` date DEFAULT NULL,
  `text` mediumtext NOT NULL,
  `meta` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4575 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_livery`
--

CREATE TABLE IF NOT EXISTS `loco_unit_livery` (
`id` int(11) NOT NULL,
  `provider` enum('flickr') NOT NULL,
  `photo_id` bigint(20) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `livery_id` int(11) NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ignored` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=7054 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `loco_unit_source`
--

CREATE TABLE IF NOT EXISTS `loco_unit_source` (
`id` int(11) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `source_id` int(11) NOT NULL,
  `desc` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_api`
--

CREATE TABLE IF NOT EXISTS `log_api` (
`id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `version` enum('1','2') NOT NULL,
  `resource` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `meta` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `log_downloads`
--

CREATE TABLE IF NOT EXISTS `log_downloads` (
`id` int(11) NOT NULL,
  `download_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=46545 DEFAULT CHARSET=utf8 TRANSACTIONAL=0;

-- --------------------------------------------------------

--
-- Table structure for table `log_errors`
--

CREATE TABLE IF NOT EXISTS `log_errors` (
`error_id` int(11) NOT NULL,
  `error_text` mediumtext NOT NULL,
  `error_time` int(11) NOT NULL,
  `error_file` mediumtext NOT NULL,
  `error_line` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `error_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `trace` mediumtext NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=374980 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_general`
--

CREATE TABLE IF NOT EXISTS `log_general` (
`id` int(11) NOT NULL,
  `module` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(128) NOT NULL,
  `args` varchar(2048) NOT NULL,
  `key` varchar(12) NOT NULL,
  `value` varchar(12) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=121064 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_herrings`
--

CREATE TABLE IF NOT EXISTS `log_herrings` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `poster_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3645 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_locos`
--

CREATE TABLE IF NOT EXISTS `log_locos` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `title` varchar(128) NOT NULL,
  `args` varchar(2048) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1268 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_logins`
--

CREATE TABLE IF NOT EXISTS `log_logins` (
`login_id` int(11) NOT NULL,
  `login_time` int(11) NOT NULL,
  `login_ip` varchar(256) NOT NULL,
  `login_hostname` varchar(512) NOT NULL,
  `user_id` int(11) NOT NULL,
  `server` varchar(32) NOT NULL,
  `device_hash` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1679492 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_pageactivity`
--

CREATE TABLE IF NOT EXISTS `log_pageactivity` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url` varchar(2048) NOT NULL,
  `pagetitle` varchar(2048) NOT NULL,
  `module` varchar(1024) NOT NULL,
  `hits` int(11) NOT NULL DEFAULT '0',
  `loggedin` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_staff`
--

CREATE TABLE IF NOT EXISTS `log_staff` (
`id` int(11) NOT NULL,
  `key` varchar(32) NOT NULL,
  `key_val` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(128) NOT NULL,
  `args` varchar(2048) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7868 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `log_useractivity`
--

CREATE TABLE IF NOT EXISTS `log_useractivity` (
`log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip` varchar(64) CHARACTER SET latin1 NOT NULL,
  `module_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `pagetitle` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=1095865 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
`message_id` int(10) NOT NULL,
  `message_active` tinyint(1) NOT NULL DEFAULT '1',
  `message_text` varchar(512) NOT NULL,
  `message_title` varchar(64) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `object_ns` varchar(64) NOT NULL,
  `object_id` int(10) NOT NULL,
  `target_user` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `messages_viewed`
--

CREATE TABLE IF NOT EXISTS `messages_viewed` (
`row_id` int(11) NOT NULL,
  `message_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2047 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter`
--

CREATE TABLE IF NOT EXISTS `newsletter` (
`id` int(11) NOT NULL,
  `subject` text NOT NULL,
  `publishdate` date NOT NULL,
  `status` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `recipients` text
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_templates`
--

CREATE TABLE IF NOT EXISTS `newsletter_templates` (
`id` int(11) NOT NULL,
  `name` text NOT NULL,
  `html` text NOT NULL,
  `contenturl` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `news_feed`
--

CREATE TABLE IF NOT EXISTS `news_feed` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topics` text NOT NULL,
  `keywords` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
`id` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  `transport` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_sent` datetime NOT NULL,
  `subject` text,
  `body` longtext NOT NULL,
  `response` longtext NOT NULL COMMENT 'Response from the transport. Used for errors/debugging',
  `meta` longtext NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6720 DEFAULT CHARSET=utf8 COMMENT='Notifications queue, to prevent page blocking when sending emails etc';

-- --------------------------------------------------------

--
-- Table structure for table `notifications_recipients`
--

CREATE TABLE IF NOT EXISTS `notifications_recipients` (
`id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `destination` text NOT NULL,
  `date_sent` datetime NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=20714 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notification_prefs`
--

CREATE TABLE IF NOT EXISTS `notification_prefs` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'User ID that this preference belongs to',
  `notify_off` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Turn off notifications completely',
  `notify_topic_reply` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notify on topic reply',
  `notify_pm` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notify on new PM',
  `notify_job_apply` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notify when someone applies for an advertised job'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='Notification email preferences';

-- --------------------------------------------------------

--
-- Table structure for table `notification_rules`
--

CREATE TABLE IF NOT EXISTS `notification_rules` (
`id` int(11) NOT NULL,
  `namespace` varchar(256) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rule` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='Custom per-user notification rules';

-- --------------------------------------------------------

--
-- Table structure for table `notification_sent`
--

CREATE TABLE IF NOT EXISTS `notification_sent` (
`id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `namespace` varchar(256) NOT NULL,
  `namespace_key` int(11) NOT NULL,
  `namespace_value` int(11) NOT NULL,
  `template_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='Previously sent notifications';

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE IF NOT EXISTS `notification_templates` (
`id` int(11) NOT NULL,
  `namespace` varchar(256) NOT NULL,
  `template` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 TRANSACTIONAL=1 COMMENT='BBCode notification templates';

-- --------------------------------------------------------

--
-- Table structure for table `nuke_alliance`
--

CREATE TABLE IF NOT EXISTS `nuke_alliance` (
`id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrip` mediumtext COLLATE utf8_unicode_ci,
  `joined` int(11) NOT NULL DEFAULT '0',
  `uniquetoken` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `indexed` tinyint(1) NOT NULL DEFAULT '0',
  `imgsrc` mediumtext COLLATE utf8_unicode_ci,
  `url` mediumtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_authors`
--

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
  `admlanguage` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade`
--

CREATE TABLE IF NOT EXISTS `nuke_bbarcade` (
  `arcade_name` varchar(255) NOT NULL DEFAULT '',
  `arcade_value` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_bbarcade_categories` (
`arcade_catid` mediumint(8) unsigned NOT NULL,
  `arcade_cattitle` varchar(100) NOT NULL DEFAULT '',
  `arcade_nbelmt` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `arcade_catorder` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `arcade_catauth` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade_comments`
--

CREATE TABLE IF NOT EXISTS `nuke_bbarcade_comments` (
  `game_id` mediumint(8) NOT NULL DEFAULT '0',
  `comments_value` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbarcade_fav`
--

CREATE TABLE IF NOT EXISTS `nuke_bbarcade_fav` (
  `order` mediumint(8) NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `game_id` mediumint(8) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbauth_access`
--

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
  `auth_mod` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbauth_arcade_access`
--

CREATE TABLE IF NOT EXISTS `nuke_bbauth_arcade_access` (
  `group_id` mediumint(8) NOT NULL DEFAULT '0',
  `arcade_catid` mediumint(8) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbbanlist`
--

CREATE TABLE IF NOT EXISTS `nuke_bbbanlist` (
`ban_id` mediumint(8) unsigned NOT NULL,
  `ban_userid` mediumint(8) NOT NULL DEFAULT '0',
  `ban_ip` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ban_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbcategories`
--

CREATE TABLE IF NOT EXISTS `nuke_bbcategories` (
`cat_id` int(8) NOT NULL,
  `cat_title` varchar(100) DEFAULT NULL,
  `cat_order` int(8) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbconfig`
--

CREATE TABLE IF NOT EXISTS `nuke_bbconfig` (
  `config_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `config_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbconfirm`
--

CREATE TABLE IF NOT EXISTS `nuke_bbconfirm` (
  `confirm_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `session_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `code` char(6) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbdisallow`
--

CREATE TABLE IF NOT EXISTS `nuke_bbdisallow` (
`disallow_id` mediumint(8) unsigned NOT NULL,
  `disallow_username` varchar(25) DEFAULT NULL
) ENGINE=Aria AUTO_INCREMENT=18 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbforums`
--

CREATE TABLE IF NOT EXISTS `nuke_bbforums` (
`forum_id` smallint(5) unsigned NOT NULL,
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
  `forum_parent` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbforum_prune`
--

CREATE TABLE IF NOT EXISTS `nuke_bbforum_prune` (
`prune_id` mediumint(8) unsigned NOT NULL,
  `forum_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `prune_days` smallint(5) unsigned NOT NULL DEFAULT '0',
  `prune_freq` smallint(5) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbgamehash`
--

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

CREATE TABLE IF NOT EXISTS `nuke_bbgames` (
`game_id` mediumint(8) NOT NULL,
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
  `arcade_catid` mediumint(8) NOT NULL DEFAULT '1'
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbgroups`
--

CREATE TABLE IF NOT EXISTS `nuke_bbgroups` (
`group_id` mediumint(8) NOT NULL,
  `group_type` tinyint(4) NOT NULL DEFAULT '1',
  `group_name` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `group_description` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `group_moderator` mediumint(8) NOT NULL DEFAULT '0',
  `group_single_user` tinyint(1) NOT NULL DEFAULT '1',
  `organisation_id` int(11) NOT NULL DEFAULT '0',
  `group_attrs` mediumtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1241 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbhackgame`
--

CREATE TABLE IF NOT EXISTS `nuke_bbhackgame` (
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `game_id` mediumint(8) NOT NULL DEFAULT '0',
  `date_hack` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts`
--

CREATE TABLE IF NOT EXISTS `nuke_bbposts` (
`post_id` mediumint(8) unsigned NOT NULL,
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
  `pinned` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1992454 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts_edit`
--

CREATE TABLE IF NOT EXISTS `nuke_bbposts_edit` (
`edit_id` int(11) NOT NULL,
  `post_id` int(32) NOT NULL DEFAULT '0',
  `thread_id` int(11) NOT NULL DEFAULT '0',
  `poster_id` int(11) NOT NULL DEFAULT '0',
  `editor_id` int(11) NOT NULL,
  `edit_time` varchar(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `edit_body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `bbcode_uid` varchar(512) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=64860 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts_reputation`
--

CREATE TABLE IF NOT EXISTS `nuke_bbposts_reputation` (
`id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `type` tinyint(2) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11711 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbposts_text`
--

CREATE TABLE IF NOT EXISTS `nuke_bbposts_text` (
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `bbcode_uid` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `post_subject` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_text` mediumtext COLLATE utf8_unicode_ci,
  `url_slug` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `editor_version` int(10) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbprivmsgs`
--

CREATE TABLE IF NOT EXISTS `nuke_bbprivmsgs` (
`privmsgs_id` mediumint(8) unsigned NOT NULL,
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
  `hide_to` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=332217 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbprivmsgs_archive`
--

CREATE TABLE IF NOT EXISTS `nuke_bbprivmsgs_archive` (
`privmsgs_id` mediumint(8) unsigned NOT NULL,
  `privmsgs_type` tinyint(4) NOT NULL DEFAULT '0',
  `privmsgs_subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `privmsgs_from_userid` mediumint(8) NOT NULL DEFAULT '0',
  `privmsgs_to_userid` mediumint(8) NOT NULL DEFAULT '0',
  `privmsgs_date` int(11) NOT NULL DEFAULT '0',
  `privmsgs_ip` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `privmsgs_enable_bbcode` tinyint(1) NOT NULL DEFAULT '1',
  `privmsgs_enable_html` tinyint(1) NOT NULL DEFAULT '0',
  `privmsgs_enable_smilies` tinyint(1) NOT NULL DEFAULT '1',
  `privmsgs_attach_sig` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=314647 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbprivmsgs_text`
--

CREATE TABLE IF NOT EXISTS `nuke_bbprivmsgs_text` (
  `privmsgs_text_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `privmsgs_bbcode_uid` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `privmsgs_text` mediumtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbranks`
--

CREATE TABLE IF NOT EXISTS `nuke_bbranks` (
`rank_id` smallint(5) unsigned NOT NULL,
  `rank_title` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rank_min` mediumint(8) NOT NULL DEFAULT '0',
  `rank_special` tinyint(1) DEFAULT NULL,
  `rank_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbscores`
--

CREATE TABLE IF NOT EXISTS `nuke_bbscores` (
  `game_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `score_game` int(11) NOT NULL DEFAULT '0',
  `score_date` int(11) NOT NULL DEFAULT '0',
  `score_time` int(11) NOT NULL DEFAULT '0',
  `score_set` mediumint(8) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_pending`
--

CREATE TABLE IF NOT EXISTS `nuke_bbsearch_pending` (
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `mode` varchar(20) COLLATE utf8_unicode_ci DEFAULT 'single',
  `post_subject` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_text` mediumtext COLLATE utf8_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_results`
--

CREATE TABLE IF NOT EXISTS `nuke_bbsearch_results` (
  `search_id` int(11) unsigned NOT NULL DEFAULT '0',
  `session_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `search_array` longtext COLLATE utf8_unicode_ci NOT NULL,
  `search_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_wordlist`
--

CREATE TABLE IF NOT EXISTS `nuke_bbsearch_wordlist` (
  `word_text` varchar(50) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
`word_id` mediumint(8) unsigned NOT NULL,
  `word_common` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsearch_wordmatch`
--

CREATE TABLE IF NOT EXISTS `nuke_bbsearch_wordmatch` (
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `word_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `title_match` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsessions`
--

CREATE TABLE IF NOT EXISTS `nuke_bbsessions` (
  `session_id` char(32) NOT NULL DEFAULT '',
  `session_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `session_start` int(11) NOT NULL DEFAULT '0',
  `session_time` int(11) NOT NULL DEFAULT '0',
  `session_ip` char(8) NOT NULL DEFAULT '',
  `session_page` int(11) NOT NULL DEFAULT '0',
  `session_logged_in` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbsmilies`
--

CREATE TABLE IF NOT EXISTS `nuke_bbsmilies` (
`smilies_id` smallint(5) unsigned NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `smile_url` varchar(100) DEFAULT NULL,
  `emoticon` varchar(75) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbthemes`
--

CREATE TABLE IF NOT EXISTS `nuke_bbthemes` (
`themes_id` mediumint(8) unsigned NOT NULL,
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
  `img_size_privmsg` smallint(5) unsigned DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbthemes_name`
--

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
  `span_class3_name` char(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbtopics`
--

CREATE TABLE IF NOT EXISTS `nuke_bbtopics` (
`topic_id` mediumint(8) unsigned NOT NULL,
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
  `topic_meta` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11382716 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbtopics_view`
--

CREATE TABLE IF NOT EXISTS `nuke_bbtopics_view` (
`id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=311313 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbtopics_watch`
--

CREATE TABLE IF NOT EXISTS `nuke_bbtopics_watch` (
  `topic_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `notify_status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbuser_group`
--

CREATE TABLE IF NOT EXISTS `nuke_bbuser_group` (
  `group_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_pending` tinyint(1) DEFAULT '0',
  `organisation_role` varchar(128) NOT NULL,
  `organisation_contact` varchar(256) NOT NULL,
  `organisation_privileges` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbvote_desc`
--

CREATE TABLE IF NOT EXISTS `nuke_bbvote_desc` (
`vote_id` mediumint(8) unsigned NOT NULL,
  `topic_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `vote_text` text NOT NULL,
  `vote_start` int(11) NOT NULL DEFAULT '0',
  `vote_length` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1452 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbvote_results`
--

CREATE TABLE IF NOT EXISTS `nuke_bbvote_results` (
  `vote_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `vote_option_id` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `vote_option_text` varchar(255) NOT NULL DEFAULT '',
  `vote_result` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbvote_voters`
--

CREATE TABLE IF NOT EXISTS `nuke_bbvote_voters` (
  `vote_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `vote_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `vote_user_ip` char(8) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_bbwords`
--

CREATE TABLE IF NOT EXISTS `nuke_bbwords` (
`word_id` mediumint(8) unsigned NOT NULL,
  `word` char(100) NOT NULL DEFAULT '',
  `replacement` char(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_blocks`
--

CREATE TABLE IF NOT EXISTS `nuke_blocks` (
`bid` int(10) NOT NULL,
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
  `view` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_comments`
--

CREATE TABLE IF NOT EXISTS `nuke_comments` (
`tid` int(11) NOT NULL,
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
  `reason` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=13973 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_config`
--

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

CREATE TABLE IF NOT EXISTS `nuke_contactbook` (
  `uid` int(11) NOT NULL DEFAULT '0',
`contactid` int(11) NOT NULL,
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
  `notes` text
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_counter`
--

CREATE TABLE IF NOT EXISTS `nuke_counter` (
  `type` varchar(80) NOT NULL DEFAULT '',
  `var` varchar(80) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_downloads_categories` (
`cid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL DEFAULT '',
  `cdescription` text NOT NULL,
  `parentid` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_downloads`
--

CREATE TABLE IF NOT EXISTS `nuke_downloads_downloads` (
`lid` int(11) NOT NULL,
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
  `approved` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM AUTO_INCREMENT=364 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_editorials`
--

CREATE TABLE IF NOT EXISTS `nuke_downloads_editorials` (
  `downloadid` int(11) NOT NULL DEFAULT '0',
  `adminid` varchar(60) NOT NULL DEFAULT '',
  `editorialtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `editorialtext` text NOT NULL,
  `editorialtitle` varchar(100) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_modrequest`
--

CREATE TABLE IF NOT EXISTS `nuke_downloads_modrequest` (
`requestid` int(11) NOT NULL,
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
  `homepage` varchar(200) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=63 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_newdownload`
--

CREATE TABLE IF NOT EXISTS `nuke_downloads_newdownload` (
`lid` int(11) NOT NULL,
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
  `homepage` varchar(200) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_downloads_votedata`
--

CREATE TABLE IF NOT EXISTS `nuke_downloads_votedata` (
`ratingdbid` int(11) NOT NULL,
  `ratinglid` int(11) NOT NULL DEFAULT '0',
  `ratinguser` varchar(60) NOT NULL DEFAULT '',
  `rating` int(11) NOT NULL DEFAULT '0',
  `ratinghostname` varchar(60) NOT NULL DEFAULT '',
  `ratingcomments` text NOT NULL,
  `ratingtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM AUTO_INCREMENT=149 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_encyclopedia`
--

CREATE TABLE IF NOT EXISTS `nuke_encyclopedia` (
`eid` int(10) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `elanguage` varchar(30) NOT NULL DEFAULT '',
  `active` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_encyclopedia_text`
--

CREATE TABLE IF NOT EXISTS `nuke_encyclopedia_text` (
`tid` int(10) NOT NULL,
  `eid` int(10) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `counter` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_ephem`
--

CREATE TABLE IF NOT EXISTS `nuke_ephem` (
`eid` int(11) NOT NULL,
  `did` int(2) NOT NULL DEFAULT '0',
  `mid` int(2) NOT NULL DEFAULT '0',
  `yid` int(4) NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `elanguage` varchar(30) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_externalsearch`
--

CREATE TABLE IF NOT EXISTS `nuke_externalsearch` (
`linkid` int(13) NOT NULL,
  `rphosted` int(1) NOT NULL DEFAULT '0',
  `linktitle` text NOT NULL,
  `linktext` text NOT NULL,
  `linkurl` text NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_faqAnswer`
--

CREATE TABLE IF NOT EXISTS `nuke_faqAnswer` (
`id` tinyint(4) NOT NULL,
  `id_cat` tinyint(4) NOT NULL DEFAULT '0',
  `question` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `answer` mediumtext COLLATE utf8_unicode_ci,
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `url_slug` varchar(128) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_faqCategories`
--

CREATE TABLE IF NOT EXISTS `nuke_faqCategories` (
`id_cat` tinyint(3) NOT NULL,
  `url_slug` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `categories` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flanguage` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_g2config`
--

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

CREATE TABLE IF NOT EXISTS `nuke_gallery` (
  `album_name` varchar(255) NOT NULL DEFAULT '',
  `album_title` varchar(255) NOT NULL DEFAULT '',
  `parent_album_name` varchar(255) DEFAULT NULL,
  `num_children` int(11) NOT NULL DEFAULT '0',
  `cached_photos` int(11) NOT NULL DEFAULT '0',
  `mod_date` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_hallfame_queue`
--

CREATE TABLE IF NOT EXISTS `nuke_hallfame_queue` (
`qid` int(11) NOT NULL,
  `qdate` varchar(255) NOT NULL DEFAULT '',
  `qnomid` int(20) NOT NULL DEFAULT '0',
  `qanon` int(1) NOT NULL DEFAULT '0',
  `hofuid` int(20) NOT NULL DEFAULT '0',
  `hofreason` text,
  `qvotesfor` int(20) NOT NULL DEFAULT '0',
  `qstate` int(1) NOT NULL DEFAULT '0',
  `qaccept` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_headlines`
--

CREATE TABLE IF NOT EXISTS `nuke_headlines` (
`hid` int(11) NOT NULL,
  `sitename` varchar(30) NOT NULL DEFAULT '',
  `headlinesurl` varchar(200) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_journal`
--

CREATE TABLE IF NOT EXISTS `nuke_journal` (
`jid` int(11) NOT NULL,
  `aid` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(80) DEFAULT NULL,
  `bodytext` text NOT NULL,
  `mood` varchar(48) NOT NULL DEFAULT '',
  `pdate` varchar(48) NOT NULL DEFAULT '',
  `ptime` varchar(48) NOT NULL DEFAULT '',
  `status` varchar(48) NOT NULL DEFAULT '',
  `mtime` varchar(48) NOT NULL DEFAULT '',
  `mdate` varchar(48) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=117 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_journal_comments`
--

CREATE TABLE IF NOT EXISTS `nuke_journal_comments` (
`cid` int(11) NOT NULL,
  `rid` varchar(48) NOT NULL DEFAULT '',
  `aid` varchar(30) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `pdate` varchar(48) NOT NULL DEFAULT '',
  `ptime` varchar(48) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_journal_stats`
--

CREATE TABLE IF NOT EXISTS `nuke_journal_stats` (
`id` int(11) NOT NULL,
  `joid` varchar(48) NOT NULL DEFAULT '',
  `nop` varchar(48) NOT NULL DEFAULT '',
  `ldp` varchar(24) NOT NULL DEFAULT '',
  `ltp` varchar(24) NOT NULL DEFAULT '',
  `micro` varchar(128) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_links_categories` (
`cid` int(11) NOT NULL,
  `title` varchar(50) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `cdescription` text CHARACTER SET latin1 NOT NULL,
  `parentid` int(11) NOT NULL DEFAULT '0',
  `slug` varchar(128) CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_editorials`
--

CREATE TABLE IF NOT EXISTS `nuke_links_editorials` (
  `linkid` int(11) NOT NULL DEFAULT '0',
  `adminid` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `editorialtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `editorialtext` text CHARACTER SET latin1 NOT NULL,
  `editorialtitle` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_links`
--

CREATE TABLE IF NOT EXISTS `nuke_links_links` (
`lid` int(11) NOT NULL,
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
  `link_approved` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=308 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_modrequest`
--

CREATE TABLE IF NOT EXISTS `nuke_links_modrequest` (
`requestid` int(11) NOT NULL,
  `lid` int(11) NOT NULL DEFAULT '0',
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `image` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `url` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `description` text CHARACTER SET latin1 NOT NULL,
  `modifysubmitter` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `brokenlink` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_newlink`
--

CREATE TABLE IF NOT EXISTS `nuke_links_newlink` (
`lid` int(11) NOT NULL,
  `cid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `image` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `url` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `description` text CHARACTER SET latin1 NOT NULL,
  `name` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `email` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `submitter` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_links_settings`
--

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

CREATE TABLE IF NOT EXISTS `nuke_links_votedata` (
`ratingdbid` int(11) NOT NULL,
  `ratinglid` int(11) NOT NULL DEFAULT '0',
  `ratinguser` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `rating` int(11) NOT NULL DEFAULT '0',
  `ratinghostname` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `ratingcomments` text CHARACTER SET latin1 NOT NULL,
  `ratingtimestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_main`
--

CREATE TABLE IF NOT EXISTS `nuke_main` (
  `main_module` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_message`
--

CREATE TABLE IF NOT EXISTS `nuke_message` (
`mid` int(11) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `date` varchar(14) NOT NULL DEFAULT '',
  `expire` int(7) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '1',
  `view` int(1) NOT NULL DEFAULT '1',
  `mlanguage` varchar(30) NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_modules`
--

CREATE TABLE IF NOT EXISTS `nuke_modules` (
`mid` int(10) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `custom_title` varchar(255) NOT NULL DEFAULT '',
  `url` text NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  `view` int(1) NOT NULL DEFAULT '0',
  `inmenu` tinyint(1) NOT NULL DEFAULT '1',
  `mcid` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_modules_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_modules_categories` (
`mcid` int(11) NOT NULL,
  `mcname` varchar(60) NOT NULL DEFAULT '',
  `visible` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_admin`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_admin` (
`id` int(11) NOT NULL,
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
  `version` varchar(25) NOT NULL DEFAULT 'MS-Analysis v1.1'
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_browsers`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_browsers` (
`id` int(11) NOT NULL,
  `ibrowser` varchar(255) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '0000-00-00',
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=310 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_countries`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_countries` (
`id` int(11) NOT NULL,
  `domain` char(20) NOT NULL DEFAULT '',
  `description` char(50) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '0000-00-00',
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=197 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_domains`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_domains` (
`id` int(11) NOT NULL,
  `domain` char(20) NOT NULL DEFAULT '',
  `description` char(50) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=266 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_modules`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_modules` (
`id` int(11) NOT NULL,
  `modulename` varchar(50) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=695 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_online`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_online` (
`id` int(11) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `uname` varchar(25) NOT NULL DEFAULT '',
  `agent` varchar(255) NOT NULL DEFAULT '',
  `ip_addr` varchar(20) NOT NULL DEFAULT '',
  `host` varchar(100) NOT NULL DEFAULT '',
  `domain` varchar(20) NOT NULL DEFAULT '',
  `modulename` varchar(50) NOT NULL DEFAULT '',
  `scr_res` varchar(25) NOT NULL DEFAULT '',
  `referral` varchar(255) NOT NULL DEFAULT '',
  `ref_query` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=18643522 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_os`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_os` (
`id` int(11) NOT NULL,
  `ios` varchar(25) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_referrals`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_referrals` (
`id` int(11) NOT NULL,
  `referral` varchar(255) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=5007 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_scr`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_scr` (
`id` int(11) NOT NULL,
  `scr_res` varchar(25) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '2003-08-10',
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=418 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_search`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_search` (
`id` int(11) NOT NULL,
  `words` varchar(255) NOT NULL DEFAULT '',
  `hits` int(25) NOT NULL DEFAULT '0',
  `today` date NOT NULL DEFAULT '0000-00-00',
  `hitstoday` int(25) NOT NULL DEFAULT '0',
  `xdays` date NOT NULL DEFAULT '0000-00-00',
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_msanalysis_users`
--

CREATE TABLE IF NOT EXISTS `nuke_msanalysis_users` (
`uid` int(11) NOT NULL,
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
  `hitsxdays` int(25) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=7932 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_newscomau`
--

CREATE TABLE IF NOT EXISTS `nuke_newscomau` (
`sid` int(11) NOT NULL,
  `title` varchar(80) DEFAULT NULL,
  `xtime` int(11) DEFAULT NULL,
  `desctext` text,
  `bodytext` text NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nsndownloads_config`
--

CREATE TABLE IF NOT EXISTS `nuke_nsndownloads_config` (
`id` int(11) NOT NULL,
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
  `show_date` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_attendees`
--

CREATE TABLE IF NOT EXISTS `nuke_nucal_attendees` (
  `event_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_nucal_categories` (
`id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `showinblock` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_events`
--

CREATE TABLE IF NOT EXISTS `nuke_nucal_events` (
`id` int(11) NOT NULL,
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
  `ticket_url` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1342 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_nucal_options`
--

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

CREATE TABLE IF NOT EXISTS `nuke_optimize_gain` (
  `gain` decimal(10,3) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_pages`
--

CREATE TABLE IF NOT EXISTS `nuke_pages` (
`pid` int(10) NOT NULL,
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
  `shortname` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_pages_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_pages_categories` (
`cid` int(10) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_pollcomments`
--

CREATE TABLE IF NOT EXISTS `nuke_pollcomments` (
`tid` int(11) NOT NULL,
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
  `reason` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=639 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_poll_check`
--

CREATE TABLE IF NOT EXISTS `nuke_poll_check` (
  `ip` varchar(20) NOT NULL DEFAULT '',
  `time` varchar(14) NOT NULL DEFAULT '',
  `pollID` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_poll_data`
--

CREATE TABLE IF NOT EXISTS `nuke_poll_data` (
  `pollID` int(11) NOT NULL DEFAULT '0',
  `optionText` varchar(512) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `optionCount` int(11) NOT NULL DEFAULT '0',
  `voteID` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_poll_desc`
--

CREATE TABLE IF NOT EXISTS `nuke_poll_desc` (
`pollID` int(11) NOT NULL,
  `pollTitle` varchar(100) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `planguage` varchar(30) NOT NULL DEFAULT '',
  `artid` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=98 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_popsettings`
--

CREATE TABLE IF NOT EXISTS `nuke_popsettings` (
`id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `account` varchar(50) DEFAULT '',
  `popserver` varchar(255) DEFAULT '',
  `port` int(5) DEFAULT '0',
  `uname` varchar(100) DEFAULT '',
  `passwd` varchar(20) DEFAULT '',
  `numshow` int(11) DEFAULT '0',
  `deletefromserver` char(1) DEFAULT '',
  `refresh` int(11) DEFAULT '0',
  `timeout` int(11) DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_priv_msgs`
--

CREATE TABLE IF NOT EXISTS `nuke_priv_msgs` (
`msg_id` int(10) NOT NULL,
  `msg_image` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `from_userid` int(10) NOT NULL DEFAULT '0',
  `to_userid` int(10) NOT NULL DEFAULT '0',
  `msg_time` varchar(20) DEFAULT NULL,
  `msg_text` text,
  `read_msg` tinyint(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_public_messages`
--

CREATE TABLE IF NOT EXISTS `nuke_public_messages` (
`mid` int(10) NOT NULL,
  `content` varchar(255) NOT NULL DEFAULT '',
  `date` varchar(14) DEFAULT NULL,
  `who` varchar(25) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_queue`
--

CREATE TABLE IF NOT EXISTS `nuke_queue` (
`qid` smallint(5) unsigned NOT NULL,
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
  `geo_lon` decimal(16,13) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=8422 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_admin`
--

CREATE TABLE IF NOT EXISTS `nuke_quizz_admin` (
`quizzID` int(11) NOT NULL,
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
  `conditions` text
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_quizz_categories` (
`cid` int(9) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `comment` varchar(255) DEFAULT NULL,
  `image` varchar(50) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_check`
--

CREATE TABLE IF NOT EXISTS `nuke_quizz_check` (
  `ip` varchar(20) DEFAULT NULL,
  `time` varchar(14) NOT NULL DEFAULT '',
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `qid` int(11) NOT NULL DEFAULT '0',
  `score` tinyint(2) NOT NULL DEFAULT '0',
  `answers` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_data`
--

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

CREATE TABLE IF NOT EXISTS `nuke_quizz_desc` (
`pollID` int(11) NOT NULL,
  `pollTitle` varchar(100) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `qid` tinyint(9) NOT NULL DEFAULT '0',
  `answer` varchar(30) NOT NULL DEFAULT '0',
  `coef` tinyint(3) NOT NULL DEFAULT '1',
  `good` text,
  `bad` text,
  `comment` text,
  `image` varchar(255) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quizz_descontrib`
--

CREATE TABLE IF NOT EXISTS `nuke_quizz_descontrib` (
`pollID` int(11) NOT NULL,
  `pollTitle` varchar(100) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `qid` tinyint(9) NOT NULL DEFAULT '0',
  `answer` varchar(30) NOT NULL DEFAULT '0',
  `coef` tinyint(3) NOT NULL DEFAULT '1',
  `good` text,
  `bad` text,
  `comment` text,
  `image` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_admin`
--

CREATE TABLE IF NOT EXISTS `nuke_quiz_admin` (
`quizID` int(11) NOT NULL,
  `quizTitle` varchar(150) NOT NULL DEFAULT '',
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `comment` text,
  `image` varchar(50) DEFAULT NULL,
  `cid` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_quiz_categories` (
`cid` int(9) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `comment` varchar(255) DEFAULT NULL,
  `image` varchar(50) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_check`
--

CREATE TABLE IF NOT EXISTS `nuke_quiz_check` (
  `ip` varchar(20) DEFAULT NULL,
  `time` varchar(14) NOT NULL DEFAULT '',
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `qid` int(11) NOT NULL DEFAULT '0',
  `score` tinyint(2) NOT NULL DEFAULT '0',
  `answers` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_data`
--

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

CREATE TABLE IF NOT EXISTS `nuke_quiz_desc` (
`pollID` int(11) NOT NULL,
  `pollTitle` blob NOT NULL,
  `timeStamp` int(11) NOT NULL DEFAULT '0',
  `voters` mediumint(9) NOT NULL DEFAULT '0',
  `qid` tinyint(9) NOT NULL DEFAULT '0',
  `answer` varchar(30) NOT NULL DEFAULT '0',
  `coef` tinyint(3) NOT NULL DEFAULT '1',
  `good` text,
  `bad` text,
  `comment` text,
  `image` varchar(255) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quiz_index`
--

CREATE TABLE IF NOT EXISTS `nuke_quiz_index` (
`quizid` int(11) NOT NULL,
  `quiztitle` varchar(255) NOT NULL DEFAULT '',
  `quizdesc` text NOT NULL,
  `quizactive` int(1) NOT NULL DEFAULT '0',
  `quizhidden` int(1) NOT NULL DEFAULT '1',
  `quizowner` int(18) NOT NULL DEFAULT '0',
  `quizstatus` int(1) NOT NULL DEFAULT '0',
  `currentuser` int(18) NOT NULL DEFAULT '0',
  `turnexpires` int(30) NOT NULL DEFAULT '0',
  `currquestion` longtext NOT NULL,
  `quizcat` int(5) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_quotes`
--

CREATE TABLE IF NOT EXISTS `nuke_quotes` (
`qid` int(10) unsigned NOT NULL,
  `quote` text
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_referer`
--

CREATE TABLE IF NOT EXISTS `nuke_referer` (
`rid` int(11) NOT NULL,
  `url` varchar(100) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=686657 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_related`
--

CREATE TABLE IF NOT EXISTS `nuke_related` (
`rid` int(11) NOT NULL,
  `tid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(30) NOT NULL DEFAULT '',
  `url` varchar(200) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews`
--

CREATE TABLE IF NOT EXISTS `nuke_reviews` (
`id` int(10) NOT NULL,
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
  `rlanguage` varchar(30) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews_add`
--

CREATE TABLE IF NOT EXISTS `nuke_reviews_add` (
`id` int(10) NOT NULL,
  `date` date DEFAULT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `reviewer` varchar(20) NOT NULL DEFAULT '',
  `email` varchar(60) DEFAULT NULL,
  `score` int(10) NOT NULL DEFAULT '0',
  `url` varchar(100) NOT NULL DEFAULT '',
  `url_title` varchar(50) NOT NULL DEFAULT '',
  `rlanguage` varchar(30) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews_comments`
--

CREATE TABLE IF NOT EXISTS `nuke_reviews_comments` (
`cid` int(10) NOT NULL,
  `rid` int(10) NOT NULL DEFAULT '0',
  `userid` varchar(25) NOT NULL DEFAULT '',
  `date` datetime DEFAULT NULL,
  `comments` text,
  `score` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_reviews_main`
--

CREATE TABLE IF NOT EXISTS `nuke_reviews_main` (
  `title` varchar(100) DEFAULT NULL,
  `description` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_seccont`
--

CREATE TABLE IF NOT EXISTS `nuke_seccont` (
`artid` int(11) NOT NULL,
  `secid` int(11) NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `content` text NOT NULL,
  `counter` int(11) NOT NULL DEFAULT '0',
  `slanguage` varchar(30) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_sections`
--

CREATE TABLE IF NOT EXISTS `nuke_sections` (
`secid` int(11) NOT NULL,
  `secname` varchar(40) NOT NULL DEFAULT '',
  `image` varchar(50) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_session`
--

CREATE TABLE IF NOT EXISTS `nuke_session` (
  `uname` varchar(25) NOT NULL DEFAULT '',
  `time` varchar(14) NOT NULL DEFAULT '',
  `host_addr` varchar(48) NOT NULL DEFAULT '',
  `guest` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_sommaire`
--

CREATE TABLE IF NOT EXISTS `nuke_sommaire` (
  `groupmenu` int(2) NOT NULL DEFAULT '0',
  `name` varchar(200) DEFAULT NULL,
  `image` varchar(99) DEFAULT NULL,
  `lien` text,
  `hr` char(2) DEFAULT NULL,
  `center` char(2) DEFAULT NULL,
  `bgcolor` tinytext,
  `invisible` int(1) DEFAULT NULL,
  `class` tinytext
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_sommaire_categories`
--

CREATE TABLE IF NOT EXISTS `nuke_sommaire_categories` (
`id` int(11) NOT NULL,
  `groupmenu` int(2) NOT NULL DEFAULT '0',
  `module` varchar(50) NOT NULL DEFAULT '',
  `url` text NOT NULL,
  `url_text` text NOT NULL,
  `image` varchar(50) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=464 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_spelling_words`
--

CREATE TABLE IF NOT EXISTS `nuke_spelling_words` (
`id` mediumint(9) NOT NULL,
  `word` varchar(30) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
  `sound` varchar(10) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=192935 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_staff`
--

CREATE TABLE IF NOT EXISTS `nuke_staff` (
  `id` int(3) NOT NULL DEFAULT '0',
`sid` int(3) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `des` mediumtext NOT NULL,
  `rank` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `photo` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_staff_cat`
--

CREATE TABLE IF NOT EXISTS `nuke_staff_cat` (
`id` int(3) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stats_date`
--

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

CREATE TABLE IF NOT EXISTS `nuke_stats_month` (
  `year` smallint(6) NOT NULL DEFAULT '0',
  `month` tinyint(4) NOT NULL DEFAULT '0',
  `hits` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stats_year`
--

CREATE TABLE IF NOT EXISTS `nuke_stats_year` (
  `year` smallint(6) NOT NULL DEFAULT '0',
  `hits` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stories`
--

CREATE TABLE IF NOT EXISTS `nuke_stories` (
`sid` int(11) NOT NULL,
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
  `featured_image` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=18259 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stories_cat`
--

CREATE TABLE IF NOT EXISTS `nuke_stories_cat` (
`catid` int(11) NOT NULL,
  `title` varchar(20) NOT NULL DEFAULT '',
  `counter` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_stories_view`
--

CREATE TABLE IF NOT EXISTS `nuke_stories_view` (
`id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=1867 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_topics`
--

CREATE TABLE IF NOT EXISTS `nuke_topics` (
`topicid` int(3) NOT NULL,
  `topicname` varchar(20) DEFAULT NULL,
  `topicimage` varchar(20) DEFAULT NULL,
  `topictext` varchar(40) DEFAULT NULL,
  `counter` int(11) NOT NULL DEFAULT '0',
  `desc` mediumtext NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_upermissions`
--

CREATE TABLE IF NOT EXISTS `nuke_upermissions` (
`pid` int(16) NOT NULL,
  `uid` int(16) NOT NULL DEFAULT '0',
  `pmodule` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users`
--

CREATE TABLE IF NOT EXISTS `nuke_users` (
`user_id` int(10) NOT NULL,
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
  `meta` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=73605 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci PACK_KEYS=0;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_autologin`
--

CREATE TABLE IF NOT EXISTS `nuke_users_autologin` (
`autologin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `autologin_token` varchar(128) NOT NULL,
  `autologin_time` int(11) NOT NULL,
  `autologin_expire` int(11) NOT NULL,
  `autologin_last` int(11) NOT NULL,
  `autologin_ip` varchar(128) NOT NULL,
  `autologin_hostname` varchar(256) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=49443 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_flags`
--

CREATE TABLE IF NOT EXISTS `nuke_users_flags` (
`user_id` int(11) NOT NULL,
  `newsletter_daily` bit(1) NOT NULL DEFAULT b'1',
  `newsletter_weekly` bit(1) NOT NULL DEFAULT b'1',
  `newsletter_weekly_last` datetime NOT NULL,
  `newsletter_monthly` bit(1) NOT NULL DEFAULT b'1',
  `notify_photocomp` bit(1) NOT NULL DEFAULT b'1',
  `notify_pm` bit(1) NOT NULL DEFAULT b'1',
  `notify_forums` bit(1) NOT NULL DEFAULT b'1'
) ENGINE=InnoDB AUTO_INCREMENT=43706 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_groups`
--

CREATE TABLE IF NOT EXISTS `nuke_users_groups` (
`gid` int(11) NOT NULL,
  `gname` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_groups_users`
--

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

CREATE TABLE IF NOT EXISTS `nuke_users_hash` (
  `user_id` int(11) NOT NULL,
  `hash` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) NOT NULL,
  `ip` varchar(128) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_notes`
--

CREATE TABLE IF NOT EXISTS `nuke_users_notes` (
`nid` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `aid` int(11) NOT NULL DEFAULT '0',
  `datetime` int(11) NOT NULL DEFAULT '0',
  `data` mediumtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=3320188 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuke_users_temp`
--

CREATE TABLE IF NOT EXISTS `nuke_users_temp` (
`user_id` int(10) NOT NULL,
  `username` varchar(25) NOT NULL DEFAULT '',
  `user_email` varchar(255) NOT NULL DEFAULT '',
  `user_password` varchar(40) NOT NULL DEFAULT '',
  `user_regdate` varchar(20) NOT NULL DEFAULT '',
  `check_num` varchar(50) NOT NULL DEFAULT '',
  `time` varchar(14) NOT NULL DEFAULT '',
  `email_sent` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer`
--

CREATE TABLE IF NOT EXISTS `oauth_consumer` (
`id` int(11) NOT NULL,
  `consumer_key` varchar(250) NOT NULL,
  `consumer_secret` varchar(250) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `dateadded` bigint(20) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1042 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_nonce`
--

CREATE TABLE IF NOT EXISTS `oauth_consumer_nonce` (
`id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `timestamp` bigint(20) NOT NULL,
  `nonce` varchar(250) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_token`
--

CREATE TABLE IF NOT EXISTS `oauth_token` (
`id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `token` varchar(250) NOT NULL,
  `token_secret` varchar(250) NOT NULL,
  `callback_url` varchar(250) NOT NULL,
  `verifier` varchar(250) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_token_type`
--

CREATE TABLE IF NOT EXISTS `oauth_token_type` (
`id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `operators`
--

CREATE TABLE IF NOT EXISTS `operators` (
`operator_id` int(11) NOT NULL,
  `operator_name` varchar(128) NOT NULL,
  `operator_desc` text NOT NULL,
  `organisation_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `organisation`
--

CREATE TABLE IF NOT EXISTS `organisation` (
`organisation_id` int(10) NOT NULL,
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
  `organisation_slug` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `organisation_member`
--

CREATE TABLE IF NOT EXISTS `organisation_member` (
  `organisation_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `organisation_roles`
--

CREATE TABLE IF NOT EXISTS `organisation_roles` (
`role_id` int(11) NOT NULL,
  `role_name` text NOT NULL,
  `organisation_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_actions`
--

CREATE TABLE IF NOT EXISTS `phpbb_reports_actions` (
`action_id` mediumint(8) unsigned NOT NULL,
  `report_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `action_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `action_time` int(11) NOT NULL DEFAULT '0',
  `action` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `action_comments` text CHARACTER SET latin1,
  `action_status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=8646 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_config`
--

CREATE TABLE IF NOT EXISTS `phpbb_reports_config` (
  `config_name` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `config_value` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_data`
--

CREATE TABLE IF NOT EXISTS `phpbb_reports_data` (
`data_id` mediumint(8) unsigned NOT NULL,
  `data_name` varchar(30) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `data_desc` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `data_comments` tinyint(1) NOT NULL DEFAULT '0',
  `data_order` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `data_code` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_reports_posts`
--

CREATE TABLE IF NOT EXISTS `phpbb_reports_posts` (
`report_id` mediumint(8) unsigned NOT NULL,
  `post_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `poster_id` mediumint(8) NOT NULL DEFAULT '0',
  `report_user_id` mediumint(8) NOT NULL DEFAULT '0',
  `report_time` int(11) NOT NULL DEFAULT '0',
  `report_reason` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `report_comments` text CHARACTER SET latin1,
  `report_status` tinyint(1) NOT NULL DEFAULT '0',
  `report_action_time` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=8608 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `phpbb_warnings`
--

CREATE TABLE IF NOT EXISTS `phpbb_warnings` (
`warn_id` int(30) NOT NULL,
  `user_id` int(30) NOT NULL DEFAULT '0',
  `warned_by` int(30) NOT NULL DEFAULT '0',
  `warn_reason` text CHARACTER SET latin1,
  `mod_comments` text CHARACTER SET latin1,
  `actiontaken` text CHARACTER SET latin1,
  `warn_date` int(30) NOT NULL DEFAULT '0',
  `old_warning_level` int(11) NOT NULL,
  `new_warning_level` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7954 DEFAULT CHARSET=utf8 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE IF NOT EXISTS `polls` (
`poll_id` int(11) NOT NULL,
  `poll_name` varchar(32) NOT NULL,
  `poll_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `poll_votes` int(11) NOT NULL,
  `poll_options` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `popover_viewed`
--

CREATE TABLE IF NOT EXISTS `popover_viewed` (
  `popover_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `privmsgs_hidelist`
--

CREATE TABLE IF NOT EXISTS `privmsgs_hidelist` (
  `privmsgs_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `railcams`
--

CREATE TABLE IF NOT EXISTS `railcams` (
`id` int(11) NOT NULL,
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
  `meta` text
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `railcams_type`
--

CREATE TABLE IF NOT EXISTS `railcams_type` (
`id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `slug` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rating_loco`
--

CREATE TABLE IF NOT EXISTS `rating_loco` (
`rating_id` int(11) NOT NULL,
  `loco_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` float NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE IF NOT EXISTS `reminders` (
`id` int(11) NOT NULL,
  `module` varchar(16) NOT NULL,
  `namespace` varchar(32) NOT NULL,
  `object` varchar(32) NOT NULL,
  `object_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reminder` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `dispatched` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `title` text NOT NULL,
  `text` text NOT NULL,
  `sent` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `route`
--

CREATE TABLE IF NOT EXISTS `route` (
`id` int(11) NOT NULL,
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
  `download_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9596 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `route_markers`
--

CREATE TABLE IF NOT EXISTS `route_markers` (
`id` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `lat` varchar(256) NOT NULL,
  `lon` varchar(256) NOT NULL,
  `name` varchar(1024) NOT NULL,
  `timing` tinyint(1) NOT NULL DEFAULT '0',
  `route_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=8161 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `route_markers_tmp`
--

CREATE TABLE IF NOT EXISTS `route_markers_tmp` (
`id` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `lat` varchar(256) NOT NULL,
  `lon` varchar(256) NOT NULL,
  `name` varchar(1024) NOT NULL,
  `timing` tinyint(1) NOT NULL DEFAULT '0',
  `route_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sighting`
--

CREATE TABLE IF NOT EXISTS `sighting` (
`id` int(11) NOT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'Australia/Melbourne',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_added` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lat` decimal(11,8) NOT NULL,
  `lon` decimal(11,8) NOT NULL,
  `text` varchar(2048) DEFAULT NULL,
  `traincode` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `loco_ids` text,
  `meta` text
) ENGINE=InnoDB AUTO_INCREMENT=7544 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sighting_locos`
--

CREATE TABLE IF NOT EXISTS `sighting_locos` (
  `sighting_id` int(11) NOT NULL,
  `loco_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `source`
--

CREATE TABLE IF NOT EXISTS `source` (
`id` int(11) NOT NULL,
  `title` varchar(256) NOT NULL,
  `desc` text NOT NULL,
  `url` varchar(512) NOT NULL,
  `image` varchar(512) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sph_counter`
--

CREATE TABLE IF NOT EXISTS `sph_counter` (
  `counter_id` int(11) NOT NULL,
  `max_doc_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE IF NOT EXISTS `tag` (
`tag_id` int(11) NOT NULL,
  `tag` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4252 DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `tag_link`
--

CREATE TABLE IF NOT EXISTS `tag_link` (
`tag_link_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=12546 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_entries`
--

CREATE TABLE IF NOT EXISTS `timetable_entries` (
`id` int(11) NOT NULL,
  `point_id` int(11) NOT NULL,
  `expires` date NOT NULL DEFAULT '0000-00-00',
  `starts` date NOT NULL DEFAULT '0000-00-00',
  `train_id` int(10) NOT NULL,
  `day` int(11) NOT NULL,
  `time` time NOT NULL,
  `going` enum('arr','dep') NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=19297 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_points`
--

CREATE TABLE IF NOT EXISTS `timetable_points` (
`id` int(10) NOT NULL,
  `name` varchar(128) NOT NULL,
  `lat` double(16,13) NOT NULL,
  `lon` double(16,13) NOT NULL,
  `route_id` int(10) NOT NULL DEFAULT '0',
  `slug` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=202 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_regions`
--

CREATE TABLE IF NOT EXISTS `timetable_regions` (
`id` int(10) NOT NULL,
  `state` varchar(12) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_trains`
--

CREATE TABLE IF NOT EXISTS `timetable_trains` (
`id` int(10) NOT NULL,
  `provider` enum('artc','pbr') NOT NULL DEFAULT 'artc',
  `train_number` varchar(128) NOT NULL,
  `train_name` varchar(512) NOT NULL,
  `train_desc` text NOT NULL,
  `operator_id` int(10) NOT NULL,
  `gauge_id` int(10) NOT NULL,
  `meta` text NOT NULL,
  `commodity` int(11) NOT NULL,
  `slug` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=300 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `viewed_threads`
--

CREATE TABLE IF NOT EXISTS `viewed_threads` (
`id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=134015 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `waynet`
--

CREATE TABLE IF NOT EXISTS `waynet` (
`id` int(11) NOT NULL,
  `trainnum` varchar(12) NOT NULL,
  `loco` varchar(12) NOT NULL,
  `linekms` varchar(12) NOT NULL,
  `linename` varchar(64) NOT NULL,
  `lineid` int(11) NOT NULL,
  `lat` varchar(32) NOT NULL,
  `lon` varchar(32) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1403055 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wheel_arrangements`
--

CREATE TABLE IF NOT EXISTS `wheel_arrangements` (
`id` int(11) NOT NULL,
  `title` varchar(256) NOT NULL,
  `arrangement` varchar(256) NOT NULL,
  `slug` varchar(32) NOT NULL,
  `image` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `woecache`
--

CREATE TABLE IF NOT EXISTS `woecache` (
`id` int(11) NOT NULL,
  `lat` decimal(11,8) NOT NULL,
  `lon` decimal(11,8) NOT NULL,
  `response` longtext,
  `stored` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `address` longtext
) ENGINE=InnoDB AUTO_INCREMENT=17413 DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api`
--
ALTER TABLE `api`
 ADD UNIQUE KEY `api_key` (`api_key`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `asset`
--
ALTER TABLE `asset`
 ADD PRIMARY KEY (`id`), ADD KEY `type_id` (`type_id`), ADD KEY `hash` (`hash`);

--
-- Indexes for table `asset_bak`
--
ALTER TABLE `asset_bak`
 ADD PRIMARY KEY (`id`), ADD KEY `type_id` (`type_id`,`date`,`user_id`), ADD KEY `loco_id` (`namespace_key`), ADD KEY `namespace` (`namespace`(255)), ADD KEY `hash` (`hash`);

--
-- Indexes for table `asset_link`
--
ALTER TABLE `asset_link`
 ADD PRIMARY KEY (`asset_link_id`), ADD KEY `type_id` (`date`,`user_id`), ADD KEY `loco_id` (`namespace_key`), ADD KEY `namespace` (`namespace`(255)), ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `asset_type`
--
ALTER TABLE `asset_type`
 ADD PRIMARY KEY (`id`), ADD KEY `type` (`type`);

--
-- Indexes for table `bancontrol`
--
ALTER TABLE `bancontrol`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `ban_active` (`ban_active`), ADD KEY `banned_by` (`banned_by`), ADD KEY `ip` (`ip`);

--
-- Indexes for table `ban_domains`
--
ALTER TABLE `ban_domains`
 ADD PRIMARY KEY (`domain_id`);

--
-- Indexes for table `cache_woe`
--
ALTER TABLE `cache_woe`
 ADD PRIMARY KEY (`id`), ADD KEY `expiry` (`expiry`), ADD KEY `hash` (`hash`);

--
-- Indexes for table `chronicle_item`
--
ALTER TABLE `chronicle_item`
 ADD PRIMARY KEY (`id`), ADD KEY `date` (`date`,`type_id`), ADD KEY `status` (`status`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chronicle_link`
--
ALTER TABLE `chronicle_link`
 ADD PRIMARY KEY (`id`), ADD KEY `item_id` (`item_id`,`module`,`object`,`object_id`);

--
-- Indexes for table `chronicle_type`
--
ALTER TABLE `chronicle_type`
 ADD PRIMARY KEY (`id`), ADD KEY `grouping` (`grouping`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `download_categories`
--
ALTER TABLE `download_categories`
 ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `download_hits`
--
ALTER TABLE `download_hits`
 ADD PRIMARY KEY (`id`), ADD KEY `download_id` (`download_id`,`date`,`user_id`);

--
-- Indexes for table `download_items`
--
ALTER TABLE `download_items`
 ADD PRIMARY KEY (`id`), ADD KEY `category_id` (`category_id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
 ADD PRIMARY KEY (`id`), ADD KEY `organisation_id` (`organisation_id`), ADD KEY `lat` (`lat`,`lon`), ADD KEY `category_id` (`category_id`), ADD KEY `slug` (`slug`), ADD KEY `status` (`status`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_categories`
--
ALTER TABLE `event_categories`
 ADD UNIQUE KEY `id_2` (`id`), ADD KEY `id` (`id`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `event_dates`
--
ALTER TABLE `event_dates`
 ADD PRIMARY KEY (`id`), ADD KEY `event_id` (`event_id`,`date`,`start`), ADD KEY `approved` (`status`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`,`area`);

--
-- Indexes for table `feedback_area`
--
ALTER TABLE `feedback_area`
 ADD PRIMARY KEY (`feedback_id`);

--
-- Indexes for table `feedback_status`
--
ALTER TABLE `feedback_status`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flickr_cache`
--
ALTER TABLE `flickr_cache`
 ADD KEY `request` (`request`);

--
-- Indexes for table `flickr_favourites`
--
ALTER TABLE `flickr_favourites`
 ADD KEY `photo_id` (`photo_id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `flickr_geodata`
--
ALTER TABLE `flickr_geodata`
 ADD PRIMARY KEY (`photo_id`), ADD KEY `lat` (`lat`), ADD KEY `lon` (`lon`), ADD KEY `owner` (`owner`);

--
-- Indexes for table `flickr_rating`
--
ALTER TABLE `flickr_rating`
 ADD PRIMARY KEY (`id`), ADD KEY `photo_id` (`photo_id`,`user_id`);

--
-- Indexes for table `fwlink`
--
ALTER TABLE `fwlink`
 ADD PRIMARY KEY (`id`), ADD KEY `url` (`url`(255));

--
-- Indexes for table `gallery_mig_album`
--
ALTER TABLE `gallery_mig_album`
 ADD PRIMARY KEY (`id`), ADD KEY `parent` (`parent`), ADD KEY `name` (`name`,`parent_id`), ADD KEY `parent_id` (`parent_id`), ADD KEY `owner_2` (`owner`,`owner_id`);

--
-- Indexes for table `gallery_mig_image`
--
ALTER TABLE `gallery_mig_image`
 ADD PRIMARY KEY (`id`), ADD KEY `album_id` (`album_id`,`owner`), ADD KEY `date_taken` (`date_taken`,`date_uploaded`), ADD KEY `hidden` (`hidden`), ADD KEY `lat` (`lat`,`lon`);

--
-- Indexes for table `gallery_mig_image_sizes`
--
ALTER TABLE `gallery_mig_image_sizes`
 ADD PRIMARY KEY (`id`), ADD KEY `photo_id` (`photo_id`,`size`,`width`,`height`);

--
-- Indexes for table `geoplace`
--
ALTER TABLE `geoplace`
 ADD PRIMARY KEY (`id`), ADD KEY `country_code` (`country_code`,`region_code`), ADD SPATIAL KEY `point` (`point`);

--
-- Indexes for table `geoplace_forecast`
--
ALTER TABLE `geoplace_forecast`
 ADD PRIMARY KEY (`id`), ADD KEY `geoplace` (`geoplace`,`expires`), ADD KEY `date` (`date`);

--
-- Indexes for table `glossary`
--
ALTER TABLE `glossary`
 ADD PRIMARY KEY (`id`), ADD KEY `type` (`type`), ADD KEY `date` (`date`), ADD KEY `author` (`author`), ADD KEY `status` (`status`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `idea_categories`
--
ALTER TABLE `idea_categories`
 ADD PRIMARY KEY (`id`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `idea_ideas`
--
ALTER TABLE `idea_ideas`
 ADD PRIMARY KEY (`id`), ADD KEY `slug` (`slug`,`votes`), ADD KEY `author` (`author`), ADD KEY `category_id` (`category_id`), ADD KEY `date` (`date`), ADD KEY `status` (`status`), ADD KEY `forum_thread_id` (`forum_thread_id`), ADD KEY `redmine_id` (`redmine_id`);

--
-- Indexes for table `idea_votes`
--
ALTER TABLE `idea_votes`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `idea_id` (`idea_id`);

--
-- Indexes for table `image`
--
ALTER TABLE `image`
 ADD PRIMARY KEY (`id`), ADD KEY `image_source` (`provider`,`photo_id`,`modified`), ADD KEY `lat` (`lat`,`lon`), ADD KEY `hits_today` (`hits_today`,`hits_weekly`,`hits_overall`), ADD KEY `geoplace` (`geoplace`), ADD KEY `user_id` (`user_id`), ADD KEY `hidden` (`hidden`), ADD KEY `captured` (`captured`), ADD KEY `flags` (`flags`);

--
-- Indexes for table `image_camera`
--
ALTER TABLE `image_camera`
 ADD PRIMARY KEY (`id`), ADD KEY `make` (`make`,`model`);

--
-- Indexes for table `image_collection`
--
ALTER TABLE `image_collection`
 ADD PRIMARY KEY (`id`), ADD KEY `slug` (`slug`,`created`,`modified`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `image_competition`
--
ALTER TABLE `image_competition`
 ADD PRIMARY KEY (`id`), ADD KEY `slug` (`slug`,`status`,`voting_date_open`,`voting_date_close`,`author`), ADD KEY `submissions_date_open` (`submissions_date_open`,`submissions_date_close`);

--
-- Indexes for table `image_competition_submissions`
--
ALTER TABLE `image_competition_submissions`
 ADD PRIMARY KEY (`id`), ADD KEY `competition_id` (`competition_id`,`user_id`,`image_id`,`date_added`,`status`), ADD KEY `winner` (`winner`);

--
-- Indexes for table `image_competition_votes`
--
ALTER TABLE `image_competition_votes`
 ADD PRIMARY KEY (`id`), ADD KEY `competition_id` (`competition_id`,`user_id`,`image_id`), ADD KEY `date` (`date`);

--
-- Indexes for table `image_exif`
--
ALTER TABLE `image_exif`
 ADD PRIMARY KEY (`image_id`), ADD KEY `camera_id` (`camera_id`,`lens_id`,`aperture`,`exposure_id`,`exposure_program_id`,`focal_length`,`iso`,`white_balance_id`), ADD KEY `lens_sn_id` (`lens_sn_id`);

--
-- Indexes for table `image_exposure`
--
ALTER TABLE `image_exposure`
 ADD PRIMARY KEY (`id`), ADD KEY `exposure` (`exposure`);

--
-- Indexes for table `image_exposure_program`
--
ALTER TABLE `image_exposure_program`
 ADD PRIMARY KEY (`id`), ADD KEY `program` (`program`);

--
-- Indexes for table `image_favourites`
--
ALTER TABLE `image_favourites`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_image_favourites_user_id_image_id` (`user_id`,`image_id`), ADD KEY `date` (`date`);

--
-- Indexes for table `image_flags`
--
ALTER TABLE `image_flags`
 ADD PRIMARY KEY (`image_id`), ADD KEY `published` (`published`,`screened`,`screened_by`,`screened_on`,`rejected`), ADD KEY `screened_pick` (`screened_pick`), ADD KEY `exifqueue` (`exifqueue`);

--
-- Indexes for table `image_flags_skip`
--
ALTER TABLE `image_flags_skip`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `image_id` (`image_id`,`user_id`), ADD KEY `date` (`date`);

--
-- Indexes for table `image_lens`
--
ALTER TABLE `image_lens`
 ADD PRIMARY KEY (`id`), ADD KEY `model` (`model`);

--
-- Indexes for table `image_lens_sn`
--
ALTER TABLE `image_lens_sn`
 ADD PRIMARY KEY (`id`), ADD KEY `sn` (`sn`);

--
-- Indexes for table `image_link`
--
ALTER TABLE `image_link`
 ADD PRIMARY KEY (`id`), ADD KEY `namespace` (`namespace`,`namespace_key`,`ignored`), ADD KEY `image_id` (`image_id`), ADD KEY `added` (`added`);

--
-- Indexes for table `image_position`
--
ALTER TABLE `image_position`
 ADD PRIMARY KEY (`id`), ADD KEY `image_id` (`image_id`,`image_type`,`namespace`,`namespace_key`);

--
-- Indexes for table `image_software`
--
ALTER TABLE `image_software`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`), ADD KEY `version` (`version`);

--
-- Indexes for table `image_weekly`
--
ALTER TABLE `image_weekly`
 ADD PRIMARY KEY (`id`), ADD KEY `image_id` (`image_id`,`datefrom`,`added_by`);

--
-- Indexes for table `image_whitebalance`
--
ALTER TABLE `image_whitebalance`
 ADD PRIMARY KEY (`id`), ADD KEY `whitebalance` (`whitebalance`);

--
-- Indexes for table `jn_applications`
--
ALTER TABLE `jn_applications`
 ADD PRIMARY KEY (`jn_application_id`), ADD KEY `jn_job_id` (`jn_job_id`,`user_id`,`jn_application_time`);

--
-- Indexes for table `jn_classifications`
--
ALTER TABLE `jn_classifications`
 ADD PRIMARY KEY (`jn_classification_id`), ADD KEY `jn_parent_id` (`jn_parent_id`);

--
-- Indexes for table `jn_jobs`
--
ALTER TABLE `jn_jobs`
 ADD PRIMARY KEY (`job_id`), ADD KEY `organisation_id` (`organisation_id`,`job_location_id`,`job_expiry`,`job_classification_id`,`job_salary`), ADD KEY `job_thread_id` (`job_thread_id`), ADD KEY `reference_id` (`reference_id`), ADD KEY `job_added` (`job_added`);

--
-- Indexes for table `jn_locations`
--
ALTER TABLE `jn_locations`
 ADD PRIMARY KEY (`jn_location_id`), ADD KEY `jn_parent_id` (`jn_parent_id`);

--
-- Indexes for table `loadstats`
--
ALTER TABLE `loadstats`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
 ADD PRIMARY KEY (`id`), ADD KEY `country` (`country`,`region`), ADD KEY `locality` (`locality`,`neighbourhood`), ADD KEY `region` (`region`), ADD KEY `camera_id` (`camera_id`), ADD KEY `active` (`active`), ADD KEY `date_modified` (`date_modified`), ADD KEY `date_added` (`date_added`), ADD KEY `topicid` (`topicid`), ADD KEY `lat` (`lat`), ADD KEY `long` (`long`), ADD KEY `country_slug` (`country_slug`), ADD KEY `region_slug` (`region_slug`), ADD KEY `geoplace` (`geoplace`);

--
-- Indexes for table `locations_like`
--
ALTER TABLE `locations_like`
 ADD KEY `location_id` (`location_id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `location_corrections`
--
ALTER TABLE `location_corrections`
 ADD PRIMARY KEY (`id`), ADD KEY `location_id` (`location_id`,`user_id`,`status`), ADD KEY `date_added` (`date_added`,`date_closed`);

--
-- Indexes for table `location_date`
--
ALTER TABLE `location_date`
 ADD PRIMARY KEY (`id`), ADD KEY `date` (`date`,`type_id`), ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `location_datetypes`
--
ALTER TABLE `location_datetypes`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loco_class`
--
ALTER TABLE `loco_class`
 ADD PRIMARY KEY (`id`), ADD KEY `loco_type_id` (`loco_type_id`), ADD KEY `manufacturer_id` (`manufacturer_id`), ADD KEY `Model` (`Model`(255)), ADD KEY `asset_id` (`asset_id`), ADD KEY `country` (`country`);

--
-- Indexes for table `loco_date_type`
--
ALTER TABLE `loco_date_type`
 ADD PRIMARY KEY (`loco_date_id`);

--
-- Indexes for table `loco_gauge`
--
ALTER TABLE `loco_gauge`
 ADD PRIMARY KEY (`gauge_id`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `loco_groups`
--
ALTER TABLE `loco_groups`
 ADD PRIMARY KEY (`group_id`), ADD KEY `active` (`active`);

--
-- Indexes for table `loco_groups_members`
--
ALTER TABLE `loco_groups_members`
 ADD PRIMARY KEY (`id`), ADD KEY `loco_unit_id` (`loco_unit_id`), ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `loco_hits`
--
ALTER TABLE `loco_hits`
 ADD KEY `loco_id` (`loco_id`,`class_id`,`user_id`);

--
-- Indexes for table `loco_link`
--
ALTER TABLE `loco_link`
 ADD PRIMARY KEY (`link_id`), ADD KEY `loco_id_a` (`loco_id_a`), ADD KEY `loco_id_b` (`loco_id_b`), ADD KEY `link_type_id` (`link_type_id`);

--
-- Indexes for table `loco_link_type`
--
ALTER TABLE `loco_link_type`
 ADD PRIMARY KEY (`link_type_id`);

--
-- Indexes for table `loco_livery`
--
ALTER TABLE `loco_livery`
 ADD PRIMARY KEY (`livery_id`), ADD KEY `superseded_by` (`superseded_by`), ADD KEY `supersedes` (`supersedes`), ADD KEY `region` (`region`), ADD KEY `country` (`country`);

--
-- Indexes for table `loco_manufacturer`
--
ALTER TABLE `loco_manufacturer`
 ADD PRIMARY KEY (`manufacturer_id`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `loco_notes`
--
ALTER TABLE `loco_notes`
 ADD PRIMARY KEY (`note_id`), ADD KEY `loco_id` (`loco_id`,`user_id`);

--
-- Indexes for table `loco_org_link`
--
ALTER TABLE `loco_org_link`
 ADD PRIMARY KEY (`id`), ADD KEY `loco_id` (`loco_id`), ADD KEY `operator_id` (`operator_id`), ADD KEY `link_type` (`link_type`), ADD KEY `link_weight` (`link_weight`);

--
-- Indexes for table `loco_org_link_type`
--
ALTER TABLE `loco_org_link_type`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loco_status`
--
ALTER TABLE `loco_status`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loco_type`
--
ALTER TABLE `loco_type`
 ADD PRIMARY KEY (`id`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `loco_unit`
--
ALTER TABLE `loco_unit`
 ADD PRIMARY KEY (`loco_id`), ADD KEY `loco_gauge_id` (`loco_gauge_id`), ADD KEY `loco_status_id` (`loco_status_id`), ADD KEY `loco_num` (`loco_num`), ADD KEY `manufacturer_id` (`manufacturer_id`), ADD KEY `class_id` (`class_id`), ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `loco_unit_corrections`
--
ALTER TABLE `loco_unit_corrections`
 ADD PRIMARY KEY (`correction_id`), ADD KEY `loco_id` (`loco_id`), ADD KEY `user_id` (`user_id`), ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `loco_unit_date`
--
ALTER TABLE `loco_unit_date`
 ADD PRIMARY KEY (`date_id`), ADD KEY `loco_unit_id` (`loco_unit_id`), ADD KEY `date_id` (`loco_date_id`), ADD KEY `timestamp` (`timestamp`), ADD KEY `date_end` (`date_end`);

--
-- Indexes for table `loco_unit_livery`
--
ALTER TABLE `loco_unit_livery`
 ADD PRIMARY KEY (`id`), ADD KEY `provider` (`provider`,`photo_id`,`loco_id`,`livery_id`), ADD KEY `added` (`added`), ADD KEY `ignored` (`ignored`);

--
-- Indexes for table `loco_unit_source`
--
ALTER TABLE `loco_unit_source`
 ADD PRIMARY KEY (`id`), ADD KEY `loco_id` (`loco_id`,`source_id`);

--
-- Indexes for table `log_api`
--
ALTER TABLE `log_api`
 ADD PRIMARY KEY (`id`), ADD KEY `version` (`version`), ADD KEY `date` (`date`);

--
-- Indexes for table `log_downloads`
--
ALTER TABLE `log_downloads`
 ADD PRIMARY KEY (`id`), ADD KEY `download_id` (`download_id`,`date`,`ip`,`user_id`);

--
-- Indexes for table `log_errors`
--
ALTER TABLE `log_errors`
 ADD PRIMARY KEY (`error_id`);

--
-- Indexes for table `log_general`
--
ALTER TABLE `log_general`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`,`timestamp`), ADD KEY `key` (`key`);

--
-- Indexes for table `log_herrings`
--
ALTER TABLE `log_herrings`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_locos`
--
ALTER TABLE `log_locos`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`,`timestamp`), ADD KEY `loco_id` (`loco_id`,`class_id`);

--
-- Indexes for table `log_logins`
--
ALTER TABLE `log_logins`
 ADD PRIMARY KEY (`login_id`), ADD KEY `user_id` (`user_id`), ADD KEY `login_time` (`login_time`);

--
-- Indexes for table `log_pageactivity`
--
ALTER TABLE `log_pageactivity`
 ADD KEY `time` (`time`);

--
-- Indexes for table `log_staff`
--
ALTER TABLE `log_staff`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `timestamp` (`timestamp`), ADD KEY `title` (`title`), ADD KEY `key` (`key`,`key_val`);

--
-- Indexes for table `log_useractivity`
--
ALTER TABLE `log_useractivity`
 ADD PRIMARY KEY (`log_id`), ADD KEY `user_id` (`user_id`), ADD KEY `ip` (`ip`), ADD KEY `module_id` (`module_id`,`date`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
 ADD PRIMARY KEY (`message_id`), ADD KEY `message_active` (`message_active`), ADD KEY `message_title` (`message_title`), ADD KEY `date_start` (`date_start`,`date_end`), ADD KEY `object_ns` (`object_ns`,`object_id`), ADD KEY `target_user` (`target_user`);

--
-- Indexes for table `messages_viewed`
--
ALTER TABLE `messages_viewed`
 ADD PRIMARY KEY (`row_id`), ADD KEY `message_id` (`message_id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `newsletter`
--
ALTER TABLE `newsletter`
 ADD PRIMARY KEY (`id`), ADD KEY `publishdate` (`publishdate`,`status`), ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `newsletter_templates`
--
ALTER TABLE `newsletter_templates`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_feed`
--
ALTER TABLE `news_feed`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
 ADD PRIMARY KEY (`id`), ADD KEY `recipient` (`author`,`transport`,`status`,`date_queued`,`date_sent`);

--
-- Indexes for table `notifications_recipients`
--
ALTER TABLE `notifications_recipients`
 ADD PRIMARY KEY (`id`), ADD KEY `notification_id` (`notification_id`,`user_id`,`date_sent`,`status`);

--
-- Indexes for table `notification_prefs`
--
ALTER TABLE `notification_prefs`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `notify_off` (`notify_off`), ADD KEY `notify_topic_reply` (`notify_topic_reply`), ADD KEY `notify_pm` (`notify_pm`), ADD KEY `notify_job_apply` (`notify_job_apply`);

--
-- Indexes for table `notification_rules`
--
ALTER TABLE `notification_rules`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `namespace` (`namespace`(255));

--
-- Indexes for table `notification_sent`
--
ALTER TABLE `notification_sent`
 ADD PRIMARY KEY (`id`), ADD KEY `timestamp` (`timestamp`,`user_id`,`namespace`(255),`template_id`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
 ADD PRIMARY KEY (`id`), ADD KEY `namespace` (`namespace`(255));

--
-- Indexes for table `nuke_alliance`
--
ALTER TABLE `nuke_alliance`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nuke_authors`
--
ALTER TABLE `nuke_authors`
 ADD PRIMARY KEY (`aid`), ADD KEY `aid` (`aid`);

--
-- Indexes for table `nuke_bbarcade`
--
ALTER TABLE `nuke_bbarcade`
 ADD PRIMARY KEY (`arcade_name`);

--
-- Indexes for table `nuke_bbarcade_categories`
--
ALTER TABLE `nuke_bbarcade_categories`
 ADD KEY `arcade_catid` (`arcade_catid`);

--
-- Indexes for table `nuke_bbauth_access`
--
ALTER TABLE `nuke_bbauth_access`
 ADD KEY `group_id` (`group_id`), ADD KEY `forum_id` (`forum_id`);

--
-- Indexes for table `nuke_bbauth_arcade_access`
--
ALTER TABLE `nuke_bbauth_arcade_access`
 ADD KEY `group_id` (`group_id`), ADD KEY `arcade_catid` (`arcade_catid`);

--
-- Indexes for table `nuke_bbbanlist`
--
ALTER TABLE `nuke_bbbanlist`
 ADD PRIMARY KEY (`ban_id`), ADD KEY `ban_ip_user_id` (`ban_ip`,`ban_userid`);

--
-- Indexes for table `nuke_bbcategories`
--
ALTER TABLE `nuke_bbcategories`
 ADD PRIMARY KEY (`cat_id`), ADD KEY `cat_order` (`cat_order`);

--
-- Indexes for table `nuke_bbconfig`
--
ALTER TABLE `nuke_bbconfig`
 ADD PRIMARY KEY (`config_name`);

--
-- Indexes for table `nuke_bbconfirm`
--
ALTER TABLE `nuke_bbconfirm`
 ADD PRIMARY KEY (`session_id`,`confirm_id`);

--
-- Indexes for table `nuke_bbdisallow`
--
ALTER TABLE `nuke_bbdisallow`
 ADD PRIMARY KEY (`disallow_id`);

--
-- Indexes for table `nuke_bbforums`
--
ALTER TABLE `nuke_bbforums`
 ADD PRIMARY KEY (`forum_id`), ADD KEY `cat_id` (`cat_id`), ADD KEY `forum_order` (`forum_order`);

--
-- Indexes for table `nuke_bbforum_prune`
--
ALTER TABLE `nuke_bbforum_prune`
 ADD PRIMARY KEY (`prune_id`), ADD KEY `forum_id` (`forum_id`);

--
-- Indexes for table `nuke_bbgames`
--
ALTER TABLE `nuke_bbgames`
 ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `nuke_bbgroups`
--
ALTER TABLE `nuke_bbgroups`
 ADD PRIMARY KEY (`group_id`), ADD KEY `group_single_user` (`group_single_user`), ADD KEY `organisation_id` (`organisation_id`);

--
-- Indexes for table `nuke_bbposts`
--
ALTER TABLE `nuke_bbposts`
 ADD PRIMARY KEY (`post_id`), ADD KEY `forum_id` (`forum_id`), ADD KEY `topic_id` (`topic_id`), ADD KEY `poster_id` (`poster_id`), ADD KEY `post_time` (`post_time`), ADD KEY `pinned` (`pinned`);

--
-- Indexes for table `nuke_bbposts_edit`
--
ALTER TABLE `nuke_bbposts_edit`
 ADD PRIMARY KEY (`edit_id`), ADD KEY `post_id` (`post_id`), ADD KEY `thread_id` (`thread_id`), ADD KEY `poster_id` (`poster_id`), ADD KEY `editor_id` (`editor_id`);

--
-- Indexes for table `nuke_bbposts_reputation`
--
ALTER TABLE `nuke_bbposts_reputation`
 ADD PRIMARY KEY (`id`), ADD KEY `post_id` (`post_id`,`type`,`date`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `nuke_bbposts_text`
--
ALTER TABLE `nuke_bbposts_text`
 ADD PRIMARY KEY (`post_id`), ADD KEY `url_slug` (`url_slug`);

--
-- Indexes for table `nuke_bbprivmsgs`
--
ALTER TABLE `nuke_bbprivmsgs`
 ADD PRIMARY KEY (`privmsgs_id`), ADD KEY `idx_from` (`privmsgs_from_userid`), ADD KEY `idx_to` (`privmsgs_to_userid`);

--
-- Indexes for table `nuke_bbprivmsgs_archive`
--
ALTER TABLE `nuke_bbprivmsgs_archive`
 ADD PRIMARY KEY (`privmsgs_id`), ADD KEY `privmsgs_from_userid` (`privmsgs_from_userid`), ADD KEY `privmsgs_to_userid` (`privmsgs_to_userid`);

--
-- Indexes for table `nuke_bbprivmsgs_text`
--
ALTER TABLE `nuke_bbprivmsgs_text`
 ADD PRIMARY KEY (`privmsgs_text_id`);

--
-- Indexes for table `nuke_bbranks`
--
ALTER TABLE `nuke_bbranks`
 ADD PRIMARY KEY (`rank_id`);

--
-- Indexes for table `nuke_bbscores`
--
ALTER TABLE `nuke_bbscores`
 ADD KEY `game_id` (`game_id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `nuke_bbsearch_pending`
--
ALTER TABLE `nuke_bbsearch_pending`
 ADD KEY `post_id` (`post_id`), ADD FULLTEXT KEY `mode` (`mode`,`post_subject`,`post_text`);

--
-- Indexes for table `nuke_bbsearch_results`
--
ALTER TABLE `nuke_bbsearch_results`
 ADD PRIMARY KEY (`search_id`), ADD KEY `session_id` (`session_id`), ADD KEY `search_time` (`search_time`);

--
-- Indexes for table `nuke_bbsearch_wordlist`
--
ALTER TABLE `nuke_bbsearch_wordlist`
 ADD PRIMARY KEY (`word_text`), ADD KEY `word_id` (`word_id`);

--
-- Indexes for table `nuke_bbsearch_wordmatch`
--
ALTER TABLE `nuke_bbsearch_wordmatch`
 ADD KEY `word_id` (`word_id`), ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `nuke_bbsessions`
--
ALTER TABLE `nuke_bbsessions`
 ADD PRIMARY KEY (`session_id`), ADD KEY `session_user_id` (`session_user_id`), ADD KEY `session_id_ip_user_id` (`session_id`,`session_ip`,`session_user_id`);

--
-- Indexes for table `nuke_bbsmilies`
--
ALTER TABLE `nuke_bbsmilies`
 ADD PRIMARY KEY (`smilies_id`);

--
-- Indexes for table `nuke_bbthemes`
--
ALTER TABLE `nuke_bbthemes`
 ADD PRIMARY KEY (`themes_id`);

--
-- Indexes for table `nuke_bbthemes_name`
--
ALTER TABLE `nuke_bbthemes_name`
 ADD PRIMARY KEY (`themes_id`);

--
-- Indexes for table `nuke_bbtopics`
--
ALTER TABLE `nuke_bbtopics`
 ADD PRIMARY KEY (`topic_id`), ADD KEY `forum_id` (`forum_id`), ADD KEY `topic_moved_id` (`topic_moved_id`), ADD KEY `topic_status` (`topic_status`), ADD KEY `topic_type` (`topic_type`), ADD KEY `topic_poster` (`topic_poster`), ADD KEY `url_slug` (`url_slug`);

--
-- Indexes for table `nuke_bbtopics_view`
--
ALTER TABLE `nuke_bbtopics_view`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `topic_id` (`topic_id`,`user_id`), ADD KEY `viewed` (`viewed`);

--
-- Indexes for table `nuke_bbtopics_watch`
--
ALTER TABLE `nuke_bbtopics_watch`
 ADD KEY `topic_id` (`topic_id`), ADD KEY `user_id` (`user_id`), ADD KEY `notify_status` (`notify_status`);

--
-- Indexes for table `nuke_bbuser_group`
--
ALTER TABLE `nuke_bbuser_group`
 ADD KEY `group_id` (`group_id`), ADD KEY `user_id` (`user_id`), ADD KEY `user_pending` (`user_pending`);

--
-- Indexes for table `nuke_bbvote_desc`
--
ALTER TABLE `nuke_bbvote_desc`
 ADD PRIMARY KEY (`vote_id`), ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `nuke_bbvote_results`
--
ALTER TABLE `nuke_bbvote_results`
 ADD KEY `vote_option_id` (`vote_option_id`), ADD KEY `vote_id` (`vote_id`);

--
-- Indexes for table `nuke_bbvote_voters`
--
ALTER TABLE `nuke_bbvote_voters`
 ADD KEY `vote_id` (`vote_id`), ADD KEY `vote_user_id` (`vote_user_id`), ADD KEY `vote_user_ip` (`vote_user_ip`);

--
-- Indexes for table `nuke_bbwords`
--
ALTER TABLE `nuke_bbwords`
 ADD PRIMARY KEY (`word_id`);

--
-- Indexes for table `nuke_blocks`
--
ALTER TABLE `nuke_blocks`
 ADD PRIMARY KEY (`bid`), ADD KEY `bid` (`bid`), ADD KEY `title` (`title`);

--
-- Indexes for table `nuke_comments`
--
ALTER TABLE `nuke_comments`
 ADD PRIMARY KEY (`tid`), ADD KEY `tid` (`tid`), ADD KEY `pid` (`pid`), ADD KEY `sid` (`sid`);

--
-- Indexes for table `nuke_contactbook`
--
ALTER TABLE `nuke_contactbook`
 ADD PRIMARY KEY (`contactid`), ADD KEY `uid` (`uid`), ADD KEY `contactid` (`contactid`);

--
-- Indexes for table `nuke_downloads_categories`
--
ALTER TABLE `nuke_downloads_categories`
 ADD PRIMARY KEY (`cid`), ADD KEY `cid` (`cid`), ADD KEY `title` (`title`);

--
-- Indexes for table `nuke_downloads_downloads`
--
ALTER TABLE `nuke_downloads_downloads`
 ADD PRIMARY KEY (`lid`), ADD KEY `lid` (`lid`), ADD KEY `cid` (`cid`), ADD KEY `sid` (`sid`), ADD KEY `title` (`title`);

--
-- Indexes for table `nuke_downloads_editorials`
--
ALTER TABLE `nuke_downloads_editorials`
 ADD PRIMARY KEY (`downloadid`), ADD KEY `downloadid` (`downloadid`);

--
-- Indexes for table `nuke_downloads_modrequest`
--
ALTER TABLE `nuke_downloads_modrequest`
 ADD PRIMARY KEY (`requestid`), ADD UNIQUE KEY `requestid` (`requestid`);

--
-- Indexes for table `nuke_downloads_newdownload`
--
ALTER TABLE `nuke_downloads_newdownload`
 ADD PRIMARY KEY (`lid`), ADD KEY `lid` (`lid`), ADD KEY `cid` (`cid`), ADD KEY `sid` (`sid`), ADD KEY `title` (`title`);

--
-- Indexes for table `nuke_downloads_votedata`
--
ALTER TABLE `nuke_downloads_votedata`
 ADD PRIMARY KEY (`ratingdbid`), ADD KEY `ratingdbid` (`ratingdbid`);

--
-- Indexes for table `nuke_encyclopedia`
--
ALTER TABLE `nuke_encyclopedia`
 ADD PRIMARY KEY (`eid`), ADD KEY `eid` (`eid`);

--
-- Indexes for table `nuke_encyclopedia_text`
--
ALTER TABLE `nuke_encyclopedia_text`
 ADD PRIMARY KEY (`tid`), ADD KEY `tid` (`tid`), ADD KEY `eid` (`eid`), ADD KEY `title` (`title`);

--
-- Indexes for table `nuke_ephem`
--
ALTER TABLE `nuke_ephem`
 ADD PRIMARY KEY (`eid`), ADD KEY `eid` (`eid`);

--
-- Indexes for table `nuke_externalsearch`
--
ALTER TABLE `nuke_externalsearch`
 ADD KEY `linkid` (`linkid`), ADD FULLTEXT KEY `linktitle` (`linktitle`,`linktext`,`linkurl`);

--
-- Indexes for table `nuke_faqAnswer`
--
ALTER TABLE `nuke_faqAnswer`
 ADD PRIMARY KEY (`id`), ADD KEY `id_cat` (`id_cat`);

--
-- Indexes for table `nuke_faqCategories`
--
ALTER TABLE `nuke_faqCategories`
 ADD PRIMARY KEY (`id_cat`);

--
-- Indexes for table `nuke_gallery`
--
ALTER TABLE `nuke_gallery`
 ADD UNIQUE KEY `album_name` (`album_name`);

--
-- Indexes for table `nuke_hallfame_queue`
--
ALTER TABLE `nuke_hallfame_queue`
 ADD PRIMARY KEY (`qid`), ADD FULLTEXT KEY `hofreason` (`hofreason`);

--
-- Indexes for table `nuke_headlines`
--
ALTER TABLE `nuke_headlines`
 ADD PRIMARY KEY (`hid`), ADD KEY `hid` (`hid`);

--
-- Indexes for table `nuke_journal`
--
ALTER TABLE `nuke_journal`
 ADD PRIMARY KEY (`jid`), ADD KEY `jid` (`jid`), ADD KEY `aid` (`aid`);

--
-- Indexes for table `nuke_journal_comments`
--
ALTER TABLE `nuke_journal_comments`
 ADD PRIMARY KEY (`cid`), ADD KEY `cid` (`cid`), ADD KEY `rid` (`rid`), ADD KEY `aid` (`aid`);

--
-- Indexes for table `nuke_journal_stats`
--
ALTER TABLE `nuke_journal_stats`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexes for table `nuke_links_categories`
--
ALTER TABLE `nuke_links_categories`
 ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `nuke_links_editorials`
--
ALTER TABLE `nuke_links_editorials`
 ADD PRIMARY KEY (`linkid`), ADD KEY `linkid` (`linkid`);

--
-- Indexes for table `nuke_links_links`
--
ALTER TABLE `nuke_links_links`
 ADD PRIMARY KEY (`lid`), ADD KEY `cid` (`cid`), ADD KEY `sid` (`sid`), ADD KEY `user_id` (`user_id`,`link_broken`,`link_approved`);

--
-- Indexes for table `nuke_links_modrequest`
--
ALTER TABLE `nuke_links_modrequest`
 ADD PRIMARY KEY (`requestid`), ADD UNIQUE KEY `requestid` (`requestid`);

--
-- Indexes for table `nuke_links_newlink`
--
ALTER TABLE `nuke_links_newlink`
 ADD PRIMARY KEY (`lid`), ADD KEY `lid` (`lid`), ADD KEY `cid` (`cid`), ADD KEY `sid` (`sid`);

--
-- Indexes for table `nuke_links_votedata`
--
ALTER TABLE `nuke_links_votedata`
 ADD PRIMARY KEY (`ratingdbid`), ADD KEY `ratingdbid` (`ratingdbid`);

--
-- Indexes for table `nuke_message`
--
ALTER TABLE `nuke_message`
 ADD PRIMARY KEY (`mid`), ADD UNIQUE KEY `mid` (`mid`);

--
-- Indexes for table `nuke_modules`
--
ALTER TABLE `nuke_modules`
 ADD PRIMARY KEY (`mid`), ADD KEY `mid` (`mid`), ADD KEY `title` (`title`), ADD KEY `custom_title` (`custom_title`);

--
-- Indexes for table `nuke_modules_categories`
--
ALTER TABLE `nuke_modules_categories`
 ADD PRIMARY KEY (`mcid`), ADD KEY `mcid` (`mcid`), ADD KEY `mcname` (`mcname`);

--
-- Indexes for table `nuke_msanalysis_admin`
--
ALTER TABLE `nuke_msanalysis_admin`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nuke_msanalysis_browsers`
--
ALTER TABLE `nuke_msanalysis_browsers`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `ibrowser` (`ibrowser`);

--
-- Indexes for table `nuke_msanalysis_countries`
--
ALTER TABLE `nuke_msanalysis_countries`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `domain` (`domain`), ADD KEY `description` (`description`);

--
-- Indexes for table `nuke_msanalysis_domains`
--
ALTER TABLE `nuke_msanalysis_domains`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `domain` (`domain`), ADD KEY `description` (`description`);

--
-- Indexes for table `nuke_msanalysis_modules`
--
ALTER TABLE `nuke_msanalysis_modules`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `modulename` (`modulename`);

--
-- Indexes for table `nuke_msanalysis_online`
--
ALTER TABLE `nuke_msanalysis_online`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `time` (`time`);

--
-- Indexes for table `nuke_msanalysis_os`
--
ALTER TABLE `nuke_msanalysis_os`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `ios` (`ios`);

--
-- Indexes for table `nuke_msanalysis_referrals`
--
ALTER TABLE `nuke_msanalysis_referrals`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `referral` (`referral`);

--
-- Indexes for table `nuke_msanalysis_scr`
--
ALTER TABLE `nuke_msanalysis_scr`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `scr_res` (`scr_res`);

--
-- Indexes for table `nuke_msanalysis_search`
--
ALTER TABLE `nuke_msanalysis_search`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `words` (`words`);

--
-- Indexes for table `nuke_msanalysis_users`
--
ALTER TABLE `nuke_msanalysis_users`
 ADD PRIMARY KEY (`uid`), ADD KEY `uid` (`uid`), ADD KEY `uname` (`uname`);

--
-- Indexes for table `nuke_newscomau`
--
ALTER TABLE `nuke_newscomau`
 ADD PRIMARY KEY (`sid`), ADD KEY `sid` (`sid`);

--
-- Indexes for table `nuke_nsndownloads_config`
--
ALTER TABLE `nuke_nsndownloads_config`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nuke_nucal_attendees`
--
ALTER TABLE `nuke_nucal_attendees`
 ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `nuke_nucal_categories`
--
ALTER TABLE `nuke_nucal_categories`
 ADD KEY `id` (`id`);

--
-- Indexes for table `nuke_nucal_events`
--
ALTER TABLE `nuke_nucal_events`
 ADD UNIQUE KEY `id_2` (`id`), ADD KEY `id` (`id`), ADD KEY `starttime` (`starttime`), ADD KEY `duration` (`duration`), ADD KEY `isactive` (`isactive`), ADD KEY `categoryid` (`categoryid`), ADD KEY `isapproved` (`isapproved`), ADD KEY `onetime_date` (`onetime_date`), ADD KEY `uid` (`uid`), ADD KEY `lat` (`lat`), ADD KEY `lon` (`lon`), ADD KEY `flagged` (`flagged`), ADD KEY `organisation_id` (`organisation_id`);

--
-- Indexes for table `nuke_pages`
--
ALTER TABLE `nuke_pages`
 ADD PRIMARY KEY (`pid`), ADD KEY `pid` (`pid`), ADD KEY `cid` (`cid`);

--
-- Indexes for table `nuke_pages_categories`
--
ALTER TABLE `nuke_pages_categories`
 ADD PRIMARY KEY (`cid`), ADD KEY `cid` (`cid`);

--
-- Indexes for table `nuke_pollcomments`
--
ALTER TABLE `nuke_pollcomments`
 ADD PRIMARY KEY (`tid`), ADD KEY `tid` (`tid`), ADD KEY `pid` (`pid`), ADD KEY `pollID` (`pollID`);

--
-- Indexes for table `nuke_poll_check`
--
ALTER TABLE `nuke_poll_check`
 ADD KEY `ip` (`ip`), ADD KEY `pollID` (`pollID`);

--
-- Indexes for table `nuke_poll_data`
--
ALTER TABLE `nuke_poll_data`
 ADD KEY `pollID` (`pollID`);

--
-- Indexes for table `nuke_poll_desc`
--
ALTER TABLE `nuke_poll_desc`
 ADD PRIMARY KEY (`pollID`), ADD KEY `artid` (`artid`);

--
-- Indexes for table `nuke_popsettings`
--
ALTER TABLE `nuke_popsettings`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`), ADD KEY `uid` (`uid`);

--
-- Indexes for table `nuke_priv_msgs`
--
ALTER TABLE `nuke_priv_msgs`
 ADD PRIMARY KEY (`msg_id`), ADD KEY `msg_id` (`msg_id`), ADD KEY `to_userid` (`to_userid`), ADD KEY `from_userid` (`from_userid`);

--
-- Indexes for table `nuke_public_messages`
--
ALTER TABLE `nuke_public_messages`
 ADD PRIMARY KEY (`mid`), ADD KEY `mid` (`mid`);

--
-- Indexes for table `nuke_queue`
--
ALTER TABLE `nuke_queue`
 ADD PRIMARY KEY (`qid`), ADD KEY `uid` (`uid`), ADD KEY `uname` (`uname`);

--
-- Indexes for table `nuke_quizz_admin`
--
ALTER TABLE `nuke_quizz_admin`
 ADD PRIMARY KEY (`quizzID`), ADD KEY `quizzID` (`quizzID`);

--
-- Indexes for table `nuke_quizz_categories`
--
ALTER TABLE `nuke_quizz_categories`
 ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `nuke_quizz_check`
--
ALTER TABLE `nuke_quizz_check`
 ADD KEY `qid` (`qid`);

--
-- Indexes for table `nuke_quizz_desc`
--
ALTER TABLE `nuke_quizz_desc`
 ADD PRIMARY KEY (`pollID`);

--
-- Indexes for table `nuke_quizz_descontrib`
--
ALTER TABLE `nuke_quizz_descontrib`
 ADD PRIMARY KEY (`pollID`);

--
-- Indexes for table `nuke_quiz_admin`
--
ALTER TABLE `nuke_quiz_admin`
 ADD PRIMARY KEY (`quizID`), ADD KEY `quizzID` (`quizID`);

--
-- Indexes for table `nuke_quiz_categories`
--
ALTER TABLE `nuke_quiz_categories`
 ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `nuke_quiz_check`
--
ALTER TABLE `nuke_quiz_check`
 ADD KEY `qid` (`qid`);

--
-- Indexes for table `nuke_quiz_desc`
--
ALTER TABLE `nuke_quiz_desc`
 ADD PRIMARY KEY (`pollID`);

--
-- Indexes for table `nuke_quiz_index`
--
ALTER TABLE `nuke_quiz_index`
 ADD KEY `quizid` (`quizid`), ADD FULLTEXT KEY `quiztitle` (`quiztitle`,`quizdesc`);

--
-- Indexes for table `nuke_quotes`
--
ALTER TABLE `nuke_quotes`
 ADD PRIMARY KEY (`qid`), ADD KEY `qid` (`qid`);

--
-- Indexes for table `nuke_referer`
--
ALTER TABLE `nuke_referer`
 ADD PRIMARY KEY (`rid`), ADD KEY `rid` (`rid`);

--
-- Indexes for table `nuke_related`
--
ALTER TABLE `nuke_related`
 ADD PRIMARY KEY (`rid`), ADD KEY `rid` (`rid`), ADD KEY `tid` (`tid`);

--
-- Indexes for table `nuke_reviews`
--
ALTER TABLE `nuke_reviews`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexes for table `nuke_reviews_add`
--
ALTER TABLE `nuke_reviews_add`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexes for table `nuke_reviews_comments`
--
ALTER TABLE `nuke_reviews_comments`
 ADD PRIMARY KEY (`cid`), ADD KEY `cid` (`cid`), ADD KEY `rid` (`rid`), ADD KEY `userid` (`userid`);

--
-- Indexes for table `nuke_seccont`
--
ALTER TABLE `nuke_seccont`
 ADD PRIMARY KEY (`artid`), ADD KEY `artid` (`artid`), ADD KEY `secid` (`secid`);

--
-- Indexes for table `nuke_sections`
--
ALTER TABLE `nuke_sections`
 ADD PRIMARY KEY (`secid`), ADD KEY `secid` (`secid`);

--
-- Indexes for table `nuke_session`
--
ALTER TABLE `nuke_session`
 ADD KEY `time` (`time`), ADD KEY `guest` (`guest`);

--
-- Indexes for table `nuke_sommaire`
--
ALTER TABLE `nuke_sommaire`
 ADD PRIMARY KEY (`groupmenu`);

--
-- Indexes for table `nuke_sommaire_categories`
--
ALTER TABLE `nuke_sommaire_categories`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nuke_spelling_words`
--
ALTER TABLE `nuke_spelling_words`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `word` (`word`), ADD KEY `sound` (`sound`);

--
-- Indexes for table `nuke_staff`
--
ALTER TABLE `nuke_staff`
 ADD PRIMARY KEY (`sid`), ADD UNIQUE KEY `sid` (`sid`);

--
-- Indexes for table `nuke_staff_cat`
--
ALTER TABLE `nuke_staff_cat`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `nuke_stories`
--
ALTER TABLE `nuke_stories`
 ADD PRIMARY KEY (`sid`), ADD KEY `catid` (`catid`), ADD KEY `counter` (`counter`), ADD KEY `topic` (`topic`), ADD KEY `approved` (`approved`), ADD KEY `aid` (`aid`), ADD KEY `ForumThreadID` (`ForumThreadID`), ADD KEY `user_id` (`user_id`), ADD KEY `staff_id` (`staff_id`), ADD KEY `time` (`time`), ADD KEY `weeklycounter` (`weeklycounter`), ADD KEY `informant` (`informant`);

--
-- Indexes for table `nuke_stories_cat`
--
ALTER TABLE `nuke_stories_cat`
 ADD PRIMARY KEY (`catid`), ADD KEY `catid` (`catid`);

--
-- Indexes for table `nuke_stories_view`
--
ALTER TABLE `nuke_stories_view`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `topic_id` (`story_id`,`user_id`), ADD KEY `viewed` (`viewed`);

--
-- Indexes for table `nuke_topics`
--
ALTER TABLE `nuke_topics`
 ADD PRIMARY KEY (`topicid`), ADD KEY `topicname` (`topicname`);

--
-- Indexes for table `nuke_upermissions`
--
ALTER TABLE `nuke_upermissions`
 ADD KEY `pid` (`pid`), ADD FULLTEXT KEY `pmodule` (`pmodule`);

--
-- Indexes for table `nuke_users`
--
ALTER TABLE `nuke_users`
 ADD PRIMARY KEY (`user_id`), ADD KEY `user_session_time` (`user_session_time`), ADD KEY `username` (`username`), ADD KEY `user_enablerte` (`user_enablerte`), ADD KEY `api_secret` (`api_secret`(255)), ADD KEY `user_active` (`user_active`), ADD KEY `user_lastvisit` (`user_lastvisit`), ADD KEY `oauth_consumer_id` (`oauth_consumer_id`), ADD KEY `reported_to_sfs` (`reported_to_sfs`), ADD KEY `user_regdate_nice` (`user_regdate_nice`), ADD KEY `provider` (`provider`);

--
-- Indexes for table `nuke_users_autologin`
--
ALTER TABLE `nuke_users_autologin`
 ADD PRIMARY KEY (`autologin_id`), ADD KEY `autologin_last` (`autologin_last`), ADD KEY `user_id` (`user_id`), ADD KEY `autologin_expire` (`autologin_expire`), ADD KEY `autologin_time` (`autologin_time`), ADD KEY `autologin_token` (`autologin_token`);

--
-- Indexes for table `nuke_users_flags`
--
ALTER TABLE `nuke_users_flags`
 ADD PRIMARY KEY (`user_id`), ADD KEY `newsletter_daily` (`newsletter_daily`,`newsletter_weekly`,`newsletter_monthly`,`notify_photocomp`,`notify_pm`,`notify_forums`), ADD KEY `newsletter_weekly_last` (`newsletter_weekly_last`);

--
-- Indexes for table `nuke_users_groups`
--
ALTER TABLE `nuke_users_groups`
 ADD PRIMARY KEY (`gid`);

--
-- Indexes for table `nuke_users_hash`
--
ALTER TABLE `nuke_users_hash`
 ADD KEY `user_id` (`user_id`,`hash`(255));

--
-- Indexes for table `nuke_users_notes`
--
ALTER TABLE `nuke_users_notes`
 ADD PRIMARY KEY (`nid`);

--
-- Indexes for table `nuke_users_temp`
--
ALTER TABLE `nuke_users_temp`
 ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `oauth_consumer`
--
ALTER TABLE `oauth_consumer`
 ADD PRIMARY KEY (`id`), ADD KEY `consumer_key` (`consumer_key`), ADD KEY `consumer_secret` (`consumer_secret`), ADD KEY `active` (`active`);

--
-- Indexes for table `oauth_consumer_nonce`
--
ALTER TABLE `oauth_consumer_nonce`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_token`
--
ALTER TABLE `oauth_token`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_token_type`
--
ALTER TABLE `oauth_token_type`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `operators`
--
ALTER TABLE `operators`
 ADD PRIMARY KEY (`operator_id`), ADD KEY `organisation_id` (`organisation_id`);

--
-- Indexes for table `organisation`
--
ALTER TABLE `organisation`
 ADD PRIMARY KEY (`organisation_id`), ADD KEY `organisation_owner` (`organisation_owner`);

--
-- Indexes for table `organisation_member`
--
ALTER TABLE `organisation_member`
 ADD KEY `organisation_id` (`organisation_id`,`user_id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `organisation_roles`
--
ALTER TABLE `organisation_roles`
 ADD PRIMARY KEY (`role_id`), ADD KEY `organisation_id` (`organisation_id`);

--
-- Indexes for table `phpbb_reports_actions`
--
ALTER TABLE `phpbb_reports_actions`
 ADD PRIMARY KEY (`action_id`), ADD KEY `report_id` (`report_id`), ADD KEY `action_user_id` (`action_user_id`), ADD KEY `action_status` (`action_status`);

--
-- Indexes for table `phpbb_reports_config`
--
ALTER TABLE `phpbb_reports_config`
 ADD PRIMARY KEY (`config_name`);

--
-- Indexes for table `phpbb_reports_data`
--
ALTER TABLE `phpbb_reports_data`
 ADD PRIMARY KEY (`data_id`), ADD KEY `data_code` (`data_code`);

--
-- Indexes for table `phpbb_reports_posts`
--
ALTER TABLE `phpbb_reports_posts`
 ADD PRIMARY KEY (`report_id`), ADD KEY `report_user_id` (`report_user_id`), ADD KEY `report_status` (`report_status`), ADD KEY `post_id` (`post_id`), ADD KEY `poster_id` (`poster_id`);

--
-- Indexes for table `phpbb_warnings`
--
ALTER TABLE `phpbb_warnings`
 ADD PRIMARY KEY (`warn_id`), ADD KEY `warn_id` (`warn_id`), ADD KEY `user_id` (`user_id`), ADD KEY `warned_by` (`warned_by`), ADD KEY `warn_date` (`warn_date`), ADD KEY `old_warning_level` (`old_warning_level`), ADD KEY `new_warning_level` (`new_warning_level`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
 ADD PRIMARY KEY (`poll_id`), ADD KEY `poll_votes` (`poll_votes`);

--
-- Indexes for table `popover_viewed`
--
ALTER TABLE `popover_viewed`
 ADD KEY `popover_id` (`popover_id`,`user_id`);

--
-- Indexes for table `privmsgs_hidelist`
--
ALTER TABLE `privmsgs_hidelist`
 ADD KEY `privmsgs_id` (`privmsgs_id`,`user_id`);

--
-- Indexes for table `railcams`
--
ALTER TABLE `railcams`
 ADD PRIMARY KEY (`id`), ADD KEY `permalink` (`permalink`), ADD KEY `lat` (`lat`), ADD KEY `lon` (`lon`), ADD KEY `route_id` (`route_id`), ADD KEY `nsid` (`nsid`), ADD KEY `type_id` (`type_id`), ADD KEY `provider` (`provider`);

--
-- Indexes for table `railcams_type`
--
ALTER TABLE `railcams_type`
 ADD PRIMARY KEY (`id`), ADD KEY `name` (`name`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `rating_loco`
--
ALTER TABLE `rating_loco`
 ADD PRIMARY KEY (`rating_id`), ADD KEY `rating_id` (`rating_id`,`loco_id`,`user_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
 ADD PRIMARY KEY (`id`), ADD KEY `module` (`module`,`object`,`object_id`,`user_id`,`reminder`), ADD KEY `sent` (`sent`), ADD KEY `dispatched` (`dispatched`);

--
-- Indexes for table `route`
--
ALTER TABLE `route`
 ADD PRIMARY KEY (`id`), ADD KEY `slug` (`slug`), ADD KEY `active` (`active`), ADD KEY `gtfs_route_id` (`gtfs_route_id`);

--
-- Indexes for table `route_markers`
--
ALTER TABLE `route_markers`
 ADD PRIMARY KEY (`id`), ADD KEY `route_id` (`route_id`), ADD KEY `path_id` (`path_id`), ADD KEY `weight` (`weight`), ADD KEY `lat` (`lat`(255)), ADD KEY `lon` (`lon`(255));

--
-- Indexes for table `route_markers_tmp`
--
ALTER TABLE `route_markers_tmp`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sighting`
--
ALTER TABLE `sighting`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sighting_locos`
--
ALTER TABLE `sighting_locos`
 ADD KEY `loco_id` (`loco_id`), ADD KEY `sighting_id` (`sighting_id`);

--
-- Indexes for table `source`
--
ALTER TABLE `source`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sph_counter`
--
ALTER TABLE `sph_counter`
 ADD PRIMARY KEY (`counter_id`);

--
-- Indexes for table `tag`
--
ALTER TABLE `tag`
 ADD PRIMARY KEY (`tag_id`), ADD UNIQUE KEY `tag` (`tag`);

--
-- Indexes for table `tag_link`
--
ALTER TABLE `tag_link`
 ADD KEY `tag_link_id` (`tag_link_id`), ADD KEY `tag_id` (`tag_id`), ADD KEY `story_id` (`story_id`), ADD KEY `topic_id` (`topic_id`), ADD KEY `post_id` (`post_id`), ADD KEY `photo_id` (`photo_id`);

--
-- Indexes for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
 ADD PRIMARY KEY (`id`), ADD KEY `point_id` (`point_id`), ADD KEY `train_id` (`train_id`), ADD KEY `day` (`day`,`time`,`going`);

--
-- Indexes for table `timetable_points`
--
ALTER TABLE `timetable_points`
 ADD PRIMARY KEY (`id`), ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `timetable_regions`
--
ALTER TABLE `timetable_regions`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable_trains`
--
ALTER TABLE `timetable_trains`
 ADD PRIMARY KEY (`id`), ADD KEY `operator_id` (`operator_id`), ADD KEY `provider` (`provider`), ADD KEY `commodity` (`commodity`);

--
-- Indexes for table `viewed_threads`
--
ALTER TABLE `viewed_threads`
 ADD PRIMARY KEY (`id`), ADD KEY `time` (`time`), ADD KEY `topic_id_index` (`topic_id`,`user_id`);

--
-- Indexes for table `waynet`
--
ALTER TABLE `waynet`
 ADD PRIMARY KEY (`id`), ADD KEY `trainnum` (`trainnum`);

--
-- Indexes for table `wheel_arrangements`
--
ALTER TABLE `wheel_arrangements`
 ADD PRIMARY KEY (`id`), ADD KEY `slug` (`slug`);

--
-- Indexes for table `woecache`
--
ALTER TABLE `woecache`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `lat` (`lat`,`lon`), ADD KEY `stored` (`stored`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset`
--
ALTER TABLE `asset`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=219;
--
-- AUTO_INCREMENT for table `asset_bak`
--
ALTER TABLE `asset_bak`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=165;
--
-- AUTO_INCREMENT for table `asset_link`
--
ALTER TABLE `asset_link`
MODIFY `asset_link_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=199;
--
-- AUTO_INCREMENT for table `asset_type`
--
ALTER TABLE `asset_type`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT for table `bancontrol`
--
ALTER TABLE `bancontrol`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12208;
--
-- AUTO_INCREMENT for table `ban_domains`
--
ALTER TABLE `ban_domains`
MODIFY `domain_id` int(12) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `cache_woe`
--
ALTER TABLE `cache_woe`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=120;
--
-- AUTO_INCREMENT for table `chronicle_item`
--
ALTER TABLE `chronicle_item`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3542;
--
-- AUTO_INCREMENT for table `chronicle_link`
--
ALTER TABLE `chronicle_link`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3542;
--
-- AUTO_INCREMENT for table `chronicle_type`
--
ALTER TABLE `chronicle_type`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `config`
--
ALTER TABLE `config`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `download_categories`
--
ALTER TABLE `download_categories`
MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=30;
--
-- AUTO_INCREMENT for table `download_hits`
--
ALTER TABLE `download_hits`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `download_items`
--
ALTER TABLE `download_items`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=874;
--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=75;
--
-- AUTO_INCREMENT for table `event_categories`
--
ALTER TABLE `event_categories`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT for table `event_dates`
--
ALTER TABLE `event_dates`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=189;
--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=163;
--
-- AUTO_INCREMENT for table `feedback_area`
--
ALTER TABLE `feedback_area`
MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `feedback_status`
--
ALTER TABLE `feedback_status`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `flickr_rating`
--
ALTER TABLE `flickr_rating`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=189;
--
-- AUTO_INCREMENT for table `fwlink`
--
ALTER TABLE `fwlink`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=25809;
--
-- AUTO_INCREMENT for table `gallery_mig_album`
--
ALTER TABLE `gallery_mig_album`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2833;
--
-- AUTO_INCREMENT for table `gallery_mig_image`
--
ALTER TABLE `gallery_mig_image`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=44521;
--
-- AUTO_INCREMENT for table `gallery_mig_image_sizes`
--
ALTER TABLE `gallery_mig_image_sizes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=189020;
--
-- AUTO_INCREMENT for table `geoplace`
--
ALTER TABLE `geoplace`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=501373;
--
-- AUTO_INCREMENT for table `geoplace_forecast`
--
ALTER TABLE `geoplace_forecast`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1808;
--
-- AUTO_INCREMENT for table `glossary`
--
ALTER TABLE `glossary`
MODIFY `id` int(12) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=53;
--
-- AUTO_INCREMENT for table `idea_categories`
--
ALTER TABLE `idea_categories`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `idea_ideas`
--
ALTER TABLE `idea_ideas`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=72;
--
-- AUTO_INCREMENT for table `idea_votes`
--
ALTER TABLE `idea_votes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=291;
--
-- AUTO_INCREMENT for table `image`
--
ALTER TABLE `image`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=73825;
--
-- AUTO_INCREMENT for table `image_camera`
--
ALTER TABLE `image_camera`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=871;
--
-- AUTO_INCREMENT for table `image_collection`
--
ALTER TABLE `image_collection`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT for table `image_competition`
--
ALTER TABLE `image_competition`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `image_competition_submissions`
--
ALTER TABLE `image_competition_submissions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=78;
--
-- AUTO_INCREMENT for table `image_competition_votes`
--
ALTER TABLE `image_competition_votes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=217;
--
-- AUTO_INCREMENT for table `image_exposure`
--
ALTER TABLE `image_exposure`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=844;
--
-- AUTO_INCREMENT for table `image_exposure_program`
--
ALTER TABLE `image_exposure_program`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=31;
--
-- AUTO_INCREMENT for table `image_favourites`
--
ALTER TABLE `image_favourites`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=65;
--
-- AUTO_INCREMENT for table `image_flags_skip`
--
ALTER TABLE `image_flags_skip`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=50;
--
-- AUTO_INCREMENT for table `image_lens`
--
ALTER TABLE `image_lens`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=147;
--
-- AUTO_INCREMENT for table `image_lens_sn`
--
ALTER TABLE `image_lens_sn`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=142;
--
-- AUTO_INCREMENT for table `image_link`
--
ALTER TABLE `image_link`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=67104;
--
-- AUTO_INCREMENT for table `image_position`
--
ALTER TABLE `image_position`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=443;
--
-- AUTO_INCREMENT for table `image_software`
--
ALTER TABLE `image_software`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=772;
--
-- AUTO_INCREMENT for table `image_weekly`
--
ALTER TABLE `image_weekly`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `image_whitebalance`
--
ALTER TABLE `image_whitebalance`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=41;
--
-- AUTO_INCREMENT for table `jn_applications`
--
ALTER TABLE `jn_applications`
MODIFY `jn_application_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for this job application';
--
-- AUTO_INCREMENT for table `jn_classifications`
--
ALTER TABLE `jn_classifications`
MODIFY `jn_classification_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of the job classification',AUTO_INCREMENT=66;
--
-- AUTO_INCREMENT for table `jn_jobs`
--
ALTER TABLE `jn_jobs`
MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique job ID',AUTO_INCREMENT=203;
--
-- AUTO_INCREMENT for table `jn_locations`
--
ALTER TABLE `jn_locations`
MODIFY `jn_location_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique job location ID',AUTO_INCREMENT=57;
--
-- AUTO_INCREMENT for table `loadstats`
--
ALTER TABLE `loadstats`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=438;
--
-- AUTO_INCREMENT for table `location_corrections`
--
ALTER TABLE `location_corrections`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `location_date`
--
ALTER TABLE `location_date`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `location_datetypes`
--
ALTER TABLE `location_datetypes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `loco_class`
--
ALTER TABLE `loco_class`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=385;
--
-- AUTO_INCREMENT for table `loco_date_type`
--
ALTER TABLE `loco_date_type`
MODIFY `loco_date_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `loco_gauge`
--
ALTER TABLE `loco_gauge`
MODIFY `gauge_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `loco_groups`
--
ALTER TABLE `loco_groups`
MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=58;
--
-- AUTO_INCREMENT for table `loco_groups_members`
--
ALTER TABLE `loco_groups_members`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `loco_link`
--
ALTER TABLE `loco_link`
MODIFY `link_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=867;
--
-- AUTO_INCREMENT for table `loco_link_type`
--
ALTER TABLE `loco_link_type`
MODIFY `link_type_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `loco_livery`
--
ALTER TABLE `loco_livery`
MODIFY `livery_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=169;
--
-- AUTO_INCREMENT for table `loco_manufacturer`
--
ALTER TABLE `loco_manufacturer`
MODIFY `manufacturer_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=102;
--
-- AUTO_INCREMENT for table `loco_notes`
--
ALTER TABLE `loco_notes`
MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=395;
--
-- AUTO_INCREMENT for table `loco_org_link`
--
ALTER TABLE `loco_org_link`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26074;
--
-- AUTO_INCREMENT for table `loco_org_link_type`
--
ALTER TABLE `loco_org_link_type`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `loco_status`
--
ALTER TABLE `loco_status`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `loco_type`
--
ALTER TABLE `loco_type`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `loco_unit`
--
ALTER TABLE `loco_unit`
MODIFY `loco_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7497;
--
-- AUTO_INCREMENT for table `loco_unit_corrections`
--
ALTER TABLE `loco_unit_corrections`
MODIFY `correction_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=102;
--
-- AUTO_INCREMENT for table `loco_unit_date`
--
ALTER TABLE `loco_unit_date`
MODIFY `date_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4575;
--
-- AUTO_INCREMENT for table `loco_unit_livery`
--
ALTER TABLE `loco_unit_livery`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7054;
--
-- AUTO_INCREMENT for table `loco_unit_source`
--
ALTER TABLE `loco_unit_source`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_api`
--
ALTER TABLE `log_api`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_downloads`
--
ALTER TABLE `log_downloads`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=46545;
--
-- AUTO_INCREMENT for table `log_errors`
--
ALTER TABLE `log_errors`
MODIFY `error_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=374980;
--
-- AUTO_INCREMENT for table `log_general`
--
ALTER TABLE `log_general`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=121064;
--
-- AUTO_INCREMENT for table `log_herrings`
--
ALTER TABLE `log_herrings`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3645;
--
-- AUTO_INCREMENT for table `log_locos`
--
ALTER TABLE `log_locos`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1268;
--
-- AUTO_INCREMENT for table `log_logins`
--
ALTER TABLE `log_logins`
MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1679492;
--
-- AUTO_INCREMENT for table `log_staff`
--
ALTER TABLE `log_staff`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7868;
--
-- AUTO_INCREMENT for table `log_useractivity`
--
ALTER TABLE `log_useractivity`
MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1095865;
--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
MODIFY `message_id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT for table `messages_viewed`
--
ALTER TABLE `messages_viewed`
MODIFY `row_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2047;
--
-- AUTO_INCREMENT for table `newsletter`
--
ALTER TABLE `newsletter`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=24;
--
-- AUTO_INCREMENT for table `newsletter_templates`
--
ALTER TABLE `newsletter_templates`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `news_feed`
--
ALTER TABLE `news_feed`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6720;
--
-- AUTO_INCREMENT for table `notifications_recipients`
--
ALTER TABLE `notifications_recipients`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20714;
--
-- AUTO_INCREMENT for table `notification_prefs`
--
ALTER TABLE `notification_prefs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `notification_rules`
--
ALTER TABLE `notification_rules`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `notification_sent`
--
ALTER TABLE `notification_sent`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_alliance`
--
ALTER TABLE `nuke_alliance`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `nuke_bbarcade_categories`
--
ALTER TABLE `nuke_bbarcade_categories`
MODIFY `arcade_catid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_bbbanlist`
--
ALTER TABLE `nuke_bbbanlist`
MODIFY `ban_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=33;
--
-- AUTO_INCREMENT for table `nuke_bbcategories`
--
ALTER TABLE `nuke_bbcategories`
MODIFY `cat_id` int(8) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `nuke_bbdisallow`
--
ALTER TABLE `nuke_bbdisallow`
MODIFY `disallow_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT for table `nuke_bbforums`
--
ALTER TABLE `nuke_bbforums`
MODIFY `forum_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=73;
--
-- AUTO_INCREMENT for table `nuke_bbforum_prune`
--
ALTER TABLE `nuke_bbforum_prune`
MODIFY `prune_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `nuke_bbgames`
--
ALTER TABLE `nuke_bbgames`
MODIFY `game_id` mediumint(8) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT for table `nuke_bbgroups`
--
ALTER TABLE `nuke_bbgroups`
MODIFY `group_id` mediumint(8) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1241;
--
-- AUTO_INCREMENT for table `nuke_bbposts`
--
ALTER TABLE `nuke_bbposts`
MODIFY `post_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1992454;
--
-- AUTO_INCREMENT for table `nuke_bbposts_edit`
--
ALTER TABLE `nuke_bbposts_edit`
MODIFY `edit_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=64860;
--
-- AUTO_INCREMENT for table `nuke_bbposts_reputation`
--
ALTER TABLE `nuke_bbposts_reputation`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11711;
--
-- AUTO_INCREMENT for table `nuke_bbprivmsgs`
--
ALTER TABLE `nuke_bbprivmsgs`
MODIFY `privmsgs_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=332217;
--
-- AUTO_INCREMENT for table `nuke_bbprivmsgs_archive`
--
ALTER TABLE `nuke_bbprivmsgs_archive`
MODIFY `privmsgs_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=314647;
--
-- AUTO_INCREMENT for table `nuke_bbranks`
--
ALTER TABLE `nuke_bbranks`
MODIFY `rank_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=75;
--
-- AUTO_INCREMENT for table `nuke_bbsearch_wordlist`
--
ALTER TABLE `nuke_bbsearch_wordlist`
MODIFY `word_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_bbsmilies`
--
ALTER TABLE `nuke_bbsmilies`
MODIFY `smilies_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=76;
--
-- AUTO_INCREMENT for table `nuke_bbthemes`
--
ALTER TABLE `nuke_bbthemes`
MODIFY `themes_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `nuke_bbtopics`
--
ALTER TABLE `nuke_bbtopics`
MODIFY `topic_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11382716;
--
-- AUTO_INCREMENT for table `nuke_bbtopics_view`
--
ALTER TABLE `nuke_bbtopics_view`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=311313;
--
-- AUTO_INCREMENT for table `nuke_bbvote_desc`
--
ALTER TABLE `nuke_bbvote_desc`
MODIFY `vote_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1452;
--
-- AUTO_INCREMENT for table `nuke_bbwords`
--
ALTER TABLE `nuke_bbwords`
MODIFY `word_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=144;
--
-- AUTO_INCREMENT for table `nuke_blocks`
--
ALTER TABLE `nuke_blocks`
MODIFY `bid` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=57;
--
-- AUTO_INCREMENT for table `nuke_comments`
--
ALTER TABLE `nuke_comments`
MODIFY `tid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13973;
--
-- AUTO_INCREMENT for table `nuke_contactbook`
--
ALTER TABLE `nuke_contactbook`
MODIFY `contactid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_downloads_categories`
--
ALTER TABLE `nuke_downloads_categories`
MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `nuke_downloads_downloads`
--
ALTER TABLE `nuke_downloads_downloads`
MODIFY `lid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=364;
--
-- AUTO_INCREMENT for table `nuke_downloads_modrequest`
--
ALTER TABLE `nuke_downloads_modrequest`
MODIFY `requestid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=63;
--
-- AUTO_INCREMENT for table `nuke_downloads_newdownload`
--
ALTER TABLE `nuke_downloads_newdownload`
MODIFY `lid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `nuke_downloads_votedata`
--
ALTER TABLE `nuke_downloads_votedata`
MODIFY `ratingdbid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=149;
--
-- AUTO_INCREMENT for table `nuke_encyclopedia`
--
ALTER TABLE `nuke_encyclopedia`
MODIFY `eid` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_encyclopedia_text`
--
ALTER TABLE `nuke_encyclopedia_text`
MODIFY `tid` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_ephem`
--
ALTER TABLE `nuke_ephem`
MODIFY `eid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_externalsearch`
--
ALTER TABLE `nuke_externalsearch`
MODIFY `linkid` int(13) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_faqAnswer`
--
ALTER TABLE `nuke_faqAnswer`
MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=100;
--
-- AUTO_INCREMENT for table `nuke_faqCategories`
--
ALTER TABLE `nuke_faqCategories`
MODIFY `id_cat` tinyint(3) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `nuke_hallfame_queue`
--
ALTER TABLE `nuke_hallfame_queue`
MODIFY `qid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_headlines`
--
ALTER TABLE `nuke_headlines`
MODIFY `hid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `nuke_journal`
--
ALTER TABLE `nuke_journal`
MODIFY `jid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=117;
--
-- AUTO_INCREMENT for table `nuke_journal_comments`
--
ALTER TABLE `nuke_journal_comments`
MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `nuke_journal_stats`
--
ALTER TABLE `nuke_journal_stats`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=67;
--
-- AUTO_INCREMENT for table `nuke_links_categories`
--
ALTER TABLE `nuke_links_categories`
MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=46;
--
-- AUTO_INCREMENT for table `nuke_links_links`
--
ALTER TABLE `nuke_links_links`
MODIFY `lid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=308;
--
-- AUTO_INCREMENT for table `nuke_links_modrequest`
--
ALTER TABLE `nuke_links_modrequest`
MODIFY `requestid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `nuke_links_newlink`
--
ALTER TABLE `nuke_links_newlink`
MODIFY `lid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `nuke_links_votedata`
--
ALTER TABLE `nuke_links_votedata`
MODIFY `ratingdbid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=271;
--
-- AUTO_INCREMENT for table `nuke_message`
--
ALTER TABLE `nuke_message`
MODIFY `mid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=64;
--
-- AUTO_INCREMENT for table `nuke_modules`
--
ALTER TABLE `nuke_modules`
MODIFY `mid` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=123;
--
-- AUTO_INCREMENT for table `nuke_modules_categories`
--
ALTER TABLE `nuke_modules_categories`
MODIFY `mcid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_admin`
--
ALTER TABLE `nuke_msanalysis_admin`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_browsers`
--
ALTER TABLE `nuke_msanalysis_browsers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=310;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_countries`
--
ALTER TABLE `nuke_msanalysis_countries`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=197;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_domains`
--
ALTER TABLE `nuke_msanalysis_domains`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=266;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_modules`
--
ALTER TABLE `nuke_msanalysis_modules`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=695;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_online`
--
ALTER TABLE `nuke_msanalysis_online`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18643522;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_os`
--
ALTER TABLE `nuke_msanalysis_os`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_referrals`
--
ALTER TABLE `nuke_msanalysis_referrals`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5007;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_scr`
--
ALTER TABLE `nuke_msanalysis_scr`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=418;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_search`
--
ALTER TABLE `nuke_msanalysis_search`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_msanalysis_users`
--
ALTER TABLE `nuke_msanalysis_users`
MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7932;
--
-- AUTO_INCREMENT for table `nuke_newscomau`
--
ALTER TABLE `nuke_newscomau`
MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=59;
--
-- AUTO_INCREMENT for table `nuke_nsndownloads_config`
--
ALTER TABLE `nuke_nsndownloads_config`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_nucal_categories`
--
ALTER TABLE `nuke_nucal_categories`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `nuke_nucal_events`
--
ALTER TABLE `nuke_nucal_events`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1342;
--
-- AUTO_INCREMENT for table `nuke_pages`
--
ALTER TABLE `nuke_pages`
MODIFY `pid` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `nuke_pages_categories`
--
ALTER TABLE `nuke_pages_categories`
MODIFY `cid` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `nuke_pollcomments`
--
ALTER TABLE `nuke_pollcomments`
MODIFY `tid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=639;
--
-- AUTO_INCREMENT for table `nuke_poll_desc`
--
ALTER TABLE `nuke_poll_desc`
MODIFY `pollID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=98;
--
-- AUTO_INCREMENT for table `nuke_popsettings`
--
ALTER TABLE `nuke_popsettings`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=55;
--
-- AUTO_INCREMENT for table `nuke_priv_msgs`
--
ALTER TABLE `nuke_priv_msgs`
MODIFY `msg_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_public_messages`
--
ALTER TABLE `nuke_public_messages`
MODIFY `mid` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `nuke_queue`
--
ALTER TABLE `nuke_queue`
MODIFY `qid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8422;
--
-- AUTO_INCREMENT for table `nuke_quizz_admin`
--
ALTER TABLE `nuke_quizz_admin`
MODIFY `quizzID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `nuke_quizz_categories`
--
ALTER TABLE `nuke_quizz_categories`
MODIFY `cid` int(9) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `nuke_quizz_desc`
--
ALTER TABLE `nuke_quizz_desc`
MODIFY `pollID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT for table `nuke_quizz_descontrib`
--
ALTER TABLE `nuke_quizz_descontrib`
MODIFY `pollID` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_quiz_admin`
--
ALTER TABLE `nuke_quiz_admin`
MODIFY `quizID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_quiz_categories`
--
ALTER TABLE `nuke_quiz_categories`
MODIFY `cid` int(9) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_quiz_desc`
--
ALTER TABLE `nuke_quiz_desc`
MODIFY `pollID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_quiz_index`
--
ALTER TABLE `nuke_quiz_index`
MODIFY `quizid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_quotes`
--
ALTER TABLE `nuke_quotes`
MODIFY `qid` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_referer`
--
ALTER TABLE `nuke_referer`
MODIFY `rid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=686657;
--
-- AUTO_INCREMENT for table `nuke_related`
--
ALTER TABLE `nuke_related`
MODIFY `rid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_reviews`
--
ALTER TABLE `nuke_reviews`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_reviews_add`
--
ALTER TABLE `nuke_reviews_add`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_reviews_comments`
--
ALTER TABLE `nuke_reviews_comments`
MODIFY `cid` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_seccont`
--
ALTER TABLE `nuke_seccont`
MODIFY `artid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `nuke_sections`
--
ALTER TABLE `nuke_sections`
MODIFY `secid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `nuke_sommaire_categories`
--
ALTER TABLE `nuke_sommaire_categories`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=464;
--
-- AUTO_INCREMENT for table `nuke_spelling_words`
--
ALTER TABLE `nuke_spelling_words`
MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=192935;
--
-- AUTO_INCREMENT for table `nuke_staff`
--
ALTER TABLE `nuke_staff`
MODIFY `sid` int(3) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `nuke_staff_cat`
--
ALTER TABLE `nuke_staff_cat`
MODIFY `id` int(3) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `nuke_stories`
--
ALTER TABLE `nuke_stories`
MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18259;
--
-- AUTO_INCREMENT for table `nuke_stories_cat`
--
ALTER TABLE `nuke_stories_cat`
MODIFY `catid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `nuke_stories_view`
--
ALTER TABLE `nuke_stories_view`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1867;
--
-- AUTO_INCREMENT for table `nuke_topics`
--
ALTER TABLE `nuke_topics`
MODIFY `topicid` int(3) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=34;
--
-- AUTO_INCREMENT for table `nuke_upermissions`
--
ALTER TABLE `nuke_upermissions`
MODIFY `pid` int(16) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `nuke_users`
--
ALTER TABLE `nuke_users`
MODIFY `user_id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=73605;
--
-- AUTO_INCREMENT for table `nuke_users_autologin`
--
ALTER TABLE `nuke_users_autologin`
MODIFY `autologin_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=49443;
--
-- AUTO_INCREMENT for table `nuke_users_flags`
--
ALTER TABLE `nuke_users_flags`
MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=43706;
--
-- AUTO_INCREMENT for table `nuke_users_groups`
--
ALTER TABLE `nuke_users_groups`
MODIFY `gid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `nuke_users_notes`
--
ALTER TABLE `nuke_users_notes`
MODIFY `nid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3320188;
--
-- AUTO_INCREMENT for table `nuke_users_temp`
--
ALTER TABLE `nuke_users_temp`
MODIFY `user_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `oauth_consumer`
--
ALTER TABLE `oauth_consumer`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1042;
--
-- AUTO_INCREMENT for table `oauth_consumer_nonce`
--
ALTER TABLE `oauth_consumer_nonce`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=39;
--
-- AUTO_INCREMENT for table `oauth_token`
--
ALTER TABLE `oauth_token`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `oauth_token_type`
--
ALTER TABLE `oauth_token_type`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `operators`
--
ALTER TABLE `operators`
MODIFY `operator_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=263;
--
-- AUTO_INCREMENT for table `organisation`
--
ALTER TABLE `organisation`
MODIFY `organisation_id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=35;
--
-- AUTO_INCREMENT for table `organisation_roles`
--
ALTER TABLE `organisation_roles`
MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `phpbb_reports_actions`
--
ALTER TABLE `phpbb_reports_actions`
MODIFY `action_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8646;
--
-- AUTO_INCREMENT for table `phpbb_reports_data`
--
ALTER TABLE `phpbb_reports_data`
MODIFY `data_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `phpbb_reports_posts`
--
ALTER TABLE `phpbb_reports_posts`
MODIFY `report_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8608;
--
-- AUTO_INCREMENT for table `phpbb_warnings`
--
ALTER TABLE `phpbb_warnings`
MODIFY `warn_id` int(30) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7954;
--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
MODIFY `poll_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `railcams`
--
ALTER TABLE `railcams`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `railcams_type`
--
ALTER TABLE `railcams_type`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `rating_loco`
--
ALTER TABLE `rating_loco`
MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=40;
--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=36;
--
-- AUTO_INCREMENT for table `route`
--
ALTER TABLE `route`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9596;
--
-- AUTO_INCREMENT for table `route_markers`
--
ALTER TABLE `route_markers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8161;
--
-- AUTO_INCREMENT for table `route_markers_tmp`
--
ALTER TABLE `route_markers_tmp`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sighting`
--
ALTER TABLE `sighting`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7544;
--
-- AUTO_INCREMENT for table `source`
--
ALTER TABLE `source`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `tag`
--
ALTER TABLE `tag`
MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4252;
--
-- AUTO_INCREMENT for table `tag_link`
--
ALTER TABLE `tag_link`
MODIFY `tag_link_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12546;
--
-- AUTO_INCREMENT for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=19297;
--
-- AUTO_INCREMENT for table `timetable_points`
--
ALTER TABLE `timetable_points`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=202;
--
-- AUTO_INCREMENT for table `timetable_regions`
--
ALTER TABLE `timetable_regions`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `timetable_trains`
--
ALTER TABLE `timetable_trains`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=300;
--
-- AUTO_INCREMENT for table `viewed_threads`
--
ALTER TABLE `viewed_threads`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=134015;
--
-- AUTO_INCREMENT for table `waynet`
--
ALTER TABLE `waynet`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1403055;
--
-- AUTO_INCREMENT for table `wheel_arrangements`
--
ALTER TABLE `wheel_arrangements`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=41;
--
-- AUTO_INCREMENT for table `woecache`
--
ALTER TABLE `woecache`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17413;
DELIMITER $$
--
-- Events
--
CREATE DEFINER=`mgreenhill`@`%` EVENT `trim_nuke_users_hash` ON SCHEDULE EVERY 1 DAY STARTS '2015-02-01 22:47:00' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM nuke_users_hash WHERE date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 month))$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `trim_nuke_bbsearch_results` ON SCHEDULE EVERY 1 DAY STARTS '2015-01-03 00:33:09' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
DELETE FROM nuke_bbsearch_results WHERE DATEDIFF (NOW(), search_time) >= 30;
END$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `trim_log_pageactivity` ON SCHEDULE EVERY 1 DAY STARTS '2013-03-12 17:43:49' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Deleting page activity logs older then 30 days' DO BEGIN
DELETE FROM log_pageactivity WHERE DATEDIFF (NOW(), time) >= 30;
END$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `trim_log_logins` ON SCHEDULE EVERY 1 DAY STARTS '2015-02-01 22:39:12' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM log_logins WHERE login_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 month))$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `trim_log_errors` ON SCHEDULE EVERY 1 DAY STARTS '2015-02-01 22:42:19' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Delete error logs older than 30 days' DO DELETE FROM log_errors WHERE error_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day))$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `trim_log_api` ON SCHEDULE EVERY 1 DAY STARTS '2014-11-28 09:16:46' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Delete API logs older than 30 days' DO BEGIN
DELETE FROM log_api WHERE DATEDIFF (NOW(), date) >= 30;
END$$

CREATE DEFINER=`mgreenhil`@`railpage` EVENT `trim_geoplace_weather` ON SCHEDULE EVERY 1 HOUR STARTS '2015-07-11 13:19:52' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM geoplace_forecast WHERE expires <= NOW()$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `photo_comp_disable` ON SCHEDULE EVERY 1 HOUR STARTS '2015-03-13 19:05:52' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE image_competition SET status = 1 WHERE (submissions_date_open > NOW() OR NOW() > voting_date_close) AND status = 0$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `photo_comp_enable` ON SCHEDULE EVERY 1 HOUR STARTS '2015-03-13 19:05:01' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE image_competition SET status = 0 WHERE NOW() >= submissions_date_open AND voting_date_close >= NOW() AND status = 1$$

CREATE DEFINER=`mgreenhill`@`%` EVENT `rp_resetStoryReadCounts` ON SCHEDULE EVERY 1 WEEK STARTS '2013-11-19 19:00:20' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE sparta_unittest.nuke_stories SET weeklycounter = 0$$

CREATE DEFINER=`mgreenhill`@`railpage` EVENT `trim_unactivated_users` ON SCHEDULE EVERY 1 DAY STARTS '2015-08-18 16:28:05' ON COMPLETION NOT PRESERVE DISABLE COMMENT 'Delete users who have not activated their accounts in 30 days' DO DELETE FROM nuke_users WHERE user_id IN (
	SELECT user_id FROM (
		SELECT u.user_id, u.username, u.user_email, u.user_regdate, u.user_regdate_nice, u.user_session_time, u.user_lastvisit, 
		COALESCE((SELECT COUNT(*) AS num FROM nuke_bbposts WHERE poster_id = u.user_id GROUP BY poster_id), 0) AS num_posts,
		COALESCE((SELECT COUNT(*) AS num FROM log_general WHERE user_id = u.user_id GROUP BY user_id), 0) AS num_logs,
		COALESCE((SELECT COUNT(*) AS num FROM log_herrings WHERE user_id = u.user_id GROUP BY user_id), 0) AS num_herrings
		FROM nuke_users AS u 
		WHERE u.user_id NOT IN (SELECT user_id FROM bancontrol WHERE ban_active = 1 AND user_id != 0) 
		AND u.user_session_time = 0
		AND u.user_lastvisit = 0
		AND u.user_regdate_nice != 0
		AND u.user_regdate_nice < DATE_SUB(NOW(), INTERVAL 30 day)
		AND u.user_id != 0
		ORDER BY user_regdate_nice ASC
	) AS blahIDontCare 
	WHERE num_posts = 0
	AND num_logs = 0
	AND num_herrings = 0
)$$

DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

