<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */
class Dropdown_Import extends PHPUnit_Framework_TestCase {

   /**
    * Import of CommonDropdown - without dictionary
    */
   public function testImportSimple() {

      $input=array('name'=>'', 'comment'=>"test import");
      $id = Dropdown::import('DeviceCaseType', $input);
      $this->assertFalse($id>0);

      $input=array('name'=>'PHP Unit test 1', 'comment'=>"test import");
      $id1 = Dropdown::import('DeviceCaseType', $input);
      $this->assertTrue($id1>0);

      $id2 = Dropdown::import('DeviceCaseType', $input);
      $this->assertTrue($id2>0);
      $this->assertTrue($id1==$id2);

      $input=array('name'=>'PHP Unit test 2');
      $id3 = Dropdown::import('DeviceCaseType', $input);
      $this->assertTrue($id3>0);
      $this->assertTrue($id3!=$id2);

      $dct = new DeviceCaseType();
      $this->assertTrue($dct->getFromDB($id3));
      $this->assertTrue($dct->fields['name']==$input['name']);
   }

   /**
    * Import of CommonTreeDropdown
    */
   public function testImportTree() {

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      $obj = new TicketCategory();
      $fk = 'ticketcategories_id';

      // root entity - A - new
      $id[0] = $obj->import(array('name'         => 'A',
                                  'is_recursive' => 1,
                                  'entities_id'  => $ent0));
      $this->assertGreaterThan(0, $id[0]);
      $this->assertTrue($obj->getFromDB($id[0]));
      $this->assertEquals(1, $obj->fields['is_recursive']);

      // root entity - B - new
      $id[1] = $obj->import(array('name'         => 'B',
                                  'entities_id'  => $ent0));
      $this->assertGreaterThan(0, $id[1]);
      $this->assertTrue($obj->getFromDB($id[1]));
      $this->assertEquals(0, $obj->fields['is_recursive']);

      // child entity - A - existing
      $id[2] = $obj->import(array('name'         => 'A',
                                  'entities_id'  => $ent1));
      $this->assertEquals($id[0],$id[2]);

      // child entity - B - new
      $id[3] = $obj->import(array('name'         => 'B',
                                  'entities_id'  => $ent1));
      $this->assertGreaterThan($id[1], $id[3]);

      // child entity - B > C - exiting B + new C
      $id[4] = $obj->import(array('completename' => 'B > C',
                                  'entities_id'  => $ent1));
      $this->assertGreaterThan($id[3], $id[4]);
      $this->assertTrue($obj->getFromDB($id[4]));
      $this->assertEquals('C', $obj->fields['name']);
      $this->assertEquals($id[3], $obj->fields[$fk]);

      // child entity - >B>>C>D - clean completename
      $id[5] = $obj->import(array('completename' => '>B>> C>D',
                                  'entities_id'  => $ent1));
      $this->assertGreaterThan($id[4], $id[5]);
      $this->assertTrue($obj->getFromDB($id[5]));
      $this->assertEquals('D', $obj->fields['name']);
      $this->assertEquals($id[4], $obj->fields[$fk]);
      $this->assertEquals('B > C > D', $obj->fields['completename']);
   }
}
?>
