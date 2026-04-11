<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Search\Output;

use Safe\DateTime;

final class Xlsx extends Spreadsheet
{
    public function __construct()
    {
        parent::__construct();
        $this->writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spread);
    }

    public function getMime(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function getFileName(): string
    {
        return "glpi.xlsx";
    }

    /**
     * Format and set the value of a cell
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
     * @param array{int, int}|string $coordinate
     * @param mixed $value
     * @param array<string, mixed> $options
     * @return void
     */
    protected function formatValue(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        $coordinate,
        mixed $value,
        array $options = []
    ): void {
        // If the value is an array and search options are provided, it's a search result item
        if (is_array($value) && isset($options['searchopt'])) {
            $is_date = in_array($options['searchopt']['datatype'] ?? '', ['date', 'datetime']);
            $raw_val = $value[0]['name'] ?? null;
            $is_single_value = ($value['count'] ?? 0) === 1;

            // Attempt to format as a native Excel date if applicable
            if ($is_date && $is_single_value && !empty($raw_val) && $raw_val !== 'NULL') {
                try {
                    $dateTime = new DateTime($raw_val);
                    $glpiFormat = (int) ($_SESSION["glpidate_format"] ?? 0);

                    $formatMap = [
                        0 => ['excel' => 'yyyy-mm-dd'],
                        1 => ['excel' => 'dd-mm-yyyy'],
                        2 => ['excel' => 'mm-dd-yyyy'],
                    ];
                    $selected = $formatMap[$glpiFormat] ?? $formatMap[0];

                    // Convert to Excel numeric date format
                    $excelDateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateTime);
                    $worksheet->setCellValue($coordinate, $excelDateValue);

                    $excelFormat = $selected['excel'] . (($options['searchopt']['datatype'] === 'datetime') ? ' hh:mm' : '');

                    // Apply the native Excel cell style for dates
                    $worksheet->getStyle($coordinate)
                        ->getNumberFormat()
                        ->setFormatCode($excelFormat);
                    return; // Success: native date applied, we exit.
                } catch (\Throwable $e) {
                    // Parsing failed: we intentionally swallow the error and fall through
                    // to the parent's default behavior below.
                }
            }
        }

        // Fallback: delegate to the parent class for standard text/date string formatting
        parent::formatValue($worksheet, $coordinate, $value, $options);
    }
}
