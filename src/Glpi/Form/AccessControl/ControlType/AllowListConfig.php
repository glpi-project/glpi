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

namespace Glpi\Form\AccessControl\ControlType;

use JsonConfigInterface;
use JsonSerializable;
use Override;

final class AllowListConfig implements JsonConfigInterface, JsonSerializable
{
    public function __construct(
        private array $user_ids = [],
        private array $group_ids = [],
        private array $profile_ids = [],
    ) {
    }

    #[Override]
    public static function createFromRawArray(array $data): self
    {
        return new self(
            user_ids   : $data['user_ids'] ?? [],
            group_ids  : $data['group_ids'] ?? [],
            profile_ids: $data['profile_ids'] ?? []
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'user_ids'    => $this->user_ids,
            'group_ids'   => $this->group_ids,
            'profile_ids' => $this->profile_ids,
        ];
    }

    public function getUserIds(): array
    {
        return $this->user_ids;
    }

    public function getGroupIds(): array
    {
        return $this->group_ids;
    }

    public function getProfileIds(): array
    {
        return $this->profile_ids;
    }
}
