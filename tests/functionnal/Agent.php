<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
            'Agent$main'   => 'Agent',
            'Log$1'        => 'Historical'
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
            'tag'       => '000005'
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
        $items_id = $this->testedInstance->fields['items_id'];
        $this->string($tag)->isIdenticalTo('000005');
        $this->integer($items_id)->isIdenticalTo(0);
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
        $this->array($metadata)->hasSize(6)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['action']->isIdenticalTo('inventory')
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
        $this->array($metadata)->hasSize(6)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['action']->isIdenticalTo('inventory')
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

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($json);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

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
            ->string['deviceid']->isIdenticalTo('glpixps-2022-01-17-11-36-53')
            ->string['version']->isIdenticalTo('GLPI-Agent_v1')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['action']->isIdenticalTo('inventory')
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
        $this->array($metadata)->hasSize(5)
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['action']->isIdenticalTo('inventory')
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

    protected function staleAgentCleanProvider()
    {
        return [
            ['Computer', 0],
            ['Computer', null],
            ['Phone', 0],
            ['Phone', null]
        ];
    }

    /**
     * @dataProvider staleAgentCleanProvider
     */
    public function testStaleAgentClean(string $itemtype, ?int $items_id)
    {
        global $DB;

        $test_stale_days = 10;

        $item = new $itemtype();
        if ($items_id === null) {
            $items_id = $item->add([
                'name' => __FUNCTION__,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
            ]);
            $this->integer($items_id)->isGreaterThan(0);
        }
        // Create a new agent
        $agent = new \Agent();
        $rand = mt_rand();
        $agents_id = $agent->add([
            'name' => __FUNCTION__ . $rand . '-2018-07-09-09-07-13',
            'deviceid' => __FUNCTION__ . $rand . '-2018-07-09-09-07-13',
            'version' => '2.5.2-1.fc31',
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'tag' => '000005',
            'agenttypes_id' => 1
        ]);
        $this->integer($agents_id)->isGreaterThan(0);
        // Force-change last_contact to be older than the stale period
        $this->boolean($agent->update([
            'id' => $agents_id,
            'last_contact' => date('Y-m-d H:i:s', strtotime('-' . ($test_stale_days + 1) . ' days'))
        ]))->isTrue();
        // Get current stale_agents_delay config value
        $original_stale_days = \Config::getConfigurationValues('inventory', ['stale_agents_delay'])['stale_agents_delay'] ?? 0;
        $original_stale_clean = \Config::getConfigurationValues('inventory', ['stale_agents_clean'])['stale_agents_clean'] ?? 1;

        // Set stale_agents_delay
        $DB->updateOrInsert(
            \Config::getTable(),
            [
                'name' => 'stale_agents_delay',
                'value' => $test_stale_days
            ],
            [
                'name' => 'stale_agents_delay'
            ]
        );
        // Set stale_agents_clean to 1
        $DB->updateOrInsert(
            \Config::getTable(),
            [
                'name' => 'stale_agents_clean',
                'value' => 1
            ],
            [
                'name' => 'stale_agents_clean'
            ]
        );

        // Force run the cleanup cron task (direct call the function)
        $crontask = new \CronTask();
        $this->boolean(\Agent::cronCleanoldagents($crontask))->isTrue();
        // Verify that the agent has been deleted
        $this->boolean($agent->getFromDB($agents_id))->isFalse();
    }

    protected function staleAgentStatusChangeProvider()
    {
        return [
            ['Computer', 0],
            ['Computer', null],
            ['Phone', 0],
            ['Phone', null]
        ];
    }

    /**
     * @dataProvider staleAgentStatusChangeProvider
     */
    public function testStaleAgentStatusChange(string $itemtype, ?int $items_id)
    {
        global $DB;

        $test_stale_days = 10;

        $item = new $itemtype();
        if ($items_id === null) {
            $items_id = $item->add([
                'name' => __FUNCTION__,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'states_id' => 1
            ]);
            $this->integer($items_id)->isGreaterThan(0);
        }

        // Create a new agent (Does need a real item linked)
        $agent = new \Agent();
        $rand = mt_rand();
        $agents_id = $agent->add([
            'name' => __FUNCTION__ . $rand . '-2018-07-09-09-07-13',
            'deviceid' => __FUNCTION__ . $rand . '-2018-07-09-09-07-13',
            'version' => '2.5.2-1.fc31',
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'tag' => '000005',
            'agenttypes_id' => 1
        ]);
        $this->integer($agents_id)->isGreaterThan(0);
        // Force-change last_contact to be older than the stale period
        $this->boolean($agent->update([
            'id' => $agents_id,
            'last_contact' => date('Y-m-d H:i:s', strtotime('-' . ($test_stale_days + 1) . ' days'))
        ]))->isTrue();

        // Set stale_agents_delay
        $DB->updateOrInsert(
            \Config::getTable(),
            [
                'name' => 'stale_agents_delay',
                'value' => $test_stale_days
            ],
            [
                'name' => 'stale_agents_delay'
            ]
        );
        // Set stale_agents_status to -1
        $DB->updateOrInsert(
            \Config::getTable(),
            [
                'name' => 'stale_agents_status',
                'value' => -1
            ],
            [
                'name' => 'stale_agents_status'
            ]
        );
        // Set stale_agents_clean to 0
        $DB->updateOrInsert(
            \Config::getTable(),
            [
                'name' => 'stale_agents_clean',
                'value' => 0
            ],
            [
                'name' => 'stale_agents_clean'
            ]
        );

        // Force run the cleanup cron task (direct call the function)
        $crontask = new \CronTask();
        // Should return true even if the agent has an invalid item
        $this->boolean(\Agent::cronCleanoldagents($crontask))->isTrue();
        // Verify that the agent has not been deleted
        $this->boolean((bool) $agent->getFromDB($agents_id))->isTrue();
        if ($items_id > 0) {
            $item->getFromDB($items_id);
            // Verify the computer status has not changed
            $this->integer($item->fields['states_id'])->isEqualTo(1);
        }

        // Set stale_agents_status to 2, run the stale agent cron, and verify the computer status matches
        $DB->updateOrInsert(
            \Config::getTable(),
            [
                'name' => 'stale_agents_status',
                'value' => 2
            ],
            [
                'name' => 'stale_agents_status'
            ]
        );
        // Should return true even if the agent has an invalid item
        $this->boolean(\Agent::cronCleanoldagents($crontask))->isTrue();
        if ($items_id > 0) {
            $item->getFromDB($items_id);
            $this->integer($item->fields['states_id'])->isEqualTo(2);
        }
    }
}
