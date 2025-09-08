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

namespace tests\units\Glpi\Kernel\Listener\RequestListener;

use Glpi\Controller\LegacyFileLoadController;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Kernel\Listener\RequestListener\LegacyRouterListener;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LegacyRouterListenerTest extends \GLPITestCase
{
    public static function fileProvider(): iterable
    {
        $structure = [
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
            'plugins' => [
                'tester' => [
                    'front' => [
                        'page.php5' => '<?php echo("/plugins/tester/front/page.php5");',
                        'test.php' => '<?php echo("/plugins/tester/front/test.php");',
                        'some.dir' => [
                            'file.test.php' => '<?php echo("/plugins/tester/front/some.dir/file.test.php");',
                        ],
                    ],
                    'public' => [
                        'css.php' => '<?php echo("/plugins/tester/public/css.php");',
                    ],
                ],
            ],
        ];

        // Path to an existing directory that does not have a `index.php` script.
        yield '/ajax' => [
            'structure'       => $structure,
            'path'            => '/ajax',
            'target_path'     => '/ajax',
            'target_pathinfo' => null,
            'included'        => false,
        ];

        // Path to an invalid PHP script.
        yield '/is/not/valid.php' => [
            'structure'       => $structure,
            'path'            => '/is/not/valid.php',
            'target_path'     => '/is/not/valid.php',
            'target_pathinfo' => null,
            'included'        => false,
        ];

        // Path to a `index.php` script.
        yield '/front/index.php' => [
            'structure'       => $structure,
            'path'            => '/front/index.php',
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];

        // Path to an existing file, but containing an extra PathInfo
        yield '/front/whatever.php/' => [
            'structure'       => $structure,
            'path'            => '/front/whatever.php/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/',
            'included'        => true,
        ];
        yield '/front/whatever.php/endpoint/' => [
            'structure'       => $structure,
            'path'            => '/front/whatever.php/endpoint/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/endpoint/',
            'included'        => true,
        ];
        yield '/front/whatever.php//endpoint/' => [
            'structure'       => $structure,
            'path'            => '/front/whatever.php//endpoint/', // double `/` in URL
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/endpoint/',
            'included'        => true,
        ];
        yield '/front/whatever.php/GlpiPlugin%5CNamespace%5CUnexemple/' => [
            'structure'       => $structure,
            'path'            => '/front/whatever.php/GlpiPlugin%5CNamespace%5CUnexemple/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/GlpiPlugin\Namespace\Unexemple/',
            'included'        => true,
        ];
        yield '/front/whatever.php/calendars/users/J.DUPONT/calendar/' => [
            'structure'       => $structure,
            'path'            => '/front/whatever.php/calendars/users/J.DUPONT/calendar/',
            'target_path'     => '/front/whatever.php',
            'target_pathinfo' => '/calendars/users/J.DUPONT/calendar/',
            'included'        => true,
        ];

        // Path to an existing directory that have a `index.php` script.
        yield '/front' => [
            'structure'       => $structure,
            'path'            => '/front',
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];

        // Path to a JS file
        yield '/js/common.js' => [
            'structure'       => $structure,
            'path'            => '/js/common.js',
            'target_path'     => '/js/common.js',
            'target_pathinfo' => null,
            'included'        => false,
        ];

        // Path to a PHP script in a directory that has a dot in its name.
        yield '/plugins/tester/front/some.dir/file.test.php' => [
            'structure'       => $structure,
            'path'            => '/plugins/tester/front/some.dir/file.test.php',
            'target_path'     => '/plugins/tester/front/some.dir/file.test.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];
        yield '/plugins/tester/front/some.dir/file.test.php/path/to/item' => [
            'structure'       => $structure,
            'path'            => '/plugins/tester/front/some.dir/file.test.php/path/to/item',
            'target_path'     => '/plugins/tester/front/some.dir/file.test.php',
            'target_pathinfo' => '/path/to/item',
            'included'        => true,
        ];

        // Path to a `.php5` script.
        yield '/plugins/tester/front/page.php5' => [
            'structure'       => $structure,
            'path'            => '/plugins/tester/front/page.php5',
            'target_path'     => '/plugins/tester/front/page.php5',
            'target_pathinfo' => null,
            'included'        => true,
        ];

        // Path to a PHP script located in the `/public` dir of a plugin
        yield '/plugins/tester/public/css.php' => [
            'structure'       => $structure,
            'path'            => '/plugins/tester/css.php',
            'target_path'     => '/plugins/tester/public/css.php',
            'target_pathinfo' => null,
            'included'        => true,
        ];
    }

    #[DataProvider('fileProvider')]
    public function testRunLegacyRouterResponse(
        array $structure,
        string $path,
        string $target_path,
        ?string $target_pathinfo,
        bool $included
    ): void {
        vfsStream::setup('glpi', null, $structure);

        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent($path);
        $instance->onKernelRequest($event);

        if ($included === false) {
            $this->assertNull($event->getRequest()->attributes->get('_controller'));
            $this->assertNull($event->getRequest()->attributes->get('_glpi_file_to_load'));
        } else {
            $this->assertEquals(LegacyFileLoadController::class, $event->getRequest()->attributes->get('_controller'));
            $this->assertEquals(vfsStream::url('glpi') . $target_path, $event->getRequest()->attributes->get('_glpi_file_to_load'));
        }
    }

    public static function pathProvider(): iterable
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
            '/locales/tester.pot',
            '/tester.xml',
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
            yield '/plugins/tester' . $file => [
                'url_path'  => '/plugins/tester' . $file,
                'file_path' => '/plugins/tester' . $file,
                'is_served' => true,
            ];
        }

        foreach ($plugins_protected_php_files as $file) {
            // plugin protected PHP files should NOT be served
            yield '/plugins/tester' . $file => [
                'url_path'  => '/plugins/tester' . $file,
                'file_path' => '/plugins/tester' . $file,
                'is_served' => false,
            ];

            // unless the file is inside the `/public`
            yield '/plugins/tester' . $file . ' (in /public)' => [
                'url_path'  => '/plugins/tester' . $file,
                'file_path' => '/plugins/tester/public' . $file,
                'is_served' => true,
            ];
        }

        $not_served_files = \array_merge(
            $plugins_static_files, // plugins static files should NOT be served by this listener
            $plugins_hidden_files // plugins hidden files should never be served
        );
        foreach ($not_served_files as $file) {
            // file should NOT be served
            yield '/plugins/tester' . $file => [
                'url_path'  => '/plugins/tester' . $file,
                'file_path' => '/plugins/tester' . $file,
                'is_served' => false,
            ];

            // even if the file is inside the `/public`
            yield '/plugins/tester' . $file . ' (in /public)' => [
                'url_path'  => '/plugins/tester' . $file,
                'file_path' => '/plugins/tester/public' . $file,
                'is_served' => false,
            ];
        }
    }

    #[DataProvider('pathProvider')]
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

        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent($url_path);
        $instance->onKernelRequest($event);

        if ($is_served === false) {
            $this->assertNull($event->getRequest()->attributes->get('_controller'));
            $this->assertNull($event->getRequest()->attributes->get('_glpi_file_to_load'));
        } else {
            $this->assertEquals(LegacyFileLoadController::class, $event->getRequest()->attributes->get('_controller'));
            $this->assertEquals(vfsStream::url('glpi') . $file_path, $event->getRequest()->attributes->get('_glpi_file_to_load'));
        }
    }

    public function testRunLegacyRouterFromMarketplaceDirWithPluginPath(): void
    {
        $structure = [
            'marketplace' => [
                'tester' => [
                    'front' => [
                        'test.php' => '<?php echo("/marketplace/tester/front/test.php");',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent('/plugins/tester/front/test.php');

        $instance->onKernelRequest($event);

        $this->assertEquals(LegacyFileLoadController::class, $event->getRequest()->attributes->get('_controller'));
        $this->assertEquals(vfsStream::url('glpi/marketplace/tester/front/test.php'), $event->getRequest()->attributes->get('_glpi_file_to_load'));
    }

    public function testRunLegacyRouterFromDeprecatedMarketplacePath(): void
    {
        $structure = [
            'marketplace' => [
                'tester' => [
                    'front' => [
                        'test.php' => '<?php echo("/marketplace/tester/front/test.php");',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent('/marketplace/tester/front/test.php');

        $instance->onKernelRequest($event);

        $this->assertEquals(LegacyFileLoadController::class, $event->getRequest()->attributes->get('_controller'));
        $this->assertEquals(vfsStream::url('glpi/marketplace/tester/front/test.php'), $event->getRequest()->attributes->get('_glpi_file_to_load'));
    }

    public function testRunLegacyRouterFromDeprecatedPublicPath(): void
    {
        $structure = [
            'plugins' => [
                'tester' => [
                    'public' => [
                        'test.php' => '<?php echo("/plugins/tester/public/test.php");',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent('/plugins/tester/public/test.php');

        $instance->onKernelRequest($event);

        $this->hasPhpLogRecordThatContains(
            'Plugins URLs containing the `/public` path are deprecated. You should remove the `/public` prefix from the URL.',
            LogLevel::INFO
        );

        $this->assertEquals(LegacyFileLoadController::class, $event->getRequest()->attributes->get('_controller'));
        $this->assertEquals(vfsStream::url('glpi/plugins/tester/public/test.php'), $event->getRequest()->attributes->get('_glpi_file_to_load'));
    }

    public function testRunLegacyRouterFromPluginInMultipleDirectories(): void
    {
        $structure = [
            'marketplace' => [
                'tester' => [
                    'front' => [
                        'test.php' => '<?php echo("/marketplace/tester/front/test.php");',
                    ],
                ],
            ],
            'plugins' => [
                'tester' => [
                    'front' => [
                        'test.php' => '<?php echo("/plugins/tester/front/test.php");',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        // Plugin inside `/marketplace` should be served when `/marketplace` is dir is declared first
        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent('/plugins/tester/front/test.php');
        $instance->onKernelRequest($event);

        $this->assertEquals(LegacyFileLoadController::class, $event->getRequest()->attributes->get('_controller'));
        $this->assertEquals(vfsStream::url('glpi/marketplace/tester/front/test.php'), $event->getRequest()->attributes->get('_glpi_file_to_load'));

        // Plugin inside `/plugins` should be served when `/plugins` is dir is declared first
        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/plugins'), vfsStream::url('glpi/marketplace')]
        );

        $event = $this->getRequestEvent('/plugins/tester/front/test.php');
        $instance->onKernelRequest($event);

        $this->assertEquals(LegacyFileLoadController::class, $event->getRequest()->attributes->get('_controller'));
        $this->assertEquals(vfsStream::url('glpi/plugins/tester/front/test.php'), $event->getRequest()->attributes->get('_glpi_file_to_load'));
    }

    public function testRunLegacyRouterFromUnloadedPlugin(): void
    {
        $structure = [
            'plugins' => [
                'notloadedplugin' => [
                    'front' => [
                        'test.php' => '<?php echo("/plugins/tester/front/test.php");',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $instance = new LegacyRouterListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = $this->getRequestEvent('/plugins/notloadedplugin/front/test.php');

        $this->expectExceptionObject(
            new NotFoundHttpException('Plugin `notloadedplugin` is not loaded.')
        );

        $instance->onKernelRequest($event);
    }

    private function getRequestEvent(string $requested_uri): RequestEvent
    {
        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $requested_uri);

        return new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
