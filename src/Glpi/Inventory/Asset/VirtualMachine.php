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

use AutoUpdateSystem;
use Computer;
use Glpi\Inventory\Conf;
use ItemVirtualMachine;
use RuleImportAssetCollection;
use RuntimeException;
use stdClass;

use function Safe\json_encode;

class VirtualMachine extends InventoryAsset
{
    use InventoryNetworkPort;

    private $conf;
    private $allports = [];

    private const VMCOMPONENTS = [
        'storages'  => Drive::class,
        'drives'    => Volume::class,
        'cpus'      => Processor::class,
        'memories'  => Memory::class,
    ];

    public function prepare(): array
    {
        global $CFG_GLPI;

        $mapping = [
            'memory'      => 'ram',
            'vmtype'      => 'virtualmachinetypes_id',
            'subsystem'   => 'virtualmachinesystems_id',
            'status'      => 'virtualmachinestates_id',
        ];

        $vm_mapping = [
            'memory'          => 'ram',
            'vmtype'          => 'computertypes_id',
            'operatingsystem' => 'operatingsystems_id',
            'customfields'    => 'comment',
        ];

        $net_mapping = [
            'description' => 'name',
            'macaddr'     => 'mac',
        ];

        if (!in_array($this->item->getType(), $CFG_GLPI['itemvirtualmachines_types'])) {
            throw new RuntimeException(
                sprintf(
                    'Virtual machines are not handled for %s.',
                    $this->item->getType()
                )
            );
        }

        foreach ($this->data as &$val) {
            $vm_val = clone($val);
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }
            $val->is_deleted = 0;

            if (!property_exists($vm_val, 'autoupdatesystems_id')) {
                $vm_val->autoupdatesystems_id = AutoUpdateSystem::NATIVE_INVENTORY;
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
                } elseif (strstr($vm_val->ram, 'KB')) {
                    $vm_val = (float) str_replace('KB', '', $vm_val->ram) / 1000;
                } elseif (strstr($vm_val->ram, 'GB')) {
                    $vm_val->ram = (float) str_replace('GB', '', $vm_val->ram) * 1000;
                } elseif (strstr($vm_val->ram, 'B')) {
                    $vm_val->ram = (float) str_replace('B', '', $vm_val->ram) / 1000000;
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
                    $cpu = new stdClass();
                    $cpu->core = $vm_val->vcpu;
                    $cpus[] = $cpu;
                    $vm_val->cpus = $cpus;
                }

                //create memory component
                if (!property_exists($vm_val, 'memories') && property_exists($vm_val, 'ram')) {
                    $memories = [];
                    $memory = new stdClass();
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

                    if (property_exists($net_val, 'ipaddress') && !property_exists($net_val, 'ip')) {
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
            'FROM'   => ItemVirtualMachine::getTable(),
            'WHERE'  => [
                'itemtype' => $this->item->getType(),
                'items_id' => $this->item->fields['id'],
                'is_dynamic'   => 1,
            ],
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
        $itemVirtualmachine = new ItemVirtualMachine();

        $db_vms = $this->getExisting();

        foreach ($db_vms as $keydb => $arraydb) {
            $itemVirtualmachine->getFromDB($keydb);
            foreach ($value as $key => $val) {
                $handled_input = $this->handleInput($val, $itemVirtualmachine);

                //search ItemvirtualMachine on cleaned UUID if it changed
                foreach (ItemVirtualMachine::getUUIDRestrictCriteria($handled_input['uuid'] ?? '') as $cleaned_uuid) {
                    $sinput = [
                        'name'                     => $handled_input['name'] ?? '',
                        'uuid'                     => $cleaned_uuid ?? '',
                        'virtualmachinesystems_id' => $handled_input['virtualmachinesystems_id'] ?? 0,
                    ];

                    //strtolower to be the same as getUUIDRestrictCriteria()
                    $arraydb['uuid'] = strtolower($arraydb['uuid']);

                    if ($sinput == $arraydb) {
                        $input = [
                            'id'           => $keydb,
                            'uuid'         => strtolower($handled_input['uuid'] ?? ''),
                            'is_dynamic'   => 1,
                        ];

                        foreach (['vcpu', 'ram', 'virtualmachinetypes_id', 'virtualmachinestates_id', 'comment'] as $prop) {
                            if (property_exists($val, $prop)) {
                                $input[$prop] = $handled_input[$prop];
                            }
                        }

                        $itemVirtualmachine->update($input);
                        unset($value[$key]);
                        unset($db_vms[$keydb]);
                        break 2;
                    }
                }
            }
        }

        if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_vms) != 0) {
            // Delete virtual machines links in DB
            foreach (array_keys($db_vms) as $idtmp) {
                $itemVirtualmachine->delete(['id' => $idtmp], true);
            }
        }

        if (count($value) != 0) {
            foreach ($value as $val) {
                $input = $this->handleInput($val, $itemVirtualmachine);
                $input['itemtype'] = $this->item->getType();
                $input['items_id'] = $this->item->fields['id'];
                $input['is_dynamic']  = 1;
                $itemVirtualmachine->add($input);
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
            $vm->last_inventory_update = $_SESSION["glpi_currenttime"];
            $vm->autoupdatesystems_id = $this->item->fields['autoupdatesystems_id'];
            $vm->is_dynamic = 1;

            if ($this->conf->vm_type) {
                $vm->computertypes_id = $this->conf->vm_type;
            }

            if (property_exists($vm, 'uuid') && $vm->uuid != '') {
                $computers_vm_id = $this->getExistingVMAsComputer($vm);
                $rulesmatched = new \RuleMatchedLog();
                $rule = new RuleImportAssetCollection();
                $rule->getCollectionPart();
                $input = $this->handleInput($vm, $this->item);
                $input['itemtype'] = Computer::class;
                $agents_id = !empty($this->agent->fields['id']) ? $this->agent->fields['id'] : 0;

                if ($computers_vm_id == 0) {
                    //call rules on current collected data to find item
                    //a callback on rulepassed() will be done if one is found.
                    $input['states_id'] = $this->conf->states_id_default > 0 ? $this->conf->states_id_default : 0;
                    $input['entities_id'] = $this->main_asset->getEntityID();
                    // Using ['return' => true] forces the rule engine to return the found item directly or indicate that no matching rule was found.
                    // This bypasses the default rulePassed() call for the current asset type.
                    // This call to processAllRules() is only to check whether the asset is rejected by a rule.
                    // Responsibility for creating the item lies with this code block.
                    $datarules = $rule->processAllRules($input, [], ['return' => true]);

                    if (isset($datarules['_no_rule_matches']) && ($datarules['_no_rule_matches'] == '1') || isset($datarules['found_inventories'])) {
                        //this is a new one
                        $vm->entities_id = $this->item->fields['entities_id'];
                        $computers_vm_id = $computervm->add($input);

                        $inputrulelog = [
                            'date'      => date('Y-m-d H:i:s'),
                            'rules_id'  => 0,
                            'items_id'  => $computers_vm_id,
                            'itemtype'  => $input['itemtype'],
                            'agents_id' => $agents_id,
                            'method'    => 'inventory',
                            'input'     => json_encode(['uuid' => $input['uuid']]),
                        ];
                        $rulesmatched->add($inputrulelog, [], false);

                    } else {
                        //refused by rules
                        continue;
                    }
                } else {
                    // Update computer
                    $computervm->getFromDB($computers_vm_id);
                    $input['id'] = $computers_vm_id;
                    if ($this->conf->states_id_default != '-1') {
                        $input['states_id'] = $this->conf->states_id_default;
                    }
                    // Using ['return' => true] forces the rule engine to return the found item directly or indicate that no matching rule was found.
                    // This bypasses the default rulePassed() call for the current asset type.
                    // This call to processAllRules() is only to check whether the asset is rejected by a rule.
                    // Responsibility for updating the item lies with this code block.
                    $datarules = $rule->processAllRules($input, [], ['return' => true]);
                    if (isset($datarules['_no_rule_matches']) && ($datarules['_no_rule_matches'] == '1') || isset($datarules['found_inventories'])) {
                        $computervm->update($input);

                        $inputrulelog = [
                            'date'      => date('Y-m-d H:i:s'),
                            'rules_id'  => 0,
                            'items_id'  => $computers_vm_id,
                            'itemtype'  => $input['itemtype'],
                            'agents_id' => $agents_id,
                            'method'    => 'inventory',
                            'input'     => json_encode(['uuid' => $input['uuid']]),
                        ];
                        $rulesmatched->add($inputrulelog, [], false);

                    } else {
                        //refused by rules
                        continue;
                    }
                }

                //load if new, reload if not.
                $computervm->getFromDB($computers_vm_id);
                // Manage networks
                if (isset($this->allports[$vm->uuid])) {
                    $this->ports = $this->allports[$vm->uuid];
                    $this->handlePorts('Computer', $computers_vm_id);
                } elseif (property_exists($vm, 'ipaddress')) {
                    $net_val = new stdClass();
                    if (property_exists($vm, 'ipaddress')) {
                        $net_val->ip = [$vm->ipaddress];
                    }

                    if (property_exists($vm, 'mac')) {
                        $net_val->instantiation_type = 'NetworkPortEthernet';
                        $net_val->mac = strtolower($vm->mac);
                        if (isset($this->allports[$vm->uuid][$vm->name])) {
                            if (property_exists($net_val, 'ip')  && $net_val->ip != '') {
                                $net_val->ipaddress = $net_val->ip;
                                $this->allports[$vm->uuid][$vm->name] = $net_val;
                            }
                        } else {
                            if (property_exists($net_val, 'ip') && $net_val->ip != '') {
                                $net_val->ipaddress = $net_val->ip;
                                $this->allports[$vm->uuid][$vm->name] = $net_val;
                            }
                        }
                        $this->ports = $this->allports[$vm->uuid];
                        $this->handlePorts('Computer', $computers_vm_id);
                    }
                }

                //manage operating system
                if (property_exists($vm, 'operatingsystem')) {
                    $os = new OperatingSystem($computervm, (array) $vm->operatingsystem);
                    if ($os->checkConf($this->conf)) {
                        $os->setAgent($this->getAgent());
                        $os->setExtraData($this->data);
                        $os->setEntityID($computervm->getEntityID());
                        $os->prepare();
                        $os->handleLinks();
                        $os->handle();
                    }
                }

                //manage extra components created form hosts information
                if ($this->conf->vm_components) {
                    foreach (self::VMCOMPONENTS as $key => $assettype) {
                        if (property_exists($vm, $key)) {
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

    public function getExistingVMAsComputer(stdClass $vm): int
    {
        global $DB;

        $computers_vm_id = 0;
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_computers',
            'WHERE'  => [
                'RAW' => [
                    'LOWER(uuid)'  => ItemVirtualMachine::getUUIDRestrictCriteria($vm->uuid),
                ],
            ],
            'LIMIT'  => 1,
        ]);

        foreach ($iterator as $data) {
            $computers_vm_id = $data['id'];
        }

        return $computers_vm_id;
    }

    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        $this->conf = $conf;
        return $conf->import_vm == 1 && in_array($this->item::class, $CFG_GLPI['itemvirtualmachines_types']);
    }

    public function getItemtype(): string
    {
        return ItemVirtualMachine::class;
    }
}
