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

use DbTestCase;

class ReservationTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login();

        // Ensure root entity is active for tests
        $this->setEntity(0, true);
    }

    public function testGetReservableItemtypes(): void
    {
        $this->logOut();
        // No reservable items
        $this->assertEquals([], \Reservation::getReservableItemtypes());

        $root = getItemByTypeName("Entity", "_test_root_entity", true);

        // Enable reservation on a computer
        $computer = $this->createItem("Computer", [
            "name"        => "test",
            "entities_id" => $root,
        ]);
        $reservation_item = $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => $root,
        ]);
        // Nothing showing because we are not logged in
        $this->assertCount(0, \Reservation::getReservableItemtypes());

        $this->login();
        $this->assertEquals(["Computer"], \Reservation::getReservableItemtypes());

        \Session::changeActiveEntities(getItemByTypeName("Entity", "_test_child_1", true));
        // Nothing showing because we are now in a child entity and the computer is not recursive
        $this->assertCount(0, \Reservation::getReservableItemtypes());

        //Make computer recursive and check again
        $this->assertTrue($computer->update([
            'id' => $computer->getID(),
            "is_recursive" => true,
        ]));
        $this->assertTrue($reservation_item->update([
            'id' => $reservation_item->getID(),
            "is_recursive" => true,
        ]));
        $this->assertEquals(["Computer"], \Reservation::getReservableItemtypes());
    }

    public function testAddRecurrentReservation(): void
    {
        $this->login();
        $computer = $this->createItem("Computer", [
            "name"        => "test",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => 0,
        ]);
        $reservation = new \Reservation();
        $this->assertEquals(0, count($reservation->find()));

        \Reservation::handleAddForm([
            "itemtype"  => "Computer",
            "items" => [
                0       => (string) $res_item->fields["id"],
            ],
            "resa" => [
                "begin" => "2023-11-02 00:00:00",
                "end"   => "2023-11-03 00:00:00",
            ],
            "periodicity" => [
                "type"  => "week",
                "end"   => "2023-11-30",
                "days"  => [
                    "Wednesday" => "on",
                ],
            ],
            "users_id"  => getItemByTypeName('User', TU_USER, true),
            "comment"   => "",
        ]);
        $this->assertEquals(5, count($reservation->find()));
    }

    public static function dataAddReservationTest(): array
    {
        return [
            [
                'begin'                   => "2023-11-01 00:00:00",
                'end'                     => "2023-11-01 00:10:00",
            ],
            [
                'begin'                   => "2023-11-02 00:00:00",
                'end'                     => "2023-11-25 23:00:00",
            ],
            [
                'begin'                   => "2023-11-03 00:00:00",
                'end'                     => "2023-11-04 00:00:00",
            ],
        ];
    }

    /**
     * @dataProvider dataAddReservationTest
     */
    public function testAddJustOneReservation($begin, $end): void
    {
        $this->login();
        $computer = $this->createItem("Computer", [
            "name"        => "test",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => 0,
        ]);

        $data = [
            'begin'                   => $begin,
            'end'                     => $end,
            'reservationitems_id'     => $res_item->getID(),
            'users_id'                => getItemByTypeName('User', TU_USER, true),
        ];
        $reservation = new \Reservation();
        $this->assertCount(0, $reservation->find($data));

        $reservation->add($data);
        $this->assertCount(1, $reservation->find($data));
    }

    public function testDeleteRecurrentReservation(): void
    {
        self::testAddRecurrentReservation();
        $reservation = new \Reservation();
        $this->assertCount(5, $reservation->find());
        foreach ($reservation->find() as $res) {
            $firstres = $res;
            break;
        }
        $reservation->delete($firstres + ['_delete_group' => 'on']);
        $this->assertCount(0, $reservation->find());
    }

    /**
     * Test that canUpdate method includes RESERVEANITEM right
     */
    public function testCanUpdateWithReserveanitemRight(): void
    {
        // Test with UPDATE right
        $_SESSION['glpiactiveprofile']['reservation'] = UPDATE;
        $this->assertTrue((bool)\Reservation::canUpdate());

        // Test with RESERVEANITEM right (simplified interface case)
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool)\Reservation::canUpdate());

        // Test with both rights
        $_SESSION['glpiactiveprofile']['reservation'] = UPDATE | \ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool)\Reservation::canUpdate());

        // Test with unrelated right
        $_SESSION['glpiactiveprofile']['reservation'] = READ;
        $this->assertFalse((bool)\Reservation::canUpdate());

        // Test with no rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;
        $this->assertFalse((bool)\Reservation::canUpdate());
    }

    /**
     * Test that canPurge method includes RESERVEANITEM right
     */
    public function testCanPurgeWithReserveanitemRight(): void
    {
        $this->login();

        // Test with PURGE right
        $_SESSION['glpiactiveprofile']['reservation'] = PURGE;
        $this->assertTrue((bool)\Reservation::canPurge());

        // Test with RESERVEANITEM right (simplified interface case)
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool)\Reservation::canPurge());

        // Test with both rights
        $_SESSION['glpiactiveprofile']['reservation'] = PURGE | \ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool)\Reservation::canPurge());

        // Test with unrelated right
        $_SESSION['glpiactiveprofile']['reservation'] = READ;
        $this->assertFalse((bool)\Reservation::canPurge());

        // Test with no rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;
        $this->assertFalse((bool)\Reservation::canPurge());
    }

    /**
     * Test that canCreate and canDelete methods work with RESERVEANITEM right
     */
    public function testCanCreateAndDeleteWithReserveanitemRight(): void
    {
        $this->login();

        // Test canCreate with RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool)\Reservation::canCreate(), "canCreate should return truthy value with RESERVEANITEM right");

        // Test canDelete with RESERVEANITEM right - canDelete only checks RESERVEANITEM
        $this->assertTrue((bool)\Reservation::canDelete(), "canDelete should return truthy value with RESERVEANITEM right");

        // Test with no rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;
        $this->assertFalse((bool)\Reservation::canCreate());
        $this->assertFalse((bool)\Reservation::canDelete());
    }

    /**
     * Test canChildItem method for reservation ownership
     */
    public function testCanChildItemOwnership(): void
    {
        $this->login();

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Create a reservation owned by current user
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
        ]);
        $this->assertGreaterThan(0, $reservation_id);
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // Test that owner has rights even with minimal permissions
        $_SESSION['glpiactiveprofile']['reservation'] = 0; // No rights at all
        $this->assertTrue($reservation->canChildItem('canUpdateItem', 'canUpdate'));

        // Test with different user
        $reservation->fields['users_id'] = $_SESSION['glpiID'] + 1; // Different user ID
        $this->assertFalse($reservation->canChildItem('canUpdateItem', 'canUpdate'));
    }

    /**
     * Test entity access in canChildItem
     */
    public function testCanChildItemEntityAccess(): void
    {
        $this->login();

        // Create computer in child entity
        $child_entity = getItemByTypeName("Entity", "_test_child_1", true);
        $computer = $this->createItem("Computer", [
            "name" => "test computer",
            "entities_id" => $child_entity,
            "is_recursive" => false,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $child_entity,
        ]);

        // Create reservation in child entity
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
        ]);
        $this->assertGreaterThan(0, $reservation_id);
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // Test access from root entity (should work due to hierarchy)
        \Session::changeActiveEntities(0);
        $this->assertTrue($reservation->canChildItem('canUpdateItem', 'canUpdate'));

        // Test access from child entity
        \Session::changeActiveEntities($child_entity);
        $this->assertTrue($reservation->canChildItem('canUpdateItem', 'canUpdate'));

        // Test access from unrelated entity (should still work because user is owner)
        // canChildItem grants rights to owner regardless of entity restrictions
        $other_entity = getItemByTypeName("Entity", "_test_child_2", true);
        \Session::changeActiveEntities($other_entity);
        $this->assertTrue(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "Owner should have access to their reservation regardless of entity restrictions"
        );
    }

    /**
     * Test complete workflow: create, update, delete reservation with RESERVEANITEM right
     */
    public function testCompleteWorkflowWithReserveanitemRight(): void
    {
        $this->login();

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // 1. Test creation
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
            'comment' => 'Test reservation',
        ]);
        $this->assertGreaterThan(0, $reservation_id, "Should be able to create reservation with RESERVEANITEM right");
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // 2. Test reading - use canViewItem instead of can($id, READ)
        $this->assertTrue($reservation->canViewItem(), "Should be able to read own reservation");

        // 3. Test updating
        $this->assertTrue($reservation->can($reservation_id, UPDATE), "Should be able to update own reservation");
        $update_result = $reservation->update([
            'id' => $reservation_id,
            'comment' => 'Updated test reservation',
        ]);
        $this->assertTrue($update_result, "Update should succeed");
        $this->assertTrue($reservation->getFromDB($reservation_id));
        $this->assertEquals('Updated test reservation', $reservation->fields['comment']);

        // 4. Test deletion
        $this->assertTrue($reservation->can($reservation_id, PURGE), "Should be able to delete own reservation");
        $delete_result = $reservation->delete(['id' => $reservation_id], true);
        $this->assertTrue($delete_result, "Delete should succeed");
        $this->assertFalse($reservation->getFromDB($reservation_id));
    }

    /**
     * Test that permissions are properly checked for non-owner reservations
     */
    public function testNonOwnerReservationPermissions(): void
    {
        $this->login();

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Create a reservation owned by current user
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
            'comment' => 'User1 reservation',
        ]);
        $this->assertGreaterThan(0, $reservation_id);
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // Test that current user (owner) can access the reservation
        $this->assertTrue(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "User should be able to modify their own reservation"
        );

        // Simulate another user by changing the users_id in the reservation object
        $original_user_id = $reservation->fields['users_id'];
        $reservation->fields['users_id'] = $original_user_id + 1; // Different user ID

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;

        // User should NOT be able to update another user's reservation
        $this->assertFalse(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "User should not be able to modify another user's reservation with only RESERVEANITEM right"
        );

        // Restore original user ID to test that owner still has access
        $reservation->fields['users_id'] = $original_user_id;
        $this->assertTrue(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "Original user should still be able to modify their own reservation"
        );
    }

    /**
     * Test reservation permissions with different profile configurations
     * This specifically tests the simplified interface scenario that was broken
     */
    public function testSimplifiedInterfacePermissions(): void
    {
        global $DB;

        // Create a user with Self-Service profile (simplified interface)
        $user = new \User();
        $user_id = $user->add([
            'name' => 'test_simplified_user',
            'password' => 'test123',
            'password2' => 'test123',
        ]);
        $this->assertGreaterThan(0, $user_id);

        // Assign Self-Service profile with RESERVEANITEM right only
        $profile_user = new \Profile_User();
        $selfservice_profile_id = getItemByTypeName('Profile', 'Self-Service', true);
        $this->assertGreaterThan(
            0,
            $profile_user->add([
                'users_id' => $user_id,
                'profiles_id' => $selfservice_profile_id,
                'entities_id' => 0,
            ])
        );

        // Ensure Self-Service profile has RESERVEANITEM right
        $DB->update(
            'glpi_profilerights',
            ['rights' => \ReservationItem::RESERVEANITEM],
            [
                'profiles_id' => $selfservice_profile_id,
                'name' => 'reservation',
            ]
        );

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer simplified",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Login as the test user
        $this->login('test_simplified_user', 'test123');

        // Test that static permission methods work correctly
        $this->assertTrue((bool)\Reservation::canCreate(), "User with RESERVEANITEM should be able to create reservations");
        $this->assertTrue((bool)\Reservation::canUpdate(), "User with RESERVEANITEM should be able to update reservations");
        $this->assertTrue((bool)\Reservation::canPurge(), "User with RESERVEANITEM should be able to purge reservations");

        // Test creating a reservation
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
            'comment' => 'Simplified interface test',
        ]);
        $this->assertGreaterThan(0, $reservation_id, "Should be able to create reservation in simplified interface");

        // Test that the user can update their own reservation
        $this->assertTrue($reservation->getFromDB($reservation_id));
        $this->assertTrue($reservation->can($reservation_id, UPDATE), "User should be able to update their own reservation");

        // Test that the user can delete their own reservation
        $this->assertTrue($reservation->can($reservation_id, PURGE), "User should be able to delete their own reservation");
    }

    /**
     * Test that front file permission checks are properly delegated to class methods
     */
    public function testFrontFilePermissionDelegation(): void
    {
        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer front",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Create a reservation
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
            'comment' => 'Front delegation test',
        ]);
        $this->assertGreaterThan(0, $reservation_id);
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // Test that check() method properly handles RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;

        // These should not throw exceptions - delegation should work
        try {
            $reservation->check($reservation_id, UPDATE);
            $this->assertTrue(true, "check() method should work with RESERVEANITEM right for owner");
        } catch (\Exception $e) {
            $this->fail("check() method failed for owner with RESERVEANITEM right: " . $e->getMessage());
        }

        try {
            $reservation->check($reservation_id, PURGE);
            $this->assertTrue(true, "check() method should work with RESERVEANITEM right for owner");
        } catch (\Exception $e) {
            $this->fail("check() method failed for owner with RESERVEANITEM right: " . $e->getMessage());
        }
    }

    /**
     * Test handleAddForm method without explicit permission checks
     */
    public function testHandleAddFormWithoutExplicitPermissionChecks(): void
    {
        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer handleAdd",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Simulate form input
        $form_input = [
            'users_id' => $_SESSION['glpiID'],
            'resa' => [
                'begin' => '2024-01-01 10:00:00',
                'end' => '2024-01-01 12:00:00',
            ],
            'items' => [$res_item->getID()],
            'comment' => 'handleAddForm test',
        ];

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;

        // Count reservations before
        $reservation = new \Reservation();
        $count_before = count($reservation->find());

        // Call handleAddForm - should work without explicit permission checks
        \Reservation::handleAddForm($form_input);

        // Count reservations after
        $count_after = count($reservation->find());

        $this->assertEquals($count_before + 1, $count_after, "handleAddForm should create reservation without explicit permission checks");
    }

    /**
     * Test showForm method without explicit permission checks
     */
    public function testShowFormWithoutExplicitPermissionChecks(): void
    {
        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer showForm",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Create a reservation
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
            'comment' => 'showForm test',
        ]);
        $this->assertGreaterThan(0, $reservation_id);

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;

        // Test that showForm doesn't fail with explicit permission checks
        ob_start();
        $result = $reservation->showForm($reservation_id, ['item' => [$res_item->getID() => $res_item->getID()]]);
        ob_end_clean();

        $this->assertTrue($result, "showForm should work without explicit permission checks for owner");

        // Test showForm for creating new reservation
        ob_start();
        $result = $reservation->showForm(0, [
            'item' => [$res_item->getID() => $res_item->getID()],
            'begin' => '2024-01-02 10:00:00',
            'end' => '2024-01-02 12:00:00'
        ]);
        ob_end_clean();

        $this->assertTrue($result, "showForm should work for creating new reservations with RESERVEANITEM right");
    }

    /**
     * Test permission escalation through canChildItem for user ownership
     */
    public function testCanChildItemPermissionEscalation(): void
    {
        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer escalation",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Create a reservation
        $reservation = new \Reservation();
        $reservation_id = $reservation->add([
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
            'comment' => 'Permission escalation test',
        ]);
        $this->assertGreaterThan(0, $reservation_id);
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // Remove all reservation rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;

        // canChildItem should still grant rights to owner
        $this->assertTrue(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "Owner should have rights even without any reservation permissions through canChildItem"
        );

        // Test with different user - should work cause user have access to entity
        $original_user_id = $reservation->fields['users_id'];
        $reservation->fields['users_id'] = $original_user_id + 1;

        $this->assertTrue(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "Non-owner should not have rights without proper permissions"
        );
    }

    /**
     * Test calendar display permissions with RESERVEANITEM right
     */
    public function testCalendarDisplayPermissions(): void
    {
        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer calendar",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Test showCalendar with RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;

        ob_start();
        $result = \Reservation::showCalendar($res_item->getID());
        $output = ob_get_clean();

        $this->assertNotFalse($result, "showCalendar should work with RESERVEANITEM right");
        $this->assertNotEmpty($output, "showCalendar should produce output");

        // Test showCalendar with no rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;

        ob_start();
        $result = \Reservation::showCalendar($res_item->getID());
        $output = ob_get_clean();

        $this->assertFalse($result, "showCalendar should not work without any rights");
    }

    /**
     * Test the complete workflow that was broken in simplified interface
     * This test simulates the exact scenario described in the bug report
     */
    public function testCompleteSimplifiedInterfaceWorkflow(): void
    {
        global $DB;

        // Create test data
        $computer = $this->createItem("Computer", [
            "name" => "test simplified workflow",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => 0,
        ]);

        // Simulate simplified interface profile with only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = \ReservationItem::RESERVEANITEM;
        $_SESSION['glpiactiveprofile']['interface'] = 'helpdesk';

        // Test 1: Can access reservation form page
        // This simulates accessing front/reservation.form.php
        $reservation = new \Reservation();

        // Test form display (GET request simulation)
        ob_start();
        $result = $reservation->showForm(0, [
            'item' => [$res_item->getID() => $res_item->getID()],
            'begin' => '2024-01-01 10:00:00'
        ]);
        ob_end_clean();
        $this->assertTrue($result, "Should be able to display reservation form with RESERVEANITEM right");

        // Test 2: Can create reservation (POST add simulation)
        $form_data = [
            'users_id' => $_SESSION['glpiID'],
            'resa' => [
                'begin' => '2024-01-01 10:00:00',
                'end' => '2024-01-01 12:00:00',
            ],
            'items' => [$res_item->getID()],
            'comment' => 'Simplified interface workflow test',
        ];

        $count_before = count($reservation->find());
        \Reservation::handleAddForm($form_data);
        $count_after = count($reservation->find());
        $this->assertEquals($count_before + 1, $count_after, "Should be able to create reservation via form");

        // Get the created reservation
        $reservations = $reservation->find(['users_id' => $_SESSION['glpiID']], ['id DESC']);
        $this->assertNotEmpty($reservations, "Should find created reservation");
        $created_reservation = array_shift($reservations);
        $reservation_id = $created_reservation['id'];
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // Test 3: Can update own reservation (POST update simulation)
        $this->assertTrue($reservation->can($reservation_id, UPDATE), "Should be able to update own reservation");
        $update_result = $reservation->update([
            'id' => $reservation_id,
            'comment' => 'Updated via simplified interface',
        ]);
        $this->assertTrue($update_result, "Should be able to update own reservation");

        // Test 4: Can delete own reservation (POST purge simulation)
        $this->assertTrue($reservation->can($reservation_id, PURGE), "Should be able to delete own reservation");
        $delete_result = $reservation->delete(['id' => $reservation_id], true);
        $this->assertTrue($delete_result, "Should be able to delete own reservation");

        // Test 5: Verify all operations work through check() method (used by front files)
        // Create another reservation for testing check() method
        $reservation_id2 = $reservation->add([
            'begin' => '2024-01-02 10:00:00',
            'end' => '2024-01-02 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => $_SESSION['glpiID'],
            'comment' => 'Check method test',
        ]);
        $this->assertGreaterThan(0, $reservation_id2);

        // These should not throw exceptions
        try {
            $reservation->check($reservation_id2, UPDATE);
            $reservation->check($reservation_id2, PURGE);
            $this->assertTrue(true, "check() method should work for owner with RESERVEANITEM right");
        } catch (\Exception $e) {
            $this->fail("check() method should not throw exceptions for owner: " . $e->getMessage());
        }
    }
}
