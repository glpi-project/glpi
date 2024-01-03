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

/* Test for inc/rulesoftwarecategory.class.php */

class RuleSoftwareCategory extends DbTestCase
{
    public function testMaxActionsCount()
    {
        $category = new \RuleSoftwareCategory();
        $this->integer($category->maxActionsCount())->isIdenticalTo(1);
    }

    public function testGetCriteria()
    {
        $category = new \RuleSoftwareCategory();
        $criteria = $category->getCriterias();
        $this->array($criteria)->hasSize(4);
    }

    public function testGetActions()
    {
        $category = new \RuleSoftwareCategory();
        $actions  = $category->getActions();
        $this->array($actions)->hasSize(3);
    }

    public function testDefaultRuleExists()
    {
        $this->integer(
            (int)countElementsInTable(
                'glpi_rules',
                [
                    'uuid' => 'glpi_rule_rule_software_category_import_category_from_inventory_tool',
                    'is_active' => 0
                ]
            )
        )->isIdenticalTo(1);
        $this->integer(
            (int)countElementsInTable(
                'glpi_rules',
                [
                    'uuid' => 'glpi_rule_rule_software_category_import_category_from_inventory_tool',
                    'is_active' => 1
                ]
            )
        )->isIdenticalTo(0);
    }

    public function testClone()
    {
        $rule = getItemByTypeName('RuleSoftwareCategory', 'Import category from inventory tool');
        $rules_id = $rule->fields['id'];

        $this->integer($rule->fields['is_active'])->isIdenticalTo(0);

        $relations = [
            \RuleAction::class => 1,
            \RuleCriteria::class  => 1
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
        $this->string($rule->fields['name'])->isIdenticalTo('Import category from inventory tool (copy)');

        foreach ($relations as $relation => $expected) {
            $this->integer(
                countElementsInTable(
                    $relation::getTable(),
                    ['rules_id' => $cloned]
                )
            )->isIdenticalTo($expected);
        }
    }
}
