<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Api\HL\Search;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Project;
use Glpi\Api\HL\Doc as Doc;
use ProjectTask;

#[Route(path: '/Project', tags: ['Project'])]
final class ProjectController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'Project' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Project::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-rights-conditions' => [ // Object-level extra permissions
                    'read' => static function () {
                        if (!\Session::haveRight(Project::$rightname, Project::READALL)) {
                            if (!\Session::haveRight(Project::$rightname, Project::READMY)) {
                                return false; // Deny reading
                            }
                            $criteria = [
                                'LEFT JOIN' => [
                                    'glpi_projectteams' => [
                                        'ON' => [
                                            'glpi_projectteams' => 'projects_id',
                                            '_' => 'id'
                                        ]
                                    ]
                                ],
                                'WHERE' => [
                                    'OR' => [
                                        '_.users_id' => \Session::getLoginUserID(),
                                        [
                                            "glpi_projectteams.itemtype"   => 'User',
                                            "glpi_projectteams.items_id"   => \Session::getLoginUserID()
                                        ]
                                    ]
                                ]
                            ];
                            if (count($_SESSION['glpigroups'])) {
                                $criteria['WHERE']['OR'][] = [
                                    '_.groups_id' => $_SESSION['glpigroups'],
                                ];
                                $criteria['WHERE']['OR'][] = [
                                    "glpi_projectteams.itemtype"   => 'Group',
                                    "glpi_projectteams.items_id"   => $_SESSION['glpigroups']
                                ];
                            }
                            return $criteria;
                        }
                        return true; // Allow reading by default. No extra SQL conditions needed.
                    }
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'content' => ['type' => Doc\Schema::TYPE_STRING],
                    'code' => ['type' => Doc\Schema::TYPE_STRING],
                    'priority' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'enum' => [1, 2, 3, 4, 5, 6],
                    ],
                    'entity' => self::getDropdownTypeSchema(class: \Entity::class, full_schema: 'Entity'),
                    'tasks' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'ProjectTask',
                            'x-join' => [
                                'table' => 'glpi_projecttasks',
                                'fkey' => 'id',
                                'field' => 'projects_id',
                                'primary-property' => 'id'
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'x-readonly' => true,
                                ],
                                'name' => ['type' => Doc\Schema::TYPE_STRING],
                                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                                'content' => ['type' => Doc\Schema::TYPE_STRING],
                            ]
                        ],
                    ]
                ]
            ],
            'ProjectTask' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => ProjectTask::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-rights-conditions' => [ // Object-level extra permissions
                    'read' => static function () {
                        if (!\Session::haveRight(Project::$rightname, Project::READALL)) {
                            if (!\Session::haveRight(Project::$rightname, Project::READMY)) {
                                return false; // Deny reading
                            }
                            $project_criteria = [
                                'LEFT JOIN' => [
                                    'glpi_projectteams' => [
                                        'ON' => [
                                            'glpi_projectteams' => 'projects_id',
                                            'project' => 'id'
                                        ]
                                    ]
                                ],
                                'WHERE' => [
                                    'OR' => [
                                        '_.users_id' => \Session::getLoginUserID(),
                                        [
                                            "glpi_projectteams.itemtype"   => 'User',
                                            "glpi_projectteams.items_id"   => \Session::getLoginUserID()
                                        ]
                                    ]
                                ]
                            ];
                            if (count($_SESSION['glpigroups'])) {
                                $project_criteria['WHERE']['OR'][] = [
                                    'project.groups_id' => $_SESSION['glpigroups'],
                                ];
                                $project_criteria['WHERE']['OR'][] = [
                                    "glpi_projectteams.itemtype"   => 'Group',
                                    "glpi_projectteams.items_id"   => $_SESSION['glpigroups']
                                ];
                            }

                            $criteria = [
                                'LEFT JOIN' => [
                                    'glpi_projecttaskteams' => [
                                        'ON' => [
                                            'glpi_projecttaskteams' => 'projecttasks_id',
                                            'project' => 'id'
                                        ]
                                    ]
                                ] + $project_criteria['LEFT JOIN'],
                                'WHERE' => [
                                    'OR' => [
                                        '_.users_id' => \Session::getLoginUserID(),
                                        $project_criteria['WHERE'],
                                        [
                                            'glpi_projecttaskteams.items_id' => \Session::getLoginUserID(),
                                            'glpi_projecttaskteams.itemtype' => 'User'
                                        ]
                                    ]
                                ]
                            ];
                            if (count($_SESSION['glpigroups'])) {
                                $criteria['WHERE']['OR'][] = [
                                    'glpi_projecttaskteams.items_id' => $_SESSION['glpigroups'],
                                    'glpi_projecttaskteams.itemtype' => 'Group'
                                ];
                            }
                            return $criteria;
                        }
                        return true; // Allow reading by default. No extra SQL conditions needed.
                    }
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'content' => ['type' => Doc\Schema::TYPE_STRING],
                    'project' => self::getDropdownTypeSchema(class: Project::class, full_schema: 'Project'),
                    'parent_task' => self::getDropdownTypeSchema(class: ProjectTask::class, full_schema: 'ProjectTask'),
                ]
            ],
        ];
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search projects',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'Project[]']
        ]
    )]
    public function searchProjects(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Project', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a project by ID',
        responses: [
            ['schema' => 'Project']
        ]
    )]
    public function getProject(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Project', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Create a new project', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'schema' => 'Project',
        ]
    ])]
    public function createProject(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Project', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getProject']);
    }

    #[Route(path: '/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update a project by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'Project',
            ]
        ],
        responses: [
            ['schema' => 'Project']
        ]
    )]
    public function updateProject(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Project', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Delete a project by ID')]
    public function deleteProject(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Project', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Task', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search project tasks',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'ProjectTask[]']
        ]
    )]
    public function searchTasks(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('ProjectTask', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Task/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a task by ID',
        responses: [
            ['schema' => 'ProjectTask']
        ]
    )]
    public function getTask(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('ProjectTask', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Task', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Create a new task', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'schema' => 'ProjectTask',
        ]
    ])]
    public function createTask(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('ProjectTask', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getTask']);
    }

    #[Route(path: '/Task/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update a task by ID',
        responses: [
            ['schema' => 'ProjectTask']
        ]
    )]
    public function updateTask(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('ProjectTask', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Task/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Delete a task by ID')]
    public function deleteTask(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('ProjectTask', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{project_id}/Task', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search project tasks',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'ProjectTask[]']
        ]
    )]
    public function searchLinkedTasks(Request $request): Response
    {
        $params = $request->getParameters();
        if (!isset($params['filter'])) {
            $params['filter'] = [];
        }
        $params['filter']['project'] = $request->getAttributes()['project_id'];
        return Search::searchBySchema($this->getKnownSchema('ProjectTask', $this->getAPIVersion($request)), $params);
    }

    #[Route(path: '/{project_id}/Task', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Create a new task', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'schema' => 'ProjectTask',
        ]
    ])]
    public function createLinkedTask(Request $request): Response
    {
        $params = $request->getParameters();
        $params['project'] = $request->getAttributes()['project_id'];
        return Search::createBySchema($this->getKnownSchema('ProjectTask', $this->getAPIVersion($request)), $params, [self::class, 'getTask']);
    }
}
