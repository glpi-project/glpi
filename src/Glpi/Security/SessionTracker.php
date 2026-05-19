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
use Glpi\Debug\Profiler;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\OAuth\Server;
use Glpi\Toolbox\IPUtilities;
use Log;
use RuntimeException;
use Session;
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
                'created_at' => QueryFunction::now(),
                'last_activity_at' => QueryFunction::now(),
            ]);
            $DB->insert('glpi_user_session_history', [
                'users_id' => $_SESSION['glpiID'],
                'session_token_hash' => Session::getSessionTokenHash(),
                'ip_address' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'auth_type' => $auth->getAuthType(),
                'logged_in_at' => QueryFunction::now(),
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
        ]);
        $session = $it->current();
        $users_id = $session['users_id'] ?? null;

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

        $ips = array_map('trim', explode(',', $filter));
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
            'START' => $start,
            'LIMIT' => $_SESSION['glpilist_limit'],
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

        $criteria = [
            'SELECT' => [
                'glpi_oauth_access_tokens.uuid',
                'glpi_oauth_access_tokens.client',
                'glpi_oauth_access_tokens.date_expiration',
                'glpi_oauth_access_tokens.user_identifier',
                'glpi_oauth_access_tokens.scopes',
                'glpi_oauth_access_tokens.ip_address',
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
            'START' => $start,
            'LIMIT' => $_SESSION['glpilist_limit'],
        ];

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

        $type_filter = $filters['type'] ?? 'all';
        $it_php = $DB->request($this->getPHPSessionsCriteria($users_id, $filters, $start));
        $it_oauth = ($type_filter === 'api' || $type_filter === 'all') ? $DB->request($this->getOAuthSessionsCriteria($users_id, $filters, $start)) : [];
        $sessions = [];

        Profiler::getInstance()->start('SessionTracker::getSessions - Create DeviceDetector instance');
        $dd = new DeviceDetector();
        $dd->skipBotDetection();
        Profiler::getInstance()->stop('SessionTracker::getSessions - Create DeviceDetector instance');

        $user_cache = [];

        Profiler::getInstance()->start('SessionTracker::getSessions - Loop sessions');
        foreach ($it_php as $data) {
            $dd->setUserAgent($data['user_agent']);
            $dd->parse();

            if (!isset($user_cache[$data['users_id']])) {
                $user_cache[$data['users_id']] = getUserLink($data['users_id']);
            }
            $is_current_session = $data['session_token_hash'] === Session::getSessionTokenHash();
            $session = [
                'id' => $data['id'],
                'type_raw' => $data['auth_type'] === Auth::API ? 'api' : 'web',
                'current_session' => $is_current_session,
                'users_id' => $data['users_id'],
                'user' => $user_cache[$data['users_id']],
                'ip_address' => $data['ip_address'],
                'login' => $data['logged_in_at'],
                'last_activity' => $data['last_activity_at'] ?? $data['logged_out_at'],
                'logout_reason' => $data['logout_reason'],
                'users_id_revoked_by' => $data['users_id_revoked_by'],
                'details' => '',
            ];
            if ($data['auth_type'] === Auth::API) {
                $session['type'] = '<span class="d-flex gap-1"><i class="ti ti-api" aria-hidden="true"></i>' . __s('API') . '</span>';
            } else {
                $session['type'] = '<span class="d-flex gap-1"><i class="ti ti-world" aria-hidden="true"></i>' . __s('Browser') . '</span>';
            }
            if ($data['logout_reason']) {
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
            }

            $agent_browser_icons = [
                'chrome' => 'ti ti-brand-chrome',
                'firefox' => 'ti ti-brand-firefox',
                'edge' => 'ti ti-brand-edge',
                'safari' => 'ti ti-brand-safari',
                'opera' => 'ti ti-brand-opera',
            ];
            $agent_icon = 'ti ti-help';

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

            $session['actions'] = '';
            if (!$data['logged_out_at'] && !$is_current_session) {
                $session['actions'] .= '<button class="btn btn-outline-danger btn-sm gap-1 revoke-session" data-type="web" data-identifier="' . $data['session_token_hash'] . '">';
                $session['actions'] .= '<i class="ti ti-logout" aria-hidden="true"></i>' . __s('Revoke') . '</button>';
            }
            $sessions[] = $session;
        }

        $access_token_lifetime = Server::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES; // Ex: PT1H
        $access_token_lifetime_seconds = (new DateInterval($access_token_lifetime))->s
            + (new DateInterval($access_token_lifetime))->i * 60
            + (new DateInterval($access_token_lifetime))->h * 3600
            + (new DateInterval($access_token_lifetime))->d * 86400;

        foreach ($it_oauth as $data) {
            $session = [
                'id' => $data['uuid'],
                'type_raw' => 'api',
                'current_session' => false,
                'users_id' => $data['user_identifier'],
                'client' => $data['client'],
                'ip_address' => $data['ip_address'],
                'login' => '',
                'last_activity' => '',
            ];

            // Make an assumption of the generation time based on the current GLPI_OAUTH_ACCESS_TOKEN_EXPIRES value and the expiration. Only used for sorting.
            //TODO If we revoke all access tokens when GLPI updates, we can safely use this as the actual "login" time as GLPI_OAUTH_ACCESS_TOKEN_EXPIRES is not configurable by admins.
            $session['assumed_login'] = strtotime($data['date_expiration']) - $access_token_lifetime_seconds;

            $session['type'] = '<span class="d-flex gap-1"><i class="ti ti-api" aria-hidden="true"></i>' . __s('API') . '</span>';
            $session['details'] = '<span class="fw-bold">' . htmlescape($data['client_name']) . '</span>&nbsp;&middot;&nbsp;';
            $session['details'] .= '<span class="text-muted">' . implode(', ', json_decode($data['scopes'], true)) . '</span>';
            $session['status'] = '<span class="badge badge-outline bg-transparent text-success">' . __s('Active') . '</span>';
            $session['status'] .= '<br><span class="text-muted fs-5">' . sprintf(__s('Expires at %s'), $data['date_expiration']) . '</span>';
            if (is_numeric($session['users_id']) && $session['users_id'] !== $data['client']) {
                if (!isset($user_cache[(int) $session['users_id']])) {
                    $user_cache[(int) $session['users_id']] = getUserLink((int) $session['users_id']);
                }
                $session['user'] = $user_cache[(int) $session['users_id']];
            } else {
                $session['user'] = '<span class="text-muted">' . __s('Client credentials') . '</span>';
            }
            $session['actions'] = '<button class="btn btn-outline-danger btn-sm gap-1 revoke-session" data-type="api" data-identifier="' . $data['uuid'] . '">';
            $session['actions'] .= '<i class="ti ti-logout" aria-hidden="true"></i>' . __s('Revoke') . '</button>';
            $sessions[] = $session;
        }

        usort($sessions, static function ($a, $b) {
            if ($a['status'] === $b['status']) {
                return strtotime($b['login']) <=> strtotime($a['login']);
            }
            // sort by active first, then login (or assumed login for API sessions)
            if (str_contains($a['status'], 'text-success') && !str_contains($b['status'], 'text-success')) {
                return -1;
            } elseif (!str_contains($a['status'], 'text-success') && str_contains($b['status'], 'text-success')) {
                return 1;
            } else {
                $a_login = $a['assumed_login'] ?? strtotime($a['login']);
                $b_login = $b['assumed_login'] ?? strtotime($b['login']);
                return $b_login <=> $a_login;
            }
        });

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

        $criteria_php = $this->getPHPSessionsCriteria($users_id, $filters);
        $criteria_oauth = ($filters['type'] === 'api' || $filters['type'] === 'all') ? $this->getOAuthSessionsCriteria($users_id, $filters) : [];
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
