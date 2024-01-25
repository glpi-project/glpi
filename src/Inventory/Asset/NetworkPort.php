<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @copyright 2010-2022 by the FusionInventory Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Inventory\Asset;

use Glpi\Inventory\Conf;
use Glpi\Inventory\FilesToJSON;
use Glpi\Toolbox\Sanitizer;
use NetworkPort as GlobalNetworkPort;
use NetworkPortAggregate;
use NetworkPortType;
use QueryParam;
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
    private $vlan_stmt;
    private $pvlan_stmt;

    public function prepare(): array
    {
        $this->connections = [];
        $this->aggregates = [];
        $this->vlans = [];

        $this->extra_data['\Glpi\Inventory\Asset\\' . $this->item->getType()] = null;
        $mapping = [
            'ifname'       => 'name',
            'ifnumber'     => 'logical_number',
            'ifportduplex' => 'portduplex',
            'ifinoctets'   => 'ifinbytes',
            'ips'          => 'ipaddress',
            'ifoutoctets'  => 'ifoutbytes'
        ];

        foreach ($this->data as $k => &$val) {
            $keep = true;
            if (!property_exists($val, 'instantiation_type')) {
                $val->instantiation_type = 'NetworkPortEthernet';
            }

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

            if ((!property_exists($val, 'name') || empty($val->name)) && property_exists($val, 'ifdescr')) {
                $val->name = $val->ifdescr;
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
    private function prepareConnections(\stdClass $port)
    {
        /** @var \DBmysql $DB */
        global $DB;

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
                // LLDP provides ChassisId (sysmac) and PortID as one of: local number, mac, interface name
                // We'll try to find the real mac and logical_number of the connection
                if (property_exists($connection, 'sysmac')) {
                    $field = null;
                    $val = null;
                    if (property_exists($connection, 'ifnumber')) {
                        $field = 'logical_number';
                        $val = $connection->ifnumber;
                    } else if (property_exists($connection, 'mac')) {
                        $field = 'mac';
                        $val = $connection->mac;
                    } else if (property_exists($connection, 'ifdescr')) {
                        $field = 'name';
                        $val = $connection->ifdescr;
                    }

                    if ($field && $val) {
                        $criteria = [
                            'SELECT'    => ['n1.logical_number', 'n1.mac'],
                            'FROM'      => \NetworkPort::getTable() . ' AS n1',
                            'WHERE'     => [
                                'n1.' . $field => $val,
                                'n2.mac'       => $connection->sysmac,
                            ]
                        ];
                        $criteria['LEFT JOIN'][\NetworkPort::getTable() . ' AS n2'][] =
                            new \QueryExpression("`n1`.`items_id`=`n2`.`items_id` AND `n1`.`itemtype`=`n2`.`itemtype`");

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $result = $iterator->current();
                            $connection->logical_number = (int)$result['logical_number'];
                            $connection->mac = $result['mac'];
                            // make sure mac does't get overwritten below
                            unset($lldp_mapping['sysmac']);
                        }
                    }
                }

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
    private function prepareVlans($vlans, $ifnumber)
    {
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
            unset($vlan->number);

            $results[$ifnumber][] = $vlan;
        }

        return $results;
    }

    private function handleLLDPConnection(\stdClass $port, int $netports_id)
    {
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

            if (count($this->connection_ports) != 1) {
                continue;
            }

            $connection_ports = current($this->connection_ports);
            if (count($connection_ports) == 1) { // single NetworkPort
                $connections_id = current($connection_ports);
            } else { // multiple NetworkPorts
                $networkPort = new \NetworkPort();
                foreach (array_keys($connection_ports) as $k) {
                    $networkPort->getFromDB($k);
                    if ($networkPort->fields['logical_number'] > 0) {
                        $connections_id = $k;
                        break;
                    }
                }
            }

            if ($connections_id) {
                $this->addPortsWiring($netports_id, $connections_id);
            }
        }
        unset($this->current_connection);
    }

    private function handleMacConnection(\stdClass $port, int $netports_id)
    {
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

        // Try to detect phone + computer on this port
        if (isset($this->connection_ports['Phone']) && count($found_macs) == 2) {
            trigger_error('Phone/Computer MAC linked', E_USER_WARNING);
            return;
        }
        if (count($found_macs) > 1) { // MultipleMac
           //do not manage MAC addresses if we found one NetworkEquipment
            if (isset($this->connections['NetworkEquipment'])) {
                return;
            }

            // see if we got a mix of different device types (other than computers)
            if (!(isset($this->connection_ports['Computer']) && count($this->connection_ports) == 1)) {
                $this->handleHub($found_macs, $netports_id);
                return;
            }

            $item_ids = [];
            $real_port_ids = [];
            foreach (array_keys($found_macs) as $k) {
                $networkPort = new \NetworkPort();
                $networkPort->getFromDB($k);

                $items_ids[$networkPort->fields['items_id']] = null;
                if ($networkPort->fields['logical_number'] > 0) {
                    $real_port_ids[$networkPort->fields['id']] = null;
                }
            }

            // multiple computers mean a hub
            if (count($items_ids) > 1) {
                $this->handleHub($found_macs, $netports_id);
            } else if (count($real_port_ids) == 1) {
                // the only remaining option is multiple macs on the same computer,
                $this->addPortsWiring($netports_id, array_key_first($real_port_ids));
            } else if (count($real_port_ids) > 1) {
                trigger_error('Multiple non-virtual NetworkPorts on the computer ('
                    . join(',', array_keys($real_port_ids)) . ')', E_USER_WARNING);
                return;
            }
        } else { // One mac on port
            if (count($this->connection_ports)) {
                $connections_id = current(current($this->connection_ports));
                $this->addPortsWiring($netports_id, $connections_id);
            }
            return;
        }
    }

    private function handleVlans(\stdClass $port, int $netports_id)
    {
        /** @var \DBmysql $DB */
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

        foreach ($iterator as $row) {
            $db_vlans[$row['id']] = $row;
        }

        if (count($db_vlans)) {
            foreach ($data as $key => $values) {
                foreach ($db_vlans as $keydb => $valuesdb) {
                    if (
                        ($values->name ?? '') == $valuesdb['name']
                        && ($values->tag ?? 0) == $valuesdb['tag']
                        && ($values->tagged ?? 0) == $valuesdb['tagged']
                    ) {
                        unset($data[$key]);
                        unset($db_vlans[$keydb]);
                        break;
                    }
                }
            }

            if (!$this->main_asset || !$this->main_asset->isPartial()) {
                foreach (array_keys($db_vlans) as $vlans_id) {
                    $pvlan->delete(['id' => $vlans_id]);
                }
            }
        }

        $db_vlans = [];
        $vlans_iterator = $DB->request([
            'FROM'   => \Vlan::getTable()
        ]);
        foreach ($vlans_iterator as $row) {
            $db_vlans[$row['name'] . '|' . $row['tag']] = $row['id'];
        }

        //add new vlans
        foreach ($data as $vlan_data) {
            $vlan_key = ($vlan_data->name ?? '') . '|' . $vlan_data->tag;
            $exists = isset($db_vlans[$vlan_key]);

            if (!$exists) {
                $stmt_columns = [
                    'name' => $vlan_data->name ?? '',
                    'tag'  => $vlan_data->tag
                ];
                $stmt_types = str_repeat('s', count($stmt_columns));

                if ($this->vlan_stmt === null) {
                    $reference = array_fill_keys(
                        array_keys($stmt_columns),
                        new QueryParam()
                    );
                    $insert_query = $DB->buildInsert(
                        $vlan->getTable(),
                        $reference
                    );
                    if (!$this->vlan_stmt = $DB->prepare($insert_query)) {
                        throw new \RuntimeException(sprintf('Error preparing query `%s`.', $insert_query));
                    }
                }

                $stmt_values = Sanitizer::encodeHtmlSpecialCharsRecursive(array_values($stmt_columns));
                $this->vlan_stmt->bind_param($stmt_types, ...$stmt_values);
                $DB->executeStatement($this->vlan_stmt);
                $vlans_id = $DB->insertId();

                $db_vlans[$vlan_key] = $vlans_id;
            }
            $vlans_id = $db_vlans[$vlan_key];

            $pvlan_stmt_columns = [
                'networkports_id' => $netports_id,
                'vlans_id'        => $vlans_id,
                'tagged'          => $vlan_data->tagged ?? 0
            ];
            $pvlan_stmt_types = str_repeat('s', count($pvlan_stmt_columns));

            if ($this->pvlan_stmt === null) {
                $reference = array_fill_keys(
                    array_keys($pvlan_stmt_columns),
                    new QueryParam()
                );
                $insert_query = $DB->buildInsert(
                    $pvlan->getTable(),
                    $reference
                );
                if (!$this->pvlan_stmt = $DB->prepare($insert_query)) {
                    throw new \RuntimeException(sprintf('Error preparing query `%s`.', $insert_query));
                }
            }

            $pvlan_stmt_values = Sanitizer::encodeHtmlSpecialCharsRecursive(array_values($pvlan_stmt_columns));
            $this->pvlan_stmt->bind_param($pvlan_stmt_types, ...$pvlan_stmt_values);
            $DB->executeStatement($this->pvlan_stmt);
        }
    }

    private function handleMetrics(\stdClass $port, int $netports_id)
    {
        $input = (array)$port;
        //only update networkport metric if needed
        if (isset($input['ifinbytes'], $input['ifoutbytes'], $input['ifinerrors'], $input['ifouterrors'])) {
            $netport = new GlobalNetworkPort();
            $input_db['id'] = $netports_id;
            $input_db['ifinbytes']   = $input['ifinbytes'];
            $input_db['ifoutbytes']  = $input['ifoutbytes'];
            $input_db['ifinerrors']  = $input['ifinerrors'];
            $input_db['ifouterrors'] = $input['ifouterrors'];
            $input_db['is_dynamic'] = true;
            $netport->update($input_db);
        }
    }

    private function prepareAggregations(\stdClass $port, int $netports_id)
    {
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

    private function handleAggregations()
    {
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
                $input['id'] = $netport_aggregate->add($input);
            }

            $input['networkports_id_list'] = array_values($aggregates);
            $netport_aggregate->update(Sanitizer::sanitize($input), false);
        }
    }

    private function handleConnections(\stdClass $port, int $netports_id)
    {
        if ($this->isLLDP($port)) {
            $this->handleLLDPConnection($port, $netports_id);
        } else {
            $this->handleMacConnection($port, $netports_id);
        }
    }

    public function handle()
    {
        $this->ports += $this->extra_data['\Glpi\Inventory\Asset\\' . $this->item->getType()]->getManagementPorts();
        $this->handlePorts();
    }

    protected function portUpdated(\stdClass $port, int $netports_id)
    {
        $this->portChanged($port, $netports_id);
    }

    protected function portCreated(\stdClass $port, int $netports_id)
    {
        $this->portChanged($port, $netports_id);
    }

    protected function portChanged(\stdClass $port, int $netports_id)
    {
        $this->handleConnections($port, $netports_id);
        $this->handleVlans($port, $netports_id);
        $this->prepareAggregations($port, $netports_id);
        $this->handleMetrics($port, $netports_id);
    }

    /**
     * After rule engine passed, update task (log) and create item if required
     *
     * @param integer $items_id id of the item (0 if new)
     * @param string  $itemtype Item type
     * @param integer $rules_id Matched rule id, if any
     * @param array   $ports_id Matched port ids, if any
     */
    public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = [])
    {
        if (!is_array($ports_id)) {
            $ports_id = [$ports_id]; // Handle compatibility with previous signature.
        }
        $netport = new \NetworkPort();
        if (empty($itemtype)) {
            $itemtype = 'Unmanaged';
        }
        $port = $this->current_connection ?? $this->current_port;
        $item = new $itemtype();

        if ($items_id == "0") {
           //not yet existing, create
            $input = (array)$port;
            if (property_exists($port, 'mac') && (!property_exists($port, 'name') || empty($port->name) || is_numeric($port->name) || preg_match('@([\w-]+)?(\d+)/\d+(/\d+)?@', $port->name))) {
                if ($name = $this->getNameForMac($port->mac)) {
                    $input['name'] = $name;
                }
            }
            $items_id = $item->add(Sanitizer::sanitize($input));
        }

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

        if (!count($ports_id)) {
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
                $input['ifdescr'] = $port->ifdescr;
            }

            if (property_exists($port, 'mac') && !empty($port->mac)) {
                $input['mac'] = $port->mac;
            }

            if (property_exists($port, 'logical_number') && !empty($port->logical_number)) {
                $input['logical_number'] = $port->logical_number;
            }

            $ports_id[] = $netport->add(Sanitizer::sanitize($input));
        }

        if (!isset($this->connection_ports[$itemtype])) {
            $this->connection_ports[$itemtype] = [];
        }
        foreach ($ports_id as $pid) {
            $this->connection_ports[$itemtype][$pid] = $pid;
        }
    }

    /**
     * Get port manufacturer name from OUIs database
     *
     * @param string $mac MAC address
     *
     * @return string|false
     */
    public function getNameForMac($mac)
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        $exploded = explode(':', $mac);

        if (isset($exploded[2])) {
            $ouis = $GLPI_CACHE->get('glpi_inventory_ouis');
            if ($ouis === null) {
                $jsonfile = new FilesToJSON();
                $ouis = json_decode(file_get_contents($jsonfile->getJsonFilePath('ouis')), true);
                $GLPI_CACHE->set('glpi_inventory_ouis', $ouis);
            }

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
    protected function isLLDP($port): bool
    {
        if (!property_exists($port, 'lldp') && !property_exists($port, 'cdp')) {
            return false;
        }
        return (bool)($port->lldp ?? $port->cdp);
    }

    public function handlePorts($itemtype = null, $items_id = null)
    {
        $mainasset = $this->extra_data['\Glpi\Inventory\Asset\\' . $this->item->getType()];

        //remove management port for Printer on netinventory
        //to prevent twice IP (NetworkPortAggregate / NetworkPortEthernet)
        if ($mainasset instanceof Printer && !$this->item->isNewItem()) {
            if (empty($this->extra_data['\Glpi\Inventory\Asset\\' . $this->item->getType()]->getManagementPorts())) {
                //remove all port management ports
                $networkport = new GlobalNetworkPort();
                $networkport->deleteByCriteria([
                    "itemtype"           => $this->item->getType(),
                    "items_id"           => $this->item->getID(),
                    "instantiation_type" => NetworkPortAggregate::getType(),
                    "name"               => "Management"
                ], 1);
            }
        }

        //handle ports for stacked switches
        if ($mainasset->isStackedSwitch()) {
            $bkp_ports = $this->ports;
            $stack_id = $mainasset->getStackId();
            $need_increment_index = false;
            $count_char = 0;
            foreach ($this->ports as $k => $val) {
                $matches = [];
                if (
                    preg_match('@[\w\s+]+(\d+)/[\w]@', $val->name, $matches)
                ) {
                    //reset increment when name lenght differ
                    //Gi0/0 then Gi0/0/1, Gi0/0/2, Gi0/0/3
                    if ($count_char && $count_char != strlen($val->name)) {
                        $need_increment_index = false;
                    }
                    $count_char = strlen($val->name);

                    //in case when port is related to chassis index 0 (stack_id)
                    //ex : GigabitEthernet 0/1    Gi0/0/1
                    //GLPI compute stack_id by starting with 1 (see: NetworkEquipment->getStackedSwitches)
                    //so we need to increment index to match related stack_id
                    if ((int) $matches[1] == 0 || $need_increment_index) {
                        $matches[1]++;
                        //current NetworkEquipement must have the index incremented
                        $need_increment_index = true;
                    }
                    if ($matches[1] != $stack_id) {
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
    public function handleHub($found_macs, $netports_id)
    {
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

    public function checkConf(Conf $conf): bool
    {
        return true;
    }

    public function getPart($part)
    {
        if (!in_array($part, ['connections', 'aggregates', 'vlans', 'connection_ports'])) {
            return;
        }

        return $this->$part;
    }

    /**
     * Add wiring between network ports.
     *
     * @param int $netports_id_1
     * @param int $netports_id_2
     *
     * @return bool
     */
    private function addPortsWiring(int $netports_id_1, int $netports_id_2): bool
    {
        if ($netports_id_1 == $netports_id_2) {
            //no wiring
            return false;
        }

        $wire = new \NetworkPort_NetworkPort();
        $current_port_1_opposite = $wire->getOppositeContact($netports_id_1);

        if ($current_port_1_opposite !== false && $current_port_1_opposite == $netports_id_2) {
            return true; // Connection already exists in DB
        }

        if ($current_port_1_opposite !== false) {
            $wire->delete($wire->fields); // Drop previous connection on self
        }

        if ($wire->getFromDBForNetworkPort($netports_id_2)) {
            $wire->delete($wire->fields); // Drop previous connection on opposite
        }

        return $wire->add([
            'networkports_id_1' => $netports_id_1,
            'networkports_id_2' => $netports_id_2,
        ]);
    }

    public function getItemtype(): string
    {
        return \NetworkPort::class;
    }
}
