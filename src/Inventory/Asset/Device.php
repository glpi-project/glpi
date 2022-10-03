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

use CommonDBTM;
use Item_Devices;

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
            'SELECT'    => [
                "$itemdevicetable.$fk",
                "is_dynamic"
            ],
            'FROM'      => $itemdevicetable,
            'WHERE'     => [
                "$itemdevicetable.items_id"     => $this->item->fields['id'],
                "$itemdevicetable.itemtype"     => $this->item->getType()
            ]
        ]);

        foreach ($iterator as $row) {
            $db_existing[$row[$fk]] = $row;
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
            $itemdevice = new $itemdevicetype();

            $itemdevicetable = getTableForItemType($itemdevicetype);
            $devicetype      = $itemdevicetype::getDeviceType();
            $device          = new $devicetype();
            $devicetable     = getTableForItemType($devicetype);
            $fk              = getForeignKeyFieldForTable($devicetable);

            $existing = $this->getExisting($itemdevicetable, $fk);
            $deleted_items = [];

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
                $device_id = $device->import(\Toolbox::addslashes_deep($this->handleInput($val, $device)) + ['with_history' => false]);

                //prepare data
                $input_data = \Toolbox::addslashes_deep([
                    $fk                  => $device_id,
                    'itemtype'           => $this->item->getType(),
                    'items_id'           => $this->item->fields['id'],
                ]);
                $itemdevice_data = $input_data + $this->handleInput($val, $itemdevice);

                //check if link between device and asset exist
                if ($itemdevice->getFromDBByCrit($input_data)) {
                    $itemdevice_data['id'] = $itemdevice->fields['id'];
                    $itemdevice->update($itemdevice_data, true, []);
                    unset($existing[$device_id]);
                } else {
                    $itemdevice->add($itemdevice_data, [], false);
                    $this->itemdeviceAdded($itemdevice, $val);
                    unset($existing[$device_id]);
                }
            }

            foreach ($existing as $deviceid => $data) {
                //first, remove items
                if ($data['is_dynamic'] == 1) {
                    $DB->delete(
                        $itemdevice->getTable(),
                        [
                            $fk => $deviceid
                        ]
                    );
                }
            }
        }
    }

    protected function itemdeviceAdded(Item_Devices $itemdevice, $val)
    {
       //to be overrided
    }
}
