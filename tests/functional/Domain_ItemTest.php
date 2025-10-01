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

namespace tests\units;

use Computer;
use DbTestCase;
use Domain;
use Domain_Item;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDomainsCapacity;
use Glpi\Features\Clonable;
use Group;
use Toolbox;

class Domain_ItemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDomainsCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['domain_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Domain_Item$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDomainsCapacity::class)]);

        foreach ($CFG_GLPI['domain_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Domain_Item::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testGetForItem()
    {
        $this->login();
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $domain = $this->createItem(Domain::class, $this->getMinimalCreationInput(Domain::class) + [
            'groups_id_tech' => [getItemByTypeName(Group::class, '_test_group_1', true), getItemByTypeName(Group::class, '_test_group_2', true)],
        ], ['groups_id_tech']);
        $this->assertCount(0, Domain_Item::getForItem($computer));

        $this->createItem(
            Domain_Item::class,
            [
                'domains_id' => $domain->getID(),
                'items_id'   => $computer->getID(),
                'itemtype'   => Computer::class,
            ]
        );

        $result = Domain_Item::getForItem($computer);
        $this->assertCount(1, $result);
        $result = $result->current();
        // Check the group IDs were fetched from the Group_Item table
        $this->assertEquals(
            implode(',', [
                getItemByTypeName(Group::class, '_test_group_1', true),
                getItemByTypeName(Group::class, '_test_group_2', true),
            ]),
            $result['groups_id_tech']
        );
    }
}
