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

use Computer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\SessionExpiredException;
use ReflectionClass;

/* Test for inc/session.class.php */

class Session extends \DbTestCase
{
    public function testAddMessageAfterRedirect()
    {
        $err_msg = 'Something is broken. Weird.';
        $warn_msg = 'There was a warning. Be carefull.';
        $info_msg = 'All goes well. Or not... Who knows ;)';

        $this->array($_SESSION)->notHasKey('MESSAGE_AFTER_REDIRECT');

       //test add message in cron mode
        $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
       //adding a message in "cron mode" does not add anything in the session
        $this->array($_SESSION)->notHasKey('MESSAGE_AFTER_REDIRECT');

       //set not running from cron
        unset($_SESSION['glpicronuserrunning']);

       //test all messages types
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($warn_msg, false, WARNING);
        \Session::addMessageAfterRedirect($info_msg, false, INFO);

        $expected = [
            ERROR   => [$err_msg],
            WARNING => [$warn_msg],
            INFO    => [$info_msg]
        ];
        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

        $this->output(
            function () {
                \Html::displayMessageAfterRedirect();
            }
        )
         ->matches('/' . str_replace('.', '\.', $err_msg)  . '/')
         ->matches('/' . str_replace('.', '\.', $warn_msg)  . '/')
         ->matches('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/');

        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

       //test multiple messages of same type
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);

        $expected = [
            ERROR   => [$err_msg, $err_msg, $err_msg]
        ];
        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

        $this->output(
            function () {
                \Html::displayMessageAfterRedirect();
            }
        )->matches('/' . str_replace('.', '\.', $err_msg)  . '/');

        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

       //test message deduplication
        $err_msg_bis = $err_msg . ' not the same';
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg_bis, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);

        $expected = [
            ERROR   => [$err_msg, $err_msg_bis]
        ];
        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

        $this->output(
            function () {
                \Html::displayMessageAfterRedirect();
            }
        )
         ->matches('/' . str_replace('.', '\.', $err_msg)  . '/')
         ->matches('/' . str_replace('.', '\.', $err_msg_bis)  . '/');

        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

       //test with reset
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($warn_msg, false, WARNING);
        \Session::addMessageAfterRedirect($info_msg, false, INFO, true);

        $expected = [
            INFO   => [$info_msg]
        ];
        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

        $this->output(
            function () {
                \Html::displayMessageAfterRedirect();
            }
        )->matches('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/');

        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();
    }

    public function testLoadGroups()
    {

        $entid_root = getItemByTypeName('Entity', '_test_root_entity', true);
        $entid_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $entid_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $entities_ids = [$entid_root, $entid_1, $entid_2];

        $uid = (int)getItemByTypeName('User', 'normal', true);

        $group = new \Group();
        $group_user = new \Group_User();

        $user_groups = [];

        foreach ($entities_ids as $entid) {
            $group_1 = [
                'name'         => "Test group {$entid} recursive=no",
                'entities_id'  => $entid,
                'is_recursive' => 0,
            ];
            $gid_1 = (int)$group->add($group_1);
            $this->integer($gid_1)->isGreaterThan(0);
            $this->integer((int)$group_user->add(['groups_id' => $gid_1, 'users_id'  => $uid]))->isGreaterThan(0);
            $group_1['id'] = $gid_1;
            $user_groups[] = $group_1;

            $group_2 = [
                'name'         => "Test group {$entid} recursive=yes",
                'entities_id'  => $entid,
                'is_recursive' => 1,
            ];
            $gid_2 = (int)$group->add($group_2);
            $this->integer($gid_2)->isGreaterThan(0);
            $this->integer((int)$group_user->add(['groups_id' => $gid_2, 'users_id'  => $uid]))->isGreaterThan(0);
            $group_2['id'] = $gid_2;
            $user_groups[] = $group_2;

            $group_3 = [
                'name'         => "Test group {$entid} not attached to user",
                'entities_id'  => $entid,
                'is_recursive' => 1,
            ];
            $gid_3 = (int)$group->add($group_3);
            $this->integer($gid_3)->isGreaterThan(0);
        }

        $this->login('normal', 'normal');

       // Test groups from whole entity tree
        $session_backup = $_SESSION;
        $_SESSION['glpiactiveentities'] = $entities_ids;
        \Session::loadGroups();
        $groups = $_SESSION['glpigroups'];
        $_SESSION = $session_backup;
        $expected_groups = array_map(
            function ($group) {
                return (string)$group['id'];
            },
            $user_groups
        );
        $this->array($groups)->isEqualTo($expected_groups);

        foreach ($entities_ids as $entid) {
           // Test groups from a given entity
            $expected_groups = [];
            foreach ($user_groups as $user_group) {
                if (
                    ($user_group['entities_id'] == $entid_root && $user_group['is_recursive'] == 1)
                    || $user_group['entities_id'] == $entid
                ) {
                    $expected_groups[] = (string)$user_group['id'];
                }
            }

            $session_backup = $_SESSION;
            $_SESSION['glpiactiveentities'] = [$entid];
            \Session::loadGroups();
            $groups = $_SESSION['glpigroups'];
            $_SESSION = $session_backup;
            $this->array($groups)->isEqualTo($expected_groups);
        }
    }

    public function testLocalI18n()
    {
       //load locales
        \Session::loadLanguage('en_GB');
        $this->string(__('Login'))->isIdenticalTo('Login');

       //create directory for local i18n
        if (!file_exists(GLPI_LOCAL_I18N_DIR . '/core')) {
            mkdir(GLPI_LOCAL_I18N_DIR . '/core');
        }

       //write local MO file with i18n override
        copy(
            __DIR__ . '/../local_en_GB.mo',
            GLPI_LOCAL_I18N_DIR . '/core/en_GB.mo'
        );
        \Session::loadLanguage('en_GB');

        $this->string(__('Login'))->isIdenticalTo('Login from local gettext');
        $this->string(__('Password'))->isIdenticalTo('Password');

       //write local PHP file with i18n override
        file_put_contents(
            GLPI_LOCAL_I18N_DIR . '/core/en_GB.php',
            "<?php\n\$lang['Login'] = 'Login from local PHP';\n\$lang['Password'] = 'Password from local PHP';\nreturn \$lang;"
        );
        \Session::loadLanguage('en_GB');

        $this->string(__('Login'))->isIdenticalTo('Login from local gettext');
        $this->string(__('Password'))->isIdenticalTo('Password from local PHP');

       //cleanup -- keep at the end
        unlink(GLPI_LOCAL_I18N_DIR . '/core/en_GB.php');
        unlink(GLPI_LOCAL_I18N_DIR . '/core/en_GB.mo');
    }

    protected function mustChangePasswordProvider()
    {
        $tests = [];

       // test with no password expiration
        $tests[] = [
            'last_update'      => date('Y-m-d H:i:s', strtotime('-10 years')),
            'expiration_delay' => -1,
            'expected_result'  => false,
        ];

       // tests with password expiration
        $cases = [
            '-5 days'  => false,
            '-30 days' => true,
        ];
        foreach ($cases as $last_update => $expected_result) {
            $tests[] = [
                'last_update'      => date('Y-m-d H:i:s', strtotime($last_update)),
                'expiration_delay' => 15,
                'expected_result'  => $expected_result,
            ];
        }

        return $tests;
    }

    /**
     * @dataProvider mustChangePasswordProvider
     */
    public function testMustChangePassword(string $last_update, int $expiration_delay, bool $expected_result)
    {
        global $CFG_GLPI;

        $this->login();
        $user = new \User();
        $username = 'test_must_change_pass_' . mt_rand();
        $user_id = (int)$user->add([
            'name'         => $username,
            'password'     => 'test',
            'password2'    => 'test',
            '_profiles_id' => 1,
        ]);
        $this->integer($user_id)->isGreaterThan(0);
        $this->boolean($user->update(['id' => $user_id, 'password_last_update' => $last_update]))->isTrue();

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_delay'] = $expiration_delay;
        $CFG_GLPI['password_expiration_lock_delay'] = -1;
        \Session::destroy();
        \Session::start();
        $auth = new \Auth();
        $is_logged = $auth->login($username, 'test', true);
        $CFG_GLPI = $cfg_backup;

        $this->boolean($is_logged)->isEqualTo(true);
        $this->boolean(\Session::mustChangePassword())->isEqualTo($expected_result);
    }

    protected function preferredLanguageProvider()
    {
        return [
            [
                'header'        => null,
                'config'        => null,
                'legacy_config' => null,
                'expected'      => 'en_GB',
            ],
            [
                'header'        => null,
                'config'        => null,
                'legacy_config' => 'it_IT',
                'expected'      => 'it_IT',
            ],
            [
                'header'        => null,
                'config'        => 'de_DE',
                'legacy_config' => null,
                'expected'      => 'de_DE',
            ],
            [
                'header'        => 'en-US',
                'config'        => 'fr_FR',
                'legacy_config' => null,
                'expected'      => 'en_US',
            ],
            [
            // latin as first choice (not available in GLPI), should fallback to italian
                'header'        => 'la, it-IT;q=0.9, it;q=0.8',
                'config'        => 'en_GB',
                'legacy_config' => null,
                'expected'      => 'it_IT',
            ],
        ];
    }

    /**
     * @dataProvider preferredLanguageProvider
     */
    public function testGetPreferredLanguage(?string $header, ?string $config, ?string $legacy_config, string $expected)
    {
        global $CFG_GLPI;

        $header_backup = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        $cfg_backup = $CFG_GLPI;

        if ($header !== null) {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $header;
        }
        $CFG_GLPI['language'] = $config;
        $CFG_GLPI['default_language'] = $legacy_config;
        $result = \Session::getPreferredLanguage();

        if ($header_backup !== null) {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $header_backup;
        }
        $CFG_GLPI = $cfg_backup;

        $this->string($result)->isEqualTo($expected);
    }

    protected function newIdorParamsProvider()
    {
        // No extra params
        foreach (['Computer', 'Ticket', 'Glpi\\Dashboard\\Item'] as $itemtype) {
            yield [
                'itemtype' => $itemtype,
            ];
        }

        // No itemtype
        yield [
            'itemtype'   => '',
            'add_params' => ['entity_restrict' => [0, 1, 2, 3, 5, 9, 1578]]
        ];

        // With itemtype and extra params
        yield [
            'itemtype'   => 'User',
            'add_params' => ['right' => 'all'],
        ];
        yield [
            'itemtype'   => 'User',
            'add_params' => ['entity_restrict' => 0],
        ];
    }

    /**
     * @dataProvider newIdorParamsProvider
     */
    public function testGetNewIDORToken(string $itemtype = "", array $add_params = [])
    {
        // generate token
        $token = \Session::getNewIDORToken($itemtype, $add_params);
        $this->string($token)->hasLength(64);

        // validate token with dedicated method
        $this->array($token_data = $_SESSION['glpiidortokens'][$token]);
        if ($itemtype !== '') {
            $this->array($token_data)->size->isEqualTo(1 + count($add_params));
            $this->array($token_data)->string['itemtype']->isEqualTo($itemtype);
        } else {
            $this->array($token_data)->size->isEqualTo(count($add_params));
        }

        // validate token
        $data = [
            '_idor_token' => $token,
            'itemtype'    => $itemtype,
        ] + $add_params;
        $this->boolean(\Session::validateIDOR($data))->isTrue();
    }

    protected function idorDataProvider()
    {
        yield [
            'data'     => [],
            'is_valid' => false, // no token provided
        ];

        $token = \Session::getNewIDORToken(
            'User',
            [
                'test'    => 1,
                'complex' => ['foo', 'bar', [1, 2]],
            ]
        );
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'test'        => 1,
                'complex'     => ['foo', 'bar', [1, 2]],
            ],
            'is_valid' => true,
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'test'        => 1,
                'complex'     => ['foo', 'bar', [1, 2]],
                'displaywith' => [], // empty displaywith is OK
            ],
            'is_valid' => true,
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'complex'     => ['foo', 'bar', [1, 2]],
            ],
            'is_valid' => false, // missing `test`
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'test'        => 1,
            ],
            'is_valid' => false, // missing `complex`
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'test'        => 1,
                'complex'     => 'foo,bar,1,2',
            ],
            'is_valid' => false, // invalid `complex`
        ];

        $token = \Session::getNewIDORToken(
            'User',
            [
                'displaywith' => ['id', 'phone'],
            ]
        );
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'displaywith' => ['id', 'phone'],
            ],
            'is_valid' => true,
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'displaywith' => ['phone'],
            ],
            'is_valid' => false, // id missing in displaywith
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
            ],
            'is_valid' => false, // missing displaywith
        ];

        $condition_sha = \Dropdown::addNewCondition(['a' => 5, 'b' => true]);
        $token = \Session::getNewIDORToken(
            'User',
            [
                'condition' => $condition_sha,
            ]
        );
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'condition'   => $condition_sha,
            ],
            'is_valid' => true,
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
                'condition'   => \Dropdown::addNewCondition(['a' => 1, 'b' => true]),
            ],
            'is_valid' => false, // not the same condition
        ];
        yield [
            'data'     => [
                'itemtype'    => 'User',
                '_idor_token' => $token,
            ],
            'is_valid' => false, // missing condition
        ];
    }

    /**
     * @dataProvider idorDataProvider
     */
    public function testValidateIDOR(array $data, bool $is_valid)
    {
        $this->boolean(\Session::validateIDOR($data))->isEqualTo($is_valid);
    }

    public function testGetNewIDORTokenWithEmptyParams()
    {
        $this->when(
            function () {
                \Session::getNewIDORToken();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('IDOR token cannot be generated with empty criteria.')
         ->exists();
    }

    public function testDORInvalid()
    {
       //  random token
        $result = \Session::validateIDOR([
            '_idor_token' => bin2hex(random_bytes(32)),
            'itemtype'    => 'Computer',
        ]);
        $this->boolean($result)->isFalse();

       // bad itemtype
        $token_bad_itt = \Session::getNewIDORToken('Ticket');
        $result = \Session::validateIDOR([
            '_idor_token' => $token_bad_itt,
            'itemtype'    => 'Computer',
        ]);
        $this->boolean($result)->isFalse();

       // missing add params
        $token_miss_param = \Session::getNewIDORToken('User', ['right' => 'all']);
        $result = \Session::validateIDOR([
            '_idor_token' => $token_miss_param,
            'itemtype'    => 'User',
        ]);
        $this->boolean($result)->isFalse();
        $result = \Session::validateIDOR([
            '_idor_token' => $token_miss_param,
            'itemtype'    => 'User',
            'right'       => 'all'
        ]);
        $this->boolean($result)->isTrue();
    }

    public function testCleanIDORTokens(): void
    {
        $refected_class = new ReflectionClass(\Session::class);
        $max = $refected_class->getConstant('IDOR_MAX_TOKENS');
        $overflow = 100;

        $tokens = [];
        for ($i = 1; $i < $max + $overflow; $i++) {
            $tokens[$i] = \Session::getNewIDORToken(Computer::class, ['test' => $i]);
        }

        \Session::cleanIDORTokens();

        // Ensure that only max token count has been preserved
        $this->integer(count($_SESSION['glpiidortokens']))->isEqualTo($max);

        // Ensure that latest tokens are preserved during cleaning
        for ($i = 1; $i < $max + $overflow; $i++) {
            $result = \Session::validateIDOR(
                [
                    '_idor_token' => $tokens[$i],
                    'itemtype'    => Computer::class,
                    'test'       => $i,
                ]
            );
            // if $i < $overflow, then the token should have been dropped from the list
            $this->boolean($result)->isEqualTo($i >= $overflow);
        }
    }

    public function testGetNewCSRFToken(): void
    {
        /** @var string $CURRENTCSRFTOKEN */
        global $CURRENTCSRFTOKEN;

        $CURRENTCSRFTOKEN = null;

        $shared_token = \Session::getNewCSRFToken();
        $this->string($shared_token)->isNotEmpty();

        $standalone_token = null;
        for ($i = 0; $i < 10; $i++) {
            $previous_shared_token = $shared_token;
            $shared_token = \Session::getNewCSRFToken(false);
            $this->string($shared_token)->isEqualTo($previous_shared_token);
            $this->string($shared_token)->isEqualTo($CURRENTCSRFTOKEN);

            $previous_standalone_token = $standalone_token;
            $standalone_token = \Session::getNewCSRFToken(true);
            $this->string($standalone_token)->isNotEmpty();
            $this->string($standalone_token)->isNotEqualTo($shared_token);
            $this->string($standalone_token)->isNotEqualTo($previous_standalone_token);
        }
    }

    public function testValidateCSRF(): void
    {
        for ($i = 0; $i < 10; $i++) {
            // A shared token is only valid once
            $shared_token = \Session::getNewCSRFToken(false);
            $this->boolean(\Session::validateCSRF(['_glpi_csrf_token' => $shared_token]))->isTrue();
            $this->boolean(\Session::validateCSRF(['_glpi_csrf_token' => $shared_token]))->isFalse();

            // A standalone token is only valid once
            $standalone_token = \Session::getNewCSRFToken(true);
            $this->boolean(\Session::validateCSRF(['_glpi_csrf_token' => $standalone_token]))->isTrue();
            $this->boolean(\Session::validateCSRF(['_glpi_csrf_token' => $standalone_token]))->isFalse();

            // A fake token is never valid
            $this->boolean(\Session::validateCSRF(['_glpi_csrf_token' => bin2hex(random_bytes(32))]))->isFalse();
        }
    }

    public function testCleanCSRFTokens(): void
    {
        $refected_class = new ReflectionClass(\Session::class);
        $max = $refected_class->getConstant('CSRF_MAX_TOKENS');
        $overflow = 100;

        $tokens = [];
        for ($i = 1; $i < $max + $overflow; $i++) {
            $tokens[$i] = \Session::getNewCSRFToken(true);
        }

        \Session::cleanCSRFTokens();

        // Ensure that only max token count has been preserved
        $this->integer(count($_SESSION['glpicsrftokens']))->isEqualTo($max);

        // Ensure that latest tokens are preserved during cleaning
        for ($i = 1; $i < $max + $overflow; $i++) {
            $result = \Session::validateCSRF(['_glpi_csrf_token' => $tokens[$i]]);
            // if $i < $overflow, then the token should have been dropped from the list
            $this->boolean($result)->isEqualTo($i >= $overflow);
        }
    }

    public function testCanImpersonate()
    {
        global $DB;

        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);

        $users = [];
        for ($i = 0; $i < 6; $i++) {
            $user = new \User();
            $users_id = $user->add([
                'name'     => 'testCanImpersonate' . $i,
                'password' => 'test',
                'password2' => 'test',
                'entities_id' => $root_entity,
            ]);
            $this->integer($users_id)->isGreaterThan(0);
            $users[] = $users_id;
        }

        $profiles_to_copy = ['Technician', 'Admin'];
        // Copy the data of each profile to a new one with the same name but suffixed with '-Impersonate
        foreach ($profiles_to_copy as $profile_name) {
            $profile = new \Profile();
            $profiles_id = getItemByTypeName('Profile', $profile_name, true);
            $this->integer($profiles_id)->isGreaterThan(0);
            $profile->getFromDB($profiles_id);
            $new_input = $profile->fields;
            unset($new_input['id']);
            foreach (['helpdesk_item_type', 'managed_domainrecordtypes', 'ticket_status', 'problem_status', 'change_status'] as $json_field) {
                $new_input[$json_field] = \importArrayFromDB($new_input[$json_field]);
            }
            $new_input['name'] .= '-Impersonate';
            $new_profiles_id = $profile->add($new_input);

            // Copy all rights from original profile to the new one, adding user impersonate right
            $rights = \ProfileRight::getProfileRights($profiles_id, ['user']);
            $rights['user'] = (int) $rights['user'] | \User::IMPERSONATE;
            \ProfileRight::updateProfileRights($new_profiles_id, $rights);
        }

        $assign_profile = function (int $users_id, int $profiles_id) use ($root_entity) {
            $profile_user = new \Profile_User();
            $result = $profile_user->add([
                'profiles_id' => $profiles_id,
                'users_id'    => $users_id,
                'entities_id' => $root_entity,
            ]);
            $this->integer($result)->isGreaterThan(0);
            $user = new \User();
            $this->boolean($user->update([
                'id' => $users_id,
                'profiles_id' => $profiles_id,
            ]))->isTrue();
        };

        $assign_profile($users[1], getItemByTypeName('Profile', 'Technician-Impersonate', true));
        $assign_profile($users[2], getItemByTypeName('Profile', 'Admin-Impersonate', true));
        $assign_profile($users[3], getItemByTypeName('Profile', 'Admin-Impersonate', true));
        $assign_profile($users[4], getItemByTypeName('Profile', 'Super-Admin', true));
        $assign_profile($users[5], getItemByTypeName('Profile', 'Super-Admin', true));

        $this->login('testCanImpersonate1', 'test');
        $this->boolean(\Session::canImpersonate($users[0]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[1]))->isFalse();
        $this->boolean(\Session::canImpersonate($users[2]))->isFalse();
        $this->boolean(\Session::canImpersonate($users[3]))->isFalse();
        $this->boolean(\Session::canImpersonate($users[4]))->isFalse();

        $this->login('testCanImpersonate2', 'test');
        $this->boolean(\Session::canImpersonate($users[0]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[1]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[2]))->isFalse();
        $this->boolean(\Session::canImpersonate($users[3]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[4]))->isFalse();

        $this->login('testCanImpersonate3', 'test');
        $this->boolean(\Session::canImpersonate($users[0]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[1]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[2]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[3]))->isFalse();
        $this->boolean(\Session::canImpersonate($users[4]))->isFalse();

        $this->login('testCanImpersonate4', 'test');
        // Super-admins have config UPDATE right so they can impersonate anyone (except themselves)
        $this->boolean(\Session::canImpersonate($users[0]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[1]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[2]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[3]))->isTrue();
        $this->boolean(\Session::canImpersonate($users[4]))->isFalse();
        $this->boolean(\Session::canImpersonate($users[5]))->isTrue();

        $assign_profile($users[0], getItemByTypeName('Profile', 'Admin-Impersonate', true));
        $this->login('testCanImpersonate1', 'test');
        // User 0 now has a higher-level profile (Admin) than User 1 which is only Technician
        $this->boolean(\Session::canImpersonate($users[0]))->isFalse();

        $this->login('testCanImpersonate0', 'test');
        // Force user 0 to use Self-Service profile initially
        \Session::changeProfile(getItemByTypeName('Profile', 'Self-Service', true));
        // User 0's default profile is still Self-Service, so they can't impersonate anyone
        $this->boolean(\Session::canImpersonate($users[1]))->isFalse();
        \Session::changeProfile(getItemByTypeName('Profile', 'Admin-Impersonate', true));
        // User 0's default profile is now Admin-Impersonate, so they can impersonate the user with Technician-Impersonate
        $this->boolean(\Session::canImpersonate($users[1]))->isTrue();
    }

    protected function testSessionGroupsProvider(): iterable
    {
        // Base entity for our tests
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create some groups to use for our tests
        $group_1 = $this->createItem('Group', [
            'name' => 'testSessionGroups Group 1',
            'entities_id' => $entity,
            'recursive_membership' => false,
        ])->getID();
        $group_1A = $this->createItem('Group', [
            'name'        => 'testSessionGroups Group 1A',
            'entities_id' => $entity,
            'groups_id'   => $group_1,
            'recursive_membership' => false,
        ])->getID();

        // Login to TU_USER account (no groups);
        $tests_users_id = getItemByTypeName("User", TU_USER, true);
        $this->login();
        yield ['expected' => []];

        // Assign our user to a group
        $group_user_1 = $this->createItem('Group_User', [
            'groups_id' => $group_1,
            'users_id' => $tests_users_id,
        ])->getID();
        yield ['expected' => [$group_1]];

        // Enable group recursion on all groups
        $this->updateItem('Group', $group_1, ['recursive_membership' => true]);
        $this->updateItem('Group', $group_1A, ['recursive_membership' => true]);
        yield ['expected' => [$group_1, $group_1A]];

        // Add another child group
        $group_1A1 = $this->createItem('Group', [
            'name'        => 'testSessionGroups Group 1A1',
            'entities_id' => $entity,
            'groups_id'   => $group_1A,
            'recursive_membership' => true,
        ])->getID();
        yield ['expected' => [$group_1, $group_1A, $group_1A1]];

        // Disable group recursion on $group_1A
        $this->updateItem("Group", $group_1A, ['recursive_membership' => false]);
        yield ['expected' => [$group_1, $group_1A]];

        // Re-enable recursion
        $this->updateItem("Group", $group_1A, ['recursive_membership' => true]);
        yield ['expected' => [$group_1, $group_1A, $group_1A1]];

        // Change parent group for $group_1A1
        $this->updateItem("Group", $group_1A1, ['groups_id' => 0]);
        yield ['expected' => [$group_1, $group_1A]];

        // Disable recursion on all groups
        $this->updateItem('Group', $group_1, ['recursive_membership' => false]);
        $this->updateItem('Group', $group_1A, ['recursive_membership' => false]);
        $this->updateItem('Group', $group_1A1, ['recursive_membership' => false]);
        yield ['expected' => [$group_1]];

        // Link Group 1A manually
        $this->createItem('Group_User', [
            'groups_id' => $group_1A,
            'users_id' => $tests_users_id,
        ]);
        yield ['expected' => [$group_1, $group_1A]];

        // Unlink Group 1
        $this->deleteItem('Group_User', $group_user_1);
        yield ['expected' => [$group_1A]];

        // Delete group 1A
        $this->deleteItem('Group', $group_1A);
        yield ['expected' => []];
    }

    /**
     * Test that $_SESSION['glpigroups'] contains the expected ids
     *
     * @dataprovider testSessionGroupsProvider
     */
    public function testSessionGroups(array $expected): void
    {
        $this->array($_SESSION['glpigroups'])->isEqualTo($expected);
    }

    protected function getRightNameForErrorProvider()
    {
        return [
            ['_nonexistant', READ, 'READ'],
            ['_nonexistant', ALLSTANDARDRIGHT, 'ALLSTANDARDRIGHT'],
            ['_nonexistant', UPDATENOTE, 'UPDATENOTE'],
            ['_nonexistant', UNLOCK, 'UNLOCK'],
            ['ticket', READ, 'See my ticket'],
            ['ticket', \Ticket::READALL, 'See all tickets'],
            ['user', \User::IMPORTEXTAUTHUSERS, 'Add external']
        ];
    }

    /**
     * @dataProvider getRightNameForErrorProvider
     */
    public function testGetRightNameForError($module, $right, $expected)
    {
        $this->login();
        // Set language to French to ensure we always get names back as en_GB regardless of the user's language
        \Session::loadLanguage('fr_FR');
        $this->string(\Session::getRightNameForError($module, $right))->isEqualTo($expected);
    }

    /**
     * Test the reloadCurrentProfile method.
     * This test creates a new profile, assigns it to a user,
     * and checks if the profile is correctly reloaded.
     *
     * @return void
     */
    public function testReloadCurrentProfile(): void
    {
        global $DB;

        // Login as a user
        $user = $this->login()->user;

        // Create a new profile with name 'testReloadCurrentProfile' and interface 'central'
        $profile = $this->createItem(
            'Profile',
            [
                'name' => 'testReloadCurrentProfile',
                'interface' => 'central',
            ]
        );

        // Create a new Profile_User item with the created profile and user
        $this->createItem(
            'Profile_User',
            [
                'profiles_id' => $profile->getID(),
                'users_id' => $user->getID(),
                'entities_id' => \Session::getActiveEntity(),
            ]
        );

        // Login again to refresh the user data
        $user = $this->login()->user;

        // Assign the new profile to the user
        \Session::changeProfile($profile->getID());

        // Update or insert a new profilerights item with the created profile and 'ticket' rights
        $DB->updateOrInsert(
            'glpi_profilerights',
            [
                'rights'       => \Ticket::READALL
            ],
            [
                'profiles_id'  => $profile->getID(),
                'name'         => 'ticket'
            ],
        );

        // Assert that the current profile does not have 'ticket' rights set to \Ticket::READALL
        $this->variable($_SESSION['glpiactiveprofile']['ticket'])->isNotEqualTo(\Ticket::READALL);

        // Reload the current profile
        \Session::reloadCurrentProfile();

        // Assert that the current profile now has 'ticket' rights set to \Ticket::READALL
        $this->variable($_SESSION['glpiactiveprofile']['ticket'])->isEqualTo(\Ticket::READALL);
    }

    /**
     * Test the deleteMessageAfterRedirect method
     *
     * @return void
     */
    public function testDeleteMessageAfterRedirect(): void
    {
        \Session::addMessageAfterRedirect("Test 1", INFO);
        \Session::addMessageAfterRedirect("Test 2", INFO);
        \Session::addMessageAfterRedirect("Test 3", INFO);
        $this->hasSessionMessages(INFO, ["Test 1", "Test 2", "Test 3"]);

        \Session::addMessageAfterRedirect("Test 1", INFO);
        \Session::addMessageAfterRedirect("Test 2", INFO);
        \Session::addMessageAfterRedirect("Test 3", INFO);
        \Session::deleteMessageAfterRedirect("Test 2", INFO);
        $this->hasSessionMessages(INFO, ["Test 1", "Test 3"]);
    }

    protected function entitiesRestrictProvider(): iterable
    {
        // Special case for -1
        foreach ([-1, "-1", [-1], ["-1"]] as $value) {
            yield [
                'entity_restrict' => $value,
                'active_entities' => [0, 1, 2, 3],
                'result'          => $value,
            ];
        }

        // Integer input, matching
        yield [
            'entity_restrict' => 2,
            'active_entities' => [0, 1, '2', 3],
            'result'          => 2,
        ];

        // String input, matching
        yield [
            'entity_restrict' => '2',
            'active_entities' => [0, 1, 2, 3],
            'result'          => 2,
        ];

        // Integer input, NOT matching
        yield [
            'entity_restrict' => 7,
            'active_entities' => [0, 1, 2, 3],
            'result'          => [],
        ];

        // String input, matching
        yield [
            'entity_restrict' => '7',
            'active_entities' => [0, 1, 2, 3],
            'result'          => [],
        ];

        // Array input, ALL matching
        yield [
            'entity_restrict' => [0, '2', 3],
            'active_entities' => [0, 1, 2, 3],
            'result'          => [0, 2, 3],
        ];

        // Array input, PARTIAL matching
        yield [
            'entity_restrict' => [0, '2', 3, 12, 54, 96],
            'active_entities' => [0, 1, 2, 3],
            'result'          => [0, 2, 3],
        ];

        // Array input, NONE matching
        yield [
            'entity_restrict' => [0, '2', 3, 12, 54, 96],
            'active_entities' => [1, 4],
            'result'          => [],
        ];

        // Empty active entities
        yield [
            'entity_restrict' => [0, '2', 3, 12, 54, 96],
            'active_entities' => null,
            'result'          => [],
        ];

        // Unexpected unique value
        yield [
            'entity_restrict' => 'not a valid value',
            'active_entities' => [0, 1, 2, 3],
            'result'          => [],
        ];

        // Unexpected value in an array
        yield [
            'entity_restrict' => [0, 'not a valid value', 3],
            'active_entities' => [0, 1, 2, 3],
            'result'          => [0, 3],
        ];

        // Active entity may contain a string value
        // do not know why, but is is the case when only one entity is selected
        foreach ([2, '2', [2], ['2']] as $entity_restrict) {
            yield [
                'entity_restrict' => $entity_restrict,
                'active_entities' => [0, 1, '2', 3],
                'result'          => is_array($entity_restrict) ? [2] : 2,
            ];
        }

        // Invalid null values in input
        yield [
            'entity_restrict' => null,
            'active_entities' => [0, 1, '2', 3],
            'result'          => [],
        ];
        yield [
            'entity_restrict' => [1, null, 3],
            'active_entities' => [0, 1, '2', 3],
            'result'          => [1, 3],
        ];
    }

    /**
     * @dataProvider entitiesRestrictProvider
     */
    public function testGetMatchingActiveEntities(mixed $entity_restrict, ?array $active_entities, mixed $result): void
    {
        $_SESSION['glpiactiveentities'] = $active_entities;
        $this->variable(\Session::getMatchingActiveEntities($entity_restrict))->isIdenticalTo($result);
    }

    public function testGetMatchingActiveEntitiesWithUnexpectedValue(): void
    {
        $_SESSION['glpiactiveentities'] = [0, 1, 2, 'foo', null, 3];

        $this->when(
            function () {
                $this->variable(\Session::getMatchingActiveEntities([2, 3]))->isIdenticalTo([2, 3]);
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unexpected value `foo` found in `$_SESSION[\'glpiactiveentities\']`.')
         ->exists()
         ->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unexpected value `null` found in `$_SESSION[\'glpiactiveentities\']`.')
         ->exists();
    }

    public function testShouldReloadActiveEntities(): void
    {
        $this->login('glpi', 'glpi');

        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
        $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

        // Create a new entity
        $entity_id = $this->createItem(\Entity::class, [
            'name'        => __METHOD__,
            'entities_id' => $ent1
        ])->getID();

        $this->boolean(\Session::changeActiveEntities($ent1, true))->isTrue();

        // The entity goes out of scope -> reloaded TRUE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent0
        ]);
        $this->boolean(\Session::shouldReloadActiveEntities())->isTrue();

        $this->boolean(\Session::changeActiveEntities($ent2, true))->isTrue();

        // The entity enters the scope -> reloaded TRUE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent2
        ]);
        $this->boolean(\Session::shouldReloadActiveEntities())->isTrue();

        $this->boolean(\Session::changeActiveEntities($ent1, true))->isTrue();

        // The entity remains out of scope -> reloaded FALSE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent0
        ]);
        $this->boolean(\Session::shouldReloadActiveEntities())->isFalse();

        $this->boolean(\Session::changeActiveEntities($ent1, false))->isTrue();

        // The entity remains out of scope -> reloaded FALSE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent1
        ]);
        $this->boolean(\Session::shouldReloadActiveEntities())->isFalse();

        // See all entities -> reloaded FALSE
        $this->boolean(\Session::changeActiveEntities('all'))->isTrue();

        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent2
        ]);

        $this->boolean(\Session::shouldReloadActiveEntities())->isFalse();
    }

    public function testActiveEntityNameForFullStructure(): void
    {
        $this->login();
        \Session::changeActiveEntities("all");
        $this->string($_SESSION["glpiactive_entity_name"])->isEqualTo("Root entity (full structure)");
        $this->string($_SESSION["glpiactive_entity_shortname"])->isEqualTo("Root entity (full structure)");
    }

    public function testCheckValidSessionIdWithSessionExpiration(): void
    {
        $this->login();

        unset($_SESSION);

        $this->exception(
            function () {
                \Session::checkValidSessionId();
            }
        )->isInstanceOf(SessionExpiredException::class);
    }

    protected function checkCentralAccessProvider(): iterable
    {
        yield [
            'credentials' => [TU_USER, TU_PASS],
            'exception'   => null,
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'exception'   => new AccessDeniedHttpException(
                'The current profile does not use the standard interface'
            ),
        ];
    }

    /**
     * @dataProvider checkCentralAccessProvider
     */
    public function testCheckCentralAccess(array $credentials, ?\Throwable $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->exception(
                function () {
                    \Session::checkCentralAccess();
                }
            )->isInstanceOf($exception::class)
             ->hasMessage($exception->getMessage())
             ->hasCode($exception->getCode());
        }
    }

    protected function checkHelpdeskAccessProvider(): iterable
    {
        yield [
            'credentials' => [TU_USER, TU_PASS],
            'exception'   => new AccessDeniedHttpException(
                'The current profile does not use the simplified interface'
            ),
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'exception'   => null,
        ];
    }

    /**
     * @dataProvider checkHelpdeskAccessProvider
     */
    public function testCheckHelpdeskAccess(array $credentials, ?\Throwable $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->exception(
                function () {
                    \Session::checkHelpdeskAccess();
                }
            )->isInstanceOf($exception::class)
             ->hasMessage($exception->getMessage())
             ->hasCode($exception->getCode());
        }
    }

    protected function checkFaqAccessProvider(): iterable
    {
        yield [
            'rights'         => 0,
            'use_public_faq' => false,
            'exception'      => new AccessDeniedHttpException(
                'Missing FAQ right'
            ),
        ];

        yield [
            'rights'         => \KnowbaseItem::READFAQ,
            'use_public_faq' => false,
            'exception'      => null,
        ];

        yield [
            'rights'         => READ,
            'use_public_faq' => false,
            'exception'      => null,
        ];

        yield [
            'rights'         => 0,
            'use_public_faq' => true,
            'exception'      => null,
        ];

        yield [
            'rights'         => \KnowbaseItem::READFAQ,
            'use_public_faq' => true,
            'exception'      => null,
        ];

        yield [
            'rights'         => READ,
            'use_public_faq' => true,
            'exception'      => null,
        ];
    }

    /**
     * @dataProvider checkFaqAccessProvider
     */
    public function testFaqAccessAccess(int $rights, bool $use_public_faq, ?\Throwable $exception): void
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        $this->login();

        $CFG_GLPI['use_public_faq'] = $use_public_faq;
        $_SESSION["glpiactiveprofile"]['knowbase'] = $rights;

        if ($exception !== null) {
            $this->exception(
                function () {
                    \Session::checkFaqAccess();
                }
            )->isInstanceOf($exception::class)
             ->hasMessage($exception->getMessage())
             ->hasCode($exception->getCode());
        }
    }

    public function testCheckLoginUser(): void
    {
        $this->login();

        \Session::checkLoginUser(); // no exception thrown, as expected

        unset($_SESSION['glpiname']);

        $this->exception(
            function () {
                \Session::checkLoginUser();
            }
        )->isInstanceOf(AccessDeniedHttpException::class)
         ->hasMessage('User has no valid session but seems to be logged in');
    }

    protected function checkRightProvider(): iterable
    {
        yield [
            'credentials' => [TU_USER, TU_PASS],
            'module'      => 'computer',
            'right'       => READ,
            'exception'   => null,
        ];

        yield [
            'credentials' => [TU_USER, TU_PASS],
            'module'      => 'computer',
            'right'       => UPDATE,
            'exception'   => null,
        ];

        yield [
            'credentials' => ['normal', 'normal'],
            'module'      => 'config',
            'right'       => DELETE,
            'exception'   => new AccessDeniedHttpException(
                'User is missing the 8 (DELETE) right for config'
            ),
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'module'      => 'computer',
            'right'       => READ,
            'exception'   => new AccessDeniedHttpException(
                'User is missing the 1 (READ) right for computer'
            ),
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'module'      => 'computer',
            'right'       => UPDATE,
            'exception'   => new AccessDeniedHttpException(
                'User is missing the 2 (UPDATE) right for computer'
            ),
        ];
    }

    /**
     * @dataProvider checkRightProvider
     */
    public function testCheckRight(array $credentials, string $module, int $right, ?\Throwable $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->exception(
                function () use ($module, $right) {
                    \Session::checkRight($module, $right);
                }
            )->isInstanceOf($exception::class)
             ->hasMessage($exception->getMessage())
             ->hasCode($exception->getCode());
        }
    }

    protected function checkRightsOrProvider(): iterable
    {
        yield [
            'credentials' => [TU_USER, TU_PASS],
            'module'      => 'computer',
            'rights'      => [READ, CREATE, UPDATE],
            'exception'   => null,
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'module'      => 'computer',
            'rights'      => [READ, CREATE, UPDATE],
            'exception'   => new AccessDeniedHttpException(
                'User is missing all of the following rights: 1 (READ), 4 (CREATE), 2 (UPDATE) for computer'
            ),
        ];
    }

    /**
     * @dataProvider checkRightsOrProvider
     */
    public function testCheckRightsOr(array $credentials, string $module, array $rights, ?\Throwable $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->exception(
                function () use ($module, $rights) {
                    \Session::checkRightsOr($module, $rights);
                }
            )->isInstanceOf($exception::class)
             ->hasMessage($exception->getMessage())
             ->hasCode($exception->getCode());
        }
    }

    protected function checkSeveralRightsOrProvider(): iterable
    {
        yield [
            'credentials' => [TU_USER, TU_PASS],
            'rights'      => [
                'notification' => READ,
                'config'       => UPDATE,
            ],
            'exception'   => null,
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'rights'      => [
                'notification' => READ,
                'config'       => UPDATE,
            ],
            'exception'   => new AccessDeniedHttpException(
                'User is missing all of the following rights: 1 (READ) for module notification, 2 (UPDATE) for module config'
            ),
        ];
    }

    /**
     * @dataProvider checkSeveralRightsOrProvider
     */
    public function testCheckSeveralRightsOr(array $credentials, array $rights, ?\Throwable $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->exception(
                function () use ($rights) {
                    \Session::checkSeveralRightsOr($rights);
                }
            )->isInstanceOf($exception::class)
             ->hasMessage($exception->getMessage())
             ->hasCode($exception->getCode());
        }
    }

    protected function checkCSRFProvider(): iterable
    {
        yield [
            'credentials' => [TU_USER, TU_PASS],
            'rights'      => [
                'notification' => READ,
                'config'       => UPDATE,
            ],
            'exception'   => null,
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'rights'      => [
                'notification' => READ,
                'config'       => UPDATE,
            ],
            'exception'   => new AccessDeniedHttpException(
                'User is missing all of the following rights: 1 (READ) for module notification, 2 (UPDATE) for module config'
            ),
        ];
    }

    public function testCheckCSRF(): void
    {
        $token = \Session::getNewCSRFToken();
        \Session::checkCSRF(['_glpi_csrf_token' => $token]); // No exception thrown


        $this->exception(
            function () {
                \Session::checkCSRF(['_glpi_csrf_token' => 'invalid token']);
            }
        )->isInstanceOf(AccessDeniedHttpException::class);
    }
}
