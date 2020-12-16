<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use DbTestCase;

/* Test for inc/change.class.php */

class Change extends DbTestCase {

   public function testAddFromItem() {
      // add change from a computer
      $computer   = getItemByTypeName('Computer', '_test_pc01');
      $change     = new \Change;
      $changes_id = $change->add([
         'name'           => "test add from computer \'_test_pc01\'",
         'content'        => "test add from computer \'_test_pc01\'",
         '_add_from_item' => true,
         '_from_itemtype' => 'Computer',
         '_from_items_id' => $computer->getID(),
      ]);
      $this->integer($changes_id)->isGreaterThan(0);
      $this->boolean($change->getFromDB($changes_id))->isTrue();

      // check relation
      $change_item = new \Change_Item;
      $this->boolean($change_item->getFromDBForItems($change, $computer))->isTrue();
   }

   public function testAssignFromCategory() {
      $this->login('glpi', 'glpi');
      $entity = new \Entity();
      $entityId = $entity->import([
         'name' => 'an entity configured to check change auto assignation of user ad group',
         'entities_id' => 0,
         'level' => 1,
         'auto_assign_mode' => \Entity::CONFIG_NEVER,
      ]);

      $this->boolean($entity->isNewID($entityId))->isFalse();
      $entity->getFromDB($entityId);
      $this->integer((int) $entity->fields['auto_assign_mode'])->isEqualTo(\Entity::CONFIG_NEVER);

      // Login again to acess the new entity
      $this->login('glpi', 'glpi');
      $success = \Session::changeActiveEntities($entity->getID(), true);
      $this->boolean($success)->isTrue();

      $group = new \Group();
      $group->add([
         'name' => 'A group to check automatic tech and group assignation',
         'entities_id' => 0,
         'is_recursive' => '1',
         'level' => 0,
      ]);
      $this->boolean($group->isNewItem())->isFalse();

      $itilCategory = new \ITILCategory();
      $itilCategory->add([
         'name' => 'A category to check automatic tech and group assignation',
         'itilcategories_id' => 0,
         'users_id' => 4, // Tech
         'groups_id' => $group->getID(),
      ]);
      $this->boolean($itilCategory->isNewItem())->isFalse();

      $change = new \Change();
      $change->add([
         'name' => 'A change to check if it is not automatically assigned user and group',
         'content' => 'foo',
         'itilcategories_id' => $itilCategory->getID(),
      ]);
      $this->boolean($change->isNewItem())->isFalse();
      $change->getFromDB($change->getID());
      $changeUser = new \Change_User();
      $changeGroup = new \Change_Group();
      $rows = $changeUser->find([
         'changes_id' => $change->getID(),
         'type'       => \CommonITILActor::ASSIGN,
      ]);
      $this->integer(count($rows))->isEqualTo(0);
      $rows = $changeGroup->find([
         'changes_id' => $change->getID(),
         'type'       => \CommonITILActor::ASSIGN,
      ]);

      // check Entity::AUTO_ASSIGN_HARDWARE_CATEGORY assignment
      $entity->update([
         'id' => $entity->getID(),
         'auto_assign_mode' => \Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
      ]);

      $change = new \Change();
      $change->add([
         'name' => 'A change to check if it is automatically assigned user and group (1)',
         'content' => 'foo',
         'itilcategories_id' => $itilCategory->getID(),
      ]);
      $this->boolean($change->isNewItem())->isFalse();
      $change->getFromDB($change->getID());
      $changeUser = new \Change_User();
      $changeGroup = new \Change_Group();
      $rows = $changeUser->find([
         'changes_id' => $change->getID(),
         'users_id'   => 4, // Tech
         'type'       => \CommonITILActor::ASSIGN,
      ]);
      $this->integer(count($rows))->isEqualTo(0);
      $rows = $changeGroup->find([
         'changes_id' => $change->getID(),
         'groups_id'  => $group->getID(),
         'type'       => \CommonITILActor::ASSIGN,
      ]);

      // check Entity::AUTO_ASSIGN_CATEGORY_HARDWARE assignment
      $entity->update([
         'id' => $entity->getID(),
         'auto_assign_mode' => \Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
      ]);

      $change = new \Change();
      $change->add([
         'name' => 'A change to check if it is automatically assigned user and group (2)',
         'content' => 'foo',
         'itilcategories_id' => $itilCategory->getID(),
      ]);
      $this->boolean($change->isNewItem())->isFalse();
      $change->getFromDB($change->getID());
      $changeUser = new \Change_User();
      $changeGroup = new \Change_Group();
      $rows = $changeUser->find([
         'changes_id' => $change->getID(),
         'users_id'   => 4, // Tech
         'type'       => \CommonITILActor::ASSIGN,
      ]);
      $this->integer(count($rows))->isEqualTo(0);
      $rows = $changeGroup->find([
         'changes_id' => $change->getID(),
         'groups_id'  => $group->getID(),
         'type'       => \CommonITILActor::ASSIGN,
      ]);
   }
}
