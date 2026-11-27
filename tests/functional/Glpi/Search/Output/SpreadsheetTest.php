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

use Glpi\Search\Output\Csv;
use Glpi\Search\Output\Xlsx;
use Glpi\Tests\GLPITestCase;

class SpreadsheetTest extends GLPITestCase
{
    public function testFormatValue(): void
    {
        $date_format = $_SESSION['glpidate_format'] ?? null;
        $_SESSION['glpidate_format'] = 1; // dd-mm-yyyy
        try {
            $date_value = [
                'count'       => 1,
                0             => ['name' => '2026-03-15 10:20:00'],
                'displayname' => '2026-03-15 10:20',
            ];
            $datetime_col = ['searchopt' => ['datatype' => 'datetime']];

            $csv = new Csv();
            $worksheet = $csv->getSpreasheet()->getActiveSheet();
            $format_value = new \ReflectionMethod($csv, 'formatValue');

            // Date column of a search result is formatted with the user date format
            $format_value->invoke($csv, $worksheet, 1, 1, $date_value, $datetime_col);
            $this->assertSame('15-03-2026 10:20', $worksheet->getCell([1, 1])->getValue());

            // Array value without search option (e.g. statistics) uses its display name
            $format_value->invoke($csv, $worksheet, 2, 1, ['displayname' => 'tech']);
            $this->assertSame('tech', $worksheet->getCell([2, 1])->getValue());

            $xlsx = new Xlsx();
            $worksheet = $xlsx->getSpreasheet()->getActiveSheet();
            $format_value = new \ReflectionMethod($xlsx, 'formatValue');

            // Date column of a search result is exported as a native date in XLSX
            $format_value->invoke($xlsx, $worksheet, 1, 1, $date_value, $datetime_col);
            $this->assertIsFloat($worksheet->getCell([1, 1])->getValue());
            $this->assertSame('dd-mm-yyyy hh:mm', $worksheet->getStyle([1, 1])->getNumberFormat()->getFormatCode());

            $format_value->invoke($xlsx, $worksheet, 2, 1, ['displayname' => 'tech']);
            $this->assertSame('tech', $worksheet->getCell([2, 1])->getValue());
        } finally {
            $_SESSION['glpidate_format'] = $date_format;
        }
    }
}
