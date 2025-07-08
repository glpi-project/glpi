<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\FuzzyMatcher;

use Glpi\FuzzyMatcher\FuzzyMatcher;
use Glpi\FuzzyMatcher\PartialMatchStrategy;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class FuzzyMatcherTest extends GLPITestCase
{
    public static function partialMatchStrategyProvider(): iterable
    {
        $subject = "Carrot, apple, cucumber and pineapple";
        yield [$subject, "apple", true];
        yield [$subject, "Apple", true];
        yield [$subject, "apple pineapple", true];
        yield [$subject, "cp", false]; // Too short to trigger full fuzzy match
        yield [$subject, "car", true];
        yield [$subject, "Red carrot", false];
        yield [$subject, "Carrot and cucumber", true];
        yield [$subject, "Carrot and oranges", false];
        yield [$subject, "apzple", true]; // Small typo is OK
        yield [$subject, "apzywple", false]; // Too much wrong chars is NOT OK
    }

    #[DataProvider('partialMatchStrategyProvider')]
    public function testPartialMatchStrategy(
        string $subject,
        string $filter,
        bool $expected,
    ): void {
        $matcher = new FuzzyMatcher(new PartialMatchStrategy());
        $match = $matcher->match($subject, $filter);
        $this->assertSame($expected, $match);
    }
}
