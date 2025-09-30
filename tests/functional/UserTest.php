<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DateInterval;
use DateTime;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Exception\ForgetPasswordException;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile_User;
use Psr\Log\LogLevel;
use User;

/* Test for inc/user.class.php */

class UserTest extends \DbTestCase
{
    public function testGenerateUserToken()
    {
        $this->login(); // must be authenticated to be able to regenerate self personal token

        $user = getItemByTypeName('User', TU_USER);
        $this->assertNull($user->fields['personal_token_date']);
        $this->assertNull($user->fields['personal_token']);

        $token = $user->getAuthToken();
        $this->assertNotEmpty($token);

        $user->getFromDB($user->getID());
        $this->assertSame($token, $user->fields['personal_token']);
        $this->assertSame($_SESSION['glpi_currenttime'], $user->fields['personal_token_date']);
    }

    public function testLostPasswordInvalidMail()
    {
        $user = getItemByTypeName('User', TU_USER);
        // Test request for a password with invalid email
        $res = $user->forgetPassword('this-email-does-not-exists@example.com');
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            "Failed to find a single user for 'this-email-does-not-exists@example.com', 0 user(s) found.",
            LogLevel::WARNING
        );
    }

    public function testLostPasswordInvalidToken()
    {
        $user = getItemByTypeName('User', TU_USER);
        // Test reset password with a bad token
        $result = $user->forgetPassword($user->getDefaultEmail());
        $this->assertTrue($result);
        $token = $user->fields['password_forget_token'];
        $this->assertNotEmpty($token);

        $input = [
            'password_forget_token' => $token . 'bad',
            'password'  => TU_PASS,
            'password2' => TU_PASS,
        ];
        $this->expectException(ForgetPasswordException::class);
        $user->updateForgottenPassword($input);
    }

    public function testLostPassword()
    {
        $user = getItemByTypeName('User', TU_USER);

        // Test request for a password
        $result = $user->forgetPassword($user->getDefaultEmail());
        $this->assertTrue($result);

        // Test reset password with good token
        // 1 - Refresh the in-memory instance of user and get the current password
        $user->getFromDB($user->getID());
        $token = $user->fields['password_forget_token'];

        // 2 - Set a new password
        $input = [
            'password_forget_token' => $token,
            'password'  => 'NewPassword',
            'password2' => 'NewPassword',
        ];

        // 3 - check the update succeeds
        $result = $user->updateForgottenPassword($input);
        $this->assertTrue($result);
        $newHash = $user->fields['password'];

        // Test the new password was saved
        $this->assertNotSame(false, \Auth::checkPassword('NewPassword', $newHash));

        // Validates that password reset token has been removed
        $user = getItemByTypeName('User', TU_USER);
        $token = $user->fields['password_forget_token'];
        $this->assertEmpty($token);
    }

    public function testGetDefaultEmail()
    {
        $this->login(); // must be authenticated to update emails

        $user = new User();

        $this->assertSame('', $user->getDefaultEmail());
        $this->assertSame([], $user->getAllEmails());
        $this->assertFalse($user->isEmail('one@test.com'));

        $uid = (int) $user->add([
            'name'   => 'test_email',
            '_useremails'  => [
                'one@test.com',
            ],
        ]);
        $this->assertGreaterThan(0, $uid);
        $this->assertTrue($user->getFromDB($user->fields['id']));
        $this->assertSame('one@test.com', $user->getDefaultEmail());

        $this->assertTrue(
            $user->update([
                'id'              => $uid,
                '_useremails'     => ['two@test.com'],
                '_default_email'  => 0,
            ])
        );

        $this->assertTrue($user->getFromDB($user->fields['id']));
        $this->assertSame('two@test.com', $user->getDefaultEmail());

        $this->assertCount(2, $user->getAllEmails());
        $this->assertTrue($user->isEmail('one@test.com'));

        $tu_user = getItemByTypeName('User', TU_USER);
        $this->assertFalse($user->isEmail($tu_user->getDefaultEmail()));
    }

    public function testUpdateEmail()
    {
        $this->login(); // must be authenticated to update emails

        // Create a user with some emails
        $user1 = new User();
        $uid1 = (int) $user1->add([
            'name'   => 'test_email 1',
            '_useremails'  => [
                -1 => 'email1@test.com',
                -2 => 'email2@test.com',
                -3 => 'email3@test.com',
            ],
        ]);
        $this->assertGreaterThan(0, $uid1);

        // Emails are all attached to user 1
        $user1_email1_id = current(
            getAllDataFromTable(\UserEmail::getTable(), ['users_id' => $uid1, 'email' => 'email1@test.com'])
        )['id'] ?? 0;
        $this->assertGreaterThan(0, $user1_email1_id);

        $this->assertSame('email1@test.com', $user1->getDefaultEmail());

        $this->assertTrue($user1->getFromDB($uid1));
        $user1_emails = $user1->getAllEmails();
        asort($user1_emails);
        $this->assertEquals(
            [
                'email1@test.com',
                'email2@test.com',
                'email3@test.com',
            ],
            array_values($user1_emails)
        );

        // Create another user
        $user2 = new User();
        $uid2 = $user2->add([
            'name'   => 'test_email 2',
            '_useremails'  => [
                -1 => 'anotheremail1@test.com',
                $user1_email1_id => 'anotheremail2@test.com', // try to change email from user 1
                -3 => 'anotheremail3@test.com',
            ],
        ]);
        $this->assertGreaterThan(0, $uid2);

        // Emails are all attached to user 2
        $user2_email1_id = current(
            getAllDataFromTable(\UserEmail::getTable(), ['users_id' => $uid2, 'email' => 'anotheremail1@test.com'])
        )['id'] ?? 0;
        $this->assertGreaterThan(0, $user2_email1_id);

        $this->assertSame('anotheremail1@test.com', $user2->getDefaultEmail());

        $this->assertTrue($user2->getFromDB($uid2));
        $user2_emails = $user2->getAllEmails();
        asort($user2_emails);
        $this->assertEquals(
            [
                'anotheremail1@test.com',
                'anotheremail2@test.com',
                'anotheremail3@test.com',
            ],
            array_values($user2_emails)
        );

        // User 1 emails did not change
        $this->assertTrue($user1->getFromDB($uid1));
        $user1_emails = $user1->getAllEmails();
        asort($user1_emails);
        $this->assertEquals(
            [
                'email1@test.com',
                'email2@test.com',
                'email3@test.com',
            ],
            array_values($user1_emails)
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
        $this->assertTrue($update);

        // Emails are all attached to user 2
        $this->assertTrue($user2->getFromDB($uid2));
        $user2_emails = $user2->getAllEmails();
        asort($user2_emails);
        $this->assertEquals(
            [
                'anotheremail1-update@test.com',
                'anotheremail2@test.com',
                'anotheremail3@test.com',
                'email1-updated@test.com',
            ],
            array_values($user2_emails)
        );

        $this->assertSame('email1-updated@test.com', $user2->getDefaultEmail());

        // User 1 emails did not change
        $this->assertTrue($user1->getFromDB($uid1));
        $user1_emails = $user1->getAllEmails();
        asort($user1_emails);
        $this->assertEquals(
            [
                'email1@test.com',
                'email2@test.com',
                'email3@test.com',
            ],
            array_values($user1_emails)
        );
    }

    public function testGetFromDBbyTokenWrongField()
    {
        $user = new User();

        $res = $user->getFromDBbyToken('1485dd60301311eda2610242ac12000249aef69a', 'my_field');
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'User::getFromDBbyToken() can only be called with $field parameter with theses values: \'personal_token\', \'api_token\'',
            LogLevel::WARNING
        );
    }

    public function testGetFromDBbyTokenNotString()
    {
        $user = new User();
        $res = $user->getFromDBbyToken(['REGEX', '.*'], 'api_token');
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Unexpected token value received: "string" expected, received "array".',
            LogLevel::WARNING
        );
    }

    public function testGetFromDBbyToken()
    {
        $user = new User();
        $uid = $user->add([
            'name'      => 'test_token',
            'password'  => 'test_password',
            'password2' => 'test_password',
        ]);
        $this->assertGreaterThan(0, $uid);
        $this->assertTrue($user->getFromDB($uid));

        $this->login('test_token', 'test_password'); // must be authenticated to be able to regenerate self personal token

        $token = $user->getToken($uid);
        $this->assertTrue($user->getFromDB($uid));
        $this->assertEquals(40, strlen($token));

        $user2 = new User();
        $this->assertTrue($user2->getFromDBbyToken($token));
        $this->assertSame($user->fields, $user2->fields);
    }

    public function testPrepareInputForAdd()
    {
        $this->login();
        $user = new User();

        $input = [
            'name'   => 'prepare_for_add',
        ];
        $expected = [
            'name'         => 'prepare_for_add',
            'authtype'     => 1,
            'auths_id'     => 0,
            'is_active'    => 1,
            'is_deleted'   => 0,
            'entities_id'  => 0,
            'profiles_id'  => 0,
        ];

        $this->assertSame($expected, $user->prepareInputForAdd($input));

        $input['_stop_import'] = 1;
        $this->assertFalse($user->prepareInputForAdd($input));

        $input = ['name' => 'invalid+login'];
        $this->assertFalse($user->prepareInputForAdd($input));
        $this->hasSessionMessages(ERROR, ['The login is not valid. Unable to add the user.']);

        //add same user twice
        $input = ['name' => 'new_user'];
        $this->assertGreaterThan(0, $user->add($input));
        $user = new User();
        $this->assertFalse($user->add($input));
        $this->hasSessionMessages(ERROR, ['Unable to add. The user already exists.']);

        $input = [
            'name'      => 'user_pass',
            'password'  => 'password',
            'password2' => 'nomatch',
        ];
        $this->assertFalse($user->prepareInputForAdd($input));
        $this->hasSessionMessages(ERROR, ['Error: the two passwords do not match']);

        $input = [
            'name'      => 'user_pass',
            'password'  => '',
            'password2' => 'nomatch',
        ];
        $expected = [
            'name'         => 'user_pass',
            'password2'    => 'nomatch',
            'authtype'     => 1,
            'auths_id'     => 0,
            'is_active'    => 1,
            'is_deleted'   => 0,
            'entities_id'  => 0,
            'profiles_id'  => 0,
        ];
        $this->assertSame($expected, $user->prepareInputForAdd($input));

        $input['password'] = 'nomatch';
        $expected['password'] = 'unknonwn';
        unset($expected['password2']);
        $prepared = $user->prepareInputForAdd($input);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $prepared);
        }
        $this->assertEquals(60, strlen($prepared['password']));
        $this->assertStringStartsWith('$2y$', $prepared['password']);

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
        $this->assertSame($expected, $user->prepareInputForAdd($input));

        // Full structure as default entity
        $input['entities_id'] = -1;
        $user_id = $user->add($input);
        $this->assertGreaterThan(0, $user_id);
        $this->assertTrue($user->getFromDB($user_id));
        $this->assertSame(null, $user->fields['entities_id']);
    }

    public function testPrepareInputForAddPdfFont(): void
    {
        global $CFG_GLPI;

        $this->login();

        $user = new User();

        $default_values = [
            'authtype'     => 1,
            'auths_id'     => 0,
            'is_active'    => 1,
            'is_deleted'   => 0,
            'entities_id'  => 0,
            'profiles_id'  => 0,
        ];

        // Valid PDF font
        $input = [
            'name'    => __FUNCTION__,
            'pdffont' => 'freesans',
        ];
        $expected = [
            'name'    => __FUNCTION__,
            'pdffont' => 'freesans',
        ] + $default_values;
        $this->assertSame($expected, $user->prepareInputForAdd($input));

        // Invalid PDF font
        $input = [
            'name'    => __FUNCTION__,
            'pdffont' => 'notavalidfont',
        ];
        $expected = [
            'name'    => __FUNCTION__,
            // pdffont is removed from the input
        ] + $default_values;
        $this->assertSame($expected, $user->prepareInputForAdd($input));
        $this->hasSessionMessages(ERROR, [
            'The following field has an incorrect value: &quot;PDF export font&quot;.',
        ]);
    }

    public function testPrepareInputForUpdatePdfFont(): void
    {
        global $CFG_GLPI;

        $this->login();

        $user = \getItemByTypeName(User::class, 'glpi');

        // Valid PDF font
        $input = [
            'id'      => $user->getID(),
            'pdffont' => 'freesans',
        ];
        $expected = $input;
        $this->assertSame($expected, $user->prepareInputForUpdate($input));

        // Invalid PDF font
        $input = [
            'id'      => $user->getID(),
            'pdffont' => 'notavalidfont',
        ];
        $expected = [
            'id'      => $user->getID(),
            // pdffont is removed from the input
        ];
        $this->assertSame($expected, $user->prepareInputForUpdate($input));
        $this->hasSessionMessages(ERROR, [
            'The following field has an incorrect value: &quot;PDF export font&quot;.',
        ]);
    }

    public static function prepareInputForTimezoneUpdateProvider()
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

    #[DataProvider('prepareInputForTimezoneUpdateProvider')]
    public function testPrepareInputForUpdateTimezone(array $input, array $expected)
    {
        $this->login();
        $user = new User();
        $username = 'prepare_for_update_' . mt_rand();
        $user_id = $user->add(
            [
                'name'         => $username,
                'password'     => 'mypass',
                'password2'    => 'mypass',
                '_profiles_id' => 1,
            ]
        );
        $this->assertGreaterThan(0, (int) $user_id);

        $this->login($username, 'mypass');

        $input = ['id' => $user_id] + $input;
        $result = $user->prepareInputForUpdate($input);

        $expected = ['id' => $user_id] + $expected;
        $this->assertSame($expected, $result);
    }

    protected function prepareInputForUpdatePasswordProvider()
    {
        return [
            [
                'input'     => [
                    'password'  => 'new_pass',
                    'password2' => 'new_pass_not_match',
                ],
                'expected'  => false,
                'messages'  => [ERROR => ['Error: the two passwords do not match']],
            ],
            [
                'input'     => [
                    'password'  => 'new_pass',
                    'password2' => 'new_pass',
                ],
                'expected'  => [
                    'password_last_update' => true,
                    'password' => true,
                ],
            ],
        ];
    }

    public function testPrepareInputForUpdatePassword()
    {
        $this->login();

        $data = $this->prepareInputForUpdatePasswordProvider();
        foreach ($data as $row) {
            $input = $row['input'];
            $expected = $row['expected'];
            $messages = $row['messages'] ?? null;

            $user = new User();
            $username = 'prepare_for_update_' . mt_rand();
            $user_id = $user->add(
                [
                    'name' => $username,
                    'password' => 'initial_pass',
                    'password2' => 'initial_pass',
                    '_profiles_id' => 1,
                ]
            );
            $this->assertGreaterThan(0, (int) $user_id);

            $this->login($username, 'initial_pass');

            $input = ['id' => $user_id] + $input;
            $result = $user->prepareInputForUpdate($input);

            if (null !== $messages) {
                $this->assertSame($messages, $_SESSION['MESSAGE_AFTER_REDIRECT']);
                $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
            }

            if (false === $expected) {
                $this->assertSame($expected, $result);
                return;
            }

            if (array_key_exists('password', $expected) && true === $expected['password']) {
                // password_hash result is unpredictable, so we cannot test its exact value
                $this->assertArrayHasKey('password', $result);
                $this->assertNotEmpty($result['password']);

                unset($expected['password']);
                unset($result['password']);
            }

            $expected = ['id' => $user_id] + $expected;
            if (array_key_exists('password_last_update', $expected) && true === $expected['password_last_update']) {
                // $_SESSION['glpi_currenttime'] was reset on login, value cannot be provided by test provider
                $expected['password_last_update'] = $_SESSION['glpi_currenttime'];
            }

            $this->assertSame($expected, $result);
        }
    }

    public function testPrepareInputForUpdateSensitiveFields(): void
    {
        $users_passwords = [
            TU_USER     => TU_PASS,
            'glpi'      => 'glpi',
            'tech'      => 'tech',
            'normal'    => 'normal',
            'post-only' => 'postonly',
        ];

        $users_matrix = [
            TU_USER => [
                TU_USER     => true,
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
            'glpi' => [
                TU_USER     => true,
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
            'tech' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => true,
                'normal'    => false, // has some more rights somewhere
                'post-only' => true,
            ],
            'normal' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => false,
                'normal'    => true,
                'post-only' => true,
            ],
            'post-only' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => false,
                'normal'    => false,
                'post-only' => true,
            ],
        ];

        $inputs = [
            'api_token'             => \bin2hex(\random_bytes(16)),
            '_reset_api_token'      => true,
            'cookie_token'          => \bin2hex(\random_bytes(16)),
            'password_forget_token' => \bin2hex(\random_bytes(16)),
            'personal_token'        => \bin2hex(\random_bytes(16)),
            '_reset_personal_token' => true,
            '_useremails'           => ['test1@example.com', 'test2@example.com'],
            '_emails'               => ['test1@example.com', 'test2@example.com'],
            'is_active'              => false,
        ];

        foreach ($users_matrix as $login => $targer_users_names) {
            $this->login($login, $users_passwords[$login]);

            foreach ($targer_users_names as $target_user_name => $can) {
                $target_user = \getItemByTypeName(User::class, $target_user_name);

                foreach ($inputs as $key => $value) {
                    $output = $target_user->prepareInputForUpdate(['id' => $target_user->getID(), $key => $value]);
                    if (is_array($output)) {
                        $this->assertEquals($can, \array_key_exists($key, $output));
                    } else {
                        $this->assertFalse($can);
                        $this->assertFalse($output);
                        $this->hasSessionMessages(ERROR, [
                            sprintf(
                                __('You are not allowed to update the following fields: %s'),
                                $key
                            ),
                        ]);
                    }
                }
            }
        }

        // Filtering of sensitive fields is not done if no session is active (cron case)
        $this->logout();
        foreach ([TU_USER, 'glpi', 'tech', 'normal', 'post-only'] as $target_user_name) {
            $target_user = \getItemByTypeName(User::class, $target_user_name);

            foreach ($inputs as $key => $value) {
                $output = $target_user->prepareInputForUpdate(['id' => $target_user->getID(), $key => $value]);
                $this->assertEquals(true, \array_key_exists($key, $output));
            }
        }
    }

    public function testPost_addItem()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);

        $user = new User();
        ;

        //user with a profile
        $pid = getItemByTypeName('Profile', 'Technician', true);
        $uid = (int) $user->add([
            'name'         => 'create_user',
            '_profiles_id' => $pid,
        ]);
        $this->assertGreaterThan(0, $uid);

        $this->assertTrue($user->getFromDB($uid));
        $this->assertSame('create_user', $user->fields['name']);
        $this->assertSame(0, $user->fields['profiles_id']);

        $puser = new Profile_User();
        $this->assertTrue($puser->getFromDBByCrit(['users_id' => $uid]));
        $this->assertSame($pid, $puser->fields['profiles_id']);
        $this->assertSame($eid, $puser->fields['entities_id']);
        $this->assertSame(0, $puser->fields['is_recursive']);
        $this->assertSame(0, $puser->fields['is_dynamic']);

        $pid = (int) \Profile::getDefault();
        $this->assertGreaterThan(0, $pid);

        //user without a profile (will take default one)
        $uid2 = (int) $user->add([
            'name' => 'create_user2',
        ]);
        $this->assertGreaterThan(0, $uid2);

        $this->assertTrue($user->getFromDB($uid2));
        $this->assertSame('create_user2', $user->fields['name']);
        $this->assertSame(0, $user->fields['profiles_id']);

        $puser = new Profile_User();
        $this->assertTrue($puser->getFromDBByCrit(['users_id' => $uid2]));
        $this->assertSame($pid, $puser->fields['profiles_id']);
        $this->assertSame($eid, $puser->fields['entities_id']);
        $this->assertSame(0, $puser->fields['is_recursive']);
        $this->assertSame(1, $puser->fields['is_dynamic']);

        //user with entity not recursive
        $eid2 = (int) getItemByTypeName('Entity', '_test_child_1', true);
        $this->assertGreaterThan(0, $eid2);
        $uid3 = (int) $user->add([
            'name'         => 'create_user3',
            '_entities_id' => $eid2,
        ]);
        $this->assertGreaterThan(0, $uid3);

        $this->assertTrue($user->getFromDB($uid3));
        $this->assertSame('create_user3', $user->fields['name']);

        $puser = new Profile_User();
        $this->assertTrue($puser->getFromDBByCrit(['users_id' => $uid3]));
        $this->assertSame($pid, $puser->fields['profiles_id']);
        $this->assertSame($eid2, $puser->fields['entities_id']);
        $this->assertSame(0, $puser->fields['is_recursive']);
        $this->assertSame(1, $puser->fields['is_dynamic']);

        //user with entity recursive
        $uid4 = $user->add([
            'name'            => 'create_user4',
            '_entities_id'    => $eid2,
            '_is_recursive'   => 1,
        ]);
        $this->assertGreaterThan(0, $uid4);

        $this->assertTrue($user->getFromDB($uid4));
        $this->assertSame('create_user4', $user->fields['name']);

        $puser = new Profile_User();
        $this->assertTrue($puser->getFromDBByCrit(['users_id' => $uid4]));
        $this->assertSame($pid, $puser->fields['profiles_id']);
        $this->assertSame($eid2, $puser->fields['entities_id']);
        $this->assertSame(1, $puser->fields['is_recursive']);
        $this->assertSame(1, $puser->fields['is_dynamic']);
    }

    public function testClone()
    {
        $this->login();

        $user = new User();
        ;

        // Create user with profile
        $uid = $user->add([
            'name'         => 'create_user',
            '_profiles_id' => (int) getItemByTypeName('Profile', 'Self-Service', true),
        ]);
        $this->assertGreaterThan(0, $uid);

        $this->setEntity('_test_root_entity', true);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        // Add authorizations
        $puser = new Profile_User();
        $this->assertGreaterThan(
            0,
            $puser->add([
                'users_id'      => $uid,
                'profiles_id'   => (int) getItemByTypeName('Profile', 'Technician', true),
                'entities_id'   => (int) getItemByTypeName('Entity', '_test_child_1', true),
                'is_recursive'  => 0,
            ])
        );

        $this->assertGreaterThan(
            0,
            $puser->add([
                'users_id'      => $uid,
                'profiles_id'   => (int) getItemByTypeName('Profile', 'Admin', true),
                'entities_id'   => (int) getItemByTypeName('Entity', '_test_child_2', true),
                'is_recursive'  => 1,
            ])
        );

        $puser_original = $puser->find(['users_id' => $uid]);

        // Test item cloning
        $added = $user->clone();
        $this->assertGreaterThan(0, (int) $added);

        $clonedUser = new User();
        $this->assertTrue($clonedUser->getFromDB($added));

        $fields = $user->fields;

        // Check the values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($user->getField($k), $clonedUser->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new DateTime($clonedUser->getField($k));
                    $expectedDate = new DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertSame("create_user-copy", $clonedUser->getField($k));
                    break;
                default:
                    $this->assertEquals($user->getField($k), $clonedUser->getField($k));
            }
        }

        // Check authorizations
        foreach ($puser_original as $row) {
            $this->assertTrue($puser->getFromDBByCrit([
                'users_id'      => $added,
                'profiles_id'   => $row['profiles_id'],
                'entities_id'   => $row['entities_id'],
                'is_recursive'  => $row['is_recursive'],
                'is_dynamic'    => $row['is_dynamic'],
            ]));
        }
    }

    public function testGetFromDBbyDn()
    {
        $user = new User();
        ;
        $dn = 'user=user_with_dn,dc=test,dc=glpi-project,dc=org';

        $uid = $user->add([
            'name'      => 'user_with_dn',
            'user_dn'   => $dn,
        ]);
        $this->assertGreaterThan(0, $uid);

        $this->assertTrue($user->getFromDBbyDn($dn));
        $this->assertSame($uid, $user->fields['id']);
        $this->assertSame('user_with_dn', $user->fields['name']);
    }

    public function testGetFromDBbySyncField()
    {
        $user = new User();
        ;
        $sync_field = 'abc-def-ghi';

        $uid = $user->add([
            'name'         => 'user_with_syncfield',
            'sync_field'   => $sync_field,
        ]);

        $this->assertGreaterThan(0, $uid);

        $this->assertTrue($user->getFromDBbySyncField($sync_field));
        $this->assertSame($uid, $user->fields['id']);
        $this->assertSame('user_with_syncfield', $user->fields['name']);
    }

    public function testGetFromDBbyName()
    {
        $user = new User();
        ;
        $name = 'user_with_name';

        $uid = $user->add([
            'name' => $name,
        ]);

        $this->assertGreaterThan(0, $uid);

        $this->assertTrue($user->getFromDBbyName($name));
        $this->assertSame($uid, $user->fields['id']);
    }

    public function testGetFromDBbyNameAndAuth()
    {
        $user = new User();
        ;
        $name = 'user_with_auth';

        $uid = $user->add([
            'name'      => $name,
            'authtype'  => \Auth::DB_GLPI,
            'auths_id'  => 12,
        ]);

        $this->assertGreaterThan(0, $uid);

        $this->assertTrue($user->getFromDBbyNameAndAuth($name, \Auth::DB_GLPI, 12));
        $this->assertSame($uid, $user->fields['id']);
        $this->assertSame($name, $user->fields['name']);
    }

    public static function rawNameProvider()
    {
        return [
            [
                'input'     => ['name' => 'myname'],
                'rawname'   => 'myname',
            ], [
                'input'     => [
                    'name'      => 'anothername',
                    'realname'  => 'real name',
                ],
                'rawname'      => 'real name',
            ], [
                'input'     => [
                    'name'      => 'yet another name',
                    'firstname' => 'first name',
                ],
                'rawname'   => 'yet another name',
            ], [
                'input'     => [
                    'name'      => 'yet another one',
                    'realname'  => 'real name',
                    'firstname' => 'first name',
                ],
                'rawname'   => 'real name first name',
            ],
        ];
    }

    #[DataProvider('rawNameProvider')]
    public function testGetFriendlyName($input, $rawname)
    {
        $user = new User();
        $this->assertSame('', $user->getFriendlyName());

        $uid = $user->add($input);
        $this->assertGreaterThan(0, $uid);
        $this->assertTrue($user->getFromDB($uid));
        $this->assertSame($rawname, $user->getFriendlyName());
    }

    public function testBlankPassword()
    {
        $input = [
            'name'      => 'myname',
            'password'  => 'mypass',
            'password2' => 'mypass',
        ];

        $user = new User();
        $uid = $user->add($input);
        $this->assertGreaterThan(0, $uid);
        $this->assertTrue($user->getFromDB($uid));
        $this->assertSame('myname', $user->fields['name']);
        $this->assertEquals(60, strlen($user->fields['password']));
        $this->assertStringStartsWith('$2y$', $user->fields['password']);

        $user->blankPassword();
        $this->assertTrue($user->getFromDB($uid));
        $this->assertSame('myname', $user->fields['name']);
        $this->assertSame('', $user->fields['password']);
    }

    public function testPre_updateInDB()
    {
        $this->login();
        $user = new User();

        $uid = $user->add([
            'name' => 'preupdate_user',
        ]);
        $this->assertGreaterThan(0, $uid);
        $this->assertTrue($user->getFromDB($uid));

        $this->assertTrue($user->update([
            'id'     => $uid,
            'name'   => 'preupdate_user_edited',
        ]));
        $this->hasNoSessionMessages([ERROR, WARNING]);

        //can update with same name when id is identical
        $this->assertTrue($user->update([
            'id'     => $uid,
            'name'   => 'preupdate_user_edited',
        ]));
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $this->assertGreaterThan(
            0,
            $user->add(['name' => 'do_exist'])
        );
        $this->assertTrue($user->update([
            'id'     => $uid,
            'name'   => 'do_exist',
        ]));
        $this->hasSessionMessages(ERROR, ['Unable to update login. A user already exists.']);

        $this->assertTrue($user->getFromDB($uid));
        $this->assertSame('preupdate_user_edited', $user->fields['name']);

        $this->assertTrue($user->update([
            'id'     => $uid,
            'name'   => 'in+valid',
        ]));
        $this->hasSessionMessages(ERROR, ['The login is not valid. Unable to update login.']);
    }

    public function testGetIdByName()
    {
        $user = new User();

        $uid = $user->add(['name' => 'id_by_name']);
        $this->assertGreaterThan(0, $uid);
        $this->assertSame($uid, $user->getIdByName('id_by_name'));
    }

    public function testGetIdByField()
    {
        $user = new User();

        $uid = $user->add([
            'name'   => 'id_by_field',
            'phone'  => '+33123456789',
        ]);
        $this->assertGreaterThan(0, $uid);
        $this->assertSame($uid, $user->getIdByField('phone', '+33123456789'));

        $this->assertGreaterThan(
            0,
            $user->add([
                'name'   => 'id_by_field2',
                'phone'  => '+33123456789',
            ])
        );
        $this->assertFalse($user->getIdByField('phone', '+33123456789'));
        $this->assertFalse($user->getIdByField('phone', 'donotexists'));
    }

    public function testgetAdditionalMenuOptions()
    {
        $this->login();

        $user = new User();
        $this->assertCount(1, $user->getAdditionalMenuOptions());
        $this->assertArrayHasKey('ldap', $user->getAdditionalMenuOptions());

        $this->login('normal', 'normal');
        $user = new User();
        $this->assertFalse($user->getAdditionalMenuOptions());
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

    public function testPasswordExpirationMethods()
    {
        global $CFG_GLPI;

        $data = $this->passwordExpirationMethodsProvider();
        foreach ($data as $row) {
            $creation_date = $row['creation_date'];
            $last_update = $row['last_update'];
            $expiration_delay = $row['expiration_delay'];
            $expiration_notice = $row['expiration_notice'];
            $expected_expiration_time = $row['expected_expiration_time'];
            $expected_should_change_password = $row['expected_should_change_password'];
            $expected_has_password_expire = $row['expected_has_password_expire'];

            $user = new User();
            $username = 'prepare_for_update_' . mt_rand();
            $user_id = $user->add(
                [
                    'date_creation' => $creation_date,
                    'name' => $username,
                    'password' => 'pass',
                    'password2' => 'pass',
                ]
            );
            $this->assertGreaterThan(0, $user_id);
            $this->assertTrue($user->update(['id' => $user_id, 'password_last_update' => $last_update]));
            $this->assertTrue($user->getFromDB($user->fields['id']));

            $cfg_backup = $CFG_GLPI;
            $CFG_GLPI['password_expiration_delay'] = $expiration_delay;
            $CFG_GLPI['password_expiration_notice'] = $expiration_notice;

            $expiration_time = $user->getPasswordExpirationTime();
            $should_change_password = $user->shouldChangePassword();
            $has_password_expire = $user->hasPasswordExpired();

            $CFG_GLPI = $cfg_backup;

            $this->assertEquals($expected_expiration_time, $expiration_time);
            $this->assertEquals($expected_should_change_password, $should_change_password);
            $this->assertEquals($expected_has_password_expire, $has_password_expire);
        }
    }


    public static function cronPasswordExpirationNotificationsProvider()
    {
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

    #[DataProvider('cronPasswordExpirationNotificationsProvider')]
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

        // create 10 users with different password_last_update dates
        // first has its password set 1 day ago
        // second has its password set 11 day ago
        // and so on
        // tenth has its password set 91 day ago
        $user = new User();
        for ($i = 1; $i < 100; $i += 10) {
            $user_id = $user->add(
                [
                    'name'     => 'cron_user_' . mt_rand(),
                    'authtype' => \Auth::DB_GLPI,
                ]
            );
            $this->assertGreaterThan(0, $user_id);
            //FIXME: why add then immeditaly update? Should not last_update set directly in add?
            $this->assertTrue(
                $user->update(
                    [
                        'id' => $user_id,
                        'password_last_update' => date('Y-m-d H:i:s', strtotime('-' . $i . ' days')),
                    ]
                )
            );
        }

        $crontask = new \CronTask();
        $this->assertTrue($crontask->getFromDBbyName(User::getType(), 'passwordexpiration'));
        $crontask->fields['param'] = $cron_limit;

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_delay'] = $expiration_delay;
        $CFG_GLPI['password_expiration_notice'] = $notice_delay;
        $CFG_GLPI['password_expiration_lock_delay'] = $lock_delay;
        $CFG_GLPI['use_notifications']  = true;
        $CFG_GLPI['notifications_ajax'] = 1;
        $result = User::cronPasswordExpiration($crontask);
        $CFG_GLPI = $cfg_backup;

        $this->assertEquals($expected_result, $result);
        $this->assertEquals(
            $expected_notifications_count,
            countElementsInTable(\Alert::getTable(), ['itemtype' => User::getType()])
        );
        $DB->delete(\Alert::getTable(), ['itemtype' => User::getType()]); // reset alerts

        $user_crit = [
            'authtype'  => \Auth::DB_GLPI,
            'is_active' => 0,
        ];
        $this->assertEquals($expected_lock_count, countElementsInTable(User::getTable(), $user_crit));
        $DB->update(User::getTable(), ['is_active' => 1], $user_crit); // reset users
    }

    protected function providerGetSubstitutes()
    {
        // remove all substitutes, if any
        $validator_substitute = new \ValidatorSubstitute();
        $testedClass = User::class;
        $validator_substitute->deleteByCriteria([
            'users_id' => $testedClass::getIdByName('normal'),
        ]);
        yield [
            'input' => $testedClass::getIdByName('normal'),
            'expected' => [],
        ];

        $this->login('normal', 'normal');
        $validator_substitute->updateSubstitutes([
            'users_id' => $testedClass::getIdByName('normal'),
            'substitutes' => [$testedClass::getIdByName('glpi')],
        ]);
        yield [
            'input' => $testedClass::getIdByName('normal'),
            'expected' => [$testedClass::getIdByName('glpi')],
        ];

        $validator_substitute->updateSubstitutes([
            'users_id' => $testedClass::getIdByName('normal'),
            'substitutes' => [$testedClass::getIdByName('glpi'), 3],
        ]);
        yield [
            'input' => $testedClass::getIdByName('normal'),
            'expected' => [$testedClass::getIdByName('glpi'), 3],
        ];
    }


    public function testGetSubstitutes(): void
    {
        $data = $this->providerGetSubstitutes();
        foreach ($data as $row) {
            $input = $row['input'];
            $expected = $row['expected'];

            $instance = new User();
            $instance->getFromDB($input);
            $output = $instance->getSubstitutes();
            $this->assertEquals($expected, $output);
        }
    }

    protected function providerGetDelegators()
    {
        // remove all delegators, if any
        $validator_substitute = new \ValidatorSubstitute();
        $testedClass = User::class;
        $validator_substitute->deleteByCriteria([
            'users_id_substitute' => $testedClass::getIdByName('normal'),
        ]);
        yield [
            'input' => $testedClass::getIdByName('normal'),
            'expected' => [],
        ];

        $this->login('glpi', 'glpi');
        $validator_substitute->updateSubstitutes([
            'users_id' => $testedClass::getIdByName('glpi'),
            'substitutes' => [$testedClass::getIdByName('normal')],
        ]);
        yield [
            'input' => $testedClass::getIdByName('normal'),
            'expected' => [$testedClass::getIdByName('glpi')],
        ];

        $this->login('post-only', 'postonly');
        $validator_substitute->updateSubstitutes([
            'users_id' => $testedClass::getIdByName('post-only'),
            'substitutes' => [$testedClass::getIdByName('normal')],
        ]);
        yield [
            'input' => $testedClass::getIdByName('normal'),
            'expected' => [$testedClass::getIdByName('glpi'), $testedClass::getIdByName('post-only')],
        ];
    }

    public function testGetDelegators(): void
    {
        $data = $this->providerGetDelegators();
        foreach ($data as $row) {
            $input = $row['input'];
            $expected = $row['expected'];

            $instance = new User();
            $instance->getFromDB($input);
            $output = $instance->getDelegators($input);
            $this->assertEquals($expected, $output);
        }
    }

    protected function providerIsSubstituteOf()
    {
        $validator_substitute = new \ValidatorSubstitute();
        $testedClass = User::class;
        $validator_substitute->deleteByCriteria([
            'users_id' => $testedClass::getIdByName('normal'),
        ]);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => false,
            'expected'           => false,
        ];

        $validator_substitute->add([
            'users_id' => $testedClass::getIdByName('normal'),
            'users_id_substitute' => $testedClass::getIdByName('glpi'),
        ]);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => false,
            'expected'           => true,
        ];

        $instance = new User();
        $success = $instance->update([
            'id' => $testedClass::getIdByName('normal'),
            'substitution_end_date' => '1999-01-01 12:00:00',
        ]);
        $this->assertTrue($success);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];

        $success = $instance->update([
            'id' => $testedClass::getIdByName('normal'),
            'substitution_end_date' => '',
            'substitution_start_date' => (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($success);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];

        $success = $instance->update([
            'id' => $testedClass::getIdByName('normal'),
            'substitution_end_date' => (new DateTime())->add(new DateInterval('P2Y'))->format('Y-m-d H:i:s'),
            'substitution_start_date' => (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($success);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];

        $success = $instance->update([
            'id' => $testedClass::getIdByName('normal'),
            'substitution_end_date' => (new DateTime())->sub(new DateInterval('P1Y'))->format('Y-m-d H:i:s'),
            'substitution_start_date' => (new DateTime())->sub(new DateInterval('P2Y'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($success);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];

        $success = $instance->update([
            'id' => $testedClass::getIdByName('normal'),
            'substitution_end_date' => (new DateTime())->add(new DateInterval('P1M'))->format('Y-m-d H:i:s'),
            'substitution_start_date' => (new DateTime())->sub(new DateInterval('P1M'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($success);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => true,
        ];

        $success = $instance->update([
            'id' => $testedClass::getIdByName('normal'),
            'substitution_start_date' => (new DateTime())->sub(new DateInterval('P1M'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($success);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => true,
        ];

        $success = $instance->update([
            'id' => $testedClass::getIdByName('normal'),
            'substitution_end_date' => (new DateTime())->add(new DateInterval('P1M'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($success);
        yield [
            'users_id'           => $testedClass::getIdByName('glpi'),
            'users_id_delegator' => $testedClass::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => true,
        ];
    }

    public function testIsSubstituteOf(): void
    {
        $data = $this->providerIsSubstituteOf();
        foreach ($data as $row) {
            $users_id = $row['users_id'];
            $users_id_delegator = $row['users_id_delegator'];
            $use_date_range = $row['use_date_range'];
            $expected = $row['expected'];

            $instance = new User();
            $instance->getFromDB($users_id);
            $output = $instance->isSubstituteOf($users_id_delegator, $use_date_range);
            $this->assertEquals($expected, $output);
        }
    }

    public function testGetUserByForgottenPasswordToken()
    {
        global $DB, $CFG_GLPI;

        $user = new User();
        // Set the password_forget_token of TU_USER to some random hex string and set the password_forget_token_date to now - 5 days
        $token = bin2hex(random_bytes(16));
        $this->assertTrue(
            $DB->update(
                'glpi_users',
                [
                    'password_forget_token' => $token,
                    'password_forget_token_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                ],
                [
                    'id' => getItemByTypeName('User', TU_USER, true),
                ]
            )
        );

        // Set password_init_token_delay config option to 1 day
        $CFG_GLPI['password_init_token_delay'] = DAY_TIMESTAMP;

        $this->assertNull(User::getUserByForgottenPasswordToken($token));

        // Set password_init_token_delay config option to 10 days
        $CFG_GLPI['password_init_token_delay'] = DAY_TIMESTAMP * 10;

        $this->assertNotNull(User::getUserByForgottenPasswordToken($token));
    }

    /**
     * Data provider for testValidatePassword
     *
     * @return iterable
     */
    protected function testValidatePasswordProvider(): iterable
    {
        global $CFG_GLPI;

        // Load test subject
        $user = getItemByTypeName('User', TU_USER);

        // Password security must be disabled by default
        $this->assertFalse((bool) $CFG_GLPI['use_password_security']);
        yield [$user, 'mypass'];

        // Enable security
        $CFG_GLPI['use_password_security'] = 1;
        $this->assertEquals(8, (int) $CFG_GLPI['password_min_length']);
        $this->assertEquals(1, (int) $CFG_GLPI['password_need_number']);
        $this->assertEquals(1, (int) $CFG_GLPI['password_need_letter']);
        $this->assertEquals(1, (int) $CFG_GLPI['password_need_caps']);
        $this->assertEquals(1, (int) $CFG_GLPI['password_need_symbol']);
        $errors = [
            'Password too short!',
            'Password must include at least a digit!',
            'Password must include at least a lowercase letter!',
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!',
        ];
        yield [$user, '', $errors];

        // Increase password length
        $errors = [
            'Password must include at least a digit!',
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!',
        ];
        yield [$user, 'mypassword', $errors];

        // Reduce minimum length
        $CFG_GLPI['password_min_length'] = strlen('mypass');
        $errors = [
            'Password must include at least a digit!',
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!',
        ];
        yield [$user, 'mypass', $errors];
        $CFG_GLPI['password_min_length'] = 8; //reset

        // Add digit to password
        $errors = [
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!',
        ];
        yield [$user, 'my1password', $errors];

        // Disable digit validation
        $CFG_GLPI['password_need_number'] = 0;
        $errors = [
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!',
        ];
        yield [$user, 'mypassword', $errors];
        $CFG_GLPI['password_need_number'] = 1; //reset

        // Add uppercase letter to password
        yield [$user, 'my1paSsword', ['Password must include at least a symbol!']];

        // Disable uppercase validation
        $CFG_GLPI['password_need_caps'] = 0;
        yield [$user, 'my1password', ['Password must include at least a symbol!']];
        $CFG_GLPI['password_need_caps'] = 1; //reset

        // Add symbol to password
        yield [$user, 'my1paSsw@rd'];

        // Disable password validation
        $CFG_GLPI['password_need_symbol'] = 0;
        yield [$user, 'my1paSsword'];
        $CFG_GLPI['password_need_symbol'] = 1; //reset

        // Test password history setting
        $this->login();
        $CFG_GLPI['use_password_security'] = 0; // Disable others checks
        $CFG_GLPI['non_reusable_passwords_count'] = 3; // Check last 3 password (current + previous 2)
        $password1 = TU_PASS; // Current password
        $password2 = "P@ssword2"; // First password change
        $password3 = "P@ssword3"; // Second password change
        $password4 = "P@ssword4"; // Not yet used password
        $this->updateItem('User', $user->getID(), ['password' => $password2, 'password2' => $password2], ['password', 'password2']);
        $this->updateItem('User', $user->getID(), ['password' => $password3, 'password2' => $password3], ['password', 'password2']);
        $this->assertTrue($user->getFromDB($user->fields['id']));

        // Last 3 passwords should not work
        yield [$user, $password1, ["Password was used too recently."]];
        yield [$user, $password2, ["Password was used too recently."]];
        yield [$user, $password3, ["Password was used too recently."]];

        // Never used before password, should work
        yield [$user, $password4];
    }

    /**
     * Tests for $user->validatePassword()
     *
     * @param User  $user     Test subject
     * @param string $password Password to validate
     * @param array  $errors   Expected errors
     *
     * @return void
     */
    public function testValidatePassword(): void
    {
        $data = $this->testValidatePasswordProvider();
        foreach ($data as $row) {
            $user = $row[0];
            $password = $row[1];
            $errors = $row[2] ?? [];

            $expected = count($errors) === 0;
            $password_errors = [];
            $this->assertEquals($expected, $user->validatePassword($password, $password_errors));
            $this->assertEquals($errors, $password_errors);
        }
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
        $this->assertTrue($super_admin->isLastSuperAdminProfile());

        // Default: 3 users with super admin account authorizations
        $users = (new User())->find([
            'id' => new QuerySubQuery([
                'SELECT' => 'users_id',
                'FROM'   => Profile_User::getTable(),
                'WHERE'  => [
                    'profiles_id' => $super_admin->fields['id'],
                ],
            ]),
        ]);
        $this->assertCount(4, $users);
        $this->assertEquals(
            ['glpi', "e2e_tests", TU_USER, "jsmith123"],
            array_column($users, 'name')
        );

        $glpi = getItemByTypeName('User', 'glpi');
        $tu_user = getItemByTypeName('User', TU_USER);
        $jsmith123 = getItemByTypeName('User', 'jsmith123');
        $e2e_tests = getItemByTypeName('User', 'e2e_tests');

        // Delete other users
        $this->login('glpi', 'glpi');
        $this->assertTrue($tu_user->canDeleteItem());
        $this->assertTrue($tu_user->delete(['id' => $tu_user->getID()]));
        $this->assertTrue($jsmith123->canDeleteItem());
        $this->assertTrue($jsmith123->delete(['id' => $jsmith123->getID()]));
        $this->assertTrue($e2e_tests->canDeleteItem());
        $this->assertTrue($e2e_tests->delete(['id' => $e2e_tests->getID()]));

        // Last user, can't be deleted or disabled
        $this->assertTrue($glpi->update([
            'id'        => $glpi->getID(),
            'is_active' => false,
        ]));
        $this->hasSessionMessages(ERROR, [
            "Can&#039;t set user as inactive as it is the only remaining super administrator.",
        ]);
        $glpi->getFromDB($glpi->getId());
        $this->assertEquals(true, (bool) $glpi->fields['is_active']);
        $this->assertFalse($glpi->canDeleteItem());

        // Can still be deleted by calling delete directly, maybe it should not be possible ?
        $this->assertTrue($glpi->delete(['id' => $glpi->getID()]));
    }

    public function testUserPreferences()
    {
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            // Cannot use `@php 8.0` to skip test as we want to ensure that test suite fails if methods are skipped
            // (to detect missing extensions for instance).
            $this->assertTrue(true);
            return;
        }

        $user = new User();
        $users_id = $user->add([
            'name' => 'for preferences',
            'login' => 'for preferences',
            'password' => 'for preferences',
            'password2' => 'for preferences',
            'profiles_id' => 4,
        ]);
        $this->assertGreaterThan(0, $users_id);

        $this->login('for preferences', 'for preferences');
        $this->assertTrue($user->getFromDB($users_id));
        $this->assertNull($user->fields['show_count_on_tabs']);
        $this->assertEquals(1, $_SESSION['glpishow_count_on_tabs']);

        $itil_layout_1 = '{"collapsed":"true","expanded":"false","items":{"item-main":"false","actors":"false","items":"false","service-levels":"false","linked_tickets":"false"}}';
        $this->assertTrue(
            $user->update([
                'id' => $users_id,
                'show_count_on_tabs' => '0',
                'itil_layout' => $itil_layout_1,
            ])
        );

        // pref should be updated even without logout/login
        $this->assertEquals(0, $_SESSION['glpishow_count_on_tabs']);
        $this->assertEquals($itil_layout_1, $_SESSION['glpiitil_layout']);

        // logout/login and check prefs
        $this->logOut();
        $this->login('for preferences', 'for preferences');
        $this->assertEquals(0, $_SESSION['glpishow_count_on_tabs']);
        $this->assertEquals($itil_layout_1, $_SESSION['glpiitil_layout']);


        $this->assertTrue($user->getFromDB($users_id));
        $this->assertEquals(0, $user->fields['show_count_on_tabs']);
        $this->assertEquals($itil_layout_1, $user->fields['itil_layout']);

        $itil_layout_2 = '{"collapsed":"false","expanded":"true"}';
        $this->assertTrue(
            $user->update([
                'id' => $users_id,
                'show_count_on_tabs' => '1',
                'itil_layout' => $itil_layout_2,
            ])
        );

        // pref should be updated even without logout/login
        $this->assertEquals(1, $_SESSION['glpishow_count_on_tabs']);
        $this->assertEquals($itil_layout_2, $_SESSION['glpiitil_layout']);

        // logout/login and check prefs
        $this->logOut();
        $this->login('for preferences', 'for preferences');
        $this->assertEquals(1, $_SESSION['glpishow_count_on_tabs']);
        $this->assertEquals($itil_layout_2, $_SESSION['glpiitil_layout']);

        $this->assertTrue($user->getFromDB($users_id));
        $this->assertNull($user->fields['show_count_on_tabs']);
        $this->assertEquals($itil_layout_2, $user->fields['itil_layout']);
    }

    /**
     * Test that user_dn_hash is correctly set on user creation and update
     *
     * @return void
     */
    public function testUserDnIsHashedOnAddAndUpdate(): void
    {
        // Create user without dn and check that user_dn_hash is not set
        $user = $this->createItem('User', [
            'name'      => __FUNCTION__,
        ]);
        $this->assertNull($user->fields['user_dn']);
        $this->assertNull($user->fields['user_dn_hash']);

        // Create user with dn and check that user_dn_hash is set
        $dn = 'user=' . __FUNCTION__ . '_created,dc=R&D,dc=glpi-project,dc=org';
        $user = $this->createItem('User', [
            'name'      => __FUNCTION__ . '_created',
            'user_dn'   => $dn,
        ]);
        $this->assertEquals(md5($dn), $user->fields['user_dn_hash']);

        // Update user dn and check that user_dn_hash is updated
        $dn = 'user=' . __FUNCTION__ . '_updated,dc=R&D,dc=glpi-project,dc=org';
        $this->updateItem('User', $user->getID(), [
            'user_dn'   => $dn,
        ]);
        $user->getFromDB($user->getID());
        $this->assertEquals(md5($dn), $user->fields['user_dn_hash']);

        // Set user_dn to empty and check that user_dn_hash is set to null
        $this->updateItem('User', $user->getID(), [
            'user_dn'   => '',
        ]);
        $user->getFromDB($user->getID());
        $this->assertNull($user->fields['user_dn_hash']);

        // Set user_dn to null and check that user_dn_hash is set to null
        $this->updateItem('User', $user->getID(), [
            'user_dn'   => null,
        ]);
        $user->getFromDB($user->getID());
        $this->assertNull($user->fields['user_dn_hash']);
    }

    /**
     * Test that user_dn_hash is correctly used in getFromDBbyDn method
     *
     * @return void
     */
    public function testUserDnHashIsUsedInGetFromDBbyDn(): void
    {
        global $DB;

        $retrievedUser = new User();

        // Get a user with a bad dn
        $this->assertFalse($retrievedUser->getFromDBbyDn(__FUNCTION__));
        $this->assertTrue($retrievedUser->isNewItem());

        // Create a user with a dn
        $dn = 'user=' . __FUNCTION__ . ',dc=R&D,dc=glpi-project,dc=org';
        $user = $this->createItem('User', [
            'name'      => __FUNCTION__,
            'user_dn'   => $dn,
        ]);

        // Retrieve the user using getFromDBbyDn method
        $this->assertTrue($retrievedUser->getFromDBbyDn($dn));
        $this->assertFalse($retrievedUser->isNewItem());

        // Unset user_dn to check that user_dn_hash is used
        $DB->update(
            User::getTable(),
            ['user_dn' => ''],
            ['id' => $user->getID()]
        );

        // Retrieve the user using getFromDBbyDn and check if user_dn_hash is used
        $this->assertTrue($retrievedUser->getFromDBbyDn($dn));
        $this->assertFalse($retrievedUser->isNewItem());
        $this->assertEmpty($retrievedUser->fields['user_dn']);
    }

    public static function toggleSavedSearchPinProvider(): iterable
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

    #[DataProvider('toggleSavedSearchPinProvider')]
    public function testToggleSavedSearchPin(string $initial_db_value, string $itemtype, bool $success, string $result_db_value): void
    {
        $user = $this->createItem(
            User::class,
            [
                'name'                  => __FUNCTION__ . (string) mt_rand(),
                'savedsearches_pinned'  => $initial_db_value,
            ]
        );

        $this->assertEquals($success, $user->toggleSavedSearchPin($itemtype));
        $this->assertTrue($user->getFromDb($user->getID()));
        $this->assertEquals($result_db_value, $user->fields['savedsearches_pinned']);

        // result value in DB is always a valid JSON string
        $this->assertEquals(
            json_decode($result_db_value, true),
            importArrayFromDB($user->fields['savedsearches_pinned'])
        );
    }

    public function testUnsetUndisclosedFields()
    {
        $users_passwords = [
            TU_USER     => TU_PASS,
            'glpi'      => 'glpi',
            'tech'      => 'tech',
            'normal'    => 'normal',
            'post-only' => 'postonly',
        ];

        $users_matrix = [
            TU_USER => [
                TU_USER     => true,
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
            'glpi' => [
                TU_USER     => true,
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
            'tech' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => true,
                'normal'    => false, // has some more rights somewhere
                'post-only' => true,
            ],
            'normal' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => false,
                'normal'    => true,
                'post-only' => false, // no update right
            ],
            'post-only' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => false,
                'normal'    => false,
                'post-only' => true,
            ],
        ];

        foreach ($users_matrix as $login => $targer_users_names) {
            $this->login($login, $users_passwords[$login]);

            foreach ($targer_users_names as $target_user_name => $disclose) {
                $target_user = \getItemByTypeName(User::class, $target_user_name);

                $fields = $target_user->fields;
                $this->assertArrayHasKey('password', $fields);
                $this->assertArrayHasKey('personal_token', $fields);
                $this->assertArrayHasKey('api_token', $fields);
                $this->assertArrayHasKey('cookie_token', $fields);
                $this->assertArrayHasKey('password_forget_token', $fields);
                $this->assertArrayHasKey('password_forget_token_date', $fields);

                User::unsetUndisclosedFields($fields);

                $this->assertEquals(false, \array_key_exists('password', $fields));
                $this->assertEquals(false, \array_key_exists('personal_token', $fields));
                $this->assertEquals(false, \array_key_exists('api_token', $fields));
                $this->assertEquals(false, \array_key_exists('cookie_token', $fields));
                $this->assertEquals($disclose, \array_key_exists('password_forget_token', $fields));
                $this->assertEquals($disclose, \array_key_exists('password_forget_token_date', $fields));
            }
        }
    }

    public function testUnsetUndisclosedFieldsWithPartialFields()
    {
        $fields = [
            //'id' is missing
            'name'                       => 'test',
            'password'                   => \bin2hex(\random_bytes(16)),
            'api_token'                  => \bin2hex(\random_bytes(16)),
            'cookie_token'               => \bin2hex(\random_bytes(16)),
            'password_forget_token'      => \bin2hex(\random_bytes(16)),
            'personal_token'             => \bin2hex(\random_bytes(16)),
            'password_forget_token_date' => '2024-10-25 13:15:12',
        ];

        User::unsetUndisclosedFields($fields);

        $this->assertEquals(['name' => 'test'], $fields);
    }

    public function testReapplyRightRules()
    {
        $this->login();
        $entities_id = $this->getTestRootEntity(true);

        $user = new User();
        $user->getFromDB($_SESSION['glpiID']);

        // Create a group that will be used to add a profile
        $group = new \Group();
        $groups_id = $group->add([
            'name' => __FUNCTION__,
            'entities_id' => $entities_id,
        ]);

        // Create a profile that will be added to the user
        $profile = new \Profile();
        $profiles_id = $profile->add([
            'name' => __FUNCTION__,
        ]);

        // Create a rule that associates the profile to users with the group
        $rule = new \RuleRight();
        $rules_id = $rule->add([
            'name' => __FUNCTION__,
            'entities_id' => $entities_id,
            'match' => 'AND',
        ]);
        (new \RuleCriteria())->add([
            'rules_id' => $rules_id,
            'criteria' => '_groups_id',
            'condition' => 0,
            'pattern' => $groups_id,
        ]);
        $action = new \RuleAction();
        $action->add([
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'profiles_id',
            'value' => $profiles_id,
        ]);
        $action->add([
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'entities_id',
            'value' => $entities_id,
        ]);

        $this->assertNotContains($profiles_id, Profile_User::getUserProfiles($user->getID()));

        $group_user = new \Group_User();
        $group_user_id = $group_user->add([
            'groups_id' => $groups_id,
            'users_id' => $user->getID(),
        ]);

        $user->reapplyRightRules();
        $this->assertContains($profiles_id, Profile_User::getUserProfiles($user->getID()));

        $group_user->delete(['id' => $group_user_id]);
        $user->reapplyRightRules();
        $this->assertNotContains($profiles_id, Profile_User::getUserProfiles($user->getID()));
    }

    public static function getFriendlyNameFieldsProvider()
    {
        return [
            [
                'input' => [
                    'name' => 'login_only',
                ],
                'names_format' => User::REALNAME_BEFORE,
                'expected' => 'login_only',
            ],
            [
                'input' => [
                    'name'      => 'firstname_only',
                    'firstname' => 'firstname',
                ],
                'names_format' => User::REALNAME_BEFORE,
                'expected' => 'firstname',
            ],
            [
                'input' => [
                    'name'      => 'lastname_only',
                    'realname'  => 'lastname',
                ],
                'names_format' => User::REALNAME_BEFORE,
                'expected' => 'lastname',
            ],
            [
                'input' => [
                    'name'      => 'firstname_lastname',
                    'firstname' => 'firstname',
                    'realname'  => 'lastname',
                ],
                'names_format' => User::REALNAME_BEFORE,
                'expected' => 'lastname firstname',
            ],
            [
                'input' => [
                    'name' => 'login_only',
                ],
                'names_format' => User::FIRSTNAME_BEFORE,
                'expected' => 'login_only',
            ],
            [
                'input' => [
                    'name'      => 'firstname_only',
                    'firstname' => 'firstname',
                ],
                'names_format' => User::FIRSTNAME_BEFORE,
                'expected' => 'firstname',
            ],
            [
                'input' => [
                    'name'      => 'lastname_only',
                    'realname'  => 'lastname',
                ],
                'names_format' => User::FIRSTNAME_BEFORE,
                'expected' => 'lastname',
            ],
            [
                'input' => [
                    'name'      => 'firstname_lastname',
                    'firstname' => 'firstname',
                    'realname'  => 'lastname',
                ],
                'names_format' => User::FIRSTNAME_BEFORE,
                'expected' => 'firstname lastname',
            ],
        ];
    }

    #[DataProvider('getFriendlyNameFieldsProvider')]
    public function testGetFriendlyNameFields(
        array $input,
        int $names_format,
        string $expected
    ) {
        global $DB;

        \Config::setConfigurationValues('core', ['names_format' => $names_format]);

        $user = $this->createItem('User', $input);

        $query = [
            'SELECT' => [
                User::getFriendlyNameFields(),
            ],
            'FROM' => [
                User::getTable(),
            ],
            'WHERE' => [
                'id' => $user->fields['id'],
            ],
        ];
        $result = $DB->request($query)->current();
        $this->assertSame($expected, $result['name']);
    }

    public function testChangeAuthMethod()
    {
        global $DB;

        $this->login();
        $user = $this->createItem(User::class, [
            'name' => 'testChangeAuthMethod',
            'password' => 'testChangeAuthMethod123',
            'password2' => 'testChangeAuthMethod123',
        ], ['password', 'password2']);
        $this->assertTrue(User::changeAuthMethod([$user->getID()], \Auth::DB_GLPI));
        // Password should not be empty since the auth method isn't different
        $it = $DB->request([
            'SELECT' => ['password', 'authtype', 'auths_id'],
            'FROM'   => User::getTable(),
            'WHERE'  => ['id' => $user->getID()],
        ])->current();
        $this->assertNotEmpty($it['password']);
        $this->assertEquals(\Auth::DB_GLPI, $it['authtype']);
        $this->assertEquals(0, $it['auths_id']);

        $this->assertTrue(User::changeAuthMethod([$user->getID()], \Auth::LDAP, 1));
        // Password should be empty
        $it = $DB->request([
            'SELECT' => ['password', 'authtype', 'auths_id'],
            'FROM'   => User::getTable(),
            'WHERE'  => ['id' => $user->getID()],
        ])->current();
        $this->assertEmpty($it['password']);
        $this->assertEquals(\Auth::LDAP, $it['authtype']);
        $this->assertEquals(1, $it['auths_id']);

        $this->assertTrue($DB->update(
            User::getTable(),
            ['password' => 'testChangeAuthMethod123'],
            ['id' => $user->getID()]
        ));
        $this->assertTrue(User::changeAuthMethod([$user->getID()], \Auth::LDAP, 2));
        // Changing servers of the same type should also empty the password
        $it = $DB->request([
            'SELECT' => ['password', 'authtype', 'auths_id'],
            'FROM'   => User::getTable(),
            'WHERE'  => ['id' => $user->getID()],
        ])->current();
        $this->assertEmpty($it['password']);
        $this->assertEquals(\Auth::LDAP, $it['authtype']);
        $this->assertEquals(2, $it['auths_id']);

        // Check with same LDAP server again to ensure password preservation isn't just for DB_GLPI, even though the other core auth types don't store passwords in the DB
        $this->assertTrue($DB->update(
            User::getTable(),
            ['password' => 'testChangeAuthMethod123'],
            ['id' => $user->getID()]
        ));
        $this->assertTrue(User::changeAuthMethod([$user->getID()], \Auth::LDAP, 2));
        $it = $DB->request([
            'SELECT' => ['password', 'authtype', 'auths_id'],
            'FROM'   => User::getTable(),
            'WHERE'  => ['id' => $user->getID()],
        ])->current();
        $this->assertNotEmpty($it['password']);
        $this->assertEquals(\Auth::LDAP, $it['authtype']);
        $this->assertEquals(2, $it['auths_id']);
    }

    public static function onlyAdministatorsCanEnableDebugModeInPreferencesProvider(): iterable
    {
        yield [
            'user' => 'glpi',
            'can_toggle_debug_mode' => true,
        ];
        yield [
            'user' => 'tech',
            'can_toggle_debug_mode' => false,
        ];
        yield [
            'user' => 'normal',
            'can_toggle_debug_mode' => false,
        ];
        yield [
            'user' => 'post-only',
            'can_toggle_debug_mode' => false,
        ];
    }

    #[DataProvider('onlyAdministatorsCanEnableDebugModeInPreferencesProvider')]
    public function testOnlyAdministatorsCanEnableDebugModeInPreferences(
        string $user,
        bool $can_toggle_debug_mode
    ): void {
        // Act: login as user and edit user preferences
        $this->login($user);
        (new User())->update([
            'id' => \Session::getLoginUserID(),
            'use_mode' => \Session::DEBUG_MODE,
        ]);

        // Assert: check if debug mode was enabled
        $this->assertEquals(
            $can_toggle_debug_mode,
            $_SESSION['glpi_use_mode'] === \Session::DEBUG_MODE
        );
    }
}
