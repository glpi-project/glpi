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

namespace tests\units\Glpi\Inventory;

/**
 * Test class for src/Glpi/Inventory/Request.php
 */
class Request extends \GLPITestCase {
   public function testConstructor() {
      //no mode
      $request = new \Glpi\Inventory\Request();
      $this->variable($request->getMode())->isNull();
      $this->exception(
         function () use ($request) {
            $this->variable($request->getResponse())->isNull();
         }
      )
         ->isInstanceOf('\RuntimeException')
         ->hasMessage('Mode has not been set');

      $this->exception(
         function () use ($request) {
            $this->variable($request->getContentType())->isNull();
         }
      )
         ->isInstanceOf('\RuntimeException')
         ->hasMessage('Mode has not been set');

      //XML mode
      $request = new \Glpi\Inventory\Request();
      $request->handleContentType('application/xml');
      $this->integer($request->getMode())->isIdenticalTo(\Glpi\Inventory\Request::XML_MODE);
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY/>\n");
      $this->string($request->getContentType())->isIdenticalTo('application/xml');

      //JSON mode
      $request = new \Glpi\Inventory\Request();
      $request->handleContentType('application/json');
      $this->integer($request->getMode())->isIdenticalTo(\Glpi\Inventory\Request::JSON_MODE);
      $response = [];
      $this->string($request->getResponse())->isIdenticalTo(json_encode($response));
      $this->string($request->getContentType())->isIdenticalTo('application/json');
   }

   public function testProlog() {
      $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><QUERY>PROLOG</QUERY></REQUEST>";
      $request = new \Glpi\Inventory\Request;
      $request->handleContentType('application/xml');
      $request->handleRequest($data);
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY><PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE></REPLY>\n");
   }

   protected function queriesProvider() {
      return [
         ['query' => 'INVENTORY'], //Request::INVENT_QUERY | Request::INVENT_ACTION
         ['query' => 'PROLOG'], //Request::PROLOG_QUERY
         ['query' => 'SNMPQUERY'], //Request::OLD_SNMP_QUERY
         ['query' => 'SNMP'], //Request::SNMP_QUERY
         ['query' => 'NETDISCOVERY'], //Request::NETDISCOVERY_ACTION
         ['query' => 'inventory'], //Request::INVENT_QUERY | Request::INVENT_ACTION
         ['query' => 'prolog'], //Request::PROLOG_QUERY
         ['query' => 'netinventory'], //Request::NETINV_ACTION
      ];
   }

   /**
    * Test known queries
    *
    * @dataProvider queriesProvider
    */
   public function testSnmpQuery($query) {
      $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><CONTENT><DEVICE></DEVICE></CONTENT><QUERY>$query</QUERY></REQUEST>";
      $request = new \mock\Glpi\Inventory\Request();
      $this->calling($request)->inventory = null;
      $this->calling($request)->prolog = null;
      $request->handleContentType('Application/xml');
      $request->handleRequest($data);
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY/>\n");
   }

   protected function unhandledQueriesProvider() {
      return [
         ['query' => 'register'], //Request::REGISTER_ACTION
         ['query' => 'configuration'], //Request::CONFIG_ACTION
         ['query' => 'ESX'], //Request::ESX_ACTION
         ['query' => 'COLLECT'], //Request::COLLECT_ACTION
         ['query' => 'DEPLOY'], //Request:: DEPLOY_ACTION
         ['query' => 'wakeonlan'], //Request::WOL_ACTION
         ['query' => 'UNKNOWN'],
      ];
   }

   /**
    * Test unknown queries
    *
    * @dataProvider unhandledQueriesProvider
    */
   public function testWrongQuery($query) {
      $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><QUERY>$query</QUERY></REQUEST>";
      $request = new \Glpi\Inventory\Request;
      $request->handleContentType('application/xml');
      $request->handleRequest($data);
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY><ERROR>Query '".strtolower($query)."' is not supported.</ERROR></REPLY>\n");
   }

   public function testAddError() {
      $request = new \Glpi\Inventory\Request;
      $request->handleContentType('application/xml');
      $request->addError('Something went wrong.');
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY><ERROR>Something went wrong.</ERROR></REPLY>\n");

      $request = new \Glpi\Inventory\Request;
      $request->handleContentType('application/xml');
      //to test nodes with attributes
      $request->addError([
         'OPTION' => [
            'NAME' => 'NETDISCOVERY',
            'PARAM' => [
               'content' => '',
               'attributes' => [
                  'THREADS_DISCOVERY' => 5,
                  'TIMEOUT' => 1,
                  'PID' => 16
               ]
            ],
            'RANGEIP' => [
               'content' => '',
               'attributes' => [
                  'ID' => 1,
                  'IPSTART' => '192.168.1.1',
                  'IPEND' => '192.168.1.254',
                  'ENTITY' => 0
               ]
            ],
            [
               'AUTHENTICATION' => [
                  'content' => '',
                  'attributes' => [
                     'ID' => 1,
                     'COMMUNITY' => 'public',
                     'VERSION' => '1',
                     'USERNAME' => '',
                     'AUTHPROTOCOL' => '',
                     'AUTHPASSPHRASE' => '',
                     'PRIVPROTOCOL' => '',
                     'PRIVPASSPHRASE' => ''
                  ]
               ]
            ], [
               'AUTHENTICATION' => [
                  'content' => '',
                  'attributes' => [
                     'ID' => 2,
                     'COMMUNITY' => 'public',
                     'VERSION' => '2c',
                     'USERNAME' => '',
                     'AUTHPROTOCOL' => '',
                     'AUTHPASSPHRASE' => '',
                     'PRIVPROTOCOL' => '',
                     'PRIVPASSPHRASE' => ''
                  ]
               ]
            ]
         ]
      ]);

      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY><ERROR><OPTION><NAME>NETDISCOVERY</NAME><PARAM THREADS_DISCOVERY=\"5\" TIMEOUT=\"1\" PID=\"16\"/><RANGEIP ID=\"1\" IPSTART=\"192.168.1.1\" IPEND=\"192.168.1.254\" ENTITY=\"0\"/><AUTHENTICATION ID=\"1\" COMMUNITY=\"public\" VERSION=\"1\" USERNAME=\"\" AUTHPROTOCOL=\"\" AUTHPASSPHRASE=\"\" PRIVPROTOCOL=\"\" PRIVPASSPHRASE=\"\"/><AUTHENTICATION ID=\"2\" COMMUNITY=\"public\" VERSION=\"2c\" USERNAME=\"\" AUTHPROTOCOL=\"\" AUTHPASSPHRASE=\"\" PRIVPROTOCOL=\"\" PRIVPASSPHRASE=\"\"/></OPTION></ERROR></REPLY>\n");
   }

   protected function compressionProvider(): array {
      return [
         [
            'function' => 'gzcompress',
            'mime' => 'application/x-compress-zlib'
         ], [
            'function' => 'gzcompress',
            'mime' => 'application/x-zlib'
         ], [
            'function' => 'gzencode',
            'mime' => 'application/x-gzip'
         ], [
            'function' => 'gzencode',
            'mime' => 'application/x-compress-gzip'
         ], [
            'function' => 'gzdeflate',
            'mime' => 'application/x-compress-deflate'
         ], [
            'function' => 'gzdeflate',
            'mime' => 'application/x-deflate'
         ]
      ];
   }

   /**
    * Test request compression
    *
    * @param string $function Compression method to use
    * @param string $mime     Mime type to set
    *
    * @dataProvider compressionProvider
    *
    * @return void
    */
   public function testCompression(string $function, string $mime) {
      $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><QUERY>PROLOG</QUERY></REQUEST>";
      $cdata = $function($data);

      $request = new \Glpi\Inventory\Request;
      $request->handleContentType($mime);
      $request->handleRequest($cdata);
      $this->string($request->getDeviceID())->isIdenticalTo('atoumized-device');
      $this->string($request->getResponse())->isIdenticalTo($function("<?xml version=\"1.0\"?>\n<REPLY><PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE></REPLY>\n"));
   }

}
