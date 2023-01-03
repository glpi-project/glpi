<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @var DB $DB
 * @var Migration $migration
 * @var array $ADDTODISPLAYPREF
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_databaseinstancetypes')) {
    $query = "CREATE TABLE `glpi_databaseinstancetypes` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
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
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
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
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) NOT NULL DEFAULT '',
         `version` varchar(255) NOT NULL DEFAULT '',
         `port` varchar(10) NOT NULL DEFAULT '',
         `path` varchar(255) NOT NULL DEFAULT '',
         `size` int NOT NULL DEFAULT '0',
         `databaseinstancetypes_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `databaseinstancecategories_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `manufacturers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `users_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
         `groups_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
         `states_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `itemtype` varchar(100) NOT NULL DEFAULT '',
         `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
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
         KEY `item` (`itemtype`,`items_id`),
         KEY `is_active` (`is_active`),
         KEY `is_deleted` (`is_deleted`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`),
         KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
         KEY `is_dynamic` (`is_dynamic`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_databaseinstances");
}

// Create glpi_databaseinstances itemtype/items_id if they are not existing (datamodel changed during v10.0 development)
if (!$DB->fieldExists('glpi_databaseinstances', 'itemtype') || !$DB->fieldExists('glpi_databaseinstances', 'items_id')) {
   //1- migrate glpi_databaseinstances table
    $migration->addField('glpi_databaseinstances', 'itemtype', 'string', [
        'after' => 'states_id'
    ]);
    $migration->addField('glpi_databaseinstances', 'items_id', "int {$default_key_sign} NOT NULL DEFAULT '0'", [
        'after' => 'itemtype'
    ]);
    $migration->addKey('glpi_databaseinstances', ['itemtype', 'items_id'], 'item');
    $migration->migrationOneTable('glpi_databaseinstances');
}
// Delete old table
if ($DB->tableExists('glpi_databaseinstances_items')) {
    $migration->dropTable('glpi_databaseinstances_items');
}

if (!$DB->tableExists('glpi_databases')) {
    $query = "CREATE TABLE `glpi_databases` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) NOT NULL DEFAULT '',
         `size` int NOT NULL DEFAULT '0',
         `databaseinstances_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_onbackup` tinyint NOT NULL DEFAULT '0',
         `is_active` tinyint NOT NULL DEFAULT '0',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `is_dynamic` tinyint NOT NULL DEFAULT '0',
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
         KEY `is_dynamic` (`is_dynamic`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`),
         KEY `databaseinstances_id` (`databaseinstances_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_databases");
}

if ($DB->fieldExists('glpi_states', 'is_visible_database')) {
    // Dev migration
    $migration->changeField('glpi_states', 'is_visible_database', 'is_visible_databaseinstance', 'bool', ['value' => 1]);
    $migration->dropKey('glpi_states', 'is_visible_database');
} else if (!$DB->fieldExists('glpi_states', 'is_visible_databaseinstance')) {
    $migration->addField('glpi_states', 'is_visible_databaseinstance', 'bool', [
        'value' => 1,
        'after' => 'is_visible_appliance'
    ]);
}
$migration->addKey('glpi_states', 'is_visible_databaseinstance');

// Create glpi_databases is_dynamic if not exist (datamodel changed during v10.0 development)
if (!$DB->fieldExists('glpi_databases', 'is_dynamic')) {
    $migration->addField('glpi_databases', 'is_dynamic', "tinyint NOT NULL DEFAULT '0'", [
        'after' => 'is_deleted'
    ]);
    $migration->addKey('glpi_databases', 'is_dynamic');
    $migration->migrationOneTable('glpi_databases');
}

$migration->addRight('database', ALLSTANDARDRIGHT);
$ADDTODISPLAYPREF['Database'] = [2, 3, 6, 9, 10];
