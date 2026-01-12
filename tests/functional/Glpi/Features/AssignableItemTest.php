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

use Glpi\DBAL\QueryExpression;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Tests\DbTestCase;
use Group;
use Group_Item;
use PHPUnit\Framework\Attributes\DataProvider;

class AssignableItemTest extends DbTestCase
{
    public static function itemtypeProvider(): iterable
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI['assignable_types'] as $itemtype) {
            yield $itemtype => [
                'class' => $itemtype,
            ];
        }
    }

    /**
     * @return iterable<array{class: class-string<AssignableItemInterface>, field: "groups_id"|"groups_id_tech"}>
     */
    public static function itemtypeAndGroupFieldProvider(): iterable
    {
        foreach (self::itemtypeProvider() as $itemtype) {
            yield [
                'item_type' => $itemtype['class'],
                'field' => 'groups_id',
            ];

            yield [
                'item_type' => $itemtype['class'],
                'field' => 'groups_id_tech',
            ];
        }
    }

    /**
     * @param class-string<AssignableItem> $class
 */
    #[DataProvider('itemtypeProvider')]
    public function testClassUsesTrait(string $class): void
    {
        // class_uses() doesn't match traits in parents -> $parent_has_trait() does it.
        $has_trait = static fn($_class) => in_array(AssignableItem::class, class_uses($_class), true);
        $parent_has_trait = fn($_class) => array_reduce(
            class_parents($_class),
            static fn($result, $_class) => $result || $has_trait($_class),
            false,
        );
        $this->assertTrue($has_trait($class) || $parent_has_trait($class));
    }

    /**
     * Test adding an item with the groups_id/groups_id_tech field as an array and null.
     * Test updating an item with the groups_id/groups_id_tech field as an array and null.
     *
     * @param class-string<AssignableItem> $class
     */
    #[DataProvider('itemtypeProvider')]
    public function testAddAndUpdateMultipleGroups(string $class): void
    {
        $this->login(); // login to bypass some rights checks (e.g. on domain records)

        $input = $this->getMinimalCreationInput($class);

        $item_1 = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__ . ' 1',
                'groups_id'            => [1, 2],
                'groups_id_tech'       => [3],
            ]
        );
        $this->assertEqualsCanonicalizing([1, 2], $item_1->fields['groups_id']);
        $this->assertEqualsCanonicalizing([3], $item_1->fields['groups_id_tech']);

        $item_2 = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__ . ' 2',
                'groups_id'            => null,
                'groups_id_tech'       => null,
            ],
            [
                // groups_id, groups_id_tech are set as empty array, not null
                'groups_id',
                'groups_id_tech',
            ]
        );
        $this->assertEquals([], $item_2->fields['groups_id']);
        $this->assertEquals([], $item_2->fields['groups_id_tech']);

        // Update both items. Asset 1 will have the groups set to null and item 2 will have the groups set to an array.
        $updated = $item_1->update(['id' => $item_1->getID(), 'groups_id' => null, 'groups_id_tech' => null]);
        $this->assertTrue($updated);
        $this->assertEquals([], $item_1->fields['groups_id']);
        $this->assertEquals([], $item_1->fields['groups_id_tech']);

        $updated = $item_2->update(['id' => $item_2->getID(), 'groups_id' => [5, 6], 'groups_id_tech' => [7]]);
        $this->assertTrue($updated);
        $this->assertEqualsCanonicalizing([5, 6], $item_2->fields['groups_id']);
        $this->assertEqualsCanonicalizing([7], $item_2->fields['groups_id_tech']);

        // Test updating array to array
        $updated = $item_2->update(['id' => $item_2->getID(), 'groups_id' => [1, 2], 'groups_id_tech' => [4, 5]]);
        $this->assertTrue($updated);
        $this->assertEqualsCanonicalizing([1, 2], $item_2->fields['groups_id']);
        $this->assertEqualsCanonicalizing([4, 5], $item_2->fields['groups_id_tech']);
    }

    /**
     * Assigning a group to an asset does not preserve previous associated groups (when previous groups_id not passed in input)
     *
     * Scenario :
     * - create an asset associated with a group
     * - update the asset to associate with another group (without first group in input)
     * -> only last group is associated
    */
    #[DataProvider('itemtypeAndGroupFieldProvider')]
    public function testAssignGroupRemovePreviousData(string $item_type, string $field): void
    {
        // --- arrange - create 2 groups
        $this->login();
        $group_type = match ($field) {
            'groups_id_tech' => Group_Item::GROUP_TYPE_TECH,
            'groups_id' => Group_Item::GROUP_TYPE_NORMAL,
        };

        $group_1 = $this->createItem(Group::class, $this->getMinimalCreationInput(Group::class));
        $group_2 = $this->createItem(Group::class, $this->getMinimalCreationInput(Group::class));

        $asset = $this->createItem(
            $item_type,
            [
                $field => [$group_1->getID()],
            ] + $this->getMinimalCreationInput($item_type),
            ['name'] // name for Item_DeviceSimcard at least
        );

        // --- act - update asset
        $this->updateItem(
            $item_type,
            $asset->getID(),
            [$field => [$group_2->getID()]] + $this->getMinimalCreationInput($item_type),
            ['name'] // name for Item_DeviceSimcard at least
        );

        // --- assert - check that the asset has the tech groups assigned
        $this->assertEquals(
            1,
            countElementsInTable(
                Group_Item::getTable(),
                [   'groups_id'  =>  [$group_2->getID()],
                    'items_id'   => $asset->getID(),
                    'itemtype'   => $asset::class,
                ]
            )
        );
    }

    /**
     * Test the loading item which still have integer values for groups_id/groups_id_tech (0 for no group).
     * The value should be automatically normalized to an array. If the group was '0', the array should be empty.
     *
     * @param class-string<AssignableItem> $class
     */
    #[DataProvider('itemtypeProvider')]
    public function testLoadGroupsFromDb(string $class): void
    {
        global $DB;

        $input = $this->getMinimalCreationInput($class);

        $item = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__,
            ]
        );
        $this->assertEquals([], $item->fields['groups_id']);
        $this->assertEquals([], $item->fields['groups_id_tech']);

        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 1,
                'type'      => Group_Item::GROUP_TYPE_NORMAL,
            ],
        );
        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 2,
                'type'      => Group_Item::GROUP_TYPE_TECH,
            ],
        );

        $this->assertTrue($item->getFromDB($item->getID()));
        $this->assertEqualsCanonicalizing([1], $item->fields['groups_id']);
        $this->assertEqualsCanonicalizing([2], $item->fields['groups_id_tech']);

        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 3,
                'type'      => Group_Item::GROUP_TYPE_NORMAL,
            ],
        );
        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 4,
                'type'      => Group_Item::GROUP_TYPE_TECH,
            ],
        );
        $this->assertTrue($item->getFromDB($item->getID()));
        $this->assertEqualsCanonicalizing([1, 3], $item->fields['groups_id']);
        $this->assertEqualsCanonicalizing([2, 4], $item->fields['groups_id_tech']);
    }

    /**
     * An empty item should have the groups_id/groups_id_tech fields initialized as an empty array.
     *
     * @param class-string<AssignableItem> $class
     */
    #[DataProvider('itemtypeProvider')]
    public function testGetEmpty(string $class): void
    {
        $item = new $class();
        $this->assertTrue($item->getEmpty());
        $this->assertEquals([], $item->fields['groups_id']);
        $this->assertEquals([], $item->fields['groups_id_tech']);
    }

    /**
     * Check that adding and updating an item with groups_id/groups_id_tech as an integer still works (minor BC, mainly for API scripts).
     *
     * @param class-string<AssignableItem> $class
     */
    #[DataProvider('itemtypeProvider')]
    public function testAddUpdateWithIntGroups(string $class): void
    {
        $this->login(); // login to bypass some rights checks (e.g. on domain records)

        $input = $this->getMinimalCreationInput($class);

        $item = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__,
                'groups_id'            => 1,
                'groups_id_tech'       => 2,
            ],
            [
                // groups_id & groups_id_tech are returned as array
                'groups_id',
                'groups_id_tech',
            ]
        );

        $this->assertEqualsCanonicalizing([1], $item->fields['groups_id']);
        $this->assertEqualsCanonicalizing([2], $item->fields['groups_id_tech']);

        $updated = $item->update(['id' => $item->getID(), 'groups_id' => 3, 'groups_id_tech' => 4]);
        $this->assertTrue($updated);
        $this->assertEqualsCanonicalizing([3], $item->fields['groups_id']);
        $this->assertEqualsCanonicalizing([4], $item->fields['groups_id_tech']);
    }

    public function testGenericAsset(): void
    {
        $class = $this->initAssetDefinition()->getAssetClassName();

        $this->testAddAndUpdateMultipleGroups($class);
        $this->testLoadGroupsFromDb($class);
        $this->testGetEmpty($class);
        $this->testAddUpdateWithIntGroups($class);
    }

    /**
     * @param class-string<AssignableItem> $class
     * @return void
     */
    #[DataProvider('itemtypeProvider')]
    public function testGetAssignableVisiblityCriteria(string $class): void
    {
        global $DB, $CFG_GLPI;

        $this->logOut();

        // Bypassing rights should return all items
        $this->assertEquals(
            [[new QueryExpression('1')]],
            \Session::callAsSystem(static function () use ($class) {
                return array_values($class::getAssignableVisiblityCriteria());
            })
        );

        // Cron context should return all items
        $_SESSION["glpicronuserrunning"] = 1;
        $this->assertEquals([[new QueryExpression('1')]], array_values($class::getAssignableVisiblityCriteria()));

        // Test getAssignableVisiblityCriteriaForHelpdesk
        if (!in_array($class, $CFG_GLPI['ticket_types'])) {
            return;
        }
        $this->login('post-only', 'postonly');
        // No helpdesk_item_types = No items
        // Need to modify the profile directly in the DB because the check doesn't use the session info for some reason
        $DB->update(
            'glpi_profiles',
            ['helpdesk_item_type' => '[]'],
            ['id' => $_SESSION['glpiactiveprofile']['id']]
        );
        $this->assertEquals(
            [[new QueryExpression('0')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        // All helpdesk_hardware = all items
        $DB->update(
            'glpi_profiles',
            [
                'helpdesk_item_type' => exportArrayToDB(array_keys(\Profile::getHelpdeskItemtypes())),
                'helpdesk_hardware' => 2 ** \CommonITILObject::HELPDESK_ALL_HARDWARE,
            ],
            ['id' => $_SESSION['glpiactiveprofile']['id']]
        );
        $this->assertEquals(
            [[new QueryExpression('1')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        // My hardware only = items assigned to the user
        $DB->update(
            'glpi_profiles',
            ['helpdesk_hardware' => 2 ** \CommonITILObject::HELPDESK_MY_HARDWARE],
            ['id' => $_SESSION['glpiactiveprofile']['id']]
        );
        $this->assertNotEquals(
            [[new QueryExpression('0')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        $this->assertNotEquals(
            [[new QueryExpression('1')]],
            array_values($class::getAssignableVisiblityCriteria())
        );

        // Test getAssignableVisiblityCriteriaForCentral
        $this->login();
        $_SESSION['glpiactiveprofile'][$class::$rightname] = 0;
        $this->assertEquals(
            [[new QueryExpression('0')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        $_SESSION['glpiactiveprofile'][$class::$rightname] = READ;
        $this->assertEquals(
            [[new QueryExpression('1')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        $_SESSION['glpiactiveprofile'][$class::$rightname] = READ_ASSIGNED;
        $this->assertNotEquals(
            [[new QueryExpression('0')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        $this->assertNotEquals(
            [[new QueryExpression('1')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        $_SESSION['glpiactiveprofile'][$class::$rightname] = READ_OWNED;
        $this->assertNotEquals(
            [[new QueryExpression('0')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
        $this->assertNotEquals(
            [[new QueryExpression('1')]],
            array_values($class::getAssignableVisiblityCriteria())
        );
    }
}
