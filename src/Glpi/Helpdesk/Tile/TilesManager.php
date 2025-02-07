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
use Glpi\Session\SessionInfo;
use InvalidArgumentException;
use RuntimeException;
use Profile;

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

    /** @return TileInterface[] */
    public function getTiles(
        SessionInfo $session_info,
        bool $check_availability = true
    ): array {
        // Load tiles for the given profile
        $profile_tiles = (new Profile_Tile())->find([
            'profiles_id' => $session_info->getProfileId(),
        ], ['rank']);

        $tiles = [];
        foreach ($profile_tiles as $row) {
            // Validate tile itemtype
            $itemtype = $row['itemtype'];
            $tile = getItemForItemtype($itemtype);
            if (!($tile instanceof TileInterface)) {
                continue;
            }

            // Try to load tile from database
            $tile = new $itemtype();
            if (!$tile->getFromDb($row['items_id'])) {
                continue;
            }

            // Make sure the tile is valid for the given session and entity details
            if ($check_availability && !$tile->isAvailable($session_info)) {
                continue;
            }

            // The tile is valid, add it to the list
            $tiles[] = $tile;
        }

        return $tiles;
    }

    public function addTile(
        Profile $profile,
        string $tile_class,
        array $params
    ): int {
        if ($profile->fields['interface'] !== 'helpdesk') {
            throw new InvalidArgumentException("Only helpdesk profiles can have tiles");
        }

        $tile = new $tile_class();
        $id = $tile->add($params);
        if (!$id) {
            throw new RuntimeException("Failed to create tile");
        }

        $profile_tile = new Profile_Tile();
        $id = $profile_tile->add([
            'profiles_id' => $profile->getID(),
            'items_id'    => $id,
            'itemtype'    => $tile_class,
            'rank'        => countElementsInTable(Profile_Tile::getTable(), [
                'profiles_id' => $profile->getID(),
            ]),
        ]);
        if (!$id) {
            throw new RuntimeException("Failed to link tile to profile");
        }

        return $id;
    }

    public function getProfileTileForTile(TileInterface $tile): Profile_Tile
    {
        $profile_tile = new Profile_Tile();
        $get_by_crit_success = $profile_tile->getFromDBByCrit([
            'itemtype' => $tile::class,
            'items_id' => $tile->getDatabaseId(),
        ]);

        if (!$get_by_crit_success) {
            throw new RuntimeException("Missing Profile_Tile data");
        }

        return $profile_tile;
    }

    /**
     * @param int[] $order Ids of the Profile_Tile entries, sorted into the desired ranks
     */
    public function setOrderForProfile(Profile $profile, array $order): void
    {
        // Increase the original ranks to avoid unicity conflicts when setting
        // the new ranks.
        $max_rank = $this->getMaxUsedRankForProfile($profile);
        $profile_tiles = (new Profile_Tile())->find([
            'profiles_id' => $profile->getID()
        ]);
        $profile_tiles_ids = array_column($profile_tiles, 'id');
        foreach ($profile_tiles_ids as $i => $id) {
            $profile_tile = new Profile_Tile();
            $profile_tile->update([
                'id' => $id,
                'rank' => $i + ++$max_rank,
            ]);
        }

        // Set new ranks
        foreach (array_values($order) as $rank => $id) {
            // Find the associated Profile_Tile
            $profile_tile = new Profile_Tile();
            $profile_tile->update([
                'id' => $id,
                'rank' => $rank,
            ]);
        }
    }

    public function deleteTile(CommonDBTM&TileInterface $tile): void
    {
        // First, find and delete the relevant Profile_Tile row
        $profile_tiles = (new Profile_Tile())->find([
            'items_id' => $tile->getDatabaseId(),
            'itemtype' => $tile::class,
        ]);
        foreach ($profile_tiles as $profile_tile_row) {
            $id = $profile_tile_row['id'];
            $delete = (new Profile_Tile())->delete(['id' => $id]);
            if (!$delete) {
                throw new RuntimeException("Failed to delete profile tile ($id)");
            }
        }

        // Then delete the tile itself
        $id =  $tile->getDatabaseId();
        $delete = $tile->delete(['id' => $id]);
        if (!$delete) {
            throw new RuntimeException("Failed to delete tile ($id)");
        }
    }

    private function getMaxUsedRankForProfile(Profile $profile): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        $rank = $DB->request([
            'SELECT' => ['MAX' => "rank AS max_rank"],
            'FROM'   => Profile_Tile::getTable(),
            'WHERE'  => ['profiles_id' => $profile->getID()],
        ])->current();

        return $rank['max_rank'];
    }
}
