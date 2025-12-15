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

        // Verify we have results
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('rows', $result['data']);
        $this->assertGreaterThan(0, $result['data']['totalcount']);

        // Find our computer in the results
        $found_computer = false;
        foreach ($result['data']['rows'] as $row) {
            if (isset($row['AllAssets_1']['displayname']) &&
                strpos($row['AllAssets_1']['displayname'], 'Test Computer ' . __FUNCTION__) !== false) {

                $found_computer = true;

                // Verify the group is correctly displayed
                $this->assertArrayHasKey('AllAssets_49', $row);
                $this->assertStringContainsString(
                    'Test Tech Group ' . __FUNCTION__,
                    $row['AllAssets_49']['displayname']
                );
                break;
            }
        }

        $this->assertTrue($found_computer, 'Computer with technical group should be found in AllAssets search results');

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
}
