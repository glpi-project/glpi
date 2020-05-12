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

namespace tests\units\Glpi\Dashboard;

/* Test for inc/dashboard/dashboard.class.php */

class Dashboard extends \GLPITestCase {

   public function testConvertRights() {
      $raw = [
         [
            'itemtype'                 => 'Entity',
            'items_id'                 => 0,
         ], [
            'itemtype'                 => 'Profile',
            'items_id'                 => 3,
         ], [
            'itemtype'                 => 'Profile',
            'items_id'                 => 4,
         ], [
            'itemtype'                 => 'User',
            'items_id'                 => 2,
         ]
      ];

      $this->array(\Glpi\Dashboard\Dashboard::convertRights($raw))->isEqualTo([
         'entities_id' => [0],
         'profiles_id' => [3, 4],
         'users_id'    => [2],
         'groups_id'   => [],
      ]);
   }


   public function testCheckRights() {
      $rights = [
         'entities_id' => [0],
         'profiles_id' => [3 => 3, 4 => 4],
         'users_id'    => [2],
         'groups_id'   => [3],
      ];

      $_SESSION['glpiactiveentities'] = [];
      $_SESSION['glpiprofiles'] = [];
      $_SESSION['glpigroups'] = [];
      $_SESSION['glpiID'] = 1;

      $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isFalse();

      $_SESSION['glpiactiveentities'] = [0];
      $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

      $_SESSION['glpiactiveentities'] = [];
      $_SESSION['glpiprofiles'] = [3 => 3];
      $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

      $_SESSION['glpiprofiles'] = [];
      $_SESSION['glpiID'] = 2;
      $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

      $_SESSION['glpiID'] = 1;
      $_SESSION['glpigroups'] = [3];
      $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

      $_SESSION['glpigroups'] = [];
      $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isFalse();
   }
}