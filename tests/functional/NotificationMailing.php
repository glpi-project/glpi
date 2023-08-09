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

use DbTestCase;
use NotificationTargetCertificate;

/* Test for inc/notificationmailing.class.php .class.php */

class NotificationMailing extends DbTestCase
{
    /**
     * @ignore
     * @see https://gitlab.alpinelinux.org/alpine/aports/issues/7392
     */
    public function testCheck()
    {
        $instance = new \NotificationMailing();

        $this->boolean($instance->check('user'))->isFalse();
        $this->boolean($instance->check('user@localhost'))->isTrue();
        $this->boolean($instance->check('user@localhost.dot'))->isTrue();
        if (!getenv('GLPI_SKIP_ONLINE')) {
            $this->boolean($instance->check('user@localhost.dot', ['checkdns' => true]))->isFalse();
            $this->boolean($instance->check('user@glpi-project.org', ['checkdns' => true]))->isTrue();
        }
    }

    public function testSendNotification()
    {
       //setup
        $this->login();

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

        $data = getAllDataFromTable('glpi_queuednotifications');
        $this->array($data)->hasSize(1);

        $row = array_pop($data);
        unset($row['id']);
        unset($row['create_time']);
        unset($row['send_time']);

        $this->array($row)
         ->isIdenticalTo([
             'itemtype'                 => 'NotificationMailing',
             'items_id'                 => 1,
             'notificationtemplates_id' => 0,
             'entities_id'              => 0,
             'is_deleted'               => 0,
             'sent_try'                 => 0,
             'sent_time'                => null,
             'name'                     => 'Test notification',
             'sender'                   => 'glpi@tests',
             'sendername'               => 'TEST',
             'recipient'                => '7',
             'recipientname'            => '',
             'replyto'                  => null,
             'replytoname'              => null,
             'headers'                  => '{"Auto-Submitted":"auto-generated","X-Auto-Response-Suppress":"OOF, DR, NDR, RN, NRN"}',
             'body_html'                => null,
             'body_text'                => 'Hello, this is a test notification.',
             'messageid'                => null,
             'documents'                => '',
             'mode'                     => 'mailing',
             'event'                    => 'test_notification',
             'attach_documents'         => 0,
             'itemtype_trigger'         => null,
             'items_id_trigger'         => 0,
         ]);
    }


    public function testDisabledNotification()
    {
        //setup
        $this->login();

        $user = new \User();
        $this->boolean((bool)$user->getFromDB(\Session::getLoginUserID()))->isTrue();
        $this->variable($user->fields['is_notif_enable_default'])->isNull(); //default value from user table
        $this->boolean((bool)$user->isUserNotificationEnable())->isTrue(); //like default configuration

        //should be sent
        $notification_target = new \NotificationTargetUser();
        $this->boolean($notification_target->validateSendTo("passwordexpires", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isTrue();

        //should be sent
        $notification_target = new \NotificationTargetTicket();
        $this->boolean($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isTrue();

        //should be sent
        $notification_target = new \NotificationTargetCertificate();
        $this->boolean($notification_target->validateSendTo("alert", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isTrue();

        //should be sent
        $notification_target = new \NotificationTargetProject();
        $this->boolean($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isTrue();

        //should be sent
        $notification_target = new \NotificationTargetReservation();
        $this->boolean($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isTrue();

        //update user to explicitly refuse notification
        $this->boolean($user->update([
            'id' => \Session::getLoginUserID(),
            'is_notif_enable_default' => '0'
        ], true))->isTrue();

        //check computed value
        $this->boolean($user->getFromDB(\Session::getLoginUserID()))->isTrue();
        $this->boolean((bool)$user->fields['is_notif_enable_default'])->isFalse();
        $this->boolean($user->isUserNotificationEnable())->isFalse();

        //Notification from NotificationTargetUser must be sent
        $notification_target = new \NotificationTargetUser();
        $this->boolean($notification_target->validateSendTo("passwordexpires", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isTrue();

        //Notification from NotificationTargetUser managed by `use_notification` property of actors
        //this sue case should by return true
        $notification_target = new \NotificationTargetTicket();
        $this->boolean($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isTrue();

        //should not be sent
        $notification_target = new \NotificationTargetCertificate();
        $this->boolean($notification_target->validateSendTo("alert", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isFalse();

        //should not be sent
        $notification_target = new \NotificationTargetProject();
        $this->boolean($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isFalse();

        //should not be sent
        $notification_target = new \NotificationTargetReservation();
        $this->boolean($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID()
        ], true))->isFalse();
    }
}
