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

use \DbTestCase;

/* Test for inc/projecttask.class.php */

class ProjectTask extends DbTestCase {

   public function testPlanningConflict() {
      $this->login();

      $user = getItemByTypeName('User', 'tech');
      $users_id = (int)$user->fields['id'];

      $ptask = new \ProjectTask();
      $this->integer(
         (int)$ptask->add([
            'name'   => 'test'
         ])
      )->isIdenticalTo(0);

      $this->hasSessionMessage(ERROR, ['A linked project is mandatory']);

      $project = new \Project();
      $pid = (int)$project->add([
         'name'   => 'Test project'
      ]);
      $this->integer($pid)->isGreaterThan(0);

      $this->integer(
         (int)$ptask->add([
            'name'                     => 'first test, whole period',
            'projects_id'              => $pid,
            'plan_start_date'          => '2019-08-10',
            'plan_end_date'            => '2019-08-20',
            'projecttasktemplates_id'  => 0
         ])
      )->isGreaterThan(0);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();
      $task_id = $ptask->fields['id'];

      $team = new \ProjectTaskTeam();
      $tid = (int)$team->add([
         'projecttasks_id' => $ptask->fields['id'],
         'itemtype'        => \User::getType(),
         'items_id'        => $users_id
      ]);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();
      $this->integer($tid)->isGreaterThan(0);

      $this->integer(
         (int)$ptask->add([
            'name'                     => 'test, subperiod',
            'projects_id'              => $pid,
            'plan_start_date'          => '2019-08-13',
            'plan_end_date'            => '2019-08-14',
            'projecttasktemplates_id'  => 0
         ])
      )->isGreaterThan(0);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      $team = new \ProjectTaskTeam();
      $tid = (int)$team->add([
         'projecttasks_id' => $ptask->fields['id'],
         'itemtype'        => \User::getType(),
         'items_id'        => $users_id
      ]);

      $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
      $this->hasSessionMessage(
         WARNING, [
            "The user $usr_str is busy at the selected timeframe.<br/>- Project task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='".
            $ptask->getFormURLWithID($task_id)."'>first test, whole period</a><br/>"
         ]
      );
      $this->integer($tid)->isGreaterThan(0);

      //check when updating. first create a new task out of existing bouds
      $this->integer(
         (int)$ptask->add([
            'name'                     => 'test subperiod, out of bounds',
            'projects_id'              => $pid,
            'plan_start_date'          => '2018-08-13',
            'plan_end_date'            => '2018-08-24',
            'projecttasktemplates_id'  => 0
         ])
      )->isGreaterThan(0);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      $team = new \ProjectTaskTeam();
      $tid = (int)$team->add([
         'projecttasks_id' => $ptask->fields['id'],
         'itemtype'        => \User::getType(),
         'items_id'        => $users_id
      ]);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();
      $this->integer($tid)->isGreaterThan(0);

      $this->boolean(
         $ptask->update([
            'id'                       => $ptask->fields['id'],
            'name'                     => 'test subperiod, no longer out of bounds',
            'projects_id'              => $pid,
            'plan_start_date'          => '2019-08-13',
            'plan_end_date'            => '2019-08-24',
            'projecttasktemplates_id'  => 0
         ])
      )->isTrue();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isNotEmpty()
         ->hasKey(WARNING);
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

      //create reference ticket
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'               => 'ticket title',
            'description'        => 'a description',
            'content'            => '',
            'entities_id'        => getItemByTypeName('Entity', '_test_root_entity', true),
            '_users_id_assign'   => getItemByTypeName('User', 'tech', true)
         ])
      )->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      $tid = (int)$ticket->fields['id'];

      $this->hasSessionMessage(
         INFO, [
            "Your ticket has been registered. (Ticket: <a href='".\Ticket::getFormURLWithID($tid)."'>$tid</a>)"
         ]
      );

      $ttask = new \TicketTask();
      $ttask_id = (int)$ttask->add([
         'name'               => 'A ticket task in bounds',
         'content'            => 'A ticket task in bounds',
         'tickets_id'         => $tid,
         'plan'               => [
            'begin'  => '2019-08-11',
            'end'    => '2019-08-12'
         ],
         'users_id_tech'      => $users_id,
         'tasktemplates_id'   => 0
      ]);
      $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';

      $this->hasSessionMessage(
         WARNING, [
            "The user $usr_str is busy at the selected timeframe.<br/>- Project task: from 2019-08-11  to 2019-08-12 :<br/><a href='".
            $ptask->getFormURLWithID($task_id)."'>first test, whole period</a><br/>"
         ]
      );
      $this->integer($ttask_id)->isGreaterThan(0);
   }
}
