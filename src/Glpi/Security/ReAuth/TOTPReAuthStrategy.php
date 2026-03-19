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

use Glpi\Security\TOTPManager;

final class TOTPReAuthStrategy implements ReAuthStrategyInterface
{
    private TOTPManager $totp_manager;

    public function __construct()
    {
        $this->totp_manager = new TOTPManager();
    }

    #[\Override]
    public function verify(int $users_id, string $user_input): bool
    {
        return $this->totp_manager->verifyCodeForUser($user_input, $users_id);
    }

    #[\Override]
    public function isAvailable(int $users_id): bool
    {
        return $this->totp_manager->is2FAEnabled($users_id);
    }

    #[\Override]
    public function getLabel(): string
    {
        return __('Two-factor authentication');
    }

    #[\Override]
    public function getPromptTemplate(): string
    {
        return 'pages/reauth/totp_form.html.twig';
    }

    #[\Override]
    public function getPriority(): int
    {
        return 100;
    }
}
