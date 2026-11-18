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

use Glpi\DBAL\Result;
use Glpi\Tests\GLPITestCase;
use Psr\Log\LogLevel;

class ResultTest extends GLPITestCase
{
    private function getResult(): Result
    {
        return new Result(
            [
                [1, 'first', null],
                [2, 'second', 4.2],
            ],
            [
                ['name' => 'id', 'table' => 'glpi_mocks'],
                ['name' => 'name', 'table' => 'glpi_mocks'],
                ['name' => 'value', 'table' => ''],
            ]
        );
    }

    public function testCounts(): void
    {
        $result = $this->getResult();

        $this->assertSame(2, $result->numRows());
        $this->assertSame(3, $result->numFields());
        $this->assertSame(
            [
                ['name' => 'id', 'table' => 'glpi_mocks'],
                ['name' => 'name', 'table' => 'glpi_mocks'],
                ['name' => 'value', 'table' => ''],
            ],
            $result->getColumns()
        );
    }

    public function testFetchModes(): void
    {
        $result = $this->getResult();

        $this->assertSame(['id' => 1, 'name' => 'first', 'value' => null], $result->fetchAssoc());
        $this->assertSame([2, 'second', 4.2], $result->fetchRow());
        $this->assertNull($result->fetchAssoc());
        $this->assertNull($result->fetchRow());
        $this->assertNull($result->fetchArray());
        $this->assertNull($result->fetchObject());

        $this->assertTrue($result->seek(0));
        $this->assertSame(
            [0 => 1, 1 => 'first', 2 => null, 'id' => 1, 'name' => 'first', 'value' => null],
            $result->fetchArray()
        );
        $this->assertEquals((object) ['id' => 2, 'name' => 'second', 'value' => 4.2], $result->fetchObject());
    }

    public function testFetchAllAssoc(): void
    {
        $result = $this->getResult();

        $result->fetchRow();
        $this->assertSame(
            [['id' => 2, 'name' => 'second', 'value' => 4.2]],
            $result->fetchAllAssoc()
        );
        $this->assertSame([], $result->fetchAllAssoc());
    }

    public function testSeek(): void
    {
        $result = $this->getResult();

        $this->assertTrue($result->seek(1));
        $this->assertSame(2, $result->fetchAssoc()['id']);

        $this->assertFalse($result->seek(2));
        $this->assertFalse($result->seek(-1));
        $this->assertNull($result->fetchAssoc());
    }

    public function testDuplicatedColumnNames(): void
    {
        $result = new Result(
            [[1, 2]],
            [
                ['name' => 'id', 'table' => 'glpi_a'],
                ['name' => 'id', 'table' => 'glpi_b'],
            ]
        );

        $this->assertSame(['id' => 2], $result->fetchAssoc());
        $result->seek(0);
        $this->assertSame([1, 2], $result->fetchRow());
    }

    public function testFree(): void
    {
        $result = $this->getResult();
        $result->free();

        $this->assertSame(0, $result->numRows());
        $this->assertNull($result->fetchAssoc());
    }

    public function testDeprecatedMysqliApi(): void
    {
        $result = $this->getResult();

        $this->assertSame(2, $result->num_rows);
        $this->hasPhpLogRecordThatContains('`Glpi\DBAL\Result::num_rows` is deprecated', LogLevel::INFO);

        $this->assertSame(3, $result->field_count);
        $this->hasPhpLogRecordThatContains('`Glpi\DBAL\Result::field_count` is deprecated', LogLevel::INFO);

        $this->assertSame(['id' => 1, 'name' => 'first', 'value' => null], $result->fetch_assoc());
        $this->hasPhpLogRecordThatContains('`Glpi\DBAL\Result::fetch_assoc()` is deprecated', LogLevel::INFO);

        $this->assertTrue($result->data_seek(1));
        $this->hasPhpLogRecordThatContains('`Glpi\DBAL\Result::data_seek()` is deprecated', LogLevel::INFO);

        $this->assertSame([2, 'second', 4.2], $result->fetch_row());
        $this->hasPhpLogRecordThatContains('`Glpi\DBAL\Result::fetch_row()` is deprecated', LogLevel::INFO);

        $this->assertEquals(
            [(object) ['name' => 'id', 'table' => 'glpi_mocks'], (object) ['name' => 'name', 'table' => 'glpi_mocks'], (object) ['name' => 'value', 'table' => '']],
            $result->fetch_fields()
        );
        $this->hasPhpLogRecordThatContains('`Glpi\DBAL\Result::fetch_fields()` is deprecated', LogLevel::INFO);
    }
}
