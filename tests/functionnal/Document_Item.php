<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

/* Test for inc/document_item.class.php */

class Document_Item extends DbTestCase {

   public function testGetForbiddenStandardMassiveAction() {
      $this->newTestedInstance();
      $this->array(
         $this->testedInstance->getForbiddenStandardMassiveAction()
      )->isIdenticalTo(['clone', 'update']);
   }

   public function testPrepareInputForAdd() {
      $input = [];
      $ditem = $this->newTestedInstance;

      $this->exception(
         function () use ($input) {
            $this->boolean($this->testedInstance->add($input))->isFalse();
         }
      )->message->contains('Item type is mandatory');

      $input['itemtype'] = '';
      $this->exception(
         function () use ($input) {
            $this->boolean($this->testedInstance->add($input))->isFalse();
         }
      )->message->contains('Item type is mandatory');

      $input['itemtype'] = 'NotAClass';
      $this->exception(
         function () use ($input) {
            $this->boolean($this->testedInstance->add($input))->isFalse();
         }
      )->message->contains('No class found for type NotAClass');

      $input['itemtype'] = 'Computer';
      $this->exception(
         function () use ($input) {
            $this->boolean($this->testedInstance->add($input))->isFalse();
         }
      )->message->contains('Item ID is mandatory');

      $input['items_id'] = 0;
      $this->exception(
         function () use ($input) {
            $this->boolean($this->testedInstance->add($input))->isFalse();
         }
      )->message->contains('Item ID is mandatory');

      $cid = getItemByTypeName('Computer', '_test_pc01', true);
      $input['items_id'] = $cid;

      $this->exception(
         function () use ($input) {
            $this->boolean($this->testedInstance->add($input))->isFalse();
         }
      )->message->contains('Document ID is mandatory');

      $input['documents_id'] = 0;
      $this->exception(
         function () use ($input) {
            $this->boolean($this->testedInstance->add($input))->isFalse();
         }
      )->message->contains('Document ID is mandatory');

      $document = new \Document();
      $this->integer(
         (int)$document->add([
            'name'   => 'Test document to link'
         ])
      )->isGreaterThan(0);
      $input['documents_id'] = $document->getID();

      $expected = [
         'itemtype'     => 'Computer',
         'items_id'     => $cid,
         'documents_id' => $document->getID(),
         'users_id'     => false,
         'entities_id'  => 0,
         'is_recursive' => 0
      ];

      $this->array(
         $this->testedInstance->prepareInputForAdd($input)
      )->isIdenticalTo($expected);
   }


   public function testGetDistinctTypesParams() {
      $expected = [
         'SELECT'          => 'itemtype',
         'DISTINCT'        => true,
         'FROM'            => 'glpi_documents_items',
         'WHERE'           => [
            'OR'  => [
               'glpi_documents_items.documents_id'  => 1,
               [
                  'glpi_documents_items.itemtype'  => 'Document',
                  'glpi_documents_items.items_id'  => 1
               ]
            ]
         ],
         'ORDER'           => 'itemtype'
      ];
      $this->array(\Document_Item::getDistinctTypesParams(1))->isIdenticalTo($expected);

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
                  'glpi_documents_items.items_id'  => 1
               ]
            ],
            [
               'date_mod'  => [
                  '>',
                  '2000-01-01'
               ]
            ]
         ],
         'ORDER'           => 'itemtype'
      ];
      $this->array(\Document_Item::getDistinctTypesParams(1, $extra_where))->isIdenticalTo($expected);
   }


   public function testPostAddItem() {
      $uid = getItemByTypeName('User', TU_USER, true);

      $ticket = new \Ticket();
      $tickets_id = $ticket->add([
         'name' => '',
         'content' => 'Test modification date not updated from Document_Item',
         'date_mod' => '2020-01-01'
      ]);

      $this->integer($tickets_id)->isGreaterThan(0);

      // Document and Document_Item
      $doc = new \Document();
      $this->integer(
         (int)$doc->add([
            'users_id'     => $uid,
            'tickets_id'   => $tickets_id,
            'name'         => 'A simple document object'
         ])
      )->isGreaterThan(0);

      //do not change ticket modification date
      $doc_item = new \Document_Item();
      $this->integer(
         (int)$doc_item->add([
            'users_id'      => $uid,
            'items_id'      => $tickets_id,
            'itemtype'      => 'Ticket',
            'documents_id'  => $doc->getID(),
            '_do_update_ticket' => false
         ])
      )->isGreaterThan(0);

      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->string($ticket->fields['date_mod'])->isIdenticalTo('2020-01-01 00:00:00');

      //do change ticket modification date
      $_SESSION["glpi_currenttime"] = '2021-01-01 00:00:01';
      $doc = new \Document();
      $this->integer(
         (int)$doc->add([
            'users_id'     => $uid,
            'tickets_id'   => $tickets_id,
            'name'         => 'A simple document object'
         ])
      )->isGreaterThan(0);

      $doc_item = new \Document_Item();
      $this->integer(
         (int)$doc_item->add([
            'users_id'      => $uid,
            'items_id'      => $tickets_id,
            'itemtype'      => 'Ticket',
            'documents_id'  => $doc->getID(),
         ])
      )->isGreaterThan(0);

      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->string($ticket->fields['date_mod'])->isNotEqualTo('2021-01-01 00:00:01');

   }
}
