<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\Toolbox;

class ArrayPathAccessor extends \GLPITestCase
{
    public function testHasElementByArrayPath()
    {
        $test_array = [
            'foo' => [
                'bar' => [
                    'baz' => 'qux',
                ],
            ],
        ];
        $this->boolean(\Glpi\Toolbox\ArrayPathAccessor::hasElementByArrayPath($test_array, 'foo'))->isTrue();
        $this->boolean(\Glpi\Toolbox\ArrayPathAccessor::hasElementByArrayPath($test_array, 'foo.bar.baz'))->isTrue();
        $this->boolean(\Glpi\Toolbox\ArrayPathAccessor::hasElementByArrayPath($test_array, 'foo.bar.baz', ','))->isFalse();
        $this->boolean(\Glpi\Toolbox\ArrayPathAccessor::hasElementByArrayPath($test_array, 'foo,bar,baz', ','))->isTrue();
        $this->boolean(\Glpi\Toolbox\ArrayPathAccessor::hasElementByArrayPath($test_array, 'foo.bar.baz.qux'))->isFalse();
    }

    public function testGetElementByArrayPath()
    {
        $test_array = [
            'foo' => [
                'bar' => [
                    'baz' => 'qux',
                ],
            ],
        ];
        $this->array(\Glpi\Toolbox\ArrayPathAccessor::getElementByArrayPath($test_array, 'foo'))->isEqualTo(['bar' => ['baz' => 'qux']]);
        $this->string(\Glpi\Toolbox\ArrayPathAccessor::getElementByArrayPath($test_array, 'foo.bar.baz'))->isEqualTo('qux');
        $this->variable(\Glpi\Toolbox\ArrayPathAccessor::getElementByArrayPath($test_array, 'foo.bar.baz', ','))->isNull();
        $this->string(\Glpi\Toolbox\ArrayPathAccessor::getElementByArrayPath($test_array, 'foo,bar,baz', ','))->isEqualTo('qux');
        $this->variable(\Glpi\Toolbox\ArrayPathAccessor::getElementByArrayPath($test_array, 'foo.bar.baz.qux'))->isNull();
    }

    public function testSetElementByArrayPath()
    {
        $test_array = [
            'foo' => [
                'bar' => [
                    'baz' => 'qux',
                ],
            ],
        ];
        \Glpi\Toolbox\ArrayPathAccessor::setElementByArrayPath($test_array, 'foo.bar.baz', 'quux');
        $this->string(\Glpi\Toolbox\ArrayPathAccessor::getElementByArrayPath($test_array, 'foo.bar.baz'))->isEqualTo('quux');
        \Glpi\Toolbox\ArrayPathAccessor::setElementByArrayPath($test_array, 'foo.bar2.baz', 'quux');
        $this->string(\Glpi\Toolbox\ArrayPathAccessor::getElementByArrayPath($test_array, 'foo.bar2.baz'))->isEqualTo('quux');
    }

    public function testGetArrayPaths()
    {
        $test_array = [
            'foo' => [
                'bar' => [
                    'baz' => 'qux',
                ],
                'bar2' => 'test'
            ],
        ];

        $this->array(\Glpi\Toolbox\ArrayPathAccessor::getArrayPaths($test_array))->containsValues([
            'foo', 'foo.bar', 'foo.bar.baz', 'foo.bar2'
        ]);
    }
}
