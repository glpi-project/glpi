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
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\Section;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\UI\IllustrationManager;

#[Route(path: '/Form', tags: ['Forms'])]
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
                'sections' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'FormSection',
                        'x-join' => [
                            'table' => 'glpi_forms_sections', // The table with the desired data
                            'fkey' => 'id', // The field in the main table
                            'field' => 'forms_forms_id', // The field in the joined table
                            'primary-property' => 'id', // Help the search engine understand the 'id' property is this object's primary key since the fkey and field params are reversed for this join.
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['FormSection'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Section::class,
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
                'rank' => ['type' => Doc\Schema::TYPE_INTEGER],
                'form' => self::getDropdownTypeSchema(class: Form::class, full_schema: 'Form'),
                'questions' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'FormQuestion',
                        'x-join' => [
                            'table' => 'glpi_forms_questions', // The table with the desired data
                            'fkey' => 'id', // The field in the main table
                            'field' => 'forms_sections_id', // The field in the joined table
                            'primary-property' => 'id', // Help the search engine understand the 'id' property is this object's primary key since the fkey and field params are reversed for this join.
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ],
            ],
        ];

        $schemas['FormQuestion'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Question::class,
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
                'section' => self::getDropdownTypeSchema(class: Section::class, full_schema: 'FormSection'),
                'type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => array_map(
                        static fn($t) => $t::class,
                        QuestionTypesManager::getInstance()->getQuestionTypes()
                    ),
                    'x-readonly' => true,
                ],
                'is_mandatory' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'vertical_rank' => ['type' => Doc\Schema::TYPE_INTEGER],
                'horizontal_rank' => ['type' => Doc\Schema::TYPE_INTEGER],
                'default_value' => ['type' => Doc\Schema::TYPE_STRING],
                'extra_data' => ['type' => Doc\Schema::TYPE_STRING],
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

    #[Route(path: '/{form_id}/Section', methods: ['GET'], requirements: [
        'form_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search sections of a form by ID',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'FormSection[]'],
        ]
    )]
    public function searchFormSections(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';form.id==' . $request->getAttribute('form_id');
        $request->setParameter('filter', $filters);
        return Search::searchBySchema($this->getKnownSchema('FormSection', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{form_id}/Section/{section_id}/Question', methods: ['GET'], requirements: [
        'form_id' => '\d+',
        'section_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search questions in a form section',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'FormQuestion'],
        ]
    )]
    public function searchFormQuestions(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';section.id==' . $request->getAttribute('section_id');
        $request->setParameter('filter', $filters);
        return Search::searchBySchema($this->getKnownSchema('FormQuestion', $this->getAPIVersion($request)), $request->getParameters());
    }
}
