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
use Override;

final class AllowListConfig implements JsonFieldInterface
{
    // Serialized keys names
    public const KEY_USER_IDS = 'user_ids';
    public const KEY_GROUP_IDS = 'group_ids';
    public const KEY_PROFILE_IDS = 'profile_ids';

    public function __construct(
        private array $user_ids = [],
        private array $group_ids = [],
        private array $profile_ids = [],
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            user_ids   : $data[self::KEY_USER_IDS] ?? [],
            group_ids  : $data[self::KEY_GROUP_IDS] ?? [],
            profile_ids: $data[self::KEY_PROFILE_IDS] ?? []
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::KEY_USER_IDS    => $this->user_ids,
            self::KEY_GROUP_IDS   => $this->group_ids,
            self::KEY_PROFILE_IDS => $this->profile_ids,
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
