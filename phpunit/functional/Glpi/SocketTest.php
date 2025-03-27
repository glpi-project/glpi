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

namespace tests\units\Glpi;

use DbTestCase;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasSocketCapacity;
use Glpi\Features\Clonable;
use Glpi\Socket;
use Toolbox;

class SocketTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasSocketCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['socket_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Glpi\\Socket$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasSocketCapacity::class)]);

        foreach ($CFG_GLPI['socket_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Socket::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testCreateNoItem()
    {
        $socket = new \Glpi\Socket();
        $sockets_id = $socket->add([
            'name' => __FUNCTION__,
            'items_id' => '',
            'itemtype' => 'Computer',
        ]);
        $this->assertTrue($socket->getFromDB($sockets_id));
        $this->assertSame(null, $socket->fields['itemtype']);
        $this->assertSame(0, $socket->fields['items_id']);
    }
}
