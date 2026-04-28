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
use CommonGLPI;
use DeviceDetector\DeviceDetector;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Debug\Profiler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Toolbox\IPUtilities;
use RuntimeException;
use Session;
use User;

/**
 * @phpstan-type SessionFilterCriteria array{user?: string, status?: 'all'|'active', type?: 'all'|'web'|'api', ip?: string}
 */
final class SessionTracker extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return __('Session list');
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    /**
     * Record the new session to the database
     * @param Auth $auth
     * @return bool true on success, false on failure. If false is returned, the session should be destroyed and the authentication process should be aborted.
     * @internal
     */
    public static function recordNewSession(Auth $auth): bool
    {
        global $DB;

        if (isCommandLine()) {
            // Do not record sessions for command line requests.
            return true;
        }

        try {
            $DB->insert('glpi_user_sessions', [
                'users_id' => $_SESSION['glpiID'],
                'session_token_hash' => Session::getSessionTokenHash(),
                'session_file' => 'sess_' . session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'], //TODO This may not be correct if GLPI is behind a reverse proxy
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'auth_type' => $auth->getAuthType(),
                'created_at' => QueryFunction::now(),
                'last_activity_at' => QueryFunction::now(),
            ]);
            $DB->insert('glpi_user_session_history', [
                'users_id' => $_SESSION['glpiID'],
                'session_token_hash' => Session::getSessionTokenHash(),
                'ip_address' => $_SERVER['REMOTE_ADDR'], //TODO This may not be correct if GLPI is behind a reverse proxy
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'auth_type' => $auth->getAuthType(),
                'logged_in_at' => QueryFunction::now(),
            ]);
        } catch (RuntimeException) {
            return false;
        }
        return true;
    }

    /**
     * Updates the last activity timestamp of the current session.
     * @return void
     * @internal
     */
    public static function updateLastSessionActivity(): void
    {
        global $DB;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $DB->update('glpi_user_sessions', ['last_activity_at' => date('Y-m-d H:i:s')], ['session_token_hash' => Session::getSessionTokenHash()]);
        }
    }

    /**
     * @param string $session_token_hash
     * @param 'user'|'admin'|'expired' $reason
     * @return void
     * @throws AccessDeniedHttpException
     */
    public static function revokeSession(string $session_token_hash, string $reason): void
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['users_id'],
            'FROM' => 'glpi_user_sessions',
            'WHERE' => ['session_token_hash' => $session_token_hash],
        ]);
        $users_id = $it->current()['users_id'] ?? null;

        if ($reason === 'admin' && $users_id !== Session::getLoginUserID() && !Session::haveRight('config', UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $DB->delete('glpi_user_sessions', ['session_token_hash' => $session_token_hash]);
        $DB->update('glpi_user_session_history', [
            'logged_out_at' => QueryFunction::now(),
            'logout_reason' => $reason,
            'users_id_revoked_by' => $_SESSION['glpiID'] ?? null,
        ], [
            'session_token_hash' => $session_token_hash,
            'logged_out_at' => null, // Possibility of reused session IDs since this history is kept indefinitely.
        ]);
        if ($reason !== 'expired' && $users_id) {
            $DB->update('glpi_users', [
                'cookie_token' => null,
            ], ['id' => $users_id]);
        }
    }

    /**
     * @param int $users_id
     * @param SessionFilterCriteria $filters
     * @return array<string, mixed>
     */
    private function getWebSessionsCriteria(int $users_id = 0, array $filters = []): array
    {
        $where = [];
        $having = [];
        $joins = [
            'glpi_user_sessions' => [
                'ON' => [
                    'glpi_user_sessions' => 'session_token_hash',
                    'glpi_user_session_history' => 'session_token_hash', [
                        'AND' => [
                            'glpi_user_session_history.logged_out_at' => null,
                        ],
                    ],
                ],
            ],
        ];

        if ($users_id > 0) {
            $where['users_id'] = $users_id;
        }

        if (isset($filters['user']) && $filters['user'] !== '') {
            $joins['glpi_users'] = [
                'ON' => [
                    'glpi_users' => 'id',
                    'glpi_user_session_history' => 'users_id',
                ],
            ];
            $user_filter = '%' . $filters['user'] . '%';
            if ($_SESSION["glpinames_format"] === User::FIRSTNAME_BEFORE) {
                $name1 = 'firstname';
                $name2 = 'realname';
            } else {
                $name1 = 'realname';
                $name2 = 'firstname';
            }

            $where[] = [
                'OR' => [
                    'glpi_users.name' => ['LIKE', $user_filter],
                    'glpi_users.realname' => ['LIKE', $user_filter],
                    'glpi_users.firstname' => ['LIKE', $user_filter],
                    'RAW' => [
                        (string) QueryFunction::concat([
                            new QueryExpression($DB::quoteName("glpi_users.$name1")),
                            new QueryExpression($DB::quoteValue(' ')),
                            new QueryExpression($DB::quoteName("glpi_users.$name2")),
                        ]) => ['LIKE', $user_filter],
                    ],
                ],
            ];
        }

        if (isset($filters['status']) && $filters['status'] === 'active') {
            $where['glpi_user_session_history.logged_out_at'] = null;
        }

        if (isset($filters['type']) && $filters['type'] !== 'all') {
            //TODO
        }

        if (isset($filters['ip']) && $filters['ip'] !== '') {
            $ips = array_map('trim', explode(',', $filters['ip']));
            $ip_criteria = [];
            foreach ($ips as $ip) {
                $is_cidr = str_contains($ip, '/');
                if ($is_cidr) {
                    [$start_ip, $end_ip] = IPUtilities::cidrToRange($ip);
                    $ip_criteria[] = [
                        'RAW' => [
                            (string) QueryFunction::inet6Aton('ip_address') => [
                                'BETWEEN',
                                new QueryExpression(
                                    QueryFunction::inet6Aton(new QueryExpression($DB::quoteValue($start_ip)))
                                    . ' AND '
                                    . QueryFunction::inet6Aton(new QueryExpression($DB::quoteValue($end_ip)))
                                ),
                            ],
                        ],
                    ];
                } else {
                    $ip_criteria[] = ['ip_address' => $ip];
                }
            }
            $having[] = ['OR' => $ip_criteria];
        }

        return [
            'SELECT' => [
                'glpi_user_session_history.id',
                'glpi_user_session_history.users_id',
                'glpi_user_session_history.session_token_hash',
                QueryFunction::ifnull('glpi_user_sessions.ip_address', 'glpi_user_session_history.ip_address', 'ip_address'),
                QueryFunction::ifnull('glpi_user_sessions.user_agent', 'glpi_user_session_history.user_agent', 'user_agent'),
                'glpi_user_sessions.auth_type',
                QueryFunction::ifnull('glpi_user_sessions.created_at', 'glpi_user_session_history.logged_in_at', 'logged_in_at'),
                'glpi_user_sessions.last_activity_at',
                'glpi_user_session_history.logged_out_at',
                'glpi_user_session_history.logout_reason',
                'glpi_user_session_history.users_id_revoked_by',
            ],
            'FROM' => 'glpi_user_session_history',
            "LEFT JOIN" => $joins,
            'WHERE' => $where,
            'ORDER' => [
                'logged_out_at ASC',
                'logged_in_at DESC',
            ],
            'HAVING' => $having,
            'LIMIT' => 100,
        ];
    }

    /**
     * @param int $users_id
     * @param SessionFilterCriteria $filters
     * @return array<string, mixed>
     */
    private function getApiSessionsCriteria(int $users_id = 0, array $filters = []): array
    {

    }

    /**
     * @param int $users_id The user ID to filter sessions by. If 0, sessions for all users will be returned.
     * @param SessionFilterCriteria $filters
     * @return array
     */
    public function getSessions(int $users_id = 0, array $filters = []): array
    {
        global $DB;

        Profiler::getInstance()->start('SessionTracker::getSessions');


        $it = $DB->request($this->getWebSessionsCriteria($users_id, $filters));
        $sessions = [];

        Profiler::getInstance()->start('SessionTracker::getSessions - Create DeviceDetector instance');
        $dd = new DeviceDetector();
        $dd->skipBotDetection();
        Profiler::getInstance()->stop('SessionTracker::getSessions - Create DeviceDetector instance');

        $user_cache = [];

        Profiler::getInstance()->start('SessionTracker::getSessions - Loop sessions');
        foreach ($it as $data) {
            $dd->setUserAgent($data['user_agent']);
            $dd->parse();

            if (!isset($user_cache[$data['users_id']])) {
                $user_cache[$data['users_id']] = getUserName($data['users_id']);
            }
            $is_current_session = $data['session_token_hash'] === Session::getSessionTokenHash();
            $sessions[] = [
                'id' => $data['id'],
                'current_session' => $is_current_session,
                'users_id' => $data['users_id'],
                'user_name' => $user_cache[$data['users_id']],
                'session_token_hash' => $data['session_token_hash'],
                'ip_address' => $data['ip_address'],
                'auth_type' => $data['auth_type'],
                'logged_in_at' => $data['logged_in_at'],
                'last_activity_at' => $data['last_activity_at'],
                'logged_out_at' => $data['logged_out_at'],
                'logout_reason' => $data['logout_reason'],
                'users_id_revoked_by' => $data['users_id_revoked_by'],
                'user_agent_info' => [
                    'raw' => $data['user_agent'],
                    'client' => $dd->getClient(),
                    'os' => $dd->getOs(),
                ],
            ];
        }
        Profiler::getInstance()->stop('SessionTracker::getSessions - Loop sessions');

        Profiler::getInstance()->stop('SessionTracker::getSessions');
        return $sessions;
    }

    public function showSessionList(int $users_id = 0): void
    {
        $twig_params = [
            'vue_props' => [
            ],
        ];
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div id="session-tracker"></div>
            <script type="module">
                window.Vue.createApp(window.Vue.components['SessionTracker/SessionTracker'].component, {{ vue_props|json_encode|raw }}).mount('#session-tracker');
            </script>
TWIG, $twig_params);
    }
}
