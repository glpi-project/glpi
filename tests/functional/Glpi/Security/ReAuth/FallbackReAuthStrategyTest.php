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

namespace tests\units\Glpi\Security\ReAuth;

use Glpi\Security\ReAuth\FallbackReAuthStrategy;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use User;

#[Group('reauth')]
class FallbackReAuthStrategyTest extends DbTestCase
{
    public static function usersProvider(): iterable
    {
        // [use_test_user]
        yield 'known user'   => [true];
        yield 'unknown user' => [false];
    }

    /**
     * The fallback strategy is always available: it is the last-resort strategy so a user is
     * never left without a way to pass the re-authentication step, even for an unknown user id.
     */
    #[DataProvider('usersProvider')]
    public function testIsAvailableIsAlwaysTrue(bool $use_test_user): void
    {
        // --- arrange ---
        $strategy = new FallbackReAuthStrategy();
        $users_id = $use_test_user ? getItemByTypeName(User::class, TU_USER, true) : 999999;

        // --- act + assert ---
        $this->assertTrue($strategy->isAvailable($users_id));
    }

    public static function verifyProvider(): iterable
    {
        // [use_test_user, user_input]
        yield 'known user, empty input'   => [true, ''];
        yield 'known user, any input'     => [true, 'whatever'];
        yield 'unknown user, empty input' => [false, ''];
        yield 'unknown user, any input'   => [false, 'whatever'];
    }

    /** verify() always succeeds: the fallback only asks for a confirmation, it checks nothing. */
    #[DataProvider('verifyProvider')]
    public function testVerifyAlwaysSucceeds(bool $use_test_user, string $user_input): void
    {
        // --- arrange ---
        $strategy = new FallbackReAuthStrategy();
        $users_id = $use_test_user ? getItemByTypeName(User::class, TU_USER, true) : 999999;

        // --- act + assert ---
        $this->assertTrue($strategy->verify($users_id, $user_input));
    }

    /** Test $strategy->getPromptTemplate(), $strategy->getPriority() & $strategy->getLabel() */
    public function testMetadata(): void
    {
        // --- arrange ---
        $strategy = new FallbackReAuthStrategy();

        // --- act + assert ---
        $this->assertSame('pages/reauth/fallback_form.html.twig', $strategy->getPromptTemplate());
        // Lowest priority: the fallback is only picked when no stronger strategy is available.
        $this->assertSame(0, $strategy->getPriority());
        $this->assertNotEmpty($strategy->getLabel());
    }
}
