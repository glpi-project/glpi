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
use Symfony\Component\HttpFoundation\Request;
use Glpi\Controller\LegacyFileLoadController;

class LegacyRouterListener extends \GLPITestCase
{
    protected function fileProvider(): iterable
    {
        vfsStream::setup(
            'glpi',
            null,
            [
                'ajax'  => [
                    'thereisnoindex.php' => '<?php echo("/ajax/thereisnoindex.php");',
                ],
                'front' => [
                    'index.php' => '<?php echo("/front/index.php");',
                    'whatever.php' => '<?php echo("/whatever.php");',
                ],
                'js' => [
                    'common.js' => 'console.log("ok");',
                ],
                'marketplace' => [
                    'myplugin' => [
                        'front' => [
                            'test.php' => '<?php echo("/marketplace/myplugin/front/test.php");',
                            'some.dir' => [
                                'file.test.php' => '<?php echo("/marketplace/myplugin/front/some.dir/file.test.php");',
                            ],
                        ],
                    ],
                ],
                'plugins' => [
                    'mystaleplugin' => [
                        'front' => [
                            'page.php5' => '<?php echo("/plugins/mystaleplugin/front/page.php5");',
                        ],
                    ],
                ],
            ]
        );

        // Path to an existing directory that does not have a `index.php` script.
        yield '/ajax' => [
            'path'            => '/ajax',
            'target_path'     => '/ajax',
            'target_pathinfo' => null,
            'included'        => false,
        ];
        yield '///ajax' => [
            'path'            => '///ajax', // triple `/` in URL
            'target_path'     => '/ajax',
            'target_pathinfo' => null,
            'included'        => false,
        ];

        // Path to an invalid PHP script.
        yield '/is/not/valid.php' => [
            'path'            => '/is/not/valid.php',
            'target_path'     => '/is/not/valid.php',
            'target_pathinfo' => null,
            'included'        => false,
        ];

        // Path to a `index.php` script.
        yield '/front/index.php' => [
            'path'            => '/front/index.php',
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];
        yield '//front/index.php' => [
            'path'            => '//front/index.php', // double `/` in URL
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];

        // Path to an existing file, but containing an extra PathInfo
        yield '/front/whatever.php/' => [
            'path'            => '/front/whatever.php/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/',
            'included'        => true,
        ];
        yield '/front/whatever.php/endpoint/' => [
            'path'            => '/front/whatever.php/endpoint/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/endpoint/',
            'included'        => true,
        ];
        yield '/front/whatever.php//endpoint/' => [
            'path'            => '/front/whatever.php//endpoint/', // double `/` in URL
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/endpoint/',
            'included'        => true,
        ];
        yield '/front/whatever.php/GlpiPlugin%5CNamespace%5CUnexemple/' => [
            'path'            => '/front/whatever.php/GlpiPlugin%5CNamespace%5CUnexemple/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/GlpiPlugin\Namespace\Unexemple/',
            'included'        => true,
        ];
        yield '/front/whatever.php/calendars/users/J.DUPONT/calendar/' => [
            'path'            => '/front/whatever.php/calendars/users/J.DUPONT/calendar/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/calendars/users/J.DUPONT/calendar/',
            'included'        => true,
        ];

        // Path to an existing directory that have a `index.php` script.
        yield '/front' => [
            'path'            => '/front',
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];

        // Path to a JS file
        yield '/js/common.js' => [
            'path'            => '/js/common.js',
            'target_path'     => '/js/common.js',
            'target_pathinfo' => null,
            'included'        => false,
        ];

        // Path to a PHP script of a plugin located inside the `/marketplace` dir, but accessed with the `/plugins` path.
        yield '/plugins/myplugin/front/test.php' => [
            'path'            => '/plugins/myplugin/front/test.php',
            'target_path'     => '/marketplace/myplugin/front/test.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];
        yield '/plugins/myplugin/front/some.dir/file.test.php/path/to/item' => [
            'path'            => '/plugins/myplugin/front/some.dir/file.test.php/path/to/item',
            'target_path'     => '/marketplace/myplugin/front/some.dir/file.test.php',
            'target_pathinfo' => '/path/to/item',
            'included'        => true,
        ];

        // Path to a PHP script in a directory that has a dot in its name.
        yield '/plugins/myplugin/front/some.dir/file.test.php' => [
            'path'            => '/plugins/myplugin/front/some.dir/file.test.php',
            'target_path'     => '/marketplace/myplugin/front/some.dir/file.test.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];
        yield '/plugins/myplugin/front/some.dir/file.test.php/path/to/item' => [
            'path'            => '/plugins/myplugin/front/some.dir/file.test.php/path/to/item',
            'target_path'     => '/marketplace/myplugin/front/some.dir/file.test.php',
            'target_pathinfo' => '/path/to/item',
            'included'        => true,
        ];

        // Path to a `.php5` script.
        yield '/plugins/mystaleplugin/front/page.php5' => [
            'path'            => '/plugins/mystaleplugin/front/page.php5',
            'target_path'     => '/plugins/mystaleplugin/front/page.php5',
            'target_pathinfo' => null,
            'included'        => true,
        ];
    }

    /**
     * @dataProvider fileProvider
     */
    public function testRunLegacyRouterResponse(
        string $path,
        string $target_path,
        ?string $target_pathinfo,
        bool $included
    ): void {
        $this->newTestedInstance(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $path);

        $response = $this->callPrivateMethod($this->testedInstance, 'runLegacyRouter', $request);

        $this->variable($response)->isNull();
        if ($included === false) {
            $this->variable($request->attributes->get('_controller'))->isNull();
            $this->variable($request->attributes->get('_glpi_file_to_load'))->isNull();
        } else {
            $this->string($request->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
            $this->string($request->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi') . $target_path);
        }
    }

    protected function pathProvider(): iterable
    {
        $allowed_glpi_php_paths = [
            '/ajax/script.php',
            '/front/page.php',
            '/install/install.php',
            '/install/update.php',
        ];
        $disallowed_glpi_php_paths = [
            '/.atoum.php',
            '/ajax-script.php',
            '/index.php', // served by a Symfony controller
            '/status.php', // served by a Symfony controller
            '/bin/console',
            '/config/config_db.php',
            '/files/PHP/1c/db5d8ce30068f6259d1b926165619386c58f1e.PHP',
            '/front-page.php',
            '/front/.hidden.php',
            '/inc/includes.php',
            '/install/index.php',
            '/install/install.old.php',
            '/install/migrations/update_9.5.x_to_10.0.0.php',
            '/node_modules/flatted/php/flatted.php',
            '/src/Computer.php',
            '/tests/bootstrap.php',
            '/tools/psr4.php',
            '/vendor/htmlawed/htmlawed/htmLawed.php',
        ];
        $allowed_glpi_static_paths = [
            '/css/lib/fontsource/inter/files/inter-latin-100-normal.woff2',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.eot',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.ttf',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.woff',
            '/css/lib/xxx/style.css',
            '/css/lib/xxx/webfont.otf',
            '/js/common.js',
            '/pics/expand.gif',
            '/pics/image.jpeg',
            '/pics/icones/ai-dist.png',
            '/pics/20/schema.svg',
            '/public/anyfile.ext',
            '/public/subdir/data.json',
            '/sound/sound_a.mp3',
            '/sound/sound_a.ogg',
            '/sound/sound_a.wav',
            '/videos/demo.mp4',
            '/videos/demo.ogm',
            '/videos/demo.ogv',
            '/videos/demo.webm',
        ];
        $disallowed_glpi_static_paths = [
            '/.htaccess',
            '/.tx/config',
            '/config/glpicrypt.key',
            '/css/palettes/auror.scss',
            '/files/_log/php-errors.log',
            '/install/mysql/.htaccess',
            '/install/mysql/glpi-empty.sql',
            '/locales/en_GB.po',
            '/locales/en_GB.mo',
            '/locales/glpi.pot',
            '/node_modules/.bin/webpack-cli',
            '/node_modules/rrule/dist/esm/demo/demo.js',
            '/pics/charts/sources.md',
            '/resources/Rules/RuleAsset.xml',
            '/templates/pages/login.html.twig',
            '/tests/fixtures/uploads/bar.png',
            '/tests/run_tests.sh',
            '/tools/psr12.sh',
        ];
        // only allowed PHP scripts are served
        $served_glpi_paths = $allowed_glpi_php_paths;
        // any static file and disallowed PHP scripts are ignored
        $ignored_glpi_paths = array_merge(
            $allowed_glpi_static_paths,
            $disallowed_glpi_php_paths,
            $disallowed_glpi_static_paths
        );

        foreach ($served_glpi_paths as $path) {
            yield $path => [
                'path'      => $path,
                'is_served' => true,
            ];
            yield '/' . $path => [
                'path'      => '/' . $path, // extra leading slash should not change result
                'is_served' => true,
            ];
        }
        foreach ($ignored_glpi_paths as $path) {
            yield $path => [
                'path'      => $path,
                'is_served' => false,
            ];
            yield '/' . $path => [
                'path'      => '/' . $path, // extra leading slash should not change result
                'is_served' => false,
            ];
        }

        $allowed_plugins_php_paths = [
            '/ajax/script.php',
            '/ajax/subdir/messages.php',
            '/css/styles.css.php',
            '/css/palette/test.css.php',
            '/front/page.php',
            '/front/subdir/form.php',
            '/index.php',
            '/js/scripts.js.php',
            '/path/to/any/index.php',
            '/path/to/any/file.css.php',
            '/path/to/any/file.js.php',
            '/public/api.php',
            '/report/mycustomreport.php',
            '/report/metrics/metrics.php',
            '/root_script.php',
        ];
        $disallowed_plugins_php_paths = [
            '/.atoum.php',
            '/hook.php',
            '/inc/config.class.php',
            '/install/install.php',
            '/install/update_2.5_3.0.php',
            '/lib/php-saml/settings_example.php',
            '/scripts/cli_install.php',
            '/setup.php',
            '/tools/dump.php',
            '/vendor/autoload.php',
            '/vendor/fpdf/fpdf/src/Fpdf/Fpdf.php',
        ];
        $allowed_plugins_static_paths = [
            '/css/lib/fontsource/inter/files/inter-latin-100-normal.woff2',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.eot',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.ttf',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.woff',
            '/css/lib/xxx/webfont.otf',
            '/css/dev.css.map',
            '/css/base.css',
            '/documentation/index.htm',
            '/js/common.js',
            '/pics/expand.gif',
            '/pics/image.jpeg',
            '/pics/icones/ai-dist.png',
            '/pics/20/schema.svg',
            '/public/anyfile.ext',
            '/public/subdir/data.json',
            '/readme/api/v1.html',
            '/sound/sound_a.mp3',
            '/sound/sound_a.ogg',
            '/sound/sound_a.wav',
            '/videos/demo.mp4',
            '/videos/demo.ogm',
            '/videos/demo.ogv',
            '/videos/demo.webm',
        ];
        $disallowed_plugins_static_paths = [
            '/.gitignore',
            '/.htaccess',
            '/.tx/config',
            '/changelog.txt',
            '/css/styles.scss',
            '/docker-compose.yml',
            '/files/templates/holidays_template.txt',
            '/locales/en_GB.po',
            '/locales/en_GB.mo',
            '/locales/myplugin.pot',
            '/myplugin.xml',
            '/node_modules/.bin/webpack-cli',
            '/node_modules/rrule/dist/esm/demo/demo.js',
            '/package.json',
            '/package-lock.json',
            '/patch/fix-pear-image-barcode.patch',
            '/README.md',
            '/sql/update-1.2.0.sql',
            '/templates/.htaccess',
            '/templates/config.html.twig',
            '/templates/type.class.tpl',
            '/tools/extract_template.sh',
            '/tools/update_mo.pl',
            '/vendor/mpdf/qrcode/tests/reference/LOREM-IPSUM-2019-L.html',
            '/vendor/tecnickcom/tcpdf/examples/images/img.png',
            '/yarn.lock',
        ];

        // only allowed PHP scripts are served
        $served_plugins_paths = $allowed_plugins_php_paths;
        // any static file and disallowed PHP scripts are ignored
        $ignored_glpi_paths = array_merge(
            $allowed_plugins_static_paths,
            $disallowed_plugins_php_paths,
            $disallowed_plugins_static_paths
        );

        foreach ($served_plugins_paths as $path) {
            yield '/plugins/myplugin' . $path => [
                'path'      => '/plugins/myplugin' . $path,
                'is_served' => true,
            ];
            yield '/plugins/myplugin' . '/' . $path => [
                'path'      => '/plugins/myplugin' . '/' . $path, // extra leading slash should not change result
                'is_served' => true,
            ];
        }
        foreach ($ignored_glpi_paths as $path) {
            yield '/plugins/myplugin' . $path => [
                'path'      => '/plugins/myplugin' . $path,
                'is_served' => false,
            ];
            yield '/plugins/myplugin' . '/' . $path => [
                'path'      => '/plugins/myplugin' . '/' . $path, // extra leading slash should not change result
                'is_served' => false,
            ];
        }
    }

    /**
     * @dataProvider pathProvider
     */
    public function testRunLegacyRouterFirewall(string $path, bool $is_served): void
    {
        $structure = null;
        foreach (array_reverse(explode('/', $path)) as $name) {
            if ($name === '') {
                continue;
            }
            if ($structure === null) {
                // first element, correspond to the file
                $structure = [$name => '<?php //...'];
            } else {
                // include sub elements in the current element
                $structure = [$name => $structure];
            }
        }

        vfsStream::setup('glpi', null, $structure);

        $this->newTestedInstance(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $path);

        $response = $this->callPrivateMethod($this->testedInstance, 'runLegacyRouter', $request);

        $this->variable($response)->isNull();
        if ($is_served === false) {
            $this->variable($request->attributes->get('_controller'))->isNull();
            $this->variable($request->attributes->get('_glpi_file_to_load'))->isNull();
        } else {
            $this->string($request->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
            $this->string($request->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi') . preg_replace('/\/{2,}/', '/', $path));
        }
    }

    public function testRunLegacyRouterFromDeprecatedMarketplacePath(): void
    {
        $structure = [
            'marketplace' => [
                'myplugin' => [
                    'front' => [
                        'test.php' => '<?php echo("/marketplace/myplugin/front/test.php");',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $this->newTestedInstance(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', '/marketplace/myplugin/front/test.php');

        $this->when(
            function () use ($request) {
                $this->callPrivateMethod($this->testedInstance, 'runLegacyRouter', $request);
            }
        )->error
            ->withMessage('Accessing the plugins resources from the `/marketplace/` path is deprecated. Use the `/plugins/` path instead.')
            ->withType(E_USER_DEPRECATED)
            ->exists();

        $this->string($request->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
        $this->string($request->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi/marketplace/myplugin/front/test.php'));
    }

    public function testRunLegacyRouterFromPluginInMultipleDirectories(): void
    {
        $structure = [
            'marketplace' => [
                'myplugin' => [
                    'front' => [
                        'test.php' => '<?php echo("/marketplace/myplugin/front/test.php");',
                    ],
                ],
            ],
            'plugins' => [
                'myplugin' => [
                    'front' => [
                        'test.php' => '<?php echo("/marketplace/myplugin/front/test.php");',
                    ],
                ],
            ],
        ];

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', '/plugins/myplugin/front/test.php');

        vfsStream::setup('glpi', null, $structure);

        // Plugin inside `/marketplace` should be served when `/marketplace` is dir is declared first
        $this->newTestedInstance(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $this->callPrivateMethod($this->testedInstance, 'runLegacyRouter', $request);

        $this->string($request->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
        $this->string($request->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi/marketplace/myplugin/front/test.php'));

        // Plugin inside `/plugins` should be served when `/plugins` is dir is declared first
        $this->newTestedInstance(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/plugins'), vfsStream::url('glpi/marketplace')]
        );

        $this->callPrivateMethod($this->testedInstance, 'runLegacyRouter', $request);

        $this->string($request->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
        $this->string($request->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi/plugins/myplugin/front/test.php'));
    }
}
