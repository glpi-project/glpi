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

// Using sglobal instead of global as it is a PHP keyword.
// This is fixed in php 8 so to be changed back when we no longer support php 7.

namespace Glpi\Stat\Data\Sglobal;

use Glpi\Stat\StatDataAlwaysDisplay;
use Session;

class StatDataSatisfaction extends StatDataAlwaysDisplay
{
   public function __construct(array $params) {
      parent::__construct($params);

      $opensatisfaction   = $this->getDataByType($params, "inter_opensatisfaction");
      $answersatisfaction = $this->getDataByType($params, "answersatisfaction");

      $this->labels = array_keys($opensatisfaction);
      $this->series = [
         [
            'name' => _nx('survey', 'Opened', 'Opened', Session::getPluralNumber()),
            'data' => $opensatisfaction,
         ], [
            'name' => _nx('survey', 'Answered', 'Answered', Session::getPluralNumber()),
            'data' => $answersatisfaction,
         ]
      ];
   }

   public function getTitle(): string {
      return __('Satisfaction survey') . " - " .  __('Tickets');
   }
}
