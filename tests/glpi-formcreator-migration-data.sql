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
INSERT INTO `glpi_plugin_formcreator_questions` VALUES (22,'Test form migration for questions - Actor',11,'actor',0,0,'[2]','',NULL,'',0,2,2,1,'13d0d449-91f5039d-67877fbc44eef4.16672745'),(23,'Test form migration for questions - Additional fields',11,'fields',0,0,NULL,'','{\"dropdown_fields_field\":\"gzfllfield\",\"blocks_field\":\"4\"}','',0,0,2,1,'13d0d449-91f5039d-678783beb1b4e5.64512762'),(24,'Test form migration for questions - Checkboxes',11,'checkboxes',0,0,'[\"Option 2\",\"Option 5\"]','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\",\"Option 5\",\"Option 6\",\"Option 7\"]','',1,0,1,1,'13d0d449-91f5039d-678783e6187d37.70279252'),(25,'Test form migration for questions - Date',11,'date',0,0,'2025-01-29','',NULL,'',1,1,1,1,'13d0d449-91f5039d-678783fbef07e7.87750991'),(26,'Test form migration for questions - Date and time',11,'datetime',0,0,'2025-01-29 12:00:00','',NULL,'',1,2,1,1,'13d0d449-91f5039d-6787840a131039.05969649'),(27,'Test form migration for questions - Description',11,'description',0,0,NULL,'',NULL,'&#60;p&#62;This is a description question type&#60;/p&#62;',1,3,1,1,'13d0d449-91f5039d-678784229b82d7.42642948'),(28,'Test form migration for questions - Dropdown',11,'dropdown',0,0,'1','Location','{\"show_tree_depth\":\"0\",\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\",\"entity_restrict\":\"2\"}','',2,0,4,1,'13d0d449-91f5039d-67878445d795b6.07616686'),(29,'Test form migration for questions - Email',11,'email',0,0,'test@test.fr','','','',3,0,4,1,'13d0d449-91f5039d-6787845a8c0550.23541628'),(30,'Test form migration for questions - File',11,'file',0,0,NULL,'',NULL,'',4,0,4,1,'13d0d449-91f5039d-678789701851a0.76378229'),(31,'Test form migration for questions - Float',11,'float',0,0,'8,45','','','',5,0,4,1,'13d0d449-91f5039d-6787897e0703b1.90417706'),(32,'Test form migration for questions - Glpi Object',11,'glpiselect',0,0,'1','Computer','{\"entity_restrict\":\"2\"}','',6,0,4,1,'13d0d449-91f5039d-67878998584a27.11882972'),(33,'Test form migration for questions - Hidden field',11,'hidden',0,0,'test hidden field','',NULL,'',7,0,4,1,'13d0d449-91f5039d-678789a5a0f964.26980024'),(34,'Test form migration for questions - Hostname',11,'hostname',0,0,NULL,'',NULL,'',8,0,4,1,'13d0d449-91f5039d-678789b34997b1.33623123'),(35,'Test form migration for questions - IP Addresse',11,'ip',0,0,NULL,'',NULL,'',9,0,4,1,'13d0d449-91f5039d-678789c0104a75.96918904'),(36,'Test form migration for questions - Integer',11,'integer',0,0,'78','','','',10,0,4,1,'13d0d449-91f5039d-678789c988a473.24972235'),(37,'Test form migration for questions - LDAP Select',11,'ldapselect',0,0,NULL,'','{\"ldap_auth\":\"1\",\"ldap_attribute\":\"12\",\"ldap_filter\":\"(& (uid=*) )\"}','',11,0,4,1,'13d0d449-91f5039d-67878a05d3c326.35829700'),(38,'Test form migration for questions - Multiselect',11,'multiselect',0,0,'[\"Option 3\",\"Option 4\"]','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\",\"Option 5\"]','',12,0,4,1,'13d0d449-91f5039d-67878a2d90cea2.48530344'),(39,'Test form migration for questions - Radios',11,'radios',0,0,'Option 2','','[\"Option 1\",\"Option 2\",\"Option 3\",\"Option 4\"]','',13,0,4,1,'13d0d449-91f5039d-67878a48cf71b3.62293600'),(40,'Test form migration for questions - Request type',11,'requesttype',0,0,'2','',NULL,'',14,0,4,1,'13d0d449-91f5039d-67878a53ac2c51.37272675'),(41,'Test form migration for questions - Select',11,'select',0,0,'Option 1','','[\"Option 1\",\"Option 2\"]','',15,0,4,1,'13d0d449-91f5039d-67878a69d3cf02.76840759'),(42,'Test form migration for questions - Tags',11,'tag',0,0,NULL,'',NULL,'',18,0,4,1,'13d0d449-91f5039d-67878ae3629375.79074002'),(43,'Test form migration for questions - Text',11,'text',0,0,'Test default text value','',NULL,'',17,0,4,1,'13d0d449-91f5039d-67878af59157f2.25429606'),(44,'Test form migration for questions - Textarea',11,'textarea',0,0,'&#60;p&#62;Test &#60;span style=\"color: #2dc26b; background-color: #843fa1;\"&#62;default value&#60;/span&#62; text &#60;strong&#62;area&#60;/strong&#62;&#60;/p&#62;','',NULL,'',16,0,4,1,'13d0d449-91f5039d-67878b0d55b865.54398926'),(45,'Test form migration for questions - Time',11,'time',0,0,'12:00:00','',NULL,'',19,0,4,1,'13d0d449-91f5039d-67878b1b2b2b86.77749904'),(46,'Test form migration for questions - Urgency',11,'urgency',0,0,'2','',NULL,'',20,0,4,1,'13d0d449-91f5039d-67878b28b26020.93425219'),(71,'Entity question',24,'glpiselect',0,0,'0','Entity','{\"entity_restrict\":2,\"show_tree_depth\":0,\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\"}','',0,0,4,1,'13d0d449-91f5039d-679212e38ccbf0.40211925'),(72,'ITILCategory question',24,'dropdown',0,0,'0','ITILCategory','{\"show_ticket_categories\":\"both\",\"show_tree_depth\":\"0\",\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\",\"entity_restrict\":\"2\"}','',1,0,4,1,'13d0d449-91f5039d-6792230a623fb8.67596154'),(73,'Location question',24,'dropdown',0,0,'0','Location','{\"show_tree_depth\":\"0\",\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\",\"entity_restrict\":\"2\"}','',2,0,4,1,'13d0d449-91f5039d-67925a9f018176.64665868'),(74,'Computer question',24,'glpiselect',0,0,'0','Computer','{\"entity_restrict\":\"2\"}','',3,0,4,1,'13d0d449-91f5039d-67925ab1aa5214.25624797'),(75,'User question',24,'glpiselect',0,0,'0','User','{\"entity_restrict\":\"2\"}','',4,0,4,1,'13d0d449-91f5039d-67925e1f0f3969.86290550'),(76,'Group question',24,'glpiselect',0,0,'0','Group','{\"entity_restrict\":\"2\",\"show_tree_depth\":0,\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\"}','',5,0,4,1,'13d0d449-91f5039d-67925e2ab7a436.44726810'),(77,'Actor question',24,'actor',0,0,'[]','',NULL,'',6,0,4,1,'13d0d449-91f5039d-6797642b8a7a44.16023597'),(78,'Group object question',24,'glpiselect',0,0,'0','Group','{\"entity_restrict\":\"2\",\"show_tree_depth\":0,\"show_tree_root\":\"0\",\"selectable_tree_root\":\"0\"}','',7,0,4,1,'13d0d449-91f5039d-679764ee4eaed3.24664602'),(79,'Urgency question',24,'urgency',0,0,'5','',NULL,'',8,0,4,1,'13d0d449-91f5039d-6798a31312aa69.73798465'),(80,'Request type question',24,'requesttype',0,0,'0','',NULL,'',9,0,4,1,'13d0d449-91f5039d-6798a93248fc10.26087226'),(82,'First question',26,'text',0,0,'Default value for first question','',NULL,'&#60;p&#62;&#60;strong&#62;Test description for &#60;/strong&#62;first&#60;strong&#62; &#60;span style="color: #b96ad9;"&#62;question&#60;/span&#62;&#60;/strong&#62;&#60;/p&#62;',0,0,4,1,'18b24cbd-91f5039d-67e3d9cb39aaf6.52249480'),(83,'Second question',26,'checkboxes',0,0,'','','["First option","Second option","Third option"]','',1,0,4,1,'18b24cbd-91f5039d-67e3da4d7602c7.07251559'),(84,'Description question',27,'description',0,0,NULL,'',NULL,'&#60;p&#62;Description &#60;span style="background-color: #e03e2d; color: #ffffff;"&#62;content&#60;/span&#62;&#60;/p&#62;',0,0,4,1,'18b24cbd-91f5039d-67e3da77611dc6.37923986');
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
INSERT INTO `glpi_plugin_formcreator_sections` VALUES (7,'Section',5,1,1,'13d0d449-91f5039d-67865f9ec21e00.64800503'),(8,'Section',4,1,1,'13d0d449-91f5039d-67868bb732f521.89613959'),(9,'First section',6,1,1,'13d0d449-91f5039d-67868bda15b5e4.42395948'),(10,'Second section',6,2,1,'13d0d449-91f5039d-67868bdf03a686.18185201'),(11,'Section',7,1,1,'13d0d449-91f5039d-67877f5c52efd1.07463389'),(24,'Section',17,1,1,'13d0d449-91f5039d-679209f7ed59c9.36010248'),(26,'First section',19,1,1,'18b24cbd-91f5039d-67e3d93755c026.80718410'),(27,'Second section',19,2,1,'18b24cbd-91f5039d-67e3da533b8809.35021958');
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
INSERT INTO `glpi_plugin_formcreator_forms` VALUES (4,'Test form migration for basic properties',0,0,'0','#999999','#e7e7e7',1,'','',0,1,'',0,0,0,0,0,0,1,'Test form migration for basic properties',1,'13d0d449-91f5039d-678638f86ff479.66501068'),(5,'Test form migration for basic properties with form category',0,0,'0','#999999','#e7e7e7',1,'','',1,1,'',0,0,0,0,0,0,1,'Test form migration for basic properties with form category',1,'13d0d449-91f5039d-67865f9ebd9f58.29603668'),(6,'Test form migration for sections',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for sections',1,'13d0d449-91f5039d-67868bc224e124.20535500'),(7,'Test form migration for questions',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for questions',1,'13d0d449-91f5039d-67877f5c4ee3c3.90813653'),(8,'Test form migration for access types with public access',0,0,'0','#999999','#e7e7e7',0,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for access types',1,'13d0d449-91f5039d-678f630f4a4737.71277458'),(9,'Test form migration for access types with private access',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for access types with private access',1,'13d0d449-91f5039d-678f634493d8b7.87824440'),(10,'Test form migration for access types with restricted access',0,0,'0','#999999','#e7e7e7',2,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for access types with restricted access',1,'13d0d449-91f5039d-678f63553754a9.65767968'),(17,'Test form migration for targets',0,0,'0','#999999','#e7e7e7',1,'','',0,0,'',0,0,0,0,0,0,1,'Test form migration for target problem',1,'13d0d449-91f5039d-679209f7eaae69.42097688'),(19,'Test form migration for translations',0,0,'0','#999999','#e7e7e7',1,'This is a description to test the translation','&#60;p&#62;This is a header to test the translation&#60;/p&#62;',0,0,'',0,0,0,0,0,0,1,'Test form migration for translations',1,'18b24cbd-91f5039d-67e3d8f1d06864.47103399');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targettickets`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targettickets`;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_targettickets`
--

LOCK TABLES `glpi_plugin_formcreator_targettickets` WRITE;
INSERT INTO `glpi_plugin_formcreator_targettickets` VALUES (12,'Test form migration for target ticket',17,'Test form migration for target ticket',1,7,1,1,0,'##FULLFORM##',0,0,-30,1,1,0,1,1,0,1,'','',1,0,1,0,1,0,1,NULL,1,1,0,0,1,0,0,'13d0d449-91f5039d-679212b1bced31.18353412');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targets_actors`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targets_actors`;
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_targets_actors`
--

LOCK TABLES `glpi_plugin_formcreator_targets_actors` WRITE;
INSERT INTO `glpi_plugin_formcreator_targets_actors` VALUES (30,'PluginFormcreatorTargetProblem',3,1,1,0,1,'13d0d449-91f5039d-67921b2d36a0d5.14351374'),(31,'PluginFormcreatorTargetProblem',3,2,2,0,1,'13d0d449-91f5039d-67921b2d374554.13569809'),(32,'PluginFormcreatorTargetChange',4,1,1,0,1,'13d0d449-91f5039d-67921b34073058.05090354'),(33,'PluginFormcreatorTargetChange',4,2,2,0,1,'13d0d449-91f5039d-67921b340843a4.52502084');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targetchanges`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targetchanges`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_targetchanges`
--

LOCK TABLES `glpi_plugin_formcreator_targetchanges` WRITE;
INSERT INTO `glpi_plugin_formcreator_targetchanges` VALUES (4,'Test form migration for target change',17,'Test form migration for target change',0,'##FULLFORM##',NULL,NULL,NULL,NULL,NULL,1,0,NULL,0,1,0,1,1,0,1,'','',1,0,1,NULL,1,1,0,0,1,0,0,'13d0d449-91f5039d-67921b340628a4.38268427');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_targetproblems`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_targetproblems`;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_targetproblems`
--

LOCK TABLES `glpi_plugin_formcreator_targetproblems` WRITE;
INSERT INTO `glpi_plugin_formcreator_targetproblems` VALUES (3,'Test form migration for target problem',17,'Test form migration for target problem',0,'##FULLFORM##',NULL,NULL,NULL,1,0,1,0,1,'','',1,0,1,'13d0d449-91f5039d-67921b2d356a12.37566021');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_users`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_users`;
CREATE TABLE `glpi_plugin_formcreator_forms_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL,
  `users_id` int unsigned NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`users_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_users`
--

LOCK TABLES `glpi_plugin_formcreator_forms_users` WRITE;
INSERT INTO `glpi_plugin_formcreator_forms_users` VALUES (1,10,2,'13d0d449-91f5039d-678f638f475179.94375485');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_groups`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_groups`;
CREATE TABLE `glpi_plugin_formcreator_forms_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL,
  `groups_id` int unsigned NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_groups`
--

LOCK TABLES `glpi_plugin_formcreator_forms_groups` WRITE;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_profiles`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_profiles`;
CREATE TABLE `glpi_plugin_formcreator_forms_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `profiles_id` int unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`profiles_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_profiles`
--

LOCK TABLES `glpi_plugin_formcreator_forms_profiles` WRITE;
INSERT INTO `glpi_plugin_formcreator_forms_profiles` VALUES (1,10,1,'13d0d449-91f5039d-678f638f4973b2.97182859'),(2,10,4,'13d0d449-91f5039d-678f638f4b51a8.63447592');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_items_targettickets`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_items_targettickets`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_items_targettickets`
--

LOCK TABLES `glpi_plugin_formcreator_items_targettickets` WRITE;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_profiles`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_profiles`;
CREATE TABLE `glpi_plugin_formcreator_forms_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `profiles_id` int unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`profiles_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_profiles`
--

LOCK TABLES `glpi_plugin_formcreator_forms_profiles` WRITE;
INSERT INTO `glpi_plugin_formcreator_forms_profiles` VALUES (1,10,1,'13d0d449-91f5039d-678f638f4973b2.97182859'),(2,10,4,'13d0d449-91f5039d-678f638f4b51a8.63447592');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_groups`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_groups`;
CREATE TABLE `glpi_plugin_formcreator_forms_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL,
  `groups_id` int unsigned NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_groups`
--

LOCK TABLES `glpi_plugin_formcreator_forms_groups` WRITE;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_users`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_users`;
CREATE TABLE `glpi_plugin_formcreator_forms_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL,
  `users_id` int unsigned NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`users_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_users`
--

LOCK TABLES `glpi_plugin_formcreator_forms_users` WRITE;
INSERT INTO `glpi_plugin_formcreator_forms_users` VALUES (1,10,2,'13d0d449-91f5039d-678f638f475179.94375485');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_forms_languages`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_forms_languages`;
CREATE TABLE `glpi_plugin_formcreator_forms_languages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `glpi_plugin_formcreator_forms_languages`
--

LOCK TABLES `glpi_plugin_formcreator_forms_languages` WRITE;
INSERT INTO `glpi_plugin_formcreator_forms_languages` VALUES (2,19,'fr_FR','','18b24cbd-91f5039d-67e3dc2daab018.88018762');
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_formcreator_conditions`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_conditions`;
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
) ENGINE=InnoDB AUTO_INCREMENT=825 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Table structure for table `glpi_plugin_formcreator_questionranges`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_questionranges`;
CREATE TABLE `glpi_plugin_formcreator_questionranges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id` int unsigned NOT NULL DEFAULT '0',
  `range_min` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `range_max` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fieldname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB AUTO_INCREMENT=304 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Table structure for table `glpi_plugin_formcreator_questionregexes`
--

DROP TABLE IF EXISTS `glpi_plugin_formcreator_questionregexes`;
CREATE TABLE `glpi_plugin_formcreator_questionregexes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id` int unsigned NOT NULL DEFAULT '0',
  `regex` mediumtext COLLATE utf8mb4_unicode_ci,
  `fieldname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB AUTO_INCREMENT=297 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_formcreator_entityconfigs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int(10) unsigned NOT NULL DEFAULT 0,
  `replace_helpdesk` int(11) NOT NULL DEFAULT -2,
  `default_form_list_mode` int(11) NOT NULL DEFAULT -2,
  `sort_order` int(11) NOT NULL DEFAULT -2,
  `is_kb_separated` int(11) NOT NULL DEFAULT -2,
  `is_search_visible` int(11) NOT NULL DEFAULT -2,
  `is_dashboard_visible` int(11) NOT NULL DEFAULT -2,
  `is_header_visible` int(11) NOT NULL DEFAULT -2,
  `is_search_issue_visible` int(11) NOT NULL DEFAULT -2,
  `tile_design` int(11) NOT NULL DEFAULT -2,
  `home_page` int(11) NOT NULL DEFAULT -2,
  `is_category_visible` int(11) NOT NULL DEFAULT -2,
  `is_folded_menu` int(11) NOT NULL DEFAULT -2,
  `header` text DEFAULT NULL,
  `service_catalog_home` int(11) NOT NULL DEFAULT -2,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`entities_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC

-- Dump completed on 2025-01-21 11:41:32
