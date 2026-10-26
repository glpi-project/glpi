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

use Alert;
use CronTask;
use Entity;
use Glpi\Tests\DbTestCase;
use Group;
use Group_User;
use Notification;
use Notification_NotificationTemplate;
use NotificationTarget;
use QueuedNotification;
use Ticket;
use User;
use UserEmail;

class TicketAlertNotClosedCronTest extends DbTestCase
{
    public function testOneNotificationPerTicketPerDay(): void
    {
        global $CFG_GLPI, $DB;

        $this->login();

        $CFG_GLPI['use_notifications']     = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $entities_id = $this->getTestRootEntity(true);
        $this->updateItem(Entity::class, $entities_id, ['notclosed_delay' => 2]);

        $users_ids = [];
        foreach (['tech', 'member'] as $name) {
            $user = $this->createItem(User::class, [
                'name'          => __FUNCTION__ . $name,
                '_profiles_id'  => getItemByTypeName('Profile', 'Technician', true),
                '_entities_id'  => $entities_id,
                '_is_recursive' => 1,
            ]);
            $this->createItem(UserEmail::class, [
                'users_id'   => $user->getID(),
                'email'      => 'alertnotclosed_' . $name . '@localhost',
                'is_default' => 1,
            ]);
            $users_ids[$name] = $user->getID();
        }
        $group = $this->createItem(Group::class, ['name' => __FUNCTION__, 'entities_id' => $entities_id]);
        $this->createItem(Group_User::class, ['groups_id' => $group->getID(), 'users_id' => $users_ids['member']]);

        $notification = $this->createItem(Notification::class, [
            'name'         => __FUNCTION__,
            'entities_id'  => $entities_id,
            'is_recursive' => 1,
            'is_active'    => 1,
            'itemtype'     => Ticket::class,
            'event'        => 'alertnotclosed_ticket',
        ]);
        $this->createItem(Notification_NotificationTemplate::class, [
            'notifications_id'         => $notification->getID(),
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            'notificationtemplates_id' => getItemByTypeName('NotificationTemplate', 'Tickets', true),
        ]);
        foreach ([Notification::ASSIGN_TECH, Notification::ASSIGN_GROUP] as $items_id) {
            $this->createItem(NotificationTarget::class, [
                'notifications_id' => $notification->getID(),
                'type'             => Notification::USER_TYPE,
                'items_id'         => $items_id,
            ]);
        }

        $late_ticket = $this->createItem(Ticket::class, [
            'name'              => __FUNCTION__ . ' late',
            'content'           => __FUNCTION__,
            'entities_id'       => $entities_id,
            '_users_id_assign'  => $users_ids['tech'],
            '_groups_id_assign' => $group->getID(),
        ]);
        $recent_ticket = $this->createItem(Ticket::class, [
            'name'             => __FUNCTION__ . ' recent',
            'content'          => __FUNCTION__,
            'entities_id'      => $entities_id,
            '_users_id_assign' => $users_ids['tech'],
        ]);
        $DB->update(
            Ticket::getTable(),
            ['date' => date('Y-m-d H:i:s', strtotime('-3 days'))],
            ['id' => $late_ticket->getID()]
        );

        $crontask = new CronTask();
        $this->assertTrue($crontask->getFromDBbyName(Ticket::class, 'alertnotclosed'));

        $get_recipients = static function (Ticket $ticket): array {
            $recipients = array_column(
                (new QueuedNotification())->find([
                    'itemtype' => Ticket::class,
                    'items_id' => $ticket->getID(),
                    'event'    => 'alertnotclosed_ticket',
                ]),
                'recipient'
            );
            sort($recipients);
            return $recipients;
        };
        $expected = ['alertnotclosed_member@localhost', 'alertnotclosed_tech@localhost'];

        // Only the late ticket is notified, to its technician and group
        $this->assertEquals(1, Ticket::cronAlertNotClosed($crontask));
        $this->assertEquals($expected, $get_recipients($late_ticket));
        $this->assertEquals([], $get_recipients($recent_ticket));

        $alert = new Alert();
        $this->assertTrue($alert->getFromDBByCrit([
            'itemtype' => Ticket::class,
            'items_id' => $late_ticket->getID(),
            'type'     => Alert::NOTCLOSED,
        ]));

        $digests_count = countElementsInTable(QueuedNotification::getTable(), ['event' => 'alertnotclosed']);
        $this->assertGreaterThan(0, $digests_count);

        // Not notified again the same day, while the digest is still sent
        Ticket::cronAlertNotClosed($crontask);
        $this->assertEquals($expected, $get_recipients($late_ticket));
        $this->assertEquals(
            2 * $digests_count,
            countElementsInTable(QueuedNotification::getTable(), ['event' => 'alertnotclosed'])
        );

        // Notified again the next day
        $DB->update(
            Alert::getTable(),
            ['date' => date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['id' => $alert->getID()]
        );
        $this->assertEquals(1, Ticket::cronAlertNotClosed($crontask));
        $this->assertEquals(
            ['alertnotclosed_member@localhost', 'alertnotclosed_member@localhost', 'alertnotclosed_tech@localhost', 'alertnotclosed_tech@localhost'],
            $get_recipients($late_ticket)
        );
        $this->assertEquals(
            1,
            countElementsInTable(Alert::getTable(), [
                'itemtype' => Ticket::class,
                'items_id' => $late_ticket->getID(),
                'type'     => Alert::NOTCLOSED,
            ])
        );

        // Alert is removed with the ticket
        $this->assertTrue($late_ticket->delete(['id' => $late_ticket->getID()], true));
        $this->assertEquals(
            0,
            countElementsInTable(Alert::getTable(), ['itemtype' => Ticket::class, 'items_id' => $late_ticket->getID()])
        );
    }

    public function testNoAlertWithoutActiveNotification(): void
    {
        global $CFG_GLPI, $DB;

        $this->login();

        $CFG_GLPI['use_notifications']     = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $entities_id = $this->getTestRootEntity(true);
        $this->updateItem(Entity::class, $entities_id, ['notclosed_delay' => 2]);
        $DB->update(Notification::getTable(), ['is_active' => 0], ['event' => 'alertnotclosed_ticket']);

        $ticket = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $entities_id,
        ]);
        $DB->update(
            Ticket::getTable(),
            ['date' => date('Y-m-d H:i:s', strtotime('-3 days'))],
            ['id' => $ticket->getID()]
        );

        $crontask = new CronTask();
        $this->assertTrue($crontask->getFromDBbyName(Ticket::class, 'alertnotclosed'));
        Ticket::cronAlertNotClosed($crontask);

        // No alert is recorded, so the ticket is notified as soon as a notification is enabled
        $this->assertEquals(
            0,
            countElementsInTable(Alert::getTable(), ['itemtype' => Ticket::class, 'items_id' => $ticket->getID()])
        );
    }
}
