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

class ProxyRouter extends \GLPITestCase
{
    protected function targetProvider(): iterable
    {
        vfsStream::setup(
            'glpi',
            null,
            [
                'ajax'  => [
                    'thereisnoindex.php' => '<php echo(1);',
                ],
                'front' => [
                    'index.php' => '<php echo(1);',
                ],
                'js' => [
                    'common.js' => 'console.log("ok");',
                ],
                'marketplace' => [
                    'myplugin' => [
                        'some.dir' => [
                            'file.test.php' => '<?php //a PHP script in a dir that contains a dot',
                        ],
                    ],
                    'mystaleplugin' => [
                        'front' => [
                            'page.php5' => '<?php //a PHP5 script',
                        ],
                    ],
                    'mimehack' => [
                        'css' => [
                            'style.css' => '<?php //a PHP script hidden in a CSS file',
                        ],
                    ],
                ],
                'apirest.php' => '<php echo(1);',
                'caldav.php' => '<php echo(1);',
                'index.php' => '<php echo(1);',
            ]
        );

        // Path to an existing directory that does not have a `index.php` script.
        yield [
            'path'            => '/ajax',
            'target_path'     => '/ajax',
            'target_pathinfo' => null,
            'target_file'     => null,
            'is_php_script'   => false,
        ];
        yield [
            'path'            => '///ajax', // triple `/` in URL
            'target_path'     => '/ajax',
            'target_pathinfo' => null,
            'target_file'     => null,
            'is_php_script'   => false,
        ];

        // Path to an invalid PHP script.
        yield [
            'path'            => '/is/not/valid.php',
            'target_path'     => '/is/not/valid.php',
            'target_pathinfo' => null,
            'target_file'     => null,
            'is_php_script'   => true,
        ];

        // Path to a `index.php` script.
        yield [
            'path'            => '/front/index.php',
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'target_file'     => vfsStream::url('glpi/front/index.php'),
            'is_php_script'   => true,
        ];
        yield [
            'path'            => '//front/index.php', // double `/` in URL
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'target_file'     => vfsStream::url('glpi/front/index.php'),
            'is_php_script'   => true,
        ];

        // Path to an existing file, but containing an extra PathInfo
        yield [
            'path'            => '/apirest.php/',
            'target_path'     => '/apirest.php',
            'target_pathinfo' => '/',
            'target_file'     => vfsStream::url('glpi/apirest.php'),
            'is_php_script'   => true,
        ];
        yield [
            'path'            => '/apirest.php/initSession/',
            'target_path'     => '/apirest.php',
            'target_pathinfo' => '/initSession/',
            'target_file'     => vfsStream::url('glpi/apirest.php'),
            'is_php_script'   => true,
        ];
        yield [
            'path'            => '/apirest.php//initSession/', // double `/` in URL
            'target_path'     => '/apirest.php',
            'target_pathinfo' => '/initSession/',
            'target_file'     => vfsStream::url('glpi/apirest.php'),
            'is_php_script'   => true,
        ];
        yield [
            'path'            => '/apirest.php/GlpiPlugin%5CNamespace%5CUnexemple/',
            'target_path'     => '/apirest.php',
            'target_pathinfo' => '/GlpiPlugin\Namespace\Unexemple/',
            'target_file'     => vfsStream::url('glpi/apirest.php'),
            'is_php_script'   => true,
        ];
        yield [
            'path'            => '/caldav.php/calendars/users/J.DUPONT/calendar/',
            'target_path'     => '/caldav.php',
            'target_pathinfo' => '/calendars/users/J.DUPONT/calendar/',
            'target_file'     => vfsStream::url('glpi/caldav.php'),
            'is_php_script'   => true,
        ];

        // Path to an existing directory that have a `index.php` script.
        yield [
            'path'            => '/front',
            'target_path'     => '/front/index.php',
            'target_pathinfo' => null,
            'target_file'     => vfsStream::url('glpi/front/index.php'),
            'is_php_script'   => true,
        ];

        // Path to a JS file
        yield [
            'path'            => '/js/common.js',
            'target_path'     => '/js/common.js',
            'target_pathinfo' => null,
            'target_file'     => vfsStream::url('glpi/js/common.js'),
            'is_php_script'   => false,
        ];

        // Path to a PHP script in a directory that has a dot in its name.
        yield [
            'path'            => '/marketplace/myplugin/some.dir/file.test.php',
            'target_path'     => '/marketplace/myplugin/some.dir/file.test.php',
            'target_pathinfo' => null,
            'target_file'     => vfsStream::url('glpi/marketplace/myplugin/some.dir/file.test.php'),
            'is_php_script'   => true,
        ];
        yield [
            'path'            => '/marketplace/myplugin/some.dir/file.test.php/path/to/item',
            'target_path'     => '/marketplace/myplugin/some.dir/file.test.php',
            'target_pathinfo' => '/path/to/item',
            'target_file'     => vfsStream::url('glpi/marketplace/myplugin/some.dir/file.test.php'),
            'is_php_script'   => true,
        ];

        // Path to a `.php5` script.
        yield [
            'path'            => '/marketplace/mystaleplugin/front/page.php5',
            'target_path'     => '/marketplace/mystaleplugin/front/page.php5',
            'target_pathinfo' => null,
            'target_file'     => vfsStream::url('glpi/marketplace/mystaleplugin/front/page.php5'),
            'is_php_script'   => true,
        ];

        // Path to a PHP script hidden in a CSS file
        yield [
            'path'            => '/marketplace/mimehack/css/style.css',
            'target_path'     => '/marketplace/mimehack/css/style.css',
            'target_pathinfo' => null,
            'target_file'     => vfsStream::url('glpi/marketplace/mimehack/css/style.css'),
            'is_php_script'   => false,
        ];
    }

    /**
     * @dataProvider targetProvider
     */
    public function testTarget(
        string $path,
        string $target_path,
        ?string $target_pathinfo,
        ?string $target_file,
        bool $is_php_script
    ) {
        $this->newTestedInstance(vfsStream::url('glpi'), $path);
        $this->string($this->testedInstance->getTargetPath())->isEqualTo($target_path, $path);
        $this->variable($this->testedInstance->getTargetPathInfo())->isEqualTo($target_pathinfo, $path);
        $this->variable($this->testedInstance->getTargetFile())->isEqualTo($target_file, $path);
        $this->boolean($this->testedInstance->isTargetAPhpScript())->isEqualTo($is_php_script, $path);
    }

    protected function pathProvider(): iterable
    {
        $allowed_glpi_php_paths = [
            '/ajax/script.php',
            '/front/page.php',
            '/install/install.php',
            '/install/update.php',
            '/apirest.php',
            '/apixmlrpc.php',
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
        foreach (array_merge($allowed_glpi_php_paths, $allowed_glpi_static_paths) as $path) {
            yield [
                'path'            => $path,
                'is_path_allowed' => true,
            ];
            yield [
                'path'            => '/' . $path, // extra leading slash should not change result
                'is_path_allowed' => true,
            ];
        }
        foreach (array_merge($disallowed_glpi_php_paths, $disallowed_glpi_static_paths) as $path) {
            yield [
                'path'            => $path,
                'is_path_allowed' => false,
            ];
            yield [
                'path'            => '/' . $path, // extra leading slash should not change result
                'is_path_allowed' => false,
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
        foreach (['/marketplace/anyname', '/plugins/myplugin'] as $plugin_basepath) {
            foreach (array_merge($allowed_plugins_php_paths, $allowed_plugins_static_paths) as $path) {
                yield [
                    'path'            => $plugin_basepath . $path,
                    'is_path_allowed' => true,
                ];
                yield [
                    'path'            => $plugin_basepath . '/' . $path, // extra leading slash should not change result
                    'is_path_allowed' => true,
                ];
            }
            foreach (array_merge($disallowed_plugins_php_paths, $disallowed_plugins_static_paths) as $path) {
                yield [
                    'path'            => $plugin_basepath . $path,
                    'is_path_allowed' => false,
                ];
                yield [
                    'path'            => $plugin_basepath . '/' . $path, // extra leading slash should not change result
                    'is_path_allowed' => false,
                ];
            }
        }
    }

    /**
     * @dataProvider pathProvider
     */
    public function testIsPathAllowed(
        string $path,
        bool $is_path_allowed
    ) {
        vfsStream::setup('glpi', null, []);

        $this->newTestedInstance(vfsStream::url('glpi'), $path);
        $this->boolean($this->testedInstance->isPathAllowed())->isEqualTo($is_path_allowed, $path);
    }

    protected function proxifyProvider(): iterable
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

        foreach ($this->pathProvider() as $path_specs) {
            $path = $path_specs['path'];
            $is_path_allowed = $path_specs['is_path_allowed'];
            if ($is_path_allowed === false) {
                // Unauthorized path should result in 403 error
                yield [
                    'path'      => $path,
                    'http_code' => 403,
                    'body'      => '',
                ];
            } elseif (file_exists(vfsStream::url('glpi' . $path)) === false) {
                // Non existing files should result in 404 error
                yield [
                    'path'      => $path,
                    'http_code' => 404,
                    'body'      => '',
                ];
            } elseif (preg_match('/\.php$/', $path) === 1) {
                // PHP scripts should not be proxified
                yield [
                    'path'      => $path,
                    'http_code' => 500,
                    'body'      => '',
                ];
            }
        }

        // HTML file
        yield [
            'path'      => '/docs/index.html',
            'http_code' => 200,
            'body'      => $structure['docs']['index.html'],
            'headers'   => [
                'Etag: b745c5b25a44dff8d077fdfa738c1db7',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: text/html',
                'Content-Length: 46'
            ],
        ];
        yield [
            'path'      => '/docs/page.htm',
            'http_code' => 200,
            'body'      => $structure['docs']['page.htm'],
            'headers'   => [
                'Etag: bca9de082cdae3763da4de1e6503279b',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: text/html',
                'Content-Length: 46'
            ],
        ];

        // CSS file
        yield [
            'path'      => '/css/test.css',
            'http_code' => 200,
            'body'      => $structure['css']['test.css'],
            'headers'   => [
                'Etag: 3b6bc56f81f3a3f94e38e1bb2ac392a2',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: text/css',
                'Content-Length: 20'
            ],
        ];

        // JS file
        yield [
            'path'      => '/js/scripts.js',
            'http_code' => 200,
            'body'      => $structure['js']['scripts.js'],
            'headers'   => [
                'Etag: 3d7266fa7f019a62fdf08b68ff8279aa',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: application/javascript',
                'Content-Length: 18'
            ],
        ];

        // GIF file
        yield [
            'path'      => '/media/Blank.gif',
            'http_code' => 200,
            'body'      => $structure['media']['Blank.gif'],
            'headers'   => [
                'Etag: b4491705564909da7f9eaf749dbbfbb1',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: image/gif',
                'Content-Length: 43'
            ],
        ];

        // JPG file
        yield [
            'path'      => '/media/Blank.jpeg',
            'http_code' => 200,
            'body'      => $structure['media']['Blank.jpeg'],
            'headers'   => [
                'Etag: d68e763c825dc0e388929ae1b375ce18',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: image/jpeg',
                'Content-Length: 631'
            ],
        ];

        // PNG file
        yield [
            'path'      => '/media/Empty.png',
            'http_code' => 200,
            'body'      => $structure['media']['Empty.png'],
            'headers'   => [
                'Etag: c9477b1f1820f9acfb93eebb2e6679c2',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: image/png',
                'Content-Length: 142'
            ],
        ];

        // SVG file
        yield [
            'path'      => '/media/Sq_blank.svg',
            'http_code' => 200,
            'body'      => $structure['media']['Sq_blank.svg'],
            'headers'   => [
                'Etag: 634ea49fe1aac547655c289003d0e83b',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: image/svg+xml',
                'Content-Length: 162'
            ],
        ];

        // JSON file
        yield [
            'path'      => '/plugins/myplugin/public/resources.json',
            'http_code' => 200,
            'body'      => $structure['plugins']['myplugin']['public']['resources.json'],
            'headers'   => [
                'Etag: c29a5747d698b2f95cdfd5ed6502f19d',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: application/json',
                'Content-Length: 13'
            ],
        ];

        // EOT/OTF file
        yield [
            'path'      => '/public/fonts/myfont.eot',
            'http_code' => 200,
            'body'      => $structure['public']['fonts']['myfont.eot'],
            'headers'   => [
                'Etag: 5bebcd707b47130cf923e8c7519d11e6',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: application/vnd.ms-opentype',
                'Content-Length: 4'
            ],
        ];
        yield [
            'path'      => '/public/fonts/myfont.otf',
            'http_code' => 200,
            'body'      => $structure['public']['fonts']['myfont.otf'],
            'headers'   => [
                'Etag: 5bebcd707b47130cf923e8c7519d11e6',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: application/vnd.ms-opentype',
                'Content-Length: 4'
            ],
        ];

        // WOFF file
        yield [
            'path'      => '/public/fonts/myfont.woff',
            'http_code' => 200,
            'body'      => $structure['public']['fonts']['myfont.woff'],
            'headers'   => [
                'Etag: e8a117b651db0ef1acb30eb66459feb6',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: font/woff',
                'Content-Length: 4'
            ],
        ];

        // WOFF2 file
        yield [
            'path'      => '/public/fonts/myfont.woff2',
            'http_code' => 200,
            'body'      => $structure['public']['fonts']['myfont.woff2'],
            'headers'   => [
                'Etag: a27fb479f580fd2628de4df27ba45137',
                'Cache-Control: public, max-age=2592000, must-revalidate',
                'Content-type: font/woff2',
                'Content-Length: 4'
            ],
        ];
    }

    /**
     * @dataProvider proxifyProvider
     */
    public function testProxify(
        string $path,
        int $http_code,
        string $body,
        array $headers = []
    ) {
        $this->newTestedInstance(vfsStream::url('glpi'), $path);

        if ($http_code === 200) {
            // Force `filemtime` result to be able to have a stable test for `Last-Modified` result.
            $filemtime = time() - 3600;
            $this->function->filemtime = $filemtime;
            $headers[] = sprintf('Last-Modified: %s GMT', gmdate('D, d M Y H:i:s', $filemtime));
        }

        $added_headers = [];
        $this->function->header = function ($value) use (&$added_headers) {
            $added_headers[] = $value;
        };

        $this->output(
            function () {
                $this->testedInstance->proxify();
            }
        )->isEqualTo($body, $path);

        $this->integer(http_response_code())->isEqualTo($http_code, $path);

        $not_found_headers = array_diff($headers, $added_headers);
        $unexpected_headers = array_diff($added_headers, $headers);
        $this->array($not_found_headers)->isEmpty($path . ': ' . json_encode($unexpected_headers));
    }
}
