<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units;

use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDevicesCapacity;
use Glpi\Features\Clonable;
use Glpi\Search\SearchOption;
use Glpi\Tests\DbTestCase;
use Item_Devices;
use PHPUnit\Framework\Attributes\DataProvider;
use Toolbox;

class Item_DevicesTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDevicesCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['itemdevices_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Item_Devices$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDevicesCapacity::class)]);

        foreach ($CFG_GLPI['itemdevices_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Item_Devices::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public static function itemAffinitiesProvider(): iterable
    {
        yield 'Computer' => [
            'itemtype' => 'Computer',
            'expected' => [
                'Item_DeviceMotherboard',
                'Item_DeviceFirmware',
                'Item_DeviceProcessor',
                'Item_DeviceMemory',
                'Item_DeviceHardDrive',
                'Item_DeviceNetworkCard',
                'Item_DeviceDrive', // no config, matched due to the presence of `Computer` in $CFG_GLPI['itemdevices_itemaffinity']
                'Item_DeviceBattery',
                'Item_DeviceGraphicCard',
                'Item_DeviceSoundCard',
                'Item_DeviceControl', // no config, matched due to the presence of `Computer` in $CFG_GLPI['itemdevices_itemaffinity']
                'Item_DevicePci', // matches `*`
                'Item_DeviceCase',
                'Item_DevicePowerSupply',
                'Item_DeviceGeneric', // matches `*`
                'Item_DeviceSimcard',
                'Item_DeviceSensor',
                'Item_DeviceCamera',
            ],
        ];

        yield 'Printer' => [
            'itemtype' => 'Printer',
            'expected' => [
                'Item_DeviceFirmware',
                'Item_DeviceMemory',
                'Item_DeviceHardDrive',
                'Item_DeviceNetworkCard',
                'Item_DeviceBattery',
                'Item_DeviceControl',
                'Item_DevicePci', // matches `*`
                'Item_DeviceGeneric', // matches `*`
                'Item_DeviceSimcard',
            ],
        ];

        yield 'Monitor' => [
            'itemtype' => 'Monitor',
            'expected' => [
            ],
        ];
    }

    #[DataProvider('itemAffinitiesProvider')]
    public function testGetItemAffinities(string $itemtype, array $expected)
    {
        $this->assertEqualsCanonicalizing($expected, Item_Devices::getItemAffinities($itemtype));
    }

    /**
     * Test that the default search result columns for Item_Device* include an itemlink to the device
     * @return void
     */
    public function testDeviceSearchHasItemlinkByDefault()
    {
        foreach (Item_Devices::getDeviceTypes() as $device_type) {
            $search_options = SearchOption::getOptionsForItemtype($device_type);
            $default_columns = SearchOption::getDefaultToView($device_type);
            $has_itemlink = false;
            $this->assertNotEmpty($default_columns, "Device type $device_type has no default search result columns");
            foreach ($default_columns as $column) {
                if (($search_options[$column]['datatype'] ?? '') === 'itemlink') {
                    $has_itemlink = true;
                    break;
                }
            }
            $this->assertTrue(
                $has_itemlink,
                "Device type $device_type does not have an itemlink in its default search result columns"
            );
        }
    }

    public static function firmwareDeviceProvider(): iterable
    {
        yield 'hard drive' => ['Item_DeviceHardDrive'];
        yield 'network card' => ['Item_DeviceNetworkCard'];
        yield 'power supply' => ['Item_DevicePowerSupply'];
        yield 'graphic card' => ['Item_DeviceGraphicCard'];
    }

    #[DataProvider('firmwareDeviceProvider')]
    public function testFirmwareAffinity(string $itemtype): void
    {
        $this->assertSame(
            [\Item_DeviceFirmware::class],
            Item_Devices::getItemAffinities($itemtype)
        );
        $this->assertContains($itemtype, \Item_DeviceFirmware::getConcernedItems());
        $this->assertNotContains(
            \Item_DeviceGeneric::class,
            Item_Devices::getItemAffinities($itemtype)
        );
    }

    public function testNestedFirmwareRelation(): void
    {
        $this->login();

        $computer = $this->createItem(\Computer::class, [
            'name'        => __FUNCTION__,
            'entities_id' => 0,
        ]);
        $hard_drive = $this->createItem(\DeviceHardDrive::class, [
            'designation' => 'Test drive',
        ]);
        $item_hard_drive = $this->createItem(\Item_DeviceHardDrive::class, [
            'deviceharddrives_id' => $hard_drive->getID(),
            'itemtype'            => \Computer::class,
            'items_id'            => $computer->getID(),
        ]);
        $firmware = $this->createItem(\DeviceFirmware::class, [
            'designation' => 'Test drive',
            'version'     => '1.2.3',
        ]);
        $item_firmware = $this->createItem(\Item_DeviceFirmware::class, [
            'devicefirmwares_id' => $firmware->getID(),
            'itemtype'           => \Item_DeviceHardDrive::class,
            'items_id'           => $item_hard_drive->getID(),
        ]);
        $item_firmware_id = $item_firmware->getID();

        $this->assertArrayHasKey('Item_Devices$1', $item_hard_drive->defineAllTabs());
        $this->assertTrue($item_hard_drive->delete(['id' => $item_hard_drive->getID()], true));

        $this->assertFalse($item_firmware->getFromDB($item_firmware_id));
    }

    public static function specificityValueProvider(): iterable
    {
        yield 'value without an adaptive unit' => [
            'value' => 1234,
            'attributes' => [],
            'expected' => '1234',
        ];

        yield 'memory below one GiB' => [
            'value' => 512,
            'attributes' => ['display_unit' => 'binary_size'],
            'expected' => '512 MiB',
        ];

        yield 'memory at the GiB boundary' => [
            'value' => 1024,
            'attributes' => ['display_unit' => 'binary_size'],
            'expected' => '1 GiB',
        ];

        yield 'disk capacity in TiB' => [
            'value' => 1_876_951,
            'attributes' => ['display_unit' => 'binary_size'],
            'expected' => '1.79 TiB',
        ];

        yield 'frequency below one GHz' => [
            'value' => 800,
            'attributes' => ['display_unit' => 'frequency'],
            'expected' => '800 MHz',
        ];

        yield 'frequency in GHz' => [
            'value' => 2300,
            'attributes' => ['display_unit' => 'frequency'],
            'expected' => '2.3 GHz',
        ];
    }

    #[DataProvider('specificityValueProvider')]
    public function testFormatSpecificityValue(int|float|string $value, array $attributes, string $expected): void
    {
        $this->assertSame($expected, Item_Devices::formatSpecificityValue($value, $attributes));
    }

    public function testAdaptiveDisplayUnitsAreConfigured(): void
    {
        $this->assertSame(
            'binary_size',
            \Item_DeviceHardDrive::getSpecificities()['capacity']['display_unit']
        );
        $this->assertSame(
            'binary_size',
            \Item_DeviceMemory::getSpecificities()['size']['display_unit']
        );
        $this->assertSame(
            'frequency',
            \Item_DeviceProcessor::getSpecificities()['frequency']['display_unit']
        );
    }
}
