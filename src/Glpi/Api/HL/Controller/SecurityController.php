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

namespace Glpi\Api\HL\Controller;

use Auth;
use Config;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Security\SessionTracker;
use Glpi\Security\User_SessionHistory;
use GraphQL\Type\Definition\ResolveInfo;
use Session;
use stdClass;
use User;

use function Safe\json_decode;
use function Safe\preg_match;

#[Route(path: '/Security', tags: ['Security'])]
final class SecurityController extends AbstractController
{
    protected static function getRawKnownSchemas(string $api_version): array
    {
        return [
            'UserAgentInfo' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '3.0.0',
                'x-graphql-resolver' => [self::class, 'resolveUserAgentInfo'],
                'x-graphql-noquery' => true,
                'properties' => [
                    'user_agent_string' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                    ],
                    'is_bot' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'readOnly' => true,
                    ],
                    'client' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'properties' => [
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                            ],
                            'version' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                            ],
                        ],
                    ],
                    'os' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'properties' => [
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                                'enum' => OperatingSystem::getAvailableOperatingSystems(),
                            ],
                            'family' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                                'enum' => OperatingSystem::getAvailableOperatingSystemFamilies(),
                            ],
                            'version' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                            ],
                        ],
                    ],
                    'device' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'properties' => [
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                                'enum' => AbstractDeviceParser::getAvailableDeviceTypeNames(),
                            ],
                            'brand' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                            ],
                            'model' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'LoginSession' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '3.0.0',
                'x-graphql-resolver' => [self::class, 'resolveLoginSession'],
                'x-itemtype' => User_SessionHistory::class,
                'x-rights-conditions' => [
                    'read' => static function () {
                        if (Session::haveRight(Config::$rightname, UPDATE)) {
                            return true;
                        }
                        return [
                            'WHERE' => ['_.users_id' => Session::getLoginUserID()],
                        ];
                    },
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'user' => self::getDropdownTypeSchema(class: User::class, name_field: ['name', 'username'], full_schema: 'User') + ['readOnly' => true],
                    'login_session_uid' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                    ],
                    'ip_address' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                    ],
                    'user_agent_string' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'user_agent',
                        'readOnly' => true,
                    ],
                    'user_agent_info' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'UserAgentInfo',
                        'x-graphql-only' => true,
                        'readOnly' => true,
                        'properties' => [
                            'user_agent_string' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'readOnly' => true,
                            ],
                        ],
                    ],
                    'auth_type' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'enum' => [Auth::DB_GLPI, Auth::MAIL, Auth::LDAP, Auth::EXTERNAL, Auth::CAS, Auth::X509, Auth::API, Auth::OAUTH],
                        'description' => <<<EOT
                            The authentication type that was used to create the session. Possible values are:
                            - 1: GLPI database authentication
                            - 2: Email authentication
                            - 3: LDAP authentication
                            - 4: External authentication
                            - 5: CAS authentication
                            - 6: X509 authentication
                            - 7: (Legacy) API authentication
                            - 8: OAuth 2.0 authentication (API)
EOT,
                        'readOnly' => true,
                    ],
                    'logged_in_at' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                    ],
                    'last_activity_at' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                        'x-field' => 'last_activity_at',
                        'x-join' => [
                            'table' => 'glpi_users_sessions',
                            'field' => 'login_session_uid',
                            'fkey' => 'login_session_uid',
                        ],
                    ],
                    'logged_out_at' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                    ],
                    'logout_reason' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => [SessionTracker::REVOKE_REASON_USER, SessionTracker::REVOKE_REASON_EXPIRED, SessionTracker::REVOKE_REASON_ADMIN],
                        'description' => <<<EOT
                            The reason why the session was logged out. Possible values are:
                            - user: The user logged out manually
                            - expired: The session expired due to inactivity
                            - admin: The session was revoked by an administrator or by the user themselves
EOT,
                        'readOnly' => true,
                    ],
                    'revoker' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_revoked_by', name_field: ['name', 'username'], full_schema: 'User') + ['readOnly' => true],
                ],
            ],
        ];
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public static function resolveUserAgentInfo(mixed $source, array $args, stdClass $context, ResolveInfo $info): mixed
    {
        // pass-through resolver. info already loaded by the LoginSession resolver. This is just to satisfy the GraphQL type system.
        return $source[$info->fieldName] ?? null;
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public static function resolveLoginSession(mixed $source, array $args, stdClass $context, ResolveInfo $info): mixed
    {
        if ($info->fieldName !== 'LoginSession') {
            if ($info->fieldName === 'user_agent_info') {
                // use DeviceDetector to parse the user agent string
                $field_selection = $info->getFieldSelection(0);
                $user_agent_string = $source['user_agent_string'] ?? '';
                $dd = new DeviceDetector($user_agent_string);
                if (!array_key_exists('is_bot', $field_selection)) {
                    $dd->skipBotDetection();
                }
                $dd->parse();
                return [
                    'user_agent_string' => $user_agent_string,
                    'is_bot' => $dd->isBot(),
                    'client' => [
                        'name' => $dd->getClient('name'),
                        'version' => $dd->getClient('version'),
                    ],
                    'os' => [
                        'name' => $dd->getOs('name'),
                        'family' => $dd->getOs('family'),
                        'version' => $dd->getOs('version'),
                    ],
                    'device' => [
                        'type' => $dd->getDevice(),
                        'name' => $dd->getDeviceName(),
                        'brand' => $dd->getBrandName(),
                        'model' => $dd->getModel(),
                    ],
                ];
            }
            return $source[$info->fieldName] ?? null;
        }

        // Main login session data
        $response = ResourceAccessor::searchBySchema(self::getKnownSchemas($context->api_version)['LoginSession'], $args);
        if ($response->getStatusCode() >= 300) {
            return null;
        }
        // if the content-range header is present, we need to parse it and add the pagination data to the context
        if ($response->hasHeader('Content-Range')) {
            $content_range = $response->getHeaderLine('Content-Range');
            if (preg_match('/^(\d+)-(\d+)\/(\d+)$/', $content_range, $matches)) {
                $context->pagination[] = [
                    'start' => (int) $matches[1],
                    'limit' => (int) $matches[2] - (int) $matches[1] + 1,
                    'total_count' => (int) $matches[3],
                ];
            }
        }
        // return the results
        return json_decode((string) $response->getBody(), true);
    }

    #[Route(path: '/LoginSession/My', methods: ['GET'])]
    #[RouteVersion(introduced: '3.0')]
    #[Doc\SearchRoute(schema_name: 'LoginSession')]
    public function getMyLoginSessions(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';user.id==' . Session::getLoginUserID();
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('LoginSession', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/LoginSession/{users_id}', methods: ['GET'], requirements: ['users_id' => '\d+'])]
    #[RouteVersion(introduced: '3.0')]
    #[Doc\SearchRoute(schema_name: 'LoginSession')]
    public function getUserLoginSessions(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';user.id==' . $request->getAttribute('users_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('LoginSession', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/LoginSession/All', methods: ['GET'])]
    #[RouteVersion(introduced: '3.0')]
    #[Doc\SearchRoute(schema_name: 'LoginSession')]
    public function getAllLoginSessions(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('LoginSession', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/LoginSession/{login_session_uid}', methods: ['DELETE'], requirements: ['login_session_uid' => '\w+'])]
    #[RouteVersion(introduced: '3.0')]
    #[Doc\DeleteRoute(schema_name: 'LoginSession')]
    public function revokeSession(Request $request): Response
    {
        global $DB;

        $login_session_uid = $request->getAttribute('login_session_uid');
        $it = $DB->request([
            'SELECT' => ['users_id'],
            'FROM' => 'glpi_users_sessions',
            'WHERE' => ['login_session_uid' => $login_session_uid],
            'LIMIT' => 1,
        ]);
        $session = $it->current();
        $users_id = $session['users_id'] ?? null;

        if ($users_id !== Session::getLoginUserID() && !Config::canUpdate()) {
            return self::getAccessDeniedErrorResponse();
        }
        SessionTracker::revokeSession($login_session_uid, SessionTracker::REVOKE_REASON_ADMIN);
        return new Response(200);
    }
}
