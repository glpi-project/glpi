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

namespace tests\units;

use DbTestCase;

class CommonTreeDropdownTest extends DbTestCase
{
    public static function completenameProvider(): iterable
    {
        yield [
            'raw'       => 'Root > Child 1 > Child 2', // "Root" > "Child 1" > "Child 2"
            'sanitized' => 'Root &#62; Child 1 &#62; Child 2',
        ];

        yield [
            // "Root">"Child 1">"Child 2" (imported from external application that does not surround the separator by spaces)
            'raw'       => 'Root>Child 1 > Child 2',
            'sanitized' => 'Root&#62;Child 1 &#62; Child 2',
        ];

        yield [
            'raw'       => 'Root > R&#38;D > Team 1', // "Root" > "R&D" > "Team 1"
            'sanitized' => 'Root &#62; R&#38;D &#62; Team 1',
        ];

        yield [
            'raw'       => null,
            'sanitized' => null,
        ];
    }

    /**
     * @dataProvider completenameProvider
     */
    public function testSanitizeSeparatorInCompletename(?string $raw, ?string $sanitized)
    {
        $this->assertSame($sanitized, \CommonTreeDropdown::sanitizeSeparatorInCompletename($raw));
    }

    /**
     * @dataProvider completenameProvider
     */
    public function testUnsanitizeSeparatorInCompletename(?string $raw, ?string $sanitized)
    {
        $this->assertSame($raw, \CommonTreeDropdown::unsanitizeSeparatorInCompletename($sanitized));
    }
}
