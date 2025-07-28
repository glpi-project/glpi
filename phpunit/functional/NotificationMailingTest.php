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

use CronTask;
use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Ticket;
use User;

/* Test for inc/notificationmailing.class.php .class.php */

class NotificationMailingTest extends DbTestCase
{
    /**
     * @ignore
     * @see https://gitlab.alpinelinux.org/alpine/aports/issues/7392
     */
    public function testCheck()
    {
        $instance = new \NotificationMailing();

        $this->assertFalse($instance->check('user'));
        $this->assertTrue($instance->check('user@localhost'));
        $this->assertTrue($instance->check('user@localhost.dot'));
        if (!getenv('GLPI_SKIP_ONLINE')) {
            $this->assertFalse($instance->check('user@localhost.dot', ['checkdns' => true]));
            $this->assertTrue($instance->check('user@glpi-project.org', ['checkdns' => true]));
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
            'event'                       => 'test_notification',
        ]);
        $this->assertTrue($res);

        $data = getAllDataFromTable('glpi_queuednotifications');
        $this->assertCount(1, $data);

        $row = array_pop($data);
        unset($row['id']);
        unset($row['create_time']);
        unset($row['send_time']);

        $this->assertSame(
            [
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
                'recipient'                => (string) \Session::getLoginUserID(),
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
            ],
            $row
        );
    }

    public static function sendImmediatelyProvider()
    {
        yield [
            'itemtype' => CronTask::class,
            'event'    => 'alert',
            'expected' => true,
        ];

        yield [
            'itemtype' => User::class,
            'event'    => 'passwordexpires',
            'expected' => true,
        ];

        yield [
            'itemtype' => User::class,
            'event'    => 'passwordforget',
            'expected' => true,
        ];

        yield [
            'itemtype' => User::class,
            'event'    => 'passwordinit',
            'expected' => true,
        ];

        yield [
            'itemtype' => Ticket::class,
            'event'    => 'new',
            'expected' => false,
        ];

        yield [
            'itemtype' => Ticket::class,
            'event'    => 'add_followup',
            'expected' => false,
        ];
    }

    #[DataProvider('sendImmediatelyProvider')]
    public function testSendImmediately(string $itemtype, string $event, bool $expected)
    {
        $this->login();

        $mail = new \NotificationMailing();
        $this->assertTrue($mail->sendNotification([
            '_itemtype'                   => $itemtype,
            '_items_id'                   => \rand(1, 100),
            '_notificationtemplates_id'   => 0,
            '_entities_id'                => $this->getTestRootEntity(true),
            'fromname'                    => 'TEST',
            'subject'                     => 'Test notification',
            'content_text'                => "Hello, this is a test notification.",
            'to'                          => 'test@localhost',
            'from'                        => 'glpi@tests',
            'toname'                      => '',
            'event'                       => $event,
        ]));

        if ($expected) {
            // Emails cannot be sent in the testing env
            $this->hasSessionMessageThatContains('Error in sending the email', ERROR);
        }

        // the email should only be in the queue because we cannot send email in tests
        // to identify that it was attempted to be sent immediately, we check the sent_try field
        $queued = getAllDataFromTable('glpi_queuednotifications');
        $this->assertCount(1, $queued);
        $queued = reset($queued);
        $this->assertEquals($expected ? 1 : 0, $queued['sent_try']);
    }


    public function testDisabledNotification()
    {
        //setup
        $this->login();

        $user = new User();
        $this->assertTrue((bool) $user->getFromDB(\Session::getLoginUserID()));
        $this->assertNull($user->fields['is_notif_enable_default']); //default value from user table
        $this->assertTrue((bool) $user->isUserNotificationEnable()); //like default configuration

        //should be sent
        $notification_target = new \NotificationTargetUser();
        $this->assertTrue($notification_target->validateSendTo("passwordexpires", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //should be sent
        $notification_target = new \NotificationTargetTicket();
        $this->assertTrue($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //should be sent
        $notification_target = new \NotificationTargetCertificate();
        $this->assertTrue($notification_target->validateSendTo("alert", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //should be sent
        $notification_target = new \NotificationTargetProject();
        $this->assertTrue($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //should be sent
        $notification_target = new \NotificationTargetReservation();
        $this->assertTrue($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //update user to explicitly refuse notification
        $this->assertTrue($user->update([
            'id' => \Session::getLoginUserID(),
            'is_notif_enable_default' => '0',
        ], true));

        //check computed value
        $this->assertTrue($user->getFromDB(\Session::getLoginUserID()));
        $this->assertFalse((bool) $user->fields['is_notif_enable_default']);
        $this->assertFalse($user->isUserNotificationEnable());

        //Notification from NotificationTargetUser must be sent
        $notification_target = new \NotificationTargetUser();
        $this->assertTrue($notification_target->validateSendTo("passwordexpires", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //Notification from NotificationTargetUser managed by `use_notification` property of actors
        //this sue case should by return true
        $notification_target = new \NotificationTargetTicket();
        $this->assertTrue($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //should not be sent
        $notification_target = new \NotificationTargetCertificate();
        $this->assertFalse($notification_target->validateSendTo("alert", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //should not be sent
        $notification_target = new \NotificationTargetProject();
        $this->assertFalse($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID(),
        ], true));

        //should not be sent
        $notification_target = new \NotificationTargetReservation();
        $this->assertFalse($notification_target->validateSendTo("new", [
            "users_id" => \Session::getLoginUserID(),
        ], true));
    }
}
