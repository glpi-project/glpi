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

namespace tests\units\Glpi\DBAL;

use Glpi\DBAL\QueryIdentifier;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class QueryIdentifierTest extends GLPITestCase
{
    public static function identifierProvider(): iterable
    {
        yield 'simple name' => ['name', null, '`name`'];
        yield 'qualified name' => ['glpi_computers.name', null, '`glpi_computers`.`name`'];
        yield 'wildcard' => ['*', null, '*'];
        yield 'qualified wildcard' => ['glpi_computers.*', null, '`glpi_computers`.*'];
        yield 'already quoted' => ['`name`', null, '`name`'];
        yield 'backtick is escaped' => ['na`me', null, '`na``me`'];
        yield 'with alias' => ['glpi_computers.name', 'computer_name', '`glpi_computers`.`name` AS `computer_name`'];
    }

    #[DataProvider('identifierProvider')]
    public function testGetValue(string $name, ?string $alias, string $expected): void
    {
        $this->assertSame($expected, (new QueryIdentifier($name, $alias))->getValue());
    }

    #[DataProvider('identifierProvider')]
    public function testToString(string $name, ?string $alias, string $expected): void
    {
        $this->assertSame($expected, (string) new QueryIdentifier($name, $alias));
    }

    public function testAnIdentifierBindsNoValue(): void
    {
        $this->assertSame([], (new QueryIdentifier('glpi_computers.name'))->getParams());
    }

    public function testAnEmptyNameIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot build an empty identifier');
        new QueryIdentifier('');
    }

    public function testAnEmptyAliasIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot build an empty alias');
        new QueryIdentifier('name', '');
    }
}
