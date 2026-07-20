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

use Change;
use CommonITILActor;
use Computer;
use Glpi\Dashboard\Filters\UserAssignedFilter;
use Glpi\Tests\DbTestCase;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Ticket;

class UserAssignedFilterTest extends DbTestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function itilProvider(): iterable
    {
        yield 'ticket'  => [Ticket::getTable(), 'glpi_tickets_users', 'tickets_id'];
        yield 'change'  => [Change::getTable(), 'glpi_changes_users', 'changes_id'];
        yield 'problem' => [Problem::getTable(), 'glpi_problems_users', 'problems_id'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function itilTableProvider(): iterable
    {
        yield 'ticket'  => [Ticket::getTable()];
        yield 'change'  => [Change::getTable()];
        yield 'problem' => [Problem::getTable()];
    }

    #[DataProvider('itilProvider')]
    public function testGetCriteria(string $table, string $ul_table, string $fk): void
    {
        $this->login();

        $criteria = UserAssignedFilter::getCriteria($table, '42');
        $this->assertSame(
            [
                "$ul_table as ul_assigned" => [
                    'ON' => [
                        'ul_assigned' => $fk,
                        $table        => 'id',
                    ],
                ],
            ],
            $criteria['JOIN']
        );
        $this->assertSame(CommonITILActor::ASSIGN, $criteria['WHERE']['ul_assigned.type']);
        $this->assertSame(42, $criteria['WHERE']['ul_assigned.users_id']);

        $myself = UserAssignedFilter::getCriteria($table, 'myself');
        $this->assertSame(CommonITILActor::ASSIGN, $myself['WHERE']['ul_assigned.type']);
        $this->assertSame($_SESSION['glpiID'], $myself['WHERE']['ul_assigned.users_id']);

        $this->assertSame([], UserAssignedFilter::getCriteria($table, ''));
        $this->assertSame([], UserAssignedFilter::getCriteria($table, '0'));
        $this->assertSame([], UserAssignedFilter::getCriteria($table, 'invalid'));
    }

    #[DataProvider('itilTableProvider')]
    public function testGetSearchCriteria(string $table): void
    {
        $this->login();

        $criteria = UserAssignedFilter::getSearchCriteria($table, '42');
        $this->assertCount(1, $criteria);
        $this->assertSame('AND', $criteria[0]['link']);
        $this->assertSame('equals', $criteria[0]['searchtype']);
        $this->assertSame(5, $criteria[0]['field']);
        $this->assertSame(42, $criteria[0]['value']);

        $myself = UserAssignedFilter::getSearchCriteria($table, 'myself');
        $this->assertCount(1, $myself);
        $this->assertSame('myself', $myself[0]['value']);

        $this->assertSame([], UserAssignedFilter::getSearchCriteria($table, ''));
        $this->assertSame([], UserAssignedFilter::getSearchCriteria($table, '0'));
        $this->assertSame([], UserAssignedFilter::getSearchCriteria($table, 'invalid'));
    }

    public function testCanBeApplied(): void
    {
        // ITIL items (assigned technician actor)
        $this->assertTrue(UserAssignedFilter::canBeApplied(Ticket::getTable()));
        $this->assertTrue(UserAssignedFilter::canBeApplied(Change::getTable()));
        $this->assertTrue(UserAssignedFilter::canBeApplied(Problem::getTable()));

        // Assets holding a users_id column (assigned user)
        $this->assertTrue(UserAssignedFilter::canBeApplied(Computer::getTable()));

        // Tables without users_id are not supported
        $this->assertFalse(UserAssignedFilter::canBeApplied(Location::getTable()));
    }

    public function testGetCriteriaOnAsset(): void
    {
        $this->login();

        $criteria = UserAssignedFilter::getCriteria(Computer::getTable(), '42');
        $this->assertSame(['WHERE' => ['glpi_computers.users_id' => 42]], $criteria);

        $myself = UserAssignedFilter::getCriteria(Computer::getTable(), 'myself');
        $this->assertSame(['WHERE' => ['glpi_computers.users_id' => $_SESSION['glpiID']]], $myself);

        $this->assertSame([], UserAssignedFilter::getCriteria(Computer::getTable(), ''));
        $this->assertSame([], UserAssignedFilter::getCriteria(Computer::getTable(), '0'));
        $this->assertSame([], UserAssignedFilter::getCriteria(Computer::getTable(), 'invalid'));
    }

    public function testGetSearchCriteriaOnAsset(): void
    {
        $this->login();

        $criteria = UserAssignedFilter::getSearchCriteria(Computer::getTable(), '42');
        $this->assertCount(1, $criteria);
        $this->assertSame('AND', $criteria[0]['link']);
        $this->assertSame('equals', $criteria[0]['searchtype']);
        $this->assertSame(42, $criteria[0]['value']);

        $this->assertSame([], UserAssignedFilter::getSearchCriteria(Computer::getTable(), ''));
        $this->assertSame([], UserAssignedFilter::getSearchCriteria(Computer::getTable(), '0'));
        $this->assertSame([], UserAssignedFilter::getSearchCriteria(Computer::getTable(), 'invalid'));
    }
}
