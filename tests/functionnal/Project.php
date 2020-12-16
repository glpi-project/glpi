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
namespace tests\units;

use DbTestCase;

/* Test for inc/project.class.php */
class Project extends DbTestCase {

   public function testAutocalculatePercentDone() {

      $this->login(); // must be logged as ProjectTask uses Session::getLoginUserID()

      $project = new \Project();
      $project_id_1 = $project->add([
         'name' => 'Project 1',
         'auto_percent_done' => 1
      ]);
      $this->integer((int) $project_id_1)->isGreaterThan(0);
      $project_id_2 = $project->add([
         'name' => 'Project 2',
         'auto_percent_done' => 1,
         'projects_id' => $project_id_1
      ]);
      $this->integer((int) $project_id_2)->isGreaterThan(0);
      $project_id_3 = $project->add([
         'name' => 'Project 3',
         'projects_id' => $project_id_2
      ]);
      $this->integer((int) $project_id_3)->isGreaterThan(0);

      $projecttask = new \ProjectTask();
      $projecttask_id_1 = $projecttask->add([
         'name' => 'Project Task 1',
         'auto_percent_done' => 1,
         'projects_id' => $project_id_2,
         'projecttasktemplates_id' => 0
      ]);
      $this->integer((int) $projecttask_id_1)->isGreaterThan(0);
      $projecttask_id_2 = $projecttask->add([
         'name' => 'Project Task 2',
         'projects_id' => 0,
         'projecttasks_id' => $projecttask_id_1,
         'projecttasktemplates_id' => 0
      ]);
      $this->integer((int) $projecttask_id_2)->isGreaterThan(0);

      $project_1 = new \Project();
      $this->boolean($project_1->getFromDB($project_id_1))->isTrue();
      $project_2 = new \Project();
      $this->boolean($project_2->getFromDB($project_id_2))->isTrue();
      $project_3 = new \Project();
      $this->boolean($project_3->getFromDB($project_id_3))->isTrue();
      $this->boolean($project_3->update([
         'id'           => $project_id_3,
         'percent_done' => '10'
      ]))->isTrue();

      // Reload projects to get newest values
      $this->boolean($project_1->getFromDB($project_id_1))->isTrue();
      $this->boolean($project_2->getFromDB($project_id_2))->isTrue();
      // Test parent and parent's parent percent done
      $this->integer($project_2->fields['percent_done'])->isEqualTo(5);
      $this->integer($project_1->fields['percent_done'])->isEqualTo(5);

      $projecttask_1 = new \ProjectTask();
      $this->boolean($projecttask_1->getFromDB($projecttask_id_1))->isTrue();
      $projecttask_2 = new \ProjectTask();
      $this->boolean($projecttask_2->getFromDB($projecttask_id_2))->isTrue();

      $this->boolean($projecttask_2->update([
         'id'           => $projecttask_id_2,
         'percent_done' => '40'
      ]))->isTrue();

      // Reload projects and tasks to get newest values
      $this->boolean($project_1->getFromDB($project_id_1))->isTrue();
      $this->boolean($project_2->getFromDB($project_id_2))->isTrue();
      $this->boolean($project_3->getFromDB($project_id_3))->isTrue();
      $this->boolean($projecttask_1->getFromDB($projecttask_id_1))->isTrue();
      $this->integer($projecttask_1->fields['percent_done'])->isEqualTo(40);
      // Check that the child project wasn't changed
      $this->integer($project_3->fields['percent_done'])->isEqualTo(10);
      $this->integer($project_2->fields['percent_done'])->isEqualTo(25);
      $this->integer($project_1->fields['percent_done'])->isEqualTo(25);

      // Test that percent done updates on delete and restore
      $project_3->delete(['id' => $project_id_3]);
      $this->boolean($project_2->getFromDB($project_id_2))->isTrue();
      $this->integer($project_2->fields['percent_done'])->isEqualTo(40);
      $project_3->restore(['id' => $project_id_3]);
      $this->boolean($project_2->getFromDB($project_id_2))->isTrue();
      $this->integer($project_2->fields['percent_done'])->isEqualTo(25);
   }
}
