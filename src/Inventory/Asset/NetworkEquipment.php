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

use Blacklist;
use Glpi\Toolbox\Sanitizer;
use NetworkEquipmentModel;
use NetworkEquipmentType;
use NetworkName;

class NetworkEquipment extends MainAsset
{
    private $management_ports = [];

    protected $extra_data = [
        'network_device'                          => null,
        'network_components'                      => null,
        '\Glpi\Inventory\Asset\NetworkPort'       => null
    ];

    protected function getModelsFieldName(): string
    {
        return NetworkEquipmentModel::getForeignKeyField();
    }

    protected function getTypesFieldName(): string
    {
        return NetworkEquipmentType::getForeignKeyField();
    }

    public function prepare(): array
    {
        parent::prepare();

        $val = $this->data[0];
        $model_field = $this->getModelsFieldName();
        $types_field = $this->getTypesFieldName();
        $blacklist = new Blacklist();

        if (isset($this->extra_data['network_device'])) {
            $device = (object)$this->extra_data['network_device'];

            $dev_mapping = [
                'description'  => 'sysdescr',
                'location'     => 'locations_id',
                'model'        => $model_field,
                'type'         => $types_field,
                'manufacturer' => 'manufacturers_id',
                'credentials'  => 'snmpcredentials_id',
                'assettag'     => 'otherserial',
            ];

            foreach ($dev_mapping as $origin => $dest) {
                if (property_exists($device, $origin)) {
                    $device->$dest = $device->$origin;
                }
            }

            if (!property_exists($device, 'name') && property_exists($device, 'description')) {
               //take description if name is missing
                $device->name = $device->description;
            }
            $this->hardware = $device;

            foreach ($device as $key => $property) {
                $val->$key = $property;
            }

            if (property_exists($device, 'ips')) {
                $portkey = 'management';
                $port = new \stdClass();
                if (property_exists($device, 'mac')) {
                    $port->mac = $device->mac;
                }
                $port->name = 'Management';
                $port->netname = __('internal');
                $port->instantiation_type = 'NetworkPortAggregate';
                $port->is_internal = true;
                $port->ipaddress = [];

               //add internal port(s)
                foreach ($device->ips as $ip) {
                    if (
                        !in_array($ip, $port->ipaddress)
                        && '' != $blacklist->process(Blacklist::IP, $ip)
                    ) {
                        $port->ipaddress[] = $ip;
                    }
                }

                $this->management_ports[$portkey] = $port;
            }
        }

        if ($this->isStackedSwitch()) {
           //keep only stack parts, not main equipment
            $this->data = [];
            $switches = $this->getStackedSwitches();
            foreach ($switches as $switch) {
                $stack = clone $val;
                $stack->firmware = $switch->firmware ?? $switch->version ?? '';
                $stack->serial = $switch->serial;
                $stack->model = $switch->model;
                $stack->$model_field = $switch->model;
                $stack->description = $stack->name . ' - ' . ($switch->name ?? $switch->description);
                $stack->name = $stack->name . ' - ' . ($switch->name ?? $switch->description);
                if (($switch->name ?? $switch->description) != $switch->stack_number ?? '') {
                    $stack->name .= ' - ' . $switch->stack_number;
                }
                $stack->stack_number = $switch->stack_number ?? null;
                $this->data[] = $stack;
            }
        } else {
           //keep an entry for main equipment
            $this->data = [$val];
            if ($this->isWirelessController()) {
                $aps = $this->getAccessPoints();
                $i = 1;
                foreach ($aps as $ap) {
                    $wcontrol = clone $val;
                    $wcontrol->is_ap = true;
                    $wcontrol->mac = $ap->mac;
                    $wcontrol->name = $ap->name . ' ' . $ap->description;
                    $wcontrol->serial = $ap->serial;
                    $wcontrol->networkequipmentmodels_id = $ap->model ?? '';
                    $wcontrol->ram = null;
                    $wcontrol->memory = null;
                    $wcontrol->ips = [$ap->ip];

                    //add internal port
                    $port = new \stdClass();
                    $port->mac = $ap->mac;
                    $port->name = 'Management';
                    $port->is_internal = true;
                    $port->netname = __('internal');
                    $port->instantiation_type = 'NetworkPortAggregate';
                    $port->ipaddress = [$ap->ip];
                    $wcontrol->ap_port = $port;

                    $firmware = new \stdClass();
                    $firmware->description = $ap->comment ?? '';
                    $firmware->name = $ap->model ?? '';
                    $firmware->devicefirmwaretypes_id = 'device';
                    $firmware->version = $ap->firware ?? $ap->version ?? '';
                    $wcontrol->firmware = $firmware;

                    $this->data[] = $wcontrol;

                    ++$i;
                }
            }
        }

        return $this->data;
    }

    /**
     * After rule engine passed, update task (log) and create item if required
     *
     * @param integer $items_id id of the item (0 if new)
     * @param string  $itemtype Item type
     * @param integer $rules_id Matched rule id, if any
     * @param integer $ports_id Matched port id, if any
     */
    public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = 0)
    {
        if (property_exists($this->data[$this->current_key], 'is_ap')) {
            $bkp_assets = $this->assets;
            $np = new NetworkPort($this->item, [$this->data[$this->current_key]]);

            if ($np->checkConf($this->conf)) {
                $np->setAgent($this->getAgent());
                $np->setEntityID($this->getEntityID());
                $np->prepare();
                $np->handleLinks();
                $this->assets = ['\Glpi\Inventory\Asset\NetworkPort' => [$np]];
            }
        }

        if (method_exists($this, 'getManagementPorts')) {
            $mports = $this->getManagementPorts();
            $np = new NetworkPort($this->item, $mports);
            if ($np->checkConf($this->conf)) {
                $np->setAgent($this->getAgent());
                $np->setEntityID($this->getEntityID());
                $np->prepare();
                $np->handleLinks();
                if (!isset($this->assets['\Glpi\Inventory\Asset\NetworkPort'])) {
                    $np->addNetworkPorts($mports);
                    $this->assets['\Glpi\Inventory\Asset\NetworkPort'] = [$np];
                } else {
                    $this->assets['\Glpi\Inventory\Asset\NetworkPort'][0]->addNetworkPorts($np->getNetworkPorts());
                }
            }
        }

        parent::rulepassed($items_id, $itemtype, $rules_id, $ports_id);

        if (isset($bkp_assets)) {
            $this->assets = $bkp_assets;
        }
    }

    public function handleLinks(array $data = null)
    {
        if ($this->current_key !== null) {
            $data = [$this->data[$this->current_key]];
        } else {
            $data = $this->data;
        }
        return parent::handleLinks();
    }

    protected function portCreated(\stdClass $port, int $netports_id)
    {
        if (property_exists($port, 'is_internal') && $port->is_internal) {
            return;
        }

       // Get networkname
        $netname = new NetworkName();
        if ($netname->getFromDBByCrit(['itemtype' => 'NetworkPort', 'items_id' => $netports_id])) {
            if ($netname->fields['name'] != $port->name) {
                $netname->update(Sanitizer::sanitize([
                    'id'     => $netname->getID(),
                    'name'   => $port->netname ?? $port->name
                ]));
            }
        } else {
            $netname->add([
                'itemtype'  => 'NetworkPort',
                'items_id'  => $netports_id,
                'name'      => addslashes($port->name)
            ]);
        }
    }

    public function getManagementPorts()
    {
        return $this->management_ports;
    }

    public function setManagementPorts(array $ports): NetworkEquipment
    {
        $this->management_ports = $ports;
        return $this;
    }

    /**
     * Is device a stacked switch
     * Relies on level/dependencies of network_components
     *
     * @param integer $parent_index Parent index for recursive calls
     *
     * @return boolean
     */
    public function isStackedSwitch($parent_index = 0): bool
    {
        $components = $this->extra_data['network_components'] ?? [];
        if (!count($components)) {
            return false;
        }

        $elt_count = 0;
        foreach ($components as $component) {
            if (!property_exists($component, 'type')) {
                continue;
            }
            switch ($component->type) {
                case 'stack':
                    if ($parent_index == 0) {
                        $elt_count += $this->isStackedSwitch($component->index);
                    }
                    break;
                case 'chassis':
                    if (property_exists($component, 'serial')) {
                        ++$elt_count;
                    }
                    break;
            }
        }

        return $elt_count >= 2;
    }

    /**
     * Get detected switches (osrted by their index)
     *
     * @return array
     */
    public function getStackedSwitches(): array
    {
        $components = $this->extra_data['network_components'] ?? [];
        if (!count($components)) {
            return [];
        }

        $switches = [];
        $stack_number = 1;
        foreach ($components as $component) {
            switch ($component->type) {
                case 'chassis':
                    if (property_exists($component, 'serial')) {
                        $component->stack_number = $stack_number;
                        $switches[$component->index] = $component;
                    }
                    $stack_number++;
                    break;
            }
        }

        ksort($switches);
        return $switches;
    }

    /**
     * Is device a wireless controller
     * Relies on level/dependencies of network_components
     *
     * @param integer $parent_index Parent index for recursive calls
     *
     * @return boolean
     */
    public function isWirelessController($parent_index = 0): bool
    {
        $components = $this->extra_data['network_components'] ?? [];
        if (!count($components)) {
            return false;
        }

        foreach ($components as $component) {
            if (
                property_exists($component, 'ip') && property_exists($component, 'mac')
                && !empty($component->ip) && !empty($component->mac)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get wireless controller access points
     *
     * @return array
     */
    public function getAccessPoints(): array
    {
        $components = $this->extra_data['network_components'] ?? [];
        if (!count($components)) {
            return [];
        }

        $aps = [];
        foreach ($components as $component) {
            if (
                property_exists($component, 'ip') && property_exists($component, 'mac')
                && !empty($component->ip) && !empty($component->mac)
            ) {
                $aps[$component->index] = $component;
            }
        }

        return $aps;
    }

    public function getStackId()
    {
        if (count($this->data) != 1) {
            throw new \RuntimeException('Exactly one entry in data is expected.');
        } else {
            $data = current($this->data);

            if ($data->stack_number !== null) {
                return $data->stack_number;
            }

            return preg_replace('/.+\s(\d+)$/', '$1', $data->name);
        }
    }

    public function getItemtype(): string
    {
        return \NetworkEquipment::class;
    }
}
