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

use PHPUnit\Framework\Attributes\DataProvider;

class ImpactItemTest extends \DbTestCase
{
    public function testFindForItem_inexistent()
    {
        $computer = getItemByTypeName('Computer', '_test_pc02');

        $this->assertFalse(\ImpactItem::findForItem($computer, false));
    }

    public function testFindForItem_exist()
    {
        $impactItemManager = new \ImpactItem();
        $computer = getItemByTypeName('Computer', '_test_pc02');

        $id = $impactItemManager->add([
            'itemtype'  => "Computer",
            'items_id'  => $computer->fields['id'],
            'parent_id' => 0,
        ]);

        $impactItem = \ImpactItem::findForItem($computer);
        $this->assertEquals($id, (int) $impactItem->fields['id']);
    }

    public static function prepareInputForUpdateProvider()
    {
        return [
            [
                'input'  => ['max_depth' => "glpi"],
                'result' => \Impact::DEFAULT_DEPTH,
            ],
            [
                'input'  => ['max_depth' => 0],
                'result' => \Impact::DEFAULT_DEPTH,
            ],
            [
                'input'  => ['max_depth' => -58],
                'result' => \Impact::DEFAULT_DEPTH,
            ],
            [
                'input'  => ['max_depth' => 9],
                'result' => 9,
            ],
            [
                'input'  => ['max_depth' => 2],
                'result' => 2,
            ],
            [
                'input'  => ['max_depth' => 40],
                'result' => \Impact::NO_DEPTH_LIMIT,
            ],
            [
                'input'  => ['max_depth' => \Impact::NO_DEPTH_LIMIT],
                'result' => \Impact::NO_DEPTH_LIMIT,
            ],
        ];
    }

    #[DataProvider('prepareInputForUpdateProvider')]
    public function testPrepareInputForUpdate($input, $result)
    {
        $impact_item = new \ImpactItem();
        $input = $impact_item->prepareInputForUpdate($input);
        $this->assertArrayHasKey('max_depth', $input);
        $this->assertEquals($result, $input['max_depth']);
    }
}
