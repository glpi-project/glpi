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

/* Test for inc/notificationeventajax.class.php */

class NotificationEventMailing extends DbTestCase
{
    public function testGetTargetField()
    {
        $data = [];
        $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');

        $expected = ['email' => null];
        $this->array($data)->isIdenticalTo($expected);

        $data = ['email' => 'user'];
        $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');

        $expected = ['email' => null];
        $this->array($data)->isIdenticalTo($expected);

        $data = ['email' => 'user@localhost'];
        $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');

        $expected = ['email' => 'user@localhost'];
        $this->array($data)->isIdenticalTo($expected);

        $uid = getItemByTypeName('User', TU_USER, true);
        $data = ['users_id' => $uid];

        $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');
        $expected = [
            'users_id'  => $uid,
            'email'     => TU_USER . '@glpi.com'
        ];
        $this->array($data)->isIdenticalTo($expected);
    }

    public function testCanCron()
    {
        $this->boolean(\NotificationEventMailing::canCron())->isTrue();
    }

    public function testGetAdminData()
    {
        global $CFG_GLPI;

        $this->array(\NotificationEventMailing::getAdminData())
         ->isIdenticalTo([
             'email'     => $CFG_GLPI['admin_email'],
             'name'      => $CFG_GLPI['admin_email_name'],
             'language'  => $CFG_GLPI['language']
         ]);

        $CFG_GLPI['admin_email'] = 'adminlocalhost';

        $this->when(
            function () {
                $this->boolean(\NotificationEventMailing::getAdminData())->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Invalid email address "adminlocalhost" configured in "admin_email".')
         ->exists();
    }

    public function testGetEntityAdminsData()
    {
        $this->login();

        $entity1 = getItemByTypeName('Entity', '_test_child_1');
        $this->boolean(
            $entity1->update([
                'id'                 => $entity1->getId(),
                'admin_email'        => 'entadmin@localhost',
                'admin_email_name'   => 'Entity admin ONE'
            ])
        )->isTrue();

        $sub_entity1 = $this->createItem(\Entity::class, ['name' => 'sub entity', 'entities_id' => $entity1->getId()]);

        $entity2 = getItemByTypeName('Entity', '_test_child_2');
        $this->boolean(
            $entity2->update([
                'id'                 => $entity2->getId(),
                'admin_email'        => 'entadmin2localhost',
                'admin_email_name'   => 'Entity admin TWO'
            ])
        )->isTrue();

        $entity0_result = [
            [
                'name'     => '',
                'email'    => 'admsys@localhost',
                'language' => 'en_GB',
            ]
        ];
        $this->array(\NotificationEventMailing::getEntityAdminsData(0))->isEqualTo($entity0_result);
        $entity1_result = [
            [
                'name'     => 'Entity admin ONE',
                'email'    => 'entadmin@localhost',
                'language' => 'en_GB',
            ]
        ];
        $this->array(\NotificationEventMailing::getEntityAdminsData($entity1->getID()))->isEqualTo($entity1_result);
        $this->array(\NotificationEventMailing::getEntityAdminsData($sub_entity1->getID()))->isEqualTo($entity1_result);

        $this->when(
            function () use ($entity2, $entity0_result) {
                $this->array(\NotificationEventMailing::getEntityAdminsData($entity2->getID()))->isEqualTo($entity0_result);
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Invalid email address "entadmin2localhost" configured for entity "' . $entity2->getID() . '". Default administrator email will be used.')
         ->exists();
    }

    public function testMemoryUsageFromImgAndNotification()
    {
        global $DB;
        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        // Mock mailer transport
        $transport = new class () extends \Symfony\Component\Mailer\Transport\AbstractTransport {
            public $sent_email;

            protected function doSend(\Symfony\Component\Mailer\SentMessage $message): void
            {
                // Extract message from envelope
                $envelope_reflection = new \ReflectionClass(\Symfony\Component\Mailer\DelayedEnvelope::class);
                /* @var \Symfony\Component\Mime\Email $email */
                $this->sent_email = $envelope_reflection->getProperty('message')->getValue($message->getEnvelope());
            }

            public function __toString(): string
            {
                return 'test://';
            }
        };

        // Enable notifications
        $CFG_GLPI['use_notifications'] = $CFG_GLPI['notifications_mailing'] = true;

        $update_ticket_notif   = new \Notification();
        $this->boolean($update_ticket_notif->getFromDBByCrit(['itemtype' => \Ticket::class, 'event' => 'add_followup']))->isTrue();


        // Ensure only tested notification is active
        $deactivated = $DB->update(
            \Notification::getTable(),
            ['is_active' => false],
            ['id' => ['<>', $update_ticket_notif->getID()]]
        );
        $this->boolean($deactivated)->isTrue();
        $this->boolean($update_ticket_notif->update(['id' => $update_ticket_notif->getID(), 'is_active' => 1]))->isTrue();

        // Ensure that there is no notification queued
        $this->integer(countElementsInTable(\QueuedNotification::getTable(), ['is_deleted' => 0]))->isEqualTo(0);

        // Create new ticket
        $ticket = new \Ticket();
        $ticket->add([
            'name' => $this->getUniqueString(),
            'content' => 'test',
        ]);
        $this->boolean($ticket->isNewItem())->isFalse();

        //create ITILFollup with heavy img
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/heavy_img.png'));
        $user = getItemByTypeName('User', TU_USER, true);
        $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $instance = new \ITILFollowup();
        $input = [
            'users_id' => $user,
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'name'    => 'a followup',
            'content' => <<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ]
        ];
        copy(__DIR__ . '/../fixtures/uploads/heavy_img.png', GLPI_TMP_DIR . '/' . $filename);

        $instance->add($input);

        $this->boolean($instance->isNewItem())->isFalse();
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['content'])->contains($expected);

        $queued_notifications = getAllDataFromTable(\QueuedNotification::getTable(), ['is_deleted' => 0]);
        $this->array($queued_notifications)->hasSize(1);


        $item_start = microtime(true);

        \NotificationEventMailing::setMailer(new \GLPIMailer($transport));
        \NotificationEventMailing::send($queued_notifications);
        \NotificationEventMailing::setMailer(null);


        $exec_time = round(microtime(true) - $item_start, 5);
        $bench = [
            'exectime'  => $exec_time,
            'mem'       => memory_get_usage(),
            'mem_real'  => memory_get_usage(true),
            'mem_peak'  => memory_get_peak_usage(),
        ];
    }
}
