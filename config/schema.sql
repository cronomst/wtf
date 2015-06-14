SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wtf`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE IF NOT EXISTS `chat` (
  `chat_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `msg` varchar(256) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chat_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26010 ;

-- --------------------------------------------------------

--
-- Table structure for table `featured`
--

CREATE TABLE IF NOT EXISTS `featured` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(35) DEFAULT NULL,
  `caption` varchar(256) DEFAULT NULL,
  `image` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2627 ;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE IF NOT EXISTS `players` (
  `player_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(35) NOT NULL,
  `score` int(10) unsigned NOT NULL DEFAULT '0',
  `caption` varchar(256) DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `vote_id` bigint(20) unsigned DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`player_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3153 ;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Current round',
  `state` varchar(15) NOT NULL DEFAULT 'pregame',
  `image` varchar(100) DEFAULT NULL,
  `next_image` varchar(100) DEFAULT NULL,
  `rule` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Rule type',
  `rule_data` varchar(25) DEFAULT NULL COMMENT 'Stores current acronym or other rule-specific info',
  `timedata` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Timer data in ms',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last time the room was updated',
  `name` varchar(45) DEFAULT NULL,
  `clean` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(45) DEFAULT NULL,
  `caption_time` tinyint(2) unsigned NOT NULL DEFAULT '60',
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=738 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
