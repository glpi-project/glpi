<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
*/

/* Test for inc/notificationajax.class.php .class.php */

class NotificationAjaxTest extends DbTestCase {

   public function testCheck() {
      $instance = new \NotificationAjax();
      $uid = getItemByTypeName('User', TU_USER, true);
      $this->assertTrue($instance->check($uid));
      $this->assertFalse($instance->check(0));
      $this->assertFalse($instance->check('abc'));
   }

   public function testSendNotification() {
      $this->assertTrue(\NotificationAjax::testNotification());
   }

   public function testGetMyNotifications() {
      global $CFG_GLPI;

      //setup
      $this->Login();

      $this->assertTrue(\NotificationAjax::testNotification());
      $this->assertTrue(\NotificationAjax::testNotification());

      //ajax notifications disabled: gets nothing.
      $notifs = \NotificationAjax::getMyNotifications();
      $this->assertFalse($notifs);

      $CFG_GLPI['notifications_ajax'] = 1;

      $notifs = \NotificationAjax::getMyNotifications();
      $this->assertCount(2, $notifs);

      foreach ($notifs as $notif) {
         $this->assertEquals('Test notification', $notif['title']);
         $this->assertEquals('Hello, this is a test notification.', $notif['body']);
         $this->assertNull($notif['url']);
      }

      //while not deleted, still 2 notifs available
      $notifs = \NotificationAjax::getMyNotifications();
      $this->assertCount(2, $notifs);

      //void method
      \NotificationAjax::raisedNotification($notifs[1]['id']);

      $expected = $notifs[0];
      $notifs = \NotificationAjax::getMyNotifications();
      $this->assertCount(1, $notifs);
      $this->assertEquals($expected, $notifs[0]);

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
         'to'                          => Session::getLoginUserID()
      ]);
      $this->assertTrue($res);

      $notifs = \NotificationAjax::getMyNotifications();
      $this->assertCount(1, $notifs);
      $this->assertEquals(
         $computer->getFormURL() . '?id=' . $computer->getID(),
         $notifs[0]['url']
      );

      //reset
      $CFG_GLPI['notifications_ajax'] = 0;

   }
}
