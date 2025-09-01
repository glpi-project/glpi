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

namespace tests\units\Glpi\Routing\Attribute;

use Computer;
use Glpi\Form\Form;
use Glpi\Routing\Attribute\ItemtypeListRoute;
use GlpiPlugin\Tester\Asset\Foo;
use GlpiPlugin\Tester\MyPsr4Dropdown;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ItemtypeListRouteTest extends GLPITestCase
{
    public static function itemtypeProvider(): iterable
    {
        yield [
            'itemtype'      => Computer::class,
            'expected_path' => '/front/computer.php',
        ];
        yield [
            'itemtype'      => Form::class,
            'expected_path' => '/front/form/form.php',
        ];
        yield [
            'itemtype'      => MyPsr4Dropdown::class,
            'expected_path' => '/front/mypsr4dropdown.php', // path must be relative to plugin path
        ];
        yield [
            'itemtype'      => Foo::class,
            'expected_path' => '/front/asset/foo.php', // path must be relative to plugin path
        ];
    }

    #[DataProvider('itemtypeProvider')]
    public function testConstructor(string $itemtype, string $expected_path): void
    {
        $attribute = new ItemtypeListRoute($itemtype);

        $this->assertEquals($expected_path, $attribute->getPath());
    }
}
