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

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/document.class.php */

class DocumentTest extends DbTestCase
{
    public static function canApplyOnProvider()
    {
        return [
            [
                'item'   => new \DeviceBattery(),
                'can'    => true,
            ], [
                'item'   => 'DeviceBattery',
                'can'    => true,
            ], [
                'item'   => 'Item_DeviceBattery',
                'can'    => true,
            ], [
                'item'   => 'Computer',
                'can'    => true,
            ], [
                'item'   => new \Ticket(),
                'can'    => true,
            ], [
                'item'   => 'Config',
                'can'    => false,
            ], [
                'item'   => 'Item_Plug',
                'can'    => false,
            ],
        ];
    }

    #[DataProvider('canApplyOnProvider')]
    public function testCanApplyOn($item, $can)
    {
        $doc = new \Document();
        $this->assertSame(
            $can,
            $doc->canApplyOn($item)
        );
    }

    public function testGetItemtypesThatCanHave()
    {
        global $CFG_GLPI;

        $doc = new \Document();
        $itemtypes_doc = $doc->getItemtypesThatCanHave();

        $item_device_types = [];
        foreach ($CFG_GLPI['device_types'] as $device_type) {
            $item_device_types[] = $device_type::getItem_DeviceType();
        }
        $itemtypes = array_merge(
            $CFG_GLPI['document_types'],
            $CFG_GLPI['device_types'],
            $item_device_types,
        );

        $this->assertEquals(count($itemtypes_doc), count($itemtypes));
        foreach ($itemtypes as $itemtype) {
            $this->assertContains($itemtype, $itemtypes_doc);
        }
    }

    public function testDefineTabs()
    {
        $expected = [
            'Document$main'   => "Document",
            'Document_Item$1' => "Associated items",
            'Document_Item$2' => "Documents",
        ];
        $doc = new \Document();
        $tabs = array_map('strip_tags', $doc->defineTabs());
        $this->assertSame($expected, $tabs);
    }

    public function testPrepareInputForAdd()
    {
        $input = [
            'filename'   => 'A_name.pdf',
        ];

        $doc = new \Document();
        $prepare = $doc->prepareInputForAdd($input);
        $this->assertCount(3, $prepare);
        $this->assertArrayHasKey('tag', $prepare);
        $this->assertArrayHasKey('filename', $prepare);
        $this->assertArrayHasKey('name', $prepare);
        $this->assertSame('A_name.pdf', $prepare['filename']);
        $this->assertSame('A_name.pdf', $prepare['name']);

        $this->login();
        $uid = getItemByTypeName('User', TU_USER, true);
        $prepare = $doc->prepareInputForAdd($input);
        $this->assertCount(4, $prepare);
        $this->assertArrayHasKey('users_id', $prepare);
        $this->assertArrayHasKey('tag', $prepare);
        $this->assertArrayHasKey('filename', $prepare);
        $this->assertArrayHasKey('name', $prepare);
        $this->assertSame($uid, $prepare['users_id']);

        $item = new \Computer();
        $cid = $item->add([
            'name'         => 'Documented Computer',
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $cid);

        $input['itemtype'] = $item->getType();
        $input['items_id'] = $cid;

        //will fail because document has not been uploaded
        $this->assertFalse($doc->prepareInputForAdd($input));

        $mdoc = $this->getMockBuilder(\Document::class)
            ->onlyMethods(['moveUploadedDocument'])
            ->getMock();
        $mdoc->method('moveUploadedDocument')->willReturn(true);
        $input['upload_file'] = 'filename.ext';

        $prepare = $mdoc->prepareInputForAdd($input);
        $this->assertCount(6, $prepare);
        $this->assertArrayHasKey('users_id', $prepare);
        $this->assertArrayHasKey('tag', $prepare);
        $this->assertArrayHasKey('itemtype', $prepare);
        $this->assertArrayHasKey('items_id', $prepare);
        $this->assertArrayHasKey('filename', $prepare);
        $this->assertArrayHasKey('name', $prepare);
        $this->assertSame($uid, $prepare['users_id']);
        $this->assertSame('Computer', $prepare['itemtype']);
        $this->assertSame($cid, $prepare['items_id']);
        $this->assertSame('A_name.pdf', $prepare['name']);
    }

    /** Cannot work without a real document uploaded.
     *  Mock would be a solution but GLPI will try to use
     *  a table based on mocked class name, this is wrong.
     * public function testPost_addItem() {
     * $this->login();
     * $item = new \Computer();
     * $cid = (int)$item->add([
     * 'name'         => 'Documented Computer',
     * 'entities_id'  => 0
     * ]);
     * $this->assertGreaterThan(0, $cid);
     *
     * $mdoc = new \mock\Document();
     * $this->calling($mdoc)->moveUploadedDocument = true;
     * $input['upload_file'] = 'filename.ext';
     * $input['itemtype'] = $item->getType();
     * $input['items_id'] = $cid;
     *
     * $docid = (int)$mdoc->add($input);
     * $this->assertGreaterThan(0, $docid);
     *
     * $doc_item = new \Document_Item();
     * $this->assertTrue($doc_item->getFromDBByCrit(['documents_id' => $docid]));
     *
     * $this->assertSame(Computer::class, $doc_item->fields['itemtype']);
     * $this->assertEquals($cid, $doc_item->fields['items_id']);
     * }*/


    /**
     * Test case for the testPost_addItem method.
     *
     * This method tests the functionality of adding a document to a ticket and the subsequent status changes.
     * Specifically, it verifies the following scenarios:
     *
     * 1. When a document is added to a ticket in WAITING status without any assigned user,
     *    the status of the ticket changes to INCOMING.
     *
     * 2. When a document is added to a ticket in WAITING status with an assigned user,
     *    the status of the ticket changes to ASSIGNED.
     *
     * Note: The ticket status does not change to ASSIGNED in the first scenario
     * because there is no user assigned to the ticket.
     */
    public function testPost_addItem()
    {
        // Login with the user.
        $this->login();

        // Create a new ticket.
        $item = new \Ticket();
        $cid = (int) $item->add([
            'name'         => 'Documented Ticket',
            'entities_id'  => 0,
            'content'      => 'Ticket content',
            'status'       => \Ticket::WAITING,
        ]);

        // Verify that the ticket is successfully added to the database.
        $this->assertGreaterThan(0, $cid);


        // Add a user as requester of the ticket.
        $ticket_user = new \Ticket_User();
        $this->assertGreaterThan(
            0,
            $ticket_user->add([
                'tickets_id' => $cid,
                'users_id'   => getItemByTypeName('User', TU_USER, true),
                'type'       => \CommonITILActor::REQUESTER,
            ])
        );


        // Create a second test user.
        $user = new \User();
        $uid = $user->add([
            'name'         => 'test_user2',
            'realname'     => 'Test User',
            'firstname'    => 'Test',
            'password'     => 'test',
            'is_active'    => 1,
            'is_deleted'   => 0,
            'authtype'     => 1,
            'profiles_id'  => 0,
            'entities_id'  => 0,
            'usercategories_id' => 1,
        ]);
        $this->assertGreaterThan(0, $uid);

        // Create a document stub.
        $mdoc = $this->getMockBuilder(\Document::class)
            ->onlyMethods(['moveUploadedDocument'])
            ->getMock();
        $mdoc->method('moveUploadedDocument')->willReturn(true);

        $input['upload_file'] = 'filename.ext';
        $input['itemtype'] = $item->getType();
        $input['items_id'] = $cid;
        $input['documentcategories_id'] = 1;

        $docid = $mdoc->add($input);
        $this->assertGreaterThan(0, $docid);

        // Refresh the ticket.
        $this->assertTrue($item->getFromDB($cid));

        $doc_item = new \Document_Item();
        $this->assertTrue($doc_item->getFromDBByCrit(['documents_id' => $docid]));

        // Verify that the ticket and document item are linked.
        $this->assertSame(\Ticket::getType(), $doc_item->fields['itemtype']);
        $this->assertEquals($doc_item->fields['items_id'], $cid);

        /**
         * Verify that when a document is added to a ticket in WAITING status
         * without any assigned user, the status of the ticket changes to INCOMING.
         */
        $this->assertEquals(\Ticket::INCOMING, $item->fields['status']);

        // Assign the second user to the ticket.
        $ticket_user = new \Ticket_User();
        $this->assertGreaterThan(
            0,
            $ticket_user->add([
                'tickets_id' => $cid,
                'users_id'   => $uid,
                'type'       => \CommonITILActor::ASSIGN,
            ])
        );

        // Update the ticket status to WAITING.
        $item->update([
            'id'     => $cid,
            'status' => \Ticket::WAITING,
        ]);
        $this->assertEquals(\Ticket::WAITING, $item->fields['status']);

        // Add another document to the ticket.
        $docid = $mdoc->add($input);
        $this->assertGreaterThan(0, $docid);

        // Refresh the ticket.
        $this->assertTrue($item->getFromDB($cid));

        /**
         * Verify that when a document is added to a ticket in WAITING status
         * with an assigned user, the status of the ticket changes to ASSIGNED.
         */
        $this->assertEquals(\Ticket::ASSIGNED, $item->fields['status']);
    }

    public static function validDocProvider()
    {
        return [
            [
                'filename'  => 'myfile.png',
                'expected'  => 'PNG',
            ], [
                'filename'  => 'myfile.dOcX',
                'expected'  => 'DOCX',
            ], [
                'filename'  => 'myfile.notknown',
                'expected'  => '',
            ],
        ];
    }

    #[DataProvider('validDocProvider')]
    public function testIsValidDoc($filename, $expected)
    {
        $this->assertSame($expected, \Document::isValidDoc($filename));
    }

    public function testIsValidDocRegexp()
    {
        $doctype = new \DocumentType();
        $this->assertGreaterThan(
            0,
            $doctype->add([
                'name'   => 'Type test',
                'ext'    => '/[0-9]{4}/',
            ])
        );

        $this->assertSame('1234', \Document::isValidDoc('myfile.1234'));
        $this->assertSame('', \Document::isValidDoc('myfile.123'));
        $this->assertSame('9645', \Document::isValidDoc('myfile.9645'));
        $this->assertSame('', \Document::isValidDoc('myfile.abcde'));
    }

    public function testGetImageTag()
    {
        $this->assertSame('#datag#', \Document::getImageTag('datag'));
    }

    public static function isImageProvider()
    {
        return [
            [__FILE__, false],
            [__DIR__ . "/../../public/pics/add_dropdown.png", true],
            [__DIR__ . "/../../public/pics/corners.gif", true],
            [__DIR__ . "/../../public/pics/PICS-AUTHORS.txt", false],
            [__DIR__ . "/../notanimage.jpg", false],
            [__DIR__ . "/../notafile.jpg", false],
            [__DIR__ . "/../", false],
        ];
    }

    #[DataProvider('isImageProvider')]
    public function testIsImage($file, $expected)
    {
        $this->assertSame($expected, \Document::isImage($file));
    }

    /**
     * Check visibility of documents files that are not attached to anything.
     */
    public function testCanViewDocumentFile()
    {

        $document = new \Document();
        $this->assertGreaterThan(
            0,
            $document->add([
                'name'     => 'basic document',
                'filename' => 'doc.xls',
                'users_id' => '2', // user "glpi"
            ])
        );

        // glpi can see all documents
        $this->login('glpi', 'glpi');
        $this->assertTrue($document->canViewFile());

        // tech can see all documents
        $this->login('tech', 'tech');
        $this->assertTrue($document->canViewFile());

        // normal can see all documents
        $this->login('normal', 'normal');
        $this->assertTrue($document->canViewFile());

        // post-only cannot see all documents
        $this->login('post-only', 'postonly');
        $this->assertFalse($document->canViewFile());

        // post-only can see its own documents
        $this->login('post-only', 'postonly');
        $this->assertFalse($document->canViewFile([
            'itemtype' => 'not_a_class',
            'items_id' => 'not an id',
        ]));
        $this->assertTrue(
            $document->update(
                [
                    'id'       => $document->getID(),
                    'users_id' => \Session::getLoginUserID(),
                ]
            )
        );
        $this->assertTrue($document->canViewFile());
    }

    /**
     * Check visibility of document attached to reminders.
     */
    public function testCanViewReminderFile()
    {

        $basicDocument = new \Document();
        $this->assertGreaterThan(
            0,
            $basicDocument->add([
                'name'     => 'basic document',
                'filename' => 'doc.xls',
                'users_id' => '2', // user "glpi"
            ])
        );

        $inlinedDocument = new \Document();
        $this->assertGreaterThan(
            0,
            $inlinedDocument->add([
                'name'     => 'inlined document',
                'filename' => 'inlined.png',
                'users_id' => '2', // user "glpi"
            ])
        );

        $this->login('post-only', 'postonly');

        // post-only cannot see documents only linked to someone else reminders
        $glpiReminder = new \Reminder();
        $this->assertGreaterThan(
            0,
            $glpiReminder->add([
                'name'     => 'Glpi reminder',
                'text'     => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
                'users_id' => '2', // user "glpi"
            ])
        );

        $document_item = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $basicDocument->getID(),
                'items_id'     => $glpiReminder->getID(),
                'itemtype'     => \Reminder::class,
            ])
        );

        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $inlinedDocument->getID(),
                'items_id'     => $glpiReminder->getID(),
                'itemtype'     => \Reminder::class,
            ])
        );

        $this->assertFalse($basicDocument->canViewFile());
        $this->assertFalse($inlinedDocument->canViewFile());

        // post-only can see documents linked to its own reminders
        $myReminder = new \Reminder();
        $this->assertGreaterThan(
            0,
            $myReminder->add([
                'name'     => 'My reminder',
                'text'     => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
                'users_id' => \Session::getLoginUserID(),
            ])
        );

        $document_item = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $basicDocument->getID(),
                'items_id'     => $myReminder->getID(),
                'itemtype'     => \Reminder::class,
            ])
        );

        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $inlinedDocument->getID(),
                'items_id'     => $myReminder->getID(),
                'itemtype'     => \Reminder::class,
            ])
        );

        $this->assertTrue($basicDocument->canViewFile());
        $this->assertTrue($inlinedDocument->canViewFile());
    }

    /**
     * Check visibility of document attached to KB items.
     */
    public function testCanViewKnowbaseItemFile()
    {
        global $CFG_GLPI;

        $basicDocument = new \Document();
        $this->assertGreaterThan(
            0,
            $basicDocument->add([
                'name'     => 'basic document',
                'filename' => 'doc.xls',
                'users_id' => '2', // user "glpi"
            ])
        );

        $inlinedDocument = new \Document();
        $this->assertGreaterThan(
            0,
            $inlinedDocument->add([
                'name'     => 'inlined document',
                'filename' => 'inlined.png',
                'users_id' => '2', // user "glpi"
            ])
        );

        $kbItem = new \KnowbaseItem();
        $this->assertGreaterThan(
            0,
            $kbItem->add([
                'name'     => 'Generic KB item',
                'answer'   => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
                'users_id' => '2', // user "glpi"
            ])
        );

        $document_item = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $basicDocument->getID(),
                'items_id'     => $kbItem->getID(),
                'itemtype'     => \KnowbaseItem::class,
                'users_id'     => getItemByTypeName('User', 'normal', true),
            ])
        );

        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $inlinedDocument->getID(),
                'items_id'     => $kbItem->getID(),
                'itemtype'     => \KnowbaseItem::class,
                'users_id'     => getItemByTypeName('User', 'normal', true),
            ])
        );

        // anonymous cannot see documents if not linked to FAQ items
        $this->assertFalse($basicDocument->canViewFile());
        $this->assertFalse($inlinedDocument->canViewFile());

        // anonymous cannot see documents linked to FAQ items if public FAQ is not active
        $CFG_GLPI['use_public_faq'] = 0;

        $this->assertTrue(
            $kbItem->update(
                [
                    'id'     => $kbItem->getID(),
                    'is_faq' => true,
                ]
            )
        );

        // faq items in multiple entity mode need to be set in root entity +recursive to be viewed
        $entity_kbitems = new \Entity_KnowbaseItem();
        $ent_kb_id = $entity_kbitems->add([
            'knowbaseitems_id' => $kbItem->getID(),
            'entities_id'      => 0,
            'is_recursive'     => 1,
        ]);
        $this->assertGreaterThan(0, $ent_kb_id);

        $this->assertFalse($basicDocument->canViewFile());
        $this->assertFalse($inlinedDocument->canViewFile());

        // anonymous can see documents linked to FAQ items when public FAQ is active
        $CFG_GLPI['use_public_faq'] = 1;

        $this->assertTrue($basicDocument->canViewFile());
        $this->assertTrue($inlinedDocument->canViewFile());

        $CFG_GLPI['use_public_faq'] = 0;

        // post-only can see documents linked to FAQ items
        $this->login('post-only', 'postonly');

        $this->assertTrue($basicDocument->canViewFile());
        $this->assertTrue($inlinedDocument->canViewFile());

        // post-only cannot see documents if not linked to FAQ items
        $this->assertTrue(
            $kbItem->update(
                [
                    'id'     => $kbItem->getID(),
                    'is_faq' => false,
                ]
            )
        );
        $this->assertTrue(
            $entity_kbitems->delete([
                'id' => $ent_kb_id,
            ])
        );

        $this->assertFalse($basicDocument->canViewFile());
        $this->assertFalse($inlinedDocument->canViewFile());
    }

    /**
     * Data provider for self::testCanViewItilFile().
     */
    public static function itilTypeProvider()
    {
        return [
            [
                'itemtype' => \Change::class,
            ],
            [
                'itemtype' => \Problem::class,
            ],
            [
                'itemtype' => \Ticket::class,
            ],
        ];
    }

    /**
     * Check visibility of document attached to ITIL objects.
     */
    #[DataProvider('itilTypeProvider')]
    public function testCanViewItilFile($itemtype)
    {

        $this->login('glpi', 'glpi'); // Login with glpi to prevent link to post-only

        $basicDocument = new \Document();
        $this->assertGreaterThan(
            0,
            $basicDocument->add([
                'name'     => 'basic document',
                'filename' => 'doc.xls',
                'users_id' => '2', // user "glpi"
            ])
        );

        $inlinedDocument = new \Document();
        $this->assertGreaterThan(
            0,
            $inlinedDocument->add([
                'name'     => 'inlined document',
                'filename' => 'inlined.png',
                'users_id' => '2', // user "glpi"
            ])
        );

        $item = new $itemtype();
        $fkey = $item->getForeignKeyField();

        $this->assertGreaterThan(
            0,
            $item->add([
                'name'     => 'New ' . $itemtype,
                'content'  => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
            ])
        );

        $document_item = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $basicDocument->getID(),
                'items_id'     => $item->getID(),
                'itemtype'     => $itemtype,
            ])
        );

        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $inlinedDocument->getID(),
                'items_id'     => $item->getID(),
                'itemtype'     => $itemtype,
            ])
        );

        // post-only cannot see documents if not able to view ITIL (ITIL content)
        $this->login('post-only', 'postonly');
        $_SESSION["glpiactiveprofile"][$item::$rightname] = READ; // force READ write for tested ITIL type
        $this->assertFalse($basicDocument->canViewFile());
        $this->assertFalse($inlinedDocument->canViewFile());
        $this->assertFalse($basicDocument->canViewFile([$fkey => $item->getID()]));
        $this->assertFalse($inlinedDocument->canViewFile([$fkey => $item->getID()]));
        $this->assertFalse($basicDocument->canViewFile(['itemtype' => $item->getType(), 'items_id' => $item->getID()]));
        $this->assertFalse($inlinedDocument->canViewFile(['itemtype' => $item->getType(), 'items_id' => $item->getID()]));

        // post-only can see documents linked to its own ITIL (ITIL content)
        $itil_user_class = $itemtype . '_User';
        $itil_user = new $itil_user_class();
        $this->assertGreaterThan(
            0,
            $itil_user->add([
                $fkey      => $item->getID(),
                'type'     => \CommonITILActor::OBSERVER,
                'users_id' => \Session::getLoginUserID(),
            ])
        );

        $this->assertFalse($basicDocument->canViewFile()); // False without params
        $this->assertFalse($inlinedDocument->canViewFile()); // False without params
        $this->assertTrue($basicDocument->canViewFile([$fkey => $item->getID()]));
        $this->assertTrue($inlinedDocument->canViewFile([$fkey => $item->getID()]));
        $this->assertTrue($basicDocument->canViewFile(['itemtype' => $item->getType(), 'items_id' => $item->getID()]));
        $this->assertTrue($inlinedDocument->canViewFile(['itemtype' => $item->getType(), 'items_id' => $item->getID()]));
    }

    /**
     * Data provider for self::testCanViewTicketChildFile().
     */
    public static function ticketChildClassProvider()
    {
        return [
            [
                'itil_itemtype'  => \Change::class,
                'child_itemtype' => \ITILSolution::class,
            ],
            [
                'itil_itemtype'  => \Change::class,
                'child_itemtype' => \ChangeTask::class,
            ],
            [
                'itil_itemtype'  => \Change::class,
                'child_itemtype' => \ITILFollowup::class,
            ],
            [
                'itil_itemtype'  => \Problem::class,
                'child_itemtype' => \ITILSolution::class,
            ],
            [
                'itil_itemtype'  => \Problem::class,
                'child_itemtype' => \ProblemTask::class,
            ],
            [
                'itil_itemtype'  => \Problem::class,
                'child_itemtype' => \ITILFollowup::class,
            ],
            [
                'itil_itemtype'  => \Ticket::class,
                'child_itemtype' => \ITILSolution::class,
            ],
            [
                'itil_itemtype'  => \Ticket::class,
                'child_itemtype' => \TicketTask::class,
            ],
            [
                'itil_itemtype'  => \Ticket::class,
                'child_itemtype' => \ITILFollowup::class,
            ],
        ];
    }

    /**
     * Check visibility of document inlined in ITIL followup, tasks, solutions.
     */
    #[DataProvider('ticketChildClassProvider')]
    public function testCanViewTicketChildFile($itil_itemtype, $child_itemtype)
    {

        $this->login('glpi', 'glpi'); // Login with glpi to prevent link to post-only

        $inlinedDocument = new \Document();
        $this->assertGreaterThan(
            0,
            $inlinedDocument->add([
                'name'     => 'inlined document',
                'filename' => 'inlined.png',
                'users_id' => '2', // user "glpi"
            ])
        );

        $itil = new $itil_itemtype();
        $fkey = $itil->getForeignKeyField();
        $this->assertGreaterThan(
            0,
            $itil->add([
                'name'     => 'New ' . $itil_itemtype,
                'content'  => 'No image in content',
            ])
        );

        $child = new $child_itemtype();
        $this->assertGreaterThan(
            0,
            $child->add([
                'content'    => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
                $fkey        => $itil->getID(),
                'items_id'   => $itil->getID(),
                'itemtype'   => $itil_itemtype,
                'users_id'   => '2', // user "glpi"
            ])
        );

        $document_item = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $document_item->add([
                'documents_id' => $inlinedDocument->getID(),
                'items_id'     => $itil->getID(),
                'itemtype'     => $itil_itemtype,
            ])
        );

        // post-only cannot see documents if not able to view ITIL
        $this->login('post-only', 'postonly');
        $_SESSION["glpiactiveprofile"][$itil::$rightname] = READ; // force READ write for tested ITIL type
        $this->assertFalse($inlinedDocument->canViewFile());
        $this->assertFalse($inlinedDocument->canViewFile([$fkey => $itil->getID()]));
        $this->assertFalse($inlinedDocument->canViewFile(['itemtype' => $itil->getType(), 'items_id' => $itil->getID()]));

        // post-only can see documents linked to its own ITIL
        $itil_user_class = $itil_itemtype . '_User';
        $itil_user = new $itil_user_class();
        $this->assertGreaterThan(
            0,
            $itil_user->add([
                $fkey => $itil->getID(),
                'type'       => \CommonITILActor::OBSERVER,
                'users_id'   => \Session::getLoginUserID(),
            ])
        );

        $this->assertFalse($inlinedDocument->canViewFile()); // False without params
        $this->assertTrue($inlinedDocument->canViewFile([$fkey => $itil->getID()]));
        $this->assertTrue($inlinedDocument->canViewFile(['itemtype' => $itil->getType(), 'items_id' => $itil->getID()]));
    }

    public function testCronCleanorphans()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        $doc = new \Document();

        $did1 = (int) $doc->add([
            'name'   => 'test doc',
        ]);
        $this->assertGreaterThan(0, $did1);

        $did2 = (int) $doc->add([
            'name'   => 'test doc',
        ]);
        $this->assertGreaterThan(0, $did2);

        $did3 = (int) $doc->add([
            'name'   => 'test doc',
        ]);
        $this->assertGreaterThan(0, $did3);

        // create a ticket and link one document
        $ticket = new \Ticket();
        $tickets_id_1 = $ticket->add([
            'name'            => "test 1",
            'content'         => "test 1",
            'entities_id'     => 0,
            '_documents_id'   => [$did3],
        ]);
        $this->assertGreaterThan(0, (int) $tickets_id_1);
        $this->assertTrue($ticket->getFromDB($tickets_id_1));

        $docitem = new \Document_Item();
        $this->assertTrue($docitem->getFromDBByCrit(['itemtype' => 'Ticket', 'items_id' => $tickets_id_1]));

        // launch Cron for closing tickets
        $mode = - \CronTask::MODE_EXTERNAL; // force
        \CronTask::launch($mode, 5, 'cleanorphans');

        // check documents presence
        $this->assertFalse($doc->getFromDB($did1));
        $this->assertFalse($doc->getFromDB($did2));
        $this->assertTrue($doc->getFromDB($did3));
    }

    public function testGetDuplicateOf()
    {
        $instance = new \Document();

        // Test when the file is not in the DB
        $output = $instance->getDuplicateOf(0, FIXTURE_DIR . '/uploads/foo.png');
        $this->assertFalse($output);

        $filename = 'foo.png';
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);
        $tag = \Rule::getUuid();
        $input = [
            'filename' => 'foo.png',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                $tag,
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ],
        ];
        $document = new \Document();
        $document->add($input);
        $this->assertFalse($document->isnewItem());

        // Check the file is found in the FB
        $instance = new \Document();
        $output = $instance->getDuplicateOf(0, FIXTURE_DIR . '/uploads/foo.png');
        $this->assertTrue($output);

        // toggle the blacklisted flag
        $success = $instance->update([
            'id'             => $instance->getID(),
            'is_blacklisted' => '1',
        ]);
        $this->assertTrue($success);

        // Test when the document exists and is blacklisted
        $output = $instance->getDuplicateOf(0, FIXTURE_DIR . '/uploads/foo.png');
        $this->assertFalse($output);
    }
}
