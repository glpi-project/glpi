<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Document_Item;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Psr\Log\LogLevel;
use Toolbox;

class Document_ItemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDocumentsCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['itemdevices_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Document_Item$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDocumentsCapacity::class)]);

        foreach ($CFG_GLPI['itemdevices_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Document_Item::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testGetForbiddenStandardMassiveAction()
    {
        $ditem = new Document_Item();
        $this->assertSame(
            ['clone', 'update'],
            $ditem->getForbiddenStandardMassiveAction()
        );
    }

    public function testPrepareInputForAdd()
    {
        $input = [];
        $ditem = new Document_Item();

        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item type is mandatory',
            LogLevel::WARNING
        );

        $input['itemtype'] = '';
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item type is mandatory',
            LogLevel::WARNING
        );

        $input['itemtype'] = 'NotAClass';
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'No class found for type NotAClass',
            LogLevel::WARNING
        );

        $input['itemtype'] = 'Computer';
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item ID is mandatory',
            LogLevel::WARNING
        );

        $input['items_id'] = 0;
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item ID is mandatory',
            LogLevel::WARNING
        );

        $cid = getItemByTypeName('Computer', '_test_pc01', true);
        $input['items_id'] = $cid;

        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Document ID is mandatory',
            LogLevel::WARNING
        );

        $input['documents_id'] = 0;
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Document ID is mandatory',
            LogLevel::WARNING
        );

        $document = new \Document();
        $this->assertGreaterThan(
            0,
            $document->add([
                'name'   => 'Test document to link',
            ])
        );
        $input['documents_id'] = $document->getID();

        $expected = [
            'itemtype'     => 'Computer',
            'items_id'     => $cid,
            'documents_id' => $document->getID(),
            'users_id'     => false,
            'entities_id'  => 0,
            'is_recursive' => 0,
        ];

        $this->assertSame(
            $expected,
            $ditem->prepareInputForAdd($input)
        );
    }


    public function testGetDistinctTypesParams()
    {
        $expected = [
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_documents_items',
            'WHERE'           => [
                'OR'  => [
                    'glpi_documents_items.documents_id'  => 1,
                    [
                        'glpi_documents_items.itemtype'  => 'Document',
                        'glpi_documents_items.items_id'  => 1,
                    ],
                ],
            ],
            'ORDER'           => 'itemtype',
        ];
        $this->assertSame($expected, Document_Item::getDistinctTypesParams(1));

        $extra_where = ['date_mod' => ['>', '2000-01-01']];
        $expected = [
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_documents_items',
            'WHERE'           => [
                'OR'  => [
                    'glpi_documents_items.documents_id'  => 1,
                    [
                        'glpi_documents_items.itemtype'  => 'Document',
                        'glpi_documents_items.items_id'  => 1,
                    ],
                ],
                [
                    'date_mod'  => [
                        '>',
                        '2000-01-01',
                    ],
                ],
            ],
            'ORDER'           => 'itemtype',
        ];
        $this->assertSame($expected, Document_Item::getDistinctTypesParams(1, $extra_where));
    }


    public function testPostAddItem()
    {
        $uid = getItemByTypeName('User', TU_USER, true);

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => '',
            'content' => 'Test modification date not updated from Document_Item',
            'date_mod' => '2020-01-01',
        ]);

        $this->assertGreaterThan(0, $tickets_id);

        // Document and Document_Item
        $doc = new \Document();
        $this->assertGreaterThan(
            0,
            $doc->add([
                'users_id'     => $uid,
                'tickets_id'   => $tickets_id,
                'name'         => 'A simple document object',
            ])
        );

        //do not change ticket modification date
        $doc_item = new Document_Item();
        $this->assertGreaterThan(
            0,
            $doc_item->add([
                'users_id'      => $uid,
                'items_id'      => $tickets_id,
                'itemtype'      => 'Ticket',
                'documents_id'  => $doc->getID(),
                '_do_update_ticket' => false,
            ])
        );

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertSame('2020-01-01 00:00:00', $ticket->fields['date_mod']);

        //do change ticket modification date
        $_SESSION["glpi_currenttime"] = '2021-01-01 00:00:01';
        $doc = new \Document();
        $this->assertGreaterThan(
            0,
            $doc->add([
                'users_id'     => $uid,
                'tickets_id'   => $tickets_id,
                'name'         => 'A simple document object',
            ])
        );

        $doc_item = new Document_Item();
        $this->assertGreaterThan(
            0,
            $doc_item->add([
                'users_id'      => $uid,
                'items_id'      => $tickets_id,
                'itemtype'      => 'Ticket',
                'documents_id'  => $doc->getID(),
            ])
        );

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertNotEquals(
            '2021-01-01 00:00:01',
            $ticket->fields['date_mod']
        );
    }

    /**
     * Test that private documents are hidden from users without SEEPRIVATE right.
     */
    public function testPrivateDocumentVisibilityWithoutSeeprivateRight()
    {
        $this->login();

        // Create a ticket
        $tickets_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test private document visibility',
                'content' => 'Test content',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        // Create a document
        $doc_id = $this->createItem(
            \Document::class,
            [
                'name' => 'Private test document',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        // Attach document to ticket as private
        $doc_item = $this->createItem(
            Document_Item::class,
            [
                'items_id'      => $tickets_id,
                'itemtype'      => \Ticket::class,
                'documents_id'  => $doc_id,
                'is_private'    => 1,
                'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
            ]
        );
        $doc_item_id = $doc_item->getID();

        // Verify the document is created with is_private = 1
        $this->assertTrue($doc_item->getFromDB($doc_item_id));
        $this->assertEquals(1, $doc_item->fields['is_private']);

        // Create a user without SEEPRIVATE right
        $user = new \User();
        $user_id = $user->add([
            'name' => 'user_no_seeprivate',
            'password' => 'test_password',
            'password2' => 'test_password',
        ]);
        $this->assertGreaterThan(0, $user_id);

        // Create a read-only profile without SEEPRIVATE
        $profile_id = $this->createItem(
            \Profile::class,
            [
                'name' => 'Test profile no SEEPRIVATE',
            ]
        )->getID();

        // Assign profile to user
        $this->createItem(
            \Profile_User::class,
            [
                'users_id' => $user_id,
                'profiles_id' => $profile_id,
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        // Login as the user without SEEPRIVATE right
        $this->login('user_no_seeprivate', 'test_password', false);

        // Check that the private document is not visible
        $doc_item_check = new Document_Item();
        $doc_item_check->getFromDB($doc_item_id);
        $this->assertFalse($doc_item_check->canViewItem());

        // Clean up and restore session
        $this->login();
    }

    /**
     * Test that private documents are visible to users with SEEPRIVATE right.
     */
    public function testPrivateDocumentVisibilityWithSeeprivateRight()
    {
        $this->login(); // Login as super-admin who should have SEEPRIVATE

        // Create a ticket
        $tickets_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test private document visibility with SEEPRIVATE',
                'content' => 'Test content',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        // Create a document
        $doc_id = $this->createItem(
            \Document::class,
            [
                'name' => 'Private test document',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        // Attach document to ticket as private
        $doc_item = $this->createItem(
            Document_Item::class,
            [
                'items_id' => $tickets_id,
                'itemtype' => \Ticket::class,
                'documents_id' => $doc_id,
                'is_private' => 1,
                'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
            ]
        );
        $doc_item_id = $doc_item->getID();

        // Check that the private document is visible
        $doc_item->getFromDB($doc_item_id);
        $this->assertTrue($doc_item->canViewItem());
    }

    /**
     * Test that public documents are visible to all authorized users.
     */
    public function testPublicDocumentVisibility()
    {
        $this->login();

        // Create a ticket
        $ticket = $this->createItem('Ticket', [
            'name' => 'Test public document visibility',
            'content' => 'Test content',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $tickets_id = $ticket->getID();

        // Create a document
        $doc_id = $this->createItem('Document', [
            'name' => 'Public test document',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        // Attach document to ticket as public (is_private = 0)
        $doc_item = $this->createItem('Document_Item', [
            'items_id'      => $tickets_id,
            'itemtype'      => 'Ticket',
            'documents_id'  => $doc_id,
            'is_private'    => 0,
            'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
        ]);
        $doc_item_id = $doc_item->getID();

        // Check that the public document is visible
        $doc_item->getFromDB($doc_item_id);
        $this->assertTrue($doc_item->canViewItem());
    }

    /**
     * Test that documents are public by default (is_private = 0).
     */
    public function testDefaultIsPrivateValue()
    {
        $this->login();

        // Create a ticket
        $tickets_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test default is_private',
                'content' => 'Test content',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        // Create a document
        $doc_id = $this->createItem(
            \Document::class,
            [
                'name' => 'Test document default is_private',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        // Attach document to ticket without specifying is_private
        $doc_item = $this->createItem(
            Document_Item::class,
            [
                'items_id'      => $tickets_id,
                'itemtype'      => \Ticket::class,
                'documents_id'  => $doc_id,
                'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
            ]
        );
        $doc_item_id = $doc_item->getID();

        // Verify is_private defaults to 0
        $doc_item->getFromDB($doc_item_id);
        $this->assertEquals(0, $doc_item->fields['is_private']);
    }

    /**
     * Test that document owner can always see their own private documents.
     */
    public function testPrivateDocumentVisibleToOwner()
    {
        // Create a user
        $user = new \User();
        $user_id = $user->add([
            'name' => 'doc_owner',
            'password' => 'test_password',
            'password2' => 'test_password',
        ]);
        $this->assertGreaterThan(0, $user_id);

        // Create profile with document READ but not SEEPRIVATE
        $profile_id = $this->createItem(
            \Profile::class,
            [
                'name' => 'Test doc owner profile',
            ]
        )->getID();

        $this->addRightToProfile(
            'Test doc owner profile',
            \Ticket::$rightname,
            \Ticket::READASSIGN
        );

        // Assign profile to user
        $this->createItem(
            \Profile_User::class,
            [
                'users_id' => $user_id,
                'profiles_id' => $profile_id,
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        // Login as doc owner user
        $this->login('doc_owner', 'test_password');

        \Session::changeProfile($profile_id);
        \Session::changeActiveEntities([$this->getTestRootEntity(true)]);

        // Create a ticket
        $tickets_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test owner private document',
                'content' => 'Test content',
                'entities_id' => $this->getTestRootEntity(true),
                '_users_id_assign' => $user_id,
            ]
        )->getID();

        // Create a document
        $doc_id = $this->createItem(
            \Document::class,
            [
                'name' => 'Owner private document',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        // Attach document as private with current user as owner
        $doc_item = $this->createItem(
            Document_Item::class,
            [
                'items_id'      => $tickets_id,
                'itemtype'      => \Ticket::class,
                'documents_id'  => $doc_id,
                'is_private'    => 1,
                'users_id'      => \Session::getLoginUserID(),
                'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
            ]
        );
        $doc_item_id = $doc_item->getID();

        // Check that the owner can see their own private document
        $doc_item->getFromDB($doc_item_id);
        $this->assertTrue($doc_item->canViewItem());

        // Clean up
        $this->login();
    }

    /**
     * Test that SEEPRIVATE constant is correctly defined.
     */
    public function testSeeprivateConstant()
    {
        $this->assertEquals(8192, Document_Item::SEEPRIVATE);
    }

    /**
     * Test that private documents are excluded from email notifications when user doesn't have SEEPRIVATE right.
     */
    public function testPrivateDocumentsExcludedFromNotifications()
    {
        $this->login();

        // Create a ticket
        $ticket = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test notification with private document',
                'content' => 'Test content',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        $tickets_id = $ticket->getID();

        // Create a public document
        $public_doc = $this->createItem(
            \Document::class,
            [
                'name' => 'Public document for notification test',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        // Create a private document
        $private_doc = $this->createItem(
            \Document::class,
            [
                'name' => 'Private document for notification test',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        // Attach public document
        $this->createItem(
            Document_Item::class,
            [
                'items_id'      => $tickets_id,
                'itemtype'      => \Ticket::class,
                'documents_id'  => $public_doc->getID(),
                'is_private'    => 0,
                'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
            ]
        );

        // Attach private document
        $this->createItem(
            Document_Item::class,
            [
                'items_id'      => $tickets_id,
                'itemtype'      => \Ticket::class,
                'documents_id'  => $private_doc->getID(),
                'is_private'    => 1,
                'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
            ]
        );

        // Get notification data without show_private (default behavior)
        $notification = new \NotificationTargetTicket();
        $notif_data = $notification->getDataForObject($ticket, [
            'additionnaloption' => [
                'usertype' => \NotificationTarget::GLPI_USER,
                'is_self_service' => false,
                'show_private' => false, // User doesn't have SEEPRIVATE right
            ],
        ]);

        // Verify documents array exists
        $this->assertArrayHasKey('documents', $notif_data);

        // Extract document names from notification data
        $document_names = array_column($notif_data['documents'], '##document.name##');

        // Verify public document is in notification
        $this->assertContains('Public document for notification test', $document_names);

        // Verify private document is NOT in notification when show_private is false
        $this->assertNotContains('Private document for notification test', $document_names);

        // Now test with show_private = true (user has SEEPRIVATE right)
        $notif_data_with_private = $notification->getDataForObject($ticket, [
            'additionnaloption' => [
                'usertype' => \NotificationTarget::GLPI_USER,
                'is_self_service' => false,
                'show_private' => true, // User has SEEPRIVATE right
            ],
        ]);

        // Extract document names with private access
        $document_names_with_private = array_column($notif_data_with_private['documents'], '##document.name##');

        // Verify both documents are in notification when show_private is true
        $this->assertContains('Public document for notification test', $document_names_with_private);
        $this->assertContains('Private document for notification test', $document_names_with_private);
    }
}
