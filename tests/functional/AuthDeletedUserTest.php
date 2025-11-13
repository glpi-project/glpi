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
            'comment' => 'Test SSO variable',
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
        Session::start();

        $this->updateItem(User::class, $user->getID(), [
            'is_deleted' => 1,
        ]);

        $_SERVER['REMOTE_USER'] = $username;
        $auth2 = new Auth();
        $result2 = $auth2->login('', '', false);

        $this->assertFalse($result2);
        $this->assertFalse(Session::getLoginUserID());
    }

    /**
     * Test that an inactive user cannot authenticate via SSO
     */
    public function testSSOFailsWithInactiveUser()
    {
        $this->login();

        $username = 'test_sso_inactive_' . mt_rand();
        $this->createItem(User::class, [
            'name' => $username,
            'authtype' => Auth::EXTERNAL,
            '_profiles_id' => Profile::getDefault(),
            'is_active' => 0,
        ]);

        Session::destroy();
        Session::start();

        $_SERVER['REMOTE_USER'] = $username;
        $auth = new Auth();
        $result = $auth->login('', '', false);

        $this->assertFalse($result);
        $this->assertFalse(Session::getLoginUserID());
    }
}
