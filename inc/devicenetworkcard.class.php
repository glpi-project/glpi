<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * DeviceNetworkCard Class
**/
class DeviceNetworkCard extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceNetworkCard', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Network card', 'Network cards', $nb);
   }


   /**
    * Criteria used for import function
    *
    * @since 0.84
   **/
   function getImportCriteria() {

      return ['designation'      => 'equal',
                   'manufacturers_id' => 'equal',
                   'mac'              => 'equal'];
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         [['name'  => 'mac_default',
                                     'label' => __('MAC address by default'),
                                     'type'  => 'text'],
                               ['name'  => 'bandwidth',
                                     'label' => __('Flow'),
                                     'type'  => 'text'],
                               ['name'  => 'devicenetworkcardmodels_id',
                                     'label' => __('Model'),
                                     'type'  => 'dropdownValue'],
                               ['name'  => 'none',
                                     'label' => RegisteredID::getTypeName(Session::getPluralNumber()).
                                        RegisteredID::showAddChildButtonForItemForm($this,
                                                                                    '_registeredID',
                                                                                    null, false),
                                     'type'  => 'registeredIDChooser']]);
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'mac_default',
         'name'               => __('MAC address by default'),
         'datatype'           => 'mac'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'bandwidth',
         'name'               => __('Flow'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicenetworkcardmodels',
         'field'              => 'name',
         'name'               => __('Model'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * Import a device if not exists
    *
    * @param $input array of datas
    *
    * @return interger ID of existing or new Device
   **/
   function import(array $input) {
      global $DB;

      if (!isset($input['designation']) || empty($input['designation'])) {
         return 0;
      }

      $query = "SELECT `id`
                FROM `".$this->getTable()."`
                WHERE `designation` = '" . $input['designation'] . "'";

      if (isset($input["bandwidth"])) {
         $query .= " AND `bandwidth` = '".$input["bandwidth"]."'";
      }

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $line = $DB->fetch_assoc($result);
         return $line['id'];
      }
      return $this->add($input);
   }


   /**
    * @since 0.84
    *
    * @see CommonDevice::getHTMLTableHeader()
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      if (in_array($itemtype, NetworkPort::getNetworkPortInstantiations())) {
         $base->addHeader($column_name, __('Interface'), $super, $father);
      } else {
         $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);
         if ($column == $father) {
            return $father;
         }
         Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
         $base->addHeader('devicenetworkcard_bandwidth', __('Flow'), $super, $father);
      }
   }


   /**
    * @since 0.84
    *
    * @see CommonDevice::getHTMLTableCellForItem()
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                            HTMLTableCell $father = null, array $options = []) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      if (empty($item)) {
         if (empty($father)) {
            return;
         }
         $item = $father->getItem();
      }

      if (in_array($item->getType(), NetworkPort::getNetworkPortInstantiations())) {
         $link = new Item_DeviceNetworkCard();
         if ($link->getFromDB($item->fields['items_devicenetworkcards_id'])) {
            $device = $link->getOnePeer(1);
            if ($device) {
               $row->addCell($row->getHeaderByName($column_name), $device->getLink(), $father);
            }
         }
      }
   }


   function getHTMLTableCellForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                    HTMLTableCell $father = null, array $options = []) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($item->getType()) {
         case 'Computer' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
            if ($this->fields["bandwidth"]) {
               $row->addCell($row->getHeaderByName('devicenetworkcard_bandwidth'),
                             $this->fields["bandwidth"], $father);
            }
            break;
      }
   }

}
