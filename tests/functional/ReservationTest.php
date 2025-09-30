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

use Computer;
use DbTestCase;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\IsReservableCapacity;
use MassiveAction;
use PHPUnit\Framework\Attributes\DataProvider;
use ReservationItem;
use Session;

class ReservationTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: IsReservableCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['reservation_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Reservation$1', $tabs, $itemtype);
        }
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

        Session::changeActiveEntities(getItemByTypeName("Entity", "_test_child_1", true));
        // Nothing showing because we are now in a child entity and the computer is not recursive
        $this->assertCount(0, \Reservation::getReservableItemtypes());

        //Make computer recursive and check again
        $this->updateItem('Computer', $computer->getID(), [
            "is_recursive" => true,
        ]);
        $this->updateItem('ReservationItem', $reservation_item->getID(), [
            "is_recursive" => true,
        ]);
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

    #[DataProvider('dataAddReservationTest')]
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

        $this->createItem('Reservation', $data);
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

    public function testMassiveActions()
    {
        $this->login();

        $actions = MassiveAction::getAllMassiveActions(Computer::class);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $actions);

        $computer_template = $this->createItem(Computer::class, [
            'template_name' => __FUNCTION__ . '_template',
            'is_template' => 1,
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $computer = $this->createItem(Computer::class, [
            'name' => __FUNCTION__ . '_1',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);

        $template_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer_template, $computer_template->getID());
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $template_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $template_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $template_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $template_actions);

        $computer_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer, $computer->getID());
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $computer_actions);

        $ri = $this->createItem(ReservationItem::class, [
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'is_active' => 0,
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $computer_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer, $computer->getID());
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $computer_actions);

        $ri->update(['id' => $ri->getID(), 'is_active' => 1]);
        $computer_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer, $computer->getID());
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $computer_actions);
    }

    /**
     * Test that canUpdate method includes RESERVEANITEM right
     */
    public function testCanUpdateWithReserveanitemRight(): void
    {
        // Test with UPDATE right
        $_SESSION['glpiactiveprofile']['reservation'] = UPDATE;
        $this->assertTrue((bool) \Reservation::canUpdate());

        // Test with RESERVEANITEM right (simplified interface case)
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool) \Reservation::canUpdate());

        // Test with both rights
        $_SESSION['glpiactiveprofile']['reservation'] = UPDATE | ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool) \Reservation::canUpdate());

        // Test with unrelated right
        $_SESSION['glpiactiveprofile']['reservation'] = READ;
        $this->assertFalse((bool) \Reservation::canUpdate());

        // Test with no rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;
        $this->assertFalse((bool) \Reservation::canUpdate());
    }

    /**
     * Test that canPurge method includes RESERVEANITEM right
     */
    public function testCanPurgeWithReserveanitemRight(): void
    {
        $this->login();

        // Test with PURGE right
        $_SESSION['glpiactiveprofile']['reservation'] = PURGE;
        $this->assertTrue((bool) \Reservation::canPurge());

        // Test with RESERVEANITEM right (simplified interface case)
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool) \Reservation::canPurge());

        // Test with both rights
        $_SESSION['glpiactiveprofile']['reservation'] = PURGE | ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool) \Reservation::canPurge());

        // Test with unrelated right
        $_SESSION['glpiactiveprofile']['reservation'] = READ;
        $this->assertFalse((bool) \Reservation::canPurge());

        // Test with no rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;
        $this->assertFalse((bool) \Reservation::canPurge());
    }

    /**
     * Test that canCreate and canDelete methods work with RESERVEANITEM right
     */
    public function testCanCreateAndDeleteWithReserveanitemRight(): void
    {
        $this->login();

        // Test canCreate with RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool) \Reservation::canCreate(), "canCreate should return truthy value with RESERVEANITEM right");

        // Test canDelete with RESERVEANITEM right - canDelete only checks RESERVEANITEM
        $this->assertTrue((bool) \Reservation::canDelete(), "canDelete should return truthy value with RESERVEANITEM right");

        // Test with no rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;
        $this->assertFalse((bool) \Reservation::canCreate());
        $this->assertFalse((bool) \Reservation::canDelete());
    }

    /**
     * Test canChildItem method for reservation ownership
     */
    public function testCanChildItemOwnership(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create a reservation owned by current user
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
        ]);

        // Test that owner has rights even with minimal permissions
        $_SESSION['glpiactiveprofile']['reservation'] = 0; // No rights at all
        $this->assertTrue($reservation->canChildItem('canUpdateItem', 'canUpdate'));

        // Test with different user
        $reservation->fields['users_id'] = Session::getLoginUserID() + 1; // Different user ID
        $this->assertFalse($reservation->canChildItem('canUpdateItem', 'canUpdate'));
    }

    /**
     * Test entity access in canChildItem
     */
    public function testCanChildItemEntityAccess(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

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
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
        ]);

        // Test access from root entity (should work due to hierarchy)
        Session::changeActiveEntities(0);
        $this->assertTrue($reservation->canChildItem('canUpdateItem', 'canUpdate'));

        // Test access from child entity
        Session::changeActiveEntities($child_entity);
        $this->assertTrue($reservation->canChildItem('canUpdateItem', 'canUpdate'));

        // Test access from unrelated entity (should still work because user is owner)
        // canChildItem grants rights to owner regardless of entity restrictions
        $other_entity = getItemByTypeName("Entity", "_test_child_2", true);
        Session::changeActiveEntities($other_entity);
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

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;

        // 1. Test creation
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'Test reservation',
        ]);

        // 2. Test reading - use canViewItem instead of can($id, READ)
        $this->assertTrue($reservation->canViewItem(), "Should be able to read own reservation");

        // 3. Test updating
        $this->assertTrue($reservation->can($reservation->getID(), UPDATE), "Should be able to update own reservation");
        $this->updateItem('Reservation', $reservation->getID(), [
            'comment' => 'Updated test reservation',
        ]);
        $this->assertTrue($reservation->getFromDB($reservation->getID()));
        $this->assertEquals('Updated test reservation', $reservation->fields['comment']);

        // 4. Test deletion
        $this->assertTrue($reservation->can($reservation->getID(), PURGE), "Should be able to delete own reservation");
        $delete_result = $reservation->delete(['id' => $reservation->getID()], true);
        $this->assertTrue($delete_result, "Delete should succeed");
        $this->assertFalse($reservation->getFromDB($reservation->getID()));
    }

    /**
     * Test that permissions are properly checked for non-owner reservations
     */
    public function testNonOwnerReservationPermissions(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create a reservation owned by current user
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'User1 reservation',
        ]);

        // Test that current user (owner) can access the reservation
        $this->assertTrue(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "User should be able to modify their own reservation"
        );

        // Simulate another user by changing the users_id in the reservation object
        $original_user_id = $reservation->fields['users_id'];
        $reservation->fields['users_id'] = $original_user_id + 1; // Different user ID

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;

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
            ['rights' => ReservationItem::RESERVEANITEM],
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
        $this->assertTrue((bool) \Reservation::canCreate(), "User with RESERVEANITEM should be able to create reservations");
        $this->assertTrue((bool) \Reservation::canUpdate(), "User with RESERVEANITEM should be able to update reservations");
        $this->assertTrue((bool) \Reservation::canPurge(), "User with RESERVEANITEM should be able to purge reservations");

        // Test creating a reservation
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'Simplified interface test',
        ]);

        // Test that the user can update their own reservation
        $this->assertTrue($reservation->getFromDB($reservation->getID()));
        $this->assertTrue($reservation->can($reservation->getID(), UPDATE), "User should be able to update their own reservation");

        // Test that the user can delete their own reservation
        $this->assertTrue($reservation->can($reservation->getID(), PURGE), "User should be able to delete their own reservation");
    }

    /**
     * Test handleAddForm method without explicit permission checks
     */
    public function testHandleAddFormWithoutExplicitPermissionChecks(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer handleAdd",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Simulate form input
        $form_input = [
            'users_id' => Session::getLoginUserID(),
            'resa' => [
                'begin' => '2024-01-01 10:00:00',
                'end' => '2024-01-01 12:00:00',
            ],
            'items' => [$res_item->getID()],
            'comment' => 'handleAddForm test',
        ];

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;

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
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer showForm",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create a reservation
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'showForm test',
        ]);

        // Set only RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;

        // Test that showForm doesn't fail with explicit permission checks
        ob_start();
        $result = $reservation->showForm($reservation->getID(), ['item' => [$res_item->getID() => $res_item->getID()]]);
        ob_end_clean();

        $this->assertTrue($result, "showForm should work without explicit permission checks for owner");

        // Test showForm for creating new reservation
        ob_start();
        $result = $reservation->showForm(0, [
            'item' => [$res_item->getID() => $res_item->getID()],
            'begin' => '2024-01-02 10:00:00',
            'end' => '2024-01-02 12:00:00',
        ]);
        ob_end_clean();

        $this->assertTrue($result, "showForm should work for creating new reservations with RESERVEANITEM right");
    }

    /**
     * Test permission escalation through canChildItem for user ownership
     */
    public function testCanChildItemPermissionEscalation(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer escalation",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create a reservation
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'Permission escalation test',
        ]);

        // Remove all reservation rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;

        // canChildItem should still grant rights to owner
        $this->assertTrue(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "Owner should have rights even without any reservation permissions through canChildItem"
        );

        // canChildItem does not grant right if not owner
        $original_user_id = $reservation->fields['users_id'];
        $reservation->fields['users_id'] = $original_user_id + 1;

        $this->assertFalse(
            $reservation->canChildItem('canUpdateItem', 'canUpdate'),
            "Non-owner should not have rights without proper permissions"
        );
    }

    /**
     * Test calendar display permissions with RESERVEANITEM right
     */
    public function testCalendarDisplayPermissions(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer calendar",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Test showCalendar with RESERVEANITEM right
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;

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
        $this->login('post-only', 'postonly');

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
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;
        $_SESSION['glpiactiveprofile']['interface'] = 'helpdesk';

        // Test 1: Can access reservation form page
        // This simulates accessing front/reservation.form.php
        $reservation = new \Reservation();

        // Test form display (GET request simulation)
        ob_start();
        $result = $reservation->showForm(0, [
            'item' => [$res_item->getID() => $res_item->getID()],
            'begin' => '2024-01-01 10:00:00',
        ]);
        ob_end_clean();
        $this->assertTrue($result, "Should be able to display reservation form with RESERVEANITEM right");

        // Test 2: Can create reservation (POST add simulation)
        $form_data = [
            'users_id' => Session::getLoginUserID(),
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
        $reservations = $reservation->find(['users_id' => Session::getLoginUserID()], ['id DESC']);
        $this->assertNotEmpty($reservations, "Should find created reservation");
        $created_reservation = array_shift($reservations);
        $reservation_id = $created_reservation['id'];
        $this->assertTrue($reservation->getFromDB($reservation_id));

        // Test 3: Can update own reservation (POST update simulation)
        $this->assertTrue($reservation->can($reservation_id, UPDATE), "Should be able to update own reservation");
        $this->updateItem('Reservation', $reservation_id, [
            'comment' => 'Updated via simplified interface',
        ]);
        $this->assertTrue($reservation->getFromDB($reservation_id));
        $this->assertEquals('Updated via simplified interface', $reservation->fields['comment']);

        // Test 4: Can delete own reservation (POST purge simulation)
        $this->assertTrue($reservation->can($reservation_id, PURGE), "Should be able to delete own reservation");
        $delete_result = $reservation->delete(['id' => $reservation_id], true);
        $this->assertTrue($delete_result, "Should be able to delete own reservation");
    }

    /**
     * Test that users without any rights cannot access reservations
     */
    public function testNoRightsCannotAccessReservations(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer no rights",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create a reservation
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'No rights test',
        ]);

        // Remove all rights
        $_SESSION['glpiactiveprofile']['reservation'] = 0;

        // Test static methods return false
        $this->assertFalse((bool) \Reservation::canCreate());
        $this->assertFalse((bool) \Reservation::canUpdate());
        $this->assertFalse((bool) \Reservation::canPurge());

        // Test instance methods also return false without global rights (expected behavior)
        $this->assertFalse($reservation->can($reservation->getID(), UPDATE), "Without global rights, even owner cannot update");
        $this->assertFalse($reservation->can($reservation->getID(), PURGE), "Without global rights, even owner cannot purge");

        // Test non-owner cannot access
        $original_user_id = $reservation->fields['users_id'];
        $reservation->fields['users_id'] = $original_user_id + 1;

        $this->assertFalse($reservation->can($reservation->getID(), UPDATE), "Non-owner should not have rights without permissions");
        $this->assertFalse($reservation->can($reservation->getID(), PURGE), "Non-owner should not have rights without permissions");
    }

    /**
     * Test canView permissions according to Curtis's recommendations
     */
    public function testCanViewPermissions(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer view permissions",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create a reservation owned by current user
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'View permissions test',
        ]);

        // Test 1: Users with READ right can see all reservations
        $_SESSION['glpiactiveprofile']['reservation'] = READ;
        $this->assertTrue((bool) \Reservation::canView(), "Users with READ should be able to view all reservations");
        $this->assertTrue($reservation->canViewItem(), "Users with READ should be able to view specific reservations");

        // Test 2: Users with RESERVEANITEM right can see their own reservations
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;
        $this->assertTrue((bool) \Reservation::canView(), "Users with RESERVEANITEM should be able to view reservations");
        $this->assertTrue($reservation->canViewItem(), "Users with RESERVEANITEM should be able to view their own reservations");

        // Test 3: Users without any rights cannot see others' reservations
        $_SESSION['glpiactiveprofile']['reservation'] = 0;
        $this->assertFalse((bool) \Reservation::canView(), "Users without rights should not be able to view reservations");

        // But they can still see their own reservations through canViewItem
        $this->assertTrue($reservation->canViewItem(), "Users should always be able to view their own reservations");

        // Test 4: Change to different user - should not be able to see
        $original_user_id = $reservation->fields['users_id'];
        $reservation->fields['users_id'] = $original_user_id + 1;
        $this->assertFalse($reservation->canViewItem(), "Different user should not be able to view others' reservations without rights");

        // Put back asset update rights
        $reservation->fields['users_id'] = $original_user_id;
        $_SESSION['glpiactiveprofile']['computer'] = UPDATE;
        $this->assertTrue($reservation->canViewItem(), "Original user should still be able to view their own reservation");
    }

    /**
     * Test permissions for users with asset update rights
     */
    public function testAssetUpdateRightsPermissions(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer asset rights",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create a reservation owned by another user
        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID() + 1, // Different user
            'comment' => 'Asset rights test',
        ]);

        // Test case 1: User with computer UPDATE rights but no reservation rights should be able to manage reservations for that asset
        $_SESSION['glpiactiveprofile']['reservation'] = 0; // No reservation rights
        $_SESSION['glpiactiveprofile']['computer'] = UPDATE; // User can update computers

        // Users with permission to update the asset should be able to CRUD all reservations for that asset
        $this->assertTrue($reservation->canViewItem(), "User with asset update rights should be able to view reservations for that asset");
        $this->assertTrue($reservation->canUpdateItem(), "User with asset update rights should be able to update reservations for that asset");
        $this->assertTrue($reservation->canDeleteItem(), "User with asset update rights should be able to delete reservations for that asset");
        $this->assertTrue($reservation->canPurgeItem(), "User with asset update rights should be able to purge reservations for that asset");

        // Test case 2: User with only RESERVEANITEM + computer rights should NOT work for others' reservations
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;
        $_SESSION['glpiactiveprofile']['computer'] = UPDATE; // User can update computers

        $this->assertFalse($reservation->canViewItem(), "User with only RESERVEANITEM should not view others' reservations even with asset rights");
        $this->assertFalse($reservation->canUpdateItem(), "User with only RESERVEANITEM should not update others' reservations even with asset rights");
    }

    /**
     * Test creating reservations for other users with CREATE right
     */
    public function testCreateForOtherUsers(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer create for others",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Test with CREATE right - should be able to create for other users
        $_SESSION['glpiactiveprofile']['reservation'] = CREATE;

        $this->assertTrue((bool) \Reservation::canCreate(), "Users with CREATE should be able to create reservations");

        $reservation = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID() + 1, // Different user
            'comment' => 'Created for another user',
        ]);
        $this->assertGreaterThan(0, $reservation->getID(), "Users with CREATE right should be able to create reservations for other users");

        // Test with only RESERVEANITEM right - traditionally only for self
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;

        $this->assertTrue((bool) \Reservation::canCreate(), "Users with RESERVEANITEM should be able to create reservations");

        $reservation2 = $this->createItem('Reservation', [
            'begin' => '2024-01-02 10:00:00',
            'end' => '2024-01-02 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(), // Own reservation
            'comment' => 'Self reservation',
        ]);
        $this->assertGreaterThan(0, $reservation2->getID(), "Users with RESERVEANITEM right should be able to create their own reservations");
    }

    /**
     * Test UPDATE and PURGE permissions work correctly
     */
    public function testUpdateAndPurgePermissions(): void
    {
        $this->login();

        $root_entity_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create a computer and reservation item
        $computer = $this->createItem("Computer", [
            "name" => "test computer update purge",
            "entities_id" => $root_entity_id,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype" => "Computer",
            "items_id" => $computer->getID(),
            "is_active" => true,
            "entities_id" => $root_entity_id,
        ]);

        // Create reservations for testing
        $reservation1 = $this->createItem('Reservation', [
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID(),
            'comment' => 'Own reservation',
        ]);

        $reservation2 = $this->createItem('Reservation', [
            'begin' => '2024-01-02 10:00:00',
            'end' => '2024-01-02 12:00:00',
            'reservationitems_id' => $res_item->getID(),
            'users_id' => Session::getLoginUserID() + 1, // Different user
            'comment' => 'Others reservation',
        ]);

        // Test with UPDATE right - should be able to update any reservation they can read (if they can view the reserved item)
        $_SESSION['glpiactiveprofile']['reservation'] = UPDATE | READ;
        $_SESSION['glpiactiveprofile']['computer'] = READ;

        $this->assertTrue((bool) \Reservation::canUpdate(), "Users with UPDATE should be able to update reservations");
        $this->assertTrue($reservation1->canUpdateItem(), "Users with UPDATE should be able to update own reservations");
        $this->assertTrue($reservation2->canUpdateItem(), "Users with UPDATE should be able to update others' reservations they can read");

        // Test with PURGE right - should be able to purge any reservation they can read (if they can view the reserved item)
        $_SESSION['glpiactiveprofile']['reservation'] = PURGE | READ;
        $_SESSION['glpiactiveprofile']['computer'] = READ;

        $this->assertTrue((bool) \Reservation::canPurge(), "Users with PURGE should be able to purge reservations");
        $this->assertTrue($reservation1->canPurgeItem(), "Users with PURGE should be able to purge own reservations");
        $this->assertTrue($reservation2->canPurgeItem(), "Users with PURGE should be able to purge others' reservations they can read");

        // Test with only RESERVEANITEM - should only work on own reservations
        $_SESSION['glpiactiveprofile']['reservation'] = ReservationItem::RESERVEANITEM;
        $_SESSION['glpiactiveprofile']['computer'] = 0; // Prevent assets checks to alter results

        $this->assertTrue((bool) \Reservation::canUpdate(), "Users with RESERVEANITEM should be able to update reservations");
        $this->assertTrue((bool) \Reservation::canPurge(), "Users with RESERVEANITEM should be able to purge reservations");
        $this->assertTrue($reservation1->canUpdateItem(), "Users with RESERVEANITEM should be able to update own reservations");
        $this->assertTrue($reservation1->canPurgeItem(), "Users with RESERVEANITEM should be able to purge own reservations");
        $this->assertFalse($reservation2->canUpdateItem(), "Users with only RESERVEANITEM should not be able to update others' reservations");
        $this->assertFalse($reservation2->canPurgeItem(), "Users with only RESERVEANITEM should not be able to purge others' reservations");
    }
}
