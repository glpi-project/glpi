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

use Glpi\Tests\DbTestCase;

class DefaultFilterTest extends DbTestCase
{
    private function createDefaultFilterWithCriteria(bool $is_active): \DefaultFilter
    {
        $filter = $this->createItem(\DefaultFilter::class, [
            'name'      => 'Test filter',
            'itemtype'  => \Ticket::class,
            'is_active' => (int) $is_active,
        ]);

        $filter->saveFilter([
            ['link' => 'AND', 'field' => 12, 'searchtype' => 'equals', 'value' => 'notold'],
        ]);

        return $filter;
    }

    public function testGetSearchCriteriaReturnsNullWhenNoFilter(): void
    {
        $this->assertNull(\DefaultFilter::getSearchCriteria(\Ticket::class));
    }

    public function testGetSearchCriteriaReturnsDataWhenActive(): void
    {
        $this->createDefaultFilterWithCriteria(true);

        $result = \DefaultFilter::getSearchCriteria(\Ticket::class);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('search_criteria', $result);
        $this->assertSame('AND', $result['search_criteria']['link']);
        $this->assertNotEmpty($result['search_criteria']['criteria']);
    }

    public function testGetSearchCriteriaReturnsNullWhenInactive(): void
    {
        $this->createDefaultFilterWithCriteria(false);

        $this->assertNull(\DefaultFilter::getSearchCriteria(\Ticket::class));
    }

    private function createActiveFilterWithoutCriteria(): \DefaultFilter
    {
        return $this->createItem(\DefaultFilter::class, [
            'name'      => 'Test filter without criteria',
            'itemtype'  => \Ticket::class,
            'is_active' => 1,
        ]);
    }

    // Verify if a filter exists where no criteria are saved
    public function testGetSearchCriteriaReturnsNullWhenActiveWithoutCriteria(): void
    {
        $this->createActiveFilterWithoutCriteria();

        $this->assertNull(\DefaultFilter::getSearchCriteria(\Ticket::class));
    }
}
