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

namespace tests\units\autoload;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

class MiscFunctionsTest extends DbTestCase
{
    public static function htmlescapeProvider(): iterable
    {
        yield [
            'input'  => '1 > 0 & 1 < 2',
            'output' => '1 &gt; 0 &amp; 1 &lt; 2',
        ];
        yield [
            'input'  => 'with quotes : "hello" and \'Good bye !\'',
            'output' => 'with quotes : &quot;hello&quot; and &#039;Good bye !&#039;',
        ];
        yield [
            'input'  => null,
            'output' => '',
        ];
        yield [
            'input'  => true,
            'output' => '1',
        ];
        yield [
            'input'  => false,
            'output' => '',
        ];
        yield [
            'input'  => 1,
            'output' => '1',
        ];
        yield [
            'input'  => 0,
            'output' => '0',
        ];
    }

    #[DataProvider('htmlescapeProvider')]
    public function testHtmlescape(mixed $input, string $output): void
    {
        $this->assertEquals($output, \htmlescape($input));
    }

    public function testHtmlescapeWithUnexpectedType(): void
    {
        $this->assertEquals('Array', \htmlescape(['an', 'array']));
        $this->hasPhpLogRecordThatContains(
            'Array to string conversion',
            LogLevel::WARNING
        );
    }
    public static function jsescapeProvider(): iterable
    {
        yield [
            'input'  => 'simple quote (\')',
            'output' => 'simple\u0020quote\u0020\u0028\u0027\u0029',
        ];
        yield [
            'input'  => 'double quote (")',
            'output' => 'double\u0020quote\u0020\u0028\u0022\u0029',
        ];
        yield [
            'input'  => 'backslash \\',
            'output' => 'backslash\u0020\\\\',
        ];
        yield [
            'input'  => null,
            'output' => '',
        ];
        yield [
            'input'  => true,
            'output' => '1',
        ];
        yield [
            'input'  => false,
            'output' => '',
        ];
        yield [
            'input'  => 1,
            'output' => '1',
        ];
        yield [
            'input'  => 0,
            'output' => '0',
        ];
    }

    #[DataProvider('jsescapeProvider')]
    public function testJsescape(mixed $input, string $output): void
    {
        $this->assertEquals($output, \jsescape($input));
    }

    public function testJsescapeWithUnexpectedType(): void
    {
        $this->assertEquals('Array', \jsescape(['an', 'array']));
        $this->hasPhpLogRecordThatContains(
            'Array to string conversion',
            LogLevel::WARNING
        );
    }
}
