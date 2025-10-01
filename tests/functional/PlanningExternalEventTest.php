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

use Session;

include_once __DIR__ . '/../abstracts/AbstractPlanningEvent.php';

class PlanningExternalEventTest extends \AbstractPlanningEvent
{
    public $myclass = "\PlanningExternalEvent";


    public function testAddInstanceException()
    {
        $this->login();

        $event     = new $this->myclass();
        $id        = $event->add($this->input);
        $exception = date('Y-m-d', $this->now + DAY_TIMESTAMP);

        $this->assertTrue($event->addInstanceException($id, $exception));

        $rrule = json_decode($event->fields['rrule'], true);
        // original event has 2 exceptions, we add one
        $this->assertCount(3, $rrule['exceptions']);
        $this->assertContains($exception, $rrule['exceptions']);
    }


    public function testCreateInstanceClone()
    {
        $this->login();

        $event     = new $this->myclass();
        $serie_id  = $event->add($this->input);
        $start     = date('Y-m-d H:i:s', $this->now + DAY_TIMESTAMP);
        $start_day = date('Y-m-d', $this->now + DAY_TIMESTAMP);

        // the clone of series should not have rrule
        $new_event = $event->createInstanceClone($serie_id, $start);
        $this->assertInstanceOf($this->myclass, $new_event);
        $this->assertNotEquals($serie_id, $new_event->fields['id']);
        $this->assertNull($new_event->fields['rrule']);

        // original event should have the instance exception
        $rrule = json_decode($event->fields['rrule'], true);
        // original event has 2 exceptions, we add one
        $this->assertCount(3, $rrule['exceptions']);
        $this->assertContains($start_day, $rrule['exceptions']);
    }

    /**
     * Test guests removal when users_id_guests field is submitted as empty string
     * (simulating the hidden input behavior from the form)
     */
    public function testGuestsRemovalWithHiddenInput()
    {
        $this->login();

        // Create event with guests
        $event = new \PlanningExternalEvent();
        $id = $event->add([
            'name'            => 'Test Event with Guests',
            'users_id'        => Session::getLoginUserID(),
            'plan'            => [
                'begin' => '2025-01-15 10:00:00',
                'end'   => '2025-01-15 12:00:00',
            ],
            'users_id_guests' => [2, 3], // Add guests
        ]);
        $this->assertGreaterThan(0, $id);

        // Verify event was created correctly
        $this->assertTrue($event->getFromDB($id));
        $this->assertEquals('2025-01-15 10:00:00', $event->fields['begin']);
        $this->assertEquals('2025-01-15 12:00:00', $event->fields['end']);

        // Verify guests were added
        $guests = $event->fields['users_id_guests'];
        $this->assertCount(2, $guests);
        $this->assertContains(2, $guests);
        $this->assertContains(3, $guests);

        // Update event with empty users_id_guests (simulating hidden input)
        $update_result = $event->update([
            'id'              => $event->getID(),
            'name'            => 'Updated Event Name',
            'users_id_guests' => '', // Empty string from hidden input
        ]);
        $this->assertTrue($update_result);

        // Verify guests were removed
        $this->assertTrue($event->getFromDB($event->getID()));
        $guests = $event->fields['users_id_guests'];
        $this->assertEmpty($guests, 'Guests should be removed when users_id_guests is submitted as empty string');
        $this->assertEquals('Updated Event Name', $event->fields['name']);
    }

    /**
     * Test that partial updates without users_id_guests field don't affect existing guests
     */
    public function testPartialUpdatePreservesGuests()
    {
        $this->login();

        // Create event with guests
        $event = new \PlanningExternalEvent();
        $id = $event->add([
            'name'            => 'Test Event with Guests',
            'users_id'        => Session::getLoginUserID(),
            'plan'            => [
                'begin' => '2025-01-15 10:00:00',
                'end'   => '2025-01-15 12:00:00',
            ],
            'users_id_guests' => [2, 3],
        ]);
        $this->assertGreaterThan(0, $id);

        // Verify event was created correctly
        $this->assertTrue($event->getFromDB($id));
        $this->assertEquals('2025-01-15 10:00:00', $event->fields['begin']);
        $this->assertEquals('2025-01-15 12:00:00', $event->fields['end']);

        // Verify guests were added
        $guests_before = $event->fields['users_id_guests'];
        $this->assertCount(2, $guests_before);

        // Partial update without users_id_guests field
        $update_result = $event->update([
            'id'   => $event->getID(),
            'name' => 'Updated Name Only',
        ]);
        $this->assertTrue($update_result);

        // Verify guests are preserved
        $this->assertTrue($event->getFromDB($event->getID()));
        $guests_after = $event->fields['users_id_guests'];
        $this->assertCount(2, $guests_after);
        $this->assertEquals($guests_before, $guests_after);
        $this->assertEquals('Updated Name Only', $event->fields['name']);
    }

    /**
     * Test that prepareGuestsInput correctly handles empty string input
     */
    public function testPrepareGuestsInputHandlesEmptyString()
    {
        $this->login();

        $event = new \PlanningExternalEvent();

        // Test with empty string (simulates hidden input from form)
        // Should convert empty string to JSON empty array
        $input = [
            'id' => 1,
            'name' => 'Test',
            'users_id_guests' => '', // Empty string from hidden input
        ];
        $result = $this->callPrivateMethod($event, 'prepareGuestsInput', $input);
        $this->assertEquals(
            '[]',
            $result['users_id_guests'],
            'prepareGuestsInput should convert empty string to JSON empty array'
        );

        // Test with array (normal case)
        $input['users_id_guests'] = [2, 3];
        $result = $this->callPrivateMethod($event, 'prepareGuestsInput', $input);
        $this->assertEquals('[2,3]', $result['users_id_guests']);

        // Test when field is not present (should remain unchanged)
        unset($input['users_id_guests']);
        $result = $this->callPrivateMethod($event, 'prepareGuestsInput', $input);
        $this->assertFalse(isset($result['users_id_guests']));
    }

    /**
     * Test guests addition and modification through array input
     */
    public function testGuestsArrayHandling()
    {
        $this->login();

        // Create event without guests
        $event = new \PlanningExternalEvent();
        $id = $event->add([
            'name'     => 'Test Event',
            'users_id' => Session::getLoginUserID(),
            'plan'     => [
                'begin' => '2025-01-15 10:00:00',
                'end'   => '2025-01-15 12:00:00',
            ],
        ]);
        $this->assertGreaterThan(0, $id);

        // Verify event was created correctly
        $this->assertTrue($event->getFromDB($id));
        $this->assertEquals('2025-01-15 10:00:00', $event->fields['begin']);
        $this->assertEquals('2025-01-15 12:00:00', $event->fields['end']);

        // Add guests via array
        $update_result = $event->update([
            'id'              => $event->getID(),
            'users_id_guests' => [2, 3, 4],
        ]);
        $this->assertTrue($update_result);

        // Verify guests were added
        $this->assertTrue($event->getFromDB($event->getID()));
        $guests = $event->fields['users_id_guests'];
        $this->assertCount(3, $guests);
        $this->assertContains(2, $guests);
        $this->assertContains(3, $guests);
        $this->assertContains(4, $guests);

        // Modify guests (remove one, add another)
        $update_result = $event->update([
            'id'              => $event->getID(),
            'users_id_guests' => [2, 5], // Remove 3,4 and add 5
        ]);
        $this->assertTrue($update_result);

        // Verify guests were modified
        $this->assertTrue($event->getFromDB($event->getID()));
        $guests = $event->fields['users_id_guests'];
        $this->assertCount(2, $guests);
        $this->assertContains(2, $guests);
        $this->assertContains(5, $guests);
        $this->assertNotContains(3, $guests);
        $this->assertNotContains(4, $guests);
    }
}
