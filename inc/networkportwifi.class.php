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

/// NetworkPortWifi class : wifi instantitation of NetworkPort
/// @todo : add connection to other wifi networks
/// @since 0.84
class NetworkPortWifi extends NetworkPortInstantiation {


   static function getTypeName($nb = 0) {
      return __('Wifi port');
   }


   function getNetworkCardInterestingFields() {
      return ['link.`mac`' => 'mac'];
   }


   /**
    * @see NetworkPortInstantiation::showInstantiationForm()
   **/
   function showInstantiationForm(NetworkPort $netport, $options, $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>\n";
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         echo "<td>" . WifiNetwork::getTypeName(1) . "</td><td>";
         WifiNetwork::dropdown(['value'  => $this->fields["wifinetworks_id"]]);
         echo "</td>";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>\n";
         echo "<td>" . __('Wifi mode') . "</td>";
         echo "<td>";

         Dropdown::showFromArray('mode', WifiNetwork::getWifiCardModes(),
                                 ['value' => $this->fields['mode']]);

         echo "</td>\n";
         echo "<td>" . __('Wifi protocol version') . "</td><td>";

         Dropdown::showFromArray('version', WifiNetwork::getWifiCardVersion(),
                                 ['value' => $this->fields['version']]);

         echo "</td>\n";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>\n";
         $this->showMacField($netport, $options);
         echo "</tr>\n";
      }
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTableHeaders
   **/
   function getInstantiationHTMLTableHeaders(HTMLTableGroup $group, HTMLTableSuperHeader $super,
                                             HTMLTableSuperHeader $internet_super = null,
                                             HTMLTableHeader $father = null,
                                             array $options = []) {

      DeviceNetworkCard::getHTMLTableHeader('NetworkPortWifi', $group, $super, null, $options);

      $group->addHeader('ESSID', __('ESSID'), $super);
      $group->addHeader('Mode', __('Wifi mode'), $super);
      $group->addHeader('Version', __('Wifi protocol version'), $super);

      parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $father, $options);
      return null;
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTable()
   **/
   function getInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                      HTMLTableCell $father = null, array $options = []) {

      DeviceNetworkCard::getHTMLTableCellsForItem($row, $this, null, $options);

      $row->addCell($row->getHeaderByName('Instantiation', 'ESSID'),
                    Dropdown::getDropdownName("glpi_wifinetworks",
                                              $this->fields["wifinetworks_id"]));

      $row->addCell($row->getHeaderByName('Instantiation', 'Mode'), $this->fields['mode']);

      $row->addCell($row->getHeaderByName('Instantiation', 'Version'), $this->fields['version']);

      parent::getInstantiationHTMLTable($netport, $row, $father, $options);
      return null;
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'mac',
         'name'               => __('MAC'),
         'massiveaction'      => false,
         'datatype'           => 'mac'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'mode',
         'name'               => __('Wifi mode'),
         'massiveaction'      => false,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'version',
         'name'               => __('Wifi protocol version'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_wifinetworks',
         'field'              => 'name',
         'name'               => WifiNetwork::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'mode':
            $tab = WifiNetwork::getWifiCardModes();
            if (isset($tab[$values[$field]])) {
               return $tab[$values[$field]];
            }
            return NOT_AVAILABLE;

         case 'version':
            $tab = WifiNetwork::getWifiCardVersion();
            if (isset($tab[$values[$field]])) {
               return $tab[$values[$field]];
            }
            return NOT_AVAILABLE;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param $field
    * @param $name            (default'')
    * @param $values           (default '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'mode':
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, WifiNetwork::getWifiCardModes(), $options);

         case 'version':
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, WifiNetwork::getWifiCardVersion(), $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @param $tab          array
    * @param $joinparams   array
   **/
   static function getSearchOptionsToAddForInstantiation(array &$tab, array $joinparams) {

      $tab[] = [
         'id'                 => '157',
         'table'              => 'glpi_wifinetworks',
         'field'              => 'name',
         'name'               => WifiNetwork::getTypeName(1),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'standard',
            'beforejoin'         => [
               'table'              => 'glpi_networkportwifis',
               'joinparams'         => $joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '158',
         'table'              => 'glpi_wifinetworks',
         'field'              => 'essid',
         'name'               => __('ESSID'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'standard',
            'beforejoin'         => [
               'table'              => 'glpi_networkportwifis',
               'joinparams'         => $joinparams
            ]
         ]
      ];
   }
}
