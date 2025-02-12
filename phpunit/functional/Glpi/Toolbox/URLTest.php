<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class URLTest extends \GLPITestCase
{
    public static function urlProvider(): iterable
    {
        yield [
            'url'       => null,
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => '',
            'sanitized' => '',
            'relative'  => false,
        ];

        // Javascript URL
        yield [
            'url'       => 'javascript:alert(1);',
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => "java\nscript:alert(1);",
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => "j a v\t\ta\n  s c \t ript  :alert(1);",
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => 'jAvAscrIPt:alert(1);',
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => 'javascript:alert(1);" title="XSS!"',
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => 'javascript:alert(1)',
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => 'javascript://%0aalert();',
            'sanitized' => '',
            'relative'  => false,
        ];

        // Invalid URL
        yield [
            'url'       => 'ht tp://www.domain.tld/test',
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => 'http:/www.domain.tld/test',
            'sanitized' => '',
            'relative'  => false,
        ];
        yield [
            'url'       => '15//test',
            'sanitized' => '',
            'relative'  => false,
        ];

        // Sane URL
        yield [
            'url'       => 'http://www.domain.tld/test',
            'sanitized' => 'http://www.domain.tld/test',
            'relative'  => false,
        ];
        yield [
            'url'       => '//hostname/path/to/file',
            'sanitized' => '//hostname/path/to/file',
            'relative'  => false,
        ];
        yield [
            'url'       => '/test?abc=12',
            'sanitized' => '/test?abc=12',
            'relative'  => true,
        ];
        yield [
            'url'       => '/Path/To/Resource/15',
            'sanitized' => '/Path/To/Resource/15',
            'relative'  => true,
        ];
        yield [
            'url'       => '/',
            'sanitized' => '/',
            'relative'  => true,
        ];
        yield [
            'url'       => '/.hiddenfile',
            'sanitized' => '/.hiddenfile',
            'relative'  => false, // not considered as a valid relative URL, as it exposes an hidden resource
        ];
        yield [
            'url'       => '/front/.hidden.php',
            'sanitized' => '/front/.hidden.php',
            'relative'  => false, // not considered as a valid relative URL, as it exposes an hidden resource
        ];
        yield [
            'url'       => '/front/../../oustideglpi.php',
            'sanitized' => '/front/../../oustideglpi.php',
            'relative'  => false, // not considered as a valid relative URL, as it contains a `/..` token that may expose paths outside GLPI
        ];
    }

    /**
     * @dataProvider urlProvider
     */
    public function testSanitizeURL(?string $url, string $sanitized, bool $relative): void
    {
        $instance = new \Glpi\Toolbox\URL();
        $this->assertEquals($sanitized, $instance->sanitizeURL($url));
    }

    /**
     * @dataProvider urlProvider
     */
    public function testIsGLPIRelativeUrl(?string $url, string $sanitized, bool $relative): void
    {
        $instance = new \Glpi\Toolbox\URL();
        $this->assertEquals($relative, $instance->isGLPIRelativeUrl((string) $url));
    }
}
