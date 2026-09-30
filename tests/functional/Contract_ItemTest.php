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

use Contract_Item;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasContractsCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Toolbox;

class Contract_ItemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasContractsCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['contract_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Contract_Item$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasContractsCapacity::class)]);

        foreach ($CFG_GLPI['contract_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Contract_Item::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testCannotUnlinkContractWithOnlyReadRights(): void
    {
        $this->login();
        $computer = $this->createItem(\Computer::class, [
            'name'        => 'Unlink ' . $this->getUniqueString(),
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $contract = $this->createItem(\Contract::class, [
            'name'        => 'Unlink ' . $this->getUniqueString(),
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $link_id = $this->createItem(Contract_Item::class, [
            'contracts_id' => $contract->getID(),
            'itemtype'     => \Computer::class,
            'items_id'     => $computer->getID(),
        ])->getID();

        // Can view both items, but can update neither
        $_SESSION['glpiactiveprofile']['contract'] = READ;
        $_SESSION['glpiactiveprofile']['computer'] = READ;

        $link = new Contract_Item();
        $this->assertFalse($link->can($link_id, DELETE));
        $this->assertFalse($link->can($link_id, PURGE));

        // Update right on the contract side is enough
        $_SESSION['glpiactiveprofile']['contract'] = READ | UPDATE;
        $this->assertTrue($link->can($link_id, PURGE));
    }
}
