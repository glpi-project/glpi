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
      $request->HandleContentType('application/xml');
      $request->handleRequest($data);
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY><PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE></REPLY>\n");
   }

   protected function queriesProvider() {
      return [
         ['query' => 'INVENTORY'],
         ['query' => 'PROLOG'],
         ['query' => 'SNMPQUERY'],
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
      $request->HandleContentType('Application/xml');
      $request->handleRequest($data);
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY/>\n");
   }


   public function testWrongQuery() {
      $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><QUERY>UNKNWON</QUERY></REQUEST>";
      $request = new \Glpi\Inventory\Request;
      $request->HandleContentType('application/xml');
      $request->handleRequest($data);
      $this->string($request->getResponse())->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY><ERROR>Query 'UNKNWON' is not supported.</ERROR></REPLY>\n");
   }

}
