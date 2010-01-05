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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}


// Relation between Computer and a CommonDevice (motherboard, memory, processor, ...)
class Computer_Device extends CommonDBChild {

   // From CommonDBChild
   public $itemtype = 'Computer';
   public $items_id = 'computers_id';

   function canCreate() {
      return haveRight('computer', 'w');
   }

   function canView() {
      return haveRight('computer', 'r');
   }

   function prepareInputForAdd($input) {
      if (empty($input['itemtype']) || !$input['items_id'] || !$input['computers_id']) {
         return false;
      }
      if (!isset($input['specificity']) || empty($input['specificity'])) {
         $dev = new $input['itemtype'];
         $dev->getFromDB($input['items_id']);
         $input['specificity'] = $dev->getField('specif_default');
      }
      return $input;
   }

   // overload to log HISTORY_ADD_DEVICE instead of HISTORY_ADD_RELATION
   function post_addItem($newID,$input) {

      if (isset($input['_no_history']) && $input['_no_history']) {
         return false;
      }
      $dev = new $this->fields['itemtype'];
      $dev->getFromDB($this->fields['items_id']);
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = addslashes($dev->getName());
      historyLog ($this->fields['computers_id'],'Computer',$changes,get_class($dev),HISTORY_ADD_DEVICE);
   }

   // overload to log HISTORY_DELETE_DEVICE instead of HISTORY_DEL_RELATION
   function post_deleteFromDB($ID) {

      if (isset($input['_no_history']) && $input['_no_history']) {
         return false;
      }
      $dev = new $this->fields['itemtype'];
      $dev->getFromDB($this->fields['items_id']);
      $changes[0] = 0;
      $changes[1] = addslashes($dev->getName());
      $changes[2] = '';
      historyLog ($this->fields['computers_id'],'Computer',$changes,get_class($dev),HISTORY_DELETE_DEVICE);
   }

   function post_updateItem($history=1) {

      if (!$history
          || (isset($this->input['_no_history']) &&  $this->input['_no_history'])
          || !in_array('specificity',$this->updates)) {
         return false;
      }
      $changes[0] = 0;
      $changes[1] = addslashes($this->oldvalues['specificity']);
      $changes[2] = $this->fields['specificity'];
      // history log
      historyLog ($this->fields['computers_id'],'Computer',$changes,$this->fields['itemtype'],HISTORY_UPDATE_DEVICE);
   }

   /**
    * Print the form for devices linked to a computer or a template
    *
    *
    * Print the form for devices linked to a computer or a template
    *
    *@param $computer Computer object
    *@param $withtemplate='' boolean : template or basic computer
    *
    *
    *@return Nothing (display)
    *
    **/
   static function showForComputer(Computer $computer, $withtemplate='') {
      global $DB, $LANG;

      $ID = $computer->getField('id');
      if (!$computer->can($ID, 'r')) {
         return false;
      }
      $canedit = ($withtemplate!=2 && $computer->can($ID, 'w'));

      $query = "SELECT count(*) AS NB, `id`, `itemtype`, `items_id`, `specificity`
                FROM `glpi_computers_devices`
                WHERE `computers_id` = '$ID'
                GROUP BY `itemtype`, `items_id`, `specificity`";

      if ($canedit) {
         echo "<form name='form_device_action' action='".getItemTypeFormURL(__CLASS__)."' method=\"post\" >";
         echo "<input type='hidden' name='computers_id' value='$ID'>";
      }
      echo "<table class='tab_cadre_fixe' >";
      echo "<tr><th colspan='63'>".$LANG['title'][30]."</th></tr>";

      $nb=0;
      $prev = '';
      foreach($DB->request($query) as $data) {
         if ($data['itemtype'] != $prev) {
            $prev = $data['itemtype'];
            initNavigateListItems($data['itemtype'], $computer->getTypeName()." = ".$computer->getName());
         }
         addToNavigateListItems($data['itemtype'], $data['items_id']);

         $device = new $data['itemtype'];
         if ($device->getFromDB($data['items_id'])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>";
            Dropdown::showInteger('quantity_'.$data['id'], $data['NB']);
            echo "</td><td>";
            if ($device->canCreate()) {
               echo "<a href='".$device->getSearchURL()."'>".$device->getTypeName()."</a>";
            } else {
               echo $device->getTypeName();
            }
            echo "</td><td>".$device->getLink()."</td>";

            $spec = $device->getFormData();
            if (isset($spec['label']) && count($spec['label'])) {
               $colspan = (60/count($spec['label']));
               foreach ($spec['label'] as $i => $label) {
                  if (isset($spec['value'][$i])) {
                     echo "<td colspan='$colspan'>".$spec['label'][$i]."&nbsp;: ";
                     echo $spec['value'][$i]."</td>";
                  } else if ($canedit){
                     // Specificity
                     echo "<td class='right' colspan='$colspan'>".$spec['label'][$i]."&nbsp;: ";
                     echo "<input type='text' name='value_".$data['id']."' value='";
                     echo $data['specificity']."' size='".$spec['size']."' ></td>";
                  } else {
                     echo "<td colspan='$colspan'>".$spec['label'][$i]."&nbsp;: ";
                     echo $data['specificity']."</td>";
                  }
               }
            } else {
               echo "<td colspan='60'>&nbsp;</td>";
            }
            echo "</tr>";
            $nb++;
         }
      }
      if ($canedit) {
         if ($nb>0) {
            echo "<tr><td colspan='63' class='tab_bg_1 center'>";
            echo "<input type='submit' class='submit' name='updateall' value='".
                   $LANG['buttons'][7]."'></td></tr>";
         }

         echo "<tr><td colspan='63' class='tab_bg_1 center'>";
         echo $LANG['devices'][0]."&nbsp;: ";
         $types =  array('DeviceMotherboard', 'DeviceProcessor', 'DeviceNetworkCard', 'DeviceMemory',
                         'DeviceHardDrive', 'DeviceDrive', 'DeviceControl', 'DeviceGraphicCard',
                         'DeviceSoundCard', 'DeviceCase', 'DevicePowerSupply', 'DevicePci');
         Dropdown::showAllItems('items_id', '', 0, -1, $types);
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</tr></table></form>";
      } else {
      echo "</table>";
      }
   }

   /**
    * Update an internal device quantity
    *
    * @param $newNumber new quantity value
    * @param $compDevID computer device ID
    */
   private function updateQuantity($newNumber, $compDevID) {
      global $DB;

      if (!$this->getFromDB($compDevID)) {
         return false;
      }
      $query2 = "SELECT `id`
                 FROM `glpi_computers_devices`
                 WHERE `computers_id` = '".$this->fields["computers_id"]."'
                       AND `itemtype` = '".$this->fields["itemtype"]."'
                       AND `items_id` = '".$this->fields["items_id"]."'
                       AND `specificity` = '".addslashes($this->fields["specificity"])."'";

      if ($result2 = $DB->query($query2)) {
         // Delete devices
         $number=$DB->numrows($result2);
         if ($number>$newNumber) {
            for ($i=$newNumber ; $i<$number ; $i++) {
               $data2 = $DB->fetch_array($result2);
               $this->delete($data2);
            }
         // Add devices
         } else if ($number<$newNumber) {
            $input = array('computers_id' => $this->fields["computers_id"],
                           'itemtype'     => $this->fields["itemtype"],
                           'items_id'     => $this->fields["items_id"],
                           'specificity'  => addslashes($this->fields["specificity"]));
            for ($i=$number ; $i<$newNumber ; $i++) {
               $this->add($input);
            }
         }
      }
   }

   /**
    * Update an internal device specificity
    *
    * @param $newValue new specifity value
    * @param $compDevID computer device ID
    */
   private function updateSpecificity($newValue, $compDevID) {
      global $DB;

      if (!$this->getFromDB($compDevID)) {
         return false;
      }
      // Is it a real change ?
      if (addslashes($this->fields['specificity'])==$newValue) {
         return false;
      }
      // Update specificity
      $query = "SELECT `id`
                FROM `glpi_computers_devices`
                WHERE `computers_id` = '".$this->fields["computers_id"]."'
                      AND `itemtype` = '".$this->fields["itemtype"]."'
                      AND `items_id` = '".$this->fields["items_id"]."'
                      AND `specificity` = '".addslashes($this->fields["specificity"])."'";

      $first = true;
      foreach ($DB->request($query) as $data) {
         $data['specificity'] = $newValue;
         $this->update($data, $first);
         $first = false;
      }
   }

   /**
    * Update the device attached to a computer
    *
    * @param $input array of data from the input form
    *
    */
   function updateAll($input) {

      // Update quantity
      foreach ($input as $key => $val) {
         $data = explode("_",$key);
         if (count($data) == 2 && $data[0] == "quantity") {
            $this->updateQuantity($val, $data[1]);
         }
      }

      // Update specificity
      foreach ($_POST as $key => $val) {
         $data = explode("_",$key);
         if (count($data) == 2 && $data[0] == "value") {
            $this->updateSpecificity($val,$data[1]);
         }
      }
   }

   // This class is not a real CommonDBChild...
   function cleanDBonItemDelete ($itemtype, $item_id) {
      global $DB;

      $query = "SELECT `id`
                FROM `".$this->getTable()."`";

      if ($itemtype == 'Computer') {
         $where = " WHERE `computers_id`='$item_id'";

      } else  {
         $where = " WHERE (`itemtype`='$itemtype'
                           AND `items_id`='$item_id')";
      }

      $result = $DB->query($query.$where);
      while ($data = $DB->fetch_assoc($result)) {
         if ($itemtype == 'Computer') {
            $data['_no_history'] = true; // Parent is deleted
         }
         $this->delete($data);
      }
   }

   /**
    * Duplicate all device from a computer template to his clone
    */
   function cloneComputer ($oldid, $newid) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `computers_id`='$oldid'";

      foreach ($db->request($query) as $data) {
         unset($data['id']);
         $data['computers_id'] = $newid;
         $data['_no_history'] = true;

         $this->add($data);
      }
   }

   function prepareInputForUpdate($input) {

      if ($this->fields['itemtype']=='DeviceGraphicCard') { // && isset($this->input['_from_ocs'])) {
         if (!$this->input['specificity']) {
            // memory can't be 0 (but sometime OCS report such value)
            return false;
         }
      }
      if ($this->fields['itemtype']=='DeviceProcessor') { // && isset($this->input['_from_ocs'])) {
         if (!$this->input['specificity']) {
            // frequency can't be 0 (but sometime OCS report such value)
            return false;
         }
         if ($this->fields['specificity']) { // old value
            $diff = ($this->input['specificity'] > $this->fields['specificity']
                      ? $this->input['specificity'] - $this->fields['specificity']
                      : $this->fields['specificity'] - $this->input['specificity']);
            if (($diff*100/$this->fields['specificity'])<5) {
               $this->input['_no_history'] = true;
            }
         }
      }
      if ($this->fields['specificity']==$this->input['specificity']) {
         // No change
         return false;
      }
      return $this->input;
   }

   /**
    * get the Mac Addresses for a computer
    *
    * @param $comp object
    *
    * @return array of Mac Addresses
    */
   static function getMacAddr (Computer $comp) {
      global $DB;

      $query = "SELECT DISTINCT `specificity`
                FROM `glpi_computers_devices`
                WHERE `itemtype`='DeviceNetworkCard'
                  AND `computers_id`='".$comp->getField('id')."'";

      $mac = array();
      foreach ($DB->request($query) as $data) {
         $mac[] = $data['specificity'];
      }
      return $mac;
   }


   /**
    * Delete old devices settings
    *
    *@param $devicetype integer : device type identifier.
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetDevices($glpi_computers_id, $devicetype) {
      global $DB;

      $query = "DELETE
                FROM `glpi_computers_devices`
                WHERE `itemtype` = '$devicetype'
                      AND `computers_id` = '$glpi_computers_id'";
      $DB->query($query);
   }

}
?>