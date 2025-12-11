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

namespace tests\units\Glpi\Search;

use Glpi\Asset\AssetDefinition;
use Glpi\Search\SearchOption;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SearchOptionTest extends DbTestCase
{
    public static function getDefaultToViewProvider(): array
    {
        return [
            [\Computer::class, [1, 80]], // Name, Entity
            [\ITILCategory::class, [1, 80]], // Completename, Entity
            [\Item_DeviceSimcard::class, [10, 80]], // Serial (marked as the name field), Entity
            [\Ticket::class, [2, 1, 80]], // ID (Always shown for ITIL Objects), Name, Entity
            [\KnowbaseItem::class, [1, 80]], // Name, Entity Target
            [\RSSFeed::class, [1]], // Name (Not Entity assignable)
            [\AllAssets::class, [1, 80]], // Name, Entity
            [AssetDefinition::class, [2]], // Label (Not Entity assignable)
        ];
    }

    #[DataProvider('getDefaultToViewProvider')]
    public function testGetDefaultToView(string $itemtype, array $expected): void
    {
        $this->login();
        $this->assertEquals(
            $expected,
            SearchOption::getDefaultToView($itemtype)
        );
    }

    public function testAllAssetsGroupInChargeSearchOption(): void
    {
        $this->login();

        // Get search options for AllAssets
        $search_options = \Search::getOptions('AllAssets');

        // Verify that option 49 (Group in charge) exists and has correct structure
        $this->assertArrayHasKey(49, $search_options);

        $group_option = $search_options[49];

        // Verify basic structure
        $this->assertEquals('glpi_groups', $group_option['table']);
        $this->assertEquals('completename', $group_option['field']);
        $this->assertEquals('groups_id', $group_option['linkfield']);
        $this->assertEquals(__('Group in charge'), $group_option['name']);
        $this->assertEquals('dropdown', $group_option['datatype']);

        // Verify it uses the new glpi_groups_items relationship structure
        $this->assertArrayHasKey('joinparams', $group_option);
        $this->assertArrayHasKey('beforejoin', $group_option['joinparams']);

        $beforejoin = $group_option['joinparams']['beforejoin'];
        $this->assertEquals('glpi_groups_items', $beforejoin['table']);
        $this->assertEquals('itemtype_item', $beforejoin['joinparams']['jointype']);

        // Verify it targets GROUP_TYPE_TECH specifically
        $this->assertArrayHasKey('condition', $beforejoin['joinparams']);
        $condition = $beforejoin['joinparams']['condition'];
        $this->assertArrayHasKey('NEWTABLE.type', $condition);
        $this->assertEquals(\Group_Item::GROUP_TYPE_TECH, $condition['NEWTABLE.type']);

        // Verify additional configuration
        $this->assertTrue($group_option['forcegroupby']);
        $this->assertFalse($group_option['massiveaction']);
        $this->assertEquals(['is_assign' => 1], $group_option['condition']);
    }
}
