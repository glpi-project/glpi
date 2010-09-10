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
// Original Author of file: Olivier Andreotti
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class RegistryKey extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][43];
   }

   function canCreate() {
      // Only create on ocsng sync
      return haveRight('sync_ocsng', 'w');
   }

   function canView() {
      return haveRight('ocsng', 'r');
   }

   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `".$this->getTable()."`
                WHERE `computers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }

   /** Display registry values for a computer
   * @param $ID integer : computer ID
   */
   static function showForComputer($ID) {
      global $DB,$CFG_GLPI, $LANG;

      if (!haveRight("computer","r")) {
         return false;
      }

      //REGISTRY HIVE
      $REGISTRY_HIVE=array("HKEY_CLASSES_ROOT",
                           "HKEY_CURRENT_USER",
                           "HKEY_LOCAL_MACHINE",
                           "HKEY_USERS",
                           "HKEY_CURRENT_CONFIG",
                           "HKEY_DYN_DATA");

      $query = "SELECT *
               FROM `glpi_registrykeys`
               WHERE `computers_id` = '$ID'";

      if ($result = $DB->query($query)) {
         echo "<div class='center'><table class='tab_cadre_fixe'>";

         if ($DB->numrows($result)!=0) {
            echo "<tr><th colspan='4'>";
            echo $DB->numrows($result)." ".$LANG['registry'][4];
            echo "</th></tr>\n";
            echo "<tr><th>".$LANG['registry'][6]."</th>";
            echo "<th>".$LANG['registry'][1]."</th>";
            echo "<th>".$LANG['registry'][2]."</th>";
            echo "<th>".$LANG['registry'][3]."</th></tr>\n";
            while ($data=$DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_1'>";
               echo "<td>".$data["ocs_name"]."</td>";
               if (isset($REGISTRY_HIVE[$data["hive"]])) {
                  echo "<td>".$REGISTRY_HIVE[$data["hive"]]."</td>";
               } else {
                  echo "<td>(".$data["hive"].")</td>";
               }
               echo "<td>".$data["path"]."</td>";
               echo "<td>".$data["value"]."</td>";
               echo "</tr>";
            }
         } else {
            echo "<tr class='tab_bg_2'><th>".$LANG['ocsconfig'][41]."</th></tr>";
            echo "<tr class='tab_bg_2'><td class='center b'>".$LANG['registry'][5]."</td></tr>";
         }

         echo "</table></div>\n\n";
      }
   }

}

?>