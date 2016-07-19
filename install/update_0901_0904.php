<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

/**
 * Update from 0.90.1 to 0.90.2
 *
 * @return bool for success (will die for most error)
**/
function update0901to0902() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.90.2'));
   $migration->setVersion('0.90.2');


   $backup_tables = false;
   $newtables     = array();

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if (TableExists($new_table)) {
         $migration->dropTable("backup_$new_table");
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.",
                                 true);
   }

   // Add rights for licenses
   $profileRight = new profileRight();
   foreach ($DB->request('glpi_profiles') as $profile) {
      if (!countElementsInTable("glpi_profilerights",
                                "`profiles_id`='".$profile['id']."' AND `name`='license'")) {
        $query = "SELECT `rights`
                  FROM `glpi_profilerights`
                  WHERE `profiles_id`='".$profile['id']."'
                     AND `name`='software'";
        $result = $DB->query($query);
        $right = 0;
        if ($DB->numrows($result) > 0) {
           $right = $DB->result($result, 0, "rights");
        }
        $query = "INSERT INTO `glpi_profilerights`
                             (`profiles_id`, `name`, `rights`)
                      VALUES ('".$profile['id']."', 'license', '$right')";
      }
   }

   $migration->addField('glpi_softwarelicenses', 'is_template', 'bool');
   $migration->addField('glpi_softwarelicenses', 'template_name', 'string');
   $migration->addKey('glpi_softwarelicenses', 'is_template');

   $migration->addField('glpi_softwarelicenses', 'is_deleted', 'bool');
   $migration->addKey('glpi_softwarelicenses', 'is_deleted');

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
