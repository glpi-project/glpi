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


   /**
    * @see inc/NetworkPortInstantiation::showInstantiationForm()
   **/
   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>\n";
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         echo "<td>" . WifiNetwork::getTypeName(1) . "</td><td>";
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


   static function getInstantiationHTMLTable_Headers(HTMLTable_Group $group,
                                                     HTMLTable_SuperHeader $header,
                                                     $options=array()) {

      // TODO : try to transfert to Interface DeviceNetworkCard::getHTMLTableHeaderForItem...
      $group->addHeader($header, 'Interface', __('Interface'));
      $group->addHeader($header, 'MAC', __('MAC'));
      $group->addHeader($header, 'ESSID', __('ESSID'));
      $group->addHeader($header, 'Mode', __('Wifi mode'));
      $group->addHeader($header, 'Version', __('Wifi protocol version'));
      NetworkPort_Vlan::getHTMLTableHeaderForItem('NetworkPort', $group, $header);
      //$group->addHeader($header, 'Connected', __('Connected to'));
   }


   function getInstantiationHTMLTable_(NetworkPort $netport, CommonDBTM $item,
                                       HTMLTable_Row $row,
                                       $canedit, $options=array()) {

      $compdev = new Computer_Device();
      $device  = $compdev->getDeviceFromComputerDeviceID("DeviceNetworkCard",
                 $this->fields['computers_devicenetworkcards_id']);

      $row->addCell($row->getHeader('Instantiation', 'Interface'),
                    ($device ? $device->getLink() : ''));

      $row->addCell($row->getHeader('Instantiation', 'MAC'), $netport->fields["mac"]);

      $row->addCell($row->getHeader('Instantiation', 'ESSID'),
                    Dropdown::getDropdownName("glpi_wifinetworks",
                                              $this->fields["wifinetworks_id"]));

      $row->addCell($row->getHeader('Instantiation', 'Mode'), $this->fields['mode']);

      $row->addCell($row->getHeader('Instantiation', 'Version'), $this->fields['version']);

      NetworkPort_Vlan::getHTMLTableForItem($row, $netport, NULL, $options);

      /*
      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      } else {
         $withtemplate = '';
      }

      $table->addElement(array('function' => array(__CLASS__, 'showConnection'),
                               'parameters' => array($item, $netport, $withtemplate)),
                         "Connected", $this->getID(),$netport->getID());
      */

   }

   function getSearchOptions() {

      $tab = array();
      $tab['common']            = __('Characteristics');

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
      $tab[13]['name']          = WifiNetwork::getTypeName(1);
      $tab[13]['massiveaction'] = false;

      return $tab;
   }

}
?>
