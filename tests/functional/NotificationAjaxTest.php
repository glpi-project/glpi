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

use DbTestCase;

/* Test for inc/notificationajax.class.php .class.php */

class NotificationAjaxTest extends DbTestCase
{
    public function testCheck()
    {
        $instance = new \NotificationAjax();
        $uid = getItemByTypeName('User', TU_USER, true);
        $this->assertTrue($instance->check($uid));
        $this->assertFalse($instance->check(0));
        $this->assertFalse($instance->check('abc'));
    }

    public function testSendNotification()
    {
        $this->assertTrue(\NotificationAjax::testNotification());
    }

    public function testGetMyNotifications()
    {
        global $CFG_GLPI;

        //setup
        $this->login();

        $this->assertTrue(\NotificationAjax::testNotification());
        //another one
        $this->assertTrue(\NotificationAjax::testNotification());

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
            'event'                       => 'test_notification',
        ]);
        $this->assertTrue($res);

        //ajax notifications disabled: gets nothing.
        $notifs = \NotificationAjax::getMyNotifications();
        $this->assertFalse($notifs);

        $CFG_GLPI['notifications_ajax'] = 1;

        $notifs = \NotificationAjax::getMyNotifications();
        $this->assertCount(2, $notifs);

        foreach ($notifs as $notif) {
            unset($notif['id']);
            $this->assertSame(
                [
                    'title'  => 'Test notification',
                    'body'   => 'Hello, this is a test notification.',
                    'url'    => null,
                ],
                $notif
            );
        }

        //while not deleted, still 2 notifs available
        $notifs = \NotificationAjax::getMyNotifications();
        $this->assertCount(2, $notifs);

        //void method
        \NotificationAjax::raisedNotification($notifs[1]['id']);

        $expected = $notifs[0];
        $notifs = \NotificationAjax::getMyNotifications();
        $this->assertCount(1, $notifs);
        $this->assertSame($expected, $notifs[0]);

        //void method
        \NotificationAjax::raisedNotification($notifs[0]['id']);
        $notifs = \NotificationAjax::getMyNotifications();
        $this->assertFalse($notifs);

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
        $this->assertTrue($res);

        $notifs = \NotificationAjax::getMyNotifications();
        $this->assertCount(1, $notifs);
        $this->assertSame(
            $computer->getFormURLWithID($computer->fields['id'], true),
            $notifs[0]['url']
        );

        //reset
        $CFG_GLPI['notifications_ajax'] = 0;
    }
}
