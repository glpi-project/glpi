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

namespace tests\units\Glpi\Asset;

use Change_Item;
use DbTestCase;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\AbstractCapacity;
use Glpi\Asset\CapacityConfig;
use GlpiPlugin\Tester\Asset\Capacity\HasFooCapacity;
use GlpiPlugin\Tester\Asset\Foo;
use Item_Problem;
use Item_Ticket;
use Profile;

class AssetDefinitionManagerTest extends DbTestCase
{
    public function testLoadConcreteClass(): void
    {
        // use a loop to simulate multiple classes
        $mapping = [];
        for ($i = 0; $i < 5; $i++) {
            $system_name = $this->getUniqueString();
            $mapping['Glpi\\CustomAsset\\' . $system_name . 'Asset'] = $this->initAssetDefinition($system_name);
        }

        foreach ($mapping as $expected_classname => $definition) {
            $this->assertTrue(class_exists($expected_classname));
            $this->assertEquals($definition->fields, $expected_classname::getDefinition()->fields);
        }
    }

    public function testAutoloader(): void
    {
        $this->initAssetDefinition('Test_Item123'); // use a name with numbers and underscore, to validate it works as expected

        $this->assertTrue(\class_exists('Glpi\CustomAsset\Test_Item123Asset'));
        $this->assertTrue(\class_exists('Glpi\CustomAsset\Test_Item123AssetModel'));
        $this->assertTrue(\class_exists('Glpi\CustomAsset\Test_Item123AssetType'));
        $this->assertTrue(\class_exists('Glpi\CustomAsset\RuleDictionaryTest_Item123AssetModelCollection'));
        $this->assertTrue(\class_exists('Glpi\CustomAsset\RuleDictionaryTest_Item123AssetModel'));
        $this->assertTrue(\class_exists('Glpi\CustomAsset\RuleDictionaryTest_Item123AssetTypeCollection'));
        $this->assertTrue(\class_exists('Glpi\CustomAsset\RuleDictionaryTest_Item123AssetType'));
    }

    /**
     * Ensure all asset types are registered in the ticket types configuration.
     *
     * @return void
     */
    public function testTicketTypeConfigRegistration(): void
    {
        global $CFG_GLPI;

        $this->login();

        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // Itemtype should be registered in $CFG_GLPI["ticket_types"]
        $this->assertContains($class, $CFG_GLPI["ticket_types"]);
    }

    protected function testCommonITILTabRegistrationProvider(): iterable
    {
        $this->login();

        // Note: the asset is not yet registered in `helpdesk_item_type` for our
        // super admin profile
        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // Create a test subject without any linked ITIL items
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        yield [
            $definition,
            $subject,
            [],
        ];

        // Link subject to ticket
        $ticket = $this->createItem('Ticket', [
            'name'    => 'Test ticket',
            'content' => 'Test ticket content',
        ]);
        $this->createItem(Item_Ticket::class, [
            'itemtype'   => $subject::getType(),
            'items_id'   => $subject->getID(),
            'tickets_id' => $ticket->getID(),
        ]);
        yield [
            $definition,
            $subject,
            ["Item_Ticket$1"],
        ];

        // Link subject to problem
        $problem = $this->createItem('Problem', [
            'name'    => 'Test problem',
            'content' => 'Test problem content',
        ]);
        $this->createItem(Item_Problem::class, [
            'itemtype'    => $subject::getType(),
            'items_id'    => $subject->getID(),
            'problems_id' => $problem->getID(),
        ]);
        yield [
            $definition,
            $subject,
            ["Item_Ticket$1", "Item_Problem$1"],
        ];

        // Link subject to change
        $change = $this->createItem('Change', [
            'name'    => 'Test change',
            'content' => 'Test change content',
        ]);
        $this->createItem(Change_Item::class, [
            'itemtype'    => $subject::getType(),
            'items_id'    => $subject->getID(),
            'changes_id'  => $change->getID(),
        ]);
        yield [
            $definition,
            $subject,
            ["Item_Ticket$1", "Item_Problem$1", "Change_Item$1"],
        ];

        // Create a separate definition to test rights as tabs are not removed
        // once they are defined until the page is reload
        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // Create a test subject without any linked ITIL items
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        yield [
            $definition,
            $subject,
            [],
        ];

        // Enable the asset in the `helpdesk_item_type` parameter of the current
        // profile
        $profile = Profile::getById($_SESSION['glpiactiveprofile']['id']);
        $itemtypes = importArrayFromDB($profile->fields["helpdesk_item_type"]);
        $itemtypes[] = $class;
        $this->updateItem(Profile::class, $profile->getID(), [
            'helpdesk_item_type' => $itemtypes,
        ], ['helpdesk_item_type']);

        yield [
            $definition,
            $subject,
            ["Item_Ticket$1", "Item_Problem$1", "Change_Item$1"],
        ];
    }

    /**
     * Test that the "Tickets", "Problems" and "Changes" tabs are registered
     * if the user is allowed to see them OR if the asset has linked ITIL items.
     *
     * @return void
     */
    public function testCommonITILTabRegistration(): void
    {
        foreach ($this->testCommonITILTabRegistrationProvider() as $row) {
            $definition = $row[0];
            $asset = $row[1];
            $expected_tabs = $row[2];

            // Get all tabs
            $tabs = $asset->defineAllTabs();

            // Remove main tab
            array_shift($tabs);

            // Keep only keys
            $tabs = array_keys($tabs);

            $this->assertEquals($expected_tabs, $tabs);
        }
    }

    public function testRegisteredTesterPluginCapacity(): void
    {
        // Register the capacity
        $asset_definition_manager = AssetDefinitionManager::getInstance();
        $asset_definition_manager->registerCapacity(new HasFooCapacity());

        // Assert that the registered capacity is listed in the available capacities
        $available_capacities = $asset_definition_manager->getAvailableCapacities();
        $this->assertCount(1, \array_filter($available_capacities, fn($capactity) => $capactity instanceof HasFooCapacity));

        // Assert that the registered capacity instance can be retrieved
        $this->assertInstanceOf(HasFooCapacity::class, $asset_definition_manager->getCapacity(HasFooCapacity::class));

        // Create an asset
        $definition = $this->initAssetDefinition(capacities: [new Capacity(name: HasFooCapacity::class)]);
        $asset_classname = $definition->getAssetClassName();
        /* @var \Glpi\Asset\Asset $asset */
        $asset = new $asset_classname();

        // Assert that the `onObjectInstanciation()` method is correctly executed
        $this->assertArrayHasKey('_added_by_hasfoocapacity', $asset->fields);
        $this->assertEquals('abc', $asset->fields['_added_by_hasfoocapacity']);

        // Assert that the capacity search options are added to the asset
        $search_options = $asset->searchOptions();

        $this->assertArrayHasKey('foo', $search_options);
        $this->assertEquals(
            [
                'name' => 'Foo',
            ],
            $search_options['foo']
        );

        $this->assertArrayHasKey(123456, $search_options);
        $this->assertEquals(
            [
                'table'     => 'glpi_plugin_tester_assets_foos',
                'field'     => 'name',
                'name'      => 'Name',
                'datatype'  => 'itemlink',
            ],
            $search_options[123456]
        );

        // Assert that the clone relations are added to the asset
        $this->assertContains(Foo::class, $asset->getCloneRelations());
    }

    public function testRegisteredMockedCapacity(): void
    {
        $definition = $this->initAssetDefinition();
        $asset_classname = $definition->getAssetClassName();

        $capacity_implementation = $this->createMock(AbstractCapacity::class);
        $capacity_config = new CapacityConfig(['foo' => 'bar']);

        // Register the capacity.
        $manager = AssetDefinitionManager::getInstance();
        $manager->registerCapacity($capacity_implementation);

        $this->assertContains($capacity_implementation, $manager->getAvailableCapacities());
        $this->assertEquals($capacity_implementation, $manager->getCapacity($capacity_implementation::class));

        // `onCapacityEnabled` method is executed when the capacity is enabled.
        $capacity_implementation->expects($this->once())
            ->method('onCapacityEnabled')
            ->with($asset_classname, $this->equalTo($capacity_config));
        $this->enableCapacity($definition, $capacity_implementation::class, $capacity_config);

        // `onObjectInstanciation` method is executed when an asset constructor is used.
        $capacity_implementation->expects($this->once())
            ->method('onObjectInstanciation')
            ->with($this->isInstanceOf($asset_classname), $this->equalTo($capacity_config));
        new $asset_classname();

        // `onCapacityUpdated` method is executed when the capacity config is updated.
        $new_config = new CapacityConfig(['bar' => 'baz']);
        $capacity_implementation->expects($this->once())
            ->method('onCapacityUpdated')
            ->with($asset_classname, $this->equalTo($capacity_config), $this->equalTo($new_config));
        $this->enableCapacity($definition, $capacity_implementation::class, $new_config);

        // `onCapacityDisabled` method is executed when the capacity is disabled.
        $capacity_implementation->expects($this->once())
            ->method('onCapacityDisabled')
            ->with($asset_classname, $this->equalTo($new_config));
        $this->disableCapacity($definition, $capacity_implementation::class);
    }
}
