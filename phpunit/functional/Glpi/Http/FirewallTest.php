<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Glpi\Http\Firewall;

class FirewallTest extends \GLPITestCase
{
    public function testComputeFallbackStrategy()
    {
        vfsStream::setup(
            'glpi',
            null,
            [
                'ajax' => [
                    'common.tabs.php' => '',
                    'dashboard.php' => '',
                    'getDropdown.php' => '',
                    'knowbase.php' => '',
                    'telemetry.php' => '',
                ],
                'front' => [
                    'foo' => [
                        'bar.php' => '',
                    ],
                    'form' => [
                        'form_renderer.php' => '',
                    ],
                    'central.php' => '',
                    'computer.php' => '',
                    'cron.php' => '',
                    'css.php' => '',
                    'document.send.php' => '',
                    'helpdesk.php' => '',
                    'helpdesk.faq.php' => '',
                    'inventory.php' => '',
                    'locale.php' => '',
                    'login.php' => '',
                    'logout.php' => '',
                    'lostpassword.php' => '',
                    'planning.php' => '',
                    'tracking.injector.php' => '',
                    'updatepassword.php' => '',
                ],
                'marketplace' => [
                    'myplugin' => [
                        'ajax' => [
                            'bar' => [
                                'script.php' => '',
                            ],
                            'foo.php' => '',
                        ],
                        'front' => [
                            'dir' => [
                                'bar.php' => '',
                            ],
                            'foo.php' => '',
                        ],
                        'index.php' => '',
                    ]
                ],
                'myplugindir' => [
                    'pluginb' => [
                        'ajax' => [
                            'foo' => [
                                'bar.php' => '',
                            ],
                            'barfoo.php' => '',
                        ],
                        'front' => [
                            'a' => [
                                'b.php' => '',
                            ],
                            'foo.php' => '',
                        ],
                        'test.php' => '',
                    ],
                ],
            ]
        );

        $default_for_core_legacy    = 'authenticated';
        $default_for_plugins_legacy = 'no_check';
        $default_for_symfony_routes = 'central_access';

        $default_mapping = [
            '/ajax/getDropdown.php'                     => $default_for_core_legacy,
            '/front/foo/bar.php'                        => $default_for_core_legacy,
            '/front/computer.php'                       => $default_for_core_legacy,
            '/Core/Route'                               => $default_for_symfony_routes,

            '/marketplace/myplugin/ajax/bar/script.php' => $default_for_plugins_legacy,
            '/marketplace/myplugin/ajax/foo.php'        => $default_for_plugins_legacy,
            '/marketplace/myplugin/front/dir/bar.php'   => $default_for_plugins_legacy,
            '/marketplace/myplugin/front/foo.php'       => $default_for_plugins_legacy,
            '/marketplace/myplugin/index.php'           => $default_for_plugins_legacy,
            '/marketplace/myplugin/PluginRoute'         => $default_for_symfony_routes,

            '/myplugindir/pluginb/ajax/foo/bar.php'     => $default_for_plugins_legacy,
            '/myplugindir/pluginb/ajax/barfoo.php'      => $default_for_plugins_legacy,
            '/myplugindir/pluginb/front/a/b.php'        => $default_for_plugins_legacy,
            '/myplugindir/pluginb/front/foo.php'        => $default_for_plugins_legacy,
            '/myplugindir/pluginb/test.php'             => $default_for_plugins_legacy,
            '/myplugindir/pluginb/Route/To/Something'   => $default_for_symfony_routes,
        ];

        foreach ($default_mapping as $path => $expected_strategy) {
            $this->dotestComputeFallbackStrategy(
                root_doc:          '',
                path:              $path,
                expected_strategy: $expected_strategy,
            );
            $this->dotestComputeFallbackStrategy(
                root_doc:          '/glpi',
                path:              '/glpi' . $path,
                expected_strategy: $expected_strategy,
            );
            $this->dotestComputeFallbackStrategy(
                root_doc:          '/path/to/app',
                path:              '/path/to/app' . $path,
                expected_strategy: $expected_strategy,
            );
        }

        // Hardcoded strategies
        foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
            // `/front/central.php` has a specific strategy only if some get parameters are defined
            $this->dotestComputeFallbackStrategy(
                root_doc:          $root_doc,
                path:              $root_doc . '/front/central.php',
                expected_strategy: $default_for_core_legacy,
            );

            $_GET['embed'] = '1';
            $_GET['dashboard'] = 'central';
            $this->dotestComputeFallbackStrategy(
                root_doc:          $root_doc,
                path:              $root_doc . '/front/central.php',
                expected_strategy: 'no_check',
            );
            unset($_GET['embed'], $_GET['dashboard']);

            // `/front/planning.php` has a specific strategy only if some get parameters are defined
            $this->dotestComputeFallbackStrategy(
                root_doc:          $root_doc,
                path:              $root_doc . '/front/planning.php',
                expected_strategy: $default_for_core_legacy,
            );

            $_GET['token'] = 'abc';
            $this->dotestComputeFallbackStrategy(
                root_doc:          $root_doc,
                path:              $root_doc . '/front/planning.php',
                expected_strategy: 'no_check',
            );
            unset($_GET['token']);

            $legacy_faq_urls = ['/ajax/knowbase.php', '/front/helpdesk.faq.php'];
            foreach ($legacy_faq_urls as $faq_url) {
                $this->dotestComputeFallbackStrategy(
                    root_doc:          $root_doc,
                    path:              $root_doc . $faq_url,
                    expected_strategy: 'faq_access',
                );
            }

            $legacy_no_check_urls = [
                '/ajax/common.tabs.php',
                '/ajax/dashboard.php',
                '/ajax/telemetry.php',
                '/front/cron.php',
                '/front/css.php',
                '/front/document.send.php',
                '/front/helpdesk.php',
                '/front/inventory.php',
                '/front/locale.php',
                '/front/login.php',
                '/front/logout.php',
                '/front/lostpassword.php',
                '/front/tracking.injector.php',
                '/front/updatepassword.php',
            ];
            foreach ($legacy_no_check_urls as $no_check_url) {
                $this->dotestComputeFallbackStrategy(
                    root_doc:          $root_doc,
                    path:              $root_doc . $no_check_url,
                    expected_strategy: 'no_check',
                );
            }
        }
    }

    private function dotestComputeFallbackStrategy(
        string $root_doc,
        string $path,
        string $expected_strategy
    ) {
        $instance = new Firewall(
            $root_doc,
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/myplugindir'), vfsStream::url('glpi/marketplace')]
        );
        $this->assertEquals(
            $expected_strategy,
            $instance->computeFallbackStrategy($path)
        );
    }
}
