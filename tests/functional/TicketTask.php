<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;

/* Test for inc/tickettask.class.php */

class TicketTask extends DbTestCase
{
    /**
     * Create a new ticket
     *
     * @param boolean $as_object Return Ticket object or its id
     *
     * @return integer|Ticket
     */
    private function getNewTicket($as_object = false)
    {
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

        return ($as_object ? $ticket : $tid);
    }

    public function testSchedulingAndRecall()
    {
        $this->login();
        $ticketId = $this->getNewTicket();
        $uid = getItemByTypeName('User', TU_USER, true);

        $date_begin = new \DateTime(); // ==> now
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');

        $date_end = new \DateTime(); // ==> +2days
        $date_end->add(new \DateInterval('P2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

       //create one task with schedule and recall
        $task = new \TicketTask();
        $task_id = $task->add([
            'state'              => \Planning::TODO,
            'tickets_id'         => $ticketId,
            'tasktemplates_id'   => '0',
            'taskcategories_id'  => '0',
            'content'            => "Task with schedule and recall",
            'users_id_tech'      => $uid,
            'plan'               => [
                'begin'        => $date_begin_string,
                'end'          => $date_end_string,
            ],
            '_planningrecall'    => ['before_time' => '14400', //recall 4 hours
                'itemtype'    => 'TicketTask',
                'users_id'    => $uid,
                'field'       => 'begin', //default
            ]
        ]);
        $this->integer($task_id)->isGreaterThan(0);

       //load plannig schedule with recall
        $recall = new \PlanningRecall();

       //calcul 'when'
        $when = date("Y-m-d H:i:s", strtotime($task->fields['begin']) - 14400);
        $this->boolean($recall->getFromDBByCrit(['before_time'   => '14400', //recall 4 hours
            'itemtype'     => 'TicketTask',
            'items_id'     => $task_id,
            'users_id'     => $uid,
            'when'         => $when,
        ]))->isTrue();

        //create one task with schedule and without recall
        $date_begin = new \DateTime();
        $date_begin->add(new \DateInterval('P1M'));
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');

        $date_begin = new \DateTime();
        $date_end->add(new \DateInterval('P1M2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $task = new \TicketTask();
        $task_id = $task->add([
            'state'              => \Planning::TODO,
            'tickets_id'         => $ticketId,
            'tasktemplates_id'   => '0',
            'taskcategories_id'  => '0',
            'content'            => "Task with schedule and without recall",
            'users_id_tech'      => $uid,
            'plan'               => [
                'begin'       => $date_begin_string,
                'end'         => $date_end_string,
            ],
            '_planningrecall' => ['before_time' => '-10', //recall to none
                'itemtype'    => 'TicketTask',
                'users_id'    => $uid,
                'field'       => 'begin', //default
            ]
        ]);
        $this->integer($task_id)->isGreaterThan(0);

       //load schedule //which return false (not exist yet without recall)
        $recall = new \PlanningRecall();
        $this->boolean($recall->getFromDBByCrit(['itemtype'   => 'TicketTask',
            'items_id'  => $task_id,
            'users_id'  => $uid,
        ]))->isFalse();

       //update task schedule with recall
        $this->boolean(
            $task->update([
                'id'                 => $task_id,
                'state'              => \Planning::TODO,
                'tickets_id'         => $ticketId,
                'tasktemplates_id'   => '0',
                'taskcategories_id'  => '0',
                'actiontime'         => "172800", //2 days
                'content'            => "Task with schedule and without recall",
                'users_id_tech'      => $uid,
                'plan'               => [
                    'begin'       => $date_begin_string,
                    'end'         => $date_end_string,
                ],
                '_planningrecall' => ['before_time' => '900',
                    'itemtype'    => 'TicketTask',
                    'users_id'    => $uid,
                    'field'       => 'begin', //default
                ]
            ])
        )->isTrue();

       //load planning recall
        $recall = new \PlanningRecall();

       //calcul when
        $when = date("Y-m-d H:i:s", strtotime($task->fields['begin']) - 900);
        $this->boolean($recall->getFromDBByCrit(['before_time'  => '900',
            'itemtype'    => 'TicketTask',
            'items_id'    => $task_id,
            'users_id'    => $uid,
            'when'        => $when,
        ]))->isTrue();
    }

    public function testGetTaskList()
    {

        $this->login();
        $ticketId = $this->getNewTicket();
        $uid = getItemByTypeName('User', TU_USER, true);

        $tasksstates = [
            \Planning::TODO,
            \Planning::TODO,
            \Planning::INFO
        ];
       //create few tasks
        $task = new \TicketTask();
        foreach ($tasksstates as $taskstate) {
            $this->integer(
                $task->add([
                    'content'      => sprintf('Task with "%s" state', $taskstate),
                    'state'        => $taskstate,
                    'tickets_id'   => $ticketId,
                    'users_id_tech' => $uid
                ])
            )->isGreaterThan(0);
        }

        $iterator = $task::getTaskList('todo', false);
       //we create two ones plus the one in bootstrap data
        $this->integer(count($iterator))->isIdenticalTo(3);

        $iterator = $task::getTaskList('todo', true);
        $this->integer(count($iterator))->isIdenticalTo(0);

        $_SESSION['glpigroups'] = [42, 1337];
        $iterator = $task::getTaskList('todo', true);
       //no task for those groups
        $this->integer(count($iterator))->isIdenticalTo(0);
    }

    public function testCentralTaskList()
    {
        $this->login();
        $ticketId = $this->getNewTicket();
        $uid = getItemByTypeName('User', TU_USER, true);

        $tasksstates = [
            \Planning::TODO,
            \Planning::TODO,
            \Planning::TODO,
            \Planning::INFO,
            \Planning::INFO
        ];
       //create few tasks
        $task = new \TicketTask();
        foreach ($tasksstates as $taskstate) {
            $this->integer(
                $task->add([
                    'content'      => sprintf('Task with "%s" state', $taskstate),
                    'state'        => $taskstate,
                    'tickets_id'   => $ticketId,
                    'users_id_tech' => $uid
                ])
            )->isGreaterThan(0);
        }

       //How could we test there are 4 matching links?
        $this->output(
            function () {
                \TicketTask::showCentralList(0, 'todo', false);
            }
        )
         ->contains("Ticket tasks to do <span class='primary-bg primary-fg count'>4</span>")
         ->matches("/a href='\/glpi\/front\/ticket.form.php\?id=\d+[^']+'>/");

       //How could we test there are 2 matching links?
        $this->output(
            function () {
                $_SESSION['glpidisplay_count_on_home'] = 2;
                \TicketTask::showCentralList(0, 'todo', false);
                unset($_SESSION['glpidisplay_count_on_home']);
            }
        )
         ->contains("Ticket tasks to do <span class='primary-bg primary-fg count'>2 on 4</span>")
         ->matches("/a href='\/glpi\/front\/ticket.form.php\?id=\d+[^']+'>/");
    }

    public function testPlanningConflict()
    {
        $this->login();

        $user = getItemByTypeName('User', 'tech');
        $users_id = (int)$user->fields['id'];

        $ticket = $this->getNewTicket(true);
        $tid = $ticket->fields['id'];

        $ttask = new \TicketTask();
        $this->integer(
            (int)$ttask->add([
                'name'               => 'first test, whole period',
                'content'            => 'first test, whole period',
                'tickets_id'         => $tid,
                'plan'               => [
                    'begin'  => '2019-08-10',
                    'end'    => '2019-08-20'
                ],
                'users_id_tech'      => $users_id,
                'tasktemplates_id'   => 0
            ])
        )->isGreaterThan(0);
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $this->integer(
            (int)$ttask->add([
                'name'               => 'test, subperiod',
                'content'            => 'test, subperiod',
                'tickets_id'         => $tid,
                'plan'               => [
                    'begin'   => '2019-08-13',
                    'end'     => '2019-08-14'
                ],
                'users_id_tech'      => $users_id,
                'tasktemplates_id'   => 0
            ])
        )->isGreaterThan(0);

        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Ticket task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='" .
            $ticket->getFormURLWithID($tid) . "&amp;forcetab=TicketTask$1'>ticket title</a><br/>"
            ]
        );
        $this->integer($tid)->isGreaterThan(0);

       //add another task to be updated
        $this->integer(
            (int)$ttask->add([
                'name'               => 'first test, whole period',
                'content'            => 'first test, whole period',
                'tickets_id'         => $tid,
                'plan'               => [
                    'begin'  => '2018-08-10',
                    'end'    => '2018-08-20'
                ],
                'users_id_tech'      => $users_id,
                'tasktemplates_id'   => 0
            ])
        )->isGreaterThan(0);
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $this->boolean($ttask->getFromDB($ttask->fields['id']))->isTrue();

        $this->boolean(
            $ttask->update([
                'id'           => $ttask->fields['id'],
                'tickets_id'   => $tid,
                'plan'               => [
                    'begin'  => str_replace('2018', '2019', $ttask->fields['begin']),
                    'end'    => str_replace('2018', '2019', $ttask->fields['end'])
                ],
                'users_id_tech'      => $users_id,
            ])
        )->isTrue();

        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Ticket task: from 2019-08-10 00:00 to 2019-08-20 00:00:<br/><a href='" .
            $ticket->getFormURLWithID($tid) . "&amp;forcetab=TicketTask$1'>ticket title</a><br/>- Ticket task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='" . $ticket
            ->getFormURLWithID($tid) . "&amp;forcetab=TicketTask$1'>ticket title</a><br/>"
            ]
        );
    }

    public function testAddFromTemplate()
    {
        $ticket = $this->getNewTicket(true);
        $template = new \TaskTemplate();
        $templates_id = $template->add([
            'name'               => 'test template',
            'content'            => 'test template',
            'users_id_tech'      => getItemByTypeName('User', TU_USER, true),
            'state'              => \Planning::DONE,
            'is_private'         => 1,
        ]);
        $this->integer($templates_id)->isGreaterThan(0);
        $task = new \TicketTask();
        $tasks_id = $task->add([
            '_tasktemplates_id'  => $templates_id,
            'itemtype'           => 'Ticket',
            'tickets_id'         => $ticket->fields['id'],
        ]);
        $this->integer($tasks_id)->isGreaterThan(0);

        $this->string($task->fields['content'])->isEqualTo('&#60;p&#62;test template&#60;/p&#62;');
        $this->integer($task->fields['users_id_tech'])->isEqualTo(getItemByTypeName('User', TU_USER, true));
        $this->integer($task->fields['state'])->isEqualTo(\Planning::DONE);
        $this->integer($task->fields['is_private'])->isEqualTo(1);

        $tasks_id = $task->add([
            '_tasktemplates_id'  => $templates_id,
            'itemtype'           => 'Ticket',
            'tickets_id'         => $ticket->fields['id'],
            'state'              => \Planning::TODO,
            'is_private'         => 0,
        ]);
        $this->integer($tasks_id)->isGreaterThan(0);

        $this->string($task->fields['content'])->isEqualTo('&#60;p&#62;test template&#60;/p&#62;');
        $this->integer($task->fields['users_id_tech'])->isEqualTo(getItemByTypeName('User', TU_USER, true));
        $this->integer($task->fields['state'])->isEqualTo(\Planning::TODO);
        $this->integer($task->fields['is_private'])->isEqualTo(0);
    }

    public function testUpdateParentStatus()
    {
        $this->login();
        $ticket_id = $this->getNewTicket();
        $task = new \TicketTask();

        $uid = getItemByTypeName('User', TU_USER, true);
        $date_begin = new \DateTime(); // ==> now
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +2days
        $date_end->add(new \DateInterval('P2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $this->integer($task->add([
            'pending'            => 1,
            'tickets_id'         => $ticket_id,
            'content'            => "Task with schedule",
            'state'              => \Planning::TODO,
            'users_id_tech'      => $uid,
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]))->isGreaterThan(0);

        $this->integer(\Ticket::getById($ticket_id)->fields['status'])->isEqualTo(\Ticket::WAITING);

        $date_begin = new \DateTime(); // ==> +3days
        $date_begin->add(new \DateInterval('P3D'));
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +4days
        $date_end->add(new \DateInterval('P4D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $this->integer($task->add([
            'pending'            => 0,
            'tickets_id'         => $ticket_id,
            'content'            => "Task with schedule",
            'state'              => \Planning::TODO,
            'users_id_tech'      => $uid,
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]))->isGreaterThan(0);

        $this->integer(\Ticket::getById($ticket_id)->fields['status'])->isEqualTo(\Ticket::PLANNED);

        $date_begin = new \DateTime(); // ==> +5days
        $date_begin->add(new \DateInterval('P5D'));
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +6days
        $date_end->add(new \DateInterval('P6D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $this->integer($task->add([
            'pending'            => 1,
            'tickets_id'         => $ticket_id,
            'content'            => "Task with schedule",
            'state'              => \Planning::TODO,
            'users_id_tech'      => $uid,
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]))->isGreaterThan(0);

        $this->integer(\Ticket::getById($ticket_id)->fields['status'])->isEqualTo(\Ticket::WAITING);
    }

    /**
     * Test that the task duration is correctly updated
     *
     * @return void
     */
    public function testTaskDurationUpdate()
    {
        $this->login();
        $ticketId = $this->getNewTicket();
        $uid = getItemByTypeName('User', TU_USER, true);

        $date_begin = new \DateTime(); // ==> now
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');

        $date_end = new \DateTime(); // ==> +2days
        $date_end->add(new \DateInterval('P2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        // Create task with actiontime and without schedule
        $task = new \TicketTask();
        $task_id = $task->add([
            'state'              => \Planning::TODO,
            'tickets_id'         => $ticketId,
            'tasktemplates_id'   => '0',
            'taskcategories_id'  => '0',
            'content'            => "Task with schedule and recall",
            'users_id_tech'      => $uid,
            'actiontime'         => 3600,
        ]);
        $this->integer($task_id)->isGreaterThan(0);

        // Check that the task duration is correctly updated
        $this->integer($task->fields['actiontime'])->isEqualTo(3600);
        $this->variable($task->fields['begin'])->isEqualTo(null);
        $this->variable($task->fields['end'])->isEqualTo(null);

        // Schedule the task
        $this->boolean($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticketId,
            'users_id_tech'      => $uid,
            'plan'               => [
                'begin'        => $date_begin_string,
                'end'          => $date_end_string,
            ]
        ]))->isTrue();

        // Check that the task duration is correctly updated
        $this->integer($task->fields['actiontime'])->isEqualTo(172800);
        $this->string($task->fields['begin'])->isEqualTo($date_begin_string);
        $this->string($task->fields['end'])->isEqualTo($date_end_string);

        // Update the task duration with actiontime
        $this->boolean($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticketId,
            'users_id_tech'      => $uid,
            'actiontime'         => 7200,
            'plan'               => [
                'begin'        => $date_begin_string,
                'end'          => $date_end_string,
            ]
        ]))->isTrue();

        // Check that the task duration is correctly updated
        $this->integer($task->fields['actiontime'])->isEqualTo(7200);
        $this->string($task->fields['begin'])->isEqualTo($date_begin_string);
        $this->string($task->fields['end'])->isEqualTo($date_begin->add(new \DateInterval('PT2H'))->format('Y-m-d H:i:s'));
    }
}
