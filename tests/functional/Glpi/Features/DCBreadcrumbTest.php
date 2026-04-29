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

namespace tests\units\Glpi\Features;

use Computer;
use DCRoom;
use Glpi\Features\DCBreadcrumb;
use Glpi\Tests\DbTestCase;
use Item_Rack;
use PHPUnit\Framework\Attributes\DataProvider;
use Rack;

/**
 * Test for the {@link \Glpi\Features\DCBreadcrumb} feature
 */
class DCBreadcrumbTest extends DbTestCase
{
    public static function itemtypeProvider()
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI["rackable_types"] as $itemtype) {
            yield [
                'class' => $itemtype,
            ];
        }

        yield [
            'class' => Rack::class,
        ];
        yield [
            'class' => DCRoom::class,
        ];
    }

    #[DataProvider('itemtypeProvider')]
    public function testClassUsesTrait($class)
    {
        $this->assertTrue(in_array(DCBreadcrumb::class, class_uses($class, true)));
    }

    public function testGetParentRackAndPosition()
    {
        $rack_1 = $this->createItem(Rack::class, [
            'name' => __FUNCTION__ . '_1',
            'entities_id' => $this->getTestRootEntity(true),
            'number_units' => 10,
        ]);
        $rack_2 = $this->createItem(Rack::class, [
            'name' => __FUNCTION__ . '_2',
            'entities_id' => $this->getTestRootEntity(true),
            'number_units' => 10,
        ]);

        $computer = getItemByTypeName(Computer::class, '_test_pc01');

        $this->createItem(Item_Rack::class, [
            'racks_id' => $rack_1->getID(),
            'items_id' => $computer->getID(),
            'itemtype' => Computer::class,
            'is_reserved' => 1,
            'position' => 1,
        ]);
        $this->createItem(Item_Rack::class, [
            'racks_id' => $rack_2->getID(),
            'items_id' => $computer->getID(),
            'itemtype' => Computer::class,
            'is_reserved' => 0,
            'position' => 4,
        ]);

        $this->assertEquals($rack_2->getID(), $computer->getParentRack()->getID());
        $this->assertEquals(4, $computer->getPositionInRack());
    }
}
