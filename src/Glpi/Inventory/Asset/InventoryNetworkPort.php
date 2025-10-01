<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Blacklist;
use DBmysqlIterator;
use FQDNLabel;
use Glpi\DBAL\QueryParam;
use Glpi\Inventory\Conf;
use Glpi\Inventory\MainAsset\MainAsset;
use IPAddress;
use IPNetwork;
use Item_DeviceNetworkCard;
use NetworkName;
use NetworkPort;
use NetworkPortAggregate;
use RuntimeException;
use stdClass;
use Toolbox;
use Unmanaged;

trait InventoryNetworkPort
{
    protected $ports = [];
    protected $ipnetwork_stmt;
    protected $idevice_stmt;
    protected $networks = [];
    protected $itemtype;
    private $items_id;

    public function handle()
    {
        parent::handle();
        $this->handlePorts();
    }

    /**
     * Get network ports
     *
     * @return array
     */
    public function getNetworkPorts(): array
    {
        return $this->ports;
    }

    /**
     * Add network ports
     *
     * @param $ports
     *
     * @return $this
     */
    public function addNetworkPorts($ports): self
    {
        $this->ports += $ports;
        return $this;
    }

    private function isMainPartial(): bool
    {
        if ($this instanceof MainAsset) {
            return $this->isPartial();
        } else {
            if (isset($this->main_asset) && method_exists($this->main_asset, 'isPartial')) {
                return $this->main_asset->isPartial();
            }
        }

        return false;
    }

    /**
     * Manage network ports
     *
     * @param string  $itemtype Item type, will take current item per default
     * @param integer $items_id Item ID, will take current item per default
     *
     * @return void
     */
    public function handlePorts($itemtype = null, $items_id = null)
    {
        if (!$this->checkPortsConf($this->conf)) {
            return;
        }

        $this->itemtype = $itemtype ?? $this->item->getType();
        $this->items_id = $items_id ?? $this->item->fields['id'];

        if (!$this->isMainPartial()) {
            $this->cleanUnmanageds();
        }

        $this->handleDeletesManagementPorts();
        $this->handleIpNetworks();
        $this->handleUpdates();
        $this->handleCreates();

        if ($this instanceof \Glpi\Inventory\Asset\NetworkPort) {
            $this->handleAggregations();
        }

        $this->itemtype = null;
        $this->items_id = null;
    }

    /**
     * Handle devices that would no longer be unmanaged
     *
     * @return void
     */
    private function cleanUnmanageds()
    {
        global $DB;

        $networkport = new NetworkPort();
        $unmanaged = new Unmanaged();

        $criteria = [
            'FROM'   => NetworkPort::getTable(),
            'WHERE'  => [
                'itemtype'  => new QueryParam(),
                'mac'       => new QueryParam(),
            ],
        ];

        $it = new DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $query = $it->getSql();
        $stmt = $DB->prepare($query);

        foreach ($this->ports as $port) {
            if (!$this->isMainPartial() && property_exists($port, 'mac') && $port->mac != '') {
                $stmt->bind_param(
                    'ss',
                    ...([Unmanaged::class, $port->mac])
                );
                $DB->executeStatement($stmt);
                $results = $stmt->get_result();

                if ($results->num_rows > 0) {
                    $row = $results->fetch_object();
                    $unmanageds_id = $row->items_id;
                    $input = [
                        'logical_number'  => $port->logical_number,
                        'itemtype'        => $this->itemtype,
                        'items_id'        => $this->items_id,
                        'is_dynamic'      => 1,
                        'name'            => $port->name,
                    ];

                    $networkport->update($input);
                    $unmanaged->delete(['id' => $unmanageds_id], true);
                }
            }
        }
    }

    /**
     * Store IP networks and prepare ports to manage later
     *
     * @return void
     */
    private function handleIpNetworks()
    {
        global $DB;

        $ipnetwork = new IPNetwork();
        foreach ($this->ports as $port) {
            if (
                !property_exists($port, 'gateway') || $port->gateway == ''
                || !property_exists($port, 'netmask') || $port->netmask == ''
                || !property_exists($port, 'subnet') ||  $port->subnet  == ''
            ) {
                // Ignore ports with incomplete information
                continue;
            }

            if ($this->ipnetwork_stmt == null) {
                $criteria = [
                    'COUNT'  => 'cnt',
                    'FROM'   => IPNetwork::getTable(),
                    'WHERE'  => [
                        'entities_id'  => new QueryParam(),
                        'address'      => new QueryParam(),
                        'netmask'      => new QueryParam(),
                        'gateway'      => new QueryParam(),
                    ],
                ];

                $it = new DBmysqlIterator(null);
                $it->buildQuery($criteria);
                $query = $it->getSql();
                $stmt = $DB->prepare($query);
                $this->ipnetwork_stmt = $stmt;
            }
            $stmt = $this->ipnetwork_stmt;

            $res = $stmt->bind_param(
                'ssss',
                $this->entities_id,
                $port->subnet,
                $port->netmask,
                $port->gateway
            );
            if (false === $res) {
                $msg = "Error binding params";
                throw new RuntimeException($msg);
            }

            $DB->executeStatement($stmt);
            $results = $stmt->get_result();

            $row = $results->fetch_object();
            $count = $row->cnt;

            if ($count == 0) {
                $input = [
                    'name'         => sprintf('%s/%s - %s', $port->subnet, $port->netmask, $port->gateway),
                    'network'      => sprintf('%s/%s', $port->subnet, $port->netmask),
                    'gateway'      => $port->gateway,
                    'entities_id'  => $this->entities_id,
                    '_no_message'  => true, //to prevent 'Network already defined in visible entities' message on add
                ];
                $ipnetwork->add($input);
            }
        }
    }

    /**
     * Add a network port into database
     *
     * @param stdClass $port Port data
     *
     * @return integer
     */
    private function addNetworkPort(stdClass $port)
    {
        $networkport = new NetworkPort();

        $input  = (array) $port;
        foreach ($input as $key => $data) {
            if (is_array($data)) {
                unset($input[$key]);
            }
        }
        $input = array_merge(
            $input,
            [
                'entities_id'  => $this->entities_id,
                'items_id'     => $this->items_id,
                'itemtype'     => $this->itemtype,
                'is_dynamic'   => 1,
            ]
        );

        if (!isset($input['trunk']) || empty($input['trunk'])) {
            $input['trunk'] = 0;
        }

        $netports_id = $networkport->add($input);
        return $netports_id;
    }

    /**
     * Add a network name into database
     *
     * @param integer $items_id Port id
     * @param string  $name     Network name
     *
     * @return integer
     */
    protected function addNetworkName($items_id, $name = null)
    {
        $networkname = new NetworkName();
        $input = [
            'entities_id'  => $this->entities_id,
            'is_dynamic'   => 1,
            'items_id'     => $items_id,
            'is_recursive' => 0,
            'itemtype'     => 'NetworkPort',
        ];

        if ($name !== null) {
            $input['name'] = $name;
        }

        $netname_id = $networkname->add($input);
        return $netname_id;
    }

    /**
     * Add several ip addresses into database
     *
     * @param array   $ips      IP adresses to add
     * @param integer $items_id NetworkName id
     *
     * @return void
     */
    private function addIPAddresses(array $ips, $items_id)
    {
        $ipaddress = new IPAddress();
        $blacklist = new Blacklist();
        foreach ($ips as $ip) {
            if ('' != $blacklist->process(Blacklist::IP, $ip)) {
                $input = [
                    'items_id'     => $items_id,
                    'itemtype'     => 'NetworkName',
                    'name'         => $ip,
                    'is_dynamic'   => 1,
                ];
                $ipaddress->add($input);
            }
        }
    }

    /**
     * Hanlde network instantiation
     *
     * @return void
     */
    private function handleUpdates()
    {
        global $DB;

        $db_ports = [];
        $networkport = new NetworkPort();

        $np_dyn_props = ['logical_number', 'ifstatus', 'ifinternalstatus', 'ifalias', 'is_dynamic'];
        $iterator = $DB->request([
            'SELECT' => array_merge(['id', 'name', 'mac', 'instantiation_type'], $np_dyn_props),
            'FROM'   => 'glpi_networkports',
            'WHERE'  => [
                'items_id'     => $this->items_id,
                'itemtype'     => $this->itemtype,
            ],
        ]);
        foreach ($iterator as $row) {
            $id = $row['id'];
            unset($row['id']);
            if (is_null($row['mac'])) {
                $row['mac'] = '';
            }
            foreach (['name', 'mac'] as $field) {
                if ($row[$field] !== null) {
                    $row[$field] = strtolower($row[$field]);
                }
            }
            $db_ports[$id] = $row;
        }

        $netname_stmt = null;

        $ports = $this->ports;
        if (method_exists($this, 'getManagementPorts')) {
            $ports += $this->getManagementPorts();
        }
        foreach ($ports as $key => $data) {
            foreach ($db_ports as $keydb => $datadb) {
                $dbdata_copy = [];
                foreach (array_merge(['instantiation_type'], $np_dyn_props) as $k) {
                    $dbdata_copy[$k] = $datadb[$k];
                    unset($datadb[$k]);
                }

                $comp_data = [];
                foreach (['name', 'mac'] as $field) {
                    if (property_exists($data, $field)) {
                        $comp_data[$field] = strtolower($data->$field);
                    } else {
                        $comp_data[$field] = "";
                    }
                }

                //check if port exists in database
                if ($comp_data != $datadb) {
                    continue;
                }

                $criteria = [];
                foreach ($np_dyn_props as $k) {
                    if (property_exists($data, $k) && $data->$k != $dbdata_copy[$k]) {
                        $criteria[$k] = $data->$k;
                    }
                }

                // force dynamic for manually added NetworkPort
                if (count($criteria) || !$dbdata_copy['is_dynamic']) {
                    $criteria['id'] = $keydb;
                    $criteria['is_dynamic'] = 1;
                    $networkport->update($criteria);
                }

                // force NetworkPortEthernet type if no instantiation_type and mac is set
                if (!property_exists($data, 'instantiation_type') && property_exists($data, 'mac') && !empty($data->mac)) {
                    $data->instantiation_type = 'NetworkPortEthernet';
                }

                //check for instantiation_type switch for NetworkPort
                if (
                    property_exists($data, 'instantiation_type')
                    && $data->instantiation_type != $dbdata_copy['instantiation_type']
                ) {
                    $networkport->getFromDB($keydb);
                    $networkport->switchInstantiationType($data->instantiation_type);
                }

                //handle instantiation type
                if (property_exists($data, 'instantiation_type')) {
                    $type = $data->instantiation_type;
                    //handle only ethernet and fiberchannel
                    $this->handleInstantiation($type, $data, $keydb, true);
                }

                $ips = $data->ipaddress ?? [];
                if (count($ips)) {
                    //handle network name
                    if ($netname_stmt == null) {
                        $criteria = [
                            'SELECT' => 'id',
                            'FROM'   => NetworkName::getTable(),
                            'WHERE'  => [
                                'itemtype'  => 'NetworkPort',
                                'items_id'  => new QueryParam(),
                            ],
                        ];

                        $it = new DBmysqlIterator(null);
                        $it->buildQuery($criteria);
                        $query = $it->getSql();
                        $netname_stmt = $DB->prepare($query);
                    }

                    $netname_stmt->bind_param(
                        's',
                        $keydb
                    );
                    $DB->executeStatement($netname_stmt);
                    $results = $netname_stmt->get_result();

                    if ($results->num_rows) {
                        $row = $results->fetch_object();
                        $netname_id = $row->id;
                    } else {
                        if (!empty($datadb['name'])) {
                            $netname = Toolbox::slugify($datadb['name']);
                            if (!FQDNLabel::checkFQDNLabel($netname)) {
                                $netname = null;
                            }
                        } else {
                            $netname = null;
                        }
                        $netname_id = $this->addNetworkName($keydb, $netname);
                    }

                    //Handle ipaddresses
                    $db_addresses = [];
                    $iterator = $DB->request([
                        'SELECT' => ['id', 'name'],
                        'FROM'   => 'glpi_ipaddresses',
                        'WHERE'  => [
                            'items_id'  => $netname_id,
                            'itemtype'  => 'NetworkName',
                        ],
                    ]);

                    foreach ($iterator as $db_data) {
                        $db_addresses[$db_data['id']] = $db_data['name'];
                    }

                    foreach ($ips as $ip_key => $ip_data) {
                        foreach ($db_addresses as $db_ip_key => $db_ip_data) {
                            if ($ip_data == $db_ip_data) {
                                unset($ips[$ip_key]);
                                unset($db_addresses[$db_ip_key]);
                                //result found in db, useless to continue
                                break 1;
                            }
                        }
                    }

                    if (count($db_addresses) && count($ips)) {
                        $ipaddress = new IPAddress();
                        //deleted IP addresses
                        foreach (array_keys($db_addresses) as $id_ipa) {
                            $ipaddress->delete(['id' => $id_ipa], true);
                        }
                    }

                    if (count($ips)) {
                        $this->addIPAddresses($ips, $netname_id);
                    }
                }

                unset($db_ports[$keydb]);
                unset($this->networks[$key]);
                unset($this->ports[$key]);
                if (method_exists($this, 'getManagementPorts') && method_exists($this, 'setManagementPorts')) {
                    $managements = $this->getManagementPorts();
                    unset($managements[$key]);
                    $this->setManagementPorts($managements);
                }

                $this->portUpdated($data, $keydb);
            }
        }

        //delete remaining network ports, if any
        if (count($db_ports)) {
            foreach ($db_ports as $netpid => $netpdata) {
                if ($netpdata['name'] != 'management' && $netpdata['is_dynamic']) { //prevent removing internal management port or non dynamic ports
                    $networkport->delete(['id' => $netpid], true);
                }
            }
        }
    }

    protected function portUpdated(stdClass $port, int $netports_id)
    {
        //does nothing
    }

    /**
     * Handle network port instantiation
     *
     * @param string    $type     Instantiation class name
     * @param stdClass $data Data
     * @param integer   $ports_id NetworkPort id
     * @param boolean   $load     Whether to load db results
     *
     * @return void
     */
    private function handleInstantiation($type, $data, $ports_id, $load)
    {
        global $DB;

        if (!in_array($type, ['NetworkPortEthernet', 'NetworkPortFiberchannel'])) {
            return;
        }

        $instance = new $type();
        $input = [];

        if ($instance->getFromDB($ports_id)) {
            $input = $instance->fields;
        }
        $input['networkports_id'] = $ports_id;

        if (property_exists($data, 'speed')) {
            $input['speed'] = $data->speed;
            $input['speed_other_value'] = $data->speed;
        }

        if (property_exists($data, 'wwn')) {
            $input['wwn'] = $data->wwn;
        }

        if (property_exists($data, 'mac')) {
            if ($this->idevice_stmt == null) {
                $criteria = [
                    'SELECT' => 'id',
                    'FROM'   => Item_DeviceNetworkCard::getTable(),
                    'WHERE'  => [
                        'itemtype'  => new QueryParam(),
                        'items_id'  => new QueryParam(),
                        'mac'       => new QueryParam(),
                    ],
                ];

                $it = new DBmysqlIterator(null);
                $it->buildQuery($criteria);
                $query = $it->getSql();
                $this->idevice_stmt = $DB->prepare($query);
            }

            $stmt = $this->idevice_stmt;
            $stmt->bind_param(
                'sss',
                $this->itemtype,
                $this->items_id,
                $data->mac
            );
            $DB->executeStatement($stmt);
            $results = $stmt->get_result();

            if ($results->num_rows > 0) {
                $row = $results->fetch_object();
                $input['items_devicenetworkcards_id'] = $row->id;
            }
        }

        //store instance
        if ($instance->isNewItem()) {
            $instance->add($input);
        } else {
            $instance->update($input);
        }
    }

    /**
     * Handle network ports, name and instantiation creation
     *
     * @return void
     */
    private function handleCreates()
    {
        $ports = $this->ports;
        if (method_exists($this, 'getManagementPorts')) {
            $ports += $this->getManagementPorts();
        }
        foreach ($ports as $port) {
            // force NetworkPortEthernet type if no instantiation_type and mac is set
            if (!property_exists($port, 'instantiation_type') && property_exists($port, 'mac') && !empty($port->mac)) {
                $port->instantiation_type = 'NetworkPortEthernet';
            }

            $netports_id = $this->addNetworkPort($port);
            if (count(($port->ipaddress ?? []))) {
                if (property_exists($port, 'name')) {
                    $netname = Toolbox::slugify($port->name);
                    if (!FQDNLabel::checkFQDNLabel($netname)) {
                        $netname = null;
                    }
                } else {
                    $netname = null;
                }
                $netnames_id = $this->addNetworkName($netports_id, $netname);
                $this->addIPAddresses($port->ipaddress, $netnames_id);
            }

            if (property_exists($port, 'instantiation_type')) {
                $type = $port->instantiation_type;
                $this->handleInstantiation($type, $port, $netports_id, false);
            }
            $this->portCreated($port, $netports_id);
        }
    }

    /**
     * Delete Management Ports if needed
     *
     * @return void
     */
    private function handleDeletesManagementPorts()
    {
        if (method_exists($this, 'getManagementPorts')) {
            if (empty($this->getManagementPorts())) {
                //remove all port management ports
                $networkport = new NetworkPort();
                $networkport->deleteByCriteria([
                    "itemtype"           => $this->itemtype,
                    "items_id"           => $this->items_id,
                    "instantiation_type" => NetworkPortAggregate::getType(),
                    "name"               => "Management",
                ], true);
            }
        }
    }

    protected function portCreated(stdClass $port, int $netports_id)
    {
        //does nothing
    }

    public function checkPortsConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        return $conf->component_networkcard == 1 && in_array($this->item::class, $CFG_GLPI['networkport_types']);
    }
}
