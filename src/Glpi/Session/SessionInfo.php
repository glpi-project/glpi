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

namespace Glpi\Session;

use Profile;

final class SessionInfo
{
    private ?Profile $profile = null;
    private ?array $rights = null;

    public function __construct(
        private int $user_id = 0,
        private array $group_ids = [],
        private int $profile_id = 0,
        /** @var int[] $entities_ids */
        private array $active_entities_ids = [],
        private int $current_entity_id = 0,
    ) {}

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getGroupIds(): array
    {
        return $this->group_ids;
    }

    public function getProfileId(): int
    {
        return $this->profile_id;
    }

    /** @return int[] */
    public function getActiveEntitiesIds(): array
    {
        return $this->active_entities_ids;
    }

    public function getCurrentEntityId(): int
    {
        return $this->current_entity_id;
    }

    public function hasRight(string $right, int $action): bool
    {
        $rights = $this->getRights();

        if (!isset($rights[$right])) {
            return false;
        }

        return ((int) $rights[$right] & $action) > 0;
    }

    public function hasAnyRights(string $right, array $actions): bool
    {
        foreach ($actions as $action) {
            if ($this->hasRight($right, $action)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRights(string $right, array $actions): bool
    {
        foreach ($actions as $action) {
            if (!$this->hasRight($right, $action)) {
                return false;
            }
        }

        return true;
    }

    private function getProfile(): ?Profile
    {
        if ($this->profile_id === 0) {
            return null;
        }

        if ($this->profile === null) {
            $profile = Profile::getById($this->profile_id);
            if (!$profile instanceof Profile) {
                return null;
            }

            $this->profile = $profile;
        }

        return $this->profile;
    }

    private function getRights(): array
    {
        if ($this->rights === null) {
            $profile = $this->getProfile();
            $profile->cleanProfile();
            $this->rights = $profile->fields;
        }

        return $this->rights;
    }
}
