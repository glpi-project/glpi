<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

        global $CFG_GLPI;
        Config::setConfigurationValues('core', ['use_notifications' => 1]);
        Config::setConfigurationValues('core', ['notifications_mailing' => 1]);

        //set notification by mail to active
        $config = new Config();
        $config->getFromDBByCrit([
            'name' => 'use_notifications',
        ]);
        $config->getFromDBByCrit([
            'name' => 'notifications_mailing',
        ]);

        $notif = new Notification();
        $knowbasenotifs = $notif->find(
            [
                'itemtype' => 'KnowbaseItem'
            ]
        );
        // test activate notification
        foreach ($knowbasenotifs as $kbnotif) {
            $datawant = $kbnotif;
            $datawant['is_active'] = 1;
            $notif->update($datawant);
            $kbnotif = $notif->getById($kbnotif['id'])->fields;
            $kbnotif['date_mod'] = null;
            $this->array($kbnotif)->isIdenticalTo($datawant);
        }
        //search glpi user
        $user = new \User();
        $user->getFromDBByCrit(['name' => 'glpi']);
        $userid = $user->getID();
        $email = new \UserEmail();
        $emailid = $email->add(
            [
                'users_id' => $userid,
                'email' => 'test@test.com',
                'is_default' => 1,
            ]
        );

        // test create group
        $groups = new Group();
        $groupid = $groups->add(
            [
                'name' => 'testknowbasegroup',
            ]
        );
        $group = $groups->getById($groupid)->fields;
        $this->string($group['name'])->isEqualTo('testknowbasegroup');

        //add user to group
        $groupsusers = new \Group_User();
        $groupsusers->add(
            [
                'groups_id' => $group['id'],
                'users_id' => $user->getID(),
            ]
        );

        //test add group for notification
        $notiftarget = new NotificationTarget();
        foreach ($knowbasenotifs as $kbnotif) {
            $ntargetid = $notiftarget->add(
                [
                    'notifications_id' => $kbnotif['id'],
                    'type' => 3,
                    'items_id' => $group['id'],
                ]
            );
            $ntargetdata = $notiftarget->getById($ntargetid)->fields;
            $this->array($ntargetdata)->isIdenticalTo(
                [
                    'id' => $ntargetid,
                    'items_id' => $group['id'],
                    'type' => 3,
                    'notifications_id' => $kbnotif['id']
                ]
            );
        }

        //create/update/delete knowbase item
        $knowbaseitem = new \KnowbaseItem();
        $id = $knowbaseitem->add(
            [
                'name' => 'testknowbaseitem',
                'answer' => 'testknowbaseitem',
                'users_id' => 2,
            ]
        );
        $knowbaseitem->update(
            [
                'id' => $id,
                'name' => 'testknowbaseitemupdate',
                'answer' => 'testknowbaseitemupdate',
                'users_id' => 2,
            ]
        );
        $knowbaseitem->delete(['id' => $id]);

        //test check if is in notification queue
        $notifqueue = new QueuedNotification();
        $count = count($notifqueue->find(['itemtype' => 'KnowbaseItem']));
        $this->integer($count)->isEqualTo(3);
    }
}
