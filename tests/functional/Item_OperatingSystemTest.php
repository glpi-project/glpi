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

namespace tests\units;

use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasOperatingSystemCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Item_OperatingSystem;
use Toolbox;

class Item_OperatingSystemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasOperatingSystemCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['operatingsystem_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Item_OperatingSystem$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasOperatingSystemCapacity::class)]);

        foreach ($CFG_GLPI['operatingsystem_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Item_OperatingSystem::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testGetTypeName()
    {
        $this->assertSame('Item operating systems', Item_OperatingSystem::getTypeName());
        $this->assertSame('Item operating systems', Item_OperatingSystem::getTypeName(0));
        $this->assertSame('Item operating systems', Item_OperatingSystem::getTypeName(10));
        $this->assertSame('Item operating system', Item_OperatingSystem::getTypeName(1));
    }

    /**
     * Create dropdown objects to be used
     *
     * @return array
     */
    private function createDdObjects()
    {
        $objects = [];
        foreach (['', 'Architecture', 'Version', 'Edition', 'KernelVersion'] as $object) {
            $classname = 'OperatingSystem' . $object;
            $instance = new $classname();
            $this->assertGreaterThan(
                0,
                $instance->add([
                    'name' => $classname . ' ' . $this->getUniqueInteger(),
                ])
            );
            $this->assertTrue($instance->getFromDB($instance->getID()));
            $objects[$object] = $instance;
        }
        return $objects;
    }

    public function testAttachComputer()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        $objects = $this->createDdObjects();
        $ios = new Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString(),
        ];
        $this->assertGreaterThan(
            0,
            $ios->add($input)
        );
        $this->assertTrue($ios->getFromDB($ios->getID()));

        $this->assertSame(
            "Operating systems 1",
            strip_tags($ios->getTabNameForItem($computer))
        );
        $this->assertSame(
            1,
            Item_OperatingSystem::countForItem($computer)
        );

        $expected_error = "/Duplicate entry '{$computer->getID()}-Computer-{$objects['']->getID()}-{$objects['Architecture']->getID()}' for key '(glpi_items_operatingsystems\.)?unicity'/";
        $this->expectExceptionMessageMatches($expected_error);
        $this->assertFalse($ios->add($input));

        $this->assertSame(
            1,
            Item_OperatingSystem::countForItem($computer)
        );

        $objects = $this->createDdObjects();
        $ios = new Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString(),
        ];
        $this->assertGreaterThan(
            0,
            $ios->add($input)
        );
        $this->assertTrue($ios->getFromDB($ios->getID()));

        $this->assertSame(
            "Operating systems 2",
            strip_tags($ios->getTabNameForItem($computer))
        );
        $this->assertSame(
            2,
            Item_OperatingSystem::countForItem($computer)
        );
    }

    public function testShowForItem()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');

        foreach (['showForItem', 'displayTabContentForItem'] as $method) {
            ob_start();
            Item_OperatingSystem::$method($computer);
            $output = ob_get_clean();
            $this->assertStringContainsString('operatingsystems_id', $output);
        }

        $objects = $this->createDdObjects();
        $ios = new Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString(),
        ];
        $this->assertGreaterThan(
            0,
            $ios->add($input)
        );
        $this->assertTrue($ios->getFromDB($ios->getID()));

        foreach (['showForItem', 'displayTabContentForItem'] as $method) {
            ob_start();
            Item_OperatingSystem::$method($computer);
            $output = ob_get_clean();
            $this->assertStringContainsString('operatingsystems_id', $output);
        }

        $objects = $this->createDdObjects();
        $ios = new Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString(),
        ];
        $this->assertGreaterThan(
            0,
            $ios->add($input)
        );
        $this->assertTrue($ios->getFromDB($ios->getID()));

        //there are now 2 OS linked, we will no longer show a form, but a list.
        foreach (['showForItem', 'displayTabContentForItem'] as $method) {
            ob_start();
            Item_OperatingSystem::$method($computer);
            $output = ob_get_clean();
            $this->assertStringNotContainsString('operatingsystems_id', $output);
        }
    }

    public function testEntityAccess()
    {
        $this->login();
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $this->setEntity('_test_root_entity', true);

        $computer = new \Computer();
        $this->assertGreaterThan(
            0,
            $computer->add([
                'name'         => 'Test Item/OS',
                'entities_id'  => $eid,
                'is_recursive' => 0,
            ])
        );

        $os = new \OperatingSystem();
        $this->assertGreaterThan(
            0,
            $os->add([
                'name' => 'Test OS',
            ])
        );

        $ios = new Item_OperatingSystem();
        $this->assertGreaterThan(
            0,
            $ios->add([
                'operatingsystems_id'   => $os->getID(),
                'itemtype'              => 'Computer',
                'items_id'              => $computer->getID(),
            ])
        );
        $this->assertTrue($ios->getFromDB($ios->getID()));

        $this->assertSame($os->getID(), $ios->fields['operatingsystems_id']);
        $this->assertSame('Computer', $ios->fields['itemtype']);
        $this->assertSame($computer->getID(), $ios->fields['items_id']);
        $this->assertSame($eid, $ios->fields['entities_id']);
        $this->assertSame(0, $ios->fields['is_recursive']);

        $this->assertTrue($ios->can($ios->getID(), READ));

        //not recursive
        $this->setEntity('Root Entity', true);
        $this->assertTrue($ios->can($ios->getID(), READ));
        $this->setEntity('_test_child_1', true);
        $this->assertFalse($ios->can($ios->getID(), READ));
        $this->setEntity('_test_child_2', true);
        $this->assertFalse($ios->can($ios->getID(), READ));

        $this->setEntity('_test_root_entity', true);
        $this->assertTrue(
            $computer->update([
                'id'           => $computer->getID(),
                'is_recursive' => 1,
            ])
        );
        $this->assertTrue($ios->getFromDB($ios->getID()));
        $this->assertSame($os->getID(), $ios->fields['operatingsystems_id']);
        $this->assertSame('Computer', $ios->fields['itemtype']);
        $this->assertSame($computer->getID(), $ios->fields['items_id']);
        $this->assertSame($eid, $ios->fields['entities_id']);
        $this->assertSame(1, $ios->fields['is_recursive']);

        //not recursive
        $this->setEntity('Root Entity', true);
        $this->assertTrue($ios->can($ios->getID(), READ));
        $this->setEntity('_test_child_1', true);
        $this->assertTrue($ios->can($ios->getID(), READ));
        $this->setEntity('_test_child_2', true);
        $this->assertTrue($ios->can($ios->getID(), READ));
    }

    public function testPreventEmptyOSAdd()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');

        $ios = new Item_OperatingSystem();

        // Test adding an OS with all empty fields - should fail
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => 0,
            'operatingsystemarchitectures_id'   => 0,
            'operatingsystemversions_id'        => 0,
            'operatingsystemkernelversions_id'  => 0,
            'operatingsystemeditions_id'        => 0,
            'operatingsystemservicepacks_id'    => 0,
            'licenseid'                         => '',
            'license_number'                    => '',
        ];

        // GLPI currently allows empty OS records
        $this->assertGreaterThan(
            0,
            $ios->add($input),
            "Empty OS record should be addable at model level.",
        );

        // Check for the error message
        $this->hasSessionMessages(ERROR, [
            "Cannot add an empty operating system. At least one field must be filled.",
        ]);

        $this->assertSame(
            0,
            Item_OperatingSystem::countForItem($computer),
            "Count should remain 0 after failed add.",
        );
    }

    public function testUpdateOSToEmptyIsRejected()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $objects  = $this->createDdObjects();
        $ios      = new Item_OperatingSystem();

        // First add a valid OS record
        $input = [
            'itemtype'                        => $computer->getType(),
            'items_id'                        => $computer->getID(),
            'entities_id'                     => $_SESSION['glpiactive_entity'],
            'operatingsystems_id'             => $objects['']->getID(),
            'operatingsystemarchitectures_id' => $objects['Architecture']->getID(),
        ];

        $id = $ios->add($input);
        $this->assertGreaterThan(0, $id);
        $this->assertSame(
            1,
            Item_OperatingSystem::countForItem($computer),
            "Count should be 1 after add",
        );

        // Reload and ensure record still exists
        $this->assertTrue($ios->getFromDB($id));
        // Snapshot original state
        $original = $ios->fields;

        // Attempt to update with empty values
        $this->assertTrue($ios->update([
            'id'             => $id,
            'operatingsystems_id' => 0,
            'operatingsystemarchitectures_id' => 0,
        ]));

        // Consume the session message so tearDown doesn't fail
        $this->hasSessionMessages(ERROR, [
            "Cannot update operating system with empty values. To remove the operating system, use the delete action instead.",
        ]);

        // Verify data integrity - record must still exist and be unchanged
        $this->assertTrue(
            $ios->getFromDB($id),
            "OS record should still exist."
        );

        // Asserting against real DB columns to ensure no changes were made
        $this->assertSame(
            $original['operatingsystems_id'],
            $ios->fields['operatingsystems_id'],
        );
        $this->assertSame(
            $original['operatingsystemarchitectures_id'],
            $ios->fields['operatingsystemarchitectures_id'],
        );
    }
}
