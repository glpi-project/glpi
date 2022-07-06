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

// Using sglobal instead of global as it is a PHP keyword.
// This is fixed in php 8 so to be changed back when we no longer support php 7.

namespace Glpi\Stat\Data\Sglobal;

use Glpi\Stat\StatDataAlwaysDisplay;
use Session;

class StatDataTicketNumber extends StatDataAlwaysDisplay
{
    public function __construct(array $params)
    {
        parent::__construct($params);

        $total  = $this->getDataByType($params, "inter_total");
        $solved = $this->getDataByType($params, "inter_solved");
        $closed = $this->getDataByType($params, "inter_closed");
        $late   = $this->getDataByType($params, "inter_solved_late");

        $this->labels = array_keys($total);
        $this->series = [
            [
                'name' => _nx('ticket', 'Opened', 'Opened', Session::getPluralNumber()),
                'data' => $total,
            ], [
                'name' => _nx('ticket', 'Solved', 'Solved', Session::getPluralNumber()),
                'data' => $solved,
            ], [
                'name' => __('Late'),
                'data' => $late,
            ], [
                'name' => __('Closed'),
                'data' => $closed,
            ]
        ];
    }

    public function getTitle(): string
    {
        $item = getItemForItemtype($this->params['itemtype']);
        return _x('Quantity', 'Number') . " - " . $item->getTypeName(Session::getPluralNumber());
    }
}
