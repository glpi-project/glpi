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

/// NetworkPortWifi class : wifi instantitation of NetworkPort
/// @todo : add connection to other wifi networks
/// @since 0.84
class NetworkPortWifi extends NetworkPortInstantiation {


   static function getTypeName($nb=0) {
      return __('Wifi port');
   }


   function getNetworkCardInterestingFields() {
      return array('link.`mac`' => 'mac');
  }


   /**
    * @see NetworkPortInstantiation::showInstantiationForm()
   **/
   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>\n";
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         echo "<td>" . WifiNetwork::getTypeName(1) . "</td><td>";
         WifiNetwork::dropdown(array('value'  => $this->fields["wifinetworks_id"]));
         echo "</td>";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>\n";
         echo "<td>" . __('Wifi mode') . "</td>";
         echo "<td>";

         Dropdown::showFromArray('mode', WifiNetwork::getWifiCardModes(),
                                 array('value' => $this->fields['mode']));

         echo "</td>\n";
         echo "<td>" . __('Wifi protocol version') . "</td><td>";

         Dropdown::showFromArray('version', WifiNetwork::getWifiCardVersion(),
                                 array('value' => $this->fields['version']));

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
                                             HTMLTableSuperHeader $internet_super=NULL,
                                             HTMLTableHeader $father=NULL,
                                             array $options=array()) {

      DeviceNetworkCard::getHTMLTableHeader('NetworkPortWifi', $group, $super, NULL, $options);

      $group->addHeader('ESSID', __('ESSID'), $super);
      $group->addHeader('Mode', __('Wifi mode'), $super);
      $group->addHeader('Version', __('Wifi protocol version'), $super);

      parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $father, $options);
      return NULL;
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTable()
   **/
   function getInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                      HTMLTableCell $father=NULL, array $options=array()) {

      DeviceNetworkCard::getHTMLTableCellsForItem($row, $this, NULL, $options);

      $row->addCell($row->getHeaderByName('Instantiation', 'ESSID'),
                    Dropdown::getDropdownName("glpi_wifinetworks",
                                              $this->fields["wifinetworks_id"]));

      $row->addCell($row->getHeaderByName('Instantiation', 'Mode'), $this->fields['mode']);

      $row->addCell($row->getHeaderByName('Instantiation', 'Version'), $this->fields['version']);

      parent::getInstantiationHTMLTable($netport, $row, $father, $options);
      return NULL;
   }


   function getSearchOptions() {

      $tab = array();
      $tab['common']            = __('Characteristics');

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'mac';
      $tab[10]['name']          = __('MAC');
      $tab[10]['massiveaction'] = false;
      $tab[10]['datatype']      = 'mac';

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'mode';
      $tab[11]['name']          = __('Wifi mode');
      $tab[11]['massiveaction'] = false;
      $tab[11]['datatype']      = 'specific';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'version';
      $tab[12]['name']          = __('Wifi protocol version');
      $tab[12]['massiveaction'] = false;
      $tab[11]['datatype']      = 'specific';

      $tab[13]['table']         = 'glpi_wifinetworks';
      $tab[13]['field']         = 'name';
      $tab[13]['name']          = WifiNetwork::getTypeName(1);
      $tab[13]['massiveaction'] = false;
      $tab[13]['datatype']      = 'dropdown';

      return $tab;
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
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
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
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
    * @param $itemtype
   **/
   static function getSearchOptionsToAddForInstantiation(array &$tab, array $joinparams,
                                                         $itemtype) {

      $tab[157]['table']         = 'glpi_wifinetworks';
      $tab[157]['field']         = 'name';
      $tab[157]['name']          = WifiNetwork::getTypeName(1);
      $tab[157]['forcegroupby']  = true;
      $tab[157]['massiveaction'] = false;
      $tab[157]['joinparams']    = array('jointype'   => 'standard',
                                         'beforejoin' => array('table' => 'glpi_networkportwifis',
                                                               'joinparams'
                                                                       => $joinparams));

      $tab[158]['table']         = 'glpi_wifinetworks';
      $tab[158]['field']         = 'essid';
      $tab[158]['name']          = __('ESSID');
      $tab[158]['forcegroupby']  = true;
      $tab[158]['massiveaction'] = false;
      $tab[158]['joinparams']    = array('jointype'   => 'standard',
                                         'beforejoin' => array('table' => 'glpi_networkportwifis',
                                                               'joinparams'
                                                                       => $joinparams));
   }

}
?>
