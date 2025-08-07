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

$default_charset   = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_itemtranslations_itemtranslations')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_itemtranslations_itemtranslations` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `items_id` int unsigned NOT NULL DEFAULT '0',
            `itemtype` varchar(100) NOT NULL,
            `key` varchar(255) NOT NULL,
            `language` varchar(10) NOT NULL,
            `translations` JSON NOT NULL,
            `hash` varchar(32) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_item_key` (`items_id`, `itemtype`, `key`, `language`),
            KEY `item` (`itemtype`, `items_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
} else {
    $migration->changeField('glpi_itemtranslations_itemtranslations', 'language', 'language', 'varchar(10) NOT NULL');
}
