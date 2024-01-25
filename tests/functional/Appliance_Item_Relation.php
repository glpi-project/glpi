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

class Appliance_Item_Relation extends DbTestCase
{
    public function testGetForbiddenStandardMassiveAction()
    {
        $this->newTestedInstance();
        $this->array(
            $this->testedInstance->getForbiddenStandardMassiveAction()
        )->isIdenticalTo(['clone'/*, 'update', 'CommonDBConnexity:unaffect', 'CommonDBConnexity:affect'*/]);
    }

    public function testCountForApplianceItem()
    {
        global $DB;

        $appliance = new \Appliance();

        $appliances_id = (int)$appliance->add([
            'name'   => 'Test appliance'
        ]);
        $this->integer($appliances_id)->isGreaterThan(0);

        $items_id = getItemByTypeName('Computer', '_test_pc01', true);
        $input = [
            'appliances_id'   => $appliances_id,
            'itemtype'        => 'Computer',
            'items_id'        => $items_id
        ];
        $appitem = new \Appliance_Item();
        $appliances_items_id = $appitem->add($input);
        $this->integer($appliances_items_id)->isGreaterThan(0);

        $input = [
            'appliances_items_id'   => $appliances_items_id,
            'itemtype'              => 'Location',
            'items_id'              => getItemByTypeName('Location', '_location01', true)
        ];
        $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($this->testedInstance->add($input))
               ->isGreaterThan(0);

        $iterator = $DB->request([
            'FROM'   => \Appliance_Item_Relation::getTable(),
            'WHERE'  => ['appliances_items_id' => $appliances_items_id]
        ]);

        $this->boolean($appliance->getFromDB($appliances_id))->isTrue();
        $this->boolean($appitem->getFromDB($appliances_items_id))->isTrue();
       //not logged, no Appliances types
        $this->integer(\Appliance_Item_Relation::countForMainItem($appitem))->isIdenticalTo(0);

        $this->login();
        $this->setEntity(0, true); //locations are in root entity not recursive
        $this->integer(\Appliance_Item_Relation::countForMainItem($appitem))->isIdenticalTo(1);
        $relations = \Appliance_Item_Relation::getForApplianceItem($appliances_items_id);
        $this->array($relations)->hasSize(1);
        $this->string(array_pop($relations))->contains('_location01');

        $this->boolean($appliance->delete(['id' => $appliances_id], true))->isTrue();
        $iterator = $DB->request([
            'FROM'   => \Appliance_Item::getTable(),
            'WHERE'  => ['appliances_id' => $appliances_id]
        ]);
        $this->integer(count($iterator))->isIdenticalTo(0);

        $iterator = $DB->request([
            'FROM'   => \Appliance_Item_Relation::getTable(),
            'WHERE'  => ['appliances_items_id' => $appliances_items_id]
        ]);
        $this->integer(count($iterator))->isIdenticalTo(0);

        $this->array(\Appliance_Item_Relation::getForApplianceItem($appliances_items_id))->isEmpty();
    }
}
