<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Stat;

use Stat;

/**
 * Base class for stats data meant to be displayed in a pie or line graph
 */
abstract class StatData
{
    protected $labels;
    protected $series;
    protected $total;
    protected $csv_link;
    protected $options;
    protected $params;

    public function __construct(array $params = [])
    {
        global $CFG_GLPI;

       // Set up link to the download as csv page with the same parameters
        if (count($params)) {
            $base_link = $CFG_GLPI['root_doc'] . '/front/graph.send.php?';
            $params['statdata_itemtype'] = static::class;
            $this->csv_link = $base_link . http_build_query($params);
        }

        $this->params = $params;
        $this->labels = [];
        $this->series = [];
        $this->options = [];
        $this->total  = 0;
    }

    public function getDataByType(array $params, string $type)
    {
        return Stat::constructEntryValues(
            $params['itemtype'],
            $type,
            $params['date1'],
            $params['date2'],
            $params['type'] ?? "",
            $params['val1'] ?? "",
            $params['val2'] ?? ""
        );
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function getSeries(): array
    {
        return $this->series;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isEmpty(): bool
    {
        return $this->total === 0;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getCsvLink(): ?string
    {
        return $this->csv_link;
    }

    public function getTitle(): string
    {
        return "";
    }
}
