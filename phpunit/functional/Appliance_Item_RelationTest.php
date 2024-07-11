<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use DbTestCase;

class Appliance_Item_RelationTest extends DbTestCase
{
    public function testGetForbiddenStandardMassiveAction()
    {
        $aritem = new \Appliance_Item_Relation();
        $this->assertSame(
            ['clone'],
            $aritem->getForbiddenStandardMassiveAction()
        );
    }

    public function testCountForApplianceItem()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $appliance = new \Appliance();

        $appliances_id = (int)$appliance->add([
            'name'   => 'Test appliance'
        ]);
        $this->assertGreaterThan(0, $appliances_id);

        $items_id = getItemByTypeName('Computer', '_test_pc01', true);
        $input = [
            'appliances_id'   => $appliances_id,
            'itemtype'        => 'Computer',
            'items_id'        => $items_id
        ];
        $appitem = new \Appliance_Item();
        $appliances_items_id = $appitem->add($input);
        $this->assertGreaterThan(0, $appliances_items_id);

        $input = [
            'appliances_items_id'   => $appliances_items_id,
            'itemtype'              => 'Location',
            'items_id'              => getItemByTypeName('Location', '_location01', true)
        ];
        $aritem = new \Appliance_Item_Relation();
        $this->assertGreaterThan(0, $aritem->add($input));

        $this->assertTrue($appliance->getFromDB($appliances_id));
        $this->assertTrue($appitem->getFromDB($appliances_items_id));
        //not logged, no Appliances types
        $this->assertSame(0, \Appliance_Item_Relation::countForMainItem($appitem));

        $this->login();
        $this->setEntity(0, true); //locations are in root entity not recursive
        $this->assertSame(1, \Appliance_Item_Relation::countForMainItem($appitem));
        $relations = \Appliance_Item_Relation::getForApplianceItem($appliances_items_id);
        $this->assertCount(1, $relations);
        $this->assertStringContainsString('_location01', array_pop($relations));

        $this->assertTrue($appliance->delete(['id' => $appliances_id], true));
        $iterator = $DB->request([
            'FROM'   => \Appliance_Item::getTable(),
            'WHERE'  => ['appliances_id' => $appliances_id]
        ]);
        $this->assertCount(0, $iterator);

        $iterator = $DB->request([
            'FROM'   => \Appliance_Item_Relation::getTable(),
            'WHERE'  => ['appliances_items_id' => $appliances_items_id]
        ]);
        $this->assertCount(0, $iterator);

        $this->assertSame([], \Appliance_Item_Relation::getForApplianceItem($appliances_items_id));
    }
}
