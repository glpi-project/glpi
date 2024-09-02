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

namespace tests\units\Glpi\Csv;

use Computer;
use DbTestCase;
use Glpi\Csv\CsvResponse as Core_CsvResponse;
use Glpi\Csv\LogCsvExport as CsvLogCsvExport;
use League\Csv\Reader;

class CsvResponse extends DbTestCase
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

        $this->integer($id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($id))->isTrue();

       // Output CSV
        ob_start();
        Core_CsvResponse::output(new CsvLogCsvExport($computer, []));

       // Parse CSV
        $csv = Reader::createFromString(ob_get_clean());
        $csv->setHeaderOffset(0);
        $csv->setDelimiter($_SESSION["glpicsv_delimiter"] ?? ";");
        $csv->setEscape('');
        $header = $csv->getHeader();
        $records = iterator_to_array($csv->getRecords());

       // Check if content is OK
        $this->array($header)->hasSize(5);
        $this->array($records)->hasSize(1);
        $record = array_pop($records);
        $this->string($record['User'])->isEqualTo($_SESSION['glpicronuserrunning']);
        $this->string($record['Update'])->isEqualTo("Add the item");
    }
}
