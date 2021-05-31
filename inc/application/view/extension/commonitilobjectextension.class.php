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

namespace Glpi\Application\View\Extension;

use CommonITILObject;
use Planning;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;

/**
 * @since 10.0.0
 */
class CommonITILObjectExtension extends AbstractExtension implements ExtensionInterface {

   public function getFilters() {
      return [
         new TwigFilter('getTimelineStats', [$this, 'getTimelineStats']),
      ];
   }


   public function getTimelineStats(CommonITILObject $item): array {
      global $DB;

      $stats = [
         'total_duration' => 0,
         'percent_done'   => 0,
      ];

      // compute itilobject duration
      $taskClass  = $item::getType() . "Task";
      $task_table = getTableForItemType($taskClass);
      $foreignKey = $item::getForeignKeyField();
      $criteria   = [
         'SELECT'   => ['SUM' => 'actiontime AS actiontime'],
         'FROM'     => $task_table,
         'WHERE'    => [$foreignKey => $item->fields['id']]
      ];

      $req = $DB->request($criteria);
      if ($row = $req->next()) {
         $stats['total_duration'] = $row['actiontime'];
      }

      // compute itilobject percent done
      $criteria    = [
         $foreignKey => $item->fields['id'],
         'state'     => [Planning::TODO, Planning::DONE]
      ];
      $total_tasks = countElementsInTable($task_table, $criteria);
      $criteria    = [
         $foreignKey => $item->fields['id'],
         'state'     => Planning::DONE,
      ];
      $done_tasks = countElementsInTable($task_table, $criteria);
      $stats['percent_done'] = floor(100 * $done_tasks / $total_tasks);

      return $stats;
   }

}
