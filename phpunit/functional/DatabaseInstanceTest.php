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

use DatabaseInstance;
use DbTestCase;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDatabaseInstanceCapacity;
use Glpi\Features\Clonable;
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
}
