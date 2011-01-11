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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}


// Relation between Computer and devices
class Computer_Device extends CommonDBTM {

   public $auto_message_on_action = false;

   function __construct($itemtype='') {

      if (!empty($itemtype)) {
         $linktable = getTableForItemType('Computer_'.$itemtype);
         $this->forceTable($linktable);
      }
   }

   /// Get itemtype of devices : key is ocs identifier
   static function getDeviceTypes() {

      return array (1 => 'DeviceMotherboard', 2 => 'DeviceProcessor',   3 => 'DeviceMemory',
                    4 => 'DeviceHardDrive',   5 => 'DeviceNetworkCard', 6 => 'DeviceDrive',
                    7 => 'DeviceControl',     8 => 'DeviceGraphicCard', 9 => 'DeviceSoundCard',
                   10 => 'DevicePci',        11 => 'DeviceCase',       12 => 'DevicePowerSupply');
   }

   function getEmpty() {
      $this->fields['id'] = '';
      $this->fields['computers_id'] = '';
   }

   function canCreate() {
      return haveRight('computer', 'w');
   }

   function canView() {
      return haveRight('computer', 'r');
   }

   function prepareInputForAdd($input) {

      // For add from interface
      if (isset($input['itemtype'])) {
         $input['_itemtype'] = $input['itemtype'];
         unset($input['itemtype']);
      }

      if (empty($input['_itemtype']) || !$input['computers_id']) {
         return false;
      }

      $dev = new $input['_itemtype']();
      // For add from interface
      if (isset($input['items_id'])) {
         $input[$dev->getForeignKeyField()] = $input['items_id'];
         unset($input['items_id']);
      }
      if (!$input[$dev->getForeignKeyField()]) {
         return false;
      }

      $linktable = getTableForItemType('Computer_'.$input['_itemtype']);
      $this->forceTable($linktable);

      if (count($dev->getSpecifityLabel()) > 0
          && (!isset($input['specificity']) || empty($input['specificity']))) {

         $dev = new $input['_itemtype'];
         $dev->getFromDB($input[$dev->getForeignKeyField()]);
         $input['specificity'] = $dev->getField('specif_default');
      }
      return $input;
   }

   // overload to log HISTORY_ADD_DEVICE instead of HISTORY_ADD_RELATION
   function post_addItem() {

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }
      $dev = new $this->input['_itemtype']();

      $dev->getFromDB($this->fields[$dev->getForeignKeyField()]);
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = addslashes($dev->getName());
      Log::history($this->fields['computers_id'],'Computer',$changes,get_class($dev),
                   HISTORY_ADD_DEVICE);
   }

   // overload to log HISTORY_DELETE_DEVICE instead of HISTORY_DEL_RELATION
   function post_deleteFromDB() {

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }
      $dev = new $this->input['_itemtype']();

      $dev->getFromDB($this->fields[$dev->getForeignKeyField()]);
      $changes[0] = 0;
      $changes[1] = addslashes($dev->getName());
      $changes[2] = '';
      Log::history($this->fields['computers_id'],'Computer',$changes,get_class($dev),
                   HISTORY_DELETE_DEVICE);
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
      Log::history($this->fields['computers_id'],'Computer',$changes,
                   $this->input['_itemtype'],HISTORY_UPDATE_DEVICE);
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

      $devtypes = self::getDeviceTypes();

      $ID = $computer->getField('id');
      if (!$computer->can($ID, 'r')) {
         return false;
      }
      $canedit = ($withtemplate!=2 && $computer->can($ID, 'w'));

      if ($canedit) {
         echo "<form name='form_device_action' action='".getItemTypeFormURL(__CLASS__)."' method='post'>";
         echo "<input type='hidden' name='computers_id' value='$ID'>";
      }
      echo "<table class='tab_cadre_fixe' >";
      echo "<tr><th colspan='63'>".$LANG['title'][30]."</th></tr>";
      $nb = 0;

      $specificity_units = array ('DeviceProcessor' => $LANG['setup'][35],
                                 'DeviceMemory' => $LANG['common'][82],
                                 'DeviceHardDrive' => $LANG['common'][82],
                                 'DeviceGraphicCard' => $LANG['common'][82]);

      foreach ($devtypes as $itemtype) {
         initNavigateListItems($itemtype, $computer->getTypeName()." = ".$computer->getName());

         $device = new $itemtype;
         $specificities = $device->getSpecifityLabel();
         $specif_fields = array_keys($specificities);
         $specif_text = implode(',',$specif_fields);
         if (!empty($specif_text)) {
            $specif_text=" ,".$specif_text." ";
         }

         $linktable = getTableForItemType('Computer_'.$itemtype);
         $fk = getForeignKeyFieldForTable(getTableForItemType($itemtype));

         $query = "SELECT count(*) AS NB, `id`, `$fk` $specif_text
                  FROM `$linktable`
                  WHERE `computers_id` = '$ID'
                  GROUP BY `$fk` $specif_text";

         $prev = '';

         foreach($DB->request($query) as $data) {
            addToNavigateListItems($itemtype, $data[$fk]);

            if ($device->getFromDB($data[$fk])) {
               echo "<tr class='tab_bg_2'>";
               echo "<td class='center'>";
               Dropdown::showInteger("quantity_".$itemtype."_".$data['id'], $data['NB']);
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
                        echo "<input type='text' name='value_".$itemtype."_".$data['id']."' value='";
                        echo $data['specificity']."' size='".$spec['size']."' >";
                        if (isset($specificity_units[$device->getType()])) {
                           echo '&nbsp;'.$specificity_units[$device->getType()];
                        }
                        echo "</td>";
                     } else {
                        echo "<td colspan='$colspan'>".$spec['label'][$i]."&nbsp;: ";
                        echo $data['specificity'];
                        if (isset($specificity_units[$device->getType()])) {
                           echo '&nbsp;'.$specificity_units[$device->getType()];
                        }

                        echo "</td>";
                     }
                  }
               } else {
                  echo "<td colspan='60'>&nbsp;</td>";
               }
               echo "</tr>";
               $nb++;
            }
         }
      }

      if ($canedit) {
         if ($nb > 0) {
            echo "<tr><td colspan='63' class='tab_bg_1 center'>";
            echo "<input type='submit' class='submit' name='updateall' value='".
                   $LANG['buttons'][7]."'></td></tr>";
         }

         echo "<tr><td colspan='63' class='tab_bg_1 center'>";
         echo $LANG['devices'][0]."&nbsp;: ";
         Dropdown::showAllItems('items_id', '', 0, -1, $devtypes);
         echo "<input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit'>";
         echo "</tr></table></form>";
      } else {
      echo "</table>";
      }
   }

   /**
    * Update an internal device quantity
    *
    * @param $newNumber new quantity value
    * @param $itemtype itemtype of device
    * @param $compDevID computer device ID
    */
   private function updateQuantity($newNumber, $itemtype,$compDevID) {
      global $DB;

      $linktable = getTableForItemType('Computer_'.$itemtype);
      $this->forceTable($linktable);
      $fk = getForeignKeyFieldForTable(getTableForItemType($itemtype));
      // Force table for link
      $item = new $itemtype();
      $specif_fields = $item->getSpecifityLabel();

      if (!$this->getFromDB($compDevID)) {
         return false;
      }

      $query2 = "SELECT `id`
                 FROM `$linktable`
                 WHERE `computers_id` = '".$this->fields["computers_id"]."'
                       AND `$fk` = '".$this->fields[$fk]."'";
      if (count($specif_fields)) {
         foreach ($specif_fields as $field => $name) {
            $query2.= " AND `$field` = '".addslashes($this->fields[$field])."' ";
         }
      }

      if ($result2 = $DB->query($query2)) {
         // Delete devices
         $number = $DB->numrows($result2);
         if ($number > $newNumber) {
            for ($i=$newNumber ; $i<$number ; $i++) {
               $data2 = $DB->fetch_array($result2);
               $data2['_itemtype'] = $itemtype;
               $this->delete($data2);
            }
         // Add devices
         } else if ($number < $newNumber) {
            $input = array('computers_id' => $this->fields["computers_id"],
                           '_itemtype'    => $itemtype,
                           $fk            => $this->fields[$fk]);
            if (count($specif_fields)) {
               foreach ($specif_fields as $field => $name) {
                  $input[$field] = addslashes($this->fields["specificity"]);
               }
            }
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
    * @param $itemtype itemtype of device
    * @param $compDevID computer device ID
    */
   private function updateSpecificity($newValue, $itemtype,$compDevID) {
      global $DB;

      $item = new $itemtype();
      $specif_fields=$item->getSpecifityLabel();

      // No specificity for this device type
      if (count($specif_fields) == 0) {
         return false;
      }

      $linktable = getTableForItemType('Computer_'.$itemtype);
      $this->forceTable($linktable);
      $fk = getForeignKeyFieldForTable(getTableForItemType($itemtype));

      if (!$this->getFromDB($compDevID)) {
         return false;
      }
      // Is it a real change ?
      if (addslashes($this->fields['specificity']) == $newValue) {
         return false;
      }
      // Update specificity
      $query = "SELECT `id`
                FROM `$linktable`
                WHERE `computers_id` = '".$this->fields["computers_id"]."'
                      AND `$fk` = '".$this->fields[$fk]."'
                      AND `specificity` = '".addslashes($this->fields["specificity"])."'";

      $first = true;
      foreach ($DB->request($query) as $data) {
         $data['specificity'] = $newValue;
         $data['_itemtype'] = $itemtype;
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
         if (count($data) == 3 && $data[0] == "quantity") {
            $this->updateQuantity($val, $data[1],$data[2]);
         }
      }

      // Update specificity
      foreach ($_POST as $key => $val) {
         $data = explode("_",$key);
         if (count($data) == 3 && $data[0] == "value") {
            $this->updateSpecificity($val,$data[1],$data[2]);
         }
      }
   }

   function cleanDBonItemDelete ($itemtype, $item_id) {
      global $DB;

      if ($itemtype == 'Computer') {
         $devtypes = self::getDeviceTypes();
         foreach ($devtypes as $type) {
            $linktable = getTableForItemType('Computer_'.$type);
            $this->forceTable($linktable);

            $query = "SELECT `id`
                      FROM `$linktable`
                      WHERE `computers_id` = '$item_id'";

            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $data['_no_history'] = true; // Parent is deleted
               $data['_itemtype'] = $type;
               $this->delete($data);
            }
         }
      } else {
         $linktable = getTableForItemType('Computer_'.$itemtype);
         $fk = getForeignKeyFieldForTable(getTableForItemType($itemtype));
         $this->forceTable($linktable);

         $query = "SELECT `id`
                   FROM `$linktable`
                   WHERE `$fk` = '$item_id'";

         $result = $DB->query($query);
         while ($data = $DB->fetch_assoc($result)) {
            $data['_itemtype'] = $itemtype;
            $this->delete($data);
         }
      }
   }

   /**
    * Duplicate all device from a computer template to his clone
    */
   function cloneComputer ($oldid, $newid) {
      global $DB;

      $devtypes = self::getDeviceTypes();
      foreach ($devtypes as $itemtype) {
         $linktable = getTableForItemType('Computer_'.$itemtype);
         $fk = getForeignKeyFieldForTable(getTableForItemType($itemtype));

         $query = "SELECT *
                   FROM `$linktable`
                   WHERE `computers_id` = '$oldid'";

         foreach ($DB->request($query) as $data) {
            unset($data['id']);
            $data['computers_id'] = $newid;
            $data['_itemtype'] = $itemtype;
            $data['_no_history'] = true;

            $this->add($data);
         }
      }
   }

   function prepareInputForUpdate($input) {

      if (isset($input['itemtype'])) {
         $input['_itemtype'] = $input['itemtype'];
         unset($input['itemtype']);
      }

      if ($input['_itemtype'] == 'DeviceGraphicCard') { // && isset($this->input['_from_ocs'])) {
         if (!$this->input['specificity']) {
            // memory can't be 0 (but sometime OCS report such value)
            return false;
         }
      }
      if ($input['_itemtype'] == 'DeviceProcessor') { // && isset($this->input['_from_ocs'])) {
         if (!$this->input['specificity']) {
            // frequency can't be 0 (but sometime OCS report such value)
            return false;
         }
         if ($this->fields['specificity']) { // old value
            $diff = ($this->input['specificity'] > $this->fields['specificity']
                      ? $this->input['specificity'] - $this->fields['specificity']
                      : $this->fields['specificity'] - $this->input['specificity']);
            if (($diff*100/$this->fields['specificity']) < 5) {
               $this->input['_no_history'] = true;
            }
         }
      }
      if ($this->fields['specificity'] == $this->input['specificity']) {
         // No change
         return false;
      }
      $linktable = getTableForItemType('Computer_'.$input['_itemtype']);
      $this->forceTable($linktable);

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
                FROM `glpi_computers_devicenetworkcards`
                WHERE `computers_id`='".$comp->getField('id')."'";

      $mac = array();
      foreach ($DB->request($query) as $data) {
         $mac[] = $data['specificity'];
      }
      return $mac;
   }


   /**
    * Delete old devices settings
    *
    *@param $itemtype integer : device type identifier.
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetDevices($glpi_computers_id, $itemtype) {
      global $DB;

      $linktable = getTableForItemType('Computer_'.$itemtype);

      $query = "DELETE
                FROM `$linktable`
                WHERE `computers_id` = '$glpi_computers_id'";
      $DB->query($query);
   }


}
?>