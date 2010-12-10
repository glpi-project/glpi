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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// NetworkPort_NetworkPort class
class NetworkPort_NetworkPort extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'NetworkPort';
   public $items_id_1 = 'networkports_id_1';
   public $itemtype_2 = 'NetworkPort';
   public $items_id_2 = 'networkports_id_2';

   /**
    * Retrieve an item from the database
    *
    *@param $ID ID of the item to get
    *
    *@return true if succeed else false
   **/
   function getFromDBForNetworkPort ($ID) {
      global $DB;

      // Make new database object and fill variables
      if (empty($ID)) {
         return false;
      }

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `networkports_id_1` = '$ID'
                      OR `networkports_id_2` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $this->fields = $DB->fetch_assoc($result);
            return true;
         }
      }
      return false;
   }


   function post_addItem() {
      global $DB, $LANG;

      // Get netpoint for $sport and $dport
      $sport = $this->fields['networkports_id_1'];
      $dport = $this->fields['networkports_id_2'];

      $ps = new NetworkPort;
      if (!$ps->getFromDB($sport)) {
         return false;
      }
      $pd = new NetworkPort;
      if (!$pd->getFromDB($dport)) {
         return false;
      }

      // Check netpoint for copy
      $source = "";
      $destination = "";
      if (isset ($ps->fields['netpoints_id']) && $ps->fields['netpoints_id'] != 0) {
         $source = $ps->fields['netpoints_id'];
      }

      if (isset ($pd->fields['netpoints_id']) && $pd->fields['netpoints_id'] != 0) {
         $destination = $pd->fields['netpoints_id'];
      }

      // Update Item
      $updates[0] = 'netpoints_id';

      if (empty ($source) && !empty ($destination)) {
         $ps->fields['netpoints_id'] = $destination;
         $ps->updateInDB($updates);
         addMessageAfterRedirect($LANG['connect'][15] . "&nbsp;: " . $LANG['networking'][51]);

      } else if (!empty ($source) && empty ($destination)) {
         $pd->fields['netpoints_id'] = $source;
         $pd->updateInDB($updates);
         addMessageAfterRedirect($LANG['connect'][15] . "&nbsp;: " . $LANG['networking'][51]);

      } else if ($source != $destination) {
         addMessageAfterRedirect($LANG['connect'][16] . "&nbsp;: " . $LANG['networking'][51]);
      }

      // Manage VLAN : use networkings one as defaults
      $npnet = -1;
      $npdev = -1;

      if ($ps->fields["itemtype"] != 'NetworkEquipment'
         && $pd->fields["itemtype"] == 'NetworkEquipment') {
         $npnet = $dport;
         $npdev = $sport;
      }

      if ($pd->fields["itemtype"] != 'NetworkEquipment'
         && $ps->fields["itemtype"] == 'NetworkEquipment') {
         $npnet = $sport;
         $npdev = $dport;
      }

      if ($npnet > 0 && $npdev > 0) {
         // Get networking VLAN
         // Unset MAC and IP from networking device
         $query = "SELECT *
                   FROM `glpi_networkports_vlans`
                   WHERE `networkports_id` = '$npnet'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // Found VLAN : clean vlan device and add found ones
               $query = "DELETE
                         FROM `glpi_networkports_vlans`
                         WHERE `networkports_id` = '$npdev' ";
               $DB->query($query);

               while ($data = $DB->fetch_array($result)) {
                  $query = "INSERT INTO
                            `glpi_networkports_vlans` (`networkports_id`, `vlans_id`)
                            VALUES ('$npdev','" . $data['vlans_id'] . "')";
                  $DB->query($query);
               }
            }
         }
      }
      // end manage VLAN

      // Manage History
      $sourcename    = NOT_AVAILABLE;
      $destname      = NOT_AVAILABLE;
      $sourcehistory = false;
      $desthistory   = false;

      if (class_exists($ps->fields['itemtype'])) {
         $sourceitem = new $ps->fields['itemtype']();
         if ($sourceitem->getFromDB($ps->fields['items_id'])) {
            $sourcename    = $sourceitem->getName();
            $sourcehistory = $sourceitem->dohistory;
         }
      }

      if (class_exists($pd->fields['itemtype'])) {
         $destitem = new $pd->fields['itemtype']();
         if ($destitem->getFromDB($pd->fields['items_id'])) {
            $destname    = $destitem->getName();
            $desthistory = $destitem->dohistory;
         }
      }

      $changes[0] = 0;
      $changes[1] = "";

      if ($sourcehistory) {

         $changes[2] = $destname;

         if ($ps->fields["itemtype"] == 'NetworkEquipment') {
            $changes[2] = "#" . $ps->fields["name"] . " > " . $changes[2];
         }

         if ($pd->fields["itemtype"] == 'NetworkEquipment') {
            $changes[2] = $changes[2] . " > #" . $pd->fields["name"];
         }

         Log::history($ps->fields["items_id"], $ps->fields["itemtype"], $changes,
                      $pd->fields["itemtype"], HISTORY_CONNECT_DEVICE);
      }

      if ($desthistory) {
         $changes[2] = $sourcename;

         if ($pd->fields["itemtype"] == 'NetworkEquipment') {
            $changes[2] = "#" . $pd->fields["name"] . " > " . $changes[2];
         }

         if ($ps->fields["itemtype"] == 'NetworkEquipment') {
            $changes[2] = $changes[2] . " > #" . $ps->fields["name"];
         }

         Log::history($pd->fields["items_id"], $pd->fields["itemtype"], $changes,
                      $ps->fields["itemtype"], HISTORY_CONNECT_DEVICE);
      }
   }


   function post_deleteFromDB() {

      // Update to blank networking item
      // clean datas of linked ports if network one
      $np1 = new NetworkPort;
      $np2 = new NetworkPort;
      if ($np1->getFromDB($this->fields['networkports_id_1'])
         && $np2->getFromDB($this->fields['networkports_id_2'])) {
         $npnet = NULL;
         $npdev = NULL;
         if ($np1->fields["itemtype"] != 'NetworkEquipment'
            && $np2->fields["itemtype"] == 'NetworkEquipment') {

            $npnet = $np2;
            $npdev = $np1;
         }

         if ($np2->fields["itemtype"] != 'NetworkEquipment'
            && $np1->fields["itemtype"] == 'NetworkEquipment') {

            $npnet = $np2;
            $npdev = $np1;
         }

         if ($npnet && $npdev ) {
            // If addresses are egal, was copied from device in GLPI 0.71 : clear it
            // Unset MAC and IP from networking device
            if ($npnet->fields['mac'] == $npdev->fields['mac']) {
               $npnet->update(array('id'  => $npnet->fields['id'],
                                    'mac' => ''));
            }
            if ($np1->fields['ip'] == $np2->fields['ip']) {
               $npnet->update(array('id'      => $npnet->fields['id'],
                                    'ip'      => '',
                                    'netmask' => '',
                                    'subnet'  => '',
                                    'gateway' => ''));
            }
            // Unset netpoint from common device
            $npdev->update(array('id'           => $npdev->fields['id'],
                                 'netpoints_id' => 0));
         }

         // Manage history

         $name      = NOT_AVAILABLE;
         $dohistory = false;

         if (class_exists($np2->fields["itemtype"])) {
            $item = new $np2->fields["itemtype"];
            if ($item->getFromDB($np2->fields["items_id"])) {
               $name      = $item->getName();
               $dohistory = $item->dohistory;
            }
         }

         if ($dohistory) {
            $changes[0] = 0;
            $changes[1] = $name;
            $changes[2] = '';

            if ($np1->fields["itemtype"] == 'NetworkEquipment') {
               $changes[1] = "#" . $np1->fields["name"] . " > " . $changes[1];
            }

            if ($np2->fields["itemtype"] == 'NetworkEquipment') {
               $changes[1] = $changes[1] . " > #" . $np2->fields["name"];
            }
            Log::history($np1->fields["items_id"], $np1->fields["itemtype"], $changes,
                         $np2->fields["itemtype"], HISTORY_DISCONNECT_DEVICE);
         }

         $name      = NOT_AVAILABLE;
         $dohistory = false;
         if (class_exists($np1->fields["itemtype"])) {
            $item = new $np1->fields["itemtype"];
            if ($item->getFromDB($np1->fields["items_id"])) {
               $name = $item->getName();
               $dohistory = $item->dohistory;
            }
         }

         if ($dohistory) {
            $changes[0] = 0;
            $changes[1] = $name;
            $changes[2] = '';

            if ($np2->fields["itemtype"] == 'NetworkEquipment') {
               $changes[1] = "#" . $np2->fields["name"] . " > " . $changes[1];
            }

            if ($np1->fields["itemtype"] == 'NetworkEquipment') {
               $changes[1] = $changes[1] . " > #" . $np1->fields["name"];
            }
            Log::history($np2->fields["items_id"], $np2->fields["itemtype"], $changes,
                         $np1->fields["itemtype"], HISTORY_DISCONNECT_DEVICE);
         }
      }

   }


   /**
    * Get port opposite port ID
    *
    *@param $ID networking port ID
    *
    *@return integer ID of opposite port. false if not found
   **/
   function getOppositeContact ($ID) {
      global $DB;

      if ($this->getFromDBForNetworkPort($ID)) {
         if ($this->fields['networkports_id_1'] == $ID) {
            return $this->fields['networkports_id_2'];

         } else if ($this->fields['networkports_id_2'] == $ID) {
            return $this->fields['networkports_id_1'];
         }
         return false;
      }
   }

}

?>
