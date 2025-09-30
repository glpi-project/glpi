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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\SessionExpiredException;
use Glpi\Http\Firewall;
use KnowbaseItem;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

class FirewallTest extends \DbTestCase
{
    public function testComputeFallbackStrategy(): void
    {
        vfsStream::setup(
            'glpi',
            null,
            [
                'ajax' => [
                    'common.tabs.php' => '',
                    'dashboard.php' => '',
                    'getDropdown.php' => '',
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
                    'helpdesk.faq.php' => '',
                    'inventory.php' => '',
                    'locale.php' => '',
                    'login.php' => '',
                    'logout.php' => '',
                    'lostpassword.php' => '',
                    'planning.php' => '',
                    'tracking.injector.php' => '',
                    'initpassword.php' => '',
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
                        'public' => [
                            'css.php' => '',
                            'index.php' => '',
                        ],
                    ],
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
        $default_for_plugins_legacy = 'authenticated';
        $default_for_symfony_routes = 'central_access';

        $default_mapping = [
            '/ajax/getDropdown.php'                     => $default_for_core_legacy,
            '/front/foo/bar.php'                        => $default_for_core_legacy,
            '/front/computer.php'                       => $default_for_core_legacy,
            '/Core/Route'                               => $default_for_symfony_routes,
        ];

        foreach (['/marketplace', '/plugins'] as $plugin_path_prefix) {
            $default_mapping = \array_merge(
                $default_mapping,
                [
                    $plugin_path_prefix . '/myplugin/ajax/bar/script.php' => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/myplugin/ajax/foo.php'        => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/myplugin/front/dir/bar.php'   => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/myplugin/front/foo.php'       => $default_for_plugins_legacy,
                    // /public/css.php file accessed with its legacy path
                    $plugin_path_prefix . '/myplugin/public/css.php'      => $default_for_plugins_legacy,
                    // /public/css.php file accessed with the expected path
                    $plugin_path_prefix . '/myplugin/css.php'             => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/myplugin/index.php'           => $default_for_plugins_legacy,
                    // matches the `/public/index.php` legacy script
                    $plugin_path_prefix . '/myplugin/'                    => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/myplugin/PluginRoute'         => $default_for_symfony_routes,

                    $plugin_path_prefix . '/pluginb/ajax/foo/bar.php'     => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/pluginb/ajax/barfoo.php'      => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/pluginb/front/a/b.php'        => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/pluginb/front/foo.php'        => $default_for_plugins_legacy,
                    $plugin_path_prefix . '/pluginb/Route/To/Something'   => $default_for_symfony_routes,
                    $plugin_path_prefix . '/pluginb/'                     => $default_for_symfony_routes,
                    // outside the public dir, the file will not be served and the router will try to match a symfony route
                    $plugin_path_prefix . '/pluginb/test.php'             => $default_for_symfony_routes,
                ]
            );
        }

        foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
            // Default strategies
            foreach ($default_mapping as $path => $expected_strategy) {
                $this->dotestComputeFallbackStrategy(
                    root_doc: $root_doc,
                    path: $root_doc . $path,
                    expected_strategy: $expected_strategy,
                );
            }

            // Hardcoded strategies

            // `/front/planning.php` has a specific strategy only if some get parameters are defined
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/front/planning.php',
                expected_strategy: $default_for_core_legacy,
            );

            $legacy_faq_urls = ['/front/helpdesk.faq.php'];
            foreach ($legacy_faq_urls as $faq_url) {
                $this->dotestComputeFallbackStrategy(
                    root_doc: $root_doc,
                    path: $root_doc . $faq_url,
                    expected_strategy: 'faq_access',
                );
            }

            $legacy_no_check_urls = [
                '/ajax/common.tabs.php',
                '/ajax/telemetry.php',
                '/front/css.php',
                '/front/document.send.php',
                '/front/locale.php',
                '/front/login.php',
                '/front/logout.php',
                '/front/initpassword.php',
                '/front/lostpassword.php',
                '/front/updatepassword.php',
            ];
            foreach ($legacy_no_check_urls as $no_check_url) {
                $this->dotestComputeFallbackStrategy(
                    root_doc: $root_doc,
                    path: $root_doc . $no_check_url,
                    expected_strategy: 'no_check',
                );
            }

            // Specific strategies defined by plugins
            Firewall::addPluginStrategyForLegacyScripts('myplugin', '#^.*/foo.php#', 'faq_access');
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/marketplace/myplugin/ajax/foo.php',
                expected_strategy: 'faq_access',
            );
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/marketplace/myplugin/front/foo.php',
                expected_strategy: 'faq_access',
            );
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/marketplace/myplugin/front/dir/bar.php',
                expected_strategy: $default_for_plugins_legacy, // does not match the pattern
            );
            Firewall::addPluginStrategyForLegacyScripts('myplugin', '#^/front/dir/#', 'helpdesk_access');
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/marketplace/myplugin/ajax/foo.php',
                expected_strategy: 'faq_access',
            );
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/marketplace/myplugin/front/foo.php',
                expected_strategy: 'faq_access',
            );
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/marketplace/myplugin/front/dir/bar.php',
                expected_strategy: 'helpdesk_access',
            );
            Firewall::addPluginStrategyForLegacyScripts('myplugin', '#^/PluginRoute$#', 'helpdesk_access');
            $this->dotestComputeFallbackStrategy(
                root_doc: $root_doc,
                path: $root_doc . '/marketplace/myplugin/PluginRoute',
                expected_strategy: $default_for_symfony_routes, // fallback strategies MUST NOT apply to symfony routes
            );
            Firewall::resetPluginsStrategies();
        }
    }

    private function dotestComputeFallbackStrategy(
        string $root_doc,
        string $path,
        string $expected_strategy
    ): void {
        $instance = new Firewall(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/myplugindir'), vfsStream::url('glpi/marketplace')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_FILENAME', $root_doc . '/index.php');
        $request->server->set('SCRIPT_NAME', $root_doc . '/index.php');
        $request->server->set('REQUEST_URI', $path);

        $this->assertEquals(
            $expected_strategy,
            $instance->computeFallbackStrategy($request),
            $path
        );

        if (\str_contains($path, '/public/')) {
            $this->hasPhpLogRecordThatContains('User Deprecated: Plugins URLs containing the `/public` path are deprecated. You should remove the `/public` prefix from the URL.', LogLevel::INFO);
        }
    }

    public static function provideStrategy(): iterable
    {
        yield ['strategy' => Firewall::STRATEGY_AUTHENTICATED];
        yield ['strategy' => Firewall::STRATEGY_ADMIN_ACCESS];
        yield ['strategy' => Firewall::STRATEGY_CENTRAL_ACCESS];
        yield ['strategy' => Firewall::STRATEGY_FAQ_ACCESS];
        yield ['strategy' => Firewall::STRATEGY_HELPDESK_ACCESS];
    }

    #[DataProvider('provideStrategy')]
    public function testApplyStrategyWhenLoggedOut(string $strategy): void
    {
        $this->expectException(SessionExpiredException::class);

        $instance = new Firewall();
        $instance->applyStrategy($strategy);
    }

    #[DataProvider('provideStrategy')]
    public function testApplyStrategyWhenSessionIsCorrupted(string $strategy): void
    {
        $this->login();

        $_SESSION = [];

        $this->expectException(SessionExpiredException::class);

        $instance = new Firewall();
        $instance->applyStrategy($strategy);
    }

    public static function provideStrategyResults(): iterable
    {
        $admin_users = [
            TU_USER => TU_PASS,
            'glpi'  => 'glpi',
        ];

        foreach ($admin_users as $login => $pass) {
            yield [
                'strategy'      => Firewall::STRATEGY_AUTHENTICATED,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_ADMIN_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_CENTRAL_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_FAQ_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_HELPDESK_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => new AccessDeniedHttpException('The current profile does not use the simplified interface'),
            ];
        }

        $central_users = [
            'tech'   => 'tech',
            'normal' => 'normal',
        ];

        foreach ($central_users as $login => $pass) {
            yield [
                'strategy'      => Firewall::STRATEGY_AUTHENTICATED,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_ADMIN_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => new AccessDeniedHttpException('Missing administration rights.'),
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_CENTRAL_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_FAQ_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_HELPDESK_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => new AccessDeniedHttpException('The current profile does not use the simplified interface'),
            ];
        }

        $helpdesk_users = [
            'post-only' => 'postonly',
        ];
        foreach ($helpdesk_users as $login => $pass) {
            yield [
                'strategy'      => Firewall::STRATEGY_AUTHENTICATED,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_ADMIN_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => new AccessDeniedHttpException('Missing administration rights.'),
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_CENTRAL_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => new AccessDeniedHttpException('The current profile does not use the standard interface'),
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_FAQ_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
            yield [
                'strategy'      => Firewall::STRATEGY_HELPDESK_ACCESS,
                'credentials'   => [$login, $pass],
                'exception'     => null,
            ];
        }
    }

    #[DataProvider('provideStrategyResults')]
    public function testApplyStrategyWithUser(string $strategy, array $credentials, ?\Throwable $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }

        $instance = new Firewall();
        $instance->applyStrategy($strategy);
    }

    public static function provideFaqAccessStrategyResults(): iterable
    {
        yield [
            'use_public_faq'    => false,
            'knowbase_rights'   => KnowbaseItem::READFAQ,
            'exception'         => null,
        ];

        yield [
            'use_public_faq'    => false,
            'knowbase_rights'   => READ,
            'exception'         => null,
        ];

        yield [
            'use_public_faq'    => false,
            'knowbase_rights'   => 0,
            'exception'         => new AccessDeniedHttpException('Missing FAQ right'),
        ];

        yield [
            'use_public_faq'    => true,
            'knowbase_rights'   => KnowbaseItem::READFAQ,
            'exception'         => null,
        ];

        yield [
            'use_public_faq'    => true,
            'knowbase_rights'   => READ,
            'exception'         => null,
        ];

        yield [
            'use_public_faq'    => true,
            'knowbase_rights'   => 0,
            'exception'         => null,
        ];
    }

    #[DataProvider('provideFaqAccessStrategyResults')]
    public function testApplyStrategyFaqAccess(bool $use_public_faq, int $knowbase_rights, ?\Throwable $exception): void
    {
        global $CFG_GLPI;

        $this->login();

        $CFG_GLPI['use_public_faq'] = $use_public_faq;

        $_SESSION['glpiactiveprofile']['knowbase'] = $knowbase_rights;

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }

        $instance = new Firewall();
        $instance->applyStrategy(Firewall::STRATEGY_FAQ_ACCESS);
    }
}
