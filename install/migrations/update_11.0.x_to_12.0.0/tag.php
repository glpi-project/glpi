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

if (!$DB->tableExists('glpi_tags')) {
    $query = "CREATE TABLE `glpi_tags` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `is_recursive` tinyint NOT NULL DEFAULT '0',
        `is_active` tinyint NOT NULL DEFAULT '0',
        `name` varchar(255) NOT NULL,
        `comment` text,
        `color` varchar(7) NULL DEFAULT NULL,
        `bg_color` varchar(7) NULL DEFAULT NULL,
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`entities_id`, `name`),
        KEY `is_recursive` (`is_recursive`),
        KEY `is_active` (`is_active`),
        KEY `name` (`name`),
        KEY `date_creation` (`date_creation`),
        KEY `date_mod` (`date_mod`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_tags_itemtypes')) {
    $query = "CREATE TABLE `glpi_tags_itemtypes` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `tags_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `itemtype` varchar(255) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`itemtype`, `tags_id`),
        KEY `tags_id` (`tags_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

$migration->addRight('tag', ALLSTANDARDRIGHT, ['dropdown' => UPDATE]);
