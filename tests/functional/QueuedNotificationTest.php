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

use Glpi\Tests\DbTestCase;
use GLPIKey;
use Notification_NotificationTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use Project;
use QueuedNotification;
use Ticket;
use User;

class QueuedNotificationTest extends DbTestCase
{
    public function testAddProjectNotification()
    {
        $queued_notification = new QueuedNotification();

        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $project_notification_id = getItemByTypeName('NotificationTemplate', 'Projects', true);

        $project = new Project();
        $project_id_1 = $project->add(['name' => 'Test project 1', 'entities_id' => $root_entity_id]);
        $this->assertGreaterThan(0, $project_id_1);
        $project_id_2 = $project->add(['name' => 'Test project 2', 'entities_id' => $root_entity_id]);
        $this->assertGreaterThan(0, $project_id_2);

        // First notification
        $queued_id_1 = $queued_notification->add(
            [
                'itemtype'                 => 'Project',
                'items_id'                 => $project_id_1,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $project_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'test-user@glpi-project.org',
                'name'                     => 'Test notification 1',
                'body_text'                => 'Text of notification 1',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_1);
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));

        // Notification with same item and recipient, should not trigger previous notification deletion
        $queued_id_2 = $queued_notification->add(
            [
                'itemtype'                 => 'Project',
                'items_id'                 => $project_id_1,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $project_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'test-user@glpi-project.org',
                'name'                     => 'Test notification 2',
                'body_text'                => 'Text of notification 2',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_2);
        $this->assertTrue($queued_notification->getFromDB($queued_id_2));
        // Previous notifications have not been removed
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));

        // Notification with different recipient, should not trigger previous notification deletion
        $queued_id_3 = $queued_notification->add(
            [
                'itemtype'                 => 'Project',
                'items_id'                 => $project_id_1,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $project_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'another-user@glpi-project.org',
                'name'                     => 'Test notification 3',
                'body_text'                => 'Text of notification 3',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_2);
        $this->assertTrue($queued_notification->getFromDB($queued_id_3));
        // Previous notifications have not been removed
        $this->assertTrue($queued_notification->getFromDB($queued_id_2));
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));

        // Notification with different item, should not trigger previous notification deletion
        $this->assertGreaterThan(0, $project_id_1);
        $queued_id_4 = $queued_notification->add(
            [
                'itemtype'                 => 'Project',
                'items_id'                 => $project_id_2,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $project_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'test-user@glpi-project.org',
                'name'                     => 'Test notification 4',
                'body_text'                => 'Text of notification 4',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_2);
        $this->assertTrue($queued_notification->getFromDB($queued_id_4));
        // Previous notifications have not been removed
        $this->assertTrue($queued_notification->getFromDB($queued_id_3));
        $this->assertTrue($queued_notification->getFromDB($queued_id_2));
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));
    }

    public function testAddTicketNotification()
    {
        $queued_notification = new QueuedNotification();

        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $ticket_notification_id = getItemByTypeName('NotificationTemplate', 'Tickets', true);

        $ticket = new Project();
        $ticket_id_1 = $ticket->add(['name' => 'Test ticket 1', 'entities_id' => $root_entity_id]);
        $this->assertGreaterThan(0, $ticket_id_1);
        $ticket_id_2 = $ticket->add(['name' => 'Test ticket 2', 'entities_id' => $root_entity_id]);
        $this->assertGreaterThan(0, $ticket_id_2);

        // First notification
        $queued_id_1 = $queued_notification->add(
            [
                'itemtype'                 => 'Ticket',
                'items_id'                 => $ticket_id_1,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $ticket_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'test-user@glpi-project.org',
                'name'                     => 'Test notification 1',
                'body_text'                => 'Text of notification 1',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_1);
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));

        // Notification with same item and recipient, should not trigger previous notification deletion
        $queued_id_2 = $queued_notification->add(
            [
                'itemtype'                 => 'Ticket',
                'items_id'                 => $ticket_id_1,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $ticket_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'test-user@glpi-project.org',
                'name'                     => 'Test notification 2',
                'body_text'                => 'Text of notification 2',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_2);
        $this->assertTrue($queued_notification->getFromDB($queued_id_2));
        // Previous notifications have not been removed
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));

        // Notification with different recipient, should not trigger previous notification deletion
        $queued_id_3 = $queued_notification->add(
            [
                'itemtype'                 => 'Ticket',
                'items_id'                 => $ticket_id_1,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $ticket_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'another-user@glpi-project.org',
                'name'                     => 'Test notification 3',
                'body_text'                => 'Text of notification 3',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_2);
        $this->assertTrue($queued_notification->getFromDB($queued_id_3));
        // Previous notifications have not been removed
        $this->assertTrue($queued_notification->getFromDB($queued_id_2));
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));

        // Notification with different item, should not trigger previous notification deletion
        $this->assertGreaterThan(0, $ticket_id_1);
        $queued_id_4 = $queued_notification->add(
            [
                'itemtype'                 => 'Ticket',
                'items_id'                 => $ticket_id_2,
                'entities_id'              => $root_entity_id,
                'notificationtemplates_id' => $ticket_notification_id,
                'sender'                   => 'mailer@glpi-project.org',
                'recipient'                => 'test-user@glpi-project.org',
                'name'                     => 'Test notification 4',
                'body_text'                => 'Text of notification 4',
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]
        );
        $this->assertGreaterThan(0, $queued_id_2);
        $this->assertTrue($queued_notification->getFromDB($queued_id_4));
        // Previous notifications have not been removed
        $this->assertTrue($queued_notification->getFromDB($queued_id_3));
        $this->assertTrue($queued_notification->getFromDB($queued_id_2));
        $this->assertTrue($queued_notification->getFromDB($queued_id_1));
    }

    public function testGetPendingsOrder()
    {
        global $CFG_GLPI;

        $CFG_GLPI['notifications_' . Notification_NotificationTemplate::MODE_MAIL] = 1;

        $recipient = 'order-' . uniqid() . '@example.com';
        $t1 = date('Y-m-d H:i:s', strtotime('-2 minutes'));
        $t0 = date('Y-m-d H:i:s', strtotime('-5 minutes'));

        $queued = new QueuedNotification();
        $make = static function (string $name, string $send_time) use ($recipient) {
            return [
                'itemtype'   => 'Ticket',
                'items_id'   => 1,
                'entities_id' => 0,
                'sender'     => 'mailer@glpi-project.org',
                'recipient'  => $recipient,
                'name'       => $name,
                'body_text'  => $name,
                'mode'       => Notification_NotificationTemplate::MODE_MAIL,
                'send_time'  => $send_time,
            ];
        };

        // Two notifications share the same send_time (ids ascending by
        // creation), a third one has an earlier send_time but a higher id.
        $id_a = $queued->add($make('A', $t1));
        $id_b = $queued->add($make('B', $t1));
        $id_c = $queued->add($make('C', $t0));
        $this->assertGreaterThan(0, $id_a);
        $this->assertGreaterThan($id_a, $id_b);
        $this->assertGreaterThan($id_b, $id_c);

        // Earliest send_time first (C), then the two same-time ones in creation
        // order (A before B) thanks to the id tie-breaker.
        $pendings = QueuedNotification::getPendings(null, 20, [Notification_NotificationTemplate::MODE_MAIL], ['recipient' => $recipient]);
        $ids = array_map('intval', array_column($pendings[Notification_NotificationTemplate::MODE_MAIL], 'id'));
        $this->assertSame([$id_c, $id_a, $id_b], $ids);

        // The batch limit is respected and keeps the same ordering.
        $limited = QueuedNotification::getPendings(null, 2, [Notification_NotificationTemplate::MODE_MAIL], ['recipient' => $recipient]);
        $this->assertSame([$id_c, $id_a], array_map('intval', array_column($limited[Notification_NotificationTemplate::MODE_MAIL], 'id')));
    }

    public static function sensitiveNotificationEventsProvider(): iterable
    {
        yield [
            'itemtype'            => Ticket::class,
            'event'               => 'new',
            'expected_encryption' => false,
        ];

        yield [
            'itemtype'            => User::class,
            'event'               => 'passwordforget',
            'expected_encryption' => true,
        ];

        yield [
            'itemtype'            => User::class,
            'event'               => 'passwordinit',
            'expected_encryption' => true,
        ];
    }

    #[DataProvider('sensitiveNotificationEventsProvider')]
    public function testPrepareInputForAddEncryptsSentitiveData(string $itemtype, string $event, bool $expected_encryption)
    {
        $input = [
            'itemtype'  => $itemtype,
            'event'     => $event,
            'body_text' => 'Content of the notification',
            'body_html' => '<p>Content of the notification</p>',
        ];

        $queued_notification = new QueuedNotification();
        $output = $queued_notification->prepareInputForAdd($input);

        if ($expected_encryption) {
            $this->assertArrayHasKey('is_body_encrypted', $output);
            $this->assertTrue($output['is_body_encrypted']);

            $glpi_key = new GLPIKey();
            $this->assertEquals($input['body_text'], $glpi_key->decrypt($output['body_text']));
            $this->assertEquals($input['body_html'], $glpi_key->decrypt($output['body_html']));
        } else {
            $this->assertArrayNotHasKey('is_body_encrypted', $output);
            $this->assertEquals($input['body_text'], $output['body_text']);
            $this->assertEquals($input['body_html'], $output['body_html']);
        }
    }

    #[DataProvider('sensitiveNotificationEventsProvider')]
    public function testPrepareInputForUpdateEncryptsSentitiveData(string $itemtype, string $event, bool $expected_encryption)
    {
        $input = [
            'itemtype'  => $itemtype,
            'event'     => $event,
            'body_text' => 'Content of the notification',
            'body_html' => '<p>Content of the notification</p>',
        ];

        $queued_notification = new QueuedNotification();
        $output = $queued_notification->prepareInputForUpdate($input);

        if ($expected_encryption) {
            $this->assertArrayHasKey('is_body_encrypted', $output);
            $this->assertTrue($output['is_body_encrypted']);

            $glpi_key = new GLPIKey();
            $this->assertEquals($input['body_text'], $glpi_key->decrypt($output['body_text']));
            $this->assertEquals($input['body_html'], $glpi_key->decrypt($output['body_html']));
        } else {
            $this->assertArrayNotHasKey('is_body_encrypted', $output);
            $this->assertEquals($input['body_text'], $output['body_text']);
            $this->assertEquals($input['body_html'], $output['body_html']);
        }
    }
}
