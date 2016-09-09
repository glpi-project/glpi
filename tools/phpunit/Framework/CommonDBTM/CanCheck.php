<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */
class Framework_CommonDBTM_CanCheck extends PHPUnit_Framework_TestCase {

   /**
    * Check right on Recursive object
    */
   public function testPrinter() {
      global $DB;

      $DB->connect();

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      $printer = new Printer();

      $id[0] = $printer->add(array('name'         => "Printer 1",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 0));
      $this->assertGreaterThan(0, $id[0], "Fail to create Printer 1");

      $id[1] = $printer->add(array('name'         => "Printer 2",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $id[1], "Fail to create Printer 2");

      $id[2] = $printer->add(array('name'         => "Printer 3",
                                   'entities_id'  => $ent1,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $id[2], "Fail to create Ptiner 3");

      $id[3] = $printer->add(array('name'         => "Printer 4",
                                   'entities_id'  => $ent2));
      $this->assertGreaterThan(0, $id[3], "Fail to create Printer 4");

      // Super admin
      Session::changeProfile(4);
      $this->assertEquals(4, $_SESSION['glpiactiveprofile']['id']);
      $this->assertEquals(31, $_SESSION['glpiactiveprofile']['printer']);

      // See all
      $this->assertTrue(Session::changeActiveEntities("all"));

      $this->assertTrue($printer->can($id[0], READ), "Fail can read Printer 1");
      $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
      $this->assertTrue($printer->can($id[2], READ), "Fail can read Printer 3");
      $this->assertTrue($printer->can($id[3], READ), "Fail can read Printer 4");

      $this->assertTrue($printer->canEdit($id[0]), "Fail can write Printer 1");
      $this->assertTrue($printer->canEdit($id[1]), "Fail can write Printer 2");
      $this->assertTrue($printer->canEdit($id[2]), "Fail can write Printer 3");
      $this->assertTrue($printer->canEdit($id[3]), "Fail can write Printer 4");

      // See only in main entity
      $this->assertTrue(Session::changeActiveEntities($ent0));

      $this->assertTrue($printer->can($id[0], READ), "Fail can read Printer 1");
      $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
      $this->assertFalse($printer->can($id[2], READ), "Fail can't read Printer 3");
      $this->assertFalse($printer->can($id[3], READ), "Fail can't read Printer 1");

      $this->assertTrue($printer->canEdit($id[0]), "Fail can write Printer 1");
      $this->assertTrue($printer->canEdit($id[1]), "Fail can write Printer 2");
      $this->assertFalse($printer->canEdit($id[2]), "Fail can't write Printer 1");
      $this->assertFalse($printer->canEdit($id[3]), "Fail can't write Printer 1");

      // See only in child entity 1 + parent if recursive
      $this->assertTrue(Session::changeActiveEntities($ent1));

      $this->assertFalse($printer->can($id[0], READ), "Fail can't read Printer 1");
      $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
      $this->assertTrue($printer->can($id[2], READ), "Fail can read Printer 3");
      $this->assertFalse($printer->can($id[3], READ), "Fail can't read Printer 4");

      $this->assertFalse($printer->canEdit($id[0]), "Fail can't write Printer 1");
      $this->assertFalse($printer->canEdit($id[1]), "Fail can't write Printer 2");
      $this->assertTrue($printer->canEdit($id[2]), "Fail can write Printer 2");
      $this->assertFalse($printer->canEdit($id[3]), "Fail can't write Printer 2");

      // See only in child entity 2 + parent if recursive
      $this->assertTrue(Session::changeActiveEntities($ent2));

      $this->assertFalse($printer->can($id[0], READ), "Fail can't read Printer 1");
      $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
      $this->assertFalse($printer->can($id[2], READ), "Fail can't read Printer 3");
      $this->assertTrue($printer->can($id[3], READ), "Fail can read Printer 4");

      $this->assertFalse($printer->canEdit($id[0]), "Fail can't write Printer 1");
      $this->assertFalse($printer->canEdit($id[1]), "Fail can't write Printer 2");
      $this->assertFalse($printer->canEdit($id[2]), "Fail can't write Printer 3");
      $this->assertTrue($printer->canEdit($id[3]), "Fail can write Printer 4");
   }

   /**
    * Check right on CommonDBRelation object
    */
   public function testContact_Supplier() {
      global $DB;

      $DB->connect();

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      // Super admin
      Session::changeProfile(4);
      $this->assertEquals(4, $_SESSION['glpiactiveprofile']['id']);
      $this->assertEquals(31, $_SESSION['glpiactiveprofile']['contact_enterprise']);

      // See all
      $this->assertTrue(Session::changeActiveEntities("all"));

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
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[0] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[0]);
      $this->assertTrue($rel->can($idr[0], READ));
      $this->assertTrue($rel->canEdit($idr[0]));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[1] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[1]);
      $this->assertTrue($rel->can($idr[1], READ));
      $this->assertTrue($rel->canEdit($idr[1]));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[2]);   // child 1
      $this->assertFalse($rel->can(-1, CREATE, $input));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[3]);   // child 2
      $this->assertFalse($rel->can(-1, CREATE, $input));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[0]);   // root
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[2] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[2]);
      $this->assertTrue($rel->can($idr[2], READ));
      $this->assertTrue($rel->canEdit($idr[2]));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[3] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[3]);
      $this->assertTrue($rel->can($idr[3], READ));
      $this->assertTrue($rel->canEdit($idr[3]));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[2]);   // child 1
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[4] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[4]);
      $this->assertTrue($rel->can($idr[4], READ));
      $this->assertTrue($rel->canEdit($idr[4]));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[3]);   // child 2
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[5] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[5]);
      $this->assertTrue($rel->can($idr[5], READ));
      $this->assertTrue($rel->canEdit($idr[5]));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1, CREATE, $input));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[6] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[6]);
      $this->assertTrue($rel->can($idr[6], READ));
      $this->assertTrue($rel->canEdit($idr[6]));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[2]);   // Child 1
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[7] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[7]);
      $this->assertTrue($rel->can($idr[7], READ));
      $this->assertTrue($rel->canEdit($idr[7]));

      $input = array('contacts_id' =>  $idc[2],    // Child 1
                     'suppliers_id' => $ids[3]);   // Child 2
      $this->assertFalse($rel->can(-1, CREATE, $input));

      // See only in child entity 2 + parent if recursive
      $this->assertTrue(Session::changeActiveEntities($ent2));

      $this->assertFalse($rel->can($idr[0], READ));  // root / root
      $this->assertFalse($rel->canEdit($idr[0]));
      $this->assertFalse($rel->can($idr[1], READ));  // root / root rec
      $this->assertFalse($rel->canEdit($idr[1]));
      $this->assertFalse($rel->can($idr[2], READ));  // root rec / root
      $this->assertFalse($rel->canEdit($idr[2]));
      $this->assertTrue($rel->can($idr[3], READ));   // root rec / root rec
      $this->assertFalse($rel->canEdit($idr[3]));
      $this->assertFalse($rel->can($idr[4], READ));  // root rec / child 1
      $this->assertFalse($rel->canEdit($idr[4]));
      $this->assertTrue($rel->can($idr[5], READ));   // root rec / child 2
      $this->assertTrue($rel->canEdit($idr[5]));
      $this->assertFalse($rel->can($idr[6], READ));  // child 1 / root rec
      $this->assertFalse($rel->canEdit($idr[6]));
      $this->assertFalse($rel->can($idr[7], READ));  // child 1 / child 1
      $this->assertFalse($rel->canEdit($idr[7]));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1, CREATE, $input));

      $input = array('contacts_id' =>  $idc[0],    // root
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertFalse($rel->can(-1, CREATE, $input));

      $input = array('contacts_id' =>  $idc[1],    // root + rec
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1, CREATE, $input));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[0]);   // root
      $this->assertFalse($rel->can(-1, CREATE, $input));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[1]);   // root + rec
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[7] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[7]);
      $this->assertTrue($rel->can($idr[7], READ));
      $this->assertTrue($rel->canEdit($idr[7]));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[2]);   // Child 1
      $this->assertFalse($rel->can(-1, CREATE,$input));

      $input = array('contacts_id' =>  $idc[3],    // Child 2
                     'suppliers_id' => $ids[3]);   // Child 3
      $this->assertTrue($rel->can(-1, CREATE, $input));
      $idr[8] = $rel->add($input);
      $this->assertGreaterThan(0, $idr[8]);
      $this->assertTrue($rel->can($idr[8], READ));
      $this->assertTrue($rel->canEdit($idr[8]));
   }

   /**
    * Entity right check
    */
   public function testEntity() {
      global $DB;

      $DB->connect();

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];
      $ent3 = $this->sharedFixture['entity'][3];
      $ent4 = $this->sharedFixture['entity'][4];

      $entity = new Entity();

      $this->assertTrue(Session::changeActiveEntities("all"));

      $this->assertTrue($entity->can(0, READ), "Fail: can't read root entity");
      $this->assertTrue($entity->can($ent0, READ), "Fail: can't read entity 0");
      $this->assertTrue($entity->can($ent1, READ), "Fail: can't read entity 1");
      $this->assertTrue($entity->can($ent2, READ), "Fail: can't read entity 2");
      $this->assertTrue($entity->can($ent3, READ), "Fail: can't read entity 2.1");
      $this->assertTrue($entity->can($ent4, READ), "Fail: can't read entity 2.2");

      $this->assertTrue($entity->canEdit(0), "Fail: can't write root entity");
      $this->assertTrue($entity->canEdit($ent0), "Fail: can't write entity 0");
      $this->assertTrue($entity->canEdit($ent1), "Fail: can't write entity 1");
      $this->assertTrue($entity->canEdit($ent2), "Fail: can't write entity 2");
      $this->assertTrue($entity->canEdit($ent3), "Fail: can't write entity 2.1");
      $this->assertTrue($entity->canEdit($ent4), "Fail: can't write entity 2.2");

      $input=array('entities_id' => $ent1);
      $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can create entity in root");
      $input=array('entities_id' => $ent2);
      $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2");
      $input=array('entities_id' => $ent3);
      $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2.1");
      $input=array('entities_id' => 99999);
      $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in not existing entity");
      $input=array('entities_id' => -1);
      $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in not existing entity");

      $this->assertTrue(Session::changeActiveEntities($ent2,true));

      $this->assertTrue($entity->can(0, READ), "Fail: can't read root entity");
      $this->assertTrue($entity->can($ent0, READ), "Fail: can't read entity 0");
      $this->assertFalse($entity->can($ent1, READ), "Fail: can read entity 1");
      $this->assertTrue($entity->can($ent2, READ), "Fail: can't read entity 2");
      $this->assertTrue($entity->can($ent3, READ), "Fail: can't read entity 2.1");
      $this->assertTrue($entity->can($ent4, READ), "Fail: can't read entity 2.2");
      $this->assertFalse($entity->can(99999, READ), "Fail: can read not existing entity");

      $this->assertFalse($entity->canEdit(0), "Fail: can write root entity");
      $this->assertFalse($entity->canEdit($ent0), "Fail: can write entity 0");
      $this->assertFalse($entity->canEdit($ent1), "Fail: can write entity 1");
      $this->assertTrue($entity->canEdit($ent2), "Fail: can't write entity 2");
      $this->assertTrue($entity->canEdit($ent3), "Fail: can't write entity 2.1");
      $this->assertTrue($entity->canEdit($ent4), "Fail: can't write entity 2.2");
      $this->assertFalse($entity->canEdit(99999), "Fail: can write not existing entity");

      $input=array('entities_id' => $ent1);
      $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in root");
      $input=array('entities_id' => $ent2);
      $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2");
      $input=array('entities_id' => $ent3);
      $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2.1");
      $input=array('entities_id' => 99999);
      $this->assertFalse($entity->can(-1, CREATE, $input),
                         "Fail: can create entity in not existing entity");
      $input=array('entities_id' => -1);
      $this->assertFalse($entity->can(-1, CREATE, $input),
                         "Fail: can create entity in not existing entity");

      $this->assertTrue(Session::changeActiveEntities($ent2,false));
      $input=array('entities_id' => $ent1);
      $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in root");
      $input=array('entities_id' => $ent2);
      // next should be false (or not).... but check is done on glpiactiveprofile
      // will require to save current state in session - this is probably acceptable
      // this allow creation when no child defined yet (no way to select tree in this case)
      $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2");
      $input=array('entities_id' => $ent3);
      $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in 2.1");
   }
}
?>
