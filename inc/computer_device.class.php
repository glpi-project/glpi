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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * Relation between Computer and devices
**/
class Computer_Device extends CommonDBTM {

   public $auto_message_on_action = false;


   function __construct($itemtype='') {

      if (!empty($itemtype)) {
         $linktable = getTableForItemType('Computer_'.$itemtype);
         $this->forceTable($linktable);
      }
   }


   /**
    * Get itemtype of devices : key is ocs identifier
   **/
   static function getDeviceTypes() {

      return array(1 => 'DeviceMotherboard', 2 => 'DeviceProcessor',   3 => 'DeviceMemory',
                   4 => 'DeviceHardDrive',   5 => 'DeviceNetworkCard', 6 => 'DeviceDrive',
                   7 => 'DeviceControl',     8 => 'DeviceGraphicCard', 9 => 'DeviceSoundCard',
                  10 => 'DevicePci',        11 => 'DeviceCase',       12 => 'DevicePowerSupply');
   }


   function getEmpty() {

      $this->fields['id'] = '';
      $this->fields['computers_id'] = '';
   }


   function canCreate() {
      return Session::haveRight('computer', 'w');
   }


   function canView() {
      return Session::haveRight('computer', 'r');
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

         $dev = new $input['_itemtype']();
         $dev->getFromDB($input[$dev->getForeignKeyField()]);
         $input['specificity'] = $dev->getField('specif_default');
      }
      return $input;
   }


   /**
    * overload to log HISTORY_ADD_DEVICE instead of HISTORY_ADD_RELATION
   **/
   function post_addItem() {

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }
      $dev = new $this->input['_itemtype']();

      $dev->getFromDB($this->fields[$dev->getForeignKeyField()]);
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = addslashes($dev->getName());
      Log::history($this->fields['computers_id'], 'Computer', $changes, get_class($dev),
                   Log::HISTORY_ADD_DEVICE);
   }


   /**
    * overload to log HISTORY_DELETE_DEVICE instead of HISTORY_DEL_RELATION
   **/
   function post_deleteFromDB() {

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }
      $dev = new $this->input['_itemtype']();

      $dev->getFromDB($this->fields[$dev->getForeignKeyField()]);
      $changes[0] = 0;
      $changes[1] = addslashes($dev->getName());
      $changes[2] = '';
      Log::history($this->fields['computers_id'], 'Computer', $changes, get_class($dev),
                   Log::HISTORY_DELETE_DEVICE);
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
      Log::history($this->fields['computers_id'], 'Computer', $changes, $this->input['_itemtype'],
                   Log::HISTORY_UPDATE_DEVICE);
   }


   /**
    * Print the form for devices linked to a computer or a template
    *
    * @param $computer        Computer object
    * @param $withtemplate    boolean : template or basic computer (default '')
    *
    * @return Nothing (display)
   **/
   static function showForComputer(Computer $computer, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $devtypes = self::getDeviceTypes();

      $ID = $computer->getField('id');
      if (!$computer->can($ID, 'r')) {
         return false;
      }
      $canedit = ($withtemplate!=2 && $computer->can($ID, 'w'));

      echo "<div class='spaced'>";
      $rand = mt_rand();
      if ($canedit) {
         echo "<form id='form_device_action$rand' name='form_device_action$rand'
                     action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>";
         echo "<input type='hidden' name='computers_id' value='$ID'>";
      }

      $table = new HTMLTable_();

      $table->setTitle(_n('Component', 'Components', 2));

      $common_column = $table->addHeader('common', __('Type of component'));
      if ($canedit) {
         $delete_column   = $table->addHeader('delete', __('Delete'));
      }
      $specific_column = $table->addHeader('specificities', __('Specificities'));

      foreach ($devtypes as $itemtype_index => $itemtype) {
         if ($device=getItemForItemtype($itemtype)) {

            $table_group = $table->createGroup($itemtype, '');

            if ($device->canView()) {
               $header_value = "<a href='".$device->getSearchURL()."'>" .
                               $device->getTypeName(1) . "</a>";
            } else {
               $header_value = $device->getTypeName(1);
            }

            $previous_header = $name_column     = $table_group->addHeader($common_column, 'name',
                                                                       $header_value);
            $name_column->setItemType($itemtype);

            if ($canedit) {
               $previous_header = $delete_all  = $table_group->addHeader($delete_column, 'all',
                                                                         '',
                                                                         $previous_header);
            }

            $device_chars = $itemtype::getHTMLTableHeaderForComputer_Device($table_group,
                                                                            $specific_column,
                                                                            $previous_header);

            if ($canedit) {
               $previous_header = $delete_one  = $table_group->addHeader($delete_column, 'one',
                                                                         '',
                                                                         $previous_header);
            }

            $specificities     = $itemtype::getSpecifityLabel();
            $specificity_names = array_values($specificities);
            if (count($specificity_names) > 0) {
               $link_char   = $table_group->addHeader($specific_column, 'link',
                                                      $specificity_names[0], $previous_header);
            }

            Session::initNavigateListItems($itemtype,
                               //TRANS : %1$s is the itemtype name,
                               //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   $computer->getTypeName(1),
                                                   $computer->getName()));

            $specif_fields = array_keys($specificities);
            $specif_text   = implode(',',$specif_fields);

            if (!empty($specif_text)) {
               $specif_text=" ,".$specif_text." ";
            }

            $linktable = getTableForItemType('Computer_'.$itemtype);
            $fk        = getForeignKeyFieldForTable(getTableForItemType($itemtype));

            $query = "SELECT COUNT(*) AS nb,
                             `$fk`
                      FROM `$linktable`
                      WHERE `computers_id` = '$ID'
                      GROUP BY `$fk`";

            foreach ($DB->request($query) as $deviceFromSQL) {
               $current_row = $table_group->createRow();
               $device_group = $itemtype.mt_rand();

               $current_row->setHTMLID($device_group);

               if ($device->getFromDB($deviceFromSQL[$fk])) {
                  if ($device->canView()) {
                     $cell_value = "<a href='".$device->getSearchURL()."'>" .
                        $device->getTypeName(1).
                        "</a>";
                  } else {
                     $cell_value = $device->getTypeName(1);
                  }

                  $cell_value = $device->getLink();
                  if ($canedit) {
                     $field_name = "quantity_".$itemtype."_".$ID."_".$device->getID();
                     $cell_value .= "&nbsp;<img title='"._sx('button', 'Add')."' alt='".
                        _sx('button', 'Add')."'
                                 onClick=\"Ext.get('$field_name').setDisplayed('block')\"
                                 class='pointer' src='".$CFG_GLPI["root_doc"].
                        "/pics/add_dropdown.png'>";
                     $cell_value .= "<span id='$field_name' ".
                        "style='display:none'><br>";
                     $cell_value .= __('Add');
                     $cell_value .= "&nbsp;";
                     $cell_value = array($cell_value,
                                         array('function' => 'Dropdown::showInteger',
                                               'parameters' => array($field_name, 0, 0, 10)),
                                         "</span>");
                  }
                  $name_cell = $current_row->addCell($name_column, $cell_value, NULL,
                                                     $device->getID());

                  if ($canedit) {
                     $cell_value = "<a href='#' onclick= \"if ( toggleCheckboxes('$device_group')".
                        ") return false;\">".__('All')."</a>";
                     $global_anchor = $current_row->addCell($delete_all, $cell_value, $name_cell);
                     $global_anchor->setHTMLStyle('text-align: center;');
                  } else {
                     $global_anchor = $name_cell;
                  }

                  $specificities = $device->getFormData();

                  $device->getHTMLTableCellsForComputer_Device($current_row, $device_chars,
                                                               $global_anchor);

                  $links_specifications = array();
                  $query = "SELECT `id`,
                                   `$fk`
                                   $specif_text
                            FROM `$linktable`
                            WHERE `computers_id` = '$ID'
                                  AND `$fk` = '".$device->getID()."'
                            ORDER BY `id`";

                  foreach ($DB->request($query) as $data) {

                     if ($canedit) {
                        $cell_value = "<input id='$device_group' type='checkbox' name='remove_" .
                                      $itemtype."_".$data['id']."' value='1'>";
                        $local_anchor = $current_row->addCell($delete_one, $cell_value,
                                                              $global_anchor);
                        $local_anchor->setHTMLStyle('text-align: center;');
                     } else {
                        $local_anchor = $global_anchor;
                     }

                     if (isset($data['specificity'])) {
                        if ($canedit) {
                           // Specificity
                           $cell_value = "<input type='text' name='value_" . $itemtype . "_" .
                              $data['id'] . "' value='" . $data['specificity'] .
                              "' size='".$specificities['size']."'>";
                        } else {
                           $cell_value = $data['specificity'];
                        }
                        $link_spec = $current_row->addCell($link_char,
                                                           $cell_value,
                                                           $local_anchor);
                        $link_spec->setHTMLStyle('text-align: center;');
                     }
                  }
               }
            }
         }
      }

      $table->display();

      if ($canedit) {

         Html::openArrowMassives("form_device_action$rand", false);
         Html::closeArrowMassives(array());


         echo __('Add a new component')." - ";
         Dropdown::showAllItems('items_id', '', 0, -1, $devtypes);

         echo "<input type='submit' class='submit' name='updateall' value='" . __s('Save')."'>";

         echo "</form>";
      }

      echo "</div>";
   }


   /**
    * \brief Remove one link between a computer and a device
    * For instance, usefull to remove one network card of some type from the computer
    *
    * @since version 0.84
    *
    * @param $itemtype     the type of the device to remove
    * @param $compDevID    the id of the link between the computer and the device
   **/
   private function removeDevice($itemtype, $compDevID) {
      global $DB;

      $linktable = getTableForItemType('Computer_'.$itemtype);

      $query = "DELETE
                FROM `$linktable`
                WHERE `id` = '$compDevID'";

      $DB->query($query);
   }


   /**
    * \brief Remove all links between a computer and a device.
    * For instance, remove all network cards of some type from a computer
    *
    * @since version 0.84
    *
    * @param $itemtype  the type of the device to remove
    * @param $devID     the id of the device to remove from the computer
   **/
   private function removeDevices($itemtype, $devID) {
      global $DB;

      $linktable = getTableForItemType('Computer_'.$itemtype);
      $fk        = getForeignKeyFieldForTable(getTableForItemType($itemtype));

      $query = "DELETE
                FROM `$linktable`
                WHERE `$fk` = '$devID'
                      AND `computers_id` = '".$this->fields["computers_id"]."'";

      $DB->query($query);
   }


   /**
    * Add one or more link to a given device
    *
    * @since version 0.84
    *
    * @param $numberToAdd  number of links to add
    * @param $itemtype     itemtype of device
    * @param $compDevID    computer device ID
   **/
   private function addDevices($numberToAdd, $itemtype, $computers_id, $devices_id) {
      global $DB;


      $linktable = getTableForItemType('Computer_'.$itemtype);
      $this->forceTable($linktable);
      $fk        = getForeignKeyFieldForTable(getTableForItemType($itemtype));
      // Force table for link
      if ($item = getItemForItemtype($itemtype)) {
         $specif_fields = $item->getSpecifityLabel();

         $device = new $itemtype();
         if (!$device->getFromDB($devices_id)) {
            return false;
         }

         $input = array('computers_id' => $computers_id,
                        '_itemtype'    => $itemtype,
                        $fk            => $devices_id,
                        'specificity'  => $device->getField('specif_default'));

         for ($i = 0 ; $i < $numberToAdd ; $i ++) {
            $this->add($input);
         }
      }
   }



   /**
    * Update an internal device specificity
    *
    * @param $newValue     new specifity value
    * @param $itemtype     itemtype of device
    * @param $compDevID    computer device ID
   **/
   private function updateSpecificity($newValue, $itemtype,$compDevID) {
      global $DB;

      if ($item = getItemForItemtype($itemtype)) {
         $specif_fields = $item->getSpecifityLabel();

         // No specificity for this device type
         if (count($specif_fields) == 0) {
            return false;
         }
      }

      $linktable = getTableForItemType('Computer_'.$itemtype);
      $this->forceTable($linktable);
      $fk        = getForeignKeyFieldForTable(getTableForItemType($itemtype));

      if (!$this->getFromDB($compDevID)) {
         return false;
      }

      // Is it a real change ?
      if (addslashes($this->fields['specificity']) == $newValue) {
         return false;
      }

      $data = array('id'          => $compDevID,
                    'specificity' => $newValue,
                    '_itemtype'   => $itemtype);
      $this->update($data, true);
   }


   /**
    * Update the device attached to a computer
    *
    * @param $input array of datas from the input form
   **/
   function updateAll(array $input) {

      if ((!empty($input['itemtype'])) && (!empty($input['items_id']))) {
         $this->addDevices(1, $input['itemtype'], $input['computers_id'], $input['items_id']);
      }


      // Update quantity
      foreach ($input as $key => $val) {
         $data = explode("_",$key);
         if (count($data) == 3) {
            switch ($data[0]) {
               case 'value' :
                  $this->updateSpecificity($val,$data[1],$data[2]);
                  break;

               case 'remove' :
                  $this->removeDevice($data[1], $data[2]);
                  break;

               case 'removeall' :
                  $this->removeDevices($data[1], $data[2]);
                  break;
            }
         } elseif (count($data) == 4) {
            switch ($data[0]) {
               case 'quantity' :
                  $this->addDevices($val, $data[1],$data[2],$data[3]);
                  break;

            }
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
               $data['_itemtype']   = $type;
               $this->delete($data);
            }
         }

      } else {
         $linktable = getTableForItemType('Computer_'.$itemtype);
         $fk        = getForeignKeyFieldForTable(getTableForItemType($itemtype));
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
    *
    * @param $oldid
    * @param $newid
   **/
   function cloneComputer ($oldid, $newid) {
      global $DB;

      $devtypes = self::getDeviceTypes();
      foreach ($devtypes as $itemtype) {
         $linktable = getTableForItemType('Computer_'.$itemtype);
         $fk        = getForeignKeyFieldForTable(getTableForItemType($itemtype));

         $query = "SELECT *
                   FROM `$linktable`
                   WHERE `computers_id` = '$oldid'";

         foreach ($DB->request($query) as $data) {
            unset($data['id']);
            $data['computers_id'] = $newid;
            $data['_itemtype']    = $itemtype;
            $data['_no_history']  = true;

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

      if ($input['_itemtype'] != 'DeviceMemory'
          && isset($this->fields['specificity'])
          && $this->fields['specificity'] == $this->input['specificity']) {
         // No change
         return false;
      }

      //For memories, type can change even if specificity not
      if ($input['_itemtype'] == 'DeviceMemory'
          && (isset($this->fields['specificity'])
              && $this->fields['specificity'] == $this->input['specificity'])
          && (isset($this->fields['devicememories_id'])
              && (!isset($this->input['devicememories_id'])
                  || $this->fields['devicememories_id'] == $this->input['devicememories_id']))) {
         // No change
         return false;
      }

      $linktable = getTableForItemType('Computer_'.$input['_itemtype']);
      $this->forceTable($linktable);

      return $this->input;
   }


   /**
    * Delete old devices settings
    *
    * @param $glpi_computers_id  integer : glpi computer id.
    * @param $itemtype           integer : device type identifier.
    *
    * @return nothing.
   **/
   static function resetDevices($glpi_computers_id, $itemtype) {
      global $DB;

      $linktable = getTableForItemType('Computer_'.$itemtype);

      $query = "DELETE
                FROM `$linktable`
                WHERE `computers_id` = '$glpi_computers_id'";
      $DB->query($query);
   }


   /**
    * Select a device from its link (device<->computer) id
    *
    * @since version 0.84
    *
    * @param $deviceType   the device type
    * @param $compDevID    the link ID
   **/
   function getDeviceFromComputerDeviceID($deviceType, $compDevID) {

      $linktable = getTableForItemType('Computer_'.$deviceType);
      $this->forceTable($linktable);

      if ($this->can($compDevID,'r')) {
         if ($device = getItemForItemtype($deviceType)) {
            if (isset($this->fields[$device->getForeignKeyField()])
                && $device->can($this->fields[$device->getForeignKeyField()], 'r')) {
               return $device;
            }
         }
      }
      return false;
   }

}
?>
