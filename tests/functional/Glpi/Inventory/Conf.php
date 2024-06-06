<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Inventory;

/**
 * Test class for src/Glpi/Inventory/conf.class.php
 */
class Conf extends \GLPITestCase
{
    public function testKnownInventoryExtensions()
    {
        $expected = [
            'json',
            'xml',
            'ocs'
        ];

        $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->knownInventoryExtensions())
            ->isIdenticalTo($expected);
    }

    protected function inventoryfilesProvider(): array
    {
        return [
            [
                'file'      => 'computer.json',
                'expected'  => true
            ], [
                'file'      => 'anything.xml',
                'expected'  => true
            ], [
                'file'      => 'another.ocs',
                'expected'  => true
            ], [
                'file'      => 'computer.xls',
                'expected'  => false
            ]
        ];
    }

    /**
     * @dataProvider inventoryfilesProvider
     */
    public function testIsInventoryFile(string $file, bool $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->isInventoryFile($file))
            ->isIdenticalTo($expected);
    }


    protected function confProvider(): array
    {
        $provider = [];
        $defaults = \Glpi\Inventory\Conf::getDefaults();
        foreach ($defaults as $key => $value) {
            $provider[] = [
                'key'    => $key,
                'value'  => $value
            ];
        }
        return $provider;
    }

    /**
     * @dataProvider confProvider
     */
    public function testGetter($key, $value)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->variable($this->testedInstance->$key)
            ->isEqualTo($value);
    }

    public function testErrorGetter()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->when(
                function () {
                    $this->variable($this->testedInstance->doesNotExists)->isEqualTo(null);
                    $this->hasSessionMessages(WARNING, ['Property doesNotExists does not exists!']);
                }
            )->error
               ->withType(E_USER_WARNING)
               ->withMessage('Property doesNotExists does not exists!')
               ->exists();
    }
}
