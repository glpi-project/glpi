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

class Session extends \DbTestCase
{
    protected function testUniqueSessionNameProvider(): iterable
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
        $this->array($cookie_names)->isEqualTo(array_unique($cookie_names));
    }
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
        $initial_time = time();

        // generate token
        $token = \Session::getNewIDORToken($itemtype, $add_params);
        $this->string($token)->hasLength(64);

        // token exists in session and is valid
        $this->array($token_data = $_SESSION['glpiidortokens'][$token]);
        if ($itemtype !== '') {
            $this->array($token_data)->size->isEqualTo(2 + count($add_params));
            $this->array($token_data)->string['itemtype']->isEqualTo($itemtype);
        } else {
            $this->array($token_data)->size->isEqualTo(1 + count($add_params));
        }
        $this->array($token_data)->integer['expires']->isGreaterThanOrEqualTo($initial_time + GLPI_IDOR_EXPIRES);

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
    public function testGetMatchingActiveEntities(/*mixed*/ $entity_restrict, ?array $active_entities, /*int|array*/ $result): void
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
}
