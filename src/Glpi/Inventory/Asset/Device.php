<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use DeviceFirmware;
use Glpi\Inventory\Conf;
use Item_DeviceFirmware;
use Item_Devices;
use PCIVendor;
use stdClass;
use USBVendor;

use function Safe\strtotime;

abstract class Device extends InventoryAsset
{
    /**
     * Get existing entries from database
     *
     * @param string $itemdevicetable
     * @param string $fk
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function getExisting($itemdevicetable, $fk): array
    {
        global $DB;

        $db_existing = [];

        $iterator = $DB->request([
            'FROM'      => $itemdevicetable,
            'WHERE'     => [
                "$itemdevicetable.items_id"     => $this->item->fields['id'],
                "$itemdevicetable.itemtype"     => $this->item::class,
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

        $devicetypes = Item_Devices::getItemAffinities($this->item::class);

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
            $supports_firmware = in_array(
                Item_DeviceFirmware::class,
                Item_Devices::getItemAffinities($itemdevice::class),
                true
            );

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

                $device_value = clone $val;
                if (
                    property_exists($device_value, 'firmware')
                    && $supports_firmware
                ) {
                    unset($device_value->firmware);
                }

                //create device or get existing device ID
                $device_input = $this->handleInput($device_value, $device);
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
                    'itemtype'           => $this->item::class,
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
                            'itemtype'           => $this->item::class,
                            'items_id'           => $this->item->fields['id'],
                            'is_dynamic'         => 1,
                        ] + $this->handleInput($device_value, $itemdevice);
                        $itemdevice->update($itemdevice_data, true);
                        unset($existing[$device_id][$key]);
                        break;
                    }
                }

                if (!$equals) {
                    $itemdevice->getEmpty();
                    $itemdevice_data = [
                        $fk => $device_id,
                        'itemtype' => $this->item::class,
                        'items_id' => $this->item->fields['id'],
                        'is_dynamic' => 1,
                    ] + $this->handleInput($device_value, $itemdevice);
                    $itemdevice->add($itemdevice_data, [], !$this->item->isNewItem()); //log only if mainitem is not new
                    $this->itemdeviceAdded($itemdevice, $val);
                }

                $this->itemdeviceHandled($itemdevice, $val, $device_input, $supports_firmware);

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

    /**
     * Apply manufacturer and product name from a PCI controller to an asset value.
     *
     * Looks up vendor/product information in the PCI database using either a combined
     * `pciid` field (colon-separated, e.g. "8086:1234") or separate `vendorid`/`productid`
     * fields on the controller object, then sets `manufacturers_id` and `designation` on `$val`.
     *
     * @param stdClass $val        Asset value object to update
     * @param stdClass $controller Controller object containing PCI identifiers
     *
     * @return bool True if any field was updated
     */
    protected function applyPciInfoFromController(stdClass $val, stdClass $controller): bool
    {
        $pcivendor = new PCIVendor();
        $updated = false;

        if (property_exists($controller, 'pciid')) {
            $exploded = explode(":", $controller->pciid);
            if (!empty($exploded[0]) && ($pci_manufacturer = $pcivendor->getManufacturer($exploded[0]))) {
                $val->manufacturers_id = $pci_manufacturer;
                $updated = true;
            }
            if (!empty($exploded[0]) && isset($exploded[1]) && ($pci_product = $pcivendor->getProductName($exploded[0], $exploded[1]))) {
                $val->designation = $pci_product;
                $updated = true;
            }
        } elseif (property_exists($controller, 'vendorid')) {
            if ($pci_manufacturer = $pcivendor->getManufacturer($controller->vendorid)) {
                $val->manufacturers_id = $pci_manufacturer;
                $updated = true;
            }
            if (property_exists($controller, 'productid')) {
                if ($pci_product = $pcivendor->getProductName($controller->vendorid, $controller->productid)) {
                    $val->designation = $pci_product;
                    $updated = true;
                }
            }
        }

        return $updated;
    }

    /**
     * Apply manufacturer and product name from a USB device to an asset value.
     *
     * Looks up vendor/product information in the USB database using separate
     * `vendorid`/`productid` fields on the usb object, then sets
     * `manufacturers_id` and `designation` on `$val`.
     *
     * @param stdClass $val Asset value object to update
     * @param stdClass $usb USB device object containing identifiers
     *
     * @return bool True if any field was updated
     */
    protected function applyUsbInfoFromDevice(stdClass $val, stdClass $usb): bool
    {
        $usbvendor = new USBVendor();
        $updated = false;

        if (property_exists($usb, 'vendorid')) {
            if ($usb_manufacturer = $usbvendor->getManufacturer($usb->vendorid)) {
                $val->manufacturers_id = $usb_manufacturer;
                $updated = true;
            }
            if (property_exists($usb, 'productid')) {
                if ($usb_product = $usbvendor->getProductName($usb->vendorid, $usb->productid)) {
                    $val->designation = $usb_product;
                    $updated = true;
                }
            }
        }

        return $updated;
    }

    /**
     * @param Item_Devices $itemdevice
     * @param stdClass $val
     *
     * @return void
     */
    protected function itemdeviceAdded(Item_Devices $itemdevice, $val)
    {
        //to be overridden
    }

    /**
     * Handle data that depends on the resulting component instance.
     *
     * @param Item_Devices        $itemdevice
     * @param object              $val
     * @param array<string,mixed> $device_input
     * @param bool                $supports_firmware
     *
     * @return void
     */
    protected function itemdeviceHandled(
        Item_Devices $itemdevice,
        object $val,
        array $device_input,
        bool $supports_firmware
    ): void {
        if (
            !$supports_firmware
            || !property_exists($val, 'firmware')
        ) {
            return;
        }

        $item_firmware = new Item_DeviceFirmware();
        $existing_links = $item_firmware->find([
            'itemtype'   => $itemdevice::class,
            'items_id'   => $itemdevice->getID(),
            'is_dynamic' => 1,
            'is_deleted' => 0,
        ]);

        $version = trim((string) $val->firmware);
        if ($version === '') {
            foreach ($existing_links as $existing_link) {
                $item_firmware->delete(
                    ['id' => $existing_link['id']],
                    true,
                    !$this->item->isNewItem()
                );
            }
            return;
        }

        $firmware = new DeviceFirmware();
        $firmware_id = $firmware->import([
            'designation'            => $device_input['designation'] ?? $itemdevice->getTypeName(1),
            'devicefirmwaretypes_id' => 0,
            'manufacturers_id'       => (int) ($device_input['manufacturers_id'] ?? 0),
            'version'                => $version,
            'entities_id'            => $itemdevice->getEntityID(),
            'with_history'           => false,
        ]);
        if (!$firmware_id) {
            return;
        }

        $existing_link = array_shift($existing_links);
        if ($existing_link === null) {
            $item_firmware->add([
                'itemtype'           => $itemdevice::class,
                'items_id'           => $itemdevice->getID(),
                'devicefirmwares_id' => $firmware_id,
                'is_dynamic'         => 1,
            ], [], !$this->item->isNewItem());
        } elseif ($existing_link['devicefirmwares_id'] != $firmware_id) {
            $item_firmware->update([
                'id'                 => $existing_link['id'],
                'devicefirmwares_id' => $firmware_id,
            ], true);
        }

        // Only one firmware value can be reported for a component by an inventory.
        foreach ($existing_links as $extra_link) {
            $item_firmware->delete(
                ['id' => $extra_link['id']],
                true,
                !$this->item->isNewItem()
            );
        }
    }

    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        /** @var class-string<Item_Devices> $item_device */
        $item_device = $this->getItemtype();
        $affinities = $item_device::itemAffinity();
        return in_array('*', $affinities) || in_array($this->item::class, $item_device::itemAffinity());
    }
}
