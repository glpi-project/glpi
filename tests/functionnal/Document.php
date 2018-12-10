<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use \DbTestCase;

/* Test for inc/document.class.php */

class Document extends DbTestCase {

   public function canApplyOnProvider() {
      return [
         [
            'item'   => new \DeviceBattery(),
            'can'    => true
         ], [
            'item'   => 'DeviceBattery',
            'can'    => true
         ], [
            'item'   => 'Item_DeviceBattery',
            'can'    => true
         ], [
            'item'   => 'Computer',
            'can'    => true
         ], [
            'item'   => new \Ticket(),
            'can'    => true
         ], [
            'item'   => 'Config',
            'can'    => false
         ], [
            'item'   => 'Pdu_Plug',
            'can'    => false
         ]
      ];
   }

   /**
    * @dataProvider canApplyOnProvider
    */
   public function testCanApplyOn($item, $expected) {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->canApplyOn($item))
               ->isIdenticalTo($expected);
   }

   public function testGetItemtypesThatCanHave() {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->getItemtypesThatCanHave())
               ->size->isGreaterThan(50);
   }

   public function testDefineTabs() {
      $expected = [
         'Document$main'   => 'Document',
         'Document_Item$1' => 'Associated items',
         'Document_Item$2' => 'Documents',
         'Log$1'           => 'Historical'

      ];
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected);
   }

   public function testPrepareInputForAdd() {
      $input = [
         'filename'   => 'A name'
      ];

      $doc = $this->newTestedInstance;

      $this->array($this->testedInstance->prepareInputForAdd($input))
         ->hasSize(1)
        ->hasKey('tag');

      $this->login();
      $uid = getItemByTypeName('User', TU_USER, true);
      $this->array($this->testedInstance->prepareInputForAdd($input))
         ->hasSize(2)
         ->hasKeys(['users_id', 'tag'])
         ->variable['users_id']->isEqualTo($uid);

      $item = new \Computer();
      $cid = (int)$item->add([
         'name'         => 'Documented Computer',
         'entities_id'  => 0
      ]);
      $this->integer($cid)->isGreaterThan(0);

      $input['itemtype'] = $item->getType();
      $input['items_id'] = $cid;

      //will fail because document has not been uploaded
      $this->boolean($this->testedInstance->prepareInputForAdd($input))->isFalse();

      $mdoc = new \mock\Document();
      $this->calling($mdoc)->moveUploadedDocument = true;
      $input['upload_file'] = 'filename.ext';

      $this->array($mdoc->prepareInputForAdd($input))
         ->hasSize(5)
         ->hasKeys(['users_id', 'tag', 'itemtype', 'items_id', 'name'])
         ->variable['users_id']->isEqualTo($uid)
         ->string['itemtype']->isIdenticalTo('Computer')
         ->variable['items_id']->isEqualTo($cid)
         ->string['name']->isIdenticalTo('Document: Computer - Documented Computer');
   }

   /** Cannot work without a real document uploaded.
    *  Mock would be a solution but GLPI will try to use
    *  a table based on mocked class name, this is wrong.
   public function testPost_addItem() {
      $this->login();
      $item = new \Computer();
      $cid = (int)$item->add([
         'name'         => 'Documented Computer',
         'entities_id'  => 0
      ]);
      $this->integer($cid)->isGreaterThan(0);

      $mdoc = new \mock\Document();
      $this->calling($mdoc)->moveUploadedDocument = true;
      $input['upload_file'] = 'filename.ext';
      $input['itemtype'] = $item->getType();
      $input['items_id'] = $cid;

      $docid = (int)$mdoc->add($input);
      $this->integer($docid)->isGreaterThan(0);

      $doc_item = new \Document_Item();
      $this->boolean($doc_item->getFromDBByCrit(['documents_id' => $docid]))->isTrue();

      $this->array($doc_item->fields)
         ->string['itemtype']->isIdenticalTo('Computer')
         ->variable['items_id']->isEqualTo($cid);
   }*/

   protected function validDocProvider() {
      return [
         [
            'filename'  => 'myfile.png',
            'expected'  => 'PNG'
         ], [
            'filename'  => 'myfile.dOcX',
            'expected'  => 'DOCX'
         ], [
            'filename'  => 'myfile.notknown',
            'expected'  => ''
         ]
      ];
   }

   /**
    * @dataProvider validDocProvider
    */
   public function testIsValidDoc($filename, $expected) {
      $this->string(\Document::isValidDoc($filename))->isIdenticalTo($expected);
   }

   public function testIsValidDocRegexp() {
      $doctype = new \DocumentType();
      $this->integer(
         (int)$doctype->add([
            'name'   => 'Type test',
            'ext'    => '/[0-9]{4}/'
         ])
      )->isGreaterThan(0);

      $this->string(\Document::isValidDoc('myfile.1234'))->isIdenticalTo('1234');
      $this->string(\Document::isValidDoc('myfile.123'))->isIdenticalTo('');
      $this->string(\Document::isValidDoc('myfile.9645'))->isIdenticalTo('9645');
      $this->string(\Document::isValidDoc('myfile.abcde'))->isIdenticalTo('');
   }

   public function testGetImageTag() {
      $this->string(\Document::getImageTag('datag'))->isIdenticalTo('#datag#');
   }

   protected function isImageProvider() {
      return [
         ['PNG', true],
         ['png', true],
         ['JPG', true],
         ['jpg', true],
         ['jpeg', true],
         ['JPEG', true],
         ['bmp', true],
         ['BMP', true],
         ['gif', true],
         ['GIF', true],
         ['SVG', false]
      ];
   }

   /**
    * @dataProvider isImageProvider
    */
   public function testIsImage($ext, $expected) {
      $this->variable(\Document::isImage('myfile.' . $ext))->isIdenticalTo($expected);
   }

   /**
    * Check visibility of documents files that are not attached to anything.
    */
   public function testCanViewDocumentFile() {

      $document = new \Document();
      $this->integer(
         (int)$document->add([
            'name'     => 'basic document',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      // glpi can see all documents
      $this->login('glpi', 'glpi');
      $this->boolean($document->canViewFile())->isTrue();

      // tech can see all documents
      $this->login('tech', 'tech');
      $this->boolean($document->canViewFile())->isTrue();

      // normal can see all documents
      $this->login('normal', 'normal');
      $this->boolean($document->canViewFile())->isTrue();

      // post-only cannot see all documents
      $this->login('post-only', 'postonly');
      $this->boolean($document->canViewFile())->isFalse();

      // post-only can see its own documents
      $this->login('post-only', 'postonly');
      $this->boolean(
         $document->update(
            [
               'id'       => $document->getID(),
               'users_id' => \Session::getLoginUserID(),
            ]
         )
      )->isTrue();
      $this->boolean($document->canViewFile())->isTrue();
   }

   /**
    * Check visibility of document attached to reminders.
    */
   public function testCanViewReminderFile() {

      $basicDocument = new \Document();
      $this->integer(
         (int)$basicDocument->add([
            'name'     => 'basic document',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $inlinedDocument = new \Document();
      $this->integer(
         (int)$inlinedDocument->add([
            'name'     => 'inlined document',
            'filename' => 'inlined.png',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $this->login('post-only', 'postonly');

      // post-only cannot see documents only linked to someone else reminders
      $glpiReminder = new \Reminder();
      $this->integer(
         (int)$glpiReminder->add([
            'name'     => 'Glpi reminder',
            'text'     => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $document_item = new \Document_Item();
      $this->integer(
         (int)$document_item->add([
            'documents_id' => $basicDocument->getID(),
            'items_id'     => $glpiReminder->getID(),
            'itemtype'     => \Reminder::class,
         ])
      )->isGreaterThan(0);

      $this->boolean($basicDocument->canViewFile())->isFalse();
      $this->boolean($inlinedDocument->canViewFile())->isFalse();

      // post-only can see documents linked to its own reminders
      $myReminder = new \Reminder();
      $this->integer(
         (int)$myReminder->add([
            'name'     => 'My reminder',
            'text'     => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
            'users_id' => \Session::getLoginUserID(),
         ])
      )->isGreaterThan(0);

      $document_item = new \Document_Item();
      $this->integer(
         (int)$document_item->add([
            'documents_id' => $basicDocument->getID(),
            'items_id'     => $myReminder->getID(),
            'itemtype'     => \Reminder::class,
         ])
      )->isGreaterThan(0);

      $this->boolean($basicDocument->canViewFile())->isTrue();
      $this->boolean($inlinedDocument->canViewFile())->isTrue();
   }

   /**
    * Check visibility of document attached to KB items.
    */
   public function testCanViewKnowbaseItemFile() {

      global $CFG_GLPI;

      $basicDocument = new \Document();
      $this->integer(
         (int)$basicDocument->add([
            'name'     => 'basic document',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $inlinedDocument = new \Document();
      $this->integer(
         (int)$inlinedDocument->add([
            'name'     => 'inlined document',
            'filename' => 'inlined.png',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $kbItem = new \KnowbaseItem();
      $this->integer(
         (int)$kbItem->add([
            'name'     => 'Generic KB item',
            'answer'   => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $document_item = new \Document_Item();
      $this->integer(
         (int)$document_item->add([
            'documents_id' => $basicDocument->getID(),
            'items_id'     => $kbItem->getID(),
            'itemtype'     => \KnowbaseItem::class,
         ])
      )->isGreaterThan(0);

      // anonymous cannot see documents if not linked to FAQ items
      $this->boolean($basicDocument->canViewFile())->isFalse();
      $this->boolean($inlinedDocument->canViewFile())->isFalse();

      // anonymous cannot see documents linked to FAQ items if public FAQ is not active
      $CFG_GLPI['use_public_faq'] = 0;

      $this->boolean(
         $kbItem->update(
            [
               'id'     => $kbItem->getID(),
               'is_faq' => true,
            ]
         )
      )->isTrue();

      // faq items in mulitple entity mode need to be set in root enity +recursive to be viewed
      $entity_kbitems = new \Entity_KnowbaseItem;
      $ent_kb_id = $entity_kbitems->add([
         'knowbaseitems_id' => $kbItem->getID(),
         'entities_id'      => 0,
         'is_recursive'     => 1,
      ]);
      $this->integer($ent_kb_id)->isGreaterThan(0);

      $this->boolean($basicDocument->canViewFile())->isFalse();
      $this->boolean($inlinedDocument->canViewFile())->isFalse();

      // anonymous can see documents linked to FAQ items when public FAQ is active
      $CFG_GLPI['use_public_faq'] = 1;

      $this->boolean($basicDocument->canViewFile())->isTrue();
      $this->boolean($inlinedDocument->canViewFile())->isTrue();

      $CFG_GLPI['use_public_faq'] = 0;

      // post-only can see documents linked to FAQ items
      $this->login('post-only', 'postonly');

      $this->boolean($basicDocument->canViewFile())->isTrue();
      $this->boolean($inlinedDocument->canViewFile())->isTrue();

      // post-only cannot see documents if not linked to FAQ items
      $this->boolean(
         $kbItem->update(
            [
               'id'     => $kbItem->getID(),
               'is_faq' => false,
            ]
         )
      )->isTrue();
      $this->boolean(
         $entity_kbitems->delete([
            'id' => $ent_kb_id
         ])
      )->isTrue();

      $this->boolean($basicDocument->canViewFile())->isFalse();
      $this->boolean($inlinedDocument->canViewFile())->isFalse();
   }

   /**
    * Check visibility of document attached to tickets.
    */
   public function testCanViewTicketFile() {

      global $CFG_GLPI;

      $CFG_GLPI['use_rich_text'] = 1;

      $basicDocument = new \Document();
      $this->integer(
         (int)$basicDocument->add([
            'name'     => 'basic document',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $inlinedDocument = new \Document();
      $this->integer(
         (int)$inlinedDocument->add([
            'name'     => 'inlined document',
            'filename' => 'inlined.png',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $this->login('glpi', 'glpi'); // Login with glpi to prevent link to post-only
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'     => 'New ticket',
            'content'  => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
         ])
      )->isGreaterThan(0);

      $document_item = new \Document_Item();
      $this->integer(
         (int)$document_item->add([
            'documents_id' => $basicDocument->getID(),
            'items_id'     => $ticket->getID(),
            'itemtype'     => \Ticket::class,
         ])
      )->isGreaterThan(0);

      // post-only cannot see documents if not able to view ticket (ticket content)
      $this->login('post-only', 'postonly');
      $this->boolean($basicDocument->canViewFile(['tickets_id' => $ticket->getID()]))->isFalse();
      $this->boolean($inlinedDocument->canViewFile(['tickets_id' => $ticket->getID()]))->isFalse();

      // post-only can see documents linked to its own tickets (ticket content)
      $ticket_user = new \Ticket_User();
      $this->integer(
         (int)$ticket_user->add([
            'tickets_id' => $ticket->getID(),
            'type'       => \CommonITILActor::OBSERVER,
            'users_id'   => \Session::getLoginUserID(),
         ])
      )->isGreaterThan(0);

      $this->boolean($basicDocument->canViewFile(['tickets_id' => $ticket->getID()]))->isTrue();
      $this->boolean($inlinedDocument->canViewFile(['tickets_id' => $ticket->getID()]))->isTrue();
   }

   /**
    * Data provider for self::testCanViewTicketChildFile().
    */
   protected function ticketChildClassProvider() {
      return [
         [
            'class' => \ITILSolution::class,
         ],
         [
            'class' => \TicketTask::class,
         ],
         [
            'class' => \ITILFollowup::class,
         ],
      ];
   }

   /**
    * Check visibility of document inlined in tickets followup.
    *
    * @dataProvider ticketChildClassProvider
    */
   public function testCanViewTicketChildFile($childClass) {

      global $CFG_GLPI;

      $CFG_GLPI['use_rich_text'] = 1;

      $this->login('glpi', 'glpi'); // Login with glpi to prevent link to post-only
      $inlinedDocument = new \Document();
      $this->integer(
         (int)$inlinedDocument->add([
            'name'     => 'inlined document',
            'filename' => 'inlined.png',
            'users_id' => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'     => 'New ticket',
            'content'  => 'No image in content',
         ])
      )->isGreaterThan(0);

      $child = new $childClass();
      $this->integer(
         (int)$child->add([
            'content'    => '<img src="/front/document.send.php?docid=' . $inlinedDocument->getID() . '" />',
            'tickets_id' => $ticket->getID(),
            'items_id'   => $ticket->getID(),
            'itemtype'   => \Ticket::class,
            'users_id'   => '2', // user "glpi"
         ])
      )->isGreaterThan(0);

      // post-only cannot see documents if not able to view ticket
      $this->login('post-only', 'postonly');
      $this->boolean($inlinedDocument->canViewFile(['tickets_id' => $ticket->getID()]))->isFalse();

      // post-only can see documents linked to its own tickets
      $ticket_user = new \Ticket_User();
      $this->integer(
         (int)$ticket_user->add([
            'tickets_id' => $ticket->getID(),
            'type'       => \CommonITILActor::OBSERVER,
            'users_id'   => \Session::getLoginUserID(),
         ])
      )->isGreaterThan(0);

      $this->boolean($inlinedDocument->canViewFile(['tickets_id' => $ticket->getID()]))->isTrue();
   }
}
