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
             'event'                    => 'test_notification'
         ]);
    }
}
