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

use Entity;
use Glpi\Api\HL\APIException;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Api\HL\RSQL\RSQLException;
use Glpi\Api\HL\Search;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Category;
use Glpi\Form\Condition\ValidationStrategy;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\RenderLayout;
use Glpi\Form\Section;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GraphQL\Type\Definition\ResolveInfo;
use Session;
use stdClass;
use Throwable;

use function Safe\json_encode;

#[Route(path: '/Form', priority: 1, tags: ['Forms'])]
final class FormController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        // Fix enums issues that ruin snapshot comparison tests
        $render_layouts = array_map(static fn($l) => $l->value, RenderLayout::cases());
        sort($render_layouts);
        $visibility_strategies = array_map(static fn($s) => $s->value, VisibilityStrategy::cases());
        sort($visibility_strategies);
        $validation_strategies = array_map(static fn($s) => $s->value, ValidationStrategy::cases());
        sort($validation_strategies);
        $question_types = array_filter(
            array: array_map(static fn($t) => $t::class, QuestionTypesManager::getInstance()->getQuestionTypes()),
            callback: static fn($t) => !str_starts_with($t, 'GlpiPlugin\\Tester\\'),
        );

        return [
            'Form' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-itemtype' => Form::class,
                'x-graphql-resolver' => [self::class, 'graphQLResolveForms'],
                'x-rights-conditions' => [
                    'read' => static function () {
                        if (Session::haveRight(Form::$rightname, READ)) {
                            return true;
                        }

                        global $DB;

                        $criteria = [
                            'LEFT JOIN' => [
                                FormAccessControl::getTable() => [
                                    'ON' => [
                                        FormAccessControl::getTable() => Form::getForeignKeyField(),
                                        '_' => 'id', [
                                            'AND' => [
                                                FormAccessControl::getTable() . '.strategy' => AllowList::class,
                                                FormAccessControl::getTable() . '.is_active' => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'WHERE' => [
                                'OR' => [],
                                '_.is_deleted' => 0,
                                '_.is_draft' => 0,
                                '_.is_active' => 1,
                            ],
                        ];
                        // directly allowed - need to check if the current user's ID is in the "user_ids" array in the config JSON of the access control record
                        $user_ids = ['all', Session::getLoginUserID()];
                        $criteria['WHERE']['OR'][] = [
                            QueryFunction::jsonOverlaps(
                                doc1: QueryFunction::jsonExtract([FormAccessControl::getTable() . '.config', new QueryExpression($DB::quoteValue('$.user_ids'))]),
                                doc2: new QueryExpression($DB::quoteValue(json_encode($user_ids)))
                            ),
                        ];

                        // allowed by group
                        $groups = array_values($_SESSION['glpigroups'] ?? []);
                        if ($groups !== []) {
                            $criteria['WHERE']['OR'][] = [
                                QueryFunction::jsonOverlaps(
                                    doc1: QueryFunction::jsonExtract([FormAccessControl::getTable() . '.config', new QueryExpression($DB::quoteValue('$.group_ids'))]),
                                    doc2: new QueryExpression($DB::quoteValue(json_encode($groups)))
                                ),
                            ];
                        }

                        // allowed by profile
                        $profile_id = $_SESSION["glpiactiveprofile"]['id'];
                        $criteria['WHERE']['OR'][] = [
                            QueryFunction::jsonOverlaps(
                                doc1: QueryFunction::jsonExtract([FormAccessControl::getTable() . '.config', new QueryExpression($DB::quoteValue('$.profile_ids'))]),
                                doc2: new QueryExpression($DB::quoteValue(json_encode([$profile_id])))
                            ),
                        ];

                        return $criteria;
                    },
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'uuid' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::PATTERN_UUIDV4,
                        'readOnly' => true,
                    ],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_draft' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_pinned' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'render_layout' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => $render_layouts,
                        'default' => RenderLayout::STEP_BY_STEP->value,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'form_description' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'x-field' => 'header',
                    ],
                    'service_catalog_description' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'x-field' => 'description',
                    ],
                    'illustration' => ['type' => Doc\Schema::TYPE_STRING],
                    'category' => self::getDropdownTypeSchema(class: Category::class, full_schema: 'FormCategory'),
                    'usage_count' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'submit_button_visibility_strategy' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => $visibility_strategies,
                        'default' => VisibilityStrategy::ALWAYS_VISIBLE->value,
                    ],
                    'submit_button_conditions' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => <<<EOT
                            JSON encoded array of conditions to apply to the submit button.
                            The order of the conditions is important as it affects the evaluation with the logic_operator value.
                            Each condition will contain an `item_type` and an `item_uuid` field to specify the item the condition is based on and `value_operator` to specify the condition operator.
                            A `logic_operator` field can be set to specify how the condition should be evaluated with the next condition. If not set, it will default to "AND".
                            If the `value_operator` uses a value, a `value` field will also be present to specify the value to compare with.
EOT,
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'access_controls' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'FormAccessControl',
                            'x-join' => [
                                'table' => FormAccessControl::getTable(),
                                'fkey' => 'id',
                                'field' => Form::getForeignKeyField(),
                                'primary-property' => 'id',
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'readOnly' => true,
                                ],
                                'strategy' => [
                                    'type' => Doc\Schema::TYPE_STRING,
                                    'enum' => [
                                        AllowList::class,
                                        DirectAccess::class,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'sections' => self::getChildrenTypeSchema(
                        parent_class: Form::class,
                        class: Section::class,
                        full_schema: 'FormSection',
                        graphql_only: true,
                    ),
                ],
            ],
            'FormCategory' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-itemtype' => Category::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'completename' => ['type' => Doc\Schema::TYPE_STRING, 'readOnly' => true],
                    'description' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_HTML],
                    'illustration' => ['type' => Doc\Schema::TYPE_STRING],
                    'parent' => self::getDropdownTypeSchema(class: Category::class, full_schema: 'FormCategory'),
                    'level' => ['type' => Doc\Schema::TYPE_INTEGER, 'readOnly' => true],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'FormAccessControl' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-graphql-no-query' => true,
                'x-itemtype' => FormAccessControl::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'form' => self::getDropdownTypeSchema(class: Form::class, full_schema: 'Form'),
                    'strategy' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => [
                            AllowList::class,
                            DirectAccess::class,
                        ],
                    ],
                    'config' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => <<<EOT
                            JSON encoded configuration for the access control strategy. The content of this field will depend on the strategy used.
                            DirectAccess strategy contains a `token` string and `allow_unauthenticated` boolean in its config.
                            AllowList strategy contains `user_ids`, `group_ids` and `profile_ids` array fields in its config where the values are IDs of the items or 'all' (for users only).
EOT,
                    ],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                ],
            ],
            'FormSection' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-graphql-no-query' => true,
                'x-graphql-resolver' => [self::class, 'graphQLResolveFormSections'],
                'x-itemtype' => Section::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'form' => self::getDropdownTypeSchema(class: Form::class, full_schema: 'Form'),
                    'uuid' => ['type' => Doc\Schema::TYPE_STRING, 'pattern' => Doc\Schema::PATTERN_UUIDV4, 'readOnly' => true],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'default' => ''],
                    'description' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_HTML],
                    'rank' => ['type' => Doc\Schema::TYPE_INTEGER, 'default' => 0],
                    'visibility_strategy' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => $visibility_strategies,
                        'default' => VisibilityStrategy::ALWAYS_VISIBLE->value,
                    ],
                    'conditions' => ['type' => Doc\Schema::TYPE_STRING],
                    'questions' => self::getChildrenTypeSchema(
                        parent_class: Section::class,
                        class: Question::class,
                        full_schema: 'FormQuestion',
                        graphql_only: true,
                    ),
                ],
            ],
            'FormQuestion' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-graphql-no-query' => true,
                'x-itemtype' => Question::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'section' => self::getDropdownTypeSchema(class: Section::class, full_schema: 'FormSection'),
                    'uuid' => ['type' => Doc\Schema::TYPE_STRING,  'pattern' => Doc\Schema::PATTERN_UUIDV4, 'readOnly' => true],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'default' => ''],
                    'type' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => $question_types,
                    ],
                    'is_mandatory' =>  ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'vertical_rank' => ['type' => Doc\Schema::TYPE_INTEGER, 'default' => 0],
                    'horizontal_rank' => ['type' => Doc\Schema::TYPE_INTEGER, 'default' => 0],
                    'description' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_HTML],
                    'default_value' =>  ['type' => Doc\Schema::TYPE_STRING],
                    'extra_data' => ['type' => Doc\Schema::TYPE_STRING, 'description' => 'JSON encoded value'],
                    'visibility_strategy' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => $visibility_strategies,
                        'default' => VisibilityStrategy::ALWAYS_VISIBLE->value,
                    ],
                    'visibility_conditions' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'conditions'],
                    'validation_strategy' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => $validation_strategies,
                        'default' => ValidationStrategy::NO_VALIDATION->value,
                    ],
                    'validation_conditions' => ['type' => Doc\Schema::TYPE_STRING],
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
    public static function graphQLResolveForms(mixed $source, array $args, stdClass $context, ResolveInfo $info): mixed
    {
        if (!empty($source)) {
            return $source[$info->fieldName] ?? null;
        }
        $results = self::getFormResults($context->api_version, $args);

        $field_selection = $info->getFieldSelection();
        if ($field_selection['sections'] ?? false) {
            // getFormResults relies on the REST search and "sections" are only available in GraphQL so we need to manually fetch it here.
            foreach ($results['results'] as &$result) {
                $result['sections'] = self::getFormSectionsResults($context->api_version, ['filter' => 'form.id==' . $result['id']])['results'];
            }
            unset($result);
        }

        if (!property_exists($context, 'pagination')) {
            $context->pagination = [];
        }
        $context->pagination[$info->path[0]] = [
            'start' => $results['start'],
            'limit' => $results['limit'],
            'total_count' => $results['total'],
        ];

        return $results['results'];
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public static function graphQLResolveFormSections(mixed $source, array $args, stdClass $context, ResolveInfo $info): mixed
    {
        if (!empty($source) && isset($source[$info->fieldName])) {
            return $source[$info->fieldName];
        }

        if ($info->fieldName === 'questions') {
            return self::getFormQuestionsResults($context->api_version, ['filter' => 'section.id==' . $source['id']])['results'];
        }

        return null;
    }

    /**
     * @param string $api_version
     * @param array<string, mixed> $params
     * @phpstan-return array{results: list<array<string, mixed>>, start: int, limit: int, total: int}
     * @throws APIException
     * @throws RSQLException
     */
    private static function getFormResults(string $api_version, array $params): array
    {
        $schema = self::getKnownSchemas($api_version)['Form'];
        $schema = ResourceAccessor::applyFieldReadRestrictions($schema);
        return Search::getSearchResultsBySchema($schema, $params);
    }

    /**
     * @param string $api_version
     * @param array<string, mixed> $params
     * @phpstan-return array{results: list<array<string, mixed>>, start: int, limit: int, total: int}
     * @throws APIException
     * @throws RSQLException
     */
    private static function getFormSectionsResults(string $api_version, array $params): array
    {
        $schema = self::getKnownSchemas($api_version)['FormSection'];
        $schema = ResourceAccessor::applyFieldReadRestrictions($schema);
        return Search::getSearchResultsBySchema($schema, $params);
    }

    /**
     * @param string $api_version
     * @param array<string, mixed> $params
     * @phpstan-return array{results: list<array<string, mixed>>, start: int, limit: int, total: int}
     * @throws APIException
     * @throws RSQLException
     */
    private static function getFormQuestionsResults(string $api_version, array $params): array
    {
        $schema = self::getKnownSchemas($api_version)['FormQuestion'];
        $schema = ResourceAccessor::applyFieldReadRestrictions($schema);
        return Search::getSearchResultsBySchema($schema, $params);
    }

    /**
     * Slimmer copy of ResourceAccessor::searchBySchema without the rights check.
     * Fully reliant on SQL restrictions.
     * @param Request $request
     * @param callable(string, array<string, mixed>): array{results: list<array<string, mixed>>, start: int, limit: int, total: int} $fn_get_results
     * @return Response
     */
    private function unsafeSearch(Request $request, callable $fn_get_results)
    {
        try {
            $results = $fn_get_results($this->getAPIVersion($request), $request->getParameters());
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getMessage(), $e->getDetails()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage(), $e->getDetails()), $e->getCode() ?: 400);
        } catch (Throwable $e) {
            $message = (new APIException())->getUserMessage();
            $detail = null;
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $detail = $e->getMessage();
            }
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
        }
        $has_more = $results['start'] + $results['limit'] < $results['total'];
        $end = max(0, ($results['start'] + $results['limit'] - 1));
        if ($end > $results['total']) {
            $end = $results['total'] - 1;
        }
        return new JSONResponse($results['results'], $has_more ? 206 : 200, [
            'Content-Range' => $results['start'] . '-' . $end . '/' . $results['total'],
        ]);
    }

    /**
     * Slimmer copy of ResourceAccessor::getOneBySchema without the rights check.
     * Fully reliant on SQL restrictions.
     * @param Request $request
     * @param callable(string, array<string, mixed>): array{results: list<array<string, mixed>>, start: int, limit: int, total: int} $fn_get_results
     * @return Response
     */
    private function unsafeGetOneResult(Request $request, callable $fn_get_results): Response
    {
        $request->setParameter('limit', 1);
        $request->setParameter('start', 0);
        try {
            $results = $fn_get_results($this->getAPIVersion($request), $request->getParameters());
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getMessage(), $e->getDetails()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage(), $e->getDetails()), $e->getCode() ?: 400);
        } catch (Throwable $e) {
            $message = (new APIException())->getUserMessage();
            $detail = null;
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $detail = $e->getMessage();
            }
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
        }
        if (count($results['results']) === 0) {
            return AbstractController::getNotFoundErrorResponse();
        }
        return new JSONResponse($results['results'][0]);
    }

    /**
     * @param string $api_version The api version to use.
     * @param int $form_id The form ID to check.
     * @return bool
     * @throws APIException
     * @throws RSQLException
     */
    private function canReadForm(string $api_version, int $form_id): bool
    {
        $forms = self::getFormResults($api_version, ['filter' => 'id==' . $form_id, 'limit' => 1]);
        return $forms['results'] !== [];
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\SearchRoute(schema_name: 'Form')]
    public function searchForms(Request $request): Response
    {
        return $this->unsafeSearch($request, self::getFormResults(...));
    }

    #[Route(path: '/{id}', methods: ['GET'], requirements: [
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\GetRoute(schema_name: 'Form')]
    public function getForm(Request $request): Response
    {
        $filter = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filter .= ';id==' . $request->getAttribute('id');
        $request->setParameter('filter', $filter);

        return $this->unsafeGetOneResult($request, self::getFormResults(...));
    }

    #[Route(path: '/', methods: ['POST'])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\CreateRoute(schema_name: 'Form')]
    public function createForm(Request $request): Response
    {
        return ResourceAccessor::createBySchema(
            schema: $this->getKnownSchema('Form', $this->getAPIVersion($request)),
            request_params: $request->getParameters(),
            get_route: [self::class, 'getForm']
        );
    }

    #[Route(path: '/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\UpdateRoute(schema_name: 'Form')]
    public function updateForm(Request $request): Response
    {
        // Updates of forms have normal GLPI permissions so we can use the normal API methods here.
        return ResourceAccessor::updateBySchema(
            schema: $this->getKnownSchema('Form', $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters()
        );
    }

    #[Route(path: '/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\DeleteRoute(schema_name: 'Form')]
    public function deleteForm(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            schema: $this->getKnownSchema('Form', $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters()
        );
    }

    #[Route(path: '/{form_id}/Section', methods: ['GET'], requirements: [
        'form_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\SearchRoute(schema_name: 'FormSection')]
    public function listFormSections(Request $request): Response
    {
        $form_id = $request->getAttribute('form_id');
        if (!$this->canReadForm($this->getAPIVersion($request), $form_id)) {
            return self::getNotFoundErrorResponse();
        }
        $filter = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filter .= ';form.id==' . $form_id;
        $request->setParameter('filter', $filter);

        return $this->unsafeSearch($request, self::getFormSectionsResults(...));
    }

    #[Route(path: '/{form_id}/Section/{id}', methods: ['GET'], requirements: [
        'form_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\GetRoute(schema_name: 'FormSection')]
    public function getFormSection(Request $request): Response
    {
        $form_id = $request->getAttribute('form_id');
        if (!$this->canReadForm($this->getAPIVersion($request), $form_id)) {
            return self::getNotFoundErrorResponse();
        }
        $filter = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filter .= ';form.id==' . $form_id . ';id==' . $request->getAttribute('id');
        $request->setParameter('filter', $filter);
        return $this->unsafeGetOneResult($request, self::getFormSectionsResults(...));
    }

    #[Route(path: '/{form_id}/Section', methods: ['POST'], requirements: ['form_id' => '\d+'])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\CreateRoute(schema_name: 'FormSection')]
    public function createFormSection(Request $request): Response
    {
        if (!$this->canReadForm($this->getAPIVersion($request), $request->getAttribute('form_id'))) {
            return self::getNotFoundErrorResponse();
        }

        $request->setParameter('form', $request->getAttribute('form_id'));
        return ResourceAccessor::createBySchema(
            schema: $this->getKnownSchema('FormSection', $this->getAPIVersion($request)),
            request_params: $request->getParameters(),
            get_route: [self::class, 'getFormSection'],
            extra_get_route_params: [
                'mapped' => ['form_id' => $request->getAttribute('form_id')],
            ]
        );
    }

    #[Route(path: '/{form_id}/Section/{id}', methods: ['PATCH'], requirements: [
        'form_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\UpdateRoute(schema_name: 'FormSection')]
    public function updateFormSection(Request $request): Response
    {
        if (!$this->canReadForm($this->getAPIVersion($request), $request->getAttribute('form_id'))) {
            return self::getNotFoundErrorResponse();
        }

        return ResourceAccessor::updateBySchema(
            schema: $this->getKnownSchema('FormSection', $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters()
        );
    }

    #[Route(path: '/{form_id}/Section/{id}', methods: ['DELETE'], requirements: [
        'form_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\DeleteRoute(schema_name: 'FormSection')]
    public function deleteFormSection(Request $request): Response
    {
        if (!$this->canReadForm($this->getAPIVersion($request), $request->getAttribute('form_id'))) {
            return self::getNotFoundErrorResponse();
        }

        return ResourceAccessor::deleteBySchema(
            schema: $this->getKnownSchema('FormSection', $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters()
        );
    }

    #[Route(path: '/{form_id}/Section/{section_id}/Question', methods: ['GET'], requirements: [
        'form_id' => '\d+',
        'section_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\SearchRoute(schema_name: 'FormQuestion')]
    public function listFormSectionQuestions(Request $request): Response
    {
        $form_id = $request->getAttribute('form_id');
        if (!$this->canReadForm($this->getAPIVersion($request), $form_id)) {
            return self::getNotFoundErrorResponse();
        }

        $sections = self::getFormSectionsResults($this->getAPIVersion($request), ['filter' => "form.id=={$form_id}"]);
        $section_ids = array_column($sections['results'], 'id');

        $section_id = (int) $request->getAttribute('section_id');
        if (!in_array($section_id, $section_ids, true)) {
            return self::getNotFoundErrorResponse();
        }

        $filter = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filter .= ';section.id==' . $section_id;
        $request->setParameter('filter', $filter);

        return $this->unsafeSearch($request, self::getFormQuestionsResults(...));
    }

    #[Route(path: '/{form_id}/Section/{section_id}/Question/{id}', methods: ['GET'], requirements: [
        'form_id' => '\d+',
        'section_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\GetRoute(schema_name: 'FormQuestion')]
    public function getFormSectionQuestion(Request $request): Response
    {
        $form_id = $request->getAttribute('form_id');
        if (!$this->canReadForm($this->getAPIVersion($request), $form_id)) {
            return self::getNotFoundErrorResponse();
        }

        $sections = self::getFormSectionsResults($this->getAPIVersion($request), ['filter' => "form.id=={$form_id}"]);
        $section_ids = array_column($sections['results'], 'id');

        $section_id = (int) $request->getAttribute('section_id');
        if (!in_array($section_id, $section_ids, true)) {
            return self::getNotFoundErrorResponse();
        }

        $filter = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filter .= ';section.id==' . $section_id . ';id==' . $request->getAttribute('id');
        $request->setParameter('filter', $filter);

        return $this->unsafeGetOneResult($request, self::getFormQuestionsResults(...));
    }

    #[Route(path: '/{form_id}/Section/{section_id}/Question', methods: ['POST'], requirements: [
        'form_id' => '\d+',
        'section_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\CreateRoute(schema_name: 'FormQuestion')]
    public function createFormSectionQuestion(Request $request): Response
    {
        if (!$this->canReadForm($this->getAPIVersion($request), $request->getAttribute('form_id'))) {
            return self::getNotFoundErrorResponse();
        }
        $sections = self::getFormSectionsResults($this->getAPIVersion($request), ['filter' => "form.id=={$request->getAttribute('form_id')}"]);
        $section_ids = array_column($sections['results'], 'id');
        if (!in_array((int) $request->getAttribute('section_id'), $section_ids, true)) {
            return self::getNotFoundErrorResponse();
        }

        $request->setParameter('section', $request->getAttribute('section_id'));
        return ResourceAccessor::createBySchema(
            schema: $this->getKnownSchema('FormQuestion', $this->getAPIVersion($request)),
            request_params: $request->getParameters(),
            get_route: [self::class, 'getFormSectionQuestion'],
            extra_get_route_params: [
                'mapped' => [
                    'form_id' => $request->getAttribute('form_id'),
                    'section_id' => $request->getAttribute('section_id'),
                ],
            ],
        );
    }

    #[Route(path: '/{form_id}/Section/{section_id}/Question/{id}', methods: ['PATCH'], requirements: [
        'form_id' => '\d+',
        'section_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\UpdateRoute(schema_name: 'FormQuestion')]
    public function updateFormSectionQuestion(Request $request): Response
    {
        if (!$this->canReadForm($this->getAPIVersion($request), $request->getAttribute('form_id'))) {
            return self::getNotFoundErrorResponse();
        }
        $sections = self::getFormSectionsResults($this->getAPIVersion($request), ['filter' => "form.id=={$request->getAttribute('form_id')}"]);
        $section_ids = array_column($sections['results'], 'id');
        if (!in_array((int) $request->getAttribute('section_id'), $section_ids, true)) {
            return self::getNotFoundErrorResponse();
        }

        return ResourceAccessor::updateBySchema(
            schema: $this->getKnownSchema('FormQuestion', $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters()
        );
    }

    #[Route(path: '/{form_id}/Section/{section_id}/Question/{id}', methods: ['DELETE'], requirements: [
        'form_id' => '\d+',
        'section_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\DeleteRoute(schema_name: 'FormQuestion')]
    public function deleteFormSectionQuestion(Request $request): Response
    {
        if (!$this->canReadForm($this->getAPIVersion($request), $request->getAttribute('form_id'))) {
            return self::getNotFoundErrorResponse();
        }
        $sections = self::getFormSectionsResults($this->getAPIVersion($request), ['filter' => "form.id=={$request->getAttribute('form_id')}"]);
        $section_ids = array_column($sections['results'], 'id');
        if (!in_array((int) $request->getAttribute('section_id'), $section_ids, true)) {
            return self::getNotFoundErrorResponse();
        }

        return ResourceAccessor::deleteBySchema(
            schema: $this->getKnownSchema('FormQuestion', $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters()
        );
    }
}
