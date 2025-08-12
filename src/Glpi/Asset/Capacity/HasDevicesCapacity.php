<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Asset\Capacity;

use CommonDevice;
use CommonGLPI;
use Glpi\Asset\CapacityConfig;
use Item_Devices;
use Override;
use Session;

class HasDevicesCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return CommonDevice::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return CommonDevice::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Includes sub-components like CPUs, drives or memory");
    }

    public function getCloneRelations(): array
    {
        return [
            Item_Devices::class,
        ];
    }

    public function getSearchOptions(string $classname): array
    {
        return Item_Devices::rawSearchOptionsToAdd($classname);
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToDevices($classname) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$d components attached to %2$d assets'),
            $this->countDevicesUsages($classname),
            $this->countAssetsLinkedToDevices($classname)
        );
    }

    private function countAssetsLinkedToDevices(string $classname): int
    {
        global $DB;

        $assets_ids = [];

        foreach (Item_Devices::getItemAffinities($classname) as $item_device_class) {
            $iterator = $DB->request([
                'SELECT'   => ['items_id'],
                'DISTINCT' => true,
                'FROM'     => $item_device_class::getTable(),
            ]);
            foreach ($iterator as $row) {
                $assets_ids[] = $row['items_id'];
            }
        }

        $assets_ids = array_unique($assets_ids);

        return count($assets_ids);
    }

    private function countDevicesUsages(string $classname): int
    {
        $count = 0;

        foreach (Item_Devices::getItemAffinities($classname) as $item_device_class) {
            $count += countDistinctElementsInTable(
                $item_device_class::getTable(),
                $item_device_class::getDeviceType()::getForeignKeyField(),
                [
                    'itemtype' => $classname,
                ]
            );
        }

        return $count;
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        global $CFG_GLPI;

        // Supports devices
        $this->registerToTypeConfig('itemdevices_types', $classname);

        // Add support of any device by default (related to devices types that not define their own config)
        $this->registerToTypeConfig('itemdevices_itemaffinity', $classname);

        // Add support of devices that have their own config entry defined
        foreach (Item_Devices::getDeviceTypes() as $item_device_class) {
            // see `Item_Devices::itemAffinity()`
            $key = str_replace('_', '', strtolower($item_device_class)) . '_types';
            if (array_key_exists($key, $CFG_GLPI)) {
                $this->registerToTypeConfig($key, $classname);
            }
        }

        CommonGLPI::registerStandardTab($classname, Item_Devices::class, 15);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        global $CFG_GLPI;

        foreach (Item_Devices::getDeviceTypes() as $item_device_class) {
            if (!\is_a($item_device_class, Item_Devices::class, true)) {
                continue;
            }

            //Delete related items
            $item_device = new $item_device_class();
            $item_device->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

            // Clean history related items
            $this->deleteRelationLogs($classname, $item_device_class);
        }

        // Clean display preferences
        $devices_search_options = Item_Devices::rawSearchOptionsToAdd($classname);
        $this->deleteDisplayPreferences($classname, $devices_search_options);

        // Unregister from types
        // Must be done after display preferences cleaning, as the SO list depends on these config entries
        $this->unregisterFromTypeConfig('itemdevices_types', $classname);
        $this->unregisterFromTypeConfig('itemdevices_itemaffinity', $classname);
        foreach (Item_Devices::getDeviceTypes() as $item_device_class) {
            // see `Item_Devices::itemAffinity()`
            $key = str_replace('_', '', strtolower($item_device_class)) . '_types';
            if (array_key_exists($key, $CFG_GLPI)) {
                $this->unregisterFromTypeConfig($key, $classname);
            }
        }
    }
}
