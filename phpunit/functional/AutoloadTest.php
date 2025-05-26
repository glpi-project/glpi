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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

require_once __DIR__ . '/../Autoload.php';

class AutoloadTest extends DbTestCase
{
    public static function dataItemType(): array
    {
        return [
            ['Computer',                         false, false],
            ['Glpi\\Event',                      false, false],
            ['PluginFooBar',                     'Foo', 'Bar'],
            ['GlpiPlugin\\Foo\\Bar',             'Foo', 'Bar'],
            ['GlpiPlugin\\Foo\\Bar\\More',       'Foo', 'Bar\\More'],
            ['PluginFooBar\Invalid',             false, false],
            ['Glpi\Api\Deprecated\PluginFooBar', false, false],
            ['Invalid\GlpiPlugin\Foo\Bar',       false, false],
        ];
    }

    #[DataProvider('dataItemType')]
    public function testIsPluginItemType($type, $plug, $class)
    {
        $res = isPluginItemType($type);
        if ($plug) {
            $this->assertSame(
                [
                    'plugin' => $plug,
                    'class'  => $class,
                ],
                $res
            );
        } else {
            $this->assertFalse($res);
        }
    }

    #[RunInSeparateProcess]
    public function testPluginAutoloading()
    {
        // PSR4 autoloader (registered during plugins initialization)
        $this->assertTrue(class_exists('GlpiPlugin\\Tester\\MyPsr4Class'));

        // Pseudo-PSR4 class with no namespace
        $this->assertTrue(class_exists('PluginTesterMyPseudoPsr4Class'));

        // Legacy `inc/*.class.php` files
        $this->assertTrue(class_exists('PluginTesterMyLegacyClass'));
    }
}
