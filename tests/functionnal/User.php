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

/* Test for inc/user.class.php */

class User extends \DbTestCase {

   public function beforeTestMethod($method) {
      global $DB;
      $DB->beginTransaction();
      parent::beforeTestMethod($method);
   }

   public function afterTestMethod($method) {
      global $DB;
      $DB->rollback();
      parent::afterTestMethod($method);
   }

   public function testGenerateUserToken() {
      $user = getItemByTypeName('User', TU_USER);
      $this->variable($user->fields['personal_token_date'])->isNull();
      $this->variable($user->fields['personal_token'])->isNull();

      $token = $user->getAuthToken();
      $this->string($token)->isNotEmpty();

      $user->getFromDB($user->getID());
      $this->string($user->fields['personal_token'])->isIdenticalTo($token);
      $this->string($user->fields['personal_token_date'])->isIdenticalTo($_SESSION['glpi_currenttime']);
   }

   /**
    *
    */
   public function testLostPassword() {
      $user = getItemByTypeName('User', TU_USER);

      // Test request for a password with invalid email
      $this->exception(
         function() use ($user) {
            $user->forgetPassword('this-email-does-not-exists@example.com');
         }
      )
         ->isInstanceOf(\Glpi\Exception\ForgetPasswordException::class);

      // Test request for a password
      $result = $user->forgetPassword($user->getDefaultEmail());
      $this->boolean($result)->isTrue();

      // Test reset password with a bad token
      $token = $user->getField('password_forget_token');
      $input = [
         'email' => $user->getDefaultEmail(),
         'password_forget_token' => $token . 'bad',
         'password'  => TU_PASS,
         'password2' => TU_PASS
      ];
      $this->exception(
         function() use ($user, $input) {
            $result = $user->updateForgottenPassword($input);
         }
      )
      ->isInstanceOf(\Glpi\Exception\ForgetPasswordException::class);

      // Test reset password with good token
      // 1 - Refresh the in-memory instance of user and get the current password
      $user->getFromDB($user->getID());

      // 2 - Set a new password
      $input = [
         'email' => $user->getDefaultEmail(),
         'password_forget_token' => $token,
         'password'  => 'NewPassword',
         'password2' => 'NewPassword'
      ];

      // 3 - check the update succeeds
      $result = $user->updateForgottenPassword($input);
      $this->boolean($result)->isTrue();
      $newHash = $user->getField('password');

      // 4 - Restore the initial password in the DB before checking he updated password
      // This ensure the original password is restored even if the next test fails
      $updateSuccess = $user->update([
         'id'        => $user->getID(),
         'password'  => TU_PASS,
         'password2' => TU_PASS
      ]);
      $this->variable($updateSuccess)->isNotFalse('password update failed');

      // Test the new password was saved
      $this->variable(\Auth::checkPassword('NewPassword', $newHash))->isNotFalse();
   }

   public function testGetDefaultEmail() {
      $user = new \User();

      $this->string($user->getDefaultEmail())->isIdenticalTo('');
      $this->array($user->getAllEmails())->isIdenticalTo([]);
      $this->boolean($user->isEmail('one@test.com'))->isFalse();

      $uid = (int)$user->add([
         'name'   => 'test_email',
         '_useremails'  => [
            'one@test.com'
         ]
      ]);
      $this->integer($uid)->isGreaterThan(0);
      $this->boolean($user->getFromDB($user->fields['id']))->isTrue();
      $this->string($user->getDefaultEmail())->isIdenticalTo('one@test.com');

      $this->boolean(
         $user->update([
            'id'              => $uid,
            '_useremails'     => ['two@test.com'],
            '_default_email'  => 0
         ])
      )->isTrue();

      $this->boolean($user->getFromDB($user->fields['id']))->isTrue();
      $this->string($user->getDefaultEmail())->isIdenticalTo('two@test.com');

      $this->array($user->getAllEmails())->hasSize(2);
      $this->boolean($user->isEmail('one@test.com'))->isTrue();

      $tu_user = getItemByTypeName('User', TU_USER);
      $this->boolean($user->isEmail($tu_user->getDefaultEmail()))->isFalse();
   }

   public function testGetFromDBbyToken() {
      $user = $this->newTestedInstance;
      $uid = (int)$user->add([
         'name'   => 'test_token'
      ]);
      $this->integer($uid)->isGreaterThan(0);
      $this->boolean($user->getFromDB($uid))->isTrue();

      $token = $user->getToken($uid);
      $this->boolean($user->getFromDB($uid))->isTrue();
      $this->string($token)->hasLength(40);

      $user2 = new \User();
      $this->boolean($user2->getFromDBbyToken($token))->isTrue();
      $this->array($user2->fields)->isIdenticalTo($user->fields);

      $this->exception(
         function () use ($uid) {
            $this->testedInstance->getFromDBbyToken($uid, 'my_field');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('User::getFromDBbyToken() can only be called with $field parameter with theses values: \'personal_token\', \'api_token\'');
   }

   public function testPrepareInputForAdd() {
      $this->login();
      $user = $this->newTestedInstance();

      $input = [
         'name'   => 'prepare_for_add'
      ];
      $expected = [
         'name'         => 'prepare_for_add',
         'authtype'     => 1,
         'auths_id'     => 0,
         'is_active'    => 1,
         'is_deleted'   => 0,
         'entities_id'  => 0,
         'profiles_id'  => 0
      ];

      $this->array($user->prepareInputForAdd($input))->isIdenticalTo($expected);

      $input['_stop_import'] = 1;
      $this->boolean($user->prepareInputForAdd($input))->isFalse();

      $input = ['name' => 'invalid+login'];
      $this->boolean($user->prepareInputForAdd($input))->isFalse();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([ERROR => ['The login is not valid. Unable to add the user.']]);
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

      //add same user twice
      $input = ['name' => 'new_user'];
      $this->integer($user->add($input))->isGreaterThan(0);
      $this->boolean($user->add($input))->isFalse(0);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([ERROR => ['Unable to add. The user already exists.']]);
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

      $input = [
         'name'      => 'user_pass',
         'password'  => 'password',
         'password2' => 'nomatch'
      ];
      $this->boolean($user->prepareInputForAdd($input))->isFalse();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([ERROR => ['Error: the two passwords do not match']]);
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

      $input = [
         'name'      => 'user_pass',
         'password'  => '',
         'password2' => 'nomatch'
      ];
      $expected = [
         'name'         => 'user_pass',
         'password2'    => 'nomatch',
         'authtype'     => 1,
         'auths_id'     => 0,
         'is_active'    => 1,
         'is_deleted'   => 0,
         'entities_id'  => 0,
         'profiles_id'  => 0
      ];
      $this->array($user->prepareInputForAdd($input))->isIdenticalTo($expected);

      $input['password'] = 'nomatch';
      $expected['password'] = 'unknonwn';
      unset($expected['password2']);
      $prepared = $user->prepareInputForAdd($input);
      $this->array($prepared)
         ->hasKeys(array_keys($expected))
         ->string['password']->hasLength(60)->startWith('$2y$');

      $input['password'] = 'mypass';
      $input['password2'] = 'mypass';
      $input['_extauth'] = 1;
      $expected = [
         'name'         => 'user_pass',
         'password'     => '',
         '_extauth'     => 1,
         'authtype'     => 1,
         'auths_id'     => 0,
         'is_active'    => 1,
         'is_deleted'   => 0,
         'entities_id'  => 0,
         'profiles_id'  => 0,
      ];
      $this->array($user->prepareInputForAdd($input))->isIdenticalTo($expected);
   }

   public function testPost_addItem() {
      $this->login();
      $this->setEntity('_test_root_entity', true);
      $eid = getItemByTypeName('Entity', '_test_root_entity', true);

      $user = $this->newTestedInstance;

      //user with a profile
      $pid = getItemByTypeName('Profile', 'Technician', true);
      $uid = (int)$user->add([
         'name'         => 'create_user',
         '_profiles_id' => $pid
      ]);
      $this->integer($uid)->isGreaterThan(0);

      $this->boolean($user->getFromDB($uid))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('create_user')
         ->string['profiles_id']->isEqualTo(0);

      $puser = new \Profile_User();
      $this->boolean($puser->getFromDBByCrit(['users_id' => $uid]))->isTrue();
      $this->array($puser->fields)
         ->string['profiles_id']->isEqualTo($pid)
         ->string['entities_id']->isEqualTo($eid)
         ->string['is_recursive']->isEqualTo(0)
         ->string['is_dynamic']->isEqualTo(0);

      $pid = (int)\Profile::getDefault();
      $this->integer($pid)->isGreaterThan(0);

      //user without a profile (will take default one)
      $uid2 = (int)$user->add([
         'name' => 'create_user2',
      ]);
      $this->integer($uid2)->isGreaterThan(0);

      $this->boolean($user->getFromDB($uid2))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('create_user2')
         ->string['profiles_id']->isEqualTo(0);

      $puser = new \Profile_User();
      $this->boolean($puser->getFromDBByCrit(['users_id' => $uid2]))->isTrue();
      $this->array($puser->fields)
         ->string['profiles_id']->isEqualTo($pid)
         ->string['entities_id']->isEqualTo($eid)
         ->string['is_recursive']->isEqualTo(0)
         ->string['is_dynamic']->isEqualTo('1');

      //user with entity not recursive
      $eid2 = (int)getItemByTypeName('Entity', '_test_child_1', true);
      $this->integer($eid2)->isGreaterThan(0);
      $uid3 = (int)$user->add([
         'name'         => 'create_user3',
         '_entities_id' => $eid2
      ]);
      $this->integer($uid3)->isGreaterThan(0);

      $this->boolean($user->getFromDB($uid3))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('create_user3');

      $puser = new \Profile_User();
      $this->boolean($puser->getFromDBByCrit(['users_id' => $uid3]))->isTrue();
      $this->array($puser->fields)
         ->string['profiles_id']->isEqualTo($pid)
         ->string['entities_id']->isEqualTo($eid2)
         ->string['is_recursive']->isEqualTo(0)
         ->string['is_dynamic']->isEqualTo('1');

      //user with entity recursive
      $uid4 = (int)$user->add([
         'name'            => 'create_user4',
         '_entities_id'    => $eid2,
         '_is_recursive'   => 1
      ]);
      $this->integer($uid4)->isGreaterThan(0);

      $this->boolean($user->getFromDB($uid4))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('create_user4');

      $puser = new \Profile_User();
      $this->boolean($puser->getFromDBByCrit(['users_id' => $uid4]))->isTrue();
      $this->array($puser->fields)
         ->string['profiles_id']->isEqualTo($pid)
         ->string['entities_id']->isEqualTo($eid2)
         ->string['is_recursive']->isEqualTo(1)
         ->string['is_dynamic']->isEqualTo('1');

   }

   public function testGetFromDBbyDn() {
      $user = $this->newTestedInstance;
      $dn = 'user=user_with_dn,dc=test,dc=glpi-project,dc=org';

      $uid = (int)$user->add([
         'name'      => 'user_with_dn',
         'user_dn'   => $dn
      ]);
      $this->integer($uid)->isGreaterThan(0);

      $this->boolean($user->getFromDBbyDn($dn))->isTrue();
      $this->array($user->fields)
         ->string['id']->isIdenticalTo((string)$uid)
         ->string['name']->isIdenticalTo('user_with_dn');
   }

   public function testGetFromDBbySyncField() {
      $user = $this->newTestedInstance;
      $sync_field = 'abc-def-ghi';

      $uid = (int)$user->add([
         'name'         => 'user_with_syncfield',
         'sync_field'   => $sync_field
      ]);

      $this->integer($uid)->isGreaterThan(0);

      $this->boolean($user->getFromDBbySyncField($sync_field))->isTrue();
      $this->array($user->fields)
         ->string['id']->isIdenticalTo((string)$uid)
         ->string['name']->isIdenticalTo('user_with_syncfield');
   }

   public function testGetFromDBbyName() {
      $user = $this->newTestedInstance;
      $name = 'user_with_name';

      $uid = (int)$user->add([
         'name' => $name
      ]);

      $this->integer($uid)->isGreaterThan(0);

      $this->boolean($user->getFromDBbyName($name))->isTrue();
      $this->array($user->fields)
         ->string['id']->isIdenticalTo((string)$uid);
   }

   public function testGetFromDBbyNameAndAuth() {
      $user = $this->newTestedInstance;
      $name = 'user_with_auth';

      $uid = (int)$user->add([
         'name'      => $name,
         'authtype'  => \Auth::DB_GLPI,
         'auths_id'  => 12
      ]);

      $this->integer($uid)->isGreaterThan(0);

      $this->boolean($user->getFromDBbyNameAndAuth($name, \Auth::DB_GLPI, 12))->isTrue();
      $this->array($user->fields)
         ->string['id']->isIdenticalTo((string)$uid)
         ->string['name']->isIdenticalTo($name);
   }

   protected function rawNameProvider() {
      return [
         [
            'input'     => ['name' => 'myname'],
            'rawname'   => 'myname'
         ], [
            'input'     => [
               'name'      => 'anothername',
               'realname'  => 'real name'
            ],
            'rawname'      => 'real name'
         ], [
            'input'     => [
               'name'      => 'yet another name',
               'firstname' => 'first name'
            ],
            'rawname'   => 'yet another name'
         ], [
            'input'     => [
               'name'      => 'yet another one',
               'realname'  => 'real name',
               'firstname' => 'first name'
            ],
            'rawname'   => 'real name first name'
         ]
      ];
   }

   /**
    * @dataProvider rawNameProvider
    */
   public function testGetRawName($input, $rawname) {
      $user = $this->newTestedInstance;

      $this->string($user->getRawName())->isIdenticalTo('');

      $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($uid = (int)$this->testedInstance->add($input))
                  ->isGreaterThan(0)
               ->boolean($this->testedInstance->getFromDB($uid))->isTrue()
               ->string($this->testedInstance->getRawName())->isIdenticalTo($rawname);
   }

   public function testBlankPassword() {
      $input = [
         'name'      => 'myname',
         'password'  => 'mypass',
         'password2' => 'mypass'
      ];
      $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($uid = (int)$this->testedInstance->add($input))
                  ->isGreaterThan(0)
               ->boolean($this->testedInstance->getFromDB($uid))->isTrue()
               ->array($this->testedInstance->fields)
                  ->string['name']->isIdenticalTo('myname')
                  ->string['password']->hasLength(60)->startWith('$2y$')
         ->given($this->testedInstance->blankPassword())
            ->then
               ->boolean($this->testedInstance->getFromDB($uid))->isTrue()
               ->array($this->testedInstance->fields)
                  ->string['name']->isIdenticalTo('myname')
                  ->string['password']->isIdenticalTo('');
   }

   public function testPre_updateInDB() {
      $this->login();
      $user = $this->newTestedInstance();
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

      $uid = (int)$user->add([
         'name' => 'preupdate_user'
      ]);
      $this->integer($uid)->isGreaterThan(0);
      $this->boolean($user->getFromDB($uid))->isTrue();

      $this->boolean($user->update([
         'id'     => $uid,
         'name'   => 'preupdate_user_edited'
      ]))->isTrue();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([]);

      //can update with same name when id is identical
      $this->boolean($user->update([
         'id'     => $uid,
         'name'   => 'preupdate_user_edited'
      ]))->isTrue();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([]);

      $this->integer(
         (int)$user->add(['name' => 'do_exist'])
      )->isGreaterThan(0);
      $this->boolean($user->update([
         'id'     => $uid,
         'name'   => 'do_exist'
      ]))->isTrue();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([ERROR => ['Unable to update login. A user already exists.']]);
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

      $this->boolean($user->getFromDB($uid))->isTrue();
      $this->string($user->fields['name'])->isIdenticalTo('preupdate_user_edited');

      $this->boolean($user->update([
         'id'     => $uid,
         'name'   => 'in+valid'
      ]))->isTrue();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([ERROR => ['The login is not valid. Unable to update login.']]);
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
   }

   public function testGetIdByName() {
      $user = $this->newTestedInstance;

      $uid = (int)$user->add(['name' => 'id_by_name']);
      $this->integer($uid)->isGreaterThan(0);

      $this->integer($user->getIdByName('id_by_name'))->isIdenticalTo($uid);
   }

   public function testGetIdByField() {
      $user = $this->newTestedInstance;

      $uid = (int)$user->add([
         'name'   => 'id_by_field',
         'phone'  => '+33123456789'
      ]);
      $this->integer($uid)->isGreaterThan(0);

      $this->integer($user->getIdByField('phone', '+33123456789'))->isIdenticalTo($uid);

      $this->integer(
         $user->add([
            'name'   => 'id_by_field2',
            'phone'  => '+33123456789'
         ])
      )->isGreaterThan(0);
      $this->boolean($user->getIdByField('phone', '+33123456789'))->isFalse();

      $this->boolean($user->getIdByField('phone', 'donotexists'))->isFalse();
   }

   public function testgetAdditionalMenuOptions() {
      $this->Login();
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->getAdditionalMenuOptions())
                  ->hasSize(1)
                  ->hasKey('ldap');

      $this->Login('normal', 'normal');
      $this
         ->given($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->getAdditionalMenuOptions())
                  ->isFalse();
   }
}
