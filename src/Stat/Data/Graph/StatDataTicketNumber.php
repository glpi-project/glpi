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

namespace Glpi\Stat\Data\Graph;

use Glpi\Stat\StatDataAlwaysDisplay;
use Session;

class StatDataTicketNumber extends StatDataAlwaysDisplay
{
    public function __construct(array $params)
    {
        parent::__construct($params);

        $avgsolved     = $this->getDataByType($params, "inter_avgsolvedtime");
        $avgclosed     = $this->getDataByType($params, "inter_avgclosedtime");
        $avgactiontime = $this->getDataByType($params, "inter_avgactiontime");

        foreach ($avgsolved as $key => &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);
        foreach ($avgclosed as $key => &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);
        foreach ($avgactiontime as $key => &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);

        $this->labels = array_keys($avgsolved);
        $this->series = [
            [
                'name' => __('Closure'),
                'data' => $avgsolved,
            ], [
                'name' => __('Resolution'),
                'data' => $avgclosed,
            ], [
                'name' => __('Real duration'),
                'data' => $avgactiontime,
            ]
        ];

        if ($params['itemtype'] == 'Ticket') {
            $avgtaketime = $this->getDataByType($params, "inter_avgtakeaccount");
            foreach ($avgtaketime as $key => &$val) {
                $val = round($val / HOUR_TIMESTAMP, 2);
            }
            unset($val);

            $this->series[] = [
                'name' => __('Take into account'),
                'data' => $avgtaketime
            ];
        }
    }

    public function getTitle(): string
    {
        return __('Average time') . " - " .  _n('Hour', 'Hours', Session::getPluralNumber());
    }
}
