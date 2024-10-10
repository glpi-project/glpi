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

namespace tests\units;

use DbTestCase;
use ReflectionClass;

/* Test for inc/rule.class.php */

class RuleTest extends DbTestCase
{
    public function testGetTable()
    {
        $table = \Rule::getTable('RuleDictionnarySoftware');
        $this->assertSame('glpi_rules', $table);

        $table = \Rule::getTable('RuleTicket');
        $this->assertSame('glpi_rules', $table);
    }

    public function testGetTypeName()
    {
        $this->assertSame('Rule', \Rule::getTypeName(1));
        $this->assertSame('Rules', \Rule::getTypeName(\Session::getPluralNumber()));
    }

    public function testGetRuleObjectByID()
    {
        $rule = new \Rule();
        $rules_id = $rule->add([
            'name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, (int)$rules_id);

        $obj = \Rule::getRuleObjectByID($rules_id);
        $this->assertInstanceOf(\RuleDictionnarySoftware::class, $obj);

        $this->assertNull(\Rule::getRuleObjectByID(100));
    }

    public function testGetConditionsArray()
    {
        $this->assertEmpty(\Rule::getConditionsArray());

        $conditions = \RuleTicket::getConditionsArray();
        $this->assertEquals(
            [
                1 => "Add",
                2 => "Update",
                3 => "Add / Update"
            ],
            $conditions
        );
    }

    public function testUseConditions()
    {
        $rule = new \Rule();
        $this->assertFalse($rule->useConditions());

        $ruleticket = new \RuleTicket();
        $this->assertTrue($ruleticket->useConditions());
    }

    public function testGetConditionName()
    {
        $this->assertSame(NOT_AVAILABLE, \Rule::getConditionName(-1));
        $this->assertSame(NOT_AVAILABLE, \Rule::getConditionName(110));
        $this->assertSame('Add', \RuleTicket::getConditionName(1));
        $this->assertSame('Update', \RuleTicket::getConditionName(2));
        $this->assertSame('Add / Update', \RuleTicket::getConditionName(3));
    }

    public function testGetRuleActionClass()
    {
        $rule = new \Rule();
        $this->assertSame('RuleAction', $rule->getRuleActionClass());

        $rule = new \RuleTicket();
        $this->assertSame('RuleAction', $rule->getRuleActionClass());
    }

    public function testGetRuleCriteriaClass()
    {
        $rule = new \Rule();
        $this->assertSame('RuleCriteria', $rule->getRuleCriteriaClass());

        $rule = new \RuleTicket();
        $this->assertSame('RuleCriteria', $rule->getRuleCriteriaClass());
    }

    public function testGetRuleIdField()
    {
        $rule = new \Rule();
        $this->assertSame('rules_id', $rule->getRuleIdField());

        $rule = new \RuleDictionnaryPrinter();
        $this->assertSame('rules_id', $rule->getRuleIdField());
    }

    public function testIsEntityAssign()
    {
        $rule = new \Rule();
        $this->assertFalse($rule->isEntityAssign());

        $rule = new \RuleTicket();
        $this->assertTrue($rule->isEntityAssign());
    }

    public function testPost_getEmpty()
    {
        $rule = new \Rule();
        $rule->getEmpty();
        $this->assertEquals(0, $rule->fields['is_active']);
    }

    public function testGetTitle()
    {
        $rule = new \Rule();
        $this->assertSame(__('Rules management'), $rule->getTitle());

        $rule = new \RuleTicket();
        $this->assertSame(__('Business rules for tickets'), $rule->getTitle());
    }

    public function testGetCollectionClassName()
    {
        $rule = new \Rule();
        $this->assertSame('RuleCollection', $rule->getCollectionClassName());

        $rule = new \RuleTicket();
        $this->assertSame('RuleTicketCollection', $rule->getCollectionClassName());
    }

    public function testGetSpecificMassiveActions()
    {
        $rule    = new \Rule();
        $actions = $rule->getSpecificMassiveActions();
        $this->assertSame(
            [
                'Rule:export'     => '<i class=\'fas fa-file-download\'></i>Export'
            ],
            $actions
        );

        $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = ALLSTANDARDRIGHT;
        $rule    = new \RuleDictionnarySoftware();
        $actions = $rule->getSpecificMassiveActions();
        $this->assertSame(
            [
                'Rule:move_rule' => '<i class=\'fas fa-arrows-alt-v\'></i>Move',
                'Rule:export'     => '<i class=\'fas fa-file-download\'></i>Export'
            ],
            $actions
        );

        $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = READ;
        $rule    = new \RuleDictionnarySoftware();
        $actions = $rule->getSpecificMassiveActions();
        $this->assertSame(
            [
                'Rule:export'     => '<i class=\'fas fa-file-download\'></i>Export'
            ],
            $actions
        );
    }

    public function testGetSearchOptionsNew()
    {
        $rule = new \Rule();
        $this->assertCount(12, $rule->rawSearchOptions());
    }

    public function testGetRuleWithCriteriasAndActions()
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::OR_MATCHING,
            'condition'   => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, (int)$rules_id);

        $this->assertGreaterThan(
            0,
            (int)$criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'Mozilla Firefox 52'
            ])
        );

        $this->assertGreaterThan(
            0,
            (int)$criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'Mozilla Firefox 53'
            ])
        );

        $this->assertGreaterThan(
            0,
            (int)$action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => '1'
            ])
        );

        $this->assertTrue($rule->getRuleWithCriteriasAndActions($rules_id));
        $this->assertEmpty($rule->criterias);
        $this->assertEmpty($rule->actions);

        $this->assertTrue($rule->getRuleWithCriteriasAndActions($rules_id, 1, 1));
        $this->assertCount(2, $rule->criterias);
        $this->assertCount(1, $rule->actions);

        $this->assertFalse($rule->getRuleWithCriteriasAndActions(100));
    }

    public function testMaxActionsCount()
    {
        $rule = new \Rule();
        $this->assertSame(1, $rule->maxActionsCount());

        $rule = new \RuleTicket();
        $this->assertSame(40, $rule->maxActionsCount());

        $rule = new \RuleDictionnarySoftware();
        $this->assertSame(7, $rule->maxActionsCount());
    }

    public function testMaybeRecursive()
    {
        $rule = new \Rule();
        $this->assertFalse($rule->maybeRecursive());

        $rule = new \RuleTicket();
        $this->assertTrue($rule->maybeRecursive());
    }

    public function testGetCriteria()
    {
        $ruleTicket = new \RuleTicket();
        $criteria   = $ruleTicket->getCriteria('locations_id');
        $this->assertSame('glpi_locations', $criteria['table']);
        $this->assertEmpty($ruleTicket->getCriteria('location'));
    }

    public function testGetAction()
    {
        $ruleTicket = new \RuleTicket();
        $action     = $ruleTicket->getAction('locations_id');
        $this->assertSame('glpi_locations', $action['table']);
        $this->assertEmpty($ruleTicket->getAction('location'));
    }

    public function testGetCriteriaName()
    {
        $ruleTicket = new \RuleTicket();
        $this->assertSame('Ticket location', $ruleTicket->getCriteriaName('locations_id'));
        $this->assertSame(__('Unavailable'), $ruleTicket->getCriteriaName('location'));
    }

    public static function actionsNamesProvider()
    {
        return [
            [\Location::getTypeName(1)               , 'locations_id'],
            ["&nbsp;"                     , 'location'],
            [_n('Type', 'Types', 1)                   , 'type'],
            [_n('Category', 'Categories', 1)               , 'itilcategories_id'],
            [_n('Requester', 'Requesters', 1)              , '_users_id_requester'],
            [_n('Requester group', 'Requester groups', 1)        , '_groups_id_requester'],
            [__('Technician')             , '_users_id_assign'],
            [__('Technician group')       , '_groups_id_assign'],
            [__('Assigned to a supplier') , '_suppliers_id_assign'],
            [_n('Watcher', 'Watchers', 1)                , '_users_id_observer'],
            [_n('Watcher group', 'Watcher groups', 1)          , '_groups_id_observer'],
            [__('Urgency')                , 'urgency'],
            [__('Impact')                 , 'impact'],
            [__('Priority')               , 'priority'],
            [__('Status')                 , 'status'],
            [_n(
                'Associated element',
                'Associated elements',
                2
            )  , 'affectobject'
            ],
            [sprintf(
                __('%1$s %2$s'),
                __('SLA'),
                __('Time to resolve')
            ) , 'slas_id_ttr'
            ],
            [sprintf(
                __('%1$s %2$s'),
                __('SLA'),
                __('Time to own')
            )     , 'slas_id_tto'
            ],
            [sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                \User::getTypeName(1)
            )            , 'users_id_validate'
            ],
            [sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                \Group::getTypeName(1)
            )           , 'groups_id_validate'
            ],
            [sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                __('Minimum validation required')
            ) , 'validation_percent'
            ],
            [__('Approval request to requester group manager') , 'users_id_validate_requester_supervisor'],
            [__('Approval request to technician group manager') , 'users_id_validate_assign_supervisor'],
            [\RequestType::getTypeName(1), 'requesttypes_id']
        ];
    }

    /**
     * @dataProvider actionsNamesProvider
     */
    public function testGetActionName($label, $field)
    {
        $ruleTicket = new \RuleTicket();
        $this->assertSame($label, $ruleTicket->getActionName($field));
    }

    public function testPrepareInputDataForProcess()
    {
        $rule = new \Rule();
        $input = ['name' => 'name', 'test' => 'test'];
        $result = $rule->prepareInputDataForProcess($input, ['test2' => 'test2']);
        $this->assertSame($input, $result);
    }

    public function testClone()
    {
        $rule = getItemByTypeName('Rule', 'One user assignation');
        $rules_id = $rule->fields['id'];

        $this->assertSame(1, $rule->fields['is_active']);
        $this->assertSame('One user assignation', $rule->fields['name']);

        $relations = [
            \RuleAction::class => 1,
            \RuleCriteria::class  => 3
        ];

        foreach ($relations as $relation => $expected) {
            $this->assertSame(
                $expected,
                countElementsInTable(
                    $relation::getTable(),
                    ['rules_id' => $rules_id]
                )
            );
        }

        $cloned = $rule->clone();
        $this->assertGreaterThan($rules_id, $cloned);
        $this->assertTrue($rule->getFromDB($cloned));

        $this->assertSame(0, $rule->fields['is_active']);
        $this->assertSame('One user assignation (copy)', $rule->fields['name']);

        foreach ($relations as $relation => $expected) {
            $this->assertSame(
                $expected,
                countElementsInTable(
                    $relation::getTable(),
                    ['rules_id' => $cloned]
                )
            );
        }

        //rename rule with a quote, then clone
        $rules_id = $cloned;
        $rule = new \RuleAsset(); //needed to reset last_clone_index...
        $this->assertTrue($rule->update(['id' => $rules_id, 'name' => addslashes("User's assigned")]));
        $this->assertTrue($rule->getFromDB($rules_id));

        $cloned = $rule->clone();
        $this->assertGreaterThan($rules_id, $cloned);
        $this->assertTrue($rule->getFromDB($cloned));

        $this->assertSame(0, $rule->fields['is_active']);
        $this->assertSame("User's assigned (copy)", $rule->fields['name']);

        foreach ($relations as $relation => $expected) {
            $this->assertSame(
                $expected,
                countElementsInTable(
                    $relation::getTable(),
                    ['rules_id' => $cloned]
                )
            );
        }
    }

    public function testCleanDBonPurge()
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::OR_MATCHING,
            'condition'   => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, (int)$rules_id);

        $criterion_1 = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52'
        ]);
        $this->assertGreaterThan(0, (int)$criterion_1);

        $criterion_2 = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 53'
        ]);
        $this->assertGreaterThan(0, (int)$criterion_2);

        $action_1 = $action->add(['rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => '_ignore_import',
            'value'       => '1'
        ]);
        $this->assertGreaterThan(0, (int)$action_1);

        $this->assertTrue($rule->getFromDB($rules_id));
        $rule->cleanDBonPurge();
        $this->assertFalse($criteria->getFromDB($criterion_1));
        $this->assertFalse($criteria->getFromDB($criterion_2));
        $this->assertFalse($action->getFromDB($action_1));
    }

    public function testPrepareInputForAdd()
    {
        $rule     = new \RuleRight();
        //Add a new rule
        $rules_id = $rule->add(['name' => 'MyRule', 'is_active' => 1]);
        $this->assertGreaterThan(0, (int)$rules_id);
        $this->assertTrue($rule->getFromDB($rules_id));
        //Check that an uuid has been generated
        $this->assertNotEmpty($rule->fields['uuid']);
        //Check that a ranking has been added
        $this->assertGreaterThan(0, $rule->fields['ranking']);

        //Add a rule and provide an uuid
        $rules_id = $rule->add(['name' => 'MyRule', 'uuid' => '12345']);
        $this->assertGreaterThan(0, (int)$rules_id);
        $this->assertTrue($rule->getFromDB($rules_id));
        //Check that the uuid has been added as it is, and has not been overriden
        $this->assertSame('12345', $rule->fields['uuid']);
    }

    public function testGetMinimalCriteriaText()
    {
        $rule     = new \RuleTicket();
        $location = getItemByTypeName('Location', "_location01");

        //Testing condition CONTAIN
        $input    = ['criteria'  => 'location',
            'condition'   => \Rule::PATTERN_CONTAIN,
            'pattern' => '_loc'
        ];
        //The criterion doesn't exist
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Unavailable</td><td >contains</td><td >_loc</td>";
        $this->assertSame($expected, $result);

        $input['criteria'] = '_locations_id_of_requester';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >contains</td><td >_loc</td>";
        $this->assertSame($expected, $result);

        //Testing condition IS
        $input['condition'] = \Rule::PATTERN_IS;
        $input['pattern']   = $location->getID();
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >is</td><td >_location01 (Root entity)</td>";
        $this->assertSame($expected, $result);

        //Testing condition IS NOT
        $input['condition'] = \Rule::PATTERN_IS_NOT;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >is not</td><td >_location01 (Root entity)</td>";
        $this->assertSame($expected, $result);

        //Testing condition REGEX MATCH
        $input['condition'] = \Rule::REGEX_MATCH;
        $input['pattern']   = '/(loc)/';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >regular expression matches</td><td >/(loc)/</td>";
        $this->assertSame($expected, $result);

        //Testing condition REGEX DOES NOT MATCH
        $input['condition'] = \Rule::REGEX_NOT_MATCH;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >regular expression does not match</td><td >/(loc)/</td>";
        $this->assertSame($expected, $result);

        //Testing condition EXISTS
        $input['condition'] = \Rule::PATTERN_EXISTS;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >exists</td><td >Yes</td>";
        $this->assertSame($expected, $result);

        //Testing condition DOES NOT EXIST
        $input['condition'] = \Rule::PATTERN_DOES_NOT_EXISTS;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >does not exist</td><td >Yes</td>";
        $this->assertSame($expected, $result);

        //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_UNDER;
        $input['pattern']   = $location->getID();
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >under</td><td >_location01 (Root entity)</td>";
        $this->assertSame($expected, $result);

        //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_NOT_UNDER;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >not under</td><td >_location01 (Root entity)</td>";
        $this->assertSame($expected, $result);

        //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_BEGIN;
        $input['pattern']   = '_loc';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >starting with</td><td >_loc</td>";
        $this->assertSame($expected, $result);

        //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_END;
        $input['pattern']   = '_loc';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >finished by</td><td >_loc</td>";
        $this->assertSame($expected, $result);

        //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_END;
        $input['pattern']   = '_loc';
        $result   = $rule->getMinimalCriteriaText($input, 'aaaa');
        $expected = "<td aaaa>Requester location</td><td aaaa>finished by</td><td aaaa>_loc</td>";
        $this->assertSame($expected, $result);
    }

    public function testGetMinimalActionText()
    {
        $rule = new \RuleSoftwareCategory();
        $input = ['field' => 'softwarecategories_id',
            'action_type' => 'assign',
            'value' => 1
        ];
        $result = $rule->getMinimalActionText($input);
        $expected = "<td >Category</td><td >Assign</td><td >Software from inventories</td>";
        $this->assertSame($expected, $result);

        $input = ['field' => '_import_category',
            'action_type' => 'assign',
            'value' => 1
        ];
        $result = $rule->getMinimalActionText($input);
        $expected = "<td >Import category from inventory tool</td><td >Assign</td><td >Yes</td>";
        $this->assertSame($expected, $result);

        $input['field'] = '_ignore_import';
        $result = $rule->getMinimalActionText($input);
        $expected = "<td >To be unaware of import</td><td >Assign</td><td >Yes</td>";
        $this->assertSame($expected, $result);
    }

    public function testGetCriteriaDisplayPattern()
    {
        $rule   = new \Rule();
        $this->assertSame(__('Yes'), $rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_EXISTS, 1));
        $this->assertSame(__('Yes'), $rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_DOES_NOT_EXISTS, 1));
        $this->assertSame(__('Yes'), $rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_FIND, 1));

        //FIXME: missing tests?
        /*$result = $rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_IS, 1);
        var_dump($result);*/
    }

    public function testRanking()
    {
       //create a software rule
        $first_rule = new \RuleSoftwareCategory();
        $add = $first_rule->add([
            'sub_type'  => 'RuleSoftwareCategory',
            'name'      => 'my test rule'
        ]);
        $this->assertGreaterThan(0, $add);
        $first_rule = new \RuleSoftwareCategory();
        $this->assertTrue($first_rule->getFromDB($add));
        $this->assertGreaterThan(0, $first_rule->fields['ranking']);

        $second_rule = new \RuleSoftwareCategory();
        $add = $second_rule->add([
            'sub_type'  => 'RuleSoftwareCategory',
            'name'      => 'my other test rule'
        ]);
        $this->assertGreaterThan(0, $add);
        $second_rule = new \RuleSoftwareCategory();
        $this->assertTrue($second_rule->getFromDB($add));
        $this->assertGreaterThan($first_rule->fields['ranking'], $second_rule->fields['ranking']);
    }

    public function testRankingFromBaseRuleClass()
    {
        $rule = new \Rule();
        // create a software rule
        $add = $rule->add([
            'sub_type'  => 'RuleSoftwareCategory',
            'name'      => 'my test rule'
        ]);
        $this->assertGreaterThan(0, $add);
        $first_rule = new \RuleSoftwareCategory();
        $this->assertTrue($first_rule->getFromDB($add));
        $this->assertGreaterThan(0, $first_rule->fields['ranking']);

        $add = $rule->add([
            'sub_type'  => 'RuleSoftwareCategory',
            'name'      => 'my other test rule'
        ]);
        $this->assertGreaterThan(0, $add);
        $second_rule = new \RuleSoftwareCategory();
        $this->assertTrue($second_rule->getFromDB($add));
        $this->assertGreaterThan($first_rule->fields['ranking'], $second_rule->fields['ranking']);
    }

    public function testAllCriteria()
    {
        $classes = $this->getClasses('getCriterias');

        foreach ($classes as $class) {
            $reflection_class = new ReflectionClass($class);
            if ($reflection_class->isAbstract() || !is_subclass_of($class, \Rule::class, true)) {
                continue;
            }

            $rule = new $class();
            $criteria = $rule->getCriterias();

            foreach ($criteria as $key => $criterion) {
                if (!is_array($criterion) || !array_key_exists('type', $criterion) || $criterion['type'] !== 'dropdown') {
                    continue;
                }

                $rulecriteria = new \RuleCriteria();

                $conditions = $rulecriteria->getConditions($class, $key);
                foreach (array_keys($conditions) as $condition) {
                    $rulecriteria->fields = [
                        'id'        => 1,
                        'rules_id'  => 1,
                        'criteria'  => $key,
                        'condition' => $condition,
                        'pattern'   => in_array($condition, [\Rule::REGEX_MATCH,  \Rule::REGEX_NOT_MATCH]) ? '/1/' : 1,
                    ];

                    $results      = [];
                    $regex_result = [];
                    $this->assertIsBool(
                        $rulecriteria->match(
                            $rulecriteria,
                            1,
                            $results,
                            $regex_result
                        )
                    );
                }
            }
        }
    }
}
