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

/// NetworkPort_NetworkPort class
class NetworkPort_NetworkPort extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1           = 'NetworkPort';
    public static $items_id_1           = 'networkports_id_1';
    public static $itemtype_2           = 'NetworkPort';
    public static $items_id_2           = 'networkports_id_2';

    public static $log_history_1_add    = Log::HISTORY_CONNECT_DEVICE;
    public static $log_history_2_add    = Log::HISTORY_CONNECT_DEVICE;

    public static $log_history_1_delete = Log::HISTORY_DISCONNECT_DEVICE;
    public static $log_history_2_delete = Log::HISTORY_DISCONNECT_DEVICE;


    /**
     * Retrieve an item from the database
     *
     * @param integer $ID ID of the item to get
     *
     * @return boolean  true if succeed else false
     **/
    public function getFromDBForNetworkPort($ID)
    {

        return $this->getFromDBByCrit([
            'OR'  => [
                $this->getTable() . '.networkports_id_1'  => $ID,
                $this->getTable() . '.networkports_id_2'  => $ID
            ]
        ]);
    }


    /**
     * Get port opposite port ID
     *
     * @param integer $ID networking port ID
     *
     * @return integer|false  ID of opposite port. false if not found
     **/
    public function getOppositeContact($ID)
    {
        if ($this->getFromDBForNetworkPort($ID)) {
            if ($this->fields['networkports_id_1'] == $ID) {
                return $this->fields['networkports_id_2'];
            }
            if ($this->fields['networkports_id_2'] == $ID) {
                return $this->fields['networkports_id_1'];
            }
        }
        return false;
    }

    /**
     * Creates a new hub
     *
     * @param integer $netports_id Network port id
     * @param integer $entities_id Entity id
     *
     * @return integer
     */
    public function createHub($netports_id, $entities_id = 0)
    {
        $netport = new NetworkPort();

        $unmanaged = new Unmanaged();
        $hubs_id = $unmanaged->add([
            'hub'          => 1,
            'name'         => 'Hub',
            'entities_id'  => $entities_id,
            'comment'      => 'Port: ' . $netports_id,
        ]);

        $ports_id = $netport->add([
            'items_id'           => $hubs_id,
            'itemtype'           => $unmanaged->getType(),
            'name'               => 'Hub link',
            'instantiation_type' => 'NetworkPortEthernet'
        ]);
        $this->disconnectFrom($netports_id);
        $this->add([
            'networkports_id_1'  => $netports_id,
            'networkports_id_2'  => $ports_id
        ]);
        return $hubs_id;
    }

    /**
     * Connects to a hub
     *
     * @param integer $ports_id Port to link
     * @param integer $hubs_id  Hub to link
     */
    public function connectToHub($ports_id, $hubs_id)
    {

        /** @var \DBmysql $DB */
        global $DB;

        $netport = new NetworkPort();

        $this->disconnectFrom($ports_id);
       // Search free port
        $result = $DB->request([
            'SELECT'    => $netport->getTable() . '.id',
            'FROM'      => $netport->getTable(),
            'LEFT JOIN' => [
                self::getTable() => [
                    'ON'  => [
                        $netport->getTable() => 'id',
                        self::getTable()     => 'networkports_id_2'
                    ]
                ]
            ],
            'WHERE'     => [
                'itemtype'           => Unmanaged::getType(),
                'items_id'           => $hubs_id,
                'networkports_id_1'  => null
            ],
            'LIMIT'     => 1
        ])->current();

        $free_id = $result['id'] ?? 0;
        if (!$free_id) {
           //no free port, create a new one
            $free_id = $netport->add([
                'itemtype'           => Unmanaged::getType(),
                'items_id'           => $hubs_id,
                'instantiation_type' => 'NetworkPortEthernet'
            ]);
        }

        $this->add([
            'networkports_id_1'  => $ports_id,
            'networkports_id_2'  => $free_id
        ]);
        return $free_id;
    }

    /**
     * Disconnect a port
     *
     * @param integer $ports_id Port id
     *
     * @return boolean
     */
    public function disconnectFrom($ports_id)
    {
        return $this->deleteByCriteria([
            'OR'  => [
                'networkports_id_1'  => $ports_id,
                'networkports_id_2'  => $ports_id,
            ]
        ]);
    }

    /**
     * Cleans hub ports
     * If remove connection of a hub port (unknown device), we must delete this port too
     *
     * @return void
     */
    public function cleanHubPorts()
    {
        $netport = new \NetworkPort();
        $unmanaged = new \Unmanaged();
        $netport_vlan = new \NetworkPort_Vlan();

        $hubs_ids = [];

        foreach (['networkports_id_1', 'networkports_id_2'] as $field) {
            $port_id = $netport->getContact($this->fields[$field]);
            $netport->getFromDB($this->fields[$field]);
            if (($netport->fields['itemtype'] ?? '') == Unmanaged::getType()) {
                $unmanaged->getFromDB($netport->fields['items_id']);
                if ($unmanaged->fields['hub'] == 1) {
                    $vlans = $netport_vlan->getVlansForNetworkPort($netport->fields['id']);
                    foreach ($vlans as $vlan_id) {
                        $netport_vlan->unassignVlan($netport->fields['id'], $vlan_id);
                    }
                    $hubs_ids[$netport->fields['items_id']] = 1;
                    $netport->delete($netport->fields);
                }
            }

            if ($port_id) {
                $netport->getFromDB($port_id);
                if ($netport->fields['itemtype'] == Unmanaged::getType()) {
                    $unmanaged->getFromDB($netport->fields['items_id']);
                    if ($unmanaged->fields['hub'] == '1') {
                        $hubs_ids[$netport->fields['items_id']] = 1;
                    }
                }
            }
        }

        // If hub have no port, delete it
        foreach (array_keys($hubs_ids) as $unmanageds_id) {
            $networkports = $netport->find([
                'itemtype'  => Unmanaged::getType(),
                'items_id'  => $unmanageds_id
            ]);
            if (count($networkports) < 2) {
                $unmanaged->delete(['id' => $unmanageds_id], 1);
            } else if (count($networkports) == 2) {
                $switchs_id = 0;
                $others_id  = 0;
                foreach ($networkports as $networkport) {
                    if ($networkport['name'] == 'Link') {
                        $switchs_id = $netport->getContact($networkport['id']);
                    } else if ($others_id == '0') {
                        $others_id = $netport->getContact($networkport['id']);
                    } else {
                        $switchs_id = $netport->getContact($networkport['id']);
                    }
                }

                $this->disconnectFrom($switchs_id);
                $this->disconnectFrom($others_id);

                $this->add([
                    'networkports_id_1' => $switchs_id,
                    'networkports_id_2' => $others_id
                ]);
            }
        }
    }

    public function prepareInputForAdd($input)
    {

        if (
            $this->getFromDBForNetworkPort($input['networkports_id_1'])
            || $this->getFromDBForNetworkPort($input['networkports_id_2'])
        ) {
            trigger_error('Wired non unique!', E_USER_WARNING);
            return false;
        }

        return $input;
    }

    public function post_addItem()
    {
        $this->storeConnectionLog('add');
    }

    public function pre_deleteItem()
    {
        $this->storeConnectionLog('remove');
        return true;
    }

    public function post_deleteItem()
    {
        $this->cleanHubPorts();
    }

    /**
     * Store connection log.
     *
     * @param string $action Either add or remove
     *
     * @return void
     */
    public function storeConnectionLog($action)
    {
        $netports_id = null;

        $netport = new NetworkPort();
        $netport->getFromDB($this->fields['networkports_id_1']);

        if ($netport->fields['itemtype'] == 'NetworkEquipment') {
            $netports_id = $this->fields['networkports_id_1'];
        } else {
            $netport->getFromDB($this->fields['networkports_id_2']);
            if ($netport->fields['itemtype'] == 'NetworkEquipment') {
                $netports_id = $this->fields['networkports_id_2'];
            }
        }

        if ($netports_id === null) {
            return;
        }

        $log = new NetworkPortConnectionLog();

        $opposite_port = $this->getOppositeContact($netports_id);
        if (!$opposite_port) {
            return;
        }

        $input = [
            'networkports_id_source'      => $netports_id,
            'networkports_id_destination' => $opposite_port,
            'connected'                   => ($action === 'add'),
            'date'                        => $_SESSION['glpi_currenttime'],
        ];

        $log->add($input);
    }
}
