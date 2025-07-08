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

namespace Glpi\Form\QuestionType;

use Glpi\DBAL\JsonFieldInterface;
use Override;

final class QuestionTypeActorsDefaultValueConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded name used for serialization
    public const KEY_USERS_IDS     = "users_ids";
    public const KEY_GROUPS_IDS    = "groups_ids";
    public const KEY_SUPPLIERS_IDS = "suppliers_ids";

    public function __construct(
        private array $users_ids = [],
        private array $groups_ids = [],
        private array $suppliers_ids = [],
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            users_ids: $data[self::KEY_USERS_IDS] ?? [],
            groups_ids: $data[self::KEY_GROUPS_IDS] ?? [],
            suppliers_ids: $data[self::KEY_SUPPLIERS_IDS] ?? [],
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::KEY_USERS_IDS     => $this->users_ids,
            self::KEY_GROUPS_IDS    => $this->groups_ids,
            self::KEY_SUPPLIERS_IDS => $this->suppliers_ids,
        ];
    }

    public function getUsersIds(): array
    {
        return $this->users_ids;
    }

    public function getGroupsIds(): array
    {
        return $this->groups_ids;
    }

    public function getSuppliersIds(): array
    {
        return $this->suppliers_ids;
    }
}
