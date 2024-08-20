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

/* Test for inc/session.class.php */

use Psr\Log\LogLevel;

class SessionTest extends \DbTestCase
{
    public static function testUniqueSessionNameProvider(): iterable
    {
        // Same host, different path
        yield [
            \Session::buildSessionName("/var/www/localhost/glpi1", 'localhost', '80'),
            \Session::buildSessionName("/var/www/localhost/glpi2", 'localhost', '80'),
            \Session::buildSessionName("/var/www/localhost/glpi3", 'localhost', '80'),
            \Session::buildSessionName("/var/www/localhost/glpi4", 'localhost', '80'),
        ];

        // Same path, different full domains
        yield [
            \Session::buildSessionName("/var/www/glpi", 'test.localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'preprod.localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'prod.localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'localhost', '80'),
        ];

        // Same host and path but different ports
        yield [
            \Session::buildSessionName("/var/www/glpi", 'localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'localhost', '8000'),
            \Session::buildSessionName("/var/www/glpi", 'localhost', '8008'),
        ];
    }

    /**
     * @dataProvider testUniqueSessionNameProvider
     */
    public function testUniqueSessionName(
        ...$cookie_names
    ): void {
        // Each cookie name must be unique
        $this->assertEquals(array_unique($cookie_names), $cookie_names);
    }
    public function testAddMessageAfterRedirect()
    {
        $err_msg = 'Something is broken. Weird.';
        $warn_msg = 'There was a warning. Be carefull.';
        $info_msg = 'All goes well. Or not... Who knows ;)';

        $this->assertArrayNotHasKey('MESSAGE_AFTER_REDIRECT', $_SESSION);

        //test add message in cron mode
        $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        //adding a message in "cron mode" does not add anything in the session
        $this->assertArrayNotHasKey('MESSAGE_AFTER_REDIRECT', $_SESSION);

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
        $this->assertSame($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression(
            '/' . str_replace('.', '\.', $err_msg)  . '/',
            $output
        );
        $this->assertMatchesRegularExpression(
            '/' . str_replace('.', '\.', $warn_msg)  . '/',
            $output
        );
        $this->assertMatchesRegularExpression(
            '/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/',
            $output
        );

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //test multiple messages of same type
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);

        $expected = [
            ERROR   => [$err_msg, $err_msg, $err_msg]
        ];
        $this->assertSame($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression(
            '/' . str_replace('.', '\.', $err_msg)  . '/',
            $output
        );

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //test message deduplication
        $err_msg_bis = $err_msg . ' not the same';
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg_bis, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);
        \Session::addMessageAfterRedirect($err_msg, true, ERROR);

        $expected = [
            ERROR   => [$err_msg, $err_msg_bis]
        ];
        $this->assertSame($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression(
            '/' . str_replace('.', '\.', $err_msg)  . '/',
            $output
        );
        $this->assertMatchesRegularExpression(
            '/' . str_replace('.', '\.', $err_msg_bis)  . '/',
            $output
        );

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);

        //test with reset
        \Session::addMessageAfterRedirect($err_msg, false, ERROR);
        \Session::addMessageAfterRedirect($warn_msg, false, WARNING);
        \Session::addMessageAfterRedirect($info_msg, false, INFO, true);

        $expected = [
            INFO   => [$info_msg]
        ];
        $this->assertSame($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression(
            '/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/',
            $output
        );

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);
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
            $this->assertGreaterThan(0, $gid_1);
            $this->assertGreaterThan(0, (int)$group_user->add(['groups_id' => $gid_1, 'users_id'  => $uid]));
            $group_1['id'] = $gid_1;
            $user_groups[] = $group_1;

            $group_2 = [
                'name'         => "Test group {$entid} recursive=yes",
                'entities_id'  => $entid,
                'is_recursive' => 1,
            ];
            $gid_2 = (int)$group->add($group_2);
            $this->assertGreaterThan(0, $gid_2);
            $this->assertGreaterThan(0, (int)$group_user->add(['groups_id' => $gid_2, 'users_id'  => $uid]));
            $group_2['id'] = $gid_2;
            $user_groups[] = $group_2;

            $group_3 = [
                'name'         => "Test group {$entid} not attached to user",
                'entities_id'  => $entid,
                'is_recursive' => 1,
            ];
            $gid_3 = (int)$group->add($group_3);
            $this->assertGreaterThan(0, $gid_3);
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
        $this->assertEquals($expected_groups, $groups);

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
            $this->assertEquals($expected_groups, $groups);
        }
    }

    public function testLocalI18n()
    {
        //load locales
        \Session::loadLanguage('en_GB');
        $this->assertSame('Login', __('Login'));

        //create directory for local i18n
        if (!file_exists(GLPI_LOCAL_I18N_DIR . '/core')) {
            mkdir(GLPI_LOCAL_I18N_DIR . '/core');
        }

        //write local MO file with i18n override
        copy(
            FIXTURE_DIR . '/../local_en_GB.mo',
            GLPI_LOCAL_I18N_DIR . '/core/en_GB.mo'
        );
        \Session::loadLanguage('en_GB');

        $this->assertSame('Login from local gettext', __('Login'));
        $this->assertSame('Password', __('Password'));

        //write local PHP file with i18n override
        file_put_contents(
            GLPI_LOCAL_I18N_DIR . '/core/en_GB.php',
            "<?php\n\$lang['Login'] = 'Login from local PHP';\n\$lang['Password'] = 'Password from local PHP';\nreturn \$lang;"
        );
        \Session::loadLanguage('en_GB');

        $this->assertSame('Login from local gettext', __('Login'));
        $this->assertSame('Password from local PHP', __('Password'));

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

    public function testMustChangePassword()
    {
        global $CFG_GLPI;

        $this->login();
        $provider = $this->mustChangePasswordProvider();
        foreach ($provider as $row) {
            $last_update = $row['last_update'];
            $expiration_delay = $row['expiration_delay'];
            $expected_result = $row['expected_result'];

            $user = new \User();
            $username = 'test_must_change_pass_' . mt_rand();
            $user_id = (int)$user->add([
                'name' => $username,
                'password' => 'test',
                'password2' => 'test',
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
            $this->assertSame($expected_result, \Session::mustChangePassword());
        }
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
        $initial_time = time();

        // generate token
        $token = \Session::getNewIDORToken($itemtype, $add_params);
        $this->assertSame(64, strlen($token));

        // token exists in session and is valid
        $this->assertIsArray($token_data = $_SESSION['glpiidortokens'][$token]);
        if ($itemtype !== '') {
            $this->assertCount(2 + count($add_params), $token_data);
            $this->assertEquals($itemtype, $token_data['itemtype']);
        } else {
            $this->assertCount(1 + count($add_params), $token_data);
        }
        $this->assertGreaterThanOrEqual(
            $initial_time + GLPI_IDOR_EXPIRES,
            $token_data['expires']
        );

        // validate token
        $data = [
            '_idor_token' => $token,
            'itemtype'    => $itemtype,
        ] + $add_params;
        $this->assertTrue(\Session::validateIDOR($data));
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

    public function testValidateIDOR()
    {
        $provider = $this->idorDataProvider();
        foreach ($provider as $row) {
            $data = $row['data'];
            $is_valid = $row['is_valid'];

            $this->assertSame($is_valid, \Session::validateIDOR($data));
        }
    }

    public function testGetNewIDORTokenWithEmptyParams()
    {
        \Session::getNewIDORToken();
        $this->hasPhpLogRecordThatContains(
            'IDOR token cannot be generated with empty criteria.',
            LogLevel::WARNING
        );
    }

    public function testDORInvalid()
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
            'right'       => 'all'
        ]);
        $this->assertTrue($result);
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

    public function testGetRightNameForError()
    {
        $this->login();

        $provider = $this->getRightNameForErrorProvider();
        foreach ($provider as $row) {
            $module = $row[0];
            $right = $row[1];
            $expected = $row[2];

            // Set language to French to ensure we always get names back as en_GB regardless of the user's language
            \Session::loadLanguage('fr_FR');
            $this->assertEquals($expected, \Session::getRightNameForError($module, $right));
        }
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
        // do not know why, but is the case when only one entity is selected
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
    public function testGetMatchingActiveEntities(/*mixed*/ $entity_restrict, ?array $active_entities, /*int|array*/ $result): void
    {
        $_SESSION['glpiactiveentities'] = $active_entities;
        $this->assertSame($result, \Session::getMatchingActiveEntities($entity_restrict));
    }

    public function testGetMatchingActiveEntitiesWithUnexpectedValue(): void
    {
        $_SESSION['glpiactiveentities'] = [0, 1, 2, 'foo', null, 3];

        $this->assertSame([2, 3], \Session::getMatchingActiveEntities([2, 3]));
        $this->hasPhpLogRecordThatContains(
            'Unexpected value `foo` found in `$_SESSION[\'glpiactiveentities\']`.',
            LogLevel::WARNING
        );
        $this->hasPhpLogRecordThatContains(
            'Unexpected value `null` found in `$_SESSION[\'glpiactiveentities\']`.',
            LogLevel::WARNING
        );
    }
}
