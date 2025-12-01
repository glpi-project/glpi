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

use Glpi\Marketplace\Controller;
use Glpi\Tests\DbTestCase;
use NotificationEvent;
use PHPUnit\Framework\Attributes\TestWith;
use Ticket;

class NotificationEventTest extends DbTestCase
{
    public function testNotificationOnCommonDBTM(): void
    {
        // arrange
        $this->login();
        $this->enableNotifications();
        $ticket = $this->createItem(Ticket::class, $this->getMinimalCreationInput(Ticket::class));
        $event = 'delete';

        // act
        NotificationEvent::raiseEvent($event, $ticket);

        // assert
        $this->assertEventInQueue($event);
    }

    #[TestWith(['checkpluginsupdate', Controller::class,  ['plugins' => ['tester' => '1.2.3']]])]
    #[TestWith(['desynchronization', \DBConnection::class, ['diff' => 'some diff', 'name' => 'my_slave_host',]])]
    public function testNotificationOnCommonGlpi(string $event, string $classname, array $options = []): void
    {
        // --- arrange
        $this->login();
        $this->enableNotifications();

        // act
        $common_glpi = new $classname();
        assert(!$common_glpi instanceof \CommonDBTM, 'test should not run on CommonDBTM but on CommonGLPI.');
        NotificationEvent::raiseEvent($event, $common_glpi, $options);

        // assert
        $this->assertEventInQueue($event);
    }

    /**
     * Assert a single notification is found in queued notifications
     *
     * @todo can be shared
     * @param string $event
     * @param array $additional_where
     */
    private function assertEventInQueue(string $event, array $additional_where = []): void
    {
        $where_criteria = ['event' => $event];
        $where_criteria = array_merge($where_criteria, $additional_where);

        $found = (new \QueuedNotification())->find($where_criteria);
        $this->assertTrue(count($found) > 0, 'Notification not found in queued notifications.');
        $this->assertTrue(count($found) < 2, 'Too much notification found in queued notifications.');
    }

    /**
     * @todo can be shared
     */
    private function enableNotifications(): void
    {
        global $CFG_GLPI;

        $CFG_GLPI['use_notifications'] = 1;
        $CFG_GLPI['notifications_' . \Notification_NotificationTemplate::MODE_MAIL] = 1;
    }
}
