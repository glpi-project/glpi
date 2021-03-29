<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
/**
 * @var DB $DB
 * @var Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

if (!$DB->tableExists('glpi_manuallinks')) {
   $query = "CREATE TABLE `glpi_manuallinks` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `url` varchar(8096) NOT NULL,
      `open_window` tinyint NOT NULL DEFAULT '1',
      `icon` varchar(255) DEFAULT NULL,
      `comment` text,
      `items_id` int NOT NULL DEFAULT '0',
      `itemtype` varchar(255) DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      `date_mod` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `item` (`itemtype`,`items_id`),
      KEY `items_id` (`items_id`),
      KEY `date_creation` (`date_creation`),
      KEY `date_mod` (`date_mod`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_manuallinks");
}
