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

/* Test for inc/notificationmailing.class.php .class.php */

class NotificationMailingTest extends DbTestCase {

   public function testCheck() {
      $instance = new \NotificationMailing();

      $this->assertFalse($instance->check('user'));
      $this->assertTrue($instance->check('user@localhost'));
      $this->assertTrue($instance->check('user@localhost.dot'));
      $this->assertFalse($instance->check('user@localhost.dot', ['checkdns' => true]));
      $this->assertTrue($instance->check('user@glpi-project.org', ['checkdns' => true]));
   }

   public function testSendNotification() {
      //setup
      $this->Login();

      $instance = new \NotificationMailing();
      $res = $instance->sendNotification([
         '_itemtype'                   => 'NotificationMailing',
         '_items_id'                   => 1,
         '_notificationtemplates_id'   => 0,
         '_entities_id'                => 0,
         'fromname'                    => 'TEST',
         'subject'                     => 'Test notification',
         'content_text'                => "Hello, this is a test notification.",
         'to'                          => Session::getLoginUserID()
      ]);
      $this->assertTrue($res);

      $data = getAllDatasFromTable('glpi_queuednotifications');
      $this->assertCount(1, $data);

      $row = array_pop($data);
      unset($row['id']);
      unset($row['create_time']);
      unset($row['send_time']);

      $this->assertEquals(
         [
            'itemtype'                 => 'NotificationMailing',
            'items_id'                 => '1',
            'notificationtemplates_id' => '0',
            'entities_id'              => '0',
            'is_deleted'               => '0',
            'sent_try'                 => '0',
            'sent_time'                => null,
            'name'                     => 'Test notification',
            'sender'                   => '',
            'sendername'               => 'TEST',
            'recipient'                => '6',
            'recipientname'            => '',
            'replyto'                  => null,
            'replytoname'              => null,
            'headers'                  => '{"Auto-Submitted":"auto-generated","X-Auto-Response-Suppress":"OOF, DR, NDR, RN, NRN"}',
            'body_html'                => null,
            'body_text'                => 'Hello, this is a test notification.',
            'messageid'                => null,
            'documents'                => '',
            'mode'                     => 'mailing'
         ],
         $row
      );
   }
}
