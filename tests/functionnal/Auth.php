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

/* Test for inc/auth.class.php */

class Auth extends DbTestCase {

   protected function loginProvider() {
      return [
         ['john', 1],
         ['john doe', 1],
         ['john_doe', 1],
         ['john-doe', 1],
         ['john.doe', 1],
         ['john \'o doe', 1],
         ['john@doe.com', 1],
         ['@doe.com', 1],
         ['john " doe', 0],
         ['john^doe', 0],
         ['john$doe', 0],
         [null, 0],
         ['', 0]
      ];
   }

   /**
    * @dataProvider loginProvider
    */
   public function testIsValidLogin($login, $isvalid) {
      $this->variable(\Auth::isValidLogin($login))->isIdenticalTo($isvalid);
   }

   public function testGetLoginAuthMethods() {
      $methods = \Auth::getLoginAuthMethods();
      $expected = [
         '_default'  => 'local',
         'local'     => 'GLPI internal database'
      ];
      $this->array($methods)->isIdenticalTo($expected);
   }

   /**
    * Provides data to test account lock strategy on password expiration.
    *
    * @return array
    */
   protected function lockStrategyProvider() {
      $tests = [];

      // test with no password expiration
      $tests[] = [
         'last_update'   => date('Y-m-d H:i:s', strtotime('-10 years')),
         'exp_delay'     => -1,
         'lock_delay'    => -1,
         'expected_lock' => false,
      ];

      // tests with no lock on password expiration
      $cases = [
         '-5 days'  => false,
         '-30 days' => false,
      ];
      foreach ($cases as $last_update => $expected_lock) {
         $tests[] = [
            'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
            'exp_delay'     => 15,
            'lock_delay'    => -1,
            'expected_lock' => $expected_lock,
         ];
      }

      // tests with immediate lock on password expiration
      $cases = [
         '-5 days'  => false,
         '-30 days' => true,
      ];
      foreach ($cases as $last_update => $expected_lock) {
         $tests[] = [
            'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
            'exp_delay'     => 15,
            'lock_delay'    => 0,
            'expected_lock' => $expected_lock,
         ];
      }

      // tests with delayed lock on password expiration
      $cases = [
         '-5 days'  => false,
         '-20 days' => false,
         '-30 days' => true,
      ];
      foreach ($cases as $last_update => $expected_lock) {
         $tests[] = [
            'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
            'exp_delay'     => 15,
            'lock_delay'    => 10,
            'expected_lock' => $expected_lock,
         ];
      }

      return $tests;
   }

   /**
    * Test that account is lock when authentication is done using an expired password.
    *
    * @dataProvider lockStrategyProvider
    */
   public function testAccountLockStrategy(string $last_update, int $exp_delay, int $lock_delay, bool $expected_lock) {
      global $CFG_GLPI;

      // reset session to prevent session having less rights to create a user
      $this->login();

      $user = new \User();
      $username = 'test_lock_' . mt_rand();
      $user_id = (int) $user->add([
         'name'         => $username,
         'password'     => 'test',
         'password2'    => 'test',
         '_profiles_id' => 1,
      ]);
      $this->integer($user_id)->isGreaterThan(0);
      $this->boolean($user->update(['id' => $user_id, 'password_last_update' => $last_update]))->isTrue();

      $cfg_backup = $CFG_GLPI;
      $CFG_GLPI['password_expiration_delay'] = $exp_delay;
      $CFG_GLPI['password_expiration_lock_delay'] = $lock_delay;
      $auth = new \Auth();
      $is_logged = $auth->login($username, 'test', true);
      $CFG_GLPI = $cfg_backup;

      $this->boolean($is_logged)->isEqualTo(!$expected_lock);
      $this->boolean($user->getFromDB($user->fields['id']))->isTrue();
      $this->boolean((bool)$user->fields['is_active'])->isEqualTo(!$expected_lock);
   }
}
