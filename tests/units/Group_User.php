<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/* Test for inc/ruleticket.class.php */

class Group_User extends DbTestCase {

   public function testIsUserInGroup() {
      $group = new \Group;
      // Add a group
      $groups_id = $group->add([
         'name' => __METHOD__,
         'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)]
      );
      $this->integer((int)$groups_id)->isGreaterThan(0);
      $this->boolean($group->getFromDB($groups_id))->isTrue();
      $this->variable($group->getField('is_deleted'))->isEqualTo(0);
      $this->variable($group->isDeleted())->isEqualTo(0);

      $group_user = new \Group_User;
      $group_users_id = $group_user->add([
         'groups_id'  => $groups_id,
         'users_id'   => getItemByTypeName('User', 'admin', true),
         'is_dynamic' => 0
      ]
      );
      $this->integer((int)$group_users_id)->isGreaterThan(0);
      $this->boolean($group_user->getFromDB($group_users_id))->isTrue();
      $this->boolean(\Group_User::isUserInGroup(getItemByTypeName('User', 'admin', true), $groups_id))->isTrue();
      $this->boolean(\Group_User::isUserInGroup(getItemByTypeName('User', 'glpi', true), $groups_id))->isFalse();
   }

}
