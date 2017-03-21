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

/* Test for inc/notificationeventajax.class.php */

class NotificationEventAjaxTest extends DbTestCase {

   public function testGetTargetField() {
      $data = [];
      $this->assertEquals('users_id', \NotificationEventAjax::getTargetField($data));

      $expected = ['users_id' => null];
      $this->assertEquals($expected, $data);

      $data = ['users_id' => '121'];
      $this->assertEquals('users_id', \NotificationEventAjax::getTargetField($data));

      $expected = ['users_id' => '121'];
      $this->assertEquals($expected, $data);
   }

   public function testCanCron() {
      $this->assertFalse(\NotificationEventAjax::canCron());
   }

   public function testGetAdminData() {
      $this->assertFalse(\NotificationEventAjax::getAdminData());
   }

   public function testGetEntityAdminsData() {
      $this->assertFalse(\NotificationEventAjax::getEntityAdminsData(0));
   }

   public function testSend() {
      $this->assertFalse(\NotificationEventAjax::send([]));
   }

   public function testRaise() {
      global $CFG_GLPI, $DB;

      //enable notifications
      $CFG_GLPI['use_notifications'] = 1;
      $CFG_GLPI['notifications_ajax'] = 1;

      $this->login();

      $ticket = new Ticket();
      $ticket->notificationqueueonaction = false;
      $uid = getItemByTypeName('User', TU_USER, true);
      $this->assertGreaterThanOrEqual(
         1,
         $ticket->add([
            'description'           => 'My ticket to be notified.',
            '_users_id_requester'   => $uid
         ])
      );

      //event has been raised; it is in the queue!
      $queue = getAllDatasFromTable('glpi_queuednotifications');

      //no ajax notification configured per default
      $this->assertCount(0, $queue);

      //add an ajax notification on tickets creation
      $iterator = $DB->request([
         'FROM'   => Notification::getTable(),
         'WHERE'  => [
            'itemtype'  => Ticket::getType(),
            'event'     => 'new'
         ]
      ]);
      $this->assertEquals(1, $iterator->numRows());
      $row = $iterator->next();
      $notif_id = $row['id'];

      $iterator = $DB->request([
         'FROM'   => Notification_NotificationTemplate::getTable(),
         'WHERE'  => [
            'notifications_id'   => $notif_id,
            'mode'               => Notification_NotificationTemplate::MODE_MAIL
         ]
      ]);
      $this->assertEquals(1, $iterator->numRows());
      $row = $iterator->next();
      unset($row['id']);
      $row['mode'] = Notification_NotificationTemplate::MODE_AJAX;
      $notiftpltpl = new Notification_NotificationTemplate();
      $this->assertGreaterThanOrEqual(
         1,
         $notiftpltpl->add($row)
      );

      $this->assertGreaterThanOrEqual(
         1,
         $ticket->add([
            'description'           => 'My ticket to be notified.',
            '_users_id_requester'   => $uid
         ])
      );

      //event has been raised; it is in the queue!
      $queue = getAllDatasFromTable('glpi_queuednotifications');

      //no ajax notification configured per default
      $this->assertCount(1, $queue);

      $data = array_pop($queue);
      unset($data['id']);
      unset($data['create_time']);
      unset($data['send_time']);
      unset($data['messageid']);
      $data['body_text'] = preg_replace(
         '/(Opening date).+/m',
         '$1 OPENING',
         $data['body_text']
      );
      $expected = [
         'itemtype' => 'Ticket',
         'items_id' => $ticket->getID(),
         'notificationtemplates_id' => '4',
         'entities_id' => '0',
         'is_deleted' => '0',
         'sent_try' => '0',
         'sent_time' => null,
         'name' => '[GLPI #' . str_pad($ticket ->getID(), 7, '0', STR_PAD_LEFT).'] New ticket ',
         'sender' => null,
         'sendername' => '',
         'recipient' => $uid,
         'recipientname' => null,
         'replyto' => null,
         'replytoname' => null,
         'headers' => '',
         'body_html' => null,
         'body_text' => 'URL : http://localhost:8088/index.php?redirect=ticket_'.$ticket->getID().'&amp;noAUTO=1 

Ticket: Description

Title : 
 Requesters : _test_user 
 Opening date OPENING
 Closing date : 
 Request source : Helpdesk
Associated item :

Status : New

Urgency : Medium
 Impact : Medium
 Priority : Medium

No defined category 
 Description : 

Number of followups : 0

Number of tasks : 0

-- 

Automatically generated by GLPI 9.2

',
         'documents' => '',
         'mode' => 'ajax'
      ];
      $this->assertEquals($expected, $data);

      //reset
      $CFG_GLPI['use_notifications'] = 0;
      $CFG_GLPI['notifications_ajax'] = 0;
   }
}
