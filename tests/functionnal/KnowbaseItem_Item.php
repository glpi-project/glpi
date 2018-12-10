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

namespace test\units;

use \DbTestCase;

/* Test for inc/knowbaseitem_item.class.php */

class KnowbaseItem_Item extends DbTestCase {

   public function testGetTypeName() {
      $expected = 'Knowledge base item';
      $this->string(\KnowbaseItem_Item::getTypeName(1))->isIdenticalTo($expected);

      $expected = 'Knowledge base items';
      $this->string(\KnowbaseItem_Item::getTypeName(0))->isIdenticalTo($expected);
      $this->string(\KnowbaseItem_Item::getTypeName(2))->isIdenticalTo($expected);
      $this->string(\KnowbaseItem_Item::getTypeName(10))->isIdenticalTo($expected);
   }

   public function testGetItemsFromKB() {
      $this->login();
      $kb1 = getItemByTypeName('KnowbaseItem', '_knowbaseitem01');
      $items = \KnowbaseItem_Item::getItems($kb1);
      $this->array($items)->hasSize(3);

      $expecteds = [
         0 => [
            'id'       => '_ticket01',
            'itemtype' => \Ticket::getType(),
         ],
         1 => [
            'id'       => '_ticket02',
            'itemtype' => \Ticket::getType(),
         ],
         2 => [
            'id'       => '_ticket03',
            'itemtype' => \Ticket::getType(),
         ]
      ];

      foreach ($expecteds as $key => $expected) {
         $item = getItemByTypeName($expected['itemtype'], $expected['id']);
         $this->object($item)->isInstanceOf($expected['itemtype']);
      }

      //add start & limit
      $kb1 = getItemByTypeName('KnowbaseItem', '_knowbaseitem01');
      $items = \KnowbaseItem_Item::getItems($kb1, 1, 1);
      $this->array($items)->hasSize(1);

      $expecteds = [
         1 => [
            'id'       => '_ticket02',
            'itemtype' => \Ticket::getType(),
         ]
      ];

      foreach ($expecteds as $key => $expected) {
         $item = getItemByTypeName($expected['itemtype'], $expected['id']);
         $this->object($item)->isInstanceOf($expected['itemtype']);
      }

      $kb2 = getItemByTypeName('KnowbaseItem', '_knowbaseitem02');
      $items = \KnowbaseItem_Item::getItems($kb2);
      $this->array($items)->hasSize(2);

      $expecteds = [
         0 => [
            'id'       => '_ticket03',
            'itemtype' => \Ticket::getType(),
         ],
         1 => [
            'id'       => '_test_pc21',
            'itemtype' => \Computer::getType(),
         ]
      ];

      foreach ($expecteds as $key => $expected) {
         $item = getItemByTypeName($expected['itemtype'], $expected['id']);
         $this->object($item)->isInstanceOf($expected['itemtype']);
      }
   }

   public function testGetKbsFromItem() {
      $this->login();
      $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
      $kbs = \KnowbaseItem_Item::getItems($ticket3);
      $this->array($kbs)
         ->hasSize(2);

      $kb_ids = [];
      foreach ($kbs as $kb) {
         $this->array($kb)
            ->string['itemtype']->isIdenticalTo($ticket3->getType())
            ->string['items_id']->isIdenticalTo($ticket3->getID());

         $kb_ids[] = $kb['knowbaseitems_id'];
      }

      //test get "used"
      $kbs = \KnowbaseItem_Item::getItems($ticket3, 0, 0, '', true);
      $this->array($kbs)->hasSize(2);

      foreach ($kbs as $key => $kb) {
         $this->variable($kb)->isEqualTo($key);
         $this->array($kb_ids)->contains($key);
      }

      $ticket1 = getItemByTypeName(\Ticket::getType(), '_ticket01');
      $kbs = \KnowbaseItem_Item::getItems($ticket1);
      $this->array($kbs)->hasSize(1);

      foreach ($kbs as $kb) {
         $this->array($kb)
            ->string['itemtype']->isIdenticalTo($ticket1->getType())
            ->string['items_id']->isIdenticalTo($ticket1->getID());
      }

      $computer21 = getItemByTypeName(\Computer::getType(), '_test_pc21');
      $kbs = \KnowbaseItem_Item::getItems($computer21);
      $this->array($kbs)->hasSize(1);

      foreach ($kbs as $kb) {
         $this->array($kb)
            ->string['itemtype']->isIdenticalTo($computer21->getType())
            ->string['items_id']->isIdenticalTo($computer21->getID());
      }

      //test with entitiesrestriction
      $_SESSION['glpishowallentities'] = 0;

      $entity = getItemByTypeName(\Entity::getType(), '_test_root_entity');
      $_SESSION['glpiactiveentities'] = [$entity->getID()];

      $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
      $kbs = \KnowbaseItem_Item::getItems($ticket3);
      $this->array($kbs)->hasSize(0);

      $entity = getItemByTypeName(\Entity::getType(), '_test_child_1');
      $_SESSION['glpiactiveentities'] = [$entity->getID()];

      $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
      $kbs = \KnowbaseItem_Item::getItems($ticket3);
      $this->array($kbs)->hasSize(2);

      $entity = getItemByTypeName(\Entity::getType(), '_test_child_2');
      $_SESSION['glpiactiveentities'] = [$entity->getID()];

      $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
      $kbs = \KnowbaseItem_Item::getItems($ticket3);
      $this->array($kbs)->hasSize(0);

      $_SESSION['glpishowallentities'] = 1;
      unset($_SESSION['glpiactiveentities']);
   }

   public function testGetTabNameForItem() {
       $this->login();
       $kb_item = new \KnowbaseItem_Item();
       $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');

       $_SESSION['glpishow_count_on_tabs'] = 1;
       $name = $kb_item->getTabNameForItem($kb1);
       $this->string($name)->isIdenticalTo('Associated elements <sup class=\'tab_nb\'>3</sup>');

       $_SESSION['glpishow_count_on_tabs'] = 0;
       $name = $kb_item->getTabNameForItem($kb1);
       $this->string($name)->isIdenticalTo('Associated elements');

       $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');

       $_SESSION['glpishow_count_on_tabs'] = 1;
       $name = $kb_item->getTabNameForItem($ticket3, true);
       $this->string($name)->isIdenticalTo('Knowledge base <sup class=\'tab_nb\'>2</sup>');

       $name = $kb_item->getTabNameForItem($ticket3);
       $this->string($name)->isIdenticalTo('Knowledge base <sup class=\'tab_nb\'>2</sup>');

       $_SESSION['glpishow_count_on_tabs'] = 0;
       $name = $kb_item->getTabNameForItem($ticket3);
       $this->string($name)->isIdenticalTo('Knowledge base');
   }
}
