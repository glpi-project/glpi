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

/* Test for inc/knowbaseitem.class.php */

class KnowbaseItem extends DbTestCase {

   public function testGetTypeName() {
      $expected = 'Knowledge base';
      $this->string(\KnowbaseItem::getTypeName(1))->isIdenticalTo($expected);

      $expected = 'Knowledge base';
      $this->string(\KnowbaseItem::getTypeName(0))->isIdenticalTo($expected);
      $this->string(\KnowbaseItem::getTypeName(2))->isIdenticalTo($expected);
      $this->string(\KnowbaseItem::getTypeName(10))->isIdenticalTo($expected);
   }

   public function testCleanDBonPurge() {
      global $DB;

      $users_id = getItemByTypeName('User', TU_USER, true);

      $kb = new \KnowbaseItem();
      $this->integer(
         (int)$kb->add([
            'name'     => 'Test to remove',
            'answer'   => 'An KB entry to remove',
            'is_faq'   => 0,
            'users_id' => $users_id,
            'date'     => '2017-10-06 12:27:48',
         ])
      )->isGreaterThan(0);

      //add some comments
      $comment = new \KnowbaseItem_Comment();
      $input = [
         'knowbaseitems_id' => $kb->getID(),
         'users_id'         => $users_id
      ];

      $id = 0;
      for ($i = 0; $i < 4; ++$i) {
         $input['comment'] = "Comment $i";
         $this->integer(
            (int)$comment->add($input)
         )->isGreaterThan($id);
         $id = (int)$comment->getID();
      }

      //change KB entry
      $this->boolean(
         $kb->update([
            'id'     => $kb->getID(),
            'answer' => 'Answer has changed'
         ])
      )->isTrue();

      //add an user
      $kbu = new \KnowbaseItem_User();
      $this->integer(
         (int)$kbu->add([
            'knowbaseitems_id'   => $kb->getID(),
            'users_id'           => $users_id
         ])
      )->isGreaterThan(0);

      //add an entity
      $kbe = new \Entity_KnowbaseItem();
      $this->integer(
         (int)$kbe->add([
            'knowbaseitems_id'   => $kb->getID(),
            'entities_id'        => 0
         ])
      )->isGreaterThan(0);

      //add a group
      $group = new \Group();
      $this->integer(
         (int)$group->add([
            'name'   => 'KB group'
         ])
      )->isGreaterThan(0);
      $kbg = new \Group_KnowbaseItem();
      $this->integer(
         (int)$kbg->add([
            'knowbaseitems_id'   => $kb->getID(),
            'groups_id'          => $group->getID()
         ])
      )->isGreaterThan(0);

      //add a profile
      $profiles_id = getItemByTypeName('Profile', 'Admin', true);
      $kbp = new \KnowbaseItem_Profile();
      $this->integer(
         (int)$kbp->add([
            'knowbaseitems_id'   => $kb->getID(),
            'profiles_id'        => $profiles_id
         ])
      )->isGreaterThan(0);

      //add an item
      $kbi = new \KnowbaseItem_Item();
      $tickets_id = getItemByTypeName('Ticket', '_ticket01', true);
      $this->integer(
         (int)$kbi->add([
            'knowbaseitems_id'   => $kb->getID(),
            'itemtype'           => 'Ticket',
            'items_id'           => $tickets_id
         ])
      )->isGreaterThan(0);

      $relations = [
         $comment->getTable(),
         \KnowbaseItem_Revision::getTable(),
         \KnowbaseItem_User::getTable(),
         \Entity_KnowbaseItem::getTable(),
         \Group_KnowbaseItem::getTable(),
         \KnowbaseItem_Profile::getTable(),
         \KnowbaseItem_Item::getTable()
      ];

      //check all relations have been created
      foreach ($relations as $relation) {
         $iterator = $DB->request([
            'FROM'   => $relation,
            'WHERE'  => ['knowbaseitems_id' => $kb->getID()]
         ]);
         $this->integer(count($iterator))->isGreaterThan(0);
      }

      //remove KB entry
      $this->boolean(
         $kb->delete(['id' => $kb->getID()], true)
      )->isTrue();

      //check all relations has been removed
      foreach ($relations as $relation) {
         $iterator = $DB->request([
            'FROM'   => $relation,
            'WHERE'  => ['knowbaseitems_id' => $kb->getID()]
         ]);
         $this->integer(count($iterator))->isIdenticalTo(0);
      }
   }
}
