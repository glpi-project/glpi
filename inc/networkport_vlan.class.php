<?php
/*
 * @version $Id: networkinterface.class.php 9836 2009-12-20 16:54:39Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
         $query = "INSERT INTO
                   `glpi_networkports_vlans` (`networkports_id`,`vlans_id`)
                   VALUES ('$contact_id','$vlan')";
         $DB->query($query);
      }
   }

}

?>
