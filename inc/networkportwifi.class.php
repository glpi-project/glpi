<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// NetworkPortWifi class : wifi instantitation of NetworkPort
/// @todo : add connection to other wifi networks
/// @since 0.84
class NetworkPortWifi extends NetworkPortInstantiation {


   static function getTypeName($nb=0) {
      return _n('Wifi port', 'Wifi ports', $nb);
   }


   function getNetworkCardInterestingFields() {
      return array('link.`specificity`' => 'mac');
  }


   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>\n";
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         echo "<td>" . WifiNetwork::getTypeName() . "&nbsp:</td><td>";
         Dropdown::show('WifiNetwork', array('value'  => $this->fields["wifinetworks_id"]));
         echo "</td>";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>\n";
         echo "<td>" . __('Wifi mode') . "</td>";
         echo "<td><select name='mode'>";
         echo "<option value=''></option>";
         foreach (WifiNetwork::getWifiCardModes() as $value => $name) {
            echo "<option value='$value'";
            if ($this->fields['mode'] == $value) {
               echo " selected";
            }
            echo ">$name</option>";
         }
         echo "</select></td>\n";
         echo "<td>" . __('Wifi protocol version') . "</td><td><select name='version'>";
         echo "<option value=''></option>";
         foreach (WifiNetwork::getWifiCardVersion() as $value) {
            echo "<option value='$value'";
            if ($this->fields['version'] == $value) {
               echo " selected";
            }
            echo ">$value</option>";
         }
         echo "</select></td>\n";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>\n";
         $this->showMacField($netport, $options);
         echo "</tr>\n";
      }
   }


   static function getShowForItemNumberColums() {
      return 7;
   }


   static function showForItemHeader() {

      echo "<th>" . __('Interface') . "</th>\n";
      echo "<th>" . __('MAC') . "</th>\n";
      echo "<th>" . __('ESSID') . "</th>\n";
      echo "<th>" . __('Wifi mode') . "</th>\n";
      echo "<th>" . __('Wifi protocol version') . "</th>\n";
      echo "<th>" . __('VLAN') . "</th>\n";
      echo "<th>" . __('Connected to')."</th>\n";
   }


   function showForItem(NetworkPort $netport, CommonDBTM $item, $canedit, $withtemplate='') {

      // Network card associated with this wifi port
      echo "<td>";
      $compdev = new Computer_Device();
      $device  = $compdev->getDeviceFromComputerDeviceID("DeviceNetworkCard",
                                                         $this->fields['computers_devicenetworkcards_id']);
      if ($device) {
         echo $device->getLink();
      } else {
         echo "&nbsp;";
      }
      echo "</td>";

      // Mac address
      echo "<td>".$this->fields['mac'] . "</td>\n";

      // ESSID
      echo "<td>".Dropdown::getDropdownName("glpi_wifinetworks",
                                            $this->fields["wifinetworks_id"])."</td>\n";

      // Wifi mode
      echo "<td>".$this->fields['mode'] . "</td>\n";

      // Wifi version
      echo "<td>".$this->fields['version'] . "</td>\n";

      // VLANs
      echo "<td>";
      NetworkPort_Vlan::showForNetworkPort($netport->fields["id"], $canedit,
                                           $withtemplate);
      echo "</td>";

      // Connections to the other Wifi networks
      echo "<td width='300' class='tab_bg_2'>&nbsp;";
      //self::showConnection($item, $this, $withtemplate);
      echo "</td>\n";
   }


   function getSearchOptions() {

      $tab = array();
      $tab['common'] = __('Characteristics');

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'mac';
      $tab[10]['name']          = __('MAC');
      $tab[10]['massiveaction'] = false;

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'mode';
      $tab[11]['name']          = __('Wifi mode');
      $tab[11]['massiveaction'] = false;

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'version';
      $tab[12]['name']          = __('Wifi protocol version');
      $tab[12]['massiveaction'] = false;

      $tab[13]['table']         = 'glpi_wifinetworks';
      $tab[13]['field']         = 'name';
      $tab[13]['name']          = WifiNetwork::getTypeName();
      $tab[13]['massiveaction'] = false;

      return $tab;
   }

}
?>
