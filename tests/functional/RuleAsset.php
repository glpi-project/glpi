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

use Computer;
use DbTestCase;
use Generator;
use Rule;
use RuleAction;
use RuleCriteria;
use SingletonRuleList;

class RuleAsset extends DbTestCase
{
    protected function testCriteriaProvider(): Generator
    {
        // Test case 1 for last_inventory_update -> Precise date
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => "2022-02-28 22:05:30",
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => '2022-02-28 22:05:30',
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_EQUAL,
            'success'        => true,
        ];

        // Test case 2 for last_inventory_update -> Precise date
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => "2022-02-28 22:05:30",
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => '2022-02-28 22:05:31',
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_EQUAL,
            'success'        => false,
        ];

        // Test case 3 for last_inventory_update -> Today
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => $_SESSION["glpi_currenttime"],
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => 'TODAY',
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_EQUAL,
            'success'        => true,
        ];

        // Test case 4 for last_inventory_update -> Today
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => "2022-02-28 22:05:30",
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => 'TODAY',
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_EQUAL,
            'success'        => false,
        ];

        // Test case 5 for last_inventory_update -> before relative date
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => date('Y-m-d H:i:s', time() - 18000),
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => '-2HOUR',
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_BEFORE,
            'success'        => true,
        ];

        // Test case 6 for last_inventory_update -> before relative date
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => $_SESSION["glpi_currenttime"],
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => '-2DAY',
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_BEFORE,
            'success'        => false,
        ];

        // Test case 7 for last_inventory_update -> after fixed date
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => "2022-04-15 17:34:47",
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => "2022-02-28 22:05:30",
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_AFTER,
            'success'        => true,
        ];

        // Test case 8 for last_inventory_update -> after fixed date
        yield [
            'itemtype' => Computer::getType(),
            'input' => [
                'name'                  => 'testLastInventoryUpdateCriteria1',
                'last_inventory_update' => "2022-02-26 20:23:18",
                'entities_id'           => 0,
            ],
            'criteria_field' => 'last_inventory_update',
            'criteria_value' => "2022-02-28 22:05:30",
            'action_field'   => 'comment',
            'condition'      => Rule::PATTERN_DATE_IS_AFTER,
            'success'        => false,
        ];
    }

    /**
     * Test a given criteria
     *
     * @dataprovider testCriteriaProvider
     *
     * @param string $itemtype          Test subject's type
     * @param array  $input             Input used to create the test subject
     * @param string $criteria_field    The tested criteria name
     * @param string $criteria_value    The tested criteria value
     * @param string $action_field      Field used in the action to test the
     *                                  results.
     *                                  Must be a string or text field and be
     *                                  different that $criteria_field
     * @param int    $condition         Condition operator (is, is not, ...)
     * @param bool   $success           Is the rule expected to succeed ?
     */
    public function testCriteria(
        string $itemtype,
        array $input,
        string $criteria_field,
        string $criteria_value,
        string $action_field,
        int $condition,
        bool $success
    ) {
        global $DB;

        $this->login();

        // Disable all others rules before running the test
        $DB->update(Rule::getTable(), ['is_active' => false], [
            'sub_type' => "RuleAsset"
        ]);
        $active_rules = countElementsInTable(Rule::getTable(), [
            'is_active' => true,
            'sub_type'  => "RuleAsset",
        ]);
        $this->integer($active_rules)->isEqualTo(0);

        // Create the rule
        $rule_asset = $this->createItem(\RuleAsset::getType(), [
            'name'      => 'testLastInventoryUpdateCriteria',
            'match'     => 'AND',
            'is_active' => true,
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONUPDATE,
        ]);

        // Add the condition
        $this->createItem(RuleCriteria::getType(), [
            'rules_id'  => $rule_asset->getID(),
            'criteria'  => $criteria_field,
            'condition' => $condition,
            'pattern'   => $criteria_value,
        ]);

        // Add the action
        $this->createItem(RuleAction::getType(), [
            'rules_id'    => $rule_asset->getID(),
            'action_type' => "assign",
            'field'       => $action_field,
            'value'       => "value_changed",
        ]);

        // Reset rule cache
        SingletonRuleList::getInstance("RuleAsset", 0)->load = 0;
        SingletonRuleList::getInstance("RuleAsset", 0)->list = [];

        // Creat the test subject
        $item = $this->createItem($itemtype, $input);

        // Safety check before test
        $this->variable($item->fields[$action_field])->isNotEqualTo("value_changed");

        // Execute the test
        $update = $item->update([
            'id'          => $item->getID(),
            $action_field => 'value_not_changed',
        ]);
        $this->boolean($update)->isTrue();
        $this->boolean($item->getFromDB($item->getID()))->isTrue();

        // Check whether or not the rule affected our item
        $value = $item->fields[$action_field];
        if ($success == true) {
            $this->string($value)->isEqualTo("value_changed");
        } else {
            $this->string($value)->isEqualTo("value_not_changed");
        }
    }
}
