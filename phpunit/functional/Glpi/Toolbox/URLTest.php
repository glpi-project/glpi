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

namespace tests\units\Glpi\Toolbox;

use Glpi\Form\Form;
use PHPUnit\Framework\Attributes\DataProvider;
use Ticket;

class URLTest extends \GLPITestCase
{
    public static function urlProvider(): iterable
    {
        yield [
            'url'      => null,
            'expected' => '',
        ];
        yield [
            'url'      => '',
            'expected' => '',
        ];

        // Javascript URL
        yield [
            'url'      => 'javascript:alert(1);',
            'expected' => '',
        ];
        yield [
            'url'      => "java\nscript:alert(1);",
            'expected' => '',
        ];
        yield [
            'url'      => "j a v\t\ta\n  s c \t ript  :alert(1);",
            'expected' => '',
        ];
        yield [
            'url'      => 'jAvAscrIPt:alert(1);',
            'expected' => '',
        ];
        yield [
            'url'      => 'javascript:alert(1);" title="XSS!"',
            'expected' => '',
        ];
        yield [
            'url'      => 'javascript:alert(1)',
            'expected' => '',
        ];
        yield [
            'url'      => 'javascript://%0aalert();',
            'expected' => '',
        ];

        // Invalid URL
        yield [
            'url'      => 'ht tp://www.domain.tld/test',
            'expected' => '',
        ];
        yield [
            'url'      => 'http:/www.domain.tld/test',
            'expected' => '',
        ];
        yield [
            'url'      => '15//test',
            'expected' => '',
        ];

        // Sane URL
        yield [
            'url'      => 'http://www.domain.tld/test',
            'expected' => 'http://www.domain.tld/test',
        ];
        yield [
            'url'      => '//hostname/path/to/file',
            'expected' => '//hostname/path/to/file',
        ];
        yield [
            'url'      => '/test?abc=12',
            'expected' => '/test?abc=12',
        ];
        yield [
            'url'      => '/',
            'expected' => '/',
        ];
    }

    #[DataProvider('urlProvider')]
    public function testSanitizeURL(?string $url, string $expected): void
    {
        $instance = new \Glpi\Toolbox\URL();
        $this->assertEquals($expected, $instance->sanitizeURL($url));
    }

    public static function extractItemtypeFromUrlPathProvider(): iterable
    {
        // Core
        yield 'Core class' => [
            'path' => '/front/ticket.php',
            'expected' => Ticket::class,
        ];
        yield 'Core class ".form" page' => [
            'path' => '/front/ticket.form.php',
            'expected' => Ticket::class,
        ];
        yield 'Namespaced core class' => [
            'path' => '/front/form/form.php',
            'expected' => Form::class,
        ];
        yield 'Namespaced core class ".form" page' => [
            'path' => '/front/form/form.form.php',
            'expected' => Form::class,
        ];

        // Plugins (/plugins)
        yield 'Plugin class (/plugins)' => [
            'path' => '/plugins/foo/front/bar.php',
            'expected' => \PluginFooBar::class,
        ];
        yield 'Plugin class ".form" page (/plugins)' => [
            'path' => '/plugins/foo/front/bar.form.php',
            'expected' => \PluginFooBar::class,
        ];
        yield 'Namespaced plugin class (/plugins)' => [
            'path' => '/plugins/foo/front/a/b/c/d/e/f/g/bar.php',
            'expected' => \GlpiPlugin\Foo\A\B\C\D\E\F\G\Bar::class,
        ];
        yield 'Namespaced plugin class ".form" page (/plugins)' => [
            'path' => '/plugins/foo/front/a/b/c/d/e/f/g/bar.form.php',
            'expected' => \GlpiPlugin\Foo\A\B\C\D\E\F\G\Bar::class,
        ];

        // Plugins (/marketplace)
        yield 'Plugin class (/marketplace)' => [
            'path' => '/marketplace/foo/front/bar.php',
            'expected' => \PluginFooBar::class,
        ];
        yield 'Plugin class ".form" page (/marketplace)' => [
            'path' => '/marketplace/foo/front/bar.form.php',
            'expected' => \PluginFooBar::class,
        ];
        yield 'Namespaced plugin class (/marketplace)' => [
            'path' => '/marketplace/foo/front/a/b/c/d/e/f/g/bar.php',
            'expected' => \GlpiPlugin\Foo\A\B\C\D\E\F\G\Bar::class,
        ];
        yield 'Namespaced plugin class ".form" page (/marketplace)' => [
            'path' => '/marketplace/foo/front/a/b/c/d/e/f/g/bar.form.php',
            'expected' => \GlpiPlugin\Foo\A\B\C\D\E\F\G\Bar::class,
        ];
    }

    #[DataProvider('extractItemtypeFromUrlPathProvider')]
    public function testExtractItemtypeFromUrlPath(
        string $path,
        string $expected
    ): void {
        // Functions like Session::setActiveTab() will use the lowercase version
        // of the itemtype, thus we must expect the lowercase version here.
        $expected = strtolower($expected);

        $instance = new \Glpi\Toolbox\URL();
        $itemtype = $instance->extractItemtypeFromUrlPath($path);
        $this->assertEquals($expected, $itemtype);
    }
}
