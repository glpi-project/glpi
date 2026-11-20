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

use Change;
use CommonITILObject;
use Computer;
use Entity;
use Glpi\Tests\DbTestCase;
use Item_Ticket;
use ITILCategory;
use ITILFollowup;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Session;
use Ticket;
use Transfer;
use User;

/**
 * Updates of closed ITIL objects: only a transfer may change the entity, location and category,
 * only a reopening may change the status.
 *
 * The result of `update()` is not checked: a fully filtered input makes it fail, while a partly
 * filtered one succeeds. Only the values stored in database matter.
 */
class CommonITILObjectClosedUpdateTest extends DbTestCase
{
    #[DataProvider('itilClassProvider')]
    public function testTransferClosedTicket(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity');
        [$new_category, $new_location] = $this->createRecursiveDropdowns();
        $new_entities_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();
        assert((bool) Session::haveRight(Transfer::$rightname, READ), 'Transfer right is required: all the transfer conditions must be met');
        assert(Session::haveAccessToEntity($new_entities_id), 'Target entity access is required: all the transfer conditions must be met');

        // --- act ---
        $itil->update([
            'id' => $itil->getID(),
            '_transfer' => 1,
            'entities_id' => $new_entities_id,
            'itilcategories_id' => $new_category->getID(),
            'locations_id' => $new_location->getID(),
        ]);

        // --- assert changes applied ---
        $updated = $this->reloadItem($itil);
        $this->assertSame($new_entities_id, $updated->fields['entities_id'], 'Entity should be changed');
        $this->assertSame($new_category->getID(), $updated->fields['itilcategories_id'], 'Category should be changed');
        $this->assertSame($new_location->getID(), $updated->fields['locations_id'], 'Location should be changed');
    }

    #[DataProvider('itilClassProvider')]
    public function testUpdateClosedTicketWithUpdateRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity');
        [$new_category, $new_location] = $this->createRecursiveDropdowns();
        $new_entities_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();
        $initial_entities_id = $itil->fields['entities_id'];
        assert((bool) Session::haveRight($itil_class::$rightname, UPDATE), 'Update right is required: category and location may be changed with it (e.g. replacement of a deleted dropdown value)');

        // --- act ---
        $itil->update([
            'id' => $itil->getID(),
            'entities_id' => $new_entities_id,
            'itilcategories_id' => $new_category->getID(),
            'locations_id' => $new_location->getID(),
        ]);

        // --- assert ---
        $updated = $this->reloadItem($itil);
        $this->assertSame($initial_entities_id, $updated->fields['entities_id'], 'Entity should not be changed outside a transfer');
        $this->assertSame($new_category->getID(), $updated->fields['itilcategories_id'], 'Category should be changed');
        $this->assertSame($new_location->getID(), $updated->fields['locations_id'], 'Location should be changed');
    }

    #[DataProvider('itilClassProvider')]
    public function testUpdateClosedTicketWithoutUpdateRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity', [
            '_users_id_requester' => getItemByTypeName(User::class, 'post-only')->getID(),
        ]);
        [$new_category, $new_location] = $this->createRecursiveDropdowns();
        $new_entities_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();
        $initial_entities_id = $itil->fields['entities_id'];
        $initial_itilcategories_id = $itil->fields['itilcategories_id'];
        $initial_locations_id = $itil->fields['locations_id'];
        $this->login('post-only');
        assert(!Session::haveRight($itil_class::$rightname, UPDATE), 'Update right must be missing: a requester cannot change the category or location of its closed ticket');

        // --- act ---
        $itil->update([
            'id' => $itil->getID(),
            'entities_id' => $new_entities_id,
            'itilcategories_id' => $new_category->getID(),
            'locations_id' => $new_location->getID(),
        ]);

        // --- assert ---
        $updated = $this->reloadItem($itil);
        $this->assertSame($initial_entities_id, $updated->fields['entities_id'], 'Entity should not be changed');
        $this->assertSame($initial_itilcategories_id, $updated->fields['itilcategories_id'], 'Category should not be changed');
        $this->assertSame($initial_locations_id, $updated->fields['locations_id'], 'Location should not be changed');
    }

    #[DataProvider('itilClassProvider')]
    public function testTransferClosedTicketWithoutTransferRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity');
        $_SESSION['glpiactiveprofile'][Transfer::$rightname] = 0;
        $initial_entities_id = $itil->fields['entities_id'];
        $new_entities_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();

        // --- act ---
        $itil->update([
            'id' => $itil->getID(),
            '_transfer' => 1,
            'entities_id' => $new_entities_id,
        ]);

        // --- assert ---
        $this->assertSame($initial_entities_id, $this->reloadItem($itil)->fields['entities_id'], 'Entity should not be changed');
    }

    #[DataProvider('itilClassProvider')]
    public function testTransferClosedTicketToInaccessibleEntity(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_child_1');
        $this->setEntity('_test_child_1', false);
        $child_2_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();
        $parent_id = $this->getTestRootEntity(true);
        assert(!Session::haveAccessToEntity($child_2_id));
        assert(!Session::haveAccessToEntity($parent_id), 'Parent entity must not be accessible: only the active entity is');
        assert((bool) Session::haveRight(Transfer::$rightname, READ), 'Transfer right is required: only the target entity access must block the transfer');
        $initial_entities_id = $itil->fields['entities_id'];

        // --- act + assert ---
        foreach ([$child_2_id, $parent_id, 999999] as $new_entities_id) {
            $itil->update(['id' => $itil->getID(), '_transfer' => 1, 'entities_id' => $new_entities_id]);
            $this->assertSame($initial_entities_id, $this->reloadItem($itil)->fields['entities_id'], "Entity should not be changed to inaccessible entity $new_entities_id");
        }
    }

    #[DataProvider('itilClassProvider')]
    public function testTransferClosedTicketWithTransferTool(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity');
        $initial_locations_id = $itil->fields['locations_id'];
        $new_entities_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();
        $transfer = getItemByTypeName(Transfer::class, 'complete');
        $options = ['keep_location' => 1] + $transfer->fields;
        assert((bool) Session::haveRight(Transfer::$rightname, READ), 'Transfer right is required: a legitimate transfer must still move a closed item');

        // --- act ---
        $transfer->moveItems([$itil_class => [$itil->getID() => $itil->getID()]], $new_entities_id, $options);

        // --- assert ---
        $transferred = $this->reloadItem($itil);
        $this->assertSame($new_entities_id, $transferred->fields['entities_id'], 'Entity should be changed');
        $this->assertSame($initial_locations_id, $transferred->fields['locations_id'], 'Location should be kept (keep_location option)');
        $this->assertSame(CommonITILObject::CLOSED, $transferred->fields['status'], 'Status should not be changed');
    }

    /**
     * A computer is transferred with its tickets: its closed ticket must follow it to the new entity.
     */
    public function testTransferAssetMovesItsClosedTicket(): void
    {
        // --- arrange ---
        $this->login();
        $initial_entities_id = $this->getTestRootEntity(true);
        $computer = $this->createItem(Computer::class, ['entities_id' => $initial_entities_id] + $this->getMinimalCreationInput(Computer::class));
        $ticket = $this->createClosedItil(Ticket::class, '_test_root_entity');
        $item_ticket = $this->createItem(Item_Ticket::class, [
            'tickets_id' => $ticket->getID(),
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
        ]);
        $new_entities_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();
        $transfer = getItemByTypeName(Transfer::class, 'complete');
        // keep_ticket = 2: the tickets linked to the computer are transferred with it
        $options = ['keep_ticket' => 2] + $transfer->fields;
        assert((bool) Session::haveRight(Transfer::$rightname, READ), 'Transfer right is required: a legitimate transfer must still move a closed ticket');
        assert($this->reloadItem($ticket)->fields['status'] === CommonITILObject::CLOSED, 'The ticket must be closed: only a closed ticket refuses an entity change outside a transfer');
        assert($ticket->fields['entities_id'] === $initial_entities_id, 'The ticket must be in the computer entity before the transfer');
        assert($computer->fields['entities_id'] === $initial_entities_id, 'The computer must be in the initial entity before the transfer');

        // --- act ---
        $transfer->moveItems([Computer::class => [$computer->getID() => $computer->getID()]], $new_entities_id, $options);

        // --- assert ---
        // computer moved
        $this->assertSame($new_entities_id, $this->reloadItem($computer)->fields['entities_id'], 'Computer should be transferred to the new entity');
        // ticket moved
        $transferred_ticket = $this->reloadItem($ticket);
        $this->assertSame($new_entities_id, $transferred_ticket->fields['entities_id'], 'Closed ticket should follow the computer to the new entity');
        $this->assertSame(CommonITILObject::CLOSED, $transferred_ticket->fields['status'], 'Status should not be changed');
        // association still correct
        $link = $this->reloadItem($item_ticket);
        $this->assertSame(Computer::class, $link->fields['itemtype'], 'Ticket should still be linked to the computer');
        $this->assertSame($computer->getID(), $link->fields['items_id'], 'Ticket should still be linked to the computer');
    }

    /**
     * A category and a location are deleted and replaced by other values.
     * The closed ITIL object that used them gets the new values,
     * even if the user who deletes them cannot update ITIL objects.
     */
    #[DataProvider('itilClassProvider')]
    public function testDropdownReplacementUpdatesClosedItemWithoutUpdateRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity');
        $initial_category = ITILCategory::getById($itil->fields['itilcategories_id']);
        $initial_location = Location::getById($itil->fields['locations_id']);
        [$new_category, $new_location] = $this->createRecursiveDropdowns();
        $_SESSION['glpiactiveprofile'][$itil_class::$rightname] = 0;
        assert(!Session::haveRight($itil_class::$rightname, UPDATE), 'Update right must be missing: the user can purge dropdown values but not update the closed item');

        // --- act ---
        // deleteItem() cannot pass the `_replace_by` input
        $initial_category->delete(['id' => $initial_category->getID(), '_replace_by' => $new_category->getID()], true);
        $initial_location->delete(['id' => $initial_location->getID(), '_replace_by' => $new_location->getID()], true);

        // --- assert ---
        $updated = $this->reloadItem($itil);
        $this->assertSame($new_category->getID(), $updated->fields['itilcategories_id'], 'Category should be replaced');
        $this->assertSame($new_location->getID(), $updated->fields['locations_id'], 'Location should be replaced');
    }

    #[DataProvider('reopenByFollowupProvider')]
    public function testFollowupReopensClosedItem(string $itil_class, bool $with_assignee, int $expected_status): void
    {
        // --- arrange ---
        $this->login();
        $input = $with_assignee ? ['_users_id_assign' => getItemByTypeName(User::class, 'tech')->getID()] : [];
        $itil = $this->createClosedItil($itil_class, '_test_root_entity', $input);

        // --- act ---
        $this->addReopeningFollowup($itil);

        // --- assert ---
        $this->assertSame($expected_status, $this->reloadItem($itil)->fields['status'], 'Status should be reopened by the followup');
    }

    public function testRequesterReopensClosedTicketWithFollowup(): void
    {
        // --- arrange ---
        $this->login();
        $ticket = $this->createClosedItil(Ticket::class, '_test_root_entity', [
            '_users_id_requester' => getItemByTypeName(User::class, 'post-only')->getID(),
        ]);
        $this->login('post-only');
        // Self-Service cannot reopen with its default status matrix
        $_SESSION['glpiactiveprofile'][Ticket::STATUS_MATRIX_FIELD][CommonITILObject::CLOSED][CommonITILObject::INCOMING] = 1;
        assert($this->reloadItem($ticket)->canReopen());

        // --- act ---
        $this->addReopeningFollowup($ticket);

        // --- assert ---
        $this->assertSame(CommonITILObject::INCOMING, $this->reloadItem($ticket)->fields['status'], 'Status should be reopened by the requester followup');
    }

    #[DataProvider('reopeningStatusProvider')]
    public function testReopenClosedTicket(string $itil_class, int $new_status): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity');
        assert($this->reloadItem($itil)->canReopen(), 'Reopen conditions are required: followup right and status matrix must allow it');

        // --- act ---
        $itil->update(['id' => $itil->getID(), 'status' => $new_status]);

        // --- assert ---
        $this->assertSame($new_status, $this->reloadItem($itil)->fields['status'], 'Status should be reopened');
    }

    /**
     * Conditions to reopen a ticket : https://help.glpi-project.org/faq/glpi/ticket#what-are-the-conditions-for-reopening-a-ticket
     *  - You must be able to create follow-ups
     *  - The status of the ticket is closed
     *  - To be able to pass a ticket in 'new' status or in 'assigned' status
     */
    #[DataProvider('missingReopeningConditionProvider')]
    public function testReopenClosedTicketWithoutReopenConditions(string $itil_class, int $new_status, ?string $restrict): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createClosedItil($itil_class, '_test_root_entity');
        match ($restrict) {
            'followup_right' => $_SESSION['glpiactiveprofile'][ITILFollowup::$rightname] = 0,
            'status_matrix'  => $_SESSION['glpiactiveprofile'][$itil_class::STATUS_MATRIX_FIELD][CommonITILObject::CLOSED][$new_status] = 0,
            null => null,
        };

        // --- act ---
        $itil->update(['id' => $itil->getID(), 'status' => $new_status]);

        // --- assert ---
        $this->assertSame(CommonITILObject::CLOSED, $this->reloadItem($itil)->fields['status'], "Status should not be changed (case $restrict)");
    }

    public static function itilClassProvider(): iterable
    {
        yield Ticket::class => ['itil_class' => Ticket::class];
        yield Change::class => ['itil_class' => Change::class];
        yield Problem::class => ['itil_class' => Problem::class];
    }

    public static function reopenByFollowupProvider(): iterable
    {
        // Change has no ASSIGNED status
        $expected_with_assignee = [
            Ticket::class => CommonITILObject::ASSIGNED,
            Change::class => CommonITILObject::INCOMING,
            Problem::class => CommonITILObject::ASSIGNED,
        ];
        foreach ($expected_with_assignee as $itil_class => $status_with_assignee) {
            yield "$itil_class without assignee" => [
                'itil_class' => $itil_class,
                'with_assignee' => false,
                'expected_status' => CommonITILObject::INCOMING,
            ];
            yield "$itil_class with assignee" => [
                'itil_class' => $itil_class,
                'with_assignee' => true,
                'expected_status' => $status_with_assignee,
            ];
        }
    }

    public static function reopeningStatusProvider(): iterable
    {
        foreach ([Ticket::class, Change::class, Problem::class] as $itil_class) {
            yield "$itil_class to incoming" => [
                'itil_class' => $itil_class,
                'new_status' => CommonITILObject::INCOMING,
            ];
        }
        // Change has no ASSIGNED status
        foreach ([Ticket::class, Problem::class] as $itil_class) {
            yield "$itil_class to assigned" => [
                'itil_class' => $itil_class,
                'new_status' => CommonITILObject::ASSIGNED,
            ];
        }
    }

    public static function missingReopeningConditionProvider(): iterable
    {
        foreach ([Ticket::class, Change::class, Problem::class] as $itil_class) {
            yield "$itil_class not a reopening status" => [
                'itil_class' => $itil_class,
                'new_status' => CommonITILObject::WAITING,
                'restrict' => null,
            ];
            yield "$itil_class without followup right" => [
                'itil_class' => $itil_class,
                'new_status' => CommonITILObject::INCOMING,
                'restrict' => 'followup_right',
            ];
            yield "$itil_class forbidden by the status matrix" => [
                'itil_class' => $itil_class,
                'new_status' => CommonITILObject::INCOMING,
                'restrict' => 'status_matrix',
            ];
        }
    }

    /**
     * Category and location are created recursive in the item entity, so a transfer may keep them.
     *
     * @param class-string<CommonITILObject> $itil_class
     */
    private function createClosedItil(string $itil_class, string $entity_name, array $input = []): CommonITILObject
    {
        assert(is_a($itil_class, CommonITILObject::class, true), 'Only CommonITILObject class is allowed in ' . __FUNCTION__);

        $entity_id = getItemByTypeName(Entity::class, $entity_name)->getID();
        $dropdown_input = ['entities_id' => $entity_id, 'is_recursive' => 1];
        $category = $this->createItem(ITILCategory::class, $dropdown_input + $this->getMinimalCreationInput(ITILCategory::class));
        $location = $this->createItem(Location::class, $dropdown_input + $this->getMinimalCreationInput(Location::class));
        $item = $this->createItem($itil_class, $input + [
            'entities_id' => $entity_id,
            'itilcategories_id' => $category->getID(),
            'locations_id' => $location->getID(),
        ] + $this->getMinimalCreationInput($itil_class));

        return $this->updateItem($itil_class, $item->getID(), ['status' => CommonITILObject::CLOSED]);
    }

    /**
     * New category and location, recursive in the root entity so they are valid in any entity.
     *
     * @return array{ITILCategory, Location}
     */
    private function createRecursiveDropdowns(): array
    {
        $dropdown_input = [
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ];

        return [
            $this->createItem(ITILCategory::class, $dropdown_input + $this->getMinimalCreationInput(ITILCategory::class)),
            $this->createItem(Location::class, $dropdown_input + $this->getMinimalCreationInput(Location::class)),
        ];
    }

    private function addReopeningFollowup(CommonITILObject $item): void
    {
        $this->createItem(ITILFollowup::class, [
            'itemtype' => $item::class,
            'items_id' => $item->getID(),
            'content' => 'Reopen',
            'add_reopen' => 1,
        ], ['add_reopen']);
    }
}
