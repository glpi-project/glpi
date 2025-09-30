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

namespace tests\units\Glpi\Http;

use Glpi\Http\SessionManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

class SessionManagerTest extends \DbTestCase
{
    public static function requestStateProvider(): iterable
    {
        foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
            // GLPI index
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc,
                'is_stateless' => false,
            ];

            // Central page
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/front/central.php',
                'is_stateless' => false,
            ];

            // Computer form
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/front/computer.form.php',
                'is_stateless' => false,
            ];

            //
            // Specific stateless cases below
            //

            // Symfony dev tools
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/_wdt/24a46e',
                'is_stateless' => true,
            ];
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/_profiler/24a46e',
                'is_stateless' => true,
            ];

            // API
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/api.php',
                'is_stateless' => true,
            ];
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/apirest.php',
                'is_stateless' => true,
            ];

            // CalDAV
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/caldav.php',
                'is_stateless' => true,
            ];

            // Embed dashboards
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/front/central.php?embed&dashboard=central&entities_id=0&is_recursive=1&token=e0c6ae82-e544-564f-b565-e37e6afd7ed6',
                'is_stateless' => true,
            ];
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/ajax/dashboard.php?action=get_card&dashboard=central&card_id=bn_count_Group&force=0&args%5Bcolor%5D=%23e0e0e0&args%5Bwidgettype%5D=bigNumber&args%5Buse_gradient%5D=0&args%5Blimit%5D=7&args%5Bcard_id%5D=bn_count_Group&args%5Bgridstack_id%5D=bn_count_Group_b84a93f2-a26c-49d7-82a4-5446697cc5b0&d_cache_key=8edf7b23477238c731e8a785ebc0f5a22f127e12&c_cache_key=&embed=1&token=e0c6ae82-e544-564f-b565-e37e6afd7ed6&entities_id=0&is_recursive=1',
                'is_stateless' => true,
            ];

            // Cron endpoint
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/front/cron.php',
                'is_stateless' => true,
            ];

            // Planing export
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/front/planning.php?genical',
                'is_stateless' => true,
            ];

            // root of tester plugin - no ending "/"
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/plugins/tester',
                'is_stateless' => true,
            ];

            // root of tester plugin - with ending "/"
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/plugins/tester/',
                'is_stateless' => true,
            ];

            // stateless URI of tester plugin
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/plugins/tester/StatelessURI',
                'is_stateless' => true,
            ];

            // any URI of tester plugin
            yield [
                'root_doc'     => $root_doc,
                'path'         => $root_doc . '/plugins/tester/AnyURI',
                'is_stateless' => false,
            ];
        }
    }

    #[DataProvider('requestStateProvider')]
    public function testIsResourceStateless(string $root_doc, string $path, bool $is_stateless): void
    {
        $instance = new SessionManager();
        $request  = $this->getMockedRequest($root_doc, $path);

        $this->assertEquals($is_stateless, $instance->isResourceStateless($request));
    }

    public function testIsResourceStatelessForFrontendFile(): void
    {
        vfsStream::setup(
            'glpi',
            null,
            [
                'public' => [
                    'pics' => [
                        'icon.png' => 'file contents',
                    ],
                    'foo' => [
                        'index.php' => '<?php //...',
                    ],
                ],
            ]
        );

        $instance = new SessionManager(vfsStream::url('glpi'));

        foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
            // Check an URL matching a front-end file
            $request = $this->getMockedRequest($root_doc, '/pics/icon.png');
            $this->assertEquals(true, $instance->isResourceStateless($request));

            // Check an URL not matching a valid front-end file
            $request = $this->getMockedRequest($root_doc, '/pics/invalid-icon.png');
            $this->assertEquals(false, $instance->isResourceStateless($request));

            // Check an URL matching a PHP file
            $request = $this->getMockedRequest($root_doc, '/foo/index.php');
            $this->assertEquals(false, $instance->isResourceStateless($request));
        }
    }

    public function testIsResourceStatelessForPlugin(): void
    {
        $instance = new SessionManager();

        $instance->registerPluginStatelessPath('myplugin', '#^/$#');
        $instance->registerPluginStatelessPath('myplugin', '#^/api/#');
        $instance->registerPluginStatelessPath('myplugin', '#^/front/state.less.php#');

        foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
            // Check an URL matching the index URL
            $request = $this->getMockedRequest($root_doc, '/plugins/myplugin/');
            $this->assertEquals(true, $instance->isResourceStateless($request));
            $request = $this->getMockedRequest($root_doc, '/marketplace/myplugin/');
            $this->assertEquals(true, $instance->isResourceStateless($request));

            // Check an URL matching the API stateless pattern
            $request = $this->getMockedRequest($root_doc, '/plugins/myplugin/api/Computer/1');
            $this->assertEquals(true, $instance->isResourceStateless($request));
            $request = $this->getMockedRequest($root_doc, '/marketplace/myplugin/api/Computer/1');
            $this->assertEquals(true, $instance->isResourceStateless($request));

            // Check an URL matching the front stateless pattern
            $request = $this->getMockedRequest($root_doc, '/plugins/myplugin/front/state.less.php?id=5');
            $this->assertEquals(true, $instance->isResourceStateless($request));
            $request = $this->getMockedRequest($root_doc, '/marketplace/myplugin/front/state.less.php?id=5');
            $this->assertEquals(true, $instance->isResourceStateless($request));

            // Check an URL not matching any stateless pattern
            $request = $this->getMockedRequest($root_doc, '/plugins/myplugin/front/foo.form.php');
            $this->assertEquals(false, $instance->isResourceStateless($request));
            $request = $this->getMockedRequest($root_doc, '/marketplace/myplugin/front/foo.form.php');
            $this->assertEquals(false, $instance->isResourceStateless($request));

            // Check that the pattern is not altering GLPI results
            $request = $this->getMockedRequest($root_doc, '/front/state.less.php?id=5');
            $this->assertEquals(false, $instance->isResourceStateless($request));

            // Check that the pattern is not altering another plugin results
            $request = $this->getMockedRequest($root_doc, '/plugins/anotherplugin/front/state.less.php?id=5');
            $this->assertEquals(false, $instance->isResourceStateless($request));
            $request = $this->getMockedRequest($root_doc, '/marketplace/anotherplugin/front/state.less.php?id=5');
            $this->assertEquals(false, $instance->isResourceStateless($request));
        }
    }

    private function getMockedRequest(string $root_doc, string $path): Request
    {
        $query_array = [];
        if ($query = \parse_url($path, PHP_URL_QUERY)) {
            \parse_str($query, $query_array);
        }
        $request = new Request($query_array);
        $request->server->set('SCRIPT_FILENAME', $root_doc . '/index.php');
        $request->server->set('SCRIPT_NAME', $root_doc . '/index.php');
        $request->server->set('REQUEST_URI', $path);

        return $request;
    }
}
