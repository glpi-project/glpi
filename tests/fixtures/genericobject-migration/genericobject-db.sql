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

CREATE TABLE `glpi_plugin_genericobject_typefamilies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_genericobject_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `itemtype` varchar(255) DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `use_global_search` tinyint NOT NULL DEFAULT '0',
  `use_unicity` tinyint NOT NULL DEFAULT '0',
  `use_history` tinyint NOT NULL DEFAULT '0',
  `use_infocoms` tinyint NOT NULL DEFAULT '0',
  `use_contracts` tinyint NOT NULL DEFAULT '0',
  `use_documents` tinyint NOT NULL DEFAULT '0',
  `use_tickets` tinyint NOT NULL DEFAULT '0',
  `use_links` tinyint NOT NULL DEFAULT '0',
  `use_loans` tinyint NOT NULL DEFAULT '0',
  `use_network_ports` tinyint NOT NULL DEFAULT '0',
  `use_direct_connections` tinyint NOT NULL DEFAULT '0',
  `use_plugin_datainjection` tinyint NOT NULL DEFAULT '0',
  `use_plugin_pdf` tinyint NOT NULL DEFAULT '0',
  `use_plugin_order` tinyint NOT NULL DEFAULT '0',
  `use_plugin_uninstall` tinyint NOT NULL DEFAULT '0',
  `use_plugin_geninventorynumber` tinyint NOT NULL DEFAULT '0',
  `use_menu_entry` tinyint NOT NULL DEFAULT '0',
  `use_projects` tinyint NOT NULL DEFAULT '0',
  `linked_itemtypes` text,
  `plugin_genericobject_typefamilies_id` int unsigned NOT NULL DEFAULT '0',
  `use_itemdevices` tinyint NOT NULL DEFAULT '0',
  `impact_icon` varchar(255) DEFAULT NULL,
  `use_notepad` tinyint NOT NULL DEFAULT '0',
  `use_plugin_simcard` tinyint NOT NULL DEFAULT '0',
  `use_plugin_treeview` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_genericobject_types` (`id`, `entities_id`, `itemtype`, `is_active`, `name`, `comment`, `date_mod`, `date_creation`, `use_global_search`, `use_unicity`, `use_history`, `use_infocoms`, `use_contracts`, `use_documents`, `use_tickets`, `use_links`, `use_loans`, `use_network_ports`, `use_direct_connections`, `use_plugin_datainjection`, `use_plugin_pdf`, `use_plugin_order`, `use_plugin_uninstall`, `use_plugin_geninventorynumber`, `use_menu_entry`, `use_projects`, `linked_itemtypes`, `plugin_genericobject_typefamilies_id`, `use_itemdevices`, `impact_icon`, `use_notepad`, `use_plugin_simcard`, `use_plugin_treeview`) VALUES
(1, 0, 'PluginGenericobjectSmartphone', 1, 'smartphone', 'Main object with all the fields and capacities.', '2025-03-05 16:30:17', '2025-03-05 16:28:56', 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, NULL, 0, 1, '62f9ae776176b4.07833080smartphone.png', 1, 0, 0),
(2, 0, 'PluginGenericobjectTablet', 1, 'tablet', 'Main object with only than the mandatory fields.', '2025-03-06 09:58:55', '2025-03-05 16:32:43', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, NULL, 0, 0, 0),
(3, 0, 'PluginGenericobjectInactive', 0, 'inactive', 'Inactive main object.', '2025-03-06 14:32:48', '2025-03-06 14:30:12', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, NULL, 0, 0, 0);

CREATE TABLE `glpi_plugin_genericobject_bars` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_bars` (`id`, `name`, `comment`, `date_mod`, `date_creation`, `entities_id`, `is_recursive`) VALUES
(1, 'Bar 1', '', '2025-03-06 10:06:47', '2025-03-06 10:06:47', 0, 0),
(2, 'Bar 2', '', '2025-03-06 10:06:49', '2025-03-06 10:06:49', 0, 1),
(3, 'Bar 3', 'A comment about bar 3', '2025-03-06 10:06:52', '2025-03-06 10:06:52', 0, 1),
(4, 'Bar 4', '', '2025-03-06 10:06:54', '2025-03-06 10:06:54', 0, 1),
(5, 'Bar 5', '', '2025-03-06 10:06:56', '2025-03-06 10:06:56', 0, 0),
(6, 'Bar 6', '', '2025-03-06 10:06:58', '2025-03-06 10:06:58', 0, 0),
(7, 'Bar 7', '', '2025-03-06 10:07:00', '2025-03-06 10:07:00', 0, 0),
(8, 'Bar 8', '', '2025-03-06 10:07:03', '2025-03-06 10:07:03', 0, 0),
(9, 'Bar 9', '', '2025-03-06 10:07:05', '2025-03-06 10:07:05', 0, 0),
(10, 'Bar 10', '', '2025-03-06 10:07:08', '2025-03-06 10:07:08', 0, 0);

CREATE TABLE `glpi_plugin_genericobject_foos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_foos` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 'Foo 1', '', '2025-03-06 10:26:47', '2025-03-06 10:26:47'),
(2, 'Foo 2', '', '2025-03-06 10:26:49', '2025-03-06 10:26:49'),
(3, 'Foo 3', '', '2025-03-06 10:26:52', '2025-03-06 10:26:52'),
(4, 'Foo 4', '', '2025-03-06 10:26:54', '2025-03-06 10:26:54'),
(5, 'Foo 5', '', '2025-03-06 10:26:56', '2025-03-06 10:26:56'),
(6, 'Foo 6', '', '2025-03-06 10:26:58', '2025-03-06 10:26:58'),
(7, 'Foo 7', '', '2025-03-06 10:27:00', '2025-03-06 10:27:00'),
(8, 'Foo 8', '', '2025-03-06 10:27:03', '2025-03-06 10:27:03'),
(9, 'Foo 9', '', '2025-03-06 10:27:05', '2025-03-06 10:27:05'),
(10, 'Foo 10', '', '2025-03-06 10:27:08', '2025-03-06 10:27:08');

CREATE TABLE `glpi_plugin_genericobject_inactives` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_genericobject_smartphones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `is_template` tinyint NOT NULL DEFAULT '0',
  `template_name` varchar(255) NOT NULL DEFAULT '',
  `is_deleted` tinyint NOT NULL DEFAULT '0',
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `serial` varchar(255) NOT NULL DEFAULT '',
  `otherserial` varchar(255) NOT NULL DEFAULT '',
  `locations_id` int unsigned NOT NULL DEFAULT '0',
  `states_id` int unsigned NOT NULL DEFAULT '0',
  `users_id` int unsigned NOT NULL DEFAULT '0',
  `groups_id` int unsigned NOT NULL DEFAULT '0',
  `manufacturers_id` int unsigned NOT NULL DEFAULT '0',
  `users_id_tech` int unsigned NOT NULL DEFAULT '0',
  `comment` text,
  `is_helpdesk_visible` tinyint NOT NULL DEFAULT '0',
  `notepad` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `computers_id_host` int unsigned NOT NULL DEFAULT '0',
  `config_str` text,
  `contact` varchar(255) NOT NULL DEFAULT '',
  `contact_num` varchar(255) NOT NULL DEFAULT '',
  `creationdate` date DEFAULT NULL,
  `count` int NOT NULL DEFAULT '0',
  `domains_id` int unsigned NOT NULL DEFAULT '0',
  `expirationdate` date DEFAULT NULL,
  `groups_id_tech` int unsigned NOT NULL DEFAULT '0',
  `is_global` tinyint NOT NULL DEFAULT '0',
  `other` varchar(255) NOT NULL DEFAULT '',
  `plugin_genericobject_smartphonecategories_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_genericobject_smartphonemodels_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_genericobject_smartphonetypes_id` int unsigned NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `plugin_genericobject_tablets_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_genericobject_bars_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_genericobject_foos_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_smartphones` (`id`, `is_template`, `template_name`, `is_deleted`, `entities_id`, `is_recursive`, `name`, `serial`, `otherserial`, `locations_id`, `states_id`, `users_id`, `groups_id`, `manufacturers_id`, `users_id_tech`, `comment`, `is_helpdesk_visible`, `notepad`, `date_mod`, `date_creation`, `computers_id_host`, `config_str`, `contact`, `contact_num`, `creationdate`, `count`, `domains_id`, `expirationdate`, `groups_id_tech`, `is_global`, `other`, `plugin_genericobject_foos_id`, `plugin_genericobject_smartphonecategories_id`, `plugin_genericobject_smartphonemodels_id`, `plugin_genericobject_smartphonetypes_id`, `url`, `plugin_genericobject_tablets_id`) VALUES
(1, 0, '', 0, 0, 0, 'Smartphone 1', 'SER0123', '', 1, 1, 3, 2, 3, 4, '', 1, NULL, '2025-03-06 10:08:51', '2025-03-06 10:08:51', 1, '# is_foo=0\nis_foo=1\nbar="adbizq"', '', '', '2025-02-01', 19, 4, '2025-12-31', 0, 0, 'some random value', 8, 5, 2, 5, 'https://example.org/?id=9783', 1),
(2, 0, '', 0, 0, 1, 'Smartphone 2', 'SER0198', '', 2, 2, 5, 0, 2, 0, '', 1, NULL, '2025-03-06 10:11:46', '2025-03-06 10:11:46', 2, '# no config', '', '', NULL, 0, 0, NULL, 1, 0, '', 0, 4, 2, 0, '', 0),
(3, 0, '', 0, 0, 0, 'Smartphone 3', '', 'INV123456', 0, 0, 0, 0, 0, 4, 'Some comments...', 1, NULL, '2025-03-06 10:12:59', '2025-03-06 10:12:59', 0, '', '', '', NULL, 0, 4, NULL, 0, 1, '', 10, 0, 0, 4, '', 3);

CREATE TABLE `glpi_plugin_genericobject_smartphonecategories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_smartphonecategories` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 'Cat 1', '', '2025-03-06 10:07:16', '2025-03-06 10:07:16'),
(2, 'Cat 2', '', '2025-03-06 10:07:18', '2025-03-06 10:07:18'),
(3, 'Cat 3', '', '2025-03-06 10:07:20', '2025-03-06 10:07:20'),
(4, 'Cat 4', '', '2025-03-06 10:07:22', '2025-03-06 10:07:22'),
(5, 'Cat 5', '', '2025-03-06 10:07:30', '2025-03-06 10:07:24'),
(6, 'Cat 6', '', '2025-03-06 10:07:34', '2025-03-06 10:07:34'),
(7, 'Cat 7', '', '2025-03-06 10:07:36', '2025-03-06 10:07:36'),
(8, 'Cat 8', '', '2025-03-06 10:07:38', '2025-03-06 10:07:38');

CREATE TABLE `glpi_plugin_genericobject_smartphonemodels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_smartphonemodels` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 'Model 1', '', '2025-03-06 10:07:47', '2025-03-06 10:07:47'),
(2, 'Model 2', '', '2025-03-06 10:07:50', '2025-03-06 10:07:50'),
(3, 'Model 3', '', '2025-03-06 10:07:52', '2025-03-06 10:07:52'),
(4, 'Model 4', '', '2025-03-06 10:07:54', '2025-03-06 10:07:54'),
(5, 'Model 5', '', '2025-03-06 10:07:57', '2025-03-06 10:07:57');

CREATE TABLE `glpi_plugin_genericobject_smartphonetypes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_smartphonetypes` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 'Type 1', '', '2025-03-06 10:08:05', '2025-03-06 10:08:05'),
(2, 'Type 2', '', '2025-03-06 10:08:07', '2025-03-06 10:08:07'),
(3, 'Type 3', '', '2025-03-06 10:08:09', '2025-03-06 10:08:09'),
(4, 'Type 4', '', '2025-03-06 10:08:11', '2025-03-06 10:08:11'),
(5, 'Type 5', '', '2025-03-06 10:08:18', '2025-03-06 10:08:13');

CREATE TABLE `glpi_plugin_genericobject_tablets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `comment` text,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_tablets` (`id`, `entities_id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 0, 'Tablet 1', '', '2025-03-06 10:01:07', '2025-03-06 09:59:28'),
(2, 0, 'Tablet 2', '', '2025-03-06 10:09:15', '2025-03-06 10:09:15'),
(3, 0, 'Tablet 3', '', '2025-03-06 10:09:19', '2025-03-06 10:09:19'),
(4, 0, 'Tablet 4', '', '2025-03-06 10:09:21', '2025-03-06 10:09:21');
