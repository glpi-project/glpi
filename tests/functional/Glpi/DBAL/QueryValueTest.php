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

use Glpi\DBAL\QueryParam;
use Glpi\DBAL\QueryValue;
use Glpi\Exception\Database\QueryException;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class QueryValueTest extends GLPITestCase
{
    public static function valueProvider(): iterable
    {
        yield 'string' => ['a string'];
        yield 'empty string' => [''];
        yield 'int' => [42];
        yield 'zero' => [0];
        yield 'float' => [4.2];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
    }

    #[DataProvider('valueProvider')]
    public function testAValueRendersAsAPlaceholderAndBindsItself(mixed $value): void
    {
        $query_value = new QueryValue($value);

        $this->assertSame('?', $query_value->getValue());
        $this->assertSame('?', (string) $query_value);
        $this->assertSame([$value], $query_value->getParams());
    }

    public static function invalidValueProvider(): iterable
    {
        yield 'array' => [['a', 'b'], 'array'];
        yield 'object' => [new QueryParam(), 'Glpi\DBAL\QueryParam'];
    }

    #[DataProvider('invalidValueProvider')]
    public function testANonScalarValueIsRejected(mixed $value, string $type): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(sprintf('A query value must be a scalar, %s given', $type));
        new QueryValue($value);
    }

    /**
     * Both render as `?`, but a QueryValue binds its value whereas a QueryParam binds nothing:
     * they are not interchangeable.
     */
    public function testAQueryValueIsNotAQueryParam(): void
    {
        $this->assertSame((new QueryParam())->getValue(), (new QueryValue('foo'))->getValue());
        $this->assertSame(['foo'], (new QueryValue('foo'))->getParams());
        $this->assertFalse(method_exists(QueryParam::class, 'getParams'));
    }
}
