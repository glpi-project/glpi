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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * DAO class for handling project records
 */
class ProjectDAO {

   function addProject($project) {
      global $DB;
      $input = [
         'name' => $project->text,
         'comment' => $project->note,
         'projects_id' => $project->parent,
         'date' => $_SESSION['glpi_currenttime'],
         'plan_start_date' => $project->start_date,
         'plan_end_date' => $project->end_date,
         'priority' => 3,  //medium
         'projectstates_id' => 1,
         'users_id' => \Session::getLoginUserID(),
         'show_on_global_gantt' => 1
      ];
      $proj = new \Project();
      $proj->prepareInputForAdd($input);
      $proj->add($input);
      return $proj;
   }

   function updateProject($project) {
      global $DB;
      $p = new \Project();
      $p->getFromDB($project->id);
      $p->update([
         'id' => $project->id,
         'percent_done' => ($project->progress * 100),
         'name' => $project->text
      ]);
      return true;
   }

   function updateParent($project) {
      global $DB;
      $p = new \Project();
      $p->getFromDB($project->id);
      $input = [
         'id' => $project->id,
         'projects_id' => $project->parent
      ];
      $p->prepareInputForUpdate($input);
      $p->update($input);
   }

   function putInTrashbin($projectId) {
      global $DB;
      if ($projectId > 0) {
         $p = new \Project();
         $p->getFromDB($projectId);
         $p->update([
            'id' => $projectId,
            'is_deleted' => 1
         ]);
      }
      return true;
   }
}
