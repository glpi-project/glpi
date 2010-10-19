<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Update from 0.78 to 0.78.1
 *
 * @param $output string for format
 *       HTML (default) for standard upgrade
 *       empty = no ouput for PHPUnit
 *
 * @return bool for success (will die for most error)
 */
function update078to0781($output='HTML') {
   global $DB, $LANG;

   $updateresult = true;

   if ($output) {
      echo "<h3>".$LANG['install'][4]." -&gt; 0.78.1</h3>";
   }
   displayMigrationMessage("0781"); // Start

   displayMigrationMessage("0781", $LANG['update'][142] . ' - Clean reservation entity link'); // Updating schema

   $entities=getAllDatasFromTable('glpi_entities');
   $entities[0]="Root";

   $query = "SELECT DISTINCT `itemtype` FROM `glpi_reservationitems`";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            $itemtable=getTableForItemType($data['itemtype']);
            // ajout d'un contrôle pour voir si la table existe ( cas migration plugin non fait)
            if (!TableExists($itemtable)) {
               if ($output) {
                  echo "<p class='red'>*** Skip : no table $itemtable ***</p>";
               }
               continue;
            }
            $do_recursive=false;
            if (FieldExists($itemtable,'is_recursive')) {
               $do_recursive=true;
            }
            foreach ($entities as $entID => $val) {
               if ($do_recursive) {
                  // Non recursive ones
                  $query3="UPDATE `glpi_reservationitems`
                           SET `entities_id`=$entID, `is_recursive`=0
                           WHERE `itemtype`='".$data['itemtype']."'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID AND `is_recursive`=0)";
                  $DB->query($query3) or die("0.78.1 update entities_id and is_recursive=0
                        in glpi_reservationitems for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());

                  // Recursive ones
                  $query3="UPDATE `glpi_reservationitems`
                           SET `entities_id`=$entID, `is_recursive`=1
                           WHERE `itemtype`='".$data['itemtype']."'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID AND `is_recursive`=1)";
                  $DB->query($query3) or die("0.78.1 update entities_id and is_recursive=1
                        in glpi_reservationitems for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());
               } else {
                  $query3="UPDATE `glpi_reservationitems`
                           SET `entities_id`=$entID
                           WHERE `itemtype`='".$data['itemtype']."'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID)";
                  $DB->query($query3) or die("0.78.1 update entities_id in glpi_reservationitems
                        for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());
               }
            }
         }
      }
   }


   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0781"); // End

   return $updateresult;
}
?>
