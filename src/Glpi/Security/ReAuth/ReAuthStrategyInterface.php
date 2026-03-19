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

declare(strict_types=1);

namespace Glpi\Security\ReAuth;

interface ReAuthStrategyInterface
{
    /**
     * Verify the user input against this re-authentication strategy.
     *
     * @param int    $users_id   The user ID
     * @param string $user_input The user input (password, TOTP code, etc.)
     * @return bool True if verification succeeds, false otherwise
     */
    public function verify(int $users_id, string $user_input): bool;

    /**
     * Check if this strategy is available for the given user.
     *
     * @param int $users_id The user ID
     * @return bool True if the strategy can be used for this user, false otherwise
     *
     * @todo Will consider entity context in the future
     */
    public function isAvailable(int $users_id): bool;

    /**
     * Get a human-readable label for this strategy (used in UI).
     *
     */
    public function getLabel(): string;

    /**
     * Get the template name for the prompt/form (pages/reauth/xxx.html.twig).
     *
     */
    public function getPromptTemplate(): string;

    /**
     * Get the priority of this strategy.
     *
     * When multiple strategies are available for a user, the one with the HIGHEST priority is selected.
     * Example: TOTP (priority 100) is preferred over Password (priority 50).
     *
     * @return int A positive integer. Higher values = higher priority
     */
    public function getPriority(): int;
}
