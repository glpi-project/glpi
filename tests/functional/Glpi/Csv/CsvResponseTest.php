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

namespace tests\units\Glpi\Csv;

use Computer;
use Glpi\Csv\CsvResponse as Core_CsvResponse;
use Glpi\Csv\LogCsvExport as CsvLogCsvExport;
use Glpi\Tests\DbTestCase;

class CsvResponseTest extends DbTestCase
{
    public function testCsvResponse()
    {
        $_SESSION['glpicronuserrunning'] = "cron_phpunit";

        // Create a dummy computer
        $computer = new Computer();
        $id = $computer->add([
            'name'        => 'testExportToCsv 1',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        $this->assertGreaterThan(0, $id);
        $this->assertTrue($computer->getFromDB($id));

        // Output CSV
        ob_start();
        $mock_logexport = $this->getMockBuilder(CsvLogCsvExport::class)
            ->setConstructorArgs([$computer, []])
            ->onlyMethods(['getFileName'])
            ->getMock();
        $mock_logexport->method('getFileName')->willReturn(null);
        Core_CsvResponse::output($mock_logexport);

        // Parse CSV
        $csv = explode("\r\n", trim(ob_get_clean()));
        $header = str_getcsv(array_shift($csv), $_SESSION["glpicsv_delimiter"] ?? ";", '"', "\\");
        $records = array_map(static fn($line) =>  str_getcsv($line, $_SESSION["glpicsv_delimiter"] ?? ";", '"', "\\"), $csv);
        $this->assertCount(5, $header);
        $this->assertCount(1, $records);
        $record = array_pop($records);
        $this->assertEquals($_SESSION['glpicronuserrunning'], $record[2]);
        $this->assertEquals("Add the item", $record[4]);
    }
}
