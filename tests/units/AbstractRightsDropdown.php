<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use Change;
use Generator;
use Group;
use Profile;
use Ticket;
use User;

class AbstractRightsDropdown extends \GLPITestCase
{
    protected function testGetPostedIdsProvider(): Generator
    {
        $flat_values_set = [
            'users_id-3',
            'users_id-14',
            'groups_id-2',
            'groups_id-78',
            'profiles_id-1',
        ];

        // Test 1: looking for users_id
        yield [
            'values'       => $flat_values_set,
            'class'        => User::class,
            'expected_ids' => [3, 14],
        ];

        // Test 2: looking for groups_id
        yield [
            'values'       => $flat_values_set,
            'class'        => Group::class,
            'expected_ids' => [2, 78],
        ];

        // Test 3: looking for profiles_id
        yield [
            'values'       => $flat_values_set,
            'class'        => Profile::class,
            'expected_ids' => [1],
        ];

        // Test 4: looking for tickets_id (no values)
        yield [
            'values'       => $flat_values_set,
            'class'        => Ticket::class,
            'expected_ids' => [],
        ];

        // Test 5: empty input
        yield [
            'values'       => [],
            'class'        => Change::class,
            'expected_ids' => [],
        ];
    }

    /**
     * @dataprovider testGetPostedIdsProvider
     */
    public function testGetPostedIds(
        array $values,
        string $class,
        array $expected_ids
    ): void {
        $ids = \AbstractRightsDropdown::getPostedIds($values, $class);
        $this->array($ids)->isEqualTo($expected_ids);
    }
}
