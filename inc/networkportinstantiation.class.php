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
 * NetworkPortInstantiation class
 *
 * Represents the type of a given network port. As such, its ID field is the same one than the ID
 * of the network port it instantiates. This class don't have any table associated. It just
 * provides usefull and default methods for the instantiations.
 * Several kind of instanciations are available for a given port :
 *    - NetworkPortLocal
 *    - NetworkPortEthernet
 *    - NetworkPortWifi
 *    - NetworkPortAggregate
 *    - NetworkPortAlias
 *
 * @since 0.84
 *
**/
class NetworkPortInstantiation extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action   = false;

   // From CommonDBChild
   static public $itemtype       = 'NetworkPort';
   static public $items_id       = 'networkports_id';
   public $dohistory             = false;

   // Instantiation properties
   public $canHaveVLAN           = true;
   public $canHaveVirtualPort    = true;
   public $haveMAC               = true;

   static function getIndexName() {
      return 'networkports_id';
   }


   /**
    * Show the instanciation element for the form of the NetworkPort
    * By default, just print that there is no parameter for this type of NetworkPort
    *
    * @param $netport               the port that owns this instantiation
    *                               (usefull, for instance to get network port attributs
    * @param $options         array of options given to NetworkPort::showForm
    * @param $recursiveItems        list of the items on which this port is attached
   **/
   function showInstantiationForm(NetworkPort $netport, $options, $recursiveItems) {

      echo "<tr><td colspan='4' class='center'>".__('No options available for this port type.').
           "</td></tr>";
   }


   function prepareInput($input) {

      // Try to get mac address from the instantiation ...
      if (!empty($input['mac'])) {
         $input['mac'] = strtolower($input['mac']);
      }
      return $input;
   }


   function prepareInputForAdd($input) {
      return parent::prepareInputForAdd($this->prepareInput($input));
   }


   function prepareInputForUpdate($input) {
      return parent::prepareInputForUpdate($this->prepareInput($input));
   }


   /**
    * Get all the instantiation specific options to display
    *
    * @return array containing the options
   **/
   static function getInstantiationNetworkPortDisplayOptions() {
      return [];
   }


   /**
    * Get the instantiation specific options to display that applies for all instantiations
    *
    * @return array containing the options
   **/
   static function getGlobalInstantiationNetworkPortDisplayOptions() {
      return ['mac'           => ['name'    => __('MAC'),
                                            'default' => true],
                   'vlans'         => ['name'    => __('VLAN'),
                                            'default' => false],
                   'virtual_ports' => ['name'    => __('Virtual ports'),
                                            'default' => false],
                   'port_opposite' => ['name'    => __('Opposite link'),
                                            'default' => false]];
   }


   /**
    * Get HTMLTable columns headers for a given item type
    * Beware : the internet informations are "sons" of each instantiation ...
    *
    * @param $group           HTMLTableGroup object
    * @param $super           HTMLTableSuperHeader object
    * @param $internet_super  HTMLTableSuperHeader object for the internet sub part (default NULL)
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array of possible options:
    *       - 'dont_display' : array of the columns that must not be display
    *
    * @return the father group for the Internet Informations ...
   **/
   function getInstantiationHTMLTableHeaders(HTMLTableGroup $group, HTMLTableSuperHeader $super,
                                             HTMLTableSuperHeader $internet_super = null,
                                             HTMLTableHeader $father = null,
                                             array $options = []) {

      $display_options = &$options['display_options'];

      if (($this->canHaveVirtualPort) && ($display_options['virtual_ports'])) {
         $father = $group->addHeader('VirtualPorts', '<i>'.__('Virtual ports').'</i>',
                                     $super, $father);
      }

      if (($this->canHaveVLAN) && ($display_options['vlans'])) {
         NetworkPort_Vlan::getHTMLTableHeader('NetworkPort', $group, $super, $father, $options);
      }

      if (($this->haveMAC) && ($display_options['mac'])) {
         $group->addHeader('MAC', __('MAC'), $super, $father);
      }

      if (($internet_super !== null) && ($display_options['internet'])) {
         NetworkName::getHTMLTableHeader('NetworkPort', $group, $internet_super, $father, $options);
      }

      return null;
   }


   /**
    * Get HTMLTable row for a given network port and a given extremity when two ports are
    * existing on a link (NetworkPort_NetworkPort).
    *
    * @param $netport         NetworkPort object (contains item)
    * @param $row             HTMLTableRow object
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array of possible options:
    *       - 'dont_display' : array of the elements that must not be display
    *       - 'withtemplate' : integer withtemplate param
    *
    * @return the father cell for the Internet Informations ...
   **/
   protected function getPeerInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                                    HTMLTableCell $father = null,
                                                    array $options = []) {

      self::getInstantiationHTMLTable($netport, $row, $father, $options);
      return null;

   }


   /**
    * Replacement of NetworkPortInstantiation::getInstantiationHTMLTable() method when two ports
    * share the same link (NetworkPort_NetworkPort). Used, for instance by Dialup and Ethernet.
    *
    * @see NetworkPortInstantiation::getInstantiationHTMLTable()
    *
    * @param $netport         NetworkPort object (contains item)
    * @param $row             HTMLTableRow object
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array of possible options:
    *       - 'dont_display' : array of the elements that must not be display
    *       - 'withtemplate' : integer withtemplate param
    *
    * @return the father cell for the Internet Informations ...
   **/
   function getInstantiationHTMLTableWithPeer(NetworkPort $netport, HTMLTableRow $row,
                                              HTMLTableCell $father = null, array $options = []) {

      $connect_cell_value = [['function'   => [__CLASS__, 'showConnection'],
                                        'parameters' => [clone $netport]]];

      $oppositePort = NetworkPort_NetworkPort::getOpposite($netport);
      if ($oppositePort !== false) {

         $opposite_options            = $options;
         $opposite_options['canedit'] = false;
         $display_options             = $options['display_options'];

         if ($display_options['port_opposite']) {
            $cell          = $row->addCell($row->getHeaderByName('Instantiation', 'Connected'),
                                           __('Local network port'));

            $opposite_cell = $row->addCell($row->getHeaderByName('Instantiation', 'Connected'),
                                           $connect_cell_value);
            $opposite_cell->setAttributForTheRow(['class' => 'htmltable_upper_separation_cell']);

            $oppositeInstantiationPort = $oppositePort->getInstantiation();
            if ($oppositeInstantiationPort !== false) {
               $oppositeInstantiationPort->getPeerInstantiationHTMLTable($oppositePort, $row,
                                                                         $opposite_cell,
                                                                         $opposite_options);
            }

         } else {
            $cell = $row->addCell($row->getHeaderByName('Instantiation', 'Connected'),
                                  $connect_cell_value);
         }

      } else {
         $cell = $row->addCell($row->getHeaderByName('Instantiation', 'Connected'),
                               $connect_cell_value);
      }

      $this->getPeerInstantiationHTMLTable($netport, $row, $cell, $options);
      return $cell;
   }


   /**
    * Get HTMLTable row for a given item
    *
    * @param $netport         NetworkPort object (contains item)
    * @param $row             HTMLTableRow object
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array of possible options:
    *       - 'dont_display' : array of the elements that must not be display
    *       - 'withtemplate' : integer withtemplate param
    *
    * @return the father cell for the Internet Informations ...
   **/
   function getInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                      HTMLTableCell $father = null, array $options = []) {
      global $DB;

      $display_options = $options['display_options'];

      if (($this->canHaveVirtualPort) && ($display_options['virtual_ports'])) {

         $virtual_header = $row->getHeaderByName('Instantiation', 'VirtualPorts');

         $iterator = $DB->request([
            'FROM' => new \QueryUnion(
               [
                  [
                     'SELECT' => 'networkports_id',
                     'FROM'   => 'glpi_networkportaliases',
                     'WHERE'  => ['networkports_id_alias' => $netport->getID()]
                  ], [
                     'SELECT' => 'networkports_id',
                     'FROM'   => 'glpi_networkportaggregates',
                     'WHERE'  => ['networkports_id_list' => ['LIKE', '%"'.$netport->getID().'"%']]
                  ]
               ],
               false,
               'networkports'
            )
         ]);

         if (count($iterator)) {
            $new_father = $row->addCell($virtual_header, __('this port'), $father);
         } else {
            $new_father = $row->addCell($virtual_header, '', $father);
         }

         foreach ($iterator as $networkports_ids) {

            $virtualPort = new NetworkPort();

            if ($virtualPort->getFromDB($networkports_ids['networkports_id'])) {

               $cell_value = '<i>'.$virtualPort->getLink().'</i>';

               $virtual_cell = $row->addCell($virtual_header, $cell_value, $father);
               $virtual_cell->setAttributForTheRow(['class' => 'htmltable_upper_separation_cell']);

               if (($this->canHaveVLAN) && ($display_options['vlans'])) {
                  NetworkPort_Vlan::getHTMLTableCellsForItem($row, $virtualPort, $virtual_cell,
                                                             $options);
               }

               if ($display_options['internet']) {
                  NetworkName::getHTMLTableCellsForItem($row, $virtualPort, $virtual_cell,
                                                        $options);
               }
            }
            unset($virtualPort);
         }

         $father = $new_father;
      }

      if (($this->canHaveVLAN) && ($display_options['vlans'])) {
         NetworkPort_Vlan::getHTMLTableCellsForItem($row, $netport, $father, $options);
      }

      if (($this->haveMAC) && ($display_options['mac']) && (!empty($netport->fields["mac"]))) {
         $row->addCell($row->getHeaderByName('Instantiation', 'MAC'),
                       $netport->fields["mac"], $father);
      }

      if ($display_options['internet']) {
         NetworkName::getHTMLTableCellsForItem($row, $netport, $father, $options);
      }

      return null;
   }


   /**
    * Get all NetworkPort and NetworkEquipments that have a specific MAC address
    *
    * @param $mac                      address to search
    * @param $wildcard_search boolean  true if we search with wildcard (false by default)
    *
    * @return (array) each value of the array (corresponding to one NetworkPort) is an array of the
    *                 items from the master item to the NetworkPort
   **/
   static function getItemsByMac($mac, $wildcard_search = false) {
      global $DB;

      $mac = strtolower($mac);
      if ($wildcard_search) {
         $count = 0;
         $mac = str_replace('*', '%', $mac, $count);
         if ($count == 0) {
            $mac = '%'.$mac.'%';
         }
         $relation = "LIKE '$mac'";
      } else {
         $relation = "= '$mac'";
      }

      $macItemWithItems = [];

      foreach (['NetworkPort'] as $netporttype) {
         $netport = new $netporttype();

         $query = "SELECT `id`
                   FROM `".$netport->getTable()."`
                   WHERE `mac` $relation ";

         foreach ($DB->request($query) as $element) {
            if ($netport->getFromDB($element['id'])) {

               if ($netport instanceof CommonDBChild) {
                  $macItemWithItems[] = array_merge(array_reverse($netport->recursivelyGetItems()),
                                                    [clone $netport]);
               } else {
                  $macItemWithItems[] = [clone $netport];
               }
            }
         }
      }

      return $macItemWithItems;
   }


   /**
    * Get an Object ID by its MAC address (only if one result is found in the entity)
    *
    * @param $value  the mac address
    * @param $entity the entity to look for
    *
    * @return an array containing the object ID
    *         or an empty array is no value of serverals ID where found
   **/
   static function getUniqueItemByMac($value, $entity) {

      $macs_with_items = self::getItemsByMac($value);
      if (count($macs_with_items)) {
         foreach ($macs_with_items as $key => $tab) {
            if (isset($tab[0])
                && ($tab[0]->getEntityID() != $entity
                    || $tab[0]->isDeleted()
                    || $tab[0]->isTemplate())) {
               unset($macs_with_items[$key]);
            }
         }
      }

      if (count($macs_with_items)) {
         // Get the first item that is matching entity
         foreach ($macs_with_items as $items) {
            foreach ($items as $item) {
               if ($item->getEntityID() == $entity) {
                  $result = ["id"       => $item->getID(),
                                  "itemtype" => $item->getType()];
                  unset($macs_with_items);
                  return $result;
               }
            }
         }
      }
      return [];
   }


   /**
    * In case of NetworkPort attached to a network card, list the fields that must be duplicate
    * from the network card to the network port (mac address, port type, ...)
    *
    * @return an array with SQL field (for instance : device.`type`) => form field (type)
   **/
   function getNetworkCardInterestingFields() {
      return [];
   }


   /**
    * Select which network card to attach to the current NetworkPort (for the moment, only ethernet
    * and wifi ports). Whenever a card is attached, its information (mac, type, ...) are
    * autmatically set to the required field.
    *
    * @param $netport               NetworkPort object :the port that owns this instantiation
    *                               (usefull, for instance to get network port attributs
    * @param $options         array of options given to NetworkPort::showForm
    * @param $recursiveItems        list of the items on which this port is attached
   **/
   function showNetworkCardField(NetworkPort $netport, $options = [], $recursiveItems = []) {
      global $DB;

      echo "<td>" . __('Network card') . "</td>\n";
      echo "<td>";

      if (count($recursiveItems)  > 0) {

         $lastItem = $recursiveItems[count($recursiveItems) - 1];

         // Network card association is only available for computers
         if (($lastItem->getType() == 'Computer')
             && !$options['several']) {

            // Query each link to network cards
            $query = "SELECT link.`id` AS link_id,
                             device.`designation` AS name";

            // $deviceFields contains the list of fields to update
            $deviceFields = [];
            foreach ($this->getNetworkCardInterestingFields() as $SQL_field => $form_field) {
               $deviceFields[] = $form_field;
               $query         .= ", $SQL_field AS $form_field";
            }
            $query .= " FROM `glpi_devicenetworkcards` AS device,
                             `glpi_items_devicenetworkcards` AS link
                        WHERE link.`items_id` = '".$lastItem->getID()."'
                              AND link.`itemtype` = '".$lastItem->getType()."'
                              AND device.`id` = link.`devicenetworkcards_id`";

            // Add the javascript to update each field
            echo "\n<script type=\"text/javascript\">
   var deviceAttributs = [];\n";

            $deviceNames = [0 => ""]; // First option : no network card
            foreach ($DB->request($query) as $availableDevice) {
               $linkID               = $availableDevice['link_id'];
               $deviceNames[$linkID] = $availableDevice['name'];
               if (isset($availableDevice['mac'])) {
                  $deviceNames[$linkID] = sprintf(__('%1$s - %2$s'), $deviceNames[$linkID],
                                                  $availableDevice['mac']);
               }

               // get fields that must be copied from those of the network card
               $deviceInformations = [];
               foreach ($deviceFields as $field) {
                  // No gettext here
                  $deviceInformations[] = "$field: '".$availableDevice[$field]."'";
               }
               //addslashes_deep($deviceInformations);
               // Fill the javascript array
               echo "  deviceAttributs[$linkID] = {".implode(', ', $deviceInformations)."};\n";
            }

            // And add the javascript function that updates the other fields
            echo "
   function updateNetworkPortForm(devID) {
      for (var fieldName in deviceAttributs[devID]) {
         var field=document.getElementsByName(fieldName)[0];
         if ((field == undefined) || (deviceAttributs[devID][fieldName] == undefined))
            continue;
         field.value = deviceAttributs[devID][fieldName];
      }
   }
</script>\n";

            if (count($deviceNames) > 0) {
               $options = ['value'
                                 => $this->fields['items_devicenetworkcards_id'],
                                'on_change'
                                 => 'updateNetworkPortForm(this.options[this.selectedIndex].value)'];
               Dropdown::showFromArray('items_devicenetworkcards_id', $deviceNames, $options);
            } else {
                echo __('No network card available');
            }
         } else {
            echo __('Equipment without network card');
         }
      } else {
         echo __('Item not linked to an object');
      }
      echo "</td>";
   }


   /**
    * Display the MAC field. Used by Ethernet, Wifi, Aggregate and alias NetworkPorts
    *
    * @param $netport         NetworkPort object : the port that owns this instantiation
    *                         (usefull, for instance to get network port attributs
    * @param $options   array of options given to NetworkPort::showForm
   **/
   function showMacField(NetworkPort $netport, $options = []) {

      // Show device MAC adresses
      echo "<td>" . __('MAC') ."</td>\n<td>";
      Html::autocompletionTextField($netport, "mac");
      echo "</td>\n";
   }


   /**
    * Display the Netpoint field. Used by Ethernet, and Migration
    *
    * @param $netport               NetworkPort object :the port that owns this instantiation
    *                               (usefull, for instance to get network port attributs
    * @param $options         array of options given to NetworkPort::showForm
    * @param $recursiveItems        list of the items on which this port is attached
   **/
   function showNetpointField(NetworkPort $netport, $options = [], $recursiveItems = []) {

      echo "<td>" . __('Network outlet') . "</td>\n";
      echo "<td>";
      if (count($recursiveItems) > 0) {
         $lastItem = $recursiveItems[count($recursiveItems) - 1];
         Netpoint::dropdownNetpoint("netpoints_id", $this->fields["netpoints_id"],
                                    $lastItem->fields['locations_id'], 1, $lastItem->getEntityID(),
                                    $netport->fields["itemtype"]);
      } else {
         echo __('item not linked to an object');
      }
      echo "</td>";
   }


   /**
    * \brief display the attached NetworkPort
    *
    * NetworkPortAlias and NetworkPortAggregate are based on other physical network ports
    * (Ethernet or Wifi). This method displays the physical network ports.
   **/
   function getInstantiationNetworkPortHTMLTable() {

      $netports = [];

      // Manage alias
      if (isset($this->fields['networkports_id_alias'])) {
         $links_id = $this->fields['networkports_id_alias'];
         $netport  = new NetworkPort();
         if ($netport->getFromDB($links_id)) {
            $netports[] = $netport->getLink();
         }
      }
      // Manage aggregate
      if (isset($this->fields['networkports_id_list'])) {
         $links_id = $this->fields['networkports_id_list'];
         $netport  = new NetworkPort();
         foreach ($links_id as $id) {
            if ($netport->getFromDB($id)) {
               $netports[] = $netport->getLink();
            }
         }
      }

      if (count($netports) > 0) {
         return implode('<br>', $netports);
      }

      return "&nbsp;";
   }


   /**
    * \brief select which NetworkPort to attach
    *
    * NetworkPortAlias and NetworkPortAggregate ara based on other physical network ports
    * (Ethernet or Wifi). This method Allows us to select which one to select.
    *
    * @param $recursiveItems
    * @param $origin          NetworkPortAlias are based on one NetworkPort wherever
    *                         NetworkPortAggregate are based on several NetworkPort.
   **/
   function showNetworkPortSelector($recursiveItems, $origin) {
      global $DB;

      if (count($recursiveItems) == 0) {
         return;
      }

      $lastItem = $recursiveItems[count($recursiveItems) - 1];

      echo "<td>" . __('Origin port') . "</td><td>\n";
      $links_id      = [];
      $netport_types = ['NetworkPortEthernet', 'NetworkPortWifi'];
      $selectOptions = [];
      $possible_ports = [];

      switch ($origin) {
         case 'NetworkPortAlias' :
            $possible_ports[-1]         = Dropdown::EMPTY_VALUE;
            $field_name                 = 'networkports_id_alias';
            $selectOptions['multiple']  = false;
            $selectOptions['on_change'] = 'updateForm(this.options[this.selectedIndex].value)';
            $netport_types[]            = 'NetworkPortAggregate';
            break;

         case 'NetworkPortAggregate' :
            $field_name                       = 'networkports_id_list';
            $selectOptions['multiple']        = true;
            $selectOptions['size']            = 4;
            $netport_types[]                  = 'NetworkPortAlias';
            break;
      }

      if (isset($this->fields[$field_name])) {
         if (is_array($this->fields[$field_name])) {
            $selectOptions['values'] = $this->fields[$field_name];
         } else {
            $selectOptions['values'] = [$this->fields[$field_name]];
         }
      }

      $macAddresses = [];
      foreach ($netport_types as $netport_type) {
         $instantiationTable = getTableForItemType($netport_type);
         $query = "SELECT port.`id`, port.`name`, port.`mac`
                   FROM `glpi_networkports` AS port
                   WHERE `items_id` = '".$lastItem->getID()."'
                         AND `itemtype` = '".$lastItem->getType()."'
                         AND `instantiation_type` = '$netport_type'
                   ORDER BY `logical_number`, `name`";

         $result = $DB->query($query);

         if ($DB->numrows($result) > 0) {
            $array_element_name = call_user_func([$netport_type, 'getTypeName'],
                                                 $DB->numrows($result));
            $possible_ports[$array_element_name] = [];

            while ($portEntry = $DB->fetch_assoc($result)) {
               $macAddresses[$portEntry['id']] = $portEntry['mac'];
               if (!empty($portEntry['mac'])) {
                  $portEntry['name'] = sprintf(__('%1$s - %2$s'), $portEntry['name'],
                                               $portEntry['mac']);
               }
               $possible_ports[$array_element_name][$portEntry['id']] = $portEntry['name'];
            }
         }
      }

      if (!$selectOptions['multiple']) {
         echo "\n<script type=\"text/javascript\">
        var device_mac_addresses = [];\n";
         foreach ($macAddresses as $port_id => $macAddress) {
            echo "  device_mac_addresses[$port_id] = '$macAddress'\n";
         }
         echo "   function updateForm(devID) {
      var field=document.getElementsByName('mac')[0];
      if ((field != undefined) && (device_mac_addresses[devID] != undefined))
         field.value = device_mac_addresses[devID];
   }
</script>\n";
      }

      Dropdown::showFromArray($field_name, $possible_ports, $selectOptions);
      echo "</td>\n";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == "NetworkPort") {
         $instantiation = $item->getInstantiation();
         if ($instantiation !== false) {
            $log = new Log();
            //TRANS: %1$s is a type, %2$s is a table
            return sprintf(__('%1$s - %2$s'), $instantiation->getTypeName(),
                           $log->getTabNameForItem($instantiation));
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == "NetworkPort") {
         $instantiation = $item->getInstantiation();
         if ($instantiation !== false) {
            return Log::displayTabContentForItem($instantiation, $tabnum, $withtemplate);
         }
      }
   }


   /**
    * @param $tab          array
    * @param $joinparams   array
    **/
   static function getSearchOptionsToAddForInstantiation(array &$tab, array $joinparams) {
   }


   /**
    * Display a connection of a networking port
    *
    * @param $netport      to be displayed
    * @param $edit         boolean permit to edit ? (false by default)
   **/
   static function showConnection($netport, $edit = false) {

      $ID = $netport->fields["id"];
      if (empty($ID)) {
         return false;
      }

      $device1 = $netport->getItem();

      if (!$device1->can($device1->getID(), READ)) {
         return false;
      }
      $canedit      = $device1->canEdit($device1->fields["id"]);
      $relations_id = 0;
      $oppositePort = NetworkPort_NetworkPort::getOpposite($netport, $relations_id);

      if ($oppositePort !== false) {
         $device2 = $oppositePort->getItem();

         if ($device2->can($device2->fields["id"], READ)) {
            $networklink = $oppositePort->getLink();
            $tooltip     = Html::showToolTip($oppositePort->fields['comment'],
                                             ['display' => false]);
            $netlink     = sprintf(__('%1$s %2$s'),
                                   "<span class='b'>".$networklink."</span>\n", $tooltip);
            //TRANS: %1$s and %2$s are links
            echo "&nbsp;". sprintf(__('%1$s on %2$s'), $netlink,
                                   "<span class='b'>".$device2->getLink()."</span>");
            if ($device1->fields["entities_id"] != $device2->fields["entities_id"]) {
               echo "<br>(". Dropdown::getDropdownName("glpi_entities",
                                                       $device2->getEntityID()) .")";
            }

            // write rights on dev1 + READ on dev2 OR READ on dev1 + write rights on dev2
            if ($canedit
                || $device2->canEdit($device2->fields["id"])) {
               echo "&nbsp;";
               Html::showSimpleForm($oppositePort->getFormURL(), 'disconnect', _x('button', 'Disconnect'),
                                    ['id' => $relations_id]);
            }

         } else {
            if (rtrim($oppositePort->fields["name"]) != "") {
               $netname = $oppositePort->fields["name"];
            } else {
               $netname = __('Without name');
            }
            printf(__('%1$s on %2$s'), "<span class='b'>".$netname."</span>",
                   "<span class='b'>".$device2->getName()."</span>");
            echo "<br>(" .Dropdown::getDropdownName("glpi_entities",
                                                    $device2->getEntityID()) .")";
         }

      } else {
         echo "<div id='not_connected_display$ID'>" . __('Not connected.') . "</div>";
         if ($canedit) {
            if (!$device1->isTemplate()) {
               if ($edit) {
                  self::dropdownConnect($ID,
                                        ['name'        => 'NetworkPortConnect_networkports_id_2',
                                              'entity'      => $device1->fields["entities_id"],
                                              'entity_sons' => $device1->isRecursive()]);
               } else {
                  echo "<a href=\"".$netport->getFormURLWithID($ID)."\">". _x('button', 'Connect')."</a>";
               }
            } else {
               echo "&nbsp;";
            }
         }
      }
   }


   /**
    * Make a select box for  connected port
    *
    * @param $ID                 ID of the current port to connect
    * @param $options   array    of possible options:
    *    - name : string / name of the select (default is networkports_id)
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - entity_sons : boolean / if entity restrict specified auto select its sons
    *                   only available if entity is a single value not an array (default false)
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownConnect($ID, $options = []) {
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
      if (!($p['entity'] < 0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            echo "entity_sons options is not available with entity option as array";
         } else {
            $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
         }
      }

      echo "<input type='hidden' name='NetworkPortConnect_networkports_id_1'value='$ID'>";
      $rand = Dropdown::showItemTypes('NetworkPortConnect_itemtype', $CFG_GLPI["networkport_types"] );

      $params = ['itemtype'           => '__VALUE__',
                      'entity_restrict'    => $p['entity'],
                      'networkports_id'    => $ID,
                      'comments'           => $p['comments'],
                      'myname'             => $p['name'],
                      'instantiation_type' => get_called_class()];

      Ajax::updateItemOnSelectEvent("dropdown_NetworkPortConnect_itemtype$rand",
                                    "show_".$p['name']."$rand",
                                    $CFG_GLPI["root_doc"].
                                       "/ajax/dropdownConnectNetworkPortDeviceType.php",
                                    $params);

      echo "<span id='show_".$p['name']."$rand'>&nbsp;</span>\n";

      return $rand;
   }
}
