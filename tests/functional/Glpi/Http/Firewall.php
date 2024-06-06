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

namespace tests\units\Glpi\Http;

use org\bovigo\vfs\vfsStream;

class Firewall extends \GLPITestCase
{
    protected function pathProvider(): iterable
    {
        // Init stream, required to compute relative path between GLPI_ROOT and PLUGINS_DIRECTORIES.
        vfsStream::setup(
            'glpi',
            null,
            [
                'marketplace' => [
                ],
                'mycustomplugindir' => [
                ],
                'plugins' => [
                ],
            ]
        );

        $strategy_no_check   = 'no_check';
        $default_for_core    = 'authenticated';
        $default_for_plugins = 'no_check';

        $protected_paths = ['/ajax/foo.php', '/ajax/bar/script.php', '/front/foo.php', '/front/dir/bar.php'];
        $unprotected_paths = ['index.php', 'api.php', '/dir/foo.php', '/a/ajax/test.php', '/bar/front/foo.php'];

        $directories = [
            '' => $default_for_core,
            '/marketplace/myplugin' => $default_for_plugins,
            '/mycustomplugindir/foo' => $default_for_plugins,
            '/plugins/bar' => $default_for_plugins,
        ];

        foreach ($directories as $path_prefix => $expected_strategy) {
            foreach ($protected_paths as $path) {
                yield [
                    'root_doc'          => '',
                    'path'              => $path_prefix . $path,
                    'expected_strategy' => $expected_strategy,
                ];
                yield [
                    'root_doc'          => '/glpi',
                    'path'              => '/glpi' . $path_prefix . $path,
                    'expected_strategy' => $expected_strategy,
                ];
                yield [
                    'root_doc'          => '/path/to/app',
                    'path'              => '/path/to/app' . $path_prefix . $path,
                    'expected_strategy' => $expected_strategy,
                ];

                // paths not matching root doc
                yield [
                    'root_doc'          => '/not/glpi',
                    'path'              => '/glpi' . $path_prefix . $path,
                    'expected_strategy' => $strategy_no_check,
                ];
                yield [
                    'root_doc'          => '',
                    'path'              => '/glpi' . $path_prefix . $path,
                    'expected_strategy' => $strategy_no_check,
                ];
            }

            foreach ($unprotected_paths as $path) {
                yield [
                    'root_doc'          => '',
                    'path'              => $path_prefix . $path,
                    'expected_strategy' => $strategy_no_check,
                ];
                yield [
                    'root_doc'          => '/glpi',
                    'path'              => '/glpi' . $path_prefix . $path,
                    'expected_strategy' => $strategy_no_check,
                ];
                yield [
                    'root_doc'          => '/path/to/app',
                    'path'              => '/path/to/app' . $path_prefix . $path,
                    'expected_strategy' => $strategy_no_check,
                ];
            }
        }
    }

    /**
     * @dataProvider pathProvider
     */
    public function testComputeDefaultStrategy(
        string $root_doc,
        string $path,
        string $expected_strategy
    ) {
        $this->newTestedInstance(
            $root_doc,
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/mycustomplugindir'), vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );
        $this->string($this->callPrivateMethod($this->testedInstance, 'computeDefaultStrategy', $path, null))->isEqualTo($expected_strategy);
    }
}
