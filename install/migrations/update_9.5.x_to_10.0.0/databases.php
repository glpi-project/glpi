<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */
/**
 * @var DB $DB
 * @var Migration $migration
 * @var array $ADDTODISPLAYPREF
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

if (!$DB->tableExists('glpi_databaseinstancetypes')) {
   $query = "CREATE TABLE `glpi_databaseinstancetypes` (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_databaseinstancetypes");
}

if (!$DB->tableExists('glpi_databaseinstancecategories')) {
   $query = "CREATE TABLE `glpi_databaseinstancecategories` (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_databaseinstancecategoriess");
}

if (!$DB->tableExists('glpi_databaseinstances')) {
   $query = "CREATE TABLE `glpi_databaseinstances` (
         `id` int NOT NULL AUTO_INCREMENT,
         `entities_id` int NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) NOT NULL DEFAULT '',
         `version` varchar(255) NOT NULL DEFAULT '',
         `port` varchar(10) NOT NULL DEFAULT '',
         `path` varchar(255) NOT NULL DEFAULT '',
         `size` int NOT NULL DEFAULT '0',
         `databaseinstancetypes_id` int NOT NULL DEFAULT '0',
         `databaseinstancecategories_id` int NOT NULL DEFAULT '0',
         `locations_id` int NOT NULL DEFAULT '0',
         `manufacturers_id` int NOT NULL DEFAULT '0',
         `users_id_tech` int NOT NULL DEFAULT '0',
         `groups_id_tech` int NOT NULL DEFAULT '0',
         `states_id` int NOT NULL DEFAULT '0',
         `is_onbackup` tinyint NOT NULL DEFAULT '0',
         `is_active` tinyint NOT NULL DEFAULT '0',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `is_helpdesk_visible` tinyint NOT NULL DEFAULT '1',
         `is_dynamic` tinyint NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_lastboot` timestamp NULL DEFAULT NULL,
         `date_lastbackup` timestamp NULL DEFAULT NULL,
         `comment` text,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `name` (`name`),
         KEY `databaseinstancetypes_id` (`databaseinstancetypes_id`),
         KEY `databaseinstancecategories_id` (`databaseinstancecategories_id`),
         KEY `locations_id` (`locations_id`),
         KEY `manufacturers_id` (`manufacturers_id`),
         KEY `users_id_tech` (`users_id_tech`),
         KEY `groups_id_tech` (`groups_id_tech`),
         KEY `states_id` (`states_id`),
         KEY `is_active` (`is_active`),
         KEY `is_deleted` (`is_deleted`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`),
         KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
         KEY `is_dynamic` (`is_dynamic`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_databaseinstances");
}

if (!$DB->tableExists('glpi_databaseinstances_items')) {
   $query = "CREATE TABLE `glpi_databaseinstances_items` (
           `id` int NOT NULL AUTO_INCREMENT,
          `databaseinstances_id` int NOT NULL DEFAULT '0',
          `items_id` int NOT NULL DEFAULT '0',
          `itemtype` varchar(100) NOT NULL DEFAULT '',
          PRIMARY KEY (`id`),
          UNIQUE KEY `unicity` (`databaseinstances_id`,`items_id`,`itemtype`),
          KEY `item` (`itemtype`,`items_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_databaseinstances_items");
}

if (!$DB->tableExists('glpi_databases')) {
   $query = "CREATE TABLE `glpi_databases` (
         `id` int NOT NULL AUTO_INCREMENT,
         `entities_id` int NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) NOT NULL DEFAULT '',
         `size` int NOT NULL DEFAULT '0',
         `databaseinstances_id` int NOT NULL DEFAULT '0',
         `is_onbackup` tinyint NOT NULL DEFAULT '0',
         `is_active` tinyint NOT NULL DEFAULT '0',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_update` timestamp NULL DEFAULT NULL,
         `date_lastbackup` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `name` (`name`),
         KEY `is_active` (`is_active`),
         KEY `is_deleted` (`is_deleted`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`),
         KEY `databaseinstances_id` (`databaseinstances_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_databases");
}
$migration->addField('glpi_states', 'is_visible_database', 'bool', [
   'value' => 1,
   'after' => 'is_visible_appliance'
]);
$migration->addKey('glpi_states', 'is_visible_database');

$migration->addRight('database', ALLSTANDARDRIGHT);
//$ADDTODISPLAYPREF['Database'] = [2, 3, 4, 5];
