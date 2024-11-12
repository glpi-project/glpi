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
            'input'  => null,
            'output' => '',
        ];
        yield [
            'input'  => true,
            'output' => '1',
        ];
        yield [
            'input'  => 1,
            'output' => '1',
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
}
