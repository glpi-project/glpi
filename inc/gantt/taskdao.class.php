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

namespace Glpi\Gantt;

use \Exception;
use Glpi\Gantt\DataFactory;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * DAO class for handling project task records
 */
class TaskDAO {

   function addTask($task) {

      if (!\ProjectTask::canCreate()) {
         throw new \Exception(__('Not enough rights'));
      }

      $t = new \ProjectTask();

      $projectId = $task->parent;
      $parentTask = null;
      if (!is_numeric($task->parent)) {
         if ($t->getFromDBByCrit(['uuid' => $task->parent])) {
            $parentTask = $t;
            $projectId = $parentTask->fields["projects_id"];
         }
      }

      $input = [
         'name' => $task->text,
         'projects_id' => $projectId,
         'projecttasks_id' => ($parentTask != null) ? $parentTask->fields["id"] : 0,
         'percent_done' => ($task->progress * 100),
         'plan_start_date' => $task->start_date,
         'plan_end_date' => $task->end_date,
         'is_milestone' => ($task->type == "milestone") ? 1 : 0
      ];

      $newTask = new \ProjectTask();
      $newTask->add($input);
      return $newTask;
   }

   function updateTask($task) {
      $t = new \ProjectTask();
      $t->getFromDB($task->id);

      if (!$t::canUpdate() || !$t->canUpdateItem()) {
         throw new \Exception(__('Not enough rights'));
      }

      $t->update([
         'id' => $task->id,
         'plan_start_date' => $task->start_date,
         'real_start_date' => ($task->type == "milestone" && $t->fields["real_start_date"] == null) ? $task->start_date : $t->fields["real_start_date"],
         'plan_end_date' => $task->end_date,
         'percent_done' => ($task->progress * 100),
         'name' => $task->text ?? $t->fields['name'],
         'is_milestone' => ($task->type == "milestone") ? 1 : 0
      ]);
      return true;
   }

   function updateParent($task) {
      $t = new \ProjectTask();
      $t->getFromDBByCrit(['uuid' => $task->id]);

      if (!$t::canUpdate() || !$t->canUpdateItem()) {
         throw new \Exception(__('Not enough rights'));
      }

      if (!is_numeric($task->parent)) {
         // change parent task
         $p = new \ProjectTask();
         $p->getFromDBByCrit(['uuid' => $task->parent]);

         $updateSubtasks = ($t->fields["projects_id"] != $p->fields["projects_id"]);

         $input = [
            'id' => $t->fields["id"],
            'projects_id' => $p->fields["projects_id"],
            'projecttasks_id' => $p->fields["id"]
         ];
         $t->update($input);

         $itemArray = [];
         if ($updateSubtasks) {

            // change subtasks parent project
            $factory = new DataFactory();
            $factory->getSubtasks($itemArray, $t->fields["id"]);

            foreach ($itemArray as $item) {
               $itm = new \ProjectTask();
               $itm->getFromDBByCrit(['uuid' => $item->id]);
               $params = [
                  'id' => $itm->fields["id"],
                  'projects_id' => $p->fields["projects_id"]
               ];
               $itm->update($params);
            }
         }
      } else if ($task->parent > 0) {
         // change parent project
         $input = [
           'id' => $t->fields["id"],
           'projects_id' => $task->parent,
           'projecttasks_id' => 0
         ];

         $t->update($input);

         // change subtasks parent project
         $itemArray = [];
         $factory = new DataFactory();
         $factory->getSubtasks($itemArray, $t->fields["id"]);

         foreach ($itemArray as $item) {
            $itm = new \ProjectTask();
            $itm->getFromDBByCrit(['uuid' => $item->id]);
            $params = [
               'id' => $itm->fields["id"],
               'projects_id' => $t->fields["projects_id"]
            ];
            $itm->update($params);
         }
      }
      return true;
   }

}
