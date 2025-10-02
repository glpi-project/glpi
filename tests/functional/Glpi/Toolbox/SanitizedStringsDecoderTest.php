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

namespace tests\units\Glpi\Toolbox;

use Glpi\Toolbox\SanitizedStringsDecoder;
use PHPUnit\Framework\Attributes\DataProvider;

class SanitizedStringsDecoderTest extends \GLPITestCase
{
    public static function sanitizedValueProvider(): iterable
    {
        yield [
            'value'  => 'not sanitized',
            'result' => 'not sanitized',
        ];

        // Data from GLPI 9.5- (autosanitized)
        // `&` was not encoded, `<` and `>` were encoded to `&lt;` and `&gt;`
        yield [
            'value'  => '&lt;p&gt;Test&lt;/p&gt;',
            'result' => '<p>Test</p>',
        ];
        yield [
            'value'  => 'A&B',
            'result' => 'A&B',
        ];
        yield [
            'value'  => '$a &gt; 150 && $a &lt; 25',
            'result' => '$a > 150 && $a < 25',
        ];

        // Data from GLPI 10.0.x (autosanitized)
        // `&`, `<` and `>` were encoded to `&#38;`, `&#60;` and `&#62;`
        yield [
            'value'  => '&#60;p&#62;Test&#60;/p&#62;',
            'result' => '<p>Test</p>',
        ];
        yield [
            'value'  => 'A&#38;B',
            'result' => 'A&B',
        ];
        yield [
            'value'  => '$a &#62; 150 &#38;&#38; $a &#60; 25',
            'result' => '$a > 150 && $a < 25',
        ];

        // Data from GLPI 11.0+ (not autosanitized)
        yield [
            'value'  => '<p>Test</p>',
            'result' => '<p>Test</p>',
        ];
        yield [
            'value'  => 'A&B',
            'result' => 'A&B',
        ];
        yield [
            'value'  => '$a > 150 && $a < 25',
            'result' => '$a > 150 && $a < 25',
        ];
    }

    #[DataProvider('sanitizedValueProvider')]
    public function testDecodeHtmlSpecialChars(string $value, string $result)
    {
        $decoder = new SanitizedStringsDecoder();
        $this->assertEquals($result, $decoder->decodeHtmlSpecialChars($value));

        // Calling method multiple times should not corrupt unsanitized value
        $this->assertEquals($result, $decoder->decodeHtmlSpecialChars($result));
    }

    public static function sanitizedCompletenameValueProvider(): iterable
    {
        yield [
            'value'  => 'root element',
            'result' => 'root element',
        ];

        // Data from GLPI 9.5- (autosanitized)
        // `&` was not encoded
        yield [
            'value'  => 'A&B > &foo',
            'result' => 'A&B > &foo',
        ];

        // Data from GLPI 10.0.x (autosanitized)
        // `&` was encoded to `&#38;`
        yield [
            'value'  => 'A&#38;B > &#38;foo',
            'result' => 'A&B > &foo',
        ];

        // Data from GLPI 11.0+ (not autosanitized)
        yield [
            'value'  => 'A&B > &foo',
            'result' => 'A&B > &foo',
        ];
    }

    #[DataProvider('sanitizedCompletenameValueProvider')]
    public function testDecodeHtmlSpecialCharsInCompletenames(string $value, string $result)
    {
        $decoder = new SanitizedStringsDecoder();
        $this->assertEquals($result, $decoder->decodeHtmlSpecialCharsInCompletename($value));

        // Calling method multiple times should not corrupt unsanitized value
        $this->assertEquals($result, $decoder->decodeHtmlSpecialCharsInCompletename($result));
    }
}
