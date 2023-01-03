<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\Features;

/**
 * Test for the {@link \Glpi\Features\Clonable} feature
 */
class DCBreadcrumb extends \DbTestCase
{
    public function DCBreadcrumbProvider()
    {
        return [
            [\Computer::class, true],
            [\DCRoom::class, true],
            [\Enclosure::class, true],
            [\Monitor::class, true],
            [\NetworkEquipment::class, true],
            [\PassiveDCEquipment::class, true],
            [\PDU::class, true],
            [\Peripheral::class, true],
            [\Rack::class, true],
            [\Location::class, false]
        ];
    }

    /**
     * @param $class
     * @param $result
     * @dataProvider DCBreadcrumbProvider
     */
    public function testDCBreadcrumbProvider($class, $result)
    {
        $this->login();
        $this->boolean(method_exists($class, 'getDcBreadcrumbSpecificValueToDisplay'))->isIdenticalTo($result);
    }


    /**
     * Test DCBreadCrumb
     */
    public function testDCBreadcrumb()
    {
        $this->login();

       //create DataCenter
        $datacenter = new \Datacenter();
        $datacenter_name = 'test datacenter';
        $datacenter_id = $datacenter->add($datacenter_input = [
            'name'         => $datacenter_name,
        ]);
        $this->checkInput($datacenter, $datacenter_id, $datacenter_input);

       //create DCRoom
        $DCroom = new \DCRoom();
        $DCroom_name = 'test room';
        $DCroom_id = $DCroom->add($DCroom_input = [
            'name'           => $DCroom_name,
            'vis_cols'       => 10,
            'vis_rows'       => 10,
            'datacenters_id' => $datacenter_id
        ]);
        $this->checkInput($DCroom, $DCroom_id, $DCroom_input);

       //create Rack
        $rack = new \Rack();
        $rack_name = 'test rack';
        $rack_id = $rack->add($rack_input = [
            'name'         => $rack_name,
            'entities_id'   => 0,
            'dcrooms_id'   => $DCroom_id,
            'number_units' => 42,
            'position' => 62,
        ]);
        $this->checkInput($rack, $rack_id, $rack_input);

       //load computer and add it to rack
        $computer1 = getItemByTypeName('Computer', '_test_pc01');

       //create Rack_Item
        $Itemrack = new \Item_Rack();
        $rack_position = 37;
        $Itemrack_id = $Itemrack->add($Itemrack_input = [
            'racks_id' => $rack_id,
            'itemtype' => "Computer",
            'items_id' => $computer1->getID(),
            'position' => $rack_position,
        ]);
        $this->checkInput($Itemrack, $Itemrack_id, $Itemrack_input);

        $DCBreadcrumb = \Computer::getDcBreadcrumbSpecificValueToDisplay($computer1->getID());
        $this->string($DCBreadcrumb)->isIdenticalTo(
            sprintf(
                "<i class='ti ti-building-warehouse'></i> %s &gt; <i class='ti ti-building'></i> %s &gt; <i class='ti ti-server'></i> %s&nbsp;(U%d)",
                $datacenter_name,
                $DCroom_name,
                $rack_name,
                $rack_position
            )
        );

       //load computer without Rack link
        $computer2 = getItemByTypeName('Computer', '_test_pc02');

        $DCBreadcrumb = \Computer::getDcBreadcrumbSpecificValueToDisplay($computer2->getID());
        $this->string($DCBreadcrumb)->isIdenticalTo('&nbsp;');
    }
}
