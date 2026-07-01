<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Security;

use Auth;
use Glpi\DBAL\QueryFunction;
use Glpi\Security\SessionTracker;
use Glpi\Tests\DbTestCase;
use Log;
use User;

class SessionTrackerTest extends DbTestCase
{
    public function testIsSessionValid(): void
    {
        global $DB;
        $this->assertFalse(SessionTracker::isSessionValid('invalid_token_hash'));
        $DB->insert('glpi_users_sessions', [
            'users_id' => 2,
            'login_session_uid' => '1c4568cf2706e5b3df66340d71330925',
            'session_file' => 'sess_session_token_hash1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $this->assertTrue(SessionTracker::isSessionValid('1c4568cf2706e5b3df66340d71330925'));
        $DB->insert('glpi_users_sessionhistories', [
            'users_id' => 2,
            'login_session_uid' => 'e6c6d4409b15d4d07ac535451f41f714',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        // only exists in history, not in active sessions.
        $this->assertFalse(SessionTracker::isSessionValid('e6c6d4409b15d4d07ac535451f41f714'));
    }

    public function testRecordNewSession(): void
    {
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $active_sessions_count = countElementsInTable('glpi_users_sessions', ['users_id' => $test_users_id]);
        $this->login();
        $this->assertEquals($active_sessions_count + 1, countElementsInTable('glpi_users_sessions', ['users_id' => $test_users_id]));
    }

    public function testUpdateLastSessionActivity(): void
    {
        global $DB;
        $this->login();
        $login_session_uid = $DB->request([
            'SELECT' => 'login_session_uid',
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['users_id' => $_SESSION['glpiID']],
            'ORDER' => ['created_at DESC'],
        ])->current()['login_session_uid'];
        $this->assertNotNull($login_session_uid);
        $DB->update('glpi_users_sessions', ['last_activity_at' => '2024-01-01 00:00:00'], ['login_session_uid' => $login_session_uid]);
        SessionTracker::updateLastSessionActivity();
        $updated_last_activity = $DB->request([
            'SELECT' => 'last_activity_at',
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['login_session_uid' => $login_session_uid],
        ])->current()['last_activity_at'];
        $this->assertGreaterThan('2024-01-01 00:00:00', $updated_last_activity);
    }

    public function testRevokeSession(): void
    {
        global $DB;
        $this->login();
        $login_session_uid = $DB->request([
            'SELECT' => 'login_session_uid',
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['users_id' => $_SESSION['glpiID']],
            'ORDER' => ['created_at DESC'],
        ])->current()['login_session_uid'];
        $this->assertNotNull($login_session_uid);

        SessionTracker::revokeSession($login_session_uid, 'admin');
        $this->assertEquals(0, countElementsInTable('glpi_users_sessions', ['login_session_uid' => $login_session_uid]));
        $this->assertEquals(1, countElementsInTable('glpi_users_sessionhistories', [
            'login_session_uid' => $login_session_uid,
            'logout_reason' => 'admin',
        ]));
        $this->assertCount(1, $DB->request([
            'SELECT' => ['id'],
            'FROM' => Log::getTable(),
            'WHERE' => [
                'itemtype' => User::class,
                'items_id' => $_SESSION['glpiID'],
                'user_name' => TU_USER . ' (' . $_SESSION['glpiID'] . ')',
                'new_value' => 'Session revoked',
            ],
        ]));
    }

    public function testRevokeAllSessionExceptCurrent(): void
    {
        global $DB;
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        // Create 2 sessions for the user.
        $DB->insert('glpi_users_sessions', [
            'users_id' => $test_users_id,
            'login_session_uid' => '1c4568cf2706e5b3df66340d71330925',
            'session_file' => 'sess_session_token_hash1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => '2026-01-01 00:00:00',
            'last_activity_at' => '2026-01-01 00:00:00',
        ]);
        $DB->insert('glpi_users_sessions', [
            'users_id' => $test_users_id,
            'login_session_uid' => 'e6c6d4409b15d4d07ac535451f41f714',
            'session_file' => 'sess_session_token_hash2',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => '2026-01-02 00:00:00',
            'last_activity_at' => '2026-01-02 00:00:00',
        ]);
        $this->login();
        $current_login_session_uid = $DB->request([
            'SELECT' => 'login_session_uid',
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['users_id' => $test_users_id],
            'ORDER' => ['created_at DESC'],
        ])->current()['login_session_uid'];
        $this->assertNotNull($current_login_session_uid);

        SessionTracker::revokeAllSessionsExceptCurrent($test_users_id);
        $this->assertEquals(1, countElementsInTable('glpi_users_sessions', ['login_session_uid' => $current_login_session_uid]));
    }

    public function testRevokeSessionByAge(): void
    {
        global $DB;
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $DB->insert('glpi_users_sessions', [
            'users_id' => $test_users_id,
            'login_session_uid' => 'login_session_uid_old',
            'session_file' => 'sess_session_token_hash_old',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::dateSub(QueryFunction::now(), '31', 'DAY'),
            'last_activity_at' => QueryFunction::dateSub(QueryFunction::now(), '31', 'DAY'),
        ]);
        $DB->insert('glpi_users_sessions', [
            'users_id' => $test_users_id,
            'login_session_uid' => 'login_session_uid_recent',
            'session_file' => 'sess_session_token_hash_recent',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::dateSub(QueryFunction::now(), '1', 'MINUTE'),
            'last_activity_at' => QueryFunction::dateSub(QueryFunction::now(), '1', 'MINUTE'),
        ]);
        $DB->insert('glpi_users_sessions', [
            'users_id' => $test_users_id,
            'login_session_uid' => 'login_session_uid_current',
            'session_file' => 'sess_session_token_hash_current',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::dateSub(QueryFunction::now(), '1', 'MINUTE'),
            'last_activity_at' => QueryFunction::now(),
        ]);

        SessionTracker::revokeSessionsByAge(30);
        $this->assertEquals(0, countElementsInTable('glpi_users_sessions', [
            'login_session_uid' => ['login_session_uid_old', 'login_session_uid_recent'],
        ]));
        $this->assertEquals(1, countElementsInTable('glpi_users_sessions', ['login_session_uid' => 'login_session_uid_current']));
    }
}
