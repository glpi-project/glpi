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

use Glpi\Http\RequestRouterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

class RequestRouterTraitTest extends \DbTestCase
{
    public static function requestProvider(): iterable
    {
        foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
            // GLPI index
            yield [
                'root_doc'        => $root_doc,
                'path'            => $root_doc . '/',
                'normalized_path' => '/',
            ];

            // Central page
            yield [
                'root_doc'        => $root_doc,
                'path'            => $root_doc . '/front/central.php',
                'normalized_path' => '/front/central.php',
            ];

            // Computer form
            yield [
                'root_doc'        => $root_doc,
                'path'            => $root_doc . '/front/computer.form.php',
                'normalized_path' => '/front/computer.form.php',
            ];

            // root of tester plugin - no ending "/"
            yield [
                'root_doc'        => $root_doc,
                'path'            => $root_doc . '/plugins/tester',
                'normalized_path' => '/plugins/tester/',
            ];

            // root of tester plugin - with ending "/"
            yield [
                'root_doc'        => $root_doc,
                'path'            => $root_doc . '/plugins/tester/',
                'normalized_path' => '/plugins/tester/',
            ];

            // stateless URI of tester plugin
            yield [
                'root_doc'        => $root_doc,
                'path'            => $root_doc . '/plugins/tester/StatelessURI',
                'normalized_path' => '/plugins/tester/StatelessURI',
            ];

            // any URI of tester plugin
            yield [
                'root_doc'        => $root_doc,
                'path'            => $root_doc . '/plugins/tester/AnyURI',
                'normalized_path' => '/plugins/tester/AnyURI',
            ];
        }
    }

    #[DataProvider('requestProvider')]
    public function testNormalizePath(string $root_doc, string $path, string $normalized_path): void
    {
        $instance = new class {
            use RequestRouterTrait;

            public function __construct()
            {
                $this->glpi_root = GLPI_ROOT;
                $this->plugin_directories = GLPI_PLUGINS_DIRECTORIES;
            }
        };

        $request = $this->getMockedRequest($root_doc, $path);
        $this->assertEquals($normalized_path, $this->callPrivateMethod($instance, 'normalizePath', $request));
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
