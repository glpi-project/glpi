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

use Computer_Item;
use Glpi\Inventory\Conf;
use Peripheral as GPeripheral;
use RuleImportAssetCollection;
use RuleMatchedLog;
use Toolbox;

class Peripheral extends InventoryAsset
{
    protected $extra_data = ['inputs' => null];

    public function prepare(): array
    {
        $mapping = [
            'manufacturer' => 'manufacturers_id',
        ];

        $existing = [];
        $usbvendor = new \USBVendor();

        foreach ($this->data as $k => &$val) {
            if (property_exists($val, 'name')) {
                foreach ($mapping as $origin => $dest) {
                    if (property_exists($val, $origin)) {
                        $val->$dest = $val->$origin;
                    }
                }

                if (
                    property_exists($val, 'vendorid')
                    && property_exists($val, 'productid')
                    && $val->vendorid != ''
                ) {
                   //manufacturer
                    if (
                        empty($val->manufacturers_id)
                        && $usb_manufacturer = $usbvendor->getManufacturer($val->vendorid)
                    ) {
                        $val->manufacturers_id = $usb_manufacturer;
                    }

                   //product name
                    if (
                        empty($val->productname)
                        && $usb_product = $usbvendor->getProductName($val->vendorid, $val->productid)
                    ) {
                        $val->productname = $usb_product;
                    }
                }

                if (property_exists($val, 'productname') && $val->productname != '') {
                    $val->name = $val->productname;
                }
                unset($val->productname);
                $val->is_dynamic = 1;

                $existing[$val->name] = $k;
            } else {
                unset($this->data[$k]);
            }
        }

        if ($this->extra_data['inputs'] !== null) {
           //hanlde inputs
            $point_types = [
                3 => 'Mouse',
                4 => 'Trackball',
                5 => 'Track Point',
                6 => 'Glide Point',
                7 => 'Touch Pad',
                8 => 'Touch Screen',
                9 => 'Mouse - Optical Sensor'
            ];

            foreach ($this->extra_data['inputs'] as $k => &$val) {
                foreach ($mapping as $origin => $dest) {
                    if (property_exists($val, $origin)) {
                        $val->$dest = $val->$origin;
                    }
                }

                $val->serial = '';
                $val->peripheraltypes_id = '';

                if (property_exists($val, 'layout')) {
                    $val->peripheraltypes_id = 'keyboard';
                } else if (property_exists($val, 'pointingtype') && isset($point_types[$val->pointingtype])) {
                    $val->peripheraltypes_id = $point_types[$val->pointingtype];
                }

                if (property_exists($val, 'name') && isset($existing[$val->name])) {
                    $this->data[$existing[$val->name]]->peripheraltypes_id = $val->peripheraltypes_id;
                } else {
                    $this->data[] = $val;
                }
            }
        }

        return $this->data;
    }

    public function handle()
    {
        global $DB;

        $rule = new RuleImportAssetCollection();
        $peripheral = new GPeripheral();
        $computer_Item = new Computer_Item();

        $peripherals = [];
        $value = $this->data;

        foreach ($value as $key => $val) {
            $handled_input = $this->handleInput($val);
            $input = [
                'itemtype'     => 'Peripheral',
                'name'         => $handled_input['name'] ?? '',
                'serial'       => $handled_input['serial'] ?? '',
                'entities_id'  => $this->item->fields['entities_id']
            ];
            $data = $rule->processAllRules($input, [], ['class' => $this, 'return' => true]);

            if (isset($data['found_inventories'])) {
                $items_id = null;
                $itemtype = 'Peripheral';
                if ($data['found_inventories'][0] == 0) {
                    // add peripheral
                    $handled_input['entities_id'] = $this->entities_id;
                    $items_id = $peripheral->add(Toolbox::addslashes_deep($handled_input), [], false);
                } else {
                    $items_id = $data['found_inventories'][0];
                    $peripheral->update(Toolbox::addslashes_deep(['id' => $items_id] + $handled_input), false);
                }

                $peripherals[] = $items_id;
                $rulesmatched = new RuleMatchedLog();
                $agents_id = $this->agent->fields['id'];
                if (empty($agents_id)) {
                    $agents_id = 0;
                }
                $inputrulelog = [
                    'date'      => date('Y-m-d H:i:s'),
                    'rules_id'  => $data['rules_id'],
                    'items_id'  => $items_id,
                    'itemtype'  => $itemtype,
                    'agents_id' => $agents_id,
                    'method'    => 'inventory'
                ];
                $rulesmatched->add($inputrulelog, [], false);
                $rulesmatched->cleanOlddata($items_id, $itemtype);
            }
        }

        $db_peripherals = [];
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_peripherals.id',
                'glpi_computers_items.id AS link_id',
                'glpi_computers_items.is_dynamic',
            ],
            'FROM'      => 'glpi_computers_items',
            'LEFT JOIN' => [
                'glpi_peripherals' => [
                    'FKEY' => [
                        'glpi_peripherals'      => 'id',
                        'glpi_computers_items'  => 'items_id'
                    ]
                ]
            ],
            'WHERE'     => [
                'itemtype' => 'Peripheral',
                'computers_id' => $this->item->fields['id'],
                'entities_id' => $this->entities_id,
                'glpi_peripherals.is_global' => 0
            ]
        ]);

        foreach ($iterator as $data) {
            $idtmp = $data['link_id'];
            unset($data['link_id']);
            $db_peripherals[$idtmp] = $data;
        }

        if (count($db_peripherals) && count($peripherals)) {
            foreach ($peripherals as $key => $peripherals_id) {
                foreach ($db_peripherals as $keydb => $data) {
                    if ($peripherals_id == $data['id']) {
                        unset($peripherals[$key]);
                        unset($db_peripherals[$keydb]);
                        $computer_Item->update(['id' => $keydb, 'is_dynamic' => 1], false);
                        break;
                    }
                }
            }
        }

        if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_peripherals)) {
           // Delete peripherals links in DB
            foreach ($db_peripherals as $keydb => $data) {
                if ($data['is_dynamic']) {
                    $computer_Item->delete(['id' => $keydb], true);
                }
            }
        }
        if (count($peripherals)) {
            foreach ($peripherals as $peripherals_id) {
                $input = [
                    'computers_id'    => $this->item->fields['id'],
                    'itemtype'        => 'Peripheral',
                    'items_id'        => $peripherals_id,
                    'is_dynamic'      => 1,
                ];
                $this->addOrMoveItem($input);
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        return $conf->import_peripheral == 1;
    }
}
