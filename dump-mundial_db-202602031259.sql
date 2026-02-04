/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.7.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mundial_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `achievement_bonus_points`
--

DROP TABLE IF EXISTS `achievement_bonus_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_bonus_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bonus_key` varchar(50) NOT NULL,
  `points_awarded` int(11) NOT NULL,
  `awarded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`bonus_key`),
  CONSTRAINT `achievement_bonus_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `achievement_bonus_points`
--

LOCK TABLES `achievement_bonus_points` WRITE;
/*!40000 ALTER TABLE `achievement_bonus_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `achievement_bonus_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_activity_log`
--

DROP TABLE IF EXISTS `admin_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `description` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_activity_log`
--

LOCK TABLES `admin_activity_log` WRITE;
/*!40000 ALTER TABLE `admin_activity_log` DISABLE KEYS */;
INSERT INTO `admin_activity_log` VALUES
(136,'match_close','Cerrado partido ID 1. Puntos, Ranking y Logros procesados.','2026-02-03 10:36:50'),
(137,'match_close','Cerrado partido ID 2. Puntos, Ranking y Logros procesados.','2026-02-03 11:08:07');
/*!40000 ALTER TABLE `admin_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bonus_candidates`
--

DROP TABLE IF EXISTS `bonus_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bonus_candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `team_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `type` enum('scorer','keeper') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `is_winner` tinyint(1) DEFAULT 0,
  `photo_url` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_candidate` (`name`,`team_name`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bonus_candidates`
--

LOCK TABLES `bonus_candidates` WRITE;
/*!40000 ALTER TABLE `bonus_candidates` DISABLE KEYS */;
INSERT INTO `bonus_candidates` VALUES
(1,'Goleador 1','España','scorer',0,'https://www.bdfutbol.com/i/j/25651b.jpg'),
(2,'Goleador 2','Francia','scorer',0,'https://www.bdfutbol.com/i/j/84413.jpg'),
(3,'Goleador 3','Argentina','scorer',0,'https://www.bdfutbol.com/i/j/1753l.jpg'),
(4,'Unai Simón','España','keeper',0,'https://www.bdfutbol.com/i/j/22117e.jpg');
/*!40000 ALTER TABLE `bonus_candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_quiz_questions`
--

DROP TABLE IF EXISTS `daily_quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `option_a` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `option_b` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `option_c` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `option_d` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `correct_answer` enum('A','B','C','D') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `date_available` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_available` (`date_available`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_quiz_questions`
--

LOCK TABLES `daily_quiz_questions` WRITE;
/*!40000 ALTER TABLE `daily_quiz_questions` DISABLE KEYS */;
INSERT INTO `daily_quiz_questions` VALUES
(2,'¿Cuál ha sido el gol más rápido en la historia de los Mundiales?	','11 segundos','15 segundos','2 segundos','25 segundos','A','2026-02-02'),
(3,'Pelé se convirtió en el jugador más joven en marcar en un Mundial, durante la edición de 1958. ¿A qué edad?','18','15','17','11','C','2025-11-26');
/*!40000 ALTER TABLE `daily_quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_quiz_responses`
--

DROP TABLE IF EXISTS `daily_quiz_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_quiz_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `points_awarded` int(11) DEFAULT 0,
  `response_date` date NOT NULL,
  `time_taken_sec` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_daily_response` (`user_id`,`response_date`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `daily_quiz_responses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `daily_quiz_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `daily_quiz_questions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_quiz_responses`
--

LOCK TABLES `daily_quiz_responses` WRITE;
/*!40000 ALTER TABLE `daily_quiz_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_quiz_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `global_chat`
--

DROP TABLE IF EXISTS `global_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` varchar(160) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_pinned` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `global_chat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `global_chat`
--

LOCK TABLES `global_chat` WRITE;
/*!40000 ALTER TABLE `global_chat` DISABLE KEYS */;
/*!40000 ALTER TABLE `global_chat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_ranking_points`
--

DROP TABLE IF EXISTS `group_ranking_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_ranking_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_name` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `points_awarded` int(11) DEFAULT 0,
  `awarded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_user` (`user_id`,`group_name`),
  CONSTRAINT `group_ranking_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_ranking_points`
--

LOCK TABLES `group_ranking_points` WRITE;
/*!40000 ALTER TABLE `group_ranking_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_ranking_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knockout_master`
--

DROP TABLE IF EXISTS `knockout_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knockout_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_phase` enum('round_32','round_16','quarter','semi','final') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `match_number` int(11) NOT NULL,
  `source_home_description` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `source_away_description` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `target_match_id` int(11) DEFAULT NULL,
  `target_team_slot` enum('home','away') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_match_phase` (`match_phase`,`match_number`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knockout_master`
--

LOCK TABLES `knockout_master` WRITE;
/*!40000 ALTER TABLE `knockout_master` DISABLE KEYS */;
INSERT INTO `knockout_master` VALUES
(1,'round_32',1,'1A','3C/D/E/F',NULL,NULL),
(2,'round_32',2,'1B','3A/E/F/G',NULL,NULL),
(3,'round_32',3,'1C','3A/B/F/G',NULL,NULL),
(4,'round_32',4,'1D','3B/C/G/H',NULL,NULL),
(5,'round_32',5,'1E','3A/B/C/D',NULL,NULL),
(6,'round_32',6,'1F','3B/C/D/E',NULL,NULL),
(7,'round_32',7,'1G','2J',NULL,NULL),
(8,'round_32',8,'1H','2K',NULL,NULL),
(9,'round_32',9,'1I','2L',NULL,NULL),
(10,'round_32',10,'1J','2G',NULL,NULL),
(11,'round_32',11,'1K','2H',NULL,NULL),
(12,'round_32',12,'1L','2I',NULL,NULL),
(13,'round_32',13,'2A','2B',NULL,NULL),
(14,'round_32',14,'2C','2D',NULL,NULL),
(15,'round_32',15,'2E','2F',NULL,NULL),
(16,'round_32',16,'2G','2H',NULL,NULL);
/*!40000 ALTER TABLE `knockout_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `match_challenges`
--

DROP TABLE IF EXISTS `match_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `match_challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `challenger_user_id` int(11) NOT NULL,
  `challenged_user_id` int(11) NOT NULL,
  `wager_status` enum('PENDING','PROCESSED','DRAW') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'PENDING',
  `points_seized` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_challenge` (`match_id`,`challenger_user_id`,`challenged_user_id`),
  KEY `challenger_user_id` (`challenger_user_id`),
  KEY `challenged_user_id` (`challenged_user_id`),
  CONSTRAINT `match_challenges_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
  CONSTRAINT `match_challenges_ibfk_2` FOREIGN KEY (`challenger_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `match_challenges_ibfk_3` FOREIGN KEY (`challenged_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `match_challenges`
--

LOCK TABLES `match_challenges` WRITE;
/*!40000 ALTER TABLE `match_challenges` DISABLE KEYS */;
/*!40000 ALTER TABLE `match_challenges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `match_comments`
--

DROP TABLE IF EXISTS `match_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `match_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `match_comments_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
  CONSTRAINT `match_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `match_comments`
--

LOCK TABLES `match_comments` WRITE;
/*!40000 ALTER TABLE `match_comments` DISABLE KEYS */;
INSERT INTO `match_comments` VALUES
(9,1,4,'dsdfsf','2026-02-03 10:38:33'),
(10,1,4,'tachan!!!','2026-02-03 11:38:10'),
(11,1,2,'tacatan!!','2026-02-03 11:38:58');
/*!40000 ALTER TABLE `match_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_home_id` int(11) DEFAULT NULL,
  `team_away_id` int(11) DEFAULT NULL,
  `match_date` datetime NOT NULL,
  `stadium` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `phase` enum('group','round_32','round_16','quarter','semi','final') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'group',
  `home_score` int(11) DEFAULT NULL,
  `away_score` int(11) DEFAULT NULL,
  `status` enum('scheduled','finished') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'scheduled',
  `real_qualifier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `team_home_id` (`team_home_id`),
  KEY `team_away_id` (`team_away_id`),
  KEY `fk_real_qualifier` (`real_qualifier_id`),
  CONSTRAINT `fk_real_qualifier` FOREIGN KEY (`real_qualifier_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`team_home_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`team_away_id`) REFERENCES `teams` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
INSERT INTO `matches` VALUES
(1,1,2,'2026-06-11 15:00:00','Estadio Placeholder','group',0,1,'finished',NULL),
(2,3,4,'2026-06-11 22:00:00','Estadio Akron','group',2,0,'finished',NULL),
(3,5,6,'2026-06-12 15:00:00','BMO Field','group',NULL,NULL,'scheduled',NULL),
(4,13,14,'2026-06-12 21:00:00','SoFi Stadium','group',NULL,NULL,'scheduled',NULL),
(5,9,10,'2026-06-13 18:00:00','MetLife Stadium','group',NULL,NULL,'scheduled',NULL),
(6,15,16,'2026-06-13 00:00:00','BC Place','group',NULL,NULL,'scheduled',NULL),
(7,11,12,'2026-06-13 21:00:00','Gillette Stadium','group',NULL,NULL,'scheduled',NULL),
(8,7,8,'2026-06-13 15:00:00','Levis Stadium','group',NULL,NULL,'scheduled',NULL),
(9,17,18,'2026-06-14 13:00:00','NRG Stadium','group',NULL,NULL,'scheduled',NULL),
(10,19,20,'2026-06-14 19:00:00','Lincoln Financial Field','group',NULL,NULL,'scheduled',NULL),
(11,21,22,'2026-06-14 16:00:00','AT&T Stadium','group',NULL,NULL,'scheduled',NULL),
(12,23,24,'2026-06-14 22:00:00','Estadio BBVA','group',NULL,NULL,'scheduled',NULL),
(13,29,30,'2026-06-15 12:00:00','MercedesBenz Stadium','group',NULL,NULL,'scheduled',NULL),
(14,31,32,'2026-06-15 18:00:00','Hard Rock Stadium','group',NULL,NULL,'scheduled',NULL),
(15,25,26,'2026-06-15 15:00:00','Lumen Field','group',NULL,NULL,'scheduled',NULL),
(16,27,28,'2026-06-15 21:00:00','SoFi Stadium','group',NULL,NULL,'scheduled',NULL),
(17,33,34,'2026-06-16 15:00:00','MetLife Stadium','group',NULL,NULL,'scheduled',NULL),
(18,35,36,'2026-06-16 18:00:00','Gillette Stadium','group',NULL,NULL,'scheduled',NULL),
(19,37,38,'2026-06-16 21:00:00','Arrowhead Stadium','group',NULL,NULL,'scheduled',NULL),
(20,39,40,'2026-06-16 00:00:00','Levis Stadium','group',NULL,NULL,'scheduled',NULL),
(21,45,46,'2026-06-17 16:00:00','AT&T Stadium','group',NULL,NULL,'scheduled',NULL),
(22,47,48,'2026-06-17 19:00:00','BMO Field','group',NULL,NULL,'scheduled',NULL),
(23,41,42,'2026-06-17 13:00:00','NRG Stadium','group',NULL,NULL,'scheduled',NULL),
(24,43,44,'2026-06-17 22:00:00','Estadio Placeholder','group',NULL,NULL,'scheduled',NULL),
(25,4,2,'2026-06-18 12:00:00','MercedesBenz Stadium','group',NULL,NULL,'scheduled',NULL),
(26,8,6,'2026-06-18 15:00:00','SoFi Stadium','group',NULL,NULL,'scheduled',NULL),
(27,5,7,'2026-06-18 18:00:00','BC Place','group',NULL,NULL,'scheduled',NULL),
(28,1,3,'2026-06-18 21:00:00','Estadio Akron','group',NULL,NULL,'scheduled',NULL),
(29,12,10,'2026-06-19 18:00:00','Gillette Stadium','group',NULL,NULL,'scheduled',NULL),
(30,9,11,'2026-06-19 17:00:00','Lincoln Financial Field','group',NULL,NULL,'scheduled',NULL),
(31,16,14,'2026-06-19 00:00:00','Levis Stadium','group',NULL,NULL,'scheduled',NULL),
(32,13,15,'2026-06-19 15:00:00','Lumen Field','group',NULL,NULL,'scheduled',NULL),
(33,20,18,'2026-06-20 22:00:00','Arrowhead Stadium','group',NULL,NULL,'scheduled',NULL),
(34,17,19,'2026-06-20 16:00:00','BMO Field','group',NULL,NULL,'scheduled',NULL),
(35,24,22,'2026-06-20 00:00:00','Estadio BBVA','group',NULL,NULL,'scheduled',NULL),
(36,21,23,'2026-06-20 13:00:00','NRG Stadium','group',NULL,NULL,'scheduled',NULL),
(37,32,30,'2026-06-21 18:00:00','Hard Rock Stadium','group',NULL,NULL,'scheduled',NULL),
(38,29,31,'2026-06-21 12:00:00','MercedesBenz Stadium','group',NULL,NULL,'scheduled',NULL),
(39,28,26,'2026-06-21 21:00:00','BC Place','group',NULL,NULL,'scheduled',NULL),
(40,25,27,'2026-06-21 15:00:00','SoFi Stadium','group',NULL,NULL,'scheduled',NULL),
(41,36,34,'2026-06-22 20:00:00','MetLife Stadium','group',NULL,NULL,'scheduled',NULL),
(42,33,35,'2026-06-22 17:00:00','Lincoln Financial Field','group',NULL,NULL,'scheduled',NULL),
(43,40,38,'2026-06-22 23:00:00','Levis Stadium','group',NULL,NULL,'scheduled',NULL),
(44,37,39,'2026-06-22 13:00:00','AT&T Stadium','group',NULL,NULL,'scheduled',NULL),
(45,48,46,'2026-06-23 19:00:00','BMO Field','group',NULL,NULL,'scheduled',NULL),
(46,45,47,'2026-06-23 16:00:00','Gillette Stadium','group',NULL,NULL,'scheduled',NULL),
(47,44,42,'2026-06-23 22:00:00','Estadio Akron','group',NULL,NULL,'scheduled',NULL),
(48,41,43,'2026-06-23 13:00:00','NRG Stadium','group',NULL,NULL,'scheduled',NULL),
(49,9,12,'2026-06-24 18:00:00','Hard Rock Stadium','group',NULL,NULL,'scheduled',NULL),
(50,10,11,'2026-06-24 18:00:00','MercedesBenz Stadium','group',NULL,NULL,'scheduled',NULL),
(51,8,5,'2026-06-24 15:00:00','BC Place','group',NULL,NULL,'scheduled',NULL),
(52,6,7,'2026-06-24 15:00:00','Lumen Field','group',NULL,NULL,'scheduled',NULL),
(53,4,1,'2026-06-24 21:00:00','Estadio Placeholder','group',NULL,NULL,'scheduled',NULL),
(54,3,2,'2026-06-24 21:00:00','Estadio BBVA','group',NULL,NULL,'scheduled',NULL),
(55,20,17,'2026-06-25 16:00:00','MetLife Stadium','group',NULL,NULL,'scheduled',NULL),
(56,18,19,'2026-06-25 16:00:00','Lincoln Financial Field','group',NULL,NULL,'scheduled',NULL),
(57,24,21,'2026-06-25 19:00:00','Arrowhead Stadium','group',NULL,NULL,'scheduled',NULL),
(58,22,23,'2026-06-25 19:00:00','NRG Stadium','group',NULL,NULL,'scheduled',NULL),
(59,16,13,'2026-06-25 22:00:00','SoFi Stadium','group',NULL,NULL,'scheduled',NULL),
(60,14,15,'2026-06-25 22:00:00','Levis Stadium','group',NULL,NULL,'scheduled',NULL),
(61,36,33,'2026-06-26 15:00:00','Gillette Stadium','group',NULL,NULL,'scheduled',NULL),
(62,34,35,'2026-06-26 15:00:00','MetLife Stadium','group',NULL,NULL,'scheduled',NULL),
(63,28,25,'2026-06-26 23:00:00','BC Place','group',NULL,NULL,'scheduled',NULL),
(64,26,27,'2026-06-26 23:00:00','Lumen Field','group',NULL,NULL,'scheduled',NULL),
(65,32,29,'2026-06-26 20:00:00','Estadio Akron','group',NULL,NULL,'scheduled',NULL),
(66,30,31,'2026-06-26 20:00:00','NRG Stadium','group',NULL,NULL,'scheduled',NULL),
(67,48,45,'2026-06-27 17:00:00','MetLife Stadium','group',NULL,NULL,'scheduled',NULL),
(68,46,47,'2026-06-27 17:00:00','Lincoln Financial Field','group',NULL,NULL,'scheduled',NULL),
(69,40,37,'2026-06-27 22:00:00','AT&T Stadium','group',NULL,NULL,'scheduled',NULL),
(70,38,39,'2026-06-27 22:00:00','Arrowhead Stadium','group',NULL,NULL,'scheduled',NULL),
(71,44,41,'2026-06-27 19:30:00','Hard Rock Stadium','group',NULL,NULL,'scheduled',NULL),
(72,42,43,'2026-06-27 19:30:00','MercedesBenz Stadium','group',NULL,NULL,'scheduled',NULL);
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `predictions`
--

DROP TABLE IF EXISTS `predictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `predictions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `predicted_home_score` int(11) NOT NULL,
  `predicted_away_score` int(11) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `prediction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `predicted_qualifier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prediction` (`user_id`,`match_id`),
  KEY `match_id` (`match_id`),
  KEY `fk_pred_qualifier` (`predicted_qualifier_id`),
  CONSTRAINT `fk_pred_qualifier` FOREIGN KEY (`predicted_qualifier_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `predictions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `predictions_ibfk_2` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=408 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `predictions`
--

LOCK TABLES `predictions` WRITE;
/*!40000 ALTER TABLE `predictions` DISABLE KEYS */;
INSERT INTO `predictions` VALUES
(399,4,1,2,1,5,'2026-02-03 10:26:07',NULL),
(400,2,1,0,2,15,'2026-02-03 10:26:45',NULL),
(401,3,1,3,0,0,'2026-02-03 10:27:00',NULL),
(402,5,1,2,2,0,'2026-02-03 10:28:16',NULL),
(404,4,2,1,0,30,'2026-02-03 11:06:59',NULL),
(405,2,2,2,0,25,'2026-02-03 11:07:18',NULL),
(406,5,2,2,2,5,'2026-02-03 11:07:31',NULL),
(407,3,2,2,1,15,'2026-02-03 11:07:44',NULL);
/*!40000 ALTER TABLE `predictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ranking_history`
--

DROP TABLE IF EXISTS `ranking_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ranking_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `points_at_moment` int(11) NOT NULL,
  `rank_at_moment` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `match_id` (`match_id`),
  CONSTRAINT `ranking_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ranking_history_ibfk_2` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=495 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ranking_history`
--

LOCK TABLES `ranking_history` WRITE;
/*!40000 ALTER TABLE `ranking_history` DISABLE KEYS */;
INSERT INTO `ranking_history` VALUES
(487,2,1,15,1,'2026-02-03 10:36:50'),
(488,4,1,5,2,'2026-02-03 10:36:50'),
(489,3,1,0,3,'2026-02-03 10:36:50'),
(490,5,1,0,4,'2026-02-03 10:36:50'),
(491,2,2,40,1,'2026-02-03 11:08:07'),
(492,4,2,35,2,'2026-02-03 11:08:07'),
(493,3,2,15,3,'2026-02-03 11:08:07'),
(494,5,2,5,4,'2026-02-03 11:08:07');
/*!40000 ALTER TABLE `ranking_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stadiums`
--

DROP TABLE IF EXISTS `stadiums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stadiums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `city_country` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `image_url` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stadiums`
--

LOCK TABLES `stadiums` WRITE;
/*!40000 ALTER TABLE `stadiums` DISABLE KEYS */;
INSERT INTO `stadiums` VALUES
(1,'Estadio Placeholder','Ciudad de México, México','../assets/img/stadiums/azteca.webp'),
(2,'MetLife Stadium','Nueva York, USA','../assets/img/stadiums/metlife.webp'),
(3,'AT&T Stadium','Arlington, USA','../assets/img/stadiums/att.webp'),
(4,'Estadio Akron','Guadalajara, México','../assets/img/stadiums/akron.webp'),
(5,'BMO Field','Toronto, Canadá','../assets/img/stadiums/bmo.webp'),
(6,'BC Place','Vancouver, Canadá','../assets/img/stadiums/bcplace.webp'),
(7,'Arrowhead Stadium','Kansas City, USA','../assets/img/stadiums/arrowhead.webp'),
(8,'Estadio BBVA','Monterrey, México','../assets/img/stadiums/bbva.webp'),
(9,'Gillette Stadium','Boston, USA','../assets/img/stadiums/gillette.webp'),
(10,'Hard Rock Stadium','Miami, USA','../assets/img/stadiums/hardrock.webp'),
(11,'Levis Stadium','San Francisco, USA','../assets/img/stadiums/levis.webp'),
(12,'Lincoln Financial Field','Filadelfia, USA','../assets/img/stadiums/lincoln.webp'),
(13,'Lumen Field','Seattle, USA','../assets/img/stadiums/lumen.webp'),
(14,'MercedesBenz Stadium','Atlanta, USA','../assets/img/stadiums/mercedesbenz.webp'),
(15,'NRG Stadium','Houston, USA','../assets/img/stadiums/nrg.webp'),
(16,'SoFi Stadium','Los Ángeles, USA','../assets/img/stadiums/sofi.webp');
/*!40000 ALTER TABLE `stadiums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `key_players` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
INSERT INTO `teams` VALUES
(1,'México','Mex','mx','A','Mexicano'),
(2,'Sudáfrica','Sud','za','A','Sudafricano'),
(3,'República de Corea','Cor','kr','A','CoreaSurano'),
(4,'Dinamarca','Din','dk','A','Dinamarques'),
(5,'Canadá','Can','ca','B','Canadiense'),
(6,'Italia','Ita','it','B','Italiano'),
(7,'Catar','Cat','qa','B','Catari'),
(8,'Suiza','Sui','ch','B','Suizo'),
(9,'Brasil','Bra','br','C','Brasileiro'),
(10,'Marruecos','Mar','ma','C','Marroqui'),
(11,'Haití','Hai','ht','C','Haitiano'),
(12,'Escocia','Esc','gb-sct','C','Escoces'),
(13,'Estados Unidos','Usa','us','D','Donald'),
(14,'Paraguay','Par','pa','D','Paraguayo'),
(15,'Australia','Aus','au','D','Australiano'),
(16,'Turquía','Tur','tr','D','Turquiano'),
(17,'Alemania','Ale','de','E','Aleman'),
(18,'Curazao','Cur','cw','E','Curazaono'),
(19,'Costa Marfil','Cma','ci','E','Marfileño'),
(20,'Ecuador','Ecu','ec','E','Ecuadorano'),
(21,'Países Bajos','Pba','nl','F','Bajo'),
(22,'Japón','Jpn','jp','F','Nipón'),
(23,'Polonia','Pol','pl','F','Polaco'),
(24,'Túnez','Tun','tn','F','Tunecino'),
(25,'Bélgica','Bel','be','G','Belgico'),
(26,'Egipto','Egi','eg','G','Egipcio'),
(27,'Irán','Ira','ir','G','Irani'),
(28,'Nueva Zelanda','New','nz','G','Nuevo'),
(29,'España','Esp','es','H','Español'),
(30,'Cabo Verde','Ver','cv','H','Verdiano'),
(31,'Arabia Saudí','Ara','sa','H','Saudi'),
(32,'Uruguay','Uru','uy','H','Uruaguayo'),
(33,'Francia','Fra','fr','I','Francés'),
(34,'Senegal','Sen','se','I','Senegalés'),
(35,'Bolivia','Bol','bo','I','Boliviano'),
(36,'Noruega','Nor','no','I','Noruego'),
(37,'Argentina','Arg','ar','J','messi'),
(38,'Argelia','Arl','dz','J','Argelino'),
(39,'Austria','Ast','at','J','Austriaco'),
(40,'Jordania','Jor','jo','J','Jordano'),
(41,'Portugal','Por','pt','K','Portugués'),
(42,'Jamaica','Jam','jm','K','Bob Marley'),
(43,'Uzbekistán','Uzb','uz','K','Uzbeko'),
(44,'Colombia','Col','co','K','Colombiano'),
(45,'Inglaterra','Ing','gb-eng','L','Inglés'),
(46,'Croacia','Cro','hr','L','Croata'),
(47,'Ghana','Gha','gh','L','Ghanés'),
(48,'Panamá','Pan','pa','L','Panameño');
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tournament_results`
--

DROP TABLE IF EXISTS `tournament_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournament_results` (
  `id` int(11) NOT NULL DEFAULT 1,
  `final_total_goals` int(11) DEFAULT NULL,
  `champion_team_id` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `champion_team_id` (`champion_team_id`),
  CONSTRAINT `tournament_results_ibfk_1` FOREIGN KEY (`champion_team_id`) REFERENCES `teams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tournament_results`
--

LOCK TABLES `tournament_results` WRITE;
/*!40000 ALTER TABLE `tournament_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `tournament_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_achievements`
--

DROP TABLE IF EXISTS `user_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `achievement_key` varchar(50) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_achievement` (`user_id`,`achievement_key`),
  CONSTRAINT `fk_user_achievements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=352 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_achievements`
--

LOCK TABLES `user_achievements` WRITE;
/*!40000 ALTER TABLE `user_achievements` DISABLE KEYS */;
INSERT INTO `user_achievements` VALUES
(348,4,'hawk_eye','2026-02-03 11:08:07'),
(349,2,'hawk_eye','2026-02-03 11:08:07'),
(351,4,'strategist','2026-02-03 11:08:07');
/*!40000 ALTER TABLE `user_achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_bonus_predictions`
--

DROP TABLE IF EXISTS `user_bonus_predictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_bonus_predictions` (
  `user_id` int(11) NOT NULL,
  `scorer_candidate_id` int(11) DEFAULT NULL,
  `keeper_candidate_id` int(11) DEFAULT NULL,
  `total_goals_prediction` int(11) DEFAULT NULL,
  `champion_team_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `scorer_candidate_id` (`scorer_candidate_id`),
  KEY `keeper_candidate_id` (`keeper_candidate_id`),
  KEY `fk_champion_team` (`champion_team_id`),
  CONSTRAINT `fk_champion_team` FOREIGN KEY (`champion_team_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `user_bonus_predictions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `user_bonus_predictions_ibfk_2` FOREIGN KEY (`scorer_candidate_id`) REFERENCES `bonus_candidates` (`id`),
  CONSTRAINT `user_bonus_predictions_ibfk_3` FOREIGN KEY (`keeper_candidate_id`) REFERENCES `bonus_candidates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_bonus_predictions`
--

LOCK TABLES `user_bonus_predictions` WRITE;
/*!40000 ALTER TABLE `user_bonus_predictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_bonus_predictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_read_status`
--

DROP TABLE IF EXISTS `user_read_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_read_status` (
  `user_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`match_id`),
  KEY `match_id` (`match_id`),
  CONSTRAINT `user_read_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `user_read_status_ibfk_2` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_read_status`
--

LOCK TABLES `user_read_status` WRITE;
/*!40000 ALTER TABLE `user_read_status` DISABLE KEYS */;
INSERT INTO `user_read_status` VALUES
(2,1,'2026-02-03 11:38:58'),
(4,1,'2026-02-03 11:39:26');
/*!40000 ALTER TABLE `user_read_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `email` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `role` enum('admin','user') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `wildcard_used_match_id` int(11) DEFAULT NULL,
  `last_known_rank` int(11) DEFAULT NULL,
  `rival_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_wildcard_match` (`wildcard_used_match_id`),
  KEY `fk_rival` (`rival_id`),
  CONSTRAINT `fk_rival` FOREIGN KEY (`rival_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_wildcard_match` FOREIGN KEY (`wildcard_used_match_id`) REFERENCES `matches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Admin','admin@mundial.com','$2y$10$Er06V/Hvc3LeQ8huzuSYMe9HfHIyc9c5bj3PIckNriMn8/TOcoCs2','admin','2025-11-18 15:05:35',NULL,NULL,NULL),
(2,'kepacampo','kepa@mundial.com','$2y$10$ZnpLpfheJq.nWqkRZfVN/uDHp0wat0RBJErl3XkbdnBVtR9YaWyVO','user','2025-11-18 16:15:47',NULL,1,NULL),
(3,'leirepueyo','leire@mundial.com','$2y$10$ABJhiv6jqzWtIl5oTaY2E.eqO3r1AYlZikqraKJGTjl72jGTZ.Edm','user','2025-11-18 16:28:22',1,3,NULL),
(4,'alaincampo','alain@mundial.com','$2y$10$.jDMDRmlCSbovHVeKXIv/eXUu5idPMwtmbxuCEkfAKb7x318mDtvK','user','2025-12-16 15:56:36',2,2,NULL),
(5,'iraticampo','irati@mundial.com','$2y$10$CuIxfOY7wRWDOxCq9NgC3eF8piBgC7MkRrLIGsp8F/BXWzqdhstYi','user','2026-02-02 12:29:47',NULL,4,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'mundial_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-02-03 12:59:03
