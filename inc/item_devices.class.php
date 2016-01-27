<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
   static public $take_entity_1         = false ;
//    static public $checkItem_1_Rights    = self::DONT_CHECK_ITEM_RIGHTS;

   static protected $notable            = true;

   static public $logs_for_item_2       = false;
   static public $take_entity_2         = true ;

   static public $log_history_1_add     = Log::HISTORY_ADD_DEVICE;
   static public $log_history_1_update  = Log::HISTORY_UPDATE_DEVICE;
   static public $log_history_1_delete  = Log::HISTORY_DELETE_DEVICE;
   static public $log_history_1_lock    = Log::HISTORY_LOCK_DEVICE;
   static public $log_history_1_unlock  = Log::HISTORY_UNLOCK_DEVICE;

   // This var is defined by CommonDBRelation ...
   var $no_form_page                    = false;

   static protected $forward_entity_to = array('Infocom');


   /**
    * @since version 0.85
    * No READ right for devices and extends CommonDBRelation not CommonDevice
   **/
   static function canView() {
      return true;
   }

   /**
    * @since version 0.85
   **/
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

      $forbidden = parent::getForbiddenStandardMassiveAction();

      if ((count(static::getSpecificities()) == 0)
          && !InfoCom::canApplyOn($this)) {
         $forbidden[] = 'update';
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
         $tab[$attributs['id']] = array('table'         => $this->getTable(),
                                        'field'         => $field,
                                        'name'          => $attributs['long name'],
                                        'massiveaction' => true);

         if (isset($attributs['datatype'])) {
            $tab[$attributs['id']]['datatype'] = $attributs['datatype'];
         }
      }

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['datatype']       = 'dropdown';

      return $tab;
   }


   /**
    * Get the specificities of the given device. For instance, the
    * serial number, the size of the memory, the frequency of the CPUs ...
    *
    * @param $specif   string   specificity to display
    *
    * Should be overloaded by Item_Device*
    *
    * @return array of the specificities: index is the field name and the values are the attributs
    *                                     of the specificity (long name, short name, size)
   **/
   static function getSpecificities($specif='') {

      switch($specif) {
         case 'serial' :
            return array('long name'  => __('Serial number'),
                         'short name' => __('Serial number'),
                         'size'       => 20,
                         'id'         => 10);

         case 'busID' :
            return array('long name'  => __('Position of the device on its bus'),
                         'short name' => __('bus ID'),
                         'size'       => 10,
                         'id'         => 11);
      }
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
    *
    * @return array of the types of Item_Device* available
   **/
   static function getDeviceTypes() {
      global $CFG_GLPI;

      // If the size of $CFG_GLPI['item_device_types'] and $CFG_GLPI['device_types'] then,
      // there is new device_types and we must update item_device_types !
      if (!isset($CFG_GLPI['item_device_types'])
          || (count($CFG_GLPI['item_device_types']) != count($CFG_GLPI['device_types']))) {

         $CFG_GLPI['item_device_types'] = array();

         foreach (CommonDevice::getDeviceTypes() as $deviceType) {
            $CFG_GLPI['item_device_types'][] = $deviceType::getItem_DeviceType();
         }
      }

      return $CFG_GLPI['item_device_types'];
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
      return array('Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer');
   }


   /**
    * Get associated device to the current item_device
    *
    * @since version 0.85
    *
    * @return string containing the device
   **/
   static function getDeviceType() {

      $devicetype = get_called_class();
      if ($plug = isPluginItemType($devicetype)) {
         return 'Plugin'.$plug['plugin'].str_replace ('Item_', '', $plug['class']);
      }
      return str_replace ('Item_', '', $devicetype);
   }


   static function cloneItem($itemtype, $oldid, $newid) {
      global $DB;

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
         $nb = 0;
         if (in_array($item->getType(), self::getConcernedItems())) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               foreach (self::getItemAffinities($item->getType()) as $link_type) {
                  $nb   += countElementsInTable($link_type::getTable(),
                                                "`items_id` = '".$item->getID()."'
                                                   AND `itemtype` = '".$item->getType()."'
                                                   AND `is_deleted` = '0'");
               }
            }
            return self::createTabEntry(_n('Component', 'Components', Session::getPluralNumber()),
                                        $nb);
         }
         if ($item instanceof CommonDevice) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $deviceClass     = $item->getType();
               $linkClass       = $deviceClass::getItem_DeviceType();
               $table           = $linkClass::getTable();
               $foreignkeyField = $deviceClass::getForeignKeyField();
               $nb = countElementsInTable($table,
                                          "`$foreignkeyField` = '".$item->getID()."'
                                            AND `is_deleted` = '0'");
            }
            return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);
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

      $canedit = (($withtemplate != 2)
                  && $item->canEdit($ID)
                  && Session::haveRightsOr('device', array(UPDATE, PURGE)));
      echo "<div class='spaced'>";
      $rand = mt_rand();
      if ($canedit) {
         echo "\n<form id='form_device_add$rand' name='form_device_add$rand'
                  action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>\n";
         echo "\t<input type='hidden' name='items_id' value='$ID'>\n";
         echo "\t<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
      }

      $table = new HTMLTableMain();

      $table->setTitle(_n('Component', 'Components', Session::getPluralNumber()));
      if ($canedit) {
         $delete_all_column = $table->addHeader('delete all',
                                                Html::getCheckAllAsCheckbox("form_device_action$rand",
                                                '__RAND__'));
         $delete_all_column->setHTMLClass('center');
      } else {
         $delete_all_column = NULL;
      }

      $column_label    = ($is_device ? _n('Item', 'Items', Session::getPluralNumber()) : __('Type of component'));
      $common_column   = $table->addHeader('common', $column_label);
      $specific_column = $table->addHeader('specificities', __('Specificities'));
      $specific_column->setHTMLClass('center');

      $dynamic_column = '';
      if ($item->isDynamic()) {
         $dynamic_column = $table->addHeader('is_dynamic', __('Automatic inventory'));
         $dynamic_column->setHTMLClass('center');
      }

      if ($canedit) {
         $massiveactionparams = array('container'     => "form_device_action$rand",
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
         Session::initNavigateListItems(static::getType(),
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));
         foreach (array_merge(array(''), self::getConcernedItems()) as $itemtype) {
            $table_options['itemtype'] = $itemtype;
            $link                      = getItemForItemtype(static::getType());

            $link->getTableGroup($item, $table, $table_options, $delete_all_column,
                                 $common_column, $specific_column, $delete_column,
                                 $dynamic_column);
         }
      } else {
         $devtypes = array();
         foreach (self::getItemAffinities($item->getType()) as $link_type) {
            $devtypes [] = $link_type::getDeviceType();
            $link        = getItemForItemtype($link_type);

            Session::initNavigateListItems($link_type,
                                           sprintf(__('%1$s = %2$s'),
                                                   $item->getTypeName(1), $item->getName()));
            $link->getTableGroup($item, $table, $table_options, $delete_all_column,
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
                                                        'entity_restrict'     => $item->getEntityID(),
                                                        'showItemSpecificity' => $CFG_GLPI['root_doc']
                                                                 .'/ajax/selectUnaffectedOrNewItem_Device.php'));
         }
         echo "</td><td>";
         echo "<input type='submit' class='submit' name='add' value='"._sx('button', 'Add')."'>";
         echo "</td></tr></table>";
         Html::closeForm();
      }

      if ($canedit) {
         echo "\n<form id='form_device_action$rand' name='form_device_action$rand'
                  action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>\n";
         echo "\t<input type='hidden' name='items_id' value='$ID'>\n";
         echo "\t<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
      }

      $table->display(array('display_super_for_each_group' => false,
                            'display_title_for_each_group' => false));

      if ($canedit) {
//          echo "<input type='submit' class='submit' name='updateall' value='" .
//               _sx('button', 'Save')."'>";

         Html::closeForm();
      }

      echo "</div>";
      // Force disable selected items
      $_SESSION['glpimassiveactionselected'] = array();
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
    * @param $delete_all_column          (default NULL)
    * @param $common_column
    * @param $specific_column
    * @param $delete_column               (default NULL)
    * @param $dynamic_column
   **/
   function getTableGroup(CommonDBTM $item, HTMLTableMain $table, array $options,
                          HTMLTableSuperHeader $delete_all_column=NULL,
                          HTMLTableSuperHeader $common_column,
                          HTMLTableSuperHeader $specific_column,
                          HTMLTableSuperHeader $delete_column=NULL, $dynamic_column) {
      global $DB;

      $is_device = ($item instanceof CommonDevice);

      if ($is_device) {
         $peer_type = $options['itemtype'];

         if (empty($peer_type)) {
            $column_label = __('Dissociated devices');
            $group_name   = 'None';
         } else {
            $column_label = $peer_type::getTypeName(Session::getPluralNumber());
            $group_name   = $peer_type;
         }

         $table_group = $table->createGroup($group_name, '');

         $peer_column = $table_group->addHeader('item', $column_label, $common_column, NULL);

         if (!empty($peer_type)) {
            //TRANS : %1$s is the type of the device
            //        %2$s is the type of the item
            //        %3$s is the name of the item (used for headings of a list),
            $itemtype_nav_title = sprintf(__('%1$s of %2$s: %3$s'), $peer_type::getTypeName(Session::getPluralNumber()),
                                          $item->getTypeName(1), $item->getName());
            $peer_column->setItemType($peer_type, $itemtype_nav_title);
         }

      } else {
         $peer_type   = $this->getDeviceType();

         $table_group = $table->createGroup($peer_type, '');

         //TRANS : %1$s is the type of the device
         //        %2$s is the type of the item
         //        %3$s is the name of the item (used for headings of a list),
         $options['itemtype_title'] = sprintf(__('%1$s of %2$s: %3$s'), $peer_type::getTypeName(Session::getPluralNumber()),
                                              $item->getTypeName(1), $item->getName());

         $peer_type::getHTMLTableHeader($item->getType(), $table_group, $common_column, NULL,
                                          $options);
      }

      $specificity_columns = array();
      $link_column         = $table_group->addHeader('spec_link', '', $specific_column);
      $spec_column         = $link_column;

      foreach ($this->getSpecificities() as $field => $attributs) {
         $spec_column                 = $table_group->addHeader('spec_'.$field,
                                                                $attributs['long name'],
                                                                $specific_column, $spec_column);
         $specificity_columns[$field] = $spec_column;
      }

      $infocom_column  = $table_group->addHeader('infocom', Infocom::getTypeName(Session::getPluralNumber()),
                                                 $specific_column, $spec_column);

      $document_column = $table_group->addHeader('document', Document::getTypeName(Session::getPluralNumber()),
                                                 $specific_column, $spec_column);

      if ($item->isDynamic()) {
         $dynamics_column = $table_group->addHeader('one', '&nbsp;', $dynamic_column,
                                                    $spec_column);
         $previous_column = $dynamics_column;
      } else {
         $previous_column = $spec_column;
      }

      if ($options['canedit']) {
         $group_checkbox_tag =  (empty($peer_type) ? '__' : $peer_type);
         $content            = Html::getCheckbox(array('criterion'
                                                         => array('tag_for_massive'
                                                                   => $group_checkbox_tag)));
         $delete_one         = $table_group->addHeader('one', $content, $delete_column,
                                                       $previous_column);
      }

      if ($is_device) {
         $fk = 'items_id';

         // Entity restrict
         $leftjoin = '';
         $where = "";
         if (!empty($peer_type)) {
            $leftjoin = "LEFT JOIN `".getTableForItemType($peer_type)."`
                        ON (`".$this->getTable()."`.`items_id` = `".getTableForItemType($peer_type)."`.`id`
                            AND `".$this->getTable()."`.`itemtype` = '$peer_type')";
            $where = getEntitiesRestrictRequest(" AND", getTableForItemType($peer_type));
         }

         $query = "SELECT `".$this->getTable()."`.*
                   FROM `".$this->getTable()."`
                   $leftjoin
                   WHERE `".$this->getDeviceForeignKey()."` = '".$item->getID()."'
                         AND `".$this->getTable()."`.`itemtype` = '$peer_type'
                         AND `".$this->getTable()."`.`is_deleted` = '0'
                         $where
                   ORDER BY `".$this->getTable()."`.`itemtype`, `".$this->getTable()."`.`$fk`";

      } else {
         $fk = $this->getDeviceForeignKey();

         $query = "SELECT *
                   FROM `".$this->getTable()."`
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

         Session::addToNavigateListItems(static::getType(), $link["id"]);
         $this->getFromDB($link['id']);
         if ((is_null($peer)) || ($link[$fk] != $peer->getID())) {

            if ($peer instanceof CommonDBTM) {
               $peer->getFromDB($link[$fk]);
            }

            $current_row  = $table_group->createRow();
            $peer_group   = $peer_type.'_'.$link[$fk].'_'.mt_rand();
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


         if (Session::haveRight('device', UPDATE)) {
            $mode = __s('Update');
         } else {
            $mode = __s('View');
         }
         $spec_cell = $current_row->addCell($link_column,
                                            "<a href='" . $this->getLinkURL() . "'>$mode</a>");

         foreach ($this->getSpecificities() as $field => $attributs) {
            if (!empty($link[$field])) {
               $content = $link[$field];
            } else {
               $content = '';
            }
            $spec_cell = $current_row->addCell($specificity_columns[$field], $content, $spec_cell);
         }

         if (countElementsInTable('glpi_infocoms', "`itemtype`='".$this->getType()."' AND
                                                    `items_id`='".$link['id']."'")) {
            $content = array(array('function'   => 'Infocom::showDisplayLink',
                                   'parameters' => array($this->getType(), $link['id'])));
         } else {
            $content = '';
         }
         $current_row->addCell($infocom_column, $content, $spec_cell);

         $content = array();
         // The order is to be sure that specific documents appear first
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE (`itemtype` = '".$this->getType()."' AND `items_id` = '".$link['id']."')
                          OR (`itemtype` = '".$this->getDeviceType()."'
                              AND `items_id` = '".$link[$this->getDeviceForeignKey()]."')
                   ORDER BY `itemtype` = '".$this->getDeviceType()."'";
         $document = new Document();
         foreach ($DB->request($query) as $document_link) {
            if ($document->can($document_link['documents_id'], READ)) {
               $content[] = $document->getLink();
            }
         }
         $content = implode('<br>', $content);
         $current_row->addCell($document_column, $content, $spec_cell);



         if ($item->isDynamic()) {
            $previous_cell = $current_row->addCell($dynamics_column,
                                                   Dropdown::getYesNo($link['is_dynamic']),
                                                   $spec_cell);
         } else {
            $previous_cell = $spec_cell;
         }

         if ($options['canedit']) {
            $cell_value = Html::getMassiveActionCheckBox($this->getType(), $link['id'],
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
    * @param $input          array to complete (permit to define values)
   **/
   function addDevices($numberToAdd, $itemtype, $items_id, $devices_id, $input=array()) {
      global $DB;

      if ($numberToAdd == 0) {
         return;
      }

      $input['itemtype']                    = $itemtype;
      $input['items_id']                    = $items_id;
      $input[static::getDeviceForeignKey()] = $devices_id;

      $device_type = static::getDeviceType();
      $device      = new $device_type();
      $device->getFromDB($devices_id);

      foreach (static::getSpecificities() as $field => $attributs) {
         if (isset($device->fields[$field.'_default'])) {
            $input[$field] = $device->fields[$field.'_default'];
         }
      }

      if ($this->can(-1, CREATE, $input)) {
         for ($i = 0 ; $i < $numberToAdd ; $i ++) {
            $this->add($input);
         }
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
         $devicetype = $input['devicetype'];
         $linktype   = $devicetype::getItem_DeviceType();
         if ($link = getItemForItemtype($linktype)) {
            if ((isset($input[$linktype::getForeignKeyField()]))
                && (count($input[$linktype::getForeignKeyField()]))) {
               $update_input = array('itemtype' => $input['itemtype'],
                                     'items_id' => $input['items_id']);
               foreach ($input[$linktype::getForeignKeyField()] as $id) {
                  $update_input['id'] = $id;
                  $link->update($update_input);
               }
            }
            if (isset($input['new_devices'])) {
               $link->addDevices($input['new_devices'], $input['itemtype'], $input['items_id'],
                                 $input['devices_id']);
            }
         }
      } else {
         if (!$item = getItemForItemtype($input['itemtype'])) {
            Html::displayNotFoundError();
         }
         if ($item instanceof CommonDevice) {
            if ($link = getItemForItemtype($item->getItem_DeviceType())) {
               $link->addDevices($input['number_devices_to_add'], '', 0, $input['items_id']);
            }
         }
      }
   }


   /**
    * @param $input array of input: should be $_POST
   **/
   static function updateAll($input) {

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
         $link_type = $itemtype::getItem_DeviceType();
      }

      $links   = array();
      // Update quantity or values
      $device_type = '';
      foreach ($input as $key => $val) {
         $data = explode("_",$key);
         if (!empty($data[0])) {
            $command = $data[0];
         } else {
            continue;
         }
         if (($command != 'quantity') && ($command != 'value')) {
            // items_id, itemtype, devicetype ...
            continue;
         }
         if (!$is_device) {
            if (empty($data[1])) {
               continue;
            }
            $device_type = $data[1];
            if (in_array($device_type::getItem_DeviceType(), self::getItemAffinities($itemtype))) {
               $link_type = $device_type::getItem_DeviceType();
            }
         }
         if (!empty($data[2])) {
            $links_id = $data[2];
         } else {
            continue;
         }
         if (!isset($links[$link_type])) {
            $links[$link_type] = array('add'    => array(),
                                       'update' => array());
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
         }
      }

      foreach ($links as $type => $commands) {
         if ($link = getItemForItemtype($type)) {
            foreach ($commands['add'] as $link_to_add => $number) {
               $link->addDevices($number, $itemtype, $items_id, $link_to_add);
            }
            foreach ($commands['update'] as $link_to_update => $input) {
               $input['id'] = $link_to_update;
               $link->update($input);
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
    * @param $unaffect
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


   /**
    * @since version 0.85
    *
    * @see CommonDBConnexity::getConnexityMassiveActionsSpecificities()
   **/
   static function getConnexityMassiveActionsSpecificities() {

      $specificities              = parent::getConnexityMassiveActionsSpecificities();

      $specificities['reaffect']  = 1;
      $specificities['itemtypes'] = self::getConcernedItems();

      return $specificities;
   }



   /**
    * @since version 0.85
    *
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @since version 0.85
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!$this->isNewID($ID)) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE);
      }
      $this->showFormHeader($options);

      $item   = $this->getOnePeer(0);
      $device = $this->getOnePeer(1);

      echo "<tr class='tab_bg_1'><td>".__('Item')."</td>";
      echo "<td>";
      if ($item === false) {
         echo __('No associated item');
      } else {
         echo $item->getLink();
      }
      echo "</td>";

      echo "<td>".__('Device')."</td>";
      echo "<td>".$device->getLink()."</td>";
      echo "</tr>";
      $even = 0;
      $nb = count(static::getSpecificities());
      foreach (static::getSpecificities() as $field => $attributs) {
         if (($even % 2) == 0) {
            echo "<tr class='tab_bg_1'>";
         }
         echo "<td>".$attributs['long name']."</td>";
         echo "<td>";
         Html::autocompletionTextField($this, $field, array('size' => $attributs['size']));
         echo "</td>";
         $even ++;
         if (($even == $nb) && (($nb % 2) != 0) && $nb > 1) {
            echo "<td></td><td></td></tr>";
         }
      }
      $options['canedit'] =  Session::haveRight('device', UPDATE);
      $this->showFormButtons($options);

      return true;
   }

}
?>
