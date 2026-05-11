<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

if (!$DB->tableExists('glpi_sharetokens')) {
    $query = "CREATE TABLE `glpi_sharetokens` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `itemtype` varchar(255) NOT NULL,
        `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `name` varchar(255) DEFAULT NULL,
        `token` varchar(64) NOT NULL,
        `is_active` tinyint NOT NULL DEFAULT '1',
        `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        `date_expiration` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `token` (`token`),
        KEY `item` (`itemtype`, `items_id`),
        KEY `name` (`name`),
        KEY `is_active` (`is_active`),
        KEY `users_id` (`users_id`),
        KEY `date_creation` (`date_creation`),
        KEY `date_mod` (`date_mod`),
        KEY `date_expiration` (`date_expiration`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}
