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

use Glpi\DBAL\QueryElementInterface;
use Glpi\DBAL\QueryExpression;
use Glpi\Tests\GLPITestCase;
use RuntimeException;

class QueryExpressionTest extends GLPITestCase
{
    public function testASimpleExpressionBindsNothing(): void
    {
        $expression = new QueryExpression('1 = 1');

        $this->assertSame('1 = 1', $expression->getValue());
        $this->assertSame('1 = 1', (string) $expression);
        $this->assertSame([], $expression->getParams());
    }

    public function testTheAliasIsQuoted(): void
    {
        $this->assertSame('1 = 1 AS `my_alias`', (string) new QueryExpression('1 = 1', 'my_alias'));
    }

    public function testValuesAreCarriedAlong(): void
    {
        $expression = new QueryExpression('a = ? AND b = ?', values: [1, 'two']);

        $this->assertSame('a = ? AND b = ?', (string) $expression);
        $this->assertSame([1, 'two'], $expression->getParams());
    }

    public function testWrappingAnExpressionMergesItsParams(): void
    {
        $inner = new QueryExpression('a = ?', values: [1]);
        $outer = new QueryExpression($inner, null, [2]);

        $this->assertSame('a = ?', (string) $outer);
        // the wrapped expression's own values come first, then the ones given to the wrapper
        $this->assertSame([1, 2], $outer->getParams());
    }

    public function testAnEmptyExpressionIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot build an empty expression');
        new QueryExpression('');
    }

    public function testItIsAQueryElement(): void
    {
        $this->assertInstanceOf(QueryElementInterface::class, new QueryExpression('1'));
    }

    /**
     * `Domain::getEntitiesCriteria()` clones a shared expression and rebinds each copy, so
     * `setParams()` has to stay a mutator and `clone` has to yield an independent object.
     */
    public function testAClonedExpressionCanBeRebound(): void
    {
        $expression = new QueryExpression('DATEDIFF(CURDATE(), `date_expiration`) > ?');

        $delay = (clone $expression)->setParams([7]);
        $zero  = (clone $expression)->setParams([0]);

        $this->assertSame([7], $delay->getParams());
        $this->assertSame([0], $zero->getParams());
        $this->assertSame([], $expression->getParams());
        $this->assertSame((string) $expression, (string) $delay);
    }
}
