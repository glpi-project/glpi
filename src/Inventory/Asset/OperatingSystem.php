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

use Glpi\Inventory\Conf;
use Item_OperatingSystem;
use RuleDictionnaryOperatingSystemArchitectureCollection;
use Toolbox;

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
            'kernel_version' => 'operatingsystemkernelversions_id'
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

        if (
            property_exists($val, 'operatingsystemarchitectures_id')
            && $val->operatingsystemarchitectures_id != ''
        ) {
            $rulecollection = new RuleDictionnaryOperatingSystemArchitectureCollection();
            $res_rule = $rulecollection->processAllRules(['name' => $val->operatingsystemarchitectures_id]);
            if (isset($res_rule['name'])) {
                $val->operatingsystemarchitectures_id = $res_rule['name'];
            }
            if ($val->operatingsystemarchitectures_id == '0') {
                $val->operatingsystemarchitectures_id = '';
            }
        }
        if (property_exists($val, 'operatingsystemservicepacks_id') && $val->operatingsystemservicepacks_id == '0') {
            $val->operatingsystemservicepacks_id = '';
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

        $input_os = $this->handleInput($val) + [
            'itemtype'                          => $this->item->getType(),
            'items_id'                          => $this->item->fields['id'],
            'is_dynamic'                        => 1,
            'entities_id'                       => $this->item->fields['entities_id']
        ];

        if (!$ios->isNewItem()) {
           //OS exists, check for updates
            $same = true;
            foreach ($input_os as $key => $value) {
                if (isset($ios->fields[$key]) && $ios->fields[$key] != $value) {
                    $same = false;
                    break;
                }
            }
            if ($same === false) {
                $ios->update(['id' => $ios->getID()] + Toolbox::addslashes_deep($input_os));
            }
        } else {
            $ios->add(Toolbox::addslashes_deep($input_os));
        }

        $ioskey = 'operatingsystems_id' . $val->operatingsystems_id;
        $this->known_links[$ioskey] = $ios->fields['id'];
        $this->operatingsystems_id = $ios->fields['id'];

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
                $ios->delete($row['id'], true);
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
}
