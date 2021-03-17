<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Inventory\Asset;

use Glpi\Inventory\Conf;
use Glpi\Inventory\FilesToJSON;
use NetworkPortType;
use RuleImportAssetCollection;
use Unmanaged;

class NetworkPort extends InventoryAsset
{
   use InventoryNetworkPort {
      handlePorts as protected handlePortsTrait;
   }

   private $connections = [];
   private $aggregates = [];
   private $vlans = [];
   private $connection_ports = [];
   private $current_port;
   private $current_connection;


   public function prepare() :array {
      $this->connections = [];
      $this->aggregates = [];
      $this->vlans = [];

      $this->extra_data['\Glpi\Inventory\Asset\\' . $this->item->getType()] = null;
      $mapping = [
         'ifname'   => 'name',
         'ifnumber' => 'logical_number',
         'ifportduplex' => 'portduplex',
         'ifinoctets' => 'ifinbytes',
         'ifoutoctets' => 'ifoutbytes'
      ];

      foreach ($this->data as $k => &$val) {
         $keep = true;
         $val->instantiation_type = 'NetworkPortEthernet';
         $val->ipaddress = [];

         if (!property_exists($val, 'logical_number') && !property_exists($val, 'ifnumber')) {
            unset($this->data[$k]);
            continue;
         }

         if (property_exists($val, 'iftype')) {
            $inst_type = NetworkPortType::getInstantiationType($val->iftype);
            $keep = $inst_type !== false;
            if ($inst_type !== false && $inst_type !== true) {
               $val->instantiation_type = $inst_type;
            }
         }

         if (property_exists($val, 'aggregate') && property_exists($val, 'ifnumber')) {
            if (isset($val->aggregate[$val->ifnumber])) {
               $keep = true;
               $val->instantiation_type = 'NetworkPortAggregate';
            }
         }

         if (!$keep) {
            //port cannot be imported, remove from data source
            unset($this->data[$k]);
            continue;
         }

         if (property_exists($val, 'connections')) {
            $this->connections += $this->prepareConnections($val);
            unset($val->connections);
         }

         if (property_exists($val, 'vlans')) {
            $this->vlans += $this->prepareVlans($val->vlans, $val->ifnumber);
            unset($val->vlans);
         }

         if (property_exists($val, 'aggregate')) {
            $this->aggregates[$val->ifnumber] = [
               'aggregates' => array_fill_keys($val->aggregate, 0)
            ];
            unset($val->aggregate);
         }

         foreach ($mapping as $origin => $dest) {
            if (property_exists($val, $origin)) {
               $val->$dest = $val->$origin;
            }
         }

         if ((!property_exists($val, 'ifdescr') || empty($val->ifdescr)) && property_exists($val, 'name')) {
            $val->ifdescr = $val->name;
         }

         if (!property_exists($val, 'trunk')) {
            $val->trunk = 0;
         }
      }

      $this->ports += $this->data;

      return $this->data;
   }

   /**
    * Prepare network ports connections
    *
    * @param \stdClass $port Port instance
    *
    * @return array
    */
   private function prepareConnections(\stdClass $port) {
      $results = [];
      $connections = $port->connections;
      $ifnumber = $port->ifnumber;

      $lldp_mapping = [
         'ifnumber'  => 'logical_number',
         'model'     => 'networkportmodels_id',
         'sysmac'    => 'mac',
         'sysname'   => 'name'
      ];

      foreach ($connections as $connection) {
         $connection = (object)$connection;
         if ($this->isLLDP($port)) {
            foreach ($lldp_mapping as $origin => $dest) {
               if (property_exists($connection, $origin)) {
                  $connection->$dest = $connection->$origin;
               }
            }
            if (!property_exists($connection, 'name') && property_exists($connection, 'ifdescr')) {
               $connection->name = $connection->ifdescr;
            }
            $results[$ifnumber][] = $connection;
         } else if (property_exists($connection, 'mac') && !empty($connection->mac)) {
            $results[$ifnumber] = array_merge(($results[$ifnumber] ?? []), (array)$connection->mac);
         } else {
            continue;
         }
      }

      return $results;
   }

   /**
    * Prepare network ports vlans
    *
    * @param array $vlans Port vlans
    *
    * @return array
    */
   private function prepareVlans($vlans, $ifnumber) {
      $results = [];

      $mapping = [
         'number'  => 'tag'
      ];

      foreach ($vlans as $vlan) {
         $vlan = (object)$vlan;
         foreach ($mapping as $origin => $dest) {
            if (property_exists($vlan, $origin)) {
               $vlan->$dest = $vlan->$origin;
            }
         }
         $results[$ifnumber][] = $vlan;
      }

      return $results;
   }

   private function handleLLDPConnection(\stdClass $port, int $netports_id) {
      if (!property_exists($port, 'logical_number') || !isset($this->connections[$port->logical_number])) {
         return;
      }

      //reset, will be populated from rulepassed
      $this->connection_ports = [];
      $this->current_port = $port;

      $connections = $this->connections[$port->logical_number];
      foreach ($connections as $connections_id => $connection) {
          $this->current_connection = $connection;
          $input = ['entities_id' => $this->entities_id];
          $props = [
             'ifdescr',
             /*'sysdescr',*/
             'ifnumber',
             'mac',
             'model',
             'ip'
          ];

          foreach ($props as $prop) {
             if (property_exists($connection, $prop)) {
                $input[$prop] = $connection->$prop;
             }
          }

          $rule = new RuleImportAssetCollection();
          $rule->getCollectionPart();
          $rule->processAllRules($input, [], ['class' => $this]);

          if (count($this->connection_ports)) {
             $connections_id = current(current($this->connection_ports));

             if ($connections_id == $netports_id) {
                throw new \RuntimeException('Cannot wire a port to itself!');
             }

             $wire = new \NetworkPort_NetworkPort();
             if ($wire->getFromDBForNetworkPort([$netports_id])) {
                continue;
             }
             $contacts_id = $wire->getOppositeContact($netports_id);

             if (!($contacts_id && $contacts_id == $connections_id)) {
                $wire->add([
                   'networkports_id_1'  => $netports_id,
                   'networkports_id_2'  => $connections_id
                ], [], $this->withHistory());
             }
          }
      }
      unset($this->current_connection);
   }

   private function handleMacConnection(\stdClass $port, int $netports_id) {
      if (!property_exists($port, 'logical_number') || !isset($this->connections[$port->logical_number])) {
         return;
      }

      //reset, will be populated from rulepassed
      $this->connection_ports = [];
      $this->current_port = $port;
      $netport = new \NetworkPort();
      $netport->getFromDB($netports_id);

      $macs = $this->connections[$port->logical_number] ?? [];

      foreach ($macs as $mac) {
         $rule = new RuleImportAssetCollection();
         $rule->getCollectionPart();

         $this->current_port->mac = $mac;
         $rule->processAllRules(['mac' => $mac], [], ['class' => $this]);
      }

      $found_macs = [];
      foreach ($this->connection_ports as $ids) {
         $found_macs += $ids;
      }

      if (count($found_macs) > 1 && $netport->fields['trunk'] == 1) {
         return;
      }

      // Try detect phone + computer on this port
      if (isset($this->connection_ports['Phone']) && count($found_macs) == 2) {
         \Toolbox::logWarning('Phone/Computer MAC linked');
         return;
      }
      if (count($found_macs) > 1) { // MultipleMac
         //do not manage MAC addresses if we found one NetworkEquipment
         if (isset($this->connections['NetworkEquipment'])) {
            return;
         }
         $this->handleHub($found_macs, $netports_id);
      } else { // One mac on port
         if (count($this->connection_ports)) {
            $connections_id = current(current($this->connection_ports));

            if ($connections_id == $netports_id) {
               throw new \RuntimeException('Cannot wire a port to itself!');
            }

            $wire = new \NetworkPort_NetworkPort();
            $contacts_id = $wire->getOppositeContact($netports_id);

            if (!($contacts_id && $contacts_id == $connections_id)) {
               $wire->add([
                  'networkports_id_1'  => $netports_id,
                  'networkports_id_2'  => $connections_id
               ], [], $this->withHistory());
            }
         }
         return;
      }
   }

   private function handleVlans(\stdClass $port, int $netports_id) {
      global $DB;

      if (!property_exists($port, 'logical_number')) {
         return;
      }

      $vlan = new \Vlan();
      $pvlan = new \NetworkPort_Vlan();
      $vtable = $vlan->getTable();
      $pvtable = $pvlan->getTable();
      $data = $this->vlans[$port->logical_number] ?? [];

      $db_vlans = [];
      $iterator = $DB->request([
         'SELECT' => [
            "$pvtable.id",
            "$vtable.name",
            "$vtable.tag",
            "$pvtable.tagged",
         ],
         'FROM'   => $pvtable,
         'LEFT JOIN' => [
            $vtable => [
               'ON'  => [
                  $vtable  => 'id',
                  $pvtable => 'vlans_id'
               ]
            ]
         ],
         'WHERE'     => [
            'networkports_id' => $netports_id
         ]
      ]);

      while ($row = $iterator->next()) {
         $db_vlans[$row['id']] = $row;
      }

      if (count($db_vlans)) {
         foreach ($data as $key => $values) {
            foreach ($db_vlans as $keydb => $valuesdb) {
               if ($values->name == $valuesdb['name']
                  && ($values->tag ?? 0) == $valuesdb['tag']
                  && ($values->tagged ?? 0) == $valuesdb['tagged']
               ) {
                  unset($data[$key]);
                  unset($db_vlans[$keydb]);
                  break;
               }
            }
         }

         if (!$this->item->isPartial()) {
            foreach (array_keys($db_vlans) as $vlans_id) {
               $pvlan->delete(['id' => $vlans_id]);
            }
         }
      }

      $db_vlans = [];
      $vlans_iterator = $DB->request([
         'FROM'   => \Vlan::getTable()
      ]);
      while ($row = $vlans_iterator->next()) {
         $db_vlans[$row['name'] . '|' . $row['tag']] = $row['id'];
      }
      //add new vlans
      foreach ($data as $vlan_data) {
         $vlan_key = ($vlan_data->name ?? '') . '|' . $vlan_data->tag;
         $exists = isset($db_vlans[$vlan_key]);

         if (!$exists) {
            $vlans_id = $vlan->add((array)$vlan_data, [], $this->withHistory());
            $db_vlans[$vlan_key] = $vlans_id;
         }
         $vlans_id = $db_vlans[$vlan_key];

         $pvlan->add([
            'networkports_id' => $netports_id,
            'vlans_id'        => $vlans_id,
            'tagged'          => $vlan_data->tagged ?? 0
         ], [], false);
      }
   }

   private function prepareAggregations(\stdClass $port, int $netports_id) {
      if (!property_exists($port, 'logical_number')) {
         return;
      }

      if (!count($this->aggregates)) {
         //no aggregation to manage, pass.
         return;
      }

      foreach ($this->aggregates as $ifnumber => &$data) {
         if ($ifnumber == $port->logical_number) {
            //main part of the aggregate
            $data['networkports_id'] = $netports_id;
            return;
         } else {
            //last part of the aggregate. find ifnumber and keep ports_id
            foreach ($data['aggregates'] as $lifnumber => &$ldata) {
               if ($lifnumber == $port->logical_number) {
                  $ldata = $netports_id;
                  return;
               }
            }
         }
      }
   }

   private function handleAggregations() {
      $netport_aggregate = new \NetworkPortAggregate();

      foreach ($this->aggregates as $data) {
         $aggregates = $data['aggregates'];
         $netports_id = $data['networkports_id'];

         //create main aggregate port, if t does not exists
         if ($netport_aggregate->getFromDB($netports_id)) {
            $input = $netport_aggregate->fields;
         } else {
            $input = [
               'networkports_id'       => $netports_id,
               'networkports_id_list'  => []
            ];
            $input['id'] = $netport_aggregate->add($input, [], $this->withHistory());
         }

         $input['networkports_id_list'] = array_values($aggregates);
         $netport_aggregate->update($input, false);
      }
   }

   private function handleConnections(\stdClass $port, int $netports_id) {
      if ($this->isLLDP($port)) {
         $this->handleLLDPConnection($port, $netports_id);
      } else {
         $this->handleMacConnection($port, $netports_id);
      }
   }

   public function handle() {
      $this->ports += $this->extra_data['\Glpi\Inventory\Asset\\' . $this->item->getType()]->getManagementPorts();
      $this->handlePorts();
   }

   protected function portUpdated(\stdClass $port, int $netports_id) {
      $this->portChanged($port, $netports_id);
   }

   protected function portCreated(\stdClass $port, int $netports_id) {
      $this->portChanged($port, $netports_id);
   }

   protected function portChanged(\stdClass $port, int $netports_id) {
      $this->handleConnections($port, $netports_id);
      $this->handleVlans($port, $netports_id);
      $this->prepareAggregations($port, $netports_id);
   }

   /**
    * After rule engine passed, update task (log) and create item if required
    *
    * @param integer $items_id id of the item (0 if new)
    * @param string  $itemtype Item type
    * @param integer $rules_id Matched rule id, if any
    * @param integer $ports_id Matched port id, if any
    */
   public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = 0) {
      $netport = new \NetworkPort();
      if (empty($itemtype)) {
         $itemtype = 'Unmanaged';
      }
      $port = $this->current_connection ?? $this->current_port;
      $item = new $itemtype;

      if ($items_id == "0") {
         //not yet existing, create
         $input = (array)$port;
         if (property_exists($port, 'mac') && (!property_exists($port, 'name') || empty($port->name) || is_numeric($port->name) || preg_match('@([\w-]+)?(\d+)/\d+(/\d+)?@', $port->name))) {
            if ($name = $this->getNameForMac($port->mac)) {
               $input['name'] = $name;
            }
         }
         $items_id = $item->add($input, [], $this->withHistory());

         $rulesmatched = new \RuleMatchedLog();
         $agents_id = $this->agent->fields['id'];
         if (empty($agents_id)) {
            $agents_id = 0;
         }
         $inputrulelog = [
            'date'      => date('Y-m-d H:i:s'),
            'rules_id'  => $rules_id,
            'items_id'  => $items_id,
            'itemtype'  => $itemtype,
            'agents_id' => $agents_id,
            'method'    => 'inventory'
         ];
         $rulesmatched->add($inputrulelog, [], false);
         $rulesmatched->cleanOlddata($items_id, $itemtype);
      }

      if (!$ports_id) {
         //create network port
         $input = [
            'items_id'           => $items_id,
            'itemtype'           => $itemtype,
            'instantiation_type' => 'NetworkPortEthernet'
         ];
         if (property_exists($port, 'ip')) {
            $input += [
               '_create_children'         => 1,
               'NetworkName_name'         => '',
               'NetworkName_fqdns_id'     => 0,
               'NetworkName__ipaddresses' => [
                  '-1' => $port->ip
               ]
            ];
         }

         if (property_exists($port, 'mac')) {
            if ($name = $this->getNameForMac($port->mac)) {
               $input['name'] = $name;
            }
         }

         if (property_exists($port, 'ifdescr') && !empty($port->ifdescr)) {
            $input['name'] = $port->ifdescr;
         }

         if (property_exists($port, 'mac') && !empty($port->mac)) {
            $input['mac'] = $port->mac;
         }
         $ports_id = $netport->add(\Toolbox::addslashes_deep($input), [], $this->withHistory());
      }

      if (!isset($this->connection_ports[$itemtype])) {
         $this->connection_ports[$itemtype] = [];
      }
      $this->connection_ports[$itemtype][$ports_id] = $ports_id;
   }

   /**
    * Get port manufacturer name from OUIs database
    *
    * @param string $mac MAC address
    *
    * @return string|false
    */
   public function getNameForMac($mac) {
      global $GLPI_CACHE;

      $exploded = explode(':', $mac);

      if (isset($exploded[2])) {
         if (!$GLPI_CACHE->has('glpi_inventory_ouis')) {
            $jsonfile = new FilesToJSON();
            $ouis = json_decode(file_get_contents($jsonfile->getJsonFilePath('ouis')), true);
            $GLPI_CACHE->set('glpi_inventory_ouis', $ouis);
         }
         $ouis = $ouis ?? $GLPI_CACHE->get('glpi_inventory_ouis');

         $mac = sprintf('%s:%s:%s', $exploded[0], $exploded[1], $exploded[2]);
         return $ouis[strtoupper($mac)] ?? false;
      }

      return false;
   }

   /**
    * Check if port connections are LLDP
    *
    * @param \stdClass $port Port
    *
    * @return boolean
    */
   protected function isLLDP($port): bool {
      if (!property_exists($port, 'lldp') && !property_exists($port, 'cdp')) {
         return false;
      }
      return (bool)($port->lldp ?? $port->cdp);
   }

   public function handlePorts($itemtype = null, $items_id = null) {
      $mainasset = $this->extra_data['\Glpi\Inventory\Asset\\' . $this->item->getType()];

      //handle ports for stacked switches
      if ($mainasset->isStackedSwitch()) {
         $bkp_ports = $this->ports;
         foreach ($this->ports as $k => $val) {
            $matches = [];
            if (preg_match('@[\w-]+(\d+)/\d+/\d+@', $val->name, $matches)) {
               if ($matches[1] != $mainasset->getStackId()) {
                  //port attached to another stack entry, remove from here
                  unset($this->ports[$k]);
                  continue;
               }
            }
         }
      }

      $this->handlePortsTrait($itemtype, $items_id);
      if (isset($bkp_ports)) {
         //all ports must be kept for next stack iteration
         $this->ports = $bkp_ports;
      }
   }

   /**
    * Handle a hub (many MAC on a port means we face a hub)
    *
    * @param array   $found_macs  ID of ports foudn by mac
    * @param integer $netports_id Network port id
    *
    * @return void
    */
   public function handleHub($found_macs, $netports_id) {
      $hubs_id = 0;

      $link = new \NetworkPort_NetworkPort();
      $netport = new \NetworkPort();

      $id = $link->getOppositeContact($netports_id);
      $netport->getFromDB($id);

      if ($id && $netport->fields['itemtype'] == Unmanaged::getType()) {
         $unmanaged = new Unmanaged();
         $unmanaged->getFromDB($netport->fields['items_id']);
         if ($unmanaged->fields['hub'] == 1) {
            //a hub is connected, updated connections
            $hubs_id = $unmanaged->fields['id'];
         } else {
            //direct connections, drop to recreate
            $link->disconnectFrom($id);
         }
      }

      if (!$hubs_id) {
         //create direct connection
         $hubs_id = $link->createHub($netports_id, $this->entities_id);
      }

      $glpi_ports = [];
      $dbports = $netport->find([
         'items_id' => $hubs_id,
         'itemtype' => Unmanaged::getType()
      ]);
      foreach ($dbports as $dbport) {
         $id = $link->getOppositeContact($dbport['id']);
         if ($id) {
            $glpi_ports[$id] = $dbport['id'];
         }
      }

      foreach ($found_macs as $ports_id) {
         if (!isset($glpi_ports[$ports_id])) {
            // Connect port (port found in GLPI)
            $link->connectToHub($ports_id, $hubs_id);
         }
      }
   }

   public function checkConf(Conf $conf): bool {
      return true;
   }

   public function getPart($part) {
      if (!in_array($part, ['connections', 'aggregates', 'vlans', 'connection_ports'])) {
         return;
      }

      return $this->$part;
   }
}
