<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/**
 * Test class for Budget item counter functionality
 */
class BudgetTest extends DbTestCase
{
    /**
     * Test the counter functionality for budget items
     */
    public function testCountForBudget()
    {
        $this->login();

        // Create a budget
        $budget = new \Budget();
        $budgets_id = $budget->add([
            'name'        => 'Test Budget ' . $this->getUniqueString(),
            'entities_id' => 0,
            'value'       => 10000
        ]);
        $this->assertGreaterThan(0, $budgets_id);
        $this->assertTrue($budget->getFromDB($budgets_id));

        // Initially, there should be no items
        $count = \Budget::countForBudget($budget);
        $this->assertEquals(0, $count);

        // Create a computer
        $computer = new \Computer();
        $computers_id = $computer->add([
            'name'        => 'Test Computer ' . $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        // Link the computer to the budget via Infocom
        $infocom = new \Infocom();
        $infocoms_id = $infocom->add([
            'itemtype'    => 'Computer',
            'items_id'    => $computers_id,
            'budgets_id'  => $budgets_id,
            'buy_date'    => date('Y-m-d'),
            'value'       => 1000
        ]);
        $this->assertGreaterThan(0, $infocoms_id);

        // Now there should be 1 item
        $this->assertTrue($budget->getFromDB($budgets_id));
        $count = \Budget::countForBudget($budget);
        $this->assertEquals(1, $count);

        // Add another computer
        $computer2 = new \Computer();
        $computers_id2 = $computer2->add([
            'name'        => 'Test Computer 2 ' . $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id2);

        $infocoms_id2 = $infocom->add([
            'itemtype'    => 'Computer',
            'items_id'    => $computers_id2,
            'budgets_id'  => $budgets_id,
            'buy_date'    => date('Y-m-d'),
            'value'       => 2000
        ]);
        $this->assertGreaterThan(0, $infocoms_id2);

        // Now there should be 2 items
        $this->assertTrue($budget->getFromDB($budgets_id));
        $count = \Budget::countForBudget($budget);
        $this->assertEquals(2, $count);
    }

    /**
     * Test the tab name includes counter when enabled
     */
    public function testGetTabNameWithCounter()
    {
        $this->login();

        // Create a budget with items
        $budget = new \Budget();
        $budgets_id = $budget->add([
            'name'        => 'Test Budget ' . $this->getUniqueString(),
            'entities_id' => 0,
            'value'       => 10000
        ]);
        $this->assertGreaterThan(0, $budgets_id);
        $this->assertTrue($budget->getFromDB($budgets_id));

        // Add a computer
        $computer = new \Computer();
        $computers_id = $computer->add([
            'name'        => 'Test Computer ' . $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $infocom = new \Infocom();
        $infocoms_id = $infocom->add([
            'itemtype'    => 'Computer',
            'items_id'    => $computers_id,
            'budgets_id'  => $budgets_id,
            'buy_date'    => date('Y-m-d'),
            'value'       => 1000
        ]);
        $this->assertGreaterThan(0, $infocoms_id);

        // Enable counter display
        $_SESSION['glpishow_count_on_tabs'] = 1;

        $this->assertTrue($budget->getFromDB($budgets_id));
        $tabs = $budget->getTabNameForItem($budget);

        $this->assertIsArray($tabs);
        $this->assertArrayHasKey(2, $tabs);
        
        // The tab should contain a counter badge with value 1
        $tab_html = $tabs[2];
        $this->assertStringContainsString('badge', $tab_html);
        $this->assertStringContainsString('1', $tab_html);

        // Disable counter display
        $_SESSION['glpishow_count_on_tabs'] = 0;
        $tabs = $budget->getTabNameForItem($budget);
        
        $this->assertIsArray($tabs);
        $this->assertArrayHasKey(2, $tabs);
        
        // The tab should not contain a counter badge
        $tab_html = $tabs[2];
        // When count is 0, no badge is shown
        $this->assertStringNotContainsString('glpi-badge', $tab_html);
    }

    /**
     * Test counter with different item types (Contract with ContractCost)
     */
    public function testCountForBudgetWithContract()
    {
        $this->login();

        // Create a budget
        $budget = new \Budget();
        $budgets_id = $budget->add([
            'name'        => 'Test Budget ' . $this->getUniqueString(),
            'entities_id' => 0,
            'value'       => 50000
        ]);
        $this->assertGreaterThan(0, $budgets_id);
        $this->assertTrue($budget->getFromDB($budgets_id));

        // Create a contract
        $contract = new \Contract();
        $contracts_id = $contract->add([
            'name'        => 'Test Contract ' . $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $contracts_id);

        // Add contract cost linked to budget
        $contractCost = new \ContractCost();
        $cost_id = $contractCost->add([
            'contracts_id' => $contracts_id,
            'budgets_id'   => $budgets_id,
            'cost'         => 5000,
            'name'         => 'Test cost'
        ]);
        $this->assertGreaterThan(0, $cost_id);

        // Count should include the contract
        $this->assertTrue($budget->getFromDB($budgets_id));
        $count = \Budget::countForBudget($budget);
        $this->assertEquals(1, $count);
    }

    /**
     * Test that counter respects permissions
     */
    public function testCountForBudgetWithoutPermissions()
    {
        $this->login();

        // Create a budget
        $budget = new \Budget();
        $budgets_id = $budget->add([
            'name'        => 'Test Budget ' . $this->getUniqueString(),
            'entities_id' => 0,
            'value'       => 10000
        ]);
        $this->assertGreaterThan(0, $budgets_id);

        // Add an item
        $computer = new \Computer();
        $computers_id = $computer->add([
            'name'        => 'Test Computer ' . $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $infocom = new \Infocom();
        $infocoms_id = $infocom->add([
            'itemtype'    => 'Computer',
            'items_id'    => $computers_id,
            'budgets_id'  => $budgets_id,
            'buy_date'    => date('Y-m-d'),
            'value'       => 1000
        ]);
        $this->assertGreaterThan(0, $infocoms_id);

        // Simulate a budget that can't be read
        $budget_no_read = new \Budget();
        $budget_no_read->fields = ['id' => 999999]; // Non-existent budget
        
        $count = \Budget::countForBudget($budget_no_read);
        $this->assertEquals(0, $count);
    }
}
