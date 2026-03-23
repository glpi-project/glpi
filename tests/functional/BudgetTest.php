<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Budget;
use Consumable;
use ConsumableItem;
use Glpi\Tests\DbTestCase;
use Infocom;

class BudgetTest extends DbTestCase
{
    public function testGetItemListCriteria(): void
    {
        global $DB;

        $this->login();

        $rc = new \ReflectionClass(Budget::class);
        $method = $rc->getMethod('getItemListCriteria');
        $method->setAccessible(true);

        $budget = $this->createItem(Budget::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $this->createItem(Infocom::class, [
            'itemtype' => Consumable::class,
            'items_id' => $this->createItem(Consumable::class, [
                'consumableitems_id' => getItemByTypeName(ConsumableItem::class, '_test_consumableitem01', true),
            ])->getID(),
            'budgets_id' => $budget->getID(),
            'value' => 42,
        ]);

        $query_union = $method->invoke($budget);
        // make sure the criteria can be run as a query without throwing an exception
        $it = $DB->request([
            'FROM' => $query_union,
        ]);
        $this->assertFalse($it->isFailed());
        $this->assertCount(1, $it);
    }
}
