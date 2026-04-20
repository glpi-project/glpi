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

namespace tests\unit\Glpi\Knowbase\History;

use Computer;
use Document;
use Document_Item;
use Entity;
use Entity_KnowbaseItem;
use Glpi\Form\Category;
use Glpi\Knowbase\History\CreationEvent;
use Glpi\Knowbase\History\CurrentTranslationEvent;
use Glpi\Knowbase\History\HistoryBuilder;
use Glpi\Knowbase\History\LogEvent;
use Glpi\Knowbase\History\RevisionEvent;
use Glpi\Knowbase\History\TranslationRevisionEvent;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Item;
use KnowbaseItem_Revision;
use KnowbaseItem_User;
use KnowbaseItemTranslation;
use Session;
use Ticket;
use User;

final class HistoryBuilderTest extends DbTestCase
{
    public function testNewKnowbaseItemReturnsCreationEvent(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(CreationEvent::class, $events[0]);
        $this->assertEquals("2026-01-15 10:00:00", $events[0]->getDate());
        $this->assertEquals(2, $events[0]->getAuthor());
        $this->assertEquals("Current version", $events[0]->getLabel());
        $this->assertEquals("Created by", $events[0]->getDescription());
    }

    public function testUpdatedKnowbaseItemReturnsLogEventAndRevision(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Original title',
            'answer' => 'Original content',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Updated content',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(2, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("2026-01-15 12:00:00", $events[0]->getDate());

        $this->assertInstanceOf(RevisionEvent::class, $events[1]);
        $this->assertEquals("2026-01-15 10:00:00", $events[1]->getDate());
        $this->assertEquals(2, $events[1]->getAuthor());
    }

    public function testMultipleRevisionsAreOrderedCorrectly(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Version 1 content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Version 2 content',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Version 3 content',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(3, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);

        $this->assertInstanceOf(RevisionEvent::class, $events[1]);
        $this->assertEquals("Version 2", $events[1]->getLabel());
        $this->assertEquals("2026-01-15 11:00:00", $events[1]->getDate());

        $this->assertInstanceOf(RevisionEvent::class, $events[2]);
        $this->assertEquals("Version 1", $events[2]->getLabel());
        $this->assertEquals("2026-01-15 10:00:00", $events[2]->getDate());
    }

    public function testLatestEvent(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Initial content',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Updated content',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();

        $latest = $history->getLatestEvent();

        $this->assertInstanceOf(LogEvent::class, $latest);
        $this->assertEquals("2026-01-15 12:00:00", $latest->getDate());
    }

    public function testRevisionEventContainsRevisionId(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Original content',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Updated content',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $revision_event = $events[1];
        $this->assertInstanceOf(RevisionEvent::class, $revision_event);
        $this->assertGreaterThan(0, $revision_event->getRevisionId());
        $this->assertInstanceOf(
            KnowbaseItem_Revision::class,
            KnowbaseItem_Revision::getById($revision_event->getRevisionId())
        );
    }

    public function testRevisionEventDescriptionDistinguishesCreationFromUpdate(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Version 1',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Version 2',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Version 3',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $version_2 = $events[1];
        $this->assertInstanceOf(RevisionEvent::class, $version_2);
        $this->assertEquals("Updated by", $version_2->getDescription());

        $version_1 = $events[2];
        $this->assertInstanceOf(RevisionEvent::class, $version_1);
        $this->assertEquals("Created by", $version_1->getDescription());
    }

    public function testPermissionAdded(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $kb->getID(),
            'entities_id'      => $this->getTestRootEntity(only_id: true),
            'is_recursive'     => 1,
        ]);

        $root_entity = new Entity();
        $root_entity->getFromDB($this->getTestRootEntity(only_id: true));
        $entity_name = $root_entity->getNameID(['forceid' => true]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertEquals([
            new LogEvent(
                label: "Permissions updated",
                description: sprintf("Access granted to %s by", $entity_name),
                date: "2026-01-15 11:00:00",
                author: "_test_user (8)",
            ),
            new CreationEvent(
                date: "2026-01-15 10:00:00",
                author: 2,
            ),
        ], $events);
    }

    public function testNameChangeAppearsInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Original title',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'name' => 'Updated title',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        // Name-only change should not create a revision, just a Renamed event
        $this->assertCount(2, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("Renamed", $events[0]->getLabel());
        $this->assertEquals("Updated by", $events[0]->getDescription());
        $this->assertEquals("Original title", $events[0]->getOldValue());
        $this->assertEquals("Updated title", $events[0]->getNewValue());
        $this->assertEquals("2026-01-15 11:00:00", $events[0]->getDate());

        $this->assertInstanceOf(CreationEvent::class, $events[1]);
    }

    public function testPermissionRemoved(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $relation = $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $kb->getID(),
            'entities_id'      => $this->getTestRootEntity(only_id: true),
            'is_recursive'     => 1,
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->deleteItem(Entity_KnowbaseItem::class, $relation->getID());

        $root_entity = new Entity();
        $root_entity->getFromDB($this->getTestRootEntity(only_id: true));
        $entity_name = $root_entity->getNameID(['forceid' => true]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertEquals([
            new LogEvent(
                label: "Permissions updated",
                description: sprintf("Access revoked from %s by", $entity_name),
                date: "2026-01-15 12:00:00",
                author: "_test_user (8)",
            ),
            new LogEvent(
                label: "Permissions updated",
                description: sprintf("Access granted to %s by", $entity_name),
                date: "2026-01-15 11:00:00",
                author: "_test_user (8)",
            ),
            new CreationEvent(
                date: "2026-01-15 10:00:00",
                author: 2,
            ),
        ], $events);
    }

    public function testMultipleNameChanges(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Title v1',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'name' => 'Title v2',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'name' => 'Title v3',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        // 3 events: 2 Renamed + Creation (no revisions for name-only changes)
        $this->assertCount(3, $events);

        $renamed_events = array_values(array_filter(
            $events,
            static fn($e) => $e instanceof LogEvent && $e->getLabel() === "Renamed"
        ));

        $this->assertCount(2, $renamed_events);
        $this->assertEquals("Updated by", $renamed_events[0]->getDescription());
        $this->assertEquals("Title v2", $renamed_events[0]->getOldValue());
        $this->assertEquals("Title v3", $renamed_events[0]->getNewValue());
        $this->assertEquals("2026-01-15 12:00:00", $renamed_events[0]->getDate());
        $this->assertEquals("Updated by", $renamed_events[1]->getDescription());
        $this->assertEquals("Title v1", $renamed_events[1]->getOldValue());
        $this->assertEquals("Title v2", $renamed_events[1]->getNewValue());
        $this->assertEquals("2026-01-15 11:00:00", $renamed_events[1]->getDate());
    }

    public function testMultiplePermissionTypes(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $kb->getID(),
            'entities_id'      => $this->getTestRootEntity(only_id: true),
            'is_recursive'     => 1,
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => 2,
        ]);

        $root_entity = new Entity();
        $root_entity->getFromDB($this->getTestRootEntity(only_id: true));
        $entity_name = $root_entity->getNameID(['forceid' => true]);

        $user = new User();
        $user->getFromDB(2);
        $user_name = $user->getNameID(['forceid' => true]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertEquals([
            new LogEvent(
                label: "Permissions updated",
                description: sprintf("Access granted to %s by", $user_name),
                date: "2026-01-15 12:00:00",
                author: "_test_user (8)",
            ),
            new LogEvent(
                label: "Permissions updated",
                description: sprintf("Access granted to %s by", $entity_name),
                date: "2026-01-15 11:00:00",
                author: "_test_user (8)",
            ),
            new CreationEvent(
                date: "2026-01-15 10:00:00",
                author: 2,
            ),
        ], $events);
    }

    public function testNameChangeWithContentRevision(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Original title',
            'answer' => 'Version 1',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'name' => 'New title',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Version 2',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        // 3 events: Current version (content), Renamed, Creation
        $this->assertCount(3, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("Current version", $events[0]->getLabel());

        $this->assertInstanceOf(LogEvent::class, $events[1]);
        $this->assertEquals("Renamed", $events[1]->getLabel());
        $this->assertEquals("Original title", $events[1]->getOldValue());
        $this->assertEquals("New title", $events[1]->getNewValue());

        $this->assertInstanceOf(RevisionEvent::class, $events[2]);
    }

    public function testFaqChanges(): void
    {
        // Arrange: create KB item with 2 FAQ status changes
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Version 1',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'is_faq' => 1,
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'is_faq' => 0,
        ]);

        // Act: build history
        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        // Assert: the history should contain references to the FAQ changes
        $this->assertEquals([
            new LogEvent(
                label: "Removed from the FAQ",
                description: "Updated by",
                date: "2026-01-15 12:00:00",
                author: "_test_user (8)",
            ),
            new LogEvent(
                label: "Added to the FAQ",
                description: "Updated by",
                date: "2026-01-15 11:00:00",
                author: "_test_user (8)",
            ),
            new CreationEvent(
                date: "2026-01-15 10:00:00",
                author: 2,
            ),
        ], $events);
    }

    public function testServiceCatalogChanges(): void
    {
        // Arrange: create KB item with changes on the service catalog properties
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $category = $this->createItem(Category::class, [
            'name' => 'My category',
        ]);
        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Version 1',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'show_in_service_catalog' => 1,
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'is_pinned' => 1,
        ]);

        $this->setCurrentTime("2026-01-15 13:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'description' => 'Service catalog description',
        ]);

        $this->setCurrentTime("2026-01-15 14:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'is_pinned' => 0,
        ]);

        $this->setCurrentTime("2026-01-15 15:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'show_in_service_catalog' => 0,
        ]);

        $this->setCurrentTime("2026-01-15 16:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'forms_categories_id' => $category->getID(),
        ]);

        // Act: build history
        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        // Assert: the history should contain references to service catalog changes
        $this->assertEquals([
            new LogEvent(
                label: "Service catalog updated",
                description: "Category updated by",
                date: "2026-01-15 16:00:00",
                author: "_test_user (8)",
                new_value: "My category ({$category->getID()})",
                old_value: " (0)",
            ),
            new LogEvent(
                label: "Service catalog updated",
                description: "Removed from the service catalog by",
                date: "2026-01-15 15:00:00",
                author: "_test_user (8)",
            ),
            new LogEvent(
                label: "Service catalog updated",
                description: "Unpinned from the top by",
                date: "2026-01-15 14:00:00",
                author: "_test_user (8)",
            ),
            new LogEvent(
                label: "Service catalog updated",
                description: "Description updated by",
                date: "2026-01-15 13:00:00",
                author: "_test_user (8)",
                new_value: "Service catalog description",
            ),
            new LogEvent(
                label: "Service catalog updated",
                description: "Pinned to the top by",
                date: "2026-01-15 12:00:00",
                author: "_test_user (8)",
            ),
            new LogEvent(
                label: "Service catalog updated",
                description: "Added to the service catalog by",
                date: "2026-01-15 11:00:00",
                author: "_test_user (8)",
            ),
            new CreationEvent(
                date: "2026-01-15 10:00:00",
                author: 2,
            ),
        ], $events);
    }

    public function testLinkedItemAppearsInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $computer = $this->createItem(Computer::class, [
            'name' => 'Test PC',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        $this->createItem(KnowbaseItem_Item::class, [
            'knowbaseitems_id' => $kb->getID(),
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(2, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("Item linked", $events[0]->getLabel());
        $this->assertStringContainsString("Computer", $events[0]->getDescription());

        $this->assertInstanceOf(CreationEvent::class, $events[1]);
    }

    public function testUnlinkedItemAppearsInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $ticket = $this->createItem(Ticket::class, [
            'name' => 'Test Ticket',
            'content' => 'Ticket content',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        $kb_item = $this->createItem(KnowbaseItem_Item::class, [
            'knowbaseitems_id' => $kb->getID(),
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->deleteItem(KnowbaseItem_Item::class, $kb_item->getID(), purge: true);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(3, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("Item unlinked", $events[0]->getLabel());
        $this->assertStringContainsString("Ticket", $events[0]->getDescription());

        $this->assertInstanceOf(LogEvent::class, $events[1]);
        $this->assertEquals("Item linked", $events[1]->getLabel());
    }

    public function testLinkedItemChangesWithContentRevisions(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Version 1',
        ]);

        // Link a computer
        $this->setCurrentTime("2026-01-15 11:00:00");
        $computer = $this->createItem(Computer::class, [
            'name' => 'My PC',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $this->createItem(KnowbaseItem_Item::class, [
            'knowbaseitems_id' => $kb->getID(),
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
        ]);

        // Update content (creates a revision)
        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Version 2',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        // 3 events sorted by date DESC: current version, item linked, revision 1
        $this->assertCount(3, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("Current version", $events[0]->getLabel());

        $this->assertInstanceOf(LogEvent::class, $events[1]);
        $this->assertEquals("Item linked", $events[1]->getLabel());


        $this->assertInstanceOf(RevisionEvent::class, $events[2]);
        $this->assertEquals("Version 1", $events[2]->getLabel());
    }

    public function testDocumentAddedAppearsInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $doc = $this->createItem(Document::class, [
            'name' => 'test_document.pdf',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        $this->createItem(Document_Item::class, [
            'documents_id' => $doc->getID(),
            'itemtype' => KnowbaseItem::class,
            'items_id' => $kb->getID(),
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(2, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("File added", $events[0]->getLabel());
        $this->assertStringContainsString("test_document.pdf", $events[0]->getDescription());

        $this->assertInstanceOf(CreationEvent::class, $events[1]);
    }

    public function testDocumentRemovedAppearsInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $doc = $this->createItem(Document::class, [
            'name' => 'to_remove.pdf',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        $doc_item = $this->createItem(Document_Item::class, [
            'documents_id' => $doc->getID(),
            'itemtype' => KnowbaseItem::class,
            'items_id' => $kb->getID(),
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->deleteItem(Document_Item::class, $doc_item->getID(), purge: true);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(3, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("File removed", $events[0]->getLabel());
        $this->assertStringContainsString("to_remove.pdf", $events[0]->getDescription());

        $this->assertInstanceOf(LogEvent::class, $events[1]);
        $this->assertEquals("File added", $events[1]->getLabel());
    }

    public function testDocumentChangesWithContentRevisions(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Version 1',
        ]);

        // Add a document
        $this->setCurrentTime("2026-01-15 11:00:00");
        $doc = $this->createItem(Document::class, [
            'name' => 'attached_file.pdf',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $this->createItem(Document_Item::class, [
            'documents_id' => $doc->getID(),
            'itemtype' => KnowbaseItem::class,
            'items_id' => $kb->getID(),
        ]);

        // Update content (creates a revision)
        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'answer' => 'Version 2',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        // 3 events sorted by date DESC: current version, file added, revision 1
        $this->assertCount(3, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("Current version", $events[0]->getLabel());

        $this->assertInstanceOf(LogEvent::class, $events[1]);
        $this->assertEquals("File added", $events[1]->getLabel());

        $this->assertInstanceOf(RevisionEvent::class, $events[2]);
        $this->assertEquals("Version 1", $events[2]->getLabel());
    }

    public function testNativeIllustrationChangeAppearsInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
            'illustration' => 'kb-faq',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'illustration' => 'browse-kb',
        ]);

        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $this->assertCount(2, $events);

        $this->assertInstanceOf(LogEvent::class, $events[0]);
        $this->assertEquals("Illustration updated", $events[0]->getLabel());
        $this->assertEquals("Native illustration set by", $events[0]->getDescription());
        $this->assertEquals("2026-01-15 11:00:00", $events[0]->getDate());

        $this->assertInstanceOf(CreationEvent::class, $events[1]);
    }

    public function testCurrentTranslationAppearsInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        // Arrange: create a KB with two translations
        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kb->getID(),
            'language' => 'fr_FR',
            'name' => 'Article de test',
            'answer' => 'Contenu de test',
            'users_id' => Session::getLoginUserID(),
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kb->getID(),
            'language' => 'de_DE',
            'name' => 'Testartikel',
            'answer' => 'Testinhalt',
            'users_id' => Session::getLoginUserID(),
        ]);

        // Act: build history for this KB and get all CurrentTranslationEvents
        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();
        $translation_events = array_values(array_filter(
            $events,
            static fn($e) => $e instanceof CurrentTranslationEvent
        ));

        // Assert: there should be two events: one per translations
        $de_event = $translation_events[0];
        $this->assertEquals("2026-01-15 12:00:00", $de_event->getDate());
        $this->assertEquals("Deutsch — Current version", $de_event->getLabel());
        $this->assertEquals("Updated by", $de_event->getDescription());
        $this->assertEquals(Session::getLoginUserID(), $de_event->getAuthor());

        $fr_event = $translation_events[1];
        $this->assertEquals("2026-01-15 11:00:00", $fr_event->getDate());
        $this->assertEquals("Français — Current version", $fr_event->getLabel());
        $this->assertEquals("Updated by", $fr_event->getDescription());
        $this->assertEquals(Session::getLoginUserID(), $fr_event->getAuthor());

        $this->assertCount(2, $translation_events);
    }

    public function testTranslationRevisionsAppearInHistory(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        // Assert: create a KB with 3 french translations
        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $translation = $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kb->getID(),
            'language' => 'fr_FR',
            'name' => 'Article de test',
            'answer' => 'Contenu V1',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItemTranslation::class, $translation->getID(), [
            'answer' => 'Contenu V2',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItemTranslation::class, $translation->getID(), [
            'answer' => 'Contenu V3',
        ]);

        // Act: build history for this KB and get all TranslationRevisionEvent
        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $revision_events = array_values(array_filter(
            $events,
            static fn($e) => $e instanceof TranslationRevisionEvent
        ));

        $this->assertCount(2, $revision_events);

        // Assert: there should be two events: one per translations minus the one
        // because the most recent one is a CurrentTranslationEvent event.
        $fr_2 = $revision_events[0];
        $this->assertEquals('fr_FR', $fr_2->getLanguage());
        $this->assertEquals("2026-01-15 11:00:00", $fr_2->getDate());
        $this->assertEquals('Français — Version 2', $fr_2->getLabel());
        $this->assertEquals("Updated by", $fr_2->getDescription());

        $fr_1 = $revision_events[1];
        $this->assertEquals('fr_FR', $fr_1->getLanguage());
        $this->assertEquals("2026-01-15 10:00:00", $fr_1->getDate());
        $this->assertEquals('Français — Version 1', $fr_1->getLabel());
        $this->assertEquals("Created by", $fr_1->getDescription());
    }

    public function testTranslationRevisionsFromMultipleLanguagesAreIndexedIndependently(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-15 10:00:00");

        // Assert: create a KB with 2 french translations and 2 german translations
        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Test article',
            'answer' => 'Test content',
        ]);

        $fr_translation = $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kb->getID(),
            'language' => 'fr_FR',
            'name' => 'French',
            'answer' => 'FR V1',
        ]);

        $this->setCurrentTime("2026-01-15 11:00:00");
        $this->updateItem(KnowbaseItemTranslation::class, $fr_translation->getID(), [
            'answer' => 'FR V2',
        ]);

        $de_translation = $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kb->getID(),
            'language' => 'de_DE',
            'name' => 'German',
            'answer' => 'DE V1',
        ]);

        $this->setCurrentTime("2026-01-15 12:00:00");
        $this->updateItem(KnowbaseItemTranslation::class, $de_translation->getID(), [
            'answer' => 'DE V2',
        ]);

        // Act: build history for this KB and get all TranslationRevisionEvent
        // for each languages
        $kb->getFromDB($kb->getID());
        $history = (new HistoryBuilder($kb))->buildHistory();
        $events = $history->getEvents();

        $fr_revisions = array_values(array_filter(
            $events,
            static fn($e) => $e instanceof TranslationRevisionEvent && $e->getLanguage() === 'fr_FR'
        ));
        $de_revisions = array_values(array_filter(
            $events,
            static fn($e) => $e instanceof TranslationRevisionEvent && $e->getLanguage() === 'de_DE'
        ));

        // Assert: the event should be indexed properly
        $this->assertCount(1, $fr_revisions);
        $this->assertEquals(
            'Français — Version 1',
            $fr_revisions[0]->getLabel(),
        );

        $this->assertCount(1, $de_revisions);
        $this->assertEquals('Deutsch — Version 1', $de_revisions[0]->getLabel());
    }
}
