<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use DbTestCase;
use Item_Devices;
use PHPUnit\Framework\Attributes\DataProvider;

class Item_DevicesTest extends DbTestCase
{
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
        $this->assertEquals($expected, Item_Devices::getItemAffinities($itemtype));
    }
}
