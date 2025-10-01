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

use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Kernel\Listener\RequestListener\FrontEndAssetsListener;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FrontEndAssetsListenerTest extends \GLPITestCase
{
    public static function assetsProvider(): iterable
    {
        $structure = [
            'index.php' => '<php echo(1);',
            'js' => [
                'scripts.js' => 'console.log("ok");',
            ],
            'plugins' => [
                'tester' => [
                    'public' => [
                        'resources.json' => '["a","b","c"]',
                    ],
                ],
            ],
            'public' => [
                'css' => [
                    'test.css' => 'body { color:blue; }',
                ],
                'docs' => [
                    'index.html' => '<html><head><title>Home</head><body>...</body>',
                    'page.htm' => '<html><head><title>Page</head><body>...</body>',
                ],
                'fonts' => [
                    // see https://en.wikipedia.org/wiki/List_of_file_signatures
                    'myfont.eot'   => hex2bin('4F54544F'),
                    'myfont.otf'   => hex2bin('4F54544F'),
                    'myfont.woff'  => hex2bin('774F4646'),
                    'myfont.woff2' => hex2bin('774F4632'),
                ],
                'media' => [
                    // https://commons.wikimedia.org/wiki/File:Blank.gif
                    'Blank.gif' => hex2bin(
                        '47494638396101000100800000ffffff00000021f90400000000002c00000000010001000002024401003b'
                    ),
                    // https://commons.wikimedia.org/wiki/File:Blank.JPG
                    'Blank.jpeg' => hex2bin(
                        'ffd8ffe000104a46494600010101006000600000ffdb004300080606070605080707070909080a0c140d0c0b0b0c191213'
                        . '0f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432ffdb004301090909'
                        . '0c0b0c180d0d1832211c2132323232323232323232323232323232323232323232323232323232323232323232323232'
                        . '32323232323232323232323232ffc00011080001000103012200021101031101ffc4001f000001050101010101010000'
                        . '0000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131'
                        . '410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a43'
                        . '4445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999a'
                        . 'a2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2'
                        . 'f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b5110002'
                        . '0102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f01562'
                        . '72d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a'
                        . '737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5'
                        . 'c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00f7fa'
                        . '28a2803fffd9'
                    ),
                    // https://commons.wikimedia.org/wiki/File:Empty.png
                    'Empty.png' => hex2bin(
                        '89504e470d0a1a0a0000000d49484452000002c00000018c0103000000ee93862600000003504c5445ffffffa7c41bc800'
                        . '00000174524e530040e6d8660000003949444154785eedc03101000000c220fba7b6c60e581800000000000000000000'
                        . '0000000000000000000000000000000000000000000000c00189ac000180fa9d590000000049454e44ae426082'
                    ),
                    // https://commons.wikimedia.org/wiki/File:Sq_blank.svg
                    'Sq_blank.svg' => '<?xml version="1.0"?>'
                        . '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">'
                        . '<path fill="none" stroke="#999" stroke-width="2" d="M1,1V199H199V1z"/>'
                        . '</svg>',
                ],
            ],
        ];

        // JS file (from the `/js` dir)
        yield '/js/scripts.js' => [
            'structure' => $structure,
            'path'      => '/js/scripts.js',
            'body'      => $structure['js']['scripts.js'],
            'type'      => 'application/javascript',
        ];

        // HTML file (inside the `/public` dir)
        yield '/docs/index.html' => [
            'structure' => $structure,
            'path'      => '/docs/index.html',
            'body'      => $structure['public']['docs']['index.html'],
            'type'      => 'text/html',
        ];
        yield '/docs/page.htm' => [
            'structure' => $structure,
            'path'      => '/docs/page.htm',
            'body'      => $structure['public']['docs']['page.htm'],
            'type'      => 'text/html',
        ];

        // CSS file (inside the `/public` dir)
        yield '/css/test.css' => [
            'structure' => $structure,
            'path'      => '/css/test.css',
            'body'      => $structure['public']['css']['test.css'],
            'type'      => 'text/css',
        ];

        // GIF file (inside the `/public` dir)
        yield '/media/Blank.gif' => [
            'structure' => $structure,
            'path'      => '/media/Blank.gif',
            'body'      => $structure['public']['media']['Blank.gif'],
            'type'      => 'image/gif',
        ];

        // JPG file (inside the `/public` dir)
        yield '/media/Blank.jpeg' => [
            'structure' => $structure,
            'path'      => '/media/Blank.jpeg',
            'body'      => $structure['public']['media']['Blank.jpeg'],
            'type'      => 'image/jpeg',
        ];

        // PNG file (inside the `/public` dir)
        yield '/media/Empty.png' => [
            'structure' => $structure,
            'path'      => '/media/Empty.png',
            'body'      => $structure['public']['media']['Empty.png'],
            'type'      => 'image/png',
        ];

        // SVG file (inside the `/public` dir)
        yield '/media/Sq_blank.svg' => [
            'structure' => $structure,
            'path'      => '/media/Sq_blank.svg',
            'body'      => $structure['public']['media']['Sq_blank.svg'],
            'type'      => 'image/svg+xml',
        ];

        // JSON file from a plugin located in `/plugins`
        yield '/plugins/tester/public/resources.json' => [
            'structure' => $structure,
            'path'      => '/plugins/tester/resources.json',
            'body'      => $structure['plugins']['tester']['public']['resources.json'],
            'type'      => 'application/json',
        ];

        // EOT/OTF file (inside the `/public` dir)
        yield '/public/fonts/myfont.eot' => [
            'structure' => $structure,
            'path'      => '/fonts/myfont.eot',
            'body'      => $structure['public']['fonts']['myfont.eot'],
            'type'      => 'application/vnd.ms-opentype',
        ];
        yield '/public/fonts/myfont.otf' => [
            'structure' => $structure,
            'path'      => '/fonts/myfont.otf',
            'body'      => $structure['public']['fonts']['myfont.otf'],
            'type'      => 'application/vnd.ms-opentype',
        ];

        // WOFF file (inside the `/public` dir)
        yield '/public/fonts/myfont.woff' => [
            'structure' => $structure,
            'path'      => '/fonts/myfont.woff',
            'body'      => $structure['public']['fonts']['myfont.woff'],
            'type'      => 'font/woff',
        ];

        // WOFF2 file (inside the `/public` dir)
        yield '/public/fonts/myfont.woff2' => [
            'structure' => $structure,
            'path'      => '/fonts/myfont.woff2',
            'body'      => $structure['public']['fonts']['myfont.woff2'],
            'type'      => 'font/woff2',
        ];
    }

    #[DataProvider('assetsProvider')]
    public function testServeLegacyAssetsResponse(array $structure, string $path, ?string $body, string $type): void
    {
        vfsStream::setup('glpi', null, $structure);

        $instance = new FrontEndAssetsListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $path);

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $instance->onKernelRequest($event);
        $response = $event->getResponse();

        if ($body === null) {
            $this->assertNull($response);
        } else {
            $this->assertInstanceOf(BinaryFileResponse::class, $response);
            $file = $response->getFile();
            $this->assertInstanceOf(File::class, $response->getFile());
            $this->assertEquals($body, $file->getContent());
            $this->assertEquals($type, $response->headers->get('Content-Type'));
        }
    }

    public static function pathProvider(): iterable
    {
        $glpi_php_files = [
            '/ajax/script.php',
            '/ajax-script.php',
            '/apirest.php',
            '/caldav.php',
            '/config/config_db.php',
            '/files/PHP/1c/db5d8ce30068f6259d1b926165619386c58f1e.PHP',
            '/front/page.php',
            '/front-page.php',
            '/inc/includes.php',
            '/index.php',
            '/install/index.php',
            '/install/install.php',
            '/install/install.old.php',
            '/install/migrations/update_9.5.x_to_10.0.0.php',
            '/install/update.php',
            '/node_modules/flatted/php/flatted.php',
            '/src/Computer.php',
            '/status.php',
            '/tests/bootstrap.php',
            '/tools/psr4.php',
            '/vendor/htmlawed/htmlawed/htmLawed.php',
        ];
        $glpi_exposed_static_files = [
            '/js/common.js',
        ];
        $glpi_protected_static_files = [
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
        foreach ($glpi_exposed_static_files as $file) {
            // exposed GLPI static files should be served
            yield $file => [
                'url_path'  => $file,
                'file_path' => $file,
                'is_served' => true,
            ];
        }

        foreach ($glpi_protected_static_files as $file) {
            // protected GLPI static files should NOT be served
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
            $glpi_php_files, // GLPI PHP files should NOT be served by this listener
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

        $plugins_php_files = [
            '/ajax/script.php',
            '/ajax/subdir/messages.php',
            '/css/styles.css.php',
            '/css/palette/test.css.php',
            '/front/page.php',
            '/front/subdir/form.php',
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
            '/report/mycustomreport.php',
            '/report/metrics/metrics.php',
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

        foreach ($plugins_static_files as $file) {
            // plugin static files should NOT be served
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
            $plugins_php_files, // plugins PHP files should NOT be served by this listener
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
    public function testServeLegacyAssetsFirewall(string $url_path, string $file_path, bool $is_served): void
    {
        $random = bin2hex(random_bytes(20));

        $structure = null;
        foreach (array_reverse(explode('/', $file_path)) as $name) {
            if ($name === '') {
                continue;
            }
            if ($structure === null) {
                // first element, correspond to the file
                $structure = [$name => $random];
            } else {
                // include sub elements in the current element
                $structure = [$name => $structure];
            }
        }

        vfsStream::setup('glpi', null, $structure);

        $instance = new FrontEndAssetsListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $url_path);

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $instance->onKernelRequest($event);
        $response = $event->getResponse();

        if ($is_served === false) {
            $this->assertNull($response);
        } else {
            $this->assertInstanceOf(BinaryFileResponse::class, $response);
            $file = $response->getFile();
            $this->assertInstanceOf(File::class, $response->getFile());
            $this->assertEquals($random, $file->getContent());
        }
    }

    public function testServeLegacyAssetsFromMarketplaceDirWithPluginPath(): void
    {
        $structure = [
            'marketplace' => [
                'tester' => [
                    'public' => [
                        'resources.json' => '["b","c","d"]',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $instance = new FrontEndAssetsListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', '/plugins/tester/resources.json');

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $instance->onKernelRequest($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $file = $response->getFile();
        $this->assertInstanceOf(File::class, $response->getFile());
        $this->assertEquals('["b","c","d"]', $file->getContent());
    }

    public function testServeLegacyAssetsFromDeprecatedMarketplacePath(): void
    {
        $structure = [
            'marketplace' => [
                'tester' => [
                    'public' => [
                        'resources.json' => '["b","c","d"]',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $instance = new FrontEndAssetsListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', '/marketplace/tester/resources.json');

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $instance->onKernelRequest($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $file = $response->getFile();
        $this->assertInstanceOf(File::class, $response->getFile());
        $this->assertEquals('["b","c","d"]', $file->getContent());
    }

    public function testServeLegacyAssetsFromPluginInMultipleDirectories(): void
    {
        $structure = [
            'marketplace' => [
                'tester' => [
                    'public' => [
                        'resources.json' => '["b","c","d"]',
                    ],
                ],
            ],
            'plugins' => [
                'tester' => [
                    'public' => [
                        'resources.json' => '[1, 2, 3]',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', '/plugins/tester/resources.json');

        // Plugin inside `/marketplace` should be served when `/marketplace` is dir is declared first
        $instance = new FrontEndAssetsListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $instance->onKernelRequest($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $file = $response->getFile();
        $this->assertInstanceOf(File::class, $response->getFile());
        $this->assertEquals('["b","c","d"]', $file->getContent());

        // Plugin inside `/plugins` should be served when `/plugins` is dir is declared first
        $instance = new FrontEndAssetsListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/plugins'), vfsStream::url('glpi/marketplace')]
        );

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $instance->onKernelRequest($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $file = $response->getFile();
        $this->assertInstanceOf(File::class, $response->getFile());
        $this->assertEquals('[1, 2, 3]', $file->getContent());
    }

    public function testServeLegacyAssetsFromUnloadedPlugin(): void
    {
        $structure = [
            'plugins' => [
                'notloadedplugin' => [
                    'public' => [
                        'resources.json' => '["b","c","d"]',
                    ],
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        $instance = new FrontEndAssetsListener(
            vfsStream::url('glpi'),
            [vfsStream::url('glpi/marketplace'), vfsStream::url('glpi/plugins')]
        );

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', '/plugins/notloadedplugin/resources.json');

        $this->expectExceptionObject(
            new NotFoundHttpException('Plugin `notloadedplugin` is not loaded.')
        );

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $instance->onKernelRequest($event);
    }
}
