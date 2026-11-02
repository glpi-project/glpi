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

use Override;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fallback re-authentication strategy.
 *
 * Always available and always succeeds: it only displays a confirmation
 * message in the prompt. It exists so that a user who has no other strategy
 * available (e.g. no local password and no TOTP) is never left without a way
 * to pass the re-authentication step.
 *
 * As it provides no real identity check, it has the lowest priority and is
 * only selected when no stronger strategy is available.
 */
final class FallbackReAuthStrategy extends InPlaceReAuthStrategy
{
    #[Override]
    public function verify(int $users_id, Request $request): bool
    {
        return true;
    }

    #[Override]
    public function isAvailable(int $users_id, int $entities_id = 0): bool
    {
        return true;
    }

    #[Override]
    public function getLabel(): string
    {
        return __('Confirmation');
    }

    #[Override]
    public function getPromptTemplate(): string
    {
        return 'pages/reauth/fallback_form.html.twig';
    }

    #[Override]
    public function getPriority(): int
    {
        return 0;
    }
}
