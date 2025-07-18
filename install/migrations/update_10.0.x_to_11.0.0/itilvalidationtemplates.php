<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
 * @var DBmysql $DB
 * @var Migration $migration
 */
$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// Add ITILValidationTemplates table
if (!$DB->tableExists('glpi_itilvalidationtemplates')) {
    $query = "CREATE TABLE `glpi_itilvalidationtemplates` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `is_recursive` tinyint NOT NULL DEFAULT '0',
        `name` varchar(255) NOT NULL DEFAULT '',
        `content` text,
        `comment` text,
        `date_mod` timestamp NULL DEFAULT NULL,
        `date_creation` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `entities_id` (`entities_id`),
        KEY `is_recursive` (`is_recursive`),
        KEY `name` (`name`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

// Add ITILValidationTemplatesTargets table
if (!$DB->tableExists('glpi_itilvalidationtemplates_targets')) {
    $query = "CREATE TABLE `glpi_itilvalidationtemplates_targets` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `itilvalidationtemplates_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `itemtype` varchar(100) DEFAULT NULL,
        `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `groups_id` int {$default_key_sign} DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `itilvalidationtemplates_id` (`itilvalidationtemplates_id`),
        KEY `item` (`itemtype`,`items_id`),
        KEY `groups_id` (`groups_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

$table = 'glpi_changevalidations';
// Add new 'itilvalidationtemplates_id' field on 'glpi_changevalidations' table
$fkey_to_add = 'itilvalidationtemplates_id';
if (!$DB->fieldExists($table, $fkey_to_add, false)) {
    $migration->addField($table, $fkey_to_add, 'fkey', ['after' => 'users_id_validate']);
    $migration->addKey($table, $fkey_to_add);
}

$table = 'glpi_ticketvalidations';
// Add new 'itilvalidationtemplates_id' field on 'glpi_ticketvalidations' table
$fkey_to_add = 'itilvalidationtemplates_id';
if (!$DB->fieldExists($table, $fkey_to_add, false)) {
    $migration->addField($table, $fkey_to_add, 'fkey', ['after' => 'users_id_validate']);
    $migration->addKey($table, $fkey_to_add);
}

$migration->addRight('itilvalidationtemplate', ALLSTANDARDRIGHT, ['dropdown' => UPDATE]);
