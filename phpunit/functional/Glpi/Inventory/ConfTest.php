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

namespace tests\units\Glpi\Inventory;

use Glpi\Inventory\Conf;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

/**
 * Test class for src/Glpi/Inventory/conf.class.php
 */
class ConfTest extends \GLPITestCase
{
    public function testKnownInventoryExtensions()
    {
        $expected = [
            'json',
            'xml',
            'ocs',
        ];

        $conf = new Conf();
        $this->assertSame($expected, $conf->knownInventoryExtensions());
    }

    public static function inventoryfilesProvider(): array
    {
        return [
            [
                'file'      => 'computer.json',
                'expected'  => true,
            ], [
                'file'      => 'anything.xml',
                'expected'  => true,
            ], [
                'file'      => 'another.ocs',
                'expected'  => true,
            ], [
                'file'      => 'computer.xls',
                'expected'  => false,
            ],
        ];
    }

    #[DataProvider('inventoryfilesProvider')]
    public function testIsInventoryFile(string $file, bool $expected)
    {
        $conf = new Conf();
        $this->assertSame($expected, $conf->isInventoryFile($file));
    }


    public static function confProvider(): array
    {
        $provider = [];
        $defaults = Conf::getDefaults();
        foreach ($defaults as $key => $value) {
            $provider[] = [
                'key'    => $key,
                'value'  => $value,
            ];
        }
        return $provider;
    }

    #[DataProvider('confProvider')]
    public function testGetter($key, $value)
    {
        $conf = new Conf();
        $this->assertEquals($value, $conf->$key);
    }

    public function testErrorGetter()
    {
        $conf = new Conf();
        $this->assertNull($conf->doesNotExists);
        $this->hasPhpLogRecordThatContains('Property doesNotExists does not exists!', LogLevel::WARNING);
        $this->hasSessionMessages(WARNING, ['Property doesNotExists does not exists!']);
    }
}
