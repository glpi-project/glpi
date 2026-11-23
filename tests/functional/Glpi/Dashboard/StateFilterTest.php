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

namespace tests\units\Glpi\Dashboard;

use Computer;
use Glpi\Dashboard\Filters\StateFilter;
use Glpi\Tests\DbTestCase;
use State;

class StateFilterTest extends DbTestCase
{
    public function testGetCriteriaSingleAndMultiple()
    {
        /** @var \DBmysql */
        global $DB;

        $entity = $this->getTestRootEntity(true);

        $state_1 = $this->createItem(State::class, ['name' => __FUNCTION__ . '_1', 'entities_id' => $entity]);
        $state_2 = $this->createItem(State::class, ['name' => __FUNCTION__ . '_2', 'entities_id' => $entity]);

        $computer_1 = $this->createItem(Computer::class, [
            'name'        => __FUNCTION__ . '_1',
            'entities_id' => $entity,
            'states_id'   => $state_1->getID(),
        ]);
        $computer_2 = $this->createItem(Computer::class, [
            'name'        => __FUNCTION__ . '_2',
            'entities_id' => $entity,
            'states_id'   => $state_2->getID(),
        ]);

        $common_criteria = [
            'SELECT' => ['glpi_computers.id AS computers_id'],
            'FROM'   => Computer::getTable(),
        ];

        // single value only computer 1 match
        $found = array_column(
            iterator_to_array(
                $DB->request($common_criteria + StateFilter::getCriteria('glpi_computers', $state_1->getID()))
            ),
            'computers_id'
        );
        $this->assertContains($computer_1->getID(), $found);
        $this->assertNotContains($computer_2->getID(), $found);

        // multiple values both computers match
        $found = array_column(
            iterator_to_array(
                $DB->request(
                    $common_criteria
                    + StateFilter::getCriteria('glpi_computers', [$state_1->getID(), $state_2->getID()])
                )
            ),
            'computers_id'
        );
        $this->assertContains($computer_1->getID(), $found);
        $this->assertContains($computer_2->getID(), $found);
    }

    public function testGetCriteriaEmptyValues()
    {
        $this->assertSame([], StateFilter::getCriteria('glpi_computers', []));
        $this->assertSame([], StateFilter::getCriteria('glpi_computers', 0));
        $this->assertSame([], StateFilter::getCriteria('glpi_computers', ''));
        $this->assertSame([], StateFilter::getCriteria('glpi_computers', [0, '']));
    }

    public function testGetSearchCriteriaSingleAndMultiple()
    {
        $entity = $this->getTestRootEntity(true);

        $state_1 = $this->createItem(State::class, ['name' => __FUNCTION__ . '_1', 'entities_id' => $entity]);
        $state_2 = $this->createItem(State::class, ['name' => __FUNCTION__ . '_2', 'entities_id' => $entity]);

        $single = StateFilter::getSearchCriteria('glpi_computers', $state_1->getID());
        $this->assertCount(1, $single);
        $this->assertSame($state_1->getID(), $single[0]['value']);
        $this->assertSame('equals', $single[0]['searchtype']);
        $this->assertArrayNotHasKey('criteria', $single[0]);

        $single_from_array = StateFilter::getSearchCriteria('glpi_computers', [$state_1->getID()]);
        $this->assertCount(1, $single_from_array);
        $this->assertSame($state_1->getID(), $single_from_array[0]['value']);
        $this->assertArrayNotHasKey('criteria', $single_from_array[0]);

        $multi = StateFilter::getSearchCriteria('glpi_computers', [$state_1->getID(), $state_2->getID()]);
        $this->assertCount(1, $multi);
        $this->assertArrayHasKey('criteria', $multi[0]);
        $this->assertCount(2, $multi[0]['criteria']);
        $this->assertSame('AND', $multi[0]['criteria'][0]['link']);
        $this->assertSame($state_1->getID(), $multi[0]['criteria'][0]['value']);
        $this->assertSame('OR', $multi[0]['criteria'][1]['link']);
        $this->assertSame($state_2->getID(), $multi[0]['criteria'][1]['value']);
    }

    public function testGetSearchCriteriaEmptyValues()
    {
        $this->assertSame([], StateFilter::getSearchCriteria('glpi_computers', []));
        $this->assertSame([], StateFilter::getSearchCriteria('glpi_computers', 0));
        $this->assertSame([], StateFilter::getSearchCriteria('glpi_computers', ''));
    }
}
