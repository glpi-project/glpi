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
        $DB->insert('glpi_user_sessions', [
            'users_id' => 2,
            'session_token_hash' => 'session_token_hash1',
            'session_file' => 'sess_session_token_hash1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $this->assertTrue(SessionTracker::isSessionValid('session_token_hash1'));
        $DB->insert('glpi_user_session_history', [
            'users_id' => 2,
            'session_token_hash' => 'session_token_hash2',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        // only exists in history, not in active sessions.
        $this->assertFalse(SessionTracker::isSessionValid('session_token_hash2'));
    }

    public function testRecordNewSession(): void
    {
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $active_sessions_count = countElementsInTable('glpi_user_sessions', ['users_id' => $test_users_id]);
        $this->login();
        $this->assertEquals($active_sessions_count + 1, countElementsInTable('glpi_user_sessions', ['users_id' => $test_users_id]));
    }

    public function testUpdateLastSessionActivity(): void
    {
        global $DB;
        $this->login();
        $session_token_hash = $DB->request([
            'SELECT' => 'session_token_hash',
            'FROM' => 'glpi_user_sessions',
            'WHERE' => ['users_id' => $_SESSION['glpiID']],
            'ORDER' => ['created_at DESC'],
        ])->current()['session_token_hash'];
        $this->assertNotNull($session_token_hash);
        $DB->update('glpi_user_sessions', ['last_activity_at' => '2024-01-01 00:00:00'], ['session_token_hash' => $session_token_hash]);
        SessionTracker::updateLastSessionActivity();
        $updated_last_activity = $DB->request([
            'SELECT' => 'last_activity_at',
            'FROM' => 'glpi_user_sessions',
            'WHERE' => ['session_token_hash' => $session_token_hash],
        ])->current()['last_activity_at'];
        $this->assertGreaterThan('2024-01-01 00:00:00', $updated_last_activity);
    }

    public function testRevokeSession(): void
    {
        global $DB;
        $this->login();
        $session_token_hash = $DB->request([
            'SELECT' => 'session_token_hash',
            'FROM' => 'glpi_user_sessions',
            'WHERE' => ['users_id' => $_SESSION['glpiID']],
            'ORDER' => ['created_at DESC'],
        ])->current()['session_token_hash'];
        $this->assertNotNull($session_token_hash);

        SessionTracker::revokeSession($session_token_hash, 'admin');
        $this->assertEquals(0, countElementsInTable('glpi_user_sessions', ['session_token_hash' => $session_token_hash]));
        $this->assertEquals(1, countElementsInTable('glpi_user_session_history', [
            'session_token_hash' => $session_token_hash,
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
        $DB->insert('glpi_user_sessions', [
            'users_id' => $test_users_id,
            'session_token_hash' => 'session_token_hash1',
            'session_file' => 'sess_session_token_hash1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => '2026-01-01 00:00:00',
            'last_activity_at' => '2026-01-01 00:00:00',
        ]);
        $DB->insert('glpi_user_sessions', [
            'users_id' => $test_users_id,
            'session_token_hash' => 'session_token_hash2',
            'session_file' => 'sess_session_token_hash2',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => '2026-01-02 00:00:00',
            'last_activity_at' => '2026-01-02 00:00:00',
        ]);
        $this->login();
        $current_session_token_hash = $DB->request([
            'SELECT' => 'session_token_hash',
            'FROM' => 'glpi_user_sessions',
            'WHERE' => ['users_id' => $test_users_id],
            'ORDER' => ['created_at DESC'],
        ])->current()['session_token_hash'];
        $this->assertNotNull($current_session_token_hash);

        SessionTracker::revokeAllSessionsExceptCurrent($test_users_id);
        $this->assertEquals(1, countElementsInTable('glpi_user_sessions', ['session_token_hash' => $current_session_token_hash]));
    }

    public function testRevokeSessionByAge(): void
    {
        global $DB;
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $DB->insert('glpi_user_sessions', [
            'users_id' => $test_users_id,
            'session_token_hash' => 'session_token_hash_old',
            'session_file' => 'sess_session_token_hash_old',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::dateSub(QueryFunction::now(), '31', 'DAY'),
            'last_activity_at' => QueryFunction::dateSub(QueryFunction::now(), '31', 'DAY'),
        ]);
        $DB->insert('glpi_user_sessions', [
            'users_id' => $test_users_id,
            'session_token_hash' => 'session_token_hash_recent',
            'session_file' => 'sess_session_token_hash_recent',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::dateSub(QueryFunction::now(), '1', 'MINUTE'),
            'last_activity_at' => QueryFunction::dateSub(QueryFunction::now(), '1', 'MINUTE'),
        ]);
        $DB->insert('glpi_user_sessions', [
            'users_id' => $test_users_id,
            'session_token_hash' => 'session_token_hash_current',
            'session_file' => 'sess_session_token_hash_current',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::dateSub(QueryFunction::now(), '1', 'MINUTE'),
            'last_activity_at' => QueryFunction::now(),
        ]);

        SessionTracker::revokeSessionsByAge(30);
        $this->assertEquals(0, countElementsInTable('glpi_user_sessions', [
            'session_token_hash' => ['session_token_hash_old', 'session_token_hash_recent']
        ]));
        $this->assertEquals(1, countElementsInTable('glpi_user_sessions', ['session_token_hash' => 'session_token_hash_current']));
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

        $DB->insert('glpi_user_sessions', [
            'users_id' => 2,
            'session_token_hash' => 'session_token_hash1',
            'session_file' => 'sess_session_token_hash1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_user_sessions', [
            'users_id' => 2,
            'session_token_hash' => 'session_token_hash2',
            'session_file' => 'sess_session_token_hash2',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_user_sessions', [
            'users_id' => 3,
            'session_token_hash' => 'session_token_hash3',
            'session_file' => 'session_token_hash3',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_user_sessions', [
            'users_id' => 3,
            'session_token_hash' => 'session_token_hash4',
            'session_file' => 'session_token_hash4',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::API,
            'created_at' => QueryFunction::now(),
            'last_activity_at' => QueryFunction::now(),
        ]);

        // create the history for all of the sessions + an extra one for the revoked session test
        $DB->insert('glpi_user_session_history', [
            'users_id' => 2,
            'session_token_hash' => 'session_token_hash1',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_user_session_history', [
            'users_id' => 2,
            'session_token_hash' => 'session_token_hash2',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_user_session_history', [
            'users_id' => 3,
            'session_token_hash' => 'session_token_hash3',
            'ip_address' => '10.1.1.3',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_user_session_history', [
            'users_id' => 3,
            'session_token_hash' => 'session_token_hash4',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::API,
            'logged_in_at' => QueryFunction::now(),
        ]);
        $DB->insert('glpi_user_session_history', [
            'users_id' => 4,
            'session_token_hash' => 'session_token_hash5',
            'ip_address' => '::1',
            'user_agent' => '',
            'auth_type' => Auth::DB_GLPI,
            'logged_in_at' => QueryFunction::now(),
            'logged_out_at' => QueryFunction::now(),
            'logout_reason' => 'admin',
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
