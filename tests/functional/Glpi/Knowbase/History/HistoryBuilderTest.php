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

namespace Glpi\Knowbase\History;

use Computer;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Item;
use KnowbaseItem_Revision;
use Ticket;

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

    public function testFaqChanges(): void
    {
        // Act: create KB item with 2 FAQ status changes
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
}
