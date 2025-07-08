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

/* Test for inc/rulesoftwarecategory.class.php */

class RuleSoftwareCategoryTest extends DbTestCase
{
    public function testMaxActionsCount()
    {
        $category = new \RuleSoftwareCategory();
        $this->assertSame(3, $category->maxActionsCount());
    }

    public function testGetCriteria()
    {
        $category = new \RuleSoftwareCategory();
        $criteria = $category->getCriterias();
        $this->assertCount(4, $criteria);
    }

    public function testGetActions()
    {
        $category = new \RuleSoftwareCategory();
        $actions  = $category->getActions();
        $this->assertCount(3, $actions);
    }

    public function testDefaultRuleExists()
    {
        $this->assertSame(
            1,
            countElementsInTable(
                'glpi_rules',
                [
                    'uuid' => 'glpi_rule_rule_software_category_import_category_from_inventory_tool',
                    'is_active' => 0,
                ]
            )
        );
        $this->assertSame(
            0,
            countElementsInTable(
                'glpi_rules',
                [
                    'uuid' => 'glpi_rule_rule_software_category_import_category_from_inventory_tool',
                    'is_active' => 1,
                ]
            )
        );
    }

    public function testClone()
    {
        $rule = getItemByTypeName('RuleSoftwareCategory', 'Import category from inventory tool');
        $rules_id = $rule->fields['id'];

        $this->assertSame(0, $rule->fields['is_active']);

        $relations = [
            \RuleAction::class => 1,
            \RuleCriteria::class  => 1,
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
        $this->assertSame('Import category from inventory tool (copy)', $rule->fields['name']);

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
}
