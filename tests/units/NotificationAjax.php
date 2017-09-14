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

namespace tests\units;

use \DbTestCase;

/* Test for inc/notificationajax.class.php .class.php */

class NotificationAjax extends DbTestCase {

   public function testCheck() {
      $instance = new \NotificationAjax();
      $uid = getItemByTypeName('User', TU_USER, true);
      $this->boolean($instance->check($uid))->isTrue();
      $this->boolean($instance->check(0))->isFalse();
      $this->boolean($instance->check('abc'))->isFalse;
   }

   public function testSendNotification() {
      $this->boolean(\NotificationAjax::testNotification())->isTrue();
   }

   public function testGetMyNotifications() {
      global $CFG_GLPI;

      //setup
      $this->Login();

      $this->boolean(\NotificationAjax::testNotification())->isTrue();
      //another one
      $this->boolean(\NotificationAjax::testNotification())->isTrue();

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
         'to'                          => \Session::getLoginUserID()
      ]);
      $this->boolean($res)->isTrue();

      $notifs = \NotificationAjax::getMyNotifications();
      $this->array($notifs)->hasSize(1);
      $this->string($notifs[0]['url'])
         ->isIdenticalTo($computer->getFormURL(false) . '?id=' . $computer->getID());

      //reset
      $CFG_GLPI['notifications_ajax'] = 0;
   }
}
