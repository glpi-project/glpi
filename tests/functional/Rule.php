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

namespace tests\units;

use DbTestCase;
use ReflectionClass;

/* Test for inc/rule.class.php */

class Rule extends DbTestCase
{
    public function testGetTable()
    {
        $table = \Rule::getTable('RuleDictionnarySoftware');
        $this->string($table)->isIdenticalTo('glpi_rules');

        $table = \Rule::getTable('RuleTicket');
        $this->string($table)->isIdenticalTo('glpi_rules');
    }

    public function testGetTypeName()
    {
        $this->string(\Rule::getTypeName(1))->isIdenticalTo('Rule');
        $this->string(\Rule::getTypeName(\Session::getPluralNumber()))->isIdenticalTo('Rules');
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
        $this->integer((int)$rules_id)->isGreaterThan(0);

        $obj = \Rule::getRuleObjectByID($rules_id);
        $this->object($obj)->isInstanceOf('RuleDictionnarySoftware');

        $this->variable(\Rule::getRuleObjectByID(100))->isNull();
    }

    public function testGetConditionsArray()
    {
        $this->array(\Rule::getConditionsArray())->isEmpty();

        $conditions = \RuleTicket::getConditionsArray();
        $this->array($conditions)->isIdenticalTo([
            1 => "Add",
            2 => "Update",
            3 => "Add / Update"
        ]);
    }

    public function testUseConditions()
    {
        $rule = new \Rule();
        $this->boolean($rule->useConditions())->isFalse();

        $ruleticket = new \RuleTicket();
        $this->boolean($ruleticket->useConditions())->isTrue();
    }

    public function testGetConditionName()
    {
        $this->string(\Rule::getConditionName(-1))->isIdenticalTo(NOT_AVAILABLE);
        $this->string(\Rule::getConditionName(110))->isIdenticalTo(NOT_AVAILABLE);
        $this->string(\RuleTicket::getConditionName(1))->isIdenticalTo('Add');
        $this->string(\RuleTicket::getConditionName(2))->isIdenticalTo('Update');
        $this->string(\RuleTicket::getConditionName(3))->isIdenticalTo('Add / Update');
    }

    public function testGetRuleActionClass()
    {
        $rule = new \Rule();
        $this->string($rule->getRuleActionClass())->isIdenticalTo('RuleAction');

        $rule = new \RuleTicket();
        $this->string($rule->getRuleActionClass())->isIdenticalTo('RuleAction');
    }

    public function testGetRuleCriteriaClass()
    {
        $rule = new \Rule();
        $this->string($rule->getRuleCriteriaClass())->isIdenticalTo('RuleCriteria');

        $rule = new \RuleTicket();
        $this->string($rule->getRuleCriteriaClass())->isIdenticalTo('RuleCriteria');
    }

    public function testGetRuleIdField()
    {
        $rule = new \Rule();
        $this->string($rule->getRuleIdField())->isIdenticalTo('rules_id');

        $rule = new \RuleDictionnaryPrinter();
        $this->string($rule->getRuleIdField())->isIdenticalTo('rules_id');
    }

    public function testIsEntityAssign()
    {
        $rule = new \Rule();
        $this->boolean($rule->isEntityAssign())->isFalse();

        $rule = new \RuleTicket();
        $this->boolean($rule->isEntityAssign())->isTrue();
    }

    public function testPost_getEmpty()
    {
        $rule = new \Rule();
        $rule->getEmpty();
        $this->variable($rule->fields['is_active'])->isEqualTo(0);
    }

    public function testGetTitle()
    {
        $rule = new \Rule();
        $this->string($rule->getTitle())->isIdenticalTo(__('Rules management'));

        $rule = new \RuleTicket();
        $this->string($rule->getTitle())->isIdenticalTo(__('Business rules for tickets'));
    }

    public function testGetCollectionClassName()
    {
        $rule = new \Rule();
        $this->string($rule->getCollectionClassName())->isIdenticalTo('RuleCollection');

        $rule = new \RuleTicket();
        $this->string($rule->getCollectionClassName())->isIdenticalTo('RuleTicketCollection');
    }

    public function testGetSpecificMassiveActions()
    {
        $rule    = new \Rule();
        $actions = $rule->getSpecificMassiveActions();
        $this->array($actions)->isIdenticalTo([
            'Rule:export'     => '<i class=\'fas fa-file-download\'></i>Export'
        ]);

        $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = ALLSTANDARDRIGHT;
        $rule    = new \RuleDictionnarySoftware();
        $actions = $rule->getSpecificMassiveActions();
        $this->array($actions)->isIdenticalTo([
            'Rule:move_rule' => '<i class=\'fas fa-arrows-alt-v\'></i>Move',
            'Rule:export'     => '<i class=\'fas fa-file-download\'></i>Export'
        ]);

        $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = READ;
        $rule    = new \RuleDictionnarySoftware();
        $actions = $rule->getSpecificMassiveActions();
        $this->array($actions)->isIdenticalTo([
            'Rule:export'     => '<i class=\'fas fa-file-download\'></i>Export'
        ]);
    }

    public function testGetSearchOptionsNew()
    {
        $rule = new \Rule();
        $this->array($rule->rawSearchOptions())->hasSize(11);
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
        $this->integer((int)$rules_id)->isGreaterThan(0);

        $this->integer(
            (int)$criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'Mozilla Firefox 52'
            ])
        )->isGreaterThan(0);

        $this->integer(
            (int)$criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'Mozilla Firefox 53'
            ])
        )->isGreaterThan(0);

        $this->integer(
            (int)$action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => '1'
            ])
        )->isGreaterThan(0);

        $this->boolean($rule->getRuleWithCriteriasAndActions($rules_id))->isTrue();
        $this->array($rule->criterias)->isEmpty();
        $this->array($rule->actions)->isEmpty();

        $this->boolean($rule->getRuleWithCriteriasAndActions($rules_id, 1, 1))->isTrue();
        $this->array($rule->criterias)->hasSize(2);
        $this->array($rule->actions)->hasSize(1);

        $this->boolean($rule->getRuleWithCriteriasAndActions(100))->isFalse();
    }

    public function testMaxActionsCount()
    {
        $rule = new \Rule();
        $this->integer($rule->maxActionsCount())->isIdenticalTo(1);

        $rule = new \RuleTicket();
        $this->integer($rule->maxActionsCount())->isIdenticalTo(36);

        $rule = new \RuleDictionnarySoftware();
        $this->integer($rule->maxActionsCount())->isIdenticalTo(4);
    }

    public function testMaybeRecursive()
    {
        $rule = new \Rule();
        $this->boolean($rule->maybeRecursive())->isFalse();

        $rule = new \RuleTicket();
        $this->boolean($rule->maybeRecursive())->isTrue();
    }

    public function testGetCriteria()
    {
        $ruleTicket = new \RuleTicket();
        $criteria   = $ruleTicket->getCriteria('locations_id');
        $this->string($criteria['table'])->isIdenticalTo('glpi_locations');
        $this->array($ruleTicket->getCriteria('location'))->isEmpty();
    }

    public function testGetAction()
    {
        $ruleTicket = new \RuleTicket();
        $action     = $ruleTicket->getAction('locations_id');
        $this->string($action['table'])->isIdenticalTo('glpi_locations');
        $this->array($ruleTicket->getAction('location'))->isEmpty();
    }

    public function testGetCriteriaName()
    {
        $ruleTicket = new \RuleTicket();
        $this->string($ruleTicket->getCriteriaName('locations_id'))->isIdenticalTo('Ticket location');
        $this->string($ruleTicket->getCriteriaName('location'))->isIdenticalTo(__('Unavailable'));
    }

    protected function actionsNamesProvider()
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
        $this->string($ruleTicket->getActionName($field))->isIdenticalTo($label);
    }

    public function testPrepareInputDataForProcess()
    {
        $rule = new \Rule();
        $input = ['name' => 'name', 'test' => 'test'];
        $result = $rule->prepareInputDataForProcess($input, ['test2' => 'test2']);
        $this->array($result)->isIdenticalTo($input);
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
        $this->integer((int)$rules_id)->isGreaterThan(0);

        $criterion_1 = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52'
        ]);
        $this->integer((int)$criterion_1)->isGreaterThan(0);

        $criterion_2 = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 53'
        ]);
        $this->integer((int)$criterion_2)->isGreaterThan(0);

        $action_1 = $action->add(['rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => '_ignore_import',
            'value'       => '1'
        ]);
        $this->integer((int)$action_1)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($rules_id))->isTrue();
        $rule->cleanDBonPurge();
        $this->boolean($criteria->getFromDB($criterion_1))->isFalse();
        $this->boolean($criteria->getFromDB($criterion_2))->isFalse();
        $this->boolean($action->getFromDB($action_1))->isFalse();
    }

    public function testPrepareInputForAdd()
    {
        $rule     = new \RuleRight();
       //Add a new rule
        $rules_id = $rule->add(['name' => 'MyRule', 'is_active' => 1]);
        $this->integer((int)$rules_id)->isGreaterThan(0);
        $this->boolean($rule->getFromDB($rules_id))->isTrue();
       //Check that an uuid has been generated
        $this->string($rule->fields['uuid'])->isNotEmpty();
       //Check that a ranking has been added
        $this->integer($rule->fields['ranking'])->isGreaterThan(0);

       //Add a rule and provide an uuid
        $rules_id = $rule->add(['name' => 'MyRule', 'uuid' => '12345']);
        $this->integer((int)$rules_id)->isGreaterThan(0);
        $this->boolean($rule->getFromDB($rules_id))->isTrue();
       //Check that the uuid has been added as it is, and has not been overriden
        $this->string($rule->fields['uuid'])->isIdenticalTo('12345');
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
       //The criterion doesn't exists
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Unavailable</td><td >contains</td><td >_loc</td>";
        $this->string($result)->isIdenticalTo($expected);

        $input['criteria'] = '_locations_id_of_requester';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >contains</td><td >_loc</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition IS
        $input['condition'] = \Rule::PATTERN_IS;
        $input['pattern']   = $location->getID();
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >is</td><td >_location01 (Root entity)</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition IS NOT
        $input['condition'] = \Rule::PATTERN_IS_NOT;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >is not</td><td >_location01 (Root entity)</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition REGEX MATCH
        $input['condition'] = \Rule::REGEX_MATCH;
        $input['pattern']   = '/(loc)/';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >regular expression matches</td><td >/(loc)/</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition REGEX DOESN NOT MATCH
        $input['condition'] = \Rule::REGEX_NOT_MATCH;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >regular expression does not match</td><td >/(loc)/</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition EXISTS
        $input['condition'] = \Rule::PATTERN_EXISTS;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >exists</td><td >Yes</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition DOES NOT EXIST
        $input['condition'] = \Rule::PATTERN_DOES_NOT_EXISTS;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >does not exist</td><td >Yes</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_UNDER;
        $input['pattern']   = $location->getID();
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >under</td><td >_location01 (Root entity)</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_NOT_UNDER;
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >not under</td><td >_location01 (Root entity)</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_BEGIN;
        $input['pattern']   = '_loc';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >starting with</td><td >_loc</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_END;
        $input['pattern']   = '_loc';
        $result   = $rule->getMinimalCriteriaText($input);
        $expected = "<td >Requester location</td><td >finished by</td><td >_loc</td>";
        $this->string($result)->isIdenticalTo($expected);

       //Testing condition UNDER
        $input['condition'] = \Rule::PATTERN_END;
        $input['pattern']   = '_loc';
        $result   = $rule->getMinimalCriteriaText($input, 'aaaa');
        $expected = "<td aaaa>Requester location</td><td aaaa>finished by</td><td aaaa>_loc</td>";
        $this->string($result)->isIdenticalTo($expected);
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
        $this->string($result)->isIdenticalTo($expected);

        $input = ['field' => '_import_category',
            'action_type' => 'assign',
            'value' => 1
        ];
        $result = $rule->getMinimalActionText($input);
        $expected = "<td >Import category from inventory tool</td><td >Assign</td><td >Yes</td>";
        $this->string($result)->isIdenticalTo($expected);

        $input['field'] = '_ignore_import';
        $result = $rule->getMinimalActionText($input);
        $expected = "<td >To be unaware of import</td><td >Assign</td><td >Yes</td>";
        $this->string($result)->isIdenticalTo($expected);
    }

    public function testGetCriteriaDisplayPattern()
    {
        $rule   = new \Rule();
        $this->string($rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_EXISTS, 1))->isIdenticalTo(__('Yes'));
        $this->string($rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_DOES_NOT_EXISTS, 1))->isIdenticalTo(__('Yes'));
        $this->string($rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_FIND, 1))->isIdenticalTo(__('Yes'));

       //FIXME: missing tests?
       /*$result = $rule->getCriteriaDisplayPattern(9, \Rule::PATTERN_IS, 1);
       var_dump($result);*/
    }

    public function testClone()
    {
        $rule = getItemByTypeName('Rule', 'One user assignation');
        $rules_id = $rule->fields['id'];

        $this->integer($rule->fields['is_active'])->isIdenticalTo(1);
        $this->string($rule->fields['name'])->isIdenticalTo('One user assignation');

        $relations = [
            \RuleAction::class => 1,
            \RuleCriteria::class  => 3
        ];

        foreach ($relations as $relation => $expected) {
            $this->integer(
                countElementsInTable(
                    $relation::getTable(),
                    ['rules_id' => $rules_id]
                )
            )->isIdenticalTo($expected);
        }

        $cloned = $rule->clone();
        $this->integer($cloned)->isGreaterThan($rules_id);
        $this->boolean($rule->getFromDB($cloned))->isTrue();

        $this->integer($rule->fields['is_active'])->isIdenticalTo(0);
        $this->string($rule->fields['name'])->isIdenticalTo('One user assignation (copy)');

        foreach ($relations as $relation => $expected) {
            $this->integer(
                countElementsInTable(
                    $relation::getTable(),
                    ['rules_id' => $cloned]
                )
            )->isIdenticalTo($expected);
        }

        //rename rule with a quote, then clone
        $rules_id = $cloned;
        $rule = new \RuleAsset(); //needed to reset last_clone_index...
        $this->boolean($rule->update(['id' => $rules_id, 'name' => addslashes("User's assigned")]))->isTrue();
        $this->boolean($rule->getFromDB($rules_id))->isTrue();

        $cloned = $rule->clone();
        $this->integer($cloned)->isGreaterThan($rules_id);
        $this->boolean($rule->getFromDB($cloned))->isTrue();

        $this->integer($rule->fields['is_active'])->isIdenticalTo(0);
        $this->string($rule->fields['name'])->isIdenticalTo("User's assigned (copy)");

        foreach ($relations as $relation => $expected) {
            $this->integer(
                countElementsInTable(
                    $relation::getTable(),
                    ['rules_id' => $cloned]
                )
            )->isIdenticalTo($expected);
        }
    }

    public function testRanking()
    {
       //create a software rule
        $first_rule = new \RuleSoftwareCategory();
        $add = $first_rule->add([
            'sub_type'  => 'RuleSoftwareCategory',
            'name'      => 'my test rule'
        ]);
        $this->integer($add)->isGreaterThan(0);
        $first_rule = new \RuleSoftwareCategory();
        $this->boolean($first_rule->getFromDB($add))->isTrue();
        $this->integer($first_rule->fields['ranking'])->isGreaterThan(0);

        $second_rule = new \RuleSoftwareCategory();
        $add = $second_rule->add([
            'sub_type'  => 'RuleSoftwareCategory',
            'name'      => 'my other test rule'
        ]);
        $this->integer($add)->isGreaterThan(0);
        $second_rule = new \RuleSoftwareCategory();
        $this->boolean($second_rule->getFromDB($add))->isTrue();
        $this->integer($second_rule->fields['ranking'])->isGreaterThan($first_rule->fields['ranking']);
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
                    $this->boolean(
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
