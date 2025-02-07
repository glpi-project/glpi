<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Csv;

use League\Csv\Writer;

class CsvResponse
{
    /**
     * Output a CSV file using League\Csv
     *
     * @param ExportToCsvInterface $export
     */
    public static function output(ExportToCsvInterface $export): void
    {
        $csv = Writer::createFromString('');

        // Using a non-empty string for `$escape` is deprecated in PHP 8.4.
        // According to https://www.php.net/manual/fr/function.fgetcsv.php, using an empty value for `$escape`
        // will result in the same as using `\`.
        $csv->setEscape('');

        $csv->setDelimiter($_SESSION["glpicsv_delimiter"] ?? ";");
        $csv->insertOne($export->getFileHeader());
        $csv->insertAll($export->getFileContent());
        $csv->download($export->getFileName());
    }
}
