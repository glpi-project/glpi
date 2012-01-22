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

/// NetworkPortEthernet class : Ethernet instantiation of NetworkPort
/// @since 0.84
class NetworkPortEthernet extends NetworkPortInstantiation {


   static function getTypeName($nb=0) {
     return __('Ethernet');
   }


   function getNetworkCardInterestingFields() {
      return array('link.`specificity`' => 'mac');
   }


   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>";
         $this->showNetpointField($netport, $options, $recursiveItems);
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         echo "</tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Ethernet port type') . "</td><td>\n";
      Dropdown::showFromArray('type', array('T'  => __('Twisted pair (RJ-45)'),
                                            'SX' => __('Multimode fiber'),
                                            'LX' => __('Single mode fiber')),
                              array('value' => $this->fields['type']));
      echo "</td>";
      echo "<td>" . __('Ethernet port speed') . "</td><td>\n";
      Dropdown::showFromArray('speed', array(0     => "",
                                             10    => 10,
                                             100   => 100,
                                             1000  => 1000,
                                             10000 => 10000),
                              array('value' => $this->fields['speed']));
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>\n";
      $this->showMacField($netport, $options);
      echo "</tr>\n";
  }


   static function getHTMLTableHeadersForNetworkPort(&$table, $canedit) {

      $table->addHeader(__('Interface'), "Interface");
      $table->addHeader(__('MAC'), "MAC");
      $table->addHeader(__('VLAN'), "VLAN");
      $table->addHeader(__('Network outlet'), "Outlet");
      $table->addHeader(__('Connected to'), "Connected");

   }


   function getHTMLTableForNetworkPort(NetworkPort $netport, CommonDBTM $item, &$table,
                                       $withtemplate, $canedit) {

      $compdev = new Computer_Device();
      $device = $compdev->getDeviceFromComputerDeviceID("DeviceNetworkCard",
                $this->fields['computers_devicenetworkcards_id']);

      if ($device) {
         $table->addElement($device->getLink(), "Interface", $this->getID(), $netport->getID());
      }

      $table->addElement($netport->fields["mac"], "MAC", $this->getID(),$netport->getID());

      $table->addElement(Dropdown::getDropdownName("glpi_netpoints",
                                                   $this->fields["netpoints_id"]),
                         "Outlet", $this->getID(),$netport->getID());

      NetworkPort_Vlan::getHTMLTableForNetworkPort($netport->getID(), $table, $canedit);

      $table->addElement(array('function' => array(__CLASS__, 'showConnection'),
                               'parameters' => array($item, $netport, $withtemplate)),
                         "Connected", $this->getID(),$netport->getID());
   }


   /**
    * Display a connection of a networking port
    *
    * @param $device1      the device of the port
    * @param $netport      to be displayed
    * @param $withtemplate (default '')
   **/
   static function showConnection($device1, $netport, $withtemplate='') {

      if (!$device1->can($device1->fields["id"], 'r')) {
         return false;
      }

      $contact = new NetworkPort_NetworkPort();
      $canedit = $device1->can($device1->fields["id"], 'w');
      $ID      = $netport->fields["id"];

      if ($contact_id = $contact->getOppositeContact($ID)) {
         $netport->getFromDB($contact_id);

        if ($device2 = getItemForItemtype($netport->fields["itemtype"])) {

            if ($device2->getFromDB($netport->fields["items_id"])) {
               echo "\n<table width='100%'>\n";
               echo "<tr " . ($device2->fields["is_deleted"] ? "class='tab_bg_2_2'" : "") . ">";
               echo "<td><span class='b'>";

               if ($device2->can($device2->fields["id"], 'r')) {
                  echo $netport->getLink();
                  echo "</span>\n";
                  Html::showToolTip($netport->fields['comment']);
                  echo "&nbsp;".__('on') . "<span class='b'>".$device2->getLink()."</span>";

                  if ($device1->fields["entities_id"] != $device2->fields["entities_id"]) {
                     echo "<br>(". Dropdown::getDropdownName("glpi_entities",
                                                             $device2->getEntityID()) .")";
                  }

                  // 'w' on dev1 + 'r' on dev2 OR 'r' on dev1 + 'w' on dev2
                  if ($canedit || $device2->can($device2->fields["id"], 'w')) {
                     echo "</td>\n<td class='right'><span class='b'>";

                     if ($withtemplate != 2) {
                        echo "<a href=\"".$netport->getFormURL()."?disconnect=".
                              "disconnect&amp;id=".$contact->fields['id']."\">". __('Disconnect').
                             "</a>";
                     } else {
                        "&nbsp;";
                     }

                     echo "</span>";
                  }

               } else {
                  if (rtrim($netport->fields["name"]) != "") {
                     echo $netport->fields["name"];
                  } else {
                     _e('Without name');
                  }
                  echo "</span> " . __('on') . " <span class='b'>".$device2->getName()."</span>";
                  echo "<br>(" .Dropdown::getDropdownName("glpi_entities",
                                                          $device2->getEntityID()) .")";
               }

               echo "</td></tr></table>\n";
            }
         }

      } else {
         echo "\n<table width='100%'><tr>";

         if ($canedit) {
            echo "<td>";

            if ($withtemplate != 2 && $withtemplate != 1) {
               self::dropdownConnect($ID, array('name'        => 'dport',
                                                'entity'      => $device1->fields["entities_id"],
                                                'entity_sons' => $device1->isRecursive()));
            } else {
               echo "&nbsp;";
            }

            echo "</td>\n";
         }

         echo "<td><div id='not_connected_display$ID'>" . __('Not connected.') . "</div></td>";
         echo "</tr></table>\n";
      }
   }


   /**
    * Make a select box for  connected port
    *
    * @param $ID                 ID of the current port to connect
    * @param $options   array of possible options
    *    - name : string / name of the select (default is networkports_id)
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - entity_sons : boolean / if entity restrict specified auto select its sons
    *                   only available if entity is a single value not an array (default false)
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownConnect($ID, $options=array()) {
      global $CFG_GLPI;

      $p['name']        = 'networkports_id';
      $p['comments']    = 1;
      $p['entity']      = -1;
      $p['entity_sons'] = false;

     if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      // Manage entity_sons
      if (!($p['entity']<0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            _e('entity_sons options are not available with array of entity');
         } else {
            $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
         }
      }

      $rand = mt_rand();
      echo "<select name='itemtype[$ID]' id='itemtype$rand'>";
      echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";

      foreach ($CFG_GLPI["networkport_types"] as $key => $itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            echo "<option value='".$itemtype."'>".$item->getTypeName(1)."</option>";
         } else {
            unset($CFG_GLPI["networkport_types"][$key]);
         }
      }
      echo "</select>";

      $params = array('itemtype'        => '__VALUE__',
                      'entity_restrict' => $p['entity'],
                      'current'         => $ID,
                      'comments'        => $p['comments'],
                      'myname'          => $p['name']);

      Ajax::updateItemOnSelectEvent("itemtype$rand", "show_".$p['name']."$rand",
                                    $CFG_GLPI["root_doc"].
                                       "/ajax/dropdownConnectEthernetPortDeviceType.php",
                                    $params);

      echo "<span id='show_".$p['name']."$rand'>&nbsp;</span>\n";

      return $rand;
   }


   function getSearchOptions() {

      $tab = array();
      $tab['common']            = __('Characteristics');

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'mac';
      $tab[10]['name']          = __('MAC');
      $tab[10]['massiveaction'] = false;

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'type';
      $tab[11]['name']          = __('Ethernet port type');
      $tab[11]['massiveaction'] = false;

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'speed';
      $tab[12]['name']          = __('Ethernet port speed');
      $tab[12]['massiveaction'] = false;

      return $tab;

   }

}
?>
