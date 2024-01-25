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

/* Test for inc/notificationajax.class.php .class.php */

class NotificationAjax extends DbTestCase
{
    public function testCheck()
    {
        $instance = new \NotificationAjax();
        $uid = getItemByTypeName('User', TU_USER, true);
        $this->boolean($instance->check($uid))->isTrue();
        $this->boolean($instance->check(0))->isFalse();
        $this->boolean($instance->check('abc'))->isFalse;
    }

    public function testSendNotification()
    {
        $this->boolean(\NotificationAjax::testNotification())->isTrue();
    }

    public function testGetMyNotifications()
    {
        global $CFG_GLPI;

       //setup
        $this->login();

        $this->boolean(\NotificationAjax::testNotification())->isTrue();
       //another one
        $this->boolean(\NotificationAjax::testNotification())->isTrue();

       //also add a mailing notification to make sure we get only ajax ons back #2997
        $instance = new \NotificationMailing();
        $res = $instance->sendNotification([
            '_itemtype'                   => 'NotificationMailing',
            '_items_id'                   => 1,
            '_notificationtemplates_id'   => 0,
            '_entities_id'                => 0,
            'fromname'                    => 'TEST',
            'subject'                     => 'Test notification',
            'content_text'                => "Hello, this is a test notification.",
            'to'                          => \Session::getLoginUserID(),
            'from'                        => 'glpi@tests',
            'toname'                      => '',
            'event'                       => 'test_notification'
        ]);
        $this->boolean($res)->isTrue();

       //ajax notifications disabled: gets nothing.
        $notifs = \NotificationAjax::getMyNotifications();
        $this->boolean($notifs)->isFalse();

        $CFG_GLPI['notifications_ajax'] = 1;

        $notifs = \NotificationAjax::getMyNotifications();
        $this->array($notifs)->hasSize(2);

        foreach ($notifs as $notif) {
            unset($notif['id']);
            $this->array($notif)->isIdenticalTo([
                'title'  => 'Test notification',
                'body'   => 'Hello, this is a test notification.',
                'url'    => null
            ]);
        }

       //while not deleted, still 2 notifs available
        $notifs = \NotificationAjax::getMyNotifications();
        $this->array($notifs)->hasSize(2);

       //void method
        \NotificationAjax::raisedNotification($notifs[1]['id']);

        $expected = $notifs[0];
        $notifs = \NotificationAjax::getMyNotifications();
        $this->array($notifs)
         ->hasSize(1);
        $this->array($notifs[0])->isIdenticalTo($expected);

       //void method
        \NotificationAjax::raisedNotification($notifs[0]['id']);
        $notifs = \NotificationAjax::getMyNotifications();
        $this->boolean($notifs)->isFalse();

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $instance = new \NotificationAjax();
        $res = $instance->sendNotification([
            '_itemtype'                   => 'Computer',
            '_items_id'                   => $computer->getId(),
            '_notificationtemplates_id'   => 0,
            '_entities_id'                => 0,
            'fromname'                    => 'TEST',
            'subject'                     => 'Test notification',
            'content_text'                => "Hello, this is a test notification.",
            'to'                          => \Session::getLoginUserID(),
            'event'                       => 'test_notification',
        ]);
        $this->boolean($res)->isTrue();

        $notifs = \NotificationAjax::getMyNotifications();
        $this->array($notifs)->hasSize(1);
        $this->string($notifs[0]['url'])
         ->isIdenticalTo($computer->getFormURLWithID($computer->fields['id'], true));

       //reset
        $CFG_GLPI['notifications_ajax'] = 0;
    }
}
