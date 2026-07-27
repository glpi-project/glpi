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
use Glpi\Exception\Http\AccessDeniedHttpException;
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

        SessionTracker::revokeSession($login_session_uid, SessionTracker::REVOKE_REASON_ADMIN);
        $this->assertEquals(0, countElementsInTable('glpi_users_sessions', ['login_session_uid' => $login_session_uid]));
        $this->assertEquals(1, countElementsInTable('glpi_users_sessionhistories', [
            'login_session_uid' => $login_session_uid,
            'logout_reason' => SessionTracker::REVOKE_REASON_ADMIN,
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

    public function testRevokeAllForOtherUser(): void
    {
        $this->login();
        SessionTracker::revokeAllSessionsExceptCurrent(99);
    }

    public function testRevokeAllForOtherUserWithoutPermission(): void
    {
        $this->login('tech', 'tech');
        $this->expectException(AccessDeniedHttpException::class);
        SessionTracker::revokeAllSessionsExceptCurrent(99);
    }

    public function testRevokeAllForAllUsersWithoutPermission(): void
    {
        $this->login('tech', 'tech');
        $this->expectException(AccessDeniedHttpException::class);
        SessionTracker::revokeAllSessionsExceptCurrent(0);
    }

    public function testRevokeAllForAllUsersWithoutPermissionNegativeID(): void
    {
        $this->login('tech', 'tech');
        $this->expectException(AccessDeniedHttpException::class);
        SessionTracker::revokeAllSessionsExceptCurrent(-1);
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

    public function testGetSessions(): void
    {
        $this->login();
        $sessions = (new SessionTracker())->getSessions($_SESSION['glpiID']);
        $this->assertCount(1, $sessions);
        $session = $sessions[0];
        $this->assertEquals($_SESSION['glpiID'], $session['users_id']);
        $this->assertEquals('web', $session['type_raw']);
        $this->assertTrue($session['current_session']);
        $this->assertStringContainsString('_test_user', $session['user']);
        $this->assertEquals('::1', $session['ip_address']);
        $this->assertStringContainsString('Browser', $session['type']);
        $this->assertStringContainsString('Active', $session['status']);
        $this->assertEmpty($session['actions']);
        $this->assertEquals($session['login'], $session['last_activity']);
        $this->assertNull($session['logout_reason']);
    }

    public function testGetSessionsFilters(): void
    {
        global $DB;

        //test user, status, type and IP filters

        $DB->insert('glpi_users_sessions', [
            'users_id' => 2,
            'login_session_uid' => 'login_session_uid1',
            'session_file' => 'sess_session_token_hash1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_users_sessions', [
            'users_id' => 2,
            'login_session_uid' => 'login_session_uid2',
            'session_file' => 'sess_session_token_hash2',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_users_sessions', [
            'users_id' => 3,
            'login_session_uid' => 'login_session_uid3',
            'session_file' => 'session_token_hash3',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_users_sessions', [
            'users_id' => 3,
            'login_session_uid' => 'login_session_uid4',
            'session_file' => 'session_token_hash4',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::API,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);

        // create the history for all of the sessions + an extra one for the revoked session test
        $DB->insert('glpi_users_sessionhistories', [
            'users_id' => 2,
            'login_session_uid' => 'login_session_uid1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_users_sessionhistories', [
            'users_id' => 2,
            'login_session_uid' => 'login_session_uid2',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_users_sessionhistories', [
            'users_id' => 3,
            'login_session_uid' => 'login_session_uid3',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_users_sessionhistories', [
            'users_id' => 3,
            'login_session_uid' => 'login_session_uid4',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::API,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_users_sessionhistories', [
            'users_id' => 4,
            'login_session_uid' => 'login_session_uid5',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
            'logged_out_at' => QueryFunction::now(),
            'logout_reason' => SessionTracker::REVOKE_REASON_ADMIN,
            'users_id_revoked_by' => 15,
        ]);

        $session_tracker = new SessionTracker();
        $this->assertCount(2, $session_tracker->getSessions(users_id: 2));
        $this->assertCount(2, $session_tracker->getSessions(users_id: 0, filters: [
            'ip' => '10.1.1.3',
        ]));
        $this->assertCount(1, $session_tracker->getSessions(users_id: 0, filters: [
            'type' => 'api',
        ]));
        $this->assertCount(0, $session_tracker->getSessions(users_id: 2, filters: [
            'user' => 'post-only',
        ]));
        $this->assertCount(2, $session_tracker->getSessions(users_id: 0, filters: [
            'user' => 'post-only',
        ]));
        $this->assertCount(4, $session_tracker->getSessions(users_id: 0, filters: [
            'status' => 'active',
        ]));
        $this->assertCount(0, $session_tracker->getSessions(users_id: 4, filters: [
            'status' => 'active',
        ]));
        $this->assertCount(5, $session_tracker->getSessions(users_id: 0, filters: [
            'status' => 'all',
        ]));
    }
}
