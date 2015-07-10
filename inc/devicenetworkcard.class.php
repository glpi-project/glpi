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
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * DeviceNetworkCard Class
**/
class DeviceNetworkCard extends CommonDevice {

   static protected $forward_entity_to = array('Item_DeviceNetworkCard', 'Infocom');

   static function getTypeName($nb=0) {
      return _n('Network card', 'Network cards', $nb);
   }


   /**
    * Criteria used for import function
    *
    * @since version 0.84
   **/
   function getImportCriteria() {

      return array('designation'      => 'equal',
                   'manufacturers_id' => 'equal',
                   'mac'              => 'equal');
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'mac_default',
                                     'label' => __('MAC address by default'),
                                     'type'  => 'text'),
                               array('name'  => 'bandwidth',
                                     'label' => __('Flow'),
                                     'type'  => 'text'),
                               array('name'  => 'none',
                                     'label' => RegisteredID::getTypeName(Session::getPluralNumber()).
                                        RegisteredID::showAddChildButtonForItemForm($this,
                                                                                    '_registeredID',
                                                                                    NULL, false),
                                     'type'  => 'registeredIDChooser')));
   }


   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'mac_default';
      $tab[11]['name']     = __('MAC address by default');
      $tab[11]['datatype'] = 'mac';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'bandwidth';
      $tab[12]['name']     = __('Flow');
      $tab[12]['datatype'] = 'string';

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
    * @since version 0.84
    *
    * @see CommonDevice::getHTMLTableHeader()
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      if (in_array($itemtype, NetworkPort::getNetworkPortInstantiations())) {
         $base->addHeader($column_name, __('Interface'), $super, $father);
      } else {
         $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);
         if ($column == $father)  {
            return $father;
         }
         Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
         $base->addHeader('devicenetworkcard_bandwidth', __('Flow'), $super, $father);
      }
   }


   /**
    * @since version 0.84
    *
    * @see CommonDevice::getHTMLTableCellForItem()
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                            HTMLTableCell $father=NULL, array $options=array()) {

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


   function getHTMLTableCellForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                    HTMLTableCell $father=NULL, array $options=array()) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($item->getType()) {
         case 'Computer' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, NULL, $options);
            if ($this->fields["bandwidth"]) {
               $row->addCell($row->getHeaderByName('devicenetworkcard_bandwidth'),
                             $this->fields["bandwidth"], $father);
            }
            break;
      }
   }

}
?>