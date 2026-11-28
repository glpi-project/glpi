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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PlanningTest extends DbTestCase
{
    public function testCloneEvent()
    {
        $this->login();

        $input = [
            'name'  => "test event to clone",
            'entities_id' => $this->getTestRootEntity(true),
            'plan'  => [
                'begin'     => date('Y-m-d H:i:s'),
                '_duration' => 2 * HOUR_TIMESTAMP,
            ],
            'rrule' => '{"freq":"weekly","interval":"1"}',
        ];

        $event = new \PlanningExternalEvent();
        $this->assertGreaterThan(0, $event_id = $event->add($input));

        $timestamp = time() + DAY_TIMESTAMP;
        $new_start = date('Y-m-d H:i:s', $timestamp);
        $new_end   = date('Y-m-d H:i:s', $timestamp + 2 * HOUR_TIMESTAMP);

        $this->assertGreaterThan(
            0,
            $clone_events_id = \Planning::cloneEvent([
                'old_itemtype' => 'PlanningExternalEvent',
                'old_items_id' => $event_id,
                'start'        => $new_start,
                'end'          => $new_end,
            ])
        );

        // check cloned event
        $this->assertTrue($event->getFromDB($clone_events_id));
        $this->assertEquals($new_start, $event->fields['begin']);
        $this->assertEquals($new_end, $event->fields['end']);
        $this->assertEquals($input['rrule'], $event->fields['rrule']);
        $this->assertStringContainsString(sprintf(__('Copy of %s'), $input['name']), $event->fields['name']);
    }

    public function testGetExternalCalendarEvents()
    {
        global $CFG_GLPI;

        $this->login();

        $session_backup = $_SESSION['glpi_plannings'];

        \Planning::initSessionForCurrentUser();

        // Expected results
        $expected_events_data = [
            'recurring_evt_1' => [
                'title'   => 'Recur event',
                'tooltip' => "Recur event<br>Monday, 11 am to 12 am starting on 1st of July",
                'backgroundColor'   => '#ff0000',
                'rrule'   => "DTSTART:20190701T090000\nRRULE:FREQ=WEEKLY;BYDAY=MO\n",
            ],
            'simple_evt_1'    => [
                'title'   => 'Base event with no desc',
                'tooltip' => 'Base event with no desc',
                'backgroundColor'   => '#ff0000',
            ],
            'simple_evt_2'    => [
                'title'   => 'An event in 2020/01/05',
                'tooltip' => "An event in 2020/01/05<br>Description of &quot;&lt;my event&gt;&quot;",
                'backgroundColor'   => '#ff0000',
            ],
            'task_1'          => [
                'title'   => 'Test task',
                'tooltip' => "Test task<br>Description of the task.",
                'backgroundColor'   => '#ff0000',
            ],
            'another_evt_1'   => [
                'title'   => 'Another event',
                'tooltip' => "Another event<br>Another event description",
                'backgroundColor'   => '#a500b3',
            ],
        ];

        $expected_events_keys = [
            'all_events'   => ['recurring_evt_1', 'simple_evt_1', 'simple_evt_2', 'task_1', 'another_evt_1'],
            'month_events' => ['recurring_evt_1', 'simple_evt_1', 'task_1', 'another_evt_1'],
            'cal2_events'  => ['another_evt_1'],
        ];

        // Add calendars
        $_SESSION['glpi_plannings']['plannings'] = [
            'external_1' => [
                'backgroundColor'   => '#ff0000',
                'display' => true,
                'type'    => 'external',
                'name'    => 'External calendar 1',
                'url'     => $CFG_GLPI['url_base'] . '/test/planning-fixtures/sample_1.ics',
            ],
            'external_2' => [
                'backgroundColor'   => '#a500b3',
                'display' => true,
                'type'    => 'external',
                'name'    => 'External calendar 2',
                'url'     => $CFG_GLPI['url_base'] . '/test/planning-fixtures/sample_2.ics',
            ],
        ];

        // Fetch all events
        $all_events = \Planning::constructEventsArray(
            [
                'start'            => '2000-01-01 00:00:00',
                'end'              => '2050-12-31 23:59:59',
                'force_all_events' => true,
            ]
        );
        // Fetch events only for a given month
        $month_events = \Planning::constructEventsArray(
            [
                'start' => '2019-11-01 00:00:00',
                'end'   => '2019-11-30 23:59:59',
            ]
        );
        // Fetch events only for a given calendar
        $_SESSION['glpi_plannings']['plannings']['external_1']['display'] = false;
        $cal2_events = \Planning::constructEventsArray(
            [
                'start' => '2019-11-01 00:00:00',
                'end'   => '2019-11-30 23:59:59',
            ]
        );

        $_SESSION['glpi_plannings'] = $session_backup;

        foreach ($expected_events_keys as $list_var => $events_keys) {
            $events_list = $$list_var;
            $this->assertCount(count($events_keys), $events_list);
            foreach ($events_keys as $index => $evt_key) {
                $event_data = $expected_events_data[$evt_key];
                $this->assertArrayHasKey($index, $events_list);
                $this->assertEquals($event_data['title'], $events_list[$index]['title']);
                $this->assertEquals($event_data['tooltip'], $events_list[$index]['tooltip']);
                $this->assertEquals($event_data['backgroundColor'], $events_list[$index]['backgroundColor']);
                if (array_key_exists('rrule', $event_data)) {
                    $this->assertEquals($event_data['rrule'], $events_list[$index]['rrule']);
                }
            }
        }
    }

    public static function getPaletteColorProvider()
    {
        $palettes = [];
        $properties = (new \ReflectionClass(\Planning::class))->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_STATIC);
        foreach ($properties as $property) {
            if (str_starts_with($property->getName(), 'palette_')) {
                // Add palette (without the 'palette_' prefix)
                $palettes[] = substr($property->getName(), 8);
            }
        }
        $result = [];
        foreach ($palettes as $palette) {
            for ($i = 0; $i < 20; $i++) {
                $result[] = [$palette, $i];
            }
        }
        return $result;
    }

    #[DataProvider('getPaletteColorProvider')]
    public function testGetPaletteColor(string $palette_name, int $index)
    {
        $color = \Planning::getPaletteColor($palette_name, $index);
        $this->assertMatchesRegularExpression('/#[0-9A-F]{6}/', $color);
    }

    public function testUpdateEventTimesAllowed()
    {
        $this->login();

        // Shouldn't be allowed to update item of invalid type
        $this->assertFalse(\Planning::updateEventTimes([
            'itemtype' => 'InvalidType',
            'items_id' => 1,
            'start'    => '2020-01-01 00:00:00',
            'end'      => '2020-01-01 01:00:00',
        ]));

        // Shouldn't be allowed to update item that doesn't exist
        $this->assertFalse(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => 99999999,
            'start'    => '2020-01-01 00:00:00',
            'end'      => '2020-01-01 01:00:00',
        ]));

        // Shouldn't be allowed to update task from closed ticket
        $ticket = $this->createItem('Ticket', [
            'name' => 'Test ticket',
            'content' => 'Test ticket',
            'entities_id' => $this->getTestRootEntity(true),
            '_users_id_assign' => $_SESSION['glpiID'],
        ]);
        $task = $this->createItem('TicketTask', [
            'tickets_id' => $ticket->getID(),
            'content' => 'Test task',
            'begin' => '2020-01-01 00:00:00',
            'end' => '2020-01-01 01:00:00',
        ]);
        $ticket->update([
            'id' => $ticket->getID(),
            'status' => \Ticket::CLOSED,
        ]);
        $this->assertFalse(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => $task->getID(),
            'start'    => '2020-01-02 00:00:00',
            'end'      => '2020-01-02 01:00:00',
        ]));

        $ticket->update([
            'id' => $ticket->getID(),
            'status' => \Ticket::INCOMING,
        ]);

        // General update test
        $_SESSION['glpiactiveprofile'][\TicketTask::$rightname] = READ;
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] &= ~\Ticket::OWN;
        $this->assertFalse(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => $task->getID(),
            'start'    => '2020-01-02 00:00:00',
            'end'      => '2020-01-02 01:00:00',
        ]));

        // Allowed test
        $_SESSION['glpiactiveprofile'][\TicketTask::$rightname] = ALLSTANDARDRIGHT | \CommonITILTask::UPDATEALL;
        $this->assertTrue(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => $task->getID(),
            'start'    => '2020-01-02 00:00:00',
            'end'      => '2020-01-02 01:00:00',
        ]));
    }

    public function testAllDayEvents()
    {
        $this->login();
        \Planning::initSessionForCurrentUser();


        $event = new \PlanningExternalEvent();
        $event->add([
            'name'  => __FUNCTION__,
            'text'  => __FUNCTION__,
            'plan' => [
                'begin' => '2020-01-01 00:00:00',
                'end'   => '2020-01-02 00:00:00',
            ],
        ]);
        $event->add([
            'name'  => __FUNCTION__ . '_recurring',
            'text'  => __FUNCTION__ . '_recurring',
            'plan' => [
                'begin' => '2020-01-01 00:00:00',
                'end'   => '2020-01-02 00:00:00',
            ],
            'rrule' => '{"freq":"weekly","interval":"3","until":""}',
        ]);
        $event->add([
            'name'  => __FUNCTION__ . '_not_allday',
            'text'  => __FUNCTION__ . '_not_allday',
            'plan' => [
                'begin' => '2020-01-01 01:00:00',
                'end'   => '2020-01-02 01:00:00',
            ],
        ]);

        $events = \Planning::constructEventsArray([
            'start' => '2020-01-01 00:00:00',
            'end'   => '2020-01-30 00:00:00',
            'view_name' => 'listFull',
            'force_all_events' => true,
        ]);
        $this->assertCount(3, $events);
        $this->assertEquals(__FUNCTION__, $events[0]['title']);
        $this->assertTrue($events[0]['allDay'] ?? false);
        $this->assertEquals(__FUNCTION__ . '_recurring', $events[1]['title']);
        $this->assertTrue($events[1]['allDay'] ?? false);
        $this->assertEquals(__FUNCTION__ . '_not_allday', $events[2]['title']);
        $this->assertFalse($events[2]['allDay'] ?? false);
    }

    public function testConstructEventsArrayIgnoresGenical()
    {
        $this->login('tech', 'tech');
        \Planning::initSessionForCurrentUser();

        $rights = $_SESSION['glpiactiveprofile'];

        // `genical` comes from the request on `ajax/planning.php?action=get_events`
        \Planning::constructEventsArray([
            'start'     => date('Y-m-d 00:00:00'),
            'end'       => date('Y-m-d 00:00:00', time() + WEEK_TIMESTAMP),
            'view_name' => 'timeGridWeek',
            'genical'   => 1,
        ]);

        $this->assertSame($rights, $_SESSION['glpiactiveprofile']);
    }

    public function testEditEventForm()
    {
        $this->login();

        $event = $this->createItem(\PlanningExternalEvent::class, [
            'name'        => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'plan'        => [
                'begin'     => date('Y-m-d H:i:s'),
                '_duration' => HOUR_TIMESTAMP,
            ],
        ], ['plan']);

        ob_start();
        \Planning::editEventForm([
            'itemtype' => \PlanningExternalEvent::class,
            'id'       => $event->getID(),
            'start'    => date('Y-m-d'),
        ]);
        $this->assertStringContainsString('edit_event_form', ob_get_clean());
    }

    public function testEditEventFormRefusesNonPlanningItemtype()
    {
        $this->login();

        $this->expectException(AccessDeniedHttpException::class);
        \Planning::editEventForm([
            'itemtype' => \Computer::class,
            'id'       => getItemByTypeName(\Computer::class, '_test_pc01', true),
            'start'    => date('Y-m-d'),
        ]);
    }

    public function testEditEventFormRefusesUnreadableItem()
    {
        $this->login();

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $task = $this->createItem(\TicketTask::class, [
            'tickets_id' => $ticket->getID(),
            'content'    => __FUNCTION__,
            'is_private' => 1,
        ]);

        $this->login('post-only', 'postonly');

        $this->expectException(AccessDeniedHttpException::class);
        \Planning::editEventForm([
            'itemtype'       => \TicketTask::class,
            'id'             => $task->getID(),
            'parentitemtype' => \Ticket::class,
            'parentid'       => $ticket->getID(),
            'start'          => date('Y-m-d'),
        ]);
    }

    public function testEditEventFormRefusesForeignParent()
    {
        $this->login();

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $other_ticket = $this->createItem(\Ticket::class, [
            'name'        => __FUNCTION__ . ' other',
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $task = $this->createItem(\TicketTask::class, [
            'tickets_id' => $ticket->getID(),
            'content'    => __FUNCTION__,
        ]);

        $this->expectException(AccessDeniedHttpException::class);
        \Planning::editEventForm([
            'itemtype'       => \TicketTask::class,
            'id'             => $task->getID(),
            'parentitemtype' => \Ticket::class,
            'parentid'       => $other_ticket->getID(),
            'start'          => date('Y-m-d'),
        ]);
    }

    public function testRRuleEncoding()
    {
        $event = new \PlanningExternalEvent();
        $this->assertEquals('{"freq":"weekly","interval":"3","until":""}', $event->encodeRRule(["freq" => "weekly", "interval" => "3", "until" => ""]));
        $this->assertEmpty($event->encodeRRule(["freq" => null]));
        $this->assertEmpty($event->encodeRRule(["freq" => '']));
    }

    public static function checkAlreadyPlannedProvider()
    {
        $begin_task = '2025-05-13 00:00:00';
        $end_task   = '2025-05-13 01:00:00';

        // test with no user assigned to a task
        yield [
            'params' => [
                'user'        => null,
                'begin'       => '2025-05-13 00:00:00',
                'end'         => '2025-05-13 01:00:00',
                'except_task' => false,
            ],
            'expected' => [
                'is_busy' => false,
            ],
        ];

        // test with no tech user who is assigned to a task outside the period of $task
        yield [
            'params' => [
                'user'        => 'tech',
                'begin'       => '2025-05-13 02:00:00',
                'end'         => '2025-05-13 03:00:00',
                'except_task' => false,
            ],
            'expected' => [
                'is_busy' => false,
            ],
        ];

        // test with the user glpi who is not assigned to any task
        yield [
            'params' => [
                'user'        => 'glpi',
                'begin'       => '2025-05-13 02:00:00',
                'end'         => '2025-05-13 03:00:00',
                'except_task' => false,
            ],
            'expected' => [
                'is_busy' => false,
            ],
        ];

        // test with the user glpi who is assigned to a task in the same period as $task
        yield [
            'params' => [
                'user'        => 'glpi',
                'begin'       => $begin_task,
                'end'         => $end_task,
                'except_task' => false,
            ],
            'expected' => [
                'is_busy' => false,
            ],
        ];

        // test with the tech user who is assigned to a task in the same period as $task
        yield [
            'params' => [
                'user'        => 'tech',
                'begin'       => $begin_task,
                'end'         => $end_task,
                'except_task' => false,
            ],
            'expected' => [
                'is_busy' => true,
            ],
        ];

        // test with the tech user who has just been assigned to the task $task
        yield [
            'params' => [
                'user'        => 'tech',
                'begin'       => $begin_task,
                'end'         => $end_task,
                'except_task' => true,
            ],
            'expected' => [
                'is_busy' => false,
            ],
        ];
    }

    #[DataProvider('checkAlreadyPlannedProvider')]
    public function testCheckAlreadyPlanned(array $params, array $expected)
    {
        $this->login('glpi', 'glpi');

        $tech_id    = getItemByTypeName('User', 'tech', true);
        $normal_id  = getItemByTypeName('User', 'normal', true);
        $user_id = is_string($params['user']) ? getItemByTypeName('User', $params['user'], true) : 0;

        $begin_task = '2025-05-13 00:00:00';
        $end_task   = '2025-05-13 01:00:00';
        $ticket_name = 'Ticket Test planned';

        $ticket_id = $this->createItem(\Ticket::class, [
            'name' => $ticket_name,
            'content' => 'Ticket content',
            'entities_id' => 0,
        ])->getID();

        $task = $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket_id,
                'users_id'      => $normal_id,
                'users_id_tech' => $tech_id,
                'date'          => '2025-05-13 00:00:00',
                'content'       => 'TicketTask content',
                'actiontime'    => 1 * HOUR_TIMESTAMP,
                'begin'         => $begin_task,
                'end'           => $end_task,
                'state'         => 1,
            ],
        );

        $this->assertEquals(\Planning::checkAlreadyPlanned(
            $user_id,
            $params['begin'],
            $params['end'],
            $params['except_task'] ? [
                \TicketTask::class => [
                    $task->getID(),
                ],
            ] : [],
        ), $expected['is_busy']);
        if ($expected['is_busy']) {
            $warning = "The user <a href=\"/front/user.form.php?id=$tech_id\">tech</a> is busy at the selected timeframe.<br/>- Ticket task: from 2025-05-13 00:00 to 2025-05-13 01:00:<br/><a href='/front/ticket.form.php?id=$ticket_id&amp;forcetab=TicketTask$1'>$ticket_name</a><br/>";
            $this->hasSessionMessages(WARNING, [$warning]);
        } else {
            $this->hasNoSessionMessages([WARNING]);
        }
    }

    public function testPlanningFilterInfoProvidesSessionUserPersonalToken()
    {
        $this->login();

        $session_users_id = \Session::getLoginUserID();
        $session_user_token = \User::getToken($session_users_id, 'personal_token');

        $filter_info = \Planning::getPlanningFilterInfo(
            'user_' . $session_users_id,
            [
                'type'    => 'user',
                'display' => true,
            ],
            [
                'token' => $session_user_token,
            ]
        );

        // The filter of the session user carries its personal token.
        $this->assertSame($session_user_token, $filter_info['token']);
        $user = new \User();
        $this->assertTrue($user->getFromDB($session_users_id));
        $this->assertSame(
            (new \GLPIKey())->decrypt($user->fields['personal_token']),
            $filter_info['token']
        );
    }

    public function testPlanningFilterInfoDoesNotUsePersonalTokenOfFilteredUser()
    {
        $this->login();

        $session_user_token = \User::getToken(\Session::getLoginUserID(), 'personal_token');

        // Create another user, used as the filter actor, with its own personal token.
        $other_user = $this->createItem(\User::class, ['name' => 'planningfiltereduser']);
        $other_user_token = \User::getToken($other_user->getID(), 'personal_token');
        $this->assertNotSame($session_user_token, $other_user_token);

        $filter_info = \Planning::getPlanningFilterInfo(
            'user_' . $other_user->getID(),
            [
                'type'    => 'user',
                'display' => true,
            ],
            [
                'token' => $session_user_token,
            ]
        );

        // The value exposed in the filter payload is the one of the session user,
        // never the one of the user the filter is displaying.
        $this->assertSame($session_user_token, $filter_info['token']);
        $this->assertNotSame($other_user_token, $filter_info['token']);
    }

    public function testPlanningFilterInfoTokenOnlyProvidedForExportableFilters()
    {
        $this->login();

        // Event filters do not display export buttons: they must not receive the token.
        $filter_info = \Planning::getPlanningFilterInfo(
            'NotPlanned',
            [
                'type'    => 'event_filter',
                'display' => true,
            ],
            [
                'token' => 'session_token_value',
            ]
        );
        $this->assertNull($filter_info['token']);

        // External calendars do not display export buttons either.
        $filter_info = \Planning::getPlanningFilterInfo(
            'external_1',
            [
                'type'    => 'external',
                'name'    => 'External calendar',
                'display' => true,
            ],
            [
                'token' => 'session_token_value',
            ]
        );
        $this->assertNull($filter_info['token']);
    }

    public function testPlanningFilterInfoPropagatesPersonalTokenToChildFilters()
    {
        $this->login();

        $session_user_token = \User::getToken(\Session::getLoginUserID(), 'personal_token');

        $group = $this->createItem(\Group::class, ['name' => 'planningchildgroup']);
        $users_id = \Session::getLoginUserID();
        $group_users_filter_info = \Planning::getPlanningFilterInfo(
            'group_' . $group->getID() . '_users',
            [
                'type'    => 'group_users',
                'display' => true,
                'users'   => [
                    'user_' . $users_id => [
                        'type'    => 'user',
                        'display' => true,
                    ],
                ],
            ],
            [
                'token' => $session_user_token,
            ]
        );

        // The group_users container does not display export buttons, it does not carry the token.
        $this->assertNull($group_users_filter_info['token']);
        // Sub-filters, that display export buttons, inherit the session user personal token.
        $this->assertArrayHasKey('user_' . $users_id, $group_users_filter_info['child_filters']);
        $this->assertSame(
            $session_user_token,
            $group_users_filter_info['child_filters']['user_' . $users_id]['token']
        );
    }

    public function testShowPlanningGeneratesPersonalTokenAndIncludesItInFiltersPayload()
    {
        $this->login();
        $users_id = \Session::getLoginUserID();

        // Ensure the session user has no personal token yet.
        $this->updateItem(\User::class, $users_id, ['personal_token' => null, 'personal_token_date' => null]);
        $user = new \User();
        $this->assertTrue($user->getFromDB($users_id));
        $this->assertEmpty($user->fields['personal_token']);

        $session_backup = $_SESSION['glpi_plannings'] ?? null;
        $session_existed = isset($_SESSION['glpi_plannings']);

        try {
            // Explicitly configure an exportable user filter for the session user, so the
            // personal token generation and its inclusion in the filters payload do not rely
            // on the current planning session state.
            $_SESSION['glpi_plannings']['filters'] = [
                'user_' . $users_id => [
                    'type'    => 'user',
                    'display' => true,
                ],
            ];
            $_SESSION['glpi_plannings']['plannings'] = [];

            ob_start();
            \Planning::showPlanning();
            $output = ob_get_clean();

            // The missing personal token has been generated and it is embedded in the filters
            // payload sent to the Vue planning application.
            $this->assertTrue($user->getFromDB($users_id));
            $this->assertNotEmpty($user->fields['personal_token']);
            $personal_token = (new \GLPIKey())->decrypt($user->fields['personal_token']);
            $this->assertSame(1, substr_count($output, sprintf('"token":"%s"', $personal_token)));
        } finally {
            if ($session_existed) {
                $_SESSION['glpi_plannings'] = $session_backup;
            } else {
                unset($_SESSION['glpi_plannings']);
            }
        }
    }
}
