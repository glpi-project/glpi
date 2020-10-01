<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Stat\Data\Graph;

use Glpi\Stat\StatDataAlwaysDisplay;
use Session;

class StatDataTicketAverageTime extends StatDataAlwaysDisplay
{
   public function __construct(array $params) {
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
            'data' => $closed,
         ], [
            'name' => __('Closed'),
            'data' => $late,
         ]
      ];
   }

   public function getTitle(): string {
      $item = getItemForItemtype($this->params['itemtype']);
      return _x('Quantity', 'Number') . " - " . $item->getTypeName(Session::getPluralNumber());
   }
}
