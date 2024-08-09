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

namespace tests\units;

use DbTestCase;
use GlpiPlugin\Tester\Controller\TestController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use GlpiPlugin\Tester\MyPsr4Class;

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

    #[dataProvider('dataItemType')]
    public function testIsPluginItemType($type, $plug, $class)
    {
        $res = isPluginItemType($type);
        if ($plug) {
            $this->assertSame(
                [
                    'plugin' => $plug,
                    'class'  => $class
                ],
                $res
            );
        } else {
            $this->assertFalse($res);
        }
    }

    /**
     * Checks autoload of some class located in Glpi namespace.
     */
    public function testAutoloadGlpiEvent()
    {
        $this->assertTrue(class_exists('Glpi\\Event'));
    }

    #[RunInSeparateProcess]
    #[DataProvider('provideClassesAutoload')]
    public function testPluginAutoloading(string $class): void
    {
        $this->assertTrue(\class_exists($class), \sprintf("Failed asserting that class %s exists", $class));
    }

    public static function provideClassesAutoload(): array
    {
        return [
            // PSR4 autoloader (registered during plugins initialization)
            MyPsr4Class::class => [MyPsr4Class::class],

            // Full PSR4 class with namespace
            TestController::class => [TestController::class],

            // Pseudo-PSR4 class with no namespace
            \PluginTesterMyPseudoPsr4Class::class => [\PluginTesterMyPseudoPsr4Class::class],

            // Legacy `inc/*.class.php` files
            \PluginTesterMyLegacyClass::class => [\PluginTesterMyLegacyClass::class],
        ];
    }
}
