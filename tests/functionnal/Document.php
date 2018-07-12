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
}
