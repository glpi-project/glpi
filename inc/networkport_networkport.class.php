<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
   static public $itemtype_1 = 'NetworkPort';
   static public $items_id_1 = 'networkports_id_1';
   static public $itemtype_2 = 'NetworkPort';
   static public $items_id_2 = 'networkports_id_2';


   /**
    * Retrieve an item from the database
    *
    * @param $ID ID of the item to get
    *
    * @return true if succeed else false
   **/
   function getFromDBForNetworkPort($ID) {

      return $this->getFromDBByQuery("WHERE `".$this->getTable()."`.`networkports_id_1` = '$ID'
                                            OR `".$this->getTable()."`.`networkports_id_2` = '$ID'");
   }


   // TODO CommonDBConnexity: this post_addItem is only to define a smarter log (ie :
   // HISTORY_CONNECT_DEVICE with "From device1 to device2" We should remove that ...
   function post_addItem() {
      global $DB;

      // Get netpoint for $sport and $dport
      $sport = $this->fields['networkports_id_1'];
      $dport = $this->fields['networkports_id_2'];

      $ps = new NetworkPort();
      if (!$ps->getFromDB($sport)) {
         return false;
      }
      $pd = new NetworkPort();
      if (!$pd->getFromDB($dport)) {
         return false;
      }

      // Manage History
      $sourcename    = NOT_AVAILABLE;
      $destname      = NOT_AVAILABLE;
      $sourcehistory = false;
      $desthistory   = false;

      if ($sourceitem = getItemForItemtype($ps->fields['itemtype'])) {
         if ($sourceitem->getFromDB($ps->fields['items_id'])) {
            $sourcename    = $sourceitem->getName();
            $sourcehistory = $sourceitem->dohistory;
         }
      }

      if ($destitem = getItemForItemtype($pd->fields['itemtype'])) {
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
            //TRANS: %1$s is a name, %2$s is the new name
            $changes[2] = sprintf(__('From #%1$s to %2$s'), $ps->fields["name"], $changes[2]);
         }

         if ($pd->fields["itemtype"] == 'NetworkEquipment') {
            //TRANS: %1$s is a name, %2$s is the new name
            $changes[2] = sprintf(__('From %1$s to #%2$s'), $changes[2], $pd->fields["name"]);
         }

         Log::history($ps->fields["items_id"], $ps->fields["itemtype"], $changes,
                      $pd->fields["itemtype"], Log::HISTORY_CONNECT_DEVICE);
      }

      if ($desthistory) {
         $changes[2] = $sourcename;

         if ($pd->fields["itemtype"] == 'NetworkEquipment') {
            $changes[2] = sprintf(__('From #%1$s to %2$s'), $pd->fields["name"], $changes[2]);
         }

         if ($ps->fields["itemtype"] == 'NetworkEquipment') {
            $changes[2] = sprintf(__('From %1$s to #%2$s'), $changes[2], $ps->fields["name"]);
         }

         Log::history($pd->fields["items_id"], $pd->fields["itemtype"], $changes,
                      $ps->fields["itemtype"], Log::HISTORY_CONNECT_DEVICE);
      }
   }


   // TODO CommonDBConnexity: ... and also remove that
   function post_deleteFromDB() {

      // Update to blank networking item
      // clean datas of linked ports if network one
      $np1 = new NetworkPort();
      $np2 = new NetworkPort();
      if ($np1->getFromDB($this->fields['networkports_id_1'])
          && $np2->getFromDB($this->fields['networkports_id_2'])) {

         // Manage history

         $name      = NOT_AVAILABLE;
         $dohistory = false;

         if ($item = getItemForItemtype($np2->fields["itemtype"])) {
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
               $changes[1] = sprintf(__('From #%1$s to %2$s'), $np1->fields["name"], $changes[1]);
            }

            if ($np2->fields["itemtype"] == 'NetworkEquipment') {
               $changes[1] = sprintf(__('From %1$s to #%2$s'), $changes[1], $np2->fields["name"]);
            }
            Log::history($np1->fields["items_id"], $np1->fields["itemtype"], $changes,
                         $np2->fields["itemtype"], Log::HISTORY_DISCONNECT_DEVICE);
         }

         $name      = NOT_AVAILABLE;
         $dohistory = false;
         if ($item = getItemForItemtype($np1->fields["itemtype"])) {
            if ($item->getFromDB($np1->fields["items_id"])) {
               $name      = $item->getName();
               $dohistory = $item->dohistory;
            }
         }

         if ($dohistory) {
            $changes[0] = 0;
            $changes[1] = $name;
            $changes[2] = '';

            if ($np2->fields["itemtype"] == 'NetworkEquipment') {
               $changes[1] = sprintf(__('From #%1$s to %2$s'), $np2->fields["name"], $changes[1]);
            }

            if ($np1->fields["itemtype"] == 'NetworkEquipment') {
               $changes[1] = sprintf(__('From %1$s to #%2$s'), $changes[1], $np1->fields["name"]);
            }
            Log::history($np2->fields["items_id"], $np2->fields["itemtype"], $changes,
                         $np1->fields["itemtype"], Log::HISTORY_DISCONNECT_DEVICE);
         }
      }

   }


   /**
    * Get port opposite port ID
    *
    * @param $ID networking port ID
    *
    * @return integer ID of opposite port. false if not found
   **/
   function getOppositeContact($ID) {
      global $DB;

      if ($this->getFromDBForNetworkPort($ID)) {
         if ($this->fields['networkports_id_1'] == $ID) {
            return $this->fields['networkports_id_2'];
         }
         if ($this->fields['networkports_id_2'] == $ID) {
            return $this->fields['networkports_id_1'];
         }
         return false;
      }
   }

}
?>
