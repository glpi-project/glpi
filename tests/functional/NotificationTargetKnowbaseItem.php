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

use Config;
use DbTestCase;
use Group;
use Notification;
use NotificationTarget;
use QueuedNotification;

/* Test for inc/notificationtargetticket.class.php */

class NotificationTargetKnowbaseItem extends DbTestCase
{
    public function testgetDataForNotifKnowbaseItem()
    {
        $this->login();

        Config::setConfigurationValues('core', ['use_notifications' => 1]);
        Config::setConfigurationValues('core', ['notifications_mailing' => 1]);

        //set notification by mail to active
        $notif = new Notification();
        $knowbasenotifs = $notif->find(
            [
                'itemtype' => 'KnowbaseItem'
            ]
        );
        // test activate notification
        foreach ($knowbasenotifs as $kbnotif) {
            $this->boolean($notif->update(['id' => $kbnotif['id'], 'is_active' => 1]))->isTrue();
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
        $this->string($group->fields['name'])->isEqualTo('testknowbasegroup');

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
            $ntarget = $this->createItem(
                NotificationTarget::class,
                [
                    'notifications_id' => $kbnotif['id'],
                    'type' => Notification::GROUP_TYPE,
                    'items_id' => $group->fields['id'],
                ]
            );
            $this->array($ntarget->fields)->isIdenticalTo(
                [
                    'id' => $ntarget->fields['id'],
                    'items_id' => $group->fields['id'],
                    'type' => Notification::GROUP_TYPE,
                    'notifications_id' => $kbnotif['id']
                ]
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
        $count = count($notifqueue->find(['itemtype' => 'KnowbaseItem']));
        $this->integer($count)->isEqualTo(1);

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
        $count = count($notifqueue->find(['itemtype' => 'KnowbaseItem']));
        $this->integer($count)->isEqualTo(2);

        $this->deleteItem(
            \KnowbaseItem::class,
            $knowbaseitem->fields['id']
        );

        //test check if delete notification is in notification queue
        $notifqueue = new QueuedNotification();
        $count = count($notifqueue->find(['itemtype' => 'KnowbaseItem']));
        $this->integer($count)->isEqualTo(3);
    }
}
