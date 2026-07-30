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

namespace Glpi\Helpdesk\Tile;

use CommonDBTM;
use Entity;
use Profile;

/**
 * Right management for items implementing {@link TileInterface}.
 *
 * Tiles do not have their own dedicated right: they can be viewed, created,
 * updated, deleted or purged by anyone that is allowed to *update* the item
 * they are attached to (see {@link LinkableToTilesInterface}).
 */
trait TileRightTrait
{
    public static function canView(): bool
    {
        return self::canViewTiles();
    }

    public static function canCreate(): bool
    {
        return self::canManageTiles();
    }

    public static function canUpdate(): bool
    {
        return self::canManageTiles();
    }

    public static function canDelete(): bool
    {
        return self::canManageTiles();
    }

    public static function canPurge(): bool
    {
        return self::canManageTiles();
    }

    public function canViewItem(): bool
    {
        return $this->hasRightOnLinkedItem(READ);
    }

    public function canCreateItem(): bool
    {
        $holder = $this->getHolderFromInput();
        if ($holder === null) {
            return false;
        }

        return $holder->can($holder->getID(), UPDATE);
    }

    public function canUpdateItem(): bool
    {
        return $this->hasRightOnLinkedItem(UPDATE);
    }

    public function canDeleteItem(): bool
    {
        return $this->hasRightOnLinkedItem(UPDATE);
    }

    public function canPurgeItem(): bool
    {
        return $this->hasRightOnLinkedItem(UPDATE);
    }

    public function post_addItem()
    {
        parent::post_addItem();

        // Attach the tile to the holder captured from the creation input. This
        // makes the holder both the value validated by canCreateItem() and the
        // value that is actually persisted, so it can not be spoofed.
        $holder = $this->getHolderFromInput();
        if ($holder === null) {
            return;
        }

        $item_tile = new Item_Tile();
        $item_tile->add([
            'itemtype_item' => $holder::class,
            'items_id_item' => $holder->getID(),
            'itemtype_tile' => static::class,
            'items_id_tile' => $this->getID(),
            'rank'          => $this->getNextTileRank($holder),
        ]);
    }

    /**
     * Itemtypes that are allowed to hold tiles.
     *
     * @return array<class-string<CommonDBTM&LinkableToTilesInterface>>
     */
    private static function getTileHolderItemtypes(): array
    {
        return [
            Entity::class,
            Profile::class,
        ];
    }

    /**
     * Whether the current user can view at least one item that can hold tiles.
     *
     * Used as a coarse check for the global "view" right.
     */
    private static function canViewTiles(): bool
    {
        foreach (self::getTileHolderItemtypes() as $itemtype) {
            if ($itemtype::canView()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the current user can update at least one item that can hold
     * tiles.
     *
     * Used as a coarse check for the global rights and for actions that are not
     * yet tied to a specific tile (e.g. creation).
     */
    private static function canManageTiles(): bool
    {
        foreach (self::getTileHolderItemtypes() as $itemtype) {
            if ($itemtype::canUpdate()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the current user has the given right on the item this tile is
     * attached to. Viewing a tile follows the item's view right, while altering
     * it (create/update/delete/purge) follows the item's update right.
     */
    private function hasRightOnLinkedItem(int $right): bool
    {
        $item_tile = new Item_Tile();
        $found = $item_tile->getFromDBByCrit([
            'itemtype_tile' => static::class,
            'items_id_tile' => $this->getID(),
        ]);
        if (!$found) {
            return false;
        }

        $holder = $this->resolveHolder(
            $item_tile->fields['itemtype_item'],
            (int) $item_tile->fields['items_id_item'],
        );

        return $holder !== null && $holder->can($holder->getID(), $right);
    }

    /**
     * The tile holder described by the creation input, or null when none is
     * provided or it is invalid.
     *
     * @return (CommonDBTM&LinkableToTilesInterface)|null
     */
    private function getHolderFromInput(): ?CommonDBTM
    {
        $itemtype = $this->input['_itemtype_item'] ?? null;
        $items_id = $this->input['_items_id_item'] ?? null;
        if ($itemtype === null || $items_id === null) {
            return null;
        }

        return $this->resolveHolder((string) $itemtype, (int) $items_id);
    }

    /**
     * Load the given tile holder, or return null when it is invalid.
     *
     * @return (CommonDBTM&LinkableToTilesInterface)|null
     */
    private function resolveHolder(string $itemtype, int $items_id): ?CommonDBTM
    {
        $holder = getItemForItemtype($itemtype);
        if (
            !$holder instanceof CommonDBTM
            || !$holder instanceof LinkableToTilesInterface
            || !$holder->getFromDB($items_id)
        ) {
            return null;
        }

        return $holder;
    }

    /**
     * Next available rank for a tile attached to the given holder.
     */
    private function getNextTileRank(CommonDBTM&LinkableToTilesInterface $holder): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'SELECT' => ['MAX' => 'rank AS max_rank'],
            'FROM'   => Item_Tile::getTable(),
            'WHERE'  => [
                'itemtype_item' => $holder::class,
                'items_id_item' => $holder->getID(),
            ],
        ])->current();

        return ($result['max_rank'] ?? 0) + 1;
    }
}
