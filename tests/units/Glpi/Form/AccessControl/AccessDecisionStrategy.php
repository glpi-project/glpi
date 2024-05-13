<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace tests\units\Glpi\Form\AccessControl;

final class AccessDecisionStrategy extends \GLPITestCase
{
    public function getDecisionProvider(): iterable
    {
        // Test the "Unanimous" strategy
        yield 'Unanimous strategy with 3 "no" and 0 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Unanimous,
            'votes'    => [false, false, false],
            'expected' => false
        ];
        yield 'Unanimous strategy with 2 "no" and 1 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Unanimous,
            'votes'    => [true, false, false],
            'expected' => false
        ];
        yield 'Unanimous strategy with 1 "no" and 2 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Unanimous,
            'votes'    => [true, true, false],
            'expected' => false
        ];
        yield 'Unanimous strategy with 0 "no" and 3 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Unanimous,
            'votes'    => [true, true, true],
            'expected' => true
        ];

        // Test the "Affirmative" strategy
        yield 'Affirmative strategy with 3 "no" and 0 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Affirmative,
            'votes'    => [false, false, false],
            'expected' => false
        ];
        yield 'Affirmative strategy with 2 "no" and 1 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Affirmative,
            'votes'    => [true, false, false],
            'expected' => true
        ];
        yield 'Affirmative strategy with 1 "no" and 2 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Affirmative,
            'votes'    => [true, true, false],
            'expected' => true
        ];
        yield 'Affirmative strategy with 0 "no" and 3 "yes" votes' => [
            'strategy' => \Glpi\Form\AccessControl\AccessDecisionStrategy::Affirmative,
            'votes'    => [true, true, true],
            'expected' => true
        ];
    }

    /**
     * @dataProvider getDecisionProvider
     */
    public function testGetDecision(
        \Glpi\Form\AccessControl\AccessDecisionStrategy $strategy,
        array $votes,
        bool $expected
    ): void {
        $this->boolean($strategy->getDecision($votes))->isEqualTo($expected);
    }

    public function testGetLabel(): void
    {
        // Not much to test here, just make sure the code run without error
        // for all cases
        $stategies = \Glpi\Form\AccessControl\AccessDecisionStrategy::cases();
        foreach ($stategies as $strategy) {
            $this->string($strategy->getLabel())->isNotEmpty();
        }
    }

    public function testGetForDropdown(): void
    {
        $strategies = \Glpi\Form\AccessControl\AccessDecisionStrategy::getForDropdown();

        foreach ($strategies as $strategy => $label) {
            $this->string($label)->isNotEmpty();
            $strategy = \Glpi\Form\AccessControl\AccessDecisionStrategy::tryFrom(
                $strategy
            );
            $this->object($strategy)->isNotNull();
        }
    }
}
