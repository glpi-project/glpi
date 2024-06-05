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

class URL extends \GLPITestCase
{
    protected function urlProvider(): iterable
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

    /**
     * @dataProvider urlProvider
     */
    public function testSanitizeURL(?string $url, string $expected): void
    {
        $this->newTestedInstance();
        $this->string($this->testedInstance->sanitizeURL($url))->isEqualTo($expected);
    }
}
