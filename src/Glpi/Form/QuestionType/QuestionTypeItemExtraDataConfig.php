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

class QuestionTypeItemExtraDataConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded name used for serialization
    public const ITEMTYPE             = "itemtype";
    public const ROOT_ITEMS_ID        = "root_items_id";
    public const SUBTREE_DEPTH        = "subtree_depth";
    public const SELECTABLE_TREE_ROOT = "selectable_tree_root";

    public function __construct(
        private ?string $itemtype          = null,
        private int $root_items_id         = 0,
        private int $subtree_depth         = 0,
        private bool $selectable_tree_root = false,
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            itemtype            : $data[self::ITEMTYPE] ?? null,
            root_items_id       : $data[self::ROOT_ITEMS_ID] ?? 0,
            subtree_depth       : $data[self::SUBTREE_DEPTH] ?? 0,
            selectable_tree_root: $data[self::SELECTABLE_TREE_ROOT] ?? false,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::ITEMTYPE             => $this->itemtype,
            self::ROOT_ITEMS_ID        => $this->root_items_id,
            self::SUBTREE_DEPTH        => $this->subtree_depth,
            self::SELECTABLE_TREE_ROOT => $this->selectable_tree_root,
        ];
    }

    public function getItemtype(): ?string
    {
        return $this->itemtype;
    }

    public function getRootItemsId(): int
    {
        return $this->root_items_id;
    }

    public function getSubtreeDepth(): int
    {
        return $this->subtree_depth;
    }

    public function isSelectableTreeRoot(): bool
    {
        return $this->selectable_tree_root;
    }

}
