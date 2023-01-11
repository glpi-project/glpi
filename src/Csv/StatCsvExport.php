<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class StatCsvExport implements ExportToCsvInterface
{
    /** @var array */
    protected $headers;

    /** @var array */
    protected $content;

    public function __construct(array $series, array $options = [])
    {
       // Since the data for both header and content is in $series, let's parse it directly
        $this->parseSeries($series, $options);
    }

    /**
     * Parse values from $series into header and content
     *
     * @param array $series
     * @param array $options
     */
    protected function parseSeries(array $series, array $options): void
    {
        $values  = [];
        $headers = [];
        $content = [];
        $row_num = 0;

        foreach ($series as $serie) {
            $data = $serie['data'];
            if (is_array($data) && count($data)) {
                $headers[$row_num] = $serie['name'] ?? '';
                foreach ($data as $key => $val) {
                    if (!isset($values[$key])) {
                        $values[$key] = [];
                    }
                    if (isset($options['datatype']) && $options['datatype'] == 'average') {
                        $val = round($val, 2);
                    }
                    $values[$key][$row_num] = $val;
                }
            } else {
                $values[$serie['name']][] = $data;
            }
            $row_num++;
        }

       // Add an empty cell at the start
        array_unshift($headers, '');
        ksort($values);

        if (!count($headers) && $options['title']) {
            $headers[] = $options['title'];
        }

       // print values
        foreach ($values as $key => $data) {
            $content_row = [$key];
            foreach ($data as $value) {
                $content_row[] = $value;
            }
            $content[] = $content_row;
        }

        $this->headers = $headers;
        $this->content = $content;
    }

    public function getFileName(): string
    {
        return 'glpi.csv';
    }

    public function getFileHeader(): array
    {
        return $this->headers;
    }

    public function getFileContent(): array
    {
        return $this->content;
    }
}
