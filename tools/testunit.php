<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

include ('../inc/includes.php');

if (!isCommandLine()) {
   echo "<pre>";
}
echo "Checking all table\n";

$result = $DB->list_tables();

for ($i = 0; $line = $DB->fetch_array($result); $i++) {
   $table = $line[0];
   $type = getItemTypeForTable($table);

   if ($item = getItemForItemtype($type)) {
      //echo "+  $table > $type : Ok\n";

      if (get_class($item) != $type) {
         echo "** $table > $type > " . get_class($item) . " incoherent get_class($type) ** \n";
      }

      $table2 = getTableForItemType($type);
      if ($table != $table2) {
         echo "** $table > $type > " . $table2 . " incoherent getTableForItemType() ** \n";
      }

   } else {
      echo "** $table > ERROR $type class doesn't exists **\n";
   }
}
echo "End of $i tables analysed\n";
?>
