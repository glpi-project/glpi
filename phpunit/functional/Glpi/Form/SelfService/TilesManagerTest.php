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

namespace tests\units\Glpi\Form\SelfService;

use Glpi\Form\SelfService\TileInterface;
use Glpi\Form\SelfService\TilesManager;
use GLPITestCase;

final class TilesManagerTest extends GLPITestCase
{
    private function getManager(): TilesManager
    {
        return new TilesManager();
    }

    public function testDefaultTiles(): void
    {
        // Act: get the default configured tiles
        $tiles = $this->getManager()->getTiles();

        // Assert: there should be at least one tile and each tile should have a
        // valid title, description, illustration and link
        $this->assertNotEmpty($tiles);
        foreach ($tiles as $tile) {
            $this->assertInstanceOf(TileInterface::class, $tile);
            $this->assertNotEmpty($tile->getTitle());
            $this->assertNotEmpty($tile->getDescription());

            // TODO: we could check that the illustration id is valid, but this
            // is too early as we don't have our own list of illustrations yet.
            $this->assertNotEmpty($tile->getIllustration());

            // TODO: once all our links are real routes, we could call the router
            // here to check that the link is valid.
            $this->assertNotEmpty($tile->getLink());
        }
    }
}
