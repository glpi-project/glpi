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

/// NetworkPortEthernet class : Ethernet instantiation of NetworkPort
/// @since 0.84
class NetworkPortEthernet extends NetworkPortInstantiation {


   static function getTypeName($nb = 0) {
      return __('Ethernet port');
   }


   function getNetworkCardInterestingFields() {
      return ['link.`mac`' => 'mac'];
   }


   function prepareInput($input) {

      if (isset($input['speed']) && ($input['speed'] == 'speed_other_value')) {
         $speed = self::transformPortSpeed($input['speed_other_value'], false);
         if ($speed === false) {
            unset($input['speed']);
         } else {
            $input['speed'] = $speed;
         }
      }

      return $input;
   }


   function prepareInputForAdd($input) {
      return parent::prepareInputForAdd($this->prepareInput($input));
   }


   function prepareInputForUpdate($input) {
      return parent::prepareInputForUpdate($this->prepareInput($input));
   }


   function showInstantiationForm(NetworkPort $netport, $options, $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>";
         $this->showNetpointField($netport, $options, $recursiveItems);
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         echo "</tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Ethernet port type') . "</td><td>\n";
      Dropdown::showFromArray('type', self::getPortTypeName(),
                              ['value' => $this->fields['type']]);
      echo "</td>";
      echo "<td>" . __('Ethernet port speed') . "</td><td>\n";

      $standard_speeds = self::getPortSpeed();
      if (!isset($standard_speeds[$this->fields['speed']])
          && !empty($this->fields['speed'])) {
         $speed = self::transformPortSpeed($this->fields['speed'], true);
      } else {
         $speed = true;
      }

      Dropdown::showFromArray('speed', $standard_speeds,
                              ['value' => $this->fields['speed'],
                                    'other' => $speed]);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>\n";
      $this->showMacField($netport, $options);

      echo "<td>".__('Connected to').'</td><td>';
      self::showConnection($netport, true);
      echo "</td>";
      echo "</tr>\n";
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTableHeaders
   **/
   function getInstantiationHTMLTableHeaders(HTMLTableGroup $group, HTMLTableSuperHeader $super,
                                             HTMLTableSuperHeader $internet_super = null,
                                             HTMLTableHeader $father = null,
                                             array $options = []) {

      $display_options = &$options['display_options'];
      $header          = $group->addHeader('Connected', __('Connected to'), $super);

      DeviceNetworkCard::getHTMLTableHeader('NetworkPortEthernet', $group, $super, $header,
                                            $options);

      $group->addHeader('speed', __('Ethernet port speed'), $super, $header);
      $group->addHeader('type', __('Ethernet port type'), $super, $header);

      Netpoint::getHTMLTableHeader('NetworkPortEthernet', $group, $super, $header, $options);

      $group->addHeader('Outlet', __('Network outlet'), $super, $header);

      parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $header, $options);
      return $header;
   }


   /**
    * @see NetworkPortInstantiation::getPeerInstantiationHTMLTable()
   **/
   protected function getPeerInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                                    HTMLTableCell $father = null,
                                                    array $options = []) {

      DeviceNetworkCard::getHTMLTableCellsForItem($row, $this, $father, $options);

      if (!empty($this->fields['speed'])) {
         $row->addCell($row->getHeaderByName('Instantiation', 'speed'),
                       self::getPortSpeed($this->fields["speed"]), $father);
      }

      if (!empty($this->fields['type'])) {
         $row->addCell($row->getHeaderByName('Instantiation', 'type'),
                       self::getPortTypeName($this->fields["type"]), $father);
      }

      parent::getInstantiationHTMLTable($netport, $row, $father, $options);
      Netpoint::getHTMLTableCellsForItem($row, $this, $father, $options);

   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTable()
   **/
   function getInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                      HTMLTableCell $father = null, array $options = []) {

      return parent::getInstantiationHTMLTableWithPeer($netport, $row, $father, $options);
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
         'datatype'           => 'mac',
         'name'               => __('MAC'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'type',
         'name'               => __('Ethernet port type'),
         'massiveaction'      => false,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'speed',
         'name'               => __('Ethernet port speed'),
         'massiveaction'      => false,
         'datatype'           => 'specific'
      ];

      return $tab;
   }


   /**
    * Get the possible value for Ethernet port type
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getPortTypeName($val = null) {

      $tmp['']   = Dropdown::EMPTY_VALUE;
      $tmp['T']  = __('Twisted pair (RJ-45)');
      $tmp['SX'] = __('Multimode fiber');
      $tmp['LX'] = __('Single mode fiber');

      if (is_null($val)) {
         return $tmp;
      }
      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      return NOT_AVAILABLE;
   }


   /**
    * Transform a port speed from string to integerer and vice-versa
    *
    * @param $val       port speed (integer or string)
    * @param $to_string (boolean) true if we must transform the speed to string
    *
    * @return integer or string (regarding what is requested)
   **/
   static function transformPortSpeed($val, $to_string) {

      if ($to_string) {
         if (($val % 1000) == 0) {
            //TRANS: %d is the speed
            return sprintf(__('%d Gbit/s'), $val / 1000);
         }

         if ((($val % 100) == 0) && ($val > 1000)) {
            $val /= 100;
            //TRANS: %f is the speed
            return sprintf(__('%.1f Gbit/s'), $val / 10);
         }

         //TRANS: %d is the speed
         return sprintf(__('%d Mbit/s'), $val);
      } else {
         $val = preg_replace( '/\s+/', '', strtolower($val));

         $number = sscanf($val, "%f%s", $speed, $unit);
         if ($number != 2) {
            return false;
         }

         if (($unit == 'mbit/s') || ($unit == 'mb/s')) {
            return (int)$speed;
         }

         if (($unit == 'gbit/s') || ($unit == 'gb/s')) {
            return (int)($speed * 1000);
         }

         return false;
      }
   }


   /**
    * Get the possible value for Ethernet port speed
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getPortSpeed($val = null) {

      $tmp = [0     => '',
                   //TRANS: %d is the speed
                   10    => sprintf(__('%d Mbit/s'), 10),
                   100   => sprintf(__('%d Mbit/s'), 100),
                   //TRANS: %d is the speed
                   1000  => sprintf(__('%d Gbit/s'), 1),
                   10000 => sprintf(__('%d Gbit/s'), 10)];

      if (is_null($val)) {
         return $tmp;
      }
      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      return self::transformPortSpeed($val, true);
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
         case 'type':
            return self::getPortTypeName($values[$field]);

         case 'speed':
            return self::getPortSpeed($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param $field
    * @param $name            (default '')
    * @param $values          (defaul '')
    * @param $options   array
    */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'type':
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getPortTypeName(), $options);

         case 'speed':
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getPortSpeed(), $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @param $tab         array
    * @param $joinparams  array
   **/
   static function getSearchOptionsToAddForInstantiation(array &$tab, array $joinparams) {
      $tab[] = [
         'id'                 => '22',
         'table'              => 'glpi_netpoints',
         'field'              => 'name',
         'datatype'           => 'dropdown',
          'name'              => __('Ethernet outlet'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'standard',
            'beforejoin'         => [
               'table'              => 'glpi_networkportethernets',
               'joinparams'         => $joinparams
            ]
         ]
      ];
   }

}
