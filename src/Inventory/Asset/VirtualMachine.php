<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Computer;
use ComputerVirtualMachine;
use Glpi\Inventory\Conf;
use RuleImportAssetCollection;
use Toolbox;

class VirtualMachine extends InventoryAsset
{
    use InventoryNetworkPort;

    private $conf;
    private $vms = [];
    private $allports = [];
    private $vmcomponents = [
        'storages'  => 'Drive',
        'drives'    => 'Volume',
        'cpus'      => 'Processor',
        'memories'  => 'Memory'
    ];

    public function prepare(): array
    {
        $mapping = [
            'memory'      => 'ram',
            'vmtype'      => 'virtualmachinetypes_id',
            'subsystem'   => 'virtualmachinesystems_id',
            'status'      => 'virtualmachinestates_id'
        ];

        $vm_mapping = [
            'memory'          => 'ram',
            'vmtype'          => 'computertypes_id',
            'operatingsystem' => 'operatingsystems_id',
            'customfields'    => 'comment'
        ];

        $net_mapping = [
            'description' => 'name',
            'macaddr'     => 'mac'
        ];

        if ($this->item->getType() != 'Computer') {
            throw new \RuntimeException('Virtual machines are handled for computers only.');
        }

        foreach ($this->data as &$val) {
            $vm_val = clone($val);
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

            if (!property_exists($vm_val, 'autoupdatesystems_id')) {
                $vm_val->autoupdatesystems_id = 'GLPI Native Inventory';
            }

            // Hack for BSD jails
            if (property_exists($val, 'virtualmachinetypes_id') && $val->virtualmachinetypes_id == 'jail') {
                $val->uuid = "-" . $val->name;
            }

            foreach ($vm_mapping as $origin => $dest) {
                if (property_exists($vm_val, $origin)) {
                    $vm_val->$dest = $vm_val->$origin;
                }
            }

            if (property_exists($vm_val, 'ram')) {
                if (strstr($vm_val->ram, 'MB')) {
                    $vm_val = str_replace('MB', '', $vm_val->ram);
                } else if (strstr($vm_val->ram, 'KB')) {
                    $vm_val = str_replace('KB', '', $vm_val->ram) / 1000;
                } else if (strstr($vm_val->ram, 'GB')) {
                    $vm_val->ram = str_replace('GB', '', $vm_val->ram) * 1000;
                } else if (strstr($vm_val->ram, 'B')) {
                    $vm_val->ram = str_replace('B', '', $vm_val->ram) / 1000000;
                }
            }

            if (property_exists($vm_val, 'comment') && is_array($vm_val->comment)) {
                $comments = '';
                foreach ($vm_val->comment as $comment) {
                    $comments .= $comment->name . ' : ' . $comment->value;
                }
                $vm_val->comment = $comments;
            }

            //handle extra components
            if ($this->conf->vm_as_computer && $this->conf->vm_components) {
                //create processor component
                if (!property_exists($vm_val, 'cpus') && property_exists($vm_val, 'vcpu')) {
                    $cpus = [];
                    $cpu = new \stdClass();
                    $cpu->core = $vm_val->vcpu;
                    $cpus[] = $cpu;
                    $vm_val->cpus = $cpus;
                }

                //create memory component
                if (!property_exists($vm_val, 'memories') && property_exists($vm_val, 'ram')) {
                    $memories = [];
                    $memory = new \stdClass();
                    $memory->capacity = $vm_val->ram;
                    $memories[] = $memory;
                    $vm_val->memories = $memories;
                }
            }

            if (property_exists($vm_val, 'networks') && is_array($vm_val->networks)) {
                foreach ($vm_val->networks as $net_val) {
                    foreach ($net_mapping as $origin => $dest) {
                        if (property_exists($net_val, $origin)) {
                            $net_val->$dest = $net_val->$origin;
                        }
                    }

                    if (property_exists($net_val, 'ipaddress') and !property_exists($net_val, 'ip')) {
                        $net_val->ip = [$net_val->ipaddress];
                    }

                    if (property_exists($net_val, 'name') && property_exists($net_val, 'mac')) {
                        $net_val->instantiation_type = 'NetworkPortEthernet';
                        $net_val->mac = strtolower($net_val->mac);
                        if (isset($this->allports[$vm_val->uuid][$net_val->name . '-' . $net_val->mac])) {
                            if (property_exists($net_val, 'ip')  && $net_val->ip != '') {
                                $net_val->ipaddress = $net_val->ip;
                                $this->allports[$vm_val->uuid][$net_val->name . '-' . $net_val->mac] = $net_val;
                            }
                        } else {
                            if (property_exists($net_val, 'ip') && $net_val->ip != '') {
                                $net_val->ipaddress = $net_val->ip;
                                $this->allports[$vm_val->uuid][$net_val->name . '-' . $net_val->mac] = $net_val;
                            }
                        }
                    }
                }
            }
        }

        return $this->data;
    }

    /**
     * Get existing entries from database
     *
     * @return array
     */
    protected function getExisting(): array
    {
        global $DB;

        $db_existing = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'uuid', 'virtualmachinesystems_id'],
            'FROM'   => ComputerVirtualMachine::getTable(),
            'WHERE'  => [
                'computers_id' => $this->item->fields['id'],
                'is_dynamic'   => 1
            ]
        ]);

        foreach ($iterator as $row) {
            $idtmp = $row['id'];
            unset($row['id']);
            $db_existing[$idtmp] = $row;
        }

        return $db_existing;
    }

    public function handle()
    {
        $value = $this->data;
        $computerVirtualmachine = new ComputerVirtualMachine();

        $db_vms = $this->getExisting();

        foreach ($db_vms as $keydb => $arraydb) {
            foreach ($value as $key => $val) {
                $handled_input = $this->handleInput($val);
                $sinput = [
                    'name'                     => $handled_input['name'] ?? '',
                    'uuid'                     => $handled_input['uuid'] ?? '',
                    'virtualmachinesystems_id' => $handled_input['virtualmachinesystems_id'] ?? 0
                ];
                if ($sinput == $arraydb) {
                    $input = [
                        'id'           => $keydb,
                        'is_dynamic'   => 1
                    ];

                    foreach (['vcpu', 'ram', 'virtualmachinetypes_id', 'virtualmachinestates_id'] as $prop) {
                        if (property_exists($val, $prop)) {
                            $input[$prop] = $handled_input[$prop];
                        }
                    }
                    $computerVirtualmachine->update(Toolbox::addslashes_deep($input));
                    unset($value[$key]);
                    unset($db_vms[$keydb]);
                    break;
                }
            }
        }

        if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_vms) != 0) {
           // Delete virtual machines links in DB
            foreach ($db_vms as $idtmp => $data) {
                $computerVirtualmachine->delete(['id' => $idtmp], true);
            }
        }

        if (count($value) != 0) {
            foreach ($value as $val) {
                $input = $this->handleInput($val);
                $input['computers_id'] = $this->item->fields['id'];
                $input['is_dynamic']  = 1;
                $computerVirtualmachine->add(Toolbox::addslashes_deep($input));
            }
        }

        if ($this->conf->vm_as_computer) {
            $this->createVmComputer();
        }
    }

    /**
     * Create computer asset from VM information
     *
     * @return void
     */
    protected function createVmComputer()
    {
        global $DB;

        $computervm = new Computer();
        foreach ($this->data as $vm) {
            if (property_exists($vm, '_onlylink') && $vm->_onlylink) {
                continue;
            }
            // Define location of physical computer (host)
            $vm->locations_id = $this->item->fields['locations_id'];
            $vm->is_dynamic = 1;

            if ($this->conf->vm_type) {
                $vm->computertypes_id = $this->conf->vm_type;
            }

            if (property_exists($vm, 'uuid') && $vm->uuid != '') {
                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_computers',
                    'WHERE'  => [
                        'RAW' => [
                            'LOWER(uuid)'  => ComputerVirtualMachine::getUUIDRestrictCriteria($vm->uuid)
                        ]
                    ],
                    'LIMIT'  => 1
                ]);
                $computers_vm_id = 0;
                foreach ($iterator as $data) {
                     $computers_vm_id = $data['id'];
                }
                if ($computers_vm_id == 0) {
                    //call rules on current collected data to find item
                    //a callback on rulepassed() will be done if one is found.
                    $rule = new RuleImportAssetCollection();
                    $rule->getCollectionPart();
                    $input = (array)$vm;
                    $input['itemtype'] = \Computer::class;
                    $input['entities_id'] = $this->main_asset->getEntityID();
                    $input  = \Toolbox::addslashes_deep($input);
                    $datarules = $rule->processAllRules($input);

                    if (isset($datarules['_no_rule_matches']) && ($datarules['_no_rule_matches'] == '1') || isset($datarules['found_inventories'])) {
                        //this is a new one
                        $vm->entities_id = $this->item->fields['entities_id'];
                        $computers_vm_id = $computervm->add($input);
                    } else {
                        //refused by rules
                        return;
                    }
                } else {
                    // Update computer
                    $computervm->getFromDB($computers_vm_id);
                    $input = (array)$vm;
                    $input['id'] = $computers_vm_id;
                    $computervm->update(Toolbox::addslashes_deep($input));
                }

                //load if new, reload if not.
                $computervm->getFromDB($computers_vm_id);
                // Manage networks
                if (isset($this->allports[$vm->uuid])) {
                    $this->ports = $this->allports[$vm->uuid];
                    $this->handlePorts('Computer', $computers_vm_id);
                }

                //manage extra components created form hosts information
                if ($this->conf->vm_components) {
                    foreach ($this->vmcomponents as $key => $assetitem) {
                        if (property_exists($vm, $key)) {
                            $assettype = '\Glpi\Inventory\Asset\\' . $assetitem;
                            $asset = new $assettype($computervm, $vm->$key);
                            if ($asset->checkConf($this->conf)) {
                                $asset->setAgent($this->getAgent());
                                $asset->setExtraData($this->data);
                                $asset->setEntityID($computervm->getEntityID());
                                $asset->prepare();
                                $asset->handleLinks();
                                $asset->handle();
                            }
                        }
                    }
                }
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        $this->conf = $conf;
        return $conf->import_vm == 1;
    }
}
