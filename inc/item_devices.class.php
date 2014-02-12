<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
* @since version 0.84
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Relation between item and devices
 * We completely relies on CommonDBConnexity to manage the can* and the history and the deletion ...
**/
class Item_Devices extends CommonDBRelation {

   static public $itemtype_1 = 'itemtype'; // Type ref or field name (must start with itemtype)
   static public $items_id_1 = 'items_id'; // Field name

   static public $checkItem_2_Rights    = self::DONT_CHECK_ITEM_RIGHTS;

   static protected $notable            = true;

   static public $logs_for_item_2       = false;

   static public $log_history_1_add     = Log::HISTORY_ADD_DEVICE;
   static public $log_history_1_update  = Log::HISTORY_UPDATE_DEVICE;
   static public $log_history_1_delete  = Log::HISTORY_DELETE_DEVICE;
   static public $log_history_1_lock    = Log::HISTORY_LOCK_DEVICE;
   static public $log_history_1_unlock  = Log::HISTORY_UNLOCK_DEVICE;


   static function getSpecificities() {
      return array();
   }


   /**
    * Get itemtype of devices
    * @todo : Think of allowing other kind of devices such as for NetworkPort Instantation types
   **/
   static function getDeviceTypes() {

      return array(1  => 'Item_DeviceMotherboard', 2  => 'Item_DeviceProcessor',
                   3  => 'Item_DeviceMemory',      4  => 'Item_DeviceHardDrive',
                   5  => 'Item_DeviceNetworkCard', 6  => 'Item_DeviceDrive',
                   7  => 'Item_DeviceControl',     8  => 'Item_DeviceGraphicCard',
                   9  => 'Item_DeviceSoundCard',   10 => 'Item_DevicePci',
                   11 => 'Item_DeviceCase',        12 => 'Item_DevicePowerSupply');
   }


   static function getDeviceType() {
      return str_replace ('Item_', '', get_called_class());
   }


   static function cloneItem($itemtype, $oldid, $newid) {
      global $DB;

      foreach (self::getDeviceTypes() as $link_type) {
         $query = "SELECT *
                   FROM `".$link_type::getTable()."`
                   WHERE `itemtype` = '$itemtype'
                         AND `items_id` = '$oldid'";

         $result_iterator = $DB->request($query);
         if ($result_iterator->numrows() > 0) {
            $link = new $link_type();
            foreach ($result_iterator as $data) {
               unset($data['id']);
               $data['items_id']     = $newid;
               $data['_itemtype']    = $itemtype;
               $data['_no_history']  = true;
               $data                 = Toolbox::addslashes_deep($data);

               $link->add($data);
             }
         }
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->canView()) {
         switch ($item->getType()) {
            case 'Computer' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = 0;
                  foreach (self::getDeviceTypes() as $link_type) {
                     $nb   += countElementsInTable($link_type::getTable(),
                                                   "`items_id` = '".$item->getID()."'
                                                      AND `itemtype` = '".$item->getType()."'
                                                      AND `is_deleted`='0'");
                  }
               }
               if (isset($nb)) {
                  return self::createTabEntry(_n('Component', 'Components', 2), $nb);
               }
               return _n('Component', 'Components', 2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForItem($item, 1, $withtemplate);
      return true;
   }


   static function showForItem(CommonGLPI $item, $withtemplate=0) {

      $ID = $item->getField('id');

      if (!$item->can($ID, 'r')) {
         return false;
      }
      $canedit = (($withtemplate != 2) && $item->can($ID, 'w'));

      echo "<div class='spaced'>";
      $rand = mt_rand();
      if ($canedit) {
         echo "\n<form id='form_device_action$rand' name='form_device_action$rand'
                  action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>\n";
         echo "\t<input type='hidden' name='items_id' value='$ID'>\n";
         echo "\t<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
      }

      $table = new HTMLTableMain();

      $table->setTitle(_n('Component', 'Components', 2));

      if ($canedit) {
         $delete_all_column = $table->addHeader('delete all',
                                                Html::getCheckAllAsCheckbox("form_device_action$rand", '__RAND__'));
         $delete_all_column->setHTMLClass('center');
      } else {
         $delete_all_column = NULL;
      }

      $common_column   = $table->addHeader('common', __('Type of component'));
      $specific_column = $table->addHeader('specificities', __('Specificities'));
      $specific_column->setHTMLClass('center');

      $dynamic_column = '';
      if ($item->isDynamic()) {
         $dynamic_column = $table->addHeader('is_dynamic', __('Automatic inventory'));
         $dynamic_column->setHTMLClass('center');
      }

      if ($canedit) {
         $content       = "<input type='submit' class='submit' name='delete' value='".
                            _sx('button', 'Delete permanently')."'>";
         $delete_column = $table->addHeader('delete one', $content);
         $delete_column->setHTMLClass('center');
      } else {
         $delete_column = NULL;
      }

      $table_options = array('canedit' => $canedit);

      $devtypes          = array();
      foreach (self::getDeviceTypes() as $link_type) {
         $devtypes [] = $link_type::getDeviceType();
         $link_type::getTableGroup($item, $table, $table_options, $delete_all_column,
                                   $common_column, $specific_column, $delete_column,
                                   $dynamic_column);
      }

      if ($canedit) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><td>";
         echo __('Add a new component')."</td><td class=left width='70%'>";
         Dropdown::showAllItems('devices_id', '', 0, -1, $devtypes, false, false, 'devicetype');
         echo "</td><td>";
         echo "<input type='submit' class='submit' name='add' value='"._sx('button', 'Add')."'>";
         echo "</td></tr></table>";
      }

      $table->display(array('display_super_for_each_group' => false,
                            'display_title_for_each_group' => false));

      if ($canedit) {
         echo "<input type='submit' class='submit' name='updateall' value='" .
              _sx('button', 'Save')."'>";

         Html::closeForm();
      }

      echo "</div>";

   }


   static function getDeviceForeignKey() {
      return getForeignKeyFieldForTable(getTableForItemType(static::getDeviceType()));
   }


   static function getTableGroup(CommonDBTM $item, HTMLTableMain $table, array $options,
                                 HTMLTableSuperHeader $delete_all_column = NULL,
                                 HTMLTableSuperHeader $common_column,
                                 HTMLTableSuperHeader $specific_column,
                                 HTMLTableSuperHeader $delete_column = NULL,
                                 $dynamic_column) {
      global $DB;

      $device_type = static::getDeviceType();

      $table_group = $table->createGroup($device_type, '');

      //TRANS : %1$s is the type of the device
      //        %2$s is the type of the item
      //        %3$s is the name of the item (used for headings of a list),
      $options['itemtype_title'] = sprintf(__('%1$s of %2$s: %3$s'), $device_type::getTypeName(2),
                                           $item->getTypeName(1), $item->getName());

      $device_type::getHTMLTableHeader($item->getType(), $table_group, $common_column, NULL,
                                       $options);

      $spec_column         = NULL;
      $specificity_columns = array();
      foreach (static::getSpecificities() as $field => $attributs) {
         $spec_column = $table_group->addHeader('spec_'.$field, $attributs['long name'],
                                                $specific_column, $spec_column);
         $specificity_columns[$field] = $spec_column;
      }

      if ($item->isDynamic()) {
         $dynamics_column = $table_group->addHeader('one', '&nbsp;', $dynamic_column,
                                                    $spec_column);
         $previous_column = $dynamics_column;
      } else {
         $previous_column = $spec_column;
      }

      if ($options['canedit']) {
         $delete_one  = $table_group->addHeader('one', '&nbsp;', $delete_column, $previous_column);
      }

      $fk = static::getDeviceForeignKey();

      $query = "SELECT *
                FROM `".static::getTable()."`
                WHERE `itemtype` = '".$item->getType()."'
                      AND `items_id` = '".$item->getID()."'
                      AND `is_deleted` = '0'
                ORDER BY $fk";

      $device = new $device_type();
      $device->getEmpty();
      foreach ($DB->request($query) as $link) {
         if ($link[$fk] != $device->getID()) {

            $device->getFromDB($link[$fk]);

            $current_row  = $table_group->createRow();
            $device_group = $device_type.'_'.$link[$fk].'_'.mt_rand();
            $current_row->setHTMLID($device_group);

            if ($options['canedit']) {
               $cell_value = Html::getCheckAllAsCheckbox($device_group);
               $current_row->addCell($delete_all_column, $cell_value);
            }

            $device->getHTMLTableCellForItem($current_row, $item, NULL, $options);

         }

         $spec_cell = NULL;
         foreach (static::getSpecificities() as $field => $attributs) {
            $content = $link[$field];
            if ($options['canedit']) {
               $content = "<input type='text' name='value_" . $device_type . "_".$link['id']."_" .
                            $field . "' value='$content' size='".$attributs['size']."'>";
            }
            $spec_cell = $current_row->addCell($specificity_columns[$field], $content, $spec_cell);
         }

         if ($item->isDynamic()) {
            $previous_cell = $current_row->addCell($dynamics_column,
                                                   Dropdown::getYesNo($link['is_dynamic']),
                                                   $spec_cell);
         } else {
            $previous_cell = $spec_cell;
         }

         if ($options['canedit']) {
            $cell_value   = "<input type='checkbox' name='remove_" . $device_type . "_" .
                              $link['id'] . "' value='1'>";
            $current_row->addCell($delete_one, $cell_value, $previous_cell);
         }
      }
   }


   /**
    * @param $numberToAdd
    * @param $itemtype
    * @param $items_id
    * @param $devices_id
   **/
   function addDevices($numberToAdd, $itemtype, $items_id, $devices_id) {
      global $DB;

      if ($numberToAdd == 0) {
         return;
      }

      $input = array('itemtype'                    => $itemtype,
                     'items_id'                    => $items_id,
                     static::getDeviceForeignKey() => $devices_id);

      $this->check(-1, 'w', $input);

      $device_type = static::getDeviceType();
      $device      = new $device_type();
      $device->getFromDB($devices_id);

      foreach (static::getSpecificities() as $field => $attributs) {
         if (isset($device->fields[$field.'_default'])) {
            $input[$field] = $device->fields[$field.'_default'];
         }
      }

      for ($i = 0 ; $i < $numberToAdd ; $i ++) {
         $this->add($input);
      }
   }


   /**
    * @param $input
    * @param $delete
   **/
   static function updateAll($input, $delete) {

      if (!isset($input['itemtype'])
          || !isset($input['items_id'])) {
         Html::displayNotFoundError();
      }

      $itemtype = $input['itemtype'];
      $items_id = $input['items_id'];
      if (!$item = getItemForItemtype($itemtype)) {
         Html::displayNotFoundError();
      }
      $item->check($input['items_id'], 'w', $_POST);

      $links   = array();
      // Update quantity
      $device_type = '';
      foreach ($input as $key => $val) {
         $data = explode("_",$key);
         if (!empty($data[0])) {
            $command = $data[0];
         } else {
            continue;
         }
         if (!empty($data[1])
             && in_array('Item_'.$data[1], self::getDeviceTypes())) {
            $link_type = 'Item_'.$data[1];
         } else {
            continue;
         }
         if (!empty($data[2])) {
            $links_id = $data[2];
         } else {
            continue;
         }
         if (!isset($links[$link_type])) {
            $links[$link_type] = array('add'    => array(),
                                       'update' => array(),
                                       'remove' => array());
         }

         switch ($command) {
            case 'quantity' :
               $links[$link_type]['add'][$links_id] = $val;
               break;

            case 'value' :
               if (!isset($links[$link_type]['update'][$links_id])) {
                  $links[$link_type]['update'][$links_id] = array();
               }
               if (isset($data[3])) {
                  $links[$link_type]['update'][$links_id][$data[3]] = $val;
               }
               break;

            case 'remove' :
               if ($val == 1) {
                  $links[$link_type]['remove'][] = $links_id;
               }
               break;
         }
      }

      foreach ($links as $type => $commands) {
         if ($link = getItemForItemtype($type)) {
            if ($delete) {
               foreach ($commands['remove'] as $link_to_remove) {
                  $link->delete(array('id' => $link_to_remove));
               }
            } else {
               foreach ($commands['add'] as $link_to_add => $number) {
                  $link->addDevices($number, $itemtype, $items_id, $link_to_add);
               }
               foreach ($commands['update'] as $link_to_update => $input) {
                  $input['id'] = $link_to_update;
                  $link->update($input);
               }
            }
            unset($link);
         }
      }

   }


   /**
    * @param $itemtype
    * @param $items_id
   **/
   static function cleanItemDeviceDBOnItemDelete($itemtype, $items_id) {

      foreach (self::getDeviceTypes() as $link_type) {
         $link = getItemForItemtype($link_type);
         if ($link) {
            $link->cleanDBOnItemDelete($itemtype, $items_id);
         }
      }
   }

}
?>
