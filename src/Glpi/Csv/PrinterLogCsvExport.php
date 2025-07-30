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

use Printer;
use PrinterLog;
use Safe\DateTime;

class PrinterLogCsvExport implements ExportToCsvInterface
{
    protected Printer $printer;
    protected string $interval;
    protected ?DateTime $start_date;
    protected ?DateTime $end_date;
    protected string $format;

    public function __construct(
        Printer $printer,
        string $interval,
        ?DateTime $start_date,
        ?DateTime $end_date,
        string $format
    ) {
        $this->printer = $printer;
        $this->interval = $interval;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->format = $format;
    }

    public function getFileName(): string
    {
        return !empty($this->printer->fields['name'])
            ? "{$this->printer->fields['name']}.csv"
            : "printer_{$this->printer->getID()}.csv";
    }

    public function getFileHeader(): array
    {
        $headers = [
            'date' => _n('Date', 'Dates', 1),
        ];

        foreach (
            PrinterLog::getMetrics(
                $this->printer,
                [],
                $this->interval,
                $this->start_date,
                $this->end_date,
                $this->format
            )[$this->printer->getID()][0] as $key => $value
        ) {
            $label = PrinterLog::getLabelFor($key);
            if ($label && $value > 0) {
                $headers[$key] = $label;
            }
        }

        return $headers;
    }

    public function getFileContent(): array
    {
        return array_map(function ($metric) {
            $fields = [];

            foreach ($metric as $key => $value) {
                $label = PrinterLog::getLabelFor($key);
                if ($label && $value > 0) {
                    $fields[$key] = $value;
                }
            }

            return [
                'date' => $metric['date'],
            ] + $fields;
        }, PrinterLog::getMetrics(
            $this->printer,
            [],
            $this->interval,
            $this->start_date,
            $this->end_date,
            $this->format
        )[$this->printer->getID()]);
    }
}
