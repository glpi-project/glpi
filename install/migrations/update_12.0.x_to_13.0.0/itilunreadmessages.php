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

if (!$DB->tableExists('glpi_itilunreadmessages')) {
    $query = "CREATE TABLE `glpi_itilunreadmessages` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `parent_itemtype` varchar(255) NOT NULL,
        `parent_items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `itemtype` varchar(255) NOT NULL,
        `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `date` timestamp NULL DEFAULT NULL,
        `flags` int unsigned NOT NULL DEFAULT '0',
        `is_read` tinyint NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `users_id` (`users_id`),
        KEY `item` (`itemtype`,`items_id`),
        KEY `items_id` (`items_id`),
        KEY `parent_item` (`parent_itemtype`,`parent_items_id`),
        KEY `parent_items_id` (`parent_items_id`),
        KEY `is_read` (`is_read`),
        KEY `date` (`date`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
    $DB->doQuery($query);
}

$migration->addCrontask(
    itemtype: 'ITILUnreadMessage',
    name: 'cleanoldmessages',
    frequency: DAY_TIMESTAMP,
    options: [
        'state' => 1,
        'logs_lifetime' => 30,
    ]
);
