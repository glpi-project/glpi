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

if (!$DB->tableExists('glpi_items_processes')) {
    $query = "CREATE TABLE `glpi_items_processes` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `itemtype` varchar(100) DEFAULT NULL,
      `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `cmd` text,
      `cpuusage` float NOT NULL DEFAULT '0',
      `memusage` float NOT NULL DEFAULT '0',
      `pid` int NOT NULL DEFAULT '1',
      `started` timestamp NULL DEFAULT NULL,
      `tty` varchar(100) DEFAULT NULL,
      `user` varchar(100) DEFAULT NULL,
      `virtualmemory` int NOT NULL DEFAULT '1',
      `is_deleted` tinyint NOT NULL DEFAULT '0',
      `is_dynamic` tinyint NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `item` (`itemtype`,`items_id`),
      KEY `is_deleted` (`is_deleted`),
      KEY `is_dynamic` (`is_dynamic`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}
