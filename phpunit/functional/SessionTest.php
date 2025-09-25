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

use Computer;
use DbTestCase;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\SessionExpiredException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

class SessionTest extends DbTestCase
{
    public function testAddMessageAfterRedirect()
    {
        $err_msg = 'Something is broken. Weird.';
        $warn_msg = 'There was a warning. Be carefull.';
        $info_msg = 'All goes well. Or not... Who knows ;)';

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //test add message in cron mode
        $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        //adding a message in "cron mode" does not add anything in the session
        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //set not running from cron
        unset($_SESSION['glpicronuserrunning']);

        //test all messages types
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($warn_msg, false, WARNING);
        \Session::addMessageAfterRedirect($info_msg, false, INFO);

        $expected = [
            ERROR   => [$err_msg],
            WARNING => [$warn_msg],
            INFO    => [$info_msg],
        ];
        $this->assertEquals($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression('/' . str_replace('.', '\.', $err_msg) . '/', $output);
        $this->assertMatchesRegularExpression('/' . str_replace('.', '\.', $warn_msg) . '/', $output);
        $this->assertMatchesRegularExpression('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg) . '/', $output);

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //test multiple messages of same type
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);

        $expected = [
            ERROR   => [$err_msg, $err_msg, $err_msg],
        ];
        $this->assertEquals($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression('/' . str_replace('.', '\.', $err_msg) . '/', $output);

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //test message deduplication
        $err_msg_bis = $err_msg . ' not the same';
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg_bis, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);

        $this->assertEquals([
            ERROR   => [$err_msg, $err_msg_bis],
        ], $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression('/' . str_replace('.', '\.', $err_msg) . '/', $output);
        $this->assertMatchesRegularExpression('/' . str_replace('.', '\.', $err_msg_bis) . '/', $output);
        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //test with reset
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($warn_msg, false, WARNING);
        \Session::addMessageAfterRedirect($info_msg, false, INFO, true);
        $this->assertEquals([
            INFO   => [$info_msg],
        ], $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg) . '/', $output);
        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);
    }

    public function testLoadGroups()
    {
        $entid_root = getItemByTypeName('Entity', '_test_root_entity', true);
        $entid_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $entid_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $entities_ids = [$entid_root, $entid_1, $entid_2];

        $uid = (int) getItemByTypeName('User', 'normal', true);

        $group = new \Group();
        $group_user = new \Group_User();

        $user_groups = [];

        foreach ($entities_ids as $entid) {
            $group_1 = [
                'name'         => "Test group {$entid} recursive=no",
                'entities_id'  => $entid,
                'is_recursive' => 0,
            ];
            $gid_1 = (int) $group->add($group_1);
            $this->assertGreaterThan(0, $gid_1);
            $this->assertGreaterThan(0, (int) $group_user->add(['groups_id' => $gid_1, 'users_id'  => $uid]));
            $group_1['id'] = $gid_1;
            $user_groups[] = $group_1;

            $group_2 = [
                'name'         => "Test group {$entid} recursive=yes",
                'entities_id'  => $entid,
                'is_recursive' => 1,
            ];
            $gid_2 = (int) $group->add($group_2);
            $this->assertGreaterThan(0, $gid_2);
            $this->assertGreaterThan(0, (int) $group_user->add(['groups_id' => $gid_2, 'users_id'  => $uid]));
            $group_2['id'] = $gid_2;
            $user_groups[] = $group_2;

            $this->assertGreaterThan(0, (int) $group->add([
                'name'         => "Test group {$entid} not attached to user",
                'entities_id'  => $entid,
                'is_recursive' => 1,
            ]));
        }

        $this->login('normal', 'normal');

        // Test groups from whole entity tree
        $session_backup = $_SESSION;
        $_SESSION['glpiactiveentities'] = $entities_ids;
        \Session::loadGroups();
        $groups = $_SESSION['glpigroups'];
        $_SESSION = $session_backup;
        $expected_groups = array_map(
            static fn($group) => (string) $group['id'],
            $user_groups
        );
        $this->assertEquals($expected_groups, $groups);

        foreach ($entities_ids as $entid) {
            // Test groups from a given entity
            $expected_groups = [];
            foreach ($user_groups as $user_group) {
                if (
                    ($user_group['entities_id'] == $entid_root && $user_group['is_recursive'] == 1)
                    || $user_group['entities_id'] == $entid
                ) {
                    $expected_groups[] = (string) $user_group['id'];
                }
            }

            $session_backup = $_SESSION;
            $_SESSION['glpiactiveentities'] = [$entid];
            \Session::loadGroups();
            $groups = $_SESSION['glpigroups'];
            $_SESSION = $session_backup;
            $this->assertEquals($expected_groups, $groups);
        }
    }

    public function testLocalI18n()
    {
        //load locales
        \Session::loadLanguage('en_GB');
        $this->assertEquals('Login', __('Login'));

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

        $this->assertEquals('Login from local gettext', __('Login'));
        $this->assertEquals('Password', __('Password'));

        //write local PHP file with i18n override
        file_put_contents(
            GLPI_LOCAL_I18N_DIR . '/core/en_GB.php',
            "<?php\n\$lang['Login'] = 'Login from local PHP';\n\$lang['Password'] = 'Password from local PHP';\nreturn \$lang;"
        );
        \Session::loadLanguage('en_GB');

        $this->assertEquals('Login from local gettext', __('Login'));
        $this->assertEquals('Password from local PHP', __('Password'));

        //cleanup -- keep at the end
        unlink(GLPI_LOCAL_I18N_DIR . '/core/en_GB.php');
        unlink(GLPI_LOCAL_I18N_DIR . '/core/en_GB.mo');
    }

    public static function mustChangePasswordProvider()
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

    #[DataProvider('mustChangePasswordProvider')]
    public function testMustChangePassword(string $last_update, int $expiration_delay, bool $expected_result)
    {
        global $CFG_GLPI;

        $this->login();
        $user = new \User();
        $username = 'test_must_change_pass_' . mt_rand();
        $user_id = (int) $user->add([
            'name'         => $username,
            'password'     => 'test',
            'password2'    => 'test',
            '_profiles_id' => 1,
        ]);
        $this->assertGreaterThan(0, $user_id);
        $this->assertTrue($user->update(['id' => $user_id, 'password_last_update' => $last_update]));

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_delay'] = $expiration_delay;
        $CFG_GLPI['password_expiration_lock_delay'] = -1;
        \Session::destroy();
        \Session::start();
        $auth = new \Auth();
        $is_logged = $auth->login($username, 'test', true);
        $CFG_GLPI = $cfg_backup;

        $this->assertTrue($is_logged);
        $this->assertEquals($expected_result, \Session::mustChangePassword());
    }

    public static function preferredLanguageProvider()
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

    #[DataProvider('preferredLanguageProvider')]
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

        $this->assertEquals($expected, $result);
    }

    public static function newIdorParamsProvider()
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
            'add_params' => ['entity_restrict' => [0, 1, 2, 3, 5, 9, 1578]],
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

    #[DataProvider('newIdorParamsProvider')]
    public function testGetNewIDORToken(string $itemtype = "", array $add_params = [])
    {
        // generate token
        $token = \Session::getNewIDORToken($itemtype, $add_params);
        $this->assertEquals(64, strlen($token));

        // validate token with dedicated method
        $this->assertIsArray($token_data = $_SESSION['glpiidortokens'][$token]);
        if ($itemtype !== '') {
            $this->assertCount(1 + count($add_params), $token_data);
            $this->assertEquals($itemtype, $token_data['itemtype']);
        } else {
            $this->assertCount(count($add_params), $token_data);
        }

        // validate token
        $data = [
            '_idor_token' => $token,
            'itemtype'    => $itemtype,
        ] + $add_params;
        $this->assertTrue(\Session::validateIDOR($data));
    }

    public static function idorDataProvider()
    {
        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'test'    => 1,
                'complex' => ['foo', 'bar', [1, 2]],
            ],
            'data'     => [
                'itemtype'    => 'User',
                'test'        => 1,
                'complex'     => ['foo', 'bar', [1, 2]],
            ],
            'is_valid' => true,
        ];
        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'test'    => 1,
                'complex' => ['foo', 'bar', [1, 2]],
            ],
            'data'     => [
                'itemtype'    => 'User',
                'test'        => 1,
                'complex'     => ['foo', 'bar', [1, 2]],
                'displaywith' => [], // empty displaywith is OK
            ],
            'is_valid' => true,
        ];
        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'test'    => 1,
                'complex' => ['foo', 'bar', [1, 2]],
            ],
            'data'     => [
                'itemtype'    => 'User',
                'complex'     => ['foo', 'bar', [1, 2]],
            ],
            'is_valid' => false, // missing `test`
        ];
        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'test'    => 1,
                'complex' => ['foo', 'bar', [1, 2]],
            ],
            'data'     => [
                'itemtype'    => 'User',
                'test'        => 1,
            ],
            'is_valid' => false, // missing `complex`
        ];
        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'test'    => 1,
                'complex' => ['foo', 'bar', [1, 2]],
            ],
            'data'     => [
                'itemtype'    => 'User',
                'test'        => 1,
                'complex'     => 'foo,bar,1,2',
            ],
            'is_valid' => false, // invalid `complex`
        ];

        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'displaywith' => ['id', 'phone'],
            ],
            'data'     => [
                'itemtype'    => 'User',
                'displaywith' => ['id', 'phone'],
            ],
            'is_valid' => true,
        ];
        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'displaywith' => ['id', 'phone'],
            ],
            'data'     => [
                'itemtype'    => 'User',
                'displaywith' => ['phone'],
            ],
            'is_valid' => false, // id missing in displaywith
        ];
        yield [
            'token_itemtype' => 'User',
            'token_data'     => [
                'displaywith' => ['id', 'phone'],
            ],
            'data'     => [
                'itemtype'    => 'User',
            ],
            'is_valid' => false, // missing displaywith
        ];
    }

    #[DataProvider('idorDataProvider')]
    public function testValidateIDOR(string $token_itemtype, array $token_data, array $data, bool $is_valid)
    {
        $token = \Session::getNewIDORToken($token_itemtype, $token_data);
        $data['_idor_token'] = $token;
        $this->assertEquals($is_valid, \Session::validateIDOR($data));
    }

    public function testValidateEmptyIDOR()
    {
        $this->assertFalse(\Session::validateIDOR());
    }

    public function testValidateIDORWithValidCondition()
    {
        $condition_sha = \Dropdown::addNewCondition(['a' => 5, 'b' => true]);
        $token = \Session::getNewIDORToken(
            'User',
            [
                'condition' => $condition_sha,
            ]
        );
        $this->assertTrue(\Session::validateIDOR([
            'itemtype'    => 'User',
            '_idor_token' => $token,
            'condition'   => $condition_sha,
        ]));
    }

    public function testValidateIDORWithDifferentCondition()
    {
        $condition_sha = \Dropdown::addNewCondition(['a' => 5, 'b' => true]);
        $token = \Session::getNewIDORToken(
            'User',
            [
                'condition' => $condition_sha,
            ]
        );
        $this->assertFalse(\Session::validateIDOR([
            'itemtype'    => 'User',
            '_idor_token' => $token,
            'condition'   => \Dropdown::addNewCondition(['a' => 1, 'b' => true]),
        ]));
    }

    public function testValidateIDORWithMissingCondition()
    {
        $condition_sha = \Dropdown::addNewCondition(['a' => 5, 'b' => true]);
        $token = \Session::getNewIDORToken(
            'User',
            [
                'condition' => $condition_sha,
            ]
        );
        $this->assertFalse(\Session::validateIDOR([
            'itemtype'    => 'User',
            '_idor_token' => $token,
        ]));
    }

    public function testGetNewIDORTokenWithEmptyParams()
    {
        $error = null;
        set_error_handler(static function ($errno, $errstr) use (&$error) {
            $error = $errstr;
        }, E_USER_WARNING);
        \Session::getNewIDORToken();
        restore_error_handler();
        $this->assertEquals('IDOR token cannot be generated with empty criteria.', $error);
    }

    public function testIDORInvalid()
    {
        //  random token
        $result = \Session::validateIDOR([
            '_idor_token' => bin2hex(random_bytes(32)),
            'itemtype'    => 'Computer',
        ]);
        $this->assertFalse($result);

        // bad itemtype
        $token_bad_itt = \Session::getNewIDORToken('Ticket');
        $result = \Session::validateIDOR([
            '_idor_token' => $token_bad_itt,
            'itemtype'    => 'Computer',
        ]);
        $this->assertFalse($result);

        // missing add params
        $token_miss_param = \Session::getNewIDORToken('User', ['right' => 'all']);
        $result = \Session::validateIDOR([
            '_idor_token' => $token_miss_param,
            'itemtype'    => 'User',
        ]);
        $this->assertFalse($result);
        $result = \Session::validateIDOR([
            '_idor_token' => $token_miss_param,
            'itemtype'    => 'User',
            'right'       => 'all',
        ]);
        $this->assertTrue($result);
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
        $this->assertCount($max, $_SESSION['glpiidortokens']);

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
            $this->assertEquals($i >= $overflow, $result);
        }
    }

    public function testGetNewCSRFToken(): void
    {
        /** @var string $CURRENTCSRFTOKEN */
        global $CURRENTCSRFTOKEN;

        $CURRENTCSRFTOKEN = null;

        $shared_token = \Session::getNewCSRFToken();
        $this->assertNotEmpty($shared_token);

        $standalone_token = null;
        for ($i = 0; $i < 10; $i++) {
            $previous_shared_token = $shared_token;
            $shared_token = \Session::getNewCSRFToken(false);
            $this->assertEquals($previous_shared_token, $shared_token);
            $this->assertEquals($CURRENTCSRFTOKEN, $shared_token);

            $previous_standalone_token = $standalone_token;
            $standalone_token = \Session::getNewCSRFToken(true);
            $this->assertNotEmpty($standalone_token);
            $this->assertNotEquals($shared_token, $standalone_token);
            $this->assertNotEquals($previous_standalone_token, $standalone_token);
        }
    }

    public function testValidateCSRF(): void
    {
        for ($i = 0; $i < 10; $i++) {
            // A shared token is only valid once
            $shared_token = \Session::getNewCSRFToken(false);
            $this->assertTrue(\Session::validateCSRF(['_glpi_csrf_token' => $shared_token]));
            $this->assertFalse(\Session::validateCSRF(['_glpi_csrf_token' => $shared_token]));

            // A standalone token is only valid once
            $standalone_token = \Session::getNewCSRFToken(true);
            $this->assertTrue(\Session::validateCSRF(['_glpi_csrf_token' => $standalone_token]));
            $this->assertFalse(\Session::validateCSRF(['_glpi_csrf_token' => $standalone_token]));

            // A fake token is never valid
            $this->assertFalse(\Session::validateCSRF(['_glpi_csrf_token' => bin2hex(random_bytes(32))]));
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
        $this->assertCount($max, $_SESSION['glpicsrftokens']);

        // Ensure that latest tokens are preserved during cleaning
        for ($i = 1; $i < $max + $overflow; $i++) {
            $result = \Session::validateCSRF(['_glpi_csrf_token' => $tokens[$i]]);
            // if $i < $overflow, then the token should have been dropped from the list
            $this->assertEquals($i >= $overflow, $result);
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
            $this->assertGreaterThan(0, $users_id);
            $users[] = $users_id;
        }

        $profiles_to_copy = ['Technician', 'Admin'];
        // Copy the data of each profile to a new one with the same name but suffixed with '-Impersonate
        foreach ($profiles_to_copy as $profile_name) {
            $profile = new \Profile();
            $profiles_id = getItemByTypeName('Profile', $profile_name, true);
            $this->assertGreaterThan(0, $profiles_id);
            $profile->getFromDB($profiles_id);
            $old_user_rights = \ProfileRight::getProfileRights($profiles_id, ['user'])['user'];
            $new_profiles_id = $profile->clone(['name' => $profile_name . '-Impersonate']);
            $DB->update('glpi_profilerights', ['rights' => $old_user_rights | \User::IMPERSONATE], [
                'profiles_id' => $new_profiles_id,
                'name' => 'user',
            ]);
        }

        $assign_profile = function (int $users_id, int $profiles_id) use ($root_entity) {
            $profile_user = new \Profile_User();
            $result = $profile_user->add([
                'profiles_id' => $profiles_id,
                'users_id'    => $users_id,
                'entities_id' => $root_entity,
            ]);
            $this->assertGreaterThan(0, $result);
            $user = new \User();
            $this->assertTrue($user->update([
                'id' => $users_id,
                'profiles_id' => $profiles_id,
            ]));
        };

        $assign_profile($users[1], getItemByTypeName('Profile', 'Technician-Impersonate', true));
        $assign_profile($users[2], getItemByTypeName('Profile', 'Admin-Impersonate', true));
        $assign_profile($users[3], getItemByTypeName('Profile', 'Admin-Impersonate', true));
        $assign_profile($users[4], getItemByTypeName('Profile', 'Super-Admin', true));
        $assign_profile($users[5], getItemByTypeName('Profile', 'Super-Admin', true));

        $this->login('testCanImpersonate1', 'test');
        $this->assertTrue(\Session::canImpersonate($users[0]));
        $this->assertFalse(\Session::canImpersonate($users[1]));
        $this->assertFalse(\Session::canImpersonate($users[2]));
        $this->assertFalse(\Session::canImpersonate($users[3]));
        $this->assertFalse(\Session::canImpersonate($users[4]));

        $this->login('testCanImpersonate2', 'test');
        $this->assertTrue(\Session::canImpersonate($users[0]));
        $this->assertTrue(\Session::canImpersonate($users[1]));
        $this->assertFalse(\Session::canImpersonate($users[2]));
        $this->assertTrue(\Session::canImpersonate($users[3]));
        $this->assertFalse(\Session::canImpersonate($users[4]));

        $this->login('testCanImpersonate3', 'test');
        $this->assertTrue(\Session::canImpersonate($users[0]));
        $this->assertTrue(\Session::canImpersonate($users[1]));
        $this->assertTrue(\Session::canImpersonate($users[2]));
        $this->assertFalse(\Session::canImpersonate($users[3]));
        $this->assertFalse(\Session::canImpersonate($users[4]));

        $this->login('testCanImpersonate4', 'test');
        // Super-admins have config UPDATE right so they can impersonate anyone (except themselves)
        $this->assertTrue(\Session::canImpersonate($users[0]));
        $this->assertTrue(\Session::canImpersonate($users[1]));
        $this->assertTrue(\Session::canImpersonate($users[2]));
        $this->assertTrue(\Session::canImpersonate($users[3]));
        $this->assertFalse(\Session::canImpersonate($users[4]));
        $this->assertTrue(\Session::canImpersonate($users[5]));

        $assign_profile($users[0], getItemByTypeName('Profile', 'Admin-Impersonate', true));
        $this->login('testCanImpersonate1', 'test');
        // User 0 now has a higher-level profile (Admin) than User 1 which is only Technician
        $this->assertFalse(\Session::canImpersonate($users[0]));

        $this->login('testCanImpersonate0', 'test');
        // Force user 0 to use Self-Service profile initially
        \Session::changeProfile(getItemByTypeName('Profile', 'Self-Service', true));
        // User 0's default profile is still Self-Service, so they can't impersonate anyone
        $this->assertFalse(\Session::canImpersonate($users[1]));
        \Session::changeProfile(getItemByTypeName('Profile', 'Admin-Impersonate', true));
        // User 0's default profile is now Admin-Impersonate, so they can impersonate the user with Technician-Impersonate
        $this->assertTrue(\Session::canImpersonate($users[1]));
    }

    protected function sessionGroupsProvider(): iterable
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
     */
    public function testSessionGroups(): void
    {
        foreach ($this->sessionGroupsProvider() as $i => $data) {
            $expected = $data['expected'];
            $this->assertEquals($expected, $_SESSION['glpigroups'], "Unexpected session groups with data set $i");
        }
    }

    public static function getRightNameForErrorProvider()
    {
        return [
            ['_nonexistant', READ, 'READ'],
            ['_nonexistant', ALLSTANDARDRIGHT, 'ALLSTANDARDRIGHT'],
            ['_nonexistant', UPDATENOTE, 'UPDATENOTE'],
            ['_nonexistant', UNLOCK, 'UNLOCK'],
            ['ticket', READ, 'See my ticket'],
            ['ticket', \Ticket::READALL, 'See all tickets'],
            ['user', \User::IMPORTEXTAUTHUSERS, 'Add external'],
        ];
    }

    #[DataProvider('getRightNameForErrorProvider')]
    public function testGetRightNameForError($module, $right, $expected)
    {
        $this->login();
        // Set language to French to ensure we always get names back as en_GB regardless of the user's language
        \Session::loadLanguage('fr_FR');
        $this->assertEquals($expected, \Session::getRightNameForError($module, $right));
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
        $this->login();

        // Assign the new profile to the user
        \Session::changeProfile($profile->getID());

        // Update or insert a new profilerights item with the created profile and 'ticket' rights
        $DB->updateOrInsert(
            'glpi_profilerights',
            [
                'rights'       => \Ticket::READALL,
            ],
            [
                'profiles_id'  => $profile->getID(),
                'name'         => 'ticket',
            ],
        );

        // Assert that the current profile does not have 'ticket' rights set to \Ticket::READALL
        $this->assertNotEquals(\Ticket::READALL, $_SESSION['glpiactiveprofile']['ticket']);

        // Reload the current profile
        \Session::reloadCurrentProfile();

        // Assert that the current profile now has 'ticket' rights set to \Ticket::READALL
        $this->assertEquals(\Ticket::READALL, $_SESSION['glpiactiveprofile']['ticket']);
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

    public static function entitiesRestrictProvider(): iterable
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

    #[DataProvider('entitiesRestrictProvider')]
    public function testGetMatchingActiveEntities(mixed $entity_restrict, ?array $active_entities, mixed $result): void
    {
        $_SESSION['glpiactiveentities'] = $active_entities;
        $this->assertSame($result, \Session::getMatchingActiveEntities($entity_restrict));
    }

    public function testGetMatchingActiveEntitiesWithUnexpectedValue(): void
    {
        $_SESSION['glpiactiveentities'] = [0, 1, 2, 'foo', null, 3];

        $errors = [];
        set_error_handler(static function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;
        }, E_USER_WARNING);
        $this->assertEquals([2, 3], \Session::getMatchingActiveEntities([2, 3]));
        restore_error_handler();

        $this->assertCount(2, $errors);
        $this->assertEquals($errors[0], 'Unexpected value `foo` found in `$_SESSION[\'glpiactiveentities\']`.');
        $this->assertEquals($errors[1], 'Unexpected value `null` found in `$_SESSION[\'glpiactiveentities\']`.');
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
            'entities_id' => $ent1,
        ])->getID();

        $this->assertTrue(\Session::changeActiveEntities($ent1, true));

        // The entity goes out of scope -> reloaded TRUE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent0,
        ]);
        $this->assertTrue(\Session::shouldReloadActiveEntities());

        $this->assertTrue(\Session::changeActiveEntities($ent2, true));

        // The entity enters the scope -> reloaded TRUE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent2,
        ]);
        $this->assertTrue(\Session::shouldReloadActiveEntities());

        $this->assertTrue(\Session::changeActiveEntities($ent1, true));

        // The entity remains out of scope -> reloaded FALSE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent0,
        ]);
        $this->assertFalse(\Session::shouldReloadActiveEntities());

        $this->assertTrue(\Session::changeActiveEntities($ent1, false));

        // The entity remains out of scope -> reloaded FALSE
        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent1,
        ]);
        $this->assertFalse(\Session::shouldReloadActiveEntities());

        // See all entities -> reloaded FALSE
        $this->assertTrue(\Session::changeActiveEntities('all'));

        $this->updateItem(\Entity::class, $entity_id, [
            'entities_id' => $ent2,
        ]);

        $this->assertFalse(\Session::shouldReloadActiveEntities());
    }

    public function testActiveEntityNameForFullStructure(): void
    {
        $this->login();
        \Session::changeActiveEntities("all");
        $this->assertEquals("Root entity (full structure)", $_SESSION["glpiactive_entity_name"]);
        $this->assertEquals("Root entity (full structure)", $_SESSION["glpiactive_entity_shortname"]);
    }

    public function testCheckValidSessionIdWithSessionExpiration(): void
    {
        $this->login();
        unset($_SESSION);
        $this->expectException(SessionExpiredException::class);
        \Session::checkValidSessionId();
    }

    public static function checkCentralAccessProvider(): iterable
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

    #[DataProvider('checkCentralAccessProvider')]
    public function testCheckCentralAccess(array $credentials, ?\Exception $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }
        \Session::checkCentralAccess();
    }

    public static function checkHelpdeskAccessProvider(): iterable
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

    #[DataProvider('checkHelpdeskAccessProvider')]
    public function testCheckHelpdeskAccess(array $credentials, ?\Exception $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }
        \Session::checkHelpdeskAccess();
    }

    public static function checkFaqAccessProvider(): iterable
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

    #[DataProvider('checkFaqAccessProvider')]
    public function testFaqAccessAccess(int $rights, bool $use_public_faq, ?\Exception $exception): void
    {
        global $CFG_GLPI;

        $this->login();

        $CFG_GLPI['use_public_faq'] = $use_public_faq;
        $_SESSION["glpiactiveprofile"]['knowbase'] = $rights;

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }
        \Session::checkFaqAccess();
    }

    public function testCheckLoginUser(): void
    {
        $this->login();

        \Session::checkLoginUser(); // no exception thrown, as expected

        unset($_SESSION['glpiname']);
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('User has no valid session but seems to be logged in');
        \Session::checkLoginUser();
    }

    public static function checkRightProvider(): iterable
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

    #[DataProvider('checkRightProvider')]
    public function testCheckRight(array $credentials, string $module, int $right, ?\Exception $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }
        \Session::checkRight($module, $right);
    }

    public static function checkRightsOrProvider(): iterable
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

    #[DataProvider('checkRightsOrProvider')]
    public function testCheckRightsOr(array $credentials, string $module, array $rights, ?\Exception $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }
        \Session::checkRightsOr($module, $rights);
    }

    public static function checkSeveralRightsOrProvider(): iterable
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

    #[DataProvider('checkSeveralRightsOrProvider')]
    public function testCheckSeveralRightsOr(array $credentials, array $rights, ?\Exception $exception): void
    {
        $this->login(...$credentials);

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }
        \Session::checkSeveralRightsOr($rights);
    }

    public function testCheckCSRF(): void
    {
        $token = \Session::getNewCSRFToken();
        \Session::checkCSRF(['_glpi_csrf_token' => $token]); // No exception thrown

        $this->expectException(AccessDeniedHttpException::class);
        \Session::checkCSRF(['_glpi_csrf_token' => 'invalid token']);
    }

    public function testRightCheckBypass()
    {
        $this->login();
        $this->assertFalse(\Session::isRightChecksDisabled());
        $this->assertFalse(\Session::haveRight('_nonexistant_module', READ));
        \Session::callAsSystem(function () {
            $this->assertTrue(\Session::isRightChecksDisabled());
            $this->assertTrue(\Session::haveRight('_nonexistant_module', READ));
        });
        $this->assertFalse(\Session::isRightChecksDisabled());
        $this->assertFalse(\Session::haveRight('_nonexistant_module', READ));
        // Try throwing an exception inside the callAsSystem callable to make sure right checks are still re-enabled after it runs
        $exception = null;
        try {
            \Session::callAsSystem(function () {
                throw new \Exception('test');
            });
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertNotNull($exception);
        $this->assertEquals('test', $exception->getMessage());
        $this->assertFalse(\Session::isRightChecksDisabled());
        $this->assertFalse(\Session::haveRight('_nonexistant_module', READ));
    }
}
