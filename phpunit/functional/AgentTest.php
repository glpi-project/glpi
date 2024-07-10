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

class AgentTest extends DbTestCase
{
    const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    public function testDefineTabs()
    {
        $expected = [
            'Agent$main'        => 'Agent',
            'RuleMatchedLog$0'  => 'Import information',
        ];

        $agent = new \Agent();
        $this->assertSame($expected, $agent->defineTabs());
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

        $agent = new \Agent();
        $this->assertGreaterThan(0, $agent->handleAgent($metadata));

        // This should also work when inventory type is different than agent linked item type
        $metadata['itemtype'] = 'Printer';

        $agent = new \Agent();
        $this->assertGreaterThan(0, $agent->handleAgent($metadata));

        // In the case the agent is used to submit another item type, we still
        // need to have access to agent tag but no item should be linked
        $tag = $agent->fields['tag'];
        $port = $agent->fields['port'];
        $items_id = $agent->fields['items_id'];
        $this->assertSame('000005', $tag);
        $this->assertSame('62354', $port);
        $this->assertSame(0, $items_id);

        $this->assertSame(1, $agent->fields['use_module_computer_inventory']);
        $this->assertSame(1, $agent->fields['use_module_network_discovery']);
        $this->assertSame(1, $agent->fields['use_module_network_inventory']);
        $this->assertSame(1, $agent->fields['use_module_remote_inventory']);
        $this->assertSame(1, $agent->fields['use_module_wake_on_lan']);
        $this->assertSame(0, $agent->fields['use_module_esx_remote_inventory']);
        $this->assertSame(0, $agent->fields['use_module_package_deployment']);
        $this->assertSame(0, $agent->fields['use_module_collect_data']);
    }

    public function testHandleAgentWOType()
    {
        /** @var \DBmysql $DB */
        global $DB;

        //explicitly remove agent type
        $this->assertTrue(
            $DB->delete(
                \AgentType::getTable(),
                [
                    'name' => 'Core'
                ]
            )
        );
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

        $agent = new \Agent();
        $this->assertGreaterThan(0, $agent->handleAgent($metadata));

        // This should also work when inventory type is different than agent linked item type
        $metadata['itemtype'] = 'Printer';

        $agent = new \Agent();
        $this->assertGreaterThan(0, $agent->handleAgent($metadata));

        // In the case the agent is used to submit another item type, we still
        // need to have access to agent tag but no item should be linked
        $tag = $agent->fields['tag'];
        $port = $agent->fields['port'];
        $items_id = $agent->fields['items_id'];
        $this->assertSame('000006', $tag);
        $this->assertSame('62354', $port);
        $this->assertSame(0, $items_id);

        $this->assertSame(1, $agent->fields['use_module_computer_inventory']);
        $this->assertSame(0, $agent->fields['use_module_network_discovery']);
        $this->assertSame(0, $agent->fields['use_module_network_inventory']);
        $this->assertSame(1, $agent->fields['use_module_remote_inventory']);
        $this->assertSame(1, $agent->fields['use_module_wake_on_lan']);
        $this->assertSame(1, $agent->fields['use_module_esx_remote_inventory']);
        $this->assertSame(0, $agent->fields['use_module_package_deployment']);
        $this->assertSame(1, $agent->fields['use_module_collect_data']);
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
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertCount(10, $metadata['provider']);

        /** @var \DBmysql $DB */
        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $current_agent = $agents->current();
        $this->assertSame('glpixps-2018-07-09-09-07-13', $current_agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $current_agent['name']);
        $this->assertSame('2.5.2-1.fc31', $current_agent['version']);
        $this->assertSame('Computer', $current_agent['itemtype']);
        $this->assertSame($agenttype['id'], $current_agent['agenttypes_id']);

        $agent = new \Agent();
        $this->assertTrue($agent->getFromDB($current_agent['id']));

        $item = $agent->getLinkedItem();
        $this->assertInstanceOf(\Computer::class, $item);

        $this->assertSame(
            [
                'glpixps',
                '192.168.1.142',
                '[fe80::b283:4fa3:d3f2:96b1]',
                '192.168.1.118',
                '[fe80::92a4:26c6:99dd:2d60]',
                '192.168.122.1'
            ],
            $agent->guessAddresses()
        );

        $this->assertSame(
            [
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
            ],
            $agent->getAgentURLs()
        );

        //link a domain to item and see if adresses are still ok
        $domain = new \Domain();
        $did = $domain->add([
            'name'   => 'glpi-project.org'
        ]);
        $this->assertGreaterThan(0, $did);

        $ditem = new \Domain_Item();
        $this->assertGreaterThan(
            0,
            $ditem->add([
                'itemtype'     => $item->getType(),
                'items_id'     => $item->getID(),
                'domains_id'   => $did
            ])
        );

        $this->assertSame(
            [
                'glpixps',
                '192.168.1.142',
                '[fe80::b283:4fa3:d3f2:96b1]',
                '192.168.1.118',
                '[fe80::92a4:26c6:99dd:2d60]',
                '192.168.122.1',
                'glpixps.glpi-project.org'
            ],
            $agent->guessAddresses()
        );
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
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertCount(10, $metadata['provider']);

        /** @var \DBmysql $DB */
        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $current_agent = $agents->current();
        $this->assertSame('glpixps-2018-07-09-09-07-13', $current_agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $current_agent['name']);
        $this->assertSame('2.5.2-1.fc31', $current_agent['version']);
        $this->assertSame('Computer', $current_agent['itemtype']);
        $this->assertSame('000005', $current_agent['tag']);
        $this->assertSame($agenttype['id'], $current_agent['agenttypes_id']);
        $old_agents_id = $current_agent['id'];

        $agent = new \Agent();
        $this->assertTrue($agent->getFromDB($current_agent['id']));

        $item = $agent->getLinkedItem();
        $this->assertInstanceOf(\Computer::class, $item);

        //play an update with changes
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        //change agent and therefore deviceid
        $json->content->versionclient = 'GLPI-Agent_v1';
        $json->deviceid = 'glpixps-2022-01-17-11-36-53';

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($json);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2022-01-17-11-36-53', $metadata['deviceid']);
        $this->assertSame('GLPI-Agent_v1', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertCount(10, $metadata['provider']);

        //check old agent has been dropped
        $current_agent = new \Agent();
        $this->assertFalse($current_agent->getFromDB($old_agents_id), 'Old Agent still exists!');
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
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(6, $metadata);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);

        /** @var \DBmysql $DB */
        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
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
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertCount(10, $metadata['provider']);

        /** @var \DBmysql $DB */
        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $current_agent = $agents->current();
        $this->assertSame('glpixps-2018-07-09-09-07-13', $current_agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $current_agent['name']);
        $this->assertSame('2.5.2-1.fc31', $current_agent['version']);
        $this->assertSame('Computer', $current_agent['itemtype']);
        $this->assertSame('000005', $current_agent['tag']);
        $this->assertSame($agenttype['id'], $current_agent['agenttypes_id']);
        $old_agents_id = $current_agent['id'];

        $agent = new \Agent();
        $this->assertTrue($agent->getFromDB($current_agent['id']));

        $item = $agent->getLinkedItem();
        $this->assertInstanceOf(\Computer::class, $item);

        //check default status
        $this->assertSame(0, $item->fields['states_id']);

        //create new status
        $state = new \State();
        $states_id = $state->add(['name' => 'Stale']);
        $this->assertGreaterThan(0, $states_id);

        //set last agent contact far ago
        $DB->update(
            \Agent::getTable(),
            ['last_contact' => date('Y-m-d H:i:s', strtotime('-1 year'))],
            ['id' => $current_agent['id']]
        );

        //define stale agents actions
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
        $this->assertSame(1, \Agent::cronCleanoldagents($task));

        //check item has been updated
        $this->assertTrue($item->getFromDB($item->fields['id']));
        $this->assertSame(1, $item->fields['is_deleted']);
        $this->assertSame($states_id, $item->fields['states_id']);
    }
}
