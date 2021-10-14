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
 * @var array $ADDTODISPLAYPREF
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

if (!$DB->tableExists('glpi_items_remotemanagements')) {
   $query = "CREATE TABLE `glpi_items_remotemanagements` (
         `id` int NOT NULL AUTO_INCREMENT,
         `itemtype` varchar(100) DEFAULT NULL,
         `items_id` int NOT NULL DEFAULT '0',
         `remoteid` varchar(255) DEFAULT NULL,
         `type` varchar(255) DEFAULT NULL,
         `is_dynamic` tinyint NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         KEY `is_dynamic` (`is_dynamic`),
         KEY `item` (`itemtype`,`items_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_items_remotemanagements");
}
