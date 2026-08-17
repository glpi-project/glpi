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

use DbTestCase;

require_once __DIR__ . '/../Autoload.php';

/* Test for inc/autoload.function.php */

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

    /**
     * @dataProvider dataItemType
     **/
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

    /**
     * Checks autoload of some class located in Glpi namespace.
     */
    public function testAutoloadGlpiEvent()
    {
        $this->assertTrue(class_exists('Glpi\\Event'));
    }

    public static function dataIsAPI(): iterable
    {
        yield 'apirest.php at server root' => [
            'script_name'     => '/apirest.php',
            'script_filename' => GLPI_ROOT . '/apirest.php',
            'request_uri'     => '/apirest.php/initSession',
            'expected'        => true,
        ];
        yield 'apirest.php under a directory prefix (alias, router or api/ rewrite)' => [
            'script_name'     => '/glpi/apirest.php',
            'script_filename' => GLPI_ROOT . '/apirest.php',
            'request_uri'     => '/glpi/api/initSession',
            'expected'        => true,
        ];
        yield 'apixmlrpc.php' => [
            'script_name'     => '/apixmlrpc.php',
            'script_filename' => GLPI_ROOT . '/apixmlrpc.php',
            'request_uri'     => '/apixmlrpc.php',
            'expected'        => true,
        ];

        // Non-API paths
        yield 'front page' => [
            'script_name'     => '/front/ticket.php',
            'script_filename' => GLPI_ROOT . '/front/ticket.php',
            'request_uri'     => '/front/ticket.php?redirect=apirest.php',
            'expected'        => false,
        ];
        yield 'front script with apirest.php as PATH_INFO' => [
            'script_name'     => '/front/central.php',
            'script_filename' => GLPI_ROOT . '/front/central.php',
            'request_uri'     => '/front/central.php/apirest.php/',
            'expected'        => false,
        ];
        yield 'similarly named script' => [
            'script_name'     => '/myapirest.php',
            'script_filename' => GLPI_ROOT . '/myapirest.php',
            'request_uri'     => '/myapirest.php',
            'expected'        => false,
        ];
    }

    /**
     * @dataProvider dataIsAPI
     */
    public function testIsAPI(
        ?string $script_name,
        string $script_filename,
        string $request_uri,
        bool $expected
    ) {
        $original_server = $_SERVER;

        if ($script_name === null) {
            unset($_SERVER['SCRIPT_NAME']);
        } else {
            $_SERVER['SCRIPT_NAME'] = $script_name;
        }
        $_SERVER['SCRIPT_FILENAME'] = $script_filename;
        $_SERVER['REQUEST_URI']     = $request_uri;

        $this->assertSame($expected, isAPI());

        // We restore $_SERVER for other tests
        $_SERVER = $original_server;
    }
}
