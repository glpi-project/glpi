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

namespace tests\units\Glpi\Inventory;

use Config;
use Glpi\Inventory\Conf;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

class ConfTest extends DbTestCase
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
        $defaults['auth_required'] = Conf::NO_AUTH;
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

    public static function invalidAuthRequiredProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'null value' => [null];
        yield 'unexpected value' => ['unexpected_value'];
    }

    #[DataProvider('invalidAuthRequiredProvider')]
    public function testSaveConfRejectsInvalidAuthRequiredWhenInventoryIsEnabled(mixed $auth_required): void
    {
        $this->login();

        Config::setConfigurationValues('inventory', [
            'enabled_inventory' => 1,
            'auth_required'     => Conf::NO_AUTH,
        ]);

        $conf = new Conf();
        $result = $conf->saveConf([
            'enabled_inventory' => 1,
            'auth_required'     => $auth_required,
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, [
            'Inventory is enabled. Please select a valid authorization header method.',
        ]);
        $this->assertSame(Conf::NO_AUTH, Config::getConfigurationValue('inventory', 'auth_required'));
    }
}
