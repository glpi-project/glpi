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

/* Test for inc/profile.class.php */

class Profile extends DbTestCase {

   /**
    * @see self::testHaveUserRight()
    *
    * @return array
    */
   protected function haveUserRightProvider() {

      return [
         [
            'user'      => [
               'login'    => 'post-only',
               'password' => 'postonly',
            ],
            'rights' => [
               ['name' => \Computer::$rightname, 'value' => CREATE, 'expected' => false],
               ['name' => \Computer::$rightname, 'value' => DELETE, 'expected' => false],
               ['name' => \Ticket::$rightname, 'value' => CREATE, 'expected' => true],
               ['name' => \Ticket::$rightname, 'value' => DELETE, 'expected' => false],
               ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMYTICKET, 'expected' => true],
               ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLTICKET, 'expected' => false],
            ],
         ],
         [
            'user'      => [
               'login'    => 'glpi',
               'password' => 'glpi',
            ],
            'rights' => [
               ['name' => \Computer::$rightname, 'value' => CREATE, 'expected' => true],
               ['name' => \Computer::$rightname, 'value' => DELETE, 'expected' => true],
               ['name' => \Ticket::$rightname, 'value' => CREATE, 'expected' => true],
               ['name' => \Ticket::$rightname, 'value' => DELETE, 'expected' => true],
               ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMYTICKET, 'expected' => true],
               ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLTICKET, 'expected' => true],
            ],
         ],
         [
            'user'      => [
               'login'    => 'tech',
               'password' => 'tech',
            ],
            'rights' => [
               ['name' => \Computer::$rightname, 'value' => CREATE, 'expected' => true],
               ['name' => \Computer::$rightname, 'value' => DELETE, 'expected' => true],
               ['name' => \Ticket::$rightname, 'value' => CREATE, 'expected' => true],
               ['name' => \Ticket::$rightname, 'value' => DELETE, 'expected' => false],
               ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMYTICKET, 'expected' => true],
               ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLTICKET, 'expected' => true],
            ],
         ],
      ];
   }

   /**
    * Tests user rights checking.
    *
    * @param array   $user     Array containing 'login' and 'password' fields of tested user.
    * @param array   $rightset Array of arrays containing 'name', 'value' and 'expected' result of a right.
    *
    * @dataProvider haveUserRightProvider
    */
   public function testHaveUserRight(array $user, array $rightset) {

      $this->login($user['login'], $user['password']);

      foreach ($rightset as $rightdata) {
         $result = \Profile::haveUserRight(
            \Session::getLoginUserID(),
            $rightdata['name'],
            $rightdata['value'],
            0
         );
         $this->boolean($result)
            ->isEqualTo(
               $rightdata['expected'],
               sprintf('Unexpected result for value "%d" of "%s" right.', $rightdata['value'], $rightdata['name'])
            );
      }
   }
}
