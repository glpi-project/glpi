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

use AbstractRightsDropdown;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Export\Context\JsonFieldReferencingDatabaseIdsInterface;
use Glpi\Form\Export\Context\ReadonlyDatabaseMapper;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Group;
use Override;
use Profile;
use User;

final class AllowListConfig implements
    JsonFieldInterface,
    JsonFieldReferencingDatabaseIdsInterface
{
    public function __construct(
        private array $user_ids = [],
        private array $group_ids = [],
        private array $profile_ids = [],
    ) {
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
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

    #[Override]
    public function getJsonDeserializeWithoutDatabaseIdsRequirements(): array
    {
        $requirements = [];
        $to_map = [
            User::class => $this->user_ids,
            Group::class => $this->group_ids,
            Profile::class => $this->profile_ids,
        ];

        foreach ($to_map as $itemtype => $ids) {
            foreach ($ids as $id) {
                // Skip special values
                if (in_array($id, $this->getSpecialValues())) {
                    continue;
                }

                $item = $itemtype::getById($id);
                if (!$item) {
                    continue;
                }

                $requirements[] = new DataRequirementSpecification(
                    itemtype: $itemtype,
                    name: $item->fields['name'],
                );
            }
        }

        return $requirements;
    }

    #[Override]
    public static function jsonDeserializeWithoutDatabaseIds(
        ReadonlyDatabaseMapper $mapper,
        array $data,
    ): self {
        $config_with_names = self::jsonDeserialize($data);
        $config_with_ids = new self(
            user_ids: $config_with_names->convertNamesToIds(
                User::class,
                $config_with_names->getUserIds(),
                $mapper,
            ),
            group_ids: $config_with_names->convertNamesToIds(
                Group::class,
                $config_with_names->getGroupIds(),
                $mapper,
            ),
            profile_ids: $config_with_names->convertNamesToIds(
                Profile::class,
                $config_with_names->getProfileIds(),
                $mapper,
            ),
        );

        return $config_with_ids;
    }

    #[Override]
    public function jsonSerializeWithoutDatabaseIds(): array
    {
        $data = $this->jsonSerialize();

        $data['user_ids'] = $this->convertIdsToNames(
            User::class,
            $data['user_ids']
        );
        $data['group_ids'] = $this->convertIdsToNames(
            Group::class,
            $data['group_ids']
        );
        $data['profile_ids'] = $this->convertIdsToNames(
            Profile::class,
            $data['profile_ids']
        );

        return $data;
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

    private function getSpecialValues(): array
    {
        return [AbstractRightsDropdown::ALL_USERS];
    }

    private function convertIdsToNames(string $itemtype, array $ids): array
    {
        $names = [];
        foreach ($ids as $id) {
            // Special value that must not be converted
            if (in_array($id, $this->getSpecialValues())) {
                $names[] = $id;
                continue;
            }

            // Load item
            $item = $itemtype::getById($id);
            if (!$item) {
                continue;
            }

            $names[] = $item->fields['name'];
        }

        return $names;
    }

    private function convertNamesToIds(
        string $itemtype,
        array $names,
        ReadonlyDatabaseMapper $mapper,
    ): array {
        $ids = [];
        foreach ($names as $name) {
            // Special value that must not be converted
            if (in_array($name, $this->getSpecialValues())) {
                $ids[] = $name;
                continue;
            }

            // Get id from mapper
            $ids[] = $mapper->getItemId($itemtype, $name);
        }

        return $ids;
    }
}
