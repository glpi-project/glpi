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
$default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
$default_charset   = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

// Add template foreign key field to all ITIL Objects
$itil_type_tables = [
    'glpi_tickets'  => 'tickettemplates_id',
    'glpi_changes'  => 'changetemplates_id',
    'glpi_problems' => 'problemtemplates_id',
];

foreach ($itil_type_tables as $table => $fkey_to_add) {
    if (!$DB->fieldExists($table, $fkey_to_add)) {
        $migration->addField($table, $fkey_to_add, "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->addKey($table, $fkey_to_add);
    }
}

// Add status_allowed field to all ITIL Object template tables
$itiltemplate_tables = [
    'glpi_tickettemplates'  => [1, 10, 2, 3, 4, 5, 6],
    'glpi_changetemplates'  => [1, 9, 10, 7, 4, 11, 12, 5, 8, 6, 14, 13],
    'glpi_problemtemplates' => [1, 7, 2, 3, 4, 5, 8, 6],
];

foreach ($itiltemplate_tables as $table => $all_statuses) {
    if (!$DB->fieldExists($table, 'allowed_statuses')) {
        $default_value = exportArrayToDB($all_statuses);
        $migration->addField($table, 'allowed_statuses', 'string', [
            'null'  => false,
            'value' => $default_value,
        ]);
    }
}

if (!$DB->tableExists('glpi_tickettemplatereadonlyfields')) {
    $query = "CREATE TABLE `glpi_tickettemplatereadonlyfields` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `tickettemplates_id` int unsigned NOT NULL DEFAULT '0',
        `num` int NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`tickettemplates_id`,`num`)
   ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_changetemplatereadonlyfields')) {
    $query = "CREATE TABLE `glpi_changetemplatereadonlyfields` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `changetemplates_id` int unsigned NOT NULL DEFAULT '0',
        `num` int NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`changetemplates_id`,`num`)
   ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_problemtemplatereadonlyfields')) {
    $query = "CREATE TABLE `glpi_problemtemplatereadonlyfields` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `problemtemplates_id` int unsigned NOT NULL DEFAULT '0',
        `num` int NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`problemtemplates_id`,`num`)
   ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}
