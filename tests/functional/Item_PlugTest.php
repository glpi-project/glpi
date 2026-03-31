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

namespace tests\units;

use Computer;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasPlugCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Item_Plug;
use Plug;
use Toolbox;

class Item_PlugTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasPlugCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['plug_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Item_Plug$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasPlugCapacity::class)]);

        foreach ($CFG_GLPI['plug_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Item_Plug::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testPlugsIdAndNumberPlugsValidation()
    {
        $plug = $this->createItem(Plug::class, ['name' => 'Test plug']);
        $computer = $this->createItem(Computer::class, ['name' => 'Test computer', 'entities_id' => 0]);

        $item_plug = new Item_Plug();

        $item_plug->getEmpty();
        $this->assertFalse($item_plug->add([
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'number_plugs' => 1,
        ]));
        $this->hasSessionMessages(ERROR, ['A plug must be selected']);

        $item_plug->getEmpty();
        $this->assertFalse($item_plug->add([
            'plugs_id' => 0,
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'number_plugs' => 1,
        ]));
        $this->hasSessionMessages(ERROR, ['A plug must be selected']);

        $item_plug->getEmpty();
        $this->assertFalse($item_plug->add([
            'plugs_id' => -1,
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'number_plugs' => 1,
        ]));
        $this->hasSessionMessages(ERROR, ['A plug must be selected']);

        $item_plug->getEmpty();
        $this->assertFalse($item_plug->add([
            'plugs_id' => $plug->getID(),
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'number_plugs' => '',
        ]));
        $this->hasSessionMessages(ERROR, ['A number of plugs is required']);

        $item_plug->getEmpty();
        $this->assertGreaterThan(0, $item_plug->add([
            'plugs_id' => $plug->getID(),
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'number_plugs' => 1,
        ]));
    }
}
