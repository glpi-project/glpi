<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

$migration->addField('glpi_dashboards_dashboards', 'users_id', "int {$default_key_sign} NOT NULL DEFAULT '0'", ['after' => 'context']);
$migration->addKey('glpi_dashboards_dashboards', 'users_id');

if (!$DB->tableExists('glpi_dashboards_filters')) {
    $query = "CREATE TABLE `glpi_dashboards_filters` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `dashboards_dashboards_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `filter` longtext,
         PRIMARY KEY (`id`),
         KEY `dashboards_dashboards_id` (`dashboards_dashboards_id`),
         KEY `users_id` (`users_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_dashboards_filters");
}
