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
use PHPUnit\Framework\Attributes\DataProvider;
use Redis;
use RedisException;
use ReflectionProperty;
use RuntimeException;
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

    public static function redisSessionSavePathProvider(): iterable
    {
        $defaults = [
            'host'           => '127.0.0.1',
            'port'           => 6379,
            'timeout'        => 0.0,
            'read_timeout'   => 0.0,
            'retry_interval' => 0,
            'auth'           => null,
            'database'       => 0,
            'prefix'         => 'PHPREDIS_SESSION:',
            'context'        => null,
        ];

        yield 'host only' => ['tcp://127.0.0.1', $defaults];

        yield 'without scheme' => ['127.0.0.1:6380', array_merge($defaults, ['port' => 6380])];

        yield 'password, database and prefix' => [
            'tcp://redis.example.org:6380?auth=s3cr%26t&database=2&prefix=GLPI_SESS:&timeout=2.5&read_timeout=3&retry_interval=100',
            [
                'host'           => 'redis.example.org',
                'port'           => 6380,
                'timeout'        => 2.5,
                'read_timeout'   => 3.0,
                'retry_interval' => 100,
                'auth'           => 's3cr&t',
                'database'       => 2,
                'prefix'         => 'GLPI_SESS:',
                'context'        => null,
            ],
        ];

        yield 'ACL user and password' => [
            'tcp://127.0.0.1:6379?auth[]=glpi&auth[]=secret',
            array_merge($defaults, ['auth' => ['glpi', 'secret']]),
        ];

        yield 'empty password' => ['tcp://127.0.0.1:6379?auth=', $defaults];

        yield 'TLS' => ['tls://redis.example.org:6390?auth=secret', array_merge($defaults, [
            'host' => 'tls://redis.example.org',
            'port' => 6390,
            'auth' => 'secret',
        ])];

        yield 'TLS with stream context options' => [
            'tls://redis.example.org:6390?stream[verify_peer]=0&stream[cafile]=/etc/ssl/glpi/ca.pem',
            array_merge($defaults, [
                'host'    => 'tls://redis.example.org',
                'port'    => 6390,
                'context' => ['stream' => ['verify_peer' => '0', 'cafile' => '/etc/ssl/glpi/ca.pem']],
            ]),
        ];

        yield 'unix socket' => ['unix:///var/run/redis/redis.sock?auth=secret&database=3', array_merge($defaults, [
            'host'     => '/var/run/redis/redis.sock',
            'port'     => 0,
            'auth'     => 'secret',
            'database' => 3,
        ])];

        // phpredis browses its pool from the last server, and picks the server from the first 4 bytes
        // of the session ID modulo the total weight: `abcd` => 1, `abce` => 2, `abcf` => 0.
        $several_servers = 'tcp://10.0.0.1:6379?weight=1&auth=first, tcp://10.0.0.2:6379?weight=2&auth=second';
        $first_server    = array_merge($defaults, ['host' => '10.0.0.1', 'auth' => 'first']);
        $second_server   = array_merge($defaults, ['host' => '10.0.0.2', 'auth' => 'second']);
        yield 'several servers, position 0' => [$several_servers, $second_server, 'abcf0123456789'];
        yield 'several servers, position 1' => [$several_servers, $second_server, 'abcd0123456789'];
        yield 'several servers, position 2' => [$several_servers, $first_server, 'abce0123456789'];

        yield 'several servers separated by spaces' => [
            'tcp://10.0.0.1:6379?auth=first tcp://10.0.0.2:6379?auth=second',
            $first_server,
            'abcd0123456789',
        ];
    }

    #[DataProvider('redisSessionSavePathProvider')]
    public function testGetRedisSessionConnectionSettings(
        string $save_path,
        array $expected,
        string $session_id = 'abcd0123456789'
    ): void {
        $this->assertSame(
            $expected,
            $this->callPrivateMethod(SessionTracker::class, 'getRedisSessionConnectionSettings', $save_path, $session_id)
        );
    }

    public function testGetRedisSessionConnectionSettingsWithoutServer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->callPrivateMethod(SessionTracker::class, 'getRedisSessionConnectionSettings', ' , ', 'abcd0123456789');
    }

    public function testDeleteRedisSession(): void
    {
        $redis = new Redis();
        try {
            @$redis->connect('redis', 6379, 1.0);
        } catch (RedisException $e) {
            $this->markTestSkipped('Redis server is not available: ' . $e->getMessage());
        }

        // Use a dedicated ACL user, to check that the credentials are sent without protecting
        // the default user, which is used by other tests.
        $user        = 'glpi_test_' . bin2hex(random_bytes(4));
        $password    = bin2hex(random_bytes(16));
        $session_id  = bin2hex(random_bytes(16));
        $prefix      = 'GLPI_TEST_SESS_' . $user . ':';
        $session_key = $prefix . $session_id;
        $database    = 3;

        $connections_property = new ReflectionProperty(SessionTracker::class, 'redis_connections');
        $connections_property->setValue(null, []);

        $redis->rawCommand('ACL', 'SETUSER', $user, 'on', '>' . $password, '~*', '+@all');
        try {
            $redis->select($database);
            $redis->set($session_key, 'data');
            $redis->set($prefix . 'other', 'data');
            $redis->select(0);
            $redis->set($session_key, 'data');

            // Invalid credentials: the session is not deleted.
            try {
                $this->callPrivateMethod(
                    SessionTracker::class,
                    'deleteRedisSession',
                    sprintf('tcp://redis:6379?auth[]=%s&auth[]=invalid&database=%d&prefix=%s', $user, $database, $prefix),
                    'sess_' . $session_id
                );
                $this->fail('Redis authentication with invalid credentials should have failed.');
            } catch (RedisException) {
                // Expected.
            }
            $redis->select($database);
            $this->assertSame(1, $redis->exists($session_key));

            // Valid credentials: only the session key of the selected database is deleted.
            $save_path = sprintf('tcp://redis:6379?auth[]=%s&auth[]=%s&database=%d&prefix=%s', $user, $password, $database, $prefix);
            $this->callPrivateMethod(SessionTracker::class, 'deleteRedisSession', $save_path, 'sess_' . $session_id);
            $this->assertSame(0, $redis->exists($session_key));
            $this->assertSame(1, $redis->exists($prefix . 'other'));

            // The connection is reused to delete another session.
            $connections = $connections_property->getValue();
            $this->assertCount(1, $connections);
            $this->callPrivateMethod(SessionTracker::class, 'deleteRedisSession', $save_path, 'sess_other');
            $this->assertSame(0, $redis->exists($prefix . 'other'));
            $this->assertSame($connections, $connections_property->getValue());

            $redis->select(0);
            $this->assertSame(1, $redis->exists($session_key));
        } finally {
            $connections_property->setValue(null, []);
            $redis->select($database);
            $redis->del($session_key, $prefix . 'other');
            $redis->select(0);
            $redis->del($session_key);
            $redis->rawCommand('ACL', 'DELUSER', $user);
        }
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

    public function testGetSessionsOAuthDetails(): void
    {
        global $DB;

        $this->login();
        $client = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
        ]);
        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'access_token',
            'uuid' => 'access_token_uuid',
            'client' => $client->fields['identifier'],
            'date_expiration' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'user_identifier' => $_SESSION['glpiID'],
            'scopes' => exportArrayToDB(['api', 'user']),
            'ip_address' => '::1',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0',
        ]));

        $sessions = array_values(array_filter(
            (new SessionTracker())->getSessions($_SESSION['glpiID']),
            static fn($session) => $session['type_raw'] === 'api'
        ));
        $this->assertCount(1, $sessions);
        $details = $sessions[0]['details'];
        $this->assertStringContainsString(__FUNCTION__, $details);
        $this->assertStringContainsString('api, user', $details);
        $this->assertStringContainsString('ti-brand-firefox', $details);
        $this->assertStringContainsString('Firefox 140.0', $details);
    }

    public function testGetSessionsClientCredentialsToken(): void
    {
        global $DB;

        $this->login();
        $client = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
        ]);
        // Tokens from the client credentials grant are not linked to a user
        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'client_credentials_token',
            'uuid' => 'client_credentials_token_uuid',
            'client' => $client->fields['identifier'],
            'date_expiration' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'user_identifier' => null,
            'scopes' => exportArrayToDB(['api']),
            'ip_address' => '::1',
        ]));

        $sessions = array_values(array_filter(
            (new SessionTracker())->getSessions(users_id: 0, filters: ['status' => 'all']),
            static fn($session) => $session['type_raw'] === 'api'
        ));
        $this->assertCount(1, $sessions);
        $this->assertStringContainsString('Client credentials', $sessions[0]['user']);
    }
}
