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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * DAO class for handling project task records
 */
class TaskDAO {

   function updateTask($task) {
      global $DB;
      $t = new \ProjectTask();
      $t->getFromDB($task->id);

      $t->update([
         'id' => $task->id,
         'plan_start_date' => $task->start_date,
         'plan_end_date' => $task->end_date,
         'percent_done' => ($task->progress * 100),
         'name' => (isset($task->text) ? $task->text : $t->fields['name'])
      ]);
      return true;
   }

   function deleteTask(&$failed, $taskId) {
      global $DB;
      if ($taskId > 0) {
         foreach ($DB->request('glpi_projecttasks', ['projecttasks_id' => $taskId]) as $record) {
            if (isset($record['id'])) {
               if (!$this->deleteTask($failed, $record['id'])) {
                  $failed[] = $record;
               }
            }
         }
         try {
            $DB->delete(\ProjectTask::getTable(), ['id' => $taskId]);
         } catch (Exception $ex) {
            return false;
         }
      }
      return true;
   }
}
