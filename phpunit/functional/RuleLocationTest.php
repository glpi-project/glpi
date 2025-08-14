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
use Glpi\Inventory\Inventory;

/* Test for inc/ruleimportlocation.class.php */

class RuleLocationTest extends DbTestCase
{
    protected const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    public function testActions()
    {
        $this->login();

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 1',
        ]);
        $this->assertGreaterThan(0, $locations_id);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1,
        ];
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "tag",
            'pattern'   => "testtag",
            'condition' => \RuleImportEntity::PATTERN_IS,
        ];
        $this->assertGreaterThan(0, $rulecriteria->add($input));

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $locations_id,
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        $input = [
            'tag' => 'testtag',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id,
        ];
        $this->assertEquals($expected, $location_data);

        $falseinput = [
            'tag' => 'testtag2',
        ];
        $location_data = $ruleLocation->processAllRules($falseinput, []);

        $expected = [
            '_no_rule_matches' => true,
            '_rule_process'    => "",
        ];
        $this->assertEquals($expected, $location_data);
    }

    public function testActionRegex()
    {
        $this->login();

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 1',
        ]);
        $this->assertGreaterThan(0, $locations_id);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1,
        ];
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "tag",
            'pattern'   => " /(.*)/",
            'condition' => \RuleImportEntity::REGEX_MATCH,
        ];
        $this->assertGreaterThan(0, $rulecriteria->add($input));

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => 'regex_result',
            'field'       => 'locations_id',
            'value'       => '#0',
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        //test with existing location
        $input = [
            'tag' => 'Location 1',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id,
        ];
        $this->assertEquals($expected, $location_data);

        //test with non existing location
        $input = [
            'tag' => 'testtag2',
        ];
        $location_data = $ruleLocation->processAllRules($input, []);

        $this->assertArrayHasKey('locations_id', $location_data);
        $this->assertGreaterThan(0, (int) $location_data['locations_id']);
        $this->assertNotEquals($locations_id, (int) $location_data['locations_id']);
        $this->assertArrayHasKey('_ruleid', $location_data);
        $this->assertGreaterThan(0, (int) $location_data['_ruleid']);
    }


    public function testIPCIDR()
    {
        $this->login();

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 2',
        ]);
        $this->assertGreaterThan(0, $locations_id);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1 - IP CIDR',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1,
        ];
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "ip",
            'pattern'   => "192.168.1.1/24",
            'condition' => \RuleImportEntity::PATTERN_CIDR,
        ];
        $this->assertGreaterThan(0, $rulecriteria->add($input));

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $locations_id,
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        $input = [
            'ip' => '192.168.1.100',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id,
        ];
        $this->assertEquals($expected, $location_data);

        $falseinput = [
            'ip' => '192.168.2.1',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($falseinput, []);

        $expected = [
            '_no_rule_matches' => true,
            '_rule_process'    => "",
        ];
        $this->assertEquals($expected, $location_data);
    }

    public function testSubIPCIDR()
    {
        global $DB;
        $this->login();

        $location = new \Location();
        $locations_parent_id = $location->add([
            'name' => 'Location 2 - parent',
        ]);
        $this->assertGreaterThan(0, $locations_parent_id);

        $locations_id = $location->add([
            'locations_id' => $locations_parent_id,
            'name' => 'Location 2 - child',
        ]);
        $this->assertGreaterThan(0, $locations_id);

        $all_locations = getAllDataFromTable($location->getTable());
        $count_locations = count($all_locations);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule 1 - IP CIDR',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1,
        ];
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "ip",
            'pattern'   => "192.168.1.1/24",
            'condition' => \RuleImportEntity::PATTERN_CIDR,
        ];
        $this->assertGreaterThan(0, $rulecriteria->add($input));

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $locations_id,
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        $input = [
            'ip' => '192.168.1.100',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($input, []);

        $expected = [
            'locations_id' => $locations_id,
            '_ruleid'      => $rules_id,
        ];
        $this->assertEquals($expected, $location_data);

        $falseinput = [
            'ip' => '192.168.2.1',
        ];

        $ruleLocation = new \RuleLocationCollection();
        $ruleLocation->getCollectionPart();
        $location_data = $ruleLocation->processAllRules($falseinput, []);

        $expected = [
            '_no_rule_matches' => true,
            '_rule_process'    => "",
        ];
        $this->assertEquals($expected, $location_data);

        //proceed a real inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = new Inventory($json);

        if ($inventory->inError()) {
            dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertSame(1, count($agents));
        $agent = $agents->current();

        $this->assertIsArray($agent);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        $all_locations = getAllDataFromTable($location->getTable());
        $this->assertCount($count_locations, $all_locations);
    }


    public function testActionsFromTestContext()
    {
        $this->login();

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location_Test',
        ]);
        $this->assertGreaterThan(0, $locations_id);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'location rule test context',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1,
        ];
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);

        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rules_id,
            'criteria'  => "tag",
            'pattern'   => "/(.*)/",
            'condition' => \RuleImportEntity::REGEX_MATCH,
        ];
        $this->assertGreaterThan(0, $rulecriteria->add($input));

        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => 'regex_result',
            'field'       => 'locations_id',
            'value'       => "#0",
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        // test rule like rule.test.php
        $rule = new \RuleLocation();
        $rule->getRuleWithCriteriasAndActions($rules_id, 1, 1);

        $params = $rule->addSpecificParamsForPreview([]);
        $input = $rule->prepareAllInputDataForProcess(['tag' => 'testtag'], $params);

        // intercepts the output of echo functions
        // as showRulePreviewResultsForm is also in charge of displaying the result (in addition to testing the rule)
        ob_start();
        $rule->showRulePreviewResultsForm($input, $params);
        ob_end_clean();

        // check that location was not created
        $location = new \Location();
        $this->assertNotTrue($location->getFromDBByCrit(['name' => 'testtag']));
    }
}
