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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}
class NetworkPort_Vlan extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'NetworkPort';
   public $items_id_1 = 'networkports_id';

   public $itemtype_2 = 'Vlan';
   public $items_id_2 = 'vlans_id';

   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      return $tab;
   }

   function unassignVlanbyID($ID) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_networkports_vlans`
                WHERE `id` = '$ID'";
      if ($result = $DB->query($query)) {
         $data = $DB->fetch_array($result);

         // Delete VLAN
         $query = "DELETE
                   FROM `glpi_networkports_vlans`
                   WHERE `id` = '$ID'";
         $DB->query($query);

         // Delete Contact VLAN if set
         $np = new NetworkPort();
         if ($contact_id = $np->getContact($data['networkports_id'])) {
            $query = "DELETE
                      FROM `glpi_networkports_vlans`
                      WHERE `networkports_id` = '$contact_id'
                            AND `vlans_id` = '" . $data['vlans_id'] . "'";
            $DB->query($query);
         }
      }
   }


   function unassignVlan($portID, $vlanID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_networkports_vlans`
                WHERE `networkports_id` = '$portID'
                      AND `vlans_id` = '$vlanID'";
      $DB->query($query);

      // Delete Contact VLAN if set
      $np = new NetworkPort();
      if ($contact_id=$np->getContact($portID)) {
         $query = "DELETE
                   FROM `glpi_networkports_vlans`
                   WHERE `networkports_id` = '$contact_id'
                         AND `vlans_id` = '$vlanID'";
         $DB->query($query);
      }
   }


   function assignVlan($port, $vlan) {
      global $DB;

      $query = "INSERT INTO
                `glpi_networkports_vlans` (`networkports_id`,`vlans_id`)
                VALUES ('$port','$vlan')";
      $DB->query($query);

      $np = new NetworkPort();
      if ($contact_id=$np->getContact($port)) {
         if ($np->getFromDB($contact_id)) {
            $vlans=self::getVlansForNetworkPort($port);
            if (!in_array($vlan,$vlans)) {
               $query = "INSERT INTO
                        `glpi_networkports_vlans` (`networkports_id`,`vlans_id`)
                        VALUES ('$contact_id','$vlan')";
               $DB->query($query);
            }
         }
      }
   }

   static function showForNetworkPort($ID, $canedit, $withtemplate) {
      global $DB, $CFG_GLPI, $LANG;

      $used = array();

      $query = "SELECT *
               FROM `glpi_networkports_vlans`
               WHERE `networkports_id` = '$ID'";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         echo "\n<table>";
         while ($line = $DB->fetch_array($result)) {
            $used[]=$line["vlans_id"];
            echo "<tr><td>" . Dropdown::getDropdownName("glpi_vlans", $line["vlans_id"]);
            echo "</td>\n<td>";
            if ($canedit) {
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php?unassign_vlan=".
                     "unassigned&amp;id=" . $line["id"] . "'>";
               echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete2.png\" alt='" .
                     $LANG['buttons'][59] . "' title='" . $LANG['buttons'][59] . "'></a>";
            } else {
               echo "&nbsp;";
            }
            echo "</td></tr>\n";
         }
         echo "</table>";
      } else {
         echo "&nbsp;";
      }
      return $used;
   }

   static function showForNetworkPortForm ($ID) {
      global $DB, $CFG_GLPI, $LANG;
      $port=new NetworkPort();

      if ($ID && $port->can($ID,'w')) {

         echo "\n<div class='center'>";
         echo "<form method='post' action='" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php'>";
         echo "<input type='hidden' name='id' value='$ID'>\n";

         echo "<table class='tab_cadre'>";
         echo "<tr><th>" . $LANG['setup'][90] . "</th></tr>\n";
         echo "<tr class='tab_bg_2'><td>";
         $used=self::showForNetworkPort($ID, true,0);
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_2'><td>";
         echo $LANG['networking'][55] . "&nbsp;:&nbsp;";
         Dropdown::show('Vlan', array('used' => $used));
         echo "&nbsp;<input type='submit' name='assign_vlan' value='" . $LANG['buttons'][3] .
                     "' class='submit'>";
         echo "</td></tr>\n";

         echo "</table></form>";
      }
   }

   static function getVlansForNetworkPort($portID) {
      global $DB;

      $vlans=array();
      $query = "SELECT `vlans_id`
               FROM `glpi_networkports_vlans`
               WHERE `networkports_id` = '$portID'";
      foreach ($DB->request($query) as $data) {
         $vlans[$data['vlans_id']] = $data['vlans_id'];
      }

      return $vlans;
   }
}

?>
