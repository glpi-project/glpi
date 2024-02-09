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

class Agent extends DbTestCase
{
    const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    public function testDefineTabs()
    {
        $expected = [
            'Agent$main'       => "<span><i class='ti ti-robot me-2'></i>Agent</span>",
            'RuleMatchedLog$0' => "<span><i class='ti ti-book me-2'></i>Import information</span>",
        ];
        $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected);
    }

    public function testHandleAgent()
    {
        $metadata = [
            'deviceid'  => 'glpixps-2018-07-09-09-07-13',
            'version'   => 'FusionInventory-Agent_v2.5.2-1.fc31',
            'itemtype'  => 'Computer',
            'tag'       => '000005',
            'port'       => '62354',
            'enabled-tasks' => [
                "inventory",
                "netdiscovery",
                "netinventory",
                "remoteinventory",
                "wakeonlan",
            ]
        ];

        $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($this->testedInstance->handleAgent($metadata))
               ->isGreaterThan(0);

        // This should also work when inventory type is different than agent linked item type
        $metadata['itemtype'] = 'Printer';

        $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($this->testedInstance->handleAgent($metadata))
               ->isGreaterThan(0);

        // In the case the agent is used to submit another item type, we still
        // need to have access to agent tag but no item should be linked
        $tag = $this->testedInstance->fields['tag'];
        $port = $this->testedInstance->fields['port'];
        $items_id = $this->testedInstance->fields['items_id'];
        $this->string($tag)->isIdenticalTo('000005');
        $this->string($port)->isIdenticalTo('62354');

        $this->integer($this->testedInstance->fields['use_module_computer_inventory'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_network_discovery'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_network_inventory'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_remote_inventory'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_wake_on_lan'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_esx_remote_inventory'])->isIdenticalTo(0);
        $this->integer($this->testedInstance->fields['use_module_package_deployment'])->isIdenticalTo(0);
        $this->integer($this->testedInstance->fields['use_module_collect_data'])->isIdenticalTo(0);
    }

    public function testHandleAgentWOType()
    {
        global $DB;

        //explicitly remove agent type
        $this->boolean(
            $DB->delete(
                \AgentType::getTable(),
                [
                    'name' => 'Core'
                ]
            )
        )->isTrue();
        //then rerun tests
        $this->testHandleAgent();
    }

    public function testHandleAgentOnUpdate()
    {
        $metadata = [
            'deviceid'  => 'glpixps-2018-07-09-09-07-13',
            'version'   => 'FusionInventory-Agent_v2.5.2-1.fc31',
            'itemtype'  => 'Computer',
            'tag'       => '000006',
            'port'       => '62354',
            'enabled-tasks' => [
                "inventory",
                "remoteinventory",
                "wakeonlan",
                "collect",
                "esx",
            ]
        ];

        $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($this->testedInstance->handleAgent($metadata))
               ->isGreaterThan(0);

        // This should also work when inventory type is different than agent linked item type
        $metadata['itemtype'] = 'Printer';

        $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($this->testedInstance->handleAgent($metadata))
               ->isGreaterThan(0);

        // In the case the agent is used to submit another item type, we still
        // need to have access to agent tag but no item should be linked
        $tag = $this->testedInstance->fields['tag'];
        $port = $this->testedInstance->fields['port'];
        $items_id = $this->testedInstance->fields['items_id'];
        $this->string($tag)->isIdenticalTo('000006');
        $this->string($port)->isIdenticalTo('62354');

        $this->integer($this->testedInstance->fields['use_module_computer_inventory'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_network_discovery'])->isIdenticalTo(0);
        $this->integer($this->testedInstance->fields['use_module_network_inventory'])->isIdenticalTo(0);
        $this->integer($this->testedInstance->fields['use_module_remote_inventory'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_wake_on_lan'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_esx_remote_inventory'])->isIdenticalTo(1);
        $this->integer($this->testedInstance->fields['use_module_package_deployment'])->isIdenticalTo(0);
        $this->integer($this->testedInstance->fields['use_module_collect_data'])->isIdenticalTo(1);
    }

    public function testAgentFeaturesFromItem()
    {
        //run an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['action']->isIdenticalTo('inventory')
         ->variable['port']->isIdenticalTo(null)
         ->string['tag']->isIdenticalTo('000005');
        $this->array($metadata['provider'])->hasSize(10);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        $this
         ->given($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->getFromDB($agent['id']))
               ->isTrue();

        $item = $this->testedInstance->getLinkedItem();
        $this->object($item)->isInstanceOf('Computer');

        $this->array($this->testedInstance->guessAddresses())->isIdenticalTo([
            'glpixps',
            '192.168.1.142',
            '[fe80::b283:4fa3:d3f2:96b1]',
            '192.168.1.118',
            '[fe80::92a4:26c6:99dd:2d60]',
            '192.168.122.1'
        ]);

        $this->array($this->testedInstance->getAgentURLs())->isIdenticalTo([
            'http://glpixps:62354',
            'http://192.168.1.142:62354',
            'http://[fe80::b283:4fa3:d3f2:96b1]:62354',
            'http://192.168.1.118:62354',
            'http://[fe80::92a4:26c6:99dd:2d60]:62354',
            'http://192.168.122.1:62354',
            'https://glpixps:62354',
            'https://192.168.1.142:62354',
            'https://[fe80::b283:4fa3:d3f2:96b1]:62354',
            'https://192.168.1.118:62354',
            'https://[fe80::92a4:26c6:99dd:2d60]:62354',
            'https://192.168.122.1:62354'
        ]);

        //link a domain to item and see if adresses are still ok
        $domain = new \Domain();
        $did = $domain->add([
            'name'   => 'glpi-project.org'
        ]);
        $this->integer($did)->isGreaterThan(0);

        $ditem = new \Domain_Item();
        $this->integer(
            $ditem->add([
                'itemtype'     => $item->getType(),
                'items_id'     => $item->getID(),
                'domains_id'   => $did
            ])
        )->isGreaterThan(0);

        $this->array($this->testedInstance->guessAddresses())->isIdenticalTo([
            'glpixps',
            '192.168.1.142',
            '[fe80::b283:4fa3:d3f2:96b1]',
            '192.168.1.118',
            '[fe80::92a4:26c6:99dd:2d60]',
            '192.168.122.1',
            'glpixps.glpi-project.org'
        ]);
    }

    public function testAgentHasChanged()
    {
        //run an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['action']->isIdenticalTo('inventory')
            ->variable['port']->isIdenticalTo(null)
            ->string['tag']->isIdenticalTo('000005');
        $this->array($metadata['provider'])->hasSize(10);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);
        $old_agents_id = $agent['id'];

        $this
            ->given($this->newTestedInstance)
            ->then
            ->boolean($this->testedInstance->getFromDB($agent['id']))
            ->isTrue();

        $item = $this->testedInstance->getLinkedItem();
        $this->object($item)->isInstanceOf('Computer');

        //play an update with changes
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        //change agent and therefore deviceid
        $json->content->versionclient = 'GLPI-Agent_v1';
        $json->deviceid = 'glpixps-2022-01-17-11-36-53';

        $entity = new \Entity();
        $entity->getFromDB(0);
        $this->boolean($entity->update([
            "id" => $entity->fields['id'],
            "is_contact_autoupdate" => 0,
        ]))->isTrue();
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
            ->string['deviceid']->isIdenticalTo('glpixps-2022-01-17-11-36-53')
            ->string['version']->isIdenticalTo('GLPI-Agent_v1')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['action']->isIdenticalTo('inventory')
            ->variable['port']->isIdenticalTo(null)
            ->string['tag']->isIdenticalTo('000005');
        $this->array($metadata['provider'])->hasSize(10);

        //check old agent has been dropped
        $agent = new \Agent();
        $this->boolean($agent->getFromDB($old_agents_id))->isFalse('Old Agent still exists!');
    }

    public function testTagFromXML()
    {
        //run an inventory
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>glpixps</NAME>
      <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
    </HARDWARE>
    <BIOS>
      <MSN>640HP72</MSN>
      <SSN>000</SSN>
    </BIOS>
    <ACCOUNTINFO>
      <KEYNAME>TAG</KEYNAME>
      <KEYVALUE>000005</KEYVALUE>
    </ACCOUNTINFO>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(6)
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['action']->isIdenticalTo('inventory')
            ->variable['port']->isIdenticalTo(null)
            ->string['tag']->isIdenticalTo('000005');

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);
    }

    public function testStaleActions()
    {
        //run an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['action']->isIdenticalTo('inventory')
            ->variable['port']->isIdenticalTo(null)
            ->string['tag']->isIdenticalTo('000005');
        $this->array($metadata['provider'])->hasSize(10);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);
        $old_agents_id = $agent['id'];

        $this
            ->given($this->newTestedInstance)
            ->then
            ->boolean($this->testedInstance->getFromDB($agent['id']))
            ->isTrue();

        $item = $this->testedInstance->getLinkedItem();
        $this->object($item)->isInstanceOf('Computer');

        //check default status
        $this->integer($item->fields['states_id'])->isIdenticalTo(0);

        //create new status
        $state = new \State();
        $states_id = $state->add(['name' => 'Stale']);
        $this->integer($states_id)->isGreaterThan(0);

        //set last agent contact far ago
        $DB->update(
            \Agent::getTable(),
            ['last_contact' => date('Y-m-d H:i:s', strtotime('-1 year'))],
            ['id' => $agent['id']]
        );

        //define sale agents actions
        \Config::setConfigurationValues(
            'inventory',
            [
                'stale_agents_delay' => 1,
                'stale_agents_action' => exportArrayToDB([
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_STATUS,
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_TRASHBIN
                ]),
                'stale_agents_status' => $states_id
            ]
        );

        //run crontask
        $task = new \CronTask();
        $this->integer(\Agent::cronCleanoldagents($task))->isIdenticalTo(1);

        //check item has been updated
        $this->boolean($item->getFromDB($item->fields['id']))->isTrue();
        $this->integer($item->fields['is_deleted'])->isIdenticalTo(1);
        $this->integer($item->fields['states_id'])->isIdenticalTo($states_id);

        //create another new status
        $states_id2 = $state->add(['name' => 'Stale2']);
        $this->integer($states_id2)->isGreaterThan(0);

        //define sale agents actions
        \Config::setConfigurationValues(
            'inventory',
            [
                'stale_agents_delay' => 1,
                'stale_agents_action' => exportArrayToDB([
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_STATUS,
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_TRASHBIN
                ]),
                'previous_stale_agents_status' => json_encode([
                    $states_id
                ]),
                'stale_agents_status' => $states_id2
            ]
        );

        //run crontask
        $task = new \CronTask();
        $this->integer(\Agent::cronCleanoldagents($task))->isIdenticalTo(1);

        //check item has been updated
        $this->boolean($item->getFromDB($item->fields['id']))->isTrue();
        $this->integer($item->fields['is_deleted'])->isIdenticalTo(1);
        $this->integer($item->fields['states_id'])->isIdenticalTo($states_id2);

        //create another new status for test while previous status is empty (0)
        $states_id3 = $state->add(['name' => 'Stale3']);
        $this->integer($states_id3)->isGreaterThan(0);

        //define sale agents actions
        \Config::setConfigurationValues(
            'inventory',
            [
                'stale_agents_delay' => 1,
                'stale_agents_action' => exportArrayToDB([
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_STATUS,
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_TRASHBIN
                ]),
                'previous_stale_agents_status' => json_encode([0]),
                'stale_agents_status' => $states_id3
            ]
        );

        //run crontask
        $task = new \CronTask();
        $this->integer(\Agent::cronCleanoldagents($task))->isIdenticalTo(1);

        //check item has been updated
        $this->boolean($item->getFromDB($item->fields['id']))->isTrue();
        $this->integer($item->fields['is_deleted'])->isIdenticalTo(1);
        $this->integer($item->fields['states_id'])->isIdenticalTo($states_id3);

        //create another new status for test while previous status is empty (0)
        $states_id4 = $state->add(['name' => 'Stale4']);
        $this->integer($states_id4)->isGreaterThan(0);

        //define sale agents actions
        \Config::setConfigurationValues(
            'inventory',
            [
                'stale_agents_delay' => 1,
                'stale_agents_action' => exportArrayToDB([
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_STATUS,
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_TRASHBIN
                ]),
                'previous_stale_agents_status' => json_encode([
                    $states_id,
                    $states_id2,
                ]),
                'stale_agents_status' => $states_id4
            ]
        );

        //run crontask
        $task = new \CronTask();
        $this->integer(\Agent::cronCleanoldagents($task))->isIdenticalTo(1);

        //check item has been updated
        $this->boolean($item->getFromDB($item->fields['id']))->isTrue();
        $this->integer($item->fields['is_deleted'])->isIdenticalTo(1);
        $this->integer($item->fields['states_id'])->isIdenticalTo($states_id3);

        //test with invalide status or undefined status
        \Config::setConfigurationValues(
            'inventory',
            [
                'stale_agents_delay' => 1,
                'stale_agents_action' => exportArrayToDB([
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_STATUS,
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_TRASHBIN
                ]),
                'previous_stale_agents_status' => json_encode([
                    "aaaaaaa"
                ]),
                'stale_agents_status' => $states_id4
            ]
        );

        //run crontask
        $task = new \CronTask();
        $this->integer(\Agent::cronCleanoldagents($task))->isIdenticalTo(1);

        //check item has been updated
        $this->boolean($item->getFromDB($item->fields['id']))->isTrue();
        $this->integer($item->fields['is_deleted'])->isIdenticalTo(1);
        $this->integer($item->fields['states_id'])->isIdenticalTo($states_id3);
    }
}
