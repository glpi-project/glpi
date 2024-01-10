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

/* Test for inc/ruleimportlocation.class.php */

class RuleImportEntity extends DbTestCase
{
    protected const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    public function testTwoRegexpEntitiesTest()
    {
        $this->login();
        $entity = new \Entity();

        $entities_id_a = $entity->add([
            'name'         => 'Entity A',
            'entities_id'  => 0,
            'completename' => 'Root entitiy > Entity A',
            'level'        => 2,
            'tag'          => 'entA'
        ]);
        $this->integer($entities_id_a)->isGreaterThan(0);

        $entities_id_b = $entity->add([
            'name'         => 'Entity B',
            'entities_id'  => 0,
            'completename' => 'Root entitiy > Entity B',
            'level'        => 2,
            'tag'          => 'entB'
        ]);
        $this->integer($entities_id_b)->isGreaterThan(0);

        $entities_id_c = $entity->add([
            'name'         => 'Entity C',
            'entities_id'  => 0,
            'completename' => 'Root entitiy > Entity C',
            'level'        => 2,
            'tag'          => 'entC'
        ]);
        $this->integer($entities_id_c)->isGreaterThan(0);

       // Add a rule for get entity tag (1)
        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'entity rule 1',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1
        ];
        $rule1_id = $rule->add($input);
        $this->integer($rule1_id)->isGreaterThan(0);

       // Add criteria
        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rule1_id,
            'criteria'  => "name",
            'pattern'   => "/^([A-Za-z0-9]*) - ([A-Za-z0-9]*) - (.*)$/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

       // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rule1_id,
            'action_type' => 'regex_result',
            'field'       => '_affect_entity_by_tag',
            'value'       => '#2'
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);

       // Add a rule for get entity tag (2)
        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'entity rule 2',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 2
        ];
        $rule2_id = $rule->add($input);
        $this->integer($rule2_id)->isGreaterThan(0);

       // Add criteria
        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rule2_id,
            'criteria'  => "name",
            'pattern'   => "/^([A-Za-z0-9]*) - (.*)$/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

       // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rule2_id,
            'action_type' => 'regex_result',
            'field'       => '_affect_entity_by_tag',
            'value'       => '#1'
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);
        $input = [
            'rules_id'    => $rule2_id,
            'action_type' => 'assign',
            'field'       => 'is_recursive',
            'value'       => 1
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);

        $input = [
            'name' => 'computer01 - entC'
        ];

        $ruleEntity = new \RuleImportEntityCollection();
        $ruleEntity->getCollectionPart();
        $ent = $ruleEntity->processAllRules($input, []);

        $expected = [
            'entities_id'  => $entities_id_c,
            'is_recursive' => 1,
            '_ruleid'      => $rule2_id
        ];
        $this->array($ent)->isEqualTo($expected);

        $input = [
            'name' => 'computer01 - blabla - entB'
        ];

        $ruleEntity->getCollectionPart();
        $ent = $ruleEntity->processAllRules($input, []);

        $expected = [
            'entities_id' => $entities_id_b,
            '_ruleid'     => $rule1_id
        ];
        $this->array($ent)->isEqualTo($expected);
    }

    public function testRefusedByEntity()
    {
        global $DB;

        $this->login();

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'entity refuse rule',
            'match'     => 'AND',
            'sub_type'  => \RuleImportEntity::class,
            'ranking'   => 1
        ];
        $rules_id = $rule->add($input);
        $this->integer($rules_id)->isGreaterThan(0);

       // Add criteria
        $rulecriteria = new \RuleCriteria();
        $this->integer(
            $rulecriteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => "name",
                'pattern'   => "/^([A-Za-z0-9]*) - (.*)$/",
                'condition' => \RuleImportEntity::REGEX_MATCH
            ])
        )->isGreaterThan(0);

       // Add action
        $ruleaction = new \RuleAction();
        $this->integer(
            $ruleaction->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => 1
            ])
        )->isGreaterThan(0);

        $input = [
            'name' => 'computer01 - entD'
        ];

        $ruleEntity = new \RuleImportEntityCollection();
        $ruleEntity->getCollectionPart();
        $ent = $ruleEntity->processAllRules($input, []);

        $expected = [
            '_ignore_import' => 1,
            '_ruleid'     => $rules_id
        ];
        $this->array($ent)->isEqualTo($expected);
    }

    /**
     * We want to test optional actions provided by ruleentity, like:
     * - location
     * - groups_id_tech
     * - users_id_tech
     */
    public function testAdditionalActions()
    {
        $this->login();

        $location = new \Location();
        $location_id = $location->add([
            'name' => 'Location 1'
        ]);
        $this->integer($location_id)->isGreaterThan(0);

        $group = new \Group();
        $group_id = $group->add([
            'name' => 'Group tech 1'
        ]);
        $this->integer($group_id)->isGreaterThan(0);

        $user = new \User();
        $user_id = $user->add([
            'name' => 'User tech 1'
        ]);
        $this->integer($user_id)->isGreaterThan(0);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'entity rule additional actions',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1
        ];
        $rule_id = $rule->add($input);
        $this->integer($rule_id)->isGreaterThan(0);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rule_id,
            'criteria'  => "name",
            'pattern'   => "/(.*)/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rule_id,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $location_id
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);
        $input = [
            'rules_id'    => $rule_id,
            'action_type' => 'assign',
            'field'       => 'groups_id_tech',
            'value'       => $group_id
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);
        $input = [
            'rules_id'    => $rule_id,
            'action_type' => 'assign',
            'field'       => 'users_id_tech',
            'value'       => $user_id
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);

        $ruleEntity = new \RuleImportEntityCollection();
        $ruleEntity->getCollectionPart();
        $ent = $ruleEntity->processAllRules([
            'name' => 'computer01'
        ], []);

        $expected = [
            'locations_id'   => $location_id,
            'groups_id_tech' => $group_id,
            'users_id_tech'  => $user_id,
            '_ruleid'        => $rule_id,
        ];
        $this->array($ent)->isEqualTo($expected);
    }

    public function testEntityInheritance()
    {
        global $DB;

        $this->login();
        $entity = new \Entity();

        //create entity rule: anything will be linked to IntEnv entity
        $entities_id_a = $entity->add([
            'name'         => 'Inventory Entity',
            'entities_id'  => 0,
            'completename' => 'Root entitiy > Entity A',
            'level'        => 2,
            'tag'          => 'InvEnt'
        ]);
        $this->integer($entities_id_a)->isGreaterThan(0);

        $all_entities = getAllDataFromTable($entity->getTable());
        $count_entities = count($all_entities);

        // Add a rule for get entity tag (1)
        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'entity rule 1',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1
        ];
        $rule1_id = $rule->add($input);
        $this->integer($rule1_id)->isGreaterThan(0);

        // Add criteria
        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rule1_id,
            'criteria'  => "name",
            'pattern'   => "/^(.*)$/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rule1_id,
            'action_type' => 'regex_result',
            'field'       => '_affect_entity_by_tag',
            'value'       => 'InvEnt'
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);

        $input = [
            'name' => 'computer01 - entC'
        ];

        $ruleEntity = new \RuleImportEntityCollection();
        $ruleEntity->getCollectionPart();
        $ent = $ruleEntity->processAllRules($input, []);

        $expected = [
            'entities_id'  => $entities_id_a,
            '_ruleid'      => $rule1_id
        ];
        $this->array($ent)->isEqualTo($expected);

        //proceed a real inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        $this->integer($computer->fields['entities_id'])->isIdenticalTo($entities_id_a);

        //get connected items
        $iterator = $DB->request(\Computer_Item::getTable(), ['computers_id' => $computer->fields['id']]);
        $this->integer(count($iterator))->isIdenticalTo(2); //1 printer, 1 monitor
        foreach ($iterator as $item) {
            $asset = new $item['itemtype']();
            $this->boolean($asset->getFromDb($item['items_id']))->isTrue();
            $this->integer($asset->fields['entities_id'])->isIdenticalTo(
                $entities_id_a,
                sprintf(
                    '%s #%s does not have the correct entity (%s expected, got %s)',
                    $item['itemtype'],
                    $item['items_id'],
                    $entities_id_a,
                    $asset->fields['entities_id']
                )
            );
        }

        $all_entities = getAllDataFromTable($entity->getTable());
        $this->integer(count($all_entities))->isIdenticalTo($count_entities);
    }

    public function testLocationInventory()
    {
        global $DB;
        $this->login();

        $location = new \Location();
        $locations_parent_id = $location->add([
            'name' => 'Location 2 - parent',
        ]);
        $this->integer($locations_parent_id)->isGreaterThan(0);

        $locations_id = $location->add([
            'locations_id' => $locations_parent_id,
            'name' => 'Location 2 - child'
        ]);
        $this->integer($locations_id)->isGreaterThan(0);

        $all_locations = getAllDataFromTable($location->getTable());
        $count_locations = count($all_locations);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1 - itemtype',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1
        ];
        $rules_id = $rule->add($input);
        $this->integer($rules_id)->isGreaterThan(0);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "itemtype",
            'pattern'   => \Computer::class,
            'condition' => \RuleImportEntity::PATTERN_IS
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $locations_id
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);

        $input = [
            'itemtype' => \Computer::class
        ];

        $ruleLocation = new \RuleImportEntityCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id
        ];
        $this->array($location_data)->isEqualTo($expected);

        $falseinput = [
            'itemtype' => \Printer::class
        ];

        $ruleLocation = new \RuleImportEntityCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($falseinput, []);

        $expected = [
            '_no_rule_matches' => true,
            '_rule_process'    => ""
        ];
        $this->array($location_data)->isEqualTo($expected);

        //proceed a real inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);

        $all_locations = getAllDataFromTable($location->getTable());
        $this->integer(count($all_locations))->isIdenticalTo($count_locations);
    }
}
