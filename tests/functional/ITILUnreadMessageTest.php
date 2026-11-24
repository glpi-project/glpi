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

use CronTask;
use Glpi\Tests\DbTestCase;
use ITILFollowup;
use ITILUnreadMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use Ticket;
use User;

use function Safe\date;
use function Safe\strtotime;

class ITILUnreadMessageTest extends DbTestCase
{
    /**
     * @return iterable<string, array{bool, int, bool}>
     */
    public static function cleanOldMessagesProvider(): iterable
    {
        yield 'read, 2 months old' => [true, 2, false];
        yield 'read, recent' => [true, 0, true];
        yield 'unread, 4 months old' => [false, 4, false];
        yield 'unread, 2 months old' => [false, 2, true];
    }

    #[DataProvider('cleanOldMessagesProvider')]
    public function testCronCleanOldMessages(bool $is_read, int $months_ago, bool $is_kept): void
    {
        $this->login();

        [$ticket, $followup] = $this->createTicketWithFollowup();
        $message_id = $this->createMessage(
            users_id: getItemByTypeName(User::class, 'post-only', true),
            ticket: $ticket,
            itemtype: ITILFollowup::class,
            items_id: $followup->getID(),
            is_read: $is_read,
            months_ago: $months_ago,
        );

        $task = new CronTask();
        $this->assertSame(1, ITILUnreadMessage::cronCleanOldMessages($task));
        $this->assertSame($is_kept, (new ITILUnreadMessage())->getFromDB($message_id));
    }

    public function testCronCleanOldMessagesCountsAllDeletedMessages(): void
    {
        $this->login();

        [$ticket, $followup] = $this->createTicketWithFollowup();
        $users_id = getItemByTypeName(User::class, 'post-only', true);
        // One message deleted by each of the two queries
        $this->createMessage($users_id, $ticket, ITILFollowup::class, $followup->getID(), true, 2);
        $this->createMessage($users_id, $ticket, ITILFollowup::class, $followup->getID(), false, 4);

        $task = $this->createMock(CronTask::class);
        $task->expects($this->once())->method('addVolume')->with(2);
        ITILUnreadMessage::cronCleanOldMessages($task);
    }

    public function testPurgeTimelineItemDeletesItsMessages(): void
    {
        $this->login();

        [$ticket, $followup] = $this->createTicketWithFollowup();
        [, $other_followup] = $this->createTicketWithFollowup();
        $users_id = getItemByTypeName(User::class, 'post-only', true);
        $message_id = $this->createMessage($users_id, $ticket, ITILFollowup::class, $followup->getID());
        $other_message_id = $this->createMessage($users_id, $ticket, ITILFollowup::class, $other_followup->getID());

        $this->assertTrue($followup->delete(['id' => $followup->getID()], true));

        $this->assertFalse((new ITILUnreadMessage())->getFromDB($message_id));
        $this->assertTrue((new ITILUnreadMessage())->getFromDB($other_message_id));
    }

    public function testPurgeITILObjectDeletesItsMessages(): void
    {
        $this->login();

        [$ticket] = $this->createTicketWithFollowup();
        [$other_ticket] = $this->createTicketWithFollowup();
        $users_id = getItemByTypeName(User::class, 'post-only', true);
        // The followup does not exist: only the link to the parent ticket can delete this message
        $message_id = $this->createMessage($users_id, $ticket, ITILFollowup::class, 999999);
        $other_message_id = $this->createMessage($users_id, $other_ticket, ITILFollowup::class, 999999);

        $this->assertTrue($ticket->delete(['id' => $ticket->getID()], true));

        $this->assertFalse((new ITILUnreadMessage())->getFromDB($message_id));
        $this->assertTrue((new ITILUnreadMessage())->getFromDB($other_message_id));
    }

    public function testPurgeUserDeletesItsMessages(): void
    {
        $this->login();

        [$ticket, $followup] = $this->createTicketWithFollowup();
        $user = $this->createItem(User::class, ['name' => __FUNCTION__]);
        $message_id = $this->createMessage($user->getID(), $ticket, ITILFollowup::class, $followup->getID());
        $other_message_id = $this->createMessage(
            getItemByTypeName(User::class, 'post-only', true),
            $ticket,
            ITILFollowup::class,
            $followup->getID()
        );

        $this->assertTrue($user->delete(['id' => $user->getID()], true));

        $this->assertFalse((new ITILUnreadMessage())->getFromDB($message_id));
        $this->assertTrue((new ITILUnreadMessage())->getFromDB($other_message_id));
    }

    /**
     * @return array{Ticket, ITILFollowup}
     */
    private function createTicketWithFollowup(): array
    {
        $ticket = $this->createItem(Ticket::class, [
            'name'        => 'Ticket with unread messages',
            'content'     => 'Content',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $followup = $this->createItem(ITILFollowup::class, [
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
            'content'  => 'Followup',
        ]);

        return [$ticket, $followup];
    }

    /**
     * Insert the message directly in DB, as nothing creates unread messages yet.
     */
    private function createMessage(
        int $users_id,
        Ticket $ticket,
        string $itemtype,
        int $items_id,
        bool $is_read = false,
        int $months_ago = 0,
    ): int {
        global $DB;

        $DB->insert(ITILUnreadMessage::getTable(), [
            'users_id'        => $users_id,
            'parent_itemtype' => Ticket::class,
            'parent_items_id' => $ticket->getID(),
            'itemtype'        => $itemtype,
            'items_id'        => $items_id,
            'date'            => date('Y-m-d H:i:s', strtotime("-{$months_ago} months")),
            'is_read'         => (int) $is_read,
        ]);

        return $DB->insertId();
    }
}
