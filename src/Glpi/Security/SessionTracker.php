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
use DateInterval;
use DeviceDetector\DeviceDetector;
use Glpi\Application\Environment;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QueryUnion;
use Glpi\Debug\Profiler;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\OAuth\Server;
use Glpi\Toolbox\IPUtilities;
use Log;
use RuntimeException;
use Safe\Exceptions\NetworkException;
use Session;
use Throwable;
use User;

use function Safe\ini_get;
use function Safe\json_decode;
use function Safe\parse_url;
use function Safe\session_id;
use function Safe\session_save_path;
use function Safe\strtotime;
use function Safe\unlink;

/**
 * @phpstan-type SessionFilterCriteria array{user?: string, status?: 'all'|'active', type?: 'all'|'web'|'api', ip?: string}
 */
final class SessionTracker extends CommonGLPI
{
    /**
     * Checks if the given session token hash corresponds to a known active session.
     * @param string $session_token_hash
     * @return bool
     */
    public static function isSessionValid(string $session_token_hash): bool
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_user_sessions',
            'WHERE' => ['session_token_hash' => $session_token_hash],
        ]);
        return $it->count() > 0;
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

        $ip = IPUtilities::getClientIP();
        if (isCommandLine() && Environment::get() !== Environment::TESTING) {
            // Do not record sessions for command line requests.
            return true;
        } elseif (isCommandLine()) {
            $ip = '::1';
        }

        try {
            $DB->insert('glpi_user_sessions', [
                'users_id' => $_SESSION['glpiID'],
                'session_token_hash' => Session::getSessionTokenHash(),
                'session_file' => 'sess_' . session_id(),
                'ip_address' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'auth_type' => $auth->getAuthType(),
                'created_at' => Session::getCurrentTime(),
                'last_activity_at' => Session::getCurrentTime(),
            ]);
            $DB->insert('glpi_user_session_history', [
                'users_id' => $_SESSION['glpiID'],
                'session_token_hash' => Session::getSessionTokenHash(),
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
     * Revokes a session by its token hash. If the reason is 'admin', the current user must have admin rights or be the owner of the session to revoke it.
     * @param string $session_token_hash
     * @param 'user'|'admin'|'expired' $reason
     * @return void
     * @throws AccessDeniedHttpException
     */
    public static function revokeSession(string $session_token_hash, string $reason): void
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['users_id', 'session_file'],
            'FROM' => 'glpi_user_sessions',
            'WHERE' => ['session_token_hash' => $session_token_hash],
            'LIMIT' => 1,
        ]);
        $session = $it->current();
        $users_id = $session['users_id'] ?? null;

        $DB->delete('glpi_user_sessions', ['session_token_hash' => $session_token_hash]);
        $DB->update('glpi_user_session_history', [
            'logged_out_at' => Session::getCurrentTime(),
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

        if (ini_get('session.save_handler') === 'files' && $session) {
            $session_file_path = session_save_path() . DIRECTORY_SEPARATOR . $session['session_file'];
            if (file_exists($session_file_path)) {
                @unlink($session_file_path);
            }
        }

        if ($reason === 'admin' && $users_id) {
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

        $current_session_token_hash = Session::getSessionTokenHash();
        $where = [
            'NOT' => ['session_token_hash' => $current_session_token_hash],
        ];
        if ($users_id > 0) {
            $where['users_id'] = $users_id;
        }

        $it_active_sessions = $DB->request([
            'SELECT' => ['users_id', 'session_token_hash'],
            'FROM' => 'glpi_user_sessions',
            'WHERE' => $where,
        ]);

        foreach ($it_active_sessions as $session) {
            self::revokeSession($session['session_token_hash'], 'admin');
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
            'SELECT' => ['session_token_hash'],
            'FROM' => 'glpi_user_sessions',
            'WHERE' => [
                'last_activity_at' => ['<', $threshold_time],
            ],
        ]);

        foreach ($it as $session) {
            self::revokeSession($session['session_token_hash'], 'expired');
        }
    }

    /**
     * @param string $filter
     * @return array<string, mixed>
     */
    private function getIPAddressHavingCriteria(string $filter): array
    {
        global $DB;

        try {
            $ips = array_map('trim', explode(',', $filter));
            $ip_criteria = [];
            foreach ($ips as $ip) {
                $is_cidr = str_contains($ip, '/');
                if ($is_cidr) {
                    [$start_ip, $end_ip] = IPUtilities::cidrToRange($ip);
                    $ip_criteria[] = [
                        'RAW' => [
                            (string)QueryFunction::inet6Aton('ip_address') => [
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
        } catch (NetworkException) {
            Session::addMessageAfterRedirect(__s('Invalid IP address filter'), true, ERROR);
            return [];
        }
        return ['OR' => $ip_criteria];
    }

    /**
     * @param int $users_id
     * @param SessionFilterCriteria $filters
     * @param int $start
     * @return array<string, mixed>
     */
    private function getPHPSessionsCriteria(int $users_id = 0, array $filters = [], int $start = 0): array
    {
        global $DB;

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
            $where['glpi_user_session_history.users_id'] = $users_id;
        }

        if (isset($filters['type']) && $filters['type'] === 'api') {
            $where['glpi_user_session_history.auth_type'] = Auth::API;
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

        if (isset($filters['ip']) && $filters['ip'] !== '') {
            $having[] = $this->getIPAddressHavingCriteria($filters['ip']);
        }

        return [
            'SELECT' => [
                new QueryExpression($DB::quoteValue('web'), '_type'),
                'glpi_user_session_history.id',
                new QueryExpression('glpi_user_session_history.users_id', 'user_identifier'),
                'glpi_user_session_history.session_token_hash',
                QueryFunction::ifnull('glpi_user_sessions.ip_address', 'glpi_user_session_history.ip_address', 'ip_address'),
                QueryFunction::ifnull('glpi_user_sessions.user_agent', 'glpi_user_session_history.user_agent', 'user_agent'),
                'glpi_user_sessions.auth_type',
                QueryFunction::ifnull('glpi_user_sessions.created_at', 'glpi_user_session_history.logged_in_at', 'logged_in_at'),
                'glpi_user_sessions.last_activity_at',
                'glpi_user_session_history.logged_out_at',
                'glpi_user_session_history.logout_reason',
                'glpi_user_session_history.users_id_revoked_by',
                new QueryExpression('NULL', 'date_expiration'),
                new QueryExpression('NULL', 'scopes'),
                new QueryExpression('NULL', 'client'),
                new QueryExpression('NULL', 'client_name'),
            ],
            'FROM' => 'glpi_user_session_history',
            "LEFT JOIN" => $joins,
            'WHERE' => $where,
            'HAVING' => $having,
        ];
    }

    /**
     * @param int $users_id
     * @param SessionFilterCriteria $filters
     * @return array<string, mixed>
     */
    private function getOAuthSessionsCriteria(int $users_id = 0, array $filters = [], int $start = 0): array
    {
        global $DB;

        $access_token_lifetime = Server::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES; // Ex: PT1H
        $access_token_lifetime_seconds = (new DateInterval($access_token_lifetime))->s
            + (new DateInterval($access_token_lifetime))->i * 60
            + (new DateInterval($access_token_lifetime))->h * 3600
            + (new DateInterval($access_token_lifetime))->d * 86400;

        $criteria = [
            'SELECT' => [
                new QueryExpression($DB::quoteValue('api'), '_type'),
                new QueryExpression('glpi_oauth_access_tokens.uuid', 'id'),
                'glpi_oauth_access_tokens.user_identifier',
                new QueryExpression('NULL', 'session_token_hash'),
                'glpi_oauth_access_tokens.ip_address',
                new QueryExpression('NULL', 'user_agent'),
                new QueryExpression('NULL', 'auth_type'),
                QueryFunction::dateSub(
                    date: 'glpi_oauth_access_tokens.date_expiration',
                    interval: $access_token_lifetime_seconds,
                    interval_unit: 'SECOND',
                    alias: 'logged_in_at',
                ),
                new QueryExpression('NULL', 'last_activity_at'),
                new QueryExpression('NULL', 'logged_out_at'),
                new QueryExpression('NULL', 'logout_reason'),
                new QueryExpression('NULL', 'users_id_revoked_by'),
                'glpi_oauth_access_tokens.date_expiration',
                'glpi_oauth_access_tokens.scopes',
                'glpi_oauth_access_tokens.client',
                'glpi_oauthclients.name AS client_name',
            ],
            'FROM' => 'glpi_oauth_access_tokens',
            'LEFT JOIN' => [
                'glpi_oauthclients' => [
                    'ON' => [
                        'glpi_oauthclients' => 'identifier',
                        'glpi_oauth_access_tokens' => 'client',
                    ],
                ],
            ],
            'WHERE' => [],
            'HAVING' => [],
        ];

        if (!in_array($filters['type'] ?? 'all', ['all', 'api'], true)) {
            $criteria['WHERE'][] = new QueryExpression('false');
        }

        if ($users_id > 0) {
            $criteria['WHERE']['user_identifier'] = $users_id;
        }

        if (isset($filters['user']) && $filters['user'] !== '') {
            $criteria['LEFT JOIN']['glpi_users'] = [
                'ON' => [
                    'glpi_users' => 'id',
                    'glpi_oauth_access_tokens' => 'user_identifier',
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

            $criteria['WHERE'][] = [
                'AND' => [
                    'NOT' => ['glpi_oauth_access_tokens.user_identifier' => new QueryExpression($DB::quoteName('glpi_oauth_access_tokens.client'))],
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
                ],
            ];
        }

        if (isset($filters['ip']) && $filters['ip'] !== '') {
            $criteria['HAVING'][] = $this->getIPAddressHavingCriteria($filters['ip']);
        }

        return $criteria;
    }

    /**
     * @param int $users_id The user ID to filter sessions by. If 0, sessions for all users will be returned.
     * @param SessionFilterCriteria $filters
     * @param int $start The offset for pagination
     * @return list<array<string, mixed>>
     */
    public function getSessions(int $users_id = 0, array $filters = [], int $start = 0): array
    {
        global $DB;

        Profiler::getInstance()->start('SessionTracker::getSessions');

        $query = new QueryUnion([
            $this->getPHPSessionsCriteria($users_id, $filters, $start),
            $this->getOAuthSessionsCriteria($users_id, $filters, $start)
        ]);
        $it = $DB->request([
            'SELECT' => '*',
            'FROM' => $query,
            'ORDER' => [
                new QueryExpression('CASE WHEN ' . $DB::quoteName('logged_out_at') . ' IS NULL THEN 0 ELSE 1 END'),
                'logged_in_at'
            ],
            'START' => $start,
            'LIMIT' => $_SESSION['glpilist_limit'],
        ]);

        $sessions = [];

        Profiler::getInstance()->start('SessionTracker::getSessions - Create DeviceDetector instance');
        $dd = new DeviceDetector();
        $dd->skipBotDetection();
        Profiler::getInstance()->stop('SessionTracker::getSessions - Create DeviceDetector instance');

        $user_cache = [];
        $agent_browser_icons = [
            'chrome' => 'ti ti-brand-chrome',
            'firefox' => 'ti ti-brand-firefox',
            'edge' => 'ti ti-brand-edge',
            'safari' => 'ti ti-brand-safari',
            'opera' => 'ti ti-brand-opera',
        ];
        $agent_icon = 'ti ti-help';

        Profiler::getInstance()->start('SessionTracker::getSessions - Loop sessions');
        foreach ($it as $data) {
            if (!isset($user_cache[$data['user_identifier']])) {
                $user_cache[$data['user_identifier']] = getUserLink($data['user_identifier']);
            }
            $is_current_session = $data['_type'] === 'web' && $data['session_token_hash'] === Session::getSessionTokenHash();

            $is_real_user = is_numeric($data['user_identifier']) && $data['user_identifier'] !== $data['client'];
            $user = $is_real_user ? $user_cache[$data['user_identifier']] : ('<span class="text-muted">' . __s('Client credentials') . '</span>');

            $session = [
                'id' => $data['id'],
                'type_raw' => $data['_type'],
                'current_session' => $is_current_session,
                'users_id' => $data['user_identifier'],
                'user' => $user,
                'ip_address' => $data['ip_address'],
                'login' => $data['logged_in_at'],
                'last_activity' => $data['last_activity_at'] ?? $data['logged_out_at'],
                'logout_reason' => $data['logout_reason'],
                'users_id_revoked_by' => $data['users_id_revoked_by'],
            ];

            if ($data['_type'] === 'api' || $data['auth_type'] === Auth::API) {
                $session['type'] = '<span class="d-flex gap-1"><i class="ti ti-api" aria-hidden="true"></i>' . __s('API') . '</span>';
            } else {
                $session['type'] = '<span class="d-flex gap-1"><i class="ti ti-world" aria-hidden="true"></i>' . __s('Browser') . '</span>';
            }

            if ($data['_type'] === 'api') {
                $session['details'] = '<span class="fw-bold">' . htmlescape($data['client_name']) . '</span>&nbsp;&middot;&nbsp;';
                $session['details'] .= '<span class="text-muted">' . implode(', ', json_decode($data['scopes'], true)) . '</span>';
            } else {
                $dd->setUserAgent($data['user_agent']);
                $dd->parse();

                $client = $dd->getClient();
                $os = $dd->getOs();
                if (is_array($client) && is_array($os)) {
                    if ($client['type'] === 'browser') {
                        $agent_icon = $agent_browser_icons[strtolower($client['name'])] ?? $agent_icon;
                        $agent_description = $client['name'] . ' ' . $client['version'] . ' - ' . $os['name'] . ' ' . $os['version'];
                    } else {
                        $agent_description = $client['name'] . ' ' . $client['version'];
                    }

                    $session['details'] = '<i class="' . $agent_icon . ' me-1" aria-hidden="true"></i>' . htmlescape($agent_description);
                    if ($is_current_session) {
                        $session['details'] .= ' <span class="badge badge-outline bg-transparent text-info">' . __s('Current session') . '</span>';
                    }
                }
                $session['details'] = '<span>' . $session['details'] . '</span>';
            }

            if ($data['_type'] === 'web' && $data['logout_reason']) {
                $reason_label = match ($data['logout_reason']) {
                    'user' => _sx('logout_reason', 'User logout'),
                    'admin' => _sx('logout_reason', 'Admin revoked'),
                    'expired' => _sx('logout_reason', 'Session expired'),
                    default => $data['logout_reason'],
                };
                $reason_class = match ($data['logout_reason']) {
                    'user' => 'badge badge-outline bg-transparent text-success',
                    'admin' => 'badge badge-outline bg-transparent text-danger',
                    default => 'badge badge-outline bg-transparent text-info',
                };
                $session['status'] = '<span class="' . $reason_class . '">' . $reason_label . '</span>';
            } else {
                $session['status'] = '<span class="badge badge-outline bg-transparent text-success">' . __s('Active') . '</span>';
                if ($data['_type'] === 'api') {
                    $session['status'] .= '<br><span class="text-muted fs-5">' . sprintf(__s('Expires at %s'), $data['date_expiration']) . '</span>';
                }
            }

            $session['actions'] = '';
            if ($data['_type'] === 'web' && !$data['logged_out_at'] && !$is_current_session) {
                $session['actions'] .= '<button class="btn btn-outline-danger btn-sm gap-1 revoke-session" data-type="web" data-identifier="' . $data['session_token_hash'] . '">';
                $session['actions'] .= '<i class="ti ti-logout" aria-hidden="true"></i>' . __s('Revoke') . '</button>';
            } elseif ($data['_type'] === 'api') {
                $session['actions'] .= '<button class="btn btn-outline-danger btn-sm gap-1 revoke-session" data-type="api" data-identifier="' . $data['id'] . '">';
                $session['actions'] .= '<i class="ti ti-logout" aria-hidden="true"></i>' . __s('Revoke') . '</button>';
            }

            $sessions[] = $session;
        }

        Profiler::getInstance()->stop('SessionTracker::getSessions - Loop sessions');
        Profiler::getInstance()->stop('SessionTracker::getSessions');
        return $sessions;
    }

    /**
     * @param int $users_id
     * @param SessionFilterCriteria $filters
     * @return int
     */
    private function getSessionsCount(int $users_id = 0, array $filters = []): int
    {
        global $DB;

        $type = $filters['type'] ?? 'all';
        $criteria_php = $this->getPHPSessionsCriteria($users_id, $filters);
        $criteria_oauth = ($type === 'api' || $type === 'all') ? $this->getOAuthSessionsCriteria($users_id, $filters) : [];
        // Remove pagination and ordering for count query
        unset($criteria_php['ORDER'], $criteria_php['LIMIT'], $criteria_php['START'],$criteria_oauth['ORDER'], $criteria_oauth['LIMIT'], $criteria_oauth['START']);
        return $DB->request($criteria_php)->count() + ($criteria_oauth ? $DB->request($criteria_oauth)->count() : 0);
    }

    /**
     * Shows the session list
     * @param int $users_id
     * @return void
     */
    public function showSessionList(int $users_id = 0): void
    {
        $users_id = (int) ($_GET['users_id'] ?? $users_id);
        $filters = [
            'user' => $_GET['user'] ?? '',
            'status' => $_GET['status'] ?? 'active',
            'type' => $_GET['type'] ?? 'all',
            'ip' => $_GET['ip'] ?? '',
        ];
        $start = (int) ($_GET['start'] ?? 0);

        if ($users_id !== Session::getLoginUserID() && !Session::haveRight('config', UPDATE)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        if ($users_id > 0) {
            unset($filters['user']);
        }

        TemplateRenderer::getInstance()->display('pages/setup/sessiontracker/sessiontracker.html.twig', [
            'sessions' => $this->getSessions($users_id, $filters, $start),
            'sessions_count' => $this->getSessionsCount($users_id, $filters),
            'filters' => $filters,
            'start' => $start,
            'href' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'is_admin_view' => $users_id === 0,
        ]);
    }
}
