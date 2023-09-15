<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Inventory\Asset;

use Glpi\Inventory\Conf;
use Glpi\Inventory\Request;
use Glpi\Toolbox\Sanitizer;
use NetworkPortInstantiation;
use RefusedEquipment;
use RuleMatchedLog;
use Transfer;

class Unmanaged extends MainAsset
{
    private $management_ports = [];

    protected $extra_data = [
        'hardware'        => null,
        'network_device'  => null,
    ];

    public function prepare(): array
    {
        parent::prepare();
        $val = $this->data[0];

        $raw_data = $this->raw_data;
        if (!is_array($raw_data)) {
            $raw_data = [$raw_data];
        }

        if (isset($this->extra_data['network_device'])) {
            $this->prepareForNetworkDevice($val);
        }

        //remove useless properties
        // DOMAIN are not managed yet for Unmanaged item
        if (property_exists($val, 'domains_id')) {
            unset($val->domains_id);
        }
        if (property_exists($val, 'workgroup')) {
            unset($val->workgroup);
        }

        $this->data[0] = $val;
        return $this->data;
    }

    /**
     * Prepare network device information
     *
     * @param \stdClass $val
     *
     * @return void
     */
    protected function prepareForNetworkDevice(\stdClass $val): void
    {
        if (isset($this->extra_data['network_device'])) {
            $device = (object)$this->extra_data['network_device'];

            $dev_mapping = [
                'mac'       => 'mac',
                'name'      => 'name',
                'ips'       => 'ips'
            ];

            foreach ($dev_mapping as $origin => $dest) {
                if (property_exists($device, $origin)) {
                    $device->$dest = $device->$origin;
                }
            }

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
                $port->logical_number = 0;
                $port->ipaddress = [];

               //add internal port(s)
                foreach ($device->ips as $ip) {
                    if ($ip != '127.0.0.1' && $ip != '::1' && !in_array($ip, $port->ipaddress)) {
                        $port->ipaddress[] = $ip;
                    }
                }

                $this->management_ports[$portkey] = $port;
            }
        }
    }

    /**
     * After rule engine passed, update task (log) and create item if required
     *
     * @param integer $items_id id of the item (0 if new)
     * @param string  $itemtype Item type
     * @param integer $rules_id Matched rule id, if any
     * @param array $ports_id Matched port ids, if any
     */
    public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = [])
    {
        $key = $this->current_key;
        $val = &$this->data[$key];
        $entities_id = $this->entities_id;
        $val->is_dynamic = 1;
        $val->entities_id = $entities_id;
        $default_states_id = $this->states_id_default ?? 0;
        if ($items_id != 0 && $default_states_id != '-1') {
            $val->states_id = $default_states_id;
        } elseif ($items_id == 0) {
            //if create mode default states_id can't be '-1' put 0 if needed
            $val->states_id = $default_states_id > 0 ? $default_states_id : 0;
        }

        // append data from RuleImportEntity
        foreach ($this->ruleentity_data as $attribute => $value) {
            $val->{$attribute} = $value;
        }
        // append data from RuleLocation
        foreach ($this->rulelocation_data as $attribute => $value) {
            $known_key = md5($attribute . $value);
            $this->known_links[$known_key] = $value;
            $val->{$attribute} = $value;
        }

        $orig_glpiactive_entity = $_SESSION['glpiactive_entity'] ?? null;
        $orig_glpiactiveentities = $_SESSION['glpiactiveentities'] ?? null;
        $orig_glpiactiveentities_string = $_SESSION['glpiactiveentities_string'] ?? null;

        //set entity in session
        $_SESSION['glpiactiveentities']        = [$entities_id];
        $_SESSION['glpiactiveentities_string'] = $entities_id;
        $_SESSION['glpiactive_entity']         = $entities_id;

        if ($items_id != 0) {
            $this->item->getFromDB($items_id);
        }

        //handleLinks relies on $this->data; update it before the call
        $this->handleLinks();


        $need_to_add = false;
        if ($items_id == 0) {
            //before add check if an asset already exist with mac
            //if found, the Unmanaged device has been converted

            if (property_exists($val, "mac")) {
                $result = NetworkPortInstantiation::getUniqueItemByMac(
                    $val->mac,
                    $entities_id
                );
                //manage converted object
                if (!empty($result)) {
                    $converted_object = new $result['itemtype']();
                    if ($converted_object->getFromDB($result['id'])) {
                        $this->item = $converted_object;
                        $items_id = $result['id'];
                        $itemtype = $result['itemtype'];
                    } else {
                        $need_to_add = true;
                    }
                } else {
                    $need_to_add = true;
                }
            } else {
                $need_to_add = true;
            }

            if ($need_to_add) {
                //else add it
                $input = $this->handleInput($val, $this->item);
                unset($input['ap_port']);
                unset($input['firmware']);
                $items_id = $this->item->add(Sanitizer::sanitize($input));
                $this->setNew();
            }
        }

        //do not update itemtype / items_id Agent from Unmanaged item
        //just keep related items_id and entities_id for the rest of the process
        //like Printer or NetworkEquipment process
        $this->agent->fields['items_id'] = $items_id;
        $this->agent->fields['entities_id'] = $entities_id;

        //check for any old agent to remove only if it an unmanaged
        //to prevent agentdeletion from another asset handle by another agent
        if ($need_to_add) {
            $agent = new \Agent();
            $agent->deleteByCriteria([
                'itemtype' => $this->item->getType(),
                'items_id' => $items_id,
                'NOT' => [
                    'id' => $this->agent->fields['id']
                ]
            ]);
        }


        $val->id = $this->item->fields['id'];

        if ($entities_id == -1) {
            $entities_id = $this->item->fields['entities_id'];
        }
        $val->entities_id = $entities_id;

        if ($entities_id != $this->item->fields['entities_id']) {
            //asset entity has changed in rules; do transfer
            $doTransfer = \Entity::getUsedConfig('transfers_strategy', $this->item->fields['entities_id'], 'transfers_id', 0);
            $transfer = new Transfer();
            if ($doTransfer > 0 && $transfer->getFromDB($doTransfer)) {
                $item_to_transfer = [$this->itemtype => [$items_id => $items_id]];
                $transfer->moveItems($item_to_transfer, $entities_id, $transfer->fields);
                //and set new entity in session
                $_SESSION['glpiactiveentities']        = [$entities_id];
                $_SESSION['glpiactiveentities_string'] = $entities_id;
                $_SESSION['glpiactive_entity']         = $entities_id;
            } else {
                //no transfert so revert to old entities_id
                $val->entities_id = $this->item->fields['entities_id'];
            }
        }

        $this->handlePorts();

        $input = $this->handleInput($val, $this->item);
        $this->item->update(Sanitizer::sanitize($input));

        if (!($this->item instanceof RefusedEquipment)) {
            $this->handleAssets();
        }

        $rulesmatched = new RuleMatchedLog();
        $inputrulelog = [
            'date'      => date('Y-m-d H:i:s'),
            'rules_id'  => $rules_id,
            'items_id'  => $items_id,
            'itemtype'  => $itemtype,
            'agents_id' => $this->agent->fields['id'],
            'method'    => $this->request_query ?? Request::INVENT_QUERY
        ];
        $rulesmatched->add($inputrulelog, [], false);
        $rulesmatched->cleanOlddata($items_id, $itemtype);

        //keep trace of inventoried assets, but not APs.
        if (!$this->isAccessPoint($val)) {
            $this->inventoried[] = clone $this->item;
        }

        //Restore entities in session
        if ($orig_glpiactive_entity !== null) {
            $_SESSION['glpiactive_entity'] = $orig_glpiactive_entity;
        }

        if ($orig_glpiactiveentities !== null) {
            $_SESSION['glpiactiveentities'] = $orig_glpiactiveentities;
        }

        if ($orig_glpiactiveentities_string !== null) {
            $_SESSION['glpiactiveentities_string'] = $orig_glpiactiveentities_string;
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

    public function getManagementPorts()
    {
        return $this->management_ports;
    }

    public function setManagementPorts(array $ports): Unmanaged
    {
        $this->management_ports = $ports;
        return $this;
    }

    protected function getModelsFieldName(): string
    {
        return "";
    }

    protected function getTypesFieldName(): string
    {
        return "";
    }

    public function checkConf(Conf $conf): bool
    {
        $this->conf = $conf;
        return $conf->import_unmanaged == 1;
    }

    public function getItemtype(): string
    {
        return \Unmanaged::class;
    }
}
