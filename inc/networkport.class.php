<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}


/// NetworkPort class
class NetworkPort extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_networkports';
   public $type = 'NetworkPort';

   /// TODO manage access right on this object

   // Specific ones
   /// ID of the port connected to the current one
   var $contact_id		= 0;
   /// hardare data : name
   var $device_name	= "";
   /// hardare data : ID
   var $device_ID		= 0;
   /// hardare data : type
   var $itemtype		= 0;
   /// hardare data : entity
   var $entities_id		= -1;
   /// hardare data : locations_id
   var $locations_id		= -1;
   /// hardare data : is_recursive
   var $is_recursive = 0;
   /// hardare data : is_deleted
   var $is_deleted = 0;

   static function canCreate() {
      return haveRight('networking', 'w');
   }

   static function canView() {
      return haveRight('networking', 'r');
   }

   function post_updateItem($input,$updates,$history=1) {

      // Only netpoint updates : ip and mac may be different.
      $tomatch=array("netpoints_id");
      $updates=array_intersect($updates,$tomatch);
      if (count($updates)) {
         $save_ID=$this->fields["id"];
         $n=new NetworkPort_NetworkPort;
         if ($this->fields["id"]=$n->getOppositeContact($save_ID)) {
            $this->updateInDB($updates);
         }
         $this->fields["id"]=$save_ID;
      }
   }

   function prepareInputForUpdate($input) {

      // Is a preselected mac adress selected ?
      if (isset($input['pre_mac']) && !empty($input['pre_mac'])) {
         $input['mac']=$input['pre_mac'];
         unset($input['pre_mac']);
      }
      return $input;
   }

   function prepareInputForAdd($input) {

      if (isset($input["logical_number"]) && strlen($input["logical_number"])==0) {
         unset($input["logical_number"]);
      }
      return $input;
   }

   function pre_deleteItem($ID) {
      removeConnector($ID);
      return true;
   }


   function cleanDBonPurge($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_networkports_networkports`
                WHERE `networkports_id_1` = '$ID'
                      OR `networkports_id_2` = '$ID'";
      $result = $DB->query($query);
   }

   // SPECIFIC FUNCTIONS
   /**
    * Retrieve data in the port of the item which belongs to
    *
    *@param $ID Integer : Id of the item to print
    *@param $itemtype item type
    *
    *@return boolean item found
    **/
   function getDeviceData($ID, $itemtype) {
      global $DB;

      $table = getTableForItemType($itemtype);

      $query = "SELECT *
                FROM `$table`
                WHERE `id` = '$ID'";
      if ($result=$DB->query($query)) {
         $data = $DB->fetch_array($result);
         $this->device_name = $data["name"];
         $this->is_deleted = $data["is_deleted"];
         $this->entities_id = $data["entities_id"];
         $this->locations_id = $data["locations_id"];
         $this->device_ID = $ID;
         $this->itemtype = $itemtype;
         $this->is_recursive = (isset($data["is_recursive"])?$data["is_recursive"]:0);
         return true;
      } else {
         return false;
      }
   }

   /**
    * Get port opposite port ID if linked item
    * ID store in contact_id
    *@param $ID networking port ID
    *
    *@return boolean item found
    **/
   function getContact($ID) {

      $wire = new NetworkPort_NetworkPort;
      if ($this->contact_id = $wire->getOppositeContact($ID)) {
         return true;
      } else {
         return false;
      }
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG, $CFG_GLPI;

      $ong[1] = $LANG['title'][26];
      return $ong;
   }

}

?>
