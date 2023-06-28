<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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


    protected function idorProvider()
    {
        return [
            ['itemtype' => 'Computer'],
            ['itemtype' => 'Ticket'],
            ['itemtype' => 'Glpi\\Dashboard\\Item'],
            ['itemtype' => 'User', 'add_params' => ['right' => 'all']],
            ['itemtype' => 'User', 'add_params' => ['entity_restrict' => 0]],
        ];
    }

    /**
     * @dataProvider idorProvider
     */
    public function testIDORToken(string $itemtype = "", array $add_params = [])
    {
       // generate token
        $token = \Session::getNewIDORToken($itemtype, $add_params);
        $this->string($token)->hasLength(64);

       // token exists in session and is valid
        $this->array($_SESSION['glpiidortokens'][$token])
         ->string['itemtype']->isEqualTo($itemtype)
         ->string['expires'];

        if (count($add_params) > 0) {
            $this->array($_SESSION['glpiidortokens'][$token])->size->isEqualTo(2 + count($add_params));
        }

       // validate token with dedicated method
        $result = \Session::validateIDOR([
            '_idor_token' => $token,
            'itemtype'    => $itemtype,
        ] + $add_params);
        $this->boolean($result)->isTrue();
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
}
