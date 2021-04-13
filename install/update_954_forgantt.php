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
 * Update v9.5.4 with Dhtmlx Gantt integration
 *
 * @return bool for success (will die for most error)
 **/
function update954forgantt() {
 
    global $DB, $migration;
    $updateresult     = true;
 
    if (!$DB->tableExists('glpi_projecttasklinks')) {
        $query = "CREATE TABLE `glpi_projecttasklinks` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `source_id` int(11) NOT NULL,
            `source_uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `target_id` int(11) NOT NULL,
            `target_uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `type` tinyint(4) NOT NULL DEFAULT '0',
            `lag` smallint(6) DEFAULT '0',
            `lead` smallint(6) DEFAULT '0',
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    }
 
    $DB->queryOrDie($query, "9.5.4 add table glpi_projecttasklinks");

    $migration->executeMigration();
    return $updateresult;
}