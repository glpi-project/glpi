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

class Appliance_Item extends DbTestCase
{
    public function testGetForbiddenStandardMassiveAction()
    {
        $this->newTestedInstance();
        $this->array(
            $this->testedInstance->getForbiddenStandardMassiveAction()
        )->isIdenticalTo(['clone', 'update', 'CommonDBConnexity:unaffect', 'CommonDBConnexity:affect']);
    }

    public function testCountForAppliance()
    {
        global $DB;

        $appliance = new \Appliance();

        $appliance_1 = (int)$appliance->add([
            'name'   => 'Test appliance'
        ]);
        $this->integer($appliance_1)->isGreaterThan(0);

        $appliance_2 = (int)$appliance->add([
            'name'   => 'Test appliance'
        ]);
        $this->integer($appliance_2)->isGreaterThan(0);

        $itemtypes = [
            'Computer'  => '_test_pc01',
            'Printer'   => '_test_printer_all',
            'Software'  => '_test_soft'
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
                    'items_id'        => $items_id
                ];
                $this
                ->given($this->newTestedInstance)
                  ->then
                     ->integer($this->testedInstance->add($input))
                     ->isGreaterThan(0);
            }
        }

        $this->boolean($appliance->getFromDB($appliance_1))->isTrue();
       //not logged, no Appliances types
        $this->integer(\Appliance_Item::countForMainItem($appliance))->isIdenticalTo(0);

        $this->login();
        $this->integer(\Appliance_Item::countForMainItem($appliance))->isIdenticalTo(3);

        $this->boolean($appliance->getFromDB($appliance_2))->isTrue();
        $this->integer(\Appliance_Item::countForMainItem($appliance))->isIdenticalTo(2);

        $this->boolean($appliance->getFromDB($appliance_1))->isTrue();
        $this->boolean($appliance->delete(['id' => $appliance_1], true))->isTrue();

        $this->boolean($appliance->getFromDB($appliance_2))->isTrue();
        $this->boolean($appliance->delete(['id' => $appliance_2], true))->isTrue();

        $iterator = $DB->request([
            'FROM'   => \Appliance_Item::getTable(),
            'WHERE'  => ['appliances_id' => [$appliance_1, $appliance_2]]
        ]);
        $this->integer(count($iterator))->isIdenticalTo(0);
    }
}
