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
use Item_Devices;

use function Safe\strtotime;

abstract class Device extends InventoryAsset
{
    /**
     * Get existing entries from database
     *
     * @return array
     */
    protected function getExisting($itemdevicetable, $fk): array
    {
        global $DB;

        $db_existing = [];

        $iterator = $DB->request([
            'FROM'      => $itemdevicetable,
            'WHERE'     => [
                "$itemdevicetable.items_id"     => $this->item->fields['id'],
                "$itemdevicetable.itemtype"     => $this->item->getType(),
            ],
        ]);

        foreach ($iterator as $row) {
            $db_existing[$row[$fk]][] = $row;
        }

        return $db_existing;
    }

    public function handle()
    {
        global $DB;

        $devicetypes = Item_Devices::getItemAffinities($this->item->getType());

        $itemdevicetype = $this->getItemtype();
        if (in_array($itemdevicetype, $devicetypes)) {
            $value = $this->data;
            /** @var Item_Devices $itemdevice */
            $itemdevice = getItemForItemtype($itemdevicetype);

            $itemdevicetable = getTableForItemType($itemdevicetype);
            $devicetype      = $itemdevice::getDeviceType();
            $device          = getItemForItemtype($devicetype);
            $devicetable     = getTableForItemType($devicetype);
            $fk              = getForeignKeyFieldForTable($devicetable);

            $existing = $this->getExisting($itemdevicetable, $fk);

            foreach ($value as $val) {
                if (!isset($val->designation) || $val->designation == '') {
                    //cannot be empty
                    $val->designation = $itemdevice->getTypeName(1);
                }

                //force conversion if needed for date format as 2015-04-16T00:00:00Z
                // TODO : need to straighten up date format globally (especially for JSON inventory) which does not use the converter
                if (property_exists($val, 'date')) {
                    $val->date = date('Y-m-d', strtotime($val->date));
                }

                //create device or get existing device ID
                $device_input = $this->handleInput($val, $device);
                $device_criteria = $device->getImportCriteria();
                foreach (array_keys($device_criteria) as $device_criterion) {
                    if (!isset($device_input[$device_criterion]) && \isForeignKeyField($device_criterion)) {
                        $device_input[$device_criterion] = 0;
                    }
                }
                $device_id = $device->import($device_input + ['with_history' => false]);

                $i_criteria = $itemdevice->getImportCriteria();
                $fk_input = [
                    $fk                  => $device_id,
                    'itemtype'           => $this->item->getType(),
                    'items_id'           => $this->item->fields['id'],
                    'is_dynamic'         => 1,
                ];
                $i_input = $fk_input;

                //populate compare criteria
                foreach (array_keys($i_criteria) as $column) {
                    if (isset($device_input[$column])) {
                        $i_input[$column] = $device_input[$column];
                    }
                }

                //check if deviceitem should be updated or added.
                $equals = false;
                foreach ($existing[$device_id] ?? [] as $key => $existing_item) {
                    $equals = true;
                    foreach ($i_criteria as $field => $compare) {
                        if (!$equals) {
                            //no need to continue if one of conditions is false already
                            break;
                        }
                        $compare = explode(':', $compare);
                        if (!isset($i_input[$field]) && !isset($existing_item[$field])) {
                            //field not present, skip
                            continue;
                        }
                        switch ($compare[0]) {
                            case 'equal':
                                if (!isset($i_input[$field]) || $i_input[$field] != $existing_item[$field]) {
                                    $equals = false;
                                }
                                break;

                            case 'delta':
                                if (
                                    $i_input[$field] - (int) $compare[1] > $existing_item[$field]
                                    && $i_input[$field] + (int) $compare[1] < $existing_item[$field]
                                ) {
                                    $equals = false;
                                }
                                break;
                        }
                    }

                    if ($equals) {
                        $itemdevice->getFromDB($existing_item['id']);
                        $itemdevice_data = [
                            'id'                 => $existing_item['id'],
                            $fk                  => $device_id,
                            'itemtype'           => $this->item->getType(),
                            'items_id'           => $this->item->fields['id'],
                            'is_dynamic'         => 1,
                        ] + $this->handleInput($val, $itemdevice);
                        $itemdevice->update($itemdevice_data, true);
                        unset($existing[$device_id][$key]);
                        break;
                    }
                }

                if (!$equals) {
                    $itemdevice->getEmpty();
                    $itemdevice_data = [
                        $fk => $device_id,
                        'itemtype' => $this->item->getType(),
                        'items_id' => $this->item->fields['id'],
                        'is_dynamic' => 1,
                    ] + $this->handleInput($val, $itemdevice);
                    $itemdevice->add($itemdevice_data, [], !$this->item->isNewItem()); //log only if mainitem is not new
                    $this->itemdeviceAdded($itemdevice, $val);
                }

                if (count($existing[$device_id] ?? []) == 0) {
                    unset($existing[$device_id]);
                }
            }

            //remove remaining devices instances
            foreach ($existing as $data) {
                foreach ($data as $itemdevice_data) {
                    if ($itemdevice_data['is_dynamic'] == 1) {
                        $itemdevice->delete(['id' => $itemdevice_data['id']], true, !$this->item->isNewItem()); //log only if mainitem is not new
                    }
                }
            }
        }
    }

    protected function itemdeviceAdded(Item_Devices $itemdevice, $val)
    {
        //to be overrided
    }

    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        /** @var class-string<Item_Devices> $item_device */
        $item_device = $this->getItemtype();
        $affinities = $item_device::itemAffinity();
        return in_array('*', $affinities) || in_array($this->item->getType(), $item_device::itemAffinity());
    }
}
