<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Session\SessionInfo;
use InvalidArgumentException;
use Profile;
use RuntimeException;

final class TilesManager
{
    /**
     * @return array<TileInterface&CommonDBTM>
     */
    public function getTileTypes(): array
    {
        return [
            new GlpiPageTile(),
            new ExternalPageTile(),
            new FormTile(),
        ];
    }

    /** @return array<TileInterface&CommonDBTM> */
    public function getVisibleTilesForSession(SessionInfo $session_info): array
    {
        // First, try to load tiles from the current profile
        $current_profile = Profile::getById($session_info->getProfileId());
        $tiles = $this->getTilesForItem($current_profile);

        // If no tiles are found in the profile, look for tiles in the current
        // entity and its parents (until we reach the root entity).
        if (empty($tiles)) {
            $entity = Entity::getById($session_info->getCurrentEntityId());
            $tiles = $this->getTilesForEntityRecursive($entity);
        }

        // Remove unavailables tiles
        $available_forms = array_filter(
            $tiles,
            fn(TileInterface $tile): bool => $tile->isAvailable($session_info),
        );

        return array_values($available_forms);
    }

    /** @return array<TileInterface&CommonDBTM> */
    public function getTilesForEntityRecursive(Entity $entity): array
    {
        $tiles = $this->getTilesForItem($entity);

        // Stop recursion when a tile is found or if we reached the root entity.
        if (!empty($tiles) || $entity->getID() === 0) {
            return $tiles;
        }

        $parent_entity = Entity::getById($entity->fields['entities_id']);
        return $this->getTilesForEntityRecursive($parent_entity);
    }

    /** @return array<TileInterface&CommonDBTM> */
    public function getTilesForItem(
        CommonDBTM&LinkableToTilesInterface $item
    ): array {
        // Load tiles for the given profile
        $item_tiles = (new Item_Tile())->find([
            'itemtype_item' => $item::class,
            'items_id_item' => $item->getID(),
        ], ['rank']);

        $tiles = [];
        foreach ($item_tiles as $row) {
            // Validate tile itemtype
            $itemtype = $row['itemtype_tile'];
            /** @var CommonDBTM $tile */
            $tile = getItemForItemtype($itemtype);
            if (!($tile instanceof TileInterface)) {
                continue;
            }

            // Try to load tile from database
            if (!$tile->getFromDb($row['items_id_tile'])) {
                continue;
            }

            // Add the tile to the list
            $tiles[] = $tile;
        }

        return $tiles;
    }

    public function addTile(
        CommonDBTM&LinkableToTilesInterface $item,
        string $tile_class,
        array $params
    ): int {
        if (
            !$item->acceptTiles()
            || !\is_a($tile_class, CommonDBTM::class, true)
            || !\is_a($tile_class, TileInterface::class, true)
        ) {
            throw new InvalidArgumentException();
        }

        $tile = new $tile_class();
        $id = $tile->add($params);
        if (!$id) {
            throw new RuntimeException("Failed to create tile");
        }

        $item_tile = new Item_Tile();
        $id = $item_tile->add([
            'items_id_item' => $item->getID(),
            'itemtype_item' => $item::class,
            'items_id_tile' => $id,
            'itemtype_tile' => $tile_class,
            'rank'          => $this->getMaxUsedRankForItem($item) + 1,
        ]);
        if (!$id) {
            throw new RuntimeException("Failed to link tile to item");
        }

        return $id;
    }

    public function getItemTileForTile(TileInterface $tile): Item_Tile
    {
        $item_tile = new Item_Tile();
        $get_by_crit_success = $item_tile->getFromDBByCrit([
            'itemtype_tile' => $tile::class,
            'items_id_tile' => $tile->getDatabaseId(),
        ]);

        if (!$get_by_crit_success) {
            throw new RuntimeException("Missing Item_Tile data");
        }

        return $item_tile;
    }

    /**
     * @param int[] $order Ids of the Item_Tile entries, sorted into the desired ranks
     */
    public function setOrderForItem(
        CommonDBTM&LinkableToTilesInterface $item,
        array $order
    ): void {
        // Increase the original ranks to avoid unicity conflicts when setting
        // the new ranks.
        $max_rank = $this->getMaxUsedRankForItem($item);
        $items_tiles = (new Item_Tile())->find([
            'itemtype_item' => $item::class,
            'items_id_item' => $item->getID(),
        ]);
        $items_tiles_ids = array_column($items_tiles, 'id');
        foreach ($items_tiles_ids as $i => $id) {
            $item_tile = new Item_Tile();
            $item_tile->update([
                'id' => $id,
                'rank' => $i + ++$max_rank,
            ]);
        }

        // Set new ranks
        foreach (array_values($order) as $rank => $id) {
            // Find the associated Profile_Tile
            $item_tile = new Item_Tile();
            $item_tile->update([
                'id' => $id,
                'rank' => $rank,
            ]);
        }
    }

    public function deleteTile(CommonDBTM&TileInterface $tile): void
    {
        // First, find and delete the relevant Profile_Tile row
        $item_tile = new Item_Tile();
        $fields = [
            'itemtype_tile' => $tile::class,
            'items_id_tile' => $tile->getDatabaseId(),
        ];
        if (!$item_tile->getFromDBByCrit($fields)) {
            throw new RuntimeException("Failed to delete, missing Item_Tile data");
        }
        $id = $item_tile->getID();
        if (!$item_tile->delete(['id' => $id])) {
            throw new RuntimeException("Failed to delete item tile ($id)");
        }

        // Then delete the tile itself
        $items_id_tile = $tile->getDatabaseId();
        if (!$tile->delete(['id' => $items_id_tile])) {
            throw new RuntimeException("Failed to delete tile ($items_id_tile)");
        }
    }

    public function showConfigFormForItem(
        CommonDBTM&LinkableToTilesInterface $item
    ): void {
        // Render content
        $twig = TemplateRenderer::getInstance();
        $twig->display('pages/admin/helpdesk_home_config.html.twig', [
            'tiles_manager' => $this,
            'tiles'         => $this->getTilesForItem($item),
            'itemtype_item' => $item::class,
            'items_id_item' => $item->getID(),
            'editable'      => $item::canUpdate() && $item->canUpdateItem(),
            'info_text'     => $item->getTilesConfigInformationText(),
        ]);
    }

    public function copyTilesFromParentEntity(Entity $entity): void
    {
        $parent_entity = Entity::getById($entity->fields['entities_id']);
        $tiles = $this->getTilesForEntityRecursive($parent_entity);
        foreach ($tiles as $tile) {
            unset($tile->fields['id']);
            $this->addTile($entity, $tile::class, $tile->fields);
        }
    }

    private function getMaxUsedRankForItem(
        CommonDBTM&LinkableToTilesInterface $item
    ): int {
        /** @var \DBmysql $DB */
        global $DB;

        $rank = $DB->request([
            'SELECT' => ['MAX' => "rank AS max_rank"],
            'FROM'   => Item_Tile::getTable(),
            'WHERE'  => [
                'itemtype_item' => $item::class,
                'items_id_item' => $item->getID(),
            ],
        ])->current();

        return $rank['max_rank'] ?? 0;
    }
}
