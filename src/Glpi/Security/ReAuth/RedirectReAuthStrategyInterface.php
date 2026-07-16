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

/**
 * Re-authentication strategy whose verification happens out-of-band, through a
 * browser redirect round-trip (e.g. OAuth/OIDC re-authentication against an
 * identity provider).
 *
 * Unlike the synchronous {@see ReAuthStrategyInterface} (password, TOTP), the
 * user does not type a secret verified in-process: they are redirected to an
 * external endpoint that, once the identity is confirmed, is responsible for
 * calling {@see ReAuthManager::authenticate()} and replaying the original
 * request.
 *
 * Because verification never goes through {@see ReAuthStrategyInterface::verify()}
 * for these strategies, implementations are expected to make that method
 * unreachable (e.g. throw a \LogicException).
 */
interface RedirectReAuthStrategyInterface extends ReAuthStrategyInterface
{
    /**
     * URL that starts the interactive re-authentication round-trip.
     *
     * The prompt page renders a link/button to this URL instead of an input
     * field. The target endpoint is responsible for enforcing the security
     * guarantees of the round-trip (anti-forgery state, identity binding, proof
     * of a fresh authentication) before granting re-authentication.
     *
     * @param int $users_id The currently authenticated user the round-trip must confirm.
     */
    public function getReauthUrl(int $users_id): string;
}
