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

class RuleLocation extends DbTestCase
{
    protected const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    public function testActions()
    {
        $this->login();

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 1',
        ]);
        $this->integer($locations_id)->isGreaterThan(0);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1
        ];
        $rules_id = $rule->add($input);
        $this->integer($rules_id)->isGreaterThan(0);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "tag",
            'pattern'   => "testtag",
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
            'tag' => 'testtag',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id
        ];
        $this->array($location_data)->isEqualTo($expected);

        $falseinput = [
            'tag' => 'testtag2',
        ];
        $location_data = $ruleLocation->processAllRules($falseinput, []);

        $expected = [
            '_no_rule_matches' => true,
            '_rule_process'    => ""
        ];
        $this->array($location_data)->isEqualTo($expected);
    }

    public function testActionRegex()
    {
        $this->login();

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 1',
        ]);
        $this->integer($locations_id)->isGreaterThan(0);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1
        ];
        $rules_id = $rule->add($input);
        $this->integer($rules_id)->isGreaterThan(0);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "tag",
            'pattern'   => " /(.*)/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => 'regex_result',
            'field'       => 'locations_id',
            'value'       => '#0'
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);

        //test with existing location
        $input = [
            'tag' => 'Location 1',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id
        ];
        $this->array($location_data)->isEqualTo($expected);

        //test with non existing location
        $input = [
            'tag' => 'testtag2',
        ];
        $location_data = $ruleLocation->processAllRules($input, []);


        $this->array($location_data)->hasKey('locations_id');
        $this->integer((int)$location_data['locations_id'])->isGreaterThan(0);
        $this->integer((int)$location_data['locations_id'])->isNotEqualTo($locations_id);
        $this->array($location_data)->hasKey('_ruleid');
        $this->integer((int)$location_data['_ruleid'])->isGreaterThan(0);
    }


    public function testIPCIDR()
    {
        $this->login();

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 2',
        ]);
        $this->integer($locations_id)->isGreaterThan(0);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1 - IP CIDR',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1
        ];
        $rules_id = $rule->add($input);
        $this->integer($rules_id)->isGreaterThan(0);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "ip",
            'pattern'   => "192.168.1.1/24",
            'condition' => \RuleImportEntity::PATTERN_CIDR
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
            'ip' => '192.168.1.100',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id
        ];
        $this->array($location_data)->isEqualTo($expected);

        $falseinput = [
            'ip' => '192.168.2.1',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($falseinput, []);

        $expected = [
            '_no_rule_matches' => true,
            '_rule_process'    => ""
        ];
        $this->array($location_data)->isEqualTo($expected);
    }

    public function testSubIPCIDR()
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
            'name'      => 'location rule 1 - IP CIDR',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1
        ];
        $rules_id = $rule->add($input);
        $this->integer($rules_id)->isGreaterThan(0);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "ip",
            'pattern'   => "192.168.1.1/24",
            'condition' => \RuleImportEntity::PATTERN_CIDR
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
            'ip' => '192.168.1.100',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id
        ];
        $this->array($location_data)->isEqualTo($expected);

        $falseinput = [
            'ip' => '192.168.2.1',
        ];

        $ruleLocation = new \RuleLocationCollection();
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

        $all_locations = getAllDataFromTable($location->getTable());
        $this->integer(count($all_locations))->isIdenticalTo($count_locations);
    }
}
