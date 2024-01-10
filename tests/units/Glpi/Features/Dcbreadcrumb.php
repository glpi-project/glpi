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


    protected function renderDcBreadcrumbProvider(): iterable
    {
        // Create location tree
        $location = new \Location();

        $location_1_name  = 'root location 1';
        $location_1_input = [
            'name' => $location_1_name,
        ];
        $location_1_id    = $location->add($location_1_input);
        $this->checkInput($location, $location_1_id, $location_1_input);
        $location_1 = getItemByTypeName('Location', $location_1_name);

        $location_1_1_name  = 'sub location 1.1';
        $location_1_1_input = [
            'name'         => $location_1_1_name,
            'locations_id' => $location_1_id,
        ];
        $location_1_1_id    = $location->add($location_1_1_input);
        $this->checkInput($location, $location_1_1_id, $location_1_1_input);
        $location_1_1 = getItemByTypeName('Location', $location_1_1_name);

        $location_2_name  = 'root location 2';
        $location_2_input = [
            'name' => $location_2_name,
        ];
        $location_2_id    = $location->add($location_2_input);
        $this->checkInput($location, $location_2_id, $location_2_input);
        $location_2 = getItemByTypeName('Location', $location_2_name);

        $location_2_1_name  = 'sub location 2.1';
        $location_2_1_input = [
            'name'         => $location_2_1_name,
            'locations_id' => $location_2_id,
        ];
        $location_2_1_id    = $location->add($location_2_1_input);
        $this->checkInput($location, $location_2_1_id, $location_2_1_input);
        $location_2_1 = getItemByTypeName('Location', $location_2_1_name);

        // Location template
        $location_tpl = <<<HTML
            <br>
            <span class="p-0 float-left badge bg-blue-lt d-inline-block text-truncate"
                  data-bs-toggle="tooltip"
                  title="%1\$s"
                  style="max-width: 100px;">
                <i class="ti ti-map-pin"></i>
                %2\$s
            </span>
HTML;

        yield [
            'datacenter_location_id'   => 0,
            'dcroom_location_id'       => 0,
            'rack_location_id'         => 0,
            'enclosure_location_id'    => 0,
            'datacenter_location_text' => '',
            'dcroom_location_text'     => '',
            'rack_location_text'       => '',
            'enclosure_location_text'  => '',
        ];

        yield [
            'datacenter_location_id'   => $location_1_id,
            'dcroom_location_id'       => $location_1_id,
            'rack_location_id'         => $location_1_id,
            'enclosure_location_id'    => $location_1_id,
            'datacenter_location_text' => sprintf(
                $location_tpl,
                htmlentities($location_1->fields['completename']),
                htmlentities($location_1->fields['completename']) // first location, show completename
            ),
            'dcroom_location_text'     => '', // same as previous location, not displayed
            'rack_location_text'       => '', // same as previous location, not displayed
            'enclosure_location_text'  => '', // same as previous location, not displayed
        ];

        yield [
            'datacenter_location_id'   => $location_1_id,
            'dcroom_location_id'       => 0,
            'rack_location_id'         => $location_1_1_id,
            'enclosure_location_id'    => $location_1_1_id,
            'datacenter_location_text' => sprintf(
                $location_tpl,
                htmlentities($location_1->fields['completename']),
                htmlentities($location_1->fields['completename']) // first location, show completename
            ),
            'dcroom_location_text'     => '',
            'rack_location_text'       => sprintf(
                $location_tpl,
                htmlentities($location_1_1->fields['completename']),
                htmlentities('> ' . $location_1_1->fields['name']) // child of previous location, show only name, prefixed by ">"
            ),
            'enclosure_location_text'  => '', // same as previous location, not displayed
        ];

        yield [
            'datacenter_location_id'   => $location_1_id,
            'dcroom_location_id'       => 0,
            'rack_location_id'         => $location_2_id,
            'enclosure_location_id'    => $location_2_1_id,
            'datacenter_location_text' => sprintf(
                $location_tpl,
                htmlentities($location_1->fields['completename']),
                htmlentities($location_1->fields['completename']) // first location, show completename
            ),
            'dcroom_location_text'     => '',
            'rack_location_text'       => sprintf(
                $location_tpl,
                htmlentities($location_2->fields['completename']),
                htmlentities($location_2->fields['completename']) // not child of previous location, show complete name
            ),
            'enclosure_location_text'  => sprintf(
                $location_tpl,
                htmlentities($location_2_1->fields['completename']),
                htmlentities('> ' . $location_2_1->fields['name']) // child of previous location, show only name, prefixed by ">"
            ),
        ];

        yield [
            'datacenter_location_id'   => 0,
            'dcroom_location_id'       => 0,
            'rack_location_id'         => $location_2_id,
            'enclosure_location_id'    => $location_1_id,
            'datacenter_location_text' => '',
            'dcroom_location_text'     => '',
            'rack_location_text'       => sprintf(
                $location_tpl,
                htmlentities($location_2->fields['completename']),
                htmlentities($location_2->fields['completename']) // first locatio, show complete name
            ),
            'enclosure_location_text'  => sprintf(
                $location_tpl,
                htmlentities($location_1->fields['completename']),
                htmlentities($location_1->fields['completename']) // not child of previous location, show completename
            ),
        ];
    }

    /**
     * @dataProvider renderDcBreadcrumbProvider
     */
    public function testRenderDcBreadcrumb(
        int $datacenter_location_id,
        int $dcroom_location_id,
        int $rack_location_id,
        int $enclosure_location_id,
        string $datacenter_location_text,
        string $dcroom_location_text,
        string $rack_location_text,
        string $enclosure_location_text
    ) {
        $this->login();

        //create DataCenter
        $datacenter = new \Datacenter();
        $datacenter_name = 'test datacenter';
        $datacenter_id = $datacenter->add($datacenter_input = [
            'name'         => $datacenter_name,
            'locations_id' => $datacenter_location_id,
        ]);
        $this->checkInput($datacenter, $datacenter_id, $datacenter_input);

        //create DCRoom
        $dcroom = new \DCRoom();
        $dcroom_name = 'test room';
        $dcroom_id = $dcroom->add($dcroom_input = [
            'name'           => $dcroom_name,
            'vis_cols'       => 10,
            'vis_rows'       => 10,
            'datacenters_id' => $datacenter_id,
            'locations_id'   => $dcroom_location_id,
        ]);
        $this->checkInput($dcroom, $dcroom_id, $dcroom_input);

        //create Rack
        $rack = new \Rack();
        $rack_name = 'test rack';
        $rack_id = $rack->add($rack_input = [
            'name'         => $rack_name,
            'entities_id'  => 0,
            'dcrooms_id'   => $dcroom_id,
            'number_units' => 42,
            'position'     => 62,
            'locations_id' => $rack_location_id,
        ]);
        $this->checkInput($rack, $rack_id, $rack_input);

        //create Enclosure
        $enclosure = new \Enclosure();
        $enclosure_name = 'test enclosure';
        $enclosure_id = $enclosure->add($enclosure_input = [
            'name'         => $enclosure_name,
            'entities_id'  => 0,
            'locations_id' => $enclosure_location_id,
        ]);
        $this->checkInput($enclosure, $enclosure_id, $enclosure_input);

        //create Rack Item
        $item_rack = new \Item_Rack();
        $position_in_rack = rand(1, 42);
        $item_rack_id = $item_rack->add($item_rack_input = [
            'racks_id' => $rack_id,
            'itemtype' => "Enclosure",
            'items_id' => $enclosure_id,
            'position' => $position_in_rack,
        ]);
        $this->checkInput($item_rack, $item_rack_id, $item_rack_input);

        //create Computer
        $computer = new \Computer();
        $computer_id = $computer->add($computer_input = [
            'name'         => 'test computer',
            'entities_id'  => 0,
        ]);
        $this->checkInput($computer, $computer_id, $computer_input);

        //create Enclosure Item
        $item_enclosure = new \Item_Enclosure();
        $position_in_enclosure = rand(1, 10);
        $item_enclosure_id = $item_enclosure->add($item_enclosure_input = [
            'enclosures_id' => $enclosure_id,
            'itemtype'      => "Computer",
            'items_id'      => $computer_id,
            'position'      => $position_in_enclosure,
        ]);
        $this->checkInput($item_enclosure, $item_enclosure_id, $item_enclosure_input);

        $expected = <<<HTML
            <div class="row">
                <div class="col-auto p-1">
                    <i class='ti ti-building-warehouse'></i> {$datacenter_name}
                    {$datacenter_location_text}
                </div>
                <div class="col-auto p-1">
                    &gt;
                </div>
                <div class="col-auto p-1">
                    <i class='ti ti-building'></i> {$dcroom_name}
                    {$dcroom_location_text}
                </div>
                <div class="col-auto p-1">
                    &gt;
                </div>
                <div class="col-auto p-1">
                    <i class='ti ti-server'></i> {$rack_name} (U{$position_in_rack})
                    {$rack_location_text}
                </div>
                <div class="col-auto p-1">
                    &gt;
                </div>
                <div class="col-auto p-1">
                    <i class='ti ti-columns'></i> {$enclosure_name} (U{$position_in_enclosure})
                    {$enclosure_location_text}
                </div>
            </div>
HTML;
        $result = \Computer::renderDcBreadcrumb($computer_id);

        // Deduplicate whitespaces to not have to deal with indentation.
        $expected = preg_replace('/\s+/', ' ', trim($expected));
        $result = preg_replace('/\s+/', ' ', trim($result));

        $this->string($result)->isIdenticalTo($expected);

       //load computer without Rack link
        $computer2 = getItemByTypeName('Computer', '_test_pc02');

        $DCBreadcrumb = \Computer::renderDcBreadcrumb($computer2->getID());
        $this->string(trim($DCBreadcrumb))->isEmpty();
    }
}
