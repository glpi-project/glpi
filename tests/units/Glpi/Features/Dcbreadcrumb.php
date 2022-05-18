<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Toolbox;

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
    public function testClassUsesTrait($class, $result)
    {
        $this->boolean(in_array(\Glpi\Features\DCBreadcrumb::class, class_uses($class, true)));
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

        $DCBreadcrumb = null;
        $this->when(
            function () use (&$DCBreadcrumb, $computer1) {
                $DCBreadcrumb = \Computer::getDcBreadcrumbSpecificValueToDisplay($computer1->getID());
            }
        )->error()
           ->withType(E_USER_DEPRECATED)
           ->withMessage('Called method is deprecated')
           ->exists()  // deprecation on getDcBreadcrumbSpecificValueToDisplay
           ->exists()  // deprecation on getDcBreadcrumb
           ->exists()  // deprecation on isEnclosurePart
           ->exists(); // deprecation on isRackPart

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

        $DCBreadcrumb = null;
        $this->when(
            function () use (&$DCBreadcrumb, $computer2) {
                $DCBreadcrumb = \Computer::getDcBreadcrumbSpecificValueToDisplay($computer2->getID());
            }
        )->error()
           ->withType(E_USER_DEPRECATED)
           ->withMessage('Called method is deprecated')
           ->exists()  // deprecation on getDcBreadcrumbSpecificValueToDisplay
           ->exists()  // deprecation on getDcBreadcrumb
           ->exists()  // deprecation on isEnclosurePart
           ->exists(); // deprecation on isRackPart

        $this->string($DCBreadcrumb)->isIdenticalTo('&nbsp;');
    }


    /**
     * Test DCBreadCrumb
     */
    public function testRenderDcBreadcrumb()
    {
        $this->login();

        //create Location for Datacenter
        $location_datacenter = new \Location();
        $location_datacenter_name = 'Saint-Petersbourg';
        $location_datacenter_id = $location_datacenter->add($location_datacenter_input = [
            'name'         => $location_datacenter_name,
        ]);
        $this->checkInput($location_datacenter, $location_datacenter_id, $location_datacenter_input);

        //create DataCenter
        $datacenter = new \Datacenter();
        $datacenter_name = 'test datacenter';
        $datacenter_id = $datacenter->add($datacenter_input = [
            'name'         => $datacenter_name,
            'locations_id' => $location_datacenter_id,
        ]);
        $this->checkInput($datacenter, $datacenter_id, $datacenter_input);

        //create Location for DCRoom (child of location_datacenter)
        $location_dcroom = new \Location();
        $location_dcroom_name = 'room1';
        $location_dcroom_id = $location_dcroom->add($location_dcroom_input = [
            'name'         => $location_dcroom_name,
            'locations_id' => $location_datacenter_id,
        ]);
        $this->checkInput($location_dcroom, $location_dcroom_id, $location_dcroom_input);
        $location_dcroom_completename = $location_dcroom->fields['completename'];

        //create DCRoom
        $DCroom = new \DCRoom();
        $DCroom_name = 'test room';
        $DCroom_id = $DCroom->add($DCroom_input = [
            'name'           => $DCroom_name,
            'vis_cols'       => 10,
            'vis_rows'       => 10,
            'datacenters_id' => $datacenter_id,
            'locations_id'   => $location_dcroom_id,
        ]);
        $this->checkInput($DCroom, $DCroom_id, $DCroom_input);

        //create Location for Rack
        $location_rack = new \Location();
        $location_rack_name = 'rack01';
        $location_rack_id = $location_rack->add($location_rack_input = [
            'name'         => $location_rack_name,
        ]);
        $this->checkInput($location_rack, $location_rack_id, $location_rack_input);

        //create Rack
        $rack = new \Rack();
        $rack_name = 'test rack';
        $rack_id = $rack->add($rack_input = [
            'name'         => $rack_name,
            'entities_id'   => 0,
            'dcrooms_id'   => $DCroom_id,
            'number_units' => 42,
            'position' => 62,
            'locations_id' => $location_rack_id,
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

        $DCBreadcrumb = \Computer::renderDcBreadcrumb($computer1->getID());

        $location_dcroom_completename = htmlentities($location_dcroom_completename); // > separator will be encoded in result
        $expected = <<<HTML
    <div class="row">
                            <div class="col-auto p-1">
                <i class='ti ti-building-warehouse'></i> {$datacenter_name}

                
                                    <br>
                    <span class="p-0 float-left badge bg-blue-lt d-inline-block text-truncate"
                          data-bs-toggle="tooltip"
                          title="{$location_datacenter_name}"
                          style="max-width: 100px;">
                        <i class="ti ti-map-pin"></i>
                                                                              {$location_datacenter_name}
                                            </span>
                                                </div>
                            <div class="col-auto p-1">
                    &gt;
                </div>
                                <div class="col-auto p-1">
                <i class='ti ti-building'></i> {$DCroom_name}

                
                                    <br>
                    <span class="p-0 float-left badge bg-blue-lt d-inline-block text-truncate"
                          data-bs-toggle="tooltip"
                          title="{$location_dcroom_completename}"
                          style="max-width: 100px;">
                        <i class="ti ti-map-pin"></i>
                                                                                 &gt;
                                                      {$location_dcroom_name}
                                            </span>
                                                </div>
                            <div class="col-auto p-1">
                    &gt;
                </div>
                                <div class="col-auto p-1">
                <i class='ti ti-server'></i> {$rack_name}

                                    (U{$rack_position})
                
                                    <br>
                    <span class="p-0 float-left badge bg-blue-lt d-inline-block text-truncate"
                          data-bs-toggle="tooltip"
                          title="{$location_rack_name}"
                          style="max-width: 100px;">
                        <i class="ti ti-map-pin"></i>
                                                   {$location_rack_name}
                                            </span>
                                                </div>
                        </div>

HTML;
        $this->string($DCBreadcrumb)->isIdenticalTo($expected);

       //load computer without Rack link
        $computer2 = getItemByTypeName('Computer', '_test_pc02');

        $DCBreadcrumb = \Computer::renderDcBreadcrumb($computer2->getID());
        $this->string($DCBreadcrumb)->isEmpty();
    }
}
