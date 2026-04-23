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

use CommonGLPI;
use DeviceDetector\DeviceDetector;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryFunction;
use User;

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

    public function getSessions(int $users_id = 0): array
    {
        global $DB;

        $criteria = [];
        if ($users_id > 0) {
            $criteria['users_id'] = $users_id;
        }

        $it = $DB->request([
            'SELECT' => [
                'glpi_user_session_history.id',
                'glpi_user_session_history.users_id',
                'glpi_user_session_history.session_token_hash',
                QueryFunction::ifnull('glpi_user_sessions.ip_address', 'glpi_user_session_history.ip_address', 'ip_address'),
                QueryFunction::ifnull('glpi_user_sessions.user_agent', 'glpi_user_session_history.user_agent', 'user_agent'),
                'glpi_user_sessions.auth_type',
                'glpi_user_sessions.created_at',
                'glpi_user_sessions.last_activity_at',
                'glpi_user_session_history.logged_in_at',
                'glpi_user_session_history.logged_out_at',
                'glpi_user_session_history.logout_reason',
                'glpi_user_session_history.users_id_revoked_by',
            ],
            'FROM' => 'glpi_user_session_history',
            "LEFT JOIN" => [
                'glpi_user_sessions' => [
                    'ON' => [
                        'glpi_user_sessions' => 'session_token_hash',
                        'glpi_user_session_history' => 'session_token_hash', [
                            'AND' => [
                                'glpi_user_session_history.logged_out_at' => null,
                            ]
                        ]
                    ]
                ],
            ],
            'WHERE' => $criteria,
            'LIMIT' => 100,
        ]);
        $sessions = [];

        $dd = new DeviceDetector();

        $user_cache = [];
        $user = new User();

        foreach ($it as $data) {
            $dd->setUserAgent($data['user_agent']);
            $dd->parse();
            $data['user_agent_info'] = [
                'client' => $dd->getClient(),
                'os' => $dd->getOs(),
            ];
            unset($data['user_agent']);

            if (!isset($user_cache[$data['users_id']])) {
                $user_cache[$data['users_id']] = getUserName($data['users_id']);
            }
            $data['user_name'] = $user_cache[$data['users_id']];
            $sessions[] = $data;
        }

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
