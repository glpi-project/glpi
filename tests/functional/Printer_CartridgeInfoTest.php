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

namespace tests\units;

use DbTestCase;

/* Test for inc/printer_cartridgeinfo.class.php */

class Printer_CartridgeInfoTest extends DbTestCase
{
    public function testGetSpecificValueToDisplayWithAggregateData()
    {
        // Create printer
        $printer = new \Printer();
        $printers_id = $printer->add([
            'name' => 'Test Printer CMYK',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->assertGreaterThan(0, $printers_id);

        // Add cartridge info
        $cartridge_info = new \Printer_CartridgeInfo();
        $cartridge_info->add([
            'printers_id' => $printers_id,
            'property' => 'tonerblack',
            'value' => '71'
        ]);
        $cartridge_info->add([
            'printers_id' => $printers_id,
            'property' => 'tonercyan',
            'value' => '85'
        ]);

        // Simulate search engine raw_data structure with 'count' metadata
        $options = [
            'raw_data' => [
                'Printer_1400' => [
                    'count' => 2,
                    0 => [
                        'property' => 'tonerblack',
                        'value' => '71'
                    ],
                    1 => [
                        'property' => 'tonercyan',
                        'value' => '85'
                    ]
                ]
            ]
        ];

        $result = \Printer_CartridgeInfo::getSpecificValueToDisplay(
            '_virtual_toner_percent',
            [],
            $options
        );

        // Verify badges are displayed
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Black', $result);
        $this->assertStringContainsString('Cyan', $result);
        $this->assertStringContainsString('71%', $result);
        $this->assertStringContainsString('85%', $result);
        $this->assertStringContainsString('d-flex flex-wrap', $result);
        $this->assertStringContainsString('badge', $result);
    }

    public function testGetSpecificValueToDisplayWithNoCartridges()
    {
        $options = [
            'raw_data' => [
                'Printer_1400' => [
                    'count' => 0,
                ]
            ]
        ];

        $result = \Printer_CartridgeInfo::getSpecificValueToDisplay(
            '_virtual_toner_percent',
            [],
            $options
        );

        $this->assertTrue(
            empty($result) || $result === \CommonDBTM::getSpecificValueToDisplay('_virtual_toner_percent', [], $options)
        );
    }

    public function testGetSpecificValueToDisplayWithTonersAndDrums()
    {
        $options_toner = [
            'raw_data' => [
                'Printer_1400' => [
                    'count' => 2,
                    0 => ['property' => 'tonerblack', 'value' => '55'],
                    1 => ['property' => 'tonercyan', 'value' => '78']
                ]
            ]
        ];

        $options_drum = [
            'raw_data' => [
                'Printer_1401' => [
                    'count' => 2,
                    0 => ['property' => 'drumblack', 'value' => '32'],
                    1 => ['property' => 'drumcyan', 'value' => '45']
                ]
            ]
        ];

        // Test toner column
        $result_toner = \Printer_CartridgeInfo::getSpecificValueToDisplay(
            '_virtual_toner_percent',
            [],
            $options_toner
        );

        $this->assertStringContainsString('55%', $result_toner);
        $this->assertStringContainsString('78%', $result_toner);
        $this->assertStringNotContainsString('32%', $result_toner);

        // Test drum column
        $result_drum = \Printer_CartridgeInfo::getSpecificValueToDisplay(
            '_virtual_drum_percent',
            [],
            $options_drum
        );

        $this->assertStringContainsString('32%', $result_drum);
        $this->assertStringContainsString('45%', $result_drum);
        $this->assertStringNotContainsString('55%', $result_drum);
    }

    public function testGetSpecificValueToDisplayWithoutCountMetadata()
    {
        $options = [
            'raw_data' => [
                'Printer_1400' => [
                    0 => ['property' => 'tonerblack', 'value' => '90'],
                ]
            ]
        ];

        $result = \Printer_CartridgeInfo::getSpecificValueToDisplay(
            '_virtual_toner_percent',
            [],
            $options
        );

        $this->assertStringContainsString('90%', $result);
    }

    public function testGetSpecificValueToDisplayWithCMYK()
    {
        $options = [
            'raw_data' => [
                'Printer_1400' => [
                    'count' => 4,
                    0 => ['property' => 'tonercyan', 'value' => '85'],
                    1 => ['property' => 'tonermagenta', 'value' => '62'],
                    2 => ['property' => 'toneryellow', 'value' => '47'],
                    3 => ['property' => 'tonerblack', 'value' => '93']
                ]
            ]
        ];

        $result = \Printer_CartridgeInfo::getSpecificValueToDisplay(
            '_virtual_toner_percent',
            [],
            $options
        );

        // Verify all colors are present
        $this->assertStringContainsString('Cyan', $result);
        $this->assertStringContainsString('Magenta', $result);
        $this->assertStringContainsString('Yellow', $result);
        $this->assertStringContainsString('Black', $result);
        $this->assertStringContainsString('85%', $result);
        $this->assertStringContainsString('62%', $result);
        $this->assertStringContainsString('47%', $result);
        $this->assertStringContainsString('93%', $result);
    }

    public function testGetSpecificValueToDisplayWithMissingRawData()
    {
        $options = [];

        $result = \Printer_CartridgeInfo::getSpecificValueToDisplay(
            '_virtual_toner_percent',
            [],
            $options
        );

        $this->assertTrue(empty($result) || is_string($result));
    }
}
