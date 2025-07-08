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

namespace tests\units\Glpi\Csv;

use Computer;
use Glpi\Csv\LogCsvExport as Core_LogCsvExport;

class LogCsvExportTest extends \CsvTestCase
{
    protected function getTestData(): array
    {
        $date = date('Y_m_d', time());

        $computer = new Computer();
        $id = $computer->add([
            'name'        => 'testExportToCsv 1',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        $this->assertGreaterThan(0, $id);
        $this->assertTrue($computer->getFromDB($id));

        // Multiple updates
        $this->assertTrue(
            $computer->update([
                'id'   => $id,
                'name' => 'testExportToCsv 2',
            ])
        );

        $this->assertTrue(
            $computer->update([
                'id'   => $id,
                'name' => 'testExportToCsv 3',
            ])
        );

        $this->assertTrue(
            $computer->update([
                'id'   => $id,
                'name' => 'testExportToCsv 4',
            ])
        );

        $this->assertTrue(
            $computer->update([
                'id'   => $id,
                'name' => 'testExportToCsv 5',
            ])
        );

        $this->assertTrue($computer->getFromDb($id));

        // Data that will be constant for all tests
        $filename = "testexporttocsv-5_$date.csv";
        $cols     = 5;

        return [
            [
                // Case 1: no filter
                'export'   => new Core_LogCsvExport($computer, []),
                'expected' => [
                    'filename' => $filename,
                    'cols'     => $cols,
                    'rows'     => 5,
                ],
            ],[
                // Case 2: only creation
                'export'   => new Core_LogCsvExport(
                    $computer,
                    ['linked_actions' => [\Log::HISTORY_CREATE_ITEM]]
                ),
                'expected' => [
                    'filename' => $filename,
                    'cols'     => $cols,
                    'rows'     => 1,
                ],
            ],[
                // Case 3: only updates
                'export'   => new Core_LogCsvExport(
                    $computer,
                    ['linked_actions' => [0]]
                ),
                'expected' => [
                    'filename' => $filename,
                    'cols'     => $cols,
                    'rows'     => 4,
                ],
            ],
            [
                // Case 4: only updates on name
                'export'   => new Core_LogCsvExport(
                    $computer,
                    ['affected_fields' => ["id_search_option::1"]]
                ),
                'expected' => [
                    'filename' => $filename,
                    'cols'     => $cols,
                    'rows'     => 4,
                ],
            ],
        ];
    }
}
