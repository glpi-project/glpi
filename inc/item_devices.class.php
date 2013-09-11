<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

   static public $itemtype_1            = 'itemtype';
   static public $items_id_1            = 'items_id';
   static public $mustBeAttached_1      = false;

   static public $checkItem_2_Rights    = self::DONT_CHECK_ITEM_RIGHTS;

   static protected $notable            = true;

   static public $logs_for_item_2       = false;

   static public $log_history_1_add     = Log::HISTORY_ADD_DEVICE;
   static public $log_history_1_update  = Log::HISTORY_UPDATE_DEVICE;
   static public $log_history_1_delete  = Log::HISTORY_DELETE_DEVICE;
   static public $log_history_1_lock    = Log::HISTORY_LOCK_DEVICE;
   static public $log_history_1_unlock  = Log::HISTORY_UNLOCK_DEVICE;

   static $rightname = 'device';


   static function getTypeName($nb=0) {

      $device_type = static::getDeviceType();
      //TRANS: %s is the type of the component
      return sprintf(__('Item - %s link'), $device_type::getTypeName($nb));

   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getForbiddenStandardMassiveAction()
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();

      if (count(static::getSpecificities()) == 0) {
         $forbidden[] = 'MassiveAction'.MassiveAction::CLASS_ACTION_SEPARATOR.'update';
      }

      return $forbidden;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBRelation::getSearchOptions()
   **/
   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      foreach (static::getSpecificities() as $field => $attributs) {
         $tab[] = array('table'         => $this->getTable(),
                        'field'         => $field,
                        'name'          => $attributs['long name'],
                        'massiveaction' => true);
      }

      return $tab;
   }


   /**
    * Get the specificities of the given device. For instance, the
    * serial number, the size of the memory, the frequency of the CPUs ...
    *
    * Should be overloaded by Item_Device*
    *
    * @return array of the specificities: index is the field name and the values are the attributs
    *                                     of the specificity (long name, short name, size)
   **/
   static function getSpecificities() {
      return array();
   }


   /**
    * Get the items on which this Item_Device can be attached. For instance, a computer can have
    * any kind of device. Conversely, a soundcard does not concern a NetworkEquipment
    *
    * Should be overloaded by Item_Device*
    *
    * @since version 0.85
    *
    * @return array of the itemtype that can have this Item_Device
   **/
   static function itemAffinity() {
      return array('Computer');
   }


   /**
    * Get all the kind of devices available inside the system.
    * This method is equivalent to getItemAffinities('')
    * TODO: allow any plugin to include its own kind of device !
    *
    * @return array of the types of Item_Device* available
   **/
   static function getDeviceTypes() {
      global $CFG_GLPI;

      return $CFG_GLPI['items_that_owns_devices'];
   }


   /**
    * Get the Item_Device* a given item type can have
    *
    * @param $itemtype the type of the item that we want to know its devices
    *
    * @since version 0.85
    *
    * @return array of Item_Device*
   **/
   static function getItemAffinities($itemtype) {

      if (!isset($_SESSION['glpi_item_device_affinities'])) {
         $_SESSION['glpi_item_device_affinities'] = array('' => static ::getDeviceTypes());
      }

      if (!isset($_SESSION['glpi_item_device_affinities'][$itemtype])) {
         $afffinities = array();
         foreach ($_SESSION['glpi_item_device_affinities'][''] as $item_id => $item_device) {
            if (in_array($itemtype, $item_device::itemAffinity())) {
               $afffinities[$item_id] = $item_device;
            }
         }
         $_SESSION['glpi_item_device_affinities'][$itemtype] = $afffinities;
      }

      return $_SESSION['glpi_item_device_affinities'][$itemtype];
   }


   /**
    * Get all kind of items that can be used by Item_Device*
    *
    * @since version 0.85
    *
    * @return array of the available items
   **/
   static function getConcernedItems() {
      return array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer');
   }


   static function getDeviceType() {
      return str_replace ('Item_', '', get_called_class());
   }


   static function cloneItem($itemtype, $oldid, $newid) {
      global $DB;

      // TODO: check what to do regarding templates
      foreach (self::getItemAffinities($itemtype) as $link_type) {
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
         if (in_array($item->getType(), self::getConcernedItems())) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = 0;
               foreach (self::getItemAffinities($item->getType()) as $link_type) {
                  $nb   += countElementsInTable($link_type::getTable(),
                                                "`items_id` = '".$item->getID()."'
                                                   AND `itemtype` = '".$item->getType()."'
                                                   AND `is_deleted` = '0'");
               }
            }
            if (isset($nb)) {
               return self::createTabEntry(_n('Component', 'Components', $nb), $nb);
            }
            return _n('Component', 'Components', 2);
         }
         if ($item instanceof CommonDevice) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $deviceClass     = $item->getType();
               $linkClass       = "Item_$deviceClass";
               $table           = $linkClass::getTable();
               $foreignkeyField = $deviceClass::getForeignKeyField();
               $nb = countElementsInTable($table,
                                          "`$foreignkeyField` = '".$item->getID()."'
                                            AND `is_deleted` = '0'");
            }
            if (isset($nb)) {
               return self::createTabEntry(_n('Item', 'Items', $nb), $nb);
            }
            return _n('Item', 'Items', 2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForItem($item, $withtemplate);
      return true;
   }


   static function showForItem(CommonGLPI $item, $withtemplate=0) {
      global $CFG_GLPI;

      $is_device = ($item instanceof CommonDevice);

      $ID = $item->getField('id');

      if (!$item->can($ID, READ)) {
         return false;
      }
      $canedit = (($withtemplate != 2) && $item->canEdit($ID));
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
                                                Html::getCheckAllAsCheckbox("form_device_action$rand",
                                                '__RAND__'));
         $delete_all_column->setHTMLClass('center');
      } else {
         $delete_all_column = NULL;
      }

      $column_label    = ($is_device ? _n('Item', 'Items', 2) : __('Type of component'));
      $common_column   = $table->addHeader('common', $column_label);
      $specific_column = $table->addHeader('specificities', __('Specificities'));
      $specific_column->setHTMLClass('center');

      $dynamic_column = '';
      if ($item->isDynamic()) {
         $dynamic_column = $table->addHeader('is_dynamic', __('Automatic inventory'));
         $dynamic_column->setHTMLClass('center');
      }

      if ($canedit) {
         $massiveactionparams = array('container'     => 'form_device_action'.$rand,
                                      'fixed'         => false,
                                      'display_arrow' => false);
         $content = array(array('function'   => 'Html::showMassiveActions',
                                'parameters' => array($massiveactionparams)));
         $delete_column = $table->addHeader('delete one', $content);
         $delete_column->setHTMLClass('center');
      } else {
         $delete_column = NULL;
      }

      $table_options = array('canedit' => $canedit,
                             'rand'    => $rand);

      if ($is_device) {
         foreach (array_merge(array(''), self::getConcernedItems()) as $itemtype) {
            $table_options['itemtype'] = $itemtype;
            static::getTableGroup($item, $table, $table_options, $delete_all_column,
                                  $common_column, $specific_column, $delete_column,
                                  $dynamic_column);
         }
      } else {
         $devtypes = array();
         foreach (self::getItemAffinities($item->getType()) as $link_type) {
            $devtypes [] = $link_type::getDeviceType();
            $link_type::getTableGroup($item, $table, $table_options, $delete_all_column,
                                      $common_column, $specific_column, $delete_column,
                                      $dynamic_column);
         }
      }

      if ($canedit) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><td>";
         echo __('Add a new component')."</td><td class=left width='70%'>";
         if ($is_device) {
            Dropdown::showNumber('number_devices_to_add', array('value' => 0,
                                                                'min'   => 0,
                                                                'max'   => 10));
         } else {
            Dropdown::showSelectItemFromItemtypes(array('itemtype_name'       => 'devicetype',
                                                        'items_id_name'       => 'devices_id',
                                                        'itemtypes'           => $devtypes,
                                                        'showItemSpecificity' => $CFG_GLPI['root_doc']
                                                                 .'/ajax/getUnaffectedItemDevice.php'));
         }
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


   /**
    * Get the group of elements regarding given item.
    * Two kind of item :
    *              - Device* feed by a link to the attached item (Computer, Printer ...)
    *              - Computer, Printer ...: feed by the "global" properties of the CommonDevice
    * Then feed with the specificities of the Item_Device elements
    * In cas of $item is an instance, then $options contains the type of the item (Computer,
    * Printer ...).
    *
    * @param $item
    * @param $table
    * @param $options            array
    * @param $delete_all_column
    * @param $common_column
    * @param $specific_column
    * @param $delete_column
    * @param $dynamic_column
   **/
   static function getTableGroup(CommonDBTM $item, HTMLTableMain $table, array $options,
                                 HTMLTableSuperHeader $delete_all_column = NULL,
                                 HTMLTableSuperHeader $common_column,
                                 HTMLTableSuperHeader $specific_column,
                                 HTMLTableSuperHeader $delete_column = NULL,
                                 $dynamic_column) {
      global $DB;

      $is_device = ($item instanceof CommonDevice);

      if ($is_device) {
         $peer_type = $options['itemtype'];

         if (empty($peer_type)) {
            $column_label = __('Dissociated devices');
            $group_name   = 'None';
         } else {
            $column_label = $peer_type::getTypeName(2);
            $group_name   = $peer_type;
         }

         $table_group = $table->createGroup($group_name, '');

         $peer_column = $table_group->addHeader('item', $column_label, $common_column, NULL);

         if (!empty($peer_type)) {
            //TRANS : %1$s is the type of the device
            //        %2$s is the type of the item
            //        %3$s is the name of the item (used for headings of a list),
            $itemtype_nav_title = sprintf(__('%1$s of %2$s: %3$s'), $peer_type::getTypeName(2),
                                          $item->getTypeName(1), $item->getName());
            $peer_column->setItemType($peer_type, $itemtype_nav_title);
         }

      } else {
         $peer_type   = static::getDeviceType();

         $table_group = $table->createGroup($peer_type, '');

         //TRANS : %1$s is the type of the device
         //        %2$s is the type of the item
         //        %3$s is the name of the item (used for headings of a list),
         $options['itemtype_title'] = sprintf(__('%1$s of %2$s: %3$s'), $peer_type::getTypeName(2),
                                              $item->getTypeName(1), $item->getName());

         $peer_type::getHTMLTableHeader($item->getType(), $table_group, $common_column, NULL,
                                          $options);
      }

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
         $group_checkbox_tag =  (empty($peer_type) ? '__' : $peer_type);
         $content = Html::getCheckbox(array('criterion'
                                               => array('tag_for_massive' => $group_checkbox_tag)));
         $delete_one  = $table_group->addHeader('one', $content, $delete_column, $previous_column);
      }

      if ($is_device) {
         $fk = 'items_id';

         $query = "SELECT *
                   FROM `".static::getTable()."`
                   WHERE `".static::getDeviceForeignKey()."` = '".$item->getID()."'
                         AND `itemtype` = '$peer_type'
                         AND `is_deleted` = '0'
                   ORDER BY `itemtype`, `$fk`";
      } else {
         $fk = static::getDeviceForeignKey();

         $query = "SELECT *
                   FROM `".static::getTable()."`
                   WHERE `itemtype` = '".$item->getType()."'
                         AND `items_id` = '".$item->getID()."'
                         AND `is_deleted` = '0'
                   ORDER BY $fk";

      }

      if (!empty($peer_type)) {
         $peer = new $peer_type();
         $peer->getEmpty();
      } else {
         $peer = NULL;
      }
      foreach ($DB->request($query) as $link) {
         if ((is_null($peer)) || ($link[$fk] != $peer->getID())) {

            if ($peer instanceof CommonDBTM) {
               $peer->getFromDB($link[$fk]);
            }

            $current_row  = $table_group->createRow();
            $peer_group = $peer_type.'_'.$link[$fk].'_'.mt_rand();
            $current_row->setHTMLID($peer_group);

            if ($options['canedit']) {
               $cell_value = Html::getCheckAllAsCheckbox($peer_group);
               $current_row->addCell($delete_all_column, $cell_value);
            }

            if ($is_device) {
               $cell = $current_row->addCell($peer_column, ($peer ? $peer->getLink() : __('None')),
                                             NULL, $peer);
               if (is_null($peer)) {
                  $cell->setHTMLClass('center');
               }
            } else {
               $peer->getHTMLTableCellForItem($current_row, $item, NULL, $options);
            }

         }

         $spec_cell = NULL;
         foreach (static::getSpecificities() as $field => $attributs) {
            $content = $link[$field];
            if ($options['canedit']) {
               $content = "<input type='text' name='value_" . $peer_type . "_".$link['id']."_" .
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
            $cell_value = Html::getMassiveActionCheckBox(static::getType(), $link['id'],
                                                         array('massive_tags' => $group_checkbox_tag));
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

      $this->check(-1, CREATE, $input);

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
    * Add one or several device(s) from front/item_devices.form.php.
    *
    * @param $input array of input: should be $_POST
    *
    * @since version 0.85
   **/
   static function addDevicesFromPOST($input) {

      if (!isset($input['itemtype'])
          || !isset($input['items_id'])) {
         Html::displayNotFoundError();
      }

      if (isset($input['devicetype'])) {
         $linktype = 'Item_'.$input['devicetype'];
         if ($link = getItemForItemtype($linktype)) {
            if ((isset($input[$linktype::getForeignKeyField()]))
                && (count($input[$linktype::getForeignKeyField()]))) {
               $update_input = array('itemtype' => $input['itemtype'],
                                     'items_id' => $input['items_id']);
               foreach ($input[$linktype::getForeignKeyField()] as $id) {
                  $update_input['id'] = $id;
                  $link->update($update_input);
               }
            } else {
               $link->addDevices(1, $input['itemtype'], $input['items_id'], $input['devices_id']);
            }
         }
      } else {
         if (!$item = getItemForItemtype($input['itemtype'])) {
            Html::displayNotFoundError();
         }
         if ($item instanceof CommonDevice) {
            if ($link = getItemForItemtype('Item_'.$item->getType())) {
               $link->addDevices($input['number_devices_to_add'], '', 0, $input['items_id']);
            }
         }
      }
   }


   /**
    * @param $input array of input: should be $_POST
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
      $item->check($input['items_id'], UPDATE, $_POST);

      $is_device = ($item instanceof CommonDevice);
      if ($is_device) {
         $link_type = 'Item_'.$itemtype;
      }

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
         if (!$is_device) {
            if (!empty($data[1])
                && in_array('Item_'.$data[1], self::getItemAffinities($itemtype))) {
               $link_type = 'Item_'.$data[1];
            } else {
               continue;
            }
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
    * @since version 0.85
    *
    * @param $item_devices_id
    * @param $items_id
    * @param $itemtype
    *
    * @return boolean
   **/
   static function affectItem_Device($item_devices_id, $items_id, $itemtype) {

      $link = new static();
      return $link->update(array('id'       => $item_devices_id,
                                 'items_id' => $items_id,
                                 'itemtype' => $itemtype));
   }


   /**
    * @param $itemtype
    * @param $items_id
   **/
   static function cleanItemDeviceDBOnItemDelete($itemtype, $items_id, $unaffect) {
      global $DB;

      foreach (self::getItemAffinities($itemtype) as $link_type) {
         $link = getItemForItemtype($link_type);
         if ($link) {
            if ($unaffect) {
               $query = "SELECT `id`
                         FROM `".$link->getTable()."`
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` = '$items_id'";
               $input = array('items_id' => 0,
                              'itemtype' => '');
               foreach ($DB->request($query) as $data) {
                  $input['id'] = $data['id'];
                  $link->update($input);
               }
            } else {
               $link->cleanDBOnItemDelete($itemtype, $items_id);
            }
         }
      }
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[READ]);
      return $values;
   }
}
?>
