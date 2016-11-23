<?php
/*
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

/* Test for inc/knowbaseitem_item.class.php */

class KnowbaseItem_ItemTest extends DbTestCase {

   /**
    * @covers KnowbaseItem_Item::getTypeName()
    */
   public function testGetTypeName() {
      $expected = 'Knowledge base item';
      $this->assertEquals($expected, KnowbaseItem_Item::getTypeName(1));

      $expected = 'Knowledge base items';
      $this->assertEquals($expected, KnowbaseItem_Item::getTypeName(0));
      $this->assertEquals($expected, KnowbaseItem_Item::getTypeName(2));
      $this->assertEquals($expected, KnowbaseItem_Item::getTypeName(10));
   }

   /**
    * @covers KnowbaseItem_Item::getItems()
    */
   public function testGetItemsFromKB() {
      $kb1 = getItemByTypeName('KnowbaseItem', '_knowbaseitem01');
      $items = KnowbaseItem_Item::getItems($kb1);
      $this->assertCount(3, $items);

      $expecteds = [
         0 => [
            'id'       => '_ticket01',
            'itemtype' => Ticket::getType(),
         ],
         1 => [
            'id'       => '_ticket02',
            'itemtype' => Ticket::getType(),
         ],
         2 => [
            'id'       => '_ticket03',
            'itemtype' => Ticket::getType(),
         ]
      ];

      foreach ($expecteds as $key => $expected) {
         $item = getItemByTypeName($expected['itemtype'], $expected['id']);
         $this->assertInstanceOf($expected['itemtype'], $item);
      }

      //add start & limit
      $kb1 = getItemByTypeName('KnowbaseItem', '_knowbaseitem01');
      $items = KnowbaseItem_Item::getItems($kb1, 1, 1);
      $this->assertCount(1, $items);

      $expecteds = [
         1 => [
            'id'       => '_ticket02',
            'itemtype' => Ticket::getType(),
         ]
      ];

      foreach ($expecteds as $key => $expected) {
         $item = getItemByTypeName($expected['itemtype'], $expected['id']);
         $this->assertInstanceOf($expected['itemtype'], $item);
      }

      $kb2 = getItemByTypeName('KnowbaseItem', '_knowbaseitem02');
      $items = KnowbaseItem_Item::getItems($kb2);
      $this->assertCount(2, $items);

      $expecteds = [
         0 => [
            'id'       => '_ticket03',
            'itemtype' => Ticket::getType(),
         ],
         1 => [
            'id'       => '_test_pc21',
            'itemtype' => Computer::getType(),
         ]
      ];

      foreach ($expecteds as $key => $expected) {
         $item = getItemByTypeName($expected['itemtype'], $expected['id']);
         $this->assertInstanceOf($expected['itemtype'], $item);
      }

      //add sql where clause
      $items = KnowbaseItem_Item::getItems($kb2, 0, 0, '`itemtype` = \'Computer\'');
      $this->assertCount(1, $items);

      $expecteds = [
         1 => [
            'id'       => '_test_pc21',
            'itemtype' => Computer::getType(),
         ]
      ];

      foreach ($expecteds as $key => $expected) {
         $item = getItemByTypeName($expected['itemtype'], $expected['id']);
         $this->assertInstanceOf($expected['itemtype'], $item);
      }
   }

   /**
    * @covers KnowbaseItem_Item::getItems()
    */
   public function testGetKbsFromItem() {
      $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
      $kbs = KnowbaseItem_Item::getItems($ticket3);
      $this->assertCount(2, $kbs);

      $kb_ids = [];
      foreach ($kbs as $kb) {
         $this->assertEquals($ticket3->getType(), $kb['itemtype']);
         $this->assertEquals($ticket3->getID(), $kb['items_id']);
         $kb_ids[] = $kb['knowbaseitems_id'];
      }

      //test get "used"
      $kbs = KnowbaseItem_Item::getItems($ticket3, 0, 0, '', true);
      $this->assertCount(2, $kbs);

      foreach ($kbs as $key => $kb) {
         $this->assertEquals($key, $kb);
         $this->assertTrue(in_array($key, $kb_ids));
      }

      $ticket1 = getItemByTypeName(Ticket::getType(), '_ticket01');
      $kbs = KnowbaseItem_Item::getItems($ticket1);
      $this->assertCount(1, $kbs);

      foreach ($kbs as $kb) {
         $this->assertEquals($ticket1->getType(), $kb['itemtype']);
         $this->assertEquals($ticket1->getID(), $kb['items_id']);
      }

      $computer21 = getItemByTypeName(Computer::getType(), '_test_pc21');
      $kbs = KnowbaseItem_Item::getItems($computer21);
      $this->assertCount(1, $kbs);

      foreach ($kbs as $kb) {
         $this->assertEquals($computer21->getType(), $kb['itemtype']);
         $this->assertEquals($computer21->getID(), $kb['items_id']);
      }

      //test with entitiesrestriction
      $_SESSION['glpishowallentities'] = 0;

      $entity = getItemByTypeName(Entity::getType(), '_test_root_entity');
      $_SESSION['glpiactiveentities_string'] = $entity->getID();

      $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
      $kbs = KnowbaseItem_Item::getItems($ticket3);
      $this->assertCount(0, $kbs);

      $entity = getItemByTypeName(Entity::getType(), '_test_child_1');
      $_SESSION['glpiactiveentities_string'] = $entity->getID();

      $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
      $kbs = KnowbaseItem_Item::getItems($ticket3);
      $this->assertCount(2, $kbs);

      $entity = getItemByTypeName(Entity::getType(), '_test_child_2');
      $_SESSION['glpiactiveentities_string'] = $entity->getID();

      $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
      $kbs = KnowbaseItem_Item::getItems($ticket3);
      $this->assertCount(0, $kbs);

      $_SESSION['glpishowallentities'] = 1;
      unset($_SESSION['glpiactiveentities_string']);
   }

   /**
    * @covers KnowbaseItem_Item::getTabNameForItem()
    */
   public function testGetTabNameForItem() {
       $kb_item = new KnowbaseItem_Item();
       $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

       $name = $kb_item->getTabNameForItem($kb1, true);
       $this->assertEquals('Linked items <sup class=\'tab_nb\'>3</sup>', $name);

       $_SESSION['glpishow_count_on_tabs'] = 1;
       $name = $kb_item->getTabNameForItem($kb1);
       $this->assertEquals('Linked items <sup class=\'tab_nb\'>3</sup>', $name);

       $_SESSION['glpishow_count_on_tabs'] = 0;
       $name = $kb_item->getTabNameForItem($kb1);
       $this->assertEquals('Linked items', $name);

       $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');

       $_SESSION['glpishow_count_on_tabs'] = 1;
       $name = $kb_item->getTabNameForItem($ticket3, true);
       $this->assertEquals('Knowledge base items <sup class=\'tab_nb\'>2</sup>', $name);

       $name = $kb_item->getTabNameForItem($ticket3);
       $this->assertEquals('Knowledge base items <sup class=\'tab_nb\'>2</sup>', $name);

       $_SESSION['glpishow_count_on_tabs'] = 0;
       $name = $kb_item->getTabNameForItem($ticket3);
       $this->assertEquals('Knowledge base items', $name);
   }
}
