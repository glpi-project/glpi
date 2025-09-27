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

use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use JsonException;
use LogicException;
use Rule;
use RuleAction;
use RuleCollection;
use RuleCriteria;
use Session;

#[Route(path: '/Rule', requirements: [
    'collection' => [self::class, 'getRuleCollections'],
    'rule_id' => '\d+',
    'id' => '\d+',
], tags: ['Rule'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'collection',
            schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
            description: 'Rule Collection',
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'rule_id',
            schema: new Doc\Schema(type: Doc\Schema::TYPE_INTEGER, format: Doc\Schema::FORMAT_INTEGER_INT64),
            description: 'Rule ID',
            location: Doc\Parameter::LOCATION_PATH,
        ),
    ]
)]
final class RuleController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        $schemas = [
            'RuleCriteria' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => 'RuleCriteria',
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'rule' => self::getDropdownTypeSchema(class: Rule::class, full_schema: 'Rule') + ['writeOnly' => true],
                    'criteria' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The criteria to use. See /Rule/Collection/{collection}/CriteriaCriteria for a complete list of criteria.',
                    ],
                    'condition' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The condition to use. See /Rule/Collection/{collection}/CriteriaCondition for a complete list of conditions.',
                    ],
                    'pattern' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The value/pattern to match against. If the condition relates to regular expressions, this value needs to be a valid regular expression including the delimiters.',
                    ],
                ],
            ],
            'RuleCriteriaCondition' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                // No x-itemtype because it isn't in the DB. It cannot be searched.
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    ],
                    'description' => ['type' => Doc\Schema::TYPE_STRING],
                    'fields' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'Fields/criteria that can be used with this condition. See /Rule/Collection/{collection}/CriteriaCriteria for a complete list of fields/criteria.',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                        ],
                    ],
                ],
            ],
            'RuleCriteriaCriteria' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                // No x-itemtype because it isn't in the DB. It cannot be searched.
                'properties' => [
                    'id' => ['type' => Doc\Schema::TYPE_STRING],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'RuleActionType' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                // No x-itemtype because it isn't in the DB. It cannot be searched.
                'properties' => [
                    'id' => ['type' => Doc\Schema::TYPE_STRING],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'fields' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'Fields/actions that can be used with this action. See /Rule/Collection/{collection}/ActionField for a complete list of fields/actions.',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                        ],
                    ],
                ],
            ],
            'RuleActionField' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                // No x-itemtype because it isn't in the DB. It cannot be searched.
                'properties' => [
                    'id' => ['type' => Doc\Schema::TYPE_STRING],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'action_types' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'Action types that can be used with this field. See /Rule/Collection/{collection}/ActionType for a complete list of action types.',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                        ],
                    ],
                ],
            ],
            'RuleAction' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => 'RuleAction',
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'rule' => self::getDropdownTypeSchema(class: Rule::class, full_schema: 'Rule') + ['writeOnly' => true],
                    'action_type' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The action to perform. See /Rule/Collection/{collection}/ActionType for a complete list of actions.',
                    ],
                    'field' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The field to modify. See /Rule/Collection/{collection}/ActionField for a complete list of fields.',
                    ],
                    'value' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The value to set. If the field relates to regular expressions, this can include a # followed by 0 through 9 to indicate a captured value from the criteria regular expression.',
                    ],
                ],
            ],
        ];
        $schemas['Rule'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => 'Rule',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'uuid' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'sub_type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'writeOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'description' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'match' => [
                    'description' => 'Logical operator to use when matching rule criteria',
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [
                        'AND',
                        'OR',
                    ],
                ],
                'condition' => [
                    'description' => 'The condition that triggers evaluation of this rule. Typically, 1 is for "On Add" and 2 is for "On Update".',
                    'type' => Doc\Schema::TYPE_INTEGER,
                ],
                'ranking' => [
                    'description' => 'The order in which to evaluate this rule. Lower numbers are evaluated first. Changing the ranking of a rule may shift the rankings of other rules.',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                ],
                'criteria' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'readOnly' => true,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'RuleCriteria',
                        'x-join' => [
                            'table' => 'glpi_rulecriterias',
                            'fkey' => 'id',
                            'field' => 'rules_id',
                            'primary-property' => 'id',
                        ],
                        'properties' => array_filter($schemas['RuleCriteria']['properties'], static fn($k) => $k !== 'rule', ARRAY_FILTER_USE_KEY),
                    ],
                ],
                'actions' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'readOnly' => true,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'RuleAction',
                        'x-join' => [
                            'table' => 'glpi_ruleactions',
                            'fkey' => 'id',
                            'field' => 'rules_id',
                            'primary-property' => 'id',
                        ],
                        'properties' => array_filter($schemas['RuleAction']['properties'], static fn($k) => $k !== 'rule', ARRAY_FILTER_USE_KEY),
                    ],
                ],
                'date_creation' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_mod' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
            ],
        ];
        return $schemas;
    }

    public static function getRuleCollections(): array
    {
        global $CFG_GLPI;

        /** @var class-string<RuleCollection>[] $collections */
        $collections = $CFG_GLPI['rulecollections_types'];
        $visible_collections = [];
        foreach ($collections as $collection) {
            if (!\is_a($collection, RuleCollection::class, true)) {
                continue; // Ignore invalid classes
            }

            /** @var RuleCollection $instance */
            $instance = new $collection();
            if ($instance->canList()) {
                $rule_class = $instance::getRuleClassName();
                if (str_starts_with($rule_class, 'Rule')) {
                    // Only handle rules from the core in the global namespace here
                    $visible_collections[] = substr($rule_class, 4);
                }
            }
        }

        return $visible_collections;
    }

    private function checkCollectionAccess(Request $request, int $right): ?Response
    {
        $rule_subtype = 'Rule' . $request->getAttribute('collection');
        if (!class_exists($rule_subtype)) {
            return self::getNotFoundErrorResponse();
        }
        if (!Session::haveRight($rule_subtype::$rightname, $right)) {
            return self::getAccessDeniedErrorResponse();
        }
        return null;
    }

    private function isChildOfRule(string $schema, Request $request): bool
    {
        $params = $request->getParameters();
        // Only allow updating if the criterion exists in the rule
        $result = ResourceAccessor::getOneBySchema($this->getKnownSchema($schema, $this->getAPIVersion($request)), $request->getAttributes(), $params);
        try {
            $decoded = json_decode((string) $result->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return isset($decoded['rule']['id']) && $decoded['rule']['id'] === (int) $request->getAttribute('rule_id');
        } catch (JsonException $e) {
            return false;
        }
    }

    #[Route(path: '/Collection', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List all rule collections',
        responses: [
            new Doc\Response(
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_ARRAY,
                    items: new Doc\Schema(
                        type: Doc\Schema::TYPE_OBJECT,
                        properties: [
                            'name' => new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                            'rule_type' => new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                        ]
                    )
                ),
                description: 'List of rule collections'
            ),
        ]
    )]
    public function getCollections(Request $request): Response
    {
        global $CFG_GLPI;

        /** @var class-string<RuleCollection>[] $collections */
        $collections = $CFG_GLPI['rulecollections_types'];
        $visible_collections = [];
        foreach ($collections as $collection) {
            if (!\is_a($collection, RuleCollection::class, true)) {
                continue; // Ignore invalid classes
            }

            /** @var RuleCollection $instance */
            $instance = new $collection();
            if ($instance->canList()) {
                $rule_class = $instance::getRuleClassName();
                if (str_starts_with($rule_class, 'Rule')) {
                    // Only handle rules from the core in the global namespace here
                    $visible_collections[] = [
                        'name' => $instance->getTitle(),
                        'rule_type' => substr($rule_class, 4),
                    ];
                }
            }
        }
        return new JSONResponse($visible_collections);
    }

    #[Route(path: '/Collection/{collection}/CriteriaCondition', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List accepted "condition" values for criteria for rules in a collection',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('RuleCriteriaCondition[]')),
        ]
    )]
    public function getRuleCriteriaConditions(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }
        $rule = $this->getRuleInstanceFromRequest($request);
        $possible_criteria = $rule->getCriterias();
        $conditions = [];
        foreach ($possible_criteria as $k => $v) {
            $to_add = RuleCriteria::getConditions($rule::class, $k);
            foreach ($to_add as $i => &$j) {
                $j = [
                    'id' => $i,
                    'description' => $j,
                    'fields' => [...$conditions[$i]['fields'] ?? [], $k],
                ];
            }
            unset($j);
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $conditions = array_replace($conditions, $to_add);
        }
        ksort($conditions);
        return new JSONResponse(array_values($conditions));
    }

    #[Route(path: '/Collection/{collection}/CriteriaCriteria', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List accepted "criteria" values for criteria for rules in a collection',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('RuleCriteriaCriteria[]')),
        ]
    )]
    public function getRuleCriteriaCriteria(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }
        $rule = $this->getRuleInstanceFromRequest($request);
        $possible_criteria = $rule->getCriterias();
        $result = [];
        foreach ($possible_criteria as $k => $v) {
            $result[] = [
                'id' => $k,
                'name' => $v['name'],
            ];
        }
        return new JSONResponse($result);
    }

    #[Route(path: '/Collection/{collection}/ActionType', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List accepted "action_type" values for actions for rules in a collection',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('RuleActionType[]')),
        ]
    )]
    public function getRuleActionType(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }
        $rule = $this->getRuleInstanceFromRequest($request);
        $fields = $rule->getActions();
        $types = RuleAction::getActions();
        $result = [];
        foreach ($fields as $fk => $fv) {
            foreach ($types as $k => $v) {
                if (in_array($k, $fv['force_actions'] ?? ['assign'], true)) {
                    $result[$k] = [
                        'id' => $k,
                        'name' => $v,
                        'fields' => [...$result[$k]['fields'] ?? [], $fk],
                    ];
                }
            }
        }
        $result = array_values($result);

        return new JSONResponse($result);
    }

    #[Route(path: '/Collection/{collection}/ActionField', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List accepted "field" values for actions for rules in a collection',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('RuleActionField[]')),
        ]
    )]
    public function getRuleActionField(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }
        $rule = $this->getRuleInstanceFromRequest($request);
        $possible_actions = $rule->getActions();
        $result = [];
        foreach ($possible_actions as $k => $v) {
            $result[] = [
                'id' => $k,
                'name' => $v['name'],
                'action_types' => $v['force_actions'] ?? ['assign'],
            ];
        }
        return new JSONResponse($result);
    }

    #[Route(path: '/Collection/{collection}/Rule', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: 'Rule',
        description: 'List or search rules in a collection'
    )]
    public function getRules(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }
        $params = $request->getParameters();
        $filter = $params['filter'] ?? '';
        $filter .= ';sub_type==Rule' . $request->getAttribute('collection');
        $params['filter'] = $filter;

        return ResourceAccessor::searchBySchema($this->getKnownSchema('Rule', $this->getAPIVersion($request)), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'Rule',
        description: 'Get a rule from a collection'
    )]
    public function getRule(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }
        $params = $request->getParameters();
        $filter = $params['filter'] ?? '';
        $filter .= ';sub_type==Rule' . $request->getAttribute('collection');
        $params['filter'] = $filter;

        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Rule', $this->getAPIVersion($request)), $request->getAttributes(), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{id}/Criteria', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get criteria for a rule from a collection',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('RuleCriteria[]')),
        ]
    )]
    public function getRuleCriteria(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }

        $params = $request->getParameters();
        $filter = $params['filter'] ?? '';
        $filter .= ';rule==' . $request->getAttribute('id');
        $params['filter'] = $filter;

        return ResourceAccessor::searchBySchema($this->getKnownSchema('RuleCriteria', $this->getAPIVersion($request)), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Criteria/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'RuleCriteria',
        description: 'Get a specific criterion for a rule from a collection'
    )]
    public function getRuleCriterion(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }

        $params = $request->getParameters();
        $filter = $params['filter'] ?? '';
        $filter .= ';rule==' . $request->getAttribute('rule_id');
        $params['filter'] = $filter;

        return ResourceAccessor::getOneBySchema($this->getKnownSchema('RuleCriteria', $this->getAPIVersion($request)), $request->getAttributes(), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{id}/Action', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get actions for a rule from a collection',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('RuleAction[]')),
        ]
    )]
    public function getRuleActions(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }

        $params = $request->getParameters();
        $filter = $params['filter'] ?? '';
        $filter .= ';rule==' . $request->getAttribute('rule_id');
        $params['filter'] = $filter;

        return ResourceAccessor::searchBySchema($this->getKnownSchema('RuleAction', $this->getAPIVersion($request)), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Action/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'RuleAction',
        description: 'Get a specific action for a rule from a collection'
    )]
    public function getRuleAction(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, READ)) {
            return $response;
        }

        $params = $request->getParameters();
        $filter = $params['filter'] ?? '';
        $filter .= ';rule==' . $request->getAttribute('rule_id');
        $params['filter'] = $filter;

        return ResourceAccessor::getOneBySchema($this->getKnownSchema('RuleAction', $this->getAPIVersion($request)), $request->getAttributes(), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'Rule',
        description: 'Create a rule in a collection'
    )]
    public function createRule(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, CREATE)) {
            return $response;
        }

        $params = $request->getParameters();
        $params['sub_type'] = 'Rule' . $request->getAttribute('collection');

        return ResourceAccessor::createBySchema($this->getKnownSchema('Rule', $this->getAPIVersion($request)), $params, [self::class, 'getRule'], [
            'mapped' => [
                'collection' => $request->getAttribute('collection'),
            ],
        ]);
    }

    #[Route(path: '/Collection/{collection}/Rule/{id}', methods: ['PATCH'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'Rule',
        description: 'Update a rule in a collection'
    )]
    public function updateRule(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, UPDATE)) {
            return $response;
        }

        $params = $request->getParameters();
        $params['sub_type'] = 'Rule' . $request->getAttribute('collection');

        return ResourceAccessor::updateBySchema($this->getKnownSchema('Rule', $this->getAPIVersion($request)), $request->getAttributes(), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'Rule',
        description: 'Delete a rule in a collection'
    )]
    public function deleteRule(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, PURGE)) {
            return $response;
        }
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Rule', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Criteria', methods: ['POST'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'RuleCriteria',
        description: 'Create a criterion for a rule in a collection'
    )]
    public function createRuleCriteria(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, UPDATE)) {
            return $response;
        }

        $params = $request->getParameters();
        $params['rule'] = $request->getAttribute('rule_id');

        return ResourceAccessor::createBySchema($this->getKnownSchema('RuleCriteria', $this->getAPIVersion($request)), $params, [self::class, 'getRuleCriterion'], [
            'mapped' => [
                'rule_id' => $request->getAttribute('rule_id'),
                'collection' => $request->getAttribute('collection'),
            ],
        ]);
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Criteria/{id}', methods: ['PATCH'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'RuleCriteria',
        description: 'Update a criterion for a rule in a collection'
    )]
    public function updateRuleCriteria(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, UPDATE)) {
            return $response;
        }
        if (!$this->isChildOfRule('RuleCriteria', $request)) {
            return self::getNotFoundErrorResponse();
        }

        // Cannot move criteria to another rule
        $params = $request->getParameters();
        $params['id'] = $request->getAttribute('id');
        $params['rule.id'] = $request->getAttribute('rule_id');

        return ResourceAccessor::updateBySchema($this->getKnownSchema('RuleCriteria', $this->getAPIVersion($request)), $request->getAttributes(), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Criteria/{id}', methods: ['DELETE'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'RuleCriteria',
        description: 'Delete a criterion for a rule in a collection'
    )]
    public function deleteRuleCriteria(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, UPDATE)) {
            return $response;
        }
        if (!$this->isChildOfRule('RuleCriteria', $request)) {
            return self::getNotFoundErrorResponse();
        }

        return ResourceAccessor::deleteBySchema($this->getKnownSchema('RuleCriteria', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Action', methods: ['POST'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'RuleAction',
        description: 'Create an action for a rule in a collection'
    )]
    public function createRuleAction(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, CREATE)) {
            return $response;
        }

        $params = $request->getParameters();
        $params['rule'] = $request->getAttribute('rule_id');

        return ResourceAccessor::createBySchema($this->getKnownSchema('RuleAction', $this->getAPIVersion($request)), $params, [self::class, 'getRuleAction'], [
            'mapped' => [
                'rule_id' => $request->getAttribute('rule_id'),
                'collection' => $request->getAttribute('collection'),
            ],
        ]);
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Action/{id}', methods: ['PATCH'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'RuleAction',
        description: 'Update an action for a rule in a collection'
    )]
    public function updateRuleAction(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, UPDATE)) {
            return $response;
        }
        if (!$this->isChildOfRule('RuleAction', $request)) {
            return self::getNotFoundErrorResponse();
        }

        // Cannot move action to another rule
        $params = $request->getParameters();
        $params['id'] = $request->getAttribute('id');
        $params['rule.id'] = $request->getAttribute('rule_id');

        return ResourceAccessor::updateBySchema($this->getKnownSchema('RuleAction', $this->getAPIVersion($request)), $request->getAttributes(), $params);
    }

    #[Route(path: '/Collection/{collection}/Rule/{rule_id}/Action/{id}', methods: ['DELETE'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'RuleAction',
        description: 'Delete an action for a rule in a collection'
    )]
    public function deleteRuleAction(Request $request): Response
    {
        if ($response = $this->checkCollectionAccess($request, UPDATE)) {
            return $response;
        }
        if (!$this->isChildOfRule('RuleAction', $request)) {
            return self::getNotFoundErrorResponse();
        }

        return ResourceAccessor::deleteBySchema($this->getKnownSchema('RuleAction', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    private function getRuleInstanceFromRequest(Request $request): Rule
    {
        $expected_class = 'Rule' . $request->getAttribute('collection');

        if (!\is_a($expected_class, Rule::class, true)) {
            throw new LogicException();
        }

        return new $expected_class();
    }
}
