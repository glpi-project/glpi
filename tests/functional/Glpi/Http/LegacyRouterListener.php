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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

        $event = $this->getRequestEvent($path);
        $this->testedInstance->onKernelRequest($event);

        if ($included === false) {
            $this->variable($event->getRequest()->attributes->get('_controller'))->isNull();
            $this->variable($event->getRequest()->attributes->get('_glpi_file_to_load'))->isNull();
        } else {
            $this->string($event->getRequest()->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
            $this->string($event->getRequest()->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi') . $target_path);
        }
    }

    protected function pathProvider(): iterable
    {
        $glpi_exposed_php_files = [
            '/ajax/script.php',
            '/front/page.php',
            '/install/install.php',
            '/install/update.php',
        ];
        $glpi_protected_php_files = [
            '/ajax-script.php',
            '/apirest.php',
            '/caldav.php',
            '/config/config_db.php',
            '/files/PHP/1c/db5d8ce30068f6259d1b926165619386c58f1e.PHP',
            '/front-page.php',
            '/inc/includes.php',
            '/install/index.php',
            '/install/install.old.php',
            '/install/migrations/update_9.5.x_to_10.0.0.php',
            '/node_modules/flatted/php/flatted.php',
            '/src/Computer.php',
            '/status.php',
            '/tests/bootstrap.php',
            '/tools/psr4.php',
            '/vendor/htmlawed/htmlawed/htmLawed.php',
        ];
        $glpi_static_files = [
            '/config/glpicrypt.key',
            '/css/lib/fontsource/inter/files/inter-latin-100-normal.woff2',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.eot',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.ttf',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.woff',
            '/css/lib/xxx/style.css',
            '/css/lib/xxx/webfont.otf',
            '/css/palettes/auror.scss',
            '/files/_log/php-errors.log',
            '/install/mysql/glpi-empty.sql',
            '/js/common.js',
            '/locales/en_GB.po',
            '/locales/en_GB.mo',
            '/locales/glpi.pot',
            '/node_modules/rrule/dist/esm/demo/demo.js',
            '/pics/20/schema.svg',
            '/pics/charts/sources.md',
            '/pics/expand.gif',
            '/pics/image.jpeg',
            '/pics/icones/ai-dist.png',
            '/resources/Rules/RuleAsset.xml',
            '/sound/sound_a.mp3',
            '/sound/sound_a.ogg',
            '/sound/sound_a.wav',
            '/templates/pages/login.html.twig',
            '/tests/fixtures/uploads/bar.png',
            '/tests/run_tests.sh',
            '/tools/psr12.sh',
            '/videos/demo.mp4',
            '/videos/demo.ogm',
            '/videos/demo.ogv',
            '/videos/demo.webm',
        ];
        $glpi_hidden_files = [
            '/.atoum.php',
            '/.htaccess',
            '/.tx/config',
            '/front/.hidden.php',
            '/install/mysql/.htaccess',
            '/node_modules/.bin/webpack-cli',
        ];

        foreach ($glpi_exposed_php_files as $file) {
            // exposed GLPI PHP files should be served
            yield $file => [
                'url_path'  => $file,
                'file_path' => $file,
                'is_served' => true,
            ];

            // extra leading slash should not change result
            yield '/' . $file => [
                'url_path'  => '/' . $file,
                'file_path' => $file,
                'is_served' => true,
            ];
        }

        foreach ($glpi_protected_php_files as $file) {
            // protected GLPI PHP files should NOT be served
            yield $file => [
                'url_path'  => $file,
                'file_path' => $file,
                'is_served' => false,
            ];

            // but any file inside the `/public` dir should be served
            yield $file . ' (in /public)' => [
                'url_path'  => $file,
                'file_path' => '/public' . $file,
                'is_served' => true,
            ];

            // extra leading slash should not change result
            yield '/' . $file => [
                'url_path'  => '/' . $file,
                'file_path' => $file,
                'is_served' => false,
            ];
            yield '/' . $file . ' (in /public)' => [
                'url_path'  => '/' . $file,
                'file_path' => '/public' . $file,
                'is_served' => true,
            ];
        }

        $not_served_files = \array_merge(
            $glpi_static_files, // GLPI static files should NOT be served by this listener
            $glpi_hidden_files // GLPI hidden files should never be served
        );
        foreach ($not_served_files as $file) {
            // file should NOT be served
            yield $file => [
                'url_path'  => $file,
                'file_path' => $file,
                'is_served' => false,
            ];

            // even if the file is inside the `/public`
            yield $file . ' (in /public)' => [
                'url_path'  => $file,
                'file_path' => '/public' . $file,
                'is_served' => false,
            ];

            // extra leading slash should not change result
            yield '/' . $file => [
                'url_path'  => '/' . $file,
                'file_path' => $file,
                'is_served' => false,
            ];
            yield '/' . $file . ' (in /public)' => [
                'url_path'  => '/' . $file,
                'file_path' => '/public' . $file,
                'is_served' => false,
            ];
        }

        $plugins_exposed_php_files = [
            '/ajax/script.php',
            '/ajax/subdir/messages.php',
            '/front/page.php',
            '/front/subdir/form.php',
            '/report/mycustomreport.php',
            '/report/metrics/metrics.php',
        ];
        $plugins_protected_php_files = [
            '/api.php',
            '/css/styles.css.php',
            '/css/palette/test.css.php',
            '/hook.php',
            '/inc/config.class.php',
            '/index.php',
            '/install/install.php',
            '/install/update_2.5_3.0.php',
            '/js/scripts.js.php',
            '/lib/php-saml/settings_example.php',
            '/path/to/any/index.php',
            '/path/to/any/file.css.php',
            '/path/to/any/file.js.php',
            '/root_script.php',
            '/scripts/cli_install.php',
            '/setup.php',
            '/tools/dump.php',
            '/vendor/autoload.php',
            '/vendor/fpdf/fpdf/src/Fpdf/Fpdf.php',
        ];
        $plugins_static_files = [
            '/changelog.txt',
            '/css/base.css',
            '/css/dev.css.map',
            '/css/lib/fontsource/inter/files/inter-latin-100-normal.woff2',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.eot',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.ttf',
            '/css/lib/tabler/icons-webfont/fonts/tabler-icons.woff',
            '/css/lib/xxx/webfont.otf',
            '/css/styles.scss',
            '/docker-compose.yml',
            '/documentation/index.htm',
            '/files/templates/holidays_template.txt',
            '/js/common.js',
            '/locales/en_GB.po',
            '/locales/en_GB.mo',
            '/locales/myplugin.pot',
            '/myplugin.xml',
            '/node_modules/rrule/dist/esm/demo/demo.js',
            '/package.json',
            '/package-lock.json',
            '/patch/fix-pear-image-barcode.patch',
            '/pics/expand.gif',
            '/pics/image.jpeg',
            '/pics/icones/ai-dist.png',
            '/pics/20/schema.svg',
            '/readme/api/v1.html',
            '/README.md',
            '/sound/sound_a.mp3',
            '/sound/sound_a.ogg',
            '/sound/sound_a.wav',
            '/sql/update-1.2.0.sql',
            '/templates/config.html.twig',
            '/templates/type.class.tpl',
            '/tools/extract_template.sh',
            '/tools/update_mo.pl',
            '/vendor/mpdf/qrcode/tests/reference/LOREM-IPSUM-2019-L.html',
            '/vendor/tecnickcom/tcpdf/examples/images/img.png',
            '/videos/demo.mp4',
            '/videos/demo.ogm',
            '/videos/demo.ogv',
            '/videos/demo.webm',
            '/yarn.lock',
        ];

        $plugins_hidden_files = [
            '/.atoum.php',
            '/.gitignore',
            '/.htaccess',
            '/.tx/config',
            '/ajax/.hidden.php',
            '/front/.hidden.php',
            '/node_modules/.bin/webpack-cli',
            '/templates/.htaccess',
        ];

        foreach ($plugins_exposed_php_files as $file) {
            // plugin exposed PHP files should be served
            yield '/plugins/myplugin' . $file => [
                'url_path'  => '/plugins/myplugin' . $file,
                'file_path' => '/plugins/myplugin' . $file,
                'is_served' => true,
            ];

            // extra leading slash should not change result
            yield '/plugins/myplugin' . '/' . $file => [
                'url_path'  => '/plugins/myplugin' . '/' . $file,
                'file_path' => '/plugins/myplugin' . $file,
                'is_served' => true,
            ];
        }

        foreach ($plugins_protected_php_files as $file) {
            // plugin protected PHP files should NOT be served
            yield '/plugins/myplugin' . $file => [
                'url_path'  => '/plugins/myplugin' . $file,
                'file_path' => '/plugins/myplugin' . $file,
                'is_served' => false,
            ];

            // unless the file is inside the `/public`
            yield '/plugins/myplugin' . $file . ' (in /public)' => [
                'url_path'  => '/plugins/myplugin' . $file,
                'file_path' => '/plugins/myplugin/public' . $file,
                'is_served' => true,
            ];

            // extra leading slash should not change result
            yield '/plugins/myplugin' . '/' . $file => [
                'url_path'  => '/plugins/myplugin' . '/' . $file,
                'file_path' => '/plugins/myplugin' . $file,
                'is_served' => false,
            ];
            yield '/plugins/myplugin' . '/' . $file . ' (in /public)' => [
                'url_path'  => '/plugins/myplugin' . '/' . $file,
                'file_path' => '/plugins/myplugin/public' . $file,
                'is_served' => true,
            ];
        }

        $not_served_files = \array_merge(
            $plugins_static_files, // plugins static files should NOT be served by this listener
            $plugins_hidden_files // plugins hidden files should never be served
        );
        foreach ($not_served_files as $file) {
            // file should NOT be served
            yield '/plugins/myplugin' . $file => [
                'url_path'  => '/plugins/myplugin' . $file,
                'file_path' => '/plugins/myplugin' . $file,
                'is_served' => false,
            ];

            // even if the file is inside the `/public`
            yield '/plugins/myplugin' . $file . ' (in /public)' => [
                'url_path'  => '/plugins/myplugin' . $file,
                'file_path' => '/plugins/myplugin/public' . $file,
                'is_served' => false,
            ];

            // extra leading slash should not change result
            yield '/plugins/myplugin' . '/' . $file => [
                'url_path'  => '/plugins/myplugin' . '/' . $file,
                'file_path' => '/plugins/myplugin' . $file,
                'is_served' => false,
            ];
            yield '/plugins/myplugin' . '/' . $file . ' (in /public)' => [
                'url_path'  => '/plugins/myplugin' . '/' . $file,
                'file_path' => '/plugins/myplugin/public' . $file,
                'is_served' => false,
            ];
        }
    }

    /**
     * @dataProvider pathProvider
     */
    public function testRunLegacyRouterFirewall(string $url_path, string $file_path, bool $is_served): void
    {
        $structure = null;
        foreach (array_reverse(explode('/', $file_path)) as $name) {
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

        $event = $this->getRequestEvent($url_path);
        $this->testedInstance->onKernelRequest($event);

        if ($is_served === false) {
            $this->variable($event->getRequest()->attributes->get('_controller'))->isNull();
            $this->variable($event->getRequest()->attributes->get('_glpi_file_to_load'))->isNull();
        } else {
            $this->string($event->getRequest()->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
            $this->string($event->getRequest()->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi') . $file_path);
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

        $event = $this->getRequestEvent('/marketplace/myplugin/front/test.php');

        $this->when(
            function () use ($event) {
                $this->testedInstance->onKernelRequest($event);
            }
        )->error
            ->withMessage('Accessing the plugins resources from the `/marketplace/` path is deprecated. Use the `/plugins/` path instead.')
            ->withType(E_USER_DEPRECATED)
            ->exists();

        $this->string($event->getRequest()->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
        $this->string($event->getRequest()->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi/marketplace/myplugin/front/test.php'));
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

        vfsStream::setup('glpi', null, $structure);

        // Plugin inside `/marketplace` should be served when `/marketplace` is dir is declared first
        $this->newTestedInstance(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent('/plugins/myplugin/front/test.php');
        $this->testedInstance->onKernelRequest($event);

        $this->string($event->getRequest()->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
        $this->string($event->getRequest()->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi/marketplace/myplugin/front/test.php'));

        // Plugin inside `/plugins` should be served when `/plugins` is dir is declared first
        $this->newTestedInstance(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/plugins'), vfsStream::url('glpi/marketplace')]
        );

        $event = $this->getRequestEvent('/plugins/myplugin/front/test.php');
        $this->testedInstance->onKernelRequest($event);

        $this->string($event->getRequest()->attributes->get('_controller'))->isEqualTo(LegacyFileLoadController::class);
        $this->string($event->getRequest()->attributes->get('_glpi_file_to_load'))->isEqualTo(vfsStream::url('glpi/plugins/myplugin/front/test.php'));
    }

    private function getRequestEvent(string $requested_uri): RequestEvent
    {
        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $requested_uri);

        return new RequestEvent(
            $this->newMockInstance(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
