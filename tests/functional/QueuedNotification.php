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

use DbTestCase;
use Notification_NotificationTemplate;
use Project;

class QueuedNotification extends DbTestCase
{
    public function testAddNotificationWithDeduplication()
    {
        $queued_notification = new \QueuedNotification();

        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $project_notification_id = getItemByTypeName('NotificationTemplate', 'Projects', true);

        $project = new Project();
        $project_id_1 = $project->add(['name' => 'Test project 1', 'entities_id' => $root_entity_id]);
        $this->integer($project_id_1)->isGreaterThan(0);
        $project_id_2 = $project->add(['name' => 'Test project 2', 'entities_id' => $root_entity_id]);
        $this->integer($project_id_2)->isGreaterThan(0);

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
        $this->integer($queued_id_1)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_1))->isTrue();

       // Notification with same item and recipient, should trigger previous notification deletion
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
        $this->integer($queued_id_2)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_2))->isTrue();
       // Previous notifications have been removed
        $this->boolean($queued_notification->getFromDB($queued_id_1))->isFalse();

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
        $this->integer($queued_id_2)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_3))->isTrue();
       // Previous notifications have not been removed
        $this->boolean($queued_notification->getFromDB($queued_id_2))->isTrue();

       // Notification with different item, should not trigger previous notification deletion
        $this->integer($project_id_1)->isGreaterThan(0);
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
        $this->integer($queued_id_2)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_4))->isTrue();
       // Previous notifications have not been removed
        $this->boolean($queued_notification->getFromDB($queued_id_3))->isTrue();
        $this->boolean($queued_notification->getFromDB($queued_id_2))->isTrue();
    }

    public function testAddNotificationWithoutDeduplication()
    {
        $queued_notification = new \QueuedNotification();

        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $ticket_notification_id = getItemByTypeName('NotificationTemplate', 'Tickets', true);

        $ticket = new Project();
        $ticket_id_1 = $ticket->add(['name' => 'Test ticket 1', 'entities_id' => $root_entity_id]);
        $this->integer($ticket_id_1)->isGreaterThan(0);
        $ticket_id_2 = $ticket->add(['name' => 'Test ticket 2', 'entities_id' => $root_entity_id]);
        $this->integer($ticket_id_2)->isGreaterThan(0);

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
        $this->integer($queued_id_1)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_1))->isTrue();

       // Notification with same item and recipient, should trigger previous notification deletion
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
        $this->integer($queued_id_2)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_2))->isTrue();
       // Previous notifications have not been removed
        $this->boolean($queued_notification->getFromDB($queued_id_1))->isTrue();

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
        $this->integer($queued_id_2)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_3))->isTrue();
       // Previous notifications have not been removed
        $this->boolean($queued_notification->getFromDB($queued_id_2))->isTrue();
        $this->boolean($queued_notification->getFromDB($queued_id_1))->isTrue();

       // Notification with different item, should not trigger previous notification deletion
        $this->integer($ticket_id_1)->isGreaterThan(0);
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
        $this->integer($queued_id_2)->isGreaterThan(0);
        $this->boolean($queued_notification->getFromDB($queued_id_4))->isTrue();
       // Previous notifications have not been removed
        $this->boolean($queued_notification->getFromDB($queued_id_3))->isTrue();
        $this->boolean($queued_notification->getFromDB($queued_id_2))->isTrue();
        $this->boolean($queued_notification->getFromDB($queued_id_1))->isTrue();
    }
}
