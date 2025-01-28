--
-- ---------------------------------------------------------------------
--
-- GLPI - Gestionnaire Libre de Parc Informatique
--
-- http://glpi-project.org
--
-- @copyright 2015-2025 Teclib' and contributors.
-- @licence   https://www.gnu.org/licenses/gpl-3.0.html
--
-- ---------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of GLPI.
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.
--
-- ---------------------------------------------------------------------
--

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `glpi_plugin_formcreator_categories`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `comment` mediumtext COLLATE utf8mb4_unicode_ci,
  `completename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plugin_formcreator_categories_id` int unsigned NOT NULL DEFAULT '0',
  `level` int NOT NULL DEFAULT '1',
  `sons_cache` longtext COLLATE utf8mb4_unicode_ci,
  `ancestors_cache` longtext COLLATE utf8mb4_unicode_ci,
  `knowbaseitemcategories_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`),
  KEY `plugin_formcreator_categories_id` (`plugin_formcreator_categories_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_categories`
--

LOCK TABLES `glpi_plugin_formcreator_categories` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_categories` DISABLE KEYS */;
INSERT INTO `glpi_plugin_formcreator_categories` VALUES (1,'My test form category','','Root form categorie > My test form category',3,2,'{\"1\":1}','{\"3\":3}',0),(3,'Root form category','','Root form categorie',0,1,NULL,'[]',0);
/*!40000 ALTER TABLE `glpi_plugin_formcreator_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_groups`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_forms_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL,
  `groups_id` int unsigned NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_groups`
--

LOCK TABLES `glpi_plugin_formcreator_forms_groups` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_questions`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_questions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `plugin_formcreator_sections_id` int unsigned NOT NULL DEFAULT '0',
  `fieldtype` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `show_empty` tinyint(1) NOT NULL DEFAULT '0',
  `default_values` mediumtext COLLATE utf8mb4_unicode_ci,
  `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'itemtype used for glpi objects and dropdown question types',
  `values` mediumtext COLLATE utf8mb4_unicode_ci,
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `row` int NOT NULL DEFAULT '0',
  `col` int NOT NULL DEFAULT '0',
  `width` int NOT NULL DEFAULT '0',
  `show_rule` int NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_sections_id` (`plugin_formcreator_sections_id`),
  FULLTEXT KEY `Search` (`name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_questions`
--

LOCK TABLES `glpi_plugin_formcreator_questions` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questions` DISABLE KEYS */;
INSERT INTO `glpi_plugin_formcreator_questions` VALUES (22,'Test form migration for questions - Actor',11,'actor',0,0,'[2]','',NULL,'',0,2,2,1,'13d0d449-91f5039d-67877fbc44eef4.16672745'),(23,'Test form migration for questions - Additional fields',11,'fields',0,0,NULL,'','{\"dropdown_fields_field\":\"gzfllfield\",\"blocks_field\":\"4\"}','',0,0,2,1,'13d0d449-91f5039d-678783beb1b4e5.64512762'),(24,'Test form migration for questions - Checkboxes',11,'checkboxes',0,0,'[\"Option 2\",\"Option 5\"]','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\",\"Option 5\",\"Option 6\",\"Option 7\"]','',1,0,1,1,'13d0d449-91f5039d-678783e6187d37.70279252'),(25,'Test form migration for questions - Date',11,'date',0,0,'2025-01-29','',NULL,'',1,1,1,1,'13d0d449-91f5039d-678783fbef07e7.87750991'),(26,'Test form migration for questions - Date and time',11,'datetime',0,0,'2025-01-29 12:00:00','',NULL,'',1,2,1,1,'13d0d449-91f5039d-6787840a131039.05969649'),(27,'Test form migration for questions - Description',11,'description',0,0,NULL,'',NULL,'&#60;p&#62;This is a description question type&#60;/p&#62;',1,3,1,1,'13d0d449-91f5039d-678784229b82d7.42642948'),(28,'Test form migration for questions - Dropdown',11,'dropdown',0,0,'1','Location','{\"show_tree_depth\":\"0\",\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\",\"entity_restrict\":\"2\"}','',2,0,4,1,'13d0d449-91f5039d-67878445d795b6.07616686'),(29,'Test form migration for questions - Email',11,'email',0,0,'test@test.fr','','','',3,0,4,1,'13d0d449-91f5039d-6787845a8c0550.23541628'),(30,'Test form migration for questions - File',11,'file',0,0,NULL,'',NULL,'',4,0,4,1,'13d0d449-91f5039d-678789701851a0.76378229'),(31,'Test form migration for questions - Float',11,'float',0,0,'8,45','','','',5,0,4,1,'13d0d449-91f5039d-6787897e0703b1.90417706'),(32,'Test form migration for questions - Glpi Object',11,'glpiselect',0,0,'1','Computer','{\"entity_restrict\":\"2\"}','',6,0,4,1,'13d0d449-91f5039d-67878998584a27.11882972'),(33,'Test form migration for questions - Hidden field',11,'hidden',0,0,'test hidden field','',NULL,'',7,0,4,1,'13d0d449-91f5039d-678789a5a0f964.26980024'),(34,'Test form migration for questions - Hostname',11,'hostname',0,0,NULL,'',NULL,'',8,0,4,1,'13d0d449-91f5039d-678789b34997b1.33623123'),(35,'Test form migration for questions - IP Addresse',11,'ip',0,0,NULL,'',NULL,'',9,0,4,1,'13d0d449-91f5039d-678789c0104a75.96918904'),(36,'Test form migration for questions - Integer',11,'integer',0,0,'78','','','',10,0,4,1,'13d0d449-91f5039d-678789c988a473.24972235'),(37,'Test form migration for questions - LDAP Select',11,'ldapselect',0,0,NULL,'','{\"ldap_auth\":\"1\",\"ldap_attribute\":\"12\",\"ldap_filter\":\"(& (uid=*) )\"}','',11,0,4,1,'13d0d449-91f5039d-67878a05d3c326.35829700'),(38,'Test form migration for questions - Multiselect',11,'multiselect',0,0,'[\"Option 3\",\"Option 4\"]','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\",\"Option 5\"]','',12,0,4,1,'13d0d449-91f5039d-67878a2d90cea2.48530344'),(39,'Test form migration for questions - Radios',11,'radios',0,0,'Option 2','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\"]','',13,0,4,1,'13d0d449-91f5039d-67878a48cf71b3.62293600'),(40,'Test form migration for questions - Request type',11,'requesttype',0,0,'2','',NULL,'',14,0,4,1,'13d0d449-91f5039d-67878a53ac2c51.37272675'),(41,'Test form migration for questions - Select',11,'select',0,0,'Option 1','','[\"Option 1\",\"Option 2\"]','',15,0,4,1,'13d0d449-91f5039d-67878a69d3cf02.76840759'),(42,'Test form migration for questions - Tags',11,'tag',0,0,NULL,'',NULL,'',18,0,4,1,'13d0d449-91f5039d-67878ae3629375.79074002'),(43,'Test form migration for questions - Text',11,'text',0,0,'Test default text value','',NULL,'',17,0,4,1,'13d0d449-91f5039d-67878af59157f2.25429606'),(44,'Test form migration for questions - Textarea',11,'textarea',0,0,'&#60;p&#62;Test &#60;span style=\"color: #2dc26b; background-color: #843fa1;\"&#62;default value&#60;/span&#62; text &#60;strong&#62;area&#60;/strong&#62;&#60;/p&#62;','',NULL,'',16,0,4,1,'13d0d449-91f5039d-67878b0d55b865.54398926'),(45,'Test form migration for questions - Time',11,'time',0,0,'12:00:00','',NULL,'',19,0,4,1,'13d0d449-91f5039d-67878b1b2b2b86.77749904'),(46,'Test form migration for questions - Urgency',11,'urgency',0,0,'2','',NULL,'',20,0,4,1,'13d0d449-91f5039d-67878b28b26020.93425219');
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_conditions`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_conditions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'itemtype of the item affected by the condition',
  `items_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'item ID of the item affected by the condition',
  `plugin_formcreator_questions_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'question to test for the condition',
  `show_condition` int NOT NULL DEFAULT '0',
  `show_value` mediumtext COLLATE utf8mb4_unicode_ci,
  `show_logic` int NOT NULL DEFAULT '1',
  `order` int NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_conditions`
--

LOCK TABLES `glpi_plugin_formcreator_conditions` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_conditions` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_sections`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_sections` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `show_rule` int NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_forms_id` (`plugin_formcreator_forms_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_sections`
--

LOCK TABLES `glpi_plugin_formcreator_sections` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_sections` DISABLE KEYS */;
INSERT INTO `glpi_plugin_formcreator_sections` VALUES (7,'Section',5,1,1,'13d0d449-91f5039d-67865f9ec21e00.64800503'),(8,'Section',4,1,1,'13d0d449-91f5039d-67868bb732f521.89613959'),(9,'First section',6,1,1,'13d0d449-91f5039d-67868bda15b5e4.42395948'),(10,'Second section',6,2,1,'13d0d449-91f5039d-67868bdf03a686.18185201'),(11,'Section',7,1,1,'13d0d449-91f5039d-67877f5c52efd1.07463389');
/*!40000 ALTER TABLE `glpi_plugin_formcreator_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targetproblems`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targetproblems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_targetproblems` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `target_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `problemtemplates_id` int unsigned NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `impactcontent` longtext COLLATE utf8mb4_unicode_ci,
  `causecontent` longtext COLLATE utf8mb4_unicode_ci,
  `symptomcontent` longtext COLLATE utf8mb4_unicode_ci,
  `urgency_rule` int NOT NULL DEFAULT '1',
  `urgency_question` int unsigned NOT NULL DEFAULT '0',
  `destination_entity` int NOT NULL DEFAULT '1',
  `destination_entity_value` int unsigned NOT NULL DEFAULT '0',
  `tag_type` int NOT NULL DEFAULT '1',
  `tag_questions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tag_specifics` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category_rule` int NOT NULL DEFAULT '1',
  `category_question` int unsigned NOT NULL DEFAULT '0',
  `show_rule` int NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `problemtemplates_id` (`problemtemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_targetproblems`
--

LOCK TABLES `glpi_plugin_formcreator_targetproblems` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targetproblems` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targetproblems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_items_targettickets`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_items_targettickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_items_targettickets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_targettickets_id` int unsigned NOT NULL DEFAULT '0',
  `link` int NOT NULL DEFAULT '0',
  `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `items_id` int unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_targettickets_id` (`plugin_formcreator_targettickets_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_items_targettickets`
--

LOCK TABLES `glpi_plugin_formcreator_items_targettickets` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_items_targettickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_items_targettickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_questiondependencies`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_questiondependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_questiondependencies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_formcreator_questions_id_2` int unsigned NOT NULL DEFAULT '0',
  `fieldname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`),
  KEY `plugin_formcreator_questions_id_2` (`plugin_formcreator_questions_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_questiondependencies`
--

LOCK TABLES `glpi_plugin_formcreator_questiondependencies` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questiondependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questiondependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_answers`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_answers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_formanswers_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_formcreator_questions_id` int unsigned NOT NULL DEFAULT '0',
  `answer` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_formanswers_id` (`plugin_formcreator_formanswers_id`),
  KEY `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_answers`
--

LOCK TABLES `glpi_plugin_formcreator_answers` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targettickets`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targettickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_targettickets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `target_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source_rule` int NOT NULL DEFAULT '0',
  `source_question` int NOT NULL DEFAULT '0',
  `type_rule` int NOT NULL DEFAULT '0',
  `type_question` int unsigned NOT NULL DEFAULT '0',
  `tickettemplates_id` int unsigned NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `due_date_rule` int NOT NULL DEFAULT '1',
  `due_date_question` int unsigned NOT NULL DEFAULT '0',
  `due_date_value` tinyint DEFAULT NULL,
  `due_date_period` int NOT NULL DEFAULT '0',
  `urgency_rule` int NOT NULL DEFAULT '1',
  `urgency_question` int unsigned NOT NULL DEFAULT '0',
  `validation_followup` tinyint(1) NOT NULL DEFAULT '1',
  `destination_entity` int NOT NULL DEFAULT '1',
  `destination_entity_value` int unsigned NOT NULL DEFAULT '0',
  `tag_type` int NOT NULL DEFAULT '1',
  `tag_questions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tag_specifics` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category_rule` int NOT NULL DEFAULT '1',
  `category_question` int unsigned NOT NULL DEFAULT '0',
  `associate_rule` int NOT NULL DEFAULT '1',
  `associate_question` int unsigned NOT NULL DEFAULT '0',
  `location_rule` int NOT NULL DEFAULT '1',
  `location_question` int unsigned NOT NULL DEFAULT '0',
  `commonitil_validation_rule` int NOT NULL DEFAULT '1',
  `commonitil_validation_question` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_rule` int NOT NULL DEFAULT '1',
  `sla_rule` int NOT NULL DEFAULT '1',
  `sla_question_tto` int unsigned NOT NULL DEFAULT '0',
  `sla_question_ttr` int unsigned NOT NULL DEFAULT '0',
  `ola_rule` int NOT NULL DEFAULT '1',
  `ola_question_tto` int unsigned NOT NULL DEFAULT '0',
  `ola_question_ttr` int unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tickettemplates_id` (`tickettemplates_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_targettickets`
--

LOCK TABLES `glpi_plugin_formcreator_targettickets` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targettickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targettickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_entityconfigs`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_entityconfigs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_entityconfigs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `replace_helpdesk` int NOT NULL DEFAULT '-2',
  `default_form_list_mode` int NOT NULL DEFAULT '-2',
  `sort_order` int NOT NULL DEFAULT '-2',
  `is_kb_separated` int NOT NULL DEFAULT '-2',
  `is_search_visible` int NOT NULL DEFAULT '-2',
  `is_dashboard_visible` int NOT NULL DEFAULT '-2',
  `is_header_visible` int NOT NULL DEFAULT '-2',
  `is_search_issue_visible` int NOT NULL DEFAULT '-2',
  `tile_design` int NOT NULL DEFAULT '-2',
  `header` text COLLATE utf8mb4_unicode_ci,
  `service_catalog_home` int NOT NULL DEFAULT '-2',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`entities_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_entityconfigs`
--

LOCK TABLES `glpi_plugin_formcreator_entityconfigs` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_entityconfigs` DISABLE KEYS */;
INSERT INTO `glpi_plugin_formcreator_entityconfigs` VALUES (1,0,0,0,0,0,0,1,0,1,0,NULL,-2);
/*!40000 ALTER TABLE `glpi_plugin_formcreator_entityconfigs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_languages`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_forms_languages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_languages`
--

LOCK TABLES `glpi_plugin_formcreator_forms_languages` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_languages` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_forms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icon_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `background_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `access_rights` tinyint(1) NOT NULL DEFAULT '1',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `plugin_formcreator_categories_id` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `language` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `helpdesk_home` tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `validation_required` tinyint(1) NOT NULL DEFAULT '0',
  `usage_count` int NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_captcha_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `show_rule` int NOT NULL DEFAULT '1' COMMENT 'Conditions setting to show the submit button',
  `formanswer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_visible` tinyint NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `plugin_formcreator_categories_id` (`plugin_formcreator_categories_id`),
  FULLTEXT KEY `Search` (`name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_forms`
--

LOCK TABLES `glpi_plugin_formcreator_forms` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms` DISABLE KEYS */;
INSERT INTO `glpi_plugin_formcreator_forms` VALUES (4,'Test form migration for basic properties',0,0,'0','#999999','#e7e7e7',1,'','',0,1,'',0,0,0,0,0,0,1,'Test form migration for basic properties',1,'13d0d449-91f5039d-678638f86ff479.66501068'),(5,'Test form migration for basic properties with form category',0,0,'0','#999999','#e7e7e7',1,'','',1,1,'',0,0,0,0,0,0,1,'Test form migration for basic properties with form category',1,'13d0d449-91f5039d-67865f9ebd9f58.29603668'),(6,'Test form migration for sections',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for sections',1,'13d0d449-91f5039d-67868bc224e124.20535500'),(7,'Test form migration for questions',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for questions',1,'13d0d449-91f5039d-67877f5c4ee3c3.90813653');
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_validators`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_validators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_forms_validators` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `items_id` int unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_validators`
--

LOCK TABLES `glpi_plugin_formcreator_forms_validators` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_validators` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_validators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_profiles`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_forms_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `profiles_id` int unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`profiles_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_profiles`
--

LOCK TABLES `glpi_plugin_formcreator_forms_profiles` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_formanswers`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_formanswers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_formanswers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `requester_id` int unsigned NOT NULL DEFAULT '0',
  `users_id_validator` int unsigned NOT NULL DEFAULT '0' COMMENT 'User in charge of validation',
  `groups_id_validator` int unsigned NOT NULL DEFAULT '0' COMMENT 'Group in charge of validation',
  `request_date` timestamp NULL DEFAULT NULL,
  `status` int NOT NULL DEFAULT '101',
  `comment` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_forms_id` (`plugin_formcreator_forms_id`),
  KEY `entities_id_is_recursive` (`entities_id`,`is_recursive`),
  KEY `requester_id` (`requester_id`),
  KEY `users_id_validator` (`users_id_validator`),
  KEY `groups_id_validator` (`groups_id_validator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_formanswers`
--

LOCK TABLES `glpi_plugin_formcreator_formanswers` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_formanswers` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_formanswers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_questionregexes`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_questionregexes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_questionregexes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id` int unsigned NOT NULL DEFAULT '0',
  `regex` mediumtext COLLATE utf8mb4_unicode_ci,
  `fieldname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_questionregexes`
--

LOCK TABLES `glpi_plugin_formcreator_questionregexes` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questionregexes` DISABLE KEYS */;
INSERT INTO `glpi_plugin_formcreator_questionregexes` VALUES (6,31,'','regex','13d0d449-91f5039d-6787897e090429.20131104'),(7,36,'','regex','13d0d449-91f5039d-678789c98abe69.15194585'),(8,43,'','regex','13d0d449-91f5039d-67878af594e281.58229304'),(9,44,'','regex','13d0d449-91f5039d-67878b0d582532.26060669');
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questionregexes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_issues`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_issues` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `items_id` int unsigned NOT NULL DEFAULT '0',
  `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `requester_id` int unsigned NOT NULL DEFAULT '0',
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `users_id_recipient` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `entities_id` (`entities_id`),
  KEY `requester_id` (`requester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_issues`
--

LOCK TABLES `glpi_plugin_formcreator_issues` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_questionranges`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_questionranges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_questionranges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id` int unsigned NOT NULL DEFAULT '0',
  `range_min` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `range_max` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fieldname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_questionranges`
--

LOCK TABLES `glpi_plugin_formcreator_questionranges` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questionranges` DISABLE KEYS */;
INSERT INTO `glpi_plugin_formcreator_questionranges` VALUES (8,24,'0','0','range','13d0d449-91f5039d-678783e61a96b8.02903405'),(9,31,'0','0','range','13d0d449-91f5039d-6787897e0a17c1.77906814'),(10,36,'0','0','range','13d0d449-91f5039d-678789c98b8709.58629514'),(11,38,'0','0','range','13d0d449-91f5039d-67878a2d930652.29485037'),(12,43,'0','0','range','13d0d449-91f5039d-67878af5958246.44434574'),(13,44,'0','0','range','13d0d449-91f5039d-67878b0d58e087.45218338');
/*!40000 ALTER TABLE `glpi_plugin_formcreator_questionranges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_users`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_forms_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL,
  `users_id` int unsigned NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_users`
--

LOCK TABLES `glpi_plugin_formcreator_forms_users` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_forms_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targetchanges`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targetchanges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_targetchanges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `target_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `changetemplates_id` int unsigned NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `impactcontent` longtext COLLATE utf8mb4_unicode_ci,
  `controlistcontent` longtext COLLATE utf8mb4_unicode_ci,
  `rolloutplancontent` longtext COLLATE utf8mb4_unicode_ci,
  `backoutplancontent` longtext COLLATE utf8mb4_unicode_ci,
  `checklistcontent` longtext COLLATE utf8mb4_unicode_ci,
  `due_date_rule` int NOT NULL DEFAULT '1',
  `due_date_question` int unsigned NOT NULL DEFAULT '0',
  `due_date_value` tinyint DEFAULT NULL,
  `due_date_period` int NOT NULL DEFAULT '0',
  `urgency_rule` int NOT NULL DEFAULT '1',
  `urgency_question` int unsigned NOT NULL DEFAULT '0',
  `validation_followup` tinyint(1) NOT NULL DEFAULT '1',
  `destination_entity` int NOT NULL DEFAULT '1',
  `destination_entity_value` int unsigned NOT NULL DEFAULT '0',
  `tag_type` int NOT NULL DEFAULT '1',
  `tag_questions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tag_specifics` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category_rule` int NOT NULL DEFAULT '1',
  `category_question` int unsigned NOT NULL DEFAULT '0',
  `commonitil_validation_rule` int NOT NULL DEFAULT '1',
  `commonitil_validation_question` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_rule` int NOT NULL DEFAULT '1',
  `sla_rule` int NOT NULL DEFAULT '1',
  `sla_question_tto` int unsigned NOT NULL DEFAULT '0',
  `sla_question_ttr` int unsigned NOT NULL DEFAULT '0',
  `ola_rule` int NOT NULL DEFAULT '1',
  `ola_question_tto` int unsigned NOT NULL DEFAULT '0',
  `ola_question_ttr` int unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_targetchanges`
--

LOCK TABLES `glpi_plugin_formcreator_targetchanges` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targetchanges` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targetchanges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targets_actors`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targets_actors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_formcreator_targets_actors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `items_id` int unsigned NOT NULL DEFAULT '0',
  `actor_role` int NOT NULL DEFAULT '1',
  `actor_type` int NOT NULL DEFAULT '1',
  `actor_value` int unsigned NOT NULL DEFAULT '0',
  `use_notification` tinyint(1) NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_formcreator_targets_actors`
--

LOCK TABLES `glpi_plugin_formcreator_targets_actors` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targets_actors` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_formcreator_targets_actors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-01-20 15:12:11
