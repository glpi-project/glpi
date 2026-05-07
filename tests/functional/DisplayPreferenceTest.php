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

use DisplayPreference;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;

/**
 * Tests of displaypreference.php.
 *
 *   - DisplayPreference::PERSONAL : manage own personal view
 *   - DisplayPreference::GENERAL : manage global / any user's view
 *
 * Rules applied in displaypreference.php:
 *   - target users_id !== current user  → GENERAL required
 *   - target users_id === current user  → PERSONAL or GENERAL required
 */
class DisplayPreferenceTest extends DbTestCase
{
    /**
     * Overrides the search_config right in the active session profile so we can simulate any combination.
     */
    private function setSearchConfigRight(int $right): void
    {
        $_SESSION['glpiactiveprofile'][DisplayPreference::$rightname] = $right;
    }

    /**
     *   if ($target !== own) → checkRight(GENERAL)
     *   else → checkRightsOr([PERSONAL, GENERAL])
     */
    private function checkAjaxAuthorization(int $target_users_id): void
    {
        DisplayPreference::checkAjaxAuthorization(['users_id' => $target_users_id]);
    }

    /**
     *   - right : the search_config
     *   - target : oww | global | other
     *   - expected_exception : null if the action is allowed, class name otherwise
     */
    public static function authorizationProvider(): iterable
    {
        // 1. User with only PERSONAL
        // Can manage own personal view only
        yield 'personal_own_prefs' => [
            'right'              => DisplayPreference::PERSONAL,
            'target'             => 'own',
            'expected_exception' => null,
        ];
        // Cannot touch global prefs
        yield 'personal_global_prefs' => [
            'right'              => DisplayPreference::PERSONAL,
            'target'             => 'global',
            'expected_exception' => AccessDeniedHttpException::class,
        ];
        // Cannot touch another users prefs
        yield 'personal_other_user_prefs' => [
            'right'              => DisplayPreference::PERSONAL,
            'target'             => 'other',
            'expected_exception' => AccessDeniedHttpException::class,
        ];

        // 2. User with only GENERAL
        // Can manage own, global, and any other users prefs
        yield 'general_own_prefs' => [
            'right'              => DisplayPreference::GENERAL,
            'target'             => 'own',
            'expected_exception' => null,
        ];
        yield 'general_global_prefs' => [
            'right'              => DisplayPreference::GENERAL,
            'target'             => 'global',
            'expected_exception' => null,
        ];
        yield 'general_other_user_prefs' => [
            'right'              => DisplayPreference::GENERAL,
            'target'             => 'other',
            'expected_exception' => null,
        ];

        // 3. User with both PERSONAL + GENERAL
        // all permissions
        yield 'both_own_prefs' => [
            'right'              => DisplayPreference::PERSONAL | DisplayPreference::GENERAL,
            'target'             => 'own',
            'expected_exception' => null,
        ];
        yield 'both_global_prefs' => [
            'right'              => DisplayPreference::PERSONAL | DisplayPreference::GENERAL,
            'target'             => 'global',
            'expected_exception' => null,
        ];

        // 4. User with no rights
        // Every target must be refused.
        yield 'none_own_prefs' => [
            'right'              => 0,
            'target'             => 'own',
            'expected_exception' => AccessDeniedHttpException::class,
        ];
        yield 'none_global_prefs' => [
            'right'              => 0,
            'target'             => 'global',
            'expected_exception' => AccessDeniedHttpException::class,
        ];
        yield 'none_other_user_prefs' => [
            'right'              => 0,
            'target'             => 'other',
            'expected_exception' => AccessDeniedHttpException::class,
        ];
    }

    #[DataProvider('authorizationProvider')]
    public function testAjaxAuthorization(
        int $right,
        string $target,
        ?string $expected_exception
    ): void {
        $this->login();
        $this->setSearchConfigRight($right);
        $login_id = (int) Session::getLoginUserID();

        $target_users_id = match ($target) {
            'own'    => $login_id, // own personal prefs
            'global' => 0, // global prefs (users_id = 0)
            'other'  => $login_id + 9999, // another user
        };

        if ($expected_exception !== null) {
            $this->expectException($expected_exception);
        }

        $this->checkAjaxAuthorization($target_users_id);
    }
}
