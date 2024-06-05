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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class LegacyAssetsListener extends \GLPITestCase
{
    protected function assetsProvider(): iterable
    {
        $structure = [
            'index.php' => '<php echo(1);',
            'css' => [
                'test.css' => 'body { color:blue; }',
            ],
            'docs' => [
                'index.html' => '<html><head><title>Home</head><body>...</body>',
                'page.htm' => '<html><head><title>Page</head><body>...</body>',
            ],
            'js' => [
                'scripts.js' => 'console.log("ok");',
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
            'plugins' => [
                'myplugin' => [
                    'public' => [
                        'resources.json' => '["a","b","c"]',
                    ],
                ],
            ],
            'public' => [
                'fonts' => [
                    // see https://en.wikipedia.org/wiki/List_of_file_signatures
                    'myfont.eot'   => hex2bin('4F54544F'),
                    'myfont.otf'   => hex2bin('4F54544F'),
                    'myfont.woff'  => hex2bin('774F4646'),
                    'myfont.woff2' => hex2bin('774F4632'),
                ],
            ],
        ];

        vfsStream::setup('glpi', null, $structure);

        // HTML file
        yield [
            'path'  => '/docs/index.html',
            'body'  => $structure['docs']['index.html'],
            'type'  => 'text/html',
        ];
        yield [
            'path'  => '/docs/page.htm',
            'body'  => $structure['docs']['page.htm'],
            'type'  => 'text/html',
        ];

        // CSS file
        yield [
            'path'  => '/css/test.css',
            'body'  => $structure['css']['test.css'],
            'type'  => 'text/css',
        ];

        // JS file
        yield [
            'path'  => '/js/scripts.js',
            'body'  => $structure['js']['scripts.js'],
            'type'  => 'application/javascript',
        ];

        // GIF file
        yield [
            'path'  => '/media/Blank.gif',
            'body'  => $structure['media']['Blank.gif'],
            'type'  => 'image/gif',
        ];

        // JPG file
        yield [
            'path'  => '/media/Blank.jpeg',
            'body'  => $structure['media']['Blank.jpeg'],
            'type'  => 'image/jpeg',
        ];

        // PNG file
        yield [
            'path'  => '/media/Empty.png',
            'body'  => $structure['media']['Empty.png'],
            'type'  => 'image/png',
        ];

        // SVG file
        yield [
            'path'  => '/media/Sq_blank.svg',
            'body'  => $structure['media']['Sq_blank.svg'],
            'type'  => 'image/svg+xml',
        ];

        // JSON file
        yield [
            'path'  => '/plugins/myplugin/public/resources.json',
            'body'  => $structure['plugins']['myplugin']['public']['resources.json'],
            'type'  => 'application/json',
        ];

        // EOT/OTF file
        yield [
            'path'  => '/public/fonts/myfont.eot',
            'body'  => $structure['public']['fonts']['myfont.eot'],
            'type'  => 'application/vnd.ms-opentype',
        ];
        yield [
            'path'  => '/public/fonts/myfont.otf',
            'body'  => $structure['public']['fonts']['myfont.otf'],
            'type'  => 'application/vnd.ms-opentype',
        ];

        // WOFF file
        yield [
            'path'  => '/public/fonts/myfont.woff',
            'body'  => $structure['public']['fonts']['myfont.woff'],
            'type'  => 'font/woff',
        ];

        // WOFF2 file
        yield [
            'path'  => '/public/fonts/myfont.woff2',
            'body'  => $structure['public']['fonts']['myfont.woff2'],
            'type'  => 'font/woff2',
        ];
    }

    /**
     * @dataProvider assetsProvider
     */
    public function testServeLegacyAssetsResponse(string $path, ?string $body, string $type): void
    {
        $this->newTestedInstance(vfsStream::url('glpi'));

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $path);

        $response = $this->callPrivateMethod($this->testedInstance, 'serveLegacyAssets', $request);

        if ($body === null) {
            $this->variable($response)->isNull();
        } else {
            $this->object($response)->isInstanceOf(BinaryFileResponse::class);
            $file = $response->getFile();
            $this->object($response->getFile())->isInstanceOf(File::class);
            $this->string($file->getContent())->isEqualTo($body);
            $this->variable($response->headers->get('Content-Type'))->isEqualTo($type);
        }
    }

    protected function pathProvider(): iterable
    {
        $allowed_glpi_php_paths = [
            '/ajax/script.php',
            '/front/page.php',
            '/install/install.php',
            '/install/update.php',
            '/apirest.php',
            '/caldav.php',
            '/index.php',
            '/status.php',
        ];
        $disallowed_glpi_php_paths = [
            '/.atoum.php',
            '/ajax-script.php',
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
        // only allowed static files are served
        $served_glpi_paths = $allowed_glpi_static_paths;
        // any PHP scripts and disallowed static files are ignored
        $ignored_glpi_paths = array_merge(
            $allowed_glpi_php_paths,
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

        // only allowed static files are served
        $served_plugins_paths = $allowed_plugins_static_paths;
        // any PHP scripts and disallowed static files are ignored
        $ignored_glpi_paths = array_merge(
            $allowed_plugins_php_paths,
            $disallowed_plugins_php_paths,
            $disallowed_plugins_static_paths
        );

        foreach (['/marketplace/anyname', '/plugins/myplugin'] as $plugin_basepath) {
            foreach ($served_plugins_paths as $path) {
                yield $plugin_basepath . $path => [
                    'path'      => $plugin_basepath . $path,
                    'is_served' => true,
                ];
                yield $plugin_basepath . '/' . $path => [
                    'path'      => $plugin_basepath . '/' . $path, // extra leading slash should not change result
                    'is_served' => true,
                ];
            }
            foreach ($ignored_glpi_paths as $path) {
                yield $plugin_basepath . $path => [
                    'path'      => $plugin_basepath . $path,
                    'is_served' => false,
                ];
                yield $plugin_basepath . '/' . $path => [
                    'path'      => $plugin_basepath . '/' . $path, // extra leading slash should not change result
                    'is_served' => false,
                ];
            }
        }
    }

    /**
     * @dataProvider pathProvider
     */
    public function testServeLegacyAssetsFirewall(string $path, bool $is_served): void
    {
        $random = bin2hex(random_bytes(20));

        $structure = null;
        foreach (array_reverse(explode('/', $path)) as $name) {
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

        $this->newTestedInstance(vfsStream::url('glpi'));

        $request = new Request();
        $request->server->set('SCRIPT_NAME', '/index.php');
        $request->server->set('REQUEST_URI', $path);

        $response = $this->callPrivateMethod($this->testedInstance, 'serveLegacyAssets', $request);
        if ($is_served === false) {
            $this->variable($response)->isNull();
        } else {
            $this->object($response)->isInstanceOf(BinaryFileResponse::class);
            $file = $response->getFile();
            $this->object($response->getFile())->isInstanceOf(File::class);
            $this->string($file->getContent())->isEqualTo($random);
        }
    }
}
