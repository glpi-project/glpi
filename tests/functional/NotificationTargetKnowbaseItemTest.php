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

use Config;
use DbTestCase;
use Group;
use Notification;
use NotificationTarget;
use QueuedNotification;

/* Test for inc/notificationtargetticket.class.php */

class NotificationTargetKnowbaseItemTest extends DbTestCase
{
    public function testgetDataForNotifKnowbaseItem()
    {
        global $DB;

        $this->login();

        Config::setConfigurationValues('core', ['use_notifications' => 1]);
        Config::setConfigurationValues('core', ['notifications_mailing' => 1]);

        //set notification by mail to active
        $notif = new Notification();
        $knowbasenotifs = $notif->find(
            [
                'itemtype' => 'KnowbaseItem',
            ]
        );
        // test activate notification
        foreach ($knowbasenotifs as $kbnotif) {
            $this->assertTrue($notif->update(['id' => $kbnotif['id'], 'is_active' => 1]));
        }
        //search glpi user
        $this->createItem(
            \UserEmail::class,
            [
                'users_id' => getItemByTypeName('User', 'glpi', true),
                'email' => 'test@test.com',
                'is_default' => 1,
            ]
        );

        // test create group
        $group = $this->createItem(
            Group::class,
            [
                'name' => 'testknowbasegroup',
            ]
        );
        $this->assertEquals('testknowbasegroup', $group->fields['name']);

        //add user to group
        $this->createItem(
            \Group_User::class,
            [
                'groups_id' => $group->fields['id'],
                'users_id' => getItemByTypeName('User', 'glpi', true),
            ]
        );

        //test add group for notification
        foreach ($knowbasenotifs as $kbnotif) {
            // remove default targets
            $DB->delete(NotificationTarget::getTable(), ['notifications_id' => $kbnotif['id']]);

            $ntarget = $this->createItem(
                NotificationTarget::class,
                [
                    'notifications_id' => $kbnotif['id'],
                    'type' => Notification::GROUP_TYPE,
                    'items_id' => $group->fields['id'],
                ]
            );
            $this->assertSame(
                [
                    'id' => $ntarget->fields['id'],
                    'items_id' => $group->fields['id'],
                    'type' => Notification::GROUP_TYPE,
                    'notifications_id' => $kbnotif['id'],
                    'is_exclusion' => 0,
                ],
                $ntarget->fields
            );
        }

        //create/update/delete knowbase item
        $knowbaseitem = $this->createItem(
            \KnowbaseItem::class,
            [
                'name' => 'testknowbaseitem',
                'answer' => 'testknowbaseitem',
                'users_id' => getItemByTypeName('User', 'glpi', true),
            ]
        );
        //test check if add notification is in notification queue
        $notifqueue = new QueuedNotification();
        $this->assertCount(
            1,
            $notifqueue->find(['itemtype' => 'KnowbaseItem'])
        );

        $this->updateItem(
            \KnowbaseItem::class,
            $knowbaseitem->fields['id'],
            [
                'name' => 'testknowbaseitemupdate',
                'answer' => 'testknowbaseitemupdate',
                'users_id' => getItemByTypeName('User', 'glpi', true),
            ]
        );
        //test check if update notification is in notification queue
        $notifqueue = new QueuedNotification();
        $this->assertCount(
            2,
            $notifqueue->find(['itemtype' => 'KnowbaseItem'])
        );

        $this->deleteItem(
            \KnowbaseItem::class,
            $knowbaseitem->fields['id']
        );

        //test check if delete notification is in notification queue
        $notifqueue = new QueuedNotification();
        $this->assertCount(
            3,
            $notifqueue->find(['itemtype' => 'KnowbaseItem'])
        );
    }
}
