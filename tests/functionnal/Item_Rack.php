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

namespace tests\units;

use DbTestCase;

/* Test for inc/item_rack.class.php */

class Item_Rack extends DbTestCase
{
    /**
     * Models provider
     *
     * @return array
     */
    protected function modelsProvider()
    {
        return [
            [
                'name'            => 'Full',
                'required_units'  => 1,
                'depth'           => 1,
                'is_half_rack'    => 0
            ], [
                'name'            => 'Midrack',
                'required_units'  => 1,
                'depth'           => 1,
                'is_half_rack'    => 1
            ], [
                'name'            => '3U',
                'required_units'  => 3,
                'depth'           => 1,
                'is_half_rack'    => 0
            ], [
                'name'            => '1/2 Depth',
                'required_units'  => 1,
                'depth'           => 0.5,
                'is_half_rack'    => 0
            ], [
                'name'            => 'Mid 1/2 depth',
                'required_units'  => 1,
                'depth'           => 0.5,
                'is_half_rack'    => 1
            ], [
                'name'            => '2U and depth',
                'required_units'  => 2,
                'depth'           => 0.25,
                'is_half_rack'    => 0
            ], [
                'name'            => '2U and mid',
                'required_units'  => 2,
                'depth'           => 1,
                'is_half_rack'    => 1
            ]
        ];
    }

    /**
     * Create models
     *
     * @return void
     */
    protected function createModels()
    {
        $model = new \ComputerModel();
        foreach ($this->modelsProvider() as $row) {
            $this->integer(
                (int)$model->add($row)
            )->isGreaterThan(0);
        }
    }

    /**
     * Computers provider
     *
     * @return array
     */
    protected function computersProvider()
    {
        return [
            [
                'name'   => 'SRV-NUX-1',
                'model'  => 'Full'
            ], [
                'name'   => 'SRV-NUX-2',
                'model'  => 'Full'
            ], [
                'name'   => 'MID-NUX-1',
                'model'  => 'Midrack'
            ], [
                'name'   => 'MID-NUX-2',
                'model'  => 'Midrack'
            ], [
                'name'   => 'MID-NUX-3',
                'model'  => 'Midrack'
            ], [
                'name'   => 'BIG-NUX-1',
                'model'  => '3U'
            ], [
                'name'   => 'DEP-NUX-1',
                'model'  => '1/2 Depth'
            ], [
                'name'   => 'DEP-NUX-2',
                'model'  => '1/2 Depth'
            ], [
                'name'   => 'MAD-NUX-1',
                'model'  => 'Mid 1/2 depth'
            ], [
                'name'   => 'MAD-NUX-2',
                'model'  => 'Mid 1/2 depth'
            ], [
                'name'   => 'MAD-NUX-3',
                'model'  => 'Mid 1/2 depth'
            ], [
                'name'   => 'MAD-NUX-4',
                'model'  => 'Mid 1/2 depth'
            ], [
                'name'   => '2AD-NUX-1',
                'model'  => '2U and depth'
            ], [
                'name'   => '2AM-NUX-1',
                'model'  => '2U and mid'
            ]
        ];
    }

    /**
     * Create computers
     *
     * @return void
     */
    protected function createComputers()
    {
        $computer = new \Computer();
        foreach ($this->computersProvider() as $row) {
            $row['computermodels_id'] = getItemByTypeName('ComputerModel', $row['model'], true);
            $this->integer((int)$row['computermodels_id'])->isGreaterThan(0);
            $row['entities_id'] = 0;
            unset($row['model']);
            $this->integer(
                (int)$computer->add($row)
            )->isGreaterThan(0);
        }
    }


    /**
     * Test for adding items into rack
     *
     * @return void
     */
    public function testAdd()
    {
        $this->createModels();
        $this->createComputers();

        $rack = new \Rack();
       //create a 10u rack
        $this->integer(
            (int)$rack->add([
                'name'         => 'Test rack',
                'number_units' => 10,
                'dcrooms_id'   => 0,
                'position'     => 0,
                'entities_id'  => 0,
            ])
        )->isGreaterThan(0);

        $ira = new \Item_Rack();

        $SRVNUX1 = getItemByTypeName('Computer', 'SRV-NUX-1', true);
       //try to add outside rack capabilities
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 15,
                'itemtype'  => 'Computer',
                'items_id'  => $SRVNUX1
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Item is out of rack bounds']);

       //add item at the first position
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 1,
                'itemtype'  => 'Computer',
                'items_id'  => $SRVNUX1
            ])
        )->isGreaterThan(0);

        $BIGNUX1 = getItemByTypeName('Computer', 'BIG-NUX-1', true);
       //take a 3U item and try to add it at the end
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 10,
                'itemtype'  => 'Computer',
                'items_id'  => $BIGNUX1
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Item is out of rack bounds']);

       //take a 3U item and try to add it at the end - 1
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 9,
                'itemtype'  => 'Computer',
                'items_id'  => $BIGNUX1
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Item is out of rack bounds']);

       //take a 3U item and try to add it at the end - 2
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 8,
                'itemtype'  => 'Computer',
                'items_id'  => $BIGNUX1
            ])
        )->isGreaterThan(0);

       //test half racks
        $MIDNUX1 = getItemByTypeName('Computer', 'MID-NUX-1', true);
        $MIDNUX2 = getItemByTypeName('Computer', 'MID-NUX-2', true);
        $MIDNUX3 = getItemByTypeName('Computer', 'MID-NUX-3', true);
       //item is half rack. hpos is required
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 1,
                'itemtype'  => 'Computer',
                'items_id'  => $MIDNUX1
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['You must define an horizontal position for this item']);

       //try to add a half size on the first row
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 1,
                'itemtype'  => 'Computer',
                'items_id'  => $MIDNUX1,
                'hpos'      => $rack::POS_LEFT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

       //add it on second row
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MIDNUX1,
                'hpos'      => $rack::POS_LEFT
            ])
        )->isGreaterThan(0);

       //add second half rack item it on second row, at same position
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MIDNUX2,
                'hpos'      => $rack::POS_LEFT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

       //add second half rack item it on second row, on the other position
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MIDNUX2,
                'hpos'      => $rack::POS_RIGHT
            ])
        )->isGreaterThan(0);

       //Unit is full!
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MIDNUX3,
                'hpos'      => $rack::POS_LEFT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

       //test depth < 1
        $DEPNUX1 = getItemByTypeName('Computer', 'DEP-NUX-1', true);
        $DEPNUX2 = getItemByTypeName('Computer', 'DEP-NUX-2', true);

       //item ahs a depth <= 0.5. orientation is required
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 1,
                'itemtype'  => 'Computer',
                'items_id'  => $DEPNUX1
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['You must define an orientation for this item']);

       //try to add on the first row
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 1,
                'itemtype'  => 'Computer',
                'items_id'  => $DEPNUX1,
                'orientation'  => $rack::FRONT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

       //try to add on the second row
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $DEPNUX1,
                'orientation'  => $rack::FRONT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

       //add on the third row
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 3,
                'itemtype'  => 'Computer',
                'items_id'  => $DEPNUX1,
                'orientation'  => $rack::FRONT
            ])
        )->isGreaterThan(0);

       //add not full depth rack item with same orientation
       //try to add on the first row
        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 3,
                'itemtype'  => 'Computer',
                'items_id'  => $DEPNUX2,
                'orientation'  => $rack::FRONT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 3,
                'itemtype'  => 'Computer',
                'items_id'  => $DEPNUX2,
                'orientation'  => $rack::REAR
            ])
        )->isGreaterThan(0);

       //test hf full depth + 2x hf mid depth
        $MADNUX1 = getItemByTypeName('Computer', 'MAD-NUX-1', true);
        $MADNUX2 = getItemByTypeName('Computer', 'MAD-NUX-2', true);

       //first element on unit2 (MID-NUX-1) is half racked on left; and is full depth
       //drop second element on unit2
        $ira->deleteByCriteria(['items_id' => $MIDNUX2], 1);

        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MADNUX1,
                'orientation'  => $rack::REAR,
                'hpos'      => $rack::POS_LEFT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MADNUX1,
                'orientation'  => $rack::REAR,
                'hpos'      => $rack::POS_RIGHT
            ])
        )->isGreaterThan(0);

        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MADNUX2,
                'orientation'  => $rack::REAR,
                'hpos'      => $rack::POS_LEFT
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['Not enough space available to place item']);

        $ira->getEmpty();
        $this->integer(
            (int)$ira->add([
                'racks_id'  => $rack->getID(),
                'position'  => 2,
                'itemtype'  => 'Computer',
                'items_id'  => $MADNUX2,
                'orientation'  => $rack::FRONT,
                'hpos'      => $rack::POS_RIGHT
            ])
        )->isGreaterThan(0);
    }
}
