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
$to_add_link = ['glpi_changes_problems', 'glpi_changes_tickets', 'glpi_problems_tickets'];

foreach ($to_add_link as $table) {
    if (!$DB->fieldExists($table, 'link')) {
        $migration->addField($table, 'link', 'int', [
            'value' => 1,
        ]);
    }
}

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// Create new tables

if (!$DB->tableExists('glpi_changes_changes')) {
    $query = "CREATE TABLE `glpi_changes_changes` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `changes_id_1` int {$default_key_sign} NOT NULL DEFAULT '0',
        `changes_id_2` int {$default_key_sign} NOT NULL DEFAULT '0',
        `link` int NOT NULL DEFAULT '1',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`changes_id_1`,`changes_id_2`),
        KEY `changes_id_2` (`changes_id_2`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_problems_problems')) {
    $query = "CREATE TABLE `glpi_problems_problems` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `problems_id_1` int {$default_key_sign} NOT NULL DEFAULT '0',
        `problems_id_2` int {$default_key_sign} NOT NULL DEFAULT '0',
        `link` int NOT NULL DEFAULT '1',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`problems_id_1`,`problems_id_2`),
        KEY `problems_id_2` (`problems_id_2`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}
