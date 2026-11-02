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

    /**
     * Test that AllAssets search results work correctly with Group in charge option (field 49)
     */
    public function testAllAssetsGroupInChargeSearchResults(): void
    {
        $this->login();

        // Create a technical group
        $group = $this->createItem(
            \Group::class,
            [
                'name'       => 'Test Tech Group ' . __FUNCTION__,
                'is_assign'  => 1,
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        // Create a computer
        $computer = $this->createItem(
            \Computer::class,
            [
                'name'        => 'Test Computer ' . __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        // Assign the technical group to the computer
        $this->createItem(
            \Group_Item::class,
            [
                'groups_id'   => $group->getID(),
                'itemtype'    => \Computer::class,
                'items_id'    => $computer->getID(),
                'type'        => \Group_Item::GROUP_TYPE_TECH,
            ]
        );

        // Test search by group ID - this should not throw SQL error
        $result = \Search::getDatas(
            \AllAssets::class,
            [
                'criteria' => [
                    [
                        'field'      => 49, // Group in charge
                        'searchtype' => 'equals',
                        'value'      => $group->getID(),
                    ],
                ],
                'forcetoview' => [1, 49], // Name and Group in charge
            ]
        );

        // Verify we have exactly one result
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('rows', $result['data']);
        $this->assertEquals(1, $result['data']['totalcount'], 'Should find exactly one computer with this technical group');

        // Get the single result
        $row = $result['data']['rows'][0];

        // Verify the computer name
        $this->assertArrayHasKey('AllAssets_1', $row);
        $this->assertStringContainsString('Test Computer ' . __FUNCTION__, $row['AllAssets_1']['displayname']);

        // Verify the group is correctly displayed
        $this->assertArrayHasKey('AllAssets_49', $row);
        $this->assertStringContainsString(
            'Test Tech Group ' . __FUNCTION__,
            $row['AllAssets_49']['displayname']
        );

        // Test search by group name - this should also work without SQL error
        $result2 = \Search::getDatas(
            \AllAssets::class,
            [
                'criteria' => [
                    [
                        'field'      => 49, // Group in charge
                        'searchtype' => 'contains',
                        'value'      => 'Test Tech Group ' . __FUNCTION__,
                    ],
                ],
                'forcetoview' => [1, 49],
            ]
        );

        // This search should also return results
        $this->assertArrayHasKey('data', $result2);
        $this->assertArrayHasKey('rows', $result2['data']);
        $this->assertGreaterThan(0, $result2['data']['totalcount']);

        // Test regression: ensure no SQL error occurs during search
        // The old bug would throw: "Unknown column 'glpi_computers.groups_id_tech' in 'field list'"
        // If we reach this point, the SQL was successful
        $this->assertTrue(true, 'Search completed without SQL error - fix is working');
    }

    public function testAllAssetsGroupSearchResults(): void
    {
        $this->login();

        $group = $this->createItem(
            \Group::class,
            [
                'name'          => 'Test Normal Group ' . __FUNCTION__,
                'is_itemgroup'  => 1,
                'entities_id'   => $this->getTestRootEntity(true),
            ]
        );

        $computer = $this->createItem(
            \Computer::class,
            [
                'name'        => 'Test Computer ' . __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );

        $this->createItem(
            \Group_Item::class,
            [
                'groups_id'   => $group->getID(),
                'itemtype'    => \Computer::class,
                'items_id'    => $computer->getID(),
                'type'        => \Group_Item::GROUP_TYPE_NORMAL,
            ]
        );

        $result = \Search::getDatas(
            \AllAssets::class,
            [
                'criteria' => [
                    [
                        'field'      => 71,
                        'searchtype' => 'equals',
                        'value'      => $group->getID(),
                    ],
                ],
                'forcetoview' => [1, 71],
            ]
        );

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('rows', $result['data']);
        $this->assertEquals(1, $result['data']['totalcount'], '1 pc found');

        $row = $result['data']['rows'][0];

        $this->assertArrayHasKey('AllAssets_1', $row);
        $this->assertStringContainsString('Test Computer ' . __FUNCTION__, $row['AllAssets_1']['displayname']);

        $this->assertArrayHasKey('AllAssets_71', $row);
        $this->assertStringContainsString(
            'Test Normal Group ' . __FUNCTION__,
            $row['AllAssets_71']['displayname']
        );

        $result2 = \Search::getDatas(
            \AllAssets::class,
            [
                'criteria' => [
                    [
                        'field'      => 71,
                        'searchtype' => 'contains',
                        'value'      => 'Test Normal Group ' . __FUNCTION__,
                    ],
                ],
                'forcetoview' => [1, 71],
            ]
        );

        $this->assertArrayHasKey('data', $result2);
        $this->assertArrayHasKey('rows', $result2['data']);
        $this->assertGreaterThan(0, $result2['data']['totalcount']);
    }

    public function testProfileRestrictionsApplied(): void
    {
        $this->login();

        $all_options = SearchOption::getOptionsForItemtype(\Ticket::class, true, false);
        $option_nums = array_filter(array_keys($all_options), 'is_int');
        $this->assertNotEmpty($option_nums);

        $excluded_num = reset($option_nums);

        $_SESSION['glpiactiveprofile']['excluded_searchoptions'] = ['Ticket' => [$excluded_num]];

        $restricted = SearchOption::getOptionsForItemtype(\Ticket::class);

        $this->assertTrue($restricted[$excluded_num]['nosearch'] ?? false);
        $this->assertTrue($restricted[$excluded_num]['nodisplay'] ?? false);

        $other_nums = array_filter($option_nums, fn($n) => $n !== $excluded_num);
        $other_num  = reset($other_nums);
        $this->assertFalse($restricted[$other_num]['nosearch'] ?? false);
        $this->assertFalse($restricted[$other_num]['nodisplay'] ?? false);
    }

    public function testProfileRestrictionsSkippedWhenDisabled(): void
    {
        $this->login();

        $all_options = SearchOption::getOptionsForItemtype(\Ticket::class, true, false);
        $option_nums = array_filter(array_keys($all_options), 'is_int');
        $excluded_num = reset($option_nums);

        $_SESSION['glpiactiveprofile']['excluded_searchoptions'] = ['Ticket' => [$excluded_num]];

        $unfiltered = SearchOption::getOptionsForItemtype(\Ticket::class, true, false);

        $this->assertFalse($unfiltered[$excluded_num]['nosearch'] ?? false);
        $this->assertFalse($unfiltered[$excluded_num]['nodisplay'] ?? false);
    }

    public function testNoRestrictionsWithoutExcludedOptions(): void
    {
        $this->login();

        $base = SearchOption::getOptionsForItemtype(\Ticket::class, true, false);

        $_SESSION['glpiactiveprofile']['excluded_searchoptions'] = [];

        $filtered = SearchOption::getOptionsForItemtype(\Ticket::class);

        foreach ($base as $key => $val) {
            if (!is_array($val) || count($val) <= 1) {
                continue;
            }
            $this->assertEquals(
                $val['nosearch'] ?? false,
                $filtered[$key]['nosearch'] ?? false,
                "Option $key nosearch should be unchanged with empty exclusions"
            );
            $this->assertEquals(
                $val['nodisplay'] ?? false,
                $filtered[$key]['nodisplay'] ?? false,
                "Option $key nodisplay should be unchanged with empty exclusions"
            );
        }
    }

    public function testGetActionsForNumericDatatypeIncludesComparisonOperators(): void
    {
        $this->login();

        $actions = SearchOption::getActionsFor(\Computer::class, 2);

        $this->assertArrayHasKey('morethan', $actions);
        $this->assertArrayHasKey('lessthan', $actions);
        $this->assertArrayHasKey('morethanorequal', $actions);
        $this->assertArrayHasKey('lessthanorequal', $actions);
        $this->assertSame(__('greater than'), $actions['morethan']);
        $this->assertSame(__('less than'), $actions['lessthan']);
        $this->assertSame(__('greater than or equal to'), $actions['morethanorequal']);
        $this->assertSame(__('less than or equal to'), $actions['lessthanorequal']);
    }
}
