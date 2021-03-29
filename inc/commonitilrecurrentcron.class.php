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

/**
 * Cron task creating recurrent tickets and changes
 *
 * @since 10.0.0
 */
class CommonITILRecurrentCron extends CommonDBTM
{
   /**
    * Get cron task's description
    *
    * @return array
    */
   public static function cronInfo(): array {
      return [
         'description' => __("Create recurrent tickets and changes")
      ];
   }

   /**
    * Run the cron task
    *
    * @param CronTask $task
    *
    * @return int task status (0: no work to do, 1: work done)
    */
   public static function cronRecurrentItems(CronTask $task) {
      global $DB;

      $total = 0;

      // Concrete classes for which recurrent items can be created
      $targets = [
         TicketRecurrent::class,
         RecurrentChange::class,
      ];

      foreach ($targets as $itemtype) {
         $iterator = $DB->request([
            'FROM'   => $itemtype::getTable(),
            'WHERE'  => [
               'next_creation_date' => ['<', new \QueryExpression('NOW()')],
               'is_active'          => 1,
               'OR'                 => [
                  ['end_date' => null],
                  ['end_date' => ['>', new \QueryExpression('NOW()')]]
               ]
            ]
         ]);

         while ($data = $iterator->next()) {
            /** @var CommonITILRecurrent */
            $item = new $itemtype();
            $item->fields = $data;

            if ($item->createItem()) {
               $total++;
            } else {
               //TRANS: %s is a name
               $task->log(
                  sprintf(
                     __('Failed to create recurrent item %s'),
                     $data['name']
                  )
               );
            }
         }
      }

      $task->setVolume($total);
      return (int) ($total > 0);
   }
}
