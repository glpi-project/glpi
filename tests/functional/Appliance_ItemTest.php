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

use Appliance_Item;
use DbTestCase;
use Entity;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasAppliancesCapacity;
use Glpi\Features\Clonable;
use Toolbox;

class Appliance_ItemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasAppliancesCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['appliance_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Appliance_Item$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasAppliancesCapacity::class)]);

        foreach ($CFG_GLPI['appliance_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Appliance_Item::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testGetForbiddenStandardMassiveAction()
    {
        $aitem = new Appliance_Item();
        $this->assertSame(
            ['clone', 'update', 'CommonDBConnexity:unaffect', 'CommonDBConnexity:affect'],
            $aitem->getForbiddenStandardMassiveAction()
        );
    }

    public function testCountForAppliance()
    {
        global $DB;

        $entity_id = \getItemByTypeName(Entity::class, '_test_root_entity', true);

        $appliance = new \Appliance();

        $appliance_1 = (int) $appliance->add([
            'name'        => 'Test appliance',
            'entities_id' => $entity_id,
        ]);
        $this->assertGreaterThan(0, $appliance_1);

        $appliance_2 = (int) $appliance->add([
            'name'        => 'Test appliance 2',
            'entities_id' => $entity_id,
        ]);
        $this->assertGreaterThan(0, $appliance_2);

        $itemtypes = [
            'Computer'  => '_test_pc01',
            'Printer'   => '_test_printer_all',
            'Software'  => '_test_soft',
        ];

        foreach ($itemtypes as $itemtype => $itemname) {
            $items_id = getItemByTypeName($itemtype, $itemname, true);
            foreach ([$appliance_1, $appliance_2] as $app) {
                //no printer on appliance_2
                if ($itemtype == 'Printer' && $app == $appliance_2) {
                    continue;
                }

                $input = [
                    'appliances_id'   => $app,
                    'itemtype'        => $itemtype,
                    'items_id'        => $items_id,
                ];
                $aitem = new Appliance_Item();
                $this->assertGreaterThan(0, $aitem->add($input));
            }
        }

        $this->assertTrue($appliance->getFromDB($appliance_1));
        //not logged, no Appliances types
        $this->assertSame(0, Appliance_Item::countForMainItem($appliance));

        $this->login();
        $this->assertSame(3, Appliance_Item::countForMainItem($appliance));

        $this->assertTrue($appliance->getFromDB($appliance_2));
        $this->assertSame(2, Appliance_Item::countForMainItem($appliance));

        $this->assertTrue($appliance->getFromDB($appliance_1));
        $this->assertTrue($appliance->delete(['id' => $appliance_1], true));

        $this->assertTrue($appliance->getFromDB($appliance_2));
        $this->assertTrue($appliance->delete(['id' => $appliance_2], true));

        $iterator = $DB->request([
            'FROM'   => Appliance_Item::getTable(),
            'WHERE'  => ['appliances_id' => [$appliance_1, $appliance_2]],
        ]);
        $this->assertCount(0, $iterator);
    }
}
