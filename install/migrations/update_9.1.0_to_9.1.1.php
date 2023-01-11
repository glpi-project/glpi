<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * Update from 9.1 to 9.1.1
 *
 * @return bool for success (will die for most error)
 **/
function update910to911()
{
    global $DB, $migration;

    $current_config   = Config::getConfigurationValues('core');
    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '9.1.1'));
    $migration->setVersion('9.1.1');

    $backup_tables = false;
   // table already exist but deleted during the migration
   // not table created during the migration
   // not table created during the migration
    $newtables     = [];

    foreach ($newtables as $new_table) {
       // rename new tables if exists ?
        if ($DB->tableExists($new_table)) {
            $migration->dropTable("backup_$new_table");
            $migration->displayWarning("$new_table table already exists. " .
                                    "A backup have been done to backup_$new_table.");
            $backup_tables = true;
            $query         = $migration->renameTable("$new_table", "backup_$new_table");
        }
    }
    if ($backup_tables) {
        $migration->displayWarning(
            "You can delete backup tables if you have no need of them.",
            true
        );
    }

   // rectify missing right in 9.1 update
    if (countElementsInTable("glpi_profilerights", ['name' => 'license']) == 0) {
        foreach ($DB->request("glpi_profilerights", ["name" => 'software']) as $profrights) {
            $DB->insertOrDie(
                "glpi_profilerights",
                [
                    'id'           => null,
                    'profiles_id'  => $profrights['profiles_id'],
                    'name'         => "license",
                    'rights'       => $profrights['rights']
                ],
                "9.1 add right for softwarelicense"
            );
        }
    }

   //put you migration script here

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
