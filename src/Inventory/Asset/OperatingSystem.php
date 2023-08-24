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
use Glpi\Toolbox\Sanitizer;
use Item_OperatingSystem;
use RuleDictionnaryOperatingSystemArchitectureCollection;
use RuleDictionnaryOperatingSystemCollection;
use RuleDictionnaryOperatingSystemEditionCollection;
use RuleDictionnaryOperatingSystemServicePackCollection;
use RuleDictionnaryOperatingSystemVersionCollection;

class OperatingSystem extends InventoryAsset
{
    protected $extra_data = ['hardware' => null];
    private $operatingsystems_id;

    public function prepare(): array
    {
        $mapping = [
            'name'           => 'operatingsystems_id',
            'version'        => 'operatingsystemversions_id',
            'service_pack'   => 'operatingsystemservicepacks_id',
            'arch'           => 'operatingsystemarchitectures_id',
            'kernel_name'    => 'operatingsystemkernels_id',
            'kernel_version' => 'operatingsystemkernelversions_id',
        ];

        $val = (object)$this->data;
        foreach ($mapping as $origin => $dest) {
            if (property_exists($val, $origin)) {
                $val->$dest = $val->$origin;
            }
        }

        if (isset($this->extra_data['hardware'])) {
            if (property_exists($this->extra_data['hardware'], 'winprodid')) {
                $val->licenseid = $this->extra_data['hardware']->winprodid;
            }

            if (property_exists($this->extra_data['hardware'], 'winprodkey')) {
                $val->license_number = $this->extra_data['hardware']->winprodkey;
            }
        }

        if (property_exists($val, 'full_name')) {
            $val->operatingsystems_id = $val->full_name;
        }

        if (property_exists($val, 'install_date')) {
            $val->install_date = date('Y-m-d', strtotime($val->install_date));
        }

        $mapping = [
            'operatingsystems_id'               => [
                "collection_class" => RuleDictionnaryOperatingSystemCollection::class,
                "main_value" => $val->operatingsystems_id ?? ''
            ],
            'operatingsystemversions_id'        => [
                "collection_class" => RuleDictionnaryOperatingSystemVersionCollection::class,
                "main_value" => $val->operatingsystemversions_id ?? ''
            ],
            'operatingsystemservicepacks_id'    => [
                "collection_class" => RuleDictionnaryOperatingSystemServicePackCollection::class,
                "main_value" => $val->operatingsystemservicepacks_id ?? ''
            ],
            'operatingsystemarchitectures_id'   => [
                "collection_class" => RuleDictionnaryOperatingSystemArchitectureCollection::class ,
                "main_value" => $val->operatingsystemarchitectures_id ?? ''
            ],
            'operatingsystemeditions_id'        => [
                "collection_class" => RuleDictionnaryOperatingSystemEditionCollection::class,
                "main_value" => $val->operatingsystemeditions_id ?? ''
            ],
        ];

        $rule_input = [
            'os_name'           => $val->operatingsystems_id ?? '',
            'os_version_name'   => $val->operatingsystemversions_id ?? '',
            'servicepack_name'  => $val->operatingsystemservicepacks_id ?? '',
            'arch_name'         => $val->operatingsystemarchitectures_id ?? '',
            'os_edition'        => $val->operatingsystemeditions_id ?? '',
        ];

        foreach ($mapping as $key => $value) {
            $rulecollection = new $value['collection_class']();
            $rule_input['name'] = $value['main_value'];
            $res_rule = $rulecollection->processAllRules($rule_input);
            if (isset($res_rule['name'])) {
                $val->{$key} = $res_rule['name'];
            }

            if (property_exists($val, $key) && $val->{$key} == '0') {
                $val->{$key} = '';
            }
        }

        $this->data = [$val];
        return $this->data;
    }

    public function handle()
    {
        global $DB;

        $ios = new Item_OperatingSystem();

        $val = $this->data[0];

        $ios->getFromDBByCrit([
            'itemtype'  => $this->item->getType(),
            'items_id'  => $this->item->fields['id']
        ]);

        $input_os = $this->handleInput($val, $ios) + [
            'itemtype'                          => $this->item->getType(),
            'items_id'                          => $this->item->fields['id'],
            'is_dynamic'                        => 1,
            'entities_id'                       => $this->item->fields['entities_id']
        ];

        if (!$ios->isNewItem()) {
            //OS exists, check for updates
            $same = true;
            foreach ($input_os as $key => $value) {
                if (array_key_exists($key, $ios->fields) && $ios->fields[$key] != $value) {
                    $same = false;
                    break;
                }
            }
            if ($same === false) {
                $ios->update(Sanitizer::sanitize(['id' => $ios->getID()] + $input_os));
            }
        } else {
            $ios->add(Sanitizer::sanitize($input_os));
        }

        $ioskey = 'operatingsystems_id' . $val->operatingsystems_id;
        $this->known_links[$ioskey] = $ios->fields['id'];
        $this->operatingsystems_id =  $input_os['operatingsystems_id'];

        //cleanup
        if (!$this->main_asset || !$this->main_asset->isPartial()) {
            $iterator = $DB->request([
                'FROM' => $ios->getTable(),
                'WHERE' => [
                    'itemtype'  => $this->item->getType(),
                    'items_id'  => $this->item->fields['id'],
                    'NOT'       => ['id' => $ios->fields['id']]
                ]
            ]);

            foreach ($iterator as $row) {
                $ios->delete(['id' => $row['id']], true);
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        return true;
    }

    /**
     * Get current OS id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->operatingsystems_id;
    }

    public function getItemtype(): string
    {
        return \Item_OperatingSystem::class;
    }
}
