<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Session\SessionInfo;
use InvalidArgumentException;
use RuntimeException;
use Profile;

final class TilesManager
{
    /** @return TileInterface[] */
    public function getTiles(SessionInfo $session_info): array
    {
        // Load tiles for the given profile
        $profile_tiles = (new Profile_Tile())->find([
            'profiles_id' => $session_info->getProfileId(),
        ]);

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
            if (!$tile->isValid($session_info)) {
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
    ): void {
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
        ]);
        if (!$id) {
            throw new RuntimeException("Failed to link tile to profile");
        }
    }
}
