--
-- ---------------------------------------------------------------------
-- GLPI - Gestionnaire Libre de Parc Informatique
-- Copyright (C) 2015-2021 Teclib' and contributors.
--
-- http://glpi-project.org
--
-- based on GLPI - Gestionnaire Libre de Parc Informatique
-- Copyright (C) 2003-2014 by the INDEPNET Development Team.
--
-- ---------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of GLPI.
--
-- GLPI is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- GLPI is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with GLPI. If not, see <http://www.gnu.org/licenses/>.
-- ---------------------------------------------------------------------
--

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `glpi_alerts`;
CREATE TABLE `glpi_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0 COMMENT 'see define.php ALERT_* constant',
  `date` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`type`),
  KEY `type` (`type`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_apiclients`;
CREATE TABLE `glpi_apiclients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `ipv4_range_start` bigint(20) DEFAULT NULL,
  `ipv4_range_end` bigint(20) DEFAULT NULL,
  `ipv6` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `app_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `app_token_date` timestamp NULL DEFAULT NULL,
  `dolog_method` tinyint(4) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_apiclients` (`id`, `entities_id`, `is_recursive`, `name`, `date_mod`, `is_active`, `ipv4_range_start`, `ipv4_range_end`, `ipv6`, `app_token`, `app_token_date`, `dolog_method`, `comment`) VALUES
(1,	0,	1,	'full access from localhost',	NULL,	1,	2130706433,	2130706433,	'::1',	NULL,	NULL,	0,	NULL);

DROP TABLE IF EXISTS `glpi_applianceenvironments`;
CREATE TABLE `glpi_applianceenvironments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_appliances`;
CREATE TABLE `glpi_appliances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `appliancetypes_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `applianceenvironments_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `externalidentifier` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_helpdesk_visible` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`externalidentifier`),
  KEY `entities_id` (`entities_id`),
  KEY `name` (`name`),
  KEY `is_deleted` (`is_deleted`),
  KEY `appliancetypes_id` (`appliancetypes_id`),
  KEY `locations_id` (`locations_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `applianceenvironments_id` (`applianceenvironments_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id` (`groups_id`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `states_id` (`states_id`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `is_helpdesk_visible` (`is_helpdesk_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_appliances_items`;
CREATE TABLE `glpi_appliances_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appliances_id` int(11) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`appliances_id`,`items_id`,`itemtype`),
  KEY `appliances_id` (`appliances_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_appliances_items_relations`;
CREATE TABLE `glpi_appliances_items_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appliances_items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `appliances_items_id` (`appliances_items_id`),
  KEY `itemtype` (`itemtype`),
  KEY `items_id` (`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_appliancetypes`;
CREATE TABLE `glpi_appliancetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `externalidentifier` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `externalidentifier` (`externalidentifier`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_authldapreplicates`;
CREATE TABLE `glpi_authldapreplicates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authldaps_id` int(11) NOT NULL DEFAULT 0,
  `host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `port` int(11) NOT NULL DEFAULT 389,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `authldaps_id` (`authldaps_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_authldaps`;
CREATE TABLE `glpi_authldaps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `basedn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rootdn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `port` int(11) NOT NULL DEFAULT 389,
  `condition` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `login_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'uid',
  `sync_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `use_tls` tinyint(1) NOT NULL DEFAULT 0,
  `group_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_condition` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_search_type` int(11) NOT NULL DEFAULT 0,
  `group_member_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email1_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `realname_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone2_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mobile_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `use_dn` tinyint(1) NOT NULL DEFAULT 1,
  `time_offset` int(11) NOT NULL DEFAULT 0 COMMENT 'in seconds',
  `deref_option` int(11) NOT NULL DEFAULT 0,
  `title_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `category_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_condition` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `rootdn_passwd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `registration_number_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email2_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email3_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email4_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `responsible_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pagesize` int(11) NOT NULL DEFAULT 0,
  `ldap_maxlimit` int(11) NOT NULL DEFAULT 0,
  `can_support_pagesize` tinyint(1) NOT NULL DEFAULT 0,
  `picture_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `inventory_domain` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `is_default` (`is_default`),
  KEY `is_active` (`is_active`),
  KEY `date_creation` (`date_creation`),
  KEY `sync_field` (`sync_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_authmails`;
CREATE TABLE `glpi_authmails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `connect_string` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_autoupdatesystems`;
CREATE TABLE `glpi_autoupdatesystems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_blacklistedmailcontents`;
CREATE TABLE `glpi_blacklistedmailcontents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_blacklists`;
CREATE TABLE `glpi_blacklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_blacklists` (`id`, `type`, `name`, `value`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	1,	'empty IP',	'',	NULL,	NULL,	NULL),
(2,	1,	'localhost',	'127.0.0.1',	NULL,	NULL,	NULL),
(3,	1,	'zero IP',	'0.0.0.0',	NULL,	NULL,	NULL),
(4,	2,	'empty MAC',	'',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_budgets`;
CREATE TABLE `glpi_budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `budgettypes_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_recursive` (`is_recursive`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `is_template` (`is_template`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `locations_id` (`locations_id`),
  KEY `budgettypes_id` (`budgettypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_budgettypes`;
CREATE TABLE `glpi_budgettypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_businesscriticities`;
CREATE TABLE `glpi_businesscriticities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `businesscriticities_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`businesscriticities_id`,`name`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_calendars`;
CREATE TABLE `glpi_calendars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `cache_duration` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_calendars` (`id`, `name`, `entities_id`, `is_recursive`, `comment`, `date_mod`, `cache_duration`, `date_creation`) VALUES
(1,	'Default',	0,	1,	'Default calendar',	NULL,	'[0,43200,43200,43200,43200,43200,0]',	NULL);

DROP TABLE IF EXISTS `glpi_calendarsegments`;
CREATE TABLE `glpi_calendarsegments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calendars_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `day` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'numer of the day based on date(w)',
  `begin` time DEFAULT NULL,
  `end` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendars_id` (`calendars_id`),
  KEY `day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_calendarsegments` (`id`, `calendars_id`, `entities_id`, `is_recursive`, `day`, `begin`, `end`) VALUES
(1,	1,	0,	0,	1,	'08:00:00',	'20:00:00'),
(2,	1,	0,	0,	2,	'08:00:00',	'20:00:00'),
(3,	1,	0,	0,	3,	'08:00:00',	'20:00:00'),
(4,	1,	0,	0,	4,	'08:00:00',	'20:00:00'),
(5,	1,	0,	0,	5,	'08:00:00',	'20:00:00');

DROP TABLE IF EXISTS `glpi_calendars_holidays`;
CREATE TABLE `glpi_calendars_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calendars_id` int(11) NOT NULL DEFAULT 0,
  `holidays_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`calendars_id`,`holidays_id`),
  KEY `holidays_id` (`holidays_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_cartridgeitems`;
CREATE TABLE `glpi_cartridgeitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ref` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `cartridgeitemtypes_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `alarm_threshold` int(11) NOT NULL DEFAULT 10,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `cartridgeitemtypes_id` (`cartridgeitemtypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `alarm_threshold` (`alarm_threshold`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_cartridgeitems_printermodels`;
CREATE TABLE `glpi_cartridgeitems_printermodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cartridgeitems_id` int(11) NOT NULL DEFAULT 0,
  `printermodels_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`printermodels_id`,`cartridgeitems_id`),
  KEY `cartridgeitems_id` (`cartridgeitems_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_cartridgeitemtypes`;
CREATE TABLE `glpi_cartridgeitemtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_cartridges`;
CREATE TABLE `glpi_cartridges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `cartridgeitems_id` int(11) NOT NULL DEFAULT 0,
  `printers_id` int(11) NOT NULL DEFAULT 0,
  `date_in` date DEFAULT NULL,
  `date_use` date DEFAULT NULL,
  `date_out` date DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cartridgeitems_id` (`cartridgeitems_id`),
  KEY `printers_id` (`printers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_certificates`;
CREATE TABLE `glpi_certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `certificatetypes_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to glpi_certificatetypes (id)',
  `dns_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dns_suffix` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to glpi_users (id)',
  `groups_id_tech` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to glpi_groups (id)',
  `locations_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to glpi_locations (id)',
  `manufacturers_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to glpi_manufacturers (id)',
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `is_autosign` tinyint(1) NOT NULL DEFAULT 0,
  `date_expiration` date DEFAULT NULL,
  `states_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to states (id)',
  `command` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `certificate_request` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `certificate_item` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_template` (`is_template`),
  KEY `is_deleted` (`is_deleted`),
  KEY `certificatetypes_id` (`certificatetypes_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `states_id` (`states_id`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_certificates_items`;
CREATE TABLE `glpi_certificates_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificates_id` int(11) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to various tables, according to itemtype (id)',
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`certificates_id`,`itemtype`,`items_id`),
  KEY `device` (`items_id`,`itemtype`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_certificatetypes`;
CREATE TABLE `glpi_certificatetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `name` (`name`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changecosts`;
CREATE TABLE `glpi_changecosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `cost_time` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cost_fixed` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cost_material` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `budgets_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `changes_id` (`changes_id`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `budgets_id` (`budgets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changes`;
CREATE TABLE `glpi_changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `solvedate` timestamp NULL DEFAULT NULL,
  `closedate` timestamp NULL DEFAULT NULL,
  `time_to_resolve` timestamp NULL DEFAULT NULL,
  `users_id_recipient` int(11) NOT NULL DEFAULT 0,
  `users_id_lastupdater` int(11) NOT NULL DEFAULT 0,
  `urgency` int(11) NOT NULL DEFAULT 1,
  `impact` int(11) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 1,
  `itilcategories_id` int(11) NOT NULL DEFAULT 0,
  `impactcontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `controlistcontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `rolloutplancontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `backoutplancontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `checklistcontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `global_validation` int(11) NOT NULL DEFAULT 1,
  `validation_percent` int(11) NOT NULL DEFAULT 0,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `begin_waiting_date` timestamp NULL DEFAULT NULL,
  `waiting_duration` int(11) NOT NULL DEFAULT 0,
  `close_delay_stat` int(11) NOT NULL DEFAULT 0,
  `solve_delay_stat` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date` (`date`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `date_mod` (`date_mod`),
  KEY `itilcategories_id` (`itilcategories_id`),
  KEY `users_id_recipient` (`users_id_recipient`),
  KEY `solvedate` (`solvedate`),
  KEY `urgency` (`urgency`),
  KEY `impact` (`impact`),
  KEY `time_to_resolve` (`time_to_resolve`),
  KEY `global_validation` (`global_validation`),
  KEY `users_id_lastupdater` (`users_id_lastupdater`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changes_groups`;
CREATE TABLE `glpi_changes_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changes_id`,`type`,`groups_id`),
  KEY `group` (`groups_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changes_items`;
CREATE TABLE `glpi_changes_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changes_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changes_problems`;
CREATE TABLE `glpi_changes_problems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changes_id`,`problems_id`),
  KEY `problems_id` (`problems_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changes_suppliers`;
CREATE TABLE `glpi_changes_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `suppliers_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `use_notification` tinyint(1) NOT NULL DEFAULT 0,
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changes_id`,`type`,`suppliers_id`),
  KEY `group` (`suppliers_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changes_tickets`;
CREATE TABLE `glpi_changes_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changes_id`,`tickets_id`),
  KEY `tickets_id` (`tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changes_users`;
CREATE TABLE `glpi_changes_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `use_notification` tinyint(1) NOT NULL DEFAULT 0,
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changes_id`,`type`,`users_id`,`alternative_email`),
  KEY `user` (`users_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changetasks`;
CREATE TABLE `glpi_changetasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `taskcategories_id` int(11) NOT NULL DEFAULT 0,
  `state` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `begin` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `users_id_editor` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `tasktemplates_id` int(11) NOT NULL DEFAULT 0,
  `timeline_position` tinyint(1) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `changes_id` (`changes_id`),
  KEY `state` (`state`),
  KEY `users_id` (`users_id`),
  KEY `users_id_editor` (`users_id_editor`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date` (`date`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `taskcategories_id` (`taskcategories_id`),
  KEY `tasktemplates_id` (`tasktemplates_id`),
  KEY `is_private` (`is_private`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changetemplatehiddenfields`;
CREATE TABLE `glpi_changetemplatehiddenfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changetemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changetemplates_id`,`num`),
  KEY `changetemplates_id` (`changetemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changetemplatemandatoryfields`;
CREATE TABLE `glpi_changetemplatemandatoryfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changetemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changetemplates_id`,`num`),
  KEY `changetemplates_id` (`changetemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_changetemplatemandatoryfields` (`id`, `changetemplates_id`, `num`) VALUES
(1,	1,	21);

DROP TABLE IF EXISTS `glpi_changetemplatepredefinedfields`;
CREATE TABLE `glpi_changetemplatepredefinedfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changetemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  `value` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `changetemplates_id` (`changetemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_changetemplates`;
CREATE TABLE `glpi_changetemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_changetemplates` (`id`, `name`, `entities_id`, `is_recursive`, `comment`) VALUES
(1,	'Default',	0,	1,	NULL);

DROP TABLE IF EXISTS `glpi_changevalidations`;
CREATE TABLE `glpi_changevalidations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `changes_id` int(11) NOT NULL DEFAULT 0,
  `users_id_validate` int(11) NOT NULL DEFAULT 0,
  `comment_submission` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment_validation` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 2,
  `submission_date` timestamp NULL DEFAULT NULL,
  `validation_date` timestamp NULL DEFAULT NULL,
  `timeline_position` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `users_id` (`users_id`),
  KEY `users_id_validate` (`users_id_validate`),
  KEY `changes_id` (`changes_id`),
  KEY `submission_date` (`submission_date`),
  KEY `validation_date` (`validation_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_clusters`;
CREATE TABLE `glpi_clusters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to states (id)',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `clustertypes_id` int(11) NOT NULL DEFAULT 0,
  `autoupdatesystems_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `group_id_tech` (`groups_id_tech`),
  KEY `is_deleted` (`is_deleted`),
  KEY `states_id` (`states_id`),
  KEY `clustertypes_id` (`clustertypes_id`),
  KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_clustertypes`;
CREATE TABLE `glpi_clustertypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_computerantiviruses`;
CREATE TABLE `glpi_computerantiviruses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `antivirus_version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `signature_version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_uptodate` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_expiration` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `antivirus_version` (`antivirus_version`),
  KEY `signature_version` (`signature_version`),
  KEY `is_active` (`is_active`),
  KEY `is_uptodate` (`is_uptodate`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `is_deleted` (`is_deleted`),
  KEY `computers_id` (`computers_id`),
  KEY `date_expiration` (`date_expiration`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_computermodels`;
CREATE TABLE `glpi_computermodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `required_units` int(11) NOT NULL DEFAULT 1,
  `depth` float NOT NULL DEFAULT 1,
  `power_connections` int(11) NOT NULL DEFAULT 0,
  `power_consumption` int(11) NOT NULL DEFAULT 0,
  `is_half_rack` tinyint(1) NOT NULL DEFAULT 0,
  `picture_front` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture_rear` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_computers`;
CREATE TABLE `glpi_computers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `autoupdatesystems_id` int(11) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `networks_id` int(11) NOT NULL DEFAULT 0,
  `computermodels_id` int(11) NOT NULL DEFAULT 0,
  `computertypes_id` int(11) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `ticket_tco` decimal(20,4) DEFAULT 0.0000,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `computermodels_id` (`computermodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `computertypes_id` (`computertypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `uuid` (`uuid`),
  KEY `date_creation` (`date_creation`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_computers_items`;
CREATE TABLE `glpi_computers_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to various table, according to itemtype (ID)',
  `computers_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_computertypes`;
CREATE TABLE `glpi_computertypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_computervirtualmachines`;
CREATE TABLE `glpi_computervirtualmachines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `computers_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `virtualmachinestates_id` int(11) NOT NULL DEFAULT 0,
  `virtualmachinesystems_id` int(11) NOT NULL DEFAULT 0,
  `virtualmachinetypes_id` int(11) NOT NULL DEFAULT 0,
  `uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `vcpu` int(11) NOT NULL DEFAULT 0,
  `ram` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `name` (`name`),
  KEY `virtualmachinestates_id` (`virtualmachinestates_id`),
  KEY `virtualmachinesystems_id` (`virtualmachinesystems_id`),
  KEY `vcpu` (`vcpu`),
  KEY `ram` (`ram`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `uuid` (`uuid`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_configs`;
CREATE TABLE `glpi_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`context`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
(1,	'core',	'version',	'9.5.3'),
(2,	'core',	'show_jobs_at_login',	'0'),
(3,	'core',	'cut',	'250'),
(4,	'core',	'list_limit',	'15'),
(5,	'core',	'list_limit_max',	'50'),
(6,	'core',	'url_maxlength',	'30'),
(7,	'core',	'event_loglevel',	'5'),
(8,	'core',	'notifications_mailing',	'0'),
(9,	'core',	'admin_email',	'admsys@localhost'),
(10,	'core',	'admin_email_name',	''),
(11,	'core',	'admin_reply',	''),
(12,	'core',	'admin_reply_name',	''),
(13,	'core',	'mailing_signature',	'SIGNATURE'),
(14,	'core',	'use_anonymous_helpdesk',	'0'),
(15,	'core',	'use_anonymous_followups',	'0'),
(16,	'core',	'language',	'en_GB'),
(17,	'core',	'priority_1',	'#fff2f2'),
(18,	'core',	'priority_2',	'#ffe0e0'),
(19,	'core',	'priority_3',	'#ffcece'),
(20,	'core',	'priority_4',	'#ffbfbf'),
(21,	'core',	'priority_5',	'#ffadad'),
(22,	'core',	'priority_6',	'#ff5555'),
(23,	'core',	'date_tax',	'2005-12-31'),
(24,	'core',	'cas_host',	''),
(25,	'core',	'cas_port',	'443'),
(26,	'core',	'cas_uri',	''),
(27,	'core',	'cas_logout',	''),
(28,	'core',	'existing_auth_server_field_clean_domain',	'0'),
(29,	'core',	'planning_begin',	'08:00:00'),
(30,	'core',	'planning_end',	'20:00:00'),
(31,	'core',	'utf8_conv',	'1'),
(32,	'core',	'use_public_faq',	'0'),
(33,	'core',	'url_base',	'http://localhost/glpi/'),
(34,	'core',	'show_link_in_mail',	'0'),
(35,	'core',	'text_login',	''),
(36,	'core',	'founded_new_version',	''),
(37,	'core',	'dropdown_max',	'100'),
(38,	'core',	'ajax_wildcard',	'*'),
(39,	'core',	'ajax_limit_count',	'10'),
(40,	'core',	'use_ajax_autocompletion',	'1'),
(41,	'core',	'is_users_auto_add',	'1'),
(42,	'core',	'date_format',	'0'),
(43,	'core',	'number_format',	'0'),
(44,	'core',	'csv_delimiter',	';'),
(45,	'core',	'is_ids_visible',	'0'),
(46,	'core',	'smtp_mode',	'0'),
(47,	'core',	'smtp_host',	''),
(48,	'core',	'smtp_port',	'25'),
(49,	'core',	'smtp_username',	''),
(50,	'core',	'proxy_name',	''),
(51,	'core',	'proxy_port',	'8080'),
(52,	'core',	'proxy_user',	''),
(53,	'core',	'add_followup_on_update_ticket',	'1'),
(54,	'core',	'keep_tickets_on_delete',	'0'),
(55,	'core',	'time_step',	'5'),
(56,	'core',	'decimal_number',	'2'),
(57,	'core',	'helpdesk_doc_url',	''),
(58,	'core',	'central_doc_url',	''),
(59,	'core',	'documentcategories_id_forticket',	'0'),
(60,	'core',	'monitors_management_restrict',	'2'),
(61,	'core',	'phones_management_restrict',	'2'),
(62,	'core',	'peripherals_management_restrict',	'2'),
(63,	'core',	'printers_management_restrict',	'2'),
(64,	'core',	'use_log_in_files',	'1'),
(65,	'core',	'time_offset',	'0'),
(66,	'core',	'is_contact_autoupdate',	'1'),
(67,	'core',	'is_user_autoupdate',	'1'),
(68,	'core',	'is_group_autoupdate',	'1'),
(69,	'core',	'is_location_autoupdate',	'1'),
(70,	'core',	'state_autoupdate_mode',	'0'),
(71,	'core',	'is_contact_autoclean',	'0'),
(72,	'core',	'is_user_autoclean',	'0'),
(73,	'core',	'is_group_autoclean',	'0'),
(74,	'core',	'is_location_autoclean',	'0'),
(75,	'core',	'state_autoclean_mode',	'0'),
(76,	'core',	'use_flat_dropdowntree',	'0'),
(77,	'core',	'use_autoname_by_entity',	'1'),
(78,	'core',	'softwarecategories_id_ondelete',	'1'),
(79,	'core',	'x509_email_field',	''),
(80,	'core',	'x509_cn_restrict',	''),
(81,	'core',	'x509_o_restrict',	''),
(82,	'core',	'x509_ou_restrict',	''),
(83,	'core',	'default_mailcollector_filesize_max',	'2097152'),
(84,	'core',	'followup_private',	'0'),
(85,	'core',	'task_private',	'0'),
(86,	'core',	'default_software_helpdesk_visible',	'1'),
(87,	'core',	'names_format',	'0'),
(88,	'core',	'default_requesttypes_id',	'1'),
(89,	'core',	'use_noright_users_add',	'1'),
(90,	'core',	'cron_limit',	'5'),
(91,	'core',	'priority_matrix',	'{\"1\":{\"1\":1,\"2\":1,\"3\":2,\"4\":2,\"5\":2},\"2\":{\"1\":1,\"2\":2,\"3\":2,\"4\":3,\"5\":3},\"3\":{\"1\":2,\"2\":2,\"3\":3,\"4\":4,\"5\":4},\"4\":{\"1\":2,\"2\":3,\"3\":4,\"4\":4,\"5\":5},\"5\":{\"1\":2,\"2\":3,\"3\":4,\"4\":5,\"5\":5}}'),
(92,	'core',	'urgency_mask',	'62'),
(93,	'core',	'impact_mask',	'62'),
(94,	'core',	'user_deleted_ldap',	'0'),
(95,	'core',	'auto_create_infocoms',	'0'),
(96,	'core',	'use_slave_for_search',	'0'),
(97,	'core',	'proxy_passwd',	''),
(98,	'core',	'smtp_passwd',	''),
(99,	'core',	'transfers_id_auto',	'0'),
(100,	'core',	'show_count_on_tabs',	'1'),
(101,	'core',	'refresh_views',	'0'),
(102,	'core',	'set_default_tech',	'1'),
(103,	'core',	'allow_search_view',	'2'),
(104,	'core',	'allow_search_all',	'0'),
(105,	'core',	'allow_search_global',	'1'),
(106,	'core',	'display_count_on_home',	'5'),
(107,	'core',	'use_password_security',	'0'),
(108,	'core',	'password_min_length',	'8'),
(109,	'core',	'password_need_number',	'1'),
(110,	'core',	'password_need_letter',	'1'),
(111,	'core',	'password_need_caps',	'1'),
(112,	'core',	'password_need_symbol',	'1'),
(113,	'core',	'use_check_pref',	'0'),
(114,	'core',	'notification_to_myself',	'1'),
(115,	'core',	'duedateok_color',	'#06ff00'),
(116,	'core',	'duedatewarning_color',	'#ffb800'),
(117,	'core',	'duedatecritical_color',	'#ff0000'),
(118,	'core',	'duedatewarning_less',	'20'),
(119,	'core',	'duedatecritical_less',	'5'),
(120,	'core',	'duedatewarning_unit',	'%'),
(121,	'core',	'duedatecritical_unit',	'%'),
(122,	'core',	'realname_ssofield',	''),
(123,	'core',	'firstname_ssofield',	''),
(124,	'core',	'email1_ssofield',	''),
(125,	'core',	'email2_ssofield',	''),
(126,	'core',	'email3_ssofield',	''),
(127,	'core',	'email4_ssofield',	''),
(128,	'core',	'phone_ssofield',	''),
(129,	'core',	'phone2_ssofield',	''),
(130,	'core',	'mobile_ssofield',	''),
(131,	'core',	'comment_ssofield',	''),
(132,	'core',	'title_ssofield',	''),
(133,	'core',	'category_ssofield',	''),
(134,	'core',	'language_ssofield',	''),
(135,	'core',	'entity_ssofield',	''),
(136,	'core',	'registration_number_ssofield',	''),
(137,	'core',	'ssovariables_id',	'0'),
(138,	'core',	'ssologout_url',	''),
(139,	'core',	'translate_kb',	'0'),
(140,	'core',	'translate_dropdowns',	'0'),
(141,	'core',	'translate_reminders',	'0'),
(142,	'core',	'pdffont',	'helvetica'),
(143,	'core',	'keep_devices_when_purging_item',	'0'),
(144,	'core',	'maintenance_mode',	'0'),
(145,	'core',	'maintenance_text',	''),
(146,	'core',	'attach_ticket_documents_to_mail',	'0'),
(147,	'core',	'backcreated',	'0'),
(148,	'core',	'task_state',	'1'),
(149,	'core',	'layout',	'lefttab'),
(150,	'core',	'palette',	'auror'),
(151,	'core',	'lock_use_lock_item',	'0'),
(152,	'core',	'lock_autolock_mode',	'1'),
(153,	'core',	'lock_directunlock_notification',	'0'),
(154,	'core',	'lock_item_list',	'[]'),
(155,	'core',	'lock_lockprofile_id',	'8'),
(156,	'core',	'set_default_requester',	'1'),
(157,	'core',	'highcontrast_css',	'0'),
(158,	'core',	'smtp_check_certificate',	'1'),
(159,	'core',	'enable_api',	'0'),
(160,	'core',	'enable_api_login_credentials',	'0'),
(161,	'core',	'enable_api_login_external_token',	'1'),
(162,	'core',	'url_base_api',	'http://localhost/glpi/api'),
(163,	'core',	'login_remember_time',	'604800'),
(164,	'core',	'login_remember_default',	'1'),
(165,	'core',	'use_notifications',	'0'),
(166,	'core',	'notifications_ajax',	'0'),
(167,	'core',	'notifications_ajax_check_interval',	'5'),
(168,	'core',	'notifications_ajax_sound',	NULL),
(169,	'core',	'notifications_ajax_icon_url',	'/pics/glpi.png'),
(170,	'core',	'dbversion',	'9.5.3'),
(171,	'core',	'smtp_max_retries',	'5'),
(172,	'core',	'smtp_sender',	NULL),
(173,	'core',	'from_email',	NULL),
(174,	'core',	'from_email_name',	NULL),
(175,	'core',	'instance_uuid',	NULL),
(176,	'core',	'registration_uuid',	NULL),
(177,	'core',	'smtp_retry_time',	'5'),
(178,	'core',	'purge_addrelation',	'0'),
(179,	'core',	'purge_deleterelation',	'0'),
(180,	'core',	'purge_createitem',	'0'),
(181,	'core',	'purge_deleteitem',	'0'),
(182,	'core',	'purge_restoreitem',	'0'),
(183,	'core',	'purge_updateitem',	'0'),
(184,	'core',	'purge_item_software_install',	'0'),
(185,	'core',	'purge_software_item_install',	'0'),
(186,	'core',	'purge_software_version_install',	'0'),
(187,	'core',	'purge_infocom_creation',	'0'),
(188,	'core',	'purge_profile_user',	'0'),
(189,	'core',	'purge_group_user',	'0'),
(190,	'core',	'purge_adddevice',	'0'),
(191,	'core',	'purge_updatedevice',	'0'),
(192,	'core',	'purge_deletedevice',	'0'),
(193,	'core',	'purge_connectdevice',	'0'),
(194,	'core',	'purge_disconnectdevice',	'0'),
(195,	'core',	'purge_userdeletedfromldap',	'0'),
(196,	'core',	'purge_comments',	'0'),
(197,	'core',	'purge_datemod',	'0'),
(198,	'core',	'purge_all',	'0'),
(199,	'core',	'purge_user_auth_changes',	'0'),
(200,	'core',	'purge_plugins',	'0'),
(201,	'core',	'display_login_source',	'1'),
(202,	'core',	'devices_in_menu',	'[\"Item_DeviceSimcard\"]'),
(203,	'core',	'password_expiration_delay',	'-1'),
(204,	'core',	'password_expiration_notice',	'-1'),
(205,	'core',	'password_expiration_lock_delay',	'-1'),
(206,	'core',	'default_dashboard_central',	'central'),
(207,	'core',	'default_dashboard_assets',	'assets'),
(208,	'core',	'default_dashboard_helpdesk',	'assistance'),
(209,	'core',	'default_dashboard_mini_ticket',	'mini_tickets'),
(210,	'core',	'admin_email_noreply',	''),
(211,	'core',	'admin_email_noreply_name',	''),
(212,	'core',	'impact_enabled_itemtypes',	'[\"Appliance\",\"Cluster\",\"Computer\",\"Datacenter\",\"DCRoom\",\"Domain\",\"Enclosure\",\"Monitor\",\"NetworkEquipment\",\"PDU\",\"Peripheral\",\"Phone\",\"Printer\",\"Rack\",\"Software\"]'),
(213,	'core',	'use_timezones',	'1');

DROP TABLE IF EXISTS `glpi_consumableitems`;
CREATE TABLE `glpi_consumableitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ref` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `consumableitemtypes_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `alarm_threshold` int(11) NOT NULL DEFAULT 10,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `consumableitemtypes_id` (`consumableitemtypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `alarm_threshold` (`alarm_threshold`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `otherserial` (`otherserial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_consumableitemtypes`;
CREATE TABLE `glpi_consumableitemtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_consumables`;
CREATE TABLE `glpi_consumables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `consumableitems_id` int(11) NOT NULL DEFAULT 0,
  `date_in` date DEFAULT NULL,
  `date_out` date DEFAULT NULL,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_in` (`date_in`),
  KEY `date_out` (`date_out`),
  KEY `consumableitems_id` (`consumableitems_id`),
  KEY `entities_id` (`entities_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contacts`;
CREATE TABLE `glpi_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contacttypes_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `usertitles_id` int(11) NOT NULL DEFAULT 0,
  `address` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `postcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `town` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `contacttypes_id` (`contacttypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `usertitles_id` (`usertitles_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contacts_suppliers`;
CREATE TABLE `glpi_contacts_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suppliers_id` int(11) NOT NULL DEFAULT 0,
  `contacts_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`suppliers_id`,`contacts_id`),
  KEY `contacts_id` (`contacts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contacttypes`;
CREATE TABLE `glpi_contacttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contractcosts`;
CREATE TABLE `glpi_contractcosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contracts_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `cost` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `budgets_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `contracts_id` (`contracts_id`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `budgets_id` (`budgets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contracts`;
CREATE TABLE `glpi_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contracttypes_id` int(11) NOT NULL DEFAULT 0,
  `begin_date` date DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `notice` int(11) NOT NULL DEFAULT 0,
  `periodicity` int(11) NOT NULL DEFAULT 0,
  `billing` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `accounting_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `week_begin_hour` time NOT NULL DEFAULT '00:00:00',
  `week_end_hour` time NOT NULL DEFAULT '00:00:00',
  `saturday_begin_hour` time NOT NULL DEFAULT '00:00:00',
  `saturday_end_hour` time NOT NULL DEFAULT '00:00:00',
  `use_saturday` tinyint(1) NOT NULL DEFAULT 0,
  `monday_begin_hour` time NOT NULL DEFAULT '00:00:00',
  `monday_end_hour` time NOT NULL DEFAULT '00:00:00',
  `use_monday` tinyint(1) NOT NULL DEFAULT 0,
  `max_links_allowed` int(11) NOT NULL DEFAULT 0,
  `alert` int(11) NOT NULL DEFAULT 0,
  `renewal` int(11) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `begin_date` (`begin_date`),
  KEY `name` (`name`),
  KEY `contracttypes_id` (`contracttypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `use_monday` (`use_monday`),
  KEY `use_saturday` (`use_saturday`),
  KEY `alert` (`alert`),
  KEY `states_id` (`states_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contracts_items`;
CREATE TABLE `glpi_contracts_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contracts_id` int(11) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`contracts_id`,`itemtype`,`items_id`),
  KEY `FK_device` (`items_id`,`itemtype`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contracts_suppliers`;
CREATE TABLE `glpi_contracts_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suppliers_id` int(11) NOT NULL DEFAULT 0,
  `contracts_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`suppliers_id`,`contracts_id`),
  KEY `contracts_id` (`contracts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_contracttypes`;
CREATE TABLE `glpi_contracttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_crontasklogs`;
CREATE TABLE `glpi_crontasklogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crontasks_id` int(11) NOT NULL,
  `crontasklogs_id` int(11) NOT NULL COMMENT 'id of ''start'' event',
  `date` timestamp NOT NULL,
  `state` int(11) NOT NULL COMMENT '0:start, 1:run, 2:stop',
  `elapsed` float NOT NULL COMMENT 'time elapsed since start',
  `volume` int(11) NOT NULL COMMENT 'for statistics',
  `content` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'message',
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `crontasks_id` (`crontasks_id`),
  KEY `crontasklogs_id_state` (`crontasklogs_id`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_crontasklogs` (`id`, `crontasks_id`, `crontasklogs_id`, `date`, `state`, `elapsed`, `volume`, `content`) VALUES
(1,	33,	0,	'2021-01-19 11:05:01',	0,	0,	0,	'Run mode: CLI'),
(2,	33,	1,	'2021-01-19 11:05:01',	2,	0.18689,	0,	'Action completed, fully processed'),
(3,	37,	0,	'2021-01-19 11:05:01',	0,	0,	0,	'Run mode: CLI'),
(4,	37,	3,	'2021-01-19 11:05:01',	2,	0.048599,	0,	'Action completed, no processing required'),
(5,	38,	0,	'2021-01-19 11:05:01',	0,	0,	0,	'Run mode: CLI'),
(6,	38,	5,	'2021-01-19 11:05:01',	2,	0.0481498,	0,	'Action completed, no processing required');

DROP TABLE IF EXISTS `glpi_crontasks`;
CREATE TABLE `glpi_crontasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8_unicode_ci NOT NULL COMMENT 'task name',
  `frequency` int(11) NOT NULL COMMENT 'second between launch',
  `param` int(11) DEFAULT NULL COMMENT 'task specify parameter',
  `state` int(11) NOT NULL DEFAULT 1 COMMENT '0:disabled, 1:waiting, 2:running',
  `mode` int(11) NOT NULL DEFAULT 1 COMMENT '1:internal, 2:external',
  `allowmode` int(11) NOT NULL DEFAULT 3 COMMENT '1:internal, 2:external, 3:both',
  `hourmin` int(11) NOT NULL DEFAULT 0,
  `hourmax` int(11) NOT NULL DEFAULT 24,
  `logs_lifetime` int(11) NOT NULL DEFAULT 30 COMMENT 'number of days',
  `lastrun` timestamp NULL DEFAULT NULL COMMENT 'last run date',
  `lastcode` int(11) DEFAULT NULL COMMENT 'last run return code',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`name`),
  KEY `mode` (`mode`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Task run by internal / external cron.';

INSERT INTO `glpi_crontasks` (`id`, `itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES
(2,	'CartridgeItem',	'cartridge',	86400,	10,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(3,	'ConsumableItem',	'consumable',	86400,	10,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(4,	'SoftwareLicense',	'software',	86400,	NULL,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(5,	'Contract',	'contract',	86400,	NULL,	1,	1,	3,	0,	24,	30,	'2010-05-06 09:31:02',	NULL,	NULL,	NULL,	NULL),
(6,	'Infocom',	'infocom',	86400,	NULL,	1,	1,	3,	0,	24,	30,	'2011-01-18 11:40:43',	NULL,	NULL,	NULL,	NULL),
(7,	'CronTask',	'logs',	86400,	30,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(9,	'MailCollector',	'mailgate',	600,	10,	1,	1,	3,	0,	24,	30,	'2011-06-28 11:34:37',	NULL,	NULL,	NULL,	NULL),
(10,	'DBconnection',	'checkdbreplicate',	300,	NULL,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(11,	'CronTask',	'checkupdate',	604800,	NULL,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(12,	'CronTask',	'session',	86400,	NULL,	1,	1,	3,	0,	24,	30,	'2011-08-30 08:22:27',	NULL,	NULL,	NULL,	NULL),
(13,	'CronTask',	'graph',	3600,	NULL,	1,	1,	3,	0,	24,	30,	'2011-12-06 09:48:42',	NULL,	NULL,	NULL,	NULL),
(14,	'ReservationItem',	'reservation',	3600,	NULL,	1,	1,	3,	0,	24,	30,	'2012-04-05 20:31:57',	NULL,	NULL,	NULL,	NULL),
(15,	'Ticket',	'closeticket',	43200,	NULL,	1,	1,	3,	0,	24,	30,	'2012-04-05 20:31:57',	NULL,	NULL,	NULL,	NULL),
(16,	'Ticket',	'alertnotclosed',	43200,	NULL,	1,	1,	3,	0,	24,	30,	'2014-04-16 15:32:00',	NULL,	NULL,	NULL,	NULL),
(17,	'SlaLevel_Ticket',	'slaticket',	300,	NULL,	1,	1,	3,	0,	24,	30,	'2014-06-18 08:02:00',	NULL,	NULL,	NULL,	NULL),
(18,	'Ticket',	'createinquest',	86400,	NULL,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(19,	'CronTask',	'watcher',	86400,	NULL,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(20,	'TicketRecurrent',	'ticketrecurrent',	3600,	NULL,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(21,	'PlanningRecall',	'planningrecall',	300,	NULL,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(22,	'QueuedNotification',	'queuednotification',	60,	50,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(23,	'QueuedNotification',	'queuednotificationclean',	86400,	30,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(24,	'CronTask',	'temp',	3600,	NULL,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(25,	'MailCollector',	'mailgateerror',	86400,	NULL,	1,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(26,	'CronTask',	'circularlogs',	86400,	4,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(27,	'ObjectLock',	'unlockobject',	86400,	4,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(28,	'SavedSearch',	'countAll',	604800,	NULL,	0,	1,	3,	0,	24,	10,	NULL,	NULL,	NULL,	NULL,	NULL),
(29,	'SavedSearch_Alert',	'savedsearchesalerts',	86400,	NULL,	0,	1,	3,	0,	24,	10,	NULL,	NULL,	NULL,	NULL,	NULL),
(30,	'Telemetry',	'telemetry',	2592000,	NULL,	0,	1,	3,	0,	24,	10,	NULL,	NULL,	NULL,	NULL,	NULL),
(31,	'Certificate',	'certificate',	86400,	NULL,	0,	1,	3,	0,	24,	10,	NULL,	NULL,	NULL,	NULL,	NULL),
(32,	'OlaLevel_Ticket',	'olaticket',	300,	NULL,	1,	1,	3,	0,	24,	30,	'2014-06-18 08:02:00',	NULL,	NULL,	NULL,	NULL),
(33,	'PurgeLogs',	'PurgeLogs',	604800,	24,	1,	2,	3,	0,	24,	30,	'2021-01-19 11:05:00',	NULL,	NULL,	NULL,	NULL),
(34,	'Ticket',	'purgeticket',	43200,	NULL,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(35,	'Document',	'cleanorphans',	43200,	NULL,	0,	1,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(36,	'User',	'passwordexpiration',	86400,	100,	0,	2,	3,	0,	24,	30,	NULL,	NULL,	NULL,	NULL,	NULL),
(37,	'Glpi\\Marketplace\\Controller',	'checkAllUpdates',	86400,	NULL,	1,	2,	3,	0,	24,	30,	'2021-01-19 11:05:00',	NULL,	NULL,	NULL,	NULL),
(38,	'Domain',	'DomainsAlert',	86400,	NULL,	1,	2,	3,	0,	24,	30,	'2021-01-19 11:05:00',	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_dashboards_dashboards`;
CREATE TABLE `glpi_dashboards_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `context` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'core',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_dashboards_dashboards` (`id`, `key`, `name`, `context`) VALUES
(1,	'central',	'Central',	'core'),
(2,	'assets',	'Assets',	'core'),
(3,	'assistance',	'Assistance',	'core'),
(4,	'mini_tickets',	'Mini tickets dashboard',	'mini_core');

DROP TABLE IF EXISTS `glpi_dashboards_items`;
CREATE TABLE `glpi_dashboards_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dashboards_dashboards_id` int(11) NOT NULL,
  `gridstack_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `card_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `x` int(11) DEFAULT NULL,
  `y` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `card_options` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboards_dashboards_id` (`dashboards_dashboards_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_dashboards_items` (`id`, `dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES
(1,	1,	'bn_count_Computer_4a315743-151c-40cb-a20b-762250668dac',	'bn_count_Computer',	3,	0,	3,	2,	'{\"color\":\"#e69393\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(2,	1,	'bn_count_Software_0690f524-e826-47a9-b50a-906451196b83',	'bn_count_Software',	0,	0,	3,	2,	'{\"color\":\"#aaddac\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(3,	1,	'bn_count_Rack_c6502e0a-5991-46b4-a771-7f355137306b',	'bn_count_Rack',	6,	2,	3,	2,	'{\"color\":\"#0e87a0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(4,	1,	'bn_count_SoftwareLicense_e755fd06-283e-4479-ba35-2d548f8f8a90',	'bn_count_SoftwareLicense',	0,	2,	3,	2,	'{\"color\":\"#27ab3c\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(5,	1,	'bn_count_Monitor_7059b94c-583c-4ba7-b100-d40461165318',	'bn_count_Monitor',	3,	2,	3,	2,	'{\"color\":\"#b52d30\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(6,	1,	'bn_count_Ticket_a74c0903-3387-4a07-9111-b0938af8f1e7',	'bn_count_Ticket',	14,	7,	3,	2,	'{\"color\":\"#ffdc64\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(7,	1,	'bn_count_Problem_c1cf5cfb-f626-472e-82a1-49c3e200e746',	'bn_count_Problem',	20,	7,	3,	2,	'{\"color\":\"#f08d7b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(8,	1,	'count_Computer_Manufacturer_6129c451-42b5-489d-b693-c362adf32d49',	'count_Computer_Manufacturer',	0,	4,	5,	4,	'{\"color\":\"#f8faf9\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(9,	1,	'top_ticket_user_requester_c74f52a8-046a-4077-b1a6-c9f840d34b82',	'top_ticket_user_requester',	14,	9,	6,	5,	'{\"color\":\"#f9fafb\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(10,	1,	'bn_count_tickets_late_04c47208-d7e5-4aca-9566-d46e68c45c67',	'bn_count_tickets_late',	17,	7,	3,	2,	'{\"color\":\"#f8911f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(11,	1,	'ticket_status_2e4e968b-d4e6-4e33-9ce9-a1aaff53dfde',	'ticket_status',	14,	0,	12,	7,	'{\"color\":\"#fafafa\",\"widgettype\":\"stackedbars\",\"use_gradient\":\"0\",\"limit\":\"12\"}'),
(12,	1,	'top_ticket_ITILCategory_37736ba9-d429-4cb3-9058-ef4d111d9269',	'top_ticket_ITILCategory',	20,	9,	6,	5,	'{\"color\":\"#fbf9f9\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(13,	1,	'bn_count_Printer_517684b0-b064-49dd-943e-fcb6f915e453',	'bn_count_Printer',	9,	2,	3,	2,	'{\"color\":\"#365a8f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(14,	1,	'bn_count_Phone_f70c489f-02c1-46e5-978b-94a95b5038ee',	'bn_count_Phone',	9,	0,	3,	2,	'{\"color\":\"#d5e1ec\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(15,	1,	'bn_count_Change_ab950dbd-cd25-466d-8dff-7dcaca386564',	'bn_count_Change',	23,	7,	3,	2,	'{\"color\":\"#cae3c4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(16,	1,	'bn_count_Group_b84a93f2-a26c-49d7-82a4-5446697cc5b0',	'bn_count_Group',	4,	8,	4,	2,	'{\"color\":\"#e0e0e0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(17,	1,	'bn_count_Profile_770b35e8-68e9-4b4f-9e09-5a11058f069f',	'bn_count_Profile',	4,	10,	4,	2,	'{\"color\":\"#e0e0e0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(18,	1,	'bn_count_Supplier_36ff9011-e4cf-4d89-b9ab-346b9857d734',	'bn_count_Supplier',	8,	8,	3,	2,	'{\"color\":\"#c9c9c9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(19,	1,	'bn_count_KnowbaseItem_a3785a56-bed4-4a30-8387-f251f5365b3b',	'bn_count_KnowbaseItem',	8,	10,	3,	2,	'{\"color\":\"#c9c9c9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(20,	1,	'bn_count_Entity_9b82951a-ba52-45cc-a2d3-1d238ec37adf',	'bn_count_Entity',	0,	10,	4,	2,	'{\"color\":\"#f9f9f9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(21,	1,	'bn_count_Document_7dc7f4b8-61ff-4147-b994-5541bddd7b66',	'bn_count_Document',	11,	8,	3,	2,	'{\"color\":\"#b4b4b4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(22,	1,	'bn_count_Project_4d412ee2-8b79-469b-995f-4c0a05ab849d',	'bn_count_Project',	11,	10,	3,	2,	'{\"color\":\"#b3b3b3\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(23,	1,	'bn_count_NetworkEquipment_c537e334-d584-43bc-b6de-b4a939143e89',	'bn_count_NetworkEquipment',	6,	0,	3,	2,	'{\"color\":\"#bfe7ea\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(24,	1,	'bn_count_User_ac0cbe52-3593-43c1-8ecc-0eb115de494d',	'bn_count_User',	0,	8,	4,	2,	'{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(25,	1,	'count_Monitor_MonitorModel_5a476ff9-116e-4270-858b-c003c20841a9',	'count_Monitor_MonitorModel',	5,	4,	5,	4,	'{\"color\":\"#f5fafa\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(26,	1,	'count_NetworkEquipment_State_81f2ae35-b366-4065-ac26-02ea4e3704a6',	'count_NetworkEquipment_State',	10,	4,	4,	4,	'{\"color\":\"#f5f3ef\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(27,	2,	'bn_count_Computer_34cfbaf9-a471-4852-b48c-0dadea7644de',	'bn_count_Computer',	0,	0,	4,	3,	'{\"color\":\"#f3d0d0\",\"widgettype\":\"bigNumber\"}'),
(28,	2,	'bn_count_Software_60091467-2137-49f4-8834-f6602a482079',	'bn_count_Software',	4,	0,	4,	3,	'{\"color\":\"#d1f1a8\",\"widgettype\":\"bigNumber\"}'),
(29,	2,	'bn_count_Printer_c9a385d4-76a3-4971-ad0e-1470efeafacc',	'bn_count_Printer',	8,	3,	4,	3,	'{\"color\":\"#5da8d6\",\"widgettype\":\"bigNumber\"}'),
(30,	2,	'bn_count_PDU_60053eb6-8dda-4416-9a4b-afd51889bd09',	'bn_count_PDU',	12,	3,	4,	3,	'{\"color\":\"#ffb62f\",\"widgettype\":\"bigNumber\"}'),
(31,	2,	'bn_count_Rack_0fdc196f-20d2-4f63-9ddb-b75c165cc664',	'bn_count_Rack',	12,	0,	4,	3,	'{\"color\":\"#f7d79a\",\"widgettype\":\"bigNumber\"}'),
(32,	2,	'bn_count_Phone_c31fde2d-510a-4482-b17d-2f65b61eae08',	'bn_count_Phone',	16,	3,	4,	3,	'{\"color\":\"#a0cec2\",\"widgettype\":\"bigNumber\"}'),
(33,	2,	'bn_count_Enclosure_c21ce30a-58c3-456a-81ec-3c5f01527a8f',	'bn_count_Enclosure',	16,	0,	4,	3,	'{\"color\":\"#d7e8e4\",\"widgettype\":\"bigNumber\"}'),
(34,	2,	'bn_count_NetworkEquipment_76f1e239-777b-4552-b053-ae5c64190347',	'bn_count_NetworkEquipment',	8,	0,	4,	3,	'{\"color\":\"#c8dae4\",\"widgettype\":\"bigNumber\"}'),
(35,	2,	'bn_count_SoftwareLicense_576e58fe-a386-480f-b405-1c2315b8ab47',	'bn_count_SoftwareLicense',	4,	3,	4,	3,	'{\"color\":\"#9bc06b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(36,	2,	'bn_count_Monitor_890e16d3-b121-48c6-9713-d9c239d9a970',	'bn_count_Monitor',	0,	3,	4,	3,	'{\"color\":\"#dc6f6f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(37,	2,	'count_Computer_Manufacturer_986e92e8-32e8-4a6f-806f-6f5383acbb3f',	'count_Computer_Manufacturer',	4,	6,	4,	4,	'{\"color\":\"#f3f5f1\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(38,	2,	'count_Computer_State_290c5920-9eab-4db8-8753-46108e60f1d8',	'count_Computer_State',	0,	6,	4,	4,	'{\"color\":\"#fbf7f7\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(39,	2,	'count_Computer_ComputerType_c58f9c7e-22d5-478b-8226-d2a752bcbb09',	'count_Computer_ComputerType',	8,	6,	4,	4,	'{\"color\":\"#f5f9fa\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(40,	2,	'count_NetworkEquipment_Manufacturer_8132b21c-6f7f-4dc1-af54-bea794cb96e9',	'count_NetworkEquipment_Manufacturer',	12,	6,	4,	4,	'{\"color\":\"#fcf8ed\",\"widgettype\":\"hbar\",\"use_gradient\":\"0\",\"limit\":\"5\"}'),
(41,	2,	'count_Monitor_Manufacturer_43b0c16b-af82-418e-aac1-f32b39705c0d',	'count_Monitor_Manufacturer',	16,	6,	4,	4,	'{\"color\":\"#f9fbfb\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}'),
(42,	3,	'bn_count_Ticket_344e761b-f7e8-4617-8c90-154b266b4d67',	'bn_count_Ticket',	0,	0,	3,	2,	'{\"color\":\"#ffdc64\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(43,	3,	'bn_count_Problem_bdb4002b-a674-4493-820f-af85bed44d2a',	'bn_count_Problem',	0,	4,	3,	2,	'{\"color\":\"#f0967b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(44,	3,	'bn_count_Change_b9b87513-4f40-41e6-8621-f51f9a30fb19',	'bn_count_Change',	0,	6,	3,	2,	'{\"color\":\"#cae3c4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(45,	3,	'bn_count_tickets_late_1e9ae481-21b4-4463-a830-dec1b68ec5e7',	'bn_count_tickets_late',	0,	2,	3,	2,	'{\"color\":\"#f8911f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(46,	3,	'bn_count_tickets_incoming_336a36d9-67fe-4475-880e-447bd766b8fe',	'bn_count_tickets_incoming',	3,	6,	3,	2,	'{\"color\":\"#a0e19d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(47,	3,	'bn_count_tickets_closed_e004bab5-f2b6-4060-a401-a2a8b9885245',	'bn_count_tickets_closed',	9,	8,	3,	2,	'{\"color\":\"#515151\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(48,	3,	'bn_count_tickets_assigned_7455c855-6df8-4514-a3d9-8b0fce52bd63',	'bn_count_tickets_assigned',	6,	6,	3,	2,	'{\"color\":\"#eaf5f7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(49,	3,	'bn_count_tickets_solved_5e9759b3-ee7e-4a14-b68f-1ac024ef55ee',	'bn_count_tickets_solved',	9,	6,	3,	2,	'{\"color\":\"#d8d8d8\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(50,	3,	'bn_count_tickets_waiting_102b2c2a-6ac6-4d73-ba47-8b09382fe00e',	'bn_count_tickets_waiting',	3,	8,	3,	2,	'{\"color\":\"#ffcb7d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(51,	3,	'bn_count_TicketRecurrent_13f79539-61f6-45f7-8dde-045706e652f2',	'bn_count_TicketRecurrent',	0,	8,	3,	2,	'{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(52,	3,	'bn_count_tickets_planned_267bf627-9d5e-4b6c-b53d-b8623d793ccf',	'bn_count_tickets_planned',	6,	8,	3,	2,	'{\"color\":\"#6298d5\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(53,	3,	'top_ticket_ITILCategory_0cba0c84-6c62-4cd8-8564-18614498d8e4',	'top_ticket_ITILCategory',	12,	6,	4,	4,	'{\"color\":\"#f1f5ef\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"7\"}'),
(54,	3,	'top_ticket_RequestType_b9e43f34-8e94-4a6e-9023-c5d1e2ce7859',	'top_ticket_RequestType',	16,	6,	4,	4,	'{\"color\":\"#f9fafb\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"4\"}'),
(55,	3,	'top_ticket_Entity_a8e65812-519c-488e-9892-9adbe22fbd5c',	'top_ticket_Entity',	20,	6,	4,	4,	'{\"color\":\"#f7f1f0\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"7\"}'),
(56,	3,	'ticket_evolution_76fd4926-ee5e-48db-b6d6-e2947c190c5e',	'ticket_evolution',	3,	0,	12,	6,	'{\"color\":\"#f3f7f8\",\"widgettype\":\"areas\",\"use_gradient\":\"0\",\"limit\":\"12\"}'),
(57,	3,	'ticket_status_5b256a35-b36b-4db5-ba11-ea7c125f126e',	'ticket_status',	15,	0,	11,	6,	'{\"color\":\"#f7f3f2\",\"widgettype\":\"stackedbars\",\"use_gradient\":\"0\",\"limit\":\"12\"}'),
(58,	4,	'bn_count_tickets_closed_ccf7246b-645a-40d2-8206-fa33c769e3f5',	'bn_count_tickets_closed',	24,	0,	4,	2,	'{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(59,	4,	'bn_count_Ticket_d5bf3576-5033-40fb-bbdb-292294a7698e',	'bn_count_Ticket',	0,	0,	4,	2,	'{\"color\":\"#ffd957\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(60,	4,	'bn_count_tickets_incoming_055e813c-b0ce-4687-91ef-559249e8ddd8',	'bn_count_tickets_incoming',	4,	0,	4,	2,	'{\"color\":\"#6fd169\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(61,	4,	'bn_count_tickets_waiting_793c665b-b620-4b3a-a5a8-cf502defc008',	'bn_count_tickets_waiting',	8,	0,	4,	2,	'{\"color\":\"#ffcb7d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(62,	4,	'bn_count_tickets_assigned_d3d2f697-52b4-435e-9030-a760dd649085',	'bn_count_tickets_assigned',	12,	0,	4,	2,	'{\"color\":\"#eaf4f7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(63,	4,	'bn_count_tickets_planned_0c7f3569-c23b-4ee3-8e85-279229b23e70',	'bn_count_tickets_planned',	16,	0,	4,	2,	'{\"color\":\"#6298d5\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}'),
(64,	4,	'bn_count_tickets_solved_ae2406cf-e8e8-410b-b355-46e3f5705ee8',	'bn_count_tickets_solved',	20,	0,	4,	2,	'{\"color\":\"#d7d7d7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}');

DROP TABLE IF EXISTS `glpi_dashboards_rights`;
CREATE TABLE `glpi_dashboards_rights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dashboards_dashboards_id` int(11) NOT NULL,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`dashboards_dashboards_id`,`itemtype`,`items_id`),
  KEY `dashboards_dashboards_id` (`dashboards_dashboards_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_datacenters`;
CREATE TABLE `glpi_datacenters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_dcrooms`;
CREATE TABLE `glpi_dcrooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `vis_cols` int(11) DEFAULT NULL,
  `vis_rows` int(11) DEFAULT NULL,
  `blueprint` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `datacenters_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `datacenters_id` (`datacenters_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicebatteries`;
CREATE TABLE `glpi_devicebatteries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `voltage` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `devicebatterytypes_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicebatterymodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicebatterymodels_id` (`devicebatterymodels_id`),
  KEY `devicebatterytypes_id` (`devicebatterytypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicebatterymodels`;
CREATE TABLE `glpi_devicebatterymodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicebatterytypes`;
CREATE TABLE `glpi_devicebatterytypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicecasemodels`;
CREATE TABLE `glpi_devicecasemodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicecases`;
CREATE TABLE `glpi_devicecases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicecasetypes_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicecasemodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicecasetypes_id` (`devicecasetypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicecasemodels_id` (`devicecasemodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicecasetypes`;
CREATE TABLE `glpi_devicecasetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicecontrolmodels`;
CREATE TABLE `glpi_devicecontrolmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicecontrols`;
CREATE TABLE `glpi_devicecontrols` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_raid` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `interfacetypes_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicecontrolmodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicecontrolmodels_id` (`devicecontrolmodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicedrivemodels`;
CREATE TABLE `glpi_devicedrivemodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicedrives`;
CREATE TABLE `glpi_devicedrives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_writer` tinyint(1) NOT NULL DEFAULT 1,
  `speed` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `interfacetypes_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicedrivemodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicedrivemodels_id` (`devicedrivemodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicefirmwaremodels`;
CREATE TABLE `glpi_devicefirmwaremodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicefirmwares`;
CREATE TABLE `glpi_devicefirmwares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `date` date DEFAULT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicefirmwaretypes_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicefirmwaremodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicefirmwaremodels_id` (`devicefirmwaremodels_id`),
  KEY `devicefirmwaretypes_id` (`devicefirmwaretypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicefirmwaretypes`;
CREATE TABLE `glpi_devicefirmwaretypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_devicefirmwaretypes` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'BIOS',	NULL,	NULL,	NULL),
(2,	'UEFI',	NULL,	NULL,	NULL),
(3,	'Firmware',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_devicegenericmodels`;
CREATE TABLE `glpi_devicegenericmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicegenerics`;
CREATE TABLE `glpi_devicegenerics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicegenerictypes_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `devicegenericmodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicegenerictypes_id` (`devicegenerictypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicegenericmodels_id` (`devicegenericmodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicegenerictypes`;
CREATE TABLE `glpi_devicegenerictypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicegraphiccardmodels`;
CREATE TABLE `glpi_devicegraphiccardmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicegraphiccards`;
CREATE TABLE `glpi_devicegraphiccards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `interfacetypes_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `memory_default` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicegraphiccardmodels_id` int(11) DEFAULT NULL,
  `chipset` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `chipset` (`chipset`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicegraphiccardmodels_id` (`devicegraphiccardmodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_deviceharddrivemodels`;
CREATE TABLE `glpi_deviceharddrivemodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_deviceharddrives`;
CREATE TABLE `glpi_deviceharddrives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rpm` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `interfacetypes_id` int(11) NOT NULL DEFAULT 0,
  `cache` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `capacity_default` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `deviceharddrivemodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `deviceharddrivemodels_id` (`deviceharddrivemodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicememories`;
CREATE TABLE `glpi_devicememories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `frequence` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `size_default` int(11) NOT NULL DEFAULT 0,
  `devicememorytypes_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicememorymodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicememorytypes_id` (`devicememorytypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicememorymodels_id` (`devicememorymodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicememorymodels`;
CREATE TABLE `glpi_devicememorymodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicememorytypes`;
CREATE TABLE `glpi_devicememorytypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_devicememorytypes` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'EDO',	NULL,	NULL,	NULL),
(2,	'DDR',	NULL,	NULL,	NULL),
(3,	'SDRAM',	NULL,	NULL,	NULL),
(4,	'SDRAM-2',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_devicemotherboardmodels`;
CREATE TABLE `glpi_devicemotherboardmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicemotherboards`;
CREATE TABLE `glpi_devicemotherboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `chipset` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicemotherboardmodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicemotherboardmodels_id` (`devicemotherboardmodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicenetworkcardmodels`;
CREATE TABLE `glpi_devicenetworkcardmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicenetworkcards`;
CREATE TABLE `glpi_devicenetworkcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bandwidth` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `mac_default` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicenetworkcardmodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicenetworkcardmodels_id` (`devicenetworkcardmodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicepcimodels`;
CREATE TABLE `glpi_devicepcimodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicepcis`;
CREATE TABLE `glpi_devicepcis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `devicenetworkcardmodels_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicepcimodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicenetworkcardmodels_id` (`devicenetworkcardmodels_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicepcimodels_id` (`devicepcimodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicepowersupplies`;
CREATE TABLE `glpi_devicepowersupplies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `power` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_atx` tinyint(1) NOT NULL DEFAULT 1,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicepowersupplymodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicepowersupplymodels_id` (`devicepowersupplymodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicepowersupplymodels`;
CREATE TABLE `glpi_devicepowersupplymodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_deviceprocessormodels`;
CREATE TABLE `glpi_deviceprocessormodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_deviceprocessors`;
CREATE TABLE `glpi_deviceprocessors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `frequence` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `frequency_default` int(11) NOT NULL DEFAULT 0,
  `nbcores_default` int(11) DEFAULT NULL,
  `nbthreads_default` int(11) DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `deviceprocessormodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `deviceprocessormodels_id` (`deviceprocessormodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicesensormodels`;
CREATE TABLE `glpi_devicesensormodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicesensors`;
CREATE TABLE `glpi_devicesensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicesensortypes_id` int(11) NOT NULL DEFAULT 0,
  `devicesensormodels_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicesensortypes_id` (`devicesensortypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicesensortypes`;
CREATE TABLE `glpi_devicesensortypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicesimcards`;
CREATE TABLE `glpi_devicesimcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `voltage` int(11) DEFAULT NULL,
  `devicesimcardtypes_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `allow_voip` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `devicesimcardtypes_id` (`devicesimcardtypes_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicesimcardtypes`;
CREATE TABLE `glpi_devicesimcardtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_devicesimcardtypes` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'Full SIM',	NULL,	NULL,	NULL),
(2,	'Mini SIM',	NULL,	NULL,	NULL),
(3,	'Micro SIM',	NULL,	NULL,	NULL),
(4,	'Nano SIM',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_devicesoundcardmodels`;
CREATE TABLE `glpi_devicesoundcardmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_devicesoundcards`;
CREATE TABLE `glpi_devicesoundcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `devicesoundcardmodels_id` int(11) DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `devicesoundcardmodels_id` (`devicesoundcardmodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_displaypreferences`;
CREATE TABLE `glpi_displaypreferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `num` int(11) NOT NULL DEFAULT 0,
  `rank` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`users_id`,`itemtype`,`num`),
  KEY `rank` (`rank`),
  KEY `num` (`num`),
  KEY `itemtype` (`itemtype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_displaypreferences` (`id`, `itemtype`, `num`, `rank`, `users_id`) VALUES
(1,	'Computer',	4,	4,	0),
(2,	'Computer',	45,	6,	0),
(3,	'Computer',	40,	5,	0),
(4,	'Computer',	5,	3,	0),
(5,	'Computer',	23,	2,	0),
(6,	'DocumentType',	3,	1,	0),
(7,	'Monitor',	31,	1,	0),
(8,	'Monitor',	23,	2,	0),
(9,	'Monitor',	3,	3,	0),
(10,	'Monitor',	4,	4,	0),
(11,	'Printer',	31,	1,	0),
(12,	'NetworkEquipment',	31,	1,	0),
(13,	'NetworkEquipment',	23,	2,	0),
(14,	'Printer',	23,	2,	0),
(15,	'Printer',	3,	3,	0),
(16,	'Software',	4,	3,	0),
(17,	'Software',	5,	2,	0),
(18,	'Software',	23,	1,	0),
(19,	'CartridgeItem',	4,	2,	0),
(20,	'CartridgeItem',	34,	1,	0),
(21,	'Peripheral',	3,	3,	0),
(22,	'Peripheral',	23,	2,	0),
(23,	'Peripheral',	31,	1,	0),
(24,	'Computer',	31,	1,	0),
(25,	'Computer',	3,	7,	0),
(26,	'Computer',	19,	8,	0),
(27,	'Computer',	17,	9,	0),
(28,	'NetworkEquipment',	3,	3,	0),
(29,	'NetworkEquipment',	4,	4,	0),
(30,	'NetworkEquipment',	11,	6,	0),
(31,	'NetworkEquipment',	19,	7,	0),
(32,	'Printer',	4,	4,	0),
(33,	'Printer',	19,	6,	0),
(34,	'Monitor',	19,	6,	0),
(35,	'Monitor',	7,	7,	0),
(36,	'Peripheral',	4,	4,	0),
(37,	'Peripheral',	19,	6,	0),
(38,	'Peripheral',	7,	7,	0),
(39,	'Contact',	3,	1,	0),
(40,	'Contact',	4,	2,	0),
(41,	'Contact',	5,	3,	0),
(42,	'Contact',	6,	4,	0),
(43,	'Contact',	9,	5,	0),
(44,	'Supplier',	9,	1,	0),
(45,	'Supplier',	3,	2,	0),
(46,	'Supplier',	4,	3,	0),
(47,	'Supplier',	5,	4,	0),
(48,	'Supplier',	10,	5,	0),
(49,	'Supplier',	6,	6,	0),
(50,	'Contract',	4,	1,	0),
(51,	'Contract',	3,	2,	0),
(52,	'Contract',	5,	3,	0),
(53,	'Contract',	6,	4,	0),
(54,	'Contract',	7,	5,	0),
(55,	'Contract',	11,	6,	0),
(56,	'CartridgeItem',	23,	3,	0),
(57,	'CartridgeItem',	3,	4,	0),
(58,	'DocumentType',	6,	2,	0),
(59,	'DocumentType',	4,	3,	0),
(60,	'DocumentType',	5,	4,	0),
(61,	'Document',	3,	1,	0),
(62,	'Document',	4,	2,	0),
(63,	'Document',	7,	3,	0),
(64,	'Document',	5,	4,	0),
(65,	'Document',	16,	5,	0),
(66,	'User',	34,	1,	0),
(67,	'User',	5,	3,	0),
(68,	'User',	6,	4,	0),
(69,	'User',	3,	5,	0),
(70,	'ConsumableItem',	34,	1,	0),
(71,	'ConsumableItem',	4,	2,	0),
(72,	'ConsumableItem',	23,	3,	0),
(73,	'ConsumableItem',	3,	4,	0),
(74,	'NetworkEquipment',	40,	5,	0),
(75,	'Printer',	40,	5,	0),
(76,	'Monitor',	40,	5,	0),
(77,	'Peripheral',	40,	5,	0),
(78,	'User',	8,	6,	0),
(79,	'Phone',	31,	1,	0),
(80,	'Phone',	23,	2,	0),
(81,	'Phone',	3,	3,	0),
(82,	'Phone',	4,	4,	0),
(83,	'Phone',	40,	5,	0),
(84,	'Phone',	19,	6,	0),
(85,	'Phone',	7,	7,	0),
(86,	'Group',	16,	1,	0),
(87,	'AllAssets',	31,	1,	0),
(88,	'ReservationItem',	4,	1,	0),
(89,	'ReservationItem',	3,	2,	0),
(90,	'Budget',	3,	2,	0),
(91,	'Software',	72,	4,	0),
(92,	'Software',	163,	5,	0),
(93,	'Budget',	5,	1,	0),
(94,	'Budget',	4,	3,	0),
(95,	'Budget',	19,	4,	0),
(96,	'CronTask',	8,	1,	0),
(97,	'CronTask',	3,	2,	0),
(98,	'CronTask',	4,	3,	0),
(99,	'CronTask',	7,	4,	0),
(100,	'RequestType',	14,	1,	0),
(101,	'RequestType',	15,	2,	0),
(102,	'NotificationTemplate',	4,	1,	0),
(103,	'NotificationTemplate',	16,	2,	0),
(104,	'Notification',	5,	1,	0),
(105,	'Notification',	6,	2,	0),
(106,	'Notification',	2,	3,	0),
(107,	'Notification',	4,	4,	0),
(108,	'Notification',	80,	5,	0),
(109,	'Notification',	86,	6,	0),
(110,	'MailCollector',	2,	1,	0),
(111,	'MailCollector',	19,	2,	0),
(112,	'AuthLDAP',	3,	1,	0),
(113,	'AuthLDAP',	19,	2,	0),
(114,	'AuthMail',	3,	1,	0),
(115,	'AuthMail',	19,	2,	0),
(116,	'IPNetwork',	18,	1,	0),
(117,	'WifiNetwork',	10,	1,	0),
(118,	'Profile',	2,	1,	0),
(119,	'Profile',	3,	2,	0),
(120,	'Profile',	19,	3,	0),
(121,	'Transfer',	19,	1,	0),
(122,	'TicketValidation',	3,	1,	0),
(123,	'TicketValidation',	2,	2,	0),
(124,	'TicketValidation',	8,	3,	0),
(125,	'TicketValidation',	4,	4,	0),
(126,	'TicketValidation',	9,	5,	0),
(127,	'TicketValidation',	7,	6,	0),
(128,	'NotImportedEmail',	2,	1,	0),
(129,	'NotImportedEmail',	5,	2,	0),
(130,	'NotImportedEmail',	4,	3,	0),
(131,	'NotImportedEmail',	6,	4,	0),
(132,	'NotImportedEmail',	16,	5,	0),
(133,	'NotImportedEmail',	19,	6,	0),
(134,	'RuleRightParameter',	11,	1,	0),
(135,	'Ticket',	12,	1,	0),
(136,	'Ticket',	19,	2,	0),
(137,	'Ticket',	15,	3,	0),
(138,	'Ticket',	3,	4,	0),
(139,	'Ticket',	4,	5,	0),
(140,	'Ticket',	5,	6,	0),
(141,	'Ticket',	7,	7,	0),
(142,	'Calendar',	19,	1,	0),
(143,	'Holiday',	11,	1,	0),
(144,	'Holiday',	12,	2,	0),
(145,	'Holiday',	13,	3,	0),
(146,	'SLA',	4,	1,	0),
(147,	'Ticket',	18,	8,	0),
(148,	'AuthLDAP',	30,	3,	0),
(149,	'AuthMail',	6,	3,	0),
(150,	'FQDN',	11,	1,	0),
(151,	'FieldUnicity',	1,	1,	0),
(152,	'FieldUnicity',	80,	2,	0),
(153,	'FieldUnicity',	4,	3,	0),
(154,	'FieldUnicity',	3,	4,	0),
(155,	'FieldUnicity',	86,	5,	0),
(156,	'FieldUnicity',	30,	6,	0),
(157,	'Problem',	21,	1,	0),
(158,	'Problem',	12,	2,	0),
(159,	'Problem',	19,	3,	0),
(160,	'Problem',	15,	4,	0),
(161,	'Problem',	3,	5,	0),
(162,	'Problem',	7,	6,	0),
(163,	'Problem',	18,	7,	0),
(164,	'Vlan',	11,	1,	0),
(165,	'TicketRecurrent',	11,	1,	0),
(166,	'TicketRecurrent',	12,	2,	0),
(167,	'TicketRecurrent',	13,	3,	0),
(168,	'TicketRecurrent',	15,	4,	0),
(169,	'TicketRecurrent',	14,	5,	0),
(170,	'Reminder',	2,	1,	0),
(171,	'Reminder',	3,	2,	0),
(172,	'Reminder',	4,	3,	0),
(173,	'Reminder',	5,	4,	0),
(174,	'Reminder',	6,	5,	0),
(175,	'Reminder',	7,	6,	0),
(176,	'IPNetwork',	10,	2,	0),
(177,	'IPNetwork',	11,	3,	0),
(178,	'IPNetwork',	12,	4,	0),
(179,	'IPNetwork',	17,	5,	0),
(180,	'NetworkName',	12,	1,	0),
(181,	'NetworkName',	13,	2,	0),
(182,	'RSSFeed',	2,	1,	0),
(183,	'RSSFeed',	4,	2,	0),
(184,	'RSSFeed',	5,	3,	0),
(185,	'RSSFeed',	19,	4,	0),
(186,	'RSSFeed',	6,	5,	0),
(187,	'RSSFeed',	7,	6,	0),
(188,	'Blacklist',	12,	1,	0),
(189,	'Blacklist',	11,	2,	0),
(190,	'ReservationItem',	5,	3,	0),
(191,	'QueueMail',	16,	1,	0),
(192,	'QueueMail',	7,	2,	0),
(193,	'QueueMail',	20,	3,	0),
(194,	'QueueMail',	21,	4,	0),
(195,	'QueueMail',	22,	5,	0),
(196,	'QueueMail',	15,	6,	0),
(197,	'Change',	12,	1,	0),
(198,	'Change',	19,	2,	0),
(199,	'Change',	15,	3,	0),
(200,	'Change',	7,	4,	0),
(201,	'Change',	18,	5,	0),
(202,	'Project',	3,	1,	0),
(203,	'Project',	4,	2,	0),
(204,	'Project',	12,	3,	0),
(205,	'Project',	5,	4,	0),
(206,	'Project',	15,	5,	0),
(207,	'Project',	21,	6,	0),
(208,	'ProjectState',	12,	1,	0),
(209,	'ProjectState',	11,	2,	0),
(210,	'ProjectTask',	2,	1,	0),
(211,	'ProjectTask',	12,	2,	0),
(212,	'ProjectTask',	14,	3,	0),
(213,	'ProjectTask',	5,	4,	0),
(214,	'ProjectTask',	7,	5,	0),
(215,	'ProjectTask',	8,	6,	0),
(216,	'ProjectTask',	13,	7,	0),
(217,	'CartridgeItem',	9,	5,	0),
(218,	'ConsumableItem',	9,	5,	0),
(219,	'ReservationItem',	9,	4,	0),
(220,	'SoftwareLicense',	1,	1,	0),
(221,	'SoftwareLicense',	3,	2,	0),
(222,	'SoftwareLicense',	10,	3,	0),
(223,	'SoftwareLicense',	162,	4,	0),
(224,	'SoftwareLicense',	5,	5,	0),
(225,	'SavedSearch',	8,	1,	0),
(226,	'SavedSearch',	9,	1,	0),
(227,	'SavedSearch',	3,	1,	0),
(228,	'SavedSearch',	10,	1,	0),
(229,	'SavedSearch',	11,	1,	0),
(230,	'Plugin',	2,	1,	0),
(231,	'Plugin',	3,	2,	0),
(232,	'Plugin',	4,	3,	0),
(233,	'Plugin',	5,	4,	0),
(234,	'Plugin',	6,	5,	0),
(235,	'Plugin',	7,	6,	0),
(236,	'Plugin',	8,	7,	0),
(237,	'Cluster',	31,	1,	0),
(238,	'Cluster',	19,	2,	0),
(239,	'Domain',	3,	1,	0),
(240,	'Domain',	4,	2,	0),
(241,	'Domain',	2,	3,	0),
(242,	'Domain',	6,	4,	0),
(243,	'Domain',	7,	5,	0),
(244,	'DomainRecord',	2,	1,	0),
(245,	'DomainRecord',	3,	2,	0),
(246,	'Appliance',	2,	1,	0),
(247,	'Appliance',	3,	2,	0),
(248,	'Appliance',	4,	3,	0),
(249,	'Appliance',	5,	4,	0);

DROP TABLE IF EXISTS `glpi_documentcategories`;
CREATE TABLE `glpi_documentcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `documentcategories_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`documentcategories_id`,`name`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_documents`;
CREATE TABLE `glpi_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'for display and transfert',
  `filepath` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'file storage path',
  `documentcategories_id` int(11) NOT NULL DEFAULT 0,
  `mime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `sha1sum` char(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_blacklisted` tinyint(1) NOT NULL DEFAULT 0,
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `tickets_id` (`tickets_id`),
  KEY `users_id` (`users_id`),
  KEY `documentcategories_id` (`documentcategories_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `sha1sum` (`sha1sum`),
  KEY `tag` (`tag`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_documents_items`;
CREATE TABLE `glpi_documents_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documents_id` int(11) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `users_id` int(11) DEFAULT 0,
  `timeline_position` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`documents_id`,`itemtype`,`items_id`,`timeline_position`),
  KEY `item` (`itemtype`,`items_id`,`entities_id`,`is_recursive`),
  KEY `users_id` (`users_id`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_documenttypes`;
CREATE TABLE `glpi_documenttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ext` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_uploadable` tinyint(1) NOT NULL DEFAULT 1,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`ext`),
  KEY `name` (`name`),
  KEY `is_uploadable` (`is_uploadable`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_documenttypes` (`id`, `name`, `ext`, `icon`, `mime`, `is_uploadable`, `date_mod`, `comment`, `date_creation`) VALUES
(1,	'JPEG',	'jpg',	'jpg-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(2,	'PNG',	'png',	'png-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(3,	'GIF',	'gif',	'gif-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(4,	'BMP',	'bmp',	'bmp-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(5,	'Photoshop',	'psd',	'psd-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(6,	'TIFF',	'tif',	'tif-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(7,	'AIFF',	'aiff',	'aiff-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(8,	'Windows Media',	'asf',	'asf-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(9,	'Windows Media',	'avi',	'avi-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(10,	'BZip',	'bz2',	'bz2-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(11,	'Word',	'doc',	'doc-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(12,	'DjVu',	'djvu',	'',	NULL,	1,	NULL,	NULL,	NULL),
(13,	'PostScript',	'eps',	'ps-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(14,	'GZ',	'gz',	'gz-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(15,	'HTML',	'html',	'html-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(16,	'Midi',	'mid',	'mid-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(17,	'QuickTime',	'mov',	'mov-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(18,	'MP3',	'mp3',	'mp3-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(19,	'MPEG',	'mpg',	'mpg-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(20,	'Ogg Vorbis',	'ogg',	'ogg-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(21,	'PDF',	'pdf',	'pdf-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(22,	'PowerPoint',	'ppt',	'ppt-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(23,	'PostScript',	'ps',	'ps-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(24,	'QuickTime',	'qt',	'qt-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(25,	'RealAudio',	'ra',	'ra-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(26,	'RealAudio',	'ram',	'ram-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(27,	'RealAudio',	'rm',	'rm-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(28,	'RTF',	'rtf',	'rtf-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(29,	'StarOffice',	'sdd',	'sdd-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(30,	'StarOffice',	'sdw',	'sdw-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(31,	'Stuffit',	'sit',	'sit-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(32,	'OpenOffice Impress',	'sxi',	'sxi-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(33,	'OpenOffice',	'sxw',	'sxw-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(34,	'Flash',	'swf',	'swf-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(35,	'TGZ',	'tgz',	'tgz-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(36,	'texte',	'txt',	'txt-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(37,	'WAV',	'wav',	'wav-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(38,	'Excel',	'xls',	'xls-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(39,	'XML',	'xml',	'xml-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(40,	'Windows Media',	'wmv',	'wmv-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(41,	'Zip',	'zip',	'zip-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(42,	'MNG',	'mng',	'',	NULL,	1,	NULL,	NULL,	NULL),
(43,	'Adobe Illustrator',	'ai',	'ai-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(44,	'C source',	'c',	'c-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(45,	'Debian',	'deb',	'deb-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(46,	'DVI',	'dvi',	'dvi-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(47,	'C header',	'h',	'h-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(48,	'Pascal',	'pas',	'pas-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(49,	'RedHat/Mandrake/SuSE',	'rpm',	'rpm-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(50,	'OpenOffice Calc',	'sxc',	'sxc-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(51,	'LaTeX',	'tex',	'tex-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(52,	'GIMP multi-layer',	'xcf',	'xcf-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(53,	'JPEG',	'jpeg',	'jpg-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(54,	'Oasis Open Office Writer',	'odt',	'odt-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(55,	'Oasis Open Office Calc',	'ods',	'ods-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(56,	'Oasis Open Office Impress',	'odp',	'odp-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(57,	'Oasis Open Office Impress Template',	'otp',	'odp-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(58,	'Oasis Open Office Writer Template',	'ott',	'odt-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(59,	'Oasis Open Office Calc Template',	'ots',	'ods-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(60,	'Oasis Open Office Math',	'odf',	'odf-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(61,	'Oasis Open Office Draw',	'odg',	'odg-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(62,	'Oasis Open Office Draw Template',	'otg',	'odg-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(63,	'Oasis Open Office Base',	'odb',	'odb-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(64,	'Oasis Open Office HTML',	'oth',	'oth-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(65,	'Oasis Open Office Writer Master',	'odm',	'odm-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(66,	'Oasis Open Office Chart',	'odc',	'',	NULL,	1,	NULL,	NULL,	NULL),
(67,	'Oasis Open Office Image',	'odi',	'',	NULL,	1,	NULL,	NULL,	NULL),
(68,	'Word XML',	'docx',	'doc-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(69,	'Excel XML',	'xlsx',	'xls-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(70,	'PowerPoint XML',	'pptx',	'ppt-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(71,	'Comma-Separated Values',	'csv',	'csv-dist.png',	NULL,	1,	NULL,	NULL,	NULL),
(72,	'Scalable Vector Graphics',	'svg',	'svg-dist.png',	NULL,	1,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_domainrecords`;
CREATE TABLE `glpi_domainrecords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `domains_id` int(11) NOT NULL DEFAULT 0,
  `domainrecordtypes_id` int(11) NOT NULL DEFAULT 0,
  `ttl` int(11) NOT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `domains_id` (`domains_id`),
  KEY `domainrecordtypes_id` (`domainrecordtypes_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date_mod` (`date_mod`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_domainrecordtypes`;
CREATE TABLE `glpi_domainrecordtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_domainrecordtypes` (`id`, `name`, `entities_id`, `is_recursive`, `comment`) VALUES
(1,	'A',	0,	1,	'Host address'),
(2,	'AAAA',	0,	1,	'IPv6 host address'),
(3,	'ALIAS',	0,	1,	'Auto resolved alias'),
(4,	'CNAME',	0,	1,	'Canonical name for an alias'),
(5,	'MX',	0,	1,	'Mail eXchange'),
(6,	'NS',	0,	1,	'Name Server'),
(7,	'PTR',	0,	1,	'Pointer'),
(8,	'SOA',	0,	1,	'Start Of Authority'),
(9,	'SRV',	0,	1,	'Location of service'),
(10,	'TXT',	0,	1,	'Descriptive text');

DROP TABLE IF EXISTS `glpi_domainrelations`;
CREATE TABLE `glpi_domainrelations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_domainrelations` (`id`, `name`, `entities_id`, `is_recursive`, `comment`) VALUES
(1,	'Belongs',	0,	1,	'Item belongs to domain'),
(2,	'Manage',	0,	1,	'Item manages domain');

DROP TABLE IF EXISTS `glpi_domains`;
CREATE TABLE `glpi_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `domaintypes_id` int(11) NOT NULL DEFAULT 0,
  `date_expiration` timestamp NULL DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `others` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `domaintypes_id` (`domaintypes_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date_mod` (`date_mod`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_expiration` (`date_expiration`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_domains_items`;
CREATE TABLE `glpi_domains_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domains_id` int(11) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `domainrelations_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`domains_id`,`itemtype`,`items_id`),
  KEY `domains_id` (`domains_id`),
  KEY `domainrelations_id` (`domainrelations_id`),
  KEY `FK_device` (`items_id`,`itemtype`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_domaintypes`;
CREATE TABLE `glpi_domaintypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_dropdowntranslations`;
CREATE TABLE `glpi_dropdowntranslations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `field` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`language`,`field`),
  KEY `typeid` (`itemtype`,`items_id`),
  KEY `language` (`language`),
  KEY `field` (`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_enclosuremodels`;
CREATE TABLE `glpi_enclosuremodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `required_units` int(11) NOT NULL DEFAULT 1,
  `depth` float NOT NULL DEFAULT 1,
  `power_connections` int(11) NOT NULL DEFAULT 0,
  `power_consumption` int(11) NOT NULL DEFAULT 0,
  `is_half_rack` tinyint(1) NOT NULL DEFAULT 0,
  `picture_front` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture_rear` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_enclosures`;
CREATE TABLE `glpi_enclosures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enclosuremodels_id` int(11) DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `orientation` tinyint(1) DEFAULT NULL,
  `power_supplies` tinyint(1) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to states (id)',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `enclosuremodels_id` (`enclosuremodels_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `group_id_tech` (`groups_id_tech`),
  KEY `is_template` (`is_template`),
  KEY `is_deleted` (`is_deleted`),
  KEY `states_id` (`states_id`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_entities`;
CREATE TABLE `glpi_entities` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `postcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `town` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phonenumber` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_email_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_reply` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_reply_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `notification_subject_tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ldap_dn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `authldaps_id` int(11) NOT NULL DEFAULT 0,
  `mail_domain` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_ldapfilter` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `mailing_signature` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `cartridges_alert_repeat` int(11) NOT NULL DEFAULT -2,
  `consumables_alert_repeat` int(11) NOT NULL DEFAULT -2,
  `use_licenses_alert` int(11) NOT NULL DEFAULT -2,
  `send_licenses_alert_before_delay` int(11) NOT NULL DEFAULT -2,
  `use_certificates_alert` int(11) NOT NULL DEFAULT -2,
  `send_certificates_alert_before_delay` int(11) NOT NULL DEFAULT -2,
  `use_contracts_alert` int(11) NOT NULL DEFAULT -2,
  `send_contracts_alert_before_delay` int(11) NOT NULL DEFAULT -2,
  `use_infocoms_alert` int(11) NOT NULL DEFAULT -2,
  `send_infocoms_alert_before_delay` int(11) NOT NULL DEFAULT -2,
  `use_reservations_alert` int(11) NOT NULL DEFAULT -2,
  `use_domains_alert` int(11) NOT NULL DEFAULT -2,
  `send_domains_alert_close_expiries_delay` int(11) NOT NULL DEFAULT -2,
  `send_domains_alert_expired_delay` int(11) NOT NULL DEFAULT -2,
  `autoclose_delay` int(11) NOT NULL DEFAULT -2,
  `autopurge_delay` int(11) NOT NULL DEFAULT -10,
  `notclosed_delay` int(11) NOT NULL DEFAULT -2,
  `calendars_id` int(11) NOT NULL DEFAULT -2,
  `auto_assign_mode` int(11) NOT NULL DEFAULT -2,
  `tickettype` int(11) NOT NULL DEFAULT -2,
  `max_closedate` timestamp NULL DEFAULT NULL,
  `inquest_config` int(11) NOT NULL DEFAULT -2,
  `inquest_rate` int(11) NOT NULL DEFAULT 0,
  `inquest_delay` int(11) NOT NULL DEFAULT -10,
  `inquest_URL` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `autofill_warranty_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-2',
  `autofill_use_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-2',
  `autofill_buy_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-2',
  `autofill_delivery_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-2',
  `autofill_order_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-2',
  `tickettemplates_id` int(11) NOT NULL DEFAULT -2,
  `changetemplates_id` int(11) NOT NULL DEFAULT -2,
  `problemtemplates_id` int(11) NOT NULL DEFAULT -2,
  `entities_id_software` int(11) NOT NULL DEFAULT -2,
  `default_contract_alert` int(11) NOT NULL DEFAULT -2,
  `default_infocom_alert` int(11) NOT NULL DEFAULT -2,
  `default_cartridges_alarm_threshold` int(11) NOT NULL DEFAULT -2,
  `default_consumables_alarm_threshold` int(11) NOT NULL DEFAULT -2,
  `delay_send_emails` int(11) NOT NULL DEFAULT -2,
  `is_notif_enable_default` int(11) NOT NULL DEFAULT -2,
  `inquest_duration` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `autofill_decommission_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-2',
  `suppliers_as_private` int(11) NOT NULL DEFAULT -2,
  `anonymize_support_agents` int(11) NOT NULL DEFAULT -2,
  `enable_custom_css` int(11) NOT NULL DEFAULT -2,
  `custom_css_code` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `altitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`entities_id`,`name`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `tickettemplates_id` (`tickettemplates_id`),
  KEY `changetemplates_id` (`changetemplates_id`),
  KEY `problemtemplates_id` (`problemtemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_entities` (`id`, `name`, `entities_id`, `completename`, `comment`, `level`, `sons_cache`, `ancestors_cache`, `address`, `postcode`, `town`, `state`, `country`, `website`, `phonenumber`, `fax`, `email`, `admin_email`, `admin_email_name`, `admin_reply`, `admin_reply_name`, `notification_subject_tag`, `ldap_dn`, `tag`, `authldaps_id`, `mail_domain`, `entity_ldapfilter`, `mailing_signature`, `cartridges_alert_repeat`, `consumables_alert_repeat`, `use_licenses_alert`, `send_licenses_alert_before_delay`, `use_certificates_alert`, `send_certificates_alert_before_delay`, `use_contracts_alert`, `send_contracts_alert_before_delay`, `use_infocoms_alert`, `send_infocoms_alert_before_delay`, `use_reservations_alert`, `use_domains_alert`, `send_domains_alert_close_expiries_delay`, `send_domains_alert_expired_delay`, `autoclose_delay`, `autopurge_delay`, `notclosed_delay`, `calendars_id`, `auto_assign_mode`, `tickettype`, `max_closedate`, `inquest_config`, `inquest_rate`, `inquest_delay`, `inquest_URL`, `autofill_warranty_date`, `autofill_use_date`, `autofill_buy_date`, `autofill_delivery_date`, `autofill_order_date`, `tickettemplates_id`, `changetemplates_id`, `problemtemplates_id`, `entities_id_software`, `default_contract_alert`, `default_infocom_alert`, `default_cartridges_alarm_threshold`, `default_consumables_alarm_threshold`, `delay_send_emails`, `is_notif_enable_default`, `inquest_duration`, `date_mod`, `date_creation`, `autofill_decommission_date`, `suppliers_as_private`, `anonymize_support_agents`, `enable_custom_css`, `custom_css_code`, `latitude`, `longitude`, `altitude`) VALUES
(0,	'Root entity',	-1,	'Root entity',	NULL,	1,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	0,	0,	0,	0,	0,	0,	0,	0,	0,	0,	0,	-2,	-2,	-2,	-10,	-10,	0,	0,	-10,	1,	NULL,	1,	0,	0,	NULL,	'0',	'0',	'0',	'0',	'0',	1,	1,	1,	-10,	0,	0,	10,	10,	0,	1,	0,	NULL,	NULL,	'0',	0,	0,	0,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_entities_knowbaseitems`;
CREATE TABLE `glpi_entities_knowbaseitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `knowbaseitems_id` (`knowbaseitems_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_entities_reminders`;
CREATE TABLE `glpi_entities_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reminders_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reminders_id` (`reminders_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_entities_rssfeeds`;
CREATE TABLE `glpi_entities_rssfeeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rssfeeds_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `rssfeeds_id` (`rssfeeds_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_events`;
CREATE TABLE `glpi_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `service` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `message` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `level` (`level`),
  KEY `item` (`type`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_fieldblacklists`;
CREATE TABLE `glpi_fieldblacklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `field` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_fieldunicities`;
CREATE TABLE `glpi_fieldunicities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `fields` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `action_refuse` tinyint(1) NOT NULL DEFAULT 0,
  `action_notify` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores field unicity criterias';


DROP TABLE IF EXISTS `glpi_filesystems`;
CREATE TABLE `glpi_filesystems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_filesystems` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'ext',	NULL,	NULL,	NULL),
(2,	'ext2',	NULL,	NULL,	NULL),
(3,	'ext3',	NULL,	NULL,	NULL),
(4,	'ext4',	NULL,	NULL,	NULL),
(5,	'FAT',	NULL,	NULL,	NULL),
(6,	'FAT32',	NULL,	NULL,	NULL),
(7,	'VFAT',	NULL,	NULL,	NULL),
(8,	'HFS',	NULL,	NULL,	NULL),
(9,	'HPFS',	NULL,	NULL,	NULL),
(10,	'HTFS',	NULL,	NULL,	NULL),
(11,	'JFS',	NULL,	NULL,	NULL),
(12,	'JFS2',	NULL,	NULL,	NULL),
(13,	'NFS',	NULL,	NULL,	NULL),
(14,	'NTFS',	NULL,	NULL,	NULL),
(15,	'ReiserFS',	NULL,	NULL,	NULL),
(16,	'SMBFS',	NULL,	NULL,	NULL),
(17,	'UDF',	NULL,	NULL,	NULL),
(18,	'UFS',	NULL,	NULL,	NULL),
(19,	'XFS',	NULL,	NULL,	NULL),
(20,	'ZFS',	NULL,	NULL,	NULL),
(21,	'APFS',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_fqdns`;
CREATE TABLE `glpi_fqdns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fqdn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `name` (`name`),
  KEY `fqdn` (`fqdn`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_groups`;
CREATE TABLE `glpi_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `ldap_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ldap_value` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `ldap_group_dn` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_requester` tinyint(1) NOT NULL DEFAULT 1,
  `is_watcher` tinyint(1) NOT NULL DEFAULT 1,
  `is_assign` tinyint(1) NOT NULL DEFAULT 1,
  `is_task` tinyint(1) NOT NULL DEFAULT 1,
  `is_notify` tinyint(1) NOT NULL DEFAULT 1,
  `is_itemgroup` tinyint(1) NOT NULL DEFAULT 1,
  `is_usergroup` tinyint(1) NOT NULL DEFAULT 1,
  `is_manager` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `ldap_field` (`ldap_field`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `ldap_value` (`ldap_value`(200)),
  KEY `ldap_group_dn` (`ldap_group_dn`(200)),
  KEY `groups_id` (`groups_id`),
  KEY `is_requester` (`is_requester`),
  KEY `is_watcher` (`is_watcher`),
  KEY `is_assign` (`is_assign`),
  KEY `is_notify` (`is_notify`),
  KEY `is_itemgroup` (`is_itemgroup`),
  KEY `is_usergroup` (`is_usergroup`),
  KEY `is_manager` (`is_manager`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_groups_knowbaseitems`;
CREATE TABLE `glpi_groups_knowbaseitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `knowbaseitems_id` (`knowbaseitems_id`),
  KEY `groups_id` (`groups_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_groups_problems`;
CREATE TABLE `glpi_groups_problems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`problems_id`,`type`,`groups_id`),
  KEY `group` (`groups_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_groups_reminders`;
CREATE TABLE `glpi_groups_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reminders_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reminders_id` (`reminders_id`),
  KEY `groups_id` (`groups_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_groups_rssfeeds`;
CREATE TABLE `glpi_groups_rssfeeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rssfeeds_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `rssfeeds_id` (`rssfeeds_id`),
  KEY `groups_id` (`groups_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_groups_tickets`;
CREATE TABLE `glpi_groups_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickets_id`,`type`,`groups_id`),
  KEY `group` (`groups_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_groups_users`;
CREATE TABLE `glpi_groups_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `is_manager` tinyint(1) NOT NULL DEFAULT 0,
  `is_userdelegate` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`users_id`,`groups_id`),
  KEY `groups_id` (`groups_id`),
  KEY `is_manager` (`is_manager`),
  KEY `is_userdelegate` (`is_userdelegate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_holidays`;
CREATE TABLE `glpi_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_perpetual` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `is_perpetual` (`is_perpetual`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_impactcompounds`;
CREATE TABLE `glpi_impactcompounds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `color` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_impactcontexts`;
CREATE TABLE `glpi_impactcontexts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `positions` text COLLATE utf8_unicode_ci NOT NULL,
  `zoom` float NOT NULL DEFAULT 0,
  `pan_x` float NOT NULL DEFAULT 0,
  `pan_y` float NOT NULL DEFAULT 0,
  `impact_color` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `depends_color` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `impact_and_depends_color` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `show_depends` tinyint(1) NOT NULL DEFAULT 1,
  `show_impact` tinyint(1) NOT NULL DEFAULT 1,
  `max_depth` int(11) NOT NULL DEFAULT 5,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_impactitems`;
CREATE TABLE `glpi_impactitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `items_id` int(11) NOT NULL DEFAULT 0,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `impactcontexts_id` int(11) NOT NULL DEFAULT 0,
  `is_slave` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`),
  KEY `source` (`itemtype`,`items_id`),
  KEY `parent_id` (`parent_id`),
  KEY `impactcontexts_id` (`impactcontexts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_impactrelations`;
CREATE TABLE `glpi_impactrelations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `items_id_source` int(11) NOT NULL DEFAULT 0,
  `itemtype_impacted` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `items_id_impacted` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype_source`,`items_id_source`,`itemtype_impacted`,`items_id_impacted`),
  KEY `source_asset` (`itemtype_source`,`items_id_source`),
  KEY `impacted_asset` (`itemtype_impacted`,`items_id_impacted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_infocoms`;
CREATE TABLE `glpi_infocoms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `buy_date` date DEFAULT NULL,
  `use_date` date DEFAULT NULL,
  `warranty_duration` int(11) NOT NULL DEFAULT 0,
  `warranty_info` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `suppliers_id` int(11) NOT NULL DEFAULT 0,
  `order_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `delivery_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `immo_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `warranty_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `sink_time` int(11) NOT NULL DEFAULT 0,
  `sink_type` int(11) NOT NULL DEFAULT 0,
  `sink_coeff` float NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `bill` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `budgets_id` int(11) NOT NULL DEFAULT 0,
  `alert` int(11) NOT NULL DEFAULT 0,
  `order_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `inventory_date` date DEFAULT NULL,
  `warranty_date` date DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `decommission_date` timestamp NULL DEFAULT NULL,
  `businesscriticities_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`),
  KEY `buy_date` (`buy_date`),
  KEY `alert` (`alert`),
  KEY `budgets_id` (`budgets_id`),
  KEY `suppliers_id` (`suppliers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `businesscriticities_id` (`businesscriticities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_interfacetypes`;
CREATE TABLE `glpi_interfacetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_interfacetypes` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'IDE',	NULL,	NULL,	NULL),
(2,	'SATA',	NULL,	NULL,	NULL),
(3,	'SCSI',	NULL,	NULL,	NULL),
(4,	'USB',	NULL,	NULL,	NULL),
(5,	'AGP',	NULL,	NULL,	NULL),
(6,	'PCI',	NULL,	NULL,	NULL),
(7,	'PCIe',	NULL,	NULL,	NULL),
(8,	'PCI-X',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_ipaddresses`;
CREATE TABLE `glpi_ipaddresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `version` tinyint(3) unsigned DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `binary_0` int(10) unsigned NOT NULL DEFAULT 0,
  `binary_1` int(10) unsigned NOT NULL DEFAULT 0,
  `binary_2` int(10) unsigned NOT NULL DEFAULT 0,
  `binary_3` int(10) unsigned NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `mainitems_id` int(11) NOT NULL DEFAULT 0,
  `mainitemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `textual` (`name`),
  KEY `binary` (`binary_0`,`binary_1`,`binary_2`,`binary_3`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `item` (`itemtype`,`items_id`,`is_deleted`),
  KEY `mainitem` (`mainitemtype`,`mainitems_id`,`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ipaddresses_ipnetworks`;
CREATE TABLE `glpi_ipaddresses_ipnetworks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipaddresses_id` int(11) NOT NULL DEFAULT 0,
  `ipnetworks_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`ipaddresses_id`,`ipnetworks_id`),
  KEY `ipnetworks_id` (`ipnetworks_id`),
  KEY `ipaddresses_id` (`ipaddresses_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ipnetworks`;
CREATE TABLE `glpi_ipnetworks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `ipnetworks_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `addressable` tinyint(1) NOT NULL DEFAULT 0,
  `version` tinyint(3) unsigned DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address_0` int(10) unsigned NOT NULL DEFAULT 0,
  `address_1` int(10) unsigned NOT NULL DEFAULT 0,
  `address_2` int(10) unsigned NOT NULL DEFAULT 0,
  `address_3` int(10) unsigned NOT NULL DEFAULT 0,
  `netmask` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `netmask_0` int(10) unsigned NOT NULL DEFAULT 0,
  `netmask_1` int(10) unsigned NOT NULL DEFAULT 0,
  `netmask_2` int(10) unsigned NOT NULL DEFAULT 0,
  `netmask_3` int(10) unsigned NOT NULL DEFAULT 0,
  `gateway` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gateway_0` int(10) unsigned NOT NULL DEFAULT 0,
  `gateway_1` int(10) unsigned NOT NULL DEFAULT 0,
  `gateway_2` int(10) unsigned NOT NULL DEFAULT 0,
  `gateway_3` int(10) unsigned NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `network_definition` (`entities_id`,`address`,`netmask`),
  KEY `address` (`address_0`,`address_1`,`address_2`,`address_3`),
  KEY `netmask` (`netmask_0`,`netmask_1`,`netmask_2`,`netmask_3`),
  KEY `gateway` (`gateway_0`,`gateway_1`,`gateway_2`,`gateway_3`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ipnetworks_vlans`;
CREATE TABLE `glpi_ipnetworks_vlans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipnetworks_id` int(11) NOT NULL DEFAULT 0,
  `vlans_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`ipnetworks_id`,`vlans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_clusters`;
CREATE TABLE `glpi_items_clusters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clusters_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`clusters_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicebatteries`;
CREATE TABLE `glpi_items_devicebatteries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicebatteries_id` int(11) NOT NULL DEFAULT 0,
  `manufacturing_date` date DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicebatteries_id` (`devicebatteries_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicecases`;
CREATE TABLE `glpi_items_devicecases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicecases_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicecases_id` (`devicecases_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicecontrols`;
CREATE TABLE `glpi_items_devicecontrols` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicecontrols_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicecontrols_id` (`devicecontrols_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicedrives`;
CREATE TABLE `glpi_items_devicedrives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicedrives_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicedrives_id` (`devicedrives_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicefirmwares`;
CREATE TABLE `glpi_items_devicefirmwares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicefirmwares_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicefirmwares_id` (`devicefirmwares_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicegenerics`;
CREATE TABLE `glpi_items_devicegenerics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicegenerics_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicegenerics_id` (`devicegenerics_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicegraphiccards`;
CREATE TABLE `glpi_items_devicegraphiccards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicegraphiccards_id` int(11) NOT NULL DEFAULT 0,
  `memory` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicegraphiccards_id` (`devicegraphiccards_id`),
  KEY `specificity` (`memory`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_deviceharddrives`;
CREATE TABLE `glpi_items_deviceharddrives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deviceharddrives_id` int(11) NOT NULL DEFAULT 0,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `deviceharddrives_id` (`deviceharddrives_id`),
  KEY `specificity` (`capacity`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicememories`;
CREATE TABLE `glpi_items_devicememories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicememories_id` int(11) NOT NULL DEFAULT 0,
  `size` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicememories_id` (`devicememories_id`),
  KEY `specificity` (`size`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicemotherboards`;
CREATE TABLE `glpi_items_devicemotherboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicemotherboards_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicemotherboards_id` (`devicemotherboards_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicenetworkcards`;
CREATE TABLE `glpi_items_devicenetworkcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicenetworkcards_id` int(11) NOT NULL DEFAULT 0,
  `mac` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicenetworkcards_id` (`devicenetworkcards_id`),
  KEY `specificity` (`mac`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicepcis`;
CREATE TABLE `glpi_items_devicepcis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicepcis_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicepcis_id` (`devicepcis_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicepowersupplies`;
CREATE TABLE `glpi_items_devicepowersupplies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicepowersupplies_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicepowersupplies_id` (`devicepowersupplies_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_deviceprocessors`;
CREATE TABLE `glpi_items_deviceprocessors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deviceprocessors_id` int(11) NOT NULL DEFAULT 0,
  `frequency` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `nbcores` int(11) DEFAULT NULL,
  `nbthreads` int(11) DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `deviceprocessors_id` (`deviceprocessors_id`),
  KEY `specificity` (`frequency`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `nbcores` (`nbcores`),
  KEY `nbthreads` (`nbthreads`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicesensors`;
CREATE TABLE `glpi_items_devicesensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicesensors_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicesensors_id` (`devicesensors_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicesimcards`;
CREATE TABLE `glpi_items_devicesimcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to various table, according to itemtype (id)',
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `devicesimcards_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `lines_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `pin` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pin2` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `puk` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `puk2` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `msin` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `devicesimcards_id` (`devicesimcards_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `states_id` (`states_id`),
  KEY `locations_id` (`locations_id`),
  KEY `lines_id` (`lines_id`),
  KEY `users_id` (`users_id`),
  KEY `groups_id` (`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_devicesoundcards`;
CREATE TABLE `glpi_items_devicesoundcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `devicesoundcards_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `busID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`items_id`),
  KEY `devicesoundcards_id` (`devicesoundcards_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `serial` (`serial`),
  KEY `busID` (`busID`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `otherserial` (`otherserial`),
  KEY `locations_id` (`locations_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_disks`;
CREATE TABLE `glpi_items_disks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mountpoint` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filesystems_id` int(11) NOT NULL DEFAULT 0,
  `totalsize` int(11) NOT NULL DEFAULT 0,
  `freesize` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `encryption_status` int(11) NOT NULL DEFAULT 0,
  `encryption_tool` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `encryption_algorithm` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `encryption_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `device` (`device`),
  KEY `mountpoint` (`mountpoint`),
  KEY `totalsize` (`totalsize`),
  KEY `freesize` (`freesize`),
  KEY `itemtype` (`itemtype`),
  KEY `items_id` (`items_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `filesystems_id` (`filesystems_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_enclosures`;
CREATE TABLE `glpi_items_enclosures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enclosures_id` int(11) NOT NULL,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item` (`itemtype`,`items_id`),
  KEY `relation` (`enclosures_id`,`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_kanbans`;
CREATE TABLE `glpi_items_kanbans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) DEFAULT NULL,
  `users_id` int(11) NOT NULL,
  `state` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_operatingsystems`;
CREATE TABLE `glpi_items_operatingsystems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `operatingsystems_id` int(11) NOT NULL DEFAULT 0,
  `operatingsystemversions_id` int(11) NOT NULL DEFAULT 0,
  `operatingsystemservicepacks_id` int(11) NOT NULL DEFAULT 0,
  `operatingsystemarchitectures_id` int(11) NOT NULL DEFAULT 0,
  `operatingsystemkernelversions_id` int(11) NOT NULL DEFAULT 0,
  `license_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `licenseid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `operatingsystemeditions_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`items_id`,`itemtype`,`operatingsystems_id`,`operatingsystemarchitectures_id`),
  KEY `items_id` (`items_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `operatingsystems_id` (`operatingsystems_id`),
  KEY `operatingsystemservicepacks_id` (`operatingsystemservicepacks_id`),
  KEY `operatingsystemversions_id` (`operatingsystemversions_id`),
  KEY `operatingsystemarchitectures_id` (`operatingsystemarchitectures_id`),
  KEY `operatingsystemkernelversions_id` (`operatingsystemkernelversions_id`),
  KEY `operatingsystemeditions_id` (`operatingsystemeditions_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_problems`;
CREATE TABLE `glpi_items_problems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`problems_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_projects`;
CREATE TABLE `glpi_items_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projects_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`projects_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_racks`;
CREATE TABLE `glpi_items_racks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `racks_id` int(11) NOT NULL,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `orientation` tinyint(1) DEFAULT NULL,
  `bgcolor` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hpos` tinyint(1) NOT NULL DEFAULT 0,
  `is_reserved` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item` (`itemtype`,`items_id`,`is_reserved`),
  KEY `relation` (`racks_id`,`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_softwarelicenses`;
CREATE TABLE `glpi_items_softwarelicenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `softwarelicenses_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `items_id` (`items_id`),
  KEY `itemtype` (`itemtype`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `softwarelicenses_id` (`softwarelicenses_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_softwareversions`;
CREATE TABLE `glpi_items_softwareversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `softwareversions_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted_item` tinyint(1) NOT NULL DEFAULT 0,
  `is_template_item` tinyint(1) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_install` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`softwareversions_id`),
  KEY `items_id` (`items_id`),
  KEY `itemtype` (`itemtype`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `softwareversions_id` (`softwareversions_id`),
  KEY `computers_info` (`entities_id`,`is_template_item`,`is_deleted_item`),
  KEY `is_template` (`is_template_item`),
  KEY `is_deleted` (`is_deleted_item`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `date_install` (`date_install`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_items_tickets`;
CREATE TABLE `glpi_items_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`tickets_id`),
  KEY `tickets_id` (`tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_itilcategories`;
CREATE TABLE `glpi_itilcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `itilcategories_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `knowbaseitemcategories_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_helpdeskvisible` tinyint(1) NOT NULL DEFAULT 1,
  `tickettemplates_id_incident` int(11) NOT NULL DEFAULT 0,
  `tickettemplates_id_demand` int(11) NOT NULL DEFAULT 0,
  `changetemplates_id` int(11) NOT NULL DEFAULT 0,
  `problemtemplates_id` int(11) NOT NULL DEFAULT 0,
  `is_incident` int(11) NOT NULL DEFAULT 1,
  `is_request` int(11) NOT NULL DEFAULT 1,
  `is_problem` int(11) NOT NULL DEFAULT 1,
  `is_change` tinyint(1) NOT NULL DEFAULT 1,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`),
  KEY `users_id` (`users_id`),
  KEY `groups_id` (`groups_id`),
  KEY `is_helpdeskvisible` (`is_helpdeskvisible`),
  KEY `itilcategories_id` (`itilcategories_id`),
  KEY `tickettemplates_id_incident` (`tickettemplates_id_incident`),
  KEY `tickettemplates_id_demand` (`tickettemplates_id_demand`),
  KEY `changetemplates_id` (`changetemplates_id`),
  KEY `problemtemplates_id` (`problemtemplates_id`),
  KEY `is_incident` (`is_incident`),
  KEY `is_request` (`is_request`),
  KEY `is_problem` (`is_problem`),
  KEY `is_change` (`is_change`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_itilfollowups`;
CREATE TABLE `glpi_itilfollowups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `users_id_editor` int(11) NOT NULL DEFAULT 0,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `requesttypes_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `timeline_position` tinyint(1) NOT NULL DEFAULT 0,
  `sourceitems_id` int(11) NOT NULL DEFAULT 0,
  `sourceof_items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `itemtype` (`itemtype`),
  KEY `item_id` (`items_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `date` (`date`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `users_id` (`users_id`),
  KEY `users_id_editor` (`users_id_editor`),
  KEY `is_private` (`is_private`),
  KEY `requesttypes_id` (`requesttypes_id`),
  KEY `sourceitems_id` (`sourceitems_id`),
  KEY `sourceof_items_id` (`sourceof_items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_itilfollowuptemplates`;
CREATE TABLE `glpi_itilfollowuptemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `requesttypes_id` int(11) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_recursive` (`is_recursive`),
  KEY `requesttypes_id` (`requesttypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `is_private` (`is_private`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_itilsolutions`;
CREATE TABLE `glpi_itilsolutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `solutiontypes_id` int(11) NOT NULL DEFAULT 0,
  `solutiontype_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_approval` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_editor` int(11) NOT NULL DEFAULT 0,
  `users_id_approval` int(11) NOT NULL DEFAULT 0,
  `user_name_approval` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `itilfollowups_id` int(11) DEFAULT NULL COMMENT 'Followup reference on reject or approve a solution',
  PRIMARY KEY (`id`),
  KEY `itemtype` (`itemtype`),
  KEY `item_id` (`items_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `solutiontypes_id` (`solutiontypes_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_editor` (`users_id_editor`),
  KEY `users_id_approval` (`users_id_approval`),
  KEY `status` (`status`),
  KEY `itilfollowups_id` (`itilfollowups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_itils_projects`;
CREATE TABLE `glpi_itils_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `items_id` int(11) NOT NULL DEFAULT 0,
  `projects_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`projects_id`),
  KEY `projects_id` (`projects_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitemcategories`;
CREATE TABLE `glpi_knowbaseitemcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `knowbaseitemcategories_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`entities_id`,`knowbaseitemcategories_id`,`name`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitems`;
CREATE TABLE `glpi_knowbaseitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitemcategories_id` int(11) NOT NULL DEFAULT 0,
  `name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `answer` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_faq` tinyint(1) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `view` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `begin_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`),
  KEY `is_faq` (`is_faq`),
  KEY `date_mod` (`date_mod`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  FULLTEXT KEY `fulltext` (`name`,`answer`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `answer` (`answer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitems_comments`;
CREATE TABLE `glpi_knowbaseitems_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `language` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitems_items`;
CREATE TABLE `glpi_knowbaseitems_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`knowbaseitems_id`),
  KEY `itemtype` (`itemtype`),
  KEY `item_id` (`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitems_profiles`;
CREATE TABLE `glpi_knowbaseitems_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL DEFAULT 0,
  `profiles_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `knowbaseitems_id` (`knowbaseitems_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitems_revisions`;
CREATE TABLE `glpi_knowbaseitems_revisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `answer` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`knowbaseitems_id`,`revision`,`language`),
  KEY `revision` (`revision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitems_users`;
CREATE TABLE `glpi_knowbaseitems_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `knowbaseitems_id` (`knowbaseitems_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_knowbaseitemtranslations`;
CREATE TABLE `glpi_knowbaseitemtranslations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `knowbaseitems_id` int(11) NOT NULL DEFAULT 0,
  `language` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `answer` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`knowbaseitems_id`,`language`),
  KEY `users_id` (`users_id`),
  FULLTEXT KEY `fulltext` (`name`,`answer`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `answer` (`answer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_lineoperators`;
CREATE TABLE `glpi_lineoperators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `mcc` int(11) DEFAULT NULL,
  `mnc` int(11) DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`mcc`,`mnc`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_lines`;
CREATE TABLE `glpi_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `caller_num` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `caller_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `lineoperators_id` int(11) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `linetypes_id` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `users_id` (`users_id`),
  KEY `lineoperators_id` (`lineoperators_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_linetypes`;
CREATE TABLE `glpi_linetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_links`;
CREATE TABLE `glpi_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 1,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `open_window` tinyint(1) NOT NULL DEFAULT 1,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_links_itemtypes`;
CREATE TABLE `glpi_links_itemtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `links_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`links_id`),
  KEY `links_id` (`links_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_locations`;
CREATE TABLE `glpi_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `postcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `town` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `building` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `room` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `altitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`entities_id`,`locations_id`,`name`),
  KEY `locations_id` (`locations_id`),
  KEY `name` (`name`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_logs`;
CREATE TABLE `glpi_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `linked_action` int(11) NOT NULL DEFAULT 0 COMMENT 'see define.php HISTORY_* constant',
  `user_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `id_search_option` int(11) NOT NULL DEFAULT 0 COMMENT 'see search.constant.php for value',
  `old_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `new_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `itemtype_link` (`itemtype_link`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `id_search_option` (`id_search_option`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_logs` (`id`, `itemtype`, `items_id`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES
(1,	'Config',	1,	'',	0,	'',	'2021-01-19 11:02:24',	1,	'version FILLED AT INSTALL',	'9.5.3'),
(2,	'Config',	1,	'',	0,	'',	'2021-01-19 11:02:24',	1,	'dbversion FILLED AT INSTALL',	'9.5.3'),
(3,	'Config',	1,	'',	0,	'',	'2021-01-19 11:02:24',	1,	'use_timezones ',	'1'),
(4,	'PurgeLogs',	0,	'',	12,	'cron_PurgeLogs',	'2021-01-19 11:05:01',	0,	'3',	'3');

DROP TABLE IF EXISTS `glpi_mailcollectors`;
CREATE TABLE `glpi_mailcollectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `login` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filesize_max` int(11) NOT NULL DEFAULT 2097152,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `accepted` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `refused` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `errors` int(11) NOT NULL DEFAULT 0,
  `use_mail_date` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `requester_field` int(11) NOT NULL DEFAULT 0,
  `add_cc_to_observer` tinyint(1) NOT NULL DEFAULT 0,
  `collect_only_unread` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_manufacturers`;
CREATE TABLE `glpi_manufacturers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_monitormodels`;
CREATE TABLE `glpi_monitormodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `required_units` int(11) NOT NULL DEFAULT 1,
  `depth` float NOT NULL DEFAULT 1,
  `power_connections` int(11) NOT NULL DEFAULT 0,
  `power_consumption` int(11) NOT NULL DEFAULT 0,
  `is_half_rack` tinyint(1) NOT NULL DEFAULT 0,
  `picture_front` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture_rear` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_monitors`;
CREATE TABLE `glpi_monitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` decimal(5,2) NOT NULL DEFAULT 0.00,
  `have_micro` tinyint(1) NOT NULL DEFAULT 0,
  `have_speaker` tinyint(1) NOT NULL DEFAULT 0,
  `have_subd` tinyint(1) NOT NULL DEFAULT 0,
  `have_bnc` tinyint(1) NOT NULL DEFAULT 0,
  `have_dvi` tinyint(1) NOT NULL DEFAULT 0,
  `have_pivot` tinyint(1) NOT NULL DEFAULT 0,
  `have_hdmi` tinyint(1) NOT NULL DEFAULT 0,
  `have_displayport` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `monitortypes_id` int(11) NOT NULL DEFAULT 0,
  `monitormodels_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `ticket_tco` decimal(20,4) DEFAULT 0.0000,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `monitormodels_id` (`monitormodels_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `monitortypes_id` (`monitortypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `date_creation` (`date_creation`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_monitortypes`;
CREATE TABLE `glpi_monitortypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_netpoints`;
CREATE TABLE `glpi_netpoints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `complete` (`entities_id`,`locations_id`,`name`),
  KEY `location_name` (`locations_id`,`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkaliases`;
CREATE TABLE `glpi_networkaliases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `networknames_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fqdns_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `name` (`name`),
  KEY `networknames_id` (`networknames_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkequipmentmodels`;
CREATE TABLE `glpi_networkequipmentmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `required_units` int(11) NOT NULL DEFAULT 1,
  `depth` float NOT NULL DEFAULT 1,
  `power_connections` int(11) NOT NULL DEFAULT 0,
  `power_consumption` int(11) NOT NULL DEFAULT 0,
  `is_half_rack` tinyint(1) NOT NULL DEFAULT 0,
  `picture_front` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture_rear` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkequipments`;
CREATE TABLE `glpi_networkequipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ram` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `networks_id` int(11) NOT NULL DEFAULT 0,
  `networkequipmenttypes_id` int(11) NOT NULL DEFAULT 0,
  `networkequipmentmodels_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `ticket_tco` decimal(20,4) DEFAULT 0.0000,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `networkequipmentmodels_id` (`networkequipmentmodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `networkequipmenttypes_id` (`networkequipmenttypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkequipmenttypes`;
CREATE TABLE `glpi_networkequipmenttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkinterfaces`;
CREATE TABLE `glpi_networkinterfaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networknames`;
CREATE TABLE `glpi_networknames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `fqdns_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `FQDN` (`name`,`fqdns_id`),
  KEY `name` (`name`),
  KEY `fqdns_id` (`fqdns_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `item` (`itemtype`,`items_id`,`is_deleted`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkportaggregates`;
CREATE TABLE `glpi_networkportaggregates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `networkports_id_list` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'array of associated networkports_id',
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkportaliases`;
CREATE TABLE `glpi_networkportaliases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `networkports_id_alias` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `networkports_id_alias` (`networkports_id_alias`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkportdialups`;
CREATE TABLE `glpi_networkportdialups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkportethernets`;
CREATE TABLE `glpi_networkportethernets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `items_devicenetworkcards_id` int(11) NOT NULL DEFAULT 0,
  `netpoints_id` int(11) NOT NULL DEFAULT 0,
  `type` varchar(10) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'T, LX, SX',
  `speed` int(11) NOT NULL DEFAULT 10 COMMENT 'Mbit/s: 10, 100, 1000, 10000',
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `card` (`items_devicenetworkcards_id`),
  KEY `netpoint` (`netpoints_id`),
  KEY `type` (`type`),
  KEY `speed` (`speed`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkportfiberchannels`;
CREATE TABLE `glpi_networkportfiberchannels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `items_devicenetworkcards_id` int(11) NOT NULL DEFAULT 0,
  `netpoints_id` int(11) NOT NULL DEFAULT 0,
  `wwn` varchar(16) COLLATE utf8_unicode_ci DEFAULT '',
  `speed` int(11) NOT NULL DEFAULT 10 COMMENT 'Mbit/s: 10, 100, 1000, 10000',
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `card` (`items_devicenetworkcards_id`),
  KEY `netpoint` (`netpoints_id`),
  KEY `wwn` (`wwn`),
  KEY `speed` (`speed`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkportlocals`;
CREATE TABLE `glpi_networkportlocals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkports`;
CREATE TABLE `glpi_networkports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `logical_number` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `instantiation_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mac` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `on_device` (`items_id`,`itemtype`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `mac` (`mac`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkports_networkports`;
CREATE TABLE `glpi_networkports_networkports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id_1` int(11) NOT NULL DEFAULT 0,
  `networkports_id_2` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`networkports_id_1`,`networkports_id_2`),
  KEY `networkports_id_2` (`networkports_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkports_vlans`;
CREATE TABLE `glpi_networkports_vlans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `vlans_id` int(11) NOT NULL DEFAULT 0,
  `tagged` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`networkports_id`,`vlans_id`),
  KEY `vlans_id` (`vlans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networkportwifis`;
CREATE TABLE `glpi_networkportwifis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT 0,
  `items_devicenetworkcards_id` int(11) NOT NULL DEFAULT 0,
  `wifinetworks_id` int(11) NOT NULL DEFAULT 0,
  `networkportwifis_id` int(11) NOT NULL DEFAULT 0 COMMENT 'only useful in case of Managed node',
  `version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'a, a/b, a/b/g, a/b/g/n, a/b/g/n/y',
  `mode` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'ad-hoc, managed, master, repeater, secondary, monitor, auto',
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `card` (`items_devicenetworkcards_id`),
  KEY `essid` (`wifinetworks_id`),
  KEY `version` (`version`),
  KEY `mode` (`mode`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_networks`;
CREATE TABLE `glpi_networks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_notepads`;
CREATE TABLE `glpi_notepads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `users_id_lastupdater` int(11) NOT NULL DEFAULT 0,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date` (`date`),
  KEY `users_id_lastupdater` (`users_id_lastupdater`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_notifications`;
CREATE TABLE `glpi_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `event` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `allow_response` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `itemtype` (`itemtype`),
  KEY `entities_id` (`entities_id`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notifications` (`id`, `name`, `entities_id`, `itemtype`, `event`, `comment`, `is_recursive`, `is_active`, `date_mod`, `date_creation`, `allow_response`) VALUES
(1,	'Alert Tickets not closed',	0,	'Ticket',	'alertnotclosed',	NULL,	1,	1,	NULL,	NULL,	1),
(2,	'New Ticket',	0,	'Ticket',	'new',	NULL,	1,	1,	NULL,	NULL,	1),
(3,	'Update Ticket',	0,	'Ticket',	'update',	NULL,	1,	0,	NULL,	NULL,	1),
(4,	'Close Ticket',	0,	'Ticket',	'closed',	NULL,	1,	1,	NULL,	NULL,	1),
(5,	'Add Followup',	0,	'Ticket',	'add_followup',	NULL,	1,	1,	NULL,	NULL,	1),
(6,	'Add Task',	0,	'Ticket',	'add_task',	NULL,	1,	1,	NULL,	NULL,	1),
(7,	'Update Followup',	0,	'Ticket',	'update_followup',	NULL,	1,	1,	NULL,	NULL,	1),
(8,	'Update Task',	0,	'Ticket',	'update_task',	NULL,	1,	1,	NULL,	NULL,	1),
(9,	'Delete Followup',	0,	'Ticket',	'delete_followup',	NULL,	1,	1,	NULL,	NULL,	1),
(10,	'Delete Task',	0,	'Ticket',	'delete_task',	NULL,	1,	1,	NULL,	NULL,	1),
(11,	'Resolve ticket',	0,	'Ticket',	'solved',	NULL,	1,	1,	NULL,	NULL,	1),
(12,	'Ticket Validation',	0,	'Ticket',	'validation',	NULL,	1,	1,	NULL,	NULL,	1),
(13,	'New Reservation',	0,	'Reservation',	'new',	NULL,	1,	1,	NULL,	NULL,	1),
(14,	'Update Reservation',	0,	'Reservation',	'update',	NULL,	1,	1,	NULL,	NULL,	1),
(15,	'Delete Reservation',	0,	'Reservation',	'delete',	NULL,	1,	1,	NULL,	NULL,	1),
(16,	'Alert Reservation',	0,	'Reservation',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(17,	'Contract Notice',	0,	'Contract',	'notice',	NULL,	1,	1,	NULL,	NULL,	1),
(18,	'Contract End',	0,	'Contract',	'end',	NULL,	1,	1,	NULL,	NULL,	1),
(19,	'MySQL Synchronization',	0,	'DBConnection',	'desynchronization',	NULL,	1,	1,	NULL,	NULL,	1),
(20,	'Cartridges',	0,	'CartridgeItem',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(21,	'Consumables',	0,	'ConsumableItem',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(22,	'Infocoms',	0,	'Infocom',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(23,	'Software Licenses',	0,	'SoftwareLicense',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(24,	'Ticket Recall',	0,	'Ticket',	'recall',	NULL,	1,	1,	NULL,	NULL,	1),
(25,	'Password Forget',	0,	'User',	'passwordforget',	NULL,	1,	1,	NULL,	NULL,	1),
(26,	'Ticket Satisfaction',	0,	'Ticket',	'satisfaction',	NULL,	1,	1,	NULL,	NULL,	1),
(27,	'Item not unique',	0,	'FieldUnicity',	'refuse',	NULL,	1,	1,	NULL,	NULL,	1),
(28,	'CronTask Watcher',	0,	'CronTask',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(29,	'New Problem',	0,	'Problem',	'new',	NULL,	1,	1,	NULL,	NULL,	1),
(30,	'Update Problem',	0,	'Problem',	'update',	NULL,	1,	1,	NULL,	NULL,	1),
(31,	'Resolve Problem',	0,	'Problem',	'solved',	NULL,	1,	1,	NULL,	NULL,	1),
(32,	'Add Task',	0,	'Problem',	'add_task',	NULL,	1,	1,	NULL,	NULL,	1),
(33,	'Update Task',	0,	'Problem',	'update_task',	NULL,	1,	1,	NULL,	NULL,	1),
(34,	'Delete Task',	0,	'Problem',	'delete_task',	NULL,	1,	1,	NULL,	NULL,	1),
(35,	'Close Problem',	0,	'Problem',	'closed',	NULL,	1,	1,	NULL,	NULL,	1),
(36,	'Delete Problem',	0,	'Problem',	'delete',	NULL,	1,	1,	NULL,	NULL,	1),
(37,	'Ticket Validation Answer',	0,	'Ticket',	'validation_answer',	NULL,	1,	1,	NULL,	NULL,	1),
(38,	'Contract End Periodicity',	0,	'Contract',	'periodicity',	NULL,	1,	1,	NULL,	NULL,	1),
(39,	'Contract Notice Periodicity',	0,	'Contract',	'periodicitynotice',	NULL,	1,	1,	NULL,	NULL,	1),
(40,	'Planning recall',	0,	'PlanningRecall',	'planningrecall',	NULL,	1,	1,	NULL,	NULL,	1),
(41,	'Delete Ticket',	0,	'Ticket',	'delete',	NULL,	1,	1,	NULL,	NULL,	1),
(42,	'New Change',	0,	'Change',	'new',	NULL,	1,	1,	NULL,	NULL,	1),
(43,	'Update Change',	0,	'Change',	'update',	NULL,	1,	1,	NULL,	NULL,	1),
(44,	'Resolve Change',	0,	'Change',	'solved',	NULL,	1,	1,	NULL,	NULL,	1),
(45,	'Add Task',	0,	'Change',	'add_task',	NULL,	1,	1,	NULL,	NULL,	1),
(46,	'Update Task',	0,	'Change',	'update_task',	NULL,	1,	1,	NULL,	NULL,	1),
(47,	'Delete Task',	0,	'Change',	'delete_task',	NULL,	1,	1,	NULL,	NULL,	1),
(48,	'Close Change',	0,	'Change',	'closed',	NULL,	1,	1,	NULL,	NULL,	1),
(49,	'Delete Change',	0,	'Change',	'delete',	NULL,	1,	1,	NULL,	NULL,	1),
(50,	'Ticket Satisfaction Answer',	0,	'Ticket',	'replysatisfaction',	NULL,	1,	1,	NULL,	NULL,	1),
(51,	'Receiver errors',	0,	'MailCollector',	'error',	NULL,	1,	1,	NULL,	NULL,	1),
(52,	'New Project',	0,	'Project',	'new',	NULL,	1,	1,	NULL,	NULL,	1),
(53,	'Update Project',	0,	'Project',	'update',	NULL,	1,	1,	NULL,	NULL,	1),
(54,	'Delete Project',	0,	'Project',	'delete',	NULL,	1,	1,	NULL,	NULL,	1),
(55,	'New Project Task',	0,	'ProjectTask',	'new',	NULL,	1,	1,	NULL,	NULL,	1),
(56,	'Update Project Task',	0,	'ProjectTask',	'update',	NULL,	1,	1,	NULL,	NULL,	1),
(57,	'Delete Project Task',	0,	'ProjectTask',	'delete',	NULL,	1,	1,	NULL,	NULL,	1),
(58,	'Request Unlock Items',	0,	'ObjectLock',	'unlock',	NULL,	1,	1,	NULL,	NULL,	1),
(59,	'New user in requesters',	0,	'Ticket',	'requester_user',	NULL,	1,	1,	NULL,	NULL,	1),
(60,	'New group in requesters',	0,	'Ticket',	'requester_group',	NULL,	1,	1,	NULL,	NULL,	1),
(61,	'New user in observers',	0,	'Ticket',	'observer_user',	NULL,	1,	1,	NULL,	NULL,	1),
(62,	'New group in observers',	0,	'Ticket',	'observer_group',	NULL,	1,	1,	NULL,	NULL,	1),
(63,	'New user in assignees',	0,	'Ticket',	'assign_user',	NULL,	1,	1,	NULL,	NULL,	1),
(64,	'New group in assignees',	0,	'Ticket',	'assign_group',	NULL,	1,	1,	NULL,	NULL,	1),
(65,	'New supplier in assignees',	0,	'Ticket',	'assign_supplier',	NULL,	1,	1,	NULL,	NULL,	1),
(66,	'Saved searches',	0,	'SavedSearch_Alert',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(67,	'Certificates',	0,	'Certificate',	'alert',	NULL,	1,	1,	NULL,	NULL,	1),
(68,	'Alert expired domains',	0,	'Domain',	'ExpiredDomains',	NULL,	1,	1,	NULL,	NULL,	1),
(69,	'Alert domains close expiries',	0,	'Domain',	'DomainsWhichExpire',	NULL,	1,	1,	NULL,	NULL,	1),
(70,	'Password expires alert',	0,	'User',	'passwordexpires',	NULL,	1,	1,	NULL,	NULL,	1),
(71,	'Check plugin updates',	0,	'Glpi\\Marketplace\\Controller',	'checkpluginsupdate',	NULL,	1,	1,	NULL,	NULL,	1);

DROP TABLE IF EXISTS `glpi_notifications_notificationtemplates`;
CREATE TABLE `glpi_notifications_notificationtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notifications_id` int(11) NOT NULL DEFAULT 0,
  `mode` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'See Notification_NotificationTemplate::MODE_* constants',
  `notificationtemplates_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`notifications_id`,`mode`,`notificationtemplates_id`),
  KEY `notifications_id` (`notifications_id`),
  KEY `notificationtemplates_id` (`notificationtemplates_id`),
  KEY `mode` (`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notifications_notificationtemplates` (`id`, `notifications_id`, `mode`, `notificationtemplates_id`) VALUES
(1,	1,	'mailing',	6),
(2,	2,	'mailing',	4),
(3,	3,	'mailing',	4),
(4,	4,	'mailing',	4),
(5,	5,	'mailing',	4),
(6,	6,	'mailing',	4),
(7,	7,	'mailing',	4),
(8,	8,	'mailing',	4),
(9,	9,	'mailing',	4),
(10,	10,	'mailing',	4),
(11,	11,	'mailing',	4),
(12,	12,	'mailing',	7),
(13,	13,	'mailing',	2),
(14,	14,	'mailing',	2),
(15,	15,	'mailing',	2),
(16,	16,	'mailing',	3),
(17,	17,	'mailing',	12),
(18,	18,	'mailing',	12),
(19,	19,	'mailing',	1),
(20,	20,	'mailing',	8),
(21,	21,	'mailing',	9),
(22,	22,	'mailing',	10),
(23,	23,	'mailing',	11),
(24,	24,	'mailing',	4),
(25,	25,	'mailing',	13),
(26,	26,	'mailing',	14),
(27,	27,	'mailing',	15),
(28,	28,	'mailing',	16),
(29,	29,	'mailing',	17),
(30,	30,	'mailing',	17),
(31,	31,	'mailing',	17),
(32,	32,	'mailing',	17),
(33,	33,	'mailing',	17),
(34,	34,	'mailing',	17),
(35,	35,	'mailing',	17),
(36,	36,	'mailing',	17),
(37,	37,	'mailing',	7),
(38,	38,	'mailing',	12),
(39,	39,	'mailing',	12),
(40,	40,	'mailing',	18),
(41,	41,	'mailing',	4),
(42,	42,	'mailing',	19),
(43,	43,	'mailing',	19),
(44,	44,	'mailing',	19),
(45,	45,	'mailing',	19),
(46,	46,	'mailing',	19),
(47,	47,	'mailing',	19),
(48,	48,	'mailing',	19),
(49,	49,	'mailing',	19),
(50,	50,	'mailing',	14),
(51,	51,	'mailing',	20),
(52,	52,	'mailing',	21),
(53,	53,	'mailing',	21),
(54,	54,	'mailing',	21),
(55,	55,	'mailing',	22),
(56,	56,	'mailing',	22),
(57,	57,	'mailing',	22),
(58,	58,	'mailing',	23),
(59,	59,	'mailing',	4),
(60,	60,	'mailing',	4),
(61,	61,	'mailing',	4),
(62,	62,	'mailing',	4),
(63,	63,	'mailing',	4),
(64,	64,	'mailing',	4),
(65,	65,	'mailing',	4),
(66,	66,	'mailing',	24),
(67,	67,	'mailing',	25),
(68,	68,	'mailing',	26),
(69,	69,	'mailing',	26),
(70,	70,	'mailing',	27),
(71,	71,	'mailing',	28);

DROP TABLE IF EXISTS `glpi_notificationtargets`;
CREATE TABLE `glpi_notificationtargets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0,
  `notifications_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `items` (`type`,`items_id`),
  KEY `notifications_id` (`notifications_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtargets` (`id`, `items_id`, `type`, `notifications_id`) VALUES
(1,	3,	1,	13),
(2,	1,	1,	13),
(3,	3,	2,	2),
(4,	1,	1,	2),
(5,	1,	1,	3),
(6,	1,	1,	5),
(7,	1,	1,	4),
(8,	2,	1,	3),
(9,	4,	1,	3),
(10,	3,	1,	2),
(11,	3,	1,	3),
(12,	3,	1,	5),
(13,	3,	1,	4),
(14,	1,	1,	19),
(15,	14,	1,	12),
(16,	3,	1,	14),
(17,	1,	1,	14),
(18,	3,	1,	15),
(19,	1,	1,	15),
(20,	1,	1,	6),
(21,	3,	1,	6),
(22,	1,	1,	7),
(23,	3,	1,	7),
(24,	1,	1,	8),
(25,	3,	1,	8),
(26,	1,	1,	9),
(27,	3,	1,	9),
(28,	1,	1,	10),
(29,	3,	1,	10),
(30,	1,	1,	11),
(31,	3,	1,	11),
(32,	19,	1,	25),
(33,	3,	1,	26),
(34,	21,	1,	2),
(35,	21,	1,	3),
(36,	21,	1,	5),
(37,	21,	1,	4),
(38,	21,	1,	6),
(39,	21,	1,	7),
(40,	21,	1,	8),
(41,	21,	1,	9),
(42,	21,	1,	10),
(43,	21,	1,	11),
(46,	1,	1,	28),
(47,	3,	1,	29),
(48,	1,	1,	29),
(49,	21,	1,	29),
(50,	2,	1,	30),
(51,	4,	1,	30),
(52,	3,	1,	30),
(53,	1,	1,	30),
(54,	21,	1,	30),
(55,	3,	1,	31),
(56,	1,	1,	31),
(57,	21,	1,	31),
(58,	3,	1,	32),
(59,	1,	1,	32),
(60,	21,	1,	32),
(61,	3,	1,	33),
(62,	1,	1,	33),
(63,	21,	1,	33),
(64,	3,	1,	34),
(65,	1,	1,	34),
(66,	21,	1,	34),
(67,	3,	1,	35),
(68,	1,	1,	35),
(69,	21,	1,	35),
(70,	3,	1,	36),
(71,	1,	1,	36),
(72,	21,	1,	36),
(73,	14,	1,	37),
(74,	3,	1,	40),
(75,	1,	1,	41),
(76,	3,	1,	42),
(77,	1,	1,	42),
(78,	21,	1,	42),
(79,	2,	1,	43),
(80,	4,	1,	43),
(81,	3,	1,	43),
(82,	1,	1,	43),
(83,	21,	1,	43),
(84,	3,	1,	44),
(85,	1,	1,	44),
(86,	21,	1,	44),
(87,	3,	1,	45),
(88,	1,	1,	45),
(89,	21,	1,	45),
(90,	3,	1,	46),
(91,	1,	1,	46),
(92,	21,	1,	46),
(93,	3,	1,	47),
(94,	1,	1,	47),
(95,	21,	1,	47),
(96,	3,	1,	48),
(97,	1,	1,	48),
(98,	21,	1,	48),
(99,	3,	1,	49),
(100,	1,	1,	49),
(101,	21,	1,	49),
(102,	3,	1,	50),
(103,	2,	1,	50),
(104,	1,	1,	51),
(105,	27,	1,	52),
(106,	1,	1,	52),
(107,	28,	1,	52),
(108,	27,	1,	53),
(109,	1,	1,	53),
(110,	28,	1,	53),
(111,	27,	1,	54),
(112,	1,	1,	54),
(113,	28,	1,	54),
(114,	31,	1,	55),
(115,	1,	1,	55),
(116,	32,	1,	55),
(117,	31,	1,	56),
(118,	1,	1,	56),
(119,	32,	1,	56),
(120,	31,	1,	57),
(121,	1,	1,	57),
(122,	32,	1,	57),
(123,	19,	1,	58),
(124,	3,	1,	59),
(125,	13,	1,	60),
(126,	21,	1,	61),
(127,	20,	1,	62),
(128,	2,	1,	63),
(129,	23,	1,	64),
(130,	8,	1,	65),
(131,	19,	1,	66),
(132,	5,	1,	67),
(133,	23,	1,	67),
(134,	5,	1,	68),
(135,	23,	1,	68),
(136,	5,	1,	69),
(137,	23,	1,	69),
(138,	19,	1,	70),
(139,	1,	1,	71);

DROP TABLE IF EXISTS `glpi_notificationtemplates`;
CREATE TABLE `glpi_notificationtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `css` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `itemtype` (`itemtype`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtemplates` (`id`, `name`, `itemtype`, `date_mod`, `comment`, `css`, `date_creation`) VALUES
(1,	'MySQL Synchronization',	'DBConnection',	NULL,	NULL,	NULL,	NULL),
(2,	'Reservations',	'Reservation',	NULL,	NULL,	NULL,	NULL),
(3,	'Alert Reservation',	'Reservation',	NULL,	NULL,	NULL,	NULL),
(4,	'Tickets',	'Ticket',	NULL,	NULL,	NULL,	NULL),
(5,	'Tickets (Simple)',	'Ticket',	NULL,	NULL,	NULL,	NULL),
(6,	'Alert Tickets not closed',	'Ticket',	NULL,	NULL,	NULL,	NULL),
(7,	'Tickets Validation',	'Ticket',	NULL,	NULL,	NULL,	NULL),
(8,	'Cartridges',	'CartridgeItem',	NULL,	NULL,	NULL,	NULL),
(9,	'Consumables',	'ConsumableItem',	NULL,	NULL,	NULL,	NULL),
(10,	'Infocoms',	'Infocom',	NULL,	NULL,	NULL,	NULL),
(11,	'Licenses',	'SoftwareLicense',	NULL,	NULL,	NULL,	NULL),
(12,	'Contracts',	'Contract',	NULL,	NULL,	NULL,	NULL),
(13,	'Password Forget',	'User',	NULL,	NULL,	NULL,	NULL),
(14,	'Ticket Satisfaction',	'Ticket',	NULL,	NULL,	NULL,	NULL),
(15,	'Item not unique',	'FieldUnicity',	NULL,	NULL,	NULL,	NULL),
(16,	'CronTask',	'CronTask',	NULL,	NULL,	NULL,	NULL),
(17,	'Problems',	'Problem',	NULL,	NULL,	NULL,	NULL),
(18,	'Planning recall',	'PlanningRecall',	NULL,	NULL,	NULL,	NULL),
(19,	'Changes',	'Change',	NULL,	NULL,	NULL,	NULL),
(20,	'Receiver errors',	'MailCollector',	NULL,	NULL,	NULL,	NULL),
(21,	'Projects',	'Project',	NULL,	NULL,	NULL,	NULL),
(22,	'Project Tasks',	'ProjectTask',	NULL,	NULL,	NULL,	NULL),
(23,	'Unlock Item request',	'ObjectLock',	NULL,	NULL,	NULL,	NULL),
(24,	'Saved searches alerts',	'SavedSearch_Alert',	NULL,	NULL,	NULL,	NULL),
(25,	'Certificates',	'Certificate',	NULL,	NULL,	NULL,	NULL),
(26,	'Alert domains',	'Domain',	NULL,	NULL,	NULL,	NULL),
(27,	'Password expires alert',	'User',	NULL,	NULL,	NULL,	NULL),
(28,	'Plugin updates',	'Glpi\\Marketplace\\Controller',	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_notificationtemplatetranslations`;
CREATE TABLE `glpi_notificationtemplatetranslations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificationtemplates_id` int(11) NOT NULL DEFAULT 0,
  `language` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content_text` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `content_html` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notificationtemplates_id` (`notificationtemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtemplatetranslations` (`id`, `notificationtemplates_id`, `language`, `subject`, `content_text`, `content_html`) VALUES
(1,	1,	'',	'##lang.dbconnection.title##',	'##lang.dbconnection.delay## : ##dbconnection.delay##',	'&lt;p&gt;##lang.dbconnection.delay## : ##dbconnection.delay##&lt;/p&gt;'),
(2,	2,	'',	'##reservation.action##',	'======================================================================\n##lang.reservation.user##: ##reservation.user##\n##lang.reservation.item.name##: ##reservation.itemtype## - ##reservation.item.name##\n##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech## ##ENDIFreservation.tech##\n##lang.reservation.begin##: ##reservation.begin##\n##lang.reservation.end##: ##reservation.end##\n##lang.reservation.comment##: ##reservation.comment##\n======================================================================',	'&lt;!-- description{ color: inherit; background: #ebebeb;border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; } --&gt;\n&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.user##:&lt;/span&gt;##reservation.user##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.item.name##:&lt;/span&gt;##reservation.itemtype## - ##reservation.item.name##&lt;br /&gt;##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech####ENDIFreservation.tech##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.begin##:&lt;/span&gt; ##reservation.begin##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.end##:&lt;/span&gt;##reservation.end##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.comment##:&lt;/span&gt; ##reservation.comment##&lt;/p&gt;'),
(3,	3,	'',	'##reservation.action##  ##reservation.entity##',	'##lang.reservation.entity## : ##reservation.entity##\n\n\n##FOREACHreservations##\n##lang.reservation.itemtype## : ##reservation.itemtype##\n\n ##lang.reservation.item## : ##reservation.item##\n\n ##reservation.url##\n\n ##ENDFOREACHreservations##',	'&lt;p&gt;##lang.reservation.entity## : ##reservation.entity## &lt;br /&gt; &lt;br /&gt;\n##FOREACHreservations## &lt;br /&gt;##lang.reservation.itemtype## :  ##reservation.itemtype##&lt;br /&gt;\n ##lang.reservation.item## :  ##reservation.item##&lt;br /&gt; &lt;br /&gt;\n &lt;a href=\"##reservation.url##\"&gt; ##reservation.url##&lt;/a&gt;&lt;br /&gt;\n ##ENDFOREACHreservations##&lt;/p&gt;'),
(4,	4,	'',	'##ticket.action## ##ticket.title##',	' ##IFticket.storestatus=5##\n ##lang.ticket.url## : ##ticket.urlapprove##\n ##lang.ticket.autoclosewarning##\n ##lang.ticket.solvedate## : ##ticket.solvedate##\n ##lang.ticket.solution.type## : ##ticket.solution.type##\n ##lang.ticket.solution.description## : ##ticket.solution.description## ##ENDIFticket.storestatus##\n ##ELSEticket.storestatus## ##lang.ticket.url## : ##ticket.url## ##ENDELSEticket.storestatus##\n\n ##lang.ticket.description##\n\n ##lang.ticket.title## : ##ticket.title##\n ##lang.ticket.authors## : ##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors## ##ELSEticket.authors##--##ENDELSEticket.authors##\n ##lang.ticket.creationdate## : ##ticket.creationdate##\n ##lang.ticket.closedate## : ##ticket.closedate##\n ##lang.ticket.requesttype## : ##ticket.requesttype##\n##lang.ticket.item.name## :\n\n##FOREACHitems##\n\n ##IFticket.itemtype##\n  ##ticket.itemtype## - ##ticket.item.name##\n  ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model##\n  ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial##\n  ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial##\n ##ENDIFticket.itemtype##\n\n##ENDFOREACHitems##\n##IFticket.assigntousers## ##lang.ticket.assigntousers## : ##ticket.assigntousers## ##ENDIFticket.assigntousers##\n ##lang.ticket.status## : ##ticket.status##\n##IFticket.assigntogroups## ##lang.ticket.assigntogroups## : ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##\n ##lang.ticket.urgency## : ##ticket.urgency##\n ##lang.ticket.impact## : ##ticket.impact##\n ##lang.ticket.priority## : ##ticket.priority##\n##IFticket.user.email## ##lang.ticket.user.email## : ##ticket.user.email ##ENDIFticket.user.email##\n##IFticket.category## ##lang.ticket.category## : ##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##\n ##lang.ticket.content## : ##ticket.content##\n ##IFticket.storestatus=6##\n\n ##lang.ticket.solvedate## : ##ticket.solvedate##\n ##lang.ticket.solution.type## : ##ticket.solution.type##\n ##lang.ticket.solution.description## : ##ticket.solution.description##\n ##ENDIFticket.storestatus##\n ##lang.ticket.numberoffollowups## : ##ticket.numberoffollowups##\n\n##FOREACHfollowups##\n\n [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##\n ##lang.followup.author## ##followup.author##\n ##lang.followup.description## ##followup.description##\n ##lang.followup.date## ##followup.date##\n ##lang.followup.requesttype## ##followup.requesttype##\n\n##ENDFOREACHfollowups##\n ##lang.ticket.numberoftasks## : ##ticket.numberoftasks##\n\n##FOREACHtasks##\n\n [##task.date##] ##lang.task.isprivate## : ##task.isprivate##\n ##lang.task.author## ##task.author##\n ##lang.task.description## ##task.description##\n ##lang.task.time## ##task.time##\n ##lang.task.category## ##task.category##\n\n##ENDFOREACHtasks##',	'<!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }    -->\n<div>##IFticket.storestatus=5##</div>\n<div>##lang.ticket.url## : <a href=\"##ticket.urlapprove##\">##ticket.urlapprove##</a> <strong>&#160;</strong></div>\n<div><strong>##lang.ticket.autoclosewarning##</strong></div>\n<div><span style=\"color: #888888;\"><strong><span style=\"text-decoration: underline;\">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style=\"text-decoration: underline; color: #888888;\"><strong>##lang.ticket.solution.type##</strong></span> : ##ticket.solution.type##<br /><span style=\"text-decoration: underline; color: #888888;\"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description## ##ENDIFticket.storestatus##</div>\n<div>##ELSEticket.storestatus## ##lang.ticket.url## : <a href=\"##ticket.url##\">##ticket.url##</a> ##ENDELSEticket.storestatus##</div>\n<p class=\"description b\"><strong>##lang.ticket.description##</strong></p>\n<p><span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.title##</span>&#160;:##ticket.title## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.authors##</span>&#160;:##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors##    ##ELSEticket.authors##--##ENDELSEticket.authors## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.creationdate##</span>&#160;:##ticket.creationdate## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.closedate##</span>&#160;:##ticket.closedate## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.requesttype##</span>&#160;:##ticket.requesttype##<br />\n<br /><span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.item.name##</span>&#160;:\n<p>##FOREACHitems##</p>\n<div class=\"description b\">##IFticket.itemtype## ##ticket.itemtype##&#160;- ##ticket.item.name## ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model## ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial## ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype## </div><br />\n<p>##ENDFOREACHitems##</p>\n##IFticket.assigntousers## <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.assigntousers##</span>&#160;: ##ticket.assigntousers## ##ENDIFticket.assigntousers##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\">##lang.ticket.status## </span>&#160;: ##ticket.status##<br /> ##IFticket.assigntogroups## <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.assigntogroups##</span>&#160;: ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.urgency##</span>&#160;: ##ticket.urgency##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.impact##</span>&#160;: ##ticket.impact##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.priority##</span>&#160;: ##ticket.priority## <br /> ##IFticket.user.email##<span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.user.email##</span>&#160;: ##ticket.user.email ##ENDIFticket.user.email##    <br /> ##IFticket.category##<span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\">##lang.ticket.category## </span>&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##    <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.content##</span>&#160;: ##ticket.content##</p>\n<br />##IFticket.storestatus=6##<br /><span style=\"text-decoration: underline;\"><strong><span style=\"color: #888888;\">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style=\"color: #888888;\"><strong><span style=\"text-decoration: underline;\">##lang.ticket.solution.type##</span></strong></span> : ##ticket.solution.type##<br /><span style=\"text-decoration: underline; color: #888888;\"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description##<br />##ENDIFticket.storestatus##</p>\n<div class=\"description b\">##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##</div>\n<p>##FOREACHfollowups##</p>\n<div class=\"description b\"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.author## </span> ##followup.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.description## </span> ##followup.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.date## </span> ##followup.date##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>\n<p>##ENDFOREACHfollowups##</p>\n<div class=\"description b\">##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##</div>\n<p>##FOREACHtasks##</p>\n<div class=\"description b\"><br /> <strong> [##task.date##] <em>##lang.task.isprivate## : ##task.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.author##</span> ##task.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.description##</span> ##task.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.time##</span> ##task.time##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.category##</span> ##task.category##</div>\n<p>##ENDFOREACHtasks##</p>'),
(5,	12,	'',	'##contract.action##  ##contract.entity##',	'##lang.contract.entity## : ##contract.entity##\n\n##FOREACHcontracts##\n##lang.contract.name## : ##contract.name##\n##lang.contract.number## : ##contract.number##\n##lang.contract.time## : ##contract.time##\n##IFcontract.type####lang.contract.type## : ##contract.type####ENDIFcontract.type##\n##contract.url##\n##ENDFOREACHcontracts##',	'&lt;p&gt;##lang.contract.entity## : ##contract.entity##&lt;br /&gt;\n&lt;br /&gt;##FOREACHcontracts##&lt;br /&gt;##lang.contract.name## :\n##contract.name##&lt;br /&gt;\n##lang.contract.number## : ##contract.number##&lt;br /&gt;\n##lang.contract.time## : ##contract.time##&lt;br /&gt;\n##IFcontract.type####lang.contract.type## : ##contract.type##\n##ENDIFcontract.type##&lt;br /&gt;\n&lt;a href=\"##contract.url##\"&gt;\n##contract.url##&lt;/a&gt;&lt;br /&gt;\n##ENDFOREACHcontracts##&lt;/p&gt;'),
(6,	5,	'',	'##ticket.action## ##ticket.title##',	'##lang.ticket.url## : ##ticket.url##\n\n##lang.ticket.description##\n\n\n##lang.ticket.title##  :##ticket.title##\n\n##lang.ticket.authors##  :##IFticket.authors##\n##ticket.authors## ##ENDIFticket.authors##\n##ELSEticket.authors##--##ENDELSEticket.authors##\n\n##IFticket.category## ##lang.ticket.category##  :##ticket.category##\n##ENDIFticket.category## ##ELSEticket.category##\n##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##\n\n##lang.ticket.content##  : ##ticket.content##\n##IFticket.itemtype##\n##lang.ticket.item.name##  : ##ticket.itemtype## - ##ticket.item.name##\n##ENDIFticket.itemtype##',	'&lt;div&gt;##lang.ticket.url## : &lt;a href=\"##ticket.url##\"&gt;\n##ticket.url##&lt;/a&gt;&lt;/div&gt;\n&lt;div class=\"description b\"&gt;\n##lang.ticket.description##&lt;/div&gt;\n&lt;p&gt;&lt;span\nstyle=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;\n##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title##\n&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;\n##lang.ticket.authors##&lt;/span&gt;\n##IFticket.authors## ##ticket.authors##\n##ENDIFticket.authors##\n##ELSEticket.authors##--##ENDELSEticket.authors##\n&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;&#160\n;&lt;/span&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;\n##IFticket.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;\n##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category##\n##ENDIFticket.category## ##ELSEticket.category##\n##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##\n&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;\n##lang.ticket.content##&lt;/span&gt;&#160;:\n##ticket.content##&lt;br /&gt;##IFticket.itemtype##\n&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;\n##lang.ticket.item.name##&lt;/span&gt;&#160;:\n##ticket.itemtype## - ##ticket.item.name##\n##ENDIFticket.itemtype##&lt;/p&gt;'),
(7,	7,	'',	'##ticket.action## ##ticket.title##',	'##FOREACHvalidations##\n\n##IFvalidation.storestatus=2##\n##validation.submission.title##\n##lang.validation.commentsubmission## : ##validation.commentsubmission##\n##ENDIFvalidation.storestatus##\n##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##\n\n##lang.ticket.url## : ##ticket.urlvalidation##\n\n##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##\n##IFvalidation.commentvalidation##\n##lang.validation.commentvalidation## : ##validation.commentvalidation##\n##ENDIFvalidation.commentvalidation##\n##ENDFOREACHvalidations##',	'&lt;div&gt;##FOREACHvalidations##&lt;/div&gt;\n&lt;p&gt;##IFvalidation.storestatus=2##&lt;/p&gt;\n&lt;div&gt;##validation.submission.title##&lt;/div&gt;\n&lt;div&gt;##lang.validation.commentsubmission## : ##validation.commentsubmission##&lt;/div&gt;\n&lt;div&gt;##ENDIFvalidation.storestatus##&lt;/div&gt;\n&lt;div&gt;##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##&lt;/div&gt;\n&lt;div&gt;&lt;/div&gt;\n&lt;div&gt;\n&lt;div&gt;##lang.ticket.url## : &lt;a href=\"##ticket.urlvalidation##\"&gt; ##ticket.urlvalidation## &lt;/a&gt;&lt;/div&gt;\n&lt;/div&gt;\n&lt;p&gt;##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##\n&lt;br /&gt; ##IFvalidation.commentvalidation##&lt;br /&gt; ##lang.validation.commentvalidation## :\n&#160; ##validation.commentvalidation##&lt;br /&gt; ##ENDIFvalidation.commentvalidation##\n&lt;br /&gt;##ENDFOREACHvalidations##&lt;/p&gt;'),
(8,	6,	'',	'##ticket.action## ##ticket.entity##',	'##lang.ticket.entity## : ##ticket.entity##\n\n##FOREACHtickets##\n\n##lang.ticket.title## : ##ticket.title##\n ##lang.ticket.status## : ##ticket.status##\n\n ##ticket.url##\n ##ENDFOREACHtickets##',	'&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;\n&lt;tbody&gt;\n&lt;tr&gt;\n&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.authors##&lt;/span&gt;&lt;/td&gt;\n&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;\n&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;\n&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;\n&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;\n&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;\n&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.content##&lt;/span&gt;&lt;/td&gt;\n&lt;/tr&gt;\n##FOREACHtickets##\n&lt;tr&gt;\n&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.authors##&lt;/span&gt;&lt;/td&gt;\n&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;&lt;a href=\"##ticket.url##\"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;\n&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;\n&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;\n&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##IFticket.assigntousers####ticket.assigntousers##&lt;br /&gt;##ENDIFticket.assigntousers####IFticket.assigntogroups##&lt;br /&gt;##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;\n&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;\n&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.content##&lt;/span&gt;&lt;/td&gt;\n&lt;/tr&gt;\n##ENDFOREACHtickets##\n&lt;/tbody&gt;\n&lt;/table&gt;'),
(9,	9,	'',	'##consumable.action##  ##consumable.entity##',	'##lang.consumable.entity## : ##consumable.entity##\n\n\n##FOREACHconsumables##\n##lang.consumable.item## : ##consumable.item##\n\n\n##lang.consumable.reference## : ##consumable.reference##\n\n##lang.consumable.remaining## : ##consumable.remaining##\n\n##consumable.url##\n\n##ENDFOREACHconsumables##',	'&lt;p&gt;\n##lang.consumable.entity## : ##consumable.entity##\n&lt;br /&gt; &lt;br /&gt;##FOREACHconsumables##\n&lt;br /&gt;##lang.consumable.item## : ##consumable.item##&lt;br /&gt;\n&lt;br /&gt;##lang.consumable.reference## : ##consumable.reference##&lt;br /&gt;\n##lang.consumable.remaining## : ##consumable.remaining##&lt;br /&gt;\n&lt;a href=\"##consumable.url##\"&gt; ##consumable.url##&lt;/a&gt;&lt;br /&gt;\n   ##ENDFOREACHconsumables##&lt;/p&gt;'),
(10,	8,	'',	'##cartridge.action##  ##cartridge.entity##',	'##lang.cartridge.entity## : ##cartridge.entity##\n\n\n##FOREACHcartridges##\n##lang.cartridge.item## : ##cartridge.item##\n\n\n##lang.cartridge.reference## : ##cartridge.reference##\n\n##lang.cartridge.remaining## : ##cartridge.remaining##\n\n##cartridge.url##\n ##ENDFOREACHcartridges##',	'&lt;p&gt;##lang.cartridge.entity## : ##cartridge.entity##\n&lt;br /&gt; &lt;br /&gt;##FOREACHcartridges##\n&lt;br /&gt;##lang.cartridge.item## :\n##cartridge.item##&lt;br /&gt; &lt;br /&gt;\n##lang.cartridge.reference## :\n##cartridge.reference##&lt;br /&gt;\n##lang.cartridge.remaining## :\n##cartridge.remaining##&lt;br /&gt;\n&lt;a href=\"##cartridge.url##\"&gt;\n##cartridge.url##&lt;/a&gt;&lt;br /&gt;\n##ENDFOREACHcartridges##&lt;/p&gt;'),
(11,	10,	'',	'##infocom.action##  ##infocom.entity##',	'##lang.infocom.entity## : ##infocom.entity##\n\n\n##FOREACHinfocoms##\n\n##lang.infocom.itemtype## : ##infocom.itemtype##\n\n##lang.infocom.item## : ##infocom.item##\n\n\n##lang.infocom.expirationdate## : ##infocom.expirationdate##\n\n##infocom.url##\n ##ENDFOREACHinfocoms##',	'&lt;p&gt;##lang.infocom.entity## : ##infocom.entity##\n&lt;br /&gt; &lt;br /&gt;##FOREACHinfocoms##\n&lt;br /&gt;##lang.infocom.itemtype## : ##infocom.itemtype##&lt;br /&gt;\n##lang.infocom.item## : ##infocom.item##&lt;br /&gt; &lt;br /&gt;\n##lang.infocom.expirationdate## : ##infocom.expirationdate##\n&lt;br /&gt; &lt;a href=\"##infocom.url##\"&gt;\n##infocom.url##&lt;/a&gt;&lt;br /&gt;\n##ENDFOREACHinfocoms##&lt;/p&gt;'),
(12,	11,	'',	'##license.action##  ##license.entity##',	'##lang.license.entity## : ##license.entity##\n\n##FOREACHlicenses##\n\n##lang.license.item## : ##license.item##\n\n##lang.license.serial## : ##license.serial##\n\n##lang.license.expirationdate## : ##license.expirationdate##\n\n##license.url##\n ##ENDFOREACHlicenses##',	'&lt;p&gt;\n##lang.license.entity## : ##license.entity##&lt;br /&gt;\n##FOREACHlicenses##\n&lt;br /&gt;##lang.license.item## : ##license.item##&lt;br /&gt;\n##lang.license.serial## : ##license.serial##&lt;br /&gt;\n##lang.license.expirationdate## : ##license.expirationdate##\n&lt;br /&gt; &lt;a href=\"##license.url##\"&gt; ##license.url##\n&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHlicenses##&lt;/p&gt;'),
(13,	13,	'',	'##user.action##',	'##user.realname## ##user.firstname##\n\n##lang.passwordforget.information##\n\n##lang.passwordforget.link## ##user.passwordforgeturl##',	'&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;\n&lt;p&gt;##lang.passwordforget.information##&lt;/p&gt;\n&lt;p&gt;##lang.passwordforget.link## &lt;a title=\"##user.passwordforgeturl##\" href=\"##user.passwordforgeturl##\"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;'),
(14,	14,	'',	'##ticket.action## ##ticket.title##',	'##lang.ticket.title## : ##ticket.title##\n\n##lang.ticket.closedate## : ##ticket.closedate##\n\n##lang.satisfaction.text## ##ticket.urlsatisfaction##',	'&lt;p&gt;##lang.ticket.title## : ##ticket.title##&lt;/p&gt;\n&lt;p&gt;##lang.ticket.closedate## : ##ticket.closedate##&lt;/p&gt;\n&lt;p&gt;##lang.satisfaction.text## &lt;a href=\"##ticket.urlsatisfaction##\"&gt;##ticket.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;'),
(15,	15,	'',	'##lang.unicity.action##',	'##lang.unicity.entity## : ##unicity.entity##\n\n##lang.unicity.itemtype## : ##unicity.itemtype##\n\n##lang.unicity.message## : ##unicity.message##\n\n##lang.unicity.action_user## : ##unicity.action_user##\n\n##lang.unicity.action_type## : ##unicity.action_type##\n\n##lang.unicity.date## : ##unicity.date##',	'&lt;p&gt;##lang.unicity.entity## : ##unicity.entity##&lt;/p&gt;\n&lt;p&gt;##lang.unicity.itemtype## : ##unicity.itemtype##&lt;/p&gt;\n&lt;p&gt;##lang.unicity.message## : ##unicity.message##&lt;/p&gt;\n&lt;p&gt;##lang.unicity.action_user## : ##unicity.action_user##&lt;/p&gt;\n&lt;p&gt;##lang.unicity.action_type## : ##unicity.action_type##&lt;/p&gt;\n&lt;p&gt;##lang.unicity.date## : ##unicity.date##&lt;/p&gt;'),
(16,	16,	'',	'##crontask.action##',	'##lang.crontask.warning##\n\n##FOREACHcrontasks##\n ##crontask.name## : ##crontask.description##\n\n##ENDFOREACHcrontasks##',	'&lt;p&gt;##lang.crontask.warning##&lt;/p&gt;\n&lt;p&gt;##FOREACHcrontasks## &lt;br /&gt;&lt;a href=\"##crontask.url##\"&gt;##crontask.name##&lt;/a&gt; : ##crontask.description##&lt;br /&gt; &lt;br /&gt;##ENDFOREACHcrontasks##&lt;/p&gt;'),
(17,	17,	'',	'##problem.action## ##problem.title##',	'##IFproblem.storestatus=5##\n ##lang.problem.url## : ##problem.urlapprove##\n ##lang.problem.solvedate## : ##problem.solvedate##\n ##lang.problem.solution.type## : ##problem.solution.type##\n ##lang.problem.solution.description## : ##problem.solution.description## ##ENDIFproblem.storestatus##\n ##ELSEproblem.storestatus## ##lang.problem.url## : ##problem.url## ##ENDELSEproblem.storestatus##\n\n ##lang.problem.description##\n\n ##lang.problem.title##  :##problem.title##\n ##lang.problem.authors##  :##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors## ##ELSEproblem.authors##--##ENDELSEproblem.authors##\n ##lang.problem.creationdate##  :##problem.creationdate##\n ##IFproblem.assigntousers## ##lang.problem.assigntousers##  : ##problem.assigntousers## ##ENDIFproblem.assigntousers##\n ##lang.problem.status##  : ##problem.status##\n ##IFproblem.assigntogroups## ##lang.problem.assigntogroups##  : ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##\n ##lang.problem.urgency##  : ##problem.urgency##\n ##lang.problem.impact##  : ##problem.impact##\n ##lang.problem.priority## : ##problem.priority##\n##IFproblem.category## ##lang.problem.category##  :##problem.category## ##ENDIFproblem.category## ##ELSEproblem.category## ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##\n ##lang.problem.content##  : ##problem.content##\n\n##IFproblem.storestatus=6##\n ##lang.problem.solvedate## : ##problem.solvedate##\n ##lang.problem.solution.type## : ##problem.solution.type##\n ##lang.problem.solution.description## : ##problem.solution.description##\n##ENDIFproblem.storestatus##\n ##lang.problem.numberoffollowups## : ##problem.numberoffollowups##\n\n##FOREACHfollowups##\n\n [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##\n ##lang.followup.author## ##followup.author##\n ##lang.followup.description## ##followup.description##\n ##lang.followup.date## ##followup.date##\n ##lang.followup.requesttype## ##followup.requesttype##\n\n##ENDFOREACHfollowups##\n ##lang.problem.numberoftickets## : ##problem.numberoftickets##\n\n##FOREACHtickets##\n [##ticket.date##] ##lang.problem.title## : ##ticket.title##\n ##lang.problem.content## ##ticket.content##\n\n##ENDFOREACHtickets##\n ##lang.problem.numberoftasks## : ##problem.numberoftasks##\n\n##FOREACHtasks##\n [##task.date##]\n ##lang.task.author## ##task.author##\n ##lang.task.description## ##task.description##\n ##lang.task.time## ##task.time##\n ##lang.task.category## ##task.category##\n\n##ENDFOREACHtasks##\n',	'&lt;p&gt;##IFproblem.storestatus=5##&lt;/p&gt;\n&lt;div&gt;##lang.problem.url## : &lt;a href=\"##problem.urlapprove##\"&gt;##problem.urlapprove##&lt;/a&gt;&lt;/div&gt;\n&lt;div&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description## ##ENDIFproblem.storestatus##&lt;/div&gt;\n&lt;div&gt;##ELSEproblem.storestatus## ##lang.problem.url## : &lt;a href=\"##problem.url##\"&gt;##problem.url##&lt;/a&gt; ##ENDELSEproblem.storestatus##&lt;/div&gt;\n&lt;p class=\"description b\"&gt;&lt;strong&gt;##lang.problem.description##&lt;/strong&gt;&lt;/p&gt;\n&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.title##&lt;/span&gt;&#160;:##problem.title## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.authors##&lt;/span&gt;&#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors##    ##ELSEproblem.authors##--##ENDELSEproblem.authors## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.creationdate##&lt;/span&gt;&#160;:##problem.creationdate## &lt;br /&gt; ##IFproblem.assigntousers## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.assigntousers##&lt;/span&gt;&#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.status## &lt;/span&gt;&#160;: ##problem.status##&lt;br /&gt; ##IFproblem.assigntogroups## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.assigntogroups##&lt;/span&gt;&#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.urgency##&lt;/span&gt;&#160;: ##problem.urgency##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.impact##&lt;/span&gt;&#160;: ##problem.impact##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.priority##&lt;/span&gt; : ##problem.priority## &lt;br /&gt;##IFproblem.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.category## &lt;/span&gt;&#160;:##problem.category##  ##ENDIFproblem.category## ##ELSEproblem.category##  ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##    &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.content##&lt;/span&gt;&#160;: ##problem.content##&lt;/p&gt;\n&lt;p&gt;##IFproblem.storestatus=6##&lt;br /&gt;&lt;span style=\"text-decoration: underline;\"&gt;&lt;strong&gt;&lt;span style=\"color: #888888;\"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.problem.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description##&lt;br /&gt;##ENDIFproblem.storestatus##&lt;/p&gt;\n<div class=\"description b\">##lang.problem.numberoffollowups##&#160;: ##problem.numberoffollowups##</div>\n<p>##FOREACHfollowups##</p>\n<div class=\"description b\"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.author## </span> ##followup.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.description## </span> ##followup.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.date## </span> ##followup.date##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>\n<p>##ENDFOREACHfollowups##</p>\n&lt;div class=\"description b\"&gt;##lang.problem.numberoftickets##&#160;: ##problem.numberoftickets##&lt;/div&gt;\n&lt;p&gt;##FOREACHtickets##&lt;/p&gt;\n&lt;div&gt;&lt;strong&gt; [##ticket.date##] &lt;em&gt;##lang.problem.title## : &lt;a href=\"##ticket.url##\"&gt;##ticket.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.content## &lt;/span&gt; ##ticket.content##\n&lt;p&gt;##ENDFOREACHtickets##&lt;/p&gt;\n&lt;div class=\"description b\"&gt;##lang.problem.numberoftasks##&#160;: ##problem.numberoftasks##&lt;/div&gt;\n&lt;p&gt;##FOREACHtasks##&lt;/p&gt;\n&lt;div class=\"description b\"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;\n&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;\n&lt;/div&gt;'),
(18,	18,	'',	'##recall.action##: ##recall.item.name##',	'##recall.action##: ##recall.item.name##\n\n##recall.item.content##\n\n##lang.recall.planning.begin##: ##recall.planning.begin##\n##lang.recall.planning.end##: ##recall.planning.end##\n##lang.recall.planning.state##: ##recall.planning.state##\n##lang.recall.item.private##: ##recall.item.private##',	'&lt;p&gt;##recall.action##: &lt;a href=\"##recall.item.url##\"&gt;##recall.item.name##&lt;/a&gt;&lt;/p&gt;\n&lt;p&gt;##recall.item.content##&lt;/p&gt;\n&lt;p&gt;##lang.recall.planning.begin##: ##recall.planning.begin##&lt;br /&gt;##lang.recall.planning.end##: ##recall.planning.end##&lt;br /&gt;##lang.recall.planning.state##: ##recall.planning.state##&lt;br /&gt;##lang.recall.item.private##: ##recall.item.private##&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;\n&lt;p&gt;&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;'),
(19,	19,	'',	'##change.action## ##change.title##',	'##IFchange.storestatus=5##\n ##lang.change.url## : ##change.urlapprove##\n ##lang.change.solvedate## : ##change.solvedate##\n ##lang.change.solution.type## : ##change.solution.type##\n ##lang.change.solution.description## : ##change.solution.description## ##ENDIFchange.storestatus##\n ##ELSEchange.storestatus## ##lang.change.url## : ##change.url## ##ENDELSEchange.storestatus##\n\n ##lang.change.description##\n\n ##lang.change.title##  :##change.title##\n ##lang.change.authors##  :##IFchange.authors## ##change.authors## ##ENDIFchange.authors## ##ELSEchange.authors##--##ENDELSEchange.authors##\n ##lang.change.creationdate##  :##change.creationdate##\n ##IFchange.assigntousers## ##lang.change.assigntousers##  : ##change.assigntousers## ##ENDIFchange.assigntousers##\n ##lang.change.status##  : ##change.status##\n ##IFchange.assigntogroups## ##lang.change.assigntogroups##  : ##change.assigntogroups## ##ENDIFchange.assigntogroups##\n ##lang.change.urgency##  : ##change.urgency##\n ##lang.change.impact##  : ##change.impact##\n ##lang.change.priority## : ##change.priority##\n##IFchange.category## ##lang.change.category##  :##change.category## ##ENDIFchange.category## ##ELSEchange.category## ##lang.change.nocategoryassigned## ##ENDELSEchange.category##\n ##lang.change.content##  : ##change.content##\n\n##IFchange.storestatus=6##\n ##lang.change.solvedate## : ##change.solvedate##\n ##lang.change.solution.type## : ##change.solution.type##\n ##lang.change.solution.description## : ##change.solution.description##\n##ENDIFchange.storestatus##\n ##lang.change.numberoffollowups## : ##change.numberoffollowups##\n\n##FOREACHfollowups##\n\n [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##\n ##lang.followup.author## ##followup.author##\n ##lang.followup.description## ##followup.description##\n ##lang.followup.date## ##followup.date##\n ##lang.followup.requesttype## ##followup.requesttype##\n\n##ENDFOREACHfollowups##\n ##lang.change.numberofproblems## : ##change.numberofproblems##\n\n##FOREACHproblems##\n [##problem.date##] ##lang.change.title## : ##problem.title##\n ##lang.change.content## ##problem.content##\n\n##ENDFOREACHproblems##\n ##lang.change.numberoftasks## : ##change.numberoftasks##\n\n##FOREACHtasks##\n [##task.date##]\n ##lang.task.author## ##task.author##\n ##lang.task.description## ##task.description##\n ##lang.task.time## ##task.time##\n ##lang.task.category## ##task.category##\n\n##ENDFOREACHtasks##\n',	'&lt;p&gt;##IFchange.storestatus=5##&lt;/p&gt;\n&lt;div&gt;##lang.change.url## : &lt;a href=\"##change.urlapprove##\"&gt;##change.urlapprove##&lt;/a&gt;&lt;/div&gt;\n&lt;div&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.change.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description## ##ENDIFchange.storestatus##&lt;/div&gt;\n&lt;div&gt;##ELSEchange.storestatus## ##lang.change.url## : &lt;a href=\"##change.url##\"&gt;##change.url##&lt;/a&gt; ##ENDELSEchange.storestatus##&lt;/div&gt;\n&lt;p class=\"description b\"&gt;&lt;strong&gt;##lang.change.description##&lt;/strong&gt;&lt;/p&gt;\n&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.title##&lt;/span&gt;&#160;:##change.title## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.authors##&lt;/span&gt;&#160;:##IFchange.authors## ##change.authors## ##ENDIFchange.authors##    ##ELSEchange.authors##--##ENDELSEchange.authors## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.creationdate##&lt;/span&gt;&#160;:##change.creationdate## &lt;br /&gt; ##IFchange.assigntousers## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.assigntousers##&lt;/span&gt;&#160;: ##change.assigntousers## ##ENDIFchange.assigntousers##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.change.status## &lt;/span&gt;&#160;: ##change.status##&lt;br /&gt; ##IFchange.assigntogroups## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.assigntogroups##&lt;/span&gt;&#160;: ##change.assigntogroups## ##ENDIFchange.assigntogroups##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.urgency##&lt;/span&gt;&#160;: ##change.urgency##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.impact##&lt;/span&gt;&#160;: ##change.impact##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.priority##&lt;/span&gt; : ##change.priority## &lt;br /&gt;##IFchange.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.change.category## &lt;/span&gt;&#160;:##change.category##  ##ENDIFchange.category## ##ELSEchange.category##  ##lang.change.nocategoryassigned## ##ENDELSEchange.category##    &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.content##&lt;/span&gt;&#160;: ##change.content##&lt;/p&gt;\n&lt;p&gt;##IFchange.storestatus=6##&lt;br /&gt;&lt;span style=\"text-decoration: underline;\"&gt;&lt;strong&gt;&lt;span style=\"color: #888888;\"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.change.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description##&lt;br /&gt;##ENDIFchange.storestatus##&lt;/p&gt;\n<div class=\"description b\">##lang.change.numberoffollowups##&#160;: ##change.numberoffollowups##</div>\n<p>##FOREACHfollowups##</p>\n<div class=\"description b\"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.author## </span> ##followup.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.description## </span> ##followup.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.date## </span> ##followup.date##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>\n<p>##ENDFOREACHfollowups##</p>\n&lt;div class=\"description b\"&gt;##lang.change.numberofproblems##&#160;: ##change.numberofproblems##&lt;/div&gt;\n&lt;p&gt;##FOREACHproblems##&lt;/p&gt;\n&lt;div&gt;&lt;strong&gt; [##problem.date##] &lt;em&gt;##lang.change.title## : &lt;a href=\"##problem.url##\"&gt;##problem.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.change.content## &lt;/span&gt; ##problem.content##\n&lt;p&gt;##ENDFOREACHproblems##&lt;/p&gt;\n&lt;div class=\"description b\"&gt;##lang.change.numberoftasks##&#160;: ##change.numberoftasks##&lt;/div&gt;\n&lt;p&gt;##FOREACHtasks##&lt;/p&gt;\n&lt;div class=\"description b\"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;\n&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;\n&lt;/div&gt;'),
(20,	20,	'',	'##mailcollector.action##',	'##FOREACHmailcollectors##\n##lang.mailcollector.name## : ##mailcollector.name##\n##lang.mailcollector.errors## : ##mailcollector.errors##\n##mailcollector.url##\n##ENDFOREACHmailcollectors##',	'&lt;p&gt;##FOREACHmailcollectors##&lt;br /&gt;##lang.mailcollector.name## : ##mailcollector.name##&lt;br /&gt; ##lang.mailcollector.errors## : ##mailcollector.errors##&lt;br /&gt;&lt;a href=\"##mailcollector.url##\"&gt;##mailcollector.url##&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHmailcollectors##&lt;/p&gt;\n&lt;p&gt;&lt;/p&gt;'),
(21,	21,	'',	'##project.action## ##project.name## ##project.code##',	'##lang.project.url## : ##project.url##\n\n##lang.project.description##\n\n##lang.project.name## : ##project.name##\n##lang.project.code## : ##project.code##\n##lang.project.manager## : ##project.manager##\n##lang.project.managergroup## : ##project.managergroup##\n##lang.project.creationdate## : ##project.creationdate##\n##lang.project.priority## : ##project.priority##\n##lang.project.state## : ##project.state##\n##lang.project.type## : ##project.type##\n##lang.project.description## : ##project.description##\n\n##lang.project.numberoftasks## : ##project.numberoftasks##\n\n\n\n##FOREACHtasks##\n\n[##task.creationdate##]\n##lang.task.name## : ##task.name##\n##lang.task.state## : ##task.state##\n##lang.task.type## : ##task.type##\n##lang.task.percent## : ##task.percent##\n##lang.task.description## : ##task.description##\n\n##ENDFOREACHtasks##',	'&lt;p&gt;##lang.project.url## : &lt;a href=\"##project.url##\"&gt;##project.url##&lt;/a&gt;&lt;/p&gt;\n&lt;p&gt;&lt;strong&gt;##lang.project.description##&lt;/strong&gt;&lt;/p&gt;\n&lt;p&gt;##lang.project.name## : ##project.name##&lt;br /&gt;##lang.project.code## : ##project.code##&lt;br /&gt; ##lang.project.manager## : ##project.manager##&lt;br /&gt;##lang.project.managergroup## : ##project.managergroup##&lt;br /&gt; ##lang.project.creationdate## : ##project.creationdate##&lt;br /&gt;##lang.project.priority## : ##project.priority## &lt;br /&gt;##lang.project.state## : ##project.state##&lt;br /&gt;##lang.project.type## : ##project.type##&lt;br /&gt;##lang.project.description## : ##project.description##&lt;/p&gt;\n&lt;p&gt;##lang.project.numberoftasks## : ##project.numberoftasks##&lt;/p&gt;\n&lt;div&gt;\n&lt;p&gt;##FOREACHtasks##&lt;/p&gt;\n&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt; ##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;\n&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;\n&lt;/div&gt;'),
(22,	22,	'',	'##projecttask.action## ##projecttask.name##',	'##lang.projecttask.url## : ##projecttask.url##\n\n##lang.projecttask.description##\n\n##lang.projecttask.name## : ##projecttask.name##\n##lang.projecttask.project## : ##projecttask.project##\n##lang.projecttask.creationdate## : ##projecttask.creationdate##\n##lang.projecttask.state## : ##projecttask.state##\n##lang.projecttask.type## : ##projecttask.type##\n##lang.projecttask.description## : ##projecttask.description##\n\n##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##\n\n\n\n##FOREACHtasks##\n\n[##task.creationdate##]\n##lang.task.name## : ##task.name##\n##lang.task.state## : ##task.state##\n##lang.task.type## : ##task.type##\n##lang.task.percent## : ##task.percent##\n##lang.task.description## : ##task.description##\n\n##ENDFOREACHtasks##',	'&lt;p&gt;##lang.projecttask.url## : &lt;a href=\"##projecttask.url##\"&gt;##projecttask.url##&lt;/a&gt;&lt;/p&gt;\n&lt;p&gt;&lt;strong&gt;##lang.projecttask.description##&lt;/strong&gt;&lt;/p&gt;\n&lt;p&gt;##lang.projecttask.name## : ##projecttask.name##&lt;br /&gt;##lang.projecttask.project## : &lt;a href=\"##projecttask.projecturl##\"&gt;##projecttask.project##&lt;/a&gt;&lt;br /&gt;##lang.projecttask.creationdate## : ##projecttask.creationdate##&lt;br /&gt;##lang.projecttask.state## : ##projecttask.state##&lt;br /&gt;##lang.projecttask.type## : ##projecttask.type##&lt;br /&gt;##lang.projecttask.description## : ##projecttask.description##&lt;/p&gt;\n&lt;p&gt;##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##&lt;/p&gt;\n&lt;div&gt;\n&lt;p&gt;##FOREACHtasks##&lt;/p&gt;\n&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt;##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;\n&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;\n&lt;/div&gt;'),
(23,	23,	'',	'##objectlock.action##',	'##objectlock.type## ###objectlock.id## - ##objectlock.name##\n\n      ##lang.objectlock.url##\n      ##objectlock.url##\n\n      ##lang.objectlock.date_mod##\n      ##objectlock.date_mod##\n\n      Hello ##objectlock.lockedby.firstname##,\n      Could go to this item and unlock it for me?\n      Thank you,\n      Regards,\n      ##objectlock.requester.firstname##',	'&lt;table&gt;\n      &lt;tbody&gt;\n      &lt;tr&gt;&lt;th colspan=\"2\"&gt;&lt;a href=\"##objectlock.url##\"&gt;##objectlock.type## ###objectlock.id## - ##objectlock.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;\n      &lt;tr&gt;\n      &lt;td&gt;##lang.objectlock.url##&lt;/td&gt;\n      &lt;td&gt;##objectlock.url##&lt;/td&gt;\n      &lt;/tr&gt;\n      &lt;tr&gt;\n      &lt;td&gt;##lang.objectlock.date_mod##&lt;/td&gt;\n      &lt;td&gt;##objectlock.date_mod##&lt;/td&gt;\n      &lt;/tr&gt;\n      &lt;/tbody&gt;\n      &lt;/table&gt;\n      &lt;p&gt;&lt;span style=\"font-size: small;\"&gt;Hello ##objectlock.lockedby.firstname##,&lt;br /&gt;Could go to this item and unlock it for me?&lt;br /&gt;Thank you,&lt;br /&gt;Regards,&lt;br /&gt;##objectlock.requester.firstname## ##objectlock.requester.lastname##&lt;/span&gt;&lt;/p&gt;'),
(24,	24,	'',	'##savedsearch.action## ##savedsearch.name##',	'##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##\n\n      ##savedsearch.message##\n\n      ##lang.savedsearch.url##\n      ##savedsearch.url##\n\n      Regards,',	'&lt;table&gt;\n      &lt;tbody&gt;\n      &lt;tr&gt;&lt;th colspan=\"2\"&gt;&lt;a href=\"##savedsearch.url##\"&gt;##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;\n      &lt;tr&gt;&lt;td colspan=\"2\"&gt;&lt;a href=\"##savedsearch.url##\"&gt;##savedsearch.message##&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;\n      &lt;tr&gt;\n      &lt;td&gt;##lang.savedsearch.url##&lt;/td&gt;\n      &lt;td&gt;##savedsearch.url##&lt;/td&gt;\n      &lt;/tr&gt;\n      &lt;/tbody&gt;\n      &lt;/table&gt;\n      &lt;p&gt;&lt;span style=\"font-size: small;\"&gt;Hello &lt;br /&gt;Regards,&lt;/span&gt;&lt;/p&gt;'),
(25,	25,	'',	'##certificate.action##  ##certificate.entity##',	'##lang.certificate.entity## : ##certificate.entity##\n\n##FOREACHcertificates##\n\n##lang.certificate.serial## : ##certificate.serial##\n\n##lang.certificate.expirationdate## : ##certificate.expirationdate##\n\n##certificate.url##\n ##ENDFOREACHcertificates##',	'&lt;p&gt;\n##lang.certificate.entity## : ##certificate.entity##&lt;br /&gt;\n##FOREACHcertificates##\n&lt;br /&gt;##lang.certificate.name## : ##certificate.name##&lt;br /&gt;\n##lang.certificate.serial## : ##certificate.serial##&lt;br /&gt;\n##lang.certificate.expirationdate## : ##certificate.expirationdate##\n&lt;br /&gt; &lt;a href=\"##certificate.url##\"&gt; ##certificate.url##\n&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHcertificates##&lt;/p&gt;'),
(26,	26,	'',	'##domain.action## : ##domain.entity##',	'##lang.domain.entity## :##domain.entity##\n   ##FOREACHdomains##\n   ##lang.domain.name## : ##domain.name## - ##lang.domain.dateexpiration## : ##domain.dateexpiration##\n   ##ENDFOREACHdomains##',	'&lt;p&gt;##lang.domain.entity## :##domain.entity##&lt;br /&gt; &lt;br /&gt;\n                        ##FOREACHdomains##&lt;br /&gt;\n                        ##lang.domain.name##  : ##domain.name## - ##lang.domain.dateexpiration## :  ##domain.dateexpiration##&lt;br /&gt;\n                        ##ENDFOREACHdomains##&lt;/p&gt;'),
(27,	27,	'',	'##user.action##',	'##user.realname## ##user.firstname##,\n\n##IFuser.password.has_expired=1##\n##lang.password.has_expired.information##\n##ENDIFuser.password.has_expired##\n##ELSEuser.password.has_expired##\n##lang.password.expires_soon.information##\n##ENDELSEuser.password.has_expired##\n##lang.user.password.expiration.date##: ##user.password.expiration.date##\n##IFuser.account.lock.date##\n##lang.user.account.lock.date##: ##user.account.lock.date##\n##ENDIFuser.account.lock.date##\n\n##password.update.link## ##user.password.update.url##',	'&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;\n\n##IFuser.password.has_expired=1##\n&lt;p&gt;##lang.password.has_expired.information##&lt;/p&gt;\n##ENDIFuser.password.has_expired##\n##ELSEuser.password.has_expired##\n&lt;p&gt;##lang.password.expires_soon.information##&lt;/p&gt;\n##ENDELSEuser.password.has_expired##\n&lt;p&gt;##lang.user.password.expiration.date##: ##user.password.expiration.date##&lt;/p&gt;\n##IFuser.account.lock.date##\n&lt;p&gt;##lang.user.account.lock.date##: ##user.account.lock.date##&lt;/p&gt;\n##ENDIFuser.account.lock.date##\n\n&lt;p&gt;##lang.password.update.link## &lt;a href=\"##user.password.update.url##\"&gt;##user.password.update.url##&lt;/a&gt;&lt;/p&gt;'),
(28,	28,	'',	'##lang.plugins_updates_available##',	'##lang.plugins_updates_available##\n\n##FOREACHplugins##\n##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##\n##ENDFOREACHplugins##',	'&lt;p&gt;##lang.plugins_updates_available##&lt;/p&gt;\n&lt;ul&gt;##FOREACHplugins##\n&lt;li&gt;##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##&lt;/li&gt;\n##ENDFOREACHplugins##&lt;/ul&gt;');

DROP TABLE IF EXISTS `glpi_notimportedemails`;
CREATE TABLE `glpi_notimportedemails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `mailcollectors_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NOT NULL,
  `subject` text DEFAULT NULL,
  `messageid` varchar(255) NOT NULL,
  `reason` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `mailcollectors_id` (`mailcollectors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `glpi_objectlocks`;
CREATE TABLE `glpi_objectlocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of locked object',
  `items_id` int(11) NOT NULL COMMENT 'RELATION to various tables, according to itemtype (ID)',
  `users_id` int(11) NOT NULL COMMENT 'id of the locker',
  `date_mod` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of the lock',
  PRIMARY KEY (`id`),
  UNIQUE KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_olalevelactions`;
CREATE TABLE `glpi_olalevelactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `olalevels_id` int(11) NOT NULL DEFAULT 0,
  `action_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `olalevels_id` (`olalevels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_olalevelcriterias`;
CREATE TABLE `glpi_olalevelcriterias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `olalevels_id` int(11) NOT NULL DEFAULT 0,
  `criteria` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition` int(11) NOT NULL DEFAULT 0 COMMENT 'see define.php PATTERN_* and REGEX_* constant',
  `pattern` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `olalevels_id` (`olalevels_id`),
  KEY `condition` (`condition`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_olalevels`;
CREATE TABLE `glpi_olalevels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `olas_id` int(11) NOT NULL DEFAULT 0,
  `execution_time` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `match` char(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'see define.php *_MATCHING constant',
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_active` (`is_active`),
  KEY `olas_id` (`olas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_olalevels_tickets`;
CREATE TABLE `glpi_olalevels_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `olalevels_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickets_id`,`olalevels_id`),
  KEY `tickets_id` (`tickets_id`),
  KEY `olalevels_id` (`olalevels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_olas`;
CREATE TABLE `glpi_olas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `number_time` int(11) NOT NULL,
  `calendars_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `definition_time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `end_of_working_day` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `slms_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `calendars_id` (`calendars_id`),
  KEY `slms_id` (`slms_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_operatingsystemarchitectures`;
CREATE TABLE `glpi_operatingsystemarchitectures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_operatingsystemeditions`;
CREATE TABLE `glpi_operatingsystemeditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_operatingsystemkernels`;
CREATE TABLE `glpi_operatingsystemkernels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_operatingsystemkernelversions`;
CREATE TABLE `glpi_operatingsystemkernelversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operatingsystemkernels_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `operatingsystemkernels_id` (`operatingsystemkernels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_operatingsystems`;
CREATE TABLE `glpi_operatingsystems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_operatingsystemservicepacks`;
CREATE TABLE `glpi_operatingsystemservicepacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_operatingsystemversions`;
CREATE TABLE `glpi_operatingsystemversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_passivedcequipmentmodels`;
CREATE TABLE `glpi_passivedcequipmentmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `required_units` int(11) NOT NULL DEFAULT 1,
  `depth` float NOT NULL DEFAULT 1,
  `power_connections` int(11) NOT NULL DEFAULT 0,
  `power_consumption` int(11) NOT NULL DEFAULT 0,
  `is_half_rack` tinyint(1) NOT NULL DEFAULT 0,
  `picture_front` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture_rear` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_passivedcequipments`;
CREATE TABLE `glpi_passivedcequipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passivedcequipmentmodels_id` int(11) DEFAULT NULL,
  `passivedcequipmenttypes_id` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to states (id)',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `passivedcequipmentmodels_id` (`passivedcequipmentmodels_id`),
  KEY `passivedcequipmenttypes_id` (`passivedcequipmenttypes_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `group_id_tech` (`groups_id_tech`),
  KEY `is_template` (`is_template`),
  KEY `is_deleted` (`is_deleted`),
  KEY `states_id` (`states_id`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_passivedcequipmenttypes`;
CREATE TABLE `glpi_passivedcequipmenttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_pdumodels`;
CREATE TABLE `glpi_pdumodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `required_units` int(11) NOT NULL DEFAULT 1,
  `depth` float NOT NULL DEFAULT 1,
  `power_connections` int(11) NOT NULL DEFAULT 0,
  `max_power` int(11) NOT NULL DEFAULT 0,
  `is_half_rack` tinyint(1) NOT NULL DEFAULT 0,
  `picture_front` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture_rear` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_rackable` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_rackable` (`is_rackable`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_pdus`;
CREATE TABLE `glpi_pdus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pdumodels_id` int(11) DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0 COMMENT 'RELATION to states (id)',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `pdutypes_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `pdumodels_id` (`pdumodels_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `group_id_tech` (`groups_id_tech`),
  KEY `is_template` (`is_template`),
  KEY `is_deleted` (`is_deleted`),
  KEY `states_id` (`states_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `pdutypes_id` (`pdutypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_pdus_plugs`;
CREATE TABLE `glpi_pdus_plugs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugs_id` int(11) NOT NULL DEFAULT 0,
  `pdus_id` int(11) NOT NULL DEFAULT 0,
  `number_plugs` int(11) DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugs_id` (`plugs_id`),
  KEY `pdus_id` (`pdus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_pdus_racks`;
CREATE TABLE `glpi_pdus_racks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `racks_id` int(11) NOT NULL DEFAULT 0,
  `pdus_id` int(11) NOT NULL DEFAULT 0,
  `side` int(11) DEFAULT 0,
  `position` int(11) NOT NULL,
  `bgcolor` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `racks_id` (`racks_id`),
  KEY `pdus_id` (`pdus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_pdutypes`;
CREATE TABLE `glpi_pdutypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `name` (`name`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_peripheralmodels`;
CREATE TABLE `glpi_peripheralmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `required_units` int(11) NOT NULL DEFAULT 1,
  `depth` float NOT NULL DEFAULT 1,
  `power_connections` int(11) NOT NULL DEFAULT 0,
  `power_consumption` int(11) NOT NULL DEFAULT 0,
  `is_half_rack` tinyint(1) NOT NULL DEFAULT 0,
  `picture_front` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture_rear` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_peripherals`;
CREATE TABLE `glpi_peripherals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `peripheraltypes_id` int(11) NOT NULL DEFAULT 0,
  `peripheralmodels_id` int(11) NOT NULL DEFAULT 0,
  `brand` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `ticket_tco` decimal(20,4) DEFAULT 0.0000,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `peripheralmodels_id` (`peripheralmodels_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `peripheraltypes_id` (`peripheraltypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `date_creation` (`date_creation`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_peripheraltypes`;
CREATE TABLE `glpi_peripheraltypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_phonemodels`;
CREATE TABLE `glpi_phonemodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_phonepowersupplies`;
CREATE TABLE `glpi_phonepowersupplies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_phones`;
CREATE TABLE `glpi_phones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `phonetypes_id` int(11) NOT NULL DEFAULT 0,
  `phonemodels_id` int(11) NOT NULL DEFAULT 0,
  `brand` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phonepowersupplies_id` int(11) NOT NULL DEFAULT 0,
  `number_line` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `have_headset` tinyint(1) NOT NULL DEFAULT 0,
  `have_hp` tinyint(1) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `ticket_tco` decimal(20,4) DEFAULT 0.0000,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `phonemodels_id` (`phonemodels_id`),
  KEY `phonepowersupplies_id` (`phonepowersupplies_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `phonetypes_id` (`phonetypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `date_creation` (`date_creation`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_phonetypes`;
CREATE TABLE `glpi_phonetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_planningeventcategories`;
CREATE TABLE `glpi_planningeventcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_planningexternalevents`;
CREATE TABLE `glpi_planningexternalevents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `planningexternaleventtemplates_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 1,
  `date` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `users_id_guests` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `rrule` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 0,
  `planningeventcategories_id` int(11) NOT NULL DEFAULT 0,
  `background` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `planningexternaleventtemplates_id` (`planningexternaleventtemplates_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date` (`date`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `users_id` (`users_id`),
  KEY `groups_id` (`groups_id`),
  KEY `state` (`state`),
  KEY `planningeventcategories_id` (`planningeventcategories_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_planningexternaleventtemplates`;
CREATE TABLE `glpi_planningexternaleventtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `before_time` int(11) NOT NULL DEFAULT 0,
  `rrule` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 0,
  `planningeventcategories_id` int(11) NOT NULL DEFAULT 0,
  `background` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `state` (`state`),
  KEY `planningeventcategories_id` (`planningeventcategories_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_planningrecalls`;
CREATE TABLE `glpi_planningrecalls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `before_time` int(11) NOT NULL DEFAULT -10,
  `when` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`users_id`),
  KEY `users_id` (`users_id`),
  KEY `before_time` (`before_time`),
  KEY `when` (`when`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugins`;
CREATE TABLE `glpi_plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `directory` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `state` int(11) NOT NULL DEFAULT 0 COMMENT 'see define.php PLUGIN_* constant',
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `homepage` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `license` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`directory`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugs`;
CREATE TABLE `glpi_plugs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_printermodels`;
CREATE TABLE `glpi_printermodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_printers`;
CREATE TABLE `glpi_printers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `have_serial` tinyint(1) NOT NULL DEFAULT 0,
  `have_parallel` tinyint(1) NOT NULL DEFAULT 0,
  `have_usb` tinyint(1) NOT NULL DEFAULT 0,
  `have_wifi` tinyint(1) NOT NULL DEFAULT 0,
  `have_ethernet` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `memory_size` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `networks_id` int(11) NOT NULL DEFAULT 0,
  `printertypes_id` int(11) NOT NULL DEFAULT 0,
  `printermodels_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `init_pages_counter` int(11) NOT NULL DEFAULT 0,
  `last_pages_counter` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `ticket_tco` decimal(20,4) DEFAULT 0.0000,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `printermodels_id` (`printermodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `printertypes_id` (`printertypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `last_pages_counter` (`last_pages_counter`),
  KEY `is_dynamic` (`is_dynamic`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_printertypes`;
CREATE TABLE `glpi_printertypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problemcosts`;
CREATE TABLE `glpi_problemcosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `cost_time` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cost_fixed` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cost_material` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `budgets_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `problems_id` (`problems_id`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `entities_id` (`entities_id`),
  KEY `budgets_id` (`budgets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problems`;
CREATE TABLE `glpi_problems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `solvedate` timestamp NULL DEFAULT NULL,
  `closedate` timestamp NULL DEFAULT NULL,
  `time_to_resolve` timestamp NULL DEFAULT NULL,
  `users_id_recipient` int(11) NOT NULL DEFAULT 0,
  `users_id_lastupdater` int(11) NOT NULL DEFAULT 0,
  `urgency` int(11) NOT NULL DEFAULT 1,
  `impact` int(11) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 1,
  `itilcategories_id` int(11) NOT NULL DEFAULT 0,
  `impactcontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `causecontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `symptomcontent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `begin_waiting_date` timestamp NULL DEFAULT NULL,
  `waiting_duration` int(11) NOT NULL DEFAULT 0,
  `close_delay_stat` int(11) NOT NULL DEFAULT 0,
  `solve_delay_stat` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date` (`date`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `date_mod` (`date_mod`),
  KEY `itilcategories_id` (`itilcategories_id`),
  KEY `users_id_recipient` (`users_id_recipient`),
  KEY `solvedate` (`solvedate`),
  KEY `urgency` (`urgency`),
  KEY `impact` (`impact`),
  KEY `time_to_resolve` (`time_to_resolve`),
  KEY `users_id_lastupdater` (`users_id_lastupdater`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problems_suppliers`;
CREATE TABLE `glpi_problems_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  `suppliers_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `use_notification` tinyint(1) NOT NULL DEFAULT 0,
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`problems_id`,`type`,`suppliers_id`),
  KEY `group` (`suppliers_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problems_tickets`;
CREATE TABLE `glpi_problems_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`problems_id`,`tickets_id`),
  KEY `tickets_id` (`tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problems_users`;
CREATE TABLE `glpi_problems_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `use_notification` tinyint(1) NOT NULL DEFAULT 0,
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`problems_id`,`type`,`users_id`,`alternative_email`),
  KEY `user` (`users_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problemtasks`;
CREATE TABLE `glpi_problemtasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `problems_id` int(11) NOT NULL DEFAULT 0,
  `taskcategories_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `begin` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `users_id_editor` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `state` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `tasktemplates_id` int(11) NOT NULL DEFAULT 0,
  `timeline_position` tinyint(1) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `problems_id` (`problems_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_editor` (`users_id_editor`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date` (`date`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `state` (`state`),
  KEY `taskcategories_id` (`taskcategories_id`),
  KEY `tasktemplates_id` (`tasktemplates_id`),
  KEY `is_private` (`is_private`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problemtemplatehiddenfields`;
CREATE TABLE `glpi_problemtemplatehiddenfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problemtemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`problemtemplates_id`,`num`),
  KEY `problemtemplates_id` (`problemtemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problemtemplatemandatoryfields`;
CREATE TABLE `glpi_problemtemplatemandatoryfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problemtemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`problemtemplates_id`,`num`),
  KEY `problemtemplates_id` (`problemtemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_problemtemplatemandatoryfields` (`id`, `problemtemplates_id`, `num`) VALUES
(1,	1,	21);

DROP TABLE IF EXISTS `glpi_problemtemplatepredefinedfields`;
CREATE TABLE `glpi_problemtemplatepredefinedfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problemtemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  `value` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `problemtemplates_id` (`problemtemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_problemtemplates`;
CREATE TABLE `glpi_problemtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_problemtemplates` (`id`, `name`, `entities_id`, `is_recursive`, `comment`) VALUES
(1,	'Default',	0,	1,	NULL);

DROP TABLE IF EXISTS `glpi_profilerights`;
CREATE TABLE `glpi_profilerights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profiles_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rights` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`profiles_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profilerights` (`id`, `profiles_id`, `name`, `rights`) VALUES
(1,	1,	'computer',	0),
(2,	1,	'monitor',	0),
(3,	1,	'software',	0),
(4,	1,	'networking',	0),
(5,	1,	'internet',	0),
(6,	1,	'printer',	0),
(7,	1,	'peripheral',	0),
(8,	1,	'cartridge',	0),
(9,	1,	'consumable',	0),
(10,	1,	'phone',	0),
(11,	6,	'queuednotification',	0),
(12,	1,	'contact_enterprise',	0),
(13,	1,	'document',	0),
(14,	1,	'contract',	0),
(15,	1,	'infocom',	0),
(16,	1,	'knowbase',	2048),
(17,	1,	'reservation',	1024),
(18,	1,	'reports',	0),
(19,	1,	'dropdown',	0),
(20,	1,	'device',	0),
(21,	1,	'typedoc',	0),
(22,	1,	'link',	0),
(23,	1,	'config',	0),
(24,	1,	'rule_ticket',	0),
(25,	1,	'rule_import',	0),
(26,	1,	'rule_ldap',	0),
(27,	1,	'rule_softwarecategories',	0),
(28,	1,	'search_config',	0),
(29,	5,	'location',	0),
(30,	7,	'domain',	23),
(31,	1,	'profile',	0),
(32,	1,	'user',	0),
(33,	1,	'group',	0),
(34,	1,	'entity',	0),
(35,	1,	'transfer',	0),
(36,	1,	'logs',	0),
(37,	1,	'reminder_public',	1),
(38,	1,	'rssfeed_public',	1),
(39,	1,	'bookmark_public',	0),
(40,	1,	'backup',	0),
(41,	1,	'ticket',	5),
(42,	1,	'followup',	5),
(43,	1,	'task',	1),
(44,	1,	'planning',	0),
(45,	2,	'state',	0),
(46,	2,	'taskcategory',	0),
(47,	1,	'statistic',	0),
(48,	1,	'password_update',	1),
(49,	1,	'show_group_hardware',	0),
(50,	1,	'rule_dictionnary_software',	0),
(51,	1,	'rule_dictionnary_dropdown',	0),
(52,	1,	'budget',	0),
(53,	1,	'notification',	0),
(54,	1,	'rule_mailcollector',	0),
(55,	7,	'solutiontemplate',	23),
(56,	1,	'calendar',	0),
(57,	1,	'slm',	0),
(58,	1,	'rule_dictionnary_printer',	0),
(59,	1,	'problem',	0),
(60,	2,	'netpoint',	0),
(61,	4,	'knowbasecategory',	23),
(62,	5,	'itilcategory',	0),
(63,	1,	'itiltemplate',	0),
(64,	1,	'ticketrecurrent',	0),
(65,	1,	'ticketcost',	0),
(66,	6,	'changevalidation',	20),
(67,	1,	'ticketvalidation',	0),
(68,	2,	'computer',	33),
(69,	2,	'monitor',	33),
(70,	2,	'software',	33),
(71,	2,	'networking',	33),
(72,	2,	'internet',	1),
(73,	2,	'printer',	33),
(74,	2,	'peripheral',	33),
(75,	2,	'cartridge',	33),
(76,	2,	'consumable',	33),
(77,	2,	'phone',	33),
(78,	5,	'queuednotification',	0),
(79,	2,	'contact_enterprise',	33),
(80,	2,	'document',	33),
(81,	2,	'contract',	33),
(82,	2,	'infocom',	1),
(83,	2,	'knowbase',	10241),
(84,	2,	'reservation',	1025),
(85,	2,	'reports',	1),
(86,	2,	'dropdown',	0),
(87,	2,	'device',	0),
(88,	2,	'typedoc',	1),
(89,	2,	'link',	1),
(90,	2,	'config',	0),
(91,	2,	'rule_ticket',	0),
(92,	2,	'rule_import',	0),
(93,	2,	'rule_ldap',	0),
(94,	2,	'rule_softwarecategories',	0),
(95,	2,	'search_config',	1024),
(96,	4,	'location',	23),
(97,	6,	'domain',	0),
(98,	2,	'profile',	0),
(99,	2,	'user',	2049),
(100,	2,	'group',	33),
(101,	2,	'entity',	0),
(102,	2,	'transfer',	0),
(103,	2,	'logs',	0),
(104,	2,	'reminder_public',	1),
(105,	2,	'rssfeed_public',	1),
(106,	2,	'bookmark_public',	0),
(107,	2,	'backup',	0),
(108,	2,	'ticket',	168989),
(109,	2,	'followup',	5),
(110,	2,	'task',	1),
(111,	6,	'projecttask',	1025),
(112,	7,	'projecttask',	1025),
(113,	2,	'planning',	1),
(114,	1,	'state',	0),
(115,	1,	'taskcategory',	0),
(116,	2,	'statistic',	1),
(117,	2,	'password_update',	1),
(118,	2,	'show_group_hardware',	0),
(119,	2,	'rule_dictionnary_software',	0),
(120,	2,	'rule_dictionnary_dropdown',	0),
(121,	2,	'budget',	33),
(122,	2,	'notification',	0),
(123,	2,	'rule_mailcollector',	0),
(124,	5,	'solutiontemplate',	0),
(125,	6,	'solutiontemplate',	0),
(126,	2,	'calendar',	0),
(127,	2,	'slm',	0),
(128,	2,	'rule_dictionnary_printer',	0),
(129,	2,	'problem',	1057),
(130,	1,	'netpoint',	0),
(131,	3,	'knowbasecategory',	23),
(132,	4,	'itilcategory',	23),
(133,	2,	'itiltemplate',	0),
(134,	2,	'ticketrecurrent',	0),
(135,	2,	'ticketcost',	1),
(136,	4,	'changevalidation',	1044),
(137,	5,	'changevalidation',	20),
(138,	2,	'ticketvalidation',	15376),
(139,	3,	'computer',	127),
(140,	3,	'monitor',	127),
(141,	3,	'software',	127),
(142,	3,	'networking',	127),
(143,	3,	'internet',	31),
(144,	3,	'printer',	127),
(145,	3,	'peripheral',	127),
(146,	3,	'cartridge',	127),
(147,	3,	'consumable',	127),
(148,	3,	'phone',	127),
(149,	4,	'queuednotification',	31),
(150,	3,	'contact_enterprise',	127),
(151,	3,	'document',	127),
(152,	3,	'contract',	127),
(153,	3,	'infocom',	23),
(154,	3,	'knowbase',	14359),
(155,	3,	'reservation',	1055),
(156,	3,	'reports',	1),
(157,	3,	'dropdown',	23),
(158,	3,	'device',	23),
(159,	3,	'typedoc',	23),
(160,	3,	'link',	23),
(161,	3,	'config',	0),
(162,	3,	'rule_ticket',	1047),
(163,	3,	'rule_import',	0),
(164,	3,	'rule_ldap',	0),
(165,	3,	'rule_softwarecategories',	0),
(166,	3,	'search_config',	3072),
(167,	3,	'location',	23),
(168,	5,	'domain',	0),
(169,	3,	'profile',	1),
(170,	3,	'user',	7199),
(171,	3,	'group',	119),
(172,	3,	'entity',	33),
(173,	3,	'transfer',	1),
(174,	3,	'logs',	1),
(175,	3,	'reminder_public',	23),
(176,	3,	'rssfeed_public',	23),
(177,	3,	'bookmark_public',	23),
(178,	3,	'backup',	1024),
(179,	3,	'ticket',	261151),
(180,	3,	'followup',	15383),
(181,	3,	'task',	13329),
(182,	3,	'projecttask',	1121),
(183,	4,	'projecttask',	1121),
(184,	5,	'projecttask',	0),
(185,	3,	'planning',	3073),
(186,	7,	'taskcategory',	23),
(187,	7,	'netpoint',	23),
(188,	3,	'statistic',	1),
(189,	3,	'password_update',	1),
(190,	3,	'show_group_hardware',	0),
(191,	3,	'rule_dictionnary_software',	0),
(192,	3,	'rule_dictionnary_dropdown',	0),
(193,	3,	'budget',	127),
(194,	3,	'notification',	0),
(195,	3,	'rule_mailcollector',	23),
(196,	3,	'solutiontemplate',	23),
(197,	4,	'solutiontemplate',	23),
(198,	3,	'calendar',	23),
(199,	3,	'slm',	23),
(200,	3,	'rule_dictionnary_printer',	0),
(201,	3,	'problem',	1151),
(202,	2,	'knowbasecategory',	0),
(203,	3,	'itilcategory',	23),
(204,	3,	'itiltemplate',	23),
(205,	3,	'ticketrecurrent',	1),
(206,	3,	'ticketcost',	23),
(207,	2,	'changevalidation',	1044),
(208,	3,	'changevalidation',	1044),
(209,	3,	'ticketvalidation',	15376),
(210,	4,	'computer',	255),
(211,	4,	'monitor',	255),
(212,	4,	'software',	255),
(213,	4,	'networking',	255),
(214,	4,	'internet',	159),
(215,	4,	'printer',	255),
(216,	4,	'peripheral',	255),
(217,	4,	'cartridge',	255),
(218,	4,	'consumable',	255),
(219,	4,	'phone',	255),
(220,	4,	'contact_enterprise',	255),
(221,	4,	'document',	255),
(222,	4,	'contract',	255),
(223,	4,	'infocom',	23),
(224,	4,	'knowbase',	15383),
(225,	4,	'reservation',	1055),
(226,	4,	'reports',	1),
(227,	4,	'dropdown',	23),
(228,	4,	'device',	23),
(229,	4,	'typedoc',	23),
(230,	4,	'link',	159),
(231,	4,	'config',	3),
(232,	4,	'rule_ticket',	1047),
(233,	4,	'rule_import',	23),
(234,	4,	'rule_ldap',	23),
(235,	4,	'rule_softwarecategories',	23),
(236,	4,	'search_config',	3072),
(237,	2,	'location',	0),
(238,	4,	'domain',	23),
(239,	4,	'profile',	23),
(240,	4,	'user',	7327),
(241,	4,	'group',	119),
(242,	4,	'entity',	3327),
(243,	4,	'transfer',	23),
(244,	4,	'logs',	1),
(245,	4,	'reminder_public',	159),
(246,	4,	'rssfeed_public',	159),
(247,	4,	'bookmark_public',	23),
(248,	4,	'backup',	1045),
(249,	4,	'ticket',	261151),
(250,	4,	'followup',	15383),
(251,	4,	'task',	13329),
(252,	7,	'project',	1151),
(253,	1,	'projecttask',	0),
(254,	2,	'projecttask',	1025),
(255,	4,	'planning',	3073),
(256,	6,	'taskcategory',	0),
(257,	6,	'netpoint',	0),
(258,	4,	'statistic',	1),
(259,	4,	'password_update',	1),
(260,	4,	'show_group_hardware',	1),
(261,	4,	'rule_dictionnary_software',	23),
(262,	4,	'rule_dictionnary_dropdown',	23),
(263,	4,	'budget',	127),
(264,	4,	'notification',	23),
(265,	4,	'rule_mailcollector',	23),
(266,	1,	'solutiontemplate',	0),
(267,	2,	'solutiontemplate',	0),
(268,	4,	'calendar',	23),
(269,	4,	'slm',	23),
(270,	4,	'rule_dictionnary_printer',	23),
(271,	4,	'problem',	1151),
(272,	1,	'knowbasecategory',	0),
(273,	2,	'itilcategory',	0),
(274,	4,	'itiltemplate',	23),
(275,	4,	'ticketrecurrent',	23),
(276,	4,	'ticketcost',	23),
(277,	7,	'change',	1151),
(278,	1,	'changevalidation',	0),
(279,	4,	'ticketvalidation',	15376),
(280,	5,	'computer',	0),
(281,	5,	'monitor',	0),
(282,	5,	'software',	0),
(283,	5,	'networking',	0),
(284,	5,	'internet',	0),
(285,	5,	'printer',	0),
(286,	5,	'peripheral',	0),
(287,	5,	'cartridge',	0),
(288,	5,	'consumable',	0),
(289,	5,	'phone',	0),
(290,	3,	'queuednotification',	0),
(291,	5,	'contact_enterprise',	0),
(292,	5,	'document',	0),
(293,	5,	'contract',	0),
(294,	5,	'infocom',	0),
(295,	5,	'knowbase',	10240),
(296,	5,	'reservation',	0),
(297,	5,	'reports',	0),
(298,	5,	'dropdown',	0),
(299,	5,	'device',	0),
(300,	5,	'typedoc',	0),
(301,	5,	'link',	0),
(302,	5,	'config',	0),
(303,	5,	'rule_ticket',	0),
(304,	5,	'rule_import',	0),
(305,	5,	'rule_ldap',	0),
(306,	5,	'rule_softwarecategories',	0),
(307,	5,	'search_config',	0),
(308,	1,	'location',	0),
(309,	3,	'domain',	23),
(310,	5,	'profile',	0),
(311,	5,	'user',	1025),
(312,	5,	'group',	0),
(313,	5,	'entity',	0),
(314,	5,	'transfer',	0),
(315,	5,	'logs',	0),
(316,	5,	'reminder_public',	0),
(317,	5,	'rssfeed_public',	0),
(318,	5,	'bookmark_public',	0),
(319,	5,	'backup',	0),
(320,	5,	'ticket',	140295),
(321,	5,	'followup',	12295),
(322,	5,	'task',	8193),
(323,	4,	'project',	1151),
(324,	5,	'project',	1151),
(325,	6,	'project',	1151),
(326,	5,	'planning',	1),
(327,	5,	'taskcategory',	0),
(328,	5,	'netpoint',	0),
(329,	5,	'statistic',	1),
(330,	5,	'password_update',	1),
(331,	5,	'show_group_hardware',	0),
(332,	5,	'rule_dictionnary_software',	0),
(333,	5,	'rule_dictionnary_dropdown',	0),
(334,	5,	'budget',	0),
(335,	5,	'notification',	0),
(336,	5,	'rule_mailcollector',	0),
(337,	6,	'state',	0),
(338,	7,	'state',	23),
(339,	5,	'calendar',	0),
(340,	5,	'slm',	0),
(341,	5,	'rule_dictionnary_printer',	0),
(342,	5,	'problem',	1024),
(343,	7,	'knowbasecategory',	23),
(344,	1,	'itilcategory',	0),
(345,	5,	'itiltemplate',	0),
(346,	5,	'ticketrecurrent',	0),
(347,	5,	'ticketcost',	23),
(348,	5,	'change',	1054),
(349,	6,	'change',	1151),
(350,	5,	'ticketvalidation',	3088),
(351,	6,	'computer',	127),
(352,	6,	'monitor',	127),
(353,	6,	'software',	127),
(354,	6,	'networking',	127),
(355,	6,	'internet',	31),
(356,	6,	'printer',	127),
(357,	6,	'peripheral',	127),
(358,	6,	'cartridge',	127),
(359,	6,	'consumable',	127),
(360,	6,	'phone',	127),
(361,	2,	'queuednotification',	0),
(362,	6,	'contact_enterprise',	96),
(363,	6,	'document',	127),
(364,	6,	'contract',	96),
(365,	6,	'infocom',	0),
(366,	6,	'knowbase',	14359),
(367,	6,	'reservation',	1055),
(368,	6,	'reports',	1),
(369,	6,	'dropdown',	0),
(370,	6,	'device',	0),
(371,	6,	'typedoc',	0),
(372,	6,	'link',	0),
(373,	6,	'config',	0),
(374,	6,	'rule_ticket',	0),
(375,	6,	'rule_import',	0),
(376,	6,	'rule_ldap',	0),
(377,	6,	'rule_softwarecategories',	0),
(378,	6,	'search_config',	0),
(379,	2,	'domain',	0),
(380,	6,	'profile',	0),
(381,	6,	'user',	1055),
(382,	6,	'group',	1),
(383,	6,	'entity',	33),
(384,	6,	'transfer',	1),
(385,	6,	'logs',	0),
(386,	6,	'reminder_public',	23),
(387,	6,	'rssfeed_public',	23),
(388,	6,	'bookmark_public',	0),
(389,	6,	'backup',	0),
(390,	6,	'ticket',	166919),
(391,	6,	'followup',	13319),
(392,	6,	'task',	13329),
(393,	1,	'project',	0),
(394,	2,	'project',	1025),
(395,	3,	'project',	1151),
(396,	6,	'planning',	1),
(397,	4,	'taskcategory',	23),
(398,	4,	'netpoint',	23),
(399,	6,	'statistic',	1),
(400,	6,	'password_update',	1),
(401,	6,	'show_group_hardware',	0),
(402,	6,	'rule_dictionnary_software',	0),
(403,	6,	'rule_dictionnary_dropdown',	0),
(404,	6,	'budget',	96),
(405,	6,	'notification',	0),
(406,	6,	'rule_mailcollector',	0),
(407,	4,	'state',	23),
(408,	5,	'state',	0),
(409,	6,	'calendar',	0),
(410,	6,	'slm',	1),
(411,	6,	'rule_dictionnary_printer',	0),
(412,	6,	'problem',	1121),
(413,	6,	'knowbasecategory',	0),
(414,	7,	'itilcategory',	23),
(415,	7,	'location',	23),
(416,	6,	'itiltemplate',	1),
(417,	6,	'ticketrecurrent',	1),
(418,	6,	'ticketcost',	23),
(419,	3,	'change',	1151),
(420,	4,	'change',	1151),
(421,	6,	'ticketvalidation',	3088),
(422,	7,	'computer',	127),
(423,	7,	'monitor',	127),
(424,	7,	'software',	127),
(425,	7,	'networking',	127),
(426,	7,	'internet',	31),
(427,	7,	'printer',	127),
(428,	7,	'peripheral',	127),
(429,	7,	'cartridge',	127),
(430,	7,	'consumable',	127),
(431,	7,	'phone',	127),
(432,	1,	'queuednotification',	0),
(433,	7,	'contact_enterprise',	96),
(434,	7,	'document',	127),
(435,	7,	'contract',	96),
(436,	7,	'infocom',	0),
(437,	7,	'knowbase',	14359),
(438,	7,	'reservation',	1055),
(439,	7,	'reports',	1),
(440,	7,	'dropdown',	0),
(441,	7,	'device',	0),
(442,	7,	'typedoc',	0),
(443,	7,	'link',	0),
(444,	7,	'config',	0),
(445,	7,	'rule_ticket',	1047),
(446,	7,	'rule_import',	0),
(447,	7,	'rule_ldap',	0),
(448,	7,	'rule_softwarecategories',	0),
(449,	7,	'search_config',	0),
(450,	1,	'domain',	0),
(451,	7,	'profile',	0),
(452,	7,	'user',	1055),
(453,	7,	'group',	1),
(454,	7,	'entity',	33),
(455,	7,	'transfer',	1),
(456,	7,	'logs',	1),
(457,	7,	'reminder_public',	23),
(458,	7,	'rssfeed_public',	23),
(459,	7,	'bookmark_public',	0),
(460,	7,	'backup',	0),
(461,	7,	'ticket',	261151),
(462,	7,	'followup',	15383),
(463,	7,	'task',	13329),
(464,	7,	'queuednotification',	0),
(465,	7,	'planning',	3073),
(466,	3,	'taskcategory',	23),
(467,	3,	'netpoint',	23),
(468,	7,	'statistic',	1),
(469,	7,	'password_update',	1),
(470,	7,	'show_group_hardware',	0),
(471,	7,	'rule_dictionnary_software',	0),
(472,	7,	'rule_dictionnary_dropdown',	0),
(473,	7,	'budget',	96),
(474,	7,	'notification',	0),
(475,	7,	'rule_mailcollector',	23),
(476,	7,	'changevalidation',	1044),
(477,	3,	'state',	23),
(478,	7,	'calendar',	23),
(479,	7,	'slm',	23),
(480,	7,	'rule_dictionnary_printer',	0),
(481,	7,	'problem',	1151),
(482,	5,	'knowbasecategory',	0),
(483,	6,	'itilcategory',	0),
(484,	6,	'location',	0),
(485,	7,	'itiltemplate',	23),
(486,	7,	'ticketrecurrent',	1),
(487,	7,	'ticketcost',	23),
(488,	1,	'change',	0),
(489,	2,	'change',	1057),
(490,	7,	'ticketvalidation',	15376),
(491,	8,	'backup',	1),
(492,	8,	'bookmark_public',	1),
(493,	8,	'budget',	33),
(494,	8,	'calendar',	1),
(495,	8,	'cartridge',	33),
(496,	8,	'change',	1057),
(497,	8,	'changevalidation',	0),
(498,	8,	'computer',	33),
(499,	8,	'config',	1),
(500,	8,	'consumable',	33),
(501,	8,	'contact_enterprise',	33),
(502,	8,	'contract',	33),
(503,	8,	'device',	1),
(504,	8,	'document',	33),
(505,	8,	'domain',	1),
(506,	8,	'dropdown',	1),
(507,	8,	'entity',	33),
(508,	8,	'followup',	8193),
(509,	8,	'global_validation',	0),
(510,	8,	'group',	33),
(511,	8,	'infocom',	1),
(512,	8,	'internet',	1),
(513,	8,	'itilcategory',	1),
(514,	8,	'knowbase',	10241),
(515,	8,	'knowbasecategory',	1),
(516,	8,	'link',	1),
(517,	8,	'location',	1),
(518,	8,	'logs',	1),
(519,	8,	'monitor',	33),
(520,	8,	'netpoint',	1),
(521,	8,	'networking',	33),
(522,	8,	'notification',	1),
(523,	8,	'password_update',	0),
(524,	8,	'peripheral',	33),
(525,	8,	'phone',	33),
(526,	8,	'planning',	3073),
(527,	8,	'printer',	33),
(528,	8,	'problem',	1057),
(529,	8,	'profile',	1),
(530,	8,	'project',	1057),
(531,	8,	'projecttask',	33),
(532,	8,	'queuednotification',	1),
(533,	8,	'reminder_public',	1),
(534,	8,	'reports',	1),
(535,	8,	'reservation',	1),
(536,	8,	'rssfeed_public',	1),
(537,	8,	'rule_dictionnary_dropdown',	1),
(538,	8,	'rule_dictionnary_printer',	1),
(539,	8,	'rule_dictionnary_software',	1),
(540,	8,	'rule_import',	1),
(541,	8,	'rule_ldap',	1),
(542,	8,	'rule_mailcollector',	1),
(543,	8,	'rule_softwarecategories',	1),
(544,	8,	'rule_ticket',	1),
(545,	8,	'search_config',	0),
(546,	8,	'show_group_hardware',	1),
(547,	8,	'slm',	1),
(548,	8,	'software',	33),
(549,	8,	'solutiontemplate',	1),
(550,	8,	'state',	1),
(551,	8,	'statistic',	1),
(552,	8,	'task',	8193),
(553,	8,	'taskcategory',	1),
(554,	8,	'ticket',	138241),
(555,	8,	'ticketcost',	1),
(556,	8,	'ticketrecurrent',	1),
(557,	8,	'itiltemplate',	1),
(558,	8,	'ticketvalidation',	0),
(559,	8,	'transfer',	1),
(560,	8,	'typedoc',	1),
(561,	8,	'user',	1),
(562,	1,	'license',	0),
(563,	2,	'license',	33),
(564,	3,	'license',	127),
(565,	4,	'license',	255),
(566,	5,	'license',	0),
(567,	6,	'license',	127),
(568,	7,	'license',	127),
(569,	8,	'license',	33),
(570,	1,	'line',	0),
(571,	2,	'line',	33),
(572,	3,	'line',	127),
(573,	4,	'line',	255),
(574,	5,	'line',	0),
(575,	6,	'line',	127),
(576,	7,	'line',	127),
(577,	8,	'line',	33),
(578,	1,	'lineoperator',	0),
(579,	2,	'lineoperator',	33),
(580,	3,	'lineoperator',	23),
(581,	4,	'lineoperator',	23),
(582,	5,	'lineoperator',	0),
(583,	6,	'lineoperator',	0),
(584,	7,	'lineoperator',	23),
(585,	8,	'lineoperator',	1),
(586,	1,	'devicesimcard_pinpuk',	0),
(587,	2,	'devicesimcard_pinpuk',	1),
(588,	3,	'devicesimcard_pinpuk',	3),
(589,	4,	'devicesimcard_pinpuk',	3),
(590,	5,	'devicesimcard_pinpuk',	0),
(591,	6,	'devicesimcard_pinpuk',	3),
(592,	7,	'devicesimcard_pinpuk',	3),
(593,	8,	'devicesimcard_pinpuk',	1),
(594,	1,	'certificate',	0),
(595,	2,	'certificate',	33),
(596,	3,	'certificate',	127),
(597,	4,	'certificate',	255),
(598,	5,	'certificate',	0),
(599,	6,	'certificate',	127),
(600,	7,	'certificate',	127),
(601,	8,	'certificate',	33),
(602,	1,	'datacenter',	0),
(603,	2,	'datacenter',	1),
(604,	3,	'datacenter',	31),
(605,	4,	'datacenter',	31),
(606,	5,	'datacenter',	0),
(607,	6,	'datacenter',	31),
(608,	7,	'datacenter',	31),
(609,	8,	'datacenter',	1),
(610,	4,	'rule_asset',	1047),
(611,	1,	'personalization',	3),
(612,	2,	'personalization',	3),
(613,	3,	'personalization',	3),
(614,	4,	'personalization',	3),
(615,	5,	'personalization',	3),
(616,	6,	'personalization',	3),
(617,	7,	'personalization',	3),
(618,	8,	'personalization',	3),
(619,	1,	'rule_asset',	0),
(620,	2,	'rule_asset',	0),
(621,	3,	'rule_asset',	0),
(622,	5,	'rule_asset',	0),
(623,	6,	'rule_asset',	0),
(624,	7,	'rule_asset',	0),
(625,	8,	'rule_asset',	0),
(626,	1,	'global_validation',	0),
(627,	2,	'global_validation',	0),
(628,	3,	'global_validation',	0),
(629,	4,	'global_validation',	0),
(630,	5,	'global_validation',	0),
(631,	6,	'global_validation',	0),
(632,	7,	'global_validation',	0),
(633,	1,	'cluster',	0),
(634,	2,	'cluster',	1),
(635,	3,	'cluster',	31),
(636,	4,	'cluster',	31),
(637,	5,	'cluster',	0),
(638,	6,	'cluster',	31),
(639,	7,	'cluster',	31),
(640,	8,	'cluster',	1),
(641,	1,	'externalevent',	0),
(642,	2,	'externalevent',	1),
(643,	3,	'externalevent',	1055),
(644,	4,	'externalevent',	1055),
(645,	5,	'externalevent',	0),
(646,	6,	'externalevent',	1),
(647,	7,	'externalevent',	31),
(648,	8,	'externalevent',	1),
(649,	1,	'dashboard',	0),
(650,	2,	'dashboard',	0),
(651,	3,	'dashboard',	0),
(652,	4,	'dashboard',	23),
(653,	5,	'dashboard',	0),
(654,	6,	'dashboard',	0),
(655,	7,	'dashboard',	0),
(656,	8,	'dashboard',	0),
(657,	1,	'appliance',	0),
(658,	2,	'appliance',	1),
(659,	3,	'appliance',	31),
(660,	4,	'appliance',	31),
(661,	5,	'appliance',	0),
(662,	6,	'appliance',	31),
(663,	7,	'appliance',	31),
(664,	8,	'appliance',	1);

DROP TABLE IF EXISTS `glpi_profiles`;
CREATE TABLE `glpi_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `interface` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'helpdesk',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `helpdesk_hardware` int(11) NOT NULL DEFAULT 0,
  `helpdesk_item_type` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `ticket_status` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'json encoded array of from/dest allowed status change',
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `problem_status` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'json encoded array of from/dest allowed status change',
  `create_ticket_on_login` tinyint(1) NOT NULL DEFAULT 0,
  `tickettemplates_id` int(11) NOT NULL DEFAULT 0,
  `changetemplates_id` int(11) NOT NULL DEFAULT 0,
  `problemtemplates_id` int(11) NOT NULL DEFAULT 0,
  `change_status` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'json encoded array of from/dest allowed status change',
  `managed_domainrecordtypes` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interface` (`interface`),
  KEY `is_default` (`is_default`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `tickettemplates_id` (`tickettemplates_id`),
  KEY `changetemplates_id` (`changetemplates_id`),
  KEY `problemtemplates_id` (`problemtemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profiles` (`id`, `name`, `interface`, `is_default`, `helpdesk_hardware`, `helpdesk_item_type`, `ticket_status`, `date_mod`, `comment`, `problem_status`, `create_ticket_on_login`, `tickettemplates_id`, `changetemplates_id`, `problemtemplates_id`, `change_status`, `managed_domainrecordtypes`, `date_creation`) VALUES
(1,	'Self-Service',	'helpdesk',	1,	1,	'[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]',	'{\"1\":{\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"2\":{\"1\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"3\":{\"1\":0,\"2\":0,\"4\":0,\"5\":0,\"6\":0},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"5\":0,\"6\":0},\"5\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0},\"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}',	NULL,	'',	'[]',	0,	0,	0,	0,	NULL,	'[]',	NULL),
(2,	'Observer',	'central',	0,	1,	'[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]',	'[]',	NULL,	'',	'[]',	0,	0,	0,	0,	NULL,	'[]',	NULL),
(3,	'Admin',	'central',	0,	3,	'[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]',	'[]',	NULL,	'',	'[]',	0,	0,	0,	0,	NULL,	'[-1]',	NULL),
(4,	'Super-Admin',	'central',	0,	3,	'[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]',	'[]',	NULL,	'',	'[]',	0,	0,	0,	0,	NULL,	'[-1]',	NULL),
(5,	'Hotliner',	'central',	0,	3,	'[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]',	'[]',	NULL,	'',	'[]',	1,	0,	0,	0,	NULL,	'[]',	NULL),
(6,	'Technician',	'central',	0,	3,	'[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]',	'[]',	NULL,	'',	'[]',	0,	0,	0,	0,	NULL,	'[]',	NULL),
(7,	'Supervisor',	'central',	0,	3,	'[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]',	'[]',	NULL,	'',	'[]',	0,	0,	0,	0,	NULL,	'[]',	NULL),
(8,	'Read-Only',	'central',	0,	0,	'[]',	'{\"1\":{\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"2\":{\"1\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"3\":{\"1\":0,\"2\":0,\"4\":0,\"5\":0,\"6\":0},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"5\":0,\"6\":0},\"5\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"6\":0},\"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}',	NULL,	'This profile defines read-only access. It is used when objects are locked. It can also be used to give to users rights to unlock objects.',	'{\"1\":{\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"2\":{\"1\":0,\"7\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"3\":{\"1\":0,\"7\":0,\"2\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0}}',	0,	0,	0,	0,	'{\"1\":{\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"9\":{\"1\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"10\":{\"1\":0,\"9\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"9\":0,\"10\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"11\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"12\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0}}',	'[]',	NULL);

DROP TABLE IF EXISTS `glpi_profiles_reminders`;
CREATE TABLE `glpi_profiles_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reminders_id` int(11) NOT NULL DEFAULT 0,
  `profiles_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reminders_id` (`reminders_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_profiles_rssfeeds`;
CREATE TABLE `glpi_profiles_rssfeeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rssfeeds_id` int(11) NOT NULL DEFAULT 0,
  `profiles_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `rssfeeds_id` (`rssfeeds_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_profiles_users`;
CREATE TABLE `glpi_profiles_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `profiles_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 1,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `users_id` (`users_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_dynamic` (`is_dynamic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profiles_users` (`id`, `users_id`, `profiles_id`, `entities_id`, `is_recursive`, `is_dynamic`) VALUES
(2,	2,	4,	0,	1,	0),
(3,	3,	1,	0,	1,	0),
(4,	4,	6,	0,	1,	0),
(5,	5,	2,	0,	1,	0);

DROP TABLE IF EXISTS `glpi_projectcosts`;
CREATE TABLE `glpi_projectcosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projects_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `cost` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `budgets_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `projects_id` (`projects_id`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `budgets_id` (`budgets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projects`;
CREATE TABLE `glpi_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 1,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `projects_id` int(11) NOT NULL DEFAULT 0,
  `projectstates_id` int(11) NOT NULL DEFAULT 0,
  `projecttypes_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `plan_start_date` timestamp NULL DEFAULT NULL,
  `plan_end_date` timestamp NULL DEFAULT NULL,
  `real_start_date` timestamp NULL DEFAULT NULL,
  `real_end_date` timestamp NULL DEFAULT NULL,
  `percent_done` int(11) NOT NULL DEFAULT 0,
  `auto_percent_done` tinyint(1) NOT NULL DEFAULT 0,
  `show_on_global_gantt` tinyint(1) NOT NULL DEFAULT 0,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `projecttemplates_id` int(11) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `code` (`code`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `projects_id` (`projects_id`),
  KEY `projectstates_id` (`projectstates_id`),
  KEY `projecttypes_id` (`projecttypes_id`),
  KEY `priority` (`priority`),
  KEY `date` (`date`),
  KEY `date_mod` (`date_mod`),
  KEY `users_id` (`users_id`),
  KEY `groups_id` (`groups_id`),
  KEY `plan_start_date` (`plan_start_date`),
  KEY `plan_end_date` (`plan_end_date`),
  KEY `real_start_date` (`real_start_date`),
  KEY `real_end_date` (`real_end_date`),
  KEY `percent_done` (`percent_done`),
  KEY `show_on_global_gantt` (`show_on_global_gantt`),
  KEY `date_creation` (`date_creation`),
  KEY `projecttemplates_id` (`projecttemplates_id`),
  KEY `is_template` (`is_template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projectstates`;
CREATE TABLE `glpi_projectstates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_finished` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_finished` (`is_finished`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_projectstates` (`id`, `name`, `comment`, `color`, `is_finished`, `date_mod`, `date_creation`) VALUES
(1,	'New',	NULL,	'#06ff00',	0,	NULL,	NULL),
(2,	'Processing',	NULL,	'#ffb800',	0,	NULL,	NULL),
(3,	'Closed',	NULL,	'#ff0000',	1,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_projecttasks`;
CREATE TABLE `glpi_projecttasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `projects_id` int(11) NOT NULL DEFAULT 0,
  `projecttasks_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `plan_start_date` timestamp NULL DEFAULT NULL,
  `plan_end_date` timestamp NULL DEFAULT NULL,
  `real_start_date` timestamp NULL DEFAULT NULL,
  `real_end_date` timestamp NULL DEFAULT NULL,
  `planned_duration` int(11) NOT NULL DEFAULT 0,
  `effective_duration` int(11) NOT NULL DEFAULT 0,
  `projectstates_id` int(11) NOT NULL DEFAULT 0,
  `projecttasktypes_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `percent_done` int(11) NOT NULL DEFAULT 0,
  `auto_percent_done` tinyint(1) NOT NULL DEFAULT 0,
  `is_milestone` tinyint(1) NOT NULL DEFAULT 0,
  `projecttasktemplates_id` int(11) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `projects_id` (`projects_id`),
  KEY `projecttasks_id` (`projecttasks_id`),
  KEY `date` (`date`),
  KEY `date_mod` (`date_mod`),
  KEY `users_id` (`users_id`),
  KEY `plan_start_date` (`plan_start_date`),
  KEY `plan_end_date` (`plan_end_date`),
  KEY `real_start_date` (`real_start_date`),
  KEY `real_end_date` (`real_end_date`),
  KEY `percent_done` (`percent_done`),
  KEY `projectstates_id` (`projectstates_id`),
  KEY `projecttasktypes_id` (`projecttasktypes_id`),
  KEY `projecttasktemplates_id` (`projecttasktemplates_id`),
  KEY `is_template` (`is_template`),
  KEY `is_milestone` (`is_milestone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projecttasks_tickets`;
CREATE TABLE `glpi_projecttasks_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `projecttasks_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickets_id`,`projecttasks_id`),
  KEY `projects_id` (`projecttasks_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projecttaskteams`;
CREATE TABLE `glpi_projecttaskteams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projecttasks_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`projecttasks_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projecttasktemplates`;
CREATE TABLE `glpi_projecttasktemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `projects_id` int(11) NOT NULL DEFAULT 0,
  `projecttasks_id` int(11) NOT NULL DEFAULT 0,
  `plan_start_date` timestamp NULL DEFAULT NULL,
  `plan_end_date` timestamp NULL DEFAULT NULL,
  `real_start_date` timestamp NULL DEFAULT NULL,
  `real_end_date` timestamp NULL DEFAULT NULL,
  `planned_duration` int(11) NOT NULL DEFAULT 0,
  `effective_duration` int(11) NOT NULL DEFAULT 0,
  `projectstates_id` int(11) NOT NULL DEFAULT 0,
  `projecttasktypes_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `percent_done` int(11) NOT NULL DEFAULT 0,
  `is_milestone` tinyint(1) NOT NULL DEFAULT 0,
  `comments` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `projects_id` (`projects_id`),
  KEY `projecttasks_id` (`projecttasks_id`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`),
  KEY `users_id` (`users_id`),
  KEY `plan_start_date` (`plan_start_date`),
  KEY `plan_end_date` (`plan_end_date`),
  KEY `real_start_date` (`real_start_date`),
  KEY `real_end_date` (`real_end_date`),
  KEY `percent_done` (`percent_done`),
  KEY `projectstates_id` (`projectstates_id`),
  KEY `projecttasktypes_id` (`projecttasktypes_id`),
  KEY `is_milestone` (`is_milestone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projecttasktypes`;
CREATE TABLE `glpi_projecttasktypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projectteams`;
CREATE TABLE `glpi_projectteams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projects_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`projects_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_projecttypes`;
CREATE TABLE `glpi_projecttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_queuednotifications`;
CREATE TABLE `glpi_queuednotifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `notificationtemplates_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `sent_try` int(11) NOT NULL DEFAULT 0,
  `create_time` timestamp NULL DEFAULT NULL,
  `send_time` timestamp NULL DEFAULT NULL,
  `sent_time` timestamp NULL DEFAULT NULL,
  `name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `sender` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `sendername` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `recipient` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `recipientname` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `replyto` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `replytoname` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `headers` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `body_html` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `body_text` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `messageid` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `documents` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `mode` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'See Notification_NotificationTemplate::MODE_* constants',
  PRIMARY KEY (`id`),
  KEY `item` (`itemtype`,`items_id`,`notificationtemplates_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `sent_try` (`sent_try`),
  KEY `create_time` (`create_time`),
  KEY `send_time` (`send_time`),
  KEY `sent_time` (`sent_time`),
  KEY `mode` (`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_rackmodels`;
CREATE TABLE `glpi_rackmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `product_number` (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_racks`;
CREATE TABLE `glpi_racks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rackmodels_id` int(11) DEFAULT NULL,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `racktypes_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `depth` int(11) DEFAULT NULL,
  `number_units` int(11) DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `dcrooms_id` int(11) NOT NULL DEFAULT 0,
  `room_orientation` int(11) NOT NULL DEFAULT 0,
  `position` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bgcolor` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
  `max_power` int(11) NOT NULL DEFAULT 0,
  `mesured_power` int(11) NOT NULL DEFAULT 0,
  `max_weight` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `locations_id` (`locations_id`),
  KEY `rackmodels_id` (`rackmodels_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `racktypes_id` (`racktypes_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `group_id_tech` (`groups_id_tech`),
  KEY `is_template` (`is_template`),
  KEY `is_deleted` (`is_deleted`),
  KEY `dcrooms_id` (`dcrooms_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_racktypes`;
CREATE TABLE `glpi_racktypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `name` (`name`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_registeredids`;
CREATE TABLE `glpi_registeredids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `device_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'USB, PCI ...',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `item` (`items_id`,`itemtype`),
  KEY `device_type` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_reminders`;
CREATE TABLE `glpi_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `is_planned` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 0,
  `begin_view_date` timestamp NULL DEFAULT NULL,
  `end_view_date` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `date` (`date`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `users_id` (`users_id`),
  KEY `is_planned` (`is_planned`),
  KEY `state` (`state`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_reminders_users`;
CREATE TABLE `glpi_reminders_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reminders_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reminders_id` (`reminders_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_remindertranslations`;
CREATE TABLE `glpi_remindertranslations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reminders_id` int(11) NOT NULL DEFAULT 0,
  `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`reminders_id`,`language`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_requesttypes`;
CREATE TABLE `glpi_requesttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_helpdesk_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_followup_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_mail_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_mailfollowup_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_ticketheader` tinyint(1) NOT NULL DEFAULT 1,
  `is_itilfollowup` tinyint(1) NOT NULL DEFAULT 1,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_helpdesk_default` (`is_helpdesk_default`),
  KEY `is_followup_default` (`is_followup_default`),
  KEY `is_mail_default` (`is_mail_default`),
  KEY `is_mailfollowup_default` (`is_mailfollowup_default`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `is_active` (`is_active`),
  KEY `is_ticketheader` (`is_ticketheader`),
  KEY `is_itilfollowup` (`is_itilfollowup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_requesttypes` (`id`, `name`, `is_helpdesk_default`, `is_followup_default`, `is_mail_default`, `is_mailfollowup_default`, `is_active`, `is_ticketheader`, `is_itilfollowup`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'Helpdesk',	1,	1,	0,	0,	1,	1,	1,	NULL,	NULL,	NULL),
(2,	'E-Mail',	0,	0,	1,	1,	1,	1,	1,	NULL,	NULL,	NULL),
(3,	'Phone',	0,	0,	0,	0,	1,	1,	1,	NULL,	NULL,	NULL),
(4,	'Direct',	0,	0,	0,	0,	1,	1,	1,	NULL,	NULL,	NULL),
(5,	'Written',	0,	0,	0,	0,	1,	1,	1,	NULL,	NULL,	NULL),
(6,	'Other',	0,	0,	0,	0,	1,	1,	1,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_reservationitems`;
CREATE TABLE `glpi_reservationitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_reservations`;
CREATE TABLE `glpi_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservationitems_id` int(11) NOT NULL DEFAULT 0,
  `begin` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `group` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `reservationitems_id` (`reservationitems_id`),
  KEY `users_id` (`users_id`),
  KEY `resagroup` (`reservationitems_id`,`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_rssfeeds`;
CREATE TABLE `glpi_rssfeeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `refresh_rate` int(11) NOT NULL DEFAULT 86400,
  `max_items` int(11) NOT NULL DEFAULT 20,
  `have_error` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `users_id` (`users_id`),
  KEY `date_mod` (`date_mod`),
  KEY `have_error` (`have_error`),
  KEY `is_active` (`is_active`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_rssfeeds_users`;
CREATE TABLE `glpi_rssfeeds_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rssfeeds_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `rssfeeds_id` (`rssfeeds_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ruleactions`;
CREATE TABLE `glpi_ruleactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rules_id` int(11) NOT NULL DEFAULT 0,
  `action_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'VALUE IN (assign, regex_result, append_regex_result, affectbyip, affectbyfqdn, affectbymac)',
  `field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rules_id` (`rules_id`),
  KEY `field_value` (`field`(50),`value`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_ruleactions` (`id`, `rules_id`, `action_type`, `field`, `value`) VALUES
(2,	2,	'assign',	'entities_id',	'0'),
(3,	3,	'assign',	'entities_id',	'0'),
(4,	4,	'assign',	'_refuse_email_no_response',	'1'),
(5,	5,	'assign',	'_refuse_email_no_response',	'1'),
(6,	6,	'fromitem',	'locations_id',	'1'),
(7,	7,	'fromuser',	'locations_id',	'1'),
(8,	8,	'assign',	'_import_category',	'1'),
(9,	9,	'regex_result',	'_affect_user_by_regex',	'#0'),
(10,	10,	'regex_result',	'_affect_user_by_regex',	'#0'),
(11,	11,	'regex_result',	'_affect_user_by_regex',	'#0');

DROP TABLE IF EXISTS `glpi_rulecriterias`;
CREATE TABLE `glpi_rulecriterias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rules_id` int(11) NOT NULL DEFAULT 0,
  `criteria` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition` int(11) NOT NULL DEFAULT 0 COMMENT 'see define.php PATTERN_* and REGEX_* constant',
  `pattern` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rules_id` (`rules_id`),
  KEY `condition` (`condition`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulecriterias` (`id`, `rules_id`, `criteria`, `condition`, `pattern`) VALUES
(2,	2,	'TYPE',	0,	'3'),
(3,	2,	'TYPE',	0,	'2'),
(5,	3,	'subject',	6,	'/.*/'),
(6,	4,	'x-auto-response-suppress',	6,	'/\\S+/'),
(7,	5,	'auto-submitted',	6,	'/^(?!.*no).+$/i'),
(9,	6,	'locations_id',	9,	'1'),
(10,	6,	'items_locations',	8,	'1'),
(11,	7,	'locations_id',	9,	'1'),
(12,	7,	'_locations_id_of_requester',	8,	'1'),
(13,	8,	'name',	0,	'*'),
(14,	9,	'_itemtype',	0,	'Computer'),
(15,	9,	'_auto',	0,	'1'),
(16,	9,	'contact',	6,	'/(.*)@/'),
(17,	10,	'_itemtype',	0,	'Computer'),
(18,	10,	'_auto',	0,	'1'),
(19,	10,	'contact',	6,	'/(.*),/'),
(20,	11,	'_itemtype',	0,	'Computer'),
(21,	11,	'_auto',	0,	'1'),
(22,	11,	'contact',	6,	'/(.*)/');

DROP TABLE IF EXISTS `glpi_rulerightparameters`;
CREATE TABLE `glpi_rulerightparameters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulerightparameters` (`id`, `name`, `value`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'(LDAP)Organization',	'o',	'',	NULL,	NULL),
(2,	'(LDAP)Common Name',	'cn',	'',	NULL,	NULL),
(3,	'(LDAP)Department Number',	'departmentnumber',	'',	NULL,	NULL),
(4,	'(LDAP)Email',	'mail',	'',	NULL,	NULL),
(5,	'Object Class',	'objectclass',	'',	NULL,	NULL),
(6,	'(LDAP)User ID',	'uid',	'',	NULL,	NULL),
(7,	'(LDAP)Telephone Number',	'phone',	'',	NULL,	NULL),
(8,	'(LDAP)Employee Number',	'employeenumber',	'',	NULL,	NULL),
(9,	'(LDAP)Manager',	'manager',	'',	NULL,	NULL),
(10,	'(LDAP)DistinguishedName',	'dn',	'',	NULL,	NULL),
(12,	'(AD)User ID',	'samaccountname',	'',	NULL,	NULL),
(13,	'(LDAP) Title',	'title',	'',	NULL,	NULL),
(14,	'(LDAP) MemberOf',	'memberof',	'',	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_rules`;
CREATE TABLE `glpi_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `sub_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ranking` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `match` char(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'see define.php *_MATCHING constant',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_active` (`is_active`),
  KEY `sub_type` (`sub_type`),
  KEY `date_mod` (`date_mod`),
  KEY `is_recursive` (`is_recursive`),
  KEY `condition` (`condition`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rules` (`id`, `entities_id`, `sub_type`, `ranking`, `name`, `description`, `match`, `is_active`, `comment`, `date_mod`, `is_recursive`, `uuid`, `condition`, `date_creation`) VALUES
(2,	0,	'RuleRight',	1,	'Root',	'',	'OR',	1,	NULL,	NULL,	0,	'500717c8-2bd6e957-53a12b5fd35745.02608131',	0,	NULL),
(3,	0,	'RuleMailCollector',	3,	'Root',	'',	'OR',	1,	NULL,	NULL,	0,	'500717c8-2bd6e957-53a12b5fd36404.54713349',	0,	NULL),
(4,	0,	'RuleMailCollector',	1,	'X-Auto-Response-Suppress',	'Exclude Auto-Reply emails using X-Auto-Response-Suppress header',	'AND',	0,	NULL,	NULL,	1,	'500717c8-2bd6e957-53a12b5fd36d97.94503423',	0,	NULL),
(5,	0,	'RuleMailCollector',	2,	'Auto-Reply Auto-Submitted',	'Exclude Auto-Reply emails using Auto-Submitted header',	'OR',	1,	NULL,	NULL,	1,	'500717c8-2bd6e957-53a12b5fd376c2.87642651',	0,	NULL),
(6,	0,	'RuleTicket',	1,	'Ticket location from item',	'',	'AND',	0,	NULL,	NULL,	1,	'500717c8-2bd6e957-53a12b5fd37f94.10365341',	1,	NULL),
(7,	0,	'RuleTicket',	2,	'Ticket location from user',	'',	'AND',	0,	NULL,	NULL,	1,	'500717c8-2bd6e957-53a12b5fd38869.86002585',	1,	NULL),
(8,	0,	'RuleSoftwareCategory',	1,	'Import category from inventory tool',	'',	'AND',	0,	NULL,	NULL,	1,	'500717c8-2bd6e957-53a12b5fd38869.86003425',	1,	NULL),
(9,	0,	'RuleAsset',	1,	'Domain user assignation',	'',	'AND',	1,	NULL,	NULL,	1,	'fbeb1115-7a37b143-5a3a6fc1afdc17.92779763',	3,	NULL),
(10,	0,	'RuleAsset',	2,	'Multiple users: assign to the first',	'',	'AND',	1,	NULL,	NULL,	1,	'fbeb1115-7a37b143-5a3a6fc1b03762.88595154',	3,	NULL),
(11,	0,	'RuleAsset',	3,	'One user assignation',	'',	'AND',	1,	NULL,	NULL,	1,	'fbeb1115-7a37b143-5a3a6fc1b073e1.16257440',	3,	NULL);

DROP TABLE IF EXISTS `glpi_savedsearches`;
CREATE TABLE `glpi_savedsearches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` int(11) NOT NULL DEFAULT 0 COMMENT 'see SavedSearch:: constants',
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 1,
  `entities_id` int(11) NOT NULL DEFAULT -1,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `query` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_execution_time` int(11) DEFAULT NULL,
  `do_count` tinyint(1) NOT NULL DEFAULT 2 COMMENT 'Do or do not count results on list display see SavedSearch::COUNT_* constants',
  `last_execution_date` timestamp NULL DEFAULT NULL,
  `counter` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `itemtype` (`itemtype`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `is_private` (`is_private`),
  KEY `is_recursive` (`is_recursive`),
  KEY `last_execution_time` (`last_execution_time`),
  KEY `last_execution_date` (`last_execution_date`),
  KEY `do_count` (`do_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_savedsearches_alerts`;
CREATE TABLE `glpi_savedsearches_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `savedsearches_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `operator` tinyint(1) NOT NULL,
  `value` int(11) NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`savedsearches_id`,`operator`,`value`),
  KEY `name` (`name`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_savedsearches_users`;
CREATE TABLE `glpi_savedsearches_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `savedsearches_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`users_id`,`itemtype`),
  KEY `savedsearches_id` (`savedsearches_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_slalevelactions`;
CREATE TABLE `glpi_slalevelactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slalevels_id` int(11) NOT NULL DEFAULT 0,
  `action_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slalevels_id` (`slalevels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_slalevelcriterias`;
CREATE TABLE `glpi_slalevelcriterias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slalevels_id` int(11) NOT NULL DEFAULT 0,
  `criteria` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition` int(11) NOT NULL DEFAULT 0 COMMENT 'see define.php PATTERN_* and REGEX_* constant',
  `pattern` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slalevels_id` (`slalevels_id`),
  KEY `condition` (`condition`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_slalevels`;
CREATE TABLE `glpi_slalevels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slas_id` int(11) NOT NULL DEFAULT 0,
  `execution_time` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `match` char(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'see define.php *_MATCHING constant',
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_active` (`is_active`),
  KEY `slas_id` (`slas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_slalevels_tickets`;
CREATE TABLE `glpi_slalevels_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `slalevels_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickets_id`,`slalevels_id`),
  KEY `tickets_id` (`tickets_id`),
  KEY `slalevels_id` (`slalevels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_slas`;
CREATE TABLE `glpi_slas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `number_time` int(11) NOT NULL,
  `calendars_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `definition_time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `end_of_working_day` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `slms_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `calendars_id` (`calendars_id`),
  KEY `slms_id` (`slms_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_slms`;
CREATE TABLE `glpi_slms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `calendars_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `calendars_id` (`calendars_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_softwarecategories`;
CREATE TABLE `glpi_softwarecategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `softwarecategories_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `softwarecategories_id` (`softwarecategories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwarecategories` (`id`, `name`, `comment`, `softwarecategories_id`, `completename`, `level`, `ancestors_cache`, `sons_cache`) VALUES
(1,	'FUSION',	NULL,	0,	'FUSION',	1,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_softwarelicenses`;
CREATE TABLE `glpi_softwarelicenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `softwares_id` int(11) NOT NULL DEFAULT 0,
  `softwarelicenses_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `number` int(11) NOT NULL DEFAULT 0,
  `softwarelicensetypes_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `softwareversions_id_buy` int(11) NOT NULL DEFAULT 0,
  `softwareversions_id_use` int(11) NOT NULL DEFAULT 0,
  `expire` date DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `is_helpdesk_visible` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_num` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `allow_overquota` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `expire` (`expire`),
  KEY `softwareversions_id_buy` (`softwareversions_id_buy`),
  KEY `entities_id` (`entities_id`),
  KEY `softwarelicensetypes_id` (`softwarelicensetypes_id`),
  KEY `softwareversions_id_use` (`softwareversions_id_use`),
  KEY `date_mod` (`date_mod`),
  KEY `softwares_id_expire_number` (`softwares_id`,`expire`,`number`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `users_id` (`users_id`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `groups_id` (`groups_id`),
  KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_creation` (`date_creation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `states_id` (`states_id`),
  KEY `allow_overquota` (`allow_overquota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_softwarelicensetypes`;
CREATE TABLE `glpi_softwarelicensetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `softwarelicensetypes_id` int(11) NOT NULL DEFAULT 0,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `softwarelicensetypes_id` (`softwarelicensetypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwarelicensetypes` (`id`, `name`, `comment`, `date_mod`, `date_creation`, `softwarelicensetypes_id`, `level`, `ancestors_cache`, `sons_cache`, `entities_id`, `is_recursive`, `completename`) VALUES
(1,	'OEM',	NULL,	NULL,	NULL,	0,	0,	NULL,	NULL,	0,	1,	'OEM');

DROP TABLE IF EXISTS `glpi_softwares`;
CREATE TABLE `glpi_softwares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `is_update` tinyint(1) NOT NULL DEFAULT 0,
  `softwares_id` int(11) NOT NULL DEFAULT 0,
  `manufacturers_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `ticket_tco` decimal(20,4) DEFAULT 0.0000,
  `is_helpdesk_visible` tinyint(1) NOT NULL DEFAULT 1,
  `softwarecategories_id` int(11) NOT NULL DEFAULT 0,
  `is_valid` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_update` (`is_update`),
  KEY `softwarecategories_id` (`softwarecategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `softwares_id` (`softwares_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_softwareversions`;
CREATE TABLE `glpi_softwareversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `softwares_id` int(11) NOT NULL DEFAULT 0,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `operatingsystems_id` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `softwares_id` (`softwares_id`),
  KEY `states_id` (`states_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `operatingsystems_id` (`operatingsystems_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_solutiontemplates`;
CREATE TABLE `glpi_solutiontemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `solutiontypes_id` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_recursive` (`is_recursive`),
  KEY `solutiontypes_id` (`solutiontypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_solutiontypes`;
CREATE TABLE `glpi_solutiontypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 1,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ssovariables`;
CREATE TABLE `glpi_ssovariables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_ssovariables` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1,	'HTTP_AUTH_USER',	'',	NULL,	NULL),
(2,	'REMOTE_USER',	'',	NULL,	NULL),
(3,	'PHP_AUTH_USER',	'',	NULL,	NULL),
(4,	'USERNAME',	'',	NULL,	NULL),
(5,	'REDIRECT_REMOTE_USER',	'',	NULL,	NULL),
(6,	'HTTP_REMOTE_USER',	'',	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_states`;
CREATE TABLE `glpi_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `states_id` int(11) NOT NULL DEFAULT 0,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_visible_computer` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_monitor` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_networkequipment` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_peripheral` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_phone` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_printer` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_softwareversion` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_softwarelicense` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_line` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_certificate` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_rack` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_passivedcequipment` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_enclosure` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_pdu` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_cluster` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_contract` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_appliance` tinyint(1) NOT NULL DEFAULT 1,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`states_id`,`name`),
  KEY `name` (`name`),
  KEY `is_visible_computer` (`is_visible_computer`),
  KEY `is_visible_monitor` (`is_visible_monitor`),
  KEY `is_visible_networkequipment` (`is_visible_networkequipment`),
  KEY `is_visible_peripheral` (`is_visible_peripheral`),
  KEY `is_visible_phone` (`is_visible_phone`),
  KEY `is_visible_printer` (`is_visible_printer`),
  KEY `is_visible_softwareversion` (`is_visible_softwareversion`),
  KEY `is_visible_softwarelicense` (`is_visible_softwarelicense`),
  KEY `is_visible_line` (`is_visible_line`),
  KEY `is_visible_certificate` (`is_visible_certificate`),
  KEY `is_visible_rack` (`is_visible_rack`),
  KEY `is_visible_passivedcequipment` (`is_visible_passivedcequipment`),
  KEY `is_visible_enclosure` (`is_visible_enclosure`),
  KEY `is_visible_pdu` (`is_visible_pdu`),
  KEY `is_visible_cluster` (`is_visible_cluster`),
  KEY `is_visible_contract` (`is_visible_contract`),
  KEY `is_visible_appliance` (`is_visible_appliance`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_suppliers`;
CREATE TABLE `glpi_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `suppliertypes_id` int(11) NOT NULL DEFAULT 0,
  `address` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `postcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `town` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phonenumber` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `fax` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `suppliertypes_id` (`suppliertypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_suppliers_tickets`;
CREATE TABLE `glpi_suppliers_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `suppliers_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `use_notification` tinyint(1) NOT NULL DEFAULT 1,
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickets_id`,`type`,`suppliers_id`),
  KEY `group` (`suppliers_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_suppliertypes`;
CREATE TABLE `glpi_suppliertypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_taskcategories`;
CREATE TABLE `glpi_taskcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `taskcategories_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `completename` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `ancestors_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `sons_cache` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_helpdeskvisible` tinyint(1) NOT NULL DEFAULT 1,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `knowbaseitemcategories_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `taskcategories_id` (`taskcategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_active` (`is_active`),
  KEY `is_helpdeskvisible` (`is_helpdeskvisible`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tasktemplates`;
CREATE TABLE `glpi_tasktemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `taskcategories_id` int(11) NOT NULL DEFAULT 0,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `is_recursive` (`is_recursive`),
  KEY `taskcategories_id` (`taskcategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `is_private` (`is_private`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ticketcosts`;
CREATE TABLE `glpi_ticketcosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `cost_time` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cost_fixed` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cost_material` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `budgets_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `tickets_id` (`tickets_id`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `entities_id` (`entities_id`),
  KEY `budgets_id` (`budgets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ticketrecurrents`;
CREATE TABLE `glpi_ticketrecurrents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `tickettemplates_id` int(11) NOT NULL DEFAULT 0,
  `begin_date` timestamp NULL DEFAULT NULL,
  `periodicity` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_before` int(11) NOT NULL DEFAULT 0,
  `next_creation_date` timestamp NULL DEFAULT NULL,
  `calendars_id` int(11) NOT NULL DEFAULT 0,
  `end_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_active` (`is_active`),
  KEY `tickettemplates_id` (`tickettemplates_id`),
  KEY `next_creation_date` (`next_creation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tickets`;
CREATE TABLE `glpi_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `closedate` timestamp NULL DEFAULT NULL,
  `solvedate` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `users_id_lastupdater` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `users_id_recipient` int(11) NOT NULL DEFAULT 0,
  `requesttypes_id` int(11) NOT NULL DEFAULT 0,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `urgency` int(11) NOT NULL DEFAULT 1,
  `impact` int(11) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 1,
  `itilcategories_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `global_validation` int(11) NOT NULL DEFAULT 1,
  `slas_id_ttr` int(11) NOT NULL DEFAULT 0,
  `slas_id_tto` int(11) NOT NULL DEFAULT 0,
  `slalevels_id_ttr` int(11) NOT NULL DEFAULT 0,
  `time_to_resolve` timestamp NULL DEFAULT NULL,
  `time_to_own` timestamp NULL DEFAULT NULL,
  `begin_waiting_date` timestamp NULL DEFAULT NULL,
  `sla_waiting_duration` int(11) NOT NULL DEFAULT 0,
  `ola_waiting_duration` int(11) NOT NULL DEFAULT 0,
  `olas_id_tto` int(11) NOT NULL DEFAULT 0,
  `olas_id_ttr` int(11) NOT NULL DEFAULT 0,
  `olalevels_id_ttr` int(11) NOT NULL DEFAULT 0,
  `ola_ttr_begin_date` timestamp NULL DEFAULT NULL,
  `internal_time_to_resolve` timestamp NULL DEFAULT NULL,
  `internal_time_to_own` timestamp NULL DEFAULT NULL,
  `waiting_duration` int(11) NOT NULL DEFAULT 0,
  `close_delay_stat` int(11) NOT NULL DEFAULT 0,
  `solve_delay_stat` int(11) NOT NULL DEFAULT 0,
  `takeintoaccount_delay_stat` int(11) NOT NULL DEFAULT 0,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `validation_percent` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `request_type` (`requesttypes_id`),
  KEY `date_mod` (`date_mod`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id_recipient` (`users_id_recipient`),
  KEY `solvedate` (`solvedate`),
  KEY `urgency` (`urgency`),
  KEY `impact` (`impact`),
  KEY `global_validation` (`global_validation`),
  KEY `slas_id_tto` (`slas_id_tto`),
  KEY `slas_id_ttr` (`slas_id_ttr`),
  KEY `time_to_resolve` (`time_to_resolve`),
  KEY `time_to_own` (`time_to_own`),
  KEY `olas_id_tto` (`olas_id_tto`),
  KEY `olas_id_ttr` (`olas_id_ttr`),
  KEY `slalevels_id_ttr` (`slalevels_id_ttr`),
  KEY `internal_time_to_resolve` (`internal_time_to_resolve`),
  KEY `internal_time_to_own` (`internal_time_to_own`),
  KEY `users_id_lastupdater` (`users_id_lastupdater`),
  KEY `type` (`type`),
  KEY `itilcategories_id` (`itilcategories_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `name` (`name`),
  KEY `locations_id` (`locations_id`),
  KEY `date_creation` (`date_creation`),
  KEY `ola_waiting_duration` (`ola_waiting_duration`),
  KEY `olalevels_id_ttr` (`olalevels_id_ttr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_ticketsatisfactions`;
CREATE TABLE `glpi_ticketsatisfactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `date_begin` timestamp NULL DEFAULT NULL,
  `date_answered` timestamp NULL DEFAULT NULL,
  `satisfaction` int(11) DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_id` (`tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tickets_tickets`;
CREATE TABLE `glpi_tickets_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id_1` int(11) NOT NULL DEFAULT 0,
  `tickets_id_2` int(11) NOT NULL DEFAULT 0,
  `link` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickets_id_1`,`tickets_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tickets_users`;
CREATE TABLE `glpi_tickets_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `use_notification` tinyint(1) NOT NULL DEFAULT 1,
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickets_id`,`type`,`users_id`,`alternative_email`),
  KEY `user` (`users_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tickettasks`;
CREATE TABLE `glpi_tickettasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `taskcategories_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `users_id_editor` int(11) NOT NULL DEFAULT 0,
  `content` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `actiontime` int(11) NOT NULL DEFAULT 0,
  `begin` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 1,
  `users_id_tech` int(11) NOT NULL DEFAULT 0,
  `groups_id_tech` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `tasktemplates_id` int(11) NOT NULL DEFAULT 0,
  `timeline_position` tinyint(1) NOT NULL DEFAULT 0,
  `sourceitems_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `date` (`date`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `users_id` (`users_id`),
  KEY `users_id_editor` (`users_id_editor`),
  KEY `tickets_id` (`tickets_id`),
  KEY `is_private` (`is_private`),
  KEY `taskcategories_id` (`taskcategories_id`),
  KEY `state` (`state`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `tasktemplates_id` (`tasktemplates_id`),
  KEY `sourceitems_id` (`sourceitems_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tickettemplatehiddenfields`;
CREATE TABLE `glpi_tickettemplatehiddenfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickettemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickettemplates_id`,`num`),
  KEY `tickettemplates_id` (`tickettemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tickettemplatemandatoryfields`;
CREATE TABLE `glpi_tickettemplatemandatoryfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickettemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`tickettemplates_id`,`num`),
  KEY `tickettemplates_id` (`tickettemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_tickettemplatemandatoryfields` (`id`, `tickettemplates_id`, `num`) VALUES
(1,	1,	21);

DROP TABLE IF EXISTS `glpi_tickettemplatepredefinedfields`;
CREATE TABLE `glpi_tickettemplatepredefinedfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickettemplates_id` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 0,
  `value` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tickettemplates_id` (`tickettemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_tickettemplates`;
CREATE TABLE `glpi_tickettemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_tickettemplates` (`id`, `name`, `entities_id`, `is_recursive`, `comment`) VALUES
(1,	'Default',	0,	1,	NULL);

DROP TABLE IF EXISTS `glpi_ticketvalidations`;
CREATE TABLE `glpi_ticketvalidations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `tickets_id` int(11) NOT NULL DEFAULT 0,
  `users_id_validate` int(11) NOT NULL DEFAULT 0,
  `comment_submission` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment_validation` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 2,
  `submission_date` timestamp NULL DEFAULT NULL,
  `validation_date` timestamp NULL DEFAULT NULL,
  `timeline_position` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_validate` (`users_id_validate`),
  KEY `tickets_id` (`tickets_id`),
  KEY `submission_date` (`submission_date`),
  KEY `validation_date` (`validation_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_transfers`;
CREATE TABLE `glpi_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keep_ticket` int(11) NOT NULL DEFAULT 0,
  `keep_networklink` int(11) NOT NULL DEFAULT 0,
  `keep_reservation` int(11) NOT NULL DEFAULT 0,
  `keep_history` int(11) NOT NULL DEFAULT 0,
  `keep_device` int(11) NOT NULL DEFAULT 0,
  `keep_infocom` int(11) NOT NULL DEFAULT 0,
  `keep_dc_monitor` int(11) NOT NULL DEFAULT 0,
  `clean_dc_monitor` int(11) NOT NULL DEFAULT 0,
  `keep_dc_phone` int(11) NOT NULL DEFAULT 0,
  `clean_dc_phone` int(11) NOT NULL DEFAULT 0,
  `keep_dc_peripheral` int(11) NOT NULL DEFAULT 0,
  `clean_dc_peripheral` int(11) NOT NULL DEFAULT 0,
  `keep_dc_printer` int(11) NOT NULL DEFAULT 0,
  `clean_dc_printer` int(11) NOT NULL DEFAULT 0,
  `keep_supplier` int(11) NOT NULL DEFAULT 0,
  `clean_supplier` int(11) NOT NULL DEFAULT 0,
  `keep_contact` int(11) NOT NULL DEFAULT 0,
  `clean_contact` int(11) NOT NULL DEFAULT 0,
  `keep_contract` int(11) NOT NULL DEFAULT 0,
  `clean_contract` int(11) NOT NULL DEFAULT 0,
  `keep_software` int(11) NOT NULL DEFAULT 0,
  `clean_software` int(11) NOT NULL DEFAULT 0,
  `keep_document` int(11) NOT NULL DEFAULT 0,
  `clean_document` int(11) NOT NULL DEFAULT 0,
  `keep_cartridgeitem` int(11) NOT NULL DEFAULT 0,
  `clean_cartridgeitem` int(11) NOT NULL DEFAULT 0,
  `keep_cartridge` int(11) NOT NULL DEFAULT 0,
  `keep_consumable` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `keep_disk` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_transfers` (`id`, `name`, `keep_ticket`, `keep_networklink`, `keep_reservation`, `keep_history`, `keep_device`, `keep_infocom`, `keep_dc_monitor`, `clean_dc_monitor`, `keep_dc_phone`, `clean_dc_phone`, `keep_dc_peripheral`, `clean_dc_peripheral`, `keep_dc_printer`, `clean_dc_printer`, `keep_supplier`, `clean_supplier`, `keep_contact`, `clean_contact`, `keep_contract`, `clean_contract`, `keep_software`, `clean_software`, `keep_document`, `clean_document`, `keep_cartridgeitem`, `clean_cartridgeitem`, `keep_cartridge`, `keep_consumable`, `date_mod`, `comment`, `keep_disk`) VALUES
(1,	'complete',	2,	2,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	1,	NULL,	NULL,	1);

DROP TABLE IF EXISTS `glpi_usercategories`;
CREATE TABLE `glpi_usercategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_useremails`;
CREATE TABLE `glpi_useremails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`users_id`,`email`),
  KEY `email` (`email`),
  KEY `is_default` (`is_default`),
  KEY `is_dynamic` (`is_dynamic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_users`;
CREATE TABLE `glpi_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_last_update` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `realname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locations_id` int(11) NOT NULL DEFAULT 0,
  `language` char(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'see define.php CFG_GLPI[language] array',
  `use_mode` int(11) NOT NULL DEFAULT 0,
  `list_limit` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `auths_id` int(11) NOT NULL DEFAULT 0,
  `authtype` int(11) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_sync` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `profiles_id` int(11) NOT NULL DEFAULT 0,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `usertitles_id` int(11) NOT NULL DEFAULT 0,
  `usercategories_id` int(11) NOT NULL DEFAULT 0,
  `date_format` int(11) DEFAULT NULL,
  `number_format` int(11) DEFAULT NULL,
  `names_format` int(11) DEFAULT NULL,
  `csv_delimiter` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_ids_visible` tinyint(1) DEFAULT NULL,
  `use_flat_dropdowntree` tinyint(1) DEFAULT NULL,
  `show_jobs_at_login` tinyint(1) DEFAULT NULL,
  `priority_1` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority_2` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority_3` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority_4` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority_5` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority_6` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `followup_private` tinyint(1) DEFAULT NULL,
  `task_private` tinyint(1) DEFAULT NULL,
  `default_requesttypes_id` int(11) DEFAULT NULL,
  `password_forget_token` char(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_forget_token_date` timestamp NULL DEFAULT NULL,
  `user_dn` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `registration_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `show_count_on_tabs` tinyint(1) DEFAULT NULL,
  `refresh_views` int(11) DEFAULT NULL,
  `set_default_tech` tinyint(1) DEFAULT NULL,
  `personal_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `personal_token_date` timestamp NULL DEFAULT NULL,
  `api_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_token_date` timestamp NULL DEFAULT NULL,
  `cookie_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cookie_token_date` timestamp NULL DEFAULT NULL,
  `display_count_on_home` int(11) DEFAULT NULL,
  `notification_to_myself` tinyint(1) DEFAULT NULL,
  `duedateok_color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duedatewarning_color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duedatecritical_color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duedatewarning_less` int(11) DEFAULT NULL,
  `duedatecritical_less` int(11) DEFAULT NULL,
  `duedatewarning_unit` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duedatecritical_unit` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `display_options` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_deleted_ldap` tinyint(1) NOT NULL DEFAULT 0,
  `pdffont` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `begin_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `keep_devices_when_purging_item` tinyint(1) DEFAULT NULL,
  `privatebookmarkorder` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `backcreated` tinyint(1) DEFAULT NULL,
  `task_state` int(11) DEFAULT NULL,
  `layout` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `palette` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `set_default_requester` tinyint(1) DEFAULT NULL,
  `lock_autolock_mode` tinyint(1) DEFAULT NULL,
  `lock_directunlock_notification` tinyint(1) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `highcontrast_css` tinyint(1) DEFAULT 0,
  `plannings` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `sync_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `groups_id` int(11) NOT NULL DEFAULT 0,
  `users_id_supervisor` int(11) NOT NULL DEFAULT 0,
  `timezone` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `default_dashboard_central` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `default_dashboard_assets` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `default_dashboard_helpdesk` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `default_dashboard_mini_ticket` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicityloginauth` (`name`,`authtype`,`auths_id`),
  KEY `firstname` (`firstname`),
  KEY `realname` (`realname`),
  KEY `entities_id` (`entities_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `locations_id` (`locations_id`),
  KEY `usertitles_id` (`usertitles_id`),
  KEY `usercategories_id` (`usercategories_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`),
  KEY `authitem` (`authtype`,`auths_id`),
  KEY `is_deleted_ldap` (`is_deleted_ldap`),
  KEY `date_creation` (`date_creation`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`end_date`),
  KEY `sync_field` (`sync_field`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id_supervisor` (`users_id_supervisor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_users` (`id`, `name`, `password`, `password_last_update`, `phone`, `phone2`, `mobile`, `realname`, `firstname`, `locations_id`, `language`, `use_mode`, `list_limit`, `is_active`, `comment`, `auths_id`, `authtype`, `last_login`, `date_mod`, `date_sync`, `is_deleted`, `profiles_id`, `entities_id`, `usertitles_id`, `usercategories_id`, `date_format`, `number_format`, `names_format`, `csv_delimiter`, `is_ids_visible`, `use_flat_dropdowntree`, `show_jobs_at_login`, `priority_1`, `priority_2`, `priority_3`, `priority_4`, `priority_5`, `priority_6`, `followup_private`, `task_private`, `default_requesttypes_id`, `password_forget_token`, `password_forget_token_date`, `user_dn`, `registration_number`, `show_count_on_tabs`, `refresh_views`, `set_default_tech`, `personal_token`, `personal_token_date`, `api_token`, `api_token_date`, `cookie_token`, `cookie_token_date`, `display_count_on_home`, `notification_to_myself`, `duedateok_color`, `duedatewarning_color`, `duedatecritical_color`, `duedatewarning_less`, `duedatecritical_less`, `duedatewarning_unit`, `duedatecritical_unit`, `display_options`, `is_deleted_ldap`, `pdffont`, `picture`, `begin_date`, `end_date`, `keep_devices_when_purging_item`, `privatebookmarkorder`, `backcreated`, `task_state`, `layout`, `palette`, `set_default_requester`, `lock_autolock_mode`, `lock_directunlock_notification`, `date_creation`, `highcontrast_css`, `plannings`, `sync_field`, `groups_id`, `users_id_supervisor`, `timezone`, `default_dashboard_central`, `default_dashboard_assets`, `default_dashboard_helpdesk`, `default_dashboard_mini_ticket`) VALUES
(2,	'glpi',	'$2y$10$rXXzbc2ShaiCldwkw4AZL.n.9QSH7c0c9XJAyyjrbL9BwmWditAYm',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	0,	20,	1,	NULL,	0,	1,	NULL,	NULL,	NULL,	0,	0,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL),
(3,	'post-only',	'$2y$10$dTMar1F3ef5X/H1IjX9gYOjQWBR1K4bERGf4/oTPxFtJE/c3vXILm',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	'en_GB',	0,	20,	1,	NULL,	0,	1,	NULL,	NULL,	NULL,	0,	0,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL),
(4,	'tech',	'$2y$10$.xEgErizkp6Az0z.DHyoeOoenuh0RcsX4JapBk2JMD6VI17KtB1lO',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	'en_GB',	0,	20,	1,	NULL,	0,	1,	NULL,	NULL,	NULL,	0,	0,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL),
(5,	'normal',	'$2y$10$Z6doq4zVHkSPZFbPeXTCluN1Q/r0ryZ3ZsSJncJqkN3.8cRiN0NV.',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	'en_GB',	0,	20,	1,	NULL,	0,	1,	NULL,	NULL,	NULL,	0,	0,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `glpi_usertitles`;
CREATE TABLE `glpi_usertitles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_virtualmachinestates`;
CREATE TABLE `glpi_virtualmachinestates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_virtualmachinesystems`;
CREATE TABLE `glpi_virtualmachinesystems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_virtualmachinetypes`;
CREATE TABLE `glpi_virtualmachinetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_vlans`;
CREATE TABLE `glpi_vlans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `tag` int(11) NOT NULL DEFAULT 0,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `tag` (`tag`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_vobjects`;
CREATE TABLE `glpi_vobjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT 0,
  `data` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_wifinetworks`;
CREATE TABLE `glpi_wifinetworks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `essid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'ad-hoc, access_point',
  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `essid` (`essid`),
  KEY `name` (`name`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2021-01-19 11:18:30
