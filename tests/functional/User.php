<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Toolbox\Sanitizer;
use Profile_User;
use QuerySubQuery;

/* Test for inc/user.class.php */

class User extends \DbTestCase
{
    public function testGenerateUserToken()
    {
        $this->login(TU_USER, TU_PASS); // must be authenticated to be able to regenerate self personal token

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
    public function testLostPassword()
    {
        $user = getItemByTypeName('User', TU_USER);

        // Test request for a password with invalid email
        $this->when(
            function () use ($user) {
                $user->forgetPassword('this-email-does-not-exists@example.com');
            }
        )->error()
            ->withType(E_USER_WARNING)
            ->withMessage("Failed to find a single user for 'this-email-does-not-exists@example.com', 0 user(s) found.")
            ->exists();

        // Test request for a password
        $result = $user->forgetPassword($user->getDefaultEmail());
        $this->boolean($result)->isTrue();

        // Test reset password with a bad token
        $token = $user->fields['password_forget_token'];
        $this->string($token)->isNotEmpty();

        $input = [
            'password_forget_token' => $token . 'bad',
            'password'  => TU_PASS,
            'password2' => TU_PASS
        ];
        $this->exception(
            function () use ($user, $input) {
                $user->updateForgottenPassword($input);
            }
        )
        ->isInstanceOf(\Glpi\Exception\ForgetPasswordException::class);

        // Test reset password with good token
        // 1 - Refresh the in-memory instance of user and get the current password
        $user->getFromDB($user->getID());

        // 2 - Set a new password
        $input = [
            'password_forget_token' => $token,
            'password'  => 'NewPassword',
            'password2' => 'NewPassword'
        ];

        // 3 - check the update succeeds
        $result = $user->updateForgottenPassword($input);
        $this->boolean($result)->isTrue();
        $newHash = $user->fields['password'];

        // Test the new password was saved
        $this->variable(\Auth::checkPassword('NewPassword', $newHash))->isNotFalse();

        // Validates that password reset token has been removed
        $user = getItemByTypeName('User', TU_USER);
        $token = $user->fields['password_forget_token'];
        $this->string($token)->isEmpty();
    }

    public function testGetDefaultEmail()
    {
        $this->login(); // must be authenticated to update emails

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

    public function testUpdateEmail()
    {
        $this->login(); // must be authenticated to update emails

        // Create a user with some emails
        $user1 = new \User();
        $uid1 = (int)$user1->add([
            'name'   => 'test_email 1',
            '_useremails'  => [
                -1 => 'email1@test.com',
                -2 => 'email2@test.com',
                -3 => 'email3@test.com',
            ]
        ]);
        $this->integer($uid1)->isGreaterThan(0);

        // Emails are all attached to user 1
        $user1_email1_id = current(
            getAllDataFromTable(\UserEmail::getTable(), ['users_id' => $uid1, 'email' => 'email1@test.com'])
        )['id'] ?? 0;
        $this->integer($user1_email1_id)->isGreaterThan(0);

        $this->string($user1->getDefaultEmail())->isIdenticalTo('email1@test.com');

        $this->boolean($user1->getFromDB($uid1))->isTrue();
        $user1_emails = $user1->getAllEmails();
        asort($user1_emails);
        $this->array(array_values($user1_emails))->isEqualTo(
            [
                'email1@test.com',
                'email2@test.com',
                'email3@test.com',
            ]
        );

        // Create another user
        $user2 = new \User();
        $uid2 = (int)$user2->add([
            'name'   => 'test_email 2',
            '_useremails'  => [
                -1 => 'anotheremail1@test.com',
                $user1_email1_id => 'anotheremail2@test.com', // try to change email from user 1
                -3 => 'anotheremail3@test.com',
            ]
        ]);
        $this->integer($uid2)->isGreaterThan(0);

        // Emails are all attached to user 2
        $user2_email1_id = current(
            getAllDataFromTable(\UserEmail::getTable(), ['users_id' => $uid2, 'email' => 'anotheremail1@test.com'])
        )['id'] ?? 0;
        $this->integer($user2_email1_id)->isGreaterThan(0);

        $this->string($user2->getDefaultEmail())->isIdenticalTo('anotheremail1@test.com');

        $this->boolean($user2->getFromDB($uid2))->isTrue();
        $user2_emails = $user2->getAllEmails();
        asort($user2_emails);
        $this->array(array_values($user2_emails))->isEqualTo(
            [
                'anotheremail1@test.com',
                'anotheremail2@test.com',
                'anotheremail3@test.com',
            ]
        );

        // User 1 emails did not changed
        $this->boolean($user1->getFromDB($uid1))->isTrue();
        $user1_emails = $user1->getAllEmails();
        asort($user1_emails);
        $this->array(array_values($user1_emails))->isEqualTo(
            [
                'email1@test.com',
                'email2@test.com',
                'email3@test.com',
            ]
        );

        // Update the second user
        $update = $user2->update([
            'id'     => $uid2,
            '_useremails'  => [
                $user1_email1_id => 'email1-updated@test.com', // try to change email from user 1
                $user2_email1_id => 'anotheremail1-update@test.com',
            ],
            '_default_email' => $user1_email1_id,
        ]);
        $this->boolean($update)->isTrue();

        // Emails are all attached to user 2
        $this->boolean($user2->getFromDB($uid2))->isTrue();
        $user2_emails = $user2->getAllEmails();
        asort($user2_emails);
        $this->array(array_values($user2_emails))->isEqualTo(
            [
                'anotheremail1-update@test.com',
                'anotheremail2@test.com',
                'anotheremail3@test.com',
                'email1-updated@test.com',
            ]
        );

        $this->string($user2->getDefaultEmail())->isIdenticalTo('email1-updated@test.com');

        // User 1 emails did not changed
        $this->boolean($user1->getFromDB($uid1))->isTrue();
        $user1_emails = $user1->getAllEmails();
        asort($user1_emails);
        $this->array(array_values($user1_emails))->isEqualTo(
            [
                'email1@test.com',
                'email2@test.com',
                'email3@test.com',
            ]
        );
    }

    public function testGetFromDBbyToken()
    {
        $user = $this->newTestedInstance;
        $uid = (int)$user->add([
            'name'      => 'test_token',
            'password'  => 'test_password',
            'password2' => 'test_password',
        ]);
        $this->integer($uid)->isGreaterThan(0);
        $this->boolean($user->getFromDB($uid))->isTrue();

        $this->login('test_token', 'test_password'); // must be authenticated to be able to regenerate self personal token

        $token = $user->getToken($uid);
        $this->boolean($user->getFromDB($uid))->isTrue();
        $this->string($token)->hasLength(40);

        $user2 = new \User();
        $this->boolean($user2->getFromDBbyToken($token))->isTrue();
        $this->array($user2->fields)->isIdenticalTo($user->fields);

        $this->when(
            function () {
                $this->testedInstance->getFromDBbyToken('1485dd60301311eda2610242ac12000249aef69a', 'my_field');
            }
        )->error
            ->withType(E_USER_WARNING)
            ->withMessage('User::getFromDBbyToken() can only be called with $field parameter with theses values: \'personal_token\', \'api_token\'')
            ->exists();

        $this->when(
            function () {
                $this->testedInstance->getFromDBbyToken(['REGEX', '.*'], 'api_token');
            }
        )->error()
            ->withType(E_USER_WARNING)
            ->withMessage('Unexpected token value received: "string" expected, received "array".')
            ->exists();
    }

    public function testPrepareInputForAdd()
    {
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
        $this->hasSessionMessages(ERROR, ['The login is not valid. Unable to add the user.']);

       //add same user twice
        $input = ['name' => 'new_user'];
        $this->integer($user->add($input))->isGreaterThan(0);
        $this->boolean($user->add($input))->isFalse(0);
        $this->hasSessionMessages(ERROR, ['Unable to add. The user already exists.']);

        $input = [
            'name'      => 'user_pass',
            'password'  => 'password',
            'password2' => 'nomatch'
        ];
        $this->boolean($user->prepareInputForAdd($input))->isFalse();
        $this->hasSessionMessages(ERROR, ['Error: the two passwords do not match']);

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
            'name'                 => 'user_pass',
            'password'             => '',
            '_extauth'             => 1,
            'authtype'             => 1,
            'auths_id'             => 0,
            'password_last_update' => $_SESSION['glpi_currenttime'],
            'is_active'            => 1,
            'is_deleted'           => 0,
            'entities_id'          => 0,
            'profiles_id'          => 0,
        ];
        $this->array($user->prepareInputForAdd($input))->isIdenticalTo($expected);
    }

    protected function prepareInputForTimezoneUpdateProvider()
    {
        return [
            [
                'input'     => [
                    'timezone' => 'Europe/Paris',
                ],
                'expected'  => [
                    'timezone' => 'Europe/Paris',
                ],
            ],
            [
                'input'     => [
                    'timezone' => '0',
                ],
                'expected'  => [
                    'timezone' => 'NULL',
                ],
            ],
         // check that timezone is not reset unexpectedly
            [
                'input'     => [
                    'registration_number' => 'no.1',
                ],
                'expected'  => [
                    'registration_number' => 'no.1',
                ],
            ],
        ];
    }

    /**
     * @dataProvider prepareInputForTimezoneUpdateProvider
     */
    public function testPrepareInputForUpdateTimezone(array $input, $expected)
    {
        $this->login();
        $user = $this->newTestedInstance();
        $username = 'prepare_for_update_' . mt_rand();
        $user_id = $user->add(
            [
                'name'         => $username,
                'password'     => 'mypass',
                'password2'    => 'mypass',
                '_profiles_id' => 1
            ]
        );
        $this->integer((int)$user_id)->isGreaterThan(0);

        $this->login($username, 'mypass');

        $input = ['id' => $user_id] + $input;
        $result = $user->prepareInputForUpdate($input);

        $expected = ['id' => $user_id] + $expected;
        $this->array($result)->isIdenticalTo($expected);
    }

    protected function prepareInputForUpdatePasswordProvider()
    {
        return [
            [
                'input'     => [
                    'password'  => 'initial_pass',
                    'password2' => 'initial_pass'
                ],
                'expected'  => [
                ],
            ],
            [
                'input'     => [
                    'password'  => 'new_pass',
                    'password2' => 'new_pass_not_match'
                ],
                'expected'  => false,
                'messages'  => [ERROR => ['Error: the two passwords do not match']],
            ],
            [
                'input'     => [
                    'password'  => 'new_pass',
                    'password2' => 'new_pass'
                ],
                'expected'  => [
                    'password_last_update' => true,
                    'password' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider prepareInputForUpdatePasswordProvider
     */
    public function testPrepareInputForUpdatePassword(array $input, $expected, array $messages = null)
    {
        $this->login();
        $user = $this->newTestedInstance();
        $username = 'prepare_for_update_' . mt_rand();
        $user_id = $user->add(
            [
                'name'         => $username,
                'password'     => 'initial_pass',
                'password2'    => 'initial_pass',
                '_profiles_id' => 1
            ]
        );
        $this->integer((int)$user_id)->isGreaterThan(0);

        $this->login($username, 'initial_pass');

        $input = ['id' => $user_id] + $input;
        $result = $user->prepareInputForUpdate($input);

        if (null !== $messages) {
            $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($messages);
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
        }

        if (false === $expected) {
            $this->boolean($result)->isIdenticalTo($expected);
            return;
        }

        if (array_key_exists('password', $expected) && true === $expected['password']) {
           // password_hash result is unpredictible, so we cannot test its exact value
            $this->array($result)->hasKey('password');
            $this->string($result['password'])->isNotEmpty();

            unset($expected['password']);
            unset($result['password']);
        }

        $expected = ['id' => $user_id] + $expected;
        if (array_key_exists('password_last_update', $expected) && true === $expected['password_last_update']) {
           // $_SESSION['glpi_currenttime'] was reset on login, value cannot be provided by test provider
            $expected['password_last_update'] = $_SESSION['glpi_currenttime'];
        }

        $this->array($result)->isIdenticalTo($expected);
    }

    public function testPost_addItem()
    {
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
         ->integer['profiles_id']->isEqualTo(0);

        $puser = new \Profile_User();
        $this->boolean($puser->getFromDBByCrit(['users_id' => $uid]))->isTrue();
        $this->array($puser->fields)
         ->integer['profiles_id']->isEqualTo($pid)
         ->integer['entities_id']->isEqualTo($eid)
         ->integer['is_recursive']->isEqualTo(0)
         ->integer['is_dynamic']->isEqualTo(0);

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
         ->integer['profiles_id']->isEqualTo(0);

        $puser = new \Profile_User();
        $this->boolean($puser->getFromDBByCrit(['users_id' => $uid2]))->isTrue();
        $this->array($puser->fields)
         ->integer['profiles_id']->isEqualTo($pid)
         ->integer['entities_id']->isEqualTo($eid)
         ->integer['is_recursive']->isEqualTo(0)
         ->integer['is_dynamic']->isEqualTo(1);

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
         ->integer['profiles_id']->isEqualTo($pid)
         ->integer['entities_id']->isEqualTo($eid2)
         ->integer['is_recursive']->isEqualTo(0)
         ->integer['is_dynamic']->isEqualTo(1);

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
         ->integer['profiles_id']->isEqualTo($pid)
         ->integer['entities_id']->isEqualTo($eid2)
         ->integer['is_recursive']->isEqualTo(1)
         ->integer['is_dynamic']->isEqualTo(1);
    }

    public function testClone()
    {
        $this->login();

        $user = $this->newTestedInstance;

        // Create user with profile
        $uid = (int)$user->add([
            'name'         => 'create_user',
            '_profiles_id' => (int)getItemByTypeName('Profile', 'Self-Service', true)
        ]);
        $this->integer($uid)->isGreaterThan(0);

        $this->setEntity('_test_root_entity', true);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        // Add authorizations
        $puser = new \Profile_User();
        $this->integer($puser->add([
            'users_id'      => $uid,
            'profiles_id'   => (int)getItemByTypeName('Profile', 'Technician', true),
            'entities_id'   => (int)getItemByTypeName('Entity', '_test_child_1', true),
            'is_recursive'  => 0,
        ]))->isGreaterThan(0);

        $this->integer($puser->add([
            'users_id'      => $uid,
            'profiles_id'   => (int)getItemByTypeName('Profile', 'Admin', true),
            'entities_id'   => (int)getItemByTypeName('Entity', '_test_child_2', true),
            'is_recursive'  => 1,
        ]))->isGreaterThan(0);

        $puser_original = $puser->find(['users_id' => $uid]);

       // Test item cloning
        $added = $user->clone();
        $this->integer((int)$added)->isGreaterThan(0);

        $clonedUser = new \User();
        $this->boolean($clonedUser->getFromDB($added))->isTrue();

        $fields = $user->fields;

       // Check the values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedUser->getField($k))->isNotEqualTo($user->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedUser->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                case 'name':
                    $this->variable($clonedUser->getField($k))->isEqualTo("create_user-copy");
                    break;
                default:
                    $this->variable($clonedUser->getField($k))->isEqualTo($user->getField($k));
            }
        }

        // Check authorizations
        foreach ($puser_original as $row) {
            $this->boolean($puser->getFromDBByCrit([
                'users_id'      => $added,
                'profiles_id'   => $row['profiles_id'],
                'entities_id'   => $row['entities_id'],
                'is_recursive'  => $row['is_recursive'],
                'is_dynamic'    => $row['is_dynamic'],
            ]))->isTrue();
        }
    }

    public function testGetFromDBbyDn()
    {
        $user = $this->newTestedInstance;
        $dn = 'user=user_with_dn,dc=test,dc=glpi-project,dc=org';

        $uid = (int)$user->add([
            'name'      => 'user_with_dn',
            'user_dn'   => $dn
        ]);
        $this->integer($uid)->isGreaterThan(0);

        $this->boolean($user->getFromDBbyDn($dn))->isTrue();
        $this->array($user->fields)
         ->integer['id']->isIdenticalTo($uid)
         ->string['name']->isIdenticalTo('user_with_dn');
    }

    public function testGetFromDBbySyncField()
    {
        $user = $this->newTestedInstance;
        $sync_field = 'abc-def-ghi';

        $uid = (int)$user->add([
            'name'         => 'user_with_syncfield',
            'sync_field'   => $sync_field
        ]);

        $this->integer($uid)->isGreaterThan(0);

        $this->boolean($user->getFromDBbySyncField($sync_field))->isTrue();
        $this->array($user->fields)
         ->integer['id']->isIdenticalTo($uid)
         ->string['name']->isIdenticalTo('user_with_syncfield');
    }

    public function testGetFromDBbyName()
    {
        $user = $this->newTestedInstance;
        $name = 'user_with_name';

        $uid = (int)$user->add([
            'name' => $name
        ]);

        $this->integer($uid)->isGreaterThan(0);

        $this->boolean($user->getFromDBbyName($name))->isTrue();
        $this->array($user->fields)
         ->integer['id']->isIdenticalTo($uid);
    }

    public function testGetFromDBbyNameAndAuth()
    {
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
         ->integer['id']->isIdenticalTo($uid)
         ->string['name']->isIdenticalTo($name);
    }

    protected function rawNameProvider()
    {
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
    public function testGetFriendlyName($input, $rawname)
    {
        $user = $this->newTestedInstance;

        $this->string($user->getFriendlyName())->isIdenticalTo('');

        $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($uid = (int)$this->testedInstance->add($input))
                  ->isGreaterThan(0)
               ->boolean($this->testedInstance->getFromDB($uid))->isTrue()
               ->string($this->testedInstance->getFriendlyName())->isIdenticalTo($rawname);
    }

    public function testBlankPassword()
    {
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

    public function testPre_updateInDB()
    {
        $this->login();
        $user = $this->newTestedInstance();

        $uid = (int)$user->add([
            'name' => 'preupdate_user'
        ]);
        $this->integer($uid)->isGreaterThan(0);
        $this->boolean($user->getFromDB($uid))->isTrue();

        $this->boolean($user->update([
            'id'     => $uid,
            'name'   => 'preupdate_user_edited'
        ]))->isTrue();
        $this->hasNoSessionMessages([ERROR, WARNING]);

       //can update with same name when id is identical
        $this->boolean($user->update([
            'id'     => $uid,
            'name'   => 'preupdate_user_edited'
        ]))->isTrue();
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $this->integer(
            (int)$user->add(['name' => 'do_exist'])
        )->isGreaterThan(0);
        $this->boolean($user->update([
            'id'     => $uid,
            'name'   => 'do_exist'
        ]))->isTrue();
        $this->hasSessionMessages(ERROR, ['Unable to update login. A user already exists.']);

        $this->boolean($user->getFromDB($uid))->isTrue();
        $this->string($user->fields['name'])->isIdenticalTo('preupdate_user_edited');

        $this->boolean($user->update([
            'id'     => $uid,
            'name'   => 'in+valid'
        ]))->isTrue();
        $this->hasSessionMessages(ERROR, ['The login is not valid. Unable to update login.']);
    }

    public function testGetIdByName()
    {
        $user = $this->newTestedInstance;

        $uid = (int)$user->add(['name' => 'id_by_name']);
        $this->integer($uid)->isGreaterThan(0);

        $this->integer($user->getIdByName('id_by_name'))->isIdenticalTo($uid);
    }

    public function testGetIdByField()
    {
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

    public function testgetAdditionalMenuOptions()
    {
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

    protected function passwordExpirationMethodsProvider()
    {
        $time = time();

        return [
            [
                'creation_date'                   => $_SESSION['glpi_currenttime'],
                'last_update'                     => date('Y-m-d H:i:s', strtotime('-10 years', $time)),
                'expiration_delay'                => -1,
                'expiration_notice'               => -1,
                'expected_expiration_time'        => null,
                'expected_should_change_password' => false,
                'expected_has_password_expire'    => false,
            ],
            [
                'creation_date'                   => $_SESSION['glpi_currenttime'],
                'last_update'                     => date('Y-m-d H:i:s', strtotime('-10 days', $time)),
                'expiration_delay'                => 15,
                'expiration_notice'               => -1,
                'expected_expiration_time'        => strtotime('+5 days', $time),
                'expected_should_change_password' => false, // not yet in notice time
                'expected_has_password_expire'    => false,
            ],
            [
                'creation_date'                   => $_SESSION['glpi_currenttime'],
                'last_update'                     => date('Y-m-d H:i:s', strtotime('-10 days', $time)),
                'expiration_delay'                => 15,
                'expiration_notice'               => 10,
                'expected_expiration_time'        => strtotime('+5 days', $time),
                'expected_should_change_password' => true,
                'expected_has_password_expire'    => false,
            ],
            [
                'creation_date'                   => $_SESSION['glpi_currenttime'],
                'last_update'                     => date('Y-m-d H:i:s', strtotime('-20 days', $time)),
                'expiration_delay'                => 15,
                'expiration_notice'               => -1,
                'expected_expiration_time'        => strtotime('-5 days', $time),
                'expected_should_change_password' => true,
                'expected_has_password_expire'    => true,
            ],
            [
                'creation_date'                   => $_SESSION['glpi_currenttime'],
                'last_update'                     => null,
                'expiration_delay'                => 15,
                'expiration_notice'               => -1,
                'expected_expiration_time'        => strtotime('+15 days', strtotime($_SESSION['glpi_currenttime'])),
                'expected_should_change_password' => false,
                'expected_has_password_expire'    => false,
            ],
            [
                'creation_date'                   => '2021-12-03 17:54:32',
                'last_update'                     => null,
                'expiration_delay'                => 15,
                'expiration_notice'               => -1,
                'expected_expiration_time'        => strtotime('2021-12-18 17:54:32'),
                'expected_should_change_password' => true,
                'expected_has_password_expire'    => true,
            ],
        ];
    }

    /**
     * @dataProvider passwordExpirationMethodsProvider
     */
    public function testPasswordExpirationMethods(
        string $creation_date,
        ?string $last_update,
        int $expiration_delay,
        int $expiration_notice,
        $expected_expiration_time,
        $expected_should_change_password,
        $expected_has_password_expire
    ) {
        global $CFG_GLPI;

        $user = $this->newTestedInstance();
        $username = 'prepare_for_update_' . mt_rand();
        $user_id = $user->add(
            [
                'date_creation' => $creation_date,
                'name'          => $username,
                'password'      => 'pass',
                'password2'     => 'pass'
            ]
        );
        $this->integer($user_id)->isGreaterThan(0);
        $this->boolean($user->update(['id' => $user_id, 'password_last_update' => $last_update]))->isTrue();
        $this->boolean($user->getFromDB($user->fields['id']))->isTrue();

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_delay'] = $expiration_delay;
        $CFG_GLPI['password_expiration_notice'] = $expiration_notice;

        $expiration_time = $user->getPasswordExpirationTime();
        $should_change_password = $user->shouldChangePassword();
        $has_password_expire = $user->hasPasswordExpired();

        $CFG_GLPI = $cfg_backup;

        $this->variable($expiration_time)->isEqualTo($expected_expiration_time);
        $this->boolean($should_change_password)->isEqualTo($expected_should_change_password);
        $this->boolean($has_password_expire)->isEqualTo($expected_has_password_expire);
    }


    protected function cronPasswordExpirationNotificationsProvider()
    {
       // create 10 users with differents password_last_update dates
       // first has its password set 1 day ago
       // second has its password set 11 day ago
       // and so on
       // tenth has its password set 91 day ago
        $user = new \User();
        for ($i = 1; $i < 100; $i += 10) {
            $user_id = $user->add(
                [
                    'name'     => 'cron_user_' . mt_rand(),
                    'authtype' => \Auth::DB_GLPI,
                ]
            );
            $this->integer($user_id)->isGreaterThan(0);
            $this->boolean(
                $user->update(
                    [
                        'id' => $user_id,
                        'password_last_update' => date('Y-m-d H:i:s', strtotime('-' . $i . ' days')),
                    ]
                )
            )->isTrue();
        }

        return [
         // validate that cron does nothing if password expiration is not active (default config)
            [
                'expiration_delay'               => -1,
                'notice_delay'                   => -1,
                'lock_delay'                     => -1,
                'cron_limit'                     => 100,
                'expected_result'                => 0, // 0 = nothing to do
                'expected_notifications_count'   => 0,
                'expected_lock_count'            => 0,
            ],
         // validate that cron send no notification if password_expiration_notice == -1
            [
                'expiration_delay'               => 15,
                'notice_delay'                   => -1,
                'lock_delay'                     => -1,
                'cron_limit'                     => 100,
                'expected_result'                => 0, // 0 = nothing to do
                'expected_notifications_count'   => 0,
                'expected_lock_count'            => 0,
            ],
         // validate that cron send notifications instantly if password_expiration_notice == 0
            [
                'expiration_delay'               => 50,
                'notice_delay'                   => 0,
                'lock_delay'                     => -1,
                'cron_limit'                     => 100,
                'expected_result'                => 1, // 1 = fully processed
                'expected_notifications_count'   => 5, // 5 users should be notified (them which has password set more than 50 days ago)
                'expected_lock_count'            => 0,
            ],
         // validate that cron send notifications before expiration if password_expiration_notice > 0
            [
                'expiration_delay'               => 50,
                'notice_delay'                   => 20,
                'lock_delay'                     => -1,
                'cron_limit'                     => 100,
                'expected_result'                => 1, // 1 = fully processed
                'expected_notifications_count'   => 7, // 7 users should be notified (them which has password set more than 50-20 days ago)
                'expected_lock_count'            => 0,
            ],
         // validate that cron returns partial result if there is too many notifications to send
            [
                'expiration_delay'               => 50,
                'notice_delay'                   => 20,
                'lock_delay'                     => -1,
                'cron_limit'                     => 5,
                'expected_result'                => -1, // -1 = partially processed
                'expected_notifications_count'   => 5, // 5 on 7 users should be notified (them which has password set more than 50-20 days ago)
                'expected_lock_count'            => 0,
            ],
         // validate that cron disable users instantly if password_expiration_lock_delay == 0
            [
                'expiration_delay'               => 50,
                'notice_delay'                   => -1,
                'lock_delay'                     => 0,
                'cron_limit'                     => 100,
                'expected_result'                => 1, // 1 = fully processed
                'expected_notifications_count'   => 0,
                'expected_lock_count'            => 5, // 5 users should be locked (them which has password set more than 50 days ago)
            ],
         // validate that cron disable users with given delay if password_expiration_lock_delay > 0
            [
                'expiration_delay'               => 20,
                'notice_delay'                   => -1,
                'lock_delay'                     => 10,
                'cron_limit'                     => 100,
                'expected_result'                => 1, // 1 = fully processed
                'expected_notifications_count'   => 0,
                'expected_lock_count'            => 7, // 7 users should be locked (them which has password set more than 20+10 days ago)
            ],
        ];
    }

    /**
     * @dataProvider cronPasswordExpirationNotificationsProvider
     */
    public function testCronPasswordExpirationNotifications(
        int $expiration_delay,
        int $notice_delay,
        int $lock_delay,
        int $cron_limit,
        int $expected_result,
        int $expected_notifications_count,
        int $expected_lock_count
    ) {
        global $CFG_GLPI, $DB;

        $this->login();

        $crontask = new \CronTask();
        $this->boolean($crontask->getFromDBbyName(\User::getType(), 'passwordexpiration'))->isTrue();
        $crontask->fields['param'] = $cron_limit;

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_delay'] = $expiration_delay;
        $CFG_GLPI['password_expiration_notice'] = $notice_delay;
        $CFG_GLPI['password_expiration_lock_delay'] = $lock_delay;
        $CFG_GLPI['use_notifications']  = true;
        $CFG_GLPI['notifications_ajax'] = 1;
        $result = \User::cronPasswordExpiration($crontask);
        $CFG_GLPI = $cfg_backup;

        $this->integer($result)->isEqualTo($expected_result);
        $this->integer(
            countElementsInTable(\Alert::getTable(), ['itemtype' => \User::getType()])
        )->isEqualTo($expected_notifications_count);
        $DB->delete(\Alert::getTable(), ['itemtype' => \User::getType()]); // reset alerts

        $user_crit = [
            'authtype'  => \Auth::DB_GLPI,
            'is_active' => 0,
        ];
        $this->integer(countElementsInTable(\User::getTable(), $user_crit))->isEqualTo($expected_lock_count);
        $DB->update(\User::getTable(), ['is_active' => 1], $user_crit); // reset users
    }

    /**
     * Tests if the last super admin user can be deleted or disabled
     *
     * @return void
     */
    public function testLastAdministratorDeleteOrDisable(): void
    {
        // Default: only one super admin account
        $super_admin = getItemByTypeName('Profile', 'Super-Admin');
        $this->boolean($super_admin->isLastSuperAdminProfile())->isTrue();

        // Default: 3 users with super admin account authorizations
        $users = (new \User())->find([
            'id' => new QuerySubQuery([
                'SELECT' => 'users_id',
                'FROM'   => Profile_User::getTable(),
                'WHERE'  => [
                    'profiles_id' => $super_admin->fields['id']
                ]
            ])
        ]);
        $this->array($users)->hasSize(3);
        $this->array(array_column($users, 'name'))->isEqualTo(['glpi', TU_USER, "jsmith123"]);

        $glpi = getItemByTypeName('User', 'glpi');
        $tu_user = getItemByTypeName('User', TU_USER);
        $jsmith123 = getItemByTypeName('User', 'jsmith123');

        // Delete 2 users
        $this->login('glpi', 'glpi');
        $this->boolean($tu_user->canDeleteItem())->isTrue();
        $this->boolean($tu_user->delete(['id' => $tu_user->getID()]))->isTrue();
        $this->boolean($jsmith123->canDeleteItem())->isTrue();
        $this->boolean($jsmith123->delete(['id' => $jsmith123->getID()]))->isTrue();

        // Last user, can't be deleted or disabled
        $this->boolean($glpi->update([
            'id'        => $glpi->getID(),
            'is_active' => false
        ]))->isTrue();
        $this->hasSessionMessages(ERROR, [
            "Can't set user as inactive as it is the only remaining super administrator."
        ]);
        $glpi->getFromDB($glpi->getId());
        $this->boolean((bool) $glpi->fields['is_active'])->isEqualTo(true);
        $this->boolean($glpi->canDeleteItem())->isFalse();

        // Can still be deleted by calling delete directly, maybe it should not be possible ?
        $this->boolean($glpi->delete(['id' => $glpi->getID()]))->isTrue();
    }

    public function testUserPreferences()
    {
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            // Cannot use `@php 8.0` to skip test as we want to ensure that test suite fails if methods are skipped
            // (to detect missing extensions for instance).
            $this->boolean(true)->isTrue();
            return;
        }

        $user = new \User();
        $users_id = $user->add([
            'name' => 'for preferences',
            'login' => 'for preferences',
            'password' => 'for preferences',
            'password2' => 'for preferences',
            'profiles_id' => 4
        ]);
        $this->integer($users_id)->isGreaterThan(0);

        $this->login('for preferences', 'for preferences');
        $this->boolean($user->getFromDB($users_id))->isTrue();
        $this->variable($user->fields['show_count_on_tabs'])->isNull();
        $this->variable($_SESSION['glpishow_count_on_tabs'])->isEqualTo(1);

        $itil_layout_1 = '{"collapsed":"true","expanded":"false","items":{"item-main":"false","actors":"false","items":"false","service-levels":"false","linked_tickets":"false"}}';
        $this->boolean(
            $user->update(Sanitizer::dbEscapeRecursive([
                'id' => $users_id,
                'show_count_on_tabs' => '0',
                'itil_layout' => $itil_layout_1,
            ]))
        )->isTrue();

        // pref should be updated even without logout/login
        $this->variable($_SESSION['glpishow_count_on_tabs'])->isEqualTo(0);
        $this->variable($_SESSION['glpiitil_layout'])->isEqualTo($itil_layout_1);

        // logout/login and check prefs
        $this->logOut();
        $this->login('for preferences', 'for preferences');
        $this->variable($_SESSION['glpishow_count_on_tabs'])->isEqualTo(0);
        $this->variable($_SESSION['glpiitil_layout'])->isEqualTo($itil_layout_1);


        $this->boolean($user->getFromDB($users_id))->isTrue();
        $this->variable($user->fields['show_count_on_tabs'])->isEqualTo(0);
        $this->variable($user->fields['itil_layout'])->isEqualTo($itil_layout_1);

        $itil_layout_2 = '{"collapsed":"false","expanded":"true"}';
        $this->boolean(
            $user->update(Sanitizer::dbEscapeRecursive([
                'id' => $users_id,
                'show_count_on_tabs' => '1',
                'itil_layout' => $itil_layout_2,
            ]))
        )->isTrue();

        // pref should be updated even without logout/login
        $this->variable($_SESSION['glpishow_count_on_tabs'])->isEqualTo(1);
        $this->variable($_SESSION['glpiitil_layout'])->isEqualTo($itil_layout_2);

        // logout/login and check prefs
        $this->logOut();
        $this->login('for preferences', 'for preferences');
        $this->variable($_SESSION['glpishow_count_on_tabs'])->isEqualTo(1);
        $this->variable($_SESSION['glpiitil_layout'])->isEqualTo($itil_layout_2);

        $this->boolean($user->getFromDB($users_id))->isTrue();
        $this->variable($user->fields['show_count_on_tabs'])->isNull();
        $this->variable($user->fields['itil_layout'])->isEqualTo($itil_layout_2);
    }

    /**
     * Test that user_dn_hash is correctly set on user creation and update
     *
     * @return void
     */
    public function testUserDnIsHashedOnAddAndUpdate(): void
    {
        // Create user whithout dn and check that user_dn_hash is not set
        $user = $this->createItem('User', [
            'name'      => __FUNCTION__,
        ]);
        $this->variable($user->fields['user_dn'])->isNull();
        $this->variable($user->fields['user_dn_hash'])->isNull();

        // Create user with dn and check that user_dn_hash is set
        $dn = 'user=' . __FUNCTION__ . '_created,dc=R&D,dc=glpi-project,dc=org';
        $user = $this->createItem('User', [
            'name'      => __FUNCTION__ . '_created',
            'user_dn'   => $dn
        ]);
        $this->string($user->fields['user_dn_hash'])->isEqualTo(md5($dn));

        // Update user dn and check that user_dn_hash is updated
        $dn = 'user=' . __FUNCTION__ . '_updated,dc=R&D,dc=glpi-project,dc=org';
        $this->updateItem('User', $user->getID(), [
            'user_dn'   => $dn
        ]);
        $user->getFromDB($user->getID());
        $this->string($user->fields['user_dn_hash'])->isEqualTo(md5($dn));

        // Set user_dn to empty and check that user_dn_hash is set to null
        $this->updateItem('User', $user->getID(), [
            'user_dn'   => ''
        ]);
        $user->getFromDB($user->getID());
        $this->variable($user->fields['user_dn_hash'])->isNull();

        // Set user_dn to null and check that user_dn_hash is set to null
        $this->updateItem('User', $user->getID(), [
            'user_dn'   => null
        ]);
        $user->getFromDB($user->getID());
        $this->variable($user->fields['user_dn_hash'])->isNull();
    }

    /**
     * Test that user_dn_hash is correctly used in getFromDBbyDn method
     *
     * @return void
     */
    public function testUserDnHashIsUsedInGetFromDBbyDn(): void
    {
        global $DB;

        $retrievedUser = new \User();

        // Get a user with a bad dn
        $this->boolean($retrievedUser->getFromDBbyDn(__FUNCTION__))
            ->isFalse();
        $this->boolean($retrievedUser->isNewItem())->isTrue();

        // Create a user with a dn
        $dn = 'user=' . __FUNCTION__ . ',dc=R&D,dc=glpi-project,dc=org';
        $user = $this->createItem('User', [
            'name'      => __FUNCTION__,
            'user_dn'   => $dn
        ]);

        // Retrieve the user using getFromDBbyDn method
        $this->boolean($retrievedUser->getFromDBbyDn($dn))->isTrue();
        $this->boolean($retrievedUser->getFromDBbyDn(Sanitizer::sanitize($dn)))->isTrue(); // works also with sanitized value
        $this->boolean($retrievedUser->isNewItem())->isFalse();

        // Unset user_dn to check that user_dn_hash is used
        $DB->update(
            \User::getTable(),
            ['user_dn' => ''],
            ['id' => $user->getID()]
        );

        // Retrieve the user using getFromDBbyDn and check if user_dn_hash is used
        $this->boolean($retrievedUser->getFromDBbyDn($dn))->isTrue();
        $this->boolean($retrievedUser->getFromDBbyDn(Sanitizer::sanitize($dn)))->isTrue(); // works also with sanitized value
        $this->boolean($retrievedUser->isNewItem())->isFalse();
        $this->string($retrievedUser->fields['user_dn'])->isEmpty();
    }

    protected function toggleSavedSearchPinProvider(): iterable
    {
        foreach (['', '[]', '{}'] as $initial_db_value) {
            // initial empty data
            yield [
                'initial_db_value' => $initial_db_value,
                'itemtype'         => 'Computer',
                'success'          => true,
                'result_db_value'  => '{"Computer":1}',
            ];
        }

        // toggle to 1
        yield [
            'initial_db_value' => '{"Computer":0,"Monitor":1}',
            'itemtype'         => 'Computer',
            'success'          => true,
            'result_db_value'  => '{"Computer":1,"Monitor":1}',
        ];

        // toggle to 0
        yield [
            'initial_db_value' => '{"Computer":1,"Monitor":1}',
            'itemtype'         => 'Monitor',
            'success'          => true,
            'result_db_value'  => '{"Computer":1,"Monitor":0}',
        ];

        // namespaced itemtype
        yield [
            'initial_db_value' => '{"Computer":1,"Monitor":0}',
            'itemtype'         => 'Glpi\\Socket',
            'success'          => true,
            'result_db_value'  => '{"Computer":1,"Monitor":0,"Glpi\\\\Socket":1}',
        ];

        // invalid itemtype
        yield [
            'initial_db_value' => '{"Computer":1,"Monitor":1}',
            'itemtype'         => 'This is not a valid itemtype',
            'success'          => false,
            'result_db_value'  => '{"Computer":1,"Monitor":1}',
        ];
    }

    /**
     * @dataProvider toggleSavedSearchPinProvider
     */
    public function testToggleSavedSearchPin(string $initial_db_value, string $itemtype, bool $success, string $result_db_value): void
    {
        $user = $this->createItem(
            \User::class,
            [
                'name'                  => __FUNCTION__ . (string) mt_rand(),
                'savedsearches_pinned'  => $initial_db_value,
            ]
        );

        $this->boolean($user->toggleSavedSearchPin($itemtype))->isEqualTo($success);
        $this->boolean($user->getFromDb($user->getID()))->isTrue();
        $this->string($user->fields['savedsearches_pinned'])->isEqualTo($result_db_value);

        // result value in DB is always a valid JSON string
        $this->array(importArrayFromDB($user->fields['savedsearches_pinned']))->isEqualTo(json_decode($result_db_value, true));
    }
}
