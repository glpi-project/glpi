<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
    }

    public function testAgentFeaturesFromItem()
    {


        // Save values and fake REMOTE_ADDR
        $saveServer = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        //run an inventory
        $json = file_get_contents(self::INV_FIXTURES . 'computer_1.json');
        $inventory = new \Glpi\Inventory\Inventory($json);

        // Restore values
        $_SERVER = $saveServer;

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
         ->string['ip_binary']->isIdenticalTo(inet_pton('123.123.123.123'))
         ->string['ip_protocol']->isIdenticalTo('http')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        $this
         ->given($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->getFromDB($agent['id']))
               ->isTrue();

        $item = $this->testedInstance->getLinkedItem();
        $this->object($item)->isInstanceOf('Computer');

        $this->string($this->testedInstance->getAddress())->isIdenticalTo('123.123.123.123');
        $this->string($this->testedInstance->getAgentURL())->isIdenticalTo('http://123.123.123.123:62354');
    }
}
