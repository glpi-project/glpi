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

// Using sglobal instead of global as it is a PHP keyword.
// This is fixed in php 8 so to be changed back when we no longer support php 7.

namespace Glpi\Stat\Data\Sglobal;

use Glpi\Stat\StatDataAlwaysDisplay;
use Session;

class StatDataTicketAverageTime extends StatDataAlwaysDisplay
{
    public function __construct(array $params)
    {
        parent::__construct($params);

        $avgsolved     = $this->getDataByType($params, "inter_avgsolvedtime");
        $avgclosed     = $this->getDataByType($params, "inter_avgclosedtime");
        $avgactiontime = $this->getDataByType($params, "inter_avgactiontime");

        // Convert to hours
        foreach ($avgsolved as &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);
        foreach ($avgclosed as &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);
        foreach ($avgactiontime as &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);

        $this->labels = array_keys($avgsolved);
        $this->series = [
            [
                'name' => __('Closure'),
                'data' => $avgclosed,
            ], [
                'name' => __('Resolution'),
                'data' => $avgsolved,
            ], [
                'name' => __('Real duration'),
                'data' => $avgactiontime,
            ],
        ];
    }

    public function getTitle(): string
    {
        return __('Average time') . " - " . _n('Hour', 'Hours', Session::getPluralNumber());
    }
}
