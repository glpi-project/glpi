<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

if (!$DB->tableExists('glpi_appliances')) {
   $query = "CREATE TABLE `glpi_appliances` (
         `id` int(11) NOT NULL auto_increment,
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
         `name` varchar(255) NOT NULL DEFAULT '',
         `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
         `appliancetypes_id` int(11) NOT NULL DEFAULT '0',
         `comment` text,
         `locations_id` int(11) NOT NULL DEFAULT '0',
         `manufacturers_id` int(11) NOT NULL DEFAULT '0',
         `applianceenvironments_id` int(11) NOT NULL DEFAULT '0',
         `users_id` int(11) NOT NULL DEFAULT '0',
         `users_id_tech` int(11) NOT NULL DEFAULT '0',
         `groups_id` int(11) NOT NULL DEFAULT '0',
         `groups_id_tech` int(11) NOT NULL DEFAULT '0',
         `relationtype` int(11) NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `states_id` int(11) NOT NULL DEFAULT '0',
         `externalidentifier` varchar(255) DEFAULT NULL,
         `serial` varchar(255) DEFAULT NULL,
         `otherserial` varchar(255) DEFAULT NULL,
         PRIMARY KEY  (`id`),
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
         KEY `otherserial` (`otherserial`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   $DB->queryOrDie($query, "9.5 add table glpi_appliances");
}

if (!$DB->tableExists('glpi_appliances_items')) {
   $query = "CREATE TABLE `glpi_appliances_items` (
         `id` int(11) NOT NULL auto_increment,
         `appliances_id` int(11) NOT NULL default '0',
         `items_id` int(11) NOT NULL default '0',
         `itemtype` VARCHAR(100) NOT NULL default '',
         PRIMARY KEY (`id`),
         UNIQUE `unicity` (`appliances_id`,`items_id`,`itemtype`),
         KEY `appliances_id` (`appliances_id`),
         KEY `item` (`itemtype`,`items_id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   $DB->queryOrDie($query, "9.5 add table glpi_appliances_items");
}

if (!$DB->tableExists('glpi_appliancetypes')) {
   $query = "CREATE TABLE `glpi_appliancetypes` (
         `id` int(11) NOT NULL auto_increment,
         `entities_id` int(11) NOT NULL default '0',
         `is_recursive` tinyint(1) NOT NULL default '0',
         `name` varchar(255) NOT NULL default '',
         `comment` text,
         `externalidentifier` varchar(255) NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         UNIQUE (`externalidentifier`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   $DB->queryOrDie($query, "9.5 add table glpi_appliancetypes");
}

if (!$DB->tableExists('glpi_applianceenvironments')) {
   $query = "CREATE TABLE `glpi_applianceenvironments` (
         `id` int(11) NOT NULL auto_increment,
         `name` varchar(255) default NULL,
         `comment` text,
         PRIMARY KEY (`id`),
         KEY `name` (`name`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   $DB->queryOrDie($query, "9.5 add table glpi_applianceenvironments");
}

if (!$DB->tableExists('glpi_appliancerelations')) {
   $query = "CREATE TABLE `glpi_appliancerelations` (
         `id` int(11) NOT NULL auto_increment,
         `appliances_items_id` int(11) NOT NULL default '0',
         `relations_id` int(11) NOT NULL default '0' comment 'locations_id,domains_id or networks_id',
         PRIMARY KEY (`id`),
         KEY `appliances_items_id` (`appliances_items_id`),
         KEY `relations_id` (`relations_id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   $DB->queryOrDie($query, "9.5 add table glpi_appliancerelations");
}

$migration->addRight('appliance', ALLSTANDARDRIGHT);
$ADDTODISPLAYPREF['appliance'] = [2, 3, 4, 5];
