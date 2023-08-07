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

        $update_ticket_notif   = getItemByTypeName('Notification', 'Update Ticket');
        $update_followup_notif = getItemByTypeName('Notification', 'Update Followup');

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

        $document_1 = $this->createTxtDocument();
        $this->createItem(
            \Document_Item::class,
            [
                'documents_id' => $document_1->getID(),
                'itemtype'     => $ticket->getType(),
                'items_id'     => $ticket->getID(),
            ]
        );

        // Create a followup and attach documents to it
        $filename = $this->createUploadedImage($prefix = uniqid('', true));
        $followup = new \ITILFollowup();
        $followup_id = $followup->add([
            'itemtype'     => $ticket->getType(),
            'items_id'     => $ticket->getID(),
            'content'     => <<<HTML
<p>Test ticket with image</p>
<p><img id="aaaaaaaa-aaaaaaaa-aaaaaaaaaaaaaa.00000000" src="data:image/png;base64,aaa=" /></p>
HTML,
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
        $this->integer($followup_id)->isGreaterThan(0);

        $document_2 = $this->createTxtDocument();
        $this->createItem(
            \Document_Item::class,
            [
                'documents_id' => $document_2->getID(),
                'itemtype'     => $followup->getType(),
                'items_id'     => $followup->getID(),
            ]
        );

        // No documents, inherited from global config
        yield [
            'global_config'      => \NotificationSetting::ATTACH_NO_DOCUMENT,
            'notif_config'       => \NotificationSetting::ATTACH_INHERIT,
            'item_to_update'     => $ticket,
            'notification'       => $update_ticket_notif,
            'expected_documents' => [
            ],
        ];

        // All documents, inherited from global config
        yield [
            'global_config'      => \NotificationSetting::ATTACH_ALL_DOCUMENTS,
            'notif_config'       => \NotificationSetting::ATTACH_INHERIT,
            'item_to_update'     => $ticket,
            'notification'       => $update_ticket_notif,
            'expected_documents' => [
                $document_1,
                $document_2,
            ],
        ];

        // Trigger documents only, inherited from global config
        yield [
            'global_config'      => \NotificationSetting::ATTACH_FROM_TRIGGER_ONLY,
            'notif_config'       => \NotificationSetting::ATTACH_INHERIT,
            'item_to_update'     => $ticket,
            'notification'       => $update_ticket_notif,
            'expected_documents' => [
                $document_1,
            ],
        ];
    }

    /**
     * @dataProvider attachedDocumentsProvider
     */
    public function testAttachedDocuments(
        int $global_config,
        int $notif_config,
        \Notification $notification,
        \CommonDBTM $item_to_update,
        array $expected_documents,
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
        \NotificationEventMailing::setMailer(new \GLPIMailer($transport));

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

        \NotificationEventMailing::send($queued_notifications);

        $attachments = $transport->sent_email->getAttachments();
        $this->array($attachments)->hasSize(count($expected_documents));
        foreach ($expected_documents as $document) {
            $attachment = array_shift($attachments);
            $this->object($attachment)->isInstanceOf(\Symfony\Component\Mime\Part\DataPart::class);
            $this->string($attachment->getFilename())->isEqualTo($document->fields['filename']);
        }
    }

    private function createTxtDocument(): \Document
    {
        $entity   = getItemByTypeName('Entity', '_test_root_entity', true);
        $filename = uniqid('glpitest_', true) . '.txt';
        $contents = random_bytes(1024);

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
    private function createUploadedImage(string $prefix): string
    {
        $filename = $prefix. uniqid('glpitest_', true) . '.png';

        $image = imagecreate(100, 100);
        $this->object($image)->isInstanceOf(\GdImage::class);
        $this->integer(imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255)));
        $this->boolean(imagepng($image, GLPI_TMP_DIR . '/' . $filename))->isTrue();

        return $filename;
    }
}
