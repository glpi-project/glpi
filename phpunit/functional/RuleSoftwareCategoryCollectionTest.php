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

/* Test for inc/rulesoftwarecategorycollection.class.php */

class RuleSoftwareCategoryCollectionTest extends DbTestCase
{
    public function testPrepareInputDataForProcess()
    {
        $this->login();

        $collection = new \RuleSoftwareCategoryCollection();

       //Only process name
        $input = ['name' => 'Software'];
        $params = $collection->prepareInputDataForProcess([], $input);
        $this->assertSame($params, $input);

       //Process name + comment
        $input = ['name' => 'Software', 'comment' => 'Comment'];
        $params = $collection->prepareInputDataForProcess([], $input);
        $this->assertSame($params, $input);

       //Process also manufacturer
        $input = ['name'             => 'Software',
            'comment'          => 'Comment',
            'manufacturers_id' => 1
        ];
        $params = $collection->prepareInputDataForProcess([], $input);
        $this->assertSame('My Manufacturer', $params['manufacturer']);
    }

    public function testNoRuleMatches()
    {
        $this->login();

        $categoryCollection = new \RuleSoftwareCategoryCollection();

       //Default rule is disabled : no rule should match
        $input = ['name'             => 'MySoft',
            'manufacturer'     => 'My Manufacturer',
            '_system_category' => 'dev'
        ];
        $result = $categoryCollection->processAllRules(null, null, $input);
        $this->assertSame(["_no_rule_matches" => '1'], $result);
    }

    public function testRuleMatchImport()
    {
        $this->login();

        $categoryCollection = new \RuleSoftwareCategoryCollection();
        $rule               = new \Rule();

        //Default rule is disabled : no rule should match
        $input = ['name'             => 'MySoft',
            'manufacturer'     => 'My Manufacturer',
            '_system_category' => 'dev'
        ];

        $rules = getAllDataFromTable(
            'glpi_rules',
            ['uuid' => 'glpi_rule_rule_software_category_import_category_from_inventory_tool']
        );
        $this->assertCount(1, $rules);

        $myrule = current($rules);
        $rule->update(['id' => $myrule['id'], 'is_active' => 1]);

       //Force reload of the rules list
        $categoryCollection->RuleList = new \stdClass();
        $categoryCollection->RuleList->load = true;

        //Run the rules engine a second time with the rule enabled
        $result = $categoryCollection->processAllRules(null, null, $input);
        $this->assertSame(
            [
                "_import_category" => '1',
                "_ruleid"          => (string) $myrule['id']
            ],
            $result
        );

        //Set default rule as disabled, as it was before
        $rule->update(['id' => $myrule['id'], 'is_active' => 0]);
    }

    public function testRuleSetCategory()
    {
        $this->login();

        $categoryCollection = new \RuleSoftwareCategoryCollection();

       //Default rule is disabled : no rule should match
        $input = ['name'             => 'MySoft',
            'manufacturer'     => 'My Manufacturer',
            '_system_category' => 'dev'
        ];

       //Let's enable the rule
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();
        $action   = new \RuleAction();

       //Force reload of the rules list
        $categoryCollection->RuleList = new \stdClass();
        $categoryCollection->RuleList->load = true;

        //Create a software category
        $category      = new \SoftwareCategory();
        $categories_id = $category->importExternal('Application');

        $rules_id = $rule->add(['name'        => 'Import name',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleSoftwareCategory',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'MySoft'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'softwarecategories_id',
                'value'       => $categories_id
            ])
        );

        $this->assertSame(
            2,
            countElementsInTable('glpi_rules', ['sub_type' => 'RuleSoftwareCategory'])
        );

        //Test that a software category can be assigned
        $result = $categoryCollection->processAllRules(null, null, $input);
        $this->assertSame(
            [
                "softwarecategories_id" => "$categories_id",
                "_ruleid"               => "$rules_id"
            ],
            $result
        );
    }

    public function testRuleIgnoreImport()
    {
        $this->login();

        $categoryCollection = new \RuleSoftwareCategoryCollection();

       //Let's enable the rule
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();
        $action   = new \RuleAction();

       //Force reload of the rules list
        $categoryCollection->RuleList = new \stdClass();
        $categoryCollection->RuleList->load = true;

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleSoftwareCategory',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => '_system_category',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'dev'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => '1'
            ])
        );

        $this->assertSame(
            2,
            countElementsInTable(
                'glpi_rules',
                ['sub_type' => 'RuleSoftwareCategory']
            )
        );

        $this->assertSame(
            1,
            countElementsInTable(
                'glpi_rules',
                ['sub_type' => 'RuleSoftwareCategory', 'is_active' => 1]
            )
        );

        //Force reload of the rules list
        $categoryCollection->RuleList = new \stdClass();
        $categoryCollection->RuleList->load = true;

        $input = ['name'             => 'fusioninventory-agent',
            'manufacturer'     => 'Teclib',
            '_system_category' => 'dev'
        ];

        //Test that a software category can be ignored
        $result = $categoryCollection->processAllRules(null, null, $input);
        $this->assertSame(
            [
                "_ignore_import" => '1',
                "_ruleid"        => "$rules_id"
            ],
            $result
        );
    }
}
