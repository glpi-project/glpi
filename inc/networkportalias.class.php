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

/// NetworkPortAlias class : alias instantiation of NetworkPort. An alias can be use to define VLAN
/// tagged ports. It is use in old version of Linux to define several IP addresses to a given port.
/// @since 0.84
class NetworkPortAlias extends NetworkPortInstantiation {


   static function getTypeName($nb=0) {
     return __('Alias port');
   }


   static function getShowForItemNumberColums() {
      return 3;
   }


   static function showForItemHeader() {
      echo "<th>" . __('Origin port') . "</th>\n";
      echo "<th>". __('MAC') ."</th>\n";
      echo "<th>". __('VLAN') ."</th>\n";
   }


   function prepareInput($input) {

      // Try to get mac address from the instantiation ...

      if (!isset($input['mac']) && isset($input['networkports_id'])) {
         $networkPort = new NetworkPort();
         if ($networkPort->getFromDB($input['networkports_id'])) {
            $networkPortInstaciation = $networkPort->getInstantiation();
            $input['mac']            = $networkPortInstaciation->getField('mac');
         }
      }

      return $input;
   }


   function prepareInputForAdd($input) {

      $input = $this->prepareInput($input);

      if (!is_array($input)) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   function prepareInputForUpdate($input) {

      $input = $this->prepareInput($input);

      if (!is_array($input)) {
         return false;
      }
      return parent::prepareInputForUpdate($input);
   }


   function showForItem(NetworkPort $netport, CommonDBTM $item, $canedit, $withtemplate='') {

      echo "<td>".$this->showNetworkPortForItem()."</td>\n";
      echo "<td>".$this->fields["mac"]."</td>\n";
      echo "<td>";
      NetworkPort_Vlan::showForNetworkPort($netport->fields["id"], $canedit, $withtemplate);
      echo "</td>\n";
   }


   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      echo "<tr class='tab_bg_1'>";
      $this->showMacField($netport, $options);
      $this->showNetworkPortSelector($recursiveItems, false);
      echo "</tr>";
   }

}
?>
