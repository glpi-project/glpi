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

/* Test for inc/planning.class.php */

class PlanningTest extends \DbTestCase
{
    public function testCloneEvent()
    {
        $this->login();

        $input = [
            'name'  => "test event to clone",
            'plan'  => [
                'begin'     => date('Y-m-d H:i:s'),
                '_duration' => 2 * HOUR_TIMESTAMP
            ],
            'rrule' => '{"freq":"weekly","interval":"1"}'
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
                'end'          => $new_end
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

        $this->login();

        $session_backup = $_SESSION['glpi_plannings'];

        \Planning::initSessionForCurrentUser();

        // Expected results
        $expected_events_data = [
            'recurring_evt_1' => [
                'title'   => 'Recur event',
                'tooltip' => "Recur event\nMonday, 11 am to 12 am starting on 1st of July",
                'color'   => '#ff0000',
                'rrule'   => "DTSTART:20190701T090000\nRRULE:FREQ=WEEKLY;BYDAY=MO\n",
            ],
            'simple_evt_1'    => [
                'title'   => 'Base event with no desc',
                'tooltip' => 'Base event with no desc',
                'color'   => '#ff0000',
            ],
            'simple_evt_2'    => [
                'title'   => 'An event in 2020/01/05',
                'tooltip' => "An event in 2020/01/05\nDescription of my event",
                'color'   => '#ff0000',
            ],
            'task_1'          => [
                'title'   => 'Test task',
                'tooltip' => "Test task\nDescription of the task.",
                'color'   => '#ff0000',
            ],
            'another_evt_1'   => [
                'title'   => 'Another event',
                'tooltip' => "Another event\nAnother event description",
                'color'   => '#a500b3',
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
                'color'   => '#ff0000',
                'display' => true,
                'type'    => 'external',
                'name'    => 'External calendar 1',
                'url'     => 'file://' . realpath(GLPI_ROOT . '/tests/fixtures/ical/sample_1.ics'),
            ],
            'external_2' => [
                'color'   => '#a500b3',
                'display' => true,
                'type'    => 'external',
                'name'    => 'External calendar 2',
                'url'     => 'file://' . realpath(GLPI_ROOT . '/tests/fixtures/ical/sample_2.ics'),
            ],
        ];

        // Fetch all events
        $all_events = \Planning::constructEventsArray(
            [
                'start'            => '2000-01-01 00:00:00',
                'end'              => '2050-12-31 23:59:59',
                'force_all_events' => true
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
                $this->assertEquals($event_data['color'], $events_list[$index]['color']);
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

    /**
     * @dataProvider getPaletteColorProvider
     */
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
            'end'      => '2020-01-01 01:00:00'
        ]));

        // Shouldn't be allowed to update item that doesn't exist
        $this->assertFalse(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => 99999999,
            'start'    => '2020-01-01 00:00:00',
            'end'      => '2020-01-01 01:00:00'
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
            'end' => '2020-01-01 01:00:00'
        ]);
        $ticket->update([
            'id' => $ticket->getID(),
            'status' => \Ticket::CLOSED
        ]);
        $this->assertFalse(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => $task->getID(),
            'start'    => '2020-01-02 00:00:00',
            'end'      => '2020-01-02 01:00:00'
        ]));

        $ticket->update([
            'id' => $ticket->getID(),
            'status' => \Ticket::INCOMING
        ]);

        // General update test
        $_SESSION['glpiactiveprofile'][\TicketTask::$rightname] = READ;
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] &= ~\Ticket::OWN;
        $this->assertFalse(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => $task->getID(),
            'start'    => '2020-01-02 00:00:00',
            'end'      => '2020-01-02 01:00:00'
        ]));

        // Allowed test
        $_SESSION['glpiactiveprofile'][\TicketTask::$rightname] = ALLSTANDARDRIGHT | \CommonITILTask::UPDATEALL;
        $this->assertTrue(\Planning::updateEventTimes([
            'itemtype' => 'TicketTask',
            'items_id' => $task->getID(),
            'start'    => '2020-01-02 00:00:00',
            'end'      => '2020-01-02 01:00:00'
        ]));
    }
}
