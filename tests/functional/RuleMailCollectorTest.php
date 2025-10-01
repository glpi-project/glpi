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

namespace tests\units;

use DbTestCase;
use Group;
use Group_User;
use PHPUnit\Framework\Attributes\DataProvider;
use Rule;
use RuleAction;
use RuleCriteria;
use RuleMailCollectorCollection;

class RuleMailCollectorTest extends DbTestCase
{
    public function testAssignEntityBasedOnGroup()
    {
        $entity_id      = getItemByTypeName('Entity', '_test_child_1', true);
        $normal_user_id = getItemByTypeName('User', 'normal', true);

        $this->login();

        // Delete all existing rule
        $rule     = new Rule();
        $rule->deleteByCriteria(['sub_type' => 'RuleMailCollector']);

        // Create new group
        $group = new Group();
        $group_id = $group->add($group_input = [
            'name' => 'group1',
        ]);
        $this->checkInput($group, $group_id, $group_input);

        // Create rule
        $rule     = new \RuleMailCollector();
        $rule_id = $rule->add($rule_input = [
            'name'         => 'test assign entity based on group',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleMailCollector',
        ]);
        $this->checkInput($rule, $rule_id, $rule_input);

        // Create criteria to check if requester group matches a specific group
        $criteria = new RuleCriteria();
        $criteria_id = $criteria->add($criteria_input = [
            'rules_id'  => $rule_id,
            'criteria'  => '_groups_id_requester',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $group_id,
        ]);
        $this->checkInput($criteria, $criteria_id, $criteria_input);

        // Create action to assign entity
        $action   = new RuleAction();
        $action_id = $action->add($action_input = [
            'rules_id'    => $rule_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => $entity_id,
        ]);
        $this->checkInput($action, $action_id, $action_input);

        // Check rules output: no rule should match
        $rulecollection = new RuleMailCollectorCollection();
        $output         = $rulecollection->processAllRules(
            [],
            [],
            [
                'mailcollector'       => 0,
                '_users_id_requester' => $normal_user_id,
            ]
        );

        $this->assertEquals(
            [
                '_no_rule_matches' => '1',
                '_rule_process'    => '',
            ],
            $output
        );

        // Add "normal" user to group
        $group_user = new Group_User();
        $group_user_id = $group_user->add($group_user_input = [
            'groups_id' => $group_id,
            'users_id'  => $normal_user_id,
        ]);
        $this->checkInput($group_user, $group_user_id, $group_user_input);

        // Check rules output: new rule should match
        $rulecollection = new RuleMailCollectorCollection();
        $output         = $rulecollection->processAllRules(
            [],
            [],
            [
                'mailcollector'       => 0,
                '_users_id_requester' => $normal_user_id,
            ]
        );

        $this->assertEquals(
            [
                'entities_id' => $entity_id,
                '_ruleid'     => $rule_id,
            ],
            $output
        );
    }

    public function testExternalID()
    {
        $entity_id      = getItemByTypeName('Entity', '_test_child_1', true);
        $normal_user_id = getItemByTypeName('User', 'normal', true);

        $this->login();

        // Delete all existing rule
        $rule     = new Rule();
        $rule->deleteByCriteria(['sub_type' => 'RuleMailCollector']);

        // Create new group
        $group = new Group();
        $group_id = $group->add($group_input = [
            'name' => 'group1',
        ]);
        $this->checkInput($group, $group_id, $group_input);

        // Create rule
        $rule     = new \RuleMailCollector();
        $rule_id = $rule->add($rule_input = [
            'name'         => 'test assign ExternalID based on subject',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleMailCollector',
        ]);
        $this->checkInput($rule, $rule_id, $rule_input);

        // Create criteria to check if requester group matches a specific group
        $criteria = new RuleCriteria();
        $criteria_id = $criteria->add($criteria_input = [
            'rules_id'  => $rule_id,
            'criteria'  => 'subject',
            'condition' => Rule::REGEX_MATCH,
            'pattern'   => "/\b(great)\b/",
        ]);
        $this->checkInput($criteria, $criteria_id, $criteria_input);

        // Create action to assign entity
        $action   = new RuleAction();
        $action_id = $action->add($action_input = [
            'rules_id'    => $rule_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => $entity_id,
        ]);
        $this->checkInput($action, $action_id, $action_input);

        // Create action to assign externalID
        $action   = new RuleAction();
        $action_id = $action->add($action_input = [
            'rules_id'    => $rule_id,
            'action_type' => 'regex_result',
            'field'       => 'externalid',
            'value'       => "#0",
        ]);
        $this->checkInput($action, $action_id, $action_input);



        $rule_options['ticket']              = [];
        $rule_options['headers']             = ['subject' => 'a great subject'];
        $rule_options['mailcollector']       = 0;
        $rule_options['_users_id_requester'] = $normal_user_id;

        $rulecollection                      = new RuleMailCollectorCollection();
        $output                              = $rulecollection->processAllRules(
            [],
            [],
            $rule_options
        );

        $this->assertEquals(
            [
                'entities_id' => $entity_id,
                '_ruleid'     => $rule_id,
                'externalid' => 'great',
            ],
            $output
        );
    }

    public static function headerCriteriasProvider()
    {
        return [
            [
                'headers'
                => [
                    'auto-submitted' => 'auto-replied',
                ],
                'rule_param' => [
                    'match'     => 'AND',
                    'criterias' => [
                        [
                            'criteria'  => 'auto-submitted',
                            'condition' => Rule::REGEX_MATCH,
                            'pattern'   => '/^(?!.*no).+$/i',
                        ],
                    ],
                ],
                'result_match' => true,
            ],
            [
                'headers'
                => [
                    'auto-submitted' => 'no',
                ],
                'rule_param' => [
                    'match'     => 'AND',
                    'criterias' => [
                        [
                            'criteria'  => 'auto-submitted',
                            'condition' => Rule::REGEX_MATCH,
                            'pattern'   => '/^(?!.*no).+$/i',
                        ],
                    ],
                ],
                'result_match' => false,
            ],
            [
                'headers'
                => [
                    'auto-submitted'                                       => 'auto-replied',
                    'x-ms-exchange-organization-expirationstarttimereason' => 'QuarantineReleased',
                ],
                'rule_param' => [
                    'match'     => 'AND',
                    'criterias' => [
                        [
                            'criteria'  => 'auto-submitted',
                            'condition' => Rule::REGEX_MATCH,
                            'pattern'   => '/^(?!.*no).+$/i',
                        ],
                        [
                            'criteria'  => '_headers',
                            'condition' => Rule::PATTERN_NOT_CONTAIN,
                            'pattern'   => 'X-MS-Exchange-Organization-ExpirationStartTimeReason: QuarantineReleased',
                        ],
                    ],
                ],
                'result_match' => false,
            ],
            [
                'headers'
                => [
                    'auto-submitted' => 'auto-replied',
                ],
                'rule_param' => [
                    'match'     => 'AND',
                    'criterias' => [
                        [
                            'criteria'  => 'auto-submitted',
                            'condition' => Rule::REGEX_MATCH,
                            'pattern'   => '/^(?!.*no).+$/i',
                        ],
                        [
                            'criteria'  => '_headers',
                            'condition' => Rule::PATTERN_NOT_CONTAIN,
                            'pattern'   => 'X-MS-Exchange-Organization-ExpirationStartTimeReason: QuarantineReleased',
                        ],
                    ],
                ],
                'result_match' => true,
            ],
        ];
    }

    #[DataProvider('headerCriteriasProvider')]
    public function testHeaderCriterias(
        array $headers,
        array $rule_param,
        bool $result_match
    ) {
        $this->login();

        // Delete all existing rule
        $rule     = new Rule();
        $rule->deleteByCriteria(['sub_type' => 'RuleMailCollector']);

        // Create rule
        $rule     = new \RuleMailCollector();
        $rule_id = $rule->add($rule_input = [
            'name'         => __FUNCTION__,
            'match'        => $rule_param['match'],
            'is_active'    => 1,
            'sub_type'     => 'RuleMailCollector',
        ]);
        $this->checkInput($rule, $rule_id, $rule_input);

        // Add criterias
        foreach ($rule_param['criterias'] as $criteria_input) {
            $criteria = new RuleCriteria();
            $criteria_input['rules_id'] = $rule_id;
            $criteria_id = $criteria->add($criteria_input);
            $this->checkInput($criteria, $criteria_id, $criteria_input);
        }

        // Create action
        $action   = new RuleAction();
        $action_id = $action->add($action_input = [
            'rules_id'    => $rule_id,
            'action_type' => 'assign',
            'field'       => '_refuse_email_no_response',
            'value'       => 1,
        ]);
        $this->checkInput($action, $action_id, $action_input);

        // Check rules output: no rule should match
        $rulecollection = new RuleMailCollectorCollection();
        $output         = $rulecollection->processAllRules(
            [],
            [],
            ['headers' => $headers]
        );

        if ($result_match) {
            $this->assertEquals(
                [
                    '_refuse_email_no_response' => 1,
                    '_ruleid'     => $rule_id,
                ],
                $output
            );
        } else {
            $this->assertEquals(
                [
                    '_no_rule_matches' => '1',
                    '_rule_process'    => '',
                ],
                $output
            );
        }
    }
}
