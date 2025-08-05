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

use Contract;
use DbTestCase;
use Glpi\DBAL\QueryExpression;
use Group;
use Group_User;
use NotificationEvent;
use NotificationTarget;
use QueuedNotification;
use Symfony\Component\Mailer\DelayedEnvelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Part\DataPart;
use User;

/* Test for inc/notification.class.php */

class NotificationTest extends DbTestCase
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

        $this->assertEquals("global_signature", \Notification::getMailingSignature($parent));
        $this->assertEquals("global_signature", \Notification::getMailingSignature($child_1));
        $this->assertEquals("global_signature", \Notification::getMailingSignature($child_2));

        $entity = new \Entity();
        $this->assertTrue($entity->update([
            'id'                => $root,
            'mailing_signature' => "signature_root",
        ]));

        $this->assertEquals("signature_root", \Notification::getMailingSignature($parent));
        $this->assertEquals("signature_root", \Notification::getMailingSignature($child_1));
        $this->assertEquals("signature_root", \Notification::getMailingSignature($child_2));

        $this->assertTrue($entity->update([
            'id'                => $parent,
            'mailing_signature' => "signature_parent",
        ]));

        $this->assertEquals("signature_parent", \Notification::getMailingSignature($parent));
        $this->assertEquals("signature_parent", \Notification::getMailingSignature($child_1));
        $this->assertEquals("signature_parent", \Notification::getMailingSignature($child_2));

        $this->assertTrue($entity->update([
            'id'                => $child_1,
            'mailing_signature' => "signature_child_1",
        ]));

        $this->assertEquals("signature_parent", \Notification::getMailingSignature($parent));
        $this->assertEquals("signature_child_1", \Notification::getMailingSignature($child_1));
        $this->assertEquals("signature_parent", \Notification::getMailingSignature($child_2));

        $this->assertTrue($entity->update([
            'id'                => $child_2,
            'mailing_signature' => "signature_child_2",
        ]));

        $this->assertEquals("signature_parent", \Notification::getMailingSignature($parent));
        $this->assertEquals("signature_child_1", \Notification::getMailingSignature($child_1));
        $this->assertEquals("signature_child_2", \Notification::getMailingSignature($child_2));
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
        $this->assertEquals(0, countElementsInTable(QueuedNotification::getTable()));

        // Enable notifications
        $CFG_GLPI['use_notifications'] = true;
        $CFG_GLPI['notifications_mailing'] = true;

        // Activate only new followup notification
        $success = $DB->update(\Notification::getTable(), ['is_active' => false], [
            'name' => ['<>', $target_notification],
        ]);
        $this->assertTrue($success);
        $this->assertEquals(
            1,
            countElementsInTable(
                \Notification::getTable(),
                ['is_active' => true]
            )
        );

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
            ],
        ]);
        $this->assertTrue($success);

        // Create tickets
        $this->createItem("Ticket", [
            "name"              => "Test",
            "content"           => "Test",
            "itilcategories_id" => $cat_A->fields['id'],
            "entities_id"       => $entity,
        ]);
        $this->assertEquals(0, countElementsInTable(QueuedNotification::getTable()));

        $this->createItem("Ticket", [
            "name"              => "Test",
            "content"           => "Test",
            "itilcategories_id" => $cat_B->fields['id'],
            "entities_id"       => $entity,
        ]);
        $this->assertEquals(1, countElementsInTable(QueuedNotification::getTable()));
    }

    protected function attachedDocumentsProvider(): iterable
    {
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        $update_ticket_notif   = new \Notification();
        $this->assertTrue($update_ticket_notif->getFromDBByCrit(['itemtype' => \Ticket::class, 'event' => 'update']));
        $update_followup_notif = new \Notification();
        $this->assertTrue($update_followup_notif->getFromDBByCrit(['itemtype' => \Ticket::class, 'event' => 'update_followup']));
        $update_task_notif = new \Notification();
        $this->assertTrue($update_task_notif->getFromDBByCrit(['itemtype' => \Ticket::class, 'event' => 'update_task']));

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
            ],
        ]);
        $this->assertGreaterThan(0, $ticket_id);
        $ticket_img = new \Document();
        $this->assertTrue($ticket_img->getFromDBByCrit(['tag' => 'aaaaaaaa-aaaaaaaa-aaaaaaaaaaaaaa.00000000']));

        $ticket_doc = $this->createTxtDocument();
        $this->createItem(
            \Document_Item::class,
            [
                'documents_id' => $ticket_doc->getID(),
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
            ],
        ]);
        $this->assertGreaterThan(0, $followup_id);
        $followup_img = new \Document();
        $this->assertTrue($followup_img->getFromDBByCrit(['tag' => 'bbbbbbbb-bbbbbbbb-bbbbbbbbbbbbbb.00000000']));

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
            ],
        ]);
        $this->assertGreaterThan(0, $task_id);
        $task_img = new \Document();
        $this->assertTrue($task_img->getFromDBByCrit(['tag' => 'cccccccc-cccccccc-cccccccccccccc.00000000']));

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

    public function testAttachedDocuments(): void
    {
        global $CFG_GLPI, $DB;

        $this->login();

        // Mock mailer transport
        $transport = new class extends AbstractTransport {
            public $sent_email;

            protected function doSend(SentMessage $message): void
            {
                // Extract message from envelope
                $envelope_reflection = new \ReflectionClass(DelayedEnvelope::class);
                /* @var \Symfony\Component\Mime\Email $email */
                $this->sent_email = $envelope_reflection->getProperty('message')->getValue($message->getEnvelope());
            }

            public function __toString(): string
            {
                return 'test://';
            }
        };

        $provider = $this->attachedDocumentsProvider();
        foreach ($provider as $row) {
            $global_config = $row['global_config'];
            $notif_config = $row['notif_config'];
            $notification = $row['notification'];
            $send_html = $row['send_html'];
            $item_to_update = $row['item_to_update'];
            $expected_attachments = $row['expected_attachments'];

            // Enable notifications
            $CFG_GLPI['use_notifications'] = $CFG_GLPI['notifications_mailing'] = true;

            // Ensure only tested notification is active
            $deactivated = $DB->update(
                \Notification::getTable(),
                ['is_active' => false],
                ['id' => ['<>', $notification->getID()]]
            );
            $this->assertTrue($deactivated);
            $this->assertTrue($notification->update(['id' => $notification->getID(), 'is_active' => 1]));

            // Update global/notification configuration
            $CFG_GLPI['attach_ticket_documents_to_mail'] = $global_config;
            $this->assertTrue($notification->update(['id' => $notification->getID(), 'attach_documents' => $notif_config]));

            // Adapt notification template to send expected content format (HTML or plain text)
            $notification_notificationtemplate_it = $DB->request([
                'FROM' => 'glpi_notifications_notificationtemplates',
                'WHERE' => ['notifications_id' => $notification->getID()],
            ]);
            foreach ($notification_notificationtemplate_it as $notification_notificationtemplate_data) {
                $notificationtemplate_it = $DB->request([
                    'FROM' => 'glpi_notificationtemplates',
                    'WHERE' => ['id' => $notification_notificationtemplate_data['notificationtemplates_id']],
                ]);
                foreach ($notificationtemplate_it as $notificationtemplate_data) {
                    $template_updated = $DB->update(
                        'glpi_notificationtemplatetranslations',
                        ['content_html' => $send_html ? '<p>HTML</p>' : null],
                        ['notificationtemplates_id' => $notificationtemplate_data['id']]
                    );
                    $this->assertTrue($template_updated);
                }
            }

            // Ensure that there is no notification queued
            $this->assertEmpty(0, countElementsInTable(QueuedNotification::getTable(), ['is_deleted' => 0]));

            // Update item
            $updated = $item_to_update->update([
                'id' => $item_to_update->getID(),
                'content' => $item_to_update->fields['content'] . '<p>updated</p>',
            ]);
            $this->assertTrue($updated);

            // Check documents attached to notification
            $queued_notifications = getAllDataFromTable(QueuedNotification::getTable(), ['is_deleted' => 0]);
            $this->assertCount(1, $queued_notifications);

            \NotificationEventMailing::setMailer(new \GLPIMailer($transport));
            \NotificationEventMailing::send($queued_notifications);
            \NotificationEventMailing::setMailer(null);

            $attachments = $transport->sent_email->getAttachments();
            $this->assertCount(count($expected_attachments), $attachments);

            $attachement_filenames = [];
            foreach ($attachments as $attachment) {
                $this->assertInstanceOf(DataPart::class, $attachment);
                $attachement_filenames[] = $attachment->getFilename();
            }
            sort($attachement_filenames);

            $expected_filenames = [];
            foreach ($expected_attachments as $document) {
                $expected_filenames[] = $document->fields['filename'];
            }
            sort($expected_filenames);

            $this->assertEquals($expected_filenames, $attachement_filenames);
        }
    }

    /**
     * Simulates upload of a random PNG image and return its filename.
     */
    private function createUploadedImage(string $prefix): string
    {
        $filename = $prefix . uniqid('glpitest_', true) . '.png';

        $image = imagecreate(100, 100);
        $this->assertInstanceOf(\GdImage::class, $image);
        $this->assertIsInt(imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255)));
        $this->assertTrue(imagepng($image, GLPI_TMP_DIR . '/' . $filename));

        return $filename;
    }

    /**
     * Data provider for the testEntityRestriction case
     *
     * @return iterable
     */
    protected function testEntityRestrictionProvider(): iterable
    {
        global $DB, $CFG_GLPI;

        // Test users
        [$user_root, $user_sub] = $this->createItems(User::class, [
            [
                'name'         => "User_root_entity",
                '_useremails'  => [-1 => "user_root@teclib.com"],
                '_entities_id' => $this->getTestRootEntity(true),
                '_profiles_id' => 4,                                // Super admin
            ],
            [
                'name'         => "User_sub_entity",
                '_useremails'  => [-1 => "user_sub@teclib.com"],
                '_entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
                '_profiles_id' => 4, // Super admin
            ],
        ]);

        // Put all our tests user into a single group so its easy to add them as
        // recipient of the test notification
        $group = $this->createItem(Group::class, [
            'name'        => "testEntityRestriction_group",
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => true,
        ]);
        $this->createItems(Group_User::class, [
            [
                'users_id'  => $user_root->getID(),
                'groups_id' => $group->getID(),
            ],
            [
                'users_id'  => $user_sub->getID(),
                'groups_id' => $group->getID(),
            ],
        ]);

        // Set up notifications
        $DB->update(\Notification::getTable(), ['is_active' => 0], [new QueryExpression('true')]);
        $active_notification = countElementsInTable(\Notification::getTable(), ['is_active' => 1]);
        $this->assertEquals(0, $active_notification);

        // Enable notification
        $CFG_GLPI['notifications_mailing'] = true;
        $CFG_GLPI['use_notifications'] = true;

        // Find the "Contract end" notification and enable it
        $notification = getItemByTypeName(\Notification::class, "Contract End");
        $this->updateItem(\Notification::class, $notification->getID(), ['is_active' => 1]);

        // Clear any exisiting target then set our group target
        $DB->delete(NotificationTarget::getTable(), ['notifications_id' => $notification->getID()]);
        $this->createItem(NotificationTarget::class, [
            'notifications_id' => $notification->getID(),
            'items_id'         => $group->getID(),
            'type'             => \Notification::GROUP_TYPE,
        ]);

        // First test case: contract in the root entity with no recursion
        // It should only be visible for the first user
        $contract_root = $this->createItem('Contract', [
            'name'         => 'Contact',
            'entities_id'  => $this->getTestRootEntity(true),
            'is_recursive' => false,
        ]);
        yield [$contract_root, ["user_root@teclib.com"]];

        // Second test case: contract in the root entity with recursion
        // It should be visible for our two users
        $contract_root_and_children = $this->createItem('Contract', [
            'name'         => 'Contact',
            'entities_id'  => $this->getTestRootEntity(true),
            'is_recursive' => true,
        ]);
        yield [$contract_root_and_children, ["user_root@teclib.com", "user_sub@teclib.com"]];
    }

    /**
     * Test that entity restriction are applied correctly for notifications (a
     * user should only receive notification on items he is allowed to see)
     *
     * @return void
     */
    public function testEntityRestriction(): void
    {
        global $DB;
        $this->login();

        $provider = $this->testEntityRestrictionProvider();
        foreach ($provider as $row) {
            [$contract, $expected_queue] = $row;

            // Clear notification queue
            $DB->delete(QueuedNotification::getTable(), [new QueryExpression('true')]);
            $queue_size = countElementsInTable(QueuedNotification::getTable());
            $this->assertEquals(0, $queue_size);

            // Raise fake notification
            NotificationEvent::raiseEvent('end', $contract, [
                'entities_id' => $this->getTestRootEntity(true),
                'items' => [
                    [
                        'id' => $contract->getID(),
                        'name' => $contract->fields['name'],
                        'num' => $contract->fields['num'],
                        'comment' => $contract->fields['comment'],
                        'accounting_number' => $contract->fields['accounting_number'],
                        'contracttypes_id' => $contract->fields['contracttypes_id'],
                        'states_id' => $contract->fields['states_id'],
                        'begin_date' => $contract->fields['begin_date'],
                        'duration' => $contract->fields['duration'],
                    ],
                ],
            ]);

            // Validate notification queue size
            $queue = (new QueuedNotification())->find();
            $emails = array_column($queue, 'recipient');
            sort($emails);
            sort($expected_queue);
            $this->assertEquals($expected_queue, $emails);
        }
    }
}
