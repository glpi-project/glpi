<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Form\AccessControl\ControlType;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\DBAL\PrepareForCloneInterface;
use Override;
use Toolbox;

final class DirectAccessConfig implements JsonFieldInterface, PrepareForCloneInterface
{
    public function __construct(
        private string $token = "",
        private bool $allow_unauthenticated = false,
    ) {
        if (empty($this->token)) {
            $this->token = $this->generateToken();
        }
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            token: $data['token'] ?? "",
            allow_unauthenticated: $data['allow_unauthenticated'] ?? false,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
            'allow_unauthenticated' => $this->allow_unauthenticated,
        ];
    }

    #[Override]
    public function prepareInputForClone(array $data): array
    {
        // Make sure the token is always unique
        $data['token'] = $this->generateToken();
        return $data;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function allowUnauthenticated(): bool
    {
        return $this->allow_unauthenticated;
    }

    private function generateToken(): string
    {
        return Toolbox::getRandomString(40);
    }
}
