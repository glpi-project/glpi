<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Api\HL\Search;
use Glpi\Form\Category;
use Glpi\Form\Form;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\UI\IllustrationManager;

#[Route(path: '/Forms', priority: 1, tags: ['Forms'])]
class FormController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        $schemas = [];
        $schemas['Form'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Form::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'uuid' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::PATTERN_UUIDV4,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'description' => ['type' => Doc\Schema::TYPE_STRING],
                'illustration' => ['type' => Doc\Schema::TYPE_STRING],
                'illustration_url' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'illustration',
                    'x-mapper' => static function ($v) {
                        return (new IllustrationManager())->getIconPath($v);
                    },
                ],
                'category' => self::getDropdownTypeSchema(class: Category::class, full_schema: 'FormCategory'),
                'entity' => self::getDropdownTypeSchema(class: \Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_draft' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_pinned' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        return $schemas;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search forms',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'Form[]'],
        ]
    )]
    public function searchForms(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Form', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{id}', methods: ['GET'], requirements: [
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a form by ID',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'Form'],
        ]
    )]
    public function getForm(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Form', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
