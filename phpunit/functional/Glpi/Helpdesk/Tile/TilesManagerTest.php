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

namespace tests\units\Glpi\Form\Helpdesk\TilesManagerTest;

use Glpi\Helpdesk\Tile\TileInterface;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\UI\IllustrationManager;
use GLPITestCase;

final class TilesManagerTest extends GLPITestCase
{
    private function getManager(): TilesManager
    {
        return new TilesManager();
    }

    public function testDefaultTiles(): void
    {
        // Act: get the default configured tiles and load valid illustrations names
        $tiles = $this->getManager()->getTiles();
        $illustration_manager = new IllustrationManager();
        $valid_illustrations = $illustration_manager->getAllIllustrationsNames();

        // Assert: there should be at least one tile and each tile should have a
        // valid title, description, illustration and link
        $this->assertNotEmpty($tiles);
        foreach ($tiles as $tile) {
            $this->assertInstanceOf(TileInterface::class, $tile);
            $this->assertNotEmpty($tile->getTitle());
            $this->assertNotEmpty($tile->getDescription());
            $this->assertContains($tile->getIllustration(), $valid_illustrations);

            // TODO: once all our links are real routes, we could call the router
            // here to check that the link is valid.
            $this->assertNotEmpty($tile->getLink());
        }
    }
}
