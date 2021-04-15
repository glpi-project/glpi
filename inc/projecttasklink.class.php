<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Represents a dependency relation between project tasks
 * Possible link types are "finish_to_start":"0", "start_to_start":"1", "finish_to_finish":"2", "start_to_finish":"3"
 *
 * @since 9.5.4
 *
 */
class ProjectTaskLink extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1 = 'ProjectTask';
   static public $items_id_1 = 'source_id';

   static public $itemtype_2 = 'ProjectTask';
   static public $items_id_2 = 'target_id';

   public function getFromDBForItemIDs($projecttaskIds) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['glpi_projecttasklinks.*'],
         'FROM' => 'glpi_projecttasklinks',
         'WHERE' => "source_id IN (" . $projecttaskIds . ") AND target_id IN (" . $projecttaskIds . ")"
      ]);

      return $iterator;
   }

   public function checkIfExist($taskLink) {
      global $DB;
      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM' => self::getTable(),
         'WHERE' => [
            'AND' => ['source_id' => $taskLink['source_id']
            ],
            ['target_id' => $taskLink['target_id']],
            ['type' => $taskLink['type']]
         ]
      ]);
      return count($iterator) > 0;
   }
}