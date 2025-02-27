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

--
-- Table structure for table `glpi_plugin_formcreator_categories`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_categories`;
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

--
-- Dumping data for table `glpi_plugin_formcreator_categories`
--

LOCK TABLES `glpi_plugin_formcreator_categories` WRITE;
INSERT INTO `glpi_plugin_formcreator_categories` VALUES (1,'My test form category','','Root form categorie > My test form category',3,2,'{\"1\":1}','{\"3\":3}',0),(3,'Root form category','','Root form categorie',0,1,NULL,'[]',0);
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_questions`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_questions`;
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

--
-- Dumping data for table `glpi_plugin_formcreator_questions`
--

LOCK TABLES `glpi_plugin_formcreator_questions` WRITE;
INSERT INTO `glpi_plugin_formcreator_questions` VALUES (22,'Test form migration for questions - Actor',11,'actor',0,0,'[2]','',NULL,'',0,2,2,1,'13d0d449-91f5039d-67877fbc44eef4.16672745'),(23,'Test form migration for questions - Additional fields',11,'fields',0,0,NULL,'','{\"dropdown_fields_field\":\"gzfllfield\",\"blocks_field\":\"4\"}','',0,0,2,1,'13d0d449-91f5039d-678783beb1b4e5.64512762'),(24,'Test form migration for questions - Checkboxes',11,'checkboxes',0,0,'[\"Option 2\",\"Option 5\"]','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\",\"Option 5\",\"Option 6\",\"Option 7\"]','',1,0,1,1,'13d0d449-91f5039d-678783e6187d37.70279252'),(25,'Test form migration for questions - Date',11,'date',0,0,'2025-01-29','',NULL,'',1,1,1,1,'13d0d449-91f5039d-678783fbef07e7.87750991'),(26,'Test form migration for questions - Date and time',11,'datetime',0,0,'2025-01-29 12:00:00','',NULL,'',1,2,1,1,'13d0d449-91f5039d-6787840a131039.05969649'),(27,'Test form migration for questions - Description',11,'description',0,0,NULL,'',NULL,'&#60;p&#62;This is a description question type&#60;/p&#62;',1,3,1,1,'13d0d449-91f5039d-678784229b82d7.42642948'),(28,'Test form migration for questions - Dropdown',11,'dropdown',0,0,'1','Location','{\"show_tree_depth\":\"0\",\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\",\"entity_restrict\":\"2\"}','',2,0,4,1,'13d0d449-91f5039d-67878445d795b6.07616686'),(29,'Test form migration for questions - Email',11,'email',0,0,'test@test.fr','','','',3,0,4,1,'13d0d449-91f5039d-6787845a8c0550.23541628'),(30,'Test form migration for questions - File',11,'file',0,0,NULL,'',NULL,'',4,0,4,1,'13d0d449-91f5039d-678789701851a0.76378229'),(31,'Test form migration for questions - Float',11,'float',0,0,'8,45','','','',5,0,4,1,'13d0d449-91f5039d-6787897e0703b1.90417706'),(32,'Test form migration for questions - Glpi Object',11,'glpiselect',0,0,'1','Computer','{\"entity_restrict\":\"2\"}','',6,0,4,1,'13d0d449-91f5039d-67878998584a27.11882972'),(33,'Test form migration for questions - Hidden field',11,'hidden',0,0,'test hidden field','',NULL,'',7,0,4,1,'13d0d449-91f5039d-678789a5a0f964.26980024'),(34,'Test form migration for questions - Hostname',11,'hostname',0,0,NULL,'',NULL,'',8,0,4,1,'13d0d449-91f5039d-678789b34997b1.33623123'),(35,'Test form migration for questions - IP Addresse',11,'ip',0,0,NULL,'',NULL,'',9,0,4,1,'13d0d449-91f5039d-678789c0104a75.96918904'),(36,'Test form migration for questions - Integer',11,'integer',0,0,'78','','','',10,0,4,1,'13d0d449-91f5039d-678789c988a473.24972235'),(37,'Test form migration for questions - LDAP Select',11,'ldapselect',0,0,NULL,'','{\"ldap_auth\":\"1\",\"ldap_attribute\":\"12\",\"ldap_filter\":\"(& (uid=*) )\"}','',11,0,4,1,'13d0d449-91f5039d-67878a05d3c326.35829700'),(38,'Test form migration for questions - Multiselect',11,'multiselect',0,0,'[\"Option 3\",\"Option 4\"]','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\",\"Option 5\"]','',12,0,4,1,'13d0d449-91f5039d-67878a2d90cea2.48530344'),(39,'Test form migration for questions - Radios',11,'radios',0,0,'Option 2','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\"]','',13,0,4,1,'13d0d449-91f5039d-67878a48cf71b3.62293600'),(40,'Test form migration for questions - Request type',11,'requesttype',0,0,'2','',NULL,'',14,0,4,1,'13d0d449-91f5039d-67878a53ac2c51.37272675'),(41,'Test form migration for questions - Select',11,'select',0,0,'Option 1','','[\"Option 1\",\"Option 2\"]','',15,0,4,1,'13d0d449-91f5039d-67878a69d3cf02.76840759'),(42,'Test form migration for questions - Tags',11,'tag',0,0,NULL,'',NULL,'',18,0,4,1,'13d0d449-91f5039d-67878ae3629375.79074002'),(43,'Test form migration for questions - Text',11,'text',0,0,'Test default text value','',NULL,'',17,0,4,1,'13d0d449-91f5039d-67878af59157f2.25429606'),(44,'Test form migration for questions - Textarea',11,'textarea',0,0,'&#60;p&#62;Test &#60;span style=\"color: #2dc26b; background-color: #843fa1;\"&#62;default value&#60;/span&#62; text &#60;strong&#62;area&#60;/strong&#62;&#60;/p&#62;','',NULL,'',16,0,4,1,'13d0d449-91f5039d-67878b0d55b865.54398926'),(45,'Test form migration for questions - Time',11,'time',0,0,'12:00:00','',NULL,'',19,0,4,1,'13d0d449-91f5039d-67878b1b2b2b86.77749904'),(46,'Test form migration for questions - Urgency',11,'urgency',0,0,'2','',NULL,'',20,0,4,1,'13d0d449-91f5039d-67878b28b26020.93425219');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_sections`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_sections`;
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

--
-- Dumping data for table `glpi_plugin_formcreator_sections`
--

LOCK TABLES `glpi_plugin_formcreator_sections` WRITE;
INSERT INTO `glpi_plugin_formcreator_sections` VALUES (7,'Section',5,1,1,'13d0d449-91f5039d-67865f9ec21e00.64800503'),(8,'Section',4,1,1,'13d0d449-91f5039d-67868bb732f521.89613959'),(9,'First section',6,1,1,'13d0d449-91f5039d-67868bda15b5e4.42395948'),(10,'Second section',6,2,1,'13d0d449-91f5039d-67868bdf03a686.18185201'),(11,'Section',7,1,1,'13d0d449-91f5039d-67877f5c52efd1.07463389');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms`;
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

--
-- Dumping data for table `glpi_plugin_formcreator_forms`
--

LOCK TABLES `glpi_plugin_formcreator_forms` WRITE;
INSERT INTO `glpi_plugin_formcreator_forms` VALUES (4,'Test form migration for basic properties',0,0,'0','#999999','#e7e7e7',1,'','',0,1,'',0,0,0,0,0,0,1,'Test form migration for basic properties',1,'13d0d449-91f5039d-678638f86ff479.66501068'),(5,'Test form migration for basic properties with form category',0,0,'0','#999999','#e7e7e7',1,'','',1,1,'',0,0,0,0,0,0,1,'Test form migration for basic properties with form category',1,'13d0d449-91f5039d-67865f9ebd9f58.29603668'),(6,'Test form migration for sections',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for sections',1,'13d0d449-91f5039d-67868bc224e124.20535500'),(7,'Test form migration for questions',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for questions',1,'13d0d449-91f5039d-67877f5c4ee3c3.90813653');
UNLOCK TABLES;

-- Dump completed on 2025-01-20 15:12:11
