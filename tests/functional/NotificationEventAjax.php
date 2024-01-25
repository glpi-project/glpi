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

/* Test for inc/notificationeventajax.class.php */

class NotificationEventAjax extends DbTestCase
{
    public function testGetTargetField()
    {
        $data = [];
        $this->string(\NotificationEventAjax::getTargetField($data))->isIdenticalTo('users_id');

        $expected = ['users_id' => null];
        $this->array($data)->isIdenticalTo($expected);

        $data = ['users_id' => '121'];
        $this->string(\NotificationEventAjax::getTargetField($data))->isIdenticalTo('users_id');

        $expected = ['users_id' => '121'];
        $this->array($data)->isIdenticalTo($expected);
    }

    public function testCanCron()
    {
        $this->boolean(\NotificationEventAjax::canCron())->isFalse();
    }

    public function testGetAdminData()
    {
        $this->array(\NotificationEventAjax::getAdminData())->isIdenticalTo([]);
    }

    public function testGetEntityAdminsData()
    {
        $this->array(\NotificationEventAjax::getEntityAdminsData(0))->isIdenticalTo([]);
    }

    public function testSend()
    {
        $this->when(
            function () {
                $this->boolean(\NotificationEventAjax::send([]))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('NotificationEventAjax::send should not be called!')
         ->exists();
    }

    public function testRaise()
    {
        global $CFG_GLPI, $DB;

       //enable notifications
        $CFG_GLPI['use_notifications'] = 1;
        $CFG_GLPI['notifications_ajax'] = 1;

        $this->login();

        $ticket = new \Ticket();
        $uid = getItemByTypeName('User', TU_USER, true);
        $this->integer(
            (int)$ticket->add([
                'name'                  => '',
                'description'           => 'My ticket to be notified.',
                '_users_id_requester'   => $uid,
                'content'               => ''
            ])
        )->isGreaterThan(0);

       //event has been raised; it is in the queue!
        $queue = getAllDataFromTable('glpi_queuednotifications');

       //no ajax notification configured per default
        $this->array($queue)->hasSize(0);

       //add an ajax notification on tickets creation
        $iterator = $DB->request([
            'FROM'   => \Notification::getTable(),
            'WHERE'  => [
                'itemtype'  => \Ticket::getType(),
                'event'     => 'new'
            ]
        ]);
        $this->integer($iterator->numRows())->isIdenticalTo(1);
        $row = $iterator->current();
        $notif_id = $row['id'];

        $iterator = $DB->request([
            'FROM'   => \Notification_NotificationTemplate::getTable(),
            'WHERE'  => [
                'notifications_id'   => $notif_id,
                'mode'               => \Notification_NotificationTemplate::MODE_MAIL
            ]
        ]);
        $this->integer($iterator->numRows())->isIdenticalTo(1);
        $row = $iterator->current();
        unset($row['id']);
        $row['mode'] = \Notification_NotificationTemplate::MODE_AJAX;
        $notiftpltpl = new \Notification_NotificationTemplate();
        $this->integer($notiftpltpl->add($row))->isGreaterThan(0);

        $this->integer(
            (int)$ticket->add([
                'name'                  => '',
                'description'           => 'My ticket to be notified.',
                '_users_id_requester'   => $uid,
                'content'               => ''
            ])
        )->isGreaterThan(0);

       //event has been raised; it is in the queue!
        $queue = getAllDataFromTable('glpi_queuednotifications');

       //no ajax notification configured per default
        $this->array($queue)->hasSize(1);

        $GLPI_URI = GLPI_URI;

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
            'notificationtemplates_id' => 4,
            'entities_id' => 0,
            'is_deleted' => 0,
            'sent_try' => 0,
            'sent_time' => null,
            'name' => '[GLPI #' . str_pad($ticket ->getID(), 7, '0', STR_PAD_LEFT) . '] New ticket ',
            'sender' => null,
            'sendername' => '',
            'recipient' => (string) $uid,
            'recipientname' => null,
            'replyto' => null,
            'replytoname' => null,
            'headers' => '',
            'body_html' => null,
            'body_text' => <<<TEXT
 
  URL : {$GLPI_URI}/index.php?redirect=ticket_{$ticket->getID()}&#38;noAUTO=1 

 Ticket: Description

 Title : 
 Requesters :  _test_user  
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

Automatically generated by GLPI


TEXT,
            'documents' => '',
            'mode' => 'ajax',
            'event' => 'new',
        ];
        $this->array($data)->isIdenticalTo($expected);

       //reset
        $CFG_GLPI['use_notifications'] = 0;
        $CFG_GLPI['notifications_ajax'] = 0;
    }
}
