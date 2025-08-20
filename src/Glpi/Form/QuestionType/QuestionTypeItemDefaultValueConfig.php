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

final class QuestionTypeItemDefaultValueConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded name used for serialization
    public const KEY_ITEMS_ID = "items_id";

    /**
     * @param null|int|string $items_id Must accept a string because the foreign key handler
     *                                  replaces the ID with the item name during serialization.
     */
    public function __construct(
        private int|string|null $items_id = null
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            items_id: $data[self::KEY_ITEMS_ID] ?? null,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::KEY_ITEMS_ID => $this->items_id,
        ];
    }

    public function getItemsId(): ?int
    {
        return $this->items_id;
    }
}
