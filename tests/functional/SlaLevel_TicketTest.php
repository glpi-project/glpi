<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use CommonITILObject;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\SLMTrait;
use LevelAgreementLevel;
use OlaLevel;
use OlaLevelAction;
use PHPUnit\Framework\Attributes\DataProvider;
use SLA;
use SlaLevel;
use SlaLevel_Ticket;
use SlaLevelAction;
use SLM;
use Ticket;

class SlaLevel_TicketTest extends DbTestCase
{
    use SLMTrait;

    /**
     * When a ticket is created
     *  - in a 'reopenning status' (@see \CommonITILObject::getReopenableStatusArray())
     *  - with an associated SLA having an escalation level delay matching (delay for sla = delay for level)
     * The escalation level should not be triggered/active
     *
     * sla delay : 1 hour, escalation delay : 60 minutes before sla delay : so it's now
     */
    public function testSlaLevelNotActivatedWhenTicketInReopenableStatus()
    {
        $this->login();

        // --- arrange ---
        // create SLM with SLA and level
        ['sla' => $sla] = $this->createSLA(['definition_time' => 'hour', 'number_time' => 1]);

        $sla_level = $this->createItem(
            SlaLevel::class,
            [
                'execution_time' => -3601,
                'slas_id'        => $sla->getID(),
            ] + $this->getMinimalCreationInput(SlaLevel::class)
        );

        // create ticket in reopenable status (SOLVED)
        $ticket = $this->createItem(
            Ticket::class,
            [
                'status'         => CommonITILObject::SOLVED,
                'slas_id_tto'    => $sla->getID(),
            ]
            + $this->getMinimalCreationInput(Ticket::class)
        );

        // --- assert ---
        // verify that SlaLevel_Ticket was NOT created for this ticket in reopenable status
        $sla_level_tickets = new SlaLevel_Ticket();
        $result = $sla_level_tickets->find([
            'tickets_id'   => $ticket->getID(),
            'slalevels_id' => $sla_level->getID(),
        ]);

        $this->assertEmpty($result, 'no escalation level should be active : db data found :' . var_export($result, true));
    }

    /**
     * Create a SLM SLA TTO
     * to update ticket itilcategories_id if ticket is linked to a specific project
     * when time to own is exceeded
     */
    public function testProjectCriteria()
    {
        $this->login();

        //create project
        $project = new \Project();
        $project_input = ['name' => 'Project'];
        $project_id = $project->add(['name' => 'Project']);
        $this->checkInput($project, $project_id, $project_input);

        //create a ticket category
        $category    = new \ITILCategory();
        $category_id = $category->add([
            'name'                        => 'my itil category',
            'is_incident'                 => true,
            'is_request'                  => true,
        ]);
        $this->assertFalse($category->isNewItem());

        $slm    = new SLM();
        $slm_id = $slm->add($slm_in = [
            'name'         => __METHOD__,
            'comment'      => $this->getUniqueString(),
            'calendars_id' => 0, //24:24 7j/7j
        ]);
        $this->checkInput($slm, $slm_id, $slm_in);

        // prepare sla/ola inputs
        $sla1_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => SLM::TTO,
            'number_time'     => 1,
            'definition_time' => 'hour',
        ];

        // add sla (TTO)
        $sla    = new SLA();
        $sla1_id = $sla->add($sla1_in);
        $this->checkInput($sla, $sla1_id, $sla1_in);

        // prepare levels input for sla
        $slal1_in = [
            'name'           => __METHOD__,
            'execution_time' => 0, //TIME TO OWN
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id,
        ];

        // add levels
        $slal = new SlaLevel();
        $slal1_id = $slal->add($slal1_in);
        $this->checkInput($slal, $slal1_id, $slal1_in);


        // add criteria/actions
        $scrit_in = [
            'slalevels_id' => $slal1_id,
            'criteria'     => 'assign_project',
            'condition'    => 0, //is
            'pattern'      => $project_id,
        ];


        $saction_in = [
            'slalevels_id' => $slal1_id,
            'action_type'  => 'assign',
            'field'        => 'itilcategories_id',
            'value'        => $category_id,
        ];

        $scrit    = new \SlaLevelCriteria();
        $saction  = new SlaLevelAction();

        $scrit_id   = $scrit->add($scrit_in);
        $saction_id = $saction->add($saction_in);

        $this->checkInput($scrit, $scrit_id, $scrit_in);
        $this->checkInput($saction, $saction_id, $saction_in);

        // test create ticket
        $ticket = new Ticket();
        //$start_date = date("Y-m-d H:i:s", time() - 2 * HOUR_TIMESTAMP);
        $tickets_id = $ticket->add($ticket_input = [
            //'date'    => $start_date,
            'name'    => __METHOD__,
            'content' => __METHOD__,
            'slas_id_tto' => $sla1_id,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals($sla1_id, (int) $ticket->getField('slas_id_tto'));
        $this->assertEquals(0, (int) $ticket->getField('itilcategories_id'));
        $this->assertEquals(CommonITILObject::INCOMING, (int) $ticket->getField('status'));

        //add Project to ticket
        $itil_project = new \Itil_Project();
        $itil_project_input = ['itemtype' => 'Ticket', 'items_id' => $tickets_id, 'projects_id' => $project_id];
        $itil_project_id = $itil_project->add($itil_project_input);
        $this->checkInput($itil_project, $itil_project_id, $itil_project_input);

        //get SlaLevel_Ticket related to this ticket and SLM
        $slalevels_tickets = new SlaLevel_Ticket();
        $this->assertTrue($slalevels_tickets->getFromDBByCrit([
            'tickets_id' => $tickets_id, 'slalevels_id' => $slal1_id,
        ]));

        //fake glpi_slalevels_tickets.date to run crontask
        $this->assertTrue($slalevels_tickets->update([
            'id' => $slalevels_tickets->fields['id'], 'date' => date("Y-m-d H:i:s", time() - 2 * HOUR_TIMESTAMP),
        ]));

        //run automatic action
        //run crontask
        $task = new \CronTask();
        $this->assertSame(1, SlaLevel_Ticket::cronSlaTicket($task));

        //check ticket category change
        //reload ticket
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals($category_id, $ticket->fields['itilcategories_id']);
    }


    /**
     * Test action of a sla level that add a followup to a ticket
     * and do not compute take into account delay.
     */
    public function testTakeIntoAccoutAndFollupAction()
    {
        $this->login();

        // Create followup template
        $followuptemplate = new \ITILFollowupTemplate();
        $templateid = $followuptemplate->add($templateinput = [
            'name' => 'followuptemplate_' . __FUNCTION__,
            'content' => 'Test',
        ]);
        $this->checkInput($followuptemplate, $templateid, $templateinput);

        // create SLM
        $slm    = new SLM();
        $slm_id = $slm->add($slm_in = [
            'name'         => __METHOD__,
            'comment'      => $this->getUniqueString(),
            'calendars_id' => 0, //24:24 7j/7j
        ]);
        $this->checkInput($slm, $slm_id, $slm_in);

        // prepare sla/ola inputs
        $sla1_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => SLM::TTO,
            'number_time'     => 1,
            'definition_time' => 'hour',
        ];

        // add sla (TTO)
        $sla    = new SLA();
        $sla1_id = $sla->add($sla1_in);
        $this->checkInput($sla, $sla1_id, $sla1_in);

        // prepare levels input for sla
        $slal1_in = [
            'name'           => __METHOD__,
            'execution_time' => 0, //TIME TO OWN
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id,
        ];

        // add levels
        $slal = new SlaLevel();
        $slal1_id = $slal->add($slal1_in);
        $this->checkInput($slal, $slal1_id, $slal1_in);


        // add criteria/actions
        $scrit_in = [
            'slalevels_id' => $slal1_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => 'test',
        ];

        $saction1_in = [
            'slalevels_id' => $slal1_id,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => $templateid,
        ];

        $saction2_in = [
            'slalevels_id' => $slal1_id,
            'action_type' => 'takeintoaccount_delay_stat',
            'field'       => 'do_not_compute',
            'value'       => true,
        ];

        $scrit    = new \SlaLevelCriteria();
        $saction  = new SlaLevelAction();

        $scrit_id   = $scrit->add($scrit_in);
        $saction_id_1 = $saction->add($saction1_in);
        $saction_id_2 = $saction->add($saction2_in);

        $this->checkInput($scrit, $scrit_id, $scrit_in);
        $this->checkInput($saction, $saction_id_1, $saction1_in);
        $this->checkInput($saction, $saction_id_2, $saction2_in);

        // create ticket
        $ticket = new Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => 'test ticket ' . __FUNCTION__,
            'content' => __METHOD__,
            'slas_id_tto' => $sla1_id,
        ]);

        // check ticket data
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals($sla1_id, (int) $ticket->getField('slas_id_tto'));
        $this->assertEquals(0, (int) $ticket->getField('takeintoaccount_delay_stat'));
        $this->assertEquals(CommonITILObject::INCOMING, (int) $ticket->getField('status'));

        //get SlaLevel_Ticket related to this ticket and SLM
        $slalevels_tickets = new SlaLevel_Ticket();
        $this->assertTrue($slalevels_tickets->getFromDBByCrit([
            'tickets_id' => $tickets_id, 'slalevels_id' => $slal1_id,
        ]));

        //fake glpi_slalevels_tickets.date to run crontask
        $this->assertTrue($slalevels_tickets->update([
            'id' => $slalevels_tickets->fields['id'], 'date' => date("Y-m-d H:i:s", time() - 2 * HOUR_TIMESTAMP),
        ]));

        //run automatic action
        //run crontask
        $task = new \CronTask();
        $this->assertSame(1, SlaLevel_Ticket::cronSlaTicket($task));

        //reload ticket
        $this->assertTrue($ticket->getFromDB($tickets_id)); // reload ticket
        $this->assertEquals($sla1_id, (int) $ticket->getField('slas_id_tto')); // check SLA
        $this->assertEquals(0, (int) $ticket->getField('takeintoaccount_delay_stat')); // check takeintoaccount_delay_stat always 0
        $this->assertEquals(CommonITILObject::INCOMING, (int) $ticket->getField('status')); // check status always incoming

        // followup well added
        $this->assertEquals(
            1,
            countElementsInTable(
                \ITILFollowup::getTable(),
                ['itemtype' => Ticket::getType(), 'items_id' => $tickets_id]
            )
        );
    }

    /**
     * addLevelToDo must not insert a duplicate (tickets_id, slalevels_id) row
     * when called twice for the same ticket and level.
     */
    public function testAddLevelToDoNoDuplicate(): void
    {
        $this->login();

        ['sla' => $sla] = $this->createSLA(['definition_time' => 'hour', 'number_time' => 4], SLM::TTR);

        $sla_level = $this->createItem(SlaLevel::class, [
            'slas_id'        => $sla->getID(),
            'execution_time' => -HOUR_TIMESTAMP,
        ] + $this->getMinimalCreationInput(SlaLevel::class));

        $ticket = $this->createItem(Ticket::class, [
            'slas_id_ttr'      => $sla->getID(),
            'slalevels_id_ttr' => $sla_level->getID(),
        ] + $this->getMinimalCreationInput(Ticket::class));

        $sla->getFromDB($sla->getID());

        $sla->addLevelToDo($ticket, $sla_level->getID());
        $sla->addLevelToDo($ticket, $sla_level->getID());

        $this->assertSame(1, countElementsInTable(SlaLevel_Ticket::getTable(), [
            'tickets_id'   => $ticket->getID(),
            'slalevels_id' => $sla_level->getID(),
        ]));
    }

    /**
     * deleteLevelsToDo must remove all SlaLevel_Ticket rows for the ticket.
     */
    public function testDeleteLevelsToDo(): void
    {
        $this->login();

        ['sla' => $sla] = $this->createSLA(['definition_time' => 'hour', 'number_time' => 4], SLM::TTR);

        $sla_level = $this->createItem(SlaLevel::class, [
            'slas_id'        => $sla->getID(),
            'execution_time' => -HOUR_TIMESTAMP,
        ] + $this->getMinimalCreationInput(SlaLevel::class));

        $ticket = $this->createItem(Ticket::class, [
            'slas_id_ttr'      => $sla->getID(),
            'slalevels_id_ttr' => $sla_level->getID(),
        ] + $this->getMinimalCreationInput(Ticket::class));

        $sla->getFromDB($sla->getID());
        $sla->addLevelToDo($ticket, $sla_level->getID());

        $this->assertSame(1, countElementsInTable(SlaLevel_Ticket::getTable(), [
            'tickets_id' => $ticket->getID(),
        ]));

        SLA::deleteLevelsToDo($ticket);

        $this->assertSame(0, countElementsInTable(SlaLevel_Ticket::getTable(), [
            'tickets_id' => $ticket->getID(),
        ]));
    }

    public static function recallLevelProvider(): iterable
    {
        yield 'SLA' => ['level_class' => SlaLevel::class, 'action_class' => SlaLevelAction::class, 'event' => 'recall'];
        yield 'OLA' => ['level_class' => OlaLevel::class, 'action_class' => OlaLevelAction::class, 'event' => 'recall_ola'];
    }

    /**
     * Create a TTR level agreement with one immediate level.
     *
     * @param class-string<SlaLevel|OlaLevel> $level_class
     *
     * @return array{level: SlaLevel|OlaLevel, ticket_input: array<string, mixed>}
     */
    private function createTtrLevel(string $level_class, string $name, int $entities_id): array
    {
        if ($level_class === SlaLevel::class) {
            $la = $this->createSLA(['entities_id' => $entities_id], SLM::TTR)['sla'];
            $ticket_input = ['slas_id_ttr' => $la->getID()];
        } else {
            $la = $this->createOLA(['entities_id' => $entities_id], SLM::TTR)['ola'];
            $ticket_input = ['_la_update' => true, '_olas_id' => [$la->getID()]];
        }
        $level = $this->createItem($level_class, [
            'name'                     => $name,
            'execution_time'           => 0,
            'is_active'                => 1,
            'match'                    => 'AND',
            $la::getForeignKeyField()  => $la->getID(),
        ]);

        return ['level' => $level, 'ticket_input' => $ticket_input];
    }

    /**
     * @param class-string<SlaLevel|OlaLevel> $level_class
     * @param class-string<SlaLevelAction|OlaLevelAction> $action_class
     */
    #[DataProvider('recallLevelProvider')]
    public function testRecallActionSendsOnlyItsNotification(string $level_class, string $action_class, string $event): void
    {
        global $CFG_GLPI;

        $this->login();

        $entities_id = $this->getTestRootEntity(true);

        $users_ids = [];
        foreach (['assigned', 'other'] as $name) {
            $user = $this->createItem(\User::class, [
                'name'          => __FUNCTION__ . $name,
                '_profiles_id'  => getItemByTypeName('Profile', 'Technician', true),
                '_entities_id'  => $entities_id,
                '_is_recursive' => 1,
            ]);
            $this->createItem(\UserEmail::class, [
                'users_id'   => $user->getID(),
                'email'      => $name . '@localhost',
                'is_default' => 1,
            ]);
            $users_ids[$name] = $user->getID();
        }
        $group = $this->createItem(\Group::class, ['name' => __FUNCTION__, 'entities_id' => $entities_id]);
        $this->createItem(\Group_User::class, [
            'groups_id' => $group->getID(),
            'users_id'  => $users_ids['other'],
        ]);

        // Two notifications on the SLA reminder event, with distinct recipients as a recipient is notified once per event
        $notifications_ids = [];
        $templates_ids = [];
        $targets = [
            'sent'     => [\Notification::USER_TYPE, \Notification::ASSIGN_TECH],
            'not_sent' => [\Notification::GROUP_TYPE, $group->getID()],
        ];
        foreach ($targets as $name => [$target_type, $target_items_id]) {
            $notification = $this->createItem(\Notification::class, [
                'name'         => __FUNCTION__ . $name,
                'entities_id'  => $entities_id,
                'is_recursive' => 1,
                'is_active'    => 1,
                'itemtype'     => Ticket::class,
                'event'        => $event,
            ]);
            $template = $this->createItem(\NotificationTemplate::class, [
                'name'     => __FUNCTION__ . $name,
                'itemtype' => Ticket::class,
            ]);
            $this->createItem(\NotificationTemplateTranslation::class, [
                'notificationtemplates_id' => $template->getID(),
                'language'                 => '',
                'subject'                  => $name,
                'content_text'             => $name,
                'content_html'             => $name,
            ]);
            $this->createItem(\Notification_NotificationTemplate::class, [
                'notifications_id'         => $notification->getID(),
                'mode'                     => \Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $template->getID(),
            ]);
            $this->createItem(\NotificationTarget::class, [
                'notifications_id' => $notification->getID(),
                'type'             => $target_type,
                'items_id'         => $target_items_id,
            ]);
            $notifications_ids[$name] = $notification->getID();
            $templates_ids[$name] = $template->getID();
        }

        ['level' => $level, 'ticket_input' => $ticket_input] = $this->createTtrLevel($level_class, __FUNCTION__, $entities_id);
        $action_field = $event . LevelAgreementLevel::NOTIFICATION_RECALL_SUFFIX;
        $this->assertArrayHasKey($action_field, $level->getActions());
        $this->createItem($action_class, [
            $level::getForeignKeyField() => $level->getID(),
            'action_type'                => 'send',
            'field'                      => $action_field,
            'value'                      => $notifications_ids['sent'],
        ]);

        $ticket = $this->createItem(Ticket::class, [
            'name'             => __FUNCTION__,
            'content'          => __FUNCTION__,
            'entities_id'      => $entities_id,
            '_users_id_assign' => $users_ids['assigned'],
        ] + $ticket_input);

        $CFG_GLPI['use_notifications']     = 1;
        $CFG_GLPI['notifications_mailing'] = 1;
        if ($level_class === SlaLevel::class) {
            $level_ticket = new SlaLevel_Ticket();
            $this->assertTrue($level_ticket->getFromDBByCrit([
                'tickets_id'   => $ticket->getID(),
                'slalevels_id' => $level->getID(),
            ]));
            $this->updateItem(SlaLevel_Ticket::class, $level_ticket->getID(), [
                'date' => date('Y-m-d H:i:s', time() - HOUR_TIMESTAMP),
            ]);
            $this->runSlaCron();
        } else {
            // OLA levels todo are rebuilt by the cron from the due time
            $this->setCurrentTime(date('Y-m-d H:i:s', strtotime('+1 month')));
            $this->runOlaCron();
        }

        $queued = (new \QueuedNotification())->find([
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
            'event'    => $event,
        ]);
        $this->assertSame([$templates_ids['sent']], array_values(array_unique(array_column($queued, 'notificationtemplates_id'))));
    }

    public function testSlaStatusAction(): void
    {
        $this->login();

        $entities_id = $this->getTestRootEntity(true);
        $status = $this->createItem(\SlaStatus::class, ['name' => __FUNCTION__]);

        ['sla' => $sla] = $this->createSLA(['entities_id' => $entities_id], SLM::TTR);
        $level = $this->createItem(SlaLevel::class, [
            'name'           => __FUNCTION__,
            'execution_time' => 0,
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla->getID(),
        ]);
        $this->assertArrayHasKey('slastatuses_id', $level->getActions());
        $this->createItem(SlaLevelAction::class, [
            'slalevels_id' => $level->getID(),
            'action_type'  => 'assign',
            'field'        => 'slastatuses_id',
            'value'        => $status->getID(),
        ]);

        $tickets = [];
        foreach (['with_sla' => $sla->getID(), 'without_sla' => 0] as $name => $slas_id) {
            $tickets[$name] = $this->createItem(Ticket::class, [
                'name'        => __FUNCTION__ . $name,
                'content'     => __FUNCTION__,
                'entities_id' => $entities_id,
                'slas_id_ttr' => $slas_id,
            ]);
        }
        $this->assertSame(0, $tickets['with_sla']->fields['slastatuses_id']);

        $level_ticket = new SlaLevel_Ticket();
        $this->assertTrue($level_ticket->getFromDBByCrit([
            'tickets_id'   => $tickets['with_sla']->getID(),
            'slalevels_id' => $level->getID(),
        ]));
        $this->updateItem(SlaLevel_Ticket::class, $level_ticket->getID(), [
            'date' => date('Y-m-d H:i:s', time() - HOUR_TIMESTAMP),
        ]);
        // Levels can be replayed in the session of a user without the SLM assign right
        $_SESSION['glpiactiveprofile'][SLM::$rightname] &= ~SLM::RIGHT_ASSIGN;
        $this->runSlaCron();

        $this->assertTrue($tickets['with_sla']->getFromDB($tickets['with_sla']->getID()));
        $this->assertSame($status->getID(), $tickets['with_sla']->fields['slastatuses_id']);

        // The SLA status is searchable
        $results = \Search::getDatas(Ticket::class, [
            'criteria' => [
                ['field' => 199, 'searchtype' => 'equals', 'value' => $status->getID()],
            ],
        ]);
        $this->assertSame(
            [$tickets['with_sla']->getID()],
            array_column($results['data']['rows'], 'id')
        );

        // A clone does not inherit the escalation history of its source
        $clone_id = $tickets['with_sla']->clone();
        $this->assertGreaterThan(0, $clone_id);
        $this->assertSame(0, Ticket::getById($clone_id)->fields['slastatuses_id']);
    }

    public function testSlaStatusRequiresSlmAssignRight(): void
    {
        $this->login();

        $status = $this->createItem(\SlaStatus::class, ['name' => __FUNCTION__]);
        $ticket = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $slm_right = $_SESSION['glpiactiveprofile'][SLM::$rightname];
        $_SESSION['glpiactiveprofile'][SLM::$rightname] = $slm_right & ~SLM::RIGHT_ASSIGN;
        $this->assertTrue($ticket->update(['id' => $ticket->getID(), 'slastatuses_id' => $status->getID()]));
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertSame(0, $ticket->fields['slastatuses_id']);

        // `_auto_update` comes from the client and must not bypass the right
        $this->assertTrue($ticket->update(['id' => $ticket->getID(), 'slastatuses_id' => $status->getID(), '_auto_update' => true]));
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertSame(0, $ticket->fields['slastatuses_id']);

        $_SESSION['glpiactiveprofile'][SLM::$rightname] = $slm_right | SLM::RIGHT_ASSIGN;
        $this->updateItem(Ticket::class, $ticket->getID(), ['slastatuses_id' => $status->getID()]);
    }

    /**
     * @param class-string<SlaLevel|OlaLevel> $level_class
     * @param class-string<SlaLevelAction|OlaLevelAction> $action_class
     */
    #[DataProvider('recallLevelProvider')]
    public function testNotificationChangeDeletesItsRecallActions(string $level_class, string $action_class, string $event): void
    {
        $this->login();

        ['level' => $level] = $this->createTtrLevel($level_class, __FUNCTION__, $this->getTestRootEntity(true));

        $notifications = [];
        $actions = [];
        foreach (['purged', 'moved', 'kept'] as $name) {
            $notification = $this->createItem(\Notification::class, [
                'name'     => __FUNCTION__ . $name,
                'itemtype' => Ticket::class,
                'event'    => $event,
            ]);
            $actions[$name] = $this->createItem($action_class, [
                $level::getForeignKeyField() => $level->getID(),
                'action_type'                => 'send',
                'field'                      => $event . LevelAgreementLevel::NOTIFICATION_RECALL_SUFFIX,
                'value'                      => $notification->getID(),
            ]);
            $notifications[$name] = $notification;
        }

        $this->assertTrue($notifications['purged']->delete(['id' => $notifications['purged']->getID()], true));
        $this->updateItem(\Notification::class, $notifications['moved']->getID(), ['event' => 'new']);

        $this->assertFalse($action_class::getById($actions['purged']->getID()));
        $this->assertFalse($action_class::getById($actions['moved']->getID()));
        $this->assertNotFalse($action_class::getById($actions['kept']->getID()));
    }

    public function testSlaStatusPurgeDisablesItsLevels(): void
    {
        $this->login();

        ['level' => $level] = $this->createTtrLevel(SlaLevel::class, __FUNCTION__, $this->getTestRootEntity(true));
        $statuses = [];
        foreach (['replaced', 'replacement'] as $name) {
            $statuses[$name] = $this->createItem(\SlaStatus::class, ['name' => __FUNCTION__ . $name]);
        }
        $action = $this->createItem(SlaLevelAction::class, [
            'slalevels_id' => $level->getID(),
            'action_type'  => 'assign',
            'field'        => 'slastatuses_id',
            'value'        => $statuses['replaced']->getID(),
        ]);

        // Replacement updates the action
        $this->assertTrue($statuses['replaced']->delete(['id' => $statuses['replaced']->getID(), '_replace_by' => $statuses['replacement']->getID()], true));
        $this->assertEquals($statuses['replacement']->getID(), SlaLevelAction::getById($action->getID())->fields['value']);
        $this->assertSame(1, SlaLevel::getById($level->getID())->fields['is_active']);

        // Purge disables the level
        $this->assertTrue($statuses['replacement']->delete(['id' => $statuses['replacement']->getID()], true));
        $this->assertSame(0, SlaLevel::getById($level->getID())->fields['is_active']);
    }
}
