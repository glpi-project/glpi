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

namespace functional\Glpi\Search\Output;

use Glpi\Search\Output\SpreadsheetValueBinder;
use Glpi\Tests\GLPITestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SpreadsheetValueBinderTest extends GLPITestCase
{
    public function testDataTypeForValue(): void
    {
        $binder = new SpreadsheetValueBinder();

        // Test that a formula is treated as a string
        $this->assertSame(
            DataType::TYPE_STRING,
            $binder::dataTypeForValue('=SUM(A1:A10)')
        );

        // Test that a regular string is still treated as a string
        $this->assertSame(
            DataType::TYPE_STRING,
            $binder::dataTypeForValue('Hello, World!')
        );

        // Test that a number is still treated as numeric
        $this->assertSame(
            DataType::TYPE_NUMERIC,
            $binder::dataTypeForValue(12345)
        );
    }
}
