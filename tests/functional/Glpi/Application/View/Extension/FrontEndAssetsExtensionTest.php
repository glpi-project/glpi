<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Application\View\Extension;

use Glpi\Application\View\Extension\FrontEndAssetsExtension;
use Glpi\Tests\GLPITestCase;
use Glpi\Toolbox\FrontEnd;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;

class FrontEndAssetsExtensionTest extends GLPITestCase
{
    public static function jsPathProvider(): iterable
    {
        global $CFG_GLPI;

        $v_default = FrontEnd::getVersionCacheKey(GLPI_VERSION);
        $v_1_2_5   = FrontEnd::getVersionCacheKey('1.2.5');

        // Static CSS files
        $path_mapping = [
            // Core file, with minified file provided
            'js/scripts_1.js' => 'js/scripts_1.min.js',

            // Core file, NO minified file provided
            'js/scripts_2.js' => 'js/scripts_2.js',

            // Plugin file, with minified file provided
            'plugins/tester/js/plugin_scripts_1.js' => 'plugins/tester/js/plugin_scripts_1.min.js',
            'marketplace/tester/js/plugin_scripts_1.js' => 'marketplace/tester/js/plugin_scripts_1.min.js',

            // Plugin files, NO minified file provided
            'plugins/tester/js/plugin_scripts_2.js' => 'plugins/tester/js/plugin_scripts_2.js',
            'marketplace/tester/js/plugin_scripts_2.js' => 'marketplace/tester/js/plugin_scripts_2.js',

        ];
        foreach ($path_mapping as $source_path => $optimized_path) {
            // in debug mode -> get source file
            yield [
                'path'       => $source_path,
                'options'    => [],
                'debug_mode' => true,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $source_path,
                    str_contains($source_path, '?') ? '&' : '?',
                    $v_default
                ),
            ];
            yield [
                'path'       => $source_path,
                'options'    => ['version' => '1.2.5'],
                'debug_mode' => true,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $source_path,
                    str_contains($source_path, '?') ? '&' : '?',
                    $v_1_2_5
                ),
            ];

            // NOT in debug mode -> get minified file, if any
            yield [
                'path'       => $source_path,
                'options'    => [],
                'debug_mode' => false,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $optimized_path,
                    str_contains($optimized_path, '?') ? '&' : '?',
                    $v_default
                ),
            ];
            yield [
                'path'       => $source_path,
                'options'    => ['version' => '1.2.5'],
                'debug_mode' => false,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $optimized_path,
                    str_contains($optimized_path, '?') ? '&' : '?',
                    $v_1_2_5
                ),
            ];
        }
    }

    #[DataProvider('jsPathProvider')]
    public function testJsPath(string $path, array $options, bool $debug_mode, string $expected): void
    {
        $_SESSION['glpi_use_mode'] = $debug_mode ? \Session::DEBUG_MODE : \Session::NORMAL_MODE;

        // Only the GLPI structure can be mocked, plugins rely on fixtures.
        vfsStream::setup(
            'glpi',
            null,
            [
                'public' => [
                    'js' => [
                        'scripts_1.js'     => '/* Source JS file */',
                        'scripts_2.js'     => '/* Source JS file */',
                        'scripts_1.min.js' => '/* Minified JS file */',
                    ],
                ],
            ]
        );

        $ext = new FrontEndAssetsExtension(vfsStream::url('glpi'));
        $this->assertEquals($expected, $ext->jsPath($path, $options));
    }

    public static function cssPathProvider(): iterable
    {
        global $CFG_GLPI;

        $v_default = FrontEnd::getVersionCacheKey(GLPI_VERSION);
        $v_1_2_5   = FrontEnd::getVersionCacheKey('1.2.5');

        // Static CSS files
        $path_mapping = [
            // Core file, with minified file provided
            'css/styles_1.css' => 'css/styles_1.min.css',
            'css/styles_1.css?param=value&a=5' => 'css/styles_1.min.css?param=value&a=5',

            // Core file, NO minified file provided
            'css/styles_2.css' => 'css/styles_2.css',
            'css/styles_2.css?param=value' => 'css/styles_2.css?param=value',

            // Plugin file, with minified file provided
            'plugins/tester/css/static_1.css' => 'plugins/tester/css/static_1.min.css',
            'plugins/tester/css/static_1.css?foo=bar&bar=foo' => 'plugins/tester/css/static_1.min.css?foo=bar&bar=foo',
            'marketplace/tester/css/static_1.css' => 'marketplace/tester/css/static_1.min.css',
            'marketplace/tester/css/static_1.css?foo=bar&bar=foo' => 'marketplace/tester/css/static_1.min.css?foo=bar&bar=foo',

            // Plugin files, NO minified file provided
            'plugins/tester/css/static_2.css' => 'plugins/tester/css/static_2.css',
            'plugins/tester/css/static_2.css?p' => 'plugins/tester/css/static_2.css?p',
            'marketplace/tester/css/static_2.css' => 'marketplace/tester/css/static_2.css',
            'marketplace/tester/css/static_2.css?p' => 'marketplace/tester/css/static_2.css?p',
        ];
        foreach ($path_mapping as $source_path => $optimized_path) {
            // in debug mode -> get source file
            yield [
                'path'       => $source_path,
                'options'    => [],
                'debug_mode' => true,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $source_path,
                    str_contains($source_path, '?') ? '&' : '?',
                    $v_default
                ),
            ];
            yield [
                'path'       => $source_path,
                'options'    => ['version' => '1.2.5'],
                'debug_mode' => true,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $source_path,
                    str_contains($source_path, '?') ? '&' : '?',
                    $v_1_2_5
                ),
            ];

            // NOT in debug mode -> get minified file, if any
            yield [
                'path'       => $source_path,
                'options'    => [],
                'debug_mode' => false,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $optimized_path,
                    str_contains($optimized_path, '?') ? '&' : '?',
                    $v_default
                ),
            ];
            yield [
                'path'       => $source_path,
                'options'    => ['version' => '1.2.5'],
                'debug_mode' => false,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $optimized_path,
                    str_contains($optimized_path, '?') ? '&' : '?',
                    $v_1_2_5
                ),
            ];
        }

        // SCSS files
        $path_mapping = [
            // Core files, no params in URL
            'css/styles_1.scss' => 'css_compiled/css_styles_1.min.css',
            'css/styles_2.scss' => 'front/css.php?file=css/styles_2.scss',

            // Core files, with params in URL
            'css/styles_1.scss?param=value'     => 'css_compiled/css_styles_1.min.css?param=value',
            'css/styles_2.scss?param=value&a=5' => 'front/css.php?file=css/styles_2.scss&param=value&a=5',

            // Plugin files, NO minified file provided
            'plugins/tester/css/styles.scss' => 'front/css.php?file=plugins/tester/css/styles.scss',
            'plugins/tester/css/styles.scss?p' => 'front/css.php?file=plugins/tester/css/styles.scss&p',
            'plugins/tester/css/styles.scss?foo=bar&bar=foo' => 'front/css.php?file=plugins/tester/css/styles.scss&foo=bar&bar=foo',
        ];
        foreach ($path_mapping as $source_path => $optimized_path) {
            // in debug mode -> get source file
            yield [
                'path'       => $source_path,
                'options'    => [],
                'debug_mode' => true,
                'expected'   => sprintf(
                    '%s/front/css.php?file=%s&debug=1&v=%s',
                    $CFG_GLPI['root_doc'],
                    str_replace('?', '&', $source_path),
                    $v_default
                ),
            ];
            yield [
                'path'       => $source_path,
                'options'    => ['version' => '1.2.5'],
                'debug_mode' => true,
                'expected'   => sprintf(
                    '%s/front/css.php?file=%s&debug=1&v=%s',
                    $CFG_GLPI['root_doc'],
                    str_replace('?', '&', $source_path),
                    $v_1_2_5
                ),
            ];

            // NOT in debug mode -> get minified file, if any
            yield [
                'path'       => $source_path,
                'options'    => [],
                'debug_mode' => false,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $optimized_path,
                    str_contains($optimized_path, '?') ? '&' : '?',
                    $v_default
                ),
            ];
            yield [
                'path'       => $source_path,
                'options'    => ['version' => '1.2.5'],
                'debug_mode' => false,
                'expected'   => sprintf(
                    '%s/%s%sv=%s',
                    $CFG_GLPI['root_doc'],
                    $optimized_path,
                    str_contains($optimized_path, '?') ? '&' : '?',
                    $v_1_2_5
                ),
            ];
        }
    }

    #[DataProvider('cssPathProvider')]
    public function testCssPath(string $path, array $options, bool $debug_mode, string $expected): void
    {
        $_SESSION['glpi_use_mode'] = $debug_mode ? \Session::DEBUG_MODE : \Session::NORMAL_MODE;

        // Only the GLPI structure can be mocked, plugins rely on fixtures.
        vfsStream::setup(
            'glpi',
            null,
            [
                'css' => [
                    'styles_1.scss'    => '/* SCSS file to compile */',
                    'styles_2.scss'    => '/* SCSS file to compile */',
                ],
                'public' => [
                    'css' => [
                        'styles_1.css'     => '/* Source CSS file */',
                        'styles_1.min.css' => '/* Minified CSS file */',
                        'styles_2.css'     => '/* Source CSS file */',
                    ],
                    'css_compiled' => [
                        'css_styles_1.min.css' => '/* Compiled and minified SCSS */',
                    ],
                ],
            ]
        );

        $ext = new FrontEndAssetsExtension(vfsStream::url('glpi'));
        $this->assertEquals($expected, $ext->cssPath($path, $options));
    }
}
