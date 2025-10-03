<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use CommonDBTM;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use Profile;
use Session;
use Toolbox;
use User;
use UserEmail;

/**
 * @phpstan-type EmailData = array{id: int, email: string, is_default: int, _links: array{'self': array{href: non-empty-string}}}
 */
#[Route(path: '/Administration', tags: ['Administration'])]
final class AdministrationController extends AbstractController
{
    use CRUDControllerTrait;

    public static function getRawKnownSchemas(): array
    {
        return [
            'User' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => User::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-rights-conditions' => [ // Object-level extra permissions
                    'read' => static function () {
                        if (!Session::canViewAllEntities()) {
                            return [
                                'LEFT JOIN' => [
                                    'glpi_profiles_users' => [
                                        'ON' => [
                                            'glpi_profiles_users' => 'users_id',
                                            'glpi_users' => 'id',
                                        ],
                                    ],
                                ],
                                'WHERE' => [
                                    'glpi_profiles_users.entities_id' => $_SESSION['glpiactiveentities'],
                                ],
                            ];
                        }
                        return true;
                    },
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'username' => [
                        'x-field' => 'name',
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Username',
                    ],
                    'realname' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Real name',
                    ],
                    'firstname' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'First name',
                    ],
                    'phone' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Phone number',
                    ],
                    'phone2' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Phone number 2',
                    ],
                    'mobile' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Mobile phone number',
                    ],
                    'emails' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'Email addresses',
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'EmailAddress',
                            'x-join' => [
                                'table' => 'glpi_useremails',
                                'fkey' => 'id',
                                'field' => 'users_id',
                                'primary-property' => 'id', // Help the search engine understand the 'id' property is this object's primary key since the fkey and field params are reversed for this join.
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'description' => 'ID',
                                ],
                                'email' => [
                                    'type' => Doc\Schema::TYPE_STRING,
                                    'description' => 'Email address',
                                ],
                                'is_default' => [
                                    'type' => Doc\Schema::TYPE_BOOLEAN,
                                    'description' => 'Is default',
                                ],
                                'is_dynamic' => [
                                    'type' => Doc\Schema::TYPE_BOOLEAN,
                                    'description' => 'Is dynamic',
                                ],
                            ],
                        ],
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                    'is_active' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is active',
                    ],
                    'is_deleted' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is deleted',
                    ],
                    'password' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_PASSWORD,
                        'description' => 'Password',
                        'writeOnly' => true,
                    ],
                    'password2' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_PASSWORD,
                        'description' => 'Password confirmation',
                        'writeOnly' => true,
                    ],
                    'picture' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'picture',
                        'x-mapper' => static function ($v) {
                            global $CFG_GLPI;
                            $path = Toolbox::getPictureUrl($v, false);
                            if (!empty($path)) {
                                return $path;
                            }
                            return $CFG_GLPI["root_doc"] . '/pics/picture.png';
                        },
                    ],
                ],
            ],
            'Group' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Group::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Name',
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                    'completename' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Complete name',
                    ],
                    'parent' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-itemtype' => Group::class,
                        'x-full-schema' => 'Group',
                        'x-join' => [
                            'table' => 'glpi_groups',
                            'fkey' => 'groups_id',
                            'field' => 'id',
                        ],
                        'description' => 'Parent group',
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'description' => 'Name',
                            ],
                        ],
                    ],
                    'level' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Level',
                    ],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                ],
            ],
            'Entity' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Entity::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Name',
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                    'completename' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Complete name',
                    ],
                    'parent' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-itemtype' => Entity::class,
                        'x-full-schema' => 'Entity',
                        'x-join' => [
                            'table' => 'glpi_entities',
                            'fkey' => 'entities_id',
                            'field' => 'id',
                        ],
                        'description' => 'Parent entity',
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'description' => 'Name',
                            ],
                        ],
                    ],
                    'level' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Level',
                    ],
                ],
            ],
            'Profile' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Profile::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Name',
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                ],
            ],
            'EmailAddress' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => UserEmail::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                    ],
                    'email' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Email address',
                    ],
                    'is_default' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is default',
                    ],
                    'is_dynamic' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is dynamic',
                    ],
                ],
            ],
        ];
    }

    #[Route(path: '/User', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'User')]
    public function searchUsers(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Group', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Group')]
    public function searchGroups(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Entity', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Entity')]
    public function searchEntities(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Profile', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Profile')]
    public function searchProfiles(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getParameters());
    }

    /**
     * @param int $users_id
     * @return EmailData[]
     */
    private function getEmailDataForUser(int $users_id): array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM' => UserEmail::getTable(),
            'WHERE' => [
                'users_id' => $users_id,
            ],
        ]);
        $emails = [];
        foreach ($iterator as $data) {
            $emails[] = [
                'id' => (int) $data['id'],
                'email' => (string) $data['email'],
                'is_default' => (int) $data['is_default'],
                '_links' => [
                    'self' => [
                        'href' => self::getAPIPathForRouteFunction(self::class, 'getMyEmail', ['id' => $data['id']]),
                    ],
                ],
            ];
        }
        return $emails;
    }

    #[Route(path: '/User/Me', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'User',
        description: 'Get the current user',
    )]
    public function me(Request $request): Response
    {
        $my_user_id = $this->getMyUserID();
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), ['id' => $my_user_id], $request->getParameters());
    }

    #[Route(path: '/User/Me/Email', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the current user\'s email addresses',
        responses: [
            new Doc\Response(new Doc\SchemaReference('EmailAddress[]')),
        ]
    )]
    public function getMyEmails(Request $request): Response
    {
        return new JSONResponse($this->getEmailDataForUser($this->getMyUserID()));
    }

    #[Route(path: '/User/Me/Email', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create a new email address for the current user',
        parameters: [
            new Doc\Parameter(
                name: 'email',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                description: 'The email address to add',
                location: Doc\Parameter::LOCATION_BODY,
                required: true,
            ),
            new Doc\Parameter(
                name: 'is_default',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_BOOLEAN, default: false),
                description: 'Whether this email address should be the default one',
                location: Doc\Parameter::LOCATION_BODY,
            ),
        ],
    )]
    public function addMyEmail(Request $request): Response
    {
        if (!$request->hasParameter('email')) {
            return self::getInvalidParametersErrorResponse([
                'missing' => ['email'],
            ]);
        }
        $new_email = $request->getParameter('email');
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            return self::getInvalidParametersErrorResponse([
                'invalid' => [
                    ['name' => 'email', 'reason' => 'The provided email address does not appear to be formatted as an email address'],
                ],
            ]);
        }
        // Check if the email address is already in the DB
        $emails = $this->getEmailDataForUser($this->getMyUserID());
        foreach ($emails as $email) {
            if ($email['email'] === $new_email) {
                return new JSONResponse(
                    self::getErrorResponseBody(self::ERROR_ALREADY_EXISTS, 'The provided email address is already associated with this user'),
                    409,
                    [
                        'Location' => self::getAPIPathForRouteFunction(self::class, 'getMyEmail', ['id' => $email['id']]),
                    ]
                );
            }
        }

        // Create the new email address
        $email = new UserEmail();
        $emails_id = $email->add([
            'users_id' => $this->getMyUserID(),
            'email' => $new_email,
            'is_default' => $request->hasParameter('is_default') ? $request->getParameter('is_default') : false,
        ]);
        return self::getCRUDCreateResponse($emails_id, self::getAPIPathForRouteFunction(self::class, 'getMyEmail', ['id' => $emails_id]));
    }

    #[Route(path: '/User/Me/Email/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'EmailAddress',
        description: 'Get a specific email address for the current user',
    )]
    public function getMyEmail(Request $request): Response
    {
        $emails = $this->getEmailDataForUser($this->getMyUserID());
        foreach ($emails as $email) {
            if ($email['id'] == $request->getAttribute('id')) {
                return new JSONResponse($email);
            }
        }
        return self::getNotFoundErrorResponse();
    }

    #[Route(path: '/User/Me/Emails/Default', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'EmailAddress',
        description: 'Get the default email address for the current user',
    )]
    public function getMyDefaultEmail(Request $request): Response
    {
        $emails = $this->getEmailDataForUser($this->getMyUserID());
        foreach ($emails as $email) {
            if ($email['is_default']) {
                return new JSONResponse($email);
            }
        }
        return self::getNotFoundErrorResponse();
    }

    /**
     * Get the specified user picture as a Response
     * @param string $username The username of the user. Used in Content-Disposition header.
     * @param string|null $picture_path The path to the picture from the user's "picture" field.
     * @return Response A response with the picture as binary content (or the placeholder user picture if the user has no picture).
     */
    private function getUserPictureResponse(string $username, ?string $picture_path): Response
    {
        if ($picture_path !== null) {
            $picture_path = GLPI_PICTURE_DIR . '/' . $picture_path;
        } else {
            $picture_path = 'public/pics/picture.png';
        }
        $symfony_response = Toolbox::getFileAsResponse($picture_path, $username);

        return new Response($symfony_response->getStatusCode(), $symfony_response->headers->all(), $symfony_response->getContent());
    }

    #[Route(path: '/User/Me/Picture', methods: ['GET'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the picture for the current user'
    )]
    public function getMyPicture(Request $request): Response
    {
        global $DB;
        $it = $DB->request([
            'SELECT' => ['name', 'picture'],
            'FROM' => User::getTable(),
            'WHERE' => [
                'id' => $this->getMyUserID(),
            ],
        ]);
        $data = $it->current();
        return $this->getUserPictureResponse($data['name'], $data['picture']);
    }

    #[Route(path: '/User', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'User')]
    public function createUser(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getUserByID']);
    }

    #[Route(path: '/User/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'User')]
    public function getUserByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/username/{username}', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'User')]
    public function getUserByUsername(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters(), 'username');
    }

    #[Route(path: '/User/{id}/Picture', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the picture for the current user'
    )]
    public function getUserPictureByID(Request $request): Response
    {
        global $DB;
        $it = $DB->request([
            'SELECT' => ['name', 'picture'],
            'FROM' => User::getTable(),
            'WHERE' => [
                'id' => $request->getAttribute('id'),
            ],
        ]);
        $data = $it->current();
        return $this->getUserPictureResponse($data['name'], $data['picture']);
    }

    #[Route(path: '/User/username/{username}/Picture', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the picture for the current user'
    )]
    public function getUserPictureByUsername(Request $request): Response
    {
        global $DB;
        $it = $DB->request([
            'SELECT' => ['name', 'picture'],
            'FROM' => User::getTable(),
            'WHERE' => [
                'name' => $request->getAttribute('username'),
            ],
        ]);
        $data = $it->current();
        return $this->getUserPictureResponse($data['name'], $data['picture']);
    }

    #[Route(path: '/User/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'User')]
    public function updateUserByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/username/{username}', methods: ['PATCH'], requirements: ['username' => '[a-zA-Z0-9_]+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'User')]
    public function updateUserByUsername(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters(), 'username');
    }

    #[Route(path: '/User/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'User')]
    public function deleteUserByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/username/{username}', methods: ['DELETE'], requirements: ['username' => '[a-zA-Z0-9_]+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'User')]
    public function deleteUserByUsername(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters(), 'username');
    }

    private function getUsedOrManagedItems(int $users_id, bool $is_managed, array $request_params, string $api_version): Response
    {
        global $CFG_GLPI;

        // Create a union schema with all relevant item types
        $schema = Doc\Schema::getUnionSchemaForItemtypes(
            itemtypes: array_filter($CFG_GLPI['assignable_types'], static function ($t) use ($is_managed) {
                if (!\is_a($t, CommonDBTM::class, true)) {
                    return false; // Ignore invalid classes
                }
                return (new $t())->isField($is_managed ? 'users_id_tech' : 'users_id');
            }),
            api_version: $api_version
        );
        $rsql_filter = $request_params['filter'] ?? '';
        if (!empty($rsql_filter)) {
            $rsql_filter = "($rsql_filter);";
        }
        $user_field = $is_managed ? 'user_tech.id' : 'user.id';
        $rsql_filter .= "$user_field==$users_id";
        $request_params['filter'] = $rsql_filter;
        return ResourceAccessor::searchBySchema($schema, $request_params);
    }

    #[Route(path: '/User/Me/UsedItem', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the used items for the current user',
    )]
    public function getMyUsedItems(Request $request): Response
    {
        return $this->getUsedOrManagedItems($this->getMyUserID(), false, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/{id}/UsedItem', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the used items for a user',
    )]
    public function getUserUsedItemsByID(Request $request): Response
    {
        return $this->getUsedOrManagedItems($request->getAttribute('id'), false, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/username/{username}/UsedItem', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the used items for a user by username',
    )]
    public function getUserUsedItemsByUsername(Request $request): Response
    {
        $users_id = ResourceAccessor::getIDForOtherUniqueFieldBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), 'username', $request->getAttribute('username'));
        return $this->getUsedOrManagedItems($users_id, false, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/Me/ManagedItem', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the managed items for the current user',
    )]
    public function getMyManagedItems(Request $request): Response
    {
        return $this->getUsedOrManagedItems($this->getMyUserID(), true, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/{id}/ManagedItem', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the managed items for a user',
    )]
    public function getUserManagedItemsByID(Request $request): Response
    {
        return $this->getUsedOrManagedItems($request->getAttribute('id'), true, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/username/{username}/ManagedItem', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the managed items for a user by username',
    )]
    public function getUserManagedItemsByUsername(Request $request): Response
    {
        $users_id = ResourceAccessor::getIDForOtherUniqueFieldBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), 'username', $request->getAttribute('username'));
        return $this->getUsedOrManagedItems($users_id, true, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/Group', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Group')]
    public function createGroup(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getGroupByID']);
    }

    #[Route(path: '/Group/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Group')]
    public function getGroupByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Group/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Group')]
    public function updateGroupByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Group/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Group')]
    public function deleteGroupByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Entity', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Entity')]
    public function createEntity(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getEntityByID']);
    }

    #[Route(path: '/Entity/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Entity')]
    public function getEntityByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Entity/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Entity')]
    public function updateEntityByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Entity/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Entity')]
    public function deleteEntityByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Profile', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Profile')]
    public function createProfile(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getProfileByID']);
    }

    #[Route(path: '/Profile/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Profile')]
    public function getProfileByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Profile/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Profile')]
    public function updateProfileByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Profile/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Profile')]
    public function deleteProfileByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
