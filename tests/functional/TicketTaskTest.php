<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Search\SearchEngine;
use Override;
use TicketTask;

final class TicketTaskTest extends CommonITILTaskTestCase
{
    #[Override]
    protected static function getTaskClass(): string
    {
        return TicketTask::class;
    }

    /**
     * Create a new ticket
     *
     * @param boolean $as_object Return Ticket object or its id
     *
     * @return integer|\Ticket
     */
    private function getNewTicket($as_object = false)
    {
        //create reference ticket
        $ticket = new \Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => '',
                'entities_id'        => getItemByTypeName('Entity', '_test_root_entity', true),
                '_users_id_assign'   => getItemByTypeName('User', 'tech', true),
            ])
        );

        $this->assertFalse($ticket->isNewItem());
        $tid = (int) $ticket->fields['id'];

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
        $task = new TicketTask();
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
                'itemtype'    => TicketTask::class,
                'users_id'    => $uid,
                'field'       => 'begin', //default
            ],
        ]);
        $this->assertGreaterThan(0, $task_id);

        //load planning schedule with recall
        $recall = new \PlanningRecall();

        //calcul 'when'
        $when = date("Y-m-d H:i:s", strtotime($task->fields['begin']) - 14400);
        $this->assertTrue(
            $recall->getFromDBByCrit([
                'before_time'   => '14400', //recall 4 hours
                'itemtype'     => TicketTask::class,
                'items_id'     => $task_id,
                'users_id'     => $uid,
                'when'         => $when,
            ])
        );

        //create one task with schedule and without recall
        $date_begin = new \DateTime();
        $date_begin->add(new \DateInterval('P1M'));
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');

        $date_begin = new \DateTime();
        $date_end->add(new \DateInterval('P1M2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $task = new TicketTask();
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
                'itemtype'    => TicketTask::class,
                'users_id'    => $uid,
                'field'       => 'begin', //default
            ],
        ]);
        $this->assertGreaterThan(0, $task_id);

        //load schedule //which return false (not exist yet without recall)
        $recall = new \PlanningRecall();
        $this->assertFalse(
            $recall->getFromDBByCrit([
                'itemtype'   => TicketTask::class,
                'items_id'  => $task_id,
                'users_id'  => $uid,
            ])
        );

        //update task schedule with recall
        $this->assertTrue(
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
                    'itemtype'    => TicketTask::class,
                    'users_id'    => $uid,
                    'field'       => 'begin', //default
                ],
            ])
        );

        //load planning recall
        $recall = new \PlanningRecall();

        //calcul when
        $when = date("Y-m-d H:i:s", strtotime($task->fields['begin']) - 900);
        $this->assertTrue(
            $recall->getFromDBByCrit(['before_time'  => '900',
                'itemtype'    => TicketTask::class,
                'items_id'    => $task_id,
                'users_id'    => $uid,
                'when'        => $when,
            ])
        );
    }

    public function testGetTaskList()
    {

        $this->login();
        $ticketId = $this->getNewTicket();
        $uid = getItemByTypeName('User', TU_USER, true);

        $tasksstates = [
            \Planning::TODO,
            \Planning::TODO,
            \Planning::INFO,
        ];
        //create few tasks
        $task = new TicketTask();
        foreach ($tasksstates as $taskstate) {
            $this->assertGreaterThan(
                0,
                $task->add([
                    'content'      => sprintf('Task with "%s" state', $taskstate),
                    'state'        => $taskstate,
                    'tickets_id'   => $ticketId,
                    'users_id_tech' => $uid,
                ])
            );
        }

        $iterator = $task::getTaskList('todo', false);
        //we create two ones plus the one in bootstrap data
        $this->assertCount(3, $iterator);

        $iterator = $task::getTaskList('todo', true);
        $this->assertCount(0, $iterator);

        $_SESSION['glpigroups'] = [42, 1337];
        $iterator = $task::getTaskList('todo', true);
        //no task for those groups
        $this->assertCount(0, $iterator);
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
            \Planning::INFO,
        ];
        //create few tasks
        $task = new TicketTask();
        foreach ($tasksstates as $taskstate) {
            $this->assertGreaterThan(
                0,
                $task->add([
                    'content'      => sprintf('Task with "%s" state', $taskstate),
                    'state'        => $taskstate,
                    'tickets_id'   => $ticketId,
                    'users_id_tech' => $uid,
                ])
            );
        }

        ob_start();
        TicketTask::showCentralList(0, 'todo', false);
        $output = ob_get_clean();
        $this->assertStringContainsString("Ticket tasks to do <span class='primary-bg primary-fg count'>4</span>", $output);
        $this->assertMatchesRegularExpression("/a href='\/front\/ticket.form.php\?id=\d+[^']+'>/", $output);
        $this->assertSame(
            4,
            preg_match_all(
                "/a href='\/front\/ticket.form.php\?id=\d*[^']+'>/",
                $output,
            )
        );

        ob_start();
        $_SESSION['glpidisplay_count_on_home'] = 2;
        TicketTask::showCentralList(0, 'todo', false);
        unset($_SESSION['glpidisplay_count_on_home']);
        $output = ob_get_clean();

        $this->assertStringContainsString(
            "Ticket tasks to do <span class='primary-bg primary-fg count'>2 on 4</span>",
            $output
        );
        $this->assertSame(
            2,
            preg_match_all(
                "/a href='\/front\/ticket.form.php\?id=\d*[^']+'>/",
                $output
            )
        );
    }

    public function testPlanningConflict()
    {
        $this->login();

        $user = getItemByTypeName('User', 'tech');
        $users_id = (int) $user->fields['id'];

        $ticket = $this->getNewTicket(true);
        $tid = $ticket->fields['id'];

        $ttask = new TicketTask();
        $this->assertGreaterThan(
            0,
            (int) $ttask->add([
                'name'               => 'first test, whole period',
                'content'            => 'first test, whole period',
                'tickets_id'         => $tid,
                'plan'               => [
                    'begin'  => '2019-08-10',
                    'end'    => '2019-08-20',
                ],
                'users_id_tech'      => $users_id,
                'tasktemplates_id'   => 0,
            ])
        );
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $this->assertGreaterThan(
            0,
            (int) $ttask->add([
                'name'               => 'test, subperiod',
                'content'            => 'test, subperiod',
                'tickets_id'         => $tid,
                'plan'               => [
                    'begin'   => '2019-08-13',
                    'end'     => '2019-08-14',
                ],
                'users_id_tech'      => $users_id,
                'tasktemplates_id'   => 0,
            ])
        );

        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Ticket task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='"
            . $ticket->getFormURLWithID($tid) . "&amp;forcetab=TicketTask$1'>ticket title</a><br/>",
            ]
        );
        $this->assertGreaterThan(0, $tid);

        //add another task to be updated
        $this->assertGreaterThan(
            0,
            (int) $ttask->add([
                'name'               => 'first test, whole period',
                'content'            => 'first test, whole period',
                'tickets_id'         => $tid,
                'plan'               => [
                    'begin'  => '2018-08-10',
                    'end'    => '2018-08-20',
                ],
                'users_id_tech'      => $users_id,
                'tasktemplates_id'   => 0,
            ])
        );
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $this->assertTrue($ttask->getFromDB($ttask->fields['id']));

        $this->assertTrue(
            $ttask->update([
                'id'           => $ttask->fields['id'],
                'tickets_id'   => $tid,
                'plan'               => [
                    'begin'  => str_replace('2018', '2019', $ttask->fields['begin']),
                    'end'    => str_replace('2018', '2019', $ttask->fields['end']),
                ],
                'users_id_tech'      => $users_id,
            ])
        );

        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Ticket task: from 2019-08-10 00:00 to 2019-08-20 00:00:<br/><a href='"
            . $ticket->getFormURLWithID($tid) . "&amp;forcetab=TicketTask$1'>ticket title</a><br/>- Ticket task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='" . $ticket
            ->getFormURLWithID($tid) . "&amp;forcetab=TicketTask$1'>ticket title</a><br/>",
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
        $this->assertGreaterThan(0, $templates_id);
        $task = new TicketTask();
        $tasks_id = $task->add([
            '_tasktemplates_id'  => $templates_id,
            'itemtype'           => 'Ticket',
            'tickets_id'         => $ticket->fields['id'],
        ]);
        $this->assertGreaterThan(0, $tasks_id);

        $this->assertEquals('<p>test template</p>', $task->fields['content']);
        $this->assertEquals(getItemByTypeName('User', TU_USER, true), $task->fields['users_id_tech']);
        $this->assertEquals(\Planning::DONE, $task->fields['state']);
        $this->assertEquals(1, $task->fields['is_private']);

        $tasks_id = $task->add([
            '_tasktemplates_id'  => $templates_id,
            'itemtype'           => 'Ticket',
            'tickets_id'         => $ticket->fields['id'],
            'state'              => \Planning::TODO,
            'is_private'         => 0,
        ]);
        $this->assertGreaterThan(0, $tasks_id);

        $this->assertEquals('<p>test template</p>', $task->fields['content']);
        $this->assertEquals(getItemByTypeName('User', TU_USER, true), $task->fields['users_id_tech']);
        $this->assertEquals(\Planning::TODO, $task->fields['state']);
        $this->assertEquals(0, $task->fields['is_private']);
    }

    /**
     * Test that the ticket status is correctly updated when the task is scheduled and then unscheduled.
     *
     * @return void
     */
    public function testDePlanifiedUpdateParentStatus()
    {
        $this->login();
        $ticket_id = $this->getNewTicket();
        $task = new TicketTask();

        $uid = getItemByTypeName('User', TU_USER, true);
        $date_begin = new \DateTime(); // ==> now
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +2days
        $date_end->add(new \DateInterval('P2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $task_id = $task->add([
            'pending'            => 0,
            'tickets_id'         => $ticket_id,
            'content'            => "Planned Task",
            'state'              => \Planning::TODO,
            'users_id_tech'      => $uid,
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]);
        $this->assertGreaterThan(0, $task_id);

        $this->assertEquals(\Ticket::PLANNED, \Ticket::getById($ticket_id)->fields['status']);

        $this->assertTrue($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticket_id,
            'content'            => "De-planned Task",
            'begin'              => null,
            'end'                => null,
        ]));

        $this->assertEquals(\Ticket::ASSIGNED, \Ticket::getById($ticket_id)->fields['status']);

        $this->assertTrue($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticket_id,
            'content'            => "Planned Task",
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]));



        $this->assertEquals(\Ticket::PLANNED, \Ticket::getById($ticket_id)->fields['status']);

        $ticket = new \Ticket();
        $ticket_users = new \Ticket_User();

        // remove assigned user from ticket
        $this->assertTrue($ticket_users->deleteByCriteria([
            'tickets_id' => $ticket_id,
            'type'       => \CommonITILActor::ASSIGN,
        ]));

        $this->assertTrue($ticket->getFromDB($ticket_id));

        $this->assertEquals(0, $ticket->countUsers(\CommonITILActor::ASSIGN));

        $this->assertEquals(\Ticket::INCOMING, \Ticket::getById($ticket_id)->fields['status']);

        $this->assertTrue($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticket_id,
            'content'            => "De-planned Task",
            'begin'              => null,
            'end'                => null,
        ]));

        $this->assertEquals(\Ticket::INCOMING, \Ticket::getById($ticket_id)->fields['status']);

        $this->assertTrue($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticket_id,
            'content'            => "Planned Task",
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]));

        $this->assertEquals(\Ticket::PLANNED, \Ticket::getById($ticket_id)->fields['status']);

        $this->assertTrue($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticket_id,
            'content'            => "De-planned Task",
            'begin'              => null,
            'end'                => null,
        ]));

        $this->assertEquals(\Ticket::INCOMING, \Ticket::getById($ticket_id)->fields['status']);

        // Check that adding a followup on a ticket that has the CommonITILObject::PLANNED status will not fail.
        $this->assertTrue($task->update([
            'id'                 => $task_id,
            'tickets_id'         => $ticket_id,
            'content'            => "Planned Task",
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]));

        $this->assertEquals(\Ticket::PLANNED, \Ticket::getById($ticket_id)->fields['status']);

        $followup = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            $followup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $ticket_id,
                'content'    => 'Followup on planned ticket',
            ])
        );

        $this->assertEquals(\Ticket::PLANNED, \Ticket::getById($ticket_id)->fields['status']);
    }

    public function testUpdateParentStatus()
    {
        $this->login();
        $ticket_id = $this->getNewTicket();
        $task = new TicketTask();

        $uid = getItemByTypeName('User', TU_USER, true);
        $date_begin = new \DateTime(); // ==> now
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +2days
        $date_end->add(new \DateInterval('P2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $this->assertGreaterThan(
            0,
            $task->add([
                'pending'            => 1,
                'tickets_id'         => $ticket_id,
                'content'            => "Task with schedule",
                'state'              => \Planning::TODO,
                'users_id_tech'      => $uid,
                'begin'              => $date_begin_string,
                'end'                => $date_end_string,
            ])
        );

        $this->assertEquals(
            \Ticket::WAITING,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $date_begin = new \DateTime(); // ==> +3days
        $date_begin->add(new \DateInterval('P3D'));
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +4days
        $date_end->add(new \DateInterval('P4D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $this->assertGreaterThan(
            0,
            $task->add([
                'pending'            => 0,
                'tickets_id'         => $ticket_id,
                'content'            => "Task with schedule",
                'state'              => \Planning::TODO,
                'users_id_tech'      => $uid,
                'begin'              => $date_begin_string,
                'end'                => $date_end_string,
            ])
        );

        $this->assertEquals(
            \Ticket::PLANNED,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $date_begin = new \DateTime(); // ==> +5days
        $date_begin->add(new \DateInterval('P5D'));
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +6days
        $date_end->add(new \DateInterval('P6D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $this->assertGreaterThan(
            0,
            $task->add([
                'pending'            => 1,
                'tickets_id'         => $ticket_id,
                'content'            => "Task with schedule",
                'state'              => \Planning::TODO,
                'users_id_tech'      => $uid,
                'begin'              => $date_begin_string,
                'end'                => $date_end_string,
            ])
        );

        $this->assertEquals(
            \Ticket::WAITING,
            \Ticket::getById($ticket_id)->fields['status']
        );
    }

    /**
     * Check that the parent ticket status is updated when tasks are added or updated
     *
     * @return void
     */
    public function testUpdateParentStatusOnTaskUpdate(): void
    {
        $this->login();
        $ticket_id = $this->getNewTicket();

        $uid = getItemByTypeName('User', TU_USER, true);
        $date_begin = new \DateTime(); // ==> now
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +2days
        $date_end->add(new \DateInterval('P2D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $task1 = new TicketTask();
        $task1_id = $task1->add([
            'tickets_id'         => $ticket_id,
            'content'            => "Task with schedule",
            'state'              => \Planning::TODO,
            'users_id_tech'      => $uid,
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]);
        $this->assertGreaterThan(
            0,
            $task1_id
        );

        $this->assertEquals(
            \Ticket::PLANNED,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $date_begin = new \DateTime(); // ==> +3days
        $date_begin->add(new \DateInterval('P3D'));
        $date_begin_string = $date_begin->format('Y-m-d H:i:s');
        $date_end = new \DateTime(); // ==> +4days
        $date_end->add(new \DateInterval('P4D'));
        $date_end_string = $date_end->format('Y-m-d H:i:s');

        $task2 = new TicketTask();
        $task2_id = $task2->add([
            'tickets_id'         => $ticket_id,
            'content'            => "Task with schedule",
            'state'              => \Planning::TODO,
            'users_id_tech'      => $uid,
            'begin'              => $date_begin_string,
            'end'                => $date_end_string,
        ]);

        $this->assertGreaterThan(
            0,
            $task2_id
        );

        $this->assertEquals(
            \Ticket::PLANNED,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $this->assertTrue(
            $task1->update([
                'id'        => $task1_id,
                'state'     => \Planning::DONE,
            ])
        );

        $this->assertEquals(
            \Ticket::PLANNED,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $this->assertTrue(
            $task2->update([
                'id'        => $task2_id,
                'state'     => \Planning::DONE,
            ])
        );

        $this->assertEquals(
            \Ticket::ASSIGNED,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $this->assertTrue(
            \Ticket::getById($ticket_id)->update([
                'id'        => $ticket_id,
                'status'    => \Ticket::WAITING,
            ])
        );

        $this->assertEquals(
            \Ticket::WAITING,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $this->assertTrue(
            $task1->update([
                'id'        => $task1_id,
                'state'     => \Planning::TODO,
            ])
        );

        $this->assertEquals(
            \Ticket::WAITING,
            \Ticket::getById($ticket_id)->fields['status']
        );

        $this->assertTrue(
            $task2->update([
                'id'        => $task2_id,
                'state'     => \Planning::TODO,
            ])
        );

        $this->assertEquals(
            \Ticket::WAITING,
            \Ticket::getById($ticket_id)->fields['status']
        );
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
        $task = new TicketTask();
        $task_id = $task->add([
            'state'              => \Planning::TODO,
            'tickets_id'         => $ticketId,
            'tasktemplates_id'   => '0',
            'taskcategories_id'  => '0',
            'content'            => "Task with schedule and recall",
            'users_id_tech'      => $uid,
            'actiontime'         => 3600,
        ]);
        $this->assertGreaterThan(0, $task_id);

        // Check that the task duration is correctly updated
        $this->assertEquals(3600, $task->fields['actiontime']);
        $this->assertNull($task->fields['begin']);
        $this->assertNull($task->fields['end']);

        // Schedule the task
        $this->assertTrue(
            $task->update([
                'id'                 => $task_id,
                'tickets_id'         => $ticketId,
                'users_id_tech'      => $uid,
                'plan'               => [
                    'begin'        => $date_begin_string,
                    'end'          => $date_end_string,
                ],
            ])
        );

        // Check that the task duration is correctly updated
        $this->assertEquals(172800, $task->fields['actiontime']);
        $this->assertEquals($date_begin_string, $task->fields['begin']);
        $this->assertEquals($date_end_string, $task->fields['end']);

        // Update the task duration with actiontime
        $this->assertTrue(
            $task->update([
                'id'                 => $task_id,
                'tickets_id'         => $ticketId,
                'users_id_tech'      => $uid,
                'actiontime'         => 7200,
                'plan'               => [
                    'begin'        => $date_begin_string,
                    'end'          => $date_end_string,
                ],
            ])
        );

        // Check that the task duration is correctly updated
        $this->assertEquals(7200, $task->fields['actiontime']);
        $this->assertEquals($date_begin_string, $task->fields['begin']);
        $this->assertEquals(
            $date_begin->add(new \DateInterval('PT2H'))->format('Y-m-d H:i:s'),
            $task->fields['end']
        );
    }

    public function testParentMetaSearchOptions()
    {
        $this->login();
        $ticket = $this->getNewTicket(true);
        $followup = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            $followup->add([
                'itemtype' => 'Ticket',
                'items_id' => $ticket->fields['id'],
                'content'  => 'Test followup',
            ])
        );

        $criteria = [
            [
                'link' => 'AND',
                'itemtype' => 'Ticket',
                'meta' => true,
                'field' => 1, //Title
                'searchtype' => 'contains',
                'value' => 'ticket title',
            ],
        ];
        $data = SearchEngine::getData('ITILFollowup', [
            'criteria' => $criteria,
        ]);
        $this->assertEquals(1, $data['data']['totalcount']);
        $this->assertEquals('ticket title', $data['data']['rows'][0]['Ticket_1'][0]['name']);
    }
}
