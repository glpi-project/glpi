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

use DatabaseInstance;
use Computer;
use Session;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDatabaseInstanceCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\GLPITestCase;
use Toolbox;

class DatabaseInstanceTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDatabaseInstanceCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['databaseinstance_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('DatabaseInstance$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDatabaseInstanceCapacity::class)]);

        foreach ($CFG_GLPI['databaseinstance_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            // FIXME DatabaseInstance must be a CommonDBChild to be clonable
            // $item = \getItemForItemtype($itemtype);
            // $this->assertContains(DatabaseInstance::class, $item->getCloneRelations(), $itemtype);
            $this->assertTrue(true);
        }
    }

    public function testDelete()
    {
        $instance = new DatabaseInstance();

        $instid = $instance->add([
            'name' => 'To be removed',
            'port' => 3306,
            'size' => 52000,
        ]);

        //check DB is created, and load it
        $this->assertGreaterThan(0, $instid);
        $this->assertTrue($instance->getFromDB($instid));

        //create databases
        for ($i = 0; $i < 5; ++$i) {
            $database = new \Database();
            $this->assertGreaterThan(
                0,
                $database->add([
                    'name'                   => 'Database ' . $i,
                    'databaseinstances_id'   => $instid,
                ])
            );
        }
        $this->assertSame(5, countElementsInTable(\Database::getTable()));

        //test removal
        $this->assertTrue($instance->delete(['id' => $instid], 1));
        $this->assertFalse($instance->getFromDB($instid));

        //ensure databases has been dropped aswell
        $this->assertSame(0, countElementsInTable(\Database::getTable()));
    }

    public function testGetInventoryAgent(): void
    {
        $computer = $this->createItem(
            \Computer::class,
            [
                'name'        => 'test computer',
                'entities_id' => 0,
            ]
        );
        $dbinstance = $this->createItem(
            DatabaseInstance::class,
            [
                'name'     => 'test database',
                'itemtype' => \Computer::class,
                'items_id' => $computer->fields['id'],
            ]
        );

        $db_agent = $dbinstance->getInventoryAgent();
        $this->assertNull($db_agent);

        $agenttype_id = getItemByTypeName(\AgentType::class, 'Core', true);

        $agent1 = $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => DatabaseInstance::class,
                'items_id'     => $dbinstance->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('yesterday')),
            ]
        );

        $agent2 = $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => DatabaseInstance::class,
                'items_id'     => $dbinstance->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('last week')),
            ]
        );

        $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \Computer::class,
                'items_id'     => $computer->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('last hour')),
            ]
        );

        $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \Computer::class,
                'items_id'     => $computer->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('yesterday')),
            ]
        );

        // most recent agent directly linked
        $db_agent = $dbinstance->getInventoryAgent();
        $this->assertInstanceOf(\Agent::class, $db_agent);
        $this->assertEquals($agent1->fields, $db_agent->fields);

        $this->assertTrue($agent1->delete(['id' => $agent1->fields['id']]));

        // most recent agent directly linked
        $db_agent = $dbinstance->getInventoryAgent();
        $this->assertInstanceOf(\Agent::class, $db_agent);
        $this->assertEquals($agent2->fields, $db_agent->fields);

        $this->assertTrue($agent2->delete(['id' => $agent2->fields['id']]));

        // most recent agent found from linked item, as there is no more agent linked directly
        $db_agent = $dbinstance->getInventoryAgent();
        $this->assertInstanceOf(\Agent::class, $db_agent);
        $computer_agent = $computer->getInventoryAgent();
        $this->assertInstanceOf(\Agent::class, $computer_agent);
        $this->assertEquals($computer_agent->fields, $db_agent->fields);
    }

    public function testLinkDatabaseInstanceToComputer(): void
    {
        $this->login();

        // Create a Computer
        $computer = new \Computer();
        $computer_id = $computer->add([
            'name' => 'Test Computer for DB Link',
            'entities_id' => 0,
        ]);
        $this->assertIsInt($computer_id);

        // Create a DatabaseInstance (unlinked)
        $db_instance = new \DatabaseInstance();
        $db_instance_id = $db_instance->add([
            'name' => 'Test DB Instance Unlinked',
            'is_active' => 1,
            'entities_id' => 0,
        ]);
        $this->assertIsInt($db_instance_id);

        // Verify initially not linked
        $this->boolean($db_instance->getFromDB($db_instance_id))->isTrue();
        $this->integer($db_instance->fields['items_id'])->isIdenticalTo(0);
        $this->string($db_instance->fields['itemtype'])->isEmpty();

        // Perform the link (update)
        // This simulates the action performed by the form added in showInstances
        $success = $db_instance->update([
            'id' => $db_instance_id,
            'items_id' => $computer_id,
            'itemtype' => \Computer::class,
        ]);
        $this->boolean($success)->isTrue();

        // Verify they are linked
        $this->boolean($db_instance->getFromDB($db_instance_id))->isTrue();
        $this->integer($db_instance->fields['items_id'])->isIdenticalTo($computer_id);
        $this->string($db_instance->fields['itemtype'])->isIdenticalTo(Computer::class);
    }

    public function testDissociateDatabaseInstanceFromComputer(): void
    {
        $this->login();

        // Create a Computer
        $computer = new \Computer();
        $computer_id = $computer->add([
            'name' => 'Test Computer for DB Dissociate',
            'entities_id' => 0,
        ]);
        $this->assertIsInt($computer_id);

        // Create a DatabaseInstance linked to the computer
        $db_instance = new \DatabaseInstance();
        $db_instance_id = $db_instance->add([
            'name' => 'Test DB Instance Linked',
            'is_active' => 1,
            'entities_id' => 0,
            'items_id' => $computer_id,
            'itemtype' => \Computer::class,
        ]);
        $this->assertIsInt($db_instance_id);

        // Verify initially linked
        $this->boolean($db_instance->getFromDB($db_instance_id))->isTrue();
        $this->integer($db_instance->fields['items_id'])->isIdenticalTo($computer_id);
        $this->string($db_instance->fields['itemtype'])->isIdenticalTo(Computer::class);

        // Perform the dissociation (update to 0/empty)
        // This mimics the logic inside processMassiveActionsForOneItemtype 'dissociate' case
        $success = $db_instance->update([
            'id' => $db_instance_id,
            'items_id' => 0,
            'itemtype' => '',
        ]);
        $this->boolean($success)->isTrue();

        // Verify they are not linked
        $this->boolean($db_instance->getFromDB($db_instance_id))->isTrue();
        $this->integer($db_instance->fields['items_id'])->isIdenticalTo(0);
        $this->string($db_instance->fields['itemtype'])->isEmpty();
    }
}
