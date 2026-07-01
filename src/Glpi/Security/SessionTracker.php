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

namespace Glpi\Security;

use Auth;
use Glpi\Application\Environment;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Toolbox\IPUtilities;
use Log;
use RuntimeException;
use Session;
use User;

use function Safe\ini_get;
use function Safe\session_id;
use function Safe\session_save_path;
use function Safe\unlink;

final class SessionTracker
{
    public const REVOKE_REASON_USER = 'user';
    public const REVOKE_REASON_ADMIN = 'admin';
    public const REVOKE_REASON_EXPIRED = 'expired';

    /**
     * Checks if the given login session UID corresponds to a known active session.
     * @param string $login_session_uid
     * @return bool
     */
    public static function isSessionValid(string $login_session_uid): bool
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['login_session_uid' => $login_session_uid],
            'LIMIT' => 1,
        ]);
        return $it->count() > 0;
    }

    /**
     * Record the new session to the database or update an existing one if the PHP session was just regenerated.
     * @param Auth $auth
     * @return bool true on success, false on failure. If false is returned, the session should be destroyed and the authentication process should be aborted.
     * @internal
     */
    public static function recordNewSession(Auth $auth): bool
    {
        global $DB;

        $ip = IPUtilities::getClientIP();
        if (isCommandLine() && Environment::get() !== Environment::TESTING) {
            // Do not record sessions for command line requests.
            return true;
        } elseif (isCommandLine()) {
            $ip = '::1';
        }

        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['login_session_uid' => $_SESSION['login_session_uid']],
            'LIMIT' => 1,
        ]);

        $session_exists = $it->count() > 0;
        if ($session_exists) {
            $DB->update('glpi_users_sessions', [
                'users_id' => $_SESSION['glpiID'],
                'session_file' => 'sess_' . session_id(),
                'ip_address' => $ip, // Update IP in case it changed (mobile users, VPNs, etc)
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'last_activity_at' => Session::getCurrentTime(),
            ], ['login_session_uid' => $_SESSION['login_session_uid']]);
            return true;
        }

        try {
            $DB->insert('glpi_users_sessions', [
                'users_id' => $_SESSION['glpiID'],
                'login_session_uid' => $_SESSION['login_session_uid'],
                'session_file' => 'sess_' . session_id(),
                'ip_address' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'auth_type' => $auth->getAuthType(),
                'created_at' => Session::getCurrentTime(),
                'last_activity_at' => Session::getCurrentTime(),
            ]);
            $DB->insert('glpi_users_sessionhistories', [
                'users_id' => $_SESSION['glpiID'],
                'login_session_uid' => $_SESSION['login_session_uid'],
                'ip_address' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'auth_type' => $auth->getAuthType(),
                'logged_in_at' => Session::getCurrentTime(),
            ]);
        } catch (RuntimeException $e) {
            ErrorHandler::logCaughtException($e);
            return false;
        }
        return true;
    }

    /**
     * Updates the last activity timestamp of the current login session.
     * @return void
     * @internal
     */
    public static function updateLastSessionActivity(): void
    {
        global $DB;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $DB->update('glpi_users_sessions', ['last_activity_at' => date('Y-m-d H:i:s')], ['login_session_uid' => $_SESSION['login_session_uid']]);
        }
    }

    /**
     * Revokes a session by a login session UID. If the reason is 'admin', the current user must have admin rights or be the owner of the session to revoke it.
     * @param string $login_session_uid
     * @param string $reason
     * @phpstan-param self::REVOKE_REASON_* $reason
     * @return void
     * @throws AccessDeniedHttpException
     */
    public static function revokeSession(string $login_session_uid, string $reason): void
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['users_id', 'session_file'],
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['login_session_uid' => $login_session_uid],
            'LIMIT' => 1,
        ]);
        $session = $it->current();
        $users_id = $session['users_id'] ?? null;

        $DB->delete('glpi_users_sessions', ['login_session_uid' => $login_session_uid]);
        $DB->update('glpi_users_sessionhistories', [
            'logged_out_at' => Session::getCurrentTime(),
            'logout_reason' => $reason,
            'users_id_revoked_by' => $_SESSION['glpiID'] ?? null,
        ], [
            'login_session_uid' => $login_session_uid,
            'logged_out_at' => null, // Possibility of reused session IDs since this history is kept indefinitely.
        ]);
        if ($reason !== self::REVOKE_REASON_EXPIRED && $users_id) {
            $DB->delete('glpi_usertokens', [
                'users_id' => $users_id,
                'token_uid' => $login_session_uid,
            ]);
        }

        if (ini_get('session.save_handler') === 'files' && $session) {
            $session_file_path = session_save_path() . DIRECTORY_SEPARATOR . $session['session_file'];
            if (file_exists($session_file_path)) {
                @unlink($session_file_path);
            }
        }

        if ($reason === self::REVOKE_REASON_ADMIN && $users_id) {
            Log::history($users_id, User::class, [0, '', 'Session revoked'], User::class, Log::HISTORY_LOG_SIMPLE_MESSAGE);
        }
    }

    /**
     * Revokes all sessions for a given user ID except the current one. If no user ID is provided, all sessions except the current one will be revoked.
     * @param int $users_id The user ID or 0 for all users
     * @return void
     * @throws AccessDeniedHttpException
     */
    public static function revokeAllSessionsExceptCurrent(int $users_id = 0): void
    {
        global $DB;

        if ($users_id > 0 && $users_id !== Session::getLoginUserID() && !Session::haveRight('config', UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $current_login_session_uid = $_SESSION['login_session_uid'];
        $where = [
            'NOT' => ['login_session_uid' => $current_login_session_uid],
        ];
        if ($users_id > 0) {
            $where['users_id'] = $users_id;
        }

        $it_active_sessions = $DB->request([
            'SELECT' => ['users_id', 'login_session_uid'],
            'FROM' => 'glpi_users_sessions',
            'WHERE' => $where,
        ]);

        foreach ($it_active_sessions as $session) {
            self::revokeSession($session['login_session_uid'], self::REVOKE_REASON_ADMIN);
        }
    }

    /**
     * Revokes sessions that have been inactive for longer than the specified age.
     * @param int $max_age_seconds The maximum age of sessions in seconds.
     * @return void
     */
    public static function revokeSessionsByAge(int $max_age_seconds): void
    {
        global $DB;

        $threshold_time = date('Y-m-d H:i:s', time() - $max_age_seconds);
        $it = $DB->request([
            'SELECT' => ['login_session_uid'],
            'FROM' => 'glpi_users_sessions',
            'WHERE' => [
                'last_activity_at' => ['<', $threshold_time],
            ],
        ]);

        foreach ($it as $session) {
            self::revokeSession($session['login_session_uid'], self::REVOKE_REASON_EXPIRED);
        }
    }
}
