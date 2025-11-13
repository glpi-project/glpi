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

use Auth;
use DbTestCase;
use Profile;
use Session;
use SsoVariable;
use User;

/**
 * Test for SSO authentication with deleted and inactive users
 */
class AuthDeletedUserTest extends DbTestCase
{
    private $ssovariable;
    private $original_ssovariables_id;

    public function setUp(): void
    {
        parent::setUp();

        global $CFG_GLPI;

        $this->ssovariable = $this->createItem(SsoVariable::class, [
            'name' => 'REMOTE_USER',
            'comment' => 'Test SSO variable'
        ]);

        $this->original_ssovariables_id = $CFG_GLPI["ssovariables_id"] ?? null;
        $CFG_GLPI["ssovariables_id"] = $this->ssovariable->getID();
    }

    public function tearDown(): void
    {
        global $CFG_GLPI;

        if ($this->original_ssovariables_id !== null) {
            $CFG_GLPI["ssovariables_id"] = $this->original_ssovariables_id;
        } else {
            unset($CFG_GLPI["ssovariables_id"]);
        }

        if (isset($_SERVER['REMOTE_USER'])) {
            unset($_SERVER['REMOTE_USER']);
        }

        parent::tearDown();
    }

    /**
     * Test that a deleted user cannot authenticate via SSO
     */
    public function testSSOFailsWithDeletedUser()
    {
        $this->login();

        $username = 'test_sso_deleted_' . mt_rand();
        $user = $this->createItem(User::class, [
            'name' => $username,
            'authtype' => Auth::EXTERNAL,
            '_profiles_id' => Profile::getDefault(),
        ]);

        $_SERVER['REMOTE_USER'] = $username;
        $auth = new Auth();
        $result = $auth->login('', '', false);
        $this->assertTrue($result);

        Session::destroy();

        $this->updateItem(User::class, $user->getID(), [
            'is_deleted' => 1
        ]);

        $_SERVER['REMOTE_USER'] = $username;
        $auth2 = new Auth();
        $result2 = $auth2->login('', '', false);

        $this->assertFalse($result2);
        $this->assertFalse(Session::getLoginUserID());
    }

    /**
     * Test SSO authentication with deleted and active user with same name
     */
    public function testSSOSucceedsWithDeletedAndActiveUserSameName()
    {
        $this->login();

        $username = 'user@domain.com';

        $first_user = $this->createItem(User::class, [
            'name' => $username,
            'authtype' => Auth::EXTERNAL,
            'auths_id' => 1,
            '_profiles_id' => Profile::getDefault(),
        ]);

        $this->updateItem(User::class, $first_user->getID(), [
            'is_deleted' => 1
        ]);

        $second_user = $this->createItem(User::class, [
            'name' => $username,
            'authtype' => Auth::EXTERNAL,
            'auths_id' => 0,
            '_profiles_id' => Profile::getDefault(),
        ]);

        $users = getAllDataFromTable(User::getTable(), ['name' => $username]);
        $this->assertCount(2, $users);

        $deleted_count = 0;
        $active_count = 0;
        foreach ($users as $user_data) {
            if ($user_data['is_deleted'] == 1) {
                $deleted_count++;
            } else {
                $active_count++;
            }
        }
        $this->assertEquals(1, $deleted_count);
        $this->assertEquals(1, $active_count);

        Session::destroy();

        $_SERVER['REMOTE_USER'] = $username;
        $auth = new Auth();
        $result = $auth->login('', '', false);

        $this->assertTrue($result);

        $logged_user_id = Session::getLoginUserID();
        $this->assertEquals($second_user->getID(), $logged_user_id);

        $logged_user = new User();
        $logged_user->getFromDB($logged_user_id);
        $this->assertEquals(0, $logged_user->fields['is_deleted']);
        $this->assertEquals(1, $logged_user->fields['is_active']);
    }

    /**
     * Test that an inactive user cannot authenticate via SSO
     */
    public function testSSOFailsWithInactiveUser()
    {
        $this->login();

        $username = 'test_sso_inactive_' . mt_rand();
        $user = $this->createItem(User::class, [
            'name' => $username,
            'authtype' => Auth::EXTERNAL,
            '_profiles_id' => Profile::getDefault(),
            'is_active' => 0,
        ]);

        Session::destroy();

        $_SERVER['REMOTE_USER'] = $username;
        $auth = new Auth();
        $result = $auth->login('', '', false);

        $this->assertFalse($result);
        $this->assertFalse(Session::getLoginUserID());
    }
}
