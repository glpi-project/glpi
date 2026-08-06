<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
                'x-itemtype' => Project::class,
                'type' => Doc\Schema::TYPE_OBJECT,
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
                    'entity' => self::getDropdownTypeSchema(\Entity::class),
                    'tasks' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-join' => [
                                'table' => 'glpi_projecttasks',
                                'fkey' => 'id',
                                'field' => 'projects_id',
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
            'Task' => [
                'x-itemtype' => ProjectTask::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'content' => ['type' => Doc\Schema::TYPE_STRING],
                    'project' => self::getDropdownTypeSchema(Project::class),
                    'parent_task' => self::getDropdownTypeSchema(ProjectTask::class),
                ]
            ],
        ];
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search projects',
        responses: [
            ['schema' => 'Project[]']
        ]
    )]
    public function searchProjects(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Project'), $request->getParameters());
    }

    #[Route(path: '/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a project by ID',
        responses: [
            ['schema' => 'Project']
        ]
    )]
    public function getProject(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Project'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new project', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Project',
        ]
    ])]
    public function createProject(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Project'), $request->getParameters(), [self::class, 'getProject']);
    }

    #[Route(path: '/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a project by ID',
        responses: [
            ['schema' => 'Project']
        ]
    )]
    public function updateProject(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Project'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a project by ID')]
    public function deleteProject(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Project'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Task', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search project tasks',
        responses: [
            ['schema' => 'Task[]']
        ]
    )]
    public function searchTasks(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Task'), $request->getParameters());
    }

    #[Route(path: '/Task/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a task by ID',
        responses: [
            ['schema' => 'Task']
        ]
    )]
    public function getTask(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Task'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Task', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new task', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Task',
        ]
    ])]
    public function createTask(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Task'), $request->getParameters(), [self::class, 'getTask']);
    }

    #[Route(path: '/Task/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a task by ID',
        responses: [
            ['schema' => 'Task']
        ]
    )]
    public function updateTask(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Task'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Task/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a task by ID')]
    public function deleteTask(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Task'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{project_id}/Task', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search project tasks',
        responses: [
            ['schema' => 'Task[]']
        ]
    )]
    public function searchLinkedTasks(Request $request): Response
    {
        $params = $request->getParameters();
        if (!isset($params['filter'])) {
            $params['filter'] = [];
        }
        $params['filter']['project'] = $request->getAttributes()['project_id'];
        return Search::searchBySchema($this->getKnownSchema('Task'), $params);
    }

    #[Route(path: '/{project_id}/Task', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new task', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Task',
        ]
    ])]
    public function createLinkedTask(Request $request): Response
    {
        $params = $request->getParameters();
        $params['project'] = $request->getAttributes()['project_id'];
        return Search::createBySchema($this->getKnownSchema('Task'), $params, [self::class, 'getTask']);
    }
}
