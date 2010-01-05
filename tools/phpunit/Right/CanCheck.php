<?php
/*
 * @version $Id: ajax.function.php 9612 2009-12-10 16:58:43Z moyo $
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
class Right_CanCheck extends PHPUnit_Framework_TestCase {

   /**
    * Check right on Recursive object
    */
   public function testPrinter() {

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      $printer = new Printer();

      $id[0] = $printer->add(array('name'         => "Printer 1",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 0));
      $this->assertGreaterThan(0, $id[0]);

      $id[1] = $printer->add(array('name'         => "Printer 2",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $id[1]);

      $id[2] = $printer->add(array('name'         => "Printer 3",
                                   'entities_id'  => $ent1,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $id[2]);

      $id[3] = $printer->add(array('name'         => "Printer 4",
                                   'entities_id'  => $ent2));
      $this->assertGreaterThan(0, $id[3]);

      // Super admin
      changeProfile(4);
      $this->assertEquals(4, $_SESSION['glpiactiveprofile']['id']);
      $this->assertEquals('w', $_SESSION['glpiactiveprofile']['printer']);

      // See all
      $this->assertTrue(changeActiveEntities("all"));

      $this->assertTrue($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertTrue($printer->can($id[2],'r'));
      $this->assertTrue($printer->can($id[3],'r'));

      $this->assertTrue($printer->can($id[0],'w'));
      $this->assertTrue($printer->can($id[1],'w'));
      $this->assertTrue($printer->can($id[2],'w'));
      $this->assertTrue($printer->can($id[3],'w'));

      // See only in main entity
      $this->assertTrue(changeActiveEntities($ent0));

      $this->assertTrue($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertFalse($printer->can($id[2],'r'));
      $this->assertFalse($printer->can($id[3],'r'));

      $this->assertTrue($printer->can($id[0],'w'));
      $this->assertTrue($printer->can($id[1],'w'));
      $this->assertFalse($printer->can($id[2],'w'));
      $this->assertFalse($printer->can($id[3],'w'));

      // See only in child entity 1 + parent if recursive
      $this->assertTrue(changeActiveEntities($ent1));

      $this->assertFalse($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertTrue($printer->can($id[2],'r'));
      $this->assertFalse($printer->can($id[3],'r'));

      $this->assertFalse($printer->can($id[0],'w'));
      $this->assertFalse($printer->can($id[1],'w'));
      $this->assertTrue($printer->can($id[2],'w'));
      $this->assertFalse($printer->can($id[3],'w'));

      // See only in child entity 2 + parent if recursive
      $this->assertTrue(changeActiveEntities($ent2));

      $this->assertFalse($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertFalse($printer->can($id[2],'r'));
      $this->assertTrue($printer->can($id[3],'r'));

      $this->assertFalse($printer->can($id[0],'w'));
      $this->assertFalse($printer->can($id[1],'w'));
      $this->assertFalse($printer->can($id[2],'w'));
      $this->assertTrue($printer->can($id[3],'w'));
   }

   /**
    * Check right on CommonDBRelation object
    */
   public function testContact_Supplier() {

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      // Super admin
      changeProfile(4);
      $this->assertEquals(4, $_SESSION['glpiactiveprofile']['id']);
      $this->assertEquals('w', $_SESSION['glpiactiveprofile']['contact_enterprise']);

      // See all
      $this->assertTrue(changeActiveEntities("all"));

      // Create some contacts
      $contact = new Contact();

      $idc[0] = $contact->add(array('name'         => "Contact 1",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 0));
      $this->assertGreaterThan(0, $idc[0]);

      $idc[1] = $contact->add(array('name'         => "Contact 2",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $idc[1]);

      $idc[2] = $contact->add(array('name'         => "Contact 3",
                                   'entities_id'  => $ent1,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $idc[2]);

      $idc[3] = $contact->add(array('name'         => "Contact 4",
                                   'entities_id'  => $ent2));
      $this->assertGreaterThan(0, $idc[3]);

      // Create some suppliers
      $supplier = new Supplier();

      $ids[0] = $supplier->add(array('name'         => "Supplier 1",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 0));
      $this->assertGreaterThan(0, $ids[0]);

      $ids[1] = $supplier->add(array('name'         => "Supplier 2",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $ids[1]);

      $ids[2] = $supplier->add(array('name'         => "Supplier 3",
                                   'entities_id'  => $ent1));
      $this->assertGreaterThan(0, $ids[2]);

      $ids[3] = $supplier->add(array('name'         => "Supplier 4",
                                   'entities_id'  => $ent2));
      $this->assertGreaterThan(0, $ids[3]);

      // Relation
      $rel = new Contact_Supplier();
      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[0]);   // root
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[0] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[0]);
      $this->assertTrue($rel->can($idr[0],'r'));
      $this->assertTrue($rel->can($idr[0],'w'));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[1] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[1]);
      $this->assertTrue($rel->can($idr[1],'r'));
      $this->assertTrue($rel->can($idr[1],'w'));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[2]);   // child 1
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[3]);   // child 2
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[0]);   // root
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[2] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[2]);
      $this->assertTrue($rel->can($idr[2],'r'));
      $this->assertTrue($rel->can($idr[2],'w'));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[3] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[3]);
      $this->assertTrue($rel->can($idr[3],'r'));
      $this->assertTrue($rel->can($idr[3],'w'));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[2]);   // child 1
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[4] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[4]);
      $this->assertTrue($rel->can($idr[4],'r'));
      $this->assertTrue($rel->can($idr[4],'w'));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[3]);   // child 2
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[5] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[5]);
      $this->assertTrue($rel->can($idr[5],'r'));
      $this->assertTrue($rel->can($idr[5],'w'));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[6] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[6]);
      $this->assertTrue($rel->can($idr[6],'r'));
      $this->assertTrue($rel->can($idr[6],'w'));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[2]);   // Child 1
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[7] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[7]);
      $this->assertTrue($rel->can($idr[7],'r'));
      $this->assertTrue($rel->can($idr[7],'w'));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[3]);   // Child 2
      $this->assertFalse($rel->can(-1,'w',$input));

      // See only in child entity 2 + parent if recursive
      $this->assertTrue(changeActiveEntities($ent2));

      $this->assertFalse($rel->can($idr[0],'r'));  // root / root
      $this->assertFalse($rel->can($idr[0],'w'));
      $this->assertFalse($rel->can($idr[1],'r'));  // root / root rec
      $this->assertFalse($rel->can($idr[1],'w'));
      $this->assertFalse($rel->can($idr[2],'r'));  // root rec / root
      $this->assertFalse($rel->can($idr[2],'w'));
      $this->assertTrue($rel->can($idr[3],'r'));   // root rec / root rec
      $this->assertFalse($rel->can($idr[3],'w'));
      $this->assertFalse($rel->can($idr[4],'r'));  // root rec / child 1
      $this->assertFalse($rel->can($idr[4],'w'));
      $this->assertTrue($rel->can($idr[5],'r'));   // root rec / child 2
      $this->assertTrue($rel->can($idr[5],'w'));
      $this->assertFalse($rel->can($idr[6],'r'));  // child 1 / root rec
      $this->assertFalse($rel->can($idr[6],'w'));
      $this->assertFalse($rel->can($idr[7],'r'));  // child 1 / child 1
      $this->assertFalse($rel->can($idr[7],'w'));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[7] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[7]);
      $this->assertTrue($rel->can($idr[7],'r'));
      $this->assertTrue($rel->can($idr[7],'w'));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[2]);   // Child 1
      $this->assertFalse($rel->can(-1,'w',$input));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[3]);   // Child 3
      $this->assertTrue($rel->can(-1,'w',$input));
      $idr[8] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[8]);
      $this->assertTrue($rel->can($idr[8],'r'));
      $this->assertTrue($rel->can($idr[8],'w'));
   }
}
?>
