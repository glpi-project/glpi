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
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_genericobject_types` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `entities_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `itemtype` VARCHAR(255) NULL,
  `is_active` TINYINT NOT NULL DEFAULT 0 ,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NULL,
  `use_global_search` TINYINT NOT NULL DEFAULT 0 ,
  `use_unicity` TINYINT NOT NULL DEFAULT 0 ,
  `use_history` TINYINT NOT NULL DEFAULT 0 ,
  `use_infocoms` TINYINT NOT NULL DEFAULT 0 ,
  `use_contracts` TINYINT NOT NULL DEFAULT 0 ,
  `use_documents` TINYINT NOT NULL DEFAULT 0 ,
  `use_tickets` TINYINT NOT NULL DEFAULT 0 ,
  `use_links` TINYINT NOT NULL DEFAULT 0 ,
  `use_loans` TINYINT NOT NULL DEFAULT 0 ,
  `use_network_ports` TINYINT NOT NULL DEFAULT 0 ,
  `use_direct_connections` TINYINT NOT NULL DEFAULT 0 ,
  `use_plugin_datainjection` TINYINT NOT NULL DEFAULT 0 ,
  `use_plugin_pdf` TINYINT NOT NULL DEFAULT 0 ,
  `use_plugin_order` TINYINT NOT NULL DEFAULT 0 ,
  `use_plugin_uninstall` TINYINT NOT NULL DEFAULT 0 ,
  `use_plugin_geninventorynumber` TINYINT NOT NULL DEFAULT 0 ,
  `use_menu_entry` TINYINT NOT NULL DEFAULT 0 ,
  `use_projects` TINYINT NOT NULL DEFAULT 0 ,
  `linked_itemtypes` TEXT NULL,
  `plugin_genericobject_typefamilies_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `use_itemdevices` TINYINT NOT NULL DEFAULT 0 ,
  `impact_icon` VARCHAR(255) NULL,
  `use_notepad` TINYINT NOT NULL DEFAULT 0 ,
  `use_plugin_simcard` TINYINT NOT NULL DEFAULT 0 ,
  `use_plugin_treeview` TINYINT NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_genericobject_types` (`id`, `entities_id`, `itemtype`, `is_active`, `name`, `comment`, `date_mod`, `date_creation`, `use_global_search`, `use_unicity`, `use_history`, `use_infocoms`, `use_contracts`, `use_documents`, `use_tickets`, `use_links`, `use_loans`, `use_network_ports`, `use_direct_connections`, `use_plugin_datainjection`, `use_plugin_pdf`, `use_plugin_order`, `use_plugin_uninstall`, `use_plugin_geninventorynumber`, `use_menu_entry`, `use_projects`, `linked_itemtypes`, `plugin_genericobject_typefamilies_id`, `use_itemdevices`, `impact_icon`, `use_notepad`, `use_plugin_simcard`, `use_plugin_treeview`) VALUES
(1, 0, 'PluginGenericobjectSmartphone', 1, 'smartphone', 'Main object with all the fields and capacities.', '2025-03-05 16:30:17', '2025-03-05 16:28:56', 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, NULL, 0, 1, NULL, 1, 0, 0),
(2, 0, 'PluginGenericobjectTablet', 1, 'tablet', 'Main object with only than the mandatory fields.', '2025-03-06 09:58:55', '2025-03-05 16:32:43', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, NULL, 0, 0, 0),
(3, 0, 'PluginGenericobjectInactive', 0, 'inactive', 'Inactive main object.', '2025-03-06 14:32:48', '2025-03-06 14:30:12', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, NULL, 0, 0, 0);

CREATE TABLE `glpi_plugin_genericobject_bars` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
  `entities_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `is_recursive` TINYINT NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_bars` (`id`, `name`, `comment`, `date_mod`, `date_creation`, `entities_id`, `is_recursive`) VALUES
(1, 'Bar 1', '', '2025-03-06 10:06:47', '2025-03-06 10:06:47', 0, 0),
(2, 'Bar 2', '', '2025-03-06 10:06:49', '2025-03-06 10:06:49', 1, 1),
(3, 'Bar 3', 'A comment about bar 3', '2025-03-06 10:07:13', '2025-03-06 10:06:52', 0, 1),
(4, 'Bar 4', '', '2025-03-06 10:06:54', '2025-03-06 10:06:54', 0, 1),
(5, 'Bar 5', '', '2025-03-06 10:06:56', '2025-03-06 10:06:56', 0, 0),
(6, 'Bar 6', '', '2025-03-06 10:06:58', '2025-03-06 10:06:58', 0, 0),
(7, 'Bar 7', '', '2025-03-06 10:07:00', '2025-03-06 10:07:00', 0, 0),
(8, 'Bar 8', '', '2025-03-06 10:07:03', '2025-03-06 10:07:03', 0, 0),
(9, 'Bar 9', '', '2025-03-06 10:07:05', '2025-03-06 10:07:05', 0, 0),
(10, 'Bar 10', '', '2025-03-06 10:07:08', '2025-03-06 10:07:08', 0, 0);

CREATE TABLE `glpi_plugin_genericobject_foos` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
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

CREATE TABLE `glpi_plugin_genericobject_uaus` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_uaus` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 'Uau 1', '', '2025-01-13 08:47:23', '2025-01-13 08:47:23'),
(2, 'Uau 2', '', '2025-02-24 17:43:01', '2025-02-26 14:12:17');

CREATE TABLE `glpi_plugin_genericobject_items_states` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_items_states` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 'State 1', '', '2025-03-06 10:26:47', '2025-03-06 10:26:47'),
(2, 'State 2', '', '2025-03-06 10:26:49', '2025-03-06 10:26:49'),
(3, 'State 3', '', '2025-03-06 10:26:52', '2025-03-06 10:26:52');

CREATE TABLE `glpi_plugin_genericobject_test_abcs` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_test_abcs` (`id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 'Test 1', '', '2025-03-06 10:26:47', '2025-03-06 10:26:47'),
(2, 'Test 2', '', '2025-03-06 10:26:49', '2025-03-06 10:26:49'),
(3, 'Test 3', '', '2025-03-06 10:26:52', '2025-03-06 10:26:52');

CREATE TABLE `glpi_plugin_genericobject_inactives` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `entities_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `name` VARCHAR(255) NOT NULL DEFAULT '' ,
  `comment` TEXT NULL,
  `notepad` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_genericobject_smartphones` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `is_template` TINYINT NOT NULL DEFAULT 0 ,
  `template_name` VARCHAR(255) NOT NULL DEFAULT '' ,
  `is_deleted` TINYINT NOT NULL DEFAULT 0 ,
  `entities_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `is_recursive` TINYINT NOT NULL DEFAULT 0 ,
  `name` VARCHAR(255) NOT NULL DEFAULT '' ,
  `serial` VARCHAR(255) NOT NULL DEFAULT '' ,
  `otherserial` VARCHAR(255) NOT NULL DEFAULT '' ,
  `locations_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `states_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `users_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `groups_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `manufacturers_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `users_id_tech` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `comment` TEXT NULL,
  `is_helpdesk_visible` TINYINT NOT NULL DEFAULT 0 ,
  `notepad` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NULL,
  `computers_id_host` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `config_str` TEXT NULL,
  `contact` VARCHAR(255) NOT NULL DEFAULT '' ,
  `contact_num` VARCHAR(255) NOT NULL DEFAULT '' ,
  `creationdate` DATE NULL,
  `count` INT NOT NULL DEFAULT 0 ,
  `domains_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `expirationdate` DATE NULL,
  `groups_id_tech` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `is_global` TINYINT NOT NULL DEFAULT 0 ,
  `other` VARCHAR(255) NOT NULL DEFAULT '' ,
  `plugin_genericobject_smartphonecategories_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `plugin_genericobject_smartphonemodels_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `plugin_genericobject_smartphonetypes_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `url` VARCHAR(255) NOT NULL DEFAULT '' ,
  `plugin_genericobject_tablets_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `plugin_genericobject_bars_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `plugin_genericobject_foos_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `plugin_genericobject_uaus_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `plugin_genericobject_items_states_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `plugin_genericobject_test_abcs_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
   PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_smartphones` (`id`, `is_template`, `template_name`, `is_deleted`, `entities_id`, `is_recursive`, `name`, `serial`, `otherserial`, `locations_id`, `states_id`, `users_id`, `groups_id`, `manufacturers_id`, `users_id_tech`, `comment`, `is_helpdesk_visible`, `notepad`, `date_mod`, `date_creation`, `computers_id_host`, `config_str`, `contact`, `contact_num`, `creationdate`, `count`, `domains_id`, `expirationdate`, `groups_id_tech`, `is_global`, `other`, `plugin_genericobject_smartphonecategories_id`, `plugin_genericobject_smartphonemodels_id`, `plugin_genericobject_smartphonetypes_id`, `url`, `plugin_genericobject_tablets_id`, `plugin_genericobject_bars_id`, `plugin_genericobject_foos_id`, `plugin_genericobject_uaus_id`, `plugin_genericobject_items_states_id`, `plugin_genericobject_test_abcs_id`) VALUES
(1, 0, '', 0, 0, 0, 'Smartphone 1', 'SER0123', '', 1, 1, 3, 2, 3, 4, '', 1, NULL, '2025-03-06 10:08:51', '2025-03-06 10:08:51', 1, '# is_foo=0\nis_foo=1\nbar="adbizq"', '', '', '2025-02-01', 19, 4, '2025-12-31', 0, 0, 'some random value', 5, 2, 5, 'https://example.org/?id=9783', 1, 3, 2, 0, 0, 1),
(2, 0, '', 0, 0, 1, 'Smartphone 2', 'SER0198', '', 2, 2, 5, 1, 2, 0, '', 1, NULL, '2025-03-06 10:11:46', '2025-03-06 10:11:46', 2, '# no config', '', '', NULL, 0, 0, NULL, 4, 0, '', 4, 2, 0, '', 0, 4, 0, 1, 3, 0),
(3, 0, '', 0, 0, 0, 'Smartphone 3', '', 'INV123456', 0, 0, 0, 0, 0, 4, 'Some comments...', 1, NULL, '2025-03-06 10:12:59', '2025-03-06 10:12:59', 0, '', '', '', NULL, 0, 3, NULL, 0, 1, '', 0, 0, 4, '', 3, 0, 0, 2, 1, 2);

CREATE TABLE `glpi_plugin_genericobject_smartphonecategories` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
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
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
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
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NULL,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NOT NULL,
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
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `entities_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `name` VARCHAR(255) NOT NULL DEFAULT '' ,
  `comment` TEXT NULL,
  `date_mod` TIMESTAMP NULL,
  `date_creation` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
INSERT INTO `glpi_plugin_genericobject_tablets` (`id`, `entities_id`, `name`, `comment`, `date_mod`, `date_creation`) VALUES
(1, 0, 'Tablet 1', '', '2025-03-06 10:01:07', '2025-03-06 09:59:28'),
(2, 0, 'Tablet 2', '', '2025-03-06 10:09:15', '2025-03-06 10:09:15'),
(3, 0, 'Tablet 3', '', '2025-03-06 10:09:19', '2025-03-06 10:09:19'),
(4, 0, 'Tablet 4', '', '2025-03-06 10:09:21', '2025-03-06 10:09:21');
