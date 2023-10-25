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
use QueuedNotification;

/* Test for inc/notification.class.php */

class Notification extends DbTestCase
{
    public function testGetMailingSignature()
    {
        global $CFG_GLPI;

        $this->login();

        $root    = getItemByTypeName('Entity', 'Root entity', true);
        $parent  = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $CFG_GLPI['mailing_signature'] = 'global_signature';

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("global_signature");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("global_signature");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("global_signature");

        $entity = new \Entity();
        $this->boolean($entity->update([
            'id'                => $root,
            'mailing_signature' => "signature_root",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_root");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_root");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_root");

        $this->boolean($entity->update([
            'id'                => $parent,
            'mailing_signature' => "signature_parent",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_parent");

        $this->boolean($entity->update([
            'id'                => $child_1,
            'mailing_signature' => "signature_child_1",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_child_1");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_parent");

        $this->boolean($entity->update([
            'id'                => $child_2,
            'mailing_signature' => "signature_child_2",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_child_1");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_child_2");
    }

    /**
     * Functionnal test on filtering a notification's target
     *
     * @return void
     */
    public function testFilter(): void
    {
        global $CFG_GLPI, $DB;

        $target_notification = "New Ticket";
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        $this->login();
        $this->integer(countElementsInTable(QueuedNotification::getTable()))->isEqualTo(0);

        // Enable notifications
        $CFG_GLPI['use_notifications'] = true;
        $CFG_GLPI['notifications_mailing'] = true;

        // Activate only new followup notification
        $success = $DB->update(\Notification::getTable(), ['is_active' => false], [
            'name' => ['<>', $target_notification]
        ]);
        $this->boolean($success)->isTrue();
        $this->integer(
            countElementsInTable(
                \Notification::getTable(),
                ['is_active' => true]
            )
        )->isEqualTo(1);

        // Create categories
        $cat_A = $this->createItem("ITILCategory", ["name" => "cat A"]);
        $cat_B = $this->createItem("ITILCategory", ["name" => "cat B"]);

        // Filter notification on category
        /** @var \Notification $notification */
        $notification = getItemByTypeName("Notification", $target_notification);
        $success = $notification->saveFilter([
            [
                "link"       => "and",
                "field"      => 7,                      // Category
                "searchtype" => "equals",
                "value"      => $cat_B->fields['id'],
            ]
        ]);
        $this->boolean($success)->isTrue();

        // Create tickets
        $this->createItem("Ticket", [
            "name"              => "Test",
            "content"           => "Test",
            "itilcategories_id" => $cat_A->fields['id'],
            "entities_id"       => $entity,
        ]);
        $this->integer(countElementsInTable(QueuedNotification::getTable()))->isEqualTo(0);

        $this->createItem("Ticket", [
            "name"              => "Test",
            "content"           => "Test",
            "itilcategories_id" => $cat_B->fields['id'],
            "entities_id"       => $entity,
        ]);
        $this->integer(countElementsInTable(QueuedNotification::getTable()))->isEqualTo(1);
    }

    protected function attachedDocumentsProvider(): iterable
    {
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        $update_ticket_notif   = new \Notification();
        $this->boolean($update_ticket_notif->getFromDBByCrit(['itemtype' => \Ticket::class, 'event' => 'update']))->isTrue();
        $update_followup_notif = new \Notification();
        $this->boolean($update_followup_notif->getFromDBByCrit(['itemtype' => \Ticket::class, 'event' => 'update_followup']))->isTrue();
        $update_task_notif = new \Notification();
        $this->boolean($update_task_notif->getFromDBByCrit(['itemtype' => \Ticket::class, 'event' => 'update_task']))->isTrue();

        // Create a ticket and attach documents to it
        $filename = $this->createUploadedImage($prefix = uniqid('', true));
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name'        => __FUNCTION__,
            'content'     => <<<HTML
<p>Test ticket with image</p>
<p><img id="aaaaaaaa-aaaaaaaa-aaaaaaaaaaaaaa.00000000" src="data:image/png;base64,aaa=" /></p>
HTML,
            'entities_id' => $entity,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                'aaaaaaaa-aaaaaaaa-aaaaaaaaaaaaaa.00000000',
            ],
            '_prefix_filename' => [
                $prefix,
            ]
        ]);
        $this->integer($ticket_id)->isGreaterThan(0);
        $ticket_img = new \Document();
        $this->boolean($ticket_img->getFromDBByCrit(['tag' => 'aaaaaaaa-aaaaaaaa-aaaaaaaaaaaaaa.00000000']))->isTrue();

        // Create large text document (15MB)
        $ticket_doc = $this->createTxtDocument(15728640);
        $this->createItem(
            \Document_Item::class,
            [
                'documents_id' => $ticket_doc->getID(),
                'itemtype'     => $ticket->getType(),
                'items_id'     => $ticket->getID(),
            ]
        );

        // Create a followup and attach documents to it (big img ~15Mb)
        $filename = $this->createUploadedImage($prefix = uniqid('', true), 10000, 7000);
        $followup = new \ITILFollowup();
        $followup_id = $followup->add([
            'itemtype'     => $ticket->getType(),
            'items_id'     => $ticket->getID(),
            'content'     => <<<HTML
<p>Test followup with image</p>
<p><img id="bbbbbbbb-bbbbbbbb-bbbbbbbbbbbbbb.00000000" src="data:image/png;base64,bbb=" /></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                'bbbbbbbb-bbbbbbbb-bbbbbbbbbbbbbb.00000000',
            ],
            '_prefix_filename' => [
                $prefix,
            ]
        ]);
        $this->integer($followup_id)->isGreaterThan(0);
        $followup_img = new \Document();
        $this->boolean($followup_img->getFromDBByCrit(['tag' => 'bbbbbbbb-bbbbbbbb-bbbbbbbbbbbbbb.00000000']))->isTrue();

        $followup_doc = $this->createTxtDocument();
        $this->createItem(
            \Document_Item::class,
            [
                'documents_id' => $followup_doc->getID(),
                'itemtype'     => $followup->getType(),
                'items_id'     => $followup->getID(),
            ]
        );

        // Create a task and attach documents to it
        $filename = $this->createUploadedImage($prefix = uniqid('', true));
        $task = new \TicketTask();
        $task_id = $task->add([
            'tickets_id'  => $ticket->getID(),
            'content'     => <<<HTML
<p>Test task with image</p>
<p><img id="cccccccc-cccccccc-cccccccccccccc.00000000" src="data:image/png;base64,ccc=" /></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                'cccccccc-cccccccc-cccccccccccccc.00000000',
            ],
            '_prefix_filename' => [
                $prefix,
            ]
        ]);
        $this->integer($task_id)->isGreaterThan(0);
        $task_img = new \Document();
        $this->boolean($task_img->getFromDBByCrit(['tag' => 'cccccccc-cccccccc-cccccccccccccc.00000000']))->isTrue();

        $task_doc = $this->createTxtDocument();
        $this->createItem(
            \Document_Item::class,
            [
                'documents_id' => $task_doc->getID(),
                'itemtype'     => $task->getType(),
                'items_id'     => $task->getID(),
            ]
        );

        foreach ([true, false] as $send_html) {
            $ticket_attachments   = [$ticket_doc];
            $followup_attachments = [$followup_doc];
            $task_attachments     = [$task_doc];
            if ($send_html === false) {
                $ticket_attachments[]   = $ticket_img;
                $followup_attachments[] = $followup_img;
                $task_attachments[]     = $task_img;
            }

            // No documents, inherited from global config
            yield [
                'global_config'        => \NotificationSetting::ATTACH_NO_DOCUMENT,
                'notif_config'         => \NotificationSetting::ATTACH_INHERIT,
                'notification'         => $update_ticket_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $ticket,
                'expected_attachments' => [],
            ];
            yield [
                'global_config'        => \NotificationSetting::ATTACH_NO_DOCUMENT,
                'notif_config'         => \NotificationSetting::ATTACH_INHERIT,
                'notification'         => $update_followup_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $followup,
                'expected_attachments' => [],
            ];
            yield [
                'global_config'        => \NotificationSetting::ATTACH_NO_DOCUMENT,
                'notif_config'         => \NotificationSetting::ATTACH_INHERIT,
                'notification'         => $update_task_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $task,
                'expected_attachments' => [],
            ];

            // All documents, inherited from global config
            yield [
                'global_config'        => \NotificationSetting::ATTACH_ALL_DOCUMENTS,
                'notif_config'         => \NotificationSetting::ATTACH_INHERIT,
                'notification'         => $update_ticket_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $ticket,
                'expected_attachments' => array_merge($ticket_attachments, $followup_attachments, $task_attachments),
            ];
            yield [
                'global_config'        => \NotificationSetting::ATTACH_ALL_DOCUMENTS,
                'notif_config'         => \NotificationSetting::ATTACH_INHERIT,
                'notification'         => $update_followup_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $followup,
                'expected_attachments' => array_merge($ticket_attachments, $followup_attachments, $task_attachments),
            ];
            yield [
                'global_config'      => \NotificationSetting::ATTACH_ALL_DOCUMENTS,
                'notif_config'       => \NotificationSetting::ATTACH_INHERIT,
                'notification'       => $update_task_notif,
                'send_html'          => $send_html,
                'item_to_update'     => $task,
                'expected_attachments' => array_merge($ticket_attachments, $followup_attachments, $task_attachments),
            ];

            // Trigger documents only, inherited from global config
            yield [
                'global_config'        => \NotificationSetting::ATTACH_FROM_TRIGGER_ONLY,
                'notif_config'         => \NotificationSetting::ATTACH_INHERIT,
                'notification'         => $update_ticket_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $ticket,
                'expected_attachments' => $ticket_attachments,
            ];
            yield [
                'global_config'        => \NotificationSetting::ATTACH_FROM_TRIGGER_ONLY,
                'notif_config'         => \NotificationSetting::ATTACH_INHERIT,
                'notification'         => $update_followup_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $followup,
                'expected_attachments' => $followup_attachments,
            ];
            yield [
                'global_config'       => \NotificationSetting::ATTACH_FROM_TRIGGER_ONLY,
                'notif_config'        => \NotificationSetting::ATTACH_INHERIT,
                'notification'        => $update_task_notif,
                'send_html'           => $send_html,
                'item_to_update'      => $task,
                'expected_attachments' => $task_attachments,
            ];

            // No documents, defined by notification config
            yield [
                'global_config'        => \NotificationSetting::ATTACH_ALL_DOCUMENTS, // will be overriden
                'notif_config'         => \NotificationSetting::ATTACH_NO_DOCUMENT,
                'notification'         => $update_ticket_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $ticket,
                'expected_attachments' => [],
            ];
            yield [
                'global_config'       => \NotificationSetting::ATTACH_ALL_DOCUMENTS, // will be overriden
                'notif_config'        => \NotificationSetting::ATTACH_NO_DOCUMENT,
                'notification'        => $update_followup_notif,
                'send_html'           => $send_html,
                'item_to_update'      => $followup,
                'expected_attachments' => [],
            ];
            yield [
                'global_config'        => \NotificationSetting::ATTACH_ALL_DOCUMENTS, // will be overriden
                'notif_config'         => \NotificationSetting::ATTACH_NO_DOCUMENT,
                'notification'         => $update_task_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $task,
                'expected_attachments' => [],
            ];

            // All documents, defined by notification configig
            yield [
                'global_config'        => \NotificationSetting::ATTACH_NO_DOCUMENT, // will be overriden
                'notif_config'         => \NotificationSetting::ATTACH_ALL_DOCUMENTS,
                'notification'         => $update_ticket_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $ticket,
                'expected_attachments' => array_merge($ticket_attachments, $followup_attachments, $task_attachments),
            ];
            yield [
                'global_config'        => \NotificationSetting::ATTACH_NO_DOCUMENT, // will be overriden
                'notif_config'         => \NotificationSetting::ATTACH_ALL_DOCUMENTS,
                'notification'         => $update_followup_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $followup,
                'expected_attachments' => array_merge($ticket_attachments, $followup_attachments, $task_attachments),
            ];
            yield [
                'global_config'        => \NotificationSetting::ATTACH_NO_DOCUMENT, // will be overriden
                'notif_config'         => \NotificationSetting::ATTACH_ALL_DOCUMENTS,
                'notification'         => $update_task_notif,
                'send_html'            => $send_html,
                'item_to_update'       => $task,
                'expected_attachments' => array_merge($ticket_attachments, $followup_attachments, $task_attachments),
            ];
        }
    }

    /**
     * @dataProvider attachedDocumentsProvider
     */
    public function testAttachedDocuments(
        int $global_config,
        int $notif_config,
        \Notification $notification,
        bool $send_html,
        \CommonDBTM $item_to_update,
        array $expected_attachments,
    ): void {
        global $CFG_GLPI, $DB;

        $this->login();

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

        // Ensure only tested notification is active
        $deactivated = $DB->update(
            \Notification::getTable(),
            ['is_active' => false],
            ['id' => ['<>', $notification->getID()]]
        );
        $this->boolean($deactivated)->isTrue();
        $this->boolean($notification->update(['id' => $notification->getID(), 'is_active' => 1]))->isTrue();

        // Update global/notification configuration
        $CFG_GLPI['attach_ticket_documents_to_mail'] = $global_config;
        $this->boolean($notification->update(['id' => $notification->getID(), 'attach_documents' => $notif_config]))->isTrue();

        // Adapt notification template to send expected content format (HTML or plain text)
        $notification_notificationtemplate_it = $DB->request([
            'FROM'  => 'glpi_notifications_notificationtemplates',
            'WHERE' => ['notifications_id' => $notification->getID()],
        ]);
        foreach ($notification_notificationtemplate_it as $notification_notificationtemplate_data) {
            $notificationtemplate_it = $DB->request([
                'FROM'  => 'glpi_notificationtemplates',
                'WHERE' => ['id' => $notification_notificationtemplate_data['notificationtemplates_id']],
            ]);
            foreach ($notificationtemplate_it as $notificationtemplate_data) {
                $template_updated = $DB->update(
                    'glpi_notificationtemplatetranslations',
                    ['content_html' => $send_html ? '<p>HTML</p>' : null],
                    ['notificationtemplates_id' => $notificationtemplate_data['id']]
                );
                $this->boolean($template_updated)->isTrue();
            }
        }

        // Ensure that there is no notification queued
        $this->integer(countElementsInTable(QueuedNotification::getTable(), ['is_deleted' => 0]))->isEqualTo(0);

        // Update item
        $updated = $item_to_update->update([
            'id' => $item_to_update->getID(),
            'content' => $item_to_update->fields['content'] . '<p>updated</p>',
        ]);
        $this->boolean($updated)->isTrue();

        // Check documents attached to notification
        $queued_notifications = getAllDataFromTable(QueuedNotification::getTable(), ['is_deleted' => 0]);
        $this->array($queued_notifications)->hasSize(1);

        \NotificationEventMailing::setMailer(new \GLPIMailer($transport));
        \NotificationEventMailing::send($queued_notifications);
        \NotificationEventMailing::setMailer(null);

        $this->integer(memory_get_usage())->isLessThan(50000000);           // less than 50 Mb
        $this->integer(memory_get_usage(true))->isLessThan(50000000);       // less than 50 Mb
        $this->integer(memory_get_peak_usage())->isLessThan(55000000);      // less than 55 Mb

        $attachments = $transport->sent_email->getAttachments();
        $this->array($attachments)->hasSize(count($expected_attachments));

        $attachement_filenames = [];
        foreach ($attachments as $attachment) {
            $this->object($attachment)->isInstanceOf(\Symfony\Component\Mime\Part\DataPart::class);
            $attachement_filenames[] = $attachment->getFilename();
        }
        sort($attachement_filenames);

        $expected_filenames    = [];
        foreach ($expected_attachments as $document) {
            $expected_filenames[] = $document->fields['filename'];
        }
        sort($expected_filenames);

        $this->array($attachement_filenames)->isEqualTo($expected_filenames);
    }

    private function createTxtDocument(int $bytes = null): \Document
    {
        $entity   = getItemByTypeName('Entity', '_test_root_entity', true);
        $filename = uniqid('glpitest_', true) . '.txt';

        if (is_null($bytes)) {
            $contents = random_bytes(1024);
        } else {
            $contents = random_bytes($bytes);
        }

        $written_bytes = file_put_contents(GLPI_TMP_DIR . '/' . $filename, $contents);
        $this->integer($written_bytes)->isEqualTo(strlen($contents));

        return $this->createItem(
            \Document::class,
            [
                'filename'    => $filename,
                'entities_id' => $entity,
                '_filename'   => [
                    $filename,
                ],
            ]
        );
    }

    /**
     * Simulates upload of a random PNG image and return its filename.
     */
    private function createUploadedImage(string $prefix, int $width = 100, int $height = 100): string
    {
        $filename = $prefix . uniqid('glpitest_', true) . '.png';

        $image = imagecreate($width, $height);
        $this->object($image)->isInstanceOf(\GdImage::class);
        $this->integer(imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255)));
        $this->boolean(imagepng($image, GLPI_TMP_DIR . '/' . $filename))->isTrue();

        return $filename;
    }
}
