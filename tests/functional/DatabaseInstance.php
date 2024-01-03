<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use DbTestCase;

/* Test for inc/databaseinstance.class.php */

class DatabaseInstance extends DbTestCase
{
    public function testDelete()
    {
        $instance = new \DatabaseInstance();

        $instid = $instance->add([
            'name' => 'To be removed',
            'port' => 3306,
            'size' => 52000
        ]);

       //check DB is created, and load it
        $this->integer($instid)->isGreaterThan(0);
        $this->boolean($instance->getFromDB($instid))->isTrue();

       //create databases
        for ($i = 0; $i < 5; ++$i) {
            $database = new \Database();
            $this->integer(
                $database->add([
                    'name'                   => 'Database ' . $i,
                    'databaseinstances_id'   => $instid
                ])
            )->isGreaterThan(0);
        }
        $this->integer(countElementsInTable(\Database::getTable()))->isIdenticalTo(5);

       //test removal
        $this->boolean($instance->delete(['id' => $instid], 1))->isTrue();
        $this->boolean($instance->getFromDB($instid))->isFalse();

       //ensure databases has been dropped aswell
        $this->integer(countElementsInTable(\Database::getTable()))->isIdenticalTo(0);
    }

    public function testGetInventoryAgent(): void
    {
        $root_entity = getItemByTypeName(\Entity::class, '_test_root_entity', true);

        $computer = $this->createItem(
            \Computer::class,
            [
                'name'        => 'test computer',
                'entities_id' => 0,
            ]
        );
        $dbinstance = $this->createItem(
            \DatabaseInstance::class,
            [
                'name'     => 'test database',
                'itemtype' => \Computer::class,
                'items_id' => $computer->fields['id'],
            ]
        );

        $db_agent = $dbinstance->getInventoryAgent();
        $this->variable($db_agent)->isNull();

        $agenttype_id = getItemByTypeName(\AgentType::class, 'Core', true);

        $agent1 = $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \DatabaseInstance::class,
                'items_id'     => $dbinstance->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('yesterday')),
            ]
        );

        $agent2 = $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \DatabaseInstance::class,
                'items_id'     => $dbinstance->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('last week')),
            ]
        );

        $agent3 = $this->createItem(
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
        $this->object($db_agent)->isInstanceOf(\Agent::class);
        $this->array($db_agent->fields)->isEqualTo($agent1->fields);

        $this->boolean($agent1->delete(['id' => $agent1->fields['id']]))->isTrue();

        // most recent agent directly linked
        $db_agent = $dbinstance->getInventoryAgent();
        $this->object($db_agent)->isInstanceOf(\Agent::class);
        $this->array($db_agent->fields)->isEqualTo($agent2->fields);

        $this->boolean($agent2->delete(['id' => $agent2->fields['id']]))->isTrue();

        // most recent agent found from linked item, as there is no more agent linked directly
        $db_agent = $dbinstance->getInventoryAgent();
        $this->object($db_agent)->isInstanceOf(\Agent::class);
        $computer_agent = $computer->getInventoryAgent();
        $this->object($computer_agent)->isInstanceOf(\Agent::class);
        $this->array($db_agent->fields)->isEqualTo($computer_agent->fields);
    }
}
