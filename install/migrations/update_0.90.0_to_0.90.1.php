<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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
 * Update from 0.90 to 0.90.1
 *
 * @return bool
 **/
function update0900to0901()
{
    /**
     * @var \DBmysql $DB
     * @var \Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '0.90.1'));
    $migration->setVersion('0.90.1');

    $backup_tables = false;
    $newtables     = [];

    foreach ($newtables as $new_table) {
       // rename new tables if exists ?
        if ($DB->tableExists($new_table)) {
            $migration->dropTable("backup_$new_table");
            $migration->displayWarning("$new_table table already exists. " .
                                    "A backup have been done to backup_$new_table.");
            $backup_tables = true;
            $migration->renameTable("$new_table", "backup_$new_table");
        }
    }
    if ($backup_tables) {
        $migration->displayWarning(
            "You can delete backup tables if you have no need of them.",
            true
        );
    }

   // Add missing fill in 0.90 empty version
    $migration->addField("glpi_entities", 'inquest_duration', "integer", ['value' => 0]);

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
